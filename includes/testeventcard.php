<?php
// File: includes/testeventcard.php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * ============================================================================
 * HELPER: Get First Member's Data for Dummy/Testing
 * ============================================================================
 * Fetches the very first member in the database.
 * If database is empty, falls back to graceful dummy data.
 * ============================================================================
 */
if ( ! function_exists( 'batchmate_get_test_member_data' ) ) {
    function batchmate_get_test_member_data() {
        global $wpdb;
        $member_table = $wpdb->prefix . 'batchmate_member';
        $prog_table   = $wpdb->prefix . 'batchmate_program';

        // Check if member table exists
        $has_table = $wpdb->get_var( "SHOW TABLES LIKE '{$member_table}'" );
        if ( $has_table ) {
            $row = $wpdb->get_row( "
                SELECT m.*, p.program_name 
                FROM {$member_table} m 
                LEFT JOIN {$prog_table} p ON m.program_id = p.id 
                ORDER BY m.id ASC 
                LIMIT 1
            " );

            if ( $row ) {
                return (object) array(
                    'id'            => (int) $row->id,
                    'name'          => ! empty( $row->name ) ? $row->name : 'Batchmate Member',
                    'email'         => ! empty( $row->email ) ? $row->email : get_bloginfo( 'admin_email' ),
                    'cell_number'   => ! empty( $row->cell_number ) ? $row->cell_number : '01700000000',
                    'blood_group'   => ! empty( $row->blood_group ) ? $row->blood_group : 'B+',
                    'batch'         => ! empty( $row->batch ) ? $row->batch : '1999',
                    'gender'        => ! empty( $row->gender ) ? $row->gender : 'Male',
                    'member_number' => ! empty( $row->member_number ) ? $row->member_number : 'M-0001',
                    'photo_url'     => ! empty( $row->photo_url ) ? $row->photo_url : ( ROYAL_BATCHMATE_PLUGIN_URL . 'assets/images/noprofile.png' ),
                    'program_name'  => ! empty( $row->program_name ) ? $row->program_name : 'General Member',
                    'program_short_code' => ! empty( $row->program_name ) ? $row->program_name : '',
                );
            }
        }

        // Graceful dummy fallback if table has no members yet
        return (object) array(
            'id'            => 1,
            'name'          => 'S M Mehdi Akram',
            'email'         => get_bloginfo( 'admin_email' ),
            'cell_number'   => '01712345678',
            'blood_group'   => 'B+',
            'batch'         => '1999',
            'gender'        => 'Male',
            'member_number' => 'M-0001',
            'photo_url'     => 'https://batchmate.org/wp-content/uploads/2025/11/023030101001-s-m-mehdi-akram-1764263702.png',
            'program_name'  => 'B.SC IN CSE',
            'program_short_code' => 'CSE',
        );
    }
}

/**
 * Internal Helper: Build Test Event Card and return temp file path + public URL
 */
function batchmate_build_test_event_card( $params = array() ) {
    global $wpdb;

    $event_id      = intval( $params['event_id'] ?? 0 );
    $event_name    = sanitize_text_field( $params['event_name'] ?? 'Annual Reunion 2026' );
    $template_url  = esc_url_raw( trim( $params['card_template_url'] ?? '' ) );
    $card_type     = sanitize_text_field( $params['card_type'] ?? 'Member' );
    $show_org_logo = ! empty( $params['show_org_logo'] ) && $params['show_org_logo'] === 'on';

    $card_zones_json = '';

    // 1. Explicit zones posted in form
    if ( ! empty( $params['card_zones_json'] ) ) {
        $card_zones_json = stripslashes( $params['card_zones_json'] );
    }

    // 2. If existing event selected from DB, prioritize its saved setup
    if ( $event_id > 0 ) {
        $ev = $wpdb->get_row( $wpdb->prepare(
            "SELECT event_name, card_template_url, card_zones_json FROM {$wpdb->prefix}batchmate_event WHERE id = %d",
            $event_id
        ) );
        if ( $ev ) {
            if ( empty( $template_url ) && ! empty( $ev->card_template_url ) ) {
                $template_url = $ev->card_template_url;
            }
            if ( ! empty( $ev->card_zones_json ) ) {
                $card_zones_json = $ev->card_zones_json;
            }
            if ( ! empty( $ev->event_name ) && ( empty( $event_name ) || $event_name === 'Annual Reunion 2026' || $event_name === 'Demo Event 2026' ) ) {
                $event_name = $ev->event_name;
            }
        }
    }

    // 3. If event_id was 0 or demo, but template_url matches an existing event in DB, auto-link its saved zones!
    if ( empty( $card_zones_json ) && ! empty( $template_url ) ) {
        $ev_match = $wpdb->get_row( $wpdb->prepare(
            "SELECT event_name, card_zones_json FROM {$wpdb->prefix}batchmate_event WHERE card_template_url = %s ORDER BY id DESC LIMIT 1",
            $template_url
        ) );
        if ( $ev_match && ! empty( $ev_match->card_zones_json ) ) {
            $card_zones_json = $ev_match->card_zones_json;
            if ( empty( $event_name ) || $event_name === 'Annual Reunion 2026' || $event_name === 'Demo Event 2026' ) {
                $event_name = $ev_match->event_name;
            }
        }
    }

    // Default template fallback if no template provided
    if ( empty( $template_url ) ) {
        $default_bg = ROYAL_BATCHMATE_PLUGIN_URL . 'templates/birthdaycard1_bg.jpg';
        $template_url = $default_bg;
    }

    // Default zones fallback if empty
    if ( empty( $card_zones_json ) ) {
        $card_zones_json = json_encode( array(
            'photo'     => array( 'x' => 380, 'y' => 220, 'w' => 320, 'h' => 320, 'cx' => 540, 'cy' => 380, 'r' => 160, 'shape' => 'square', 'enabled' => true ),
            'name'      => array( 'x' => 140, 'y' => 640, 'w' => 800, 'h' => 60, 'size' => 26, 'color_text' => '#ffffff', 'enabled' => true ),
            'serial'    => array( 'x' => 820, 'y' => 220, 'w' => 200, 'h' => 44, 'size' => 28, 'color_text' => '#ffffff', 'prefix' => '', 'enabled' => true ),
            'blood'     => array( 'x' => 420, 'y' => 730, 'w' => 200, 'h' => 36, 'size' => 26, 'color_text' => '#ffffff', 'enabled' => true ),
            'district'  => array( 'x' => 420, 'y' => 790, 'w' => 240, 'h' => 36, 'size' => 26, 'color_text' => '#ffffff', 'enabled' => true ),
            'batch'     => array( 'x' => 420, 'y' => 850, 'w' => 150, 'h' => 36, 'size' => 26, 'color_text' => '#ffffff', 'enabled' => false ),
            'free_text' => array( 'x' => 420, 'y' => 910, 'w' => 240, 'h' => 36, 'size' => 26, 'color_text' => '#ffffff', 'enabled' => false ),
            'logo'      => array( 'x' => 60,  'y' => 50,  'w' => 240, 'h' => 80 ),
        ) );
    }

    $settings = get_option( 'batchmate_settings', array() );
    $org_logo_url = ! empty( $settings['org_logo_url'] ) ? $settings['org_logo_url'] : '';

    $member_data = array(
        'name'               => sanitize_text_field( $params['member_name'] ?? 'Member' ),
        'email'              => sanitize_email( $params['recipient_email'] ?? '' ),
        'cell_number'        => sanitize_text_field( $params['cell_number'] ?? '' ),
        'blood_group'        => sanitize_text_field( $params['blood_group'] ?? 'B+' ),
        'district'           => sanitize_text_field( $params['district'] ?? ($member->present_district ?? ($member->permanent_district ?? 'Tangail')) ),
        'batch'              => sanitize_text_field( $params['batch'] ?? '1999' ),
        'program_name'       => sanitize_text_field( $params['program_name'] ?? 'General Member' ),
        'program_short_code' => sanitize_text_field( $params['program_name'] ?? '' ),
        'photo_url'          => esc_url_raw( $params['photo_url'] ?? '' ),
        'serial_number'      => sanitize_text_field( $params['serial_number'] ?? '001' ),
        'member_number'      => sanitize_text_field( $params['member_number'] ?? '' ),
    );

    $event_data = array(
        'event_name'        => $event_name,
        'card_template_url' => $template_url,
        'card_zones_json'   => $card_zones_json,
        'logo_url'          => $org_logo_url,
        'show_org_logo'     => $show_org_logo ? 1 : 0,
    );

    require_once ROYAL_BATCHMATE_PLUGIN_DIR . 'includes/admin-events-card.php';

    $card_path = BatchMate_generate_event_card( $member_data, $event_data, $card_type );

    if ( ! $card_path || ! file_exists( $card_path ) ) {
        return false;
    }

    $upload_dir = wp_upload_dir();
    $card_url   = str_replace( $upload_dir['basedir'], $upload_dir['baseurl'], $card_path );

    return array(
        'path'        => $card_path,
        'url'         => $card_url,
        'member'      => $member_data,
        'event'       => $event_data,
        'type'        => $card_type,
        'org_logo'    => $org_logo_url,
        'org_name'    => function_exists( 'batchmate_org_label' ) ? batchmate_org_label( 'full' ) : ( $settings['org_name'] ?? 'BatchMate' ),
    );
}

/**
 * ============================================================================
 * AJAX HANDLER 1: Preview Event Card
 * ============================================================================
 */
add_action( 'wp_ajax_batchmate_test_event_card_preview', 'batchmate_test_event_card_preview_ajax' );
function batchmate_test_event_card_preview_ajax() {
    if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], 'BatchMate_settings_nonce' ) ) {
        wp_send_json_error( 'Security check failed.', 403 );
    }
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( 'Permission denied.', 403 );
    }

    $card = batchmate_build_test_event_card( $_POST );
    if ( ! $card ) {
        wp_send_json_error( 'Failed to generate test event card image. Please verify template URL.' );
    }

    wp_send_json_success( array(
        'card_url' => $card['url'],
        'message'  => 'Card generated successfully at 1080x1080 resolution.',
    ) );
}

