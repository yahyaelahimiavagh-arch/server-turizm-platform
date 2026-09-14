<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * WordPress admin dashboard and settings.
 */
add_action('admin_menu', 'stai_register_admin_menu');
add_action('admin_post_stai_save_settings', 'stai_save_settings_handler');

function stai_register_admin_menu() {
    add_menu_page(
        'Server Turizm AI',
        'Server Turizm AI',
        'manage_options',
        'stai-dashboard',
        'stai_render_admin_dashboard',
        'dashicons-format-chat',
        56
    );

    add_submenu_page(
        'stai-dashboard',
        'Dashboard',
        'Dashboard',
        'manage_options',
        'stai-dashboard',
        'stai_render_admin_dashboard'
    );

    add_submenu_page(
        'stai-dashboard',
        'Settings',
        'Settings',
        'manage_options',
        'stai-settings',
        'stai_render_settings_page'
    );
    
    add_submenu_page(
    'stai-dashboard',
    'Tools',
    'Tools',
    'manage_options',
    'stai-tools',
    'stai_render_tools_page'
    );
}

function stai_get_settings_defaults() {
    return [
        'widget_enabled' => '1',
        'whatsapp_number' => defined('STAI_WHATSAPP_NUMBER') ? STAI_WHATSAPP_NUMBER : '',
        'lead_email' => defined('STAI_LEAD_EMAIL') ? STAI_LEAD_EMAIL : '',
        'primary_color' => '#ab8726',
        'secondary_color' => '#bd933e',
        'widget_title' => 'Server Turizm Asistanı',
        'widget_subtitle' => 'Programlarımız hakkında bilgi alın',
        'welcome_message' => "Merhaba 👋\nUmre programları, fiyatlar, çocuk ücretleri ve tarih bilgileri hakkında yardımcı olabilirim.",
        'gemini_enabled' => '1',
        'gemini_quota_message' => 'AI cevabı şu anda Gemini kullanım kotası nedeniyle geçici olarak kullanılamıyor.',
        'chat_rate_limit_enabled' => '1',
'chat_max_messages' => '8',
'chat_window_minutes' => '10',
'chat_min_interval_seconds' => '4',
'duplicate_block_seconds' => '12',

'lead_rate_limit_enabled' => '1',
'lead_max_submissions' => '2',
'lead_window_minutes' => '60',

'recaptcha_enabled' => '0',
'recaptcha_min_score' => '0.5',
    ];
}

function stai_get_settings() {
    $saved = get_option('stai_settings', []);

    if (!is_array($saved)) {
        $saved = [];
    }

    return wp_parse_args($saved, stai_get_settings_defaults());
}

function stai_sanitize_color($value, $default) {
    $value = sanitize_hex_color($value);
    return !empty($value) ? $value : $default;
}

