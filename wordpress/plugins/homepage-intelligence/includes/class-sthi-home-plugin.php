<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

final class STHI_Home_Plugin {
    const OPTION_PAGE_SETTINGS     = 'sthi_home_page_settings';
    const OPTION_HERO_LINKS        = 'sthi_home_hero_links';
    const OPTION_CAMPAIGN_SETTINGS = 'sthi_home_campaign_settings';
    const OPTION_CAMPAIGN_ITEMS    = 'sthi_home_campaign_items';
    const OPTION_TOUR_SETTINGS     = 'sthi_home_tour_settings';
    const OPTION_TOUR_ITEMS        = 'sthi_home_tour_items';
    const OPTION_PUBLIC            = 'sthi_home_public_enabled';
    const OPTION_REVISION          = 'sthi_home_revision';
    const OPTION_DATA_VERSION      = 'sthi_home_data_version';
    const OPTION_STORY_SETTINGS    = 'sthi_home_story_settings';
    const OPTION_STORY_PUBLIC      = 'sthi_home_story_public_enabled';

    const PREVIEW_ARG       = 'sthi_home_preview';
    const PREVIEW_NONCE_ARG = 'sthi_home_nonce';

    public static function activate() {
        add_option( self::OPTION_PAGE_SETTINGS, STHI_Home_Repository::default_page_settings(), '', false );
        add_option( self::OPTION_HERO_LINKS, STHI_Home_Repository::default_hero_links(), '', false );
        add_option( self::OPTION_CAMPAIGN_SETTINGS, STHI_Home_Repository::default_campaign_settings(), '', false );
        add_option( self::OPTION_CAMPAIGN_ITEMS, STHI_Home_Repository::default_campaign_items(), '', false );
        add_option( self::OPTION_TOUR_SETTINGS, STHI_Home_Repository::default_tour_settings(), '', false );
        add_option( self::OPTION_TOUR_ITEMS, STHI_Home_Repository::default_tour_items(), '', false );
        add_option( self::OPTION_PUBLIC, false, '', false );
        add_option( self::OPTION_REVISION, 1, '', false );
        add_option( self::OPTION_DATA_VERSION, STHI_HOME_VERSION, '', false );
        add_option( self::OPTION_STORY_SETTINGS, STHI_Home_Repository::default_story_settings(), '', false );
        add_option( self::OPTION_STORY_PUBLIC, false, '', false );
    }

    public static function init() {
        self::maybe_migrate();
        STHI_Home_Admin::init();

        // Run before H12F (which injects before culture-tours at priority 80).
        // This keeps the Hotel Discovery placement contract intact after our
        // Culture Tours section replacement.
        add_filter( 'the_content', array( __CLASS__, 'inject_preview' ), 70 );
        // H13C v0.3.2: H12F fail-safe composition runs after the legacy
        // H12F priority-80 preview filter. If H12F already injected itself we
        // do nothing; otherwise we compose its accepted renderer/data source
        // directly so the no-Raw Homepage cannot silently lose Hotels.
        add_filter( 'the_content', array( __CLASS__, 'ensure_h12f_composition' ), 90 );
        add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_frontend_assets' ) );
        add_filter( 'wp_robots', array( __CLASS__, 'preview_robots' ) );
        add_filter( 'pre_get_document_title', array( __CLASS__, 'preview_document_title' ), 20 );
        add_action( 'wp_head', array( __CLASS__, 'preview_seo_head' ), 3 );
        add_action( 'template_redirect', array( __CLASS__, 'preview_nocache' ), 1 );
    }


