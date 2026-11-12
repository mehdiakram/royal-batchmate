<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * =====================================================================
 * EVENT REGISTRATION CARD GENERATOR — PHP GD
 * =====================================================================
 * Generates a 1080x1080 card overlaying member data on a template
 * or renders on a pure programmatic GD canvas without background files.
 * Used for email attachment (server-side) and Make.com / Webhook payload.
 * =====================================================================
 */

// Font paths
define( 'BatchMate_FONT_BOLD',    ROYAL_BATCHMATE_PLUGIN_DIR . '/assets/fonts/Montserrat-Bold.ttf' );
define( 'BatchMate_FONT_REGULAR', ROYAL_BATCHMATE_PLUGIN_DIR . '/assets/fonts/Roboto-Bold.ttf' );

// Load Bengali text helper (Unicode-to-Bijoy converter + font selector)
if ( ! function_exists( 'batchmate_has_bangla' ) ) {
    $bangla_helper = ROYAL_BATCHMATE_PLUGIN_DIR . 'includes/bangla-helper.php';
    if ( file_exists( $bangla_helper ) ) {
        require_once $bangla_helper;
    }
}

/**
 * Generate a registration card image and return the temp file path.
 *
 * @param array $member  { name, photo_url, blood_group, batch, dept_name, program_name, member_number }
 * @param array $event   { card_template_url, card_zones_json, event_name, logo_url, show_org_logo }
 * @param string $type   'Member' or 'Non-Member'
 * @return string|false  Absolute path to temp PNG/JPG file, or false on failure.
 */
