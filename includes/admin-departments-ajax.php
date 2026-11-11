<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * --- AJAX HANDLER: Get Departments ---
 */
function BatchMate_get_departments_ajax() {
    check_ajax_referer( 'BatchMate_dept_nonce' );
    if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Permission denied.', 403 );

    global $wpdb;
    $dept_table = $wpdb->prefix . 'batchmate_department';
    $prog_table = $wpdb->prefix . 'batchmate_program';
    $member_table = $wpdb->prefix . 'batchmate_member';

    // ?????? ?????: is_active ???? ??
    $departments = $wpdb->get_results( $wpdb->prepare(
        "SELECT d.id, d.dept_name, d.is_active, COUNT(DISTINCT m.id) as member_count
         FROM %i d
         LEFT JOIN %i p ON d.id = p.dept_id
         LEFT JOIN %i m ON p.id = GROUP BY d.id
         ORDER BY d.id DESC",
         $dept_table, $prog_table, $member_table
    ) );

    wp_send_json( array( 'data' => $departments ? $departments : [] ) );
}
add_action( 'wp_ajax_BatchMate_get_departments', 'BatchMate_get_departments_ajax' );
add_action( 'wp_ajax_puaa_get_departments',       'BatchMate_get_departments_ajax' );

/**
 * --- AJAX HANDLER: Save/Update Department ---
 */
function BatchMate_save_department_ajax() {
    check_ajax_referer( 'BatchMate_dept_nonce' );
    if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Permission denied.', 403 );

    if ( empty( $_POST['dept_name'] ) ) wp_send_json_error( 'Department name is required.' );

    global $wpdb;
    $table_name = $wpdb->prefix . 'batchmate_department';
    
    $dept_id = intval( $_POST['dept_id'] );
    $dept_name = sanitize_text_field( $_POST['dept_name'] );
    // ????????? ?????? ? (Active)
    $is_active = isset( $_POST['is_active'] ) ? intval( $_POST['is_active'] ) : 1; 

    $data = array( 
        'dept_name' => $dept_name,
        'is_active' => $is_active 
    );
    $format = array( '%s', '%d' );

    try {
        if ( $dept_id > 0 ) {
            $result = $wpdb->update( $table_name, $data, array( 'id' => $dept_id ), $format, array( '%d' ) );
        } else {
            $result = $wpdb->insert( $table_name, $data, $format );
        }

        if ( $result === false ) {
            if ( $wpdb->last_error && str_contains( $wpdb->last_error, 'Duplicate entry' ) ) {
                wp_send_json_error( 'A department with this name already exists.' );
            }
            wp_send_json_error( 'Database error: Could not save department.' );
        }
        wp_send_json_success( 'Department saved.' );
    } catch (Exception $e) {
        wp_send_json_error( $e->getMessage() );
    }
}
add_action( 'wp_ajax_BatchMate_save_department', 'BatchMate_save_department_ajax' );
add_action( 'wp_ajax_puaa_save_department',       'BatchMate_save_department_ajax' );
