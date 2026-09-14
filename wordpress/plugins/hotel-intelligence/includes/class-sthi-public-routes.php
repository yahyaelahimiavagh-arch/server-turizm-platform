<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * H9E — public route governance with an explicit publication/indexation master lock.
 *
 * Header/Footer ownership remains outside Hotel Intelligence. This class only
 * owns Hotel Intelligence routes and SEO/indexation behavior for those routes.
 */
final class STHI_Public_Routes {
    const HUB_QUERY          = 'sthi_public_hub';
    const HOTEL_QUERY        = 'sthi_public_hotel';
    const PUBLIC_SLUG_META   = '_sthi_public_slug';
    const SLUG_CONFLICT_META = '_sthi_public_slug_conflict';
    const SLUG_FREEZE_VER    = '0.9.10-h10e-slug-lifecycle-1';
    const PUBLICATION_OPTION  = 'sthi_hotel_public_indexation';

    private static $hotel_post_id = 0;
    private static $hub_id = '';

    public static function init() {
        add_action( 'init', array( __CLASS__, 'register_rewrites' ), 20 );
        add_action( 'init', array( __CLASS__, 'maybe_freeze_public_slugs' ), 40 );
        add_action( 'save_post_sthi_hotel', array( __CLASS__, 'maybe_freeze_saved_hotel_slug' ), 200, 3 );

        add_filter( 'query_vars', array( __CLASS__, 'query_vars' ) );
        add_action( 'template_redirect', array( __CLASS__, 'serve' ), -20 );

        add_filter( 'sthi_hotel_context_post_id', array( __CLASS__, 'hotel_context_post_id' ), 20 );
        add_filter( 'sthi_hotel_context_url', array( __CLASS__, 'hotel_context_url' ), 20, 2 );
        add_filter( 'sthi_hotel_is_public_route_context', array( __CLASS__, 'is_public_hotel_context' ), 20, 2 );
        add_filter( 'sthi_hotel_editor_ribbon_text', array( __CLASS__, 'editor_ribbon_text' ), 20, 2 );

        add_filter( 'sthi_hotel_link_target_is_routable', array( __CLASS__, 'hotel_is_routable' ), 20, 2 );
        add_filter( 'sthi_hotel_link_target_is_indexable', array( __CLASS__, 'hotel_is_indexable_filter' ), 20, 2 );
        add_filter( 'sthi_hotel_public_url', array( __CLASS__, 'hotel_public_url_filter' ), 20, 2 );
        add_filter( 'sthi_hotel_canonical_url', array( __CLASS__, 'hotel_canonical_url_filter' ), 20, 2 );

        add_filter( 'sthi_link_hub_registry', array( __CLASS__, 'link_hub_registry' ), 20 );
        add_filter( 'sthi_registered_public_route_allowed', array( __CLASS__, 'registered_public_route_allowed' ), 20, 5 );

        add_filter( 'body_class', array( __CLASS__, 'hub_body_class' ), 120 );
        add_filter( 'document_title_parts', array( __CLASS__, 'hub_document_title_parts' ), 1010 );
        add_filter( 'pre_get_document_title', array( __CLASS__, 'hub_pre_get_document_title' ), 1010 );
        add_filter( 'wp_robots', array( __CLASS__, 'hub_robots' ), 1010 );
        add_action( 'wp_head', array( __CLASS__, 'hub_start_head_buffer' ), -10010 );
        add_action( 'wp_head', array( __CLASS__, 'hub_finish_head_buffer' ), PHP_INT_MAX );
        add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_hub_assets' ), 31 );
    }

    public static function register_rewrites() {
        add_rewrite_rule( '^oteller/?$', 'index.php?' . self::HUB_QUERY . '=all', 'top' );
        add_rewrite_rule( '^mekke-otelleri/?$', 'index.php?' . self::HUB_QUERY . '=mekke', 'top' );
        add_rewrite_rule( '^medine-otelleri/?$', 'index.php?' . self::HUB_QUERY . '=medine', 'top' );
        add_rewrite_rule( '^otel/([^/]+)/?$', 'index.php?' . self::HOTEL_QUERY . '=$matches[1]', 'top' );
    }

