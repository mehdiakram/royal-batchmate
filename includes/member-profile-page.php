<?php
/**
 * My BatchMate Profile — Member self-edit page (wp-admin)
 */
if ( ! defined( 'ABSPATH' ) ) exit;

global $wpdb;
$current_user_id = get_current_user_id();

$member = $wpdb->get_row( $wpdb->prepare(
    "SELECT m.*, d.dept_name, p.program_name
     FROM {$wpdb->prefix}batchmate_member m
     LEFT JOIN {$wpdb->prefix}batchmate_program p ON m.program_id = p.id
     LEFT JOIN {$wpdb->prefix}batchmate_department d ON p.dept_id = d.id
     WHERE m.wp_user_id = %d",
    $current_user_id
) );

if ( ! $member ) {
    echo '<div class="wrap"><div class="notice notice-error"><p><strong>কোনো BatchMate member account আপনার profile এর সাথে লিংক করা নেই।</strong></p></div></div>';
    return;
}

$no_photo   = ROYAL_BATCHMATE_PLUGIN_URL . '/assets/images/noprofile.png';
$photo_url  = ! empty( $member->photo_url ) ? $member->photo_url : $no_photo;
$ajax_url   = admin_url( 'admin-ajax.php' );
$nonce      = wp_create_nonce( 'batchmate_member_self_nonce' );
$pub_url    = home_url( '/member/' . strtolower( $member->member_number ) . '/' );

