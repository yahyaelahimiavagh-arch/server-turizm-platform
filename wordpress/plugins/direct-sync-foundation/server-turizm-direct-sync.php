<?php
/**
 * Plugin Name: Server Turizm Direct Sync Foundation
 * Description: Shared authenticated Google Sheets direct-sync gateway for Umrah Program Intelligence and Tour Intelligence.
 * Version: 0.1.2
 * Author: Server Turizm
 */
if (!defined('ABSPATH')) { exit; }
define('STDS_VERSION', '0.1.2');
define('STDS_CONTRACT', 'ST-DIRECT-SYNC-1.0.0');
define('STDS_DIR', plugin_dir_path(__FILE__));
require_once STDS_DIR . 'includes/class-stds-store.php';
require_once STDS_DIR . 'includes/class-stds-auth.php';
require_once STDS_DIR . 'includes/class-stds-umrah.php';
require_once STDS_DIR . 'includes/class-stds-tour.php';
require_once STDS_DIR . 'includes/class-stds-rest.php';
register_activation_hook(__FILE__, array('STDS_Store','install'));
add_action('rest_api_init', array('STDS_REST','register_routes'));
