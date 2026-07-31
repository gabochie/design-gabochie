<?php
defined('ABSPATH') || exit;

class GAB_Admin {
    private static $instance = null;

    public static function instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('admin_menu', array($this, 'add_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_assets'));
        add_action('admin_init', array($this, 'register_settings'));
    }

    public function add_menu() {
        add_menu_page(
            'Authority Builder',
            'Authority',
            'manage_options',
            'gab-dashboard',
            array($this, 'render_dashboard'),
            'dashicons-groups',
            69
        );
        add_submenu_page(
            'gab-dashboard',
            'Dashboard',
            'Dashboard',
            'manage_options',
            'gab-dashboard',
            array($this, 'render_dashboard')
        );
        add_submenu_page(
            'gab-dashboard',
            'Authors',
            'Authors',
            'manage_options',
            'edit.php?post_type=gab_author'
        );
        add_submenu_page(
            'gab-dashboard',
            'Case Studies',
            'Case Studies',
            'manage_options',
            'edit.php?post_type=gab_case_study'
        );
        add_submenu_page(
            'gab-dashboard',
            'Settings',
            'Settings',
            'manage_options',
            'gab-settings',
            array($this, 'render_settings')
        );
    }

    public function enqueue_assets($hook) {
        if (strpos($hook, 'gab-') === false) return;
        wp_enqueue_style('gab-admin', GABUILD_URL . 'assets/admin.css', array(), GABUILD_VERSION);
        wp_enqueue_script('gab-admin', GABUILD_URL . 'assets/admin.js', array('jquery'), GABUILD_VERSION, true);
        wp_localize_script('gab-admin', 'gab', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('gab_ajax'),
        ));
    }

    public function register_settings() {
        register_setting('gab_settings', 'gab_default_author_id', array('default' => 0));
        register_setting('gab_settings', 'gab_show_author_on_posts', array('default' => '1'));
    }

    public function render_dashboard() {
        $authors = get_posts(array(
            'post_type' => 'gab_author',
            'posts_per_page' => -1,
            'post_status' => 'publish',
            'fields' => 'ids',
        ));
        $studies = get_posts(array(
            'post_type' => 'gab_case_study',
            'posts_per_page' => -1,
            'post_status' => 'publish',
            'fields' => 'ids',
        ));
        $credentials = get_terms(array('taxonomy' => 'gab_credential', 'hide_empty' => false));
        ?>
        <div class="wrap gab-wrap">
            <h1>Authority Builder — Dashboard</h1>
            <p style="color:#555;">Manage author bio pages, credentials, and project case studies to build topical authority.</p>

            <div class="gab-cards">
                <div class="gab-card"><strong><?php echo count($authors); ?></strong><span>Authors</span></div>
                <div class="gab-card"><strong><?php echo count($studies); ?></strong><span>Case Studies</span></div>
                <div class="gab-card"><strong><?php echo count($credentials); ?></strong><span>Credentials</span></div>
            </div>

            <h2 style="margin-top:24px;">Quick Links</h2>
            <p>
                <a href="<?php echo admin_url('edit.php?post_type=gab_author'); ?>" class="button">Manage Authors</a>
                <a href="<?php echo admin_url('edit.php?post_type=gab_case_study'); ?>" class="button">Manage Case Studies</a>
                <a href="<?php echo admin_url('edit-tags.php?taxonomy=gab_credential&post_type=gab_author'); ?>" class="button">Manage Credentials</a>
            </p>

            <h2 style="margin-top:24px;">Usage</h2>
            <p>Use <code>[gab_author_bio id="X"]</code> to display an author bio card. Use <code>[gab_case_studies count="3"]</code> to display a case study grid.</p>
        </div>
        <?php
    }

    public function render_settings() {
        $default_author = get_option('gab_default_author_id', 0);
        $show_on_posts = get_option('gab_show_author_on_posts', '1');
        $authors = get_posts(array(
            'post_type' => 'gab_author',
            'posts_per_page' => -1,
            'post_status' => 'publish',
        ));
        ?>
        <div class="wrap gab-wrap">
            <h1>Authority Builder Settings</h1>
            <form method="post" action="options.php">
                <?php settings_fields('gab_settings'); ?>
                <table class="form-table">
                    <tr>
                        <th scope="row">Default Author</th>
                        <td>
                            <select name="gab_default_author_id">
                                <option value="0">— Auto (first found) —</option>
                                <?php foreach ($authors as $a): ?>
                                    <option value="<?php echo $a->ID; ?>" <?php selected($default_author, $a->ID); ?>><?php echo esc_html($a->post_title); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Show Author on Posts</th>
                        <td>
                            <label>
                                <input type="checkbox" name="gab_show_author_on_posts" value="1" <?php checked($show_on_posts, '1'); ?>>
                                Display author bio at the bottom of blog posts
                            </label>
                        </td>
                    </tr>
                </table>
                <?php submit_button('Save Settings'); ?>
            </form>
        </div>
        <?php
    }
}

GAB_Admin::instance();
