<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

function BatchMate_get_members_ajax() {
    check_ajax_referer( 'batchmate_member_nonce' );
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( 'Permission denied.', 403 );
    }

    global $wpdb;
    $table_member = $wpdb->prefix . 'batchmate_member';

    $start = intval( $_POST['start'] ?? 0 );
    $length = intval( $_POST['length'] ?? 10 );
    if ( $length == -1 ) $length = 999999999;
    $search_value = isset($_POST['search']['value']) ? sanitize_text_field( $_POST['search']['value'] ) : '';

    // Filters
    $filter_institution = sanitize_text_field($_POST['filter_institution'] ?? '');
    $filter_batch = sanitize_text_field($_POST['filter_batch'] ?? '');
    $filter_gender = sanitize_text_field($_POST['filter_gender'] ?? '');
    $filter_blood_group = sanitize_text_field($_POST['filter_blood_group'] ?? '');
    $filter_payment_status = sanitize_text_field($_POST['filter_payment_status'] ?? '');

    $sql = "FROM $table_member AS m";
    $where_parts = [];
    if ( ! empty( $filter_institution ) ) {
        $where_parts[] = $wpdb->prepare("m.institution = %s", $filter_institution);
    }
    if ( ! empty( $filter_batch ) ) {
        $where_parts[] = $wpdb->prepare("m.batch = %s", $filter_batch);
    }
    if ( ! empty( $filter_gender ) ) {
        $where_parts[] = $wpdb->prepare("m.gender = %s", $filter_gender);
    }
    if ( ! empty( $filter_blood_group ) ) {
        $where_parts[] = $wpdb->prepare("m.blood_group = %s", $filter_blood_group);
    }
    // payment status filter goes here if applicable, but for now ignoring it since there is no payment_status in member table


    if ( ! empty( $search_value ) ) {
        $search_term = '%' . $wpdb->esc_like( $search_value ) . '%';
        $where_parts[] = $wpdb->prepare(
            "(m.name LIKE %s OR m.member_number LIKE %s OR m.email LIKE %s OR m.cell_number LIKE %s)",
            $search_term, $search_term, $search_term, $search_term
        );
    }

    if ( $where_parts ) {
        $sql .= ' WHERE ' . implode( ' AND ', $where_parts );
    }

    $order_column_index = intval( $_POST['order'][0]['column'] ?? 0 );
    $order_dir = (isset($_POST['order'][0]['dir']) && strtolower($_POST['order'][0]['dir']) === 'desc') ? 'DESC' : 'ASC';
    
    // Column mapping matching DataTables columns in admin-members-list-page.php:
    // 0: Mem. ID (member_number)
    // 1: Name (name)
    // 2: Photo (not orderable)
    // 3: Institute (institution)
    // 4: Contact (cell_number)
    // 5: Email (email)
    // 6: WP User (wp_user_id)
    $columns = array( 
        0 => 'm.member_number', 
        1 => 'm.name', 
        2 => 'm.member_number', 
        3 => 'm.institution', 
        4 => 'm.cell_number', 
        5 => 'm.email', 
        6 => 'm.wp_user_id' 
    ); 
    $order_column = $columns[$order_column_index] ?? 'm.member_number';

    if ( $order_column === 'm.member_number' ) {
        // Natural numerical sorting for member IDs like 99BF-00166 or BM-0001
        $order_by_sql = "ORDER BY 
            CASE WHEN m.member_number IS NULL OR TRIM(m.member_number) = '' THEN 1 ELSE 0 END ASC,
            SUBSTRING_INDEX(m.member_number, '-', 1) $order_dir,
            CAST(SUBSTRING_INDEX(m.member_number, '-', -1) AS UNSIGNED) $order_dir,
            m.member_number $order_dir";
    } else {
        $order_by_sql = "ORDER BY $order_column $order_dir";
    }

    $records_total = $wpdb->get_var( "SELECT COUNT(m.id) FROM $table_member AS m" );
    $records_filtered = $wpdb->get_var( "SELECT COUNT(m.id) $sql" );

    $data_sql = "SELECT m.* $sql $order_by_sql LIMIT %d, %d";
    $data = $wpdb->get_results( $wpdb->prepare( $data_sql, $start, $length ), ARRAY_A );

    $formatted_data = array();
    foreach ( $data as $row ) {
        if (empty($row['photo_url'])) {
            $row['photo_url'] = plugin_dir_url( dirname(__FILE__) ) . 'assets/images/noprofile.png';
        }
        
        $row['member_number'] = $row['member_number'] ?: '';
        $row['name'] = $row['name'] ?: '';
        $row['institution'] = esc_html($row['institution'] ?? '');
        $row['wp_username'] = ''; // It expects wp_username for the tooltip
        if ($row['wp_user_id'] > 0) {
            $user = get_userdata($row['wp_user_id']);
            if ($user) $row['wp_username'] = $user->user_login;
        }

        $formatted_data[] = $row;
    }

    $response = array(
        "draw"            => intval( $_POST['draw'] ?? 1 ),
        "recordsTotal"    => intval( $records_total ),
        "recordsFiltered" => intval( $records_filtered ),
        "data"            => $formatted_data
    );

    wp_send_json( $response );
}
add_action( 'wp_ajax_BatchMate_get_members', 'BatchMate_get_members_ajax' );
add_action( 'wp_ajax_puaa_get_members',       'BatchMate_get_members_ajax' );







