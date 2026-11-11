<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/* =====================================================================
 * EXCEL IMPORT — AJAX HANDLER
 * Action : batchmate_excel_import
 * Nonce  : batchmate_excel_import_nonce  (field: _excel_import_nonce)
 *
 * Excel Column Mapping (Google Form Export):
 *   A  → applied_at (timestamp)        B  → student_id
 *   C  → name                          D  → institution
 *   E  → upazila                       F  → permanent_district
 *   G  → cell_number                   H  → (skip – duplicate mobile)
 *   I  → blood_group                   J  → email
 *   K  → facebook_url                  L  → tshirt_size
 *   M  → payment_details               N  → (skip – nickname)
 *   O  → options_json (fee text)       P  → (skip – static text)
 *   Q  → (skip – consent)              R  → amount
 *   S  → (skip – static notice)        T  → photo_url
 *   U  → date_of_birth                 V  → gender
 *   W  → employment_position           X  → payment_receiver
 *   Y  → remarks
 * ===================================================================== */

add_action( 'wp_ajax_batchmate_excel_import', 'batchmate_excel_import_ajax' );

if ( ! function_exists( 'batchmate_excel_import_ajax' ) ) {
function batchmate_excel_import_ajax(): void {
    // ── Auth ──────────────────────────────────────────────────────────
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( 'Permission denied.' );
    }
    check_ajax_referer( 'batchmate_excel_import_nonce', '_excel_import_nonce' );

    // ── Params ────────────────────────────────────────────────────────
    $event_id          = intval( $_POST['event_id'] ?? 0 );
    $skip_dupes        = ! empty( $_POST['skip_duplicates'] );
    $dry_run           = ! empty( $_POST['dry_run'] );
    $import_as_members = ! empty( $_POST['import_as_members'] );
    $has_event         = $event_id > 0;   // false → rawdata-only mode

    if ( $event_id <= 0 ) {
        // Now optional, no error needed here. But if we did:
        // wp_send_json_error( __( 'Please select an event.', 'royal-batchmate' ) );
    }

    // ── File ─────────────────────────────────────────────────────────
    if ( empty( $_FILES['xlsx_file']['tmp_name'] ) ) {
        wp_send_json_error( __( 'Please upload an Excel file.', 'royal-batchmate' ) );
    }

    $tmp = $_FILES['xlsx_file']['tmp_name'];
    $rows = batchmate_parse_xlsx( $tmp );

    if ( $rows === false ) {
        wp_send_json_error( __( 'Could not read the Excel file. Please ensure the ZIP extension is enabled.', 'royal-batchmate' ) );
    }
    if ( count( $rows ) <= 1 ) {
        wp_send_json_error( __( 'The file contains no data.', 'royal-batchmate' ) );
    }

    // Remove header row
    array_shift( $rows );

    global $wpdb;
    $raw_tbl = $wpdb->prefix . 'batchmate_rawdata';
    $ea_tbl  = $wpdb->prefix . 'batchmate_event_application';

    $total   = 0;
    $success = 0;
    $skipped = 0;
    $errors  = 0;
    $log     = [];

    foreach ( $rows as $row ) {
        $total++;

        // Read cells from new Excel mapping
        $col_id      = trim( $row['A'] ?? '' );   // Member ID
        $col_en_name = trim( $row['B'] ?? '' );   // En Name
        $col_bn_name = trim( $row['C'] ?? '' );   // Bn Name
        $col_school  = trim( $row['D'] ?? '' );   // School Name
        $col_dist    = trim( $row['E'] ?? '' );   // District
        $col_mobile  = trim( $row['F'] ?? '' );   // Contact
        $col_mobile2 = trim( $row['G'] ?? '' );   // Contact 2
        $col_blood   = trim( $row['H'] ?? '' );   // Blood Group
        $col_email   = trim( $row['I'] ?? '' );   // Email
        $col_fb      = trim( $row['J'] ?? '' );   // Facebook
        $col_tshirt  = trim( $row['K'] ?? '' );   // Tshirt size
        $col_fee     = trim( $row['L'] ?? '' );   // Fee
        $col_pay_det = trim( $row['M'] ?? '' );   // Payment Details
        $col_paid    = trim( $row['N'] ?? '' );   // Paid Amount
        $col_photo   = trim( $row['O'] ?? '' );   // Photo
        $col_dob     = trim( $row['P'] ?? '' );   // DOB
        $col_gender  = trim( $row['Q'] ?? '' );   // Gender
        $col_prof    = trim( $row['R'] ?? '' );   // Profession

        // Skip empty rows
        if ( empty( $col_en_name ) && empty( $col_mobile ) ) {
            $log[] = "<span style='color:#888;'>[SKIP #{$total}] Empty row</span>";
            $skipped++;
            continue;
        }

        // Mobile normalization
        $bn_digits = ['১','২','৩','৪','৫','৬','৭','৮','৯','০'];
        $en_digits = ['1','2','3','4','5','6','7','8','9','0'];
        $col_mobile = str_replace($bn_digits, $en_digits, $col_mobile);
        
        $col_g_parts = preg_split('/[\/,\-]/', $col_mobile);
        $first_mobile = '';
        foreach ($col_g_parts as $part) {
            $clean_part = preg_replace('/[^0-9]/', '', $part);
            if (strlen($clean_part) >= 10) {
                $first_mobile = $clean_part;
                break;
            }
        }
        if (empty($first_mobile)) {
            $first_mobile = preg_replace('/[^0-9]/', '', $col_mobile);
        }
        $col_mobile = substr($first_mobile, 0, 20); // enforce limit
        
        if ( preg_match( '/^1[3-9]\d{8}$/', $col_mobile ) ) {
            $col_mobile = '0' . $col_mobile;
        }

        // Blood group
        $blood = batchmate_normalize_blood_group( $col_blood );

        // Gender
        $gender_map = [
            'পুরুষ'  => 'Male',
            'মহিলা' => 'Female',
            'নারী'  => 'Female',
            'male'   => 'Male',
            'female' => 'Female',
        ];
        $gender = $gender_map[ mb_strtolower( $col_gender, 'UTF-8' ) ] ?? ( $col_gender ?: 'Male' );
        $gender = sanitize_text_field( mb_substr( $gender, 0, 10, 'UTF-8' ) );

        $applied_at = current_time( 'mysql' ); // Default timestamp

        // Date of birth
        $dob = null;
        if ( ! empty( $col_dob ) ) {
            if ( is_numeric( $col_dob ) && floatval( $col_dob ) > 100 ) {
                $unix = ( floatval( $col_dob ) - 25569 ) * 86400;
                $dob  = gmdate( 'Y-m-d', (int) $unix );
            } else {
                $parsed_dob = strtotime( $col_dob );
                if ( $parsed_dob && date( 'Y', $parsed_dob ) > 1900 ) {
                    $dob = date( 'Y-m-d', $parsed_dob );
                }
            }
        }

        // Student ID
        $student_id = sanitize_text_field( mb_substr( $col_id, 0, 20, 'UTF-8' ) );
        if ( empty( $student_id ) && ! empty( $col_mobile ) ) {
            $student_id = preg_replace( '/[^0-9]/', '', $col_mobile );
        }

        // Photo URL
        if (strpos($col_photo, 'drive.google.com/open?id=') !== false) {
            $col_photo = str_replace('open?id=', 'thumbnail?sz=w500-h500&id=', $col_photo);
        } elseif (strpos($col_photo, 'drive.google.com/file/d/') !== false) {
            if (preg_match('/file\/d\/([a-zA-Z0-9_-]+)/', $col_photo, $matches)) {
                $col_photo = 'https://drive.google.com/thumbnail?sz=w500-h500&id=' . $matches[1];
            }
        }
        $default_photo = ROYAL_BATCHMATE_PLUGIN_URL . 'assets/images/noprofile.png';
        $photo_url     = ! empty( $col_photo ) ? esc_url_raw( $col_photo ) : $default_photo;

        // Duplicate check
        if ( $skip_dupes ) {
            $existing_id = null;
            if ( ! empty( $student_id ) ) {
                $existing_id = $wpdb->get_var( $wpdb->prepare(
                    "SELECT id FROM {$raw_tbl} WHERE student_id = %s LIMIT 1",
                    $student_id
                ) );
            }
            if ( ! $existing_id && ! empty( $col_mobile ) ) {
                $clean_mobile = preg_replace( '/[^0-9]/', '', $col_mobile );
                $existing_id  = $wpdb->get_var( $wpdb->prepare(
                    "SELECT id FROM {$raw_tbl} WHERE cell_number = %s LIMIT 1",
                    $clean_mobile
                ) );
            }

            if ( $existing_id ) {
                $log[] = "<span style='color:#f59e0b;'>[SKIP #{$total}] Duplicate - {$col_en_name} ({$col_mobile}) -> rawdata #{$existing_id}</span>";
                $skipped++;
                continue;
            }
        }

        $raw_data = [
            'student_id'          => $student_id,
            'name'                => sanitize_text_field( $col_en_name ),
            'name_bn'             => sanitize_text_field( $col_bn_name ),
            'email'               => sanitize_email( $col_email ),
            'cell_number'         => sanitize_text_field( $col_mobile ),
            'land_phone_number'   => sanitize_text_field( $col_mobile2 ),
            'blood_group'         => $blood,
            'gender'              => $gender,
            'date_of_birth'       => $dob,
            'facebook_url'        => esc_url_raw( $col_fb ),
            'employment_position' => sanitize_text_field( batchmate_transliterate_bn_to_en( $col_prof ) ),
            'institution'         => sanitize_text_field( batchmate_transliterate_bn_to_en( $col_school ) ),
            'permanent_district'  => sanitize_text_field( batchmate_transliterate_bn_to_en( $col_dist ) ),
            'upazila'             => '',
            'photo_url'           => $photo_url,
            'status'              => 'pending',
            'submission_date'     => $applied_at,
        ];

        // Prepare event_application 
        $ea_data = [
            'event_id'        => $event_id,
            'member_id'       => 0,       
            'rawdata_id'      => 0,       
            'payment_method'  => 'bKash/Nagad/Rocket',
            'payment_details' => sanitize_text_field( $col_pay_det ),
            'amount'          => floatval( $col_paid ),
            'payment_receiver'=> '',
            'remarks'         => sanitize_textarea_field( "Contact 2: " . $col_mobile2 ),
            'options_json'    => ! empty( $col_fee ) ? wp_json_encode( [ 'fee_option' => $col_fee ] ) : null,
            'tshirt_size'     => sanitize_text_field( $col_tshirt ),
            'status'          => 'pending',
            'applied_at'      => $applied_at,
        ];

        if ( $dry_run ) {
            $display_name = ($col_en_name !== $col_bn_name && !empty($col_bn_name)) ? "{$col_en_name} ({$col_bn_name})" : $col_en_name;
            $log[] = "<span style='color:#60a5fa;'>[DRY #{$total}] {$display_name} | {$raw_data['cell_number']} | {$raw_data['permanent_district']} | ৳{$col_paid} | {$blood} | {$gender}</span>";
            $success++;
            continue;
        }

        // Insert rawdata if NOT importing directly as members
        $raw_id = 0;
        if ( ! $import_as_members ) {
            $raw_inserted = $wpdb->insert( $raw_tbl, $raw_data );
            if ( ! $raw_inserted ) {
                $log[] = "<span style='color:#f87171;'>[ERR #{$total}] rawdata insert failed - {$col_en_name} ({$col_mobile}) : " . $wpdb->last_error . "</span>";
                $errors++;
                continue;
            }
            $raw_id = $wpdb->insert_id;
        }

        $member_id = 0;
        $log_msgs = [];

        if ( $import_as_members ) {
            $member_table = $wpdb->prefix . 'batchmate_member';
            $final_student_id = $raw_data['student_id'];
            if (empty($final_student_id) && function_exists('batchmate_generate_member_id')) {
                $max_id = (int) $wpdb->get_var( "SELECT COALESCE( MAX(id), 0 ) FROM $member_table" );
                $final_student_id = batchmate_generate_member_id($max_id + $total);
            }

            $cell = $raw_data['cell_number'];
            if (empty($cell)) {
                $unique_suffix = $raw_id ? $raw_id : 'R' . $total . time();
                $cell = 'NONE-' . $unique_suffix;
            }
            
            $email = $raw_data['email'];
            if (!empty($email)) {
                $is_duplicate = $wpdb->get_var($wpdb->prepare("SELECT id FROM $member_table WHERE email = %s LIMIT 1", $email));
                if ($is_duplicate) {
                    $email = '';
                }
            }
            if (empty($email)) {
                $email = strtolower($final_student_id) . '@batchmate.local';
            }
            
            $user_id = email_exists($email);
            if (!$user_id) $user_id = username_exists($cell);
            if (!$user_id) {
                $user_id = wp_create_user($cell, wp_generate_password(), $email);
                if (!is_wp_error($user_id)) {
                    $user = new WP_User($user_id);
                    $user->set_role('subscriber');
                }
            }
            if (!is_wp_error($user_id)) {
                if (!empty($raw_data['photo_url'])) {
                    update_user_meta($user_id, 'batchmate_member_photo', $raw_data['photo_url']);
                }
                
                $unique_app_id = $raw_id ? -$raw_id : -1000000 - $total;
                
                $member_data = [
                    'wp_user_id' => $user_id,
                    'application_id' => $unique_app_id,
                    'member_number' => $final_student_id,
                    'student_id' => $final_student_id,
                    'name' => $raw_data['name'],
                    'name_bn' => $raw_data['name_bn'],
                    'email' => $email,
                    'cell_number' => $raw_data['cell_number'],
                    'land_phone_number' => substr(sanitize_text_field($raw_data['land_phone_number']), 0, 20),
                    'blood_group' => $raw_data['blood_group'],
                    'gender' => $raw_data['gender'],
                    'date_of_birth' => $raw_data['date_of_birth'],
                    'photo_url' => $raw_data['photo_url'],
                    'permanent_district' => $raw_data['permanent_district'],
                    'upazila' => $raw_data['upazila'],
                    'institution' => $raw_data['institution'],
                    'employment_position' => $raw_data['employment_position'],
                    'facebook_url' => $raw_data['facebook_url'],
                    'joining_date' => current_time('mysql'),
                ];
                $wpdb->insert($member_table, $member_data);
                $member_id = $wpdb->insert_id;
                
                if ( $member_id ) {
                    $wpdb->update($raw_tbl, ['status' => 'Applied'], ['id' => $raw_id]);
                    $log_msgs[] = "member#{$member_id}";
                } else {
                    $log_msgs[] = "member#0 (" . $wpdb->last_error . ")";
                }
            } else {
                $log_msgs[] = "User creation failed";
            }
        }

        // 2. Create Event Application if requested
        $ea_failed = false;
        if ( $has_event ) {
            $ea_data['rawdata_id'] = $raw_id;
            $ea_data['member_id']  = $member_id;
            
            if ( $import_as_members ) {
                $ea_data['status'] = 'approved';
            }
            
            $ea_inserted = $wpdb->insert( $ea_tbl, $ea_data );
            if ( ! $ea_inserted ) {
                $ea_failed = true;
                $log_msgs[] = "ea failed - " . $wpdb->last_error;
            } else {
                $ea_id = $wpdb->insert_id;
                $log_msgs[] = "ea#{$ea_id}";
                
                if ( $import_as_members && $member_id ) {
                    $em_tbl = $wpdb->prefix . 'batchmate_event_members';
                    $max_serial = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COALESCE(MAX(serial_no), 0) FROM $em_tbl WHERE event_id = %d", $event_id ) );
                    $wpdb->insert( $em_tbl, [
                        'event_id'       => $event_id,
                        'member_id'      => $member_id,
                        'application_id' => $ea_id,
                        'rawdata_id'     => $raw_id,
                        'serial_no'      => $max_serial + 1,
                        'approved_at'    => current_time('mysql'),
                        'approved_by'    => get_current_user_id()
                    ]);
                }
            }
        }

        // 3. Output Log
        if ( empty($log_msgs) ) {
            $log_msgs[] = "(rawdata only)";
        }
        
        $log_str = implode(' + ', $log_msgs);
        if ( $ea_failed ) {
            $log[] = "<span style='color:#fbbf24;'>[WARN #{$total}] {$col_en_name} | {$col_mobile} | raw#{$raw_id} -> {$log_str}</span>";
            $errors++;
        } else {
            $log[] = "<span style='color:#4ade80;'>[OK #{$total}] {$col_en_name} | {$col_mobile} | raw#{$raw_id} -> {$log_str}</span>";
            $success++;
        }
    }
wp_send_json_success( [
        'total'    => $total,
        'success'  => $success,
        'skipped'  => $skipped,
        'errors'   => $errors,
        'dry_run'  => $dry_run,
        'event_id' => $event_id,
        'log'      => $log,
    ] );
}


