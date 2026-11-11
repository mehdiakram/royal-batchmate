<?php

$settings = get_option("batchmate_settings", array());
$fields_config = $settings["fields"] ?? [];

if (!function_exists("bm_field_visible")) {
    function bm_field_visible($key, $config) {
        if ($key === "reg_name" || $key === "reg_cell_number") return true;
        if (!empty($config) && is_array($config)) {
            if (isset($config[$key]["show"])) {
                return !empty($config[$key]["show"]);
            }
            // Backward compatibility for separated country & upazila fields
            if ($key === "reg_present_country" || $key === "reg_present_upazila") {
                return !empty($config["reg_present_district"]["show"]);
            }
            if ($key === "reg_permanent_country" || $key === "reg_permanent_upazila") {
                return !empty($config["reg_permanent_district"]["show"]);
            }
            return false;
        }
        return in_array($key, array("reg_name_bn", "reg_email", "reg_dob", "reg_gender", "reg_photo", "reg_board", "reg_institution", "reg_present_address", "reg_present_country", "reg_present_district", "reg_present_upazila", "reg_permanent_address", "reg_permanent_country", "reg_permanent_district", "reg_permanent_upazila"), true);
    }
}
if (!function_exists("bm_field_required")) {
    function bm_field_required($key, $config) {
        if ($key === "reg_name" || $key === "reg_cell_number") return true;
        if (!empty($config) && is_array($config)) {
            return !empty($config[$key]["req"]);
        }
        // Email is optional by default
        return in_array($key, array("reg_name_bn", "reg_dob", "reg_gender", "reg_photo", "reg_board", "reg_institution"), true);
    }
}
if (!function_exists("bm_req_attr")) {
    function bm_req_attr($key, $config) {
        return bm_field_required($key, $config) ? " required" : "";
    }
}
if (!function_exists("bm_req_star")) {
    function bm_req_star($key, $config) {
        return bm_field_required($key, $config) ? ' <span style="color:red;">*</span>' : '';
    }
}
if (!function_exists("bm_field_label")) {
    function bm_field_label($key, $default = '') {
        if (function_exists('batchmate_get_field_label')) {
            return batchmate_get_field_label($key, $default);
        }
        return $default;
    }
}

/*
Template Name: BatchMate Registration Form
*/

get_header(); 

global $wpdb;
$dept_table = $wpdb->prefix . 'batchmate_department';
$prog_table = $wpdb->prefix . 'batchmate_program';
$pmode_table = $wpdb->prefix . 'batchmate_payment_modes'; // Payment Modes Table

// [FIXED] Only Fetch Active Departments
$departments = $wpdb->get_results( "SELECT id, dept_name FROM $dept_table WHERE is_active = 1 ORDER BY dept_name ASC" );

// [FIXED] Only Fetch Active Programs
$all_programs = $wpdb->get_results( "SELECT id, dept_id, program_name FROM $prog_table WHERE is_active = 1 ORDER BY program_name ASC" );

// [NEW] Fetch Payment Modes from Database
$payment_modes = $wpdb->get_results( "SELECT id, name FROM $pmode_table ORDER BY name ASC" );

// [NEW] Fetch Theme Settings
$settings = get_option('batchmate_settings', array());
$reg_fee_text = $settings['reg_fee_text'] ?? 'Payment Method * [204 Taka]';
$reg_fee_active = $settings['reg_fee_active'] ?? 'on';
$exam_type    = $settings['exam_type'] ?? 'SSC';
$passing_year = $settings['passing_year'] ?? '1999';
$auto_verify  = $settings['auto_verify'] ?? 'off';
?>

<link rel='stylesheet' href='<?php echo ROYAL_BATCHMATE_PLUGIN_URL; ?>/assets/select2/select2.min.css'>
<link rel='stylesheet' href='<?php echo ROYAL_BATCHMATE_PLUGIN_URL; ?>/assets/daterangepicker/daterangepicker.css'>
<link rel='stylesheet' href='<?php echo ROYAL_BATCHMATE_PLUGIN_URL; ?>/assets/sweetalert2/sweetalert2.min.css'>

<style>
.registration-form input:not([type="file"]):not([type="checkbox"]):not([type="radio"]),
.registration-form select {
    height: 42px !important;
    box-sizing: border-box;
}
.registration-form .select2-container .select2-selection--single {
    height: 42px !important;
    display: flex;
    align-items: center;
}
.registration-form .select2-container--default .select2-selection--single .select2-selection__rendered {
    line-height: 42px;
}
.registration-form .select2-container--default .select2-selection--single .select2-selection__arrow {
    height: 40px;
}
.registration-form input[type="file"] {
    height: 42px !important;
    padding: 8px 12px;
    box-sizing: border-box;
}
.registration-form ::placeholder,
.registration-form input::placeholder,
.registration-form textarea::placeholder {
    color: #94a3b8 !important;
    opacity: 0.85 !important;
    font-weight: 400 !important;
}
#photo-preview {
    max-width: 130px;
    max-height: 130px;
    border-radius: 10px;
    margin-top: 10px;
    border: 2px solid #2563eb;
    box-shadow: 0 4px 12px rgba(0,0,0,0.12);
    object-fit: cover;
    display: none;
}
</style>