function BatchMate_get_member_details_ajax() {
    check_ajax_referer( "batchmate_member_nonce" );
    if ( ! current_user_can( "manage_options" ) ) wp_send_json_error( "Permission denied.", 403 );

    global $wpdb;
    $member_id = intval( $_POST["member_id"] ?? 0 );
    if ( ! $member_id ) wp_send_json_error( "Invalid member ID." );

    $table_member = $wpdb->prefix . "batchmate_member";
    $member = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table_member WHERE id = %d", $member_id ) );

    if ( ! $member ) wp_send_json_error( "Member not found." );

    // Ensure photo url is populated
    if (empty($member->photo_url)) {
        $member->photo_url = plugin_dir_url( dirname(__FILE__) ) . "assets/images/noprofile.png";
    }

    wp_send_json_success( $member );
}
add_action( "wp_ajax_BatchMate_get_member_details", "BatchMate_get_member_details_ajax" );
add_action( "wp_ajax_puaa_get_member_details",       "BatchMate_get_member_details_ajax" );



// --- RESTORED FUNCTIONS ---

function BatchMate_save_member_details_ajax() {
    check_ajax_referer( "batchmate_member_nonce" );
    if ( ! current_user_can( "manage_options" ) ) wp_send_json_error( "Permission denied.", 403 );

    global $wpdb;
    $member_id = intval( $_POST["member_id"] ?? 0 );
    if ( ! $member_id ) wp_send_json_error( "Invalid member ID." );

    $table = $wpdb->prefix . "batchmate_member";
    $existing_member = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $member_id ) );
    if ( ! $existing_member ) {
        wp_send_json_error( "Member not found." );
    }

    $raw_dob = sanitize_text_field( $_POST["date_of_birth"] ?? "" );

    $data = [
        "name"                   => sanitize_text_field( $_POST["name"] ?? "" ),
        "name_bn"                => sanitize_text_field( $_POST["name_bn"] ?? "" ),
        "email"                  => sanitize_email( $_POST["email"] ?? "" ),
        "cell_number"            => sanitize_text_field( $_POST["cell_number"] ?? "" ),
        "cell_show"              => intval( $_POST["cell_show"] ?? 0 ),
        "land_phone_number"      => sanitize_text_field( $_POST["land_phone_number"] ?? "" ),
        "date_of_birth"          => ! empty( $raw_dob ) ? $raw_dob : null,
        "blood_group"            => sanitize_text_field( $_POST["blood_group"] ?? "" ),
        "gender"                 => sanitize_text_field( $_POST["gender"] ?? "" ),
        "religion"               => sanitize_text_field( $_POST["religion"] ?? "" ),
        "student_id"             => sanitize_text_field( $_POST["student_id"] ?? "" ),
        "institution"            => sanitize_text_field( $_POST["institution"] ?? "" ),
        "board"                  => sanitize_text_field( $_POST["board"] ?? "" ),
        "edu_group"              => sanitize_text_field( $_POST["edu_group"] ?? "" ),
        "employment_position"    => sanitize_text_field( $_POST["employment_position"] ?? "" ),
        "employment_address"     => sanitize_textarea_field( $_POST["employment_address"] ?? "" ),
        "organization"           => sanitize_text_field( $_POST["organization"] ?? "" ),
        "industry"               => sanitize_text_field( $_POST["industry"] ?? "" ),
        "work_experience"        => sanitize_text_field( $_POST["work_experience"] ?? "" ),
        "previous_organizations" => sanitize_textarea_field( $_POST["previous_organizations"] ?? "" ),
        "present_address"        => sanitize_textarea_field( $_POST["present_address"] ?? "" ),
        "present_district"       => sanitize_text_field( $_POST["present_district"] ?? "" ),
        "present_upazila"        => sanitize_text_field( $_POST["present_upazila"] ?? "" ),
        "present_country"        => sanitize_text_field( $_POST["present_country"] ?? "" ),
        "permanent_address"      => sanitize_textarea_field( $_POST["permanent_address"] ?? "" ),
        "permanent_district"     => sanitize_text_field( $_POST["permanent_district"] ?? "" ),
        "upazila"                => sanitize_text_field( $_POST["upazila"] ?? $_POST["permanent_upazila"] ?? "" ),
        "permanent_country"      => sanitize_text_field( $_POST["permanent_country"] ?? "" ),
        "about_me"               => sanitize_textarea_field( $_POST["about_me"] ?? "" ),
        "skills"                 => sanitize_text_field( $_POST["skills"] ?? "" ),
        "facebook_url"           => esc_url_raw( $_POST["facebook_url"] ?? "" ),
        "linkedin_url"           => esc_url_raw( $_POST["linkedin_url"] ?? "" ),
        "x_url"                  => esc_url_raw( $_POST["x_url"] ?? "" ),
        "website"                => esc_url_raw( $_POST["website"] ?? "" )
    ];

    if ( ! empty( $_POST["member_number"] ) ) {
        $data["member_number"] = sanitize_text_field( $_POST["member_number"] );
    }
    
    // Convert skills to JSON if needed
    $skills_raw = $data["skills"];
    if ( ! empty( $skills_raw ) ) {
        $skills_arr = array_map( "trim", explode( ",", $skills_raw ) );
        $skills_arr = array_filter( $skills_arr );
        $data["skills_json"] = json_encode( array_values( $skills_arr ), JSON_UNESCAPED_UNICODE );
    } else {
        $data["skills_json"] = "[]";
    }
    unset($data["skills"]);

    
    // --- Photo Upload Logic ---
    if ( isset( $_FILES["swal_photo"] ) && $_FILES["swal_photo"]["error"] === 0 ) {
        if ( ! function_exists( "wp_handle_upload" ) )    require_once ABSPATH . "wp-admin/includes/file.php";
        if ( ! function_exists( "wp_get_image_editor" ) ) require_once ABSPATH . "wp-admin/includes/image.php";
        if ( ! function_exists( "wp_insert_attachment" ) ) {
            require_once ABSPATH . "wp-admin/includes/media.php";
            require_once ABSPATH . "wp-admin/includes/post.php";
        }

        // Rename photo: Member ID / Name / Mobile / Timestamp
        $m_num = ! empty( $data["member_number"] ) ? $data["member_number"] : ( $existing_member->member_number ?? '' );
        if ( empty( $m_num ) ) {
            $m_num = ! empty( $existing_member->student_id ) ? $existing_member->student_id : ( ! empty( $existing_member->roll_no ) ? $existing_member->roll_no : $member_id );
        }
        $m_name = ! empty( $data["name"] ) ? $data["name"] : ( $existing_member->name ?? 'member' );
        $m_cell = ! empty( $data["cell_number"] ) ? $data["cell_number"] : ( $existing_member->cell_number ?? '' );

        $safe_id   = sanitize_title( $m_num );
        $safe_name = sanitize_title( $m_name );
        $safe_cell = preg_replace( '/[^0-9]/', '', $m_cell );

        $slug_parts = array_filter( [ $safe_id, $safe_name, $safe_cell ] );
        $file_slug  = ! empty( $slug_parts ) ? implode( '-', $slug_parts ) : ( 'member-' . $member_id );

        $ext = strtolower( pathinfo( $_FILES['swal_photo']['name'], PATHINFO_EXTENSION ) );
        if ( empty( $ext ) || ! in_array( $ext, [ 'jpg', 'jpeg', 'png', 'webp' ], true ) ) {
            $ext = 'jpg';
        }

        $new_filename = "{$file_slug}-" . time() . ".{$ext}";
        $_FILES['swal_photo']['name'] = $new_filename;

        // Temporarily lift upload size restriction
        if ( function_exists( 'BatchMate_override_upload_size_limit' ) ) {
            add_filter( 'upload_size_limit', 'BatchMate_override_upload_size_limit' );
        }
        $file = $_FILES["swal_photo"];
        $movefile = wp_handle_upload( $file, [ "test_form" => false ] );
        if ( function_exists( 'BatchMate_override_upload_size_limit' ) ) {
            remove_filter( 'upload_size_limit', 'BatchMate_override_upload_size_limit' );
        }

        if ( $movefile && ! isset( $movefile["error"] ) ) {
            if ( function_exists("BatchMate_crop_photo_400") ) {
                BatchMate_crop_photo_400( $movefile["file"] );
            } elseif ( function_exists("BatchMate_crop_photo_200") ) {
                BatchMate_crop_photo_200( $movefile["file"] );
            }

            // Remove previous photo from server & media library so no duplicate / extra files remain
            $old_photo_url = $existing_member->photo_url ?? '';
            if ( ! empty( $old_photo_url ) && strpos( $old_photo_url, 'noprofile.png' ) === false ) {
                $clean_old_url = strtok( $old_photo_url, '?' );

                // 1. Delete WordPress attachment if found
                if ( function_exists( 'attachment_url_to_postid' ) ) {
                    $old_att_id = attachment_url_to_postid( $clean_old_url );
                    if ( $old_att_id ) {
                        wp_delete_attachment( $old_att_id, true );
                    }
                }

                // 2. Delete local file on server if still present
                $old_file_path = function_exists( 'BatchMate_url_to_local_path' ) 
                    ? BatchMate_url_to_local_path( $clean_old_url ) 
                    : '';
                if ( empty( $old_file_path ) ) {
                    $upload_dir = wp_upload_dir();
                    if ( strpos( $clean_old_url, $upload_dir['baseurl'] ) === 0 ) {
                        $old_file_path = $upload_dir['basedir'] . substr( $clean_old_url, strlen( $upload_dir['baseurl'] ) );
                    }
                }
                if ( ! empty( $old_file_path ) && file_exists( $old_file_path ) ) {
                    @unlink( $old_file_path );
                }
            }

            $att = [
                "guid"           => $movefile["url"],
                "post_mime_type" => $movefile["type"],
                "post_title"     => sanitize_file_name( basename( $movefile["file"] ) ),
                "post_content"   => "",
                "post_status"    => "inherit",
            ];
            $att_id = wp_insert_attachment( $att, $movefile["file"] );
            if ( ! is_wp_error( $att_id ) ) {
                wp_update_attachment_metadata( $att_id, wp_generate_attachment_metadata( $att_id, $movefile["file"] ) );
                $data["photo_url"] = wp_get_attachment_url( $att_id );
            } else {
                $data["photo_url"] = $movefile["url"];
            }
            
            // Sync with WP Avatar if WP user exists
            if ( ! empty( $existing_member->wp_user_id ) ) {
                update_user_meta( $existing_member->wp_user_id, "batchmate_member_photo", $data["photo_url"] );
            }

            // Sync with application record if application_id exists
            if ( ! empty( $existing_member->application_id ) ) {
                $app_table = $wpdb->prefix . "batchmate_application";
                $wpdb->update( $app_table, [ "photo_url" => $data["photo_url"] ], [ "id" => $existing_member->application_id ] );
            }
        } else {
            $err_msg = isset( $movefile["error"] ) ? $movefile["error"] : "Failed to upload photo.";
            wp_send_json_error( "Photo upload failed: " . $err_msg );
        }
    }

    // Auto-generate email from member number if blank
    if ( empty( $data["email"] ) ) {
        $m_num = ! empty( $data["member_number"] ) ? $data["member_number"] : ( $existing_member->member_number ?? '' );
        if ( ! empty( $m_num ) && function_exists( 'batchmate_generate_member_email' ) ) {
            $data["email"] = batchmate_generate_member_email( $m_num );
        }
    }

    // Safety: ensure only existing table columns are sent to update
    $table_cols = $wpdb->get_col( "DESCRIBE {$table}" );
    if ( ! empty( $table_cols ) ) {
        $data = array_intersect_key( $data, array_flip( $table_cols ) );
    }

    $updated = $wpdb->update( $table, $data, [ "id" => $member_id ] );
    if ( false === $updated ) {
        wp_send_json_error( "Database update failed: " . $wpdb->last_error );
    }

    wp_send_json_success( "Member updated successfully." );
}
add_action( "wp_ajax_BatchMate_save_member_details", "BatchMate_save_member_details_ajax" );
add_action( "wp_ajax_puaa_save_member_details",       "BatchMate_save_member_details_ajax" );

