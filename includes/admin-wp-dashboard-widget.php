<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * =====================================================================
 * BatchMate — WordPress Dashboard Widget
 * Shows member stats + active event info. Clickable cards.
 * =====================================================================
 */

// ── Register the widget ──────────────────────────────────────────────
function BatchMate_register_dashboard_widget() {
    wp_add_dashboard_widget(
        'BatchMate_dashboard_widget',
        '🎓 ' . batchmate_org_label( 'short' ) . ' — Quick Stats',
        'BatchMate_render_dashboard_widget'
    );
}
add_action( 'wp_dashboard_setup', 'BatchMate_register_dashboard_widget' );


// ── Move widget to the TOP of the dashboard ──────────────────────────
function BatchMate_dashboard_widget_to_top() {
    global $wp_meta_boxes;
    $widget = $wp_meta_boxes['dashboard']['normal']['core']['BatchMate_dashboard_widget'] ?? null;
    if ( ! $widget ) return;
    unset( $wp_meta_boxes['dashboard']['normal']['core']['BatchMate_dashboard_widget'] );
    $wp_meta_boxes['dashboard']['normal']['high']['BatchMate_dashboard_widget'] = $widget;
}
add_action( 'wp_dashboard_setup', 'BatchMate_dashboard_widget_to_top', 99 );


