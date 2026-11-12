<?php
// File: includes/cron-jobs.php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * 1. Schedule Crons
 */
function BatchMate_schedule_events() {
    // Performance optimization: verify crons only once per day using transient
    if ( get_transient( 'batchmate_crons_scheduled' ) ) {
        return;
    }

    // বার্থডে চেক (দিনে ১ বার, রাত ১২:০১ এ)
    if ( ! wp_next_scheduled( 'BatchMate_daily_birthday_event' ) ) {
        $time = strtotime( 'tomorrow 00:01:00', current_time( 'timestamp' ) );
        wp_schedule_event( $time, 'daily', 'BatchMate_daily_birthday_event' );
    }
    
    // কার্ড ক্লিনআপ (দিনে ১ বার)
    if ( ! wp_next_scheduled( 'BatchMate_hourly_card_cleanup' ) ) {
        wp_schedule_event( time(), 'daily', 'BatchMate_hourly_card_cleanup' );
    }

    set_transient( 'batchmate_crons_scheduled', 1, DAY_IN_SECONDS );
}
add_action( 'init', 'BatchMate_schedule_events' );

add_action( 'BatchMate_daily_birthday_event', 'BatchMate_process_birthday_emails' );
add_action( 'BatchMate_hourly_card_cleanup', 'BatchMate_delete_old_cards' );

/**
 * 2. Process Emails & Facebook Post
 */
function BatchMate_process_birthday_emails() {
    set_time_limit(0); 

    global $wpdb;
    $settings = get_option('batchmate_settings', array());
    
    // ১. মাস্টার সুইচ চেক
    if ( isset($settings['birthday_active']) && $settings['birthday_active'] !== 'on' ) {
        return; 
    }

    $email_active = isset($settings['email_active']) && $settings['email_active'] === 'on';
    $sms_active   = isset($settings['birthday_sms_active']) && $settings['birthday_sms_active'] === 'on';
    
    // পোস্ট মেথড ডিটেকশন
    $fb_method     = isset($settings['fb_post_method']) ? $settings['fb_post_method'] : 'none'; // none, access_token, make_com
    $is_fb_enabled = ($fb_method === 'access_token' || $fb_method === 'make_com');
    $has_webhooks  = function_exists('batchmate_has_active_webhooks_or_buffer')
        ? batchmate_has_active_webhooks_or_buffer($settings)
        : (!empty($settings['webhook_urls']) || !empty($settings['make_webhook_url']));

    if ( ! $email_active && ! $sms_active && ! $is_fb_enabled && ! $has_webhooks ) {
        return;
    }

    $today_md = current_time('m-d'); 
    
    $table_member = $wpdb->prefix . 'batchmate_member';
    $table_program = $wpdb->prefix . 'batchmate_program';

    $sql = "SELECT m.*, p.program_name 
            FROM $table_member m
            LEFT JOIN $table_program p ON m.program_id = p.id
            WHERE DATE_FORMAT(m.date_of_birth, '%%m-%%d') = %s
            AND ( 
                (m.email IS NOT NULL AND m.email != '') 
                OR 
                (m.cell_number IS NOT NULL AND m.cell_number != '') 
            )";

    $members = $wpdb->get_results( $wpdb->prepare( $sql, $today_md ) );

    if ( ! empty( $members ) ) {
        foreach ( $members as $member ) {
            // কার্ড জেনারেট (Create the Card)
            $card_path = BatchMate_generate_custom_card( $member );

            if ( $card_path && file_exists($card_path) ) {
                
                // ১. ইমেইল পাঠানো (যদি অন থাকে)
                if ( $email_active && ! empty( $member->email ) ) {
                    BatchMate_send_email_with_card( $member, $card_path );
                }

                // ২. SMS পাঠানো (যদি অন থাকে)
                if ( $sms_active && ! empty( $member->cell_number ) && function_exists('batchmate_send_sms') ) {
                    $org_name = batchmate_org_label( 'short' );
                    $raw_sms  = !empty($settings['birthday_sms_template']) 
                        ? $settings['birthday_sms_template'] 
                        : batchmate_get_default_template('birthday_sms');

                    $sms_text = batchmate_replace_template_tags( $raw_sms, array(
                        'name'       => $member->name,
                        'org_name'   => $org_name,
                        'program'    => $member->program_name ?? '',
                    ) );

                    // SMS শিডিউল টাইম চেক
                    $sms_time_opt = $settings['birthday_sms_time'] ?? 'morning_9am';
                    $schedule_dt  = null;
                    if ( $sms_time_opt === 'morning_9am' ) {
                        $schedule_dt = current_time('Y-m-d') . ' 09:00:00';
                    } elseif ( $sms_time_opt === 'morning_10am' ) {
                        $schedule_dt = current_time('Y-m-d') . ' 10:00:00';
                    }

                    batchmate_send_sms( $member->cell_number, $sms_text, $schedule_dt );
                }

                // ৩. ফেসবুক / Webhook পোস্ট
                if ( $fb_method === 'access_token' ) {
                    // Direct FB API
                    BatchMate_post_to_facebook_page_php( $member, $card_path );
                    sleep(8); 
                }

                if ( $fb_method === 'make_com' || $has_webhooks ) {
                    // Multi-Webhook Dispatch
                    BatchMate_post_to_make_webhook( $member, $card_path );
                }
            }
        }
    }
}

