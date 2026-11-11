<div id="tab-organization" class="BatchMate-tab-pane BatchMate-tab-pane-active">

            <!-- Logo & Banner side-by-side -->
            <div class="BatchMate-media-grid">

                <!-- Logo Card -->
                <div class="BatchMate-settings-card BatchMate-media-card">
                    <div class="BatchMate-card-head">
                        <span class="dashicons dashicons-format-image"></span>
                        <h3>Organization Logo</h3>
                    </div>
                    <div class="BatchMate-card-body">
                        <div class="BatchMate-logo-preview-wrap" id="logo-preview-wrap">
                            <?php if ( $org_logo_url ) : ?>
                            <img src="<?php echo esc_url($org_logo_url); ?>" id="org-logo-preview" class="BatchMate-logo-preview-img" alt="Logo">
                            <?php else : ?>
                            <div class="BatchMate-media-placeholder" id="logo-placeholder">
                                <span class="dashicons dashicons-format-image"></span>
                                <p>No logo uploaded</p>
                            </div>
                            <img src="" id="org-logo-preview" class="BatchMate-logo-preview-img" style="display:none;" alt="Logo">
                            <?php endif; ?>
                        </div>
                        <input type="hidden" id="org_logo_url_input" name="org_logo_url" value="<?php echo esc_attr($org_logo_url); ?>" />
                        <div class="BatchMate-media-actions">
                            <button type="button" class="BatchMate-media-upload-btn" id="org_logo_upload_button">
                                <span class="dashicons dashicons-upload"></span> Upload Logo
                            </button>
                            <button type="button" class="BatchMate-media-remove-btn" id="org_logo_remove_button" <?php echo $org_logo_url ? '' : 'style="display:none;"'; ?>>
                                <span class="dashicons dashicons-trash"></span> Remove
                            </button>
                        </div>
                        <p class="BatchMate-field-hint">Recommended: PNG with transparent background</p>
                    </div>
                </div>

                <!-- Banner Card -->
                <div class="BatchMate-settings-card BatchMate-media-card BatchMate-banner-card">
                    <div class="BatchMate-card-head">
                        <span class="dashicons dashicons-images-alt2"></span>
                        <h3>Member Profile Banner</h3>
                        <?php if ( $member_profile_banner_url ) : ?>
                        <span class="BatchMate-badge BatchMate-badge-custom">Custom</span>
                        <?php else : ?>
                        <span class="BatchMate-badge BatchMate-badge-default">Default</span>
                        <?php endif; ?>
                    </div>
                    <div class="BatchMate-card-body">
                        <div class="BatchMate-banner-preview-wrap">
                            <img
                                id="banner-preview"
                                src="<?php echo esc_url( $member_profile_banner_url ?: $default_banner_url ); ?>"
                                class="BatchMate-banner-preview-img"
                                alt="Profile Banner"
                            />
                            <div class="BatchMate-banner-overlay" id="banner-overlay-label">
                                <?php echo $member_profile_banner_url ? '✓ Custom Banner' : '📌 Theme Default'; ?>
                            </div>
                        </div>
                        <input type="hidden" id="member_profile_banner_url_input" name="member_profile_banner_url" value="<?php echo esc_attr($member_profile_banner_url); ?>" />
                        <div class="BatchMate-media-actions">
                            <button type="button" class="BatchMate-media-upload-btn" id="banner_upload_button">
                                <span class="dashicons dashicons-upload"></span> Upload Banner
                            </button>
                            <button type="button" class="BatchMate-media-remove-btn" id="banner_remove_button" <?php echo $member_profile_banner_url ? '' : 'style="display:none;"'; ?>>
                                <span class="dashicons dashicons-image-rotate"></span> Restore Default
                            </button>
                        </div>
                        <p class="BatchMate-field-hint">Recommended: <strong>1200 × 300 px</strong> · Shown on all member profile pages</p>
                    </div>
                </div>

            </div><!-- /.BatchMate-media-grid -->

            <!-- Organization Details Card -->
            <div class="BatchMate-settings-card">
                <div class="BatchMate-card-head">
                    <span class="dashicons dashicons-building"></span>
                    <h3>Organization Details</h3>
                </div>
                <div class="BatchMate-card-body">
                    <div class="BatchMate-field-grid">
                        <div class="BatchMate-field-group">
                            <label for="org_name">Organization Name</label>
                            <input type="text" id="org_name" name="org_name"
                                   value="<?php echo esc_attr($org_name); ?>"
                                   placeholder="e.g. SSC Batch '99 Reunion Committee">
                            <p class="BatchMate-field-hint">Full formal name — used in print documents &amp; footers.</p>
                        </div>
                        <div class="BatchMate-field-group">
                            <label for="org_short_name">Organization Shortname</label>
                            <input type="text" id="org_short_name" name="org_short_name"
                                   value="<?php echo esc_attr($org_short_name); ?>"
                                   placeholder="e.g. Batch99 or B99RC">
                            <p class="BatchMate-field-hint">Short label — shown in the <strong>admin menu &amp; page headers</strong>. If empty, Organization Name is used.</p>
                        </div>
                        <div class="BatchMate-field-group">
                            <label for="org_member_prefix">Member ID Pattern</label>
                            
                            <div style="display:flex; align-items:center; gap:10px; margin-bottom:8px;">
                                <div style="flex:1;">
                                    <small style="display:block; color:#6b7280; font-weight:600; margin-bottom:4px;">1. Fixed Prefix</small>
                                    <input type="text" id="org_member_prefix" name="org_member_prefix"
                                           value="<?php echo esc_attr($org_member_prefix); ?>"
                                           placeholder="e.g. B99"
                                           maxlength="10"
                                           style="width:100%; text-transform:uppercase;"
                                           oninput="this.value=this.value.toUpperCase().replace(/[^A-Z0-9\-_]/g,''); updateIdPreview();">
                                </div>
                                <div style="flex:1;">
                                    <small style="display:block; color:#6b7280; font-weight:600; margin-bottom:4px;">2. Auto Year (Optional)</small>
                                    <select id="org_member_id_year" name="org_member_id_year" onchange="updateIdPreview();" style="width:100%;">
                                        <option value="none" <?php selected('none', $org_member_id_year); ?>>None (Don't include year)</option>
                                        <option value="2digit" <?php selected('2digit', $org_member_id_year); ?>>2-Digit Year (e.g. <?php echo date('y'); ?>)</option>
                                        <option value="4digit" <?php selected('4digit', $org_member_id_year); ?>>4-Digit Year (e.g. <?php echo date('Y'); ?>)</option>
                                    </select>
                                </div>
                                <div style="flex:1;">
                                    <small style="display:block; color:#6b7280; font-weight:600; margin-bottom:4px;">3. Serial Digits</small>
                                    <select id="org_member_id_digits" name="org_member_id_digits" onchange="updateIdPreview();" style="width:100%;">
                                        <option value="3" <?php selected('3', $org_member_id_digits); ?>>3 Digits (001)</option>
                                        <option value="4" <?php selected('4', $org_member_id_digits); ?>>4 Digits (0001)</option>
                                        <option value="5" <?php selected('5', $org_member_id_digits); ?>>5 Digits (00001)</option>
                                        <option value="6" <?php selected('6', $org_member_id_digits); ?>>6 Digits (000001)</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div style="background:#f8fafc; padding:10px 15px; border-radius:6px; border:1px solid #e2e8f0;">
                                <span style="font-size:13px; color:#64748b; font-weight:600;">Live Preview: </span>
                                <code id="prefix-preview" style="font-size:16px; color:#1a3a6b; font-weight:700; background:transparent;">BM-0001</code>
                            </div>
                            
                            <p class="BatchMate-field-hint">This pattern will be used to auto-generate Member IDs (e.g., when a registration is approved).</p>
                            
                            <script>
                            function updateIdPreview() {
                                var prefix = document.getElementById('org_member_prefix').value || 'BM';
                                var yearOpt = document.getElementById('org_member_id_year').value;
                                var digitsOpt = parseInt(document.getElementById('org_member_id_digits').value) || 4;
                                
                                var currentYear2 = '<?php echo date("y"); ?>';
                                var currentYear4 = '<?php echo date("Y"); ?>';
                                
                                var yearStr = '';
                                if (yearOpt === '2digit') yearStr = '-' + currentYear2;
                                else if (yearOpt === '4digit') yearStr = '-' + currentYear4;
                                
                                // Clean up prefix trailing dash if year is appended so we don't get double dash
                                if (yearStr !== '' && prefix.endsWith('-')) {
                                    prefix = prefix.slice(0, -1);
                                }
                                
                                var base = prefix + yearStr;
                                if (!base.endsWith('-') && !base.endsWith('_')) {
                                    base += '-';
                                }
                                
                                var zeros = '';
                                for(var i = 1; i < digitsOpt; i++) {
                                    zeros += '0';
                                }
                                var finalStr = base + zeros + '1';
                                
                                document.getElementById('prefix-preview').textContent = finalStr;
                            }
                            // Run once on load
                            document.addEventListener('DOMContentLoaded', updateIdPreview);
                            </script>
                        </div>
                        <div class="BatchMate-field-group">
                            <label for="org_tagline">Tagline</label>
                            <input type="text" id="org_tagline" name="org_tagline"
                                   value="<?php echo esc_attr($org_tagline); ?>"
                                   placeholder="e.g. Once a Primeian, Always a Primeian">
                        </div>
                        <div class="BatchMate-field-group">
                            <label for="org_email">Email Address</label>
                            <input type="email" id="org_email" name="org_email"
                                   value="<?php echo esc_attr($org_email); ?>"
                                   placeholder="info@batchmate.org">
                        </div>
                        <div class="BatchMate-field-group BatchMate-field-full">
                            <label for="org_address">Address</label>
                            <textarea id="org_address" name="org_address" rows="3"
                                      placeholder="Full organization address..."><?php echo esc_textarea($org_address); ?></textarea>
                        </div>
                    </div>
                </div>
            </div>


            <!-- Shortcodes & Page Templates Info Card -->
            <div class="BatchMate-settings-card" style="margin-top: 25px;">
                <div class="BatchMate-card-head">
                    <span class="dashicons dashicons-editor-code"></span>
                    <h3>Shortcodes &amp; Page Templates</h3>
                </div>
                <div class="BatchMate-card-body">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        <div>
                            <h4 style="margin-top:0; color:#1d2327;">Shortcodes</h4>
                            <table class="widefat striped">
                                <tbody>
                                    <tr><td style="font-family: monospace; font-weight: 600;">[memberlogin]</td><td>Member Login Form</td></tr>
                                    <tr><td style="font-family: monospace; font-weight: 600;">[ec session="0" style="photo" show_title="no"]</td><td>Executive Committee</td></tr>
                                    <tr><td style="font-family: monospace; font-weight: 600;">[batchmate_members]</td><td>Total Members Count</td></tr>
                                    <tr><td style="font-family: monospace; font-weight: 600;">[event_list]</td><td>Event List</td></tr>
                                    <tr><td style="font-family: monospace; font-weight: 600;">[event_form]</td><td>Event Registration Form</td></tr>
                                </tbody>
                            </table>
                        </div>
                        <div>
                            <h4 style="margin-top:0; color:#1d2327;">Page Templates</h4>
                            <p style="font-size: 13px; color: #646970; margin-bottom: 10px;">Select from "Template" dropdown in Page Editor.</p>
                            <table class="widefat striped">
                                <tbody>
                                    <tr><td style="font-weight: 600;">BatchMate Registration Form</td><td>For new member applications</td></tr>
                                    <tr><td style="font-weight: 600;">BatchMate Members List</td><td>Public member directory</td></tr>
                                    <tr><td style="font-weight: 600;">BatchMate Registration Success</td><td>Success/Thank you page</td></tr>
                                    <tr><td style="font-weight: 600;">BatchMate Application Print</td><td>Application print view</td></tr>
                                    <tr><td style="font-weight: 600;">BatchMate EC Print</td><td>EC list print view</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

        </div><!-- /#tab-organization -->