/**
 * ============================================================================
 * AJAX HANDLER 2: Send Test Event Card Email
 * ============================================================================
 */
add_action( 'wp_ajax_batchmate_test_event_card_email', 'batchmate_test_event_card_email_ajax' );
function batchmate_test_event_card_email_ajax() {
    if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], 'BatchMate_settings_nonce' ) ) {
        wp_send_json_error( 'Security check failed.', 403 );
    }
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( 'Permission denied.', 403 );
    }

    $recipient_email = sanitize_email( $_POST['recipient_email'] ?? '' );
    if ( ! is_email( $recipient_email ) ) {
        wp_send_json_error( 'Invalid recipient email address.' );
    }

    $card = batchmate_build_test_event_card( $_POST );
    if ( ! $card ) {
        wp_send_json_error( 'Failed to generate card image for email.' );
    }

    $settings  = get_option( 'batchmate_settings', array() );
    $org_name  = $card['org_name'];
    $org_email = ! empty( $settings['org_email'] ) ? $settings['org_email'] : get_bloginfo( 'admin_email' );

    $event_name  = $card['event']['event_name'];
    $member_name = $card['member']['name'];
    $subject     = '[BatchMate] Registration Card (Test) — ' . $event_name;

    $card_img_tag = "<img src='" . esc_url( $card['url'] ) . "' alt='Event Registration Card' style='max-width: 100%; height: auto; border-radius: 12px; border: 1px solid #cbd5e1; box-shadow: 0 8px 24px rgba(0,0,0,0.12);'>";

    $body  = "<div style='font-family: Arial, sans-serif; background-color: #f1f5f9; padding: 30px;'>";
    $body .= "<div style='background: #ffffff; padding: 32px; border-radius: 14px; max-width: 620px; margin: auto; box-shadow: 0 4px 18px rgba(0,0,0,0.06);'>";
    
    if ( ! empty( $card['org_logo'] ) ) {
        $body .= "<div style='text-align: center; margin-bottom: 20px;'><img src='" . esc_url( $card['org_logo'] ) . "' alt='" . esc_attr( $org_name ) . "' style='max-height: 60px;'></div>";
    }

    $body .= "<h2 style='color: #1e3a8a; margin-top: 0;'>Hello " . esc_html( $member_name ) . ",</h2>";
    $body .= "<p style='font-size: 15px; color: #475569; line-height: 1.6;'>This is a <strong>test registration card</strong> for <strong>" . esc_html( $event_name ) . "</strong>. You can find your digital entry card below and attached to this email.</p>";
    $body .= "<div style='margin: 25px 0; text-align: center;'>" . $card_img_tag . "</div>";
    $body .= "<p style='font-size: 13px; color: #64748b; line-height: 1.5;'>Attendee Type: <strong>" . esc_html( $card['type'] ) . "</strong> | Batch: <strong>" . esc_html( $card['member']['batch'] ) . "</strong> | Blood: <strong>" . esc_html( $card['member']['blood_group'] ) . "</strong></p>";
    $body .= "<hr style='border: none; border-top: 1px solid #e2e8f0; margin: 24px 0;' />";
    $body .= "<p style='color: #64748b; font-size: 13px; margin: 0;'>Warm regards,<br><strong style='color: #1e3a8a;'>" . esc_html( $org_name ) . "</strong></p>";
    $body .= "</div></div>";

    $headers = array(
        'Content-Type: text/html; charset=UTF-8',
        'From: ' . $org_name . ' <' . $org_email . '>'
    );

    $attachments = array( $card['path'] );
    $sent = wp_mail( $recipient_email, $subject, $body, $headers, $attachments );

    if ( $sent ) {
        wp_send_json_success( 'Test event card email successfully sent to ' . $recipient_email . ' with 1080x1080 card attached.' );
    } else {
        wp_send_json_error( 'wp_mail() could not deliver the email. Please check your WordPress SMTP / mail server configuration.' );
    }
}