/* ─────────────────────────────────────────────────────────────────────
 * HELPER: Parse .xlsx → array of associative rows (col letter => value)
 * Uses only ZipArchive + DOMDocument (always available in PHP)
 * ───────────────────────────────────────────────────────────────────── */
}

function batchmate_parse_xlsx( string $file ): array|false {
    if ( ! class_exists( 'ZipArchive' ) ) return false;

    $zip = new ZipArchive();
    if ( $zip->open( $file ) !== true ) return false;

    // 1. Shared strings
    $shared = [];
    $ss_xml = $zip->getFromName( 'xl/sharedStrings.xml' );
    if ( $ss_xml ) {
        $ss_dom = new DOMDocument();
        @$ss_dom->loadXML( $ss_xml );
        // Each <si> element may have one or more <t> children (rich text)
        foreach ( $ss_dom->getElementsByTagName( 'si' ) as $si ) {
            $text = '';
            foreach ( $si->getElementsByTagName( 't' ) as $t ) {
                $text .= $t->nodeValue;
            }
            $shared[] = $text;
        }
    }

    // 2. Sheet 1
    $sheet_xml = $zip->getFromName( 'xl/worksheets/sheet1.xml' );
    $zip->close();

    if ( ! $sheet_xml ) return false;

    $dom = new DOMDocument();
    @$dom->loadXML( $sheet_xml );

    $result = [];

    foreach ( $dom->getElementsByTagName( 'row' ) as $row_node ) {
        $row_data = [];

        foreach ( $row_node->getElementsByTagName( 'c' ) as $cell ) {
            $ref    = $cell->getAttribute( 'r' );          // e.g. "A1", "AB3"
            $type   = $cell->getAttribute( 't' );          // 's'=shared, 'b'=bool, ''=number
            $v_node = $cell->getElementsByTagName( 'v' )->item( 0 );
            $val    = $v_node ? $v_node->nodeValue : '';

            if ( $type === 's' ) {
                $val = $shared[ (int) $val ] ?? $val;
            } elseif ( is_numeric( $val ) && strpos( strtoupper((string)$val), 'E' ) !== false ) {
                // Expand scientific notation (e.g. 1.712335469E9 -> 1712335469)
                $val = number_format( (float) $val, 0, '', '' );
            }

            // Extract only the letter part of the reference
            preg_match( '/^([A-Z]+)/', $ref, $m );
            $col = $m[1] ?? 'A';

            $row_data[ $col ] = $val;
        }

        $result[] = $row_data;
    }

    return $result;
}


