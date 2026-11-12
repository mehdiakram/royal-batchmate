<?php
if ( ! defined( 'ABSPATH' ) ) exit;

// 12. GET EVENT TRANSACTIONS
// ─────────────────────────────────────────────────────────────────────
function BatchMate_get_event_transactions_ajax() {
    batchmate_event_check_admin();
    global $wpdb;
    $event_id = intval( $_POST['event_id'] ?? 0 );
    if ( ! $event_id ) wp_send_json_error( 'Event ID missing.' );

    $table = $wpdb->prefix . 'batchmate_event_transactions';
    $results = $wpdb->get_results( $wpdb->prepare( "SELECT id, trans_type, purpose, amount, trans_date FROM $table WHERE event_id = %d ORDER BY trans_date DESC, id DESC", $event_id ) );

    wp_send_json( array( 'data' => $results ? $results : [] ) );
}
add_action( 'wp_ajax_BatchMate_get_event_transactions', 'BatchMate_get_event_transactions_ajax' );
add_action( 'wp_ajax_puaa_get_event_transactions',       'BatchMate_get_event_transactions_ajax' );

// ─────────────────────────────────────────────────────────────────────
// 13. SAVE EVENT TRANSACTION
// ─────────────────────────────────────────────────────────────────────
function BatchMate_save_event_transaction_ajax() {
    batchmate_event_check_admin();
    global $wpdb;

    $id         = intval( $_POST['trans_id'] ?? 0 );
    $event_id   = intval( $_POST['event_id'] ?? 0 );
    $trans_type = sanitize_text_field( $_POST['trans_type'] ?? 'expense' );
    $purpose    = sanitize_text_field( $_POST['purpose'] ?? '' );
    $amount     = floatval( $_POST['amount'] ?? 0 );
    $trans_date = sanitize_text_field( $_POST['trans_date'] ?? '' );

    if ( ! $event_id || empty( $purpose ) || empty( $trans_date ) || $amount <= 0 ) {
        wp_send_json_error( 'Please fill all required fields correctly.' );
    }

    $table = $wpdb->prefix . 'batchmate_event_transactions';

    if ( $id > 0 ) {
        $res = $wpdb->update( $table,
            array( 'trans_type' => $trans_type, 'purpose' => $purpose, 'amount' => $amount, 'trans_date' => $trans_date ),
            array( 'id' => $id ),
            array( '%s', '%s', '%f', '%s' ), array( '%d' )
        );
        if ( $res === false ) wp_send_json_error( 'Database error on update.' );
        wp_send_json_success( 'Transaction updated.' );
    } else {
        $res = $wpdb->insert( $table,
            array( 'event_id' => $event_id, 'trans_type' => $trans_type, 'purpose' => $purpose, 'amount' => $amount, 'trans_date' => $trans_date, 'created_by' => get_current_user_id() ),
            array( '%d', '%s', '%s', '%f', '%s', '%d' )
        );
        if ( $res === false ) wp_send_json_error( 'Database error on insert.' );
        wp_send_json_success( 'Transaction added.' );
    }
}
add_action( 'wp_ajax_BatchMate_save_event_transaction', 'BatchMate_save_event_transaction_ajax' );
add_action( 'wp_ajax_puaa_save_event_transaction',       'BatchMate_save_event_transaction_ajax' );

// ─────────────────────────────────────────────────────────────────────
// 14. DELETE EVENT TRANSACTION
// ─────────────────────────────────────────────────────────────────────
function BatchMate_delete_event_transaction_ajax() {
    batchmate_event_check_admin();
    global $wpdb;
    $id = intval( $_POST['trans_id'] ?? 0 );
    if ( ! $id ) wp_send_json_error( 'Invalid ID.' );

    $res = $wpdb->delete( $wpdb->prefix . 'batchmate_event_transactions', array( 'id' => $id ), array( '%d' ) );
    if ( $res === false ) wp_send_json_error( 'Database error.' );
    wp_send_json_success( 'Deleted.' );
}
add_action( 'wp_ajax_BatchMate_delete_event_transaction', 'BatchMate_delete_event_transaction_ajax' );
add_action( 'wp_ajax_puaa_delete_event_transaction',       'BatchMate_delete_event_transaction_ajax' );

