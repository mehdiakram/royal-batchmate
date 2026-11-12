<?php

if ( ! defined( 'ABSPATH' ) ) exit;
?>
<div class="wrap">
    <div class="BatchMate-page-header-animated">
        <div class="BatchMate-header-left">
            <h1><span class="dashicons dashicons-groups"></span> Approved <?php echo esc_html(batchmate_get_label('members')); ?> List</h1>
            <p>View and manage all approved <?php echo esc_html(batchmate_get_label('batchmate')); ?> members.</p>
        </div>
        <div class="BatchMate-header-actions">
            <a href="<?php echo esc_url( admin_url( 'admin-post.php?action=BatchMate_export_members' ) ); ?>" class="BatchMate-header-btn BatchMate-header-btn-primary">
                <span class="dashicons dashicons-download"></span> Export to CSV
            </a>
        </div>
    </div>

    <!-- Filter Row -->
    <div id="BatchMate-member-filters" style="display:flex; gap:12px; align-items:flex-end; flex-wrap:wrap; margin-bottom:16px; padding:14px 16px; background:#f0f0f1; border:1px solid #c3c4c7; border-radius:6px;">
        <div style="display:flex; flex-direction:column; gap:4px;">
            <label for="filter-institution" style="font-weight:600; font-size:13px;"><?php echo esc_html( function_exists('batchmate_get_field_label') ? batchmate_get_field_label('reg_institution', 'Institute') : 'Institute' ); ?></label>
            <select id="filter-institution" style="min-width:180px; padding:6px 10px; border:1px solid #c3c4c7; border-radius:4px;">
                <option value="">-- All Institutes --</option>
                <?php
                global $wpdb;
                $institutes = $wpdb->get_col( "SELECT DISTINCT institution FROM {$wpdb->prefix}batchmate_member WHERE institution IS NOT NULL AND TRIM(institution) != '' ORDER BY institution ASC" );
                foreach ( $institutes as $inst ) {
                    echo '<option value="' . esc_attr( $inst ) . '">' . esc_html( $inst ) . '</option>';
                }
                ?>
            </select>
        </div>
        <div style="display:flex; flex-direction:column; gap:4px;">
            <label for="filter-batch" style="font-weight:600; font-size:13px;">Batch</label>
            <select id="filter-batch" style="min-width:120px; padding:6px 10px; border:1px solid #c3c4c7; border-radius:4px;">
                <option value="">-- All Batches --</option>
                <?php
                $batches = $wpdb->get_col( "SELECT DISTINCT batch FROM {$wpdb->prefix}batchmate_member WHERE batch IS NOT NULL AND batch != '' ORDER BY batch ASC" );
                foreach ( $batches as $batch ) {
                    echo '<option value="' . esc_attr( $batch ) . '">' . esc_html( $batch ) . '</option>';
                }
                ?>
            </select>
        </div>
        <div style="display:flex; flex-direction:column; gap:4px;">
            <label for="filter-gender" style="font-weight:600; font-size:13px;">Gender</label>
            <select id="filter-gender" style="min-width:120px; padding:6px 10px; border:1px solid #c3c4c7; border-radius:4px;">
                <option value="">-- All --</option>
                <option value="Male">Male</option>
                <option value="Female">Female</option>
            </select>
        </div>
        <div style="display:flex; flex-direction:column; gap:4px;">
            <label for="filter-blood-group" style="font-weight:600; font-size:13px;">Blood Group</label>
            <select id="filter-blood-group" style="min-width:120px; padding:6px 10px; border:1px solid #c3c4c7; border-radius:4px;">
                <option value="">-- All --</option>
                <option value="A+">A+</option>
                <option value="A-">A-</option>
                <option value="B+">B+</option>
                <option value="B-">B-</option>
                <option value="O+">O+</option>
                <option value="O-">O-</option>
                <option value="AB+">AB+</option>
                <option value="AB-">AB-</option>
            </select>
        </div>
        <div style="display:flex; flex-direction:column; gap:4px;">
            <label for="filter-payment-status" style="font-weight:600; font-size:13px;">Payment Status</label>
            <select id="filter-payment-status" style="min-width:120px; padding:6px 10px; border:1px solid #c3c4c7; border-radius:4px;">
                <option value="">-- All --</option>
                <option value="paid">Paid</option>
                <option value="unpaid">Unpaid</option>
            </select>
        </div>
        <div style="display:flex; flex-direction:column; gap:4px; justify-content:flex-end;">
            <button type="button" id="filter-reset-btn" class="button" style="padding:6px 14px;">Reset Filters</button>
        </div>
    </div>

    <table id="members-table" class="display wp-list-table widefat striped" style="width:100%">
        <thead>
            <tr>
                <th>Mem. ID</th>
                <th>Name</th>
                <th>Photo</th>
                <th><?php echo esc_html( function_exists('batchmate_get_field_label') ? batchmate_get_field_label('reg_institution', 'Institute') : 'Institute' ); ?></th>
                <th>Contact</th>
                <th>Email</th>
                <th>WP User</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            </tbody>
    </table>
