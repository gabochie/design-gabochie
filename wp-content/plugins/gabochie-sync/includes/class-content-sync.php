<?php
if (!defined('ABSPATH')) exit;

class Gabochie_Content_Sync {

    private function get_remote_auth_headers() {
        $api_key = get_option('gabochie_sync_api_key', '');
        $remote_user = get_option('gabochie_sync_remote_username', '');
        $headers = array();

        if (!empty($remote_user) && !empty($api_key)) {
            $headers['Authorization'] = 'Basic ' . base64_encode($remote_user . ':' . $api_key);
        } elseif (!empty($api_key)) {
            $headers['X-Gabochie-Key'] = $api_key;
        }

        return $headers;
    }

    public function push($dry_run = false, $post_types = null) {
        $remote_url = get_option('gabochie_sync_remote_url', '');
        $api_key = get_option('gabochie_sync_api_key', '');

        if (empty($remote_url) || empty($api_key)) {
            return array('success' => false, 'message' => 'Remote URL and Application Password are required.');
        }

        $export = $this->export($post_types);

        if ($dry_run) {
            return array(
                'success' => true,
                'dry_run' => true,
                'message' => sprintf('Would push %d items to %s', count($export['items']), $remote_url),
                'export' => $export,
            );
        }

        $headers = $this->get_remote_auth_headers();
        $headers['Content-Type'] = 'application/json';
        $response = wp_remote_post($remote_url . '/wp-json/gabochie-sync/v1/import', array(
            'headers' => $headers,
            'body' => wp_json_encode($export),
            'timeout' => 120,
        ));

        if (is_wp_error($response)) {
            $this->log('push', 'error', $response->get_error_message());
            return array('success' => false, 'message' => $response->get_error_message());
        }

        $code = wp_remote_retrieve_response_code($response);
        $body = json_decode(wp_remote_retrieve_body($response), true);

        if ($code === 200 && !empty($body['success'])) {
            $msg = sprintf('Pushed %d items to %s', count($export['items']), $remote_url);
            $this->log('push', 'success', $msg);
            update_option('gabochie_sync_last_sync', current_time('mysql'));
            return array('success' => true, 'message' => $msg, 'details' => $body);
        }

        $err = !empty($body['message']) ? $body['message'] : 'Unknown error (HTTP ' . $code . ')';
        $this->log('push', 'error', $err);
        return array('success' => false, 'message' => $err);
    }

    public function pull($dry_run = false) {
        $remote_url = get_option('gabochie_sync_remote_url', '');
        $api_key = get_option('gabochie_sync_api_key', '');

        if (empty($remote_url) || empty($api_key)) {
            return array('success' => false, 'message' => 'Remote URL and API key are required.');
        }

        $response = wp_remote_get($remote_url . '/wp-json/gabochie-sync/v1/export', array(
            'headers' => $this->get_remote_auth_headers(),
            'timeout' => 120,
        ));

        if (is_wp_error($response)) {
            $this->log('pull', 'error', $response->get_error_message());
            return array('success' => false, 'message' => $response->get_error_message());
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);

        if (empty($body['items'])) {
            return array('success' => true, 'message' => 'No items to pull.');
        }

        if ($dry_run) {
            return array(
                'success' => true,
                'dry_run' => true,
                'message' => sprintf('Would pull %d items from %s', count($body['items']), $remote_url),
                'export' => $body,
            );
        }

        $result = $this->import($body);

        if ($result['success']) {
            $msg = sprintf('Pulled %d items from %s', count($body['items']), $remote_url);
            $this->log('pull', 'success', $msg);
            update_option('gabochie_sync_last_sync', current_time('mysql'));
        }

        return $result;
    }

