<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * H7 — data-driven Mekke / Medine Hotel Hubs.
 *
 * H7 builds the hub rendering/eligibility architecture but deliberately keeps
 * public hub activation locked until H9 final Hotel canonical/indexation policy.
 * Admins can use the noindex preview surface to runtime-test real Hotel Entity data.
 */
final class STHI_Hubs {
    const PREVIEW_QUERY = 'sthi_hub_preview';

    public static function init() {
        add_action( 'admin_menu', array( __CLASS__, 'admin_menu' ), 40 );
        add_action( 'template_redirect', array( __CLASS__, 'serve_preview' ), -5 );
        add_filter( 'body_class', array( __CLASS__, 'body_class' ), 110 );
        add_filter( 'document_title_parts', array( __CLASS__, 'document_title_parts' ), 1000 );
        add_filter( 'pre_get_document_title', array( __CLASS__, 'pre_get_document_title' ), 1000 );
        add_filter( 'wp_robots', array( __CLASS__, 'robots' ), 1000 );
        add_action( 'wp_head', array( __CLASS__, 'start_head_buffer' ), -10000 );
        add_action( 'wp_head', array( __CLASS__, 'finish_head_buffer' ), PHP_INT_MAX );
        add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ), 30 );
        add_shortcode( 'sthi_hotel_hub', array( __CLASS__, 'shortcode' ) );
    }

    public static function registry() {
        $registry = array(
            'all' => array(
                'title'       => 'Oteller',
                'kicker'      => 'SERVER TURİZM • HOTEL INTELLIGENCE',
                'description' => 'Server Turizm Hotel Intelligence içinde doğrulanmış ve yayın hazırlığı tamamlanmış otel kayıtlarını tek merkezden inceleyin.',
                'future_path' => '/oteller/',
                'city_aliases'=> array(),
            ),
            'mekke' => array(
                'title'       => 'Mekke Otelleri',
                'kicker'      => 'MEKKE • HOTEL INTELLIGENCE',
                'description' => 'Server Turizm tarafından yapılandırılmış ve doğrulama sürecinden geçirilen Mekke otellerini tek merkezden inceleyin.',
                'future_path' => '/mekke-otelleri/',
                'city_aliases'=> array( 'makkah', 'mekke', 'mecca' ),
            ),
            'medine' => array(
                'title'       => 'Medine Otelleri',
                'kicker'      => 'MEDİNE • HOTEL INTELLIGENCE',
                'description' => 'Server Turizm tarafından yapılandırılmış ve doğrulama sürecinden geçirilen Medine otellerini tek merkezden inceleyin.',
                'future_path' => '/medine-otelleri/',
                'city_aliases'=> array( 'madinah', 'medine', 'medina' ),
            ),
        );
        return apply_filters( 'sthi_h7_hub_registry', $registry );
    }

    public static function admin_menu() {
        add_submenu_page(
            'sthi-dashboard',
            'Hotel Hubs — H9E',
            'Hubs — H9E',
            'edit_posts',
            'sthi-hubs',
            array( __CLASS__, 'render_admin' )
        );
    }

    public static function render_admin() {
        if ( ! current_user_can( 'edit_posts' ) ) { return; }
        $registry = self::registry();
        ?>
        <div class="wrap sthi-wrap">
            <h1>Hotel Hubs — H9E</h1>
            <p class="sthi-lead">Data-driven Mekke / Medine Hotel Hub architecture. H7 Preview remains noindex/editor-only. Final public routes remain available for QA; H9E master publication lock governs canonical/indexation/sitemap exposure.</p>
            <div class="sthi-panel" style="margin-bottom:18px">
                <h2>H7 safety contract</h2>
                <ul>
                    <li>Hub membership comes from structured Hotel Entity city data; never Hotel-name guessing.</li>
                    <li>Candidate hotels must pass the existing Hotel Intelligence public-ready gate.</li>
                    <li>Public Hotel links require the later H9 indexable/canonical gate; H7 does not invent final Hotel URLs.</li>
                    <li>No filter/search URL explosion. Only the two durable hub concepts are implemented.</li>
                    <li>Legacy Porto Portfolio is retired. H9B may now claim <code>/oteller/</code> as the new data-driven All Hotels candidate route.</li>
                </ul>
            </div>
            <div class="sthi-cards">
                <?php foreach ( $registry as $hub_id => $hub ) :
                    $candidates = self::get_hotels( $hub_id, 'candidate' );
                    $eligible   = self::get_hotels( $hub_id, 'public' );
                    $preview    = add_query_arg( self::PREVIEW_QUERY, $hub_id, home_url( '/' ) );
                ?>
                    <div class="sthi-card" style="min-width:280px">
                        <span><?php echo esc_html( $hub['title'] ); ?></span>
                        <strong><?php echo esc_html( count( $candidates ) ); ?> Candidate</strong>
                        <p style="margin:8px 0 4px">Public eligible now: <b><?php echo esc_html( count( $eligible ) ); ?></b></p>
                        <p style="margin:4px 0">Future route: <code><?php echo esc_html( $hub['future_path'] ); ?></code></p>
                        <a class="button button-primary" href="<?php echo esc_url( $preview ); ?>" target="_blank" rel="noopener">Open noindex Preview</a>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="sthi-panel" style="margin-top:18px">
                <h2>H9E publication master lock</h2>
                <p><strong>CONTROLLED PUBLICATION.</strong> Final routes can be runtime-tested while the master switch is locked. When enabled later, only eligible non-empty public surfaces may become canonical/indexable; previews always remain noindex.</p>
            </div>
        </div>
        <?php
    }

    public static function is_preview() {
        if ( ! isset( $_GET[ self::PREVIEW_QUERY ] ) ) { return false; }
        $hub = sanitize_key( wp_unslash( $_GET[ self::PREVIEW_QUERY ] ) );
        return isset( self::registry()[ $hub ] );
    }

    public static function current_hub_id() {
        if ( ! self::is_preview() ) { return ''; }
        return sanitize_key( wp_unslash( $_GET[ self::PREVIEW_QUERY ] ) );
    }

    public static function serve_preview() {
        if ( ! self::is_preview() ) { return; }
        if ( ! is_user_logged_in() || ! current_user_can( 'edit_posts' ) ) {
            global $wp_query;
            if ( $wp_query ) { $wp_query->set_404(); }
            status_header( 404 );
            nocache_headers();
            return;
        }

        status_header( 200 );
        nocache_headers();
        $hub_id = self::current_hub_id();
        get_header();
        self::render_hub( $hub_id, true );
        get_footer();
        exit;
    }

    public static function enqueue_assets() {
        if ( self::is_preview() ) {
            wp_enqueue_style( 'sthi-hubs', STHI_URL . 'assets/hubs.css', array(), STHI_VERSION );
        }
    }

    public static function body_class( $classes ) {
        if ( ! self::is_preview() ) { return $classes; }
        $classes = array_values( array_diff( $classes, array( 'st-shell-front' ) ) );
        if ( ! in_array( 'st-shell-internal', $classes, true ) ) { $classes[] = 'st-shell-internal'; }
        $classes[] = 'sthi-hub-page';
        $classes[] = 'sthi-hub-preview';
        return array_values( array_unique( $classes ) );
    }

    public static function document_title_parts( $parts ) {
        if ( ! self::is_preview() ) { return $parts; }
        $hub = self::registry()[ self::current_hub_id() ];
        $parts['title'] = $hub['title'] . ' | Server Turizm';
        unset( $parts['site'], $parts['tagline'], $parts['page'] );
        return $parts;
    }

    public static function pre_get_document_title( $title ) {
        if ( ! self::is_preview() ) { return $title; }
        $hub = self::registry()[ self::current_hub_id() ];
        return $hub['title'] . ' | Server Turizm';
    }

    public static function robots( $robots ) {
        if ( ! self::is_preview() ) { return $robots; }
        $robots['noindex'] = true;
        $robots['follow'] = true;
        $robots['max-image-preview'] = 'large';
        return $robots;
    }

    public static function start_head_buffer() {
        if ( ! self::is_preview() ) { return; }
        if ( empty( $GLOBALS['sthi_h7_head_buffer'] ) ) {
            $GLOBALS['sthi_h7_head_buffer'] = true;
            ob_start();
        }
    }

    public static function finish_head_buffer() {
        if ( empty( $GLOBALS['sthi_h7_head_buffer'] ) ) { return; }
        $html = ob_get_clean();
        unset( $GLOBALS['sthi_h7_head_buffer'] );
        $hub = self::registry()[ self::current_hub_id() ];
        $title = $hub['title'] . ' | Server Turizm';
        $desc  = $hub['description'];

        $patterns = array(
            '~<title\b[^>]*>.*?</title>\s*~is',
            '~<meta\b(?=[^>]*\bname\s*=\s*(["\'])description\1)[^>]*>\s*~is',
            '~<meta\b(?=[^>]*\bname\s*=\s*(["\'])robots\1)[^>]*>\s*~is',
            '~<link\b(?=[^>]*\brel\s*=\s*(["\'])[^"\']*\bcanonical\b[^"\']*\1)[^>]*>\s*~is',
            '~<meta\b(?=[^>]*\b(?:property|name)\s*=\s*(["\'])og:[^"\']+\1)[^>]*>\s*~is',
            '~<meta\b(?=[^>]*\b(?:property|name)\s*=\s*(["\'])twitter:[^"\']+\1)[^>]*>\s*~is',
        );
        $html = preg_replace( $patterns, '', (string) $html );

        $head  = '<title>' . esc_html( $title ) . '</title>' . "\n";
        $head .= '<meta name="description" content="' . esc_attr( $desc ) . '">' . "\n";
        $head .= '<meta name="robots" content="noindex, follow, max-image-preview:large">' . "\n";
        $head .= '<meta property="og:type" content="website">' . "\n";
        $head .= '<meta property="og:title" content="' . esc_attr( $title ) . '">' . "\n";
        $head .= '<meta property="og:description" content="' . esc_attr( $desc ) . '">' . "\n";
        $head .= '<meta property="og:url" content="' . esc_url( add_query_arg( self::PREVIEW_QUERY, self::current_hub_id(), home_url( '/' ) ) ) . '">' . "\n";
        $head .= '<meta property="og:site_name" content="Server Turizm">' . "\n";
        $head .= '<meta name="twitter:card" content="summary_large_image">' . "\n";
        $head .= '<meta name="twitter:title" content="' . esc_attr( $title ) . '">' . "\n";
        $head .= '<meta name="twitter:description" content="' . esc_attr( $desc ) . '">' . "\n";
        echo $head . $html;
    }

    public static function shortcode( $atts ) {
        $atts = shortcode_atts( array( 'id' => '' ), $atts, 'sthi_hotel_hub' );
        $hub_id = sanitize_key( $atts['id'] );
        if ( ! isset( self::registry()[ $hub_id ] ) ) { return ''; }
        ob_start();
        self::render_hub( $hub_id, false );
        return ob_get_clean();
    }

    public static function render_hub( $hub_id, $preview = false ) {
        $registry = self::registry();
        if ( ! isset( $registry[ $hub_id ] ) ) { return; }
        $hub = $registry[ $hub_id ];
        $hotels = self::get_hotels( $hub_id, $preview ? 'candidate' : 'public' );
        ?>
        <main class="sthi-hub" data-sthi-hub="<?php echo esc_attr( $hub_id ); ?>">
            <?php if ( $preview ) : ?>
                <div class="sthi-hub-preview-ribbon">H7 Önizleme • Editör Only • Noindex • H9 public activation locked</div>
            <?php endif; ?>
            <div class="sthi-hub-shell">
                <nav class="sthi-hub-breadcrumb" aria-label="Breadcrumb">
                    <a href="<?php echo esc_url( home_url( '/' ) ); ?>">Ana Sayfa</a><span>/</span><span aria-current="page"><?php echo esc_html( $hub['title'] ); ?></span>
                </nav>
                <header class="sthi-hub-hero">
                    <div>
                        <span class="sthi-hub-kicker"><?php echo esc_html( $hub['kicker'] ); ?></span>
                        <h1><?php echo esc_html( $hub['title'] ); ?></h1>
                        <p><?php echo esc_html( $hub['description'] ); ?></p>
                    </div>
                    <div class="sthi-hub-count"><strong><?php echo esc_html( count( $hotels ) ); ?></strong><span><?php echo $preview ? 'candidate otel' : 'otel'; ?></span></div>
                </header>

                <?php if ( empty( $hotels ) ) : ?>
                    <section class="sthi-hub-empty" aria-live="polite">
                        <h2>Henüz yayınlanabilir otel bulunmuyor</h2>
                        <p><?php echo $preview ? 'Bu hub yalnızca Public Ready gate’i geçen Hotel Entity kayıtlarını gösterir.' : 'Doğrulanmış otel kayıtları hazır olduğunda bu alan otomatik güncellenir.'; ?></p>
                    </section>
                <?php else : ?>
                    <section class="sthi-hub-list" aria-labelledby="sthi-hub-list-title">
                        <div class="sthi-hub-section-head">
                            <div><span>DOĞRULANMIŞ ENVANTER</span><h2 id="sthi-hub-list-title"><?php echo esc_html( $hub['title'] ); ?> listesi</h2></div>
                            <p>Liste Hotel Entity verisinden otomatik oluşur; ayrı bir manuel otel listesi tutulmaz.</p>
                        </div>
                        <div class="sthi-hub-grid">
                            <?php foreach ( $hotels as $hotel ) { self::render_card( $hotel, $preview ); } ?>
                        </div>
                    </section>
                <?php endif; ?>
                <section class="sthi-hub-footnote">
                    <h2>Server Turizm Hotel Intelligence</h2>
                    <p>Otel bilgileri yapılandırılmış Hotel Entity kayıtlarından üretilir. Doğrulanmamış bilgiler veya tahmini ilişkiler Hub listesine eklenmez.</p>
                    <a href="<?php echo esc_url( home_url( '/umre-1/' ) ); ?>">Güncel Umre programlarını inceleyin <span aria-hidden="true">→</span></a>
                </section>
            </div>
        </main>
        <?php
    }

    private static function render_card( $hotel, $preview ) {
        $post_id = (int) $hotel['post_id'];
        $img = self::primary_image( $post_id );
        $url = $preview ? STHI_Frontend::stable_pilot_url( $post_id ) : self::public_hotel_url( $post_id );
        ?>
        <article class="sthi-hub-card" data-hotel-id="<?php echo esc_attr( $hotel['hotel_id'] ); ?>">
            <div class="sthi-hub-card-media">
                <?php if ( $img['url'] ) : ?>
                    <img src="<?php echo esc_url( $img['url'] ); ?>" alt="<?php echo esc_attr( $hotel['name'] . ' — ' . $hotel['city'] ); ?>" loading="lazy" decoding="async"<?php echo $img['width'] ? ' width="' . esc_attr( $img['width'] ) . '"' : ''; ?><?php echo $img['height'] ? ' height="' . esc_attr( $img['height'] ) . '"' : ''; ?>>
                <?php endif; ?>
                <span class="sthi-hub-verified">Doğrulanmış veri</span>
            </div>
            <div class="sthi-hub-card-body">
                <div class="sthi-hub-card-meta"><span><?php echo esc_html( implode( ' / ', array_filter( array( $hotel['district'], $hotel['city'] ) ) ) ); ?></span><?php if ( $hotel['stars'] ) : ?><span><?php echo esc_html( str_repeat( '★', $hotel['stars'] ) ); ?></span><?php endif; ?></div>
                <h3><?php echo esc_html( $hotel['name'] ); ?></h3>
                <?php if ( $hotel['summary'] ) : ?><p><?php echo esc_html( $hotel['summary'] ); ?></p><?php endif; ?>
                <?php if ( $url ) : ?><a class="sthi-hub-card-link" href="<?php echo esc_url( $url ); ?>"><?php echo $preview ? 'Otel önizlemesini aç' : 'Otel detaylarını incele'; ?> <span aria-hidden="true">→</span></a><?php endif; ?>
                <?php if ( $preview ) : ?><small><?php echo esc_html( $hotel['hotel_id'] ); ?> • workflow=published • verification=verified</small><?php endif; ?>
            </div>
        </article>
        <?php
    }

    public static function get_hotels( $hub_id, $mode = 'candidate' ) {
        $registry = self::registry();
        if ( ! isset( $registry[ $hub_id ] ) ) { return array(); }
        $aliases = array_map( array( __CLASS__, 'normalize_city' ), (array) $registry[ $hub_id ]['city_aliases'] );
        $ids = get_posts( array(
            'post_type'      => 'sthi_hotel',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'fields'         => 'ids',
            'orderby'        => array( 'menu_order' => 'ASC', 'title' => 'ASC' ),
            'no_found_rows'  => true,
        ) );
        $out = array();
        foreach ( $ids as $post_id ) {
            $city = trim( (string) get_post_meta( $post_id, '_sthi_city', true ) );
            if ( 'all' !== $hub_id ) {
                $city_key = class_exists( 'STHI_Geography' ) ? STHI_Geography::city_key( $city ) : self::normalize_city( $city );
                $wanted_key = ( 'mekke' === $hub_id ) ? 'makkah' : ( ( 'medine' === $hub_id ) ? 'madinah' : '' );
                if ( $wanted_key ) {
                    if ( $city_key !== $wanted_key ) { continue; }
                } elseif ( ! in_array( self::normalize_city( $city ), $aliases, true ) ) {
                    continue;
                }
            }
            if ( ! class_exists( 'STHI_Frontend' ) || ! STHI_Frontend::is_public_ready( $post_id ) ) { continue; }
            if ( 'public' === $mode && ! self::public_hotel_url( $post_id ) ) { continue; }
            $out[] = self::hotel_record( $post_id );
        }
        return $out;
    }

    private static function hotel_record( $post_id ) {
        $name = trim( (string) get_post_meta( $post_id, '_sthi_official_name', true ) );
        if ( ! $name ) { $name = get_the_title( $post_id ); }
        return array(
            'post_id'  => (int) $post_id,
            'hotel_id' => trim( (string) get_post_meta( $post_id, '_sthi_hotel_id', true ) ),
            'name'     => $name,
            'city'     => trim( (string) get_post_meta( $post_id, '_sthi_city', true ) ),
            'district' => trim( (string) get_post_meta( $post_id, '_sthi_district', true ) ),
            'stars'    => class_exists( 'STHI_Frontend' ) ? STHI_Frontend::verified_star_rating( $post_id ) : 0,
            'summary'  => trim( (string) get_post_meta( $post_id, '_sthi_editorial_summary_tr', true ) ),
        );
    }

    private static function primary_image( $post_id ) {
        $result = array( 'url' => '', 'width' => 0, 'height' => 0 );
        if ( ! class_exists( 'STHI_Media' ) ) { return $result; }
        $items = STHI_Media::get_display_items( $post_id );
        if ( empty( $items ) || empty( $items[0]['url'] ) ) { return $result; }
        $result['url'] = esc_url_raw( $items[0]['url'] );
        if ( ! empty( $items[0]['id'] ) ) {
            $meta = wp_get_attachment_metadata( absint( $items[0]['id'] ) );
            if ( is_array( $meta ) ) {
                $result['width'] = absint( $meta['width'] ?? 0 );
                $result['height'] = absint( $meta['height'] ?? 0 );
            }
        }
        return $result;
    }

    private static function public_hotel_url( $post_id ) {
        $indexable = (bool) apply_filters( 'sthi_hotel_link_target_is_indexable', false, $post_id );
        $routable  = (bool) apply_filters( 'sthi_hotel_link_target_is_routable', false, $post_id );
        if ( ! $indexable && ! $routable ) { return ''; }

        $url = trim( (string) apply_filters( 'sthi_hotel_public_url', '', $post_id ) );
        if ( ! $url && $indexable ) {
            $url = trim( (string) apply_filters( 'sthi_hotel_canonical_url', '', $post_id ) );
        }
        return $url ? esc_url_raw( $url ) : '';
    }

    private static function normalize_city( $value ) {
        $value = remove_accents( strtolower( trim( (string) $value ) ) );
        $value = preg_replace( '/\s+/u', ' ', $value );
        return $value;
    }
}
STHI_Hubs::init();
