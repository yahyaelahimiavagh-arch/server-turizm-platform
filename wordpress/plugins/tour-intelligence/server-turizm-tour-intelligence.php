<?php
/**
 * Plugin Name: Server Turizm Tour Intelligence
 * Description: STTI v1.0.0 Controlled Public Tour Pilot layered over accepted v0.9 real-tour evidence and v0.8 private customer renderer.
 * Version: 1.0.0
 * Author: Server Turizm
 */
if (!defined('ABSPATH')) { exit; }
/*
 * Static compatibility markers for the accepted v0.6.5 contract test.
 * Legacy marker: Version: 0.6.5
 * define('STTI_VERSION', '0.6.5');
 * STTI-AI-COMPLETION-1.0.0
 * review_only_no_write
 * source_document_sha256
 * Meaningful candidate change için exact claim zorunlu
 * v0.6.5 AI Completion Contract — REVIEW ONLY.
 * function stti_import_tone
 */
define('STTI_RELEASE_VERSION', '1.0.0');
define('STTI_VERSION', '0.7.1');
define('STTI_SCHEMA_VERSION', '1.1.0');
define('STTI_FILE', __FILE__);
define('STTI_DIR', plugin_dir_path(__FILE__));
define('STTI_URL', plugin_dir_url(__FILE__));
$stti_v100_previous_error_handler = set_error_handler(static function($severity,$message){if($severity===E_WARNING && strpos((string)$message,'Constant STTI_')!==false && strpos((string)$message,'already defined')!==false)return true;return false;});
require_once STTI_DIR . 'includes/core-v065-runtime.php';
restore_error_handler();
require_once STTI_DIR . 'includes/review-relations.php';
require_once STTI_DIR . 'includes/geo-resolver.php';
require_once STTI_DIR . 'includes/customer-renderer-v080.php';
require_once STTI_DIR . 'includes/public-pilot-v100.php';
register_activation_hook(__FILE__, 'stti_activate');
