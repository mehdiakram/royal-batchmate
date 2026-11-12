<?php
if ( ! defined( 'ABSPATH' ) ) exit;

// 4. GET EVENT APPLICATIONS (DataTable — Tab 2)
// ─────────────────────────────────────────────────────────────────────
function BatchMate_get_event_applications_ajax() {
    batchmate_event_check_admin();
    global $wpdb;

    $event_id = intval( $_POST['event_id'] ?? 0 );

    $where = $event_id > 0 ? $wpdb->prepare( "WHERE ea.event_id = %d", $event_id ) : '';

    $rows = $wpdb->get_results( "
        SELECT
            ea.id,
            ea.event_id,
            ev.event_name,
            ea.member_id,
            ea.rawdata_id,
            IF(ea.member_id > 0, m.member_number, 'Non-Member') AS member_number,
            IF(ea.member_id > 0, m.name, r.name) AS name,
            IF(ea.member_id > 0, m.photo_url, r.photo_url) AS photo_url,
            '' AS batch,
            IF(ea.member_id > 0, m.institution, r.institution) AS program_name,
            IF(ea.member_id > 0, m.student_id, r.student_id) AS student_id,
            IF(ea.member_id > 0, m.cell_number, r.cell_number) AS cell_number,
            IF(ea.member_id > 0, m.email, r.email) AS email,
            IF(ea.member_id > 0, m.blood_group, r.blood_group) AS blood_group,
            IF(ea.member_id > 0, m.gender, r.gender) AS gender,
            ea.payment_method,
            ea.payment_details,
            ea.amount,
            ea.options_json,
            ea.tshirt_size,
            ea.status,
            ea.applied_at,
            ev.has_tshirt
        FROM {$wpdb->prefix}batchmate_event_application ea
        LEFT JOIN {$wpdb->prefix}batchmate_event  ev   ON ea.event_id  = ev.id
        LEFT JOIN {$wpdb->prefix}batchmate_member m    ON ea.member_id = m.id
                LEFT JOIN {$wpdb->prefix}batchmate_rawdata r ON ea.rawdata_id = r.id
                $where
        ORDER BY ea.id DESC
    " );

    wp_send_json( array( 'data' => $rows ? $rows : [] ) );
}
add_action( 'wp_ajax_BatchMate_get_event_applications', 'BatchMate_get_event_applications_ajax' );
add_action( 'wp_ajax_puaa_get_event_applications',       'BatchMate_get_event_applications_ajax' );


// ─────────────────────────────────────────────────────────────────────
// 5. APPROVE EVENT APPLICATION
// ─────────────────────────────────────────────────────────────────────
function BatchMate_approve_event_application_ajax() {
    batchmate_event_check_admin();
    global $wpdb;

    $app_id = intval( $_POST['app_id'] ?? 0 );
    if ( ! $app_id ) wp_send_json_error( 'Invalid application ID.' );

    $app_table    = $wpdb->prefix . 'batchmate_event_application';
    $member_table = $wpdb->prefix . 'batchmate_event_members';

    $app = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $app_table WHERE id = %d", $app_id ) );
    if ( ! $app ) wp_send_json_error( 'Application not found.' );
    if ( $app->status === 'approved' ) wp_send_json_error( 'Already approved.' );

    // Fetch event
    $event = $wpdb->get_row( $wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}batchmate_event WHERE id = %d",
        $app->event_id
    ) );
    if ( ! $event ) wp_send_json_error( 'Event not found.' );

    // Duplicate check in event_members
    $exists = $wpdb->get_var( $wpdb->prepare(
        "SELECT COUNT(id) FROM $member_table WHERE event_id = %d AND member_id = %d AND rawdata_id = %d",
        $app->event_id, $app->member_id, $app->rawdata_id
    ) );
    if ( $exists > 0 ) wp_send_json_error( 'Member/Non-Member already registered for this event.' );

    $new_user_login_info = null;
    $target_member_id    = intval( $app->member_id );

    $wpdb->query( 'START TRANSACTION' );
    try {
        // Option 2 & 3 & 4: If allow_non_members >= 2 and applicant is a guest (member_id == 0 && rawdata_id > 0)
        // Automatically convert guest to full club member and create WP user!
        if ( intval( $event->allow_non_members ) >= 2 && $app->member_id == 0 && $app->rawdata_id > 0 ) {
            $rawdata_table   = $wpdb->prefix . 'batchmate_rawdata';
            $bm_member_table = $wpdb->prefix . 'batchmate_member';

            $raw = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $rawdata_table WHERE id = %d", $app->rawdata_id ) );
            if ( ! raw ) {
                throw new Exception( 'Non-member data not found in rawdata records.' );
            }

            // Check if member with this cell_number, email, or student_id already exists in batchmate_member
            $existing_mid = 0;
            if ( ! empty( $raw->cell_number ) ) {
                $existing_mid = (int) $wpdb->get_var( $wpdb->prepare(
                    "SELECT id FROM $bm_member_table WHERE cell_number = %s LIMIT 1",
                    $raw->cell_number
                ) );
            }
            if ( ! $existing_mid && ! empty( $raw->email ) ) {
                $existing_mid = (int) $wpdb->get_var( $wpdb->prepare(
                    "SELECT id FROM $bm_member_table WHERE email = %s LIMIT 1",
                    $raw->email
                ) );
            }
            if ( ! $existing_mid && ! empty( $raw->student_id ) ) {
                $existing_mid = (int) $wpdb->get_var( $wpdb->prepare(
                    "SELECT id FROM $bm_member_table WHERE student_id = %s LIMIT 1",
                    $raw->student_id
                ) );
            }

            if ( $existing_mid > 0 ) {
                $target_member_id = $existing_mid;
                // Auto-sync: update existing member's photo and details with the fresh submission!
                $upd_data = array();
                if ( ! empty( $raw->photo_url ) )            $upd_data['photo_url'] = $raw->photo_url;
                if ( ! empty( $raw->name ) )                 $upd_data['name'] = $raw->name;
                if ( ! empty( $raw->name_bn ) )              $upd_data['name_bn'] = $raw->name_bn;
                if ( ! empty( $raw->blood_group ) )          $upd_data['blood_group'] = $raw->blood_group;
                if ( ! empty( $raw->gender ) )               $upd_data['gender'] = $raw->gender;
                if ( ! empty( $raw->institution ) )          $upd_data['institution'] = $raw->institution;
                if ( ! empty( $raw->present_district ) )     $upd_data['present_district'] = $raw->present_district;
                if ( ! empty( $raw->permanent_district ) )   $upd_data['permanent_district'] = $raw->permanent_district;
                if ( ! empty( $raw->present_address ) )      $upd_data['present_address'] = $raw->present_address;
                if ( ! empty( $raw->permanent_address ) )    $upd_data['permanent_address'] = $raw->permanent_address;
                if ( ! empty( $raw->upazila ) )              $upd_data['upazila'] = $raw->upazila;
                if ( ! empty( $raw->present_upazila ) )      $upd_data['present_upazila'] = $raw->present_upazila;
                if ( ! empty( $upd_data ) ) {
                    $wpdb->update( $bm_member_table, $upd_data, array( 'id' => $target_member_id ) );
                }
                // Update rawdata status
                $wpdb->update( $rawdata_table, array( 'status' => 'approved' ), array( 'id' => $app->rawdata_id ), array( '%s' ), array( '%d' ) );
            } else {
                // Generate next member ID
                $max_mid = (int) $wpdb->get_var( "SELECT COALESCE(MAX(id), 0) FROM $bm_member_table" );
                $next_mid = $max_mid + 1;
                $member_number = function_exists( 'batchmate_generate_member_id' )
                    ? batchmate_generate_member_id( $next_mid )
                    : ( 'BM-' . str_pad( (string) $next_mid, 5, '0', STR_PAD_LEFT ) );

                while ( $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $bm_member_table WHERE member_number = %s", $member_number ) ) ) {
                    $next_mid++;
                    $member_number = function_exists( 'batchmate_generate_member_id' )
                        ? batchmate_generate_member_id( $next_mid )
                        : ( 'BM-' . str_pad( (string) $next_mid, 5, '0', STR_PAD_LEFT ) );
                }

                // Create WordPress User
                $wp_user_id = 0;
                $gen_password = wp_generate_password( 12, false );
                $username = sanitize_user( strtolower( $member_number ), true );

                $email_to_use = ! empty( $raw->email ) ? $raw->email : ( function_exists( 'batchmate_generate_member_email' ) ? batchmate_generate_member_email( $member_number ) : '' );

                if ( ! empty( $email_to_use ) ) {
                    $existing_uid = email_exists( $email_to_use );
                    if ( $existing_uid ) {
                        $wp_user_id = $existing_uid;
                    } else {
                        if ( username_exists( $username ) ) {
                            $username = $username . '_' . wp_rand( 100, 999 );
                        }
                        $created_uid = wp_create_user( $username, $gen_password, $email_to_use );
                        if ( ! is_wp_error( $created_uid ) ) {
                            $wp_user_id = $created_uid;
                            $user = new WP_User( $wp_user_id );
                            $user->set_role( 'batchmate_member' );
                            wp_update_user( array(
                                'ID'           => $wp_user_id,
                                'display_name' => $raw->name,
                                'first_name'   => explode( ' ', $raw->name, 2 )[0],
                                'last_name'    => isset( explode( ' ', $raw->name, 2 )[1] ) ? explode( ' ', $raw->name, 2 )[1] : '',
                            ) );
                            if ( ! empty( $raw->photo_url ) ) {
                                update_user_meta( $wp_user_id, 'batchmate_member_photo', $raw->photo_url );
                            }
                            $new_user_login_info = array(
                                'member_number' => $member_number,
                                'username'      => $username,
                                'password'      => $gen_password,
                            );
                        }
                    }
                }

                // Insert into batchmate_member
                $unique_app_id = -intval( $app->rawdata_id );
                while ( $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $bm_member_table WHERE application_id = %d", $unique_app_id ) ) ) {
                    $unique_app_id--;
                }

                $new_member_insert = array(
                    'application_id'    => $unique_app_id,
                    'wp_user_id'        => $wp_user_id,
                    'member_number'     => $member_number,
                    'name'              => $raw->name,
                    'email'             => $email_to_use,
                    'cell_number'       => $raw->cell_number,
                    'cell_show'         => 1,
                    'blood_group'       => $raw->blood_group,
                    'student_id'        => ! empty( $raw->student_id ) ? $raw->student_id : $member_number,
                    'photo_url'         => $raw->photo_url,
                    'joining_date'      => current_time( 'mysql' ),
                );
                if ( ! empty( $raw->gender ) )             $new_member_insert['gender'] = $raw->gender;
                if ( ! empty( $raw->date_of_birth ) )      $new_member_insert['date_of_birth'] = $raw->date_of_birth;
                if ( ! empty( $raw->institution ) )        $new_member_insert['institution'] = $raw->institution;
                if ( ! empty( $raw->board ) )              $new_member_insert['board'] = $raw->board;
                if ( ! empty( $raw->roll_no ) )            $new_member_insert['roll_no'] = $raw->roll_no;
                if ( ! empty( $raw->registration_no ) )    $new_member_insert['registration_no'] = $raw->registration_no;
                if ( ! empty( $raw->edu_group ) )          $new_member_insert['edu_group'] = $raw->edu_group;
                if ( ! empty( $raw->present_address ) )    $new_member_insert['present_address'] = $raw->present_address;
                if ( ! empty( $raw->permanent_address ) )  $new_member_insert['permanent_address'] = $raw->permanent_address;
                if ( ! empty( $raw->present_district ) )   $new_member_insert['present_district'] = $raw->present_district;
                if ( ! empty( $raw->present_country ) )    $new_member_insert['present_country'] = $raw->present_country;
                if ( ! empty( $raw->present_upazila ) )    $new_member_insert['present_upazila'] = $raw->present_upazila;
                if ( ! empty( $raw->permanent_district ) ) $new_member_insert['permanent_district'] = $raw->permanent_district;
                if ( ! empty( $raw->permanent_country ) )  $new_member_insert['permanent_country'] = $raw->permanent_country;
                if ( ! empty( $raw->upazila ) )            $new_member_insert['upazila'] = $raw->upazila;
                if ( ! empty( $raw->name_bn ) )            $new_member_insert['name_bn'] = $raw->name_bn;
                if ( ! empty( $raw->religion ) )           $new_member_insert['religion'] = $raw->religion;
                if ( ! empty( $raw->land_phone_number ) )  $new_member_insert['land_phone_number'] = $raw->land_phone_number;
                if ( ! empty( $raw->organization ) )       $new_member_insert['organization'] = $raw->organization;
                if ( ! empty( $raw->employment_position ) )$new_member_insert['employment_position'] = $raw->employment_position;
                if ( ! empty( $raw->industry ) )           $new_member_insert['industry'] = $raw->industry;
                if ( ! empty( $raw->work_experience ) )    $new_member_insert['work_experience'] = $raw->work_experience;
                if ( ! empty( $raw->employment_address ) ) $new_member_insert['employment_address'] = $raw->employment_address;
                if ( ! empty( $raw->previous_organizations ) ) $new_member_insert['previous_organizations'] = $raw->previous_organizations;
                if ( ! empty( $raw->about_me ) )           $new_member_insert['about_me'] = $raw->about_me;
                if ( ! empty( $raw->skills_json ) )        $new_member_insert['skills_json'] = $raw->skills_json;
                if ( ! empty( $raw->facebook_url ) )       $new_member_insert['facebook_url'] = $raw->facebook_url;
                if ( ! empty( $raw->linkedin_url ) )       $new_member_insert['linkedin_url'] = $raw->linkedin_url;
                if ( ! empty( $raw->x_url ) )              $new_member_insert['x_url'] = $raw->x_url;
                if ( ! empty( $raw->website ) )            $new_member_insert['website'] = $raw->website;

                $r_mem = $wpdb->insert( $bm_member_table, $new_member_insert );
                if ( $r_mem === false ) {
                    throw new Exception( 'Could not create new member record: ' . $wpdb->last_error );
                }
                $target_member_id = (int) $wpdb->insert_id;

                // Update rawdata status
                $wpdb->update( $rawdata_table, array( 'status' => 'approved' ), array( 'id' => $app->rawdata_id ), array( '%s' ), array( '%d' ) );
            }

            // Update application member_id
            $wpdb->update( $app_table, array( 'member_id' => $target_member_id ), array( 'id' => $app_id ), array( '%d' ), array( '%d' ) );
        }

        // Calculate next serial number for this event
        $next_serial = (int) $wpdb->get_var( $wpdb->prepare(
            "SELECT COALESCE(MAX(serial_no), 0) + 1 FROM $member_table WHERE event_id = %d",
            $app->event_id
        ) );

        // Insert into event_members
        $r1 = $wpdb->insert( $member_table, array(
            'event_id'       => $app->event_id,
            'member_id'      => $target_member_id,
            'application_id' => $app->id,
            'rawdata_id'     => $app->rawdata_id,
            'serial_no'      => $next_serial,
            'approved_by'    => get_current_user_id(),
        ), array( '%d', '%d', '%d', '%d', '%d', '%d' ) );

        if ( $r1 === false ) throw new Exception( 'Could not insert into event members.' );

        // Update application status
        $r2 = $wpdb->update( $app_table, array( 'status' => 'approved' ), array( 'id' => $app_id ), array( '%s' ), array( '%d' ) );
        if ( $r2 === false ) throw new Exception( 'Could not update application status.' );

        $wpdb->query( 'COMMIT' );

        // â”€â”€ Send approval email (includes event card attachment & login credentials if converted) â”€â”€
        BatchMate_send_event_confirmation_email( $app_id, $next_serial, $new_user_login_info );

        $resp_msg = 'Application approved and member registered.';
        if ( $new_user_login_info ) {
            $resp_msg .= ' Non-member converted to Member (' . $new_user_login_info['member_number'] . ') with user account.';
        }
        wp_send_json_success( $resp_msg );
    } catch ( Exception $e ) {
        $wpdb->query( 'ROLLBACK' );
        wp_send_json_error( $e->getMessage() );
    }
}
add_action( 'wp_ajax_BatchMate_approve_event_application', 'BatchMate_approve_event_application_ajax' );
add_action( 'wp_ajax_puaa_approve_event_application',       'BatchMate_approve_event_application_ajax' );


