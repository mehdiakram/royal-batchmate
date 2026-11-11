<div id="tab-registration" class="BatchMate-tab-pane">
            
            <div class="BatchMate-settings-card">
                <div class="BatchMate-card-head">
                    <span class="dashicons dashicons-admin-network"></span>
                    <h3>Auto Verification</h3>
                </div>
                <div class="BatchMate-card-body">
                    <div class="BatchMate-toggle-row">
                        <div class="BatchMate-toggle-info">
                            <strong>Enable Auto Verification</strong>
                            <p>Verify Roll and Registration numbers automatically with the Education Board API.</p>
                        </div>
                        <label class="BatchMate-toggle-switch">
                            <input type="checkbox" id="auto_verify" name="auto_verify" value="on" <?php checked('on', $auto_verify); ?>>
                            <span class="BatchMate-toggle-slider"></span>
                        </label>
                    </div>
                </div>
            </div>

            <div class="BatchMate-settings-card">
                <div class="BatchMate-card-head">
                    <span class="dashicons dashicons-welcome-learn-more"></span>
                    <h3>Batch Information</h3>
                </div>
                <div class="BatchMate-card-body">
                    <div class="BatchMate-field-grid">
                        <div class="BatchMate-field-group">
                            <label for="exam_type">Exam Type</label>
                            <input type="text" id="exam_type" name="exam_type"
                                   value="<?php echo esc_attr($exam_type); ?>"
                                   placeholder="e.g. SSC or HSC">
                            <p class="BatchMate-field-hint">Used for verification.</p>
                        </div>
                        <div class="BatchMate-field-group">
                            <label for="passing_year">Passing Year</label>
                            <input type="text" id="passing_year" name="passing_year"
                                   value="<?php echo esc_attr($passing_year); ?>"
                                   placeholder="e.g. 1999">
                            <p class="BatchMate-field-hint">Used for verification.</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="BatchMate-settings-card">
                <div class="BatchMate-card-head">
                    <span class="dashicons dashicons-money-alt"></span>
                    <h3>Registration Fee</h3>
                </div>
                <div class="BatchMate-card-body">
                    <div class="BatchMate-toggle-row">
                        <div class="BatchMate-toggle-info">
                            <strong>Enable Registration Fee</strong>
                            <p>Collect payment information during member registration.</p>
                        </div>
                        <label class="BatchMate-toggle-switch">
                            <input type="checkbox" id="reg_fee_active" name="reg_fee_active" value="on" <?php checked('on', $reg_fee_active); ?> onchange="document.getElementById('reg_fee_text_wrapper').style.display = this.checked ? 'block' : 'none';">
                            <span class="BatchMate-toggle-slider"></span>
                        </label>
                    </div>

                    <div id="reg_fee_text_wrapper" style="margin-top: 15px; display: <?php echo $reg_fee_active === 'on' ? 'block' : 'none'; ?>;">
                        <div class="BatchMate-field-grid">
                            <div class="BatchMate-field-group">
                                <label for="reg_fee_amount">Registration Fee Amount (BDT)</label>
                                <input type="number" id="reg_fee_amount" name="reg_fee_amount"
                                       value="<?php echo esc_attr($reg_fee_amount); ?>"
                                       placeholder="e.g. 200" min="0" step="0.01">
                                <p class="BatchMate-field-hint">Fixed membership fee amount in BDT. Added to total fee during Direct Member event registration.</p>
                            </div>
                            <div class="BatchMate-field-group">
                                <label for="reg_fee_text">Fee Display Label</label>
                                <input type="text" id="reg_fee_text" name="reg_fee_text"
                                       value="<?php echo esc_attr($reg_fee_text); ?>"
                                       placeholder="e.g. Payment Method * [204 Taka]">
                                <p class="BatchMate-field-hint">Label displayed above payment details in the registration form.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="BatchMate-settings-card">
                <div class="BatchMate-card-head">
                    <span class="dashicons dashicons-cart"></span>
                    <h3>Payment Methods &amp; Accounts</h3>
                </div>
                <div class="BatchMate-card-body">
                    <div class="BatchMate-field-group">
                        <label for="reg_payment_methods"><?php esc_html_e( 'Payment Method Options / Accounts (One per line)', 'royal-batchmate' ); ?></label>
                        <textarea id="reg_payment_methods" name="reg_payment_methods" rows="5"
                                  placeholder="<?php echo esc_attr( "01717-164695 (Firoz)\n01719-073668 (Bobi)\n01751-771222 (Awal)" ); ?>"
                                  style="width:100%;font-family:monospace;font-size:13px;"><?php echo esc_textarea($reg_payment_methods); ?></textarea>
                        <p class="BatchMate-field-hint"><?php printf( esc_html__( 'Enter payment methods, mobile numbers, or account info one per line (e.g. %s). These will appear as choices in the Payment Method dropdown in both Registration and Event forms (unless an event specifies its own custom methods).', 'royal-batchmate' ), '<code>01717-164695 (Firoz)</code>' ); ?></p>
                    </div>
                </div>
            </div>

