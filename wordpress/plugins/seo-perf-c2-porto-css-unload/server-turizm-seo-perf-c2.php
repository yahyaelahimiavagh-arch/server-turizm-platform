<?php
/**
 * Plugin Name: Server Turizm SEO PERF C2 — Porto plugins.css Unload
 * Description: Experimental performance candidate. Suppresses Porto plugins.css only on /umre-1/.
 * Version: 0.1.0
 * Author: Server Turizm
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Target only the public Umre page.
 */
function st_seo_perf_c2_is_target_page() {
	if ( is_admin() ) {
		return false;
	}

	if ( is_page( 'umre-1' ) ) {
		return true;
	}

	$uri  = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '';
	$path = wp_parse_url( $uri, PHP_URL_PATH );

	return untrailingslashit( (string) $path ) === '/umre-1';
}

/**
 * Remove Porto plugins.css from the normal WordPress style queue.
 */
function st_seo_perf_c2_dequeue_porto_plugins_css() {
	if ( ! st_seo_perf_c2_is_target_page() ) {
		return;
	}

	wp_dequeue_style( 'porto-plugins' );
}

add_action( 'wp_enqueue_scripts', 'st_seo_perf_c2_dequeue_porto_plugins_css', PHP_INT_MAX );
add_action( 'wp_print_styles', 'st_seo_perf_c2_dequeue_porto_plugins_css', PHP_INT_MAX );

/**
 * Final output guard in case Porto re-enqueues the stylesheet later.
 */
function st_seo_perf_c2_filter_porto_plugins_style_tag( $html, $handle, $href, $media ) {
	if ( ! st_seo_perf_c2_is_target_page() ) {
		return $html;
	}

	if ( 'porto-plugins' === $handle ) {
		return '';
	}

	return $html;
}

add_filter( 'style_loader_tag', 'st_seo_perf_c2_filter_porto_plugins_style_tag', PHP_INT_MAX, 4 );
