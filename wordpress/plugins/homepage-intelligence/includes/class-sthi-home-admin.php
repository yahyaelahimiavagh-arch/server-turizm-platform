<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

final class STHI_Home_Admin {
    const MENU_SLUG = 'sthi-homepage-intelligence';

    public static function init() {
        add_action( 'admin_menu', array( __CLASS__, 'menu' ), 40 );
        add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue' ) );
        add_action( 'admin_post_sthi_home_save', array( __CLASS__, 'save' ) );
        add_action( 'admin_post_sthi_home_evidence', array( __CLASS__, 'evidence' ) );
    }

    public static function menu() {
        add_menu_page(
            'Homepage Intelligence',
            'Homepage Intelligence',
            'manage_options',
            self::MENU_SLUG,
            array( __CLASS__, 'page' ),
            'dashicons-layout',
            26
        );
    }

    public static function enqueue( $hook ) {
        if ( false === strpos( (string) $hook, self::MENU_SLUG ) ) { return; }
        wp_enqueue_media();
        wp_enqueue_style( 'sthi-home-admin', STHI_HOME_URL . 'assets/admin.css', array(), STHI_HOME_VERSION );
        wp_enqueue_script( 'sthi-home-admin', STHI_HOME_URL . 'assets/admin.js', array( 'jquery' ), STHI_HOME_VERSION, true );
        wp_localize_script( 'sthi-home-admin', 'STHI_HOME_ADMIN', array(
            'maxItems' => 10,
            'choose'   => 'Görsel seç',
            'use'      => 'Bu görseli kullan',
        ) );
    }

    private static function val( $arr, $key, $default = '' ) {
        return isset( $arr[ $key ] ) ? $arr[ $key ] : $default;
    }

    private static function status_counts( $issues ) {
        $counts = array( 'P0' => 0, 'P1' => 0, 'P2' => 0 );
        foreach ( $issues as $issue ) {
            if ( isset( $counts[ $issue['severity'] ] ) ) { $counts[ $issue['severity'] ]++; }
        }
        return $counts;
    }

    private static function render_settings( $prefix, $settings, $context ) {
        $is_tours = 'tour' === $context;
        ?>
        <div class="sthi-home-settings-grid">
            <label><span>Section</span><select name="<?php echo esc_attr( $prefix ); ?>[enabled]"><option value="1" <?php selected( ! empty( $settings['enabled'] ) ); ?>>AÇIK</option><option value="0" <?php selected( empty( $settings['enabled'] ) ); ?>>KAPALI</option></select></label>
            <?php if ( $is_tours ) : ?>
                <label class="wide"><span>H2 başlık</span><input type="text" name="<?php echo esc_attr( $prefix ); ?>[title]" value="<?php echo esc_attr( self::val( $settings, 'title', 'Kültür Turları' ) ); ?>" /></label>
                <label class="wide"><span>Kısa açıklama (opsiyonel)</span><input type="text" name="<?php echo esc_attr( $prefix ); ?>[intro]" value="<?php echo esc_attr( self::val( $settings, 'intro' ) ); ?>" /></label>
            <?php endif; ?>
            <label><span>Grid</span><select name="<?php echo esc_attr( $prefix ); ?>[layout_mode]"><option value="auto" <?php selected( self::val( $settings, 'layout_mode', 'auto' ), 'auto' ); ?>>AUTO</option><option value="manual" <?php selected( self::val( $settings, 'layout_mode' ), 'manual' ); ?>>MANUAL</option></select></label>
            <label><span>Manual sütun</span><select name="<?php echo esc_attr( $prefix ); ?>[columns]">
                <?php for ( $i = 1; $i <= 5; $i++ ) : ?><option value="<?php echo $i; ?>" <?php selected( absint( self::val( $settings, 'columns', 2 ) ), $i ); ?>><?php echo $i; ?></option><?php endfor; ?>
            </select></label>
            <label><span>Yerleşim yönü</span><select name="<?php echo esc_attr( $prefix ); ?>[direction]"><option value="ltr" <?php selected( self::val( $settings, 'direction', 'ltr' ), 'ltr' ); ?>>Soldan → sağa</option><option value="rtl" <?php selected( self::val( $settings, 'direction' ), 'rtl' ); ?>>Sağdan → sola</option></select></label>
            <label><span>Doldurma</span><select name="<?php echo esc_attr( $prefix ); ?>[flow]"><option value="row" <?php selected( self::val( $settings, 'flow', 'row' ), 'row' ); ?>>Satır satır</option><option value="column" <?php selected( self::val( $settings, 'flow' ), 'column' ); ?>>Sütun sütun</option></select></label>
            <label><span>Maksimum kart</span><select name="<?php echo esc_attr( $prefix ); ?>[max_items]">
                <?php for ( $i = 1; $i <= 10; $i++ ) : ?><option value="<?php echo $i; ?>" <?php selected( absint( self::val( $settings, 'max_items', 10 ) ), $i ); ?>><?php echo $i; ?></option><?php endfor; ?>
            </select></label>
            <?php if ( $is_tours ) : ?><label><span>Veri kaynağı</span><select disabled><option>Manual (şimdi)</option><option>Tours Intelligence (gelecek)</option></select><input type="hidden" name="<?php echo esc_attr( $prefix ); ?>[source_mode]" value="manual" /></label><?php endif; ?>
        </div>
        <?php
    }

