<?php
/**
 * Gabochie Design Hub - smart launch checklist.
 *
 * Auto-detects the real state of each manual action from the database and
 * active plugins, so the owner only sees what actually needs doing.
 *
 * @package Gabochie_Design_Hub
 */

defined('ABSPATH') || exit;

final class Gabochie_Design_Hub {

    const MENU_SLUG = 'gabochie-design-hub';
    const OPTION    = 'gdh_overrides';

    /** @var Gabochie_Design_Hub|null */
    private static $instance = null;

    public static function instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('admin_menu', array($this, 'register_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_assets'));
        add_action('wp_ajax_gdh_toggle', array($this, 'ajax_toggle'));
        add_action('admin_init', array($this, 'handle_reset'));
    }

    /* ---------------------------------------------------------------------
     * Menu + assets
     * ------------------------------------------------------------------- */

    public function register_menu() {
        add_menu_page(
            'Gabochie Design Hub',
            'Gabochie Design',
            'manage_options',
            self::MENU_SLUG,
            array($this, 'render_page'),
            'dashicons-admin-generic',
            3
        );
    }

    public function enqueue_assets($hook) {
        if ('toplevel_page_' . self::MENU_SLUG !== $hook) {
            return;
        }
        wp_enqueue_style('gdh-admin', GDH_URL . 'admin.css', array(), GDH_VERSION);
    }

    /* ---------------------------------------------------------------------
     * Checklist definition - each item knows how to check its own status.
     * ------------------------------------------------------------------- */

    /**
     * @return array[] Each item: id, title, desc, group, auto (bool),
     *                 check (callable|null), url (callable|string),
     *                 url_label, manual_label.
     */
    private function items() {
        $admin_url = admin_url();

        return array(
            array(
                'id'    => 'local_site',
                'title' => 'Local site running',
                'desc'  => 'Start the Gabochie Design site in Local by Flywheel. Everything below is verified against this database.',
                'group' => 'Foundation',
                'auto'  => true,
                'check' => array($this, 'check_local_site'),
                'url'   => 'https://localwp.com/',
                'url_label' => 'Open Local by Flywheel',
            ),
            array(
                'id'    => 'ai_provider',
                'title' => 'AI provider configured',
                'desc'  => 'The site already has a Gemini API key wired into ghbc (provider chain + fallbacks). Verify it responds, or add your Anthropic key as primary if you prefer Claude.',
                'group' => 'Foundation',
                'auto'  => true,
                'check' => array($this, 'check_ai_provider'),
                'url'   => $admin_url . 'admin.php?page=ghbc-settings',
                'url_label' => 'Open GhBC LLM settings',
            ),
            array(
                'id'    => 'pmpro_tiers',
                'title' => 'Membership tiers decided',
                'desc'  => 'Three PMPro levels already exist (Free Lead, Active Client, Premium Service). Confirm the names, prices and what each tier unlocks in the community.',
                'group' => 'Foundation',
                'auto'  => true,
                'check' => array($this, 'check_pmpro_tiers'),
                'url'   => $admin_url . 'admin.php?page=pmpro-membershiplevels',
                'url_label' => 'Review membership levels',
            ),
            array(
                'id'    => 'community_url',
                'title' => 'Community URL decided',
                'desc'  => 'Pick the community home (e.g. /community/ or /ask/) and where it lives in the main navigation. The platform build will create the page once you confirm.',
                'group' => 'Foundation',
                'auto'  => false,
                'url'   => $admin_url . 'nav-menus.php',
                'url_label' => 'Open Menus',
            ),
            array(
                'id'    => 'expresspay',
                'title' => 'ExpressPay payment credentials',
                'desc'  => 'No ExpressPay merchant credentials are saved yet. Add the ExpressPay merchant ID + API key (sandbox first) under WooCommerce > Settings > Payments. Needed for paid consultations and premium membership checkout.',
                'group' => 'Payments & Email',
                'auto'  => true,
                'check' => array($this, 'check_expresspay'),
                'url'   => $admin_url . 'admin.php?page=wc-settings&tab=checkout',
                'url_label' => 'WooCommerce payment settings',
            ),
            array(
                'id'    => 'email_sending',
                'title' => 'Email sending connected',
                'desc'  => 'No Brevo API key is saved (gabochie-email). Add it so welcome emails, digests and notifications actually arrive. Test with the Settings > Gabochie Email test button.',
                'group' => 'Payments & Email',
                'auto'  => true,
                'check' => array($this, 'check_email_sending'),
                'url'   => $admin_url . 'options-general.php?page=gabochie-email',
                'url_label' => 'Open Gabochie Email settings',
            ),
            array(
                'id'    => 'sitekit',
                'title' => 'Google Site Kit connected',
                'desc'  => 'Site Kit is installed but not connected. Finish the OAuth flow in the browser (Google login can only be done by you). Required for search console + analytics on the community.',
                'group' => 'Payments & Email',
                'auto'  => true,
                'check' => array($this, 'check_sitekit'),
                'url'   => $admin_url . 'admin.php?page=googlesitekit-splash',
                'url_label' => 'Connect Site Kit',
            ),
            array(
                'id'    => 'community_build',
                'title' => 'Community platform built',
                'desc'  => 'The questions/answers engine, journey taxonomy, AI classification and lead detection. This is the automated build part - nothing manual, tracked here so you can see progress at a glance.',
                'group' => 'Community Platform',
                'auto'  => true,
                'check' => array($this, 'check_community_build'),
                'url'   => $admin_url . 'edit.php?post_type=gabochie_question',
                'url_label' => 'Manage questions',
            ),
            array(
                'id'    => 'seed_content',
                'title' => 'Review seed content',
                'desc'  => 'I will draft the journey taxonomy terms, ~10 sample questions/answers and the community page copy. Your review = approval before anything reaches the live site.',
                'group' => 'Community Platform',
                'auto'  => false,
                'url'   => $admin_url . 'edit.php',
                'url_label' => 'Browse posts',
            ),
            array(
                'id'    => 'go_live',
                'title' => 'Approve deployment to live',
                'desc'  => 'The webhook deploy path is ready, but nothing goes to gabochiedesign.com without your explicit go. Trigger the deploy when the local build is approved.',
                'group' => 'Launch',
                'auto'  => false,
                'url'   => 'https://gabochiedesign.com/wp-admin/',
                'url_label' => 'Live site admin',
            ),
            array(
                'id'    => 'live_tests',
                'title' => 'Post-launch live tests',
                'desc'  => 'Only you can do these on the live site: register a fresh test account, confirm the welcome email arrives, post a question as guest vs member.',
                'group' => 'Launch',
                'auto'  => false,
                'url'   => 'https://gabochiedesign.com/',
                'url_label' => 'Visit live site',
            ),
        );
    }

    /* ---------------------------------------------------------------------
     * Auto-checks (read real DB / plugin state)
     * ------------------------------------------------------------------- */

    private function check_local_site() {
        // We are rendering this page from inside the site, so it is up.
        return true;
    }

    private function check_ai_provider() {
        $key      = get_option('ghbc_llm_key', '');
        $provider = get_option('ghbc_llm_provider', '');
        return !empty($key) && !empty($provider);
    }

    private function check_pmpro_tiers() {
        if (!function_exists('pmpro_getAllLevels')) {
            return false;
        }
        $levels = pmpro_getAllLevels(false, true);
        return is_array($levels) && count($levels) > 0;
    }

    private function check_expresspay() {
        $settings = get_option('woocommerce_expresspay-gateway-for-woocommerce_settings', array());
        $enabled  = isset($settings['enabled']) ? $settings['enabled'] : 'no';
        $has_key  = !empty($settings['merchant_id']) || !empty($settings['api_key']);
        return 'yes' === $enabled && $has_key;
    }

    private function check_email_sending() {
        // gabochie-email is the single Brevo interceptor; its key proves sending is configured.
        return '' !== (string) get_option('gabochie_email_api_key', '');
    }

    private function check_sitekit() {
        $modules = get_option('googlesitekit_active_modules', array());
        return is_array($modules) && count($modules) > 0;
    }

    private function check_community_build() {
        // The platform ships as ghbc's community modules; flag once a
        // community CPT or page exists.
        $page = get_page_by_path('community');
        if ($page instanceof WP_Post && 'publish' === $page->post_status) {
            return true;
        }
        return post_type_exists('gabochie_question');
    }

    /* ---------------------------------------------------------------------
     * Overrides (for items that need a human verdict)
     * ------------------------------------------------------------------- */

    private function overrides() {
        return get_option(self::OPTION, array());
    }

    private function is_done_override($id) {
        $o = $this->overrides();
        return isset($o[$id]) && !empty($o[$id]);
    }

    public function ajax_toggle() {
        check_ajax_referer('gdh_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error('nope');
        }
        $id   = sanitize_key($_POST['id']);
        $done = !empty($_POST['done']) ? 1 : 0;
        $o    = $this->overrides();
        if ($done) {
            $o[$id] = current_time('mysql');
        } else {
            unset($o[$id]);
        }
        update_option(self::OPTION, $o, false);
        wp_send_json_success(array('done' => (bool) $done));
    }

    public function handle_reset() {
        if (isset($_GET['gdh_reset']) && check_admin_referer('gdh_reset')) {
            delete_option(self::OPTION);
            wp_safe_redirect(admin_url('admin.php?page=' . self::MENU_SLUG));
            exit;
        }
    }

    /* ---------------------------------------------------------------------
     * Render
     * ------------------------------------------------------------------- */

    public function render_page() {
        $items    = $this->items();
        $done_cnt = 0;
        $statuses = array();

        foreach ($items as $item) {
            $done = false;
            if (!empty($item['auto'])) {
                $done = call_user_func($item['check']);
            }
            if (!$done && $this->is_done_override($item['id'])) {
                $done = true;
            }
            $statuses[$item['id']] = $done;
            if ($done) {
                $done_cnt++;
            }
        }

        $pct  = $items ? round(($done_cnt / count($items)) * 100) : 0;
        $url  = admin_url('admin-ajax.php');
        $nonce = wp_create_nonce('gdh_nonce');
        $reset = wp_nonce_url(admin_url('admin.php?page=' . self::MENU_SLUG . '&gdh_reset=1'), 'gdh_reset');
        ?>
        <div class="wrap gdh-wrap">
            <div class="gdh-head">
                <div class="gdh-head-text">
                    <h1>Gabochie Design &mdash; Launch Checklist</h1>
                    <p>Live status of every manual action. Auto-detected from this site's database &mdash; no guesswork. The <strong>automated build</strong> happens in parallel; your jobs are the amber items.</p>
                </div>
                <div class="gdh-gauge" style="--pct: <?php echo esc_attr($pct); ?>">
                    <div class="gdh-gauge-fill"></div>
                    <span><?php echo (int) $done_cnt; ?>/<?php echo count($items); ?></span>
                </div>
            </div>

            <?php
            $groups = array();
            foreach ($items as $item) {
                $groups[$item['group']][] = $item;
            }
            ?>

            <?php foreach ($groups as $group => $group_items): ?>
                <h2 class="gdh-group"><?php echo esc_html($group); ?></h2>
                <div class="gdh-list">
                    <?php foreach ($group_items as $item):
                        $done = $statuses[$item['id']];
                        $url_label = isset($item['url_label']) ? $item['url_label'] : 'Open';
                        $target = strpos($item['url'], 'http') === 0 ? ' target="_blank" rel="noopener"' : '';
                        $auto_tag = !empty($item['auto']) ? 'auto' : 'manual';
                        ?>
                        <div class="gdh-item <?php echo $done ? 'is-done' : 'is-pending'; ?> <?php echo esc_attr($auto_tag); ?>" data-id="<?php echo esc_attr($item['id']); ?>">
                            <div class="gdh-item-status" aria-hidden="true"><?php echo $done ? '&#10003;' : '&#9679;'; ?></div>
                            <div class="gdh-item-body">
                                <div class="gdh-item-top">
                                    <h3><?php echo esc_html($item['title']); ?></h3>
                                    <span class="gdh-tag <?php echo esc_attr($auto_tag); ?>"><?php echo 'auto' === $auto_tag ? 'auto-detected' : 'your verdict'; ?></span>
                                </div>
                                <p><?php echo esc_html($item['desc']); ?></p>
                                <div class="gdh-item-actions">
                                    <a class="button button-secondary" href="<?php echo esc_url($item['url']); ?>"<?php echo $target; ?>><?php echo esc_html($url_label); ?></a>
                                    <?php if (empty($item['auto'])): ?>
                                        <button type="button" class="button gdh-toggle" data-done="<?php echo $done ? '1' : '0'; ?>"><?php echo $done ? 'Mark not done' : 'Mark done'; ?></button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>

            <div class="gdh-foot">
                <a class="button-link gdh-reset" href="<?php echo esc_url($reset); ?>">Reset manual verdicts</a>
            </div>
        </div>

        <script>
        (function () {
            var ajaxUrl = <?php echo wp_json_encode($url); ?>;
            var nonce   = <?php echo wp_json_encode($nonce); ?>;
            document.querySelectorAll('.gdh-toggle').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    var item  = btn.closest('.gdh-item');
                    var done  = btn.dataset.done === '1' ? 0 : 1;
                    btn.disabled = true;
                    var body = new URLSearchParams();
                    body.append('action', 'gdh_toggle');
                    body.append('nonce', nonce);
                    body.append('id', item.dataset.id);
                    body.append('done', done);
                    fetch(ajaxUrl, { method: 'POST', body: body, credentials: 'same-origin' })
                        .then(function (r) { return r.json(); })
                        .then(function () { location.reload(); })
                        .catch(function () { btn.disabled = false; });
                });
            });
        })();
        </script>
        <?php
    }
}

Gabochie_Design_Hub::instance();
