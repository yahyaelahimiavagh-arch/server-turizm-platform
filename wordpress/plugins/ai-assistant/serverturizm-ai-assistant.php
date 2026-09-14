<?php
/**
 * Plugin Name: Server Turizm AI Assistant
 * Description: AI assistant widget for Server Turizm. Modular rebuild.
 * Version: 0.2.2-modular
 * Author: Server Turizm
 */

if (!defined('ABSPATH')) {
    exit;
}

define('STAI_VERSION', '0.2.2-modular');
define('STAI_PLUGIN_FILE', __FILE__);
define('STAI_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('STAI_PLUGIN_URL', plugin_dir_url(__FILE__));

require_once STAI_PLUGIN_DIR . 'includes/settings-core.php';
require_once STAI_PLUGIN_DIR . 'includes/core-helpers.php';
require_once STAI_PLUGIN_DIR . 'includes/logging.php';

register_activation_hook(__FILE__, 'stai_maybe_create_log_table');

require_once STAI_PLUGIN_DIR . 'includes/programs.php';
require_once STAI_PLUGIN_DIR . 'includes/faq.php';
require_once STAI_PLUGIN_DIR . 'includes/rate-limit.php';
require_once STAI_PLUGIN_DIR . 'includes/gemini.php';
require_once STAI_PLUGIN_DIR . 'includes/admin-data.php';
require_once STAI_PLUGIN_DIR . 'includes/rest-handlers.php';
require_once STAI_PLUGIN_DIR . 'includes/rest-routes.php';
require_once STAI_PLUGIN_DIR . 'admin/admin.php';
require_once STAI_PLUGIN_DIR . 'frontend/frontend.php';
