<?php
/**
 * Royal BatchMate — License System
 * Ed25519 asymmetric signature verification.
 * Modelled after Royal Sentinel's license architecture.
 *
 * Software slug: royalbatchmate
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// ============================================================
// CONFIGURATION
// ============================================================
define( 'ROYAL_BATCHMATE_LICENSE_SOFTWARE', 'royalbatchmate' );
define( 'ROYAL_BATCHMATE_LICENSE_PUBLIC_KEY', 'vE7PkX4ho5A4ZVWH5KwToDcv1cPIY6FJsNU4tDjbNIw=' );
define( 'ROYAL_BATCHMATE_AUTH_URL',       'https://auth.royaltechbd.com/api/verify' );
define( 'ROYAL_BATCHMATE_VERSION_URL',    'https://auth.royaltechbd.com/api/license/version' );
define( 'ROYAL_BATCHMATE_ACTIVITIES_URL', 'https://auth.royaltechbd.com/activities' );

// ============================================================
// HELPER: Base Domain Extractor
// Handles ccSLDs (e.g. .co.uk, .com.bd) using ccsld.json
// ============================================================

/**
 * Get the base domain name from a host string.
 */
function rbm_get_base_domain( string $host = '' ): string {
    if ( empty( $host ) ) {
        $host = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? '';
        if ( empty( $host ) && function_exists( 'home_url' ) ) {
            $host = (string) parse_url( home_url(), PHP_URL_HOST );
        }
    }
    if ( strpos( $host, ':' ) !== false ) {
        $host = explode( ':', $host )[0];
    }

    static $all_ccslds = null;

    if ( $all_ccslds === null ) {
        $all_ccslds = [];
        $ccsld_path = ROYAL_BATCHMATE_PLUGIN_DIR . 'license/ccsld.json';

        if ( file_exists( $ccsld_path ) ) {
            $ccsld_data = json_decode( file_get_contents( $ccsld_path ), true );
            if ( is_array( $ccsld_data ) ) {
                foreach ( $ccsld_data as $country_slds ) {
                    if ( is_array( $country_slds ) ) {
                        $all_ccslds = array_merge( $all_ccslds, $country_slds );
                    }
                }
                usort( $all_ccslds, fn( $a, $b ) => strlen( $b ) - strlen( $a ) );
            }
        }
    }

    $host = strtolower( trim( $host ) );

    if ( ! empty( $all_ccslds ) ) {
        foreach ( $all_ccslds as $sld ) {
            if ( preg_match( '/\.' . preg_quote( $sld, '/' ) . '$/i', $host ) ) {
                $host_without_sld = substr( $host, 0, strlen( $host ) - strlen( $sld ) - 1 );
                $parts            = explode( '.', $host_without_sld );
                $domain_part      = end( $parts );
                return $domain_part . '.' . $sld;
            }
        }
    }

    $host  = preg_replace( '/^www\./', '', $host );
    $parts = explode( '.', $host );
    $count = count( $parts );

    if ( $count >= 2 ) {
        return $parts[ $count - 2 ] . '.' . $parts[ $count - 1 ];
    }

    return $host;
}

// ============================================================
// HELPER: Expiry Encryption (offline fallback for non-lifetime)
// ============================================================

function rbm_encrypt_expiry( string $plain ): string|false {
    if ( ! function_exists( 'openssl_encrypt' ) ) {
        return base64_encode( $plain );
    }
    $key = substr( wp_salt( 'auth' ), 0, 32 );
    $iv  = substr( wp_salt( 'secure_auth' ), 0, 16 );
    $enc = openssl_encrypt( $plain, 'AES-256-CBC', $key, 0, $iv );
    return $enc ? base64_encode( $enc ) : false;
}

function rbm_decrypt_expiry( string $encrypted ): string|false {
    if ( ! function_exists( 'openssl_decrypt' ) ) {
        return base64_decode( $encrypted );
    }
    $key = substr( wp_salt( 'auth' ), 0, 32 );
    $iv  = substr( wp_salt( 'secure_auth' ), 0, 16 );
    return openssl_decrypt( base64_decode( $encrypted ), 'AES-256-CBC', $key, 0, $iv );
}

// ============================================================
// CORE: Ed25519 Signature Verifier (Pure local — no HTTP)
// ============================================================