    private static function render_item( $type, $index, $item ) {
        $name = $type . '_items[' . $index . ']';
        $is_tour = 'tour' === $type;
        $image_url = (string) self::val( $item, 'image_url' );
        ?>
        <article class="sthi-home-item" data-sthi-item>
            <header>
                <strong><?php echo $is_tour ? 'Tur kartı' : 'Kampanya görseli'; ?> #<?php echo intval( $index + 1 ); ?></strong>
                <div><label class="sthi-home-switch"><input type="checkbox" name="<?php echo esc_attr( $name ); ?>[enabled]" value="1" <?php checked( ! empty( $item['enabled'] ) ); ?> /> Aktif</label></div>
            </header>
            <input type="hidden" name="<?php echo esc_attr( $name ); ?>[id]" value="<?php echo esc_attr( self::val( $item, 'id', $type . '-' . ( $index + 1 ) ) ); ?>" />
            <input type="hidden" data-sthi-attachment-id name="<?php echo esc_attr( $name ); ?>[attachment_id]" value="<?php echo absint( self::val( $item, 'attachment_id', 0 ) ); ?>" />
            <input type="hidden" data-sthi-image-url name="<?php echo esc_attr( $name ); ?>[image_url]" value="<?php echo esc_attr( $image_url ); ?>" />

            <div class="sthi-home-item__body">
                <div class="sthi-home-media-box">
                    <div class="sthi-home-media-preview" data-sthi-media-preview><?php if ( $image_url ) : ?><img src="<?php echo esc_url( $image_url ); ?>" alt="" /><?php else : ?><span>Görsel yok</span><?php endif; ?></div>
                    <div class="sthi-home-media-actions">
                        <button type="button" class="button" data-sthi-choose-media>Görsel seç / değiştir</button>
                        <button type="button" class="button button-link-delete" data-sthi-remove-media <?php disabled( ! $image_url && ! absint( self::val( $item, 'attachment_id', 0 ) ) ); ?>>Görseli kaldır</button>
                    </div>
                    <p class="sthi-home-media-help">Görsel kaldırıldığında kart otomatik olarak pasifleşir; başlık, ALT ve bağlantı alanları korunur.</p>
                </div>
                <div class="sthi-home-fields">
                    <?php if ( $is_tour ) : ?>
                        <label><span>Görünen başlık *</span><input type="text" name="<?php echo esc_attr( $name ); ?>[title]" value="<?php echo esc_attr( self::val( $item, 'title' ) ); ?>" /></label>
                    <?php else : ?>
                        <label><span>Yönetim etiketi</span><input type="text" name="<?php echo esc_attr( $name ); ?>[title]" value="<?php echo esc_attr( self::val( $item, 'title' ) ); ?>" /></label>
                    <?php endif; ?>
                    <label><span>Görsel ALT metni * (SEO)</span><input type="text" name="<?php echo esc_attr( $name ); ?>[alt]" value="<?php echo esc_attr( self::val( $item, 'alt' ) ); ?>" /></label>
                    <?php if ( ! $is_tour ) : ?>
                        <label class="wide"><span>Tıklama davranışı</span><select data-sthi-action-mode name="<?php echo esc_attr( $name ); ?>[action_mode]"><option value="lightbox" <?php selected( self::val( $item, 'action_mode', 'lightbox' ), 'lightbox' ); ?>>Görsel galerisi / Lightbox</option><option value="link" <?php selected( self::val( $item, 'action_mode', 'lightbox' ), 'link' ); ?>>Tüm görsel Hedef URL'ye gitsin</option><option value="none" <?php selected( self::val( $item, 'action_mode', 'lightbox' ), 'none' ); ?>>Tıklama yok</option></select></label>
                        <p class="wide sthi-home-link-help" data-sthi-link-help>Lightbox modunda görsel büyür; aşağıdaki URL doluysa ayrıca küçük, crawlable bir bağlantı düğmesi görünür. Doğrudan Link modunda ise görselin tamamı bu adrese gider.</p>
                    <?php endif; ?>
                    <label class="wide" <?php if ( ! $is_tour ) : ?>data-sthi-link-field<?php endif; ?>><span>Hedef URL <?php echo $is_tour ? '*' : '(opsiyonel; Lightbox’ta ayrı CTA, Link modunda zorunlu)'; ?></span><input type="text" inputmode="url" name="<?php echo esc_attr( $name ); ?>[link_url]" value="<?php echo esc_attr( self::val( $item, 'link_url' ) ); ?>" placeholder="https://www.serverturizm.com.tr/...  veya  /sayfa/" /></label>
                    <?php if ( $is_tour ) : ?>
                        <label><span>Buton metni</span><input type="text" name="<?php echo esc_attr( $name ); ?>[button_text]" value="<?php echo esc_attr( self::val( $item, 'button_text', 'Detaylar' ) ); ?>" /></label>
                    <?php else : ?>
                        <label data-sthi-link-field><span>Bağlantı metni</span><input type="text" name="<?php echo esc_attr( $name ); ?>[link_label]" value="<?php echo esc_attr( self::val( $item, 'link_label' ) ); ?>" placeholder="Detayları aç" /></label>
                    <?php endif; ?>
                    <label <?php if ( ! $is_tour ) : ?>data-sthi-link-field<?php endif; ?>><span>Açılış</span><select name="<?php echo esc_attr( $name ); ?>[target]"><option value="_self" <?php selected( self::val( $item, 'target', '_self' ), '_self' ); ?>>Aynı sekme</option><option value="_blank" <?php selected( self::val( $item, 'target' ), '_blank' ); ?>>Yeni sekme</option></select></label>
                    <label><span>Sıra</span><input type="number" min="1" max="99" name="<?php echo esc_attr( $name ); ?>[order]" value="<?php echo esc_attr( absint( self::val( $item, 'order', $index + 1 ) ) ); ?>" /></label>
                    <label><span>Başlangıç (site saati, opsiyonel)</span><input type="datetime-local" name="<?php echo esc_attr( $name ); ?>[start_at]" value="<?php echo esc_attr( self::val( $item, 'start_at' ) ); ?>" /></label>
                    <label><span>Bitiş (site saati, opsiyonel)</span><input type="datetime-local" name="<?php echo esc_attr( $name ); ?>[end_at]" value="<?php echo esc_attr( self::val( $item, 'end_at' ) ); ?>" /></label>
                </div>
            </div>
        </article>
        <?php
    }

    private static function render_image_control( $prefix, $settings, $id_key, $url_key, $label ) {
        $aid = absint( self::val( $settings, $id_key, 0 ) );
        $url = (string) self::val( $settings, $url_key, '' );
        ?>
        <div class="sthi-home-global-media" data-sthi-item>
            <input type="hidden" data-sthi-attachment-id name="<?php echo esc_attr( $prefix . '[' . $id_key . ']' ); ?>" value="<?php echo $aid; ?>" />
            <input type="hidden" data-sthi-image-url name="<?php echo esc_attr( $prefix . '[' . $url_key . ']' ); ?>" value="<?php echo esc_attr( $url ); ?>" />
            <div class="sthi-home-global-media__preview" data-sthi-media-preview><?php if ( $url ) : ?><img src="<?php echo esc_url( $url ); ?>" alt="" /><?php else : ?><span>Görsel yok</span><?php endif; ?></div>
            <div>
                <strong><?php echo esc_html( $label ); ?></strong>
                <p>Media Library kullanılır; gerçek attachment seçildiğinde responsive WordPress image verisi korunur.</p>
                <button type="button" class="button" data-sthi-choose-media>Görsel seç / değiştir</button>
                <button type="button" class="button button-link-delete" data-sthi-remove-media <?php disabled( ! $aid && ! $url ); ?>>Görseli kaldır</button>
            </div>
        </div>
        <?php
    }

    private static function render_hero_links( $items ) {
        $icons = array(
            'kaaba' => 'Kaaba / Hac', 'mosque' => 'Cami / Umre', 'globe' => 'Dünya / Kültür', 'car' => 'Araç',
            'bed' => 'Otel', 'plane' => 'Uçak', 'passport' => 'Vize', 'map' => 'Harita',
        );
        while ( count( $items ) < 6 ) { $items[] = array( 'id' => 'hero-link-' . ( count( $items ) + 1 ), 'enabled' => 0, 'order' => count( $items ) + 1 ); }
        ?>
        <div class="sthi-home-hero-links">
            <?php for ( $i = 0; $i < 6; $i++ ) : $item = $items[ $i ]; $name = 'hero_links[' . $i . ']'; ?>
                <article class="sthi-home-hero-link-row">
                    <input type="hidden" name="<?php echo esc_attr( $name ); ?>[id]" value="<?php echo esc_attr( self::val( $item, 'id', 'hero-link-' . ( $i + 1 ) ) ); ?>" />
                    <label class="sthi-home-switch"><input type="checkbox" name="<?php echo esc_attr( $name ); ?>[enabled]" value="1" <?php checked( ! empty( $item['enabled'] ) ); ?> /> Aktif</label>
                    <label><span>Etiket</span><input type="text" name="<?php echo esc_attr( $name ); ?>[label]" value="<?php echo esc_attr( self::val( $item, 'label' ) ); ?>" /></label>
                    <label><span>Hedef URL</span><input type="text" inputmode="url" name="<?php echo esc_attr( $name ); ?>[url]" value="<?php echo esc_attr( self::val( $item, 'url' ) ); ?>" placeholder="/umre-1/" /></label>
                    <label><span>İkon</span><select name="<?php echo esc_attr( $name ); ?>[icon]">
                        <?php foreach ( $icons as $key => $label ) : ?><option value="<?php echo esc_attr( $key ); ?>" <?php selected( self::val( $item, 'icon', 'globe' ), $key ); ?>><?php echo esc_html( $label ); ?></option><?php endforeach; ?>
                    </select></label>
                    <label><span>Sıra</span><input type="number" min="1" max="20" name="<?php echo esc_attr( $name ); ?>[order]" value="<?php echo absint( self::val( $item, 'order', $i + 1 ) ); ?>" /></label>
                </article>
            <?php endfor; ?>
        </div>
        <?php
    }


