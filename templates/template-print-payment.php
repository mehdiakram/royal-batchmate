<?php
/**
 * Template Name: BatchMate Payment Receipt Print
 * Description: Template for printing payment receipts.
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

global $wpdb;
$payment_id = isset( $_GET['payment_id'] ) ? intval( $_GET['payment_id'] ) : 0;

if ( $payment_id <= 0 ) {
    wp_die( 'Invalid Payment ID.', 'Error', array( 'response' => 400 ) );
}

$payments_table = $wpdb->prefix . 'batchmate_payments';
$members_table = $wpdb->prefix . 'batchmate_member';
$modes_table = $wpdb->prefix . 'batchmate_payment_modes';
$purpose_table = $wpdb->prefix . 'batchmate_payment_purpose'; // NEW
$settings = get_option('batchmate_settings', array());

// Fetch Payment Data with Member, Mode and Purpose details (UPDATED)
$payment = $wpdb->get_row( $wpdb->prepare(
    "SELECT p.*, 
            m.name as member_name, 
            m.member_number,
            pm.name as mode_name,
            pp.name as purpose_name
     FROM {$payments_table} p
     LEFT JOIN {$members_table} m ON p.member_id = m.id
     LEFT JOIN {$modes_table} pm ON p.payment_mode_id = pm.id
     LEFT JOIN {$purpose_table} pp ON p.purpose_id = pp.id
     WHERE p.id = %d",
     $payment_id
) );

if ( ! $payment ) {
    wp_die( 'Payment not found.', 'Error', array( 'response' => 404 ) );
}

// Prepare Data
$org_name = batchmate_org_label( 'full' );
$org_address = isset($settings['org_address']) ? nl2br(esc_html($settings['org_address'])) : '';
$org_logo_url = isset($settings['org_logo_url']) ? esc_url($settings['org_logo_url']) : '';
$member_info = $payment->member_name ? "{$payment->member_name} (ID: {$payment->member_number})" : 'General Payment (Non-member)';
$receipt_date = date_i18n( get_option('date_format'), strtotime($payment->payment_date) );
$created_by_user = get_user_by('id', $payment->created_by);
$recorded_by = $created_by_user ? esc_html($created_by_user->display_name) : 'System';
$payment_purpose = esc_html($payment->purpose_name) ?: (esc_html($payment->note) ?: 'N/A'); // Fallback to note if purpose is missing


// Start Output Buffer
ob_start();
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <title>Payment Receipt #<?php echo esc_html($payment->id); ?></title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen, Ubuntu, Cantarell, "Fira Sans", "Droid Sans", "Helvetica Neue", sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f4f4f4;
        }
        .receipt-container {
            max-width: 700px;
            margin: 0 auto;
            background: #fff;
            padding: 30px;
            border: 1px solid #ddd;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #333;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        .header img {
            max-height: 80px;
            margin-bottom: 10px;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
            color: #1a73e8; /* BatchMate Blue */
        }
        .header p {
            margin: 5px 0 0;
            font-size: 14px;
            color: #666;
        }
        .title {
            text-align: center;
            font-size: 20px;
            font-weight: bold;
            margin-bottom: 30px;
            color: #333;
        }
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 30px;
        }
        .info-block {
            padding: 10px;
            border: 1px solid #eee;
            border-radius: 5px;
        }
        .info-block label {
            display: block;
            font-weight: bold;
            color: #555;
            font-size: 12px;
            margin-bottom: 3px;
            text-transform: uppercase;
        }
        .info-block span {
            font-size: 16px;
            color: #000;
            display: block;
        }
        .amount-box {
            background-color: #e6f7ff; /* Light Blue Background */
            border: 1px solid #91d5ff;
            padding: 20px;
            text-align: center;
            margin-bottom: 30px;
            border-radius: 5px;
        }
        .amount-box label {
            font-size: 18px;
            font-weight: bold;
            color: #1a73e8;
            display: block;
            margin-bottom: 5px;
        }
        .amount-box span {
            font-size: 36px;
            font-weight: 900;
            color: #1a73e8;
        }
        .footer {
            text-align: center;
            border-top: 1px solid #ddd;
            padding-top: 20px;
            font-size: 12px;
            color: #888;
        }
        .signatures {
            display: flex;
            justify-content: space-between;
            margin-top: 50px;
            padding-top: 10px;
            border-top: 1px dashed #ccc;
            font-size: 14px;
        }
        .signature-block {
            width: 45%;
            text-align: center;
            font-weight: bold;
            color: #333;
        }
        @media print {
            body { background-color: #fff; padding: 0; }
            .receipt-container { border: none; box-shadow: none; padding: 0; }
            .signatures { margin-top: 70px; }
        }
    </style>
</head>
<body>
    <div class="receipt-container">
        <div class="header">
            <?php if ( $org_logo_url ) : ?>
                <img src="<?php echo $org_logo_url; ?>" alt="<?php echo $org_name; ?> Logo">
            <?php endif; ?>
            <h1><?php echo esc_html($org_name); ?></h1>
            <p><?php echo $org_address; ?></p>
        </div>

        <div class="title">Official Payment Receipt</div>
        
        <div class="info-grid">
            <div class="info-block">
                <label>Receipt No.</label>
                <span>#<?php echo esc_html($payment->id); ?></span>
            </div>
            <div class="info-block">
                <label>Date</label>
                <span><?php echo $receipt_date; ?></span>
            </div>
            <div class="info-block swal2-form-full" style="grid-column: 1 / span 2;">
                <label>Paid By</label>
                <span><?php echo $member_info; ?></span>
            </div>
        </div>

        <div class="amount-box">
            <label>Amount Received</label>
            <span>৳ <?php echo number_format($payment->amount, 2); ?></span>
        </div>

        <div class="info-grid">
            <div class="info-block">
                <label>Payment Purpose</label>
                <span><?php echo $payment_purpose; ?></span>
            </div>
            <div class="info-block">
                <label>Payment Method</label>
                <span><?php echo esc_html($payment->mode_name); ?></span>
            </div>
            <div class="info-block">
                <label>Transaction ID / Ref.</label>
                <span><?php echo esc_html($payment->transaction ?: 'N/A'); ?></span>
            </div>
            <div class="info-block">
                <label>Note</label>
                <span><?php echo esc_html($payment->note ?: 'N/A'); ?></span>
            </div>
        </div>
        
        <div class="signatures">
            <div class="signature-block">
                ..........................................<br>
                Recipient Signature<br>
                (Recorded by: <?php echo $recorded_by; ?>)
            </div>
            <div class="signature-block">
                ..........................................<br>
                Member Signature
            </div>
        </div>

        <div class="footer">
            <p>This is an electronically generated receipt and requires no physical signature.</p>
        </div>
    </div>
    
    <script type="text/javascript">
        // Automatically trigger print dialog on load
        window.onload = function() {
            window.print();
        };
    </script>
</body>
</html>