</div>

<div id="BatchMate-edit-modal-backdrop" style="display: none;"></div>
<div id="BatchMate-edit-modal-wrapper" style="display: none;">
    <div class="BatchMate-modal-header">
        <h2>Edit Member Details</h2>
        <button type="button" class="BatchMate-modal-close">&times;</button>
    </div>
    <div class="BatchMate-modal-content">
        <form id="BatchMate-edit-form">
            <div class="swal-edit-grid" style="text-align: left;">
                <input type="hidden" id="modal_member_id" value="">
                
                <h3 class="swal-section-title">Personal Information</h3>
                <div class="swal-edit-row">
                    <div class="swal-edit-group">
                        <label>Member ID *</label>
                        <input type="text" id="modal_member_number" value="">
                    </div>
                    <div class="swal-edit-group">
                        <label>Name (English) *</label>
                        <input type="text" id="modal_name" value="">
                    </div>
                </div>
                
                <div class="swal-edit-row">
                    <div class="swal-edit-group">
                        <label>Name (Bangla)</label>
                        <input type="text" id="modal_name_bn" value="">
                    </div>
                    <div class="swal-edit-group">
                        <label>Email (Optional)</label>
                        <input type="email" id="modal_email" value="" placeholder="Auto-generated if blank">
                    </div>
                </div>
                
                <div class="swal-edit-row">
                    <div class="swal-edit-group">
                        <label>Cell Number *</label>
                        <input type="text" id="modal_cell_number" value="">
                    </div>
                
                <div class="swal-edit-row">
                    <div class="swal-edit-group">
                        <label>Alternative Number (Optional)</label>
                        <input type="text" id="modal_land_phone_number" value="">
                    </div>
                    <div class="swal-edit-group" style="padding-top: 30px;">
                        <input type="checkbox" id="modal_cell_show" name="modal_cell_show" value="1" style="width: 16px; margin-right: 10px; height: 16px;">
                        <label for="modal_cell_show" style="margin-bottom: 0; font-weight: normal; display: inline;">Show cell number</label>
                    </div>
                </div>

                <div class="swal-edit-row">
                    <div class="swal-edit-group">
                        <label>Date of Birth</label>
                        <input type="text" id="modal_date_of_birth" value="" autocomplete="off" placeholder="YYYY-MM-DD">
                    </div>
                    <div class="swal-edit-group">
                        <label>Blood Group</label>
                        <select id="modal_blood_group" class="select2-field">
                            <option value="">Select...</option>
                        </select>
                    </div>
                </div>

                <div class="swal-edit-row">
                    <div class="swal-edit-group">
                        <label>Gender *</label>
                        <select id="modal_gender" class="select2-field">
                            <option value="">Select Gender...</option>
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                        </select>
                    </div>
                    <div class="swal-edit-group">
                        <label>Religion</label>
                        <select id="modal_religion" class="select2-field">
                            <option value="">Select Religion...</option>
                            <option value="Islam">Islam</option>
                            <option value="Hinduism">Hinduism</option>
                            <option value="Buddhism">Buddhism</option>
                            <option value="Christianity">Christianity</option>
                            <option value="Others">Others</option>
                        </select>
                    </div>
                </div>

                <h3 class="swal-section-title">Academic Information</h3>
                <div class="swal-edit-row">
                    <div class="swal-edit-group">
                        <label><?php echo esc_html( function_exists('batchmate_get_field_label') ? batchmate_get_field_label('reg_institution', 'Institute') : 'Institute' ); ?> *</label>
                        <input type="text" id="modal_institution" value="">
                    </div>
                    <div class="swal-edit-group">
                        <label>SSC Roll (Optional)</label>
                        <input type="text" id="modal_student_id" value="">
                    </div>
                </div>

                <div class="swal-edit-row">
                    <div class="swal-edit-group">
                        <label>Name of Board (Optional)</label>
                        <select id="modal_board" class="select2-field">
                            <option value="">Select Board</option>
                            <option value="barisal">Barisal</option>
                            <option value="chittagong">Chittagong</option>
                            <option value="comilla">Comilla</option>
                            <option value="dhaka">Dhaka</option>
                            <option value="dinajpur">Dinajpur</option>
                            <option value="jessore">Jessore</option>
                            <option value="madrasah">Madrasah</option>
                            <option value="mymensingh">Mymensingh</option>
                            <option value="rajshahi">Rajshahi</option>
                            <option value="sylhet">Sylhet</option>
                            <option value="tec">Technical</option>
                        </select>
                    </div>
                    <div class="swal-edit-group">
                        <label>Group (Optional)</label>
                        <select id="modal_edu_group" class="select2-field">
                            <option value="">Select Group...</option>
                            <option value="Science">Science</option>
                            <option value="Commerce">Commerce</option>
                            <option value="Humanities">Humanities</option>
                            <option value="Vocational">Vocational</option>
                            <option value="Others">Others</option>
                        </select>
                    </div>
                </div>

                <h3 class="swal-section-title">Professional Information</h3>
                <div class="swal-edit-row">
                    <div class="swal-edit-group">
                        <label>Organization</label>
                        <input type="text" id="modal_organization" value="">
                    </div>
                    <div class="swal-edit-group">
                        <label>Position / Designation</label>
                        <input type="text" id="modal_employment_position" value="">
                    </div>
                </div>
                <div class="swal-edit-row">
                    <div class="swal-edit-group">
                        <label>Industry</label>
                        <input type="text" id="modal_industry" value="">
                    </div>
                    <div class="swal-edit-group">
                        <label>Work Experience (Years)</label>
                        <input type="number" id="modal_work_experience" value="" placeholder="e.g. 5">
                    </div>
                </div>
                <div class="swal-edit-row">
                    <div class="swal-edit-group full">
                        <label>Organization Address</label>
                        <textarea id="modal_employment_address"></textarea>
                    </div>
                </div>
                <div class="swal-edit-row">
                    <div class="swal-edit-group full">
                        <label>Previous Organizations</label>
                        <textarea id="modal_previous_organizations"></textarea>
                    </div>
                </div>
                <div class="swal-edit-row">
                    <div class="swal-edit-group full">
                        <label>Present Address</label>
                        <textarea id="modal_present_address"></textarea>
                    </div>
                </div>
                <?php
                require_once ROYAL_BATCHMATE_PLUGIN_DIR . '/includes/countries.php';
                $all_countries = BatchMate_get_countries();
                $bd_districts = ['Bagerhat','Bandarban','Barguna','Barishal','Bhola','Bogura','Brahmanbaria','Chandpur', 'Chapai Nawabganj', 'Chattogram','Chuadanga',"Cox's Bazar",'Cumilla','Dhaka','Dinajpur','Faridpur','Feni','Gaibandha','Gazipur','Gopalganj','Habiganj','Jamalpur','Jashore','Jhalokati','Jhenaidah','Joypurhat','Khagrachari','Khulna','Kishoreganj','Kurigram','Kushtia','Lakshmipur','Lalmonirhat','Madaripur','Magura','Manikganj','Meherpur','Moulvibazar','Munshiganj','Mymensingh','Naogaon','Narail','Narayanganj','Narsingdi','Natore','Netrokona','Nilphamari','Noakhali','Pabna','Panchagarh','Patuakhali','Pirojpur','Rajbari','Rajshahi','Rangamati','Rangpur','Satkhira','Shariatpur','Sherpur','Sirajganj','Sunamganj','Sylhet','Tangail','Thakurgaon'];
                ?>
                <!-- Present Country + District + Upazila -->
                <div class="swal-edit-row">
                    <div class="swal-edit-group">
                        <label>Present Country</label>
                        <select id="modal_present_country" class="select2-field">
                            <option value="">Select Country...</option>
                            <?php foreach ($all_countries as $c) : ?>
                            <option value="<?php echo esc_attr($c); ?>"><?php echo esc_html($c); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="swal-edit-group" id="wrap_present_district">
                        <label><?php echo esc_html( function_exists('batchmate_get_field_label') ? batchmate_get_field_label('reg_present_district', 'Present District') : 'Present District' ); ?></label>
                        <select id="modal_present_district" class="select2-field">
                            <option value="">Select District...</option>
                            <?php foreach ($bd_districts as $dist) : ?>
                            <option value="<?php echo esc_attr($dist); ?>"><?php echo esc_html($dist); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="swal-edit-group" id="wrap_present_upazila">
                        <label><?php echo esc_html( function_exists('batchmate_get_field_label') ? batchmate_get_field_label('reg_present_upazila', 'Present Thana / Upazila') : 'Present Thana / Upazila' ); ?></label>
                        <select id="modal_present_upazila" class="select2-field">
                            <option value="">Select Thana / Upazila...</option>
                        </select>
                    </div>
                </div>
                <!-- Permanent Country + District + Upazila -->
                <div class="swal-edit-row">
                    <div class="swal-edit-group">
                        <label>Permanent Country</label>
                        <select id="modal_permanent_country" class="select2-field">
                            <option value="">Select Country...</option>
                            <?php foreach ($all_countries as $c) : ?>
                            <option value="<?php echo esc_attr($c); ?>"><?php echo esc_html($c); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="swal-edit-group" id="wrap_permanent_district">
                        <label><?php echo esc_html( function_exists('batchmate_get_field_label') ? batchmate_get_field_label('reg_permanent_district', 'Permanent District') : 'Permanent District' ); ?></label>
                        <select id="modal_permanent_district" class="select2-field">
                            <option value="">Select District...</option>
                            <?php foreach ($bd_districts as $dist) : ?>
                            <option value="<?php echo esc_attr($dist); ?>"><?php echo esc_html($dist); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="swal-edit-group" id="wrap_permanent_upazila">
                        <label><?php echo esc_html( function_exists('batchmate_get_field_label') ? batchmate_get_field_label('reg_permanent_upazila', 'Permanent Thana / Upazila') : 'Permanent Thana / Upazila' ); ?></label>
                        <select id="modal_upazila" class="select2-field">
                            <option value="">Select Thana / Upazila...</option>
                        </select>
                    </div>
                </div>
                <div class="swal-edit-row">
                    <div class="swal-edit-group full">
                        <label>Permanent Address</label>
                        <textarea id="modal_permanent_address"></textarea>
                    </div>
                </div>
                
                <h3 class="swal-section-title">Additional Information & Social Links</h3>
                <div class="swal-edit-row">
                    <div class="swal-edit-group full">
                        <label>About Me (Bio)</label>
                        <textarea id="modal_about_me" rows="3"></textarea>
                    </div>
                </div>
                <div class="swal-edit-row">
                    <div class="swal-edit-group full">
                        <label>Skills (Comma-separated)</label>
                        <input type="text" id="modal_skills" value="">
                    </div>
                </div>

                <div class="swal-edit-row">
                    <div class="swal-edit-group">
                        <label>Facebook URL</label>
                        <input type="text" id="modal_facebook_url" value="">
                    </div>
                    <div class="swal-edit-group">
                        <label>LinkedIn URL</label>
                        <input type="text" id="modal_linkedin_url" value="">
                    </div>
                </div>
                <div class="swal-edit-row">
                    <div class="swal-edit-group">
                        <label>X (Twitter) URL</label>
                        <input type="text" id="modal_x_url" value="">
                    </div>
                    <div class="swal-edit-group">
                        <label>Website</label>
                        <input type="text" id="modal_website" value="">
                    </div>
                </div>
                
                <div class="swal-edit-row">
                    <div class="swal-edit-group full">
                        <label>Change Photo (400x400)</label>
                        <input type="file" id="modal_photo" accept="image/jpeg, image/png">
                        <img id="modal_photo_preview" src="" alt="Photo Preview" style="display: none;">
                    </div>
                </div>
            </div>
        </form>
    </div>
    <div class="BatchMate-modal-footer">
        <button type="button" class="button BatchMate-modal-close">Cancel</button>
        <button type="button" id="BatchMate-modal-save" class="button button-primary">Save Changes</button>
    </div>
</div>

<?php
$bm_ml_ver = defined( 'ROYAL_BATCHMATE_VERSION' ) ? ROYAL_BATCHMATE_VERSION . '.' . (string) @filemtime( ROYAL_BATCHMATE_PLUGIN_DIR . 'assets/admin-members-list.js' ) : (string) time();
wp_enqueue_script( 'batchmate-admin-members-list-js', ROYAL_BATCHMATE_PLUGIN_URL . 'assets/admin-members-list.js', array( 'jquery', 'datatables-js', 'sweetalert2-js', 'select2-js' ), $bm_ml_ver, true );
?>
<script type="text/javascript">
window.batchmate_members_cfg = {
    member_base_url: <?php echo json_encode( home_url('/member/') ); ?>,
    print_base_url:  <?php echo json_encode( home_url('/print-member/') ); ?>,
    bdUpazilas:      <?php echo json_encode( function_exists('BatchMate_get_bd_upazilas') ? BatchMate_get_bd_upazilas() : [] ); ?>
};
</script>
