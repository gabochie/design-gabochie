<?php
defined('ABSPATH') || exit;

class GAB_Case_Studies {
    private static $instance = null;

    public static function instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('init', array($this, 'register_post_type'));
        add_action('add_meta_boxes', array($this, 'add_meta_box'));
        add_action('save_post', array($this, 'save_meta'));
        add_shortcode('gab_case_studies', array($this, 'shortcode'));
        add_filter('the_content', array($this, 'single_template'));
    }

    public function register_post_type() {
        register_post_type('gab_case_study', array(
            'labels' => array(
                'name' => 'Case Studies',
                'singular_name' => 'Case Study',
                'add_new_item' => 'Add New Case Study',
                'edit_item' => 'Edit Case Study',
            ),
            'public' => true,
            'has_archive' => true,
            'supports' => array('title', 'editor', 'thumbnail', 'excerpt', 'custom-fields'),
            'menu_icon' => 'dashicons-portfolio',
            'show_in_menu' => false,
        ));
    }

    public function add_meta_box() {
        add_meta_box(
            'gab_case_study_details',
            'Case Study Details',
            array($this, 'render_meta_box'),
            'gab_case_study',
            'normal',
            'high'
        );
    }

    public function render_meta_box($post) {
        wp_nonce_field('gab_case_study_save', 'gab_case_study_nonce');

        $client = get_post_meta($post->ID, '_gab_client', true);
        $location = get_post_meta($post->ID, '_gab_location', true);
        $year = get_post_meta($post->ID, '_gab_year', true);
        $project_type = get_post_meta($post->ID, '_gab_project_type', true);
        $budget = get_post_meta($post->ID, '_gab_budget', true);
        $features = get_post_meta($post->ID, '_gab_features', true);
        $results = get_post_meta($post->ID, '_gab_results', true);
        ?>
        <table class="form-table">
            <tr>
                <th><label for="gab_client">Client Name</label></th>
                <td><input type="text" id="gab_client" name="gab_client" value="<?php echo esc_attr($client); ?>" class="regular-text"></td>
            </tr>
            <tr>
                <th><label for="gab_location">Location</label></th>
                <td><input type="text" id="gab_location" name="gab_location" value="<?php echo esc_attr($location); ?>" class="regular-text" placeholder="e.g. Accra, Ghana"></td>
            </tr>
            <tr>
                <th><label for="gab_year">Completion Year</label></th>
                <td><input type="number" id="gab_year" name="gab_year" value="<?php echo esc_attr($year); ?>" min="2000" max="2030"></td>
            </tr>
            <tr>
                <th><label for="gab_project_type">Project Type</label></th>
                <td>
                    <select id="gab_project_type" name="gab_project_type">
                        <option value="">— Select —</option>
                        <?php foreach (array('Residential', 'Commercial', 'Interior', 'Renovation', 'New Build') as $type): ?>
                            <option value="<?php echo esc_attr($type); ?>" <?php selected($project_type, $type); ?>><?php echo esc_html($type); ?></option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="gab_budget">Budget Range</label></th>
                <td><input type="text" id="gab_budget" name="gab_budget" value="<?php echo esc_attr($budget); ?>" class="regular-text" placeholder="e.g. GHS 500,000 - GHS 1,000,000"></td>
            </tr>
            <tr>
                <th><label for="gab_features">Key Features</label></th>
                <td><textarea id="gab_features" name="gab_features" rows="5" class="large-text" placeholder="One per line"><?php echo esc_textarea($features); ?></textarea></td>
            </tr>
            <tr>
                <th><label for="gab_results">Results</label></th>
                <td><textarea id="gab_results" name="gab_results" rows="4" class="large-text"><?php echo esc_textarea($results); ?></textarea></td>
            </tr>
        </table>
        <?php
    }

    public function save_meta($post_id) {
        if (!isset($_POST['gab_case_study_nonce']) || !wp_verify_nonce($_POST['gab_case_study_nonce'], 'gab_case_study_save')) return;
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if (!current_user_can('edit_post', $post_id)) return;

        $fields = array('gab_client', 'gab_location', 'gab_budget', 'gab_results');
        foreach ($fields as $field) {
            if (isset($_POST[$field])) {
                update_post_meta($post_id, '_' . $field, sanitize_text_field($_POST[$field]));
            }
        }

        if (isset($_POST['gab_year'])) {
            update_post_meta($post_id, '_gab_year', intval($_POST['gab_year']));
        }
        if (isset($_POST['gab_project_type'])) {
            update_post_meta($post_id, '_gab_project_type', sanitize_text_field($_POST['gab_project_type']));
        }
        if (isset($_POST['gab_features'])) {
            update_post_meta($post_id, '_gab_features', sanitize_textarea_field($_POST['gab_features']));
        }
    }

    public function shortcode($atts) {
        $atts = shortcode_atts(array('count' => 3), $atts);
        $count = intval($atts['count']);

        $posts = get_posts(array(
            'post_type' => 'gab_case_study',
            'posts_per_page' => $count,
            'post_status' => 'publish',
        ));

        if (empty($posts)) return '<p style="color:#787c82;">No case studies found.</p>';

        ob_start();
        ?>
        <div class="gab-case-studies-grid">
            <?php foreach ($posts as $post):
                $location = get_post_meta($post->ID, '_gab_location', true);
                $year = get_post_meta($post->ID, '_gab_year', true);
                $thumb = get_the_post_thumbnail_url($post->ID, 'medium');
            ?>
            <div class="gab-case-study-card">
                <?php if ($thumb): ?>
                    <div class="gab-case-study-thumb">
                        <img src="<?php echo esc_url($thumb); ?>" alt="<?php echo esc_attr($post->post_title); ?>">
                    </div>
                <?php endif; ?>
                <div class="gab-case-study-content">
                    <h3><?php echo esc_html($post->post_title); ?></h3>
                    <p class="gab-case-study-meta">
                        <?php if ($location): ?><span><?php echo esc_html($location); ?></span><?php endif; ?>
                        <?php if ($year): ?><span><?php echo intval($year); ?></span><?php endif; ?>
                    </p>
                    <p><?php echo esc_html($post->post_excerpt ?: wp_trim_words($post->post_content, 30)); ?></p>
                    <a href="<?php echo get_permalink($post->ID); ?>" class="gab-case-study-link">View Project</a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    public function single_template($content) {
        if (!is_singular('gab_case_study')) return $content;

        $client = get_post_meta(get_the_ID(), '_gab_client', true);
        $location = get_post_meta(get_the_ID(), '_gab_location', true);
        $year = get_post_meta(get_the_ID(), '_gab_year', true);
        $project_type = get_post_meta(get_the_ID(), '_gab_project_type', true);
        $budget = get_post_meta(get_the_ID(), '_gab_budget', true);
        $features = get_post_meta(get_the_ID(), '_gab_features', true);
        $results = get_post_meta(get_the_ID(), '_gab_results', true);

        ob_start();
        ?>
        <article class="gab-case-study-single" itemscope itemtype="https://schema.org/Article">
            <meta itemprop="name" content="<?php echo esc_attr(get_the_title()); ?>">
            <meta itemprop="author" content="Gabochie Design Studio">

            <div class="gab-case-study-details" itemscope itemtype="https://schema.org/WPProject">
                <table class="gab-case-study-table">
                    <?php if ($client): ?><tr><th>Client</th><td><?php echo esc_html($client); ?></td></tr><?php endif; ?>
                    <?php if ($location): ?><tr><th>Location</th><td><?php echo esc_html($location); ?></td></tr><?php endif; ?>
                    <?php if ($year): ?><tr><th>Year</th><td><?php echo intval($year); ?></td></tr><?php endif; ?>
                    <?php if ($project_type): ?><tr><th>Type</th><td><?php echo esc_html($project_type); ?></td></tr><?php endif; ?>
                    <?php if ($budget): ?><tr><th>Budget</th><td><?php echo esc_html($budget); ?></td></tr><?php endif; ?>
                </table>

                <?php if ($features): ?>
                    <h3>Key Features</h3>
                    <ul class="gab-case-study-features">
                        <?php foreach (explode("\n", $features) as $feature): ?>
                            <li><?php echo esc_html(trim($feature)); ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>

                <?php if ($results): ?>
                    <h3>Results</h3>
                    <div class="gab-case-study-results"><?php echo wpautop(esc_html($results)); ?></div>
                <?php endif; ?>
            </div>

            <div class="gab-case-study-content-inner">
                <?php echo $content; ?>
            </div>
        </article>
        <?php
        return ob_get_clean();
    }

    public function seed_example() {
        $existing = get_posts(array(
            'post_type' => 'gab_case_study',
            'posts_per_page' => 1,
            'post_status' => 'publish',
        ));

        if (!empty($existing)) return;

        wp_insert_post(array(
            'post_title' => 'Modern Diaspora Family Home – Accra',
            'post_type' => 'gab_case_study',
            'post_status' => 'publish',
            'post_excerpt' => 'A 4-bedroom modern residence designed for a diaspora family building their dream home in Accra. The project balanced contemporary aesthetics with tropical climate responsiveness.',
            'post_content' => 'This project involved designing a 4-bedroom family residence for a Ghanaian diaspora family based in the UK. The clients wanted a home that reflected their modern aesthetic sensibilities while remaining practical for the Accra climate. The design incorporates cross-ventilation strategies, deep overhangs for sun shading, and locally sourced materials. The result is a sustainable, energy-efficient home that blends contemporary architecture with Ghanaian building traditions.',
            'meta_input' => array(
                '_gab_client' => 'Private Client (Diaspora)',
                '_gab_location' => 'Accra, Ghana',
                '_gab_year' => '2025',
                '_gab_project_type' => 'Residential',
                '_gab_budget' => 'GHS 2,500,000 - GHS 3,500,000',
                '_gab_features' => "4 bedrooms with en-suite\nOpen-plan living/dining\nSwimming pool\nRooftop terrace\nSolar-ready infrastructure\nSmart home automation\nRainwater harvesting system",
                '_gab_results' => 'Completed on schedule and within budget. The clients reported a 30% reduction in projected energy costs due to passive design strategies. Featured in a local architecture publication.',
            ),
        ));
    }
}

GAB_Case_Studies::instance();
