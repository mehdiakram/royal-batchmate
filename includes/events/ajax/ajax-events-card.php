<?php
if ( ! defined( 'ABSPATH' ) ) exit;

// 16. HELPER: SEND CONFIRMATION EMAIL
// ─────────────────────────────────────────────────────────────────────
/**
 * Post Event Card to Make.com Webhook for Facebook posting
 * Matches the structure of BatchMate_post_to_make_webhook in cron-jobs.php
 */
function BatchMate_post_event_card_to_make_webhook( $app_id, $card_path = '', $serial_no = 0 ) {
    $settings    = get_option('batchmate_settings', array());
    $has_wh      = function_exists('batchmate_has_active_webhooks_or_buffer')
        ? batchmate_has_active_webhooks_or_buffer($settings)
        : (! empty($settings['webhook_urls']) || ! empty($settings['make_webhook_url']));
    $webhook_url = isset($settings['make_webhook_url']) ? trim($settings['make_webhook_url']) : '';

    if ( ! $has_wh ) {
        error_log('BatchMate Event Make.com Error: No Webhook URL configured.');
        return false;
    }

    global $wpdb;
    $app_table = $wpdb->prefix . 'batchmate_event_application';
    $app = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $app_table WHERE id = %d", $app_id ) );
    if ( ! $app ) return false;

    $event = $wpdb->get_row( $wpdb->prepare(
        "SELECT event_name, event_date, card_template_url, card_zones_json FROM {$wpdb->prefix}batchmate_event WHERE id = %d",
        $app->event_id
    ) );
    if ( ! $event ) return false;

    $member_name  = '';
    $program_name = '';
    $batch        = '';
    $member_row   = null;
    $type         = 'Member';

    if ( $app->member_id > 0 ) {
        $m = $wpdb->get_row( $wpdb->prepare(
            "SELECT m.*, prog.program_name, prog.short_code AS program_short_code, d.dept_name,
                    COALESCE(NULLIF(m.present_district, ''), NULLIF(m.permanent_district, ''), '') AS district
             FROM {$wpdb->prefix}batchmate_member m
             LEFT JOIN {$wpdb->prefix}batchmate_program prog ON m.program_id = prog.id
             LEFT JOIN {$wpdb->prefix}batchmate_department d ON prog.dept_id  = d.id
             WHERE m.id = %d",
            $app->member_id
        ), ARRAY_A );
        if ( $m ) {
            $member_row   = $m;
            $member_name  = $m['name'] ?? '';
            $batch        = $m['batch'] ?? '';
            $program_name = $m['program_name'] ?? '';
        }
    } elseif ( $app->rawdata_id > 0 ) {
        $r = $wpdb->get_row( $wpdb->prepare(
            "SELECT r.*, prog.program_name, prog.short_code AS program_short_code, d.dept_name,
                    COALESCE(NULLIF(r.present_district, ''), NULLIF(r.permanent_district, ''), '') AS district
             FROM {$wpdb->prefix}batchmate_rawdata r
             LEFT JOIN {$wpdb->prefix}batchmate_program prog ON r.program_id = prog.id
             LEFT JOIN {$wpdb->prefix}batchmate_department d ON prog.dept_id  = d.id
             WHERE r.id = %d",
            $app->rawdata_id
        ), ARRAY_A );
        $type = 'Non-Member';
        if ( $r ) {
            $member_row   = $r;
            $member_name  = $r['name'] ?? '';
            $batch        = $r['batch'] ?? '';
            $program_name = $r['program_name'] ?? '';
        }
    }

    if ( empty( $serial_no ) ) {
        $serial_no = $wpdb->get_var( $wpdb->prepare("SELECT serial_no FROM {$wpdb->prefix}batchmate_event_members WHERE application_id = %d", $app_id) );
    }
    if ( $member_row && ! empty( $serial_no ) ) {
        $member_row['serial_number'] = sprintf( '%04d', intval( $serial_no ) );
    }

    // Try generating card if card_path is empty or missing
    if ( ( empty($card_path) || ! file_exists($card_path) ) && function_exists('BatchMate_generate_event_card') && ! empty($member_row) && ! empty($event) ) {
        $gen_path = BatchMate_generate_event_card( $member_row, (array) $event, $type );
        if ( $gen_path && file_exists( $gen_path ) ) {
            $card_path = $gen_path;
        }
    }

    $card_url = '';
    if ( ! empty($card_path) && file_exists($card_path) ) {
        $upload_dir = wp_upload_dir();
        $card_url   = str_replace( $upload_dir['basedir'], $upload_dir['baseurl'], $card_path );
    }

    $org = batchmate_org_label( 'short' );
    $event_name = $event->event_name;

    // Full Facebook Message
    $full_message  = "🎉 Registration Confirmed for " . $member_name . "! 🎟️\n\n";
    $full_message .= "Event: " . $event_name . "\n";
    if ( ! empty( $serial_no ) ) {
        $full_message .= "Registration Serial: #" . intval( $serial_no ) . "\n";
    }
    if ( ! empty( $program_name ) ) {
        $full_message .= "Program: " . $program_name . "\n";
    }
    if ( ! empty( $batch ) ) {
        $full_message .= "Batch: " . $batch . "\n";
    }
    $full_message .= "\nWe are thrilled to welcome you to the event! See you there!\n\n";
    $full_message .= "#" . preg_replace('/\s+/', '', $org) . " #EventRegistration #" . preg_replace('/[^\p{L}\p{N}]+/u', '', $event_name);

    $payload = array(
        'type'        => 'event_registration',
        'member_name' => $member_name,
        'event_name'  => $event_name,
        'serial_no'   => $serial_no,
        'program'     => $program_name,
        'batch'       => $batch,
        'card_url'    => $card_url,
        'message'     => $full_message,
    );

    // Dispatch to Multi-Webhooks
    if ( function_exists('batchmate_dispatch_webhooks') ) {
        $res = batchmate_dispatch_webhooks( $payload, 'event_registration' );
        return ( ! empty($res['dispatched']) );
    }

    $args = array(
        'body'        => json_encode( $payload ),
        'headers'     => array( 'Content-Type' => 'application/json' ),
        'timeout'     => 15,
        'blocking'    => true,
    );

    $response = wp_remote_post( $webhook_url, $args );

    if ( is_wp_error( $response ) ) {
        error_log( 'BatchMate Event Make.com Error: ' . $response->get_error_message() );
        return false;
    } else {
        $code = wp_remote_retrieve_response_code( $response );
        if ( $code >= 200 && $code < 300 ) {
            error_log( 'BatchMate Event Make.com Success: Data sent for ' . $member_name );
            return true;
        } else {
            error_log( 'BatchMate Event Make.com Failed: HTTP ' . $code );
            return false;
        }
    }
}

