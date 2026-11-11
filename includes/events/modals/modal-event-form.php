<div id="BatchMate-event-modal-overlay"></div>
<div id="BatchMate-event-modal" role="dialog" aria-labelledby="BatchMate-event-modal-title">
    <div class="BatchMate-emodal-header">
        <div>
            <h2 id="BatchMate-event-modal-title">New Event</h2>
            <p style="margin:4px 0 0;font-size:13px;opacity:.8;">Fill in the details below</p>
        </div>
        <button class="BatchMate-emodal-close" id="BatchMate-event-modal-close" aria-label="Close">&times;</button>
    </div>
    <div class="BatchMate-emodal-body">
        <form id="BatchMate-event-form" novalidate>
            <input type="hidden" id="modal_event_id" value="0">
            <input type="hidden" id="modal_card_template_url" value="">
            <input type="hidden" id="modal_card_zones_json" value="">

            <div class="BatchMate-efield-group">
                <label for="modal_event_name">Event Name <span class="required">*</span></label>
                <input type="text" id="modal_event_name" placeholder="e.g. Annual Reunion 2025" required>
            </div>

            <div class="BatchMate-efield-group">
                <label for="modal_event_date">Event Date <span class="required">*</span></label>
                <input type="date" id="modal_event_date" required>
            </div>

            <div class="BatchMate-efield-group">
                <label for="modal_event_location">Location <span style="font-size:12px;color:#888;font-weight:400;">(optional)</span></label>
                <input type="text" id="modal_event_location" placeholder="e.g. BatchMate Campus, Dhaka">
            </div>

            <div class="BatchMate-efield-group">
                <label for="modal_event_registered_url">Registered List URL <span style="font-size:12px;color:#888;font-weight:400;">(optional)</span></label>
                <input type="url" id="modal_event_registered_url" placeholder="https://example.com/registered-list">
                <p style="margin:4px 0 0;font-size:11px;color:#888;">If provided, a button to this link will appear in the confirmation email.</p>
            </div>

            <div class="BatchMate-efield-group">
                <label for="modal_event_fee">Base Member Fee (&#2547;)</label>
                <input type="number" id="modal_event_fee" placeholder="e.g. 500" min="0" step="0.01">
                <p style="margin:4px 0 0;font-size:11px;color:#888;">Fee for the member themselves. Leave 0 if free or covered by guests.</p>
            </div>

            <!-- Guest Options -->
            <div class="BatchMate-efield-group">
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:8px;">
                    <label style="margin:0;">Guest Options <span style="font-size:12px;color:#888;font-weight:400;">(optional)</span></label>
                    <button type="button" id="add-event-option" class="BatchMate-ev-btn BatchMate-ev-btn--ghost" style="padding:4px 10px;font-size:12px;">
                        <span class="dashicons dashicons-plus-alt2" style="font-size:14px;width:14px;height:14px;"></span> Add Option
                    </button>
                </div>
                <div id="event-options-list">
                    <!-- dynamic rows injected here -->
                </div>
                <p style="margin:6px 0 0;font-size:11px;color:#aaa;">e.g. Adult &#2547;300, Child &#2547;150. Max Qty = 0 means unlimited.</p>
            </div>


            <div class="BatchMate-efield-group BatchMate-toggle-group" style="margin-top:20px;">
                <div class="toggle-row">
                    <div>
                        <label style="margin-bottom:2px">Active (Accepting Registrations)</label>
                        <p style="margin:0;font-size:12px;color:#888;">Only one event can be active at a time.</p>
                    </div>
                    <label class="BatchMate-toggle-switch">
                        <input type="checkbox" id="modal_event_active">
                        <span class="BatchMate-toggle-slider"></span>
                    </label>
                </div>
            </div>

            <div class="BatchMate-efield-group" style="margin-top:16px;">
                <label for="modal_event_allow_non_members" style="display:block;margin-bottom:4px;font-weight:600;color:#1e293b;">Registration Permissions (Non-Members)</label>
                <select id="modal_event_allow_non_members" style="width:100%;height:38px;border-radius:6px;border:1px solid #ccd0d4;padding:0 10px;font-size:13px;background:#fff;color:#1e293b;">
                    <option value="0"><?php esc_html_e( '1. Members Only (Search Required)', 'royal-batchmate' ); ?></option>
                    <option value="1"><?php esc_html_e( '2. Non-Members Allowed (as Guests)', 'royal-batchmate' ); ?></option>
                    <option value="2"><?php esc_html_e( '3. Direct Member on Approval (Search + Switch to Form)', 'royal-batchmate' ); ?></option>
                    <option value="3"><?php esc_html_e( '4. Direct Form Only (Open Form Directly — No Search)', 'royal-batchmate' ); ?></option>
                </select>
                <p style="margin:4px 0 0;font-size:12px;color:#888;"><?php esc_html_e( 'Option 3 & 4 automatically create full member accounts on approval. Option 4 opens the registration form directly without any search box.', 'royal-batchmate' ); ?></p>
            </div>

            <div class="BatchMate-efield-group" id="wrap_event_direct_reg_text" style="margin-top:12px;">
                <label for="modal_event_direct_reg_text" style="display:block;margin-bottom:4px;font-weight:600;color:#1e293b;">Direct Registration Form Title / Button Text <span style="font-size:12px;color:#888;font-weight:400;">(optional)</span></label>
                <input type="text" id="modal_event_direct_reg_text" placeholder="<?php echo esc_attr__( 'e.g. Register for Membership & Event', 'royal-batchmate' ); ?>" style="width:100%;height:38px;border-radius:6px;border:1px solid #ccd0d4;padding:0 10px;font-size:13px;background:#fff;color:#1e293b;">
                <p style="margin:4px 0 0;font-size:11px;color:#888;">Custom text for the form title or button (Option 3 &amp; 4). Leave empty for default text.</p>
            </div>

            <div class="BatchMate-efield-group BatchMate-toggle-group" style="margin-top:16px;">
                <div class="toggle-row">
                    <div>
                        <label style="margin-bottom:2px">Has ID Card?</label>
                        <p style="margin:0;font-size:12px;color:#888;">If yes, members will be issued an ID card for this event.</p>
                    </div>
                    <label class="BatchMate-toggle-switch">
                        <input type="checkbox" id="modal_event_has_id_card">
                        <span class="BatchMate-toggle-slider"></span>
                    </label>
                </div>
            </div>

            <div class="BatchMate-efield-group BatchMate-toggle-group" style="margin-top:16px;">
                <div class="toggle-row">
                    <div>
                        <label style="margin-bottom:2px">Auto Email ID Card</label>
                        <p style="margin:0;font-size:12px;color:#888;">Automatically send the ID card via email when their application is approved.</p>
                    </div>
                    <label class="BatchMate-toggle-switch">
                        <input type="checkbox" id="modal_event_auto_send_card">
                        <span class="BatchMate-toggle-slider"></span>
                    </label>
                </div>
            </div>

            <div class="BatchMate-efield-group" style="margin-top:16px;">
                <label for="modal_event_cc_email">CC Email <span style="font-size:12px;color:#888;font-weight:400;">(optional)</span></label>
                <input type="email" id="modal_event_cc_email" placeholder="e.g. office@example.org">
                <p style="margin:4px 0 0;font-size:11px;color:#888;">A copy of all event-related emails will be sent to this address.</p>
            </div>

            <div class="BatchMate-efield-group" style="margin-top:16px;">
                <label for="modal_event_payment_methods" style="display:block;margin-bottom:4px;font-weight:600;color:#1e293b;">Payment Methods / Accounts <span style="font-size:12px;color:#888;font-weight:400;">(optional — one per line)</span></label>
                <textarea id="modal_event_payment_methods" rows="3" style="width:100%;border-radius:6px;border:1px solid #ccd0d4;padding:8px 10px;font-size:13px;background:#fff;color:#1e293b;font-family:monospace;" placeholder="bKash 01552333272 Royal&#10;bKash 01973245450 Mehdi"></textarea>
                <p style="margin:4px 0 0;font-size:11px;color:#888;">Custom accounts for this event. Leave empty to use global methods from Settings &rarr; Registration.</p>
            </div>

            <div class="BatchMate-efield-group BatchMate-toggle-group" style="margin-top:16px;">
                <div class="toggle-row">
                    <div>
                        <label style="margin-bottom:2px">&#128247; T-Shirt Distribution</label>
                        <p style="margin:0;font-size:12px;color:#888;">If enabled, registrants will be asked to choose a T-Shirt size (S, M, X, L, XL, XXL, XXXL).</p>
                    </div>
                    <label class="BatchMate-toggle-switch">
                        <input type="checkbox" id="modal_event_has_tshirt">
                        <span class="BatchMate-toggle-slider"></span>
                    </label>
                </div>
            </div>
            <div class="BatchMate-efield-group BatchMate-toggle-group" id="modal_event_has_saree_wrap" style="margin-top:16px;">
                <div class="toggle-row">
                    <div>
                        <label style="margin-bottom:2px">&#128088; <?php esc_html_e( 'Saree Option for Females', 'royal-batchmate' ); ?></label>
                        <p style="margin:0;font-size:12px;color:#888;"><?php esc_html_e( 'If enabled, female registrants can choose either a Saree or a T-Shirt.', 'royal-batchmate' ); ?></p>
                    </div>
                    <label class="BatchMate-toggle-switch">
                        <input type="checkbox" id="modal_event_has_saree">
                        <span class="BatchMate-toggle-slider"></span>
                    </label>
                </div>
            </div>
        </form>
    </div>
    <div class="BatchMate-emodal-footer">
        <button class="BatchMate-ev-btn BatchMate-ev-btn--ghost BatchMate-emodal-close" id="BatchMate-event-modal-cancel">Cancel</button>
        <button class="BatchMate-ev-btn BatchMate-ev-btn--primary" id="BatchMate-event-modal-save">
            <span class="dashicons dashicons-saved"></span> Save Event
        </button>
    </div>
</div>