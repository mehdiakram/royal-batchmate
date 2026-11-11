<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Royal BatchMate - Database & User Migrations
 */
// LICENSE DB MIGRATION (v3.0 → v3.1+)
// Runs once on plugins_loaded to migrate old option formats.
// ============================================================
add_action( 'plugins_loaded', 'rbm_migrate_license_options', 1 );
function rbm_migrate_license_options(): void {
    // 1. Migrate old plain-text expiry → encrypted expiry
    $old_expiry = get_option( 'rbm_license_expiry' );
    if ( $old_expiry && ! get_option( 'rbm_license_expiry_enc' ) ) {
        if ( function_exists( 'rbm_encrypt_expiry' ) ) {
            $enc = rbm_encrypt_expiry( $old_expiry );
            if ( $enc ) {
                update_option( 'rbm_license_expiry_enc', $enc );
            }
        }
        delete_option( 'rbm_license_expiry' );
    }

    // 2. Migrate old JSON-string cache → array format
    $raw_cache = get_option( 'rbm_license_cache' );
    if ( is_string( $raw_cache ) && ! empty( $raw_cache ) ) {
        $decoded = json_decode( $raw_cache, true );
        if ( is_array( $decoded ) ) {
            update_option( 'rbm_license_cache', $decoded );
        }
    }
}

// ============================================================

// One-time migration: Subscriber → Member role
function batchmate_migrate_subscribers_to_member_role(): void {
    if ( get_option( 'batchmate_member_role_v1_migrated' ) ) return;
    global $wpdb;
    $linked_user_ids = $wpdb->get_col(
        "SELECT wp_user_id FROM {$wpdb->prefix}batchmate_member WHERE wp_user_id > 0"
    );
    foreach ( $linked_user_ids as $uid ) {
        $user = get_user_by( 'id', (int) $uid );
        if ( $user && in_array( 'subscriber', (array) $user->roles, true ) ) {
            $user->set_role( 'batchmate_member' );
        }
    }
    update_option( 'batchmate_member_role_v1_migrated', true );
}
add_action( 'admin_init', 'batchmate_migrate_subscribers_to_member_role' );

// One-time migration: photo_url → user_meta
function batchmate_migrate_member_photos_to_usermeta(): void {
    if ( get_option( 'batchmate_member_photo_meta_v1_migrated' ) ) return;
    global $wpdb;
    $members = $wpdb->get_results(
        "SELECT wp_user_id, photo_url FROM {$wpdb->prefix}batchmate_member
         WHERE wp_user_id > 0 AND photo_url != '' AND photo_url IS NOT NULL"
    );
    foreach ( $members as $m ) {
        if ( strpos( $m->photo_url, 'noprofile.png' ) !== false ) continue;
        update_user_meta( (int) $m->wp_user_id, 'batchmate_member_photo', $m->photo_url );
    }
    update_option( 'batchmate_member_photo_meta_v1_migrated', true );
}
add_action( 'admin_init', 'batchmate_migrate_member_photos_to_usermeta' );

// ============================================================