function BatchMate_generate_event_card( $member, $event, $type = 'Member' ) {
    $font_bold    = file_exists( BatchMate_FONT_BOLD )    ? BatchMate_FONT_BOLD    : BatchMate_FONT_REGULAR;
    $font_regular = file_exists( BatchMate_FONT_REGULAR ) ? BatchMate_FONT_REGULAR : $font_bold;

    // Fallback zones if not provided
    if ( empty( $event['card_zones_json'] ) ) {
        $zones = array(
            'photo'     => array( 'x' => 380, 'y' => 220, 'w' => 320, 'h' => 320, 'cx' => 540, 'cy' => 380, 'r' => 160, 'shape' => 'square' ),
            'name'      => array( 'x' => 140, 'y' => 640, 'w' => 800, 'h' => 60, 'size' => 26, 'color_text' => '#ffffff' ),
            'serial'    => array( 'x' => 820, 'y' => 220, 'w' => 200, 'h' => 44, 'size' => 28, 'color_text' => '#ffffff', 'prefix' => '' ),
            'blood'     => array( 'x' => 420, 'y' => 730, 'w' => 200, 'h' => 36, 'size' => 26, 'color_text' => '#ffffff' ),
            'district'  => array( 'x' => 420, 'y' => 790, 'w' => 240, 'h' => 36, 'size' => 26, 'color_text' => '#ffffff' ),
            'batch'     => array( 'x' => 420, 'y' => 850, 'w' => 200, 'h' => 36, 'size' => 26, 'color_text' => '#ffffff', 'enabled' => false ),
            'free_text' => array( 'x' => 420, 'y' => 910, 'w' => 240, 'h' => 36, 'size' => 26, 'color_text' => '#ffffff', 'enabled' => false ),
            'logo'      => array( 'x' => 60,  'y' => 50,  'w' => 240, 'h' => 80 ),
        );
    } else {
        $zones = is_array( $event['card_zones_json'] ) ? $event['card_zones_json'] : json_decode( $event['card_zones_json'], true );
        if ( ! is_array( $zones ) ) $zones = array();
    }

    // Sanitize and ensure valid font colors for all zones
    foreach ( array( 'name', 'serial', 'blood', 'district', 'free_text', 'batch', 'type', 'dept' ) as $zk ) {
        if ( isset( $zones[$zk] ) ) {
            if ( empty( $zones[$zk]['color_text'] ) ) {
                $zones[$zk]['color_text'] = '#ffffff';
            }
        }
    }

    $template_url = ! empty( $event['card_template_url'] ) ? trim( $event['card_template_url'] ) : '';
    $base = null;

    // Load template image if provided and not 'canvas'
    if ( ! empty( $template_url ) && $template_url !== 'canvas' ) {
        $template_path = BatchMate_url_to_local_path( $template_url );
        $base = BatchMate_gd_load_image( $template_path );
    }

    // Programmatic 1080x1080 Canvas (No background file required)
    if ( ! $base ) {
        $base = imagecreatetruecolor( 1080, 1080 );
        // Smooth Navy / Indigo Gradient
        for ( $y = 0; $y < 1080; $y++ ) {
            $ratio = $y / 1080;
            $r = (int)( 15 + ( 30 - 15 ) * $ratio );
            $g = (int)( 23 + ( 58 - 23 ) * $ratio );
            $b = (int)( 42 + ( 138 - 42 ) * $ratio );
            $col = imagecolorallocate( $base, $r, $g, $b );
            imageline( $base, 0, $y, 1080, $y, $col );
        }

        $col_gold  = imagecolorallocate( $base, 245, 158, 11 );
        $col_cyan  = imagecolorallocate( $base, 56, 189, 248 );
        $col_white = imagecolorallocate( $base, 255, 255, 255 );

        // Dual border
        imagerectangle( $base, 28, 28, 1052, 1052, $col_gold );
        imagerectangle( $base, 34, 34, 1046, 1046, $col_cyan );

        // Header Banner Background
        $hdr_bg = imagecolorallocatealpha( $base, 0, 0, 0, 60 );
        imagefilledrectangle( $base, 35, 35, 1045, 170, $hdr_bg );

        // Event Name in Header
        $event_title = ! empty( $event['event_name'] ) ? mb_strtoupper( (string) $event['event_name'], 'UTF-8' ) : 'EVENT ENTRY PASS';
        if ( function_exists( 'batchmate_gd_draw_text' ) ) {
            batchmate_gd_draw_text( $base, 28, 0, 60, 115, $col_gold, $font_bold, $event_title );
        } else {
            imagettftext( $base, 28, 0, 60, 115, $col_gold, $font_bold, $event_title );
        }
        imagettftext( $base, 16, 0, 60, 148, $col_cyan, $font_bold, 'OFFICIAL ATTENDEE ENTRY PASS' );

        // Center card background for attendee info
        $card_box = imagecolorallocatealpha( $base, 15, 23, 42, 40 );
        imagefilledrectangle( $base, 80, 580, 1000, 980, $card_box );
        imagerectangle( $base, 80, 580, 1000, 980, imagecolorallocatealpha( $base, 56, 189, 248, 80 ) );

        // Labels inside card box
        imagettftext( $base, 20, 0, 240, 730, $col_cyan, $font_regular, 'Blood Group:' );
        imagettftext( $base, 20, 0, 240, 790, $col_cyan, $font_regular, 'District:' );
        imagettftext( $base, 20, 0, 240, 850, $col_cyan, $font_regular, 'Batch Year:' );
    }

    // ALWAYS ensure the canvas is a pristine 1080x1080 32-bit TrueColor image with full color depth
    $orig_w = imagesx( $base );
    $orig_h = imagesy( $base );
    if ( $orig_w !== 1080 || $orig_h !== 1080 || ! imageistruecolor( $base ) ) {
        $tc_base = imagecreatetruecolor( 1080, 1080 );
        imagealphablending( $tc_base, false );
        imagesavealpha( $tc_base, true );
        $transparent = imagecolorallocatealpha( $tc_base, 0, 0, 0, 127 );
        imagefill( $tc_base, 0, 0, $transparent );
        imagecopyresampled( $tc_base, $base, 0, 0, 0, 0, 1080, 1080, $orig_w, $orig_h );
        imagedestroy( $base );
        $base = $tc_base;
    }
    imagealphablending( $base, true );

    $card_w = 1080;
    $card_h = 1080;

    // -- Place member photo (Shape: square, circle, or rounded) --
    if ( ! empty( $zones['photo'] ) && ! empty( $member['photo_url'] ) ) {
        $z     = $zones['photo'];
        $shape = ! empty( $z['shape'] ) ? $z['shape'] : 'square';

        if ( ! empty( $z['w'] ) && ! empty( $z['h'] ) ) {
            $pw = intval( $z['w'] );
            $ph = intval( $z['h'] );
            $px = intval( $z['x'] ?? ( ($z['cx'] ?? 540) - $pw / 2 ) );
            $py = intval( $z['y'] ?? ( ($z['cy'] ?? 360) - $ph / 2 ) );
        } else {
            $r  = intval( $z['r'] ?? 150 );
            $pw = $r * 2;
            $ph = $r * 2;
            $px = intval( ($z['cx'] ?? 540) - $r );
            $py = intval( ($z['cy'] ?? 360) - $r );
        }

        $photo_path = BatchMate_get_full_image_path( $member['photo_url'] );
        $photo_src  = BatchMate_gd_load_image( $photo_path );

        if ( $photo_src ) {
            // Ensure photo_src is truecolor for maximum quality without palette quantization
            if ( ! imageistruecolor( $photo_src ) ) {
                $tc_photo = imagecreatetruecolor( imagesx( $photo_src ), imagesy( $photo_src ) );
                imagealphablending( $tc_photo, false );
                imagesavealpha( $tc_photo, true );
                imagecopy( $tc_photo, $photo_src, 0, 0, 0, 0, imagesx( $photo_src ), imagesy( $photo_src ) );
                imagedestroy( $photo_src );
                $photo_src = $tc_photo;
            }

            if ( function_exists( 'imagesetinterpolation' ) ) {
                @imagesetinterpolation( $photo_src, IMG_BICUBIC );
                @imagesetinterpolation( $base, IMG_BICUBIC );
            }

            $sw = imagesx( $photo_src );
            $sh = imagesy( $photo_src );

            // Calculate cover-fit crop (no distortion)
            $target_ratio = $pw / max( 1, $ph );
            $src_ratio    = $sw / max( 1, $sh );

            if ( $src_ratio > $target_ratio ) {
                $crop_h = $sh;
                $crop_w = intval( $sh * $target_ratio );
                $src_x  = intval( ( $sw - $crop_w ) / 2 );
                $src_y  = 0;
            } else {
                $crop_w = $sw;
                $crop_h = intval( $sw / $target_ratio );
                $src_x  = 0;
                $src_y  = intval( ( $sh - $crop_h ) / 2 );
            }

            imagealphablending( $base, true );

            if ( $shape === 'circle' ) {
                $diam = min( $pw, $ph );
                $rad  = intval( $diam / 2 );

                $photo_resized = imagecreatetruecolor( $diam, $diam );
                imagealphablending( $photo_resized, false );
                imagesavealpha( $photo_resized, true );
                imagecopyresampled( $photo_resized, $photo_src, 0, 0, $src_x, $src_y, $diam, $diam, $crop_w, $crop_h );

                // Draw ONLY pixels inside circle onto base (strictly prevents black background)
                $rad_sq = $rad * $rad;
                for ( $ix = 0; $ix < $diam; $ix++ ) {
                    $dx = $ix - $rad;
                    $dx_sq = $dx * $dx;
                    for ( $iy = 0; $iy < $diam; $iy++ ) {
                        $dy = $iy - $rad;
                        if ( ( $dx_sq + $dy * $dy ) <= $rad_sq ) {
                            $src_color = imagecolorat( $photo_resized, $ix, $iy );
                            imagesetpixel( $base, $px + $ix, $py + $iy, $src_color );
                        }
                    }
                }
                imagedestroy( $photo_resized );
            } elseif ( $shape === 'rounded' ) {
                // Rounded rectangle
                $photo_resized = imagecreatetruecolor( $pw, $ph );
                imagealphablending( $photo_resized, false );
                imagesavealpha( $photo_resized, true );
                imagecopyresampled( $photo_resized, $photo_src, 0, 0, $src_x, $src_y, $pw, $ph, $crop_w, $crop_h );

                $corner_r = min( 20, intval( min( $pw, $ph ) / 8 ) );
                $cr_sq    = $corner_r * $corner_r;

                for ( $ix = 0; $ix < $pw; $ix++ ) {
                    for ( $iy = 0; $iy < $ph; $iy++ ) {
                        $in_corner = false;
                        if ( $ix < $corner_r && $iy < $corner_r ) {
                            $cdx = $corner_r - $ix; $cdy = $corner_r - $iy;
                            if ( ($cdx * $cdx + $cdy * $cdy) > $cr_sq ) $in_corner = true;
                        } elseif ( $ix >= ($pw - $corner_r) && $iy < $corner_r ) {
                            $cdx = $ix - ($pw - $corner_r); $cdy = $corner_r - $iy;
                            if ( ($cdx * $cdx + $cdy * $cdy) > $cr_sq ) $in_corner = true;
                        } elseif ( $ix < $corner_r && $iy >= ($ph - $corner_r) ) {
                            $cdx = $corner_r - $ix; $cdy = $iy - ($ph - $corner_r);
                            if ( ($cdx * $cdx + $cdy * $cdy) > $cr_sq ) $in_corner = true;
                        } elseif ( $ix >= ($pw - $corner_r) && $iy >= ($ph - $corner_r) ) {
                            $cdx = $ix - ($pw - $corner_r); $cdy = $iy - ($ph - $corner_r);
                            if ( ($cdx * $cdx + $cdy * $cdy) > $cr_sq ) $in_corner = true;
                        }

                        if ( ! $in_corner ) {
                            $src_color = imagecolorat( $photo_resized, $ix, $iy );
                            imagesetpixel( $base, $px + $ix, $py + $iy, $src_color );
                        }
                    }
                }
                imagedestroy( $photo_resized );
            } else {
                // Square / Rectangle — direct cover copy resampled onto base
                imagecopyresampled( $base, $photo_src, $px, $py, $src_x, $src_y, $pw, $ph, $crop_w, $crop_h );
            }

            imagedestroy( $photo_src );
        }
    }

    // -- Name (with auto line-break & font scaling) -----------------
    if ( ! empty( $zones['name'] ) ) {
        $z    = $zones['name'];
        $w    = intval( $z['w'] ?? 800 );
        $h    = intval( $z['h'] ?? 60 );
        $size = floatval( $z['size'] ?? 26 );
        if ( $size >= 34 ) {
            $size = 26; // Adjust legacy oversized 36 down to 26
        }
        $col  = BatchMate_hex_to_gd( $base, $z['color_text'] ?? '#ffffff' );
        $val  = trim( (string)( $member['name'] ?? '' ) );

        if ( $val !== '' ) {
            $calc_box = function( $sz, $txt ) use ( $font_bold ) {
                if ( function_exists( 'batchmate_gd_measure_text' ) ) {
                    return batchmate_gd_measure_text( $sz, $txt, $font_bold );
                }
                $b = imagettfbbox( $sz, 0, $font_bold, $txt );
                return array(
                    'w' => abs( $b[2] - $b[0] ),
                    'h' => abs( $b[1] - $b[7] ),
                );
            };

            $draw_text = function( $sz, $tx, $ty, $txt ) use ( $base, $col, $font_bold ) {
                if ( function_exists( 'batchmate_gd_draw_text' ) ) {
                    batchmate_gd_draw_text( $base, $sz, 0, (int) $tx, (int) $ty, $col, $font_bold, $txt );
                } else {
                    imagettftext( $base, $sz, 0, (int) $tx, (int) $ty, $col, $font_bold, $txt );
                }
            };

            $box = $calc_box( $size, $val );

            if ( $box['w'] <= $w ) {
                // Fits in single line — strictly left-aligned
                $tx = intval( $z['x'] );
                $ty = intval( $z['y'] ) - max( 2, (int)( ( $h - $box['h'] ) / 2 ) );
                $draw_text( $size, $tx, $ty, $val );
            } else {
                // Wrap into 2 lines
                $wrap_words = function( $sz, $txt ) use ( $calc_box, $w ) {
                    $words = preg_split( '/\s+/u', $txt );
                    $lines = array();
                    $cur   = '';
                    foreach ( $words as $wd ) {
                        $test = $cur ? ( $cur . ' ' . $wd ) : $wd;
                        $tbox = $calc_box( $sz, $test );
                        if ( $tbox['w'] <= $w ) {
                            $cur = $test;
                        } else {
                            if ( $cur !== '' ) $lines[] = $cur;
                            $cur = $wd;
                        }
                    }
                    if ( $cur !== '' ) $lines[] = $cur;
                    return $lines;
                };

                $lines = $wrap_words( $size, $val );
                while ( ( count( $lines ) > 2 || ( count( $lines ) * $size * 1.25 > $h ) || array_reduce( $lines, fn($c, $l) => $c || ($calc_box($size, $l)['w'] > $w), false ) ) && $size > 14 ) {
                    $size -= 2;
                    $lines = $wrap_words( $size, $val );
                }

                $line_h  = intval( $size * 1.25 );
                $total_h = count( $lines ) * $line_h;
                $box_top = intval( $z['y'] ) - $h;
                $start_y = $box_top + (int)( ( $h - $total_h ) / 2 ) + intval( $size );

                foreach ( $lines as $idx => $line ) {
                    $tx   = intval( $z['x'] ); // strictly left-aligned
                    $ty   = $start_y + ( $idx * $line_h );
                    $draw_text( $size, $tx, $ty, $line );
                }
            }
        }
    }

    $draw_zone_line = function( array $z, string $val, string $fallback_font, float $default_size = 26, int $default_h = 36 ) use ( $base ) {
        if ( $val === '' ) {
            return;
        }
        $col  = BatchMate_hex_to_gd( $base, $z['color_text'] ?? '#ffffff' );
        $size = floatval( $z['size'] ?? $default_size );
        $h    = intval( $z['h'] ?? $default_h );
        if ( function_exists( 'batchmate_gd_measure_text' ) && function_exists( 'batchmate_gd_draw_text' ) ) {
            $box = batchmate_gd_measure_text( $size, $val, $fallback_font );
            $ty  = intval( $z['y'] ) - max( 2, (int)( ( $h - $box['h'] ) / 2 ) );
            batchmate_gd_draw_text( $base, $size, 0, intval( $z['x'] ), (int) $ty, $col, $fallback_font, $val );
        } else {
            $bbox = imagettfbbox( $size, 0, $fallback_font, $val );
            $th   = $bbox[1] - $bbox[7];
            $ty   = intval( $z['y'] ) - max( 2, (int)( ( $h - $th ) / 2 ) );
            imagettftext( $base, $size, 0, intval( $z['x'] ), (int) $ty, $col, $fallback_font, $val );
        }
    };

    // -- Blood Group (optional) --------------------------------------------
    if ( ! empty( $zones['blood'] ) && ( ! isset( $zones['blood']['enabled'] ) || $zones['blood']['enabled'] !== false ) ) {
        $draw_zone_line( $zones['blood'], (string)( $member['blood_group'] ?? 'N/A' ), $font_regular, 26, 36 );
    }

    // -- District (optional — from member database) ------------------------
    if ( ! empty( $zones['district'] ) && ( ! isset( $zones['district']['enabled'] ) || $zones['district']['enabled'] !== false ) ) {
        $val = (string)( ! empty( $member['district'] ) ? $member['district']
                : ( ! empty( $member['present_district'] ) ? $member['present_district']
                : ( ! empty( $member['permanent_district'] ) ? $member['permanent_district'] : '' ) ) );
        $draw_zone_line( $zones['district'], $val, $font_regular, 26, 36 );
    }

    // -- Free Text (optional) ----------------------------------------------
    if ( ! empty( $zones['free_text'] ) && ( ! isset( $zones['free_text']['enabled'] ) || $zones['free_text']['enabled'] !== false ) ) {
        $val = isset( $zones['free_text']['text'] ) ? trim( (string) $zones['free_text']['text'] ) : '';
        $draw_zone_line( $zones['free_text'], $val, $font_bold, 26, 36 );
    }

    // -- Batch (optional — only if zone.enabled !== false) -----------------
    if ( ! empty( $zones['batch'] ) && ( ! isset( $zones['batch']['enabled'] ) || $zones['batch']['enabled'] !== false ) ) {
        $draw_zone_line( $zones['batch'], (string)( $member['batch'] ?? 'N/A' ), $font_regular, 26, 36 );
    }

    // -- Serial Number (with prefix option, e.g. TK or TEST) ----------------
    if ( ! empty( $zones['serial'] ) && ( ! isset( $zones['serial']['enabled'] ) || $zones['serial']['enabled'] !== false ) ) {
        $z          = $zones['serial'];
        $raw_serial = ! empty( $member['serial_number'] ) ? (string) $member['serial_number']
                    : ( ! empty( $member['serial_no'] ) ? sprintf('%04d', intval($member['serial_no'])) : ( ! empty( $member['id'] ) ? sprintf('%04d', intval($member['id'])) : '0001' ) );
        $prefix     = isset( $z['prefix'] ) ? trim( (string) $z['prefix'] ) : '';
        if ( $prefix !== '' ) {
            $sep = ( substr( $prefix, -1 ) === '-' || substr( $prefix, -1 ) === '#' || substr( $prefix, -1 ) === ' ' ) ? '' : ' ';
            $clean_serial = $raw_serial;
            if ( preg_match( '/^[A-Za-z0-9]+[-_](.+)$/', $raw_serial, $m_prefix ) ) {
                $clean_serial = $m_prefix[1];
            }
            $serial_val = $prefix . $sep . $clean_serial;
        } else {
            $serial_val = $raw_serial;
        }
        $draw_zone_line( $z, $serial_val, $font_bold, 28, 44 );
    }

    // -- Legacy compatibility: Type & Dept for existing cards --------------
    if ( ! empty( $zones['type'] ) && ( ! isset( $zones['type']['enabled'] ) || $zones['type']['enabled'] !== false ) ) {
        $draw_zone_line( $zones['type'], (string)( $type ), $font_regular, 26, 36 );
    }
    if ( ! empty( $zones['dept'] ) && ( ! isset( $zones['dept']['enabled'] ) || $zones['dept']['enabled'] !== false ) ) {
        $val = (string)( ! empty( $member['program_short_code'] ) ? $member['program_short_code'] : ( $member['program_name'] ?? 'N/A' ) );
        $draw_zone_line( $zones['dept'], $val, $font_regular, 26, 36 );
    }

    // -- Organization Logo (from Settings: admin.php?page=batchmate_manage_settings#organization) --
    $settings     = get_option( 'batchmate_settings', array() );
    $org_logo_url = ! empty( $event['logo_url'] ) ? $event['logo_url'] : ( $settings['org_logo_url'] ?? '' );

    if ( ! empty( $org_logo_url ) && ( ! empty( $zones['logo'] ) || ! empty( $event['show_org_logo'] ) ) ) {
        $logo_path = BatchMate_url_to_local_path( $org_logo_url );
        $logo_src  = BatchMate_gd_load_image( $logo_path );

        if ( $logo_src ) {
            $lw = imagesx( $logo_src );
            $lh = imagesy( $logo_src );

            $lz     = $zones['logo'] ?? array();
            $lx     = intval( $lz['x'] ?? 780 );
            $ly     = intval( $lz['y'] ?? 55 );
            $max_lw = intval( $lz['w'] ?? 220 );
            $max_lh = intval( $lz['h'] ?? 80 );

            $ratio  = $lw / max( 1, $lh );
            $dest_h = $max_lh;
            $dest_w = intval( $dest_h * $ratio );
            if ( $dest_w > $max_lw ) {
                $dest_w = $max_lw;
                $dest_h = intval( $dest_w / $ratio );
            }

            imagealphablending( $base, true );
            imagecopyresampled( $base, $logo_src, $lx, $ly, 0, 0, $dest_w, $dest_h, $lw, $lh );
            imagedestroy( $logo_src );
        }
    }

    // -- Save to BatchMate-cards directory --------------------------
    $upload_dir = wp_upload_dir();
    $basedir    = function_exists( 'trailingslashit' ) ? trailingslashit( $upload_dir['basedir'] ) : ( rtrim( $upload_dir['basedir'], '/\\' ) . '/' );
    $tmp_dir    = $basedir . 'BatchMate-cards/';
    if ( ! file_exists( $tmp_dir ) ) {
        if ( function_exists( 'wp_mkdir_p' ) ) {
            wp_mkdir_p( $tmp_dir );
        }
        if ( ! file_exists( $tmp_dir ) ) {
            @mkdir( $tmp_dir, 0755, true );
        }
    }
    $safe_name = sanitize_file_name( str_replace( ' ', '_', (string) ( $member['name'] ?? 'card' ) ) );
    $filename  = 'event_' . $safe_name . '_' . time() . '_' . uniqid() . '.jpg';
    $tmp_path  = $tmp_dir . $filename;

    // Fill background with white in case template has transparency
    $bg = imagecreatetruecolor( imagesx( $base ), imagesy( $base ) );
    $white = imagecolorallocate( $bg, 255, 255, 255 );
    imagefill( $bg, 0, 0, $white );
    imagealphablending( $bg, true );
    imagecopy( $bg, $base, 0, 0, 0, 0, imagesx( $base ), imagesy( $base ) );

    $saved = imagejpeg( $bg, $tmp_path, 100 ); // 100% Maximum Quality (Lossless JPEG)
    imagedestroy( $base );
    imagedestroy( $bg );

    return $saved ? $tmp_path : false;
}

