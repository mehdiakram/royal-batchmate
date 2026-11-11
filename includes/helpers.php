<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Royal BatchMate - Core Helpers & Utilities
 */
// HELPERS
// ============================================================
if ( ! defined( 'ROYALTECH_THEME_DIR' ) ) {
    define( 'ROYALTECH_THEME_DIR', get_template_directory_uri() );
}

/**
 * Dynamic org label helper
 */
function batchmate_org_label( string $type = 'short' ): string {
    static $s = null;
    if ( $s === null ) {
        $s = get_option( 'batchmate_settings', [] );
    }
    $short = trim( $s['org_short_name'] ?? '' );
    $full  = trim( $s['org_name']       ?? '' );
    $label = ( $type === 'full' )
        ? ( $full  ?: $short ?: 'BatchMate' )
        : ( $short ?: $full  ?: 'BatchMate' );
    return esc_html( $label );
}

/**
 * Dynamic Custom Labels (Batchmate, Member, etc.)
 */
function batchmate_get_label( string $key ): string {
    static $s = null;
    if ( $s === null ) {
        $s = get_option( 'batchmate_settings', [] );
    }
    
    $defaults = [
        'batchmate'  => 'Batchmate',
        'batchmates' => 'Batchmates',
        'member'     => 'Member',
        'members'    => 'Members',
        'main_menu'  => 'BatchMate',
        'settings_menu' => 'Settings',
        'settings_page_title' => 'BatchMate Settings'
    ];

    $setting_key = 'label_' . $key;
    $val = trim( $s[$setting_key] ?? '' );
    
    if ( empty( $val ) ) {
        if ( function_exists( 'batchmate_get_field_label' ) && ( isset( $s['fields']['reg_' . $key] ) || isset( $s['fields'][$key] ) ) ) {
            return batchmate_get_field_label( $key );
        }
        $val = $defaults[$key] ?? ucfirst( $key );
    }
    
    return esc_html( $val );
}

/**
 * Dynamic Registration & Profile Field Labels
 */
function batchmate_get_field_label( string $key, string $default = '' ): string {
    static $s = null;
    if ( $s === null ) {
        $s = get_option( 'batchmate_settings', array() );
    }

    $default_map = array(
        'reg_name'                   => 'Name (English)',
        'reg_name_bn'                => 'Name (Bangla)',
        'reg_email'                  => 'Email',
        'reg_cell_number'            => 'Cell Number',
        'reg_land_phone'             => 'Alternative Number',
        'reg_blood_group'            => 'Blood Group',
        'reg_dob'                    => 'Date of Birth',
        'reg_gender'                 => 'Gender',
        'reg_religion'               => 'Religion',
        'reg_photo'                  => 'Photo',
        'reg_board'                  => 'Name of Board',
        'reg_roll_no'                => 'SSC Roll',
        'reg_registration_no'        => 'Registration No',
        'reg_group'                  => 'Group',
        'reg_institution'            => 'Institution',
        'reg_present_address'        => 'Present Address',
        'reg_present_country'        => 'Present Country',
        'reg_present_district'       => 'Present District',
        'reg_present_upazila'        => 'Present Thana / Upazila',
        'reg_permanent_address'      => 'Permanent Address',
        'reg_permanent_country'      => 'Permanent Country',
        'reg_permanent_district'     => 'Permanent District',
        'reg_permanent_upazila'      => 'Permanent Thana / Upazila',
        'reg_organization'           => 'Organization',
        'reg_position'               => 'Position / Designation',
        'reg_industry'               => 'Industry',
        'reg_work_experience'        => 'Work Experience',
        'reg_employment_address'     => 'Organization Address',
        'reg_previous_organizations' => 'Previous Organizations',
        'reg_about_me'               => 'About Me',
        'reg_skills'                 => 'Skills',
        'reg_facebook'               => 'Facebook URL',
        'reg_linkedin'               => 'LinkedIn URL',
        'reg_x'                      => 'X / Twitter URL',
        'reg_website'                => 'Personal Website',
    );

    $clean_key = ( strpos( $key, 'reg_' ) === 0 ) ? $key : 'reg_' . $key;
    $short_key = ( strpos( $key, 'reg_' ) === 0 ) ? substr( $key, 4 ) : $key;

    // 1. Check configured field label in settings['fields'][$key]['label']
    if ( ! empty( $s['fields'][$clean_key]['label'] ) ) {
        return trim( $s['fields'][$clean_key]['label'] );
    }
    if ( ! empty( $s['fields'][$short_key]['label'] ) ) {
        return trim( $s['fields'][$short_key]['label'] );
    }

    // 2. Check standalone setting (e.g. label_institution, label_present_district, etc.)
    if ( ! empty( $s['label_' . $short_key] ) ) {
        return trim( $s['label_' . $short_key] );
    }
    if ( ! empty( $s['label_' . $clean_key] ) ) {
        return trim( $s['label_' . $clean_key] );
    }

    // 3. Custom passed default
    if ( ! empty( $default ) ) {
        return $default;
    }

    // 4. Default map
    if ( isset( $default_map[$clean_key] ) ) {
        return $default_map[$clean_key];
    }
    if ( isset( $default_map[$key] ) ) {
        return $default_map[$key];
    }

    return ucfirst( str_replace( array('reg_', '_'), array('', ' '), $key ) );
}

