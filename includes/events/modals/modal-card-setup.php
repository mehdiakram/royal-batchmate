<div id="BatchMate-cardsetup-overlay" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.6);z-index:99990;"></div>
<div id="BatchMate-cardsetup-modal" style="display:none;position:fixed;top:50%;left:50%;transform:translate(-50%,-50%);
    z-index:99991;background:#fff;border-radius:16px;box-shadow:0 24px 64px rgba(0,0,0,.35);
    width:min(900px, calc(100vw - 40px));max-height:92vh;overflow:hidden;flex-direction:column;">

    <!-- Header -->
    <div style="background:linear-gradient(135deg,#1a3a6b,#2271b1);padding:18px 28px;display:flex;align-items:center;justify-content:space-between;flex-shrink:0;">
        <div>
            <h2 id="cardsetup-modal-title" style="margin:0;font-size:18px;color:#fff;display:flex;align-items:center;gap:8px;">
                <span class="dashicons dashicons-id" style="font-size:22px;width:22px;height:22px;"></span>
                Card Setup
            </h2>
            <p id="cardsetup-event-name" style="margin:4px 0 0;font-size:12px;color:rgba(255,255,255,.75);"></p>
        </div>
        <button id="cardsetup-close" style="background:rgba(255,255,255,.15);border:none;color:#fff;font-size:22px;cursor:pointer;width:36px;height:36px;border-radius:50%;display:flex;align-items:center;justify-content:center;line-height:1;">&times;</button>
    </div>

    <!-- Body -->
    <div style="display:flex;gap:0;flex:1;overflow:hidden;min-height:0;">

        <!-- LEFT: Upload Panel -->
        <div style="width:300px;flex-shrink:0;border-right:1px solid #e2e4e7;padding:20px;overflow-y:auto;background:#f9fafb;">
            <h4 style="margin:0 0 12px;font-size:13px;font-weight:700;color:#1d2327;display:flex;align-items:center;gap:6px;">
                <span class="dashicons dashicons-format-image" style="color:#2271b1;font-size:16px;width:16px;height:16px;"></span> 1. Upload Template Image
            </h4>
            <p style="margin:0 0 10px;font-size:11px;color:#888;line-height:1.5;">Upload the blank card image (1080 &times; 1080 px recommended). Dynamic fields will be overlaid automatically.</p>

            <input type="hidden" id="cs_card_template_url" value="">
            <input type="hidden" id="cs_card_zones_json" value="">
            <input type="hidden" id="cs_event_id" value="">

            <div id="cs-upload-area" style="border:2px dashed #c3c4c7;border-radius:8px;padding:16px;text-align:center;cursor:pointer;transition:.2s;background:#fff;" onclick="document.getElementById('cs_template_file_input').click()">
                <span class="dashicons dashicons-format-image" id="cs-upload-icon" style="font-size:36px;width:36px;height:36px;color:#c3c4c7;display:block;margin:0 auto 8px;"></span>
                <p id="cs-upload-label" style="margin:0;font-size:13px;color:#888;">Click to upload</p>
            </div>
            <input type="file" id="cs_template_file_input" accept="image/*" style="display:none;">
            <div id="cs-uploading" style="display:none;margin-top:8px;font-size:12px;color:#888;text-align:center;">
                <span class="dashicons dashicons-update" style="animation:spin 1s linear infinite;font-size:14px;width:14px;height:14px;"></span> Uploading...
            </div>
            <div id="cs-size-info" style="margin-top:8px;font-size:11px;min-height:16px;"></div>

            <hr style="margin:16px 0;border:0;border-top:1px solid #e2e4e7;">
            <h4 style="margin:0 0 10px;font-size:13px;font-weight:700;color:#1d2327;display:flex;align-items:center;gap:6px;">
                <span class="dashicons dashicons-editor-table" style="color:#2271b1;font-size:16px;width:16px;height:16px;"></span> 2. Active Field Zones
                <span style="font-size:10px;font-weight:400;color:#888;margin-left:auto;">Toggle to show/hide on card</span>
            </h4>

            <!-- Zone toggle rows -->
            <div style="display:flex;flex-direction:column;gap:6px;" id="cs-zone-toggles">

                <!-- Photo — always on, with shape selector -->
                <div style="padding:6px 8px;border-radius:6px;background:#eff6ff;border:1px solid #bfdbfe;">
                    <div style="display:flex;align-items:center;gap:8px;">
                        <span style="width:12px;height:12px;border-radius:50%;background:#3b82f6;flex-shrink:0;"></span>
                        <span style="font-size:12px;font-weight:600;color:#1e40af;flex:1;">Photo (ছবি)</span>
                        <span style="font-size:10px;color:#2563eb;font-weight:600;">Always on</span>
                    </div>
                    <div style="display:flex;align-items:center;gap:6px;margin-top:5px;">
                        <span style="font-size:11px;color:#1e40af;font-weight:600;">Shape:</span>
                        <select id="cs-photo-shape" style="font-size:11px;padding:2px 6px;height:24px;border-radius:4px;border:1px solid #93c5fd;background:#fff;color:#1e3a8a;flex:1;">
                            <option value="square">Square / Rectangle (চারকোনা)</option>
                            <option value="circle">Circle (বৃত্তাকার)</option>
                            <option value="rounded">Rounded (কোণা গোল)</option>
                        </select>
                    </div>
                </div>

                <!-- Name — always on, with font color & font size -->
                <div style="padding:6px 8px;border-radius:6px;background:#fffbeb;border:1px solid #fde68a;">
                    <div style="display:flex;align-items:center;gap:8px;">
                        <span style="width:12px;height:12px;border-radius:50%;background:#f59e0b;flex-shrink:0;"></span>
                        <span style="font-size:12px;font-weight:600;color:#92400e;flex:1;">Name Banner (নাম)</span>
                        <span style="font-size:10px;color:#d97706;font-weight:600;">Always on</span>
                    </div>
                    <div style="display:flex;align-items:center;gap:6px;margin-top:5px;">
                        <span style="font-size:11px;color:#92400e;font-weight:600;">Font Size:</span>
                        <input type="number" id="cs-size-name" min="12" max="60" value="26" style="width:55px;height:24px;font-size:11px;padding:2px 6px;border:1px solid #fde68a;border-radius:4px;" title="Name Font Size (px)">
                        <span style="font-size:11px;color:#92400e;font-weight:600;margin-left:auto;">Color:</span>
                        <input type="color" id="cs-color-name" data-zone="name" value="#ffffff" title="Name Font Color" style="width:32px;height:22px;border:none;padding:0;background:none;cursor:pointer;border-radius:4px;">
                    </div>
                </div>

                <!-- Serial No (with prefix & font color & font size) -->
                <div style="padding:6px 8px;border-radius:6px;background:#fdf4ff;border:1px solid #e9d5ff;">
                    <label style="display:flex;align-items:center;gap:8px;cursor:pointer;">
                        <span style="width:12px;height:12px;border-radius:50%;background:#a855f7;flex-shrink:0;"></span>
                        <span style="font-size:12px;font-weight:600;color:#6b21a8;flex:1;">Serial No. (আইডি / সিরিয়াল)</span>
                        <input type="checkbox" id="cs-toggle-serial" data-zone="serial" checked style="margin:0;">
                    </label>
                    <div style="display:flex;align-items:center;gap:6px;margin-top:5px;">
                        <span style="font-size:11px;color:#6b21a8;font-weight:600;">Prefix:</span>
                        <input type="text" id="cs-serial-prefix" placeholder="e.g. TK or TK-" style="flex:1;font-size:11px;padding:2px 6px;height:24px;border:1px solid #d8b4fe;border-radius:4px;" value="">
                        <span style="font-size:11px;color:#6b21a8;font-weight:600;">Size:</span>
                        <input type="number" id="cs-size-serial" min="12" max="50" value="28" style="width:48px;height:24px;font-size:11px;padding:2px 4px;border:1px solid #d8b4fe;border-radius:4px;" title="Serial Font Size (px)">
                        <span style="font-size:11px;color:#6b21a8;font-weight:600;">Color:</span>
                        <input type="color" id="cs-color-serial" data-zone="serial" value="#ffffff" title="Serial Font Color" style="width:28px;height:22px;border:none;padding:0;background:none;cursor:pointer;border-radius:4px;">
                    </div>
                </div>

                <!-- Blood Group -->
                <div style="padding:6px 8px;border-radius:6px;background:#f0fdf4;border:1px solid #bbf7d0;">
                    <label style="display:flex;align-items:center;gap:8px;cursor:pointer;">
                        <span style="width:12px;height:12px;border-radius:50%;background:#10b981;flex-shrink:0;"></span>
                        <span style="font-size:12px;font-weight:600;color:#065f46;flex:1;">Blood Group (রক্তের গ্রুপ)</span>
                        <input type="checkbox" id="cs-toggle-blood" data-zone="blood" checked style="margin:0;">
                    </label>
                    <div style="display:flex;align-items:center;gap:6px;margin-top:5px;">
                        <span style="font-size:11px;color:#065f46;font-weight:600;">Font Size:</span>
                        <input type="number" id="cs-size-blood" min="12" max="50" value="26" style="width:50px;height:24px;font-size:11px;padding:2px 4px;border:1px solid #bbf7d0;border-radius:4px;" title="Blood Group Font Size (px)">
                        <span style="font-size:11px;color:#065f46;font-weight:600;margin-left:auto;">Color:</span>
                        <input type="color" id="cs-color-blood" data-zone="blood" value="#ffffff" title="Blood Group Font Color" style="width:32px;height:22px;border:none;padding:0;background:none;cursor:pointer;border-radius:4px;">
                    </div>
                </div>

                <!-- District (জেলা) — from member DB -->
                <div style="padding:6px 8px;border-radius:6px;background:#f0fdfa;border:1px solid #99f6e4;">
                    <label style="display:flex;align-items:center;gap:8px;cursor:pointer;">
                        <span style="width:12px;height:12px;border-radius:50%;background:#0d9488;flex-shrink:0;"></span>
                        <span style="font-size:12px;font-weight:600;color:#0f766e;flex:1;">District (জেলা)</span>
                        <input type="checkbox" id="cs-toggle-district" data-zone="district" checked style="margin:0;">
                    </label>
                    <div style="display:flex;align-items:center;gap:6px;margin-top:5px;">
                        <span style="font-size:11px;color:#0f766e;font-weight:600;">Font Size:</span>
                        <input type="number" id="cs-size-district" min="12" max="50" value="26" style="width:50px;height:24px;font-size:11px;padding:2px 4px;border:1px solid #99f6e4;border-radius:4px;" title="District Font Size (px)">
                        <span style="font-size:11px;color:#0f766e;font-weight:600;margin-left:auto;">Color:</span>
                        <input type="color" id="cs-color-district" data-zone="district" value="#ffffff" title="District Font Color" style="width:32px;height:22px;border:none;padding:0;background:none;cursor:pointer;border-radius:4px;">
                    </div>
                </div>

                <!-- Free Text (কাস্টম টেক্সট) -->
                <div style="padding:6px 8px;border-radius:6px;background:#f5f3ff;border:1px solid #ddd6fe;">
                    <label style="display:flex;align-items:center;gap:8px;cursor:pointer;">
                        <span style="width:12px;height:12px;border-radius:50%;background:#6366f1;flex-shrink:0;"></span>
                        <span style="font-size:12px;font-weight:600;color:#4338ca;flex:1;">Free Text (ফ্রি টেক্সট)</span>
                        <input type="checkbox" id="cs-toggle-free_text" data-zone="free_text" style="margin:0;">
                    </label>
                    <div id="cs-freetext-controls" style="display:none;margin-top:5px;">
                        <input type="text" id="cs-freetext-val" placeholder="Type text (e.g. TK or Custom Text)" style="width:100%;font-size:11px;padding:2px 6px;height:24px;border:1px solid #c7d2fe;border-radius:4px;margin-bottom:4px;" value="">
                        <div style="display:flex;align-items:center;gap:6px;">
                            <span style="font-size:11px;color:#4338ca;font-weight:600;">Font Size:</span>
                            <input type="number" id="cs-size-free_text" min="12" max="50" value="26" style="width:50px;height:24px;font-size:11px;padding:2px 4px;border:1px solid #c7d2fe;border-radius:4px;" title="Free Text Font Size (px)">
                            <span style="font-size:11px;color:#4338ca;font-weight:600;margin-left:auto;">Color:</span>
                            <input type="color" id="cs-color-free_text" data-zone="free_text" value="#ffffff" title="Free Text Font Color" style="width:32px;height:22px;border:none;padding:0;background:none;cursor:pointer;border-radius:4px;">
                        </div>
                    </div>
                </div>

                <!-- Batch / Year -->
                <div style="padding:6px 8px;border-radius:6px;background:#fff1f2;border:1px solid #fecdd3;">
                    <label style="display:flex;align-items:center;gap:8px;cursor:pointer;">
                        <span style="width:12px;height:12px;border-radius:50%;background:#ef4444;flex-shrink:0;"></span>
                        <span style="font-size:12px;font-weight:600;color:#991b1b;flex:1;">Batch / Year</span>
                        <input type="checkbox" id="cs-toggle-batch" data-zone="batch" style="margin:0;">
                    </label>
                    <div style="display:flex;align-items:center;gap:6px;margin-top:5px;">
                        <span style="font-size:11px;color:#991b1b;font-weight:600;">Font Size:</span>
                        <input type="number" id="cs-size-batch" min="12" max="50" value="26" style="width:50px;height:24px;font-size:11px;padding:2px 4px;border:1px solid #fecdd3;border-radius:4px;" title="Batch Font Size (px)">
                        <span style="font-size:11px;color:#991b1b;font-weight:600;margin-left:auto;">Color:</span>
                        <input type="color" id="cs-color-batch" data-zone="batch" value="#ffffff" title="Batch Font Color" style="width:32px;height:22px;border:none;padding:0;background:none;cursor:pointer;border-radius:4px;">
                    </div>
                </div>

            </div>

            <hr style="margin:16px 0;border:0;border-top:1px solid #e2e4e7;">
            <div style="display:flex;gap:8px;">
                <button id="cs-reset-zones" class="BatchMate-ev-btn BatchMate-ev-btn--ghost" style="flex:1;justify-content:center;font-size:12px;padding:6px 10px;">
                    <span class="dashicons dashicons-image-rotate" style="font-size:14px;width:14px;height:14px;"></span> Reset Positions
                </button>
            </div>
            <p style="margin:8px 0 0;font-size:10px;color:#aaa;line-height:1.5;">Drag colored boxes to reposition. Toggle checkboxes to show/hide each field on the generated card.</p>
        </div>

        <!-- RIGHT: Visual Editor Canvas -->
        <div style="flex:1;padding:20px;overflow:auto;background:#f0f4f8;display:flex;align-items:flex-start;justify-content:center;">
            <div id="cs-editor-placeholder" style="text-align:center;padding:60px 20px;color:#aaa;">
                <span class="dashicons dashicons-format-image" style="font-size:64px;width:64px;height:64px;opacity:.3;"></span>
                <p style="margin:12px 0 0;font-size:14px;">Upload a card template to start positioning zones</p>
            </div>
            <div id="cs-zone-canvas-wrap" style="display:none;position:relative;border-radius:8px;overflow:hidden;box-shadow:0 8px 32px rgba(0,0,0,.18);">
                <img id="cs-zone-bg" src="" style="display:block;max-height:70vh;width:auto;" draggable="false">
                <!-- Zone overlays injected here -->
            </div>
        </div>
    </div>

    <!-- Footer -->
    <div style="padding:14px 28px;border-top:1px solid #f0f0f1;display:flex;justify-content:flex-end;gap:12px;background:#fff;flex-shrink:0;">
        <button id="cardsetup-cancel" class="BatchMate-ev-btn BatchMate-ev-btn--ghost">Cancel</button>
        <button id="cardsetup-save" class="BatchMate-ev-btn BatchMate-ev-btn--primary">
            <span class="dashicons dashicons-saved"></span> Save Card Setup
        </button>
    </div>
</div>