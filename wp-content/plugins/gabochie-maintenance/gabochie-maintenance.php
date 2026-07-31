<?php
/**
 * Plugin Name: Gabochie Maintenance Mode
 * Plugin URI: https://gabochiedesign.com
 * Description: Brand-aware maintenance mode with countdown, email capture, and admin bypass.
 * Version: 1.0.0
 * Author: Gabochie Design
 * License: GPL-2.0+
 * Text Domain: gabochie-maintenance
 */

if (!defined('ABSPATH')) exit;

class Gabochie_Maintenance {
    private static $instance = null;
    const OPTION = 'gabochie_maintenance_active';
    const END_OPTION = 'gabochie_maintenance_end';
    const EMAILS_OPTION = 'gabochie_maintenance_emails';
    const MESSAGE_OPTION = 'gabochie_maintenance_message';

    public static function instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('wp', array($this, 'maybe_activate'));
        add_action('admin_menu', array($this, 'add_menu'));
        add_action('admin_init', array($this, 'save_settings'));
        add_action('wp_ajax_gabochie_maintenance_save', array($this, 'ajax_save'));
        add_action('wp_ajax_gabochie_maintenance_export', array($this, 'ajax_export'));
        add_action('wp_ajax_gabochie_maintenance_delete', array($this, 'ajax_delete'));
        add_action('wp_head', array($this, 'admin_bar_bypass'));
        add_shortcode('maintenance_countdown', array($this, 'countdown_shortcode'));
    }

    public function is_active() {
        return get_option(self::OPTION, 0) && !is_user_logged_in();
    }

    public function maybe_activate() {
        $end = get_option(self::END_OPTION, '');
        if ($end && current_time('timestamp') > strtotime($end)) {
            update_option(self::OPTION, 0);
            return;
        }
        if ($this->is_active()) {
            $this->show_maintenance();
        }
    }

    public function admin_bar_bypass() {
        if (get_option(self::OPTION, 0) && is_user_logged_in() && current_user_can('manage_options')) {
            echo '<style>#wpadminbar { display: block !important; } .maintenance-bypass { position:fixed; bottom:20px; right:20px; z-index:99999; background:#0D5C52; color:#fff; padding:12px 20px; border-radius:8px; font-family:Jost,sans-serif; font-weight:600; cursor:pointer; box-shadow:0 4px 16px rgba(0,0,0,0.3); text-decoration:none; } .maintenance-bypass:hover { background:#C9A96E; color:#1A1A1A; }</style>';
            echo '<a class="maintenance-bypass" href="' . esc_url(home_url('/')) . '?gabochie_bypass=1">Site is in Maintenance Mode — View as Admin</a>';
        }
    }

    public function show_maintenance() {
        $message = get_option(self::MESSAGE_OPTION, '');
        $end_time = get_option(self::END_OPTION, '');
        $end_ts = $end_time ? strtotime($end_time) : 0;
        $brand = get_bloginfo('name');
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Maintenance Mode — <?php echo esc_html($brand); ?></title>
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=Jost:wght@300;400;500;600;700&display=swap" rel="stylesheet">
        <style>
            * { margin:0; padding:0; box-sizing:border-box; }
            body {
                font-family:'Jost',system-ui,sans-serif;
                background:#072E28;
                color:#fff;
                min-height:100vh;
                display:flex;
                align-items:center;
                justify-content:center;
                overflow:hidden;
                position:relative;
            }
            .m-wrap {
                text-align:center;
                max-width:600px;
                padding:40px 24px;
                position:relative;
                z-index:2;
            }
            .m-brand {
                font-family:'Playfair Display',Georgia,serif;
                font-size:1.25rem;
                font-weight:700;
                letter-spacing:0.1em;
                margin-bottom:48px;
                color:#C9A96E;
            }
            .m-icon {
                width:80px; height:80px;
                border:2px solid #C9A96E44;
                border-radius:50%;
                display:inline-flex;
                align-items:center;
                justify-content:center;
                margin-bottom:32px;
            }
            .m-icon svg { width:36px; height:36px; stroke:#C9A96E; fill:none; stroke-width:1.5; }
            h1 {
                font-family:'Playfair Display',Georgia,serif;
                font-size:clamp(2rem,5vw,3rem);
                font-weight:700;
                line-height:1.15;
                margin-bottom:16px;
            }
            h1 em { color:#C9A96E; font-style:italic; }
            .m-sub {
                font-size:1.1rem;
                color:#B0B8C1;
                line-height:1.6;
                margin-bottom:40px;
            }
            .m-countdown {
                display:flex;
                gap:16px;
                justify-content:center;
                margin-bottom:40px;
            }
            .m-cd-item {
                min-width:80px;
                padding:16px;
                background:#0D5C5233;
                border:1px solid #0D5C5266;
                border-radius:12px;
            }
            .m-cd-n {
                font-size:2rem;
                font-weight:700;
                font-family:'Playfair Display',Georgia,serif;
                line-height:1;
                color:#C9A96E;
            }
            .m-cd-l {
                font-size:0.7rem;
                text-transform:uppercase;
                letter-spacing:0.1em;
                color:#646970;
                margin-top:4px;
            }
            .m-form {
                display:flex;
                gap:8px;
                max-width:440px;
                margin:0 auto;
            }
            .m-form input[type="email"] {
                flex:1;
                padding:14px 20px;
                border:1px solid #0D5C5266;
                border-radius:8px;
                background:#0D5C5211;
                color:#fff;
                font-family:'Jost',sans-serif;
                font-size:0.95rem;
                outline:none;
                transition:border-color 0.2s;
            }
            .m-form input[type="email"]::placeholder { color:#646970; }
            .m-form input[type="email"]:focus { border-color:#C9A96E; }
            .m-form button {
                padding:14px 28px;
                background:#C9A96E;
                color:#072E28;
                border:none;
                border-radius:8px;
                font-family:'Jost',sans-serif;
                font-weight:600;
                font-size:0.95rem;
                cursor:pointer;
                transition:background 0.2s;
                white-space:nowrap;
            }
            .m-form button:hover { background:#b8944a; }
            .m-msg { margin-top:16px; font-size:0.85rem; color:#00a32a; display:none; }
            .m-msg.show { display:block; }
            .m-footer {
                margin-top:48px;
                font-size:0.8rem;
                color:#646970;
            }
            .m-footer a { color:#C9A96E; text-decoration:none; }

            /* Animated background */
            .bg-circles {
                position:fixed; top:0; left:0; width:100%; height:100%;
                pointer-events:none; z-index:0; overflow:hidden;
            }
            .bg-circles span {
                position:absolute;
                border-radius:50%;
                border:1px solid #0D5C5215;
                animation:float 20s infinite ease-in-out;
            }
            .bg-circles span:nth-child(1) { width:400px; height:400px; top:10%; left:-5%; animation-delay:0s; }
            .bg-circles span:nth-child(2) { width:300px; height:300px; top:60%; right:-8%; animation-delay:4s; border-color:#C9A96E10; }
            .bg-circles span:nth-child(3) { width:500px; height:500px; bottom:-15%; left:30%; animation-delay:8s; }
            .bg-circles span:nth-child(4) { width:200px; height:200px; top:30%; right:20%; animation-delay:12s; border-color:#C9A96E08; }

            @keyframes float {
                0%, 100% { transform:translate(0,0) scale(1); }
                33% { transform:translate(30px,-30px) scale(1.05); }
                66% { transform:translate(-20px,20px) scale(0.95); }
            }

            @media (max-width:600px) {
                .m-countdown { gap:8px; }
                .m-cd-item { min-width:64px; padding:12px 8px; }
                .m-cd-n { font-size:1.5rem; }
                .m-form { flex-direction:column; }
            }
        </style>
        </head>
        <body>
        <div class="bg-circles">
            <span></span><span></span><span></span><span></span>
        </div>
        <div class="m-wrap">
            <div class="m-brand"><?php echo esc_html($brand); ?></div>
            <div class="m-icon">
                <svg viewBox="0 0 24 24"><path d="M3 21h18M9 8h1M9 12h1M9 16h1M14 8h1M14 12h1M14 16h1M5 21V5a2 2 0 012-2h10a2 2 0 012 2v16"/></svg>
            </div>
            <h1>We're Building<br>Something <em>Intelligent</em></h1>
            <p class="m-sub">Our AI-powered architecture platform is being upgraded. We'll be back soon with smarter tools, faster designs, and a better experience.</p>

            <?php if ($end_ts > time()) : ?>
            <div class="m-countdown" id="m-countdown" data-end="<?php echo esc_attr($end_ts); ?>">
                <div class="m-cd-item"><div class="m-cd-n" id="m-d">00</div><div class="m-cd-l">Days</div></div>
                <div class="m-cd-item"><div class="m-cd-n" id="m-h">00</div><div class="m-cd-l">Hours</div></div>
                <div class="m-cd-item"><div class="m-cd-n" id="m-m">00</div><div class="m-cd-l">Minutes</div></div>
                <div class="m-cd-item"><div class="m-cd-n" id="m-s">00</div><div class="m-cd-l">Seconds</div></div>
            </div>
            <?php endif; ?>

            <form class="m-form" id="m-form">
                <input type="email" name="email" placeholder="Enter your email for launch updates" required>
                <button type="submit">Notify Me</button>
            </form>
            <div class="m-msg" id="m-msg">Thanks! We'll notify you when we launch.</div>

            <?php if ($message) : ?>
            <p class="m-sub" style="margin-top:32px;font-size:0.9rem;"><?php echo esc_html($message); ?></p>
            <?php endif; ?>

            <div class="m-footer">
                <p>&copy; <?php echo date('Y'); ?> <?php echo esc_html($brand); ?>. All rights reserved.</p>
            </div>
        </div>

        <script>
        (function(){
            var el = document.getElementById('m-countdown');
            if (!el) return;
            var end = parseInt(el.dataset.end) * 1000;
            function tick() {
                var now = Date.now(), diff = Math.max(0, end - now);
                var d = Math.floor(diff / 86400000);
                var h = Math.floor((diff % 86400000) / 3600000);
                var m = Math.floor((diff % 3600000) / 60000);
                var s = Math.floor((diff % 60000) / 1000);
                document.getElementById('m-d').textContent = String(d).padStart(2,'0');
                document.getElementById('m-h').textContent = String(h).padStart(2,'0');
                document.getElementById('m-m').textContent = String(m).padStart(2,'0');
                document.getElementById('m-s').textContent = String(s).padStart(2,'0');
                if (diff > 0) requestAnimationFrame(tick);
            }
            tick();
        })();
        </script>
        <script>
        document.getElementById('m-form').addEventListener('submit', function(e) {
            e.preventDefault();
            var email = this.querySelector('input[name="email"]').value;
            var fd = new FormData();
            fd.append('action', 'gabochie_maintenance_save');
            fd.append('email', email);
            fd.append('_ajax_nonce', '<?php echo wp_create_nonce('gabochie_maintenance'); ?>');
            fetch('<?php echo admin_url('admin-ajax.php'); ?>', { method:'POST', body:fd })
                .then(function(r){ return r.json(); })
                .then(function(res){
                    if (res.success) {
                        document.getElementById('m-msg').classList.add('show');
                        document.getElementById('m-form').reset();
                    }
                });
        });
        </script>
        </body></html>
        <?php
        exit;
    }

    public function add_menu() {
        add_options_page(
            'Maintenance Mode',
            'Maintenance Mode',
            'manage_options',
            'gabochie-maintenance',
            array($this, 'settings_page')
        );
    }

    public function settings_page() {
        if (isset($_POST['gabochie_maintenance_nonce']) && wp_verify_nonce($_POST['gabochie_maintenance_nonce'], 'gabochie_maintenance_settings')) {
            $active = isset($_POST['active']) ? 1 : 0;
            update_option(self::OPTION, $active);
            update_option(self::END_OPTION, sanitize_text_field($_POST['end_time'] ?? ''));
            update_option(self::MESSAGE_OPTION, sanitize_text_field($_POST['message'] ?? ''));
            echo '<div class="notice notice-success is-dismissible"><p>Settings saved.</p></div>';
        }

        $active = get_option(self::OPTION, 0);
        $end_time = get_option(self::END_OPTION, '');
        $message = get_option(self::MESSAGE_OPTION, '');
        $emails = get_option(self::EMAILS_OPTION, array());
        ?>
        <div class="wrap">
            <h1>Maintenance Mode</h1>
            <form method="post">
                <?php wp_nonce_field('gabochie_maintenance_settings', 'gabochie_maintenance_nonce'); ?>
                <table class="form-table">
                    <tr>
                        <th scope="row">Enable Maintenance</th>
                        <td><label><input type="checkbox" name="active" value="1" <?php checked($active); ?>> Show maintenance page to non-logged-in visitors</label></td>
                    </tr>
                    <tr>
                        <th scope="row">End Date & Time</th>
                        <td><input type="datetime-local" name="end_time" value="<?php echo esc_attr($end_time); ?>" class="regular-text">
                        <p class="description">Countdown timer shows on the maintenance page. Leave empty for no countdown.</p></td>
                    </tr>
                    <tr>
                        <th scope="row">Custom Message</th>
                        <td><textarea name="message" rows="3" class="large-text" placeholder="e.g. Launching June 2026"><?php echo esc_textarea($message); ?></textarea></td>
                    </tr>
                </table>
                <p class="submit">
                    <button type="submit" class="button button-primary">Save Settings</button>
                    <a href="<?php echo wp_nonce_url(admin_url('admin-ajax.php?action=gabochie_maintenance_export'), 'gabochie_maintenance'); ?>" class="button" style="margin-left:8px;">Export Emails (CSV)</a>
                </p>
            </form>

            <hr>
            <h2>Captured Emails (<?php echo count($emails); ?>)</h2>
            <?php if (!empty($emails)) : ?>
            <table class="wp-list-table widefat fixed striped">
                <thead><tr><th>Email</th><th>Date</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($emails as $i => $e) : ?>
                <tr>
                    <td><?php echo esc_html($e['email']); ?></td>
                    <td><?php echo esc_html($e['date']); ?></td>
                    <td><a href="<?php echo wp_nonce_url(admin_url('admin-ajax.php?action=gabochie_maintenance_delete&index=' . $i), 'gabochie_maintenance'); ?>" class="button button-small" style="color:#a00;" onclick="return confirm('Delete this email?')">Delete</a></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php else : ?>
            <p>No emails captured yet.</p>
            <?php endif; ?>
        </div>
        <?php
    }

    public function save_settings() {
        if (wp_doing_ajax() && isset($_POST['action']) && $_POST['action'] === 'gabochie_maintenance_save') {
            check_ajax_referer('gabochie_maintenance');
            $email = sanitize_email($_POST['email'] ?? '');
            if (!is_email($email)) {
                wp_send_json_error('Invalid email');
            }
            $emails = get_option(self::EMAILS_OPTION, array());
            $emails[] = array('email' => $email, 'date' => current_time('mysql'));
            update_option(self::EMAILS_OPTION, $emails);
            wp_send_json_success();
        }
    }

    public function ajax_export() {
        if (!current_user_can('manage_options')) wp_die('Unauthorized');
        $emails = get_option(self::EMAILS_OPTION, array());
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="maintenance-emails-' . date('Y-m-d') . '.csv"');
        $fp = fopen('php://output', 'w');
        fputcsv($fp, array('Email', 'Date'));
        foreach ($emails as $e) {
            fputcsv($fp, array($e['email'], $e['date']));
        }
        fclose($fp);
        exit;
    }

    public function ajax_delete() {
        if (!current_user_can('manage_options')) wp_die('Unauthorized');
        check_admin_referer('gabochie_maintenance');
        $index = intval($_GET['index'] ?? -1);
        $emails = get_option(self::EMAILS_OPTION, array());
        if (isset($emails[$index])) {
            array_splice($emails, $index, 1);
            update_option(self::EMAILS_OPTION, $emails);
        }
        wp_redirect(admin_url('options-general.php?page=gabochie-maintenance'));
        exit;
    }

    public function countdown_shortcode($atts) {
        $end = get_option(self::END_OPTION, '');
        if (!$end) return '';
        $ts = strtotime($end);
        return '<div class="m-countdown" data-end="' . esc_attr($ts) . '"><div class="m-cd-item"><div class="m-cd-n" id="m-d">--</div><div class="m-cd-l">Days</div></div><div class="m-cd-item"><div class="m-cd-n" id="m-h">--</div><div class="m-cd-l">Hours</div></div><div class="m-cd-item"><div class="m-cd-n" id="m-m">--</div><div class="m-cd-l">Minutes</div></div><div class="m-cd-item"><div class="m-cd-n" id="m-s">--</div><div class="m-cd-l">Seconds</div></div></div>';
    }
}

Gabochie_Maintenance::instance();