// ─────────────────────────────────────────────────────────────────────
// 15. GET EVENT REPORT (COLLECTION, SPONSORS, EXPENSES)
// ─────────────────────────────────────────────────────────────────────
function BatchMate_get_event_report_ajax() {
    batchmate_event_check_admin();
    global $wpdb;
    $event_id = intval( $_POST['event_id'] ?? 0 );
    if ( ! $event_id ) wp_send_json_error( 'Event ID missing.' );

    // 1. Total Collection (sum of amounts from approved applications in batchmate_event_application matching batchmate_event_members)
    $collection = (float) $wpdb->get_var( $wpdb->prepare( "
        SELECT SUM(ea.amount) 
        FROM {$wpdb->prefix}batchmate_event_application ea
        INNER JOIN {$wpdb->prefix}batchmate_event_members em ON ea.id = em.application_id
        WHERE ea.event_id = %d
    ", $event_id ) );

    // Count of distinct members
    $total_members = (int) $wpdb->get_var( $wpdb->prepare( "
        SELECT COUNT(em.id) 
        FROM {$wpdb->prefix}batchmate_event_members em
        WHERE em.event_id = %d
    ", $event_id ) );

    // Calculate guests
    $total_guests = 0;
    $apps = $wpdb->get_results( $wpdb->prepare( "
        SELECT ea.options_json 
        FROM {$wpdb->prefix}batchmate_event_application ea
        INNER JOIN {$wpdb->prefix}batchmate_event_members em ON ea.id = em.application_id
        WHERE ea.event_id = %d
    ", $event_id ) );
    foreach ( $apps as $app ) {
        if ( ! empty( $app->options_json ) ) {
            $opts = json_decode( stripslashes( $app->options_json ), true );
            if ( is_string( $opts ) ) $opts = json_decode( $opts, true );
            if ( is_array( $opts ) ) {
                foreach ( $opts as $o ) {
                    $total_guests += intval( $o['qty'] ?? 0 );
                }
            }
        }
    }

    // 2. Sponsorships & Expenses
    $transactions = $wpdb->get_results( $wpdb->prepare( "SELECT trans_type, SUM(amount) as total FROM {$wpdb->prefix}batchmate_event_transactions WHERE event_id = %d GROUP BY trans_type", $event_id ) );
    
    $sponsorship = 0.0;
    $expense = 0.0;
    foreach ( $transactions as $t ) {
        if ( $t->trans_type === 'sponsor' ) $sponsorship = (float) $t->total;
        if ( $t->trans_type === 'expense' ) $expense = (float) $t->total;
    }

    $total_income = $collection + $sponsorship;
    $net_balance = $total_income - $expense;

    wp_send_json_success( array(
        'collection'  => $collection,
        'sponsorship' => $sponsorship,
        'expense'     => $expense,
        'total_income'=> $total_income,
        'net_balance' => $net_balance,
        'members_count'=> $total_members,
        'guests_count' => $total_guests
    ) );
}
add_action( 'wp_ajax_BatchMate_get_event_report', 'BatchMate_get_event_report_ajax' );
add_action( 'wp_ajax_puaa_get_event_report',       'BatchMate_get_event_report_ajax' );


// ─────────────────────────────────────────────────────────────────────
// 15b. GET T-SHIRT SIZE SUMMARY (Admin only)
// ─────────────────────────────────────────────────────────────────────
function BatchMate_get_tshirt_summary_ajax() {
    batchmate_event_check_admin();
    global $wpdb;

    $event_id = intval( $_POST['event_id'] ?? 0 );
    if ( ! $event_id ) wp_send_json_error( 'Event ID missing.' );

    // Check if event has tshirt or saree enabled
    $event_row = $wpdb->get_row( $wpdb->prepare(
        "SELECT has_tshirt, has_saree FROM {$wpdb->prefix}batchmate_event WHERE id = %d", $event_id
    ) );

    $has_tshirt = (int) ( $event_row->has_tshirt ?? 0 );
    $has_saree  = (int) ( $event_row->has_saree  ?? 0 );

    // Count T-Shirt/Saree sizes — if event has tshirt/saree enabled
    $valid_sizes = array( 'S', 'M', 'X', 'L', 'XL', 'XXL', 'XXXL', 'Saree' );
    $ordered     = array();

    if ( $has_tshirt ) {
        $rows = $wpdb->get_results( $wpdb->prepare(
            "SELECT
                CASE
                    WHEN ea.tshirt_size IS NULL OR ea.tshirt_size = '' OR ea.tshirt_size = 'NA'
                    THEN 'N/A'
                    ELSE ea.tshirt_size
                END AS size_label,
                COUNT(*) AS cnt
             FROM {$wpdb->prefix}batchmate_event_members em
             LEFT JOIN {$wpdb->prefix}batchmate_event_application ea ON ea.id = em.application_id
             WHERE em.event_id = %d
             GROUP BY size_label
             ORDER BY FIELD(size_label, 'S','M','X','L','XL','XXL','XXXL','Saree','N/A')",
            $event_id
        ) );

        $size_map = array();
        foreach ( $rows as $r ) {
            $size_map[ $r->size_label ] = (int) $r->cnt;
        }
        foreach ( $valid_sizes as $s ) {
            if ( isset( $size_map[ $s ] ) && $size_map[ $s ] > 0 ) {
                $ordered[] = array( 'size' => $s, 'count' => $size_map[ $s ] );
            }
        }
        if ( isset( $size_map['N/A'] ) && $size_map['N/A'] > 0 ) {
            $ordered[] = array( 'size' => 'N/A', 'count' => $size_map['N/A'] );
        }
    }

    // Payment method breakdown — always calculated from approved members
    $payment_rows = $wpdb->get_results( $wpdb->prepare(
        "SELECT
            COALESCE(NULLIF(TRIM(ea.payment_method), ''), 'Cash / Direct') AS method,
            COUNT(em.id)                AS member_count,
            SUM(COALESCE(ea.amount, 0)) AS total_amount
         FROM {$wpdb->prefix}batchmate_event_members em
         LEFT JOIN {$wpdb->prefix}batchmate_event_application ea 
            ON (ea.id = em.application_id OR (ea.event_id = em.event_id AND (
                (em.member_id > 0 AND ea.member_id = em.member_id) OR
                (em.rawdata_id > 0 AND ea.rawdata_id = em.rawdata_id)
            )))
         WHERE em.event_id = %d
         GROUP BY method
         ORDER BY total_amount DESC",
        $event_id
    ) );

    $payment_methods = array();
    foreach ( $payment_rows as $pr ) {
        $payment_methods[] = array(
            'method'       => $pr->method,
            'member_count' => (int) $pr->member_count,
            'total_amount' => (float) $pr->total_amount,
        );
    }

    wp_send_json_success( array(
        'has_tshirt'      => (bool) $has_tshirt,
        'has_saree'       => (bool) $has_saree,
        'sizes'           => $ordered,
        'payment_methods' => $payment_methods,
    ) );
}
add_action( 'wp_ajax_BatchMate_get_tshirt_summary', 'BatchMate_get_tshirt_summary_ajax' );
add_action( 'wp_ajax_puaa_get_tshirt_summary',       'BatchMate_get_tshirt_summary_ajax' );

// ─────────────────────────────────────────────────────────────────────

// 18. GET DEPARTMENT-WISE REGISTRATION SUMMARY
// ─────────────────────────────────────────────────────────────────────
function BatchMate_get_dept_registration_summary_ajax() {
    batchmate_event_check_admin();
    global $wpdb;

    $event_id = intval( $_POST['event_id'] ?? 0 );
    if ( ! $event_id ) wp_send_json_error( 'Event ID missing.' );

    // Fetch per-registrant data including options_json for guest counting
    $rows_raw = $wpdb->get_results( $wpdb->prepare( "
        SELECT
            COALESCE(
                NULLIF(IF(em.member_id > 0, 
                    COALESCE(NULLIF(NULLIF(NULLIF(TRIM(m.permanent_district), ''), 'null'), 'NULL'), NULLIF(NULLIF(NULLIF(TRIM(m.present_district), ''), 'null'), 'NULL')), 
                    COALESCE(NULLIF(NULLIF(NULLIF(TRIM(r.permanent_district), ''), 'null'), 'NULL'), NULLIF(NULLIF(NULLIF(TRIM(r.present_district), ''), 'null'), 'NULL'))
                ), ''),
                'Other'
            ) AS dept_name,
            ea.options_json
        FROM {$wpdb->prefix}batchmate_event_members em
        LEFT JOIN {$wpdb->prefix}batchmate_member  m    ON em.member_id  = m.id
        LEFT JOIN {$wpdb->prefix}batchmate_rawdata r     ON em.rawdata_id = r.id
        LEFT JOIN {$wpdb->prefix}batchmate_event_application ea ON ea.id = em.application_id
        WHERE em.event_id = %d
        ORDER BY dept_name ASC
    ", $event_id ) );

    // Aggregate per department: member_count + guest_count
    $dept_map = array(); // dept_name => [ member_count, guest_count ]
    foreach ( $rows_raw as $r ) {
        $dept = $r->dept_name ?: 'Unknown';
        if ( ! isset( $dept_map[ $dept ] ) ) {
            $dept_map[ $dept ] = array( 'member_count' => 0, 'guest_count' => 0 );
        }
        $dept_map[ $dept ]['member_count']++;

        // Parse options_json to sum guest qty for this registrant
        if ( ! empty( $r->options_json ) ) {
            $opts = json_decode( stripslashes( $r->options_json ), true );
            if ( is_string( $opts ) ) $opts = json_decode( $opts, true );
            if ( is_array( $opts ) ) {
                foreach ( $opts as $o ) {
                    $dept_map[ $dept ]['guest_count'] += intval( $o['qty'] ?? 0 );
                }
            }
        }
    }

    // Sort by member_count DESC, then dept_name ASC
    uasort( $dept_map, function( $a, $b ) {
        if ( $b['member_count'] !== $a['member_count'] ) return $b['member_count'] - $a['member_count'];
        return strcmp( array_key_first( $a ), array_key_first( $b ) );
    } );

    $rows        = array();
    $total       = 0;
    $total_guest = 0;
    foreach ( $dept_map as $dept_name => $counts ) {
        $rows[]       = array(
            'dept_name'    => $dept_name,
            'member_count' => $counts['member_count'],
            'guest_count'  => $counts['guest_count'],
        );
        $total        += $counts['member_count'];
        $total_guest  += $counts['guest_count'];
    }

    wp_send_json_success( array(
        'rows'        => $rows,
        'total'       => $total,
        'total_guest' => $total_guest,
    ) );
}
add_action( 'wp_ajax_BatchMate_get_dept_registration_summary', 'BatchMate_get_dept_registration_summary_ajax' );
add_action( 'wp_ajax_puaa_get_dept_registration_summary',       'BatchMate_get_dept_registration_summary_ajax' );


// ─────────────────────────────────────────────────────────────────────