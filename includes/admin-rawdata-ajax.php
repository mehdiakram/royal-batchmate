<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * --- AJAX: Get Single Raw Data for Modal ---
 */
add_action( 'wp_ajax_BatchMate_get_raw_data_details', 'BatchMate_get_raw_data_details_ajax' );
add_action( 'wp_ajax_puaa_get_raw_data_details',       'BatchMate_get_raw_data_details_ajax' );
function BatchMate_get_raw_data_details_ajax() {
    if (!current_user_can('manage_options')) wp_send_json_error('Permission denied.');
    
    global $wpdb;
    $id = intval($_POST['id']);
    $table = $wpdb->prefix . 'batchmate_rawdata';
    $data = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $id));

    if ($data) {
        wp_send_json_success($data);
    } else {
        wp_send_json_error('Data not found.');
    }
}

/**
 * --- AJAX: Transfer Raw Data to Application ---
 */
add_action( 'wp_ajax_BatchMate_transfer_raw_to_app', 'BatchMate_transfer_raw_to_app_ajax' );
add_action( 'wp_ajax_puaa_transfer_raw_to_app',       'BatchMate_transfer_raw_to_app_ajax' );
function BatchMate_transfer_raw_to_app_ajax() {
    if (!current_user_can('manage_options')) wp_send_json_error('Permission denied.');
    
    check_ajax_referer('BatchMate_import_raw_action', '_wpnonce');

    global $wpdb;
    $raw_table = $wpdb->prefix . 'batchmate_rawdata';
    $app_table = $wpdb->prefix . 'batchmate_application';

    $raw_id = intval($_POST['raw_id']);
    $photo_url = sanitize_text_field($_POST['existing_photo_url']); 

    // ?. ??? ????? ??? ????????
    if (!empty($_FILES['app_photo']['name'])) {
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');

        $file = $_FILES['app_photo'];
        
        // --- ?.?: ??????? ???? (Cell-Name-Time) ---
        $cell_number = isset($_POST['cell_number']) ? sanitize_text_field($_POST['cell_number']) : 'unknown';
        $name        = isset($_POST['name']) ? sanitize_text_field($_POST['name']) : 'unknown';
        
        $safe_name = sanitize_title($name);
        $safe_cell = preg_replace('/[^0-9]/', '', $cell_number);
        $timestamp = time();
        
        $path_parts = pathinfo($file['name']);
        $extension = isset($path_parts['extension']) ? $path_parts['extension'] : 'jpg';
        
        $new_filename = "{$safe_cell}-{$safe_name}-{$timestamp}.{$extension}";
        $file['name'] = $new_filename;
        // -------------------------------------------

        $upload_overrides = array('test_form' => false);
        $movefile = wp_handle_upload($file, $upload_overrides);

        if ($movefile && !isset($movefile['error'])) {
            $filename = $movefile['file'];
            $filetype = wp_check_filetype(basename($filename), null);
            
            // ?.?: ??? ???x??? ???????? ?????? ???
            $image_editor = wp_get_image_editor($filename);
            if (!is_wp_error($image_editor)) {
                $image_editor->resize(400, 400, true); // 400x400 crop
                $image_editor->save($filename);
            }

            // ?.?: ?????? ??????????? ????? ???
            $attachment = array(
                'guid'           => $movefile['url'],
                'post_mime_type' => $filetype['type'],
                'post_title'     => sanitize_file_name(basename($filename)),
                'post_content'   => '',
                'post_status'    => 'inherit'
            );

            $attach_id = wp_insert_attachment($attachment, $filename);
            
            if (!is_wp_error($attach_id)) {
                $attach_data = wp_generate_attachment_metadata($attach_id, $filename);
                wp_update_attachment_metadata($attach_id, $attach_data);
                $photo_url = wp_get_attachment_url($attach_id);
            } else {
                $photo_url = $movefile['url'];
            }
        }
    }

    // ?. ?? ???? ???????? ???
    $app_data = array(
        'name'              => sanitize_text_field($_POST['name']),
        'email'             => sanitize_email($_POST['email']),
        'cell_number'       => sanitize_text_field($_POST['cell_number']),
        'cell_show'         => isset($_POST['cell_show']) ? 1 : 0,
        'land_phone_number' => sanitize_text_field($_POST['land_phone_number']),
        'blood_group'       => sanitize_text_field($_POST['blood_group']),
        'date_of_birth'     => sanitize_text_field($_POST['date_of_birth']),
        'gender'            => sanitize_text_field($_POST['gender']),
        'student_id'        => sanitize_text_field($_POST['student_id']),
        
        
        
        
        'present_address'   => sanitize_textarea_field($_POST['present_address']),
        'permanent_address' => sanitize_textarea_field($_POST['permanent_address']),
        'employment_position' => sanitize_text_field($_POST['employment_position']),
        'employment_address'  => sanitize_textarea_field($_POST['employment_address']),
        'facebook_url'      => esc_url_raw($_POST['facebook_url']),
        'linkedin_url'      => esc_url_raw($_POST['linkedin_url']),
        'x_url'             => esc_url_raw($_POST['x_url']),
        'website'           => esc_url_raw($_POST['website']),
        'payment_method'    => sanitize_text_field($_POST['payment_method']),
        'payment_details'   => sanitize_text_field($_POST['payment_details']),
        'photo_url'         => $photo_url,
        'status'            => 'pending', // Application ?????? ?????? 'pending'
        'submission_date'   => current_time('mysql')
    );

    // ?. Application ?????? ???????
    $inserted = $wpdb->insert($app_table, $app_data);

    if ($inserted) {
        // ?. Raw Data ?????? ????????? ??? ??? ????? ??? (FIXED)
        $wpdb->update(
            $raw_table,
            array(
                'status'    => 'Applied',
                'photo_url' => $photo_url // ???? ?? ???????? ???? ???? ????? ???
            ),
            array('id' => $raw_id),
            array('%s', '%s'), // ????????? ??? ???? ???? ?????
            array('%d')
        );
        wp_send_json_success('Application submitted successfully!');
    } else {
        wp_send_json_error('Database insert failed: ' . $wpdb->last_error);
    }
}

/**
 * --- ACTION: Export Raw Data to CSV ---
 */
add_action( 'admin_post_BatchMate_export_raw_data_action', 'batchmate_export_raw_data_csv' );
function batchmate_export_raw_data_csv() {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( 'Permission denied.' );
    }

    global $wpdb;
    $table = $wpdb->prefix . 'batchmate_rawdata';
    $results = $wpdb->get_results( "SELECT * FROM {$table} ORDER BY id DESC", ARRAY_A );

    header( 'Content-Type: text/csv; charset=utf-8' );
    header( 'Content-Disposition: attachment; filename=batchmate-raw-data-' . date('Y-m-d') . '.csv' );

    $output = fopen( 'php://output', 'w' );
    if ( ! empty( $results ) ) {
        fputcsv( $output, array_keys( $results[0] ) );
        foreach ( $results as $row ) {
            fputcsv( $output, $row );
        }
    } else {
        fputcsv( $output, array('id', 'student_id', 'name', 'cell_number', 'email', 'status', 'submission_date') );
    }
    fclose( $output );
    exit;
}