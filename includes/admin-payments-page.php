<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

global $wpdb;
$payment_modes = $wpdb->get_results( "SELECT id, name FROM {$wpdb->prefix}batchmate_payment_modes ORDER BY name ASC" );
$payment_purposes = $wpdb->get_results( "SELECT id, name, is_active FROM {$wpdb->prefix}batchmate_payment_purpose ORDER BY name ASC" );
$members = $wpdb->get_results( "SELECT id, name, member_number FROM {$wpdb->prefix}batchmate_member ORDER BY member_number ASC" );
?>
<div class="wrap">
    <div class="BatchMate-page-header-animated">
        <div class="BatchMate-header-left">
            <h1><span class="dashicons dashicons-money-alt"></span> Manage Payments</h1>
            <p>Track and manage all member payment records.</p>
        </div>
        <div class="BatchMate-header-actions">
            <button class="BatchMate-header-btn BatchMate-header-btn-secondary" id="manage-payment-modes-btn">Payment Modes</button>
            <button class="BatchMate-header-btn BatchMate-header-btn-secondary" id="manage-payment-purposes-btn">Purposes</button>
            <button class="BatchMate-header-btn BatchMate-header-btn-primary" id="add-new-payment-btn">
                <span class="dashicons dashicons-plus-alt2"></span> Add New Payment
            </button>
        </div>
    </div>

    <table id="payments-table" class="display wp-list-table widefat striped" style="width:100%">
        <thead>
            <tr>
                <th style="width: 50px;">ID</th>
                <th>Member Name</th>
                <th>Amount</th>
                <th>Payment Date</th>
                <th>Purpose</th>
                <th>Mode</th>
                <th>Transaction ID</th>
                <th style="width: 120px;">Actions</th>
            </tr>
        </thead>
        <tbody></tbody>
    </table>
</div>

<!-- ================================================================
     SLIDE-IN PANEL MODALS
     ================================================================ -->
<div id="BatchMate-modal-overlay"></div>

<!-- 1. Payment Form Modal -->
<div id="BatchMate-payment-modal" class="BatchMate-slide-panel BatchMate-slide-panel-wide">
    <div class="BatchMate-ec-modal-header">
        <h2>
            <span class="dashicons dashicons-money-alt"></span>
            <span id="BatchMate-payment-modal-title">Record New Payment</span>
        </h2>
        <button class="BatchMate-ec-modal-close" data-modal="BatchMate-payment-modal" title="Close">&times;</button>
    </div>
    <div class="BatchMate-ec-modal-body">
        <form id="new-payment-form" method="post">
            <input type="hidden" name="payment_id" id="payment_id" value="0">
            
            <div class="BatchMate-ec-field">
                <label for="member_id">Member (Optional)</label>
                <select id="member_id" name="member_id" style="width: 100%;">
                    <option value="">Select Member (Optional)</option>
                    <?php foreach($members as $m): ?>
                        <option value="<?php echo esc_attr($m->id); ?>"><?php echo esc_html($m->member_number . ' - ' . $m->name); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="BatchMate-ec-field">
                <label for="amount">Amount (?) <span class="BatchMate-required">*</span></label>
                <input type="number" id="amount" name="amount" min="1" step="0.01" style="width:100%;" required>
            </div>

            <div class="BatchMate-ec-field">
                <label for="payment_date">Date <span class="BatchMate-required">*</span></label>
                <input type="date" id="payment_date" name="payment_date" style="width:100%;" required>
            </div>

            <div class="BatchMate-ec-field">
                <label for="purpose_id">Payment Purpose <span class="BatchMate-required">*</span></label>
                <select id="purpose_id" name="purpose_id" style="width:100%;" required>
                    <option value="">Select Payment Purpose</option>
                    <?php foreach($payment_purposes as $p): ?>
                        <?php if($p->is_active == 1): ?>
                        <option value="<?php echo esc_attr($p->id); ?>"><?php echo esc_html($p->name); ?></option>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="BatchMate-ec-field">
                <label for="payment_mode_id">Payment Mode <span class="BatchMate-required">*</span></label>
                <select id="payment_mode_id" name="payment_mode_id" style="width:100%;" required>
                    <option value="">Select Payment Mode</option>
                    <?php foreach($payment_modes as $m): ?>
                        <option value="<?php echo esc_attr($m->id); ?>"><?php echo esc_html($m->name); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="BatchMate-ec-field">
                <label for="transaction">Transaction/Reference ID</label>
                <input type="text" id="transaction" name="transaction" style="width:100%;">
            </div>
            
            <div class="BatchMate-ec-field">
                <label for="note">Note</label>
                <textarea id="note" name="note" style="width:100%;"></textarea>
            </div>
        </form>
    </div>
    <div class="BatchMate-ec-modal-footer">
        <button type="button" class="button BatchMate-ec-modal-cancel-btn" data-modal="BatchMate-payment-modal">Cancel</button>
        <button type="button" class="button button-primary" id="BatchMate-save-payment-btn">
            <span class="dashicons dashicons-saved" style="vertical-align:text-top;margin-top:2px;"></span> Record Payment
        </button>
    </div>
