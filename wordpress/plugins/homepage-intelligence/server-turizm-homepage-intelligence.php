<?php
/**
 * Plugin Name: Server Turizm Homepage Intelligence
 * Description: SEO-first Homepage Control Center with plugin-owned no-Raw-HTML baseline, controlled public cutover, rollback, SEO title/meta, hero content, campaign gallery/lightbox, featured Umrah labels, Culture Tours, H12F composition and a controlled Journey Evidence module.
 * Version: 0.3.3
 * Author: Server Turizm
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

define( 'STHI_HOME_VERSION', '0.3.3' );
define( 'STHI_HOME_FILE', __FILE__ );
define( 'STHI_HOME_DIR', plugin_dir_path( __FILE__ ) );
define( 'STHI_HOME_URL', plugin_dir_url( __FILE__ ) );

require_once STHI_HOME_DIR . 'includes/class-sthi-home-plugin.php';
require_once STHI_HOME_DIR . 'includes/class-sthi-home-repository.php';
require_once STHI_HOME_DIR . 'includes/class-sthi-home-renderer.php';
require_once STHI_HOME_DIR . 'includes/class-sthi-home-admin.php';

register_activation_hook( __FILE__, array( 'STHI_Home_Plugin', 'activate' ) );
add_action( 'plugins_loaded', array( 'STHI_Home_Plugin', 'init' ), 25 );
