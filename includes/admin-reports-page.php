<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

global $wpdb;

// Load settings for dynamic print header title
$settings = get_option('batchmate_settings', array());
$org_name = esc_js( batchmate_org_label( 'full' ) );

// Define default date range (Current Year)
$default_start_date = date('Y-01-01');
$default_end_date = date('Y-12-31');

// Get current report parameters (or use default)
$start_date = isset($_GET['start_date']) ? sanitize_text_field($_GET['start_date']) : $default_start_date;
$end_date = isset($_GET['end_date']) ? sanitize_text_field($_GET['end_date']) : $default_end_date;

$current_page_url = admin_url('admin.php?page=batchmate_reports');

// Generate initial date range string for the input field
$date_range_string = date('F j, Y', strtotime($start_date)) . ' - ' . date('F j, Y', strtotime($end_date));

?>
<link rel='stylesheet' href='<?php echo ROYAL_BATCHMATE_PLUGIN_URL; ?>/assets/daterangepicker/daterangepicker.css'>

<div class="wrap BatchMate-reports-page">
    <div class="BatchMate-page-header-animated">
        <div class="BatchMate-header-left">
            <h1><span class="dashicons dashicons-chart-bar"></span> Financial Reports</h1>
            <p>Generate detailed financial reports and export data.</p>
        </div>
    </div>

    <div class="BatchMate-report-controls">
        
        <div class="report-selector BatchMate-filter-group">
            <label for="report-type">Select Report:</label>
            <select id="report-type" name="report_type">
                <option value="">-- Choose a Report --</option>
                <option value="financial-summary">Financial Summary (Ledger)</option>
                <option value="collection-monthly">Monthly Collection</option>
                <option value="collection-details">Collection Details</option>
                <option value="expense">Expense</option>
            </select>
        </div>

        <div class="report-filters">
            <div id="filter-date" class="BatchMate-filter-group">
                <label for="filter-date-range">Date Range:</label>
                <input type="text" id="filter-date-range" name="date_range" value="<?php echo esc_attr($date_range_string); ?>">
                <input type="hidden" id="report_start_date" value="<?php echo esc_attr($start_date); ?>">
                <input type="hidden" id="report_end_date" value="<?php echo esc_attr($end_date); ?>">
            </div>
        </div>

        <div class="report-actions">
            <button id="generate-report-btn" class="button button-primary" disabled>Generate Report</button>
        </div>
    </div>
    
    <div id="report-content-wrapper">
        <div id="report-content-container" class="card" style="padding: 0;">
            <div id="report-content" style="padding: 20px;">
                <p style="text-align: center; color: #666;">Select a report from the dropdown and click 'Generate Report'.</p>
            </div>
        </div>
        <div class="report-export-options" style="margin-top: 10px; display: none;">
            <button class="button" id="btn-print-report">
                <span class="dashicons dashicons-printer"></span> Print (A4)
            </button>
        </div>
    </div>
</div>


<div id="print-modal" style="display: none;">
    <div id="print-modal-content"></div>
</div>