<?php
$fields_config = [
    'Personal Information' => [
        'reg_name' => 'Name (English)',
        'reg_name_bn' => 'Name (Bangla)',
        'reg_email' => 'Email',
        'reg_cell_number' => 'Cell Number',
        'reg_land_phone' => 'Alternative Number',
        'reg_blood_group' => 'Blood Group',
        'reg_dob' => 'Date of Birth',
        'reg_gender' => 'Gender',
        'reg_religion' => 'Religion',
        'reg_photo' => 'Photo'
    ],
    'Academic Information' => [
        'reg_board' => 'Name of Board',
        'reg_roll_no' => 'SSC Roll',
        'reg_registration_no' => 'Registration No',
        'reg_group' => 'Group',
        'reg_institution' => 'Institution'
    ],
    'Address Information' => [
        'reg_present_address'   => 'Present Address',
        'reg_present_country'   => 'Present Country',
        'reg_present_district'  => 'Present District',
        'reg_present_upazila'   => 'Present Thana / Upazila',
        'reg_permanent_address' => 'Permanent Address',
        'reg_permanent_country' => 'Permanent Country',
        'reg_permanent_district'=> 'Permanent District',
        'reg_permanent_upazila' => 'Permanent Thana / Upazila'
    ],
    'Professional Information' => [
        'reg_organization' => 'Organization',
        'reg_position' => 'Position / Designation',
        'reg_industry' => 'Industry',
        'reg_work_experience' => 'Work Experience',
        'reg_employment_address' => 'Organization Address',
        'reg_previous_organizations' => 'Previous Organizations',
        'reg_about_me' => 'About Me',
        'reg_skills' => 'Skills'
    ],
    'Social & Web' => [
        'reg_facebook' => 'Facebook URL',
        'reg_linkedin' => 'LinkedIn URL',
        'reg_x' => 'X / Twitter URL',
        'reg_website' => 'Personal Website'
    ]
];

$field_settings = $settings['fields'] ?? [];
?>

