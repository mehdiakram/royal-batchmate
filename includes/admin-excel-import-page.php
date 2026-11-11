<?php
if ( ! defined( 'ABSPATH' ) ) exit;

global $wpdb;

$events     = $wpdb->get_results( "SELECT id, event_name, event_date FROM {$wpdb->prefix}batchmate_event ORDER BY event_date DESC" );
$has_events = ! empty( $events );
$events_url = esc_url( admin_url( 'admin.php?page=batchmate_manage_events' ) );
?>

<div class="wrap">
    <div class="BatchMate-page-header-animated">
        <div class="BatchMate-header-left">
            <h1><span class="dashicons dashicons-media-spreadsheet"></span> <?php esc_html_e( 'Excel Import — Event Registrations', 'royal-batchmate' ); ?></h1>
            <p><?php esc_html_e( 'Import event registration data from a Google Form Excel export.', 'royal-batchmate' ); ?></p>
        </div>
    </div>

    <div class="card" style="max-width:900px; padding:24px; margin-top:20px;">
        <h2 style="border-bottom:1px solid #eee; padding-bottom:10px; margin-bottom:20px; display:flex; justify-content:space-between; align-items:center;">
            <span><?php esc_html_e( 'Import', 'royal-batchmate' ); ?></span>
            <a href="<?php echo $events_url; ?>" class="button button-secondary" style="font-weight:normal; font-size:13px;">
                <span class="dashicons dashicons-plus-alt2" style="vertical-align:middle;"></span>
                <?php esc_html_e( 'Create New Event', 'royal-batchmate' ); ?>
            </a>
        </h2>

        <?php if ( ! $has_events ) : ?>
            <div class="notice notice-info inline" style="margin:0 0 16px;">
                <p>
                    <?php esc_html_e( 'No events found yet.', 'royal-batchmate' ); ?>
                    <strong><?php esc_html_e( 'You can still import without selecting an event', 'royal-batchmate' ); ?></strong>
                    — <?php esc_html_e( 'data will go into the rawdata table only. You can link to an event later.', 'royal-batchmate' ); ?>
                    &nbsp;
                    <a href="<?php echo $events_url; ?>" class="button button-small">
                        <?php esc_html_e( 'Create Event Now', 'royal-batchmate' ); ?> &rarr;
                    </a>
                </p>
            </div>
        <?php endif; ?>

        <form id="excel-import-form" enctype="multipart/form-data">
            <?php wp_nonce_field( 'batchmate_excel_import_nonce', '_excel_import_nonce' ); ?>

            <table class="form-table" style="max-width:700px;">
                <tr>
                    <th><label for="import_event_id"><?php esc_html_e( 'Event (optional)', 'royal-batchmate' ); ?></label></th>
                    <td>
                        <select name="event_id" id="import_event_id" class="regular-text" style="width:100%;">
                            <option value="0"><?php esc_html_e( '— Raw Data only (no event) —', 'royal-batchmate' ); ?></option>
                            <?php foreach ( $events as $ev ) : ?>
                                <option value="<?php echo esc_attr( $ev->id ); ?>">
                                    <?php echo esc_html( $ev->event_name . ' (' . $ev->event_date . ')' ); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <p class="description" id="event-select-hint" style="margin-top:4px;"></p>
                    </td>
                </tr>
                <tr>
                    <th><label for="import_xlsx_file"><?php esc_html_e( 'Excel File (.xlsx)', 'royal-batchmate' ); ?> *</label></th>
                    <td>
                        <input type="file" name="xlsx_file" id="import_xlsx_file" accept=".xlsx" required
                               style="padding:8px; border:1px solid #ccc; width:100%; background:#fff;">
                        <p class="description"><?php esc_html_e( 'Upload the .xlsx file exported from Google Forms.', 'royal-batchmate' ); ?></p>
                    </td>
                </tr>
                <tr>
                    <th><label><?php esc_html_e( 'Options', 'royal-batchmate' ); ?></label></th>
                    <td>
                        <label>
                            <input type="checkbox" name="import_as_members" value="1" checked>
                            <strong>Import directly as Approved Members</strong> (creates users)
                        </label><br><br>
                        <label>
                            <input type="checkbox" name="skip_duplicates" value="1" checked>
                            <?php esc_html_e( 'Skip duplicate mobile / student ID', 'royal-batchmate' ); ?>
                        </label><br><br>
                        <label>
                            <input type="checkbox" name="dry_run" value="1" id="chk_dry_run">
                            <strong><?php esc_html_e( 'Dry Run', 'royal-batchmate' ); ?></strong>
                            — <?php esc_html_e( 'Preview only, no data will be saved.', 'royal-batchmate' ); ?>
                        </label>
                    </td>
                </tr>
            </table>

            <style>
                #btn-excel-import {
                    position: relative;
                    display: inline-flex;
                    align-items: center;
                    gap: 10px;
                    padding: 14px 32px;
                    font-size: 15px;
                    font-weight: 700;
                    letter-spacing: 0.4px;
                    color: #fff !important;
                    background: linear-gradient(135deg, #6366f1 0%, #4f46e5 50%, #4338ca 100%);
                    border: none !important;
                    border-radius: 12px !important;
                    box-shadow: 0 4px 15px rgba(99, 102, 241, 0.45), 0 1px 3px rgba(0,0,0,0.12);
                    cursor: pointer;
                    transition: all 0.25s ease;
                    overflow: hidden;
                    height: auto !important;
                    line-height: 1.4 !important;
                    text-shadow: 0 1px 2px rgba(0,0,0,0.2);
                }
                #btn-excel-import::before {
                    content: '';
                    position: absolute;
                    top: 0; left: -75%;
                    width: 50%; height: 100%;
                    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.22), transparent);
                    transform: skewX(-20deg);
                    animation: btn-shimmer 2.8s infinite;
                }
                @keyframes btn-shimmer {
                    0%   { left: -75%; }
                    60%  { left: 130%; }
                    100% { left: 130%; }
                }
                #btn-excel-import .dashicons {
                    font-size: 20px;
                    width: 20px;
                    height: 20px;
                    transition: transform 0.3s ease;
                }
                #btn-excel-import:hover {
                    background: linear-gradient(135deg, #818cf8 0%, #6366f1 50%, #4f46e5 100%);
                    box-shadow: 0 6px 22px rgba(99, 102, 241, 0.55), 0 2px 6px rgba(0,0,0,0.15);
                    transform: translateY(-2px);
                    color: #fff !important;
                }
                #btn-excel-import:hover .dashicons {
                    transform: rotate(-8deg) scale(1.15);
                }
                #btn-excel-import:active {
                    transform: translateY(0px);
                    box-shadow: 0 2px 8px rgba(99, 102, 241, 0.4);
                }
                #btn-excel-import:disabled {
                    opacity: 0.65;
                    cursor: not-allowed;
                    transform: none;
                }
                #import-spinner {
                    display: inline-flex;
                    align-items: center;
                    gap: 8px;
                    font-size: 14px;
                    color: #6366f1;
                    font-weight: 600;
                    vertical-align: middle;
                    margin-left: 14px;
                    animation: fadeIn 0.3s ease;
                }
                @keyframes fadeIn { from { opacity: 0; transform: translateX(-6px); } to { opacity: 1; transform: translateX(0); } }
                #import-spinner .spinner { float: none; margin: 0; }
            </style>

            <div style="margin-top:24px; display:flex; align-items:center; flex-wrap:wrap; gap:12px;">
                <button type="submit" id="btn-excel-import">
                    <span class="dashicons dashicons-database-import"></span>
                    <?php esc_html_e( 'Start Import', 'royal-batchmate' ); ?>
                </button>
                <span id="import-spinner" style="display:none;">
                    <span class="spinner is-active"></span>
                    <?php esc_html_e( 'Processing…', 'royal-batchmate' ); ?>
                </span>
            </div>
        </form>
    </div>

    <!-- Result Panel -->
    <div id="import-result-panel" style="display:none; margin-top:20px;">
        <div class="card" style="max-width:900px; padding:24px;">
            <h2 style="border-bottom:1px solid #eee; padding-bottom:10px; margin-bottom:15px;">
                <?php esc_html_e( 'Import Result', 'royal-batchmate' ); ?>
            </h2>
            <div id="import-summary" style="margin-bottom:15px; font-size:14px; line-height:2;"></div>
            <div id="import-log" style="
                background:#1e1e1e; color:#d4d4d4;
                padding:15px; max-height:450px; overflow-y:auto;
                font-family:monospace; font-size:12px; border-radius:4px;
                white-space:pre-wrap; line-height:1.6;
            "></div>
        </div>
    </div>
