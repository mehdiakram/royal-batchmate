<?php
// File: includes/automation-service.php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * ============================================================================
 * ROYAL BATCHMATE AUTOMATION & SMS SERVICE
 * ============================================================================
 * Handles SMS dispatch (sms.net.bd / Alpha SMS), multi-webhook dispatch,
 * email & SMS templating, and schedule management.
 * ============================================================================
 */

/**
 * Format / normalize Bangladesh phone number to 8801XXXXXXXXX
 * Handles Bengali numerals, dashes, spaces, +880, 01, etc.
 *
 * @param string $phone
 * @return string
 */
function batchmate_format_bd_phone( $phone ) {
    if ( empty( $phone ) ) return '';

    // Convert Bengali numerals to standard English digits
    $bn_digits = array( '০', '১', '২', '৩', '৪', '৫', '৬', '৭', '৮', '৯' );
    $en_digits = array( '0', '1', '2', '3', '4', '5', '6', '7', '8', '9' );
    $phone = str_replace( $bn_digits, $en_digits, (string) $phone );

    // Strip any non-digit characters
    $phone = preg_replace( '/\D+/', '', $phone );

    // Check patterns
    if ( preg_match( '/^8801[3-9]\d{8}$/', $phone ) ) {
        return $phone;
    } elseif ( preg_match( '/^01[3-9]\d{8}$/', $phone ) ) {
        return '88' . $phone;
    } elseif ( preg_match( '/^1[3-9]\d{8}$/', $phone ) ) {
        return '880' . $phone;
    }

    return $phone;
}

/**
 * Send SMS via Alpha SMS (https://api.sms.net.bd/sendsms)
 *
 * @param string      $phone     Single or comma-separated numbers
 * @param string      $message   Message text
 * @param string|null $schedule  Optional schedule (Y-m-d H:i:s)
 * @return array                 ['success' => bool, 'message' => string, 'data' => mixed]
 */
function batchmate_send_sms( $phone, $message, $schedule = null ) {
    $settings    = get_option( 'batchmate_settings', array() );
    $api_key     = ! empty( $settings['sms_api_key'] ) ? trim( $settings['sms_api_key'] ) : '';
    $sender_id   = ! empty( $settings['sms_sender_id'] ) ? trim( $settings['sms_sender_id'] ) : '';
    $sms_enabled = isset( $settings['sms_enabled'] ) && $settings['sms_enabled'] === 'on';

    if ( ! $sms_enabled ) {
        return array( 'success' => false, 'message' => 'SMS Gateway is currently disabled in settings.' );
    }

    if ( empty( $api_key ) ) {
        return array( 'success' => false, 'message' => 'Alpha SMS API Key is not configured.' );
    }

    if ( empty( $message ) ) {
        return array( 'success' => false, 'message' => 'SMS message content is empty.' );
    }

    // Split and format multiple numbers if comma-separated
    $raw_phones = explode( ',', (string) $phone );
    $formatted_phones = array();
    foreach ( $raw_phones as $p ) {
        $clean = batchmate_format_bd_phone( trim( $p ) );
        if ( ! empty( $clean ) ) {
            $formatted_phones[] = $clean;
        }
    }

    if ( empty( $formatted_phones ) ) {
        return array( 'success' => false, 'message' => 'No valid recipient phone number found.' );
    }

    $to = implode( ',', array_unique( $formatted_phones ) );

    $params = array(
        'api_key' => $api_key,
        'msg'     => $message,
        'to'      => $to,
    );

    if ( ! empty( $sender_id ) ) {
        $params['sender_id'] = $sender_id;
    }

    if ( ! empty( $schedule ) ) {
        $params['schedule'] = $schedule;
    }

    $response = wp_remote_post( 'https://api.sms.net.bd/sendsms', array(
        'body'      => $params,
        'timeout'   => 15,
        'sslverify' => false,
    ) );

    if ( is_wp_error( $response ) ) {
        $err = $response->get_error_message();
        error_log( '[BatchMate SMS] Connection error: ' . $err );
        return array( 'success' => false, 'message' => 'SMS Gateway Connection Failed: ' . $err );
    }

    $body = wp_remote_retrieve_body( $response );
    $json = json_decode( $body, true );

    if ( isset( $json['error'] ) && (int) $json['error'] === 0 ) {
        return array(
            'success'    => true,
            'message'    => $json['msg'] ?? 'SMS submitted successfully.',
            'request_id' => $json['data']['request_id'] ?? null,
            'data'       => $json,
        );
    }

    $error_msg = $json['msg'] ?? ( 'SMS Gateway Error code ' . ( $json['error'] ?? 'Unknown' ) );
    error_log( '[BatchMate SMS] Send failed for ' . $to . ': ' . $error_msg );

    return array(
        'success' => false,
        'message' => $error_msg,
        'data'    => $json,
    );
}

