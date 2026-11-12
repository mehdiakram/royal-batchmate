<?php
/**
 * Template Name: Birthday Card Design 1 (Pure GD Canvas 1080x1080)
 * Uses dynamic canvas rendering without any static template background file.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// 1. Canvas Dimensions: strictly 1080x1080 square
$card_width  = 1080;
$card_height = 1080;
$photo_size  = 340;

$final_image = imagecreatetruecolor( $card_width, $card_height );

// 2. Programmatic Canvas Background (Pure GD Gradient - No static background file)
// Deep Royal Celebration Gradient: Midnight Navy -> Royal Sapphire -> Deep Slate
$top_r = 10;  $top_g = 20;  $top_b = 40;
$mid_r = 24;  $mid_g = 52;  $mid_b = 98;
$bot_r = 13;  $bot_g = 27;  $bot_b = 52;

for ( $y = 0; $y < $card_height; $y++ ) {
    $norm = $y / $card_height;
    if ( $norm < 0.5 ) {
        $sub = $norm / 0.5;
        $r = (int)( $top_r + ( $mid_r - $top_r ) * $sub );
        $g = (int)( $top_g + ( $mid_g - $top_g ) * $sub );
        $b = (int)( $top_b + ( $mid_b - $top_b ) * $sub );
    } else {
        $sub = ( $norm - 0.5 ) / 0.5;
        $r = (int)( $mid_r + ( $bot_r - $mid_r ) * $sub );
        $g = (int)( $mid_g + ( $bot_g - $mid_g ) * $sub );
        $b = (int)( $mid_b + ( $bot_b - $mid_b ) * $sub );
    }
    $line_color = imagecolorallocate( $final_image, $r, $g, $b );
    imageline( $final_image, 0, $y, $card_width, $y, $line_color );
}

// 3. Colors
$color_white  = imagecolorallocate( $final_image, 255, 255, 255 );
$color_gold   = imagecolorallocate( $final_image, 245, 158, 11 );
$color_gold_lt= imagecolorallocate( $final_image, 253, 230, 138 );
$color_cyan   = imagecolorallocate( $final_image, 56, 189, 248 );
$color_blue_dk= imagecolorallocate( $final_image, 15, 35, 75 );

// Decorative Gold Frame (Double Border)
for ( $t = 0; $t < 3; $t++ ) {
    imagerectangle( $final_image, 28 + $t, 28 + $t, 1052 - $t, 1052 - $t, $color_gold );
}
imagerectangle( $final_image, 38, 38, 1042, 1042, $color_gold_lt );

// Decorative Corner Accents
$corner_size = 50;
// Top-Left
imageline( $final_image, 28, 28 + $corner_size, 28 + $corner_size, 28, $color_gold_lt );
// Top-Right
imageline( $final_image, 1052 - $corner_size, 28, 1052, 28 + $corner_size, $color_gold_lt );
// Bottom-Left
imageline( $final_image, 28, 1052 - $corner_size, 28 + $corner_size, 1052, $color_gold_lt );
// Bottom-Right
imageline( $final_image, 1052 - $corner_size, 1052, 1052, 1052 - $corner_size, $color_gold_lt );

// Subtle Celebration Accents (Golden Stars & Sparkles)
$stars = array(
    array(120, 90, 4),  array(480, 80, 3),  array(980, 120, 5), array(920, 220, 3),
    array(100, 340, 4), array(580, 210, 3), array(600, 680, 4), array(1020, 720, 4),
    array(80, 740, 3),  array(520, 840, 4), array(940, 840, 3), array(160, 860, 4),
);
foreach ( $stars as $s ) {
    imagefilledellipse( $final_image, $s[0], $s[1], $s[2] * 2, $s[2] * 2, $color_gold );
    imageline( $final_image, $s[0] - $s[2] * 3, $s[1], $s[0] + $s[2] * 3, $s[1], $color_gold_lt );
    imageline( $final_image, $s[0], $s[1] - $s[2] * 3, $s[0], $s[1] + $s[2] * 3, $color_gold_lt );
}

// 4. Fonts
if ( ! function_exists( 'batchmate_gd_draw_text' ) && file_exists( ROYAL_BATCHMATE_PLUGIN_DIR . 'includes/bangla-helper.php' ) ) {
    require_once ROYAL_BATCHMATE_PLUGIN_DIR . 'includes/bangla-helper.php';
}
$font_dir    = ROYAL_BATCHMATE_PLUGIN_DIR . '/assets/fonts/';
$font_script = file_exists( $font_dir . 'GreatVibes-Regular.ttf' ) ? $font_dir . 'GreatVibes-Regular.ttf' : ( $font_dir . 'Roboto-Bold.ttf' );
$font_bold   = file_exists( $font_dir . 'PlayfairDisplay-Bold.ttf' ) ? $font_dir . 'PlayfairDisplay-Bold.ttf' : ( $font_dir . 'Montserrat-Bold.ttf' );
$font_sans   = file_exists( $font_dir . 'Roboto-Bold.ttf' ) ? $font_dir . 'Roboto-Bold.ttf' : ( $font_dir . 'Montserrat-Bold.ttf' );

// 5. Typography on Canvas
// "Happy" & "Birthday" in script
imagettftext( $final_image, 105, 0, 80, 180, $color_gold_lt, $font_script, "Happy" );
imagettftext( $final_image, 105, 0, 140, 310, $color_gold, $font_script, "Birthday" );

// "to our respected batchmate"
imagettftext( $final_image, 22, 0, 85, 380, $color_cyan, $font_sans, "WARMEST GREETINGS TO" );

// Member Name (Bold and prominent)
$user_name = ! empty( $user_name ) ? $user_name : 'Valued Batchmate';
$name_len  = function_exists( 'mb_strlen' ) ? mb_strlen( $user_name, 'UTF-8' ) : strlen( $user_name );
$font_size_name = ( $name_len > 22 ) ? 34 : 46;

// Drop shadow for name
$color_shadow = imagecolorallocate( $final_image, 2, 6, 23 );
if ( function_exists( 'batchmate_gd_draw_text' ) ) {
    batchmate_gd_draw_text( $final_image, $font_size_name, 0, 87, 462, $color_shadow, $font_bold, $user_name );
    batchmate_gd_draw_text( $final_image, $font_size_name, 0, 85, 460, $color_white, $font_bold, $user_name );
} else {
    imagettftext( $final_image, $font_size_name, 0, 87, 462, $color_shadow, $font_bold, $user_name );
    imagettftext( $final_image, $font_size_name, 0, 85, 460, $color_white, $font_bold, $user_name );
}

// Subtitle / Badge (Only displayed if mapped/provided and never 'MEMBER')
$badge_display = '';
if ( ! empty( $badge_text ) && strtolower( trim( $badge_text ) ) !== 'member' ) {
    $badge_display = mb_strtoupper( trim( $badge_text ), 'UTF-8' );
} elseif ( ! empty( $program_name ) && strtolower( trim( $program_name ) ) !== 'member' ) {
    $badge_display = mb_strtoupper( trim( $program_name ), 'UTF-8' );
}

if ( ! empty( $badge_display ) ) {
    // Translucent badge pill
    if ( function_exists( 'batchmate_gd_measure_text' ) ) {
        $bm = batchmate_gd_measure_text( 16, $badge_display, $font_sans );
        $badge_w = min( 540, max( 120, $bm['w'] + 40 ) );
    } else {
        $badge_w = min( 520, (int)( strlen( $badge_display ) * 14 + 40 ) );
    }
    $badge_bg = imagecolorallocatealpha( $final_image, 30, 58, 138, 50 );
    imagefilledrectangle( $final_image, 85, 495, 85 + $badge_w, 535, $badge_bg );
    imagerectangle( $final_image, 85, 495, 85 + $badge_w, 535, $color_cyan );
    if ( function_exists( 'batchmate_gd_draw_text' ) ) {
        batchmate_gd_draw_text( $final_image, 16, 0, 105, 523, $color_cyan, $font_sans, $badge_display );
    } else {
        imagettftext( $final_image, 16, 0, 105, 523, $color_cyan, $font_sans, $badge_display );
    }
}

// Celebration Wish
imagettftext( $final_image, 52, 0, 85, 660, $color_white, $font_script, "Wishing you a year filled with" );
imagettftext( $final_image, 52, 0, 85, 740, $color_gold_lt, $font_script, "joy, great health & prosperous success!" );


// ==========================================
// 6. Member Photo (Circular with Gold Border on Canvas)
// ==========================================
$photo_x = 660;
$photo_y = 230;

if ( ! empty( $user_photo_url ) ) {
    $src_img = null;

    // Check local path
    $upload_dir  = wp_upload_dir();
    $local_photo = str_replace( $upload_dir['baseurl'], $upload_dir['basedir'], $user_photo_url );
    if ( file_exists( $local_photo ) ) {
        $pext = strtolower( pathinfo( $local_photo, PATHINFO_EXTENSION ) );
        if ( $pext === 'png' )       $src_img = @imagecreatefrompng( $local_photo );
        elseif ( in_array( $pext, ['jpg', 'jpeg'] ) ) $src_img = @imagecreatefromjpeg( $local_photo );
        elseif ( $pext === 'webp' && function_exists( 'imagecreatefromwebp' ) ) $src_img = @imagecreatefromwebp( $local_photo );
    }

    // Remote URL fallback
    if ( ! $src_img && function_exists( 'wp_remote_get' ) ) {
        $clean_url = strtok( $user_photo_url, '?' );
        $response  = wp_remote_get( $clean_url, array( 'timeout' => 12, 'sslverify' => false ) );
        if ( ! is_wp_error( $response ) && wp_remote_retrieve_response_code( $response ) === 200 ) {
            $image_data = wp_remote_retrieve_body( $response );
            if ( ! empty( $image_data ) && function_exists( 'imagecreatefromstring' ) ) {
                $src_img = @imagecreatefromstring( $image_data );
            }
        }
    }

    if ( $src_img ) {
        $sw = imagesx( $src_img );
        $sh = imagesy( $src_img );
        $sq = min( $sw, $sh );
        $sx = (int)( ( $sw - $sq ) / 2 );
        $sy = (int)( ( $sh - $sq ) / 2 );

        $circular_img = imagecreatetruecolor( $photo_size, $photo_size );
        imagealphablending( $circular_img, false );
        imagesavealpha( $circular_img, true );
        $transp = imagecolorallocatealpha( $circular_img, 0, 0, 0, 127 );
        imagefill( $circular_img, 0, 0, $transp );

        imagecopyresampled( $circular_img, $src_img, 0, 0, $sx, $sy, $photo_size, $photo_size, $sq, $sq );

        // Circle Mask
        $mask = imagecreatetruecolor( $photo_size, $photo_size );
        imagealphablending( $mask, false );
        imagesavealpha( $mask, true );
        imagefill( $mask, 0, 0, $transp );
        $black = imagecolorallocate( $mask, 0, 0, 0 );
        imagefilledellipse( $mask, $photo_size / 2, $photo_size / 2, $photo_size, $photo_size, $black );

        for ( $x = 0; $x < $photo_size; $x++ ) {
            for ( $y = 0; $y < $photo_size; $y++ ) {
                $rgb_src   = imagecolorat( $circular_img, $x, $y );
                $alpha_src = ( $rgb_src >> 24 ) & 0x7F;

                $rgb_mask   = imagecolorat( $mask, $x, $y );
                $alpha_mask = ( $rgb_mask >> 24 ) & 0x7F;

                $new_alpha = min( 127, $alpha_src + $alpha_mask );
                $new_color = imagecolorallocatealpha(
                    $circular_img,
                    ( $rgb_src >> 16 ) & 0xFF,
                    ( $rgb_src >> 8 ) & 0xFF,
                    $rgb_src & 0xFF,
                    $new_alpha
                );
                imagesetpixel( $circular_img, $x, $y, $new_color );
            }
        }

        // Gold border rings around photo
        for ( $i = 0; $i < 6; $i++ ) {
            imageellipse(
                $final_image,
                (int)( $photo_x + $photo_size / 2 ),
                (int)( $photo_y + $photo_size / 2 ),
                $photo_size + $i,
                $photo_size + $i,
                $color_gold
            );
        }

        imagecopy( $final_image, $circular_img, $photo_x, $photo_y, 0, 0, $photo_size, $photo_size );

        imagedestroy( $src_img );
        imagedestroy( $circular_img );
        imagedestroy( $mask );
    } else {
        imagefilledellipse( $final_image, (int)( $photo_x + $photo_size / 2 ), (int)( $photo_y + $photo_size / 2 ), $photo_size, $photo_size, $color_white );
    }
}


// ==========================================
// 7. White Footer Pill Box with Org Logo from Settings
// ==========================================
// Settings source: admin.php?page=batchmate_manage_settings#organization
$box_x  = 60; 
$box_y  = 900; 
$box_w  = 960; 
$box_h  = 120; 
$radius = 50;

// Rounded white container pill
imagefilledellipse( $final_image, $box_x + $radius, $box_y + $radius, $radius * 2, $radius * 2, $color_white );
imagefilledellipse( $final_image, $box_x + $radius, $box_y + $box_h - $radius, $radius * 2, $radius * 2, $color_white );
imagefilledellipse( $final_image, $box_x + $box_w - $radius, $box_y + $radius, $radius * 2, $radius * 2, $color_white );
imagefilledellipse( $final_image, $box_x + $box_w - $radius, $box_y + $box_h - $radius, $radius * 2, $radius * 2, $color_white );
imagefilledrectangle( $final_image, $box_x + $radius, $box_y, $box_x + $box_w - $radius, $box_y + $box_h, $color_white );
imagefilledrectangle( $final_image, $box_x, $box_y + $radius, $box_x + $box_w, $box_y + $box_h - $radius, $color_white );

// Fetch Organization Settings
if ( ! isset( $settings ) || ! is_array( $settings ) ) {
    $settings = get_option( 'batchmate_settings', array() );
}
$org_logo_url = isset( $settings['org_logo_url'] ) ? trim( $settings['org_logo_url'] ) : '';
$org_name     = function_exists( 'batchmate_org_label' ) ? batchmate_org_label( 'full' ) : ( $settings['org_name'] ?? 'BatchMate' );

$logo_src = null;
if ( ! empty( $org_logo_url ) ) {
    $upload_dir    = wp_upload_dir();
    $org_logo_path = str_replace( $upload_dir['baseurl'], $upload_dir['basedir'], $org_logo_url );

    if ( file_exists( $org_logo_path ) ) {
        $ext = strtolower( pathinfo( $org_logo_path, PATHINFO_EXTENSION ) );
        if ( $ext === 'png' )       $logo_src = @imagecreatefrompng( $org_logo_path );
        elseif ( in_array( $ext, ['jpg', 'jpeg'] ) ) $logo_src = @imagecreatefromjpeg( $org_logo_path );
        elseif ( $ext === 'webp' && function_exists( 'imagecreatefromwebp' ) ) $logo_src = @imagecreatefromwebp( $org_logo_path );
    }

    if ( ! $logo_src && function_exists( 'wp_remote_get' ) ) {
        $resp = wp_remote_get( $org_logo_url, array( 'timeout' => 10, 'sslverify' => false ) );
        if ( ! is_wp_error( $resp ) && wp_remote_retrieve_response_code( $resp ) === 200 ) {
            $raw_logo = wp_remote_retrieve_body( $resp );
            if ( ! empty( $raw_logo ) && function_exists( 'imagecreatefromstring' ) ) {
                $logo_src = @imagecreatefromstring( $raw_logo );
            }
        }
    }
}

if ( $logo_src ) {
    $logo_orig_w = imagesx( $logo_src );
    $logo_orig_h = imagesy( $logo_src );
    
    $max_h = 80;
    $max_w = $box_w - 60;

    $ratio = $logo_orig_w / max( 1, $logo_orig_h );
    $new_h = $max_h;
    $new_w = $new_h * $ratio;

    if ( $new_w > $max_w ) {
        $new_w = $max_w;
        $new_h = $new_w / $ratio;
    }

    $pos_x = $box_x + ( $box_w - $new_w ) / 2;
    $pos_y = $box_y + ( $box_h - $new_h ) / 2;

    imagecopyresampled(
        $final_image, 
        $logo_src, 
        (int)$pos_x, 
        (int)$pos_y, 
        0, 
        0, 
        (int)$new_w, 
        (int)$new_h, 
        $logo_orig_w, 
        $logo_orig_h
    );
    imagedestroy( $logo_src );
} else {
    // If no logo image uploaded, center organization name in bold inside the pill
    if ( function_exists( 'batchmate_gd_measure_text' ) && function_exists( 'batchmate_gd_draw_text' ) ) {
        $om = batchmate_gd_measure_text( 28, $org_name, $font_bold );
        $tx = $box_x + ( $box_w - $om['w'] ) / 2;
        $ty = $box_y + 70;
        batchmate_gd_draw_text( $final_image, 28, 0, (int)$tx, (int)$ty, $color_blue_dk, $font_bold, $org_name );
    } else {
        $bbox = imagettfbbox( 28, 0, $font_bold, $org_name );
        $tw   = $bbox[2] - $bbox[0];
        $tx   = $box_x + ( $box_w - $tw ) / 2;
        $ty   = $box_y + 70;
        imagettftext( $final_image, 28, 0, (int)$tx, (int)$ty, $color_blue_dk, $font_bold, $org_name );
    }
}

// 8. Output strictly 1080x1080 JPEG
imagejpeg( $final_image, $output_path, 88 );
imagedestroy( $final_image );
