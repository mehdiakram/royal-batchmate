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

    // Enqueue dependencies for event registration (Select2 for district/upazila, SweetAlert2 for validation popups)
    wp_enqueue_style( 'select2-css' );
    wp_enqueue_script( 'select2-js' );
    wp_enqueue_style( 'sweetalert2-css' );
    wp_enqueue_script( 'sweetalert2-js' );

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


<?php
$ef_css_ver = defined( 'ROYAL_BATCHMATE_VERSION' ) ? ROYAL_BATCHMATE_VERSION . '.' . (string) @filemtime( ROYAL_BATCHMATE_PLUGIN_DIR . 'assets/shortcode-event-form.css' ) : (string) time();
$ef_js_ver  = defined( 'ROYAL_BATCHMATE_VERSION' ) ? ROYAL_BATCHMATE_VERSION . '.' . (string) @filemtime( ROYAL_BATCHMATE_PLUGIN_DIR . 'assets/shortcode-event-form.js' )  : (string) time();
wp_enqueue_style( 'batchmate-shortcode-event-form', ROYAL_BATCHMATE_PLUGIN_URL . 'assets/shortcode-event-form.css', array(), $ef_css_ver );
wp_enqueue_script( 'batchmate-shortcode-event-form', ROYAL_BATCHMATE_PLUGIN_URL . 'assets/shortcode-event-form.js', array( 'jquery' ), $ef_js_ver, true );
?>
<script>
window.batchmate_event_forms = window.batchmate_event_forms || [];
window.batchmate_event_forms.push({
    evtId: <?php echo intval( $event_id ); ?>,
    nonce: <?php echo wp_json_encode( $nonce ); ?>,
    ajaxUrl: <?php echo wp_json_encode( $ajax_url ); ?>,
    noPhoto: <?php echo wp_json_encode( $no_photo ); ?>,
    regLink: <?php echo wp_json_encode( $reg_link ); ?>,
    allowNonMembers: <?php echo intval( $event->allow_non_members ); ?>,
    hasTshirt: <?php echo intval( $event->has_tshirt ); ?>,
    hasSaree: <?php echo intval( $event->has_saree ?? 0 ); ?>,
    guestFieldsCfg: <?php echo $guest_fields_json; ?>,
    paymentMethods: <?php echo $payment_methods_json; ?>,
    distinctInstitutions: <?php echo $institutions_json; ?>,
    membershipFeeInfo: <?php echo $membership_fee_json; ?>,
    bdDistricts: <?php echo $bd_districts_json; ?>,
    bdUpazilas: <?php echo $bd_upazilas_json; ?>,
    bmCountries: <?php echo $countries_json; ?>,
    customDirectRegTitle: <?php echo wp_json_encode( ! empty( $event->direct_reg_text ) ? $event->direct_reg_text : '' ); ?>
});
</script>
    <?php
    return ob_get_clean();
}
add_shortcode( 'event_form', 'batchmate_event_form_shortcode' );
