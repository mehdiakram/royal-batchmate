<?php
// File: includes/admin-settings-page.php
if ( ! defined( 'ABSPATH' ) ) exit;

$settings = get_option('batchmate_settings', array());

$org_name           = $settings['org_name']           ?? '';
$org_short_name     = $settings['org_short_name']     ?? '';
$org_member_prefix  = $settings['org_member_prefix']  ?? '';
$org_member_id_year = $settings['org_member_id_year'] ?? 'none';
$org_member_id_digits = $settings['org_member_id_digits'] ?? '4';
$org_tagline        = $settings['org_tagline']        ?? '';
$org_logo_url       = $settings['org_logo_url']       ?? '';
$org_email          = $settings['org_email']          ?? '';
$org_address        = $settings['org_address']        ?? '';

$default_banner_url        = ROYAL_BATCHMATE_PLUGIN_URL . '/assets/images/banner.jpg';
$member_profile_banner_url = ( ! empty( $settings['member_profile_banner_url'] ) )
                             ? $settings['member_profile_banner_url'] : '';

$automation_active = $settings['automation_active'] ?? ( isset($settings['birthday_active']) && $settings['birthday_active'] === 'on' ? 'on' : 'off' );
$webhook_urls      = $settings['webhook_urls'] ?? array();
if ( empty( $webhook_urls ) && ! empty( $settings['make_webhook_url'] ) ) {
    $webhook_urls = array(
        array(
            'url'    => $settings['make_webhook_url'],
            'name'   => 'make.com Webhook',
            'status' => 'on',
            'events' => array('all')
        )
    );
}
$buffer_enabled  = $settings['buffer_enabled'] ?? 'off';
$buffer_api_key  = $settings['buffer_api_key'] ?? '';
$buffer_channels = $settings['buffer_channels'] ?? array();

// SMS Gateway Settings
$sms_enabled   = $settings['sms_enabled'] ?? 'off';
$sms_api_key   = $settings['sms_api_key'] ?? '';
$sms_sender_id = $settings['sms_sender_id'] ?? '';

// Birthday Automation
$birthday_active        = $settings['birthday_active'] ?? 'off';
$email_active           = $settings['email_active'] ?? 'on';
$birthday_email_subject = $settings['birthday_email_subject'] ?? ( function_exists('batchmate_get_default_template') ? batchmate_get_default_template('birthday_email_subject') : 'Happy Birthday, {name}!' );
$birthday_email_body    = $settings['birthday_email_body'] ?? ( function_exists('batchmate_get_default_template') ? batchmate_get_default_template('birthday_email_body') : '' );
$birthday_sms_active    = $settings['birthday_sms_active'] ?? 'off';
$birthday_sms_time      = $settings['birthday_sms_time'] ?? 'morning_9am';
$birthday_sms_template  = $settings['birthday_sms_template'] ?? ( function_exists('batchmate_get_default_template') ? batchmate_get_default_template('birthday_sms') : '' );

// Event Automation
$event_automation_active = $settings['event_automation_active'] ?? 'off';
$event_email_active      = $settings['event_email_active'] ?? 'on';
$event_email_subject     = $settings['event_email_subject'] ?? ( function_exists('batchmate_get_default_template') ? batchmate_get_default_template('event_email_subject') : 'Registration Confirmed: {event_name}' );
$event_email_body        = $settings['event_email_body'] ?? ( function_exists('batchmate_get_default_template') ? batchmate_get_default_template('event_email_body') : '' );
$event_sms_active        = $settings['event_sms_active'] ?? 'off';
$event_sms_template      = $settings['event_sms_template'] ?? ( function_exists('batchmate_get_default_template') ? batchmate_get_default_template('event_sms') : '' );
$event_webhook_active    = $settings['event_webhook_active'] ?? 'on';

