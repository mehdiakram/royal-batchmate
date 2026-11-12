<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * --- AJAX HANDLER: Get Applications ---
 */
function BatchMate_get_applications_ajax() {
    check_ajax_referer( 'BatchMate_app_nonce' );
    if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Permission denied.', 403 );

    global $wpdb;
    $app_table  = $wpdb->prefix . 'batchmate_application';
    $dept_table = $wpdb->prefix . 'batchmate_department';
    $prog_table = $wpdb->prefix . 'batchmate_program';

    $applications = $wpdb->get_results( $wpdb->prepare(
        "SELECT app.id, app.name, app.email, app.cell_number, app.payment_method, app.payment_details, app.status, d.dept_name
         FROM %i app
         LEFT JOIN %i p ON app.program_id = p.id
         LEFT JOIN %i d ON p.dept_id = d.id
         ORDER BY app.id DESC",
         $app_table, $prog_table, $dept_table
    ) );

    wp_send_json( array( 'data' => $applications ? $applications : [] ) );
}
add_action( 'wp_ajax_BatchMate_get_applications', 'BatchMate_get_applications_ajax' );
// Backward-compat alias (old puaa_ action)
add_action( 'wp_ajax_BatchMate_get_applications', 'BatchMate_get_applications_ajax' );
add_action( 'wp_ajax_puaa_get_applications',       'BatchMate_get_applications_ajax' );


/**
 * --- AJAX HANDLER: Get Next Member Number ---
 */
function BatchMate_get_next_member_number_ajax() {
    check_ajax_referer( 'BatchMate_app_nonce' );
    if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Permission denied.', 403 );

    global $wpdb;
    $member_table = $wpdb->prefix . 'batchmate_member';

    // Use MAX(id) so gaps from deletions don't cause duplicate numbers
    $max_id      = (int) $wpdb->get_var( "SELECT COALESCE( MAX(id), 0 ) FROM $member_table" );
    $next_number = $max_id + 1;

    $formatted_number = batchmate_generate_member_id( $next_number );
    wp_send_json_success( $formatted_number );
}
add_action( 'wp_ajax_BatchMate_get_next_member_number', 'BatchMate_get_next_member_number_ajax' );
add_action( 'wp_ajax_BatchMate_get_next_member_number', 'BatchMate_get_next_member_number_ajax' );
add_action( 'wp_ajax_puaa_get_next_member_number',       'BatchMate_get_next_member_number_ajax' );


/**
 * --- AJAX HANDLER: Check Member Email ---
 */
function BatchMate_check_member_email_ajax() {
    check_ajax_referer( 'BatchMate_app_nonce' );
    if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Permission denied.', 403 );
    if ( ! isset( $_POST['email'] ) || ! is_email( $_POST['email'] ) ) wp_send_json_error( 'Invalid email.' );

    global $wpdb;
    $member_table = $wpdb->prefix . 'batchmate_member';
    $email        = sanitize_email( $_POST['email'] );
    $exists       = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(id) FROM $member_table WHERE email = %s", $email ) );
    wp_send_json_success( $exists > 0 );
}
add_action( 'wp_ajax_BatchMate_check_member_email', 'BatchMate_check_member_email_ajax' );
add_action( 'wp_ajax_BatchMate_check_member_email', 'BatchMate_check_member_email_ajax' );
add_action( 'wp_ajax_puaa_check_member_email',       'BatchMate_check_member_email_ajax' );


/**
 * --- AJAX HANDLER: Approve Application ---
 */
