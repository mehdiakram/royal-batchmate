# Changelog

All notable changes to the **Royal BatchMate** plugin will be documented in this file.

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
