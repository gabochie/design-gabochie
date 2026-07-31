<?php
defined('ABSPATH') || exit;

class GHBC_Billing {
    private static $instance = null;

    public static function instance() {
        if (null === self::$instance) self::$instance = new self();
        return self::$instance;
    }

    private function __construct() {
        add_filter('woocommerce_product_data_tabs', [$this, 'add_subscription_tab']);
        add_action('init', [$this, 'register_products']);
        add_filter('ghbc_user_can_query', [$this, 'check_query_limit'], 10, 2);
    }

    public function register_products() {
        if (!function_exists('wc_create_product')) return;

        $tiers = [
            'free' => ['name' => 'GhBC Free', 'queries' => 20, 'price' => 0],
            'pro'  => ['name' => 'GhBC Pro',  'queries' => 500, 'price' => 500],
            'firm' => ['name' => 'GhBC Firm',  'queries' => 0,   'price' => 2000],
        ];

        $existing = get_option('ghbc_product_ids', []);
        $needs_save = false;

        foreach ($tiers as $key => $tier) {
            if (!empty($existing[$key])) continue;

            $product = new WC_Product_Simple();
            $product->set_name($tier['name']);
            $product->set_status('publish');
            $product->set_catalog_visibility('visible');
            $product->set_price($tier['price'] > 0 ? $tier['price'] : 0);
            $product->set_regular_price($tier['price'] > 0 ? $tier['price'] : 0);
            $product->set_virtual(true);
            $id = $product->save();

            if ($id) {
                $existing[$key] = $id;
                $needs_save = true;
            }
        }

        if ($needs_save) {
            update_option('ghbc_product_ids', $existing);
        }
    }

    public function check_query_limit($can, $user_id) {
        if (!$user_id) return false;

        $tier = $this->get_user_tier($user_id);
        $max  = $this->get_tier_limit($tier);

        if ($max === 0) return true;

        global $wpdb;
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}ghbc_queries
             WHERE user_id = %d AND created_at >= DATE_SUB(NOW(), INTERVAL 1 MONTH)",
            $user_id
        ));

        return (int)$count < $max;
    }

    public function get_user_tier($user_id) {
        if (!function_exists('wc_user_has_active_subscription')) return 'free';
        if (wc_user_has_active_subscription($user_id, 'ghbc_firm')) return 'firm';
        if (wc_user_has_active_subscription($user_id, 'ghbc_pro')) return 'pro';
        return 'free';
    }

    public function get_tier_limit($tier) {
        $limits = ['free' => 20, 'pro' => 500, 'firm' => 0];
        return $limits[$tier] ?? 20;
    }

    public function get_remaining_queries($user_id) {
        $tier = $this->get_user_tier($user_id);
        $max  = $this->get_tier_limit($tier);
        if ($max === 0) return -1;

        global $wpdb;
        $count = (int)$wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}ghbc_queries
             WHERE user_id = %d AND created_at >= DATE_SUB(NOW(), INTERVAL 1 MONTH)",
            $user_id
        ));
        return max(0, $max - $count);
    }

    public function add_subscription_tab($tabs) {
        $tabs['ghbc'] = [
            'label'  => 'GhBC',
            'target' => 'ghbc_product_data',
            'class'  => ['show_if_simple'],
        ];
        return $tabs;
    }
}
