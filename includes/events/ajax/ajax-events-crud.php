<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/* =====================================================================
 * EVENTS MODULE — AJAX HANDLERS
 * =====================================================================
 * Nonce: batchmate_event_nonce
 * ===================================================================== */

// ─────────────────────────────────────────────────────────────────────
// Helper
// ─────────────────────────────────────────────────────────────────────
function batchmate_event_check_admin() {
    check_ajax_referer( 'batchmate_event_nonce' );
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( 'Permission denied.', 403 );
    }
}


// ─────────────────────────────────────────────────────────────────────
// 1. GET EVENTS LIST (DataTable)
// ─────────────────────────────────────────────────────────────────────
function BatchMate_get_events_ajax() {
    batchmate_event_check_admin();
    global $wpdb;

    if ( function_exists( 'batchmate_ensure_saree_column' ) ) {
        batchmate_ensure_saree_column();
    }

    $events = $wpdb->get_results( "
        SELECT id, event_name, event_date, member_fee, is_active, allow_non_members, has_id_card, auto_send_card,
               has_tshirt, has_saree, cc_email, location, registered_url, card_template_url, card_zones_json, payment_methods
        FROM {$wpdb->prefix}batchmate_event
        ORDER BY id DESC
    " );

    if ( $events === null || ( empty( $events ) && ! empty( $wpdb->last_error ) ) ) {
        $events = $wpdb->get_results( "
            SELECT id, event_name, event_date, member_fee, is_active, allow_non_members, has_id_card, auto_send_card,
                   has_tshirt, 0 as has_saree, cc_email, location, registered_url, card_template_url, card_zones_json, '' AS payment_methods
            FROM {$wpdb->prefix}batchmate_event
            ORDER BY id DESC
        " );
    }

    wp_send_json( array( 'data' => $events ? $events : [] ) );
}
add_action( 'wp_ajax_BatchMate_get_events', 'BatchMate_get_events_ajax' );
add_action( 'wp_ajax_puaa_get_events',       'BatchMate_get_events_ajax' );


// ─────────────────────────────────────────────────────────────────────
// 2. SAVE EVENT (Create / Update)
// ─────────────────────────────────────────────────────────────────────
function BatchMate_save_event_ajax() {
    batchmate_event_check_admin();
    global $wpdb;
    batchmate_ensure_saree_column();
    $table = $wpdb->prefix . 'batchmate_event';

    $event_id   = intval( $_POST['event_id'] ?? 0 );
    $event_name = sanitize_text_field( $_POST['event_name'] ?? '' );
    $event_date = sanitize_text_field( $_POST['event_date'] ?? '' );
    $member_fee = floatval( $_POST['member_fee'] ?? 0 );
    $is_active         = intval( $_POST['is_active'] ?? 0 );
    $allow_non_members = intval( $_POST['allow_non_members'] ?? 0 );
    $has_id_card       = intval( $_POST['has_id_card'] ?? 0 );
    $auto_send_card    = intval( $_POST['auto_send_card'] ?? 0 );
    $has_tshirt        = intval( $_POST['has_tshirt'] ?? 0 );
    $has_saree         = intval( $_POST['has_saree'] ?? 0 );
    $payment_methods   = sanitize_textarea_field( $_POST['payment_methods'] ?? '' );
    $cc_email          = sanitize_email( $_POST['cc_email'] ?? '' );
    $location          = sanitize_textarea_field( $_POST['location'] ?? '' );
    $registered_url    = esc_url_raw( trim( $_POST['registered_url'] ?? '' ) );
    $card_template_url   = esc_url_raw( trim( $_POST['card_template_url'] ?? '' ) );
    $card_zones_json_raw = $_POST['card_zones_json'] ?? '';
    $card_zones_json     = '';
    if ( ! empty( $card_zones_json_raw ) ) {
        $decoded = json_decode( stripslashes( $card_zones_json_raw ), true );
        $card_zones_json = $decoded ? wp_json_encode( $decoded ) : '';
    }

    if ( empty( $event_name ) || empty( $event_date ) ) {
        wp_send_json_error( 'Event name and date are required.' );
    }

    if ( $is_active ) {
        $wpdb->update( $table, array( 'is_active' => 0 ), array( 'is_active' => 1 ), array( '%d' ), array( '%d' ) );
    }

    if ( $event_id > 0 ) {
        // Preserve existing card template & zones if empty in the event edit form
        if ( empty( $card_template_url ) ) {
            $existing_tpl = $wpdb->get_var( $wpdb->prepare( "SELECT card_template_url FROM $table WHERE id = %d", $event_id ) );
            if ( ! empty( $existing_tpl ) ) {
                $card_template_url = $existing_tpl;
            }
        }
        if ( empty( $card_zones_json ) ) {
            $existing_zn = $wpdb->get_var( $wpdb->prepare( "SELECT card_zones_json FROM $table WHERE id = %d", $event_id ) );
            if ( ! empty( $existing_zn ) ) {
                $card_zones_json = $existing_zn;
            }
        }

        $result = $wpdb->update(
            $table,
            array(
                'event_name'         => $event_name,
                'event_date'         => $event_date,
                'member_fee'         => $member_fee,
                'allow_non_members'  => $allow_non_members,
                'has_id_card'        => $has_id_card,
                'auto_send_card'     => $auto_send_card,
                'has_tshirt'         => $has_tshirt,
                'has_saree'          => $has_saree,
                'payment_methods'    => $payment_methods ?: null,
                'cc_email'           => $cc_email,
                'is_active'          => $is_active,
                'location'           => $location,
                'registered_url'     => $registered_url,
                'card_template_url'  => $card_template_url,
                'card_zones_json'    => $card_zones_json ?: null,
            ),
            array( 'id' => $event_id ),
            array( '%s', '%s', '%f', '%d', '%d', '%d', '%d', '%d', '%s', '%s', '%d', '%s', '%s', '%s', '%s' ),
            array( '%d' )
        );
        if ( $result === false ) wp_send_json_error( 'Database error while updating.' );
    } else {
        $result = $wpdb->insert(
            $table,
            array(
                'event_name'         => $event_name,
                'event_date'         => $event_date,
                'member_fee'         => $member_fee,
                'allow_non_members'  => $allow_non_members,
                'has_id_card'        => $has_id_card,
                'auto_send_card'     => $auto_send_card,
                'has_tshirt'         => $has_tshirt,
                'has_saree'          => $has_saree,
                'payment_methods'    => $payment_methods ?: null,
                'cc_email'           => $cc_email,
                'is_active'          => $is_active,
                'location'           => $location,
                'registered_url'     => $registered_url,
                'card_template_url'  => $card_template_url,
                'card_zones_json'    => $card_zones_json ?: null,
                'created_by'         => get_current_user_id(),
            ),
            array( '%s', '%s', '%f', '%d', '%d', '%d', '%d', '%d', '%s', '%s', '%d', '%s', '%s', '%s', '%s', '%d' )
        );
        if ( $result === false ) wp_send_json_error( 'Database error while saving.' );
        $event_id = $wpdb->insert_id;
    }

    // Save guest options
    $opt_table = $wpdb->prefix . 'batchmate_event_option';
    $wpdb->delete( $opt_table, array( 'event_id' => $event_id ), array( '%d' ) );
    $options_raw = $_POST['event_options'] ?? [];
    if ( is_array( $options_raw ) ) {
        $sort = 0;
        foreach ( $options_raw as $opt ) {
            $name    = sanitize_text_field( $opt['name']    ?? '' );
            $fee     = floatval( $opt['fee']     ?? 0 );
            $max_qty = intval(   $opt['max_qty'] ?? 0 );
            if ( empty( $name ) ) continue;
            $wpdb->insert( $opt_table, array(
                'event_id'   => $event_id,
                'option_name'=> $name,
                'fee'        => $fee,
                'max_qty'    => $max_qty,
                'sort_order' => $sort++,
            ), array( '%d', '%s', '%f', '%d', '%d' ) );
        }
    }

    wp_send_json_success( array( 'message' => 'Event saved successfully.', 'event_id' => $event_id ) );
}
add_action( 'wp_ajax_BatchMate_save_event', 'BatchMate_save_event_ajax' );
add_action( 'wp_ajax_puaa_save_event',       'BatchMate_save_event_ajax' );


// ─────────────────────────────────────────────────────────────────────
// 1b. GET EVENT OPTIONS (public + admin)
// ─────────────────────────────────────────────────────────────────────
function BatchMate_get_event_options_ajax() {
    global $wpdb;
    $event_id = intval( $_POST['event_id'] ?? 0 );
    if ( ! $event_id ) wp_send_json_error( 'Invalid event.' );

    $ev_info = $wpdb->get_row( $wpdb->prepare( "SELECT member_fee, has_tshirt, has_saree FROM {$wpdb->prefix}batchmate_event WHERE id = %d", $event_id ) );


    $opts = $wpdb->get_results( $wpdb->prepare(
        "SELECT id, option_name, fee, max_qty FROM {$wpdb->prefix}batchmate_event_option WHERE event_id = %d ORDER BY sort_order ASC",
        $event_id
    ) );
    
    $payment_methods = function_exists( 'batchmate_get_payment_methods' ) ? batchmate_get_payment_methods( $event_id ) : array();
    $membership_fee  = function_exists( 'batchmate_get_membership_fee_info' ) ? batchmate_get_membership_fee_info() : array( 'active' => false, 'amount' => 0 );

    wp_send_json_success( array(
        'options'          => $opts,
        'member_fee'       => floatval( $ev_info->member_fee ?? 0 ),
        'has_tshirt'       => (int) ($ev_info->has_tshirt ?? 0),
        'has_saree'        => (int) ($ev_info->has_saree ?? 0),
        'payment_methods'  => $payment_methods,
        'membership_fee'   => $membership_fee,
    ) );
}
add_action( 'wp_ajax_BatchMate_get_event_options', 'BatchMate_get_event_options_ajax' );
add_action( 'wp_ajax_nopriv_BatchMate_get_event_options', 'BatchMate_get_event_options_ajax' );
add_action( 'wp_ajax_puaa_get_event_options',       'BatchMate_get_event_options_ajax' );
add_action( 'wp_ajax_nopriv_puaa_get_event_options', 'BatchMate_get_event_options_ajax' );



// ─────────────────────────────────────────────────────────────────────
// 3. DELETE EVENT
// ─────────────────────────────────────────────────────────────────────
function BatchMate_delete_event_ajax() {
    batchmate_event_check_admin();
    global $wpdb;

    $event_id = intval( $_POST['event_id'] ?? 0 );
    if ( ! $event_id ) wp_send_json_error( 'Invalid event ID.' );

    $app_count = $wpdb->get_var( $wpdb->prepare(
        "SELECT COUNT(id) FROM {$wpdb->prefix}batchmate_event_application WHERE event_id = %d", $event_id
    ) );
    if ( $app_count > 0 ) {
        wp_send_json_error( 'Cannot delete: this event has ' . $app_count . ' application(s). Deactivate it instead.' );
    }

    $result = $wpdb->delete( $wpdb->prefix . 'batchmate_event', array( 'id' => $event_id ), array( '%d' ) );
    if ( $result === false ) wp_send_json_error( 'Database error while deleting.' );
    wp_send_json_success( 'Event deleted.' );
}
add_action( 'wp_ajax_BatchMate_delete_event', 'BatchMate_delete_event_ajax' );
add_action( 'wp_ajax_puaa_delete_event',       'BatchMate_delete_event_ajax' );


// ─────────────────────────────────────────────────────────────────────