    private static function maybe_migrate() {
        $stored = (string) get_option( self::OPTION_DATA_VERSION, '0.1.0' );
        if ( version_compare( $stored, '0.3.3', '>=' ) ) { return; }

        // Pre-v0.1.7 seeded campaign records stored the image file itself in
        // link_url even while the card was in Lightbox mode. v0.1.7 exposes
        // optional crawlable CTA links, so exact legacy self-media links must
        // be cleared before they can accidentally become public link targets.
        $items = get_option( self::OPTION_CAMPAIGN_ITEMS, array() );
        $changed = false;
        if ( is_array( $items ) ) {
            foreach ( $items as &$item ) {
                if ( ! is_array( $item ) ) { continue; }
                $mode = isset( $item['action_mode'] ) ? (string) $item['action_mode'] : 'lightbox';
                $link = trim( (string) ( $item['link_url'] ?? '' ) );
                $img  = trim( (string) ( $item['image_url'] ?? '' ) );
                if ( 'lightbox' === $mode && $link && $img && untrailingslashit( $link ) === untrailingslashit( $img ) ) {
                    $item['link_url']   = '';
                    $item['link_label'] = '';
                    $changed = true;
                }
            }
            unset( $item );
        }
        if ( $changed ) {
            update_option( self::OPTION_CAMPAIGN_ITEMS, $items, false );
        }
        add_option( self::OPTION_PAGE_SETTINGS, STHI_Home_Repository::default_page_settings(), '', false );
        add_option( self::OPTION_HERO_LINKS, STHI_Home_Repository::default_hero_links(), '', false );
        add_option( self::OPTION_STORY_SETTINGS, STHI_Home_Repository::default_story_settings(), '', false );
        add_option( self::OPTION_STORY_PUBLIC, false, '', false );
        update_option( self::OPTION_DATA_VERSION, '0.3.3', false );
    }

    public static function public_enabled() {
        return (bool) get_option( self::OPTION_PUBLIC, false );
    }

    public static function story_public_enabled() {
        return (bool) get_option( self::OPTION_STORY_PUBLIC, false );
    }

    public static function story_ready() {
        $settings = STHI_Home_Repository::story_settings();
        if ( empty( $settings['enabled'] ) ) { return false; }
        $post_id = absint( $settings['post_id'] ?? 0 );
        if ( ! $post_id ) { return false; }
        $post = get_post( $post_id );
        return $post && 'post' === $post->post_type && 'publish' === $post->post_status && (bool) get_post_thumbnail_id( $post_id );
    }


    public static function should_render_owned_homepage() {
        return is_front_page() && ( self::is_preview_request() || self::public_enabled() );
    }

    public static function baseline_path() {
        return STHI_HOME_DIR . 'templates/homepage-baseline.html';
    }

    public static function baseline_html() {
        $path = self::baseline_path();
        if ( ! is_readable( $path ) ) { return ''; }
        $html = file_get_contents( $path );
        return is_string( $html ) ? $html : '';
    }

    public static function baseline_sha256() {
        $path = self::baseline_path();
        return is_readable( $path ) ? hash_file( 'sha256', $path ) : '';
    }

    public static function is_preview_request() {
        if ( ! is_front_page() ) { return false; }
        if ( ! isset( $_GET[ self::PREVIEW_ARG ] ) || '1' !== (string) wp_unslash( $_GET[ self::PREVIEW_ARG ] ) ) { return false; }
        if ( ! is_user_logged_in() || ! current_user_can( 'manage_options' ) ) { return false; }
        $nonce = isset( $_GET[ self::PREVIEW_NONCE_ARG ] ) ? sanitize_text_field( wp_unslash( $_GET[ self::PREVIEW_NONCE_ARG ] ) ) : '';
        return $nonce && wp_verify_nonce( $nonce, 'sthi_home_preview' );
    }

    public static function preview_url() {
        $args = array(
            self::PREVIEW_ARG       => '1',
            self::PREVIEW_NONCE_ARG => wp_create_nonce( 'sthi_home_preview' ),
        );

        // Compose with H12F preview if it is installed, so the owner sees the
        // complete intended Homepage rather than isolated fragments.
        if ( class_exists( 'STHHD_Plugin' ) ) {
            $args['sthhd_preview'] = '1';
            $args['sthhd_nonce']   = wp_create_nonce( 'sthhd_home_preview' );
        }

        return add_query_arg( $args, home_url( '/' ) );
    }

