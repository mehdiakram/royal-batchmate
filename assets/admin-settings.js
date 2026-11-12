/**
 * Royal BatchMate - Admin Settings Scripts
 */
jQuery(document).ready(function($) {
    const ajax_url      = batchmate_admin_ajax.ajax_url;
    const settings_nonce = batchmate_admin_ajax.settings_nonce;
    const template_dir = (window.batchmate_settings_cfg && window.batchmate_settings_cfg.templateDir) ? window.batchmate_settings_cfg.templateDir : '';
    const defaultBannerUrl = (window.batchmate_settings_cfg && window.batchmate_settings_cfg.defaultBannerUrl) ? window.batchmate_settings_cfg.defaultBannerUrl : '';

    /* ══════════════════════════════
       Tab Switching — instant
    ══════════════════════════════ */
    function switchTab(target) {
        $('.BatchMate-tab-btn').removeClass('BatchMate-tab-active');
        $('.BatchMate-tab-pane').removeClass('BatchMate-tab-pane-active');
        $(`.BatchMate-tab-btn[data-tab="${target}"]`).addClass('BatchMate-tab-active');
        $(`#tab-${target}`).addClass('BatchMate-tab-pane-active');
        $('#current_tab').val(target);
        history.replaceState(null, null, '#' + target); localStorage.setItem('batchmate_active_tab', target);
    }
    $('.BatchMate-tab-btn').on('click', function() {
        switchTab($(this).data('tab'));
    });
    const urlHash = window.location.hash.replace('#', '') || localStorage.getItem('batchmate_active_tab');
    switchTab(urlHash && ['organization','breadcrumb','registration','labels','automation','license','about'].includes(urlHash) ? urlHash : 'organization');

    /* ── Instant Field Search Filter in Registration Tab ── */
    $('#bm-field-search').on('input', function() {
        var val = $.trim($(this).val()).toLowerCase();
        if (!val) {
            $('.bm-field-row, .bm-field-group-header').show();
            return;
        }
        $('.bm-field-row').each(function() {
            var name = String($(this).data('field-name') || '').toLowerCase();
            var match = name.indexOf(val) !== -1;
            $(this).toggle(match);
        });
        $('.bm-field-group-header').each(function() {
            var $groupRows = $(this).nextUntil('.bm-field-group-header', '.bm-field-row');
            var anyVisible = $groupRows.filter(':visible').length > 0;
            $(this).toggle(anyVisible);
        });
    });

    // Bidirectional sync for dedicated label inputs and table label inputs
    var bmLabelMap = {
        '#label_institution':        'input[name="fields[reg_institution][label]"]',
        '#label_present_district':   'input[name="fields[reg_present_district][label]"]',
        '#label_present_upazila':    'input[name="fields[reg_present_upazila][label]"]',
        '#label_permanent_district': 'input[name="fields[reg_permanent_district][label]"]',
        '#label_permanent_upazila':  'input[name="fields[reg_permanent_upazila][label]"]',
        '#label_roll_no':            'input[name="fields[reg_roll_no][label]"]'
    };
    $.each(bmLabelMap, function(topSel, tblSel) {
        $(document).on('input', topSel, function() {
            $(tblSel).val($(this).val());
        });
        $(document).on('input', tblSel, function() {
            $(topSel).val($(this).val());
        });
    });

    /* ══════════════════════════════
       Automation Master & Sub Toggles
    ══════════════════════════════ */
    $('#automation_active').on('change', function() {
        if ($(this).is(':checked')) $('#automation-inner-settings').slideDown(200);
        else $('#automation-inner-settings').slideUp(200);
    });

    $('#sms_enabled').on('change', function() {
        if ($(this).is(':checked')) $('#sms-gateway-fields').slideDown(200);
        else $('#sms-gateway-fields').slideUp(200);
    });

    $('#buffer_enabled').on('change', function() {
        if ($(this).is(':checked')) $('#buffer-connections-fields').slideDown(200);
        else $('#buffer-connections-fields').slideUp(200);
    });

    $('#birthday_active').on('change', function() {
        if ($(this).is(':checked')) $('#birthday-inner-settings').slideDown(200);
        else $('#birthday-inner-settings').slideUp(200);
    });

    $('#email_active').on('change', function() {
        if ($(this).is(':checked')) $('#birthday-email-fields').slideDown(200);
        else $('#birthday-email-fields').slideUp(200);
    });

    $('#birthday_sms_active').on('change', function() {
        if ($(this).is(':checked')) $('#birthday-sms-fields').slideDown(200);
        else $('#birthday-sms-fields').slideUp(200);
    });

    $('#event_automation_active').on('change', function() {
        if ($(this).is(':checked')) $('#event-inner-settings').slideDown(200);
        else $('#event-inner-settings').slideUp(200);
    });

    $('#event_sms_active').on('change', function() {
        if ($(this).is(':checked')) $('#event-sms-fields').slideDown(200);
        else $('#event-sms-fields').slideUp(200);
    });

    $('#member_reg_automation_active').on('change', function() {
        if ($(this).is(':checked')) $('#member-reg-inner-settings').slideDown(200);
        else $('#member-reg-inner-settings').slideUp(200);
    });

    $('#member_reg_sms_active').on('change', function() {
        if ($(this).is(':checked')) $('#member-reg-sms-fields').slideDown(200);
        else $('#member-reg-sms-fields').slideUp(200);
    });

    /* ══════════════════════════════
       160-Character SMS Counter
    ══════════════════════════════ */
    function updateSmsCounter($textarea) {
        const text = $textarea.val() || '';
        const len = text.length;
        const isUnicode = /[^\u0000-\u007f]/.test(text);
        const limitPerSms = isUnicode ? 70 : 160;
        const concatLimit = isUnicode ? 67 : 153;
        
        let smsCount = 1;
        if (len > limitPerSms) {
            smsCount = Math.ceil(len / concatLimit);
        } else if (len === 0) {
            smsCount = 0;
        }

        const $counter = $textarea.siblings('.batchmate-char-counter');
        if ($counter.length) {
            const label = len + ' / ' + limitPerSms + ' characters (' + (smsCount || 1) + ' SMS)' + (isUnicode ? ' [Unicode]' : '');
            $counter.find('.count-info').text(label);
            
            $counter.removeClass('warning danger');
            if (len > limitPerSms) {
                $counter.addClass('warning');
            }
        }
    }

    $(document).on('input keyup', '.batchmate-sms-textarea', function() {
        updateSmsCounter($(this));
    });
    $('.batchmate-sms-textarea').each(function() {
        updateSmsCounter($(this));
    });

    /* ══════════════════════════════
       Template Tag Inserter
    ══════════════════════════════ */
    $(document).on('click', '.batchmate-tag-chip', function() {
        const tag = $(this).text();
        const targetId = $(this).data('target');
        const $target = $('#' + targetId);
        if ($target.length) {
            const input = $target[0];
            const start = input.selectionStart || input.value.length;
            const end = input.selectionEnd || input.value.length;
            const val = input.value;
            input.value = val.substring(0, start) + tag + val.substring(end);
            input.selectionStart = input.selectionEnd = start + tag.length;
            $target.trigger('input').focus();
        }
    });

    /* ══════════════════════════════
       Check SMS Balance
    ══════════════════════════════ */
    $('#check-sms-balance-btn').on('click', function(e) {
        e.preventDefault();
        const $btn = $(this);
        const $display = $('#sms-balance-display');
        const apiKey = $('#sms_api_key').val().trim();

        if (!apiKey) {
            Swal.fire('API Key Required', 'Please enter your Alpha SMS API Key first.', 'warning');
            return;
        }

        $btn.prop('disabled', true).html('<span class="dashicons dashicons-update BatchMate-spin"></span> Checking...');
        $display.text('');

        $.ajax({
            url: ajax_url,
            type: 'POST',
            data: {
                action: 'batchmate_check_sms_balance',
                _wpnonce: settings_nonce,
                api_key: apiKey
            },
            success: function(res) {
                if (res.success) {
                    $display.text(res.data.balance + ' BDT').css('color', '#16a34a');
                    Swal.fire({
                        title: 'SMS Balance',
                        html: '<strong>' + res.data.balance + ' BDT</strong> remaining in your account.',
                        icon: 'success'
                    });
                } else {
                    $display.text('Failed').css('color', '#ef4444');
                    Swal.fire('Error', res.data || 'Failed to fetch balance.', 'error');
                }
            },
            error: function() {
                $display.text('Network Error').css('color', '#ef4444');
                Swal.fire('Error', 'Network or connection error.', 'error');
            },
            complete: function() {
                $btn.prop('disabled', false).html('<span class="dashicons dashicons-money-alt" style="vertical-align: middle;"></span> Check Balance');
            }
        });
    });

    /* ══════════════════════════════
       Send Live Test SMS
    ══════════════════════════════ */
    $('#send-test-sms-btn').on('click', function(e) {
        e.preventDefault();
        const phone = $('#test-sms-phone').val().trim();
        const apiKey = $('#sms_api_key').val().trim();
        const senderId = $('#sms_sender_id').val().trim();
        const $res = $('#test-sms-result');

        if (!phone) {
            Swal.fire('Phone Required', 'Please enter a recipient phone number (e.g. 017XXXXXXXX).', 'warning');
            return;
        }
        if (!apiKey) {
            Swal.fire('API Key Required', 'Please enter your Alpha SMS API Key first.', 'warning');
            return;
        }

        const $btn = $(this);
        $btn.prop('disabled', true).html('<span class="dashicons dashicons-update BatchMate-spin"></span> Sending...');
        $res.text('').css('color', '');

        $.ajax({
            url: ajax_url,
            type: 'POST',
            data: {
                action: 'batchmate_test_sms',
                _wpnonce: settings_nonce,
                phone: phone,
                api_key: apiKey,
                sender_id: senderId
            },
            success: function(response) {
                if (response.success) {
                    $res.text('Sent successfully!').css('color', '#16a34a');
                    Swal.fire('Success!', response.data, 'success');
                } else {
                    $res.text('Failed: ' + response.data).css('color', '#ef4444');
                    Swal.fire('SMS Failed', response.data, 'error');
                }
            },
            error: function() {
                $res.text('Failed to send.').css('color', '#ef4444');
                Swal.fire('Error', 'Connection failed.', 'error');
            },
            complete: function() {
                $btn.prop('disabled', false).html('<span class="dashicons dashicons-email-alt" style="vertical-align: middle;"></span> Send Test SMS');
            }
        });
    });

    /* ══════════════════════════════
       Multi-Webhook Rows: Add / Remove
    ══════════════════════════════ */
    let webhookNextIndex = $('#batchmate-webhook-rows tr').length;

    $('#batchmate-add-webhook-btn').on('click', function() {
        const idx = webhookNextIndex++;
        const rowHtml = `
            <tr class="batchmate-webhook-row" data-index="${idx}">
                <td style="text-align: center;">
                    <label class="BatchMate-toggle-switch BatchMate-toggle-sm">
                        <input type="checkbox" name="webhook_urls[${idx}][status]" value="on" checked>
                        <span class="BatchMate-toggle-slider"></span>
                    </label>
                </td>
                <td>
                    <input type="text" name="webhook_urls[${idx}][name]" value="" placeholder="e.g. Discord / Zapier" style="width:100%;">
                </td>
                <td>
                    <input type="url" name="webhook_urls[${idx}][url]" value="" placeholder="https://..." style="width:100%;">
                </td>
                <td style="text-align: center;">
                    <button type="button" class="button button-link-delete batchmate-remove-webhook-row" title="Remove Webhook">
                        <span class="dashicons dashicons-trash"></span>
                    </button>
                </td>
            </tr>
        `;
        $('#batchmate-webhook-rows').append(rowHtml);
    });

    $(document).on('click', '.batchmate-remove-webhook-row', function() {
        if ($('#batchmate-webhook-rows tr').length > 1) {
            $(this).closest('tr').remove();
        } else {
            $(this).closest('tr').find('input[type="url"]').val('');
            $(this).closest('tr').find('input[type="text"]').val('');
        }
    });

    /* ══════════════════════════════
       Ping Test Webhooks
    ══════════════════════════════ */
    $('#batchmate-test-webhooks-btn').on('click', function(e) {
        e.preventDefault();
        const $btn = $(this);
        const $res = $('#batchmate-webhook-test-result');

        // Collect webhook URLs currently active in the rows
        const webhookUrls = [];
        $('#batchmate-webhook-rows tr.batchmate-webhook-row').each(function() {
            const $row = $(this);
            const isChecked = $row.find('input[type="checkbox"]').is(':checked');
            const urlVal = ($row.find('input[type="url"]').val() || '').trim();
            if (isChecked && urlVal) {
                webhookUrls.push({ url: urlVal });
            }
        });

        $btn.prop('disabled', true).html('<span class="dashicons dashicons-update BatchMate-spin"></span> Testing...');
        $res.html('');

        $.ajax({
            url: ajax_url,
            type: 'POST',
            data: {
                action: 'batchmate_test_webhooks',
                _wpnonce: settings_nonce,
                webhook_urls: webhookUrls
            },
            success: function(response) {
                if (response.success) {
                    Swal.fire({
                        title: 'Webhook Ping Results',
                        html: '<div style="text-align:left; font-size:13px; max-height:250px; overflow-y:auto;">' + response.data.html + '</div>',
                        icon: 'info'
                    });
                } else {
                    Swal.fire('Webhook Ping', response.data || 'Failed to ping webhooks.', 'warning');
                }
            },
            error: function() {
                Swal.fire('Error', 'Connection failed while testing webhooks.', 'error');
            },
            complete: function() {
                $btn.prop('disabled', false).html('<span class="dashicons dashicons-rest-api"></span> Test Ping Webhooks');
            }
        });
    });

    /* ══════════════════════════════
       Buffer Direct Connections
    ══════════════════════════════ */
    let bufferNextIndex = $('#batchmate-buffer-rows tr').length;

    function createBufferRowHtml(idx, name, channelId, mode, service) {
        const safeName = $('<div>').text(name || '').html();
        const safeId   = $('<div>').text(channelId || '').html();
        const selMode  = mode || 'shareNow';
        const svc      = (service || 'auto').toLowerCase();
        const svcOpts  = [
            ['auto', 'Auto-detect'],
            ['facebook', 'Facebook'],
            ['instagram', 'Instagram'],
            ['linkedin', 'LinkedIn'],
            ['twitter', 'X / Twitter'],
            ['threads', 'Threads'],
            ['bluesky', 'Bluesky'],
            ['other', 'Other']
        ].map(function(pair) {
            return `<option value="${pair[0]}" ${svc === pair[0] ? 'selected' : ''}>${pair[1]}</option>`;
        }).join('');

        return `
            <tr class="batchmate-buffer-row" data-index="${idx}">
                <td style="text-align: center;">
                    <label class="BatchMate-toggle-switch BatchMate-toggle-sm">
                        <input type="checkbox" name="buffer_channels[${idx}][status]" value="on" checked>
                        <span class="BatchMate-toggle-slider"></span>
                    </label>
                </td>
                <td>
                    <input type="text" name="buffer_channels[${idx}][name]" value="${safeName}" placeholder="e.g. Facebook Page" style="width:100%;">
                </td>
                <td>
                    <select name="buffer_channels[${idx}][service]" style="width:100%;">
                        ${svcOpts}
                    </select>
                </td>
                <td>
                    <input type="text" name="buffer_channels[${idx}][channel_id]" value="${safeId}" placeholder="e.g. 67a1b2c3d4e5f6..." style="width:100%; font-family:monospace;">
                </td>
                <td>
                    <select name="buffer_channels[${idx}][mode]" style="width:100%;">
                        <option value="shareNow" ${selMode === 'shareNow' ? 'selected' : ''}>Publish Now (shareNow)</option>
                        <option value="addToQueue" ${selMode === 'addToQueue' ? 'selected' : ''}>Add to Queue (addToQueue)</option>
                        <option value="shareNext" ${selMode === 'shareNext' ? 'selected' : ''}>Share Next (shareNext)</option>
                    </select>
                </td>
                <td style="text-align: center;">
                    <button type="button" class="button button-link-delete batchmate-remove-buffer-row" title="Remove Channel">
                        <span class="dashicons dashicons-trash"></span>
                    </button>
                </td>
            </tr>
        `;
    }

    function addOrFillBufferChannel(name, channelId, service) {
        let alreadyExists = false;
        $('#batchmate-buffer-rows tr.batchmate-buffer-row').each(function() {
            const existingId = ($(this).find('input[name$="[channel_id]"]').val() || '').trim();
            if (existingId && existingId === channelId) {
                alreadyExists = true;
            }
        });
        if (alreadyExists) {
            Swal.fire('Already Added', 'This Buffer Channel ID is already in the table.', 'info');
            return;
        }

        const normSvc = (service || 'auto').toLowerCase();
        const allowedSvcs = ['auto', 'facebook', 'instagram', 'linkedin', 'twitter', 'threads', 'bluesky', 'other'];
        const finalSvc = allowedSvcs.includes(normSvc) ? normSvc : 'other';

        // Reuse first empty row if available
        let filledEmpty = false;
        $('#batchmate-buffer-rows tr.batchmate-buffer-row').each(function() {
            const $row = $(this);
            const cid  = ($row.find('input[name$="[channel_id]"]').val() || '').trim();
            if (!cid && !filledEmpty) {
                $row.find('input[name$="[name]"]').val(name || '');
                $row.find('select[name$="[service]"]').val(finalSvc);
                $row.find('input[name$="[channel_id]"]').val(channelId || '');
                $row.find('input[type="checkbox"]').prop('checked', true);
                filledEmpty = true;
            }
        });

        if (!filledEmpty) {
            $('#batchmate-buffer-rows').append(createBufferRowHtml(bufferNextIndex++, name, channelId, 'shareNow', finalSvc));
        }
    }

    $('#batchmate-toggle-buffer-key').on('click', function() {
        const $inp = $('#buffer_api_key');
        const isPass = $inp.attr('type') === 'password';
        $inp.attr('type', isPass ? 'text' : 'password');
        $(this).find('.dashicons')
            .toggleClass('dashicons-visibility', !isPass)
            .toggleClass('dashicons-hidden', isPass);
    });

    $('#batchmate-add-buffer-channel-btn').on('click', function() {
        $('#batchmate-buffer-rows').append(createBufferRowHtml(bufferNextIndex++, '', '', 'shareNow', 'auto'));
    });

    $(document).on('click', '.batchmate-remove-buffer-row', function() {
        if ($('#batchmate-buffer-rows tr').length > 1) {
            $(this).closest('tr').remove();
        } else {
            $(this).closest('tr').find('input[type="text"]').val('');
            $(this).closest('tr').find('select[name$="[service]"]').val('auto');
        }
    });

    $('#batchmate-fetch-buffer-channels-btn').on('click', function(e) {
        e.preventDefault();
        const $btn  = $(this);
        const $box  = $('#batchmate-buffer-fetched-channels');
        const apiKey = ($('#buffer_api_key').val() || '').trim();

        if (!apiKey) {
            Swal.fire('API Key Required', 'Please enter your Buffer API Key first.', 'warning');
            return;
        }

        $btn.prop('disabled', true).html('<span class="dashicons dashicons-update BatchMate-spin" style="vertical-align: middle;"></span> Fetching...');
        $box.hide().empty();

        $.ajax({
            url: ajax_url,
            type: 'POST',
            data: {
                action: 'batchmate_fetch_buffer_channels',
                _wpnonce: settings_nonce,
                api_key: apiKey
            },
            success: function(response) {
                if (response.success && response.data && Array.isArray(response.data.channels)) {
                    let html = '<div style="font-size:12px; font-weight:600; color:#1e293b; margin-bottom:8px;">Connected Buffer Channels (click to add):</div><div style="display:flex; flex-wrap:wrap; gap:8px;">';
                    response.data.channels.forEach(function(ch) {
                        const label = (ch.name || ch.service || 'Channel') + (ch.service ? ' (' + ch.service + ')' : '');
                        const safeLabel   = $('<div>').text(label).html();
                        const safeId      = $('<div>').text(ch.id).html();
                        const safeService = $('<div>').text(ch.service || 'auto').html();
                        html += `<button type="button" class="button button-small batchmate-pick-buffer-channel" data-name="${safeLabel}" data-id="${safeId}" data-service="${safeService}" style="display:inline-flex; align-items:center; gap:4px;">+ ${safeLabel}</button>`;
                    });
                    html += '</div>';
                    $box.html(html).slideDown(150);
                } else {
                    Swal.fire('Buffer Fetch Failed', response.data || 'Could not fetch channels.', 'error');
                }
            },
            error: function() {
                Swal.fire('Error', 'Connection failed while contacting Buffer API.', 'error');
            },
            complete: function() {
                $btn.prop('disabled', false).html('<span class="dashicons dashicons-download" style="vertical-align: middle;"></span> Fetch Channels');
            }
        });
    });

    $(document).on('click', '.batchmate-pick-buffer-channel', function() {
        addOrFillBufferChannel($(this).data('name'), $(this).data('id'), $(this).data('service'));
    });

    $('#batchmate-test-buffer-btn').on('click', function(e) {
        e.preventDefault();
        const $btn   = $(this);
        const apiKey = ($('#buffer_api_key').val() || '').trim();
        const channels = [];

        $('#batchmate-buffer-rows tr.batchmate-buffer-row').each(function() {
            const $row = $(this);
            const isChecked = $row.find('input[type="checkbox"]').is(':checked');
            const cid     = ($row.find('input[name$="[channel_id]"]').val() || '').trim();
            const name    = ($row.find('input[name$="[name]"]').val() || '').trim();
            const service = $row.find('select[name$="[service]"]').val() || 'auto';
            const mode    = $row.find('select[name$="[mode]"]').val() || 'shareNow';
            if (isChecked && cid) {
                channels.push({ channel_id: cid, name: name, service: service, mode: mode });
            }
        });

        if (!apiKey || !channels.length) {
            Swal.fire('Missing Configuration', 'Please enter your Buffer API Key and at least one active Channel ID.', 'warning');
            return;
        }

        $btn.prop('disabled', true).html('<span class="dashicons dashicons-update BatchMate-spin"></span> Sending...');

        $.ajax({
            url: ajax_url,
            type: 'POST',
            data: {
                action: 'batchmate_test_buffer',
                _wpnonce: settings_nonce,
                api_key: apiKey,
                buffer_channels: channels
            },
            success: function(response) {
                if (response.success) {
                    Swal.fire({
                        title: 'Buffer Test Results',
                        html: '<div style="text-align:left; font-size:13px; max-height:250px; overflow-y:auto;">' + response.data.html + '</div>',
                        icon: 'info'
                    });
                } else {
                    Swal.fire('Buffer Test', response.data || 'Failed to send test post to Buffer.', 'warning');
                }
            },
            error: function() {
                Swal.fire('Error', 'Connection failed while testing Buffer.', 'error');
            },
            complete: function() {
                $btn.prop('disabled', false).html('<span class="dashicons dashicons-share-alt2"></span> Send Test Buffer Post');
            }
        });
    });

    /* ══════════════════════════════
       Facebook Method Dropdown
    ══════════════════════════════ */
    function handlePostMethodChange() {
        const method = $('#fb_post_method').val();
        $('#fb-credentials-wrapper, #make-credentials-wrapper').slideUp(150);
        if (method === 'access_token') $('#fb-credentials-wrapper').slideDown(200);
        else if (method === 'make_com') $('#make-credentials-wrapper').slideDown(200);
    }
    $('#fb_post_method').on('change', handlePostMethodChange);
    handlePostMethodChange();

    /* ══════════════════════════════
       Birthday Card Dynamic Canvas Preview
    ══════════════════════════════ */
    function refreshBirthdayCardPreview(forceRegenerate) {
        const template = $('#birthday_card_template').val();
        const badgeField = $('#birthday_card_badge_field').val();
        const customSubtitle = $('#birthday_card_custom_subtitle').val();
        
        $('#preview-filename').text(template);
        $('#preview-loading-spinner').css('display', 'inline-flex');
        $('#card-preview-overlay').css('display', 'flex');
        $('#card-preview').css('opacity', '0.4');
        $('#btn-refresh-card-preview').prop('disabled', true);

        $.ajax({
            url: ajax_url,
            type: 'POST',
            data: {
                action: 'batchmate_preview_birthday_card',
                _wpnonce: settings_nonce,
                template_name: template,
                birthday_card_badge_field: badgeField,
                birthday_card_custom_subtitle: customSubtitle,
                is_preview_cache: 1,
                force: forceRegenerate ? 1 : 0
            },
            success: function(res) {
                if (res.success && res.data.card_url) {
                    const cacheBustUrl = res.data.card_url + (res.data.card_url.indexOf('?') >= 0 ? '&' : '?') + 't=' + new Date().getTime();
                    const img = new Image();
                    img.onload = function() {
                        $('#card-preview').attr('src', cacheBustUrl).css('opacity', '1');
                        $('#card-preview-overlay').hide();
                        $('#preview-loading-spinner').hide();
                    };
                    img.onerror = function() {
                        $('#card-preview').attr('src', cacheBustUrl).css('opacity', '1');
                        $('#card-preview-overlay').hide();
                        $('#preview-loading-spinner').hide();
                    };
                    img.src = cacheBustUrl;
                } else {
                    $('#card-preview-overlay').hide();
                    $('#preview-loading-spinner').hide();
                    $('#card-preview').css('opacity', '1');
                }
            },
            error: function() {
                $('#card-preview-overlay').hide();
                $('#preview-loading-spinner').hide();
                $('#card-preview').css('opacity', '1');
            },
            complete: function() {
                $('#btn-refresh-card-preview').prop('disabled', false);
            }
        });
    }

    $('#birthday_card_template').on('change', function() {
        refreshBirthdayCardPreview(true);
    });

    $('#btn-refresh-card-preview').on('click', function(e) {
        e.preventDefault();
        refreshBirthdayCardPreview(true);
    });

    $('#birthday_card_badge_field').on('change', function() {
        refreshBirthdayCardPreview(true);
    });

    /* ══════════════════════════════
       Test FB Connection
    ══════════════════════════════ */
    $('#test-fb-connection').on('click', function(e) {
        e.preventDefault();
        const pageId = $('#fb_page_id').val().trim();
        const token  = $('#fb_access_token').val().trim();
        const $result = $('#fb-test-result');
        if (!pageId || !token) { $result.text('Please enter Page ID and Token first.').css('color','#ef4444'); return; }
        $result.text('Checking...').css('color','#6b7280');
        $.ajax({
            url: ajax_url, type: 'POST',
            data: { action: 'batchmate_test_fb_connection', _wpnonce: settings_nonce, page_id: pageId, token: token },
            success: function(res) {
                if (res.success) $result.text(res.data.message).css('color','#16a34a');
                else $result.text('Error: ' + res.data).css('color','#ef4444');
            },
            error: function() { $result.text('Connection failed.').css('color','#ef4444'); }
        });
    });

    /* ══════════════════════════════
       Save Form
    ══════════════════════════════ */
    $('#BatchMate-settings-form, #submit-settings').on('click', function(e) {
        if ($(this).is('#submit-settings')) {
            e.preventDefault();
            const $btn = $('#submit-settings');
            $btn.prop('disabled', true).html('<span class="dashicons dashicons-update BatchMate-spin"></span> Saving...');
            const formData = new FormData($('#BatchMate-settings-form')[0]);
            formData.append('action', 'batchmate_save_settings');
            formData.append('_wpnonce', settings_nonce);
            $.ajax({
                url: ajax_url, type: 'POST', data: formData, processData: false, contentType: false,
                success: function(res) {
                    if (res.success) {
                        Swal.fire({
                            title: 'Saved!',
                            text: 'Settings saved successfully.',
                            icon: 'success',
                            timer: 1200,
                            showConfirmButton: false
                        }).then(function() {
                            location.reload();
                        });
                    } else {
                        Swal.fire('Error!', res.data || 'Failed.', 'error');
                    }
                },
                complete: function() { $btn.prop('disabled', false).html('<span class="dashicons dashicons-saved"></span> Save Settings'); }
            });
        }
    });

    /* ══════════════════════════════
       Media Uploader — Logo
    ══════════════════════════════ */
    var mediaUploader;
    $('#org_logo_upload_button').on('click', function(e) {
        e.preventDefault();
        if (mediaUploader) { mediaUploader.open(); return; }
        mediaUploader = wp.media({ title: 'Choose Logo', button: { text: 'Use this image' }, multiple: false });
        mediaUploader.on('select', function() {
            const att = mediaUploader.state().get('selection').first().toJSON();
            $('#org_logo_url_input').val(att.url);
            $('#org-logo-preview').attr('src', att.url).show();
            $('#logo-placeholder').hide();
            $('#org_logo_remove_button').show();
        });
        mediaUploader.open();
    });
    $('#org_logo_remove_button').on('click', function() {
        $('#org_logo_url_input').val('');
        $('#org-logo-preview').attr('src', '').hide();
        $('#logo-placeholder').show();
        $(this).hide();
    });

    /* ══════════════════════════════
       Media Uploader — Banner
    ══════════════════════════════ */
    var bannerUploader;
    $('#banner_upload_button').on('click', function(e) {
        e.preventDefault();
        if (bannerUploader) { bannerUploader.open(); return; }
        bannerUploader = wp.media({ title: 'Choose Profile Banner', button: { text: 'Use this banner' }, multiple: false });
        bannerUploader.on('select', function() {
            const att = bannerUploader.state().get('selection').first().toJSON();
            $('#member_profile_banner_url_input').val(att.url);
            $('#banner-preview').attr('src', att.url);
            $('#banner_remove_button').show();
            $('#banner-overlay-label').text('✓ Custom Banner (unsaved)');
            // Update badge
            $('.BatchMate-badge').removeClass('BatchMate-badge-default').addClass('BatchMate-badge-custom').text('Custom');
        });
        bannerUploader.open();
    });
    $('#banner_remove_button').on('click', function() {
        $('#member_profile_banner_url_input').val('');
        $('#banner-preview').attr('src', defaultBannerUrl);
        $(this).hide();
        $('#banner-overlay-label').text('📌 Theme Default (restore after save)');
        $('.BatchMate-badge').removeClass('BatchMate-badge-custom').addClass('BatchMate-badge-default').text('Default');
    });

    /* Breadcrumb Image Uploader */
    var bcUploader;
    $('#bc_bg_image_upload_btn').on('click', function(e) {
        e.preventDefault();
        if (bcUploader) { bcUploader.open(); return; }
        bcUploader = wp.media({ title: 'Choose Breadcrumb Image', button: { text: 'Use this image' }, multiple: false });
        bcUploader.on('select', function() {
            const att = bcUploader.state().get('selection').first().toJSON();
            $('#bc_bg_image').val(att.url);
        });
        bcUploader.open();
    });
});