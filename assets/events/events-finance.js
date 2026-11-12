/**
 * Royal BatchMate - Events Expenses & Financial Report
 */
jQuery(document).ready(function($) {
    const BM          = window.BatchMateEvents = window.BatchMateEvents || {};
    const ajax_url    = BM.ajax_url;
    const event_nonce = BM.event_nonce;
    const noPhoto     = BM.noPhoto;

    
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

    BM.loadEventReport = function() {
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
        BM.loadEventReport();
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
                BM.loadEventReport(); 
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

    
    

});