function BatchMate_approve_application_ajax() {
    check_ajax_referer( 'BatchMate_app_nonce' );

    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( 'Permission denied.', 403 );
    }

    global $wpdb;
    $application_id = intval( $_POST['application_id'] );
    $member_number  = sanitize_text_field( $_POST['member_number'] );
    $new_email      = isset( $_POST['new_email'] ) ? sanitize_email( $_POST['new_email'] ) : null;
    $create_user    = ( isset( $_POST['create_user'] ) && $_POST['create_user'] === 'true' );

    if ( empty( $application_id ) || empty( $member_number ) ) {
        wp_send_json_error( 'Application ID or Member Number is missing.' );
    }

    // 1. Fetch application data
    $app_table   = $wpdb->prefix . 'batchmate_application';
    $application = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $app_table WHERE id = %d", $application_id ), ARRAY_A );

    if ( ! $application ) {
        wp_send_json_error( 'Application not found.' );
    }

    if ( $application['status'] === 'approved' ) {
        wp_send_json_error( 'This application has already been approved.' );
    }

    $email_to_use   = $new_email ? $new_email : $application['email'];
    if ( empty( $email_to_use ) && function_exists( 'batchmate_generate_member_email' ) ) {
        $email_to_use = batchmate_generate_member_email( $member_number );
    } elseif ( empty( $email_to_use ) ) {
        $email_to_use = strtolower( $member_number ) . '@batchmate.org';
    }
    $applicant_name = $application['name'];

    $wp_user_id          = 0;
    $send_new_user_email = false;
    $password            = '';
    $username            = '';

    // 2. Create WP user if requested
    if ( $create_user ) {
        if ( email_exists( $email_to_use ) ) {
            wp_send_json_error( 'A WordPress user with the email "' . $email_to_use . '" already exists.' );
        } else {
            $password = wp_generate_password( 12, false );
            $username = sanitize_user( strtolower( $member_number ), true );

            if ( username_exists( $username ) ) {
                wp_send_json_error( 'Could not create user: Username exists.' );
            }

            $wp_user_id = wp_create_user( $username, $password, $email_to_use );

            if ( is_wp_error( $wp_user_id ) ) {
                wp_send_json_error( 'Could not create WordPress user: ' . $wp_user_id->get_error_message() );
            }

            wp_update_user( array(
                'ID'           => $wp_user_id,
                'display_name' => $applicant_name,
                'first_name'   => explode( ' ', $applicant_name, 2 )[0],
                'last_name'    => isset( explode( ' ', $applicant_name, 2 )[1] ) ? explode( ' ', $applicant_name, 2 )[1] : '',
            ) );

            $send_new_user_email = true;
        }
    }

    // 3. DB transaction
    $wpdb->query( 'START TRANSACTION' );

    try {
        // 4. Insert into batchmate_member
        $member_table = $wpdb->prefix . 'batchmate_member';

        unset( $application['id'] );
        unset( $application['payment_method'] );
        unset( $application['payment_details'] );
        unset( $application['status'] );
        unset( $application['submission_date'] );

        $application['application_id'] = $application_id;
        $application['wp_user_id']     = $wp_user_id;
        $application['member_number']  = $member_number;
        $application['email']          = $email_to_use;
        $application['joining_date']   = current_time( 'mysql' );

        $result_member = $wpdb->insert( $member_table, $application );

        if ( $result_member === false ) {
            throw new Exception( 'Database Error: Could not save member data. ' . $wpdb->last_error );
        }

        // 5. Update application status to approved
        $result_app_update = $wpdb->update(
            $app_table,
            array( 'status' => 'approved' ),
            array( 'id'     => $application_id ),
            array( '%s' ),
            array( '%d' )
        );

        if ( $result_app_update === false ) {
            throw new Exception( 'Could not update application status.' );
        }

        // 6. Commit
        $wpdb->query( 'COMMIT' );

        // 7. Send email
        $email_message  = '';
        $site_url       = home_url( '/' );
        $profile_url    = $site_url . 'member/' . strtolower( $member_number ) . '/';
        $subject        = 'Your BatchMate Member Application is Approved!';

        $body  = '<p>Hello ' . $applicant_name . ',</p>';
        $body .= '<p>Congratulations! Your application for the BatchMate membership has been approved.</p>';
        $body .= '<p>Your Member ID is: <strong>' . $member_number . '</strong></p>';

        if ( $send_new_user_email ) {
            $body .= '<p>A user account has been created for you. You can log in to the website with these credentials:</p>';
            $body .= '<ul>';
            $body .= '<li>Website: <a href="' . esc_url( wp_login_url() ) . '">' . esc_url( wp_login_url() ) . '</a></li>';
            $body .= '<li>Username: ' . $username . '</li>';
            $body .= '<li>Password: ' . $password . '</li>';
            $body .= '</ul>';
            $email_message = ' Member created and login email sent.';
        } elseif ( $create_user ) {
            $email_message = ' Member linked to existing WP user (Email not sent).';
        } else {
            $email_message = ' Member created without a WP user.';
        }

        $body .= '<p>Your member profile is now live at:<br><a href="' . esc_url( $profile_url ) . '">' . esc_url( $profile_url ) . '</a></p>';
        $body .= '<p>Regards,<br>The BatchMate Team</p>';

        wp_mail( $email_to_use, $subject, $body, array( 'Content-Type: text/html; charset=UTF-8' ) );

        // Member Welcome SMS
        $settings = get_option('batchmate_settings', array());
        $member_sms_active = isset($settings['member_reg_sms_active']) && $settings['member_reg_sms_active'] === 'on';
        $cell = !empty($application['cell_number']) ? $application['cell_number'] : '';
        if ( $member_sms_active && !empty($cell) && function_exists('batchmate_send_sms') ) {
            $org_name = batchmate_org_label('short');
            $sms_tmpl = !empty($settings['member_reg_sms_template']) ? $settings['member_reg_sms_template'] : batchmate_get_default_template('member_reg_sms');
            $sms_msg = batchmate_replace_template_tags( $sms_tmpl, array(
                'name'      => $applicant_name,
                'member_id' => $member_number,
                'org_name'  => $org_name,
            ) );
            batchmate_send_sms( $cell, $sms_msg );
        }

        // Member Approval Webhook
        $member_wh_active = isset($settings['member_reg_webhook_active']) && $settings['member_reg_webhook_active'] === 'on';
        if ( $member_wh_active && function_exists('batchmate_dispatch_webhooks') ) {
            $payload = array(
                'event'         => 'member_approval',
                'member_id'     => $member_row_id ?? 0,
                'member_number' => $member_number,
                'name'          => $applicant_name,
                'email'         => $email_to_use,
                'phone'         => $cell,
                'profile_url'   => $profile_url,
                'approved_at'   => current_time('mysql'),
            );
            batchmate_dispatch_webhooks( $payload, 'member_registration' );
        }

        wp_send_json_success( 'Application approved.' . $email_message );

    } catch ( Throwable $e ) {
        $wpdb->query( 'ROLLBACK' );
        wp_send_json_error( $e->getMessage() );
    }
}
add_action( 'wp_ajax_BatchMate_approve_application', 'BatchMate_approve_application_ajax' );
add_action( 'wp_ajax_BatchMate_approve_application', 'BatchMate_approve_application_ajax' );
add_action( 'wp_ajax_puaa_approve_application',       'BatchMate_approve_application_ajax' );


/**
 * --- AJAX HANDLER: Reject Application ---
 */
function BatchMate_reject_application_ajax() {
    check_ajax_referer( 'BatchMate_app_nonce' );

    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( 'Permission denied.', 403 );
    }

    if ( ! isset( $_POST['application_id'] ) ) {
        wp_send_json_error( 'Application ID is missing.' );
    }

    global $wpdb;
    $app_table      = $wpdb->prefix . 'batchmate_application';
    $application_id = intval( $_POST['application_id'] );

    $result = $wpdb->update(
        $app_table,
        array( 'status' => 'rejected' ),
        array( 'id'     => $application_id ),
        array( '%s' ),
        array( '%d' )
    );

    if ( $result === false ) {
        wp_send_json_error( 'Database error: Could not update status.' );
    }

    wp_send_json_success( 'Application status updated to Rejected.' );
}
add_action( 'wp_ajax_BatchMate_reject_application', 'BatchMate_reject_application_ajax' );
add_action( 'wp_ajax_BatchMate_reject_application', 'BatchMate_reject_application_ajax' );
add_action( 'wp_ajax_puaa_reject_application',       'BatchMate_reject_application_ajax' );