    public static function query_vars( $vars ) {
        $vars[] = self::HUB_QUERY;
        $vars[] = self::HOTEL_QUERY;
        return array_values( array_unique( $vars ) );
    }

    /**
     * Backfill frozen public slugs for all currently public-ready Hotels once per
     * slug-lifecycle version. Existing frozen slugs are never rewritten.
     *
     * H10E note: Data Bridge can make an Entity public-ready only after the
     * save_post lifecycle has already finished (metadata + media are applied
     * afterwards). Earlier builds therefore left some imported Hotels without
     * _sthi_public_slug even though the Grid correctly showed Publish Ready.
     */
    public static function maybe_freeze_public_slugs() {
        if ( self::SLUG_FREEZE_VER === (string) get_option( 'sthi_h9b_slug_freeze_version', '' ) ) { return; }

        $ids = get_posts( array(
            'post_type'      => 'sthi_hotel',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'fields'         => 'ids',
            'orderby'        => 'ID',
            'order'          => 'ASC',
            'no_found_rows'  => true,
        ) );
        foreach ( $ids as $post_id ) { self::freeze_public_slug_for_hotel( $post_id ); }
        update_option( 'sthi_h9b_slug_freeze_version', self::SLUG_FREEZE_VER, false );
    }

    /**
     * Ensure one Hotel has a frozen public slug after an out-of-band data update
     * (Data Bridge / Hotel Grid). This is intentionally idempotent: an existing
     * public slug is preserved forever, and conflicts still fail closed.
     */
    public static function ensure_public_slug( $post_id ) {
        return self::freeze_public_slug_for_hotel( $post_id );
    }