/**
 * Verify a locally-cached signed license response.
 *
 * Checks:
 *  1. Ed25519 signature   (tamper prevention)
 *  2. Domain lock         (cryptographically bound to signature)
 *  3. Expiry + grace      (offline-safe)
 *
 * @param array|null $localCache  Decoded JSON from auth.royaltechbd.com
 * @return bool
 */
function royal_batchmate_verify_license_data( ?array $localCache ): bool {
    if ( ! $localCache || ! isset( $localCache['signature'], $localCache['license_data'] ) ) {
        return false;
    }

    // 1. Ed25519 Signature Verification
    try {
        $publicKey = base64_decode( ROYAL_BATCHMATE_LICENSE_PUBLIC_KEY );
        $signature = base64_decode( $localCache['signature'] );
        $message   = json_encode( $localCache['license_data'] );

        if ( ! sodium_crypto_sign_verify_detached( $signature, $message, $publicKey ) ) {
            return false;
        }
    } catch ( \Exception $e ) {
        return false;
    }

    $data = $localCache['license_data'];

    // 2. Domain Lock
    $host = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? '';
    if ( strpos( $host, ':' ) !== false ) {
        $host = explode( ':', $host )[0];
    }
    $currentDomain       = rbm_get_base_domain( $host );
    $allowedLocalDomains = [ 'localhost', '127.0.0.1' ];

    if (
        ! in_array( $currentDomain, $allowedLocalDomains, true ) &&
        strpos( $currentDomain, '.test' ) === false &&
        strpos( $currentDomain, '.local' ) === false &&
        $currentDomain !== ( $data['domain'] ?? '' )
    ) {
        return false;
    }

    // 3. Expiry Check with Grace Period
    $now       = time();
    $expiresAt = ! empty( $data['expires_at'] ) ? strtotime( $data['expires_at'] ) : null;
    $type      = $data['type'] ?? 'lifetime';

    if ( $type === 'lifetime' ) {
        return ( $data['status'] ?? '' ) === 'active';
    }

    if ( $expiresAt && $now > $expiresAt ) {
        $graceDays = ( $type === 'yearly' ) ? 30 : 3;
        return $now <= ( $expiresAt + ( $graceDays * 24 * 60 * 60 ) );
    }

    return ( $data['status'] ?? '' ) === 'active';
}

// ============================================================
// MAIN LICENSE CHECK — ⚡ ZERO HTTP on normal page loads
// Pure local Ed25519 verification only.
// HTTP re-validation happens ONLY in the daily WP-Cron job.
// ============================================================

/**
 * Main License Validity Check.
 *
 * Flow:
 *  1. Fast-path: check stored status flag (microseconds)
 *  2. Load locally cached signed response from DB
 *  3. Verify Ed25519 signature locally — NO server needed
 *  4. Return result immediately
 *
 * @return bool
 */
function royal_batchmate_is_licensed(): bool {
    // Fast path
    if ( get_option( 'rbm_license_status', 'inactive' ) !== 'active' ) {
        return false;
    }

    $licenseKey = get_option( 'rbm_license_key', '' );
    if ( empty( $licenseKey ) ) {
        return false;
    }

    // Load locally cached signed response
    $localCache = get_option( 'rbm_license_cache', [] );
    if ( empty( $localCache ) ) {
        return false;
    }

    // Verify Ed25519 signature locally — no HTTP, no blocking
    return royal_batchmate_verify_license_data( $localCache );
}

// ============================================================
// CRON: Daily License Ping (the ONLY place HTTP calls happen)
// ============================================================

add_action( 'rbm_daily_license_ping', 'royal_batchmate_daily_license_ping' );