// â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
// 16. SEND EVENT CONFIRMATION EMAIL (WITH ATTACHED CARD & LOGIN CREDENTIALS)
// â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
function BatchMate_send_event_confirmation_email( $app_id, $serial_no, $new_user_login_info = null, $trigger_webhook = true ) {
    global $wpdb;
    
    $app_table = $wpdb->prefix . 'batchmate_event_application';
    $app = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $app_table WHERE id = %d", $app_id ) );
    if ( ! $app ) return false;

    $email_address = '';
    $member_name   = '';
    $member_row    = null;
    $type          = 'Member';

    if ( $app->member_id > 0 ) {
        $member_row = $wpdb->get_row( $wpdb->prepare(
            "SELECT m.*, prog.program_name, prog.short_code AS program_short_code, d.dept_name,
                    COALESCE(NULLIF(m.present_district, ''), NULLIF(m.permanent_district, ''), '') AS district
             FROM {$wpdb->prefix}batchmate_member m
             LEFT JOIN {$wpdb->prefix}batchmate_program prog ON m.program_id = prog.id
             LEFT JOIN {$wpdb->prefix}batchmate_department d ON prog.dept_id  = d.id
             WHERE m.id = %d",
            $app->member_id
        ), ARRAY_A );
        if ( $member_row ) {
            $email_address = $member_row['email'];
            $member_name   = $member_row['name'];
        }
    } else if ( $app->rawdata_id > 0 ) {
        $member_row = $wpdb->get_row( $wpdb->prepare(
            "SELECT r.*, prog.program_name, prog.short_code AS program_short_code, d.dept_name,
                    COALESCE(NULLIF(r.present_district, ''), NULLIF(r.permanent_district, ''), '') AS district
             FROM {$wpdb->prefix}batchmate_rawdata r
             LEFT JOIN {$wpdb->prefix}batchmate_program prog ON r.program_id = prog.id
             LEFT JOIN {$wpdb->prefix}batchmate_department d ON prog.dept_id  = d.id
             WHERE r.id = %d",
            $app->rawdata_id
        ), ARRAY_A );
        $type = 'Non-Member';
        if ( $member_row ) {
            $email_address = $member_row['email'];
            $member_name   = $member_row['name'];
        }
    }

    if ( $member_row && ! empty( $serial_no ) ) {
        $member_row['serial_number'] = sprintf( '%04d', intval( $serial_no ) );
    }

    $event_data = $wpdb->get_row( $wpdb->prepare(
        "SELECT event_name, event_date, location, registered_url, cc_email, card_template_url, card_zones_json FROM {$wpdb->prefix}batchmate_event WHERE id = %d",
        $app->event_id
    ) );

    $cc_email_evt = $event_data->cc_email ?? '';
    $recipient_email = ! empty( $email_address ) ? $email_address : ( ! empty( $cc_email_evt ) ? $cc_email_evt : '' );

    if ( ! empty( $recipient_email ) && $event_data ) {
        $event_name     = $event_data->event_name;
        $event_date_fmt = date_i18n( 'l, j F Y', strtotime( $event_data->event_date ) );
        $event_location = $event_data->location ?? '';
        $list_url       = $event_data->registered_url ?? '';
        $serial         = intval( $serial_no );
        $contact_url    = home_url( '/contact-us/' );

        $en  = esc_html( $event_name );
        $mn  = esc_html( $member_name );
        $ed  = esc_html( $event_date_fmt );
        $el  = esc_html( $event_location );
        $lu  = esc_url( $list_url );
        $cu  = esc_url( $contact_url );

        $is_guest_reg = ( $app->member_id == 0 && $app->rawdata_id > 0 );
        $guest_details = null;
        if ( $is_guest_reg && $app->rawdata_id > 0 ) {
            $guest_details = $wpdb->get_row( $wpdb->prepare(
                "SELECT r.name, r.email, r.cell_number, r.student_id, r.blood_group, r.photo_url, r.batch,
                        prog.program_name, d.dept_name
                 FROM {$wpdb->prefix}batchmate_rawdata r
                 LEFT JOIN {$wpdb->prefix}batchmate_program prog ON r.program_id = prog.id
                 LEFT JOIN {$wpdb->prefix}batchmate_department d ON prog.dept_id = d.id
                 WHERE r.id = %d",
                $app->rawdata_id
            ) );
        }

        $total_guests = 0;
        if ( ! empty( $app->options_json ) ) {
            $opts = json_decode( stripslashes( $app->options_json ), true );
            if ( is_string( $opts ) ) $opts = json_decode( $opts, true );
            if ( is_array( $opts ) ) {
                foreach ( $opts as $o ) {
                    $total_guests += intval( $o['qty'] ?? 0 );
                }
            }
        }

        // â”€â”€ Generate Event ID Card & Attach â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
        $attachments = array();
        $card_url    = '';
        $card_path   = '';

        if ( ! empty( $event_data->card_template_url ) && ! empty( $event_data->card_zones_json ) && function_exists( 'BatchMate_generate_event_card' ) && ! empty( $member_row ) ) {
            $gen_path = BatchMate_generate_event_card( $member_row, (array) $event_data, $type );
            if ( $gen_path && file_exists( $gen_path ) ) {
                $card_path = $gen_path;
                $upload_dir = wp_upload_dir();
                $card_url = str_replace( $upload_dir['basedir'], $upload_dir['baseurl'], $card_path );
                $attachments[] = $card_path;

                // Post to Make.com Facebook Webhook
                if ( $trigger_webhook && function_exists( 'BatchMate_post_event_card_to_make_webhook' ) ) {
                    BatchMate_post_event_card_to_make_webhook( $app_id, $card_path, $serial_no );
                }
            }
        }

        $subject = '[BatchMate] Registration Confirmed â€” ' . $event_name . ' (Serial #' . $serial . ')';

        $body  = '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><title>Registration Confirmed</title></head>';
        $body .= '<body style="margin:0;padding:0;background:#eef2f7;font-family:Arial,Helvetica,sans-serif;">';
        $body .= '<table width="100%" cellpadding="0" cellspacing="0" border="0" bgcolor="#eef2f7" style="padding:36px 0;"><tr><td align="center">';
        $body .= '<table width="600" cellpadding="0" cellspacing="0" border="0" style="max-width:600px;width:100%;">';

        $body .= '<tr><td bgcolor="#1d4ed8" style="height:5px;border-radius:12px 12px 0 0;font-size:0;line-height:0;">&nbsp;</td></tr>';

        $body .= '<tr><td align="center" style="background:linear-gradient(160deg,#0f2756 0%,#1a3a8f 55%,#2563eb 100%);padding:40px 48px 32px;">';
        $body .= '<table cellpadding="0" cellspacing="0" border="0" align="center"><tr><td align="center" style="background:rgba(255,255,255,.18);border:2px solid rgba(255,255,255,.35);border-radius:50%;width:72px;height:72px;font-size:32px;line-height:72px;color:#fff;font-weight:900;text-align:center;">&#10003;</td></tr></table>';
        $body .= '<p style="margin:16px 0 4px;font-size:26px;font-weight:800;color:#fff;text-align:center;letter-spacing:-.5px;">Registration Confirmed!</p>';
        
        if ( $total_guests > 0 ) {
            $body .= '<p style="margin:0 0 4px;font-size:16px;font-weight:700;color:#93c5fd;text-align:center;">With ' . $total_guests . ' Guest' . ($total_guests > 1 ? 's' : '') . '</p>';
        }

        $body .= '<p style="margin:0;font-size:14px;color:#ffffff;text-align:center;">' . $en . '</p>';
        $body .= '</td></tr>';

        $body .= '<tr><td bgcolor="#ffffff" style="background:#fff;padding:32px 48px 0;">';
        $body .= '<p style="margin:0 0 10px;font-size:16px;color:#1e293b;line-height:1.5;">Dear <strong>' . $mn . '</strong>,</p>';
        $body .= '<p style="margin:0 0 24px;font-size:14px;color:#475569;line-height:1.85;">Congratulations! Your registration for <strong>' . $en . '</strong> has been successfully completed and approved. All your confirmation details are provided below.</p>';
        $body .= '</td></tr>';

        // â”€â”€ Direct Membership Welcome & Credentials Block (if converted via Option 3) â”€â”€
        if ( ! empty( $new_user_login_info ) ) {
            $login_url = wp_login_url();
            $body .= '<tr><td bgcolor="#ffffff" style="background:#fff;padding:0 48px 24px;">';
            $body .= '<div style="background:linear-gradient(135deg,#f0fdf4,#dcfce7);border:2px solid #86efac;border-radius:14px;padding:22px 24px;">';
            $body .= '<p style="margin:0 0 6px;font-size:11px;font-weight:800;letter-spacing:2px;color:#15803d;text-transform:uppercase;">&#127881; Official Membership Approved</p>';
            $body .= '<p style="margin:0 0 14px;font-size:14px;color:#14532d;line-height:1.6;">Congratulations! Along with your event registration, you have been directly approved as an official club member. Your membership details and website access credentials are below:</p>';
            $table_tag = '<table cellpadding="0" cellspacing="0" border="0" style="font-size:13px;color:#166534;line-height:2.1;">';
            $body .= $table_tag;
            $body .= '<tr><td style="font-weight:700;width:130px;">Member ID:</td><td><strong style="font-size:15px;color:#14532d;">' . esc_html( $new_user_login_info['member_number'] ) . '</strong></td></tr>';
            $body .= '<tr><td style="font-weight:700;">Login Website:</td><td><a href="' . esc_url( $login_url ) . '" target="_blank" style="color:#15803d;font-weight:700;text-decoration:underline;">' . esc_url( $login_url ) . '</a></td></tr>';
            $body .= '<tr><td style="font-weight:700;">Username:</td><td><code style="background:#fff;padding:2px 8px;border-radius:4px;border:1px solid #bbf7d0;">' . esc_html( $new_user_login_info['username'] ) . '</code></td></tr>';
            $body .= '<tr><td style="font-weight:700;">Password:</td><td><code style="background:#fff;padding:2px 8px;border-radius:4px;border:1px solid #bbf7d0;">' . esc_html( $new_user_login_info['password'] ) . '</code></td></tr>';
            $body .= '</table>';
            $body .= '</div>';
            $body .= '</td></tr>';
        }

        $body .= '<tr><td bgcolor="#ffffff" style="background:#fff;padding:0 48px 24px;">';
        $body .= '<table width="100%" cellpadding="0" cellspacing="0" border="0"><tr>';
        $body .= '<td align="center" bgcolor="#eff6ff" style="background:#eff6ff;border:2px solid #93c5fd;border-radius:14px;padding:24px 20px;text-align:center;">';
        $body .= '<p style="margin:0 0 4px;font-size:10px;font-weight:700;letter-spacing:3px;color:#3b82f6;text-transform:uppercase;">Your Registration Serial Number</p>';
        $body .= '<p style="margin:0;font-size:80px;font-weight:900;color:#1d4ed8;line-height:1;letter-spacing:-3px;">' . $serial . '</p>';
        $body .= '<p style="margin:10px 0 0;font-size:12px;color:#64748b;">Please show this number at the entrance on the event day</p>';
        $body .= '</td></tr></table>';
        $body .= '</td></tr>';

        // â”€â”€ Card Image Preview Section â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
        if ( ! empty( $card_url ) ) {
            $body .= '<tr><td bgcolor="#ffffff" style="background:#fff;padding:0 48px 24px;text-align:center;">';
            $body .= '<div style="background:#f8fafc;border:2px dashed #93c5fd;border-radius:14px;padding:24px 20px;text-align:center;">';
            $body .= '<p style="margin:0 0 12px;font-size:11px;font-weight:700;letter-spacing:2px;color:#2563eb;text-transform:uppercase;">&#127380; Your Official Event ID Card</p>';
            $body .= '<a href="' . esc_url( $card_url ) . '" target="_blank"><img src="' . esc_url( $card_url ) . '" alt="Event ID Card" style="max-width:100%;width:380px;height:auto;border-radius:10px;box-shadow:0 8px 24px rgba(0,0,0,0.12);display:inline-block;border:1px solid #e2e8f0;"></a>';
            $body .= '<p style="margin:12px 0 0;font-size:12px;color:#64748b;">The high-resolution ID card has also been attached to this email. Feel free to download and share it!</p>';
            $body .= '</div>';
            $body .= '</td></tr>';
        }

        $body .= '<tr><td bgcolor="#ffffff" style="background:#fff;padding:0 48px 24px;">';
        $body .= '<table width="100%" cellpadding="0" cellspacing="0" border="0" style="border:1px solid #e2e8f0;border-radius:12px;overflow:hidden;">';
        $body .= '<tr bgcolor="#f8fafc"><td colspan="2" style="background:#f8fafc;padding:11px 20px;border-bottom:1px solid #e2e8f0;"><span style="font-size:10px;font-weight:700;letter-spacing:2px;color:#64748b;text-transform:uppercase;">Confirmation Details</span></td></tr>';
        $body .= '<tr><td style="padding:12px 20px;border-bottom:1px solid #f1f5f9;font-size:12px;color:#94a3b8;font-weight:700;width:140px;white-space:nowrap;">&#127967; Event</td><td style="padding:12px 20px;border-bottom:1px solid #f1f5f9;font-size:13px;font-weight:700;color:#1e293b;">' . $en . '</td></tr>';
        $body .= '<tr><td style="padding:12px 20px;border-bottom:1px solid #f1f5f9;font-size:12px;color:#94a3b8;font-weight:700;white-space:nowrap;">&#128197; Date</td><td style="padding:12px 20px;border-bottom:1px solid #f1f5f9;font-size:13px;font-weight:700;color:#1e293b;">' . $ed . '</td></tr>';
        if ( ! empty( $el ) ) {
            $body .= '<tr><td style="padding:12px 20px;border-bottom:1px solid #f1f5f9;font-size:12px;color:#94a3b8;font-weight:700;white-space:nowrap;">&#128205; Location</td><td style="padding:12px 20px;border-bottom:1px solid #f1f5f9;font-size:13px;color:#334155;">' . $el . '</td></tr>';
        }
        $body .= '<tr><td style="padding:12px 20px;border-bottom:1px solid #f1f5f9;font-size:12px;color:#94a3b8;font-weight:700;white-space:nowrap;">&#128100; Name</td><td style="padding:12px 20px;border-bottom:1px solid #f1f5f9;font-size:13px;color:#334155;">' . $mn . '</td></tr>';
        $body .= '<tr><td style="padding:12px 20px;font-size:12px;color:#94a3b8;font-weight:700;white-space:nowrap;"># Serial</td><td style="padding:12px 20px;font-size:20px;font-weight:900;color:#1d4ed8;">' . $serial . '</td></tr>';
        $body .= '</table>';
        $body .= '</td></tr>';

        if ( $is_guest_reg && $guest_details ) {
            $g = $guest_details;
            $body .= '<tr><td bgcolor="#ffffff" style="background:#fff;padding:0 48px 24px;">';
            $body .= '<table width="100%" cellpadding="0" cellspacing="0" border="0" style="border:1px solid #e2e8f0;border-radius:12px;overflow:hidden;">';
            $body .= '<tr bgcolor="#f0fdf4"><td colspan="2" style="background:#f0fdf4;padding:11px 20px;border-bottom:1px solid #e2e8f0;"><span style="font-size:10px;font-weight:700;letter-spacing:2px;color:#15803d;text-transform:uppercase;">&#128100; Registrant Details</span></td></tr>';
            if ( ! empty( $g->photo_url ) ) {
                $g_photo = esc_url( $g->photo_url );
                $body .= '<tr><td style="padding:14px 20px;border-bottom:1px solid #f1f5f9;font-size:12px;color:#94a3b8;font-weight:700;white-space:nowrap;">Photo</td>';
                $body .= '<td style="padding:10px 20px;border-bottom:1px solid #f1f5f9;"><img src="' . $g_photo . '" width="80" height="80" style="width:80px;height:80px;border-radius:50%;object-fit:cover;border:3px solid #86efac;display:block;"></td></tr>';
            }
            if ( ! empty( $g->student_id ) )   $body .= '<tr><td style="padding:12px 20px;border-bottom:1px solid #f1f5f9;font-size:12px;color:#94a3b8;font-weight:700;white-space:nowrap;">Student ID</td><td style="padding:12px 20px;border-bottom:1px solid #f1f5f9;font-size:13px;color:#334155;">' . esc_html($g->student_id) . '</td></tr>';
            if ( ! empty( $g->program_name ) )  $body .= '<tr><td style="padding:12px 20px;border-bottom:1px solid #f1f5f9;font-size:12px;color:#94a3b8;font-weight:700;white-space:nowrap;">Program</td><td style="padding:12px 20px;border-bottom:1px solid #f1f5f9;font-size:13px;color:#334155;">' . esc_html($g->program_name) . '</td></tr>';
            if ( ! empty( $g->batch ) )          $body .= '<tr><td style="padding:12px 20px;border-bottom:1px solid #f1f5f9;font-size:12px;color:#94a3b8;font-weight:700;white-space:nowrap;">Batch</td><td style="padding:12px 20px;border-bottom:1px solid #f1f5f9;font-size:13px;color:#334155;">' . esc_html($g->batch) . '</td></tr>';
            if ( ! empty( $g->cell_number ) )    $body .= '<tr><td style="padding:12px 20px;border-bottom:1px solid #f1f5f9;font-size:12px;color:#94a3b8;font-weight:700;white-space:nowrap;">Mobile</td><td style="padding:12px 20px;border-bottom:1px solid #f1f5f9;font-size:13px;color:#334155;">' . esc_html($g->cell_number) . '</td></tr>';
            if ( ! empty( $g->blood_group ) )    $body .= '<tr><td style="padding:12px 20px;font-size:12px;color:#94a3b8;font-weight:700;white-space:nowrap;">Blood Group</td><td style="padding:12px 20px;font-size:13px;color:#334155;">' . esc_html($g->blood_group) . '</td></tr>';
            $body .= '</table>';
            $body .= '</td></tr>';
        }

        $body .= '<tr><td bgcolor="#ffffff" style="background:#fff;padding:4px 48px 32px;text-align:center;">';
        if ( ! empty( $lu ) ) {
            $body .= '<table cellpadding="0" cellspacing="0" border="0" align="center"><tr>';
            $body .= '<td align="center" style="background:linear-gradient(135deg,#0f2756,#1d4ed8);border-radius:10px;">';
            $body .= '<a href="' . $lu . '" target="_blank" style="display:inline-block;padding:14px 40px;font-size:14px;font-weight:700;color:#ffffff;text-decoration:none;letter-spacing:.5px;border-radius:10px;font-family:Arial,sans-serif;">View Registration List &rarr;</a>';
            $body .= '</td></tr></table>';
        }
        $body .= '<p style="margin:' . ( ! empty( $lu ) ? '14px' : '0' ) . ' 0 0;font-size:12px;color:#94a3b8;">If you have any issues, please <a href="' . $cu . '" style="color:#2563eb;text-decoration:none;font-weight:600;">contact us</a>.</p>';
        $body .= '</td></tr>';

        $body .= '<tr><td align="center" bgcolor="#f8fafc" style="background:#f8fafc;padding:20px 48px;border:1px solid #e2e8f0;border-top:none;">';
        $body .= '<p style="margin:0 0 4px;font-size:13px;font-weight:700;color:#475569;">' . batchmate_org_label( 'short' ) . '</p>';
        $ev_settings = get_option('batchmate_settings', []);
        $ev_org_email = ! empty( $ev_settings['org_email'] ) ? $ev_settings['org_email'] : '';
        $body .= '<p style="margin:0;font-size:11px;color:#94a3b8;">' . ( $ev_org_email ? esc_html($ev_org_email) . ' &nbsp;&bull;&nbsp; ' : '' ) . 'This email was sent automatically</p>';
        $body .= '</td></tr>';

        $body .= '<tr><td bgcolor="#1d4ed8" style="height:4px;border-radius:0 0 12px 12px;font-size:0;line-height:0;">&nbsp;</td></tr>';

        $body .= '</table></td></tr></table></body></html>';

        $headers = array( 'Content-Type: text/html; charset=UTF-8' );
        if ( ! empty( $email_address ) && ! empty( $cc_email_evt ) && function_exists( 'batchmate_get_cc_headers' ) ) {
            $headers = array_merge( $headers, batchmate_get_cc_headers( $cc_email_evt ) );
        }
        $mail_sent = wp_mail( $recipient_email, $subject, $body, $headers, $attachments );

        // Event SMS Notification
        $ev_all_settings = get_option('batchmate_settings', array());
        $event_sms_active = isset($ev_all_settings['event_sms_active']) && $ev_all_settings['event_sms_active'] === 'on';
        $phone_number = !empty($member_row['cell_number']) ? $member_row['cell_number'] : '';
        if ( $event_sms_active && !empty($phone_number) && function_exists('batchmate_send_sms') ) {
            $sms_tmpl = !empty($ev_all_settings['event_sms_template']) ? $ev_all_settings['event_sms_template'] : batchmate_get_default_template('event_sms');
            $sms_msg = batchmate_replace_template_tags( $sms_tmpl, array(
                'name'       => $member_name,
                'event_name' => $event_name,
                'serial_no'  => $serial,
                'org_name'   => batchmate_org_label( 'short' ),
            ) );
            batchmate_send_sms( $phone_number, $sms_msg );
        }

        return (bool) $mail_sent;
    }
    return false;
}