function stai_save_settings_handler() {
    if (!current_user_can('manage_options')) {
        wp_die('You do not have permission to save these settings.');
    }

    check_admin_referer('stai_save_settings_action');

    $defaults = stai_get_settings_defaults();

    $settings = [
        'widget_enabled' => isset($_POST['widget_enabled']) ? '1' : '0',

        'whatsapp_number' => isset($_POST['whatsapp_number'])
            ? sanitize_text_field(wp_unslash($_POST['whatsapp_number']))
            : '',

        'lead_email' => isset($_POST['lead_email'])
            ? sanitize_email(wp_unslash($_POST['lead_email']))
            : '',

        'primary_color' => isset($_POST['primary_color'])
            ? stai_sanitize_color(wp_unslash($_POST['primary_color']), $defaults['primary_color'])
            : $defaults['primary_color'],

        'secondary_color' => isset($_POST['secondary_color'])
            ? stai_sanitize_color(wp_unslash($_POST['secondary_color']), $defaults['secondary_color'])
            : $defaults['secondary_color'],

        'widget_title' => isset($_POST['widget_title'])
            ? sanitize_text_field(wp_unslash($_POST['widget_title']))
            : $defaults['widget_title'],

        'widget_subtitle' => isset($_POST['widget_subtitle'])
            ? sanitize_text_field(wp_unslash($_POST['widget_subtitle']))
            : $defaults['widget_subtitle'],

        'welcome_message' => isset($_POST['welcome_message'])
            ? sanitize_textarea_field(wp_unslash($_POST['welcome_message']))
            : $defaults['welcome_message'],

        'gemini_enabled' => isset($_POST['gemini_enabled']) ? '1' : '0',

        'gemini_quota_message' => isset($_POST['gemini_quota_message'])
            ? sanitize_textarea_field(wp_unslash($_POST['gemini_quota_message']))
            : $defaults['gemini_quota_message'],
            
            'chat_rate_limit_enabled' => isset($_POST['chat_rate_limit_enabled']) ? '1' : '0',

'chat_max_messages' => isset($_POST['chat_max_messages'])
    ? (string) max(1, min(100, absint($_POST['chat_max_messages'])))
    : $defaults['chat_max_messages'],

'chat_window_minutes' => isset($_POST['chat_window_minutes'])
    ? (string) max(1, min(1440, absint($_POST['chat_window_minutes'])))
    : $defaults['chat_window_minutes'],

'chat_min_interval_seconds' => isset($_POST['chat_min_interval_seconds'])
    ? (string) max(0, min(60, absint($_POST['chat_min_interval_seconds'])))
    : $defaults['chat_min_interval_seconds'],

'duplicate_block_seconds' => isset($_POST['duplicate_block_seconds'])
    ? (string) max(0, min(300, absint($_POST['duplicate_block_seconds'])))
    : $defaults['duplicate_block_seconds'],

'lead_rate_limit_enabled' => isset($_POST['lead_rate_limit_enabled']) ? '1' : '0',

'lead_max_submissions' => isset($_POST['lead_max_submissions'])
    ? (string) max(1, min(20, absint($_POST['lead_max_submissions'])))
    : $defaults['lead_max_submissions'],

'lead_window_minutes' => isset($_POST['lead_window_minutes'])
    ? (string) max(1, min(1440, absint($_POST['lead_window_minutes'])))
    : $defaults['lead_window_minutes'],

'recaptcha_enabled' => isset($_POST['recaptcha_enabled']) ? '1' : '0',

'recaptcha_min_score' => isset($_POST['recaptcha_min_score'])
    ? (string) max(0.1, min(1, (float) wp_unslash($_POST['recaptcha_min_score'])))
    : $defaults['recaptcha_min_score'],
    
    ];

    update_option('stai_settings', $settings, false);

    wp_safe_redirect(
        add_query_arg(
            [
                'page' => 'stai-settings',
                'updated' => '1',
            ],
            admin_url('admin.php')
        )
    );
    exit;
}