// ── Render function ───────────────────────────────────────────────────
function BatchMate_render_dashboard_widget() {
    global $wpdb;

    // ── Data ──
    $member_table  = $wpdb->prefix . 'batchmate_member';
    $event_table   = $wpdb->prefix . 'batchmate_event';
    $em_table      = $wpdb->prefix . 'batchmate_event_members';
    $app_table     = $wpdb->prefix . 'batchmate_application';

    $total_members  = (int) $wpdb->get_var( "SELECT COUNT(id) FROM $member_table" );
    $male_members   = (int) $wpdb->get_var( "SELECT COUNT(id) FROM $member_table WHERE gender = 'Male'" );
    $female_members = (int) $wpdb->get_var( "SELECT COUNT(id) FROM $member_table WHERE gender = 'Female'" );
    $pending_apps   = (int) $wpdb->get_var( "SELECT COUNT(id) FROM $app_table WHERE status = 'pending'" );

    // Active event (only if date has not passed)
    $active_event = $wpdb->get_row( "SELECT id, event_name, event_date FROM $event_table WHERE is_active = 1 AND DATE(event_date) >= CURDATE() LIMIT 1" );
    $event_reg_count = 0;
    if ( $active_event ) {
        $event_reg_count = (int) $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(id) FROM $em_table WHERE event_id = %d",
            $active_event->id
        ) );
    }

    // ── URLs ──
    $members_url   = admin_url( 'admin.php?page=batchmate_manage_members' );
    $apps_url      = admin_url( 'admin.php?page=batchmate_manage_applications' );
    $events_url    = admin_url( 'admin.php?page=batchmate_manage_events' );

    // ── Inline styles ──
    ?>
    <style>
    #BatchMate_dashboard_widget .inside { padding: 0 !important; margin: 0; }
    .BatchMate-dw-wrap { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; }

    .BatchMate-dw-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 0;
        border-bottom: 1px solid #edf0f4;
    }
    .BatchMate-dw-card {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 18px 10px 14px;
        text-decoration: none;
        color: inherit;
        border-right: 1px solid #edf0f4;
        transition: background 0.18s;
        cursor: pointer;
        position: relative;
        overflow: hidden;
    }
    .BatchMate-dw-card:last-child { border-right: none; }
    .BatchMate-dw-card:hover { background: #f6f8fc; text-decoration: none; color: inherit; }
    .BatchMate-dw-card:hover .BatchMate-dw-num { transform: scale(1.08); }

    .BatchMate-dw-icon {
        font-size: 22px;
        margin-bottom: 6px;
        line-height: 1;
        opacity: 0.85;
    }
    .BatchMate-dw-num {
        font-size: 28px;
        font-weight: 800;
        line-height: 1;
        transition: transform 0.18s;
    }
    .BatchMate-dw-label {
        font-size: 10.5px;
        color: #7b8a9a;
        margin-top: 5px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        text-align: center;
        font-weight: 600;
    }
    .BatchMate-dw-card--total  .BatchMate-dw-num { color: #2271b1; }
    .BatchMate-dw-card--male   .BatchMate-dw-num { color: #0369a1; }
    .BatchMate-dw-card--female .BatchMate-dw-num { color: #be185d; }
    .BatchMate-dw-card--pending .BatchMate-dw-num { color: #b45309; }

    /* ── Active Event Banner ── */
    .BatchMate-dw-event {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 14px 18px;
        background: linear-gradient(135deg, #1a3a6b 0%, #2271b1 100%);
        text-decoration: none;
        transition: filter 0.18s;
        gap: 12px;
    }
    .BatchMate-dw-event:hover { filter: brightness(1.08); text-decoration: none; }
    .BatchMate-dw-event-left { display: flex; align-items: center; gap: 12px; }
    .BatchMate-dw-event-icon { font-size: 26px; flex-shrink: 0; }
    .BatchMate-dw-event-name {
        color: #fff;
        font-size: 13px;
        font-weight: 700;
        line-height: 1.3;
    }
    .BatchMate-dw-event-meta {
        color: rgba(255,255,255,0.75);
        font-size: 11px;
        margin-top: 2px;
    }
    .BatchMate-dw-event-right { text-align: center; flex-shrink: 0; }
    .BatchMate-dw-event-count {
        font-size: 32px;
        font-weight: 900;
        color: #fff;
        line-height: 1;
    }
    .BatchMate-dw-event-count-label {
        font-size: 10px;
        color: rgba(255,255,255,0.75);
        text-transform: uppercase;
        letter-spacing: 0.5px;
        font-weight: 600;
    }
    .BatchMate-dw-no-event {
        padding: 14px 18px;
        text-align: center;
        color: #9aa3ad;
        font-size: 12px;
        font-style: italic;
        background: #f8f9fa;
    }
    .BatchMate-dw-pending-banner {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 14px 18px;
        background: linear-gradient(135deg, #c2410c 0%, #ea580c 100%);
        text-decoration: none;
        transition: filter 0.18s;
        gap: 12px;
        border-bottom: 1px solid rgba(0,0,0,0.05);
    }
    .BatchMate-dw-pending-banner:hover { filter: brightness(1.08); text-decoration: none; }
    .BatchMate-dw-pending-icon { font-size: 26px; flex-shrink: 0; }
    .BatchMate-dw-pending-name {
        color: #fff;
        font-size: 14px;
        font-weight: 700;
        line-height: 1.3;
    }
    .BatchMate-dw-pending-meta {
        color: rgba(255,255,255,0.8);
        font-size: 11px;
        margin-top: 2px;
    }
    .BatchMate-dw-pending-right { text-align: center; flex-shrink: 0; }
    .BatchMate-dw-pending-count {
        font-size: 32px;
        font-weight: 900;
        color: #fff;
        line-height: 1;
    }
    .BatchMate-dw-pending-count-label {
        font-size: 10px;
        color: rgba(255,255,255,0.8);
        text-transform: uppercase;
        letter-spacing: 0.5px;
        font-weight: 600;
    }

    .BatchMate-dw-footer {
        display: flex;
        justify-content: flex-end;
        padding: 8px 14px 10px;
        gap: 12px;
        background: #f8f9fa;
    }
    .BatchMate-dw-footer a {
        font-size: 11px;
        color: #7b8a9a;
        text-decoration: none;
    }
    .BatchMate-dw-footer a:hover { color: #2271b1; text-decoration: underline; }
    </style>

    <div class="BatchMate-dw-wrap">

        <?php /* ── Top: 3-col member stats ── */ ?>
        <div class="BatchMate-dw-grid">

            <a href="<?php echo esc_url( $members_url ); ?>" class="BatchMate-dw-card BatchMate-dw-card--total">
                <div class="BatchMate-dw-icon">👥</div>
                <div class="BatchMate-dw-num"><?php echo number_format( $total_members ); ?></div>
                <div class="BatchMate-dw-label">Total Members</div>
            </a>

            <a href="<?php echo esc_url( $members_url ); ?>" class="BatchMate-dw-card BatchMate-dw-card--male">
                <div class="BatchMate-dw-icon">👨</div>
                <div class="BatchMate-dw-num"><?php echo number_format( $male_members ); ?></div>
                <div class="BatchMate-dw-label">Male Members</div>
            </a>

            <a href="<?php echo esc_url( $members_url ); ?>" class="BatchMate-dw-card BatchMate-dw-card--female">
                <div class="BatchMate-dw-icon">👩</div>
                <div class="BatchMate-dw-num"><?php echo number_format( $female_members ); ?></div>
                <div class="BatchMate-dw-label">Female Members</div>
            </a>

        </div>

        <?php /* ── Middle: Pending Applications ── */ ?>
        <?php if ( $pending_apps > 0 ) : ?>
            <a href="<?php echo esc_url( $apps_url ); ?>" class="BatchMate-dw-pending-banner">
                <div class="BatchMate-dw-event-left">
                    <div class="BatchMate-dw-pending-icon">⏳</div>
                    <div>
                        <div class="BatchMate-dw-pending-name">Pending Applications</div>
                        <div class="BatchMate-dw-pending-meta">Needs Review & Approval</div>
                    </div>
                </div>
                <div class="BatchMate-dw-pending-right">
                    <div class="BatchMate-dw-pending-count"><?php echo $pending_apps; ?></div>
                    <div class="BatchMate-dw-pending-count-label">WAITING</div>
                </div>
            </a>
        <?php endif; ?>

        <?php /* ── Middle: Active event ── */ ?>
        <?php if ( $active_event ) : ?>
            <a href="<?php echo esc_url( $events_url ); ?>" class="BatchMate-dw-event">
                <div class="BatchMate-dw-event-left">
                    <div class="BatchMate-dw-event-icon">🏟️</div>
                    <div>
                        <div class="BatchMate-dw-event-name"><?php echo esc_html( $active_event->event_name ); ?></div>
                        <div class="BatchMate-dw-event-meta">
                            📅 <?php echo esc_html( date_i18n( 'j M Y', strtotime( $active_event->event_date ) ) ); ?>
                            &nbsp;·&nbsp; Live Event
                        </div>
                    </div>
                </div>
                <div class="BatchMate-dw-event-right">
                    <div class="BatchMate-dw-event-count"><?php echo $event_reg_count; ?></div>
                    <div class="BatchMate-dw-event-count-label">Registered</div>
                </div>
            </a>
        <?php elseif ( $pending_apps == 0 ) : ?>
            <div class="BatchMate-dw-no-event">No active event at the moment.</div>
        <?php endif; ?>

        <?php /* ── Footer links ── */ ?>
        <div class="BatchMate-dw-footer">
            <a href="<?php echo esc_url( $events_url ); ?>">Events →</a>
            <a href="<?php echo esc_url( $members_url ); ?>">Members →</a>
        </div>

    </div>
    <?php
}
