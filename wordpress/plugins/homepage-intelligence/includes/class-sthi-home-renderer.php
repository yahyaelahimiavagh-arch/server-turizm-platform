<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

final class STHI_Home_Renderer {
    private static function hero_icon_class( $icon ) {
        $map = array(
            'kaaba' => 'fas fa-kaaba',
            'mosque' => 'fas fa-mosque',
            'globe' => 'fas fa-globe-asia',
            'car' => 'fas fa-car',
            'bed' => 'fas fa-bed',
            'plane' => 'fas fa-plane',
            'passport' => 'fas fa-passport',
            'map' => 'fas fa-map-marked-alt',
        );
        return isset( $map[ $icon ] ) ? $map[ $icon ] : 'fas fa-circle';
    }

    public static function hero_buttons( $items ) {
        $items = is_array( $items ) ? $items : array();
        usort( $items, function( $a, $b ) { return intval( $a['order'] ?? 999 ) <=> intval( $b['order'] ?? 999 ); } );
        ob_start();
        ?><div class="hero-btn-container" style="margin-bottom: 20px;"><?php
        foreach ( $items as $item ) {
            if ( empty( $item['enabled'] ) ) { continue; }
            $label = trim( (string) ( $item['label'] ?? '' ) );
            $url = esc_url( (string) ( $item['url'] ?? '' ) );
            if ( ! $label || ! $url ) { continue; }
            $icon = self::hero_icon_class( sanitize_key( $item['icon'] ?? '' ) );
            ?><a href="<?php echo $url; ?>" class="btn-hero-cat"><i class="<?php echo esc_attr( $icon ); ?>" aria-hidden="true"></i> <?php echo esc_html( $label ); ?></a><?php
        }
        ?></div><?php
        return trim( ob_get_clean() );
    }

    public static function hero_h1( $text ) {
        $text = trim( (string) $text );
        if ( ! $text ) { return '<h1 class="hero-title"></h1>'; }
        $lines = preg_split( '/\r\n|\r|\n/', $text );
        $lines = array_map( 'trim', $lines );
        return '<h1 class="hero-title">' . implode( '<br>', array_map( 'esc_html', $lines ) ) . '</h1>';
    }

    public static function hero_intro( $text ) {
        return '<p style="font-size: 1.1rem; opacity: 0.9; margin-bottom: 30px; max-width: 600px;">' . esc_html( trim( (string) $text ) ) . '</p>';
    }

    private static function layout_items( $items, $cols, $flow ) {
        if ( 'column' !== $flow || $cols <= 1 || count( $items ) <= $cols ) { return $items; }
        $count = count( $items );
        $rows  = (int) ceil( $count / $cols );
        $out   = array();
        for ( $r = 0; $r < $rows; $r++ ) {
            for ( $c = 0; $c < $cols; $c++ ) {
                $idx = ( $c * $rows ) + $r;
                if ( isset( $items[ $idx ] ) ) { $out[] = $items[ $idx ]; }
            }
        }
        return $out;
    }

    private static function safe_target( $target ) {
        return '_blank' === $target ? '_blank' : '_self';
    }

    private static function image_html( $item, $size, $loading = 'lazy', $fetchpriority = '' ) {
        $aid = absint( $item['attachment_id'] ?? 0 );
        $alt = (string) ( $item['alt'] ?? '' );
        $attrs = array(
            'alt'      => $alt,
            'loading'  => $loading,
            'decoding' => 'async',
        );
        if ( $fetchpriority ) { $attrs['fetchpriority'] = $fetchpriority; }

        if ( $aid && wp_attachment_is_image( $aid ) ) {
            return wp_get_attachment_image( $aid, $size, false, $attrs );
        }

        $url = esc_url( (string) ( $item['image_url'] ?? '' ) );
        if ( ! $url ) { return ''; }
        $extra = $fetchpriority ? ' fetchpriority="' . esc_attr( $fetchpriority ) . '"' : '';
        return '<img src="' . $url . '" alt="' . esc_attr( $alt ) . '" loading="' . esc_attr( $loading ) . '" decoding="async"' . $extra . ' />';
    }

    private static function link_attrs( $item ) {
        $target = self::safe_target( $item['target'] ?? '_self' );
        $rel = '_blank' === $target ? ' rel="noopener"' : '';
        return ' target="' . esc_attr( $target ) . '"' . $rel;
    }