</div>

<!-- 2. Payment Modes Modal -->
<div id="BatchMate-modes-modal" class="BatchMate-slide-panel">
    <div class="BatchMate-ec-modal-header">
        <h2>
            <span class="dashicons dashicons-portfolio"></span>
            <span id="BatchMate-modes-modal-title">Payment Modes</span>
        </h2>
        <button class="BatchMate-ec-modal-close" data-modal="BatchMate-modes-modal" title="Close">&times;</button>
    </div>
    <div class="BatchMate-ec-modal-body">
        <button class="button button-secondary" id="add-new-mode-inline" style="margin-bottom: 15px;">Add New Mode</button>
        
        <div id="mode-input-container" style="margin-bottom: 15px; border-bottom: 1px solid #ddd; padding-bottom: 15px; display: none;">
            <form id="mode-inline-form">
                <input type="hidden" id="inline_mode_id" value="0">
                <div class="BatchMate-ec-field">
                    <label for="inline_mode_name">Mode Name <span class="BatchMate-required">*</span></label>
                    <input type="text" id="inline_mode_name" name="mode_name" style="width:100%;" required>
                </div>
                <div style="margin-top:10px;">
                    <button type="button" class="button button-secondary" id="inline-cancel-mode-btn">Cancel</button>
                    <button type="submit" class="button button-primary" id="inline-save-mode-btn">Save Mode</button>
                </div>
            </form>
        </div>
        
        <div id="payment-modes-list"></div>
    </div>
</div>

<!-- 3. Payment Purposes Modal -->
<div id="BatchMate-purposes-modal" class="BatchMate-slide-panel">
    <div class="BatchMate-ec-modal-header">
        <h2>
            <span class="dashicons dashicons-clipboard"></span>
            <span id="BatchMate-purposes-modal-title">Payment Purposes</span>
        </h2>
        <button class="BatchMate-ec-modal-close" data-modal="BatchMate-purposes-modal" title="Close">&times;</button>
    </div>
    <div class="BatchMate-ec-modal-body">
        <button class="button button-secondary" id="add-new-purpose-inline" style="margin-bottom: 15px;">Add New Purpose</button>
        
        <div id="purpose-input-container" style="margin-bottom: 15px; border-bottom: 1px solid #ddd; padding-bottom: 15px; display: none;">
            <form id="purpose-inline-form">
                <input type="hidden" id="inline_purpose_id" value="0">
                <div class="BatchMate-ec-field">
                    <label for="inline_purpose_name">Purpose Name <span class="BatchMate-required">*</span></label>
                    <input type="text" id="inline_purpose_name" name="purpose_name" style="width:100%;" required>
                </div>
                <div class="BatchMate-ec-field">
                    <label for="inline_purpose_active">Status</label>
                    <select id="inline_purpose_active" name="is_active" style="width:100%;">
                        <option value="1">Active</option>
                        <option value="0">Inactive</option>
                    </select>
                </div>
                <div style="margin-top:10px;">
                    <button type="button" class="button button-secondary" id="inline-cancel-purpose-btn">Cancel</button>
                    <button type="submit" class="button button-primary" id="inline-save-purpose-btn">Save Purpose</button>
                </div>
            </form>
        </div>
        
        <div id="payment-purposes-list"></div>
    </div>