function BatchMate_check_member_user_ajax() {
    check_ajax_referer( "batchmate_member_nonce" );
    if ( ! current_user_can( "manage_options" ) ) wp_send_json_error( "Permission denied." );
    
    global $wpdb;
    $member_id = intval($_POST["member_id"] ?? 0);
    if (!$member_id) wp_send_json_error("Invalid ID.");
    
    $table = $wpdb->prefix . "batchmate_member";
    $member = $wpdb->get_row($wpdb->prepare("SELECT email, wp_user_id FROM $table WHERE id = %d", $member_id));
    if (!$member) wp_send_json_error("Not found.");
    
    if ($member->wp_user_id) wp_send_json_success(["exists" => true, "msg" => "User already created."]);
    if (email_exists($member->email)) wp_send_json_success(["exists" => true, "msg" => "Email already used by another WP user."]);
    
    wp_send_json_success(["exists" => false]);
}
add_action( "wp_ajax_BatchMate_check_member_user", "BatchMate_check_member_user_ajax" );

function BatchMate_create_member_wp_user_ajax() {
    check_ajax_referer( "batchmate_member_nonce" );
    if ( ! current_user_can( "manage_options" ) ) wp_send_json_error( "Permission denied." );
    
    global $wpdb;
    $member_id = intval($_POST["member_id"] ?? 0);
    $table = $wpdb->prefix . "batchmate_member";
    $member = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $member_id));
    if (!$member) wp_send_json_error("Member not found.");

    if ( empty( $member->email ) && function_exists( 'batchmate_generate_member_email' ) ) {
        $member->email = batchmate_generate_member_email( $member->member_number );
        $wpdb->update( $table, [ "email" => $member->email ], [ "id" => $member_id ] );
    }
    
    $password = wp_generate_password( 12, false );
    $user_id = wp_create_user( $member->email, $password, $member->email );
    if (is_wp_error($user_id)) wp_send_json_error($user_id->get_error_message());
    
    $wpdb->update($table, ["wp_user_id" => $user_id], ["id" => $member_id]);
    
    // Optional: Send email with password
    
    wp_send_json_success("User created. Password: " . $password);
}
add_action( "wp_ajax_BatchMate_create_member_wp_user", "BatchMate_create_member_wp_user_ajax" );