function stai_render_admin_dashboard() {
    if (!current_user_can('manage_options')) {
        wp_die('You do not have permission to access this page.');
    }

    stai_maybe_create_log_table();

    $today_start = date('Y-m-d 00:00:00', current_time('timestamp'));
    $week_start = date('Y-m-d 00:00:00', current_time('timestamp') - 7 * DAY_IN_SECONDS);

    $today_messages = stai_admin_get_count('message', $today_start);
    $today_whatsapp = stai_admin_get_count('whatsapp_click', $today_start);
    $today_opens = stai_admin_get_count('widget_open', $today_start);
    $today_leads = stai_admin_get_count('lead', $today_start);

    $week_messages = stai_admin_get_count('message', $week_start);
    $week_whatsapp = stai_admin_get_count('whatsapp_click', $week_start);
    $week_opens = stai_admin_get_count('widget_open', $week_start);
    $week_leads = stai_admin_get_count('lead', $week_start);

    $recent_messages = stai_admin_get_recent_messages(20);
    $recent_leads = stai_admin_get_recent_leads(20);
    $daily_stats = function_exists('stai_admin_get_daily_stats') ? stai_admin_get_daily_stats(7) : [];
    $top_programs = function_exists('stai_admin_get_top_programs') ? stai_admin_get_top_programs(10) : [];

    $program_count = function_exists('stai_get_programs_from_sheet') ? count(stai_get_programs_from_sheet()) : 0;
    $faq_count = function_exists('stai_get_faqs_from_sheet') ? count(stai_get_faqs_from_sheet()) : 0;
    $cooldown_remaining = function_exists('stai_gemini_get_cooldown_remaining') ? stai_gemini_get_cooldown_remaining() : 0;

    $gemini_status = 'Ready';

    if (!defined('STAI_GEMINI_API_KEY') || empty(STAI_GEMINI_API_KEY)) {
        $gemini_status = 'API Key Missing';
    } elseif ($cooldown_remaining > 0) {
        $gemini_status = 'Cooldown';
    }
    ?>
    <div class="wrap stai-admin-wrap">
        <h1>Server Turizm AI Dashboard</h1>
        <p>Chatbot mesajları, kullanıcı talepleri, WhatsApp tıklamaları ve sistem durumunu buradan takip edebilirsiniz.</p>

        <style>
            .stai-admin-cards {
                display: grid;
                grid-template-columns: repeat(4, minmax(160px, 1fr));
                gap: 14px;
                margin: 20px 0;
            }

            .stai-admin-card,
            .stai-admin-box {
                background: #fff;
                border: 1px solid #dcdcde;
                border-radius: 12px;
                padding: 16px;
                box-shadow: 0 1px 2px rgba(0,0,0,.04);
            }

            .stai-admin-card span {
                display: block;
                color: #646970;
                font-size: 13px;
                margin-bottom: 8px;
            }

            .stai-admin-card strong {
                font-size: 28px;
                line-height: 1;
            }

            .stai-admin-grid {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 18px;
                margin-top: 20px;
            }

            .stai-admin-box h2 {
                margin-top: 0;
            }

            .stai-admin-table {
                width: 100%;
                border-collapse: collapse;
            }

            .stai-admin-table th,
            .stai-admin-table td {
                border-bottom: 1px solid #eee;
                padding: 8px;
                text-align: left;
                vertical-align: top;
                font-size: 13px;
            }

            .stai-admin-muted {
                color: #646970;
                font-size: 12px;
            }

            .stai-status-pill {
                display: inline-block;
                padding: 4px 10px;
                border-radius: 999px;
                font-size: 12px;
                font-weight: 700;
                background: #f0f0f1;
            }

            .stai-status-ok {
                background: #edfaef;
                color: #008a20;
            }

            .stai-status-warn {
                background: #fcf9e8;
                color: #996800;
            }

            .stai-status-bad {
                background: #fcf0f1;
                color: #b32d2e;
            }

            .stai-bar-row {
                display: grid;
                grid-template-columns: 90px 1fr 40px;
                gap: 10px;
                align-items: center;
                margin: 8px 0;
            }

            .stai-bar-track {
                background: #f0f0f1;
                height: 8px;
                border-radius: 999px;
                overflow: hidden;
            }

            .stai-bar-fill {
                background: #2271b1;
                height: 8px;
                border-radius: 999px;
            }

            @media (max-width: 1100px) {
                .stai-admin-cards {
                    grid-template-columns: repeat(2, 1fr);
                }

                .stai-admin-grid {
                    grid-template-columns: 1fr;
                }
            }
        </style>

        <div class="stai-admin-cards">
            <div class="stai-admin-card">
                <span>Bugünkü Mesajlar</span>
                <strong><?php echo esc_html($today_messages); ?></strong>
            </div>

            <div class="stai-admin-card">
                <span>Bugünkü Lead</span>
                <strong><?php echo esc_html($today_leads); ?></strong>
            </div>

            <div class="stai-admin-card">
                <span>Bugünkü WhatsApp</span>
                <strong><?php echo esc_html($today_whatsapp); ?></strong>
            </div>

            <div class="stai-admin-card">
                <span>Bugünkü Widget Açılışı</span>
                <strong><?php echo esc_html($today_opens); ?></strong>
            </div>

            <div class="stai-admin-card">
                <span>7 Gün Mesaj</span>
                <strong><?php echo esc_html($week_messages); ?></strong>
            </div>

            <div class="stai-admin-card">
                <span>7 Gün Lead</span>
                <strong><?php echo esc_html($week_leads); ?></strong>
            </div>

            <div class="stai-admin-card">
                <span>7 Gün WhatsApp</span>
                <strong><?php echo esc_html($week_whatsapp); ?></strong>
            </div>

            <div class="stai-admin-card">
                <span>7 Gün Widget Açılışı</span>
                <strong><?php echo esc_html($week_opens); ?></strong>
            </div>
        </div>

        <div class="stai-admin-grid">
            <div class="stai-admin-box">
                <h2>Sistem Durumu</h2>

                <table class="stai-admin-table">
                    <tbody>
                        <tr>
                            <td>Program Sayısı</td>
                            <td><strong><?php echo esc_html($program_count); ?></strong></td>
                        </tr>

                        <tr>
                            <td>FAQ Sayısı</td>
                            <td><strong><?php echo esc_html($faq_count); ?></strong></td>
                        </tr>

                        <tr>
                            <td>Gemini Durumu</td>
                            <td>
                                <?php
                                $status_class = 'stai-status-ok';

                                if ($gemini_status === 'Cooldown') {
                                    $status_class = 'stai-status-warn';
                                }

                                if ($gemini_status === 'API Key Missing') {
                                    $status_class = 'stai-status-bad';
                                }
                                ?>
                                <span class="stai-status-pill <?php echo esc_attr($status_class); ?>">
                                    <?php echo esc_html($gemini_status); ?>
                                </span>

                                <?php if ($cooldown_remaining > 0) : ?>
                                    <br>
                                    <span class="stai-admin-muted">
                                        Remaining: <?php echo esc_html($cooldown_remaining); ?> sec
                                    </span>
                                <?php endif; ?>
                            </td>
                        </tr>

                        <tr>
                            <td>Tools</td>
                            <td>
                                <a class="button button-secondary" href="<?php echo esc_url(admin_url('admin.php?page=stai-tools')); ?>">
                                    Open Tools
                                </a>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="stai-admin-box">
                <h2>7 Günlük Aktivite</h2>

                <table class="stai-admin-table">
                    <thead>
                        <tr>
                            <th>Tarih</th>
                            <th>Mesaj</th>
                            <th>Lead</th>
                            <th>WhatsApp</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($daily_stats as $day) : ?>
                            <tr>
                                <td><?php echo esc_html($day['date']); ?></td>
                                <td><?php echo esc_html($day['message']); ?></td>
                                <td><?php echo esc_html($day['lead']); ?></td>
                                <td><?php echo esc_html($day['whatsapp_click']); ?></td>
                            </tr>
                        <?php endforeach; ?>

                        <?php if (empty($daily_stats)) : ?>
                            <tr>
                                <td colspan="4">Henüz veri yok.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="stai-admin-grid">
            <div class="stai-admin-box">
                <h2>Popüler Programlar</h2>

                <?php
                $max_program_total = 0;

                foreach ($top_programs as $program_row) {
                    $max_program_total = max($max_program_total, (int) $program_row['total']);
                }
                ?>

                <?php if (empty($top_programs)) : ?>
                    <p>Henüz program etkileşimi yok.</p>
                <?php else : ?>
                    <?php foreach ($top_programs as $program_row) : ?>
                        <?php
                        $total = (int) $program_row['total'];
                        $percent = $max_program_total > 0 ? round(($total / $max_program_total) * 100) : 0;
                        ?>
                        <div class="stai-bar-row">
                            <strong><?php echo esc_html($program_row['program_no']); ?></strong>
                            <div class="stai-bar-track">
                                <div class="stai-bar-fill" style="width: <?php echo esc_attr($percent); ?>%;"></div>
                            </div>
                            <span><?php echo esc_html($total); ?></span>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <div class="stai-admin-box">
                <h2>Son Kullanıcı Talepleri</h2>

                <table class="stai-admin-table">
                    <thead>
                        <tr>
                            <th>Tarih</th>
                            <th>Ad / Telefon</th>
                            <th>Program</th>
                            <th>Not</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($recent_leads)) : ?>
                        <tr>
                            <td colspan="4">Henüz lead yok.</td>
                        </tr>
                    <?php else : ?>
                        <?php foreach ($recent_leads as $lead) : ?>
                            <?php
                            $meta = json_decode($lead['meta'], true);

                            if (!is_array($meta)) {
                                $meta = [];
                            }

                            $name = isset($meta['name']) ? $meta['name'] : '';
                            $phone = isset($meta['phone']) ? $meta['phone'] : '';
                            $note = isset($meta['note']) ? $meta['note'] : $lead['message'];
                            ?>
                            <tr>
                                <td>
                                    <?php echo esc_html($lead['created_at']); ?>
                                    <?php if (!empty($lead['page_url'])) : ?>
                                        <br>
                                        <a class="stai-admin-muted" href="<?php echo esc_url($lead['page_url']); ?>" target="_blank" rel="noopener noreferrer">Sayfa</a>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <strong><?php echo esc_html($name); ?></strong><br>
                                    <?php echo esc_html($phone); ?>
                                </td>
                                <td><?php echo esc_html($lead['program_no']); ?></td>
                                <td><?php echo esc_html($note); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="stai-admin-box" style="margin-top: 18px;">
            <h2>Son Mesajlar</h2>

            <table class="stai-admin-table">
                <thead>
                    <tr>
                        <th>Tarih</th>
                        <th>Dil</th>
                        <th>Mesaj</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($recent_messages)) : ?>
                    <tr>
                        <td colspan="3">Henüz mesaj yok.</td>
                    </tr>
                <?php else : ?>
                    <?php foreach ($recent_messages as $msg) : ?>
                        <tr>
                            <td>
                                <?php echo esc_html($msg['created_at']); ?>
                                <?php if (!empty($msg['page_url'])) : ?>
                                    <br>
                                    <a class="stai-admin-muted" href="<?php echo esc_url($msg['page_url']); ?>" target="_blank" rel="noopener noreferrer">Sayfa</a>
                                <?php endif; ?>
                            </td>
                            <td><?php echo esc_html($msg['language']); ?></td>
                            <td><?php echo esc_html($msg['message']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php
}


