<?php
/**
 * Royal BatchMate — License Admin Page
 * AJAX-based activation with SweetAlert2 feedback.
 * All user-facing strings are i18n-ready via text domain 'royal-batchmate'.
 */
if ( ! defined( 'ABSPATH' ) ) exit;
if ( ! current_user_can( 'manage_options' ) ) wp_die( __( 'Unauthorized', 'royal-batchmate' ) );

// Enqueue SweetAlert2
wp_enqueue_style(
    'rbm-swal-css',
    ROYAL_BATCHMATE_PLUGIN_URL . 'assets/sweetalert2/sweetalert2.min.css',
    [],
    ROYAL_BATCHMATE_VERSION
);
wp_enqueue_script(
    'rbm-swal-js',
    ROYAL_BATCHMATE_PLUGIN_URL . 'assets/sweetalert2/sweetalert2.all.min.js',
    [ 'jquery' ],
    ROYAL_BATCHMATE_VERSION,
    true
);

// Deactivation status notice
$status       = isset( $_GET['status'] ) ? sanitize_key( $_GET['status'] ) : '';
$admin_notice = '';
if ( $status === 'deactivated' ) {
    $admin_notice = '<div class="notice notice-warning is-dismissible"><p>'
        . esc_html__( 'License deactivated successfully.', 'royal-batchmate' )
        . '</p></div>';
}

$info      = royal_batchmate_get_license_info();
$is_active = ( $info['status'] === 'active' && $info['valid'] );
$nonce     = wp_create_nonce( 'rbm_license_nonce' );
$ajaxurl   = admin_url( 'admin-ajax.php' );
?>
<div class="BatchMate-settings-card">
    <div class="BatchMate-card-head">
        <span class="dashicons dashicons-lock"></span>
        <h3>License Management</h3>
    </div>
    <div class="BatchMate-card-body">

    <?php echo $admin_notice; ?>

    <?php if ( ! $is_active ) : ?>
    <div style="background:#fff3cd; border-left:4px solid #ffc107; padding:16px 20px; margin:16px 0; border-radius:4px; display:flex; align-items:center; gap:12px;">
        <span style="font-size:28px;">🔒</span>
        <div>
            <strong style="font-size:15px;"><?php esc_html_e( 'License Not Active', 'royal-batchmate' ); ?></strong><br>
            <span style="color:#666;"><?php esc_html_e( 'To use all plugin features, please activate your license key below.', 'royal-batchmate' ); ?></span>
        </div>
    </div>
    <?php endif; ?>

    <div style="max-width:640px; margin-top:20px;">

        <!-- Current Status Table -->
        <table class="widefat" style="margin-bottom:20px;">
            <thead>
                <tr><th colspan="2"><?php esc_html_e( 'License Status', 'royal-batchmate' ); ?></th></tr>
            </thead>
            <tbody>
                <tr>
                    <td><strong><?php esc_html_e( 'Status', 'royal-batchmate' ); ?></strong></td>
                    <td>
                        <?php if ( $is_active ) : ?>
                            <span style="color:green;">&#x2705; <?php esc_html_e( 'Active', 'royal-batchmate' ); ?></span>
                        <?php else : ?>
                            <span style="color:red;">&#x274C; <?php esc_html_e( 'Inactive', 'royal-batchmate' ); ?></span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php if ( $info['key'] ) : ?>
                <tr>
                    <td><strong><?php esc_html_e( 'License Key', 'royal-batchmate' ); ?></strong></td>
                    <td>
                        <code><?php
                            $k = $info['key'];
                            echo esc_html( substr( $k, 0, 5 ) . str_repeat( '*', max( 0, strlen( $k ) - 10 ) ) . substr( $k, -5 ) );
                        ?></code>
                    </td>
                </tr>
                <tr>
                    <td><strong><?php esc_html_e( 'Type', 'royal-batchmate' ); ?></strong></td>
                    <td><?php echo esc_html( ucfirst( $info['type'] ) ); ?></td>
                </tr>
                <tr>
                    <td><strong><?php esc_html_e( 'Domain', 'royal-batchmate' ); ?></strong></td>
                    <td><?php echo esc_html( $info['domain'] ); ?></td>
                </tr>
                <tr>
                    <td><strong><?php esc_html_e( 'Expires', 'royal-batchmate' ); ?></strong></td>
                    <td><?php echo $info['expires_at'] ? esc_html( $info['expires_at'] ) : '<em>' . esc_html__( 'Never (Lifetime)', 'royal-batchmate' ) . '</em>'; ?></td>
                </tr>
                <tr>
                    <td><strong><?php esc_html_e( 'Last Synced', 'royal-batchmate' ); ?></strong></td>
                    <td><?php echo esc_html( $info['synced_at'] ?: __( 'Never', 'royal-batchmate' ) ); ?></td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- Activate / Deactivate Box -->
        <div style="background:#fff; border:1px solid #ccd0d4; padding:24px; border-radius:6px;">
            <?php if ( ! $is_active ) : ?>
            <h3 style="margin-top:0;">
                <?php esc_html_e( 'Activate License', 'royal-batchmate' ); ?>
            </h3>

            <div id="rbm-license-activation-form">
                <table class="form-table">
                    <tr>
                        <th><label for="rbm_license_key_input"><?php esc_html_e( 'License Key', 'royal-batchmate' ); ?></label></th>
                        <td>
                            <input
                                type="text"
                                id="rbm_license_key_input"
                                name="rbm_license_key"
                                value="<?php echo esc_attr( $info['key'] ); ?>"
                                class="regular-text"
                                placeholder="XXXX-XXXX-XXXX-XXXX"
                                autocomplete="off"
                            />
                        </td>
                    </tr>
                </table>
                <p class="submit">
                    <button type="button" id="rbm-activate-btn" class="button button-primary">
                        <?php esc_html_e( 'Activate License', 'royal-batchmate' ); ?>
                    </button>
                </p>
            </div>
            <?php else : ?>
            <h3 style="margin-top:0;">
                <?php esc_html_e( 'License Management', 'royal-batchmate' ); ?>
            </h3>
            <form method="POST" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                <input type="hidden" name="action" value="rbm_deactivate_license">
                <?php wp_nonce_field( 'rbm_license_deactivation_nonce' ); ?>
                <button type="submit" class="button button-secondary"
                    onclick="return confirm('<?php echo esc_js( __( 'Are you sure you want to deactivate the license?', 'royal-batchmate' ) ); ?>');">
                    <?php esc_html_e( 'Deactivate License', 'royal-batchmate' ); ?>
                </button>
                <p class="description" style="margin-top:8px;">
                    <?php esc_html_e( 'Deactivating will disable plugin features until reactivated.', 'royal-batchmate' ); ?>
                </p>
            </form>
            <?php endif; ?>
        </div>

    </div>
