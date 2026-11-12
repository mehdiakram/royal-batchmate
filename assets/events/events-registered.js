/**
 * Royal BatchMate - Events Registered Members & Summaries
 */
jQuery(document).ready(function($) {
    const BM          = window.BatchMateEvents = window.BatchMateEvents || {};
    const ajax_url    = BM.ajax_url;
    const event_nonce = BM.event_nonce;
    const noPhoto     = BM.noPhoto;

    
    var regTable = BM.regTable = $('#event-registered-table').DataTable({
        autoWidth: false,
        processing: true,
        pageLength: 25,
        order: [[0, 'desc']],
        ajax: {
            url: ajax_url, type: 'POST',
            data: function(d) {
                d.action   = 'BatchMate_get_event_members';
                d._wpnonce = event_nonce;
                d.event_id = $('#filter-registered-event').val();
            }
        },
        columns: [
            { 
                data: 'serial_no',
                width: '38px',
                className: 'text-center',
                render: function(d, t, row, meta) {
                    return '<span style="font-weight:700;color:#2271b1;font-size:15px;">' + (d || (meta.row + 1)) + '</span>';
                }
            },
            { 
                data: 'member_number',
                width: '120px',
                render: function(d) {
                    if (!d || d === 'Non-Member') {
                        return '<span style="color:#64748b;font-size:12px;font-style:italic;">' + (d || '—') + '</span>';
                    }
                    return '<span style="white-space:nowrap;font-weight:700;font-family:monospace;font-size:13px;letter-spacing:0.3px;color:#1e293b;">' + d + '</span>';
                }
            },
            { data: 'name' },
            { 
                data: 'photo_url', 
                orderable: false,
                width: '48px',
                className: 'text-center',
                render: function(d){
                    return '<img src="'+(d||noPhoto)+'" class="evt-photo">';
                }
            },
            { 
                data: 'dept_name',
                render: function(d, t, row) {
                    var val = d || row.district || '';
                    return val ? '<span style="white-space:nowrap;">' + val + '</span>' : '<span style="color:#aaa">—</span>';
                }
            },
            { 
                data: 'cell_number',
                render: function(d) {
                    return '<span style="white-space:nowrap;">' + (d || '—') + '</span>';
                }
            },
            { 
                data: 'amount', 
                render: function(d){
                    return '<span style="white-space:nowrap;">&#2547; ' + parseFloat(d||0).toFixed(2) + '</span>';
                }
            },
            { 
                data: 'options_json', 
                orderable: false, 
                render: function(d){
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
                }
            },
            { 
                data: 'tshirt_size', 
                orderable: true, 
                render: function(d, t, row){
                    if (!parseInt(row.has_tshirt)) return '<span style="color:#bbb;font-size:11px;">—</span>';
                    if (!d || d === '') return '<span style="display:inline-block;padding:2px 8px;border-radius:10px;background:#fee2e2;color:#991b1b;font-size:11px;font-weight:700;">N/A</span>';
                    if (d === 'Saree') return '<span style="display:inline-block;padding:2px 10px;border-radius:10px;background:#fce7f3;color:#be185d;font-weight:700;font-size:12px;">👘 Saree</span>';
                    return '<span style="display:inline-block;padding:2px 10px;border-radius:10px;background:#e0f2fe;color:#0369a1;font-weight:700;font-size:12px;">' + d + '</span>';
                }
            },
            { data: 'event_name' },
            { 
                data: null, 
                orderable: false, 
                searchable: false, 
                width: '95px',
                className: 'text-center',
                render: function(d, t, row) {
                    if (!row.app_id) return '<span style="color:#aaa">—</span>';
                    var cardBtn = '';
                    if (row.card_template_url) {
                        cardBtn = `<button class="button button-small gen-card-btn" title="View Registration Card"
                            data-member-id="${row.member_id||0}"
                            data-rawdata-id="${row.rawdata_id||0}"
                            data-app-id="${row.app_id}"
                            data-event-id="${row.event_id}"
                            data-member-name="${(row.name||'').replace(/"/g,'&quot;')}"
                            style="color:#0a7242;border-color:#bbf7d0;background:#f0fdf4;padding:0;width:28px;height:28px;display:inline-flex;align-items:center;justify-content:center;">
                            <span class="dashicons dashicons-id" style="font-size:18px;width:18px;height:18px;line-height:1;"></span>
                        </button>`;
                    }
                    return `<div style="display:inline-flex;gap:6px;align-items:center;">
                            <button class="button button-small edit-app-btn" title="Edit Application" style="color:#0f2756;border-color:#cbd5e1;background:#f8fafc;padding:0;width:28px;height:28px;display:inline-flex;align-items:center;justify-content:center;"
                                data-id="${row.app_id}"
                                data-method="${row.payment_method||''}"
                                data-details="${(row.payment_details||'').replace(/"/g,'&quot;')}"
                                data-amount="${row.amount||0}"
                                data-eventid="${row.event_id}"
                                data-tshirt="${row.tshirt_size||''}"
                                data-options='${row.options_json||"null"}'>
                                <span class="dashicons dashicons-edit" style="font-size:18px;width:18px;height:18px;line-height:1;"></span>
                            </button>
                            <button class="button button-small resend-mail-btn" data-appid="${row.app_id}" title="Resend Mail / Webhook" style="color:#2563eb;border-color:#bfdbfe;background:#eff6ff;padding:0;width:28px;height:28px;display:inline-flex;align-items:center;justify-content:center;">
                                <span class="dashicons dashicons-email-alt" style="font-size:18px;width:18px;height:18px;line-height:1;"></span>
                            </button>
                            ${cardBtn}
                            </div>`;
                }
            }
        ],
        drawCallback: function() {
            var total = 0;
            var memberCount = 0;
            var guestTotals = {};
            this.api().rows({ search: 'applied', order: 'current' }).data().each(function(r){
                memberCount++;
                total += parseFloat(r.amount || 0);
                if (r.options_json) {
                    try {
                        var cleanD = typeof r.options_json === 'string' ? r.options_json.replace(/\\"/g, '"').replace(/\\\\/g, '\\') : r.options_json;
                        var opts = typeof cleanD === 'string' ? JSON.parse(cleanD) : cleanD;
                        if (typeof opts === 'string') opts = JSON.parse(opts);
                        opts.forEach(function(o){
                            var n = o.name; var q = parseInt(o.qty)||0;
                            if(q>0) guestTotals[n] = (guestTotals[n]||0) + q;
                        });
                    } catch(e){}
                }
            });
            $('#reg-table-foot-total').html('&#2547; ' + total.toFixed(2));
            var evtSel = $('#filter-registered-event').val();
            if (evtSel) {
                var memberStr = '<span class="dashicons dashicons-admin-users"></span> Members: <strong style="color:#1d2327;">' + memberCount + '</strong>';
                
                var gArr = [];
                for (var key in guestTotals) {
                    gArr.push(key + ': ' + guestTotals[key]);
                }
                
                var guestStr = '';
                if (gArr.length > 0) {
                    guestStr = '<span style="margin:0 6px; color:#9ae6b4; font-weight:400;">|</span> <span class="dashicons dashicons-groups"></span> Guests: <strong style="color:#1d2327;">' + gArr.join(', ') + '</strong>';
                }
                
                var totalStr = '<span style="margin:0 6px; color:#9ae6b4; font-weight:400;">|</span> <span class="dashicons dashicons-money-alt"></span> Total: <strong style="color:#1d2327;">\u09F3 ' + total.toFixed(2) + '</strong>';
                
                $('#reg-collection-badge').html(memberStr + guestStr + totalStr).show();
            } else {
                $('#reg-collection-badge').hide();
            }
        }
    });

    $('#filter-registered-event').on('change', function() {
        regTable.ajax.reload(null, false);
        BM.loadDeptSummary();
    });

    
    
    
    BM.loadDeptSummary = function() {
        var evtId = $('#filter-registered-event').val();
        if (!evtId) {
            $('#dept-summary-wrap').hide();
            $('#tshirt-summary-wrap').hide();
            return;
        }
        var evtLabel = $('#filter-registered-event option:selected').text();
        $('#dept-summary-event-label').text('District-wise Registration — ' + evtLabel);
        $('#dept-summary-tbody').html('<tr><td colspan="6" style="text-align:center;color:#aaa;padding:16px;">Loading...</td></tr>');
        $('#dept-summary-wrap').show();

        $.ajax({
            url: ajax_url,
            type: 'POST',
            data: {
                action: 'BatchMate_get_dept_registration_summary',
                _wpnonce: event_nonce,
                event_id: evtId
            }
        }).done(function(res) {
            if (!res.success || !res.data.rows.length) {
                $('#dept-summary-tbody').html('<tr><td colspan="6" style="text-align:center;color:#aaa;padding:16px;">No data found for this event.</td></tr>');
                $('#dept-summary-total').text('0');
                $('#dept-summary-guest-total').text('0');
                $('#dept-summary-grand-total').text('0');
                return;
            }
            var rows        = res.data.rows;
            var total       = parseInt(res.data.total)       || 0;
            var totalGuest  = parseInt(res.data.total_guest) || 0;
            var grandTotal  = total + totalGuest;
            var maxVal      = Math.max.apply(null, rows.map(function(r){ return parseInt(r.member_count) + parseInt(r.guest_count||0); }));
            var hasAnyGuest = totalGuest > 0;
            var tbody  = '';
            rows.forEach(function(r, idx) {
                var cnt       = parseInt(r.member_count);
                var guestCnt  = parseInt(r.guest_count || 0);
                var rowTotal  = cnt + guestCnt;
                var pct       = maxVal > 0 ? Math.round((rowTotal / maxVal) * 100) : 0;
                var pctTotal  = total > 0 ? ((cnt / total) * 100).toFixed(1) : '0.0';
                tbody += '<tr>';
                tbody += '<td style="text-align:center;font-weight:700;color:#2271b1;">' + (idx + 1) + '</td>';
                tbody += '<td style="font-weight:600;">' + (r.dept_name || '—') + ' <span style="font-size:11px;color:#888;font-weight:400;">(' + pctTotal + '%)</span></td>';
                tbody += '<td style="text-align:center;font-weight:700;font-size:15px;color:#0a7242;">' + cnt + '</td>';
                tbody += '<td style="text-align:center;font-weight:700;font-size:15px;color:' + (guestCnt > 0 ? '#2563eb' : '#bbb') + ';">' + (guestCnt > 0 ? guestCnt : '—') + '</td>';
                tbody += '<td style="text-align:center;font-weight:800;font-size:15px;color:#b91c1c;">' + rowTotal + '</td>';
                tbody += '<td><div class="BatchMate-dept-bar-wrap"><div class="BatchMate-dept-bar-fill" style="width:' + pct + '%;"></div></div></td>';
                tbody += '</tr>';
            });
            $('#dept-summary-tbody').html(tbody);
            $('#dept-summary-total').text(total);
            $('#dept-summary-guest-total').text(totalGuest);
            $('#dept-summary-grand-total').text(grandTotal);
        }).fail(function() {
            $('#dept-summary-tbody').html('<tr><td colspan="6" style="text-align:center;color:#d63638;padding:16px;">Failed to load summary.</td></tr>');
        });

        
        $.ajax({
            url: ajax_url, type: 'POST',
            data: { action: 'BatchMate_get_tshirt_summary', event_id: evtId, _wpnonce: event_nonce }
        }).done(function(res) {
            if (!res.success) {
                $('#tshirt-summary-wrap').hide();
                $('#payment-method-wrap').hide();
                return;
            }

            // 1. Left Card: T-Shirt / শাড়ি Summary
            if (res.data.has_tshirt) {
                var sizes    = res.data.sizes;
                var hasSaree = res.data.has_saree;
                var tBody    = '';
                var grandTotal = 0;

                $('#tshirt-summary-title').text(hasSaree ? 'T-Shirt / শাড়ি Summary' : 'T-Shirt Size Summary');

                if (!sizes || sizes.length === 0) {
                    tBody = '<tr><td colspan="2" style="padding:10px 12px;color:#aaa;text-align:center;">No sizes recorded yet.</td></tr>';
                } else {
                    sizes.forEach(function(row) {
                        var isSaree = row.size === 'Saree';
                        var isNA    = row.size === 'N/A';
                        var sizeLabel, colorStyle;
                        if (isSaree) {
                            sizeLabel   = '<span style="display:inline-block;padding:2px 10px;border-radius:10px;background:#fce7f3;color:#be185d;font-weight:700;">👘 শাড়ি</span>';
                            colorStyle  = 'color:#be185d;font-weight:700;';
                        } else if (isNA) {
                            sizeLabel   = '<span style="font-style:italic;color:#94a3b8;">N/A</span>';
                            colorStyle  = 'color:#94a3b8;';
                        } else {
                            sizeLabel   = '<span style="display:inline-block;padding:2px 10px;border-radius:10px;background:#e0f2fe;color:#0369a1;font-weight:700;">' + row.size + '</span>';
                            colorStyle  = 'color:#d97706;font-weight:700;';
                        }
                        grandTotal += parseInt(row.count);
                        tBody += '<tr>' +
                            '<td style="padding:7px 12px;">' + sizeLabel + '</td>' +
                            '<td style="text-align:center;padding:7px 12px;font-size:15px;' + colorStyle + '">' + row.count + '</td>' +
                        '</tr>';
                    });
                    tBody += '<tr style="border-top:2px solid #e2e8f0;background:#f8fafc;">' +
                        '<td style="padding:8px 12px;font-weight:700;color:#1d2327;">Total</td>' +
                        '<td style="text-align:center;padding:8px 12px;font-weight:800;font-size:16px;color:#0a7242;">' + grandTotal + '</td>' +
                    '</tr>';
                }
                $('#tshirt-summary-tbody').html(tBody);
                $('#tshirt-summary-wrap').show();
            } else {
                $('#tshirt-summary-wrap').hide();
            }

            // 2. Right Card: Payment Method Summary (ডানে থাকবে)
            var pmRows = res.data.payment_methods || [];
            if (pmRows.length > 0) {
                var pmBody = '';
                var pmTotalMembers = 0;
                var pmTotalAmount  = 0;
                pmRows.forEach(function(pm) {
                    pmTotalMembers += pm.member_count;
                    pmTotalAmount  += pm.total_amount;
                    pmBody += '<tr>' +
                        '<td style="padding:8px 12px;font-weight:600;color:#1e293b;">' + pm.method + '</td>' +
                        '<td style="text-align:center;padding:8px 12px;font-weight:700;color:#0284c7;font-size:14px;">' + pm.member_count + '</td>' +
                        '<td style="text-align:right;padding:8px 12px;font-weight:700;color:#0a7242;font-size:14px;">৳ ' + parseFloat(pm.total_amount).toLocaleString('en-BD', {minimumFractionDigits:2, maximumFractionDigits:2}) + '</td>' +
                    '</tr>';
                });
                pmBody += '<tr style="border-top:2px solid #e2e8f0;background:#f8fafc;">' +
                    '<td style="padding:9px 12px;font-weight:700;color:#1d2327;">Grand Total</td>' +
                    '<td style="text-align:center;padding:9px 12px;font-weight:800;font-size:15px;color:#0284c7;">' + pmTotalMembers + '</td>' +
                    '<td style="text-align:right;padding:9px 12px;font-weight:800;font-size:15px;color:#0a7242;">৳ ' + parseFloat(pmTotalAmount).toLocaleString('en-BD', {minimumFractionDigits:2, maximumFractionDigits:2}) + '</td>' +
                '</tr>';
                $('#payment-method-tbody').html(pmBody);
                $('#payment-method-wrap').show();
            } else {
                $('#payment-method-wrap').hide();
            }
        });
    }

    
    BM.loadDeptSummary();

    
    
    
    $('#btn-print-dept-summary').on('click', function() {
        var evtLabel = $('#filter-registered-event option:selected').text();
        var rows = [];
        var total = 0;
        var totalGuest = 0;
        $('#dept-summary-tbody tr').each(function(idx) {
            var tds = $(this).find('td');
            if (tds.length >= 5) {
                var deptName  = $(tds[1]).clone().find('span').remove().end().text().trim();
                var cnt       = parseInt($(tds[2]).text().trim()) || 0;
                var guestText = $(tds[3]).text().trim();
                var guestCnt  = (guestText === '—') ? 0 : (parseInt(guestText) || 0);
                rows.push({ sl: idx + 1, dept: deptName, count: cnt, guest: guestCnt, rowTotal: cnt + guestCnt });
                total      += cnt;
                totalGuest += guestCnt;
            }
        });
        if (!rows.length) { Swal.fire('No Data', 'No district data to print.', 'info'); return; }

        var hasAnyGuest = totalGuest > 0;
        var grandTotal  = total + totalGuest;

        var bodyRows = '';
        rows.forEach(function(r) {
            var pct = total > 0 ? ((r.count / total) * 100).toFixed(1) : '0.0';
            bodyRows += '<tr>';
            bodyRows += '<td style="text-align:center;font-weight:700;">' + r.sl + '</td>';
            bodyRows += '<td>' + r.dept + '</td>';
            bodyRows += '<td style="text-align:center;font-weight:700;font-size:15px;color:#0a7242;">' + r.count + '</td>';
            if (hasAnyGuest) {
                bodyRows += '<td style="text-align:center;font-weight:700;color:' + (r.guest > 0 ? '#2563eb' : '#bbb') + ';">' + (r.guest > 0 ? r.guest : '—') + '</td>';
                bodyRows += '<td style="text-align:center;font-weight:800;color:#b91c1c;">' + r.rowTotal + '</td>';
            }
            bodyRows += '<td style="text-align:center;color:#555;">' + pct + '%</td>';
            bodyRows += '</tr>';
        });

        var now = new Date().toLocaleString('en-US', { dateStyle: 'medium', timeStyle: 'short' });
        var guestHeader   = hasAnyGuest ? '<th style="text-align:center;width:90px;">Guest(s)</th><th style="text-align:center;width:90px;">Total</th>' : '';
        var guestFooter   = hasAnyGuest
            ? '<td style="text-align:center;color:#93c5fd;">' + totalGuest + '</td><td style="text-align:center;font-size:15px;color:#fcd34d;">' + grandTotal + '</td>'
            : '';
        var orgLabel = (window.batchmate_events_cfg && window.batchmate_events_cfg.orgLabel) ? window.batchmate_events_cfg.orgLabel : '';
        var footerText = (orgLabel ? orgLabel + ' ' : '') + 'Events Management — ' + evtLabel;
        var html = `<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>District Registration Summary — ${evtLabel}</title>
<style>
  *{box-sizing:border-box;margin:0;padding:0;}
  body{font-family:Arial,sans-serif;padding:28px 36px;color:#1d2327;font-size:12px;}
  .ph{text-align:center;margin-bottom:18px;border-bottom:2px solid #2271b1;padding-bottom:14px;}
  .ph h2{font-size:18px;color:#1a3a6b;margin-bottom:6px;}
  .ph .evtag{display:inline-block;background:#e7f0fa;color:#2271b1;padding:3px 14px;border-radius:20px;font-size:13px;font-weight:700;}
  .ph .meta{font-size:11px;color:#888;margin-top:6px;}
  table{width:100%;border-collapse:collapse;margin-top:4px;}
  thead{background:#2271b1;color:#fff;}
  th,td{border:1px solid #dee2e6;padding:8px 12px;}
  tr:nth-child(even) td{background:#f8f9fa;}
  tfoot td{background:#1a3a6b;color:#fff;font-weight:700;}
  .pf{margin-top:14px;font-size:10px;color:#aaa;text-align:right;}
  @media print{body{padding:10px;}}
</style>
</head>
<body>
  <div class="ph">
    <h2>District-wise Registration Summary</h2>
    <div class="evtag">&#127937; ${evtLabel}</div>
    <p class="meta">Printed: ${now}</p>
  </div>
  <table>
    <thead>
      <tr>
        <th style="width:40px;text-align:center;">#</th>
        <th>District</th>
        <th style="text-align:center;width:110px;">Registered</th>
        ${guestHeader}
        <th style="text-align:center;width:80px;">% of Total</th>
      </tr>
    </thead>
    <tbody>${bodyRows}</tbody>
    <tfoot>
      <tr>
        <td colspan="2" style="text-align:right;">Grand Total:</td>
        <td style="text-align:center;">${total}</td>
        ${guestFooter}
        <td style="text-align:center;">100%</td>
      </tr>
    </tfoot>
  </table>
  <div class="pf">${footerText}</div>
</body></html>`;

        var w = window.open('', '_blank');
        w.document.write(html);
        w.document.close();
        setTimeout(function(){ w.print(); }, 400);
    });

    
    // ── Print T-Shirt / শাড়ি Summary ──────────────────────────────────
    $(document).on('click', '#btn-print-tshirt-summary', function() {
        var evtLabel = $('#filter-registered-event option:selected').text();
        var title    = $('#tshirt-summary-title').text();
        var rows = [];
        var grandTotal = 0;
        $('#tshirt-summary-tbody tr').each(function() {
            var tds = $(this).find('td');
            if (tds.length >= 2) {
                var sizeText = $(tds[0]).text().trim();
                var cnt      = parseInt($(tds[1]).text().trim()) || 0;
                if (sizeText === 'Total') return;
                rows.push({ size: sizeText, count: cnt });
                grandTotal += cnt;
            }
        });
        if (!rows.length) { Swal.fire('No Data', 'No size data to print.', 'info'); return; }
        var now = new Date().toLocaleString('en-US', {dateStyle:'medium', timeStyle:'short'});
        var bodyRows = '';
        rows.forEach(function(r) {
            var isSaree = r.size.indexOf('শাড়ি') > -1 || r.size === 'Saree';
            var bg = isSaree ? 'background:#fce7f3;color:#be185d;' : 'background:#e0f2fe;color:#0369a1;';
            bodyRows += '<tr><td style="padding:8px 14px;"><span style="display:inline-block;padding:2px 10px;border-radius:10px;font-weight:700;' + bg + '">' + r.size + '</span></td>' +
                '<td style="text-align:center;padding:8px 14px;font-weight:700;font-size:15px;">' + r.count + '</td></tr>';
        });
        var html = '<!DOCTYPE html><html><head><meta charset="utf-8"><title>' + title + ' — ' + evtLabel + '</title>' +
            '<style>*{box-sizing:border-box;margin:0;padding:0;}body{font-family:Arial,sans-serif;padding:28px 36px;font-size:13px;}' +
            '.ph{text-align:center;margin-bottom:18px;border-bottom:2px solid #d97706;padding-bottom:14px;}' +
            '.ph h2{font-size:18px;color:#92400e;margin-bottom:6px;}' +
            '.ph .evtag{display:inline-block;background:#fef3c7;color:#d97706;padding:3px 14px;border-radius:20px;font-size:13px;font-weight:700;}' +
            '.ph .meta{font-size:11px;color:#888;margin-top:6px;}' +
            'table{width:100%;border-collapse:collapse;max-width:400px;margin:0 auto;}' +
            'thead{background:#f59e0b;color:#fff;-webkit-print-color-adjust:exact;print-color-adjust:exact;}' +
            'th,td{border:1px solid #fde68a;padding:8px 14px;}' +
            'tfoot td{background:#d97706;color:#fff;font-weight:700;-webkit-print-color-adjust:exact;print-color-adjust:exact;}' +
            '@media print{body{padding:10px;}}</style></head><body>' +
            '<div class="ph"><h2>' + title + '</h2><div class="evtag">🎪 ' + evtLabel + '</div><p class="meta">Printed: ' + now + '</p></div>' +
            '<table><thead><tr><th>Size / Item</th><th style="text-align:center;">Count</th></tr></thead>' +
            '<tbody>' + bodyRows + '</tbody>' +
            '<tfoot><tr><td style="text-align:right;">Total</td><td style="text-align:center;font-size:16px;">' + grandTotal + '</td></tr></tfoot>' +
            '</table></body></html>';
        var w = window.open('', '_blank');
        w.document.write(html);
        w.document.close();
        setTimeout(function(){ w.print(); }, 400);
    });

    // ── Print Payment Method Breakdown ─────────────────────────────────
    $(document).on('click', '#btn-print-payment-summary', function() {
        var evtLabel = $('#filter-registered-event option:selected').text();
        var rows = [];
        var totalMembers = 0, totalAmount = 0;
        $('#payment-method-tbody tr').each(function() {
            var tds = $(this).find('td');
            if (tds.length >= 3) {
                var method = $(tds[0]).text().trim();
                var cnt    = parseInt($(tds[1]).text().trim()) || 0;
                var amtTxt = $(tds[2]).text().trim().replace(/[৳,\s]/g, '');
                var amt    = parseFloat(amtTxt) || 0;
                if (method === 'Total') return;
                rows.push({ method: method, count: cnt, amount: amt });
                totalMembers += cnt;
                totalAmount  += amt;
            }
        });
        if (!rows.length) { Swal.fire('No Data', 'No payment data to print.', 'info'); return; }
        var now = new Date().toLocaleString('en-US', {dateStyle:'medium', timeStyle:'short'});
        var bodyRows = '';
        rows.forEach(function(r) {
            bodyRows += '<tr>' +
                '<td style="padding:8px 14px;font-weight:600;">' + r.method + '</td>' +
                '<td style="text-align:center;padding:8px 14px;font-weight:700;color:#2271b1;">' + r.count + '</td>' +
                '<td style="text-align:right;padding:8px 14px;font-weight:700;color:#0a7242;">৳ ' + r.amount.toLocaleString('en-BD', {minimumFractionDigits:2}) + '</td>' +
            '</tr>';
        });
        var html = '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Payment Method Breakdown — ' + evtLabel + '</title>' +
            '<style>*{box-sizing:border-box;margin:0;padding:0;}body{font-family:Arial,sans-serif;padding:28px 36px;font-size:13px;}' +
            '.ph{text-align:center;margin-bottom:18px;border-bottom:2px solid #2271b1;padding-bottom:14px;}' +
            '.ph h2{font-size:18px;color:#1a3a6b;margin-bottom:6px;}' +
            '.ph .evtag{display:inline-block;background:#e7f0fa;color:#2271b1;padding:3px 14px;border-radius:20px;font-size:13px;font-weight:700;}' +
            '.ph .meta{font-size:11px;color:#888;margin-top:6px;}' +
            'table{width:100%;border-collapse:collapse;max-width:500px;margin:0 auto;}' +
            'thead{background:#2271b1;color:#fff;-webkit-print-color-adjust:exact;print-color-adjust:exact;}' +
            'th,td{border:1px solid #bfdbfe;padding:8px 14px;}' +
            'tfoot td{background:#1a3a6b;color:#fff;font-weight:700;-webkit-print-color-adjust:exact;print-color-adjust:exact;}' +
            '@media print{body{padding:10px;}}</style></head><body>' +
            '<div class="ph"><h2>Payment Method Breakdown</h2><div class="evtag">&#127937; ' + evtLabel + '</div><p class="meta">Printed: ' + now + '</p></div>' +
            '<table><thead><tr><th>Payment Method</th><th style="text-align:center;">Members</th><th style="text-align:right;">Amount (৳)</th></tr></thead>' +
            '<tbody>' + bodyRows + '</tbody>' +
            '<tfoot><tr><td style="text-align:right;">Grand Total</td><td style="text-align:center;">' + totalMembers + '</td><td style="text-align:right;">৳ ' + totalAmount.toLocaleString('en-BD', {minimumFractionDigits:2}) + '</td></tr></tfoot>' +
            '</table></body></html>';
        var w = window.open('', '_blank');
        w.document.write(html);
        w.document.close();
        setTimeout(function(){ w.print(); }, 400);
    });


    $('#event-registered-table tbody').on('click', '.resend-mail-btn', function() {
        var appId = $(this).data('appid');
        var $btn = $(this);
        Swal.fire({
            title: 'Resend Confirmation',
            text: "Choose an action to dispatch for this registrant:",
            icon: 'question',
            showCancelButton: true,
            showDenyButton: true,
            confirmButtonText: 'Sent Mail',
            denyButtonText: 'Sent Webhook',
            cancelButtonText: 'Cancel',
            confirmButtonColor: '#2563eb',
            denyButtonColor: '#0284c7',
            cancelButtonColor: '#6b7280'
        }).then((result) => {
            if (result.isConfirmed) {
                $btn.prop('disabled', true).css('opacity', '0.5');
                Swal.fire({ title: 'Sending Mail...', text: 'Please wait while the confirmation email is being sent.', allowOutsideClick: false, didOpen: () => { Swal.showLoading(); } });
                $.ajax({
                    url: ajax_url,
                    type: 'POST',
                    data: { action: 'BatchMate_resend_event_email', _wpnonce: event_nonce, app_id: appId }
                }).done(function(res) {
                    if(res.success) {
                        Swal.fire({ title: 'Sent!', text: res.data || 'Confirmation email sent successfully.', icon: 'success', timer: 2000, showConfirmButton: false });
                    } else {
                        Swal.fire('Error', res.data || 'Failed to send email.', 'error');
                    }
                }).fail(function() {
                    Swal.fire('Error', 'Server request failed.', 'error');
                }).always(function(){
                    $btn.prop('disabled', false).css('opacity', '1');
                });
            } else if (result.isDenied) {
                $btn.prop('disabled', true).css('opacity', '0.5');
                Swal.fire({ title: 'Dispatching Webhook...', text: 'Please wait while dispatching to webhook.', allowOutsideClick: false, didOpen: () => { Swal.showLoading(); } });
                $.ajax({
                    url: ajax_url,
                    type: 'POST',
                    data: { action: 'BatchMate_resend_event_webhook', _wpnonce: event_nonce, app_id: appId }
                }).done(function(res) {
                    if(res.success) {
                        Swal.fire({ title: 'Sent!', text: res.data || 'Webhook dispatched successfully.', icon: 'success', timer: 2000, showConfirmButton: false });
                    } else {
                        Swal.fire('Error', res.data || 'Failed to dispatch webhook.', 'error');
                    }
                }).fail(function() {
                    Swal.fire('Error', 'Server request failed.', 'error');
                }).always(function(){
                    $btn.prop('disabled', false).css('opacity', '1');
                });
            }
        });
    });    
    
    
    $('#btn-print-registered').on('click', function() {
        var evtSel = $('#filter-registered-event').val();
        var evtLabel = evtSel ? $('#filter-registered-event option:selected').text() : 'All Events';

        var allRows = [];
        regTable.rows({ search: 'applied' }).data().each(function(row) { allRows.push(row); });
        allRows.sort(function(a, b) { return parseInt(a.serial_no || 0) - parseInt(b.serial_no || 0); });

        if (!allRows.length) { Swal.fire('No Data', 'No records to print', 'info'); return; }

        var hasTshirt = allRows.some(function(r){ return parseInt(r.has_tshirt) === 1; });
        var totalAmt = 0;
        var guestTotals = {};
        var totalGuestCount = 0;
        var tshirtTotals = {};
        var sareeCount = 0;
        var totalMembers = 0;
        var bodyRows = '';

        allRows.forEach(function(row) {
            totalMembers++;
            var amt = parseFloat(row.amount || 0);
            totalAmt += amt;

            // T-shirt / Saree cell
            var itemCell = '';
            if (hasTshirt) {
                var sz = row.tshirt_size || '';
                if (sz === 'Saree') {
                    sareeCount++;
                    itemCell = '<td style="text-align:center;"><span style="display:inline-block;padding:2px 8px;border-radius:10px;background:#fce7f3;color:#be185d;font-weight:700;font-size:11px;">👘 শাড়ি</span></td>';
                } else if (sz && sz !== 'NA') {
                    tshirtTotals[sz] = (tshirtTotals[sz] || 0) + 1;
                    itemCell = '<td style="text-align:center;"><span style="display:inline-block;padding:2px 8px;border-radius:10px;background:#e0f2fe;color:#0369a1;font-weight:700;font-size:11px;">' + sz + '</span></td>';
                } else {
                    itemCell = '<td style="text-align:center;color:#bbb;">—</td>';
                }
            }

            // Guests
            var guestStr = '—';
            if (row.options_json) {
                try {
                    var cleanD = typeof row.options_json === 'string' ? row.options_json.replace(/\\"/g, '"').replace(/\\\\/g, '\\') : row.options_json;
                    var opts = typeof cleanD === 'string' ? JSON.parse(cleanD) : cleanD;
                    if (typeof opts === 'string') opts = JSON.parse(opts);
                    var strArr = [];
                    opts.forEach(function(o){
                        var n = o.name; var q = parseInt(o.qty)||0;
                        if(q>0){ 
                            guestTotals[n] = (guestTotals[n]||0) + q; 
                            totalGuestCount += q;
                            strArr.push(n+': '+q);
                        }
                    });
                    if(strArr.length) guestStr = strArr.join(', ');
                } catch(e){}
            }

            bodyRows += '<tr>' +
                '<td style="text-align:center;font-weight:700;font-size:13px;">' + (row.serial_no || totalMembers) + '</td>' +
                '<td style="white-space:nowrap;font-family:monospace;font-weight:600;">' + (row.member_number || '') + '</td>' +
                '<td style="text-align:center;"><img src="' + (row.photo_url || noPhoto) + '" style="width:30px;height:30px;border-radius:50%;object-fit:cover;"></td>' +
                '<td><strong>' + (row.name || '') + '</strong></td>' +
                '<td>' + (row.district || row.dept_name || '') + '</td>' +
                '<td>' + (row.cell_number || '') + '</td>' +
                '<td style="text-align:right;font-weight:600;">৳ ' + amt.toFixed(2) + '</td>' +
                (hasTshirt ? itemCell : '') +
                '<td>' + guestStr + '</td>' +
            '</tr>';
        });

        // Top summary boxes
        var guestSummaryParts = [];
        for (var gk in guestTotals) { guestSummaryParts.push(gk + ': ' + guestTotals[gk]); }
        var guestCardHtml = totalGuestCount > 0
            ? '<div class="si"><div class="lbl">Total Guests</div><div class="val">' + totalGuestCount + '</div>' +
              (guestSummaryParts.length ? '<div style="font-size:10px;color:#555;margin-top:3px;">(' + guestSummaryParts.join(', ') + ')</div>' : '') +
              '</div>'
            : '';

        var itemSummaryHtml = '';
        if (hasTshirt) {
            var tParts = [];
            for (var tk in tshirtTotals) { tParts.push(tk + ': ' + tshirtTotals[tk]); }
            if (sareeCount > 0) { tParts.push('শাড়ি: ' + sareeCount); }
            if (tParts.length) {
                itemSummaryHtml = '<div class="si"><div class="lbl">T-Shirt / শাড়ি</div><div class="val" style="font-size:13px;">' + tParts.join(' | ') + '</div></div>';
            }
        }

        var colSpanLabel     = 6; // Col 1 to 6 (#, Mem ID, Photo, Name, District, Contact)
        var tshirtHeaderCell = hasTshirt ? '<th style="text-align:center;width:95px;">T-Shirt / শাড়ি</th>' : '';
        var tshirtFootCell   = hasTshirt ? '<td></td>' : '';
        var guestFootCell    = '<td>' + (totalGuestCount > 0 ? 'Guests: ' + totalGuestCount : '') + '</td>';

        var orgLabel = (window.batchmate_events_cfg && window.batchmate_events_cfg.orgLabel) ? window.batchmate_events_cfg.orgLabel : 'SSC 99';
        var now = new Date().toLocaleString('en-US', {dateStyle:'medium', timeStyle:'short'});

        var html = '<!DOCTYPE html><html><head><meta charset="utf-8">' +
            '<title>Event Registered Members — ' + evtLabel + '</title>' +
            '<style>' +
            '*{box-sizing:border-box;margin:0;padding:0;}' +
            'body{font-family:Arial,sans-serif;padding:28px 36px;color:#1d2327;font-size:12px;}' +
            '.ph{text-align:center;margin-bottom:18px;border-bottom:2px solid #2271b1;padding-bottom:14px;}' +
            '.ph h2{font-size:18px;color:#1a3a6b;margin-bottom:6px;}' +
            '.ph .evtag{display:inline-block;background:#e7f0fa;color:#2271b1;padding:3px 14px;border-radius:20px;font-size:13px;font-weight:700;}' +
            '.ph .meta{font-size:11px;color:#888;margin-top:6px;}' +
            '.sumbox{display:flex;gap:24px;justify-content:center;align-items:center;margin:14px 0 18px;padding:12px 20px;background:#f0fff4;border:1.5px solid #9ae6b4;border-radius:10px;flex-wrap:wrap;}' +
            '.sumbox .si{text-align:center;}' +
            '.sumbox .si .val{font-size:18px;font-weight:700;color:#0a7242;line-height:1.2;}' +
            '.sumbox .si .lbl{font-size:11px;color:#555;margin-top:4px;text-transform:uppercase;letter-spacing:0.5px;}' +
            'table{width:100%;border-collapse:collapse;margin-top:4px;}' +
            'thead{background:#2271b1;color:#fff;-webkit-print-color-adjust:exact;print-color-adjust:exact;}' +
            'th,td{border:1px solid #dee2e6;padding:6px 9px;}' +
            'tr:nth-child(even) td{background:#f8f9fa;}' +
            'tfoot td{background:#1a3a6b;color:#fff;font-weight:700;-webkit-print-color-adjust:exact;print-color-adjust:exact;}' +
            '.pf{margin-top:14px;font-size:10px;color:#aaa;text-align:right;}' +
            '@media print{body{padding:10px;}}' +
            '</style></head><body>' +
            '<div class="ph"><h2>Event Registered Members</h2><div class="evtag">&#127937; ' + evtLabel + '</div><p class="meta">Printed: ' + now + '</p></div>' +
            '<div class="sumbox">' +
            '<div class="si"><div class="lbl">Total Members</div><div class="val">' + totalMembers + '</div></div>' +
            itemSummaryHtml +
            guestCardHtml +
            '<div class="si"><div class="lbl">Total Collection</div><div class="val">৳ ' + totalAmt.toFixed(2) + '</div></div>' +
            '</div>' +
            '<table><thead><tr>' +
            '<th style="width:32px;text-align:center;">#</th>' +
            '<th>Mem. ID</th><th style="width:36px;text-align:center;">Photo</th><th>Name</th><th>District</th>' +
            '<th>Contact</th><th style="text-align:right;">Amount (BDT)</th>' +
            tshirtHeaderCell +
            '<th>Guests</th>' +
            '</tr></thead>' +
            '<tbody>' + bodyRows + '</tbody>' +
            '<tfoot><tr>' +
            '<td colspan="' + colSpanLabel + '" style="text-align:right;font-weight:700;">Grand Total:</td>' +
            '<td style="text-align:right;font-weight:700;">৳ ' + totalAmt.toFixed(2) + '</td>' +
            tshirtFootCell +
            guestFootCell +
            '</tr></tfoot></table>' +
            '<div class="pf">' + orgLabel + ' Events Management &mdash; ' + evtLabel + '</div>' +
            '</body></html>';

        var w = window.open('', '_blank');
        w.document.write(html);
        w.document.close();
        setTimeout(function(){ w.print(); }, 400);
    });




    $('#btn-print-registered-noamount').on('click', function() {
        var evtSel = $('#filter-registered-event').val();
        var evtLabel = evtSel ? $('#filter-registered-event option:selected').text() : 'All Events';

        var allRows = [];
        regTable.rows({ search: 'applied' }).data().each(function(row) { allRows.push(row); });
        allRows.sort(function(a, b) { return parseInt(a.serial_no || 0) - parseInt(b.serial_no || 0); });

        if (!allRows.length) { Swal.fire('No Data', 'No records to print', 'info'); return; }

        var hasTshirt = allRows.some(function(r){ return parseInt(r.has_tshirt) === 1; });
        var totalMembers = 0;
        var totalGuestCount = 0;
        var guestTotals = {};
        var tshirtTotals = {};
        var sareeCount = 0;
        var bodyRows = '';

        allRows.forEach(function(row) {
            totalMembers++;

            var itemCell = '';
            if (hasTshirt) {
                var sz = row.tshirt_size || '';
                if (sz === 'Saree') {
                    sareeCount++;
                    itemCell = '<td style="text-align:center;"><span style="display:inline-block;padding:2px 8px;border-radius:10px;background:#fce7f3;color:#be185d;font-weight:700;font-size:11px;">👘 শাড়ি</span></td>';
                } else if (sz && sz !== 'NA') {
                    tshirtTotals[sz] = (tshirtTotals[sz] || 0) + 1;
                    itemCell = '<td style="text-align:center;"><span style="display:inline-block;padding:2px 8px;border-radius:10px;background:#e0f2fe;color:#0369a1;font-weight:700;font-size:11px;">' + sz + '</span></td>';
                } else {
                    itemCell = '<td style="text-align:center;color:#bbb;">—</td>';
                }
            }

            var guestStr = '—';
            var rowHasGuest = false;
            if (row.options_json) {
                try {
                    var cleanD = typeof row.options_json === 'string' ? row.options_json.replace(/\\"/g, '"').replace(/\\\\/g, '\\') : row.options_json;
                    var opts = typeof cleanD === 'string' ? JSON.parse(cleanD) : cleanD;
                    if (typeof opts === 'string') opts = JSON.parse(opts);
                    var strArr = [];
                    opts.forEach(function(o) {
                        var qty = parseInt(o.qty) || 0;
                        if (qty > 0) {
                            strArr.push(o.name + ': ' + qty);
                            totalGuestCount += qty;
                            guestTotals[o.name] = (guestTotals[o.name] || 0) + qty;
                            rowHasGuest = true;
                        }
                    });
                    if (strArr.length) guestStr = strArr.join(', ');
                } catch(e){}
            }
            var trStyle = rowHasGuest ? ' style="background:#f1f5f9;"' : '';
            bodyRows += '<tr' + trStyle + '>' +
                '<td style="text-align:center;font-weight:700;font-size:13px;">' + (row.serial_no || totalMembers) + '</td>' +
                '<td style="white-space:nowrap;font-family:monospace;font-weight:600;">' + (row.member_number || '') + '</td>' +
                '<td style="text-align:center;"><img src="' + (row.photo_url || noPhoto) + '" style="width:30px;height:30px;border-radius:50%;object-fit:cover;"></td>' +
                '<td><strong>' + (row.name || '') + '</strong></td>' +
                '<td>' + (row.district || row.dept_name || '') + '</td>' +
                '<td>' + (row.cell_number || '') + '</td>' +
                (hasTshirt ? itemCell : '') +
                '<td' + (rowHasGuest ? ' style="font-weight:600;"' : '') + '>' + guestStr + '</td>' +
            '</tr>';
        });

        var guestSummaryParts = [];
        for (var gk in guestTotals) { guestSummaryParts.push(gk + ': ' + guestTotals[gk]); }
        var guestCardHtml = totalGuestCount > 0
            ? '<div class="si"><div class="lbl">Total Guests</div><div class="val">' + totalGuestCount + '</div>' +
              (guestSummaryParts.length ? '<div style="font-size:10px;color:#555;margin-top:3px;">(' + guestSummaryParts.join(', ') + ')</div>' : '') +
              '</div>'
            : '';

        var itemSummaryHtml = '';
        if (hasTshirt) {
            var tParts = [];
            for (var tk in tshirtTotals) { tParts.push(tk + ': ' + tshirtTotals[tk]); }
            if (sareeCount > 0) { tParts.push('শাড়ি: ' + sareeCount); }
            if (tParts.length) {
                itemSummaryHtml = '<div class="si"><div class="lbl">T-Shirt / শাড়ি</div><div class="val" style="font-size:13px;">' + tParts.join(' | ') + '</div></div>';
            }
        }

        var colSpanLabel     = 6; // Col 1 to 6 (#, Mem ID, Photo, Name, District, Contact)
        var tshirtHeaderCell = hasTshirt ? '<th style="text-align:center;width:95px;">T-Shirt / শাড়ি</th>' : '';
        var tshirtFootCell   = hasTshirt ? '<td></td>' : '';
        var guestFootCell    = '<td>' + (totalGuestCount > 0 ? 'Guests: ' + totalGuestCount : '') + '</td>';

        var orgLabel = (window.batchmate_events_cfg && window.batchmate_events_cfg.orgLabel) ? window.batchmate_events_cfg.orgLabel : 'SSC 99';
        var now = new Date().toLocaleString('en-US', {dateStyle:'medium', timeStyle:'short'});

        var html = '<!DOCTYPE html><html><head><meta charset="utf-8">' +
            '<title>Event Registered Members — ' + evtLabel + '</title>' +
            '<style>' +
            '*{box-sizing:border-box;margin:0;padding:0;}' +
            'body{font-family:Arial,sans-serif;padding:28px 36px;color:#1d2327;font-size:12px;}' +
            '.ph{text-align:center;margin-bottom:18px;border-bottom:2px solid #2271b1;padding-bottom:14px;}' +
            '.ph h2{font-size:18px;color:#1a3a6b;margin-bottom:6px;}' +
            '.ph .evtag{display:inline-block;background:#e7f0fa;color:#2271b1;padding:3px 14px;border-radius:20px;font-size:13px;font-weight:700;}' +
            '.ph .meta{font-size:11px;color:#888;margin-top:6px;}' +
            '.sumbox{display:flex;gap:24px;justify-content:center;align-items:center;margin:14px 0 18px;padding:12px 20px;background:#f0fff4;border:1.5px solid #9ae6b4;border-radius:10px;flex-wrap:wrap;}' +
            '.sumbox .si{text-align:center;}' +
            '.sumbox .si .val{font-size:18px;font-weight:700;color:#0a7242;line-height:1.2;}' +
            '.sumbox .si .lbl{font-size:11px;color:#555;margin-top:4px;text-transform:uppercase;letter-spacing:0.5px;}' +
            'table{width:100%;border-collapse:collapse;margin-top:4px;}' +
            'thead{background:#2271b1;color:#fff;-webkit-print-color-adjust:exact;print-color-adjust:exact;}' +
            'th,td{border:1px solid #dee2e6;padding:6px 9px;}' +
            'tr:nth-child(even) td{background:#f8f9fa;}' +
            'tfoot td{background:#1a3a6b;color:#fff;font-weight:700;-webkit-print-color-adjust:exact;print-color-adjust:exact;}' +
            '.pf{margin-top:14px;font-size:10px;color:#aaa;text-align:right;}' +
            '@media print{body{padding:10px;}}' +
            '</style></head><body>' +
            '<div class="ph"><h2>Event Registered Members</h2><div class="evtag">&#127937; ' + evtLabel + '</div><p class="meta">Printed: ' + now + '</p></div>' +
            '<div class="sumbox">' +
            '<div class="si"><div class="lbl">Total Members</div><div class="val">' + totalMembers + '</div></div>' +
            itemSummaryHtml +
            guestCardHtml +
            '</div>' +
            '<table><thead><tr>' +
            '<th style="width:32px;text-align:center;">#</th>' +
            '<th>Mem. ID</th><th style="width:36px;text-align:center;">Photo</th><th>Name</th><th>District</th><th>Contact</th>' +
            tshirtHeaderCell +
            '<th>Guests</th>' +
            '</tr></thead>' +
            '<tbody>' + bodyRows + '</tbody>' +
            '<tfoot><tr>' +
            '<td colspan="' + colSpanLabel + '" style="text-align:right;font-weight:700;">Total Members: ' + totalMembers + '</td>' +
            tshirtFootCell +
            guestFootCell +
            '</tr></tfoot></table>' +
            '<div class="pf">' + orgLabel + ' Events Management &mdash; ' + evtLabel + '</div>' +
            '</body></html>';

        var w = window.open('', '_blank');
        w.document.write(html);
        w.document.close();
        setTimeout(function(){ w.print(); }, 400);
    });



});
