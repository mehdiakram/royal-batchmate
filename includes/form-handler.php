<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! function_exists( 'pua_handle_registration_submission' ) ) {
function pua_handle_registration_submission() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'batchmate_application';

    // --- 1. Security Check ---
    // Nonce check can fail for guest users due to page caching plugins.
    if ( is_user_logged_in() && ( ! isset( $_POST['pua_registration_nonce'] ) || ! wp_verify_nonce( $_POST['pua_registration_nonce'], 'pua_registration_action' ) ) ) {
        wp_die( 'Security check failed. Please try again.' );
    }

    // --- Bot Protection: Honeypot Check ---
    if ( ! empty( $_POST['website_url'] ) ) {
        // Honeypot field was filled - likely a bot
        wp_die( 'Security check failed. Bot submission detected.' );
    }

    // --- Bot Protection: Time-based Validation ---
    $form_time = isset( $_POST['_pua_form_time'] ) ? intval( $_POST['_pua_form_time'] ) : 0;
    $time_diff = time() - $form_time;
    if ( $time_diff < 3 ) {
        // Form submitted too quickly - likely a bot
        wp_die( 'Security check failed. Please take your time to fill out the form.' );
    }

    // --- 2. Handle File Upload ---
    $photo_url = '';
    if ( ! empty( $_FILES['reg_photo']['name'] ) ) {
        if ( ! function_exists( 'wp_handle_upload' ) ) require_once( ABSPATH . 'wp-admin/includes/file.php' );

        $uploaded_file = $_FILES['reg_photo'];
        
        // --- RENAMING LOGIC: Roll No-name-time ---
        $path_parts = pathinfo( $uploaded_file['name'] );
        $roll_no_safe = sanitize_title( $_POST['reg_roll_no'] );
        $name_slug = sanitize_title( $_POST['reg_name'] );
        $timestamp = time(); 
        $extension = strtolower( $path_parts['extension'] ?? 'jpg' );
        
        $uploaded_file['name'] = "{$roll_no_safe}-{$name_slug}-{$timestamp}.{$extension}";
        $_FILES['reg_photo'] = $uploaded_file;

        // Temporarily lift WordPress upload size restriction (crop makes it tiny anyway)
        add_filter( 'upload_size_limit', 'BatchMate_override_upload_size_limit' );
        $upload_overrides = array( 'test_form' => false );
        $movefile = wp_handle_upload( $uploaded_file, $upload_overrides );
        remove_filter( 'upload_size_limit', 'BatchMate_override_upload_size_limit' );

        if ( $movefile && isset( $movefile['file'] ) ) {
            // Center-crop to exactly 200×200 px using shared helper
            $cropped = function_exists( 'BatchMate_crop_photo_400' )
                ? BatchMate_crop_photo_400( $movefile['file'] )
                : BatchMate_crop_photo_200( $movefile['file'] );

            if ( ! $cropped ) {
                @unlink( $movefile['file'] );
                wp_die( 'Photo upload error: Unable to resize image to 400x400.' );
            }
            $photo_url = $movefile['url'];
        } elseif ( isset( $movefile['error'] ) ) {
            // Provide a user-friendly message without exposing raw byte limits
            $err = $movefile['error'];
            if ( strpos( $err, 'exceeds' ) !== false ) {
                $err = 'The photo file is too large for the server. Please try a smaller image.';
            }
            wp_die( 'Photo upload error: ' . esc_html( $err ) );
        }
    }


    // --- Skills Processing ---
    $skills = isset( $_POST['reg_skills'] ) ? sanitize_text_field( $_POST['reg_skills'] ) : '';
    $skills_json = null;
    if ( ! empty( $skills ) ) {
        $skills_arr = array_map( 'trim', explode( ',', $skills ) );
        $skills_arr = array_filter( $skills_arr ); // remove empty
        if ( ! empty( $skills_arr ) ) {
            $skills_json = wp_json_encode( array_values( $skills_arr ) );
        }
    }

    // --- 3. Data Sanitization and Preparation ---
    $passing_year = sanitize_text_field( $_POST['reg_passing_year'] ?? '' );
    
    $data_to_insert = array(
        'name' => sanitize_text_field( $_POST['reg_name'] ),
        'name_bn' => sanitize_text_field( $_POST['reg_name_bn'] ?? '' ),
        'email' => sanitize_email( $_POST['reg_email'] ?? '' ),
        'cell_number' => sanitize_text_field( $_POST['reg_cell_number'] ),
        'cell_show' => isset( $_POST['reg_cell_show'] ) ? 1 : 0,
        'land_phone_number' => sanitize_text_field( $_POST['reg_land_phone'] ?? '' ),
        'blood_group' => sanitize_text_field( $_POST['reg_blood_group'] ),
        'date_of_birth' => sanitize_text_field( $_POST['reg_dob'] ),
        'gender' => sanitize_text_field( $_POST['reg_gender'] ),
        'religion' => sanitize_text_field( $_POST['reg_religion'] ?? '' ),
        
        // New Academic Info
        'board'           => sanitize_text_field( $_POST['reg_board'] ),
        'roll_no'         => sanitize_text_field( $_POST['reg_roll_no'] ),
        'registration_no' => sanitize_text_field( $_POST['reg_registration_no'] ?? '' ),
        'edu_group'       => sanitize_text_field( $_POST['reg_group'] ),
        'institution'     => sanitize_text_field( $_POST['reg_institution'] ),
        
        // Keep old fields empty to avoid DB strict mode issues, save passing year as batch
        'student_id' => '',
        
         
        
        
        
        'present_address' => sanitize_textarea_field( $_POST['reg_present_address'] ),
        'permanent_address' => sanitize_textarea_field( $_POST['reg_permanent_address'] ),
        'present_district'   => sanitize_text_field( $_POST['reg_present_district'] ?? '' ),
        'present_upazila'    => sanitize_text_field( $_POST['reg_present_upazila'] ?? '' ),
        'present_country'    => sanitize_text_field( $_POST['reg_present_country'] ?? 'Bangladesh' ),
        'permanent_district' => sanitize_text_field( $_POST['reg_permanent_district'] ?? '' ),
        'upazila'            => sanitize_text_field( $_POST['reg_permanent_upazila'] ?? $_POST['reg_upazila'] ?? '' ),
        'permanent_country'  => sanitize_text_field( $_POST['reg_permanent_country'] ?? 'Bangladesh' ),
        
        'employment_position' => sanitize_text_field( $_POST['reg_position'] ),
        'employment_address' => sanitize_textarea_field( $_POST['reg_employment_address'] ),
        'organization' => sanitize_text_field( $_POST['reg_organization'] ?? '' ),
        'industry' => sanitize_text_field( $_POST['reg_industry'] ?? '' ),
        'work_experience' => sanitize_text_field( $_POST['reg_work_experience'] ?? '' ),
        'previous_organizations' => sanitize_textarea_field( $_POST['reg_previous_organizations'] ?? '' ),
        
        'about_me' => sanitize_textarea_field( $_POST['reg_about_me'] ?? '' ),
        'skills_json' => $skills_json,
        
        'facebook_url' => esc_url_raw( $_POST['reg_facebook'] ),
        'linkedin_url' => esc_url_raw( $_POST['reg_linkedin'] ),
        'x_url' => esc_url_raw( $_POST['reg_x'] ),
        'website' => esc_url_raw( $_POST['reg_website'] ),
        'photo_url' => $photo_url,
        
        'payment_method' => sanitize_text_field( $_POST['reg_payment_method'] ?? '' ),
        'payment_details' => sanitize_text_field( $_POST['reg_payment_details'] ?? '' ),
        
        'status' => 'pending', 
        'submission_date' => current_time( 'mysql' ),
    );
    
    // --- 3a. Check if Roll No + Board already belongs to an existing member ---
    $roll_no_to_check = $data_to_insert['roll_no'];
    $board_to_check = $data_to_insert['board'];
    if ( ! empty( $roll_no_to_check ) && ! empty( $board_to_check ) ) {
        $existing_member = $wpdb->get_row( $wpdb->prepare(
            "SELECT member_number FROM {$wpdb->prefix}batchmate_member WHERE roll_no = %s AND board = %s LIMIT 1",
            $roll_no_to_check, $board_to_check
        ) );
        if ( $existing_member ) {
            $redirect_url = wp_get_referer();
            if ( ! $redirect_url ) $redirect_url = home_url('/');
            $redirect_url = add_query_arg( array(
                'submission'  => 'already_member',
                'member_num'  => urlencode( $existing_member->member_number ),
                'member_slug' => urlencode( strtolower( $existing_member->member_number ) ),
            ), $redirect_url );
            wp_redirect( esc_url( $redirect_url ) );
            exit;
        }
    }

    // --- 4. Database Insert ---
    $result = $wpdb->insert( $table_name, $data_to_insert );

    // --- 5. Send Email & Redirect ---
    if ( $result ) {
        $settings = get_option('batchmate_settings', array());
        $to = !empty($settings['form_mail_to']) ? $settings['form_mail_to'] : 'info@batchmate.org';
        $subject = 'New Batchmate Registration Application: ' . $data_to_insert['name'];
        
        $headers = array('Content-Type: text/html; charset=UTF-8');
        
        $message = '<html><body>';
        $message .= '<h2>New Member Application Received</h2>';
        $message .= '<table border="1" cellpadding="10" cellspacing="0" style="border-collapse: collapse; width: 100%; max-width: 600px;">';
        
        if ( ! empty( $data_to_insert['photo_url'] ) ) {
            $message .= '<tr><td colspan="2" style="text-align: center; background: #f9f9f9;"><img src="' . esc_url( $data_to_insert['photo_url'] ) . '" alt="Applicant Photo" style="max-width: 150px; border: 1px solid #ddd; padding: 5px;"></td></tr>';
        }

        $message .= '<tr><td width="40%"><strong>Name</strong></td><td>' . esc_html( $data_to_insert['name'] ) . '</td></tr>';
        $message .= '<tr><td><strong>Email</strong></td><td>' . ( ! empty( $data_to_insert['email'] ) ? esc_html( $data_to_insert['email'] ) : '<em>(Not provided - will be auto-generated)</em>' ) . '</td></tr>';
        $message .= '<tr><td><strong>Phone</strong></td><td>' . esc_html( $data_to_insert['cell_number'] ) . '</td></tr>';
        $message .= '<tr><td><strong>Blood Group</strong></td><td>' . esc_html( $data_to_insert['blood_group'] ) . '</td></tr>';
        $message .= '<tr><td><strong>Gender</strong></td><td>' . esc_html( $data_to_insert['gender'] ) . '</td></tr>';
        $message .= '<tr><td><strong>Date of Birth</strong></td><td>' . esc_html( $data_to_insert['date_of_birth'] ) . '</td></tr>';
        
        $message .= '<tr><td colspan="2" style="background-color: #eee;"><strong>Academic Info</strong></td></tr>';
        $message .= '<tr><td><strong>Board</strong></td><td>' . esc_html( ucfirst( $data_to_insert['board'] ) ) . '</td></tr>';
        $message .= '<tr><td><strong>Roll No</strong></td><td>' . esc_html( $data_to_insert['roll_no'] ) . '</td></tr>';
        $message .= '<tr><td><strong>Registration No</strong></td><td>' . esc_html( $data_to_insert['registration_no'] ) . '</td></tr>';
        $message .= '<tr><td><strong>Group</strong></td><td>' . esc_html( ucfirst( $data_to_insert['edu_group'] ) ) . '</td></tr>';
        $message .= '<tr><td><strong>Institution</strong></td><td>' . esc_html( $data_to_insert['institution'] ) . '</td></tr>';
        $message .= '<tr><td><strong>Passing Year</strong></td><td>' . esc_html( $data_to_insert['batch'] ) . '</td></tr>';
        
        $message .= '<tr><td colspan="2" style="background-color: #eee;"><strong>Payment Info</strong></td></tr>';
        $message .= '<tr><td><strong>Method</strong></td><td>' . esc_html( $data_to_insert['payment_method'] ) . '</td></tr>';
        $message .= '<tr><td><strong>TrxID / Details</strong></td><td>' . esc_html( $data_to_insert['payment_details'] ) . '</td></tr>';
        
        $message .= '</table>';
        $message .= '<p><a href="' . admin_url('admin.php?page=batchmate_applications') . '" style="background: #007cba; color: #fff; padding: 10px 15px; text-decoration: none; display: inline-block; margin-top: 20px;">Manage Applications</a></p>';
        $message .= '</body></html>';

        wp_mail( $to, $subject, $message, $headers );
        
        $redirect_url = wp_get_referer();
        if ( ! $redirect_url ) $redirect_url = home_url('/');
        $redirect_url = add_query_arg( 'submission', 'success', $redirect_url );
        wp_redirect( esc_url( $redirect_url ) );
        exit;

    } else {
        $redirect_url = wp_get_referer();
        if ( ! $redirect_url ) $redirect_url = home_url('/');
        $redirect_url = add_query_arg( 'submission', 'error', $redirect_url );
        wp_redirect( esc_url( $redirect_url ) );
        exit;
    }
}
}
add_action( 'admin_post_nopriv_pua_handle_registration', 'pua_handle_registration_submission' );
add_action( 'admin_post_pua_handle_registration', 'pua_handle_registration_submission' );