function BatchMate_resend_event_email_ajax() {
    batchmate_event_check_admin();
    global $wpdb;

    $app_id = intval( $_POST['app_id'] ?? 0 );
    if ( ! $app_id ) wp_send_json_error( 'Invalid application ID.' );

    $member_table = $wpdb->prefix . 'batchmate_event_members';
    $serial = $wpdb->get_var( $wpdb->prepare("SELECT serial_no FROM $member_table WHERE application_id = %d", $app_id) );

    if ( ! $serial ) wp_send_json_error( 'Member not registered or serial number missing.' );

    $sent = BatchMate_send_event_confirmation_email( $app_id, $serial, null, false );
    
    if ( $sent ) {
        wp_send_json_success( 'Confirmation email sent successfully.' );
    } else {
        wp_send_json_error( 'Failed to send email. Data missing.' );
    }
}
add_action( 'wp_ajax_BatchMate_resend_event_email', 'BatchMate_resend_event_email_ajax' );
add_action( 'wp_ajax_puaa_resend_event_email',       'BatchMate_resend_event_email_ajax' );

function BatchMate_resend_event_webhook_ajax() {
    batchmate_event_check_admin();
    global $wpdb;

    $app_id = intval( $_POST['app_id'] ?? 0 );
    if ( ! $app_id ) wp_send_json_error( 'Invalid application ID.' );

    $member_table = $wpdb->prefix . 'batchmate_event_members';
    $serial = $wpdb->get_var( $wpdb->prepare("SELECT serial_no FROM $member_table WHERE application_id = %d", $app_id) );

    if ( ! $serial ) wp_send_json_error( 'Member not registered or serial number missing.' );

    $settings = get_option('batchmate_settings', array());
    $has_wh   = function_exists('batchmate_has_active_webhooks_or_buffer')
        ? batchmate_has_active_webhooks_or_buffer($settings)
        : (! empty($settings['webhook_urls']) || ! empty($settings['make_webhook_url']));
    if ( ! $has_wh ) {
        wp_send_json_error( 'No Webhook URL configured in Settings.' );
    }

    if ( function_exists( 'BatchMate_post_event_card_to_make_webhook' ) ) {
        $posted = BatchMate_post_event_card_to_make_webhook( $app_id, '', $serial );
        if ( $posted ) {
            wp_send_json_success( 'Webhook dispatched successfully.' );
        } else {
            wp_send_json_error( 'Failed to dispatch webhook. Check webhook configuration.' );
        }
    } else {
        wp_send_json_error( 'Webhook handler function not found.' );
    }
}
add_action( 'wp_ajax_BatchMate_resend_event_webhook', 'BatchMate_resend_event_webhook_ajax' );
add_action( 'wp_ajax_puaa_resend_event_webhook',       'BatchMate_resend_event_webhook_ajax' );