<div class="BatchMate-settings-card" style="margin-top: 20px;">
    <div class="BatchMate-card-head">
        <span class="dashicons dashicons-admin-appearance"></span>
        <h3>Customize Front-End Labels (Institution &amp; Locations)</h3>
    </div>
    <div class="BatchMate-card-body">
        <p style="margin-bottom: 20px; color: #50575e;">
            Change these primary labels to adapt the registration form, member profiles, and event registration for your organization (e.g. Institution, School, District, Thana/Upazila). Leave blank to use default names.
        </p>
        <div class="BatchMate-field-grid">
            <div class="BatchMate-field-group">
                <label for="label_institution">"Institution" Label</label>
                <input type="text" id="label_institution" name="label_institution"
                       value="<?php echo esc_attr( $label_institution ); ?>"
                       placeholder="e.g. Institution, School, College, University">
            </div>
            <div class="BatchMate-field-group">
                <label for="label_present_district">"Present District" Label</label>
                <input type="text" id="label_present_district" name="label_present_district"
                       value="<?php echo esc_attr( $label_present_district ); ?>"
                       placeholder="e.g. Present District, বর্তমান জেলা, District">
            </div>
            <div class="BatchMate-field-group">
                <label for="label_present_upazila">"Present Thana / Upazila" Label</label>
                <input type="text" id="label_present_upazila" name="label_present_upazila"
                       value="<?php echo esc_attr( $label_present_upazila ); ?>"
                       placeholder="e.g. Present Thana / Upazila, থানা/উপজেলা, Upazila">
            </div>
            <div class="BatchMate-field-group">
                <label for="label_permanent_district">"Permanent District" Label</label>
                <input type="text" id="label_permanent_district" name="label_permanent_district"
                       value="<?php echo esc_attr( $label_permanent_district ); ?>"
                       placeholder="e.g. Permanent District, স্থায়ী জেলা">
            </div>
            <div class="BatchMate-field-group">
                <label for="label_permanent_upazila">"Permanent Thana / Upazila" Label</label>
                <input type="text" id="label_permanent_upazila" name="label_permanent_upazila"
                       value="<?php echo esc_attr( $label_permanent_upazila ); ?>"
                       placeholder="e.g. Permanent Thana / Upazila, স্থায়ী থানা/উপজেলা">
            </div>
            <div class="BatchMate-field-group">
                <label for="label_roll_no">"Roll No" Label</label>
                <input type="text" id="label_roll_no" name="label_roll_no"
                       value="<?php echo esc_attr( $label_roll_no ); ?>"
                       placeholder="e.g. SSC Roll, Roll No, Student ID">
            </div>
        </div>
    </div>
</div>