/**
 * Member ID Generator
 */
function batchmate_generate_member_id( int $next_number ): string {
    $s         = get_option( 'batchmate_settings', [] );
    $prefix    = strtoupper( trim( $s['org_member_prefix'] ?? '' ) );
    $year_opt  = $s['org_member_id_year'] ?? 'none';
    $digits    = intval( $s['org_member_id_digits'] ?? 4 );
    
    if ( empty( $prefix ) ) {
        $prefix = 'BM';
    }

    $year_str = '';
    if ( $year_opt === '2digit' ) {
        $year_str = '-' . gmdate('y');
    } elseif ( $year_opt === '4digit' ) {
        $year_str = '-' . gmdate('Y');
    }

    // Clean trailing dash if year is appended to avoid double dash
    if ( $year_str !== '' && rtrim($prefix, '-') !== $prefix ) {
        $prefix = rtrim( $prefix, '-' );
    }
    
    $base = $prefix . $year_str;
    if ( substr( $base, -1 ) !== '-' && substr( $base, -1 ) !== '_' ) {
        $base .= '-';
    }

    $format = '%s%0' . $digits . 'd';
    return sprintf( $format, $base, $next_number );
}

/**
 * Get domain used for auto-generated member emails
 */
function batchmate_get_email_domain(): string {
    $settings = get_option( 'batchmate_settings', [] );
    if ( ! empty( $settings['org_email'] ) && is_email( $settings['org_email'] ) ) {
        $parts = explode( '@', $settings['org_email'] );
        $d = trim( end( $parts ) );
        if ( ! empty( $d ) ) return strtolower( $d );
    }
    $raw_host = parse_url( home_url(), PHP_URL_HOST );
    if ( ! empty( $raw_host ) && $raw_host !== 'localhost' && ! filter_var( $raw_host, FILTER_VALIDATE_IP ) ) {
        return strtolower( preg_replace( '/^www\./i', '', $raw_host ) );
    }
    return 'batchmate.org';
}

/**
 * Auto-generate default member email from member number
 */
function batchmate_generate_member_email( string $member_number ): string {
    $clean = strtolower( trim( $member_number ) );
    $clean = preg_replace( '/[^a-z0-9._-]/', '', $clean );
    if ( empty( $clean ) ) {
        $clean = 'member';
    }
    return $clean . '@' . batchmate_get_email_domain();
}

/**
 * Upload size limit filter
 */
function BatchMate_override_upload_size_limit() {
    return wp_convert_hr_to_bytes( ini_get( 'upload_max_filesize' ) );
}

/**
 * Crop & resize an image to 400x400px (center crop)
 * Overwrites original file so original high-res image is never kept
 */
function BatchMate_crop_photo_400( string $file_path ): bool {
    if ( ! file_exists( $file_path ) ) return false;
    $editor = wp_get_image_editor( $file_path );
    if ( is_wp_error( $editor ) ) return false;

    $size   = $editor->get_size();
    $src_w  = intval( $size['width'] );
    $src_h  = intval( $size['height'] );

    $crop_size = min( $src_w, $src_h );
    $x = intval( ( $src_w - $crop_size ) / 2 );
    $y = intval( ( $src_h - $crop_size ) / 2 );

    $result = $editor->crop( $x, $y, $crop_size, $crop_size, 400, 400 );
    if ( is_wp_error( $result ) ) return false;

    $editor->set_quality( 90 );
    $saved = $editor->save( $file_path );
    return ! is_wp_error( $saved );
}

/**
 * Backward compatibility alias for 200x200 crop call -> now 400x400
 */
function BatchMate_crop_photo_200( string $file_path ): bool {
    return BatchMate_crop_photo_400( $file_path );
}

// ============================================================
// MEMBER ROLE
// ============================================================
function batchmate_register_member_role(): void {
    if ( ! get_role( 'batchmate_member' ) ) {
        add_role( 'batchmate_member', 'Member', [ 'read' => true ] );
    }
}
add_action( 'init', 'batchmate_register_member_role' );

// HELPERS: Institutions, Payment Methods & Membership Fee
// ============================================================
/**
 * Return sorted distinct non-empty institutions from member and rawdata tables.
 */
