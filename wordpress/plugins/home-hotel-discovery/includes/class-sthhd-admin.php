<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

final class STHHD_Admin {
    const MENU_SLUG = 'sthhd-home-hotel-discovery';

    public static function init() {
        add_action( 'admin_menu', array( __CLASS__, 'menu' ), 50 );
        add_action( 'admin_menu', array( __CLASS__, 'cleanup_duplicate_top_level' ), 999 );
        add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue' ) );
        add_action( 'admin_post_sthhd_save_selection', array( __CLASS__, 'save_selection' ) );
        add_action( 'admin_post_sthhd_download_evidence', array( __CLASS__, 'download_evidence' ) );
    }

    public static function menu() {
        $parent = self::detect_hotel_intelligence_parent();

        if ( $parent ) {
            add_submenu_page(
                $parent,
                'Ana Sayfa Otel Keşfi',
                'Homepage Hotel Discovery',
                'manage_options',
                self::MENU_SLUG,
                array( __CLASS__, 'page' )
            );
            return;
        }

        // Fail-visible fallback: never leave the admin page unreachable merely
        // because Hotel Intelligence changes its top-level menu slug.
        add_menu_page(
            'Ana Sayfa Otel Keşfi',
            'Hotel Discovery',
            'manage_options',
            self::MENU_SLUG,
            array( __CLASS__, 'page' ),
            'dashicons-building',
            58.7
        );
    }

    public static function cleanup_duplicate_top_level() {
        // If Hotel Intelligence is present, keep H12F only as its submenu.
        // This also cleans up any duplicate top-level fallback left by an
        // earlier menu-resolution pass in the same admin request.
        if ( self::detect_hotel_intelligence_parent() ) {
            remove_menu_page( self::MENU_SLUG );
        }
    }

    private static function detect_hotel_intelligence_parent() {
        global $menu;

        if ( is_array( $menu ) ) {
            foreach ( $menu as $item ) {
                if ( ! is_array( $item ) || empty( $item[0] ) || empty( $item[2] ) ) { continue; }
                $label = strtolower( trim( wp_strip_all_tags( (string) $item[0] ) ) );
                if ( false !== strpos( $label, 'hotel intelligence' ) ) {
                    return (string) $item[2];
                }
            }
        }

        // Historical/fallback CPT parent. If the current Hotel Intelligence
        // build does not expose that parent, add_submenu_page would be hidden,
        // so callers must use the top-level fallback instead.
        return '';
    }

    public static function enqueue( $hook ) {
        if ( false === strpos( (string) $hook, self::MENU_SLUG ) ) { return; }
        wp_enqueue_style( 'sthhd-admin', STHHD_URL . 'assets/admin.css', array(), STHHD_VERSION );
    }