// ─────────────────────────────────────────────────────────────────────
// 6. REJECT EVENT APPLICATION
// ─────────────────────────────────────────────────────────────────────
function BatchMate_reject_event_application_ajax() {
    batchmate_event_check_admin();
    global $wpdb;

    $app_id = intval( $_POST['app_id'] ?? 0 );
    if ( ! $app_id ) wp_send_json_error( 'Invalid application ID.' );

    $result = $wpdb->update(
        $wpdb->prefix . 'batchmate_event_application',
        array( 'status' => 'rejected' ),
        array( 'id'     => $app_id ),
        array( '%s' ), array( '%d' )
    );

    if ( $result === false ) wp_send_json_error( 'Database error.' );
    wp_send_json_success( 'Application rejected.' );
}
add_action( 'wp_ajax_BatchMate_reject_event_application', 'BatchMate_reject_event_application_ajax' );
add_action( 'wp_ajax_puaa_reject_event_application',       'BatchMate_reject_event_application_ajax' );


// ─────────────────────────────────────────────────────────────────────
// 7. GET REGISTERED MEMBERS (DataTable — Tab 1)
// ─────────────────────────────────────────────────────────────────────
function BatchMate_get_event_members_ajax() {
    batchmate_event_check_admin();
    global $wpdb;

    $event_id = intval( $_POST['event_id'] ?? 0 );
    $where    = $event_id > 0 ? $wpdb->prepare( "WHERE em.event_id = %d", $event_id ) : '';

    $rows = $wpdb->get_results( "
        SELECT
            em.id,
            em.event_id,
            em.member_id,
            em.rawdata_id,
            em.serial_no,
            ev.event_name,
            ev.card_template_url,
            ev.has_tshirt,
            ev.has_saree,
            IF(em.member_id > 0, m.member_number, 'Non-Member') AS member_number,
            IF(em.member_id > 0, m.name, r.name) AS name,
            IF(em.member_id > 0, m.photo_url, r.photo_url) AS photo_url,
            IF(em.member_id > 0, COALESCE(NULLIF(m.permanent_district, ''), NULLIF(m.present_district, ''), ''), COALESCE(NULLIF(r.permanent_district, ''), NULLIF(r.present_district, ''), '')) AS dept_name,
            IF(em.member_id > 0, COALESCE(NULLIF(m.permanent_district, ''), NULLIF(m.present_district, ''), ''), COALESCE(NULLIF(r.permanent_district, ''), NULLIF(r.present_district, ''), '')) AS district,
            '' AS program_name,
            '' AS batch,
            IF(em.member_id > 0, m.cell_number, r.cell_number) AS cell_number,
            IF(em.member_id > 0, m.employment_position, r.employment_position) AS employment_position,
            COALESCE(ea.amount, 0) AS amount,
            ea.options_json,
            ea.tshirt_size,
            ea.id AS app_id,
            ea.payment_method,
            ea.payment_details
        FROM {$wpdb->prefix}batchmate_event_members em
        LEFT JOIN {$wpdb->prefix}batchmate_event   ev   ON em.event_id  = ev.id
        LEFT JOIN {$wpdb->prefix}batchmate_member  m    ON em.member_id = m.id
        LEFT JOIN {$wpdb->prefix}batchmate_rawdata r ON em.rawdata_id = r.id
        LEFT JOIN {$wpdb->prefix}batchmate_event_application ea ON ea.id = em.application_id
        $where
        GROUP BY em.id
        ORDER BY em.serial_no ASC
    " );

    wp_send_json( array( 'data' => $rows ? $rows : [] ) );
}
add_action( 'wp_ajax_BatchMate_get_event_members', 'BatchMate_get_event_members_ajax' );
add_action( 'wp_ajax_puaa_get_event_members',       'BatchMate_get_event_members_ajax' );


// ─────────────────────────────────────────────────────────────────────
// 8. SEARCH MEMBER (Frontend shortcode — public)
// ─────────────────────────────────────────────────────────────────────
function BatchMate_search_member_for_event_ajax() {
    if ( ! empty( $_POST['_wpnonce'] ) ) {
        check_ajax_referer( 'batchmate_event_public_nonce', '_wpnonce', false );
    }
    global $wpdb;

    $query = sanitize_text_field( $_POST['query'] ?? '' );
    if ( strlen( $query ) < 3 ) wp_send_json_error( 'Search query too short.' );

    $like       = '%' . $wpdb->esc_like( $query ) . '%';
    $numeric_id = intval( $query );

    // Fixed: removed broken prog/dept join — use m.institution directly
    $members = $wpdb->get_results( $wpdb->prepare(
        "SELECT m.id, m.member_number, m.name, m.photo_url, m.cell_number, m.student_id,
                m.gender, m.email, m.institution, m.blood_group, '' AS batch
         FROM {$wpdb->prefix}batchmate_member m
         WHERE m.id = %d
            OR m.member_number = %s
            OR m.name LIKE %s
            OR m.student_id = %s
            OR m.cell_number = %s
         ORDER BY m.member_number ASC
         LIMIT 20",
        $numeric_id, $query, $like, $query, $query
    ) );

    if ( $wpdb->last_error ) {
        wp_send_json_error( 'db_error' );
    }

    if ( empty( $members ) ) {
        // For allow_non_members=2 (Direct Member mode): check if email/mobile already exists
        $event_id  = intval( $_POST['event_id'] ?? 0 );
        $dup_check = null;
        if ( $event_id && strlen( $query ) >= 5 ) {
            // Check in rawdata (non-member applications) by email or cell
            $dup_check = $wpdb->get_row( $wpdb->prepare(
                "SELECT r.name, r.email, r.cell_number
                 FROM {$wpdb->prefix}batchmate_rawdata r
                 INNER JOIN {$wpdb->prefix}batchmate_event_application ea ON ea.rawdata_id = r.id
                 WHERE ea.event_id = %d AND (r.email = %s OR r.cell_number = %s)
                 LIMIT 1",
                $event_id, $query, $query
            ) );
        }
        if ( $dup_check ) {
            wp_send_json_error( array( 'code' => 'duplicate', 'name' => $dup_check->name ) );
        }
        wp_send_json_error( 'not_found' );
    }

    // For each member check if already applied for this event
    $event_id = intval( $_POST['event_id'] ?? 0 );
    $result_members = array();

    foreach ( $members as $member ) {
        $already    = false;
        $app_status = '';
        if ( $event_id ) {
            $app = $wpdb->get_row( $wpdb->prepare(
                "SELECT status FROM {$wpdb->prefix}batchmate_event_application WHERE event_id = %d AND member_id = %d LIMIT 1",
                $event_id, $member->id
            ) );
            if ( $app ) {
                $already    = true;
                $app_status = $app->status;
            }
        }
        $result_members[] = array(
            'member'     => $member,
            'already'    => $already,
            'app_status' => $app_status,
        );
    }

    // If only one result, send as before (backward compat); if multiple, send as list
    wp_send_json_success( array(
        'members' => $result_members,
        'count'   => count( $result_members ),
    ) );
}
add_action( 'wp_ajax_BatchMate_search_member_for_event',        'BatchMate_search_member_for_event_ajax' );
add_action( 'wp_ajax_nopriv_BatchMate_search_member_for_event', 'BatchMate_search_member_for_event_ajax' );
add_action( 'wp_ajax_puaa_search_member_for_event',              'BatchMate_search_member_for_event_ajax' );
add_action( 'wp_ajax_nopriv_puaa_search_member_for_event',       'BatchMate_search_member_for_event_ajax' );


// ─────────────────────────────────────────────────────────────────────
// 9. SUBMIT EVENT APPLICATION (Frontend shortcode — public)
// ─────────────────────────────────────────────────────────────────────
function BatchMate_submit_event_application_ajax() {
    if ( ! check_ajax_referer( 'batchmate_event_public_nonce', '_wpnonce', false ) ) {
        wp_send_json_error( 'Your security session has expired. Please refresh the page and try submitting again.' );
    }
    global $wpdb;

    // Ensure all required columns exist on rawdata/application tables (migration guard)
    if ( function_exists( 'batchmate_ensure_location_columns' ) ) {
        batchmate_ensure_location_columns();
    }

    $event_id        = intval( $_POST['event_id']        ?? 0 );
    $member_id       = intval( $_POST['member_id']       ?? 0 );
    $is_guest        = intval( $_POST['is_guest']        ?? 0 );
    $payment_method  = sanitize_text_field( $_POST['payment_method']  ?? '' );
    $payment_details = sanitize_text_field( $_POST['payment_details'] ?? '' );
    $amount          = floatval( $_POST['amount'] ?? 0 );
    $tshirt_size     = sanitize_text_field( $_POST['tshirt_size'] ?? '' );
    $options_raw     = $_POST['options_json'] ?? '';
    $options_json    = is_string( $options_raw ) ? sanitize_textarea_field( $options_raw ) : '';

    if ( ! $event_id ) wp_send_json_error( 'Invalid event ID.' );
    if ( ! $member_id && ! $is_guest ) wp_send_json_error( 'Invalid member data.' );
    if ( empty( $payment_method ) || empty( $payment_details ) ) {
        wp_send_json_error( 'Payment method and transaction ID are required.' );
    }

    $event = $wpdb->get_row( $wpdb->prepare(
        "SELECT id, event_name, allow_non_members, has_tshirt, has_saree, cc_email FROM {$wpdb->prefix}batchmate_event WHERE id = %d AND is_active = 1", $event_id
    ) );
    if ( ! $event ) wp_send_json_error( 'This event is no longer accepting applications.' );

    if ( $is_guest && ! $event->allow_non_members ) {
        wp_send_json_error( 'This event does not allow non-member registrations.' );
    }

    // Validate tshirt_size if event has tshirt enabled
    $valid_tshirt_sizes = array( 'S', 'M', 'X', 'L', 'XL', 'XXL', 'XXXL' );
    if ( ! empty( $event->has_saree ) ) { $valid_tshirt_sizes[] = 'Saree'; }
    if ( $event->has_tshirt && ! in_array( $tshirt_size, $valid_tshirt_sizes ) ) {
        wp_send_json_error( 'Please select a valid T-Shirt size or Saree.' );
    }

    $rawdata_id = 0;
    if ( $is_guest ) {
        $g_name   = sanitize_text_field( $_POST['guest_name']   ?? '' );
        $g_email  = sanitize_email(      $_POST['guest_email']  ?? '' );
        $g_cell   = sanitize_text_field( $_POST['guest_cell']   ?? '' );
        $g_gender = sanitize_text_field( $_POST['guest_gender'] ?? 'Male' );
        $g_blood  = sanitize_text_field( $_POST['guest_blood']  ?? '' );
        $g_inst   = sanitize_text_field( $_POST['guest_institution'] ?? '' );
        $g_roll   = sanitize_text_field( $_POST['guest_roll']   ?? '' );
        $g_regno  = sanitize_text_field( $_POST['guest_regno']  ?? '' );
        $g_board  = sanitize_text_field( $_POST['guest_board']  ?? '' );
        $g_group     = sanitize_text_field( $_POST['guest_group']     ?? '' );
        $g_dob       = sanitize_text_field( $_POST['guest_dob']       ?? '' );
        $g_pres_addr = sanitize_text_field( $_POST['guest_present_address']   ?? '' );
        $g_perm_addr = sanitize_text_field( $_POST['guest_permanent_address'] ?? '' );
        $g_pres_dist = sanitize_text_field( $_POST['guest_present_district']  ?? '' );
        $g_perm_dist = sanitize_text_field( $_POST['guest_permanent_district'] ?? '' );
        $g_pres_country = sanitize_text_field( $_POST['guest_present_country'] ?? 'Bangladesh' );
        $g_pres_upz     = sanitize_text_field( $_POST['guest_present_upazila'] ?? '' );
        $g_perm_country = sanitize_text_field( $_POST['guest_permanent_country'] ?? 'Bangladesh' );
        $g_perm_upz     = sanitize_text_field( $_POST['guest_upazila'] ?? '' );
        $g_name_bn   = sanitize_text_field( $_POST['guest_name_bn'] ?? '' );
        $g_religion  = sanitize_text_field( $_POST['guest_religion'] ?? '' );
        $g_land      = sanitize_text_field( $_POST['guest_land_phone'] ?? '' );
        $g_org       = sanitize_text_field( $_POST['guest_organization'] ?? '' );
        $g_pos       = sanitize_text_field( $_POST['guest_position'] ?? '' );
        $g_ind       = sanitize_text_field( $_POST['guest_industry'] ?? '' );
        $g_exp       = sanitize_text_field( $_POST['guest_work_experience'] ?? '' );
        $g_emp_addr  = sanitize_text_field( $_POST['guest_employment_address'] ?? '' );
        $g_prev_org  = sanitize_textarea_field( $_POST['guest_previous_organizations'] ?? '' );
        $g_about     = sanitize_textarea_field( $_POST['guest_about_me'] ?? '' );
        $g_skills    = sanitize_textarea_field( $_POST['guest_skills'] ?? '' );
        $g_fb        = esc_url_raw( $_POST['guest_facebook'] ?? '' );
        $g_li        = esc_url_raw( $_POST['guest_linkedin'] ?? '' );
        $g_x         = esc_url_raw( $_POST['guest_x'] ?? '' );
        $g_web       = esc_url_raw( $_POST['guest_website'] ?? '' );

        if ( empty( $g_name ) || empty( $g_cell ) ) {
            wp_send_json_error( 'Name and Mobile are required.' );
        }

        // Handle photo upload — optional, crop to 400x400
        $photo_url = '';
        if ( ! empty( $_FILES['guest_photo']['name'] ) ) {
            require_once( ABSPATH . 'wp-admin/includes/file.php' );
            // Temporarily lift WordPress upload size restriction
            add_filter( 'upload_size_limit', 'BatchMate_override_upload_size_limit' );
            $upload = wp_handle_upload( $_FILES['guest_photo'], array( 'test_form' => false ) );
            remove_filter( 'upload_size_limit', 'BatchMate_override_upload_size_limit' );

            if ( isset( $upload['file'] ) ) {
                // Center-crop to 400x400
                if ( function_exists('BatchMate_crop_photo_400') ) {
                    BatchMate_crop_photo_400( $upload['file'] );
                } else {
                    BatchMate_crop_photo_200( $upload['file'] );
                }
                $photo_url = $upload['url'];
                
                // Add to Media Library so it shows up in upload.php
                require_once( ABSPATH . 'wp-admin/includes/image.php' );
                $attachment = array(
                    'post_mime_type' => $upload['type'],
                    'post_title'     => sanitize_file_name( basename( $upload['file'] ) ),
                    'post_content'   => '',
                    'post_status'    => 'inherit'
                );
                $attach_id = wp_insert_attachment( $attachment, $upload['file'] );
                if ( ! is_wp_error( $attach_id ) ) {
                    $attach_data = wp_generate_attachment_metadata( $attach_id, $upload['file'] );
                    wp_update_attachment_metadata( $attach_id, $attach_data );
                }
            } else {
                // Upload failed — try to give a friendly error (not a file-size rejection message)
                $err_msg = isset( $upload['error'] ) ? $upload['error'] : 'Unknown error';
                // Strip the byte-limit details from the error so it is less confusing
                if ( strpos( $err_msg, 'exceeds' ) !== false ) {
                    $err_msg = 'Photo file is too large for the server. Please reduce the size or contact the administrator.';
                }
            wp_send_json_error( 'Error uploading photo: ' . $err_msg );
            }
        } // end photo upload

        // Insert into rawdata
        $rawdata_arr = array(
            'name'            => $g_name,
            'student_id'      => $g_roll ?: '',
            'email'           => $g_email,
            'cell_number'     => $g_cell,
            'blood_group'     => $g_blood,
            'photo_url'       => $photo_url,
            'gender'          => $g_gender,
            'institution'     => $g_inst,
            'status'          => 'pending',
            'submission_date' => current_time( 'mysql' ),
        );
        if ( ! empty( $g_board ) )     $rawdata_arr['board'] = $g_board;
        if ( ! empty( $g_roll ) )      $rawdata_arr['roll_no'] = $g_roll;
        if ( ! empty( $g_regno ) )     $rawdata_arr['registration_no'] = $g_regno;
        if ( ! empty( $g_group ) )     $rawdata_arr['edu_group'] = $g_group;
        if ( ! empty( $g_dob ) )       $rawdata_arr['date_of_birth'] = $g_dob;
        if ( ! empty( $g_pres_addr ) ) $rawdata_arr['present_address'] = $g_pres_addr;
        if ( ! empty( $g_perm_addr ) ) $rawdata_arr['permanent_address'] = $g_perm_addr;
        if ( ! empty( $g_pres_dist ) ) $rawdata_arr['present_district'] = $g_pres_dist;
        if ( ! empty( $g_pres_country ) ) $rawdata_arr['present_country'] = $g_pres_country;
        if ( ! empty( $g_pres_upz ) )     $rawdata_arr['present_upazila'] = $g_pres_upz;
        if ( ! empty( $g_perm_dist ) ) $rawdata_arr['permanent_district'] = $g_perm_dist;
        if ( ! empty( $g_perm_country ) ) $rawdata_arr['permanent_country'] = $g_perm_country;
        if ( ! empty( $g_perm_upz ) )     $rawdata_arr['upazila'] = $g_perm_upz;
        if ( ! empty( $g_name_bn ) )   $rawdata_arr['name_bn'] = $g_name_bn;
        if ( ! empty( $g_religion ) )  $rawdata_arr['religion'] = $g_religion;
        if ( ! empty( $g_land ) )      $rawdata_arr['land_phone_number'] = $g_land;
        if ( ! empty( $g_org ) )       $rawdata_arr['organization'] = $g_org;
        if ( ! empty( $g_pos ) )       $rawdata_arr['employment_position'] = $g_pos;
        if ( ! empty( $g_ind ) )       $rawdata_arr['industry'] = $g_ind;
        if ( ! empty( $g_exp ) )       $rawdata_arr['work_experience'] = $g_exp;
        if ( ! empty( $g_emp_addr ) )  $rawdata_arr['employment_address'] = $g_emp_addr;
        if ( ! empty( $g_prev_org ) )  $rawdata_arr['previous_organizations'] = $g_prev_org;
        if ( ! empty( $g_about ) )     $rawdata_arr['about_me'] = $g_about;
        if ( ! empty( $g_skills ) )    $rawdata_arr['skills_json'] = $g_skills;
        if ( ! empty( $g_fb ) )        $rawdata_arr['facebook_url'] = $g_fb;
        if ( ! empty( $g_li ) )        $rawdata_arr['linkedin_url'] = $g_li;
        if ( ! empty( $g_x ) )         $rawdata_arr['x_url'] = $g_x;
        if ( ! empty( $g_web ) )       $rawdata_arr['website'] = $g_web;

        // Defensive column filtering: ensure we only insert columns that actually exist in the DB table
        $existing_raw_cols = $wpdb->get_col( "SHOW COLUMNS FROM {$wpdb->prefix}batchmate_rawdata" );
        if ( ! empty( $existing_raw_cols ) ) {
            $rawdata_arr = array_intersect_key( $rawdata_arr, array_flip( $existing_raw_cols ) );
        }

        $result = $wpdb->insert( $wpdb->prefix . 'batchmate_rawdata', $rawdata_arr );

        if ( $result === false ) {
            wp_send_json_error( 'Failed to save non-member information: ' . ( $wpdb->last_error ?: 'Database insert error' ) );
        }
        $rawdata_id = $wpdb->insert_id;
        $member_id = 0;
    }

    if ( $member_id > 0 ) {
        $exists = $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(id) FROM {$wpdb->prefix}batchmate_event_application WHERE event_id = %d AND member_id = %d",
            $event_id, $member_id
        ) );
        if ( $exists > 0 ) wp_send_json_error( 'You have already applied for this event.' );
    } else if ( $rawdata_id > 0 ) {
        // Can a guest apply twice? For safety, we just created the rawdata, so it's a new entry anyway.
    }

    $result = $wpdb->insert(
        $wpdb->prefix . 'batchmate_event_application',
        array(
            'event_id'        => $event_id,
            'member_id'       => $member_id,
            'rawdata_id'      => $rawdata_id,
            'payment_method'  => $payment_method,
            'payment_details' => $payment_details,
            'amount'          => $amount,
            'options_json'    => $options_json ?: null,
            'tshirt_size'     => $tshirt_size,
            'status'          => 'pending',
        ),
        array( '%d', '%d', '%d', '%s', '%s', '%f', '%s', '%s', '%s' )
    );

    if ( $result === false ) wp_send_json_error( 'Database error. Please try again. ' . $wpdb->last_error );

    // Fetch member name for the email
    if ( $member_id > 0 ) {
        $member = $wpdb->get_row( $wpdb->prepare(
            "SELECT name FROM {$wpdb->prefix}batchmate_member WHERE id = %d", $member_id
        ) );
        $member_name = $member ? $member->name : 'A member';
    } else {
        // Guest — name was saved in rawdata
        $guest = $wpdb->get_row( $wpdb->prepare(
            "SELECT name FROM {$wpdb->prefix}batchmate_rawdata WHERE id = %d", $rawdata_id
        ) );
        $member_name = $guest ? $guest->name : 'A non-member';
    }

    // Send email notification to admins
    $settings = get_option('batchmate_settings', array());
    $to = !empty($settings['event_mail_to']) ? $settings['event_mail_to'] : 'info@batchmate.org';
    $subject = 'New Event Application: ' . $member_name . ' — ' . $event->event_name;

    $tshirt_row = '';
    if ( $event->has_tshirt ) {
        $tshirt_display = ( empty( $tshirt_size ) || $tshirt_size === 'NA' )
            ? '<span style="color:#888;font-style:italic;">N/A</span>'
            : '<strong style="color:#d97706;">' . esc_html( $tshirt_size ) . '</strong>';
        $item_label = ( $tshirt_size === 'Saree' ) ? 'Item / Gift' : 'T-Shirt Size';
        $tshirt_row = '<tr><td width="40%"><strong>' . $item_label . '</strong></td><td>' . $tshirt_display . '</td></tr>';
    }

    $body  = '<html><body>';
    $body .= '<h2 style="color:#1a3a6b;">New Event Application Received</h2>';
    $body .= '<table border="1" cellpadding="10" cellspacing="0" style="border-collapse:collapse;width:100%;max-width:600px;">';
    $body .= '<tr><td colspan="2" style="background:#1a3a6b;color:#fff;font-size:15px;font-weight:bold;">' . esc_html( $event->event_name ) . '</td></tr>';
    $body .= '<tr><td width="40%"><strong>Applicant Name</strong></td><td>' . esc_html( $member_name ) . '</td></tr>';
    $body .= '<tr><td><strong>Payment Method</strong></td><td>' . esc_html( $payment_method ) . '</td></tr>';
    $body .= '<tr><td><strong>Transaction / Ref ID</strong></td><td>' . esc_html( $payment_details ) . '</td></tr>';
    $body .= '<tr><td><strong>Amount (BDT)</strong></td><td>&#2547; ' . number_format( $amount, 2 ) . '</td></tr>';
    $body .= $tshirt_row;
    $body .= '</table>';
    $body .= '<p style="margin-top:20px;"><a href="' . admin_url('admin.php?page=batchmate_events#tab-applications') . '" style="background:#2271b1;color:#fff;padding:10px 18px;text-decoration:none;border-radius:6px;display:inline-block;">Review in Events Dashboard</a></p>';
    $body .= '</body></html>';

    $headers = array('Content-Type: text/html; charset=UTF-8');
    if ( ! empty( $event->cc_email ) && function_exists( 'batchmate_get_cc_headers' ) ) {
        $headers = array_merge( $headers, batchmate_get_cc_headers( $event->cc_email ) );
    }
    wp_mail( $to, $subject, $body, $headers );

    wp_send_json_success( 'Your application has been submitted successfully. Please wait for approval.' );
}
add_action( 'wp_ajax_BatchMate_submit_event_application',        'BatchMate_submit_event_application_ajax' );
add_action( 'wp_ajax_nopriv_BatchMate_submit_event_application', 'BatchMate_submit_event_application_ajax' );
add_action( 'wp_ajax_puaa_submit_event_application',              'BatchMate_submit_event_application_ajax' );
add_action( 'wp_ajax_nopriv_puaa_submit_event_application',       'BatchMate_submit_event_application_ajax' );


