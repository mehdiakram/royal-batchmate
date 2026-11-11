<?php
/*
Template Name: BatchMate EC Print
*/

// পারমিশন চেক
if ( ! current_user_can( 'manage_options' ) ) {
    wp_die( 'You do not have permission to view this page.' );
}

// কমিটি ID চেক
if ( ! isset( $_GET['committee_id'] ) || empty( $_GET['committee_id'] ) ) {
    wp_die( 'No committee ID provided.' );
}

global $wpdb;
$committee_id = intval( $_GET['committee_id'] );

// টেবিলের নাম
$committee_table = $wpdb->prefix . 'batchmate_committee';
$ec_table = $wpdb->prefix . 'batchmate_ec';
$member_table = $wpdb->prefix . 'batchmate_member';
$post_table = $wpdb->prefix . 'batchmate_ec_post_title';
$prog_table = $wpdb->prefix . 'batchmate_program'; // New Table
$dept_table = $wpdb->prefix . 'batchmate_department'; // Use for final dept name

// কমিটির তথ্য (সেশন ইয়ার) বের করা
$committee = $wpdb->get_row( $wpdb->prepare(
    "SELECT session_year FROM %i WHERE id = %d",
    $committee_table, $committee_id
) );

if ( ! $committee ) {
    wp_die( 'Committee not found.' );
}
$session_title = $committee->session_year;

// (FIXED QUERY) কমিটির সদস্যদের তথ্য বের করা - Now correctly joins through Program table
$members = $wpdb->get_results( $wpdb->prepare(
    "SELECT
        m.name, m.email, m.cell_number, d.dept_name,
        p.post_title, p.position_order
    FROM %i AS ec
    LEFT JOIN %i AS m ON ec.member_id = m.id
    LEFT JOIN %i AS p ON ec.post_id = p.id
       /* <--- NEW JOIN STEP */
    LEFT JOIN %i AS d ON prog.dept_id = d.id          /* <--- UPDATED JOIN */
    WHERE ec.committee_id = %d
    ORDER BY p.position_order ASC, ASC", 
    $ec_table, $member_table, $post_table, $prog_table, $dept_table, $committee_id
) );

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Print Committee: <?php echo esc_html( $session_title ); ?></title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            line-height: 1.6; 
            max-width: 960px; 
            margin: 20px auto; 
            color: #333; 
        }
        .print-container { 
            border: 1px solid #ddd; 
            padding: 30px; 
        }
        .print-header { 
            text-align: center; 
            border-bottom: 2px solid #000; 
            padding-bottom: 20px; 
            margin-bottom: 30px; 
        }
        .print-header h1 { 
            margin: 0; 
            font-size: 2em;
        }
        .print-table { 
            width: 100%; 
            border-collapse: collapse; 
            margin-top: 20px; 
            font-size: 11pt;
        }
        .print-table th,
        .print-table td {
            padding: 8px 10px;
            border: 1px solid #ccc;
            text-align: left;
            vertical-align: top;
        }
        .print-table th {
            background-color: #f2f2f2;
            font-weight: bold;
        }
        .print-table tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .si-no { width: 50px; text-align: center; }
        .position { width: 200px; }
        .department { width: 150px; }
        .contact { width: 120px; }
        
        @media print {
            body { margin: 0; max-width: 100%; }
            .print-container { border: none; padding: 0; box-shadow: none; }
            .no-print { display: none; }
        }
    </style>
</head>
<body onload="window.print()">

    <button class="no-print" onclick="window.print()" style="padding: 10px 20px; margin-bottom: 20px; cursor: pointer;">Print Now</button>

    <div class="print-container">
        <div class="print-header">
            <h1>Executive Committee <?php echo esc_html( $session_title ); ?></h1>
            <p style="font-size: 1.2em;"><?php echo batchmate_org_label( 'full' ); ?></p>
        </div>

        <table class="print-table">
            <thead>
                <tr>
                    <th class="si-no">SI No.</th>
                    <th>Name</th>
                    <th class="department">Department</th>
                    <th class="position">Position</th>
                    <th>Email</th>
                    <th class="contact">Cell No.</th>
                </tr>
            </thead>
            <tbody>
                <?php if ( ! empty( $members ) ) : ?>
                    <?php $si = 1; ?>
                    <?php foreach ( $members as $member ) : ?>
                        <tr>
                            <td class="si-no"><?php echo $si++; ?></td>
                            <td><?php echo esc_html( $member->name ); ?></td>
                            <td><?php echo esc_html( $member->dept_name ); ?></td>
                            <td><?php echo esc_html( $member->post_title ); ?></td>
                            <td><?php echo esc_html( $member->email ); ?></td>
                            <td><?php echo esc_html( $member->cell_number ); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else : ?>
                    <tr>
                        <td colspan="6" style="text-align: center;">No members found for this committee.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

</body>
</html>