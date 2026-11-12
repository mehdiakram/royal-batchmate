<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Helper: Get first member's data from DB for testing
 */
if ( ! function_exists( 'batchmate_get_test_member_data' ) ) {
    function batchmate_get_test_member_data() {
        global $wpdb;
        if ( ! empty( $wpdb ) && isset( $wpdb->prefix ) ) {
            $member_table = $wpdb->prefix . 'batchmate_member';
            $prog_table   = $wpdb->prefix . 'batchmate_program';

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
                        'program_name'  => ( ! empty( $row->program_name ) && strtolower( trim( $row->program_name ) ) !== 'member' ) ? $row->program_name : '',
                        'dept_name'     => ! empty( $row->dept_name ) ? $row->dept_name : '',
                        'employment_position' => ! empty( $row->employment_position ) ? $row->employment_position : '',
                        'organization'  => ! empty( $row->organization ) ? $row->organization : '',
                        'program_short_code' => ! empty( $row->program_name ) ? $row->program_name : '',
                    );
                }
            }
        }

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
 * --- AJAX HANDLER: Send Test Birthday Email ---
 */
function batchmate_send_test_email_ajax() {
    if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], 'BatchMate_settings_nonce' ) ) {
        wp_send_json_error( 'Security check failed.', 403 );
    }
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( 'Permission denied.', 403 );
    }

    $recipient_email = sanitize_email( $_POST['recipient_email'] );
    $template_name   = sanitize_file_name( $_POST['template_name'] );

    if ( ! is_email( $recipient_email ) ) {
        wp_send_json_error( 'Invalid email address.' );
    }

    if ( ! function_exists( 'BatchMate_generate_custom_card' ) ) {
        $cron_file = ROYAL_BATCHMATE_PLUGIN_DIR . '/includes/cron-jobs.php';
        if ( file_exists( $cron_file ) ) require_once $cron_file;
    }

    $first_member = batchmate_get_test_member_data();

    $member_data = (object) array(
        'id'           => $first_member->id,
        'name'         => ! empty( $_POST['member_name'] ) ? sanitize_text_field( $_POST['member_name'] ) : $first_member->name,
        'email'        => $recipient_email,
        'photo_url'    => ! empty( $_POST['photo_url'] ) ? esc_url_raw( $_POST['photo_url'] ) : $first_member->photo_url,
        'program_name' => isset( $_POST['program_name'] ) ? sanitize_text_field( $_POST['program_name'] ) : ( $first_member->program_name ?? '' ),
        'batch'        => $first_member->batch ?? '',
        'blood_group'  => $first_member->blood_group ?? '',
        'dept_name'    => $first_member->dept_name ?? '',
        'employment_position' => $first_member->employment_position ?? '',
        'organization' => $first_member->organization ?? '',
    );

    // Generate 1080x1080 Card
    $attachment_path = BatchMate_generate_custom_card( $member_data, $template_name );

    if ( ! $attachment_path || ! file_exists( $attachment_path ) ) {
        wp_send_json_error( 'Failed to generate 1080x1080 birthday card image.' );
    }

    // --- Email Logic (URL + Attachment) ---
    $settings  = get_option( 'batchmate_settings', array() );
    $org_name  = function_exists( 'batchmate_org_label' ) ? batchmate_org_label( 'full' ) : ( $settings['org_name'] ?? 'BatchMate' );
    $org_email = ! empty( $settings['org_email'] ) ? $settings['org_email'] : get_bloginfo( 'admin_email' );
    
    // Subject with Timestamp
    $raw_subject = ! empty( $settings['birthday_email_subject'] ) ? $settings['birthday_email_subject'] : 'Happy Birthday, {name}!';
    $subject     = batchmate_replace_template_tags( $raw_subject, array(
        'name'     => $member_data->name,
        'org_name' => $org_name,
    ) ) . ' (Test ' . time() . ')';
    
    // URL for Inline Image
    $upload_dir = wp_upload_dir();
    $image_url  = str_replace( $upload_dir['basedir'], $upload_dir['baseurl'], $attachment_path );

    $card_img_tag = "<img src='" . esc_url( $image_url ) . "' alt='Birthday Card' style='max-width: 100%; height: auto; border-radius: 12px; border: 1px solid #cbd5e1; box-shadow: 0 6px 18px rgba(0,0,0,0.1);'>";
    $raw_body     = ! empty( $settings['birthday_email_body'] ) ? $settings['birthday_email_body'] : batchmate_get_default_template( 'birthday_email_body' );
    $body         = batchmate_replace_template_tags( $raw_body, array(
        'name'       => esc_html( $member_data->name ),
        'org_name'   => esc_html( $org_name ),
        'card_url'   => esc_url( $image_url ),
        'card_image' => $card_img_tag,
        'program'    => esc_html( $member_data->program_name ),
        'site_title' => esc_html( get_bloginfo( 'name' ) ),
    ) );
    $headers = array(
        'Content-Type: text/html; charset=UTF-8',
        'From: ' . $org_name . ' <' . $org_email . '>'
    );

    // Attach File
    $attachments = array( $attachment_path );

    $mail_sent = wp_mail( $recipient_email, $subject, $body, $headers, $attachments );
    
    if ( $mail_sent ) {
        wp_send_json_success( array(
            'message'  => 'Test email successfully sent to ' . $recipient_email . ' with 1080x1080 card attached.',
            'card_url' => $image_url,
        ) );
    } else {
        wp_send_json_error( 'Failed to send email via wp_mail(). Please check mail server.' );
    }
}
add_action( 'wp_ajax_batchmate_send_test_email', 'batchmate_send_test_email_ajax' );
add_action( 'wp_ajax_puua_send_test_email', 'batchmate_send_test_email_ajax' );

