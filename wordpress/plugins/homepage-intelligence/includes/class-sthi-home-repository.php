<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

final class STHI_Home_Repository {
    public static function default_page_settings() {
        return array(
            'seo_title'            => 'Server Turizm | Hac, Umre, Kültür Turları ve Oteller',
            'meta_description'      => 'Server Turizm ile güncel Umre programları, Hac hizmetleri, Mekke ve Medine otelleri, kültür turları ve seyahat hizmetlerini keşfedin.',
            'social_title'          => 'Server Turizm | Hac, Umre ve Kültür Turları',
            'social_description'    => 'Güncel Umre programları, Hac hizmetleri, oteller ve kültür turları için Server Turizm.',
            'social_image_id'       => 0,
            'social_image_url'      => '',
            'hero_background_id'    => 0,
            'hero_background_url'   => home_url( '/wp-content/uploads/2026/02/11221QQ-01-scaled.jpg' ),
            'hero_background_zoom'  => 100,
            'hero_background_x'     => 50,
            'hero_background_y'     => 50,
            'hero_h1'               => "Server Turizm:\nHac, Umre ve Gönül\nCoğrafyamıza Kültür Turları",
            'hero_intro'            => 'Kutsal toprakların huzurunu ve tarihin derinliklerini bizimle keşfedin. Hac ve Umre ibadetlerinizden, seçkin kültür turlarına kadar; konforlu ve güvenli bir seyahat deneyimi.',
            'search_button_label'   => 'DETAYLI ARAMA',
            'umre_section_title'    => 'Öne Çıkan Umre Programları',
            'umre_cta_label'        => '2026–2027 Umre Turları ve Fiyatlarını İncele',
            'umre_cta_url'          => home_url( '/umre-1/' ),
        );
    }

    public static function default_hero_links() {
        return array(
            array( 'id' => 'hero-link-1', 'enabled' => 1, 'label' => 'HAC TURLARI', 'url' => home_url( '/hac/' ), 'icon' => 'kaaba', 'order' => 1 ),
            array( 'id' => 'hero-link-2', 'enabled' => 1, 'label' => 'UMRE TURLARI', 'url' => home_url( '/umre-1/' ), 'icon' => 'mosque', 'order' => 2 ),
            array( 'id' => 'hero-link-3', 'enabled' => 1, 'label' => 'KÜLTÜR TURLARI', 'url' => home_url( '/kultur-turlari/' ), 'icon' => 'globe', 'order' => 3 ),
            array( 'id' => 'hero-link-4', 'enabled' => 1, 'label' => 'ARAÇ KİRALAMA', 'url' => home_url( '/arac-kiralama/' ), 'icon' => 'car', 'order' => 4 ),
            array( 'id' => 'hero-link-5', 'enabled' => 0, 'label' => '', 'url' => '', 'icon' => 'bed', 'order' => 5 ),
            array( 'id' => 'hero-link-6', 'enabled' => 0, 'label' => '', 'url' => '', 'icon' => 'plane', 'order' => 6 ),
        );
    }

    public static function default_story_settings() {
        return array(
            'enabled'    => 1,
            'post_id'    => 6216,
            'eyebrow'    => 'YOLCULUKLARIMIZDAN',
            'heading'    => 'Özbekistan’dan Bir Hatıra',
            'badge'      => 'RESMÎ YAYINDA YER ALDI',
            'cta_label'  => 'HABERİ OKU',
        );
    }

    public static function default_campaign_settings() {
        return array(
            'enabled'      => 1,
            'layout_mode'  => 'auto',
            'columns'      => 2,
            'direction'    => 'ltr',
            'flow'         => 'row',
            'max_items'    => 10,
        );
    }

    public static function default_tour_settings() {
        return array(
            'enabled'      => 1,
            'title'        => 'Kültür Turları',
            'intro'        => '',
            'layout_mode'  => 'auto',
            'columns'      => 4,
            'direction'    => 'ltr',
            'flow'         => 'row',
            'max_items'    => 10,
            'source_mode'  => 'manual',
        );
    }

    private static function campaign_seed( $id, $url, $alt, $link, $order ) {
        return array(
            'id'            => $id,
            'enabled'       => 1,
            'attachment_id' => 0,
            'image_url'     => $url,
            'alt'           => $alt,
            'title'         => $alt,
            'action_mode'   => 'lightbox',
            'link_url'      => $link,
            'link_label'    => $alt,
            'target'        => '_self',
            'start_at'      => '',
            'end_at'        => '',
            'order'         => $order,
        );
    }

