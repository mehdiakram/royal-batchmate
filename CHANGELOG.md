# Changelog

All notable changes to the **Royal BatchMate** plugin will be documented in this file.

## [26.11.23] - 2026-11-12

### Added
- **Dedicated "Labels & Fields" Settings Tab:** Moved **Customize Front-End Labels** and **Form Fields & Labels Configuration** into a dedicated **Labels & Fields** tab (`includes/settings/tabs/tab-labels.php`), keeping `Registration` and `Automation` focused and cleanly separated.
- **Buffer Direct Connections (GraphQL API) in Multi-Webhook Settings:** Added a dedicated Buffer configuration panel inside **Automation -> Webhook Connections (Multi-Webhook)** (`tab-automation.php`, `admin-settings-page.php`, `admin-settings-ajax.php`, `admin-settings.js`) supporting a Buffer Personal API Key, one-click **Fetch Channels** discovery (`batchmate_fetch_buffer_channels`), multiple active Buffer channels with platform service metadata (`facebook`, `instagram`, `linkedin`, `twitter`, `threads`, `bluesky`, auto-detect fallback via `channel` GraphQL query) and per-channel post modes (`shareNow`, `addToQueue`, `shareNext`), plus a **Send Test Buffer Post** tester (`batchmate_test_buffer`).
- **Simultaneous Webhook + Buffer Dispatch:** Updated `batchmate_dispatch_webhooks()` and added `batchmate_dispatch_buffer()`, `batchmate_resolve_buffer_channel_service()`, `batchmate_buffer_graphql_request()`, and `batchmate_has_active_webhooks_or_buffer()` in `includes/automation-service.php` (plus checks in `includes/cron-jobs.php` and `includes/events/ajax/ajax-events-card.php`) so Birthday, Event Registration, and Member Approval events post to all active Webhook URLs and Buffer channels (`https://api.buffer.com` `createPost` mutation with `card_url` image assets and platform metadata such as `facebook.type = post`) simultaneously.

### Fixed
- **Settings Layout, Tab Leakage & Multi-Row Save Bug:** Removed two stray closing `</div>` tags in `tab-registration.php` and moved the closing `</form>` out of `tab-breadcrumb.php` into `admin-settings-page.php`, fixing the broken full-width layout, `#wpfooter` overlap, `Form Fields & Labels Configuration` leaking across tabs, and Automation/Buffer/Webhook multi-row fields failing to submit inside `<form id="BatchMate-settings-form">`. Also enabled `flex-wrap: wrap` on `.BatchMate-tab-nav` in `assets/admin-settings.css`.

## [26.11.22] - 2026-11-12

### Fixed
- **Existing Member Auto-Sync on Event Approval:** Fixed silent update failures when approving direct registrations matching an existing member by running `batchmate_ensure_location_columns()` prior to the transaction, filtering update/insert payloads against `SHOW COLUMNS`, checking update results, syncing all submitted profile fields (including English name, Bangla name, districts, and photo) plus linked WP user display name/avatar meta, and clearing legacy literal `'null'` district values.
- **District Display & Summary Fallback:** Ignored literal `'null'`/`'NULL'` strings when reading `permanent_district` and `present_district` in Registered Members DataTable and District-wise Registration Summary so valid `present_district` values display properly instead of `null`.
- **Database Migration Column Order:** Fixed `batchmate_ensure_location_columns()` to add `present_district` and `permanent_district` before `present_upazila`, `upazila`, `present_country`, and `permanent_country`.
- **Dynamic Event Form Field Labels:** Updated `[event_form]` (`shortcode-event-form.php` and `shortcode-event-form.js`) to load all configured labels via `batchmate_get_field_label()` (such as `Name (English)` and `Cell Number`) and render them dynamically in both form labels and validation alerts.

## [26.11.21] - 2026-11-12

### Added & Improved
- **Payment Method Summary Card & Print:** Added a side-by-side **পেমেন্ট সামারি (Payment Method Breakdown)** card next to the **T-Shirt / শাড়ি Summary** in Registered Members, along with dedicated Print support and Saree (`👘 শাড়ি`) breakdown counts.
- **Permanent District Priority:** Prioritized `permanent_district` (স্থায়ী জেলা) over `present_district` across the Registered Members table, print sheets, and **District-wise Registration** summary.
- **Modular Code Architecture & Asset Caching:** Refactored large monolithic files into cleanly separated, cache-busted (`filemtime`) CSS and JS assets:
  - Split `assets/admin-events.js` into 6 focused modules under `assets/events/` (`events-core.js`, `events-registered.js`, `events-applications.js`, `events-crud.js`, `events-card.js`, `events-finance.js`).
  - Extracted inline CSS/JS from `includes/shortcodes/shortcode-event-form.php` into `assets/shortcode-event-form.css` and `assets/shortcode-event-form.js`.
  - Extracted inline JS from `includes/admin-members-list-page.php` into `assets/admin-members-list.js`.
  - Extracted inline CSS/JS from `templates/single-member.php` into `assets/single-member.css` and `assets/single-member.js`.

