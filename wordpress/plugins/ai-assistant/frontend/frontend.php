<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Frontend assets.
 */
add_action('wp_enqueue_scripts', 'stai_enqueue_widget_assets');

function stai_enqueue_widget_assets() {
    if (is_admin()) {
        return;
    }

    if (!stai_get_bool_setting('widget_enabled', true)) {
        return;
    }

    $css_path = STAI_PLUGIN_DIR . 'assets/stai-widget.css';
    $js_path  = STAI_PLUGIN_DIR . 'assets/stai-widget.js';

    if (file_exists($css_path)) {
        wp_enqueue_style(
            'stai-widget-style',
            STAI_PLUGIN_URL . 'assets/stai-widget.css',
            [],
            filemtime($css_path)
        );
    }

    if (
        stai_get_bool_setting('recaptcha_enabled', false) &&
        defined('STAI_RECAPTCHA_SITE_KEY') &&
        !empty(STAI_RECAPTCHA_SITE_KEY)
    ) {
        wp_enqueue_script(
            'google-recaptcha-v3',
            'https://www.google.com/recaptcha/api.js?render=' . rawurlencode(STAI_RECAPTCHA_SITE_KEY),
            [],
            null,
            true
        );
    }

    if (file_exists($js_path)) {
        wp_enqueue_script(
            'stai-widget-script',
            STAI_PLUGIN_URL . 'assets/stai-widget.js',
            [],
            filemtime($js_path),
            true
        );

        wp_localize_script(
            'stai-widget-script',
            'STAI_WIDGET_CONFIG',
            [
                'endpoint' => esc_url_raw(rest_url('serverturizm-ai/v1/chat')),
                'trackEndpoint' => esc_url_raw(rest_url('serverturizm-ai/v1/track')),
                'leadEndpoint' => esc_url_raw(rest_url('serverturizm-ai/v1/lead')),
                'whatsappUrl' => stai_get_whatsapp_url('Merhaba, umre programları hakkında bilgi almak istiyorum.'),
                'recaptchaSiteKey' => defined('STAI_RECAPTCHA_SITE_KEY') ? STAI_RECAPTCHA_SITE_KEY : '',
                'recaptchaEnabled' => stai_get_bool_setting('recaptcha_enabled', false),
            ]
        );
    }
}

/**
 * Frontend widget HTML.
 */
add_action('wp_footer', 'stai_output_floating_assistant');