    public function export($post_types = null) {
        if (is_string($post_types)) {
            $post_types = array_map('trim', explode(',', $post_types));
        }
        if (empty($post_types)) {
            $post_types = get_option('gabochie_sync_sync_post_types', array('post', 'page'));
        }

        $items = array();

        foreach ($post_types as $pt) {
            $posts = get_posts(array(
                'post_type' => $pt,
                'posts_per_page' => -1,
                'post_status' => 'any',
                'orderby' => 'ID',
                'order' => 'ASC',
            ));

            foreach ($posts as $post) {
                $item = array(
                    'type' => 'post',
                    'post_type' => $post->post_type,
                    'ID' => $post->ID,
                    'post_name' => $post->post_name,
                    'post_title' => $post->post_title,
                    'post_content' => $post->post_content,
                    'post_excerpt' => $post->post_excerpt,
                    'post_status' => $post->post_status,
                    'post_date' => $post->post_date,
                    'post_date_gmt' => $post->post_date_gmt,
                    'menu_order' => $post->menu_order,
                    'comment_status' => $post->comment_status,
                    'ping_status' => $post->ping_status,
                    'meta' => array(),
                    'taxonomies' => array(),
                );

                // Export post meta (skip internal WP keys)
                $skip_meta = array(
                    '_edit_lock', '_edit_last', '_wp_page_template',
                    '_thumbnail_id', '_oembed_cached_url',
                );
                $metas = get_post_meta($post->ID);
                foreach ($metas as $key => $values) {
                    if (in_array($key, $skip_meta) || substr($key, 0, 1) === '_') {
                        // Allow specific underscore meta
                        $allow_meta = array('_wp_page_template', '_thumbnail_id');
                        if (!in_array($key, $allow_meta)) continue;
                    }
                    $item['meta'][$key] = maybe_unserialize($values[0]);
                }

                // Export taxonomies
                $taxonomies = get_object_taxonomies($post->post_type, 'objects');
                foreach ($taxonomies as $tax) {
                    $terms = wp_get_post_terms($post->ID, $tax->name, array('fields' => 'ids'));
                    if (!is_wp_error($terms) && !empty($terms)) {
                        $item['taxonomies'][$tax->name] = $terms;
                    }
                }

                $items[] = $item;
            }
        }

        return array(
            'items' => $items,
            'count' => count($items),
            'exported_at' => current_time('mysql'),
            'site_url' => home_url(),
        );
    }

    public function import($data) {
        if (empty($data['items'])) {
            return array('success' => true, 'message' => 'No items to import.');
        }

        $imported = 0;
        $skipped = 0;
        $errors = array();
        $term_map = array(); // remote_term_id => local_term_id

        foreach ($data['items'] as $item) {
            if ($item['type'] === 'post') {
                $result = $this->import_post($item, $term_map);
                if ($result['success']) {
                    $imported++;
                } elseif ($result['skipped']) {
                    $skipped++;
                } else {
                    $errors[] = $result['message'];
                }
            } elseif ($item['type'] === 'term') {
                $result = $this->import_term($item, $term_map);
                if ($result['success']) {
                    $imported++;
                }
            } elseif ($item['type'] === 'option') {
                $result = $this->import_option($item);
                if ($result['success']) {
                    $imported++;
                }
            }
        }

        $msg = sprintf('Imported: %d, Skipped: %d, Errors: %d', $imported, $skipped, count($errors));
        $this->log('import', empty($errors) ? 'success' : 'partial', $msg);

        return array(
            'success' => true,
            'imported' => $imported,
            'skipped' => $skipped,
            'errors' => $errors,
            'message' => $msg,
        );
    }