<div id="welcome"> <section class="breadcrumb">
        <div class="container">
            <h1 class="pagetitle"><?php the_title(); ?></h1>
        </div>
    </section>
    <div class="container pagesection">
        <div class="registration-form">
            <form action="<?php echo esc_url( admin_url('admin-post.php') ); ?>" method="POST" enctype="multipart/form-data">
                
                <input type="hidden" name="action" value="pua_handle_registration">
                <?php wp_nonce_field( 'pua_registration_action', 'pua_registration_nonce' ); ?>
                
                <!-- Bot Protection: Honeypot Field (hidden via CSS) -->
                <div style="position: absolute; left: -9999px; opacity: 0; height: 0; overflow: hidden;" aria-hidden="true">
                    <label for="website_url">Leave this field empty</label>
                    <input type="text" name="website_url" id="website_url" autocomplete="off" tabindex="-1">
                </div>
                <!-- Bot Protection: Time-based Validation -->
                <input type="hidden" name="_pua_form_time" value="<?php echo time(); ?>">

                <div class="form-section">
                    <h3>1. Personal Information</h3>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="reg_name">Name (as per certificate - English) *</label>
                            <input type="text" id="reg_name" name="reg_name" required>
                        </div>
                        <?php if ( bm_field_visible( 'reg_name_bn', $fields_config ) ) : ?>
                        <div class="form-group">
                            <label for="reg_name_bn">Name (Bangla)<?php echo bm_req_star('reg_name_bn', $fields_config); ?></label>
                            <input type="text" id="reg_name_bn" name="reg_name_bn"<?php echo bm_req_attr('reg_name_bn', $fields_config); ?>>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php if ( bm_field_visible( 'reg_email', $fields_config ) ) : ?>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="reg_email">Email (Optional)<?php echo bm_req_star('reg_email', $fields_config); ?></label>
                            <input type="email" id="reg_email" name="reg_email"<?php echo bm_req_attr('reg_email', $fields_config); ?>>
                        </div>
                    </div>
                    <?php endif; ?>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="reg_cell_number">Cell Number *</label>
                            <input type="text" id="reg_cell_number" name="reg_cell_number" required>
                        </div>
                        <?php if ( bm_field_visible( 'reg_land_phone', $fields_config ) ) : ?>
                        <div class="form-group">
                            <label for="reg_land_phone">Alternative Number (Optional)<?php echo bm_req_star('reg_land_phone', $fields_config); ?></label>
                            <input type="text" id="reg_land_phone" name="reg_land_phone"<?php echo bm_req_attr('reg_land_phone', $fields_config); ?>>
                        </div>
                        <?php endif; ?>
                    </div>

                    <div class="form-row">
                        <div class="form-group full-width" style="display: flex; align-items: center; margin-bottom: 0;">
                            <input type="checkbox" id="reg_cell_show" name="reg_cell_show" value="1" style="width: auto; margin-right: 10px; height: auto;" checked>
                            <label for="reg_cell_show" style="margin-bottom: 0; font-weight: normal; font-size: 0.9em;">Show my cell number in the public member list.</label>
                        </div>
                    </div>
                    <?php if ( bm_field_visible( 'reg_blood_group', $fields_config ) || bm_field_visible( 'reg_dob', $fields_config ) ) : ?>
                    <div class="form-row">
                        <?php if ( bm_field_visible( 'reg_blood_group', $fields_config ) ) : ?>
                        <div class="form-group">
                            <label for="reg_blood_group">Blood Group<?php echo bm_req_star('reg_blood_group', $fields_config); ?></label>
                            <select id="reg_blood_group" name="reg_blood_group" class="select2-field"<?php echo bm_req_attr('reg_blood_group', $fields_config); ?>>
                                <option value="">Select...</option>
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
                        <?php endif; ?>
                        <?php if ( bm_field_visible( 'reg_dob', $fields_config ) ) : ?>
                        <div class="form-group">
                            <label for="reg_dob">Date of Birth<?php echo bm_req_star('reg_dob', $fields_config); ?></label>
                            <input type="text" id="reg_dob" name="reg_dob"<?php echo bm_req_attr('reg_dob', $fields_config); ?> autocomplete="off">
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ( bm_field_visible( 'reg_gender', $fields_config ) || bm_field_visible( 'reg_religion', $fields_config ) ) : ?>
                    <div class="form-row">
                        <?php if ( bm_field_visible( 'reg_gender', $fields_config ) ) : ?>
                        <div class="form-group">
                            <label for="reg_gender">Gender<?php echo bm_req_star('reg_gender', $fields_config); ?></label>
                            <select id="reg_gender" name="reg_gender"<?php echo bm_req_attr('reg_gender', $fields_config); ?>>
                                <option value="">Select Gender...</option>
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                            </select>
                        </div>
                        <?php endif; ?>
                        <?php if ( bm_field_visible( 'reg_religion', $fields_config ) ) : ?>
                        <div class="form-group">
                            <label for="reg_religion">Religion<?php echo bm_req_star('reg_religion', $fields_config); ?></label>
                            <select id="reg_religion" name="reg_religion"<?php echo bm_req_attr('reg_religion', $fields_config); ?>>
                                <option value="">Select Religion...</option>
                                <option value="Islam">Islam</option>
                                <option value="Hinduism">Hinduism</option>
                                <option value="Buddhism">Buddhism</option>
                                <option value="Christianity">Christianity</option>
                                <option value="Others">Others</option>
                            </select>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>

                    <?php if ( bm_field_visible( 'reg_photo', $fields_config ) ) : ?>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="reg_photo">Photo (Will be resized to 400x400)<?php echo bm_req_star('reg_photo', $fields_config); ?></label>
                            <input type="file" id="reg_photo" name="reg_photo" accept="image/jpeg, image/png"<?php echo bm_req_attr('reg_photo', $fields_config); ?>>
                            <img id="photo-preview" src="" alt="Photo Preview">
                        </div>
                    </div>
                    <?php endif; ?>
                </div>

                <?php
                $show_board       = bm_field_visible('reg_board', $fields_config);
                $show_roll        = bm_field_visible('reg_roll_no', $fields_config);
                $show_regno       = bm_field_visible('reg_registration_no', $fields_config);
                $show_group       = bm_field_visible('reg_group', $fields_config);
                $show_institution = bm_field_visible('reg_institution', $fields_config);
                $has_academic     = $show_board || $show_roll || $show_regno || $show_group || $show_institution;
                ?>
                <?php if ($has_academic): ?>
                <div class="form-section">
                    <h3>2. Academic Information</h3>
                    
                    <input type="hidden" name="reg_exam_type" value="<?php echo esc_attr($exam_type); ?>">
                    <input type="hidden" name="reg_passing_year" value="<?php echo esc_attr($passing_year); ?>">

                    <?php if ($show_board || $show_roll): ?>
                    <div class="form-row">
                        <?php if ($show_board): ?>
                        <div class="form-group">
                            <label for="reg_board">Board<?php echo bm_req_star('reg_board', $fields_config); ?></label>
                            <select id="reg_board" name="reg_board" class="select2-field"<?php echo bm_req_attr('reg_board', $fields_config); ?>>
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
                        <?php endif; ?>
                        <?php if ($show_roll): ?>
                        <div class="form-group">
                            <label for="reg_roll_no"><?php echo esc_html($exam_type); ?> Roll<?php echo bm_req_star('reg_roll_no', $fields_config); ?></label>
                            <input type="text" id="reg_roll_no" name="reg_roll_no" autocomplete="off"<?php echo bm_req_attr('reg_roll_no', $fields_config); ?>>
                            <div id="roll-no-check-msg" style="margin-top:6px;font-size:13px;display:none;"></div>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>

                    <?php if ($show_regno || $show_group): ?>
                    <div class="form-row">
                        <?php if ($show_regno): ?>
                        <div class="form-group">
                            <label for="reg_registration_no">Registration No<?php echo bm_req_star('reg_registration_no', $fields_config); ?></label>
                            <input type="text" id="reg_registration_no" name="reg_registration_no"<?php echo bm_req_attr('reg_registration_no', $fields_config); ?>>
                        </div>
                        <?php endif; ?>
                        <?php if ($show_group): ?>
                        <div class="form-group">
                            <label for="reg_group">Group<?php echo bm_req_star('reg_group', $fields_config); ?></label>
                            <select id="reg_group" name="reg_group" class="select2-field"<?php echo bm_req_attr('reg_group', $fields_config); ?>>
                                <option value="">Select Group...</option>
                                <option value="Science">Science</option>
                                <option value="Commerce">Commerce</option>
                                <option value="Humanities">Humanities</option>
                                <option value="Vocational">Vocational</option>
                                <option value="Others">Others</option>
                            </select>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>

                    <?php if ($show_institution): ?>
                    <div class="form-row">
                        <div class="form-group full-width">
                            <label for="reg_institution"><?php echo esc_html( bm_field_label('reg_institution', 'Institution') ); ?><?php echo bm_req_star('reg_institution', $fields_config); ?></label>
                            <input type="text" id="reg_institution" name="reg_institution"<?php echo bm_req_attr('reg_institution', $fields_config); ?> list="institutions_list" autocomplete="off" placeholder="Start typing your school/college name...">
                            <datalist id="institutions_list">
                                <?php
                                $saved_institutions = function_exists('batchmate_get_distinct_institutions') ? batchmate_get_distinct_institutions() : array();
                                foreach ( $saved_institutions as $inst ) :
                                ?>
                                    <option value="<?php echo esc_attr( $inst ); ?>"></option>
                                <?php endforeach; ?>
                            </datalist>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <?php
                $show_pres_addr    = bm_field_visible('reg_present_address', $fields_config);
                $show_pres_country = bm_field_visible('reg_present_country', $fields_config);
                $show_pres_dist    = bm_field_visible('reg_present_district', $fields_config);
                $show_pres_upazila = bm_field_visible('reg_present_upazila', $fields_config);

                $show_perm_addr    = bm_field_visible('reg_permanent_address', $fields_config);
                $show_perm_country = bm_field_visible('reg_permanent_country', $fields_config);
                $show_perm_dist    = bm_field_visible('reg_permanent_district', $fields_config);
                $show_perm_upazila = bm_field_visible('reg_permanent_upazila', $fields_config);

                $has_address   = $show_pres_addr || $show_pres_country || $show_pres_dist || $show_pres_upazila ||
                                 $show_perm_addr || $show_perm_country || $show_perm_dist || $show_perm_upazila;
                ?>
                <?php if ($has_address): ?>
                <div class="form-section">
                    <h3>3. Address</h3>
                    <?php if ($show_pres_addr || $show_perm_addr): ?>
                    <div class="form-row">
                        <?php if ($show_pres_addr): ?>
                        <div class="form-group">
                            <label for="reg_present_address"><?php echo esc_html( bm_field_label('reg_present_address', 'Present Address') ); ?><?php echo bm_req_star('reg_present_address', $fields_config); ?></label>
                            <textarea id="reg_present_address" name="reg_present_address"<?php echo bm_req_attr('reg_present_address', $fields_config); ?>></textarea>
                        </div>
                        <?php endif; ?>
                        <?php if ($show_perm_addr): ?>
                        <div class="form-group">
                            <label for="reg_permanent_address"><?php echo esc_html( bm_field_label('reg_permanent_address', 'Permanent Address') ); ?><?php echo bm_req_star('reg_permanent_address', $fields_config); ?></label>
                            <textarea id="reg_permanent_address" name="reg_permanent_address"<?php echo bm_req_attr('reg_permanent_address', $fields_config); ?>></textarea>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>

                    <?php
                    require_once ROYAL_BATCHMATE_PLUGIN_DIR . '/includes/countries.php';
                    require_once ROYAL_BATCHMATE_PLUGIN_DIR . '/includes/bd-locations.php';
                    $all_countries = BatchMate_get_countries();
                    $bd_districts  = BatchMate_get_bd_districts();
                    $bd_upazilas   = BatchMate_get_bd_upazilas();
                    ?>
                    <?php if ($show_pres_country || $show_pres_dist || $show_pres_upazila): ?>
                    <div class="form-row">
                        <?php if ($show_pres_country): ?>
                        <div class="form-group">
                            <label for="reg_present_country"><?php echo esc_html( bm_field_label('reg_present_country', 'Present Country') ); ?><?php echo bm_req_star('reg_present_country', $fields_config); ?></label>
                            <select id="reg_present_country" name="reg_present_country" class="select2-field"<?php echo bm_req_attr('reg_present_country', $fields_config); ?>>
                                <option value="">Select Country...</option>
                                <?php foreach ($all_countries as $c) : ?>
                                <option value="<?php echo esc_attr($c); ?>" <?php selected('Bangladesh', $c); ?>><?php echo esc_html($c); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <?php endif; ?>
                        <?php if ($show_pres_dist): ?>
                        <div class="form-group" id="wrap_reg_present_district">
                            <label for="reg_present_district"><?php echo esc_html( bm_field_label('reg_present_district', 'Present District') ); ?><?php echo bm_req_star('reg_present_district', $fields_config); ?></label>
                            <select id="reg_present_district" name="reg_present_district" class="select2-field"<?php echo bm_req_attr('reg_present_district', $fields_config); ?>>
                                <option value="">Select District...</option>
                                <?php foreach ($bd_districts as $dist) : ?>
                                <option value="<?php echo esc_attr($dist); ?>"><?php echo esc_html($dist); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <?php endif; ?>
                        <?php if ($show_pres_upazila): ?>
                        <div class="form-group" id="wrap_reg_present_upazila">
                            <label for="reg_present_upazila"><?php echo esc_html( bm_field_label('reg_present_upazila', 'Present Thana / Upazila') ); ?><?php echo bm_req_star('reg_present_upazila', $fields_config); ?></label>
                            <select id="reg_present_upazila" name="reg_present_upazila" class="select2-field"<?php echo bm_req_attr('reg_present_upazila', $fields_config); ?>>
                                <option value="">Select Thana / Upazila...</option>
                            </select>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($show_perm_country || $show_perm_dist || $show_perm_upazila): ?>
                    <div class="form-row">
                        <?php if ($show_perm_country): ?>
                        <div class="form-group">
                            <label for="reg_permanent_country"><?php echo esc_html( bm_field_label('reg_permanent_country', 'Permanent Country') ); ?><?php echo bm_req_star('reg_permanent_country', $fields_config); ?></label>
                            <select id="reg_permanent_country" name="reg_permanent_country" class="select2-field"<?php echo bm_req_attr('reg_permanent_country', $fields_config); ?>>
                                <option value="">Select Country...</option>
                                <?php foreach ($all_countries as $c) : ?>
                                <option value="<?php echo esc_attr($c); ?>" <?php selected('Bangladesh', $c); ?>><?php echo esc_html($c); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <?php endif; ?>
                        <?php if ($show_perm_dist): ?>
                        <div class="form-group" id="wrap_reg_permanent_district">
                            <label for="reg_permanent_district"><?php echo esc_html( bm_field_label('reg_permanent_district', 'Permanent District') ); ?><?php echo bm_req_star('reg_permanent_district', $fields_config); ?></label>
                            <select id="reg_permanent_district" name="reg_permanent_district" class="select2-field"<?php echo bm_req_attr('reg_permanent_district', $fields_config); ?>>
                                <option value="">Select District...</option>
                                <?php foreach ($bd_districts as $dist) : ?>
                                <option value="<?php echo esc_attr($dist); ?>"><?php echo esc_html($dist); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <?php endif; ?>
                        <?php if ($show_perm_upazila): ?>
                        <div class="form-group" id="wrap_reg_permanent_upazila">
                            <label for="reg_permanent_upazila"><?php echo esc_html( bm_field_label('reg_permanent_upazila', 'Permanent Thana / Upazila') ); ?><?php echo bm_req_star('reg_permanent_upazila', $fields_config); ?></label>
                            <select id="reg_permanent_upazila" name="reg_permanent_upazila" class="select2-field"<?php echo bm_req_attr('reg_permanent_upazila', $fields_config); ?>>
                                <option value="">Select Thana / Upazila...</option>
                            </select>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <?php
                $show_org   = bm_field_visible('reg_organization', $fields_config);
                $show_pos   = bm_field_visible('reg_position', $fields_config);
                $show_ind   = bm_field_visible('reg_industry', $fields_config);
                $show_exp   = bm_field_visible('reg_work_experience', $fields_config);
                $show_eaddr = bm_field_visible('reg_employment_address', $fields_config);
                $show_prev  = bm_field_visible('reg_previous_organizations', $fields_config);
                $has_prof   = $show_org || $show_pos || $show_ind || $show_exp || $show_eaddr || $show_prev;
                ?>
                <?php if ($has_prof): ?>
                <div class="form-section">
                    <h3>4. Professional Information (If any)</h3>
                    <?php if ($show_org || $show_pos): ?>
                    <div class="form-row">
                        <?php if ($show_org): ?>
                        <div class="form-group">
                            <label for="reg_organization">Organization<?php echo bm_req_star('reg_organization', $fields_config); ?></label>
                            <input type="text" id="reg_organization" name="reg_organization"<?php echo bm_req_attr('reg_organization', $fields_config); ?>>
                        </div>
                        <?php endif; ?>
                        <?php if ($show_pos): ?>
                        <div class="form-group">
                            <label for="reg_position">Position / Designation<?php echo bm_req_star('reg_position', $fields_config); ?></label>
                            <input type="text" id="reg_position" name="reg_position"<?php echo bm_req_attr('reg_position', $fields_config); ?>>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                    <?php if ($show_ind || $show_exp): ?>
                    <div class="form-row">
                        <?php if ($show_ind): ?>
                        <div class="form-group">
                            <label for="reg_industry">Industry<?php echo bm_req_star('reg_industry', $fields_config); ?></label>
                            <input type="text" id="reg_industry" name="reg_industry" placeholder="e.g., IT, Banking"<?php echo bm_req_attr('reg_industry', $fields_config); ?>>
                        </div>
                        <?php endif; ?>
                        <?php if ($show_exp): ?>
                        <div class="form-group">
                            <label for="reg_work_experience">Total Work Experience (Years)<?php echo bm_req_star('reg_work_experience', $fields_config); ?></label>
                            <input type="number" id="reg_work_experience" name="reg_work_experience" placeholder="e.g. 5"<?php echo bm_req_attr('reg_work_experience', $fields_config); ?>>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                    <?php if ($show_eaddr || $show_prev): ?>
                    <div class="form-row">
                        <?php if ($show_eaddr): ?>
                        <div class="form-group">
                            <label for="reg_employment_address">Organization Address<?php echo bm_req_star('reg_employment_address', $fields_config); ?></label>
                            <textarea id="reg_employment_address" name="reg_employment_address"<?php echo bm_req_attr('reg_employment_address', $fields_config); ?>></textarea>
                        </div>
                        <?php endif; ?>
                        <?php if ($show_prev): ?>
                        <div class="form-group">
                            <label for="reg_previous_organizations">Previous Organizations<?php echo bm_req_star('reg_previous_organizations', $fields_config); ?></label>
                            <textarea id="reg_previous_organizations" name="reg_previous_organizations" placeholder="Where else have you worked?"<?php echo bm_req_attr('reg_previous_organizations', $fields_config); ?>></textarea>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                
                <?php
                $show_about  = bm_field_visible('reg_about_me', $fields_config);
                $show_skills = bm_field_visible('reg_skills', $fields_config);
                $has_profile = $show_about || $show_skills;
                ?>
                <?php if ($has_profile): ?>
                <div class="form-section">
                    <h3>5. Profile Details</h3>
                    <?php if ($show_about): ?>
                    <div class="form-row">
                        <div class="form-group full-width">
                            <label for="reg_about_me">About Me (Bio)<?php echo bm_req_star('reg_about_me', $fields_config); ?></label>
                            <textarea id="reg_about_me" name="reg_about_me" rows="3"<?php echo bm_req_attr('reg_about_me', $fields_config); ?>></textarea>
                        </div>
                    </div>
                    <?php endif; ?>
                    <?php if ($show_skills): ?>
                    <div class="form-row">
                        <div class="form-group full-width">
                            <label for="reg_skills">Skills (Comma-separated)<?php echo bm_req_star('reg_skills', $fields_config); ?></label>
                            <input type="text" id="reg_skills" name="reg_skills" placeholder="e.g., Leadership, Web Development, Public Speaking"<?php echo bm_req_attr('reg_skills', $fields_config); ?>>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <?php
                $show_fb   = bm_field_visible('reg_facebook', $fields_config);
                $show_li   = bm_field_visible('reg_linkedin', $fields_config);
                $show_tw   = bm_field_visible('reg_x', $fields_config);
                $show_web  = bm_field_visible('reg_website', $fields_config);
                $has_social = $show_fb || $show_li || $show_tw || $show_web;
                ?>
                <?php if ($has_social): ?>
                <div class="form-section">
                    <h3>6. Social Links (Optional)</h3>
                    <?php if ($show_fb || $show_li): ?>
                    <div class="form-row">
                        <?php if ($show_fb): ?>
                        <div class="form-group">
                            <label for="reg_facebook">Facebook URL<?php echo bm_req_star('reg_facebook', $fields_config); ?></label>
                            <input type="text" id="reg_facebook" name="reg_facebook"<?php echo bm_req_attr('reg_facebook', $fields_config); ?>>
                        </div>
                        <?php endif; ?>
                        <?php if ($show_li): ?>
                        <div class="form-group">
                            <label for="reg_linkedin">LinkedIn URL<?php echo bm_req_star('reg_linkedin', $fields_config); ?></label>
                            <input type="text" id="reg_linkedin" name="reg_linkedin"<?php echo bm_req_attr('reg_linkedin', $fields_config); ?>>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                    <?php if ($show_tw || $show_web): ?>
                    <div class="form-row">
                        <?php if ($show_tw): ?>
                        <div class="form-group">
                            <label for="reg_x">X (Twitter) URL<?php echo bm_req_star('reg_x', $fields_config); ?></label>
                            <input type="text" id="reg_x" name="reg_x"<?php echo bm_req_attr('reg_x', $fields_config); ?>>
                        </div>
                        <?php endif; ?>
                        <?php if ($show_web): ?>
                        <div class="form-group">
                            <label for="reg_website">Website<?php echo bm_req_star('reg_website', $fields_config); ?></label>
                            <input type="text" id="reg_website" name="reg_website"<?php echo bm_req_attr('reg_website', $fields_config); ?>>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <?php if ( $reg_fee_active === 'on' ) : ?>
                <div class="form-section">
                    <h3>7. Payment Information</h3>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="reg_payment_method"><?php echo esc_html($reg_fee_text); ?></label>
                            <select id="reg_payment_method" name="reg_payment_method" required>
                                <option value="">Select Payment Method...</option>
                                <?php
                                $configured_methods = function_exists( 'batchmate_get_payment_methods' ) ? batchmate_get_payment_methods( 0 ) : array();
                                if ( ! empty( $configured_methods ) ) :
                                    foreach ( $configured_methods as $mode_str ) :
                                ?>
                                    <option value="<?php echo esc_attr( $mode_str ); ?>"><?php echo esc_html( $mode_str ); ?></option>
                                <?php
                                    endforeach;
                                elseif ( ! empty( $payment_modes ) ) :
                                    foreach ( $payment_modes as $mode ) :
                                ?>
                                    <option value="<?php echo esc_attr( $mode->name ); ?>"><?php echo esc_html( $mode->name ); ?></option>
                                <?php
                                    endforeach;
                                else :
                                ?>
                                    <option value="Cash">Cash</option>
                                <?php endif; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="reg_payment_details">Payment Details (TrxID / Ref) *</label>
                            <input type="text" id="reg_payment_details" name="reg_payment_details" required>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <div style="text-align: center;">
                    <button type="submit" class="submit-btn">Submit Application</button>
                </div>

            </form>
        </div>
    </div>
