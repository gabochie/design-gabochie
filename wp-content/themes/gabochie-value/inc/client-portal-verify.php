<?php
/**
 * Task Verification + Live Status Endpoints
 * @package Gabochie_Value
 * 
 * GET /wp-json/gabochie/v1/verify-tasks
 *   Returns verification status for all phase checklist tasks.
 * GET /wp-json/gabochie/v1/live-status
 *   Returns comprehensive site status (core, theme, plugins, pages, content, etc.)
 * No auth required (read-only checks).
 */

if (!defined('ABSPATH')) exit;

add_action('rest_api_init', function () {
    // CORS for verify endpoints
    $uri = $_SERVER['REQUEST_URI'] ?? '';
    if (strpos($uri, '/gabochie/v1/verify') !== false || strpos($uri, '/gabochie/v1/live-status') !== false || strpos($uri, '/gabochie/v1/deploy-status') !== false) {
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type');
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            status_header(200);
            exit();
        }
    }

    register_rest_route('gabochie/v1', '/verify-tasks', array(
        'methods'             => 'GET',
        'callback'            => 'gabochie_verify_tasks',
        'permission_callback' => '__return_true',
    ));

    register_rest_route('gabochie/v1', '/live-status', array(
        'methods'             => 'GET',
        'callback'            => 'gabochie_live_status',
        'permission_callback' => '__return_true',
    ));

    // Deploy notification endpoint — POST from deploy scripts, GET from voice widget
    register_rest_route('gabochie/v1', '/deploy-status', array(
        'methods'             => 'GET,POST,OPTIONS',
        'callback'            => 'gabochie_deploy_status',
        'permission_callback' => '__return_true',
    ));
});

function gabochie_verify_tasks() {
    $results = array();

    // Phase A: Workspace Core
    $results['phaseA-0'] = _verify_cpt_exists('client_project', 'client_projects');
    $results['phaseA-1'] = _verify_cpt_exists('project_message', 'project_messages');
    $results['phaseA-2'] = _verify_cpt_exists('project_document', 'project_documents');
    $results['phaseA-3'] = _verify_page_exists('/dashboard/', 'Client Dashboard');
    $results['phaseA-4'] = _verify_rest_route('gabochie/v1', '/workspace');
    $results['phaseA-5'] = _verify_page_template('template-dashboard.php');
    $results['phaseA-6'] = _verify_page_exists('/client-login/', 'Client Login');
    $results['phaseA-7'] = _verify_plugin_active('gabochie-voice-agent');

    // Phase B: Readiness Score
    $results['phaseB-0'] = _verify_rest_route('gabochie/v1', '/projects/1/readiness');
    $results['phaseB-1'] = _verify_postmeta_exists('gabochie_readiness_score');
    $results['phaseB-2'] = _verify_rest_route('gabochie/v1', '/projects/1/readiness', 'POST');
    $results['phaseB-3'] = _verify_dashboard_widget();
    $results['phaseB-4'] = _verify_postmeta_exists('gabochie_readiness_strengths');
    $results['phaseB-5'] = _verify_postmeta_exists('gabochie_readiness_weaknesses');
    $results['phaseB-6'] = _verify_option_exists('ghbc_llm_provider');
    $results['phaseB-7'] = _verify_plugin_active('gabochie-ai-agentic-bridge');

    // Phase C: Feasibility Studio + CostSmart
    $results['phaseC-0'] = _verify_page_exists('/feasibility-studio/', 'Feasibility Studio');
    $results['phaseC-1'] = _verify_option_exists('gabochie_cost_engine');
    $results['phaseC-2'] = _verify_rest_route('gabochie/v1', '/cost-estimate');
    $results['phaseC-3'] = _verify_postmeta_exists('gabochie_project_budget');
    $results['phaseC-4'] = _verify_option_exists('gabochie_materials_db');
    $results['phaseC-5'] = _verify_page_exists('/cost-breakdown/', 'Cost Breakdown');
    $results['phaseC-6'] = _verify_page_exists('/feasibility-report/', 'Feasibility Report');

    // Phase D: Development Strategy + Go Live
    $results['phaseD-0'] = _verify_page_exists('/go-live/', 'Go Live');
    $results['phaseD-1'] = _verify_option_exists('gabochie_launch_checklist');
    $results['phaseD-2'] = _verify_option_exists('gabochie_precheck_list');
    $results['phaseD-3'] = _verify_plugin_active('gabochie-go-live');
    $results['phaseD-4'] = _verify_page_exists('/comparison/', 'Comparison');

    // Phase E: AI Copilot + Reports
    $results['phaseE-0'] = _verify_rest_route('gabochie-voice/v1', '/chat');
    $results['phaseE-1'] = _verify_option_exists('ghbc_llm_provider');
    $results['phaseE-2'] = _verify_page_exists('/reports/', 'Reports');
    $results['phaseE-3'] = _verify_rest_route('gabochie/v1', '/reports');
    $results['phaseE-4'] = _verify_page_exists('/admin-dashboard/', 'Admin Dashboard');
    $results['phaseE-5'] = _verify_rest_route('gabochie/v1', '/export');
    $results['phaseE-6'] = _verify_plugin_active('gabochie-command-center');

    return rest_ensure_response($results);
}

