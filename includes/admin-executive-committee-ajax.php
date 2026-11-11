<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

function BatchMate_get_committees_ajax() {
    check_ajax_referer( 'batchmate_ec_nonce' );
    if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Permission denied.', 403 );
    global $wpdb;
    $committee_table = $wpdb->prefix . 'batchmate_committee';
    $ec_table = $wpdb->prefix . 'batchmate_ec';

    // FIX: Add COUNT for members in each committee from batchmate_ec table
    $data = $wpdb->get_results( $wpdb->prepare(
        "SELECT c.*, COUNT(ec.id) as member_count
         FROM %i c
         LEFT JOIN %i ec ON c.id = ec.committee_id
         GROUP BY c.id
         ORDER BY c.id DESC",
         $committee_table, $ec_table
    ) );
    wp_send_json( array( 'data' => $data ? $data : [] ) );
}
add_action( 'wp_ajax_BatchMate_get_committees', 'BatchMate_get_committees_ajax' );
add_action( 'wp_ajax_puaa_get_committees',       'BatchMate_get_committees_ajax' );


function BatchMate_save_committee_ajax() {
    // ???? ???? ?????
    check_ajax_referer( 'batchmate_ec_nonce' );
    if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Permission denied.', 403 );
    if ( ! isset( $_POST['session_year'] ) || empty( $_POST['session_year'] ) ) wp_send_json_error( 'Session Year is required.' );
    global $wpdb;
    $table = $wpdb->prefix . 'batchmate_committee';
    $id = intval( $_POST['id'] );
    $data = array( 'session_year' => sanitize_text_field( $_POST['session_year'] ), 'start_date' => empty( $_POST['start_date'] ) ? null : sanitize_text_field( $_POST['start_date'] ), 'end_date' => empty( $_POST['end_date'] ) ? null : sanitize_text_field( $_POST['end_date'] ), 'is_active' => intval( $_POST['is_active'] ) );
    $format = array( '%s', '%s', '%s', '%d' );
    if ( $data['is_active'] == 1 ) $wpdb->query( "UPDATE $table SET is_active = 0 WHERE id != $id" );
    if ( $id > 0 ) $result = $wpdb->update( $table, $data, array( 'id' => $id ), $format, array( '%d' ) );
    else $result = $wpdb->insert( $table, $data, $format );
    if ( $result === false ) wp_send_json_error( 'Could not save committee. Database error.' );
    wp_send_json_success( 'Committee saved.' );
}
add_action( 'wp_ajax_BatchMate_save_committee', 'BatchMate_save_committee_ajax' );
add_action( 'wp_ajax_puaa_save_committee',       'BatchMate_save_committee_ajax' );

function BatchMate_get_ec_posts_ajax() {
    check_ajax_referer( 'batchmate_ec_nonce' );
    if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Permission denied.', 403 );
    global $wpdb;
    $post_table = $wpdb->prefix . 'batchmate_ec_post_title';
    $data = $wpdb->get_results( $wpdb->prepare( "SELECT p.id, p.post_title, p.position_order, p.position_row FROM %i p ORDER BY p.position_order ASC, p.post_title ASC", $post_table ) );
    wp_send_json( array( 'data' => $data ? $data : [] ) );
}
add_action( 'wp_ajax_BatchMate_get_ec_posts', 'BatchMate_get_ec_posts_ajax' );
add_action( 'wp_ajax_puaa_get_ec_posts',       'BatchMate_get_ec_posts_ajax' );

function BatchMate_save_ec_post_ajax() {
    check_ajax_referer( 'batchmate_ec_nonce' );
    if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Permission denied.', 403 );
    if ( empty( $_POST['post_title'] ) ) wp_send_json_error( 'Post Title is required.' );
    global $wpdb;
    $table = $wpdb->prefix . 'batchmate_ec_post_title';
    $id = intval( $_POST['id'] );
    $data = array( 'post_title' => sanitize_text_field( $_POST['post_title'] ), 'position_order' => intval( $_POST['position_order'] ), 'position_row' => intval( $_POST['position_row'] ) );
    $format = array( '%s', '%d', '%d' );
    if ( $id > 0 ) $result = $wpdb->update( $table, $data, array( 'id' => $id ), $format, array( '%d' ) );
    else $result = $wpdb->insert( $table, $data, $format );
    if ( $result === false ) { if ( $wpdb->last_error && str_contains( $wpdb->last_error, 'post_title_uk' ) ) wp_send_json_error( 'A post with this title already exists.' ); wp_send_json_error( 'Could not save post. Database error: ' . $wpdb->last_error ); }
    wp_send_json_success( 'Post saved.' );
}
add_action( 'wp_ajax_BatchMate_save_ec_post', 'BatchMate_save_ec_post_ajax' );
add_action( 'wp_ajax_puaa_save_ec_post',       'BatchMate_save_ec_post_ajax' );


