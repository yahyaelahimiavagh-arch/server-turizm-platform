<?php
/**
 * Plugin Name: Server Turizm Home Hotel Discovery
 * Description: H12F preview-only homepage Hotel discovery layer with controlled visual integration, backed directly by Hotel Intelligence entities.
 * Version: 0.1.5
 * Author: Server Turizm
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

define( 'STHHD_VERSION', '0.1.5' );
define( 'STHHD_FILE', __FILE__ );
define( 'STHHD_DIR', plugin_dir_path( __FILE__ ) );
define( 'STHHD_URL', plugin_dir_url( __FILE__ ) );

require_once STHHD_DIR . 'includes/class-sthhd-repository.php';
require_once STHHD_DIR . 'includes/class-sthhd-renderer.php';
require_once STHHD_DIR . 'includes/class-sthhd-admin.php';
require_once STHHD_DIR . 'includes/class-sthhd-plugin.php';

register_activation_hook( __FILE__, array( 'STHHD_Plugin', 'activate' ) );
add_action( 'plugins_loaded', array( 'STHHD_Plugin', 'init' ), 30 );
