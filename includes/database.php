<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * =====================================================================
 * BatchMate Database Version Manager
 * =====================================================================
 *
 * Ã Â¦â€¢Ã Â¦Â¿Ã Â¦Â­Ã Â¦Â¾Ã Â¦Â¬Ã Â§â€¡ Ã Â¦â€¢Ã Â¦Â¾Ã Â¦Å“ Ã Â¦â€¢Ã Â¦Â°Ã Â§â€¡:
 *  - BATCHMATE_DB_VERSION Ã Â¦â€¢Ã Â¦Â¨Ã Â¦Â¸Ã Â§ÂÃ Â¦Å¸Ã Â§ÂÃ Â¦Â¯Ã Â¦Â¾Ã Â¦Â¨Ã Â§ÂÃ Â¦Å¸ Ã Â¦Â¹Ã Â¦Â²Ã Â§â€¹ Ã Â¦Â¬Ã Â¦Â°Ã Â§ÂÃ Â¦Â¤Ã Â¦Â®Ã Â¦Â¾Ã Â¦Â¨ Ã Â¦â€¢Ã Â§â€¹Ã Â¦Â¡Ã Â§â€¡Ã Â¦Â° DB Ã Â¦Â­Ã Â¦Â¾Ã Â¦Â°Ã Â§ÂÃ Â¦Â¸Ã Â¦Â¨Ã Â¥Â¤
 *  - WordPress options Ã Â¦Å¸Ã Â§â€¡Ã Â¦Â¬Ã Â¦Â¿Ã Â¦Â²Ã Â§â€¡ 'batchmate_db_version' Ã Â¦â€¢Ã Â¦Â¿ Ã Â¦Â¤Ã Â§â€¡ Ã Â¦Â¸Ã Â¦â€šÃ Â¦Â°Ã Â¦â€¢Ã Â§ÂÃ Â¦Â·Ã Â¦Â¿Ã Â¦Â¤
 *    Ã Â¦Â­Ã Â¦Â¾Ã Â¦Â°Ã Â§ÂÃ Â¦Â¸Ã Â¦Â¨Ã Â§â€¡Ã Â¦Â° Ã Â¦Â¸Ã Â¦Â¾Ã Â¦Â¥Ã Â§â€¡ Ã Â¦Â¤Ã Â§ÂÃ Â¦Â²Ã Â¦Â¨Ã Â¦Â¾ Ã Â¦â€¢Ã Â¦Â°Ã Â¦Â¾ Ã Â¦Â¹Ã Â¦Â¯Ã Â¦Â¼Ã Â¥Â¤
 *  - Ã Â¦Â¦Ã Â§ÂÃ Â¦Å¸Ã Â§â€¹ Ã Â¦Â®Ã Â¦Â¿Ã Â¦Â²Ã Â§â€¡ Ã Â¦Â¨Ã Â¦Â¾ Ã Â¦â€”Ã Â§â€¡Ã Â¦Â²Ã Â§â€¡ migration Ã Â¦Å¡Ã Â¦Â¾Ã Â¦Â²Ã Â¦Â¾Ã Â¦Â¨Ã Â§â€¹ Ã Â¦Â¹Ã Â¦Â¯Ã Â¦Â¼ Ã Â¦ÂÃ Â¦Â¬Ã Â¦â€š Ã Â¦Â¨Ã Â¦Â¤Ã Â§ÂÃ Â¦Â¨ Ã Â¦Â­Ã Â¦Â¾Ã Â¦Â°Ã Â§ÂÃ Â¦Â¸Ã Â¦Â¨ Ã Â¦Â¸Ã Â§â€¡Ã Â¦Â­ Ã Â¦Â¹Ã Â¦Â¯Ã Â¦Â¼Ã Â¥Â¤
 *
 * Ã Â¦Â¨Ã Â¦Â¤Ã Â§ÂÃ Â¦Â¨ Ã Â¦Å¸Ã Â§â€¡Ã Â¦Â¬Ã Â¦Â¿Ã Â¦Â² Ã Â¦Â¬Ã Â¦Â¾ Ã Â¦â€¢Ã Â¦Â²Ã Â¦Â¾Ã Â¦Â® Ã Â¦Â¯Ã Â§â€¹Ã Â¦â€” Ã Â¦â€¢Ã Â¦Â°Ã Â¦Â¾Ã Â¦Â° Ã Â¦Â¨Ã Â¦Â¿Ã Â¦Â¯Ã Â¦Â¼Ã Â¦Â®:
 *  1. BATCHMATE_DB_VERSION Ã Â¦ÂÃ Â¦Â° Ã Â¦Â¸Ã Â¦â€šÃ Â¦â€“Ã Â§ÂÃ Â¦Â¯Ã Â¦Â¾ Ã Â¦Â¬Ã Â¦Â¾Ã Â¦Â¡Ã Â¦Â¼Ã Â¦Â¾Ã Â¦â€œ (Ã Â¦Â¯Ã Â§â€¡Ã Â¦Â®Ã Â¦Â¨ 1.2 Ã¢â€ â€™ 1.3)
 *  2. batchmate_run_migrations() Ã Â¦Â«Ã Â¦Â¾Ã Â¦â€šÃ Â¦Â¶Ã Â¦Â¨Ã Â§â€¡ Ã Â¦Â¨Ã Â¦Â¤Ã Â§ÂÃ Â¦Â¨ Ã Â¦Â¬Ã Â§ÂÃ Â¦Â²Ã Â¦â€¢ Ã Â¦Â¯Ã Â§â€¹Ã Â¦â€” Ã Â¦â€¢Ã Â¦Â°Ã Â§â€¹:
 *       if ( version_compare( $installed_version, '1.3', '<' ) ) { ... }
 *  3. Ã Â¦Â¬Ã Â¦Â¿Ã Â¦Â¦Ã Â§ÂÃ Â¦Â¯Ã Â¦Â®Ã Â¦Â¾Ã Â¦Â¨ Ã Â¦Å¸Ã Â§â€¡Ã Â¦Â¬Ã Â¦Â¿Ã Â¦Â²Ã Â§â€¡Ã Â¦Â° Ã Â¦Å“Ã Â¦Â¨Ã Â§ÂÃ Â¦Â¯ batchmate_create_base_tables() Ã Â¦â€ Ã Â¦ÂªÃ Â¦Â¡Ã Â§â€¡Ã Â¦Å¸ Ã Â¦â€¢Ã Â¦Â°Ã Â§â€¹Ã Â¥Â¤
 * =====================================================================
 */

define( 'BATCHMATE_DB_VERSION', '3.6' );

/**
 * Ã Â¦Â¥Ã Â¦Â¿Ã Â¦Â® Ã Â¦Â²Ã Â§â€¹Ã Â¦Â¡ Ã Â¦Â¹Ã Â¦â€œÃ Â¦Â¯Ã Â¦Â¼Ã Â¦Â¾Ã Â¦Â° Ã Â¦Â¸Ã Â¦Â®Ã Â¦Â¯Ã Â¦Â¼ DB Ã Â¦Â­Ã Â¦Â¾Ã Â¦Â°Ã Â§ÂÃ Â¦Â¸Ã Â¦Â¨ Ã Â¦Å¡Ã Â§â€¡Ã Â¦â€¢ Ã Â¦â€¢Ã Â¦Â°Ã Â§â€¡ migration Ã Â¦Å¡Ã Â¦Â¾Ã Â¦Â²Ã Â¦Â¾Ã Â¦Â¯Ã Â¦Â¼Ã Â¥Â¤
 */
