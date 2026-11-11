<div id="BatchMate-editapp-overlay"></div>
<div id="BatchMate-editapp-modal" role="dialog">
    <div class="BatchMate-emodal-header" style="background:linear-gradient(135deg,#1a3a6b,#2271b1);">
        <div>
            <h2 style="color:#fff;margin:0;">Edit Application</h2>
            <p style="margin:4px 0 0;font-size:13px;opacity:.8;color:#fff;">Update payment info &amp; non-member details</p>
        </div>
        <button class="BatchMate-emodal-close" id="BatchMate-editapp-close" aria-label="Close" style="color:#fff;">&times;</button>
    </div>
    <div class="BatchMate-emodal-body">
        <input type="hidden" id="editapp_id">
        <div class="BatchMate-efield-group">
            <label>Payment Method</label>
            <select id="editapp_method">
                <option value="bKash">bKash</option>
                <option value="Nagad">Nagad</option>
                <option value="Rocket">Rocket</option>
                <option value="Bank Transfer">Bank Transfer</option>
                <option value="Cash">Cash</option>
            </select>
        </div>
        <div class="BatchMate-efield-group">
            <label>Transaction / Reference ID</label>
            <input type="text" id="editapp_details" placeholder="e.g. 8N7YXXABCD...">
        </div>
        <div id="editapp-options-section" style="display:none;">
            <div class="BatchMate-efield-group">
                <label>Guest Options</label>
                <table class="editapp-opts-table">
                    <thead><tr><th>Option</th><th>Fee/person</th><th>Max</th><th>Qty</th><th>Subtotal</th></tr></thead>
                    <tbody id="editapp-opts-body"></tbody>
                    <tfoot><tr><td colspan="4" style="text-align:right;font-weight:700;">Total:</td><td id="editapp-opts-total" style="font-weight:700;">&#2547; 0.00</td></tr></tfoot>
                </table>
            </div>
        </div>
        <div class="BatchMate-efield-group">
            <div id="editapp-calc-total" style="font-size:13px; color:#0a7242; font-weight:600; margin-bottom:6px; display:none; background:#f0fff4; padding:6px 10px; border-radius:6px; border:1px solid #9ae6b4;"></div>
            <label>Amount (BDT) <span style="font-size:11px;color:#888;">&mdash; edit if needed</span></label>
            <input type="number" id="editapp_amount" step="0.01" min="0" placeholder="0.00">
        </div>
        <div id="editapp-tshirt-section" style="display:none;">
            <div class="BatchMate-efield-group">
                <label>&#128247; T-Shirt Size</label>
                <select id="editapp_tshirt_size">
                    <option value="NA" style="color:#991b1b;font-weight:700;">&#9888; N/A (Not Provided)</option>
                    <option value="S">S</option>
                    <option value="M">M</option>
                    <option value="X">X</option>
                    <option value="L">L</option>
                    <option value="XL">XL</option>
                    <option value="XXL">XXL</option>
                    <option value="XXXL">XXXL</option>
                    <option value="Saree" style="color:#be185d;font-weight:700;">👘 Saree</option>
                </select>
                <p id="editapp-tshirt-na-hint" style="display:none;margin:5px 0 0;font-size:12px;color:#991b1b;font-weight:600;">&#9888; ?? ??????? T-Shirt ???? ?????? ??????? ???? ???? ???????? ?????</p>
            </div>
        </div>
    </div>
    <div class="BatchMate-emodal-footer">
        <button class="BatchMate-ev-btn BatchMate-ev-btn--ghost" id="BatchMate-editapp-cancel">Cancel</button>
        <button class="BatchMate-ev-btn BatchMate-ev-btn--primary" id="BatchMate-editapp-save">
            <span class="dashicons dashicons-saved"></span> Save Changes
        </button>
    </div>
</div>