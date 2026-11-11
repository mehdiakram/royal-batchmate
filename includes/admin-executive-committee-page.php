<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

global $wpdb;

// Fetch Dropdown Data for Modals
$committees = $wpdb->get_results("SELECT id, session_year, is_active FROM {$wpdb->prefix}batchmate_committee ORDER BY id DESC");
$active_committee_id = '';
if ($committees) {
    foreach($committees as $c) {
        if ($c->is_active == 1) {
            $active_committee_id = $c->id;
            break;
        }
    }
}
$posts = $wpdb->get_results("SELECT id, post_title FROM {$wpdb->prefix}batchmate_ec_post_title ORDER BY position_order ASC");
$members = $wpdb->get_results("SELECT id, CONCAT(member_number, ' - ', name) as text FROM {$wpdb->prefix}batchmate_member ORDER BY name ASC");
?>

<div class="wrap">

    <!-- Animated Page Header -->
    <div class="BatchMate-page-header-animated">
        <div class="BatchMate-header-left">
            <h1><span class="dashicons dashicons-id-alt"></span> Manage Executive Committee</h1>
            <p>Add, edit, and manage committee members, sessions, and posts.</p>
        </div>
        <div class="BatchMate-header-actions">
            <button class="BatchMate-header-btn BatchMate-header-btn-primary" id="add-new-ec-member-btn">
                <span class="dashicons dashicons-plus-alt2"></span> Add New EC Member
            </button>
        </div>
    </div>

    <div class="BatchMate-section-header">
        <h1>Committee Members</h1>
    </div>
    <table id="ec-members-table" class="display wp-list-table widefat striped" style="width:100%">
        <thead>
            <tr>
                <th>ID</th>
                <th>Member Name</th>
                <th>Committee</th>
                <th>Post</th>
                <th>Actions</th>
            </tr>
        </thead>
    </table>

    <div class="BatchMate-section-header">
        <h1>Committees (Sessions)</h1>
        <button class="page-title-action" id="add-new-committee-btn">Add New Committee</button>
    </div>
    <table id="committees-table" class="display wp-list-table widefat striped" style="width:100%">
        <thead>
            <tr>
                <th>ID</th>
                <th>Session Year</th>
                <th>Start Date</th>
                <th>End Date</th>
                <th>Member Count</th>
                <th>Active</th>
                <th>Shortcode</th>
                <th>Actions</th>
            </tr>
        </thead>
    </table>

    <div class="BatchMate-section-header">
        <h1>Committee Posts</h1>
        <button class="page-title-action" id="add-new-post-btn">Add New Post</button>
    </div>
    <table id="posts-table" class="display wp-list-table widefat striped" style="width:100%">
        <thead>
            <tr>
                <th>ID</th>
                <th>Post Title</th>
                <th>Order</th>
                <th>Row</th> <th>Actions</th>
            </tr>
        </thead>
    </table>
</div>

<!-- ================================================================
     SLIDE-IN PANEL MODALS (??? ???? ?????? ???)
     ================================================================ -->
<div id="BatchMate-ec-member-modal-overlay"></div>

