<?php
defined('ABSPATH') || exit;

class GAB_Author {
    private static $instance = null;

    private $specialties = array(
        'Residential Architecture',
        'Sustainable Design',
        'Diaspora Building',
        'AI-Powered Design',
        'Interior Design',
        'Project Management',
        '3D Visualization',
        'Cost Estimation',
    );

    public static function instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('init', array($this, 'register_post_type'));
        add_action('init', array($this, 'register_taxonomy'));
        add_action('add_meta_boxes', array($this, 'add_meta_box'));
        add_action('save_post', array($this, 'save_meta'));
        add_shortcode('gab_author_bio', array($this, 'shortcode'));
    }

    public function register_post_type() {
        register_post_type('gab_author', array(
            'labels' => array(
                'name' => 'Authors',
                'singular_name' => 'Author',
                'add_new_item' => 'Add New Author',
                'edit_item' => 'Edit Author',
            ),
            'public' => true,
            'has_archive' => false,
            'supports' => array('title', 'editor', 'thumbnail', 'excerpt'),
            'menu_icon' => 'dashicons-groups',
            'show_in_menu' => false,
        ));
    }

    public function register_taxonomy() {
        register_taxonomy('gab_credential', 'gab_author', array(
            'labels' => array(
                'name' => 'Credentials',
                'singular_name' => 'Credential',
                'add_new_item' => 'Add New Credential',
            ),
            'public' => true,
            'show_in_menu' => false,
            'show_admin_column' => true,
            'hierarchical' => false,
        ));
    }

    public function add_meta_box() {
        add_meta_box(
            'gab_author_details',
            'Author Details',
            array($this, 'render_meta_box'),
            'gab_author',
            'normal',
            'high'
        );
    }

    public function render_meta_box($post) {
        wp_nonce_field('gab_author_save', 'gab_author_nonce');

        $full_name = get_post_meta($post->ID, '_gab_full_name', true);
        $linkedin = get_post_meta($post->ID, '_gab_linkedin', true);
        $experience = get_post_meta($post->ID, '_gab_experience', true);
        $selected_specialties = get_post_meta($post->ID, '_gab_specialties', true) ?: array();
        $selected_credentials = wp_get_object_terms($post->ID, 'gab_credential', array('fields' => 'slugs'));
        $all_credentials = get_terms(array('taxonomy' => 'gab_credential', 'hide_empty' => false));
        ?>
        <table class="form-table">
            <tr>
                <th><label for="gab_full_name">Full Name</label></th>
                <td><input type="text" id="gab_full_name" name="gab_full_name" value="<?php echo esc_attr($full_name); ?>" class="regular-text"></td>
            </tr>
            <tr>
                <th><label for="gab_linkedin">LinkedIn URL</label></th>
                <td><input type="url" id="gab_linkedin" name="gab_linkedin" value="<?php echo esc_attr($linkedin); ?>" class="regular-text"></td>
            </tr>
            <tr>
                <th><label for="gab_experience">Experience (years)</label></th>
                <td><input type="number" id="gab_experience" name="gab_experience" value="<?php echo esc_attr($experience); ?>" min="0" max="100"></td>
            </tr>
            <tr>
                <th>Credentials</th>
                <td>
                    <select name="gab_credentials[]" multiple style="min-width:250px;height:100px;">
                        <?php foreach ($all_credentials as $cred): ?>
                            <option value="<?php echo esc_attr($cred->slug); ?>" <?php echo in_array($cred->slug, $selected_credentials) ? 'selected' : ''; ?>><?php echo esc_html($cred->name); ?></option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            <tr>
                <th>Specialties</th>
                <td>
                    <?php foreach ($this->specialties as $spec): ?>
                        <label style="display:block;margin-bottom:2px;">
                            <input type="checkbox" name="gab_specialties[]" value="<?php echo esc_attr($spec); ?>" <?php echo in_array($spec, $selected_specialties) ? 'checked' : ''; ?>>
                            <?php echo esc_html($spec); ?>
                        </label>
                    <?php endforeach; ?>
                </td>
            </tr>
        </table>
        <?php
    }

    public function save_meta($post_id) {
        if (!isset($_POST['gab_author_nonce']) || !wp_verify_nonce($_POST['gab_author_nonce'], 'gab_author_save')) return;
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if (!current_user_can('edit_post', $post_id)) return;

        if (isset($_POST['gab_full_name'])) {
            update_post_meta($post_id, '_gab_full_name', sanitize_text_field($_POST['gab_full_name']));
        }
        if (isset($_POST['gab_linkedin'])) {
            update_post_meta($post_id, '_gab_linkedin', esc_url_raw($_POST['gab_linkedin']));
        }
        if (isset($_POST['gab_experience'])) {
            update_post_meta($post_id, '_gab_experience', intval($_POST['gab_experience']));
        }

        $specialties = isset($_POST['gab_specialties']) ? array_map('sanitize_text_field', $_POST['gab_specialties']) : array();
        update_post_meta($post_id, '_gab_specialties', $specialties);

        if (isset($_POST['gab_credentials']) && is_array($_POST['gab_credentials'])) {
            $slugs = array_map('sanitize_title', $_POST['gab_credentials']);
            $term_ids = array();
            foreach ($slugs as $slug) {
                $term = get_term_by('slug', $slug, 'gab_credential');
                if ($term) {
                    $term_ids[] = intval($term->term_id);
                }
            }
            wp_set_object_terms($post_id, $term_ids, 'gab_credential');
        } else {
            wp_set_object_terms($post_id, array(), 'gab_credential');
        }
    }

    public function shortcode($atts) {
        $atts = shortcode_atts(array('id' => 0), $atts);
        $post_id = intval($atts['id']);

        if (!$post_id) {
            $default = $this->get_default_author();
            $post_id = $default ? $default->ID : 0;
        }

        if (!$post_id) return '';

        $post = get_post($post_id);
        if (!$post || $post->post_type !== 'gab_author') return '';

        $full_name = get_post_meta($post_id, '_gab_full_name', true) ?: $post->post_title;
        $linkedin = get_post_meta($post_id, '_gab_linkedin', true);
        $experience = get_post_meta($post_id, '_gab_experience', true);
        $specialties = get_post_meta($post_id, '_gab_specialties', true) ?: array();
        $credentials = wp_get_object_terms($post_id, 'gab_credential', array('fields' => 'names'));
        $photo = get_the_post_thumbnail_url($post_id, 'medium');

        ob_start();
        ?>
        <div class="gab-author-card">
            <?php if ($photo): ?>
                <div class="gab-author-photo"><img src="<?php echo esc_url($photo); ?>" alt="<?php echo esc_attr($full_name); ?>"></div>
            <?php endif; ?>
            <div class="gab-author-info">
                <h3 class="gab-author-name"><?php echo esc_html($full_name); ?></h3>
                <?php if (!empty($credentials)): ?>
                    <p class="gab-author-credentials"><?php echo esc_html(implode(', ', $credentials)); ?></p>
                <?php endif; ?>
                <?php if ($experience): ?>
                    <p class="gab-author-experience"><?php echo intval($experience); ?> years experience</p>
                <?php endif; ?>
                <?php if (!empty($specialties)): ?>
                    <div class="gab-author-specialties">
                        <?php foreach ($specialties as $spec): ?>
                            <span class="gab-specialty-tag"><?php echo esc_html($spec); ?></span>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                <?php if ($linkedin): ?>
                    <a href="<?php echo esc_url($linkedin); ?>" class="gab-author-linkedin" target="_blank" rel="noopener">LinkedIn</a>
                <?php endif; ?>
                <div class="gab-author-bio"><?php echo wpautop($post->post_content); ?></div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    public function get_default_author() {
        $authors = get_posts(array(
            'post_type' => 'gab_author',
            'posts_per_page' => 1,
            'post_status' => 'publish',
            'fields' => 'ids',
        ));
        if (!empty($authors)) {
            return get_post($authors[0]);
        }
        return null;
    }

    public function create_default_author() {
        $existing = $this->get_default_author();
        if ($existing) return;

        if (!term_exists('BSc Architecture, KNUST', 'gab_credential')) {
            wp_insert_term('BSc Architecture, KNUST', 'gab_credential');
        }
        if (!term_exists('ARC Registered', 'gab_credential')) {
            wp_insert_term('ARC Registered', 'gab_credential');
        }
        if (!term_exists('GhIS Member', 'gab_credential')) {
            wp_insert_term('GhIS Member', 'gab_credential');
        }

        $post_id = wp_insert_post(array(
            'post_title' => 'Gabochie Design Studio',
            'post_type' => 'gab_author',
            'post_status' => 'publish',
            'post_content' => 'Gabochie Design Studio is a Ghana-based architecture and design practice with a passion for creating spaces that blend modern innovation with cultural heritage. Founded by a KNUST-trained architect with a BSc in Architecture, the studio serves both local and diaspora clients across residential, commercial, and interior design projects.',
        ));

        if ($post_id && !is_wp_error($post_id)) {
            update_post_meta($post_id, '_gab_full_name', 'Gabochie Design Studio');
            update_post_meta($post_id, '_gab_linkedin', 'https://www.linkedin.com/company/gabochiedesign');
            update_post_meta($post_id, '_gab_experience', '8');
            update_post_meta($post_id, '_gab_specialties', array(
                'Residential Architecture',
                'Sustainable Design',
                'Diaspora Building',
                'AI-Powered Design',
                'Interior Design',
                'Project Management',
                '3D Visualization',
                'Cost Estimation',
            ));

            $knust = get_term_by('name', 'BSc Architecture, KNUST', 'gab_credential');
            $arc = get_term_by('name', 'ARC Registered', 'gab_credential');
            $terms = array();
            if ($knust) $terms[] = intval($knust->term_id);
            if ($arc) $terms[] = intval($arc->term_id);
            if (!empty($terms)) {
                wp_set_object_terms($post_id, $terms, 'gab_credential');
            }
        }
    }
}

GAB_Author::instance();