function royal_batchmate_daily_license_ping(): void {
    $licenseKey = get_option( 'rbm_license_key', '' );
    if ( empty( $licenseKey ) ) return;

    $localCache = get_option( 'rbm_license_cache', [] );
    $localValid = ! empty( $localCache ) ? royal_batchmate_verify_license_data( $localCache ) : false;

    // ── 1. Lightweight Version Check (GET, ~50 bytes) ──────────────────
    if ( $localValid ) {
        $cachedVersion = (int) get_option( 'rbm_license_version', 0 );
        $versionUrl    = add_query_arg( [
            'k' => $licenseKey,
            's' => ROYAL_BATCHMATE_LICENSE_SOFTWARE,
            'v' => $cachedVersion,
        ], ROYAL_BATCHMATE_VERSION_URL );

        $vResponse = wp_remote_get( $versionUrl, [
            'timeout'   => 5,
            'sslverify' => false,
        ] );

        if ( ! is_wp_error( $vResponse ) && wp_remote_retrieve_response_code( $vResponse ) === 200 ) {
            $vData = json_decode( wp_remote_retrieve_body( $vResponse ), true );
            if ( isset( $vData['version'] ) ) {
                update_option( 'rbm_license_version', (int) $vData['version'] );
            }
            if ( ! ( $vData['changed'] ?? false ) ) {
                // Version hasn't changed and license is active — skip sync! ✅
                return;
            }
        }
    }

    $host = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? home_url();
    $host = parse_url( $host, PHP_URL_HOST ) ?: $host;
    if ( strpos( $host, ':' ) !== false ) {
        $host = explode( ':', $host )[0];
    }
    $domain = rbm_get_base_domain( $host );

    global $wp_version;
    $active_theme = wp_get_theme();
    $theme_name   = $active_theme ? $active_theme->get( 'Name' ) : 'Unknown';
    $user_agent   = sprintf(
        'WordPress/%s; PHP/%s; Theme/%s; Url/%s',
        $wp_version, PHP_VERSION, $theme_name, home_url()
    );

    // ── 2. Full Sync (POST /api/verify) ────────────────────────────────
    $response = wp_remote_post( ROYAL_BATCHMATE_AUTH_URL, [
        'timeout'    => 15,
        'user-agent' => $user_agent,
        'sslverify'  => false,
        'body'       => [
            'software'    => ROYAL_BATCHMATE_LICENSE_SOFTWARE,
            'license_key' => $licenseKey,
            'domain'      => $domain,
        ],
    ] );

    // Server unreachable — fall back to local cache
    if ( is_wp_error( $response ) ) {
        if ( ! $localValid ) {
            $encExpiry = get_option( 'rbm_license_expiry_enc' );
            if ( $encExpiry ) {
                $expiryStr   = rbm_decrypt_expiry( $encExpiry );
                $expiryTime  = $expiryStr ? strtotime( $expiryStr ) : 0;
                $graceExpiry = $expiryTime + ( 3 * 24 * 60 * 60 );
                if ( time() > $graceExpiry ) {
                    update_option( 'rbm_license_status', 'inactive' );
                    return;
                }
            }
        }
        return; // Keep current status as-is
    }

    $statusCode = wp_remote_retrieve_response_code( $response );
    $body       = json_decode( wp_remote_retrieve_body( $response ), true );

    // Server 5xx error — NEVER wipe local cache on server downtime! ✅
    if ( $statusCode >= 500 ) {
        return;
    }

    // Server explicitly rejected the license
    if ( isset( $body['success'] ) && $body['success'] === false ) {
        $msg = $body['message'] ?? '';
        $definitelyInvalid = stripos( $msg, 'suspended' ) !== false ||
                             stripos( $msg, 'expired' ) !== false   ||
                             stripos( $msg, 'invalid' ) !== false   ||
                             stripos( $msg, 'blocked' ) !== false   ||
                             $statusCode === 403                    ||
                             $statusCode === 404;

        if ( $definitelyInvalid ) {
            update_option( 'rbm_license_status', 'inactive' );
            delete_option( 'rbm_license_cache' );
            return;
        }

        // Ambiguous rejection and local cache valid — keep working
        if ( $localValid ) {
            return;
        }
        update_option( 'rbm_license_status', 'inactive' );
        return;
    }

    // Server returned a valid signed response
    if ( isset( $body['success'] ) && $body['success'] === true && isset( $body['signature'] ) ) {
        if ( ! royal_batchmate_verify_license_data( $body ) ) {
            update_option( 'rbm_license_status', 'inactive' );
            delete_option( 'rbm_license_cache' );
            return;
        }

        update_option( 'rbm_license_cache',      $body );
        update_option( 'rbm_license_last_check', gmdate( 'Y-m-d H:i:s' ) );
        update_option( 'rbm_license_status',     'active' );
        if ( isset( $body['license_data']['version'] ) ) {
            update_option( 'rbm_license_version', (int) $body['license_data']['version'] );
        }

        // Store encrypted expiry for offline fallback
        $expiresAt = $body['license_data']['expires_at'] ?? null;
        if ( $expiresAt ) {
            $enc = rbm_encrypt_expiry( $expiresAt );
            if ( $enc ) {
                update_option( 'rbm_license_expiry_enc', $enc );
            }
        } else {
            delete_option( 'rbm_license_expiry_enc' );
        }

        // Non-critical activity ping (fire-and-forget)
        wp_remote_post( ROYAL_BATCHMATE_ACTIVITIES_URL, [
            'timeout'   => 5,
            'blocking'  => false,
            'sslverify' => false,
            'body'      => [
                'software'    => ROYAL_BATCHMATE_LICENSE_SOFTWARE,
                'license_key' => $licenseKey,
                'domain'      => $domain,
                'action'      => 'ping',
            ],
        ] );
    }
}

