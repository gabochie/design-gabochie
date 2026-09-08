<?php
/**
 * Marketplace Property Intelligence Report generator.
 *
 * Produces a fully automated AI Property Intelligence Report per listing and
 * caches it in `_gd_intelligence` (JSON). The report combines listing metadata,
 * comparable listings from the same CPT, a build-cost estimate from the
 * calculator engine (bcgh_estimate), and an LLM-written analysis via the same
 * 3-tier chain as the news portal (auto-blog → GHBC_LLM → builtin Gemini).
 *
 * Never invents figures: the LLM is constrained to use only the data provided;
 * the calculator numbers are computed, not generated.
 *
 * @package Gabochie_Marketplace
 */

defined('ABSPATH') || exit;

/**
 * Marketplace intelligence generator.
 */
class Gabochie_Marketplace_AI
{

    /**
     * @var Gabochie_Marketplace_AI|null 
     */
    private static $instance = null;

    /**
     * @var string Last provider that succeeded ('' if none). 
     */
    private $last_source = '';

    /**
     * @return Gabochie_Marketplace_AI 
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
    }

    /**
     * @return string 
     */
    public function last_source()
    {
        return $this->last_source;
    }

    /**
     * Generate (and cache) the intelligence report for a listing.
     *
     * @param  int $post_id
     * @return bool Whether a report was written.
     */
    public function generate_report( $post_id )
    {
        $post_id = (int) $post_id;

        $estimate      = $this->listing_estimate($post_id);
        $comparables   = $this->comparables($post_id, 4);
        $market_ctx    = $this->market_context();
        $listing_ctx   = $this->listing_context($post_id, $estimate, $comparables);

        $system = 'You are a Ghana property intelligence analyst for Gabochie Design. '
        . 'You turn a curated property listing plus verified market data into a concise, '
        . 'decision-useful intelligence brief for a buyer, developer or investor. '
        . 'Doctrine: NEVER invent figures, dates, names, claims or locations. Use ONLY the data '
        . 'provided in the listing and context. Where data is insufficient for a field, return null. '
        . 'Return STRICT JSON (no markdown fences, no commentary).';

        $prompt = $this->build_prompt($listing_ctx, $market_ctx);

        $json = $this->complete($system, $prompt);
        $data = json_decode((string) $json, true);
        if (! is_array($data) ) {
            Gabochie_Marketplace::log_error("intelligence: LLM returned invalid JSON for {$post_id}");
            return false;
        }

        $this->finalize_report($post_id, $data);
        return true;
    }

    /**
     * Run the 3-tier completion chain and return the raw text.
     *
     * @param  string $system
     * @param  string $prompt
     * @return string
     */
    public function complete( $system, $prompt )
    {
        // Tier 1: gabochie-auto-blog AI adapter.
        if (class_exists('Gab_Auto_AI') ) {
            $out = Gab_Auto_AI::instance()->complete($system, $prompt);
            if (is_string($out) && '' !== trim($out) ) {
                $this->last_source = 'auto-blog';
                return $out;
            }
        }
        // Tier 2: GhBC_LLM.
        if (class_exists('GHBC_LLM') ) {
            $llm = GHBC_LLM::instance();
            if ($llm->is_ready() ) {
                $out = $llm->complete($system, array( array( 'role' => 'user', 'content' => $prompt ) ));
                if (is_string($out) && '' !== trim($out) ) {
                    $this->last_source = 'ghbc-llm';
                    return $out;
                }
            }
        }
        // Tier 3: builtin Gemini direct.
        $out = $this->gemini_direct($system, $prompt);
        if ('' !== $out ) {
            $this->last_source = 'builtin:gemini';
        }
        return $out;
    }

