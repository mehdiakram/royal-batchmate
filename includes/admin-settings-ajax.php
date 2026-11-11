<?php
// File: includes/admin-settings-ajax.php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Save Settings Handler
 */
function batchmate_save_settings_ajax() {
    if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], 'BatchMate_settings_nonce' ) ) {
        wp_send_json_error( 'Security check failed.', 403 );
    }

    if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Permission denied.', 403 );

    $current_settings = get_option('batchmate_settings', array());
    $new_settings = $current_settings; 

    // Organization
    $new_settings['org_name']             = sanitize_text_field( $_POST['org_name'] );
    $new_settings['org_short_name']       = sanitize_text_field( $_POST['org_short_name'] ?? '' );
    $new_settings['org_member_prefix']    = strtoupper( preg_replace( '/[^A-Z0-9\-_]/i', '', sanitize_text_field( $_POST['org_member_prefix'] ?? '' ) ) );
    $new_settings['org_member_id_year']   = sanitize_text_field( $_POST['org_member_id_year'] ?? 'none' );
    $new_settings['org_member_id_digits'] = intval( $_POST['org_member_id_digits'] ?? 4 );
    $new_settings['org_tagline']          = wp_kses( $_POST['org_tagline'], array( 'br' => array() ) );
    $new_settings['org_email'] = sanitize_email( $_POST['org_email'] );
    $new_settings['org_address'] = sanitize_textarea_field( $_POST['org_address'] );
    $new_settings['org_logo_url'] = isset($_POST['org_logo_url']) ? esc_url_raw($_POST['org_logo_url']) : '';
    $new_settings['member_profile_banner_url'] = isset($_POST['member_profile_banner_url']) ? esc_url_raw($_POST['member_profile_banner_url']) : '';
    
    // Mail Settings
    $new_settings['form_mail_to'] = sanitize_text_field( $_POST['form_mail_to'] );
    $new_settings['event_mail_to'] = sanitize_text_field( $_POST['event_mail_to'] );
    
    // Registration Settings
    $new_settings['auto_verify'] = isset( $_POST['auto_verify'] ) ? 'on' : 'off';
    $new_settings['reg_fee_active'] = isset( $_POST['reg_fee_active'] ) ? 'on' : 'off';
    $new_settings['exam_type'] = sanitize_text_field( $_POST['exam_type'] ?? '' );
    $new_settings['passing_year'] = sanitize_text_field( $_POST['passing_year'] ?? '' );
    $new_settings['reg_fee_text'] = sanitize_text_field( $_POST['reg_fee_text'] ?? '' );
    $new_settings['reg_fee_amount'] = isset( $_POST['reg_fee_amount'] ) ? floatval( $_POST['reg_fee_amount'] ) : 0;
    $new_settings['payment_methods'] = sanitize_textarea_field( $_POST['reg_payment_methods'] ?? '' );
    
    // Labels
    $new_settings['label_batchmate'] = sanitize_text_field( $_POST['label_batchmate'] ?? '' );
    $new_settings['label_batchmates'] = sanitize_text_field( $_POST['label_batchmates'] ?? '' );
    $new_settings['label_member'] = sanitize_text_field( $_POST['label_member'] ?? '' );
    $new_settings['label_members'] = sanitize_text_field( $_POST['label_members'] ?? '' );
    $new_settings['label_main_menu'] = sanitize_text_field( $_POST['label_main_menu'] ?? '' );
    $new_settings['label_settings_menu'] = sanitize_text_field( $_POST['label_settings_menu'] ?? '' );
    $new_settings['label_settings_page_title'] = sanitize_text_field( $_POST['label_settings_page_title'] ?? '' );
    
    // Breadcrumb
    $new_settings['bc_bg_type'] = sanitize_text_field( $_POST['bc_bg_type'] ?? 'color' );
    $new_settings['bc_bg_color'] = sanitize_text_field( $_POST['bc_bg_color'] ?? '#263b71' );
    $new_settings['bc_bg_image'] = esc_url_raw( $_POST['bc_bg_image'] ?? '' );
    $new_settings['bc_text_color'] = sanitize_text_field( $_POST['bc_text_color'] ?? '#000000' );
    $new_settings['bc_font_size'] = intval( $_POST['bc_font_size'] ?? 32 );
    
    // Master Automation Toggle
    $new_settings['automation_active'] = isset( $_POST['automation_active'] ) ? 'on' : 'off';

    // Multi Webhooks
    $new_settings['webhook_urls'] = array();
    if ( isset( $_POST['webhook_urls'] ) && is_array( $_POST['webhook_urls'] ) ) {
        foreach ( $_POST['webhook_urls'] as $wh ) {
            if ( ! empty( $wh['url'] ) ) {
                $new_settings['webhook_urls'][] = array(
                    'url'    => esc_url_raw( trim( $wh['url'] ) ),
                    'name'   => sanitize_text_field( $wh['name'] ?? '' ),
                    'status' => isset( $wh['status'] ) && $wh['status'] === 'on' ? 'on' : 'off',
                    'events' => isset( $wh['events'] ) && is_array( $wh['events'] ) 
                                ? array_map( 'sanitize_text_field', $wh['events'] ) 
                                : array( 'all' ),
                );
            }
        }
    }

    // SMS Gateway Settings (sms.net.bd / Alpha SMS)
    $new_settings['sms_enabled']   = isset( $_POST['sms_enabled'] ) ? 'on' : 'off';
    $new_settings['sms_api_key']   = sanitize_text_field( $_POST['sms_api_key'] ?? '' );
    $new_settings['sms_sender_id'] = sanitize_text_field( $_POST['sms_sender_id'] ?? '' );

    // Birthday Automation & Templates
    $new_settings['birthday_active']               = isset( $_POST['birthday_active'] ) ? 'on' : 'off';
    $new_settings['birthday_card_template']        = sanitize_file_name( $_POST['birthday_card_template'] ?? 'birthdaycard1.php' );
    $new_settings['birthday_card_badge_field']     = sanitize_text_field( $_POST['birthday_card_badge_field'] ?? 'none' );
    $new_settings['birthday_card_custom_subtitle'] = sanitize_text_field( $_POST['birthday_card_custom_subtitle'] ?? '' );
    $new_settings['email_active']                  = isset( $_POST['email_active'] ) ? 'on' : 'off';
    $new_settings['birthday_email_subject'] = sanitize_text_field( $_POST['birthday_email_subject'] ?? '' );
    $new_settings['birthday_email_body']    = wp_kses_post( $_POST['birthday_email_body'] ?? '' );
    $new_settings['birthday_sms_active']    = isset( $_POST['birthday_sms_active'] ) ? 'on' : 'off';
    $new_settings['birthday_sms_time']      = sanitize_text_field( $_POST['birthday_sms_time'] ?? 'morning_9am' );
    $new_settings['birthday_sms_template']  = sanitize_textarea_field( $_POST['birthday_sms_template'] ?? '' );

    // Event Card & Registration Automation
    $new_settings['event_automation_active'] = isset( $_POST['event_automation_active'] ) ? 'on' : 'off';
    $new_settings['event_email_active']      = isset( $_POST['event_email_active'] ) ? 'on' : 'off';
    $new_settings['event_email_subject']     = sanitize_text_field( $_POST['event_email_subject'] ?? '' );
    $new_settings['event_email_body']        = wp_kses_post( $_POST['event_email_body'] ?? '' );
    $new_settings['event_sms_active']        = isset( $_POST['event_sms_active'] ) ? 'on' : 'off';
    $new_settings['event_sms_template']      = sanitize_textarea_field( $_POST['event_sms_template'] ?? '' );
    $new_settings['event_webhook_active']    = isset( $_POST['event_webhook_active'] ) ? 'on' : 'off';

    // Member Registration Automation
    $new_settings['member_reg_automation_active'] = isset( $_POST['member_reg_automation_active'] ) ? 'on' : 'off';
    $new_settings['member_reg_sms_active']        = isset( $_POST['member_reg_sms_active'] ) ? 'on' : 'off';
    $new_settings['member_reg_sms_template']      = sanitize_textarea_field( $_POST['member_reg_sms_template'] ?? '' );
    $new_settings['member_reg_webhook_active']    = isset( $_POST['member_reg_webhook_active'] ) ? 'on' : 'off';

    // Menu visibility
    $new_settings['show_test_birthday']   = isset( $_POST['show_test_birthday'] ) ? 'on' : 'off';
    $new_settings['show_test_event_card'] = isset( $_POST['show_test_event_card'] ) ? 'on' : 'off';
    $new_settings['show_raw_excel']       = isset( $_POST['show_raw_excel'] ) ? 'on' : 'off';

    // Facebook Post Settings
    $new_settings['fb_post_method']   = sanitize_text_field( $_POST['fb_post_method'] ?? 'none' ); // none, access_token, make_com
    $new_settings['make_webhook_url'] = esc_url_raw( $_POST['make_webhook_url'] ?? '' );

    // FB Creds
    $new_settings['fb_page_id']      = sanitize_text_field( $_POST['fb_page_id'] ?? '' );
    $new_settings['fb_access_token'] = sanitize_textarea_field( $_POST['fb_access_token'] ?? '' );

    // Fields Configuration
    if ( isset($_POST['fields']) || (isset($_POST['current_tab']) && $_POST['current_tab'] === 'registration') || isset($_POST['label_institution']) ) {
        $submitted_fields = ( isset($_POST['fields']) && is_array($_POST['fields']) ) ? wp_unslash($_POST['fields']) : array();

        // Explicit primary field labels
        $explicit_labels = array(
            'label_institution'        => 'reg_institution',
            'label_present_district'   => 'reg_present_district',
            'label_present_upazila'    => 'reg_present_upazila',
            'label_permanent_district' => 'reg_permanent_district',
            'label_permanent_upazila'  => 'reg_permanent_upazila',
            'label_roll_no'            => 'reg_roll_no',
        );
        foreach ( $explicit_labels as $post_k => $reg_k ) {
            if ( isset( $_POST[$post_k] ) ) {
                $val = sanitize_text_field( $_POST[$post_k] );
                $new_settings[$post_k] = $val;
                if ( ! empty( $val ) || ! isset( $submitted_fields[$reg_k]['label'] ) ) {
                    if ( ! isset( $submitted_fields[$reg_k] ) ) {
                        $submitted_fields[$reg_k] = array();
                    }
                    $submitted_fields[$reg_k]['label'] = $val;
                }
            }
        }

        $all_field_keys = array(
            'reg_name', 'reg_name_bn', 'reg_email', 'reg_cell_number', 'reg_land_phone',
            'reg_blood_group', 'reg_dob', 'reg_gender', 'reg_religion', 'reg_photo',
            'reg_board', 'reg_roll_no', 'reg_registration_no', 'reg_group', 'reg_institution',
            'reg_present_address', 'reg_present_country', 'reg_present_district', 'reg_present_upazila',
            'reg_permanent_address', 'reg_permanent_country', 'reg_permanent_district', 'reg_permanent_upazila',
            'reg_organization', 'reg_position', 'reg_industry', 'reg_work_experience',
            'reg_employment_address', 'reg_previous_organizations', 'reg_about_me', 'reg_skills',
            'reg_facebook', 'reg_linkedin', 'reg_x', 'reg_website'
        );
        $saved_fields = array();
        foreach ( $all_field_keys as $fk ) {
            $existing_lbl = $current_settings['fields'][$fk]['label'] ?? '';
            $field_label  = isset( $submitted_fields[$fk]['label'] ) ? sanitize_text_field( $submitted_fields[$fk]['label'] ) : $existing_lbl;
            if ( $fk === 'reg_name' || $fk === 'reg_cell_number' ) {
                $saved_fields[$fk] = array( 'show' => true, 'req' => true, 'label' => $field_label );
            } else {
                $saved_fields[$fk] = array(
                    'show'  => ! empty( $submitted_fields[$fk]['show'] ),
                    'req'   => ! empty( $submitted_fields[$fk]['req'] ),
                    'label' => $field_label,
                );
            }
            // Sync top-level label shortcut (e.g. label_institution, label_present_district)
            $short_k = substr( $fk, 4 );
            $new_settings['label_' . $short_k] = $field_label;
        }
        $new_settings['fields'] = $saved_fields;
    }

    update_option('batchmate_settings', $new_settings);

    // Auto-generate fresh canvas preview for the selected template
    if ( function_exists( 'batchmate_get_or_generate_card_preview' ) ) {
        $preview_tpl = ! empty( $new_settings['birthday_card_template'] ) ? $new_settings['birthday_card_template'] : 'birthdaycard1.php';
        batchmate_get_or_generate_card_preview( $preview_tpl, true, $new_settings );
    }

    wp_send_json_success( array( 'message' => __( 'Settings saved successfully.', 'royal-batchmate' ) ) );
}
add_action( 'wp_ajax_batchmate_save_settings', 'batchmate_save_settings_ajax' );


