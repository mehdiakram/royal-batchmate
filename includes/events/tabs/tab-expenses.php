<div class="BatchMate-ev-tab-content <?php echo $req_tab === 'tab-expenses' ? 'active' : ''; ?>" id="tab-expenses" style="<?php echo $req_tab === 'tab-expenses' ? 'display:block;' : 'display:none;'; ?>">
        
        <!-- Print Header (Hidden on screen) -->
        <div class="BatchMate-print-only BatchMate-print-header">
            <h2><?php echo batchmate_org_label( 'short' ); ?></h2>
            <h3 id="print-event-title">Event Financial Report</h3>
        </div>

        <div class="BatchMate-ev-toolbar BatchMate-no-print">
            <div class="BatchMate-ev-toolbar-left">
                <label class="BatchMate-ev-toolbar-label">Filter by Event:</label>
                <select id="filter-expenses-event" class="BatchMate-ev-select">
                    <option value="">-- Select Event --</option>
                    <?php foreach ( $events_for_filter as $ev ) : ?>
                        <option value="<?php echo esc_attr( $ev->id ); ?>" <?php selected( $ev->is_active, 1 ); ?>><?php echo esc_html( $ev->event_name ); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="BatchMate-ev-toolbar-right" style="display:flex;align-items:center;gap:12px;">
                <button id="btn-print-report" class="BatchMate-ev-btn BatchMate-ev-btn--outline" style="display:none;">
                    <span class="dashicons dashicons-printer"></span> Print Report
                </button>
                <button id="btn-new-transaction" class="BatchMate-ev-btn BatchMate-ev-btn--primary" style="display:none;">
                    <span class="dashicons dashicons-plus-alt2"></span> Add Transaction
                </button>
            </div>
        </div>

        <!-- Report Cards -->
        <div id="expenses-report-cards" class="BatchMate-ev-cards" style="display:none;">
            <div class="BatchMate-ev-card BatchMate-ev-card--blue">
                <div class="BatchMate-ev-card-body">
                    <div class="BatchMate-ev-card-number" id="rep-collection">0.00</div>
                    <div class="BatchMate-ev-card-label" id="rep-collection-label">Total Collection</div>
                </div>
            </div>
            <div class="BatchMate-ev-card BatchMate-ev-card--green">
                <div class="BatchMate-ev-card-body">
                    <div class="BatchMate-ev-card-number" id="rep-sponsorship">0.00</div>
                    <div class="BatchMate-ev-card-label">Total Sponsorship</div>
                </div>
            </div>
            <div class="BatchMate-ev-card BatchMate-ev-card--orange">
                <div class="BatchMate-ev-card-body">
                    <div class="BatchMate-ev-card-number" id="rep-expense">0.00</div>
                    <div class="BatchMate-ev-card-label">Total Expense</div>
                </div>
            </div>
            <div class="BatchMate-ev-card" id="rep-balance-card" style="border-left: 5px solid #64748b;">
                <div class="BatchMate-ev-card-body">
                    <div class="BatchMate-ev-card-number" id="rep-balance" style="color:#64748b;">0.00</div>
                    <div class="BatchMate-ev-card-label">Net Balance</div>
                </div>
            </div>
        </div>

        <div id="expenses-table-wrap" class="BatchMate-ev-table-wrap" style="display:none;">
            <h3 class="BatchMate-print-only" style="margin-top:20px;margin-bottom:10px;font-size:16px;">Transaction Details</h3>
            <table id="event-transactions-table" class="display wp-list-table widefat striped" style="width:100%">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Date</th>
                        <th>Type</th>
                        <th>Purpose / Sponsor Name</th>
                        <th>Amount (BDT)</th>
                        <th class="BatchMate-no-print" style="width:80px">Actions</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
        
        <div id="expenses-no-event" style="padding:40px 20px;text-align:center;color:#64748b;">
            <span class="dashicons dashicons-calendar-alt" style="font-size:40px;width:40px;height:40px;opacity:0.3;margin-bottom:10px;"></span>
            <p style="font-size:15px;">Please select an event to view its financial report and transactions.</p>
        </div>

        <!-- Print Signatures (Hidden on screen) -->
        <div class="BatchMate-print-only BatchMate-print-signatures">
            <div class="sig-box">
                <div class="sig-line"></div>
                <p>Prepared By</p>
            </div>
            <div class="sig-box">
                <div class="sig-line"></div>
                <p>Checked By</p>
            </div>
            <div class="sig-box">
                <div class="sig-line"></div>
                <p>Approved By</p>
            </div>
        </div>

    </div>