// ─────────────────────────────────────────────────────────────────────

// 19. UPLOAD CARD TEMPLATE IMAGE
// ─────────────────────────────────────────────────────────────────────
function BatchMate_upload_card_template_ajax() {
    batchmate_event_check_admin();

    if ( empty( $_FILES['card_template'] ) || $_FILES['card_template']['error'] !== UPLOAD_ERR_OK ) {
        wp_send_json_error( 'No file uploaded or upload error.' );
    }

    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/media.php';
    require_once ABSPATH . 'wp-admin/includes/image.php';

    // Use WordPress media uploader
    $_FILES['async-upload'] = $_FILES['card_template'];
    $attachment_id = media_handle_upload( 'async-upload', 0 );

    if ( is_wp_error( $attachment_id ) ) {
        wp_send_json_error( $attachment_id->get_error_message() );
    }

    $url = wp_get_attachment_url( $attachment_id );
    wp_send_json_success( array(
        'url'           => $url,
        'attachment_id' => $attachment_id,
    ) );
}
add_action( 'wp_ajax_BatchMate_upload_card_template', 'BatchMate_upload_card_template_ajax' );
add_action( 'wp_ajax_puaa_upload_card_template',       'BatchMate_upload_card_template_ajax' );


// ─────────────────────────────────────────────────────────────────────
// 19b. SAVE EVENT CARD SETUP (template URL + zone JSON only)
// ─────────────────────────────────────────────────────────────────────
function BatchMate_save_event_card_setup_ajax() {
    batchmate_event_check_admin();
    global $wpdb;

    $event_id          = intval( $_POST['event_id'] ?? 0 );
    $card_template_url = esc_url_raw( trim( $_POST['card_template_url'] ?? '' ) );
    $card_zones_json_raw = $_POST['card_zones_json'] ?? '';

    if ( ! $event_id ) {
        wp_send_json_error( 'Event ID is required.' );
    }

    $card_zones_json = '';
    if ( ! empty( $card_zones_json_raw ) ) {
        $decoded = json_decode( stripslashes( $card_zones_json_raw ), true );
        $card_zones_json = $decoded ? wp_json_encode( $decoded ) : '';
    }

    $result = $wpdb->update(
        $wpdb->prefix . 'batchmate_event',
        array(
            'card_template_url' => $card_template_url,
            'card_zones_json'   => $card_zones_json ?: null,
        ),
        array( 'id' => $event_id ),
        array( '%s', '%s' ),
        array( '%d' )
    );

    if ( $result === false ) {
        wp_send_json_error( 'Database error.' );
    }

    wp_send_json_success( array(
        'message'  => 'Card setup saved.',
        'event_id' => $event_id,
    ) );
}
add_action( 'wp_ajax_BatchMate_save_event_card_setup', 'BatchMate_save_event_card_setup_ajax' );
add_action( 'wp_ajax_puaa_save_event_card_setup',       'BatchMate_save_event_card_setup_ajax' );


