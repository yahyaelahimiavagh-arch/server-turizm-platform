<?php
/**
 * Plugin Name: Server Turizm Auto Sync 2
 * Description: Google Sheets source-of-truth sync for Umrah programs and hotel directory. Compatible with STDS 0.1.6, STPI 0.3.8, STHI 0.9.11, STPPI 0.4.23.
 * Version: 2.8.0
 * Requires PHP: 7.4
 * Author: Server Turizm
 */
if (!defined('ABSPATH')) { exit; }
define('ST2L_DIR', plugin_dir_path(__FILE__));
define('ST2L_VERSION', '2.8.0');
require_once ST2L_DIR.'includes/class-st2l-checkpoint.php';
require_once ST2L_DIR.'includes/class-st2l-core.php';
require_once ST2L_DIR.'includes/class-st2l-fast-archive.php';
require_once ST2L_DIR.'includes/class-st2l-hotels.php';
require_once ST2L_DIR.'includes/class-st2l-programs.php';
// DO NOT load frontend replacement; the original STPPI renderer owns all visuals.
require_once ST2L_DIR.'includes/class-st2l-legacy-publisher.php';
require_once ST2L_DIR.'includes/class-st2l-r2-confirmed.php';
add_action('rest_api_init', array('ST2L_Core','register'));
add_action('st2l_process_job', array('ST2L_Core','run_queue'));
add_action('st2l_fast_archive_job', array('ST2L_Fast_Archive','run_due'));
add_action('admin_menu', array('ST2L_Core','menu'));
// Auto Sync is data-only. No shortcode, route, CSS or public template is registered.
add_action('init', function(){ if(get_option('st2l_public_enabled',false))update_option('st2l_public_enabled',false,false); }, 1);
register_deactivation_hook(__FILE__, function(){ wp_clear_scheduled_hook('st2l_process_job');wp_clear_scheduled_hook('st2l_fast_archive_job'); });