</div>

<script src='<?php echo ROYAL_BATCHMATE_PLUGIN_URL; ?>/assets/moment/moment.min.js'></script>
<script src='<?php echo ROYAL_BATCHMATE_PLUGIN_URL; ?>/assets/daterangepicker/daterangepicker.js'></script>
<script src='<?php echo ROYAL_BATCHMATE_PLUGIN_URL; ?>/assets/select2/select2.min.js'></script>
<script src='<?php echo ROYAL_BATCHMATE_PLUGIN_URL; ?>/assets/sweetalert2/sweetalert2.all.min.js'></script>

<script type="text/javascript">
jQuery(document).ready(function($) {
    // ── Live Roll No + Board check (on blur) ──
    var memberCheckXhr = null;
    
    function checkDuplicateMember() {
        var rollNo = $.trim($('#reg_roll_no').val());
        var board = $.trim($('#reg_board').val());
        var $msg = $('#roll-no-check-msg');
        
        if (!rollNo || !board) { $msg.hide(); return; }

        if (memberCheckXhr) memberCheckXhr.abort();
        $msg.html('<span style="color:#888;">&#8987; Checking...</span>').show();

        memberCheckXhr = $.ajax({
            url: '<?php echo esc_js( admin_url("admin-ajax.php") ); ?>',
            type: 'POST',
            data: { action: 'puaa_check_roll_no_member', roll_no: rollNo, board: board },
            success: function(res) {
                if (res.success && res.data.is_member) {
                    var profileUrl = '<?php echo esc_js( home_url("/member/") ); ?>' + res.data.member_slug + '/';
                    $msg.html(
                        '<span style="color:#dc2626;font-weight:600;">&#9888; This Registration already exists.</span>' +
                        ' <a href="' + profileUrl + '" target="_blank" style="color:#2271b1;font-weight:600;">View Profile &rarr;</a><br>' +
                        '<button type="button" class="claim-btn" style="margin-top:8px;background:#f59e0b;color:#fff;border:none;padding:5px 10px;border-radius:4px;cursor:pointer;font-weight:bold;">Claim This Account</button>'
                    ).show();
                    // Disable submit
                    $('.submit-btn').prop('disabled', true).css('opacity','0.5');
                } else {
                    $msg.html('<span style="color:#16a34a;font-weight:600;">&#10003; Available.</span>').show();
                    $('.submit-btn').prop('disabled', false).css('opacity','1');
                }
            },
            error: function() { $msg.hide(); }
        });
    }

    $('#reg_roll_no, #reg_board').on('blur change', checkDuplicateMember);

    // Re-enable submit if inputs change
    $('#reg_roll_no, #reg_board').on('input', function() {
        $('#roll-no-check-msg').hide();
        $('.submit-btn').prop('disabled', false).css('opacity','1');
    });

    $('form').on('submit', function(e) {
        // Block submit if check shows a member
        if ($('#roll-no-check-msg').text().indexOf('already exists') !== -1) {
            e.preventDefault();
            Swal.fire({
                icon: 'warning',
                title: 'Already a Member',
                text: 'A member with this Roll No and Board already exists. You can claim it if it belongs to you.',
                confirmButtonColor: '#2271b1'
            });
            return false;
        }
        $(this).find('.submit-btn').prop('disabled', true).text('Submitting...');
        Swal.fire({
            title: 'Submitting...',
            text: 'Please wait while your application is being processed.',
            icon: 'info',
            allowOutsideClick: false,
            allowEscapeKey: false,
            showConfirmButton: false,
            willOpen: () => { Swal.showLoading(); }
        });
    });

    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('submission') === 'success') {
        Swal.fire({
            title: 'Submitted!',
            text: 'Your application has been received successfully.',
            icon: 'success',
            timer: 3000, 
            showConfirmButton: false
        }).then((result) => {
            window.location.href = '<?php echo esc_url( home_url('/registration-success/') ); ?>';
        });
    } else if (urlParams.get('submission') === 'already_member') {
        var memberNum  = decodeURIComponent(urlParams.get('member_num')  || '');
        var memberSlug = decodeURIComponent(urlParams.get('member_slug') || '');
        var profileUrl = '<?php echo esc_js( home_url("/member/") ); ?>' + memberSlug + '/';
        Swal.fire({
            icon: 'info',
            title: 'You Are Already a Member!',
            html:
                '<p style="margin-bottom:14px;">A member with this Student ID is already registered.</p>' +
                '<p style="font-size:18px;font-weight:700;color:#2271b1;margin-bottom:18px;">Member ID: ' + memberNum + '</p>' +
                '<a href="' + profileUrl + '" target="_blank" style="display:inline-block;background:#2271b1;color:#fff;padding:10px 22px;border-radius:6px;text-decoration:none;font-weight:600;">View My Profile &rarr;</a>',
            confirmButtonText: 'OK',
            confirmButtonColor: '#2271b1',
            allowOutsideClick: true
        });
    } else if (urlParams.get('submission') === 'error') {
        Swal.fire({ title: 'Error!', text: 'Application could not be saved. Please try again.', icon: 'error' });
        $('.submit-btn').prop('disabled', false).text('Submit Application');
    }

    window.BatchMateBdUpazilas = <?php echo json_encode( BatchMate_get_bd_upazilas() ); ?>;

    $('.select2-field').select2({ width: '100%' });

    function populateUpazilas($districtSelect, $upazilaSelect, selectedVal) {
        var dist = $districtSelect.val();
        var upzList = (window.BatchMateBdUpazilas && window.BatchMateBdUpazilas[dist]) || [];
        var opts = '<option value="">Select Thana / Upazila...</option>';
        upzList.forEach(function(u) {
            var sel = (selectedVal && selectedVal === u) ? ' selected' : '';
            opts += '<option value="' + u + '"' + sel + '>' + u + '</option>';
        });
        $upazilaSelect.html(opts);
        if ($.fn.select2) {
            $upazilaSelect.trigger('change.select2');
        }
    }

    // District change → populate Thana / Upazila
    $('#reg_present_district').on('change', function() {
        populateUpazilas($(this), $('#reg_present_upazila'), '');
    });

    $('#reg_permanent_district').on('change', function() {
        populateUpazilas($(this), $('#reg_permanent_upazila'), '');
    });

    // Country change → hide/show district and upazila logic
    $('#reg_present_country').on('change', function() {
        var isBD = $(this).val() === 'Bangladesh';
        $('#wrap_reg_present_district, #wrap_reg_present_upazila').toggle(isBD);
        if (!isBD) {
            if ($.fn.select2) {
                $('#reg_present_district').val('').trigger('change.select2');
                $('#reg_present_upazila').val('').trigger('change.select2');
            } else {
                $('#reg_present_district, #reg_present_upazila').val('');
            }
        }
    }).trigger('change');

    $('#reg_permanent_country').on('change', function() {
        var isBD = $(this).val() === 'Bangladesh';
        $('#wrap_reg_permanent_district, #wrap_reg_permanent_upazila').toggle(isBD);
        if (!isBD) {
            if ($.fn.select2) {
                $('#reg_permanent_district').val('').trigger('change.select2');
                $('#reg_permanent_upazila').val('').trigger('change.select2');
            } else {
                $('#reg_permanent_district, #reg_permanent_upazila').val('');
            }
        }
    }).trigger('change');

    $('#reg_dob').daterangepicker({ singleDatePicker: true, showDropdowns: true, autoApply: true, locale: { format: 'YYYY-MM-DD' } });


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

    let isResizingRegPhoto = false;
    $('#reg_photo').on('change', function() {
        if (isResizingRegPhoto) return;
        const input = this;
        const file = input.files && input.files[0];
        if (!file) {
            $('#photo-preview').attr('src', '').hide();
            return;
        }

        // Instant preview right after file selection
        if (window.URL && window.URL.createObjectURL) {
            try {
                $('#photo-preview').attr('src', window.URL.createObjectURL(file)).show();
            } catch(e){}
        }

        isResizingRegPhoto = true;
        $('.submit-btn').prop('disabled', true);

        batchmateResizeTo400(file, function(resizedFile, previewUrl) {
            if (previewUrl) {
                $('#photo-preview').attr('src', previewUrl).show();
            }
            if (resizedFile && window.DataTransfer) {
                const dt = new DataTransfer();
                dt.items.add(resizedFile);
                input.files = dt.files;
            }
            isResizingRegPhoto = false;
            $('.submit-btn').prop('disabled', false);
        });
    });
});
</script>

<?php get_footer(); ?>
