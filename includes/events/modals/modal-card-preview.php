<div id="BatchMate-card-modal-overlay" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.65);z-index:99992;"></div>
<div id="BatchMate-card-modal" style="display:none;position:fixed;top:50%;left:50%;transform:translate(-50%,-50%);z-index:99993;background:#fff;border-radius:16px;box-shadow:0 20px 60px rgba(0,0,0,.3);max-width:500px;width:calc(100% - 40px);max-height:90vh;overflow:hidden;flex-direction:column;">
    <div style="background:linear-gradient(135deg,#1a3a6b,#2271b1);padding:18px 24px;display:flex;align-items:center;justify-content:space-between;">
        <div>
            <h2 id="card-modal-title" style="margin:0;font-size:17px;color:#fff;">Registration Card</h2>
            <p id="card-modal-subtitle" style="margin:2px 0 0;font-size:12px;color:rgba(255,255,255,.75);"></p>
        </div>
        <button id="card-modal-close" style="background:rgba(255,255,255,.15);border:none;color:#fff;font-size:20px;cursor:pointer;width:32px;height:32px;border-radius:50%;display:flex;align-items:center;justify-content:center;">&times;</button>
    </div>
    <div style="padding:16px 20px;overflow-y:auto;text-align:center;max-height:calc(92vh - 140px);display:flex;align-items:center;justify-content:center;">
        <div id="card-modal-loading" style="padding:40px;color:#888;"><span class="dashicons dashicons-update" style="animation:spin 1s linear infinite;font-size:32px;width:32px;height:32px;"></span><p style="margin:10px 0 0;">Generating card...</p></div>
        <canvas id="BatchMate-card-canvas" style="display:none;max-width:100%;max-height:68vh;width:auto;height:auto;border-radius:8px;box-shadow:0 4px 20px rgba(0,0,0,.15);object-fit:contain;"></canvas>
    </div>
    <div style="padding:14px 20px;border-top:1px solid #f0f0f1;display:flex;justify-content:center;gap:12px;background:#f9f9f9;">
        <button id="card-btn-download" class="BatchMate-ev-btn BatchMate-ev-btn--primary" style="display:none;">
            <span class="dashicons dashicons-download"></span> Download PNG
        </button>
        <button id="card-btn-email" class="BatchMate-ev-btn BatchMate-ev-btn--outline" style="display:none;">
            <span class="dashicons dashicons-email-alt"></span> Send to Email
        </button>
    </div>
</div>