function batchmate_maybe_upgrade_db() {
    $installed_version = get_option( 'batchmate_db_version', '0' );

    // Ã Â¦Â­Ã Â¦Â¾Ã Â¦Â°Ã Â§ÂÃ Â¦Â¸Ã Â¦Â¨ Ã Â¦Â®Ã Â¦Â¿Ã Â¦Â²Ã Â§â€¡ Ã Â¦â€”Ã Â§â€¡Ã Â¦Â²Ã Â§â€¡ Ã Â¦â€¢Ã Â¦Â¿Ã Â¦â€ºÃ Â§Â Ã Â¦â€¢Ã Â¦Â°Ã Â¦Â¾Ã Â¦Â° Ã Â¦Â¨Ã Â§â€¡Ã Â¦â€¡ Ã¢â‚¬â€ performance Ã Â¦Â¸Ã Â¦Â¾Ã Â¦Â¶Ã Â§ÂÃ Â¦Â°Ã Â¦Â¯Ã Â¦Â¼
    if ( version_compare( $installed_version, BATCHMATE_DB_VERSION, '>=' ) ) {
        return;
    }

    if ( ! function_exists( 'dbDelta' ) ) {
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    }

    // Ã Â¦Â§Ã Â¦Â¾Ã Â¦Âª Ã Â§Â§: Ã Â¦Â¸Ã Â¦Â¬ base Ã Â¦Å¸Ã Â§â€¡Ã Â¦Â¬Ã Â¦Â¿Ã Â¦Â² Ã Â¦Â¤Ã Â§Ë†Ã Â¦Â°Ã Â¦Â¿/Ã Â¦â€ Ã Â¦ÂªÃ Â¦Â¡Ã Â§â€¡Ã Â¦Å¸ Ã Â¦â€¢Ã Â¦Â°Ã Â§â€¹ (Ã Â¦Â¨Ã Â¦Â¿Ã Â¦Â°Ã Â¦Â¾Ã Â¦ÂªÃ Â¦Â¦, data Ã Â¦Â¨Ã Â¦Â·Ã Â§ÂÃ Â¦Å¸ Ã Â¦Â¹Ã Â¦Â¯Ã Â¦Â¼ Ã Â¦Â¨Ã Â¦Â¾)
    batchmate_create_base_tables();

    // Ã Â¦Â§Ã Â¦Â¾Ã Â¦Âª Ã Â§Â¨: version-specific migrations Ã Â¦Å¡Ã Â¦Â¾Ã Â¦Â²Ã Â¦Â¾Ã Â¦â€œ
    batchmate_run_migrations( $installed_version );

    // Ã Â¦Â§Ã Â¦Â¾Ã Â¦Âª Ã Â§Â©: Ã Â¦Â¨Ã Â¦Â¤Ã Â§ÂÃ Â¦Â¨ Ã Â¦Â­Ã Â¦Â¾Ã Â¦Â°Ã Â§ÂÃ Â¦Â¸Ã Â¦Â¨ Ã Â¦Â¸Ã Â§â€¡Ã Â¦Â­ Ã Â¦â€¢Ã Â¦Â°Ã Â§â€¹
    update_option( 'batchmate_db_version', BATCHMATE_DB_VERSION );
}
add_action( 'after_setup_theme', 'batchmate_maybe_upgrade_db' );
add_action( 'admin_init',         'batchmate_maybe_upgrade_db' );


/**
 * =====================================================================
 * BASE TABLES Ã¢â‚¬â€ Ã Â¦Â¸Ã Â¦Â¬ Ã Â¦Å¸Ã Â§â€¡Ã Â¦Â¬Ã Â¦Â¿Ã Â¦Â² Ã Â¦Â¸Ã Â¦â€šÃ Â¦Å“Ã Â§ÂÃ Â¦Å¾Ã Â¦Â¾ Ã Â¦ÂÃ Â¦â€“Ã Â¦Â¾Ã Â¦Â¨Ã Â§â€¡ Ã Â¦Â¥Ã Â¦Â¾Ã Â¦â€¢Ã Â§â€¡Ã Â¥Â¤
 * dbDelta() Ã Â¦Â¨Ã Â¦Â¿Ã Â¦Å“Ã Â§â€¡Ã Â¦â€¡ Ã Â¦Â¬Ã Â§ÂÃ Â¦Â¦Ã Â§ÂÃ Â¦Â§Ã Â¦Â¿Ã Â¦Â®Ã Â¦Â¾Ã Â¦Â¨Ã Â¦Â­Ã Â¦Â¾Ã Â¦Â¬Ã Â§â€¡ ADD/MODIFY Ã Â¦â€¢Ã Â¦Â°Ã Â§â€¡, DROP Ã Â¦â€¢Ã Â¦Â°Ã Â§â€¡ Ã Â¦Â¨Ã Â¦Â¾Ã Â¥Â¤
 * =====================================================================
 */
