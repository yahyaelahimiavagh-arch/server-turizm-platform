<?php
/**
 * Plugin Name: Server Turizm Program Intelligence
 * Description: Shadow-mode ST-TDE contract, validation and private candidate foundation for Umrah and cultural tour programs.
 * Version: 0.3.5
 * Author: Server Turizm
 * Requires at least: 6.5
 * Requires PHP: 7.4
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

define( 'STPI_VERSION', '0.3.5' );
define( 'STPI_SCHEMA_VERSION', '1.0.0' );
define( 'STPI_FILE', __FILE__ );
define( 'STPI_DIR', plugin_dir_path( __FILE__ ) );
define( 'STPI_URL', plugin_dir_url( __FILE__ ) );

/**
 * Activation is intentionally self-contained so frontend requests do not need
 * to load any STPI implementation class merely to keep the activation hook
 * registered.
 */
function stpi_activate_plugin() {
    if ( false === get_option( 'stpi_launch_state', false ) ) {
        add_option( 'stpi_launch_state', 'shadow_locked', '', false );
    }
    if ( false === get_option( 'stpi_schema_version', false ) ) {
        add_option( 'stpi_schema_version', STPI_SCHEMA_VERSION, '', false );
    }
    if ( false === get_option( 'stpi_next_program_number', false ) ) {
        add_option( 'stpi_next_program_number', 1, '', false );
    }
}
register_activation_hook( __FILE__, 'stpi_activate_plugin' );

/**
 * v0.3.x is an administrator-only private candidate system. It has no public
 * renderer, route, REST endpoint, schema, sitemap integration or frontend
 * asset. Therefore public requests must not bootstrap the STPI runtime at all.
 *
 * Keep cron / WP-CLI available for controlled maintenance and future private
 * lifecycle jobs without exposing any public behavior.
 */
$stpi_private_runtime = is_admin()
    || ( function_exists( 'wp_doing_cron' ) && wp_doing_cron() )
    || ( defined( 'WP_CLI' ) && WP_CLI );

if ( ! $stpi_private_runtime ) {
    return;
}

require_once STPI_DIR . 'includes/class-stpi-contract.php';
require_once STPI_DIR . 'includes/class-stpi-hotel-adapter.php';
require_once STPI_DIR . 'includes/class-stpi-hotel-directory.php';
require_once STPI_DIR . 'includes/class-stpi-validator.php';
require_once STPI_DIR . 'includes/class-stpi-audit.php';
require_once STPI_DIR . 'includes/class-stpi-lifecycle.php';
require_once STPI_DIR . 'includes/class-stpi-store.php';
require_once STPI_DIR . 'includes/class-stpi-importer.php';
require_once STPI_DIR . 'includes/class-stpi-removals.php';
require_once STPI_DIR . 'includes/class-stpi-identity-repair.php';
require_once STPI_DIR . 'includes/class-stpi-admin.php';
require_once STPI_DIR . 'includes/class-stpi-plugin.php';

STPI_Plugin::instance();