/**
 * [UPDATED] Post to make.com Webhook
 * - মেসেজে নাম যুক্ত করা হয়েছে (Happy Birthday to...)
 * - শুধু জেনারেট করা কার্ডের লিংক পাঠানো হচ্ছে
 */
function BatchMate_post_to_make_webhook( $member, $card_path ) {
    $settings = get_option('batchmate_settings', array());

    // ১. কার্ডের পাবলিক URL তৈরি
    $upload_dir = wp_upload_dir();
    $card_url = str_replace( $upload_dir['basedir'], $upload_dir['baseurl'], $card_path );

    // ২. মেসেজ তৈরি
    $org = batchmate_org_label( 'short' );
    $greetings = array(
        "On behalf of the {$org}, we wish you a day filled with happiness.",
        "Wishing you a very Happy Birthday! May this day bring you joy, success, and endless memories.",
        "Happy Birthday from {$org}! We are proud to have you as a member of our family. Have a blast!",
        "Warmest birthday wishes from the {$org}. Enjoy your special day!"
    );
    $random_msg = $greetings[array_rand($greetings)];

    // Full message construction
    $full_message = "🎉 Happy Birthday to " . $member->name . "! 🎂\n\n";
    $full_message .= $random_msg . "\n\n";
    $full_message .= "#" . preg_replace('/\s+/', '', $org) . " #HappyBirthday";

    // ৩. ডেটা তৈরি (Payload)
    $payload = array(
        'event'       => 'birthday',
        'member_id'   => $member->id ?? 0,
        'member_name' => $member->name,
        'email'       => $member->email ?? '',
        'phone'       => $member->cell_number ?? '',
        'program'     => $member->program_name ?? '',
        'birthday'    => current_time('Y-m-d'),
        'card_url'    => $card_url,
        'message'     => $full_message
    );

    // ৪. Dispatch to Webhooks (Multi-Webhook service)
    if ( function_exists('batchmate_dispatch_webhooks') ) {
        batchmate_dispatch_webhooks( $payload, 'birthday' );
    } else {
        $webhook_url = isset($settings['make_webhook_url']) ? $settings['make_webhook_url'] : '';
        if ( ! empty($webhook_url) ) {
            wp_remote_post( $webhook_url, array(
                'body'    => wp_json_encode($payload),
                'headers' => array( 'Content-Type' => 'application/json' ),
                'timeout' => 15,
            ) );
        }
    }
}


/**
 * [Function] Post directly to Facebook Page via PHP (Old Method)
 */