function batchmate_create_base_tables() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();

    if ( ! function_exists( 'dbDelta' ) ) {
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    }


    


    


    // --- Table: batchmate_committee ---
    $sql = "CREATE TABLE {$wpdb->prefix}batchmate_committee (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        session_year varchar(20) NOT NULL,
        start_date date DEFAULT NULL,
        end_date date DEFAULT NULL,
        is_active tinyint(1) NOT NULL DEFAULT 0,
        PRIMARY KEY  (id),
        UNIQUE KEY session_year_uk (session_year)
    ) $charset_collate;";
    dbDelta( $sql );


    // --- Table: batchmate_ec_post_title ---
    $sql = "CREATE TABLE {$wpdb->prefix}batchmate_ec_post_title (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        post_title varchar(255) NOT NULL,
        position_order mediumint(9) NOT NULL DEFAULT 0,
        position_row mediumint(9) NOT NULL DEFAULT 0,
        PRIMARY KEY  (id),
        UNIQUE KEY post_title_uk (post_title)
    ) $charset_collate;";
    dbDelta( $sql );


    // --- Table: batchmate_ec ---
    $sql = "CREATE TABLE {$wpdb->prefix}batchmate_ec (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        committee_id mediumint(9) NOT NULL,
        member_id mediumint(9) NOT NULL,
        post_id mediumint(9) NOT NULL,
        joining_date date DEFAULT NULL,
        tenure_end_date date DEFAULT NULL,
        PRIMARY KEY  (id),
        UNIQUE KEY committee_member_uk (committee_id, member_id),
        KEY member_id_idx (member_id),
        KEY post_id_idx (post_id)
    ) $charset_collate;";
    dbDelta( $sql );


    // --- Table: batchmate_rawdata ---
    $sql = "CREATE TABLE {$wpdb->prefix}batchmate_rawdata (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        name tinytext NOT NULL,
        name_bn varchar(255) DEFAULT NULL,
        email varchar(100) NOT NULL,
        cell_number varchar(20),
        cell_show tinyint(1) NOT NULL DEFAULT 1,
        land_phone_number varchar(20),
        blood_group varchar(5),
        date_of_birth date,
        gender varchar(10),
        religion varchar(50) DEFAULT NULL,
        student_id varchar(20),
        board varchar(100) DEFAULT NULL,
        roll_no varchar(50) DEFAULT NULL,
        registration_no varchar(50) DEFAULT NULL,
        edu_group varchar(50) DEFAULT NULL,
        institution varchar(255) DEFAULT NULL,
        present_address text,
        present_country varchar(100) DEFAULT 'Bangladesh',
        present_district varchar(100) DEFAULT NULL,
        present_upazila varchar(100) DEFAULT NULL,
        permanent_address text,
        permanent_district varchar(100) DEFAULT NULL,
        permanent_country varchar(100) DEFAULT 'Bangladesh',
        upazila varchar(100) DEFAULT NULL,
        employment_position varchar(100),
        organization varchar(255) DEFAULT NULL,
        industry varchar(100) DEFAULT NULL,
        work_experience varchar(100) DEFAULT NULL,
        previous_organizations text DEFAULT NULL,
        employment_address text,
        about_me text DEFAULT NULL,
        skills_json text DEFAULT NULL,
        facebook_url varchar(255),
        linkedin_url varchar(255),
        x_url varchar(255),
        website varchar(255),
        photo_url varchar(255),
        payment_method varchar(50),
        payment_details varchar(255),
        status varchar(20) DEFAULT 'pending' NOT NULL,
        submission_date datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
        PRIMARY KEY  (id),
        KEY status_idx (status),
        KEY student_id_idx (student_id)
    ) $charset_collate;";
    dbDelta( $sql );


    // --- Table: batchmate_application ---
    $sql = "CREATE TABLE {$wpdb->prefix}batchmate_application (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        name tinytext NOT NULL,
        email varchar(100) NOT NULL,
        cell_number varchar(20),
        cell_show tinyint(1) NOT NULL DEFAULT 1,
        land_phone_number varchar(20),
        blood_group varchar(5),
        date_of_birth date,
        gender varchar(10),
        student_id varchar(20),
        present_address text,
        permanent_address text,
        employment_position varchar(100),
        employment_address text,
        facebook_url varchar(255),
        linkedin_url varchar(255),
        x_url varchar(255),
        website varchar(255),
        photo_url varchar(255),
        payment_method varchar(50),
        payment_details varchar(255),
        status varchar(20) DEFAULT 'pending' NOT NULL,
        submission_date datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
        PRIMARY KEY  (id),
        KEY status_idx (status),
        KEY student_id_idx (student_id)
    ) $charset_collate;";
    dbDelta( $sql );


    // --- Table: batchmate_member ---
    $sql = "CREATE TABLE {$wpdb->prefix}batchmate_member (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        application_id mediumint(9) NOT NULL,
        wp_user_id bigint(20) unsigned NOT NULL,
        member_number varchar(15) NOT NULL,
        name tinytext NOT NULL,
        email varchar(100) NOT NULL,
        cell_number varchar(20),
        cell_show tinyint(1) NOT NULL DEFAULT 1,
        land_phone_number varchar(20),
        blood_group varchar(5),
        date_of_birth date,
        gender varchar(10),
        student_id varchar(20),
        present_address text,
        permanent_address text,
        permanent_district varchar(100),
        present_district varchar(100),
        employment_position varchar(100),
        organization varchar(255),
        industry varchar(100),
        work_experience varchar(100),
        previous_organizations text,
        employment_address text,
        about_me text,
        skills_json text,
        facebook_url varchar(255),
        linkedin_url varchar(255),
        x_url varchar(255),
        website varchar(255),
        photo_url varchar(255),
        joining_date datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
        PRIMARY KEY  (id),
        UNIQUE KEY application_id_uk (application_id),
        UNIQUE KEY member_number_uk (member_number),
        UNIQUE KEY email_uk (email),
        KEY phone_idx (phone),
        KEY wp_user_id_idx (wp_user_id),
        KEY blood_group_idx (blood_group),
        KEY program_id_idx (program_id)
    ) $charset_collate;";
    dbDelta( $sql );


    // --- Table: batchmate_expenses_type ---
    $sql = "CREATE TABLE {$wpdb->prefix}batchmate_expenses_type (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        name varchar(255) NOT NULL,
        PRIMARY KEY  (id),
        UNIQUE KEY name_uk (name)
    ) $charset_collate;";
    dbDelta( $sql );


    // --- Table: batchmate_payment_modes ---
    $sql = "CREATE TABLE {$wpdb->prefix}batchmate_payment_modes (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        name VARCHAR(100) NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        created_by BIGINT UNSIGNED NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";
    dbDelta( $sql );


    // --- Table: batchmate_payments ---
    $sql = "CREATE TABLE {$wpdb->prefix}batchmate_payments (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        member_id mediumint(9) NULL,
        amount DECIMAL(10,2) NOT NULL,
        payment_date DATE NOT NULL,
        payment_mode_id BIGINT UNSIGNED,
        purpose_id mediumint(9) NULL,
        transaction VARCHAR(191),
        note TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        created_by BIGINT UNSIGNED NOT NULL,
        PRIMARY KEY  (id),
        KEY member_id_idx (member_id),
        KEY payment_mode_id_idx (payment_mode_id)
    ) $charset_collate;";
    dbDelta( $sql );


    // --- Table: batchmate_expenses ---
    $sql = "CREATE TABLE {$wpdb->prefix}batchmate_expenses (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        expense_date DATE NOT NULL,
        type_id mediumint(9) NULL,
        amount DECIMAL(10,2) NOT NULL,
        description TEXT,
        vendor VARCHAR(191),
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        created_by BIGINT UNSIGNED NOT NULL,
        PRIMARY KEY  (id),
        KEY type_id_idx (type_id)
    ) $charset_collate;";
    dbDelta( $sql );


    // --- Table: batchmate_payment_purpose ---
    $sql = "CREATE TABLE {$wpdb->prefix}batchmate_payment_purpose (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        name varchar(255) NOT NULL,
        is_active tinyint(1) NOT NULL DEFAULT 1,
        PRIMARY KEY  (id),
        UNIQUE KEY name_uk (name)
    ) $charset_collate;";
    dbDelta( $sql );


    // =========================================================
    // v1.2 Ã¢â‚¬â€ Events Module Tables
    // =========================================================

    // --- Table: batchmate_event ---
    $sql = "CREATE TABLE {$wpdb->prefix}batchmate_event (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        event_name varchar(255) NOT NULL,
        event_date date NOT NULL,
        member_fee DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        allow_non_members tinyint(1) NOT NULL DEFAULT 0,
        direct_registration tinyint(1) NOT NULL DEFAULT 0,
        direct_reg_text varchar(255) NOT NULL DEFAULT '',
        is_active tinyint(1) NOT NULL DEFAULT 0,
        location varchar(500) NOT NULL DEFAULT '',
        registered_url varchar(500) NOT NULL DEFAULT '',
        has_id_card tinyint(1) NOT NULL DEFAULT 0,
        auto_send_card tinyint(1) NOT NULL DEFAULT 0,
        has_tshirt tinyint(1) NOT NULL DEFAULT 0,
        has_saree tinyint(1) NOT NULL DEFAULT 0,
        cc_email varchar(191) NOT NULL DEFAULT '',
        card_template_url varchar(500) NOT NULL DEFAULT '',
        card_zones_json text DEFAULT NULL,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        created_by bigint(20) unsigned NOT NULL DEFAULT 0,
        PRIMARY KEY  (id),
        KEY is_active_idx (is_active)
    ) $charset_collate;";
    dbDelta( $sql );


    // --- Table: batchmate_event_option ---
    $sql = "CREATE TABLE {$wpdb->prefix}batchmate_event_option (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        event_id mediumint(9) NOT NULL,
        option_name varchar(100) NOT NULL,
        fee DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        max_qty int(11) NOT NULL DEFAULT 0,
        sort_order int(11) NOT NULL DEFAULT 0,
        PRIMARY KEY  (id),
        KEY event_id_idx (event_id)
    ) $charset_collate;";
    dbDelta( $sql );


    // --- Table: batchmate_event_application ---
    $sql = "CREATE TABLE {$wpdb->prefix}batchmate_event_application (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        event_id mediumint(9) NOT NULL,
        member_id mediumint(9) NOT NULL,
        rawdata_id mediumint(9) NOT NULL DEFAULT 0,
        payment_method varchar(100) NOT NULL DEFAULT '',
        payment_details varchar(255) NOT NULL DEFAULT '',
        amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        options_json text DEFAULT NULL,
        tshirt_size varchar(10) NOT NULL DEFAULT '',
        status varchar(20) NOT NULL DEFAULT 'pending',
        applied_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY  (id),
        UNIQUE KEY event_member_rawdata_uk (event_id, member_id, rawdata_id),
        KEY event_id_idx (event_id),
        KEY member_id_idx (member_id),
        KEY status_idx (status)
    ) $charset_collate;";
    dbDelta( $sql );


    // --- Table: batchmate_event_members ---
    $sql = "CREATE TABLE {$wpdb->prefix}batchmate_event_members (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        event_id mediumint(9) NOT NULL,
        member_id mediumint(9) NOT NULL,
        application_id mediumint(9) NOT NULL,
        rawdata_id mediumint(9) NOT NULL DEFAULT 0,
        serial_no mediumint(9) NOT NULL DEFAULT 0,
        approved_at datetime DEFAULT CURRENT_TIMESTAMP,
        approved_by bigint(20) unsigned NOT NULL DEFAULT 0,
        PRIMARY KEY  (id),
        UNIQUE KEY event_member_rawdata_uk (event_id, member_id, rawdata_id),
        KEY event_id_idx (event_id),
        KEY member_id_idx (member_id),
        KEY application_id_idx (application_id)
    ) $charset_collate;";
    dbDelta( $sql );

    // --- Table: batchmate_event_transactions ---
    $sql = "CREATE TABLE {$wpdb->prefix}batchmate_event_transactions (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        event_id mediumint(9) NOT NULL,
        trans_type enum('expense', 'sponsor') NOT NULL DEFAULT 'expense',
        purpose varchar(255) NOT NULL,
        amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        trans_date date NOT NULL,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        created_by bigint(20) unsigned NOT NULL DEFAULT 0,
        PRIMARY KEY  (id),
        KEY event_id_idx (event_id),
        KEY trans_type_idx (trans_type)
    ) $charset_collate;";
    dbDelta( $sql );
}