/**
 * Generate card as base64 PNG/JPG string (for AJAX response).
 */
function BatchMate_generate_event_card_base64( $member, $event, $type = 'Member' ) {
    $tmp = BatchMate_generate_event_card( $member, $event, $type );
    if ( ! $tmp ) return false;
    $data = base64_encode( file_get_contents( $tmp ) );
    @unlink( $tmp );
    return $data;
}

// -- Helpers ------------------------------------------------------------

/**
 * Convert a WordPress media URL to an absolute server path,
 * preferring the original full-size image file over generated thumbnails.
 */
function BatchMate_get_full_image_path( $url ) {
    if ( empty( $url ) ) return '';
    $path = BatchMate_url_to_local_path( $url );
    if ( ! empty( $path ) && file_exists( $path ) ) {
        // Strip WordPress dimension suffix (e.g., -150x150, -300x300, -768x512, etc.)
        $orig_path = preg_replace( '/-\d+x\d+(\.[a-zA-Z0-9]+)$/', '$1', $path );
        if ( $orig_path !== $path && file_exists( $orig_path ) ) {
            return $orig_path;
        }
        // Strip WordPress -scaled suffix (e.g., image-scaled.jpg)
        $unscaled_path = preg_replace( '/-scaled(\.[a-zA-Z0-9]+)$/', '$1', $path );
        if ( $unscaled_path !== $path && file_exists( $unscaled_path ) ) {
            return $unscaled_path;
        }
        return $path;
    }
    // Remote URL fallback: strip WP thumbnail suffix from URL if present
    return preg_replace( '/-\d+x\d+(\.[a-zA-Z0-9]+)$/', '$1', $url );
}

