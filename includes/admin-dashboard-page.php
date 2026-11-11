<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

global $wpdb;

// Load settings for Organization Name
$settings = get_option('batchmate_settings', array());
$org_name = esc_js( batchmate_org_label( 'short' ) );

// --- 1. Data collection for Status Cards ---
$member_table = $wpdb->prefix . 'batchmate_member';
$app_table = $wpdb->prefix . 'batchmate_application';
$dept_table = $wpdb->prefix . 'batchmate_department';
$comm_table = $wpdb->prefix . 'batchmate_committee';
$prog_table = $wpdb->prefix . 'batchmate_program';
$payments_table = $wpdb->prefix . 'batchmate_payments';
$expenses_table = $wpdb->prefix . 'batchmate_expenses';

$total_members = $wpdb->get_var( "SELECT COUNT(id) FROM $member_table" );
$pending_apps = $wpdb->get_var( "SELECT COUNT(id) FROM $app_table WHERE status = 'pending'" );
$total_institutions = $wpdb->get_var( "SELECT COUNT(DISTINCT TRIM(institution)) FROM $member_table WHERE institution IS NOT NULL AND TRIM(institution) != ''" );
if ( empty( $total_institutions ) && ! empty( $dept_table ) ) {
    $total_institutions = (int) $wpdb->get_var( "SELECT COUNT(id) FROM $dept_table" );
}
$total_depts = intval( $total_institutions );
$total_committees = $wpdb->get_var( "SELECT COUNT(id) FROM $comm_table" );

// NEW QUERIES FOR MALE/FEMALE MEMBERS
$male_members = $wpdb->get_var( "SELECT COUNT(id) FROM $member_table WHERE gender = 'Male'" );
$female_members = $wpdb->get_var( "SELECT COUNT(id) FROM $member_table WHERE gender = 'Female'" );

// --- 2. Financial Info for Current Year ---
$current_year_start = date('Y-01-01');
$current_year_end = date('Y-12-31');

// Queries for Opening Balance (Balance B/F)
$lifetime_collection_before_year = $wpdb->get_var( $wpdb->prepare(
    "SELECT COALESCE(SUM(amount), 0) FROM {$payments_table} WHERE payment_date < %s", $current_year_start
) );
$lifetime_expense_before_year = $wpdb->get_var( $wpdb->prepare(
    "SELECT COALESCE(SUM(amount), 0) FROM {$expenses_table} WHERE expense_date < %s", $current_year_start
) );
$opening_balance = floatval($lifetime_collection_before_year) - floatval($lifetime_expense_before_year);


$total_income_year = $wpdb->get_var( $wpdb->prepare(
    "SELECT SUM(amount) FROM {$payments_table} WHERE payment_date >= %s AND payment_date <= %s",
    $current_year_start, $current_year_end
) );
$total_expense_year = $wpdb->get_var( $wpdb->prepare(
    "SELECT SUM(amount) FROM {$expenses_table} WHERE expense_date >= %s AND expense_date <= %s",
    $current_year_start, $current_year_end
) );

$total_income_year = floatval($total_income_year);
$total_expense_year = floatval($total_expense_year);
$net_balance_year = $total_income_year - $total_expense_year;


// --- 3. Chart & Table 1: Members by group ---
$dept_data_raw = $wpdb->get_results( $wpdb->prepare(
    "SELECT 
        CASE WHEN m.edu_group IS NULL OR TRIM(m.edu_group) = '' THEN 'No Group Assigned' ELSE TRIM(m.edu_group) END as dept_name, 
        COUNT(m.id) as member_count
     FROM %i AS m
     GROUP BY CASE WHEN m.edu_group IS NULL OR TRIM(m.edu_group) = '' THEN 'No Group Assigned' ELSE TRIM(m.edu_group) END
     ORDER BY member_count DESC",
     $member_table
) );

