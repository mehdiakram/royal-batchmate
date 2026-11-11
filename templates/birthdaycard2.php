<?php
/**
 * Template Name: Birthday Card Design 2 (Warm Crimson & Champagne Gold - 1080x1080 Pure GD Canvas)
 */

if ( ! defined( 'ABSPATH' ) ) exit;

$card_width  = 1080;
$card_height = 1080;
$photo_size  = 340;

$final_image = imagecreatetruecolor( $card_width, $card_height );

// Deep Festive Burgundy / Wine Gradient
$top_r = 74;  $top_g = 4;   $top_b = 24;
$mid_r = 131; $mid_g = 24;  $mid_b = 67;
$bot_r = 46;  $bot_g = 2;   $bot_b = 13;

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

$color_white  = imagecolorallocate( $final_image, 255, 255, 255 );
$color_gold   = imagecolorallocate( $final_image, 251, 191, 36 );
$color_gold_lt= imagecolorallocate( $final_image, 254, 240, 138 );
$color_rose   = imagecolorallocate( $final_image, 253, 164, 175 );
$color_wine_dk= imagecolorallocate( $final_image, 76, 5, 25 );

// Double Gold & Rose Frame
for ( $t = 0; $t < 3; $t++ ) {
    imagerectangle( $final_image, 28 + $t, 28 + $t, 1052 - $t, 1052 - $t, $color_gold );
}
imagerectangle( $final_image, 38, 38, 1042, 1042, $color_gold_lt );

// Confetti Dots & Stars
$confetti = array(
    array(140, 100, 5), array(460, 90, 4),  array(960, 110, 6), array(900, 240, 4),
    array(120, 320, 4), array(590, 220, 3), array(620, 670, 5), array(1010, 710, 4),
    array(90, 750, 4),  array(510, 830, 4), array(950, 850, 4), array(180, 850, 5)
);
foreach ( $confetti as $c ) {
    imagefilledellipse( $final_image, $c[0], $c[1], $c[2] * 2, $c[2] * 2, $color_gold );
    imageline( $final_image, $c[0] - $c[2] * 3, $c[1], $c[0] + $c[2] * 3, $c[1], $color_gold_lt );
    imageline( $final_image, $c[0], $c[1] - $c[2] * 3, $c[0], $c[1] + $c[2] * 3, $color_gold_lt );
}

$font_dir    = ROYAL_BATCHMATE_PLUGIN_DIR . '/assets/fonts/';
$font_script = file_exists( $font_dir . 'GreatVibes-Regular.ttf' ) ? $font_dir . 'GreatVibes-Regular.ttf' : ( $font_dir . 'Roboto-Bold.ttf' );
$font_bold   = file_exists( $font_dir . 'PlayfairDisplay-Bold.ttf' ) ? $font_dir . 'PlayfairDisplay-Bold.ttf' : ( $font_dir . 'Montserrat-Bold.ttf' );
$font_sans   = file_exists( $font_dir . 'Roboto-Bold.ttf' ) ? $font_dir . 'Roboto-Bold.ttf' : ( $font_dir . 'Montserrat-Bold.ttf' );

// Title
imagettftext( $final_image, 105, 0, 80, 180, $color_gold_lt, $font_script, "Happy" );
imagettftext( $final_image, 105, 0, 140, 310, $color_gold, $font_script, "Birthday" );

imagettftext( $final_image, 22, 0, 85, 380, $color_rose, $font_sans, "BEST WISHES TO OUR DEAR" );

$user_name = ! empty( $user_name ) ? $user_name : 'Dear Batchmate';
$font_size_name = ( strlen( $user_name ) > 22 ) ? 34 : 46;

$shadow = imagecolorallocate( $final_image, 15, 2, 7 );
imagettftext( $final_image, $font_size_name, 0, 87, 462, $shadow, $font_bold, $user_name );
imagettftext( $final_image, $font_size_name, 0, 85, 460, $color_white, $font_bold, $user_name );

$badge_display = '';
if ( ! empty( $badge_text ) && strtolower( trim( $badge_text ) ) !== 'member' ) {
    $badge_display = strtoupper( trim( $badge_text ) );
} elseif ( ! empty( $program_name ) && strtolower( trim( $program_name ) ) !== 'member' ) {
    $badge_display = strtoupper( trim( $program_name ) );
}

