/**
 * Royal BatchMate - Events Applications & Edit Modal
 */
jQuery(document).ready(function($) {
    const BM          = window.BatchMateEvents = window.BatchMateEvents || {};
    const ajax_url    = BM.ajax_url;
    const event_nonce = BM.event_nonce;
    const noPhoto     = BM.noPhoto;

    
    
    
    var appTable = BM.appTable = $('#event-applications-table').DataTable({
        processing: true,
        autoWidth: false,
        order: [[10, 'asc'], [0, 'desc']],
        ajax: {
            url: ajax_url, type: 'POST',
            data: function(d) {
                d.action   = 'BatchMate_get_event_applications';
                d._wpnonce = event_nonce;
                d.event_id = $('#filter-app-event').val();
            }
        },
        columns: [
            { data: 'id', width: '48px', render: function(d){
                return '<span style="font-weight:700;color:#2271b1;">' + d + '</span>';
            }},
            { data: 'member_number', width: '90px' },
            { data: 'name' },
            { data: 'photo_url', width: '52px', orderable: false, render: function(d){
                return '<img src="'+(d||noPhoto)+'" class="evt-photo">';
            }},
            
            
            { data: 'payment_method' },
            { data: 'payment_details' },
            { data: 'amount', render: function(d){ return '&#2547; '+parseFloat(d||0).toFixed(2); }},
            { data: 'options_json', orderable:false, render: function(d){
                if (!d) return '<span style="color:#aaa">—</span>';
                try {
                    var cleanD = typeof d === 'string' ? d.replace(/\\"/g, '"').replace(/\\\\/g, '\\') : d;
                    var opts = typeof cleanD === 'string' ? JSON.parse(cleanD) : cleanD;
                    var arr = [];
                    opts.forEach(function(o){
                        if(parseInt(o.qty) > 0) arr.push(o.name + ': ' + o.qty);
                    });
                    return arr.length ? arr.join(', ') : '<span style="color:#aaa">—</span>';
                } catch(e){ return '<span style="color:#aaa">—</span>'; }
            }},
            { data: 'event_name' },
            { data: 'status', render: function(d){
                return '<span class="BatchMate-status-'+d+'">'+d.charAt(0).toUpperCase()+d.slice(1)+'</span>';
            }},
            
            { data: 'status', visible: false, searchable: false, render: function(d){
                return d === 'pending' ? 0 : (d === 'approved' ? 1 : 2);
            }},
            { data: null, orderable: false, searchable: false, render: function(d, t, row) {
                var guestsText = '—';
                try {
                    if (row.options_json) {
                        var cleanOpts = typeof row.options_json === 'string' ? row.options_json.replace(/\\"/g, '"') : row.options_json;
                        var parsedOpts = typeof cleanOpts === 'string' ? JSON.parse(cleanOpts) : cleanOpts;
                        var gArr = [];
                        parsedOpts.forEach(function(o){ if(parseInt(o.qty)>0) gArr.push(o.name+': '+o.qty); });
                        if (gArr.length) guestsText = gArr.join(', ');
                    }
                } catch(e){}
                var previewData = {
                    id: row.id,
                    name: row.name,
                    member_number: row.member_number,
                    photo_url: row.photo_url,
                    
                    student_id: row.student_id,
                    cell_number: row.cell_number,
                    email: row.email,
                    blood_group: row.blood_group,
                    gender: row.gender,
                    is_guest: (parseInt(row.rawdata_id) > 0),
                    payment_method: row.payment_method,
                    payment_details: row.payment_details,
                    amount: row.amount,
                    tshirt_size: row.tshirt_size || '',
                    has_tshirt: parseInt(row.has_tshirt) === 1,
                    guests: guestsText,
                    event_name: row.event_name,
                    status: row.status,
                    applied_at: row.applied_at
                };
                var previewJson = JSON.stringify(previewData).replace(/'/g, '&#39;').replace(/"/g, '&quot;');
                return `<div class="BatchMate-actions-btn-container">
                    <button class="BatchMate-actions-btn">Actions</button>
                    <div class="BatchMate-actions-dropdown">
                        <button class="button preview-app-btn" data-appinfo="${previewJson}">
                            <span class="dashicons dashicons-visibility" style="margin-right:4px"></span>Preview
                        </button>
                        <button class="button edit-app-btn"
                            data-id="${row.id}"
                            data-method="${row.payment_method}"
                            data-details="${(row.payment_details||'').replace(/"/g,'&quot;')}"
                            data-amount="${row.amount||0}"
                            data-eventid="${row.event_id}"
                            data-tshirt="${row.tshirt_size||''}"
                            data-options='${row.options_json||"null"}'>
                            <span class="dashicons dashicons-edit" style="margin-right:4px"></span>Edit
                        </button>
                        ${row.status === 'pending' ? `
                        <button class="button approve-event-btn" data-id="${row.id}">
                            <span class="dashicons dashicons-yes" style="margin-right:4px"></span>Approve
                        </button>
                        <button class="button reject-event-btn" data-id="${row.id}" style="color:#d63638">
                            <span class="dashicons dashicons-no" style="margin-right:4px"></span>Reject
                        </button>` : ''}
                    </div>
                </div>`;
            }}
        ]
    });

    $('#filter-app-event').on('change', function() { appTable.ajax.reload(null, false); });

    
    $('#event-applications-table tbody').on('click', '.approve-event-btn', function() {
        var app_id = $(this).data('id');
        Swal.fire({
            title: 'Approve Application?',
            text: 'The member will be added to the registered list.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#0a7242',
            confirmButtonText: 'Approve',
            showLoaderOnConfirm: true,
            preConfirm: function() {
                return $.ajax({
                    url: ajax_url,
                    type: 'POST',
                    data: { action: 'BatchMate_approve_event_application', app_id: app_id, _wpnonce: event_nonce }
                }).then(function(res) {
                    if (!res || !res.success) {
                        var errMsg = (res && res.data) ? res.data : 'Approval failed.';
                        if (typeof errMsg === 'string' && errMsg.indexOf('Status updated to approved') !== -1) {
                            appTable.ajax.reload(null, false);
                            BM.regTable.ajax.reload(null, false);
                        }
                        Swal.showValidationMessage(errMsg);
                        return false;
                    }
                    return res;
                }, function(xhr) {
                    var msg = 'Request failed.';
                    if (xhr.responseJSON && xhr.responseJSON.data) {
                        msg = xhr.responseJSON.data;
                    } else if (xhr.responseText) {
                        try {
                            var json = JSON.parse(xhr.responseText);
                            if (json && json.data) msg = json.data;
                            else if (typeof json === 'string') msg = json;
                        } catch(e) {
                            var text = xhr.responseText.replace(/<[^>]+>/g, '').trim();
                            if (text) {
                                msg = text.length > 200 ? text.substring(0, 200) + '...' : text;
                            } else {
                                msg = 'Server Error (' + (xhr.status || 'unknown') + ')';
                            }
                        }
                    }
                    Swal.showValidationMessage(msg);
                    return false;
                });
            }
        }).then(function(result) {
            if (result.isConfirmed && result.value && result.value.success) {
                Swal.fire({ title: 'Approved!', text: result.value.data || '', icon: 'success', timer: 1800, showConfirmButton: false });
                appTable.ajax.reload(null, false);
                BM.regTable.ajax.reload(null, false);
            }
        });
    });

    
    $('#event-applications-table tbody').on('click', '.reject-event-btn', function() {
        var app_id = $(this).data('id');
        Swal.fire({
            title: 'Reject this application?',
            icon: 'warning', showCancelButton: true,
            confirmButtonColor: '#d63638', confirmButtonText: 'Yes, Reject'
        }).then(function(result) {
            if (!result.isConfirmed) return;
            $.ajax({ url: ajax_url, type: 'POST',
                data: { action: 'BatchMate_reject_event_application', app_id: app_id, _wpnonce: event_nonce }
            }).done(function(res) {
                if (res.success) {
                    Swal.fire({ title: 'Rejected!', icon: 'success', timer: 1200, showConfirmButton: false });
                    appTable.ajax.reload(null, false);
                } else { Swal.fire('Error!', res.data, 'error'); }
            });
        });
    });


    
    var noPhotoUrl = BM.noPhotoUrl;
    $('#event-applications-table tbody').on('click', '.preview-app-btn', function() {
        var info;
        try { info = JSON.parse($(this).attr('data-appinfo').replace(/&#39;/g, "'").replace(/&quot;/g, '"')); }
        catch(e){ return; }

        
        var statusColour = info.status === 'approved' ? '#16a34a' : (info.status === 'rejected' ? '#dc2626' : '#d97706');
        var statusBg    = info.status === 'approved' ? '#f0fdf4' : (info.status === 'rejected' ? '#fef2f2' : '#fffbeb');

        
        var appliedAt = '—';
        if (info.applied_at) {
            var d = new Date(info.applied_at);
            if (!isNaN(d)) appliedAt = d.toLocaleString('en-GB', {day:'2-digit',month:'short',year:'numeric',hour:'2-digit',minute:'2-digit'});
        }


        var isGuest = info.is_guest || false;
        $('#apppreview-member-num').html(
            isGuest
                ? '<span style="background:rgba(255,255,255,.2);padding:2px 8px;border-radius:10px;font-size:11px;">Non-Member</span>'
                : 'Member ID: ' + (info.member_number || '')
        );

        
        var rows = [];

        
        function sectionHdr(label) {
            return '<tr><td colspan="2" style="padding:8px 14px 4px;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.8px;color:#6b7280;background:#f3f4f6;border-bottom:1px solid #e5e7eb;">' + label + '</td></tr>';
        }

        
        rows.push(sectionHdr('Application Info'));
        rows.push(['Application ID', '<strong style="color:#2271b1">#' + info.id + '</strong>']);
        rows.push(['Status', '<span style="background:'+statusBg+';color:'+statusColour+';padding:3px 10px;border-radius:20px;font-weight:700;font-size:12px;">' + info.status.charAt(0).toUpperCase() + info.status.slice(1) + '</span>']);
        rows.push(['Event', info.event_name || '—']);
        rows.push(['Applied At', appliedAt]);

        
        rows.push(sectionHdr('Applicant Details'));
        rows.push(['Name', info.name || '—']);
        if (info.student_id) rows.push(['Student ID', info.student_id]);
        if (isGuest && info.cell_number) rows.push(['Mobile Number', info.cell_number]);
        if (isGuest && info.email)       rows.push(['Email', '<a href="mailto:'+info.email+'" style="color:#2271b1;">'+info.email+'</a>']);
        rows.push(['Batch', info.batch || '—']);
        rows.push(['Program', info.program_name || '—']);
        if (isGuest && info.blood_group) rows.push(['Blood Group', info.blood_group]);
        if (isGuest && info.gender)      rows.push(['Gender', info.gender]);

        
        rows.push(sectionHdr('Payment Info'));
        rows.push(['Payment Method', info.payment_method || '—']);
        rows.push(['Transaction / Ref ID', info.payment_details || '—']);
        rows.push(['Amount (BDT)', '&#2547; ' + parseFloat(info.amount || 0).toFixed(2)]);
        if (info.has_tshirt) {
            if (info.tshirt_size === 'Saree') {
                rows.push(['Gift Choice', '<span style="background:#fce7f3;color:#be185d;padding:3px 10px;border-radius:20px;font-weight:700;font-size:12px;">👘 Saree</span>']);
            } else if (info.tshirt_size && info.tshirt_size !== '' && info.tshirt_size !== 'NA') {
                rows.push(['T-Shirt Size', '<span style="background:#fef3c7;color:#d97706;padding:3px 10px;border-radius:20px;font-weight:700;font-size:12px;">' + info.tshirt_size + '</span>']);
            } else {
                rows.push(['T-Shirt Size', '<span style="color:#94a3b8;font-style:italic;">N/A (not provided)</span>']);
            }
        }
        if (info.guests && info.guests !== '—') rows.push(['Guests', info.guests]);

        var tbodyHtml = rows.map(function(r, i){
            
            if (typeof r === 'string') return r;
            var bg = (i % 2 === 0) ? '#f8fafc' : '#fff';
            return '<tr style="background:'+bg+';">' +
                '<td style="padding:9px 14px;font-weight:600;color:#374151;width:40%;border-bottom:1px solid #f0f0f1;">' + r[0] + '</td>' +
                '<td style="padding:9px 14px;color:#1f2937;border-bottom:1px solid #f0f0f1;">' + r[1] + '</td>' +
                '</tr>';
        }).join('');

        $('#apppreview-tbody').html(tbodyHtml);
        $('#apppreview-title').text(info.name || 'Applicant');
        $('#apppreview-photo').attr('src', info.photo_url || noPhotoUrl);

        $('#BatchMate-apppreview-overlay, #BatchMate-apppreview-modal').css('display','flex');
        $('body').css('overflow','hidden');
    });

    function closeAppPreview() {
        $('#BatchMate-apppreview-overlay, #BatchMate-apppreview-modal').css('display','none');
        $('body').css('overflow','');
    }
    $('#apppreview-close, #apppreview-close-btn').on('click', closeAppPreview);


    
    


    
    var _editAppEventOptions = []; 
    var _editAppMemberFee = 0;
    var _cachedEventOptions = {};
    var _editAppCurrentRow = null; 

    function renderEditAppOptions(row, data) {
        if (!data || !data.options || !data.options.length) {
            $('#editapp-options-section').hide();
            return;
        }
        _editAppEventOptions = data.options;
        _editAppMemberFee = parseFloat(data.member_fee) || 0;

        var $body = $('#editapp-opts-body');
        $body.empty();
        
        var savedQty = {};
        try { var saved = JSON.parse(row.options_json||'[]');
            saved.forEach(function(o){ savedQty[o.option_id] = o.qty; }); } catch(e){}

        data.options.forEach(function(opt){
            var qty = savedQty[opt.id] || 0;
            var max = parseInt(opt.max_qty) || 0;
            var subtotal = qty * parseFloat(opt.fee);
            $body.append('<tr data-optid="'+opt.id+'" data-fee="'+opt.fee+'" data-max="'+max+'">' +
                '<td>'+opt.option_name+'</td>' +
                '<td>&#2547; '+parseFloat(opt.fee).toFixed(2)+'</td>' +
                '<td>'+(max>0?max:'8')+'</td>' +
                '<td><input type="number" class="ea-qty-inp" value="'+qty+'" min="0"'+(max>0?' max="'+max+'"':'')+' step="1"></td>' +
                '<td class="ea-sub">&#2547; '+subtotal.toFixed(2)+'</td>' +
            '</tr>');
        });
        
        recalcEditTotal();
        $('#editapp-options-section').show();
    }

    function openEditApp(row) {
        _editAppCurrentRow = row; 
        $('#editapp_id').val(row.id);
        $('#editapp_method').val(row.method);
        $('#editapp_details').val(row.details);
        $('#editapp_amount').val(parseFloat(row.amount||0).toFixed(2));

        $('#editapp-opts-body').empty();
        $('#editapp-options-section').hide();
        $('#editapp-tshirt-section').hide();
        $('#editapp_tshirt_size').val('XL');
        _editAppEventOptions = [];
        _editAppMemberFee = 0;

        var loadOptions = function(data) {
            renderEditAppOptions(row, data);
            
            if (data && data.has_tshirt) {
                var currentSize = row.tshirt || '';
                if (!currentSize) {
                    
                    $('#editapp_tshirt_size').val('NA');
                    $('#editapp-tshirt-na-hint').show();
                } else {
                    $('#editapp_tshirt_size').val(currentSize);
                    $('#editapp-tshirt-na-hint').hide();
                }
                $('#editapp-tshirt-section').show();
            } else {
                $('#editapp-tshirt-section').hide();
                $('#editapp-tshirt-na-hint').hide();
            }
        };

        if (_cachedEventOptions[row.event_id]) {
            loadOptions(_cachedEventOptions[row.event_id]);
        } else {
            $.ajax({ url: ajax_url, type: 'POST',
                data: { action: 'BatchMate_get_event_options', event_id: row.event_id, _wpnonce: event_nonce }
            }).done(function(res){
                if (res.success && res.data) {
                    _cachedEventOptions[row.event_id] = res.data;
                    loadOptions(res.data);
                }
            });
        }

        $('#BatchMate-editapp-overlay').fadeIn(200);
        $('#BatchMate-editapp-modal').addClass('open');
    }

    function recalcEditTotal() {
        var guestTotal = 0;
        $('#editapp-opts-body tr').each(function(){
            var fee = parseFloat($(this).data('fee')) || 0;
            var qty = parseInt($(this).find('.ea-qty-inp').val()) || 0;
            var sub = fee * qty;
            $(this).find('.ea-sub').html('&#2547; '+sub.toFixed(2));
            guestTotal += sub;
        });
        $('#editapp-opts-total').html('&#2547; '+guestTotal.toFixed(2));
        
        var totalShouldBe = _editAppMemberFee + guestTotal;
        $('#editapp-calc-total').html('<span class="dashicons dashicons-info" style="font-size:16px;width:16px;height:16px;vertical-align:text-bottom;"></span> Calculated Amount Should Be: <strong>&#2547; ' + totalShouldBe.toFixed(2) + '</strong>').show();
    }

    $(document).on('input', '.ea-qty-inp', recalcEditTotal);

    $('#event-applications-table tbody, #event-registered-table tbody').on('click', '.edit-app-btn', function(){
        var $b = $(this);
        openEditApp({
            id:          $b.data('id'),
            method:      $b.data('method'),
            details:     $b.data('details'),
            amount:      $b.data('amount'),
            event_id:    $b.data('eventid'),
            tshirt:      $b.data('tshirt') || '',
            options_json:$b.attr('data-options')
        });
    });

    function closeEditApp() {
        $('#BatchMate-editapp-modal').removeClass('open');
        $('#BatchMate-editapp-overlay').fadeOut(200);
        $('#editapp-tshirt-section').hide();
        $('#editapp_tshirt_size').val('NA');
        $('#editapp-tshirt-na-hint').hide();
    }
    $('#BatchMate-editapp-close, #BatchMate-editapp-cancel').on('click', closeEditApp);

    
    $('#editapp_tshirt_size').on('change', function(){
        if ($(this).val() === 'NA') {
            $('#editapp-tshirt-na-hint').show();
        } else {
            $('#editapp-tshirt-na-hint').hide();
        }
    });

    $('#BatchMate-editapp-save').on('click', function(){
        var appId  = $('#editapp_id').val();
        var method = $('#editapp_method').val();
        var details= $.trim($('#editapp_details').val());
        var amount = $('#editapp_amount').val();

        
        var optsArr = [];
        var hasOptionsUI = ($('#editapp-opts-body tr').length > 0);
        $('#editapp-opts-body tr').each(function(){
            var optId = parseInt($(this).data('optid'));
            var fee   = parseFloat($(this).data('fee'));
            var qty   = parseInt($(this).find('.ea-qty-inp').val()) || 0;
            var optData = _editAppEventOptions.find(function(o){ return o.id == optId; });
            optsArr.push({ option_id: optId, name: (optData?optData.option_name:''), qty: qty, fee: fee, subtotal: fee*qty });
        });
        
        var options_json;
        if (hasOptionsUI) {
            options_json = JSON.stringify(optsArr);
        } else {
            
            var existingOpts = (_editAppCurrentRow && _editAppCurrentRow.options_json) ? _editAppCurrentRow.options_json : null;
            options_json = existingOpts;
        }

        
        var tshirt_val;
        if ($('#editapp-tshirt-section').is(':visible')) {
            tshirt_val = $('#editapp_tshirt_size').val();
        } else {
            tshirt_val = (_editAppCurrentRow && _editAppCurrentRow.tshirt) ? _editAppCurrentRow.tshirt : '';
        }

        $(this).prop('disabled', true).text('Saving…');
        $.ajax({ url: ajax_url, type: 'POST',
            data: { action: 'BatchMate_edit_event_application', _wpnonce: event_nonce,
                    app_id: appId, payment_method: method, payment_details: details,
                    amount: amount, options_json: options_json,
                    tshirt_size: tshirt_val }
        }).done(function(res){
            if (res.success) {
                Swal.fire({ title:'Updated!', icon:'success', timer:1400, showConfirmButton:false });
                closeEditApp();
                appTable.ajax.reload(null, false);
                BM.regTable.ajax.reload(null, false);
            } else { Swal.fire('Error!', res.data, 'error'); }
        }).always(function(){
            $('#BatchMate-editapp-save').prop('disabled',false).html('<span class="dashicons dashicons-saved"></span> Save Changes');
        });
    });


    
    

});