// ─────────────────────────────────────────────────────────────────────
// 20. GET MEMBER CARD DATA (for Canvas rendering)
// ─────────────────────────────────────────────────────────────────────
function BatchMate_get_member_card_data_ajax() {
    batchmate_event_check_admin();
    global $wpdb;

    $member_id = intval( $_POST['member_id'] ?? 0 );
    $event_id  = intval( $_POST['event_id']  ?? 0 );

    if ( ! $event_id ) wp_send_json_error( 'Event ID missing.' );

    // Get event card info
    $event = $wpdb->get_row( $wpdb->prepare(
        "SELECT event_name, card_template_url, card_zones_json FROM {$wpdb->prefix}batchmate_event WHERE id = %d",
        $event_id
    ) );
    if ( ! $event ) wp_send_json_error( 'Event not found.' );
    if ( empty( $event->card_template_url ) ) wp_send_json_error( 'No card template for this event.' );

    // Get member data
    $member = null;
    $type   = 'Member';

    if ( $member_id > 0 ) {
        $member = $wpdb->get_row( $wpdb->prepare(
            "SELECT m.name, m.photo_url, m.blood_group, m.member_number,
                    m.institution AS program_name, '' AS short_code,
                    COALESCE(NULLIF(m.present_district, ''), NULLIF(m.permanent_district, ''), '') AS district,
                    COALESCE(m.present_district, '') AS dept_name,
                    '' AS batch
             FROM {$wpdb->prefix}batchmate_member m
             WHERE m.id = %d",
            $member_id
        ) );
        $type = 'Member';
    }

    if ( ! $member ) {
        // Try rawdata (guest)
        $rawdata_id = intval( $_POST['rawdata_id'] ?? 0 );
        if ( $rawdata_id > 0 ) {
            $member = $wpdb->get_row( $wpdb->prepare(
                "SELECT r.name, r.photo_url, r.blood_group, '' AS member_number,
                        r.institution AS program_name, '' AS short_code,
                        COALESCE(NULLIF(r.present_district, ''), NULLIF(r.permanent_district, ''), '') AS district,
                        COALESCE(r.present_district, '') AS dept_name,
                        '' AS batch
                 FROM {$wpdb->prefix}batchmate_rawdata r
                 WHERE r.id = %d",
                $rawdata_id
            ) );
            $type = 'Non-Member';
        }
    }

    if ( ! $member ) wp_send_json_error( 'Member data not found.' );

    // Build serial number: check event_members for serial_no, else fallback
    $app_serial = '';
    $event_members_table = $wpdb->prefix . 'batchmate_event_members';
    $em_serial = null;
    $app_id = intval( $_POST['app_id'] ?? 0 );
    if ( $app_id > 0 ) {
        $em_serial = $wpdb->get_var( $wpdb->prepare( "SELECT serial_no FROM {$event_members_table} WHERE application_id = %d", $app_id ) );
    }
    if ( empty( $em_serial ) && $event_id > 0 ) {
        if ( $member_id > 0 ) {
            $em_serial = $wpdb->get_var( $wpdb->prepare( "SELECT serial_no FROM {$event_members_table} WHERE event_id = %d AND member_id = %d", $event_id, $member_id ) );
        } elseif ( $rawdata_id > 0 ) {
            $em_serial = $wpdb->get_var( $wpdb->prepare( "SELECT serial_no FROM {$event_members_table} WHERE event_id = %d AND rawdata_id = %d", $event_id, $rawdata_id ) );
        }
    }
    if ( ! empty( $em_serial ) ) {
        $app_serial = sprintf( '%04d', intval( $em_serial ) );
    } else {
        // Event serial should be distinct from member_number
        if ( $app_id > 0 ) {
            $app_serial = sprintf( '%04d', $app_id );
        } elseif ( $event_id > 0 ) {
            $prev_count = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(id) FROM {$event_members_table} WHERE event_id = %d", $event_id ) );
            $app_serial = sprintf( '%04d', $prev_count + 1 );
        } else {
            $app_serial = '0001';
        }
    }

    $member_data = array(
        'name'               => $member->name,
        'email'              => $member->email ?? '',
        'cell_number'        => $member->phone ?? ( $member->mobile ?? '' ),
        'blood_group'        => $member->blood_group ?: 'N/A',
        'district'           => $member->district ?: '',
        'batch'              => $member->batch ?: 'N/A',
        'program_name'       => $member->program_name ?: 'N/A',
        'program_short_code' => $member->short_code ?: $member->program_name ?: 'N/A',
        'photo_url'          => $member->photo_url,
        'serial_number'      => $app_serial,
        'member_number'      => $member->member_number ?? '',
        'id'                 => $member_id ?: ($rawdata_id ?: 1),
    );

    $settings     = get_option( 'batchmate_settings', array() );
    $org_logo_url = ! empty( $settings['org_logo_url'] ) ? $settings['org_logo_url'] : '';

    $event_data = array(
        'event_name'        => $event->event_name,
        'card_template_url' => $event->card_template_url,
        'card_zones_json'   => $event->card_zones_json,
        'logo_url'          => $org_logo_url,
        'show_org_logo'     => 0,
    );

    $server_card_url = '';
    require_once ROYAL_BATCHMATE_PLUGIN_DIR . 'includes/admin-events-card.php';
    if ( function_exists( 'BatchMate_generate_event_card' ) ) {
        ob_start();
        $generated_path = BatchMate_generate_event_card( $member_data, $event_data, $type );
        ob_get_clean();
        if ( $generated_path && file_exists( $generated_path ) ) {
            $upload_dir = wp_upload_dir();
            $server_card_url = str_replace( $upload_dir['basedir'], $upload_dir['baseurl'], $generated_path );
        }
    }

    wp_send_json_success( array(
        'card_url' => $server_card_url,
        'member' => array(
            'name'               => $member->name,
            'photo_url'          => $member->photo_url,
            'blood_group'        => $member->blood_group ?: 'N/A',
            'batch'              => $member->batch ?: 'N/A',
            'district'           => $member->district ?: '',
            'dept_name'          => $member->district ?: ( $member->dept_name ?: ( $member->program_name ?: 'N/A' ) ),
            'program_name'       => $member->program_name ?: 'N/A',
            'program_short_code' => $member->short_code ?: $member->program_name ?: 'N/A',
            'member_number'      => $member->member_number ?? '',
            'serial_number'      => $app_serial,
            'type'               => $type,
        ),
        'event'  => array(
            'event_name'         => $event->event_name,
            'card_template_url'  => $event->card_template_url,
            'card_zones_json'    => $event->card_zones_json,
        ),
    ) );
}
add_action( 'wp_ajax_BatchMate_get_member_card_data', 'BatchMate_get_member_card_data_ajax' );
add_action( 'wp_ajax_puaa_get_member_card_data',       'BatchMate_get_member_card_data_ajax' );


