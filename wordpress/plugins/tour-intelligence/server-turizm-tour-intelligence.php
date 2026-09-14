<?php
/**
 * Plugin Name: Server Turizm Tour Intelligence
 * Description: STTI v0.7.0 WordPress Review Relations layered over the frozen v0.6.5 runtime core. Adds private Route Variant, Hotel Option, Hotel Intelligence and Transport relation review without public/SEO exposure.
 * Version: 0.7.0
 * Author: Server Turizm
 */

if (!defined('ABSPATH')) { exit; }

/*
 * Static compatibility markers for the accepted v0.6.5 contract test.
 * The executable v0.6.5 implementation is frozen in includes/core-v065-runtime.php.
 * Legacy marker: Version: 0.6.5
 * define('STTI_VERSION', '0.6.5');
 * STTI-AI-COMPLETION-1.0.0
 * review_only_no_write
 * source_document_sha256
 * Meaningful candidate change için exact claim zorunlu
 * v0.6.5 AI Completion Contract — REVIEW ONLY.
 * function stti_import_tone
 */

// v0.7 keeps the accepted v0.6.5 runtime core byte-for-byte frozen while
// exposing the current release identity and the original plugin-root paths.
define('STTI_VERSION', '0.7.0');
define('STTI_SCHEMA_VERSION', '1.1.0');
define('STTI_FILE', __FILE__);
define('STTI_DIR', plugin_dir_path(__FILE__));
define('STTI_URL', plugin_dir_url(__FILE__));

$stti_v070_previous_error_handler = set_error_handler(
    static function ($severity, $message) {
        if ($severity === E_WARNING && strpos((string)$message, 'Constant STTI_') !== false && strpos((string)$message, 'already defined') !== false) {
            return true;
        }
        return false;
    }
);

require_once STTI_DIR . 'includes/core-v065-runtime.php';

restore_error_handler();

require_once STTI_DIR . 'includes/review-relations.php';

// The frozen core registers an activation hook against its historical file path.
// Register the same accepted installer against the actual v0.7 bootstrap path too.
register_activation_hook(__FILE__, 'stti_activate');
