<div class="BatchMate-ev-tab-content <?php echo $req_tab === 'tab-registered' ? 'active' : ''; ?>" id="tab-registered" style="<?php echo $req_tab === 'tab-registered' ? 'display:block;' : 'display:none;'; ?>">
        <div class="BatchMate-ev-toolbar">
            <div class="BatchMate-ev-toolbar-left">
                <label class="BatchMate-ev-toolbar-label">Filter by Event:</label>
                <select id="filter-registered-event" class="BatchMate-ev-select">
                    <?php foreach ( $events_for_filter as $ev ) : ?>
                        <option value="<?php echo esc_attr( $ev->id ); ?>" <?php selected( $ev->is_active, 1 ); ?>><?php echo esc_html( $ev->event_name ); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="BatchMate-ev-toolbar-right" style="display:flex;align-items:center;gap:12px;">
                <!-- Live summary badge -->
                <span id="reg-collection-badge" style="display:none;" class="BatchMate-ev-collection-badge"></span>
                <button id="btn-print-registered" class="BatchMate-ev-btn BatchMate-ev-btn--outline">
                    <span class="dashicons dashicons-printer"></span> Print List with Amount
                </button>
                <button id="btn-print-registered-noamount" class="BatchMate-ev-btn BatchMate-ev-btn--outline">
                    <span class="dashicons dashicons-printer"></span> Print List
                </button>
            </div>
        </div>

        <div class="BatchMate-ev-table-wrap">
            <table id="event-registered-table" class="display wp-list-table widefat striped" style="width:100%">
                <thead>
                    <tr>
                        <th style="width:38px; text-align:center;">#</th>
                        <th style="width:120px; min-width:115px; white-space:nowrap;">Mem. ID</th>
                        <th>Name</th>
                        <th style="width:48px; min-width:48px; max-width:56px; text-align:center;">Photo</th>
                        <th>District</th>
                        
                        
                        <th>Contact</th>
                        <th>Amount (BDT)</th>
                        <th>Guests</th>
                        <th>T-shirt</th>
                        <th>Event Name</th>
                        <th style="width:95px; text-align:center;">Actions</th>
                    </tr>
                </thead>
                <tbody></tbody>
                <tfoot>
                    <tr>
                        <th colspan="8" style="text-align:right">Total Collection:</th>
                        <th id="reg-table-foot-total" colspan="4" style="text-align:left">&#2547; 0.00</th>
                    </tr>
                </tfoot>
            </table>
        </div>

        <!-- -- District-wise Registration Summary -- -->
        <div id="dept-summary-wrap" class="BatchMate-dept-summary-wrap" style="display:none; margin-top:28px;">
            <div class="BatchMate-dept-summary-header">
                <div class="BatchMate-dept-summary-title">
                    <span class="dashicons dashicons-chart-bar"></span>
                    <span id="dept-summary-event-label">District-wise Registration Summary</span>
                </div>
                <button id="btn-print-dept-summary" class="BatchMate-ev-btn BatchMate-ev-btn--outline">
                    <span class="dashicons dashicons-printer"></span> Print District List
                </button>
            </div>
            <div class="BatchMate-ev-table-wrap">
                <table id="dept-summary-table" class="wp-list-table widefat striped" style="width:100%">
                    <thead>
                        <tr>
                            <th style="width:40px; text-align:center;">#</th>
                            <th>District Name</th>
                            <th style="text-align:center; width:140px;">Registered</th>
                            <th style="text-align:center; width:110px;">Guest(s)</th>
                            <th style="text-align:center; width:110px;">Total</th>
                            <th style="width:200px;">Visual Bar</th>
                        </tr>
                    </thead>
                    <tbody id="dept-summary-tbody">
                        <tr><td colspan="6" style="text-align:center;color:#aaa;padding:20px;">Loading...</td></tr>
                    </tbody>
                    <tfoot>
                        <tr>
                            <th colspan="2" style="text-align:right;">Grand Total:</th>
                            <th id="dept-summary-total" style="text-align:center; font-size:16px; color:#0a7242;">0</th>
                            <th id="dept-summary-guest-total" style="text-align:center; font-size:16px; color:#2563eb;">0</th>
                            <th id="dept-summary-grand-total" style="text-align:center; font-size:16px; color:#b91c1c; font-weight:800;">0</th>
                            <th></th>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <!-- Bottom Summaries: T-Shirt / শাড়ি (Left) & Payment Method (Right) -->
            <div class="BatchMate-bottom-summaries" style="display:flex;gap:24px;flex-wrap:wrap;align-items:flex-start;margin-top:24px;">

                <!-- Left Card: T-Shirt / Saree Size Summary -->
                <div id="tshirt-summary-wrap" style="flex:1;min-width:300px;max-width:480px;background:#fff;border:1px solid #e2e8f0;border-radius:12px;overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,0.05);display:none;">
                    <div style="background:linear-gradient(135deg,#f59e0b,#d97706);padding:12px 20px;display:flex;align-items:center;justify-content:space-between;gap:8px;">
                        <div style="display:flex;align-items:center;gap:8px;">
                            <span style="font-size:18px;">📸</span>
                            <span id="tshirt-summary-title" style="font-size:14px;font-weight:700;color:#fff;">T-Shirt / শাড়ি Summary</span>
                        </div>
                        <button id="btn-print-tshirt-summary" class="BatchMate-ev-btn" style="background:#fff;color:#d97706;border:none;padding:4px 12px;border-radius:6px;font-size:12px;font-weight:700;cursor:pointer;">
                            <span class="dashicons dashicons-printer" style="font-size:14px;vertical-align:middle;"></span> Print
                        </button>
                    </div>
                    <div style="padding:16px 20px;">
                        <table style="width:100%;border-collapse:collapse;font-size:13px;">
                            <thead>
                                <tr style="background:#fef3c7;">
                                    <th style="padding:8px 12px;text-align:left;border-bottom:1px solid #fde68a;">Size / Item</th>
                                    <th style="padding:8px 12px;text-align:center;border-bottom:1px solid #fde68a;">Count</th>
                                </tr>
                            </thead>
                            <tbody id="tshirt-summary-tbody"></tbody>
                        </table>
                    </div>
                </div>

                <!-- Right Card: Payment Method Summary (ডানে থাকবে) -->
                <div id="payment-method-wrap" style="flex:1.4;min-width:360px;background:#fff;border:1px solid #e2e8f0;border-radius:12px;overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,0.05);display:none;">
                    <div style="background:linear-gradient(135deg,#0284c7,#0369a1);padding:12px 20px;display:flex;align-items:center;justify-content:space-between;gap:8px;">
                        <div style="display:flex;align-items:center;gap:8px;">
                            <span style="font-size:18px;">💳</span>
                            <span style="font-size:14px;font-weight:700;color:#fff;">Payment Method Breakdown (পেমেন্ট সামারি)</span>
                        </div>
                        <button id="btn-print-payment-summary" class="BatchMate-ev-btn" style="background:#fff;color:#0284c7;border:none;padding:4px 12px;border-radius:6px;font-size:12px;font-weight:700;cursor:pointer;">
                            <span class="dashicons dashicons-printer" style="font-size:14px;vertical-align:middle;"></span> Print
                        </button>
                    </div>
                    <div style="padding:16px 20px;">
                        <table style="width:100%;border-collapse:collapse;font-size:13px;">
                            <thead>
                                <tr style="background:#e0f2fe;">
                                    <th style="padding:8px 12px;text-align:left;border-bottom:1px solid #bae6fd;">Payment Method</th>
                                    <th style="padding:8px 12px;text-align:center;border-bottom:1px solid #bae6fd;">Members</th>
                                    <th style="padding:8px 12px;text-align:right;border-bottom:1px solid #bae6fd;">Amount Received (৳)</th>
                                </tr>
                            </thead>
                            <tbody id="payment-method-tbody"></tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>
    </div>