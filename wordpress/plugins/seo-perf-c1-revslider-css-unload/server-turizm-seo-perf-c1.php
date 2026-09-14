<?php
/**
 * Plugin Name: Server Turizm SEO PERF C1 — RevSlider CSS Unload
 * Description: Experimental performance candidate. Suppresses Slider Revolution rs6.css only on /umre-1/.
 * Version: 0.1.1
 * Author: Server Turizm
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Target only the public Umre page.
 */
function st_seo_perf_c1_is_target_page() {
	if ( is_admin() ) {
		return false;
	}

	/*
	 * Primary WordPress page check.
	 */
	if ( is_page( 'umre-1' ) ) {
		return true;
	}

	/*
	 * Defensive fallback for this one production URL only.
	 * This avoids depending solely on theme/plugin query manipulation.
	 */
	$uri  = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '';
	$path = wp_parse_url( $uri, PHP_URL_PATH );

	return untrailingslashit( (string) $path ) === '/umre-1';
}

/**
 * First attempt: remove the enqueue in the normal queue.
 */
function st_seo_perf_c1_dequeue_revslider_css() {
	if ( ! st_seo_perf_c1_is_target_page() ) {
		return;
	}

	wp_dequeue_style( 'rs-plugin-settings' );
}
add_action( 'wp_enqueue_scripts', 'st_seo_perf_c1_dequeue_revslider_css', PHP_INT_MAX );
add_action( 'wp_print_styles', 'st_seo_perf_c1_dequeue_revslider_css', PHP_INT_MAX );

/**
 * Final output guard:
 * If RevSlider or the theme re-enqueues the stylesheet after our dequeue,
 * suppress ONLY this exact WordPress style handle on the target page.
 */
function st_seo_perf_c1_filter_revslider_style_tag( $html, $handle, $href, $media ) {
	if ( ! st_seo_perf_c1_is_target_page() ) {
		return $html;
	}

	if ( 'rs-plugin-settings' === $handle ) {
		return '';
	}

	return $html;
}
add_filter( 'style_loader_tag', 'st_seo_perf_c1_filter_revslider_style_tag', PHP_INT_MAX, 4 );