    private function import_post($item, &$term_map) {
        // Find existing post by slug
        $existing = get_page_by_path($item['post_name'], OBJECT, $item['post_type']);

        if ($existing) {
            // Update existing
            $update_data = array(
                'ID' => $existing->ID,
                'post_title' => $item['post_title'],
                'post_content' => $item['post_content'],
                'post_excerpt' => $item['post_excerpt'],
                'post_status' => $item['post_status'],
                'menu_order' => $item['menu_order'],
                'comment_status' => $item['comment_status'],
                'ping_status' => $item['ping_status'],
            );
            $result = wp_update_post($update_data, true);

            if (is_wp_error($result)) {
                return array('success' => false, 'message' => $result->get_error_message());
            }

            // Update meta
            if (!empty($item['meta'])) {
                foreach ($item['meta'] as $key => $value) {
                    update_post_meta($existing->ID, $key, $value);
                }
            }

            return array('success' => true, 'id' => $existing->ID);
        }

        // Insert new
        $insert_data = array(
            'post_type' => $item['post_type'],
            'post_name' => $item['post_name'],
            'post_title' => $item['post_title'],
            'post_content' => $item['post_content'],
            'post_excerpt' => $item['post_excerpt'],
            'post_status' => $item['post_status'],
            'post_date' => $item['post_date'],
            'post_date_gmt' => $item['post_date_gmt'],
            'menu_order' => $item['menu_order'],
            'comment_status' => $item['comment_status'],
            'ping_status' => $item['ping_status'],
        );

        $new_id = wp_insert_post($insert_data, true);

        if (is_wp_error($new_id)) {
            return array('success' => false, 'message' => $new_id->get_error_message());
        }

        // Set meta
        if (!empty($item['meta'])) {
            foreach ($item['meta'] as $key => $value) {
                update_post_meta($new_id, $key, $value);
            }
        }

        // Set taxonomies
        if (!empty($item['taxonomies'])) {
            foreach ($item['taxonomies'] as $tax => $remote_ids) {
                $local_ids = array();
                foreach ($remote_ids as $remote_id) {
                    if (isset($term_map[$remote_id])) {
                        $local_ids[] = $term_map[$remote_id];
                    }
                }
                if (!empty($local_ids)) {
                    wp_set_post_terms($new_id, $local_ids, $tax);
                }
            }
        }

        return array('success' => true, 'id' => $new_id);
    }

    private function import_term($item, &$term_map) {
        $existing = get_term_by('slug', $item['slug'], $item['taxonomy']);

        if ($existing) {
            $term_map[$item['remote_id']] = $existing->term_id;
            return array('success' => true, 'id' => $existing->term_id);
        }

        $result = wp_insert_term($item['name'], $item['taxonomy'], array(
            'slug' => $item['slug'],
            'description' => $item['description'],
            'parent' => 0,
        ));

        if (is_wp_error($result)) {
            return array('success' => false, 'message' => $result->get_error_message());
        }

        $term_map[$item['remote_id']] = $result['term_id'];
        return array('success' => true, 'id' => $result['term_id']);
    }

    private function import_option($item) {
        $blocked = array(
            'siteurl', 'home', 'admin_email', 'admin_password', 'admin_password_hash',
            'template', 'stylesheet', 'active_plugins', 'db_version', 'WP_VERSION',
            'gmt_offset', 'timezone_string', 'site_icon', 'blogdescription',
            'blogname', 'site_language',
        );
        $name = isset($item['option_name']) ? sanitize_key($item['option_name']) : '';
        if (empty($name) || in_array($name, $blocked)) {
            return array('success' => false, 'message' => 'Option "' . $name . '" is blocked from sync.');
        }
        update_option($name, $item['option_value']);
        return array('success' => true);
    }

