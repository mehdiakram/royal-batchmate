<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Royal BatchMate - Shortcodes Orchestrator
 */
$shortcodes_dir = plugin_dir_path( __FILE__ ) . 'shortcodes/';

require_once $shortcodes_dir . 'shortcode-ec.php';
require_once $shortcodes_dir . 'shortcode-members-count.php';
require_once $shortcodes_dir . 'shortcode-event-form.php';
require_once $shortcodes_dir . 'shortcode-event-list.php';
