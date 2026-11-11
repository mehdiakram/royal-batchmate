<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

global $wpdb;
$table_name = $wpdb->prefix . 'batchmate_rawdata';
$logs = [];
$processed = false;

// --- ???? ??????? ?????????? (???????) ---
if ( isset( $_POST['BatchMate_sync_raw_data'] ) ) {
    $sync_log     = [];
    $member_synced = 0;
    $guest_added   = 0;
    $guest_deduped = 0;

    // -- ?. ???????? status ????? --
    $updated = $wpdb->query("
        UPDATE {$wpdb->prefix}batchmate_rawdata r
        INNER JOIN {$wpdb->prefix}batchmate_member m ON r.student_id = m.student_id
        SET r.status = 'Applied'
        WHERE r.status != 'Applied'
    ");
    $member_synced = intval( $updated );

    // -- ?. Event ???????? sync ??? --
    //
    // batchmate_event_application ? rawdata_id > 0 ???? ??? ???? non-member guest?
    // Guest apply ???? ???? rawdata ?? ???? ???? row insert ????
    // ?????? ??? ??????? ?????? event ? apply ???? ?????? rawdata row ???? ???
    // (same student_id, ?????? ????? rawdata id)?
    // ?? Sync:
    //   a) ??? student_id ?? duplicate rawdata row ????? ??? ???,
    //   b) ??????? ????? row ?? ???? ????????? rawdata_id event_application ? ????? ???,
    //   c) ????? duplicate row ???? ???? ?????
    //
    // Student_id ????? guest (blank student_id) ???? skip ??? ????

    $ea_table  = $wpdb->prefix . 'batchmate_event_application';
    $raw_table = $wpdb->prefix . 'batchmate_rawdata';

    // ?a. Duplicate student_id ????? rawdata rows ?????
    $duplicates = $wpdb->get_results("
        SELECT student_id, MIN(id) AS keep_id, GROUP_CONCAT(id ORDER BY id ASC) AS all_ids
        FROM {$raw_table}
        WHERE student_id IS NOT NULL AND student_id != ''
        GROUP BY student_id
        HAVING COUNT(*) > 1
    ");

    foreach ( $duplicates as $dup ) {
        $keep_id  = intval( $dup->keep_id );
        $all_ids  = array_map( 'intval', explode( ',', $dup->all_ids ) );
        $drop_ids = array_filter( $all_ids, fn($id) => $id !== $keep_id );

        if ( empty( $drop_ids ) ) continue;

        foreach ( $drop_ids as $drop_id ) {
            // ?b. event_application ? rawdata_id ????????? ????? ???
            $wpdb->update(
                $ea_table,
                [ 'rawdata_id' => $keep_id ],
                [ 'rawdata_id' => $drop_id ],
                [ '%d' ], [ '%d' ]
            );

            // ?c. Duplicate rawdata row ???? ???
            $del = $wpdb->delete( $raw_table, [ 'id' => $drop_id ], [ '%d' ] );
            if ( $del ) {
                $guest_deduped++;
                $sync_log[] = "<span style='color:#d97706;'>[DEDUP] rawdata #{$drop_id} (student_id: {$dup->student_id}) ? kept #{$keep_id}</span>";
            }
        }
    }

    // -- ?. Summary message ???? ??? --
    $parts = [];
    if ( $member_synced > 0 )  $parts[] = "<strong>{$member_synced}</strong> member status updated";
    if ( $guest_deduped > 0 )  $parts[] = "<strong>{$guest_deduped}</strong> duplicate guest row(s) merged";
    if ( empty( $parts ) )     $parts[] = 'Everything is already up to date';

    $sync_success_msg  = 'Sync Complete � ' . implode( ', ', $parts ) . '.';
    $sync_detail_log   = $sync_log;
}

if ( isset( $_POST['BatchMate_import_raw_nonce'] ) && wp_verify_nonce( $_POST['BatchMate_import_raw_nonce'], 'BatchMate_import_raw_action' ) ) {
    
    if ( ! empty( $_FILES['csv_file']['tmp_name'] ) ) {
        $file = $_FILES['csv_file']['tmp_name'];
        $handle = fopen( $file, 'r' );
        
        if ( $handle !== FALSE ) {
            $processed = true;
            $row_count = 0;
            $success = 0;
            $skipped = 0;
            $errors = 0;

            // ????????? ???????
            $program_map = array(
                'LLB (Hons.)' => 1, 'LL.B (Hons)' => 1, 'LLB (Preliminary And Final)' => 2, 'LL.B (P & F)' => 2,
                'LLB (Final)' => 3, 'LLM (1 Year)' => 4, 'LL.M (Regular)' => 4, 'LLM (Preliminary And Final)' => 5, 
                'LL.M (Preliminary & Final)' => 5, 'B.Sc in CSE' => 6, 'B.Sc in ETE' => 7, 'B.Sc in EEE' => 8, 
                'BSc in EEE' => 8, 'B.Sc in CS' => 9, 'B.Sc in Civil' => 10, 'BA (Hons) in English' => 11, 
                'BA(Hons) in English' => 11, 'BA (Hons) in Bangla' => 12, 'BA(Hons) in Bangla' => 12, 'MCA' => 13, 
                'BBA' => 14, 'BBIS' => 15, 'MBA' => 16, 'MA in English' => 17, 'MA in ELT' => 18, 'MA in Literature' => 19, 
                'Diploma in English' => 20, 'Bachelor of Education' => 21, 'B.Ed' => 21, 'Master of Education' => 22, 'M.Ed' => 22
            );
            $default_photo_url = 'https://batchmate.org/wp-content/themes/royal-elementor-BatchMate/assets/images/noprofile.png';

            while ( ( $data = fgetcsv( $handle, 1000, "," ) ) !== FALSE ) {
                $row_count++;
                if ( $row_count == 1 && ( stripos($data[0], 'student id') !== false || stripos($data[0], 'student_id') !== false ) ) continue;

                $student_id = sanitize_text_field( isset($data[0]) ? $data[0] : '' );
                if ( empty( $student_id ) ) continue;

                $exists = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $table_name WHERE student_id = %s", $student_id ) );
                if ( $exists ) {
                    $logs[] = "<span style='color:#ffa500;'>[SKIP] Duplicate Student ID: $student_id</span>";
                    $skipped++;
                    continue;
                }

                $name       = sanitize_text_field( isset($data[1]) ? $data[1] : '' );
                                $prog_name  = trim( isset($data[3]) ? $data[3] : '' );
                $contact    = sanitize_text_field( isset($data[4]) ? $data[4] : '' );
                $status_raw = sanitize_text_field( isset($data[5]) ? $data[5] : '' );
                $email      = sanitize_email( isset($data[6]) ? $data[6] : '' );
                $csv_photo  = isset($data[7]) ? esc_url_raw( $data[7] ) : '';
                $photo_url  = !empty($csv_photo) ? $csv_photo : $default_photo_url;
                $program_id = isset( $program_map[ $prog_name ] ) ? $program_map[ $prog_name ] : 0;
                $prog_warn = ($program_id == 0 && !empty($prog_name)) ? " <span style='color:red; font-size:12px;'>(Program '$prog_name' not mapped, set to 0)</span>" : "";

                $result = $wpdb->insert( $table_name, array(
                    'student_id' => $student_id, 'name' => $name, 
                    'cell_number' => $contact, 'email' => $email, 'photo_url' => $photo_url, 'status' => $status_raw,
                    'payment_method' => '', 'payment_details' => '', 'submission_date' => current_time('mysql')
                ));

                if ( $result ) {
                    $logs[] = "<span style='color:green;'>[OK] Imported: $student_id - $name</span>" . $prog_warn;
                    $success++;
                } else {
                    $logs[] = "<span style='color:red;'>[ERROR] Failed to insert ID $student_id: " . $wpdb->last_error . "</span>";
                    $errors++;
                }
            }
            fclose( $handle );
            echo '<div class="notice notice-success is-dismissible"><p><strong>Import Completed!</strong> Success: ' . $success . ' | Skipped: ' . $skipped . ' | Errors: ' . $errors . '</p></div>';
        } else {
            echo '<div class="notice notice-error is-dismissible"><p>Could not open the CSV file.</p></div>';
        }
    } else {
        echo '<div class="notice notice-error is-dismissible"><p>Please upload a valid CSV file.</p></div>';
    }
}

// ???? ??? (????????? ??? ??????? ???)
$programs = $wpdb->get_results("SELECT id, program_name FROM {$wpdb->prefix}batchmate_program");
$payment_modes = $wpdb->get_results("SELECT id, name FROM {$wpdb->prefix}batchmate_payment_modes");
?>

<div class="wrap">
    <div class="BatchMate-page-header-animated">
        <div class="BatchMate-header-left">
            <h1><span class="dashicons dashicons-database-import"></span> Raw Data Management</h1>
            <p>Import, sync, and manage raw student data records.</p>
        </div>
    </div>
    
    <div class="card" style="max-width: 100%; padding: 20px; margin-top: 20px;">
        <h2 style="border-bottom: 1px solid #eee; padding-bottom: 10px; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center;">
            <span>Existing Raw Data List</span>
            <form method="post" action="<?php echo esc_url( admin_url('admin-post.php') ); ?>" style="display:inline; margin:0;">
                <input type="hidden" name="action" value="BatchMate_export_raw_data_action">
                <button type="submit" class="button button-secondary" style="margin-right: 5px;">Export to CSV</button>
            </form>
            <form method="post" style="display:inline; margin:0;">
                <button type="submit" name="BatchMate_sync_raw_data" class="button button-primary">Sync Members</button>
            </form>
        </h2>
        <table id="raw-data-table" class="display wp-list-table widefat striped" style="width:100%">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Student ID</th>
                    <th>Name</th>
                    <th>Contact</th>
                    <th>Status</th>
                    <th>Photo</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $raw_data = $wpdb->get_results( "SELECT * FROM $table_name ORDER BY id DESC" );
                if ( $raw_data ) {
                    foreach ( $raw_data as $row ) {
                        // Check if member already exists
                        $member = $wpdb->get_row( $wpdb->prepare( "SELECT id, member_number, photo_url FROM {$wpdb->prefix}batchmate_member WHERE student_id = %s", $row->student_id ) );
                        $application = null;
                        if ( !$member ) {
                            $application = $wpdb->get_row( $wpdb->prepare( "SELECT id, status FROM {$wpdb->prefix}batchmate_application WHERE student_id = %s ORDER BY id DESC LIMIT 1", $row->student_id ) );
                        }

                        $display_status = $row->status;
                        $display_photo = !empty($row->photo_url) ? $row->photo_url : 'https://batchmate.org/wp-content/themes/royal-elementor-BatchMate/assets/images/noprofile.png';
                        
                        $status_color = ($display_status === 'Applied') ? 'green' : 'inherit';
                        $status_weight = ($display_status === 'Applied') ? 'bold' : 'normal';
                        $btn_disabled = ($display_status === 'Applied') ? 'disabled' : '';
                        $btn_text = ($display_status === 'Applied') ? 'Applied' : 'Application';
                        $btn_class = ($display_status === 'Applied') ? 'button-secondary' : 'button-primary';

                        if ( $member ) {
                            $display_status = 'Member';
                            $status_color = '#2271b1'; // WordPress blue
                            $status_weight = 'bold';
                            if ( !empty($member->photo_url) ) {
                                $display_photo = $member->photo_url;
                            }
                        } elseif ( $application && $application->status === 'pending' ) {
                            $display_status = 'Applied';
                            $status_color = 'green';
                            $status_weight = 'bold';
                            $btn_disabled = 'disabled';
                            $btn_text = 'Applied';
                            $btn_class = 'button-secondary';
                        }

                        $photo_html = '<img src="' . esc_url($display_photo) . '" style="width:40px;height:40px;object-fit:cover;border-radius:50%;border:1px solid #ddd;">';

                        echo '<tr>';
                        echo '<td>' . esc_html($row->id) . '</td>';
                        echo '<td>' . esc_html($row->student_id) . '</td>';
                        echo '<td>' . esc_html($row->name) . '</td>';
                        echo '<td>' . esc_html($row->cell_number) . '</td>';
                        echo '<td style="color:'.$status_color.'; font-weight:'.$status_weight.';">' . esc_html($display_status) . '</td>';
                        echo '<td>' . $photo_html . '</td>';
                        
                        if ( $member ) {
                            $profile_url = home_url('/member/' . strtolower($member->member_number) . '/');
                            echo '<td>
                                    <a href="' . esc_url($profile_url) . '" target="_blank" class="button button-secondary">
                                        <span class="dashicons dashicons-admin-users" style="vertical-align: text-top;"></span> View Profile
                                    </a>
                                  </td>';
                        } else {
                            echo '<td>
                                    <button class="button ' . $btn_class . ' app-transfer-btn" 
                                        data-id="' . $row->id . '" ' . $btn_disabled . '>
                                        <span class="dashicons dashicons-migrate" style="vertical-align: text-top;"></span> ' . $btn_text . '
                                    </button>
                                  </td>';
                        }
                        echo '</tr>';
                    }
                }
                ?>
            </tbody>
        </table>
    </div>

    <div class="card" style="max-width: 800px; padding: 20px; margin-top: 30px; background: #f9f9f9; border: 1px solid #ccd0d4;">
        <h2 style="display: flex; justify-content: space-between; align-items: center;">
            <span>Import New Data (CSV)</span>
            <a href="<?php echo esc_url(ROYAL_BATCHMATE_PLUGIN_URL . '/assets/sample.csv'); ?>" download class="button button-secondary" style="font-size: 13px; font-weight: normal;">Download Sample CSV</a>
        </h2>
        <p>Upload your CSV file to import members into the <code>batchmate_rawdata</code> table.</p>
        <form method="post" enctype="multipart/form-data" style="margin-top: 15px;">
            <?php wp_nonce_field( 'BatchMate_import_raw_action', 'BatchMate_import_raw_nonce' ); ?>
            <div style="margin-bottom: 20px;">
                <input type="file" name="csv_file" accept=".csv" required style="padding: 10px; background: #fff; border: 1px solid #ccc; width: 100%;">
            </div>
            <button type="submit" class="button button-primary button-hero">Import Data</button>
        </form>
        <?php if ( $processed ) : ?>
            <div style="margin-top: 20px; border-top: 1px solid #ddd; padding-top: 15px;">
                <h3>Import Log:</h3>
                <div style="background: #fff; border: 1px solid #ddd; padding: 15px; max-height: 300px; overflow-y: auto; font-family: monospace; font-size: 12px;">
                    <?php if ( empty( $logs ) ) { echo "No data processed."; } else { foreach ( $logs as $log ) { echo $log . "<br>"; } } ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="BatchMate-modal-overlay" id="app-transfer-overlay" style="display:none;"></div>
<div class="BatchMate-modal-panel" id="app-transfer-modal">
    <div class="BatchMate-modal-header">
        <h2>Process Application</h2>
        <button type="button" class="BatchMate-modal-close-btn" onclick="closeAppModal()">&times;</button>
    </div>
    <div class="BatchMate-modal-content large-modal">
        <form id="app-transfer-form" enctype="multipart/form-data">
            <input type="hidden" name="raw_id" id="modal_raw_id">
            <input type="hidden" name="existing_photo_url" id="modal_existing_photo">
            
            <div style="text-align: center; margin-bottom: 20px; padding: 10px; background: #f0f0f1; border-radius: 8px;">
                <img id="modal_photo_preview" src="" style="width: 120px; height: 120px; object-fit: cover; border-radius: 50%; border: 3px solid #fff; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">
                <br>
                <label for="app_photo" class="button button-secondary" style="margin-top: 10px;">Upload New Photo</label>
                <input type="file" id="app_photo" name="app_photo" style="display: none;" accept="image/*">
                <p style="font-size: 12px; color: #666; margin-top: 5px;">Image will be resized to 400x400px</p>
            </div>

            <h3 class="BatchMate-section-title">Personal Information</h3>
            <div class="BatchMate-form-row">
                <div class="swal-edit-group"><label>Name *</label><input type="text" name="name" id="modal_name" class="regular-text" required></div>
                <div class="swal-edit-group"><label>Email *</label><input type="email" name="email" id="modal_email" class="regular-text" required></div>
            </div>
            
            <div class="BatchMate-form-row">
                <div class="swal-edit-group"><label>Cell Number *</label><input type="text" name="cell_number" id="modal_cell" class="regular-text" required></div>
                <div class="" style="flex: 1 1 48%;">
                    <label>Show Cell?</label><br>
                    <input type="checkbox" name="cell_show" id="modal_cell_show" value="1" checked> Yes
                </div>
            </div>

            <div class="BatchMate-form-row">
                <div class="swal-edit-group"><label>Alternative Number</label><input type="text" name="land_phone_number" id="modal_land_phone" class="regular-text"></div>
                <div class="swal-edit-group">
                    <label>Blood Group *</label>
                    <select name="blood_group" id="modal_blood_group" class="regular-text" required>
                        <option value="">Select</option>
                        <option value="A+">A+</option><option value="A-">A-</option><option value="B+">B+</option><option value="B-">B-</option>
                        <option value="O+">O+</option><option value="O-">O-</option><option value="AB+">AB+</option><option value="AB-">AB-</option>
                    </select>
                </div>
            </div>
            <div class="BatchMate-form-row">
                <div class="swal-edit-group"><label>Date of Birth</label><input type="date" name="date_of_birth" id="modal_dob" class="regular-text"></div>
                <div class="swal-edit-group">
                    <label>Gender</label>
                    <select name="gender" id="modal_gender" class="regular-text">
                        <option value="Male">Male</option><option value="Female">Female</option><option value="Other">Other</option>
                    </select>
                </div>
            </div>

            <h3 class="BatchMate-section-title">Academic Information</h3>
            <div class="BatchMate-form-row">
                <div class="swal-edit-group"><label>Student ID *</label><input type="text" name="student_id" id="modal_student_id" class="regular-text" required></div>
                
            </div>
            <div class="BatchMate-form-row">
                <div class="swal-edit-group">
                    <label>Program *</label>
                    <select name="program_id" id="modal_program_id" class="regular-text" required>
                        <option value="">Select Program</option>
                        <?php foreach($programs as $prog): ?>
                            <option value="<?php echo $prog->id; ?>"><?php echo esc_html($prog->program_name); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
            </div>
            <div class="BatchMate-form-row">
                
            </div>

            <h3 class="BatchMate-section-title">Address</h3>
            <div class="BatchMate-form-row">
                <div class="swal-edit-group"><label>Present Address</label><textarea name="present_address" id="modal_present_address" class="regular-text" rows="2"></textarea></div>
                <div class="swal-edit-group"><label>Permanent Address</label><textarea name="permanent_address" id="modal_permanent_address" class="regular-text" rows="2"></textarea></div>
            </div>

            <h3 class="BatchMate-section-title">Professional & Social</h3>
            <div class="BatchMate-form-row">
                <div class="swal-edit-group"><label>Designation/Position</label><input type="text" name="employment_position" id="modal_position" class="regular-text"></div>
                <div class="swal-edit-group"><label>Organization Address</label><textarea name="employment_address" id="modal_emp_address" class="regular-text" rows="2"></textarea></div>
            </div>
            <div class="BatchMate-form-row">
                <div class="swal-edit-group"><label>Facebook URL</label><input type="text" name="facebook_url" id="modal_fb" class="regular-text"></div>
                <div class="swal-edit-group"><label>LinkedIn URL</label><input type="text" name="linkedin_url" id="modal_li" class="regular-text"></div>
            </div>
            <div class="BatchMate-form-row">
                <div class="swal-edit-group"><label>X (Twitter) URL</label><input type="text" name="x_url" id="modal_x" class="regular-text"></div>
                <div class="swal-edit-group"><label>Website URL</label><input type="text" name="website" id="modal_web" class="regular-text"></div>
            </div>

            <h3 class="BatchMate-section-title">Payment & Status</h3>
            <div class="BatchMate-form-row">
                <div class="swal-edit-group">
                    <label>Payment Method</label>
                    <select name="payment_method" id="modal_payment_method" class="regular-text">
                        <?php foreach($payment_modes as $pmode): ?>
                            <option value="<?php echo esc_attr($pmode->name); ?>"><?php echo esc_html($pmode->name); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="swal-edit-group"><label>TrxID / Details</label><input type="text" name="payment_details" id="modal_payment_details" class="regular-text"></div>
            </div>

            <div class="BatchMate-modal-footer">
                <button type="submit" class="button button-primary button-large" id="btn-submit-app">Submit Application</button>
            </div>
        </form>
    </div>
</div>

<style>
    .BatchMate-section-title { border-bottom: 1px solid #ddd; padding-bottom: 5px; margin-top: 20px; color: #2271b1; font-size: 1.1em; }
    .large-modal { max-width: 800px !important; }
    .swal-edit-group { flex: 1 1 48%; margin-bottom: 10px; }
</style>

<script type="text/javascript">
jQuery(document).ready(function($) {
    var table = $('#raw-data-table').DataTable({ "order": [[ 0, "desc" ]], "pageLength": 25, "responsive": true });

    $('#raw-data-table tbody').on('click', '.app-transfer-btn', function() {
        var id = $(this).data('id');
        Swal.fire({ title: 'Loading...', showConfirmButton: false, willOpen: () => { Swal.showLoading(); } });

        $.ajax({
            url: ajaxurl, type: 'POST',
            data: { action: 'BatchMate_get_raw_data_details', id: id },
            success: function(res) {
                Swal.close();
                if(res.success) {
                    var d = res.data;
                    // Populate Fields
                    $('#modal_raw_id').val(d.id);
                    $('#modal_name').val(d.name);
                    $('#modal_email').val(d.email);
                    $('#modal_cell').val(d.cell_number);
                    $('#modal_student_id').val(d.student_id);
                    // FIX: Payment details will be populated if data exists, else empty
                    $('#modal_payment_details').val(d.payment_details ? d.payment_details : '');
                    
                    $('#modal_existing_photo').val(d.photo_url);
                    
                    // Clear other fields
                    $('#modal_land_phone, #modal_dob, #modal_enrollment, #modal_completion, #modal_present_address, #modal_permanent_address, #modal_position, #modal_emp_address, #modal_fb, #modal_li, #modal_x, #modal_web').val('');
                    $('#modal_gender').val('Male'); 
                    $('#modal_blood_group').val('');

                    var photo = d.photo_url ? d.photo_url : '<?php echo ROYAL_BATCHMATE_PLUGIN_URL; ?>/assets/images/noprofile.png';
                    $('#modal_photo_preview').attr('src', photo);
                    openAppModal();
                } else {
                    Swal.fire('Error', 'Data not found', 'error');
                }
            }
        });
    });

    function batchmateResizeTo400(file, callback) {
        if (!file || !file.type.match(/^image\//)) {
            if (callback) callback(file, null);
            return;
        }
        const reader = new FileReader();
        reader.onload = function(e) {
            const img = new Image();
            img.onload = function() {
                const canvas = document.createElement('canvas');
                canvas.width = 400;
                canvas.height = 400;
                const ctx = canvas.getContext('2d');
                ctx.imageSmoothingEnabled = true;
                ctx.imageSmoothingQuality = 'high';

                const w = img.width;
                const h = img.height;
                const cropSize = Math.min(w, h);
                const sx = (w - cropSize) / 2;
                const sy = (h - cropSize) / 2;

                ctx.drawImage(img, sx, sy, cropSize, cropSize, 0, 0, 400, 400);

                if (canvas.toBlob) {
                    canvas.toBlob(function(blob) {
                        if (!blob) {
                            if (callback) callback(file, e.target.result);
                            return;
                        }
                        const baseName = file.name ? file.name.replace(/\.[^/.]+$/, "") : 'photo';
                        const newFileName = baseName + '.jpg';
                        const resizedFile = new File([blob], newFileName, { type: 'image/jpeg', lastModified: Date.now() });
                        if (callback) callback(resizedFile, canvas.toDataURL('image/jpeg', 0.90));
                    }, 'image/jpeg', 0.90);
                } else {
                    if (callback) callback(file, e.target.result);
                }
            };
            img.onerror = function() {
                if (callback) callback(file, null);
            };
            img.src = e.target.result;
        };
        reader.onerror = function() {
            if (callback) callback(file, null);
        };
        reader.readAsDataURL(file);
    }

    let isResizingAppPhoto = false;
    $('#app_photo').change(function() {
        if (isResizingAppPhoto) return;
        const input = this;
        const file = input.files && input.files[0];
        if (!file) {
            return;
        }
        isResizingAppPhoto = true;
        batchmateResizeTo400(file, function(resizedFile, previewUrl) {
            if (previewUrl) {
                $('#modal_photo_preview').attr('src', previewUrl);
            }
            if (resizedFile && window.DataTransfer) {
                const dt = new DataTransfer();
                dt.items.add(resizedFile);
                input.files = dt.files;
            }
            isResizingAppPhoto = false;
        });
    });

    $('#app-transfer-form').on('submit', function(e) {
        e.preventDefault();
        var formData = new FormData(this);
        formData.append('action', 'BatchMate_transfer_raw_to_app');
        formData.append('_wpnonce', '<?php echo wp_create_nonce("BatchMate_import_raw_action"); ?>');

        $('#btn-submit-app').prop('disabled', true).text('Processing...');

        $.ajax({
            url: ajaxurl, type: 'POST', data: formData, processData: false, contentType: false,
            success: function(res) {
                if(res.success) {
                    closeAppModal();
                    Swal.fire({
                        title: 'Success!',
                        text: res.data,
                        icon: 'success',
                        timer: 1500,
                        showConfirmButton: false
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire('Error!', res.data, 'error');
                    $('#btn-submit-app').prop('disabled', false).text('Submit Application');
                }
            },
            error: function() {
                Swal.fire('Error', 'Request failed', 'error');
                $('#btn-submit-app').prop('disabled', false).text('Submit Application');
            }
        });
    });
});

function openAppModal() { jQuery('#app-transfer-overlay').show(); jQuery('#app-transfer-modal').addClass('is-open'); }
function closeAppModal() { jQuery('#app-transfer-modal').removeClass('is-open'); setTimeout(function() { jQuery('#app-transfer-overlay').hide(); }, 300); }

<?php if ( !empty($sync_success_msg) ) : ?>
jQuery(document).ready(function($) {
    Swal.fire({
        title: 'Success!',
        text: '<?php echo esc_js($sync_success_msg); ?>',
        icon: 'success',
        timer: 1500,
        showConfirmButton: false
    });
});
<?php endif; ?>

jQuery(document).ready(function($) {
    $('button[name="BatchMate_sync_raw_data"]').on('click', function() {
        Swal.fire({ 
            title: 'Syncing.....', 
            allowOutsideClick: false,
            showConfirmButton: false, 
            willOpen: () => { Swal.showLoading(); } 
        });
    });
});
</script>
