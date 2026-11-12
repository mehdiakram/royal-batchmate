<div id="tab-labels" class="BatchMate-tab-pane">

    <div class="BatchMate-settings-card">
        <div class="BatchMate-card-head">
            <span class="dashicons dashicons-admin-appearance"></span>
            <h3>Customize Front-End Labels</h3>
        </div>
        <div class="BatchMate-card-body">
            <p style="margin-bottom: 20px; color: #50575e;">Change these labels to adapt the plugin for different types of organizations (e.g. Alumni, Members, Students).</p>

            <div class="BatchMate-field-grid">
                <div class="BatchMate-field-group">
                    <label for="label_batchmate">"Batchmate" (Singular)</label>
                    <input type="text" id="label_batchmate" name="label_batchmate"
                           value="<?php echo esc_attr($label_batchmate); ?>"
                           placeholder="e.g. Batchmate, Alumni, Student">
                </div>
                <div class="BatchMate-field-group">
                    <label for="label_batchmates">"Batchmates" (Plural)</label>
                    <input type="text" id="label_batchmates" name="label_batchmates"
                           value="<?php echo esc_attr($label_batchmates); ?>"
                           placeholder="e.g. Batchmates, Alumni, Students">
                </div>
                <div class="BatchMate-field-group">
                    <label for="label_member">"Member" (Singular)</label>
                    <input type="text" id="label_member" name="label_member"
                           value="<?php echo esc_attr($label_member); ?>"
                           placeholder="e.g. Member, User">
                </div>
                <div class="BatchMate-field-group">
                    <label for="label_members">"Members" (Plural)</label>
                    <input type="text" id="label_members" name="label_members"
                           value="<?php echo esc_attr($label_members); ?>"
                           placeholder="e.g. Members, Users">
                </div>
                <div class="BatchMate-field-group">
                    <label for="label_main_menu">Main Menu Name</label>
                    <input type="text" id="label_main_menu" name="label_main_menu"
                           value="<?php echo esc_attr($label_main_menu); ?>"
                           placeholder="e.g. BatchMate">
                </div>
                <div class="BatchMate-field-group">
                    <label for="label_settings_menu">Settings Menu Name</label>
                    <input type="text" id="label_settings_menu" name="label_settings_menu"
                           value="<?php echo esc_attr($label_settings_menu); ?>"
                           placeholder="e.g. Settings">
                </div>
                <div class="BatchMate-field-group" style="grid-column: 1 / -1;">
                    <label for="label_settings_page_title">Settings Page Header Title</label>
                    <input type="text" id="label_settings_page_title" name="label_settings_page_title"
                           value="<?php echo esc_attr($label_settings_page_title); ?>"
                           placeholder="e.g. BatchMate Settings">
                </div>
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

</div><!-- /#tab-labels -->