    public static function preview_nocache() {
        if ( ! self::is_preview_request() ) { return; }
        if ( ! defined( 'DONOTCACHEPAGE' ) ) { define( 'DONOTCACHEPAGE', true ); }
        nocache_headers();
        header( 'X-STHI-Home-Mode: admin-preview-only' );
        header( 'X-STHI-Home-SSR: true' );
        header( 'X-Robots-Tag: noindex, nofollow', true );
    }

    public static function preview_robots( $robots ) {
        if ( ! self::is_preview_request() ) { return $robots; }
        unset( $robots['index'], $robots['follow'] );
        $robots['noindex']  = true;
        $robots['nofollow'] = true;
        return $robots;
    }

    public static function preview_document_title( $title ) {
        if ( ! self::should_render_owned_homepage() ) { return $title; }
        $settings = STHI_Home_Repository::page_settings();
        $seo_title = trim( (string) ( $settings['seo_title'] ?? '' ) );
        return $seo_title ? $seo_title : $title;
    }

    public static function preview_seo_head() {
        if ( ! self::should_render_owned_homepage() ) { return; }
        $settings = STHI_Home_Repository::page_settings();
        $desc = trim( (string) ( $settings['meta_description'] ?? '' ) );
        $og_title = trim( (string) ( $settings['social_title'] ?? '' ) );
        $og_desc = trim( (string) ( $settings['social_description'] ?? '' ) );
        $img = '';
        $aid = absint( $settings['social_image_id'] ?? 0 );
        if ( $aid && wp_attachment_is_image( $aid ) ) { $img = (string) wp_get_attachment_image_url( $aid, 'full' ); }
        if ( ! $img ) { $img = trim( (string) ( $settings['social_image_url'] ?? '' ) ); }
        if ( $desc ) { echo "\n<meta name=\"description\" content=\"" . esc_attr( $desc ) . "\" data-sthi-home-seo-preview=\"1\" />"; }
        if ( $og_title ) { echo "\n<meta property=\"og:title\" content=\"" . esc_attr( $og_title ) . "\" data-sthi-home-seo-preview=\"1\" />"; }
        if ( $og_desc ) { echo "\n<meta property=\"og:description\" content=\"" . esc_attr( $og_desc ) . "\" data-sthi-home-seo-preview=\"1\" />"; }
        if ( $img ) { echo "\n<meta property=\"og:image\" content=\"" . esc_url( $img ) . "\" data-sthi-home-seo-preview=\"1\" />"; }
        echo "\n<meta property=\"og:url\" content=\"" . esc_url( home_url( '/' ) ) . "\" data-sthi-home-seo-preview=\"1\" />";
        echo "\n<meta name=\"twitter:card\" content=\"summary_large_image\" data-sthi-home-seo-preview=\"1\" />\n";
    }

    public static function enqueue_frontend_assets() {
        if ( ! self::should_render_owned_homepage() ) { return; }
        wp_enqueue_style( 'sthi-home-preview', STHI_HOME_URL . 'assets/homepage.css', array(), STHI_HOME_VERSION );
        wp_enqueue_script( 'sthi-home-preview', STHI_HOME_URL . 'assets/homepage.js', array(), STHI_HOME_VERSION, true );

        // The Homepage owner composes H12F in both no-Raw preview and public
        // cutover. H12F keeps Hotel entity/data ownership; we only guarantee
        // its accepted presentation assets are present when composed.
        if ( self::h12f_bridge_ready() && defined( 'STHHD_URL' ) ) {
            $ver = defined( 'STHHD_VERSION' ) ? STHHD_VERSION : STHI_HOME_VERSION;
            wp_enqueue_style( 'sthhd-home-hotels', STHHD_URL . 'assets/home-hotels.css', array(), $ver );
            wp_enqueue_script( 'sthhd-home-hotels', STHHD_URL . 'assets/home-hotels.js', array(), $ver, true );
        }
    }

    public static function h12f_selected_count() {
        if ( ! class_exists( 'STHHD_Repository' ) || ! is_callable( array( 'STHHD_Repository', 'selected_hotels' ) ) ) { return 0; }
        $hotels = STHHD_Repository::selected_hotels();
        return is_array( $hotels ) ? count( $hotels ) : 0;
    }