</div>
</div>

<script>
jQuery(document).ready(function($) {
    function rbmShowMsg(icon, title, text, cb) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: icon,
                title: title,
                text: text,
                timer: (icon === 'success' ? 1500 : undefined),
                showConfirmButton: (icon !== 'success'),
                allowOutsideClick: false
            }).then(function() {
                if (cb) cb();
            });
        } else {
            alert(title + (text ? "\n" + text : ""));
            if (cb) cb();
        }
    }

    function rbmDoActivate() {
        var $btn   = $("#rbm-activate-btn");
        var $input = $("#rbm_license_key_input");
        var key    = $input.val().trim();

        if (!key) {
            rbmShowMsg('warning', 'Required', 'Please enter a license key.');
            $input.focus();
            return;
        }

        $btn.prop("disabled", true).text("Activating...");
        $input.prop("readonly", true);

        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Activating License...',
                html: 'Please wait a moment.',
                allowOutsideClick: false,
                allowEscapeKey: false,
                didOpen: function() { Swal.showLoading(); }
            });
        }

        $.ajax({
            url: <?php echo json_encode( $ajaxurl ); ?>,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'rbm_activate_license',
                nonce: <?php echo json_encode( $nonce ); ?>,
                license_key: key
            },
            success: function(res) {
                if (res && res.success) {
                    rbmShowMsg('success', 'Success!', (res.data && res.data.message) ? res.data.message : 'License activated successfully.', function() {
                        window.location.reload();
                    });
                } else {
                    $btn.prop("disabled", false).text("Activate License");
                    $input.prop("readonly", false);
                    var msg = (res && res.data && res.data.message) ? res.data.message : 'Activation failed. Please check the license key.';
                    rbmShowMsg('error', 'Activation Failed', msg);
                }
            },
            error: function(xhr, status, error) {
                $btn.prop("disabled", false).text("Activate License");
                $input.prop("readonly", false);
                var errTxt = 'Could not reach the license server. Please try again.';
                if (xhr.responseText) {
                    try {
                        var parsed = JSON.parse(xhr.responseText);
                        if (parsed && parsed.data && parsed.data.message) errTxt = parsed.data.message;
                    } catch(e) {}
                }
                rbmShowMsg('error', 'Error', errTxt);
            }
        });
    }

    $(document).on('click', '#rbm-activate-btn', function(e) {
        e.preventDefault();
        rbmDoActivate();
    });

    $(document).on('keydown', '#rbm_license_key_input', function(e) {
        if (e.key === 'Enter' || e.keyCode === 13) {
            e.preventDefault();
            rbmDoActivate();
        }
    });
});
</script>
