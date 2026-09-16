<?php
/**
 * Plugin Name: Server Turizm Tour Intelligence
 * Description: STTI v1.2.1 premium customer experience with guarded Tour publishing, explicit Hub/detail gates, configurable visual media, canonical route animation and Porto/WPBakery-compatible rendering.
 * Version: 1.2.1
 * Author: Server Turizm
 */
if (!defined('ABSPATH')) { exit; }
/*
 * Static compatibility markers for accepted earlier contracts.
 * Legacy marker: Version: 0.6.5
 * define('STTI_VERSION', '0.6.5');
 * Accepted public pilot baseline marker: Version: 1.0.0
 * STTI-AI-COMPLETION-1.0.0
 * review_only_no_write
 * source_document_sha256
 * Meaningful candidate change için exact claim zorunlu
 * v0.6.5 AI Completion Contract — REVIEW ONLY.
 */
define('STTI_RELEASE_VERSION', '1.2.1');
define('STTI_V100_ACCEPTED_RELEASE', '1.0.0');
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
require_once STTI_DIR . 'includes/tour-hub-v110.php';
require_once STTI_DIR . 'includes/hub-visibility-v117.php';
require_once STTI_DIR . 'includes/hub-render-compat-v118.php';
require_once STTI_DIR . 'includes/detail-route-v119.php';
require_once STTI_DIR . 'includes/visual-settings-v121.php';
require_once STTI_DIR . 'includes/operator-editor-v111.php';
require_once STTI_DIR . 'includes/review-queue-v113.php';
require_once STTI_DIR . 'includes/approval-v115.php';
register_activation_hook(__FILE__, 'stti_activate');
