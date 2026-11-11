<div id="BatchMate-apppreview-overlay" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.6);z-index:99994;"></div>
<div id="BatchMate-apppreview-modal" style="display:none;position:fixed;top:50%;left:50%;transform:translate(-50%,-50%);z-index:99995;background:#fff;border-radius:16px;box-shadow:0 24px 64px rgba(0,0,0,.3);width:min(600px,calc(100vw - 32px));max-height:90vh;overflow:hidden;flex-direction:column;" role="dialog" aria-labelledby="apppreview-title">
    <!-- Header -->
    <div style="background:linear-gradient(135deg,#1a3a6b,#2271b1);padding:20px 24px;display:flex;align-items:center;justify-content:space-between;flex-shrink:0;">
        <div style="display:flex;align-items:center;gap:12px;">
            <div id="apppreview-photo-wrap" style="width:52px;height:52px;border-radius:50%;overflow:hidden;border:2px solid rgba(255,255,255,.4);flex-shrink:0;background:#e2e8f0;">
                <img id="apppreview-photo" src="" style="width:100%;height:100%;object-fit:cover;" alt="">
            </div>
            <div>
                <h2 id="apppreview-title" style="margin:0;font-size:17px;color:#fff;font-weight:700;"></h2>
                <p id="apppreview-member-num" style="margin:3px 0 0;font-size:12px;color:rgba(255,255,255,.75);"></p>
            </div>
        </div>
        <button id="apppreview-close" style="background:rgba(255,255,255,.15);border:none;color:#fff;font-size:22px;cursor:pointer;width:36px;height:36px;border-radius:50%;display:flex;align-items:center;justify-content:center;line-height:1;transition:.2s;" aria-label="Close">&times;</button>
    </div>
    <!-- Body -->
    <div style="padding:24px;overflow-y:auto;">
        <table style="width:100%;border-collapse:collapse;font-size:13.5px;">
            <tbody id="apppreview-tbody">
            </tbody>
        </table>
    </div>
    <!-- Footer -->
    <div style="padding:14px 24px;border-top:1px solid #f0f0f1;display:flex;justify-content:flex-end;background:#f9fafb;flex-shrink:0;">
        <button id="apppreview-close-btn" class="BatchMate-ev-btn BatchMate-ev-btn--ghost">Close</button>
    </div>
</div>