    public static function h12f_bridge_ready() {
        return class_exists( 'STHHD_Renderer' )
            && class_exists( 'STHHD_Repository' )
            && is_callable( array( 'STHHD_Renderer', 'render' ) )
            && self::h12f_selected_count() > 0;
    }

    private static function insert_h12f_before_culture( $content, $module ) {
        if ( ! $module ) { return $content; }
        if ( false !== strpos( $content, 'data-sthhd-module' ) ) { return $content; }
        $pattern = '~(<section\b[^>]*\bclass\s*=\s*(["\'])[^"\']*\bculture-tours-section\b[^"\']*\2[^>]*>)~i';
        $count = 0;
        $result = preg_replace( $pattern, $module . "\n$1", $content, 1, $count );
        if ( 1 === $count && is_string( $result ) ) { return $result; }
        if ( self::is_preview_request() ) {
            return $content . '<div class="sthi-home-preview-error">H12F Hotel Discovery composition anchor bulunamadı. PUBLIC CUTOVER yapma.</div>';
        }
        return $content;
    }

    public static function ensure_h12f_composition( $content ) {
        if ( ! self::should_render_owned_homepage() || ! is_main_query() || ! in_the_loop() ) { return $content; }
        if ( false !== strpos( $content, 'data-sthhd-module' ) ) { return $content; }
        if ( ! self::h12f_bridge_ready() ) {
            if ( self::is_preview_request() ) {
                return $content . '<div class="sthi-home-preview-error">H12F Hotel Discovery hazır değil veya seçili Hotel yok. PUBLIC CUTOVER yapma.</div>';
            }
            return $content;
        }
        $hotels = STHHD_Repository::selected_hotels();
        $module = STHHD_Renderer::render( $hotels, self::is_preview_request() );
        return self::insert_h12f_before_culture( $content, $module );
    }

    private static function replace_campaign_gallery( $content, $html ) {
        // Contract: the gallery is the existing hero-media-slide identified by
        // data-hero-slide="gallery". Replace only its inner body and preserve
        // the original section tag/attributes and surrounding hero structure.
        $pattern = '~(<section\b[^>]*\bdata-hero-slide\s*=\s*(["\'])gallery\2[^>]*>).*?(</section>)~is';
        $count   = 0;
        $result  = preg_replace( $pattern, '$1' . "\n" . $html . "\n" . '$3', $content, 1, $count );

        if ( 1 === $count && is_string( $result ) ) { return $result; }
        return $content . '<div class="sthi-home-preview-error">Homepage Campaign anchor <code>data-hero-slide=&quot;gallery&quot;</code> bulunamadı. Raw HTML değiştirilmedi.</div>';
    }

    private static function insert_story_after_culture( $content, $html ) {
        if ( ! $html || false !== strpos( $content, 'data-sthi-home-story' ) ) { return $content; }
        $pattern = '~(<section\b[^>]*\bclass\s*=\s*(["\'])[^"\']*\bculture-tours-section\b[^"\']*\2[^>]*>.*?</section>)~is';
        $count = 0;
        $result = preg_replace( $pattern, '$1' . "\n" . $html, $content, 1, $count );
        if ( 1 === $count && is_string( $result ) ) { return $result; }
        if ( self::is_preview_request() ) {
            return $content . '<div class="sthi-home-preview-error">Journey Evidence placement anchor bulunamadı. PUBLIC STORY açma.</div>';
        }
        return $content;
    }

    private static function replace_culture_tours( $content, $html ) {
        $pattern = '~<section\b[^>]*\bclass\s*=\s*(["\'])[^"\']*\bculture-tours-section\b[^"\']*\1[^>]*>.*?</section>~is';
        $count   = 0;
        $result  = preg_replace( $pattern, $html, $content, 1, $count );

        if ( 1 === $count && is_string( $result ) ) { return $result; }
        return $content . '<div class="sthi-home-preview-error">Culture Tours anchor <code>culture-tours-section</code> bulunamadı. Raw HTML değiştirilmedi.</div>';
    }

