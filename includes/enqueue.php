<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Royal BatchMate - Scripts & Styles Enqueue
 */
// ENQUEUE: Admin scripts
// ============================================================
add_action( 'admin_enqueue_scripts', 'batchmate_load_admin_scripts' );
function batchmate_load_admin_scripts( string $hook_suffix ): void {
    global $wp_version;
    $plugin_url = ROYAL_BATCHMATE_PLUGIN_URL;

    if ( strpos( $hook_suffix, 'batchmate_manage_settings' ) !== false || strpos( $hook_suffix, 'batchmate_manage_events' ) !== false || strpos( $hook_suffix, 'batchmate_test_event_card' ) !== false || strpos( $hook_suffix, 'batchmate_test_birthday' ) !== false ) {
        wp_enqueue_media();
    }

    if ( strpos( $hook_suffix, 'BatchMate' ) === false && strpos( $hook_suffix, 'batchmate' ) === false ) {
        return;
    }

    wp_enqueue_style(  'admin-css',         $plugin_url . 'assets/admin.css' );
    if ( strpos( $hook_suffix, 'batchmate_manage_events' ) !== false ) {
        wp_enqueue_style( 'batchmate-admin-events-css', $plugin_url . 'assets/admin-events.css', [ 'admin-css' ], ROYAL_BATCHMATE_VERSION );
    }
    if ( strpos( $hook_suffix, 'batchmate_manage_settings' ) !== false ) {
        wp_enqueue_style( 'batchmate-admin-settings-css', $plugin_url . 'assets/admin-settings.css', [ 'admin-css' ], ROYAL_BATCHMATE_VERSION );
    }
    wp_enqueue_style(  'sweetalert2-css',   $plugin_url . 'assets/sweetalert2/sweetalert2.min.css' );
    wp_enqueue_script( 'sweetalert2-js',    $plugin_url . 'assets/sweetalert2/sweetalert2.all.min.js',  [], $wp_version, true );
    wp_enqueue_style(  'datatables-css',    $plugin_url . 'assets/datatables/datatables.min.css' );
    wp_enqueue_script( 'datatables-js',     $plugin_url . 'assets/datatables/datatables.min.js',        [], $wp_version, true );
    wp_enqueue_style(  'select2-css',       $plugin_url . 'assets/select2/select2.min.css' );
    wp_enqueue_script( 'select2-js',        $plugin_url . 'assets/select2/select2.min.js',             [ 'jquery' ], $wp_version, true );

    if ( strpos( $hook_suffix, 'batchmate_dashboard' ) !== false || $hook_suffix === 'toplevel_page_puua_member_management' ) {
        wp_enqueue_script( 'chart-js', $plugin_url . 'assets/chart/chart.js', [], '4.5.0', true );
    }

    if (
        strpos( $hook_suffix, 'batchmate_manage_payments' ) !== false ||
        strpos( $hook_suffix, 'batchmate_manage_expenses' ) !== false ||
        strpos( $hook_suffix, 'batchmate_executive_committee' ) !== false
    ) {
        wp_enqueue_script( 'moment-js', $plugin_url . 'assets/moment/moment.min.js', [], '2.29.1', true );
    }

    wp_localize_script( 'datatables-js', 'batchmate_admin_ajax', [
        'ajax_url'          => admin_url( 'admin-ajax.php' ),
        'dept_nonce'        => wp_create_nonce( 'BatchMate_dept_nonce' ),
        'prog_nonce'        => wp_create_nonce( 'BatchMate_prog_nonce' ),
        'app_nonce'         => wp_create_nonce( 'BatchMate_app_nonce' ),
        'member_nonce'      => wp_create_nonce( 'batchmate_member_nonce' ),
        'ec_nonce'          => wp_create_nonce( 'batchmate_ec_nonce' ),
        'payment_nonce'     => wp_create_nonce( 'BatchMate_payment_nonce' ),
        'expense_nonce'     => wp_create_nonce( 'BatchMate_expense_nonce' ),
        'settings_nonce'    => wp_create_nonce( 'BatchMate_settings_nonce' ),
        'event_nonce'       => wp_create_nonce( 'batchmate_event_nonce' ),
        'member_self_nonce' => wp_create_nonce( 'batchmate_member_self_nonce' ),
    ] );

    if ( strpos( $hook_suffix, 'batchmate_manage_events' ) !== false ) {
        wp_enqueue_script( 'batchmate-admin-events-js', $plugin_url . 'assets/admin-events.js', [ 'jquery', 'datatables-js', 'sweetalert2-js', 'select2-js' ], ROYAL_BATCHMATE_VERSION, true );
    }
    if ( strpos( $hook_suffix, 'batchmate_manage_settings' ) !== false ) {
        wp_enqueue_script( 'batchmate-admin-settings-js', $plugin_url . 'assets/admin-settings.js', [ 'jquery', 'sweetalert2-js' ], ROYAL_BATCHMATE_VERSION, true );
    }
}