/**
 * Convert a WordPress media URL to an absolute server path.
 */
function BatchMate_url_to_local_path( $url ) {
    if ( empty( $url ) ) return '';
    $upload_dir = wp_upload_dir();
    $base_url   = $upload_dir['baseurl'];
    $base_path  = $upload_dir['basedir'];

    if ( strpos( $url, $base_url ) === 0 ) {
        $p = $base_path . substr( $url, strlen( $base_url ) );
        if ( file_exists( $p ) ) return $p;
    }

    // Protocol & www agnostic matching against uploads directory
    $clean_url      = preg_replace( '#^https?://(www\.)?#i', '', $url );
    $clean_base_url = preg_replace( '#^https?://(www\.)?#i', '', $base_url );
    if ( strpos( $clean_url, $clean_base_url ) === 0 ) {
        $rel = substr( $clean_url, strlen( $clean_base_url ) );
        $p   = rtrim( $base_path, '/\\' ) . '/' . ltrim( $rel, '/\\' );
        if ( file_exists( $p ) ) return $p;
    }

    // Direct check for /wp-content/uploads/ relative path
    if ( strpos( $url, 'wp-content/uploads/' ) !== false ) {
        $rel = substr( $url, strpos( $url, 'wp-content/uploads/' ) + strlen( 'wp-content/uploads/' ) );
        $p   = rtrim( $base_path, '/\\' ) . '/' . ltrim( $rel, '/\\' );
        if ( file_exists( $p ) ) return $p;
    }

    $raw_site_url   = function_exists( 'get_site_url' ) ? get_site_url() : home_url();
    $clean_site_url = preg_replace( '#^https?://(www\.)?#i', '', $raw_site_url );
    if ( strpos( $clean_url, $clean_site_url ) === 0 ) {
        $rel = substr( $clean_url, strlen( $clean_site_url ) );
        $p   = rtrim( ABSPATH, '/\\' ) . '/' . ltrim( $rel, '/\\' );
        if ( file_exists( $p ) ) return $p;
    }

    return $url;
}