    public function merge($dry_run = false, $direction = 'push', $post_types = null) {
        $remote_url = get_option('gabochie_sync_remote_url', '');
        $api_key = get_option('gabochie_sync_api_key', '');

        if (empty($remote_url) || empty($api_key)) {
            return array('success' => false, 'message' => 'Remote URL and API key are required.');
        }

        if ($direction === 'push') {
            // Push: local is source, remote is target
            $source_items = $this->export($post_types)['items'];
            $response = wp_remote_get($remote_url . '/wp-json/gabochie-sync/v1/export', array(
                'headers' => $this->get_remote_auth_headers(),
                'timeout' => 120,
            ));
            if (is_wp_error($response)) {
                return array('success' => false, 'message' => 'Failed to fetch remote: ' . $response->get_error_message());
            }
            $remote_data = json_decode(wp_remote_retrieve_body($response), true);
            $target_items = isset($remote_data['items']) ? $remote_data['items'] : array();
        } else {
            // Pull: remote is source, local is target
            $response = wp_remote_get($remote_url . '/wp-json/gabochie-sync/v1/export', array(
                'headers' => $this->get_remote_auth_headers(),
                'timeout' => 120,
            ));
            if (is_wp_error($response)) {
                return array('success' => false, 'message' => 'Failed to fetch remote: ' . $response->get_error_message());
            }
            $remote_data = json_decode(wp_remote_retrieve_body($response), true);
            $source_items = isset($remote_data['items']) ? $remote_data['items'] : array();
            $target_items = $this->export($post_types)['items'];
        }

        // Build index of existing target items by slug
        $target_index = array();
        foreach ($target_items as $item) {
            $key = $item['post_type'] . '|' . $item['post_name'];
            $target_index[$key] = $item;
        }

        // Categorize source items
        $to_import = array();
        $conflicts = array();
        $identical = array();

        foreach ($source_items as $item) {
            $key = $item['post_type'] . '|' . $item['post_name'];

            if (isset($target_index[$key])) {
                $target_item = $target_index[$key];
                if ($item['post_title'] === $target_item['post_title'] &&
                    $item['post_content'] === $target_item['post_content']) {
                    $identical[] = array(
                        'slug' => $item['post_name'],
                        'type' => $item['post_type'],
                        'title' => $item['post_title'],
                    );
                } else {
                    $conflicts[] = array(
                        'slug' => $item['post_name'],
                        'type' => $item['post_type'],
                        'source_title' => $item['post_title'],
                        'target_title' => $target_item['post_title'],
                    );
                }
            } else {
                $to_import[] = $item;
            }
        }

        $report = array(
            'direction' => $direction,
            'to_import' => count($to_import),
            'conflicts' => count($conflicts),
            'identical' => count($identical),
            'items_to_import' => $to_import,
            'conflict_details' => $conflicts,
            'identical_details' => $identical,
        );

        if ($dry_run) {
            $report['message'] = sprintf(
                'Dry run: %d items to import, %d conflicts (skipped), %d identical (skipped)',
                count($to_import), count($conflicts), count($identical)
            );
            $this->log('merge_dry_run', 'info', $report['message']);
            return array('success' => true, 'dry_run' => true, 'report' => $report);
        }

        // Send items to remote site for import
        $import_payload = array('items' => $to_import);
        $headers = $this->get_remote_auth_headers();
        $headers['Content-Type'] = 'application/json';

        $response = wp_remote_post($remote_url . '/wp-json/gabochie-sync/v1/import', array(
            'headers' => $headers,
            'body' => wp_json_encode($import_payload),
            'timeout' => 120,
        ));

        if (is_wp_error($response)) {
            return array('success' => false, 'message' => 'Failed to push to remote: ' . $response->get_error_message());
        }

        $code = wp_remote_retrieve_response_code($response);
        $body = json_decode(wp_remote_retrieve_body($response), true);

        $imported = isset($body['imported']) ? $body['imported'] : 0;
        $remote_errors = isset($body['errors']) ? $body['errors'] : array();
        $skipped = isset($body['skipped']) ? $body['skipped'] : 0;

        $report['imported'] = $imported;
        $report['errors'] = $remote_errors;
        $report['remote_message'] = isset($body['message']) ? $body['message'] : '';
        $report['message'] = sprintf(
            'Merged: %d imported, %d conflicts skipped, %d identical skipped, %d remote errors',
            $imported, count($conflicts), count($identical), count($remote_errors)
        );

        $this->log('merge', empty($errors) ? 'success' : 'partial', $report['message']);
        update_option('gabochie_sync_last_sync', current_time('mysql'));

        return array('success' => true, 'report' => $report);
    }

    private function import_post_safe($item) {
        // Check if post already exists by slug
        $existing = get_page_by_path($item['post_name'], OBJECT, $item['post_type']);

        if ($existing) {
            return array('success' => false, 'message' => 'Post already exists (slug conflict)');
        }

        // Insert new post
        $insert_data = array(
            'post_type' => $item['post_type'],
            'post_name' => $item['post_name'],
            'post_title' => $item['post_title'],
            'post_content' => $item['post_content'],
            'post_excerpt' => $item['post_excerpt'],
            'post_status' => $item['post_status'],
            'post_date' => $item['post_date'],
            'post_date_gmt' => $item['post_date_gmt'],
            'menu_order' => $item['menu_order'],
            'comment_status' => $item['comment_status'],
            'ping_status' => $item['ping_status'],
        );

        $new_id = wp_insert_post($insert_data, true);

        if (is_wp_error($new_id)) {
            return array('success' => false, 'message' => $new_id->get_error_message());
        }

        // Set meta
        if (!empty($item['meta'])) {
            foreach ($item['meta'] as $key => $value) {
                update_post_meta($new_id, $key, $value);
            }
        }

        // Set taxonomies
        if (!empty($item['taxonomies'])) {
            foreach ($item['taxonomies'] as $tax => $remote_ids) {
                $local_ids = array();
                foreach ($remote_ids as $remote_id) {
                    $existing_term = get_term($remote_id, $tax);
                    if ($existing_term && !is_wp_error($existing_term)) {
                        $local_ids[] = $existing_term->term_id;
                    }
                }
                if (!empty($local_ids)) {
                    wp_set_post_terms($new_id, $local_ids, $tax);
                }
            }
        }

        return array('success' => true, 'id' => $new_id, 'title' => $item['post_title']);
    }