<script src='<?php echo ROYAL_BATCHMATE_PLUGIN_URL; ?>/assets/moment/moment.min.js'></script>
<script src='<?php echo ROYAL_BATCHMATE_PLUGIN_URL; ?>/assets/daterangepicker/daterangepicker.js'></script>
<script src='<?php echo ROYAL_BATCHMATE_PLUGIN_URL; ?>/assets/sweetalert2/sweetalert2.all.min.js'></script>
<script type="text/javascript">
jQuery(document).ready(function($) {

    // --- State Variables ---
    const ajax_url = '<?php echo admin_url( 'admin-ajax.php' ); ?>';
    const report_type_select = $('#report-type');
    const date_range_input = $('#filter-date-range');
    const generate_btn = $('#generate-report-btn');
    const report_content_wrapper = $('#report-content-wrapper');
    const report_content = $('#report-content');
    const export_options = $('.report-export-options');
    
    let currentStartDate = moment('<?php echo esc_attr($start_date); ?>');
    let currentEndDate = moment('<?php echo esc_attr($end_date); ?>');

    // Load org name for print header dynamically
    const orgName = '<?php echo $org_name; ?>';

    // --- Date Range Picker Initialization ---
    var ranges = {
        'Today': [moment(), moment()],
        'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
        'Last 7 Days': [moment().subtract(6, 'days'), moment()],
        'Last 30 Days': [moment().subtract(29, 'days'), moment()],
        'This Month': [moment().startOf('month'), moment().endOf('month')],
        'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')],
        'This Year': [moment().startOf('year'), moment().endOf('year')],
        'Last Year': [moment().subtract(1, 'year').startOf('year'), moment().subtract(1, 'year').endOf('year')],
        '2 Years': [moment().subtract(2, 'year').startOf('year'), moment()],
    };
    
    // Function to update local state and enable button
    function updateDateState(start, end) {
        currentStartDate = start;
        currentEndDate = end;
        generate_btn.prop('disabled', !report_type_select.val());
    }

    date_range_input.daterangepicker({
        startDate: currentStartDate,
        endDate: currentEndDate,
        showDropdowns: true,
        autoUpdateInput: true,
        alwaysShowCalendars: true,
        ranges: ranges,
        locale: { format: 'MMMM D, YYYY' }
    }, updateDateState);

    // --- Control Logic ---
    report_type_select.on('change', function() {
        const type = $(this).val();
        generate_btn.prop('disabled', !type);
        report_content_wrapper.show();
        export_options.hide();
        report_content.html('<p style="text-align: center; color: #666;">Click \'Generate Report\' to load data.</p>');
    });

    generate_btn.on('click', function() {
        const reportType = report_type_select.val();
        
        if (!reportType) return;

        // --- Loading Pop-up (SweetAlert2) ---
        Swal.fire({
            title: 'Generating Report...',
            text: 'Please wait.',
            icon: 'info',
            showConfirmButton: false,
            allowOutsideClick: false,
            willOpen: () => { Swal.showLoading(); }
        });

        // --- AJAX Call to Load Report Content ---
        generate_btn.prop('disabled', true).text('Generating...');

        $.ajax({
            url: ajax_url,
            type: 'POST',
            data: {
                action: 'BatchMate_generate_financial_report',
                report_type: reportType,
                start_date: currentStartDate.format('YYYY-MM-DD'),
                end_date: currentEndDate.format('YYYY-MM-DD')
            },
            success: function(response) {
                Swal.close();
                if (response.success) {
                    report_content.html(response.data.html);
                    export_options.show();
                } else {
                    Swal.fire('Error!', 'Failed to load report: ' + (response.data || 'Unknown error'), 'error');
                    report_content.html('<p style="color:red; text-align: center;">Report failed to load.</p>');
                }
            },
            error: function(xhr, status, error) {
                Swal.fire('AJAX Error!', 'Network or server error.', 'error');
                report_content.html('<p style="color:red; text-align: center;">Communication Failed.</p>');
            },
            complete: function() {
                generate_btn.prop('disabled', false).text('Generate Report');
            }
        });
    });

    // --- Export/Print Logic (A4 Print Preview) ---
    $('#btn-print-report').on('click', function() {
        const printContents = document.getElementById('report-content').innerHTML;
        const printWindow = window.open('', '_blank', 'height=800,width=800');
        
        printWindow.document.write('<html><head><title>Financial Report Print</title>');
        
        // Include A4 specific styles
        printWindow.document.write('<style>');
        printWindow.document.write('body { font-family: Arial, sans-serif; margin: 0; padding: 0; background: white; -webkit-print-color-adjust: exact !important; }');
        printWindow.document.write('@page { size: A4; margin: 20mm; }');
        
        // Print-specific Table Styles (Matching the new UI)
        printWindow.document.write('.report-table { width: 100%; border-collapse: collapse; margin-top: 15px; font-size: 10pt; box-shadow: none; }');
        printWindow.document.write('.report-table th, .report-table td { border: 1px solid #ccc; padding: 8px; text-align: left; vertical-align: middle; }');
        printWindow.document.write('.report-table th { background-color: #f0f0f0 !important; color: #333 !important; border-top: 3px solid #007bff !important; }');
        printWindow.document.write('.report-table tbody tr:nth-child(even) { background-color: #fafafa !important; }');
        printWindow.document.write('.report-table .balance-bf { font-weight: bold; background-color: #f0f0f0 !important; }');
        printWindow.document.write('.report-table .balance-closing { font-weight: bold; background-color: #d4edda !important; color: #155724 !important; }');
        printWindow.document.write('.report-table .total-summary { font-weight: bold; background-color: #e6f1ff !important; border-top: 2px solid #007bff !important; }');
        printWindow.document.write('.report-table .positive { color: #28a745; }');
        printWindow.document.write('.report-table .negative { color: #dc3545; }');

        printWindow.document.write('.header-title { text-align: center; margin-bottom: 25px; border-bottom: 2px solid #333; padding-bottom: 10px; }');
        printWindow.document.write('.header-title h1 { margin: 0; font-size: 16pt; }');
        printWindow.document.write('.header-title p { margin: 2px 0 0; font-size: 10pt; }');
        printWindow.document.write('.report-signatures { display: block; margin-top: 60px; padding-top: 20px; clear: both; page-break-before: auto; break-inside: avoid; }');
        printWindow.document.write('.signatures-table { width: 100%; border-collapse: collapse; table-layout: fixed; }');
        printWindow.document.write('.signatures-table td { width: 33.33%; text-align: center; padding: 0 25px; border: none !important; }');
        printWindow.document.write('.signatures-table .signature-line { border-top: 1px solid #333; margin-bottom: 5px; height: 1px; }');
        printWindow.document.write('.signatures-table .signature-title { font-size: 13px; font-weight: bold; color: #555; text-transform: uppercase; white-space: nowrap; }');
        printWindow.document.write('</style>');
        
        printWindow.document.write('</head><body>');
        
        // Wrap content with a custom header
        printWindow.document.write('<div class="header-title">');
        printWindow.document.write('<h1>' + orgName + '</h1>');
        printWindow.document.write('<p>Report Period: ' + currentStartDate.format('D MMM YYYY') + ' - ' + currentEndDate.format('D MMM YYYY') + '</p>');
        printWindow.document.write('</div>');
        
        printWindow.document.write(printContents);

        printWindow.document.write('<div class="report-signatures">');
        printWindow.document.write('<table class="signatures-table">');
        printWindow.document.write('<tr>');
        printWindow.document.write('<td><div class="signature-line"></div><div class="signature-title">Finance Secretary</div></td>');
        printWindow.document.write('<td><div class="signature-line"></div><div class="signature-title">Secretary General</div></td>');
        printWindow.document.write('<td><div class="signature-line"></div><div class="signature-title">President</div></td>');
        printWindow.document.write('</tr>');
        printWindow.document.write('</table>');
        printWindow.document.write('</div>');

        printWindow.document.write('</body></html>');
        
        printWindow.document.close();
        printWindow.focus();
        setTimeout(() => {
            printWindow.print();
            printWindow.close();
        }, 300); 
    });

    // Default to 'This Year' on initial load if no parameters exist
    if (!window.location.search.includes('start_date')) {
         const thisYearStart = moment().startOf('year').format('YYYY-MM-DD');
         const thisYearEnd = moment().endOf('year').format('YYYY-MM-DD');

         if ('<?php echo esc_attr($start_date); ?>' !== thisYearStart || '<?php echo esc_attr($end_date); ?>' !== thisYearEnd) {
             // Reload page with default date to ensure data consistency
             window.location.href = current_page_url + '&start_date=' + thisYearStart + '&end_date=' + thisYearEnd;
         }
    }
});
</script>