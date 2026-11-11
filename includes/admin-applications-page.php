<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;
?>
<div class="wrap">
    <div class="BatchMate-page-header-animated">
        <div class="BatchMate-header-left">
            <h1><span class="dashicons dashicons-list-view"></span> Manage Applications</h1>
            <p>Review, approve, or reject new membership applications.</p>
        </div>
    </div>

    <table id="applications-table" class="display wp-list-table widefat striped" style="width:100%">
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Cell Number</th> <th>Dept.</th>
                <th>Payment Method</th> <th>Payment Details</th> <th>Status</th>
                <th style="width: 120px;">Actions</th>
            </tr>
        </thead>
        <tbody>
            </tbody>
    </table>
</div>

<script type="text/javascript">
    jQuery(document).ready(function($) {

        const ajax_url = batchmate_admin_ajax.ajax_url;
        const app_nonce = batchmate_admin_ajax.app_nonce;

        // 1. DataTable ??????????? ????
        var table = $('#applications-table').DataTable({
            "processing": true,
            "ajax": {
                "url": ajax_url,
                "type": "POST",
                "data": {
                    "action": "BatchMate_get_applications",
                    "_wpnonce": app_nonce
                }
            },
            // ?????? ??????: ID (column index 0) ??????? Descending
            "order": [[ 0, "desc" ]], 
            "columns": [
                { "data": "id" },
                { "data": "name" },
                { "data": "cell_number" }, // Email ?? ?????? Cell Number
                { "data": "dept_name" }, 
                { 
                    "data": "payment_method", // Batch ?? ?????? Payment Method
                    "render": function(data) {
                        return data ? data.toUpperCase() : '';
                    }
                },
                { "data": "payment_details" }, // ???? ????: Payment Details
                { 
                    "data": "status",
                    "render": function(data, type, row) {
                        return `<span class="BatchMate-status-${data}">${data.charAt(0).toUpperCase() + data.slice(1)}</span>`;
                    }
                },
                { 
                    "data": null,
                    "orderable": false,
                    "searchable": false,
                    "render": function(data, type, row) {
                        let actionsHtml = `
                        <div class="BatchMate-actions-btn-container">
                            <button class="BatchMate-actions-btn">Actions</button>
                            <div class="BatchMate-actions-dropdown">
                                <button class="button print-btn" data-id="${row.id}">
                                    <span class="dashicons dashicons-printer" style="margin-right: 5px;"></span>Print
                                </button>`;
                        
                        // ????????? ??????? ??? ???? ???????? ??? ????
                        if (row.status === 'pending') {
                            actionsHtml += `
                                <button class="button status-update-btn" data-id="${row.id}">
                                    <span class="dashicons dashicons-dismiss" style="margin-right: 5px;"></span>Status Update (Reject)
                                </button>
                                <button class="button approve-btn" data-id="${row.id}" data-email="${row.email}" style="width: 100%; text-align: left; justify-content: flex-start;">
                                    <span class="dashicons dashicons-yes" style="margin-right: 5px;"></span>Approval
                                </button>`;
                        }

                        actionsHtml += `
                            </div>
                        </div>`;
                        
                        return actionsHtml;
                    }
                }
            ]
        });

        // 2. Approval ????? ?????
        $('#applications-table tbody').on('click', '.approve-btn', function() {
            var app_id = $(this).data('id');
            var app_email = $(this).data('email'); // ??????? ???? ????? ??? ?????, ?????? ?????? ?? ???? ??

            // ???? AJAX ?? ?????? ??? ???
            var getNextMemberId = $.ajax({
                url: ajax_url,
                type: 'POST',
                data: { action: 'BatchMate_get_next_member_number', _wpnonce: app_nonce }
            });

            var checkEmail = $.ajax({
                url: ajax_url,
                type: 'POST',
                data: { action: 'BatchMate_check_member_email', email: app_email, _wpnonce: app_nonce }
            });

            $.when(getNextMemberId, checkEmail).done(function(numResult, emailResult) {
                
                var nextMemberNumber = numResult[0].success ? numResult[0].data : 'BM-00001';
                var emailExists = emailResult[0].success ? emailResult[0].data : false;

                var defaultEmailDomain = '<?php echo esc_js( function_exists("batchmate_get_email_domain") ? batchmate_get_email_domain() : "batchmate.org" ); ?>';
                var suggestedEmail = nextMemberNumber.toLowerCase() + '@' + defaultEmailDomain;
                var emailInputHtml = '';

                if (emailExists) {
                    emailInputHtml = `
                        <div class="swal-email-warning">
                            Warning: This email (${app_email}) already exists in the member list.
                        </div>
                        <label for="swal_new_email" class="swal-approval-label">New Member Email *</label>
                        <input type="email" id="swal_new_email" class="swal2-input swal-approval-input" 
                               value="${suggestedEmail}">
                    `;
                } else if (!app_email) {
                    emailInputHtml = `
                        <label for="swal_new_email" class="swal-approval-label">Member Email (Auto-generated)</label>
                        <input type="email" id="swal_new_email" class="swal2-input swal-approval-input" 
                               value="${suggestedEmail}">
                    `;
                }

                // --- ????? ????? ??????? (?????? ??????) ---
                var createUserHtml = `
                    <div style="text-align: left; margin-top: 25px; padding: 10px; background: #f7f7f7; border-radius: 5px; border: 1px solid #eee;">
                        <input type="checkbox" id="swal_create_user" class="swal2-checkbox" style="display: inline-block; width: auto; margin-right: 10px;">
                        <label for="swal_create_user" style="display: inline; font-size: 1em; font-weight: bold; cursor: pointer;">
                            Create WordPress User
                        </label>
                        <div style="font-size: 0.85em; margin-left: 30px; margin-top: 5px; color: #555;">
                            (If checked, a user account will be created and an email with login details will be sent.)
                        </div>
                    </div>
                `;

                Swal.fire({
                    title: 'Approve Application',
                    html: `
                        <label for="swal_member_number" class="swal-approval-label">Member Number *</label>
                        <input type="text" id="swal_member_number" class="swal2-input swal-approval-input" 
                               value="${nextMemberNumber}">
                        
                        ${emailInputHtml}
                        ${createUserHtml} 
                    `,
                    confirmButtonText: 'Approve',
                    focusConfirm: false,
                    showLoaderOnConfirm: true,
                    preConfirm: () => {
                        const memberNumber = Swal.getPopup().querySelector('#swal_member_number').value;
                        const newEmailEl = Swal.getPopup().querySelector('#swal_new_email');
                        const newEmail = newEmailEl ? newEmailEl.value : null;
                        const createUser = Swal.getPopup().querySelector('#swal_create_user').checked;

                        if (!memberNumber) {
                            Swal.showValidationMessage(`Member Number is required.`);
                            return false;
                        }
                        if (emailExists && !newEmail) {
                            Swal.showValidationMessage(`New email is required as the original one exists.`);
                            return false;
                        }
                        
                        return $.ajax({
                            url: ajax_url,
                            type: 'POST',
                            data: {
                                action: 'BatchMate_approve_application',
                                application_id: app_id,
                                member_number: memberNumber,
                                new_email: newEmail,
                                create_user: createUser,
                                _wpnonce: app_nonce
                            }
                        }).fail(function(xhr) {
                            var errMsg = xhr.responseText ? 'Request failed: ' + xhr.responseText : 'Request failed. Please try again.';
                            Swal.showValidationMessage(errMsg);
                        });
                    }
                }).then((result) => {
                    if (result.isConfirmed && result.value.success) {
                        Swal.fire({
                            title: 'Approved!',
                            text: result.value.data,
                            icon: 'success',
                            timer: 3000,
                            showConfirmButton: false
                        }).then(() => {
                            table.ajax.reload(null, false); 
                        });
                    } else if (result.value && !result.value.success) {
                         Swal.fire('Error!', result.value.data, 'error');
                    }
                });

            }).fail(function() {
                Swal.fire('Error', 'Could not check application details. Please try again.', 'error');
            });
        });

        // 3. Status Update (Reject) ????? ?????
        $('#applications-table tbody').on('click', '.status-update-btn', function() {
            var app_id = $(this).data('id');

            Swal.fire({
                title: 'Reject Application?',
                text: "Are you sure you want to reject this application? This cannot be undone easily.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, Reject it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    // ??????? ???? AJAX ??
                    $.ajax({
                        url: ajax_url,
                        type: 'POST',
                        data: {
                            action: 'BatchMate_reject_application',
                            application_id: app_id,
                            _wpnonce: app_nonce
                        },
                        success: function(response) {
                            if (response.success) {
                                Swal.fire({
                                    title: 'Rejected!',
                                    text: 'The application has been rejected.',
                                    icon: 'success',
                                    timer: 1500,
                                    showConfirmButton: false
                                }).then(() => {
                                    table.ajax.reload(null, false); 
                                });
                            } else {
                                Swal.fire('Error!', response.data, 'error');
                            }
                        },
                        error: function() {
                            Swal.fire('Error!', 'Something went wrong.', 'error');
                        }
                    });
                }
            });
        });

        // 4. Print ????? ?????
        $('#applications-table tbody').on('click', '.print-btn', function() {
            var app_id = $(this).data('id');
            // ????? 'print-application' ??????? ????? ???? ??? ????? ????? ??????? ??? ???
            var print_url = `<?php echo home_url('/print-application/'); ?>?application_id=${app_id}`;
            window.open(print_url, '_blank');
        });

    });
</script>