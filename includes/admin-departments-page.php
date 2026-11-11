<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;
?>
<div class="wrap">
    <div class="BatchMate-page-header-animated">
        <div class="BatchMate-header-left">
            <h1><span class="dashicons dashicons-building"></span> Manage Departments</h1>
            <p>Add and manage academic departments.</p>
        </div>
        <div class="BatchMate-header-actions">
            <button class="BatchMate-header-btn BatchMate-header-btn-primary" id="add-new-department-btn">
                <span class="dashicons dashicons-plus-alt2"></span> Add New
            </button>
        </div>
    </div>

    <table id="departments-table" class="display wp-list-table widefat striped" style="width:100%">
        <thead>
            <tr>
                <th style="width: 50px;">ID</th>
                <th>Department Name</th>
                <th>Member Count</th>
                <th style="width: 80px;">Status</th> <th style="width: 120px;">Actions</th>
            </tr>
        </thead>
        <tbody></tbody>
    </table>
</div>

<script type="text/javascript">
    jQuery(document).ready(function($) {
        const ajax_url = batchmate_admin_ajax.ajax_url;
        const nonce = batchmate_admin_ajax.dept_nonce;

        var table = $('#departments-table').DataTable({
            "processing": true,
            "pageLength": 25,
            "ajax": {
                "url": ajax_url,
                "type": "POST",
                "data": { "action": "BatchMate_get_departments", "_wpnonce": nonce }
            },
            "columns": [
                { "data": "id" },
                { "data": "dept_name" },
                { 
                    "data": "member_count", 
                    "render": function(data) { return `<span class="BatchMate-badge" style="background:#e5e5e5;padding:4px 8px;border-radius:10px;">${data}</span>`; }
                },
                { 
                    "data": "is_active", // Status Data
                    "render": function(data) {
                        return data == 1 
                            ? '<span style="color:green;font-weight:bold;">Active</span>' 
                            : '<span style="color:red;">Inactive</span>';
                    }
                },
                { 
                    "data": null,
                    "orderable": false,
                    "render": function(data, type, row) {
                        // Edit button with data attributes
                        return `<button class="button button-primary edit-btn" 
                                    data-id="${row.id}" 
                                    data-name="${row.dept_name}"
                                    data-active="${row.is_active}">Edit</button>`;
                    }
                }
            ]
        });

    // =====================================================================
    // SLIDE-IN MODAL LOGIC FOR DEPARTMENTS
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

    // Add New Button
    $('#add-new-department-btn').on('click', function(e) {
        e.preventDefault();
        $('#swal_dept_id').val(0);
        $('#swal_dept_name').val('');
        $('#swal_dept_active').val(1);
        openPanel('#BatchMate-department-modal-panel', 'Add New Department', '#BatchMate-dept-modal-title-text');
    });

    // Edit Button
    $('#departments-table tbody').on('click', '.edit-btn', function() {
        var data = $(this).data();
        $('#swal_dept_id').val(data.id);
        $('#swal_dept_name').val(data.name || '');
        $('#swal_dept_active').val(data.active || 0);
        openPanel('#BatchMate-department-modal-panel', 'Edit Department', '#BatchMate-dept-modal-title-text');
    });

    // Save Button
    $('#BatchMate-dept-modal-save-btn').on('click', function() {
        const $btn = $(this);
        const name = $('#swal_dept_name').val();
        
        if (!name) {
            Swal.fire({ icon: 'warning', title: 'Required', text: 'Department Name is required.', toast: true, position: 'top-end', timer: 3000, showConfirmButton: false });
            return;
        }

        $btn.prop('disabled', true).html('<span class="dashicons dashicons-update" style="animation:BatchMate-spin 1s linear infinite;"></span> Saving...');
        $.ajax({
            url: ajax_url, type: 'POST',
            data: {
                action: 'BatchMate_save_department',
                dept_id: $('#swal_dept_id').val(),
                dept_name: name,
                is_active: $('#swal_dept_active').val(),
                _wpnonce: nonce
            }
        }).done(function(res) {
            if (res.success) {
                closeAllPanels();
                table.ajax.reload();
                Swal.fire({ icon: 'success', title: 'Saved!', timer: 1500, showConfirmButton: false, toast: true, position: 'top-end' });
            } else {
                Swal.fire('Error!', res.data || 'Could not save.', 'error');
            }
        }).always(function() {
            $btn.prop('disabled', false).html('<span class="dashicons dashicons-saved"></span> Save Department');
        });
    });
});
</script>

<!-- ================================================================
     SLIDE-IN PANEL MODAL
     ================================================================ -->
<div id="BatchMate-modal-overlay"></div>

<div id="BatchMate-department-modal-panel" class="BatchMate-slide-panel">
    <div class="BatchMate-ec-modal-header">
        <h2>
            <span class="dashicons dashicons-building"></span>
            <span id="BatchMate-dept-modal-title-text">Add New Department</span>
        </h2>
        <button class="BatchMate-ec-modal-close" title="Close">&times;</button>
    </div>
    <div class="BatchMate-ec-modal-body">
        <input type="hidden" id="swal_dept_id" value="0">
        <div class="BatchMate-ec-field">
            <label for="swal_dept_name">Department Name <span class="BatchMate-required">*</span></label>
            <input type="text" id="swal_dept_name" placeholder="Department Name" style="width:100%;">
        </div>
        <div class="BatchMate-ec-field">
            <label for="swal_dept_active">Status</label>
            <select id="swal_dept_active" style="width:100%;">
                <option value="1">Active</option>
                <option value="0">Inactive</option>
            </select>
        </div>
    </div>
    <div class="BatchMate-ec-modal-footer">
        <button class="button BatchMate-ec-modal-cancel-btn">Cancel</button>
        <button class="button button-primary" id="BatchMate-dept-modal-save-btn">
            <span class="dashicons dashicons-saved" style="vertical-align:text-top;margin-top:2px;"></span> Save Department
        </button>
    </div>
</div>