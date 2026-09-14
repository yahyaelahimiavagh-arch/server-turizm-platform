<?php
/**
 * Plugin Name: Server Turizm Program Publishing Integration
 * Description: Safe publishing glue between Program Intelligence, Hotel Intelligence, final Program routes, /umre-1/ and controlled derived Hotel→Program relations. Stable-ID allowlist rollout defaults OFF.
 * Version: 0.4.14
 * Requires at least: 6.5
 * Requires PHP: 7.4
 * Author: Server Turizm
 */
if (!defined('ABSPATH')) { exit; }
define('STPPI_VERSION', '0.4.14');
define('STPPI_DIR', plugin_dir_path(__FILE__));
define('STPPI_URL', plugin_dir_url(__FILE__));
define('STPPI_ROUTE_BASE', '/umre-programlari/');

/**
 * WordPress stores scalar option values as strings when they are read back
 * from the database. Never use a strict `=== true` comparison for a persisted
 * boolean gate: update_option(..., true) is normally read later as string `1`.
 * Keep this deliberately fail-closed; only the canonical true forms pass.
 */
function stppi_option_enabled($name) {
    return in_array(get_option($name, false), array(true, 1, '1'), true);
}
function stppi_public_master_enabled() {
    return stppi_option_enabled('stppi_public_master');
}
function stppi_hub_bridge_enabled() { return stppi_option_enabled('stppi_hub_bridge_enabled'); }
function stppi_hotel_links_enabled() { return stppi_option_enabled('stppi_hotel_links_enabled'); }
function stppi_umre_skin_enabled() { return stppi_option_enabled('stppi_umre_skin_enabled'); }
function stppi_is_umre_hub_request() {
    $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
    $home_path = rtrim((string)parse_url(home_url('/'), PHP_URL_PATH), '/');
    return is_string($path) && rtrim($path, '/') === ($home_path . '/umre-1');
}
function stppi_hotel_link_canary_id() {
    // Legacy evidence compatibility only. v0.4.2 public Hotel relations use the Stable-ID allowlist below.
    $id = strtoupper(trim((string)get_option('stppi_hotel_link_canary', '')));
    return preg_match('/^STH-[0-9]{6}$/D', $id) ? $id : '';
}
function stppi_hotel_allowlist_ids() {
    $raw = get_option('stppi_hotel_allowlist', array());
    if (!is_array($raw)) { return array(); }
    $out = array();
    foreach ($raw as $id) {
        $id = strtoupper(trim((string)$id));
        if (preg_match('/^STH-[0-9]{6}$/D', $id)) { $out[$id] = true; }
    }
    $ids = array_keys($out);
    sort($ids, SORT_STRING);
    return $ids;
}
function stppi_set_hotel_allowlist($ids) {
    $out = array();
    foreach ((array)$ids as $id) {
        $id = strtoupper(trim((string)$id));
        if (preg_match('/^STH-[0-9]{6}$/D', $id)) { $out[$id] = true; }
    }
    $ids = array_keys($out);
    sort($ids, SORT_STRING);
    update_option('stppi_hotel_allowlist', $ids, false);
    return $ids;
}
function stppi_close_hotel_links($clear_selection = false) {
    update_option('stppi_hotel_links_enabled', false, false);
    if ($clear_selection) {
        delete_option('stppi_hotel_link_canary');
        stppi_set_hotel_allowlist(array());
    }
}

