<?php
defined('ABSPATH') || exit;

/**
 * Gabochie FluentCRM Integration
 *
 * Syncs WP users, PMPro members, WooCommerce customers into FluentCRM.
 * Creates custom fields, tags, and lists for Gabochie's business model.
 */

// --- Helpers ---

function gs_fluent_available(): bool {
    return defined('FLUENTCRM') && function_exists('FluentCrmApi');
}

function gs_fluent_get_tag_id(string $slug): ?int {
    if (!gs_fluent_available()) return null;
    $tag = \FluentCrm\App\Models\Tag::where('slug', sanitize_title($slug))->first();
    return $tag ? (int) $tag->id : null;
}

function gs_fluent_get_list_id(string $slug): ?int {
    if (!gs_fluent_available()) return null;
    $list = \FluentCrm\App\Models\Lists::where('slug', sanitize_title($slug))->first();
    return $list ? (int) $list->id : null;
}

function gs_fluent_sync_user(int $user_id): void {
    if (!gs_fluent_available()) return;

    $user = get_userdata($user_id);
    if (!$user || empty($user->user_email)) return;

    $tier = get_user_meta($user_id, '_gs_tier', true) ?: 'free';
    $registered = get_user_meta($user_id, '_gs_saas_registered', true);

    $data = [
        'email'      => $user->user_email,
        'first_name' => $user->first_name ?: $user->display_name,
        'last_name'  => $user->last_name,
        'user_id'    => $user_id,
        'status'     => 'subscribed',
        'contact_type' => 'lead',
        'custom_values' => [
            'gs_saas_tier' => $tier,
        ],
    ];

    if (!empty($registered)) {
        $data['created_at'] = $registered;
    }

    $contact = FluentCrmApi('contacts')->createOrUpdate($data, false, false);
    if (is_wp_error($contact) || !$contact) return;

    $contact_id = $contact->id;

    $tag_slug = 'saas-member-' . $tier;
    $tag_id = gs_fluent_get_tag_id($tag_slug);
    if ($tag_id) {
        $contact->attachTags([$tag_id]);
    }

    $list_id = gs_fluent_get_list_id('saas-users');
    if ($list_id) {
        $contact->attachLists([$list_id]);
    }

    $roles = $user->roles;
    if (in_array('administrator', $roles, true)) {
        $admin_tag = gs_fluent_get_tag_id('staff');
        if ($admin_tag) $contact->attachTags([$admin_tag]);
    }
}

// --- One-time setup ---

function gs_fluent_setup(): void {
    if (!gs_fluent_available()) return;

    if (get_option('gs_fluent_setup_done')) return;

    $tags = [
        ['title' => 'SaaS Member (Free)',     'slug' => 'saas-member-free'],
        ['title' => 'SaaS Member (Pro)',      'slug' => 'saas-member-pro'],
        ['title' => 'SaaS Member (Enterprise)', 'slug' => 'saas-member-enterprise'],
        ['title' => 'WooCommerce Customer',   'slug' => 'woocommerce-customer'],
        ['title' => 'PMPro Member',           'slug' => 'pmpro-member'],
        ['title' => 'Active Client',          'slug' => 'active-client'],
        ['title' => 'Lead',                   'slug' => 'lead'],
        ['title' => 'Staff',                  'slug' => 'staff'],
        ['title' => 'Build Cost User',        'slug' => 'build-cost-user'],
        ['title' => 'Project Owner',          'slug' => 'project-owner'],
    ];

    foreach ($tags as $t) {
        $existing = \FluentCrm\App\Models\Tag::where('slug', $t['slug'])->first();
        if (!$existing) {
            \FluentCrm\App\Models\Tag::create($t);
        }
    }

    $lists = [
        ['title' => 'SaaS Users',         'slug' => 'saas-users'],
        ['title' => 'Active Clients',     'slug' => 'active-clients'],
        ['title' => 'Leads',              'slug' => 'leads'],
        ['title' => 'Newsletter',         'slug' => 'newsletter'],
        ['title' => 'WooCommerce Customers', 'slug' => 'woocommerce-customers'],
    ];

    foreach ($lists as $l) {
        $existing = \FluentCrm\App\Models\Lists::where('slug', $l['slug'])->first();
        if (!$existing) {
            \FluentCrm\App\Models\Lists::create($l);
        }
    }

    $customFieldsModel = new \FluentCrm\App\Models\CustomContactField();
    $existingFields = $customFieldsModel->getGlobalFields();
    $existingSlugs = array_map(function ($f) {
        return $f['slug'] ?? '';
    }, $existingFields['fields']);

    $newFields = [
        [
            'label'       => 'SaaS Tier',
            'slug'        => 'gs_saas_tier',
            'type'        => 'single-select',
            'placeholder' => 'Select tier',
            'options'     => ['Free', 'Pro', 'Enterprise'],
        ],
        [
            'label'       => 'Company',
            'slug'        => 'gs_company',
            'type'        => 'text',
            'placeholder' => 'Company name',
        ],
        [
            'label'       => 'Team Size',
            'slug'        => 'gs_team_size',
            'type'        => 'number',
            'placeholder' => 'Number of team members',
        ],
        [
            'label'       => 'ExpressPay Customer ID',
            'slug'        => 'gs_expresspay_customer',
            'type'        => 'text',
            'placeholder' => 'ExpressPay customer ref',
        ],
        [
            'label'       => 'SaaS Registered Date',
            'slug'        => 'gs_saas_registered',
            'type'        => 'date',
            'placeholder' => 'Registration date',
        ],
    ];

    $merged = $existingFields['fields'];
    foreach ($newFields as $nf) {
        if (!in_array($nf['slug'], $existingSlugs, true)) {
            $merged[] = $nf;
        }
    }
    $customFieldsModel->saveGlobalFields($merged);

    // Sync all existing WP users
    $users = get_users(['fields' => 'ID', 'number' => 500]);
    foreach ($users as $uid) {
        gs_fluent_sync_user($uid);
    }

    update_option('gs_fluent_setup_done', time());
}

