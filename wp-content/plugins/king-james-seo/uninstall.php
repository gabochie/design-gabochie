<?php

defined('WP_UNINSTALL_PLUGIN') || exit;

global $wpdb;

$tables = [
    'kjs_audit_log',
    'kjs_pipeline_runs',
    'kjs_shared_memory',
    'kjs_content_scores',
    'kjs_schema_data',
    'kjs_seo_titles',
    'kjs_meta_descriptions',
    'kjs_ymyl_audits',
    'kjs_content_calendar',
    'kjs_api_keys',
];

foreach ($tables as $table) {
    $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}{$table}");
}

$options = $wpdb->get_col("SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE 'kjs_%'");
foreach ($options as $option) {
    delete_option($option);
}

$meta_keys = [
    '_kjs_seo_title',
    '_kjs_h1_title',
    '_kjs_meta_description',
    '_kjs_meta_cta',
    '_kjs_schema_type',
    '_kjs_ymyl_topic',
    '_kjs_ymyl_requires_review',
];

foreach ($meta_keys as $key) {
    delete_post_meta_by_key($key);
}