function stai_render_settings_page() {
    if (!current_user_can('manage_options')) {
        wp_die('You do not have permission to access this page.');
    }

    $settings = stai_get_settings();

    $api_key_configured = defined('STAI_GEMINI_API_KEY') && !empty(STAI_GEMINI_API_KEY);
    $sheet_configured = defined('STAI_SHEET_CSV_URL') && !empty(STAI_SHEET_CSV_URL);
    $faq_configured = defined('STAI_FAQ_CSV_URL') && !empty(STAI_FAQ_CSV_URL);
    ?>
    <div class="wrap stai-settings-wrap">
        <h1>Server Turizm AI Settings</h1>

        <?php if (!empty($_GET['updated'])) : ?>
            <div class="notice notice-success is-dismissible">
                <p>Ayarlar kaydedildi.</p>
            </div>
        <?php endif; ?>

        <style>
            .stai-settings-grid {
                display: grid;
                grid-template-columns: minmax(0, 1fr) 340px;
                gap: 18px;
                margin-top: 20px;
            }
            .stai-settings-panel {
                background: #fff;
                border: 1px solid #dcdcde;
                border-radius: 12px;
                padding: 18px;
                margin-bottom: 18px;
            }
            .stai-settings-panel h2 {
                margin-top: 0;
            }
            .stai-setting-field {
                display: block;
                margin-bottom: 16px;
            }
            .stai-setting-field span {
                display: block;
                font-weight: 600;
                margin-bottom: 6px;
            }
            .stai-setting-field input[type="text"],
            .stai-setting-field input[type="email"],
            .stai-setting-field input[type="number"],
            .stai-setting-field textarea {
                width: 100%;
                max-width: 720px;
            }
            .stai-setting-field small {
                display: block;
                color: #646970;
                margin-top: 5px;
            }
            .stai-color-preview {
                height: 56px;
                border-radius: 12px;
                color: #fff;
                display: flex;
                align-items: center;
                justify-content: center;
                font-weight: 700;
                margin-top: 8px;
            }
            .stai-status-row {
                display: flex;
                justify-content: space-between;
                gap: 10px;
                padding: 10px 0;
                border-bottom: 1px solid #eee;
            }
            .stai-status-ok {
                color: #008a20;
                font-weight: 700;
            }
            .stai-status-bad {
                color: #b32d2e;
                font-weight: 700;
            }
            @media (max-width: 1000px) {
                .stai-settings-grid {
                    grid-template-columns: 1fr;
                }
            }
        </style>

        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <?php wp_nonce_field('stai_save_settings_action'); ?>
            <input type="hidden" name="action" value="stai_save_settings">

            <div class="stai-settings-grid">
                <div>
                    <div class="stai-settings-panel">
                        <h2>Widget Durumu</h2>
                        <label>
                            <input type="checkbox" name="widget_enabled" value="1" <?php checked($settings['widget_enabled'], '1'); ?>>
                            Widget aktif
                        </label>
                    </div>

                    <div class="stai-settings-panel">
                        <h2>Widget Metinleri</h2>

                        <label class="stai-setting-field">
                            <span>Widget Başlığı</span>
                            <input type="text" name="widget_title" value="<?php echo esc_attr($settings['widget_title']); ?>">
                        </label>

                        <label class="stai-setting-field">
                            <span>Widget Alt Başlığı</span>
                            <input type="text" name="widget_subtitle" value="<?php echo esc_attr($settings['widget_subtitle']); ?>">
                        </label>

                        <label class="stai-setting-field">
                            <span>Karşılama Mesajı</span>
                            <textarea name="welcome_message" rows="5"><?php echo esc_textarea($settings['welcome_message']); ?></textarea>
                        </label>
                    </div>

                    <div class="stai-settings-panel">
                        <h2>Renkler</h2>

                        <label class="stai-setting-field">
                            <span>Ana Renk</span>
                            <input type="color" name="primary_color" value="<?php echo esc_attr($settings['primary_color']); ?>">
                        </label>

                        <label class="stai-setting-field">
                            <span>İkinci Renk</span>
                            <input type="color" name="secondary_color" value="<?php echo esc_attr($settings['secondary_color']); ?>">
                        </label>

                        <div class="stai-color-preview" style="background: linear-gradient(135deg, <?php echo esc_attr($settings['primary_color']); ?> 0%, <?php echo esc_attr($settings['secondary_color']); ?> 100%);">
                            Önizleme
                        </div>
                    </div>

                    <div class="stai-settings-panel">
                        <h2>İletişim</h2>

                        <label class="stai-setting-field">
                            <span>WhatsApp Numarası</span>
                            <input type="text" name="whatsapp_number" value="<?php echo esc_attr($settings['whatsapp_number']); ?>" placeholder="905XXXXXXXXX">
                            <small>Başında + olmadan yaz. Örnek: 905321234567</small>
                        </label>

                        <label class="stai-setting-field">
                            <span>Lead E-posta</span>
                            <input type="email" name="lead_email" value="<?php echo esc_attr($settings['lead_email']); ?>" placeholder="info@serverturizm.com.tr">
                            <small>Boş bırakırsan lead sadece panelde kaydedilir.</small>
                        </label>
                    </div>

                    <div class="stai-settings-panel">
                        <h2>Gemini</h2>

                        <label>
                            <input type="checkbox" name="gemini_enabled" value="1" <?php checked($settings['gemini_enabled'], '1'); ?>>
                            Gemini fallback aktif
                        </label>

                        <label class="stai-setting-field" style="margin-top: 16px;">
                            <span>Kota / Hata Mesajı</span>
                            <textarea name="gemini_quota_message" rows="4"><?php echo esc_textarea($settings['gemini_quota_message']); ?></textarea>
                            <small>Gemini quota biterse veya API cevap vermezse kullanıcıya gösterilecek mesaj.</small>
                        </label>
                    </div>

                    <div class="stai-settings-panel">
                        <h2>Anti-Spam & Rate Limit</h2>

                        <label>
                            <input type="checkbox" name="chat_rate_limit_enabled" value="1" <?php checked($settings['chat_rate_limit_enabled'], '1'); ?>>
                            Chat mesaj limiti aktif
                        </label>

                        <label class="stai-setting-field" style="margin-top: 16px;">
                            <span>Mesaj Limiti</span>
                            <input type="number" name="chat_max_messages" value="<?php echo esc_attr($settings['chat_max_messages']); ?>" min="1" max="100">
                            <small>Öneri: 8 mesaj.</small>
                        </label>

                        <label class="stai-setting-field">
                            <span>Zaman Aralığı / Dakika</span>
                            <input type="number" name="chat_window_minutes" value="<?php echo esc_attr($settings['chat_window_minutes']); ?>" min="1" max="1440">
                            <small>Örnek: 10 dakika içinde 8 mesaj.</small>
                        </label>

                        <label class="stai-setting-field">
                            <span>Mesajlar Arası Minimum Süre / Saniye</span>
                            <input type="number" name="chat_min_interval_seconds" value="<?php echo esc_attr($settings['chat_min_interval_seconds']); ?>" min="0" max="60">
                            <small>Arka arkaya mesajı engeller. Öneri: 4 saniye.</small>
                        </label>

                        <label class="stai-setting-field">
                            <span>Tekrarlı Mesaj Blok Süresi / Saniye</span>
                            <input type="number" name="duplicate_block_seconds" value="<?php echo esc_attr($settings['duplicate_block_seconds']); ?>" min="0" max="300">
                            <small>Aynı mesajın tekrar gönderilmesini engeller. Öneri: 12 saniye.</small>
                        </label>

                        <hr>

                        <label>
                            <input type="checkbox" name="lead_rate_limit_enabled" value="1" <?php checked($settings['lead_rate_limit_enabled'], '1'); ?>>
                            Lead form limiti aktif
                        </label>

                        <label class="stai-setting-field" style="margin-top: 16px;">
                            <span>Lead Form Limiti</span>
                            <input type="number" name="lead_max_submissions" value="<?php echo esc_attr($settings['lead_max_submissions']); ?>" min="1" max="20">
                            <small>Öneri: 2 form.</small>
                        </label>

                        <label class="stai-setting-field">
                            <span>Lead Zaman Aralığı / Dakika</span>
                            <input type="number" name="lead_window_minutes" value="<?php echo esc_attr($settings['lead_window_minutes']); ?>" min="1" max="1440">
                            <small>Örnek: 60 dakika içinde 2 lead.</small>
                        </label>

                        <hr>

                        <label>
                            <input type="checkbox" name="recaptcha_enabled" value="1" <?php checked($settings['recaptcha_enabled'], '1'); ?>>
                            reCAPTCHA aktif
                        </label>

                        <label class="stai-setting-field" style="margin-top: 16px;">
                            <span>reCAPTCHA Minimum Score</span>
                            <input type="number" step="0.1" name="recaptcha_min_score" value="<?php echo esc_attr($settings['recaptcha_min_score']); ?>" min="0.1" max="1">
                            <small>Google reCAPTCHA v3 için öneri: 0.5</small>
                        </label>

                        <p class="description">
                            reCAPTCHA aktif edilmeden önce site key ve secret key <code>wp-config.php</code> içine eklenmelidir.
                        </p>
                    </div>

                    <?php submit_button('Ayarları Kaydet'); ?>
                </div>

                <div>
                    <div class="stai-settings-panel">
                        <h2>Sistem Durumu</h2>

                        <div class="stai-status-row">
                            <span>Google Sheet</span>
                            <strong class="<?php echo $sheet_configured ? 'stai-status-ok' : 'stai-status-bad'; ?>">
                                <?php echo $sheet_configured ? 'OK' : 'Eksik'; ?>
                            </strong>
                        </div>

                        <div class="stai-status-row">
                            <span>FAQ Sheet</span>
                            <strong class="<?php echo $faq_configured ? 'stai-status-ok' : 'stai-status-bad'; ?>">
                                <?php echo $faq_configured ? 'OK' : 'Eksik'; ?>
                            </strong>
                        </div>

                        <div class="stai-status-row">
                            <span>Gemini API Key</span>
                            <strong class="<?php echo $api_key_configured ? 'stai-status-ok' : 'stai-status-bad'; ?>">
                                <?php echo $api_key_configured ? 'OK' : 'Eksik'; ?>
                            </strong>
                        </div>

                        <p style="margin-top: 14px;">
                            API key ve Sheet URL bilgileri güvenlik için <code>wp-config.php</code> içinde kalmalı.
                        </p>
                    </div>
                </div>
            </div>
        </form>
    </div>
    <?php
}