/**
 * ============================================================================
 * AJAX HANDLER 3: Dispatch to Make.com / Multi-Webhooks
 * ============================================================================
 */
add_action( 'wp_ajax_batchmate_test_event_card_webhook', 'batchmate_test_event_card_webhook_ajax' );
function batchmate_test_event_card_webhook_ajax() {
    if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], 'BatchMate_settings_nonce' ) ) {
        wp_send_json_error( 'Security check failed.', 403 );
    }
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( 'Permission denied.', 403 );
    }

    $card = batchmate_build_test_event_card( $_POST );
    if ( ! $card ) {
        wp_send_json_error( 'Failed to generate card image for webhook payload.' );
    }

    $member_name = $card['member']['name'];
    $event_name  = $card['event']['event_name'];
    $org_name    = $card['org_name'];
    $org_logo    = $card['org_logo'];

    $serial_number = ! empty( $_POST['serial_number'] ) ? sanitize_text_field( $_POST['serial_number'] ) : ( $card['member']['serial_number'] ?? '001' );

    $full_message  = "🎉 Registration Confirmed for " . $member_name . "! 🎟️\n\n";
    $full_message .= "Event: " . $event_name . "\n";
    $full_message .= "Registration Serial: #" . $serial_number . "\n";
    if ( ! empty( $card['member']['program_name'] ) ) {
        $full_message .= "Program: " . $card['member']['program_name'] . "\n";
    }
    if ( ! empty( $card['member']['batch'] ) ) {
        $full_message .= "Batch: " . $card['member']['batch'] . "\n";
    }
    $full_message .= "Blood Group: " . $card['member']['blood_group'] . "\n\n";
    $full_message .= "Card URL: " . $card['url'] . "\n\n";
    $full_message .= "We are thrilled to welcome you! See you there!\n\n";
    $full_message .= "#" . preg_replace( '/\s+/', '', $org_name ) . " #EventRegistration #" . preg_replace( '/[^\p{L}\p{N}]+/u', '', $event_name );

    $payload = array(
        'event_type'    => 'event_registration',
        'test_mode'     => true,
        'member_name'   => $member_name,
        'email'         => $card['member']['email'],
        'cell_number'   => $card['member']['cell_number'],
        'event_name'    => $event_name,
        'serial_no'     => $serial_number,
        'serial_number' => $serial_number,
        'attendee_type' => $card['type'],
        'program'       => $card['member']['program_name'],
        'batch'         => $card['member']['batch'],
        'blood_group'   => $card['member']['blood_group'],
        'card_url'      => $card['url'],
        'photo_url'     => $card['member']['photo_url'],
        'org_name'      => $org_name,
        'org_logo_url'  => $org_logo,
        'message'       => $full_message,
        'dispatched_at' => current_time( 'mysql' ),
        'source_site'   => home_url(),
    );

    $custom_webhook_url = esc_url_raw( trim( $_POST['webhook_url'] ?? '' ) );
    $dispatch_all       = ! empty( $_POST['dispatch_all_webhooks'] ) && $_POST['dispatch_all_webhooks'] === 'on';

    $dispatch_results = array();

    // 1. Direct Webhook (e.g. Make.com scenario webhook URL)
    if ( ! empty( $custom_webhook_url ) ) {
        $args = array(
            'body'      => wp_json_encode( $payload ),
            'headers'   => array( 'Content-Type' => 'application/json; charset=utf-8' ),
            'timeout'   => 15,
            'blocking'  => true,
            'sslverify' => false,
        );

        $res = wp_remote_post( $custom_webhook_url, $args );

        if ( is_wp_error( $res ) ) {
            $dispatch_results[] = array(
                'url'     => $custom_webhook_url,
                'success' => false,
                'code'    => 0,
                'body'    => $res->get_error_message(),
            );
        } else {
            $code = wp_remote_retrieve_response_code( $res );
            $body = wp_remote_retrieve_body( $res );
            $dispatch_results[] = array(
                'url'     => $custom_webhook_url,
                'success' => ( $code >= 200 && $code < 300 ),
                'code'    => $code,
                'body'    => $body ? $body : 'Accepted',
            );
        }
    }

    // 2. Multi-Webhooks from settings if enabled
    if ( $dispatch_all && function_exists( 'batchmate_dispatch_webhooks' ) ) {
        $multi_res = batchmate_dispatch_webhooks( $payload, 'event_registration' );
        if ( ! empty( $multi_res['results'] ) ) {
            foreach ( $multi_res['results'] as $r ) {
                if ( empty( $custom_webhook_url ) || $r['url'] !== $custom_webhook_url ) {
                    $dispatch_results[] = array(
                        'url'     => $r['url'],
                        'success' => $r['success'] ?? false,
                        'code'    => $r['code'] ?? 0,
                        'body'    => $r['message'] ?? '',
                    );
                }
            }
        }
    }

    if ( empty( $dispatch_results ) ) {
        wp_send_json_error( 'No Webhook URL provided. Please enter a Make.com Webhook URL or enable multi-webhooks in Automation Settings.' );
    }

    $all_success = true;
    foreach ( $dispatch_results as $dr ) {
        if ( empty( $dr['success'] ) ) {
            $all_success = false;
            break;
        }
    }

    wp_send_json_success( array(
        'all_success' => $all_success,
        'results'     => $dispatch_results,
        'card_url'    => $card['url'],
        'payload'     => $payload,
    ) );
}