// ============================================================
// ENQUEUE: Public scripts
// ============================================================
add_action( 'wp_enqueue_scripts', 'BatchMate_load_public_scripts' );
function BatchMate_load_public_scripts(): void {
    $plugin_url = ROYAL_BATCHMATE_PLUGIN_URL;

    // Enqueue Select2 for frontend (event forms, member directory, registration)
    wp_enqueue_style(  'select2-css', $plugin_url . 'assets/select2/select2.min.css', [], '4.1.0' );
    wp_enqueue_script( 'select2-js',  $plugin_url . 'assets/select2/select2.min.js', [ 'jquery' ], '4.1.0', false );

    // Enqueue SweetAlert2 for frontend (event registration validation popups, notifications)
    wp_enqueue_style(  'sweetalert2-css', $plugin_url . 'assets/sweetalert2/sweetalert2.min.css', [], '11.0.0' );
    wp_enqueue_script( 'sweetalert2-js',  $plugin_url . 'assets/sweetalert2/sweetalert2.all.min.js', [], '11.0.0', false );

    if ( is_page_template( 'template-members.php' ) ) {
        wp_enqueue_style(  'datatables-css', $plugin_url . 'assets/datatables/datatables.min.css' );
        wp_enqueue_script( 'datatables-js',  $plugin_url . 'assets/datatables/datatables.min.js', [ 'jquery' ], '1.11.3', true );
        wp_localize_script( 'datatables-js', 'BatchMate_public_ajax', [
            'ajax_url' => admin_url( 'admin-ajax.php' ),
        ] );
    }
}

// ============================================================
// CHILD THEME STYLE (still needed for CSS vars)
// ============================================================
add_action( 'wp_enqueue_scripts', 'batchmate_plugin_enqueue_style' );
function batchmate_plugin_enqueue_style(): void {
    wp_enqueue_style( 'royal-batchmate', ROYAL_BATCHMATE_PLUGIN_URL . 'assets/style.css', [], ROYAL_BATCHMATE_VERSION );

    $s = get_option( 'batchmate_settings', [] );
    $bc_bg_type = $s['bc_bg_type'] ?? 'color';
    $bc_bg_color = $s['bc_bg_color'] ?? '#263b71';
    $bc_bg_image = $s['bc_bg_image'] ?? '';
    $bc_text_color = $s['bc_text_color'] ?? '#000000';
    $bc_font_size = intval( $s['bc_font_size'] ?? 32 );

    $custom_css = "
        :root {
            --breadcrumb_text_color: " . esc_attr( $bc_text_color ) . ";
            --breadcrumb_bg_color: " . esc_attr( $bc_bg_color ) . ";
        }
        .breadcrumb .pagetitle {
            font-size: " . esc_attr( $bc_font_size ) . "px !important;
            color: var(--breadcrumb_text_color) !important;
        }
    ";
    
    if ( ( $bc_bg_type === 'image' || $bc_bg_type === 'both' ) && ! empty( $bc_bg_image ) ) {
        $custom_css .= "
            .breadcrumb {
                background-image: url('" . esc_url( $bc_bg_image ) . "');
                background-size: cover;
                background-position: center;
                /* Optional blend mode if both are used */
                background-blend-mode: multiply;
            }
        ";
        if ( $bc_bg_type === 'image' ) {
            $custom_css .= "
                .breadcrumb::after {
                    border-top-color: transparent !important;
                }
            ";
        }
    }

    wp_add_inline_style( 'royal-batchmate', $custom_css );
}

// ============================================================

add_action('admin_footer', function() {
    ?>
    <script>
    jQuery(document).ready(function($) {
        const observer = new MutationObserver((mutations) => {
            let anyOpen = false;
            $('[id$="-overlay"], [id*="-modal-overlay"], .BatchMate-modal-overlay').each(function() {
                if ($(this).is(':visible')) {
                    anyOpen = true;
                }
            });
            if (anyOpen) {
                $('body').addClass('batchmate-modal-open');
            } else {
                $('body').removeClass('batchmate-modal-open');
            }
        });
        observer.observe(document.body, { childList: true, subtree: true, attributes: true, attributeFilter: ['style', 'class'] });
    });
    </script>
    <?php
});