/* ─────────────────────────────────────────────────────────────────────
 * HELPER: Normalize blood group strings
 *   "A(+ve)" → "A+"   "AB(-ve)" → "AB-"   "B Positive" → "B+"
 * ───────────────────────────────────────────────────────────────────── */
function batchmate_normalize_blood_group( string $raw ): string {
    $raw = trim( $raw );
    if ( empty( $raw ) ) return '';

    $bn_map = [
        'এবি' => 'AB',
        'এ'   => 'A',
        'বি'  => 'B',
        'ও'   => 'O',
        '০'   => 'O',
        '0'   => 'O',
        'পজেটিভ' => '+',
        'পজিটিভ' => '+',
        'নেগেটিভ' => '-',
    ];
    $normalized = str_replace( array_keys($bn_map), array_values($bn_map), $raw );
    
    $en_map = [
        'positive' => '+',
        'negative' => '-',
        'pos' => '+',
        'neg' => '-',
        '+ve' => '+',
        '-ve' => '-',
    ];
    $normalized = str_ireplace( array_keys($en_map), array_values($en_map), $normalized );
    
    // Remove unwanted characters
    $normalized = str_replace(['"', "'", '(', ')', ' ', '.', ',', '/', 'V', 'v'], '', $normalized);
    $normalized = strtoupper($normalized);

    // Now look for standard ABO patterns
    if ( preg_match( '/(AB|A|B|O)[\s\-]*(?:\+|\-)/i', $normalized, $m ) ) {
        $type = $m[1];
        $sign = strpos($normalized, '-') !== false ? '-' : '+';
        return $type . $sign;
    }

    if ( preg_match( '/^(AB|A|B|O)[+-]$/i', $normalized, $m ) ) {
        return $m[0];
    }

    return sanitize_text_field( mb_substr( $raw, 0, 5, 'UTF-8' ) );
}

