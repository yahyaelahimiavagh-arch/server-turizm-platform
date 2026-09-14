<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

final class STHHD_Renderer {
    private static function display_city_label( $city ) {
        $key = STHHD_Repository::normalized_city_key( $city );
        $map = array(
            'madinah' => 'Medine',
            'medine'  => 'Medine',
            'makkah'  => 'Mekke',
            'mekke'   => 'Mekke',
            'cairo'   => 'Kahire',
            'kahire'  => 'Kahire',
        );
        return isset( $map[ $key ] ) ? $map[ $key ] : trim( (string) $city );
    }

    public static function render( $hotels, $preview = false ) {
        if ( empty( $hotels ) || ! is_array( $hotels ) ) { return ''; }

        $cities = array();
        foreach ( $hotels as $hotel ) {
            $key = STHHD_Repository::normalized_city_key( $hotel['city'] );
            if ( $key && $hotel['city'] ) { $cities[ $key ] = self::display_city_label( $hotel['city'] ); }
        }

        $all_hotels_url = home_url( '/oteller/' );

        ob_start();
        ?>
        <section class="sthhd-home-hotels" data-sthhd-module data-preview="<?php echo $preview ? '1' : '0'; ?>" data-placement="before-culture-tours">
            <div class="sthhd-home-hotels__halo" aria-hidden="true"></div>
            <div class="sthhd-home-hotels__inner">
                <header class="sthhd-home-hotels__header">
                    <div class="sthhd-home-hotels__copy">
                        <p class="sthhd-home-hotels__eyebrow"><span></span> SERVER TURİZM · HOTEL INTELLIGENCE</p>
                        <h2 class="sthhd-home-hotels__title">Yolculuğunuzun ritmine uygun oteli keşfedin.</h2>
                        <p class="sthhd-home-hotels__lead">Mekke, Medine ve Kahire’deki doğrulanmış otelleri şehirlerine göre keşfedin; güncel konaklama bilgileri için doğrudan otel sayfasına geçin.</p>
                    </div>
                    <div class="sthhd-home-hotels__header-actions">
                        <a class="sthhd-home-hotels__all" href="<?php echo esc_url( $all_hotels_url ); ?>">Tüm otelleri keşfet <span aria-hidden="true">↗</span></a>
                    </div>
                </header>

                <?php if ( count( $cities ) > 1 ) : ?>
                <div class="sthhd-home-hotels__filters" role="group" aria-label="Şehre göre otel filtrele">
                    <button type="button" class="is-active" data-sthhd-filter="all" aria-pressed="true">Tümü</button>
                    <?php foreach ( $cities as $key => $label ) : ?>
                        <button type="button" data-sthhd-filter="<?php echo esc_attr( $key ); ?>" aria-pressed="false"><?php echo esc_html( $label ); ?></button>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <div class="sthhd-home-hotels__rail-shell">
                    <button class="sthhd-home-hotels__edge-control sthhd-home-hotels__edge-control--prev" type="button" data-sthhd-prev aria-label="Önceki oteller"><span aria-hidden="true">←</span></button>
                    <div class="sthhd-home-hotels__rail" data-sthhd-rail tabindex="0" aria-label="Seçili oteller">
                    <?php foreach ( $hotels as $hotel ) :
                        $city_key = STHHD_Repository::normalized_city_key( $hotel['city'] );
                        $city_label = self::display_city_label( $hotel['city'] );
                        $place = implode( ' · ', array_filter( array( $hotel['district'], $city_label ) ) );
                        $dims = ( $hotel['image_width'] && $hotel['image_height'] )
                            ? ' width="' . absint( $hotel['image_width'] ) . '" height="' . absint( $hotel['image_height'] ) . '"'
                            : '';
                        ?>
                        <article class="sthhd-hotel-card" data-sthhd-card data-city="<?php echo esc_attr( $city_key ); ?>" data-hotel-id="<?php echo esc_attr( $hotel['hotel_id'] ); ?>">
                            <a class="sthhd-hotel-card__link" href="<?php echo esc_url( $hotel['url'] ); ?>" aria-label="<?php echo esc_attr( $hotel['name'] ); ?> otel detaylarını incele">
                                <div class="sthhd-hotel-card__media">
                                    <img src="<?php echo esc_url( $hotel['image_url'] ); ?>" alt="<?php echo esc_attr( $hotel['name'] . ( $city_label ? ' - ' . $city_label : '' ) ); ?>" loading="lazy" decoding="async"<?php echo $dims; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> />
                                    <div class="sthhd-hotel-card__shade" aria-hidden="true"></div>
                                    <div class="sthhd-hotel-card__topline">
                                        <?php if ( $city_label ) : ?><span class="sthhd-hotel-card__city"><?php echo esc_html( $city_label ); ?></span><?php endif; ?>
                                        <span class="sthhd-hotel-card__verified"><i aria-hidden="true">✓</i> Doğrulanmış</span>
                                    </div>
                                    <div class="sthhd-hotel-card__content">
                                        <?php if ( $hotel['stars'] ) : ?>
                                            <p class="sthhd-hotel-card__stars" aria-label="<?php echo esc_attr( $hotel['stars'] ); ?> yıldız"><?php echo esc_html( str_repeat( '★', $hotel['stars'] ) ); ?></p>
                                        <?php else : ?>
                                            <p class="sthhd-hotel-card__stars sthhd-hotel-card__stars--empty">Yıldız bilgisi yok</p>
                                        <?php endif; ?>
                                        <h3 class="sthhd-hotel-card__title"><?php echo esc_html( $hotel['name'] ); ?></h3>
                                        <?php if ( $place ) : ?><p class="sthhd-hotel-card__place"><?php echo esc_html( $place ); ?></p><?php endif; ?>
                                        <span class="sthhd-hotel-card__cta">Otel detaylarını incele <b aria-hidden="true">↗</b></span>
                                    </div>
                                </div>
                            </a>
                        </article>
                    <?php endforeach; ?>
                    </div>
                    <button class="sthhd-home-hotels__edge-control sthhd-home-hotels__edge-control--next" type="button" data-sthhd-next aria-label="Sonraki oteller"><span aria-hidden="true">→</span></button>
                </div>

                <footer class="sthhd-home-hotels__footer">
                    <p><span aria-hidden="true">◆</span> Otel bilgileri Server Turizm Hotel Intelligence katmanından canlı olarak okunur.</p>
                    <?php if ( $preview ) : ?><span class="sthhd-home-hotels__preview-badge">ADMIN PREVIEW · PUBLIC OFF</span><?php endif; ?>
                </footer>
            </div>
        </section>
        <?php
        return trim( ob_get_clean() );
    }
}
