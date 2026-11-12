<?php
/**
 * [memberlogin] Shortcode
 * শুধুমাত্র BatchMate মেম্বারদের জন্য কাস্টম লগইন ফর্ম।
 * লগইন সফল হলে → admin.php?page=batchmate_my_profile এ redirect।
 */
if ( ! defined( 'ABSPATH' ) ) exit;


/**
 * Password Reset Email Filter
 * retrieve_password() যে email পাঠায় তাতে wp-login.php URL কে
 * /memberlogin/ URL দিয়ে replace করে — শুধু BatchMate members এর জন্য।
 */
function BatchMate_filter_reset_password_email( $message, $key, $user_login, $user_data ) {
    global $wpdb;
    $is_member = $wpdb->get_var( $wpdb->prepare(
        "SELECT id FROM {$wpdb->prefix}batchmate_member WHERE wp_user_id = %d",
        $user_data->ID
    ) );
    if ( ! $is_member ) return $message;

    // WordPress এর default reset URL build করে replace করো
    $old_url = network_site_url(
        'wp-login.php?action=rp&key=' . $key . '&login=' . rawurlencode( $user_login ),
        'login'
    );
    $new_url = home_url(
        '/memberlogin/?action=rp&key=' . $key . '&login=' . rawurlencode( $user_login )
    );
    $message = str_replace( $old_url, $new_url, $message );

    return $message;
}
add_filter( 'retrieve_password_message', 'BatchMate_filter_reset_password_email', 10, 4 );


/**
 * Lost Password URL Filter
 * wp_lostpassword_url() রিটার্ন করবে /memberlogin/?action=lostpassword
 * ফলে /royallogin/ URL লিক হবে না।
 */
add_filter( 'lostpassword_url', function( $url, $redirect ) {
    return home_url( '/memberlogin/?action=lostpassword' );
}, 10, 2 );


