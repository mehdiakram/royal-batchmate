/**
 * Royal BatchMate - Events Core & Navigation
 */
jQuery(document).ready(function($) {
    const BM = window.BatchMateEvents = window.BatchMateEvents || {};
    BM.ajax_url    = batchmate_admin_ajax.ajax_url;
    BM.event_nonce = batchmate_admin_ajax.event_nonce;
    BM.noPhoto     = (window.batchmate_events_cfg && window.batchmate_events_cfg.noPhoto) ? window.batchmate_events_cfg.noPhoto : '';
    BM.noPhotoUrl  = (window.batchmate_events_cfg && window.batchmate_events_cfg.noPhotoUrl) ? window.batchmate_events_cfg.noPhotoUrl : '';

    BM.copyToClipboard = function(text, $el) {
        navigator.clipboard.writeText(text).then(function() {
            var orig = $el.html();
            $el.html('<span class="dashicons dashicons-yes"></span> Copied!');
            setTimeout(function(){ $el.html(orig); }, 1600);
        });
    };

    function switchToTab(tab) {
        if (!tab) return;
        if (tab.indexOf('tab-') !== 0) tab = 'tab-' + tab;
        $('.BatchMate-ev-tab').removeClass('active');
        $('.BatchMate-ev-tab[data-tab="' + tab + '"]').addClass('active');
        $('.BatchMate-ev-tab-content').removeClass('active').css('display', 'none');
        $('#' + tab).addClass('active').css('display', 'block');

        setTimeout(function() {
            if (tab === 'tab-registered' && BM.regTable) {
                try { BM.regTable.columns.adjust().draw(); } catch(e){}
                try { BM.regTable.ajax.reload(null, false); } catch(e){}
            }
            if (tab === 'tab-applications' && BM.appTable) {
                try { BM.appTable.columns.adjust().draw(); } catch(e){}
                try { BM.appTable.ajax.reload(null, false); } catch(e){}
            }
            if (tab === 'tab-events' && BM.evtTable) {
                try { BM.evtTable.columns.adjust().draw(); } catch(e){}
                try { BM.evtTable.ajax.reload(null, false); } catch(e){}
            }
            if (tab === 'tab-expenses' && typeof BM.loadEventReport === 'function') {
                try { BM.loadEventReport(); } catch(e){}
            }
        }, 40);
    }
    BM.switchToTab = switchToTab;
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

    $('.BatchMate-ev-card--clickable').on('click', function() {
        var tab = $(this).data('goto-tab');
        if (tab) {
            switchToTab(tab);
            $('html, body').animate({ scrollTop: $('.BatchMate-ev-tab-bar').offset().top - 40 }, 400);
        }
    });

    // Shared actions dropdown handler
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
});