// ============================================================
// AJAX: Activate License
// ============================================================

add_action( 'wp_ajax_rbm_activate_license', 'rbm_ajax_activate_license' );

function rbm_ajax_activate_license(): void {
    // 1. Nonce
    if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'rbm_license_nonce' ) ) {
        wp_send_json_error( [ 'message' => 'Security check failed.' ] );
    }

    // 2. Permission
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( [ 'message' => 'You do not have permission to perform this action.' ] );
    }

    // 3. License Key
    $licenseKey = isset( $_POST['license_key'] ) ? sanitize_text_field( wp_unslash( $_POST['license_key'] ) ) : '';
    if ( empty( $licenseKey ) ) {
        wp_send_json_error( [ 'message' => 'License key cannot be empty.' ] );
    }

    // 4. Clear stale state
    delete_option( 'rbm_license_cache' );
    delete_option( 'rbm_license_status' );
    delete_option( 'rbm_license_last_check' );
    delete_option( 'rbm_license_expiry_enc' );
    delete_transient( 'rbm_license_check_lock' );

    // 5. Determine domain
    $host = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? '';
    if ( strpos( $host, ':' ) !== false ) {
        $host = explode( ':', $host )[0];
    }
    $domain = rbm_get_base_domain( $host );

    // 6. Call auth server
    global $wp_version;
    $active_theme = wp_get_theme();
    $theme_name   = $active_theme ? $active_theme->get( 'Name' ) : 'Unknown';
    $user_agent   = sprintf(
        'WordPress/%s; PHP/%s; Theme/%s; Url/%s',
        $wp_version, PHP_VERSION, $theme_name, home_url()
    );

    $response = wp_remote_post( ROYAL_BATCHMATE_AUTH_URL, [
        'timeout'    => 15,
        'user-agent' => $user_agent,
        'sslverify'  => false,
        'body'       => [
            'software'    => ROYAL_BATCHMATE_LICENSE_SOFTWARE,
            'license_key' => $licenseKey,
            'domain'      => $domain,
        ],
    ] );

    // 7. Connection error
    if ( is_wp_error( $response ) ) {
        wp_send_json_error( [ 'message' => 'Could not connect to the license server. Please try again later.' ] );
    }

    $data = json_decode( wp_remote_retrieve_body( $response ), true );

    // 8. Server rejected
    if ( ! isset( $data['success'] ) || $data['success'] !== true ) {
        $server_msg = $data['message'] ?? 'Invalid or incorrect license key.';
        wp_send_json_error( [ 'message' => $server_msg ] );
    }

    // 9. Ed25519 signature verification
    if ( ! royal_batchmate_verify_license_data( $data ) ) {
        wp_send_json_error( [ 'message' => 'Invalid server response. Possible tampering detected.' ] );
    }

    // 10. Persist license state
    update_option( 'rbm_license_key',        $licenseKey );
    update_option( 'rbm_license_cache',      $data );
    update_option( 'rbm_license_status',     'active' );
    update_option( 'rbm_license_last_check', gmdate( 'Y-m-d H:i:s' ) );

    // Store encrypted expiry for offline fallback
    $expiresAt = $data['license_data']['expires_at'] ?? null;
    if ( $expiresAt ) {
        $enc = rbm_encrypt_expiry( $expiresAt );
        if ( $enc ) {
            update_option( 'rbm_license_expiry_enc', $enc );
        }
    } else {
        delete_option( 'rbm_license_expiry_enc' );
    }

    wp_send_json_success( [
        'message'  => 'License activated successfully!',
        'redirect' => admin_url( 'admin.php?page=batchmate_member_management' ),
    ] );
}