    private static function ai_payload( $page_settings, $hero_links, $campaign_settings, $tour_settings ) {
        $links = array();
        foreach ( array_slice( is_array( $hero_links ) ? $hero_links : array(), 0, 6 ) as $item ) {
            $links[] = array(
                'enabled' => ! empty( $item['enabled'] ),
                'label'   => (string) ( $item['label'] ?? '' ),
                'url'     => (string) ( $item['url'] ?? '' ),
                'icon'    => (string) ( $item['icon'] ?? 'globe' ),
                'order'   => absint( $item['order'] ?? 1 ),
            );
        }

        return array(
            'schema' => 'sthi-homepage-ai/v1',
            'seo' => array(
                'title'              => (string) ( $page_settings['seo_title'] ?? '' ),
                'meta_description'   => (string) ( $page_settings['meta_description'] ?? '' ),
                'social_title'       => (string) ( $page_settings['social_title'] ?? '' ),
                'social_description' => (string) ( $page_settings['social_description'] ?? '' ),
            ),
            'hero' => array(
                'h1'                  => (string) ( $page_settings['hero_h1'] ?? '' ),
                'intro'               => (string) ( $page_settings['hero_intro'] ?? '' ),
                'search_button_label' => (string) ( $page_settings['search_button_label'] ?? '' ),
                'background' => array(
                    'zoom' => absint( $page_settings['hero_background_zoom'] ?? 100 ),
                    'x'    => absint( $page_settings['hero_background_x'] ?? 50 ),
                    'y'    => absint( $page_settings['hero_background_y'] ?? 50 ),
                ),
                'quick_links' => $links,
            ),
            'featured_umre' => array(
                'title'     => (string) ( $page_settings['umre_section_title'] ?? '' ),
                'cta_label' => (string) ( $page_settings['umre_cta_label'] ?? '' ),
                'cta_url'   => (string) ( $page_settings['umre_cta_url'] ?? '' ),
            ),
            'campaign_grid' => array(
                'enabled'     => ! empty( $campaign_settings['enabled'] ),
                'layout_mode' => (string) ( $campaign_settings['layout_mode'] ?? 'auto' ),
                'columns'     => absint( $campaign_settings['columns'] ?? 2 ),
                'direction'   => (string) ( $campaign_settings['direction'] ?? 'ltr' ),
                'flow'        => (string) ( $campaign_settings['flow'] ?? 'row' ),
                'max_items'   => absint( $campaign_settings['max_items'] ?? 10 ),
            ),
            'culture_tours' => array(
                'enabled'     => ! empty( $tour_settings['enabled'] ),
                'title'       => (string) ( $tour_settings['title'] ?? 'Kültür Turları' ),
                'intro'       => (string) ( $tour_settings['intro'] ?? '' ),
                'layout_mode' => (string) ( $tour_settings['layout_mode'] ?? 'auto' ),
                'columns'     => absint( $tour_settings['columns'] ?? 4 ),
                'direction'   => (string) ( $tour_settings['direction'] ?? 'ltr' ),
                'flow'        => (string) ( $tour_settings['flow'] ?? 'row' ),
                'max_items'   => absint( $tour_settings['max_items'] ?? 10 ),
            ),
        );
    }

