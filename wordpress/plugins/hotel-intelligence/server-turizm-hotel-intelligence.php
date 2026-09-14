<?php
/**
 * Plugin Name: Server Turizm Hotel Intelligence
 * Description: Structured hotel entity foundation for Server Turizm: stable Hotel IDs, hotel data, destinations, collections, verification and an Excel-like management grid.
 * Version: 0.9.11
 * Author: Server Turizm
 * Text Domain: server-turizm-hotel-intelligence
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'STHI_VERSION', '0.9.11' );
define( 'STHI_FILE', __FILE__ );
define( 'STHI_DIR', plugin_dir_path( __FILE__ ) );
define( 'STHI_URL', plugin_dir_url( __FILE__ ) );

require_once STHI_DIR . 'includes/class-sthi-plugin.php';
require_once STHI_DIR . 'includes/class-sthi-hotel-id.php';
require_once STHI_DIR . 'includes/class-sthi-taxonomies.php';
require_once STHI_DIR . 'includes/class-sthi-geography.php';
require_once STHI_DIR . 'includes/class-sthi-meta.php';
require_once STHI_DIR . 'includes/class-sthi-content.php';
require_once STHI_DIR . 'includes/class-sthi-media.php';
require_once STHI_DIR . 'includes/class-sthi-sacred-distance.php';
require_once STHI_DIR . 'includes/class-sthi-admin-grid.php';
require_once STHI_DIR . 'includes/class-sthi-data-bridge.php';
require_once STHI_DIR . 'includes/class-sthi-structured-details.php';
require_once STHI_DIR . 'includes/class-sthi-frontend.php';
require_once STHI_DIR . 'includes/class-sthi-hubs.php';
require_once STHI_DIR . 'includes/class-sthi-public-routes.php';
require_once STHI_DIR . 'includes/class-sthi-sitemap.php';

register_activation_hook( __FILE__, array( 'STHI_Plugin', 'activate' ) );

STHI_Plugin::instance();