    private static function replace_hero_buttons( $content, $html ) {
        $pattern = '~<div\b[^>]*class=(["\'])[^"\']*\bhero-btn-container\b[^"\']*\1[^>]*>.*?</div>~is';
        $count = 0;
        $result = preg_replace( $pattern, $html, $content, 1, $count );
        return ( 1 === $count && is_string( $result ) ) ? $result : $content;
    }

    private static function replace_hero_h1( $content, $html ) {
        $count = 0;
        $result = preg_replace( '~<h1\b[^>]*class=(["\'])[^"\']*\bhero-title\b[^"\']*\1[^>]*>.*?</h1>~is', $html, $content, 1, $count );
        return ( 1 === $count && is_string( $result ) ) ? $result : $content;
    }

    private static function replace_hero_intro( $content, $html ) {
        $pattern = '~(<h1\b[^>]*class=(["\'])[^"\']*\bhero-title\b[^"\']*\2[^>]*>.*?</h1>)\s*<p\b[^>]*>.*?</p>~is';
        $count = 0;
        $result = preg_replace( $pattern, '$1' . "\n" . $html, $content, 1, $count );
        return ( 1 === $count && is_string( $result ) ) ? $result : $content;
    }

    private static function replace_hero_background( $content, $url, $zoom = 100, $x = 50, $y = 50 ) {
        $url = esc_url_raw( $url );
        if ( ! $url ) { return $content; }

        $zoom = max( 100, min( 180, absint( $zoom ) ) );
        $x    = max( 0, min( 100, absint( $x ) ) );
        $y    = max( 0, min( 100, absint( $y ) ) );

        $pattern = '~<section\b[^>]*class=(["\'])[^"\']*\bluxury-hero\b[^"\']*\1[^>]*>~i';
        $count = 0;
        $result = preg_replace_callback( $pattern, function( $m ) use ( $url, $zoom, $x, $y ) {
            $tag = (string) $m[0];
            $tag = preg_replace( '~\sstyle=(["\']).*?\1~is', '', $tag );
            $size = ( 100 === $zoom ) ? 'cover' : $zoom . '% auto';
            $style = sprintf(
                "background-image:url('%s');background-size:%s;background-position:%d%% %d%%;background-repeat:no-repeat;",
                esc_url( $url ),
                $size,
                $x,
                $y
            );
            return preg_replace( '~>$~', ' style="' . esc_attr( $style ) . '">', $tag, 1 );
        }, $content, 1, $count );

        return ( 1 === $count && is_string( $result ) ) ? $result : $content;
    }

    private static function replace_search_button( $content, $label ) {
        $label = trim( (string) $label );
        if ( ! $label ) { return $content; }
        $html = '<button id="open-search-btn" class="btn-toggle-search" onclick="toggleSearchBox()"><i class="fas fa-search"></i> ' . esc_html( $label ) . '</button>';
        $count = 0;
        $result = preg_replace( '~<button\b[^>]*id=(["\'])open-search-btn\1[^>]*>.*?</button>~is', $html, $content, 1, $count );
        return ( 1 === $count && is_string( $result ) ) ? $result : $content;
    }

    private static function replace_umre_heading( $content, $title ) {
        $title = trim( (string) $title );
        if ( ! $title ) { return $content; }
        $pattern = '~(<section\b[^>]*class=(["\'])[^"\']*\bumre-programs-section\b[^"\']*\2[^>]*>.*?<h2\b[^>]*>).*?(</h2>)~is';
        $count = 0;
        $result = preg_replace( $pattern, '$1' . esc_html( $title ) . '$3', $content, 1, $count );
        return ( 1 === $count && is_string( $result ) ) ? $result : $content;
    }

