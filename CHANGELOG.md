# Changelog

All notable changes to the **Royal BatchMate** plugin will be documented in this file.

## [26.11.15] - 2026-11-11

### Fixed
- **Guest Options Missing on Direct Form (Option 4):** Fixed race condition where Family/Guest options (with fees) did not appear because the form rendered before the AJAX options fetch completed. Form now renders inside the `.done()` callback so all options load first.
- **Duplicate Labels Section Removed:** Removed the redundant "Customize Front-End Labels (Institution & Locations)" card from Registration Settings — superseded by the full "Form Fields & Labels Configuration" table which covers all fields.

## [26.11.14] - 2026-11-11

### Fixed
- **Plugin Auto-Updater & BOM Resilience:** Sanitized UTF-8 BOM removal in remote update JSON responses and added cache-busting timestamp queries (`rbm_check`) to ensure GitHub update checks never fail or serve stale CDN caches.
- **Push Automation Script:** Updated `Push-RoyalBatchMate.bat` to strictly generate `royal-batchmate.json` in UTF-8 without BOM so automated updates always decode properly.

### Changed
- **Event Modal Localization:** Converted all Edit Event modal registration permission options and form title placeholders to clean English (`1. Members Only (Search Required)`, `4. Direct Form Only (Open Form Directly — No Search)`).

## [26.11.13] - 2026-11-11

### Added
- **Direct Form Only Mode (Option 4):** Added `4. Direct Form Only (সরাসরি ফর্ম — কোনো সার্চ থাকবে না)` in the Edit Event modal. When enabled, the event registration page completely bypasses the search box and displays the full membership & event registration form upfront with fresh photo upload and payment fields.
- **Custom Form Title / Button Text:** Added a custom text field in the Edit Event modal allowing administrators to rename the direct registration form title or button (e.g. `রেজিষ্ট্রেশন + ইভেন্ট রেজিষ্ট্রেশন`).
- **Segmented Mode Switcher Tabs (Option 3):** Added interactive tabs (`Search Existing Profile` vs `Register for Membership & Event`) when Option 3 is enabled, allowing seamless toggling between searching and direct registration.
- **Smart Member Profile Auto-Sync on Approval:** When an applicant submits a direct registration whose mobile number or email matches an existing member, approving the application automatically updates the member's profile with the freshly uploaded photo, corrected name, and district info.

## [26.11.12] - 2026-11-11

### Fixed
- **Plugin Activation BOM Fix:** Removed 3-byte UTF-8 Byte Order Mark (BOM) from `shortcode-ec.php`, `admin-dashboard-page.php`, and `royal-batchmate.json`, completely resolving the WordPress "plugin generated 3 characters of unexpected output during activation" and "headers already sent" warnings.
- **Event CC Email Delivery:** Fixed issue where event registration submissions omitted `cc_email` from the database query and mail headers. All event registration notifications, approval confirmations, and card resends now reliably deliver copies to configured event CC email address(es).
- **Multi-Address CC Support:** Added `batchmate_get_cc_headers()` helper to cleanly parse, sanitize, and validate comma-separated CC recipients.
- **Recipient Fallback for Registrations:** Allowed event confirmation emails to deliver directly to the event CC email address even if the applicant did not specify an email address.

### Changed
- **Default Payment Methods:** Updated payment method accounts and placeholders to `bKash 01552333272 Royal` and `bKash 01973245450 Mehdi` across event form modals and registration settings.

## [26.11.11] - 2026-11-11

### Added
- **Elementor Event Form Widget:** Dedicated Elementor widget with live styling controls (gradients, solid colors, typography, search box border styling).
- **GitHub Auto-Updater:** Integrated updater with release packaging and official branding assets.

## [26.11.10] - 2026-11-10

### Added
- **District (জেলা) Field on Cards:** Dynamic district fetching from member profile database (`present_district` / `permanent_district`) replacing obsolete Department / Attendee Type fields.
- **Serial Number Prefix Support:** Configurable prefix option for Card serial IDs (e.g., `TK`, `99TA-`).
- **Free Text Zone:** Added an optional custom text zone on cards with configurable text content.
- **Photo Shape Selection:** Added support for Square / Rectangle (চারকোনা), Circle (বৃত্তাকার), and Rounded (কোণা গোল) photo rendering without dark or black border artifacts.
- **Font Color Customization:** Real-time font color pickers for Name, Serial Number, Blood Group, District, Free Text, and Batch Year.
- **Lossless TrueColor Rendering Pipeline:** Automatic 32-bit TrueColor canvas conversion (`1080x1080`) ensuring zero palette posterization and lossless 100% JPEG export.
- **Bicubic Photo Resampling:** Native `IMG_BICUBIC` interpolation on source images preserving maximum clarity for 400x400 member photos.

### Fixed
- **Font Color Saving:** Fixed bug where card setup modal did not serialize newly picked font colors on save button click, ensuring `#ffffff` and other colors persist accurately.
- **Text Alignment (Left-Aligned):** Set strict left-alignment (`x`) for Name banner, Serial number, District, Blood group, and all dynamic text fields.
- **Long Name Banner Wrapping:** Implemented automatic 2-line word wrapping and dynamic font scaling for long names preventing horizontal text overflow.
- **HD Image Resolution:** Added protocol and www agnostic local path resolution and automatic WordPress thumbnail suffix stripping so original 400x400 member photos are loaded cleanly without network degradation.
- **Emoji Encoding Fix (Mojibake Resolution):** Fixed corrupted Windows-1252 character sequence (`ðŸŽ‰`, `ðŸŽŸï¸`) in event confirmation webhook messages to proper UTF-8 emojis (🎉 Party Popper and 🎟️ Ticket).
- **Serial ID Duplicate Prefix Fix:** Fixed issue where the member's default club prefix (e.g. `99BF-`) and the event card custom prefix (e.g. `99TA-`) were concatenated together (`99TA-99BF-00001`). Now automatically passes the registration serial (`99TA-0001`) and strips legacy prefixes if falling back to member number.
