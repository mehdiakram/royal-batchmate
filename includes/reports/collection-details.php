<?php
// Exit if accessed directly
if (!defined('ABSPATH')) exit;

// Variables $start_date, $end_date, $wpdb are available here
$payments_table = $wpdb->prefix . 'batchmate_payments';
$members_table = $wpdb->prefix . 'batchmate_member';
$purpose_table = $wpdb->prefix . 'batchmate_payment_purpose';
$modes_table = $wpdb->prefix . 'batchmate_payment_modes';

// --- Fetch Detailed Collection Data ---
$collection_data = $wpdb->get_results( $wpdb->prepare(
    "SELECT 
        p.payment_date, 
        p.amount, 
        p.transaction,
        m.name as member_name, 
        pp.name as purpose_name, 
        pm.name as mode_name
     FROM {$payments_table} p
     LEFT JOIN {$members_table} m ON p.member_id = m.id
     LEFT JOIN {$purpose_table} pp ON p.purpose_id = pp.id
     LEFT JOIN {$modes_table} pm ON p.payment_mode_id = pm.id
     WHERE p.payment_date >= %s AND p.payment_date <= %s
     ORDER BY p.payment_date ASC",
     $start_date, $end_date
), ARRAY_A );

$grand_total_collection = array_sum(array_column($collection_data, 'amount'));

?>
<div class="report-section">
    <h2 style="font-weight: bold; text-align: center;">Collection: Detailed Report</h2>
    <p style="font-weight: bold; text-align: center;">Period: <?php echo date('F j, Y', strtotime($start_date)) . ' - ' . date('F j, Y', strtotime($end_date)); ?></p>
    
    <table class="report-table">
        <thead>
            <tr>
                <th style="width: 150px;">Payment Date</th>
                <th style="width: 180px;">Member Name</th>
                <th style="width: 150px;">Purpose</th>
                <th style="width: 120px;">Mode</th>
                <th style="width: 150px;">Transaction ID</th>
                <th style="width: 120px; text-align: right;">Amount (?)</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($collection_data)): ?>
                <?php foreach ($collection_data as $row): ?>
                    <tr>
                        <td><?php echo date('M j, Y', strtotime($row['payment_date'])); ?></td>
                        <td><?php echo esc_html($row['member_name'] ?: 'N/A (Non-member)'); ?></td>
                        <td><?php echo esc_html($row['purpose_name'] ?: 'Unknown Purpose'); ?></td>
                        <td><?php echo esc_html($row['mode_name'] ?: 'N/A'); ?></td>
                        <td><?php echo esc_html($row['transaction'] ?: 'N/A'); ?></td>
                        <td style="text-align: right;"><?php echo number_format($row['amount'], 2); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="6" style="text-align: center;">No collection transactions found for this period.</td></tr>
            <?php endif; ?>
        </tbody>
        <tfoot>
            <tr class="total-summary">
                <td colspan="5" style="font-weight: bold; text-align: right; border-right: none;">GRAND TOTAL COLLECTION</td>
                <td style="font-weight: bold; text-align: right; border-left: none;"><?php echo number_format($grand_total_collection, 2); ?></td>
            </tr>
        </tfoot>
    </table>
</div>