    public static function page() {
        if ( ! current_user_can( 'manage_options' ) ) { wp_die( 'Forbidden' ); }
        $page_settings     = STHI_Home_Repository::page_settings();
        $hero_links        = STHI_Home_Repository::hero_links();
        $campaign_settings = STHI_Home_Repository::campaign_settings();
        $campaign_items    = STHI_Home_Repository::campaign_items();
        $tour_settings     = STHI_Home_Repository::tour_settings();
        $tour_items        = STHI_Home_Repository::tour_items();
        $story_settings    = STHI_Home_Repository::story_settings();
        $story_public      = STHI_Home_Plugin::story_public_enabled();
        $issues            = STHI_Home_Repository::seo_audit();
        $counts            = self::status_counts( $issues );
        $ai_payload        = self::ai_payload( $page_settings, $hero_links, $campaign_settings, $tour_settings );
        $public_enabled    = STHI_Home_Plugin::public_enabled();
        $baseline_sha      = STHI_Home_Plugin::baseline_sha256();

        // Always present 10 editable slots while preserving current items.
        while ( count( $campaign_items ) < 10 ) { $campaign_items[] = array( 'id' => 'campaign-' . ( count( $campaign_items ) + 1 ), 'enabled' => 0, 'action_mode' => 'lightbox', 'order' => count( $campaign_items ) + 1 ); }
        while ( count( $tour_items ) < 10 ) { $tour_items[] = array( 'id' => 'tour-' . ( count( $tour_items ) + 1 ), 'enabled' => 0, 'button_text' => 'Detaylar', 'order' => count( $tour_items ) + 1 ); }
        ?>
        <div class="wrap sthi-home-admin">
            <h1>Server Turizm · Homepage Intelligence</h1>
            <p class="sthi-home-lead">SEO-first Homepage Control Center. H13C ile ADMIN PREVIEW artık WPBakery Raw HTML yerine plugin-owned frozen baseline üzerinden render edilir. Public cutover kontrollüdür; eski Raw HTML yalnızca rollback snapshot olarak tutulur.</p>

            <?php if ( isset( $_GET['saved'] ) ) : ?><div class="notice notice-success is-dismissible"><p>Homepage Intelligence kaydedildi. Public Master: <strong><?php echo $public_enabled ? 'AÇIK' : 'KAPALI'; ?></strong>.</p></div><?php endif; ?>
            <?php if ( isset( $_GET['cutover'] ) ) : ?><div class="notice notice-success is-dismissible"><p>Homepage Raw HTML cutover durumu güncellendi: <strong><?php echo $public_enabled ? 'PUBLIC ON · PLUGIN BASELINE' : 'ROLLBACK · WPBAKERY RAW HTML'; ?></strong>.</p></div><?php endif; ?>

            <div class="sthi-home-status">
                <div><span>Plugin</span><strong>v<?php echo esc_html( STHI_HOME_VERSION ); ?></strong></div>
                <div><span>Mode</span><strong><?php echo $public_enabled ? 'PUBLIC · PLUGIN BASELINE' : 'NO-RAW ADMIN PREVIEW'; ?></strong></div>
                <div><span>Public Master</span><strong class="<?php echo $public_enabled ? 'good' : 'bad'; ?>"><?php echo $public_enabled ? 'AÇIK' : 'KAPALI'; ?></strong></div>
                <div><span>Campaign active</span><strong><?php echo count( STHI_Home_Repository::active_campaign_items() ); ?>/10</strong></div>
                <div><span>Culture active</span><strong><?php echo count( STHI_Home_Repository::active_tour_items() ); ?>/10</strong></div>
                <div><span>SEO P0</span><strong class="<?php echo $counts['P0'] ? 'bad' : 'good'; ?>"><?php echo absint( $counts['P0'] ); ?></strong></div>
                <div><span>Journey Evidence</span><strong class="<?php echo $story_public ? 'good' : 'bad'; ?>"><?php echo $story_public ? 'PUBLIC ON' : 'PREVIEW ONLY'; ?></strong></div>
            </div>

            <div class="sthi-home-architecture">
                <strong>SEO OWNERSHIP LOCK</strong>
                <p><b>Homepage SEO title + meta description + social preview</b> ADMIN PREVIEW ve kontrollü PUBLIC CUTOVER içinde bu plugin tarafından yönetilir. Canonical, public robots, sitemap ve Organization schema başka ownerlarda kalır. Hero tek H1 üretir; kart içerikleri SSR HTML, linkler gerçek <code>&lt;a href&gt;</code>, görseller gerçek <code>&lt;img&gt;</code> olarak render edilir.</p>
            </div>

            <nav class="sthi-home-tabs" aria-label="Homepage panel sections">
                <button class="is-active" type="button" data-sthi-tab-button="page">1 · Ana Sayfa</button>
                <button type="button" data-sthi-tab-button="campaigns">2 · Kampanya Grid</button>
                <button type="button" data-sthi-tab-button="tours">3 · Kültür Turları</button>
                <button type="button" data-sthi-tab-button="seo">4 · SEO Gate</button>
                <button type="button" data-sthi-tab-button="integrations">5 · Integrations / Cutover</button>
                <button type="button" data-sthi-tab-button="json">6 · AI / JSON</button>
            </nav>

            <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                <input type="hidden" name="action" value="sthi_home_save" />
                <?php wp_nonce_field( 'sthi_home_save' ); ?>

                <section data-sthi-tab="page" class="sthi-home-tab is-active">
                    <div class="sthi-home-section-head"><div><h2>Homepage Control Center</h2><p>SEO ile Hero içeriğini tek panelden yönetir. Hac duyurusu FROZEN; Hotel Discovery kendi H12F kaynağını, Umre kartları Program Intelligence kaynağını korur.</p></div></div>

                    <div class="sthi-home-control-grid">
                        <article class="sthi-home-control-card">
                            <h3>Hero içerik</h3>
                            <label><span>Hero H1 (satır sonları korunur)</span><textarea name="page_settings[hero_h1]" rows="4"><?php echo esc_textarea( self::val( $page_settings, 'hero_h1' ) ); ?></textarea></label>
                            <label><span>Hero açıklaması</span><textarea name="page_settings[hero_intro]" rows="4"><?php echo esc_textarea( self::val( $page_settings, 'hero_intro' ) ); ?></textarea></label>
                            <label><span>Arama butonu</span><input type="text" name="page_settings[search_button_label]" value="<?php echo esc_attr( self::val( $page_settings, 'search_button_label', 'DETAYLI ARAMA' ) ); ?>" /></label>
                        </article>
                        <article class="sthi-home-control-card">
                            <h3>Öne Çıkan Umre alanı</h3>
                            <p>Program kartlarının verisi kopyalanmaz; sadece Homepage sunum etiketleri yönetilir.</p>
                            <label><span>H2 başlık</span><input type="text" name="page_settings[umre_section_title]" value="<?php echo esc_attr( self::val( $page_settings, 'umre_section_title' ) ); ?>" /></label>
                            <label><span>Alt CTA metni</span><input type="text" name="page_settings[umre_cta_label]" value="<?php echo esc_attr( self::val( $page_settings, 'umre_cta_label' ) ); ?>" /></label>
                            <label><span>Alt CTA URL</span><input type="text" inputmode="url" name="page_settings[umre_cta_url]" value="<?php echo esc_attr( self::val( $page_settings, 'umre_cta_url' ) ); ?>" /></label>
                        </article>
                        <article class="sthi-home-control-card">
                            <h3>Journey Evidence / Güncel</h3>
                            <p>Seçilen yayınlanmış WordPress Post'tan başlık, excerpt, featured image ve permalink dinamik okunur; içerik kopyalanmaz.</p>
                            <label class="sthi-home-check"><input type="checkbox" name="story_settings[enabled]" value="1" <?php checked( ! empty( $story_settings['enabled'] ) ); ?> /> <span>ADMIN PREVIEW'da göster</span></label>
                            <label><span>Kaynak Post ID</span><input type="number" min="1" step="1" name="story_settings[post_id]" value="<?php echo absint( self::val( $story_settings, 'post_id', 6216 ) ); ?>" /></label>
                            <label><span>Eyebrow</span><input type="text" name="story_settings[eyebrow]" value="<?php echo esc_attr( self::val( $story_settings, 'eyebrow', 'YOLCULUKLARIMIZDAN' ) ); ?>" /></label>
                            <label><span>H2 başlık</span><input type="text" name="story_settings[heading]" value="<?php echo esc_attr( self::val( $story_settings, 'heading', 'Özbekistan’dan Bir Hatıra' ) ); ?>" /></label>
                            <label><span>Görsel rozeti</span><input type="text" name="story_settings[badge]" value="<?php echo esc_attr( self::val( $story_settings, 'badge', 'RESMÎ YAYINDA YER ALDI' ) ); ?>" /></label>
                            <label><span>CTA</span><input type="text" name="story_settings[cta_label]" value="<?php echo esc_attr( self::val( $story_settings, 'cta_label', 'HABERİ OKU' ) ); ?>" /></label>
                            <p class="description"><strong>Public:</strong> <?php echo $story_public ? 'AÇIK' : 'KAPALI'; ?> · Public açma/kapama yalnızca Integrations gate'inden yapılır.</p>
                        </article>
                    </div>

                    <h3>Hero arka plan</h3>
                    <?php self::render_image_control( 'page_settings', $page_settings, 'hero_background_id', 'hero_background_url', 'Hero arka plan görseli' ); ?>
                    <div class="sthi-home-background-controls">
                        <div class="sthi-home-number-grid">
                            <label class="sthi-home-number-row">
                                <span><strong>Boyut / Zoom</strong><small>100–180</small></span>
                                <div class="sthi-home-number-input"><input type="number" min="100" max="180" step="1" inputmode="numeric" name="page_settings[hero_background_zoom]" value="<?php echo absint( self::val( $page_settings, 'hero_background_zoom', 100 ) ); ?>" /><b>%</b></div>
                                <small>100 = mevcut <code>cover</code>; daha yüksek değer desktop arka planını yakınlaştırır.</small>
                            </label>
                            <label class="sthi-home-number-row">
                                <span><strong>Yatay pozisyon X</strong><small>0–100</small></span>
                                <div class="sthi-home-number-input"><input type="number" min="0" max="100" step="1" inputmode="numeric" name="page_settings[hero_background_x]" value="<?php echo absint( self::val( $page_settings, 'hero_background_x', 50 ) ); ?>" /><b>%</b></div>
                                <small>0 = sol, 50 = orta, 100 = sağ.</small>
                            </label>
                            <label class="sthi-home-number-row">
                                <span><strong>Dikey pozisyon Y</strong><small>0–100</small></span>
                                <div class="sthi-home-number-input"><input type="number" min="0" max="100" step="1" inputmode="numeric" name="page_settings[hero_background_y]" value="<?php echo absint( self::val( $page_settings, 'hero_background_y', 50 ) ); ?>" /><b>%</b></div>
                                <small>0 = üst, 50 = orta, 100 = alt.</small>
                            </label>
                        </div>
                        <p class="description">Değerleri doğrudan yazabilirsiniz. Sunucu tarafı doğrulama Zoom'u 100–180, X/Y'yi 0–100 aralığında kilitler. Mobil kırpma ayrı responsive gate'te değerlendirilecektir.</p>
                    </div>

                    <h3>Hero hızlı bağlantılar</h3>
                    <p class="description">1–6 gerçek crawlable bağlantı. Menü/Header kopyalanmaz; bunlar yalnızca Hero içindeki kategori butonlarıdır.</p>
                    <?php self::render_hero_links( $hero_links ); ?>
                </section>

                <section data-sthi-tab="campaigns" class="sthi-home-tab">
                    <div class="sthi-home-section-head"><div><h2>Hero Campaign Grid</h2><p>1–10 görsel. AUTO veya 1–5 sütun; soldan/sağdan ve satır/sütun doldurma. Hac duyuru kartına dokunmaz.</p></div></div>
                    <?php self::render_settings( 'campaign_settings', $campaign_settings, 'campaign' ); ?>
                    <div class="sthi-home-items">
                        <?php for ( $i = 0; $i < 10; $i++ ) { self::render_item( 'campaign', $i, $campaign_items[ $i ] ); } ?>
                    </div>
                </section>

                <section data-sthi-tab="tours" class="sthi-home-tab">
                    <div class="sthi-home-section-head"><div><h2>Kültür Turları</h2><p>Şimdilik manual kartlar. Tours Intelligence hazır olduğunda aynı sunum katmanı veri kaynağına bağlanacak; yeniden HTML kurmayacağız.</p></div></div>
                    <?php self::render_settings( 'tour_settings', $tour_settings, 'tour' ); ?>
                    <div class="sthi-home-items">
                        <?php for ( $i = 0; $i < 10; $i++ ) { self::render_item( 'tour', $i, $tour_items[ $i ] ); } ?>
                    </div>
                </section>

                <section data-sthi-tab="seo" class="sthi-home-tab">
                    <div class="sthi-home-section-head"><div><h2>SEO Release Gate</h2><p>Bu sürüm public açılmaz. Aşağıdaki P0'lar temizlenmeden gelecekte public candidate üretilmeyecek.</p></div></div>
                    <div class="sthi-home-control-grid sthi-home-seo-fields">
                        <article class="sthi-home-control-card">
                            <h3>Google / Search</h3>
                            <label><span>SEO title</span><input type="text" name="page_settings[seo_title]" value="<?php echo esc_attr( self::val( $page_settings, 'seo_title' ) ); ?>" data-sthi-count-input data-sthi-count-target="seo-title-count" /></label>
                            <small><span id="seo-title-count"><?php echo mb_strlen( (string) self::val( $page_settings, 'seo_title' ) ); ?></span> karakter · hedef yaklaşık 30–65</small>
                            <label><span>Meta description</span><textarea rows="4" name="page_settings[meta_description]" data-sthi-count-input data-sthi-count-target="meta-desc-count"><?php echo esc_textarea( self::val( $page_settings, 'meta_description' ) ); ?></textarea></label>
                            <small><span id="meta-desc-count"><?php echo mb_strlen( (string) self::val( $page_settings, 'meta_description' ) ); ?></span> karakter · editoryal hedef yaklaşık 80–170</small>
                            <div class="sthi-home-readonly"><strong>Canonical</strong><code><?php echo esc_html( home_url( '/' ) ); ?></code><span>READ-ONLY · self canonical public cutover gate'te doğrulanacak.</span></div>
                        </article>
                        <article class="sthi-home-control-card">
                            <h3>Social preview</h3>
                            <label><span>Social title</span><input type="text" name="page_settings[social_title]" value="<?php echo esc_attr( self::val( $page_settings, 'social_title' ) ); ?>" /></label>
                            <label><span>Social description</span><textarea rows="3" name="page_settings[social_description]"><?php echo esc_textarea( self::val( $page_settings, 'social_description' ) ); ?></textarea></label>
                            <?php self::render_image_control( 'page_settings', $page_settings, 'social_image_id', 'social_image_url', 'Social share görseli' ); ?>
                        </article>
                    </div>

                    <div class="sthi-home-serp-preview">
                        <span class="sthi-home-serp-preview__url"><?php echo esc_html( home_url( '/' ) ); ?></span>
                        <strong><?php echo esc_html( self::val( $page_settings, 'seo_title' ) ); ?></strong>
                        <p><?php echo esc_html( self::val( $page_settings, 'meta_description' ) ); ?></p>
                    </div>

                    <div class="sthi-home-seo-summary">
                        <div><strong><?php echo absint( $counts['P0'] ); ?></strong><span>P0 Blocker</span></div>
                        <div><strong><?php echo absint( $counts['P1'] ); ?></strong><span>P1 Review</span></div>
                        <div><strong><?php echo absint( $counts['P2'] ); ?></strong><span>P2 Hygiene</span></div>
                    </div>
                    <?php if ( $issues ) : ?>
                        <table class="widefat striped"><thead><tr><th>Severity</th><th>Item</th><th>Code</th><th>Problem</th></tr></thead><tbody>
                        <?php foreach ( $issues as $issue ) : ?><tr><td><strong><?php echo esc_html( $issue['severity'] ); ?></strong></td><td><?php echo esc_html( $issue['item'] ); ?></td><td><code><?php echo esc_html( $issue['code'] ); ?></code></td><td><?php echo esc_html( $issue['message'] ); ?></td></tr><?php endforeach; ?>
                        </tbody></table>
                    <?php else : ?><div class="notice notice-success inline"><p>Config-level SEO Gate temiz. Runtime QA yine gereklidir.</p></div><?php endif; ?>
                    <div class="sthi-home-seo-contract">
                        <h3>Non-negotiables</h3>
                        <ul>
                            <li>Content is server-rendered; JS is not required for crawlable content.</li>
                            <li>Every actionable card uses a real <code>&lt;a href&gt;</code>.</li>
                            <li>Every linked image has descriptive ALT text; image ALT can act as anchor context.</li>
                            <li>Media Library attachments use WordPress responsive <code>srcset</code>/<code>sizes</code> automatically.</li>
                            <li>Below-fold images are lazy-loaded; first hero campaign image can be eager/high-priority.</li>
                            <li>No extra H1. Culture section uses H2 → H3 hierarchy.</li>
                            <li>No schema spam: manual cards emit no speculative structured data.</li>
                            <li>Temporal start/end dates prevent stale campaign cards remaining live forever.</li>
                        </ul>
                    </div>
                </section>

                <section data-sthi-tab="integrations" class="sthi-home-tab">
                    <div class="sthi-home-section-head"><div><h2>Integrations / Ownership</h2><p>Homepage tek panel mantığı; fakat source-of-truth sınırları korunur.</p></div></div>
                    <div class="sthi-home-integration-grid">
                        <article><span>Umre Programs</span><strong>Program Intelligence</strong><p>Mevcut Featured Umre alanı bu plugin tarafından kopyalanmaz.</p></article>
                        <article><span>Hac 2027</span><strong>FROZEN / LATER</strong><p>Şimdilik yönetim dışı. Temporal governance ileride ayrı gate ile bağlanacak.</p></article>
                        <article><span>Hotels</span><strong><?php echo STHI_Home_Plugin::h12f_bridge_ready() ? 'H12F BRIDGE READY · ' . absint( STHI_Home_Plugin::h12f_selected_count() ) . ' HOTEL' : 'H12F BRIDGE NOT READY'; ?></strong><p>Homepage yalnızca H12F renderer'ını compose eder; Stable Hotel ID / Hotel Intelligence source-of-truth değişmez.</p></article>
                        <article><span>Tours</span><strong>MANUAL NOW → INTELLIGENCE LATER</strong><p>Bu panelin Tour kartları gelecekte Tours Intelligence adapter'ına geçirilecek.</p></article>
                        <article><span>Journey Evidence</span><strong><?php echo STHI_Home_Plugin::story_ready() ? 'POST READY · #' . absint( self::val( $story_settings, 'post_id', 6216 ) ) : 'NOT READY'; ?></strong><p>Başlık, excerpt, featured image ve URL seçilen WordPress Post'tan canlı okunur. Yeni schema üretmez.</p></article>
                    </div>
                    <div class="sthi-home-cutover-warning">
                        <h3>Journey Evidence · Controlled Public Gate</h3>
                        <p>Bu modül <strong>ADMIN PREVIEW'da</strong> görülebilir; Public Homepage'e ayrıca açılır. Böylece yeni kartı canlı sayfaya körlemesine göndermeden önce görsel/mobile QA yapılır.</p>
                        <p><strong>Kaynak:</strong> WordPress Post #<?php echo absint( self::val( $story_settings, 'post_id', 6216 ) ); ?> · <strong>Durum:</strong> <?php echo STHI_Home_Plugin::story_ready() ? 'READY' : 'NOT READY'; ?> · <strong>Public:</strong> <?php echo $story_public ? 'ON' : 'OFF'; ?></p>
                        <div class="sthi-home-cutover-form">
                            <label><span>Onay</span><input type="text" name="sthi_home_story_cutover_confirm" placeholder="<?php echo $story_public ? 'STORY-OFF' : 'STORY'; ?>" autocomplete="off" /></label>
                            <button type="submit" name="sthi_home_story_cutover_mode" value="<?php echo $story_public ? 'off' : 'on'; ?>" class="button <?php echo $story_public ? '' : 'button-primary'; ?>"><?php echo $story_public ? 'JOURNEY EVIDENCE PUBLIC KAPAT' : 'JOURNEY EVIDENCE PUBLIC AÇ'; ?></button>
                        </div>
                    </div>

                    <div class="sthi-home-cutover-warning">
                        <h3>H13C · Raw HTML Retirement Gate</h3>
                        <p><strong>NO-RAW PREVIEW AKTİF.</strong> ADMIN PREVIEW artık sayfadaki WPBakery Raw HTML'i okumaz; plugin içindeki frozen baseline + panel verileriyle render eder.</p>
                        <p><strong>Baseline SHA-256:</strong> <code><?php echo esc_html( $baseline_sha ); ?></code></p>
                        <ol>
                            <li>NO-RAW Admin Preview parity: görsel + Search + Hac + Featured Umre + H12F + Culture</li>
                            <li>SEO P0 = 0</li>
                            <li>Controlled Public Cutover</li>
                            <li>Canlı sayfada son QA</li>
                            <li>Sonra WPBakery Raw HTML elementi silinebilir; ayrı snapshot dosyası rollback için saklanır.</li>
                        </ol>
                        <div class="sthi-home-cutover-form">
                            <label><span>Onay</span><input type="text" name="sthi_home_cutover_confirm" placeholder="<?php echo $public_enabled ? 'ROLLBACK' : 'CUTOVER'; ?>" autocomplete="off" /></label>
                            <button type="submit" name="sthi_home_cutover_mode" value="<?php echo $public_enabled ? 'off' : 'on'; ?>" class="button <?php echo $public_enabled ? '' : 'button-primary'; ?>"><?php echo $public_enabled ? 'ACİL ROLLBACK · RAW HTML\'YE DÖN' : 'PUBLIC CUTOVER AÇ · PLUGIN BASELINE'; ?></button>
                        </div>
                    </div>
                </section>

                <section data-sthi-tab="json" class="sthi-home-tab">
                    <div class="sthi-home-section-head"><div><h2>AI / JSON Content Import</h2><p>ChatGPT'den tek JSON al, forma uygula, sonra normal Preview/Save gate'inden geçir. JSON hiçbir zaman PUBLIC MASTER, canonical, robots, sitemap, schema veya program/hotel entity verisini değiştiremez.</p></div></div>

                    <div class="sthi-home-json-grid">
                        <article class="sthi-home-control-card">
                            <h3>AI giriş JSON'u</h3>
                            <p class="description">Aşağıdaki schema bilerek sınırlıdır: SEO, Hero metinleri/arka plan konumu, Hero hızlı bağlantılar, Featured Umre etiketleri, Campaign grid ayarları ve Kültür Turları sunum ayarları. Görseller ve entity verileri elle/ayrı source-of-truth üzerinden kalır.</p>
                            <textarea rows="22" class="large-text code" data-sthi-json-input placeholder='{"schema":"sthi-homepage-ai/v1", ...}'></textarea>
                            <div class="sthi-home-json-actions">
                                <button type="button" class="button button-primary" data-sthi-json-apply>JSON'u forma uygula · KAYDETMEZ</button>
                                <button type="button" class="button" data-sthi-json-clear>Temizle</button>
                            </div>
                            <div class="sthi-home-json-status" data-sthi-json-status aria-live="polite"></div>
                        </article>
                        <article class="sthi-home-control-card">
                            <h3>Mevcut ayarlardan AI şablonu</h3>
                            <p class="description">Bunu kopyalayıp ChatGPT'ye verebilirsin. Dönen JSON'u sol kutuya yapıştır. <strong>Kaydetmez</strong>; önce form alanlarını doldurur, sonra sen Preview gate'inden geçirirsin.</p>
                            <textarea rows="22" class="large-text code" readonly data-sthi-json-template><?php echo esc_textarea( wp_json_encode( $ai_payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) ); ?></textarea>
                            <div class="sthi-home-json-actions">
                                <button type="button" class="button" data-sthi-json-copy>Mevcut JSON'u kopyala</button>
                            </div>
                        </article>
                    </div>

                    <div class="sthi-home-json-contract">
                        <h3>JSON Safety Contract</h3>
                        <ul>
                            <li><code>schema</code> tam olarak <code>sthi-homepage-ai/v1</code> olmalıdır.</li>
                            <li>Eksik alan mevcut değeri silmez; yalnızca JSON'da gelen izinli alanlar forma uygulanır.</li>
                            <li>URL alanları yalnızca http(s) veya site-relative <code>/...</code> değer kabul eder.</li>
                            <li>JSON görsel attachment ID'lerini, Campaign/Tour kart içeriklerini, Hac bölümünü, H12F Hotel verisini veya Program Intelligence verisini değiştirmez.</li>
                            <li>Public değişiklik yoktur: mevcut <strong>Kaydet + Full Homepage ADMIN PREVIEW</strong> gate'i zorunludur.</li>
                        </ul>
                    </div>
                </section>

                <div class="sthi-home-savebar">
                    <button class="button button-primary button-hero" type="submit">Kaydet / Public durumu koru</button>
                    <button class="button button-secondary" type="submit" name="sthi_home_after_save" value="preview" formtarget="_blank">Kaydet + Full Homepage ADMIN PREVIEW ↗</button>
                    <a class="button" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=sthi_home_evidence' ), 'sthi_home_evidence' ) ); ?>">Read-only evidence indir</a>
                    <span class="sthi-home-savehint">Preview her zaman formdaki son değişiklikleri önce kaydeder; Public Master durumu ayrıca Cutover Gate üzerinden yönetilir.</span>
                </div>
            </form>
        </div>
        <?php
    }