/**
 * Tools page actions.
 */
add_action('admin_post_stai_clear_program_cache', 'stai_clear_program_cache_handler');
add_action('admin_post_stai_clear_faq_cache', 'stai_clear_faq_cache_handler');
add_action('admin_post_stai_reset_gemini_cooldown', 'stai_reset_gemini_cooldown_handler');

function stai_tools_redirect($message_key) {
    wp_safe_redirect(
        add_query_arg(
            [
                'page' => 'stai-tools',
                'message' => $message_key,
            ],
            admin_url('admin.php')
        )
    );
    exit;
}

function stai_clear_program_cache_handler() {
    if (!current_user_can('manage_options')) {
        wp_die('You do not have permission to do this.');
    }

    check_admin_referer('stai_clear_program_cache_action');

    delete_transient('stai_programs_cache');

    stai_tools_redirect('program_cache_cleared');
}

function stai_clear_faq_cache_handler() {
    if (!current_user_can('manage_options')) {
        wp_die('You do not have permission to do this.');
    }

    check_admin_referer('stai_clear_faq_cache_action');

    delete_transient('stai_faq_cache');
    delete_transient('stai_faq_public_buttons_cache');

    stai_tools_redirect('faq_cache_cleared');
}

function stai_reset_gemini_cooldown_handler() {
    if (!current_user_can('manage_options')) {
        wp_die('You do not have permission to do this.');
    }

    check_admin_referer('stai_reset_gemini_cooldown_action');

    if (function_exists('stai_gemini_cooldown_key')) {
        delete_transient(stai_gemini_cooldown_key());
    } else {
        delete_transient('stai_gemini_cooldown_until');
    }

    stai_tools_redirect('gemini_cooldown_reset');
}