/**
 * =====================================================================
 * VERSION-SPECIFIC MIGRATIONS
 * =====================================================================
 *
 * Ã Â¦ÂªÃ Â§ÂÃ Â¦Â°Ã Â¦Â¤Ã Â¦Â¿Ã Â¦Å¸Ã Â¦Â¿ Ã Â¦Â¬Ã Â§ÂÃ Â¦Â²Ã Â¦â€¢ Ã Â¦Â¶Ã Â§ÂÃ Â¦Â§Ã Â§Â Ã Â¦ÂÃ Â¦â€¢Ã Â¦Â¬Ã Â¦Â¾Ã Â¦Â° Ã Â¦Å¡Ã Â¦Â²Ã Â¦Â¬Ã Â§â€¡Ã Â¥Â¤ Ã Â¦Â­Ã Â¦Â¬Ã Â¦Â¿Ã Â¦Â·Ã Â§ÂÃ Â¦Â¯Ã Â¦Â¤Ã Â§â€¡ Ã Â¦Â¨Ã Â¦Â¤Ã Â§ÂÃ Â¦Â¨ version Ã Â¦Â¯Ã Â§â€¹Ã Â¦â€” Ã Â¦â€¢Ã Â¦Â°Ã Â¦Â¤Ã Â§â€¡
 * Ã Â¦Â¨Ã Â¦Â¿Ã Â¦Å¡Ã Â§â€¡ Ã Â¦Â¨Ã Â¦Â¤Ã Â§ÂÃ Â¦Â¨ if-Ã Â¦Â¬Ã Â§ÂÃ Â¦Â²Ã Â¦â€¢ Ã Â¦Â¯Ã Â§â€¹Ã Â¦â€” Ã Â¦â€¢Ã Â¦Â°Ã Â§ÂÃ Â¦Â¨Ã Â¥Â¤
 *
 * Ã Â¦â€°Ã Â¦Â¦Ã Â¦Â¾Ã Â¦Â¹Ã Â¦Â°Ã Â¦Â£ (v1.3 Ã Â¦ÂÃ Â¦Â° Ã Â¦Å“Ã Â¦Â¨Ã Â§ÂÃ Â¦Â¯):
 *   if ( version_compare( $installed_version, '1.3', '<' ) ) {
 *       // Ã Â¦Â¯Ã Â§â€¡Ã Â¦â€¢Ã Â§â€¹Ã Â¦Â¨Ã Â§â€¹ data migration Ã Â¦Â¬Ã Â¦Â¾ Ã Â¦ÂÃ Â¦â€¢Ã Â¦â€¢Ã Â¦Â¾Ã Â¦Â²Ã Â§â‚¬Ã Â¦Â¨ Ã Â¦â€¢Ã Â¦Â¾Ã Â¦Å“
 *       $wpdb->query( "ALTER TABLE ... ADD COLUMN ..." );
 *   }
 * =====================================================================
 */
