<?php
// Exit if accessed directly
if (!defined('ABSPATH')) exit;

// Variables $start_date, $end_date, $wpdb are available here
$expenses_table = $wpdb->prefix . 'batchmate_expenses';
$types_table = $wpdb->prefix . 'batchmate_expenses_type';

// --- Fetch Detailed Expense Data ---
$expenses_data = $wpdb->get_results( $wpdb->prepare(
    "SELECT 
        e.expense_date, 
        e.amount, 
        e.description, 
        e.vendor,
        et.name as type_name
     FROM {$expenses_table} e
     LEFT JOIN {$types_table} et ON e.type_id = et.id
     WHERE e.expense_date >= %s AND e.expense_date <= %s
     ORDER BY e.expense_date ASC",
     $start_date, $end_date
), ARRAY_A );

$grand_total_expense = array_sum(array_column($expenses_data, 'amount'));

?>
<div class="report-section">
    <h2 style="font-weight: bold; text-align: center;">Expense: Detailed Report</h2>
    <p style="font-weight: bold; text-align: center;">Period: <?php echo date('F j, Y', strtotime($start_date)) . ' - ' . date('F j, Y', strtotime($end_date)); ?></p>
    
    <table class="report-table">
        <thead>
            <tr>
                <th style="width: 150px;">Date</th>
                <th style="width: 180px;">Expense Type</th>
                <th>Description / Details</th>
                <th style="width: 150px;">Vendor</th>
                <th style="width: 120px; text-align: right;">Amount (?)</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($expenses_data)): ?>
                <?php foreach ($expenses_data as $row): ?>
                    <tr>
                        <td><?php echo date('M j, Y', strtotime($row['expense_date'])); ?></td>
                        <td><?php echo esc_html($row['type_name']); ?></td>
                        <td><?php echo esc_html($row['description'] ?: 'N/A'); ?></td>
                        <td><?php echo esc_html($row['vendor'] ?: 'N/A'); ?></td>
                        <td style="text-align: right;"><?php echo number_format($row['amount'], 2); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="5" style="text-align: center;">No expense transactions found for this period.</td></tr>
            <?php endif; ?>
        </tbody>
        <tfoot>
            <tr class="total-summary">
                <td colspan="4" style="font-weight: bold; text-align: right; border-right: none;">GRAND TOTAL EXPENSE</td>
                <td style="font-weight: bold; text-align: right; border-left: none;"><?php echo number_format($grand_total_expense, 2); ?></td>
            </tr>
        </tfoot>
    </table>
</div>