    public static function default_campaign_items() {
        return array(
            self::campaign_seed( 'campaign-1', home_url( '/wp-content/uploads/2026/07/34334-02-scaled.jpg' ), 'Nisan tur programı', '', 1 ),
            self::campaign_seed( 'campaign-2', home_url( '/wp-content/uploads/2026/08/Onur-hoca-Umre-Programi.jpg' ), 'Onur Hoca Umre programı', '', 2 ),
            self::campaign_seed( 'campaign-3', home_url( '/wp-content/uploads/2026/07/Ismail-agha-cemati-project-01-scaled.jpg' ), 'İsmail Ağa Cemaati Umre programı', '', 3 ),
            self::campaign_seed( 'campaign-4', home_url( '/wp-content/uploads/2026/04/balkan-turu.png' ), 'Balkan turu', '', 4 ),
        );
    }

    private static function tour_seed( $id, $title, $url, $alt, $link, $order ) {
        return array(
            'id'            => $id,
            'enabled'       => 1,
            'attachment_id' => 0,
            'image_url'     => $url,
            'alt'           => $alt,
            'title'         => $title,
            'link_url'      => $link,
            'button_text'   => 'Detaylar',
            'target'        => '_self',
            'start_at'      => '',
            'end_at'        => '',
            'order'         => $order,
        );
    }

    public static function default_tour_items() {
        return array(
            self::tour_seed( 'tour-1', 'Mısır', home_url( '/wp-content/uploads/2026/02/egypt.webp' ), 'Mısır kültür turu', home_url( '/kultur-turlari/#t3' ), 1 ),
            self::tour_seed( 'tour-2', 'İran', home_url( '/wp-content/uploads/2026/02/iran.jpg' ), 'İran kültür turu', home_url( '/turlar/' ), 2 ),
            self::tour_seed( 'tour-3', 'Özbekistan', home_url( '/wp-content/uploads/2026/02/UZBEKISTAN.webp' ), 'Özbekistan kültür turu', home_url( '/kultur-turlari/#t2' ), 3 ),
            self::tour_seed( 'tour-4', 'Balkan', home_url( '/wp-content/uploads/2026/02/Greece.webp' ), 'Balkan kültür turu', home_url( '/kultur-turlari/#t1' ), 4 ),
        );
    }

    private static function merge_settings( $raw, $defaults ) {
        $raw = is_array( $raw ) ? $raw : array();
        return array_merge( $defaults, $raw );
    }

    public static function page_settings() {
        return self::merge_settings( get_option( STHI_Home_Plugin::OPTION_PAGE_SETTINGS, array() ), self::default_page_settings() );
    }

    public static function hero_links() {
        $items = get_option( STHI_Home_Plugin::OPTION_HERO_LINKS, array() );
        return is_array( $items ) ? $items : self::default_hero_links();
    }

    public static function story_settings() {
        return self::merge_settings( get_option( STHI_Home_Plugin::OPTION_STORY_SETTINGS, array() ), self::default_story_settings() );
    }

    public static function campaign_settings() {
        return self::merge_settings( get_option( STHI_Home_Plugin::OPTION_CAMPAIGN_SETTINGS, array() ), self::default_campaign_settings() );
    }

    public static function tour_settings() {
        return self::merge_settings( get_option( STHI_Home_Plugin::OPTION_TOUR_SETTINGS, array() ), self::default_tour_settings() );
    }

    public static function campaign_items() {
        $items = get_option( STHI_Home_Plugin::OPTION_CAMPAIGN_ITEMS, array() );
        return is_array( $items ) ? $items : array();
    }

    public static function tour_items() {
        $items = get_option( STHI_Home_Plugin::OPTION_TOUR_ITEMS, array() );
        return is_array( $items ) ? $items : array();
    }

    private static function is_active_now( $item ) {
        if ( empty( $item['enabled'] ) ) { return false; }
        $now = time();
        $tz  = wp_timezone();

        if ( ! empty( $item['start_at'] ) ) {
            $dt = DateTimeImmutable::createFromFormat( 'Y-m-d\\TH:i', (string) $item['start_at'], $tz );
            $start = $dt ? $dt->getTimestamp() : 0;
            if ( $start && $now < $start ) { return false; }
        }
        if ( ! empty( $item['end_at'] ) ) {
            $dt = DateTimeImmutable::createFromFormat( 'Y-m-d\\TH:i', (string) $item['end_at'], $tz );
            $end = $dt ? $dt->getTimestamp() : 0;
            if ( $end && $now >= $end ) { return false; }
        }
        return true;
    }