function batchmate_run_migrations( $installed_version ) {
    global $wpdb;

    /**
     * v1.1 Ã¢â€ â€™ v1.2 : Events Module
     * batchmate_create_base_tables() Ã Â¦â€¡ Ã Â¦Â¤Ã Â¦Â¿Ã Â¦Â¨Ã Â¦Å¸Ã Â¦Â¿ Ã Â¦Â¨Ã Â¦Â¤Ã Â§ÂÃ Â¦Â¨ Ã Â¦Å¸Ã Â§â€¡Ã Â¦Â¬Ã Â¦Â¿Ã Â¦Â² Ã Â¦Â¤Ã Â§Ë†Ã Â¦Â°Ã Â¦Â¿ Ã Â¦â€¢Ã Â¦Â°Ã Â§â€¡Ã Â¥Â¤
     * Ã Â¦ÂÃ Â¦â€“Ã Â¦Â¾Ã Â¦Â¨Ã Â§â€¡ Ã Â¦â€¢Ã Â§â€¹Ã Â¦Â¨Ã Â§â€¹ data migration Ã Â¦Â¦Ã Â¦Â°Ã Â¦â€¢Ã Â¦Â¾Ã Â¦Â° Ã Â¦Â¨Ã Â§â€¡Ã Â¦â€¡Ã Â¥Â¤
     */
    if ( version_compare( $installed_version, '1.2', '<' ) ) {
        // Ã Â¦Â­Ã Â¦Â¬Ã Â¦Â¿Ã Â¦Â·Ã Â§ÂÃ Â¦Â¯Ã Â¦Â¤Ã Â§â€¡ Ã Â¦ÂÃ Â¦â€“Ã Â¦Â¾Ã Â¦Â¨Ã Â§â€¡ one-time data migration Ã Â¦â€¢Ã Â§â€¹Ã Â¦Â¡ Ã Â¦Â²Ã Â§â€¡Ã Â¦â€“Ã Â§â€¹Ã Â¥Â¤
    }

    /**
     * v1.2 Ã¢â€ â€™ v1.3 : Add amount column to event_application
     */
    if ( version_compare( $installed_version, '1.3', '<' ) ) {
        $columns = $wpdb->get_col( "SHOW COLUMNS FROM {$wpdb->prefix}batchmate_event_application LIKE 'amount'" );
        if ( empty( $columns ) ) {
            $wpdb->query( "ALTER TABLE {$wpdb->prefix}batchmate_event_application ADD COLUMN amount DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER payment_details" );
        }
    }

    /**
     * v1.3 Ã¢â€ â€™ v1.4 : Add batchmate_event_option table + options_json to event_application
     */
    if ( version_compare( $installed_version, '1.4', '<' ) ) {
        // Create batchmate_event_option if missing
        $charset_collate = $wpdb->get_charset_collate();
        $wpdb->query( "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}batchmate_event_option (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            event_id mediumint(9) NOT NULL,
            option_name varchar(100) NOT NULL,
            fee DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            max_qty int(11) NOT NULL DEFAULT 0,
            sort_order int(11) NOT NULL DEFAULT 0,
            PRIMARY KEY  (id),
            KEY event_id_idx (event_id)
        ) $charset_collate" );

        // Add options_json column if missing
        $cols = $wpdb->get_col( "SHOW COLUMNS FROM {$wpdb->prefix}batchmate_event_application LIKE 'options_json'" );
        if ( empty( $cols ) ) {
            $wpdb->query( "ALTER TABLE {$wpdb->prefix}batchmate_event_application ADD COLUMN options_json text DEFAULT NULL AFTER amount" );
        }
    }

    /**
     * v1.4 Ã¢â€ â€™ v1.5 : Add member_fee column to event table
     */
    if ( version_compare( $installed_version, '1.5', '<' ) ) {
        $cols = $wpdb->get_col( "SHOW COLUMNS FROM {$wpdb->prefix}batchmate_event LIKE 'member_fee'" );
        if ( empty( $cols ) ) {
            $wpdb->query( "ALTER TABLE {$wpdb->prefix}batchmate_event ADD COLUMN member_fee DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER event_date" );
        }
    }

    /**
     * v1.5 Ã¢â€ â€™ v1.6 : Add allow_non_members to event, rawdata_id to applications and members
     */
    if ( version_compare( $installed_version, '1.6', '<' ) ) {
        // Event table: allow_non_members
        $cols = $wpdb->get_col( "SHOW COLUMNS FROM {$wpdb->prefix}batchmate_event LIKE 'allow_non_members'" );
        if ( empty( $cols ) ) {
            $wpdb->query( "ALTER TABLE {$wpdb->prefix}batchmate_event ADD COLUMN allow_non_members tinyint(1) NOT NULL DEFAULT 0 AFTER member_fee" );
        }

        // Application table: rawdata_id and update unique index
        $cols = $wpdb->get_col( "SHOW COLUMNS FROM {$wpdb->prefix}batchmate_event_application LIKE 'rawdata_id'" );
        if ( empty( $cols ) ) {
            $wpdb->query( "ALTER TABLE {$wpdb->prefix}batchmate_event_application ADD COLUMN rawdata_id mediumint(9) NOT NULL DEFAULT 0 AFTER member_id" );
            
            // Drop old index, add new one to allow multiple member_id = 0 entries
            $wpdb->query( "ALTER TABLE {$wpdb->prefix}batchmate_event_application DROP INDEX event_member_uk" );
            $wpdb->query( "ALTER TABLE {$wpdb->prefix}batchmate_event_application ADD UNIQUE KEY event_member_rawdata_uk (event_id, member_id, rawdata_id)" );
        }

        // Members table: rawdata_id and update unique index
        $cols = $wpdb->get_col( "SHOW COLUMNS FROM {$wpdb->prefix}batchmate_event_members LIKE 'rawdata_id'" );
        if ( empty( $cols ) ) {
            $wpdb->query( "ALTER TABLE {$wpdb->prefix}batchmate_event_members ADD COLUMN rawdata_id mediumint(9) NOT NULL DEFAULT 0 AFTER application_id" );
            
            // Drop old index, add new one
            $wpdb->query( "ALTER TABLE {$wpdb->prefix}batchmate_event_members DROP INDEX event_member_uk" );
            $wpdb->query( "ALTER TABLE {$wpdb->prefix}batchmate_event_members ADD UNIQUE KEY event_member_rawdata_uk (event_id, member_id, rawdata_id)" );
        }
    }

    /**
     * v1.6 Ã¢â€ â€™ v1.7 : Add serial_no to event_members for per-event sequential numbering
     */
    if ( version_compare( $installed_version, '1.7', '<' ) ) {
        $cols = $wpdb->get_col( "SHOW COLUMNS FROM {$wpdb->prefix}batchmate_event_members LIKE 'serial_no'" );
        if ( empty( $cols ) ) {
            $wpdb->query( "ALTER TABLE {$wpdb->prefix}batchmate_event_members ADD COLUMN serial_no mediumint(9) NOT NULL DEFAULT 0 AFTER rawdata_id" );
        }
        // Backfill serial numbers for existing records (ordered by approved_at within each event)
        $events = $wpdb->get_col( "SELECT DISTINCT event_id FROM {$wpdb->prefix}batchmate_event_members" );
        foreach ( $events as $eid ) {
            $members = $wpdb->get_results( $wpdb->prepare(
                "SELECT id FROM {$wpdb->prefix}batchmate_event_members WHERE event_id = %d ORDER BY id ASC",
                $eid
            ) );
            $sn = 1;
            foreach ( $members as $m ) {
                $wpdb->update(
                    $wpdb->prefix . 'batchmate_event_members',
                    array( 'serial_no' => $sn++ ),
                    array( 'id' => $m->id ),
                    array( '%d' ),
                    array( '%d' )
                );
            }
        }
    }

    /**
     * v1.7 Ã¢â€ â€™ v1.8 : Add location and registered_url to batchmate_event
     */
    if ( version_compare( $installed_version, '1.8', '<' ) ) {
        $cols = $wpdb->get_col( "SHOW COLUMNS FROM {$wpdb->prefix}batchmate_event LIKE 'location'" );
        if ( empty( $cols ) ) {
            $wpdb->query( "ALTER TABLE {$wpdb->prefix}batchmate_event ADD COLUMN location varchar(500) NOT NULL DEFAULT '' AFTER is_active" );
        }
        $cols2 = $wpdb->get_col( "SHOW COLUMNS FROM {$wpdb->prefix}batchmate_event LIKE 'registered_url'" );
        if ( empty( $cols2 ) ) {
            $wpdb->query( "ALTER TABLE {$wpdb->prefix}batchmate_event ADD COLUMN registered_url varchar(500) NOT NULL DEFAULT '' AFTER location" );
        }
    }

    /**
     * v1.8 Ã¢â€ â€™ v1.9 : Add batchmate_event_transactions table
     */
    if ( version_compare( $installed_version, '1.9', '<' ) ) {
        $charset_collate = $wpdb->get_charset_collate();
        $wpdb->query( "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}batchmate_event_transactions (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            event_id mediumint(9) NOT NULL,
            trans_type enum('expense', 'sponsor') NOT NULL DEFAULT 'expense',
            purpose varchar(255) NOT NULL,
            amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            trans_date date NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            created_by bigint(20) unsigned NOT NULL DEFAULT 0,
            PRIMARY KEY  (id),
            KEY event_id_idx (event_id),
            KEY trans_type_idx (trans_type)
        ) $charset_collate" );
    }

    /**
     * v1.9 Ã¢â€ â€™ v2.0 : Add card_template_url and card_zones_json to batchmate_event
     */
    if ( version_compare( $installed_version, '2.0', '<' ) ) {
        $cols1 = $wpdb->get_col( "SHOW COLUMNS FROM {$wpdb->prefix}batchmate_event LIKE 'card_template_url'" );
        if ( empty( $cols1 ) ) {
            $wpdb->query( "ALTER TABLE {$wpdb->prefix}batchmate_event ADD COLUMN card_template_url varchar(500) NOT NULL DEFAULT '' AFTER registered_url" );
        }
        $cols2 = $wpdb->get_col( "SHOW COLUMNS FROM {$wpdb->prefix}batchmate_event LIKE 'card_zones_json'" );
        if ( empty( $cols2 ) ) {
            $wpdb->query( "ALTER TABLE {$wpdb->prefix}batchmate_event ADD COLUMN card_zones_json text DEFAULT NULL AFTER card_template_url" );
        }
    }

    /**
     * v2.1 -> v2.2 : Drop event_member_uk index which causes issues for non-members
     */
    if ( version_compare( $installed_version, '2.2', '<' ) ) {
        $idx1 = $wpdb->get_results( "SHOW INDEX FROM {$wpdb->prefix}batchmate_event_application WHERE Key_name = 'event_member_uk'" );
        if ( !empty($idx1) ) {
            $wpdb->query( "ALTER TABLE {$wpdb->prefix}batchmate_event_application DROP INDEX event_member_uk" );
        }

        $idx2 = $wpdb->get_results( "SHOW INDEX FROM {$wpdb->prefix}batchmate_event_members WHERE Key_name = 'event_member_uk'" );
        if ( !empty($idx2) ) {
            $wpdb->query( "ALTER TABLE {$wpdb->prefix}batchmate_event_members DROP INDEX event_member_uk" );
        }
    }
    /**
     * v2.2 -> v2.3 : Backfill rawdata_id in batchmate_event_members from batchmate_event_application
     */
    if ( version_compare( $installed_version, '2.3', '<' ) ) {
        $wpdb->query(
            "UPDATE {$wpdb->prefix}batchmate_event_members em
             INNER JOIN {$wpdb->prefix}batchmate_event_application ea ON em.application_id = ea.id
             SET em.rawdata_id = ea.rawdata_id,
                 em.member_id  = ea.member_id
             WHERE em.rawdata_id = 0
               AND ea.rawdata_id > 0"
        );
    }

    /**
     * v2.3 -> v2.4 : Add cc_email, has_tshirt to batchmate_event; tshirt_size to batchmate_event_application
     */
    if ( version_compare( $installed_version, '2.4', '<' ) ) {
        // batchmate_event: cc_email
        $cols = $wpdb->get_col( "SHOW COLUMNS FROM {$wpdb->prefix}batchmate_event LIKE 'cc_email'" );
        if ( empty( $cols ) ) {
            $wpdb->query( "ALTER TABLE {$wpdb->prefix}batchmate_event ADD COLUMN cc_email varchar(191) NOT NULL DEFAULT '' AFTER auto_send_card" );
        }
        // batchmate_event: has_tshirt
        $cols2 = $wpdb->get_col( "SHOW COLUMNS FROM {$wpdb->prefix}batchmate_event LIKE 'has_tshirt'" );
        if ( empty( $cols2 ) ) {
            $wpdb->query( "ALTER TABLE {$wpdb->prefix}batchmate_event ADD COLUMN has_tshirt tinyint(1) NOT NULL DEFAULT 0 AFTER cc_email" );
        }
        // batchmate_event_application: tshirt_size
        $cols3 = $wpdb->get_col( "SHOW COLUMNS FROM {$wpdb->prefix}batchmate_event_application LIKE 'tshirt_size'" );
        if ( empty( $cols3 ) ) {
            $wpdb->query( "ALTER TABLE {$wpdb->prefix}batchmate_event_application ADD COLUMN tshirt_size varchar(10) NOT NULL DEFAULT '' AFTER options_json" );
        }
    }

    /**
     * v2.4 -> v2.5 : Add new member profile fields
     * about_me, organization, industry, work_experience,
     * previous_organizations, present_district, skills_json
     */
    if ( version_compare( $installed_version, '2.5', '<' ) ) {
        $mt = $wpdb->prefix . 'batchmate_member';

        $new_cols = [
            'present_district'       => "ALTER TABLE {$mt} ADD COLUMN present_district varchar(100) DEFAULT NULL AFTER permanent_address",
            'organization'           => "ALTER TABLE {$mt} ADD COLUMN organization varchar(255) DEFAULT NULL AFTER employment_position",
            'industry'               => "ALTER TABLE {$mt} ADD COLUMN industry varchar(100) DEFAULT NULL AFTER organization",
            'work_experience'        => "ALTER TABLE {$mt} ADD COLUMN work_experience varchar(100) DEFAULT NULL AFTER industry",
            'previous_organizations' => "ALTER TABLE {$mt} ADD COLUMN previous_organizations text DEFAULT NULL AFTER work_experience",
            'about_me'               => "ALTER TABLE {$mt} ADD COLUMN about_me text DEFAULT NULL AFTER employment_address",
            'skills_json'            => "ALTER TABLE {$mt} ADD COLUMN skills_json text DEFAULT NULL AFTER about_me",
        ];

        foreach ( $new_cols as $col_name => $alter_sql ) {
            $exists = $wpdb->get_col( "SHOW COLUMNS FROM {$mt} LIKE '{$col_name}'" );
            if ( empty( $exists ) ) {
                $wpdb->query( $alter_sql );
            }
        }
    }

    /**
     * v2.5 -> v2.6 : Add new profile fields to application and rawdata tables
     */
    if ( version_compare( $installed_version, '2.6', '<' ) ) {
        $tables_to_update = [
            $wpdb->prefix . 'batchmate_application',
            $wpdb->prefix . 'batchmate_rawdata'
        ];

        foreach ( $tables_to_update as $table ) {
            $new_cols = [
                'present_district'       => "ALTER TABLE {$table} ADD COLUMN present_district varchar(100) DEFAULT NULL AFTER permanent_address",
                'organization'           => "ALTER TABLE {$table} ADD COLUMN organization varchar(255) DEFAULT NULL AFTER employment_position",
                'industry'               => "ALTER TABLE {$table} ADD COLUMN industry varchar(100) DEFAULT NULL AFTER organization",
                'work_experience'        => "ALTER TABLE {$table} ADD COLUMN work_experience varchar(100) DEFAULT NULL AFTER industry",
                'previous_organizations' => "ALTER TABLE {$table} ADD COLUMN previous_organizations text DEFAULT NULL AFTER work_experience",
                'about_me'               => "ALTER TABLE {$table} ADD COLUMN about_me text DEFAULT NULL AFTER employment_address",
                'skills_json'            => "ALTER TABLE {$table} ADD COLUMN skills_json text DEFAULT NULL AFTER about_me",
            ];

            foreach ( $new_cols as $col_name => $alter_sql ) {
                $exists = $wpdb->get_col( "SHOW COLUMNS FROM {$table} LIKE '{$col_name}'" );
                if ( empty( $exists ) ) {
                    $wpdb->query( $alter_sql );
                }
            }
        }
    }
    /**
     * v2.6 -> v2.7 : Add permanent_district to member, application, and rawdata tables
     */
    if ( version_compare( $installed_version, '2.7', '<' ) ) {
        $tables_to_update = [
            $wpdb->prefix . 'batchmate_member',
            $wpdb->prefix . 'batchmate_application',
            $wpdb->prefix . 'batchmate_rawdata'
        ];

        foreach ( $tables_to_update as $table ) {
            $exists = $wpdb->get_col( "SHOW COLUMNS FROM {$table} LIKE 'permanent_district'" );
            if ( empty( $exists ) ) {
                $wpdb->query( "ALTER TABLE {$table} ADD COLUMN permanent_district varchar(100) DEFAULT NULL AFTER permanent_address" );
            }
        }
    }

    /**
     * v2.7 â†’ v2.8 : Rename legacy puaa_* / BatchMate_* tables â†’ batchmate_* (BatchMate rebranding)
     * à¦à¦•à¦¬à¦¾à¦°à¦‡ à¦šà¦²à¦¬à§‡à¥¤ à¦ªà§à¦°à¦¾à¦¨à§‹ à¦¨à¦¾à¦®à§‡à¦° à¦Ÿà§‡à¦¬à¦¿à¦² à¦¥à¦¾à¦•à¦²à§‡ à¦¨à¦¤à§à¦¨ à¦¨à¦¾à¦®à§‡ rename à¦•à¦°à¦¬à§‡à¥¤
     */
    if ( version_compare( $installed_version, '2.8', '<' ) ) {
        $table_renames = [
            // From original PUAA schema
            'puaa_member'                  => 'batchmate_member',
            'puaa_application'             => 'batchmate_application',
            'puaa_department'              => 'batchmate_department',
            'puaa_program'                 => 'batchmate_program',
            'puaa_payment'                 => 'batchmate_payment',
            'puaa_expense'                 => 'batchmate_expense',
            'puaa_event'                   => 'batchmate_event',
            'puaa_event_registration'      => 'batchmate_event_registration',
            'puaa_executive_committee'     => 'batchmate_executive_committee',
            'puaa_committee'               => 'batchmate_committee',
            'puaa_ec_post_title'           => 'batchmate_ec_post_title',
            'puaa_ec_member'               => 'batchmate_ec_member',
            'puaa_rawdata'                 => 'batchmate_rawdata',
            'puaa_event_application'       => 'batchmate_event_application',
            'puaa_event_members'           => 'batchmate_event_members',
            'puaa_event_option'            => 'batchmate_event_option',
            'puaa_event_transactions'      => 'batchmate_event_transactions',
            // From partial BatchMate_ (mixed-case) rename
            'batchmate_member'             => 'batchmate_member',
            'batchmate_application'        => 'batchmate_application',
            'batchmate_department'         => 'batchmate_department',
            'batchmate_program'            => 'batchmate_program',
            'batchmate_payment'            => 'batchmate_payment',
            'batchmate_expense'            => 'batchmate_expense',
            'batchmate_event'              => 'batchmate_event',
            'batchmate_committee'          => 'batchmate_committee',
            'batchmate_ec_post_title'      => 'batchmate_ec_post_title',
            'batchmate_ec_member'          => 'batchmate_ec_member',
            'batchmate_rawdata'            => 'batchmate_rawdata',
            'batchmate_event_application'  => 'batchmate_event_application',
            'batchmate_event_members'      => 'batchmate_event_members',
            'batchmate_event_option'       => 'batchmate_event_option',
            'batchmate_event_transactions' => 'batchmate_event_transactions',
            'batchmate_executive_committee' => 'batchmate_executive_committee',
            'batchmate_event_registration'  => 'batchmate_event_registration',
        ];

        foreach ( $table_renames as $old_suffix => $new_suffix ) {
            $old_table = $wpdb->prefix . $old_suffix;
            $new_table = $wpdb->prefix . $new_suffix;

            $old_exists = $wpdb->get_var( $wpdb->prepare(
                "SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = %s",
                $old_table
            ) );
            $new_exists = $wpdb->get_var( $wpdb->prepare(
                "SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = %s",
                $new_table
            ) );

            if ( $old_exists && ! $new_exists ) {
                // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
                $wpdb->query( "RENAME TABLE `{$old_table}` TO `{$new_table}`" );
            }
        }

        // Options key migration
        $wpdb->query( "UPDATE {$wpdb->options} SET option_name = 'batchmate_db_version'                    WHERE option_name IN ('puaa_db_version', 'batchmate_db_version')" );
        $wpdb->query( "UPDATE {$wpdb->options} SET option_name = 'batchmate_settings'                     WHERE option_name IN ('puua_settings')" );
        $wpdb->query( "UPDATE {$wpdb->options} SET option_name = 'batchmate_member_role_v1_migrated'      WHERE option_name IN ('puua_member_role_v1_migrated')" );
        $wpdb->query( "UPDATE {$wpdb->options} SET option_name = 'batchmate_member_photo_meta_v1_migrated' WHERE option_name IN ('puua_member_photo_meta_v1_migrated')" );

        // User meta key migration
        $wpdb->query( "UPDATE {$wpdb->usermeta} SET meta_key = 'batchmate_member_photo' WHERE meta_key = 'puaa_member_photo'" );

        // WordPress role migration: puaa_member â†’ batchmate_member
        if ( ! get_role( 'batchmate_member' ) && get_role( 'puaa_member' ) ) {
            add_role( 'batchmate_member', 'Member', [ 'read' => true ] );
            $old_role_users = get_users( [ 'role' => 'puaa_member' ] );
            foreach ( $old_role_users as $u ) {
                $u->remove_role( 'puaa_member' );
                $u->add_role( 'batchmate_member' );
            }
            remove_role( 'puaa_member' );
        }
    }

    /**
     * v2.8 -> v2.9 : Add board, roll_no, registration_no, edu_group, institution
     */
    if ( version_compare( $installed_version, '2.9', '<' ) ) {
        $tables_to_update = [
            $wpdb->prefix . 'batchmate_member',
            $wpdb->prefix . 'batchmate_application',
            $wpdb->prefix . 'batchmate_rawdata'
        ];

        foreach ( $tables_to_update as $table ) {
            $new_cols = [
                'board'           => "ALTER TABLE {$table} ADD COLUMN board varchar(50) DEFAULT NULL AFTER gender",
                'roll_no'         => "ALTER TABLE {$table} ADD COLUMN roll_no varchar(50) DEFAULT NULL AFTER board",
                'registration_no' => "ALTER TABLE {$table} ADD COLUMN registration_no varchar(50) DEFAULT NULL AFTER roll_no",
                'edu_group'       => "ALTER TABLE {$table} ADD COLUMN edu_group varchar(50) DEFAULT NULL AFTER registration_no",
                'institution'     => "ALTER TABLE {$table} ADD COLUMN institution varchar(255) DEFAULT NULL AFTER edu_group",
            ];

            foreach ( $new_cols as $col_name => $alter_sql ) {
                $exists = $wpdb->get_col( "SHOW COLUMNS FROM {$table} LIKE '{$col_name}'" );
                if ( empty( $exists ) ) {
                    $wpdb->query( $alter_sql );
                }
            }
        }
    }

    /**
     * v2.9 → v3.0 : Add upazila to member/application/rawdata; payment_receiver & remarks to event_application
     */
    if ( version_compare( $installed_version, '3.0', '<' ) ) {
        // upazila column → rawdata, application, member
        $member_tables = [
            $wpdb->prefix . 'batchmate_member',
            $wpdb->prefix . 'batchmate_application',
            $wpdb->prefix . 'batchmate_rawdata',
        ];
        foreach ( $member_tables as $table ) {
            $exists = $wpdb->get_col( "SHOW COLUMNS FROM {$table} LIKE 'upazila'" );
            if ( empty( $exists ) ) {
                $wpdb->query( "ALTER TABLE {$table} ADD COLUMN upazila varchar(100) DEFAULT NULL AFTER permanent_district" );
            }
        }

        // payment_receiver → event_application
        $ea = $wpdb->prefix . 'batchmate_event_application';
        $cols1 = $wpdb->get_col( "SHOW COLUMNS FROM {$ea} LIKE 'payment_receiver'" );
        if ( empty( $cols1 ) ) {
            $wpdb->query( "ALTER TABLE {$ea} ADD COLUMN payment_receiver varchar(100) DEFAULT NULL AFTER amount" );
        }

        // remarks → event_application
        $cols2 = $wpdb->get_col( "SHOW COLUMNS FROM {$ea} LIKE 'remarks'" );
        if ( empty( $cols2 ) ) {
            $wpdb->query( "ALTER TABLE {$ea} ADD COLUMN remarks text DEFAULT NULL AFTER payment_receiver" );
        }
    }

    /**
     * v3.0 → v3.1 : Add name_bn to member, application, rawdata
     */
    if ( version_compare( $installed_version, '3.1', '<' ) ) {
        $member_tables = [
            $wpdb->prefix . 'batchmate_member',
            $wpdb->prefix . 'batchmate_application',
            $wpdb->prefix . 'batchmate_rawdata',
        ];
        foreach ( $member_tables as $table ) {
            $exists = $wpdb->get_col( "SHOW COLUMNS FROM {$table} LIKE 'name_bn'" );
            if ( empty( $exists ) ) {
                $wpdb->query( "ALTER TABLE {$table} ADD COLUMN name_bn varchar(255) DEFAULT NULL AFTER name" );
            }
        }
    }
    
    /**
     * v3.1 -> v3.2 : Add present_country and permanent_country
     */
    if ( version_compare( $installed_version, '3.2', '<' ) ) {
        $country_tables = [
            $wpdb->prefix . 'batchmate_member',
            $wpdb->prefix . 'batchmate_application'
        ];
        foreach ( $country_tables as $table ) {
            $exists = $wpdb->get_col( "SHOW COLUMNS FROM {$table} LIKE 'present_country'" );
            if ( empty( $exists ) ) {
                $wpdb->query( "ALTER TABLE {$table} ADD COLUMN present_country varchar(100) DEFAULT 'Bangladesh' AFTER present_district" );
                $wpdb->query( "ALTER TABLE {$table} ADD COLUMN permanent_country varchar(100) DEFAULT 'Bangladesh' AFTER permanent_district" );
            }
        }
        update_option( 'batchmate_db_version', '3.2' );
    }

    /**
     * v3.2 -> v3.3 : Add religion
     */
    if ( version_compare( $installed_version, '3.3', '<' ) ) {
        $tables_to_update = [
            $wpdb->prefix . 'batchmate_member',
            $wpdb->prefix . 'batchmate_application',
            $wpdb->prefix . 'batchmate_rawdata'
        ];
        foreach ( $tables_to_update as $table ) {
            $exists = $wpdb->get_col( "SHOW COLUMNS FROM {$table} LIKE 'religion'" );
            if ( empty( $exists ) ) {
                $wpdb->query( "ALTER TABLE {$table} ADD COLUMN religion varchar(50) DEFAULT NULL AFTER gender" );
            }
        }
        update_option( 'batchmate_db_version', '3.3' );
    }

    /**
     * v3.3 -> v3.4 : Add has_saree to batchmate_event; expand tshirt_size in batchmate_event_application
     */
    if ( version_compare( $installed_version, '3.4', '<' ) ) {
        batchmate_ensure_saree_column();
        $app_table = $wpdb->prefix . 'batchmate_event_application';
        $app_col = $wpdb->get_col( "SHOW COLUMNS FROM {$app_table} LIKE 'tshirt_size'" );
        if ( ! empty( $app_col ) ) {
            $wpdb->query( "ALTER TABLE {$app_table} MODIFY COLUMN tshirt_size varchar(20) NOT NULL DEFAULT ''" );
        }
        update_option( 'batchmate_db_version', '3.4' );
    }

    /**
     * v3.4 -> v3.5 : Add present_upazila / upazila columns
     */
    batchmate_ensure_location_columns();
    if ( version_compare( $installed_version, '3.5', '<' ) ) {
        update_option( 'batchmate_db_version', '3.5' );
    }

    /**
     * v3.5 -> v3.6 : Add performance indexes to batchmate_member (phone, wp_user_id, blood_group, program_id)
     */
    if ( version_compare( $installed_version, '3.6', '<' ) ) {
        batchmate_add_performance_indexes();
        update_option( 'batchmate_db_version', '3.6' );
    }
}