$blood_groups = ['A+','A-','B+','B-','O+','O-','AB+','AB-'];
?>
<div class="wrap BatchMate-mp-wrap">
    <h1 class="wp-heading-inline">My <?php echo esc_html( batchmate_org_label( 'short' ) ); ?> Profile</h1>
    <a href="<?php echo esc_url( $pub_url ); ?>" target="_blank" class="page-title-action">
        &#128065; View Public Profile
    </a>
    <hr class="wp-header-end">

    <div class="BatchMate-mp-container">

        <!-- ══ Header Card ══ -->
        <div class="BatchMate-mp-header-card">
            <div class="BatchMate-mp-photo-col">
                <img id="BatchMate-mp-photo-preview" src="<?php echo esc_url( $photo_url ); ?>"
                     alt="Profile Photo" class="BatchMate-mp-photo-img">
                <label for="BatchMate-mp-photo-input" class="BatchMate-mp-change-photo-btn">
                    <span class="dashicons dashicons-camera"></span> Change Photo
                </label>
                <input type="file" id="BatchMate-mp-photo-input" accept="image/jpeg,image/png,image/webp" style="display:none;">
                <p class="BatchMate-mp-photo-hint">Will be cropped to 400×400 px</p>
            </div>
            <div class="BatchMate-mp-header-info">
                <h2 id="BatchMate-mp-display-name"><?php echo esc_html( $member->name ); ?></h2>
                <div class="BatchMate-mp-badges">
                    <span class="BatchMate-mp-badge BatchMate-mp-badge-id"><?php echo esc_html( $member->member_number ); ?></span>
                    <span class="BatchMate-mp-badge"><?php echo esc_html( ucfirst($member->board) ); ?> Board</span>
                    <span class="BatchMate-mp-badge"><?php echo esc_html( ucfirst($member->edu_group) ); ?></span>
                    
                    <span class="BatchMate-mp-badge">Roll: <?php echo esc_html( $member->roll_no ); ?></span>
                </div>
                <p class="BatchMate-mp-locked-note">
                    <span class="dashicons dashicons-lock"></span>
                    Board, Roll No, Registration No, Group, and Institution cannot be changed.
                </p>
            </div>
        </div><!-- /.BatchMate-mp-header-card -->

        <form id="BatchMate-mp-form">

            <!-- ══ About Me ══ -->
            <div class="BatchMate-mp-card">
                <div class="BatchMate-mp-card-head">
                    <span class="dashicons dashicons-format-quote"></span>
                    <h3>About Me</h3>
                </div>
                <div class="BatchMate-mp-card-body">
                    <div class="BatchMate-mp-row">
                        <div class="BatchMate-mp-group full">
                            <label>Short Bio / About Me</label>
                            <textarea id="mp_about_me" rows="4" placeholder="Write a short introduction about yourself..."><?php echo esc_textarea( $member->about_me ?? '' ); ?></textarea>
                            <small class="BatchMate-mp-hint">This will appear in the "About Me" section of your public profile. Leave blank to hide.</small>
                        </div>
                    </div>
                </div>
            </div><!-- /.BatchMate-mp-card -->

            <!-- ══ Personal Information ══ -->

            <div class="BatchMate-mp-card">
                <div class="BatchMate-mp-card-head">
                    <span class="dashicons dashicons-admin-users"></span>
                    <h3>Personal Information</h3>
                </div>
                <div class="BatchMate-mp-card-body">
                    <div class="BatchMate-mp-row">
                        <div class="BatchMate-mp-group">
                            <label>Full Name <span class="req">*</span></label>
                            <input type="text" id="mp_name" value="<?php echo esc_attr( $member->name ); ?>" required>
                        </div>
                        <div class="BatchMate-mp-group">
                            <label>Name (Bangla) <span class="req">*</span></label>
                            <input type="text" id="mp_name_bn" value="<?php echo esc_attr( $member->name_bn ?? '' ); ?>" required>
                        </div>
                    </div>
                    <div class="BatchMate-mp-row">
                        <div class="BatchMate-mp-group">
                            <label>Email Address (Optional)</label>
                            <input type="email" id="mp_email" value="<?php echo esc_attr( $member->email ); ?>" placeholder="Auto-generated if left blank">
                            <small class="BatchMate-mp-hint">Changing email also updates your WordPress login email.</small>
                        </div>
                    </div>
                    <div class="BatchMate-mp-row">
                        <div class="BatchMate-mp-group">
                            <label>Cell Number</label>
                            <input type="text" id="mp_cell_number" value="<?php echo esc_attr( $member->cell_number ?? '' ); ?>">
                        </div>
                        <div class="BatchMate-mp-group">
                            <label>Alternative Number (Optional)</label>
                            <input type="text" id="mp_land_phone_number" value="<?php echo esc_attr( $member->land_phone_number ?? '' ); ?>">
                        </div>
                    </div>
                    <div class="BatchMate-mp-row">
                        <div class="BatchMate-mp-group">
                            <label>Date of Birth</label>
                            <input type="text" id="mp_date_of_birth" value="<?php echo esc_attr( $member->date_of_birth ?? '' ); ?>" placeholder="YYYY-MM-DD">
                        </div>
                        <div class="BatchMate-mp-group">
                            <label>Blood Group</label>
                            <select id="mp_blood_group">
                                <option value="">Select...</option>
                                <?php foreach ( $blood_groups as $bg ) : ?>
                                <option value="<?php echo esc_attr( $bg ); ?>" <?php selected( $member->blood_group, $bg ); ?>><?php echo esc_html( $bg ); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="BatchMate-mp-row">
                        <div class="BatchMate-mp-group">
                            <label>Gender</label>
                            <select id="mp_gender">
                                <option value="">Select Gender...</option>
                                <option value="Male"   <?php selected( $member->gender, 'Male' );   ?>>Male</option>
                                <option value="Female" <?php selected( $member->gender, 'Female' ); ?>>Female</option>
                            </select>
                        </div>
                        <div class="BatchMate-mp-group">
                            <label>Religion</label>
                            <select id="mp_religion">
                                <option value="">Select Religion...</option>
                                <option value="Islam" <?php selected( $member->religion, 'Islam' ); ?>>Islam</option>
                                <option value="Hinduism" <?php selected( $member->religion, 'Hinduism' ); ?>>Hinduism</option>
                                <option value="Buddhism" <?php selected( $member->religion, 'Buddhism' ); ?>>Buddhism</option>
                                <option value="Christianity" <?php selected( $member->religion, 'Christianity' ); ?>>Christianity</option>
                                <option value="Others" <?php selected( $member->religion, 'Others' ); ?>>Others</option>
                            </select>
                        </div>
                        <div class="BatchMate-mp-group" style="padding-top:30px;">
                            <label class="BatchMate-mp-check-label">
                                <input type="checkbox" id="mp_cell_show" value="1" <?php checked( $member->cell_show, 1 ); ?>>
                                Show cell number publicly on profile
                            </label>
                        </div>
                    </div>
                </div>
            </div><!-- /.BatchMate-mp-card -->

            <!-- ══ Academic ══ -->
            <div class="BatchMate-mp-card">
                <div class="BatchMate-mp-card-head">
                    <span class="dashicons dashicons-welcome-learn-more"></span>
                    <h3>Academic Information</h3>
                </div>
                <div class="BatchMate-mp-card-body">
                    <div class="BatchMate-mp-row">
                        <div class="BatchMate-mp-group">
                            <label>Board</label>
                            <input type="text" value="<?php echo esc_attr( ucfirst($member->board) ); ?>" readonly style="background:#f1f5f9; cursor:not-allowed;">
                        </div>
                        <div class="BatchMate-mp-group">
                            <label>Roll No</label>
                            <input type="text" value="<?php echo esc_attr( $member->roll_no ); ?>" readonly style="background:#f1f5f9; cursor:not-allowed;">
                        </div>
                    </div>
                    <div class="BatchMate-mp-row">
                        <div class="BatchMate-mp-group">
                            <label>Registration No</label>
                            <input type="text" value="<?php echo esc_attr( $member->registration_no ); ?>" readonly style="background:#f1f5f9; cursor:not-allowed;">
                        </div>
                        <div class="BatchMate-mp-group">
                            <label>Group</label>
                            <input type="text" value="<?php echo esc_attr( ucfirst($member->edu_group) ); ?>" readonly style="background:#f1f5f9; cursor:not-allowed;">
                        </div>
                    </div>
                    <div class="BatchMate-mp-row">
                        <div class="BatchMate-mp-group full">
                            <label><?php echo esc_html( batchmate_get_field_label('reg_institution', 'Institution') ); ?></label>
                            <input type="text" value="<?php echo esc_attr( $member->institution ); ?>" readonly style="background:#f1f5f9; cursor:not-allowed;">
                        </div>
                    </div>
                </div>
            </div>

            <!-- ══ Professional ══ -->
            <div class="BatchMate-mp-card">
                <div class="BatchMate-mp-card-head">
                    <span class="dashicons dashicons-businessman"></span>
                    <h3>Professional Information</h3>
                </div>
                <div class="BatchMate-mp-card-body">
                    <div class="BatchMate-mp-row">
                        <div class="BatchMate-mp-group">
                            <label>Position / Designation</label>
                            <input type="text" id="mp_employment_position" value="<?php echo esc_attr( $member->employment_position ?? '' ); ?>" placeholder="e.g. Managing Director">
                        </div>
                        <div class="BatchMate-mp-group">
                            <label>Organization Name</label>
                            <input type="text" id="mp_organization" value="<?php echo esc_attr( $member->organization ?? '' ); ?>" placeholder="e.g. Rafin Tours (Haj Agency)">
                        </div>
                    </div>
                    <div class="BatchMate-mp-row">
                        <div class="BatchMate-mp-group">
                            <label>Industry</label>
                            <input type="text" id="mp_industry" value="<?php echo esc_attr( $member->industry ?? '' ); ?>" placeholder="e.g. Travel & Tourism">
                        </div>
                        <div class="BatchMate-mp-group">
                            <label>Work Experience</label>
                            <input type="text" id="mp_work_experience" value="<?php echo esc_attr( $member->work_experience ?? '' ); ?>" placeholder="e.g. 10 years">
                        </div>
                    </div>
                    <div class="BatchMate-mp-row">
                        <div class="BatchMate-mp-group full">
                            <label>Previous Organizations</label>
                            <textarea id="mp_previous_organizations" rows="3" placeholder="List previous employers, one per line..."><?php echo esc_textarea( $member->previous_organizations ?? '' ); ?></textarea>
                        </div>
                    </div>
                    <div class="BatchMate-mp-row">
                        <div class="BatchMate-mp-group full">
                            <label>Organization Address</label>
                            <textarea id="mp_employment_address" rows="3"><?php echo esc_textarea( $member->employment_address ?? '' ); ?></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ══ Skills & Expertise ══ -->
            <div class="BatchMate-mp-card">
                <div class="BatchMate-mp-card-head">
                    <span class="dashicons dashicons-admin-tools"></span>
                    <h3>Skills &amp; Expertise</h3>
                </div>
                <div class="BatchMate-mp-card-body">
                    <div class="BatchMate-mp-row">
                        <div class="BatchMate-mp-group full">
                            <label>Skills (comma-separated)</label>
                            <?php
                            $skills_val = '';
                            if ( ! empty( $member->skills_json ) ) {
                                $dec = json_decode( $member->skills_json, true );
                                if ( is_array( $dec ) ) $skills_val = implode( ', ', $dec );
                            }
                            ?>
                            <input type="text" id="mp_skills" value="<?php echo esc_attr( $skills_val ); ?>" placeholder="e.g. Leadership, Business Management, IT">
                            <small class="BatchMate-mp-hint">Enter skills separated by commas. Leave blank to hide the Skills section on your profile.</small>
                            <div id="mp_skills_preview" class="mp-skills-preview-wrap"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ══ Address ══ -->
            <div class="BatchMate-mp-card">
                <div class="BatchMate-mp-card-head">
                    <span class="dashicons dashicons-location"></span>
                    <h3>Address</h3>
                </div>
                <div class="BatchMate-mp-card-body">
                    <div class="BatchMate-mp-row">
                        <div class="BatchMate-mp-group">
                            <label>Present Address</label>
                            <textarea id="mp_present_address" rows="3"><?php echo esc_textarea( $member->present_address ?? '' ); ?></textarea>
                        </div>
                        <div class="BatchMate-mp-group">
                            <label>Permanent Address</label>
                            <textarea id="mp_permanent_address" rows="3"><?php echo esc_textarea( $member->permanent_address ?? '' ); ?></textarea>
                        </div>
                    </div>
                    <?php
                    require_once ROYAL_BATCHMATE_PLUGIN_DIR . '/includes/countries.php';
                    $all_countries = BatchMate_get_countries();
                    $bd_districts = ['Bagerhat','Bandarban','Barguna','Barishal','Bhola','Bogura','Brahmanbaria','Chandpur', 'Chapai Nawabganj', 'Chattogram','Chuadanga',"Cox's Bazar",'Cumilla','Dhaka','Dinajpur','Faridpur','Feni','Gaibandha','Gazipur','Gopalganj','Habiganj','Jamalpur','Jashore','Jhalokati','Jhenaidah','Joypurhat','Khagrachari','Khulna','Kishoreganj','Kurigram','Kushtia','Lakshmipur','Lalmonirhat','Madaripur','Magura','Manikganj','Meherpur','Moulvibazar','Munshiganj','Mymensingh','Naogaon','Narail','Narayanganj','Narsingdi','Natore','Netrokona','Nilphamari','Noakhali','Pabna','Panchagarh','Patuakhali','Pirojpur','Rajbari','Rajshahi','Rangamati','Rangpur','Satkhira','Shariatpur','Sherpur','Sirajganj','Sunamganj','Sylhet','Tangail','Thakurgaon'];
                    $cur_present_country  = $member->present_country  ?? 'Bangladesh';
                    $cur_perm_country     = $member->permanent_country ?? 'Bangladesh';
                    ?>
                    <!-- Present Country + District + Upazila -->
                    <div class="BatchMate-mp-row">
                        <div class="BatchMate-mp-group">
                            <label>Present Country</label>
                            <select id="mp_present_country" class="BatchMate-select2">
                                <option value="">Select Country...</option>
                                <?php foreach ($all_countries as $c) : ?>
                                <option value="<?php echo esc_attr($c); ?>" <?php selected($cur_present_country, $c); ?>><?php echo esc_html($c); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="BatchMate-mp-group" id="wrap_mp_present_district" <?php echo $cur_present_country !== 'Bangladesh' ? 'style="display:none"' : ''; ?>>
                            <label><?php echo esc_html( batchmate_get_field_label('reg_present_district', 'Present District') ); ?></label>
                            <select id="mp_present_district" class="BatchMate-select2">
                                <option value="">Select District...</option>
                                <?php foreach ($bd_districts as $dist) : ?>
                                <option value="<?php echo esc_attr($dist); ?>" <?php selected($member->present_district ?? '', $dist); ?>><?php echo esc_html($dist); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <small class="BatchMate-mp-hint">বাংলাদেশে থাকলে জেলা দিন।</small>
                        </div>
                        <div class="BatchMate-mp-group" id="wrap_mp_present_upazila" <?php echo $cur_present_country !== 'Bangladesh' ? 'style="display:none"' : ''; ?>>
                            <label><?php echo esc_html( batchmate_get_field_label('reg_present_upazila', 'Present Thana / Upazila') ); ?></label>
                            <select id="mp_present_upazila" class="BatchMate-select2">
                                <option value="">Select Thana / Upazila...</option>
                                <?php 
                                $cur_pres_dist = $member->present_district ?? '';
                                $pres_upazilas = function_exists('BatchMate_get_upazilas_by_district') ? BatchMate_get_upazilas_by_district($cur_pres_dist) : [];
                                foreach ($pres_upazilas as $upz) : ?>
                                <option value="<?php echo esc_attr($upz); ?>" <?php selected($member->present_upazila ?? '', $upz); ?>><?php echo esc_html($upz); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <!-- Permanent Country + District + Upazila -->
                    <div class="BatchMate-mp-row">
                        <div class="BatchMate-mp-group">
                            <label>Permanent Country</label>
                            <select id="mp_permanent_country" class="BatchMate-select2">
                                <option value="">Select Country...</option>
                                <?php foreach ($all_countries as $c) : ?>
                                <option value="<?php echo esc_attr($c); ?>" <?php selected($cur_perm_country, $c); ?>><?php echo esc_html($c); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="BatchMate-mp-group" id="wrap_mp_perm_district" <?php echo $cur_perm_country !== 'Bangladesh' ? 'style="display:none"' : ''; ?>>
                            <label><?php echo esc_html( batchmate_get_field_label('reg_permanent_district', 'Permanent District') ); ?></label>
                            <select id="mp_permanent_district" class="BatchMate-select2">
                                <option value="">Select District...</option>
                                <?php foreach ($bd_districts as $dist) : ?>
                                <option value="<?php echo esc_attr($dist); ?>" <?php selected($member->permanent_district ?? '', $dist); ?>><?php echo esc_html($dist); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="BatchMate-mp-group" id="wrap_mp_perm_upazila" <?php echo $cur_perm_country !== 'Bangladesh' ? 'style="display:none"' : ''; ?>>
                            <label><?php echo esc_html( batchmate_get_field_label('reg_permanent_upazila', 'Permanent Thana / Upazila') ); ?></label>
                            <select id="mp_upazila" class="BatchMate-select2">
                                <option value="">Select Thana / Upazila...</option>
                                <?php 
                                $cur_perm_dist = $member->permanent_district ?? '';
                                $perm_upazilas = function_exists('BatchMate_get_upazilas_by_district') ? BatchMate_get_upazilas_by_district($cur_perm_dist) : [];
                                foreach ($perm_upazilas as $upz) : ?>
                                <option value="<?php echo esc_attr($upz); ?>" <?php selected($member->upazila ?? '', $upz); ?>><?php echo esc_html($upz); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ══ Social ══ -->
            <div class="BatchMate-mp-card">
                <div class="BatchMate-mp-card-head">
                    <span class="dashicons dashicons-share"></span>
                    <h3>Social Links</h3>
                </div>
                <div class="BatchMate-mp-card-body">
                    <div class="BatchMate-mp-row">
                        <div class="BatchMate-mp-group">
                            <label>Facebook URL</label>
                            <input type="url" id="mp_facebook_url" value="<?php echo esc_attr( $member->facebook_url ?? '' ); ?>" placeholder="https://facebook.com/...">
                        </div>
                        <div class="BatchMate-mp-group">
                            <label>LinkedIn URL</label>
                            <input type="url" id="mp_linkedin_url" value="<?php echo esc_attr( $member->linkedin_url ?? '' ); ?>" placeholder="https://linkedin.com/in/...">
                        </div>
                    </div>
                    <div class="BatchMate-mp-row">
                        <div class="BatchMate-mp-group">
                            <label>X (Twitter) URL</label>
                            <input type="url" id="mp_x_url" value="<?php echo esc_attr( $member->x_url ?? '' ); ?>" placeholder="https://x.com/...">
                        </div>
                        <div class="BatchMate-mp-group">
                            <label>Website</label>
                            <input type="url" id="mp_website" value="<?php echo esc_attr( $member->website ?? '' ); ?>" placeholder="https://yourwebsite.com">
                        </div>
                    </div>
                </div>
            </div>

            <!-- ══ Password Change ══ -->
            <div class="BatchMate-mp-card">
                <div class="BatchMate-mp-card-head">
                    <span class="dashicons dashicons-lock"></span>
                    <h3>Change Password</h3>
                </div>
                <div class="BatchMate-mp-card-body">
                    <p class="BatchMate-mp-pass-note">Leave blank if you do not want to change your password.</p>
                    <div class="BatchMate-mp-row">
                        <div class="BatchMate-mp-group">
                            <label>New Password</label>
                            <input type="password" id="mp_new_password" placeholder="Enter new password" autocomplete="new-password">
                        </div>
                        <div class="BatchMate-mp-group">
                            <label>Confirm New Password</label>
                            <input type="password" id="mp_confirm_password" placeholder="Confirm new password" autocomplete="new-password">
                        </div>
                    </div>
                    <div id="BatchMate-mp-pass-strength" class="BatchMate-mp-pass-strength" style="display:none;"></div>
                </div>
            </div>

            <!-- ══ Actions ══ -->
            <div class="BatchMate-mp-actions">
                <a href="<?php echo esc_url( $pub_url ); ?>" target="_blank" class="button button-large">
                    <span class="dashicons dashicons-visibility" style="margin-top:3px;margin-right:4px;"></span>
                    View Public Profile
                </a>
                <button type="button" id="BatchMate-mp-save-btn" class="button button-primary button-large">
                    <span class="dashicons dashicons-yes-alt" style="margin-top:3px;margin-right:4px;"></span>
                    Save Changes
                </button>
            </div>

        </form><!-- /#BatchMate-mp-form -->
    </div><!-- /.BatchMate-mp-container -->
