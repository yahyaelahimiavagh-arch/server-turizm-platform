<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

final class STHI_Frontend {
    const ROUTE_VERSION = '0.9.5-h9e-publication-lock';

    public static function init() {
        add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
        add_filter( 'template_include', array( __CLASS__, 'template_include' ), 99 );
        add_filter( 'body_class', array( __CLASS__, 'body_class' ), 100 );
        add_filter( 'document_title_parts', array( __CLASS__, 'document_title_parts' ), 999 );
        add_filter( 'pre_get_document_title', array( __CLASS__, 'pre_get_document_title' ), 999 );
        add_filter( 'wp_robots', array( __CLASS__, 'robots' ), 999 );
        add_filter( 'wp_sitemaps_post_types', array( __CLASS__, 'exclude_from_sitemap' ), 50 );
        add_action( 'wp_head', array( __CLASS__, 'start_head_buffer' ), -9999 );
        add_action( 'wp_head', array( __CLASS__, 'head_meta' ), 3 );
        add_action( 'wp_head', array( __CLASS__, 'finish_head_buffer' ), PHP_INT_MAX );
        add_action( 'template_redirect', array( __CLASS__, 'serve_stable_pilot_preview' ), 0 );
        add_action( 'template_redirect', array( __CLASS__, 'protect_unapproved_hotels' ), 1 );
        add_filter( 'preview_post_link', array( __CLASS__, 'stable_preview_link' ), 50, 2 );
        add_filter( 'post_type_link', array( __CLASS__, 'stable_post_link' ), 50, 4 );
        add_filter( 'redirect_canonical', array( __CLASS__, 'disable_preview_canonical_redirect' ), 50, 2 );
        add_action( 'init', array( __CLASS__, 'maybe_flush_rewrites' ), 99 );
    }

    public static function maybe_flush_rewrites() {
        $stored = (string) get_option( 'sthi_route_version', '' );
        if ( self::ROUTE_VERSION === $stored ) { return; }
        update_option( 'sthi_route_version', self::ROUTE_VERSION, false );
        flush_rewrite_rules( false );
    }

    public static function enqueue_assets() {
        if ( ! self::is_hotel_context() ) { return; }
        wp_enqueue_style( 'sthi-frontend', STHI_URL . 'assets/frontend.css', array(), STHI_VERSION );
        wp_enqueue_script( 'sthi-frontend', STHI_URL . 'assets/frontend.js', array(), STHI_VERSION, true );
        wp_script_add_data( 'sthi-frontend', 'strategy', 'defer' );
    }

    public static function template_include( $template ) {
        if ( is_singular( 'sthi_hotel' ) ) {
            $plugin_template = STHI_DIR . 'templates/single-sthi_hotel.php';
            if ( file_exists( $plugin_template ) ) { return $plugin_template; }
        }
        return $template;
    }

    public static function body_class( $classes ) {
        if ( self::is_hotel_context() ) {
            /*
             * Header/Footer v1.1.10 marks the root URL as st-shell-front via
             * is_front_page(). The stable Hotel pilot URL intentionally lives on
             * that root (/?sthi_hotel_preview=POST_ID), so without this context
             * correction the fixed Server Turizm shell applies zero #main top
             * clearance and covers the Hotel hero.
             *
             * Normalize Hotel Intelligence to the shell's existing internal-page
             * contract. This does not change routing or invent an offset: the
             * accepted Header/Footer plugin remains the source of truth for its
             * own responsive --st-shell-height values and admin-bar placement.
             */
            $classes = array_values( array_diff( $classes, array( 'st-shell-front' ) ) );
            if ( ! in_array( 'st-shell-internal', $classes, true ) ) {
                $classes[] = 'st-shell-internal';
            }
            $classes[] = 'sthi-hotel-page';
            $is_public_route = (bool) apply_filters( 'sthi_hotel_is_public_route_context', false, self::context_post_id() );
            $classes[] = $is_public_route ? 'sthi-hotel-public-candidate' : 'sthi-hotel-pilot';
            $classes[] = 'sthi-luxury-editorial';
        }
        return array_values( array_unique( $classes ) );
    }

    public static function document_title_parts( $parts ) {
        if ( ! self::is_hotel_context() ) { return $parts; }
        $post_id = self::context_post_id();
        $parts['title'] = self::seo_title( $post_id );
        unset( $parts['site'], $parts['tagline'], $parts['page'] );
        return $parts;
    }

    public static function pre_get_document_title( $title ) {
        if ( ! self::is_hotel_context() ) { return $title; }
        return self::seo_title( self::context_post_id() );
    }

    public static function start_head_buffer() {
        if ( ! self::is_hotel_context() ) { return; }
        if ( ! isset( $GLOBALS['sthi_head_buffer_active'] ) ) {
            $GLOBALS['sthi_head_buffer_active'] = true;
            ob_start();
        }
    }

    public static function finish_head_buffer() {
        if ( empty( $GLOBALS['sthi_head_buffer_active'] ) ) { return; }
        $html = ob_get_clean();
        unset( $GLOBALS['sthi_head_buffer_active'] );
        $post_id = self::context_post_id();
        if ( ! $post_id ) { echo $html; return; }
        echo self::normalized_seo_head( $post_id ) . self::strip_conflicting_head_metadata( $html );
    }

    private static function strip_conflicting_head_metadata( $html ) {
        $patterns = array(
            '~<title\b[^>]*>.*?</title>\s*~is',
            '~<meta\b(?=[^>]*\bname\s*=\s*(["\'])description\1)[^>]*>\s*~is',
            '~<meta\b(?=[^>]*\bname\s*=\s*(["\'])robots\1)[^>]*>\s*~is',
            '~<link\b(?=[^>]*\brel\s*=\s*(["\'])[^"\']*\bcanonical\b[^"\']*\1)[^>]*>\s*~is',
            '~<meta\b(?=[^>]*\b(?:property|name)\s*=\s*(["\'])og:[^"\']+\1)[^>]*>\s*~is',
            '~<meta\b(?=[^>]*\b(?:property|name)\s*=\s*(["\'])twitter:[^"\']+\1)[^>]*>\s*~is',
        );
        return preg_replace( $patterns, '', (string) $html );
    }

    private static function normalized_seo_head( $post_id ) {
        $title = self::seo_title( $post_id );
        $description = self::meta_description( $post_id );
        $page_url = self::context_url( $post_id );
        $is_public_route = (bool) apply_filters( 'sthi_hotel_is_public_route_context', false, $post_id );
        $is_indexable = $is_public_route && (bool) apply_filters( 'sthi_hotel_link_target_is_indexable', false, $post_id );
        $canonical = $is_indexable ? trim( (string) apply_filters( 'sthi_hotel_canonical_url', '', $post_id ) ) : '';
        if ( $canonical && ! self::is_public_ready( $post_id ) ) { $canonical = ''; }
        $og_url = $canonical ?: $page_url;
        $gallery = STHI_Media::get_display_items( $post_id );
        $primary = $gallery ? $gallery[0] : array();
        $image = ! empty( $primary['url'] ) ? esc_url_raw( $primary['url'] ) : '';
        list( $iw, $ih ) = $primary ? STHI_Media::image_dimensions( $primary ) : array( 0, 0 );

        $out = "\n<title>" . esc_html( $title ) . "</title>\n";
        if ( $description ) { $out .= '<meta name="description" content="' . esc_attr( $description ) . '">' . "\n"; }
        $robots_content = $is_indexable ? 'index, follow, max-image-preview:large' : 'noindex, follow, max-image-preview:large';
        $out .= '<meta name="robots" content="' . esc_attr( $robots_content ) . '">' . "\n";
        if ( $canonical ) { $out .= '<link rel="canonical" href="' . esc_url( $canonical ) . '">' . "\n"; }
        $out .= '<meta property="og:title" content="' . esc_attr( $title ) . '">' . "\n";
        $out .= '<meta property="og:description" content="' . esc_attr( $description ) . '">' . "\n";
        $out .= '<meta property="og:type" content="website">' . "\n";
        $out .= '<meta property="og:url" content="' . esc_url( $og_url ) . '">' . "\n";
        $out .= '<meta property="og:site_name" content="Server Turizm">' . "\n";
        if ( $image ) {
            $out .= '<meta property="og:image" content="' . esc_url( $image ) . '">' . "\n";
            if ( $iw && $ih ) {
                $out .= '<meta property="og:image:width" content="' . absint( $iw ) . '">' . "\n";
                $out .= '<meta property="og:image:height" content="' . absint( $ih ) . '">' . "\n";
            }
        }
        $out .= '<meta name="twitter:card" content="summary_large_image">' . "\n";
        $out .= '<meta name="twitter:title" content="' . esc_attr( $title ) . '">' . "\n";
        $out .= '<meta name="twitter:description" content="' . esc_attr( $description ) . '">' . "\n";
        if ( $image ) { $out .= '<meta name="twitter:image" content="' . esc_url( $image ) . '">' . "\n"; }
        return $out;
    }