/**
 * Fetch Alpha SMS Account Balance (https://api.sms.net.bd/user/balance/)
 *
 * @param string $api_key
 * @return array ['success' => bool, 'balance' => string, 'message' => string]
 */
function batchmate_get_sms_balance( $api_key = '' ) {
    if ( empty( $api_key ) ) {
        $settings = get_option( 'batchmate_settings', array() );
        $api_key  = ! empty( $settings['sms_api_key'] ) ? trim( $settings['sms_api_key'] ) : '';
    }

    if ( empty( $api_key ) ) {
        return array( 'success' => false, 'message' => 'Please enter your Alpha SMS API Key first.' );
    }

    $url = add_query_arg( array( 'api_key' => $api_key ), 'https://api.sms.net.bd/user/balance/' );

    $response = wp_remote_get( $url, array(
        'timeout'   => 12,
        'sslverify' => false,
    ) );

    if ( is_wp_error( $response ) ) {
        return array( 'success' => false, 'message' => 'Connection Failed: ' . $response->get_error_message() );
    }

    $body = wp_remote_retrieve_body( $response );
    $json = json_decode( $body, true );

    if ( isset( $json['error'] ) && (int) $json['error'] === 0 && isset( $json['data']['balance'] ) ) {
        return array(
            'success' => true,
            'balance' => $json['data']['balance'],
            'message' => 'Balance fetched successfully.',
        );
    }

    return array(
        'success' => false,
        'message' => $json['msg'] ?? 'Unable to retrieve SMS balance. Please verify API key.',
    );
}

/**
 * Dispatch payload to multiple configured Webhook URLs
 *
 * @param array  $payload
 * @param string $event_type  'birthday', 'event_registration', 'member_registration', 'test'
 * @return array
 */
function batchmate_dispatch_webhooks( $payload, $event_type = 'general', $override_urls = null ) {
    $settings = get_option( 'batchmate_settings', array() );
    $urls     = array();

    // Direct override URLs (e.g. from live form test)
    if ( ! empty( $override_urls ) && is_array( $override_urls ) ) {
        foreach ( $override_urls as $wh ) {
            $u = is_array( $wh ) ? ( $wh['url'] ?? '' ) : $wh;
            $u = trim( (string) $u );
            if ( ! empty( $u ) && filter_var( $u, FILTER_VALIDATE_URL ) ) {
                $urls[] = $u;
            }
        }
    } else {
        // 1. Multi-webhook rows
        if ( ! empty( $settings['webhook_urls'] ) && is_array( $settings['webhook_urls'] ) ) {
            foreach ( $settings['webhook_urls'] as $wh ) {
                if ( ! is_array( $wh ) ) continue;
                $url    = trim( $wh['url'] ?? '' );
                $status = $wh['status'] ?? 'on';
                $events = isset( $wh['events'] ) ? (array) $wh['events'] : array( 'all' );

                if ( ! empty( $url ) && $status === 'on' ) {
                    if ( in_array( 'all', $events ) || in_array( $event_type, $events ) || $event_type === 'test' ) {
                        $urls[] = $url;
                    }
                }
            }
        }

        // 2. Legacy fallback: make_webhook_url
        if ( ! empty( $settings['make_webhook_url'] ) ) {
            $legacy_url = trim( $settings['make_webhook_url'] );
            if ( ! empty( $legacy_url ) && ! in_array( $legacy_url, $urls ) ) {
                $urls[] = $legacy_url;
            }
        }
    }

    $urls = array_unique( $urls );

    if ( empty( $urls ) ) {
        return array( 'dispatched' => 0, 'results' => array() );
    }

    // Ensure metadata in payload
    $payload['event_type']    = $event_type;
    $payload['dispatched_at'] = current_time( 'mysql' );
    $payload['source_site']   = home_url();

    $results = array();
    foreach ( $urls as $target_url ) {
        $args = array(
            'body'      => wp_json_encode( $payload ),
            'headers'   => array( 'Content-Type' => 'application/json; charset=utf-8' ),
            'timeout'   => 15,
            'blocking'  => true,
            'sslverify' => false,
        );

        $res = wp_remote_post( $target_url, $args );

        if ( is_wp_error( $res ) ) {
            error_log( "[BatchMate Webhook] Error ({$target_url}): " . $res->get_error_message() );
            $results[] = array(
                'url'     => $target_url,
                'success' => false,
                'message' => $res->get_error_message(),
            );
        } else {
            $code = wp_remote_retrieve_response_code( $res );
            $ok   = ( $code >= 200 && $code < 300 );
            $results[] = array(
                'url'     => $target_url,
                'success' => $ok,
                'code'    => $code,
                'message' => 'HTTP ' . $code,
            );
        }
    }

    return array( 'dispatched' => count( $results ), 'results' => $results );
}