    private static function sanitize_page_settings( $raw ) {
        $raw = is_array( $raw ) ? $raw : array();
        $d = STHI_Home_Repository::default_page_settings();
        $out = $d;
        foreach ( array( 'seo_title','meta_description','social_title','social_description','hero_h1','hero_intro','search_button_label','umre_section_title','umre_cta_label' ) as $key ) {
            $out[ $key ] = sanitize_textarea_field( $raw[ $key ] ?? $d[ $key ] );
        }
        $out['umre_cta_url'] = esc_url_raw( $raw['umre_cta_url'] ?? $d['umre_cta_url'] );
        $out['hero_background_id'] = absint( $raw['hero_background_id'] ?? 0 );
        $out['hero_background_url'] = esc_url_raw( $raw['hero_background_url'] ?? '' );
        $out['hero_background_zoom'] = max( 100, min( 180, absint( $raw['hero_background_zoom'] ?? $d['hero_background_zoom'] ?? 100 ) ) );
        $out['hero_background_x'] = max( 0, min( 100, absint( $raw['hero_background_x'] ?? $d['hero_background_x'] ?? 50 ) ) );
        $out['hero_background_y'] = max( 0, min( 100, absint( $raw['hero_background_y'] ?? $d['hero_background_y'] ?? 50 ) ) );
        $out['social_image_id'] = absint( $raw['social_image_id'] ?? 0 );
        $out['social_image_url'] = esc_url_raw( $raw['social_image_url'] ?? '' );
        return $out;
    }

