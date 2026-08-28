<?php
/**
 * Gabochie Anti-Spam — admin settings + log review.
 *
 * Settings page under Settings → Anti-Spam. Shows config, stats, and a
 * paginated log table with mark ham/spam/delete actions.
 *
 * @package Gabochie_Antispam
 */

defined( 'ABSPATH' ) || exit;

final class Gabochie_Antispam_Admin {

    private static $instance = null;

    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action( 'admin_menu', array( $this, 'register_menu' ), 25 );
        add_action( 'admin_init', array( $this, 'save_settings' ) );
        add_action( 'wp_ajax_gabochie_antispam_action', array( $this, 'ajax_action' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_css' ) );
    }

    /* ------------------------------------------------------------------ */
    /*  Menu                                                              */
    /* ------------------------------------------------------------------ */

    public function register_menu() {
        add_options_page(
            'Gabochie Anti-Spam',
            'Anti-Spam',
            'manage_options',
            'gabochie-antispam',
            array( $this, 'render_page' )
        );
    }

    /* ------------------------------------------------------------------ */
    /*  Save settings                                                     */
    /* ------------------------------------------------------------------ */

    public function save_settings() {
        if ( ! isset( $_POST['gabochie_as_save'] ) ) {
            return;
        }
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }
        check_admin_referer( 'gabochie_as_settings' );

        $settings = array(
            'sensitivity' => sanitize_text_field( wp_unslash( $_POST['sensitivity'] ?? 'normal' ) ),
            'ai_enabled'  => ! empty( $_POST['ai_enabled'] ) ? 1 : 0,
            'ai_cap_hour' => max( 1, min( 200, (int) ( $_POST['ai_cap_hour'] ?? 30 ) ) ),
            'honeypot'    => ! empty( $_POST['honeypot'] ) ? 1 : 0,
            'retention'   => max( 7, min( 365, (int) ( $_POST['retention'] ?? 30 ) ) ),
        );

        update_option( 'gabochie_antispam_settings', $settings );
        add_settings_error( 'gabochie_as', 'saved', 'Settings saved.', 'updated' );
    }

    /* ------------------------------------------------------------------ */
    /*  AJAX mark ham / spam / delete                                     */
    /* ------------------------------------------------------------------ */

