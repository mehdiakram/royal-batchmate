<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * 1. Adds the rewrite rule to handle /member/{member_number}/
 *    Guarded to prevent fatal redeclaration if the same function exists in the active theme.
 */
if ( ! function_exists( 'custom_member_rewrite_rule' ) ) {
    function custom_member_rewrite_rule() {
        add_rewrite_rule(
            '^member/([^/]+)/?$',
            'index.php?member_number=$matches[1]',
            'top'
        );
    }
    add_action( 'init', 'custom_member_rewrite_rule' );
}

/**
 * 2. Whitelists the 'member_number' query variable
 *    Guarded to prevent fatal redeclaration if the same function exists in the active theme.
 */
if ( ! function_exists( 'add_custom_query_var' ) ) {
    function add_custom_query_var( $vars ) {
        $vars[] = 'member_number';
        return $vars;
    }
    add_filter( 'query_vars', 'add_custom_query_var' );
}

/**
 * 3. Loads the single-member.php template for the custom member URL
 *    Plugin template takes priority over theme template.
 *    Guarded to prevent fatal redeclaration if the same function exists in the active theme.
 */
if ( ! function_exists( 'load_custom_member_template' ) ) {
    function load_custom_member_template( $template ) {
        global $wp_query;

        if ( isset( $wp_query->query_vars['member_number'] ) ) {
            // Prevent WordPress from treating this as a 404 immediately
            $wp_query->is_404 = false;

            $custom_template = ROYAL_BATCHMATE_PLUGIN_DIR . 'templates/single-member.php';

            if ( file_exists( $custom_template ) ) {
                return $custom_template;
            }
        }
        return $template;
    }
    add_filter( 'template_include', 'load_custom_member_template' );
}