/**
 * Add performance indexes to batchmate_member if not already present
 */
function batchmate_add_performance_indexes() {
    global $wpdb;
    $member_table = $wpdb->prefix . 'batchmate_member';
    
    // Check if table exists
    $table_check = $wpdb->get_var( "SHOW TABLES LIKE '{$member_table}'" );
    if ( ! $table_check ) return;

    $existing = $wpdb->get_results( "SHOW INDEX FROM {$member_table}" );
    $index_names = array();
    if ( ! empty( $existing ) ) {
        foreach ( $existing as $idx ) {
            $index_names[] = $idx->Key_name;
        }
    }
    
    if ( ! in_array( 'phone_idx', $index_names, true ) ) {
        $wpdb->query( "ALTER TABLE {$member_table} ADD INDEX phone_idx (phone)" );
    }
    if ( ! in_array( 'wp_user_id_idx', $index_names, true ) ) {
        $wpdb->query( "ALTER TABLE {$member_table} ADD INDEX wp_user_id_idx (wp_user_id)" );
    }
    if ( ! in_array( 'blood_group_idx', $index_names, true ) ) {
        $wpdb->query( "ALTER TABLE {$member_table} ADD INDEX blood_group_idx (blood_group)" );
    }
    if ( ! in_array( 'program_id_idx', $index_names, true ) ) {
        $wpdb->query( "ALTER TABLE {$member_table} ADD INDEX program_id_idx (program_id)" );
    }
}