/**
 * --- AJAX HANDLER: Dispatch Birthday Test to Make.com / Webhook ---
 */
add_action( 'wp_ajax_batchmate_send_test_birthday_webhook', 'batchmate_send_test_birthday_webhook_ajax' );
function batchmate_send_test_birthday_webhook_ajax() {
    if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], 'BatchMate_settings_nonce' ) ) {
        wp_send_json_error( 'Security check failed.', 403 );
    }
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( 'Permission denied.', 403 );
    }

    if ( ! function_exists( 'BatchMate_generate_custom_card' ) ) {
        $cron_file = ROYAL_BATCHMATE_PLUGIN_DIR . '/includes/cron-jobs.php';
        if ( file_exists( $cron_file ) ) require_once $cron_file;
    }

    $template_name = sanitize_file_name( $_POST['template_name'] ?? 'birthdaycard1.php' );
    $first_member  = batchmate_get_test_member_data();

    $member_data = (object) array(
        'id'           => $first_member->id,
        'name'         => ! empty( $_POST['member_name'] ) ? sanitize_text_field( $_POST['member_name'] ) : $first_member->name,
        'email'        => sanitize_email( $_POST['recipient_email'] ?? $first_member->email ),
        'photo_url'    => ! empty( $_POST['photo_url'] ) ? esc_url_raw( $_POST['photo_url'] ) : $first_member->photo_url,
        'program_name' => isset( $_POST['program_name'] ) ? sanitize_text_field( $_POST['program_name'] ) : ( $first_member->program_name ?? '' ),
        'batch'        => $first_member->batch ?? '',
        'blood_group'  => $first_member->blood_group ?? '',
        'dept_name'    => $first_member->dept_name ?? '',
        'employment_position' => $first_member->employment_position ?? '',
        'organization' => $first_member->organization ?? '',
    );

    $card_path = BatchMate_generate_custom_card( $member_data, $template_name );
    if ( ! $card_path || ! file_exists( $card_path ) ) {
        wp_send_json_error( 'Failed to generate 1080x1080 card image.' );
    }

    $upload_dir = wp_upload_dir();
    $card_url   = str_replace( $upload_dir['basedir'], $upload_dir['baseurl'], $card_path );

    $settings     = get_option( 'batchmate_settings', array() );
    $org_name     = function_exists( 'batchmate_org_label' ) ? batchmate_org_label( 'full' ) : ( $settings['org_name'] ?? 'BatchMate' );
    $org_logo_url = ! empty( $settings['org_logo_url'] ) ? $settings['org_logo_url'] : '';

    $full_message  = "🎂 Happy Birthday, " . $member_data->name . "! 🎉🎈\n\n";
    $full_message .= "Wishing you a wonderful year filled with joy, great health, peace, and prosperous success!\n\n";
    if ( ! empty( $member_data->program_name ) ) {
        $full_message .= "Program: " . $member_data->program_name . "\n";
    }
    $full_message .= "Card URL: " . $card_url . "\n\n";
    $full_message .= "Warm Regards,\n" . $org_name . "\n";
    $full_message .= "#HappyBirthday #" . preg_replace( '/\s+/', '', $org_name );

    $payload = array(
        'event_type'    => 'birthday',
        'test_mode'     => true,
        'member_name'   => $member_data->name,
        'email'         => $member_data->email,
        'program'       => $member_data->program_name,
        'photo_url'     => $member_data->photo_url,
        'card_url'      => $card_url,
        'org_name'      => $org_name,
        'org_logo_url'  => $org_logo_url,
        'message'       => $full_message,
        'dispatched_at' => current_time( 'mysql' ),
        'source_site'   => home_url(),
    );

    $custom_url = esc_url_raw( trim( $_POST['webhook_url'] ?? '' ) );
    if ( empty( $custom_url ) ) {
        $custom_url = ! empty( $settings['make_webhook_url'] ) ? $settings['make_webhook_url'] : '';
    }

    $results = array();
    if ( ! empty( $custom_url ) ) {
        $res = wp_remote_post( $custom_url, array(
            'body'      => wp_json_encode( $payload ),
            'headers'   => array( 'Content-Type' => 'application/json; charset=utf-8' ),
            'timeout'   => 15,
            'blocking'  => true,
            'sslverify' => false,
        ) );

        if ( is_wp_error( $res ) ) {
            $results[] = array( 'url' => $custom_url, 'success' => false, 'code' => 0, 'body' => $res->get_error_message() );
        } else {
            $code = wp_remote_retrieve_response_code( $res );
            $results[] = array( 'url' => $custom_url, 'success' => ( $code >= 200 && $code < 300 ), 'code' => $code, 'body' => wp_remote_retrieve_body( $res ) );
        }
    }

    // Multi-webhooks fallback
    if ( function_exists( 'batchmate_dispatch_webhooks' ) ) {
        $multi = batchmate_dispatch_webhooks( $payload, 'birthday' );
        if ( ! empty( $multi['results'] ) ) {
            foreach ( $multi['results'] as $r ) {
                if ( empty( $custom_url ) || $r['url'] !== $custom_url ) {
                    $results[] = array( 'url' => $r['url'], 'success' => $r['success'] ?? false, 'code' => $r['code'] ?? 0, 'body' => $r['message'] ?? '' );
                }
            }
        }
    }

    if ( empty( $results ) ) {
        wp_send_json_error( 'Please enter a Make.com Webhook URL or configure webhooks in Automation Settings.' );
    }

    wp_send_json_success( array(
        'results'  => $results,
        'card_url' => $card_url,
        'payload'  => $payload,
    ) );
}