// --- ?. ??????????? ??????? (Member) ???????????? (Updated) ---
// ????? EC ????? ???? dept_id ??????? ??? ??? ??, ??????? ????? ????? ?????
// ?????? ??? ??????? ?????? dept_id ???, ??? JOIN ????? ???? ????
function BatchMate_get_ec_members_ajax() {
    check_ajax_referer( 'batchmate_ec_nonce' );
    if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Permission denied.', 403 );

    global $wpdb;
    $ec_table = $wpdb->prefix . 'batchmate_ec';
    $member_table = $wpdb->prefix . 'batchmate_member';
    $comm_table = $wpdb->prefix . 'batchmate_committee';
    $post_table = $wpdb->prefix . 'batchmate_ec_post_title';

    // ????? ???????????? ??????? ???? ???????? JOIN ????? ?? ???? EC ?????? ??????? ??? ?? ?????? ?????, 
    // ??? ??? ???????????? ?????? ??? ??? ????? ?? ???? ???:
    // $prog_table = $wpdb->prefix . 'batchmate_program';
    // $dept_table = $wpdb->prefix . 'batchmate_department';
    // ????? JOIN ??? SELECT ? ????? ???? ???? ???????? ?? ??? ??? ???? ??? ???? EC ??????? ?????? ???????????? ???? ????
    
    $data = $wpdb->get_results( $wpdb->prepare(
        "SELECT ec.id, ec.committee_id, ec.member_id, ec.post_id, 
                m.name as member_name, c.session_year, p.post_title, p.position_order
         FROM %i ec
         LEFT JOIN %i m ON ec.member_id = m.id
         LEFT JOIN %i c ON ec.committee_id = c.id
         LEFT JOIN %i p ON ec.post_id = p.id
         ORDER BY c.session_year DESC, p.position_order ASC", 
         $ec_table, $member_table, $comm_table, $post_table
    ) );
    wp_send_json( array( 'data' => $data ? $data : [] ) );
}
add_action( 'wp_ajax_BatchMate_get_ec_members', 'BatchMate_get_ec_members_ajax' );
add_action( 'wp_ajax_puaa_get_ec_members',       'BatchMate_get_ec_members_ajax' );

function BatchMate_save_ec_member_ajax() {
    // ???? ???? ?????
    check_ajax_referer( 'batchmate_ec_nonce' );
    if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Permission denied.', 403 );
    if ( empty( $_POST['committee_id'] ) || empty( $_POST['member_id'] ) || empty( $_POST['post_id'] ) ) wp_send_json_error( 'Committee, Member, and Post are required.' );
    global $wpdb;
    $table = $wpdb->prefix . 'batchmate_ec';
    $id = intval( $_POST['id'] );
    $data = array( 'committee_id' => intval( $_POST['committee_id'] ), 'member_id' => intval( $_POST['member_id'] ), 'post_id' => intval( $_POST['post_id'] ) );
    $format = array( '%d', '%d', '%d' );
    if ( $id > 0 ) $result = $wpdb->update( $table, $data, array( 'id' => $id ), $format, array( '%d' ) );
    else $result = $wpdb->insert( $table, $data, $format );
    if ( $result === false ) { if ( $wpdb->last_error && str_contains( $wpdb->last_error, 'committee_member_uk' ) ) wp_send_json_error( 'This member is already assigned to this committee.' ); wp_send_json_error( 'Could not save member. Database error.' ); }
    wp_send_json_success( 'EC Member saved.' );
}
add_action( 'wp_ajax_BatchMate_save_ec_member', 'BatchMate_save_ec_member_ajax' );
add_action( 'wp_ajax_puaa_save_ec_member',       'BatchMate_save_ec_member_ajax' );


// --- ?. ???????? ??????? (AJAX) ---
function BatchMate_get_committees_for_dropdown_ajax() {
    check_ajax_referer( 'batchmate_ec_nonce' );
    if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Permission denied.', 403 );
    global $wpdb;
    $table = $wpdb->prefix . 'batchmate_committee';
    $data = $wpdb->get_results( "SELECT id, session_year FROM $table ORDER BY session_year DESC" );
    wp_send_json_success( $data ? $data : [] );
}
add_action( 'wp_ajax_BatchMate_get_committees_for_dropdown', 'BatchMate_get_committees_for_dropdown_ajax' );
add_action( 'wp_ajax_puaa_get_committees_for_dropdown',       'BatchMate_get_committees_for_dropdown_ajax' );

function BatchMate_get_posts_for_dropdown_ajax() {
    check_ajax_referer( 'batchmate_ec_nonce' );
    if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Permission denied.', 403 );
    global $wpdb;
    $table = $wpdb->prefix . 'batchmate_ec_post_title';
    $data = $wpdb->get_results( "SELECT id, post_title FROM $table ORDER BY position_order ASC" );
    wp_send_json_success( $data ? $data : [] );
}
add_action( 'wp_ajax_BatchMate_get_posts_for_dropdown', 'BatchMate_get_posts_for_dropdown_ajax' );
add_action( 'wp_ajax_puaa_get_posts_for_dropdown',       'BatchMate_get_posts_for_dropdown_ajax' );

function BatchMate_get_members_for_dropdown_ajax() {
    check_ajax_referer( 'batchmate_ec_nonce' );
    if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Permission denied.', 403 );
    global $wpdb;
    $table = $wpdb->prefix . 'batchmate_member';
    $data = $wpdb->get_results( "SELECT id, CONCAT(name, ' (', member_number, ')') as text FROM $table ORDER BY name ASC" );
    wp_send_json_success( $data ? $data : [] );
}
add_action( 'wp_ajax_BatchMate_get_members_for_dropdown', 'BatchMate_get_members_for_dropdown_ajax' );
add_action( 'wp_ajax_puaa_get_members_for_dropdown',       'BatchMate_get_members_for_dropdown_ajax' );