/**
 * Ensures present_upazila and upazila columns exist on member, application, rawdata
 */
function batchmate_ensure_location_columns() {
    global $wpdb;
    static $done = false;
    if ( $done ) return;
    $done = true;

    $tables = [
        $wpdb->prefix . 'batchmate_member',
        $wpdb->prefix . 'batchmate_application',
        $wpdb->prefix . 'batchmate_rawdata',
    ];
    foreach ( $tables as $table ) {
        $cols = $wpdb->get_col( "SHOW COLUMNS FROM {$table}" );
        if ( empty( $cols ) ) continue;

        // Ensure district columns exist before country/upazila columns that reference them
        if ( ! in_array( 'present_district', $cols, true ) ) {
            $wpdb->query( "ALTER TABLE {$table} ADD COLUMN present_district varchar(100) DEFAULT NULL AFTER present_address" );
        }
        if ( ! in_array( 'permanent_district', $cols, true ) ) {
            $wpdb->query( "ALTER TABLE {$table} ADD COLUMN permanent_district varchar(100) DEFAULT NULL AFTER permanent_address" );
        }

        // present_upazila / upazila (permanent)
        if ( ! in_array( 'present_upazila', $cols, true ) ) {
            $wpdb->query( "ALTER TABLE {$table} ADD COLUMN present_upazila varchar(100) DEFAULT NULL AFTER present_district" );
        }
        if ( ! in_array( 'upazila', $cols, true ) ) {
            $wpdb->query( "ALTER TABLE {$table} ADD COLUMN upazila varchar(100) DEFAULT NULL AFTER permanent_district" );
        }

        // Columns that may be missing in rawdata and application from older installs
        if ( ! in_array( 'name_bn', $cols, true ) ) {
            $wpdb->query( "ALTER TABLE {$table} ADD COLUMN name_bn varchar(255) DEFAULT NULL AFTER name" );
        }
        if ( ! in_array( 'religion', $cols, true ) ) {
            $wpdb->query( "ALTER TABLE {$table} ADD COLUMN religion varchar(50) DEFAULT NULL AFTER gender" );
        }
        if ( ! in_array( 'present_country', $cols, true ) ) {
            $wpdb->query( "ALTER TABLE {$table} ADD COLUMN present_country varchar(100) DEFAULT 'Bangladesh' AFTER present_district" );
        }
        if ( ! in_array( 'permanent_country', $cols, true ) ) {
            $wpdb->query( "ALTER TABLE {$table} ADD COLUMN permanent_country varchar(100) DEFAULT 'Bangladesh' AFTER permanent_district" );
        }
        if ( ! in_array( 'organization', $cols, true ) ) {
            $wpdb->query( "ALTER TABLE {$table} ADD COLUMN organization varchar(255) DEFAULT NULL AFTER employment_position" );
        }
        if ( ! in_array( 'industry', $cols, true ) ) {
            $wpdb->query( "ALTER TABLE {$table} ADD COLUMN industry varchar(100) DEFAULT NULL AFTER organization" );
        }
        if ( ! in_array( 'work_experience', $cols, true ) ) {
            $wpdb->query( "ALTER TABLE {$table} ADD COLUMN work_experience varchar(100) DEFAULT NULL AFTER industry" );
        }
        if ( ! in_array( 'previous_organizations', $cols, true ) ) {
            $wpdb->query( "ALTER TABLE {$table} ADD COLUMN previous_organizations text DEFAULT NULL AFTER work_experience" );
        }
        if ( ! in_array( 'about_me', $cols, true ) ) {
            $wpdb->query( "ALTER TABLE {$table} ADD COLUMN about_me text DEFAULT NULL AFTER employment_address" );
        }
        if ( ! in_array( 'skills_json', $cols, true ) ) {
            $wpdb->query( "ALTER TABLE {$table} ADD COLUMN skills_json text DEFAULT NULL AFTER about_me" );
        }
        if ( ! in_array( 'board', $cols, true ) ) {
            $wpdb->query( "ALTER TABLE {$table} ADD COLUMN board varchar(100) DEFAULT NULL AFTER student_id" );
        }
        if ( ! in_array( 'roll_no', $cols, true ) ) {
            $wpdb->query( "ALTER TABLE {$table} ADD COLUMN roll_no varchar(50) DEFAULT NULL AFTER board" );
        }
        if ( ! in_array( 'registration_no', $cols, true ) ) {
            $wpdb->query( "ALTER TABLE {$table} ADD COLUMN registration_no varchar(50) DEFAULT NULL AFTER roll_no" );
        }
        if ( ! in_array( 'edu_group', $cols, true ) ) {
            $wpdb->query( "ALTER TABLE {$table} ADD COLUMN edu_group varchar(50) DEFAULT NULL AFTER registration_no" );
        }
        if ( ! in_array( 'institution', $cols, true ) ) {
            $wpdb->query( "ALTER TABLE {$table} ADD COLUMN institution varchar(255) DEFAULT NULL AFTER edu_group" );
        }

        // Expand status column if it was varchar(20) to prevent 'Data too long' errors on long event names
        $status_col = $wpdb->get_row( "SHOW COLUMNS FROM {$table} LIKE 'status'" );
        if ( ! empty( $status_col ) && stripos( $status_col->Type, 'varchar(20)' ) !== false ) {
            $wpdb->query( "ALTER TABLE {$table} MODIFY COLUMN status varchar(100) NOT NULL DEFAULT 'pending'" );
        }

        // Ensure submission_date is nullable to prevent strict MySQL zero-date errors
        $sub_col = $wpdb->get_row( "SHOW COLUMNS FROM {$table} LIKE 'submission_date'" );
        if ( ! empty( $sub_col ) && strtoupper( $sub_col->Null ?? '' ) === 'NO' ) {
            $wpdb->query( "ALTER TABLE {$table} MODIFY COLUMN submission_date datetime DEFAULT NULL" );
        }
    }
}