    public static function robots( $robots ) {
        if ( ! self::is_hotel_context() ) { return $robots; }
        $post_id = self::context_post_id();
        $is_public_route = (bool) apply_filters( 'sthi_hotel_is_public_route_context', false, $post_id );
        $is_indexable = $is_public_route && (bool) apply_filters( 'sthi_hotel_link_target_is_indexable', false, $post_id );
        if ( $is_indexable ) {
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

    public static function exclude_from_sitemap( $post_types ) {
        if ( isset( $post_types['sthi_hotel'] ) ) { unset( $post_types['sthi_hotel'] ); }
        return $post_types;
    }

    /**
     * Recovery preview architecture: while Hotel Intelligence is still a noindex pilot,
     * admin/front-end preview links use a direct Post-ID query route instead of relying
     * on custom-post rewrite rules or a mutable slug.
     */
    public static function stable_pilot_url( $post_id ) {
        $post_id = absint( $post_id );
        return $post_id ? add_query_arg( 'sthi_hotel_preview', $post_id, home_url( '/' ) ) : home_url( '/' );
    }

    public static function stable_preview_link( $preview_link, $post ) {
        if ( $post instanceof WP_Post && 'sthi_hotel' === $post->post_type ) {
            return self::stable_pilot_url( $post->ID );
        }
        return $preview_link;
    }

    public static function stable_post_link( $permalink, $post, $leavename, $sample ) {
        if ( $post instanceof WP_Post && 'sthi_hotel' === $post->post_type ) {
            return self::stable_pilot_url( $post->ID );
        }
        return $permalink;
    }

    public static function disable_preview_canonical_redirect( $redirect_url, $requested_url ) {
        if ( self::requested_preview_id() ) { return false; }
        return $redirect_url;
    }

    public static function requested_preview_id() {
        if ( ! isset( $_GET['sthi_hotel_preview'] ) ) { return 0; }
        return absint( wp_unslash( $_GET['sthi_hotel_preview'] ) );
    }

    public static function context_post_id() {
        $preview_id = self::requested_preview_id();
        if ( $preview_id && 'sthi_hotel' === get_post_type( $preview_id ) ) { return $preview_id; }
        $post_id = is_singular( 'sthi_hotel' ) ? get_queried_object_id() : 0;
        return absint( apply_filters( 'sthi_hotel_context_post_id', $post_id ) );
    }

    public static function is_hotel_context() {
        return (bool) self::context_post_id();
    }

    /**
     * Serve the stable pilot URL before WordPress can convert it into a 404.
     * Editors may preview any normal hotel status; anonymous visitors may only see
     * published pilot records. The entire pilot remains noindex and outside sitemap.
     */
    public static function serve_stable_pilot_preview() {
        $post_id = self::requested_preview_id();
        if ( ! $post_id ) { return; }

        $hotel = get_post( $post_id );
        if ( ! $hotel || 'sthi_hotel' !== $hotel->post_type || 'trash' === $hotel->post_status || 'auto-draft' === $hotel->post_status ) {
            self::pilot_404();
        }

        $can_edit = is_user_logged_in() && current_user_can( 'edit_post', $post_id );
        if ( ! $can_edit && 'publish' !== $hotel->post_status ) {
            self::pilot_404();
        }

        if ( ! defined( 'DONOTCACHEPAGE' ) ) { define( 'DONOTCACHEPAGE', true ); }
        status_header( 200 );
        nocache_headers();

        global $post;
        $post = $hotel;
        setup_postdata( $post );

        get_header();
        self::render_page( $post_id );
        get_footer();
        wp_reset_postdata();
        exit;
    }

    private static function pilot_404() {
        status_header( 404 );
        nocache_headers();
        wp_die( esc_html__( 'Hotel preview not found.', 'server-turizm-hotel-intelligence' ), '404', array( 'response' => 404 ) );
    }

    public static function protect_unapproved_hotels() {
        if ( self::requested_preview_id() ) { return; }
        if ( ! is_singular( 'sthi_hotel' ) ) { return; }

        /*
         * H9A separates the accepted query-string Preview surface from the new
         * controlled /otel/{slug}/ public-route foundation. The historical native
         * CPT rewrite (/hotel-preview/{slug}/) must not become a second public URL
         * for the same Hotel Entity, even when that entity is public-ready.
         */
        global $wp_query;
        if ( $wp_query ) { $wp_query->set_404(); }
        status_header( 404 );
        nocache_headers();
    }

    public static function head_meta() {
        if ( ! self::is_hotel_context() ) { return; }
        $post_id = self::context_post_id();
        if ( ! $post_id ) { return; }
        self::render_schema( $post_id );
    }

    public static function is_public_ready( $post_id ) {
        $workflow     = (string) get_post_meta( $post_id, '_sthi_workflow_status', true );
        $verification = (string) get_post_meta( $post_id, '_sthi_verification_status', true );
        $name         = self::hotel_name( $post_id );
        $city         = trim( (string) get_post_meta( $post_id, '_sthi_city', true ) );
        $gallery      = STHI_Media::get_items( $post_id );
        return ( 'published' === $workflow && 'verified' === $verification && $name && $city && ! empty( $gallery ) );
    }

    /**
     * v0.4.5: one-screen hotel workspace. The gallery is rendered once only.
     * Information changes in place through JS tabs instead of creating a very long page.
     */
    public static function render_page( $post_id ) {
        $name          = self::hotel_name( $post_id );
        $official      = trim( (string) get_post_meta( $post_id, '_sthi_official_name', true ) );
        $city          = trim( (string) get_post_meta( $post_id, '_sthi_city', true ) );
        $country       = trim( (string) get_post_meta( $post_id, '_sthi_country', true ) );
        $district      = trim( (string) get_post_meta( $post_id, '_sthi_district', true ) );
        $stars         = self::verified_star_rating( $post_id );
        $verification  = (string) get_post_meta( $post_id, '_sthi_verification_status', true );
        $last_verified = trim( (string) get_post_meta( $post_id, '_sthi_last_verified_at', true ) );
        $gallery       = STHI_Media::get_display_items( $post_id );
        $location      = implode( ', ', array_filter( array( $district, $city, $country ) ) );
        $sacred        = STHI_Sacred_Distance::get( $post_id );
        $rooms         = STHI_Structured_Details::get_rooms( $post_id );
        $amenities     = STHI_Structured_Details::get_amenities( $post_id );
        $references    = STHI_Structured_Details::get_references( $post_id );
        $contacts      = self::public_contacts( STHI_Structured_Details::get_contacts( $post_id ) );
        $scenes        = self::public_scenes( STHI_Structured_Details::get_scenes( $post_id ) );
        $rooms         = self::public_rooms( $rooms );
        $references    = self::public_references( $references );
        $has_details   = self::has_detail_content( $post_id );
        $excerpt       = trim( (string) get_post_field( 'post_excerpt', $post_id ) );
        $overview      = $excerpt ?: self::factual_summary( $post_id );
        $hotel_id      = trim( (string) get_post_meta( $post_id, '_sthi_hotel_id', true ) );
        $has_editorial = STHI_Content::can_render( $post_id );

        if ( empty( $gallery ) ) {
            $thumb_id = get_post_thumbnail_id( $post_id );
            if ( $thumb_id ) {
                $gallery = array( array( 'type' => 'attachment', 'id' => $thumb_id, 'url' => wp_get_attachment_url( $thumb_id ), 'key' => 'attachment:' . $thumb_id ) );
            }
        }
        ?>
        <main id="main" class="sthi-hotel-app" data-sthi-hotel-id="<?php echo esc_attr( $hotel_id ); ?>">
            <?php if ( current_user_can( 'edit_post', $post_id ) ) :
                $ribbon = (string) apply_filters( 'sthi_hotel_editor_ribbon_text', 'Önizleme • Geçici URL • Noindex', $post_id );
            ?>
                <div class="sthi-preview-ribbon" role="status"><?php echo esc_html( $ribbon ); ?></div>
            <?php endif; ?>

            <div class="sthi-luxe-shell">
                <div class="sthi-hero-stage" data-sthi-reveal>
                    <nav class="sthi-breadcrumb" aria-label="Breadcrumb">
                        <a href="<?php echo esc_url( home_url( '/' ) ); ?>">Ana Sayfa</a><span>/</span>
                        <a href="<?php echo esc_url( home_url( '/oteller/' ) ); ?>">Oteller</a><span>/</span>
                        <span aria-current="page"><?php echo esc_html( $name ); ?></span>
                    </nav>

                    <section class="sthi-cinema" aria-label="Otel fotoğrafları">
                        <?php self::render_cinematic_gallery( $gallery, $name, $city ); ?>
                    </section>

                    <section class="sthi-identity">
                        <div class="sthi-identity-copy">
                            <div class="sthi-kicker"><span></span> SERVER TURİZM • HOTEL INTELLIGENCE</div>
                            <?php if ( $stars ) : ?><?php self::render_stars( $stars ); ?><?php endif; ?>
                            <h1><?php echo esc_html( $name ); ?></h1>
                            <?php if ( $official && $official !== $name ) : ?><p class="sthi-official-name"><?php echo esc_html( $official ); ?></p><?php endif; ?>
                            <div class="sthi-title-meta">
                                <?php if ( $location ) : ?><span><?php echo self::icon( 'pin' ); ?><?php echo esc_html( $location ); ?></span><?php endif; ?>
                                <?php if ( 'verified' === $verification ) : ?><span class="is-verified"><?php echo self::icon( 'check' ); ?>Doğrulanmış otel verisi</span><?php endif; ?>
                                <?php if ( $last_verified ) : ?><span class="sthi-last-check">Son kontrol <?php echo esc_html( self::format_date( $last_verified ) ); ?></span><?php endif; ?>
                            </div>
                        </div>

                        <div class="sthi-identity-side">
                            <?php if ( ! empty( $sacred['detected'] ) ) : ?>
                                <a class="sthi-proximity" href="#location" data-sthi-scroll-to="location">
                                    <span class="sthi-proximity-icon"><?php echo self::icon( 'route' ); ?></span>
                                    <span><small><?php echo esc_html( $sacred['target_short'] ); ?></small><strong><?php echo esc_html( $sacred['distance_label'] ?: 'Mesafe' ); ?></strong><em>Kuş uçuşu</em></span>
                                </a>
                            <?php endif; ?>
                            <div class="sthi-identity-actions">
                                <a class="sthi-btn sthi-btn-secondary" href="<?php echo esc_url( self::hotel_map_url( $post_id ) ); ?>" target="_blank" rel="noopener"><?php echo self::icon( 'map' ); ?><span>Haritada aç</span></a>
                                <a class="sthi-btn sthi-btn-primary" href="<?php echo esc_url( self::server_whatsapp_url( $post_id ) ); ?>" target="_blank" rel="noopener"><?php echo self::icon( 'whatsapp' ); ?><span>Danışmana sor</span></a>
                            </div>
                        </div>
                    </section>
                </div>

                <nav class="sthi-journey-nav" data-sthi-journey-nav aria-label="Otel sayfa bölümleri">
                    <div class="sthi-journey-scroll">
                        <a href="#overview" data-sthi-nav-link="overview" class="is-active">Genel bakış</a>
                        <?php if ( $has_details ) : ?><a href="#details" data-sthi-nav-link="details">Bilgiler</a><?php endif; ?>
                        <?php if ( ! empty( $sacred['hotel_lat'] ) && ! empty( $sacred['hotel_lng'] ) ) : ?><a href="#location" data-sthi-nav-link="location">Konum</a><?php endif; ?>
                        <?php if ( $rooms || $references ) : ?><a href="#rooms" data-sthi-nav-link="rooms">Oda & mesafe</a><?php endif; ?>
                        <?php if ( self::known_amenity_count( $amenities ) ) : ?><a href="#amenities" data-sthi-nav-link="amenities">Olanaklar</a><?php endif; ?>
                        <?php if ( $has_editorial ) : ?><a href="#editorial" data-sthi-nav-link="editorial">Otel rehberi</a><?php endif; ?>
                        <?php if ( $contacts || $scenes ) : ?><a href="#contact" data-sthi-nav-link="contact"><?php echo esc_html( $contacts && $scenes ? 'İletişim & 360°' : ( $contacts ? 'İletişim' : '360°' ) ); ?></a><?php endif; ?>
                        <?php if ( $gallery ) : ?><button type="button" data-sthi-open-gallery><?php echo self::icon( 'image' ); ?>Fotoğraflar</button><?php endif; ?>
                    </div>
                    <a class="sthi-journey-cta" href="<?php echo esc_url( self::server_whatsapp_url( $post_id ) ); ?>" target="_blank" rel="noopener"><?php echo self::icon( 'whatsapp' ); ?><span>Bilgi al</span></a>
                </nav>

                <div class="sthi-story">
                    <section id="overview" class="sthi-story-section" data-sthi-section="overview" data-sthi-reveal>
                        <div class="sthi-story-label"><span>01</span><small>GENEL BAKIŞ</small><h2>İlk bakışta otel</h2></div>
                        <div class="sthi-story-body sthi-story-surface"><?php self::render_panel_overview( $post_id, $overview, $sacred ); ?></div>
                    </section>

                    <?php if ( $has_details ) : ?>
                    <section id="details" class="sthi-story-section" data-sthi-section="details" data-sthi-reveal>
                        <div class="sthi-story-label"><span>02</span><small>OTEL PROFİLİ</small><h2>Doğrulanmış bilgiler</h2></div>
                        <div class="sthi-story-body sthi-story-surface"><?php self::render_panel_details( $post_id ); ?></div>
                    </section>
                    <?php endif; ?>

                    <?php if ( ! empty( $sacred['hotel_lat'] ) && ! empty( $sacred['hotel_lng'] ) ) : ?>
                    <section id="location" class="sthi-story-section sthi-location-section" data-sthi-section="location" data-sthi-reveal>
                        <div class="sthi-story-label"><span>03</span><small>KONUM</small><h2>Harem ile bağlantı</h2><?php if ( ! empty( $sacred['distance_label'] ) ) : ?><p><?php echo esc_html( $sacred['target_name'] ); ?> ile kuş uçuşu <strong><?php echo esc_html( $sacred['distance_label'] ); ?></strong>.</p><?php endif; ?></div>
                        <div class="sthi-story-body sthi-map-surface"><?php self::render_panel_map( $post_id, $sacred ); ?></div>
                    </section>
                    <?php endif; ?>

                    <?php if ( $rooms || $references ) : ?>
                    <section id="rooms" class="sthi-story-section" data-sthi-section="rooms" data-sthi-reveal>
                        <div class="sthi-story-label"><span>04</span><small>KONAKLAMA</small><h2>Oda ve referanslar</h2></div>
                        <div class="sthi-story-body sthi-story-surface"><?php self::render_panel_rooms_refs( $rooms, $references, $sacred ); ?></div>
                    </section>
                    <?php endif; ?>

                    <?php if ( self::known_amenity_count( $amenities ) ) : ?>
                    <section id="amenities" class="sthi-story-section" data-sthi-section="amenities" data-sthi-reveal>
                        <div class="sthi-story-label"><span>05</span><small>OLANAKLAR</small><h2>Konaklama deneyimi</h2></div>
                        <div class="sthi-story-body sthi-story-surface"><?php self::render_panel_amenities( $amenities ); ?></div>
                    </section>
                    <?php endif; ?>

                    <?php if ( $has_editorial ) : ?>
                    <section id="editorial" class="sthi-story-section sthi-editorial-section" data-sthi-section="editorial" data-sthi-reveal>
                        <div class="sthi-story-label"><span>06</span><small>OTEL REHBERİ</small><h2>Detaylı otel bilgisi</h2><p>Server Turizm Hotel Entity verileri ve onaylı kanıtlara dayalı editoryal içerik.</p></div>
                        <div class="sthi-story-body sthi-story-surface sthi-editorial-surface"><?php STHI_Content::render( $post_id ); ?></div>
                    </section>
                    <?php endif; ?>

                    <?php if ( $contacts || $scenes ) : ?>
                    <section id="contact" class="sthi-story-section" data-sthi-section="contact" data-sthi-reveal>
                        <div class="sthi-story-label"><span>07</span><small><?php echo esc_html( $contacts && $scenes ? 'İLETİŞİM & 360°' : ( $contacts ? 'İLETİŞİM' : 'SANAL TUR' ) ); ?></small><h2><?php echo esc_html( $contacts && $scenes ? 'Bağlantılar ve 360°' : ( $contacts ? 'Otel iletişimi' : '360° sanal tur' ) ); ?></h2></div>
                        <div class="sthi-story-body sthi-story-surface"><?php self::render_panel_contact( $post_id, $contacts, $scenes ); ?></div>
                    </section>
                    <?php endif; ?>
                </div>

                <section class="sthi-final-cta" data-sthi-reveal>
                    <div><span>SERVER TURİZM</span><h2><?php echo esc_html( $name ); ?> sizin programınıza uygun mu?</h2><p>Otel, oda ve program seçimini tek mesajda danışmanımıza sorun.</p></div>
                    <a class="sthi-final-cta-button" href="<?php echo esc_url( self::server_whatsapp_url( $post_id ) ); ?>" target="_blank" rel="noopener"><?php echo self::icon( 'whatsapp' ); ?><span>WhatsApp'tan bilgi al</span><b>↗</b></a>
                </section>
            </div>

            <?php self::render_lightbox(); ?>
        </main>
        <?php
    }

    private static function render_cinematic_gallery( $items, $name, $city ) {
        if ( empty( $items ) ) {
            echo '<div class="sthi-cinema-empty">' . self::icon( 'image' ) . '<strong>Otel fotoğrafları hazırlanıyor</strong></div>';
            return;
        }
        $data = self::gallery_js_data( $items, $name, $city );
        $visible = array_slice( $items, 0, 5 );
        $total = count( $items );
        ?>
        <div class="sthi-cinema-grid" data-sthi-cinema-grid>
            <?php foreach ( $visible as $index => $item ) : ?>
                <button type="button" class="sthi-cinema-tile sthi-cinema-tile-<?php echo esc_attr( $index + 1 ); ?>" data-sthi-open-gallery data-sthi-index="<?php echo esc_attr( $index ); ?>" aria-label="Fotoğraf <?php echo esc_attr( $index + 1 ); ?> aç">
                    <?php echo self::image_html( $item, $name, $city, $index, 0 === $index, 'large' ); ?>
                    <span class="sthi-cinema-shade" aria-hidden="true"></span>
                    <?php if ( 4 === $index && $total > 5 ) : ?><span class="sthi-cinema-more">+<?php echo esc_html( $total - 5 ); ?><small>daha</small></span><?php endif; ?>
                </button>
            <?php endforeach; ?>
            <button type="button" class="sthi-cinema-all" data-sthi-open-gallery><?php echo self::icon( 'image' ); ?><span>Tüm fotoğraflar</span><strong><?php echo esc_html( $total ); ?></strong></button>
            <div class="sthi-cinema-mobile-progress" aria-hidden="true"><span></span></div>
        </div>
        <script type="application/json" id="sthi-gallery-data"><?php echo wp_json_encode( $data, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT ); ?></script>
        <?php
    }

    private static function render_panel_overview( $post_id, $excerpt, $sacred ) {
        $facts = array();
        $stars = self::verified_star_rating( $post_id );
        $checkin = trim( (string) get_post_meta( $post_id, '_sthi_checkin', true ) );
        $checkout = trim( (string) get_post_meta( $post_id, '_sthi_checkout', true ) );
        $meal = trim( (string) get_post_meta( $post_id, '_sthi_meal_plan', true ) );
        if ( $stars ) { $facts[] = array( 'star', 'Sınıf', $stars . ' Yıldız' ); }
        if ( $checkin ) { $facts[] = array( 'clock', 'Giriş', $checkin ); }
        if ( $checkout ) { $facts[] = array( 'clock', 'Çıkış', $checkout ); }
        if ( $meal ) { $facts[] = array( 'meal', 'Yemek', $meal ); }
        if ( ! empty( $sacred['detected'] ) && ! empty( $sacred['distance_label'] ) ) { $facts[] = array( 'route', $sacred['target_short'], $sacred['distance_label'] ); }
        ?>
        <div class="sthi-panel-heading"><span>GENEL BAKIŞ</span><h2>Otel özeti</h2></div>
        <?php if ( $facts ) : ?>
            <div class="sthi-mini-facts">
                <?php foreach ( $facts as $fact ) : ?><div><?php echo self::icon( $fact[0] ); ?><span><small><?php echo esc_html( $fact[1] ); ?></small><strong><?php echo esc_html( $fact[2] ); ?></strong></span></div><?php endforeach; ?>
            </div>
        <?php endif; ?>
        <?php if ( $excerpt ) : ?><div class="sthi-panel-copy sthi-factual-summary" data-sthi-ai-summary="factual"><?php echo wp_kses_post( wpautop( $excerpt ) ); ?></div><?php endif; ?>
        <div class="sthi-panel-cta">
            <a class="sthi-btn sthi-btn-primary" href="<?php echo esc_url( self::server_whatsapp_url( $post_id ) ); ?>" target="_blank" rel="noopener"><?php echo self::icon( 'whatsapp' ); ?>Bu oteli danışmana sor</a>
        </div>
        <?php
    }

    private static function render_panel_details( $post_id ) {
        $rows = array();
        self::add_detail( $rows, 'Otel tipi', get_post_meta( $post_id, '_sthi_hotel_type', true ) );
        self::add_detail( $rows, 'Marka', get_post_meta( $post_id, '_sthi_brand', true ) );
        self::add_detail( $rows, 'Otel zinciri', get_post_meta( $post_id, '_sthi_hotel_chain', true ) );
        self::add_detail( $rows, 'Açılış yılı', get_post_meta( $post_id, '_sthi_opening_year', true ) );
        self::add_detail( $rows, 'Yenileme yılı', get_post_meta( $post_id, '_sthi_renovation_year', true ) );
        self::add_detail( $rows, 'Check-in', get_post_meta( $post_id, '_sthi_checkin', true ) );
        self::add_detail( $rows, 'Check-out', get_post_meta( $post_id, '_sthi_checkout', true ) );
        $room_count = absint( get_post_meta( $post_id, '_sthi_room_count', true ) );
        if ( $room_count ) { $rows[] = array( 'Oda sayısı', number_format_i18n( $room_count ) ); }
        $floor_count = absint( get_post_meta( $post_id, '_sthi_floor_count', true ) );
        if ( $floor_count ) { $rows[] = array( 'Kat sayısı', number_format_i18n( $floor_count ) ); }
        self::add_detail( $rows, 'Lisans / kayıt no', get_post_meta( $post_id, '_sthi_license_number', true ) );
        self::add_detail( $rows, 'Yemek planı', get_post_meta( $post_id, '_sthi_meal_plan', true ) );
        ?>
        <div class="sthi-panel-heading"><span>DOĞRULANMIŞ VERİLER</span><h2>Otel bilgileri</h2></div>
        <?php if ( $rows ) : ?><dl class="sthi-data-list"><?php foreach ( $rows as $row ) : ?><div><dt><?php echo esc_html( $row[0] ); ?></dt><dd><?php echo esc_html( $row[1] ); ?></dd></div><?php endforeach; ?></dl><?php else : ?><p class="sthi-muted">Ayrıntılı bilgiler doğrulama sürecinde.</p><?php endif; ?>
        <?php $note = trim( (string) get_post_meta( $post_id, '_sthi_operational_notes', true ) ); if ( $note ) : ?><div class="sthi-insight"><strong>Server Turizm operasyon notu</strong><p><?php echo esc_html( $note ); ?></p></div><?php endif; ?>
        <?php
    }

    private static function render_panel_map( $post_id, $sacred ) {
        $lat = isset( $sacred['hotel_lat'] ) ? $sacred['hotel_lat'] : null;
        $lng = isset( $sacred['hotel_lng'] ) ? $sacred['hotel_lng'] : null;
        if ( null === $lat || null === $lng ) { echo '<p class="sthi-muted">Harita için koordinat gerekiyor.</p>'; return; }
        $target_lat = ! empty( $sacred['detected'] ) ? $sacred['lat'] : '';
        $target_lng = ! empty( $sacred['detected'] ) ? $sacred['lng'] : '';
        $target_name = ! empty( $sacred['detected'] ) ? $sacred['target_name'] : '';
        ?>
        <div class="sthi-map-headline">
            <div><span>KONUM & MESAFE</span><h2><?php echo $target_name ? esc_html( $target_name . ' bağlantısı' ) : 'Otel haritası'; ?></h2></div>
            <?php if ( ! empty( $sacred['distance_label'] ) ) : ?><div class="sthi-distance-card"><small>Kuş uçuşu</small><strong><?php echo esc_html( $sacred['distance_label'] ); ?></strong></div><?php endif; ?>
        </div>
        <div class="sthi-live-map-wrap">
            <div class="sthi-live-map"
                data-hotel-lat="<?php echo esc_attr( $lat ); ?>"
                data-hotel-lng="<?php echo esc_attr( $lng ); ?>"
                data-hotel-name="<?php echo esc_attr( self::hotel_name( $post_id ) ); ?>"
                data-target-lat="<?php echo esc_attr( $target_lat ); ?>"
                data-target-lng="<?php echo esc_attr( $target_lng ); ?>"
                data-target-name="<?php echo esc_attr( $target_name ); ?>"
                data-distance="<?php echo esc_attr( isset( $sacred['distance_label'] ) ? $sacred['distance_label'] : '' ); ?>">
                <button type="button" class="sthi-map-start" data-sthi-map-start><?php echo self::icon( 'map' ); ?><span><strong>İnteraktif haritayı aç</strong><small>Harita yalnızca bu sekme açıldığında yüklenir.</small></span></button>
            </div>
            <?php if ( ! empty( $sacred['detected'] ) ) : ?>
                <div class="sthi-route-summary">
                    <span class="sthi-route-dot is-hotel"></span><strong><?php echo esc_html( self::hotel_name( $post_id ) ); ?></strong>
                    <span class="sthi-route-line"></span>
                    <span class="sthi-route-distance"><?php echo esc_html( $sacred['distance_label'] ); ?></span>
                    <span class="sthi-route-line"></span>
                    <span class="sthi-route-dot is-haram"></span><strong><?php echo esc_html( $target_name ); ?></strong>
                </div>
                <p class="sthi-map-note">Gösterilen mesafe koordinatlardan hesaplanan kuş uçuşu mesafedir; yürüme veya araç rotası değildir.</p>
                <a class="sthi-text-link" href="<?php echo esc_url( self::sacred_directions_url( $sacred ) ); ?>" target="_blank" rel="noopener">Google Maps'te rota aç →</a>
            <?php endif; ?>
        </div>
        <?php
    }

    private static function render_panel_rooms_refs( $rooms, $references, $sacred ) {
        ?>
        <div class="sthi-panel-heading"><span>ODA & REFERANS</span><h2>Konaklama detayları</h2></div>
        <?php if ( $rooms ) : ?>
            <div class="sthi-room-list">
                <?php foreach ( $rooms as $room ) : if ( empty( $room['name'] ) ) { continue; } ?>
                    <article><div><strong><?php echo esc_html( $room['name'] ); ?></strong><?php if ( ! empty( $room['view'] ) ) : ?><small><?php echo esc_html( $room['view'] ); ?></small><?php endif; ?></div><div class="sthi-room-meta"><?php if ( ! empty( $room['capacity'] ) ) : ?><span><?php echo esc_html( $room['capacity'] ); ?> kişi</span><?php endif; ?><?php if ( ! empty( $room['beds'] ) ) : ?><span><?php echo esc_html( $room['beds'] ); ?></span><?php endif; ?><?php if ( ! empty( $room['size_m2'] ) ) : ?><span><?php echo esc_html( $room['size_m2'] ); ?> m²</span><?php endif; ?></div></article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        <?php if ( $references ) : ?>
            <div class="sthi-ref-list">
                <?php foreach ( $references as $ref ) : if ( empty( $ref['name'] ) ) { continue; } ?>
                    <div><span><?php echo self::icon( 'route' ); ?></span><div><strong><?php echo esc_html( $ref['name'] ); ?></strong><?php if ( ! empty( $ref['transport_note'] ) ) : ?><small><?php echo esc_html( $ref['transport_note'] ); ?></small><?php endif; ?></div><b><?php echo ! empty( $ref['distance_m'] ) ? esc_html( STHI_Sacred_Distance::format_distance( $ref['distance_m'] ) ) : '—'; ?></b></div>
                <?php endforeach; ?>
            </div>
        <?php elseif ( ! empty( $sacred['detected'] ) ) : ?>
            <div class="sthi-ref-list"><div><span><?php echo self::icon( 'route' ); ?></span><div><strong><?php echo esc_html( $sacred['target_name'] ); ?></strong><small>Koordinatlardan otomatik hesaplandı</small></div><b><?php echo esc_html( $sacred['distance_label'] ); ?></b></div></div>
        <?php endif;
    }

    private static function render_panel_amenities( $amenities ) {
        $catalog = STHI_Structured_Details::amenity_catalog();
        ?>
        <div class="sthi-panel-heading"><span>OLANAKLAR</span><h2>Otel hizmetleri</h2></div>
        <div class="sthi-amenities-public">
            <?php foreach ( $catalog as $group ) : foreach ( $group['items'] as $code => $label ) :
                $status = isset( $amenities[ $code ] ) ? $amenities[ $code ] : 'unknown';
                if ( 'unknown' === $status ) { continue; }
                ?><div class="<?php echo 'yes' === $status ? 'is-yes' : 'is-no'; ?>"><span><?php echo 'yes' === $status ? self::icon( 'check' ) : self::icon( 'minus' ); ?></span><strong><?php echo esc_html( $label ); ?></strong><small><?php echo 'yes' === $status ? 'Var' : 'Yok'; ?></small></div><?php
            endforeach; endforeach; ?>
        </div>
        <?php
    }

    private static function render_panel_contact( $post_id, $contacts, $scenes ) {
        ?>
        <div class="sthi-panel-heading"><span><?php echo esc_html( $contacts && $scenes ? 'İLETİŞİM & SANAL TUR' : ( $contacts ? 'İLETİŞİM' : 'SANAL TUR' ) ); ?></span><h2><?php echo esc_html( $contacts && $scenes ? 'Otel bağlantıları ve 360°' : ( $contacts ? 'Otel iletişim bilgileri' : '360° sanal tur' ) ); ?></h2></div>
        <?php if ( $contacts ) : ?><div class="sthi-contact-list"><?php foreach ( $contacts as $contact ) :
            $value = trim( (string) ( $contact['value'] ?? '' ) ); if ( ! $value ) { continue; }
            $type = (string) ( $contact['type'] ?? 'phone' );
            $href = self::contact_href( $type, $value );
            ?><a href="<?php echo esc_url( $href ); ?>" <?php echo in_array( $type, array( 'website','instagram','facebook','youtube' ), true ) ? 'target="_blank" rel="noopener"' : ''; ?>><span><?php echo self::icon( self::contact_icon( $type ) ); ?></span><span><small><?php echo esc_html( $contact['label'] ?: ucwords( str_replace( '_', ' ', $type ) ) ); ?></small><strong><?php echo esc_html( $value ); ?></strong></span></a><?php endforeach; ?></div><?php endif; ?>
        <?php if ( $scenes ) : ?><div class="sthi-scene-list"><h3>360° sanal tur</h3><?php foreach ( $scenes as $scene ) : if ( empty( $scene['url'] ) ) { continue; } ?><a href="<?php echo esc_url( $scene['url'] ); ?>" target="_blank" rel="noopener"><span class="sthi-360-badge">360°</span><span><strong><?php echo esc_html( $scene['label'] ?: '360° Tur' ); ?></strong><small><?php echo esc_html( ucfirst( (string) ( $scene['type'] ?? 'other' ) ) ); ?></small></span><b>↗</b></a><?php endforeach; ?></div><?php endif; ?>
        <div class="sthi-consultant-box"><div><small>SERVER TURİZM</small><strong>Bu otel size uygun mu?</strong><span>Program ve oda seçimi için danışmanımıza sorun.</span></div><a class="sthi-btn sthi-btn-primary" href="<?php echo esc_url( self::server_whatsapp_url( $post_id ) ); ?>" target="_blank" rel="noopener"><?php echo self::icon( 'whatsapp' ); ?>WhatsApp</a></div>
        <?php
    }

    private static function render_mobile_action_bar( $post_id, $sacred ) {
        ?>
        <nav class="sthi-mobile-actions" aria-label="Otel hızlı işlemleri">
            <a href="<?php echo esc_url( self::server_whatsapp_url( $post_id ) ); ?>" target="_blank" rel="noopener"><?php echo self::icon( 'whatsapp' ); ?><span>Bilgi al</span></a>
            <?php if ( ! empty( $sacred['hotel_lat'] ) ) : ?><button type="button" data-sthi-tab-target="map"><?php echo self::icon( 'route' ); ?><span><?php echo esc_html( $sacred['distance_label'] ?: 'Harita' ); ?></span></button><?php endif; ?>
            <a href="<?php echo esc_url( self::hotel_map_url( $post_id ) ); ?>" target="_blank" rel="noopener"><?php echo self::icon( 'map' ); ?><span>Konum</span></a>
        </nav>
        <?php
    }

    private static function render_lightbox() {
        ?>
        <dialog class="sthi-lightbox" id="sthi-lightbox" aria-label="Otel fotoğraf galerisi">
            <button type="button" class="sthi-lightbox-close" aria-label="Kapat">×</button>
            <button type="button" class="sthi-lightbox-prev" aria-label="Önceki fotoğraf">‹</button>
            <figure><img src="" alt=""><figcaption><span class="sthi-lightbox-count"></span></figcaption></figure>
            <button type="button" class="sthi-lightbox-next" aria-label="Sonraki fotoğraf">›</button>
        </dialog>
        <?php
    }

    public static function verified_star_rating( $post_id ) {
        $stars = max( 0, min( 5, absint( get_post_meta( $post_id, '_sthi_star_rating', true ) ) ) );
        if ( ! $stars ) { return 0; }

        $source_url = trim( (string) get_post_meta( $post_id, '_sthi_star_rating_source_url', true ) );
        $verified_at = trim( (string) get_post_meta( $post_id, '_sthi_star_rating_verified_at', true ) );

        // Star class is a high-risk factual claim. A raw admin value may remain
        // visible for operator review, but public Hotel/Hub/schema surfaces fail
        // closed until provenance + verification date exist.
        if ( ! $source_url || ! $verified_at ) { return 0; }
        return $stars;
    }

    /**
     * Deterministic, short Hotel Entity summary for public and AI-facing output.
     *
     * Only structured Hotel Intelligence values are used. Star class keeps the
     * existing provenance gate; amenities must be explicitly yes; sacred-site
     * distance comes from the accepted distance engine. Nothing is persisted,
     * guessed or copied from an OTA at render time.
     */
    public static function factual_summary( $post_id ) {
        $post_id = absint( $post_id );
        if ( ! $post_id || 'sthi_hotel' !== get_post_type( $post_id ) ) { return ''; }

        $name     = self::hotel_name( $post_id );
        $district = trim( (string) get_post_meta( $post_id, '_sthi_district', true ) );
        $city     = trim( (string) get_post_meta( $post_id, '_sthi_city', true ) );
        $country  = trim( (string) get_post_meta( $post_id, '_sthi_country', true ) );
        $stars    = self::verified_star_rating( $post_id );

        $place_parts = array();
        foreach ( array( $district, $city, $country ) as $part ) {
            if ( '' === $part ) { continue; }
            $key = function_exists( 'mb_strtolower' ) ? mb_strtolower( $part, 'UTF-8' ) : strtolower( $part );
            if ( isset( $place_parts[ $key ] ) ) { continue; }
            $place_parts[ $key ] = $part;
        }
        $place = implode( ', ', array_values( $place_parts ) );

        $classification = $stars ? sprintf( '%d yıldızlı ', $stars ) : '';
        $summary = $place
            ? sprintf( '%1$s, %2$s konumunda bulunan %3$sbir oteldir.', $name, $place, $classification )
            : sprintf( '%1$s, Hotel Intelligence kaydında yer alan %2$sbir oteldir.', $name, $classification );

        $facts = array();
        $sacred = STHI_Sacred_Distance::get( $post_id );
        if ( ! empty( $sacred['detected'] ) && ! empty( $sacred['target_short'] ) && ! empty( $sacred['distance_label'] ) ) {
            $facts[] = sprintf(
                '%1$s için kayıtlı kuş uçuşu mesafe %2$s',
                trim( (string) $sacred['target_short'] ),
                trim( (string) $sacred['distance_label'] )
            );
        }

        $amenity_labels = array(
            'wifi'              => 'Wi-Fi',
            'reception_24h'     => '24 saat resepsiyon',
            'concierge'         => 'concierge hizmeti',
            'luggage_storage'   => 'bagaj muhafazası',
            'restaurant'        => 'restoran',
            'breakfast'         => 'kahvaltı',
            'lunch'             => 'öğle yemeği',
            'dinner'            => 'akşam yemeği',
            'room_service'      => 'oda servisi',
            'minibar'           => 'minibar',
            'elevator'          => 'asansör',
            'wheelchair_access' => 'tekerlekli sandalye erişimi',
            'parking'           => 'otopark',
            'shuttle'           => 'servis',
            'airport_transfer'  => 'havalimanı transferi',
            'air_conditioning'  => 'klima',
            'safe'              => 'kasa',
            'family_room'       => 'aile odası',
            'housekeeping'      => 'kat hizmetleri',
            'laundry'           => 'çamaşırhane',
            'prayer_room'       => 'mescit',
            'gym'               => 'spor salonu',
            'pool'              => 'havuz',
            'business_center'   => 'iş merkezi',
        );
        $amenities = STHI_Structured_Details::get_amenities( $post_id );
        $selected = array();
        foreach ( $amenity_labels as $code => $label ) {
            if ( isset( $amenities[ $code ] ) && 'yes' === $amenities[ $code ] ) { $selected[] = $label; }
            if ( 3 === count( $selected ) ) { break; }
        }
        if ( $selected ) {
            $facts[] = 'kayıtlı olanaklar arasında ' . self::turkish_list( $selected ) . ' bulunur';
        }

        if ( $facts ) {
            $summary .= ' Hotel Intelligence verilerine göre ' . implode( '; ', $facts ) . '.';
        } else {
            $summary .= ' Konum ve otel bilgileri yapılandırılmış Hotel Intelligence kaydından üretilir.';
        }

        return trim( preg_replace( '/\s+/u', ' ', wp_strip_all_tags( $summary ) ) );
    }

    private static function turkish_list( $items ) {
        $items = array_values( array_filter( array_map( 'trim', (array) $items ) ) );
        $count = count( $items );
        if ( 0 === $count ) { return ''; }
        if ( 1 === $count ) { return $items[0]; }
        $last = array_pop( $items );
        return implode( ', ', $items ) . ' ve ' . $last;
    }

    private static function render_stars( $stars ) {
        echo '<div class="sthi-star-row" aria-label="' . esc_attr( sprintf( '%d yıldızlı otel', $stars ) ) . '">';
        for ( $i = 0; $i < $stars; $i++ ) { echo '<span>' . self::icon( 'star' ) . '</span>'; }
        echo '</div>';
    }

    private static function public_contacts( $contacts ) {
        return array_values( array_filter( (array) $contacts, function( $contact ) {
            return is_array( $contact ) && '' !== trim( (string) ( $contact['value'] ?? '' ) );
        } ) );
    }

    private static function public_scenes( $scenes ) {
        return array_values( array_filter( (array) $scenes, function( $scene ) {
            return is_array( $scene ) && '' !== trim( (string) ( $scene['url'] ?? '' ) );
        } ) );
    }

    private static function public_rooms( $rooms ) {
        return array_values( array_filter( (array) $rooms, function( $room ) {
            return is_array( $room ) && '' !== trim( (string) ( $room['name'] ?? '' ) );
        } ) );
    }

    private static function public_references( $references ) {
        return array_values( array_filter( (array) $references, function( $ref ) {
            return is_array( $ref ) && '' !== trim( (string) ( $ref['name'] ?? '' ) );
        } ) );
    }

    private static function has_detail_content( $post_id ) {
        $keys = array(
            '_sthi_hotel_type', '_sthi_brand', '_sthi_hotel_chain', '_sthi_license_number', '_sthi_opening_year', '_sthi_renovation_year',
            '_sthi_checkin', '_sthi_checkout', '_sthi_room_count', '_sthi_floor_count', '_sthi_meal_plan', '_sthi_operational_notes'
        );
        foreach ( $keys as $key ) {
            if ( '' !== trim( (string) get_post_meta( $post_id, $key, true ) ) ) { return true; }
        }
        return false;
    }

    private static function known_amenity_count( $amenities ) {
        return count( array_filter( (array) $amenities, function( $value ) { return 'unknown' !== $value; } ) );
    }

    private static function add_detail( &$rows, $label, $value ) {
        $value = trim( (string) $value );
        if ( '' !== $value ) { $rows[] = array( $label, $value ); }
    }

    private static function hotel_name( $post_id ) {
        $official = trim( (string) get_post_meta( $post_id, '_sthi_official_name', true ) );
        return $official ?: get_the_title( $post_id );
    }

    private static function gallery_js_data( $items, $name, $city ) {
        $data = array();
        foreach ( $items as $index => $item ) {
            $url = isset( $item['url'] ) ? esc_url_raw( $item['url'] ) : '';
            if ( 'attachment' === ( $item['type'] ?? '' ) && ! empty( $item['id'] ) ) {
                $full = wp_get_attachment_image_url( absint( $item['id'] ), 'full' );
                if ( $full ) { $url = $full; }
            }
            if ( ! $url ) { continue; }
            $data[] = array( 'url' => $url, 'alt' => self::image_alt( $item, $name, $city, $index ) );
        }
        return $data;
    }

    private static function image_html( $item, $name, $city, $index, $priority = false, $size = 'large' ) {
        $alt = self::image_alt( $item, $name, $city, $index );
        if ( 'attachment' === ( $item['type'] ?? '' ) && ! empty( $item['id'] ) ) {
            return wp_get_attachment_image( absint( $item['id'] ), $size, false, array(
                'alt' => $alt,
                'loading' => $priority ? 'eager' : 'lazy',
                'fetchpriority' => $priority ? 'high' : 'auto',
                'decoding' => 'async',
                'sizes' => $priority ? '(max-width: 820px) 100vw, 62vw' : '(max-width: 820px) 20vw, 120px',
            ) );
        }
        $url = isset( $item['url'] ) ? $item['url'] : '';
        if ( ! $url ) { return ''; }
        list( $width, $height ) = STHI_Media::image_dimensions( $item );
        $dimensions = ( $width && $height ) ? ' width="' . absint( $width ) . '" height="' . absint( $height ) . '"' : '';
        return '<img src="' . esc_url( $url ) . '" alt="' . esc_attr( $alt ) . '" loading="' . ( $priority ? 'eager' : 'lazy' ) . '" fetchpriority="' . ( $priority ? 'high' : 'auto' ) . '" decoding="async"' . $dimensions . '>';
    }

    private static function image_alt( $item, $name, $city, $index ) {
        if ( 'attachment' === ( $item['type'] ?? '' ) && ! empty( $item['id'] ) ) {
            $custom = trim( (string) get_post_meta( absint( $item['id'] ), '_wp_attachment_image_alt', true ) );
            if ( $custom ) { return $custom; }
        }
        return trim( sprintf( '%s%s otel fotoğrafı %d', $name, $city ? ' - ' . $city : '', $index + 1 ) );
    }

    private static function hotel_map_url( $post_id ) {
        $coords = STHI_Sacred_Distance::hotel_coordinates( $post_id );
        if ( $coords ) {
            return 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode( $coords[0] . ',' . $coords[1] );
        }
        $address = trim( (string) get_post_meta( $post_id, '_sthi_address', true ) );
        $city = trim( (string) get_post_meta( $post_id, '_sthi_city', true ) );
        return 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode( trim( $address . ' ' . $city ) );
    }

    private static function sacred_directions_url( $sacred ) {
        if ( empty( $sacred['hotel_lat'] ) || empty( $sacred['hotel_lng'] ) || empty( $sacred['lat'] ) || empty( $sacred['lng'] ) ) { return ''; }
        return 'https://www.google.com/maps/dir/?api=1&origin=' . rawurlencode( $sacred['hotel_lat'] . ',' . $sacred['hotel_lng'] ) . '&destination=' . rawurlencode( $sacred['lat'] . ',' . $sacred['lng'] );
    }

    private static function server_whatsapp_url( $post_id ) {
        $number = apply_filters( 'sthi_server_whatsapp_number', '905302015284' );
        $name = self::hotel_name( $post_id );
        $hotel_id = get_post_meta( $post_id, '_sthi_hotel_id', true );
        $text = sprintf( 'Merhaba, %s (%s) oteli hakkında bilgi almak istiyorum.', $name, $hotel_id ?: 'Server Turizm' );
        return 'https://wa.me/' . preg_replace( '/\D+/', '', (string) $number ) . '?text=' . rawurlencode( $text );
    }

    private static function contact_href( $type, $value ) {
        if ( in_array( $type, array( 'website','instagram','facebook','youtube' ), true ) ) { return $value; }
        if ( in_array( $type, array( 'email','reservation_email' ), true ) ) { return 'mailto:' . sanitize_email( $value ); }
        if ( 'whatsapp' === $type ) { return 'https://wa.me/' . preg_replace( '/\D+/', '', $value ); }
        return 'tel:+' . ltrim( preg_replace( '/\D+/', '', $value ), '+' );
    }

    private static function contact_icon( $type ) {
        if ( in_array( $type, array( 'email','reservation_email' ), true ) ) { return 'mail'; }
        if ( in_array( $type, array( 'website','instagram','facebook','youtube' ), true ) ) { return 'globe'; }
        if ( 'whatsapp' === $type ) { return 'whatsapp'; }
        return 'phone';
    }

    private static function format_date( $date ) {
        $timestamp = strtotime( $date );
        return $timestamp ? wp_date( 'd.m.Y', $timestamp ) : $date;
    }

    private static function seo_title( $post_id ) {
        $override = trim( wp_strip_all_tags( (string) get_post_meta( $post_id, '_sthi_seo_title_tr', true ) ) );
        if ( $override ) { return wp_html_excerpt( $override, 70, '…' ); }
        $name = self::hotel_name( $post_id );
        $city = trim( (string) get_post_meta( $post_id, '_sthi_city', true ) );
        return $city
            ? sprintf( '%1$s | %2$s Otel Bilgileri | Server Turizm', $name, $city )
            : sprintf( '%s | Otel Bilgileri | Server Turizm', $name );
    }

    private static function meta_description( $post_id ) {
        $override = trim( wp_strip_all_tags( (string) get_post_meta( $post_id, '_sthi_meta_description_tr', true ) ) );
        if ( $override ) { return wp_html_excerpt( $override, 160, '…' ); }
        $excerpt = trim( wp_strip_all_tags( (string) get_post_field( 'post_excerpt', $post_id ) ) );
        if ( $excerpt ) { return wp_html_excerpt( $excerpt, 160, '…' ); }
        if ( class_exists( 'STHI_Content' ) && STHI_Content::can_render( $post_id ) ) {
            $summary = trim( wp_strip_all_tags( (string) get_post_meta( $post_id, STHI_Content::META_SUMMARY, true ) ) );
            if ( $summary ) { return wp_html_excerpt( $summary, 160, '…' ); }
        }
        $name    = self::hotel_name( $post_id );
        $city    = trim( (string) get_post_meta( $post_id, '_sthi_city', true ) );
        $country = trim( (string) get_post_meta( $post_id, '_sthi_country', true ) );
        $place   = implode( ', ', array_filter( array( $city, $country ) ) );
        $text    = $place
            ? sprintf( '%1$s (%2$s) için fotoğraflar, konum ve doğrulanmış otel bilgilerini Server Turizm üzerinden inceleyin.', $name, $place )
            : sprintf( '%s için fotoğraflar, konum ve doğrulanmış otel bilgilerini Server Turizm üzerinden inceleyin.', $name );
        return wp_html_excerpt( $text, 160, '…' );
    }

    private static function context_url( $post_id ) {
        if ( self::requested_preview_id() ) { return self::stable_pilot_url( $post_id ); }
        $route_url = trim( (string) apply_filters( 'sthi_hotel_context_url', '', $post_id ) );
        if ( $route_url ) { return esc_url_raw( $route_url ); }
        $url = get_permalink( $post_id );
        return $url ?: self::stable_pilot_url( $post_id );
    }

    private static function stable_hotel_entity_id( $post_id ) {
        $hotel_id = trim( (string) get_post_meta( $post_id, '_sthi_hotel_id', true ) );
        $key = $hotel_id ?: 'post-' . absint( $post_id );
        return home_url( '/#hotel-' . sanitize_title( $key ) );
    }

    private static function verified_schema_contacts( $post_id ) {
        $out = array( 'telephone' => '', 'email' => '', 'sameAs' => array() );
        $contacts = STHI_Structured_Details::get_contacts( $post_id );
        foreach ( (array) $contacts as $contact ) {
            $verified = trim( (string) ( $contact['verified_at'] ?? '' ) );
            $value = trim( (string) ( $contact['value'] ?? '' ) );
            $type = sanitize_key( (string) ( $contact['type'] ?? '' ) );
            if ( ! $verified || ! $value ) { continue; }
            if ( ! $out['telephone'] && in_array( $type, array( 'phone', 'reservation_phone', 'whatsapp' ), true ) ) { $out['telephone'] = $value; }
            if ( ! $out['email'] && in_array( $type, array( 'email', 'reservation_email' ), true ) && is_email( $value ) ) { $out['email'] = $value; }
            if ( in_array( $type, array( 'website', 'instagram', 'facebook', 'youtube' ), true ) && wp_http_validate_url( $value ) ) { $out['sameAs'][] = esc_url_raw( $value ); }
        }
        $out['sameAs'] = array_values( array_unique( array_filter( $out['sameAs'] ) ) );
        return $out;
    }

    private static function schema_amenities( $post_id ) {
        $values = STHI_Structured_Details::get_amenities( $post_id );
        $labels = array();
        foreach ( STHI_Structured_Details::amenity_catalog() as $group ) {
            foreach ( $group['items'] as $code => $label ) { $labels[ $code ] = $label; }
        }
        $features = array();
        foreach ( $values as $code => $status ) {
            if ( 'yes' !== $status || empty( $labels[ $code ] ) ) { continue; }
            $features[] = array( '@type' => 'LocationFeatureSpecification', 'name' => $labels[ $code ], 'value' => true );
        }
        return $features;
    }

    private static function render_schema( $post_id ) {
        $name = self::hotel_name( $post_id );
        $url = self::context_url( $post_id );
        $page_id = $url . '#webpage';
        $hotel_id = self::stable_hotel_entity_id( $post_id );
        $crumb_id = $url . '#breadcrumb';
        $website_id = home_url( '/#website' );
        $organization_id = home_url( '/#organization' );
        $graph = array();
        $graph[] = array(
            '@type' => array( 'Organization', 'TravelAgency' ),
            '@id' => $organization_id,
            'name' => 'Server Turizm',
            'url' => home_url( '/' ),
        );
        $graph[] = array(
            '@type' => 'WebSite',
            '@id' => $website_id,
            'url' => home_url( '/' ),
            'name' => 'Server Turizm',
            'inLanguage' => 'tr-TR',
            'publisher' => array( '@id' => $organization_id ),
        );
        $graph[] = array(
            '@type' => 'BreadcrumbList', '@id' => $crumb_id,
            'itemListElement' => array(
                array( '@type' => 'ListItem', 'position' => 1, 'name' => 'Ana Sayfa', 'item' => home_url( '/' ) ),
                array( '@type' => 'ListItem', 'position' => 2, 'name' => $name, 'item' => $url ),
            ),
        );
        $webpage = array(
            '@type' => 'WebPage', '@id' => $page_id, 'url' => $url,
            'name' => self::seo_title( $post_id ), 'description' => self::meta_description( $post_id ),
            'isPartOf' => array( '@id' => $website_id ),
            'breadcrumb' => array( '@id' => $crumb_id ),
            'dateModified' => get_post_modified_time( DATE_W3C, true, $post_id ),
        );
        if ( 'verified' === get_post_meta( $post_id, '_sthi_verification_status', true ) ) {
            $hotel = array(
                '@type' => 'Hotel', '@id' => $hotel_id, 'name' => $name, 'url' => $url,
                'description' => self::factual_summary( $post_id ),
                'subjectOf' => array( '@id' => $page_id ),
            );
            $brand = trim( (string) get_post_meta( $post_id, '_sthi_brand', true ) );
            if ( $brand ) { $hotel['brand'] = array( '@type' => 'Brand', 'name' => $brand ); }
            $contacts = self::verified_schema_contacts( $post_id );
            if ( $contacts['telephone'] ) { $hotel['telephone'] = $contacts['telephone']; }
            if ( $contacts['email'] ) { $hotel['email'] = $contacts['email']; }
            if ( $contacts['sameAs'] ) { $hotel['sameAs'] = $contacts['sameAs']; }
            $address = array_filter( array(
                '@type' => 'PostalAddress',
                'streetAddress' => trim( (string) get_post_meta( $post_id, '_sthi_address', true ) ),
                'addressLocality' => trim( (string) get_post_meta( $post_id, '_sthi_city', true ) ),
                'addressRegion' => trim( (string) get_post_meta( $post_id, '_sthi_region', true ) ),
                'postalCode' => trim( (string) get_post_meta( $post_id, '_sthi_postal_code', true ) ),
                'addressCountry' => trim( (string) get_post_meta( $post_id, '_sthi_country', true ) ),
            ) );
            if ( count( $address ) > 1 ) { $hotel['address'] = $address; }
            $coords = STHI_Sacred_Distance::hotel_coordinates( $post_id );
            if ( $coords ) { $hotel['geo'] = array( '@type' => 'GeoCoordinates', 'latitude' => $coords[0], 'longitude' => $coords[1] ); }
            $amenities = self::schema_amenities( $post_id );
            if ( $amenities ) { $hotel['amenityFeature'] = $amenities; }

            $stars = self::verified_star_rating( $post_id );
            $star_source = trim( (string) get_post_meta( $post_id, '_sthi_star_rating_source_url', true ) );
            $star_verified = trim( (string) get_post_meta( $post_id, '_sthi_star_rating_verified_at', true ) );
            if ( $stars >= 1 && $stars <= 5 && $star_source && $star_verified ) {
                $hotel['starRating'] = array( '@type' => 'Rating', 'ratingValue' => $stars, 'bestRating' => 5 );
            }

            $images = STHI_Media::get_display_items( $post_id );
            if ( $images && ! empty( $images[0]['url'] ) ) {
                $primary = $images[0];
                list( $iw, $ih ) = STHI_Media::image_dimensions( $primary );
                $image_id = $hotel_id . '-primary-image';
                $image = array(
                    '@type' => 'ImageObject', '@id' => $image_id,
                    'contentUrl' => esc_url_raw( $primary['url'] ),
                    'caption' => self::image_alt( $primary, $name, trim( (string) get_post_meta( $post_id, '_sthi_city', true ) ), 0 ),
                );
                if ( $iw && $ih ) { $image['width'] = $iw; $image['height'] = $ih; }
                $graph[] = $image;
                $hotel['image'] = array( '@id' => $image_id );
                $webpage['primaryImageOfPage'] = array( '@id' => $image_id );
            }
            $webpage['mainEntity'] = array( '@id' => $hotel_id );
            $graph[] = $hotel;
        }
        $graph[] = $webpage;
        echo "\n" . '<script type="application/ld+json" data-sthi-schema="hotel-graph">' . wp_json_encode( array( '@context' => 'https://schema.org', '@graph' => $graph ), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . '</script>' . "\n";
    }

    private static function icon( $name ) {
        $icons = array(
            'pin' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 21s7-6.2 7-12a7 7 0 1 0-14 0c0 5.8 7 12 7 12Z"/><circle cx="12" cy="9" r="2.4"/></svg>',
            'check' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="m5 12 4 4L19 6"/></svg>',
            'clock' => '<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>',
            'star' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="m12 3 2.8 5.7 6.2.9-4.5 4.4 1.1 6.2-5.6-3-5.6 3 1.1-6.2L3 9.6l6.2-.9L12 3Z"/></svg>',
            'meal' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7 3v8M4 3v5a3 3 0 0 0 6 0V3M7 11v10M16 3v18M16 3c3 1 4 4 4 7h-4"/></svg>',
            'map' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="m3 6 6-3 6 3 6-3v15l-6 3-6-3-6 3V6Z"/><path d="M9 3v15M15 6v15"/></svg>',
            'route' => '<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="6" cy="18" r="2"/><circle cx="18" cy="6" r="2"/><path d="M8 18h3a3 3 0 0 0 3-3V9a3 3 0 0 1 3-3"/></svg>',
            'whatsapp' => '<svg class="sthi-icon-whatsapp" viewBox="0 0 24 24" aria-hidden="true"><path d="M20 11.6a8 8 0 0 1-11.6 7.1L4 20l1.3-4.2A8 8 0 1 1 20 11.6Z"/><path d="M8.2 7.7c.35 3.55 2.85 6.05 6.4 6.4l1.25-1.75-2.15-1.05-.95 1.05a6.7 6.7 0 0 1-3.05-3.05l1.05-.95-1.05-2.15-1.5 1.5Z"/></svg>',
            'phone' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 4h4l2 5-2.5 1.5a15 15 0 0 0 5 5L15 13l5 2v4c0 1.1-.9 2-2 2C9.7 20.5 3.5 14.3 3 6c0-1.1.9-2 2-2Z"/></svg>',
            'mail' => '<svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="m4 7 8 6 8-6"/></svg>',
            'globe' => '<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3c3 3 4 6 4 9s-1 6-4 9c-3-3-4-6-4-9s1-6 4-9Z"/></svg>',
            'image' => '<svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="4" width="18" height="16" rx="2"/><circle cx="9" cy="9" r="2"/><path d="m4 17 5-5 4 4 2-2 5 5"/></svg>',
            'minus' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h14"/></svg>',
        );
        return isset( $icons[ $name ] ) ? $icons[ $name ] : '';
    }
}
STHI_Frontend::init();
