<?php
/**
 * STTI v1.1.8 — Porto/WPBakery-compatible Culture Tours Hub renderer.
 *
 * The accepted Hub intentionally keeps ownership of /kultur-turlari/ with the
 * existing WordPress page. Some builders/themes render that page outside the
 * strict is_main_query()/in_the_loop() path used by the v1.1.0/v1.1.7 content
 * filters. This layer primes only the queried page's in-memory post_content and
 * provides a page-ID-scoped late filter fallback. It does not add routes,
 * rewrite rules, SEO ownership, indexation, sitemap or schema exposure.
 */
if (!defined('ABSPATH')) { exit; }

function stti_v118_hub_request_active() {
    return !is_admin()
        && function_exists('stti_v110_hub_enabled')
        && stti_v110_hub_enabled()
        && function_exists('stti_v110_is_hub_request')
        && stti_v110_is_hub_request();
}

function stti_v118_hub_html() {
    // Later presentation layers may enrich accepted Hub card models without
    // taking ownership of the WordPress route or SEO shell.
    if (function_exists('stti_v121_hub_html')) return stti_v121_hub_html();
    if (function_exists('stti_v119_hub_html')) return stti_v119_hub_html();
    if (!function_exists('stti_v110_render_hub') || !function_exists('stti_v117_hub_records')) return '';
    return stti_v110_render_hub(stti_v117_hub_records());
}

function stti_v118_prime_post_object($candidate, $queried_id, $html) {
    if (!($candidate instanceof WP_Post)) return;
    if ($queried_id > 0 && (int)$candidate->ID !== $queried_id) return;
    $candidate->post_content = $html;
    $candidate->post_content_filtered = '';
}

function stti_v118_prime_queried_page_content() {
    if (!stti_v118_hub_request_active()) return;

    $html = stti_v118_hub_html();
    if ($html === '') return;

    $queried_id = function_exists('get_queried_object_id') ? (int)get_queried_object_id() : 0;
    global $post, $wp_query;

    stti_v118_prime_post_object($post, $queried_id, $html);

    if ($wp_query instanceof WP_Query) {
        stti_v118_prime_post_object($wp_query->post, $queried_id, $html);
        foreach ((array)$wp_query->posts as $candidate) {
            stti_v118_prime_post_object($candidate, $queried_id, $html);
        }
    }
}

function stti_v118_force_queried_page_content($content) {
    if (!stti_v118_hub_request_active()) return $content;

    $queried_id = function_exists('get_queried_object_id') ? (int)get_queried_object_id() : 0;
    $current_id = function_exists('get_the_ID') ? (int)get_the_ID() : 0;

    // Never replace secondary posts/widgets when WordPress can identify them.
    if ($queried_id > 0 && $current_id > 0 && $current_id !== $queried_id) return $content;

    $html = stti_v118_hub_html();
    return $html !== '' ? $html : $content;
}

// Retire the fragile runtime replacements while keeping their accepted source
// code and contract intact for rollback/audit. This module loads after v1.1.7.
remove_filter('the_content', 'stti_v110_replace_hub_content', 99);
remove_filter('the_content', 'stti_v117_replace_hub_content', 100);

// Prime the canonical queried page before Porto/WPBakery consumes post_content.
add_action('wp', 'stti_v118_prime_queried_page_content', 99);

// Fallbacks remain exact-route + Hub-Master + queried-page-ID scoped.
add_filter('the_content', 'stti_v118_force_queried_page_content', 9999);
add_filter('get_the_content', 'stti_v118_force_queried_page_content', 9999);