/**
 * Replace placeholders in template text
 *
 * @param string $template
 * @param array  $vars
 * @return string
 */
function batchmate_replace_template_tags( $template, $vars = array() ) {
    if ( empty( $template ) ) return '';
    foreach ( $vars as $tag => $val ) {
        $template = str_replace( '{' . $tag . '}', (string) $val, $template );
    }
    return $template;
}

/**
 * Default templates helper
 *
 * @param string $key
 * @return string
 */
function batchmate_get_default_template( $key ) {
    $org_short = batchmate_org_label( 'short' );
    $org_full  = batchmate_org_label( 'full' );

    switch ( $key ) {
        case 'birthday_email_subject':
            return 'Happy Birthday, {name}! 🎉';

        case 'birthday_email_body':
            return "<div style='font-family: Arial, sans-serif; background-color: #f4f7fb; padding: 25px;'>
  <div style='background: #ffffff; padding: 28px; border-radius: 12px; max-width: 600px; margin: auto; box-shadow: 0 4px 15px rgba(0,0,0,0.06);'>
    <h2 style='color: #1a3a6b; margin-top: 0;'>Dear {name},</h2>
    <p style='font-size: 15px; color: #475569; line-height: 1.6;'>On your special birthday, on behalf of <strong>{org_name}</strong>, we wish you joyful moments, vibrant health, peace, and prosperous success in every chapter of life!</p>
    <div style='margin: 22px 0; text-align: center;'>
      {card_image}
    </div>
    <hr style='border: none; border-top: 1px solid #e2e8f0; margin: 24px 0;' />
    <p style='color: #64748b; font-size: 13px; margin: 0;'>Warmest Regards,<br><strong style='color: #1a3a6b; font-size: 14px;'>{org_name}</strong></p>
  </div>
</div>";

        case 'birthday_sms':
            return 'Happy Birthday {name}! Wishing you boundless joy, good health and grand success today and always. - {org_name}';

        case 'event_email_subject':
            return 'Registration Confirmed: {event_name} (Serial #{serial_no})';

        case 'event_email_body':
            return "<div style='font-family: Arial, sans-serif; background-color: #f4f7fb; padding: 25px;'>
  <div style='background: #ffffff; padding: 28px; border-radius: 12px; max-width: 600px; margin: auto; box-shadow: 0 4px 15px rgba(0,0,0,0.06);'>
    <h2 style='color: #1a3a6b; margin-top: 0;'>Hello {name},</h2>
    <p style='font-size: 15px; color: #475569; line-height: 1.6;'>Your registration for <strong>{event_name}</strong> is confirmed!</p>
    <p style='font-size: 15px; color: #1e293b;'>Registration Serial: <strong style='color: #2563eb;'>#{serial_no}</strong></p>
    <div style='margin: 22px 0; text-align: center;'>
      {card_image}
    </div>
    <hr style='border: none; border-top: 1px solid #e2e8f0; margin: 24px 0;' />
    <p style='color: #64748b; font-size: 13px; margin: 0;'>See you at the event!<br><strong style='color: #1a3a6b; font-size: 14px;'>{org_name}</strong></p>
  </div>
</div>";

        case 'event_sms':
            return 'Hello {name}, your registration for {event_name} is confirmed! Serial: #{serial_no}. See you at the event! - {org_name}';

        case 'member_reg_sms':
            return 'Welcome {name}! Your membership registration at {org_name} has been approved. Member ID: {member_id}.';

        default:
            return '';
    }
}

/**
 * ============================================================================
 * AJAX HANDLERS FOR AUTOMATION & SMS TESTING
 * ============================================================================
 */

/**
 * AJAX: Check SMS Balance
 */