// ─────────────────────────────────────────────────────────────────────
// AJAX: Check if a Roll No + Board combo already exists in the member table.
// Used by the public registration form for live feedback.
// ─────────────────────────────────────────────────────────────────────
function BatchMate_check_roll_no_member_ajax() {
    global $wpdb;

    $roll_no = sanitize_text_field( $_POST['roll_no'] ?? '' );
    $board = sanitize_text_field( $_POST['board'] ?? '' );
    if ( empty( $roll_no ) || empty( $board ) ) {
        wp_send_json_success( array( 'is_member' => false ) );
    }

    $member = $wpdb->get_row( $wpdb->prepare(
        "SELECT member_number FROM {$wpdb->prefix}batchmate_member WHERE roll_no = %s AND board = %s LIMIT 1",
        $roll_no, $board
    ) );

    if ( $member ) {
        wp_send_json_success( array(
            'is_member'    => true,
            'member_number'=> $member->member_number,
            'member_slug'  => strtolower( $member->member_number ),
        ) );
    } else {
        wp_send_json_success( array( 'is_member' => false ) );
    }
}
add_action( 'wp_ajax_BatchMate_check_roll_no_member', 'BatchMate_check_roll_no_member_ajax' );
add_action( 'wp_ajax_puaa_check_roll_no_member',       'BatchMate_check_roll_no_member_ajax' );
add_action( 'wp_ajax_nopriv_puaa_check_roll_no_member', 'BatchMate_check_roll_no_member_ajax' );