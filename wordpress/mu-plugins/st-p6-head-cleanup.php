<?php
/**
 * Plugin Name: Server Turizm P6 Head Cleanup
 * Description: Minimal SEO head cleanup for Server Turizm.
 * Version: 1.0.1
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action( 'wp_head', function () {
    ob_start();
}, -999999 );

add_action( 'wp_head', function () {

    if ( ! ob_get_level() ) {
        return;
    }

    $head = ob_get_clean();

    /**
     * 1) Remove empty duplicate OG description.
     */
    $head = preg_replace(
        '~<meta\s+property=["\']og:description["\']\s+content=["\']\s*["\']\s*/?>\s*~i',
        '',
        $head
    );

    /**
     * 2) Add Meta Description for /turlar/
     * only if another system has not already generated one.
     */
    if (
        is_page( 'turlar' ) &&
        ! preg_match(
            '~<meta\s+name=["\']description["\']~i',
            $head
        )
    ) {
        $description = 'Server Turizm’in kültür, yurt içi ve yurt dışı tur seçeneklerini keşfedin. Güncel tur programları ve seyahat danışmanlığı için bizimle iletişime geçin.';

        $head .= "\n<meta name=\"description\" content=\"" .
            esc_attr( $description ) .
            "\">\n";
    }

    echo $head;

}, 999999 );


/**
 * Server Turizm — Umre airline logo accessibility fix.
 * Adds ALT only to existing airline-logo images that do not already have one.
 */
add_filter( 'the_content', function ( $content ) {

    if ( is_admin() || ! is_page( 'umre-1' ) ) {
        return $content;
    }

    $content = preg_replace(
        '~<img(?![^>]*\balt=)([^>]*\bclass=["\'][^"\']*\blc-airline-logo\b[^"\']*["\'][^>]*)>~i',
        '<img alt="Program havayolu logosu"$1>',
        $content
    );

    return $content;

}, 20 );