</div><!-- /.wrap -->

<style>
/* ── Layout ── */
.BatchMate-mp-wrap { max-width: 960px; }
.BatchMate-mp-container { margin-top: 18px; }

/* ── Header Card ── */
.BatchMate-mp-header-card {
    display: flex; gap: 28px; align-items: center;
    background: linear-gradient(135deg, #1a3a6b 0%, #2561a8 100%);
    border-radius: 14px; padding: 28px 32px;
    margin-bottom: 22px;
    box-shadow: 0 6px 28px rgba(26,58,107,.22);
}
.BatchMate-mp-photo-col { flex-shrink: 0; text-align: center; }
.BatchMate-mp-photo-img {
    width: 110px; height: 110px; border-radius: 50%;
    object-fit: cover;
    border: 4px solid rgba(255,255,255,.85);
    display: block; margin: 0 auto 10px;
}
.BatchMate-mp-change-photo-btn {
    display: inline-flex; align-items: center; gap: 5px;
    background: rgba(255,255,255,.2); border: 1px solid rgba(255,255,255,.45);
    color: #fff; padding: 5px 14px; border-radius: 20px;
    font-size: 12px; cursor: pointer; transition: background .2s;
}
.BatchMate-mp-change-photo-btn:hover { background: rgba(255,255,255,.32); }
.BatchMate-mp-photo-hint { font-size: 11px; color: rgba(255,255,255,.65); margin-top: 6px; }
.BatchMate-mp-header-info h2 { margin: 0 0 12px; font-size: 24px; font-weight: 700; color: #fff; }
.BatchMate-mp-badges { display: flex; flex-wrap: wrap; gap: 7px; margin-bottom: 12px; }
.BatchMate-mp-badge {
    background: rgba(255,255,255,.18); border: 1px solid rgba(255,255,255,.35);
    color: #fff; padding: 3px 11px; border-radius: 20px; font-size: 12px; font-weight: 500;
}
.BatchMate-mp-badge-id { background: rgba(255,255,255,.9); color: #1a3a6b; font-weight: 700; }
.BatchMate-mp-locked-note { margin: 0; font-size: 12px; color: rgba(255,255,255,.7); display: flex; align-items: center; gap: 5px; }

/* ── Form Cards ── */
.BatchMate-mp-card {
    background: #fff; border: 1px solid #e2e8f0;
    border-radius: 12px; margin-bottom: 18px;
    box-shadow: 0 1px 6px rgba(0,0,0,.05);
    overflow: hidden;
}
.BatchMate-mp-card-head {
    display: flex; align-items: center; gap: 10px;
    padding: 14px 22px; background: #f8fafc;
    border-bottom: 1px solid #e2e8f0;
}
.BatchMate-mp-card-head .dashicons { color: #2271b1; font-size: 20px; width: 20px; height: 20px; }
.BatchMate-mp-card-head h3 { margin: 0; font-size: 15px; font-weight: 600; color: #1d2327; }
.BatchMate-mp-card-body { padding: 22px 22px 6px; }

/* ── Form Fields ── */
.BatchMate-mp-row {
    display: grid; grid-template-columns: 1fr 1fr;
    gap: 18px; margin-bottom: 18px;
}
.BatchMate-mp-group { display: flex; flex-direction: column; gap: 5px; }
.BatchMate-mp-group.full { grid-column: 1 / -1; }
.BatchMate-mp-group label { font-size: 13px; font-weight: 600; color: #374151; }
.BatchMate-mp-group .req { color: #e53e3e; }
.BatchMate-mp-hint { font-size: 11px; color: #6b7280; margin: 2px 0 0; }
.BatchMate-mp-group input[type="text"],
.BatchMate-mp-group input[type="email"],
.BatchMate-mp-group input[type="url"],
.BatchMate-mp-group input[type="password"],
.BatchMate-mp-group select {
    width: 100%; padding: 9px 13px; height: 42px !important;
    border: 1.5px solid #d1d5db; border-radius: 7px;
    font-size: 14px; color: #1d2327; background: #fafafa;
    transition: border-color .2s, box-shadow .2s;
    box-sizing: border-box; font-family: inherit;
}
.BatchMate-mp-group textarea {
    width: 100%; padding: 9px 13px;
    border: 1.5px solid #d1d5db; border-radius: 7px;
    font-size: 14px; color: #1d2327; background: #fafafa;
    transition: border-color .2s, box-shadow .2s;
    box-sizing: border-box; font-family: inherit;
}
.BatchMate-mp-group input:focus,
.BatchMate-mp-group select:focus,
.BatchMate-mp-group textarea:focus {
    border-color: #2271b1; outline: none; background: #fff;
    box-shadow: 0 0 0 3px rgba(34,113,177,.12);
}
.BatchMate-mp-group textarea { resize: vertical; min-height: 78px; }
.BatchMate-mp-check-label {
    display: inline-flex; align-items: center; gap: 7px;
    font-size: 14px; font-weight: 400; cursor: pointer; color: #374151;
}
.BatchMate-mp-check-label input { width: 16px; height: 16px; margin: 0; }

/* ── Password ── */
.BatchMate-mp-pass-note { margin: 0 0 16px; font-size: 13px; color: #6b7280; }
.BatchMate-mp-pass-strength {
    padding: 8px 14px; border-radius: 7px; font-size: 13px; font-weight: 600;
    margin-top: -10px; margin-bottom: 16px;
}
.strength-weak   { background: #fff5f5; color: #c53030; border: 1px solid #fed7d7; }
.strength-medium { background: #fffbeb; color: #92400e; border: 1px solid #fde68a; }
.strength-strong { background: #f0fff4; color: #276749; border: 1px solid #9ae6b4; }

/* ── Actions ── */
.BatchMate-mp-actions {
    display: flex; justify-content: space-between; align-items: center;
    padding: 18px 0 30px;
}
.BatchMate-mp-actions .button-primary.button-large {
    height: auto; padding: 10px 26px; font-size: 15px; line-height: 1.5;
    display: inline-flex; align-items: center; gap: 5px; border-radius: 7px;
}

@media (max-width: 720px) {
    .BatchMate-mp-header-card { flex-direction: column; text-align: center; }
    .BatchMate-mp-row { grid-template-columns: 1fr; }
}

/* ── Skills Preview Tags ── */
.mp-skills-preview-wrap { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 10px; }
.mp-skill-preview-tag {
    display: inline-block;
    padding: 4px 14px;
    background: #eff6ff;
    border: 1px solid #bfdbfe;
    color: #1d4ed8;
    border-radius: 30px;
    font-size: 12px;
    font-weight: 500;
}
</style>

<script>
jQuery(document).ready(function($) {

    var ajaxUrl = '<?php echo esc_js( $ajax_url ); ?>';
    var nonce   = '<?php echo esc_js( $nonce ); ?>';

    /* ── Select2 for country/district dropdowns ── */
    if ($.fn.select2) {
        $('.BatchMate-select2').select2({ width: '100%' });
    }

    var bdUpazilas = <?php echo json_encode( function_exists('BatchMate_get_bd_upazilas') ? BatchMate_get_bd_upazilas() : [] ); ?>;

    function populateMpUpazilas($distSelect, $upzSelect, selectedVal) {
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

    $('#mp_present_district').on('change', function() {
        populateMpUpazilas($(this), $('#mp_present_upazila'), '');
    });
    $('#mp_permanent_district').on('change', function() {
        populateMpUpazilas($(this), $('#mp_upazila'), '');
    });

    /* ── Country → District visibility ── */
    $('#mp_present_country').on('change', function() {
        var isBD = $(this).val() === 'Bangladesh';
        $('#wrap_mp_present_district, #wrap_mp_present_upazila').toggle(isBD);
        if (!isBD && $.fn.select2) {
            $('#mp_present_district, #mp_present_upazila').val('').trigger('change.select2');
        }
    });
    $('#mp_permanent_country').on('change', function() {
        var isBD = $(this).val() === 'Bangladesh';
        $('#wrap_mp_perm_district, #wrap_mp_perm_upazila').toggle(isBD);
        if (!isBD && $.fn.select2) {
            $('#mp_permanent_district, #mp_upazila').val('').trigger('change.select2');
        }
    });

    /* ── Photo Preview & 400x400 Resize ── */
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

    var mpResizedPhoto = null;
    var isResizingMpPhoto = false;
    $('#BatchMate-mp-photo-input').on('change', function() {
        if (isResizingMpPhoto) return;
        var input = this;
        var file = input.files && input.files[0];
        if (!file) {
            mpResizedPhoto = null;
            return;
        }

        isResizingMpPhoto = true;
        $('#BatchMate-mp-submit-btn').prop('disabled', true);

        batchmateResizeTo400(file, function(resizedFile, previewUrl) {
            mpResizedPhoto = resizedFile;
            if (previewUrl) {
                $('#BatchMate-mp-photo-preview').attr('src', previewUrl);
            }
            if (resizedFile && window.DataTransfer) {
                var dt = new DataTransfer();
                dt.items.add(resizedFile);
                input.files = dt.files;
            }
            isResizingMpPhoto = false;
            $('#BatchMate-mp-submit-btn').prop('disabled', false);
        });
    });

    /* ── Password Strength ── */
    $('#mp_new_password').on('input', function() {
        var pass = $(this).val();
        var $bar = $('#BatchMate-mp-pass-strength');
        if (!pass) { $bar.hide(); return; }
        var strength = 0;
        if (pass.length >= 8)            strength++;
        if (/[A-Z]/.test(pass))          strength++;
        if (/[0-9]/.test(pass))          strength++;
        if (/[^A-Za-z0-9]/.test(pass))   strength++;
        $bar.removeClass('strength-weak strength-medium strength-strong').show();
        if (strength <= 1)      $bar.addClass('strength-weak').text('Weak password');
        else if (strength <= 2) $bar.addClass('strength-medium').text('Medium password');
        else                    $bar.addClass('strength-strong').text('Strong password ✓');
    });

    /* ── Skills Live Preview ── */
    function renderSkillsPreview() {
        var raw = $('#mp_skills').val();
        var $wrap = $('#mp_skills_preview');
        $wrap.empty();
        if (!raw.trim()) return;
        var skills = raw.split(',').map(function(s){ return s.trim(); }).filter(Boolean);
        skills.forEach(function(skill) {
            $wrap.append('<span class="mp-skill-preview-tag">' + $('<span>').text(skill).html() + '</span>');
        });
    }
    $('#mp_skills').on('input', renderSkillsPreview);
    renderSkillsPreview(); // on load

    /* ── Save ── */
    $('#BatchMate-mp-save-btn').on('click', function() {

        // Basic validation
        if (!$.trim($('#mp_name').val())) {
            Swal.fire('Required', 'Full Name is required.', 'warning'); return;
        }

        // Password match
        var newPass  = $('#mp_new_password').val();
        var confPass = $('#mp_confirm_password').val();
        if (newPass && newPass !== confPass) {
            Swal.fire('Password Mismatch', 'New password and confirm password do not match.', 'error'); return;
        }

        Swal.fire({
            title: 'Saving...', text: 'Please wait.',
            allowOutsideClick: false, showConfirmButton: false,
            willOpen: () => Swal.showLoading()
        });

        var formData = new FormData();
        formData.append('action',    'batchmate_member_self_save');
        formData.append('_wpnonce',  nonce);
        formData.append('name',      $('#mp_name').val());
        formData.append('name_bn',   $('#mp_name_bn').val());
        formData.append('email',     $('#mp_email').val());
        formData.append('about_me',             $('#mp_about_me').val());
        formData.append('cell_number',          $('#mp_cell_number').val());
        formData.append('cell_show',            $('#mp_cell_show').is(':checked') ? 1 : 0);
        formData.append('land_phone_number',    $('#mp_land_phone_number').val());
        formData.append('date_of_birth',        $('#mp_date_of_birth').val());
        formData.append('blood_group',          $('#mp_blood_group').val());
        formData.append('gender',               $('#mp_gender').val());
        formData.append('religion',             $('#mp_religion').val());
        formData.append('employment_position',  $('#mp_employment_position').val());
        formData.append('organization',         $('#mp_organization').val());
        formData.append('industry',             $('#mp_industry').val());
        formData.append('work_experience',      $('#mp_work_experience').val());
        formData.append('previous_organizations', $('#mp_previous_organizations').val());
        formData.append('employment_address',   $('#mp_employment_address').val());
        formData.append('skills',               $('#mp_skills').val());
        formData.append('present_address',      $('#mp_present_address').val());
        formData.append('permanent_address',    $('#mp_permanent_address').val());
        formData.append('present_district',     $('#mp_present_district').val());
        formData.append('present_country',      $('#mp_present_country').val());
        formData.append('present_upazila',      $('#mp_present_upazila').val());
        formData.append('permanent_district',   $('#mp_permanent_district').val());
        formData.append('permanent_country',    $('#mp_permanent_country').val());
        formData.append('upazila',              $('#mp_upazila').val());
        formData.append('facebook_url',         $('#mp_facebook_url').val());
        formData.append('linkedin_url',         $('#mp_linkedin_url').val());
        formData.append('x_url',               $('#mp_x_url').val());
        formData.append('website',             $('#mp_website').val());
        if (newPass) formData.append('new_password', newPass);

        var photoFile = $('#BatchMate-mp-photo-input')[0].files;
        if (photoFile.length > 0) formData.append('member_photo', photoFile[0]);

        $.ajax({
            url: ajaxUrl, type: 'POST',
            data: formData, processData: false, contentType: false,
            success: function(res) {
                if (res.success) {
                    // নামের badge আপডেট
                    $('#BatchMate-mp-display-name').text($('#mp_name').val());
                    // Password field clear
                    $('#mp_new_password, #mp_confirm_password').val('');
                    $('#BatchMate-mp-pass-strength').hide();
                    // Photo input reset
                    $('#BatchMate-mp-photo-input').val('');
                    Swal.fire({ title: 'Saved!', text: res.data, icon: 'success', timer: 2000, showConfirmButton: false });
                } else {
                    Swal.fire('Error!', res.data, 'error');
                }
            },
            error: function() {
                Swal.fire('Error!', 'Request failed. Please try again.', 'error');
            }
        });
    });

});
</script>
