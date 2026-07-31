<?php
if (!defined('ABSPATH')) exit;
$remote_url = get_option('gabochie_sync_remote_url', '');
$remote_username = get_option('gabochie_sync_remote_username', '');
$api_key = get_option('gabochie_sync_api_key', '');
$git_repo_path = get_option('gabochie_sync_git_repo_path', ABSPATH);
$git_remote = get_option('gabochie_sync_git_remote', 'origin');
$git_branch = get_option('gabochie_sync_git_branch', 'main');
$sync_post_types = get_option('gabochie_sync_sync_post_types', array('post', 'page'));
$last_sync = get_option('gabochie_sync_last_sync', '');
$log = get_option('gabochie_sync_log', array());
$log = array_reverse(array_slice($log, -20));

$git = new Gabochie_Git_Deploy();
$git_status = $git->get_status();
?>
<div class="wrap gabochie-sync-wrap">
    <h1>Gabochie Sync</h1>
    <p class="description">Push/pull content and code between local development and live production.</p>

    <!-- Status Bar -->
    <div class="gs-status-bar">
        <div class="gs-status-item">
            <span class="gs-dot <?php echo $status === 'connected' ? 'gs-dot-green' : ($status === 'error' ? 'gs-dot-red' : 'gs-dot-gray'); ?>"></span>
            <span>Remote: <?php echo $status === 'connected' ? 'Connected' : ($status === 'error' ? 'Error' : 'Not Configured'); ?></span>
        </div>
        <div class="gs-status-item">
            <span class="gs-dot <?php echo $git_status['available'] ? 'gs-dot-green' : 'gs-dot-gray'; ?>"></span>
            <span>Git: <?php echo $git_status['available'] ? 'Available' : 'Not Installed'; ?></span>
        </div>
        <div class="gs-status-item">
            <span>Last Sync: <?php echo $last_sync ? date('M j, g:i A', strtotime($last_sync)) : 'Never'; ?></span>
        </div>
    </div>

    <!-- Action Buttons -->
    <div class="gs-actions">
        <div class="gs-action-group">
            <h2>Content Sync</h2>
            <p class="description">Sync posts, pages, and settings between environments.</p>
            <div class="gs-buttons">
                <button type="button" class="button button-primary gs-btn gs-push-btn" data-action="push">
                    &#8593; Push to Live
                </button>
                <button type="button" class="button button-secondary gs-btn gs-pull-btn" data-action="pull">
                    &#8595; Pull from Live
                </button>
                <button type="button" class="button button-secondary gs-btn gs-dry-run-btn" data-action="dry-run">
                    &#128269; Dry Run
                </button>
                <button type="button" class="button button-secondary gs-btn gs-diff-btn" data-action="diff">
                    &#9881; Show Changes
                </button>
            </div>
        </div>

        <div class="gs-action-group">
            <h2>Git Deploy</h2>
            <p class="description">Deploy code via Git. Run on the <strong>live server</strong> to pull.</p>
            <div class="gs-buttons">
                <button type="button" class="button button-secondary gs-btn gs-deploy-pull-btn" data-action="git-pull">
                    &#8595; Git Pull (Remote)
                </button>
                <button type="button" class="button button-secondary gs-btn gs-deploy-push-btn" data-action="git-push">
                    &#8593; Git Push (Local)
                </button>
            </div>
            <?php if ($git_status['is_repo']) : ?>
            <div class="gs-git-info">
                <p>Branch: <code><?php echo esc_html($git_status['branch']); ?></code></p>
                <p>Remote: <code><?php echo esc_html($git_status['remote_url']); ?></code></p>
                <?php if ($git_status['has_changes']) : ?>
                    <p class="gs-warning"><?php echo $git_status['uncommitted']; ?> uncommitted change(s)</p>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Progress -->
    <div class="gs-progress-wrap" style="display:none">
        <div class="gs-progress-bar">
            <div class="gs-progress-fill"></div>
        </div>
        <p class="gs-progress-text">Working...</p>
    </div>

    <!-- Result -->
    <div class="gs-result" style="display:none"></div>

    <!-- Diff Panel -->
    <div class="gs-diff-panel" style="display:none">
        <h2>Content Differences</h2>
        <div class="gs-diff-content"></div>
    </div>

    <!-- Log -->
    <div class="gs-log-section">
        <h2>Sync Log <small>(last 20)</small></h2>
        <?php if (empty($log)) : ?>
            <p class="description">No sync operations yet.</p>
        <?php else : ?>
        <table class="widefat gs-log-table">
            <thead>
                <tr>
                    <th>Time</th>
                    <th>Action</th>
                    <th>Status</th>
                    <th>Message</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($log as $entry) : ?>
                <tr class="gs-log-<?php echo esc_attr($entry['status']); ?>">
                    <td><?php echo esc_html(date('M j, g:i A', strtotime($entry['time']))); ?></td>
                    <td><code><?php echo esc_html($entry['action']); ?></code></td>
                    <td><span class="gs-badge gs-badge-<?php echo esc_attr($entry['status']); ?>"><?php echo esc_html($entry['status']); ?></span></td>
                    <td><?php echo esc_html(wp_trim_words($entry['message'], 30)); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>

    <!-- Settings -->
    <div class="gs-settings-section">
        <h2>Settings</h2>
        <form method="post">
            <?php wp_nonce_field('gabochie_sync_save_action'); ?>

            <h3>Remote Connection</h3>
            <table class="form-table">
                <tr>
                    <th><label for="remote_url">Remote Site URL</label></th>
                    <td>
                        <input type="url" id="remote_url" name="remote_url" value="<?php echo esc_attr($remote_url); ?>" class="regular-text" placeholder="https://gabochiedesign.com" />
                        <p class="description">The live WordPress site URL.</p>
                    </td>
                </tr>
                <tr>
                    <th><label for="remote_username">Remote WP Username</label></th>
                    <td>
                        <input type="text" id="remote_username" name="remote_username" value="<?php echo esc_attr($remote_username); ?>" class="regular-text" placeholder="e.g. admin" />
                        <p class="description">WordPress admin username on the remote site.</p>
                    </td>
                </tr>
                <tr>
                    <th><label for="api_key">Remote Application Password</label></th>
                    <td>
                        <input type="password" id="api_key" name="api_key" value="<?php echo esc_attr($api_key); ?>" class="regular-text" placeholder="xxxx xxxx xxxx xxxx xxxx xxxx" />
                        <p class="description">The remote site's WordPress Application Password (from Users → Profile → Application Passwords).</p>
                    </td>
                </tr>
            </table>

            <h3>Local API Key</h3>
            <table class="form-table">
                <tr>
                    <th>This Site's API Key</th>
                    <td>
                        <code style="background:#f1f1f1;padding:6px 12px;font-size:13px;display:inline-block;min-width:300px;"><?php echo esc_html(get_option('gabochie_sync_local_api_key', 'Not generated yet')); ?></code>
                        <p class="description">Enter this key on the <strong>remote site</strong> as their "Remote API Key".</p>
                    </td>
                </tr>
            </table>

            <h3>Git Configuration</h3>
            <table class="form-table">
                <tr>
                    <th><label for="git_repo_path">Repository Path</label></th>
                    <td>
                        <input type="text" id="git_repo_path" name="git_repo_path" value="<?php echo esc_attr($git_repo_path); ?>" class="regular-text" />
                        <p class="description">Absolute path to the WordPress installation on the server.</p>
                    </td>
                </tr>
                <tr>
                    <th><label for="git_remote">Git Remote</label></th>
                    <td>
                        <input type="text" id="git_remote" name="git_remote" value="<?php echo esc_attr($git_remote); ?>" class="regular-text" placeholder="origin" />
                    </td>
                </tr>
                <tr>
                    <th><label for="git_branch">Git Branch</label></th>
                    <td>
                        <input type="text" id="git_branch" name="git_branch" value="<?php echo esc_attr($git_branch); ?>" class="regular-text" placeholder="main" />
                    </td>
                </tr>
            </table>

            <h3>Content Types to Sync</h3>
            <table class="form-table">
                <tr>
                    <th>Post Types</th>
                    <td>
                        <?php
                        $all_post_types = get_post_types(array('public' => true), 'objects');
                        foreach ($all_post_types as $pt) :
                        ?>
                        <label style="display:inline-block;margin-right:15px;margin-bottom:5px;">
                            <input type="checkbox" name="sync_post_types[]" value="<?php echo esc_attr($pt->name); ?>"
                                <?php checked(in_array($pt->name, $sync_post_types)); ?> />
                            <?php echo esc_html($pt->labels->singular_name); ?>
                        </label>
                        <?php endforeach; ?>
                    </td>
                </tr>
            </table>

            <?php submit_button('Save Settings', 'primary', 'gabochie_sync_save'); ?>
        </form>
    </div>
</div>