/**
 * --- AJAX HANDLER: Preview Birthday Card ---
 */
add_action( 'wp_ajax_batchmate_preview_birthday_card', 'batchmate_preview_birthday_card_ajax' );
function batchmate_preview_birthday_card_ajax() {
    if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], 'BatchMate_settings_nonce' ) ) {
        wp_send_json_error( __( 'Security check failed.', 'royal-batchmate' ), 403 );
    }
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( __( 'Permission denied.', 'royal-batchmate' ), 403 );
    }

    $template_name = sanitize_file_name( $_POST['template_name'] ?? 'birthdaycard1.php' );
    $template_name = str_replace( '.jpg', '.php', $template_name );
    $first_member  = batchmate_get_test_member_data();

    $member_data = (object) array(
        'id'           => $first_member->id,
        'name'         => ! empty( $_POST['member_name'] ) ? sanitize_text_field( $_POST['member_name'] ) : $first_member->name,
        'email'        => sanitize_email( $_POST['recipient_email'] ?? $first_member->email ),
        'photo_url'    => ! empty( $_POST['photo_url'] ) ? esc_url_raw( $_POST['photo_url'] ) : $first_member->photo_url,
        'program_name' => isset( $_POST['program_name'] ) ? sanitize_text_field( $_POST['program_name'] ) : ( $first_member->program_name ?? '' ),
        'batch'        => $first_member->batch ?? '',
        'blood_group'  => $first_member->blood_group ?? '',
        'dept_name'    => $first_member->dept_name ?? '',
        'employment_position' => $first_member->employment_position ?? '',
        'organization' => $first_member->organization ?? '',
    );

    $settings_override = array();
    if ( isset( $_POST['birthday_card_badge_field'] ) ) {
        $settings_override['birthday_card_badge_field'] = sanitize_text_field( $_POST['birthday_card_badge_field'] );
    }
    if ( isset( $_POST['birthday_card_custom_subtitle'] ) ) {
        $settings_override['birthday_card_custom_subtitle'] = sanitize_text_field( $_POST['birthday_card_custom_subtitle'] );
    }

    if ( ! function_exists( 'BatchMate_generate_custom_card' ) ) {
        $cron_file = ROYAL_BATCHMATE_PLUGIN_DIR . '/includes/cron-jobs.php';
        if ( file_exists( $cron_file ) ) require_once $cron_file;
    }

    $custom_output_path = null;
    $upload_dir = wp_upload_dir();
    $basedir    = function_exists( 'trailingslashit' ) ? trailingslashit( $upload_dir['basedir'] ) : ( rtrim( $upload_dir['basedir'], '/\\' ) . '/' );

    if ( ! empty( $_POST['is_preview_cache'] ) ) {
        $base_slug  = sanitize_file_name( str_replace( '.php', '', $template_name ) );
        $custom_output_path = $basedir . 'BatchMate-cards/preview_' . $base_slug . '.jpg';
    }

    $card_path = BatchMate_generate_custom_card( $member_data, $template_name, $settings_override, $custom_output_path );
    if ( ! $card_path || ! file_exists( $card_path ) ) {
        wp_send_json_error( __( 'Failed to generate 1080x1080 card image.', 'royal-batchmate' ) );
    }

    $card_url = str_replace( $upload_dir['basedir'], $upload_dir['baseurl'], $card_path );

    wp_send_json_success( array(
        'card_url' => $card_url,
        'message'  => __( '1080x1080 card generated successfully!', 'royal-batchmate' )
    ) );
}