/**
 * Test Facebook Connection Handler
 */
function batchmate_test_fb_connection_ajax() {
    if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], 'BatchMate_settings_nonce' ) ) {
        wp_send_json_error( 'Security check failed.' );
    }

    $page_id = sanitize_text_field( $_POST['page_id'] );
    $token = sanitize_textarea_field( $_POST['token'] );

    if ( empty($page_id) || empty($token) ) {
        wp_send_json_error( 'Missing Page ID or Token.' );
    }

    // Facebook Graph API Call to verify token
    $url = "https://graph.facebook.com/v19.0/$page_id?fields=name,id&access_token=$token";

    $response = wp_remote_get( $url );

    if ( is_wp_error( $response ) ) {
        wp_send_json_error( 'Connection Error: ' . $response->get_error_message() );
    }

    $body = wp_remote_retrieve_body( $response );
    $data = json_decode( $body, true );

    if ( isset( $data['error'] ) ) {
        // ফেসবুক থেকে এরর আসলে
        wp_send_json_error( 'FB Error: ' . $data['error']['message'] );
    } elseif ( isset( $data['id'] ) && $data['id'] == $page_id ) {
        // সফল হলে পেজের নাম দেখাবে
        wp_send_json_success( array( 
            'message' => 'Success! Connected to: ' . $data['name'] 
        ) );
    } else {
        wp_send_json_error( 'Unknown response from Facebook.' );
    }
}
add_action( 'wp_ajax_batchmate_test_fb_connection', 'batchmate_test_fb_connection_ajax' );
add_action( 'wp_ajax_puua_test_fb_connection', 'batchmate_test_fb_connection_ajax' );

