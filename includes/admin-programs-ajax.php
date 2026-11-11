<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * --- AJAX HANDLER: Get Programs ---
 */
function BatchMate_get_programs_ajax() {
    check_ajax_referer( 'BatchMate_prog_nonce' );
    if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Permission denied.', 403 );

    global $wpdb;
    $program_table = $wpdb->prefix . 'batchmate_program';
    $dept_table = $wpdb->prefix . 'batchmate_department';
    $member_table = $wpdb->prefix . 'batchmate_member'; 

    $programs = $wpdb->get_results( $wpdb->prepare(
        "SELECT p.id, p.program_name, p.short_code, p.dept_id, p.is_active, d.dept_name, COUNT(DISTINCT m.id) as member_count
         FROM %i p
         LEFT JOIN %i d ON p.dept_id = d.id
         LEFT JOIN %i m ON p.id = GROUP BY p.id
         ORDER BY p.id DESC",
         $program_table, $dept_table, $member_table
    ) );

    wp_send_json( array( 'data' => $programs ? $programs : [] ) );
}
add_action( 'wp_ajax_BatchMate_get_programs', 'BatchMate_get_programs_ajax' );
add_action( 'wp_ajax_puaa_get_programs',       'BatchMate_get_programs_ajax' );

/**
 * --- AJAX HANDLER: Get Departments for Dropdown (Updated to return ALL) ---
 */
function BatchMate_get_departments_for_dropdown_ajax() {
    check_ajax_referer( 'BatchMate_dept_nonce' );
    if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Permission denied.', 403 );
    
    global $wpdb;
    $table_name = $wpdb->prefix . 'batchmate_department';
    
    // FIX: Return ALL departments for admin pages (Member Edit), including the status.
    $departments = $wpdb->get_results( "SELECT id, dept_name, is_active FROM $table_name ORDER BY dept_name ASC" );
    
    wp_send_json_success( $departments ? $departments : [] );
}
add_action( 'wp_ajax_BatchMate_get_departments_for_dropdown', 'BatchMate_get_departments_for_dropdown_ajax' );
add_action( 'wp_ajax_puaa_get_departments_for_dropdown',       'BatchMate_get_departments_for_dropdown_ajax' );

/**
 * --- AJAX HANDLER: Save Program ---
 */
function BatchMate_save_program_ajax() {
    check_ajax_referer( 'BatchMate_prog_nonce' );
    if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Permission denied.', 403 );
    if ( empty( $_POST['program_name'] ) || empty( $_POST['dept_id'] ) ) wp_send_json_error( 'Required fields missing.' );

    global $wpdb;
    $table_name = $wpdb->prefix . 'batchmate_program';
    
    $id = intval( $_POST['program_id'] );
    $data = array( 
        'program_name' => sanitize_text_field( $_POST['program_name'] ),
        'short_code'   => isset( $_POST['short_code'] ) ? sanitize_text_field( $_POST['short_code'] ) : null,
        'dept_id'      => intval( $_POST['dept_id'] ),
        'is_active'    => isset( $_POST['is_active'] ) ? intval( $_POST['is_active'] ) : 1
    );
    $format = array( '%s', '%s', '%d', '%d' );

    try {
        if ( $id > 0 ) {
            $result = $wpdb->update( $table_name, $data, array( 'id' => $id ), $format, array( '%d' ) );
        } else {
            $result = $wpdb->insert( $table_name, $data, $format );
        }
        if ( $result === false ) {
            if ( $wpdb->last_error && str_contains( $wpdb->last_error, 'Duplicate entry' ) ) wp_send_json_error( 'Duplicate program name.' );
            wp_send_json_error( 'Database error.' );
        }
        wp_send_json_success( 'Program saved.' );
    } catch (Exception $e) {
        wp_send_json_error( $e->getMessage() );
    }
}
add_action( 'wp_ajax_BatchMate_save_program', 'BatchMate_save_program_ajax' );
add_action( 'wp_ajax_puaa_save_program',       'BatchMate_save_program_ajax' );