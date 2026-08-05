<?php
/**
 * Plugin Name: Gabochie Maintenance Mode
 * Plugin URI: https://gabochiedesign.com
 * Description: Branded maintenance page (503) with admin toggle. Keeps wp-admin, login, PMPro login, webhook and cron reachable.
 * Version: 1.1.0
 * Author: Gabochie Design
 * License: GPL-2.0+
 * Text Domain: gabochie-maintenance
 */

if (!defined('ABSPATH')) exit;

class Gabochie_Maintenance {
    const OPTION_ACTIVE = 'gabochie_maintenance_active';
    const OPTION_MESSAGE = 'gabochie_maintenance_message';

    public static function instance() {
        static $instance = null;
        if (null === $instance) {
            $instance = new self();
        }
        return $instance;
    }

    private function __construct() {
        add_action('template_redirect', array($this, 'maybe_serve'), 0);
        add_action('admin_menu', array($this, 'add_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_bar_menu', array($this, 'admin_bar_notice'), 100);
    }

    public function is_active() {
        return (bool) get_option(self::OPTION_ACTIVE, 0);
    }

    public function maybe_serve() {
        if (!$this->is_active()) return;
        if (current_user_can('manage_options')) return;
        if (is_page('membership-login')) return;
        $this->serve_page();
    }

    private function serve_page() {
        status_header(503);
        nocache_headers();
        header('Retry-After: 3600');
        header('X-Robots-Tag: noindex, nofollow');
        $message = get_option(self::OPTION_MESSAGE, '');
        $brand = get_bloginfo('name');
        if (!$message) {
            $message = 'We\'re giving the website a quick tune-up so it works even better for your project. In the meantime, we\'re one message away.';
        }
        ?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="robots" content="noindex,nofollow">
<title><?php echo esc_html($brand); ?> — Back Shortly</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,500;0,700;1,500;1,600&family=Jost:wght@300;400;500;600&display=swap" rel="stylesheet">
<style>
  * { margin: 0; padding: 0; box-sizing: border-box; }
  html, body { height: 100%; }
  body {
    font-family: 'Jost', 'Segoe UI', system-ui, -apple-system, sans-serif;
    background:
      radial-gradient(1200px 600px at 80% -10%, rgba(26,122,110,0.35), transparent 60%),
      radial-gradient(900px 500px at 10% 110%, rgba(201,169,110,0.12), transparent 55%),
      linear-gradient(160deg, #072E28 0%, #0A4840 55%, #072E28 100%);
    color: #FAF7F2;
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: 100vh;
    padding: 24px;
    -webkit-font-smoothing: antialiased;
  }
  .panel {
    max-width: 560px;
    width: 100%;
    text-align: center;
    background: rgba(7,46,40,0.55);
    border: 1px solid rgba(201,169,110,0.28);
    border-radius: 24px;
    padding: 56px 40px 48px;
    box-shadow: 0 24px 80px rgba(0,0,0,0.35);
    backdrop-filter: blur(6px);
  }
  .logo-hex { width: 64px; height: 64px; display: block; margin: 0 auto 22px; }
  .brand-name {
    font-size: 15px; letter-spacing: 0.34em; font-weight: 500; color: #E8D5A8;
    text-transform: uppercase;
  }
  .brand-tag {
    font-family: 'Playfair Display', Georgia, serif; font-style: italic;
    font-size: 16px; color: #9FB8B2; margin-top: 4px;
  }
  .rule {
    width: 56px; height: 2px; background: #C9A96E; margin: 26px auto;
  }
  h1 {
    font-family: 'Playfair Display', Georgia, serif; font-weight: 600;
    font-size: clamp(26px, 4.4vw, 34px); line-height: 1.25; color: #FDFCF9;
    margin-bottom: 14px;
  }
  h1 em { font-style: italic; color: #E8D5A8; }
  p.lead {
    font-size: 16px; font-weight: 300; line-height: 1.65; color: #D4E8E4;
    margin-bottom: 30px;
  }
  .actions { display: flex; flex-direction: column; gap: 12px; align-items: stretch; }
  .btn {
    display: flex; align-items: center; justify-content: center; gap: 10px;
    padding: 14px 22px; border-radius: 12px; font-size: 15px; font-weight: 500;
    text-decoration: none; transition: opacity 0.2s ease, transform 0.2s ease;
  }
  .btn:hover { opacity: 0.92; transform: translateY(-1px); }
  .btn-wa { background: #C9A96E; color: #072E28; }
  .btn-ghost {
    background: transparent; color: #FAF7F2; border: 1px solid rgba(201,169,110,0.5);
  }
  .btn svg { width: 18px; height: 18px; flex: none; }
  .foot {
    margin-top: 30px; font-size: 13px; font-weight: 300; letter-spacing: 0.04em;
    color: #6B8A84;
  }
  .foot span { color: #E8D5A8; }
  @media (max-width: 480px) {
    .panel { padding: 44px 24px 38px; border-radius: 18px; }
  }
</style>
</head>
<body>
  <main class="panel">
    <svg class="logo-hex" viewBox="0 0 52 52" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
      <polygon points="26,4 47,15 47,37 26,48 5,37 5,15" fill="#072E28" stroke="#C9A96E" stroke-width="2"/>
      <text x="26" y="29" text-anchor="middle" font-family="Georgia,serif" font-size="17" font-weight="700" fill="#C9A96E">GD</text>
    </svg>
    <div class="brand-name"><?php echo esc_html(strtoupper($brand)); ?></div>
    <div class="brand-tag">Design.Value.Transformation</div>
    <div class="rule"></div>
    <h1>Back <em>shortly.</em></h1>
    <p class="lead"><?php echo esc_html($message); ?></p>
    <div class="actions">
      <a class="btn btn-wa" href="https://wa.me/233268248970?text=Hello%20Gabochie%20Design%20%E2%80%94%20I%20visited%20your%20site%20while%20it%20was%20in%20maintenance." target="_blank" rel="noopener">
        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 0 1-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 0 1-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 0 1 2.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0 0 12.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 0 0 5.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 0 0-3.48-8.413Z"/></svg>
        WhatsApp us
      </a>
      <a class="btn btn-ghost" href="tel:+233268248970">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
        +233 26 824 8970
      </a>
    </div>
    <div class="foot">Sakumono &middot; Tema &middot; Ghana &nbsp;&nbsp;|&nbsp;&nbsp; <span><?php echo esc_html(parse_url(home_url(), PHP_URL_HOST)); ?></span></div>
  </main>
</body>
</html>
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

    public function register_settings() {
        register_setting('gabochie_maintenance_group', self::OPTION_ACTIVE, array('type' => 'integer', 'default' => 0));
        register_setting('gabochie_maintenance_group', self::OPTION_MESSAGE, array('type' => 'string', 'sanitize_callback' => 'sanitize_text_field'));
    }

    public function settings_page() {
        $active = $this->is_active();
        ?>
        <div class="wrap">
            <h1>Maintenance Mode</h1>
            <?php if ($active) : ?>
            <div class="notice notice-warning"><p><strong>Maintenance is ON.</strong> Visitors see the branded "Back shortly" page (HTTP 503).</p></div>
            <?php else : ?>
            <div class="notice notice-success"><p>Maintenance is OFF — the site is live.</p></div>
            <?php endif; ?>
            <form method="post" action="options.php">
                <?php settings_fields('gabochie_maintenance_group'); ?>
                <table class="form-table">
                    <tr>
                        <th scope="row">Enable Maintenance</th>
                        <td>
                            <label><input type="checkbox" name="<?php echo esc_attr(self::OPTION_ACTIVE); ?>" value="1" <?php checked($active); ?>> Show the maintenance page to visitors</label>
                            <p class="description">Logged-in admins, wp-admin, wp-login, the PMPro login page, webhook and cron stay reachable.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Message</th>
                        <td>
                            <textarea name="<?php echo esc_attr(self::OPTION_MESSAGE); ?>" rows="3" class="large-text" placeholder="We're giving the website a quick tune-up..."><?php echo esc_textarea(get_option(self::OPTION_MESSAGE, '')); ?></textarea>
                            <p class="description">Shown on the maintenance page. Leave empty for the default message.</p>
                        </td>
                    </tr>
                </table>
                <?php submit_button('Save Settings'); ?>
            </form>
            <?php if ($active) : ?>
            <p><a class="button" href="<?php echo esc_url(home_url('/')); ?>" target="_blank" rel="noopener">Preview maintenance page</a></p>
            <?php endif; ?>
        </div>
        <?php
    }

    public function admin_bar_notice($wp_admin_bar) {
        if (!$this->is_active() || !current_user_can('manage_options')) return;
        $wp_admin_bar->add_node(array(
            'id' => 'gabochie-maintenance',
            'title' => '<span style="color:#C9A96E;">&#9888; Maintenance ON</span>',
            'href' => admin_url('options-general.php?page=gabochie-maintenance'),
            'meta' => array('title' => 'Maintenance mode is active')
        ));
    }
}

Gabochie_Maintenance::instance();