    private static function sort_items( $items ) {
        usort( $items, function( $a, $b ) {
            $ao = isset( $a['order'] ) ? intval( $a['order'] ) : 999;
            $bo = isset( $b['order'] ) ? intval( $b['order'] ) : 999;
            if ( $ao === $bo ) { return strcmp( (string) ( $a['id'] ?? '' ), (string) ( $b['id'] ?? '' ) ); }
            return $ao <=> $bo;
        } );
        return $items;
    }

    private static function active_items( $items, $max ) {
        $active = array_values( array_filter( $items, array( __CLASS__, 'is_active_now' ) ) );
        $active = self::sort_items( $active );
        return array_slice( $active, 0, max( 1, min( 10, absint( $max ) ) ) );
    }

    public static function active_campaign_items() {
        $settings = self::campaign_settings();
        return self::active_items( self::campaign_items(), $settings['max_items'] );
    }

    public static function active_tour_items() {
        $settings = self::tour_settings();
        return self::active_items( self::tour_items(), $settings['max_items'] );
    }

    public static function auto_columns( $count, $context ) {
        $count = max( 1, min( 10, absint( $count ) ) );
        if ( 'campaign' === $context ) {
            $map = array( 1 => 1, 2 => 2, 3 => 3, 4 => 2, 5 => 5, 6 => 3, 7 => 4, 8 => 4, 9 => 3, 10 => 5 );
        } else {
            $map = array( 1 => 1, 2 => 2, 3 => 3, 4 => 4, 5 => 5, 6 => 3, 7 => 4, 8 => 4, 9 => 3, 10 => 5 );
        }
        return $map[ $count ];
    }

    public static function effective_columns( $settings, $count, $context ) {
        if ( isset( $settings['layout_mode'] ) && 'manual' === $settings['layout_mode'] ) {
            return max( 1, min( 5, absint( $settings['columns'] ) ) );
        }
        return self::auto_columns( $count, $context );
    }

