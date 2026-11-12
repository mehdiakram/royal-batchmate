<div id="tab-automation" class="BatchMate-tab-pane">
<div class="BatchMate-settings-card">
                <div class="BatchMate-card-head">
                    <span class="dashicons dashicons-email-alt"></span>
                    <h3>Mail Settings</h3>
                </div>
                <div class="BatchMate-card-body">
                    <p class="BatchMate-card-desc">Multiple addresses can be separated by commas — <code>info@example.com, admin@example.com</code></p>
                    <div class="BatchMate-field-grid">
                        <div class="BatchMate-field-group">
                            <label for="form_mail_to">
                                <span class="dashicons dashicons-admin-users"></span>
                                Member Registration Notify To
                            </label>
                            <input type="text" id="form_mail_to" name="form_mail_to"
                                   value="<?php echo esc_attr($form_mail_to); ?>"
                                   placeholder="info@batchmate.org">
                        </div>
                        <div class="BatchMate-field-group">
                            <label for="event_mail_to">
                                <span class="dashicons dashicons-calendar-alt"></span>
                                Event Registration Notify To
                            </label>
                            <input type="text" id="event_mail_to" name="event_mail_to"
                                   value="<?php echo esc_attr($event_mail_to); ?>"
                                   placeholder="info@batchmate.org">
                        </div>
                    </div>
                </div>
            </div>
<!-- Admin Menu Visibility -->
            <div class="BatchMate-settings-card">
                <div class="BatchMate-card-head">
                    <span class="dashicons dashicons-admin-generic"></span>
                    <h3>Admin Menu Visibility</h3>
                </div>
                <div class="BatchMate-card-body">
                    <div class="BatchMate-toggle-row">
                        <div class="BatchMate-toggle-info">
                            <strong>Show "Test Birthday" Menu</strong>
                            <p>Show or hide the "Test Birthday" menu under the plugin admin menu.</p>
                        </div>
                        <label class="BatchMate-toggle-switch">
                            <input type="checkbox" id="show_test_birthday" name="show_test_birthday" value="on" <?php checked('on', $show_test_birthday); ?>>
                            <span class="BatchMate-toggle-slider"></span>
                        </label>
                    </div>
                    <div class="BatchMate-toggle-row">
                        <div class="BatchMate-toggle-info">
                            <strong>Show "Test Event Card" Menu</strong>
                            <p>Show or hide the "Test Event Card" menu under the plugin admin menu.</p>
                        </div>
                        <label class="BatchMate-toggle-switch">
                            <input type="checkbox" id="show_test_event_card" name="show_test_event_card" value="on" <?php checked('on', $show_test_event_card); ?>>
                            <span class="BatchMate-toggle-slider"></span>
                        </label>
                    </div>
                    <div class="BatchMate-toggle-row">
                        <div class="BatchMate-toggle-info">
                            <strong>Show "Raw Data" & "Excel Import" Menus</strong>
                            <p>Show or hide the "Raw Data" and "Excel Import" menus under the plugin admin menu.</p>
                        </div>
                        <label class="BatchMate-toggle-switch">
                            <input type="checkbox" id="show_raw_excel" name="show_raw_excel" value="on" <?php checked('on', $show_raw_excel); ?>>
                            <span class="BatchMate-toggle-slider"></span>
                        </label>
                    </div>
                </div>
            </div>

