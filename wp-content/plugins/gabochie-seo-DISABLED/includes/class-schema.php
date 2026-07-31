<?php
defined('ABSPATH') || exit;

class Gabochie_SEO_Schema {
    private static $instance = null;

    public static function instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('wp_head', array($this, 'output_schema'), 2);
        add_filter('rank_math/json_ld', array($this, 'extend_rank_math_schema'));
    }

    public function output_schema() {
        if (is_front_page()) {
            $this->output_organization_schema();
        }
        if (is_singular('post')) {
            $this->output_article_schema();
        }
        if (is_singular('page')) {
            $page_slugs = array('about', 'services', 'contact', 'pricing');
            if (in_array(get_post_field('post_name'), $page_slugs, true)) {
                $this->output_local_business_schema();
            }
        }
    }

    private function output_organization_schema() {
        $socials = function_exists('gabochie_get_social_links') ? gabochie_get_social_links() : array();
        $schema = array(
            '@context' => 'https://schema.org',
            '@type' => array('Organization', 'Architect'),
            '@id' => home_url('/#organization'),
            'name' => 'Gabochie Design',
            'url' => home_url('/'),
            'logo' => array(
                '@type' => 'ImageObject',
                'url' => get_theme_mod('custom_logo') ? wp_get_attachment_url(get_theme_mod('custom_logo')) : home_url('/wp-content/uploads/2026/07/og-image.jpg'),
            ),
            'email' => 'admin@gabochiedesign.com',
            'telephone' => '+233 26 824 8970',
            'address' => array(
                '@type' => 'PostalAddress',
                'addressLocality' => 'Sakumono, Tema',
                'addressCountry' => 'GH',
            ),
            'description' => 'AI-powered residential architecture firm serving Ghana and the African diaspora. Specializing in sustainable design, diaspora building, and AI-accelerated project delivery.',
            'sameAs' => array_values($socials),
            'knowsAbout' => array(
                'Residential Architecture',
                'Sustainable Design Ghana',
                'Diaspora Building',
                'AI-Powered Design',
                'Ghana Building Regulations',
                'Interior Design',
                '3D Visualization',
                'Project Management',
            ),
            'areaServed' => array(
                array('@type' => 'Country', 'name' => 'Ghana'),
                array('@type' => 'Country', 'name' => 'United Kingdom'),
                array('@type' => 'Country', 'name' => 'United States'),
                array('@type' => 'Country', 'name' => 'Canada'),
            ),
            'hasOfferCatalog' => array(
                '@type' => 'OfferCatalog',
                'name' => 'Architecture Services',
                'itemListElement' => array(
                    array('@type' => 'Offer', 'itemOffered' => array('@type' => 'Service', 'name' => 'Architectural Design')),
                    array('@type' => 'Offer', 'itemOffered' => array('@type' => 'Service', 'name' => 'Interior Design')),
                    array('@type' => 'Offer', 'itemOffered' => array('@type' => 'Service', 'name' => '3D Visualization')),
                    array('@type' => 'Offer', 'itemOffered' => array('@type' => 'Service', 'name' => 'Project Management')),
                    array('@type' => 'Offer', 'itemOffered' => array('@type' => 'Service', 'name' => 'AI Cost Calculator')),
                ),
            ),
        );
        echo "\n<!-- Gabochie Schema -->\n";
        echo '<script type="application/ld+json">' . wp_json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . '</script>' . "\n";
    }

    private function output_article_schema() {
        global $post;
        $schema = array(
            '@context' => 'https://schema.org',
            '@type' => 'Article',
            '@id' => get_permalink() . '#article',
            'headline' => $post->post_title,
            'description' => wp_trim_words(strip_tags($post->post_content), 25),
            'datePublished' => get_the_date('c'),
            'dateModified' => get_the_modified_date('c'),
            'author' => array(
                '@type' => 'Person',
                'name' => get_the_author_meta('display_name', $post->post_author),
                'url' => get_author_posts_url($post->post_author),
            ),
            'publisher' => array(
                '@type' => 'Organization',
                '@id' => home_url('/#organization'),
                'name' => 'Gabochie Design',
            ),
            'mainEntityOfPage' => array('@type' => 'WebPage', '@id' => get_permalink()),
        );
        if (has_post_thumbnail()) {
            $schema['image'] = array(
                '@type' => 'ImageObject',
                'url' => get_the_post_thumbnail_url($post->ID, 'full'),
                'width' => 1200,
                'height' => 630,
            );
        }
        echo '<script type="application/ld+json">' . wp_json_encode($schema, JSON_UNESCAPED_SLASHES) . '</script>' . "\n";
    }

    private function output_local_business_schema() {
        $schema = array(
            '@context' => 'https://schema.org',
            '@type' => array('LocalBusiness', 'Architect'),
            '@id' => home_url('/#localbusiness'),
            'name' => 'Gabochie Design',
            'url' => home_url('/'),
            'telephone' => '+233 26 824 8970',
            'email' => 'admin@gabochiedesign.com',
            'address' => array(
                '@type' => 'PostalAddress',
                'addressLocality' => 'Sakumono, Tema',
                'addressCountry' => 'GH',
            ),
            'openingHours' => 'Mo-Fr 09:00-18:00',
            'priceRange' => '$$$',
            'image' => home_url('/wp-content/uploads/2026/07/og-image.jpg'),
        );
        echo '<script type="application/ld+json">' . wp_json_encode($schema, JSON_UNESCAPED_SLASHES) . '</script>' . "\n";
        echo "<!-- / Gabochie Schema -->\n";
    }

    public function extend_rank_math_schema($data) {
        if (isset($data['organization'])) {
            $data['organization']['knowsAbout'] = array(
                'Residential Architecture Ghana',
                'Sustainable Design',
                'Diaspora Building',
                'AI-Powered Design',
                'Ghana Building Regulations',
            );
            $data['organization']['areaServed'] = array(
                array('@type' => 'Country', 'name' => 'Ghana'),
                array('@type' => 'Country', 'name' => 'United Kingdom'),
                array('@type' => 'Country', 'name' => 'United States'),
            );
        }
        return $data;
    }
}

Gabochie_SEO_Schema::instance();
