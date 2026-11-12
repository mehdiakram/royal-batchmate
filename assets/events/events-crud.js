/**
 * Royal BatchMate - Events List & Create/Edit Modal
 */
jQuery(document).ready(function($) {
    const BM          = window.BatchMateEvents = window.BatchMateEvents || {};
    const ajax_url    = BM.ajax_url;
    const event_nonce = BM.event_nonce;
    const noPhoto     = BM.noPhoto;

    var evtTable = BM.evtTable = $('#event-list-table').DataTable({
        processing: true,
        ajax: { url: ajax_url, type: 'POST', data: { action: 'BatchMate_get_events', _wpnonce: event_nonce } },
        order: [[0, 'desc']],
        columns: [
            { data: 'id' },
            { data: 'event_name' },
            { data: 'event_date' },
            { data: 'is_active', render: function(d, t, row){
                var statusHtml = d == 1 ? '<span class="BatchMate-status-active">Active</span>' : '<span class="BatchMate-status-inactive">Inactive</span>';
                var nmHtml = '';
                if (row.allow_non_members == 2) {
                    nmHtml = '<div style="margin-top:4px;"><span style="display:inline-block;font-size:10px;font-weight:700;padding:2px 7px;border-radius:4px;background:#ecfdf5;color:#059669;border:1px solid #a7f3d0;">Auto Member</span></div>';
                } else if (row.allow_non_members == 1) {
                    nmHtml = '<div style="margin-top:4px;"><span style="display:inline-block;font-size:10px;font-weight:600;padding:2px 7px;border-radius:4px;background:#f8fafc;color:#64748b;border:1px solid #e2e8f0;">Non-Members</span></div>';
                } else {
                    nmHtml = '<div style="margin-top:4px;"><span style="display:inline-block;font-size:10px;font-weight:600;padding:2px 7px;border-radius:4px;background:#eff6ff;color:#2563eb;border:1px solid #bfdbfe;">Members Only</span></div>';
                }
                return statusHtml + nmHtml;
            }},
            { data: 'id', orderable: false, render: function(d){
                var sc  = '[event_form id="'+d+'"]';
                var enc = sc.replace(/"/g, '&quot;');
                return '<span class="BatchMate-shortcode-chip copy-sc" data-sc="'+enc+'"><span class="dashicons dashicons-clipboard"></span>'+sc+'</span>';
            }},
            { data: 'id', orderable: false, render: function(d){
                var sc  = '[event_list id="'+d+'"]';
                var enc = sc.replace(/"/g, '&quot;');
                return '<span class="BatchMate-shortcode-chip copy-sc" data-sc="'+enc+'"><span class="dashicons dashicons-clipboard"></span>'+sc+'</span>';
            }},
            { data: null, orderable: false, searchable: false, render: function(d, t, row) {
                return `<div class="BatchMate-actions-btn-container">
                    <button class="BatchMate-actions-btn">Actions</button>
                    <div class="BatchMate-actions-dropdown">
                        <button class="button edit-event-btn"
                            data-id="${row.id}"
                            data-name="${row.event_name.replace(/"/g,'&quot;')}"
                            data-date="${row.event_date}"
                            data-active="${row.is_active}"
                            data-location="${(row.location||'').replace(/"/g,'&quot;')}"
                            data-registeredurl="${(row.registered_url||'').replace(/"/g,'&quot;')}"
                            data-allownonmembers="${row.allow_non_members}"
                            data-directregtext="${(row.direct_reg_text||'').replace(/"/g,'&quot;')}"
                            data-hasidcard="${row.has_id_card}"
                            data-autosendcard="${row.auto_send_card}"
                            data-hastshirt="${row.has_tshirt||0}"
                            data-hassaree="${row.has_saree||0}"
                            data-paymentmethods="${(row.payment_methods||'').replace(/"/g,'&quot;')}"
                            data-ccemail="${(row.cc_email||'').replace(/"/g,'&quot;')}"
                            data-cardtemplateurl="${(row.card_template_url||'').replace(/"/g,'&quot;')}"
                            data-cardzonesjson="${(row.card_zones_json||'').replace(/"/g,'&quot;')}">
                            <span class="dashicons dashicons-edit" style="margin-right:4px"></span>Edit
                        </button>
                        <button class="button card-setup-btn" style="color:#0a7242;"
                            data-id="${row.id}"
                            data-name="${row.event_name.replace(/"/g,'&quot;')}"
                            data-cardtemplateurl="${(row.card_template_url||'').replace(/"/g,'&quot;')}"
                            data-cardzonesjson="${(row.card_zones_json||'').replace(/"/g,'&quot;')}">
                            <span class="dashicons dashicons-id" style="margin-right:4px"></span>Card Setup
                        </button>
                        <button class="button view-event-btn"
                            data-id="${row.id}"
                            data-name="${row.event_name.replace(/"/g,'&quot;')}"
                            data-date="${row.event_date}"
                            data-active="${row.is_active}"
                            data-location="${(row.location||'').replace(/"/g,'&quot;')}"
                            style="color:#2271b1">
                            <span class="dashicons dashicons-visibility" style="margin-right:4px"></span>View
                        </button>
                    </div>
                </div>`;
            }}
        ]
    });

    
    $('#event-list-table tbody').on('click', '.copy-sc', function() {
        var sc = $(this).attr('data-sc').replace(/&quot;/g, '"');
        BM.copyToClipboard(sc, $(this));
    });

    

    
    $('#event-list-table tbody').on('click', '.view-event-btn', function() {
        var $b = $(this);
        var event_id = $b.data('id');
        var event_name = $b.data('name');
        var event_date = $b.data('date');
        var is_active = $b.data('active');
        var event_location = $b.data('location');
        
        Swal.fire({
            title: 'Loading...',
            didOpen: function() {
                Swal.showLoading();
                $.ajax({ url: ajax_url, type: 'POST',
                    data: { action: 'BatchMate_get_event_options', event_id: event_id, _wpnonce: event_nonce }
                }).done(function(res){
                    if (res.success && res.data) {
                        var fee = parseFloat(res.data.member_fee||0).toFixed(2);
                        var html = '<div style="text-align:left; font-size:14px; line-height:1.6; padding:10px;">';
                        html += '<p><strong>Event Name:</strong> ' + event_name + '</p>';
                        html += '<p><strong>Event Date:</strong> ' + event_date + '</p>';
                        if (event_location) html += '<p><strong>Location:</strong> ' + event_location + '</p>';
                        html += '<p><strong>Status:</strong> ' + (is_active==1?'Active':'Inactive') + '</p>';
                        html += '<p><strong>Member Base Fee:</strong> &#2547; ' + fee + '</p>';
                        if (res.data.options && res.data.options.length) {
                            html += '<hr style="margin:12px 0; border:0; border-top:1px solid #eee;"><p><strong>Guest Options:</strong></p><ul style="margin-top:5px; padding-left:20px; color:#555;">';
                            res.data.options.forEach(function(o){
                                var max = parseInt(o.max_qty) > 0 ? ' (Max: '+o.max_qty+')' : '';
                                html += '<li>' + o.option_name + ' — &#2547; '+parseFloat(o.fee).toFixed(2) + max + '</li>';
                            });
                            html += '</ul>';
                        }
                        html += '</div>';
                        
                        Swal.fire({
                            title: 'Event Details',
                            html: html,
                            icon: 'info',
                            confirmButtonText: 'Close'
                        });
                    } else {
                        Swal.fire('Error', 'Could not load event info.', 'error');
                    }
                }).fail(function(){
                    Swal.fire('Error', 'Request failed.', 'error');
                });
            }
        });
    });


    
    
    
    function addOptionRow(name, fee, max_qty) {
        var row = $('<div class="event-option-row">'+
            '<input type="text" class="opt-name" placeholder="e.g. Adult" value="'+(name||'')+'">' +
            '<span class="lbl">Fee &#2547;</span>' +
            '<input type="number" class="fee-input" placeholder="300" value="'+(fee||'')+'" min="0" step="0.01">' +
            '<span class="lbl">Max</span>' +
            '<input type="number" class="maxqty-input" placeholder="0" value="'+(max_qty||0)+'" min="0">' +
            '<button type="button" class="remove-opt" title="Remove">&times;</button>'+
        '</div>');
        $('#event-options-list').append(row);
    }
    $('#add-event-option').on('click', function(){ addOptionRow('','',''); });
    $(document).on('click', '.remove-opt', function(){ $(this).closest('.event-option-row').remove(); });



    $('#btn-add-event').on('click', function(){
        $('#modal_card_template_url').val('');
        $('#modal_card_zones_json').val('');
        $('#card-zone-editor').hide();
        $('#card-template-upload-name').text('');
        $('#card-template-upload-area .dashicons').css('color','#c3c4c7');
        $('#card-template-upload-area p:first-of-type').text('Click to upload template image');
    });

    
    function openModal() {
        $('#BatchMate-event-modal-overlay').fadeIn(200);
        $('#BatchMate-event-modal').addClass('open');
    }
    function closeModal() {
        $('#BatchMate-event-modal').removeClass('open');
        $('#BatchMate-event-modal-overlay').fadeOut(200);
        $('#BatchMate-event-form')[0].reset();
        $('#modal_event_id').val(0);
        $('#modal_card_template_url').val('');
        $('#modal_card_zones_json').val('');
        $('#modal_event_location').val('');
        $('#modal_event_registered_url').val('');
        $('#modal_event_allow_non_members').val('0');
        $('#modal_event_direct_reg_text').val('');
        $('#modal_event_has_id_card').prop('checked', false);
        $('#modal_event_auto_send_card').prop('checked', false);
        $('#modal_event_has_tshirt').prop('checked', false);
        $('#modal_event_has_saree').prop('checked', false);
        $('#modal_event_payment_methods').val('');
        $('#modal_event_cc_email').val('');
        $('#event-options-list').empty();
        $('#BatchMate-event-modal-title').text('New Event');
    }

    $('#btn-new-event').on('click', openModal);
    $('#BatchMate-event-modal-close, #BatchMate-event-modal-cancel').on('click', closeModal);

    $('#BatchMate-event-modal-save').on('click', function() {
        var name = $.trim($('#modal_event_name').val());
        var date = $('#modal_event_date').val();
        if (!name || !date) { Swal.fire('Validation', 'Event name and date are required.', 'warning'); return; }

        
        var opts = [];
        $('#event-options-list .event-option-row').each(function(){
            var n = $.trim($(this).find('.opt-name').val());
            if (!n) return;
            opts.push({ name: n, fee: $(this).find('.fee-input').val()||0, max_qty: $(this).find('.maxqty-input').val()||0 });
        });

        $(this).prop('disabled', true).text('Saving…');
        $.ajax({
            url: ajax_url, type: 'POST',
            data: {
                action: 'BatchMate_save_event', _wpnonce: event_nonce,
                event_id: $('#modal_event_id').val(),
                event_name: name, event_date: date,
                location: $.trim($('#modal_event_location').val()),
                registered_url: $.trim($('#modal_event_registered_url').val()),
                card_template_url: $('#modal_card_template_url').val() || '',
                card_zones_json: $('#modal_card_zones_json').val() || '',
                member_fee: $('#modal_event_fee').val() || 0,
                is_active: $('#modal_event_active').is(':checked') ? 1 : 0,
                allow_non_members: parseInt($('#modal_event_allow_non_members').val()) || 0,
                direct_reg_text: $.trim($('#modal_event_direct_reg_text').val()),
                has_id_card: $('#modal_event_has_id_card').is(':checked') ? 1 : 0,
                auto_send_card: $('#modal_event_auto_send_card').is(':checked') ? 1 : 0,
                has_tshirt: $('#modal_event_has_tshirt').is(':checked') ? 1 : 0,
                has_saree: $('#modal_event_has_saree').is(':checked') ? 1 : 0,
                payment_methods: $.trim($('#modal_event_payment_methods').val()),
                cc_email: $.trim($('#modal_event_cc_email').val()),
                'event_options': opts
            }
        }).done(function(res) {
            if (res.success) {
                Swal.fire({ title: 'Saved!', icon: 'success', timer: 1400, showConfirmButton: false });
                closeModal(); evtTable.ajax.reload(null, false);
                setTimeout(function(){ location.reload(); }, 1500);
            } else { Swal.fire('Error!', res.data, 'error'); }
        }).always(function(){
            $('#BatchMate-event-modal-save').prop('disabled', false).html('<span class="dashicons dashicons-saved"></span> Save Event');
        });
    });

    
    $('#event-list-table tbody').on('click', '.edit-event-btn', function() {
        var $b = $(this);
        $('#modal_event_id').val($b.data('id'));
        $('#modal_event_name').val($b.data('name'));
        $('#modal_event_date').val($b.data('date'));
        $('#modal_event_location').val($b.data('location'));
        $('#modal_event_registered_url').val($b.data('registeredurl'));
        $('#modal_event_active').prop('checked', $b.data('active') == 1);
        $('#modal_event_allow_non_members').val(String($b.data('allownonmembers') || 0));
        $('#modal_event_direct_reg_text').val($b.attr('data-directregtext') || '');
        $('#modal_event_has_id_card').prop('checked', $b.data('hasidcard') == 1);
        $('#modal_event_auto_send_card').prop('checked', $b.data('autosendcard') == 1);
        $('#modal_event_has_tshirt').prop('checked', $b.data('hastshirt') == 1);
        $('#modal_event_has_saree').prop('checked', $b.data('hassaree') == 1);
        $('#modal_event_payment_methods').val($b.attr('data-paymentmethods') || '');
        $('#modal_event_cc_email').val($b.attr('data-ccemail') || $b.data('ccemail') || '');
        $('#modal_card_template_url').val($b.attr('data-cardtemplateurl') || '');
        $('#modal_card_zones_json').val($b.attr('data-cardzonesjson') || '');
        $('#BatchMate-event-modal-title').text('Edit Event');
        $('#event-options-list').empty();
        
        $.ajax({ url: ajax_url, type: 'POST',
            data: { action: 'BatchMate_get_event_options', event_id: $b.data('id'), _wpnonce: event_nonce }
        }).done(function(res){
            if (res.success && res.data) {
                $('#modal_event_fee').val(parseFloat(res.data.member_fee||0).toFixed(2));
                if (res.data.options) {
                    res.data.options.forEach(function(o){ addOptionRow(o.option_name, o.fee, o.max_qty); });
                }
            }
        });
        openModal();
    });


});
