/**
 * Royal BatchMate - Events Card Setup & Preview Canvas
 */
jQuery(document).ready(function($) {
    const BM          = window.BatchMateEvents = window.BatchMateEvents || {};
    const ajax_url    = BM.ajax_url;
    const event_nonce = BM.event_nonce;
    const noPhoto     = BM.noPhoto;

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
    BM.openCardSetup = function(eventId, eventName, templateUrl, zonesJson) {
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
        BM.openCardSetup(
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
                BM.evtTable.ajax.reload(null, false);
                BM.regTable.ajax.reload(null, false);
            } else {
                Swal.fire('Error', res.data, 'error');
            }
        }).fail(function() {
            Swal.fire('Error', 'Request failed.', 'error');
        }).always(function() {
            $btn.prop('disabled', false).html('<span class="dashicons dashicons-saved"></span> Save Card Setup');
        });
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
            ctx.font = 'bold ' + sz + 'px Montserrat, "Hind Siliguri", "Noto Sans Bengali", Kalpurush, Arial, sans-serif';
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
        ctx.font = 'bold ' + fontSize + 'px Montserrat, "Hind Siliguri", "Noto Sans Bengali", Kalpurush, Arial, sans-serif';
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

        ctx.font = 'bold ' + fontSize + 'px Montserrat, "Hind Siliguri", "Noto Sans Bengali", Kalpurush, Arial, sans-serif';
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

});
