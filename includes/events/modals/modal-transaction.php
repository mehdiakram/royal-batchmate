<div id="BatchMate-transaction-modal-overlay"></div>
<div id="BatchMate-transaction-modal" role="dialog" aria-labelledby="BatchMate-transaction-modal-title">
    <div class="BatchMate-emodal-header">
        <div>
            <h2 id="BatchMate-transaction-modal-title">Add Transaction</h2>
            <p style="margin:4px 0 0;font-size:13px;opacity:.8;">Record a sponsorship or expense</p>
        </div>
        <button class="BatchMate-emodal-close" id="BatchMate-transaction-modal-close" aria-label="Close">&times;</button>
    </div>
    <div class="BatchMate-emodal-body">
        <form id="BatchMate-transaction-form" novalidate>
            <input type="hidden" id="trans_id" value="0">
            <input type="hidden" id="trans_event_id" value="">

            <div class="BatchMate-efield-group">
                <label for="trans_type">Transaction Type <span class="required">*</span></label>
                <select id="trans_type" class="BatchMate-ev-select" required>
                    <option value="expense">Expense</option>
                    <option value="sponsor">Sponsorship (Income)</option>
                </select>
            </div>

            <div class="BatchMate-efield-group">
                <label for="trans_date">Date <span class="required">*</span></label>
                <input type="date" id="trans_date" required>
            </div>

            <div class="BatchMate-efield-group">
                <label for="trans_purpose">Purpose / Sponsor Name <span class="required">*</span></label>
                <input type="text" id="trans_purpose" placeholder="e.g. Venue Booking or Rahim Group" required>
            </div>

            <div class="BatchMate-efield-group">
                <label for="trans_amount">Amount (&#2547;) <span class="required">*</span></label>
                <input type="number" id="trans_amount" placeholder="e.g. 5000" min="1" step="0.01" required>
            </div>
            
        </form>
    </div>
    <div class="BatchMate-emodal-footer">
        <button class="BatchMate-ev-btn BatchMate-ev-btn--ghost BatchMate-emodal-close" id="BatchMate-transaction-modal-cancel">Cancel</button>
        <button class="BatchMate-ev-btn BatchMate-ev-btn--primary" id="BatchMate-transaction-modal-save">
            <span class="dashicons dashicons-saved"></span> Save Transaction
        </button>
    </div>
</div>