<!-- 1. EC MEMBER MODAL -->
<div id="BatchMate-ec-member-modal-panel" class="BatchMate-slide-panel">
    <div class="BatchMate-ec-modal-header">
        <h2 id="BatchMate-ec-modal-title">
            <span class="dashicons dashicons-admin-users"></span>
            <span id="BatchMate-ec-modal-title-text">Add New EC Member</span>
        </h2>
        <button class="BatchMate-ec-modal-close" data-target="#BatchMate-ec-member-modal-panel" title="Close">&times;</button>
    </div>
    <div class="BatchMate-ec-modal-body">
        <input type="hidden" id="batchmate_ec_panel_id" value="0">
        <div class="BatchMate-ec-field">
            <label for="batchmate_ec_panel_committee">Committee (Session) <span class="BatchMate-required">*</span></label>
            <select id="batchmate_ec_panel_committee" style="width:100%;">
                <option value="">Select Committee...</option>
                <?php foreach($committees as $c): ?>
                    <option value="<?php echo esc_attr($c->id); ?>"><?php echo esc_html($c->session_year); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="BatchMate-ec-field">
            <label for="batchmate_ec_panel_post">Post <span class="BatchMate-required">*</span></label>
            <select id="batchmate_ec_panel_post" style="width:100%;">
                <option value="">Select Post...</option>
                <?php foreach($posts as $p): ?>
                    <option value="<?php echo esc_attr($p->id); ?>"><?php echo esc_html($p->post_title); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="BatchMate-ec-field">
            <label for="batchmate_ec_panel_member">Member <span class="BatchMate-required">*</span></label>
            <select id="batchmate_ec_panel_member" style="width:100%;">
                <option value="">Search Member...</option>
                <?php foreach($members as $m): ?>
                    <option value="<?php echo esc_attr($m->id); ?>"><?php echo esc_html($m->text); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
    <div class="BatchMate-ec-modal-footer">
        <button class="button BatchMate-ec-modal-cancel-btn" data-target="#BatchMate-ec-member-modal-panel">Cancel</button>
        <button class="button button-primary" id="BatchMate-ec-modal-save-btn">
            <span class="dashicons dashicons-saved" style="vertical-align:text-top;margin-top:2px;"></span> Save Member
        </button>
    </div>
</div>

<!-- 2. COMMITTEE MODAL -->
<div id="BatchMate-committee-modal-panel" class="BatchMate-slide-panel">
    <div class="BatchMate-ec-modal-header">
        <h2>
            <span class="dashicons dashicons-groups"></span>
            <span id="BatchMate-comm-modal-title-text">Add New Committee</span>
        </h2>
        <button class="BatchMate-ec-modal-close" data-target="#BatchMate-committee-modal-panel" title="Close">&times;</button>
    </div>
    <div class="BatchMate-ec-modal-body">
        <input type="hidden" id="swal_comm_id" value="0">
        <div class="BatchMate-ec-field">
            <label for="swal_session_year">Session Year <span class="BatchMate-required">*</span></label>
            <input type="text" id="swal_session_year" placeholder="e.g., 2025-2026" style="width:100%;">
        </div>
        <div class="BatchMate-ec-field">
            <label for="swal_start_date">Start Date</label>
            <input type="date" id="swal_start_date" style="width:100%;">
        </div>
        <div class="BatchMate-ec-field">
            <label for="swal_end_date">End Date</label>
            <input type="date" id="swal_end_date" style="width:100%;">
        </div>
        <div class="BatchMate-ec-field">
            <label for="swal_is_active">Is Active?</label>
            <select id="swal_is_active" style="width:100%;">
                <option value="1">Yes</option>
                <option value="0">No</option>
            </select>
        </div>
    </div>
    <div class="BatchMate-ec-modal-footer">
        <button class="button BatchMate-ec-modal-cancel-btn" data-target="#BatchMate-committee-modal-panel">Cancel</button>
        <button class="button button-primary" id="BatchMate-comm-modal-save-btn">
            <span class="dashicons dashicons-saved" style="vertical-align:text-top;margin-top:2px;"></span> Save Committee
        </button>
    </div>
</div>