function batchmate_check_sms_balance_ajax() {
    if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], 'BatchMate_settings_nonce' ) ) {
        wp_send_json_error( 'Security verification failed.', 403 );
    }
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( 'Permission denied.', 403 );
    }

    $api_key = sanitize_text_field( $_POST['api_key'] ?? '' );
    $result  = batchmate_get_sms_balance( $api_key );

    if ( $result['success'] ) {
        wp_send_json_success( array(
            'balance' => $result['balance'],
            'message' => 'Current Balance: ' . $result['balance'] . ' BDT',
        ) );
    } else {
        wp_send_json_error( $result['message'] );
    }
}
add_action( 'wp_ajax_batchmate_check_sms_balance', 'batchmate_check_sms_balance_ajax' );

/**
 * AJAX: Send Test SMS
 */
function batchmate_test_sms_ajax() {
    if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], 'BatchMate_settings_nonce' ) ) {
        wp_send_json_error( 'Security verification failed.', 403 );
    }
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( 'Permission denied.', 403 );
    }

    $phone     = sanitize_text_field( $_POST['phone'] ?? '' );
    $message   = sanitize_textarea_field( $_POST['message'] ?? '' );
    $api_key   = sanitize_text_field( $_POST['api_key'] ?? '' );
    $sender_id = sanitize_text_field( $_POST['sender_id'] ?? '' );

    if ( empty( $phone ) ) {
        wp_send_json_error( 'Please provide a valid recipient phone number.' );
    }

    if ( empty( $message ) ) {
        $org_name = batchmate_org_label( 'short' );
        $message  = "Test SMS from {$org_name} via Alpha SMS gateway at " . current_time( 'H:i:s' ) . ".";
    }

    // Temporary override credentials if provided in test request
    $settings = get_option( 'batchmate_settings', array() );
    if ( ! empty( $api_key ) ) {
        $settings['sms_api_key'] = $api_key;
    }
    if ( ! empty( $sender_id ) ) {
        $settings['sms_sender_id'] = $sender_id;
    }
    $settings['sms_enabled'] = 'on';
    update_option( 'batchmate_settings', $settings );

    $res = batchmate_send_sms( $phone, $message );

    if ( $res['success'] ) {
        wp_send_json_success( 'Test SMS sent successfully to ' . esc_html( $phone ) . '! Request ID: ' . ( $res['request_id'] ?? 'OK' ) );
    } else {
        wp_send_json_error( $res['message'] );
    }
}
add_action( 'wp_ajax_batchmate_test_sms', 'batchmate_test_sms_ajax' );

/**
 * AJAX: Test Webhook Connections
 */
function batchmate_test_webhooks_ajax() {
    if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], 'BatchMate_settings_nonce' ) ) {
        wp_send_json_error( 'Security verification failed.', 403 );
    }
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( 'Permission denied.', 403 );
    }

    $override_urls = array();
    if ( isset( $_POST['webhook_urls'] ) && is_array( $_POST['webhook_urls'] ) ) {
        foreach ( $_POST['webhook_urls'] as $wh ) {
            $u = is_array( $wh ) ? ( $wh['url'] ?? '' ) : $wh;
            if ( ! empty( $u ) ) {
                $override_urls[] = $u;
            }
        }
    }

    $sample_payload = array(
        'type'        => 'test_ping',
        'event_name'  => 'Automation Webhook Test Ping',
        'member_name' => wp_get_current_user()->display_name,
        'card_url'    => 'https://images.unsplash.com/photo-1513151233558-d860c5398176?w=1200&auto=format&fit=crop&q=80',
        'message'     => '🎉 Test card ping from Royal BatchMate Automation System! Wishing everyone joyful celebration and success. #RoyalBatchMate #SSC99',
        'timestamp'   => current_time( 'mysql' ),
    );

    $res = batchmate_dispatch_webhooks( $sample_payload, 'test', $override_urls );

    if ( empty( $res['dispatched'] ) ) {
        wp_send_json_error( 'No active Webhook URLs found to ping. Please add at least one active Webhook URL or click Save Settings first.' );
    }

    $msg = 'Dispatched to ' . $res['dispatched'] . ' webhook(s):<br>';
    foreach ( $res['results'] as $r ) {
        $badge = $r['success'] ? '<span style="color:#16a34a;font-weight:bold;">[OK]</span>' : '<span style="color:#ef4444;font-weight:bold;">[FAIL]</span>';
        $msg  .= $badge . ' ' . esc_html( $r['url'] ) . ' (' . esc_html( $r['message'] ?? '' ) . ')<br>';
    }

    wp_send_json_success( array( 'html' => $msg ) );
}
add_action( 'wp_ajax_batchmate_test_webhooks', 'batchmate_test_webhooks_ajax' );
