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
                                  placeholder="<?php echo esc_attr( "bKash 01552333272 Royal\nbKash 01973245450 Mehdi" ); ?>"
                                  style="width:100%;font-family:monospace;font-size:13px;"><?php echo esc_textarea($reg_payment_methods); ?></textarea>
                        <p class="BatchMate-field-hint"><?php printf( esc_html__( 'Enter payment methods, mobile numbers, or account info one per line (e.g. %s). These will appear as choices in the Payment Method dropdown in both Registration and Event forms (unless an event specifies its own custom methods).', 'royal-batchmate' ), '<code>bKash 01552333272 Royal</code>' ); ?></p>
                    </div>
                </div>
            </div>

</div><!-- /#tab-registration -->