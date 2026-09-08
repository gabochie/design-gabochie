<?php
/**
 * Gabochie Marketplace — admin UI.
 *
 * Top-level "Marketplace" menu with a Listings tab (WP_List_Table) + a
 * Settings tab (AI model, sample importer, intelligence refresh). Handles the
 * GET actions (publish/intelligence/feature/delete/seed) with per-item nonces.
 *
 * @package Gabochie_Marketplace
 */

defined('ABSPATH') || exit;

/**
 * Marketplace admin.
 */
class Gabochie_Marketplace_Admin
{

    const PAGE = 'gabochie-marketplace';

    /**
     * @var Gabochie_Marketplace_Admin|null 
     */
    private static $instance = null;

    /**
     * @return Gabochie_Marketplace_Admin 
     */
    public static function instance()
    {
        if (null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct()
    {
        add_action('admin_menu', array( $this, 'register_menu' ));
        add_action('admin_init', array( $this, 'handle_get_action' ));
        add_action('admin_post_gmp_save_settings', array( $this, 'handle_settings_save' ));
    }

    /**
     * @return void 
     */
    public function handle_settings_save()
    {
        check_admin_referer('gmp_settings_form');
        if (current_user_can('manage_options') ) {
            $model = isset($_POST['gmp_ai_model']) ? sanitize_key((string) $_POST['gmp_ai_model']) : 'gemini-3.5-flash';
            update_option(Gabochie_Marketplace::OPT_AI_MODEL, $model);
        }
        wp_safe_redirect(add_query_arg(array( 'page' => self::PAGE, 'tab' => 'settings' ), admin_url('admin.php')));
        exit;
    }

    /**
     * @return void 
     */
    public function register_menu()
    {
        add_menu_page(
            __('Marketplace', 'gabochie-marketplace'),
            __('Marketplace', 'gabochie-marketplace'),
            'edit_posts',
            self::PAGE,
            array( $this, 'render' ),
            'dashicons-building',
            4
        );
    }

    /**
     * @return void 
     */
    public function render()
    {
        $tab = isset($_GET['tab']) ? sanitize_key((string) $_GET['tab']) : 'listings';
        echo '<div class="wrap">';
        echo '<h1>' . esc_html__('Gabochie Marketplace', 'gabochie-marketplace') . '</h1>';
        $this->render_tabs($tab);
        if ('settings' === $tab ) {
            $this->render_settings();
        } else {
            $this->render_listings();
        }
        echo '</div>';
    }

    /**
     * @param string $current @return void 
     */
    private function render_tabs( $current )
    {
        $base = admin_url('admin.php?page=' . self::PAGE);
        echo '<h2 class="nav-tab-wrapper">';
        printf(
            '<a class="nav-tab %s" href="%s">%s</a>',
            'listings' === $current ? 'nav-tab-active' : '',
            esc_url($base . '&tab=listings'),
            esc_html__('Listings', 'gabochie-marketplace')
        );
        printf(
            '<a class="nav-tab %s" href="%s">%s</a>',
            'settings' === $current ? 'nav-tab-active' : '',
            esc_url($base . '&tab=settings'),
            esc_html__('Settings', 'gabochie-marketplace')
        );
        echo '</h2>';
    }

    /**
     * @return void 
     */
    private function render_listings()
    {
        $table = new Gabochie_Marketplace_List_Table();
        $table->prepare_items();
        echo '<form method="get">';
        echo '<input type="hidden" name="page" value="' . esc_attr(self::PAGE) . '" />';
        echo '<input type="hidden" name="tab" value="listings" />';
        $table->display();
        echo '</form>';
    }

    /**
     * @return void 
     */
    private function render_settings()
    {
        $model = (string) get_option(Gabochie_Marketplace::OPT_AI_MODEL, 'gemini-3.5-flash');
        ?>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
        <?php wp_nonce_field('gmp_settings_form'); ?>
            <input type="hidden" name="action" value="gmp_save_settings" />
            <h2><?php esc_html_e('Intelligence Engine', 'gabochie-marketplace'); ?></h2>
            <table class="form-table" role="presentation">
                <tr>
                    <th><label for="gmp_ai_model"><?php esc_html_e('Gemini model (builtin)', 'gabochie-marketplace'); ?></label></th>
                    <td>
                        <select name="gmp_ai_model" id="gmp_ai_model">
                            <option value="gemini-3.5-flash" <?php selected($model, 'gemini-3.5-flash'); ?>>gemini-3.5-flash</option>
                            <option value="gemini-flash-latest" <?php selected($model, 'gemini-flash-latest'); ?>>gemini-flash-latest</option>
                            <option value="gemini-2.5-pro" <?php selected($model, 'gemini-2.5-pro'); ?>>gemini-2.5-pro</option>
                        </select>
                        <p class="description">Used by the builtin fallback caller. Auto-blog + GhBC providers are tried first.</p>
                    </td>
                </tr>
            </table>
        <?php submit_button(__('Save Settings', 'gabochie-marketplace')); ?>
        </form>

        <h2><?php esc_html_e('Recent errors', 'gabochie-marketplace'); ?></h2>
        <table class="widefat striped">
            <thead><tr><th><?php esc_html_e('Time', 'gabochie-marketplace'); ?></th><th><?php esc_html_e('Error', 'gabochie-marketplace'); ?></th></tr></thead>
            <tbody>
        <?php
        $log = get_option(Gabochie_Marketplace::OPT_ERRORS, array());
        if (empty($log) ) {
            echo '<tr><td colspan="2">' . esc_html__('No errors recorded.', 'gabochie-marketplace') . '</td></tr>';
        }
        foreach ( array_reverse($log) as $row ) {
            printf('<tr><td>%s</td><td>%s</td></tr>', esc_html($row['time']), esc_html($row['error']));
        }
        ?>
            </tbody>
        </table>
        <?php
    }

    /**
     * Handle GET row actions + bulk actions.
     *
     * @return void
     */
    public function handle_get_action()
    {
        if (empty($_GET['page']) || self::PAGE !== $_GET['page'] ) {
            return;
        }

        $action = isset($_GET['action']) ? sanitize_key((string) $_GET['action']) : '';

        if ('gmp_seed' === $action ) {
            check_admin_referer('gmp_seed');
            if (current_user_can('manage_options') ) {
                $count = Gabochie_Marketplace::instance()->seed_sample_listings();
                wp_safe_redirect(add_query_arg(array( 'page' => self::PAGE, 'tab' => 'listings', 'seeded' => $count ), admin_url('admin.php')));
                exit;
            }
        }

        if (isset($_GET['item']) && in_array($action, array( 'gmp_publish', 'gmp_intelligence', 'gmp_feature', 'gmp_delete' ), true) ) {
            $id = (int) $_GET['item'];
            check_admin_referer('gmp_item_' . $id);

            if ('gmp_publish' === $action ) {
                $post = get_post($id);
                wp_update_post(
                    array(
                    'ID'          => $id,
                    'post_status' => 'publish' === $post->post_status ? 'draft' : 'publish',
                    )
                );
            } elseif ('gmp_intelligence' === $action ) {
                Gabochie_Marketplace_AI::instance()->generate_report($id);
            } elseif ('gmp_feature' === $action ) {
                $feat = '1' === (string) get_post_meta($id, Gabochie_Marketplace::META_FEATURED, true) ? '' : '1';
                update_post_meta($id, Gabochie_Marketplace::META_FEATURED, $feat);
            } elseif ('gmp_delete' === $action ) {
                wp_delete_post($id, true);
            }

            wp_safe_redirect(add_query_arg(array( 'page' => self::PAGE, 'tab' => 'listings' ), admin_url('admin.php')));
            exit;
        }

        // Bulk actions arrive as POST (form submit).
        if (! empty($_POST['action']) ) {
            $this->handle_bulk();
        }
    }

    /**
     * @return void 
     */
    private function handle_bulk()
    {
        $action = sanitize_key((string) $_POST['action']);
        if (! in_array($action, array( 'gmp_bulk_publish', 'gmp_bulk_draft', 'gmp_bulk_intelligence', 'gmp_bulk_delete' ), true) ) {
            return;
        }
        check_admin_referer('bulk-properties');
        $ids = isset($_POST['property_ids']) ? array_map('intval', (array) $_POST['property_ids']) : array();
        foreach ( $ids as $id ) {
            if ('gmp_bulk_publish' === $action ) {
                wp_update_post(array( 'ID' => $id, 'post_status' => 'publish' ));
            } elseif ('gmp_bulk_draft' === $action ) {
                wp_update_post(array( 'ID' => $id, 'post_status' => 'draft' ));
            } elseif ('gmp_bulk_intelligence' === $action ) {
                Gabochie_Marketplace_AI::instance()->generate_report($id);
            } elseif ('gmp_bulk_delete' === $action ) {
                wp_delete_post($id, true);
            }
        }
        wp_safe_redirect(add_query_arg(array( 'page' => self::PAGE, 'tab' => 'listings' ), admin_url('admin.php')));
        exit;
    }
}
