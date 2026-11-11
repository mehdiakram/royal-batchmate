<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * --- AJAX HANDLER: Get Institutes Summary ---
 */
function batchmate_get_institutes_ajax() {
    check_ajax_referer( 'BatchMate_app_nonce' );
    if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Permission denied.', 403 );

    global $wpdb;
    
    // Read from batchmate_application where all records are stored initially
    $app_table = $wpdb->prefix . 'batchmate_application';
    
    // Group by institution
    // We count approved and pending applications per institution
    $query = "
        SELECT 
            COALESCE(institution, 'N/A') as institution,
            SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved_count,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_count
        FROM $app_table
        WHERE institution IS NOT NULL AND TRIM(institution) != ''
        GROUP BY institution
        ORDER BY approved_count DESC, pending_count DESC
    ";

    $results = $wpdb->get_results( $query );

    wp_send_json( array( 'data' => $results ? $results : [] ) );
}
add_action( 'wp_ajax_BatchMate_get_institutes', 'batchmate_get_institutes_ajax' );
add_action( 'wp_ajax_puaa_get_institutes',      'batchmate_get_institutes_ajax' );

/**
 * --- AJAX HANDLER: Merge Institutes ---
 */
function batchmate_merge_institutes_ajax() {
    check_ajax_referer( 'BatchMate_app_nonce' );
    if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Permission denied.', 403 );

    if ( empty( $_POST['old_name'] ) || empty( $_POST['new_name'] ) ) {
        wp_send_json_error( 'Both old and new names are required.' );
    }

    global $wpdb;
    $old_name = sanitize_text_field( $_POST['old_name'] );
    $new_name = sanitize_text_field( $_POST['new_name'] );

    $app_table = $wpdb->prefix . 'batchmate_application';
    $member_table = $wpdb->prefix . 'batchmate_member';

    // Update in both tables
    $wpdb->update( $app_table, array('institution' => $new_name), array('institution' => $old_name), array('%s'), array('%s') );
    $wpdb->update( $member_table, array('institution' => $new_name), array('institution' => $old_name), array('%s'), array('%s') );

    wp_send_json_success( 'Institutes merged successfully.' );
}
add_action( 'wp_ajax_BatchMate_merge_institutes', 'batchmate_merge_institutes_ajax' );
add_action( 'wp_ajax_puaa_merge_institutes',      'batchmate_merge_institutes_ajax' );
