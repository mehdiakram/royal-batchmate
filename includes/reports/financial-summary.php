<?php
// Exit if accessed directly
if (!defined('ABSPATH')) exit;

// Variables $start_date, $end_date, $wpdb are available here

$payments_table = $wpdb->prefix . 'batchmate_payments';
$expenses_table = $wpdb->prefix . 'batchmate_expenses';
$purpose_table = $wpdb->prefix . 'batchmate_payment_purpose';
$types_table = $wpdb->prefix . 'batchmate_expenses_type';

// --- 1. Calculate Starting Balance ---
$collection_before = $wpdb->get_var( $wpdb->prepare(
    "SELECT SUM(amount) FROM {$payments_table} WHERE payment_date < %s", $start_date
) );
$expense_before = $wpdb->get_var( $wpdb->prepare(
    "SELECT SUM(amount) FROM {$expenses_table} WHERE expense_date < %s", $start_date
) );

$starting_balance = floatval($collection_before) - floatval($expense_before);
$running_balance = $starting_balance;


// --- 2. Fetch All Transactions within Date Range ---
// Use UNION ALL to combine payments and expenses
$transactions = $wpdb->get_results( $wpdb->prepare(
    "SELECT 
        p.payment_date as date, 
        p.amount, 
        'Collection' as type, 
        pp.name as detail 
     FROM {$payments_table} p 
     LEFT JOIN {$purpose_table} pp ON p.purpose_id = pp.id
     WHERE payment_date >= %s AND payment_date <= %s
     
     UNION ALL
     
     SELECT 
        e.expense_date as date, 
        e.amount, 
        'Expense' as type, 
        et.name as detail 
     FROM {$expenses_table} e 
     LEFT JOIN {$types_table} et ON e.type_id = et.id
     WHERE expense_date >= %s AND expense_date <= %s
     
     ORDER BY date ASC, type DESC",
     $start_date, $end_date, $start_date, $end_date
), ARRAY_A );

$total_collection = 0;
$total_expense = 0;
?>
<div class="report-section">
    <h2 style="font-weight: bold; text-align: center;">Financial Summary</h2>
    <p style="font-weight: bold; text-align: center;">Period: <?php echo date('F j, Y', strtotime($start_date)) . ' - ' . date('F j, Y', strtotime($end_date)); ?></p>
    
    <table class="report-table">
        <thead>
            <tr>
                <th style="width:150px;">Date</th>
                <th>Detail</th>
                <th style="width:120px; text-align: right;">Collection (?)</th>
                <th style="width:120px; text-align: right;">Expense (?)</th>
                <th style="width:120px; text-align: right;">Balance (?)</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td colspan="4" style="font-weight: bold; background-color: #f9f9f9;">Balance B/F</td>
                <td style="font-weight: bold; text-align: right; background-color: #e6f7e9;"><?php echo number_format($starting_balance, 2); ?></td>
            </tr>

            <?php if (!empty($transactions)): ?>
                <?php foreach ($transactions as $row): 
                    $collection = 0;
                    $expense = 0;

                    if ($row['type'] === 'Collection') {
                        $collection = floatval($row['amount']);
                        $running_balance += $collection;
                        $total_collection += $collection;
                    } else {
                        $expense = floatval($row['amount']);
                        $running_balance -= $expense;
                        $total_expense += $expense;
                    }
                ?>
                    <tr>
                        <td><?php echo date('M j, Y', strtotime($row['date'])); ?></td>
                        <td><?php echo esc_html($row['type']) . ': ' . esc_html($row['detail']); ?></td>
                        <td style="text-align: right; color: <?php echo $collection > 0 ? 'green' : 'inherit'; ?>;"><?php echo $collection > 0 ? number_format($collection, 2) : '-'; ?></td>
                        <td style="text-align: right; color: <?php echo $expense > 0 ? 'red' : 'inherit'; ?>;"><?php echo $expense > 0 ? number_format($expense, 2) : '-'; ?></td>
                        <td style="font-weight: bold; text-align: right;"><?php echo number_format($running_balance, 2); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="5" style="text-align: center;">No transactions found for this period.</td>
                </tr>
            <?php endif; ?>
            
            <tr style="background-color: #e6f1ff;">
                <td colspan="2" style="font-weight: bold;">Total Summary</td>
                <td style="font-weight: bold; text-align: right; color: green;">+ <?php echo number_format($total_collection, 2); ?></td>
                <td style="font-weight: bold; text-align: right; color: red;">- <?php echo number_format($total_expense, 2); ?></td>
                <td style="font-weight: bold; text-align: right;"></td>
            </tr>

            <tr>
                <td colspan="4" style="font-weight: bold; background-color: #f9f9f9;">Closing Balance</td>
                <td style="font-weight: bold; text-align: right; background-color: #e6f7e9;"><?php echo number_format($running_balance, 2); ?></td>
            </tr>
        </tbody>
    </table>
</div>