// Member Registration Automation
$member_reg_automation_active = $settings['member_reg_automation_active'] ?? 'off';
$member_reg_sms_active        = $settings['member_reg_sms_active'] ?? 'off';
$member_reg_sms_template      = $settings['member_reg_sms_template'] ?? ( function_exists('batchmate_get_default_template') ? batchmate_get_default_template('member_reg_sms') : '' );
$member_reg_webhook_active    = $settings['member_reg_webhook_active'] ?? 'on';

$form_mail_to           = $settings['form_mail_to']           ?? 'info@batchmate.org';
$event_mail_to          = $settings['event_mail_to']          ?? 'info@batchmate.org';
$fb_post_method         = $settings['fb_post_method']         ?? 'none';
$show_test_birthday     = $settings['show_test_birthday']     ?? 'on';
$show_test_event_card   = $settings['show_test_event_card']   ?? 'on';
$show_raw_excel         = $settings['show_raw_excel']         ?? 'on';
$make_webhook_url       = $settings['make_webhook_url']       ?? '';
$birthday_card_template        = $settings['birthday_card_template']        ?? 'birthdaycard1.php';
$birthday_card_badge_field     = $settings['birthday_card_badge_field']     ?? 'none';
$birthday_card_custom_subtitle = $settings['birthday_card_custom_subtitle'] ?? '';
$fb_page_id             = $settings['fb_page_id']             ?? '';
$fb_access_token        = $settings['fb_access_token']        ?? '';

$auto_verify  = $settings['auto_verify']  ?? 'off';
$exam_type    = $settings['exam_type']    ?? 'SSC';
$passing_year = $settings['passing_year'] ?? '1999';
$reg_fee_active = $settings['reg_fee_active'] ?? 'on';
$reg_fee_text = $settings['reg_fee_text'] ?? 'Payment Method * [204 Taka]';
$reg_fee_amount = $settings['reg_fee_amount'] ?? '';
if ( $reg_fee_amount === '' && preg_match( '/(\d+(?:\.\d+)?)/', $reg_fee_text, $m ) ) {
    $reg_fee_amount = $m[1];
}
$reg_payment_methods = $settings['payment_methods'] ?? '';

$label_batchmate = $settings['label_batchmate'] ?? 'Batchmate';
$label_batchmates = $settings['label_batchmates'] ?? 'Batchmates';
$label_member = $settings['label_member'] ?? 'Member';
$label_members = $settings['label_members'] ?? 'Members';
$label_main_menu = $settings['label_main_menu'] ?? 'BatchMate';
$label_settings_menu = $settings['label_settings_menu'] ?? 'Settings';
$label_settings_page_title = $settings['label_settings_page_title'] ?? 'BatchMate Settings';

$label_institution        = $settings['label_institution'] ?? ($settings['fields']['reg_institution']['label'] ?? '');
$label_present_district   = $settings['label_present_district'] ?? ($settings['fields']['reg_present_district']['label'] ?? '');
$label_present_upazila    = $settings['label_present_upazila'] ?? ($settings['fields']['reg_present_upazila']['label'] ?? '');
$label_permanent_district = $settings['label_permanent_district'] ?? ($settings['fields']['reg_permanent_district']['label'] ?? '');
$label_permanent_upazila  = $settings['label_permanent_upazila'] ?? ($settings['fields']['reg_permanent_upazila']['label'] ?? '');
$label_roll_no            = $settings['label_roll_no'] ?? ($settings['fields']['reg_roll_no']['label'] ?? '');

$bc_bg_type = $settings['bc_bg_type'] ?? 'color'; // 'color' or 'image'
$bc_bg_color = $settings['bc_bg_color'] ?? '#263b71'; // default blue
$bc_bg_image = $settings['bc_bg_image'] ?? '';
$bc_text_color = $settings['bc_text_color'] ?? '#000000'; // black by default, based on screenshot
$bc_font_size = $settings['bc_font_size'] ?? '32';

