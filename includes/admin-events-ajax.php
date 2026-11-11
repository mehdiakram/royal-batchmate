<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Royal BatchMate - Admin Events AJAX Orchestrator
 */
$events_ajax_dir = plugin_dir_path( __FILE__ ) . 'events/ajax/';

require_once $events_ajax_dir . 'ajax-events-crud.php';
require_once $events_ajax_dir . 'ajax-events-applications.php';
require_once $events_ajax_dir . 'ajax-events-finance.php';
require_once $events_ajax_dir . 'ajax-events-card.php';
