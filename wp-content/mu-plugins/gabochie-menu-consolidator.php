<?php
/**
 * Plugin Name: Gabochie Menu Consolidator
 * Description: Moves all Gabochie plugin menus under the Gabochie Design command center.
 * Version: 1.0.4
 * Author: GABOCHIE DESIGN
 */

defined('ABSPATH') || exit;

/**
 * Phase 1: Remove gabochie-sync's submenus so we can re-register in clean order.
 */
add_action('admin_menu', function () {
    remove_submenu_page('gabochie-hub', 'gabochie-hub');
    remove_submenu_page('gabochie-hub', 'gabochie-strategy');
    remove_submenu_page('gabochie-hub', 'gabochie-roadmap');
    remove_submenu_page('gabochie-hub', 'gabochie-revenue');
    remove_submenu_page('gabochie-hub', 'gabochie-sync');
}, 11);

/**
 * Phase 2: Register all submenus in clean order + remove other top-level menus.
 */
add_action('admin_menu', function () {
    // --- Core (from gabochie-sync) ---
    // NOTE: Empty callback to avoid double-rendering — gabochie-sync.php
    // already registered the render_action at admin_menu priority 10.
    // WordPress add_submenu_page() does NOT deduplicate callbacks, so
    // re-registering here with a non-empty callback would fire it twice.
    add_submenu_page('gabochie-hub', 'Dashboard', 'Dashboard', 'manage_options', 'gabochie-hub', '');
    add_submenu_page('gabochie-hub', 'Agentic Strategy', 'Strategy', 'manage_options', 'gabochie-strategy', '');
    add_submenu_page('gabochie-hub', 'Build Roadmap', 'Build Roadmap', 'manage_options', 'gabochie-roadmap', '');
    add_submenu_page('gabochie-hub', 'Mega Revenue Strategy', 'Revenue Strategy', 'manage_options', 'gabochie-revenue', '');

    // --- Strategy & Planning (from other plugins) ---
    add_submenu_page('gabochie-hub', 'Topic Clusters', 'Topic Clusters', 'edit_posts', 'gtc-clusters', function () {
        if (class_exists('GTC_Admin')) {
            GTC_Admin::instance()->render_dashboard();
        }
    });
    add_submenu_page('gabochie-hub', 'Rank Gap Analysis', 'Rank Gaps', 'manage_options', 'grg-dashboard', function () {
        if (class_exists('GRG_Admin')) {
            GRG_Admin::instance()->render_dashboard();
        }
    });

    // --- Content & SEO (from other plugins) ---
    add_submenu_page('gabochie-hub', 'Content Pipeline', 'Content Pipeline', 'edit_posts', 'gcp-queue', function () {
        if (class_exists('GCP_Admin')) {
            GCP_Admin::instance()->render_queue();
        }
    });
    add_submenu_page('gabochie-hub', 'Content Refresh', 'Content Refresh', 'manage_options', 'gcr-dashboard', function () {
        if (class_exists('GCR_Admin')) {
            GCR_Admin::instance()->render_dashboard();
        }
    });
    add_submenu_page('gabochie-hub', 'Auto-Blog', 'Auto-Blog', 'manage_options', 'gabochie-auto-blog', function () {
        if (class_exists('Gabochie_Auto_Admin')) {
            Gabochie_Auto_Admin::instance()->render_dashboard();
        }
    });
    add_submenu_page('gabochie-hub', 'Authority Builder', 'Authority', 'manage_options', 'gab-dashboard', function () {
        if (class_exists('GAB_Admin')) {
            GAB_Admin::instance()->render_dashboard();
        }
    });
    add_submenu_page('gabochie-hub', 'Ad RPM Optimizer', 'Ad RPM', 'manage_options', 'gar-dashboard', function () {
        if (class_exists('GAR_Admin')) {
            GAR_Admin::instance()->render_dashboard();
        }
    });

    // --- Gabochie SEO (may be disabled, but still registers menus) ---
    remove_menu_page('gabochie-seo');
    if (class_exists('Gabochie_SEO_Admin')) {
        add_submenu_page('gabochie-hub', 'Gabochie SEO', 'Gabochie SEO', 'manage_options', 'gabochie-seo', function () {
            Gabochie_SEO_Admin::instance()->render_dashboard();
        });
        add_submenu_page('gabochie-hub', 'Gabochie SEO Settings', 'SEO Settings', 'manage_options', 'gabochie-seo-settings', function () {
            Gabochie_SEO_Admin::instance()->render_settings();
        });
    }

    // --- SaaS Tools CPT (suppress top-level menu) ---
    remove_menu_page('edit.php?post_type=saas_tool');
    add_submenu_page('gabochie-hub', 'SaaS Tools', 'SaaS Tools', 'manage_options', 'edit.php?post_type=saas_tool');

    // --- Settings ---
    add_submenu_page('gabochie-hub', 'Sync Settings', 'Sync', 'manage_options', 'gabochie-sync-settings', function () {
        if (class_exists('Gabochie_Sync_Admin')) {
            Gabochie_Sync_Admin::render_page();
        }
    });

    // --- AI Site Recovery (native) ---
    add_submenu_page('gabochie-hub', 'AI Site Recovery', 'AI Recovery', 'manage_options', 'gabochie-recovery', function () {
        if (class_exists('Gabochie_Recovery')) {
            Gabochie_Recovery::render_page();
        }
    });

    // --- Remove all top-level menus from other plugins ---
    $top_level = [
        'gab-dashboard',       // Authority Builder
        'gar-dashboard',       // Ad RPM
        'gcr-dashboard',       // Content Refresh
        'grg-dashboard',       // Rank Gap Tracker
        'gtc-clusters',        // Topic Clusters
        'gcp-queue',           // Content Pipeline
        'gabochie-auto-blog',  // Auto-Blog
        'wp-ai-emergency-recovery', // Old AI Recovery (now native)
    ];
    foreach ($top_level as $slug) {
        remove_menu_page($slug);
    }
}, 12);
