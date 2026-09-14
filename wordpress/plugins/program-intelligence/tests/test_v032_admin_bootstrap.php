<?php
// Minimal WordPress stubs to prove admin requests retain the complete STPI runtime.
define('ABSPATH', __DIR__ . '/');
function plugin_dir_path($file){ return dirname($file) . '/'; }
function plugin_dir_url($file){ return 'https://example.test/wp-content/plugins/stpi/'; }
function register_activation_hook($file,$callback){ $GLOBALS['stpi_activation_callback']=$callback; }
function is_admin(){ return true; }
function wp_doing_cron(){ return false; }
function add_action($hook,$callback){ $GLOBALS['stpi_hooks'][]=$hook; }
require dirname(__DIR__) . '/server-turizm-program-intelligence.php';
$ok = class_exists('STPI_Plugin', false)
    && class_exists('STPI_Store', false)
    && class_exists('STPI_Admin', false)
    && in_array('init', $GLOBALS['stpi_hooks'] ?? [], true)
    && in_array('admin_menu', $GLOBALS['stpi_hooks'] ?? [], true);
echo $ok ? "admin bootstrap: PASS\n" : "admin bootstrap: FAIL\n";
exit($ok ? 0 : 1);