function BatchMate_post_to_facebook_page_php( $member, $card_path ) {
    
    $settings = get_option('batchmate_settings');
    
    if ( empty($settings['fb_page_id']) || empty($settings['fb_access_token']) ) {
        error_log('BatchMate FB Error: Credentials missing.');
        return;
    }

    $page_id      = $settings['fb_page_id'];
    $access_token = $settings['fb_access_token'];

    $org = batchmate_org_label( 'short' );
    $greetings = array(
        "On behalf of the {$org}, we wish you a day filled with happiness.",
        "Wishing you a very Happy Birthday! May this day bring you joy, success, and endless memories.",
        "Happy Birthday from {$org}! We are proud to have you as a member of our family. Have a blast!",
        "Warmest birthday wishes from the {$org}. Enjoy your special day!"
    );
    $random_msg = $greetings[array_rand($greetings)];

    $message = "🎉 Happy Birthday, " . $member->name . "! 🎂\n\n";
    $message .= $random_msg . "\n\n";
    $message .= "#" . preg_replace('/\s+/', '', $org) . " #HappyBirthday";

    $data = array(
        'message' => $message,
        'access_token' => $access_token
    );

    if ( function_exists('curl_file_create') ) {
        $data['source'] = curl_file_create( $card_path );
    } else {
        $data['source'] = '@' . realpath( $card_path );
    }

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://graph.facebook.com/v19.0/' . $page_id . '/photos');
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $result = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error) {
        error_log('BatchMate FB cURL Error: ' . $error);
    } else {
        $response = json_decode($result, true);
        if ( isset($response['id']) ) {
            error_log('BatchMate FB Success: Post ID ' . $response['id']);
        } else {
            error_log('BatchMate FB API Error: ' . print_r($result, true));
        }
    }
}

/**
 * Helper to determine what subtitle/badge text should appear on the birthday card.
 * Respects settings['birthday_card_badge_field'] and avoids hardcoded 'Member'.
 */
function batchmate_get_birthday_card_subtitle( $member, $settings = null ) {
    if ( $settings === null ) {
        $settings = get_option( 'batchmate_settings', array() );
    }

    $field = $settings['birthday_card_badge_field'] ?? 'none';
    if ( $field === 'none' || empty( $field ) ) {
        return '';
    }

    $get = function( $key ) use ( $member ) {
        if ( is_array( $member ) ) return $member[ $key ] ?? '';
        if ( is_object( $member ) ) return $member->$key ?? '';
        return '';
    };

    switch ( $field ) {
        case 'batch':
            $batch = $get('batch');
            return ! empty( $batch ) ? ( 'Batch ' . $batch ) : '';
        case 'dept':
            $dept = $get('dept_name');
            if ( empty( $dept ) ) $dept = $get('dept');
            if ( empty( $dept ) ) $dept = $get('department');
            return ! empty( $dept ) ? $dept : '';
        case 'profession':
            $pos = $get('employment_position');
            $org = $get('organization');
            if ( ! empty( $pos ) && ! empty( $org ) ) return $pos . ', ' . $org;
            return ! empty( $pos ) ? $pos : ( ! empty( $org ) ? $org : '' );
        case 'blood_group':
            $bg = $get('blood_group');
            return ! empty( $bg ) ? ( 'Blood: ' . $bg ) : '';
        case 'program':
            $prog = $get('program_name');
            if ( empty( $prog ) ) $prog = $get('program');
            if ( strtolower( trim( (string) $prog ) ) === 'member' ) return '';
            return ! empty( $prog ) ? $prog : '';
        case 'custom':
            return $settings['birthday_card_custom_subtitle'] ?? '';
        default:
            return '';
    }
}

/**
 * 3. Card Generator
 */
