<?php
// Exit if accessed directly
if (!defined('ABSPATH')) exit;

// Variables $start_date, $end_date, $wpdb are available here

$payments_table = $wpdb->prefix . 'batchmate_payments';

$monthly_trend_data = $wpdb->get_results( $wpdb->prepare(
    "SELECT 
        DATE_FORMAT(payment_date, '%%Y-%%m') AS month_year, 
        SUM(amount) as monthly_collection
     FROM {$payments_table}
     WHERE payment_date >= %s AND payment_date <= %s
     GROUP BY month_year
     ORDER BY month_year ASC",
     $start_date, $end_date
), ARRAY_A );

$grand_total_collection = array_sum(array_column($monthly_trend_data, 'monthly_collection'));

?>
<div class="report-section">
    <h2 style="font-weight: bold; text-align: center;">Monthly Wise Collection</h2>
    <p style="font-weight: bold; text-align: center;">Period: <?php echo date('F j, Y', strtotime($start_date)) . ' - ' . date('F j, Y', strtotime($end_date)); ?></p>
    
    <table class="report-table">
        <thead>
            <tr>
                <th>Month (Year)</th>
                <th style="text-align: right;">Total Collection (?)</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($monthly_trend_data)): ?>
                <?php foreach ($monthly_trend_data as $row): ?>
                    <tr>
                        <td><?php echo date('F Y', strtotime($row['month_year'] . '-01')); ?></td>
                        <td style="text-align: right;"><?php echo number_format($row['monthly_collection'], 2); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="2" style="text-align: center;">No collection data available for this period.</td></tr>
            <?php endif; ?>
        </tbody>
        <tfoot>
             <tr class="total-summary">
                <td style="font-weight: bold; text-align: right;">GRAND TOTAL COLLECTION</td>
                <td style="font-weight: bold; text-align: right;"><?php echo number_format($grand_total_collection, 2); ?></td>
            </tr>
        </tfoot>
    </table>
</div>