    public static function seo_audit() {
        $issues = array();
        $seen_links = array();

        $sets = array(
            'campaign' => self::campaign_items(),
            'tour'     => self::tour_items(),
        );

        foreach ( $sets as $type => $items ) {
            foreach ( $items as $i => $item ) {
                if ( empty( $item['enabled'] ) ) { continue; }
                $label = strtoupper( $type ) . ' #' . ( $i + 1 );
                $alt   = trim( (string) ( $item['alt'] ?? '' ) );
                $link  = trim( (string) ( $item['link_url'] ?? '' ) );
                $img   = trim( (string) ( $item['image_url'] ?? '' ) );
                $aid   = absint( $item['attachment_id'] ?? 0 );
                $action_mode = 'campaign' === $type ? (string) ( $item['action_mode'] ?? 'lightbox' ) : 'link';
                if ( ! in_array( $action_mode, array( 'lightbox', 'link', 'none' ), true ) ) { $action_mode = 'lightbox'; }

                if ( '' === $alt ) { $issues[] = array( 'severity' => 'P0', 'code' => 'missing_alt', 'item' => $label, 'message' => 'Görsel alt metni eksik.' ); }
                if ( ! $aid && '' === $img ) { $issues[] = array( 'severity' => 'P0', 'code' => 'missing_image', 'item' => $label, 'message' => 'Görsel eksik.' ); }
                if ( 'link' === $action_mode && '' === $link ) {
                    $issues[] = array( 'severity' => 'P0', 'code' => 'missing_link', 'item' => $label, 'message' => 'Doğrudan Link modunda crawlable href hedefi eksik.' );
                }

                // A link is optional in Lightbox mode (rendered as a separate
                // crawlable CTA) and required in direct Link mode.
                if ( 'none' !== $action_mode && $link ) {
                    $parts = wp_parse_url( $link );
                    $home  = wp_parse_url( home_url( '/' ) );
                    if ( empty( $parts['scheme'] ) && 0 !== strpos( $link, '/' ) ) {
                        $issues[] = array( 'severity' => 'P0', 'code' => 'invalid_link', 'item' => $label, 'message' => 'URL gerçek bir web adresi veya /site-ici-yol/ olarak çözümlenemiyor.' );
                    }
                    if ( ! empty( $parts['host'] ) && ! empty( $home['host'] ) && strtolower( $parts['host'] ) !== strtolower( $home['host'] ) ) {
                        $issues[] = array( 'severity' => 'P1', 'code' => 'external_link', 'item' => $label, 'message' => 'Harici bağlantı: editoryal olarak doğrulayın.' );
                    }
                    if ( false !== strpos( $link, '/wp-content/uploads/' ) ) {
                        $issues[] = array( 'severity' => 'P0', 'code' => 'media_file_link', 'item' => $label, 'message' => 'Bağlantı doğrudan medya dosyasına gidiyor. Lightbox tam görseli zaten açar; anlamlı bir sayfa hedefi kullanın veya URL alanını boş bırakın.' );
                    }
                    $norm = untrailingslashit( strtolower( $link ) );
                    if ( isset( $seen_links[ $norm ] ) ) {
                        $issues[] = array( 'severity' => 'P2', 'code' => 'duplicate_link', 'item' => $label, 'message' => 'Aynı hedef URL birden fazla kartta kullanılıyor.' );
                    }
                    $seen_links[ $norm ] = true;
                }
                if ( 'tour' === $type && '' === trim( (string) ( $item['title'] ?? '' ) ) ) {
                    $issues[] = array( 'severity' => 'P0', 'code' => 'missing_title', 'item' => $label, 'message' => 'Tur başlığı eksik.' );
                }
            }
        }

        $page = self::page_settings();
        $seo_title = trim( (string) ( $page['seo_title'] ?? '' ) );
        $meta = trim( (string) ( $page['meta_description'] ?? '' ) );
        $h1 = trim( (string) ( $page['hero_h1'] ?? '' ) );
        if ( '' === $seo_title ) { $issues[] = array( 'severity' => 'P0', 'code' => 'homepage_seo_title_missing', 'item' => 'HOMEPAGE SEO', 'message' => 'Ana sayfa SEO title boş.' ); }
        elseif ( mb_strlen( $seo_title ) < 30 || mb_strlen( $seo_title ) > 65 ) { $issues[] = array( 'severity' => 'P1', 'code' => 'homepage_seo_title_length', 'item' => 'HOMEPAGE SEO', 'message' => 'SEO title editoryal uzunluk aralığının dışında; SERP preview ile kontrol edin.' ); }
        if ( '' === $meta ) { $issues[] = array( 'severity' => 'P0', 'code' => 'homepage_meta_missing', 'item' => 'HOMEPAGE SEO', 'message' => 'Meta description boş.' ); }
        elseif ( mb_strlen( $meta ) < 80 || mb_strlen( $meta ) > 170 ) { $issues[] = array( 'severity' => 'P1', 'code' => 'homepage_meta_length', 'item' => 'HOMEPAGE SEO', 'message' => 'Meta description editoryal uzunluk aralığının dışında.' ); }
        if ( '' === $h1 ) { $issues[] = array( 'severity' => 'P0', 'code' => 'homepage_h1_missing', 'item' => 'HERO', 'message' => 'Hero H1 boş.' ); }
        if ( '' === trim( (string) ( $page['hero_intro'] ?? '' ) ) ) { $issues[] = array( 'severity' => 'P1', 'code' => 'hero_intro_missing', 'item' => 'HERO', 'message' => 'Hero açıklaması boş.' ); }

        $seen_hero = array();
        foreach ( self::hero_links() as $i => $link_item ) {
            if ( empty( $link_item['enabled'] ) ) { continue; }
            $label = trim( (string) ( $link_item['label'] ?? '' ) );
            $url = trim( (string) ( $link_item['url'] ?? '' ) );
            if ( '' === $label || '' === $url ) {
                $issues[] = array( 'severity' => 'P0', 'code' => 'hero_link_incomplete', 'item' => 'HERO LINK #' . ( $i + 1 ), 'message' => 'Aktif hero bağlantısında etiket veya URL eksik.' );
                continue;
            }
            $norm = untrailingslashit( strtolower( $url ) );
            if ( isset( $seen_hero[ $norm ] ) ) { $issues[] = array( 'severity' => 'P2', 'code' => 'hero_link_duplicate', 'item' => 'HERO LINK #' . ( $i + 1 ), 'message' => 'Aynı hero hedefi birden fazla kez kullanılıyor.' ); }
            $seen_hero[ $norm ] = true;
        }

        return $issues;
    }
}
