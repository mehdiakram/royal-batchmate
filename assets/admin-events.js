/**
 * Royal BatchMate - Admin Events Management Scripts
 */
jQuery(document).ready(function($) {

    const ajax_url    = batchmate_admin_ajax.ajax_url;
    const event_nonce = batchmate_admin_ajax.event_nonce;
    const noPhoto = (window.batchmate_events_cfg && window.batchmate_events_cfg.noPhoto) ? window.batchmate_events_cfg.noPhoto : '';

    
    function copyToClipboard(text, $el) {
        navigator.clipboard.writeText(text).then(function() {
            var orig = $el.html();
            $el.html('<span class="dashicons dashicons-yes"></span> Copied!');
            setTimeout(function(){ $el.html(orig); }, 1600);
        });
    }

    
    
    
    
    function switchToTab(tab) {
        if (!tab) return;
        if (tab.indexOf('tab-') !== 0) tab = 'tab-' + tab;
        $('.BatchMate-ev-tab').removeClass('active');
        $('.BatchMate-ev-tab[data-tab="' + tab + '"]').addClass('active');
        $('.BatchMate-ev-tab-content').removeClass('active').css('display', 'none');
        $('#' + tab).addClass('active').css('display', 'block');

        setTimeout(function() {
            if (tab === 'tab-registered' && typeof regTable !== 'undefined' && regTable) {
                try { regTable.columns.adjust().draw(); } catch(e){}
                try { regTable.ajax.reload(null, false); } catch(e){}
            }
            if (tab === 'tab-applications' && typeof appTable !== 'undefined' && appTable) {
                try { appTable.columns.adjust().draw(); } catch(e){}
                try { appTable.ajax.reload(null, false); } catch(e){}
            }
            if (tab === 'tab-events' && typeof evtTable !== 'undefined' && evtTable) {
                try { evtTable.columns.adjust().draw(); } catch(e){}
                try { evtTable.ajax.reload(null, false); } catch(e){}
            }
            if (tab === 'tab-expenses' && typeof loadEventReport === 'function') {
                try { loadEventReport(); } catch(e){}
            }
        }, 40);
    }
    window.switchToTab = switchToTab;

    $(document).on('click', '#BatchMate-event-tab-nav .BatchMate-ev-tab', function(e) {
        e.preventDefault();
        var tab = $(this).attr('data-tab') || $(this).data('tab');
        if (tab) {
            switchToTab(tab);
            if (history.pushState) {
                history.pushState(null, null, '#' + tab);
            } else {
                location.hash = '#' + tab;
            }
        }
    });

    $(window).on('hashchange', function() {
        var h = window.location.hash.replace('#', '');
        if (h && $('.BatchMate-ev-tab[data-tab="' + h + '"]').length) {
            switchToTab(h);
        }
    });

    var currentHash = window.location.hash.replace('#', '');
    if (currentHash && $('.BatchMate-ev-tab[data-tab="' + currentHash + '"]').length) {
        switchToTab(currentHash);
    }

    
    $('.BatchMate-ev-card--clickable').on('click', function() {
        var tab = $(this).data('goto-tab');
        if (tab) {
            switchToTab(tab);
            
            $('html, body').animate({ scrollTop: $('.BatchMate-ev-tab-bar').offset().top - 40 }, 400);
        }
    });


    
    
    
    var regTable = $('#event-registered-table').DataTable({
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
        loadDeptSummary();
    });

    
    
    
    function loadDeptSummary() {
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

    
    loadDeptSummary();

    
    
    
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




    
    
    
    var appTable = $('#event-applications-table').DataTable({
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
                return $.ajax({ url: ajax_url, type: 'POST',
                    data: { action: 'BatchMate_approve_event_application', app_id: app_id, _wpnonce: event_nonce }
                }).fail(function(){ Swal.showValidationMessage('Request failed.'); });
            }
        }).then(function(result) {
            if (result.isConfirmed && result.value && result.value.success) {
                Swal.fire({ title: 'Approved!', icon: 'success', timer: 1500, showConfirmButton: false });
                appTable.ajax.reload(null, false);
                regTable.ajax.reload(null, false);
            } else if (result.value && !result.value.success) {
                Swal.fire('Error!', result.value.data, 'error');
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


    
    var noPhotoUrl = (window.batchmate_events_cfg && window.batchmate_events_cfg.noPhotoUrl) ? window.batchmate_events_cfg.noPhotoUrl : '';
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


    
    
    
    var evtTable = $('#event-list-table').DataTable({
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
        copyToClipboard(sc, $(this));
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


    
    
    

    
    var DEFAULT_ZONES = {
        photo:     { cx: 540, cy: 380, r: 160, x: 380, y: 220, w: 320, h: 320, shape: 'square', color: '#3b82f6', label: 'Photo', always: true },
        name:      { x: 140, y: 640, w: 800, h: 60, size: 26, color_text: '#ffffff', color: '#f59e0b', label: 'Name',    always: true },
        serial:    { x: 820, y: 220, w: 200, h: 44, size: 28, color_text: '#ffffff', color: '#a855f7', label: 'Serial No.', prefix: '', enabled: true },
        blood:     { x: 420, y: 730, w: 200, h: 36, size: 26, color_text: '#ffffff', color: '#10b981', label: 'Blood',   enabled: true },
        district:  { x: 420, y: 790, w: 240, h: 36, size: 26, color_text: '#ffffff', color: '#0d9488', label: 'District', enabled: true },
        batch:     { x: 420, y: 850, w: 200, h: 36, size: 26, color_text: '#ffffff', color: '#ef4444', label: 'Batch',   enabled: false },
        free_text: { x: 420, y: 910, w: 240, h: 36, size: 26, color_text: '#ffffff', color: '#6366f1', label: 'Free Text', text: '', enabled: false },
    };

    var CARD_W = 1080, CARD_H = 1080;
    var _currentZones = {};
    var _editorScale  = 1;

    
    $('#btn-add-event').on('click', function(){
        $('#modal_card_template_url').val('');
        $('#modal_card_zones_json').val('');
        $('#card-zone-editor').hide();
        $('#card-template-upload-name').text('');
        $('#card-template-upload-area .dashicons').css('color','#c3c4c7');
        $('#card-template-upload-area p:first-of-type').text('Click to upload template image');
    });

    $('#cs-reset-zones').on('click', function() {
        var url = $('#cs_card_template_url').val();
        if (url) initCsZoneEditor(url, null);
        else Swal.fire({ title:'No template', text:'Please upload a card template first.', icon:'warning' });
    });

    // Toggle checkboxes — show/hide zones on canvas and mark enabled/disabled in saved data
    $('#cs-zone-toggles input[type=checkbox]').on('change', function() {
        var zone = $(this).data('zone');
        var enabled = $(this).is(':checked');
        var $zoneEl = $('#cs-zone-canvas-wrap').find('[data-key="' + zone + '"]');
        if (zone === 'free_text') {
            $('#cs-freetext-controls').toggle(enabled);
        }
        if (enabled) {
            if ($zoneEl.length === 0) {
                // zone not on canvas yet — add it with defaults
                var url = $('#cs_card_template_url').val();
                if (url) {
                    var df = DEFAULT_ZONES[zone];
                    df.enabled = true;
                    _currentZones[zone] = df;
                    addSingleZone(zone, df);
                }
            } else {
                $zoneEl.show();
            }
            if (_currentZones[zone]) _currentZones[zone].enabled = true;
        } else {
            $zoneEl.hide();
            if (_currentZones[zone]) _currentZones[zone].enabled = false;
        }
        saveCsZonesToInput();
    });

    // Listeners for shape, prefix, free text & font color
    $('#cs-photo-shape').on('change', function() {
        var shape = $(this).val();
        var $photoEl = $('#cs-zone-canvas-wrap').find('[data-key="photo"]');
        if ($photoEl.length) {
            if (shape === 'circle') {
                $photoEl.css('border-radius', '50%');
                var w = parseInt($photoEl.css('width'));
                $photoEl.css('height', w + 'px');
                $photoEl.find('.cs-resizer').css('border-radius', '50%');
            } else if (shape === 'rounded') {
                $photoEl.css('border-radius', '16px');
                $photoEl.find('.cs-resizer').css('border-radius', '2px');
            } else {
                $photoEl.css('border-radius', '2px');
                $photoEl.find('.cs-resizer').css('border-radius', '2px');
            }
            updateCsZoneFromEl($photoEl, 'photo');
            saveCsZonesToInput();
        }
    });

    $('#cs-serial-prefix').on('input change', function() {
        if (_currentZones.serial) {
            _currentZones.serial.prefix = $(this).val();
            saveCsZonesToInput();
        }
    });

    $('#cs-freetext-val').on('input change', function() {
        var txt = $(this).val();
        if (_currentZones.free_text) {
            _currentZones.free_text.text = txt;
            saveCsZonesToInput();
        }
        var $lbl = $('#cs-zone-canvas-wrap').find('[data-key="free_text"] .cs-lbl');
        if ($lbl.length) {
            $lbl.text(txt || 'Free Text');
        }
    });

    $('#cs-zone-toggles').on('input change', 'input[type=color]', function() {
        var zone = $(this).data('zone');
        var col = $(this).val();
        if (_currentZones[zone]) {
            _currentZones[zone].color_text = col;
            saveCsZonesToInput();
        }
    });

    function addSingleZone(key, z) {
        var $wrap = $('#cs-zone-canvas-wrap');
        var df = DEFAULT_ZONES[key];
        if (!df || !_editorScale) return;
        var $zone, $lbl;
        var px = (z.x !== undefined ? z.x : df.x) * _editorScale;
        var py = ((z.y !== undefined ? z.y : df.y) - (z.h || df.h)) * _editorScale;
        var pw = (z.w || df.w) * _editorScale;
        var ph = (z.h || df.h) * _editorScale;
        var displayLabel = (key === 'free_text' && z.text) ? z.text : df.label;
        $zone = $('<div class="czn czn-rect" data-key="'+key+'"></div>').css({
            position:'absolute', left:px+'px', top:py+'px',
            width:pw+'px', height:ph+'px',
            border:'2.5px solid '+(df.color||'#888'),
            background:'rgba('+hexToRgb(df.color||'#888')+',.18)',
            cursor:'move', boxSizing:'border-box', zIndex:10,
        });
        $lbl = $('<span class="cs-lbl" style="position:absolute;top:50%;left:8px;transform:translateY(-50%);color:'+(df.color||'#888')+';font-size:12px;font-weight:700;pointer-events:none;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:calc(100% - 16px);">'+displayLabel+'</span>');
        $zone.append('<div class="cs-resizer" style="width:10px;height:10px;background:'+(df.color||'#888')+';position:absolute;bottom:0;right:0;cursor:se-resize;z-index:20;"></div>');
        $zone.append($lbl);
        $wrap.append($zone);
        makeCsDraggable($zone, key);
    }

    // Open Card Setup — also restore toggle states from saved zones
    function openCardSetup(eventId, eventName, templateUrl, zonesJson) {
        $('#cs_event_id').val(eventId);
        $('#cs_card_template_url').val(templateUrl || '');
        $('#cs_card_zones_json').val(zonesJson || '');
        $('#cardsetup-event-name').text('Event: ' + eventName);

        // Restore toggle checkbox states from saved zones
        var savedZones = {};
        try { if (zonesJson) savedZones = JSON.parse(zonesJson); } catch(e) {}

        // Restore photo shape
        var photoShape = (savedZones.photo && savedZones.photo.shape) ? savedZones.photo.shape : 'square';
        $('#cs-photo-shape').val(photoShape);

        // Restore serial prefix
        var serialPrefix = (savedZones.serial && savedZones.serial.prefix !== undefined) ? savedZones.serial.prefix : '';
        $('#cs-serial-prefix').val(serialPrefix);

        // Restore free text
        var freeTextVal = (savedZones.free_text && savedZones.free_text.text !== undefined) ? savedZones.free_text.text : '';
        $('#cs-freetext-val').val(freeTextVal);
        var freeTextEnabled = (savedZones.free_text && savedZones.free_text.enabled === true);
        $('#cs-freetext-controls').toggle(freeTextEnabled);

        // Restore font colors
        $('#cs-zone-toggles input[type=color]').each(function() {
            var zone = $(this).data('zone');
            var col = (savedZones[zone] && savedZones[zone].color_text) ? savedZones[zone].color_text : '#ffffff';
            $(this).val(col);
        });

        // Restore font sizes
        ['name', 'serial', 'blood', 'district', 'batch', 'free_text'].forEach(function(zk) {
            var sz = (savedZones[zk] && savedZones[zk].size) ? savedZones[zk].size : (DEFAULT_ZONES[zk] ? DEFAULT_ZONES[zk].size : 26);
            if (zk === 'name' && sz >= 34) sz = 26; // adjust legacy oversized 36 down to 26
            $('#cs-size-' + zk).val(sz);
        });

        // Listen for font size changes on number inputs
        $('#cs-zone-toggles').off('input.cs change.cs', 'input[type=number]').on('input.cs change.cs', 'input[type=number]', function() {
            var zone = $(this).attr('id').replace('cs-size-', '');
            var val = parseInt($(this).val(), 10);
            if (zone && _currentZones[zone] && val > 0) {
                _currentZones[zone].size = val;
            }
            saveCsZonesToInput();
        });

        $('#cs-zone-toggles input[type=checkbox]').each(function() {
            var zone = $(this).data('zone');
            var df = DEFAULT_ZONES[zone] || {};
            if (savedZones[zone] !== undefined) {
                // If saved, use saved enabled state
                $(this).prop('checked', savedZones[zone].enabled !== false);
            } else {
                // Not in saved — use DEFAULT_ZONES enabled
                $(this).prop('checked', df.enabled === true);
            }
        });

        $('#cs-upload-icon').css('color', templateUrl ? '#0a7242' : '#c3c4c7');
        $('#cs-upload-label').text(templateUrl ? 'Template loaded. Click to replace.' : 'Click to upload');
        $('#cs-size-info').html('');

        if (templateUrl) {
            $('#cs-editor-placeholder').hide();
            initCsZoneEditor(templateUrl, savedZones && Object.keys(savedZones).length ? savedZones : null);
        } else {
            $('#cs-editor-placeholder').show();
            $('#cs-zone-canvas-wrap').hide();
        }

        $('#BatchMate-cardsetup-overlay').fadeIn(200);
        $('#BatchMate-cardsetup-modal').css('display','flex');
    }

    function closeCardSetup() {
        $('#BatchMate-cardsetup-overlay').fadeOut(200);
        $('#BatchMate-cardsetup-modal').hide();
        $('#cs-zone-canvas-wrap').find('.czn').remove();
        $('#cs_template_file_input').val('');
    }

    // Wire up Card Setup button on events table
    $('#event-list-table tbody').on('click', '.card-setup-btn', function() {
        var $b = $(this);
        openCardSetup(
            $b.data('id'),
            $b.attr('data-name').replace(/&quot;/g,'"'),
            $b.attr('data-cardtemplateurl') || '',
            $b.attr('data-cardzonesjson')   || ''
        );
    });

    $('#cardsetup-close, #cardsetup-cancel').on('click', closeCardSetup);
    $('#BatchMate-cardsetup-modal').on('click', function(e){ e.stopPropagation(); });

    $('#cs-upload-area').on('mouseenter', function(){ $(this).css('border-color','#2271b1'); })
                        .on('mouseleave', function(){ $(this).css('border-color','#c3c4c7'); });

    $('#cs_template_file_input').on('change', function() {
        var file = this.files[0];
        if (!file) return;
        var fd = new FormData();
        fd.append('action',        'BatchMate_upload_card_template');
        fd.append('_wpnonce',      event_nonce);
        fd.append('card_template', file);
        $('#cs-uploading').show();
        $('#cs-size-info').html('');
        $.ajax({ url: ajax_url, type: 'POST', data: fd, processData: false, contentType: false })
        .done(function(res) {
            if (res.success) {
                $('#cs_card_template_url').val(res.data.url);
                $('#cs-upload-icon').css('color', '#0a7242');
                $('#cs-upload-label').text('Template uploaded! Click to replace.');

                var tmpImg = new Image();
                tmpImg.onload = function() {
                    var w = tmpImg.naturalWidth, h = tmpImg.naturalHeight;
                    var ok = (w === 1080 && h === 1080);
                    var col = ok ? '#0a7242' : '#d63638';
                    var note = ok ? '&#10003; Correct size (1080 &times; 1080)' : '&#9888; Recommended: 1080 &times; 1080 px';
                    $('#cs-size-info').html(
                        '<span style="font-weight:700;color:' + col + ';">' + w + ' &times; ' + h + ' px</span> ' +
                        '<span style="color:' + col + ';">' + note + '</span>'
                    );

                    $('#cs-editor-placeholder').hide();
                    var currentZonesStr = $('#cs_card_zones_json').val();
                    var existingZones = null;
                    try { if (currentZonesStr) existingZones = JSON.parse(currentZonesStr); } catch(e){}
                    initCsZoneEditor(res.data.url, existingZones);
                };
                tmpImg.src = res.data.url;
            } else {
                Swal.fire('Upload Failed', res.data, 'error');
            }
        })
        .fail(function() { Swal.fire('Error', 'Upload failed.', 'error'); })
        .always(function() { $('#cs-uploading').hide(); });
    });


    function initCsZoneEditor(templateUrl, savedZones) {
        var $wrap = $('#cs-zone-canvas-wrap');
        var $bg   = $('#cs-zone-bg');
        $bg.attr('src', templateUrl);
        $wrap.show();

        $bg.off('load.cseditor').on('load.cseditor', function() {
            var imgH   = $bg[0].naturalHeight || 1080;
            var imgW   = $bg[0].naturalWidth  || 1080;
            var dispH  = $bg[0].offsetHeight  || $bg.height();
            _editorScale = dispH / imgH;

            $wrap.find('.czn').remove();
            _currentZones = {};

            // Determine which zones to show based on toggles + saved zones
            var src = savedZones
                ? JSON.parse(JSON.stringify(savedZones))
                : JSON.parse(JSON.stringify(DEFAULT_ZONES));

            $.each(DEFAULT_ZONES, function(key, dfZone) {
                var z = src[key] || dfZone;
                var $checkbox = $('#cs-toggle-' + key);

                // Skip zones not enabled (optional zones unchecked)
                var isEnabled = dfZone.always || ($checkbox.length === 0) || $checkbox.is(':checked');
                // For saved zones: use saved enabled state
                if (src[key] !== undefined && src[key].enabled === false) isEnabled = false;
                if (src[key] !== undefined && src[key].enabled === true) isEnabled = true;

                if (!isEnabled) {
                    // Store with enabled=false but don't show on canvas
                    _currentZones[key] = $.extend({}, dfZone, z, {enabled: false});
                    return; // skip rendering
                }

                var $zone, $lbl;
                if (key === 'photo') {
                    var shape = z.shape || $('#cs-photo-shape').val() || 'square';
                    var pw = (z.w ? z.w : (z.r ? z.r * 2 : (dfZone.w || 320))) * _editorScale;
                    var ph = (z.h ? z.h : (z.r ? z.r * 2 : (dfZone.h || 320))) * _editorScale;
                    var px = (z.x !== undefined ? z.x : ((z.cx || dfZone.cx || 540) - (pw / _editorScale) / 2)) * _editorScale;
                    var py = (z.y !== undefined ? z.y : ((z.cy || dfZone.cy || 380) - (ph / _editorScale) / 2)) * _editorScale;
                    var br = (shape === 'circle') ? '50%' : (shape === 'rounded' ? '16px' : '2px');

                    $zone = $('<div class="czn czn-photo" data-key="photo"></div>').css({
                        position:'absolute', borderRadius: br,
                        left: px + 'px', top: py + 'px',
                        width: pw + 'px', height: ph + 'px',
                        border: '3px solid ' + dfZone.color,
                        background: 'rgba(59,130,246,.15)',
                        cursor: 'move', boxSizing: 'border-box', zIndex: 10,
                    });
                    $lbl = $('<span class="cs-lbl" style="position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);color:'+dfZone.color+';font-size:13px;font-weight:700;pointer-events:none;">Photo</span>');
                    $zone.append('<div class="cs-resizer" style="width:14px;height:14px;background:'+dfZone.color+';position:absolute;bottom:-4px;right:-4px;border-radius:'+(shape==='circle'?'50%':'2px')+';cursor:se-resize;z-index:20;"></div>');
                } else {
                    var px = (z.x !== undefined ? z.x : dfZone.x) * _editorScale;
                    var py = ((z.y !== undefined ? z.y : dfZone.y) - (z.h || dfZone.h)) * _editorScale;
                    var pw = (z.w || dfZone.w) * _editorScale;
                    var ph = (z.h || dfZone.h) * _editorScale;
                    var displayLabel = (key === 'free_text' && z.text) ? z.text : dfZone.label;
                    $zone = $('<div class="czn czn-rect" data-key="'+key+'"></div>').css({
                        position:'absolute',
                        left:px+'px', top:py+'px',
                        width:pw+'px', height:ph+'px',
                        border:'2.5px solid '+(dfZone.color||'#888'),
                        background:'rgba('+hexToRgb(dfZone.color||'#888')+',.18)',
                        cursor:'move', boxSizing:'border-box', zIndex:10,
                    });
                    $lbl = $('<span class="cs-lbl" style="position:absolute;top:50%;left:8px;transform:translateY(-50%);color:'+(dfZone.color||'#888')+';font-size:12px;font-weight:700;pointer-events:none;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:calc(100% - 16px);">'+displayLabel+'</span>');
                    $zone.append('<div class="cs-resizer" style="width:10px;height:10px;background:'+(dfZone.color||'#888')+';position:absolute;bottom:0;right:0;cursor:se-resize;z-index:20;"></div>');
                }
                $zone.append($lbl);
                $wrap.append($zone);
                makeCsDraggable($zone, key);
                if (key === 'photo') {
                    _currentZones.photo = $.extend({}, dfZone, z, {
                        x: Math.round(px / _editorScale),
                        y: Math.round(py / _editorScale),
                        w: Math.round(pw / _editorScale),
                        h: Math.round(ph / _editorScale),
                        cx: Math.round((px + pw / 2) / _editorScale),
                        cy: Math.round((py + ph / 2) / _editorScale),
                        r: Math.round(Math.min(pw, ph) / 2 / _editorScale),
                        shape: shape,
                        enabled: true
                    });
                } else {
                    _currentZones[key] = $.extend({}, dfZone, z, {enabled: true});
                }
            });
            saveCsZonesToInput();
        });
        if ($bg[0].complete && $bg[0].naturalWidth) $bg.trigger('load.cseditor');
    }

    function makeCsDraggable($el, key) {
        var startX, startY, startLeft, startTop, startW, startH;
        var isResizing = false;
        
        $el.on('mousedown', function(e) {
            e.preventDefault();
            e.stopPropagation();
            startX    = e.clientX; startY    = e.clientY;
            startLeft = parseInt($el.css('left')); startTop = parseInt($el.css('top'));
            startW    = parseInt($el.css('width')); startH  = parseInt($el.css('height'));
            
            isResizing = $(e.target).hasClass('cs-resizer');

            $(document).on('mousemove.csdrag', function(ev) {
                var dx = ev.clientX - startX;
                var dy = ev.clientY - startY;
                
                if (isResizing) {
                    if (key === 'photo') {
                        var curShape = $('#cs-photo-shape').val() || 'square';
                        if (curShape === 'circle') {
                            var d = Math.max(dx, dy); 
                            $el.css({ width: Math.max(30, startW + d)+'px', height: Math.max(30, startW + d)+'px' });
                        } else {
                            $el.css({ width: Math.max(30, startW + dx)+'px', height: Math.max(30, startH + dy)+'px' });
                        }
                    } else {
                        $el.css({ width: Math.max(20, startW + dx)+'px', height: Math.max(10, startH + dy)+'px' });
                    }
                } else {
                    $el.css({ left:(startLeft + dx)+'px', top: (startTop + dy)+'px' });
                }
            }).on('mouseup.csdrag', function() {
                $(document).off('.csdrag');
                updateCsZoneFromEl($el, key);
                saveCsZonesToInput();
            });
        });
    }

    function updateCsZoneFromEl($el, key) {
        var s = _editorScale || 1;
        var l = parseInt($el.css('left'));
        var t = parseInt($el.css('top'));
        var w = parseInt($el.css('width'));
        var h = parseInt($el.css('height'));
        if (key === 'photo') {
            var shape = $('#cs-photo-shape').val() || 'square';
            _currentZones.photo = {
                x: Math.round(l / s),
                y: Math.round(t / s),
                w: Math.round(w / s),
                h: Math.round(h / s),
                cx: Math.round((l + w/2) / s),
                cy: Math.round((t + h/2) / s),
                r:  Math.round(Math.min(w, h) / 2 / s),
                shape: shape,
                enabled: true
            };
        } else if (key === 'name') {
            var df = DEFAULT_ZONES[key];
            var customSize = parseInt($('#cs-size-name').val(), 10);
            var finalSize = customSize ? customSize : (df ? (df.size || 26) : 26);
            if (finalSize >= 34) finalSize = 26;
            _currentZones.name = {
                x:          Math.round(l / s),
                y:          Math.round((t + h) / s),
                w:          Math.round(w / s),
                h:          Math.round(h / s),
                size:       finalSize,
                color_text: $('#cs-color-name').val() || '#ffffff',
                enabled:    true
            };
        } else if (key === 'serial') {
            var df = DEFAULT_ZONES[key];
            var customSize = parseInt($('#cs-size-serial').val(), 10);
            _currentZones.serial = {
                x:          Math.round(l / s),
                y:          Math.round((t + h) / s),
                w:          Math.round(w / s),
                h:          Math.round(h / s),
                size:       customSize ? customSize : (df ? (df.size || 28) : 28),
                color_text: $('#cs-color-serial').val() || '#ffffff',
                prefix:     $('#cs-serial-prefix').val() || '',
                enabled:    true
            };
        } else if (key === 'free_text') {
            var df = DEFAULT_ZONES[key];
            var customSize = parseInt($('#cs-size-free_text').val(), 10);
            _currentZones.free_text = {
                x:          Math.round(l / s),
                y:          Math.round((t + h) / s),
                w:          Math.round(w / s),
                h:          Math.round(h / s),
                size:       customSize ? customSize : (df ? (df.size || 26) : 26),
                color_text: $('#cs-color-free_text').val() || '#ffffff',
                text:       $('#cs-freetext-val').val() || '',
                enabled:    true
            };
        } else {
            var df = DEFAULT_ZONES[key];
            var colInput = $('#cs-color-' + key);
            var sizeInput = $('#cs-size-' + key);
            var customSize = sizeInput.length ? parseInt(sizeInput.val(), 10) : 0;
            _currentZones[key] = {
                x:          Math.round(l / s),
                y:          Math.round((t + h) / s),
                w:          Math.round(w / s),
                h:          Math.round(h / s),
                size:       customSize ? customSize : (df ? (df.size || 26) : 26),
                color_text: colInput.length ? colInput.val() : (df ? df.color_text : '#ffffff'),
                enabled:    true
            };
        }
    }

    function saveCsZonesToInput() {
        if (!_currentZones) _currentZones = {};
        if (_currentZones.photo) {
            _currentZones.photo.shape = $('#cs-photo-shape').val() || 'square';
        }
        if (_currentZones.name) {
            _currentZones.name.color_text = $('#cs-color-name').val() || '#ffffff';
            var nSize = parseInt($('#cs-size-name').val(), 10);
            if (nSize) _currentZones.name.size = (nSize >= 34 ? 26 : nSize);
        }
        ['serial', 'blood', 'district', 'batch', 'free_text'].forEach(function(zk) {
            if (_currentZones[zk]) {
                var szVal = parseInt($('#cs-size-' + zk).val(), 10);
                if (szVal) _currentZones[zk].size = szVal;
            }
        });
        if (_currentZones.serial) {
            _currentZones.serial.prefix     = $('#cs-serial-prefix').val() || '';
            _currentZones.serial.color_text = $('#cs-color-serial').val() || '#ffffff';
        }
        if (_currentZones.blood) {
            _currentZones.blood.color_text  = $('#cs-color-blood').val() || '#ffffff';
        }
        if (_currentZones.district) {
            _currentZones.district.color_text = $('#cs-color-district').val() || '#ffffff';
        }
        if (_currentZones.free_text) {
            _currentZones.free_text.text       = $('#cs-freetext-val').val() || '';
            _currentZones.free_text.color_text = $('#cs-color-free_text').val() || '#ffffff';
        }
        $('#cs-zone-toggles input[type=color]').each(function() {
            var z = $(this).data('zone');
            if (z && _currentZones[z]) {
                _currentZones[z].color_text = $(this).val() || '#ffffff';
            }
        });
        $('#cs_card_zones_json').val(JSON.stringify(_currentZones));
    }

    function hexToRgb(hex) {
        hex = hex.replace('#','');
        if (hex.length === 3) hex = hex[0]+hex[0]+hex[1]+hex[1]+hex[2]+hex[2];
        return parseInt(hex.substr(0,2),16)+','+parseInt(hex.substr(2,2),16)+','+parseInt(hex.substr(4,2),16);
    }

    $('#cardsetup-save').on('click', function() {
        saveCsZonesToInput();
        var eventId     = $('#cs_event_id').val();
        var templateUrl = $('#cs_card_template_url').val();
        var zonesJson   = $('#cs_card_zones_json').val();

        if (!eventId) { Swal.fire('Error', 'Event ID missing.', 'error'); return; }
        if (!templateUrl) { Swal.fire('No Template', 'Please upload a card template image first.', 'warning'); return; }

        var $btn = $(this);
        $btn.prop('disabled', true).html('<span class="dashicons dashicons-update" style="animation:spin 1s linear infinite"></span> Saving...');

        $.ajax({
            url: ajax_url, type: 'POST',
            data: {
                action:            'BatchMate_save_event_card_setup',
                _wpnonce:          event_nonce,
                event_id:          eventId,
                card_template_url: templateUrl,
                card_zones_json:   zonesJson,
            }
        }).done(function(res) {
            if (res.success) {
                Swal.fire({ title: 'Saved!', text: 'Card setup saved successfully.', icon: 'success', timer: 1600, showConfirmButton: false });
                closeCardSetup();
                evtTable.ajax.reload(null, false);
                regTable.ajax.reload(null, false);
            } else {
                Swal.fire('Error', res.data, 'error');
            }
        }).fail(function() {
            Swal.fire('Error', 'Request failed.', 'error');
        }).always(function() {
            $btn.prop('disabled', false).html('<span class="dashicons dashicons-saved"></span> Save Card Setup');
        });
    });


    
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
                regTable.ajax.reload(null, false);
            } else { Swal.fire('Error!', res.data, 'error'); }
        }).always(function(){
            $('#BatchMate-editapp-save').prop('disabled',false).html('<span class="dashicons dashicons-saved"></span> Save Changes');
        });
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
        $('#modal_event_has_id_card').prop('checked', $b.data('hasidcard') == 1);
        $('#modal_event_auto_send_card').prop('checked', $b.data('autosendcard') == 1);
        $('#modal_event_has_tshirt').prop('checked', $b.data('hastshirt') == 1);
        $('#modal_event_has_saree').prop('checked', $b.data('hassaree') == 1);
        $('#modal_event_payment_methods').val($b.attr('data-paymentmethods') || '');
        $('#modal_event_cc_email').val($b.data('ccemail') || '');
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



    
    $(document).on('click', function(e) {
        if (!$(e.target).closest('.BatchMate-actions-btn-container').length) {
            $('.BatchMate-actions-dropdown').hide();
        }
    });
    $(document).on('click', '.BatchMate-actions-btn', function(e) {
        e.stopPropagation();
        var $dd = $(this).next('.BatchMate-actions-dropdown');
        $('.BatchMate-actions-dropdown').not($dd).hide();
        $dd.toggle();
    });

    
    
    
    var transTable;

    function initTransTable() {
        if (!transTable) {
            transTable = $('#event-transactions-table').DataTable({
                ajax: {
                    url: ajax_url, type: 'POST',
                    data: function(d) {
                        d.action = 'BatchMate_get_event_transactions';
                        d._wpnonce = event_nonce;
                        d.event_id = $('#filter-expenses-event').val();
                    }
                },
                columns: [
                    { data: 'id' },
                    { data: 'trans_date' },
                    { data: 'trans_type', render: function(data){
                        return data === 'sponsor' ? '<span style="color:#15803d;font-weight:bold;">Sponsorship</span>' : '<span style="color:#b91c1c;font-weight:bold;">Expense</span>';
                    }},
                    { data: 'purpose' },
                    { data: 'amount', render: function(data){ return '&#2547; ' + parseFloat(data).toFixed(2); } },
                    { data: null, className: 'BatchMate-no-print', orderable: false, render: function(data, type, row) {
                        return `<div style="display:inline-flex;gap:6px;">
                        <button class="button button-small BatchMate-trans-edit-btn" title="Edit Transaction" style="color:#0f2756;border-color:#cbd5e1;background:#f8fafc;padding:0;width:28px;height:28px;display:inline-flex;align-items:center;justify-content:center;"
                            data-id="${row.id}" data-type="${row.trans_type}" data-date="${row.trans_date}" data-purpose="${(row.purpose||'').replace(/"/g,'&quot;')}" data-amount="${row.amount}">
                            <span class="dashicons dashicons-edit" style="font-size:18px;width:18px;height:18px;line-height:1;"></span>
                        </button>
                        </div>`;
                    }}
                ],
                order: [[1, 'desc']],
                pageLength: 25,
                dom: 'lfrtip'
            });
        } else {
            transTable.ajax.reload(null, false);
        }
    }

    function loadEventReport() {
        var evId = $('#filter-expenses-event').val();
        if (!evId) {
            $('#expenses-report-cards, #expenses-table-wrap, #btn-new-transaction, #btn-print-report').hide();
            $('#expenses-no-event').show();
            return;
        }

        $('#expenses-no-event').hide();
        $('#btn-new-transaction, #btn-print-report').show();
        $('#expenses-report-cards, #expenses-table-wrap').show();

        
        $.ajax({ url: ajax_url, type: 'POST', data: { action: 'BatchMate_get_event_report', event_id: evId, _wpnonce: event_nonce } })
        .done(function(res) {
            if (res.success) {
                $('#rep-collection').html('&#2547; ' + parseFloat(res.data.collection).toFixed(2));
                var memCount = parseInt(res.data.members_count) || 0;
                var guestCount = parseInt(res.data.guests_count) || 0;
                var labelText = 'REGISTERED: ' + memCount;
                if (guestCount > 0) labelText += '<br>GUEST(s): ' + guestCount;
                $('#rep-collection-label').html(labelText);
                
                $('#rep-sponsorship').html('&#2547; ' + parseFloat(res.data.sponsorship).toFixed(2));
                $('#rep-expense').html('&#2547; ' + parseFloat(res.data.expense).toFixed(2));
                $('#rep-balance').html('&#2547; ' + parseFloat(res.data.net_balance).toFixed(2));
                $('#rep-balance-card').css('border-left-color', res.data.net_balance >= 0 ? '#15803d' : '#b91c1c');
                $('#rep-balance').css('color', res.data.net_balance >= 0 ? '#15803d' : '#b91c1c');
            }
        });

        initTransTable();
    }

    $('#filter-expenses-event').on('change', function(){
        $('#print-event-title').text('Event Financial Report: ' + $(this).find('option:selected').text());
        loadEventReport();
    });

    
    $('#btn-print-report').on('click', function(){
        if (!transTable) return;
        var evtLabel = $('#filter-expenses-event option:selected').text();
        var collection = $('#rep-collection').text();
        var collectionLabel = $('#rep-collection-label').html() || 'Total Collection';
        var rawCollection = parseFloat(collection.replace(/[^0-9.-]+/g,"")) || 0;

        var sponsor = $('#rep-sponsorship').text();
        var expense = $('#rep-expense').text();
        var balance = $('#rep-balance').text();

        var bodyRows = '';
        var currentBalance = rawCollection;

        bodyRows += '<tr style="font-weight:bold;background:#f0fdf4;">'+
            '<td style="text-align:center;color:#94a3b8;">—</td>'+
            '<td style="color:#94a3b8;">—</td>'+
            '<td><span style="color:#0ea5e9;font-weight:bold;">Registration</span></td>'+
            '<td>' + collectionLabel.toUpperCase() + '</td>'+
            '<td style="text-align:right;color:#15803d;">&#2547; ' + rawCollection.toFixed(2) + '</td>'+
            '<td style="text-align:right;color:#94a3b8;">—</td>'+
            '<td style="text-align:right;color:#0f2756;">&#2547; ' + currentBalance.toFixed(2) + '</td>'+
        '</tr>';

        var allRows = [];
        transTable.rows().data().each(function(row) { allRows.push(row); });
        
        allRows.sort(function(a,b) { return parseInt(a.id) - parseInt(b.id); });

        allRows.forEach(function(row) {
            var amt = parseFloat(row.amount||0);
            var incomeStr = '<span style="color:#94a3b8;">—</span>';
            var expenseStr = '<span style="color:#94a3b8;">—</span>';
            
            if (row.trans_type === 'sponsor') {
                currentBalance += amt;
                incomeStr = '&#2547; ' + amt.toFixed(2);
            } else {
                currentBalance -= amt;
                expenseStr = '&#2547; ' + amt.toFixed(2);
            }

            var typeLabel = row.trans_type === 'sponsor' ? '<span style="color:#15803d;font-weight:bold;">Sponsorship</span>' : '<span style="color:#b91c1c;font-weight:bold;">Expense</span>';
            bodyRows += '<tr>'+
                '<td style="text-align:center;">'+row.id+'</td>'+
                '<td>'+row.trans_date+'</td>'+
                '<td>'+typeLabel+'</td>'+
                '<td>'+(row.purpose||'')+'</td>'+
                '<td style="text-align:right;color:#15803d;">'+incomeStr+'</td>'+
                '<td style="text-align:right;color:#b91c1c;">'+expenseStr+'</td>'+
                '<td style="text-align:right;font-weight:bold;color:#0f2756;">&#2547; '+currentBalance.toFixed(2)+'</td>'+
            '</tr>';
        });

        var now = new Date().toLocaleString('en-GB', { day:'numeric', month:'short', year:'numeric', hour:'2-digit', minute:'2-digit', hour12:true });

        var html = `<!DOCTYPE html><html><head><title>Financial Report \u2014 ${evtLabel}</title>
<style>
  *{box-sizing:border-box;margin:0;padding:0;}
  body{font-family:Arial,sans-serif;padding:28px 36px;color:#1d2327;font-size:12px;}
  .ph{text-align:center;margin-bottom:18px;border-bottom:2px solid #2271b1;padding-bottom:14px;}
  .ph h2{font-size:18px;color:#1a3a6b;margin-bottom:6px;}
  .ph .evtag{display:inline-block;background:#e7f0fa;color:#2271b1;padding:3px 14px;border-radius:20px;font-size:13px;font-weight:700;}
  .ph .meta{font-size:11px;color:#888;margin-top:6px;}
  .sumbox{display:flex;gap:20px;justify-content:center;margin:14px 0 18px;padding:12px;background:#f8fafc;border:1.5px solid #e2e8f0;border-radius:10px;}
  .sumbox .si{flex:1;text-align:center;padding:10px;background:#fff;border:1px solid #e2e8f0;border-radius:6px;}
  .sumbox .si .val{font-size:18px;font-weight:700;line-height:1.2;margin-top:4px;}
  .sumbox .si .lbl{font-size:11px;color:#64748b;text-transform:uppercase;letter-spacing:0.5px;}
  table{width:100%;border-collapse:collapse;margin-top:20px;}
  thead{background:#2271b1;color:#fff;}
  th,td{border:1px solid #dee2e6;padding:8px 10px;}
  tr:nth-child(even) td{background:#f8f9fa;}
  .sigs{display:flex;justify-content:space-between;margin-top:80px;page-break-inside:avoid;}
  .sig-box{text-align:center;width:25%;}
  .sig-line{border-bottom:1px solid #000;margin-bottom:8px;height:40px;}
  .sig-box p{font-size:12px;font-weight:700;color:#1d2327;}
  @media print{body{padding:10px;}}
</style>
</head>
<body>
  <div class="ph">
    <h2>Event Financial Report</h2>
    <div class="evtag">&#128176; ${evtLabel}</div>
    <p class="meta">Printed: ${now}</p>
  </div>
  
  <div class="sumbox">
    <div class="si"><div class="lbl">${collectionLabel}</div><div class="val" style="color:#0f2756;">${collection}</div></div>
    <div class="si"><div class="lbl">Sponsorship</div><div class="val" style="color:#15803d;">${sponsor}</div></div>
    <div class="si"><div class="lbl">Total Expense</div><div class="val" style="color:#b91c1c;">${expense}</div></div>
    <div class="si"><div class="lbl">Net Balance</div><div class="val">${balance}</div></div>
  </div>

  <table>
    <thead>
      <tr>
        <th style="width:50px">ID</th>
        <th style="width:100px">Date</th>
        <th style="width:100px">Type</th>
        <th>Particulars / Purpose</th>
        <th style="text-align:right;width:100px">Income (In)</th>
        <th style="text-align:right;width:100px">Expense (Out)</th>
        <th style="text-align:right;width:110px">Running Balance</th>
      </tr>
    </thead>
    <tbody>${bodyRows}</tbody>
    <tfoot>
      <tr style="background:#e7f0fa;font-weight:bold;color:#0f2756;">
        <td colspan="6" style="text-align:right;padding:12px 10px;">CLOSING BALANCE:</td>
        <td style="text-align:right;padding:12px 10px;font-size:14px;">&#2547; ${currentBalance.toFixed(2)}</td>
      </tr>
    </tfoot>
  </table>

  <div class="sigs">
      <div class="sig-box"><div class="sig-line"></div><p>Prepared By</p></div>
      <div class="sig-box"><div class="sig-line"></div><p>Checked By</p></div>
      <div class="sig-box"><div class="sig-line"></div><p>Approved By</p></div>
  </div>
</body></html>`;

        var w = window.open('', '_blank');
        w.document.write(html);
        w.document.close();
        setTimeout(function(){ w.print(); }, 400);
    });

    
    function openTransModal() {
        $('#trans_event_id').val($('#filter-expenses-event').val());
        var today = new Date();
        var yyyy = today.getFullYear();
        var mm = String(today.getMonth() + 1).padStart(2, '0');
        var dd = String(today.getDate()).padStart(2, '0');
        $('#trans_date').val(yyyy + '-' + mm + '-' + dd);
        $('#BatchMate-transaction-modal-overlay').fadeIn(200);
        $('#BatchMate-transaction-modal').addClass('open');
    }
    function closeTransModal() {
        $('#BatchMate-transaction-modal').removeClass('open');
        $('#BatchMate-transaction-modal-overlay').fadeOut(200);
        $('#BatchMate-transaction-form')[0].reset();
        $('#trans_id').val(0);
        $('#BatchMate-transaction-modal-title').text('Add Transaction');
    }
    $('#btn-new-transaction').on('click', openTransModal);
    $('#BatchMate-transaction-modal-close, #BatchMate-transaction-modal-cancel').on('click', closeTransModal);

    $('#BatchMate-transaction-modal-save').on('click', function() {
        var event_id = $('#trans_event_id').val();
        var t_type = $('#trans_type').val();
        var date = $('#trans_date').val();
        var purpose = $.trim($('#trans_purpose').val());
        var amount = $('#trans_amount').val();

        if (!event_id || !date || !purpose || amount <= 0) {
            Swal.fire('Validation', 'Please fill all fields correctly.', 'warning');
            return;
        }

        $(this).prop('disabled', true).text('Saving…');
        $.ajax({ url: ajax_url, type: 'POST', data: {
            action: 'BatchMate_save_event_transaction', _wpnonce: event_nonce,
            trans_id: $('#trans_id').val(), event_id: event_id, trans_type: t_type, trans_date: date, purpose: purpose, amount: amount
        }}).done(function(res){
            if (res.success) {
                Swal.fire({ title: 'Saved!', icon: 'success', timer: 1200, showConfirmButton: false });
                closeTransModal();
                loadEventReport(); 
            } else { Swal.fire('Error!', res.data, 'error'); }
        }).always(function(){
            $('#BatchMate-transaction-modal-save').prop('disabled', false).html('<span class="dashicons dashicons-saved"></span> Save Transaction');
        });
    });

    
    $('#event-transactions-table tbody').on('click', '.BatchMate-trans-edit-btn', function() {
        var id = $(this).data('id');
        var type = $(this).data('type');
        var date = $(this).data('date');
        var purpose = $(this).data('purpose');
        var amount = $(this).data('amount');

        $('#trans_id').val(id);
        $('#trans_event_id').val($('#filter-expenses-event').val());
        $('#trans_type').val(type);
        $('#trans_date').val(date);
        $('#trans_purpose').val(purpose);
        $('#trans_amount').val(amount);

        $('#BatchMate-transaction-modal-title').text('Edit Transaction');
        $('#BatchMate-transaction-modal-overlay').fadeIn(200);
        $('#BatchMate-transaction-modal').addClass('open');
    });

    
    
    

    var _cardAppId   = 0;
    var _cardEventId = 0;

    
    $('#event-registered-table tbody').on('click', '.gen-card-btn', function() {
        var $btn      = $(this);
        _cardAppId    = parseInt($btn.data('app-id'))   || 0;
        _cardEventId  = parseInt($btn.data('event-id')) || 0;
        var memberId  = parseInt($btn.data('member-id'))  || 0;
        var rawdataId = parseInt($btn.data('rawdata-id')) || 0;
        var memberName= $btn.attr('data-member-name') || '';

        
        $('#card-modal-title').text('Registration Card');
        $('#card-modal-subtitle').text(memberName);
        $('#card-modal-loading').show();
        $('#BatchMate-card-canvas').hide();
        $('#card-btn-download').hide();
        $('#card-btn-email').hide();
        $('#BatchMate-card-modal-overlay').show();
        $('#BatchMate-card-modal').css('display','flex');

        
        $.ajax({
            url: ajax_url, type: 'POST',
            data: {
                action:     'BatchMate_get_member_card_data',
                _wpnonce:   event_nonce,
                event_id:   _cardEventId,
                member_id:  memberId,
                rawdata_id: rawdataId,
                app_id:     _cardAppId,
            }
        }).done(function(res) {
            if (!res.success) {
                $('#card-modal-loading').html('<p style="color:#d63638;">'+res.data+'</p>');
                return;
            }
            renderCardOnCanvas(res.data.member, res.data.event, res.data.card_url);
        }).fail(function() {
            $('#card-modal-loading').html('<p style="color:#d63638;">Failed to load card data.</p>');
        });
    });

    function renderCardOnCanvas(member, event, cardUrl) {
        var CW = 1080, CH = 1080;
        var canvas = document.getElementById('BatchMate-card-canvas');
        canvas.width  = CW;
        canvas.height = CH;
        var ctx = canvas.getContext('2d');

        // Priority 1: If server GD rendered the card, load and display it directly (100% match with test_event_card & emails)
        if (cardUrl) {
            var srvImg = new Image();
            srvImg.crossOrigin = 'anonymous';
            srvImg.onload = function() {
                ctx.clearRect(0, 0, CW, CH);
                ctx.drawImage(srvImg, 0, 0, CW, CH);
                showCanvas(canvas);
            };
            srvImg.onerror = function() {
                drawClientFallback();
            };
            srvImg.src = cardUrl + (cardUrl.indexOf('?') === -1 ? '?t=' : '&t=') + new Date().getTime();
            return;
        }

        drawClientFallback();

        function drawClientFallback() {
            var zones = {};
            try { zones = JSON.parse(event.card_zones_json || '{}'); } catch(e){}

            var tpl = new Image();
            tpl.crossOrigin = 'anonymous';
            tpl.onload = function() {
                ctx.drawImage(tpl, 0, 0, CW, CH);

                var pz = zones.photo || { cx:540, cy:380, r:160, shape:'square' };
                if (member.photo_url) {
                    var photo = new Image();
                    photo.crossOrigin = 'anonymous';
                    photo.onload = function() {
                        ctx.save();
                        var pShape = pz.shape || 'square';
                        var pw = pz.w ? pz.w : (pz.r ? pz.r * 2 : 320);
                        var ph = pz.h ? pz.h : (pz.r ? pz.r * 2 : 320);
                        var px = (pz.x !== undefined) ? pz.x : ((pz.cx || 540) - pw / 2);
                        var py = (pz.y !== undefined) ? pz.y : ((pz.cy || 380) - ph / 2);

                        ctx.beginPath();
                        if (pShape === 'circle') {
                            var cx = px + pw / 2;
                            var cy = py + ph / 2;
                            var r  = Math.min(pw, ph) / 2;
                            ctx.arc(cx, cy, r, 0, Math.PI * 2);
                        } else if (pShape === 'rounded') {
                            if (ctx.roundRect) {
                                ctx.roundRect(px, py, pw, ph, 16);
                            } else {
                                ctx.rect(px, py, pw, ph);
                            }
                        } else {
                            ctx.rect(px, py, pw, ph);
                        }
                        ctx.closePath();
                        ctx.clip();

                        // Cover fit (no stretching or distortion)
                        var sw = photo.naturalWidth;
                        var sh = photo.naturalHeight;
                        var scale = Math.max(pw / sw, ph / sh);
                        var dw = sw * scale;
                        var dh = sh * scale;
                        var dx = px + (pw - dw) / 2;
                        var dy = py + (ph - dh) / 2;

                        ctx.drawImage(photo, dx, dy, dw, dh);
                        ctx.restore();

                        drawCardTexts(ctx, zones, member);
                        showCanvas(canvas);
                    };
                    photo.onerror = function() {
                        drawCardTexts(ctx, zones, member);
                        showCanvas(canvas);
                    };
                    photo.src = member.photo_url;
                } else {
                    drawCardTexts(ctx, zones, member);
                    showCanvas(canvas);
                }
            };
            tpl.onerror = function() {
                $('#card-modal-loading').html('<p style="color:#d63638;">Failed to load template image.</p>');
            };
            tpl.src = event.card_template_url;
        }
    }

    // Helper: draw fitted & wrapped name without overflowing (strictly left-aligned, centered vertically)
    function drawFittedName(ctx, text, x, y, w, h, maxFontSize, color) {
        if (!text) return;
        var fontSize = maxFontSize || 26;
        if (fontSize >= 34) fontSize = 26; // adjust legacy 36 down to 26
        ctx.fillStyle = color || '#ffffff';
        ctx.textAlign = 'left';
        ctx.textBaseline = 'middle';

        function getLines(sz) {
            ctx.font = 'bold ' + sz + 'px Montserrat, Arial, sans-serif';
            var words = text.split(/\s+/);
            var lines = [];
            var cur = '';
            for (var i = 0; i < words.length; i++) {
                var test = cur ? (cur + ' ' + words[i]) : words[i];
                if (ctx.measureText(test).width <= w) {
                    cur = test;
                } else {
                    if (cur) lines.push(cur);
                    cur = words[i];
                }
            }
            if (cur) lines.push(cur);
            return lines;
        }

        // Test single line first
        ctx.font = 'bold ' + fontSize + 'px Montserrat, Arial, sans-serif';
        if (ctx.measureText(text).width <= w) {
            ctx.fillText(text, x, y + h / 2);
            return;
        }

        // Try wrapping into 2 lines, scale down if needed
        var lines = getLines(fontSize);
        while ((lines.length > 2 || (lines.length * fontSize * 1.15 > h) || lines.some(function(l){ return ctx.measureText(l).width > w; })) && fontSize > 16) {
            fontSize -= 2;
            lines = getLines(fontSize);
        }

        ctx.font = 'bold ' + fontSize + 'px Montserrat, Arial, sans-serif';
        var lineHeight = fontSize * 1.15;
        var totalH = lines.length * lineHeight;
        var startY = (y + h / 2) - (totalH / 2) + (lineHeight / 2);

        for (var k = 0; k < lines.length; k++) {
            ctx.fillText(lines[k], x, startY + (k * lineHeight));
        }
    }

    function drawCardTexts(ctx, zones, member) {
        // Helper: check if a zone is enabled
        function zoneEnabled(key) {
            if (!zones[key]) return false;
            return zones[key].enabled !== false; // default true unless explicitly false
        }

        // -- Name (always draw with line wrap & auto-fit) --
        var nz = zones.name || { x:140, y:640, w:800, h:60, size:26, color_text:'#ffffff' };
        var nSize = nz.size || 26;
        if (nSize >= 34) nSize = 26;
        var nameTop = (nz.y !== undefined && nz.h) ? (nz.y - nz.h) : (640 - 60);
        drawFittedName(ctx, member.name || '', nz.x || 140, nameTop, nz.w || 800, nz.h || 60, nSize, nz.color_text || '#ffffff');

        ctx.textAlign = 'left';
        ctx.textBaseline = 'middle';

        // -- Serial No (with prefix) --
        if (zoneEnabled('serial')) {
            var sz = zones.serial;
            var rawSerial = member.serial_number || (member.serial_no ? String(member.serial_no).padStart(4, '0') : ('#' + String(member.id || 1).padStart(4, '0')));
            var prefix = (sz.prefix !== undefined) ? String(sz.prefix).trim() : '';
            var serialVal = rawSerial;
            if (prefix !== '') {
                var sep = (prefix.endsWith('-') || prefix.endsWith('#') || prefix.endsWith(' ')) ? '' : ' ';
                var cleanSerial = rawSerial;
                if (!member.serial_number && /^[A-Za-z0-9]+[-_]/.test(rawSerial)) {
                    cleanSerial = rawSerial.replace(/^[A-Za-z0-9]+[-_]/, '');
                }
                serialVal = prefix + sep + cleanSerial;
            }
            ctx.font = 'bold ' + (sz.size||28) + 'px Montserrat, Arial, sans-serif';
            ctx.fillStyle = sz.color_text || '#ffffff';
            var sy = (sz.y !== undefined && sz.h) ? (sz.y - sz.h / 2) : (sz.y || 220);
            ctx.fillText(serialVal, sz.x||820, sy);
        }

        // -- Blood Group --
        if (zoneEnabled('blood')) {
            var bz = zones.blood;
            var bloodVal = member.blood_group || 'N/A';
            ctx.font = 'bold ' + (bz.size||26) + 'px Montserrat, Arial, sans-serif';
            ctx.fillStyle = bz.color_text || '#ffffff';
            var by = (bz.y !== undefined && bz.h) ? (bz.y - bz.h / 2) : (bz.y || 730);
            ctx.fillText(bloodVal, bz.x||420, by);
        }

        // -- District (জেলা) from member database --
        if (zoneEnabled('district')) {
            var dz = zones.district;
            var distVal = member.district || member.present_district || member.permanent_district || '';
            if (distVal) {
                ctx.font = 'bold ' + (dz.size||26) + 'px Montserrat, Arial, sans-serif';
                ctx.fillStyle = dz.color_text || '#ffffff';
                var dy = (dz.y !== undefined && dz.h) ? (dz.y - dz.h / 2) : (dz.y || 790);
                ctx.fillText(distVal, dz.x||420, dy);
            }
        }

        // -- Free Text (কাস্টম টেক্সট) --
        if (zoneEnabled('free_text')) {
            var ftz = zones.free_text;
            var ftVal = ftz.text || '';
            if (ftVal) {
                ctx.font = 'bold ' + (ftz.size||26) + 'px Montserrat, Arial, sans-serif';
                ctx.fillStyle = ftz.color_text || '#ffffff';
                var fty = (ftz.y !== undefined && ftz.h) ? (ftz.y - ftz.h / 2) : (ftz.y || 910);
                ctx.fillText(ftVal, ftz.x||420, fty);
            }
        }

        // -- Batch --
        if (zoneEnabled('batch')) {
            var btz = zones.batch;
            var batchVal = member.batch || 'N/A';
            ctx.font = 'bold ' + (btz.size||26) + 'px Montserrat, Arial, sans-serif';
            ctx.fillStyle = btz.color_text || '#ffffff';
            var bty = (btz.y !== undefined && btz.h) ? (btz.y - btz.h / 2) : (btz.y || 850);
            ctx.fillText(batchVal, btz.x||420, bty);
        }

        // Legacy compatibility for older cards with dept/type
        if (zoneEnabled('type')) {
            var tz = zones.type;
            ctx.font = 'bold ' + (tz.size||26) + 'px Montserrat, Arial, sans-serif';
            ctx.fillStyle = tz.color_text || '#ffffff';
            var ty = (tz.y !== undefined && tz.h) ? (tz.y - tz.h / 2) : (tz.y || 790);
            ctx.fillText(member.type || 'Member', tz.x||420, ty);
        }
        if (zoneEnabled('dept')) {
            var dpz = zones.dept;
            ctx.font = 'bold ' + (dpz.size||26) + 'px Montserrat, Arial, sans-serif';
            ctx.fillStyle = dpz.color_text || '#ffffff';
            var dpy = (dpz.y !== undefined && dpz.h) ? (dpz.y - dpz.h / 2) : (dpz.y || 910);
            ctx.fillText(member.program_short_code || member.program_name || 'N/A', dpz.x||420, dpy);
        }
    }

    function showCanvas(canvas) {
        $('#card-modal-loading').hide();
        $('#BatchMate-card-canvas').show();
        $('#card-btn-download').show();
        $('#card-btn-email').show();
    }

    
    $('#card-modal-close').on('click', function() {
        $('#BatchMate-card-modal-overlay').hide();
        $('#BatchMate-card-modal').hide();
        _cardAppId = 0; _cardEventId = 0;
    });

    
    $('#card-btn-download').on('click', function() {
        var canvas = document.getElementById('BatchMate-card-canvas');
        var link = document.createElement('a');
        link.download = 'registration-card.png';
        link.href = canvas.toDataURL('image/png');
        link.click();
    });

    
    $('#card-btn-email').on('click', function() {
        var $btn = $(this);
        var canvas = document.getElementById('BatchMate-card-canvas');
        var b64 = canvas.toDataURL('image/png');

        $btn.prop('disabled', true).html('<span class="dashicons dashicons-update" style="animation:spin 1s linear infinite"></span> Sending…');

        $.ajax({
            url: ajax_url, type: 'POST',
            data: {
                action:      'BatchMate_send_event_card_email',
                _wpnonce:    event_nonce,
                app_id:      _cardAppId,
                event_id:    _cardEventId
            }
        }).done(function(res) {
            if (res.success) {
                Swal.fire({ title:'Sent!', text: res.data, icon:'success', timer:2000, showConfirmButton:false });
            } else {
                Swal.fire('Error', res.data, 'error');
            }
        }).fail(function(xhr, textStatus, errorThrown){
            var errDetails = 'Status: ' + xhr.status + ' ' + errorThrown;
            if (xhr.responseText) {
                console.error("AJAX Error Response:", xhr.responseText);
            }
            Swal.fire('Error', 'Request failed.<br><small>' + errDetails + '</small>', 'error');
        }).always(function(){
            $btn.prop('disabled', false).html('<span class="dashicons dashicons-email-alt"></span> Send to Email');
        });
    });

    // Final tab activation after all DataTables and handlers are ready
    var initTab = window.location.hash.replace('#', '') || ((window.batchmate_events_cfg && window.batchmate_events_cfg.reqTab) ? window.batchmate_events_cfg.reqTab : 'tab-registered');
    if (initTab && $('.BatchMate-ev-tab[data-tab="' + initTab + '"]').length) {
        switchToTab(initTab);
    }

}); 