    /**
     * Direct Google Generative Language call with retry-with-backoff.
     *
     * @param  string $system
     * @param  string $prompt
     * @return string
     */
    private function gemini_direct( $system, $prompt )
    {
        $key   = (string) get_option('ghbc_llm_key', '');
        if ('' === $key ) {
            return '';
        }
        $model = (string) get_option(Gabochie_Marketplace::OPT_AI_MODEL, 'gemini-3.5-flash');
        $url   = 'https://generativelanguage.googleapis.com/v1beta/models/' . rawurlencode($model) . ':generateContent?key=' . rawurlencode($key);

        $body = array(
        'systemInstruction' => array(
        'parts' => array( array( 'text' => $system ) ),
        ),
        'contents'          => array(
        array(
                    'role'  => 'user',
                    'parts' => array( array( 'text' => $prompt ) ),
        ),
        ),
        'generationConfig'  => array(
        'temperature'     => 0.5,
        'maxOutputTokens' => 2048,
        'responseMimeType'=> 'application/json',
        ),
        );

        $attempts = 3;
        for ( $i = 1; $i <= $attempts; $i++ ) {
            $resp = wp_remote_post(
                $url,
                array(
                'headers' => array( 'Content-Type' => 'application/json' ),
                'body'    => wp_json_encode($body),
                'timeout' => 90,
                )
            );
            if (is_wp_error($resp) ) {
                Gabochie_Marketplace::log_error('intelligence: gemini transport: ' . $resp->get_error_message());
            } else {
                $code = (int) wp_remote_retrieve_response_code($resp);
                if ($code >= 200 && $code < 300 ) {
                    $parsed = json_decode(wp_remote_retrieve_body($resp), true);
                    if (! empty($parsed['candidates'][0]['content']['parts'][0]['text']) ) {
                        return (string) $parsed['candidates'][0]['content']['parts'][0]['text'];
                    }
                } elseif (429 === $code || $code >= 500 ) {
                    Gabochie_Marketplace::log_error("intelligence: gemini HTTP {$code} (attempt {$i})");
                } else {
                    Gabochie_Marketplace::log_error("intelligence: gemini HTTP {$code}");
                    return '';
                }
            }
            if ($i < $attempts ) {
                sleep($i * 5);
            }
        }
        return '';
    }

    /**
     * Pull the listing title + meta into a context string.
     *
     * @param  int   $post_id
     * @param  array $estimate
     * @param  array $comparables
     * @return string
     */
    private function listing_context( $post_id, $estimate, $comparables )
    {
        $price      = (float) get_post_meta($post_id, Gabochie_Marketplace::META_PRICE, true);
        $size       = (float) get_post_meta($post_id, Gabochie_Marketplace::META_SIZE_M2, true);
        $location   = (string) get_post_meta($post_id, Gabochie_Marketplace::META_LOCATION, true);
        $bed        = (int) get_post_meta($post_id, Gabochie_Marketplace::META_BEDROOMS, true);
        $bath       = (int) get_post_meta($post_id, Gabochie_Marketplace::META_BATHROOMS, true);

        return 'Listing title: ' . get_the_title($post_id) . "\n"
        . 'Property type: ' . Gabochie_Marketplace::get_type_slug($post_id) . "\n"
        . 'Listing status: ' . Gabochie_Marketplace::get_status_slug($post_id) . "\n"
        . 'Location: ' . $location . "\n"
        . 'Asking price (GHS): ' . number_format($price) . "\n"
        . 'Size (m2): ' . $size . "\n"
        . 'Bedrooms: ' . $bed . "\n"
        . 'Bathrooms: ' . $bath . "\n\n"
        . 'Development cost estimate (from Gabochie build-cost engine): '
        . ( $estimate ? wp_json_encode($estimate) : 'null' ) . "\n\n"
        . 'Comparable listings: ' . ( $comparables ? wp_json_encode($comparables) : '[]' );
    }

    /**
     * Programme-derived market context (median asking prices by node).
     *
     * @return string
     */
    private function market_context()
    {
        $medians = array(
        'Accra premium nodes'      => 1500000,
        'Accra mid-market'         => 950000,
        'Kumasi'                   => 800000,
        'Tema'                     => 700000,
        'Takoradi'                 => 650000,
        'Cape Coast'               => 600000,
        'Greater Accra periphery'  => 500000,
        );
        $out = 'Ghana property market context (derived, not invented): ';
        foreach ( $medians as $label => $v ) {
            $out .= "{$label}: GHS " . number_format($v) . '; ';
        }
        return trim($out, '; ');
    }

    /**
     * Compute a development cost estimate via the calculator engine.
     *
     * @param  int $post_id
     * @return array|null
     */
    private function listing_estimate( $post_id )
    {
        if (! function_exists('bcgh_estimate') ) {
            return null;
        }
        $type  = Gabochie_Marketplace::get_type_slug($post_id);
        $size  = (float) get_post_meta($post_id, Gabochie_Marketplace::META_SIZE_M2, true);
        $loc   = $this->map_location((string) get_post_meta($post_id, Gabochie_Marketplace::META_LOCATION, true));
        if ($size <= 0 ) {
            return null;
        }

        // Residential building types only make sense for house/apartment; land
        // and development-site estimate a generic multi-unit residential shell.
        if ('house' === $type ) {
            $building = 'single-storey';
        } elseif ('apartment' === $type ) {
            $building = 'apartment';
        } elseif ('commercial' === $type ) {
            $building = 'commercial-office';
        } else {
            $building = 'apartment';
        }

        $args = array(
        'category'     => ( 'commercial' === $type ) ? 'non-residential' : 'residential',
        'location'     => $loc,
        'building'     => $building,
        'area'         => $size,
        'floors'       => (int) max(1, get_post_meta($post_id, Gabochie_Marketplace::META_BEDROOMS, true) > 0 ? 1 : 1),
        'finish'       => 'standard',
        'roof'         => 'tile',
        'professional' => 'all',
        'contingency'  => 10,
        'extras'       => array(),
        );

        return bcgh_estimate($args);
    }

