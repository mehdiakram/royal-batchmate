<?php
// Exit if accessed directly
if ( ! defined( "ABSPATH" ) ) exit;

function BatchMate_admin_menu() {
    $icon_url = file_exists( ROYAL_BATCHMATE_PLUGIN_DIR . 'assets/images/icon-20x20.png' ) 
        ? ROYAL_BATCHMATE_PLUGIN_URL . 'assets/images/icon-20x20.png' 
        : 'dashicons-groups';
    
    $main_menu_label     = batchmate_get_label("main_menu");
    $settings_menu_label = batchmate_get_label("settings_menu");
    $members_label       = batchmate_get_label("members");

    $settings           = get_option( 'batchmate_settings', array() );
    $show_test_birthday = $settings['show_test_birthday'] ?? 'on';
    $show_test_event_card = $settings['show_test_event_card'] ?? 'on';
    $show_raw_excel     = $settings['show_raw_excel']     ?? 'on';
    
    // Main Menu
    add_menu_page( $main_menu_label, $main_menu_label, "manage_options", "batchmate_dashboard", "BatchMate_render_dashboard_page", $icon_url, 30 );
    
    // Submenus
    add_submenu_page( "batchmate_dashboard", "Dashboard", "Dashboard", "manage_options", "batchmate_dashboard", "BatchMate_render_dashboard_page" );
    add_submenu_page( "batchmate_dashboard", "Applications", "Applications", "manage_options", "batchmate_manage_applications", "BatchMate_render_applications_page" );
    add_submenu_page( "batchmate_dashboard", $members_label, $members_label, "manage_options", "batchmate_manage_members", "BatchMate_render_members_list_page" );
    add_submenu_page( "batchmate_dashboard", "Committees", "Committees", "manage_options", "batchmate_manage_ec", "BatchMate_render_executive_committee_page" );
    add_submenu_page( "batchmate_dashboard", "Events", "Events", "manage_options", "batchmate_manage_events", "BatchMate_render_events_page" );
    // Alias for backward compatibility with 'batchmate_events' slug
    add_submenu_page( null, "Events", "Events", "manage_options", "batchmate_events", "BatchMate_render_events_page" );
    add_submenu_page( "batchmate_dashboard", "Payments", "Payments", "manage_options", "batchmate_manage_payments", "BatchMate_render_payments_page" );
    add_submenu_page( "batchmate_dashboard", "Expenses", "Expenses", "manage_options", "batchmate_manage_expenses", "BatchMate_render_expenses_page" );
    add_submenu_page( "batchmate_dashboard", "Reports", "Reports", "manage_options", "batchmate_manage_reports", "BatchMate_render_reports_page" );

    // Optional Menus: Raw Data & Excel Import
    if ( $show_raw_excel === 'on' ) {
        add_submenu_page( "batchmate_dashboard", "Raw Data", "Raw Data", "manage_options", "batchmate_raw_data", "BatchMate_render_raw_data_page" );
        add_submenu_page( "batchmate_dashboard", "Excel Import", "Excel Import", "manage_options", "batchmate_excel_import", "BatchMate_render_excel_import_page" );
    }

    // Optional Menu: Test Birthday
    if ( $show_test_birthday === 'on' ) {
        add_submenu_page( "batchmate_dashboard", "Test Birthday", "Test Birthday", "manage_options", "batchmate_test_birthday", "batchmate_render_test_birthday_page" );
    }

    // Optional Menu: Test Event Card
    if ( $show_test_event_card === 'on' ) {
        add_submenu_page( "batchmate_dashboard", "Test Event Card", "Test Event Card", "manage_options", "batchmate_test_event_card", "batchmate_render_test_event_card_page" );
    }

    add_submenu_page( "batchmate_dashboard", $settings_menu_label, $settings_menu_label, "manage_options", "batchmate_manage_settings", "BatchMate_render_settings_page" );
}
add_action( "admin_menu", "BatchMate_admin_menu" );

// Backward compatibility redirect for old slug 'batchmate_events' -> 'batchmate_manage_events'
add_action( 'admin_init', function() {
    if ( isset( $_GET['page'] ) && $_GET['page'] === 'batchmate_events' ) {
        $tab = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : '';
        wp_safe_redirect( admin_url( 'admin.php?page=batchmate_manage_events' . ( $tab ? '&tab=' . $tab : '' ) ) );
        exit;
    }
} );

function BatchMate_render_dashboard_page() { require_once ROYAL_BATCHMATE_PLUGIN_DIR . "includes/admin-dashboard-page.php"; }
function BatchMate_render_applications_page() { require_once ROYAL_BATCHMATE_PLUGIN_DIR . "includes/admin-applications-page.php"; }
function BatchMate_render_members_list_page() { require_once ROYAL_BATCHMATE_PLUGIN_DIR . "includes/admin-members-list-page.php"; }
function BatchMate_render_executive_committee_page() { require_once ROYAL_BATCHMATE_PLUGIN_DIR . "includes/admin-executive-committee-page.php"; }
function BatchMate_render_events_page() { require_once ROYAL_BATCHMATE_PLUGIN_DIR . "includes/admin-events-page.php"; }
function BatchMate_render_payments_page() { require_once ROYAL_BATCHMATE_PLUGIN_DIR . "includes/admin-payments-page.php"; }
function BatchMate_render_expenses_page() { require_once ROYAL_BATCHMATE_PLUGIN_DIR . "includes/admin-expenses-page.php"; }
function BatchMate_render_reports_page() { require_once ROYAL_BATCHMATE_PLUGIN_DIR . "includes/admin-reports-page.php"; }
function BatchMate_render_raw_data_page() { require_once ROYAL_BATCHMATE_PLUGIN_DIR . "includes/admin-rawdata-import.php"; }
function BatchMate_render_excel_import_page() { require_once ROYAL_BATCHMATE_PLUGIN_DIR . "includes/admin-excel-import-page.php"; }
function BatchMate_render_settings_page() { require_once ROYAL_BATCHMATE_PLUGIN_DIR . "includes/admin-settings-page.php"; }