// ─────────────────────────────────────────────────────────────────────
// 10. EDIT EVENT APPLICATION (Admin only)
// ─────────────────────────────────────────────────────────────────────
function BatchMate_edit_event_application_ajax() {
    batchmate_event_check_admin();
    global $wpdb;

    $app_id          = intval( $_POST['app_id']          ?? 0 );
    $payment_method  = sanitize_text_field( $_POST['payment_method']  ?? '' );
    $payment_details = sanitize_text_field( $_POST['payment_details'] ?? '' );
    $amount          = floatval( $_POST['amount'] ?? 0 );
    $tshirt_size     = sanitize_text_field( $_POST['tshirt_size'] ?? '' );
    $options_raw     = isset( $_POST['options_json'] ) ? $_POST['options_json'] : '__NOT_SENT__';

    if ( ! $app_id ) wp_send_json_error( 'Invalid application ID.' );

    // Fetch existing application data so we can preserve fields not being changed
    $existing = $wpdb->get_row( $wpdb->prepare(
        "SELECT options_json, tshirt_size FROM {$wpdb->prefix}batchmate_event_application WHERE id = %d",
        $app_id
    ) );
    if ( ! $existing ) wp_send_json_error( 'Application not found.' );

    // --- options_json: only update if a valid JSON array was explicitly sent ---
    $new_options_json = $existing->options_json; // keep existing by default
    if ( $options_raw !== '__NOT_SENT__' ) {
        $decoded = null;
        if ( is_string( $options_raw ) && $options_raw !== '' && $options_raw !== 'null' ) {
            $decoded = json_decode( stripslashes( $options_raw ), true );
        }
        if ( is_array( $decoded ) ) {
            // Valid array — save it (even if empty, to allow clearing guests)
            $new_options_json = wp_json_encode( $decoded );
        } elseif ( $options_raw === 'null' || $options_raw === '' ) {
            // Explicitly null/empty sent — preserve existing
            $new_options_json = $existing->options_json;
        }
    }

    // --- tshirt_size: if empty string sent and existing had a value, keep existing ---
    if ( $tshirt_size === '' && ! empty( $existing->tshirt_size ) ) {
        $tshirt_size = $existing->tshirt_size;
    }

    $update_data   = array(
        'payment_method'  => $payment_method,
        'payment_details' => $payment_details,
        'amount'          => $amount,
        'tshirt_size'     => $tshirt_size,
        'options_json'    => $new_options_json ?: null,
    );
    $update_format = array( '%s', '%s', '%f', '%s', '%s' );

    $result = $wpdb->update(
        $wpdb->prefix . 'batchmate_event_application',
        $update_data,
        array( 'id' => $app_id ),
        $update_format,
        array( '%d' )
    );

    if ( $result === false ) wp_send_json_error( 'Database error. Please try again.' );
    wp_send_json_success( 'Application updated successfully.' );
}
add_action( 'wp_ajax_BatchMate_edit_event_application', 'BatchMate_edit_event_application_ajax' );
add_action( 'wp_ajax_puaa_edit_event_application',       'BatchMate_edit_event_application_ajax' );


// ─────────────────────────────────────────────────────────────────────