    private static function sanitize_story_settings( $raw ) {
        $raw = is_array( $raw ) ? $raw : array();
        $d = STHI_Home_Repository::default_story_settings();
        return array(
            'enabled'   => ! empty( $raw['enabled'] ) ? 1 : 0,
            'post_id'   => absint( $raw['post_id'] ?? $d['post_id'] ),
            'eyebrow'   => sanitize_text_field( $raw['eyebrow'] ?? $d['eyebrow'] ),
            'heading'   => sanitize_text_field( $raw['heading'] ?? $d['heading'] ),
            'badge'     => sanitize_text_field( $raw['badge'] ?? $d['badge'] ),
            'cta_label' => sanitize_text_field( $raw['cta_label'] ?? $d['cta_label'] ),
        );
    }

    private static function sanitize_hero_links( $raw_items ) {
        $raw_items = is_array( $raw_items ) ? $raw_items : array();
        $allowed_icons = array( 'kaaba','mosque','globe','car','bed','plane','passport','map' );
        $out = array();
        foreach ( array_slice( $raw_items, 0, 6 ) as $i => $item ) {
            $item = is_array( $item ) ? $item : array();
            $icon = sanitize_key( $item['icon'] ?? 'globe' );
            if ( ! in_array( $icon, $allowed_icons, true ) ) { $icon = 'globe'; }
            $out[] = array(
                'id' => sanitize_key( $item['id'] ?? 'hero-link-' . ( $i + 1 ) ),
                'enabled' => ! empty( $item['enabled'] ) ? 1 : 0,
                'label' => sanitize_text_field( $item['label'] ?? '' ),
                'url' => esc_url_raw( $item['url'] ?? '' ),
                'icon' => $icon,
                'order' => max( 1, min( 20, absint( $item['order'] ?? $i + 1 ) ) ),
            );
        }
        return $out;
    }