    private static function meaningful_campaign_link( $url, $full ) {
        $url  = trim( (string) $url );
        $full = trim( (string) $full );
        if ( '' === $url ) { return ''; }

        // Never surface the poster file itself as the optional CTA. Lightbox
        // already owns the full-resolution media interaction.
        if ( $full && untrailingslashit( $url ) === untrailingslashit( $full ) ) {
            return '';
        }
        return esc_url( $url );
    }

    private static function full_image_url( $item ) {
        $aid = absint( $item['attachment_id'] ?? 0 );
        if ( $aid && wp_attachment_is_image( $aid ) ) {
            $url = wp_get_attachment_image_url( $aid, 'full' );
            if ( $url ) { return esc_url( $url ); }
        }
        return esc_url( (string) ( $item['image_url'] ?? '' ) );
    }

    private static function campaign_page_sizes( $count, $settings ) {
        $count = max( 1, min( 10, absint( $count ) ) );
        $manual = isset( $settings['layout_mode'] ) && 'manual' === $settings['layout_mode'];
        $capacity = $manual ? max( 1, min( 4, absint( $settings['columns'] ?? 4 ) ) ) : 4;
        $page_count = (int) ceil( $count / $capacity );
        $base = (int) floor( $count / $page_count );
        $remainder = $count % $page_count;
        $sizes = array();
        for ( $i = 0; $i < $page_count; $i++ ) {
            $sizes[] = $base + ( $i < $remainder ? 1 : 0 );
        }
        return $sizes;
    }

    private static function campaign_pages( $items, $settings, $flow ) {
        $sizes = self::campaign_page_sizes( count( $items ), $settings );
        $pages = array();
        $offset = 0;
        foreach ( $sizes as $size ) {
            $page_items = array_slice( $items, $offset, $size );
            // 3-item pages use an editorial mosaic (1 large + 2 stacked) rather
            // than three tiny equal columns. Four items remain a balanced 2x2.
            $cols = in_array( $size, array( 3, 4 ), true ) ? 2 : $size;
            $rows = in_array( $size, array( 3, 4 ), true ) ? 2 : 1;
            $page_items = self::layout_items( $page_items, $cols, $flow );
            $pages[] = array(
                'items' => $page_items,
                'count' => $size,
                'cols'  => $cols,
                'rows'  => $rows,
            );
            $offset += $size;
        }
        return $pages;
    }

    public static function campaign_empty_state() {
        return '<span class="sthi-home-campaign-empty-marker" data-sthi-home-campaign-empty hidden aria-hidden="true"></span>';
    }

