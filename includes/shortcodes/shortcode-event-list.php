<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * ===================================================================
 * [event_list id="X"] � Frontend Registered Members List (Searchable)
 * ===================================================================
 */
function batchmate_event_list_shortcode( $atts ) {
    $atts     = shortcode_atts( array( 'id' => 0 ), $atts, 'event_list' );
    $event_id = intval( $atts['id'] );

    if ( ! $event_id ) return '<p>Invalid event list. Please specify a valid event ID.</p>';

    global $wpdb;

    $event = $wpdb->get_row( $wpdb->prepare(
        "SELECT event_name, event_date FROM {$wpdb->prefix}batchmate_event WHERE id = %d", $event_id
    ) );

    if ( ! $event ) return '<p>Event not found.</p>';

    $members = $wpdb->get_results( $wpdb->prepare(
        "SELECT em.id,
                em.serial_no,
                IF(em.member_id > 0, m.member_number, 'Non-Member') AS member_number,
                IF(em.member_id > 0, m.name, r.name) AS name,
                IF(em.member_id > 0, m.photo_url, r.photo_url) AS photo_url,
                IF(em.member_id > 0, m.batch, '') AS batch,
                IF(em.member_id > 0, m.employment_position, '') AS employment_position,
                IF(em.member_id > 0, m.institution, r.institution) AS institution,
                COALESCE(ea.amount, 0) AS amount
         FROM {$wpdb->prefix}batchmate_event_members em
         LEFT JOIN {$wpdb->prefix}batchmate_member     m  ON em.member_id  = m.id
         LEFT JOIN {$wpdb->prefix}batchmate_rawdata    r  ON em.rawdata_id = r.id
         LEFT JOIN {$wpdb->prefix}batchmate_event_application ea
                ON ea.event_id = em.event_id
               AND (ea.member_id = em.member_id OR ea.rawdata_id = em.rawdata_id)
         WHERE em.event_id = %d
         GROUP BY em.id
         ORDER BY em.serial_no ASC, em.id ASC",
        $event_id
    ) );

    $total_guests = 0;
    $apps = $wpdb->get_results( $wpdb->prepare( "
        SELECT ea.options_json 
        FROM {$wpdb->prefix}batchmate_event_application ea
        INNER JOIN {$wpdb->prefix}batchmate_event_members em ON ea.id = em.application_id
        WHERE ea.event_id = %d
    ", $event_id ) );

    foreach ( $apps as $app ) {
        if ( ! empty( $app->options_json ) ) {
            $opts = json_decode( stripslashes( $app->options_json ), true );
            if ( is_string( $opts ) ) $opts = json_decode( $opts, true );
            if ( is_array( $opts ) ) {
                foreach ( $opts as $o ) {
                    $total_guests += intval( $o['qty'] ?? 0 );
                }
            }
        }
    }
    $total_count      = count( $members );
    $no_photo         = ROYAL_BATCHMATE_PLUGIN_URL . '/assets/images/noprofile.png';
    $uid              = 'el' . $event_id; // unique prefix for IDs

    ob_start();
    ?>
<div class="BatchMate-el-wrap" id="<?php echo $uid; ?>-wrap">

    <!-- Header -->
    <div class="BatchMate-el-header">
        <h3 class="BatchMate-el-title">&#127937; <?php echo esc_html( $event->event_name ); ?></h3>
        <div class="BatchMate-el-header-right">
            <span class="BatchMate-el-count" id="<?php echo $uid; ?>-count">
                <?php echo $total_count; ?> Registered
            </span>
            <?php if ( $total_guests > 0 ) : ?>
            <span class="BatchMate-el-collection" style="background:#d97706;">
                <?php echo $total_guests; ?> Guest<?php echo $total_guests > 1 ? 's' : ''; ?>
            </span>
            <?php endif; ?>
        </div>
    </div>

    <!-- Search -->
    <div class="BatchMate-el-search-bar">
        <div class="BatchMate-el-search-wrap">
            <div class="BatchMate-el-sfield">
                <svg class="BatchMate-el-sico" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#adb5bd" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                <input type="text"
                       id="<?php echo $uid; ?>-search"
                       class="BatchMate-el-search-input"
                       placeholder="Search by name, Member ID, batch, program..."
                       autocomplete="off">
                <button class="BatchMate-el-clear-btn" id="<?php echo $uid; ?>-clear" style="display:none;" title="Clear">&#10005;</button>
            </div>
        </div>
        <span class="BatchMate-el-search-info" id="<?php echo $uid; ?>-info"></span>
    </div>

    <?php if ( empty( $members ) ) : ?>
        <div class="BatchMate-el-empty">No registered members yet.</div>
    <?php else : ?>

    <!-- Card Grid -->
    <div class="BatchMate-el-grid" id="<?php echo $uid; ?>-grid">
        <?php foreach ( $members as $i => $m ) :
            $photo       = $m->photo_url ? esc_url( $m->photo_url ) : $no_photo;
            $profile_url = home_url( '/member/' . strtolower( $m->member_number ) . '/' );
            /* searchable text � lowercase for JS matching */
            $search_data = strtolower( implode( ' ', [
                $m->name, $m->member_number,
                $m->program_name ?? '', $m->dept_name ?? '',
                $m->batch ?? '', $m->employment_position ?? ''
            ] ) );
        ?>
        <div class="BatchMate-el-card" data-search="<?php echo esc_attr( $search_data ); ?>" data-index="<?php echo $i + 1; ?>">
            <div class="BatchMate-el-card-inner">
                <?php $event_serial = ! empty( $m->serial_no ) ? sprintf( '%04d', intval( $m->serial_no ) ) : sprintf( '%04d', $i + 1 ); ?>
                <span class="BatchMate-el-sl" title="Event Serial"><?php echo esc_html( $event_serial ); ?></span>
                <img src="<?php echo $photo; ?>" alt="<?php echo esc_attr( $m->name ); ?>" class="BatchMate-el-photo" loading="lazy">
                <div class="BatchMate-el-info">
                    <a href="<?php echo esc_url( $profile_url ); ?>" class="BatchMate-el-name" target="_blank" rel="noopener">
                        <?php echo esc_html( $m->name ); ?>
                    </a>
                    <p class="BatchMate-el-meta">
                        <span class="BatchMate-el-mid" style="background:#0284c7;color:#fff;font-weight:700;" title="Event Serial">Event Sl: #<?php echo esc_html( $event_serial ); ?></span>
                        <?php if ( $m->member_number && $m->member_number !== 'Non-Member' ) : ?>
                            &bull; <span class="BatchMate-el-mid" title="Member ID">ID: <?php echo esc_html( $m->member_number ); ?></span>
                        <?php endif; ?>
                        <?php if ( ! empty( $m->batch ) ) echo ' &bull; Batch ' . esc_html( $m->batch ); ?>
                    </p>
                    <?php if ( $m->employment_position ) : ?>
                    <p class="BatchMate-el-prof"><?php echo esc_html( $m->employment_position ); ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- No results -->
    <div class="BatchMate-el-no-results" id="<?php echo $uid; ?>-noresult" style="display:none;">
        <span>&#128269;</span> No members found matching "<strong id="<?php echo $uid; ?>-noresult-q"></strong>"
    </div>

    <?php endif; ?>
</div>

<style>
/* -- Wrap -- */
.BatchMate-el-wrap{max-width:820px;margin:0 auto;font-family:'Segoe UI',system-ui,sans-serif;}

/* -- Header -- */
.BatchMate-el-header{display:flex;align-items:center;justify-content:space-between;padding:14px 0;border-bottom:2px solid #2271b1;margin-bottom:16px;flex-wrap:wrap;gap:8px;}
.BatchMate-el-title{margin:0;font-size:20px;color:#1d2327;font-weight:700;}
.BatchMate-el-header-right{display:flex;align-items:center;gap:8px;flex-wrap:wrap;}
.BatchMate-el-count{background:#2271b1;color:#fff;padding:4px 14px;border-radius:20px;font-size:13px;font-weight:600;}
.BatchMate-el-collection{background:#0a7242;color:#fff;padding:4px 14px;border-radius:20px;font-size:13px;font-weight:600;}

/* -- Search bar -- */
.BatchMate-el-search-bar{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:14px;flex-wrap:wrap;}
.BatchMate-el-search-wrap{flex:1;min-width:200px;}
.BatchMate-el-sfield{display:flex;align-items:center;border:1.5px solid #c3c4c7;border-radius:9px;background:#fff;padding:0 10px 0 12px;transition:border .2s,box-shadow .2s;}
.BatchMate-el-sfield:focus-within{border-color:#2271b1;box-shadow:0 0 0 3px rgba(34,113,177,.1);}
.BatchMate-el-sico{flex-shrink:0;display:block;opacity:.6;}
.BatchMate-el-search-input{flex:1;border:none!important;outline:none!important;box-shadow:none!important;background:transparent!important;padding:10px 6px!important;font-size:14px;font-family:inherit;min-width:0;}
.BatchMate-el-clear-btn{flex-shrink:0;background:none;border:none;font-size:14px;color:#adb5bd;cursor:pointer;padding:2px 4px;border-radius:4px;line-height:1;}
.BatchMate-el-clear-btn:hover{background:#f0f0f0;color:#555;}
.BatchMate-el-search-info{font-size:13px;color:#6c757d;white-space:nowrap;flex-shrink:0;}

/* -- Grid -- */
.BatchMate-el-grid{display:flex;flex-direction:column;gap:14px;}
.BatchMate-el-card{background:#fff;border:1px solid #dee2e6;border-radius:10px;transition:all .18s;box-shadow:0 1px 4px rgba(0,0,0,.05);}
.BatchMate-el-card:hover{box-shadow:0 4px 16px rgba(0,0,0,.1);transform:translateY(-1px);border-color:#c3c4c7;}
.BatchMate-el-card.el-hidden{display:none;}
.BatchMate-el-card-inner{display:flex;align-items:center;gap:14px;padding:12px 18px;}
.BatchMate-el-sl{min-width:26px;height:26px;background:#2271b1;color:#fff;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;flex-shrink:0;}
.BatchMate-el-photo{width:50px;height:50px;border-radius:50%;object-fit:cover;border:2px solid #2271b1;flex-shrink:0;}
.BatchMate-el-info{flex:1;min-width:0;}
.BatchMate-el-name{font-size:15px;font-weight:700;color:#1d2327;text-decoration:none;display:block;}
.BatchMate-el-name:hover{color:#2271b1;}
.BatchMate-el-meta{margin:3px 0 0;font-size:12px;color:#6c757d;}
.BatchMate-el-mid{background:#e7f0fa;color:#2271b1;padding:1px 8px;border-radius:12px;font-size:11px;font-weight:600;}
.BatchMate-el-prof{margin:3px 0 0;font-size:11px;color:#adb5bd;font-style:italic;}


/* -- Empty / No results -- */
.BatchMate-el-empty,.BatchMate-el-no-results{text-align:center;padding:36px 20px;color:#888;font-size:14px;border:1.5px dashed #dee2e6;border-radius:10px;margin-top:4px;}
.BatchMate-el-no-results span:first-child{font-size:22px;display:block;margin-bottom:8px;}
</style>

<script>
(function($){
    var uid    = '<?php echo $uid; ?>';
    var $wrap  = $('#' + uid + '-wrap');
    var $input = $('#' + uid + '-search');
    var $clear = $('#' + uid + '-clear');
    var $info  = $('#' + uid + '-info');
    var $cards = $('#' + uid + '-grid .BatchMate-el-card');
    var $noRes = $('#' + uid + '-noresult');
    var $count = $('#' + uid + '-count');
    var total  = $cards.length;
    var timer  = null;

    $input.on('input', function() {
        clearTimeout(timer);
        timer = setTimeout(doFilter, 200);
    });

    $clear.on('click', function() {
        $input.val('').trigger('input').focus();
    });

    function doFilter() {
        var q = $.trim($input.val()).toLowerCase();

        $clear.toggle(q.length > 0);

        if (!q) {
            $cards.removeClass('el-hidden');
            $noRes.hide();
            $info.text('');
            $count.text(total + ' Registered');
            return;
        }

        var matched = 0;
        $cards.each(function() {
            var haystack = $(this).data('search') || '';
            if (haystack.indexOf(q) !== -1) {
                $(this).removeClass('el-hidden');
                matched++;
            } else {
                $(this).addClass('el-hidden');
            }
        });

        $count.text(matched + ' / ' + total + ' Registered');
        $info.text(matched + ' result' + (matched !== 1 ? 's' : ''));

        if (matched === 0) {
            $('#<?php echo $uid; ?>-noresult-q').text($input.val());
            $noRes.show();
        } else {
            $noRes.hide();
        }
    }

})(jQuery);
</script>
    <?php
    return ob_get_clean();
}
add_shortcode( 'event_list', 'batchmate_event_list_shortcode' );