<div class="BatchMate-settings-card" style="margin-top: 20px;">
    <div class="BatchMate-card-head">
        <span class="dashicons dashicons-list-view"></span>
        <h3>Form Fields &amp; Labels Configuration</h3>
    </div>
    <div class="BatchMate-card-body">
        <p class="BatchMate-card-desc">
            Customize front-end field labels (e.g. <strong>Institution</strong>, <strong>Present District</strong>, <strong>Present Thana / Upazila</strong>), control visibility, and set mandatory fields for registration and member profile. Leave custom label blank to use the default name.
        </p>

        <!-- Quick Search & Filter bar -->
        <div style="margin: 15px 0 12px; display: flex; justify-content: space-between; align-items: center; gap: 12px; flex-wrap: wrap;">
            <div style="position: relative; max-width: 360px; width: 100%;">
                <input type="text" id="bm-field-search" placeholder="Search fields (e.g. Institution, District, Thana)..." style="width: 100%; padding: 7px 12px 7px 32px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 13px;">
                <span class="dashicons dashicons-search" style="position: absolute; left: 8px; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: 18px;"></span>
            </div>
            <div style="font-size: 12px; color: #64748b;">
                <span style="display: inline-block; width: 8px; height: 8px; background: #2563eb; border-radius: 50%; margin-right: 4px;"></span>
                Custom labels apply dynamically to registration, profile edit, and event guest forms.
            </div>
        </div>
        
        <div style="overflow-x: auto;">
        <table class="form-table bm-fields-table" style="width: 100%; border-collapse: collapse; margin-top: 5px;">
            <thead>
                <tr style="background: #f1f5f9; border-bottom: 2px solid #cbd5e1;">
                    <th style="padding: 12px; text-align: left; width: 220px;">Field Name</th>
                    <th style="padding: 12px; text-align: left;">Custom Front-End Label (Optional)</th>
                    <th style="padding: 12px; text-align: center; width: 90px;">Show</th>
                    <th style="padding: 12px; text-align: center; width: 90px;">Required</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($fields_config as $group => $group_fields): ?>
                <tr class="bm-field-group-header">
                    <td colspan="4" style="padding: 12px; background: #e2e8f0; font-weight: bold; border-top: 1px solid #cbd5e1; color: #1e293b;"><?php echo esc_html($group); ?></td>
                </tr>
                <?php 
                $has_saved_field_cfg = ! empty( $field_settings ) && is_array( $field_settings );
                foreach ($group_fields as $key => $label): 
                    if ( $has_saved_field_cfg ) {
                        if ( ! isset( $field_settings[$key] ) ) {
                            if ( $key === 'reg_present_country' ) {
                                $is_show = ! empty( $field_settings['reg_present_district']['show'] );
                                $is_req  = ! empty( $field_settings['reg_present_district']['req'] );
                            } elseif ( $key === 'reg_permanent_country' ) {
                                $is_show = ! empty( $field_settings['reg_permanent_district']['show'] );
                                $is_req  = ! empty( $field_settings['reg_permanent_district']['req'] );
                            } elseif ( $key === 'reg_present_upazila' ) {
                                $is_show = ! empty( $field_settings['reg_present_district']['show'] );
                                $is_req  = false;
                            } elseif ( $key === 'reg_permanent_upazila' ) {
                                $is_show = ! empty( $field_settings['reg_permanent_district']['show'] );
                                $is_req  = false;
                            } else {
                                $is_show = false;
                                $is_req  = false;
                            }
                        } else {
                            $is_show = ! empty( $field_settings[$key]['show'] );
                            $is_req  = ! empty( $field_settings[$key]['req'] );
                        }
                    } else {
                        // Default values for initial install before settings are saved
                        $is_show = in_array( $key, array('reg_name', 'reg_name_bn', 'reg_email', 'reg_cell_number', 'reg_dob', 'reg_gender', 'reg_photo', 'reg_board', 'reg_institution', 'reg_present_address', 'reg_present_country', 'reg_present_district', 'reg_present_upazila'), true );
                        $is_req  = in_array( $key, array('reg_name', 'reg_cell_number', 'reg_institution', 'reg_photo'), true );
                    }
                    
                    if ($key === 'reg_name') { $is_show = true; $is_req = true; }

                    $custom_label = $field_settings[$key]['label'] ?? ($settings['label_' . substr($key, 4)] ?? '');
                ?>
                <tr class="bm-field-row" style="border-bottom: 1px solid #e2e8f0;" data-field-name="<?php echo esc_attr(strtolower($label . ' ' . $key)); ?>">
                    <td style="padding: 10px 12px; vertical-align: middle;">
                        <strong style="color: #0f172a; font-size: 13px;"><?php echo esc_html($label); ?></strong>
                        <span style="display:block; font-size:11px; color:#64748b; font-family:monospace;"><?php echo esc_html($key); ?></span>
                    </td>
                    <td style="padding: 10px 12px; vertical-align: middle;">
                        <input type="text" 
                               name="fields[<?php echo esc_attr($key); ?>][label]" 
                               value="<?php echo esc_attr($custom_label); ?>" 
                               placeholder="<?php echo esc_attr($label); ?>" 
                               style="width: 100%; max-width: 320px; padding: 6px 10px; font-size: 13px; border: 1px solid #cbd5e1; border-radius: 6px;">
                    </td>
                    <td style="padding: 10px 12px; text-align: center; vertical-align: middle;">
                        <label class="BatchMate-toggle-switch" style="margin: 0 auto;">
                            <input type="checkbox" name="fields[<?php echo esc_attr($key); ?>][show]" value="1" <?php checked($is_show); ?> <?php echo ($key === 'reg_name') ? 'disabled' : ''; ?>>
                            <span class="BatchMate-toggle-slider"></span>
                        </label>
                        <?php if ($key === 'reg_name'): ?><input type="hidden" name="fields[reg_name][show]" value="1"><?php endif; ?>
                    </td>
                    <td style="padding: 10px 12px; text-align: center; vertical-align: middle;">
                        <label class="BatchMate-toggle-switch" style="margin: 0 auto;">
                            <input type="checkbox" name="fields[<?php echo esc_attr($key); ?>][req]" value="1" <?php checked($is_req); ?> <?php echo ($key === 'reg_name') ? 'disabled' : ''; ?>>
                            <span class="BatchMate-toggle-slider"></span>
                        </label>
                        <?php if ($key === 'reg_name'): ?><input type="hidden" name="fields[reg_name][req]" value="1"><?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
    </div>
</div>
        </div><!-- /#tab-registration -->