if ( ! empty( $badge_display ) ) {
    $badge_w = min( 520, (int)( strlen( $badge_display ) * 14 + 40 ) );
    $badge_bg = imagecolorallocatealpha( $final_image, 159, 18, 57, 50 );
    imagefilledrectangle( $final_image, 85, 495, 85 + $badge_w, 535, $badge_bg );
    imagerectangle( $final_image, 85, 495, 85 + $badge_w, 535, $color_gold );
    imagettftext( $final_image, 16, 0, 105, 523, $color_gold_lt, $font_sans, $badge_display );
}

imagettftext( $final_image, 52, 0, 85, 660, $color_white, $font_script, "Wishing you good health, endless smiles," );
imagettftext( $final_image, 52, 0, 85, 740, $color_gold_lt, $font_script, "and prosperous joy on your special day!" );

// Photo
$photo_x = 660;
$photo_y = 230;

if ( ! empty( $user_photo_url ) ) {
    $src_img = null;
    $upload_dir  = wp_upload_dir();
    $local_photo = str_replace( $upload_dir['baseurl'], $upload_dir['basedir'], $user_photo_url );
    if ( file_exists( $local_photo ) ) {
        $pext = strtolower( pathinfo( $local_photo, PATHINFO_EXTENSION ) );
        if ( $pext === 'png' )       $src_img = @imagecreatefrompng( $local_photo );
        elseif ( in_array( $pext, ['jpg', 'jpeg'] ) ) $src_img = @imagecreatefromjpeg( $local_photo );
        elseif ( $pext === 'webp' && function_exists( 'imagecreatefromwebp' ) ) $src_img = @imagecreatefromwebp( $local_photo );
    }

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

        for ( $i = 0; $i < 6; $i++ ) {
            imageellipse( $final_image, (int)( $photo_x + $photo_size / 2 ), (int)( $photo_y + $photo_size / 2 ), $photo_size + $i, $photo_size + $i, $color_gold );
        }

        imagecopy( $final_image, $circular_img, $photo_x, $photo_y, 0, 0, $photo_size, $photo_size );
        imagedestroy( $src_img );
        imagedestroy( $circular_img );
        imagedestroy( $mask );
    } else {
        imagefilledellipse( $final_image, (int)( $photo_x + $photo_size / 2 ), (int)( $photo_y + $photo_size / 2 ), $photo_size, $photo_size, $color_white );
    }
}

// White Footer Pill with Logo from Settings
$box_x  = 60; 
$box_y  = 900; 
$box_w  = 960; 
$box_h  = 120; 
$radius = 50;

imagefilledellipse( $final_image, $box_x + $radius, $box_y + $radius, $radius * 2, $radius * 2, $color_white );
imagefilledellipse( $final_image, $box_x + $radius, $box_y + $box_h - $radius, $radius * 2, $radius * 2, $color_white );
imagefilledellipse( $final_image, $box_x + $box_w - $radius, $box_y + $radius, $radius * 2, $radius * 2, $color_white );
imagefilledellipse( $final_image, $box_x + $box_w - $radius, $box_y + $box_h - $radius, $radius * 2, $radius * 2, $color_white );
imagefilledrectangle( $final_image, $box_x + $radius, $box_y, $box_x + $box_w - $radius, $box_y + $box_h, $color_white );
imagefilledrectangle( $final_image, $box_x, $box_y + $radius, $box_x + $box_w, $box_y + $box_h - $radius, $color_white );

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

    imagecopyresampled( $final_image, $logo_src, (int)$pos_x, (int)$pos_y, 0, 0, (int)$new_w, (int)$new_h, $logo_orig_w, $logo_orig_h );
    imagedestroy( $logo_src );
} else {
    $bbox = imagettfbbox( 28, 0, $font_bold, $org_name );
    $tw   = $bbox[2] - $bbox[0];
    $tx   = $box_x + ( $box_w - $tw ) / 2;
    $ty   = $box_y + 70;
    imagettftext( $final_image, 28, 0, (int)$tx, (int)$ty, $color_wine_dk, $font_bold, $org_name );
}

imagejpeg( $final_image, $output_path, 88 );
imagedestroy( $final_image );
