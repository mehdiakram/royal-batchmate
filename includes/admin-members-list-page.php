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
<script type="text/javascript">

    jQuery(document).ready(function($) {

        const ajax_url = batchmate_admin_ajax.ajax_url;
        const member_nonce = batchmate_admin_ajax.member_nonce;
        const dept_nonce = batchmate_admin_ajax.dept_nonce;
        const prog_nonce = batchmate_admin_ajax.prog_nonce;
        const member_base_url = '<?php echo home_url('/member/'); ?>';
        const bdUpazilas = <?php echo json_encode( function_exists('BatchMate_get_bd_upazilas') ? BatchMate_get_bd_upazilas() : [] ); ?>;

        function populateModalUpazilas($distSelect, $upzSelect, selectedVal) {
            var dist = $distSelect.val();
            var list = (bdUpazilas && bdUpazilas[dist]) || [];
            var opts = '<option value="">Select Thana / Upazila...</option>';
            list.forEach(function(u) {
                var sel = (selectedVal && selectedVal === u) ? ' selected' : '';
                opts += '<option value="' + u + '"' + sel + '>' + u + '</option>';
            });
            $upzSelect.html(opts);
            if ($.fn.select2) {
                $upzSelect.trigger('change.select2');
            }
        }

        $('#modal_present_district').on('change', function() {
            populateModalUpazilas($(this), $('#modal_present_upazila'), '');
        });
        $('#modal_permanent_district').on('change', function() {
            populateModalUpazilas($(this), $('#modal_upazila'), '');
        });

        $('#modal_present_country').on('change', function() {
            var isBD = $(this).val() === 'Bangladesh';
            $('#wrap_present_district, #wrap_present_upazila').toggle(isBD);
            if (!isBD) {
                $('#modal_present_district, #modal_present_upazila').val('');
                if ($.fn.select2) $('#modal_present_district, #modal_present_upazila').trigger('change.select2');
            }
        });
        $('#modal_permanent_country').on('change', function() {
            var isBD = $(this).val() === 'Bangladesh';
            $('#wrap_permanent_district, #wrap_permanent_upazila').toggle(isBD);
            if (!isBD) {
                $('#modal_permanent_district, #modal_upazila').val('');
                if ($.fn.select2) $('#modal_permanent_district, #modal_upazila').trigger('change.select2');
            }
        });

        var allPrograms = [];

        // Load all programs for filter dropdown
        $.ajax({
            url: ajax_url, type: 'POST',
            data: { action: 'BatchMate_get_programs_for_dropdown', _wpnonce: prog_nonce }
        }).done(function(res) {
            if (res.success) {
                allPrograms = res.data;
            }
        });

        $('#filter-institution').on('change', function() { table.ajax.reload(); });
        $('#filter-batch').on('change', function() { table.ajax.reload(); });
        $('#filter-gender').on('change', function() { table.ajax.reload(); });
        $('#filter-blood-group').on('change', function() { table.ajax.reload(); });
        $('#filter-payment-status').on('change', function() { table.ajax.reload(); });

        $('#filter-reset-btn').on('click', function() {
            $('#filter-institution').val('');
            $('#filter-batch').val('');
            $('#filter-gender').val('');
            $('#filter-blood-group').val('');
            $('#filter-payment-status').val('');
            table.ajax.reload();
        });

        var table = $('#members-table').DataTable({
            "processing": true,
            "serverSide": true,
            "autoWidth": false,
            "order": [[ 0, "asc" ]],
            "ajax": {
                "url": ajax_url,
                "type": "POST",
                "data": function(d) {
                    d.action = 'BatchMate_get_members';
                    d._wpnonce = member_nonce;
                    d.filter_institution = $('#filter-institution').val();
                    d.filter_batch = $('#filter-batch').val();
                    d.filter_gender = $('#filter-gender').val();
                    d.filter_blood_group = $('#filter-blood-group').val();
                    d.filter_payment_status = $('#filter-payment-status').val();
                }
            },
            "columns": [
                { "data": "member_number", "width": "120px" }, 
                { 
                    "data": "name",
                    "width": "200px",
                    "render": function(data, type, row) {
                        var member_number_lower = row.member_number.toLowerCase();
                        var profile_url = member_base_url + member_number_lower + '/';
                        return '<a href="' + profile_url + '" target="_blank" >' + data + '</a>';
                    }
                },
                { 
                "data": "photo_url", 
                "width": "50px",
                "orderable": false,
                "render": function(data, type, row) {
                    return data ? `<img src="${data}" class="photo">` : '';
                } 
                },{ "data": "institution" },
                { "data": "cell_number" }, { "data": "email" },
                { 
                    "data": "wp_user_id", 
                    "render": function(data, type, row) { 
                        if (data > 0) {
                            var username = row.wp_username ? row.wp_username : 'Unknown';
                            return `<span title="ID: ${data} | User: ${username}" style="color:green; cursor:help; display:inline-flex; align-items:center;"><span class="dashicons dashicons-yes-alt" style="font-size:22px; width:22px; height:22px;"></span></span>`;
                        } else {
                            return `<span style="color:#d63638; display:inline-flex; align-items:center;"><span class="dashicons dashicons-dismiss" style="font-size:22px; width:22px; height:22px;"></span></span>`;
                        }
                    } 
                },
                { "data": null, "orderable": false, "searchable": false, "render": function(data, type, row) {
                        return `
                        <div class="BatchMate-actions-btn-container">
                            <button class="BatchMate-actions-btn">Actions</button>
                            <div class="BatchMate-actions-dropdown">
                                <button class="button edit-btn" data-id="${row.id}"><span class="dashicons dashicons-edit"></span>Edit</button>
                                <button class="button create-user-btn" data-id="${row.id}"><span class="dashicons dashicons-admin-users"></span>Create User</button>
                                <button class="button reset-credentials-btn" data-id="${row.id}"><span class="dashicons dashicons-email"></span>Send Password Reset Link</button>
                                <button class="button print-btn" data-id="${row.id}"><span class="dashicons dashicons-printer"></span>Print</button>
                            </div>
                        </div>
                        `;
                    }
                }
            ]
        });

        // 2. "Edit" ? ?
        $('#members-table tbody').on('click', '.edit-btn', function() {
            var member_id = $(this).data('id');
            
            Swal.fire({
                title: 'Loading Member Data...',
                showConfirmButton: false,
                allowOutsideClick: false,
                willOpen: () => { Swal.showLoading(); }
            });

            var getMember = $.ajax({
                url: ajax_url, type: 'POST',
                data: { action: 'BatchMate_get_member_details', member_id: member_id, _wpnonce: member_nonce }
            });
            $.when(getMember).done(function(memberRes) {
                if (memberRes.length === 3) memberRes = memberRes[0]; // Just in case
                if (!memberRes.success) {
                    Swal.fire('Error!', memberRes.data, 'error');
                    return;
                }
                var member = memberRes.data; 

                $('#BatchMate-edit-form')[0].reset();
                adminMemberResizedPhoto = null;
                $('#modal_photo').val('');

                // --- ফর্ম ফিল্ড পূরণ ---
                $('#modal_member_id').val(member.id);
                $('#modal_member_number').val(member.member_number);
                $('#modal_name').val(member.name);
                $('#modal_name_bn').val(member.name_bn || '');
                $('#modal_email').val(member.email);
                $('#modal_cell_number').val(member.cell_number || ''); 
                $('#modal_cell_show').prop('checked', member.cell_show == 1);
                $('#modal_land_phone_number').val(member.land_phone_number || '');
                $('#modal_date_of_birth').val(member.date_of_birth || '');
                $('#modal_gender').val(member.gender || ''); // [ADDED] Populate Gender
                $('#modal_religion').val(member.religion || '');
                $('#modal_student_id').val(member.student_id || member.roll_no || '');
                $('#modal_institution').val(member.institution || '');
                $('#modal_board').val(member.board || '').trigger('change');
                $('#modal_edu_group').val(member.edu_group || '').trigger('change');
                $('#modal_employment_position').val(member.employment_position || '');
                $('#modal_employment_address').val(member.employment_address || '');
                $('#modal_organization').val(member.organization || '');
                $('#modal_industry').val(member.industry || '');
                $('#modal_work_experience').val(member.work_experience || '');
                $('#modal_previous_organizations').val(member.previous_organizations || '');
                $('#modal_present_address').val(member.present_address || '');
                $('#modal_present_district').val(member.present_district || '');
                populateModalUpazilas($('#modal_present_district'), $('#modal_present_upazila'), member.present_upazila || '');
                if ($.fn.select2) $('#modal_present_district').trigger('change.select2');
                var pc = member.present_country || 'Bangladesh';
                $('#modal_present_country').val(pc);
                if ($.fn.select2) $('#modal_present_country').trigger('change.select2');
                $('#wrap_present_district, #wrap_present_upazila').toggle(pc === 'Bangladesh');

                $('#modal_permanent_address').val(member.permanent_address || '');
                $('#modal_permanent_district').val(member.permanent_district || '');
                populateModalUpazilas($('#modal_permanent_district'), $('#modal_upazila'), member.upazila || '');
                if ($.fn.select2) $('#modal_permanent_district').trigger('change.select2');
                var pmc = member.permanent_country || 'Bangladesh';
                $('#modal_permanent_country').val(pmc);
                if ($.fn.select2) $('#modal_permanent_country').trigger('change.select2');
                $('#wrap_permanent_district, #wrap_permanent_upazila').toggle(pmc === 'Bangladesh');
                $('#modal_about_me').val(member.about_me || '');
                
                // Skills (JSON array to comma-separated string)
                let skills_str = '';
                if (member.skills_json) {
                    try {
                        let skills_arr = JSON.parse(member.skills_json);
                        if (Array.isArray(skills_arr)) {
                            skills_str = skills_arr.join(', ');
                        }
                    } catch (e) {}
                }
                $('#modal_skills').val(skills_str);

                $('#modal_facebook_url').val(member.facebook_url || '');
                $('#modal_linkedin_url').val(member.linkedin_url || '');
                $('#modal_x_url').val(member.x_url || '');
                $('#modal_website').val(member.website || '');

                // ? ?
                let bloodGroups = ['A+', 'A-', 'B+', 'B-', 'O+', 'O-', 'AB+', 'AB-'];
                let bloodOptions = bloodGroups.map(bg => 
                    `<option value="${bg}" ${bg == member.blood_group ? 'selected' : ''}>${bg}</option>`
                ).join('');
                $('#modal_blood_group').html('<option value="">Select...</option>' + bloodOptions);

                

                // ??? ???
                if (member.photo_url) {
                    $('#modal_photo_preview').attr('src', member.photo_url).show();
                } else {
                    $('#modal_photo_preview').hide();
                }
                
                Swal.close(); 
                $('#BatchMate-edit-modal-backdrop, #BatchMate-edit-modal-wrapper').show(); 
                $('body').addClass('modal-open');

                // Initialize Select2 inside modal
                $('#BatchMate-edit-modal-wrapper .select2-field').each(function() {
                    if (!$(this).hasClass('select2-hidden-accessible')) {
                        $(this).select2({ dropdownParent: $('#BatchMate-edit-modal-wrapper'), width: '100%' });
                    }
                });
                // Re-set values after select2 init
                $('#modal_blood_group').val(member.blood_group || '').trigger('change');
                $('#modal_gender').val(member.gender || '').trigger('change');
                $('#modal_religion').val(member.religion || '').trigger('change');
                $('#modal_dept_id').val(member.dept_id || '').trigger('change');
                
                $('#modal_present_district').val(member.present_district || '').trigger('change');
                var pc2 = member.present_country || 'Bangladesh';
                $('#modal_present_country').val(pc2).trigger('change');
                $('#wrap_present_district').toggle(pc2 === 'Bangladesh');
                $('#modal_permanent_district').val(member.permanent_district || '').trigger('change');
                var pmc2 = member.permanent_country || 'Bangladesh';
                $('#modal_permanent_country').val(pmc2).trigger('change');
                $('#wrap_permanent_district').toggle(pmc2 === 'Bangladesh');
            }).fail(function() {
                Swal.fire('Error', 'Could not load member data.', 'error');
            });
          });

          // Country change ? show/hide district
        $(document).on('change', '#modal_present_country', function() {
            $('#wrap_present_district').toggle($(this).val() === 'Bangladesh');
            if ($(this).val() !== 'Bangladesh') $('#modal_present_district').val('').trigger('change');
        });
        $(document).on('change', '#modal_permanent_country', function() {
            $('#wrap_permanent_district').toggle($(this).val() === 'Bangladesh');
            if ($(this).val() !== 'Bangladesh') $('#modal_permanent_district').val('').trigger('change');
        });

        // ??? ???
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

        var adminMemberResizedPhoto = null;
        var isResizingAdminPhoto = false;
        $('#modal_photo').on('change', function() {
            if (isResizingAdminPhoto) return;
            const input = this;
            const file = input.files && input.files[0];
            if (!file) {
                adminMemberResizedPhoto = null;
                $('#modal_photo_preview').attr('src', '').hide();
                return;
            }

            isResizingAdminPhoto = true;
            $('#BatchMate-modal-save').prop('disabled', true);

            batchmateResizeTo400(file, function(resizedFile, previewUrl) {
                adminMemberResizedPhoto = resizedFile;
                if (previewUrl) {
                    $('#modal_photo_preview').attr('src', previewUrl).show();
                }
                if (resizedFile && window.DataTransfer) {
                    const dt = new DataTransfer();
                    dt.items.add(resizedFile);
                    input.files = dt.files;
                }
                isResizingAdminPhoto = false;
                $('#BatchMate-modal-save').prop('disabled', false);
            });
        });

        //  ???  ?
        $('#BatchMate-modal-save').on('click', function() {
            
            // ??
            const requiredFields = [
                { id: '#modal_member_number', name: 'Member ID' },
                { id: '#modal_name', name: 'Name' },
                // Email is optional (auto-generated if blank)
                { id: '#modal_cell_number', name: 'Cell Number' },
                { id: '#modal_institution', name: 'Institute' },
                { id: '#modal_gender', name: 'Gender' } // [ADDED] Gender Required
            ];

            for (const field of requiredFields) {
                const value = $(field.id).val();
                if (!value) {
                    Swal.fire('Validation Error', `${field.name} is required.`, 'error');
                    return; 
                }
            }
            
            Swal.fire({
                title: 'Saving Changes...',
                text: 'Please wait...',
                icon: 'info',
                allowOutsideClick: false,
                allowEscapeKey: false,
                showConfirmButton: false,
                willOpen: () => {
                    Swal.showLoading();
                }
            });

            // FormData   ??  ??
            var formData = new FormData();
            formData.append('action', 'BatchMate_save_member_details');
            formData.append('_wpnonce', member_nonce);
            formData.append('member_id', $('#modal_member_id').val());
            formData.append('member_number', $('#modal_member_number').val());
            
            // ফিল্ড এর মান যোগ করুন
            formData.append('name', $('#modal_name').val());
            formData.append('name_bn', $('#modal_name_bn').val() || '');
            formData.append('email', $('#modal_email').val());
            formData.append('cell_number', $('#modal_cell_number').val());
            formData.append('cell_show', $('#modal_cell_show').is(':checked') ? 1 : 0);
            formData.append('land_phone_number', $('#modal_land_phone_number').val());
            formData.append('date_of_birth', $('#modal_date_of_birth').val());
            formData.append('blood_group', $('#modal_blood_group').val());
            formData.append('gender', $('#modal_gender').val()); // [ADDED] Gender Field
            formData.append('religion', $('#modal_religion').val());
            formData.append('student_id', $('#modal_student_id').val());
            formData.append('institution', $('#modal_institution').val());
            formData.append('board', $('#modal_board').val());
            formData.append('edu_group', $('#modal_edu_group').val());
            formData.append('employment_position', $('#modal_employment_position').val());
            formData.append('employment_address', $('#modal_employment_address').val());
            formData.append('organization', $('#modal_organization').val());
            formData.append('industry', $('#modal_industry').val());
            formData.append('work_experience', $('#modal_work_experience').val());
            formData.append('previous_organizations', $('#modal_previous_organizations').val());
            formData.append('present_address', $('#modal_present_address').val());
            formData.append('present_district', $('#modal_present_district').val() || '');
            formData.append('present_upazila', $('#modal_present_upazila').val() || '');
            formData.append('present_country', $('#modal_present_country').val() || 'Bangladesh');
            formData.append('permanent_address', $('#modal_permanent_address').val());
            formData.append('permanent_district', $('#modal_permanent_district').val() || '');
            formData.append('upazila', $('#modal_upazila').val() || '');
            formData.append('permanent_country', $('#modal_permanent_country').val() || 'Bangladesh');
            formData.append('about_me', $('#modal_about_me').val());
            formData.append('skills', $('#modal_skills').val());
            formData.append('facebook_url', $('#modal_facebook_url').val());
            formData.append('linkedin_url', $('#modal_linkedin_url').val());
            formData.append('x_url', $('#modal_x_url').val());
            formData.append('website', $('#modal_website').val());

            var modalPhoto = adminMemberResizedPhoto || ($('#modal_photo')[0].files.length > 0 ? $('#modal_photo')[0].files[0] : null);
            if (modalPhoto) { formData.append('swal_photo', modalPhoto); }

            $.ajax({
                url: ajax_url,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(result) {
                    if (result.success) {
                        Swal.fire({ title: 'Updated!', text: result.data, icon: 'success', timer: 1500, showConfirmButton: false });
                        table.ajax.reload(null, false);
                        adminMemberResizedPhoto = null;
                        $('#modal_photo').val('');
                        $('#BatchMate-edit-modal-backdrop, #BatchMate-edit-modal-wrapper').hide(); 
                        $('body').removeClass('modal-open');
                    } else {
                        Swal.fire('Error!', result.data, 'error');
                    }
                },
                error: function() {
                    Swal.fire('Error!', 'Request failed. Please try again.', 'error');
                }
            });
        });

        // Modal Close
        $('.BatchMate-modal-close, #BatchMate-edit-modal-backdrop').on('click', function() {
            adminMemberResizedPhoto = null;
            $('#modal_photo').val('');
            $('#BatchMate-edit-modal-backdrop, #BatchMate-edit-modal-wrapper').hide();
            $('body').removeClass('modal-open');
        });


        // 3. "Print" ? ?
        $('#members-table tbody').on('click', '.print-btn', function() {
            var member_id = $(this).data('id');
            var print_url = `<?php echo home_url('/print-member/'); ?>?member_id=${member_id}`;
            window.open(print_url, '_blank');
        });

        // 4. "Create User" ? ?
        $('#members-table tbody').on('click', '.create-user-btn', function() {
            var member_id = $(this).data('id');
            
            Swal.fire({
                title: 'Processing...', text: 'Checking member status...', icon: 'info',
                showConfirmButton: false, allowOutsideClick: false,
                willOpen: () => { Swal.showLoading(); }
            });

            $.ajax({
                url: ajax_url,
                type: 'POST',
                data: {
                    action: 'BatchMate_check_member_user',
                    member_id: member_id,
                    _wpnonce: member_nonce
                }
            }).done(function(response) {
                if (!response.success) {
                    Swal.fire('Error!', response.data, 'error');
                    return;
                }

                if (response.data.status === 'user_exists') {
                    Swal.fire({
                        title: 'User Already Exists',
                        html: `This member already has an active user account (Email: <b>${response.data.email}</b>).<br/><br/>Do you want to reset their credentials and send a temporary password with login details to their email?`,
                        icon: 'info',
                        showCancelButton: true,
                        confirmButtonText: 'Yes, Reset Credentials',
                        cancelButtonText: 'No, Cancel',
                        showLoaderOnConfirm: true,
                        preConfirm: () => {
                            return $.ajax({
                                url: ajax_url,
                                type: 'POST',
                                data: {
                                    action: 'BatchMate_reset_member_credentials',
                                    member_id: member_id,
                                    _wpnonce: member_nonce
                                }
                            }).fail(function(xhr) {
                                var err = 'Request failed.';
                                if (xhr.responseJSON && xhr.responseJSON.data) {
                                    err = xhr.responseJSON.data;
                                }
                                Swal.showValidationMessage(err);
                            });
                        }
                    }).then((result) => {
                        if (result.isConfirmed && result.value && result.value.success) {
                            Swal.fire('Credentials Reset!', result.value.data, 'success');
                            table.ajax.reload(null, false);
                        } else if (result.value && !result.value.success) {
                            Swal.fire('Error!', result.value.data, 'error');
                        }
                    });

                } else if (response.data.status === 'email_conflict') {
                    Swal.fire({
                        title: 'Email Conflict',
                        html: `Cannot create user. This member's email (<b>${response.data.email}</b>) is already registered to another WordPress user (ID: ${response.data.existing_user_id}).<br/><br/>Please edit the member's email address first.`,
                        icon: 'error',
                        confirmButtonText: 'OK'
                    });

                } else if (response.data.status === 'no_user') {
                    Swal.fire({
                        title: 'Create New User?',
                        text: `Do you want to create a new WordPress user for ${response.data.name} (Email: ${response.data.email})?`,
                        icon: 'info',
                        showCancelButton: true,
                        confirmButtonText: 'Yes, Create User',
                        cancelButtonText: 'No, Cancel',
                        showLoaderOnConfirm: true,
                        preConfirm: () => {
                            return $.ajax({
                                url: ajax_url,
                                type: 'POST',
                                data: {
                                    action: 'BatchMate_create_member_wp_user',
                                    member_id: member_id,
                                    _wpnonce: member_nonce
                                }
                            }).fail(function(xhr) { 
                                var errMsg = xhr.responseText ? 'Request failed: ' + xhr.responseText : 'Request failed.';
                                Swal.showValidationMessage(errMsg); 
                            });
                        }
                    }).then((result) => {
                        if (result.isConfirmed && result.value.success) {
                            Swal.fire({
                                title: 'User Created!',
                                text: result.value.data,
                                icon: 'success',
                                timer: 3000,
                                showConfirmButton: false
                            }).then(() => {
                                table.ajax.reload(null, false);
                            });
                        } else if (result.value) {
                            Swal.fire('Error!', result.value.data, 'error');
                        }
                    });
                }
            }).fail(function() {
                Swal.fire('Error', 'Could not check member status.', 'error');
            });
        });

        // 5. "Reset User Credentials" ? ?
        $('#members-table tbody').on('click', '.reset-credentials-btn', function() {
            var member_id = $(this).data('id');
            
            Swal.fire({
                title: 'Checking Status...',
                text: 'Checking member user account...',
                icon: 'info',
                showConfirmButton: false,
                allowOutsideClick: false,
                willOpen: () => { Swal.showLoading(); }
            });

            $.ajax({
                url: ajax_url,
                type: 'POST',
                data: {
                    action: 'BatchMate_check_member_user',
                    member_id: member_id,
                    _wpnonce: member_nonce
                }
            }).done(function(response) {
                if (!response.success) {
                    Swal.fire('Error!', response.data, 'error');
                    return;
                }

                if (response.data.status === 'no_user') {
                    Swal.fire({
                        title: 'No User Account',
                        html: `This member (<b>${response.data.name || ''}</b>) does not have a WordPress user account yet.<br/><br/>Please use <b>"Create User"</b> first to create their account.`,
                        icon: 'warning',
                        confirmButtonText: 'OK'
                    });
                } else if (response.data.status === 'email_conflict') {
                    Swal.fire({
                        title: 'Email Conflict',
                        html: `Cannot reset credentials. This member's email (<b>${response.data.email}</b>) is registered to another WordPress user (ID: ${response.data.existing_user_id}), but not linked to this member.<br/><br/>Please update the member's email address first.`,
                        icon: 'error',
                        confirmButtonText: 'OK'
                    });
                } else if (response.data.status === 'user_exists') {
                    Swal.fire({
                        title: 'Send Password Reset Link?',
                        html: `Are you sure you want to send a password reset link to <b>${response.data.name || 'this member'}</b>?<br/><br/>An email with the reset link will be sent to <b>${response.data.email}</b>.`,
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: 'Yes, Send Link',
                        cancelButtonText: 'Cancel',
                        showLoaderOnConfirm: true,
                        preConfirm: () => {
                            return $.ajax({
                                url: ajax_url,
                                type: 'POST',
                                data: {
                                    action: 'BatchMate_reset_member_credentials',
                                    member_id: member_id,
                                    _wpnonce: member_nonce
                                }
                            }).fail(function(xhr) {
                                var err = xhr.responseText ? 'Request failed: ' + xhr.responseText : 'Request failed.';
                                if (xhr.responseJSON && xhr.responseJSON.data) {
                                    err = xhr.responseJSON.data;
                                }
                                Swal.showValidationMessage(err);
                            });
                        }
                    }).then((result) => {
                        if (result.isConfirmed && result.value && result.value.success) {
                            Swal.fire({
                                title: 'Credentials Reset!',
                                text: result.value.data,
                                icon: 'success'
                            }).then(() => {
                                table.ajax.reload(null, false);
                            });
                        } else if (result.value && !result.value.success) {
                            Swal.fire('Error!', result.value.data, 'error');
                        }
                    });
                }
            }).fail(function() {
                Swal.fire('Error', 'Could not check member status.', 'error');
            });
        });

    });


</script>