/**
 * --- RENDER ADMIN PAGE ---
 */
if ( ! function_exists( 'batchmate_render_test_birthday_page' ) ) {
function batchmate_render_test_birthday_page() {
    $settings = get_option( 'batchmate_settings', array() );
    $default_template = isset( $settings['birthday_card_template'] ) ? $settings['birthday_card_template'] : 'birthdaycard1.php';
    $default_template = str_replace( '.jpg', '.php', $default_template );

    $first_member = batchmate_get_test_member_data();

    $templates = array(
        'random'            => '🎲 Random (Different Design Each Time)',
        'birthdaycard1.php' => 'Design One — Midnight Royal & Gold (1080x1080)',
        'birthdaycard2.php' => 'Design Two — Crimson & Rose Gold (1080x1080)', 
        'birthdaycard3.php' => 'Design Three — Emerald & Gold (1080x1080)'
    );
    
    $org_name     = function_exists( 'batchmate_org_label' ) ? batchmate_org_label( 'full' ) : ( $settings['org_name'] ?? 'BatchMate' );
    $org_logo_url = ! empty( $settings['org_logo_url'] ) ? $settings['org_logo_url'] : '';
    $make_url     = ! empty( $settings['make_webhook_url'] ) ? $settings['make_webhook_url'] : '';
    ?>
    <div class="wrap BatchMate-admin-wrap" style="max-width: 1240px; margin-top: 15px;">
        <div class="BatchMate-page-header-animated" style="background: linear-gradient(135deg, #0f2756 0%, #1a3fa8 35%, #1565c0 60%, #0d47a1 100%) !important; color: #ffffff !important; padding: 24px 30px; border-radius: 12px; margin-bottom: 24px; box-shadow: 0 8px 24px rgba(15,39,86,0.3); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
            <div class="BatchMate-header-left" style="flex: 1; min-width: 280px;">
                <h1 style="color: #ffffff !important; margin: 0 0 6px; font-size: 22px; font-weight: 700; display: flex; align-items: center; gap: 10px;">
                    <span class="dashicons dashicons-email-alt" style="font-size: 24px; width: 24px; height: 24px; color: #fde047;"></span>
                    Test Birthday Card System &amp; Make.com
                </h1>
                <p style="color: rgba(255,255,255,0.85) !important; margin: 0; font-size: 13px;">
                    Generates 1080&times;1080 pure GD canvas birthday cards using first member's real data from database and logo from Settings #organization.
                </p>
            </div>
            <div class="BatchMate-header-actions" style="display: flex; align-items: center; gap: 10px;">
                <span class="badge" style="background: rgba(255,255,255,0.18); color: #fff; padding: 7px 16px; border-radius: 20px; font-weight: 600; font-size: 13px; border: 1px solid rgba(255,255,255,0.35); display: inline-flex; align-items: center; gap: 6px;">
                    <span class="dashicons dashicons-format-image" style="font-size: 16px; width: 16px; height: 16px;"></span> 1080 &times; 1080 Square Canvas
                </span>
                <?php if ( ! empty( $org_logo_url ) ) : ?>
                    <div style="background: #ffffff; padding: 5px 12px; border-radius: 8px; display: inline-flex; align-items: center; box-shadow: 0 2px 8px rgba(0,0,0,0.15);">
                        <img src="<?php echo esc_url( $org_logo_url ); ?>" alt="Logo" style="max-height: 32px; max-width: 120px; vertical-align: middle;">
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 1.1fr 0.9fr; gap: 24px; align-items: start;">
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
                <form id="BatchMate-test-email-form">
                    <input type="hidden" name="action" value="batchmate_send_test_email">
                    <input type="hidden" name="_wpnonce" value="<?php echo wp_create_nonce('BatchMate_settings_nonce'); ?>">
                    
                    <h2 style="margin: 0 0 16px; font-size: 17px; color: #1e293b; border-bottom: 2px solid #f1f5f9; padding-bottom: 10px;">
                        Attendee Data (First Member from DB)
                    </h2>

                    <table class="form-table" style="margin-top: 0;">
                        <tr>
                            <th scope="row"><label for="recipient_email">Recipient Email</label></th>
                            <td>
                                <input type="email" id="recipient_email" name="recipient_email" value="<?php echo esc_attr( $first_member->email ); ?>" class="regular-text" style="width: 100%;" required />
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="member_name">Member Name</label></th>
                            <td>
                                <input type="text" id="member_name" name="member_name" value="<?php echo esc_attr( $first_member->name ); ?>" class="regular-text" style="width: 100%;" required />
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="program_name">Card Subtitle / Badge</label></th>
                            <td>
                                <?php
                                $mapped_subtitle = function_exists('batchmate_get_birthday_card_subtitle') ? batchmate_get_birthday_card_subtitle( $first_member ) : ( $first_member->program_name ?? '' );
                                ?>
                                <input type="text" id="program_name" name="program_name" value="<?php echo esc_attr( $mapped_subtitle ); ?>" class="regular-text" style="width: 100%;" placeholder="Leave blank to hide badge (mapped from Settings #birthday)" />
                                <p class="description" style="margin-top: 4px; font-size: 11.5px; color: #64748b;"><?php esc_html_e( 'Leave empty to omit subtitle. You can also configure this automatically in Settings #automation.', 'royal-batchmate' ); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="photo_url">Photo URL</label></th>
                            <td>
                                <div style="display: flex; gap: 8px; align-items: center;">
                                    <img id="bday-photo-thumb" src="<?php echo esc_url( $first_member->photo_url ); ?>" style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover; border: 1px solid #cbd5e1;">
                                    <input type="text" id="photo_url" name="photo_url" value="<?php echo esc_attr( $first_member->photo_url ); ?>" class="regular-text" style="flex: 1;" />
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="test_template_name">Select Design</label></th>
                            <td>
                                <select id="test_template_name" name="template_name" class="regular-text" style="width: 100%;">
                                    <?php foreach( $templates as $file => $label ): ?>
                                        <option value="<?php echo esc_attr( $file ); ?>" <?php selected( $file, $default_template ); ?>>
                                             <?php echo esc_html( $label ); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="webhook_url">Make.com Webhook</label></th>
                            <td>
                                <input type="url" id="webhook_url" name="webhook_url" value="<?php echo esc_attr( $make_url ); ?>" class="regular-text" style="width: 100%;" placeholder="https://hook.eu1.make.com/..." />
                            </td>
                        </tr>
                    </table>

                    <div style="margin-top: 24px; padding-top: 18px; border-top: 2px solid #f1f5f9; display: flex; gap: 10px; flex-wrap: wrap;">
                        <button type="button" id="btn-bday-preview" class="button button-secondary button-large" style="display: inline-flex; align-items: center; gap: 6px;">
                            <span class="dashicons dashicons-visibility"></span> Generate Preview
                        </button>
                        <button type="submit" id="send-test-email" class="button button-primary button-large" style="display: inline-flex; align-items: center; gap: 6px;">
                            <span class="dashicons dashicons-email"></span> Send Test Email
                        </button>
                        <button type="button" id="btn-bday-webhook" class="button button-large" style="background: #0284c7; border-color: #0284c7; color: #fff; display: inline-flex; align-items: center; gap: 6px;">
                            <span class="dashicons dashicons-rest-api"></span> Send to Make.com
                        </button>
                    </div>
                </form>
            </div>

            <div class="card" style="margin: 0; padding: 24px; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); text-align: center;">
                <h3 style="margin-top: 0; font-size: 16px; color: #1e293b;">1080&times;1080 Card Preview</h3>
                <div style="width: 100%; aspect-ratio: 1 / 1; max-width: 420px; margin: 15px auto; background: #0b192c; border-radius: 12px; overflow: hidden; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 15px rgba(0,0,0,0.15);">
                    <img id="test-bday-preview-img" src="" alt="Birthday Card" style="display: none; width: 100%; height: 100%; object-fit: contain;">
                    <div id="bday-placeholder" style="color: #94a3b8; font-size: 14px; padding: 20px;">
                        Click <strong>Generate Preview</strong>, <strong>Send Test Email</strong>, or <strong>Send to Make.com</strong> to render the 1080&times;1080 card.
                    </div>
                </div>
                <div id="bday-console" style="margin-top: 15px; text-align: left; background: #0f172a; color: #38bdf8; padding: 12px; border-radius: 8px; font-family: monospace; font-size: 11px; max-height: 180px; overflow-y: auto; display: none;"></div>
            </div>
        </div>
    </div>
    <script type="text/javascript">
    jQuery(document).ready(function($) {
        function generateBdayPreview() {
            var btn = $('#btn-bday-preview');
            var orig = btn.html();
            btn.prop('disabled', true).html('<span class="dashicons dashicons-update BatchMate-spin"></span> Rendering...');
            var data = $('#BatchMate-test-email-form').serialize() + '&action=batchmate_preview_birthday_card';
            $.post(ajaxurl, data, function(res) {
                if (res.success && res.data.card_url) {
                    $('#bday-placeholder').hide();
                    $('#test-bday-preview-img').attr('src', res.data.card_url + '?t=' + new Date().getTime()).show();
                } else {
                    Swal.fire({ title: 'Error', text: res.data || 'Failed to render card preview.', icon: 'error' });
                }
            }).fail(function() {
                Swal.fire('Error', 'Server connection failed while rendering preview.', 'error');
            }).always(function() {
                btn.prop('disabled', false).html(orig);
            });
        }

        $('#btn-bday-preview').on('click', function(e) {
            e.preventDefault();
            generateBdayPreview();
        });

        $('#test_template_name').on('change', function() {
            generateBdayPreview();
        });

        $('#photo_url').on('change input', function() {
            var url = $(this).val();
            if (url) {
                $('#bday-photo-thumb').attr('src', url);
            }
        });

        // Auto-generate preview on load
        setTimeout(generateBdayPreview, 300);

        $('#BatchMate-test-email-form').on('submit', function(e) {
            e.preventDefault();
            var btn = $('#send-test-email');
            var originalText = btn.html();
            btn.prop('disabled', true).html('<span class="dashicons dashicons-update BatchMate-spin"></span> Sending...');
            $.post(ajaxurl, $(this).serialize(), function(response) {
                if (response.success) {
                    Swal.fire({ title: 'Success!', text: response.data.message || response.data, icon: 'success' });
                    if (response.data.card_url) {
                        $('#bday-placeholder').hide();
                        $('#test-bday-preview-img').attr('src', response.data.card_url + '?t=' + new Date().getTime()).show();
                    }
                } else {
                    Swal.fire({ title: 'Error!', text: response.data, icon: 'error' });
                }
            }).always(function() { btn.prop('disabled', false).html(originalText); });
        });

        $('#btn-bday-webhook').on('click', function() {
            var btn = $(this);
            var originalText = btn.html();
            btn.prop('disabled', true).html('<span class="dashicons dashicons-update BatchMate-spin"></span> Dispatching...');
            var data = $('#BatchMate-test-email-form').serialize() + '&action=batchmate_send_test_birthday_webhook';
            $.post(ajaxurl, data, function(res) {
                if (res.success) {
                    Swal.fire({ title: 'Dispatched to Make.com!', text: 'Payload successfully sent to Make.com / Webhook.', icon: 'success' });
                    if (res.data.card_url) {
                        $('#bday-placeholder').hide();
                        $('#test-bday-preview-img').attr('src', res.data.card_url + '?t=' + new Date().getTime()).show();
                    }
                    $('#bday-console').show().html('<pre style="margin:0;color:#38bdf8;">' + JSON.stringify(res.data, null, 2) + '</pre>');
                } else {
                    Swal.fire('Error', res.data || 'Failed to dispatch', 'error');
                }
            }).always(function() { btn.prop('disabled', false).html(originalText); });
        });
    });
    </script>
    <?php
}
}