</div>

<!-- Column Mapping Legend -->
<div class="card" style="max-width:900px; padding:20px; margin-top:10px; background:#f9f9f9;">
    <h3 style="margin-top:0;"><?php esc_html_e( 'Excel Column Mapping', 'royal-batchmate' ); ?></h3>
    <table class="widefat striped" style="width:auto; font-size:13px;">
        <thead>
            <tr>
                <th><?php esc_html_e( 'Col', 'royal-batchmate' ); ?></th>
                <th><?php esc_html_e( 'Excel Field', 'royal-batchmate' ); ?></th>
                <th><?php esc_html_e( 'Database', 'royal-batchmate' ); ?></th>
            </tr>
        </thead>
        <tbody>
            <tr><td>A</td><td>Member ID</td><td>rawdata.student_id</td></tr>
            <tr><td>B</td><td>Name (English)</td><td>rawdata.name</td></tr>
            <tr><td>C</td><td>Name (Bangla)</td><td>rawdata.name_bn</td></tr>
            <tr><td>D</td><td>School / Institution</td><td>rawdata.institution</td></tr>
            <tr><td>E</td><td>District</td><td>rawdata.permanent_district</td></tr>
            <tr><td>F</td><td>Contact</td><td>rawdata.cell_number</td></tr>
            <tr><td>G</td><td>Contact 2 (Alternative Number)</td><td>rawdata.land_phone_number</td></tr>
            <tr><td>H</td><td>Blood Group</td><td>rawdata.blood_group</td></tr>
            <tr><td>I</td><td>Email</td><td>rawdata.email</td></tr>
            <tr><td>J</td><td>Facebook Link</td><td>rawdata.facebook_url</td></tr>
            <tr><td>K</td><td>T-shirt Size</td><td>event_application.tshirt_size</td></tr>
            <tr><td>L</td><td>Fee (Options)</td><td>event_application.options_json</td></tr>
            <tr><td>M</td><td>Payment Details</td><td>event_application.payment_details</td></tr>
            <tr><td>N</td><td>Paid Amount</td><td>event_application.amount</td></tr>
            <tr><td>O</td><td>Photo</td><td>rawdata.photo_url</td></tr>
            <tr><td>P</td><td>Date of Birth</td><td>rawdata.date_of_birth</td></tr>
            <tr><td>Q</td><td>Gender</td><td>rawdata.gender</td></tr>
            <tr><td>R</td><td>Profession</td><td>rawdata.employment_position</td></tr>
        </tbody>
    </table>