    /**
     * Map a location text to a calculator location key (best-effort).
     *
     * @param  string $text
     * @return string
     */
    private function map_location( $text )
    {
        $text  = strtolower((string) $text);
        $rules = array(
        'accra'       => array( 'accra', 'legon', 'cantonments', 'east legon', 'airport', 'ridge', 'osu', 'labadi', 'dansoman', 'adenta', 'dodowa', 'spintex', 'trassaco' ),
        'tema'        => array( 'tema', 'community 25', 'sakumono' ),
        'kumasi'      => array( 'kumasi', 'adum' ),
        'takoradi'    => array( 'takoradi', 'sekondi' ),
        'cape-coast'  => array( 'cape coast', 'cape-coast' ),
        'tamale'      => array( 'tamale' ),
        'rural'       => array( 'village', 'rural' ),
        );
        foreach ( $rules as $key => $needles ) {
            foreach ( $needles as $needle ) {
                if (false !== strpos($text, $needle) ) {
                    return $key;
                }
            }
        }
        return 'other-urban';
    }

    /**
     * Pull comparable listings (same type, published, excludes self).
     *
     * @param  int $post_id
     * @param  int $limit
     * @return array
     */
    private function comparables( $post_id, $limit = 4 )
    {
        $type = Gabochie_Marketplace::get_type_slug($post_id);
        if ('' === $type ) {
            return array();
        }
        $ids = get_posts(
            array(
            'post_type'      => Gabochie_Marketplace::CPT,
            'post_status'    => 'publish',
            'posts_per_page' => $limit,
            'fields'         => 'ids',
            'exclude'        => array( $post_id ),
            'tax_query'      => array(
                    array(
                        'taxonomy' => Gabochie_Marketplace::TAX_TYPE,
                        'field'    => 'slug',
                        'terms'    => $type,
            ),
            ),
            )
        );
        $out = array();
        foreach ( (array) $ids as $id ) {
            $price = (float) get_post_meta($id, Gabochie_Marketplace::META_PRICE, true);
            $size  = (float) get_post_meta($id, Gabochie_Marketplace::META_SIZE_M2, true);
            $out[] = array(
            'title'        => get_the_title($id),
            'location'     => (string) get_post_meta($id, Gabochie_Marketplace::META_LOCATION, true),
            'price'        => $price,
            'size_m2'      => $size,
            'price_per_m2' => $size > 0 ? round($price / $size) : null,
            );
        }
        return $out;
    }

    /**
     * Build the structured LLM prompt.
     *
     * @param  string $listing_ctx
     * @param  string $market_ctx
     * @return string
     */
    private function build_prompt( $listing_ctx, $market_ctx )
    {
        return "Produce a Property Intelligence Report for the following Ghana property listing.\n\n"
        . "===LISTING===\n{$listing_ctx}\n\n"
        . "===MARKET===\n{$market_ctx}\n\n"
        . 'Return a JSON object with exactly these keys: '
        . '{"location":{"area","city","infrastructure","demand"},'
        . '"comparables":[{"title","price","size_m2","price_per_m2"}],'
        . '"feasibility":{"potential_use","estimated_cost","estimated_gdv","developer_margin","timeline"},'
        . '"risk":{"title_deed","access","flooding","utilities"},'
        . '"recommendation": "..."}. '
        . 'estimated_cost/estimated_gdv: use the development cost estimate provided when present; '
        . 'for land/development-site listings frame feasibility as buildable units + cost inside the estimate band. '
        . 'Never invent figures — use only provided data, else null. recommendation is one paragraph.';
    }

    /**
     * Cache the finalized report on the listing.
     *
     * @param int   $post_id
     * @param array $data
     */
    private function finalize_report( $post_id, $data )
    {
        $data['generated_at'] = gmdate('c');
        $data['provider']     = $this->last_source;
        update_post_meta($post_id, Gabochie_Marketplace::META_INTEL, wp_json_encode($data));
        update_post_meta($post_id, Gabochie_Marketplace::META_INTEL_AT, current_time('mysql'));
        update_post_meta($post_id, Gabochie_Marketplace::META_INTEL_PROVIDER, $this->last_source);
    }
}
