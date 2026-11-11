<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * ===================================================================
 * [event_form id="X"] � Frontend Event Registration Form
 * Live server-side search: triggers after 3 characters typed (350ms debounce)
 * ===================================================================
 */
function batchmate_event_form_shortcode( $atts ) {
    $atts     = shortcode_atts( array( 'id' => 0 ), $atts, 'event_form' );
    $event_id = intval( $atts['id'] );

    if ( ! $event_id ) return '<p>Invalid event form. Please specify a valid event ID.</p>';

    global $wpdb;
    $event = $wpdb->get_row( $wpdb->prepare(
        "SELECT id, event_name, event_date, allow_non_members, location, has_tshirt, has_saree, direct_reg_text FROM {$wpdb->prefix}batchmate_event WHERE id = %d AND is_active = 1",
        $event_id
    ) );
    if ( ! $event ) {
        // Backward compat: try without has_saree
        $event = $wpdb->get_row( $wpdb->prepare(
            "SELECT id, event_name, event_date, allow_non_members, location, has_tshirt, 0 as has_saree, '' as direct_reg_text FROM {$wpdb->prefix}batchmate_event WHERE id = %d AND is_active = 1",
            $event_id
        ) );
    }

    if ( ! $event ) {
        return '<div class="BatchMate-ef-notice BatchMate-ef-notice--closed">&#128683; This event is currently not accepting registrations.</div>';
    }

    // Load registration field settings for non-member form
    $bm_settings = get_option( 'batchmate_settings', array() );
    $field_cfg   = $bm_settings['fields'] ?? array();
    $has_saved   = ! empty( $field_cfg ) && is_array( $field_cfg );

    $fs = function( $key ) use ( $field_cfg, $has_saved ) {
        if ( in_array( $key, array( 'reg_name', 'reg_cell_number' ), true ) ) return true;
        if ( $has_saved ) {
            if ( isset( $field_cfg[ $key ]['show'] ) ) {
                return ! empty( $field_cfg[ $key ]['show'] );
            }
            if ( in_array( $key, array( 'reg_present_country', 'reg_present_upazila' ), true ) && isset( $field_cfg['reg_present_district']['show'] ) ) {
                return ! empty( $field_cfg['reg_present_district']['show'] );
            }
            if ( in_array( $key, array( 'reg_permanent_country', 'reg_permanent_upazila' ), true ) && isset( $field_cfg['reg_permanent_district']['show'] ) ) {
                return ! empty( $field_cfg['reg_permanent_district']['show'] );
            }
            return false;
        }
        return in_array( $key, array( 'reg_name_bn', 'reg_email', 'reg_dob', 'reg_gender', 'reg_photo', 'reg_board', 'reg_institution', 'reg_present_address', 'reg_present_country', 'reg_present_district', 'reg_present_upazila', 'reg_permanent_address', 'reg_permanent_country', 'reg_permanent_district', 'reg_permanent_upazila' ), true );
    };
    $fr = function( $key ) use ( $field_cfg, $has_saved ) {
        if ( in_array( $key, array( 'reg_name', 'reg_cell_number' ), true ) ) return true;
        if ( $key === 'reg_email' ) return false;
        if ( $has_saved ) {
            return ! empty( $field_cfg[ $key ]['req'] );
        }
        return in_array( $key, array( 'reg_institution', 'reg_photo' ), true );
    };
    $guest_fields_config = array(
        'name'               => array( 'show' => true,                              'req' => true,                               'label' => 'Name' ),
        'name_bn'            => array( 'show' => $fs('reg_name_bn'),                'req' => $fr('reg_name_bn'),                'label' => 'Name (Bangla)' ),
        'email'              => array( 'show' => $fs('reg_email'),                  'req' => $fr('reg_email'),                  'label' => 'Email' ),
        'cell'               => array( 'show' => true,                              'req' => true,                               'label' => 'Mobile' ),
        'land_phone'         => array( 'show' => $fs('reg_land_phone'),             'req' => $fr('reg_land_phone'),             'label' => 'Alternative Number' ),
        'gender'             => array( 'show' => $fs('reg_gender'),                 'req' => $fr('reg_gender'),                 'label' => 'Gender' ),
        'blood_group'        => array( 'show' => $fs('reg_blood_group'),            'req' => $fr('reg_blood_group'),            'label' => 'Blood Group' ),
        'dob'                => array( 'show' => $fs('reg_dob'),                    'req' => $fr('reg_dob'),                    'label' => 'Date of Birth' ),
        'religion'           => array( 'show' => $fs('reg_religion'),               'req' => $fr('reg_religion'),               'label' => 'Religion' ),
        'board'              => array( 'show' => $fs('reg_board'),                  'req' => $fr('reg_board'),                  'label' => 'Board' ),
        'roll_no'            => array( 'show' => $fs('reg_roll_no'),                'req' => $fr('reg_roll_no'),                'label' => 'Roll No' ),
        'reg_no'             => array( 'show' => $fs('reg_registration_no'),        'req' => $fr('reg_registration_no'),        'label' => 'Reg No' ),
        'group'              => array( 'show' => $fs('reg_group'),                 'req' => $fr('reg_group'),                 'label' => ( function_exists('batchmate_get_field_label') ? batchmate_get_field_label('reg_group', 'Group') : 'Group' ) ),
        'institution'        => array( 'show' => $fs('reg_institution'),           'req' => $fr('reg_institution'),           'label' => ( function_exists('batchmate_get_field_label') ? batchmate_get_field_label('reg_institution', 'Institution') : 'Institution' ) ),
        'present_address'    => array( 'show' => $fs('reg_present_address'),       'req' => $fr('reg_present_address'),       'label' => ( function_exists('batchmate_get_field_label') ? batchmate_get_field_label('reg_present_address', 'Present Address') : 'Present Address' ) ),
        'present_country'    => array( 'show' => $fs('reg_present_country'),       'req' => $fr('reg_present_country'),       'label' => ( function_exists('batchmate_get_field_label') ? batchmate_get_field_label('reg_present_country', 'Present Country') : 'Present Country' ) ),
        'present_district'   => array( 'show' => $fs('reg_present_district'),      'req' => $fr('reg_present_district'),      'label' => ( function_exists('batchmate_get_field_label') ? batchmate_get_field_label('reg_present_district', 'Present District') : 'Present District' ) ),
        'present_upazila'    => array( 'show' => $fs('reg_present_upazila'),       'req' => $fr('reg_present_upazila'),       'label' => ( function_exists('batchmate_get_field_label') ? batchmate_get_field_label('reg_present_upazila', 'Present Thana / Upazila') : 'Present Thana / Upazila' ) ),
        'permanent_address'  => array( 'show' => $fs('reg_permanent_address'),     'req' => $fr('reg_permanent_address'),     'label' => ( function_exists('batchmate_get_field_label') ? batchmate_get_field_label('reg_permanent_address', 'Permanent Address') : 'Permanent Address' ) ),
        'permanent_country'  => array( 'show' => $fs('reg_permanent_country'),     'req' => $fr('reg_permanent_country'),     'label' => ( function_exists('batchmate_get_field_label') ? batchmate_get_field_label('reg_permanent_country', 'Permanent Country') : 'Permanent Country' ) ),
        'permanent_district' => array( 'show' => $fs('reg_permanent_district'),    'req' => $fr('reg_permanent_district'),    'label' => ( function_exists('batchmate_get_field_label') ? batchmate_get_field_label('reg_permanent_district', 'Permanent District') : 'Permanent District' ) ),
        'permanent_upazila'  => array( 'show' => $fs('reg_permanent_upazila'),     'req' => $fr('reg_permanent_upazila'),     'label' => ( function_exists('batchmate_get_field_label') ? batchmate_get_field_label('reg_permanent_upazila', 'Permanent Thana / Upazila') : 'Permanent Thana / Upazila' ) ),
        'organization'       => array( 'show' => $fs('reg_organization'),          'req' => $fr('reg_organization'),          'label' => 'Organization' ),
        'position'           => array( 'show' => $fs('reg_position'),              'req' => $fr('reg_position'),              'label' => 'Position / Designation' ),
        'industry'           => array( 'show' => $fs('reg_industry'),              'req' => $fr('reg_industry'),              'label' => 'Industry' ),
        'work_experience'    => array( 'show' => $fs('reg_work_experience'),       'req' => $fr('reg_work_experience'),       'label' => 'Work Experience' ),
        'employment_address' => array( 'show' => $fs('reg_employment_address'),    'req' => $fr('reg_employment_address'),    'label' => 'Organization Address' ),
        'previous_organizations' => array( 'show' => $fs('reg_previous_organizations'), 'req' => $fr('reg_previous_organizations'), 'label' => 'Previous Organizations' ),
        'about_me'           => array( 'show' => $fs('reg_about_me'),              'req' => $fr('reg_about_me'),              'label' => 'About Me' ),
        'skills'             => array( 'show' => $fs('reg_skills'),                'req' => $fr('reg_skills'),                'label' => 'Skills' ),
        'facebook'           => array( 'show' => $fs('reg_facebook'),              'req' => $fr('reg_facebook'),              'label' => 'Facebook URL' ),
        'linkedin'           => array( 'show' => $fs('reg_linkedin'),              'req' => $fr('reg_linkedin'),              'label' => 'LinkedIn URL' ),
        'x'                  => array( 'show' => $fs('reg_x'),                     'req' => $fr('reg_x'),                     'label' => 'X / Twitter URL' ),
        'website'            => array( 'show' => $fs('reg_website'),               'req' => $fr('reg_website'),               'label' => 'Personal Website' ),
        'photo'              => array( 'show' => $fs('reg_photo'),                 'req' => $fr('reg_photo'),                 'label' => 'Photo (400x400)' ),
    );
    $guest_fields_json = wp_json_encode( $guest_fields_config );

    $bd_districts_list = function_exists('BatchMate_get_bd_districts') ? BatchMate_get_bd_districts() : array();
    $bd_upazilas_list  = function_exists('BatchMate_get_bd_upazilas') ? BatchMate_get_bd_upazilas() : array();
    $all_countries_list = function_exists('BatchMate_get_countries') ? BatchMate_get_countries() : array();
    $bd_districts_json = wp_json_encode( $bd_districts_list );
    $bd_upazilas_json  = wp_json_encode( $bd_upazilas_list );
    $countries_json    = wp_json_encode( $all_countries_list );

    $event_payment_methods = function_exists('batchmate_get_payment_methods') ? batchmate_get_payment_methods( $event_id ) : array('bKash', 'Nagad', 'Rocket', 'Bank Transfer', 'Cash');
    $event_institutions    = function_exists('batchmate_get_distinct_institutions') ? batchmate_get_distinct_institutions() : array();
    $membership_fee_info   = function_exists('batchmate_get_membership_fee_info') ? batchmate_get_membership_fee_info() : array('active' => false, 'amount' => 0.0, 'label' => '');

    $payment_methods_json  = wp_json_encode( $event_payment_methods );
    $institutions_json     = wp_json_encode( $event_institutions );
    $membership_fee_json   = wp_json_encode( $membership_fee_info );

    $ajax_url = admin_url( 'admin-ajax.php' );
    $nonce    = wp_create_nonce( 'batchmate_event_public_nonce' );
    $reg_link = home_url( '/registration-form/' );
    $no_photo = ROYAL_BATCHMATE_PLUGIN_URL . '/assets/images/noprofile.png';

    ob_start();
    ?>
<div class="BatchMate-ef-wrap" id="BatchMate-ef-wrap-<?php echo $event_id; ?>">

    <!-- Modern Header Banner -->
    <div class="BatchMate-ef-header">
        <div class="BatchMate-ef-header-content">
            <div class="BatchMate-ef-badge-event">Event Registration</div>
            <h3 class="BatchMate-ef-title"><?php echo esc_html( $event->event_name ); ?></h3>
            <div class="BatchMate-ef-meta-pills">
                <span class="BatchMate-ef-pill">
                    <span class="dashicons dashicons-calendar-alt"></span>
                    <?php echo esc_html( date_i18n( 'l, F j, Y', strtotime( $event->event_date ) ) ); ?>
                </span>
                <?php if ( ! empty( $event->location ) ) : ?>
                <span class="BatchMate-ef-pill">
                    <span class="dashicons dashicons-location"></span>
                    <?php echo esc_html( $event->location ); ?>
                </span>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php
    $allow_non_members = intval( $event->allow_non_members );
    $is_direct_only    = ( $allow_non_members === 3 );
    $custom_reg_text   = ! empty( $event->direct_reg_text ) ? $event->direct_reg_text : '';

    if ( empty( $custom_reg_text ) ) {
        if ( $allow_non_members >= 2 ) {
            $custom_reg_text = ( $allow_non_members === 3 ) 
                ? 'Membership &amp; Event Registration' 
                : 'Not a member yet? Register for Membership &amp; Event &rarr;';
        } else {
            $custom_reg_text = 'Not a member? Register as Guest &rarr;';
        }
    }
    ?>

    <?php if ( $allow_non_members === 2 ) : ?>
    <!-- Mode Switcher Tabs (Only when Search + Direct is active) -->
    <div class="BatchMate-ef-switcher" id="BatchMate-ef-switcher-<?php echo $event_id; ?>" style="display:flex; border-bottom:1px solid #e2e8f0; background:#f8fafc;">
        <button type="button" class="BatchMate-ef-switch-tab active" data-tab="search" id="BatchMate-ef-tab-search-<?php echo $event_id; ?>" style="flex:1; padding:13px 18px; border:none; background:#fff; font-weight:700; color:#1e3a8a; border-bottom:3px solid #2563eb; cursor:pointer; font-size:14px; display:inline-flex; align-items:center; justify-content:center; gap:8px; transition:all .2s;">
            <span class="dashicons dashicons-search" style="font-size:16px;"></span> <?php esc_html_e( 'Search Existing Profile', 'royal-batchmate' ); ?>
        </button>
        <button type="button" class="BatchMate-ef-switch-tab" data-tab="direct" id="BatchMate-ef-tab-direct-<?php echo $event_id; ?>" style="flex:1; padding:13px 18px; border:none; background:#f8fafc; font-weight:600; color:#64748b; border-bottom:3px solid transparent; cursor:pointer; font-size:14px; display:inline-flex; align-items:center; justify-content:center; gap:8px; transition:all .2s;">
            <span class="dashicons dashicons-id-alt" style="font-size:16px;"></span> <?php echo esc_html( $custom_reg_text ); ?>
        </button>
    </div>
    <?php endif; ?>

    <!-- Direct Search Box (Hidden if Option 4 Direct Form Only) -->
    <div class="BatchMate-ef-search-box" id="BatchMate-ef-search-wrap-<?php echo $event_id; ?>" <?php if ( $is_direct_only ) echo 'style="display:none;"'; ?>>
        <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:6px;">
            <label class="BatchMate-ef-label" for="BatchMate-ef-search-<?php echo $event_id; ?>">
                &#128269; Search Your Information
            </label>
        </div>
        <p class="BatchMate-ef-hint">Enter your Mobile Number, Member ID, or Name (minimum 3 characters)</p>
        <div class="BatchMate-ef-input-wrap">
            <input type="text"
                   id="BatchMate-ef-search-<?php echo $event_id; ?>"
                   class="BatchMate-ef-input"
                   placeholder="e.g. 01700000000  /  BM-00001  /  Karim"
                   autocomplete="off"
                   maxlength="100">
            <span class="BatchMate-ef-spinner" id="BatchMate-ef-spinner-<?php echo $event_id; ?>"></span>
        </div>
        <div style="display:flex; justify-content:space-between; align-items:center; margin-top:8px; flex-wrap:wrap; gap:6px;">
            <p class="BatchMate-ef-live-hint" id="BatchMate-ef-char-hint-<?php echo $event_id; ?>" style="margin:0;">
                &#9660; Results appear automatically after 3 characters.
            </p>
            <?php if ( $event->allow_non_members ) : ?>
            <button type="button" class="BatchMate-ef-direct-reg-btn" id="BatchMate-ef-direct-reg-btn-<?php echo $event_id; ?>" style="background:none; border:none; color:#2271b1; font-size:13px; font-weight:600; cursor:pointer; text-decoration:underline; padding:0;">
                <?php echo $custom_reg_text; ?>
            </button>
            <?php endif; ?>
        </div>
    </div>

    <div class="BatchMate-ef-result" id="BatchMate-ef-result-<?php echo $event_id; ?>"></div>

</div>

<style>
.BatchMate-ef-wrap{max-width:900px;margin:24px auto;font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Oxygen-Sans,Ubuntu,Cantarell,"Helvetica Neue",sans-serif;background:#fff;border-radius:18px;overflow:hidden;box-shadow:0 12px 32px rgba(15,23,42,0.08),0 2px 6px rgba(15,23,42,0.04);border:1px solid #e2e8f0;}
.BatchMate-ef-header{background:linear-gradient(135deg,#0f172a 0%,#1e3a8a 52%,#2563eb 100%);color:#ffffff!important;padding:28px 32px 24px;position:relative;}
.BatchMate-ef-badge-event{display:inline-block;background:rgba(255,255,255,0.18);color:#ffffff!important;font-size:11px;font-weight:700;letter-spacing:1px;padding:3px 10px;border-radius:20px;margin-bottom:10px;text-transform:uppercase;}
.BatchMate-ef-title{margin:0 0 10px 0!important;font-size:22px!important;font-weight:700!important;color:#ffffff!important;line-height:1.35!important;text-shadow:0 1px 3px rgba(0,0,0,0.3);}
.BatchMate-ef-meta-pills{display:flex;gap:8px;flex-wrap:wrap;}
.BatchMate-ef-pill{display:inline-flex;align-items:center;gap:6px;background:rgba(255,255,255,0.14);backdrop-filter:blur(6px);-webkit-backdrop-filter:blur(6px);border:1px solid rgba(255,255,255,0.22);color:#ffffff!important;font-size:12.5px;font-weight:500;padding:4px 12px;border-radius:20px;}
.BatchMate-ef-pill .dashicons{font-size:14px;width:14px;height:14px;color:#93c5fd!important;vertical-align:text-bottom;}
/* Back button */
.BatchMate-ef-back-btn{background:#fff;border:1.5px solid #cbd5e1;border-radius:8px;padding:5px 14px;font-size:13px;font-weight:600;cursor:pointer;color:#475569;transition:.15s;}
.BatchMate-ef-back-btn:hover{border-color:#2271b1;color:#2271b1;background:#f0f6fc;}
/* Search box */
.BatchMate-ef-search-box{background:#f8fafc;border-top:1px solid #e2e8f0;padding:24px 30px 18px;}
.BatchMate-ef-label{display:block;font-weight:700;margin-bottom:4px;color:#0f172a;font-size:15px;}
.BatchMate-ef-hint{margin:0 0 12px;font-size:13px;color:#64748b;}
.BatchMate-ef-live-hint{margin:7px 0 0;font-size:12px;color:#94a3b8;transition:color .2s;}
.BatchMate-ef-live-hint.typing{color:#2271b1;font-weight:600;}
.BatchMate-ef-input-wrap{position:relative;}
.BatchMate-ef-input{width:100%!important;padding:13px 46px 13px 16px!important;border:2px solid #94a3b8!important;border-radius:10px!important;font-size:15px!important;box-sizing:border-box!important;transition:border .2s,box-shadow .2s;background:#fff!important;font-family:inherit!important;outline:none!important;}
.BatchMate-ef-input:focus{border-color:#2563eb!important;outline:none!important;box-shadow:0 0 0 4px rgba(37,99,235,.12)!important;}
.BatchMate-ef-input.ef-searching{border-color:#2563eb!important;}
.BatchMate-ef-spinner{display:none;position:absolute;right:14px;top:50%;transform:translateY(-50%);width:18px;height:18px;border:2.5px solid #dee2e6;border-top-color:#2563eb;border-radius:50%;animation:efSpin .65s linear infinite;}
.BatchMate-ef-spinner.active{display:block;}
@keyframes efSpin{to{transform:translateY(-50%) rotate(360deg)}}
.BatchMate-ef-result{margin-top:16px;padding:0 24px 24px;}
.ef-anim{animation:efFade .28s ease;}
@keyframes efFade{from{opacity:0;transform:translateY(8px)}to{opacity:1;transform:none}}
.BatchMate-ef-notice{padding:15px 20px;border-radius:10px;font-size:14px;font-weight:500;line-height:1.6;}
.BatchMate-ef-notice--closed{background:#fff3cd;border:1px solid #ffc107;color:#856404;}
.BatchMate-ef-notice--error{background:#fff5f5;border:1px solid #fed7d7;color:#c53030;}
.BatchMate-ef-notice--warning{background:#fffbeb;border:1px solid #f6d860;color:#92400e;}
.BatchMate-ef-notice--success{background:#f0fff4;border:1px solid #9ae6b4;color:#276749;}
.BatchMate-ef-card{background:#fff;border:1.5px solid #e2e8f0;border-radius:14px;overflow:hidden;box-shadow:0 4px 16px rgba(0,0,0,.06);}
.BatchMate-ef-card-header{display:flex;align-items:center;gap:14px;padding:18px 22px;background:#f8fafc;border-bottom:1px solid #e2e8f0;}
.BatchMate-ef-card-photo{width:66px;height:66px;border-radius:50%;object-fit:cover;border:3px solid #2563eb;flex-shrink:0;}
.BatchMate-ef-mid{display:inline-block;background:#e7f0fa;color:#2271b1;padding:2px 10px;border-radius:20px;font-size:12px;font-weight:600;margin-bottom:5px;}
.BatchMate-ef-card-info h4{margin:0 0 3px;font-size:17px;font-weight:700;color:#0f172a;}
.BatchMate-ef-card-info p{margin:0;font-size:13px;color:#64748b;}
.BatchMate-ef-card-body{padding:20px 22px;}
.BatchMate-ef-pay-title{font-size:15px;font-weight:700;color:#0f172a;margin:18px 0 14px;padding-bottom:8px;border-bottom:1.5px solid #e2e8f0;display:flex;align-items:center;gap:8px;}
.BatchMate-ef-pay-group{margin-bottom:14px;}
.BatchMate-ef-pay-group label{display:block;font-weight:600;margin-bottom:5px;font-size:13px;color:#334155;}
.BatchMate-ef-pay-group input,.BatchMate-ef-pay-group select,.BatchMate-ef-pay-group textarea{width:100%;padding:10px 13px;border:1.5px solid #cbd5e1;border-radius:8px;font-size:14px;box-sizing:border-box;background:#fff;font-family:inherit;transition:.2s;}
.BatchMate-ef-pay-group input:focus,.BatchMate-ef-pay-group select:focus,.BatchMate-ef-pay-group textarea:focus{border-color:#2563eb;outline:none;box-shadow:0 0 0 3px rgba(37,99,235,.12);}

/* Responsive Multi-column Grids (2 or 3 per row) */
.BatchMate-ef-grid-2{display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:14px;}
.BatchMate-ef-grid-3{display:grid;grid-template-columns:1fr 1fr 1fr;gap:14px;margin-bottom:14px;}
.BatchMate-ef-grid-1{display:grid;grid-template-columns:1fr;gap:14px;margin-bottom:14px;}
@media (max-width:768px){
  .BatchMate-ef-grid-2,.BatchMate-ef-grid-3{grid-template-columns:1fr;gap:12px;}
}

/* Beautiful Gift Choice Card Selector */
.ef-gift-selector-grid{display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:12px;box-sizing:border-box;}
@media (max-width:580px){.ef-gift-selector-grid{grid-template-columns:1fr;}}
.ef-gift-card{position:relative;display:flex!important;flex-direction:row!important;align-items:center!important;gap:14px!important;padding:14px 18px!important;border:2px solid #e2e8f0!important;border-radius:12px!important;background:#ffffff!important;cursor:pointer!important;transition:all .2s ease!important;user-select:none!important;box-sizing:border-box!important;text-align:left!important;}
.ef-gift-card:hover{border-color:#93c5fd!important;background:#f8fafc!important;}
.ef-gift-card.selected{border-color:#2563eb!important;background:#eff6ff!important;box-shadow:0 4px 12px rgba(37,99,235,0.12)!important;}
.ef-gift-card input[type="radio"]{display:none!important;position:absolute!important;opacity:0!important;visibility:hidden!important;width:0!important;height:0!important;pointer-events:none!important;}
.ef-gift-icon-wrap{font-size:26px!important;line-height:1!important;display:flex!important;align-items:center!important;justify-content:center!important;width:44px!important;height:44px!important;border-radius:10px!important;background:#f1f5f9!important;flex-shrink:0!important;}
.ef-gift-card.selected .ef-gift-icon-wrap{background:#dbeafe!important;}
.ef-gift-text{flex:1!important;min-width:0!important;}
.ef-gift-text strong{display:block!important;font-size:14px!important;color:#0f172a!important;margin-bottom:2px!important;white-space:nowrap!important;}
.ef-gift-text span{font-size:11.5px!important;color:#64748b!important;display:block!important;}
.ef-gift-check{width:22px!important;height:22px!important;border-radius:50%!important;border:1.5px solid #cbd5e1!important;display:flex!important;align-items:center!important;justify-content:center!important;font-size:12px!important;font-weight:700!important;color:transparent!important;transition:all .2s ease!important;flex-shrink:0!important;}
.ef-gift-card.selected .ef-gift-check{border-color:#2563eb!important;background:#2563eb!important;color:#ffffff!important;}

/* Photo Upload Box with Instant Preview */
.ef-photo-card{display:flex!important;flex-direction:row!important;align-items:center!important;gap:18px!important;background:#f8fafc!important;border:1.5px dashed #cbd5e1!important;border-radius:12px!important;padding:14px 18px!important;transition:border-color .2s!important;box-sizing:border-box!important;}
.ef-photo-card:hover{border-color:#2563eb!important;}
.ef-photo-thumb{width:80px!important;height:80px!important;border-radius:10px!important;overflow:hidden!important;background:#e2e8f0!important;flex-shrink:0!important;border:2px solid #ffffff!important;box-shadow:0 2px 8px rgba(0,0,0,0.1)!important;position:relative!important;}
.ef-photo-thumb img{width:100%!important;height:100%!important;object-fit:cover!important;display:block!important;}
.ef-photo-actions{flex:1!important;min-width:0!important;}
.ef-photo-btn{display:inline-flex!important;align-items:center!important;gap:6px!important;background:#2563eb!important;color:#ffffff!important;font-size:13px!important;font-weight:600!important;padding:8px 18px!important;border-radius:8px!important;cursor:pointer!important;transition:background .2s!important;line-height:1.4!important;}
.ef-photo-btn:hover{background:#1d4ed8!important;}
.ef-photo-filename{font-size:12px!important;color:#334155!important;font-weight:600!important;margin-top:6px!important;word-break:break-all!important;}
.ef-photo-help{font-size:11.5px!important;color:#64748b!important;margin-top:2px!important;}

.BatchMate-ef-submit-row{margin-top:20px;text-align:right;}
.BatchMate-ef-submit-btn{display:inline-flex;align-items:center;gap:6px;padding:12px 28px;border:none;border-radius:9px;font-size:14px;font-weight:700;cursor:pointer;background:#059669;color:#fff;transition:.2s;font-family:inherit;box-shadow:0 2px 4px rgba(5,150,105,0.2);}
.BatchMate-ef-submit-btn:hover{background:#047857;transform:translateY(-1px);box-shadow:0 4px 8px rgba(5,150,105,0.3);}
.BatchMate-ef-submit-btn:disabled{opacity:.6;cursor:not-allowed;transform:none;}
.BatchMate-ef-reg-link{color:#2563eb;font-weight:700;text-decoration:underline;}
/* Guest option selector */
.BatchMate-ef-opt-fee{font-size:12px;color:#64748b;font-weight:400;}
.BatchMate-ef-opt-ctrl{display:flex;align-items:center;gap:8px;margin-top:6px;}
.BatchMate-ef-qty-btn{width:32px;height:32px;border:1.5px solid #cbd5e1;border-radius:6px;background:#fff;font-size:18px;font-weight:700;cursor:pointer;line-height:1;display:flex;align-items:center;justify-content:center;transition:.15s;color:#0f172a;}
.BatchMate-ef-qty-btn:hover{border-color:#2563eb;background:#f0f6fc;color:#2563eb;}
.BatchMate-ef-qty-inp{width:54px;text-align:center;padding:6px 4px;border:1.5px solid #e2e8f0;border-radius:6px;font-size:15px;font-weight:700;background:#f8fafc;}
.BatchMate-ef-opt-sub{font-size:13px;font-weight:600;color:#059669;min-width:80px;}
/* Member selection list */
.BatchMate-ef-member-list{background:#fff;border:1.5px solid #e2e8f0;border-radius:14px;overflow:hidden;box-shadow:0 4px 16px rgba(0,0,0,.06);}
.BatchMate-ef-member-list-title{margin:0;padding:16px 20px 12px;font-size:14px;font-weight:700;color:#0f172a;border-bottom:1px solid #e2e8f0;background:#f8fafc;}
.BatchMate-ef-member-list-items{display:flex;flex-direction:column;}
.BatchMate-ef-ml-item{display:flex;align-items:center;gap:12px;padding:13px 18px;border-bottom:1px solid #f1f5f9;cursor:pointer;transition:background .15s;position:relative;}
.BatchMate-ef-ml-item:last-child{border-bottom:none;}
.BatchMate-ef-ml-item:hover{background:#f0f6fc;}
.BatchMate-ef-ml-item:hover .BatchMate-ef-ml-arrow{color:#2563eb;transform:translateX(3px);}
.BatchMate-ef-ml-photo{width:48px;height:48px;border-radius:50%;object-fit:cover;border:2px solid #e2e8f0;flex-shrink:0;}
.BatchMate-ef-ml-info{flex:1;display:flex;flex-direction:column;gap:2px;min-width:0;}
.BatchMate-ef-ml-name{font-size:15px;color:#0f172a;font-weight:700;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
.BatchMate-ef-ml-meta{font-size:12px;color:#64748b;}
.BatchMate-ef-ml-status{font-size:11px;font-weight:700;padding:3px 10px;border-radius:12px;flex-shrink:0;}
.BatchMate-ef-ml-arrow{font-size:22px;color:#cbd5e1;flex-shrink:0;transition:all .15s;}
/* Select2 & placeholder styling in event registration */
.BatchMate-ef-wrap ::placeholder,
.BatchMate-ef-wrap input::placeholder,
.BatchMate-ef-wrap textarea::placeholder {
    color: #94a3b8 !important;
    opacity: 0.85 !important;
    font-weight: 400 !important;
}
.BatchMate-ef-wrap .select2-container .select2-selection--single {
    height: 42px !important;
    border: 1.5px solid #cbd5e1 !important;
    border-radius: 8px !important;
    display: flex !important;
    align-items: center !important;
}
.BatchMate-ef-wrap .select2-container--default .select2-selection--single .select2-selection__rendered {
    line-height: 40px !important;
    padding-left: 12px !important;
    color: #0f172a !important;
}
.BatchMate-ef-wrap .select2-container--default .select2-selection--single .select2-selection__arrow {
    height: 40px !important;
    right: 8px !important;
}
</style>


<script>
(function($){
    var evtId   = <?php echo $event_id; ?>;
    var nonce   = '<?php echo $nonce; ?>';
    var ajaxUrl = '<?php echo esc_js( $ajax_url ); ?>';
    var noPhoto = '<?php echo esc_js( $no_photo ); ?>';
    var regLink = '<?php echo esc_js( $reg_link ); ?>';
    var allowNonMembers = <?php echo intval( $event->allow_non_members ); ?>;
    var hasTshirt = <?php echo intval( $event->has_tshirt ); ?>; // T-Shirt feature flag
    var hasSaree  = <?php echo intval( $event->has_saree ?? 0 ); ?>; // Saree for females flag
    var guestFieldsCfg = <?php echo $guest_fields_json; ?>;
    var paymentMethods = <?php echo $payment_methods_json; ?>;
    var distinctInstitutions = <?php echo $institutions_json; ?>;
    var membershipFeeInfo = <?php echo $membership_fee_json; ?>;
    var _memFeeActive = !!membershipFeeInfo.active;
    var _memFeeAmount = parseFloat(membershipFeeInfo.amount || 0);
    var _memFeeLabel  = membershipFeeInfo.label || 'Membership Fee';
    var _bmDistricts  = <?php echo $bd_districts_json; ?>;
    var _bmUpazilas   = <?php echo $bd_upazilas_json; ?>;
    var _bmCountries  = <?php echo $countries_json; ?>;
    var _isCurrentGuest = false;
    var customDirectRegTitle = <?php echo wp_json_encode( ! empty( $event->direct_reg_text ) ? $event->direct_reg_text : '' ); ?>;

    var $actionBtns   = $('#BatchMate-ef-action-btns-' + evtId);
    var $searchWrap   = $('#BatchMate-ef-search-wrap-' + evtId);
    var $input        = $('#BatchMate-ef-search-' + evtId);
    var $spinner      = $('#BatchMate-ef-spinner-' + evtId);
    var $result       = $('#BatchMate-ef-result-' + evtId);
    var $hint         = $('#BatchMate-ef-char-hint-' + evtId);

    // Helper: reset search view
    function resetSearch() {
        $result.html('');
        $input.val('').focus();
        lastQ = '';
        $hint.removeClass('typing').html('&#9660; Results appear automatically after 3 characters.');
    }

    function switchToDirect() {
        $('#BatchMate-ef-tab-search-' + evtId).removeClass('active').css({ 'background':'#f8fafc', 'color':'#64748b', 'border-bottom':'3px solid transparent' });
        $('#BatchMate-ef-tab-direct-' + evtId).addClass('active').css({ 'background':'#fff', 'color':'#1e3a8a', 'border-bottom':'3px solid #2563eb' });
        $searchWrap.slideUp(180);
        renderMemberForm({
            member: { is_guest: true },
            already: false
        });
    }

    function switchToSearch() {
        $('#BatchMate-ef-tab-direct-' + evtId).removeClass('active').css({ 'background':'#f8fafc', 'color':'#64748b', 'border-bottom':'3px solid transparent' });
        $('#BatchMate-ef-tab-search-' + evtId).addClass('active').css({ 'background':'#fff', 'color':'#1e3a8a', 'border-bottom':'3px solid #2563eb' });
        $result.html('');
        $searchWrap.slideDown(180);
        $input.val('').focus();
        lastQ = '';
        $hint.removeClass('typing').html('&#9660; Results appear automatically after 3 characters.');
    }

    $('#BatchMate-ef-tab-direct-' + evtId).on('click', switchToDirect);
    $('#BatchMate-ef-tab-search-' + evtId).on('click', switchToSearch);
    $('#BatchMate-ef-direct-reg-btn-' + evtId).on('click', switchToDirect);

    // Back button -> return to search
    $(document).on('click', '.BatchMate-ef-guest-back-btn, #BatchMate-ef-back-' + evtId, function() {
        if (allowNonMembers === 2) {
            switchToSearch();
        } else if (allowNonMembers !== 3) {
            resetSearch();
        }
    });
    var timer     = null;
    var lastQ     = '';
    var activeXhr = null;
    var _evtOptions = []; // event guest options cache
    var _evtMemberFee = 0;

    // Load event options on init; for Option 3 & 4 also trigger direct form render AFTER options load
    $.ajax({ url: ajaxUrl, type: 'POST',
        data: { action: 'BatchMate_get_event_options', event_id: evtId }
    }).done(function(res){
        if (res.success && res.data && res.data.options) {
            _evtOptions      = res.data.options;
            _evtMemberFee    = parseFloat(res.data.member_fee) || 0;
        }
        // Option 4: Direct Form Only — render AFTER options are loaded so guest options appear
        if (allowNonMembers === 3) {
            $searchWrap.hide();
            renderMemberForm({ member: { is_guest: true }, already: false });
        }
    });

    /* -- Live input handler -- */
    $input.on('input', function() {
        var q = $.trim($(this).val());

        clearTimeout(timer);
        if (activeXhr) { activeXhr.abort(); activeXhr = null; }

        /* Less than 3 chars ? clear */
        if (q.length < 3) {
            $spinner.removeClass('active');
            $input.removeClass('ef-searching');
            $result.html('');
            lastQ = '';
            if (q.length === 0) {
                $hint.removeClass('typing').html('&#9660; Results appear automatically after 3 characters.');
            } else {
                $hint.addClass('typing').text((3 - q.length) + ' more character' + ((3 - q.length) > 1 ? 's' : '') + ' to search...');
            }
            return;
        }

        /* Same query ? skip */
        if (q === lastQ) return;

        /* Show loading state */
        $hint.addClass('typing').text('Searching...');
        $spinner.addClass('active');
        $input.addClass('ef-searching');

        /* Debounce 350ms */
        timer = setTimeout(function() {
            lastQ = q;
            activeXhr = $.ajax({
                url: ajaxUrl, type: 'POST',
                data: { action: 'BatchMate_search_member_for_event', query: q, event_id: evtId, _wpnonce: nonce }
            }).done(function(res) {
                $spinner.removeClass('active');
                $input.removeClass('ef-searching');
                $hint.removeClass('typing').text('');
                renderResult(res, q);
            }).fail(function(jqXHR, textStatus, errorThrown) {
                if (jqXHR.statusText === 'abort') return;
                $spinner.removeClass('active');
                $input.removeClass('ef-searching');
                $hint.removeClass('typing').text('');
                var errMsg = 'Connection error. Please try again.';
                if (jqXHR.responseJSON && jqXHR.responseJSON.data) {
                    errMsg = jqXHR.responseJSON.data;
                } else if (jqXHR.status === 403 || jqXHR.status === 400) {
                    errMsg = 'Security check or session expired. Please refresh the page.';
                }
                $result.html(mkNotice('error', errMsg));
            });
        }, 350);
    });

    /* -- Helpers -- */
    function mkNotice(type, msg) {
        return '<div class="ef-anim BatchMate-ef-notice BatchMate-ef-notice--' + type + '">' + msg + '</div>';
    }

    
    /* ---- Build guest form HTML from guestFieldsCfg ---- */
    function buildGuestFormHtml(currentGender) {
        var html = '<div class="BatchMate-ef-pay-title">&#128100; Personal Information</div>';
        var fields = guestFieldsCfg;
        var req = function(r){ return r ? ' <span style="color:#c53030">*</span>' : ''; };

        // Row 1: Name, Name Bangla (if shown), Mobile (always)
        var nameBnShow = fields.name_bn && fields.name_bn.show;
        html += '<div class="' + (nameBnShow ? 'BatchMate-ef-grid-3' : 'BatchMate-ef-grid-2') + '">';
        html += '<div class="BatchMate-ef-pay-group" style="margin-bottom:0;"><label>Name <span style="color:#c53030">*</span></label><input type="text" id="ef-guest-name" placeholder="Full Name"></div>';
        if (nameBnShow) {
            html += '<div class="BatchMate-ef-pay-group" style="margin-bottom:0;"><label>Name (Bangla)' + req(fields.name_bn.req) + '</label><input type="text" id="ef-guest-name-bn" placeholder="বাংলা নাম"></div>';
        }
        html += '<div class="BatchMate-ef-pay-group" style="margin-bottom:0;"><label>Mobile <span style="color:#c53030">*</span></label><input type="text" id="ef-guest-cell" inputmode="numeric" placeholder="01XXXXXXXXX"></div>';
        html += '</div>';

        // Row 2: Email, Gender, Blood Group
        var emailShow  = fields.email       && fields.email.show;
        var genderShow = fields.gender      && fields.gender.show;
        var bloodShow  = fields.blood_group && fields.blood_group.show;
        var r2Count = (emailShow ? 1 : 0) + (genderShow ? 1 : 0) + (bloodShow ? 1 : 0);
        if (r2Count > 0) {
            var r2Class = r2Count === 3 ? 'BatchMate-ef-grid-3' : (r2Count === 2 ? 'BatchMate-ef-grid-2' : 'BatchMate-ef-grid-1');
            html += '<div class="' + r2Class + '">';
            if (emailShow) {
                html += '<div class="BatchMate-ef-pay-group" style="margin-bottom:0;"><label>' + (fields.email.req ? ('Email' + req(true)) : 'Email (Optional)') + '</label><input type="email" id="ef-guest-email" placeholder="example@email.com"></div>';
            }
            if (genderShow) {
                var isFem = (currentGender === 'Female');
                html += '<div class="BatchMate-ef-pay-group" style="margin-bottom:0;"><label>Gender' + req(fields.gender.req) + '</label>' +
                    '<select id="ef-guest-gender">' +
                    '<option value="Male"' + (!isFem ? ' selected' : '') + '>Male</option>' +
                    '<option value="Female"' + (isFem ? ' selected' : '') + '>Female</option>' +
                    '</select></div>';
            }
            if (bloodShow) {
                html += '<div class="BatchMate-ef-pay-group" style="margin-bottom:0;"><label>Blood Group' + req(fields.blood_group.req) + '</label>' +
                    '<select id="ef-guest-blood"><option value="">Select...</option>' +
                    '<option>A+</option><option>A-</option><option>B+</option><option>B-</option>' +
                    '<option>O+</option><option>O-</option><option>AB+</option><option>AB-</option>' +
                    '</select></div>';
            }
            html += '</div>';
        }

        // Row 3: Date of Birth, Religion, Land Phone
        var dobShow      = fields.dob        && fields.dob.show;
        var religionShow = fields.religion   && fields.religion.show;
        var landShow     = fields.land_phone && fields.land_phone.show;
        var r3Count = (dobShow ? 1 : 0) + (religionShow ? 1 : 0) + (landShow ? 1 : 0);
        if (r3Count > 0) {
            var r3Class = r3Count === 3 ? 'BatchMate-ef-grid-3' : (r3Count === 2 ? 'BatchMate-ef-grid-2' : 'BatchMate-ef-grid-1');
            html += '<div class="' + r3Class + '">';
            if (dobShow) {
                html += '<div class="BatchMate-ef-pay-group" style="margin-bottom:0;"><label>Date of Birth' + req(fields.dob.req) + '</label><input type="date" id="ef-guest-dob"></div>';
            }
            if (religionShow) {
                html += '<div class="BatchMate-ef-pay-group" style="margin-bottom:0;"><label>Religion' + req(fields.religion.req) + '</label>' +
                    '<select id="ef-guest-religion"><option value="">Select Religion...</option>' +
                    '<option>Islam</option><option>Hinduism</option><option>Buddhism</option><option>Christianity</option><option>Others</option>' +
                    '</select></div>';
            }
            if (landShow) {
                html += '<div class="BatchMate-ef-pay-group" style="margin-bottom:0;"><label>Alternative Number' + req(fields.land_phone.req) + '</label><input type="text" id="ef-guest-land-phone" placeholder="Alternative Number"></div>';
            }
            html += '</div>';
        }

        // Academic Information (only if any academic field is shown)
        var boardShow = fields.board       && fields.board.show;
        var rollShow  = fields.roll_no     && fields.roll_no.show;
        var regNoShow = fields.reg_no      && fields.reg_no.show;
        var groupShow = fields.group       && fields.group.show;
        var instShow  = fields.institution && fields.institution.show;

        if (boardShow || rollShow || regNoShow || groupShow || instShow) {
            html += '<div class="BatchMate-ef-pay-title" style="margin-top:16px;">&#127891; Academic Information</div>';

            var acadRow1Count = (boardShow ? 1 : 0) + (rollShow ? 1 : 0) + (regNoShow ? 1 : 0);
            if (acadRow1Count > 0) {
                var a1Class = acadRow1Count === 3 ? 'BatchMate-ef-grid-3' : (acadRow1Count === 2 ? 'BatchMate-ef-grid-2' : 'BatchMate-ef-grid-1');
                html += '<div class="' + a1Class + '">';
                if (boardShow) {
                    html += '<div class="BatchMate-ef-pay-group" style="margin-bottom:0;"><label>Board' + req(fields.board.req) + '</label>' +
                        '<select id="ef-guest-board"><option value="">Select Board...</option>' +
                        '<option value="dhaka">Dhaka</option><option value="barisal">Barisal</option><option value="chittagong">Chittagong</option>' +
                        '<option value="comilla">Comilla</option><option value="dinajpur">Dinajpur</option><option value="jessore">Jessore</option>' +
                        '<option value="mymensingh">Mymensingh</option><option value="rajshahi">Rajshahi</option><option value="sylhet">Sylhet</option>' +
                        '<option value="madrasah">Madrasah</option><option value="tec">Technical</option>' +
                        '</select></div>';
                }
                if (rollShow) {
                    html += '<div class="BatchMate-ef-pay-group" style="margin-bottom:0;"><label>Roll No' + req(fields.roll_no.req) + '</label><input type="text" id="ef-guest-roll" placeholder="SSC Roll"></div>';
                }
                if (regNoShow) {
                    html += '<div class="BatchMate-ef-pay-group" style="margin-bottom:0;"><label>Reg No' + req(fields.reg_no.req) + '</label><input type="text" id="ef-guest-regno" placeholder="Registration No"></div>';
                }
                html += '</div>';
            }

            var acadRow2Count = (groupShow ? 1 : 0) + (instShow ? 1 : 0);
            if (acadRow2Count > 0) {
                var a2Class = acadRow2Count === 2 ? 'BatchMate-ef-grid-2' : 'BatchMate-ef-grid-1';
                html += '<div class="' + a2Class + '">';
                if (groupShow) {
                    html += '<div class="BatchMate-ef-pay-group" style="margin-bottom:0;"><label>Group' + req(fields.group.req) + '</label>' +
                        '<select id="ef-guest-group"><option value="">Select Group...</option>' +
                        '<option>Science</option><option>Humanities</option><option>Commerce</option><option>Vocational</option><option>Others</option>' +
                        '</select></div>';
                }
                if (instShow) {
                    var instDatalistOptions = '';
                    if (distinctInstitutions && distinctInstitutions.length) {
                        distinctInstitutions.forEach(function(inst){
                            instDatalistOptions += '<option value="' + inst.replace(/"/g, '&quot;') + '"></option>';
                        });
                    }
                    html += '<div class="BatchMate-ef-pay-group" style="margin-bottom:0;"><label>' + (fields.institution.label || 'Institution') + req(fields.institution.req) + '</label>' +
                        '<input type="text" id="ef-guest-institution" list="ef-institutions-list" placeholder="School / College Name" autocomplete="off">' +
                        '<datalist id="ef-institutions-list">' + instDatalistOptions + '</datalist>' +
                        '</div>';
                }
                html += '</div>';
            }
        }

        // Address Information (if shown)
        var presAddrShow    = fields.present_address    && fields.present_address.show;
        var presCountryShow = fields.present_country    && fields.present_country.show;
        var presDistShow    = fields.present_district   && fields.present_district.show;
        var presUpzShow     = fields.present_upazila    && fields.present_upazila.show;
        var permAddrShow    = fields.permanent_address  && fields.permanent_address.show;
        var permCountryShow = fields.permanent_country  && fields.permanent_country.show;
        var permDistShow    = fields.permanent_district && fields.permanent_district.show;
        var permUpzShow     = fields.permanent_upazila  && fields.permanent_upazila.show;

        if (presAddrShow || presCountryShow || presDistShow || presUpzShow || permAddrShow || permCountryShow || permDistShow || permUpzShow) {
            html += '<div class="BatchMate-ef-pay-title" style="margin-top:16px;">&#128205; Address Information</div>';
            if (presAddrShow || permAddrShow) {
                var addrClass = (presAddrShow && permAddrShow) ? 'BatchMate-ef-grid-2' : 'BatchMate-ef-grid-1';
                html += '<div class="' + addrClass + '">';
                if (presAddrShow) {
                    html += '<div class="BatchMate-ef-pay-group" style="margin-bottom:0;"><label>Present Address' + req(fields.present_address.req) + '</label><input type="text" id="ef-guest-pres-addr" placeholder="House, Road, Area"></div>';
                }
                if (permAddrShow) {
                    html += '<div class="BatchMate-ef-pay-group" style="margin-bottom:0;"><label>Permanent Address' + req(fields.permanent_address.req) + '</label><input type="text" id="ef-guest-perm-addr" placeholder="Village, Area"></div>';
                }
                html += '</div>';
            }

            // Present Location (Country, District, Upazila)
            var presLocCount = (presCountryShow ? 1 : 0) + (presDistShow ? 1 : 0) + (presUpzShow ? 1 : 0);
            if (presLocCount > 0) {
                var presLocClass = presLocCount === 3 ? 'BatchMate-ef-grid-3' : (presLocCount === 2 ? 'BatchMate-ef-grid-2' : 'BatchMate-ef-grid-1');
                html += '<div class="' + presLocClass + '" id="wrap-ef-pres-loc" style="margin-top:10px;">';
                if (presCountryShow) {
                    var cOpts = '<option value="">Select Country...</option>';
                    if (_bmCountries && _bmCountries.length) {
                        _bmCountries.forEach(function(c){
                            cOpts += '<option value="' + c + '"' + (c === 'Bangladesh' ? ' selected' : '') + '>' + c + '</option>';
                        });
                    } else {
                        cOpts += '<option value="Bangladesh" selected>Bangladesh</option>';
                    }
                    html += '<div class="BatchMate-ef-pay-group" id="wrap-ef-guest-pres-country" style="margin-bottom:0;"><label>Present Country' + req(fields.present_country.req) + '</label><select id="ef-guest-pres-country" class="ef-select2">' + cOpts + '</select></div>';
                }
                if (presDistShow) {
                    var dOpts = '<option value="">Select District...</option>';
                    if (_bmDistricts && _bmDistricts.length) {
                        _bmDistricts.forEach(function(d){
                            dOpts += '<option value="' + d + '">' + d + '</option>';
                        });
                    }
                    html += '<div class="BatchMate-ef-pay-group" id="wrap-ef-guest-pres-dist" style="margin-bottom:0;"><label>' + (fields.present_district.label || 'Present District') + req(fields.present_district.req) + '</label><select id="ef-guest-pres-dist" class="ef-select2">' + dOpts + '</select></div>';
                }
                if (presUpzShow) {
                    html += '<div class="BatchMate-ef-pay-group" id="wrap-ef-guest-pres-upazila" style="margin-bottom:0;"><label>' + (fields.present_upazila.label || 'Present Thana / Upazila') + req(fields.present_upazila.req) + '</label><select id="ef-guest-pres-upazila" class="ef-select2"><option value="">Select Thana / Upazila...</option></select></div>';
                }
                html += '</div>';
            }

            // Permanent Location (Country, District, Upazila)
            var permLocCount = (permCountryShow ? 1 : 0) + (permDistShow ? 1 : 0) + (permUpzShow ? 1 : 0);
            if (permLocCount > 0) {
                var permLocClass = permLocCount === 3 ? 'BatchMate-ef-grid-3' : (permLocCount === 2 ? 'BatchMate-ef-grid-2' : 'BatchMate-ef-grid-1');
                html += '<div class="' + permLocClass + '" id="wrap-ef-perm-loc" style="margin-top:10px;">';
                if (permCountryShow) {
                    var cOpts2 = '<option value="">Select Country...</option>';
                    if (_bmCountries && _bmCountries.length) {
                        _bmCountries.forEach(function(c){
                            cOpts2 += '<option value="' + c + '"' + (c === 'Bangladesh' ? ' selected' : '') + '>' + c + '</option>';
                        });
                    } else {
                        cOpts2 += '<option value="Bangladesh" selected>Bangladesh</option>';
                    }
                    html += '<div class="BatchMate-ef-pay-group" id="wrap-ef-guest-perm-country" style="margin-bottom:0;"><label>Permanent Country' + req(fields.permanent_country.req) + '</label><select id="ef-guest-perm-country" class="ef-select2">' + cOpts2 + '</select></div>';
                }
                if (permDistShow) {
                    var dOpts2 = '<option value="">Select District...</option>';
                    if (_bmDistricts && _bmDistricts.length) {
                        _bmDistricts.forEach(function(d){
                            dOpts2 += '<option value="' + d + '">' + d + '</option>';
                        });
                    }
                    html += '<div class="BatchMate-ef-pay-group" id="wrap-ef-guest-perm-dist" style="margin-bottom:0;"><label>' + (fields.permanent_district.label || 'Permanent District') + req(fields.permanent_district.req) + '</label><select id="ef-guest-perm-dist" class="ef-select2">' + dOpts2 + '</select></div>';
                }
                if (permUpzShow) {
                    html += '<div class="BatchMate-ef-pay-group" id="wrap-ef-guest-perm-upazila" style="margin-bottom:0;"><label>' + (fields.permanent_upazila.label || 'Permanent Thana / Upazila') + req(fields.permanent_upazila.req) + '</label><select id="ef-guest-perm-upazila" class="ef-select2"><option value="">Select Thana / Upazila...</option></select></div>';
                }
                html += '</div>';
            }
        }

        // Professional Information (if any field shown)
        var orgShow     = fields.organization           && fields.organization.show;
        var posShow     = fields.position               && fields.position.show;
        var indShow     = fields.industry               && fields.industry.show;
        var expShow     = fields.work_experience        && fields.work_experience.show;
        var empAddrShow = fields.employment_address     && fields.employment_address.show;
        var prevOrgShow = fields.previous_organizations && fields.previous_organizations.show;
        var aboutShow   = fields.about_me               && fields.about_me.show;
        var skillsShow  = fields.skills                 && fields.skills.show;

        if (orgShow || posShow || indShow || expShow || empAddrShow || prevOrgShow || aboutShow || skillsShow) {
            html += '<div class="BatchMate-ef-pay-title" style="margin-top:16px;">&#128188; Professional Information</div>';
            if (orgShow || posShow) {
                var p1Class = (orgShow && posShow) ? 'BatchMate-ef-grid-2' : 'BatchMate-ef-grid-1';
                html += '<div class="' + p1Class + '">';
                if (orgShow) html += '<div class="BatchMate-ef-pay-group" style="margin-bottom:0;"><label>Organization' + req(fields.organization.req) + '</label><input type="text" id="ef-guest-org" placeholder="Current Workplace"></div>';
                if (posShow) html += '<div class="BatchMate-ef-pay-group" style="margin-bottom:0;"><label>Position / Designation' + req(fields.position.req) + '</label><input type="text" id="ef-guest-pos" placeholder="e.g. Manager, Officer"></div>';
                html += '</div>';
            }
            if (indShow || expShow) {
                var p2Class = (indShow && expShow) ? 'BatchMate-ef-grid-2' : 'BatchMate-ef-grid-1';
                html += '<div class="' + p2Class + '">';
                if (indShow) html += '<div class="BatchMate-ef-pay-group" style="margin-bottom:0;"><label>Industry' + req(fields.industry.req) + '</label><input type="text" id="ef-guest-ind" placeholder="e.g. IT, Textile, Banking"></div>';
                if (expShow) html += '<div class="BatchMate-ef-pay-group" style="margin-bottom:0;"><label>Work Experience' + req(fields.work_experience.req) + '</label><input type="text" id="ef-guest-exp" placeholder="e.g. 5 Years"></div>';
                html += '</div>';
            }
            if (empAddrShow || prevOrgShow) {
                var p3Class = (empAddrShow && prevOrgShow) ? 'BatchMate-ef-grid-2' : 'BatchMate-ef-grid-1';
                html += '<div class="' + p3Class + '">';
                if (empAddrShow) html += '<div class="BatchMate-ef-pay-group" style="margin-bottom:0;"><label>Organization Address' + req(fields.employment_address.req) + '</label><input type="text" id="ef-guest-emp-addr" placeholder="Workplace Address"></div>';
                if (prevOrgShow) html += '<div class="BatchMate-ef-pay-group" style="margin-bottom:0;"><label>Previous Organizations' + req(fields.previous_organizations.req) + '</label><input type="text" id="ef-guest-prev-org" placeholder="Past companies"></div>';
                html += '</div>';
            }
            if (aboutShow || skillsShow) {
                var p4Class = (aboutShow && skillsShow) ? 'BatchMate-ef-grid-2' : 'BatchMate-ef-grid-1';
                html += '<div class="' + p4Class + '">';
                if (aboutShow) html += '<div class="BatchMate-ef-pay-group" style="margin-bottom:0;"><label>About Me' + req(fields.about_me.req) + '</label><input type="text" id="ef-guest-about" placeholder="Brief bio"></div>';
                if (skillsShow) html += '<div class="BatchMate-ef-pay-group" style="margin-bottom:0;"><label>Skills' + req(fields.skills.req) + '</label><input type="text" id="ef-guest-skills" placeholder="e.g. Management, Public Relations"></div>';
                html += '</div>';
            }
        }

        // Social & Web Links (if any field shown)
        var fbShow  = fields.facebook && fields.facebook.show;
        var liShow  = fields.linkedin && fields.linkedin.show;
        var xShow   = fields.x        && fields.x.show;
        var webShow = fields.website  && fields.website.show;

        if (fbShow || liShow || xShow || webShow) {
            html += '<div class="BatchMate-ef-pay-title" style="margin-top:16px;">&#127760; Social &amp; Web Links</div>';
            if (fbShow || liShow) {
                var s1Class = (fbShow && liShow) ? 'BatchMate-ef-grid-2' : 'BatchMate-ef-grid-1';
                html += '<div class="' + s1Class + '">';
                if (fbShow) html += '<div class="BatchMate-ef-pay-group" style="margin-bottom:0;"><label>Facebook URL' + req(fields.facebook.req) + '</label><input type="url" id="ef-guest-fb" placeholder="https://facebook.com/username"></div>';
                if (liShow) html += '<div class="BatchMate-ef-pay-group" style="margin-bottom:0;"><label>LinkedIn URL' + req(fields.linkedin.req) + '</label><input type="url" id="ef-guest-li" placeholder="https://linkedin.com/in/username"></div>';
                html += '</div>';
            }
            if (xShow || webShow) {
                var s2Class = (xShow && webShow) ? 'BatchMate-ef-grid-2' : 'BatchMate-ef-grid-1';
                html += '<div class="' + s2Class + '">';
                if (xShow)  html += '<div class="BatchMate-ef-pay-group" style="margin-bottom:0;"><label>X / Twitter URL' + req(fields.x.req) + '</label><input type="url" id="ef-guest-x" placeholder="https://x.com/username"></div>';
                if (webShow) html += '<div class="BatchMate-ef-pay-group" style="margin-bottom:0;"><label>Personal Website' + req(fields.website.req) + '</label><input type="url" id="ef-guest-web" placeholder="https://yourwebsite.com"></div>';
                html += '</div>';
            }
        }

        // Photo Upload Box with Instant Preview
        var photoShow = fields.photo && fields.photo.show;
        if (photoShow) {
            html += '<div class="BatchMate-ef-pay-group" style="margin-top:16px;margin-bottom:14px;">' +
                '<label style="display:block;font-weight:600;margin-bottom:6px;font-size:13px;color:#334155;">Photo (Square 400&times;400)' + req(fields.photo.req) + '</label>' +
                '<div class="ef-photo-card">' +
                '<div class="ef-photo-thumb" id="ef-photo-thumb-container">' +
                '<img id="ef-photo-preview-img" src="' + noPhoto + '" alt="Photo Preview">' +
                '</div>' +
                '<div class="ef-photo-actions">' +
                '<label for="ef-guest-photo" class="ef-photo-btn"><span class="dashicons dashicons-upload" style="margin-right:4px;"></span> Choose Photo</label>' +
                '<input type="file" id="ef-guest-photo" accept="image/*" style="position:absolute;left:-9999px;opacity:0;pointer-events:none;">' +
                '<div id="ef-photo-filename" class="ef-photo-filename">No photo chosen yet</div>' +
                '<div class="ef-photo-help">Recommended: Square format 400&times;400 px. Instant preview shown above.</div>' +
                '</div></div></div>';
        }

        return html;
    }

    /* ---- Gift / T-Shirt / Saree HTML ---- */
    function renderGiftOptionHtml(isFemale) {
        if (!hasTshirt) return '';
        var sizeOpts = '<option value="">-- Select Size --</option>' +
            '<option value="S">S</option><option value="M">M</option><option value="X">X</option>' +
            '<option value="L">L</option><option value="XL" selected>XL</option>' +
            '<option value="XXL">XXL</option><option value="XXXL">XXXL</option>';

        if (hasSaree && isFemale) {
            return '<div class="BatchMate-ef-pay-group" id="ef-gift-container" style="margin-bottom:16px;background:#fdf2f8;border:1.5px solid #fbcfe8;border-radius:12px;padding:16px;">' +
                '<label style="font-weight:700;color:#9d174d;display:flex;align-items:center;gap:6px;margin-bottom:12px;font-size:14px;">&#128088; Gift Choice <span style="color:#c53030">*</span></label>' +
                '<div class="ef-gift-selector-grid">' +
                '<div class="ef-gift-card selected" data-value="tshirt">' +
                '<input type="radio" name="ef_gift_choice" value="tshirt" checked style="display:none;">' +
                '<div class="ef-gift-icon-wrap">&#128085;</div>' +
                '<div class="ef-gift-text"><strong>T-Shirt</strong><span>Select your size below</span></div>' +
                '<div class="ef-gift-check">&#10003;</div>' +
                '</div>' +
                '<div class="ef-gift-card" data-value="saree">' +
                '<input type="radio" name="ef_gift_choice" value="saree" style="display:none;">' +
                '<div class="ef-gift-icon-wrap">&#129528;</div>' +
                '<div class="ef-gift-text"><strong>Saree (শাড়ি)</strong><span>Traditional festive gift</span></div>' +
                '<div class="ef-gift-check">&#10003;</div>' +
                '</div>' +
                '</div>' +
                '<div id="ef-tshirt-size-wrapper" style="margin-top:12px;">' +
                '<label style="font-size:13px;font-weight:600;color:#475569;margin-bottom:6px;display:block;">Select T-Shirt Size: <span style="color:#c53030">*</span></label>' +
                '<select id="ef-tshirt-size">' + sizeOpts + '</select>' +
                '</div>' +
                '</div>';
        }
        return '<div class="BatchMate-ef-pay-group" id="ef-gift-container" style="margin-bottom:14px;">' +
            '<label>&#128085; T-Shirt Size <span style="color:#c53030">*</span></label>' +
            '<select id="ef-tshirt-size">' + sizeOpts + '</select>' +
            '</div>';
    }

    function cardHeader(m, photo) {
        return '<div class="BatchMate-ef-card-header">' +
            '<img src="' + photo + '" class="BatchMate-ef-card-photo">' +
            '<div class="BatchMate-ef-card-info">' +
            '<span class="BatchMate-ef-mid">' + (m.member_number||'') + '</span>' +
            '<h4>' + (m.name||'') + '</h4>' +
            '<p>' + (m.institution||m.program_name||'') + (m.batch ? ' &bull; Batch ' + m.batch : '') + '</p>' +
            '</div></div>';
    }

    /* -- Render single member form (after selection) -- */
    function renderMemberForm(memberData) {
        var m       = memberData.member;
        var isGuest = m.is_guest === true;
        var photo   = m.photo_url || noPhoto;
        _isCurrentGuest = isGuest;

        /* Already applied */
        if (memberData.already) {
            var st      = memberData.app_status;
            var stText  = st === 'approved' ? '&#10003; Your registration has been approved.'
                        : st === 'rejected' ? '&#10008; Your application was not accepted.'
                        : '&#8987; Your application is under review. Please wait for approval.';
            var stClass = st === 'approved' ? 'success' : st === 'rejected' ? 'error' : 'warning';
            $result.html('<div class="ef-anim BatchMate-ef-card">' + cardHeader(m, photo) +
                '<div class="BatchMate-ef-card-body">' + mkNotice(stClass, stText) + '</div></div>');
            return;
        }

        var headerHtml      = '';
        var guestFieldsHtml = '';
        var isFemaleGuest   = false;

        if (isGuest) {
            var isDirectMemberMode = (allowNonMembers === 2 || allowNonMembers === 3);
            var customHeader = customDirectRegTitle || '';
            var defaultTitle = isDirectMemberMode ? 'Membership &amp; Event Registration' : 'Guest Registration';
            var headerTitle = customHeader ? customHeader : defaultTitle;
            var subMsg = isDirectMemberMode
                ? 'Fill in your details to register for the event and apply for lifetime membership!'
                : 'Please provide your information below to register as a guest for this event.';
            var badgeHtml = isDirectMemberMode
                ? ' <span style="background:#ecfdf5;color:#047857;border:1px solid #a7f3d0;padding:3px 10px;border-radius:20px;font-size:11.5px;font-weight:700;margin-left:8px;display:inline-flex;align-items:center;gap:4px;">&#9733; Direct Member on Approval</span>'
                : ' <span style="background:#f1f5f9;color:#475569;border:1px solid #cbd5e1;padding:3px 10px;border-radius:20px;font-size:11.5px;font-weight:600;margin-left:8px;">Guest</span>';

            var backBtnHtml = (allowNonMembers === 3)
                ? ''
                : '<button type="button" class="BatchMate-ef-back-btn BatchMate-ef-guest-back-btn">&#8592; Search Another</button>';

            headerHtml = '<div class="BatchMate-ef-card-header">' +
                '<div class="BatchMate-ef-card-info" style="width:100%;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px;">' +
                '<div><h4 style="margin:0 0 4px;display:flex;align-items:center;flex-wrap:wrap;">' + headerTitle + badgeHtml + '</h4>' +
                '<p style="margin:0;font-size:13px;color:#64748b;">' + subMsg + '</p></div>' +
                backBtnHtml +
                '</div></div>';

            guestFieldsHtml = buildGuestFormHtml('Male');
        } else {
            headerHtml    = cardHeader(m, photo);
            isFemaleGuest = (m.gender && m.gender.toLowerCase() === 'female');
        }

        var giftHtml = isGuest ? '' : renderGiftOptionHtml(isFemaleGuest);
        var memberGiftHtml = (!isGuest && hasTshirt) ? giftHtml : '';
        var guestGiftPlaceholder = (isGuest && hasTshirt) ? '<div id="ef-guest-gift-wrap"></div>' : '';
        var giftSectionHtml = isGuest ? guestGiftPlaceholder : memberGiftHtml;

        /* Guest options */
        var optHtml = '';
        if (_evtOptions.length) {
            optHtml += '<p class="BatchMate-ef-pay-title" style="margin-top:16px;">&#128101; Guest Options</p>';
            _evtOptions.forEach(function(opt){
                var max = parseInt(opt.max_qty) || 0;
                optHtml += '<div class="BatchMate-ef-pay-group BatchMate-ef-opt-row" data-optid="'+opt.id+'" data-fee="'+opt.fee+'" data-max="'+max+'" data-name="'+opt.option_name+'">' +
                    '<label>'+opt.option_name+' <span class="BatchMate-ef-opt-fee">(&#2547; '+parseFloat(opt.fee).toFixed(2)+'/person'+(max>0?', max '+max:'')+' )</span></label>' +
                    '<div class="BatchMate-ef-opt-ctrl">' +
                    '<button type="button" class="BatchMate-ef-qty-btn" data-act="dec">&#8722;</button>' +
                    '<input type="number" class="BatchMate-ef-qty-inp" value="0" min="0"'+(max>0?' max="'+max+'"':'')+' readonly>' +
                    '<button type="button" class="BatchMate-ef-qty-btn" data-act="inc">&#43;</button>' +
                    '<span class="BatchMate-ef-opt-sub">&#2547; 0.00</span>' +
                    '</div></div>';
            });
        }

        /* Base Fee & Fee Breakdown (Show before Payment Information) */
        var baseFeeHtml = '';
        if (isGuest && allowNonMembers === 2 && _memFeeActive && _memFeeAmount > 0) {
            baseFeeHtml = '<div class="ef-fee-summary-card" style="margin:16px 0;padding:14px 18px;background:#f0fdf4;border:1.5px solid #bbf7d0;border-radius:10px;">' +
                '<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px;font-size:13.5px;color:#334155;">' +
                '<span><span class="dashicons dashicons-calendar-alt" style="color:#2563eb;margin-right:6px;vertical-align:text-bottom;"></span>Event Registration Fee:</span>' +
                '<strong style="color:#0f172a;">&#2547; ' + _evtMemberFee.toFixed(2) + '</strong>' +
                '</div>' +
                '<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;font-size:13.5px;color:#15803d;">' +
                '<span><span class="dashicons dashicons-id" style="color:#16a34a;margin-right:6px;vertical-align:text-bottom;"></span>Membership Fee (' + (_memFeeLabel || 'Direct Member') + '):</span>' +
                '<strong style="color:#15803d;">+ &#2547; ' + _memFeeAmount.toFixed(2) + '</strong>' +
                '</div>' +
                '<div style="border-top:1px dashed #86efac;padding-top:8px;display:flex;justify-content:space-between;align-items:center;font-size:14px;font-weight:700;color:#0f172a;">' +
                '<span>Total Base Registration:</span>' +
                '<span style="color:#15803d;font-size:16px;">&#2547; ' + (_evtMemberFee + _memFeeAmount).toFixed(2) + '</span>' +
                '</div>' +
                '</div>';
        } else if (_evtMemberFee > 0) {
            baseFeeHtml = '<div style="margin:16px 0;padding:12px 16px;background:#f0f6fc;border:1px solid #c8e1fa;border-radius:8px;display:flex;justify-content:space-between;align-items:center;">' +
                '<span style="font-weight:600;color:#1d2327;font-size:14px;"><span class="dashicons dashicons-admin-users" style="color:#2271b1;margin-right:6px;vertical-align:text-bottom;"></span>Base Registration Fee:</span>' +
                '<span style="font-weight:700;color:#2271b1;font-size:15px;">&#2547; ' + _evtMemberFee.toFixed(2) + '</span>' +
                '</div>';
        }

        /* Configured Payment Methods */
        var methodOpts = '<option value="">&mdash; Select Payment Method &mdash;</option>';
        if (paymentMethods && paymentMethods.length) {
            paymentMethods.forEach(function(pm){
                var esc = pm.replace(/"/g, '&quot;');
                methodOpts += '<option value="' + esc + '">' + pm + '</option>';
            });
        } else {
            ['bKash', 'Nagad', 'Rocket', 'Bank Transfer', 'Cash'].forEach(function(pm){
                methodOpts += '<option value="' + pm + '">' + pm + '</option>';
            });
        }

        var amountLabel = (_evtOptions.length || (isGuest && allowNonMembers === 2 && _memFeeActive && _memFeeAmount > 0))
            ? 'Total Amount (BDT) <span style="font-size:11px;color:#6c757d;">&mdash; auto-calculated</span>'
            : 'Amount (BDT) <span style="color:#c53030">*</span>';

        var submitBtnText = (isGuest && allowNonMembers === 2)
            ? '&#10003; Confirm Membership &amp; Event Registration'
            : '&#10003; Confirm Registration';

        /* Order: Personal/Academic info -> Gift Choice -> Guest Options -> Base Fee Breakdown -> Payment Info -> Submit */
        $result.html('<div class="ef-anim BatchMate-ef-card">' + headerHtml +
            '<div class="BatchMate-ef-card-body">' +
            guestFieldsHtml +
            giftSectionHtml +
            optHtml +
            baseFeeHtml +
            '<p class="BatchMate-ef-pay-title" style="margin-top:18px;">&#128179; Payment Information</p>' +
            '<div class="BatchMate-ef-grid-3">' +
            '<div class="BatchMate-ef-pay-group" style="margin-bottom:0;"><label>Payment Method <span style="color:#c53030">*</span></label>' +
            '<select id="BatchMate-ef-pay-method">' + methodOpts + '</select></div>' +
            '<div class="BatchMate-ef-pay-group" style="margin-bottom:0;"><label>Transaction / Ref ID <span style="color:#c53030">*</span></label>' +
            '<input type="text" id="BatchMate-ef-pay-details" placeholder="e.g. 8N7YXXABCD..."></div>' +
            '<div class="BatchMate-ef-pay-group" style="margin-bottom:0;"><label>' + amountLabel + '</label>' +
            '<input type="number" id="BatchMate-ef-pay-amount" placeholder="0.00" min="0" step="0.01" value="0.00" readonly style="background:#f8fafc; cursor:not-allowed;"></div>' +
            '</div>' +
            '<div class="BatchMate-ef-submit-row">' +
            '<button class="BatchMate-ef-submit-btn" id="BatchMate-ef-submit-btn-' + evtId + '" data-member-id="' + (isGuest ? 0 : m.id) + '">' +
            submitBtnText + '</button></div>' +
            '</div></div>');

        // Init select2 on event form
        if ($.fn.select2) {
            $result.find('.ef-select2').select2({ width: '100%' });
        }

        // Cascading District -> Upazila
        $result.off('change.presDist').on('change.presDist', '#ef-guest-pres-dist', function(){
            var dist = $(this).val();
            var upzList = (_bmUpazilas && _bmUpazilas[dist]) || [];
            var opts = '<option value="">Select Thana / Upazila...</option>';
            upzList.forEach(function(u){
                opts += '<option value="' + u + '">' + u + '</option>';
            });
            $('#ef-guest-pres-upazila').html(opts);
            if ($.fn.select2) $('#ef-guest-pres-upazila').trigger('change.select2');
        });

        $result.off('change.permDist').on('change.permDist', '#ef-guest-perm-dist', function(){
            var dist = $(this).val();
            var upzList = (_bmUpazilas && _bmUpazilas[dist]) || [];
            var opts = '<option value="">Select Thana / Upazila...</option>';
            upzList.forEach(function(u){
                opts += '<option value="' + u + '">' + u + '</option>';
            });
            $('#ef-guest-perm-upazila').html(opts);
            if ($.fn.select2) $('#ef-guest-perm-upazila').trigger('change.select2');
        });

        // Country toggle District & Upazila
        $result.off('change.presCountry').on('change.presCountry', '#ef-guest-pres-country', function(){
            var isBD = $(this).val() === 'Bangladesh';
            $('#wrap-ef-guest-pres-dist, #wrap-ef-guest-pres-upazila').toggle(isBD);
            if (!isBD && $.fn.select2) {
                $('#ef-guest-pres-dist, #ef-guest-pres-upazila').val('').trigger('change.select2');
            }
        });

        $result.off('change.permCountry').on('change.permCountry', '#ef-guest-perm-country', function(){
            var isBD = $(this).val() === 'Bangladesh';
            $('#wrap-ef-guest-perm-dist, #wrap-ef-guest-perm-upazila').toggle(isBD);
            if (!isBD && $.fn.select2) {
                $('#ef-guest-perm-dist, #ef-guest-perm-upazila').val('').trigger('change.select2');
            }
        });

        // For guests: listen to gender change → update gift section
        if (isGuest && hasTshirt) {
            $result.on('change', '#ef-guest-gender', function(){
                var isFem = $(this).val() === 'Female';
                $('#ef-guest-gift-wrap').html(renderGiftOptionHtml(isFem));
                bindGiftRadio();
            });
            // Init gift for default gender (Male)
            $('#ef-guest-gift-wrap').html(renderGiftOptionHtml(false));
        }
        bindGiftRadio();

        // Qty buttons
        $result.on('click', '.BatchMate-ef-qty-btn', function(){
            var $row = $(this).closest('.BatchMate-ef-opt-row');
            var $inp = $row.find('.BatchMate-ef-qty-inp');
            var max  = parseInt($row.data('max')) || 0;
            var fee  = parseFloat($row.data('fee')) || 0;
            var val  = parseInt($inp.val()) || 0;
            if ($(this).data('act') === 'inc') {
                if (max > 0 && val >= max) return;
                val++;
            } else {
                if (val > 0) val--;
            }
            $inp.val(val);
            $row.find('.BatchMate-ef-opt-sub').html('&#2547; '+(val*fee).toFixed(2));
            recalcTotal();
        });

        function recalcTotal() {
            var baseFee = _evtMemberFee;
            if (_isCurrentGuest && allowNonMembers === 2 && _memFeeActive && _memFeeAmount > 0) {
                baseFee += _memFeeAmount;
            }
            var total = baseFee;
            $result.find('.BatchMate-ef-opt-row').each(function(){
                total += parseFloat($(this).data('fee')) * (parseInt($(this).find('.BatchMate-ef-qty-inp').val())||0);
            });
            if (_evtOptions.length > 0 || _evtMemberFee > 0 || (_isCurrentGuest && allowNonMembers === 2 && _memFeeActive && _memFeeAmount > 0)) {
                $('#BatchMate-ef-pay-amount').val(total.toFixed(2));
            }
        }
        recalcTotal();
    }

    /* ---- Saree/TShirt card selector toggle ---- */
    function bindGiftRadio() {
        $(document).off('click.giftCard', '.ef-gift-card').on('click.giftCard', '.ef-gift-card', function(){
            var $this = $(this);
            $this.siblings('.ef-gift-card').removeClass('selected');
            $this.addClass('selected');
            var val = $this.data('value');
            $this.find('input[name="ef_gift_choice"]').prop('checked', true).trigger('change');
            if (val === 'saree') {
                $('#ef-tshirt-size-wrapper').slideUp(180);
            } else {
                $('#ef-tshirt-size-wrapper').slideDown(180);
            }
        });
    }

    /* -- Render search result -- */
    function renderResult(res, q) {
        if (!res.success) {
            var errData = res.data;
            // Duplicate non-member record
            if (errData && typeof errData === 'object' && errData.code === 'duplicate') {
                $result.html(mkNotice('warning', '&#9888; <strong>' + errData.name + '</strong> has already registered for this event using this email/mobile.'));
                return;
            }
            if (errData === 'not_found') {
                // allow_non_members === 2: Direct Member mode — show form instead of "not found"
                if (allowNonMembers === 2) {
                    renderMemberForm({ member: { is_guest: true }, already: false });
                    return;
                }
                var msg = (allowNonMembers === 1)
                    ? 'No matching member was found. You can register as a non-member or become a member.'
                    : 'No matching member was found. You must be a Batchmate member to register.';
                var btnHtml = '<a href="' + regLink + '" class="BatchMate-ef-submit-btn" style="background:#2271b1;text-decoration:none;" target="_blank">Become a Member</a>';
                if (allowNonMembers === 1) {
                    btnHtml += ' <button type="button" class="BatchMate-ef-submit-btn" id="BatchMate-ef-guest-btn" style="background:#6c757d;">Register as Guest</button>';
                }
                $result.html('<div class="ef-anim BatchMate-ef-notice BatchMate-ef-notice--warning" style="background:#fff;border-color:#dee2e6;">' +
                    '<h4 style="margin:0 0 10px;color:#1d2327;">No Member Found</h4>' +
                    '<p style="margin:0 0 15px;color:#6c757d;">' + msg + '</p>' +
                    '<div style="display:flex;gap:10px;flex-wrap:wrap;">' + btnHtml + '</div></div>');
            } else {
                $result.html(mkNotice('error', res.data || 'An error occurred.'));
            }
            return;
        }

        var members = res.data.members;
        if (!members || members.length === 0) {
            $result.html(mkNotice('error', 'No members found.'));
            return;
        }

        // If only 1 result -> show form directly
        if (members.length === 1) {
            renderMemberForm(members[0]);
            return;
        }

        // Multiple results -> show selection list
        var listHtml = '<div class="ef-anim BatchMate-ef-member-list">' +
            '<p class="BatchMate-ef-member-list-title">&#128269; ' + members.length + ' matching members found — select yours:</p>' +
            '<div class="BatchMate-ef-member-list-items">';

        members.forEach(function(md, idx) {
            var m = md.member;
            var photo = m.photo_url || noPhoto;
            var statusBadge = '';
            if (md.already) {
                var st = md.app_status;
                var stLabel = st === 'approved' ? '&#10003; Approved' : (st === 'rejected' ? '&#10008; Rejected' : '&#8987; Pending');
                var stBg    = st === 'approved' ? '#d1e7dd' : (st === 'rejected' ? '#f8d7da' : '#fff3cd');
                var stClr   = st === 'approved' ? '#0a7242' : (st === 'rejected' ? '#d63638' : '#856404');
                statusBadge = '<span class="BatchMate-ef-ml-status" style="background:' + stBg + ';color:' + stClr + ';">' + stLabel + '</span>';
            }
            listHtml += '<div class="BatchMate-ef-ml-item" data-idx="' + idx + '">' +
                '<img src="' + photo + '" class="BatchMate-ef-ml-photo">' +
                '<div class="BatchMate-ef-ml-info">' +
                '<span class="BatchMate-ef-mid">' + (m.member_number||'') + '</span>' +
                '<strong class="BatchMate-ef-ml-name">' + (m.name||'') + '</strong>' +
                '<span class="BatchMate-ef-ml-meta">' + (m.institution||'') + (m.batch ? ' &bull; Batch ' + m.batch : '') + '</span>' +
                '</div>' + statusBadge +
                '<span class="BatchMate-ef-ml-arrow">&#8250;</span>' +
                '</div>';
        });

        listHtml += '</div></div>';
        $result.html(listHtml);

        $result.on('click', '.BatchMate-ef-ml-item', function() {
            renderMemberForm(members[parseInt($(this).data('idx'))]);
        });
    }
    $(document).on('click', '#BatchMate-ef-guest-btn', function() {
        renderMemberForm({
            member: { is_guest: true },
            already: false
        });
    });

    $(document).on('change', '#ef-guest-photo', function() {
        var input = this;
        var file = input.files && input.files[0];
        if (!file) return;

        $('#ef-photo-filename').text(file.name + ' (Selected)');

        if (!file.type.match(/^image\//)) return;

        // INSTANT preview update without any delay
        if (window.URL && window.URL.createObjectURL) {
            try {
                $('#ef-photo-preview-img').attr('src', window.URL.createObjectURL(file));
                $('#ef-photo-thumb-container').show();
            } catch(e){}
        }

        var reader = new FileReader();
        reader.onload = function(e) {
            $('#ef-photo-preview-img').attr('src', e.target.result);

            var img = new Image();
            img.onload = function() {
                var canvas = document.createElement('canvas');
                canvas.width = 400;
                canvas.height = 400;
                var ctx = canvas.getContext('2d');
                ctx.imageSmoothingEnabled = true;
                ctx.imageSmoothingQuality = 'high';
                var w = img.width, h = img.height;
                var cropSize = Math.min(w, h);
                var sx = (w - cropSize) / 2, sy = (h - cropSize) / 2;
                ctx.drawImage(img, sx, sy, cropSize, cropSize, 0, 0, 400, 400);
                if (canvas.toBlob && window.DataTransfer) {
                    canvas.toBlob(function(blob) {
                        if (!blob) return;
                        var baseName = file.name ? file.name.replace(/\.[^/.]+$/, "") : 'photo';
                        var resizedFile = new File([blob], baseName + '.jpg', { type: 'image/jpeg', lastModified: Date.now() });
                        var dt = new DataTransfer();
                        dt.items.add(resizedFile);
                        input.files = dt.files;
                    }, 'image/jpeg', 0.90);
                }
            };
            img.src = e.target.result;
        };
        reader.readAsDataURL(file);
    });

    /* -- Submit application -- */
    $(document).on('click', '#BatchMate-ef-submit-btn-' + evtId, function() {
        var method  = $('#BatchMate-ef-pay-method').val();
        var details = $.trim($('#BatchMate-ef-pay-details').val());
        var amount  = parseFloat($('#BatchMate-ef-pay-amount').val()) || 0;
        var mId     = $(this).data('member-id');

        var isGuest = (mId == '0' || !mId);
        var formData = new FormData();

        if (isGuest) {
            var gName     = $.trim($('#ef-guest-name').val());
            var gNameBn   = $.trim($('#ef-guest-name-bn').val());
            var gCell     = $.trim($('#ef-guest-cell').val());
            var gEmail    = $.trim($('#ef-guest-email').val());
            var gGender   = $('#ef-guest-gender').val() || 'Male';
            var gBlood    = $('#ef-guest-blood').val();
            var gInst     = $.trim($('#ef-guest-institution').val());
            var gRoll     = $.trim($('#ef-guest-roll').val());
            var gRegno    = $.trim($('#ef-guest-regno').val());
            var gBoard    = $.trim($('#ef-guest-board').val());
            var gGroup    = $('#ef-guest-group').val();
            var gDob      = $('#ef-guest-dob').val();
            var gReligion = $('#ef-guest-religion').val();
            var gLand     = $.trim($('#ef-guest-land-phone').val());
            var gPresAddr = $.trim($('#ef-guest-pres-addr').val());
            var gPermAddr = $.trim($('#ef-guest-perm-addr').val());
            var gPresCountry = $('#ef-guest-pres-country').val() || 'Bangladesh';
            var gPresDist = $.trim($('#ef-guest-pres-dist').val());
            var gPresUpz  = $.trim($('#ef-guest-pres-upazila').val());
            var gPermCountry = $('#ef-guest-perm-country').val() || 'Bangladesh';
            var gPermDist = $.trim($('#ef-guest-perm-dist').val());
            var gPermUpz  = $.trim($('#ef-guest-perm-upazila').val());
            var gOrg      = $.trim($('#ef-guest-org').val());
            var gPos      = $.trim($('#ef-guest-pos').val());
            var gInd      = $.trim($('#ef-guest-ind').val());
            var gExp      = $.trim($('#ef-guest-exp').val());
            var gEmpAddr  = $.trim($('#ef-guest-emp-addr').val());
            var gPrevOrg  = $.trim($('#ef-guest-prev-org').val());
            var gAbout    = $.trim($('#ef-guest-about').val());
            var gSkills   = $.trim($('#ef-guest-skills').val());
            var gFb       = $.trim($('#ef-guest-fb').val());
            var gLi       = $.trim($('#ef-guest-li').val());
            var gX        = $.trim($('#ef-guest-x').val());
            var gWeb      = $.trim($('#ef-guest-web').val());
            var gPhotoInp = document.getElementById('ef-guest-photo');
            var gPhoto    = gPhotoInp && gPhotoInp.files && gPhotoInp.files.length > 0 ? gPhotoInp.files[0] : null;

            var fields = guestFieldsCfg;
            var missingFields = [];
            if (!gName) missingFields.push('Name');
            if (!gCell) missingFields.push('Mobile');
            if (fields.name_bn && fields.name_bn.show && fields.name_bn.req && !gNameBn) missingFields.push('Name (Bangla)');
            if (fields.email && fields.email.show && fields.email.req && !gEmail) missingFields.push('Email');
            if (fields.gender && fields.gender.show && fields.gender.req && !gGender) missingFields.push('Gender');
            if (fields.blood_group && fields.blood_group.show && fields.blood_group.req && !gBlood) missingFields.push('Blood Group');
            if (fields.dob && fields.dob.show && fields.dob.req && !gDob) missingFields.push('Date of Birth');
            if (fields.religion && fields.religion.show && fields.religion.req && !gReligion) missingFields.push('Religion');
            if (fields.land_phone && fields.land_phone.show && fields.land_phone.req && !gLand) missingFields.push('Alternative Number');
            if (fields.board && fields.board.show && fields.board.req && !gBoard) missingFields.push('Board');
            if (fields.institution && fields.institution.show && fields.institution.req && !gInst) missingFields.push('Institution');
            if (fields.roll_no && fields.roll_no.show && fields.roll_no.req && !gRoll) missingFields.push('Roll No');
            if (fields.reg_no && fields.reg_no.show && fields.reg_no.req && !gRegno) missingFields.push('Reg No');
            if (fields.group && fields.group.show && fields.group.req && !gGroup) missingFields.push('Group');
            if (fields.present_address && fields.present_address.show && fields.present_address.req && !gPresAddr) missingFields.push('Present Address');
            if (fields.present_country && fields.present_country.show && fields.present_country.req && !gPresCountry) missingFields.push('Present Country');
            if (fields.present_district && fields.present_district.show && fields.present_district.req && gPresCountry === 'Bangladesh' && !gPresDist) missingFields.push('Present District');
            if (fields.present_upazila && fields.present_upazila.show && fields.present_upazila.req && gPresCountry === 'Bangladesh' && !gPresUpz) missingFields.push('Present Thana / Upazila');
            if (fields.permanent_address && fields.permanent_address.show && fields.permanent_address.req && !gPermAddr) missingFields.push('Permanent Address');
            if (fields.permanent_country && fields.permanent_country.show && fields.permanent_country.req && !gPermCountry) missingFields.push('Permanent Country');
            if (fields.permanent_district && fields.permanent_district.show && fields.permanent_district.req && gPermCountry === 'Bangladesh' && !gPermDist) missingFields.push('Permanent District');
            if (fields.permanent_upazila && fields.permanent_upazila.show && fields.permanent_upazila.req && gPermCountry === 'Bangladesh' && !gPermUpz) missingFields.push('Permanent Thana / Upazila');
            if (fields.organization && fields.organization.show && fields.organization.req && !gOrg) missingFields.push('Organization');
            if (fields.position && fields.position.show && fields.position.req && !gPos) missingFields.push('Position / Designation');
            if (fields.industry && fields.industry.show && fields.industry.req && !gInd) missingFields.push('Industry');
            if (fields.work_experience && fields.work_experience.show && fields.work_experience.req && !gExp) missingFields.push('Work Experience');
            if (fields.employment_address && fields.employment_address.show && fields.employment_address.req && !gEmpAddr) missingFields.push('Organization Address');
            if (fields.previous_organizations && fields.previous_organizations.show && fields.previous_organizations.req && !gPrevOrg) missingFields.push('Previous Organizations');
            if (fields.about_me && fields.about_me.show && fields.about_me.req && !gAbout) missingFields.push('About Me');
            if (fields.skills && fields.skills.show && fields.skills.req && !gSkills) missingFields.push('Skills');
            if (fields.facebook && fields.facebook.show && fields.facebook.req && !gFb) missingFields.push('Facebook URL');
            if (fields.linkedin && fields.linkedin.show && fields.linkedin.req && !gLi) missingFields.push('LinkedIn URL');
            if (fields.x && fields.x.show && fields.x.req && !gX) missingFields.push('X / Twitter URL');
            if (fields.website && fields.website.show && fields.website.req && !gWeb) missingFields.push('Personal Website');
            if (fields.photo && fields.photo.show && fields.photo.req && !gPhoto) missingFields.push('Photo');

            if (missingFields.length > 0) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Required Fields Missing',
                    html: '<p style="margin:0 0 10px;color:#555;">Please fill in the following required fields:</p>' +
                          '<ul style="text-align:left;margin:0;padding-left:20px;color:#d63638;font-weight:600;">' +
                          missingFields.map(function(f){ return '<li>' + f + '</li>'; }).join('') +
                          '</ul>',
                    confirmButtonText: 'OK, Fix It',
                    confirmButtonColor: '#2271b1',
                });
                return;
            }

            formData.append('is_guest', '1');
            formData.append('guest_name',   gName);
            formData.append('guest_name_bn', gNameBn);
            formData.append('guest_email',  gEmail);
            formData.append('guest_cell',   gCell);
            formData.append('guest_gender', gGender);
            formData.append('guest_blood',  gBlood);
            formData.append('guest_institution', gInst);
            formData.append('guest_roll',   gRoll);
            formData.append('guest_regno',  gRegno);
            formData.append('guest_board',  gBoard);
            formData.append('guest_group',  gGroup);
            formData.append('guest_dob',    gDob);
            formData.append('guest_religion', gReligion);
            formData.append('guest_land_phone', gLand);
            formData.append('guest_present_address', gPresAddr);
            formData.append('guest_present_country', gPresCountry);
            formData.append('guest_present_district', gPresDist);
            formData.append('guest_present_upazila', gPresUpz);
            formData.append('guest_permanent_address', gPermAddr);
            formData.append('guest_permanent_country', gPermCountry);
            formData.append('guest_permanent_district', gPermDist);
            formData.append('guest_upazila', gPermUpz);
            formData.append('guest_organization', gOrg);
            formData.append('guest_position', gPos);
            formData.append('guest_industry', gInd);
            formData.append('guest_work_experience', gExp);
            formData.append('guest_employment_address', gEmpAddr);
            formData.append('guest_previous_organizations', gPrevOrg);
            formData.append('guest_about_me', gAbout);
            formData.append('guest_skills', gSkills);
            formData.append('guest_facebook', gFb);
            formData.append('guest_linkedin', gLi);
            formData.append('guest_x', gX);
            formData.append('guest_website', gWeb);
            if (gPhoto) formData.append('guest_photo', gPhoto);
        } else {
            formData.append('member_id', mId);
        }

        if (!method) {
            Swal.fire({
                icon: 'warning',
                title: 'Payment Method Required',
                text: 'Please select a payment method.',
                confirmButtonColor: '#2271b1',
            });
            $('#BatchMate-ef-pay-method').focus();
            return;
        }
        if (!details) {
            Swal.fire({
                icon: 'warning',
                title: 'Transaction ID Required',
                text: 'Please enter your Transaction / Reference ID.',
                confirmButtonColor: '#2271b1',
            });
            $('#BatchMate-ef-pay-details').focus();
            return;
        }
        var totalExpected = parseFloat($('#BatchMate-ef-pay-amount').val()) || 0;
        if (totalExpected > 0 && amount <= 0) {
            Swal.fire({
                icon: 'warning',
                title: 'Invalid Amount',
                text: 'Please enter a valid payment amount.',
                confirmButtonColor: '#2271b1',
            });
            $('#BatchMate-ef-pay-amount').focus();
            return;
        }

        var optsArr = [];
        $result.find('.BatchMate-ef-opt-row').each(function(){
            var qty = parseInt($(this).find('.BatchMate-ef-qty-inp').val()) || 0;
            optsArr.push({
                option_id: $(this).data('optid'),
                name:      $(this).data('name'),
                qty:       qty,
                fee:       parseFloat($(this).data('fee')),
                subtotal:  qty * parseFloat($(this).data('fee'))
            });
        });
        var options_json = optsArr.length ? JSON.stringify(optsArr) : '';

        formData.append('action', 'BatchMate_submit_event_application');
        formData.append('event_id', evtId);
        formData.append('payment_method', method);
        formData.append('payment_details', details);
        formData.append('amount', amount);
        formData.append('options_json', options_json);
        formData.append('_wpnonce', nonce);

        // T-Shirt / Saree (if event has T-shirt enabled)
        if (hasTshirt) {
            var giftChoice = $('input[name="ef_gift_choice"]:checked').val() || 'tshirt';
            var tshirtVal  = ''
            if (giftChoice === 'saree') {
                tshirtVal = 'Saree';
            } else {
                tshirtVal = $('#ef-tshirt-size').val();
                if (!tshirtVal) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Gift / T-Shirt Required',
                        text: 'Please select your T-Shirt size before submitting.',
                        confirmButtonColor: '#2271b1',
                    });
                    $('#ef-tshirt-size').focus();
                    return;
                }
            }
            formData.append('tshirt_size', tshirtVal);
        }

        var $btn = $(this).prop('disabled', true).html('&#8987; Submitting�');

        $.ajax({
            url: ajaxUrl, type: 'POST',
            data: formData,
            processData: false, contentType: false
        }).done(function(res) {
            if (res.success) {
                $result.html('<div class="ef-anim">' + mkNotice('success', '&#10003; ' + res.data) + '</div>');
                $input.val(''); lastQ = '';
                $hint.html('&#9660; Results appear automatically after 3 characters.').removeClass('typing');
            } else {
                $result.html('<div class="ef-anim">' + mkNotice('error', '&#10008; ' + res.data) + '</div>');
                $btn.prop('disabled', false).html('&#10003; Confirm Registration');
            }
        }).fail(function() {
            $result.html('<div class="ef-anim">' + mkNotice('error', 'Connection error. Please try again.') + '</div>');
            $btn.prop('disabled', false).html('&#10003; Confirm Registration');
        });
    });

    // Option 4 render is now handled inside the $.ajax().done() callback above
    // so that _evtOptions and _evtMemberFee are populated before the form renders.

})(jQuery);

</script>
    <?php
    return ob_get_clean();
}
add_shortcode( 'event_form', 'batchmate_event_form_shortcode' );