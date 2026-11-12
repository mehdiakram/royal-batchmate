<?php
/*
Template Name: BatchMate Application Print
*/

// Check if application ID is provided
if ( ! isset( $_GET['application_id'] ) || empty( $_GET['application_id'] ) ) {
    wp_die( 'No application ID provided.' );
}

if ( ! current_user_can( 'manage_options' ) ) {
    wp_die( 'You do not have permission to view this page.' );
}

global $wpdb;
$app_id = intval( $_GET['application_id'] );
$app_table = $wpdb->prefix . 'batchmate_application';
$dept_table = $wpdb->prefix . 'batchmate_department';
$prog_table = $wpdb->prefix . 'batchmate_program';

// Fetch application details with dept and program names
$application = $wpdb->get_row( $wpdb->prepare(
    "SELECT app.*, d.dept_name, p.program_name
     FROM %i app
     LEFT JOIN %i p ON app.program_id = p.id
     LEFT JOIN %i d ON p.dept_id = d.id
     WHERE app.id = %d",
     $app_table, $prog_table, $dept_table, $app_id
) );

if ( ! $application ) {
    wp_die( 'Application not found.' );
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Print Application: <?php echo esc_html( $application->name ); ?></title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; max-width: 800px; margin: 20px auto; color: #333; }
        .print-container { border: 1px solid #ddd; padding: 30px; position: relative; }
        .print-header { text-align: center; border-bottom: 2px solid #000; padding-bottom: 20px; margin-bottom: 30px; }
        .print-header h1 { margin: 0; }
        .profile-photo { float: right; width: 150px; height: 150px; border: 1px solid #ccc; padding: 4px; border-radius: 5px; object-fit: cover; }
        .profile-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .profile-table td {
            padding: 10px;
            border: 1px solid #eee;
            vertical-align: top;
        }
        .profile-table td:first-child { width: 180px; font-weight: bold; background-color: #f9f9f9; }
        .status-badge { 
            display: inline-block; padding: 5px 10px; border-radius: 4px; color: white; font-weight: bold; text-transform: uppercase; font-size: 0.8em;
        }
        .status-pending { background-color: #ffc107; color: #333; }
        .status-approved { background-color: #28a745; }
        .status-rejected { background-color: #dc3545; }

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
            <h1>Membership Application</h1>
            <p><?php echo batchmate_org_label( 'full' ); ?></p>
        </div>

        <?php if ( $application->photo_url ) : ?>
            <img src="<?php echo esc_url( $application->photo_url ); ?>" alt="Applicant Photo" class="profile-photo">
        <?php endif; ?>

        <h2><?php echo esc_html( $application->name ); ?></h2>
        <p>
            <strong>Application ID: <?php echo esc_html( $application->id ); ?></strong> 
            <span class="status-badge status-<?php echo esc_attr($application->status); ?>">
                <?php echo esc_html( $application->status ); ?>
            </span>
        </p>

        <table class="profile-table">
            <tr><td colspan="2" style="background-color: #f0f0f0; font-weight: bold;">Personal Details</td></tr>
            <tr>
                <td>Email</td>
                <td><?php echo esc_html( $application->email ); ?></td>
            </tr>
            <tr>
                <td>Cell Number</td>
                <td><?php echo esc_html( $application->cell_number ); ?></td>
            </tr>
            <tr>
                <td>Blood Group</td>
                <td><?php echo esc_html( $application->blood_group ); ?></td>
            </tr>
            <tr>
                <td>Date of Birth</td>
                <td><?php echo esc_html( $application->date_of_birth ); ?></td>
            </tr>

            <tr><td colspan="2" style="background-color: #f0f0f0; font-weight: bold;">Academic Details</td></tr>
            <tr>
                <td>Student ID</td>
                <td><?php echo esc_html( $application->student_id ); ?></td>
            </tr>
            <tr>
                <td>Department</td>
                <td><?php echo esc_html( $application->dept_name ); ?></td>
            </tr>
            <tr>
                <td>Program</td>
                <td><?php echo esc_html( $application->program_name ); ?></td>
            </tr>
            <tr>
                
            </tr>
            <tr>
                <td>Enrollment</td>
                
            </tr>
            <tr>
                <td>Completion</td>
                
            </tr>

            <tr><td colspan="2" style="background-color: #f0f0f0; font-weight: bold;">Professional Details</td></tr>
            <tr>
                <td>Position</td>
                <td><?php echo esc_html( $application->employment_position ); ?></td>
            </tr>
            <tr>
                <td>Organization</td>
                <td><?php echo esc_html( $application->employment_address ); ?></td>
            </tr>
            
            <tr><td colspan="2" style="background-color: #f0f0f0; font-weight: bold;">Contact Details</td></tr>
            <tr>
                <td>Present Address</td>
                <td><?php echo nl2br( esc_html( $application->present_address ) ); ?></td>
            </tr>
            <tr>
                <td>Permanent Address</td>
                <td><?php echo nl2br( esc_html( $application->permanent_address ) ); ?></td>
            </tr>
            
            <tr><td colspan="2" style="background-color: #f0f0f0; font-weight: bold;">Payment Info</td></tr>
            <tr>
                <td>Method</td>
                <td><?php echo esc_html( $application->payment_method ); ?></td>
            </tr>
            <tr>
                <td>Details (TrxID)</td>
                <td><?php echo esc_html( $application->payment_details ); ?></td>
            </tr>
        </table>
        
        <div style="margin-top: 50px; text-align: center; font-size: 0.9em; color: #666;">
            <p>Application Date: <?php echo date( 'F j, Y, g:i a', strtotime( $application->submission_date ) ); ?></p>
        </div>
    </div>

</body>
</html>