<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

// --- AJAX HANDLER: Get All Payments (UPDATED to include purpose_name) ---
function BatchMate_get_payments_ajax() {
    // check_ajax_referer( 'BatchMate_payment_nonce' ); 
    if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Permission denied.', 403 );

    global $wpdb;
    $payments_table = $wpdb->prefix . 'batchmate_payments';
    $members_table = $wpdb->prefix . 'batchmate_member';
    $modes_table = $wpdb->prefix . 'batchmate_payment_modes';
    $purpose_table = $wpdb->prefix . 'batchmate_payment_purpose';

    $payments = $wpdb->get_results( $wpdb->prepare(
        "SELECT p.id, p.payment_mode_id, p.purpose_id, p.member_id, p.amount, p.payment_date, p.transaction, p.note, 
                m.name as member_name, 
                pm.name as mode_name,
                pp.name as purpose_name
         FROM %i p
         LEFT JOIN %i m ON p.member_id = m.id
         LEFT JOIN %i pm ON p.payment_mode_id = pm.id
         LEFT JOIN %i pp ON p.purpose_id = pp.id
         ORDER BY p.id DESC",
         $payments_table, $members_table, $modes_table, $purpose_table
    ) );

    wp_send_json( array( 'data' => $payments ? $payments : [] ) );
}
add_action( 'wp_ajax_BatchMate_get_payments', 'BatchMate_get_payments_ajax' );
add_action( 'wp_ajax_puaa_get_payments',       'BatchMate_get_payments_ajax' );

// --- AJAX HANDLER: Get All Payment Modes ---
function BatchMate_get_payment_modes_ajax() {
    // check_ajax_referer( 'BatchMate_payment_nonce' ); 
    if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Permission denied.', 403 );

    global $wpdb;
    $table_name = $wpdb->prefix . 'batchmate_payment_modes';
    $modes = $wpdb->get_results( "SELECT id, name FROM $table_name ORDER BY name ASC" );
    
    wp_send_json_success( $modes );
}
add_action( 'wp_ajax_BatchMate_get_payment_modes', 'BatchMate_get_payment_modes_ajax' );
add_action( 'wp_ajax_puaa_get_payment_modes',       'BatchMate_get_payment_modes_ajax' );

// --- AJAX HANDLER: Get All Payment Purposes (UPDATED) ---
function BatchMate_get_payment_purposes_ajax() {
    if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Permission denied.', 403 );

    global $wpdb;
    $table_name = $wpdb->prefix . 'batchmate_payment_purpose';
    // Fetch all purposes, including is_active status
    $purposes = $wpdb->get_results( "SELECT id, name, is_active FROM $table_name ORDER BY name ASC" );
    
    wp_send_json_success( $purposes );
}
add_action( 'wp_ajax_BatchMate_get_payment_purposes', 'BatchMate_get_payment_purposes_ajax' );
add_action( 'wp_ajax_puaa_get_payment_purposes',       'BatchMate_get_payment_purposes_ajax' );

// --- AJAX HANDLER: Save Payment Mode ---
function BatchMate_save_payment_mode_ajax() {
    // check_ajax_referer( 'BatchMate_payment_nonce' );
    if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Permission denied.', 403 );

    if ( empty( $_POST['mode_name'] ) ) wp_send_json_error( 'Mode Name is required.' );

    global $wpdb;
    $table_name = $wpdb->prefix . 'batchmate_payment_modes';
    
    $id = intval( $_POST['mode_id'] );
    $mode_name = sanitize_text_field( $_POST['mode_name'] );
    $current_user_id = get_current_user_id();

    $data = array( 
        'name' => $mode_name,
        'created_by' => $current_user_id 
    );
    $format = array( '%s', '%d' );

    try {
        if ( $id > 0 ) {
            // Update
            $result = $wpdb->update( $table_name, $data, array( 'id' => $id ), $format, array( '%d' ) );
        } else {
            // Insert
            $result = $wpdb->insert( $table_name, $data, $format );
        }

        if ( $result === false ) {
            wp_send_json_error( 'Database error: ' . $wpdb->last_error );
        }
        wp_send_json_success( 'Payment mode saved successfully.' );
    } catch (Exception $e) {
        wp_send_json_error( $e->getMessage() );
    }
}
add_action( 'wp_ajax_BatchMate_save_payment_mode', 'BatchMate_save_payment_mode_ajax' );
add_action( 'wp_ajax_puaa_save_payment_mode',       'BatchMate_save_payment_mode_ajax' );