/**
 * Ensures has_saree column exists on batchmate_event table
 */
function batchmate_ensure_saree_column() {
    global $wpdb;
    static $done = false;
    if ( $done ) return;
    $done = true;
    $event_table = $wpdb->prefix . 'batchmate_event';
    $col = $wpdb->get_col( "SHOW COLUMNS FROM {$event_table} LIKE 'has_saree'" );
    if ( empty( $col ) ) {
        $wpdb->query( "ALTER TABLE {$event_table} ADD COLUMN has_saree tinyint(1) NOT NULL DEFAULT 0 AFTER has_tshirt" );
    }
    $col_pm = $wpdb->get_col( "SHOW COLUMNS FROM {$event_table} LIKE 'payment_methods'" );
    if ( empty( $col_pm ) ) {
        $wpdb->query( "ALTER TABLE {$event_table} ADD COLUMN payment_methods text DEFAULT NULL AFTER has_saree" );
    }
    $col_dr = $wpdb->get_col( "SHOW COLUMNS FROM {$event_table} LIKE 'direct_registration'" );
    if ( empty( $col_dr ) ) {
        $wpdb->query( "ALTER TABLE {$event_table} ADD COLUMN direct_registration tinyint(1) NOT NULL DEFAULT 0 AFTER allow_non_members" );
    }
    $col_drt = $wpdb->get_col( "SHOW COLUMNS FROM {$event_table} LIKE 'direct_reg_text'" );
    if ( empty( $col_drt ) ) {
        $wpdb->query( "ALTER TABLE {$event_table} ADD COLUMN direct_reg_text varchar(255) NOT NULL DEFAULT '' AFTER direct_registration" );
    }
    $app_table = $wpdb->prefix . 'batchmate_event_application';
    $app_col = $wpdb->get_col( "SHOW COLUMNS FROM {$app_table} LIKE 'tshirt_size'" );
    if ( ! empty( $app_col ) ) {
        $wpdb->query( "ALTER TABLE {$app_table} MODIFY COLUMN tshirt_size varchar(20) NOT NULL DEFAULT ''" );
    }
}