// Clean & merge any whitespace or empty string into 'No Group Assigned'
$clean_dept = array();
$no_group_cnt = 0;
foreach ( (array) $dept_data_raw as $row ) {
    $gname = trim( preg_replace( '/[\s\x00-\x1F\x7F\xA0\x{00A0}]+/u', ' ', (string) $row->dept_name ) );
    if ( $gname === '' || strcasecmp( $gname, 'No Group Assigned' ) === 0 ) {
        $no_group_cnt += (int) $row->member_count;
    } else {
        $clean_dept[$gname] = ($clean_dept[$gname] ?? 0) + (int) $row->member_count;
    }
}
$dept_data = array();
if ( $no_group_cnt > 0 ) {
    $dept_data[] = (object) array( 'dept_name' => 'No Group Assigned', 'member_count' => $no_group_cnt );
}
foreach ( $clean_dept as $gname => $cnt ) {
    $dept_data[] = (object) array( 'dept_name' => $gname, 'member_count' => $cnt );
}
// Sort by count desc
usort( $dept_data, function($a, $b) {
    if ($a->dept_name === 'No Group Assigned') return -1;
    if ($b->dept_name === 'No Group Assigned') return 1;
    return $b->member_count <=> $a->member_count;
});

$total_dept_members = array_sum(array_column($dept_data, 'member_count'));
$dept_chart_labels = [];
$dept_chart_data = [];
foreach ( $dept_data as $row ) {
    if ($row->member_count > 0) {
        $dept_chart_labels[] = $row->dept_name;
        $dept_chart_data[] = (int) $row->member_count;
    }
}

// --- 4. Chart & Table 2: Members by Board ---
$prog_data_raw = $wpdb->get_results( $wpdb->prepare(
    "SELECT 
        CASE WHEN m.board IS NULL OR TRIM(m.board) = '' THEN 'No Board Assigned' ELSE TRIM(m.board) END as program_name, 
        COUNT(m.id) as member_count
     FROM %i AS m
     GROUP BY CASE WHEN m.board IS NULL OR TRIM(m.board) = '' THEN 'No Board Assigned' ELSE TRIM(m.board) END
     ORDER BY member_count DESC",
     $member_table
) );

$clean_prog = array();
$no_board_cnt = 0;
foreach ( (array) $prog_data_raw as $row ) {
    $bname = trim( preg_replace( '/[\s\x00-\x1F\x7F\xA0\x{00A0}]+/u', ' ', (string) $row->program_name ) );
    if ( $bname === '' || strcasecmp( $bname, 'No Board Assigned' ) === 0 ) {
        $no_board_cnt += (int) $row->member_count;
    } else {
        $clean_prog[$bname] = ($clean_prog[$bname] ?? 0) + (int) $row->member_count;
    }
}
$prog_data = array();
if ( $no_board_cnt > 0 ) {
    $prog_data[] = (object) array( 'program_name' => 'No Board Assigned', 'member_count' => $no_board_cnt );
}
foreach ( $clean_prog as $bname => $cnt ) {
    $prog_data[] = (object) array( 'program_name' => $bname, 'member_count' => $cnt );
}
usort( $prog_data, function($a, $b) {
    if ($a->program_name === 'No Board Assigned') return -1;
    if ($b->program_name === 'No Board Assigned') return 1;
    return $b->member_count <=> $a->member_count;
});

$total_prog_members = array_sum(array_column($prog_data, 'member_count'));
$prog_chart_labels = [];
$prog_chart_data = [];
foreach ( $prog_data as $row ) {
    if ($row->member_count > 0) {
        $prog_chart_labels[] = $row->program_name;
        $prog_chart_data[] = (int) $row->member_count;
    }
}
// --- 4.1 New Queries for Monthly Financials (Current Year) ---
$monthly_income_data = array_fill(1, 12, 0);
$monthly_expense_data = array_fill(1, 12, 0);

$monthly_incomes = $wpdb->get_results( $wpdb->prepare(
    "SELECT MONTH(payment_date) as m, SUM(amount) as total FROM {$payments_table} WHERE YEAR(payment_date) = %d GROUP BY m",
    date('Y')
) );
foreach($monthly_incomes as $row) { $monthly_income_data[(int)$row->m] = (float)$row->total; }