function gabochie_live_status() {
    // Ensure plugin helpers are available
    if (!function_exists('get_plugin_data')) {
        require_once ABSPATH . 'wp-admin/includes/plugin.php';
    }

    $status = array(
        'timestamp' => current_time('mysql'),
        'site_url'  => home_url('/'),
        'environment' => defined('WP_ENVIRONMENT_TYPE') ? WP_ENVIRONMENT_TYPE : 'production',
    );

    // 1. WordPress core
    $status['wordpress'] = array(
        'version' => get_bloginfo('version'),
        'multisite' => is_multisite(),
    );

    // 2. Theme
    $theme = wp_get_theme();
    $status['theme'] = array(
        'name' => $theme->get('Name'),
        'version' => $theme->get('Version'),
        'active' => true,
    );

    // 3. Plugins (active)
    $active_plugins = get_option('active_plugins', array());
    $status['plugins'] = array(
        'count' => count($active_plugins),
        'active' => array_map(function($p) {
            $data = get_plugin_data(WP_PLUGIN_DIR . '/' . $p);
            return array(
                'slug' => explode('/', $p)[0],
                'name' => $data['Name'],
                'version' => $data['Version'],
            );
        }, $active_plugins),
    );

    // 4. Custom Post Types
    $cpt_args = array('show_in_rest' => true, 'public' => true);
    $cpts = get_post_types($cpt_args, 'objects');
    $status['cpts'] = array(
        'count' => count($cpts),
        'types' => array_keys($cpts),
    );

    // 5. Pages (key membership/portal pages)
    $key_pages = array(
        'dashboard' => '/dashboard/',
        'login' => '/client-login/',
        'membership-levels' => '/membership-levels/',
        'membership-account' => '/membership-account/',
        'membership-checkout' => '/membership-checkout/',
        'membership-billing' => '/membership-billing/',
        'membership-cancel' => '/membership-cancel/',
        'membership-confirmation' => '/membership-confirmation/',
        'membership-invoices' => '/membership-invoices/',
        'onboarding' => '/onboarding/',
        'pricing' => '/pricing/',
        'community' => '/community/',
        'go-live' => '/go-live/',
        'shop' => '/shop/',
        'cart' => '/cart/',
        'checkout' => '/checkout/',
    );
    $pages_found = array();
    foreach ($key_pages as $name => $slug) {
        $page = get_page_by_path($slug);
        $pages_found[$name] = $page && $page->post_status === 'publish';
    }
    $status['pages'] = array(
        'found' => count(array_filter($pages_found)),
        'total' => count($pages_found),
        'details' => $pages_found,
    );

    // 6. Published posts count
    $status['content'] = array(
        'posts' => wp_count_posts('post')->publish,
        'pages' => wp_count_posts('page')->publish,
        'media' => wp_count_posts('attachment')->inherit,
    );

    // 7. WooCommerce
    $status['woocommerce'] = array(
        'active' => class_exists('WooCommerce'),
        'products' => post_type_exists('product') ? wp_count_posts('product')->publish : 0,
        'orders' => post_type_exists('shop_order') ? wp_count_posts('shop_order')->publish : 0,
    );

    // 8. Membership (PMPro)
    global $wpdb;
    $levels_table = $wpdb->prefix . 'pmpro_membership_levels';
    $level_count = $wpdb->get_var("SELECT COUNT(*) FROM $levels_table");
    $users_table = $wpdb->prefix . 'pmpro_memberships_users';
    $member_count = $wpdb->get_var("SELECT COUNT(*) FROM $users_table WHERE status = 'active'");
    $status['membership'] = array(
        'active' => defined('PMPRO_VERSION'),
        'levels' => (int) $level_count,
        'active_members' => (int) $member_count,
    );

    // 9. Payment gateways
    $gateways = array();
    if (class_exists('WooCommerce') && function_exists('WC')) {
        try {
            $wc = WC();
            if (is_object($wc) && method_exists($wc, 'payment_gateways')) {
                $pg = $wc->payment_gateways();
                if (is_object($pg) && method_exists($pg, 'get_available_payment_gateways')) {
                    $available = $pg->get_available_payment_gateways();
                    if (is_array($available)) {
                        foreach ($available as $id => $gw) {
                            $gateways[] = array(
                                'id' => $id,
                                'title' => is_object($gw) && method_exists($gw, 'get_title') ? $gw->get_title() : $id,
                                'enabled' => is_object($gw) && method_exists($gw, 'is_enabled') ? $gw->is_enabled() : false,
                            );
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            $gateways = array(array('id' => 'error', 'title' => 'Gateway check failed', 'enabled' => false));
        }
    }
    $status['payment_gateways'] = $gateways;

    // 10. REST API routes (key ones)
    $routes = rest_get_server()->get_routes();
    $key_routes = array(
        'gabochie/v1/workspace',
        'gabochie/v1/projects',
        'gabochie/v1/readiness',
        'gabochie-voice/v1/chat',
        'gabochie/v1/mcp-status',
    );
    $route_status = array();
    foreach ($key_routes as $r) {
        $route_status[$r] = isset($routes['/' . $r]);
    }
    $status['rest_routes'] = $route_status;

    // 11. PHP version
    $status['php'] = array(
        'version' => phpversion(),
    );

    // 12. Uploads writable
    $upload_dir = wp_upload_dir();
    $status['filesystem'] = array(
        'uploads_writable' => is_writable($upload_dir['basedir']),
    );

    // 13. Maintenance mode
    $status['maintenance'] = file_exists(ABSPATH . '.maintenance');

    // 14. Plugin auto-updates
    $auto_updates = get_option('auto_update_plugins', array());
    $status['auto_updates'] = array(
        'enabled' => !empty($auto_updates),
        'count' => is_array($auto_updates) ? count($auto_updates) : 0,
    );

    // 15. Deploy status (for Ama voice agent)
    $deploy = get_option('gabochie_last_deploy', array());
    $status['deploy'] = array(
        'version'    => isset($deploy['version']) ? $deploy['version'] : 'unknown',
        'timestamp'  => isset($deploy['timestamp']) ? $deploy['timestamp'] : '',
        'message'    => isset($deploy['message']) ? $deploy['message'] : '',
        'tag'        => isset($deploy['tag']) ? $deploy['tag'] : '',
    );

    // 16. Roadmap phase status (for Ama voice agent)
    $status['roadmap'] = array(
        'phases' => array(
            'A' => array('name' => 'Project Workspace Core', 'status' => 'live', 'pct' => 100),
            'B' => array('name' => 'Development Readiness Score', 'status' => 'live', 'pct' => 80),
            'C' => array('name' => 'Feasibility Studio + CostSmart', 'status' => 'planned', 'pct' => 0),
            'D' => array('name' => 'Development Strategy + Go Live', 'status' => 'partial', 'pct' => 40),
            'E' => array('name' => 'AI Copilot + Reports', 'status' => 'partial', 'pct' => 30),
            'F' => array('name' => 'User Identity & Trust System', 'status' => 'planned', 'pct' => 0),
            'G' => array('name' => 'Go-To-Market', 'status' => 'planned', 'pct' => 0),
        ),
        'live_features' => array(
            'Voice Agent (Ama v3.0)',
            'Client Dashboard',
            'Client Portal + Login',
            'Onboarding Wizard',
            'Pricing Page',
            'Community Q&A',
            'Development Readiness Quiz',
            'Build Cost Calculator',
            'Go Live Checklist Plugin',
            'Command Center Plugin',
            'Design Hub Plugin',
        ),
        'blocked_items' => array(
            'Moolre payment gateway (sandbox mode)',
            'Monthly billing (currently one-time)',
            'Feasibility Studio (Phase C)',
            'Development Intelligence Score engine',
            'Homepage repositioned as platform',
            'Thesis lead magnet',
        ),
    );

    return rest_ensure_response($status);
}

/* ─── Deploy Status Endpoint ─── */

function gabochie_deploy_status($request) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Store deploy notification (called by deploy scripts)
        $body = json_decode(file_get_contents('php://input'), true);
        if (!$body || !isset($body['version'])) {
            return new WP_Error('missing_version', 'version required', array('status' => 400));
        }
        $notification = array(
            'version'   => sanitize_text_field($body['version']),
            'tag'       => isset($body['tag']) ? sanitize_text_field($body['tag']) : '',
            'message'   => isset($body['message']) ? sanitize_text_field($body['message']) : '',
            'timestamp' => current_time('mysql'),
            'seen'      => false,
        );
        update_option('gabochie_last_deploy', $notification);
        // Also store in a transient for the voice widget (expires after 1 hour)
        set_transient('gabochie_deploy_notification', $notification, HOUR_IN_SECONDS);
        return rest_ensure_response(array('ok' => true, 'deploy' => $notification));
    }

    // GET: Return last deploy notification
    $deploy = get_option('gabochie_last_deploy', array());
    $notification = get_transient('gabochie_deploy_notification');
    return rest_ensure_response(array(
        'last_deploy'    => $deploy,
        'pending'        => $notification ? true : false,
        'notification'   => $notification,
    ));
}

/* ─── Verification Helpers ─── */

function _verify_cpt_exists($cpt, $plural) {
    $types = get_post_types(array('show_in_rest' => true), 'names');
    $found = in_array($cpt, $types);
    return array(
        'verified' => $found,
        'message'  => $found ? ucfirst($plural) . ' CPT active' : ucfirst($plural) . ' CPT not found',
    );
}

function _verify_page_exists($slug, $title) {
    $page = get_page_by_path($slug);
    $found = $page && $page->post_status === 'publish';
    return array(
        'verified' => $found,
        'message'  => $found ? "'$title' page exists (ID {$page->ID})" : "'$title' page not found at $slug",
    );
}

function _verify_page_template($template) {
    $pages = get_pages(array('meta_key' => '_wp_page_template', 'meta_value' => $template, 'number' => 1));
    $found = !empty($pages);
    return array(
        'verified' => $found,
        'message'  => $found ? "Template '$template' in use" : "Template '$template' not assigned to any page",
    );
}

function _verify_rest_route($namespace, $path, $method = 'GET') {
    $routes = rest_get_server()->get_routes();
    $full = '/' . $namespace . $path;
    $found = false;
    foreach ($routes as $route => $handlers) {
        if (strpos($route, $full) === 0) {
            foreach ($handlers as $handler) {
                if (is_array($handler['methods']) && in_array($method, $handler['methods'])) {
                    $found = true;
                    break;
                } elseif (is_string($handler['methods']) && $handler['methods'] === $method) {
                    $found = true;
                    break;
                }
            }
        }
        if ($found) break;
    }
    return array(
        'verified' => $found,
        'message'  => $found ? "REST route $method $full exists" : "REST route $method $full not found",
    );
}

function _verify_postmeta_exists($meta_key) {
    global $wpdb;
    $count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key = %s", $meta_key));
    $found = $count > 0;
    return array(
        'verified' => $found,
        'message'  => $found ? "Meta '$meta_key' exists ($count rows)" : "Meta '$meta_key' not found",
    );
}

function _verify_option_exists($option) {
    $val = get_option($option);
    $found = !empty($val);
    return array(
        'verified' => $found,
        'message'  => $found ? "Option '$option' is set" : "Option '$option' not configured",
    );
}

function _verify_plugin_active($plugin) {
    $active = in_array($plugin, get_option('active_plugins', array()));
    return array(
        'verified' => $active,
        'message'  => $active ? "Plugin '$plugin' active" : "Plugin '$plugin' not active",
    );
}

function _verify_dashboard_widget() {
    // Check if the readiness score function exists in theme
    $file = get_stylesheet_directory() . '/inc/client-portal-readiness.php';
    $exists = file_exists($file);
    return array(
        'verified' => $exists,
        'message'  => $exists ? 'Readiness Score engine file exists' : 'Readiness Score engine file missing',
    );
}