// ─────────────────────────────────────────────────────────────────────
// 21. SEND EVENT CARD EMAIL (Manual — base64 PNG from Canvas)
// ─────────────────────────────────────────────────────────────────────
function BatchMate_send_event_card_email_internal( $app_id, $event_id ) {
    global $wpdb;

    if ( ! $app_id || ! $event_id ) return array( 'success' => false, 'message' => 'Missing data.' );

    // Get Application Info
    $app_table = $wpdb->prefix . 'batchmate_event_application';
    $app = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $app_table WHERE id = %d", $app_id ) );
    if ( ! $app ) return array( 'success' => false, 'message' => 'Application not found.' );

    // Get Event Info
    $event_row = $wpdb->get_row( $wpdb->prepare(
        "SELECT event_name, cc_email, card_template_url, card_zones_json FROM {$wpdb->prefix}batchmate_event WHERE id = %d",
        $event_id
    ), ARRAY_A );
    if ( ! $event_row ) return array( 'success' => false, 'message' => 'Event not found.' );

    // Get Member Data
    $member_row = null;
    $type       = 'Member';
    $email      = '';

    if ( $app->member_id > 0 ) {
        $member_row = $wpdb->get_row( $wpdb->prepare(
            "SELECT m.name, m.email, m.photo_url, m.blood_group, m.member_number,
                    m.institution AS program_name, '' AS program_short_code,
                    COALESCE(NULLIF(m.present_district, ''), NULLIF(m.permanent_district, ''), '') AS district,
                    COALESCE(m.present_district, '') AS dept_name
             FROM {$wpdb->prefix}batchmate_member m
             WHERE m.id = %d",
            $app->member_id
        ), ARRAY_A );
        if ( $member_row ) {
            $email = $member_row['email'];
        }
    } elseif ( $app->rawdata_id > 0 ) {
        $member_row = $wpdb->get_row( $wpdb->prepare(
            "SELECT r.name, r.email, r.photo_url, r.blood_group, '' AS member_number,
                    r.institution AS program_name, '' AS program_short_code,
                    COALESCE(NULLIF(r.present_district, ''), NULLIF(r.permanent_district, ''), '') AS district,
                    COALESCE(r.present_district, '') AS dept_name
             FROM {$wpdb->prefix}batchmate_rawdata r
             WHERE r.id = %d",
            $app->rawdata_id
        ), ARRAY_A );
        $type = 'Non-Member';
        if ( $member_row ) {
            $email = $member_row['email'];
        }
    }

    if ( empty( $email ) ) return array( 'success' => false, 'message' => 'No email address found for this registration.' );
    if ( ! $member_row ) return array( 'success' => false, 'message' => 'Member data not found.' );

    // Fetch registration serial from event_members
    $em_serial = $wpdb->get_var( $wpdb->prepare(
        "SELECT serial_no FROM {$wpdb->prefix}batchmate_event_members WHERE application_id = %d",
        $app_id
    ) );
    if ( ! empty( $em_serial ) ) {
        $member_row['serial_number'] = sprintf( '%04d', intval( $em_serial ) );
    }

    // Generate the card on the server-side to avoid payload limits and ModSecurity issues
    $tmp_path = false;
    if ( function_exists('BatchMate_generate_event_card') ) {
        // Start output buffering to catch any warnings (e.g. exif_imagetype network timeout)
        ob_start();
        $tmp_path = BatchMate_generate_event_card( $member_row, $event_row, $type );
        $warnings = ob_get_clean();
    }

    if ( ! $tmp_path || ! file_exists( $tmp_path ) ) {
        return array( 'success' => false, 'message' => 'Failed to generate card image on the server. Please check your template image URL.' );
    }

    $event_name = $event_row['event_name'];
    $name       = $member_row['name'];

    $subject = '[BatchMate] Your Registration Card — ' . $event_name;
    $body    = '<p>Dear <strong>' . esc_html( $name ) . '</strong>,</p>';
    $body   .= '<p>Please find your registration card for <strong>' . esc_html( $event_name ) . '</strong> attached.</p>';
    $body   .= '<p>Feel free to share this card on your social media. It may inspire and encourage other Batchmate to register for the reunion.</p>';
    $body   .= '<p>— BatchMate</p>';

    $headers     = array( 'Content-Type: text/html; charset=UTF-8' );
    if ( ! empty( $event_row['cc_email'] ) && function_exists( 'batchmate_get_cc_headers' ) ) {
        $headers = array_merge( $headers, batchmate_get_cc_headers( $event_row['cc_email'] ) );
    }
    $attachments = array( $tmp_path );

    try {
        ob_start();
        $mail_error_msg = '';
        $catch_mail_error = function( $wp_error ) use ( &$mail_error_msg ) {
            $mail_error_msg = $wp_error->get_error_message();
        };
        add_action( 'wp_mail_failed', $catch_mail_error );

        $sent = wp_mail( $email, $subject, $body, $headers, $attachments );

        remove_action( 'wp_mail_failed', $catch_mail_error );
        $mail_warnings = ob_get_clean();
    } catch ( Exception $e ) {
        if (ob_get_level()) ob_end_clean();
        // @unlink( $tmp_path ); // Preserved in BatchMate-cards/ for Make.com & cleaned up by cron after 24h        return array( 'success' => false, 'message' => 'Mail server error: ' . $e->getMessage() );
    }
    
    $file_exists = file_exists($tmp_path);
    $file_size = $file_exists ? filesize($tmp_path) : 0;
    
    @unlink( $tmp_path );

    if ( $sent ) {
        return array( 'success' => true, 'message' => 'Card sent successfully to ' . $email );
    } else {
        $err_desc = 'Failed to send email. Ensure WP Mail SMTP or local mailer is configured properly.';
        if ( ! empty( $mail_error_msg ) ) {
            $err_desc .= ' Detail: ' . $mail_error_msg;
        }
        if ( ! empty( $mail_warnings ) ) {
            $err_desc .= ' Warnings: ' . $mail_warnings;
        }
        global $phpmailer;
        if ( isset( $phpmailer ) && ! empty( $phpmailer->ErrorInfo ) ) {
            $err_desc .= ' PHPMailer Error: ' . $phpmailer->ErrorInfo;
        }
        $err_desc .= ' [Debug Path: ' . $tmp_path . ' | Exists: ' . ($file_exists ? 'Yes' : 'No') . ' | Size: ' . $file_size . ']';
        
        // Fetch WP Mail SMTP Debug Events
        $debug_events = get_option( 'wp_mail_smtp_debug_events', array() );
        if ( is_array( $debug_events ) && ! empty( $debug_events ) ) {
            $last_event = end( $debug_events );
            $err_desc .= ' | WP_Mail_SMTP: ' . ( isset($last_event['message']) ? $last_event['message'] : json_encode($last_event) );
        }
        
        return array( 'success' => false, 'message' => $err_desc );
    }
}

function BatchMate_send_event_card_email_ajax() {
    batchmate_event_check_admin();

    $app_id    = intval( $_POST['app_id']    ?? 0 );
    $event_id  = intval( $_POST['event_id']  ?? 0 );

    $result = BatchMate_send_event_card_email_internal( $app_id, $event_id );
    if ( $result['success'] ) {
        wp_send_json_success( $result['message'] );
    } else {
        wp_send_json_error( $result['message'] );
    }
}
add_action( 'wp_ajax_BatchMate_send_event_card_email', 'BatchMate_send_event_card_email_ajax' );
add_action( 'wp_ajax_puaa_send_event_card_email',       'BatchMate_send_event_card_email_ajax' );