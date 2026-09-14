<?php
// Minimal WordPress stubs to prove a normal frontend include stops before STPI classes load.
define('ABSPATH', __DIR__ . '/');
function plugin_dir_path($file){ return dirname($file) . '/'; }
function plugin_dir_url($file){ return 'https://example.test/wp-content/plugins/stpi/'; }
function register_activation_hook($file,$callback){ $GLOBALS['stpi_activation_callback']=$callback; }
function is_admin(){ return false; }
function wp_doing_cron(){ return false; }
require dirname(__DIR__) . '/server-turizm-program-intelligence.php';
$ok = defined('STPI_VERSION')
    && '0.3.3' === STPI_VERSION
    && isset($GLOBALS['stpi_activation_callback'])
    && !class_exists('STPI_Plugin', false)
    && !class_exists('STPI_Store', false)
    && !class_exists('STPI_Admin', false);
echo $ok ? "frontend isolation: PASS\n" : "frontend isolation: FAIL\n";
exit($ok ? 0 : 1);
