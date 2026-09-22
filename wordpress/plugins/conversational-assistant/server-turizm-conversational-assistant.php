<?php
/**
 * Plugin Name: Server Turizm Conversational Assistant
 * Description: Read-only conversational layer for Instagram DMs using canonical Program, Hotel and Tour Intelligence data.
 * Version: 1.0.0
 * Author: Server Turizm
 */

if (!defined('ABSPATH')) {
    exit;
}

define('STCA_VERSION', '1.0.0');
define('STCA_PLUGIN_FILE', __FILE__);
define('STCA_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('STCA_PLUGIN_URL', plugin_dir_url(__FILE__));

require_once STCA_PLUGIN_DIR . 'includes/class-stca-config.php';
require_once STCA_PLUGIN_DIR . 'includes/class-stca-intent.php';
require_once STCA_PLUGIN_DIR . 'includes/class-stca-data.php';
require_once STCA_PLUGIN_DIR . 'includes/class-stca-responder.php';
require_once STCA_PLUGIN_DIR . 'includes/class-stca-logging.php';
require_once STCA_PLUGIN_DIR . 'includes/class-stca-meta.php';
require_once STCA_PLUGIN_DIR . 'includes/class-stca-webhook.php';
require_once STCA_PLUGIN_DIR . 'includes/class-stca-admin.php';

register_activation_hook(__FILE__, array('STCA_Logging', 'install'));

add_action('plugins_loaded', static function () {
    STCA_Logging::maybe_install();
    STCA_Webhook::init();
    STCA_Admin::init();
});