// ============================================================
// admin-post: Deactivate License
// ============================================================

add_action( 'admin_post_rbm_deactivate_license', 'rbm_handle_license_deactivation' );

function rbm_handle_license_deactivation(): void {
    // 1. Nonce
    if ( ! isset( $_REQUEST['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_REQUEST['_wpnonce'] ) ), 'rbm_license_deactivation_nonce' ) ) {
        wp_die( 'Security check failed.' );
    }

    // 2. Permission
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( 'You do not have permission to perform this action.' );
    }

    // 3. Full state reset
    delete_option( 'rbm_license_key' );
    delete_option( 'rbm_license_cache' );
    delete_option( 'rbm_license_status' );
    delete_option( 'rbm_license_last_check' );
    delete_option( 'rbm_license_expiry_enc' );
    delete_transient( 'rbm_license_check_lock' );

    // 4. Redirect
    wp_redirect( add_query_arg(
        [ 'status' => 'deactivated' ],
        admin_url( 'admin.php?page=batchmate_license' )
    ) );
    exit;
}

// ============================================================
// OLD COMPAT: keep rbm_activate_license() callable from old page
// ============================================================

function royal_batchmate_activate_license( string $licenseKey ): array {
    $host = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? '';
    if ( strpos( $host, ':' ) !== false ) {
        $host = explode( ':', $host )[0];
    }
    $domain   = rbm_get_base_domain( $host );
    $response = wp_remote_post( ROYAL_BATCHMATE_AUTH_URL, [
        'timeout'   => 15,
        'sslverify' => false,
        'body'      => [
            'software'    => ROYAL_BATCHMATE_LICENSE_SOFTWARE,
            'license_key' => $licenseKey,
            'domain'      => $domain,
        ],
    ] );

    if ( is_wp_error( $response ) ) {
        return [ 'success' => false, 'message' => $response->get_error_message() ];
    }

    $data = json_decode( wp_remote_retrieve_body( $response ), true );

    if ( empty( $data['success'] ) ) {
        return [ 'success' => false, 'message' => $data['message'] ?? 'License verification failed.' ];
    }

    if ( ! royal_batchmate_verify_license_data( $data ) ) {
        return [ 'success' => false, 'message' => 'Invalid server response. Possible tampering detected.' ];
    }

    update_option( 'rbm_license_key',        $licenseKey );
    update_option( 'rbm_license_status',     'active' );
    update_option( 'rbm_license_cache',      $data );
    update_option( 'rbm_license_last_check', gmdate( 'Y-m-d H:i:s' ) );

    $expiresAt = $data['license_data']['expires_at'] ?? null;
    if ( $expiresAt ) {
        $enc = rbm_encrypt_expiry( $expiresAt );
        if ( $enc ) update_option( 'rbm_license_expiry_enc', $enc );
    } else {
        delete_option( 'rbm_license_expiry_enc' );
    }

    return [ 'success' => true, 'message' => 'License activated successfully.', 'data' => $data['license_data'] ];
}

function royal_batchmate_deactivate_license(): void {
    delete_option( 'rbm_license_key' );
    delete_option( 'rbm_license_status' );
    delete_option( 'rbm_license_cache' );
    delete_option( 'rbm_license_last_check' );
    delete_option( 'rbm_license_expiry_enc' );
}

// ============================================================
// LICENSE STATUS HELPER (for admin UI display)
// ============================================================

function royal_batchmate_get_license_info(): array {
    $cache = get_option( 'rbm_license_cache', [] );
    $data  = $cache['license_data'] ?? [];

    return [
        'key'        => get_option( 'rbm_license_key', '' ),
        'status'     => get_option( 'rbm_license_status', 'inactive' ),
        'type'       => $data['type']       ?? 'unknown',
        'expires_at' => $data['expires_at'] ?? null,
        'domain'     => $data['domain']     ?? '',
        'synced_at'  => get_option( 'rbm_license_last_check', '' ),
        'valid'      => ! empty( $cache ) ? royal_batchmate_verify_license_data( $cache ) : false,
    ];
}