function stai_render_tools_page() {
    if (!current_user_can('manage_options')) {
        wp_die('You do not have permission to access this page.');
    }

    $program_count = function_exists('stai_get_programs_from_sheet') ? count(stai_get_programs_from_sheet()) : 0;
    $faq_count = function_exists('stai_get_faqs_from_sheet') ? count(stai_get_faqs_from_sheet()) : 0;
    $cooldown_remaining = function_exists('stai_gemini_get_cooldown_remaining') ? stai_gemini_get_cooldown_remaining() : 0;

    $message = isset($_GET['message']) ? sanitize_key($_GET['message']) : '';
    ?>
    <div class="wrap stai-tools-wrap">
        <h1>Server Turizm AI Tools</h1>
        <p>Cache ve Gemini cooldown durumunu buradan yönetebilirsiniz.</p>

        <?php if ($message === 'program_cache_cleared') : ?>
            <div class="notice notice-success is-dismissible"><p>Program cache temizlendi.</p></div>
        <?php elseif ($message === 'faq_cache_cleared') : ?>
            <div class="notice notice-success is-dismissible"><p>FAQ cache temizlendi.</p></div>
        <?php elseif ($message === 'gemini_cooldown_reset') : ?>
            <div class="notice notice-success is-dismissible"><p>Gemini cooldown sıfırlandı.</p></div>
        <?php endif; ?>

        <style>
            .stai-tools-grid {
                display: grid;
                grid-template-columns: repeat(3, minmax(220px, 1fr));
                gap: 16px;
                margin-top: 20px;
            }

            .stai-tools-card {
                background: #fff;
                border: 1px solid #dcdcde;
                border-radius: 12px;
                padding: 18px;
            }

            .stai-tools-card h2 {
                margin-top: 0;
            }

            .stai-tools-number {
                font-size: 28px;
                font-weight: 700;
                margin: 8px 0 14px;
            }

            .stai-tools-muted {
                color: #646970;
                font-size: 13px;
            }

            @media (max-width: 900px) {
                .stai-tools-grid {
                    grid-template-columns: 1fr;
                }
            }
        </style>

        <div class="stai-tools-grid">
            <div class="stai-tools-card">
                <h2>Program Cache</h2>
                <div class="stai-tools-number"><?php echo esc_html($program_count); ?></div>
                <p class="stai-tools-muted">Google Sheet programları 15 dakika cache içinde tutulur.</p>

                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                    <?php wp_nonce_field('stai_clear_program_cache_action'); ?>
                    <input type="hidden" name="action" value="stai_clear_program_cache">
                    <?php submit_button('Clear Program Cache', 'secondary', 'submit', false); ?>
                </form>
            </div>

            <div class="stai-tools-card">
                <h2>FAQ Cache</h2>
                <div class="stai-tools-number"><?php echo esc_html($faq_count); ?></div>
                <p class="stai-tools-muted">FAQ Sheet cevapları 30 dakika cache içinde tutulur.</p>

                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                    <?php wp_nonce_field('stai_clear_faq_cache_action'); ?>
                    <input type="hidden" name="action" value="stai_clear_faq_cache">
                    <?php submit_button('Clear FAQ Cache', 'secondary', 'submit', false); ?>
                </form>
            </div>

            <div class="stai-tools-card">
                <h2>Gemini Cooldown</h2>
                <div class="stai-tools-number">
                    <?php echo esc_html($cooldown_remaining); ?> sn
                </div>
                <p class="stai-tools-muted">Gemini 429 quota hatası verirse geçici olarak duraklatılır.</p>

                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                    <?php wp_nonce_field('stai_reset_gemini_cooldown_action'); ?>
                    <input type="hidden" name="action" value="stai_reset_gemini_cooldown">
                    <?php submit_button('Reset Gemini Cooldown', 'secondary', 'submit', false); ?>
                </form>
            </div>
            


        </div>
    </div>
    <?php
}
