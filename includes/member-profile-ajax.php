<?php
/**
 * AJAX Handlers: Member Self-Edit Profile
 */
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Member saves own profile data
 */
function batchmate_member_self_save_ajax() {
    check_ajax_referer( 'batchmate_member_self_nonce' );

    if ( ! is_user_logged_in() ) {
        wp_send_json_error( 'You must be logged in.' );
    }

    global $wpdb;
    $current_user_id = get_current_user_id();
    $table           = $wpdb->prefix . 'batchmate_member';

    // Current user এর member record নিন
    $member = $wpdb->get_row( $wpdb->prepare(
        "SELECT * FROM $table WHERE wp_user_id = %d",
        $current_user_id
    ) );

    if ( ! $member ) {
        wp_send_json_error( 'No BatchMate member account linked to your profile.' );
    }

    $new_name  = sanitize_text_field( $_POST['name']  ?? '' );
    $new_email = sanitize_email(      $_POST['email'] ?? '' );

    if ( empty( $new_name ) )  wp_send_json_error( 'Name is required.' );
    if ( empty( $new_email ) ) {
        if ( function_exists( 'batchmate_generate_member_email' ) && ! empty( $member->member_number ) ) {
            $new_email = batchmate_generate_member_email( $member->member_number );
        } elseif ( ! empty( $member->email ) ) {
            $new_email = $member->email;
        }
    }

    // Email duplicate check (নিজের ID বাদে)
    if ( ! empty( $new_email ) && $new_email !== $member->email ) {
        $email_taken = $wpdb->get_var( $wpdb->prepare(
            "SELECT id FROM $table WHERE email = %s AND id != %d",
            $new_email, $member->id
        ) );
        if ( $email_taken ) {
            wp_send_json_error( 'This email is already used by another member.' );
        }
    }

    // ── Allowed fields (locked fields are intentionally excluded) ──
    $data = [
        'name'                     => $new_name,
        'name_bn'                  => sanitize_text_field( $_POST['name_bn'] ?? '' ),
        'email'                    => $new_email,
        'about_me'                 => sanitize_textarea_field( $_POST['about_me']                ?? '' ),
        'cell_number'              => sanitize_text_field( $_POST['cell_number']                 ?? '' ),
        'cell_show'                => intval( $_POST['cell_show'] ?? 0 ),
        'land_phone_number'        => sanitize_text_field( $_POST['land_phone_number']           ?? '' ),
        'date_of_birth'            => sanitize_text_field( $_POST['date_of_birth']               ?? '' ),
        'blood_group'              => sanitize_text_field( $_POST['blood_group']                 ?? '' ),
        'gender'                   => sanitize_text_field( $_POST['gender']                      ?? '' ),
        'religion'                 => sanitize_text_field( $_POST['religion']                    ?? '' ),
        'employment_position'      => sanitize_text_field( $_POST['employment_position']         ?? '' ),
        'organization'             => sanitize_text_field( $_POST['organization']                ?? '' ),
        'industry'                 => sanitize_text_field( $_POST['industry']                    ?? '' ),
        'work_experience'          => sanitize_text_field( $_POST['work_experience']             ?? '' ),
        'previous_organizations'   => sanitize_textarea_field( $_POST['previous_organizations']  ?? '' ),
        'employment_address'       => sanitize_textarea_field( $_POST['employment_address']      ?? '' ),
        'present_address'          => sanitize_textarea_field( $_POST['present_address']         ?? '' ),
        'permanent_address'        => sanitize_textarea_field( $_POST['permanent_address']       ?? '' ),
        'present_district'         => sanitize_text_field( $_POST['present_district']            ?? '' ),
        'present_country'          => sanitize_text_field( $_POST['present_country']             ?? 'Bangladesh' ),
        'present_upazila'          => sanitize_text_field( $_POST['present_upazila']             ?? '' ),
        'permanent_district'       => sanitize_text_field( $_POST['permanent_district']          ?? '' ),
        'permanent_country'        => sanitize_text_field( $_POST['permanent_country']           ?? 'Bangladesh' ),
        'upazila'                  => sanitize_text_field( $_POST['upazila']                     ?? '' ),
        'facebook_url'             => esc_url_raw( $_POST['facebook_url'] ?? '' ),
        'linkedin_url'             => esc_url_raw( $_POST['linkedin_url'] ?? '' ),
        'x_url'                    => esc_url_raw( $_POST['x_url']        ?? '' ),
        'website'                  => esc_url_raw( $_POST['website']       ?? '' ),
    ];

    // skills_json — comma-separated string থেকে JSON array
    $skills_raw = sanitize_text_field( $_POST['skills'] ?? '' );
    if ( ! empty( $skills_raw ) ) {
        $skills_arr = array_values( array_filter( array_map( 'trim', explode( ',', $skills_raw ) ) ) );
        $data['skills_json'] = wp_json_encode( $skills_arr );
    } else {
        $data['skills_json'] = '';
    }

    $format = array_fill( 0, count( $data ), '%s' );
    $format[4] = '%d'; // cell_show is integer

    // ── Photo Upload ──
    if ( isset( $_FILES['member_photo'] ) && $_FILES['member_photo']['error'] === 0 ) {
        if ( ! function_exists( 'wp_handle_upload' ) )    require_once ABSPATH . 'wp-admin/includes/file.php';
        if ( ! function_exists( 'wp_get_image_editor' ) ) require_once ABSPATH . 'wp-admin/includes/image.php';
        if ( ! function_exists( 'wp_insert_attachment' ) ) {
            require_once ABSPATH . 'wp-admin/includes/media.php';
            require_once ABSPATH . 'wp-admin/includes/post.php';
        }

        // পুরনো ছবি মুছুন
        if ( ! empty( $member->photo_url ) && strpos( $member->photo_url, 'noprofile.png' ) === false ) {
            if ( function_exists( 'attachment_url_to_postid' ) ) {
                $old_att_id = attachment_url_to_postid( $member->photo_url );
                if ( $old_att_id ) wp_delete_attachment( $old_att_id, true );
            }
        }

        // ফাইলের নাম rename
        $file      = $_FILES['member_photo'];
        $ext       = strtolower( pathinfo( $file['name'], PATHINFO_EXTENSION ) );
        $new_fname = sanitize_title( $member->roll_no ) . '-' . sanitize_title( $new_name ) . '-' . time() . '.' . $ext;
        $file['name']              = $new_fname;
        $_FILES['member_photo']    = $file;

        $movefile = wp_handle_upload( $file, [ 'test_form' => false ] );

        if ( $movefile && ! isset( $movefile['error'] ) ) {
            // 200×200 center crop (functions.php এ defined)
            if ( function_exists('BatchMate_crop_photo_400') ) {
                BatchMate_crop_photo_400( $movefile['file'] );
            } else {
                BatchMate_crop_photo_200( $movefile['file'] );
            }

            $att = [
                'guid'           => $movefile['url'],
                'post_mime_type' => $movefile['type'],
                'post_title'     => sanitize_file_name( basename( $movefile['file'] ) ),
                'post_content'   => '',
                'post_status'    => 'inherit',
            ];
            $att_id = wp_insert_attachment( $att, $movefile['file'] );
            if ( ! is_wp_error( $att_id ) ) {
                wp_update_attachment_metadata( $att_id, wp_generate_attachment_metadata( $att_id, $movefile['file'] ) );
                $data['photo_url'] = wp_get_attachment_url( $att_id );
            } else {
                $data['photo_url'] = $movefile['url'];
            }
            $format[] = '%s';
        }
    }

    // ── DB Update ──
    $result = $wpdb->update( $table, $data, [ 'id' => $member->id ], $format, [ '%d' ] );
    if ( $result === false ) {
        wp_send_json_error( 'Database error: ' . $wpdb->last_error );
    }

    // ── WordPress User Sync ──
    $wp_update = [ 'ID' => $current_user_id ];

    // Email sync
    if ( ! empty( $new_email ) && $new_email !== $member->email ) {
        $wp_email_check = get_user_by( 'email', $new_email );
        if ( ! $wp_email_check || (int) $wp_email_check->ID === $current_user_id ) {
            $wp_update['user_email'] = $new_email;
        }
    }

    // Name / display_name sync
    if ( $new_name !== $member->name ) {
        $parts = explode( ' ', $new_name, 2 );
        $wp_update['display_name'] = $new_name;
        $wp_update['first_name']   = $parts[0];
        $wp_update['last_name']    = $parts[1] ?? '';
    }

    if ( count( $wp_update ) > 1 ) {
        wp_update_user( $wp_update );
    }

    // ── WP Avatar Sync: photo upload হলে WP profile avatar ও update ──
    if ( isset( $data['photo_url'] ) && ! empty( $data['photo_url'] ) ) {
        update_user_meta( $current_user_id, 'batchmate_member_photo', $data['photo_url'] );
    }

    // ── Password Change ──
    $new_password = $_POST['new_password'] ?? '';
    if ( ! empty( $new_password ) ) {
        if ( strlen( $new_password ) < 6 ) {
            wp_send_json_error( 'Password must be at least 6 characters.' );
        }
        wp_set_password( $new_password, $current_user_id );
        // পাসওয়ার্ড পরিবর্তনের পর session ঠিক রাখো
        wp_set_auth_cookie( $current_user_id, false );
    }

    wp_send_json_success( 'Profile updated successfully!' );
}
add_action( 'wp_ajax_batchmate_member_self_save', 'batchmate_member_self_save_ajax' );
add_action( 'wp_ajax_puaa_member_self_save',       'batchmate_member_self_save_ajax' );