// --- Hooks ---

add_action('user_register', function (int $user_id) {
    gs_fluent_sync_user($user_id);
}, 10, 1);

add_action('profile_update', function (int $user_id) {
    gs_fluent_sync_user($user_id);
}, 10, 1);

add_action('gs_after_team_invite', function (int $invited_user_id) {
    if (gs_fluent_available()) {
        $contact = FluentCrmApi('contacts')->createOrUpdate([
            'user_id' => $invited_user_id,
        ], false, false);
        if ($contact && !is_wp_error($contact)) {
            $tag_id = gs_fluent_get_tag_id('active-client');
            if ($tag_id) $contact->attachTags([$tag_id]);
            $list_id = gs_fluent_get_list_id('active-clients');
            if ($list_id) $contact->attachLists([$list_id]);
        }
    }
});

add_action('pmpro_after_change_membership_level', function (int $level_id, int $user_id) {
    if (!gs_fluent_available()) return;
    $contact = FluentCrmApi('contacts')->createOrUpdate([
        'user_id' => $user_id,
    ], false, false);
    if (!$contact || is_wp_error($contact)) return;

    $tag_id = gs_fluent_get_tag_id('pmpro-member');
    if ($tag_id) $contact->attachTags([$tag_id]);

    $list_id = gs_fluent_get_list_id('active-clients');
    if ($list_id) $contact->attachLists([$list_id]);

    $contact->updateMeta('custom_values', [
        'gs_saas_tier' => $level_id === 1 ? 'pro' : 'enterprise',
    ]);
}, 10, 2);

add_action('woocommerce_new_order', function (int $order_id) {
    if (!gs_fluent_available()) return;
    $order = wc_get_order($order_id);
    if (!$order) return;

    $user_id = $order->get_customer_id('edit');
    if (!$user_id) return;

    $contact = FluentCrmApi('contacts')->createOrUpdate([
        'user_id'      => $user_id,
        'contact_type' => 'customer',
    ], false, false);
    if (!$contact || is_wp_error($contact)) return;

    $tag_id = gs_fluent_get_tag_id('woocommerce-customer');
    if ($tag_id) $contact->attachTags([$tag_id]);
});

// --- Admin setup trigger ---

add_action('admin_init', function () {
    if (defined('DOING_AJAX') && DOING_AJAX) return;
    if (!current_user_can('manage_options')) return;
    if (!gs_fluent_available()) return;

    if (isset($_GET['gs_fluent_setup']) && $_GET['gs_fluent_setup'] === '1') {
        gs_fluent_setup();
        wp_redirect(remove_query_arg('gs_fluent_setup'));
        exit;
    }
});

// --- Settings section ---

add_filter('gs_admin_sections', function (array $sections) {
    if (!gs_fluent_available()) return $sections;

    $setup_done = get_option('gs_fluent_setup_done');
    $sections['gs_crm'] = [
        'title' => 'CRM (FluentCRM)',
        'intro' => 'Manage customer relationships. Contacts sync automatically from registrations, PMPro memberships, and WooCommerce orders.',
        'fields' => [
            'gs_fluent_status' => [
                'label' => 'FluentCRM Status',
                'type'  => 'info',
                'value' => 'Active & connected',
            ],
            'gs_fluent_setup' => [
                'label' => 'Initial Setup',
                'type'  => 'info',
                'value' => $setup_done
                    ? 'Completed ' . date('Y-m-d H:i', $setup_done)
                    : 'Not run yet',
            ],
            'gs_fluent_contacts' => [
                'label' => 'Contacts in CRM',
                'type'  => 'info',
                'value' => \FluentCrm\App\Models\Subscriber::count() . ' contacts',
            ],
            'gs_fluent_tags' => [
                'label' => 'Tags',
                'type'  => 'info',
                'value' => \FluentCrm\App\Models\Tag::count() . ' tags configured',
            ],
            'gs_fluent_lists' => [
                'label' => 'Lists',
                'type'  => 'info',
                'value' => \FluentCrm\App\Models\Lists::count() . ' lists configured',
            ],
        ],
        'actions' => [
            [
                'label' => $setup_done ? 'Re-run CRM Setup' : 'Run CRM Setup',
                'url'   => add_query_arg('gs_fluent_setup', '1'),
                'class' => $setup_done ? 'button-secondary' : 'button-primary',
            ],
        ],
    ];
    return $sections;
});
