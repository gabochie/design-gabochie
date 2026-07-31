<?php

namespace KingJamesSEO\Core;

defined('ABSPATH') || exit;

class Rollback {
    public static function create_checkpoint(string $module, int $post_id, string $label = ''): int {
        $post = get_post($post_id);
        if (!$post) {
            return 0;
        }

        $checkpoint_id = wp_insert_post([
            'post_type'   => 'revision',
            'post_parent' => $post_id,
            'post_status' => 'inherit',
            'post_name'   => $post->post_name . '-kjs-checkpoint',
            'post_content' => $post->post_content,
            'post_title'  => $post->post_title,
            'post_excerpt' => $post->post_excerpt,
        ]);

        if ($checkpoint_id) {
            Plugin::instance()->logger()->log(
                $module,
                'checkpoint_created',
                "Checkpoint created for post {$post_id}: {$label}",
                '',
                ['post_id' => $post_id, 'checkpoint_id' => $checkpoint_id, 'label' => $label]
            );
        }

        return (int) $checkpoint_id;
    }

    public static function restore(int $checkpoint_id): bool {
        $checkpoint = get_post($checkpoint_id);
        if (!$checkpoint || 'revision' !== $checkpoint->post_type) {
            return false;
        }

        $post_id = $checkpoint->post_parent;
        $result = wp_update_post([
            'ID'           => $post_id,
            'post_content' => $checkpoint->post_content,
            'post_title'   => $checkpoint->post_title,
            'post_excerpt' => $checkpoint->post_excerpt,
        ], true);

        if (!is_wp_error($result)) {
            Plugin::instance()->logger()->log(
                'rollback',
                'restored',
                "Post {$post_id} restored to checkpoint {$checkpoint_id}",
                '',
                ['post_id' => $post_id, 'checkpoint_id' => $checkpoint_id]
            );
            return true;
        }

        return false;
    }
}
