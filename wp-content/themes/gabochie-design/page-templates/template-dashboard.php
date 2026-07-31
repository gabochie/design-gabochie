<?php
/**
 * Template Name: Client Dashboard
 * @package Gabochie_Design
 */

if (!is_user_logged_in()) {
    wp_redirect(home_url('/login/'));
    exit;
}

get_header();
$user = wp_get_current_user();
?>

<section class="page-hero" style="padding-bottom:3rem">
    <div class="container">
        <div class="breadcrumb"><a href="<?php echo esc_url(home_url('/')); ?>">Home</a> / Dashboard</div>
        <h1>Welcome, <em><?php echo esc_html($user->display_name ?: $user->user_login); ?></em></h1>
        <p>Your project hub — track progress, review designs, and stay in the loop.</p>
    </div>
</section>

<section style="padding:0 2rem 6rem;margin-top:-2rem">
    <div style="max-width:1100px;margin:0 auto">

        <?php
        $user_id = get_current_user_id();
        $projects = get_posts(array(
            'post_type'      => 'client_project',
            'author'         => $user_id,
            'posts_per_page' => 1,
            'orderby'        => 'date',
            'order'          => 'DESC',
        ));
        $latest_project = !empty($projects) ? $projects[0] : null;

        if ($latest_project) {
            $project_status = get_post_meta($latest_project->ID, 'project_status', true);
            $project_stage  = get_post_meta($latest_project->ID, 'project_stage', true);
            $next_milestone = get_post_meta($latest_project->ID, 'next_milestone', true);
            $next_date      = get_post_meta($latest_project->ID, 'next_milestone_date', true);
            $status_labels  = function_exists('gabochie_project_statuses') ? gabochie_project_statuses() : array();
            $stage_labels   = function_exists('gabochie_project_stages') ? gabochie_project_stages() : array();

            $status_display = $status_labels[$project_status] ?? ucfirst($project_status ?: 'In Progress');
            $stage_display  = $stage_labels[$project_stage] ?? ($project_stage ?: 'Design Development');

            $message_count = 0;
            if (function_exists('gabochie_project_statuses')) {
                $messages = get_posts(array(
                    'post_type'      => 'project_message',
                    'posts_per_page' => -1,
                    'meta_query'     => array(
                        array('key' => 'project_id', 'value' => $latest_project->ID, 'compare' => '='),
                    ),
                    'fields' => 'ids',
                ));
                $message_count = count($messages);
            }
        } else {
            $status_display = 'No Project';
            $stage_display  = '—';
            $next_milestone = '—';
            $next_date      = '';
            $message_count  = 0;
        }
        ?>

        <!-- Quick Stats -->
        <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:1.5rem;margin-bottom:3rem">
            <div style="background:var(--cream);padding:1.5rem;border-radius:var(--r-lg);border:1px solid var(--border-light)">
                <div style="font-size:0.75rem;letter-spacing:0.08em;text-transform:uppercase;color:var(--mist);margin-bottom:0.5rem">Project Status</div>
                <div style="font-size:1.5rem;font-weight:700;color:var(--teal)"><?php echo esc_html($status_display); ?></div>
                <div style="font-size:0.8rem;color:var(--mist);margin-top:0.25rem">Stage: <?php echo esc_html($stage_display); ?></div>
            </div>
            <div style="background:var(--cream);padding:1.5rem;border-radius:var(--r-lg);border:1px solid var(--border-light)">
                <div style="font-size:0.75rem;letter-spacing:0.08em;text-transform:uppercase;color:var(--mist);margin-bottom:0.5rem">Next Milestone</div>
                <div style="font-size:1.5rem;font-weight:700;color:var(--gold)"><?php echo esc_html($next_date ?: $next_milestone ?: '—'); ?></div>
                <div style="font-size:0.8rem;color:var(--mist);margin-top:0.25rem"><?php echo esc_html($next_milestone ?: 'No upcoming milestone'); ?></div>
            </div>
            <div style="background:var(--cream);padding:1.5rem;border-radius:var(--r-lg);border:1px solid var(--border-light)">
                <div style="font-size:0.75rem;letter-spacing:0.08em;text-transform:uppercase;color:var(--mist);margin-bottom:0.5rem">Unread Messages</div>
                <div style="font-size:1.5rem;font-weight:700;color:var(--charcoal)"><?php echo esc_html($message_count); ?></div>
                <div style="font-size:0.8rem;color:var(--mist);margin-top:0.25rem">From your design team</div>
            </div>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:2rem">

            <!-- Recent Activity -->
            <div style="background:var(--ivory);padding:2rem;border-radius:var(--r-lg);border:1px solid var(--border-light)">
                <h3 style="font-size:1.2rem;margin-bottom:1.5rem">Recent Activity</h3>
                <div style="display:flex;flex-direction:column;gap:1rem">
                    <?php
                    $all_projects = get_posts(array(
                        'post_type'      => 'client_project',
                        'author'         => $user_id,
                        'posts_per_page' => 5,
                        'orderby'        => 'modified',
                        'order'          => 'DESC',
                    ));
                    $recent_messages = get_posts(array(
                        'post_type'      => 'project_message',
                        'author'         => $user_id,
                        'posts_per_page' => 3,
                        'orderby'        => 'date',
                        'order'          => 'DESC',
                    ));
                    $activities = array();

                    foreach ($all_projects as $p) {
                        $p_status = get_post_meta($p->ID, 'project_status', true);
                        $activities[] = array(
                            'title'   => $p->post_title,
                            'desc'    => 'Status: ' . ($status_labels[$p_status] ?? $p_status ?: 'Active'),
                            'date'    => $p->post_modified,
                            'color'   => 'var(--teal)',
                        );
                    }
                    foreach ($recent_messages as $m) {
                        $activities[] = array(
                            'title'   => $m->post_title,
                            'desc'    => wp_trim_words($m->post_content, 10),
                            'date'    => $m->post_date,
                            'color'   => 'var(--gold)',
                        );
                    }
                    usort($activities, function ($a, $b) { return strtotime($b['date']) - strtotime($a['date']); });
                    $activities = array_slice($activities, 0, 5);

                    if (empty($activities)) : ?>
                        <div style="font-size:0.85rem;color:var(--mist)">No recent activity yet.</div>
                    <?php else : foreach ($activities as $act) :
                        $time_diff = human_time_diff(strtotime($act['date']), current_time('timestamp'));
                    ?>
                    <div style="display:flex;gap:0.75rem;font-size:0.85rem">
                        <div style="width:8px;height:8px;background:<?php echo $act['color']; ?>;border-radius:50%;margin-top:0.4rem;flex-shrink:0"></div>
                        <div><div style="font-weight:500"><?php echo esc_html($act['title']); ?></div><div style="color:var(--mist)"><?php echo esc_html($act['desc']); ?></div><div style="color:var(--mist);font-size:0.75rem;margin-top:0.2rem"><?php echo esc_html($time_diff); ?> ago</div></div>
                    </div>
                    <?php endforeach; endif; ?>
                </div>
            </div>

            <!-- Quick Actions -->
            <div style="background:var(--ivory);padding:2rem;border-radius:var(--r-lg);border:1px solid var(--border-light)">
                <h3 style="font-size:1.2rem;margin-bottom:1.5rem">Quick Actions</h3>
                <div style="display:flex;flex-direction:column;gap:0.75rem">
                    <a href="mailto:<?php echo esc_attr(get_theme_mod('company_email', 'admin@gabochiedesign.com')); ?>?subject=Project%20Question" style="display:flex;align-items:center;gap:0.75rem;padding:0.85rem 1rem;background:var(--cream);border-radius:var(--r-sm);text-decoration:none;color:var(--charcoal);font-size:0.9rem;transition:background 0.2s">
                        <span style="font-size:1.2rem">✉️</span>
                        <span>Message Your Design Team</span>
                    </a>
                    <a href="<?php echo esc_url(home_url('/contact/')); ?>" style="display:flex;align-items:center;gap:0.75rem;padding:0.85rem 1rem;background:var(--cream);border-radius:var(--r-sm);text-decoration:none;color:var(--charcoal);font-size:0.9rem;transition:background 0.2s">
                        <span style="font-size:1.2rem">📅</span>
                        <span>Schedule a Check-In</span>
                    </a>
                    <a href="<?php echo esc_url(home_url('/contact/')); ?>" style="display:flex;align-items:center;gap:0.75rem;padding:0.85rem 1rem;background:var(--cream);border-radius:var(--r-sm);text-decoration:none;color:var(--charcoal);font-size:0.9rem;transition:background 0.2s">
                        <span style="font-size:1.2rem">📁</span>
                        <span>Request Document / Revision</span>
                    </a>
                    <a href="<?php echo esc_url(home_url('/pricing/')); ?>" style="display:flex;align-items:center;gap:0.75rem;padding:0.85rem 1rem;background:var(--cream);border-radius:var(--r-sm);text-decoration:none;color:var(--charcoal);font-size:0.9rem;transition:background 0.2s">
                        <span style="font-size:1.2rem">💳</span>
                        <span>View Payment Plan</span>
                    </a>
                </div>
            </div>

        </div>

        <!-- Referral / Conversion Bar -->
        <div style="margin-top:3rem;background:linear-gradient(135deg,var(--teal-dark),#0d3d3d);padding:2.5rem;border-radius:var(--r-lg);text-align:center;color:#FAF7F2">
            <h3 style="font-size:1.5rem;margin-bottom:0.75rem;color:#FAF7F2">Know Someone Building Their Dream Space?</h3>
            <p style="font-size:0.95rem;opacity:0.9;margin-bottom:1.5rem;max-width:600px;margin-left:auto;margin-right:auto">Refer a friend and both receive <strong style="color:var(--gold)">10% off</strong> your next project phase.</p>
            <a href="mailto:<?php echo esc_attr(get_theme_mod('company_email', 'admin@gabochiedesign.com')); ?>?subject=Referral%20from%20<?php echo esc_attr($user->display_name ?: $user->user_login); ?>" style="display:inline-block;padding:0.85rem 2.5rem;background:var(--gold);color:var(--teal-dark);text-decoration:none;border-radius:var(--r-sm);font-weight:600;font-size:0.85rem;letter-spacing:0.05em">Refer a Friend</a>
        </div>

    </div>
</section>

<?php get_footer(); ?>