### Fixed
- **Event Management AJAX Handlers:** Registered missing admin/public AJAX handler files in `royal-batchmate.php` resolving DataTables AJAX loading errors.
- **Print Table Alignment & Footer Label:** Fixed print table column/grand-total (`tfoot`) alignment across Print List and Print List with Amount, and fixed literal `${...}` JavaScript template expression appearing in the print footer.
- **Desktop Table Horizontal Scrollbar:** Improved responsive table container rules in `assets/admin-events.css` to avoid unnecessary horizontal scrollbars on desktop (`>=1200px`).

## [26.11.20] - 2026-11-12

### Added & Improved
- **Event Form Submission State & UX:** When submitting an event application, the submit button is immediately made readonly/disabled with duplicate-click protection, displaying `<span class="ef-btn-spinner"></span> সাবমিট হচ্ছে...`.
- **Auto-Scroll to Success Notification:** Upon successful application submission, the page smoothly auto-scrolls up to the success banner showing `✓ Your application has been submitted successfully. Please wait for approval.`, preventing users from getting lost at the website footer.
- **Robust Error Handling & State Recovery:** If submission encounters validation or network errors, the button gracefully restores its original state and text, and displays the error notice without losing user inputs.

## [26.11.19] - 2026-11-12

### Fixed
- **Database Query & Placeholder Mismatch:** Fixed missing `LEFT JOIN` on `batchmate_program` table across member login shortcode, member profile page, application AJAX list, application print, and member print templates that caused `Unknown column 'p.program_name'` and `wpdb::prepare` argument mismatch warnings. Fixed invalid `GROUP BY` syntax in admin programs AJAX.

## [26.11.18] - 2026-11-12

### Fixed
- **Frontend Event Form Render Crash (SyntaxError: Unexpected token '}'):** Removed an accidental duplicate closing brace in `includes/shortcodes/shortcode-event-form.php` that broke JavaScript execution on page load and caused the frontend registration form not to render. The script now parses cleanly and initializes all form components and Option 4 directly.

## [26.11.17] - 2026-11-12

### Fixed
- **Form Submission Failure ("Failed to save non-member information"):** Solved root cause where long event names (e.g. `99 Friends Reunion, Tangail-2026` = 32 chars) exceeded the `status varchar(20)` column in `batchmate_rawdata`. Expanded column to `varchar(100)`, set status to `'pending'`, added current MySQL `submission_date` timestamp, and dynamically filtered insert payloads against existing table columns.
- **Select2 District & Upazila Dropdown Search:** Enqueued Select2 in `<head>` (`$in_footer = false`) for frontend and Elementor widgets with multi-pass initialization (`initSelect2Dropdowns`) and cascading triggers, enabling responsive live search for 64 districts and upazilas.
- **Form Validation Popup & Red Field Highlighting:** Enqueued SweetAlert2 on the frontend and enhanced submission validation. If any required `*` field is missing, a popup lists all missing fields, highlights invalid inputs in red, and smoothly scrolls/focuses the first missing field.
- **Preserve Form Inputs on Error:** If a server or database error occurs during submission, the form is no longer wiped out — all entered information remains intact with an error banner and popup.

## [26.11.16] - 2026-11-12

### Fixed
- **"Failed to save non-member information" Error:** Fixed crash when guests submitted the event registration form. The `batchmate_rawdata` table was missing many columns (`institution`, `board`, `roll_no`, `registration_no`, `edu_group`, `name_bn`, `religion`, `present_district`, `present_country`, `present_upazila`, `permanent_district`, `permanent_country`, `upazila`, `organization`, `industry`, `work_experience`, `previous_organizations`, `about_me`, `skills_json`). Fixed by: (1) adding all missing columns to the CREATE TABLE definition, and (2) auto-migrating them on the live site via `batchmate_ensure_location_columns()` before each guest submission.

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