/* ─────────────────────────────────────────────────────────────────────
 * HELPER: Transliterate Bengali to English (Basic)
 * ───────────────────────────────────────────────────────────────────── */
function batchmate_transliterate_bn_to_en( $text ) {
    if ( empty( $text ) ) return '';
    
    // Check if it contains BOTH English letters and Bengali letters
    $has_en = preg_match( '/[a-zA-Z]/', $text );
    $has_bn = preg_match( '/[\x{0980}-\x{09FF}]/u', $text );
    
    if ( $has_en && $has_bn ) {
        // If it contains both, e.g., "Abdul Kddus (আবদুল কদ্দূস)", we just keep the English part
        $text = preg_replace( '/[\x{0980}-\x{09FF}\(\)]+/u', '', $text );
        $text = preg_replace( '/\s+/', ' ', $text ); // Clean up extra spaces
        return trim( $text );
    }

    // If no Bengali characters, just return
    if ( ! $has_bn ) {
        return $text;
    }

    // Pre-process common prefixes to avoid issues during str_replace
    $text = preg_replace( '/^মো\s+/u', 'Md. ', $text );
    $text = str_replace( ['মোছাঃ', 'মোসাঃ', 'মোসাম্মাৎ', 'মোসাম্মাত'], 'Most.', $text );
    $text = str_replace( ['মোঃ', 'মো:', 'মো.'], 'Md.', $text );

    $map = array(
        // Vowels
        'অ' => 'O', 'আ' => 'A', 'ই' => 'I', 'ঈ' => 'I', 'উ' => 'U', 'ঊ' => 'U',
        'ঋ' => 'Ri', 'এ' => 'E', 'ঐ' => 'Oi', 'ও' => 'O', 'ঔ' => 'Ou',
        
        // Consonants
        'ক' => 'K', 'খ' => 'Kh', 'গ' => 'G', 'ঘ' => 'Gh', 'ঙ' => 'Ng',
        'চ' => 'Ch', 'ছ' => 'Chh', 'জ' => 'J', 'ঝ' => 'Jh', 'ঞ' => 'N',
        'ট' => 'T', 'ঠ' => 'Th', 'ড' => 'D', 'ঢ' => 'Dh', 'ণ' => 'N',
        'ত' => 'T', 'থ' => 'Th', 'দ' => 'D', 'ধ' => 'Dh', 'ন' => 'N',
        'প' => 'P', 'ফ' => 'F', 'ব' => 'B', 'ভ' => 'Bh', 'ম' => 'M',
        'য' => 'J', 'র' => 'R', 'ল' => 'L',
        'শ' => 'Sh', 'ষ' => 'Sh', 'স' => 'S', 'হ' => 'H',
        'ড়' => 'R', 'ঢ়' => 'Rh', 'য়' => 'Y', 'ৎ' => 'T',
        
        // Vowel Signs
        'া' => 'a', 'ি' => 'i', 'ী' => 'i', 'ু' => 'u', 'ূ' => 'u',
        'ৃ' => 'ri', 'ে' => 'e', 'ৈ' => 'oi', 'ো' => 'o', 'ৌ' => 'ou',
        
        // Other modifiers
        'ং' => 'ng', 'ঃ' => 'h', 'ঁ' => 'n', '্' => '',
        
        // Common initials
        'এস.এম.' => 'S M ',
        'এস.এম' => 'S M ',
        'এস এম' => 'S M ',
        'এ.কে.' => 'A K ',
        'এ.কে' => 'A K ',
        'এ কে' => 'A K ',
        'এইচ.এম.' => 'H M ',
        'এইচ.এম' => 'H M ',
        'এইচ এম' => 'H M ',
        'এম.এ.' => 'M A ',
        'এম.এ' => 'M A ',
        'এম এ' => 'M A ',
        
        // Common words
        'মোহাম্মদ' => 'Mohammad',
        'শেখ' => 'Sheikh',
        'সৈয়দ' => 'Syed',
        'মীর' => 'Mir',
        'কাজী' => 'Kazi',
        'আবু' => 'Abu',
        'আব্দুল' => 'Abdul',
        'রহমান' => 'Rahman',
        'ইসলাম' => 'Islam',
        'আলী' => 'Ali',
        'খান' => 'Khan',
        'চৌধুরী' => 'Chowdhury',
        'মিয়া' => 'Mia',
        'আহমেদ' => 'Ahmed',
        'হোসেন' => 'Hossain',
        'হাসান' => 'Hasan',
        'বেগম' => 'Begum',
        'আকতার' => 'Aktar',
        'খাতুন' => 'Khatun'
    );

    // Apply specific common word replacements first
    $text = str_replace( array_keys( $map ), array_values( $map ), $text );
    
    // Capitalize words properly
    $text = ucwords( strtolower( $text ) );
    
    // Fix common issues after basic replace
    $text = str_replace( '  ', ' ', $text );
    $text = str_replace( 'Md. ', 'Md.', $text ); // Standardize to Md. without space for now
    $text = str_replace( 'Md.', 'Md. ', $text ); // Ensure space after Md.
    $text = str_replace( 'Most. ', 'Most.', $text );
    $text = str_replace( 'Most.', 'Most. ', $text );
    
    return trim( $text );
}