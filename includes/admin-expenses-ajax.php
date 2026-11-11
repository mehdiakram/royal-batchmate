<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

// --- AJAX HANDLER: Get All Expenses ---
function BatchMate_get_expenses_ajax() {
    // check_ajax_referer( 'BatchMate_expense_nonce' ); 
    if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Permission denied.', 403 );

    global $wpdb;
    $expenses_table = $wpdb->prefix . 'batchmate_expenses';
    $types_table = $wpdb->prefix . 'batchmate_expenses_type';

    $expenses = $wpdb->get_results( $wpdb->prepare(
        "SELECT e.id, e.type_id, e.expense_date, e.amount, e.description, e.vendor, 
                t.name as type_name
         FROM %i e
         LEFT JOIN %i t ON e.type_id = t.id
         ORDER BY e.expense_date DESC",
         $expenses_table, $types_table
    ) );

    wp_send_json( array( 'data' => $expenses ? $expenses : [] ) );
}
add_action( 'wp_ajax_BatchMate_get_expenses', 'BatchMate_get_expenses_ajax' );
add_action( 'wp_ajax_puaa_get_expenses',       'BatchMate_get_expenses_ajax' );

// --- AJAX HANDLER: Get All Expense Types ---
function BatchMate_get_expense_types_ajax() {
    // check_ajax_referer( 'BatchMate_expense_nonce' ); 
    if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Permission denied.', 403 );

    global $wpdb;
    $table_name = $wpdb->prefix . 'batchmate_expenses_type';
    $types = $wpdb->get_results( "SELECT id, name FROM $table_name ORDER BY name ASC" );
    
    wp_send_json_success( $types );
}
add_action( 'wp_ajax_BatchMate_get_expense_types', 'BatchMate_get_expense_types_ajax' );
add_action( 'wp_ajax_puaa_get_expense_types',       'BatchMate_get_expense_types_ajax' );

// --- AJAX HANDLER: Save Expense Type ---
function BatchMate_save_expense_type_ajax() {
    // check_ajax_referer( 'BatchMate_expense_nonce' );
    if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Permission denied.', 403 );

    if ( empty( $_POST['type_name'] ) ) wp_send_json_error( 'Type Name is required.' );

    global $wpdb;
    $table_name = $wpdb->prefix . 'batchmate_expenses_type';
    
    $id = intval( $_POST['type_id'] );
    $type_name = sanitize_text_field( $_POST['type_name'] );

    $data = array( 'name' => $type_name );
    $format = array( '%s' );

    try {
        if ( $id > 0 ) {
            // Update
            $result = $wpdb->update( $table_name, $data, array( 'id' => $id ), $format, array( '%d' ) );
        } else {
            // Insert
            $result = $wpdb->insert( $table_name, $data, $format );
        }

        if ( $result === false ) {
            if ( $wpdb->last_error && str_contains( $wpdb->last_error, 'Duplicate entry' ) ) {
                wp_send_json_error( 'An expense type with this name already exists.' );
            }
            wp_send_json_error( 'Database error: Could not save expense type.' );
        }
        wp_send_json_success( 'Expense Type saved.' );
    } catch (Exception $e) {
        wp_send_json_error( $e->getMessage() );
    }
}
add_action( 'wp_ajax_BatchMate_save_expense_type', 'BatchMate_save_expense_type_ajax' );
add_action( 'wp_ajax_puaa_save_expense_type',       'BatchMate_save_expense_type_ajax' );

// --- AJAX HANDLER: Get Single Expense Data for Editing (NEW) ---
function BatchMate_get_single_expense_ajax() {
    // check_ajax_referer( 'BatchMate_expense_nonce' ); 
    if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Permission denied.', 403 );

    if ( empty( $_POST['expense_id'] ) ) wp_send_json_error( 'Expense ID is missing.' );

    global $wpdb;
    $expenses_table = $wpdb->prefix . 'batchmate_expenses';
    
    $expense_id = intval( $_POST['expense_id'] );

    $expense = $wpdb->get_row( $wpdb->prepare(
        "SELECT id, expense_date, type_id, amount, description, vendor
         FROM %i 
         WHERE id = %d",
         $expenses_table, $expense_id
    ), ARRAY_A );

    if ($expense) {
        wp_send_json_success( $expense );
    } else {
        wp_send_json_error( 'Expense not found.' );
    }
}
add_action( 'wp_ajax_BatchMate_get_single_expense', 'BatchMate_get_single_expense_ajax' );
add_action( 'wp_ajax_puaa_get_single_expense',       'BatchMate_get_single_expense_ajax' );

// --- AJAX HANDLER: Save Expense (UPDATED for Insert/Update) ---
function BatchMate_save_expense_ajax() {
    // check_ajax_referer( 'BatchMate_expense_nonce' );
    if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Permission denied.', 403 );

    // Minimal required validation
    if ( empty( $_POST['amount'] ) || empty( $_POST['expense_date'] ) || empty( $_POST['type_id'] ) ) {
        wp_send_json_error( 'Amount, Date, and Type are required.' );
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'batchmate_expenses';

    $expense_id = intval($_POST['expense_id']); // 0 for new, >0 for edit

    $data = array(
        'expense_date' => sanitize_text_field($_POST['expense_date']),
        'type_id' => intval($_POST['type_id']),
        'amount' => floatval($_POST['amount']),
        'description' => sanitize_textarea_field($_POST['description']),
        'vendor' => sanitize_text_field($_POST['vendor']),
        'created_by' => get_current_user_id(),
    );
    $format = array('%s', '%d', '%f', '%s', '%s', '%d');

    if ($expense_id > 0) {
        // UPDATE Existing Expense
        $result = $wpdb->update($table_name, $data, array('id' => $expense_id), $format, array('%d'));
        $message = 'Expense updated successfully.';
    } else {
        // INSERT New Expense
        $result = $wpdb->insert($table_name, $data, $format);
        $message = 'Expense recorded successfully.';
    }

    if ($result === false) {
        wp_send_json_error('Database error: ' . $wpdb->last_error);
    }
    
    wp_send_json_success($message);
}
add_action( 'wp_ajax_BatchMate_save_expense', 'BatchMate_save_expense_ajax' );
add_action( 'wp_ajax_puaa_save_expense',       'BatchMate_save_expense_ajax' );