<?php
/**
 * Plugin Name:       Royal BatchMate
 * Plugin URI:        https://www.royaltechbd.com
 * Description:       SSC Batch Community — Member Directory, Events, Payments, Executive Committee & Reunion Management.
 * Version:           26.11.16
 * Author:            Royal Technologies
 * Author URI:        https://www.royaltechbd.com
 * License:           Proprietary
 * Text Domain:       royal-batchmate
 * Requires at least: 6.0
 * Requires PHP:      8.1
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// ============================================================
// CONSTANTS
// ============================================================
define( 'ROYAL_BATCHMATE_VERSION',    '26.11.16' );
define( 'ROYAL_BATCHMATE_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'ROYAL_BATCHMATE_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'ROYAL_BATCHMATE_PLUGIN_FILE', __FILE__ );

// ============================================================
// LICENSE SYSTEM (loads first)
// ============================================================
require_once ROYAL_BATCHMATE_PLUGIN_DIR . 'license/license.php';

// ============================================================
// ACTIVATION / DEACTIVATION HOOKS
// ============================================================
register_activation_hook( __FILE__, 'royal_batchmate_activate' );
register_deactivation_hook( __FILE__, 'royal_batchmate_deactivate' );

function royal_batchmate_activate(): void {
    require_once ROYAL_BATCHMATE_PLUGIN_DIR . 'includes/database.php';
    batchmate_create_base_tables();

    // Schedule daily license cron
    if ( ! wp_next_scheduled( 'rbm_daily_license_ping' ) ) {
        wp_schedule_event( time(), 'daily', 'rbm_daily_license_ping' );
    }

    // Schedule birthday cron
    if ( ! wp_next_scheduled( 'batchmate_daily_birthday_cron' ) ) {
        wp_schedule_event( strtotime( 'today 08:00:00' ), 'daily', 'batchmate_daily_birthday_cron' );
    }
}

function royal_batchmate_deactivate(): void {
    wp_clear_scheduled_hook( 'rbm_daily_license_ping' );
    wp_clear_scheduled_hook( 'batchmate_daily_birthday_cron' );
}

// ============================================================
// INTERNATIONALIZATION (i18n)
// ============================================================
add_action( 'init', 'royal_batchmate_load_textdomain' );
function royal_batchmate_load_textdomain(): void {
    load_plugin_textdomain(
        'royal-batchmate',
        false,
        dirname( plugin_basename( __FILE__ ) ) . '/languages'
    );
}

// ============================================================

// ============================================================
// LOAD CORE SUBSYSTEMS & INCLUDES
// ============================================================
require_once ROYAL_BATCHMATE_PLUGIN_DIR . 'includes/helpers.php';
require_once ROYAL_BATCHMATE_PLUGIN_DIR . 'includes/migrations.php';
require_once ROYAL_BATCHMATE_PLUGIN_DIR . 'includes/enqueue.php';
require_once ROYAL_BATCHMATE_PLUGIN_DIR . 'includes/countries.php';
require_once ROYAL_BATCHMATE_PLUGIN_DIR . 'includes/bd-locations.php';
require_once ROYAL_BATCHMATE_PLUGIN_DIR . 'includes/database.php';
require_once ROYAL_BATCHMATE_PLUGIN_DIR . 'includes/menu.php';
require_once ROYAL_BATCHMATE_PLUGIN_DIR . 'includes/rewrites.php';
require_once ROYAL_BATCHMATE_PLUGIN_DIR . 'includes/shortcode.php';
require_once ROYAL_BATCHMATE_PLUGIN_DIR . 'includes/form-handler.php';
require_once ROYAL_BATCHMATE_PLUGIN_DIR . 'includes/cron-jobs.php';
require_once ROYAL_BATCHMATE_PLUGIN_DIR . 'includes/automation-service.php';
require_once ROYAL_BATCHMATE_PLUGIN_DIR . 'includes/page-templates.php';
require_once ROYAL_BATCHMATE_PLUGIN_DIR . 'includes/admin-wp-dashboard-widget.php';
require_once ROYAL_BATCHMATE_PLUGIN_DIR . 'includes/member-profile-ajax.php';
require_once ROYAL_BATCHMATE_PLUGIN_DIR . 'includes/member-login-shortcode.php';

// ============================================================
// AJAX HANDLERS (must be loaded on every request for admin-ajax.php)
// ============================================================
require_once ROYAL_BATCHMATE_PLUGIN_DIR . 'includes/admin-events-ajax.php';
require_once ROYAL_BATCHMATE_PLUGIN_DIR . 'includes/admin-events-card.php';
require_once ROYAL_BATCHMATE_PLUGIN_DIR . 'includes/admin-applications-ajax.php';
require_once ROYAL_BATCHMATE_PLUGIN_DIR . 'includes/admin-departments-ajax.php';
require_once ROYAL_BATCHMATE_PLUGIN_DIR . 'includes/admin-programs-ajax.php';
require_once ROYAL_BATCHMATE_PLUGIN_DIR . 'includes/admin-members-list-ajax.php';
require_once ROYAL_BATCHMATE_PLUGIN_DIR . 'includes/admin-executive-committee-ajax.php';
require_once ROYAL_BATCHMATE_PLUGIN_DIR . 'includes/public-members-list-ajax.php';
require_once ROYAL_BATCHMATE_PLUGIN_DIR . 'includes/admin-settings-ajax.php';
require_once ROYAL_BATCHMATE_PLUGIN_DIR . 'includes/admin-payments-ajax.php';
require_once ROYAL_BATCHMATE_PLUGIN_DIR . 'includes/admin-expenses-ajax.php';
require_once ROYAL_BATCHMATE_PLUGIN_DIR . 'includes/admin-reports-ajax.php';
require_once ROYAL_BATCHMATE_PLUGIN_DIR . 'includes/admin-rawdata-ajax.php';
require_once ROYAL_BATCHMATE_PLUGIN_DIR . 'includes/admin-excel-import-ajax.php';
require_once ROYAL_BATCHMATE_PLUGIN_DIR . 'includes/admin-institutes-ajax.php';
require_once ROYAL_BATCHMATE_PLUGIN_DIR . 'includes/testbirthday.php';
require_once ROYAL_BATCHMATE_PLUGIN_DIR . 'includes/testeventcard.php';

// ============================================================
// ELEMENTOR WIDGETS
// ============================================================
add_action( 'elementor/widgets/register', function( $widgets_manager ): void {
    require_once ROYAL_BATCHMATE_PLUGIN_DIR . 'includes/elementor-latest-members.php';
    require_once ROYAL_BATCHMATE_PLUGIN_DIR . 'includes/elementor-batchmate-member-widget.php';
    require_once ROYAL_BATCHMATE_PLUGIN_DIR . 'includes/elementor-event-form-widget.php';
    $widgets_manager->register( new BatchMate_Latest_Members_Widget() );
    $widgets_manager->register( new BatchMate_Member_Widget() );
    $widgets_manager->register( new BatchMate_Event_Form_Widget() );
} );

// ============================================================
// PLUGIN UPDATER (GitHub Auto-Updates)
// ============================================================
require_once ROYAL_BATCHMATE_PLUGIN_DIR . 'includes/updater.php';

function royal_batchmate_initialize_updater(): void {
    if ( class_exists( 'Royal_BatchMate_Updater' ) ) {
        $update_url = 'https://raw.githubusercontent.com/mehdiakram/royal-batchmate/main/royal-batchmate.json';
        new Royal_BatchMate_Updater( ROYAL_BATCHMATE_PLUGIN_FILE, $update_url );
    }
}
add_action( 'init', 'royal_batchmate_initialize_updater' );