function batchmate_get_distinct_institutions(): array {
    global $wpdb;
    $table_m = $wpdb->prefix . 'batchmate_member';
    $table_r = $wpdb->prefix . 'batchmate_rawdata';

    $results = $wpdb->get_col( "
        SELECT DISTINCT institution FROM (
            SELECT TRIM(institution) AS institution FROM {$table_m} WHERE institution IS NOT NULL AND institution != ''
            UNION
            SELECT TRIM(institution) AS institution FROM {$table_r} WHERE institution IS NOT NULL AND institution != ''
        ) AS comb
        ORDER BY institution ASC
    " );

    return is_array( $results ) ? $results : array();
}

/**
 * Return array of payment method options (strings).
 */
function batchmate_get_payment_methods( int $event_id = 0 ): array {
    global $wpdb;

    if ( $event_id > 0 ) {
        if ( function_exists( 'batchmate_ensure_saree_column' ) ) {
            batchmate_ensure_saree_column();
        }
        $ev_methods = $wpdb->get_var( $wpdb->prepare(
            "SELECT payment_methods FROM {$wpdb->prefix}batchmate_event WHERE id = %d",
            $event_id
        ) );
        if ( ! empty( $ev_methods ) ) {
            $lines = array_filter( array_map( 'trim', preg_split( '/\r\n|\r|\n/', $ev_methods ) ) );
            if ( ! empty( $lines ) ) {
                return array_values( $lines );
            }
        }
    }

    $settings = get_option( 'batchmate_settings', array() );
    if ( ! empty( $settings['payment_methods'] ) ) {
        $lines = array_filter( array_map( 'trim', preg_split( '/\r\n|\r|\n/', $settings['payment_methods'] ) ) );
        if ( ! empty( $lines ) ) {
            return array_values( $lines );
        }
    }

    // Try batchmate_payment_modes table
    $modes_table = $wpdb->prefix . 'batchmate_payment_modes';
    if ( $wpdb->get_var( "SHOW TABLES LIKE '$modes_table'" ) === $modes_table ) {
        $modes = $wpdb->get_col( "SELECT name FROM $modes_table ORDER BY name ASC" );
        if ( ! empty( $modes ) ) {
            return array_values( array_filter( array_map( 'trim', $modes ) ) );
        }
    }

    return array( 'bKash', 'Nagad', 'Rocket', 'Bank Transfer', 'Cash' );
}

/**
 * Return membership fee configuration.
 */
function batchmate_get_membership_fee_info(): array {
    $settings = get_option( 'batchmate_settings', array() );
    $active   = ( ( $settings['reg_fee_active'] ?? 'off' ) === 'on' );
    $text     = $settings['reg_fee_text'] ?? 'Payment Method * [204 Taka]';
    $amount   = 0.0;

    if ( isset( $settings['reg_fee_amount'] ) && is_numeric( $settings['reg_fee_amount'] ) && floatval( $settings['reg_fee_amount'] ) > 0 ) {
        $amount = floatval( $settings['reg_fee_amount'] );
    } elseif ( preg_match( '/(\d+(?:\.\d+)?)/', $text, $m ) ) {
        $amount = floatval( $m[1] );
    }

    return array(
        'active' => $active,
        'amount' => $amount,
        'label'  => $text,
    );
}

// ============================================================
// AVATAR: BatchMate photo → WP avatar
// ============================================================
add_filter( 'pre_get_avatar_data', function( $args, $id_or_email ) {
    $user_id = 0;
    if ( is_numeric( $id_or_email ) )              $user_id = (int) $id_or_email;
    elseif ( $id_or_email instanceof WP_User )     $user_id = $id_or_email->ID;
    elseif ( $id_or_email instanceof WP_Post )     $user_id = (int) $id_or_email->post_author;
    elseif ( $id_or_email instanceof WP_Comment )  $user_id = (int) $id_or_email->user_id;
    elseif ( is_string( $id_or_email ) && is_email( $id_or_email ) ) {
        $user = get_user_by( 'email', $id_or_email );
        if ( $user ) $user_id = $user->ID;
    }
    if ( ! $user_id ) return $args;
    $custom_photo = get_user_meta( $user_id, 'batchmate_member_photo', true );
    if ( ! empty( $custom_photo ) ) {
        $args['url']          = $custom_photo;
        $args['found_avatar'] = true;
    }
    return $args;
}, 10, 2 );

// ============================================================
// HTTP TIMEOUT (for WP Mail SMTP)
// ============================================================
add_filter( 'http_request_timeout', fn( $t ) => 30 );
add_filter( 'http_request_args', function( $args ) {
    $args['timeout'] = 30;
    return $args;
} );

/**
 * Return array of 'Cc: email' header lines from a comma-separated or single string.
 */
function batchmate_get_cc_headers( ?string $cc_email_str ): array {
    $headers = array();
    if ( empty( $cc_email_str ) ) {
        return $headers;
    }
    $parts = preg_split( '/[,;]+/', $cc_email_str );
    foreach ( $parts as $p ) {
        $clean = sanitize_email( trim( $p ) );
        if ( is_email( $clean ) ) {
            $headers[] = 'Cc: ' . $clean;
        }
    }
    return $headers;
}