    public static function maybe_freeze_saved_hotel_slug( $post_id, $post, $update ) {
        if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) { return; }
        if ( ! ( $post instanceof WP_Post ) || 'sthi_hotel' !== $post->post_type ) { return; }
        self::freeze_public_slug_for_hotel( $post_id );
    }

    private static function freeze_public_slug_for_hotel( $post_id ) {
        $post_id = absint( $post_id );
        if ( ! $post_id || 'publish' !== get_post_status( $post_id ) ) { return ''; }
        if ( ! class_exists( 'STHI_Frontend' ) || ! STHI_Frontend::is_public_ready( $post_id ) ) { return ''; }

        $existing = sanitize_title( (string) get_post_meta( $post_id, self::PUBLIC_SLUG_META, true ) );
        if ( $existing ) { return $existing; }

        $candidate = self::candidate_public_slug( $post_id );
        if ( ! $candidate ) { return ''; }

        $conflicts = get_posts( array(
            'post_type'      => 'sthi_hotel',
            'post_status'    => 'any',
            'posts_per_page' => 2,
            'fields'         => 'ids',
            'post__not_in'   => array( $post_id ),
            'meta_key'       => self::PUBLIC_SLUG_META,
            'meta_value'     => $candidate,
            'no_found_rows'  => true,
        ) );
        if ( $conflicts ) {
            update_post_meta( $post_id, self::SLUG_CONFLICT_META, $candidate );
            return '';
        }

        update_post_meta( $post_id, self::PUBLIC_SLUG_META, $candidate );
        delete_post_meta( $post_id, self::SLUG_CONFLICT_META );
        return $candidate;
    }

    public static function public_slug( $post_id ) {
        $post_id = absint( $post_id );
        if ( ! $post_id || 'sthi_hotel' !== get_post_type( $post_id ) ) { return ''; }
        return sanitize_title( (string) get_post_meta( $post_id, self::PUBLIC_SLUG_META, true ) );
    }

    private static function candidate_public_slug( $post_id ) {
        $name = trim( (string) get_post_meta( $post_id, '_sthi_official_name', true ) );
        if ( ! $name ) { $name = trim( (string) get_the_title( $post_id ) ); }
        if ( $name ) {
            $slug = sanitize_title( $name );
            if ( $slug ) { return $slug; }
        }
        return sanitize_title( (string) get_post_field( 'post_name', $post_id ) );
    }

    public static function serve() {
        $hub_id = sanitize_key( (string) get_query_var( self::HUB_QUERY ) );
        if ( $hub_id ) { self::serve_hub( $hub_id ); }

        $slug = sanitize_title( (string) get_query_var( self::HOTEL_QUERY ) );
        if ( $slug ) { self::serve_hotel( $slug ); }
    }

    private static function serve_hub( $hub_id ) {
        $registry = class_exists( 'STHI_Hubs' ) ? STHI_Hubs::registry() : array();
        if ( ! isset( $registry[ $hub_id ] ) ) { self::route_404(); return; }

        self::$hub_id = $hub_id;
        self::mark_public_response( 'hub-' . $hub_id, self::hub_lastmod( $hub_id ) );
        get_header();
        STHI_Hubs::render_hub( $hub_id, false );
        get_footer();
        exit;
    }

    private static function serve_hotel( $slug ) {
        $hotel = self::resolve_hotel_by_public_slug( $slug );
        if ( ! ( $hotel instanceof WP_Post ) || ! self::hotel_is_routable_id( $hotel->ID ) ) { self::route_404(); }

        self::$hotel_post_id = (int) $hotel->ID;
        self::mark_public_response( 'hotel', self::hotel_lastmod( $hotel->ID ) );
        header( 'X-STHI-Hotel-Resolver: h9e-frozen-public-slug' );

        global $post;
        $post = $hotel;
        setup_postdata( $post );
        get_header();
        STHI_Frontend::render_page( $hotel->ID );
        get_footer();
        wp_reset_postdata();
        exit;
    }

    private static function resolve_hotel_by_public_slug( $slug ) {
        $slug = sanitize_title( (string) $slug );
        if ( ! $slug ) { return null; }
        $ids = get_posts( array(
            'post_type'      => 'sthi_hotel',
            'post_status'    => 'publish',
            'posts_per_page' => 2,
            'fields'         => 'ids',
            'meta_key'       => self::PUBLIC_SLUG_META,
            'meta_value'     => $slug,
            'no_found_rows'  => true,
        ) );
        if ( 1 !== count( $ids ) ) { return null; }
        $post = get_post( (int) $ids[0] );
        return $post instanceof WP_Post ? $post : null;
    }

    private static function mark_public_response( $surface, $lastmod = '' ) {
        status_header( 200 );
        header( 'X-STHI-H9E: ' . ( self::publication_enabled() ? 'publication-enabled' : 'publication-locked' ) );
        header( 'X-STHI-Surface: ' . sanitize_key( $surface ) );
        if ( $lastmod ) {
            $ts = strtotime( $lastmod );
            if ( $ts ) { header( 'Last-Modified: ' . gmdate( 'D, d M Y H:i:s', $ts ) . ' GMT' ); }
        }
    }

    private static function route_404() {
        global $wp_query;
        if ( $wp_query ) { $wp_query->set_404(); }
        status_header( 404 );
        nocache_headers();
        $template = get_404_template();
        if ( $template ) { include $template; }
        exit;
    }

    public static function is_public_hub_context() { return (bool) self::$hub_id; }

    public static function is_public_hotel_context( $is_public = false, $post_id = 0 ) {
        if ( self::$hotel_post_id && ( ! $post_id || (int) $post_id === self::$hotel_post_id ) ) { return true; }
        return (bool) $is_public;
    }

    public static function hotel_context_post_id( $post_id ) { return self::$hotel_post_id ?: $post_id; }

    public static function hotel_context_url( $url, $post_id ) {
        if ( self::$hotel_post_id && (int) $post_id === self::$hotel_post_id ) { return self::hotel_public_url( $post_id ); }
        return $url;
    }

    public static function editor_ribbon_text( $text, $post_id ) {
        if ( self::$hotel_post_id && (int) $post_id === self::$hotel_post_id ) {
            if ( ! self::publication_enabled() ) { return 'H9E Public QA • Indexation Locked'; }
            return self::hotel_is_indexable( $post_id ) ? 'H9E Public • Index • Self Canonical' : 'H9E Public • Noindex';
        }
        return $text;
    }

    public static function publication_enabled() {
        if ( '0' === (string) get_option( 'blog_public', '1' ) ) { return false; }
        return 'enabled' === (string) get_option( self::PUBLICATION_OPTION, 'locked' );
    }

    public static function publication_state() {
        return self::publication_enabled() ? 'enabled' : 'locked';
    }

    public static function hotel_is_routable( $routable, $post_id ) {
        if ( $routable ) { return true; }
        return self::hotel_is_routable_id( $post_id );
    }

    public static function hotel_is_indexable_filter( $indexable, $post_id ) {
        if ( $indexable ) { return true; }
        return self::hotel_is_indexable( $post_id );
    }

    /**
     * Public route eligibility is intentionally independent from the launch lock.
     * This keeps final URLs available for owner QA while robots/canonical/sitemap
     * stay locked until the pilot inventory is approved.
     */
    public static function hotel_is_routable_id( $post_id ) {
        $post_id = absint( $post_id );
        if ( ! $post_id || 'sthi_hotel' !== get_post_type( $post_id ) || 'publish' !== get_post_status( $post_id ) ) { return false; }
        if ( ! class_exists( 'STHI_Frontend' ) || ! STHI_Frontend::is_public_ready( $post_id ) ) { return false; }
        if ( get_post_meta( $post_id, self::SLUG_CONFLICT_META, true ) ) { return false; }
        $slug = self::public_slug( $post_id );
        if ( ! $slug ) { return false; }

        $ids = get_posts( array(
            'post_type'      => 'sthi_hotel',
            'post_status'    => 'publish',
            'posts_per_page' => 2,
            'fields'         => 'ids',
            'meta_key'       => self::PUBLIC_SLUG_META,
            'meta_value'     => $slug,
            'no_found_rows'  => true,
        ) );
        return 1 === count( $ids ) && (int) $ids[0] === $post_id;
    }

    public static function hotel_is_indexable( $post_id ) {
        return self::publication_enabled() && self::hotel_is_routable_id( $post_id );
    }

    public static function hotel_public_url_filter( $url, $post_id ) {
        if ( $url ) { return $url; }
        return self::hotel_public_url( $post_id );
    }

    public static function hotel_canonical_url_filter( $url, $post_id ) {
        if ( $url ) { return $url; }
        if ( ! self::$hotel_post_id || (int) $post_id !== self::$hotel_post_id || ! self::hotel_is_indexable( $post_id ) ) { return ''; }
        return self::hotel_public_url( $post_id );
    }

    public static function hotel_public_url( $post_id ) {
        $post_id = absint( $post_id );
        if ( ! self::hotel_is_routable_id( $post_id ) ) { return ''; }
        $slug = self::public_slug( $post_id );
        return $slug ? home_url( '/otel/' . $slug . '/' ) : '';
    }

    public static function hub_public_url( $hub_id ) {
        $registry = class_exists( 'STHI_Hubs' ) ? STHI_Hubs::registry() : array();
        return isset( $registry[ $hub_id ]['future_path'] ) ? home_url( $registry[ $hub_id ]['future_path'] ) : '';
    }

    public static function hub_is_indexable( $hub_id ) {
        if ( ! self::publication_enabled() || ! class_exists( 'STHI_Hubs' ) ) { return false; }
        $registry = STHI_Hubs::registry();
        if ( ! isset( $registry[ $hub_id ] ) ) { return false; }
        return count( STHI_Hubs::get_hotels( $hub_id, 'public' ) ) > 0;
    }

    public static function hotel_lastmod( $post_id ) {
        $value = get_post_modified_time( DATE_W3C, true, absint( $post_id ) );
        return $value ? $value : '';
    }

    public static function hub_lastmod( $hub_id ) {
        if ( ! class_exists( 'STHI_Hubs' ) ) { return ''; }
        $latest = 0;
        foreach ( STHI_Hubs::get_hotels( $hub_id, 'public' ) as $hotel ) {
            $value = self::hotel_lastmod( (int) $hotel['post_id'] );
            $ts = $value ? strtotime( $value ) : 0;
            if ( $ts > $latest ) { $latest = $ts; }
        }
        return $latest ? gmdate( DATE_W3C, $latest ) : '';
    }

    public static function indexable_hub_ids() {
        $out = array();
        if ( ! class_exists( 'STHI_Hubs' ) ) { return $out; }
        foreach ( array_keys( STHI_Hubs::registry() ) as $hub_id ) {
            if ( self::hub_is_indexable( $hub_id ) ) { $out[] = $hub_id; }
        }
        return $out;
    }

    public static function routable_hotel_ids() {
        $ids = get_posts( array(
            'post_type'      => 'sthi_hotel',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'fields'         => 'ids',
            'orderby'        => 'ID',
            'order'          => 'ASC',
            'no_found_rows'  => true,
        ) );
        return array_values( array_filter( array_map( 'absint', $ids ), array( __CLASS__, 'hotel_is_routable_id' ) ) );
    }

    public static function indexable_hotel_ids() {
        $ids = get_posts( array(
            'post_type'      => 'sthi_hotel',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'fields'         => 'ids',
            'orderby'        => 'ID',
            'order'          => 'ASC',
            'no_found_rows'  => true,
        ) );
        return array_values( array_filter( array_map( 'absint', $ids ), array( __CLASS__, 'hotel_is_indexable' ) ) );
    }

    public static function link_hub_registry( $registry ) {
        if ( isset( $registry['mekke-hotels'] ) ) {
            $registry['mekke-hotels']['path'] = '/mekke-otelleri/';
            $registry['mekke-hotels']['allow_route_fallback'] = self::hub_is_indexable( 'mekke' );
        }
        if ( isset( $registry['medine-hotels'] ) ) {
            $registry['medine-hotels']['path'] = '/medine-otelleri/';
            $registry['medine-hotels']['allow_route_fallback'] = self::hub_is_indexable( 'medine' );
        }
        return $registry;
    }

    public static function registered_public_route_allowed( $allowed, $url, $target_type, $target_id, $current_post_id ) {
        if ( 'hub' !== $target_type ) { return $allowed; }
        if ( 'mekke-hotels' === $target_id ) { return self::hub_is_indexable( 'mekke' ); }
        if ( 'medine-hotels' === $target_id ) { return self::hub_is_indexable( 'medine' ); }
        return $allowed;
    }

    public static function enqueue_hub_assets() {
        if ( self::is_public_hub_context() ) { wp_enqueue_style( 'sthi-hubs', STHI_URL . 'assets/hubs.css', array(), STHI_VERSION ); }
    }

    public static function hub_body_class( $classes ) {
        if ( ! self::is_public_hub_context() ) { return $classes; }
        $classes = array_values( array_diff( $classes, array( 'st-shell-front' ) ) );
        if ( ! in_array( 'st-shell-internal', $classes, true ) ) { $classes[] = 'st-shell-internal'; }
        $classes[] = 'sthi-hub-page';
        $classes[] = self::hub_is_indexable( self::$hub_id ) ? 'sthi-hub-public-indexable' : 'sthi-hub-public-noindex';
        $classes[] = 'sthi-h9e-route';
        $classes[] = self::publication_enabled() ? 'sthi-publication-enabled' : 'sthi-publication-locked';
        return array_values( array_unique( $classes ) );
    }

    public static function hub_document_title_parts( $parts ) {
        if ( ! self::is_public_hub_context() ) { return $parts; }
        $hub = STHI_Hubs::registry()[ self::$hub_id ];
        $parts['title'] = $hub['title'] . ' | Server Turizm';
        unset( $parts['site'], $parts['tagline'], $parts['page'] );
        return $parts;
    }

    public static function hub_pre_get_document_title( $title ) {
        if ( ! self::is_public_hub_context() ) { return $title; }
        $hub = STHI_Hubs::registry()[ self::$hub_id ];
        return $hub['title'] . ' | Server Turizm';
    }

    public static function hub_robots( $robots ) {
        if ( ! self::is_public_hub_context() ) { return $robots; }
        if ( self::hub_is_indexable( self::$hub_id ) ) {
            unset( $robots['noindex'], $robots['nofollow'] );
            $robots['index'] = true;
            $robots['follow'] = true;
        } else {
            unset( $robots['index'] );
            $robots['noindex'] = true;
            $robots['follow'] = true;
        }
        $robots['max-image-preview'] = 'large';
        return $robots;
    }

    public static function hub_start_head_buffer() {
        if ( ! self::is_public_hub_context() ) { return; }
        if ( empty( $GLOBALS['sthi_h9b_hub_head_buffer'] ) ) {
            $GLOBALS['sthi_h9b_hub_head_buffer'] = true;
            ob_start();
        }
    }

    public static function hub_finish_head_buffer() {
        if ( empty( $GLOBALS['sthi_h9b_hub_head_buffer'] ) ) { return; }
        $html = ob_get_clean();
        unset( $GLOBALS['sthi_h9b_hub_head_buffer'] );
        if ( ! self::is_public_hub_context() ) { echo $html; return; }

        $hub = STHI_Hubs::registry()[ self::$hub_id ];
        $title = $hub['title'] . ' | Server Turizm';
        $desc = $hub['description'];
        $url = self::hub_public_url( self::$hub_id );
        $indexable = self::hub_is_indexable( self::$hub_id );

        $patterns = array(
            '~<title\b[^>]*>.*?</title>\s*~is',
            '~<meta\b(?=[^>]*\bname\s*=\s*(["\'])description\1)[^>]*>\s*~is',
            '~<meta\b(?=[^>]*\bname\s*=\s*(["\'])robots\1)[^>]*>\s*~is',
            '~<link\b(?=[^>]*\brel\s*=\s*(["\'])[^"\']*\bcanonical\b[^"\']*\1)[^>]*>\s*~is',
            '~<meta\b(?=[^>]*\b(?:property|name)\s*=\s*(["\'])og:[^"\']+\1)[^>]*>\s*~is',
            '~<meta\b(?=[^>]*\b(?:property|name)\s*=\s*(["\'])twitter:[^"\']+\1)[^>]*>\s*~is',
        );
        $clean = preg_replace( $patterns, '', (string) $html );

        $robots = $indexable ? 'index, follow, max-image-preview:large' : 'noindex, follow, max-image-preview:large';
        $head  = "\n<title>" . esc_html( $title ) . "</title>\n";
        $head .= '<meta name="description" content="' . esc_attr( $desc ) . '">' . "\n";
        $head .= '<meta name="robots" content="' . esc_attr( $robots ) . '">' . "\n";
        if ( $indexable ) { $head .= '<link rel="canonical" href="' . esc_url( $url ) . '">' . "\n"; }
        $head .= '<meta property="og:title" content="' . esc_attr( $title ) . '">' . "\n";
        $head .= '<meta property="og:description" content="' . esc_attr( $desc ) . '">' . "\n";
        $head .= '<meta property="og:type" content="website">' . "\n";
        $head .= '<meta property="og:url" content="' . esc_url( $url ) . '">' . "\n";
        $head .= '<meta property="og:site_name" content="Server Turizm">' . "\n";
        $head .= '<meta name="twitter:card" content="summary">' . "\n";
        $head .= '<meta name="twitter:title" content="' . esc_attr( $title ) . '">' . "\n";
        $head .= '<meta name="twitter:description" content="' . esc_attr( $desc ) . '">' . "\n";
        echo $head . $clean;
    }
}
STHI_Public_Routes::init();