function BatchMate_generate_custom_card( $member_data, $template_override = null, $settings_override = null, $custom_output_path = null ) {
    $settings = get_option('batchmate_settings', array());
    if ( is_array( $settings_override ) && ! empty( $settings_override ) ) {
        $settings = array_merge( $settings, $settings_override );
    }
    
    if ( ! empty( $template_override ) ) {
        $selected_template = $template_override;
    } else {
        $saved_template = isset($settings['birthday_card_template']) ? $settings['birthday_card_template'] : 'birthdaycard1.php';
        $selected_template = str_replace('.jpg', '.php', $saved_template);
    }
    $selected_template = sanitize_file_name( $selected_template );
    if ( empty( $selected_template ) ) {
        $selected_template = 'birthdaycard1.php';
    }

    if ( is_array( $member_data ) ) {
        $user_name       = ! empty( $member_data['name'] ) ? $member_data['name'] : 'Batchmate';
        $user_photo_url  = ! empty( $member_data['photo_url'] ) ? $member_data['photo_url'] : ''; 
        $program_name    = ! empty( $member_data['program_name'] ) ? $member_data['program_name'] : ( $member_data['program'] ?? '' ); 
    } else {
        $user_name       = ! empty( $member_data->name ) ? $member_data->name : 'Batchmate';
        $user_photo_url  = ! empty( $member_data->photo_url ) ? $member_data->photo_url : ''; 
        $program_name    = ! empty( $member_data->program_name ) ? $member_data->program_name : ( $member_data->program ?? '' ); 
    }

    // Resolve subtitle/badge based on admin mapping
    $badge_text = batchmate_get_birthday_card_subtitle( $member_data, $settings );
    if ( empty( $badge_text ) && ! empty( $program_name ) && strtolower( trim( (string) $program_name ) ) !== 'member' ) {
        $badge_text = $program_name;
    }

    $upload_dir = wp_upload_dir();
    $basedir    = function_exists( 'trailingslashit' ) ? trailingslashit( $upload_dir['basedir'] ) : ( rtrim( $upload_dir['basedir'], '/\\' ) . '/' );
    $output_dir = $basedir . 'BatchMate-cards/';
    
    if ( ! file_exists( $output_dir ) ) {
        if ( function_exists( 'wp_mkdir_p' ) ) {
            wp_mkdir_p( $output_dir );
        }
        if ( ! file_exists( $output_dir ) ) {
            @mkdir( $output_dir, 0755, true );
        }
    }

    if ( ! empty( $custom_output_path ) ) {
        $output_path = $custom_output_path;
    } else {
        $safe_name = sanitize_file_name( str_replace( ' ', '_', $user_name ) );
        $filename = $safe_name . '_' . time() . '.jpg';
        $output_path = $output_dir . $filename;
    }

    $template_file = ROYAL_BATCHMATE_PLUGIN_DIR . '/templates/' . $selected_template;

    if ( file_exists( $template_file ) ) {
        ob_start(); 
        include $template_file; 
        ob_end_clean(); 
        
        if ( file_exists( $output_path ) ) {
            return $output_path;
        }
    }

    // Fallback: If selected template failed or did not generate output, fallback to birthdaycard1.php
    $fallback_file = ROYAL_BATCHMATE_PLUGIN_DIR . '/templates/birthdaycard1.php';
    if ( file_exists( $fallback_file ) && $selected_template !== 'birthdaycard1.php' ) {
        ob_start();
        include $fallback_file;
        ob_end_clean();
        if ( file_exists( $output_path ) ) {
            return $output_path;
        }
    }

    return false;
}

/**
 * Auto-generates or retrieves the birthday card template preview URL dynamically.
 * Renders the pure GD canvas template instead of relying on hardcoded static images.
 *
 * @param string $template_name Optional template filename (e.g. 'birthdaycard1.php').
 * @param bool   $force         Whether to force regeneration of the preview image.
 * @param array  $settings_override Optional settings array to override.
 * @return string The URL to the dynamically generated preview JPEG image.
 */