    private static function sanitize_settings( $raw, $defaults, $is_tour = false ) {
        $raw = is_array( $raw ) ? $raw : array();
        $out = $defaults;
        $out['enabled']     = ! empty( $raw['enabled'] ) ? 1 : 0;
        $out['layout_mode'] = isset( $raw['layout_mode'] ) && 'manual' === $raw['layout_mode'] ? 'manual' : 'auto';
        $out['columns']     = max( 1, min( 5, absint( $raw['columns'] ?? $defaults['columns'] ) ) );
        $out['direction']   = isset( $raw['direction'] ) && 'rtl' === $raw['direction'] ? 'rtl' : 'ltr';
        $out['flow']        = isset( $raw['flow'] ) && 'column' === $raw['flow'] ? 'column' : 'row';
        $out['max_items']   = max( 1, min( 10, absint( $raw['max_items'] ?? 10 ) ) );
        if ( $is_tour ) {
            $out['title']       = sanitize_text_field( $raw['title'] ?? 'Kültür Turları' );
            $out['intro']       = sanitize_text_field( $raw['intro'] ?? '' );
            $out['source_mode'] = 'manual';
        }
        return $out;
    }

    private static function sanitize_items( $raw_items, $type ) {
        $raw_items = is_array( $raw_items ) ? $raw_items : array();
        $out = array();
        foreach ( array_slice( $raw_items, 0, 10 ) as $index => $item ) {
            $item = is_array( $item ) ? $item : array();
            $row = array(
                'id'            => sanitize_key( $item['id'] ?? $type . '-' . ( $index + 1 ) ),
                'enabled'       => ! empty( $item['enabled'] ) ? 1 : 0,
                'attachment_id' => absint( $item['attachment_id'] ?? 0 ),
                'image_url'     => esc_url_raw( $item['image_url'] ?? '' ),
                'alt'           => sanitize_text_field( $item['alt'] ?? '' ),
                'title'         => sanitize_text_field( $item['title'] ?? '' ),
                'link_url'      => esc_url_raw( $item['link_url'] ?? '' ),
                'target'        => isset( $item['target'] ) && '_blank' === $item['target'] ? '_blank' : '_self',
                'start_at'      => sanitize_text_field( $item['start_at'] ?? '' ),
                'end_at'        => sanitize_text_field( $item['end_at'] ?? '' ),
                'order'         => max( 1, min( 99, absint( $item['order'] ?? $index + 1 ) ) ),
            );
            if ( 'campaign' === $type ) {
                $mode = sanitize_key( $item['action_mode'] ?? 'lightbox' );
                $row['action_mode'] = in_array( $mode, array( 'lightbox', 'link', 'none' ), true ) ? $mode : 'lightbox';
                $row['link_label'] = sanitize_text_field( $item['link_label'] ?? '' );
            } else {
                $row['button_text'] = sanitize_text_field( $item['button_text'] ?? 'Detaylar' );
            }
            $out[] = $row;
        }
        return $out;
    }

