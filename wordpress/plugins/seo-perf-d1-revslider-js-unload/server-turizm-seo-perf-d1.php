<?php
/**
 * Plugin Name: Server Turizm SEO PERF D1 — RevSlider JS Unload
 * Description: Candidate: conditionally removes Revolution Slider frontend JavaScript from /umre-1/ only. Reversible by deactivation.
 * Version: 0.1.0
 * Author: Server Turizm
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Scope strictly to the public /umre-1/ page.
 */
function st_perf_d1_is_target() {
    if (is_admin()) {
        return false;
    }

    // Primary WordPress-aware check.
    if (function_exists('is_page') && is_page('umre-1')) {
        return true;
    }

    // Conservative path fallback.
    $uri  = isset($_SERVER['REQUEST_URI']) ? (string) $_SERVER['REQUEST_URI'] : '';
    $path = wp_parse_url($uri, PHP_URL_PATH);

    return untrailingslashit((string) $path) === '/umre-1';
}

/**
 * Remove the two confirmed RevSlider handles.
 *
 * Dependency audit proof before this candidate:
 * - tp-tools -> rbtools.min.js; no direct/transitive dependents on this page.
 * - revmin   -> rs6.min.js;    no direct/transitive dependents on this page.
 */
function st_perf_d1_dequeue_revslider_js() {
    if (!st_perf_d1_is_target()) {
        return;
    }

    wp_dequeue_script('revmin');
    wp_dequeue_script('tp-tools');

    // Deregister only on the target request so a later dependency-resolution
    // pass cannot silently re-enqueue these handles.
    wp_deregister_script('revmin');
    wp_deregister_script('tp-tools');
}
add_action('wp_enqueue_scripts', 'st_perf_d1_dequeue_revslider_js', PHP_INT_MAX);

/**
 * Some themes/plugins manipulate footer queues late.
 * Run once more immediately before footer script printing.
 */
add_action('wp_footer', 'st_perf_d1_dequeue_revslider_js', 1);

/**
 * Final narrow safety guard. This does not touch any other script.
 * It protects against a late/raw WordPress script tag for the two exact
 * RevSlider assets on /umre-1/.
 */
function st_perf_d1_script_tag_guard($tag, $handle, $src) {
    if (!st_perf_d1_is_target()) {
        return $tag;
    }

    if (in_array($handle, array('tp-tools', 'revmin'), true)) {
        return '';
    }

    $src_lc = strtolower((string) $src);

    if (
        strpos($src_lc, '/revslider/public/assets/js/rbtools.min.js') !== false ||
        strpos($src_lc, '/revslider/public/assets/js/rs6.min.js') !== false
    ) {
        return '';
    }

    return $tag;
}
add_filter('script_loader_tag', 'st_perf_d1_script_tag_guard', PHP_INT_MAX, 3);
