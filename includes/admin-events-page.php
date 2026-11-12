<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/* =====================================================================
 * EVENTS ADMIN PAGE — Modern UI with Stats Cards + 3 Tabs
 * ===================================================================== */

global $wpdb;


$bm_events_org_label = batchmate_org_label( 'short' );


$event_table   = $wpdb->prefix . 'batchmate_event';
$app_table     = $wpdb->prefix . 'batchmate_event_application';
$members_table = $wpdb->prefix . 'batchmate_event_members';

$req_tab = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'registered';
if ( strpos( $req_tab, 'tab-' ) !== 0 ) {
    $req_tab = 'tab-' . $req_tab;
}
$valid_tabs = [ 'tab-registered', 'tab-applications', 'tab-events', 'tab-expenses' ];
if ( ! in_array( $req_tab, $valid_tabs, true ) ) {
    $req_tab = 'tab-registered';
}

$total_events       = (int) $wpdb->get_var( "SELECT COUNT(id) FROM $event_table" );
$active_event       = $wpdb->get_row( "SELECT id, event_name, event_date FROM $event_table WHERE is_active = 1 LIMIT 1" );
$total_applications = (int) $wpdb->get_var( "SELECT COUNT(id) FROM $app_table" );
$pending_apps       = (int) $wpdb->get_var( "SELECT COUNT(id) FROM $app_table WHERE status = 'pending'" );

// Total Registered strictly represents active club members from wp_batchmate_member
$bm_main_member_table = $wpdb->prefix . 'batchmate_member';
$total_registered     = (int) $wpdb->get_var( "SELECT COUNT(id) FROM $bm_main_member_table" );


$events_for_filter = $wpdb->get_results(
    "SELECT id, event_name, is_active FROM $event_table ORDER BY event_date DESC"
);

$active_event_name = $active_event ? $active_event->event_name : 'None';
$active_collection = 0;
$active_members = 0;
$active_guests_html = '';


$event_date_passed = false;
if ( $active_event && !empty($active_event->event_date) ) {
    $event_date_passed = ( strtotime($active_event->event_date) < strtotime(date('Y-m-d')) );
}