$monthly_expenses = $wpdb->get_results( $wpdb->prepare(
    "SELECT MONTH(expense_date) as m, SUM(amount) as total FROM {$expenses_table} WHERE YEAR(expense_date) = %d GROUP BY m",
    date('Y')
) );
foreach($monthly_expenses as $row) { $monthly_expense_data[(int)$row->m] = (float)$row->total; }

// --- 4.2 New Query for Members by Year ---
$members_by_year = $wpdb->get_results( "
    SELECT YEAR(joining_date) as y, COUNT(id) as total 
    FROM {$member_table} 
    WHERE (joining_date IS NOT NULL AND joining_date != '0000-00-00 00:00:00')
    GROUP BY y 
    ORDER BY y ASC
" );
$yearly_chart_labels = [];
$yearly_chart_data = [];
foreach($members_by_year as $row) {
    if ($row->y) {
        $yearly_chart_labels[] = $row->y;
        $yearly_chart_data[] = (int)$row->total;
    }
}


// --- 7. Live Event Data ---
$event_table = $wpdb->prefix . 'batchmate_event';
$app_table_ev = $wpdb->prefix . 'batchmate_event_application';
$mem_table_ev = $wpdb->prefix . 'batchmate_event_members';
$trans_table_ev = $wpdb->prefix . 'batchmate_event_transactions';

$active_event = $wpdb->get_row( "SELECT id, event_name, event_date FROM $event_table WHERE is_active = 1 LIMIT 1" );

// Check if event date has passed
$event_date_passed = false;
if ( $active_event && !empty($active_event->event_date) ) {
    $event_date_passed = ( strtotime($active_event->event_date) < strtotime(date('Y-m-d')) );
}

$active_event_stats = null;
if ( $active_event ) {
    $ev_id = $active_event->id;
    // Registrations
    $regs = (int) $wpdb->get_var( $wpdb->prepare("SELECT COUNT(id) FROM $mem_table_ev WHERE event_id = %d", $ev_id) );
    
    // Collection
    $collection = (float) $wpdb->get_var( $wpdb->prepare( "
        SELECT SUM(ea.amount) 
        FROM $app_table_ev ea
        INNER JOIN $mem_table_ev em ON ea.id = em.application_id
        WHERE ea.event_id = %d
    ", $ev_id ) );

    // Transactions
    $transactions = $wpdb->get_results( $wpdb->prepare( "SELECT trans_type, SUM(amount) as total FROM $trans_table_ev WHERE event_id = %d GROUP BY trans_type", $ev_id ) );
    
    $sponsorship = 0.0;
    $expense = 0.0;
    foreach ( $transactions as $t ) {
        if ( $t->trans_type === 'sponsor' ) $sponsorship = (float) $t->total;
        if ( $t->trans_type === 'expense' ) $expense = (float) $t->total;
    }

    $active_event_stats = array(
        'registrations' => $regs,
        'collection' => $collection,
        'sponsorship' => $sponsorship,
        'expense' => $expense,
        'balance' => ($collection + $sponsorship) - $expense
    );
}

// --- 5. Pass Data to Chart.js ---
$puaa_dashboard_data = array(
    'deptLabels'        => $dept_chart_labels,
    'deptData'          => $dept_chart_data,
    'progLabels'        => $prog_chart_labels, 
    'progData'          => $prog_chart_data,   
    'orgName'           => $org_name,
    'genderData'        => [ (int)$male_members, (int)$female_members ],
    'monthlyIncome'     => array_values($monthly_income_data),
    'monthlyExpense'    => array_values($monthly_expense_data),
    'yearlyLabels'      => $yearly_chart_labels,
    'yearlyData'        => $yearly_chart_data,
    'activeEventName'   => $active_event ? esc_js($active_event->event_name) : '',
    'activeEventDate'   => $active_event ? esc_js($active_event->event_date) : '',
);
wp_localize_script( 'chart-js', 'puaaDashboardData', $puaa_dashboard_data );

?>
<div class="wrap BatchMate-dashboard">
    <div class="BatchMate-page-header-animated">
        <div class="BatchMate-header-left">
            <h1><span class="dashicons dashicons-dashboard"></span> <?php echo esc_html(batchmate_get_label('batchmate')); ?> Dashboard</h1>
            <p>Welcome! Here's an overview of your organization's data.</p>
        </div>
    </div>
    
    <?php if ( $active_event && $active_event_stats && !$event_date_passed ) : ?>
    <h2 style="font-size: 1.5em; margin-top: 20px; border-bottom: 1px solid #eee; padding-bottom: 5px;">Live Event Status</h2>
    <a href="<?php echo admin_url('admin.php?page=batchmate_manage_events&tab=expenses#tab-expenses'); ?>" style="text-decoration:none; display:block; margin-bottom:20px;">
        <div style="background: linear-gradient(135deg, #0f2756, #1d4ed8); border-radius: 12px; padding: 25px; color: #fff; box-shadow: 0 4px 15px rgba(0,0,0,0.1); position:relative; overflow:hidden; transition: transform 0.2s;" onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform='translateY(0)'">
            <div style="position:absolute; top:-20px; right:-20px; opacity:0.1;"><span class="dashicons dashicons-megaphone" style="font-size:150px; width:150px; height:150px;"></span></div>
            <div style="position:relative; z-index:1;">
                <h3 style="margin:0 0 5px 0; font-size:24px; color:#fff; display:flex; align-items:center; gap:10px;">
                    <span class="dashicons dashicons-calendar-alt" style="margin-top:4px;"></span> 
                    <?php echo esc_html($active_event->event_name); ?>
                    <span style="background:#ef4444; color:#fff; font-size:11px; padding:3px 8px; border-radius:20px; text-transform:uppercase; font-weight:bold; letter-spacing:1px; margin-left:10px; animation: pulse 2s infinite;">Live</span>
                </h3>
                <p style="margin:0 0 20px 0; font-size:14px; opacity:0.8;">Date: <?php echo date('d M, Y', strtotime($active_event->event_date)); ?></p>
                
                <div style="display:flex; gap:20px; flex-wrap:wrap; margin-bottom: 20px;">
                    <div style="background:rgba(255,255,255,0.1); padding:15px 20px; border-radius:8px; flex:1; min-width:150px; border:1px solid rgba(255,255,255,0.2);">
                        <p style="margin:0; font-size:12px; opacity:0.8; text-transform:uppercase; letter-spacing:1px;">Registrations</p>
                        <p style="margin:5px 0 0; font-size:24px; font-weight:bold;"><?php echo number_format($active_event_stats['registrations']); ?></p>
                    </div>
                    <div style="background:rgba(255,255,255,0.1); padding:15px 20px; border-radius:8px; flex:1; min-width:150px; border:1px solid rgba(255,255,255,0.2);">
                        <p style="margin:0; font-size:12px; opacity:0.8; text-transform:uppercase; letter-spacing:1px;">Collection</p>
                        <p style="margin:5px 0 0; font-size:24px; font-weight:bold;">&#2547; <?php echo number_format($active_event_stats['collection'], 2); ?></p>
                    </div>
                    <div style="background:rgba(255,255,255,0.1); padding:15px 20px; border-radius:8px; flex:1; min-width:150px; border:1px solid rgba(255,255,255,0.2);">
                        <p style="margin:0; font-size:12px; opacity:0.8; text-transform:uppercase; letter-spacing:1px;">Sponsorship</p>
                        <p style="margin:5px 0 0; font-size:24px; font-weight:bold;">&#2547; <?php echo number_format($active_event_stats['sponsorship'], 2); ?></p>
                    </div>
                    <div style="background:rgba(255,255,255,0.1); padding:15px 20px; border-radius:8px; flex:1; min-width:150px; border:1px solid rgba(255,255,255,0.2);">
                        <p style="margin:0; font-size:12px; opacity:0.8; text-transform:uppercase; letter-spacing:1px;">Expense</p>
                        <p style="margin:5px 0 0; font-size:24px; font-weight:bold; color:#fca5a5;">&#2547; <?php echo number_format($active_event_stats['expense'], 2); ?></p>
                    </div>
                </div>

                <div id="event-countdown-container" style="background:rgba(0,0,0,0.2); padding:15px; border-radius:8px; text-align:center; border:1px solid rgba(255,255,255,0.1);">
                    <h4 style="margin:0 0 10px 0; color:#fff; text-transform:uppercase; letter-spacing:2px; font-size:12px;">Event Starts In</h4>
                    <div id="event-countdown" style="display:flex; justify-content:center; gap:15px; font-family:monospace; font-size:24px; font-weight:bold;">
                        <!-- Countdown goes here -->
                    </div>
                </div>

            </div>
        </div>
    </a>
    <style>
        @keyframes pulse { 0% { opacity: 1; } 50% { opacity: 0.5; } 100% { opacity: 1; } }
        #event-countdown div { display:flex; flex-direction:column; align-items:center; }
        #event-countdown span { font-size:10px; text-transform:uppercase; opacity:0.7; letter-spacing:1px; margin-top:2px; font-family:sans-serif;}
    </style>
    <?php endif; ?>
    
    <h2 style="font-size: 1.5em; margin-top: 20px; border-bottom: 1px solid #eee; padding-bottom: 5px;">Financial Summary (This Year)</h2>
    <div class="BatchMate-dashboard-cards" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); margin-bottom: 20px;">
        
        <div class="BatchMate-card" style="border-color: #826eb4;">
            <div class="card-number">৳ <?php echo number_format($opening_balance, 2); ?></div>
            <div class="card-title">Opening Balance (B/F)</div>
            <div class="card-icon"><span class="dashicons dashicons-wallet"></span></div>
        </div>

        <div class="BatchMate-card" style="border-color: #28a745;">
            <div class="card-number">৳ <?php echo number_format($total_income_year, 2); ?></div>
            <div class="card-title">Total Collection</div>
            <div class="card-icon"><span class="dashicons dashicons-chart-bar"></span></div>
        </div>
        <div class="BatchMate-card" style="border-color: #dc3545;">
            <div class="card-number">৳ <?php echo number_format($total_expense_year, 2); ?></div>
            <div class="card-title">Total Expense</div>
            <div class="card-icon"><span class="dashicons dashicons-minus-o"></span></div>
        </div>
        <div class="BatchMate-card" style="border-color: #007bff;">
            <div class="card-number">৳ <?php echo number_format($net_balance_year, 2); ?></div>
            <div class="card-title">Net Balance</div>
            <div class="card-icon"><span class="dashicons dashicons-editor-break"></span></div>
        </div>
    </div>


    <h2 style="font-size: 1.5em; margin-top: 30px; border-bottom: 1px solid #eee; padding-bottom: 5px;">Membership & Application Status</h2>
    <div class="BatchMate-dashboard-cards">
        
        <a href="<?php echo admin_url('admin.php?page=batchmate_manage_members'); ?>" class="BatchMate-card" style="text-decoration:none;color:inherit;cursor:pointer;">
            <div class="card-number"><?php echo intval($total_members); ?></div>
            <div class="card-title">Total <?php echo esc_html(batchmate_get_label('members')); ?></div>
            <div class="card-icon"><span class="dashicons dashicons-groups"></span></div>
        </a>
        
        <div class="BatchMate-card" style="border-color: #007bff;">
            <div class="card-number"><?php echo intval($male_members); ?></div>
            <div class="card-title">Male Members</div>
            <div class="card-icon"><span class="dashicons dashicons-admin-users"></span></div>
        </div>
        
        <div class="BatchMate-card" style="border-color: #ff69b4;">
            <div class="card-number"><?php echo intval($female_members); ?></div>
            <div class="card-title">Female Members</div>
            <div class="card-icon"><span class="dashicons dashicons-groups"></span></div>
        </div>

        <a href="<?php echo admin_url('admin.php?page=batchmate_manage_applications'); ?>" class="BatchMate-card" style="text-decoration:none;color:inherit;cursor:pointer;">
            <div class="card-number"><?php echo intval($pending_apps); ?></div>
            <div class="card-title">Pending Applications</div>
            <div class="card-icon"><span class="dashicons dashicons-hourglass"></span></div>
        </a>
        <div class="BatchMate-card">
            <div class="card-number"><?php echo intval($total_depts); ?></div>
            <div class="card-title">Total Institution</div>
            <div class="card-icon"><span class="dashicons dashicons-building"></span></div>
        </div>
        <div class="BatchMate-card">
            <div class="card-number"><?php echo intval($total_committees); ?></div>
            <div class="card-title">Total Committees</div>
            <div class="card-icon"><span class="dashicons dashicons-sitemap"></span></div>
        </div>
    </div>
    
    <h2 style="font-size: 1.5em; margin-top: 30px; border-bottom: 1px solid #eee; padding-bottom: 5px;">Charts & Tables Overview</h2>
    
    <div id="tables-and-charts-area">
        
        <div class="BatchMate-dashboard-charts" id="charts-area">
            <div class="BatchMate-chart-container" style="grid-column: span 2;">
                <h2>Financials (Current Year)</h2>
                <canvas id="monthlyFinancialsChart" style="max-height: 300px;"></canvas>
            </div>
            
            <div class="BatchMate-chart-container">
                <h2>Members Growth by Year</h2>
                <canvas id="yearlyMembersChart"></canvas>
            </div>
            <div class="BatchMate-chart-container">
                <h2>Gender Distribution</h2>
                <canvas id="genderPieChart"></canvas>
            </div>

            <div class="BatchMate-chart-container">
                <h2>Members by group</h2>
                <canvas id="departmentMemberChart"></canvas>
            </div>
            <div class="BatchMate-chart-container">
                <h2>Members by Board</h2>
                <canvas id="programMemberChart"></canvas>
            </div>
        </div>
        
        <div id="tables-print-area" style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 30px;">
            
            <div class="BatchMate-report-table-container" id="dept-table-container">
                <h2>Members by group</h2>
                <table class="wp-list-table widefat striped" style="width:100%">
                    <thead>
                        <tr>
                            <th>Group</th>
                            <th style="width: 150px; text-align: right;">Member Count</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($dept_data)): ?>
                            <?php foreach ($dept_data as $row): ?>
                                <tr>
                                    <td><?php echo esc_html($row->dept_name); ?></td>
                                    <td style="text-align: right; font-weight: bold;"><?php echo number_format_i18n($row->member_count); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="2" style="text-align: center;">No group data found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                    <tfoot style="background-color: #f0f0f0;">
                        <tr>
                            <th style="text-align: right;">Total <?php echo esc_html(batchmate_get_label('members')); ?>:</th>
                            <th style="width: 150px; text-align: right;"><?php echo number_format_i18n($total_dept_members); ?></th>
                        </tr>
                    </tfoot>
                </table>
                <button class="button button-secondary print-table-btn" data-target="dept-table-container" data-title="Members by group">
                    <span class="dashicons dashicons-printer"></span> Print Group
                </button>
            </div>

            <div class="BatchMate-report-table-container" id="prog-table-container">
                <h2>Members by Board</h2>
                <table class="wp-list-table widefat striped" style="width:100%">
                    <thead>
                        <tr>
                            <th>Board</th>
                            <th style="width: 150px; text-align: right;">Member Count</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($prog_data)): ?>
                            <?php foreach ($prog_data as $row): ?>
                                <tr>
                                    <td><?php echo esc_html($row->program_name); ?></td>
                                    <td style="text-align: right; font-weight: bold;"><?php echo number_format_i18n($row->member_count); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="2" style="text-align: center;">No board data found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                    <tfoot style="background-color: #f0f0f0;">
                        <tr>
                            <th style="text-align: right;">Total <?php echo esc_html(batchmate_get_label('members')); ?>:</th>
                            <th style="width: 150px; text-align: right;"><?php echo number_format_i18n($total_prog_members); ?></th>
                        </tr>
                    </tfoot>
                </table>
                <button class="button button-secondary print-table-btn" data-target="prog-table-container" data-title="Members by Board">
                    <span class="dashicons dashicons-printer"></span> Print Board
                </button>
            </div>
        </div>
        
        
        
    </div>

