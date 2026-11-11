<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

function BatchMate_generate_financial_report_ajax() {
    // Note: Since report_nonce is not explicitly used/defined, we check capability only.
    if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Permission denied.', 403 );

    global $wpdb;
    
    $report_type = sanitize_key( $_POST['report_type'] );
    $start_date = sanitize_text_field( $_POST['start_date'] );
    $end_date = sanitize_text_field( $_POST['end_date'] );

    $report_file_map = [
        'financial-summary'     => 'financial-summary.php',
        'collection-monthly'    => 'collection-monthly.php',
        'collection-details'    => 'collection-details.php',
        'expense'               => 'expense.php',
    ];

    if ( ! isset($report_file_map[$report_type]) ) {
        wp_send_json_error( 'Invalid report type selected.' );
    }

    $report_file = ROYAL_BATCHMATE_PLUGIN_DIR . '/includes/reports/' . $report_file_map[$report_type];

    if ( ! file_exists( $report_file ) ) {
        wp_send_json_error( 'Report file not found: ' . $report_type );
    }

    // Define variables that the report files can access
    $start_date = $start_date;
    $end_date = $end_date;
    
    // Start output buffering to capture the report file content
    ob_start();
    include $report_file;
    $content = ob_get_clean();

    wp_send_json_success( array( 'html' => $content ) );
}
add_action( 'wp_ajax_BatchMate_generate_financial_report', 'BatchMate_generate_financial_report_ajax' );
add_action( 'wp_ajax_puaa_generate_financial_report',       'BatchMate_generate_financial_report_ajax' );