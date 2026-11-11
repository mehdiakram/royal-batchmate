<?php
/*
Template Name: BatchMate Member Print
*/

// Check if member ID is provided
if ( ! isset( $_GET['member_id'] ) || empty( $_GET['member_id'] ) ) {
    wp_die( 'No member ID provided.' );
}

if ( ! current_user_can( 'manage_options' ) ) {
    wp_die( 'You do not have permission to view this page.' );
}

global $wpdb;
$member_id = intval( $_GET['member_id'] );
$member_table = $wpdb->prefix . 'batchmate_member';
$dept_table = $wpdb->prefix . 'batchmate_department';
$prog_table = $wpdb->prefix . 'batchmate_program';

// [FIXED] JOIN Logic Updated: Member -> Program -> Department
$member = $wpdb->get_row( $wpdb->prepare(
    "SELECT m.*, d.dept_name, p.program_name
     FROM %i m
     
     LEFT JOIN %i d ON p.dept_id = d.id
     WHERE m.id = %d",
     $member_table, $prog_table, $dept_table, $member_id
) );

if ( ! $member ) {
    wp_die( 'Member not found.' );
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Print Member: <?php echo esc_html( $member->name ); ?></title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; max-width: 800px; margin: 20px auto; color: #333; }
        .print-container { border: 1px solid #ddd; padding: 30px; }
        .print-header { text-align: center; border-bottom: 2px solid #000; padding-bottom: 20px; margin-bottom: 30px; }
        .print-header img.logo { max-width: 150px; }
        .print-header h1 { margin: 0; }
        .profile-photo { float: right; width: 150px; height: 150px; border: 1px solid #ccc; padding: 4px; border-radius: 5px; object-fit: cover; }
        .profile-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .profile-table td {
            padding: 10px;
            border: 1px solid #eee;
            vertical-align: top;
        }
        .profile-table td:first-child { width: 180px; font-weight: bold; background-color: #f9f9f9; }
        @media print {
            body { margin: 0; }
            .print-container { border: none; padding: 0; }
            .no-print { display: none; }
        }
    </style>
</head>
<body onload="window.print()">

    <button class="no-print" onclick="window.print()" style="padding: 10px 20px; margin-bottom: 20px; cursor: pointer;">Print Now</button>

    <div class="print-container">
        <div class="print-header">
            <h1><?php echo batchmate_org_label( 'short' ); ?> Member Profile</h1>
            <p><?php echo batchmate_org_label( 'full' ); ?></p>
        </div>

        <?php if ( $member->photo_url ) : ?>
            <img src="<?php echo esc_url( $member->photo_url ); ?>" alt="Profile Photo" class="profile-photo">
        <?php endif; ?>

        <h2><?php echo esc_html( $member->name ); ?></h2>
        <p><strong>Member ID: <?php echo esc_html( $member->member_number ); ?></strong></p>

        <table class="profile-table">
            <tr><td colspan="2" style="background-color: #f0f0f0; font-weight: bold;">Personal Details</td></tr>
            <tr>
                <td>Email</td>
                <td><?php echo esc_html( $member->email ); ?></td>
            </tr>
            <tr>
                <td>Cell Number</td>
                <td><?php echo esc_html( $member->cell_number ); ?></td>
            </tr>
            <tr>
                <td>Blood Group</td>
                <td><?php echo esc_html( $member->blood_group ); ?></td>
            </tr>
            <tr>
                <td>Gender</td>
                <td><?php echo esc_html( $member->gender ); ?></td>
            </tr>
            <tr>
                <td>Date of Birth</td>
                <td><?php echo esc_html( $member->date_of_birth ); ?></td>
            </tr>

            <tr><td colspan="2" style="background-color: #f0f0f0; font-weight: bold;">Academic Details</td></tr>
            <tr>
                <td>Student ID</td>
                <td><?php echo esc_html( $member->student_id ); ?></td>
            </tr>
            <tr>
                <td>Department</td>
                <td><?php echo esc_html( $member->dept_name ); ?></td>
            </tr>
            <tr>
                <td>Program</td>
                <td><?php echo esc_html( $member->program_name ); ?></td>
            </tr>
            <tr>
                
            </tr>

            <tr><td colspan="2" style="background-color: #f0f0f0; font-weight: bold;">Professional Details</td></tr>
            <tr>
                <td>Position</td>
                <td><?php echo esc_html( $member->employment_position ); ?></td>
            </tr>
            <tr>
                <td>Organization</td>
                <td><?php echo esc_html( $member->employment_address ); ?></td>
            </tr>
            
            <tr><td colspan="2" style="background-color: #f0f0f0; font-weight: bold;">Contact Details</td></tr>
            <tr>
                <td>Present Address</td>
                <td><?php echo nl2br( esc_html( $member->present_address ) ); ?></td>
            </tr>
            <tr>
                <td>Permanent Address</td>
                <td><?php echo nl2br( esc_html( $member->permanent_address ) ); ?></td>
            </tr>
            <tr>
                <td>Social Links</td>
                <td>
                    <?php if($member->facebook_url) echo 'Facebook: ' . esc_url($member->facebook_url) . '<br>'; ?>
                    <?php if($member->linkedin_url) echo 'LinkedIn: ' . esc_url($member->linkedin_url) . '<br>'; ?>
                    <?php if($member->website) echo 'Website: ' . esc_url($member->website); ?>
                </td>
            </tr>
        </table>
    </div>

</body>
</html>