    private static function replace_umre_cta( $content, $label, $url ) {
        $label = trim( (string) $label );
        $url = esc_url( $url );
        if ( ! $label || ! $url ) { return $content; }
        $html = '<a href="' . $url . '" class="btn-view-all">' . esc_html( $label ) . '<i class="fas fa-arrow-right"></i></a>';
        $count = 0;
        $result = preg_replace( '~<a\b[^>]*class=(["\'])[^"\']*\bbtn-view-all\b[^"\']*\1[^>]*>.*?</a>~is', $html, $content, 1, $count );
        return ( 1 === $count && is_string( $result ) ) ? $result : $content;
    }

    public static function inject_preview( $content ) {
        static $rendered = false;
        if ( $rendered || ! self::should_render_owned_homepage() || ! is_main_query() || ! in_the_loop() ) { return $content; }
        $rendered = true;

        // H13C: render from the plugin-owned frozen baseline, not from the
        // WPBakery Raw HTML page element. This makes ADMIN PREVIEW a real
        // no-Raw-HTML parity test and allows controlled public cutover.
        $baseline = self::baseline_html();
        if ( $baseline ) {
            $content = $baseline;
        } elseif ( self::is_preview_request() ) {
            return $content . '<div class="sthi-home-preview-error">Plugin-owned Homepage baseline okunamadı. Raw HTML fallback korundu.</div>';
        }

        $page = STHI_Home_Repository::page_settings();
        $hero_links = STHI_Home_Repository::hero_links();
        $bg = '';
        $bg_id = absint( $page['hero_background_id'] ?? 0 );
        if ( $bg_id && wp_attachment_is_image( $bg_id ) ) { $bg = (string) wp_get_attachment_image_url( $bg_id, 'full' ); }
        if ( ! $bg ) { $bg = (string) ( $page['hero_background_url'] ?? '' ); }
        $content = self::replace_hero_background( $content, $bg, $page['hero_background_zoom'] ?? 100, $page['hero_background_x'] ?? 50, $page['hero_background_y'] ?? 50 );
        $content = self::replace_hero_buttons( $content, STHI_Home_Renderer::hero_buttons( $hero_links ) );
        $content = self::replace_hero_h1( $content, STHI_Home_Renderer::hero_h1( $page['hero_h1'] ?? '' ) );
        $content = self::replace_hero_intro( $content, STHI_Home_Renderer::hero_intro( $page['hero_intro'] ?? '' ) );
        $content = self::replace_search_button( $content, $page['search_button_label'] ?? 'DETAYLI ARAMA' );
        $content = self::replace_umre_heading( $content, $page['umre_section_title'] ?? '' );
        $content = self::replace_umre_cta( $content, $page['umre_cta_label'] ?? '', $page['umre_cta_url'] ?? '' );

        $campaign_settings = STHI_Home_Repository::campaign_settings();
        $campaign_items    = STHI_Home_Repository::active_campaign_items();
        $tour_settings     = STHI_Home_Repository::tour_settings();
        $tour_items        = STHI_Home_Repository::active_tour_items();

        // Preview owns the campaign surface even when the section is disabled or
        // every campaign item is unchecked. This prevents the legacy Raw HTML
        // four-image gallery from leaking back into the preview.
        $campaign_html = ! empty( $campaign_settings['enabled'] )
            ? STHI_Home_Renderer::campaign_grid( $campaign_items, $campaign_settings, true )
            : STHI_Home_Renderer::campaign_empty_state();
        $content = self::replace_campaign_gallery( $content, $campaign_html );

        if ( ! empty( $tour_settings['enabled'] ) ) {
            $tour_html = STHI_Home_Renderer::culture_tours( $tour_items, $tour_settings, true );
            if ( $tour_html ) { $content = self::replace_culture_tours( $content, $tour_html ); }
        }

        $story_settings = STHI_Home_Repository::story_settings();
        $show_story = self::is_preview_request() || self::story_public_enabled();
        if ( $show_story && ! empty( $story_settings['enabled'] ) ) {
            $story_html = STHI_Home_Renderer::journey_evidence( $story_settings, self::is_preview_request() );
            if ( $story_html ) { $content = self::insert_story_after_culture( $content, $story_html ); }
        }

        return $content;
    }
}
