/**
 * Royal BatchMate - Admin Events Entry / Bootstrap
 * Sub-modules live in assets/events/:
 *  - events-core.js
 *  - events-registered.js
 *  - events-applications.js
 *  - events-crud.js
 *  - events-card.js
 *  - events-finance.js
 */
jQuery(document).ready(function($) {
    var BM = window.BatchMateEvents || {};
    var initTab = window.location.hash.replace('#', '') ||
        ((window.batchmate_events_cfg && window.batchmate_events_cfg.reqTab) ? window.batchmate_events_cfg.reqTab : 'tab-registered');

    if (initTab && $('.BatchMate-ev-tab[data-tab="' + initTab + '"]').length && typeof BM.switchToTab === 'function') {
        BM.switchToTab(initTab);
    }
});
