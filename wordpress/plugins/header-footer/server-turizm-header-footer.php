<?php
/**
 * Plugin Name: Server Turizm Header & Footer
 * Description: Server Turizm için özel, sabit ve mobil uyumlu header/footer tasarımı. Porto'nun varsayılan header ve footer alanlarını görünümden kaldırır.
 * Version: 1.1.12
 * Author: Server Turizm
 * Text Domain: server-turizm-shell
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class ST_Header_Footer {
    private const VERSION = '1.1.12';

    public static function init(): void {
        add_filter( 'body_class', [ __CLASS__, 'body_classes' ] );
        add_filter( 'porto_logo', [ __CLASS__, 'filter_porto_legacy_logo_semantics' ], PHP_INT_MAX );
        add_action( 'wp_enqueue_scripts', [ __CLASS__, 'enqueue_assets' ], 100 );
        add_action( 'wp_body_open', [ __CLASS__, 'render_header' ], 1 );
        add_action( 'wp_footer', [ __CLASS__, 'render_footer' ], 5 );
    }

    /**
     * v1.1.9: source-level semantic cleanup for Porto's legacy logo markup.
     *
     * The Server Turizm shell hides Porto's original header globally, but older
     * Porto versions can still render the hidden site logo as an H1. Modify only
     * that exact logo wrapper through Porto's documented `porto_logo` filter.
     * No page conditional is used here because Porto may build the logo markup
     * in a context where conditional tags are not yet reliable.
     *
     * Safety contract:
     * - only an H1 whose class list contains `logo`;
     * - must contain an <a> with rel="home";
     * - must contain an <img>;
     * - attributes and inner markup are preserved byte-for-byte;
     * - only H1/H1 tag names become DIV/DIV.
     */
    public static function filter_porto_legacy_logo_semantics( $logo_html ) {
        if ( ! is_string( $logo_html ) || '' === $logo_html ) {
            return $logo_html;
        }

        $filtered = preg_replace_callback(
            '~<h1\\b([^>]*)>(.*?)</h1>~is',
            static function ( array $match ): string {
                $attributes = $match[1];
                $inner_html = $match[2];

                if ( ! preg_match( '/\\bclass\\s*=\\s*(["\\\'])(.*?)\\1/is', $attributes, $class_match ) ) {
                    return $match[0];
                }

                $classes = preg_split( '/\\s+/', trim( $class_match[2] ) );
                if ( ! is_array( $classes )
                    || ! in_array( 'logo', $classes, true ) ) {
                    return $match[0];
                }

                if ( false === stripos( $inner_html, '<img' ) ) {
                    return $match[0];
                }

                if ( ! preg_match( '/<a\\b[^>]*\\brel\\s*=\\s*(["\\\'])[^"\\\']*\\bhome\\b[^"\\\']*\\1[^>]*>/is', $inner_html ) ) {
                    return $match[0];
                }

                return '<div' . $attributes . '>' . $inner_html . '</div>';
            },
            $logo_html,
            1
        );

        return is_string( $filtered ) ? $filtered : $logo_html;
    }

    public static function body_classes( array $classes ): array {
        $classes[] = 'st-shell-active';
        $classes[] = is_front_page() ? 'st-shell-front' : 'st-shell-internal';
        return $classes;
    }

    public static function enqueue_assets(): void {
        $base = plugin_dir_url( __FILE__ );

        wp_enqueue_style(
            'st-shell-fonts',
            'https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&family=Playfair+Display:wght@500;600&display=swap',
            [],
            null
        );

        wp_enqueue_style(
            'st-shell-style',
            $base . 'assets/css/server-header-footer.css',
            [ 'st-shell-fonts' ],
            self::VERSION
        );

        wp_enqueue_script(
            'st-shell-script',
            $base . 'assets/js/server-header-footer.js',
            [],
            self::VERSION,
            true
        );
    }

    private static function icon( string $name ): string {
        $icons = [
            'clock' => '<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"></circle><path d="M12 7v5l3 2"></path></svg>',
            'phone' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6A19.79 19.79 0 0 1 2.12 4.18 2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.12.9.33 1.78.62 2.63a2 2 0 0 1-.45 2.11L8 9.73a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.85.29 1.73.5 2.63.62A2 2 0 0 1 22 16.92z"></path></svg>',
            'mail' => '<svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2"></rect><path d="m3 7 9 6 9-6"></path></svg>',
            'map' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20 10c0 5-8 11-8 11S4 15 4 10a8 8 0 1 1 16 0z"></path><circle cx="12" cy="10" r="2.5"></circle></svg>',
            'instagram' => '<svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="5"></rect><circle cx="12" cy="12" r="4"></circle><circle cx="17.5" cy="6.5" r="1"></circle></svg>',
            'facebook' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M14 8h3V4h-3c-3 0-5 2-5 5v3H6v4h3v6h4v-6h3l1-4h-4V9c0-.7.3-1 1-1z"></path></svg>',
            'whatsapp' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20.5 11.7a8.5 8.5 0 0 1-12.6 7.4L3 20.5l1.4-4.7a8.5 8.5 0 1 1 16.1-4.1z"></path><path d="M8.1 7.8c.3-.6.6-.6.9-.6h.5c.2 0 .4.1.5.4l.8 2c.1.3.1.5-.1.7l-.6.8c-.2.2-.2.4 0 .7.5.9 1.3 1.7 2.2 2.2.3.2.5.2.7 0l.9-1c.2-.2.4-.3.7-.2l2 .9c.3.1.4.3.4.5 0 .3-.1 1.4-.7 2-.6.6-1.5.9-2.4.7-1.1-.2-2.7-.8-4.5-2.4-1.5-1.3-2.5-3-2.8-4.1-.3-1 .1-2 .5-2.6z"></path></svg>',
            'chevron' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="m7 10 5 5 5-5"></path></svg>',
            'arrow' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h14"></path><path d="m14 7 5 5-5 5"></path></svg>',
        ];

        return $icons[ $name ] ?? '';
    }

    public static function render_header(): void {
        $logo = plugin_dir_url( __FILE__ ) . 'assets/images/server-turizm-gold.png';
        $whatsapp = 'https://wa.me/905302015284?text=' . rawurlencode( 'Merhaba, Server Turizm programları hakkında bilgi almak istiyorum.' );
        ?>
        <div class="st-shell" id="stShell">
            <a class="st-skip-link" href="#main">İçeriğe geç</a>

            <div class="st-topbar">
                <div class="st-topbar__inner">
                    <div class="st-topbar__message"><span>1998'den beri güvenli yolculuklar</span><i aria-hidden="true">✦</i><span class="st-topbar__hours"><?php echo self::icon( 'clock' ); ?> P.tesi–Cuma / 09:00–18:00</span></div>
                    <div class="st-topbar__contact">
                        <a href="mailto:server@serverturizm.com.tr"><?php echo self::icon( 'mail' ); ?><span>server@serverturizm.com.tr</span></a>
                        <a href="tel:+902126210500"><?php echo self::icon( 'phone' ); ?><span>+90 212 621 05 00</span></a>
                        <a class="st-social" href="https://instagram.com/servertur" target="_blank" rel="noopener noreferrer" aria-label="Instagram"><?php echo self::icon( 'instagram' ); ?></a>
                        <a class="st-social" href="https://facebook.com/servertur" target="_blank" rel="noopener noreferrer" aria-label="Facebook"><?php echo self::icon( 'facebook' ); ?></a>
                    </div>
                </div>
            </div>

            <a class="st-brand-logo" href="<?php echo esc_url( home_url( '/' ) ); ?>" aria-label="Server Turizm ana sayfa">
                <img src="<?php echo esc_url( $logo ); ?>" alt="Server Turizm" fetchpriority="high">
            </a>

            <header class="st-site-header" id="stSiteHeader">
                <div class="st-header__inner">
                    <div class="st-header__logo-space" aria-hidden="true"></div>

                    <nav class="st-desktop-nav" aria-label="Ana menü">
                        <a class="st-nav-link" href="<?php echo esc_url( home_url( '/' ) ); ?>">Ana Sayfa</a>
                        <a class="st-nav-link" href="<?php echo esc_url( home_url( '/hakkimizda/' ) ); ?>">Hakkımızda</a>
                        <a class="st-nav-link" href="<?php echo esc_url( home_url( '/dis-ticaret/' ) ); ?>">Dış Ticaret</a>

                        <div class="st-nav-item st-has-dropdown">
                            <button class="st-nav-link st-dropdown-trigger" type="button" aria-expanded="false">Hac &amp; Umre <?php echo self::icon( 'chevron' ); ?></button>
                            <div class="st-dropdown" role="menu">
                                <span class="st-dropdown__eyebrow">Kutsal Yolculuklar</span>
                                <a href="<?php echo esc_url( home_url( '/umre-1/' ) ); ?>" role="menuitem"><b>Umre Programları</b><small>Yaklaşan ekonomik ve lüks programlar</small></a>
                                <a href="<?php echo esc_url( home_url( '/hac/' ) ); ?>" role="menuitem"><b>Hac Programları</b><small>Kayıt ve program detayları</small></a>
                            </div>
                        </div>

                        <a class="st-nav-link" href="<?php echo esc_url( home_url( '/kultur-turlari/' ) ); ?>">Kültür Turları</a>

                        <div class="st-nav-item st-has-dropdown">
                            <button class="st-nav-link st-dropdown-trigger" type="button" aria-expanded="false">Hizmetler <?php echo self::icon( 'chevron' ); ?></button>
                            <div class="st-dropdown" role="menu">
                                <span class="st-dropdown__eyebrow">Seyahat Hizmetleri</span>
                                <a href="<?php echo esc_url( home_url( '/oteller/' ) ); ?>" role="menuitem"><b>Oteller</b><small>Mekke ve Medine otel seçenekleri</small></a>
                                <a href="<?php echo esc_url( home_url( '/vize/' ) ); ?>" role="menuitem"><b>Vize</b><small>Başvuru ve danışmanlık hizmetleri</small></a>
                                <a href="<?php echo esc_url( home_url( '/arac-kiralama/' ) ); ?>" role="menuitem"><b>Araç Kiralama</b><small>Konforlu ulaşım çözümleri</small></a>
                            </div>
                        </div>

                        <div class="st-nav-item st-has-dropdown">
                            <button class="st-nav-link st-dropdown-trigger" type="button" aria-expanded="false">Galeri <?php echo self::icon( 'chevron' ); ?></button>
                            <div class="st-dropdown st-dropdown--compact" role="menu">
                                <span class="st-dropdown__eyebrow">Yolculuklardan Kareler</span>
                                <a href="<?php echo esc_url( home_url( '/foto-galeri/' ) ); ?>" role="menuitem"><b>Foto Galeri</b></a>
                                <a href="<?php echo esc_url( home_url( '/video-galeri/' ) ); ?>" role="menuitem"><b>Video Galeri</b></a>
                            </div>
                        </div>

                        <a class="st-nav-link" href="<?php echo esc_url( home_url( '/letisim/' ) ); ?>">İletişim</a>
                    </nav>

                    <div class="st-header__actions">
                        <a class="st-advisor-button" href="<?php echo esc_url( $whatsapp ); ?>" target="_blank" rel="noopener noreferrer">
                            <span><?php echo self::icon( 'whatsapp' ); ?></span>
                            <b>Tur Danışmanı</b>
                            <?php echo self::icon( 'arrow' ); ?>
                        </a>
                        <button class="st-menu-toggle" id="stMenuToggle" type="button" aria-label="Menüyü aç" aria-expanded="false" aria-controls="stMobileMenu"><span></span><span></span><span></span></button>
                    </div>
                </div>
            </header>

            <div class="st-mobile-backdrop" id="stMobileBackdrop"></div>
            <aside class="st-mobile-menu" id="stMobileMenu" aria-label="Mobil menü" aria-hidden="true">
                <div class="st-mobile-menu__head">
                    <span>Menü</span>
                    <button type="button" id="stMobileClose" aria-label="Menüyü kapat">×</button>
                </div>
                <div class="st-mobile-menu__body">
                    <a href="<?php echo esc_url( home_url( '/' ) ); ?>">Ana Sayfa</a>
                    <a href="<?php echo esc_url( home_url( '/hakkimizda/' ) ); ?>">Hakkımızda</a>
                    <a href="<?php echo esc_url( home_url( '/dis-ticaret/' ) ); ?>">Dış Ticaret</a>
                    <div class="st-mobile-group">
                        <button type="button" aria-expanded="false">Hac &amp; Umre <?php echo self::icon( 'chevron' ); ?></button>
                        <div><a href="<?php echo esc_url( home_url( '/umre-1/' ) ); ?>">Umre Programları</a><a href="<?php echo esc_url( home_url( '/hac/' ) ); ?>">Hac Programları</a></div>
                    </div>
                    <a href="<?php echo esc_url( home_url( '/kultur-turlari/' ) ); ?>">Kültür Turları</a>
                    <div class="st-mobile-group">
                        <button type="button" aria-expanded="false">Hizmetler <?php echo self::icon( 'chevron' ); ?></button>
                        <div><a href="<?php echo esc_url( home_url( '/oteller/' ) ); ?>">Oteller</a><a href="<?php echo esc_url( home_url( '/vize/' ) ); ?>">Vize</a><a href="<?php echo esc_url( home_url( '/arac-kiralama/' ) ); ?>">Araç Kiralama</a></div>
                    </div>
                    <div class="st-mobile-group">
                        <button type="button" aria-expanded="false">Galeri <?php echo self::icon( 'chevron' ); ?></button>
                        <div><a href="<?php echo esc_url( home_url( '/foto-galeri/' ) ); ?>">Foto Galeri</a><a href="<?php echo esc_url( home_url( '/video-galeri/' ) ); ?>">Video Galeri</a></div>
                    </div>
                    <a href="<?php echo esc_url( home_url( '/letisim/' ) ); ?>">İletişim</a>
                </div>
                <div class="st-mobile-menu__foot">
                    <a href="tel:+902126210500"><?php echo self::icon( 'phone' ); ?> +90 212 621 05 00</a>
                    <a class="st-mobile-whatsapp" href="<?php echo esc_url( $whatsapp ); ?>" target="_blank" rel="noopener noreferrer"><?php echo self::icon( 'whatsapp' ); ?> WhatsApp'tan Bilgi Al</a>
                </div>
            </aside>
        </div>
        <?php
    }

    public static function render_footer(): void {
        static $rendered = false;
        if ( $rendered ) {
            return;
        }
        $rendered = true;
        $logo = plugin_dir_url( __FILE__ ) . 'assets/images/server-turizm-white.png';
        $map = 'https://maps.app.goo.gl/YdfgejPcid1z5Ung7';
        $whatsapp = 'https://wa.me/905302015284?text=' . rawurlencode( 'Merhaba, Server Turizm programları hakkında bilgi almak istiyorum.' );
        ?>
        <footer class="st-site-footer" id="stSiteFooter">
            <div class="st-footer-cta">
                <div>
                    <span class="st-footer-kicker">Bir sonraki yolculuğunuz</span>
                    <h2>Güvenle başlasın.</h2>
                    <p>Program, otel ve vize seçenekleri için seyahat danışmanımızla görüşün.</p>
                </div>
                <a href="<?php echo esc_url( $whatsapp ); ?>" target="_blank" rel="noopener noreferrer"><?php echo self::icon( 'whatsapp' ); ?><span>WhatsApp'tan Bilgi Al</span><?php echo self::icon( 'arrow' ); ?></a>
            </div>

            <div class="st-footer-main">
                <div class="st-footer-grid">
                    <div class="st-footer-brand">
                        <img src="<?php echo esc_url( $logo ); ?>" alt="Server Turizm">
                        <p>1998 yılından bugüne edindiğimiz tecrübeyi, kaliteden ödün vermeden güvenli ve özenli seyahat hizmetlerine dönüştürüyoruz.</p>
                        <a class="st-footer-readmore" href="<?php echo esc_url( home_url( '/hakkimizda/' ) ); ?>">Tümünü Oku <?php echo self::icon( 'arrow' ); ?></a>
                        <div class="st-footer-socials">
                            <a href="https://instagram.com/servertur" target="_blank" rel="noopener noreferrer" aria-label="Instagram"><?php echo self::icon( 'instagram' ); ?></a>
                            <a href="https://facebook.com/servertur" target="_blank" rel="noopener noreferrer" aria-label="Facebook"><?php echo self::icon( 'facebook' ); ?></a>
                        </div>
                    </div>

                    <div class="st-footer-col">
                        <b>Programlar</b>
                        <a href="<?php echo esc_url( home_url( '/umre-1/' ) ); ?>">Umre Programları</a>
                        <a href="<?php echo esc_url( home_url( '/hac/' ) ); ?>">Hac Programları</a>
                        <a href="<?php echo esc_url( home_url( '/kultur-turlari/' ) ); ?>">Kültür Turları</a>
                        <a href="<?php echo esc_url( home_url( '/oteller/' ) ); ?>">Oteller</a>
                    </div>

                    <div class="st-footer-col">
                        <b>Hizmetler</b>
                        <a href="<?php echo esc_url( home_url( '/vize/' ) ); ?>">Vize Hizmetleri</a>
                        <a href="<?php echo esc_url( home_url( '/arac-kiralama/' ) ); ?>">Araç Kiralama</a>
                        <a href="<?php echo esc_url( home_url( '/foto-galeri/' ) ); ?>">Foto Galeri</a>
                        <a href="<?php echo esc_url( home_url( '/video-galeri/' ) ); ?>">Video Galeri</a>
                        <a href="<?php echo esc_url( home_url( '/letisim/' ) ); ?>">İletişim</a>
                    </div>

                    <div class="st-footer-col st-footer-contact">
                        <b>İletişim</b>
                        <a href="<?php echo esc_url( $map ); ?>" target="_blank" rel="noopener noreferrer"><?php echo self::icon( 'map' ); ?><span>Akşemsettin Mah. Akdeniz Cad.<br>No:6, Kat:4 Daire:7<br>Fatih / İstanbul</span></a>
                        <a href="tel:+902126210500"><?php echo self::icon( 'phone' ); ?><span>+90 212 621 05 00</span></a>
                        <a href="tel:+902126210600"><?php echo self::icon( 'phone' ); ?><span>+90 212 621 06 00</span></a>
                        <a href="mailto:server@serverturizm.com.tr"><?php echo self::icon( 'mail' ); ?><span>server@serverturizm.com.tr</span></a>
                        <p><?php echo self::icon( 'clock' ); ?><span>P.tesi–Cuma<br>09:00–18:00</span></p>
                    </div>
                </div>

                <div class="st-footer-bottom">
                    <span>© <?php echo esc_html( wp_date( 'Y' ) ); ?> Server Turizm. Tüm hakları saklıdır.</span>
                    <span>1998'den beri güvenli yolculuklar. <span aria-hidden="true">·</span> <a class="st-footer-credit" href="https://elahimiavagh.com/" target="_blank" rel="nofollow noopener noreferrer">ElahiMiavagh © 2026</a></span>
                </div>
            </div>
        </footer>

        <a class="st-whatsapp-float" href="<?php echo esc_url( $whatsapp ); ?>" target="_blank" rel="noopener noreferrer" aria-label="WhatsApp ile iletişime geç">
            <span>Tur danışmanına yazın</span><b><?php echo self::icon( 'whatsapp' ); ?></b>
        </a>
        <?php
    }
}

ST_Header_Footer::init();