function stppi_close_all() {
    update_option('stppi_public_master', false, false);
    update_option('stppi_hub_bridge_enabled', false, false);
    update_option('stppi_hotel_links_enabled', false, false);
    $registry = get_option('stppi_registry', array());
    if (!is_array($registry)) { $registry = array(); }
    foreach ($registry as $id => $row) {
        if (is_array($row) && (($row['mode'] ?? '') === 'public_noindex')) { $registry[$id]['mode'] = 'prepared'; }
    }
    update_option('stppi_registry', $registry, false);
}
function stppi_upgrade_guard() {
    $previous = (string)get_option('stppi_version', '');
    if ($previous !== STPPI_VERSION) {
        /*
         * Safe production upgrade contracts are exact and deliberately narrow.
         * - 0.3.1 -> 0.3.2: accepted presentation-only mobile hotfix.
         * - 0.4.1 -> 0.4.2: replaces one exact Hotel canary with a Stable Hotel ID
         *   allowlist. Preserve accepted Program public/noindex routes + live Hub,
         *   but force Hotel relations OFF and start with an EMPTY allowlist.
         * Every other version transition remains fail-closed.
         */
        $safe_mobile_ui_hotfix = ($previous === '0.3.1' && STPPI_VERSION === '0.3.2');
        $safe_hotel_allowlist_candidate = ($previous === '0.4.1' && STPPI_VERSION === '0.4.2');
        $safe_umre_skin_candidate = ($previous === '0.4.2' && STPPI_VERSION === '0.4.3');
        $safe_umre_skin_palette_hotfix = ($previous === '0.4.3' && STPPI_VERSION === '0.4.4');
        $safe_umre_skin_navy_hotfix = ($previous === '0.4.4' && STPPI_VERSION === '0.4.5');
        $safe_umre_skin_soft_navy_hotfix = ($previous === '0.4.5' && STPPI_VERSION === '0.4.6');
        $safe_umre_skin_perf_hotfix = ($previous === '0.4.6' && STPPI_VERSION === '0.4.7');
        $safe_incremental_recovery_hotfix = ($previous === '0.4.7' && STPPI_VERSION === '0.4.8');
        $safe_temporal_recovery_hotfix = ($previous === '0.4.8' && STPPI_VERSION === '0.4.9');
        $safe_current_order_countdown_hotfix = ($previous === '0.4.9' && STPPI_VERSION === '0.4.10');
        $safe_single_day_countdown_hotfix = ($previous === '0.4.10' && STPPI_VERSION === '0.4.11');
        $safe_stpi033_compat_hotfix = ($previous === '0.4.11' && STPPI_VERSION === '0.4.12');
        $safe_stpi034_identity_repair_hotfix = ($previous === '0.4.12' && STPPI_VERSION === '0.4.13');
        $safe_stpi035_repair_preflight_hotfix = ($previous === '0.4.13' && STPPI_VERSION === '0.4.14');
        if ($safe_hotel_allowlist_candidate) {
            stppi_close_hotel_links(true);
        } elseif ($safe_umre_skin_candidate) {
            // Presentation-only candidate: preserve accepted Program/Hub/Hotel runtime state.
            // The new visual skin starts fail-closed and must be explicitly enabled after preview.
            update_option('stppi_umre_skin_enabled', false, false);
        } elseif ($safe_umre_skin_palette_hotfix) {
            // CSS-only palette correction: preserve every accepted runtime gate and the current skin state.
        } elseif ($safe_umre_skin_navy_hotfix) {
            // Background-only luxury navy correction: preserve every accepted runtime gate and skin state.
            // No Program card/button selectors are owned by the skin stylesheet.
        } elseif ($safe_umre_skin_soft_navy_hotfix) {
            // Background-only soft navy/pearl correction: preserve all accepted runtime gates and skin state.
        } elseif ($safe_umre_skin_perf_hotfix) {
            // Performance-only background simplification: preserve every runtime gate, source-of-truth and current skin state.
            // Program cards and CTA colors remain owned by hub.css.
        } elseif ($safe_incremental_recovery_hotfix) {
            // Production recovery/incremental publishing hotfix. Preserve current runtime state exactly.
            // Recovery is explicit from the admin screen; replacement itself never opens/closes routes.
        } elseif ($safe_temporal_recovery_hotfix) {
            // Recovery-only temporal hotfix. Preserve the current OFF/ON gates exactly on replacement.
            // Explicit recovery will restore only current UPCOMING routes and leave older routes PREPARED.
        } elseif ($safe_current_order_countdown_hotfix) {
            // Current/in-progress visibility + chronological ordering + date-only countdown hotfix.
            // Preserve every runtime gate and registry mode on replacement; any route repair remains explicit.
        } elseif ($safe_single_day_countdown_hotfix) {
            // Presentation-only countdown simplification: days-only premium counter.
            // Preserve every runtime gate, registry mode and publication state exactly.
        } elseif ($safe_stpi033_compat_hotfix) {
            // Compatibility-only patch for Program Intelligence v0.3.3 explicit removal-intent workflow.
            // Preserve every accepted Program/Hub/Hotel/skin runtime gate and registry mode exactly.
        } elseif ($safe_stpi034_identity_repair_hotfix) {
            // Compatibility + exact post-repair registry-refresh hotfix for Program Intelligence v0.3.4.
            // Replacement preserves every accepted runtime gate and registry mode. Refresh is explicit and transactional.
        } elseif ($safe_stpi035_repair_preflight_hotfix) {
            // Compatibility-only patch for Program Intelligence v0.3.5 repair-preflight verification fix.
            // Preserve every accepted runtime gate and registry mode exactly.
        } elseif (!$safe_mobile_ui_hotfix) {
            stppi_close_all();
        }
        update_option('stppi_version', STPPI_VERSION, false);
    }
}
function stppi_activation_guard() {
    // Reinstall/replace of accepted production hotfixes must not close an already accepted live baseline.
    $previous = (string)get_option('stppi_version', '');
    if (in_array($previous, array('0.4.10','0.4.11','0.4.12','0.4.13','0.4.14'), true)) { return; }
    stppi_close_all();
}
register_activation_hook(__FILE__, 'stppi_activation_guard');
register_deactivation_hook(__FILE__, 'stppi_close_all');

$stppi_path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$stppi_home_path = rtrim((string)parse_url(home_url('/'), PHP_URL_PATH), '/');
$stppi_base = $stppi_home_path . STPPI_ROUTE_BASE;
$stppi_is_namespace = is_string($stppi_path) && (strpos($stppi_path, $stppi_base) === 0 || $stppi_path === rtrim($stppi_base, '/'));

// Admin and final Program namespace only. Ordinary frontend does not bootstrap STPI read classes.
if (is_admin() || $stppi_is_namespace) {
    require_once STPPI_DIR . 'includes/class-stppi-repository.php';
    require_once STPPI_DIR . 'includes/class-stppi-renderer.php';
    if (is_admin()) {
        stppi_upgrade_guard();
        require_once STPPI_DIR . 'includes/class-stppi-batch.php';
        require_once STPPI_DIR . 'includes/class-stppi-admin.php';
        STPPI_Batch::init();
        STPPI_Admin::init();
    } else {
        if (!defined('DONOTCACHEPAGE')) { define('DONOTCACHEPAGE', true); }
        add_action('template_redirect', array('STPPI_Renderer', 'dispatch'), -110);
    }
}

// Tiny inert integration shell: no STPI reads unless an eligible feature is explicitly enabled later.
require_once STPPI_DIR . 'includes/class-stppi-integrations.php';
STPPI_Integrations::init();
