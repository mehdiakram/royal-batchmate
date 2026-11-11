<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

global $wpdb;
$expense_types = $wpdb->get_results("SELECT id, name FROM {$wpdb->prefix}batchmate_expenses_type ORDER BY name ASC");
?>
<div class="wrap">
    <div class="BatchMate-page-header-animated">
        <div class="BatchMate-header-left">
            <h1><span class="dashicons dashicons-chart-line"></span> Manage Expenses</h1>
            <p>Record and track all organizational expenses.</p>
        </div>
        <div class="BatchMate-header-actions">
            <button class="BatchMate-header-btn BatchMate-header-btn-secondary" id="manage-expense-types-btn">Expense Types</button>
            <button class="BatchMate-header-btn BatchMate-header-btn-primary" id="add-new-expense-btn">
                <span class="dashicons dashicons-plus-alt2"></span> Record New Expense
            </button>
        </div>
    </div>

    <table id="expenses-table" class="display wp-list-table widefat striped" style="width:100%">
        <thead>
            <tr>
                <th style="width: 50px;">ID</th>
                <th>Date</th>
                <th>Type</th>
                <th>Amount</th>
                <th>Description</th>
                <th>Vendor</th>
                <th style="width: 100px;">Actions</th>
            </tr>
        </thead>
        <tbody></tbody>
    </table>
</div>

<!-- ================================================================
     SLIDE-IN PANEL MODALS
     ================================================================ -->
<div id="BatchMate-modal-overlay"></div>

<!-- 1. Expense Form Modal -->
<div id="BatchMate-expense-modal" class="BatchMate-slide-panel">
    <div class="BatchMate-ec-modal-header">
        <h2>
            <span class="dashicons dashicons-chart-line"></span>
            <span id="BatchMate-expense-modal-title">Record New Expense</span>
        </h2>
        <button class="BatchMate-ec-modal-close" data-modal="BatchMate-expense-modal" title="Close">&times;</button>
    </div>
    <div class="BatchMate-ec-modal-body">
        <form id="new-expense-form" method="post">
            <input type="hidden" name="expense_id" id="expense_id" value="0">
            
            <div class="BatchMate-ec-field">
                <label for="expense_date">Date <span class="BatchMate-required">*</span></label>
                <input type="date" id="expense_date" name="expense_date" style="width:100%;" required>
            </div>

            <div class="BatchMate-ec-field">
                <label for="amount">Amount (?) <span class="BatchMate-required">*</span></label>
                <input type="number" id="amount" name="amount" min="1" step="0.01" style="width:100%;" required>
            </div>

            <div class="BatchMate-ec-field">
                <label for="type_id">Expense Type <span class="BatchMate-required">*</span></label>
                <select id="type_id" name="type_id" style="width:100%;" required>
                    <option value="">Select Expense Type</option>
                    <?php foreach($expense_types as $type): ?>
                        <option value="<?php echo esc_attr($type->id); ?>"><?php echo esc_html($type->name); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="BatchMate-ec-field">
                <label for="vendor">Vendor/Recipient</label>
                <input type="text" id="vendor" name="vendor" style="width:100%;">
            </div>
            
            <div class="BatchMate-ec-field">
                <label for="description">Description</label>
                <textarea id="description" name="description" style="width:100%;"></textarea>
            </div>
        </form>
    </div>
    <div class="BatchMate-ec-modal-footer">
        <button type="button" class="button BatchMate-ec-modal-cancel-btn BatchMate-modal-close-btn" data-modal="BatchMate-expense-modal">Cancel</button>
        <button type="button" class="button button-primary" id="BatchMate-save-expense-btn">
            <span class="dashicons dashicons-saved" style="vertical-align:text-top;margin-top:2px;"></span> Record Expense
        </button>
    </div>
</div>

