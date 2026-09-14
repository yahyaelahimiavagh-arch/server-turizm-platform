<?php
/**
 * Plugin Name: Server Turizm Program Foundation
 * Description: Stable Program Entity foundation plus H6B Program↔Hotel relation graph for Server Turizm. Keeps Program→hotel_refs[] as the only relationship source of truth and derives Hotel→Programs safely.
 * Version: 0.2.0
 * Author: Server Turizm
 * Text Domain: server-turizm-program-foundation
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

define( 'STPF_VERSION', '0.2.0' );
define( 'STPF_FILE', __FILE__ );
define( 'STPF_DIR', plugin_dir_path( __FILE__ ) );
define( 'STPF_URL', plugin_dir_url( __FILE__ ) );

require_once STPF_DIR . 'includes/class-stpf-plugin.php';
require_once STPF_DIR . 'includes/class-stpf-program-id.php';
require_once STPF_DIR . 'includes/class-stpf-meta.php';
require_once STPF_DIR . 'includes/class-stpf-data-bridge.php';
require_once STPF_DIR . 'includes/class-stpf-relations.php';

register_activation_hook( __FILE__, array( 'STPF_Plugin', 'activate' ) );
STPF_Plugin::instance();