<!-- Master Automation Switch Card -->
            <div class="BatchMate-settings-card">
                <div class="BatchMate-card-head">
                    <span class="dashicons dashicons-rest-api"></span>
                    <h3>Automation System</h3>
                </div>
                <div class="BatchMate-card-body">
                    <div class="BatchMate-toggle-row">
                        <div class="BatchMate-toggle-info">
                            <strong>Enable Automation System</strong>
                            <p>Master switch for Multi-Webhooks, SMS Gateway, Birthday Greetings, and Event Cards.</p>
                        </div>
                        <label class="BatchMate-toggle-switch">
                            <input type="checkbox" id="automation_active" name="automation_active" value="on" <?php checked('on', $automation_active); ?>>
                            <span class="BatchMate-toggle-slider"></span>
                        </label>
                    </div>
                </div>
            </div>

            <!-- Automation Inner Settings (shown when enabled) -->
            <div id="automation-inner-settings" style="<?php echo ($automation_active === 'on') ? '' : 'display:none;'; ?>">

                <!-- SECTION 1: Webhook Connections (1 or Multiple Webhooks) -->
                <div class="BatchMate-settings-card">
                    <div class="BatchMate-card-head">
                        <span class="dashicons dashicons-networking"></span>
                        <h3>Webhook Connections (Multi-Webhook)</h3>
                    </div>
                    <div class="BatchMate-card-body">
                        <p class="BatchMate-card-desc">
                            Connect 1 or more Webhook URLs (Make.com, Zapier, Pabbly, n8n, Discord, Custom API). Automation events will dispatch payloads simultaneously to all active webhooks.
                        </p>

                        <div class="batchmate-webhooks-container">
                            <table class="batchmate-webhook-table" id="batchmate-webhook-table">
                                <thead>
                                    <tr>
                                        <th style="width: 70px; text-align: center;">Status</th>
                                        <th style="width: 200px;">Label / Service</th>
                                        <th>Webhook URL</th>
                                        <th style="width: 70px; text-align: center;">Action</th>
                                    </tr>
                                </thead>
                                <tbody id="batchmate-webhook-rows">
                                    <?php 
                                    if ( ! empty( $webhook_urls ) ) :
                                        foreach ( $webhook_urls as $idx => $wh ) :
                                            $wh_status = $wh['status'] ?? 'on';
                                            $wh_name   = $wh['name'] ?? '';
                                            $wh_url    = $wh['url'] ?? '';
                                    ?>
                                    <tr class="batchmate-webhook-row" data-index="<?php echo intval($idx); ?>">
                                        <td style="text-align: center;">
                                            <label class="BatchMate-toggle-switch BatchMate-toggle-sm">
                                                <input type="checkbox" name="webhook_urls[<?php echo intval($idx); ?>][status]" value="on" <?php checked('on', $wh_status); ?>>
                                                <span class="BatchMate-toggle-slider"></span>
                                            </label>
                                        </td>
                                        <td>
                                            <input type="text" name="webhook_urls[<?php echo intval($idx); ?>][name]" value="<?php echo esc_attr($wh_name); ?>" placeholder="e.g. Make.com Facebook" style="width:100%;">
                                        </td>
                                        <td>
                                            <input type="url" name="webhook_urls[<?php echo intval($idx); ?>][url]" value="<?php echo esc_attr($wh_url); ?>" placeholder="https://hook.us1.make.com/..." style="width:100%;">
                                        </td>
                                        <td style="text-align: center;">
                                            <button type="button" class="button button-link-delete batchmate-remove-webhook-row" title="Remove Webhook">
                                                <span class="dashicons dashicons-trash"></span>
                                            </button>
                                        </td>
                                    </tr>
                                    <?php 
                                        endforeach;
                                    else:
                                    ?>
                                    <tr class="batchmate-webhook-row" data-index="0">
                                        <td style="text-align: center;">
                                            <label class="BatchMate-toggle-switch BatchMate-toggle-sm">
                                                <input type="checkbox" name="webhook_urls[0][status]" value="on" checked>
                                                <span class="BatchMate-toggle-slider"></span>
                                            </label>
                                        </td>
                                        <td>
                                            <input type="text" name="webhook_urls[0][name]" value="Make.com Webhook" placeholder="e.g. Make.com Facebook" style="width:100%;">
                                        </td>
                                        <td>
                                            <input type="url" name="webhook_urls[0][url]" value="<?php echo esc_attr($make_webhook_url); ?>" placeholder="https://hook.us1.make.com/..." style="width:100%;">
                                        </td>
                                        <td style="text-align: center;">
                                            <button type="button" class="button button-link-delete batchmate-remove-webhook-row" title="Remove Webhook">
                                                <span class="dashicons dashicons-trash"></span>
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>

                            <div style="margin-top: 14px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px;">
                                <button type="button" id="batchmate-add-webhook-btn" class="button button-secondary">
                                    <span class="dashicons dashicons-plus-alt2" style="vertical-align: middle;"></span> Add Another Webhook URL
                                </button>
                                
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <button type="button" id="batchmate-test-webhooks-btn" class="BatchMate-test-btn">
                                        <span class="dashicons dashicons-rest-api"></span> Test Ping Webhooks
                                    </button>
                                    <span id="batchmate-webhook-test-result" class="BatchMate-test-result"></span>
                                </div>
                            </div>
                        </div>

                        <hr class="BatchMate-divider">

                        <!-- Dedicated Buffer Direct Connections (GraphQL API) -->
                        <div class="batchmate-buffer-section">
                            <div class="BatchMate-toggle-row">
                                <div class="BatchMate-toggle-info">
                                    <strong><span class="dashicons dashicons-share-alt2"></span> Buffer Direct Connections (GraphQL API)</strong>
                                    <p>Post simultaneously to one or more Buffer channels (Facebook, Instagram, LinkedIn, X) alongside the Webhook URLs above.</p>
                                </div>
                                <label class="BatchMate-toggle-switch">
                                    <input type="checkbox" id="buffer_enabled" name="buffer_enabled" value="on" <?php checked('on', $buffer_enabled); ?>>
                                    <span class="BatchMate-toggle-slider"></span>
                                </label>
                            </div>

                            <div id="buffer-connections-fields" style="<?php echo ($buffer_enabled === 'on') ? '' : 'display:none;'; ?>; margin-top: 16px;">
                                <div class="BatchMate-field-group BatchMate-field-full" style="margin-bottom: 14px;">
                                    <label for="buffer_api_key">
                                        Buffer Personal API Key <span style="color:#ef4444;">*</span>
                                    </label>
                                    <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
                                        <input type="password" id="buffer_api_key" name="buffer_api_key"
                                               value="<?php echo esc_attr($buffer_api_key); ?>"
                                               placeholder="Paste your Buffer API Key from publish.buffer.com/settings/api"
                                               style="flex: 1; min-width: 260px;">
                                        <button type="button" id="batchmate-toggle-buffer-key" class="button button-secondary" title="Show/Hide Key">
                                            <span class="dashicons dashicons-visibility" style="vertical-align: middle;"></span>
                                        </button>
                                        <button type="button" id="batchmate-fetch-buffer-channels-btn" class="button button-secondary">
                                            <span class="dashicons dashicons-download" style="vertical-align: middle;"></span> Fetch Channels
                                        </button>
                                    </div>
                                    <span class="BatchMate-field-hint">
                                        Endpoint: <code>https://api.buffer.com</code> &mdash; Get your API key from <a href="https://publish.buffer.com/settings/api" target="_blank">Buffer API Settings</a>, then click <strong>Fetch Channels</strong> to auto-load your Channel IDs.
                                    </span>
                                    <div id="batchmate-buffer-fetched-channels" style="display:none; margin-top: 10px; padding: 10px 12px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px;"></div>
                                </div>

                                <table class="batchmate-webhook-table" id="batchmate-buffer-table">
                                    <thead>
                                        <tr>
                                            <th style="width: 70px; text-align: center;">Status</th>
                                            <th style="width: 200px;">Channel Label</th>
                                            <th>Buffer Channel ID</th>
                                            <th style="width: 190px;">Post Mode</th>
                                            <th style="width: 70px; text-align: center;">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody id="batchmate-buffer-rows">
                                        <?php
                                        if ( ! empty( $buffer_channels ) ) :
                                            foreach ( $buffer_channels as $b_idx => $bc ) :
                                                $bc_status = $bc['status'] ?? 'on';
                                                $bc_name   = $bc['name'] ?? '';
                                                $bc_id     = $bc['channel_id'] ?? '';
                                                $bc_mode   = $bc['mode'] ?? 'shareNow';
                                        ?>
                                        <tr class="batchmate-buffer-row" data-index="<?php echo intval($b_idx); ?>">
                                            <td style="text-align: center;">
                                                <label class="BatchMate-toggle-switch BatchMate-toggle-sm">
                                                    <input type="checkbox" name="buffer_channels[<?php echo intval($b_idx); ?>][status]" value="on" <?php checked('on', $bc_status); ?>>
                                                    <span class="BatchMate-toggle-slider"></span>
                                                </label>
                                            </td>
                                            <td>
                                                <input type="text" name="buffer_channels[<?php echo intval($b_idx); ?>][name]" value="<?php echo esc_attr($bc_name); ?>" placeholder="e.g. Facebook Page" style="width:100%;">
                                            </td>
                                            <td>
                                                <input type="text" name="buffer_channels[<?php echo intval($b_idx); ?>][channel_id]" value="<?php echo esc_attr($bc_id); ?>" placeholder="e.g. 67a1b2c3d4e5f6..." style="width:100%; font-family:monospace;">
                                            </td>
                                            <td>
                                                <select name="buffer_channels[<?php echo intval($b_idx); ?>][mode]" style="width:100%;">
                                                    <option value="shareNow"   <?php selected('shareNow',   $bc_mode); ?>>Publish Now (shareNow)</option>
                                                    <option value="addToQueue" <?php selected('addToQueue', $bc_mode); ?>>Add to Queue (addToQueue)</option>
                                                    <option value="shareNext"  <?php selected('shareNext',  $bc_mode); ?>>Share Next (shareNext)</option>
                                                </select>
                                            </td>
                                            <td style="text-align: center;">
                                                <button type="button" class="button button-link-delete batchmate-remove-buffer-row" title="Remove Channel">
                                                    <span class="dashicons dashicons-trash"></span>
                                                </button>
                                            </td>
                                        </tr>
                                        <?php
                                            endforeach;
                                        else :
                                        ?>
                                        <tr class="batchmate-buffer-row" data-index="0">
                                            <td style="text-align: center;">
                                                <label class="BatchMate-toggle-switch BatchMate-toggle-sm">
                                                    <input type="checkbox" name="buffer_channels[0][status]" value="on" checked>
                                                    <span class="BatchMate-toggle-slider"></span>
                                                </label>
                                            </td>
                                            <td>
                                                <input type="text" name="buffer_channels[0][name]" value="" placeholder="e.g. Facebook Page" style="width:100%;">
                                            </td>
                                            <td>
                                                <input type="text" name="buffer_channels[0][channel_id]" value="" placeholder="e.g. 67a1b2c3d4e5f6..." style="width:100%; font-family:monospace;">
                                            </td>
                                            <td>
                                                <select name="buffer_channels[0][mode]" style="width:100%;">
                                                    <option value="shareNow" selected>Publish Now (shareNow)</option>
                                                    <option value="addToQueue">Add to Queue (addToQueue)</option>
                                                    <option value="shareNext">Share Next (shareNext)</option>
                                                </select>
                                            </td>
                                            <td style="text-align: center;">
                                                <button type="button" class="button button-link-delete batchmate-remove-buffer-row" title="Remove Channel">
                                                    <span class="dashicons dashicons-trash"></span>
                                                </button>
                                            </td>
                                        </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>

                                <div style="margin-top: 14px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px;">
                                    <button type="button" id="batchmate-add-buffer-channel-btn" class="button button-secondary">
                                        <span class="dashicons dashicons-plus-alt2" style="vertical-align: middle;"></span> Add Buffer Channel
                                    </button>

                                    <div style="display: flex; align-items: center; gap: 10px;">
                                        <button type="button" id="batchmate-test-buffer-btn" class="BatchMate-test-btn">
                                            <span class="dashicons dashicons-share-alt2"></span> Send Test Buffer Post
                                        </button>
                                    </div>
                                </div>
                                <p class="BatchMate-field-hint" style="margin-top: 10px;">
                                    Note: Generated birthday/event cards expire after 24 hours, so <strong>Publish Now (shareNow)</strong> is recommended. Buffer also requires a publicly accessible image URL (live site, not localhost).
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- SECTION 2: SMS Gateway Settings (Alpha SMS / portal.sms.net.bd) -->
                <div class="BatchMate-settings-card">
                    <div class="BatchMate-card-head">
                        <span class="dashicons dashicons-smartphone"></span>
                        <h3>SMS Gateway Settings (<a href="https://portal.sms.net.bd/" target="_blank" style="color:inherit;text-decoration:underline;">Alpha SMS / portal.sms.net.bd</a>)</h3>
                    </div>
                    <div class="BatchMate-card-body">
                        <!-- SMS Master Toggle -->
                        <div class="BatchMate-toggle-row">
                            <div class="BatchMate-toggle-info">
                                <strong>Enable SMS Gateway</strong>
                                <p>Enable automated SMS dispatch for birthdays, event card confirmations, and member welcome alerts.</p>
                            </div>
                            <label class="BatchMate-toggle-switch">
                                <input type="checkbox" id="sms_enabled" name="sms_enabled" value="on" <?php checked('on', $sms_enabled); ?>>
                                <span class="BatchMate-toggle-slider"></span>
                            </label>
                        </div>

                        <div id="sms-gateway-fields" style="<?php echo ($sms_enabled === 'on') ? '' : 'display:none;'; ?>; margin-top: 16px;">
                            <hr class="BatchMate-divider">
                            <div class="BatchMate-field-grid">
                                <div class="BatchMate-field-group BatchMate-field-full">
                                    <label for="sms_api_key">
                                        Alpha SMS API Key <span style="color:#ef4444;">*</span>
                                    </label>
                                    <input type="text" id="sms_api_key" name="sms_api_key"
                                           value="<?php echo esc_attr($sms_api_key); ?>"
                                           placeholder="Your API Key from portal.sms.net.bd">
                                    <span class="BatchMate-field-hint">Endpoint: <code>https://api.sms.net.bd/sendsms</code></span>
                                </div>
                                <div class="BatchMate-field-group">
                                    <label for="sms_sender_id">
                                        Sender ID (Masking / Non-Masking) <span style="color:#64748b;font-weight:normal;">(Optional)</span>
                                    </label>
                                    <input type="text" id="sms_sender_id" name="sms_sender_id"
                                           value="<?php echo esc_attr($sms_sender_id); ?>"
                                           placeholder="e.g. 8809612XXXXXX or Approved Brand Name">
                                    <span class="BatchMate-field-hint">Leave blank if using default portal sender ID.</span>
                                </div>
                                <div class="BatchMate-field-group">
                                    <label>Account Balance Check</label>
                                    <div style="display: flex; align-items: center; gap: 10px; margin-top: 4px;">
                                        <button type="button" id="check-sms-balance-btn" class="button button-secondary">
                                            <span class="dashicons dashicons-money-alt" style="vertical-align: middle;"></span> Check Balance
                                        </button>
                                        <span id="sms-balance-display" style="font-weight: 700; color: #16a34a; font-size: 14px;"></span>
                                    </div>
                                </div>
                            </div>

                            <!-- Live SMS Tester Box -->
                            <div class="BatchMate-credentials-box" style="margin-top: 18px; background: #f0fdf4; border: 1px solid #bbf7d0;">
                                <h4 style="color: #166534; margin: 0 0 10px;"><span class="dashicons dashicons-testimonial"></span> Send Live Test SMS</h4>
                                <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
                                    <input type="text" id="test-sms-phone" placeholder="Recipient Phone (e.g. 01700000000)" style="max-width: 250px;">
                                    <button type="button" id="send-test-sms-btn" class="button button-primary">
                                        <span class="dashicons dashicons-email-alt" style="vertical-align: middle;"></span> Send Test SMS
                                    </button>
                                    <span id="test-sms-result" style="font-size: 13px; font-weight: 600;"></span>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>

                <!-- SECTION 3: Birthday Automation Card -->
                <div class="BatchMate-settings-card">
                    <div class="BatchMate-card-head">
                        <span class="dashicons dashicons-heart"></span>
                        <h3>Birthday Automation</h3>
                    </div>
                    <div class="BatchMate-card-body">
                        <!-- Birthday Master Toggle -->
                        <div class="BatchMate-toggle-row">
                            <div class="BatchMate-toggle-info">
                                <strong>Enable Birthday Wishes System</strong>
                                <p>Automatically generate birthday cards and send wishes via Email, SMS, Facebook, and Webhooks.</p>
                            </div>
                            <label class="BatchMate-toggle-switch">
                                <input type="checkbox" id="birthday_active" name="birthday_active" value="on" <?php checked('on', $birthday_active); ?>>
                                <span class="BatchMate-toggle-slider"></span>
                            </label>
                        </div>

                        <!-- Birthday Inner Settings -->
                        <div id="birthday-inner-settings" style="<?php echo ($birthday_active === 'on') ? '' : 'display:none;'; ?>; margin-top: 20px;">
                            
                            <!-- 1. Card Template -->
                            <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 16px; margin-bottom: 20px;">
                                <h4 style="margin: 0 0 12px; color: #1e293b;"><span class="dashicons dashicons-id"></span> <?php esc_html_e( 'Birthday Card Template', 'royal-batchmate' ); ?></h4>
                                <div class="BatchMate-birthday-template-grid" style="display: grid; grid-template-columns: 1fr 340px; gap: 24px; align-items: start;">
                                    <div class="BatchMate-field-group">
                                        <label for="birthday_card_template" style="font-weight: 600; color: #1e293b;"><?php esc_html_e( 'Select Card Design', 'royal-batchmate' ); ?></label>
                                        <select id="birthday_card_template" name="birthday_card_template" style="width: 100%; max-width: 360px;">
                                            <option value="birthdaycard1.php" <?php selected('birthdaycard1.php', $birthday_card_template); ?>><?php esc_html_e( 'Birthday Card 1 (Midnight Royal & Gold)', 'royal-batchmate' ); ?></option>
                                            <option value="birthdaycard2.php" <?php selected('birthdaycard2.php', $birthday_card_template); ?>><?php esc_html_e( 'Birthday Card 2 (Crimson & Rose Gold)', 'royal-batchmate' ); ?></option>
                                            <option value="birthdaycard3.php" <?php selected('birthdaycard3.php', $birthday_card_template); ?>><?php esc_html_e( 'Birthday Card 3 (Emerald & Gold)', 'royal-batchmate' ); ?></option>
                                        </select>
                                        <p class="BatchMate-field-hint" style="margin-top: 8px;">
                                            <?php esc_html_e( 'Pure GD canvas template rendered dynamically in 1080x1080 square format.', 'royal-batchmate' ); ?>
                                        </p>
                                        <div style="margin-top: 14px;">
                                            <button type="button" id="btn-refresh-card-preview" class="button button-secondary" style="display: inline-flex; align-items: center; gap: 6px;">
                                                <span class="dashicons dashicons-update"></span> <?php esc_html_e( 'Re-generate Preview', 'royal-batchmate' ); ?>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="BatchMate-card-preview-wrap" style="text-align: center;">
                                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                                            <span class="BatchMate-field-hint" style="margin: 0; font-size: 12px;"><?php esc_html_e( 'Live Canvas Preview:', 'royal-batchmate' ); ?> <strong id="preview-filename" style="color: #2563eb;"><?php echo esc_html( $birthday_card_template ); ?></strong></span>
                                            <span id="preview-loading-spinner" style="display: none; font-size: 11px; color: #64748b; align-items: center; gap: 4px;">
                                                <span class="dashicons dashicons-update BatchMate-spin" style="font-size: 14px; width: 14px; height: 14px;"></span> <?php esc_html_e( 'Rendering...', 'royal-batchmate' ); ?>
                                            </span>
                                        </div>
                                        <div style="position: relative; width: 100%; aspect-ratio: 1 / 1; max-width: 340px; margin: 0 auto; background: #0b192c; border-radius: 12px; overflow: hidden; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 15px rgba(0,0,0,0.15); border: 1px solid #cbd5e1;">
                                            <img src="<?php echo esc_url($default_preview_url); ?>" id="card-preview" class="BatchMate-card-preview-img" alt="<?php esc_attr_e( 'Card Preview', 'royal-batchmate' ); ?>" style="width: 100%; height: 100%; object-fit: contain; transition: opacity 0.2s ease;">
                                            <div id="card-preview-overlay" style="display: none; position: absolute; inset: 0; background: rgba(15, 23, 42, 0.75); align-items: center; justify-content: center; flex-direction: column; color: #38bdf8; font-weight: 600; font-size: 13px; gap: 8px;">
                                                <span class="dashicons dashicons-update BatchMate-spin" style="font-size: 26px; width: 26px; height: 26px; color: #fde047;"></span>
                                                <span><?php esc_html_e( 'Generating Canvas Preview...', 'royal-batchmate' ); ?></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Card Field / Subtitle Mapping -->
                                <div style="margin-top: 20px; padding-top: 16px; border-top: 1px dashed #cbd5e1;">
                                    <label for="birthday_card_badge_field" style="font-weight: 600; color: #1e293b; display: block; margin-bottom: 6px;">
                                        <span class="dashicons dashicons-tag" style="color: #2563eb;"></span> <?php esc_html_e( 'Card Subtitle / Badge Display', 'royal-batchmate' ); ?>
                                    </label>
                                    <p class="BatchMate-field-hint" style="margin-top:0; margin-bottom: 8px;">
                                        <?php echo wp_kses_post( __( 'Choose what text appears below the member name on the card. Select <strong>None / Hidden</strong> to omit any subtitle.', 'royal-batchmate' ) ); ?>
                                    </p>
                                    <div style="display: flex; gap: 12px; align-items: center; flex-wrap: wrap;">
                                        <select id="birthday_card_badge_field" name="birthday_card_badge_field" style="min-width: 260px; max-width: 360px;" onchange="document.getElementById('bday_custom_subtitle_wrap').style.display = (this.value === 'custom' ? 'block' : 'none');">
                                            <option value="none" <?php selected('none', $birthday_card_badge_field); ?>><?php esc_html_e( 'None / Hidden', 'royal-batchmate' ); ?></option>
                                            <option value="batch" <?php selected('batch', $birthday_card_badge_field); ?>><?php esc_html_e( 'Batch (e.g. Batch 1999)', 'royal-batchmate' ); ?></option>
                                            <option value="dept" <?php selected('dept', $birthday_card_badge_field); ?>><?php esc_html_e( 'Department / Group (e.g. Science)', 'royal-batchmate' ); ?></option>
                                            <option value="profession" <?php selected('profession', $birthday_card_badge_field); ?>><?php esc_html_e( 'Profession / Designation', 'royal-batchmate' ); ?></option>
                                            <option value="blood_group" <?php selected('blood_group', $birthday_card_badge_field); ?>><?php esc_html_e( 'Blood Group (e.g. Blood: B+)', 'royal-batchmate' ); ?></option>
                                            <option value="program" <?php selected('program', $birthday_card_badge_field); ?>><?php esc_html_e( 'Program', 'royal-batchmate' ); ?></option>
                                            <option value="custom" <?php selected('custom', $birthday_card_badge_field); ?>><?php esc_html_e( 'Custom Text', 'royal-batchmate' ); ?></option>
                                        </select>
                                        <div id="bday_custom_subtitle_wrap" style="flex: 1; min-width: 200px; <?php echo ($birthday_card_badge_field === 'custom') ? '' : 'display:none;'; ?>">
                                            <input type="text" id="birthday_card_custom_subtitle" name="birthday_card_custom_subtitle" value="<?php echo esc_attr($birthday_card_custom_subtitle); ?>" placeholder="<?php esc_attr_e( 'Custom badge text (e.g. SSC 99 Batchmate)', 'royal-batchmate' ); ?>" style="width: 100%;">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- 2. Birthday Email Channel -->
                            <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 16px; margin-bottom: 20px;">
                                <div class="BatchMate-toggle-row">
                                    <div class="BatchMate-toggle-info">
                                        <strong><span class="dashicons dashicons-email"></span> Send Birthday Email</strong>
                                        <p>Send a personalized HTML email with the attached birthday card.</p>
                                    </div>
                                    <label class="BatchMate-toggle-switch">
                                        <input type="checkbox" id="email_active" name="email_active" value="on" <?php checked('on', $email_active); ?>>
                                        <span class="BatchMate-toggle-slider"></span>
                                    </label>
                                </div>

                                <div id="birthday-email-fields" style="<?php echo ($email_active === 'on') ? '' : 'display:none;'; ?>; margin-top: 14px;">
                                    <div class="BatchMate-field-group BatchMate-field-full" style="margin-bottom: 12px;">
                                        <label for="birthday_email_subject">Email Subject</label>
                                        <input type="text" id="birthday_email_subject" name="birthday_email_subject"
                                               value="<?php echo esc_attr($birthday_email_subject); ?>"
                                               placeholder="Happy Birthday, {name}!">
                                    </div>
                                    <div class="BatchMate-field-group BatchMate-field-full">
                                        <label for="birthday_email_body">Email Body (HTML Supported)</label>
                                        <textarea id="birthday_email_body" name="birthday_email_body" rows="6" style="font-family:monospace;"><?php echo esc_textarea($birthday_email_body); ?></textarea>
                                        <div class="batchmate-tag-chips">
                                            <span style="font-size: 11px; color:#64748b;">Available Tags:</span>
                                            <span class="batchmate-tag-chip" data-target="birthday_email_body">{name}</span>
                                            <span class="batchmate-tag-chip" data-target="birthday_email_body">{org_name}</span>
                                            <span class="batchmate-tag-chip" data-target="birthday_email_body">{card_image}</span>
                                            <span class="batchmate-tag-chip" data-target="birthday_email_body">{card_url}</span>
                                            <span class="batchmate-tag-chip" data-target="birthday_email_body">{program}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- 3. Birthday SMS Channel (160 Chars & Timing Switch) -->
                            <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 16px; margin-bottom: 20px;">
                                <div class="BatchMate-toggle-row">
                                    <div class="BatchMate-toggle-info">
                                        <strong><span class="dashicons dashicons-smartphone"></span> Send Birthday SMS</strong>
                                        <p>Send an automated birthday greeting SMS to member's cell number.</p>
                                    </div>
                                    <label class="BatchMate-toggle-switch">
                                        <input type="checkbox" id="birthday_sms_active" name="birthday_sms_active" value="on" <?php checked('on', $birthday_sms_active); ?>>
                                        <span class="BatchMate-toggle-slider"></span>
                                    </label>
                                </div>

                                <div id="birthday-sms-fields" style="<?php echo ($birthday_sms_active === 'on') ? '' : 'display:none;'; ?>; margin-top: 14px;">
                                    <div class="BatchMate-field-group" style="max-width: 380px; margin-bottom: 12px;">
                                        <label for="birthday_sms_time">
                                            <span class="dashicons dashicons-clock"></span> SMS Timing / When to Send
                                        </label>
                                        <select id="birthday_sms_time" name="birthday_sms_time">
                                            <option value="morning_9am" <?php selected('morning_9am', $birthday_sms_time); ?>>Morning 09:00 AM (Scheduled via Alpha SMS)</option>
                                            <option value="morning_10am" <?php selected('morning_10am', $birthday_sms_time); ?>>Morning 10:00 AM (Scheduled via Alpha SMS)</option>
                                            <option value="instant" <?php selected('instant', $birthday_sms_time); ?>>Instant Midnight 00:01 AM (With Daily Cron)</option>
                                        </select>
                                    </div>

                                    <div class="BatchMate-field-group BatchMate-field-full">
                                        <label for="birthday_sms_template">
                                            160-Character SMS Template
                                        </label>
                                        <textarea id="birthday_sms_template" name="birthday_sms_template" rows="3" class="batchmate-sms-textarea" maxlength="320"><?php echo esc_textarea($birthday_sms_template); ?></textarea>
                                        <div class="batchmate-char-counter" id="birthday-sms-counter">
                                            <span class="count-info">0 / 160 characters (1 SMS)</span>
                                            <span class="limit-info">Standard English: 160 chars/SMS | Unicode: 70 chars/SMS</span>
                                        </div>
                                        <div class="batchmate-tag-chips">
                                            <span style="font-size: 11px; color:#64748b;">Available Tags:</span>
                                            <span class="batchmate-tag-chip" data-target="birthday_sms_template">{name}</span>
                                            <span class="batchmate-tag-chip" data-target="birthday_sms_template">{org_name}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- 4. Facebook & Webhook Notification -->
                            <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 16px;">
                                <div class="BatchMate-field-group" style="max-width:340px;">
                                    <label for="fb_post_method">
                                        <span class="dashicons dashicons-facebook"></span> Facebook Post Method
                                    </label>
                                    <select id="fb_post_method" name="fb_post_method">
                                        <option value="none"         <?php selected('none',         $fb_post_method); ?>>No Facebook Post</option>
                                        <option value="access_token" <?php selected('access_token', $fb_post_method); ?>>Access Token (Direct API)</option>
                                        <option value="make_com"     <?php selected('make_com',     $fb_post_method); ?>>Webhooks (Make.com / Automation)</option>
                                    </select>
                                </div>

                                <!-- FB Access Token -->
                                <div id="fb-credentials-wrapper" class="BatchMate-credentials-box BatchMate-credentials-fb" style="display:none; margin-top:14px;">
                                    <h4><span class="dashicons dashicons-facebook-alt"></span> Facebook API Settings</h4>
                                    <div class="BatchMate-field-grid">
                                        <div class="BatchMate-field-group">
                                            <label for="fb_page_id">Page ID</label>
                                            <input type="text" id="fb_page_id" name="fb_page_id"
                                                   value="<?php echo esc_attr($fb_page_id); ?>"
                                                   placeholder="Your Facebook Page ID">
                                        </div>
                                        <div class="BatchMate-field-group BatchMate-field-full">
                                            <label for="fb_access_token">Long-Lived Page Access Token</label>
                                            <textarea id="fb_access_token" name="fb_access_token" rows="3"
                                                      placeholder="Paste your access token here..."><?php echo esc_textarea($fb_access_token); ?></textarea>
                                        </div>
                                    </div>
                                    <div style="margin-top:12px; display:flex; align-items:center; gap:12px;">
                                        <button type="button" id="test-fb-connection" class="BatchMate-test-btn">
                                            <span class="dashicons dashicons-networking"></span> Test Connection
                                        </button>
                                        <span id="fb-test-result" class="BatchMate-test-result"></span>
                                    </div>
                                </div>

                                <!-- Legacy make.com Webhook box (if selected) -->
                                <div id="make-credentials-wrapper" class="BatchMate-credentials-box BatchMate-credentials-make" style="display:none; margin-top:14px;">
                                    <h4>⚡ make.com / Webhook Integration</h4>
                                    <div class="BatchMate-field-group">
                                        <label for="make_webhook_url">Webhook URL</label>
                                        <input type="url" id="make_webhook_url" name="make_webhook_url"
                                               value="<?php echo esc_attr($make_webhook_url); ?>"
                                               placeholder="https://hook.us1.make.com/...">
                                        <span class="BatchMate-field-hint">Tip: You can also manage multiple Webhook URLs in the "Webhook Connections" card above!</span>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>

                <!-- SECTION 4: Event Card & Registration Automation -->
                <div class="BatchMate-settings-card">
                    <div class="BatchMate-card-head">
                        <span class="dashicons dashicons-calendar-alt"></span>
                        <h3>Event Card & Registration Automation</h3>
                    </div>
                    <div class="BatchMate-card-body">
                        <!-- Event Master Toggle -->
                        <div class="BatchMate-toggle-row">
                            <div class="BatchMate-toggle-info">
                                <strong>Enable Event Registration Automation</strong>
                                <p>Automatically generate event admission cards and dispatch Email, SMS, and Webhooks upon registration approval.</p>
                            </div>
                            <label class="BatchMate-toggle-switch">
                                <input type="checkbox" id="event_automation_active" name="event_automation_active" value="on" <?php checked('on', $event_automation_active); ?>>
                                <span class="BatchMate-toggle-slider"></span>
                            </label>
                        </div>

                        <div id="event-inner-settings" style="<?php echo ($event_automation_active === 'on') ? '' : 'display:none;'; ?>; margin-top: 16px;">
                            
                            <!-- Event Email Channel -->
                            <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 16px; margin-bottom: 16px;">
                                <div class="BatchMate-toggle-row">
                                    <div class="BatchMate-toggle-info">
                                        <strong><span class="dashicons dashicons-email"></span> Send Event Confirmation Email</strong>
                                        <p>Send admission card attachment and registration confirmation email to attendee.</p>
                                    </div>
                                    <label class="BatchMate-toggle-switch">
                                        <input type="checkbox" id="event_email_active" name="event_email_active" value="on" <?php checked('on', $event_email_active); ?>>
                                        <span class="BatchMate-toggle-slider"></span>
                                    </label>
                                </div>
                            </div>

                            <!-- Event SMS Channel -->
                            <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 16px; margin-bottom: 16px;">
                                <div class="BatchMate-toggle-row">
                                    <div class="BatchMate-toggle-info">
                                        <strong><span class="dashicons dashicons-smartphone"></span> Send Event Confirmation SMS</strong>
                                        <p>Instant SMS dispatch upon event approval with serial number and event name.</p>
                                    </div>
                                    <label class="BatchMate-toggle-switch">
                                        <input type="checkbox" id="event_sms_active" name="event_sms_active" value="on" <?php checked('on', $event_sms_active); ?>>
                                        <span class="BatchMate-toggle-slider"></span>
                                    </label>
                                </div>

                                <div id="event-sms-fields" style="<?php echo ($event_sms_active === 'on') ? '' : 'display:none;'; ?>; margin-top: 14px;">
                                    <div class="BatchMate-field-group BatchMate-field-full">
                                        <label for="event_sms_template">
                                            160-Character Event SMS Template
                                        </label>
                                        <textarea id="event_sms_template" name="event_sms_template" rows="3" class="batchmate-sms-textarea" maxlength="320"><?php echo esc_textarea($event_sms_template); ?></textarea>
                                        <div class="batchmate-char-counter" id="event-sms-counter">
                                            <span class="count-info">0 / 160 characters (1 SMS)</span>
                                            <span class="limit-info">Standard English: 160 chars/SMS | Unicode: 70 chars/SMS</span>
                                        </div>
                                        <div class="batchmate-tag-chips">
                                            <span style="font-size: 11px; color:#64748b;">Available Tags:</span>
                                            <span class="batchmate-tag-chip" data-target="event_sms_template">{name}</span>
                                            <span class="batchmate-tag-chip" data-target="event_sms_template">{event_name}</span>
                                            <span class="batchmate-tag-chip" data-target="event_sms_template">{serial_no}</span>
                                            <span class="batchmate-tag-chip" data-target="event_sms_template">{org_name}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Event Webhook Toggle -->
                            <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 16px;">
                                <div class="BatchMate-toggle-row">
                                    <div class="BatchMate-toggle-info">
                                        <strong><span class="dashicons dashicons-networking"></span> Dispatch Event Card Payload to Webhooks</strong>
                                        <p>Send event attendee name, program, batch, card URL, and registration message to configured Webhook URLs.</p>
                                    </div>
                                    <label class="BatchMate-toggle-switch">
                                        <input type="checkbox" id="event_webhook_active" name="event_webhook_active" value="on" <?php checked('on', $event_webhook_active); ?>>
                                        <span class="BatchMate-toggle-slider"></span>
                                    </label>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>

                <!-- SECTION 5: Member Registration Automation -->
                <div class="BatchMate-settings-card">
                    <div class="BatchMate-card-head">
                        <span class="dashicons dashicons-id-alt"></span>
                        <h3>Member Registration Automation</h3>
                    </div>
                    <div class="BatchMate-card-body">
                        <!-- Member Reg Master Toggle -->
                        <div class="BatchMate-toggle-row">
                            <div class="BatchMate-toggle-info">
                                <strong>Enable Member Registration Automation</strong>
                                <p>Automate welcome SMS alerts and webhook notifications when a new member application is approved.</p>
                            </div>
                            <label class="BatchMate-toggle-switch">
                                <input type="checkbox" id="member_reg_automation_active" name="member_reg_automation_active" value="on" <?php checked('on', $member_reg_automation_active); ?>>
                                <span class="BatchMate-toggle-slider"></span>
                            </label>
                        </div>

                        <div id="member-reg-inner-settings" style="<?php echo ($member_reg_automation_active === 'on') ? '' : 'display:none;'; ?>; margin-top: 16px;">
                            
                            <!-- Member Welcome SMS -->
                            <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 16px; margin-bottom: 16px;">
                                <div class="BatchMate-toggle-row">
                                    <div class="BatchMate-toggle-info">
                                        <strong><span class="dashicons dashicons-smartphone"></span> Send Member Welcome SMS on Approval</strong>
                                        <p>Sends instant SMS to applicant containing Member ID once admin approves application.</p>
                                    </div>
                                    <label class="BatchMate-toggle-switch">
                                        <input type="checkbox" id="member_reg_sms_active" name="member_reg_sms_active" value="on" <?php checked('on', $member_reg_sms_active); ?>>
                                        <span class="BatchMate-toggle-slider"></span>
                                    </label>
                                </div>

                                <div id="member-reg-sms-fields" style="<?php echo ($member_reg_sms_active === 'on') ? '' : 'display:none;'; ?>; margin-top: 14px;">
                                    <div class="BatchMate-field-group BatchMate-field-full">
                                        <label for="member_reg_sms_template">
                                            160-Character Welcome SMS Template
                                        </label>
                                        <textarea id="member_reg_sms_template" name="member_reg_sms_template" rows="3" class="batchmate-sms-textarea" maxlength="320"><?php echo esc_textarea($member_reg_sms_template); ?></textarea>
                                        <div class="batchmate-char-counter" id="member-reg-sms-counter">
                                            <span class="count-info">0 / 160 characters (1 SMS)</span>
                                            <span class="limit-info">Standard English: 160 chars/SMS | Unicode: 70 chars/SMS</span>
                                        </div>
                                        <div class="batchmate-tag-chips">
                                            <span style="font-size: 11px; color:#64748b;">Available Tags:</span>
                                            <span class="batchmate-tag-chip" data-target="member_reg_sms_template">{name}</span>
                                            <span class="batchmate-tag-chip" data-target="member_reg_sms_template">{member_id}</span>
                                            <span class="batchmate-tag-chip" data-target="member_reg_sms_template">{org_name}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Member Reg Webhook -->
                            <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 16px;">
                                <div class="BatchMate-toggle-row">
                                    <div class="BatchMate-toggle-info">
                                        <strong><span class="dashicons dashicons-networking"></span> Dispatch Webhook on Member Approval</strong>
                                        <p>Send member details, assigned member number, profile URL to configured Webhook URLs.</p>
                                    </div>
                                    <label class="BatchMate-toggle-switch">
                                        <input type="checkbox" id="member_reg_webhook_active" name="member_reg_webhook_active" value="on" <?php checked('on', $member_reg_webhook_active); ?>>
                                        <span class="BatchMate-toggle-slider"></span>
                                    </label>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>

            </div><!-- /#automation-inner-settings -->





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
        </div><!-- /#tab-automation -->

<!-- 
             TAB: Breadcrumb
        -->