$theme_uri           = ROYAL_BATCHMATE_PLUGIN_URL;
$template_dir        = $theme_uri . '/templates/';
$default_preview_url = function_exists( 'batchmate_get_or_generate_card_preview' )
    ? batchmate_get_or_generate_card_preview( $birthday_card_template )
    : ( $template_dir . str_replace('.php', '_preview.jpg', $birthday_card_template) );

// Active tab from URL hash (PHP fallback)
$active_tab = 'organization';
?>

<div class="wrap BatchMate-settings-wrap">

    <!-- ── Page Header ── -->
    <div class="BatchMate-settings-header">
        <div class="BatchMate-settings-header-inner">
            <div class="BatchMate-settings-title">
                <span class="BatchMate-settings-icon">⚙️</span>
                <div>
                    <h1><?php echo esc_html($label_settings_page_title); ?></h1>
                    <p>Configure organization details, email notifications, and system preferences.</p>
                </div>
            </div>
            <button type="button" id="submit-settings" class="BatchMate-save-btn">
                <span class="dashicons dashicons-saved"></span> Save Settings
            </button>
        </div>
    </div>

    <!-- ── Tab Navigation ── -->
    <div class="BatchMate-tab-nav">
        <button class="BatchMate-tab-btn BatchMate-tab-active" data-tab="organization">
            <span class="dashicons dashicons-building"></span>
            Organization
        </button>
        <button class="BatchMate-tab-btn" data-tab="breadcrumb">
            <span class="dashicons dashicons-layout"></span>
            Breadcrumb
        </button>
        <button class="BatchMate-tab-btn" data-tab="registration">
            <span class="dashicons dashicons-clipboard"></span>
            Registration
        </button>
        <button class="BatchMate-tab-btn" data-tab="automation">
            <span class="dashicons dashicons-admin-generic"></span>
            Automation
        </button>
        <button class="BatchMate-tab-btn" data-tab="license">
            <span class="dashicons dashicons-lock"></span>
            License
        </button>
        <button class="BatchMate-tab-btn" data-tab="about">
            <span class="dashicons dashicons-info"></span>
            About
        </button>
    </div>

    <!-- ── Form ── -->
    <form id="BatchMate-settings-form" method="post" enctype="multipart/form-data">
        <input type="hidden" name="action" value="batchmate_save_settings">
        <input type="hidden" name="_wpnonce" value="<?php echo wp_create_nonce('BatchMate_settings_nonce'); ?>">
        <input type="hidden" id="current_tab" name="current_tab" value="organization">

    <?php
    // ------------------------------------------------------------
    // SETTINGS TABS
    // ------------------------------------------------------------
    $settings_dir = plugin_dir_path( __FILE__ ) . 'settings/';
    include_once $settings_dir . 'tabs/tab-organization.php';
    include_once $settings_dir . 'tabs/tab-registration.php';
    include_once $settings_dir . 'tabs/tab-automation.php';
    include_once $settings_dir . 'tabs/tab-breadcrumb.php';
    include_once $settings_dir . 'tabs/tab-license.php';
    include_once $settings_dir . 'tabs/tab-about.php';
    ?>

</div><!-- /.BatchMate-settings-wrap -->

<!-- ============================================================
     ASSETS & RUNTIME CONFIGURATION
============================================================ -->
<?php
wp_enqueue_style( 'batchmate-admin-settings-css', ROYAL_BATCHMATE_PLUGIN_URL . 'assets/admin-settings.css', [ 'admin-css' ], ROYAL_BATCHMATE_VERSION );
wp_enqueue_script( 'batchmate-admin-settings-js', ROYAL_BATCHMATE_PLUGIN_URL . 'assets/admin-settings.js', [ 'jquery', 'sweetalert2-js' ], ROYAL_BATCHMATE_VERSION, true );
?>
<script type="text/javascript">
window.batchmate_settings_cfg = window.batchmate_settings_cfg || {
    templateDir: <?php echo json_encode( $template_dir ); ?>,
    defaultBannerUrl: <?php echo json_encode( $default_banner_url ); ?>
};
</script>