// --- AJAX HANDLER: Save Payment Purpose (FIXED to save is_active) ---
function BatchMate_save_payment_purpose_ajax() {
    if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Permission denied.', 403 );

    if ( empty( $_POST['purpose_name'] ) ) wp_send_json_error( 'Purpose Name is required.' );

    global $wpdb;
    $table_name = $wpdb->prefix . 'batchmate_payment_purpose';
    
    $id = intval( $_POST['purpose_id'] );
    $purpose_name = sanitize_text_field( $_POST['purpose_name'] );
    // FIX: is_active মানটি এখন জাভাস্ক্রিপ্ট থেকে পাঠানো হচ্ছে
    $is_active = isset( $_POST['is_active'] ) ? intval( $_POST['is_active'] ) : 0; 

    $data = array( 'name' => $purpose_name, 'is_active' => $is_active ); // <--- FIX: is_active is now included in data
    $format = array( '%s', '%d' );

    try {
        if ( $id > 0 ) {
            $result = $wpdb->update( $table_name, $data, array( 'id' => $id ), $format, array( '%d' ) );
        } else {
            $result = $wpdb->insert( $table_name, $data, $format );
        }

        if ( $result === false ) {
            if ( $wpdb->last_error && str_contains( $wpdb->last_error, 'Duplicate entry' ) ) {
                wp_send_json_error( 'A payment purpose with this name already exists.' );
            }
            wp_send_json_error( 'Database error: Could not save payment purpose.' );
        }
        wp_send_json_success( 'Payment Purpose saved.' );
    } catch (Exception $e) {
        wp_send_json_error( $e->getMessage() );
    }
}
add_action( 'wp_ajax_BatchMate_save_payment_purpose', 'BatchMate_save_payment_purpose_ajax' );
add_action( 'wp_ajax_puaa_save_payment_purpose',       'BatchMate_save_payment_purpose_ajax' );


function BatchMate_get_members_for_select_ajax() {
    // check_ajax_referer( 'BatchMate_payment_nonce' ); 
    if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Permission denied.', 403 );

    global $wpdb;
    $table_name = $wpdb->prefix . 'batchmate_member';
    
    $members = $wpdb->get_results( "SELECT id, name, member_number FROM $table_name ORDER BY member_number ASC" );
    
    wp_send_json_success( $members );
}
add_action( 'wp_ajax_BatchMate_get_members_for_select', 'BatchMate_get_members_for_select_ajax' );
add_action( 'wp_ajax_puaa_get_members_for_select',       'BatchMate_get_members_for_select_ajax' );


// --- NEW AJAX HANDLER: Get Single Payment Data for Editing (UPDATED to retrieve purpose_id) ---
function BatchMate_get_single_payment_ajax() {
    // check_ajax_referer( 'BatchMate_payment_nonce' ); 
    if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Permission denied.', 403 );

    if ( empty( $_POST['payment_id'] ) ) wp_send_json_error( 'Payment ID is missing.' );

    global $wpdb;
    $payments_table = $wpdb->prefix . 'batchmate_payments';
    
    $payment_id = intval( $_POST['payment_id'] );

    $payment = $wpdb->get_row( $wpdb->prepare(
        "SELECT id, member_id, amount, payment_date, payment_mode_id, purpose_id, transaction, note
         FROM %i 
         WHERE id = %d",
         $payments_table, $payment_id
    ), ARRAY_A );

    if ($payment) {
        wp_send_json_success( $payment );
    } else {
        wp_send_json_error( 'Payment not found.' );
    }
}
add_action( 'wp_ajax_BatchMate_get_single_payment', 'BatchMate_get_single_payment_ajax' );
add_action( 'wp_ajax_puaa_get_single_payment',       'BatchMate_get_single_payment_ajax' );


// --- AJAX HANDLER: Save Payment (Insert/Update) (UPDATED to save purpose_id) ---
function BatchMate_save_payment_ajax() {
    // check_ajax_referer( 'BatchMate_payment_nonce' );
    if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Permission denied.', 403 );

    // Minimal required validation
    if ( empty( $_POST['amount'] ) || empty( $_POST['payment_date'] ) || empty( $_POST['payment_mode_id'] ) || empty( $_POST['purpose_id'] ) ) {
        wp_send_json_error( 'Amount, Date, Payment Mode, and Purpose are required.' );
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'batchmate_payments';

    $payment_id = intval($_POST['payment_id']); // 0 for new, >0 for edit
    $member_id = empty($_POST['member_id']) ? NULL : intval($_POST['member_id']);

    $data = array(
        'member_id' => $member_id,
        'amount' => floatval($_POST['amount']),
        'payment_date' => sanitize_text_field($_POST['payment_date']),
        'payment_mode_id' => intval($_POST['payment_mode_id']),
        'purpose_id' => intval($_POST['purpose_id']), // <--- NEW
        'transaction' => sanitize_text_field($_POST['transaction']),
        'note' => sanitize_textarea_field($_POST['note']),
        'created_by' => get_current_user_id(), // Must be a valid user ID (ensured by check)
    );
    // %d ফরম্যাট ব্যবহার করা হয়েছে member_id এবং purpose_id এর জন্য, NULL ভ্যালুও গ্রহণ করবে।
    $format = array('%d', '%f', '%s', '%d', '%d', '%s', '%s', '%d');

    if ($payment_id > 0) {
        // UPDATE Existing Payment
        $result = $wpdb->update($table_name, $data, array('id' => $payment_id), $format, array('%d'));
        if ($result === false) {
             wp_send_json_error('Database error: ' . $wpdb->last_error);
        }
        wp_send_json_success('Payment updated successfully.');
    } else {
        // INSERT New Payment
        $result = $wpdb->insert($table_name, $data, $format);
        if ($result === false) {
            wp_send_json_error('Database error: ' . $wpdb->last_error);
        }
        wp_send_json_success('Payment recorded successfully.');
    }
}
add_action( 'wp_ajax_BatchMate_save_payment', 'BatchMate_save_payment_ajax' );
add_action( 'wp_ajax_puaa_save_payment',       'BatchMate_save_payment_ajax' );

