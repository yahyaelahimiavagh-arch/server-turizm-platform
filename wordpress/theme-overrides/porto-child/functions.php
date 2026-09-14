<?php
/**
 * Server Turizm Porto Child
 * Version: 1.0.0
 *
 * Purpose:
 * - Preserve the parent Porto theme_mods on first activation so menu/location
 *   assignments do not unexpectedly disappear when switching parent -> child.
 * - Keep Porto core untouched and update-safe.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'after_switch_theme', function () {
	$parent_key = 'theme_mods_porto';
	$child_key  = 'theme_mods_' . get_stylesheet();

	$parent_mods = get_option( $parent_key );
	$child_mods  = get_option( $child_key );

	// Copy once only when the child does not already have meaningful theme mods.
	if (
		is_array( $parent_mods ) &&
		! empty( $parent_mods ) &&
		( ! is_array( $child_mods ) || empty( $child_mods ) )
	) {
		update_option( $child_key, $parent_mods, true );
	}
} );


/**
 * Load the child stylesheet after the Porto/plugin styles.
 * The previous build contained the footer-width hotfix in style.css, but
 * some Porto setups do not automatically enqueue the child stylesheet.
 */
add_action( 'wp_enqueue_scripts', function () {
	wp_enqueue_style(
		'server-turizm-porto-child',
		get_stylesheet_uri(),
		array(),
		'1.0.3'
	);
}, 999 );