<!-- 2. Expense Types Modal -->
<div id="BatchMate-modes-modal" class="BatchMate-slide-panel">
    <div class="BatchMate-ec-modal-header">
        <h2>
            <span class="dashicons dashicons-category"></span>
            <span id="BatchMate-modes-modal-title">Expense Types</span>
        </h2>
        <button class="BatchMate-ec-modal-close" data-modal="BatchMate-modes-modal" title="Close">&times;</button>
    </div>
    <div class="BatchMate-ec-modal-body">
        <button class="button button-secondary" id="add-new-mode-inline" style="margin-bottom: 15px;">Add New Type</button>
        
        <div id="mode-input-container" style="margin-bottom: 15px; border-bottom: 1px solid #ddd; padding-bottom: 15px; display: none;">
            <form id="mode-inline-form">
                <input type="hidden" id="inline_mode_id" value="0">
                <div class="BatchMate-ec-field">
                    <label for="inline_mode_name">Type Name <span class="BatchMate-required">*</span></label>
                    <input type="text" id="inline_mode_name" name="mode_name" style="width:100%;" required>
                </div>
                <div style="margin-top:10px;">
                    <button type="button" class="button button-secondary" id="inline-cancel-mode-btn">Cancel</button>
                    <button type="submit" class="button button-primary" id="inline-save-mode-btn">Save Type</button>
                </div>
            </form>
        </div>
        
        <div id="payment-modes-list"></div>
    </div>
