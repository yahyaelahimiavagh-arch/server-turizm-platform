<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

final class STHHD_Plugin {
    const OPTION_SELECTED_IDS = 'sthhd_selected_ids';
    const OPTION_ORDER        = 'sthhd_selected_order';
    const OPTION_PUBLIC       = 'sthhd_public_enabled';
    const PREVIEW_ARG         = 'sthhd_preview';
    const PREVIEW_NONCE_ARG   = 'sthhd_nonce';

    public static function activate() {
        add_option( self::OPTION_SELECTED_IDS, array(), '', false );
        add_option( self::OPTION_ORDER, array(), '', false );
        add_option( self::OPTION_PUBLIC, false, '', false );
    }

    public static function init() {
        STHHD_Admin::init();
        add_filter( 'the_content', array( __CLASS__, 'inject_home_preview' ), 80 );
        add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_frontend_assets' ) );
        add_filter( 'wp_robots', array( __CLASS__, 'preview_robots' ) );
        add_action( 'template_redirect', array( __CLASS__, 'preview_nocache' ), 1 );
    }

    public static function dependency_ready() {
        return post_type_exists( 'sthi_hotel' )
            && class_exists( 'STHI_Frontend' )
            && class_exists( 'STHI_Media' );
    }

    /**
     * Controlled public bridge: H12F never owns an independent public switch.
     * It may render publicly only when Homepage Intelligence is the active
     * Homepage owner and its public cutover is enabled. This keeps Hotel data
     * ownership in Hotel Intelligence while making preview/public composition
     * use the same proven priority-80 path.
     */
    public static function owner_public_request() {
        if ( ! is_front_page() ) { return false; }
        if ( ! class_exists( 'STHI_Home_Plugin' ) || ! is_callable( array( 'STHI_Home_Plugin', 'public_enabled' ) ) ) { return false; }
        return (bool) STHI_Home_Plugin::public_enabled();
    }

    public static function should_render_request() {
        return self::is_preview_request() || self::owner_public_request();
    }

    public static function is_preview_request() {
        if ( ! is_front_page() ) { return false; }
        if ( ! isset( $_GET[ self::PREVIEW_ARG ] ) || '1' !== (string) wp_unslash( $_GET[ self::PREVIEW_ARG ] ) ) { return false; }
        if ( ! is_user_logged_in() || ! current_user_can( 'manage_options' ) ) { return false; }
        $nonce = isset( $_GET[ self::PREVIEW_NONCE_ARG ] ) ? sanitize_text_field( wp_unslash( $_GET[ self::PREVIEW_NONCE_ARG ] ) ) : '';
        return $nonce && wp_verify_nonce( $nonce, 'sthhd_home_preview' );
    }

    public static function preview_url() {
        $url = add_query_arg( self::PREVIEW_ARG, '1', home_url( '/' ) );
        return wp_nonce_url( $url, 'sthhd_home_preview', self::PREVIEW_NONCE_ARG );
    }

    public static function preview_nocache() {
        if ( ! self::is_preview_request() ) { return; }
        if ( ! defined( 'DONOTCACHEPAGE' ) ) { define( 'DONOTCACHEPAGE', true ); }
        nocache_headers();
        header( 'X-STHHD-Mode: admin-preview-only' );
        header( 'X-STHHD-Placement: before-culture-tours' );
        header( 'X-Robots-Tag: noindex, nofollow', true );
    }

    public static function preview_robots( $robots ) {
        if ( ! self::is_preview_request() ) { return $robots; }
        unset( $robots['index'], $robots['follow'] );
        $robots['noindex'] = true;
        $robots['nofollow'] = true;
        return $robots;
    }

    public static function enqueue_frontend_assets() {
        if ( ! self::should_render_request() ) { return; }
        wp_enqueue_style( 'sthhd-home-hotels', STHHD_URL . 'assets/home-hotels.css', array(), STHHD_VERSION );
        wp_enqueue_script( 'sthhd-home-hotels', STHHD_URL . 'assets/home-hotels.js', array(), STHHD_VERSION, true );
    }

    /**
     * Visual-integration contract for v0.1.2:
     * place the preview between the Hajj announcement and Culture Tours.
     * We deliberately do not mutate/save the homepage Raw HTML.
     * If the anchor disappears, fail visible instead of silently appending the
     * module somewhere commercially wrong on the page.
     */
    private static function insert_before_culture_tours( $content, $module ) {
        $pattern = '~(<section\b[^>]*\bclass\s*=\s*(["\'])[^"\']*\bculture-tours-section\b[^"\']*\2[^>]*>)~i';
        $count   = 0;
        $result  = preg_replace( $pattern, $module . "\n$1", $content, 1, $count );

        if ( 1 === $count && is_string( $result ) ) {
            return $result;
        }

        return $content . '<div class="sthhd-preview-error">H12F placement anchor <code>culture-tours-section</code> bulunamadı. Modül yanlış yere eklenmedi; Public output hâlâ OFF.</div>';
    }

    public static function inject_home_preview( $content ) {
        static $rendered = false;
        if ( $rendered || ! self::should_render_request() || ! is_main_query() || ! in_the_loop() ) { return $content; }
        $rendered = true;
        $preview  = self::is_preview_request();

        if ( ! self::dependency_ready() ) {
            return $preview
                ? $content . '<div class="sthhd-preview-error">Hotel Intelligence dependency is not ready. Public output remains OFF.</div>'
                : $content;
        }

        $hotels = STHHD_Repository::selected_hotels();
        $module = STHHD_Renderer::render( $hotels, $preview );
        if ( ! $module ) {
            return $preview
                ? $content . '<div class="sthhd-preview-error">No launch-ready Hotel is selected. Public output remains OFF.</div>'
                : $content;
        }

        return self::insert_before_culture_tours( $content, $module );
    }
}