function BatchMate_reset_member_credentials_ajax() {
    check_ajax_referer( "batchmate_member_nonce" );
    if ( ! current_user_can( "manage_options" ) ) wp_send_json_error( "Permission denied." );
    
    global $wpdb;
    $member_id = intval($_POST["member_id"] ?? 0);
    $table = $wpdb->prefix . "batchmate_member";
    $member = $wpdb->get_row($wpdb->prepare("SELECT wp_user_id FROM $table WHERE id = %d", $member_id));
    
    if (!$member->wp_user_id) wp_send_json_error("No WP User assigned.");
    
    $password = wp_generate_password( 12, false );
    wp_set_password( $password, $member->wp_user_id );
    
    wp_send_json_success("Password reset successfully. New Password: " . $password);
}
add_action( "wp_ajax_BatchMate_reset_member_credentials", "BatchMate_reset_member_credentials_ajax" );

function BatchMate_delete_member_ajax() {
    check_ajax_referer( "batchmate_member_nonce" );
    if ( ! current_user_can( "manage_options" ) ) wp_send_json_error( "Permission denied." );
    
    global $wpdb;
    $member_id = intval($_POST["member_id"] ?? 0);
    $table = $wpdb->prefix . "batchmate_member";
    $wpdb->delete($table, ["id" => $member_id]);
    wp_send_json_success("Member deleted.");
}
add_action( "wp_ajax_BatchMate_delete_member", "BatchMate_delete_member_ajax" );