function batchmate_member_login_shortcode() {

    /* ── লগইন করা থাকলে Dashboard দেখাও ── */
    if ( is_user_logged_in() ) {
        $user = wp_get_current_user();
        $is_admin = current_user_can( 'manage_options' );

        // Member data fetch
        global $wpdb;
        $member = $wpdb->get_row( $wpdb->prepare(
            "SELECT m.*, d.dept_name, p.program_name
             FROM {$wpdb->prefix}batchmate_member m
             LEFT JOIN {$wpdb->prefix}batchmate_program p ON m.program_id = p.id
             LEFT JOIN {$wpdb->prefix}batchmate_department d ON p.dept_id = d.id
             WHERE m.wp_user_id = %d",
            $user->ID
        ) );

        // URLs
        $no_photo  = ROYAL_BATCHMATE_PLUGIN_URL . '/assets/images/noprofile.png';
        // Member photo → WP avatar (Gravatar/custom) → noprofile fallback
        if ( $member && ! empty( $member->photo_url ) ) {
            $photo_url = $member->photo_url;
        } else {
            $wp_avatar = get_avatar_url( $user->ID, [ 'size' => 200, 'default' => '404' ] );
            $photo_url = ( $wp_avatar && strpos( $wp_avatar, '404' ) === false ) ? $wp_avatar : $no_photo;
        }
        $logout_url = wp_logout_url( home_url( '/memberlogin/' ) );

        if ( $is_admin ) {
            $dashboard_url   = admin_url();
            $dashboard_label = '&#128187; Go to Admin Panel';
            $profile_url     = '';
        } else {
            $dashboard_url   = admin_url( 'admin.php?page=batchmate_my_profile' );
            $dashboard_label = '&#9998; Edit My Profile';
            $profile_url     = $member ? home_url( '/member/' . strtolower( $member->member_number ) . '/' ) : '';
        }

        ob_start(); ?>
        <div class="BatchMate-li-wrap">
            <div class="BatchMate-li-card">

                <!-- ── Header gradient with photo ── -->
                <div class="BatchMate-li-header">
                    <div class="BatchMate-li-photo-ring">
                        <img src="<?php echo esc_url( $photo_url ); ?>"
                             alt="<?php echo esc_attr( $user->display_name ); ?>"
                             class="BatchMate-li-photo">
                    </div>
                    <?php if ( $is_admin ) : ?>
                    <span class="BatchMate-li-role-badge BatchMate-li-role-admin">&#128737; Administrator</span>
                    <?php else : ?>
                    <span class="BatchMate-li-role-badge BatchMate-li-role-member">&#127891; Member</span>
                    <?php endif; ?>
                </div>

                <!-- ── Name & Info ── -->
                <div class="BatchMate-li-body">
                    <h3 class="BatchMate-li-name"><?php echo esc_html( $user->display_name ); ?></h3>

                    <?php if ( $member && ! $is_admin ) : ?>
                    <p class="BatchMate-li-member-id"><?php echo esc_html( $member->member_number ); ?></p>
                    <div class="BatchMate-li-badges">
                        <?php if ( $member->dept_name ) : ?>
                        <span class="BatchMate-li-badge"><?php echo esc_html( $member->dept_name ); ?></span>
                        <?php endif; ?>
                        <?php if ( $member->program_name ) : ?>
                        <span class="BatchMate-li-badge"><?php echo esc_html( $member->program_name ); ?></span>
                        <?php endif; ?>
                        <?php if ( $member->batch ) : ?>
                        
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>

                    <!-- ── Action Buttons ── -->
                    <div class="BatchMate-li-actions">
                        <a href="<?php echo esc_url( $dashboard_url ); ?>" class="BatchMate-li-btn BatchMate-li-btn-primary">
                            <?php echo $dashboard_label; ?>
                        </a>
                        <?php if ( $profile_url ) : ?>
                        <a href="<?php echo esc_url( $profile_url ); ?>" class="BatchMate-li-btn BatchMate-li-btn-outline" target="_blank">
                            &#128065; View Public Profile
                        </a>
                        <?php endif; ?>
                        <a href="<?php echo esc_url( $logout_url ); ?>" class="BatchMate-li-btn BatchMate-li-btn-logout">
                            &#8594; Log Out
                        </a>
                    </div>
                </div>

            </div>
        </div>

        <style>
        .BatchMate-li-wrap {
            display: flex; justify-content: center; align-items: center;
            min-height: 50vh; padding: 24px;
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
        }
        .BatchMate-li-card {
            background: #fff;
            border-radius: 20px;
            box-shadow: 0 10px 50px rgba(26,58,107,.15), 0 2px 10px rgba(26,58,107,.07);
            width: 100%; max-width: 380px;
            overflow: hidden;
        }

        /* ── Header ── */
        .BatchMate-li-header {
            background: linear-gradient(135deg, #1a3a6b 0%, #2561a8 100%);
            padding: 36px 24px 54px;
            text-align: center;
            position: relative;
        }
        .BatchMate-li-photo-ring {
            width: 108px; height: 108px;
            border-radius: 50%;
            border: 4px solid rgba(255,255,255,.9);
            box-shadow: 0 4px 20px rgba(0,0,0,.25);
            overflow: hidden;
            margin: 0 auto;
            background: #e2e8f0;
        }
        .BatchMate-li-photo {
            width: 100%; height: 100%;
            object-fit: cover; display: block;
        }
        .BatchMate-li-role-badge {
            display: inline-block;
            margin-top: 14px;
            padding: 4px 14px;
            border-radius: 20px;
            font-size: 12px; font-weight: 600;
            letter-spacing: .3px;
        }
        .BatchMate-li-role-member { background: rgba(255,255,255,.22); color: #fff; border: 1px solid rgba(255,255,255,.4); }
        .BatchMate-li-role-admin  { background: #f59e0b; color: #1a1a1a; }

        /* ── Body ── */
        .BatchMate-li-body {
            padding: 28px 28px 24px;
            text-align: center;
            margin-top: -28px;
            position: relative;
            z-index: 1;
        }
        .BatchMate-li-name {
            margin: 0 0 4px;
            font-size: 20px; font-weight: 700;
            color: #1a3a6b; line-height: 1.3;
        }
        .BatchMate-li-member-id {
            margin: 0 0 12px;
            font-size: 13px; font-weight: 600;
            color: #2271b1; letter-spacing: .5px;
        }
        .BatchMate-li-badges {
            display: flex; flex-wrap: wrap;
            justify-content: center; gap: 6px;
            margin-bottom: 22px;
        }
        .BatchMate-li-badge {
            background: #f0f6fc; color: #1a3a6b;
            border: 1px solid #c8e1fa;
            padding: 3px 11px; border-radius: 20px;
            font-size: 11px; font-weight: 500;
        }

        /* ── Buttons ── */
        .BatchMate-li-actions {
            display: flex; flex-direction: column; gap: 10px;
        }
        .BatchMate-li-btn {
            display: block; padding: 13px 20px;
            border-radius: 10px; font-size: 14px; font-weight: 600;
            text-decoration: none; text-align: center;
            transition: opacity .2s, transform .15s;
            cursor: pointer;
        }
        .BatchMate-li-btn:hover { opacity: .88; transform: translateY(-1px); text-decoration: none; }
        .BatchMate-li-btn-primary { background: linear-gradient(135deg,#1a3a6b,#2271b1); color: #fff !important; }
        .BatchMate-li-btn-outline  { background: #f0f6fc; color: #2271b1 !important; border: 1.5px solid #c8e1fa; }
        .BatchMate-li-btn-logout   { background: #fff5f5; color: #c53030 !important; border: 1.5px solid #fed7d7; }

        @media (max-width: 420px) {
            .BatchMate-li-card { border-radius: 16px; }
            .BatchMate-li-body { padding: 24px 20px 20px; }
        }
        </style>
        <?php
        return ob_get_clean();
    }

    /* ── Forgot Password Form ──
     * /memberlogin/?action=lostpassword এ আসলে reset form দেখাবো
     */
    if ( isset( $_GET['action'] ) && $_GET['action'] === 'lostpassword' ) {
        $fp_nonce = wp_create_nonce( 'batchmate_member_lostpassword_nonce' );
        $login_url = home_url( '/memberlogin/' );
        ob_start(); ?>
        <div class="BatchMate-ml-wrapper">
            <div class="BatchMate-ml-card">
                <div class="BatchMate-ml-header">
                    <div class="BatchMate-ml-logo">&#128273;</div>
                    <h2>Reset Password</h2>
                    <p>Enter your username or email to receive a reset link.</p>
                </div>

                <div id="BatchMate-fp-msg" class="BatchMate-ml-error" style="display:none;"></div>
                <div id="BatchMate-fp-success" style="display:none; background:#f0fff4; border:1px solid #9ae6b4; color:#276749; border-radius:8px; padding:12px 16px; font-size:14px; margin-bottom:16px;"></div>

                <form id="BatchMate-forgot-form" autocomplete="off">
                    <input type="hidden" name="_wpnonce" value="<?php echo esc_attr( $fp_nonce ); ?>">
                    <div class="BatchMate-ml-field">
                        <label for="BatchMate-fp-login">&#128100; Username or Email</label>
                        <input type="text" id="BatchMate-fp-login" name="user_login"
                               placeholder="e.g. bm-00001" autocomplete="username" required>
                    </div>
                    <button type="submit" id="BatchMate-fp-submit" class="BatchMate-ml-submit">
                        <span class="BatchMate-fp-text">Send Reset Link</span>
                        <span class="BatchMate-fp-spinner" style="display:none;">&#8987;</span>
                    </button>
                </form>

                <p class="BatchMate-ml-footer-note">
                    <a href="<?php echo esc_url( $login_url ); ?>">&larr; Back to Login</a>
                </p>
            </div>
        </div>
        <style>
        .BatchMate-ml-wrapper { display:flex; justify-content:center; align-items:center; min-height:60vh; padding:20px; font-family:'Segoe UI',system-ui,-apple-system,sans-serif; }
        .BatchMate-ml-card { background:#fff; border-radius:18px; box-shadow:0 8px 40px rgba(30,58,95,.13),0 2px 8px rgba(30,58,95,.07); padding:44px 40px 36px; width:100%; max-width:420px; }
        .BatchMate-ml-header { text-align:center; margin-bottom:28px; }
        .BatchMate-ml-logo { font-size:48px; line-height:1; margin-bottom:10px; }
        .BatchMate-ml-header h2 { margin:0 0 4px; font-size:24px; font-weight:700; color:#1a3a6b; }
        .BatchMate-ml-header p { margin:0; color:#6c757d; font-size:14px; }
        .BatchMate-ml-field { margin-bottom:20px; }
        .BatchMate-ml-field label { display:block; margin-bottom:7px; font-size:13px; font-weight:600; color:#374151; }
        .BatchMate-ml-field input { width:100%; padding:12px 16px; border:1.5px solid #d1d5db; border-radius:9px; font-size:15px; color:#1d2327; background:#f9fafb; transition:border-color .2s,box-shadow .2s; box-sizing:border-box; }
        .BatchMate-ml-field input:focus { border-color:#2271b1; background:#fff; outline:none; box-shadow:0 0 0 3px rgba(34,113,177,.14); }
        .BatchMate-ml-submit { width:100%; padding:13px; background:linear-gradient(135deg,#1a3a6b 0%,#2271b1 100%); color:#fff; border:none; border-radius:9px; font-size:16px; font-weight:700; cursor:pointer; display:flex; align-items:center; justify-content:center; gap:8px; transition:opacity .2s; }
        .BatchMate-ml-submit:hover:not(:disabled) { opacity:.9; }
        .BatchMate-ml-submit:disabled { opacity:.65; cursor:not-allowed; }
        .BatchMate-ml-error { background:#fff5f5; border:1px solid #fed7d7; color:#c53030; border-radius:8px; padding:10px 14px; font-size:13px; margin-bottom:16px; }
        .BatchMate-ml-footer-note { text-align:center; margin-top:20px; font-size:13px; color:#9ca3af; }
        .BatchMate-ml-footer-note a { color:#2271b1; text-decoration:none; }
        </style>
        <script>
        jQuery(document).ready(function($) {
            $('#BatchMate-forgot-form').on('submit', function(e) {
                e.preventDefault();
                var $btn     = $('#BatchMate-fp-submit');
                var $errBox  = $('#BatchMate-fp-msg');
                var $success = $('#BatchMate-fp-success');
                $errBox.hide(); $success.hide();
                $btn.prop('disabled', true);
                $btn.find('.BatchMate-fp-text').text('Sending...');
                $btn.find('.BatchMate-fp-spinner').show();

                $.ajax({
                    url:  '<?php echo esc_js( admin_url('admin-ajax.php') ); ?>',
                    type: 'POST',
                    data: {
                        action:     'batchmate_member_lostpassword',
                        _wpnonce:   $('[name="_wpnonce"]').val(),
                        user_login: $('#BatchMate-fp-login').val()
                    },
                    success: function(res) {
                        if (res.success) {
                            $('#BatchMate-forgot-form').hide();
                            $success.html('&#10003; ' + res.data).show();
                        } else {
                            $errBox.html(res.data).show();
                            $btn.prop('disabled', false);
                            $btn.find('.BatchMate-fp-text').text('Send Reset Link');
                            $btn.find('.BatchMate-fp-spinner').hide();
                        }
                    },
                    error: function() {
                        $errBox.html('Connection error. Please try again.').show();
                        $btn.prop('disabled', false);
                        $btn.find('.BatchMate-fp-text').text('Send Reset Link');
                        $btn.find('.BatchMate-fp-spinner').hide();
                    }
                });
            });
        });
        </script>
        <?php
        return ob_get_clean();
    }

    /* ── Password Reset Redirect ──
     * Email link → /memberlogin/?action=rp&key=...&login=...
     * এখান থেকে WP এর actual reset form (wp-login.php) এ পাঠাই
     */
    if (
        isset( $_GET['action'] ) && $_GET['action'] === 'rp' &&
        isset( $_GET['key'], $_GET['login'] )
    ) {
        $rp_key   = sanitize_text_field( wp_unslash( $_GET['key'] ) );
        $rp_login = sanitize_text_field( wp_unslash( $_GET['login'] ) );
        $rp_url   = network_site_url(
            'wp-login.php?action=rp&key=' . rawurlencode( $rp_key ) . '&login=' . rawurlencode( $rp_login ),
            'login'
        );
        // JS দিয়ে redirect (shortcode context এ headers ইতিমধ্যে sent)
        return '<script>window.location.replace(' . json_encode( $rp_url ) . ');</script>
                <noscript><meta http-equiv="refresh" content="0;url=' . esc_attr( $rp_url ) . '"></noscript>';
    }

    /* ── লগইন ফর্ম ── */
    $nonce       = wp_create_nonce( 'batchmate_member_login_nonce' );
    $ajax_url    = admin_url( 'admin-ajax.php' );
    $forgot_url  = wp_lostpassword_url( home_url( '/memberlogin/' ) );

    ob_start(); ?>
    <div class="BatchMate-ml-wrapper">
        <div class="BatchMate-ml-card">

            <div class="BatchMate-ml-header">
                <div class="BatchMate-ml-logo">&#127891;</div>
                <h2>Member Login</h2>
                <p><?php echo batchmate_org_label( 'short' ); ?></p>
            </div>

            <form id="BatchMate-member-login-form" autocomplete="off" novalidate>
                <input type="hidden" name="action"   value="batchmate_member_login">
                <input type="hidden" name="_wpnonce" value="<?php echo esc_attr( $nonce ); ?>">

                <div class="BatchMate-ml-field">
                    <label for="BatchMate-ml-username">&#128100; Username</label>
                    <input type="text" id="BatchMate-ml-username" name="username"
                           placeholder="e.g. bm-00001" autocomplete="username" required>
                </div>

                <div class="BatchMate-ml-field">
                    <label for="BatchMate-ml-password">&#128274; Password</label>
                    <div class="BatchMate-ml-pass-wrap">
                        <input type="password" id="BatchMate-ml-password" name="password"
                               placeholder="Your password" autocomplete="current-password" required>
                        <button type="button" class="BatchMate-ml-toggle-pass" tabindex="-1" aria-label="Show/Hide password">
                            <span class="dashicons dashicons-visibility"></span>
                        </button>
                    </div>
                </div>

                <div class="BatchMate-ml-row-flex">
                    <label class="BatchMate-ml-remember">
                        <input type="checkbox" name="rememberme" value="forever"> Remember me
                    </label>
                    <a href="<?php echo esc_url( $forgot_url ); ?>" class="BatchMate-ml-forgot">Forgot password?</a>
                </div>

                <div id="BatchMate-ml-error" class="BatchMate-ml-error" style="display:none;"></div>

                <button type="submit" id="BatchMate-ml-submit" class="BatchMate-ml-submit">
                    <span class="BatchMate-ml-submit-text">Log In</span>
                    <span class="BatchMate-ml-spinner" style="display:none;">&#8987;</span>
                </button>
            </form>

            <p class="BatchMate-ml-footer-note">
                <?php
                $login_settings = get_option('batchmate_settings', []);
                $login_contact  = ! empty( $login_settings['org_email'] ) ? $login_settings['org_email'] : '';
                if ( $login_contact ) :
                ?>
                Having trouble? Contact the <a href="mailto:<?php echo esc_attr($login_contact); ?>">admin</a>.
                <?php else : ?>
                Having trouble? Please contact the admin.
                <?php endif; ?>
            </p>
        </div>
    </div>

    <style>
    /* ── Wrapper ── */
    .BatchMate-ml-wrapper {
        display: flex; justify-content: center; align-items: center;
        min-height: 60vh; padding: 20px;
        font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
    }
    .BatchMate-ml-card {
        background: #fff;
        border-radius: 18px;
        box-shadow: 0 8px 40px rgba(30,58,95,.13), 0 2px 8px rgba(30,58,95,.07);
        padding: 44px 40px 36px;
        width: 100%; max-width: 420px;
    }
    /* ── Header ── */
    .BatchMate-ml-header { text-align: center; margin-bottom: 32px; }
    .BatchMate-ml-logo { font-size: 48px; line-height: 1; margin-bottom: 10px; }
    .BatchMate-ml-header h2 {
        margin: 0 0 4px; font-size: 26px; font-weight: 700;
        color: #1a3a6b; letter-spacing: -.3px;
    }
    .BatchMate-ml-header p { margin: 0; color: #6c757d; font-size: 14px; }

    /* ── Fields ── */
    .BatchMate-ml-field { margin-bottom: 18px; }
    .BatchMate-ml-field label {
        display: block; margin-bottom: 7px;
        font-size: 13px; font-weight: 600; color: #374151;
    }
    .BatchMate-ml-field input[type="text"],
    .BatchMate-ml-field input[type="password"] {
        width: 100%; padding: 12px 16px;
        border: 1.5px solid #d1d5db; border-radius: 9px;
        font-size: 15px; color: #1d2327;
        background: #f9fafb;
        transition: border-color .2s, box-shadow .2s;
        box-sizing: border-box;
    }
    .BatchMate-ml-field input:focus {
        border-color: #2271b1; background: #fff;
        outline: none; box-shadow: 0 0 0 3px rgba(34,113,177,.14);
    }
    .BatchMate-ml-pass-wrap { position: relative; }
    .BatchMate-ml-pass-wrap input { padding-right: 44px; }
    .BatchMate-ml-toggle-pass {
        position: absolute; right: 12px; top: 50%; transform: translateY(-50%);
        background: none; border: none; cursor: pointer; padding: 0;
        color: #9ca3af; line-height: 1;
    }
    .BatchMate-ml-toggle-pass:hover { color: #2271b1; }
    .BatchMate-ml-toggle-pass .dashicons { font-size: 18px; width: 18px; height: 18px; }

    /* ── Remember / Forgot ── */
    .BatchMate-ml-row-flex {
        display: flex; justify-content: space-between; align-items: center;
        margin-bottom: 22px; font-size: 13px;
    }
    .BatchMate-ml-remember { display: flex; align-items: center; gap: 6px; color: #4b5563; cursor: pointer; }
    .BatchMate-ml-remember input { margin: 0; width: 15px; height: 15px; }
    .BatchMate-ml-forgot { color: #2271b1; text-decoration: none; font-weight: 500; }
    .BatchMate-ml-forgot:hover { text-decoration: underline; }

    /* ── Error ── */
    .BatchMate-ml-error {
        background: #fff5f5; border: 1px solid #fed7d7;
        color: #c53030; border-radius: 8px;
        padding: 10px 14px; font-size: 13px;
        margin-bottom: 16px; line-height: 1.5;
    }

    /* ── Submit ── */
    .BatchMate-ml-submit {
        width: 100%; padding: 13px;
        background: linear-gradient(135deg, #1a3a6b 0%, #2271b1 100%);
        color: #fff; border: none; border-radius: 9px;
        font-size: 16px; font-weight: 700;
        cursor: pointer; letter-spacing: .3px;
        transition: opacity .2s, transform .15s;
        display: flex; align-items: center; justify-content: center; gap: 8px;
    }
    .BatchMate-ml-submit:hover:not(:disabled) { opacity: .9; transform: translateY(-1px); }
    .BatchMate-ml-submit:disabled { opacity: .65; cursor: not-allowed; }

    /* ── Footer note ── */
    .BatchMate-ml-footer-note { text-align: center; margin-top: 20px; font-size: 13px; color: #9ca3af; }
    .BatchMate-ml-footer-note a { color: #2271b1; }

    /* ── Logged-in view ── */
    .BatchMate-ml-logged-in {
        max-width: 360px; margin: 0 auto;
        background: #fff; border-radius: 16px;
        box-shadow: 0 4px 20px rgba(30,58,95,.1);
        padding: 36px 30px; text-align: center;
    }
    .BatchMate-ml-avatar-img { border-radius: 50%; border: 3px solid #2271b1; }
    .BatchMate-ml-greeting { margin: 16px 0 20px; font-size: 16px; color: #374151; line-height: 1.6; }
    .BatchMate-ml-greeting strong { color: #1a3a6b; font-size: 18px; }
    .BatchMate-ml-actions { display: flex; flex-direction: column; gap: 10px; }
    .BatchMate-ml-btn {
        display: block; padding: 12px 20px; border-radius: 9px;
        font-size: 14px; font-weight: 600; text-decoration: none;
        transition: opacity .2s, transform .15s;
    }
    .BatchMate-ml-btn:hover { opacity: .88; transform: translateY(-1px); text-decoration: none; }
    .BatchMate-ml-btn-primary  { background: linear-gradient(135deg,#1a3a6b,#2271b1); color: #fff; }
    .BatchMate-ml-btn-secondary { background: #f0f6fc; color: #2271b1; border: 1.5px solid #c8e1fa; }
    .BatchMate-ml-btn-logout   { background: #fff5f5; color: #c53030; border: 1.5px solid #fed7d7; }

    @media (max-width: 480px) {
        .BatchMate-ml-card { padding: 32px 20px 26px; }
    }
    </style>

    <script>
    (function($) {
        $(document).ready(function() {

            /* ── Show/Hide Password ── */
            $('.BatchMate-ml-toggle-pass').on('click', function() {
                var $inp = $(this).siblings('input');
                var $icon = $(this).find('.dashicons');
                if ($inp.attr('type') === 'password') {
                    $inp.attr('type', 'text');
                    $icon.removeClass('dashicons-visibility').addClass('dashicons-hidden');
                } else {
                    $inp.attr('type', 'password');
                    $icon.removeClass('dashicons-hidden').addClass('dashicons-visibility');
                }
            });

            /* ── Login Form Submit ── */
            $('#BatchMate-member-login-form').on('submit', function(e) {
                e.preventDefault();

                var $btn     = $('#BatchMate-ml-submit');
                var $errBox  = $('#BatchMate-ml-error');
                var $text    = $btn.find('.BatchMate-ml-submit-text');
                var $spinner = $btn.find('.BatchMate-ml-spinner');

                $errBox.hide();
                $btn.prop('disabled', true);
                $text.text('Logging in…');
                $spinner.show();

                $.ajax({
                    url:  '<?php echo esc_js( $ajax_url ); ?>',
                    type: 'POST',
                    data: $(this).serialize(),
                    success: function(res) {
                        if (res.success) {
                            $text.text('Redirecting…');
                            window.location.href = res.data.redirect;
                        } else {
                            $errBox.html(res.data).show();
                            $btn.prop('disabled', false);
                            $text.text('Log In');
                            $spinner.hide();
                        }
                    },
                    error: function() {
                        $errBox.html('Connection error. Please try again.').show();
                        $btn.prop('disabled', false);
                        $text.text('Log In');
                        $spinner.hide();
                    }
                });
            });
        });
    })(jQuery);
    </script>
    <?php
    return ob_get_clean();
}
add_shortcode( 'memberlogin', 'batchmate_member_login_shortcode' );


/**
 * AJAX Handler: Member Login
 */
function batchmate_member_login_ajax() {
    check_ajax_referer( 'batchmate_member_login_nonce' );

    $username   = sanitize_text_field( $_POST['username'] ?? '' );
    $password   = $_POST['password'] ?? '';
    $rememberme = ! empty( $_POST['rememberme'] );

    if ( empty( $username ) || empty( $password ) ) {
        wp_send_json_error( 'Username and password are required.' );
    }

    $user = wp_authenticate( $username, $password );

    if ( is_wp_error( $user ) ) {
        // WordPress এর এরর মেসেজ থেকে ভালো মেসেজ দেওয়া
        $code = $user->get_error_code();
        if ( in_array( $code, [ 'invalid_username', 'invalid_email', 'incorrect_password' ] ) ) {
            wp_send_json_error( 'Invalid username or password. Please try again.' );
        }
        wp_send_json_error( $user->get_error_message() );
    }

    // Member role check — শুধু batchmate_member role লগইন করতে পারবে
    if (
        ! in_array( 'batchmate_member', (array) $user->roles, true ) &&
        ! user_can( $user, 'manage_options' )
    ) {
        wp_send_json_error( 'Access denied. This login is for BatchMate members only.' );
    }

    // লগইন করো
    wp_set_current_user( $user->ID );
    wp_set_auth_cookie( $user->ID, $rememberme );

    // Redirect URL নির্ধারণ
    if ( user_can( $user, 'manage_options' ) ) {
        $redirect = admin_url();
    } else {
        $redirect = admin_url( 'admin.php?page=batchmate_my_profile' );
    }

    wp_send_json_success( [ 'redirect' => $redirect ] );
}
add_action( 'wp_ajax_nopriv_puaa_member_login', 'batchmate_member_login_ajax' );
add_action( 'wp_ajax_batchmate_member_login', 'batchmate_member_login_ajax' );
add_action( 'wp_ajax_puaa_member_login',       'batchmate_member_login_ajax' );


/**
 * AJAX Handler: Forgot Password (Lost Password)
 * WP এর retrieve_password() কল করে — আমাদের email filter দিয়ে
 * reset link এ /memberlogin/ URL যাবে।
 */
function batchmate_member_lostpassword_ajax() {
    check_ajax_referer( 'batchmate_member_lostpassword_nonce' );

    $user_login = sanitize_text_field( wp_unslash( $_POST['user_login'] ?? '' ) );
    if ( empty( $user_login ) ) {
        wp_send_json_error( 'Please enter your username or email address.' );
    }

    // User exist কিনা চেক (মেম্বার হতে হবে)
    if ( is_email( $user_login ) ) {
        $user = get_user_by( 'email', $user_login );
    } else {
        $user = get_user_by( 'login', $user_login );
    }

    if ( ! $user ) {
        wp_send_json_error( 'No account found with that username or email.' );
    }

    // Member role check
    if (
        ! in_array( 'batchmate_member', (array) $user->roles, true ) &&
        ! user_can( $user, 'manage_options' )
    ) {
        wp_send_json_error( 'No member account found with that username or email.' );
    }

    // WP এর built-in retrieve_password() কল — আমাদের filter email URL ঠিক করবে
    $result = retrieve_password( $user->user_login );

    if ( is_wp_error( $result ) ) {
        wp_send_json_error( $result->get_error_message() );
    }

    wp_send_json_success( 'Password reset link has been sent to your email address. Please check your inbox.' );
}
add_action( 'wp_ajax_nopriv_puaa_member_lostpassword', 'batchmate_member_lostpassword_ajax' );
add_action( 'wp_ajax_batchmate_member_lostpassword', 'batchmate_member_lostpassword_ajax' );
add_action( 'wp_ajax_puaa_member_lostpassword',       'batchmate_member_lostpassword_ajax' );
