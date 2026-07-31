<?php
if (!defined('ABSPATH')) exit;

class Gabochie_Hub {

    const HUB_VERSION = '1.1.0';

    private static function is_plugin_active_check($plugin_file) {
        $active = get_option('active_plugins', array());
        foreach ($active as $p) {
            if (strpos($p, $plugin_file) !== false) return true;
        }
        return false;
    }
    public static function render_dashboard() {
        if (!current_user_can('manage_options')) return;

        $sync = new Gabochie_Content_Sync();
        $status = $sync->get_status();
        $log = get_option('gabochie_sync_log', array());
        $log = array_reverse(array_slice($log, -10));

        $posts = wp_count_posts('post');
        $pages = wp_count_posts('page');
        $comments = wp_count_comments();

        $plugins = get_plugins();
        $active = get_option('active_plugins', array());
        $gabochie_plugins = array();
        foreach ($plugins as $file => $data) {
            if (strpos($file, 'gabochie') === 0 || strpos($file, 'gabochie') !== false) {
                $gabochie_plugins[$file] = $data;
                $gabochie_plugins[$file]['active'] = in_array($file, $active);
            }
        }
        ?>
        <div class="wrap gabochie-hub-wrap">
            <div class="gh-header">
                <div class="gh-brand">
                    <svg width="40" height="40" viewBox="0 0 52 52" fill="none">
                        <polygon points="26,3 49,15 49,37 26,49 3,37 3,15" fill="none" stroke="#C9A96E" stroke-width="1.5"/>
                        <text x="26" y="28" text-anchor="middle" font-family="serif" font-size="16" font-weight="700" fill="#0D5C52">GD</text>
                    </svg>
                    <div>
                        <h1>Gabochie Design</h1>
                        <p>AI-Powered Architecture — Command Center</p>
                    </div>
                </div>
            </div>

            <div class="gh-grid">
                <div class="gh-card">
                    <div class="gh-card-icon" style="background:#E8F4F2;color:#0D5C52">&#128230;</div>
                    <div class="gh-card-body">
                        <div class="gh-card-label">Posts</div>
                        <div class="gh-card-value"><?php echo $posts->publish; ?></div>
                        <div class="gh-card-sub"><?php echo $posts->draft; ?> drafts</div>
                    </div>
                </div>
                <div class="gh-card">
                    <div class="gh-card-icon" style="background:#FBF5E8;color:#C9A96E">&#128196;</div>
                    <div class="gh-card-body">
                        <div class="gh-card-label">Pages</div>
                        <div class="gh-card-value"><?php echo $pages->publish; ?></div>
                        <div class="gh-card-sub"><?php echo $pages->draft; ?> drafts</div>
                    </div>
                </div>
                <div class="gh-card">
                    <div class="gh-card-icon" style="background:<?php echo $status['connected'] ? '#E8F4F2' : '#f8d7da' ?>;color:<?php echo $status['connected'] ? '#0D5C52' : '#d63638' ?>">&#128279;</div>
                    <div class="gh-card-body">
                        <div class="gh-card-label">Live Sync</div>
                        <div class="gh-card-value"><?php echo $status['connected'] ? 'Connected' : 'Offline'; ?></div>
                        <div class="gh-card-sub"><?php echo $status['last_sync'] ? 'Last: ' . date('M j, g:i A', strtotime($status['last_sync'])) : 'Never synced'; ?></div>
                    </div>
                </div>
                <div class="gh-card">
                    <div class="gh-card-icon" style="background:#E8F4F2;color:#0D5C52">&#128736;</div>
                    <div class="gh-card-body">
                        <div class="gh-card-label">Environment</div>
                        <div class="gh-card-value"><?php echo phpversion(); ?></div>
                        <div class="gh-card-sub">XAMPP / <?php echo defined('GABOCHIE_SYNC_VERSION') ? 'v' . GABOCHIE_SYNC_VERSION : ''; ?></div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="gh-section">
                <h2>Quick Actions</h2>
                <div class="gh-actions-row">
                    <a href="<?php echo admin_url('admin.php?page=gabochie-strategy'); ?>" class="gh-action-btn">
                        <span class="gh-action-icon">&#128209;</span>
                        <span>View Strategy</span>
                    </a>
                    <a href="<?php echo admin_url('admin.php?page=gabochie-roadmap'); ?>" class="gh-action-btn">
                        <span class="gh-action-icon">&#128203;</span>
                        <span>Build Roadmap</span>
                    </a>
                    <a href="<?php echo admin_url('admin.php?page=gabochie-sync'); ?>" class="gh-action-btn">
                        <span class="gh-action-icon">&#8644;</span>
                        <span>Sync Content</span>
                    </a>
                    <a href="<?php echo admin_url('edit.php'); ?>" class="gh-action-btn">
                        <span class="gh-action-icon">&#9998;</span>
                        <span>Edit Posts</span>
                    </a>
                    <a href="<?php echo home_url('/'); ?>" class="gh-action-btn" target="_blank">
                        <span class="gh-action-icon">&#127760;</span>
                        <span>View Site</span>
                    </a>
                    <a href="<?php echo admin_url('customize.php'); ?>" class="gh-action-btn">
                        <span class="gh-action-icon">&#127912;</span>
                        <span>Customize</span>
                    </a>
                </div>
            </div>

            <!-- Plugins Status -->
            <div class="gh-section">
                <h2>Gabochie Plugins</h2>
                <?php if (empty($gabochie_plugins)) : ?>
                    <p class="gh-empty">No Gabochie plugins installed.</p>
                <?php else : ?>
                <table class="widefat gh-log-table">
                    <thead><tr><th>Plugin</th><th>Version</th><th>Status</th><th>Description</th></tr></thead>
                    <tbody>
                    <?php foreach ($gabochie_plugins as $file => $p) : ?>
                        <tr>
                            <td><strong><?php echo esc_html($p['Name']); ?></strong></td>
                            <td><?php echo esc_html($p['Version']); ?></td>
                            <td><span class="gh-badge <?php echo $p['active'] ? 'gh-badge-success' : 'gh-badge-error'; ?>"><?php echo $p['active'] ? 'Active' : 'Inactive'; ?></span></td>
                            <td><?php echo esc_html(wp_trim_words($p['Description'], 12)); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>

            <!-- Recent Activity -->
            <div class="gh-section">
                <h2>Recent Sync Activity</h2>
                <?php if (empty($log)) : ?>
                    <p class="gh-empty">No sync activity yet. Go to <a href="<?php echo admin_url('admin.php?page=gabochie-sync'); ?>">Sync</a> to push/pull content.</p>
                <?php else : ?>
                <table class="widefat gh-log-table">
                    <thead><tr><th>Time</th><th>Action</th><th>Status</th><th>Message</th></tr></thead>
                    <tbody>
                    <?php foreach ($log as $e) : ?>
                        <tr>
                            <td><?php echo date('M j, g:i A', strtotime($e['time'])); ?></td>
                            <td><code><?php echo esc_html($e['action']); ?></code></td>
                            <td><span class="gh-badge gh-badge-<?php echo esc_attr($e['status']); ?>"><?php echo esc_html($e['status']); ?></span></td>
                            <td><?php echo esc_html(wp_trim_words($e['message'], 20)); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>

            <!-- Recommendations (Auto-Detecting) -->
            <div class="gh-section">
                <h2>Smart Recommendations <small style="font-weight:normal;font-size:12px;color:#646970;">(auto-refreshes)</small></h2>
                <div class="gh-recommendations">
                    <?php
                    $recs = array();
                    $cache_key = 'gabochie_recs_cache';
                    $cached = get_transient($cache_key);

                    if ($cached === false || !is_array($cached) || empty($cached['time']) || (time() - $cached['time']) > 3600) {
                        $checks = array();

                        // 1. Wordfence IP whitelist check
                        $checks['wf_whitelisted'] = false;
                        $sync_url = home_url('/wp-json/gabochie-sync/v1/status');
                        $sync_resp = wp_remote_get($sync_url, array('timeout' => 5));
                        if (!is_wp_error($sync_resp)) {
                            $code = wp_remote_retrieve_response_code($sync_resp);
                            if ($code === 200) {
                                $body = json_decode(wp_remote_retrieve_body($sync_resp), true);
                                $checks['wf_whitelisted'] = !empty($body['connected']);
                            }
                        }

                        // 2. Sync test (same as above)
                        $checks['sync_working'] = $checks['wf_whitelisted'];

                        // 3. Rank Math sitemap check
                        $checks['sitemap_ok'] = false;
                        $sitemap_url = home_url('/sitemap_index.xml');
                        $sitemap_resp = wp_remote_head($sitemap_url, array('timeout' => 5));
                        if (!is_wp_error($sitemap_resp)) {
                            $checks['sitemap_ok'] = wp_remote_retrieve_response_code($sitemap_resp) === 200;
                        }

                        // 4. llms.txt check
                        $checks['llms_ok'] = false;
                        $llms_resp = wp_remote_head(home_url('/llms.txt'), array('timeout' => 5));
                        if (!is_wp_error($llms_resp)) {
                            $checks['llms_ok'] = wp_remote_retrieve_response_code($llms_resp) === 200;
                        }

                        // 5. PMPro installed
                        $checks['pmpro_installed'] = false;
                        foreach (get_plugins() as $file => $data) {
                            if (strpos($file, 'paid-memberships-pro') !== false) {
                                $checks['pmpro_installed'] = true;
                                $checks['pmpro_active'] = in_array($file, get_option('active_plugins', array()));
                                break;
                            }
                        }

                        // 6. Deployer on page.php
                        $checks['deployer_active'] = false;
                        $page_php = get_template_directory() . '/page.php';
                        if (file_exists($page_php)) {
                            $page_content = file_get_contents($page_php);
                            $checks['deployer_active'] = strpos($page_content, 'gabochie-deploy') !== false;
                        }

                        set_transient($cache_key, array('data' => $checks, 'time' => time()), 3600);
                        $checks_cache = $checks;
                    } else {
                        $checks_cache = $cached['data'];
                    }

                    // === CRITICAL: Sync & Security ===
                    if ($checks_cache['wf_whitelisted']) {
                        $recs[] = array('icon' => '&#9989;', 'title' => 'Wordfence IP Whitelisted', 'desc' => 'Dev IP 41.215.171.9 is whitelisted. REST API sync is working.', 'priority' => 'done');
                    } else {
                        $recs[] = array('icon' => '&#128737;', 'title' => 'Whitelist Dev IP in Wordfence', 'desc' => 'Wordfence blocks REST API sync. Add IP 41.215.171.9 to Wordfence > All Options > Whitelisted IP Addresses.', 'priority' => 'high', 'url' => admin_url('admin.php?page=Wordfence&wafOptions'));
                    }

                    if ($checks_cache['sync_working']) {
                        $recs[] = array('icon' => '&#9989;', 'title' => 'Live-Local Sync Working', 'desc' => 'REST API connection verified. Push/pull is functional.', 'priority' => 'done');
                    } else {
                        $recs[] = array('icon' => '&#8644;', 'title' => 'Test Live-Local Sync', 'desc' => 'REST API not reachable. Whitelist IP in Wordfence first.', 'priority' => 'high', 'url' => admin_url('admin.php?page=gabochie-sync'));
                    }

                    // === SEO & Indexing ===
                    if (empty(get_option('gabochie_gsc_submitted'))) {
                        $recs[] = array('icon' => '&#128269;', 'title' => 'Submit to Google Search Console', 'desc' => 'Your sitemap is ready but not submitted to Google.', 'priority' => 'high', 'url' => 'https://search.google.com/search-console');
                    } else {
                        $recs[] = array('icon' => '&#9989;', 'title' => 'Google Search Console Submitted', 'desc' => 'Sitemap submitted to Google. Check indexing status regularly.', 'priority' => 'done');
                    }

                    if (empty(get_option('gabochie_bing_submitted'))) {
                        $recs[] = array('icon' => '&#128270;', 'title' => 'Submit to Bing Webmaster Tools', 'desc' => 'Bing powers 9% of searches. Submit your sitemap.', 'priority' => 'high', 'url' => 'https://www.bing.com/webmasters');
                    } else {
                        $recs[] = array('icon' => '&#9989;', 'title' => 'Bing Webmaster Tools Submitted', 'desc' => 'Sitemap submitted to Bing.', 'priority' => 'done');
                    }

                    // === Content & SEO ===
                    $post_count = wp_count_posts('post');
                    if ($post_count->publish < 30) {
                        $recs[] = array('icon' => '&#9997;&#65039;', 'title' => 'Publish More Content', 'desc' => 'You have ' . $post_count->publish . ' published posts. Aim for 30+ for topical authority.', 'priority' => 'medium', 'url' => admin_url('edit.php'));
                    } else {
                        $recs[] = array('icon' => '&#9989;', 'title' => 'Content Threshold Met', 'desc' => $post_count->publish . ' posts published. Focus on quality and backlinks.', 'priority' => 'done');
                    }

                    $pillar_posts = get_posts(array('post_type' => 'post', 'post_name__in' => array('complete-guide-building-house-ghana-2026', 'build-house-ghana-from-abroad-diaspora-guide-2026', 'ai-architecture-transforming-building-design-ghana-2026', 'ghana-building-regulations-permits-complete-guide-2026', 'choose-best-architectural-firm-ghana-guide-2026'), 'numberposts' => 5));
                    if (count($pillar_posts) < 5) {
                        $recs[] = array('icon' => '&#128214;', 'title' => 'Publish Pillar Posts', 'desc' => (5 - count($pillar_posts)) . ' pillar posts not yet published.', 'priority' => 'high', 'url' => admin_url('edit.php'));
                    } else {
                        $recs[] = array('icon' => '&#9989;', 'title' => 'All 5 Pillar Posts Published', 'desc' => 'Pillar content is live. Focus on backlinks.', 'priority' => 'done');
                    }

                    // === Analytics & Tracking ===
                    if (!self::is_plugin_active_check('google-site-kit/google-site-kit.php')) {
                        $recs[] = array('icon' => '&#128200;', 'title' => 'Install Site Kit by Google', 'desc' => 'Connects GA4 + Search Console.', 'priority' => 'medium', 'url' => admin_url('plugin-install.php?s=site+kit&tab=plugin-information'));
                    } else {
                        $recs[] = array('icon' => '&#9989;', 'title' => 'Site Kit Installed', 'desc' => 'GA4 + Search Console connected.', 'priority' => 'done');
                    }

                    $recs[] = array('icon' => '&#128205;', 'title' => 'Create Google Business Profile', 'desc' => 'Free local SEO listing for Ghana searches.', 'priority' => 'high', 'url' => 'https://business.google.com');

                    // === Technical SEO ===
                    if ($checks_cache['sitemap_ok']) {
                        $recs[] = array('icon' => '&#9989;', 'title' => 'Rank Math Sitemap OK', 'desc' => 'Sitemap is live and accessible.', 'priority' => 'done');
                    } else {
                        $recs[] = array('icon' => '&#128260;', 'title' => 'Flush Rank Math Sitemap', 'desc' => 'Sitemap may be stale. Flush cache.', 'priority' => 'medium', 'url' => admin_url('admin.php?page=rank-math'));
                    }

                    if ($checks_cache['llms_ok']) {
                        $recs[] = array('icon' => '&#9989;', 'title' => 'llms.txt Verified', 'desc' => 'AI crawler file is live.', 'priority' => 'done');
                    } else {
                        $recs[] = array('icon' => '&#129302;', 'title' => 'Verify llms.txt', 'desc' => 'Check llms.txt is accessible.', 'priority' => 'low', 'url' => home_url('/llms.txt'));
                    }

                    // === Monetization ===
                    $recs[] = array('icon' => '&#128176;', 'title' => 'Apply for Google AdSense', 'desc' => 'With 40+ posts, you qualify.', 'priority' => 'medium', 'url' => 'https://www.google.com/adsense/');

                    // === Next Sprint ===
                    $recs[] = array('icon' => '&#128640;', 'title' => 'Start Sprint 4: BuildCostGH', 'desc' => 'Cost calculator is your lead magnet.', 'priority' => 'medium', 'url' => admin_url('admin.php?page=gabochie-roadmap'));

                    // === Membership ===
                    if ($checks_cache['pmpro_installed']) {
                        $pmpro_status = !empty($checks_cache['pmpro_active']) ? 'Active' : 'Inactive';
                        $recs[] = array('icon' => '&#9989;', 'title' => 'Paid Memberships Pro ' . $pmpro_status, 'desc' => 'PMPro is installed. ' . $pmpro_status . '.', 'priority' => 'done');
                    } else {
                        $recs[] = array('icon' => '&#128101;', 'title' => 'Install Paid Memberships Pro', 'desc' => 'Install PMPro + Paystack for membership.', 'priority' => 'low', 'url' => admin_url('plugin-install.php?s=paid+memberships+pro&tab=plugin-information'));
                    }

                    // === Deployer Safety ===
                    if ($checks_cache['deployer_active']) {
                        $recs[] = array('icon' => '&#9888;&#65039;', 'title' => 'Deployer Active on page.php', 'desc' => 'Mini-deployer is still in page.php. Restore to normal template for safety.', 'priority' => 'high', 'url' => admin_url('admin.php?page=Wordfence'));
                    }

                    // Sort: high > medium > low > done
                    $priority_order = array('high' => 0, 'medium' => 1, 'low' => 2, 'done' => 3);
                    usort($recs, function($a, $b) use ($priority_order) {
                        return $priority_order[$a['priority']] - $priority_order[$b['priority']];
                    });

                    foreach ($recs as $r) :
                        if ($r['priority'] === 'done') {
                            $color = '#0D5C52';
                            $style = 'border-left:3px solid ' . $color . ';opacity:0.7;';
                        } else {
                            $color = $r['priority'] === 'high' ? '#d63638' : ($r['priority'] === 'medium' ? '#C9A96E' : '#0D5C52');
                            $style = 'border-left:3px solid ' . $color . ';';
                        }
                    ?>
                    <a href="<?php echo esc_url($r['url'] ?? '#'); ?>" target="_blank" class="gh-rec-item" style="<?php echo $style; ?>">
                        <span class="gh-rec-icon"><?php echo $r['icon']; ?></span>
                        <div class="gh-rec-body">
                            <strong><?php echo esc_html($r['title']); ?></strong>
                            <small><?php echo esc_html($r['desc']); ?></small>
                        </div>
                        <?php if ($r['priority'] !== 'done') : ?>
                        <span class="gh-rec-badge" style="background:<?php echo $color; ?>;">&#8599;</span>
                        <?php else : ?>
                        <span class="gh-rec-badge" style="background:<?php echo $color; ?>;">&#10003;</span>
                        <?php endif; ?>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php
    }
}
