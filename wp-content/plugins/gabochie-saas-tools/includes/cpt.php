<?php
defined('ABSPATH') || exit;

function gs_register_role() {
    add_role('saas_member', 'SaaS Member', ['read' => true, 'level_0' => true]);
}

function gs_register_tool_cpt() {
    $labels = [
        'name' => 'Tools',
        'singular_name' => 'Tool',
        'add_new' => 'Add New Tool',
        'add_new_item' => 'Add New Tool',
        'edit_item' => 'Edit Tool',
        'view_item' => 'View Tool',
        'search_items' => 'Search Tools',
        'not_found' => 'No tools found',
        'not_found_in_trash' => 'No tools found in Trash',
        'all_items' => 'All Tools',
        'menu_name' => 'SaaS Tools',
        'name_admin_bar' => 'Tool'
    ];
    register_post_type('saas_tool', [
        'labels' => $labels,
        'public' => true,
        'has_archive' => true,
        'rewrite' => ['slug' => 'tools'],
        'menu_icon' => 'dashicons-hammer',
        'supports' => ['title', 'editor', 'thumbnail', 'excerpt', 'page-attributes'],
        'show_in_rest' => true,
        'publicly_queryable' => true,
    ]);

    register_taxonomy('tool_category', 'saas_tool', [
        'labels' => ['name' => 'Categories', 'singular_name' => 'Category', 'menu_name' => 'Categories'],
        'hierarchical' => true,
        'show_in_rest' => true,
        'rewrite' => ['slug' => 'tool-category'],
    ]);

    register_taxonomy('tool_status', 'saas_tool', [
        'labels' => ['name' => 'Statuses', 'singular_name' => 'Status', 'menu_name' => 'Statuses'],
        'hierarchical' => false,
        'show_in_rest' => true,
    ]);

    wp_insert_term('Live', 'tool_status', ['slug' => 'live']);
    wp_insert_term('Beta', 'tool_status', ['slug' => 'beta']);
    wp_insert_term('New', 'tool_status', ['slug' => 'new']);
    wp_insert_term('Coming Soon', 'tool_status', ['slug' => 'coming-soon']);
}
add_action('init', 'gs_register_tool_cpt');

function gs_tool_meta_boxes() {
    add_meta_box('gs_tool_details', 'Tool Details', 'gs_tool_meta_cb', 'saas_tool', 'normal', 'high');
}
add_action('add_meta_boxes', 'gs_tool_meta_boxes');

function gs_tool_meta_cb($post) {
    $icon = get_post_meta($post->ID, '_tool_icon', true) ?: '🔧';
    $price = get_post_meta($post->ID, '_tool_price', true) ?: '$29/mo';
    $color = get_post_meta($post->ID, '_tool_accent', true) ?: '#0F5A45';
    $bg = get_post_meta($post->ID, '_tool_bg', true) ?: '#E8F2EB';
    $tag = get_post_meta($post->ID, '_tool_tag', true) ?: 'General';
    ?>
    <table style="width:100%;border-collapse:collapse">
    <tr><td style="padding:8px 0"><label>Icon (emoji):</label></td><td><input type="text" name="tool_icon" value="<?php echo esc_attr($icon); ?>" style="width:80px"></td></tr>
    <tr><td style="padding:8px 0"><label>Price display:</label></td><td><input type="text" name="tool_price" value="<?php echo esc_attr($price); ?>" style="width:200px"></td></tr>
    <tr><td style="padding:8px 0"><label>Accent color:</label></td><td><input type="color" name="tool_accent" value="<?php echo esc_attr($color); ?>"></td></tr>
    <tr><td style="padding:8px 0"><label>Card bg color:</label></td><td><input type="color" name="tool_bg" value="<?php echo esc_attr($bg); ?>"></td></tr>
    <tr><td style="padding:8px 0"><label>Category tag:</label></td><td><input type="text" name="tool_tag" value="<?php echo esc_attr($tag); ?>" style="width:200px"></td></tr>
    </table>
    <?php
}

function gs_tool_meta_save($post_id) {
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!current_user_can('edit_post', $post_id)) return;
    foreach (['tool_icon', 'tool_price', 'tool_accent', 'tool_bg', 'tool_tag'] as $f) {
        if (isset($_POST[$f])) { update_post_meta($post_id, '_' . $f, sanitize_text_field($_POST[$f])); }
    }
}
add_action('save_post', 'gs_tool_meta_save');

function gs_register_document_cpt() {
    register_post_type('saas_document', array(
        'labels' => array(
            'name'          => 'SaaS Documents',
            'singular_name' => 'SaaS Document',
            'add_new_item'  => 'Add New Document',
            'edit_item'     => 'Edit Document',
            'not_found'     => 'No documents found',
            'not_found_in_trash' => 'No documents found in Trash',
            'all_items'     => 'All Documents',
            'menu_name'     => 'SaaS Documents',
        ),
        'public'            => false,
        'show_ui'           => true,
        'show_in_menu'      => false,
        'supports'          => array('title', 'editor', 'author', 'custom-fields'),
        'capability_type'   => 'post',
        'map_meta_cap'      => true,
    ));
}
add_action('init', 'gs_register_document_cpt');