/**
 * Load an image with GD supporting JPG, PNG, WEBP, GIF, and Remote URLs,
 * ensuring the returned GdImage is always truecolor.
 */
function BatchMate_gd_load_image( $path ) {
    if ( empty( $path ) ) return false;
    $img = false;

    // Support remote URLs (e.g. external CDN, Make.com, or remote test photos)
    if ( ! file_exists( $path ) && ( strpos( $path, 'http://' ) === 0 || strpos( $path, 'https://' ) === 0 ) ) {
        if ( function_exists( 'wp_remote_get' ) ) {
            $response = wp_remote_get( $path, array( 'timeout' => 15, 'sslverify' => false ) );
            if ( ! is_wp_error( $response ) && wp_remote_retrieve_response_code( $response ) === 200 ) {
                $data = wp_remote_retrieve_body( $response );
                if ( ! empty( $data ) && function_exists( 'imagecreatefromstring' ) ) {
                    $img = @imagecreatefromstring( $data );
                }
            }
        }
    } elseif ( file_exists( $path ) ) {
        $type = @exif_imagetype( $path );
        switch ( $type ) {
            case IMAGETYPE_JPEG: $img = @imagecreatefromjpeg( $path ); break;
            case IMAGETYPE_PNG:  $img = @imagecreatefrompng( $path ); break;
            case IMAGETYPE_WEBP: $img = function_exists( 'imagecreatefromwebp' ) ? @imagecreatefromwebp( $path ) : false; break;
            case IMAGETYPE_GIF:  $img = @imagecreatefromgif( $path ); break;
            default:
                $data = @file_get_contents( $path );
                $img = $data ? @imagecreatefromstring( $data ) : false;
        }
    }

    if ( $img && ! imageistruecolor( $img ) ) {
        $tc = imagecreatetruecolor( imagesx( $img ), imagesy( $img ) );
        imagealphablending( $tc, false );
        imagesavealpha( $tc, true );
        imagecopy( $tc, $img, 0, 0, 0, 0, imagesx( $img ), imagesy( $img ) );
        imagedestroy( $img );
        $img = $tc;
    }

    return $img;
}

/**
 * Convert a hex color string to a GD color resource.
 */
function BatchMate_hex_to_gd( $image, $hex ) {
    $hex = ltrim( trim( (string) $hex ), '#' );
    if ( strlen( $hex ) === 3 ) {
        $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
    }
    if ( strlen( $hex ) !== 6 || ! ctype_xdigit( $hex ) ) {
        $hex = 'ffffff'; // Strict fallback to pure white
    }
    $r = hexdec( substr( $hex, 0, 2 ) );
    $g = hexdec( substr( $hex, 2, 2 ) );
    $b = hexdec( substr( $hex, 4, 2 ) );

    if ( function_exists( 'imagecolorresolve' ) ) {
        $col = imagecolorresolve( $image, $r, $g, $b );
        if ( $col !== false && $col !== -1 ) {
            return $col;
        }
    }
    $col = imagecolorallocate( $image, $r, $g, $b );
    if ( $col !== false && $col !== -1 ) {
        return $col;
    }
    return imagecolorresolve( $image, 255, 255, 255 );
}