</div>

<script src="<?php echo esc_url( ROYAL_BATCHMATE_PLUGIN_URL . 'assets/chart/chart.js' ); ?>"></script>
<script type="text/javascript">
if (typeof puaaDashboardData === 'undefined') {
    window.puaaDashboardData = <?php echo json_encode( $puaa_dashboard_data ); ?>;
}
jQuery(document).ready(function($) {
    if (typeof Chart === 'undefined' || typeof puaaDashboardData === 'undefined') return;

    const generateColors = (data, hueStart = 0) => {
        const count = data.length > 0 ? data.length : 1; 
        return data.map((_, i) => {
            const hue = (hueStart + (i * (360 / count))) % 360;
            return {
                bg: `hsla(${hue}, 70%, 60%, 0.7)`,
                border: `hsla(${hue}, 70%, 60%, 1)`
            };
        });
    };

    const ctxDept = document.getElementById('departmentMemberChart');
    if (ctxDept && puaaDashboardData.deptData.length > 0) {
        const deptColors = generateColors(puaaDashboardData.deptData, 0); 
        new Chart(ctxDept.getContext('2d'), {
            type: 'bar',
            data: {
                labels: puaaDashboardData.deptLabels,
                datasets: [{
                    label: 'Members',
                    data: puaaDashboardData.deptData,
                    backgroundColor: deptColors.map(c => c.bg),
                    borderColor: deptColors.map(c => c.border),
                    borderWidth: 1
                }]
            },
            options: {
                indexAxis: 'x', 
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false }, title: { display: true, text: 'Group Wise Member' } },
                scales: {
                    x: { title: { display: true, text: 'Group' }, grid: { display: false } },
                    y: { beginAtZero: true, title: { display: true, text: 'Number of Members' } }
                }
            }
        });
    }

    const ctxProg = document.getElementById('programMemberChart');
    if (ctxProg && puaaDashboardData.progData.length > 0) {
        const progColors = generateColors(puaaDashboardData.progData, 150); 
        new Chart(ctxProg.getContext('2d'), {
            type: 'bar', 
            data: {
                labels: puaaDashboardData.progLabels,
                datasets: [{
                    label: 'Members',
                    data: puaaDashboardData.progData,
                    backgroundColor: progColors.map(c => c.bg), 
                    borderColor: progColors.map(c => c.border), 
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false }, title: { display: true, text: 'Board Wise Member' } },
                scales: {
                    x: { title: { display: true, text: 'Board' }, grid: { display: false } },
                    y: { beginAtZero: true, title: { display: true, text: 'Number of Members' } }
                }
            }
        });
    }

    // 1. Gender Pie Chart
    const ctxGender = document.getElementById('genderPieChart');
    if (ctxGender && puaaDashboardData.genderData && puaaDashboardData.genderData.length > 0) {
        new Chart(ctxGender.getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: ['Male', 'Female'],
                datasets: [{
                    data: puaaDashboardData.genderData,
                    backgroundColor: ['#007bff', '#ff69b4'],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'bottom' } }
            }
        });
    }

    // 2. Monthly Financials Line Chart
    const ctxMonthly = document.getElementById('monthlyFinancialsChart');
    if (ctxMonthly && puaaDashboardData.monthlyIncome && puaaDashboardData.monthlyExpense) {
        new Chart(ctxMonthly.getContext('2d'), {
            type: 'line',
            data: {
                labels: ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'],
                datasets: [
                    {
                        label: 'Collection',
                        data: puaaDashboardData.monthlyIncome,
                        borderColor: '#28a745',
                        backgroundColor: 'rgba(40, 167, 69, 0.1)',
                        fill: true,
                        tension: 0.4
                    },
                    {
                        label: 'Expense',
                        data: puaaDashboardData.monthlyExpense,
                        borderColor: '#dc3545',
                        backgroundColor: 'rgba(220, 53, 69, 0.1)',
                        fill: true,
                        tension: 0.4
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'top' } },
                scales: { y: { beginAtZero: true } }
            }
        });
    }

    // 3. Yearly Members Bar Chart
    const ctxYearly = document.getElementById('yearlyMembersChart');
    if (ctxYearly && puaaDashboardData.yearlyLabels && puaaDashboardData.yearlyLabels.length > 0) {
        new Chart(ctxYearly.getContext('2d'), {
            type: 'bar',
            data: {
                labels: puaaDashboardData.yearlyLabels,
                datasets: [{
                    label: 'New <?php echo esc_html(batchmate_get_label('members')); ?>',
                    data: puaaDashboardData.yearlyData,
                    backgroundColor: 'hsla(210, 70%, 60%, 0.7)',
                    borderColor: 'hsla(210, 70%, 60%, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true } }
            }
        });
    }

    // 4. Countdown Timer Logic
    if (puaaDashboardData.activeEventDate) {
        const countDownDate = new Date(puaaDashboardData.activeEventDate).getTime();
        const countdownEl = document.getElementById("event-countdown");
        const countdownContainer = document.getElementById("event-countdown-container");
        
        if (countdownEl && countDownDate > 0) {
            const timer = setInterval(function() {
                const now = new Date().getTime();
                const distance = countDownDate - now;

                if (distance < 0) {
                    clearInterval(timer);
                    countdownEl.innerHTML = "Event Started / Ended";
                    return;
                }

                const days = Math.floor(distance / (1000 * 60 * 60 * 24));
                const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
                const seconds = Math.floor((distance % (1000 * 60)) / 1000);

                countdownEl.innerHTML = 
                    "<div>" + days + "<span>Days</span></div> : " +
                    "<div>" + hours + "<span>Hours</span></div> : " +
                    "<div>" + minutes + "<span>Mins</span></div> : " +
                    "<div>" + seconds + "<span>Secs</span></div>";
            }, 1000);
        } else if (countdownContainer) {
            countdownContainer.style.display = 'none';
        }
    }
    
    function printRawContent(contentHtml, title) {
        const printWindow = window.open('', '_blank', 'height=800,width=800');

        printWindow.document.write('<html><head><title>' + title + '</title>');
        
        // Include necessary styles for printing
        printWindow.document.write('<style>');
        printWindow.document.write('body { font-family: Arial, sans-serif; padding: 20px; }');
        printWindow.document.write('h3 { margin-top: 15px; }');
        
        // --- ADDED Robust Table CSS FIX to ensure data visibility ---
        printWindow.document.write('.wp-list-table { width: 100%; border-collapse: collapse; margin-top: 10px; font-size: 10pt; display: table !important; table-layout: auto; }');
        printWindow.document.write('.wp-list-table th, .wp-list-table td { border: 1px solid #ccc; padding: 8px; text-align: left; line-height: 1.2; vertical-align: top; -webkit-print-color-adjust: exact !important; color-adjust: exact !important; }');
        printWindow.document.write('.wp-list-table th { background-color: #f0f0f0 !important; }');
        printWindow.document.write('.wp-list-table .striped tbody tr:nth-child(odd) { background-color: #f9f9f9 !important; }');
        
        // Ensure the grid container and item display block
        printWindow.document.write('.BatchMate-report-table-container { display: block !important; margin-bottom: 20px; }');
        printWindow.document.write('#tables-print-area { display: block !important; }');
        
        // Hide print buttons in the print view
        printWindow.document.write('.print-table-btn { display: none; }'); 
        printWindow.document.write('@media print { body { margin: 0; } }');
        printWindow.document.write('</style>');
        
        printWindow.document.write('</head><body>');
        // Use the correctly passed orgName
        printWindow.document.write('<h2 style="text-align: center;">' + puaaDashboardData.orgName + '</h2>');
        printWindow.document.write('<div style="text-align: center; clear:both;">' + contentHtml + '</div>'); 
        printWindow.document.write('</body></html>');
        
        printWindow.document.close();
        printWindow.focus();
        
        setTimeout(() => {
            printWindow.print();
            printWindow.close();
        }, 300);
    }
    
    // 1. Individual Print Buttons (Department & Program)
    $('.print-table-btn').on('click', function() {
        const targetId = $(this).data('target');
        const title = $(this).data('title'); 
        const targetElement = document.getElementById(targetId);
        
        // Capture only h3 and table
        const heading = targetElement.querySelector('h2').outerHTML;
        const table = targetElement.querySelector('table').outerHTML;
        
        printRawContent(heading + table, title);
    });
});
</script>