</div>
<script type="text/javascript">
    jQuery(document).ready(function($) {
        const ajax_url = batchmate_admin_ajax.ajax_url;
        const expense_nonce = batchmate_admin_ajax.expense_nonce; 

        // Reusable AJAX wrapper function
        const sendAjax = (action, data) => {
            // For form data, ensure it's an object if coming from serializeArray, or spread if already object
            const payload = typeof data === 'string' ? data : { ...data };

            return $.ajax({
                url: ajax_url,
                type: 'POST',
                dataType: 'json', 
                data: {
                    action: action,
                    _wpnonce: expense_nonce,
                    ...payload
                }
            });
        };

        // --- ?. Expenses Table Initialization (DataTables) ---
        var table = $('#expenses-table').DataTable({
            "processing": true,
            "pageLength": 25,
            "ajax": {
                "url": ajax_url,
                "type": "POST",
                "data": { "action": "BatchMate_get_expenses", "_wpnonce": expense_nonce } 
            },
            "columns": [
                { "data": "id" },
                { "data": "expense_date" },
                { "data": "type_name" }, 
                { 
                    "data": "amount", 
                    "render": function(data) { return `? ${parseFloat(data).toFixed(2)}`; }
                },
                { "data": "description" },
                { "data": "vendor" },
                { 
                    "data": null,
                    "orderable": false,
                    "searchable": false,
                    "render": function(data, type, row) {
                        return `<button class="button button-primary edit-expense-btn" data-id="${row.id}">Edit</button>`;
                    }
                }
            ],
            "order": [[ 1, "desc" ]] 
        });

        // =====================================================================
        // SLIDE-IN MODAL LOGIC FOR EXPENSES
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

        $('.BatchMate-ec-modal-close, .BatchMate-ec-modal-cancel-btn, .BatchMate-modal-close-btn').on('click', function(e) {
            e.preventDefault();
            closeAllPanels();
        });

        

        $(document).on('keydown', function(e) {
            if (e.key === 'Escape') {
                closeAllPanels();
            }
        });

        // Add New Expense Button
        $('#add-new-expense-btn').on('click', function(e) {
            e.preventDefault();
            $('#new-expense-form')[0].reset();
            $('#expense_id').val('0');
            $('#expense_date').val(moment().format('YYYY-MM-DD'));
            $('#BatchMate-save-expense-btn').html('<span class="dashicons dashicons-saved" style="vertical-align:text-top;margin-top:2px;"></span> Record Expense');
            openPanel('#BatchMate-expense-modal', 'Record New Expense', '#BatchMate-expense-modal-title');
        });

        // Edit Expense Button
        $('#expenses-table tbody').on('click', '.edit-expense-btn', function() {
            var tr = $(this).closest('tr');
            var row = table.row(tr).data();

            $('#new-expense-form')[0].reset();
            $('#expense_id').val(row.id);
            $('#expense_date').val(moment(row.expense_date).format('YYYY-MM-DD'));
            $('#amount').val(row.amount);
            $('#type_id').val(row.type_id || '');
            $('#vendor').val(row.vendor || '');
            $('#description').val(row.description || '');

            $('#BatchMate-save-expense-btn').html('<span class="dashicons dashicons-saved" style="vertical-align:text-top;margin-top:2px;"></span> Update Expense');
            openPanel('#BatchMate-expense-modal', `Edit Expense (ID: ${row.id})`, '#BatchMate-expense-modal-title');
        });

        // Form Submission Handler (New/Edit Expense)
        $('#BatchMate-save-expense-btn').on('click', function(e) {
            e.preventDefault();
            
            const formData = $('#new-expense-form').serializeArray().reduce((obj, item) => {
                obj[item.name] = item.value;
                return obj;
            }, {});
            
            if(!formData.expense_date || !formData.amount || !formData.type_id) {
                Swal.fire({ icon: 'warning', title: 'Required Fields', text: 'Please fill out all required fields.', toast: true, position: 'top-end', timer: 3000, showConfirmButton: false });
                return;
            }

            const $btn = $(this);
            $btn.prop('disabled', true).html('<span class="dashicons dashicons-update" style="animation:BatchMate-spin 1s linear infinite;"></span> Saving...');

            sendAjax('BatchMate_save_expense', formData)
                .then(function(response) {
                    closeAllPanels(); 
                    Swal.fire({title: 'Success!', text: response.data || 'Expense successfully processed.', icon: 'success', timer: 1500, showConfirmButton: false, toast: true, position: 'top-end'});
                    table.ajax.reload(null, false);
                })
                .catch(function(error) {
                     const errorMessage = (error && error.responseJSON && error.responseJSON.data) || error.message || 'AJAX request failed.';
                     Swal.fire('Error!', errorMessage, 'error');
                })
                .finally(function() {
                    $btn.prop('disabled', false).html('<span class="dashicons dashicons-saved" style="vertical-align:text-top;margin-top:2px;"></span> Save Expense');
                });
        });

        // --- Expense Types Modal Logic ---
        function loadExpenseTypesList() {
            const listContainer = $('#payment-modes-list');
            listContainer.html('<p>Loading types...</p>');

            sendAjax('BatchMate_get_expense_types', {})
                .then(function(response) {
                    if (!response.success) {
                        listContainer.html(`<p style="color:red;">Error loading types: ${response.data}</p>`);
                        return;
                    }

                    let listHtml = '<ul style="list-style: none; padding: 0; text-align: left;">';
                    (response.data || []).forEach(mode => {
                        listHtml += `
                            <li style="border-bottom: 1px solid #ddd; padding: 10px 0; display: flex; justify-content: space-between; align-items: center;">
                                <span>${mode.name}</span>
                                <button class="button button-secondary edit-type-btn" 
                                    data-id="${mode.id}" 
                                    data-name="${mode.name}">Edit</button>
                            </li>`;
                    });
                    listHtml += '</ul>';
                    listContainer.html(listHtml);
                })
                .catch(function(err) {
                    listContainer.html(`<p style="color:red;">AJAX error.</p>`);
                });
        }

        $('#manage-expense-types-btn').on('click', function(e) {
            e.preventDefault();
            openPanel('#BatchMate-modes-modal', 'Manage Expense Types', '#BatchMate-modes-modal-title');
            loadExpenseTypesList();
        });

        $('#add-new-mode-inline').on('click', function() {
            $('#inline_mode_id').val(0);
            $('#inline_mode_name').val('');
            $('#mode-input-container').slideDown();
            $(this).hide();
            $('#inline-save-mode-btn').text('Save Type');
        });

        $('#inline-cancel-mode-btn').on('click', function() {
            $('#mode-input-container').slideUp();
            $('#add-new-mode-inline').show();
        });

        $('#payment-modes-list').on('click', '.edit-type-btn', function() {
            const id = $(this).data('id');
            const name = $(this).data('name');
            $('#inline_mode_id').val(id);
            $('#inline_mode_name').val(name);
            $('#add-new-mode-inline').hide();
            $('#mode-input-container').slideDown();
            $('#inline-save-mode-btn').text('Update Type');
        });

        $('#mode-inline-form').on('submit', function(e) {
            e.preventDefault();
            const modeId = $('#inline_mode_id').val();
            const modeName = $('#inline_mode_name').val();

            if(!modeName) return;

            sendAjax('BatchMate_save_expense_type', { type_id: modeId, type_name: modeName })
                .then(function(res) {
                    if(res.success) {
                        $('#mode-input-container').slideUp();
                        $('#add-new-mode-inline').show();
                        loadExpenseTypesList();
                        
                        Swal.fire({title: 'Success', icon: 'success', toast: true, position: 'top-end', timer: 2000, showConfirmButton: false});
                        
                        setTimeout(() => location.reload(), 1500); 
                    } else {
                        Swal.fire('Error', res.data || 'Could not save type.', 'error');
                    }
                });
        });
    });
</script>