    public static function page() {
        if ( ! current_user_can( 'manage_options' ) ) { wp_die( 'Forbidden' ); }
        $hotels = STHHD_Repository::launch_ready_hotels();
        $selected = get_option( STHHD_Plugin::OPTION_SELECTED_IDS, array() );
        $selected = is_array( $selected ) ? $selected : array();
        $order = get_option( STHHD_Plugin::OPTION_ORDER, array() );
        $order = is_array( $order ) ? $order : array();
        $dependency = STHHD_Plugin::dependency_ready();
        $publication = class_exists( 'STHI_Public_Routes' ) && is_callable( array( 'STHI_Public_Routes', 'publication_enabled' ) )
            ? (bool) STHI_Public_Routes::publication_enabled()
            : false;
        ?>
        <div class="wrap sthhd-admin">
            <h1>H12F · Homepage Hotel Discovery</h1>
            <p class="sthhd-admin__intro">Hotel Intelligence verisini ikinci kez kopyalamadan, ana sayfa için premium otel keşif katmanını hazırlayan güvenli preview adımı.</p>

            <?php if ( isset( $_GET['sthhd_saved'] ) ) : ?>
                <div class="notice notice-success is-dismissible"><p>Seçim kaydedildi. Public Master Lock hâlâ KAPALI.</p></div>
            <?php endif; ?>

            <div class="sthhd-admin__status-grid">
                <div><span>Plugin</span><strong>v<?php echo esc_html( STHHD_VERSION ); ?></strong></div>
                <div><span>Hotel Intelligence</span><strong><?php echo $dependency ? 'DETECTED' : 'MISSING'; ?></strong></div>
                <div><span>Hotel Publication</span><strong><?php echo $publication ? 'ENABLED' : 'LOCKED'; ?></strong></div>
                <div><span>Launch-ready inventory</span><strong><?php echo esc_html( count( $hotels ) ); ?> HOTEL</strong></div>
                <div><span>Selected</span><strong><?php echo esc_html( count( array_intersect( $selected, array_keys( $hotels ) ) ) ); ?> HOTEL</strong></div>
                <div class="is-locked"><span>Homepage Public Master</span><strong>KAPALI</strong></div>
            </div>

            <div class="sthhd-admin__lock-note">
                <strong>v0.1.3 POLISH + MOBILE-PREP PREVIEW</strong>
                <p>Public açma butonu kasıtlı olarak yoktur. Preview, ana sayfa Raw HTML'ini değiştirmeden Hac duyurusu ile Kültür Turları arasına yerleştirilir. Şehir adları sadece sunum katmanında Türkçeleştirilir; Stable ID ve Hotel Intelligence verisi değiştirilmez.</p>
            </div>

            <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                <input type="hidden" name="action" value="sthhd_save_selection" />
                <?php wp_nonce_field( 'sthhd_save_selection' ); ?>

                <div class="sthhd-admin__toolbar">
                    <div>
                        <h2>Seçili Oteller</h2>
                        <p>Sadece Stable Hotel ID kaydedilir. Otel adı, şehir, görsel ve public URL canlı Hotel Intelligence verisinden okunur.</p>
                    </div>
                    <div class="sthhd-admin__actions">
                        <button type="button" class="button" onclick="document.querySelectorAll('[data-sthhd-select]').forEach(el=>el.checked=true)">9’unu seç</button>
                        <button type="button" class="button" onclick="document.querySelectorAll('[data-sthhd-select]').forEach(el=>el.checked=false)">Seçimi temizle</button>
                    </div>
                </div>

                <div class="sthhd-admin__hotel-list">
                    <?php foreach ( $hotels as $hotel_id => $hotel ) :
                        $checked = in_array( $hotel_id, $selected, true );
                        $position = isset( $order[ $hotel_id ] ) ? intval( $order[ $hotel_id ] ) : 999;
                        ?>
                        <label class="sthhd-admin__hotel-row">
                            <input data-sthhd-select type="checkbox" name="hotel_ids[]" value="<?php echo esc_attr( $hotel_id ); ?>" <?php checked( $checked ); ?> />
                            <div class="sthhd-admin__thumb"><img src="<?php echo esc_url( $hotel['image_url'] ); ?>" alt="" /></div>
                            <div class="sthhd-admin__hotel-main">
                                <code><?php echo esc_html( $hotel_id ); ?></code>
                                <strong><?php echo esc_html( $hotel['name'] ); ?></strong>
                                <span><?php echo esc_html( implode( ' · ', array_filter( array( $hotel['district'], $hotel['city'] ) ) ) ); ?></span>
                            </div>
                            <div class="sthhd-admin__hotel-side">
                                <?php if ( $hotel['stars'] ) : ?><span><?php echo esc_html( str_repeat( '★', $hotel['stars'] ) ); ?></span><?php endif; ?>
                                <label>Sıra <input type="number" min="1" max="99" name="hotel_order[<?php echo esc_attr( $hotel_id ); ?>]" value="<?php echo esc_attr( 999 === $position ? '' : $position ); ?>" /></label>
                            </div>
                        </label>
                    <?php endforeach; ?>
                </div>

                <p class="submit"><button class="button button-primary button-hero" type="submit">Seçimi kaydet / PUBLIC KAPALI tut</button></p>
            </form>

            <div class="sthhd-admin__preview-actions">
                <a class="button button-secondary" target="_blank" rel="noopener" href="<?php echo esc_url( STHHD_Plugin::preview_url() ); ?>">Ana sayfada ADMIN PREVIEW aç ↗</a>
                <a class="button" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=sthhd_download_evidence' ), 'sthhd_download_evidence' ) ); ?>">Read-only evidence indir</a>
            </div>
        </div>
        <?php
    }