function stai_output_floating_assistant() {
    if (is_admin() || wp_doing_ajax()) {
        return;
    }

    if (!stai_get_bool_setting('widget_enabled', true)) {
        return;
    }

    $widget_title = stai_get_setting('widget_title', 'Server Turizm Asistanı');
    $widget_subtitle = stai_get_setting('widget_subtitle', 'Programlarımız hakkında bilgi alın');
    $welcome_message = stai_get_setting('welcome_message', "Merhaba 👋\nUmre programları, fiyatlar, çocuk ücretleri ve tarih bilgileri hakkında yardımcı olabilirim.");
    $primary_color = stai_get_setting('primary_color', '#ab8726');
    $secondary_color = stai_get_setting('secondary_color', '#bd933e');

    $whatsapp_url = stai_get_whatsapp_url('Merhaba, umre programları hakkında bilgi almak istiyorum.');
    $public_faq_buttons = function_exists('stai_get_public_faq_buttons')
        ? stai_get_public_faq_buttons()
        : [];
    ?>
    <div id="stai-floating-root" style="--stai-primary: <?php echo esc_attr($primary_color); ?>; --stai-secondary: <?php echo esc_attr($secondary_color); ?>;">
        <button id="stai-launcher" aria-label="Open assistant">
            <span class="stai-launcher-icon">
                <svg width="26" height="26" viewBox="0 0 24 24" fill="none">
                    <path d="M8 10H16M8 14H13M7 19L3 21V5C3 3.89543 3.89543 3 5 3H19C20.1046 3 21 3.89543 21 5V17C21 18.1046 20.1046 19 19 19H7Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </span>
        </button>

        <div id="stai-panel" aria-hidden="true">
            <div id="stai-panel-header">
                <div class="stai-head-left">
                    <div class="stai-avatar">AI</div>
                    <div class="stai-head-text">
                        <strong><?php echo esc_html($widget_title); ?></strong>
                        <span><?php echo esc_html($widget_subtitle); ?></span>
                    </div>
                </div>

                <div class="stai-head-actions">
                    <button type="button" id="stai-clear" aria-label="Clear chat" title="Sohbeti temizle">↻</button>
                    <button type="button" id="stai-minimize" aria-label="Minimize">—</button>
                    <button type="button" id="stai-close" aria-label="Close">×</button>
                </div>
            </div>

            <div id="stai-messages">
                <div class="stai-msg stai-bot">
                    <div class="stai-msg-text">
                        <?php echo nl2br(esc_html($welcome_message)); ?>
                    </div>

                    <div class="stai-suggested">
                        <button type="button" class="stai-chip" data-message="Mevcut programları göster">Mevcut Programlar</button>
                        <button type="button" class="stai-chip" data-message="Lüks programları listele">Lüks Programlar</button>
                        <button type="button" class="stai-chip" data-message="Ekonomik programları göster">Ekonomik Programlar</button>
                        <button type="button" class="stai-chip" data-message="En uygun program hangisi?">En Uygun Program</button>

                        <?php if (!empty($public_faq_buttons)) : ?>
                            <button
                                type="button"
                                class="stai-chip stai-chip-faq-toggle"
                                data-faq-toggle="1"
                                aria-expanded="false"
                            >
                                Sık Sorulan Sorular
                            </button>

                            <div id="stai-faq-menu" class="stai-faq-menu" hidden>
                                <?php foreach ($public_faq_buttons as $faq_button) : ?>
                                    <button
                                        type="button"
                                        class="stai-chip stai-chip-faq"
                                        data-message="<?php echo esc_attr($faq_button['question']); ?>"
                                    >
                                        <?php echo esc_html($faq_button['label']); ?>
                                    </button>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <button type="button" class="stai-chip stai-chip-lead" data-lead-open="1">Beni Arayın</button>

                        <?php if (!empty($whatsapp_url)) : ?>
                            <a class="stai-chip stai-chip-wa" href="<?php echo esc_url($whatsapp_url); ?>" target="_blank" rel="noopener noreferrer">WhatsApp</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div id="stai-composer">
                <div id="stai-input-wrap">
                    <input id="stai-input" type="text" placeholder="Sorunuzu yazın..." autocomplete="off" />
                    <button type="button" id="stai-send" aria-label="Send">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
                            <path d="M22 2L11 13" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M22 2L15 22L11 13L2 9L22 2Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <style>
        #stai-floating-root .stai-faq-menu {
            display: none;
            width: 100%;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 2px;
        }

        #stai-floating-root .stai-faq-menu.is-open {
            display: flex;
        }

        #stai-floating-root .stai-faq-menu[hidden] {
            display: none !important;
        }

        #stai-floating-root .stai-chip-faq-toggle {
            font-weight: 700;
        }

        #stai-floating-root .stai-chip-faq {
            font-size: 13px;
        }
    </style>

    <script>
        (function () {
            document.addEventListener('click', function (event) {
                var toggle = event.target.closest('[data-faq-toggle="1"]');

                if (!toggle) {
                    return;
                }

                event.preventDefault();
                event.stopPropagation();

                if (typeof event.stopImmediatePropagation === 'function') {
                    event.stopImmediatePropagation();
                }

                var faqMenu = document.getElementById('stai-faq-menu');

                if (!faqMenu) {
                    return;
                }

                var isOpen = faqMenu.classList.contains('is-open');

                if (isOpen) {
                    faqMenu.classList.remove('is-open');
                    faqMenu.hidden = true;
                    toggle.setAttribute('aria-expanded', 'false');
                } else {
                    faqMenu.hidden = false;
                    faqMenu.classList.add('is-open');
                    toggle.setAttribute('aria-expanded', 'true');
                }
            }, true);
        })();
    </script>
    <?php
}
