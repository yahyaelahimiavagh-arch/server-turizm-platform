<?php
/**
 * Plugin Name: Server Turizm Umre Semantic Adapter
 * Description: Adds a zero-visual-change semantic layer to Server Turizm, including Umre program semantics and verified homepage/Umre structured data, without modifying the Google Sheet, Apps Script, generated tour HTML, CSS, or JavaScript.
 * Version: 0.33.0
 * Author: Server Turizm
 * License: GPL-2.0-or-later
 * Text Domain: st-umre-semantic-adapter
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class ST_Umre_Semantic_Adapter {
    const VERSION = '0.33.0';
    const TARGET_PAGE_SLUG = 'umre-1';
    const CARD_CLASS = 'lc-card-template';

    /** Register hooks. */
    public static function init() {
        // v0.22.0 candidate: consolidate all verified legacy/duplicate Umre entry points
        // into the single current Umre hub before any output buffering starts.
        add_action( 'template_redirect', array( __CLASS__, 'maybe_redirect_legacy_umre_archive' ), -20 );

        // v0.23.0 candidate: normalize only WordPress navigation-menu items that
        // still point at verified legacy Umre destinations. This avoids sending
        // users/crawlers through an unnecessary 301 hop while leaving visible
        // link text, menus, page content and non-menu URLs untouched.
        add_filter( 'wp_nav_menu_objects', array( __CLASS__, 'rewrite_legacy_umre_menu_urls' ), 20, 2 );

        add_action( 'template_redirect', array( __CLASS__, 'maybe_start_buffer' ), 0 );

        // v0.33.0 performance candidate: on the canonical Umre hub only,
        // remove a narrow allowlist of shop/search assets whose corresponding
        // WooCommerce/YITH UI is absent from this page. Run late so handles
        // registered/enqueued by Porto, WooCommerce, WPBakery and YITH have
        // already had a chance to enter the queue.
        add_action( 'wp_enqueue_scripts', array( __CLASS__, 'dequeue_unused_umre_shop_assets' ), PHP_INT_MAX );
    }

    /**
     * v0.22.0 candidate: permanently consolidate the verified legacy Umre
     * taxonomy archive, the duplicate /ekonomik-umre-programlari/ page, and
     * the complete audited set of 15 expired individual Umre tours from
     * 2023–2024 into /umre-1/.
     *
     * Scope remains exact-list only. No wildcard /tour/... redirect is used,
     * so cultural tours and any unrelated/current tour URLs remain untouched.
     */
    /**
     * v0.23.0 candidate: rewrite verified legacy Umre destinations inside
     * WordPress nav-menu objects to the canonical /umre-1/ hub.
     *
     * Exact-path allowlist only. The menu item's title/classes/target/rel and
     * every unrelated URL remain untouched.
     *
     * @param array $items
     * @param object $args
     * @return array
     */
    public static function rewrite_legacy_umre_menu_urls( $items, $args = null ) {
        if ( ! self::is_enabled() || ! is_array( $items ) ) {
            return $items;
        }

        $legacy_paths = array(
            '/tour-category/ekonomik-umre-programi/',
            '/ekonomik-umre-programlari/'
        );

        $target = home_url( '/' . self::TARGET_PAGE_SLUG . '/' );

        foreach ( $items as $item ) {
            if ( ! is_object( $item ) || ! isset( $item->url ) ) {
                continue;
            }

            $url = (string) $item->url;
            $path = wp_parse_url( $url, PHP_URL_PATH );

            if ( ! is_string( $path ) ) {
                continue;
            }

            $normalized = trailingslashit( $path );

            if ( in_array( $normalized, $legacy_paths, true ) ) {
                $item->url = $target;
            }
        }

        return $items;
    }

    public static function maybe_redirect_legacy_umre_archive() {
        if ( ! self::is_enabled() || is_admin() ) {
            return;
        }

        if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
            return;
        }

        if ( function_exists( 'wp_doing_ajax' ) && wp_doing_ajax() ) {
            return;
        }

        $request_uri = isset( $_SERVER['REQUEST_URI'] ) ? (string) $_SERVER['REQUEST_URI'] : '';
        $request_path = wp_parse_url( $request_uri, PHP_URL_PATH );

        if ( ! is_string( $request_path ) ) {
            return;
        }

        $legacy_prefix = '/tour-category/ekonomik-umre-programi/';
        $duplicate_page = '/ekonomik-umre-programlari/';
        $legacy_individual_paths = array(
            '/tour/sevval-ayi-ekonomik-umre-programi-2-grup/',
            '/tour/ramazan-ayi-ekonomik-umre-programi-3-grup-bayram-mekke/',
            '/tour/ramazan-ayi-ekonomik-umre-programi-2-grup/',
            '/tour/ramazan-ayi-ekonomik-umre-programi-1-grup/',
            '/tour/mart-ayi-ekonomik-umre-programi/',
            '/tour/subat-ayi-ekonomik-umre-programi-2-grup/',
            '/tour/subat-ayi-ekonomik-umre-programi-1-grup/',
            '/tour/ocak-ayi-ekonomik-umre-programi-2-grup/',
            '/tour/ocak-ayi-ekonomik-umre-programi-1-grup/',
            '/tour/aralik-ayi-ekonomik-umre-programi-2-grup/',
            '/tour/aralik-ayi-ekonomik-umre-programi-1-grup/',
            '/tour/kasim-ayi-ekonomik-umre-programi-3-grup/',
            '/tour/kasim-ayi-ekonomik-umre-programi-2-grup/',
            '/tour/kasim-ayi-ekonomik-umre-programi-1-grup/',
            '/tour/ekim-ayi-ekonomik-umre-programi/'
        );
        $normalized_path = trailingslashit( $request_path );

        $is_legacy_archive = ( 0 === strpos( $normalized_path, $legacy_prefix ) );
        $is_duplicate_page = ( $duplicate_page === $normalized_path );
        $is_legacy_individual = in_array( $normalized_path, $legacy_individual_paths, true );

        if ( ! $is_legacy_archive && ! $is_duplicate_page && ! $is_legacy_individual ) {
            return;
        }

        $target = home_url( '/' . self::TARGET_PAGE_SLUG . '/' );

        if ( function_exists( 'wp_safe_redirect' ) ) {
            wp_safe_redirect( $target, 301, 'Server Turizm Umre Semantic Adapter' );
            exit;
        }
    }

    /** Start buffering on the public HTML surfaces this adapter owns. */
    public static function maybe_start_buffer() {
        if ( ! self::is_enabled() ) {
            return;
        }

        if ( is_admin() ) {
            return;
        }

        if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
            return;
        }

        if ( function_exists( 'wp_doing_ajax' ) && wp_doing_ajax() ) {
            return;
        }

        // v0.17.0 accepted: homepage keeps its dedicated schema/output path.
        if ( function_exists( 'is_front_page' ) && is_front_page() ) {
            ob_start( array( __CLASS__, 'filter_home_output' ) );
            return;
        }

        // Accepted Umre transformation pipeline remains isolated to /umre-1/.
        if ( is_page( self::TARGET_PAGE_SLUG ) ) {
            ob_start( array( __CLASS__, 'filter_output' ) );
            return;
        }

        // v0.26.0 candidate: /oteller/ is rendered by the current site in a
        // way that does not satisfy WordPress is_singular(), even though it
        // contains the same shared legacy Umre anchor. Cover this one verified
        // path explicitly, using the exact same href-only normalization.
        $request_uri = isset( $_SERVER['REQUEST_URI'] ) ? (string) $_SERVER['REQUEST_URI'] : '';
        $request_path = wp_parse_url( $request_uri, PHP_URL_PATH );
        $normalized_request_path = is_string( $request_path ) ? trailingslashit( $request_path ) : '';

        if ( '/oteller/' === $normalized_request_path ) {
            ob_start( array( __CLASS__, 'filter_public_singular_output' ) );
            return;
        }

        // v0.25.0 accepted baseline: the same verified legacy Umre href is
        // emitted by shared/hard-coded site content across multiple public
        // singular documents. Apply only the exact anchor-href normalization;
        // no schema, metadata, card or visual transformation runs.
        if ( function_exists( 'is_singular' ) && is_singular() ) {
            ob_start( array( __CLASS__, 'filter_public_singular_output' ) );
        }
    }

    /**
     * v0.33.0 candidate: dequeue only the exact shop/search handles proven by
     * runtime DOM audit to be unused on /umre-1/.
     *
     * Deliberately NOT removed in this first candidate:
     * - jquery-blockui
     * - js-cookie
     * - jquery-cookie
     * Those handles are generic enough that another feature could consume them;
     * they can be considered separately only after a dedicated dependency test.
     */
    public static function dequeue_unused_umre_shop_assets() {
        if ( ! self::is_enabled() || is_admin() ) {
            return;
        }

        if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
            return;
        }

        if ( function_exists( 'wp_doing_ajax' ) && wp_doing_ajax() ) {
            return;
        }

        if ( ! function_exists( 'is_page' ) || ! is_page( self::TARGET_PAGE_SLUG ) ) {
            return;
        }

        $style_handles = array(
            'yith_wcas_frontend',
            'porto-theme-shop',
        );

        $script_handles = array(
            'wc-add-to-cart',
            'vc_woocommerce-add-to-cart-js',
            'woocommerce',
            'yith_autocomplete',
            'porto-woocommerce-theme',
        );

        foreach ( $style_handles as $handle ) {
            wp_dequeue_style( $handle );
        }

        foreach ( $script_handles as $handle ) {
            wp_dequeue_script( $handle );
        }
    }

    /**
     * Global emergency kill switch + filterable runtime switch.
     *
     * To disable without deactivating the plugin, add this to wp-config.php:
     * define( 'ST_UMRE_SEMANTIC_ADAPTER_DISABLED', true );
     */
    private static function is_enabled() {
        if ( defined( 'ST_UMRE_SEMANTIC_ADAPTER_DISABLED' ) && ST_UMRE_SEMANTIC_ADAPTER_DISABLED ) {
            return false;
        }

        return (bool) apply_filters( 'st_umre_semantic_adapter_enabled', true );
    }

    /** Output-buffer callback. */
    public static function filter_output( $html ) {
        if ( ! is_string( $html ) || '' === $html ) {
            return $html;
        }

        if ( false === strpos( $html, self::CARD_CLASS ) ) {
            return $html;
        }

        $transformed = 0;
        $html = self::transform_program_wrappers( $html, $transformed );

        // v0.15.0 accepted expansion candidate: promote every rendered Umre
        // program's visible .lc-title from a generic DIV to an H2. The existing
        // lc-title class is preserved and only native heading spacing is
        // neutralized inline to protect the frozen visual design.
        $program_headings_changed = 0;
        $html = self::promote_all_program_titles_to_h2( $html, $program_headings_changed );

        $main_image_alts_added = 0;
        $html = self::add_all_program_main_image_alts( $html, $main_image_alts_added );

        // v0.31.0 performance candidate: restore responsive-image delivery for
        // generated Program main images by deriving srcset candidates from the
        // matching WordPress Media Library attachment. The original src remains
        // unchanged as a safe fallback; no lazy-loading, dimensions, CSS,
        // generator data or visible markup are otherwise altered.
        $main_image_responsive_hints_added = 0;
        $html = self::add_all_program_main_image_responsive_hints( $html, $main_image_responsive_hints_added );

        // v0.7.0 accepted baseline: hotel-thumbnail ALT behavior on all
        // rendered Umre cards, including optional third-country hotel blocks.
        $hotel_thumbnail_alts_added = 0;
        $html = self::add_all_hotel_thumbnail_alts( $html, $hotel_thumbnail_alts_added );

        // v0.30.0 performance candidate: add native lazy-loading and async
        // decoding only to hotel gallery thumbnails inside .lc-thumbs. Main
        // program images, logos, airline images, URLs, CSS and JavaScript are
        // deliberately untouched.
        $hotel_thumbnail_loading_hints_added = 0;
        $html = self::add_all_hotel_thumbnail_loading_hints( $html, $hotel_thumbnail_loading_hints_added );

        // v0.9.0 accepted expansion candidate: add accessible names to hotel
        // KONUM / GALERİ buttons on all rendered Umre cards, including optional
        // third-country hotel blocks. No button text, classes or JS are changed.
        $hotel_action_aria_added = 0;
        $html = self::add_all_hotel_action_aria_labels( $html, $hotel_action_aria_added );

        // v0.11.0 accepted expansion candidate: add dynamic accessible names to
        // Google Calendar route/date links on every rendered Umre card. The
        // visible route text, href, target, dates, countdown and JavaScript
        // remain unchanged.
        $calendar_aria_added = 0;
        $html = self::add_all_calendar_link_aria_labels( $html, $calendar_aria_added );

        // v0.13.0 accepted expansion candidate: ensure rel="noopener" on all
        // Google Calendar links opened in a new tab across every rendered Umre
        // card. No URL, target, visible text, ARIA label, CSS or JavaScript
        // is changed.
        $calendar_noopener_added = 0;
        $html = self::add_all_calendar_noopener( $html, $calendar_noopener_added );

        // v0.16.0 candidate: add one server-rendered JSON-LD graph to the
        // public Umre page. The graph contains the verified Server Turizm
        // TravelAgency entity plus the current Umre WebPage entity. It is
        // deliberately minimal: no Product, Offer, Review, AggregateRating,
        // FAQPage or invented data.
        $schema_graph_added = 0;
        $html = self::add_umre_schema_graph( $html, $schema_graph_added );

        // v0.18.0 candidate: optimize only the /umre-1/ document title and
        // meta description. The year range is derived from the current H1 so
        // future 2027–2028-style updates can flow through without hard-coding
        // program data, prices, hotels or dates in the plugin.
        $search_metadata_changed = 0;
        $html = self::optimize_umre_search_metadata( $html, $search_metadata_changed );

        // v0.25.0 candidate: keep shared/global legacy Umre anchors canonical
        // on the Umre hub itself as well. Only the two exact verified href
        // destinations are eligible; all accepted Umre transformations remain
        // otherwise unchanged.
        $content_links_changed = 0;
        $html = self::rewrite_legacy_umre_anchor_urls_in_html( $html, $content_links_changed );

        return $html;
    }

    /**
     * v0.17.0 candidate: homepage-only output callback.
     * No visible markup is changed; only the homepage JSON-LD graph is added.
     *
     * @param string $html
     * @return string
     */
    public static function filter_home_output( $html ) {
        if ( ! is_string( $html ) || '' === $html ) {
            return $html;
        }

        $schema_graph_added = 0;
        $html = self::add_home_schema_graph( $html, $schema_graph_added );

        // v0.24.0 candidate: the homepage contains at least one legacy Umre
        // link authored directly inside page-builder/content HTML rather than
        // through a WordPress nav-menu object. Rewrite only anchor href values
        // whose parsed path exactly matches one of the two verified legacy
        // Umre destinations. Link text, classes, attributes and all unrelated
        // URLs remain byte-preserved.
        $content_links_changed = 0;
        $html = self::rewrite_legacy_umre_anchor_urls_in_html( $html, $content_links_changed );

        return $html;
    }


    /**
     * v0.25.0 candidate: on non-home, non-Umre singular public documents,
     * normalize only the two verified legacy Umre anchor destinations.
     * No schema, metadata, CSS, JavaScript or visible markup is otherwise
     * changed.
     *
     * @param string $html
     * @return string
     */
    public static function filter_public_singular_output( $html ) {
        if ( ! is_string( $html ) || '' === $html ) {
            return $html;
        }

        $content_links_changed = 0;
        return self::rewrite_legacy_umre_anchor_urls_in_html( $html, $content_links_changed );
    }

    /**
     * v0.25.0 accepted/expanded helper: normalize hard-coded legacy Umre anchors
     * in rendered front-end HTML to the canonical /umre-1/ hub.
     *
     * Safety contract:
     * - Called only from explicitly scoped front-end output callbacks.
     * - Only <a ... href="..."> attributes are eligible.
     * - Only the two exact verified legacy paths are eligible.
     * - External hosts are ignored.
     * - No visible link text, classes, targets, rel values, surrounding HTML,
     *   scripts or non-anchor URL strings are changed.
     *
     * @param string $html
     * @param int    $changed Number of href values changed (by reference).
     * @return string
     */
    public static function rewrite_legacy_umre_anchor_urls_in_html( $html, &$changed = 0 ) {
        $changed = 0;

        if ( ! is_string( $html ) || '' === $html ) {
            return $html;
        }

        $legacy_paths = array(
            '/tour-category/ekonomik-umre-programi/',
            '/ekonomik-umre-programlari/'
        );

        $target = home_url( '/' . self::TARGET_PAGE_SLUG . '/' );
        $home_host = wp_parse_url( home_url( '/' ), PHP_URL_HOST );

        $result = preg_replace_callback(
            '~<a\b[^>]*\bhref\s*=\s*(["\'])(.*?)\1[^>]*>~is',
            static function ( $match ) use ( $legacy_paths, $target, $home_host, &$changed ) {
                $tag = $match[0];
                $quote = $match[1];
                $raw_href = html_entity_decode( $match[2], ENT_QUOTES | ENT_HTML5, 'UTF-8' );
                $path = wp_parse_url( $raw_href, PHP_URL_PATH );
                $host = wp_parse_url( $raw_href, PHP_URL_HOST );

                if ( ! is_string( $path ) ) {
                    return $tag;
                }

                if ( is_string( $host ) && '' !== $host && is_string( $home_host ) && 0 !== strcasecmp( $host, $home_host ) ) {
                    return $tag;
                }

                $normalized = trailingslashit( $path );
                if ( ! in_array( $normalized, $legacy_paths, true ) ) {
                    return $tag;
                }

                $href_pattern = '~(\bhref\s*=\s*)' . preg_quote( $quote, '~' ) . '.*?' . preg_quote( $quote, '~' ) . '~is';
                $new_tag = preg_replace( $href_pattern, '$1' . $quote . esc_url( $target ) . $quote, $tag, 1 );

                if ( is_string( $new_tag ) && $new_tag !== $tag ) {
                    ++$changed;
                    return $new_tag;
                }

                return $tag;
            },
            $html
        );

        return is_string( $result ) ? $result : $html;
    }

    /**
     * Change only the root element of every generated Umre card:
     *   <div class="lc-card-template" ...> ... </div>
     * becomes:
     *   <article class="lc-card-template" ...> ... </article>
     *
     * The inner markup is not parsed, normalized, decoded, or re-serialized.
     * This intentionally preserves the existing generated HTML byte-for-byte
     * except for the two root tag names.
     *
     * @param string $html
     * @param int    $transformed Number of transformed cards (by reference).
     * @return string
     */
    public static function transform_program_wrappers( $html, &$transformed = 0 ) {
        $transformed = 0;
        $offset = 0;
        $html_length = strlen( $html );

        while ( $offset < $html_length && preg_match( '~<div\\b[^>]*>~i', $html, $open_match, PREG_OFFSET_CAPTURE, $offset ) ) {
            $open_tag = $open_match[0][0];
            $open_start = $open_match[0][1];
            $open_end = $open_start + strlen( $open_tag );

            if ( ! self::tag_has_class( $open_tag, self::CARD_CLASS ) ) {
                $offset = $open_end;
                continue;
            }

            $depth = 1;
            $cursor = $open_end;
            $close_start = null;
            $close_end = null;

            while ( $depth > 0 && preg_match( '~</?div\\b[^>]*>~i', $html, $tag_match, PREG_OFFSET_CAPTURE, $cursor ) ) {
                $tag = $tag_match[0][0];
                $tag_start = $tag_match[0][1];
                $tag_end = $tag_start + strlen( $tag );

                if ( 0 === stripos( $tag, '</div' ) ) {
                    --$depth;
                } else {
                    ++$depth;
                }

                $cursor = $tag_end;

                if ( 0 === $depth ) {
                    $close_start = $tag_start;
                    $close_end = $tag_end;
                    break;
                }
            }

            // Fail safely: if a matching closing tag cannot be proven, leave output untouched.
            if ( null === $close_start || null === $close_end ) {
                $offset = $open_end;
                continue;
            }

            $new_open_tag = '<article' . substr( $open_tag, 4 );
            $inner_html = substr( $html, $open_end, $close_start - $open_end );
            $replacement = $new_open_tag . $inner_html . '</article>';

            $html = substr( $html, 0, $open_start ) . $replacement . substr( $html, $close_end );

            ++$transformed;
            $offset = $open_start + strlen( $replacement );
            $html_length = strlen( $html );
        }

        return $html;
    }


    /**
     * v0.15.0: convert every rendered Umre card's visible .lc-title wrapper
     * from <div> to <h2>, preserving the exact title content and class list.
     *
     * The current production CSS already defines title typography via
     * .lc-title. Native H2 margin/padding are neutralized inline so the semantic
     * element change does not introduce browser/theme heading spacing.
     *
     * Scope:
     * - Every <article class="lc-card-template"> generated on the Umre page.
     * - First .lc-title DIV inside each card only.
     * - Existing style values, if any, are preserved and the reset is appended.
     * - Card data, IDs, scripts, dates, prices and all other markup stay intact.
     *
     * @param string $html
     * @param int    $changed Number of headings changed (by reference).
     * @return string
     */
    public static function promote_all_program_titles_to_h2( $html, &$changed = 0 ) {
        $changed = 0;
        $offset = 0;
        $html_length = strlen( $html );

        while ( $offset < $html_length && preg_match( '~<article\\b[^>]*>~i', $html, $card_match, PREG_OFFSET_CAPTURE, $offset ) ) {
            $card_open_tag = $card_match[0][0];
            $card_start = $card_match[0][1];
            $card_open_end = $card_start + strlen( $card_open_tag );

            if ( ! self::tag_has_class( $card_open_tag, self::CARD_CLASS ) ) {
                $offset = $card_open_end;
                continue;
            }

            $card_close_start = stripos( $html, '</article>', $card_open_end );
            if ( false === $card_close_start ) {
                $offset = $card_open_end;
                continue;
            }

            $card_end = $card_close_start + strlen( '</article>' );
            $card_html = substr( $html, $card_start, $card_end - $card_start );
            $title_range = self::find_div_range_by_class( $card_html, 'lc-title', 0 );

            if ( null === $title_range ) {
                $offset = $card_end;
                continue;
            }

            list( $title_start, $title_end ) = $title_range;
            $title_html = substr( $card_html, $title_start, $title_end - $title_start );

            if ( ! preg_match( '~^<div\\b[^>]*>~i', $title_html, $open_match ) ) {
                $offset = $card_end;
                continue;
            }

            $open_tag = $open_match[0];
            $open_end = strlen( $open_tag );

            if ( 0 !== strcasecmp( substr( $title_html, -6 ), '</div>' ) ) {
                $offset = $card_end;
                continue;
            }

            $inner_html = substr( $title_html, $open_end, -6 );
            $new_open_tag = '<h2' . substr( $open_tag, 4 );
            $reset = 'margin:0;padding:0;';

            if ( preg_match( '~\\bstyle\\s*=\\s*(["\\\'])(.*?)\\1~is', $new_open_tag, $style_match, PREG_OFFSET_CAPTURE ) ) {
                $style_value = rtrim( $style_match[2][0] );
                if ( '' !== $style_value && ';' !== substr( $style_value, -1 ) ) {
                    $style_value .= ';';
                }
                $new_style_value = $style_value . $reset;
                $full_style = $style_match[0][0];
                $full_style_start = $style_match[0][1];
                $quote = $style_match[1][0];
                $new_style = 'style=' . $quote . esc_attr( $new_style_value ) . $quote;
                $new_open_tag = substr( $new_open_tag, 0, $full_style_start ) . $new_style . substr( $new_open_tag, $full_style_start + strlen( $full_style ) );
            } else {
                $new_open_tag = substr( $new_open_tag, 0, -1 ) . ' style="' . $reset . '">';
            }

            $new_title_html = $new_open_tag . $inner_html . '</h2>';
            $new_card_html = substr( $card_html, 0, $title_start ) . $new_title_html . substr( $card_html, $title_end );

            $html = substr( $html, 0, $card_start ) . $new_card_html . substr( $html, $card_end );
            ++$changed;

            $offset = $card_start + strlen( $new_card_html );
            $html_length = strlen( $html );
        }

        return $html;
    }

    /**
     * v0.5.0: add an alt attribute to the MAIN image of every generated
     * Umre program card, using that card's visible .lc-title text.
     *
     * Scope is intentionally narrow:
     * - Only the first <img> inside .lc-img is eligible.
     * - Hotel thumbnails are not touched.
     * - Airline logos are not touched.
     * - Existing alt attributes are never overwritten.
     *
     * @param string $html
     * @param int    $changed Number of images changed (by reference).
     * @return string
     */
    public static function add_all_program_main_image_alts( $html, &$changed = 0 ) {
        $changed = 0;
        $offset = 0;
        $html_length = strlen( $html );

        while ( $offset < $html_length && preg_match( '~<article\\b[^>]*>~i', $html, $card_match, PREG_OFFSET_CAPTURE, $offset ) ) {
            $card_open_tag = $card_match[0][0];
            $card_start = $card_match[0][1];
            $card_open_end = $card_start + strlen( $card_open_tag );

            if ( ! self::tag_has_class( $card_open_tag, self::CARD_CLASS ) ) {
                $offset = $card_open_end;
                continue;
            }

            $card_close_start = stripos( $html, '</article>', $card_open_end );
            if ( false === $card_close_start ) {
                // Fail safely if a closing article cannot be proven.
                $offset = $card_open_end;
                continue;
            }

            $card_end = $card_close_start + strlen( '</article>' );
            $card_html = substr( $html, $card_start, $card_end - $card_start );

            if ( ! preg_match( '~<(?:div|h2)\\b[^>]*\\bclass\\s*=\\s*(["\\\'])[^"\\\']*\\blc-title\\b[^"\\\']*\\1[^>]*>(.*?)</(?:div|h2)>~is', $card_html, $title_match ) ) {
                $offset = $card_end;
                continue;
            }

            $title = html_entity_decode( wp_strip_all_tags( $title_match[2] ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );
            $title = trim( preg_replace( '~\\s+~u', ' ', $title ) );

            if ( '' === $title ) {
                $offset = $card_end;
                continue;
            }

            $alt = esc_attr( $title );
            $card_changed = 0;
            $pattern = '~(<div\\b[^>]*\\bclass\\s*=\\s*(["\\\'])[^"\\\']*\\blc-img\\b[^"\\\']*\\2[^>]*>\\s*<img\\b)([^>]*)(>)~is';

            $new_card_html = preg_replace_callback(
                $pattern,
                static function ( $match ) use ( $alt, &$card_changed ) {
                    if ( preg_match( '~\\balt\\s*=~i', $match[3] ) ) {
                        return $match[0];
                    }

                    ++$card_changed;
                    return $match[1] . $match[3] . ' alt="' . $alt . '"' . $match[4];
                },
                $card_html,
                1
            );

            if ( is_string( $new_card_html ) && $card_changed > 0 ) {
                $html = substr( $html, 0, $card_start ) . $new_card_html . substr( $html, $card_end );
                $delta = strlen( $new_card_html ) - strlen( $card_html );
                $changed += $card_changed;
                $card_end += $delta;
                $html_length += $delta;
            }

            $offset = $card_end;
        }

        return $html;
    }

    /**
     * v0.31.0 performance candidate: add responsive-image hints to the MAIN
     * image of each generated Umre program card using WordPress attachment
     * derivatives when they can be proven from the existing src URL.
     *
     * Safety contract:
     * - Only the first <img> inside .lc-img is eligible.
     * - Existing srcset/sizes are never overwritten.
     * - The original src URL is never changed.
     * - No loading="lazy" or fetchpriority is added here.
     * - If the src cannot be mapped to a Media Library attachment, no change.
     * - Hotel thumbnails, logos, airline images, CSS and JavaScript untouched.
     *
     * @param string $html
     * @param int    $changed Number of image tags changed (by reference).
     * @return string
     */
    public static function add_all_program_main_image_responsive_hints( $html, &$changed = 0 ) {
        $changed = 0;
        $offset = 0;
        $html_length = strlen( $html );
        $attachment_cache = array();

        while ( $offset < $html_length && preg_match( '~<article\\b[^>]*>~i', $html, $card_match, PREG_OFFSET_CAPTURE, $offset ) ) {
            $card_open_tag = $card_match[0][0];
            $card_start = $card_match[0][1];
            $card_open_end = $card_start + strlen( $card_open_tag );

            if ( ! self::tag_has_class( $card_open_tag, self::CARD_CLASS ) ) {
                $offset = $card_open_end;
                continue;
            }

            $card_close_start = stripos( $html, '</article>', $card_open_end );
            if ( false === $card_close_start ) {
                $offset = $card_open_end;
                continue;
            }

            $card_end = $card_close_start + strlen( '</article>' );
            $card_html = substr( $html, $card_start, $card_end - $card_start );
            $card_changed = 0;

            $pattern = '~(<div\\b[^>]*\\bclass\\s*=\\s*(["\\\'])[^"\\\']*\\blc-img\\b[^"\\\']*\\2[^>]*>\\s*<img\\b)([^>]*)(>)~is';

            $new_card_html = preg_replace_callback(
                $pattern,
                static function ( $match ) use ( &$card_changed, &$attachment_cache ) {
                    $attrs = $match[3];

                    if ( preg_match( '~\\bsrcset\\s*=~i', $attrs ) ) {
                        return $match[0];
                    }

                    if ( ! preg_match( '~\\bsrc\\s*=\\s*(["\\\'])(.*?)\\1~is', $attrs, $src_match ) ) {
                        return $match[0];
                    }

                    $src = html_entity_decode( trim( $src_match[2] ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );
                    if ( '' === $src ) {
                        return $match[0];
                    }

                    $cache_key = preg_replace( '~[?#].*$~', '', $src );
                    if ( ! array_key_exists( $cache_key, $attachment_cache ) ) {
                        $attachment_id = attachment_url_to_postid( $cache_key );
                        $srcset = false;

                        if ( $attachment_id ) {
                            $srcset = wp_get_attachment_image_srcset( $attachment_id, 'medium_large' );
                            if ( ! $srcset ) {
                                $srcset = wp_get_attachment_image_srcset( $attachment_id, 'large' );
                            }
                        }

                        $attachment_cache[ $cache_key ] = $srcset ? $srcset : false;
                    }

                    $srcset = $attachment_cache[ $cache_key ];
                    if ( ! $srcset ) {
                        return $match[0];
                    }

                    $add = ' srcset="' . esc_attr( $srcset ) . '"';

                    if ( ! preg_match( '~\\bsizes\\s*=~i', $attrs ) ) {
                        $add .= ' sizes="(max-width: 480px) calc(100vw - 32px), 399px"';
                    }

                    if ( ! preg_match( '~\\bdecoding\\s*=~i', $attrs ) ) {
                        $add .= ' decoding="async"';
                    }

                    ++$card_changed;
                    return $match[1] . $attrs . $add . $match[4];
                },
                $card_html,
                1
            );

            if ( is_string( $new_card_html ) && $card_changed > 0 ) {
                $html = substr( $html, 0, $card_start ) . $new_card_html . substr( $html, $card_end );
                $delta = strlen( $new_card_html ) - strlen( $card_html );
                $changed += $card_changed;
                $card_end += $delta;
                $html_length += $delta;
            }

            $offset = $card_end;
        }

        return $html;
    }

    /**
     * v0.7.0: add descriptive ALT text to hotel thumbnails in every rendered
     * Umre card. The real hotel name comes from .lc-h-name. For Medina and
     * Makkah the city labels are fixed from the existing block classes; for an
     * optional third-country block the location label is read from .lc-h-title
     * (for example "KAHIRE OTELİ" -> "KAHIRE").
     *
     * Scope remains intentionally narrow:
     * - Images inside .lc-thumbs only.
     * - Existing alt attributes are never overwritten.
     * - Main images, airline logos, JavaScript gallery strings and file URLs
     *   are not touched.
     *
     * @param string $html
     * @param int    $changed Number of thumbnail images changed (by reference).
     * @return string
     */
    public static function add_all_hotel_thumbnail_alts( $html, &$changed = 0 ) {
        $changed = 0;
        $offset = 0;
        $html_length = strlen( $html );

        while ( $offset < $html_length && preg_match( '~<article\b[^>]*>~i', $html, $card_match, PREG_OFFSET_CAPTURE, $offset ) ) {
            $card_open_tag = $card_match[0][0];
            $card_start = $card_match[0][1];
            $card_open_end = $card_start + strlen( $card_open_tag );

            if ( ! self::tag_has_class( $card_open_tag, self::CARD_CLASS ) ) {
                $offset = $card_open_end;
                continue;
            }

            $card_close_start = stripos( $html, '</article>', $card_open_end );
            if ( false === $card_close_start ) {
                $offset = $card_open_end;
                continue;
            }

            $card_end = $card_close_start + strlen( '</article>' );
            $card_html = substr( $html, $card_start, $card_end - $card_start );
            $card_changed = 0;

            $card_html = self::add_thumbnail_alts_in_hotel_block( $card_html, 'medine-hotel-block', 'Medine', $card_changed );
            $card_html = self::add_thumbnail_alts_in_hotel_block( $card_html, 'mekke-hotel-block', 'Mekke', $card_changed );
            $card_html = self::add_thumbnail_alts_in_hotel_block( $card_html, 'baska-hotel-block', null, $card_changed );

            if ( $card_changed > 0 ) {
                $original_card_length = $card_end - $card_start;
                $html = substr( $html, 0, $card_start ) . $card_html . substr( $html, $card_end );
                $delta = strlen( $card_html ) - $original_card_length;
                $changed += $card_changed;
                $card_end += $delta;
                $html_length += $delta;
            }

            $offset = $card_end;
        }

        return $html;
    }

    /**
     * Add ALT text to all <img> elements inside .lc-thumbs of one hotel block.
     * When $city_label is null, derive the location label from .lc-h-title by
     * removing the trailing Turkish "OTELİ" word.
     */
    private static function add_thumbnail_alts_in_hotel_block( $card_html, $block_class, $city_label, &$changed ) {
        $block_range = self::find_div_range_by_class( $card_html, $block_class, 0 );
        if ( null === $block_range ) {
            return $card_html;
        }

        list( $block_start, $block_end ) = $block_range;
        $block_html = substr( $card_html, $block_start, $block_end - $block_start );

        if ( ! preg_match( '~<span\b[^>]*\bclass\s*=\s*(["\'])[^"\']*\blc-h-name\b[^"\']*\1[^>]*>(.*?)</span>~is', $block_html, $name_match ) ) {
            return $card_html;
        }

        $hotel_name = html_entity_decode( wp_strip_all_tags( $name_match[2] ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );
        $hotel_name = trim( preg_replace( '~\s+~u', ' ', $hotel_name ) );
        if ( '' === $hotel_name ) {
            return $card_html;
        }

        if ( null === $city_label ) {
            if ( ! preg_match( '~<span\b[^>]*\bclass\s*=\s*(["\'])[^"\']*\blc-h-title\b[^"\']*\1[^>]*>(.*?)</span>~is', $block_html, $title_match ) ) {
                return $card_html;
            }

            $city_label = html_entity_decode( wp_strip_all_tags( $title_match[2] ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );
            $city_label = trim( preg_replace( '~\s+~u', ' ', $city_label ) );
            $city_label = trim( preg_replace( '~\s+OTELİ\s*$~iu', '', $city_label ) );

            if ( '' === $city_label ) {
                return $card_html;
            }
        }

        $thumbs_range = self::find_div_range_by_class( $block_html, 'lc-thumbs', 0 );
        if ( null === $thumbs_range ) {
            return $card_html;
        }

        list( $thumbs_start, $thumbs_end ) = $thumbs_range;
        $thumbs_html = substr( $block_html, $thumbs_start, $thumbs_end - $thumbs_start );
        $image_index = 0;
        $local_changed = 0;

        $new_thumbs_html = preg_replace_callback(
            '~(<img\b[^>]*?)(\s*/?>)~is',
            static function ( $match ) use ( $hotel_name, $city_label, &$image_index, &$local_changed ) {
                ++$image_index;

                // Preserve any manually supplied ALT text.
                if ( preg_match( '~\balt\s*=~i', $match[1] ) ) {
                    return $match[0];
                }

                $alt = esc_attr( $hotel_name . ', ' . $city_label . ' - otel görseli ' . $image_index );
                ++$local_changed;
                return $match[1] . ' alt="' . $alt . '"' . $match[2];
            },
            $thumbs_html
        );

        if ( ! is_string( $new_thumbs_html ) || 0 === $local_changed ) {
            return $card_html;
        }

        $new_block_html = substr( $block_html, 0, $thumbs_start ) . $new_thumbs_html . substr( $block_html, $thumbs_end );
        $changed += $local_changed;

        return substr( $card_html, 0, $block_start ) . $new_block_html . substr( $card_html, $block_end );
    }


    /**
     * v0.30.0 performance candidate: add browser-native image loading hints to
     * hotel thumbnails only.
     *
     * Scope is intentionally narrow and reversible:
     * - Only <img> elements inside .lc-thumbs of verified hotel blocks.
     * - Adds loading="lazy" only when no loading attribute already exists.
     * - Adds decoding="async" only when no decoding attribute already exists.
     * - Does not alter src/srcset, ALT, classes, dimensions, gallery JS, main
     *   program images, logos, airline images or any visible text/style.
     *
     * @param string $html
     * @param int    $changed Number of image tags changed (by reference).
     * @return string
     */
    public static function add_all_hotel_thumbnail_loading_hints( $html, &$changed = 0 ) {
        $changed = 0;
        $offset = 0;
        $html_length = strlen( $html );

        while ( $offset < $html_length && preg_match( '~<article\b[^>]*>~i', $html, $card_match, PREG_OFFSET_CAPTURE, $offset ) ) {
            $card_open_tag = $card_match[0][0];
            $card_start = $card_match[0][1];
            $card_open_end = $card_start + strlen( $card_open_tag );

            if ( ! self::tag_has_class( $card_open_tag, self::CARD_CLASS ) ) {
                $offset = $card_open_end;
                continue;
            }

            $card_close_start = stripos( $html, '</article>', $card_open_end );
            if ( false === $card_close_start ) {
                $offset = $card_open_end;
                continue;
            }

            $card_end = $card_close_start + strlen( '</article>' );
            $card_html = substr( $html, $card_start, $card_end - $card_start );
            $card_changed = 0;

            $card_html = self::add_loading_hints_in_hotel_block( $card_html, 'medine-hotel-block', $card_changed );
            $card_html = self::add_loading_hints_in_hotel_block( $card_html, 'mekke-hotel-block', $card_changed );
            $card_html = self::add_loading_hints_in_hotel_block( $card_html, 'baska-hotel-block', $card_changed );

            if ( $card_changed > 0 ) {
                $original_card_length = $card_end - $card_start;
                $html = substr( $html, 0, $card_start ) . $card_html . substr( $html, $card_end );
                $delta = strlen( $card_html ) - $original_card_length;
                $changed += $card_changed;
                $card_end += $delta;
                $html_length += $delta;
            }

            $offset = $card_end;
        }

        return $html;
    }

    /** Add lazy/async hints to .lc-thumbs images inside one hotel block. */
    private static function add_loading_hints_in_hotel_block( $card_html, $block_class, &$changed ) {
        $block_range = self::find_div_range_by_class( $card_html, $block_class, 0 );
        if ( null === $block_range ) {
            return $card_html;
        }

        list( $block_start, $block_end ) = $block_range;
        $block_html = substr( $card_html, $block_start, $block_end - $block_start );

        $thumbs_range = self::find_div_range_by_class( $block_html, 'lc-thumbs', 0 );
        if ( null === $thumbs_range ) {
            return $card_html;
        }

        list( $thumbs_start, $thumbs_end ) = $thumbs_range;
        $thumbs_html = substr( $block_html, $thumbs_start, $thumbs_end - $thumbs_start );
        $local_changed = 0;

        $new_thumbs_html = preg_replace_callback(
            '~(<img\b)([^>]*?)(\s*/?>)~is',
            static function ( $match ) use ( &$local_changed ) {
                $attrs = $match[2];
                $add = '';

                if ( ! preg_match( '~\bloading\s*=~i', $attrs ) ) {
                    $add .= ' loading="lazy"';
                }

                if ( ! preg_match( '~\bdecoding\s*=~i', $attrs ) ) {
                    $add .= ' decoding="async"';
                }

                if ( '' === $add ) {
                    return $match[0];
                }

                ++$local_changed;
                return $match[1] . $attrs . $add . $match[3];
            },
            $thumbs_html
        );

        if ( ! is_string( $new_thumbs_html ) || 0 === $local_changed ) {
            return $card_html;
        }

        $new_block_html = substr( $block_html, 0, $thumbs_start ) . $new_thumbs_html . substr( $block_html, $thumbs_end );
        $changed += $local_changed;

        return substr( $card_html, 0, $block_start ) . $new_block_html . substr( $card_html, $block_end );
    }

    /**
     * v0.9.0: add aria-label to hotel action buttons (KONUM / GALERİ) on
     * every rendered Umre card, using the visible .lc-h-name hotel name.
     * Existing aria-label values are preserved. No visible text, classes,
     * click behavior, map/gallery logic, CSS or JavaScript are modified.
     *
     * @param string $html
     * @param int    $changed Number of buttons changed (by reference).
     * @return string
     */
    public static function add_all_hotel_action_aria_labels( $html, &$changed = 0 ) {
        $changed = 0;
        $offset = 0;
        $html_length = strlen( $html );

        while ( $offset < $html_length && preg_match( '~<article\b[^>]*>~i', $html, $card_match, PREG_OFFSET_CAPTURE, $offset ) ) {
            $card_open_tag = $card_match[0][0];
            $card_start = $card_match[0][1];
            $card_open_end = $card_start + strlen( $card_open_tag );

            if ( ! self::tag_has_class( $card_open_tag, self::CARD_CLASS ) ) {
                $offset = $card_open_end;
                continue;
            }

            $card_close_start = stripos( $html, '</article>', $card_open_end );
            if ( false === $card_close_start ) {
                $offset = $card_open_end;
                continue;
            }

            $card_end = $card_close_start + strlen( '</article>' );
            $card_html = substr( $html, $card_start, $card_end - $card_start );
            $card_changed = 0;

            $card_html = self::add_action_aria_in_hotel_block( $card_html, 'medine-hotel-block', $card_changed );
            $card_html = self::add_action_aria_in_hotel_block( $card_html, 'mekke-hotel-block', $card_changed );
            $card_html = self::add_action_aria_in_hotel_block( $card_html, 'baska-hotel-block', $card_changed );

            if ( $card_changed > 0 ) {
                $original_card_length = $card_end - $card_start;
                $html = substr( $html, 0, $card_start ) . $card_html . substr( $html, $card_end );
                $delta = strlen( $card_html ) - $original_card_length;
                $changed += $card_changed;
                $card_end += $delta;
                $html_length += $delta;
            }

            $offset = $card_end;
        }

        return $html;
    }

    /** Add accessible names to map/gallery buttons inside one hotel block. */
    private static function add_action_aria_in_hotel_block( $card_html, $block_class, &$changed ) {
        $block_range = self::find_div_range_by_class( $card_html, $block_class, 0 );
        if ( null === $block_range ) {
            return $card_html;
        }

        list( $block_start, $block_end ) = $block_range;
        $block_html = substr( $card_html, $block_start, $block_end - $block_start );

        if ( ! preg_match( '~<span\b[^>]*\bclass\s*=\s*(["\'])[^"\']*\blc-h-name\b[^"\']*\1[^>]*>(.*?)</span>~is', $block_html, $name_match ) ) {
            return $card_html;
        }

        $hotel_name = html_entity_decode( wp_strip_all_tags( $name_match[2] ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );
        $hotel_name = trim( preg_replace( '~\s+~u', ' ', $hotel_name ) );
        if ( '' === $hotel_name ) {
            return $card_html;
        }

        $local_changed = 0;
        $new_block_html = preg_replace_callback(
            '~<button\b[^>]*\bclass\s*=\s*(["\'])([^"\']*\blc-btn-icon\b[^"\']*)\1[^>]*>~is',
            static function ( $match ) use ( $hotel_name, &$local_changed ) {
                $tag = $match[0];

                if ( preg_match( '~\baria-label\s*=~i', $tag ) ) {
                    return $tag;
                }

                $classes = preg_split( '~\s+~', trim( $match[2] ) );
                $action = null;

                foreach ( $classes as $class_name ) {
                    if ( preg_match( '~-map$~', $class_name ) ) {
                        $action = 'konumunu aç';
                        break;
                    }
                    if ( preg_match( '~-img$~', $class_name ) ) {
                        $action = 'galerisini aç';
                        break;
                    }
                }

                if ( null === $action ) {
                    return $tag;
                }

                $aria = esc_attr( $hotel_name . ' ' . $action );
                ++$local_changed;

                return substr( $tag, 0, -1 ) . ' aria-label="' . $aria . '">';
            },
            $block_html
        );

        if ( ! is_string( $new_block_html ) || 0 === $local_changed ) {
            return $card_html;
        }

        $changed += $local_changed;
        return substr( $card_html, 0, $block_start ) . $new_block_html . substr( $card_html, $block_end );
    }

    /**
     * v0.11.0: add aria-label to Google Calendar route links on every rendered
     * Umre program card. Labels are generated at render time from each card's
     * current visible program title, route label and rendered date, so normal
     * Google Sheet / generator data updates require no plugin update.
     *
     * Example:
     *   MEVLİD KANDİLİ MEDİNEDE LÜKS UMRE PROGRAMI - Gidiş 22 Ağustos tarihini Google Takvim'e ekle
     *
     * Existing aria-label attributes are preserved. No href, target, visible
     * text, date markup, Hijri markup, CSS or JavaScript are modified.
     *
     * @param string $html
     * @param int    $changed Number of links changed (by reference).
     * @return string
     */
    public static function add_all_calendar_link_aria_labels( $html, &$changed = 0 ) {
        $changed = 0;
        $offset = 0;
        $html_length = strlen( $html );

        while ( $offset < $html_length && preg_match( '~<article\b[^>]*>~i', $html, $card_match, PREG_OFFSET_CAPTURE, $offset ) ) {
            $card_open_tag = $card_match[0][0];
            $card_start = $card_match[0][1];
            $card_open_end = $card_start + strlen( $card_open_tag );

            if ( ! self::tag_has_class( $card_open_tag, self::CARD_CLASS ) ) {
                $offset = $card_open_end;
                continue;
            }

            $card_close_start = stripos( $html, '</article>', $card_open_end );
            if ( false === $card_close_start ) {
                $offset = $card_open_end;
                continue;
            }

            $card_end = $card_close_start + strlen( '</article>' );
            $card_html = substr( $html, $card_start, $card_end - $card_start );

            if ( ! preg_match( '~<(?:div|h2)\b[^>]*\bclass\s*=\s*(["\'])[^"\']*\blc-title\b[^"\']*\1[^>]*>(.*?)</(?:div|h2)>~is', $card_html, $title_match ) ) {
                $offset = $card_end;
                continue;
            }

            $program_title = html_entity_decode( wp_strip_all_tags( $title_match[2] ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );
            $program_title = trim( preg_replace( '~\s+~u', ' ', $program_title ) );
            if ( '' === $program_title ) {
                $offset = $card_end;
                continue;
            }

            $path_range = self::find_div_range_by_class( $card_html, 'lc-path', 0 );
            if ( null === $path_range ) {
                $offset = $card_end;
                continue;
            }

            list( $path_start, $path_end ) = $path_range;
            $path_html = substr( $card_html, $path_start, $path_end - $path_start );
            $local_changed = 0;

            $new_path_html = preg_replace_callback(
                '~<a\b([^>]*)>(.*?)</a>~is',
                static function ( $match ) use ( $program_title, &$local_changed ) {
                    $attributes = $match[1];
                    $inner_html = $match[2];

                    if ( ! preg_match( '~\bhref\s*=\s*(["\'])(.*?)\1~is', $attributes, $href_match ) ) {
                        return $match[0];
                    }

                    if ( false === stripos( $href_match[2], 'calendar.google.com/calendar/render' ) ) {
                        return $match[0];
                    }

                    if ( preg_match( '~\baria-label\s*=~i', $attributes ) ) {
                        return $match[0];
                    }

                    if ( ! preg_match( '~<span\b[^>]*\bclass\s*=\s*(["\'])[^"\']*\blc-date-label\b[^"\']*\1[^>]*>(.*?)</span>~is', $inner_html, $label_match ) ) {
                        return $match[0];
                    }

                    if ( ! preg_match( '~<b\b[^>]*>(.*?)</b>~is', $inner_html, $date_match ) ) {
                        return $match[0];
                    }

                    $route_label = html_entity_decode( wp_strip_all_tags( $label_match[2] ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );
                    $route_label = trim( preg_replace( '~\s+~u', ' ', $route_label ) );
                    $route_date = html_entity_decode( wp_strip_all_tags( $date_match[1] ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );
                    $route_date = trim( preg_replace( '~\s+~u', ' ', $route_date ) );

                    if ( '' === $route_label || '' === $route_date ) {
                        return $match[0];
                    }

                    $aria = esc_attr( $program_title . ' - ' . $route_label . ' ' . $route_date . " tarihini Google Takvim'e ekle" );
                    ++$local_changed;

                    return '<a' . $attributes . ' aria-label="' . $aria . '">' . $inner_html . '</a>';
                },
                $path_html
            );

            if ( ! is_string( $new_path_html ) || 0 === $local_changed ) {
                $offset = $card_end;
                continue;
            }

            $new_card_html = substr( $card_html, 0, $path_start ) . $new_path_html . substr( $card_html, $path_end );
            $html = substr( $html, 0, $card_start ) . $new_card_html . substr( $html, $card_end );

            $delta = strlen( $new_card_html ) - strlen( $card_html );
            $changed += $local_changed;
            $card_end += $delta;
            $html_length += $delta;
            $offset = $card_end;
        }

        return $html;
    }


    /**
     * v0.13.0: ensure rel="noopener" on Google Calendar links that open in a
     * new tab across every rendered Umre card.
     *
     * Existing rel values are preserved and "noopener" is appended only when
     * missing. href, target, aria-label, visible route/date text, CSS and
     * JavaScript remain unchanged.
     *
     * @param string $html
     * @param int    $changed Number of links changed (by reference).
     * @return string
     */
    public static function add_all_calendar_noopener( $html, &$changed = 0 ) {
        $changed = 0;
        $offset = 0;
        $html_length = strlen( $html );

        while ( $offset < $html_length && preg_match( '~<article\b[^>]*>~i', $html, $card_match, PREG_OFFSET_CAPTURE, $offset ) ) {
            $card_open_tag = $card_match[0][0];
            $card_start = $card_match[0][1];
            $card_open_end = $card_start + strlen( $card_open_tag );

            if ( ! self::tag_has_class( $card_open_tag, self::CARD_CLASS ) ) {
                $offset = $card_open_end;
                continue;
            }

            $card_close_start = stripos( $html, '</article>', $card_open_end );
            if ( false === $card_close_start ) {
                $offset = $card_open_end;
                continue;
            }

            $card_end = $card_close_start + strlen( '</article>' );
            $card_html = substr( $html, $card_start, $card_end - $card_start );
            $path_range = self::find_div_range_by_class( $card_html, 'lc-path', 0 );

            if ( null === $path_range ) {
                $offset = $card_end;
                continue;
            }

            list( $path_start, $path_end ) = $path_range;
            $path_html = substr( $card_html, $path_start, $path_end - $path_start );
            $local_changed = 0;

            $new_path_html = preg_replace_callback(
                '~<a\b([^>]*)>~is',
                static function ( $match ) use ( &$local_changed ) {
                    $attributes = $match[1];

                    if ( ! preg_match( '~\bhref\s*=\s*(["\'])(.*?)\1~is', $attributes, $href_match ) ) {
                        return $match[0];
                    }

                    if ( false === stripos( $href_match[2], 'calendar.google.com/calendar/render' ) ) {
                        return $match[0];
                    }

                    if ( ! preg_match( '~\btarget\s*=\s*(["\'])_blank\1~i', $attributes ) ) {
                        return $match[0];
                    }

                    if ( preg_match( '~\brel\s*=\s*(["\'])(.*?)\1~is', $attributes, $rel_match, PREG_OFFSET_CAPTURE ) ) {
                        $rel_value = $rel_match[2][0];
                        $tokens = preg_split( '~\s+~', trim( $rel_value ) );
                        $tokens_lower = array_map( 'strtolower', array_filter( $tokens, 'strlen' ) );

                        if ( in_array( 'noopener', $tokens_lower, true ) ) {
                            return $match[0];
                        }

                        $new_rel_value = trim( $rel_value . ' noopener' );
                        $full_rel = $rel_match[0][0];
                        $full_rel_start = $rel_match[0][1];
                        $quote = $rel_match[1][0];
                        $new_rel = 'rel=' . $quote . esc_attr( $new_rel_value ) . $quote;
                        $attributes = substr( $attributes, 0, $full_rel_start ) . $new_rel . substr( $attributes, $full_rel_start + strlen( $full_rel ) );
                        ++$local_changed;
                        return '<a' . $attributes . '>';
                    }

                    ++$local_changed;
                    return '<a' . $attributes . ' rel="noopener">';
                },
                $path_html
            );

            if ( ! is_string( $new_path_html ) || 0 === $local_changed ) {
                $offset = $card_end;
                continue;
            }

            $new_card_html = substr( $card_html, 0, $path_start ) . $new_path_html . substr( $card_html, $path_end );
            $html = substr( $html, 0, $card_start ) . $new_card_html . substr( $html, $card_end );

            $delta = strlen( $new_card_html ) - strlen( $card_html );
            $changed += $local_changed;
            $card_end += $delta;
            $html_length += $delta;
            $offset = $card_end;
        }

        return $html;
    }

    /**
     * v0.16.0 candidate: inject one JSON-LD @graph on /umre-1/ containing:
     * - Server Turizm as a TravelAgency
     * - the current Umre landing page as a WebPage
     *
     * Verified business facts only. The primary phone is exposed directly on
     * the TravelAgency and both valid office numbers are represented as
     * ContactPoint nodes. The WebPage name is generated at render time from
     * the page's current H1 so future content-title updates do not require a
     * plugin release.
     *
     * The script is inserted before </head> when available, otherwise before
     * </body>. Existing JSON-LD is not modified and the adapter's own graph is
     * never duplicated.
     *
     * @param string $html
     * @param int    $changed 1 when the graph is inserted, otherwise 0.
     * @return string
     */
    public static function add_umre_schema_graph( $html, &$changed = 0 ) {
        $changed = 0;

        if ( false !== stripos( $html, 'id="st-umre-schema-jsonld"' ) || false !== stripos( $html, "id='st-umre-schema-jsonld'" ) ) {
            return $html;
        }

        $page_name = self::extract_first_h1_text( $html );
        if ( '' === $page_name ) {
            return $html;
        }

        $site_url = rtrim( home_url( '/' ), '/' ) . '/';
        $page_url = rtrim( home_url( '/umre-1/' ), '/' ) . '/';
        $agency_id = $site_url . '#organization';
        $page_id = $page_url . '#webpage';

        $graph = array(
            '@context' => 'https://schema.org',
            '@graph'   => array(
                array(
                    '@type'     => 'TravelAgency',
                    '@id'       => $agency_id,
                    'name'      => 'Server Turizm',
                    'url'       => $site_url,
                    'email'     => 'server@serverturizm.com.tr',
                    'telephone' => '+90 212 621 05 00',
                    'contactPoint' => array(
                        array(
                            '@type'       => 'ContactPoint',
                            'telephone'   => '+90 212 621 05 00',
                            'contactType' => 'customer service',
                        ),
                        array(
                            '@type'       => 'ContactPoint',
                            'telephone'   => '+90 212 621 06 00',
                            'contactType' => 'customer service',
                        ),
                    ),
                    'address' => array(
                        '@type'           => 'PostalAddress',
                        'streetAddress'   => 'Akşemsettin Mah. Akdeniz Cad. No:6, Kat:4 Daire:7',
                        'addressLocality' => 'Fatih',
                        'addressRegion'   => 'İstanbul',
                        'addressCountry'  => 'TR',
                    ),
                    'openingHoursSpecification' => array(
                        array(
                            '@type'     => 'OpeningHoursSpecification',
                            'dayOfWeek' => array(
                                'Monday',
                                'Tuesday',
                                'Wednesday',
                                'Thursday',
                                'Friday',
                            ),
                            'opens'  => '09:00',
                            'closes' => '18:00',
                        ),
                    ),
                    'identifier' => array(
                        '@type' => 'PropertyValue',
                        'name'  => 'TÜRSAB Belge No',
                        'value' => '6532',
                    ),
                ),
                array(
                    '@type'      => 'WebPage',
                    '@id'        => $page_id,
                    'url'        => $page_url,
                    'name'       => $page_name,
                    'inLanguage' => 'tr-TR',
                    'publisher'  => array(
                        '@id' => $agency_id,
                    ),
                ),
            ),
        );

        $json = wp_json_encode( $graph, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
        if ( ! is_string( $json ) || '' === $json ) {
            return $html;
        }

        $script = "\n<script type=\"application/ld+json\" id=\"st-umre-schema-jsonld\">" . $json . "</script>\n";

        $head_close = stripos( $html, '</head>' );
        if ( false !== $head_close ) {
            ++$changed;
            return substr( $html, 0, $head_close ) . $script . substr( $html, $head_close );
        }

        $body_close = stripos( $html, '</body>' );
        if ( false !== $body_close ) {
            ++$changed;
            return substr( $html, 0, $body_close ) . $script . substr( $html, $body_close );
        }

        return $html;
    }

    /**
     * v0.17.0 candidate: inject one JSON-LD @graph on the homepage containing:
     * - Server Turizm as the same TravelAgency entity used on /umre-1/
     * - the root WebSite entity for site-name/entity understanding
     *
     * The WebSite node stays intentionally minimal. Existing JSON-LD is not
     * rewritten and this adapter's own homepage graph is never injected twice.
     *
     * @param string $html
     * @param int    $changed 1 when the graph is inserted, otherwise 0.
     * @return string
     */
    public static function add_home_schema_graph( $html, &$changed = 0 ) {
        $changed = 0;

        if ( false !== stripos( $html, 'id="st-home-schema-jsonld"' ) || false !== stripos( $html, "id='st-home-schema-jsonld'" ) ) {
            return $html;
        }

        $site_url = rtrim( home_url( '/' ), '/' ) . '/';
        $agency_id = $site_url . '#organization';
        $website_id = $site_url . '#website';

        $graph = array(
            '@context' => 'https://schema.org',
            '@graph'   => array(
                array(
                    '@type'     => 'TravelAgency',
                    '@id'       => $agency_id,
                    'name'      => 'Server Turizm',
                    'url'       => $site_url,
                    'email'     => 'server@serverturizm.com.tr',
                    'telephone' => '+90 212 621 05 00',
                    'contactPoint' => array(
                        array(
                            '@type'       => 'ContactPoint',
                            'telephone'   => '+90 212 621 05 00',
                            'contactType' => 'customer service',
                        ),
                        array(
                            '@type'       => 'ContactPoint',
                            'telephone'   => '+90 212 621 06 00',
                            'contactType' => 'customer service',
                        ),
                    ),
                    'address' => array(
                        '@type'           => 'PostalAddress',
                        'streetAddress'   => 'Akşemsettin Mah. Akdeniz Cad. No:6, Kat:4 Daire:7',
                        'addressLocality' => 'Fatih',
                        'addressRegion'   => 'İstanbul',
                        'addressCountry'  => 'TR',
                    ),
                    'openingHoursSpecification' => array(
                        array(
                            '@type'     => 'OpeningHoursSpecification',
                            'dayOfWeek' => array( 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday' ),
                            'opens'     => '09:00',
                            'closes'    => '18:00',
                        ),
                    ),
                    'identifier' => array(
                        '@type' => 'PropertyValue',
                        'name'  => 'TÜRSAB Belge No',
                        'value' => '6532',
                    ),
                ),
                array(
                    '@type'      => 'WebSite',
                    '@id'        => $website_id,
                    'url'        => $site_url,
                    'name'       => 'Server Turizm',
                    'inLanguage' => 'tr-TR',
                    'publisher'  => array( '@id' => $agency_id ),
                ),
            ),
        );

        $json = wp_json_encode( $graph, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
        if ( ! is_string( $json ) || '' === $json ) {
            return $html;
        }

        $script = "\n<script type=\"application/ld+json\" id=\"st-home-schema-jsonld\">" . $json . "</script>\n";

        $head_close = stripos( $html, '</head>' );
        if ( false !== $head_close ) {
            ++$changed;
            return substr( $html, 0, $head_close ) . $script . substr( $html, $head_close );
        }

        $body_close = stripos( $html, '</body>' );
        if ( false !== $body_close ) {
            ++$changed;
            return substr( $html, 0, $body_close ) . $script . substr( $html, $body_close );
        }

        return $html;
    }

    /**
     * v0.18.0 candidate: optimize the /umre-1/ search-snippet metadata only.
     *
     * Title candidate:
     *   {YEAR_RANGE} Umre Turları ve Güncel Fiyatlar | Server Turizm
     *
     * Meta description candidate:
     *   {YEAR_RANGE} Server Turizm Umre turlarını; güncel tarihler, fiyatlar,
     *   çocuk ücretleri, Mekke ve Medine otelleri ve program detaylarıyla
     *   karşılaştırın.
     *
     * The year range is extracted from the current visible H1 when present,
     * preserving the live-page-data principle. Existing title/description
     * elements are replaced rather than duplicated. No visible body markup,
     * card HTML, CSS, JS or structured-data graph is changed here.
     *
     * @param string $html
     * @param int    $changed Number of metadata fields changed (0-2).
     * @return string
     */
    public static function optimize_umre_search_metadata( $html, &$changed = 0 ) {
        $changed = 0;

        $h1 = self::extract_first_h1_text( $html );
        $year_range = '';

        if ( '' !== $h1 && preg_match( '~\b(20\d{2})\s*[–—-]\s*(20\d{2})\b~u', $h1, $year_match ) ) {
            $year_range = $year_match[1] . '–' . $year_match[2];
        }

        $prefix = '' !== $year_range ? $year_range . ' ' : '';
        $title = $prefix . 'Umre Turları ve Güncel Fiyatlar | Server Turizm';
        $description = $prefix . 'Server Turizm Umre turlarını; güncel tarihler, fiyatlar, çocuk ücretleri, Mekke ve Medine otelleri ve program detaylarıyla karşılaştırın.';

        $title_markup = '<title>' . htmlspecialchars( $title, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8' ) . '</title>';

        if ( preg_match( '~<title\b[^>]*>.*?</title>~is', $html ) ) {
            $new_html = preg_replace( '~<title\b[^>]*>.*?</title>~is', $title_markup, $html, 1, $count );
            if ( is_string( $new_html ) && $count > 0 ) {
                $html = $new_html;
                ++$changed;
            }
        } else {
            $head_close = stripos( $html, '</head>' );
            if ( false !== $head_close ) {
                $html = substr( $html, 0, $head_close ) . "\n" . $title_markup . "\n" . substr( $html, $head_close );
                ++$changed;
            }
        }

        $meta_markup = '<meta name="description" content="' . esc_attr( $description ) . '">';
        $meta_pattern = '~<meta\b(?=[^>]*\bname\s*=\s*(["\'])description\1)[^>]*>~is';

        if ( preg_match( $meta_pattern, $html ) ) {
            $new_html = preg_replace( $meta_pattern, $meta_markup, $html, 1, $count );
            if ( is_string( $new_html ) && $count > 0 ) {
                $html = $new_html;
                ++$changed;
            }
        } else {
            $head_close = stripos( $html, '</head>' );
            if ( false !== $head_close ) {
                $html = substr( $html, 0, $head_close ) . "\n" . $meta_markup . "\n" . substr( $html, $head_close );
                ++$changed;
            }
        }

        return $html;
    }

    /** Extract normalized visible text from the first H1 on the page. */
    private static function extract_first_h1_text( $html ) {
        if ( ! preg_match( '~<h1\b[^>]*>(.*?)</h1>~is', $html, $match ) ) {
            return '';
        }

        $text = html_entity_decode( wp_strip_all_tags( $match[1] ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );
        return trim( preg_replace( '~\s+~u', ' ', $text ) );
    }

    /**
     * Find the byte range [start, end) of a complete <div> block carrying
     * a class token, using balanced DIV depth rather than HTML reserialization.
     *
     * @return array|null
     */
    private static function find_div_range_by_class( $html, $class_name, $offset = 0 ) {
        $html_length = strlen( $html );

        while ( $offset < $html_length && preg_match( '~<div\b[^>]*>~i', $html, $open_match, PREG_OFFSET_CAPTURE, $offset ) ) {
            $open_tag = $open_match[0][0];
            $open_start = $open_match[0][1];
            $open_end = $open_start + strlen( $open_tag );

            if ( ! self::tag_has_class( $open_tag, $class_name ) ) {
                $offset = $open_end;
                continue;
            }

            $depth = 1;
            $cursor = $open_end;

            while ( $depth > 0 && preg_match( '~</?div\b[^>]*>~i', $html, $tag_match, PREG_OFFSET_CAPTURE, $cursor ) ) {
                $tag = $tag_match[0][0];
                $tag_start = $tag_match[0][1];
                $tag_end = $tag_start + strlen( $tag );

                if ( 0 === stripos( $tag, '</div' ) ) {
                    --$depth;
                } else {
                    ++$depth;
                }

                $cursor = $tag_end;

                if ( 0 === $depth ) {
                    return array( $open_start, $tag_end );
                }
            }

            return null; // Fail safely if the matching close cannot be proven.
        }

        return null;
    }

    /** Check whether an opening tag's ID begins with a known prefix. */
    private static function tag_id_starts_with( $tag, $prefix ) {
        if ( ! preg_match( '~\bid\s*=\s*(["\'])(.*?)\1~is', $tag, $id_match ) ) {
            return false;
        }

        return 0 === strpos( $id_match[2], $prefix );
    }

    /** Test whether an opening HTML tag contains a specific class token. */
    private static function tag_has_class( $tag, $class_name ) {
        if ( ! preg_match( '~\\bclass\\s*=\\s*(["\\\'])(.*?)\\1~is', $tag, $class_match ) ) {
            return false;
        }

        $classes = preg_split( '~\\s+~', trim( $class_match[2] ) );
        return in_array( $class_name, $classes, true );
    }
}

ST_Umre_Semantic_Adapter::init();