<!-- 3. POST MODAL -->
<div id="BatchMate-post-modal-panel" class="BatchMate-slide-panel">
    <div class="BatchMate-ec-modal-header">
        <h2>
            <span class="dashicons dashicons-businessman"></span>
            <span id="BatchMate-post-modal-title-text">Add New Post</span>
        </h2>
        <button class="BatchMate-ec-modal-close" data-target="#BatchMate-post-modal-panel" title="Close">&times;</button>
    </div>
    <div class="BatchMate-ec-modal-body">
        <input type="hidden" id="swal_post_id" value="0">
        <div class="BatchMate-ec-field">
            <label for="swal_post_title">Post Title <span class="BatchMate-required">*</span></label>
            <input type="text" id="swal_post_title" style="width:100%;">
        </div>
        <div class="BatchMate-ec-field">
            <label for="swal_position_order">Position Order (For Sorting)</label>
            <input type="number" id="swal_position_order" value="0" style="width:100%;">
        </div>
        <div class="BatchMate-ec-field">
            <label for="swal_position_row">Position Row (For Photo Grouping)</label>
            <input type="number" id="swal_position_row" value="0" style="width:100%;">
        </div>
    </div>
    <div class="BatchMate-ec-modal-footer">
        <button class="button BatchMate-ec-modal-cancel-btn" data-target="#BatchMate-post-modal-panel">Cancel</button>
        <button class="button button-primary" id="BatchMate-post-modal-save-btn">
            <span class="dashicons dashicons-saved" style="vertical-align:text-top;margin-top:2px;"></span> Save Post
        </button>
    </div>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {

    const ajax_url = batchmate_admin_ajax.ajax_url;
    const ec_nonce = batchmate_admin_ajax.ec_nonce;

    // Helper: Yes/No ?????????
    function renderYesNo(data) {
        return data == 1 ? 
            '<span style="color:green;font-weight:bold;">Yes</span>' : 
            '<span style="color:red;">No</span>';
    }

    // Helper: ??????? ???? (???? ????)
    function renderActions(id) {
        return `<button class="button button-primary edit-btn" data-id="${id}">Edit</button>`;
    }

    // Helper: ??????? ???? ????
    function buildOptions(data, selected_id = 0) {
        return data.map(item => 
            `<option value="${item.id}" ${item.id == selected_id ? 'selected' : ''}>
                ${item.session_year || item.post_title || item.text}
             </option>`
        ).join('');
    }

    // --- ?. ????? ????? ---
    var committeesTable = $('#committees-table').DataTable({
        "processing": true,
        "ajax": { "url": ajax_url, "type": "POST", "data": { "action": "BatchMate_get_committees", "_wpnonce": ec_nonce } },
        "order": [[ 1, "desc" ]],
        "pageLength": 25,
        "columns": [
            { "data": "id" },
            { "data": "session_year" },
            { "data": "start_date" },
            { "data": "end_date" },
            { "data": "member_count", "render": (data) => `<span class="BatchMate-badge" style="background:#e5e5e5;padding:4px 8px;border-radius:10px;">${data}</span>` },
            { "data": "is_active", "render": renderYesNo },
            { 
                "data": null,
                "orderable": false,
                "searchable": false,
                "render": function(data, type, row) {
                    var listCode = `[ec session="${row.id}" style="list"]`;
                    var photoCode = `[ec session="${row.id}" style="photo"]`;
                    return `<code style="display: block; margin-bottom: 5px; user-select: all;">${listCode}</code>
                            <code style="display: block; user-select: all;">${photoCode}</code>`;
                }
            },
            { 
                "data": "id", 
                "render": function(data, type, row) {
                     return `<button class="button button-primary edit-btn" data-id="${data}">Edit</button>
                             <button class="button print-btn" data-id="${data}" style="margin-left: 5px;">
                                 <span class="dashicons dashicons-printer" style="vertical-align: text-top;"></span> Print
                             </button>`;
                }, 
                "orderable": false, "searchable": false 
            }
        ]
    });

    // =====================================================================
    // SLIDE-IN MODAL CORE LOGIC
    // =====================================================================
    
    function openPanel(targetSelector, titleText, titleElementSelector) {
        $(titleElementSelector).text(titleText);
        $('#BatchMate-ec-member-modal-overlay').fadeIn(200);
        setTimeout(function() {
            $(targetSelector).addClass('is-open');
        }, 10);
        $('body').css('overflow', 'hidden');
    }

    function closeAllPanels() {
        $('.BatchMate-slide-panel').removeClass('is-open');
        setTimeout(function() {
            $('#BatchMate-ec-member-modal-overlay').fadeOut(200);
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

    // =====================================================================
    // 1. COMMITTEE MODAL LOGIC
    // =====================================================================
    $('#add-new-committee-btn').on('click', function(e) {
        e.preventDefault();
        $('#swal_comm_id').val(0);
        $('#swal_session_year').val('');
        $('#swal_start_date').val('');
        $('#swal_end_date').val('');
        $('#swal_is_active').val(1);
        openPanel('#BatchMate-committee-modal-panel', 'Add New Committee', '#BatchMate-comm-modal-title-text');
    });

    $('#committees-table tbody').on('click', '.edit-btn', function() {
        var data = committeesTable.row($(this).parents('tr')).data();
        $('#swal_comm_id').val(data.id);
        $('#swal_session_year').val(data.session_year || '');
        $('#swal_start_date').val(data.start_date || '');
        $('#swal_end_date').val(data.end_date || '');
        $('#swal_is_active').val(data.is_active || 0);
        openPanel('#BatchMate-committee-modal-panel', 'Edit Committee', '#BatchMate-comm-modal-title-text');
    });

    $('#BatchMate-comm-modal-save-btn').on('click', function() {
        const $btn = $(this);
        const session = $('#swal_session_year').val();
        if (!session) {
            Swal.fire({ icon: 'warning', title: 'Required', text: 'Session Year is required.', toast: true, position: 'top-end', timer: 3000, showConfirmButton: false });
            return;
        }

        $btn.prop('disabled', true).html('<span class="dashicons dashicons-update" style="animation:BatchMate-spin 1s linear infinite;"></span> Saving...');
        $.ajax({
            url: ajax_url, type: 'POST',
            data: {
                action: 'BatchMate_save_committee', _wpnonce: ec_nonce,
                id: $('#swal_comm_id').val(),
                session_year: session,
                start_date: $('#swal_start_date').val(),
                end_date: $('#swal_end_date').val(),
                is_active: $('#swal_is_active').val()
            }
        }).done(function(res) {
            if (res.success) {
                closeAllPanels();
                committeesTable.ajax.reload();
                postsTable.ajax.reload();
                Swal.fire({ icon: 'success', title: 'Saved!', timer: 1500, showConfirmButton: false, toast: true, position: 'top-end' });
            } else {
                Swal.fire('Error!', res.data || 'Could not save.', 'error');
            }
        }).always(function() {
            $btn.prop('disabled', false).html('<span class="dashicons dashicons-saved"></span> Save Committee');
        });
    });

    $('#committees-table tbody').on('click', '.print-btn', function(e) {
        e.stopPropagation(); 
        var committee_id = $(this).data('id');
        var print_url = `<?php echo home_url('/ec-print/'); ?>?committee_id=${committee_id}`;
        window.open(print_url, '_blank');
    });


    // --- ?. ????? ????? ---
    var postsTable = $('#posts-table').DataTable({
        "processing": true,
        "ajax": { "url": ajax_url, "type": "POST", "data": { "action": "BatchMate_get_ec_posts", "_wpnonce": ec_nonce } },
        "order": [[ 2, "asc" ]],
        "pageLength": 25,
        "columns": [
            { "data": "id" },
            { "data": "post_title" },
            { "data": "position_order" },
            { "data": "position_row" },
            { "data": "id", "render": renderActions, "orderable": false, "searchable": false }
        ]
    });

    // =====================================================================
    // 2. POST MODAL LOGIC
    // =====================================================================
    $('#add-new-post-btn').on('click', function(e) {
        e.preventDefault();
        $('#swal_post_id').val(0);
        $('#swal_post_title').val('');
        $('#swal_position_order').val(0);
        $('#swal_position_row').val(0);
        openPanel('#BatchMate-post-modal-panel', 'Add New Post', '#BatchMate-post-modal-title-text');
    });

    $('#posts-table tbody').on('click', '.edit-btn', function() {
        var data = postsTable.row($(this).parents('tr')).data();
        $('#swal_post_id').val(data.id);
        $('#swal_post_title').val(data.post_title || '');
        $('#swal_position_order').val(data.position_order || 0);
        $('#swal_position_row').val(data.position_row || 0);
        openPanel('#BatchMate-post-modal-panel', 'Edit Post', '#BatchMate-post-modal-title-text');
    });

    $('#BatchMate-post-modal-save-btn').on('click', function() {
        const $btn = $(this);
        const postTitle = $('#swal_post_title').val();
        if (!postTitle) {
            Swal.fire({ icon: 'warning', title: 'Required', text: 'Post Title is required.', toast: true, position: 'top-end', timer: 3000, showConfirmButton: false });
            return;
        }

        $btn.prop('disabled', true).html('<span class="dashicons dashicons-update" style="animation:BatchMate-spin 1s linear infinite;"></span> Saving...');
        $.ajax({
            url: ajax_url, type: 'POST',
            data: {
                action: 'BatchMate_save_ec_post', _wpnonce: ec_nonce,
                id: $('#swal_post_id').val(),
                post_title: postTitle,
                position_order: $('#swal_position_order').val(),
                position_row: $('#swal_position_row').val()
            }
        }).done(function(res) {
            if (res.success) {
                closeAllPanels();
                postsTable.ajax.reload();
                ecMembersTable.ajax.reload(); 
                Swal.fire({ icon: 'success', title: 'Saved!', timer: 1500, showConfirmButton: false, toast: true, position: 'top-end' });
            } else {
                Swal.fire('Error!', res.data || 'Could not save.', 'error');
            }
        }).always(function() {
            $btn.prop('disabled', false).html('<span class="dashicons dashicons-saved"></span> Save Post');
        });
    });


    // --- ?. ??? ??????? ????? ---
    var ecMembersTable = $('#ec-members-table').DataTable({
        "processing": true,
        "ajax": { "url": ajax_url, "type": "POST", "data": { "action": "BatchMate_get_ec_members", "_wpnonce": ec_nonce } },
        "order": [ [ 2, "desc" ], [ 5, "asc" ] ],
        "pageLength": 25,
        "columns": [
            { "data": "id" },
            { "data": "member_name" },
            { "data": "session_year" },
            { "data": "post_title" },
            { "data": "id", "render": renderActions, "orderable": false, "searchable": false },
            { "data": "position_order", "visible": false }
        ]
    });

    // =====================================================================
    // 3. EC MEMBER MODAL LOGIC
    // =====================================================================
    $('#batchmate_ec_panel_member, #batchmate_ec_panel_post').select2({
        dropdownParent: $('#BatchMate-ec-member-modal-panel')
    });

    $('#add-new-ec-member-btn').on('click', function(e) {
        e.preventDefault();
        $('#batchmate_ec_panel_id').val(0);
        $('#batchmate_ec_panel_committee').val('<?php echo esc_js($active_committee_id); ?>').trigger('change');
        $('#batchmate_ec_panel_post').val('').trigger('change');
        $('#batchmate_ec_panel_member').val('').trigger('change');
        openPanel('#BatchMate-ec-member-modal-panel', 'Add New EC Member', '#BatchMate-ec-modal-title-text');
    });

    $('#ec-members-table tbody').on('click', '.edit-btn', function() {
        var data = ecMembersTable.row($(this).parents('tr')).data();
        $('#batchmate_ec_panel_id').val(data.id);
        $('#batchmate_ec_panel_committee').val(data.committee_id);
        $('#batchmate_ec_panel_post').val(data.post_id).trigger('change');
        $('#batchmate_ec_panel_member').val(data.member_id).trigger('change');
        openPanel('#BatchMate-ec-member-modal-panel', 'Edit EC Member', '#BatchMate-ec-modal-title-text');
    });

    $('#BatchMate-ec-modal-save-btn').on('click', function() {
        const $btn = $(this);
        const commId = $('#batchmate_ec_panel_committee').val();
        const postId = $('#batchmate_ec_panel_post').val();
        const memId  = $('#batchmate_ec_panel_member').val();
        const ecId   = $('#batchmate_ec_panel_id').val();

        if (!commId || !postId || !memId) {
            Swal.fire({ icon: 'warning', title: 'Required', text: 'All fields are required.', toast: true, position: 'top-end', timer: 3000, showConfirmButton: false });
            return;
        }

        $btn.prop('disabled', true).html('<span class="dashicons dashicons-update" style="animation:BatchMate-spin 1s linear infinite;"></span> Saving...');
        $.ajax({
            url: ajax_url, type: 'POST',
            data: {
                action: 'BatchMate_save_ec_member', _wpnonce: ec_nonce,
                id: ecId, committee_id: commId, post_id: postId, member_id: memId
            }
        }).done(function(res) {
            if (res.success) {
                closeAllPanels();
                ecMembersTable.ajax.reload();
                Swal.fire({ icon: 'success', title: 'Saved!', timer: 1500, showConfirmButton: false, toast: true, position: 'top-end' });
            } else {
                Swal.fire('Error!', res.data || 'Could not save.', 'error');
            }
        }).always(function() {
            $btn.prop('disabled', false).html('<span class="dashicons dashicons-saved"></span> Save Member');
        });
    });

});
</script>