<?php
/**
 * Plugin Name: Server Turizm — Legacy SEO Redirects 2026-09-14
 * Description: Exact legacy URL redirects discovered in Google Search Console.
 * Author: Yahya Elahi Miavagh
 * Version: 1.0.0
 */

defined('ABSPATH') || exit;

add_action('template_redirect', function () {

    if (is_admin()) {
        return;
    }

    $request_uri = isset($_SERVER['REQUEST_URI'])
        ? wp_unslash($_SERVER['REQUEST_URI'])
        : '/';

    $path = wp_parse_url($request_uri, PHP_URL_PATH);

    if (!is_string($path)) {
        return;
    }

    $path = '/' . trim(rawurldecode($path), '/');

    /*
     * Legacy commercial Umrah URLs.
     * Exact allowlist only — NO wildcard redirects.
     */
    $umrah_legacy = [
        '/tour/ramazan-ayi-luks-umre-programi-4-grup-luks-umre',
        '/sezonluk-umre-programlari',
        '/tour/ekonomik-yakin-servisli-umre-programi-19-gece-20-gun',
        '/umre-2',
        '/umre-3',
    ];

    if (in_array($path, $umrah_legacy, true)) {
        wp_safe_redirect(
            home_url('/umre-1/'),
            301,
            'Server Turizm Legacy SEO'
        );
        exit;
    }

    /*
     * Legacy Hac commercial URL.
     */
    if ($path === '/tour/2023-2024-ekonomik-hac-vizeli') {
        wp_safe_redirect(
            home_url('/hac/'),
            301,
            'Server Turizm Legacy SEO'
        );
        exit;
    }

}, 1);