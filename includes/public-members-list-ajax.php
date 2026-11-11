<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

function BatchMate_get_public_members_list_ajax() {
    global $wpdb;

    $table_member = $wpdb->prefix . 'batchmate_member';
    $table_dept   = $wpdb->prefix . 'batchmate_department';
    $table_prog   = $wpdb->prefix . 'batchmate_program';

    $start = intval( $_POST['start'] ?? 0 );
    $length = intval( $_POST['length'] ?? 10 );
    if ( $length == -1 ) $length = 999999999;
    $search_value = isset($_POST['search']['value']) ? sanitize_text_field( $_POST['search']['value'] ) : '';
    
    $order_column_index = intval( $_POST['order'][0]['column'] ?? 0 );
    $order_dir = (isset($_POST['order'][0]['dir']) && $_POST['order'][0]['dir'] === 'asc') ? 'ASC' : 'DESC';
    $columns = array(
        0 => 'm.member_number', 1 => 'm.name', 2 => 'm.photo_url', 3 => 'm.batch',
        4 => 'm.institution', 5 => 'm.permanent_district', 6 => 'm.employment_position', 7 => 'm.blood_group',
        8 => 'm.cell_number', 9 => 'm.email'
    );
    $order_column = $columns[$order_column_index] ?? 'm.member_number';

    // Filter parameters
    $filter_district   = isset( $_POST['filter_district'] )   ? sanitize_text_field( $_POST['filter_district'] ) : '';
    $filter_profession = isset( $_POST['filter_profession'] ) ? sanitize_text_field( $_POST['filter_profession'] ) : '';
    $filter_bloodgroup = isset( $_POST['filter_bloodgroup'] ) ? sanitize_text_field( $_POST['filter_bloodgroup'] ) : '';

    $sql = "FROM $table_member AS m";
    $where_parts = [];

    if ( $filter_district !== '' ) {
        $where_parts[] = $wpdb->prepare( 'm.permanent_district = %s', $filter_district );
    }
    
    if ( $filter_profession !== '' ) {
        $where_parts[] = $wpdb->prepare( 'm.employment_position = %s', $filter_profession );
    }

    if ( $filter_bloodgroup !== '' ) {
        $where_parts[] = $wpdb->prepare( 'm.blood_group = %s', $filter_bloodgroup );
    }

    if ( $search_value !== '' ) {
        $search_term = '%' . $wpdb->esc_like( $search_value ) . '%';
        $where_parts[] = $wpdb->prepare(
            "(m.name LIKE %s OR m.member_number LIKE %s OR m.blood_group LIKE %s)",
            $search_term, $search_term, $search_term
        );
    }

    if ( $where_parts ) {
        $sql .= ' WHERE ' . implode( ' AND ', $where_parts );
    }

    $records_total = $wpdb->get_var( "SELECT COUNT(m.id) FROM $table_member AS m" );
    $records_filtered = $wpdb->get_var( "SELECT COUNT(m.id) $sql" );

    $data_sql = "SELECT m.* $sql ORDER BY $order_column $order_dir LIMIT %d, %d";
    $data = $wpdb->get_results( $wpdb->prepare( $data_sql, $start, $length ), ARRAY_A );

    $formatted_data = array();
    if ($data) {
        foreach ( $data as $row ) {
            if (empty($row['photo_url'])) {
                $row['photo_url'] = plugin_dir_url( dirname(__FILE__) ) . 'assets/images/noprofile.png';
            }
            $row['profile_url'] = home_url( '/member/' . strtolower( $row['member_number'] ) . '/' );
            if ($row['cell_show'] != 1) {
                $row['cell_number'] = 'Hidden';
            }
            $formatted_data[] = $row;
        }
    }

    $response = array(
        "draw"            => intval( $_POST['draw'] ?? 1 ),
        "recordsTotal"    => intval( $records_total ),
        "recordsFiltered" => intval( $records_filtered ),
        "data"            => $formatted_data
    );

    wp_send_json( $response );
}
add_action( 'wp_ajax_nopriv_get_public_members', 'BatchMate_get_public_members_list_ajax' );
add_action( 'wp_ajax_get_public_members', 'BatchMate_get_public_members_list_ajax' );


/**
 * Public endpoint: Get programs list for the public members filter dropdown
 */
function BatchMate_get_public_programs_for_filter_ajax() {
    global $wpdb;
    $programs = $wpdb->get_results(
        "SELECT id, dept_id, program_name FROM {$wpdb->prefix}batchmate_program WHERE is_active = 1 ORDER BY program_name ASC"
    );
    if ( $programs ) {
        wp_send_json_success( $programs );
    } else {
        wp_send_json_success( [] );
    }
}
add_action( 'wp_ajax_nopriv_puaa_get_public_programs_for_filter', 'BatchMate_get_public_programs_for_filter_ajax' );
add_action( 'wp_ajax_BatchMate_get_public_programs_for_filter', 'BatchMate_get_public_programs_for_filter_ajax' );
add_action( 'wp_ajax_puaa_get_public_programs_for_filter',       'BatchMate_get_public_programs_for_filter_ajax' );