if ( $active_event ) {
    $active_id = $active_event->id;
    $active_members = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(id) FROM $members_table WHERE event_id = %d", $active_id ) );

    $active_apps = $wpdb->get_results( $wpdb->prepare( "
        SELECT ea.amount, ea.options_json 
        FROM $app_table ea 
        INNER JOIN $members_table em ON ea.id = em.application_id
        WHERE ea.event_id = %d
    ", $active_id ) );

    $guest_counts = array();
    foreach ( $active_apps as $app ) {
        $active_collection += floatval( $app->amount );
        if ( ! empty( $app->options_json ) ) {
            $raw_json = stripslashes($app->options_json);
            $opts = json_decode( $raw_json, true );
            if ( is_string( $opts ) ) {
                $opts = json_decode( $opts, true );
            }
            if ( is_array( $opts ) ) {
                foreach ( $opts as $opt ) {
                    $name = $opt['name'] ?? '';
                    $qty = intval( $opt['qty'] ?? 0 );
                    if ( $qty > 0 && $name ) {
                        if ( ! isset( $guest_counts[$name] ) ) $guest_counts[$name] = 0;
                        $guest_counts[$name] += $qty;
                    }
                }
            }
        }
    }
    
    if ( ! empty( $guest_counts ) ) {
        $g_arr = array();
        foreach ( $guest_counts as $name => $count ) {
            $g_arr[] = esc_html( $name . ': ' . $count );
        }
        $active_guests_html = implode( ', ', $g_arr );
    } else {
        $active_guests_html = '0';
    }

    $transactions = $wpdb->get_results( $wpdb->prepare( "SELECT trans_type, SUM(amount) as total FROM {$wpdb->prefix}batchmate_event_transactions WHERE event_id = %d GROUP BY trans_type", $active_id ) );
    
    $active_sponsorship = 0.0;
    $active_expense = 0.0;
    foreach ( $transactions as $t ) {
        if ( $t->trans_type === 'sponsor' ) $active_sponsorship = (float) $t->total;
        if ( $t->trans_type === 'expense' ) $active_expense = (float) $t->total;
    }
}
?>

<div class="wrap BatchMate-events-admin">

    <!-- -- Page Header -- -->
    <div class="BatchMate-page-header-animated BatchMate-ev-page-header">
        <div class="BatchMate-header-left">
            <h1><span class="dashicons dashicons-calendar-alt"></span> Events Management</h1>
            <p>Manage events, registrations, and applications from one place.</p>
        </div>
    </div>

    <?php if ( $active_event ) : ?>
    <a href="<?php echo admin_url('admin.php?page=batchmate_manage_events&tab=expenses#tab-expenses'); ?>" style="text-decoration:none; display:block; margin-bottom:20px;">
        <div style="background: linear-gradient(135deg, #0f2756, #1d4ed8); border-radius: 12px; padding: 25px; color: #fff; box-shadow: 0 4px 15px rgba(0,0,0,0.1); position:relative; overflow:hidden; transition: transform 0.2s;" onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform='translateY(0)'">
            <div style="position:absolute; top:-20px; right:-20px; opacity:0.1;"><span class="dashicons dashicons-megaphone" style="font-size:150px; width:150px; height:150px;"></span></div>
            <div style="position:relative; z-index:1;">
                <h3 style="margin:0 0 5px 0; font-size:24px; color:#fff; display:flex; align-items:center; gap:10px;">
                    <span class="dashicons dashicons-calendar-alt" style="margin-top:4px;"></span> 
                    <?php echo esc_html($active_event->event_name); ?>
                    <?php if ( !$event_date_passed ) : ?>
                    <span style="background:#ef4444; color:#fff; font-size:11px; padding:3px 8px; border-radius:20px; text-transform:uppercase; font-weight:bold; letter-spacing:1px; margin-left:10px; animation: pulse 2s infinite;">Live</span>
                    <?php endif; ?>
                </h3>
                <p style="margin:0 0 20px 0; font-size:14px; opacity:0.8;">Date: <?php echo date('d M, Y', strtotime($active_event->event_date)); ?></p>
                
                <div style="display:flex; gap:20px; flex-wrap:wrap;">
                    <div style="background:rgba(255,255,255,0.1); padding:15px 20px; border-radius:8px; flex:1; min-width:150px; border:1px solid rgba(255,255,255,0.2);">
                        <p style="margin:0; font-size:12px; opacity:0.8; text-transform:uppercase; letter-spacing:1px;">Registrations</p>
                        <p style="margin:5px 0 0; font-size:24px; font-weight:bold;"><?php echo number_format($active_members); ?></p>
                    </div>
                    <div style="background:rgba(255,255,255,0.1); padding:15px 20px; border-radius:8px; flex:1; min-width:150px; border:1px solid rgba(255,255,255,0.2);">
                        <p style="margin:0; font-size:12px; opacity:0.8; text-transform:uppercase; letter-spacing:1px;">Collection</p>
                        <p style="margin:5px 0 0; font-size:24px; font-weight:bold;">&#2547; <?php echo number_format($active_collection, 2); ?></p>
                    </div>
                    <div style="background:rgba(255,255,255,0.1); padding:15px 20px; border-radius:8px; flex:1; min-width:150px; border:1px solid rgba(255,255,255,0.2);">
                        <p style="margin:0; font-size:12px; opacity:0.8; text-transform:uppercase; letter-spacing:1px;">Sponsorship</p>
                        <p style="margin:5px 0 0; font-size:24px; font-weight:bold;">&#2547; <?php echo number_format($active_sponsorship, 2); ?></p>
                    </div>
                    <div style="background:rgba(255,255,255,0.1); padding:15px 20px; border-radius:8px; flex:1; min-width:150px; border:1px solid rgba(255,255,255,0.2);">
                        <p style="margin:0; font-size:12px; opacity:0.8; text-transform:uppercase; letter-spacing:1px;">Expense</p>
                        <p style="margin:5px 0 0; font-size:24px; font-weight:bold; color:#fca5a5;">&#2547; <?php echo number_format($active_expense, 2); ?></p>
                    </div>
                </div>
            </div>
        </div>
    </a>
    <?php endif; ?>

    <!-- --------------------------------------------------------
         STAT CARDS
    -------------------------------------------------------- -->
    <div class="BatchMate-ev-cards">

        <div class="BatchMate-ev-card BatchMate-ev-card--blue">
            <div class="BatchMate-ev-card-body">
                <div class="BatchMate-ev-card-number"><?php echo $total_events; ?></div>
                <div class="BatchMate-ev-card-label">Total Events</div>
            </div>
            <div class="BatchMate-ev-card-icon">
                <span class="dashicons dashicons-calendar-alt"></span>
            </div>
        </div>

        <div class="BatchMate-ev-card BatchMate-ev-card--green BatchMate-ev-card--clickable" data-goto-tab="tab-registered" title="View Registered Members">
            <div class="BatchMate-ev-card-body">
                <div class="BatchMate-ev-card-number"><?php echo $total_registered; ?></div>
                <div class="BatchMate-ev-card-label">Total Registered</div>
            </div>
            <div class="BatchMate-ev-card-icon">
                <span class="dashicons dashicons-yes-alt"></span>
            </div>
        </div>

        <div class="BatchMate-ev-card BatchMate-ev-card--orange BatchMate-ev-card--clickable" data-goto-tab="tab-applications" title="View Pending Applications">
            <div class="BatchMate-ev-card-body">
                <div class="BatchMate-ev-card-number"><?php echo $pending_apps; ?></div>
                <div class="BatchMate-ev-card-label">Pending Applications</div>
            </div>
            <div class="BatchMate-ev-card-icon">
                <span class="dashicons dashicons-clock"></span>
            </div>
        </div>

        <div class="BatchMate-ev-card BatchMate-ev-card--purple">
            <div class="BatchMate-ev-card-body">
                <div class="BatchMate-ev-card-number" style="font-size:1.2em;line-height:1.3;margin-bottom:8px;">
                    <?php echo esc_html( mb_strimwidth( $active_event_name, 0, 22, '…' ) ); ?>
                </div>
                <div class="BatchMate-ev-card-label" style="font-size:11.5px;opacity:0.95;line-height:1.5;">
                    <div style="margin-bottom:3px;"><strong>Members:</strong> <?php echo $active_members; ?> &nbsp;|&nbsp; <strong>Guests:</strong> <?php echo $active_guests_html; ?></div>
                    <div><strong>Collection:</strong> <span style="font-size:12px;font-weight:bold;color:#ffeb3b;">&#2547; <?php echo number_format($active_collection, 2); ?></span></div>
                </div>
            </div>
            <div class="BatchMate-ev-card-icon">
                <span class="dashicons dashicons-megaphone"></span>
            </div>
        </div>

    </div><!-- /BatchMate-ev-cards -->


    <!-- --------------------------------------------------------
         TAB NAVIGATION — Modern pill style
    -------------------------------------------------------- -->
    <div class="BatchMate-ev-tab-bar">
        <nav class="BatchMate-ev-tabs" id="BatchMate-event-tab-nav">
            <a href="<?php echo esc_url( admin_url('admin.php?page=batchmate_manage_events&tab=registered') ); ?>" class="BatchMate-ev-tab <?php echo $req_tab === 'tab-registered' ? 'active' : ''; ?>" data-tab="tab-registered">
                <span class="dashicons dashicons-groups"></span>
                <span class="tab-label">Registered</span>
                <span class="tab-badge BatchMate-ev-badge--green"><?php echo $total_registered; ?></span>
            </a>
            <a href="<?php echo esc_url( admin_url('admin.php?page=batchmate_manage_events&tab=applications') ); ?>" class="BatchMate-ev-tab <?php echo $req_tab === 'tab-applications' ? 'active' : ''; ?>" data-tab="tab-applications">
                <span class="dashicons dashicons-list-view"></span>
                <span class="tab-label">Applications</span>
                <span class="tab-badge BatchMate-ev-badge--orange"><?php echo $pending_apps; ?></span>
            </a>
            <a href="<?php echo esc_url( admin_url('admin.php?page=batchmate_manage_events&tab=events') ); ?>" class="BatchMate-ev-tab <?php echo $req_tab === 'tab-events' ? 'active' : ''; ?>" data-tab="tab-events">
                <span class="dashicons dashicons-calendar-alt"></span>
                <span class="tab-label">Events</span>
                <span class="tab-badge BatchMate-ev-badge--blue"><?php echo $total_events; ?></span>
            </a>
            <a href="<?php echo esc_url( admin_url('admin.php?page=batchmate_manage_events&tab=expenses') ); ?>" class="BatchMate-ev-tab <?php echo $req_tab === 'tab-expenses' ? 'active' : ''; ?>" data-tab="tab-expenses">
                <span class="dashicons dashicons-chart-pie"></span>
                <span class="tab-label">Expenses & Report</span>
            </a>
        </nav>
    </div>

    <?php
    // ------------------------------------------------------------
    // TABS CONTENT
    // ------------------------------------------------------------
    $events_dir = plugin_dir_path( __FILE__ ) . 'events/';
    include_once $events_dir . 'tabs/tab-registered.php';
    include_once $events_dir . 'tabs/tab-applications.php';
    include_once $events_dir . 'tabs/tab-events.php';
    include_once $events_dir . 'tabs/tab-expenses.php';
    ?>
</div><!-- /wrap -->

<!-- ============================================================
     EVENT MODALS
============================================================ -->
<?php
include_once $events_dir . 'modals/modal-event-form.php';
include_once $events_dir . 'modals/modal-card-setup.php';
include_once $events_dir . 'modals/modal-card-preview.php';
include_once $events_dir . 'modals/modal-edit-app.php';
include_once $events_dir . 'modals/modal-app-preview.php';
include_once $events_dir . 'modals/modal-transaction.php';
?>

<!-- ============================================================
     ASSETS & RUNTIME CONFIGURATION
============================================================ -->
<?php
$bm_events_css_ver = defined( 'ROYAL_BATCHMATE_VERSION' ) ? ROYAL_BATCHMATE_VERSION . '.' . (string) @filemtime( ROYAL_BATCHMATE_PLUGIN_DIR . 'assets/admin-events.css' ) : (string) time();
wp_enqueue_style( 'batchmate-admin-events-css', ROYAL_BATCHMATE_PLUGIN_URL . 'assets/admin-events.css', [ 'admin-css' ], $bm_events_css_ver );

$bm_base_deps = [ 'jquery', 'datatables-js', 'sweetalert2-js', 'select2-js' ];
$bm_ev_modules = [
    'batchmate-events-core'         => 'assets/events/events-core.js',
    'batchmate-events-registered'   => 'assets/events/events-registered.js',
    'batchmate-events-applications' => 'assets/events/events-applications.js',
    'batchmate-events-crud'         => 'assets/events/events-crud.js',
    'batchmate-events-card'         => 'assets/events/events-card.js',
    'batchmate-events-finance'      => 'assets/events/events-finance.js',
];

$prev_handle = $bm_base_deps;
foreach ( $bm_ev_modules as $handle => $rel_path ) {
    $mod_ver = defined( 'ROYAL_BATCHMATE_VERSION' ) ? ROYAL_BATCHMATE_VERSION . '.' . (string) @filemtime( ROYAL_BATCHMATE_PLUGIN_DIR . $rel_path ) : (string) time();
    wp_enqueue_script( $handle, ROYAL_BATCHMATE_PLUGIN_URL . $rel_path, $prev_handle, $mod_ver, true );
    $prev_handle = [ $handle ];
}

$bm_events_ver = defined( 'ROYAL_BATCHMATE_VERSION' ) ? ROYAL_BATCHMATE_VERSION . '.' . (string) @filemtime( ROYAL_BATCHMATE_PLUGIN_DIR . 'assets/admin-events.js' ) : (string) time();
wp_enqueue_script( 'batchmate-admin-events-js', ROYAL_BATCHMATE_PLUGIN_URL . 'assets/admin-events.js', [ 'batchmate-events-finance' ], $bm_events_ver, true );
?>
<script type="text/javascript">
window.batchmate_events_cfg = window.batchmate_events_cfg || {
    noPhoto: <?php echo json_encode( ROYAL_BATCHMATE_PLUGIN_URL . 'assets/images/noprofile.png' ); ?>,
    orgLabel: <?php echo json_encode( $bm_events_org_label ); ?>,
    noPhotoUrl: <?php echo json_encode( get_template_directory_uri() . '/assets/images/no-photo.png' ); ?>,
    reqTab: <?php echo json_encode( $req_tab ); ?>
};
</script>
