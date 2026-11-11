<div class="BatchMate-ev-tab-content <?php echo $req_tab === 'tab-applications' ? 'active' : ''; ?>" id="tab-applications" style="<?php echo $req_tab === 'tab-applications' ? 'display:block;' : 'display:none;'; ?>">
        <div class="BatchMate-ev-toolbar">
            <div class="BatchMate-ev-toolbar-left">
                <label class="BatchMate-ev-toolbar-label">Filter by Event:</label>
                <select id="filter-app-event" class="BatchMate-ev-select">
                    <?php foreach ( $events_for_filter as $ev ) : ?>
                        <option value="<?php echo esc_attr( $ev->id ); ?>" <?php selected( $ev->is_active, 1 ); ?>><?php echo esc_html( $ev->event_name ); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="BatchMate-ev-table-wrap">
            <table id="event-applications-table" class="display wp-list-table widefat striped" style="width:100%">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Member ID</th>
                        <th>Name</th>
                        <th>Photo</th>
                        
                        
                        <th>Payment Method</th>
                        <th>Payment Details</th>
                        <th>Amount (BDT)</th>
                        <th>Guests</th>
                        <th>Event</th>
                        <th>Status</th>
                          <th></th>
                          <th style="width:110px">Action</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>