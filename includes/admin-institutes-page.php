<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;
?>
<div class="wrap">
    <div class="BatchMate-page-header-animated">
        <div class="BatchMate-header-left">
            <h1><span class="dashicons dashicons-building"></span> Institutes (Organizations)</h1>
            <p>Summary of registered members per educational institute.</p>
        </div>
    </div>

    <table id="institutes-table" class="display wp-list-table widefat striped" style="width:100%">
        <thead>
            <tr>
                <th style="width: 50px;">#</th>
                <th>Institute Name</th>
                <th>Total Registered (Approved)</th>
                <th>Pending Applications</th>
                <th style="width: 120px;">Actions</th>
            </tr>
        </thead>
        <tbody></tbody>
    </table>
</div>

<script type="text/javascript">
    jQuery(document).ready(function($) {
        $('#institutes-table').DataTable({
            "processing": true,
            "ajax": {
                "url": batchmate_admin_ajax.ajax_url,
                "type": "POST",
                "data": {
                    "action": "BatchMate_get_institutes",
                    "_wpnonce": batchmate_admin_ajax.app_nonce // using standard nonce
                }
            },
            "order": [[ 2, "desc" ]], // Order by Total Registered by default
            "columns": [
                { 
                    "data": null,
                    "render": function (data, type, row, meta) {
                        return meta.row + 1; // serial number
                    }
                },
                { "data": "institution" },
                { 
                    "data": "approved_count",
                    "render": function(data) {
                        return '<span class="BatchMate-status-approved" style="padding:4px 8px;border-radius:4px;background:#c3f3d7;color:#0b7a37;font-weight:bold;">' + data + '</span>';
                    }
                },
                { 
                    "data": "pending_count",
                    "render": function(data) {
                        return data > 0 ? '<span class="BatchMate-status-pending" style="padding:4px 8px;border-radius:4px;background:#fff8b5;color:#856404;font-weight:bold;">' + data + '</span>' : '-';
                    }
                },
                { 
                    "data": "institution",
                    "render": function(data) {
                        return '<button class="button button-small action-merge-inst" data-inst="'+data+'">Merge / Edit</button>';
                    }
                }
            ]
        });

        // Handle Merge Button Click
        $('#institutes-table tbody').on('click', '.action-merge-inst', function() {
            var oldInst = $(this).data('inst');
            if (!oldInst || oldInst === 'N/A') {
                Swal.fire('Error', 'Cannot merge empty or N/A institution', 'error');
                return;
            }

            Swal.fire({
                title: 'Merge / Edit Institute',
                html: 'Change <b>' + oldInst + '</b> to correct name:<br><small>(All members of the old name will be moved to the new name)</small>',
                input: 'text',
                inputValue: oldInst,
                showCancelButton: true,
                confirmButtonText: 'Merge & Save',
                confirmButtonColor: '#007cba',
                showLoaderOnConfirm: true,
                preConfirm: (newInst) => {
                    if(!newInst || newInst.trim() === '') {
                        Swal.showValidationMessage('Name cannot be empty');
                        return false;
                    }
                    if(newInst.trim() === oldInst) {
                        Swal.showValidationMessage('Name is unchanged');
                        return false;
                    }
                    return $.ajax({
                        url: batchmate_admin_ajax.ajax_url,
                        type: 'POST',
                        data: {
                            action: 'BatchMate_merge_institutes',
                            _wpnonce: batchmate_admin_ajax.app_nonce,
                            old_name: oldInst,
                            new_name: newInst.trim()
                        }
                    }).then(response => {
                        if(!response.success) {
                            throw new Error(response.data || 'Failed to merge');
                        }
                        return response;
                    }).catch(error => {
                        Swal.showValidationMessage(error.message);
                    });
                },
                allowOutsideClick: () => !Swal.isLoading()
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire('Merged!', 'The institution name has been updated for all records.', 'success');
                    $('#institutes-table').DataTable().ajax.reload();
                }
            });
        }); // <-- Missing closure for click handler added here
    });
</script>