    public function get_status() {
        $remote_url = get_option('gabochie_sync_remote_url', '');
        $connected = false;
        $remote_version = '';

        if (!empty($remote_url)) {
            $response = wp_remote_get($remote_url . '/wp-json/gabochie-sync/v1/status', array(
                'headers' => $this->get_remote_auth_headers(),
                'timeout' => 10,
            ));

            if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
                $connected = true;
                $body = json_decode(wp_remote_retrieve_body($response), true);
                $remote_version = isset($body['version']) ? $body['version'] : '';
            }
        }

        return array(
            'connected' => $connected,
            'remote_url' => $remote_url,
            'remote_version' => $remote_version,
            'local_version' => GABOCHIE_SYNC_VERSION,
            'last_sync' => get_option('gabochie_sync_last_sync', ''),
            'sync_post_types' => get_option('gabochie_sync_sync_post_types', array('post', 'page')),
        );
    }

    public function get_diff() {
        $remote_url = get_option('gabochie_sync_remote_url', '');
        $api_key = get_option('gabochie_sync_api_key', '');

        if (empty($remote_url) || empty($api_key)) {
            return array('success' => false, 'message' => 'Remote not configured.');
        }

        // Get remote content
        $response = wp_remote_get($remote_url . '/wp-json/gabochie-sync/v1/export', array(
            'headers' => $this->get_remote_auth_headers(),
            'timeout' => 60,
        ));

        if (is_wp_error($response)) {
            return array('success' => false, 'message' => $response->get_error_message());
        }

        $remote_data = json_decode(wp_remote_retrieve_body($response), true);
        $local_data = $this->export();

        // Compare
        $changes = array(
            'local_only' => array(),
            'remote_only' => array(),
            'modified' => array(),
        );

        $remote_slugs = array();
        foreach ($remote_data['items'] as $item) {
            $remote_slugs[$item['post_type'] . '|' . $item['post_name']] = $item;
        }

        $local_slugs = array();
        foreach ($local_data['items'] as $item) {
            $local_slugs[$item['post_type'] . '|' . $item['post_name']] = $item;
        }

        // Find local-only and modified
        foreach ($local_slugs as $key => $local_item) {
            if (isset($remote_slugs[$key])) {
                $remote_item = $remote_slugs[$key];
                if ($local_item['post_content'] !== $remote_item['post_content'] ||
                    $local_item['post_title'] !== $remote_item['post_title']) {
                    $changes['modified'][] = array(
                        'key' => $key,
                        'title' => $local_item['post_title'],
                        'type' => $local_item['post_type'],
                    );
                }
            } else {
                $changes['local_only'][] = array(
                    'key' => $key,
                    'title' => $local_item['post_title'],
                    'type' => $local_item['post_type'],
                );
            }
        }

        // Find remote-only
        foreach ($remote_slugs as $key => $remote_item) {
            if (!isset($local_slugs[$key])) {
                $changes['remote_only'][] = array(
                    'key' => $key,
                    'title' => $remote_item['post_title'],
                    'type' => $remote_item['post_type'],
                );
            }
        }

        return array(
            'success' => true,
            'changes' => $changes,
            'summary' => array(
                'local_only' => count($changes['local_only']),
                'remote_only' => count($changes['remote_only']),
                'modified' => count($changes['modified']),
            ),
        );
    }

    private function log($action, $status, $message) {
        $log = get_option('gabochie_sync_log', array());
        $log[] = array(
            'time' => current_time('mysql'),
            'action' => $action,
            'status' => $status,
            'message' => $message,
        );
        // Keep last 200 entries
        if (count($log) > 200) {
            $log = array_slice($log, -200);
        }
        update_option('gabochie_sync_log', $log);
    }
}
