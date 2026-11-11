<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

global $wpdb;
$departments = $wpdb->get_results("SELECT id, dept_name FROM {$wpdb->prefix}batchmate_department ORDER BY dept_name ASC");
?>
<div class="wrap">
    <div class="BatchMate-page-header-animated">
        <div class="BatchMate-header-left">
            <h1><span class="dashicons dashicons-welcome-learn-more"></span> Manage Programs</h1>
            <p>Add and manage academic programs under departments.</p>
        </div>
        <div class="BatchMate-header-actions">
            <button class="BatchMate-header-btn BatchMate-header-btn-primary" id="add-new-program-btn">
                <span class="dashicons dashicons-plus-alt2"></span> Add New
            </button>
        </div>
    </div>

    <table id="programs-table" class="display wp-list-table widefat striped" style="width:100%">
        <thead>
            <tr>
                <th style="width: 50px;">ID</th>
                <th>Program Name</th>
                <th>Short Code</th>
                <th>Department</th>
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
        const dept_nonce = batchmate_admin_ajax.dept_nonce;
        const prog_nonce = batchmate_admin_ajax.prog_nonce;

        var table = $('#programs-table').DataTable({
            "processing": true,
            "pageLength": 50,
            "ajax": {
                "url": ajax_url,
                "type": "POST",
                "data": { "action": "BatchMate_get_programs", "_wpnonce": prog_nonce }
            },
            "columns": [
                { "data": "id" },
                { "data": "program_name" },
                { "data": "short_code", "render": function(data) { return data ? `<span style="background:#e0f2fe;color:#0284c7;padding:2px 6px;border-radius:4px;font-size:11px;font-weight:700;">${data}</span>` : '<span style="color:#aaa;">-</span>'; } },
                { "data": "dept_name" },
                { 
                    "data": "member_count", 
                    "render": function(data) { return `<span class="BatchMate-badge" style="background:#e5e5e5;padding:4px 8px;border-radius:10px;">${data}</span>`; }
                },
                { 
                    "data": "is_active",
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
                        return `<button class="button button-primary edit-btn" 
                                    data-id="${row.id}" 
                                    data-name="${row.program_name}" 
                                    data-shortcode="${row.short_code || ''}" 
                                    data-dept-id="${row.dept_id}"
                                    data-active="${row.is_active}">Edit</button>`;
                    }
                }
            ]
        });

    // =====================================================================
    // SLIDE-IN MODAL LOGIC FOR PROGRAMS
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
    $('#add-new-program-btn').on('click', function(e) {
        e.preventDefault();
        $('#swal_prog_id').val(0);
        $('#swal_prog_name').val('');
        $('#swal_short_code').val('');
        $('#swal_dept_id').val('');
        $('#swal_prog_active').val(1);
        openPanel('#BatchMate-program-modal-panel', 'Add New Program', '#BatchMate-prog-modal-title-text');
    });

    // Edit Button
    $('#programs-table tbody').on('click', '.edit-btn', function() {
        var data = $(this).data();
        $('#swal_prog_id').val(data.id);
        $('#swal_prog_name').val(data.name || '');
        $('#swal_short_code').val(data.shortcode || '');
        $('#swal_dept_id').val(data.deptId || '');
        $('#swal_prog_active').val(data.active || 0);
        openPanel('#BatchMate-program-modal-panel', 'Edit Program', '#BatchMate-prog-modal-title-text');
    });

    // Save Button
    $('#BatchMate-prog-modal-save-btn').on('click', function() {
        const $btn = $(this);
        const name = $('#swal_prog_name').val();
        const scode = $('#swal_short_code').val();
        const did = $('#swal_dept_id').val();
        
        if (!name || !did) {
            Swal.fire({ icon: 'warning', title: 'Required', text: 'Program Name and Department are required.', toast: true, position: 'top-end', timer: 3000, showConfirmButton: false });
            return;
        }

        $btn.prop('disabled', true).html('<span class="dashicons dashicons-update" style="animation:BatchMate-spin 1s linear infinite;"></span> Saving...');
        $.ajax({
            url: ajax_url, type: 'POST',
            data: {
                action: 'BatchMate_save_program',
                program_id: $('#swal_prog_id').val(),
                program_name: name,
                short_code: scode,
                dept_id: did,
                is_active: $('#swal_prog_active').val(),
                _wpnonce: prog_nonce
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
            $btn.prop('disabled', false).html('<span class="dashicons dashicons-saved"></span> Save Program');
        });
    });
});
</script>

<!-- ================================================================
     SLIDE-IN PANEL MODAL
     ================================================================ -->
<div id="BatchMate-modal-overlay"></div>

<div id="BatchMate-program-modal-panel" class="BatchMate-slide-panel">
    <div class="BatchMate-ec-modal-header">
        <h2>
            <span class="dashicons dashicons-welcome-learn-more"></span>
            <span id="BatchMate-prog-modal-title-text">Add New Program</span>
        </h2>
        <button class="BatchMate-ec-modal-close" title="Close">&times;</button>
    </div>
    <div class="BatchMate-ec-modal-body">
        <input type="hidden" id="swal_prog_id" value="0">
        <div class="BatchMate-ec-field">
            <label for="swal_prog_name">Program Name <span class="BatchMate-required">*</span></label>
            <input type="text" id="swal_prog_name" placeholder="Program Name" style="width:100%;">
        </div>
        <div class="BatchMate-ec-field">
            <label for="swal_short_code">Short Code <span style="font-size:11px;color:#888;">(Max 50 chars)</span></label>
            <input type="text" id="swal_short_code" placeholder="e.g. CSE" style="width:100%;">
        </div>
        <div class="BatchMate-ec-field">
            <label for="swal_dept_id">Department <span class="BatchMate-required">*</span></label>
            <select id="swal_dept_id" style="width:100%;">
                <option value="">Select Department...</option>
                <?php foreach($departments as $d): ?>
                    <option value="<?php echo esc_attr($d->id); ?>"><?php echo esc_html($d->dept_name); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="BatchMate-ec-field">
            <label for="swal_prog_active">Status</label>
            <select id="swal_prog_active" style="width:100%;">
                <option value="1">Active</option>
                <option value="0">Inactive</option>
            </select>
        </div>
    </div>
    <div class="BatchMate-ec-modal-footer">
        <button class="button BatchMate-ec-modal-cancel-btn">Cancel</button>
        <button class="button button-primary" id="BatchMate-prog-modal-save-btn">
            <span class="dashicons dashicons-saved" style="vertical-align:text-top;margin-top:2px;"></span> Save Program
        </button>
    </div>
</div>