function batchmate_get_or_generate_card_preview( $template_name = 'birthdaycard1.php', $force = false, $settings_override = null ) {
    $template_name = sanitize_file_name( $template_name );
    if ( empty( $template_name ) ) {
        $template_name = 'birthdaycard1.php';
    }
    $template_name = str_replace( '.jpg', '.php', $template_name );

    $upload_dir = wp_upload_dir();
    $basedir    = function_exists( 'trailingslashit' ) ? trailingslashit( $upload_dir['basedir'] ) : ( rtrim( $upload_dir['basedir'], '/\\' ) . '/' );
    $baseurl    = function_exists( 'trailingslashit' ) ? trailingslashit( $upload_dir['baseurl'] ) : ( rtrim( $upload_dir['baseurl'], '/\\' ) . '/' );
    $output_dir = $basedir . 'BatchMate-cards/';

    if ( ! file_exists( $output_dir ) ) {
        if ( function_exists( 'wp_mkdir_p' ) ) {
            wp_mkdir_p( $output_dir );
        } else {
            @mkdir( $output_dir, 0755, true );
        }
    }

    $base_slug    = sanitize_file_name( str_replace( '.php', '', $template_name ) );
    $preview_file = $output_dir . 'preview_' . $base_slug . '.jpg';
    $preview_url  = $baseurl . 'BatchMate-cards/preview_' . $base_slug . '.jpg';

    if ( ! $force && file_exists( $preview_file ) && filesize( $preview_file ) > 1000 ) {
        return $preview_url;
    }

    if ( ! function_exists( 'batchmate_get_test_member_data' ) ) {
        $test_file = ROYAL_BATCHMATE_PLUGIN_DIR . '/includes/testbirthday.php';
        if ( file_exists( $test_file ) ) {
            require_once $test_file;
        }
    }

    $first_member = function_exists( 'batchmate_get_test_member_data' )
        ? batchmate_get_test_member_data()
        : (object) array(
            'id'           => 1,
            'name'         => 'Valued Batchmate',
            'email'        => get_bloginfo( 'admin_email' ),
            'batch'        => '1999',
            'dept_name'    => 'Science',
            'photo_url'    => ROYAL_BATCHMATE_PLUGIN_URL . 'assets/images/noprofile.png',
            'program_name' => 'Batch 1999',
        );

    $generated = BatchMate_generate_custom_card( $first_member, $template_name, $settings_override, $preview_file );
    if ( $generated && file_exists( $preview_file ) ) {
        return $preview_url;
    }

    return $preview_url;
}

/**
 * 4. Email Sender
 */
function BatchMate_send_email_with_card( $member, $attachment_path ) {
    $to = $member->email;
    if ( empty( $to ) || ! is_email( $to ) ) return;

    $settings  = get_option('batchmate_settings', array());
    $org_name  = batchmate_org_label( 'full' );
    $org_email = isset($settings['org_email']) ? $settings['org_email'] : get_bloginfo('admin_email');

    $upload_dir = wp_upload_dir();
    $image_url  = str_replace( $upload_dir['basedir'], $upload_dir['baseurl'], $attachment_path );
    $card_img   = "<img src='" . esc_url($image_url) . "' alt='Birthday Card' style='max-width: 100%; height: auto; border-radius: 8px; border: 1px solid #ddd; box-shadow: 0 4px 10px rgba(0,0,0,0.1);'>";

    // Custom Subject
    $raw_subject = !empty($settings['birthday_email_subject']) ? $settings['birthday_email_subject'] : 'Happy Birthday, {name}!';
    $subject = batchmate_replace_template_tags( $raw_subject, array(
        'name'       => $member->name,
        'org_name'   => $org_name,
        'program'    => $member->program_name ?? '',
        'site_title' => get_bloginfo('name'),
    ) );

    // Custom Body
    $raw_body = !empty($settings['birthday_email_body']) ? $settings['birthday_email_body'] : batchmate_get_default_template('birthday_email_body');
    $body = batchmate_replace_template_tags( $raw_body, array(
        'name'       => esc_html($member->name),
        'org_name'   => esc_html($org_name),
        'card_url'   => esc_url($image_url),
        'card_image' => $card_img,
        'program'    => esc_html($member->program_name ?? ''),
        'site_title' => esc_html(get_bloginfo('name')),
    ) );

    $headers = array(
        'Content-Type: text/html; charset=UTF-8',
        'From: ' . $org_name . ' <' . $org_email . '>'
    );

    $attachments = array( $attachment_path );
    wp_mail( $to, $subject, $body, $headers, $attachments );
}

/**
 * 5. Cleanup Old Cards
 */
function BatchMate_delete_old_cards() {
    $upload_dir = wp_upload_dir();
    $card_dir = $upload_dir['basedir'] . '/BatchMate-cards/';

    if ( is_dir( $card_dir ) ) {
        $files = glob( $card_dir . '*.jpg' );
        $now = time();
        $expire_time = 24 * 60 * 60; // 1 Day

        foreach ( $files as $file ) {
            if ( is_file( $file ) ) {
                if ( $now - filemtime( $file ) >= $expire_time ) {
                    unlink( $file );
                }
            }
        }
    }
}