</div>
<script type="text/javascript">
    jQuery(document).ready(function($) {
        const ajax_url = batchmate_admin_ajax.ajax_url;
        const payment_nonce = batchmate_admin_ajax.payment_nonce; 
        
        // Reusable AJAX wrapper function for robustness and consistency
        // This spreads the data object into the AJAX payload.
        const sendAjax = (action, data) => {
            return $.ajax({
                url: ajax_url,
                type: 'POST',
                dataType: 'json', // Explicitly request JSON
                data: {
                    action: action,
                    _wpnonce: payment_nonce,
                    ...data
                }
            });
        };
        
        var table = $('#payments-table').DataTable({
            "processing": true,
            "pageLength": 25,
            "ajax": {
                "url": ajax_url,
                "type": "POST",
                "data": { "action": "BatchMate_get_payments", "_wpnonce": payment_nonce } 
            },
            "columns": [
                { "data": "id" },
                { "data": "member_name" }, 
                { "data": "amount", "render": function(data) { return `? ${parseFloat(data).toFixed(2)}`; } },
                { "data": "payment_date" },
                { "data": "purpose_name" }, // <--- ADDED COLUMN DATA
                { "data": "mode_name" }, 
                { "data": "transaction" },
                { 
                    "data": null,
                    "orderable": false,
                    "searchable": false,
                    "render": function(data, type, row) {
                        return `
                            <button class="button button-primary edit-payment-btn" data-id="${row.id}" title="Edit Payment">
                                <span class="dashicons dashicons-edit"></span>
                            </button>
                            <button class="button button-secondary print-btn" data-id="${row.id}" title="Print Payment Receipt">
                                <span class="dashicons dashicons-printer"></span>
                            </button>
                        `;
                    }
                }
            ],
            "order": [[ 3, "desc" ]]
        });


        // =====================================================================
        // SLIDE-IN MODAL LOGIC FOR PAYMENTS
        // =====================================================================
        
        function openPanel(targetSelector, titleText, titleElementSelector) {
            $(titleElementSelector).text(titleText);
            $('#BatchMate-modal-overlay').fadeIn(200);
            setTimeout(function() {
                $(targetSelector).addClass('is-open');
            }, 10);
            $('body').css('overflow', 'hidden');
        }

        function closeAllPanels() {
            $('.BatchMate-slide-panel').removeClass('is-open');
            setTimeout(function() {
                $('#BatchMate-modal-overlay').fadeOut(200);
            }, 300);
            $('body').css('overflow', '');
        }

        $('.BatchMate-ec-modal-close, .BatchMate-ec-modal-cancel-btn').on('click', function(e) {
            e.preventDefault();
            closeAllPanels();
        });

        

        $(document).on('keydown', function(e) {
            if (e.key === 'Escape') {
                closeAllPanels();
            }
        });

        function initSelect2() {
            const $memberSelect = $('#member_id');
            if (typeof $memberSelect.select2 === 'function' && $memberSelect.data('select2')) {
                $memberSelect.select2('destroy');
            }
            
            $memberSelect.select2({
                dropdownParent: $('#BatchMate-payment-modal'), 
                placeholder: "Select Member (Optional)",
                allowClear: true
            });
        }

        // Add New Payment Button
        $('#add-new-payment-btn').on('click', function(e) {
            e.preventDefault();
            $('#new-payment-form')[0].reset();
            $('#payment_id').val('0');
            $('#payment_date').val(moment().format('YYYY-MM-DD'));
            $('#BatchMate-save-payment-btn').html('<span class="dashicons dashicons-saved" style="vertical-align:text-top;margin-top:2px;"></span> Record Payment');
            
            initSelect2();
            $('#member_id').val(null).trigger('change');

            openPanel('#BatchMate-payment-modal', 'Record New Payment', '#BatchMate-payment-modal-title');
        });

        // Edit Payment Button
        $('#payments-table tbody').on('click', '.edit-payment-btn', function() {
            var tr = $(this).closest('tr');
            var row = table.row(tr).data();

            $('#new-payment-form')[0].reset();
            $('#payment_id').val(row.id);
            $('#payment_date').val(moment(row.payment_date).format('YYYY-MM-DD'));
            $('#amount').val(row.amount);
            $('#payment_mode_id').val(row.payment_mode_id || '');
            $('#purpose_id').val(row.purpose_id || '');
            $('#transaction').val(row.transaction || '');
            $('#note').val(row.note || '');

            initSelect2();
            if (row.member_id) {
                $('#member_id').val(row.member_id).trigger('change');
            } else {
                $('#member_id').val(null).trigger('change');
            }

            $('#BatchMate-save-payment-btn').html('<span class="dashicons dashicons-saved" style="vertical-align:text-top;margin-top:2px;"></span> Update Payment');
            openPanel('#BatchMate-payment-modal', `Edit Payment (ID: ${row.id})`, '#BatchMate-payment-modal-title');
        });

        // Form Submission Handler
        $('#BatchMate-save-payment-btn').on('click', function(e) {
            e.preventDefault();
            
            const formData = $('#new-payment-form').serializeArray().reduce((obj, item) => {
                obj[item.name] = item.value;
                return obj;
            }, {});
            
            if(!formData.payment_date || !formData.amount || !formData.purpose_id || !formData.payment_mode_id) {
                Swal.fire({ icon: 'warning', title: 'Required Fields', text: 'Please fill out all required fields.', toast: true, position: 'top-end', timer: 3000, showConfirmButton: false });
                return;
            }

            const $btn = $(this);
            $btn.prop('disabled', true).html('<span class="dashicons dashicons-update" style="animation:BatchMate-spin 1s linear infinite;"></span> Saving...');

            sendAjax('BatchMate_save_payment', formData)
                .then(function(response) {
                    closeAllPanels(); 
                    Swal.fire({title: 'Success!', text: response.data || 'Payment successfully processed.', icon: 'success', timer: 1500, showConfirmButton: false, toast: true, position: 'top-end'});
                    table.ajax.reload(null, false);
                })
                .catch(function(error) {
                     const errorMessage = (error && error.responseJSON && error.responseJSON.data) || error.message || 'AJAX request failed.';
                     Swal.fire('Error!', errorMessage, 'error');
                })
                .finally(function() {
                     $btn.prop('disabled', false).html('<span class="dashicons dashicons-saved" style="vertical-align:text-top;margin-top:2px;"></span> Record Payment');
                });
        });

        // Payment Print Button Click
        $('#payments-table tbody').on('click', '.print-btn', function() {
            var payment_id = $(this).data('id');
            var print_url = `<?php echo home_url('/print-payment/'); ?>?payment_id=${payment_id}`; 
            window.open(print_url, '_blank');
        });
        
        // --- Payment Modes ---
        function loadPaymentModesList() {
            const listContainer = $('#payment-modes-list');
            listContainer.html('<p>Loading modes...</p>');

            sendAjax('BatchMate_get_payment_modes', {})
                .then(function(response) {
                    if (!response.success) {
                        listContainer.html(`<p style="color:red;">Error loading modes: ${response.data}</p>`);
                        return;
                    }

                    let listHtml = '<ul style="list-style: none; padding: 0; text-align: left;">';
                    (response.data || []).forEach(mode => {
                        listHtml += `
                            <li style="border-bottom: 1px solid #ddd; padding: 10px 0; display: flex; justify-content: space-between; align-items: center;">
                                <span>${mode.name}</span>
                                <button class="button button-secondary edit-mode-btn" 
                                    data-id="${mode.id}" 
                                    data-name="${mode.name}">Edit</button>
                            </li>`;
                    });
                    listHtml += '</ul>';
                    listContainer.html(listHtml);
                })
                .catch(function(error) {
                     listContainer.html(`<p style="color:red;">AJAX error.</p>`);
                });
        }

        $('#manage-payment-modes-btn').on('click', function(e) {
            e.preventDefault();
            openPanel('#BatchMate-modes-modal', 'Payment Modes', '#BatchMate-modes-modal-title');
            loadPaymentModesList();
        });
        
        $('#add-new-mode-inline').on('click', function() {
            $('#inline_mode_id').val(0);
            $('#inline_mode_name').val('');
            $('#inline-save-mode-btn').text('Save Mode');
            $('#mode-input-container').slideDown();
            $(this).hide();
        });

        $('#inline-cancel-mode-btn').on('click', function() {
            $('#mode-input-container').slideUp();
            $('#add-new-mode-inline').show();
        });

        $('#payment-modes-list').on('click', '.edit-mode-btn', function() {
            const id = $(this).data('id');
            const name = $(this).data('name');
            $('#inline_mode_id').val(id);
            $('#inline_mode_name').val(name);
            $('#mode-input-container').slideDown();
            $('#inline-save-mode-btn').text('Update Mode');
            $('#add-new-mode-inline').hide();
        });

        $('#mode-inline-form').on('submit', function(e) {
            e.preventDefault();
            const id = $('#inline_mode_id').val();
            const name = $('#inline_mode_name').val();
            
            if (!name) return;

            const action = id == 0 ? 'BatchMate_add_payment_mode' : 'BatchMate_save_payment_mode';
            const payload = { mode_name: name };
            if (id != 0) payload.mode_id = id; // existing ajax function might use save or edit, but we just use BatchMate_save_payment_mode assuming it handles it

            sendAjax('BatchMate_save_payment_mode', {mode_id: id, mode_name: name})
                .then((response) => {
                    if (!response.success) {
                        Swal.fire('Error!', response.data || 'Failed to save mode.', 'error');
                        return;
                    }
                    Swal.fire({title: 'Saved!', icon: 'success', toast: true, position: 'top-end', timer: 1500, showConfirmButton: false});
                    $('#mode-input-container').slideUp();
                    $('#add-new-mode-inline').show();
                    loadPaymentModesList();
                    setTimeout(() => location.reload(), 1500); 
                })
                .catch(error => {
                    Swal.fire('Error!', 'Failed to save.', 'error');
                });
        });

        // --- Payment Purposes ---
        function loadPaymentPurposesList() {
            const listContainer = $('#payment-purposes-list');
            listContainer.html('<p>Loading purposes...</p>');

            sendAjax('BatchMate_get_payment_purposes', {})
                .then(function(response) {
                    if (!response.success) {
                        listContainer.html(`<p style="color:red;">Error loading purposes: ${response.data}</p>`);
                        return;
                    }

                    let listHtml = '<ul style="list-style: none; padding: 0; text-align: left;">';
                    (response.data || []).forEach(purpose => {
                        const statusText = purpose.is_active == 1 ? '<span style="color:green">Active</span>' : '<span style="color:red">Inactive</span>';
                        listHtml += `
                            <li style="border-bottom: 1px solid #ddd; padding: 10px 0; display: flex; justify-content: space-between; align-items: center;">
                                <span>${purpose.name} (${statusText})</span>
                                <button class="button button-secondary edit-purpose-btn" 
                                    data-id="${purpose.id}" 
                                    data-name="${purpose.name}"
                                    data-active="${purpose.is_active}">Edit</button>
                            </li>`;
                    });
                    listHtml += '</ul>';
                    listContainer.html(listHtml);
                })
                .catch(function(error) {
                     listContainer.html(`<p style="color:red;">AJAX error.</p>`);
                });
        }

        $('#manage-payment-purposes-btn').on('click', function(e) {
            e.preventDefault();
            openPanel('#BatchMate-purposes-modal', 'Payment Purposes', '#BatchMate-purposes-modal-title');
            loadPaymentPurposesList();
        });
        
        $('#add-new-purpose-inline').on('click', function() {
            $('#inline_purpose_id').val(0);
            $('#inline_purpose_name').val('');
            $('#inline_purpose_active').val(1);
            $('#inline-save-purpose-btn').text('Save Purpose');
            $('#purpose-input-container').slideDown();
            $(this).hide();
        });

        $('#inline-cancel-purpose-btn').on('click', function() {
            $('#purpose-input-container').slideUp();
            $('#add-new-purpose-inline').show();
        });

        $('#payment-purposes-list').on('click', '.edit-purpose-btn', function() {
            const id = $(this).data('id');
            const name = $(this).data('name');
            const active = $(this).data('active');
            
            $('#inline_purpose_id').val(id);
            $('#inline_purpose_name').val(name);
            $('#inline_purpose_active').val(active);
            $('#purpose-input-container').slideDown();
            $('#inline-save-purpose-btn').text('Update Purpose');
            $('#add-new-purpose-inline').hide();
        });

        $('#purpose-inline-form').on('submit', function(e) {
            e.preventDefault();
            const id = $('#inline_purpose_id').val();
            const name = $('#inline_purpose_name').val();
            const active = $('#inline_purpose_active').val();
            
            if (!name) return;

            sendAjax('BatchMate_save_payment_purpose', {
                purpose_id: id,
                purpose_name: name,
                is_active: active
            })
                .then((response) => {
                    if (!response.success) {
                        Swal.fire('Error!', response.data || 'Failed to save purpose.', 'error');
                        return;
                    }
                    Swal.fire({title: 'Saved!', icon: 'success', toast: true, position: 'top-end', timer: 1500, showConfirmButton: false});
                    $('#purpose-input-container').slideUp();
                    $('#add-new-purpose-inline').show();
                    loadPaymentPurposesList();
                    setTimeout(() => location.reload(), 1500); 
                })
                .catch(error => {
                    Swal.fire('Error!', 'Failed to save.', 'error');
                });
        });

    });
</script>