    public function ajax_action() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => 'Unauthorized' ), 403 );
        }
        check_ajax_referer( 'gabochie_as_action', 'nonce' );

        $id     = (int) ( $_POST['id'] ?? 0 );
        $action = sanitize_text_field( wp_unslash( $_POST['as_action'] ?? '' ) );
        $db     = Gabochie_Antispam_DB::instance();

        if ( ! $id ) {
            wp_send_json_error( array( 'message' => 'Invalid ID' ), 400 );
        }

        switch ( $action ) {
            case 'ham':
                $db->update_verdict( $id, 'ham' );
                wp_send_json_success( array( 'message' => 'Marked as ham' ) );
                break;
            case 'spam':
                $db->update_verdict( $id, 'spam' );
                wp_send_json_success( array( 'message' => 'Marked as spam' ) );
                break;
            case 'delete':
                $db->delete( $id );
                wp_send_json_success( array( 'message' => 'Deleted' ) );
                break;
            default:
                wp_send_json_error( array( 'message' => 'Unknown action' ), 400 );
        }
    }

    /* ------------------------------------------------------------------ */
    /*  Admin CSS                                                         */
    /* ------------------------------------------------------------------ */

    public function enqueue_admin_css( $hook ) {
        if ( $hook !== 'settings_page_gabochie-antispam' ) {
            return;
        }
        echo '<style>
.gas-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:1rem;margin:1rem 0}
.gas-card{background:#fff;border:1px solid #ccc;border-radius:6px;padding:1rem 1.25rem}
.gas-card .num{font-size:1.6rem;font-weight:600;color:#072E28}
.gas-card .label{font-size:.75rem;color:#666;margin-top:4px}
.gas-log{width:100%;border-collapse:collapse;margin-top:1rem}
.gas-log th,.gas-log td{text-align:left;padding:8px 10px;border-bottom:1px solid #eee;font-size:.85rem}
.gas-log th{background:#f9f9f9;font-weight:600}
.gas-verdict-spam{color:#d32f2f;font-weight:600}
.gas-verdict-borderline{color:#ed6c02;font-weight:600}
.gas-verdict-ham{color:#2e7d32}
.gas-actions button{margin-right:4px}
        </style>';
    }

    /* ------------------------------------------------------------------ */
    /*  Render                                                            */
    /* ------------------------------------------------------------------ */

    public function render_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }
        $settings = gabochie_antispam_settings();
        $stats    = Gabochie_Antispam_DB::instance()->stats( 24 );
        $log      = Gabochie_Antispam_DB::instance()->recent( 100 );

        ?>
        <div class="wrap">
            <h1>Gabochie Anti-Spam</h1>

            <!-- Stats -->
            <div class="gas-grid">
                <div class="gas-card">
                    <div class="num"><?php echo esc_html( $stats['spam_24h'] ); ?></div>
                    <div class="label">Spam blocked (24h)</div>
                </div>
                <div class="gas-card">
                    <div class="num"><?php echo esc_html( $stats['borderline_24h'] ); ?></div>
                    <div class="label">Borderline (24h)</div>
                </div>
                <div class="gas-card">
                    <div class="num"><?php echo esc_html( $stats['ham_24h'] ); ?></div>
                    <div class="label">Ham (24h)</div>
                </div>
                <div class="gas-card">
                    <div class="num"><?php echo esc_html( $stats['ai_24h'] ); ?></div>
                    <div class="label">AI classified (24h)</div>
                </div>
                <div class="gas-card">
                    <div class="num"><?php echo esc_html( $stats['total'] ); ?></div>
                    <div class="label">Total log rows</div>
                </div>
            </div>

            <?php settings_errors( 'gabochie_as' ); ?>

            <!-- Settings -->
            <form method="post" style="max-width:600px;margin:2rem 0">
                <?php wp_nonce_field( 'gabochie_as_settings' ); ?>
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="sensitivity">Sensitivity</label></th>
                        <td>
                            <select id="sensitivity" name="sensitivity">
                                <?php foreach ( array( 'strict' => 'Strict — blocks more aggressively', 'normal' => 'Normal — balanced (recommended)', 'lenient' => 'Lenient — only high-confidence spam' ) as $val => $lbl ) : ?>
                                    <option value="<?php echo esc_attr( $val ); ?>" <?php selected( $settings['sensitivity'], $val ); ?>><?php echo esc_html( $lbl ); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <p class="description">Controls the score thresholds for ham / borderline / spam classification.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="ai_enabled">AI second opinion</label></th>
                        <td>
                            <label><input type="checkbox" id="ai_enabled" name="ai_enabled" value="1" <?php checked( $settings['ai_enabled'] ); ?>> Enable GhBC LLM classification on borderline submissions</label>
                            <p class="description">Requires the GhBC plugin to be active with a configured LLM provider.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="ai_cap_hour">AI rate cap</label></th>
                        <td>
                            <input type="number" id="ai_cap_hour" name="ai_cap_hour" min="1" max="200" value="<?php echo esc_attr( $settings['ai_cap_hour'] ); ?>">
                            <p class="description">Maximum LLM calls per hour. Prevents runaway costs if traffic spikes.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="honeypot">Honeypot</label></th>
                        <td>
                            <label><input type="checkbox" id="honeypot" name="honeypot" value="1" <?php checked( $settings['honeypot'] ); ?>> Inject a hidden honeypot field into all forms + comment form</label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="retention">Log retention</label></th>
                        <td>
                            <input type="number" id="retention" name="retention" min="7" max="365" value="<?php echo esc_attr( $settings['retention'] ); ?>"> days
                        </td>
                    </tr>
                </table>
                <p class="submit">
                    <button type="submit" name="gabochie_as_save" class="button button-primary">Save Settings</button>
                </p>
            </form>

            <!-- Log -->
            <h2>Submission Log</h2>
            <?php if ( empty( $log ) ) : ?>
                <p>No submissions recorded yet.</p>
            <?php else : ?>
                <table class="gas-log widefat">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Time</th>
                            <th>Source</th>
                            <th>Verdict</th>
                            <th>Score</th>
                            <th>Author</th>
                            <th>Email</th>
                            <th>IP</th>
                            <th>Reasons</th>
                            <th>AI</th>
                            <th>Content</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ( $log as $row ) : ?>
                            <tr id="gas-row-<?php echo esc_attr( $row->id ); ?>">
                                <td><?php echo esc_html( $row->id ); ?></td>
                                <td><?php echo esc_html( $row->created ); ?></td>
                                <td><?php echo esc_html( $row->source ); ?></td>
                                <td class="gas-verdict-<?php echo esc_attr( $row->verdict ); ?>">
                                    <?php echo esc_html( $row->verdict ); ?>
                                </td>
                                <td><?php echo esc_html( $row->score ); ?></td>
                                <td><?php echo esc_html( mb_substr( $row->name, 0, 30 ) ); ?></td>
                                <td><?php echo esc_html( $row->email ); ?></td>
                                <td><?php echo esc_html( $row->ip ); ?></td>
                                <td><?php echo esc_html( implode( ', ', $row->reasons ) ); ?></td>
                                <td><?php echo $row->ai ? '✓' : ''; ?></td>
                                <td><em><?php echo esc_html( mb_substr( wp_strip_all_tags( $row->content ), 0, 80 ) ); ?></em></td>
                                <td class="gas-actions">
                                    <?php if ( $row->verdict !== 'ham' ) : ?>
                                        <button class="button button-small gas-btn" data-id="<?php echo esc_attr( $row->id ); ?>" data-action="ham">Allow</button>
                                    <?php endif; ?>
                                    <?php if ( $row->verdict !== 'spam' ) : ?>
                                        <button class="button button-small gas-btn" data-id="<?php echo esc_attr( $row->id ); ?>" data-action="spam">Spam</button>
                                    <?php endif; ?>
                                    <button class="button button-small button-link-delete gas-btn" data-id="<?php echo esc_attr( $row->id ); ?>" data-action="delete">Delete</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <script>
                document.querySelectorAll('.gas-btn').forEach(function(btn){
                    btn.addEventListener('click',function(){
                        var row=document.getElementById('gas-row-'+this.dataset.id);
                        if(!row)return;
                        row.style.opacity='0.4';
                        var data=new FormData();
                        data.append('action','gabochie_antispam_action');
                        data.append('nonce','<?php echo wp_create_nonce('gabochie_as_action'); ?>');
                        data.append('id',this.dataset.id);
                        data.append('as_action',this.dataset.action);
                        fetch('<?php echo admin_url('admin-ajax.php'); ?>',{method:'POST',body:data})
                            .then(function(r){return r.json();})
                            .then(function(res){
                                if(res.success){row.style.background='#fff3cd';row.style.opacity='1';}
                                else{row.style.opacity='1';alert(res.data&&res.data.message?res.data.message:'Error');}
                            })
                            .catch(function(){row.style.opacity='1';});
                    });
                });
                </script>
            <?php endif; ?>
        </div>
        <?php
    }
}

Gabochie_Antispam_Admin::instance();