</div>

<script>
jQuery(document).ready(function($) {

    var i18n = {
        hintNoEvent  : '<?php echo esc_js( __( 'No event selected — data will be saved to rawdata only. event_application will not be created.', 'royal-batchmate' ) ); ?>',
        hintWithEvent: '<?php echo esc_js( __( 'Data will be saved to rawdata and event_application (and Approved Members if checked).', 'royal-batchmate' ) ); ?>',
        confirmNoEvent: '<?php echo esc_js( __( 'No event selected. Import to rawdata only?', 'royal-batchmate' ) ); ?>',
        complete     : '<?php echo esc_js( __( 'Import Complete!', 'royal-batchmate' ) ); ?>',
        dryRun       : '<?php echo esc_js( __( 'DRY RUN — No data was saved', 'royal-batchmate' ) ); ?>',
        rawOnly      : '<?php echo esc_js( __( '(rawdata only)', 'royal-batchmate' ) ); ?>',
        withEvent    : '<?php echo esc_js( __( '+ Event Application', 'royal-batchmate' ) ); ?>',
        totalRows    : '<?php echo esc_js( __( 'Total rows', 'royal-batchmate' ) ); ?>',
        success      : '<?php echo esc_js( __( 'Success', 'royal-batchmate' ) ); ?>',
        skipped      : '<?php echo esc_js( __( 'Skipped', 'royal-batchmate' ) ); ?>',
        errors       : '<?php echo esc_js( __( 'Errors', 'royal-batchmate' ) ); ?>',
        serverError  : '<?php echo esc_js( __( 'Server error. Please try again.', 'royal-batchmate' ) ); ?>',
    };

    // ── Event selector hint ───────────────────────────────────────────
    $('#import_event_id').on('change', function() {
        var hint = $('#event-select-hint');
        if ($(this).val() === '0') {
            hint.html('<span style="color:#b45309;">⚠ ' + i18n.hintNoEvent + '</span>');
        } else {
            hint.html('<span style="color:#15803d;">✓ ' + i18n.hintWithEvent + '</span>');
        }
    }).trigger('change');

    // ── Form submit ───────────────────────────────────────────────────
    $('#excel-import-form').on('submit', function(e) {
        e.preventDefault();

        if ($('#import_event_id').val() === '0' && !$('#chk_dry_run').is(':checked')) {
            if (!window.confirm(i18n.confirmNoEvent)) return;
        }

        var formData = new FormData(this);
        formData.append('action', 'batchmate_excel_import');

        $('#btn-excel-import').prop('disabled', true);
        $('#import-spinner').show();
        $('#import-result-panel').hide();

        $.ajax({
            url: batchmate_admin_ajax.ajax_url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(res) {
                $('#btn-excel-import').prop('disabled', false);
                $('#import-spinner').hide();
                $('#import-result-panel').show();

                if (res.success) {
                    var d = res.data;
                    var dryTag  = d.dry_run  ? ' &nbsp;<span style="background:#fef3c7;color:#92400e;padding:2px 8px;border-radius:4px;font-size:12px;">' + i18n.dryRun  + '</span>' : '';
                    var evTag   = (d.event_id > 0) ? i18n.withEvent : i18n.rawOnly;
                    $('#import-summary').html(
                        '<strong>✅ ' + i18n.complete + dryTag + '</strong> ' + evTag + '<br>' +
                        i18n.totalRows + ': <b>' + d.total   + '</b> &nbsp;|&nbsp; ' +
                        i18n.success   + ': <b style="color:green;">'  + d.success + '</b> &nbsp;|&nbsp; ' +
                        i18n.skipped   + ': <b style="color:orange;">' + d.skipped + '</b> &nbsp;|&nbsp; ' +
                        i18n.errors    + ': <b style="color:red;">'    + d.errors  + '</b>'
                    );
                    $('#import-log').html(d.log.join('\n'));
                } else {
                    $('#import-summary').html('<span style="color:red;">❌ ' + res.data + '</span>');
                    $('#import-log').html('');
                }
                $('html, body').animate({ scrollTop: $('#import-result-panel').offset().top - 50 }, 400);
            },
            error: function() {
                $('#btn-excel-import').prop('disabled', false);
                $('#import-spinner').hide();
                Swal.fire('Error', i18n.serverError, 'error');
            }
        });
    });
});
</script>