    public static function campaign_grid( $items, $settings, $preview = false ) {
        if ( empty( $items ) ) { return self::campaign_empty_state(); }
        $dir  = 'rtl' === ( $settings['direction'] ?? 'ltr' ) ? 'rtl' : 'ltr';
        $flow = 'column' === ( $settings['flow'] ?? 'row' ) ? 'column' : 'row';
        $pages = self::campaign_pages( array_values( $items ), $settings, $flow );
        $page_count = count( $pages );
        $has_lightbox = false;
        $global_index = 0;

        ob_start();
        ?>
        <div class="grid-cat-container sthi-home-campaign-grid" aria-label="Güncel kampanya ve tur duyuruları" data-sthi-home-campaign data-sthi-campaign-page-count="<?php echo absint( $page_count ); ?>" data-flow="<?php echo esc_attr( $flow ); ?>" dir="<?php echo esc_attr( $dir ); ?>">
            <div class="sthi-home-campaign-stage" data-sthi-campaign-stage>
                <?php foreach ( $pages as $page_index => $page ) : ?>
                    <div class="sthi-home-campaign-page" data-sthi-campaign-page="<?php echo absint( $page_index ); ?>" data-count="<?php echo absint( $page['count'] ); ?>" role="list" aria-label="Kampanya sayfası <?php echo absint( $page_index + 1 ); ?> / <?php echo absint( $page_count ); ?>" <?php echo 0 === $page_index ? '' : 'hidden aria-hidden="true"'; ?> style="--sthi-page-cols:<?php echo absint( $page['cols'] ); ?>;--sthi-page-rows:<?php echo absint( $page['rows'] ); ?>">
                        <?php foreach ( $page['items'] as $item ) :
                            $img = self::image_html( $item, 'large', 0 === $global_index ? 'eager' : 'lazy', 0 === $global_index ? 'high' : '' );
                            $global_index++;
                            if ( ! $img ) { continue; }
                            // Keep the image/lightbox label independent from the
                            // optional CTA text. Otherwise an operator writing
                            // "Detayları aç" would accidentally replace the
                            // lightbox caption / image accessible name.
                            $label = trim( (string) ( $item['title'] ?? '' ) );
                            if ( ! $label ) { $label = trim( (string) ( $item['alt'] ?? '' ) ); }
                            if ( ! $label ) { $label = 'Kampanya görseli'; }
                            $mode = (string) ( $item['action_mode'] ?? 'lightbox' );
                            if ( ! in_array( $mode, array( 'lightbox', 'link', 'none' ), true ) ) { $mode = 'lightbox'; }
                            $full = self::full_image_url( $item );
                            $url = self::meaningful_campaign_link( (string) ( $item['link_url'] ?? '' ), $full );
                            $ambient = $full ? '--sthi-card-image:url("' . esc_url_raw( $full ) . '")' : '';
                            ?>
                            <div class="grid-cat-item sthi-home-campaign-card" role="listitem"<?php echo $ambient ? ' style="' . esc_attr( $ambient ) . '"' : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
                                <span class="sthi-home-campaign-card__ambient" aria-hidden="true"></span>
                                <?php if ( 'lightbox' === $mode && $full ) : $has_lightbox = true; ?>
                                    <button type="button" class="sthi-home-campaign-card__lightbox" data-sthi-lightbox-open data-sthi-lightbox-src="<?php echo esc_url( $full ); ?>" data-sthi-lightbox-alt="<?php echo esc_attr( (string) ( $item['alt'] ?? '' ) ); ?>" data-sthi-lightbox-caption="<?php echo esc_attr( $label ); ?>" aria-label="<?php echo esc_attr( $label . ' görselini büyüt' ); ?>">
                                        <?php echo $img; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                                    </button>
                                    <?php if ( $url ) :
                                        $cta_label = trim( (string) ( $item['link_label'] ?? '' ) );
                                        if ( ! $cta_label ) { $cta_label = 'Detayları aç'; }
                                    ?>
                                        <a class="sthi-home-campaign-card__link" href="<?php echo $url; ?>"<?php echo self::link_attrs( $item ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> aria-label="<?php echo esc_attr( $cta_label . ': ' . $label ); ?>" title="<?php echo esc_attr( $cta_label ); ?>">
                                            <span aria-hidden="true">↗</span><span class="sthi-home-sr-only"><?php echo esc_html( $cta_label ); ?></span>
                                        </a>
                                    <?php endif; ?>
                                <?php elseif ( 'link' === $mode && $url ) : ?>
                                    <a href="<?php echo $url; ?>"<?php echo self::link_attrs( $item ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> aria-label="<?php echo esc_attr( $label ); ?>">
                                        <?php echo $img; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                                        <span class="sthi-home-sr-only"><?php echo esc_html( $label ); ?></span>
                                    </a>
                                <?php else : ?>
                                    <div class="sthi-home-campaign-card__static" aria-label="<?php echo esc_attr( $label ); ?>">
                                        <?php echo $img; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>
            </div>

            <?php if ( $page_count > 1 ) : ?>
                <div class="sthi-home-campaign-pager" data-sthi-campaign-pager aria-label="Kampanya sayfaları">
                    <button type="button" class="sthi-home-campaign-pager__arrow" data-sthi-campaign-prev aria-label="Önceki kampanya grubu">‹</button>
                    <div class="sthi-home-campaign-pager__dots" role="tablist" aria-label="Kampanya grubu seçimi">
                        <?php for ( $dot = 0; $dot < $page_count; $dot++ ) : ?>
                            <button type="button" class="sthi-home-campaign-pager__dot<?php echo 0 === $dot ? ' is-active' : ''; ?>" data-sthi-campaign-dot="<?php echo absint( $dot ); ?>" role="tab" aria-selected="<?php echo 0 === $dot ? 'true' : 'false'; ?>" aria-label="Kampanya grubu <?php echo absint( $dot + 1 ); ?>"></button>
                        <?php endfor; ?>
                    </div>
                    <span class="sthi-home-campaign-pager__counter" data-sthi-campaign-page-counter aria-live="polite">1 / <?php echo absint( $page_count ); ?></span>
                    <button type="button" class="sthi-home-campaign-pager__arrow" data-sthi-campaign-next aria-label="Sonraki kampanya grubu">›</button>
                </div>
            <?php endif; ?>
        </div>
        <?php if ( $has_lightbox ) : ?>
            <div class="sthi-home-lightbox" data-sthi-lightbox hidden aria-hidden="true" role="dialog" aria-modal="true" aria-label="Kampanya görsel galerisi">
                <div class="sthi-home-lightbox__backdrop" data-sthi-lightbox-close></div>
                <div class="sthi-home-lightbox__dialog" role="document">
                    <button type="button" class="sthi-home-lightbox__close" data-sthi-lightbox-close aria-label="Galeriyi kapat">×</button>
                    <button type="button" class="sthi-home-lightbox__nav sthi-home-lightbox__prev" data-sthi-lightbox-prev aria-label="Önceki görsel">‹</button>
                    <figure class="sthi-home-lightbox__figure">
                        <img data-sthi-lightbox-image src="" alt="" />
                        <figcaption data-sthi-lightbox-caption></figcaption>
                    </figure>
                    <button type="button" class="sthi-home-lightbox__nav sthi-home-lightbox__next" data-sthi-lightbox-next aria-label="Sonraki görsel">›</button>
                    <div class="sthi-home-lightbox__counter" data-sthi-lightbox-counter aria-live="polite"></div>
                </div>
            </div>
        <?php endif; ?>
        
        <?php
        return trim( ob_get_clean() );
    }

    public static function journey_evidence( $settings, $preview = false ) {
        if ( empty( $settings['enabled'] ) ) { return ''; }

        $post_id = absint( $settings['post_id'] ?? 0 );
        if ( ! $post_id ) {
            return $preview ? '<div class="sthi-home-preview-error">Journey Evidence için Post ID eksik.</div>' : '';
        }

        $post = get_post( $post_id );
        if ( ! $post || 'post' !== $post->post_type || 'publish' !== $post->post_status ) {
            return $preview ? '<div class="sthi-home-preview-error">Journey Evidence Post #' . absint( $post_id ) . ' bulunamadı veya yayınlanmış değil.</div>' : '';
        }

        $title = get_the_title( $post_id );
        $url   = get_permalink( $post_id );
        $excerpt = trim( wp_strip_all_tags( get_the_excerpt( $post_id ) ) );
        if ( ! $excerpt ) {
            $excerpt = wp_trim_words( wp_strip_all_tags( strip_shortcodes( $post->post_content ) ), 30, '…' );
        }

        $thumb_id = get_post_thumbnail_id( $post_id );
        if ( ! $thumb_id ) {
            return $preview ? '<div class="sthi-home-preview-error">Journey Evidence Post #' . absint( $post_id ) . ' için Featured Image eksik.</div>' : '';
        }

        $alt = trim( (string) get_post_meta( $thumb_id, '_wp_attachment_image_alt', true ) );
        if ( ! $alt ) { $alt = $title; }
        $img = wp_get_attachment_image(
            $thumb_id,
            'large',
            false,
            array(
                'class'    => 'sthi-home-story__image',
                'loading'  => 'lazy',
                'decoding' => 'async',
                'alt'      => $alt,
            )
        );
        if ( ! $img ) { return ''; }

        $eyebrow = trim( (string) ( $settings['eyebrow'] ?? 'YOLCULUKLARIMIZDAN' ) );
        $heading = trim( (string) ( $settings['heading'] ?? 'Özbekistan’dan Bir Hatıra' ) );
        $badge   = trim( (string) ( $settings['badge'] ?? 'RESMÎ YAYINDA YER ALDI' ) );
        $cta     = trim( (string) ( $settings['cta_label'] ?? 'HABERİ OKU' ) );
        $date    = get_the_date( 'j F Y', $post_id );

        ob_start();
        ?>
        <section class="sthi-home-story" data-sthi-home-story data-post-id="<?php echo absint( $post_id ); ?>">
            <div class="container sthi-home-story__container">
                <div class="sthi-home-story__shell">
                    <a class="sthi-home-story__media" href="<?php echo esc_url( $url ); ?>" aria-label="<?php echo esc_attr( $title ); ?>">
                        <?php echo $img; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                        <?php if ( $badge ) : ?><span class="sthi-home-story__badge"><?php echo esc_html( $badge ); ?></span><?php endif; ?>
                    </a>
                    <div class="sthi-home-story__content">
                        <?php if ( $eyebrow ) : ?><p class="sthi-home-story__eyebrow"><?php echo esc_html( $eyebrow ); ?></p><?php endif; ?>
                        <?php if ( $heading ) : ?><h2 class="sthi-home-story__heading"><?php echo esc_html( $heading ); ?></h2><?php endif; ?>
                        <p class="sthi-home-story__date"><?php echo esc_html( $date ); ?></p>
                        <h3 class="sthi-home-story__title"><a href="<?php echo esc_url( $url ); ?>"><?php echo esc_html( $title ); ?></a></h3>
                        <?php if ( $excerpt ) : ?><p class="sthi-home-story__excerpt"><?php echo esc_html( $excerpt ); ?></p><?php endif; ?>
                        <a class="sthi-home-story__cta" href="<?php echo esc_url( $url ); ?>"><?php echo esc_html( $cta ?: 'HABERİ OKU' ); ?> <span aria-hidden="true">→</span></a>
                        <?php if ( $preview ) : ?><p class="sthi-home-preview-marker">ADMIN PREVIEW · Journey Evidence · <?php echo STHI_Home_Plugin::story_public_enabled() ? 'PUBLIC ON' : 'PUBLIC OFF'; ?></p><?php endif; ?>
                    </div>
                </div>
            </div>
        </section>
        <?php
        return trim( ob_get_clean() );
    }

    public static function culture_tours( $items, $settings, $preview = false ) {
        if ( empty( $items ) ) { return ''; }
        $cols = STHI_Home_Repository::effective_columns( $settings, count( $items ), 'tour' );
        $dir  = 'rtl' === ( $settings['direction'] ?? 'ltr' ) ? 'rtl' : 'ltr';
        $flow = 'column' === ( $settings['flow'] ?? 'row' ) ? 'column' : 'row';
        $items = self::layout_items( $items, $cols, $flow );
        $mobile_cols = min( 2, $cols );
        $title = trim( (string) ( $settings['title'] ?? 'Kültür Turları' ) );
        $intro = trim( (string) ( $settings['intro'] ?? '' ) );

        ob_start();
        ?>
        <section class="py-5 culture-tours-section sthi-home-culture" data-sthi-home-tours data-flow="<?php echo esc_attr( $flow ); ?>" dir="<?php echo esc_attr( $dir ); ?>">
            <div class="container py-5">
                <header class="sthi-home-culture__header text-center mb-5">
                    <h2><?php echo esc_html( $title ); ?></h2>
                    <?php if ( $intro ) : ?><p><?php echo esc_html( $intro ); ?></p><?php endif; ?>
                    <div class="sthi-home-culture__rule" aria-hidden="true"></div>
                </header>
                <div class="sthi-home-culture__grid" role="list" style="--sthi-home-cols:<?php echo absint( $cols ); ?>;--sthi-home-mobile-cols:<?php echo absint( $mobile_cols ); ?>">
                    <?php foreach ( $items as $item ) :
                        $img = self::image_html( $item, 'large', 'lazy', '' );
                        $url = esc_url( (string) ( $item['link_url'] ?? '' ) );
                        $name = trim( (string) ( $item['title'] ?? '' ) );
                        if ( ! $img || ! $url || ! $name ) { continue; }
                        $button = trim( (string) ( $item['button_text'] ?? 'Detaylar' ) );
                        ?>
                        <article class="culture-grid-item sthi-home-tour-card" role="listitem">
                            <a class="sthi-home-tour-card__media" href="<?php echo $url; ?>"<?php echo self::link_attrs( $item ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> aria-label="<?php echo esc_attr( $name . ' turunu incele' ); ?>">
                                <?php echo $img; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                            </a>
                            <h3><?php echo esc_html( $name ); ?></h3>
                            <a href="<?php echo $url; ?>"<?php echo self::link_attrs( $item ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> class="btn-small-outline"><?php echo esc_html( $button ); ?></a>
                        </article>
                    <?php endforeach; ?>
                </div>
                <?php if ( $preview ) : ?><p class="sthi-home-preview-marker">ADMIN PREVIEW · Manual source now · Tours Intelligence adapter later · PUBLIC OFF</p><?php endif; ?>
            </div>
        </section>
        <?php
        return trim( ob_get_clean() );
    }
}