    public static function save_selection() {
        if ( ! current_user_can( 'manage_options' ) ) { wp_die( 'Forbidden' ); }
        check_admin_referer( 'sthhd_save_selection' );

        $available = STHHD_Repository::launch_ready_hotels();
        $submitted = isset( $_POST['hotel_ids'] ) && is_array( $_POST['hotel_ids'] ) ? wp_unslash( $_POST['hotel_ids'] ) : array();
        $clean = array();
        foreach ( $submitted as $hotel_id ) {
            $hotel_id = sanitize_text_field( $hotel_id );
            if ( isset( $available[ $hotel_id ] ) ) { $clean[] = $hotel_id; }
        }
        $clean = array_values( array_unique( $clean ) );

        $submitted_order = isset( $_POST['hotel_order'] ) && is_array( $_POST['hotel_order'] ) ? wp_unslash( $_POST['hotel_order'] ) : array();
        $order = array();
        foreach ( $clean as $hotel_id ) {
            $value = isset( $submitted_order[ $hotel_id ] ) ? absint( $submitted_order[ $hotel_id ] ) : 999;
            $order[ $hotel_id ] = $value ?: 999;
        }

        update_option( STHHD_Plugin::OPTION_SELECTED_IDS, $clean, false );
        update_option( STHHD_Plugin::OPTION_ORDER, $order, false );
        update_option( STHHD_Plugin::OPTION_PUBLIC, false, false );

        wp_safe_redirect( add_query_arg( array( 'page' => self::MENU_SLUG, 'sthhd_saved' => '1' ), admin_url( 'admin.php' ) ) );
        exit;
    }

    public static function download_evidence() {
        if ( ! current_user_can( 'manage_options' ) ) { wp_die( 'Forbidden' ); }
        check_admin_referer( 'sthhd_download_evidence' );

        $selected = STHHD_Repository::selected_hotels();
        $inventory = STHHD_Repository::launch_ready_hotels();
        $rows = array();
        foreach ( $selected as $hotel ) {
            $rows[ $hotel['hotel_id'] ] = array(
                'post_id'       => $hotel['post_id'],
                'city'          => $hotel['city'],
                'stars'         => $hotel['stars'],
                'public_url'    => $hotel['url'],
                'image_url_sha' => hash( 'sha256', $hotel['image_url'] ),
                'post_modified' => $hotel['modified'],
            );
        }

        $data = array(
            'version'                    => STHHD_VERSION,
            'captured_at'                => current_time( DATE_W3C, true ),
            'mode'                       => 'admin_preview_only',
            'homepage_public_master'     => false,
            'hotel_intelligence_detected'=> STHHD_Plugin::dependency_ready(),
            'launch_ready_count'         => count( $inventory ),
            'selected_ids'               => array_values( array_map( function( $row ) { return $row['hotel_id']; }, $selected ) ),
            'selected_count'             => count( $selected ),
            'placement_strategy'         => 'before_culture_tours',
            'placement_anchor'           => 'culture-tours-section',
            'visual_revision'            => 'v0.1.2_visual_integration',
            'public_enable_control'       => false,
            'selected_inventory'         => $rows,
        );

        $filename = 'sthhd-evidence-' . gmdate( 'Ymd-His' ) . '.json';
        nocache_headers();
        header( 'Content-Type: application/json; charset=utf-8' );
        header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
        echo wp_json_encode( $data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
        exit;
    }
}
