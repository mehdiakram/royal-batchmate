<div class="BatchMate-ev-tab-content <?php echo $req_tab === 'tab-events' ? 'active' : ''; ?>" id="tab-events" style="<?php echo $req_tab === 'tab-events' ? 'display:block;' : 'display:none;'; ?>">
        <div class="BatchMate-ev-toolbar">
            <div class="BatchMate-ev-toolbar-left">
                <span style="color:#555;font-size:13px;">Create and manage events, copy shortcodes.</span>
            </div>
            <div class="BatchMate-ev-toolbar-right">
                <button id="btn-new-event" class="BatchMate-ev-btn BatchMate-ev-btn--primary">
                    <span class="dashicons dashicons-plus-alt2"></span> New Event
                </button>
            </div>
        </div>

        <div class="BatchMate-ev-table-wrap">
            <table id="event-list-table" class="display wp-list-table widefat striped" style="width:100%">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Event Name</th>
                        <th>Date</th>
                        <th>Status</th>
                        <th>Form Shortcode</th>
                        <th>List Shortcode</th>
                        <th style="width:110px">Actions</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>