/**
 * ============================================================================
 * RENDER PAGE: Test Event Card & Make.com Automation
 * ============================================================================
 */
if ( ! function_exists( 'batchmate_render_test_event_card_page' ) ) {
function batchmate_render_test_event_card_page() {
    global $wpdb;
    $settings = get_option( 'batchmate_settings', array() );

    // Fetch First Member's Data from database
    $member = batchmate_get_test_member_data();

    // Fetch Events list
    $events = $wpdb->get_results( "
        SELECT id, event_name, event_date, card_template_url, card_zones_json 
        FROM {$wpdb->prefix}batchmate_event 
        ORDER BY id DESC
    " );

    $org_name     = function_exists( 'batchmate_org_label' ) ? batchmate_org_label( 'full' ) : ( $settings['org_name'] ?? 'BatchMate' );
    $org_logo_url = ! empty( $settings['org_logo_url'] ) ? $settings['org_logo_url'] : '';

    // Make.com webhook URL fallback
    $make_url = ! empty( $settings['make_webhook_url'] ) ? $settings['make_webhook_url'] : '';
    if ( empty( $make_url ) && ! empty( $settings['webhook_urls'] ) && is_array( $settings['webhook_urls'] ) ) {
        foreach ( $settings['webhook_urls'] as $wh ) {
            if ( ! empty( $wh['url'] ) && ( $wh['status'] ?? 'on' ) === 'on' ) {
                $make_url = $wh['url'];
                break;
            }
        }
    }

    $default_template = ! empty( $events[0]->card_template_url ) ? $events[0]->card_template_url : ( ROYAL_BATCHMATE_PLUGIN_URL . 'templates/birthdaycard1_bg.jpg' );
    ?>
    <div class="wrap BatchMate-admin-wrap" style="max-width: 1280px; margin-top: 15px;">
        <div class="BatchMate-page-header-animated" style="background: linear-gradient(135deg, #0f2756 0%, #1a3fa8 35%, #1565c0 60%, #0d47a1 100%) !important; color: #ffffff !important; padding: 24px 30px; border-radius: 12px; margin-bottom: 24px; box-shadow: 0 8px 24px rgba(15,39,86,0.3); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
            <div class="BatchMate-header-left" style="flex: 1; min-width: 280px;">
                <h1 style="color: #ffffff !important; margin: 0 0 6px; font-size: 22px; font-weight: 700; display: flex; align-items: center; gap: 10px;">
                    <span class="dashicons dashicons-id-alt" style="font-size: 24px; width: 24px; height: 24px; color: #38bdf8;"></span>
                    Test Event Card System &amp; Make.com
                </h1>
                <p style="color: rgba(255,255,255,0.85) !important; margin: 0; font-size: 13px;">
                    Generate 1080&times;1080 registration cards with first member data, overlay organization logo from settings, and test Make.com webhooks and email delivery.
                </p>
            </div>
            <div class="BatchMate-header-actions" style="display: flex; align-items: center; gap: 10px;">
                <span class="badge" style="background: rgba(255,255,255,0.18); color: #fff; padding: 7px 16px; border-radius: 20px; font-weight: 600; font-size: 13px; border: 1px solid rgba(255,255,255,0.35); display: inline-flex; align-items: center; gap: 6px;">
                    <span class="dashicons dashicons-calendar-alt" style="font-size: 16px; width: 16px; height: 16px;"></span> Org: <?php echo esc_html( $org_name ); ?>
                </span>
                <?php if ( ! empty( $org_logo_url ) ) : ?>
                    <div style="background: #ffffff; padding: 5px 12px; border-radius: 8px; display: inline-flex; align-items: center; box-shadow: 0 2px 8px rgba(0,0,0,0.15);">
                        <img src="<?php echo esc_url( $org_logo_url ); ?>" alt="Logo" style="max-height: 32px; max-width: 120px; vertical-align: middle;">
                    </div>
                <?php else : ?>
                    <span style="background: rgba(239,68,68,0.2); color: #fecaca; border: 1px solid rgba(239,68,68,0.4); padding: 5px 12px; border-radius: 6px; font-size: 12px;">
                        No logo in <a href="<?php echo admin_url('admin.php?page=batchmate_manage_settings#organization'); ?>" style="color: #fff; text-decoration: underline;" target="_blank">Settings #organization</a>
                    </span>
                <?php endif; ?>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 1.15fr 0.85fr; gap: 24px; align-items: start;">
            <!-- LEFT COLUMN: Form & Config -->
            <div class="card BatchMate-clean-form" style="margin: 0; padding: 24px; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.05);">
                <style>
                .BatchMate-clean-form .form-table {
                    margin-top: 0 !important;
                    width: 100% !important;
                    border-collapse: collapse !important;
                }
                .BatchMate-clean-form .form-table th {
                    width: 120px !important;
                    max-width: 120px !important;
                    min-width: 110px !important;
                    padding: 8px 12px 8px 0 !important;
                    vertical-align: middle !important;
                    font-size: 13px !important;
                    font-weight: 600 !important;
                    color: #1e293b !important;
                    white-space: nowrap !important;
                    text-align: left !important;
                }
                .BatchMate-clean-form .form-table td {
                    padding: 8px 0 !important;
                    vertical-align: middle !important;
                }
                .BatchMate-clean-form .form-table td input[type="text"],
                .BatchMate-clean-form .form-table td input[type="email"],
                .BatchMate-clean-form .form-table td input[type="url"],
                .BatchMate-clean-form .form-table td select {
                    box-sizing: border-box !important;
                    font-size: 13px !important;
                    padding: 6px 10px !important;
                    border: 1.5px solid #cbd5e1 !important;
                    border-radius: 6px !important;
                }
                .BatchMate-clean-form .form-table td input:focus,
                .BatchMate-clean-form .form-table td select:focus {
                    border-color: #2563eb !important;
                    box-shadow: 0 0 0 2px rgba(37,99,235,0.15) !important;
                    outline: none !important;
                }
                </style>
                <form id="BatchMate-test-event-form">
                    <input type="hidden" name="_wpnonce" value="<?php echo wp_create_nonce( 'BatchMate_settings_nonce' ); ?>">
                    <input type="hidden" id="card_zones_json" name="card_zones_json" value="<?php echo ! empty( $events[0]->card_zones_json ) ? esc_attr( $events[0]->card_zones_json ) : ''; ?>">

                    <h2 style="margin: 0 0 16px; font-size: 17px; color: #1e293b; border-bottom: 2px solid #f1f5f9; padding-bottom: 10px;">
                        <span class="dashicons dashicons-calendar-alt" style="color: #2563eb;"></span> 1. Event &amp; Card Design (1080x1080)
                    </h2>

                    <table class="form-table">
                        <tr>
                            <th scope="row"><label for="event_id">Select Event</label></th>
                            <td>
                                <select id="event_id" name="event_id" style="width: 100%;">
                                    <?php if ( ! empty( $events ) ) : ?>
                                        <?php foreach ( $events as $idx => $ev ) : ?>
                                            <option value="<?php echo esc_attr( $ev->id ); ?>" 
                                                    data-template="<?php echo esc_attr( $ev->card_template_url ); ?>"
                                                    data-name="<?php echo esc_attr( $ev->event_name ); ?>"
                                                    data-zones="<?php echo esc_attr( $ev->card_zones_json ); ?>"
                                                    <?php selected( $idx, 0 ); ?>>
                                                <?php echo esc_html( $ev->event_name . ' (' . $ev->event_date . ')' ); ?>
                                                <?php echo empty( $ev->card_template_url ) ? ' [No Template]' : ' [Has Template]'; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                    <option value="0" data-template="" data-name="Demo Event 2026">— Custom / Demo Event —</option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="event_name">Event Name</label></th>
                            <td>
                                <input type="text" id="event_name" name="event_name" value="<?php echo ! empty( $events[0]->event_name ) ? esc_attr( $events[0]->event_name ) : 'Annual Reunion 2026'; ?>" style="width: 100%;" required>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="card_template_url">Card Template</label></th>
                            <td>
                                <div style="display: flex; gap: 8px;">
                                    <input type="text" id="card_template_url" name="card_template_url" value="<?php echo esc_attr( $default_template ); ?>" style="flex: 1;" placeholder="https://.../card-1080x1080.jpg">
                                    <button type="button" id="btn-select-template" class="button button-secondary">Choose Image</button>
                                </div>
                                <p class="description" style="margin: 4px 0 0; font-size: 11px;">1080 &times; 1080 px template image recommended.</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="card_type">Attendee Type</label></th>
                            <td>
                                <select id="card_type" name="card_type" style="width: 180px;">
                                    <option value="Member" selected>Member</option>
                                    <option value="Non-Member">Non-Member (Guest)</option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Org Logo Overlay</th>
                            <td>
                                <label style="display: flex; align-items: center; gap: 8px;">
                                    <input type="checkbox" id="show_org_logo" name="show_org_logo" value="on" style="margin: 0;">
                                    <span style="font-size: 13px;">Overlay Organization Logo from <a href="<?php echo admin_url('admin.php?page=batchmate_manage_settings#organization'); ?>" target="_blank">Settings #organization</a> <small style="color: #888;">(Uncheck if template graphic already has logo)</small></span>
                                </label>
                            </td>
                        </tr>
                    </table>

                    <h2 style="margin: 24px 0 16px; font-size: 17px; color: #1e293b; border-bottom: 2px solid #f1f5f9; padding-bottom: 10px;">
                        <span class="dashicons dashicons-admin-users" style="color: #10b981;"></span> 2. Attendee Test Data (First Member from DB)
                    </h2>

                    <table class="form-table">
                        <!-- Serial Number (Prominent Option for Event Card) -->
                        <tr>
                            <th scope="row"><label for="serial_number">Serial No. <span class="required" style="color:#d63638;">*</span></label></th>
                            <td>
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <input type="text" id="serial_number" name="serial_number" value="TEST-001" style="width: 140px; font-weight: 700; font-size: 14px; color: #6b21a8; border: 1.5px solid #d8b4fe !important; background: #faf5ff !important;" required>
                                    <span style="font-size: 12px; color: #64748b;">(Attendee card serial / registration number)</span>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="member_name">Member Name</label></th>
                            <td>
                                <input type="text" id="member_name" name="member_name" value="<?php echo esc_attr( $member->name ); ?>" style="width: 100%;" required>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="recipient_email">Recipient Email</label></th>
                            <td>
                                <input type="email" id="recipient_email" name="recipient_email" value="<?php echo esc_attr( $member->email ); ?>" style="width: 100%;" required>
                                <p class="description" style="margin: 4px 0 0; font-size: 11px;">Test emails will be delivered to this address.</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="blood_group">Blood Group</label></th>
                            <td>
                                <select id="blood_group" name="blood_group" style="width: 120px;">
                                    <?php foreach ( ['A+', 'A-', 'B+', 'B-', 'O+', 'O-', 'AB+', 'AB-'] as $bg ) : ?>
                                        <option value="<?php echo $bg; ?>" <?php selected( $bg, $member->blood_group ); ?>><?php echo $bg; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="district">District (জেলা)</label></th>
                            <td>
                                <input type="text" id="district" name="district" value="<?php echo esc_attr( $member->present_district ?? ($member->permanent_district ?? 'Tangail') ); ?>" style="width: 100%;">
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="photo_url">Member Photo</label></th>
                            <td>
                                <div style="display: flex; gap: 8px; align-items: center;">
                                    <img id="test-member-photo-thumb" src="<?php echo esc_url( $member->photo_url ); ?>" style="width: 38px; height: 38px; border-radius: 50%; object-fit: cover; border: 1px solid #cbd5e1; flex-shrink: 0;">
                                    <input type="text" id="photo_url" name="photo_url" value="<?php echo esc_attr( $member->photo_url ); ?>" style="flex: 1;">
                                    <button type="button" id="btn-select-photo" class="button button-secondary" style="flex-shrink: 0;">Choose Photo</button>
                                </div>
                            </td>
                        </tr>
                    </table>

                    <!-- Optional Additional Fields Accordion -->
                    <details style="margin: 16px 0 0; border: 1.5px dashed #cbd5e1; border-radius: 8px; padding: 12px 16px; background: #f8fafc;">
                        <summary style="cursor: pointer; font-size: 13px; font-weight: 600; color: #475569; display: flex; align-items: center; gap: 6px;">
                            <span class="dashicons dashicons-admin-generic" style="font-size: 16px; width: 16px; height: 16px; color: #64748b;"></span>
                            <span>Optional Fields: Batch, Department &amp; Phone (Click to Expand)</span>
                        </summary>
                        <table class="form-table" style="margin-top: 10px;">
                            <tr>
                                <th scope="row"><label for="batch">Batch / Year</label></th>
                                <td>
                                    <input type="text" id="batch" name="batch" value="<?php echo esc_attr( $member->batch ); ?>" style="width: 140px;" placeholder="e.g. 1999">
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="program_name">Program / Dept</label></th>
                                <td>
                                    <input type="text" id="program_name" name="program_name" value="<?php echo esc_attr( $member->program_name ); ?>" style="width: 100%;">
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="cell_number">Cell / WhatsApp</label></th>
                                <td>
                                    <input type="text" id="cell_number" name="cell_number" value="<?php echo esc_attr( $member->cell_number ); ?>" style="width: 100%;">
                                </td>
                            </tr>
                        </table>
                    </details>

                    <h2 style="margin: 24px 0 16px; font-size: 17px; color: #1e293b; border-bottom: 2px solid #f1f5f9; padding-bottom: 10px;">
                        <span class="dashicons dashicons-rest-api" style="color: #8b5cf6;"></span> 3. Webhook / Make.com Connection
                    </h2>

                    <table class="form-table">
                        <tr>
                            <th scope="row"><label for="webhook_url">Webhook URL</label></th>
                            <td>
                                <input type="url" id="webhook_url" name="webhook_url" value="<?php echo esc_attr( $make_url ); ?>" style="width: 100%;" placeholder="https://hook.eu1.make.com/xxxxxxxxxxxxxxxxxxxxxxxx">
                                <p class="description" style="margin: 4px 0 0; font-size: 11px;">Paste your Make.com Scenario Custom Webhook URL to test live payload dispatch.</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Multi-Webhooks</th>
                            <td>
                                <label style="display: flex; align-items: center; gap: 8px;">
                                    <input type="checkbox" name="dispatch_all_webhooks" value="on" checked style="margin: 0;">
                                    <span style="font-size: 13px;">Also dispatch to all active Webhook connections configured in Settings</span>
                                </label>
                            </td>
                        </tr>
                    </table>
                                <input type="url" id="webhook_url" name="webhook_url" value="<?php echo esc_attr( $make_url ); ?>" class="regular-text" style="width: 100%;" placeholder="https://hook.eu1.make.com/xxxxxxxxxxxxxxxxxxxxxxxx">
                                <p class="description" style="margin-top: 4px;">Paste your Make.com Scenario Custom Webhook URL to test live payload dispatch.</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Multi-Webhooks</th>
                            <td>
                                <label>
                                    <input type="checkbox" name="dispatch_all_webhooks" value="on" checked>
                                    Also dispatch to all active Webhook connections configured in Settings
                                </label>
                            </td>
                        </tr>
                    </table>

                    <div style="margin-top: 28px; padding-top: 20px; border-top: 2px solid #f1f5f9; display: flex; flex-wrap: wrap; gap: 10px;">
                        <button type="button" id="btn-preview-card" class="button button-secondary button-large" style="display: inline-flex; align-items: center; gap: 6px;">
                            <span class="dashicons dashicons-visibility"></span> Generate &amp; Preview
                        </button>
                        <button type="button" id="btn-send-email" class="button button-primary button-large" style="display: inline-flex; align-items: center; gap: 6px;">
                            <span class="dashicons dashicons-email"></span> Send Test Email
                        </button>
                        <button type="button" id="btn-send-webhook" class="button button-large" style="background: #0284c7; border-color: #0284c7; color: #fff; display: inline-flex; align-items: center; gap: 6px;">
                            <span class="dashicons dashicons-rest-api"></span> Send to Make.com
                        </button>
                        <button type="button" id="btn-test-all" class="button button-large" style="background: #16a34a; border-color: #16a34a; color: #fff; display: inline-flex; align-items: center; gap: 6px;">
                            <span class="dashicons dashicons-yes-alt"></span> Run Full Test (All 3)
                        </button>
                    </div>
                </form>
            </div>

            <!-- RIGHT COLUMN: Card Preview & Webhook Response Box -->
            <div>
                <div class="card" style="margin: 0; padding: 24px; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); text-align: center;">
                    <h3 style="margin-top: 0; font-size: 16px; color: #1e293b; display: flex; justify-content: space-between; align-items: center;">
                        <span>Generated 1080&times;1080 Card Preview</span>
                        <span id="preview-badge" style="font-size: 12px; font-weight: normal; background: #f1f5f9; color: #64748b; padding: 3px 8px; border-radius: 6px;">Awaiting Generation</span>
                    </h3>

                    <div id="card-preview-container" style="position: relative; width: 100%; aspect-ratio: 1 / 1; max-width: 440px; margin: 15px auto; background: #f8fafc; border: 2px dashed #cbd5e1; border-radius: 12px; display: flex; align-items: center; justify-content: center; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.08);">
                        <img id="live-card-preview-img" src="" alt="Event Card" style="display: none; width: 100%; height: 100%; object-fit: contain;">
                        <div id="card-placeholder-text" style="color: #94a3b8; font-size: 14px; padding: 20px;">
                            <span class="dashicons dashicons-format-image" style="font-size: 48px; width: 48px; height: 48px; display: block; margin: 0 auto 10px; color: #cbd5e1;"></span>
                            Click <strong>Generate &amp; Preview</strong> or <strong>Send Test Email</strong> to render the 1080&times;1080 card.
                        </div>
                    </div>

                    <div id="card-url-bar" style="display: none; margin-top: 14px; text-align: left;">
                        <label style="font-size: 12px; font-weight: 600; color: #475569;">Card Image URL (1080x1080):</label>
                        <div style="display: flex; gap: 6px; margin-top: 4px;">
                            <input type="text" id="live-card-url" readonly style="flex: 1; font-size: 12px; background: #f8fafc;">
                            <button type="button" id="btn-copy-card-url" class="button button-small">Copy</button>
                            <a id="btn-open-card-url" href="" target="_blank" class="button button-small">Open</a>
                        </div>
                    </div>
                </div>

                <!-- Webhook Output Console -->
                <div class="card" style="margin: 24px 0 0; padding: 20px; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.05);">
                    <h3 style="margin-top: 0; font-size: 15px; color: #1e293b; display: flex; align-items: center; gap: 8px;">
                        <span class="dashicons dashicons-networking" style="color: #8b5cf6;"></span>
                        Make.com / Webhook Response Console
                    </h3>
                    <div id="webhook-console" style="background: #0f172a; color: #38bdf8; padding: 14px; border-radius: 8px; font-family: Consolas, Monaco, monospace; font-size: 12px; min-height: 140px; max-height: 280px; overflow-y: auto; line-height: 1.5;">
                        <span style="color: #64748b;">// Ready to test. Click "Send to Make.com" to view live HTTP response payload here...</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script type="text/javascript">
    jQuery(document).ready(function($) {
        var wpMediaFrame;

        // Sync dropdown event change
        $('#event_id').on('change', function() {
            var $opt   = $(this).find(':selected');
            var tpl    = $opt.data('template');
            var name   = $opt.data('name');
            var zones  = $opt.attr('data-zones') || '';
            if (name) $('#event_name').val(name);
            if (tpl)  $('#card_template_url').val(tpl);
            $('#card_zones_json').val(zones);
            // Automatically regenerate preview when changing event
            setTimeout(function() { $('#btn-preview-card').trigger('click'); }, 100);
        });

        // Choose Template Media Frame
        $('#btn-select-template').on('click', function(e) {
            e.preventDefault();
            wpMediaFrame = wp.media({
                title: 'Select Event Card Template (1080x1080)',
                button: { text: 'Use Template' },
                multiple: false
            });
            wpMediaFrame.on('select', function() {
                var attachment = wpMediaFrame.state().get('selection').first().toJSON();
                $('#card_template_url').val(attachment.url);
            });
            wpMediaFrame.open();
        });

        // Choose Photo Media Frame
        $('#btn-select-photo').on('click', function(e) {
            e.preventDefault();
            wpMediaFrame = wp.media({
                title: 'Select Member Photo',
                button: { text: 'Use Photo' },
                multiple: false
            });
            wpMediaFrame.on('select', function() {
                var attachment = wpMediaFrame.state().get('selection').first().toJSON();
                $('#photo_url').val(attachment.url);
                $('#test-member-photo-thumb').attr('src', attachment.url);
            });
            wpMediaFrame.open();
        });

        $('#photo_url').on('input change', function() {
            var url = $(this).val();
            if (url) $('#test-member-photo-thumb').attr('src', url);
        });

        // Copy Card URL
        $('#btn-copy-card-url').on('click', function() {
            var copyText = document.getElementById('live-card-url');
            copyText.select();
            copyText.setSelectionRange(0, 99999);
            document.execCommand('copy');
            Swal.fire({ title: 'Copied!', text: 'Card URL copied to clipboard', icon: 'success', timer: 1200, showConfirmButton: false });
        });

        function updatePreviewCard(url) {
            $('#card-placeholder-text').hide();
            $('#live-card-preview-img').attr('src', url).show();
            $('#live-card-url').val(url);
            $('#btn-open-card-url').attr('href', url);
            $('#card-url-bar').show();
            $('#preview-badge').text('1080 × 1080 Generated').css({ background: '#dcfce7', color: '#15803d' });
        }

        // Action 1: Preview Card
        $('#btn-preview-card').on('click', function() {
            var $btn = $(this);
            var origText = $btn.html();
            $btn.prop('disabled', true).html('<span class="dashicons dashicons-update BatchMate-spin"></span> Generating...');

            var data = $('#BatchMate-test-event-form').serialize() + '&action=batchmate_test_event_card_preview';

            $.post(ajaxurl, data, function(res) {
                if (res.success) {
                    updatePreviewCard(res.data.card_url);
                    Swal.fire({ title: 'Preview Ready!', text: res.data.message, icon: 'success', timer: 1500, showConfirmButton: false });
                } else {
                    Swal.fire('Error', res.data || 'Failed to generate card', 'error');
                }
            }).fail(function() {
                Swal.fire('Error', 'Server connection failed', 'error');
            }).always(function() {
                $btn.prop('disabled', false).html(origText);
            });
        });

        // Action 2: Send Test Email
        $('#btn-send-email').on('click', function() {
            var $btn = $(this);
            var origText = $btn.html();
            $btn.prop('disabled', true).html('<span class="dashicons dashicons-update BatchMate-spin"></span> Sending Email...');

            var data = $('#BatchMate-test-event-form').serialize() + '&action=batchmate_test_event_card_email';

            $.post(ajaxurl, data, function(res) {
                if (res.success) {
                    Swal.fire({ title: 'Email Sent!', text: res.data, icon: 'success' });
                    // Also trigger preview in background
                    $('#btn-preview-card').trigger('click');
                } else {
                    Swal.fire('Failed', res.data || 'Could not send email', 'error');
                }
            }).fail(function() {
                Swal.fire('Error', 'Server connection failed', 'error');
            }).always(function() {
                $btn.prop('disabled', false).html(origText);
            });
        });

        // Action 3: Send to Make.com / Webhook
        $('#btn-send-webhook').on('click', function() {
            var $btn = $(this);
            var origText = $btn.html();
            $btn.prop('disabled', true).html('<span class="dashicons dashicons-update BatchMate-spin"></span> Dispatching to Webhook...');

            var data = $('#BatchMate-test-event-form').serialize() + '&action=batchmate_test_event_card_webhook';
            $('#webhook-console').html('<span style="color:#fbbf24;">[DISPATCHING] Sending payload to webhook endpoint...</span>');

            $.post(ajaxurl, data, function(res) {
                if (res.success) {
                    var logHtml = '<span style="color:#4ade80;">[SUCCESS] Webhook dispatched successfully!</span>\n\n';
                    logHtml += '<span style="color:#94a3b8;">Target Endpoints:</span>\n';
                    $.each(res.data.results, function(i, r) {
                        var statusCol = r.success ? '#4ade80' : '#f87171';
                        logHtml += ' - ' + r.url + ' -> <strong style="color:'+statusCol+';">HTTP ' + r.code + ' (' + r.body + ')</strong>\n';
                    });
                    logHtml += '\n<span style="color:#94a3b8;">Payload Data Sent:</span>\n';
                    logHtml += JSON.stringify(res.data.payload, null, 2);

                    $('#webhook-console').html('<pre style="margin:0;white-space:pre-wrap;color:#38bdf8;">' + logHtml + '</pre>');
                    updatePreviewCard(res.data.card_url);

                    Swal.fire({ title: 'Webhook Dispatched!', text: 'Payload successfully received by Make.com / Webhook.', icon: 'success' });
                } else {
                    $('#webhook-console').html('<span style="color:#f87171;">[ERROR] ' + (res.data || 'Webhook failed') + '</span>');
                    Swal.fire('Webhook Error', res.data || 'Failed to dispatch webhook', 'error');
                }
            }).fail(function() {
                $('#webhook-console').html('<span style="color:#f87171;">[ERROR] Server request failed</span>');
                Swal.fire('Error', 'Server connection failed', 'error');
            }).always(function() {
                $btn.prop('disabled', false).html(origText);
            });
        });

        // Action 4: Run Full Test
        $('#btn-test-all').on('click', function() {
            Swal.fire({
                title: 'Run Full Test?',
                text: 'This will generate the 1080x1080 card, send a test email, and dispatch to Make.com / Webhook.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Yes, Run Full Test',
                confirmButtonColor: '#16a34a'
            }).then(function(result) {
                if (result.isConfirmed) {
                    $('#btn-send-email').trigger('click');
                    setTimeout(function() {
                        $('#btn-send-webhook').trigger('click');
                    }, 1200);
                }
            });
        });

        // Automatically trigger live preview on page load
        setTimeout(function() {
            $('#btn-preview-card').trigger('click');
        }, 400);
    });
    </script>
    <?php
}
}