    public static function save() {
        if ( ! current_user_can( 'manage_options' ) ) { wp_die( 'Forbidden' ); }
        check_admin_referer( 'sthi_home_save' );

        $page_settings     = self::sanitize_page_settings( wp_unslash( $_POST['page_settings'] ?? array() ) );
        $hero_links        = self::sanitize_hero_links( wp_unslash( $_POST['hero_links'] ?? array() ) );
        $campaign_settings = self::sanitize_settings( wp_unslash( $_POST['campaign_settings'] ?? array() ), STHI_Home_Repository::default_campaign_settings(), false );
        $tour_settings     = self::sanitize_settings( wp_unslash( $_POST['tour_settings'] ?? array() ), STHI_Home_Repository::default_tour_settings(), true );
        $campaign_items    = self::sanitize_items( wp_unslash( $_POST['campaign_items'] ?? array() ), 'campaign' );
        $tour_items        = self::sanitize_items( wp_unslash( $_POST['tour_items'] ?? array() ), 'tour' );
        $story_settings    = self::sanitize_story_settings( wp_unslash( $_POST['story_settings'] ?? array() ) );

        update_option( STHI_Home_Plugin::OPTION_PAGE_SETTINGS, $page_settings, false );
        update_option( STHI_Home_Plugin::OPTION_HERO_LINKS, $hero_links, false );
        update_option( STHI_Home_Plugin::OPTION_CAMPAIGN_SETTINGS, $campaign_settings, false );
        update_option( STHI_Home_Plugin::OPTION_CAMPAIGN_ITEMS, $campaign_items, false );
        update_option( STHI_Home_Plugin::OPTION_TOUR_SETTINGS, $tour_settings, false );
        update_option( STHI_Home_Plugin::OPTION_TOUR_ITEMS, $tour_items, false );
        update_option( STHI_Home_Plugin::OPTION_STORY_SETTINGS, $story_settings, false );
        update_option( STHI_Home_Plugin::OPTION_REVISION, absint( get_option( STHI_Home_Plugin::OPTION_REVISION, 1 ) ) + 1, false );

        $story_cutover_mode = isset( $_POST['sthi_home_story_cutover_mode'] ) ? sanitize_key( wp_unslash( $_POST['sthi_home_story_cutover_mode'] ) ) : '';
        if ( $story_cutover_mode ) {
            $confirm = isset( $_POST['sthi_home_story_cutover_confirm'] ) ? strtoupper( trim( sanitize_text_field( wp_unslash( $_POST['sthi_home_story_cutover_confirm'] ) ) ) ) : '';
            if ( 'on' === $story_cutover_mode ) {
                if ( 'STORY' !== $confirm ) { wp_die( 'Journey Evidence onayı STORY olmalıdır.' ); }
                if ( ! STHI_Home_Plugin::story_ready() ) { wp_die( 'Journey Evidence kaynağı hazır değil: yayınlanmış Post + Featured Image zorunlu.' ); }
                update_option( STHI_Home_Plugin::OPTION_STORY_PUBLIC, true, false );
            } elseif ( 'off' === $story_cutover_mode ) {
                if ( 'STORY-OFF' !== $confirm ) { wp_die( 'Journey Evidence kapatma onayı STORY-OFF olmalıdır.' ); }
                update_option( STHI_Home_Plugin::OPTION_STORY_PUBLIC, false, false );
            }
            wp_safe_redirect( add_query_arg( array( 'page' => self::MENU_SLUG, 'saved' => '1' ), admin_url( 'admin.php' ) ) );
            exit;
        }

        $cutover_mode = isset( $_POST['sthi_home_cutover_mode'] ) ? sanitize_key( wp_unslash( $_POST['sthi_home_cutover_mode'] ) ) : '';
        if ( $cutover_mode ) {
            $confirm = isset( $_POST['sthi_home_cutover_confirm'] ) ? strtoupper( trim( sanitize_text_field( wp_unslash( $_POST['sthi_home_cutover_confirm'] ) ) ) ) : '';
            if ( 'on' === $cutover_mode ) {
                $issues = STHI_Home_Repository::seo_audit();
                $p0 = array_filter( $issues, function( $issue ) { return isset( $issue['severity'] ) && 'P0' === $issue['severity']; } );
                if ( $p0 ) { wp_die( 'SEO P0 blocker varken PUBLIC CUTOVER açılamaz.' ); }
                if ( ! STHI_Home_Plugin::h12f_bridge_ready() ) { wp_die( 'H12F Hotel Discovery bridge hazır değil. Hotel bölümü olmadan PUBLIC CUTOVER açılamaz.' ); }
                if ( 'CUTOVER' !== $confirm ) { wp_die( 'Onay metni CUTOVER olmalıdır.' ); }
                if ( ! STHI_Home_Plugin::baseline_sha256() ) { wp_die( 'Plugin-owned Homepage baseline okunamadı.' ); }
                update_option( STHI_Home_Plugin::OPTION_PUBLIC, true, false );
            } elseif ( 'off' === $cutover_mode ) {
                if ( 'ROLLBACK' !== $confirm ) { wp_die( 'Onay metni ROLLBACK olmalıdır.' ); }
                update_option( STHI_Home_Plugin::OPTION_PUBLIC, false, false );
            }
            wp_safe_redirect( add_query_arg( array( 'page' => self::MENU_SLUG, 'cutover' => '1' ), admin_url( 'admin.php' ) ) );
            exit;
        }

        $after_save = isset( $_POST['sthi_home_after_save'] ) ? sanitize_key( wp_unslash( $_POST['sthi_home_after_save'] ) ) : '';
        if ( 'preview' === $after_save ) {
            wp_safe_redirect( STHI_Home_Plugin::preview_url() );
            exit;
        }

        wp_safe_redirect( add_query_arg( array( 'page' => self::MENU_SLUG, 'saved' => '1' ), admin_url( 'admin.php' ) ) );
        exit;
    }

    public static function evidence() {
        if ( ! current_user_can( 'manage_options' ) ) { wp_die( 'Forbidden' ); }
        check_admin_referer( 'sthi_home_evidence' );

        $issues = STHI_Home_Repository::seo_audit();
        $data = array(
            'version'                  => STHI_HOME_VERSION,
            'captured_at'              => current_time( DATE_W3C, true ),
            'mode'                     => STHI_Home_Plugin::public_enabled() ? 'public_plugin_baseline' : 'no_raw_admin_preview',
            'homepage_public_master'   => STHI_Home_Plugin::public_enabled(),
            'revision'                 => absint( get_option( STHI_Home_Plugin::OPTION_REVISION, 1 ) ),
            'data_version'             => (string) get_option( STHI_Home_Plugin::OPTION_DATA_VERSION, STHI_HOME_VERSION ),
            'front_page_id'            => absint( get_option( 'page_on_front' ) ),
            'server_rendered_content'  => true,
            'javascript_required'      => false,
            'javascript_required_for_core_content' => false,
            'javascript_required_for_lightbox'     => true,
            'campaign_interaction'      => 'lightbox_with_optional_crawlable_cta_or_direct_link',
            'touches_h1'               => true,
            'h1_ownership'              => STHI_Home_Plugin::public_enabled() ? 'public_plugin_baseline' : 'admin_preview_only',
            'touches_canonical'        => false,
            'touches_robots_public'    => false,
            'touches_title_meta'       => true,
            'title_meta_ownership'      => STHI_Home_Plugin::public_enabled() ? 'public_plugin_baseline' : 'admin_preview_only',
            'ai_json_schema'            => 'sthi-homepage-ai/v1',
            'ai_json_apply_mode'        => 'client_side_form_only_then_preview_gate',
            'hero_background_controls' => array( 'zoom' => '100-180', 'x' => '0-100', 'y' => '0-100' ),
            'raw_html_retirement_candidate' => true,
            'h12f_bridge_ready'       => STHI_Home_Plugin::h12f_bridge_ready(),
            'h12f_selected_count'     => STHI_Home_Plugin::h12f_selected_count(),
            'h12f_composition_mode'   => 'priority90_fail_safe_after_h12f_priority80',
            'journey_evidence_public' => STHI_Home_Plugin::story_public_enabled(),
            'journey_evidence_ready'  => STHI_Home_Plugin::story_ready(),
            'journey_evidence_settings' => STHI_Home_Repository::story_settings(),
            'plugin_baseline_sha256' => STHI_Home_Plugin::baseline_sha256(),
            'structured_data_emitted'  => false,
            'page_settings'            => STHI_Home_Repository::page_settings(),
            'hero_links'               => STHI_Home_Repository::hero_links(),
            'campaign_settings'        => STHI_Home_Repository::campaign_settings(),
            'campaign_active_count'    => count( STHI_Home_Repository::active_campaign_items() ),
            'culture_settings'         => STHI_Home_Repository::tour_settings(),
            'culture_active_count'     => count( STHI_Home_Repository::active_tour_items() ),
            'seo_issues'               => $issues,
            'seo_issue_count'          => count( $issues ),
            'config_sha256'            => hash( 'sha256', wp_json_encode( array(
                STHI_Home_Repository::page_settings(),
                STHI_Home_Repository::hero_links(),
                STHI_Home_Repository::campaign_settings(),
                STHI_Home_Repository::campaign_items(),
                STHI_Home_Repository::tour_settings(),
                STHI_Home_Repository::tour_items(),
                STHI_Home_Repository::story_settings(),
                STHI_Home_Plugin::story_public_enabled(),
            ), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) ),
        );

        $filename = 'sthi-home-evidence-' . gmdate( 'Ymd-His' ) . '.json';
        nocache_headers();
        header( 'Content-Type: application/json; charset=utf-8' );
        header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
        echo wp_json_encode( $data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
        exit;
    }
}
