<?php
/**
 * STTI v1.2.1 — Visual settings + Server Turizm brand finish.
 *
 * Presentation-only controls for customer-facing Culture Tour Hub/detail pages.
 * Writes only media/presentation keys inside the canonical Tour payload and
 * global visual fallback options. Editorial, publication, lifecycle, SEO,
 * sitemap, schema and route gates are never changed here.
 */
if (!defined('ABSPATH')) { exit; }

function stti_v121_option($key) { return 'stti_v121_' . $key; }

function stti_v121_clamp($value, $min, $max, $default) {
    if (!is_numeric($value)) return $default;
    return max($min, min($max, (float)$value));
}

function stti_v121_visual_defaults() {
    return array(
        'hub_hero_image'     => esc_url_raw((string)get_option(stti_v121_option('hub_hero_image'), '')),
        'default_hero_image' => esc_url_raw((string)get_option(stti_v121_option('default_hero_image'), '')),
        'default_card_image' => esc_url_raw((string)get_option(stti_v121_option('default_card_image'), '')),
    );
}

function stti_v121_presentation($payload) {
    $payload = is_array($payload) ? $payload : array();
    $presentation = is_array($payload['presentation'] ?? null) ? $payload['presentation'] : array();
    return array(
        'overlay' => stti_v121_clamp($presentation['hero_overlay'] ?? 0.72, 0.25, 0.92, 0.72),
        'focal_x' => stti_v121_clamp($presentation['hero_focal_x'] ?? 72, 0, 100, 72),
        'focal_y' => stti_v121_clamp($presentation['hero_focal_y'] ?? 50, 0, 100, 50),
        'height'  => (int)round(stti_v121_clamp($presentation['hero_height'] ?? 700, 560, 900, 700)),
    );
}

function stti_v121_media($payload) {
    $payload = is_array($payload) ? $payload : array();
    return is_array($payload['media'] ?? null) ? $payload['media'] : array();
}

function stti_v121_effective_hero($payload) {
    $media = stti_v121_media($payload);
    $defaults = stti_v121_visual_defaults();
    foreach (array($media['hero_image_url'] ?? '', $media['cover_image_url'] ?? '', $media['image_url'] ?? '', $defaults['default_hero_image']) as $candidate) {
        $candidate = esc_url_raw((string)$candidate);
        if ($candidate !== '') return $candidate;
    }
    return '';
}

function stti_v121_effective_card($payload) {
    $media = stti_v121_media($payload);
    $defaults = stti_v121_visual_defaults();
    foreach (array($media['cover_image_url'] ?? '', $media['hero_image_url'] ?? '', $media['image_url'] ?? '', $defaults['default_card_image'], $defaults['default_hero_image']) as $candidate) {
        $candidate = esc_url_raw((string)$candidate);
        if ($candidate !== '') return $candidate;
    }
    return '';
}

function stti_v121_hub_records() {
    $records = function_exists('stti_v119_hub_records') ? stti_v119_hub_records() : (function_exists('stti_v117_hub_records') ? stti_v117_hub_records() : array());
    foreach ($records as &$card) {
        $stable_id = (string)($card['stable_id'] ?? '');
        if ($stable_id === '') continue;
        $row = stti_get_candidate($stable_id);
        if (!$row) continue;
        $payload = function_exists('stti_v110_payload') ? stti_v110_payload($row) : json_decode((string)($row['payload'] ?? ''), true);
        if (!is_array($payload)) continue;
        $image = stti_v121_effective_card($payload);
        if ($image !== '') $card['image'] = $image;
    }
    unset($card);
    return $records;
}

function stti_v121_hub_html() {
    return function_exists('stti_v110_render_hub') ? stti_v110_render_hub(stti_v121_hub_records()) : '';
}

function stti_v121_is_visual_admin() {
    if (!is_admin()) return false;
    return isset($_GET['page']) && sanitize_key(wp_unslash($_GET['page'])) === 'stti-visual-settings-v121';
}

function stti_v121_register_visual_admin() {
    add_submenu_page(
        'stti-tour-intelligence',
        'Tur Görselleri',
        'Görsel Ayarları',
        'manage_options',
        'stti-visual-settings-v121',
        'stti_v121_render_visual_admin'
    );
}
add_action('admin_menu', 'stti_v121_register_visual_admin', 35);

function stti_v121_admin_assets() {
    if (!stti_v121_is_visual_admin()) return;
    wp_enqueue_media();
    wp_enqueue_style('stti-v121-visual-admin', STTI_URL . 'assets/visual-settings-v121.css', array(), STTI_RELEASE_VERSION);
    wp_enqueue_script('stti-v121-visual-admin', STTI_URL . 'assets/visual-settings-v121.js', array('jquery'), STTI_RELEASE_VERSION, true);
}
add_action('admin_enqueue_scripts', 'stti_v121_admin_assets', 20);

function stti_v121_selected_candidate() {
    $stable_id = isset($_GET['stable_id']) ? sanitize_text_field(wp_unslash($_GET['stable_id'])) : '';
    if ($stable_id !== '') return stti_get_candidate($stable_id);
    foreach (stti_get_candidates() as $row) return $row;
    return null;
}

function stti_v121_render_media_field($label, $name, $value, $help='') {
    ?>
    <div class="stti-v121-field stti-v121-media-field">
      <label><?php echo esc_html($label); ?></label>
      <div class="stti-v121-media-row">
        <input type="url" name="<?php echo esc_attr($name); ?>" value="<?php echo esc_attr($value); ?>" placeholder="https://..." />
        <button type="button" class="button stti-v121-media-pick" data-target="<?php echo esc_attr($name); ?>">Medya Kütüphanesi</button>
        <button type="button" class="button stti-v121-media-clear" data-target="<?php echo esc_attr($name); ?>">Temizle</button>
      </div>
      <div class="stti-v121-media-preview<?php echo $value ? ' has-image' : ''; ?>" data-preview="<?php echo esc_attr($name); ?>"<?php if ($value): ?> style="background-image:url('<?php echo esc_url($value); ?>')"<?php endif; ?>></div>
      <?php if ($help !== ''): ?><p class="description"><?php echo esc_html($help); ?></p><?php endif; ?>
    </div>
    <?php
}

function stti_v121_render_visual_admin() {
    if (!current_user_can('manage_options')) return;
    $defaults = stti_v121_visual_defaults();
    $row = stti_v121_selected_candidate();
    $payload = $row && function_exists('stti_v110_payload') ? stti_v110_payload($row) : array();
    $media = stti_v121_media($payload);
    $presentation = stti_v121_presentation($payload);
    $stable_id = (string)($row['stable_id'] ?? '');
    ?>
    <div class="wrap stti-v121-admin">
      <div class="stti-v121-admin-head">
        <div><span>STTI v1.2.1 · BRAND FINISH</span><h1>Görsel Ayarları</h1><p>Hub ve tur detay sayfasının görsel katmanı. Bu ekran yalnızca görsel/presentation verisini değiştirir; yayın ve SEO kilitlerine dokunmaz.</p></div>
        <div class="stti-v121-locks"><b>PUBLIC/SEO GATES</b><span>KAPALI · AYRI KONTROL</span></div>
      </div>

      <?php if (isset($_GET['visual_saved'])): ?><div class="notice notice-success is-dismissible"><p>Tur görsel ayarları kaydedildi.</p></div><?php endif; ?>
      <?php if (isset($_GET['defaults_saved'])): ?><div class="notice notice-success is-dismissible"><p>Varsayılan görsel ayarları kaydedildi.</p></div><?php endif; ?>

      <section class="stti-v121-panel">
        <header><span>GENEL</span><h2>Site varsayılan görselleri</h2><p>Turda özel görsel yoksa kullanılan güvenli fallback görseller.</p></header>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
          <input type="hidden" name="action" value="stti_v121_save_visual_defaults" />
          <?php wp_nonce_field('stti_v121_visual_defaults'); ?>
          <?php stti_v121_render_media_field('Kültür Turları Hub Hero', 'hub_hero_image', $defaults['hub_hero_image'], '/kultur-turlari/ üst bölümünün arka planı.'); ?>
          <?php stti_v121_render_media_field('Varsayılan Tur Hero', 'default_hero_image', $defaults['default_hero_image'], 'Turda özel hero yoksa detay sayfasında kullanılır.'); ?>
          <?php stti_v121_render_media_field('Varsayılan Kart Görseli', 'default_card_image', $defaults['default_card_image'], 'Tur kartında özel cover yoksa kullanılır.'); ?>
          <p><button type="submit" class="button button-primary">Varsayılanları Kaydet</button></p>
        </form>
      </section>

      <section class="stti-v121-panel">
        <header><span>TUR BAZLI</span><h2>Tur görsel yönetimi</h2><p>Hero ve Hub kart görsellerini aynı yerden yönetin.</p></header>
        <?php $candidates = stti_get_candidates(); if (!$candidates): ?>
          <p>Henüz tur kaydı yok.</p>
        <?php else: ?>
          <form method="get" class="stti-v121-picker">
            <input type="hidden" name="page" value="stti-visual-settings-v121" />
            <label>Tur seç</label>
            <select name="stable_id">
              <?php foreach ($candidates as $candidate): $cid=(string)($candidate['stable_id']??''); $ctitle=(string)($candidate['public_title']??'Tur'); ?>
                <option value="<?php echo esc_attr($cid); ?>" <?php selected($cid,$stable_id); ?>><?php echo esc_html($cid . ' · ' . $ctitle); ?></option>
              <?php endforeach; ?>
            </select>
            <button class="button">Aç</button>
          </form>

          <?php if ($row): ?>
          <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="stti-v121-tour-form">
            <input type="hidden" name="action" value="stti_v121_save_tour_visuals" />
            <input type="hidden" name="stable_id" value="<?php echo esc_attr($stable_id); ?>" />
            <?php wp_nonce_field('stti_v121_tour_visuals_' . $stable_id); ?>
            <div class="stti-v121-tour-title"><code><?php echo esc_html($stable_id); ?></code><h3><?php echo esc_html((string)($row['public_title'] ?? 'Tur')); ?></h3></div>
            <?php stti_v121_render_media_field('Detay Hero Görseli', 'hero_image_url', esc_url_raw((string)($media['hero_image_url'] ?? '')), 'Geniş yatay görsel önerilir (örn. 1920×1080 veya daha büyük).'); ?>
            <?php stti_v121_render_media_field('Hub Kart Görseli', 'cover_image_url', esc_url_raw((string)($media['cover_image_url'] ?? '')), 'Dikey/yatay kırpmaya dayanıklı görsel kullanın.'); ?>

            <div class="stti-v121-grid">
              <div class="stti-v121-field"><label>Hero Overlay</label><input type="number" name="hero_overlay" min="0.25" max="0.92" step="0.01" value="<?php echo esc_attr((string)$presentation['overlay']); ?>" /><p class="description">0.25 açık · 0.92 koyu</p></div>
              <div class="stti-v121-field"><label>Odak X (%)</label><input type="number" name="hero_focal_x" min="0" max="100" step="1" value="<?php echo esc_attr((string)$presentation['focal_x']); ?>" /></div>
              <div class="stti-v121-field"><label>Odak Y (%)</label><input type="number" name="hero_focal_y" min="0" max="100" step="1" value="<?php echo esc_attr((string)$presentation['focal_y']); ?>" /></div>
              <div class="stti-v121-field"><label>Hero Yüksekliği (px)</label><input type="number" name="hero_height" min="560" max="900" step="10" value="<?php echo esc_attr((string)$presentation['height']); ?>" /></div>
            </div>
            <div class="stti-v121-truth-note"><b>Truth boundary</b><span>Bu kayıt sadece media + presentation alanlarını günceller. Editorial, Hub visibility, Detail Route, Indexation, Sitemap, Schema veya Canonical değişmez.</span></div>
            <p><button type="submit" class="button button-primary button-hero">Tur Görsellerini Kaydet</button></p>
          </form>
          <?php endif; ?>
        <?php endif; ?>
      </section>
    </div>
    <?php
}

function stti_v121_handle_visual_defaults() {
    if (!current_user_can('manage_options')) wp_die('Yetki yok.');
    check_admin_referer('stti_v121_visual_defaults');
    foreach (array('hub_hero_image','default_hero_image','default_card_image') as $key) {
        $value = isset($_POST[$key]) ? esc_url_raw(wp_unslash($_POST[$key])) : '';
        update_option(stti_v121_option($key), $value, false);
    }
    wp_safe_redirect(add_query_arg(array('page'=>'stti-visual-settings-v121','defaults_saved'=>'1'), admin_url('admin.php')));
    exit;
}
add_action('admin_post_stti_v121_save_visual_defaults', 'stti_v121_handle_visual_defaults');

function stti_v121_handle_tour_visuals() {
    if (!current_user_can('manage_options')) wp_die('Yetki yok.');
    $stable_id = isset($_POST['stable_id']) ? sanitize_text_field(wp_unslash($_POST['stable_id'])) : '';
    if ($stable_id === '') wp_die('Stable ID required.');
    check_admin_referer('stti_v121_tour_visuals_' . $stable_id);
    $row = stti_get_candidate($stable_id);
    if (!$row) wp_die('Unknown STT stable ID.');
    $payload = function_exists('stti_v110_payload') ? stti_v110_payload($row) : json_decode((string)($row['payload'] ?? ''), true);
    if (!is_array($payload)) wp_die('Payload invalid.');
    $before = $payload;
    if (!isset($payload['media']) || !is_array($payload['media'])) $payload['media'] = array();
    if (!isset($payload['presentation']) || !is_array($payload['presentation'])) $payload['presentation'] = array();

    $payload['media']['hero_image_url'] = isset($_POST['hero_image_url']) ? esc_url_raw(wp_unslash($_POST['hero_image_url'])) : '';
    $payload['media']['cover_image_url'] = isset($_POST['cover_image_url']) ? esc_url_raw(wp_unslash($_POST['cover_image_url'])) : '';
    $payload['presentation']['hero_overlay'] = stti_v121_clamp($_POST['hero_overlay'] ?? 0.72, 0.25, 0.92, 0.72);
    $payload['presentation']['hero_focal_x'] = stti_v121_clamp($_POST['hero_focal_x'] ?? 72, 0, 100, 72);
    $payload['presentation']['hero_focal_y'] = stti_v121_clamp($_POST['hero_focal_y'] ?? 50, 0, 100, 50);
    $payload['presentation']['hero_height'] = (int)round(stti_v121_clamp($_POST['hero_height'] ?? 700, 560, 900, 700));

    $json = wp_json_encode($payload, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
    $checksum = hash('sha256', $json);
    $now = current_time('mysql');
    global $wpdb; $tables = stti_tables();
    $updated = $wpdb->update($tables['tours'], array('payload'=>$json,'checksum'=>$checksum,'updated_at'=>$now), array('stable_id'=>$stable_id), array('%s','%s','%s'), array('%s'));
    if ($updated === false) wp_die('Visual settings write failed.');
    if (function_exists('stti_audit_event')) stti_audit_event($stable_id, 'visual_presentation_updated', $before, $payload);

    wp_safe_redirect(add_query_arg(array('page'=>'stti-visual-settings-v121','stable_id'=>$stable_id,'visual_saved'=>'1'), admin_url('admin.php')));
    exit;
}
add_action('admin_post_stti_v121_save_tour_visuals', 'stti_v121_handle_tour_visuals');

function stti_v121_body_classes($classes) {
    $classes = (array)$classes;
    if (function_exists('stti_v119_request_stable_id')) {
        $stable_id = stti_v119_request_stable_id();
        if ($stable_id !== '') {
            $surface = stti_v119_detail_surface($stable_id);
            if (!empty($surface['route'])) $classes[] = 'stti-v121-brand-finish';
        }
    }
    if (function_exists('stti_v118_hub_request_active') && stti_v118_hub_request_active()) $classes[] = 'stti-v121-hub-page';
    return array_values(array_unique($classes));
}
add_filter('body_class', 'stti_v121_body_classes', 9999);

function stti_v121_enqueue_front_assets() {
    if (is_admin()) return;

    if (function_exists('stti_v119_request_stable_id')) {
        $stable_id = stti_v119_request_stable_id();
        if ($stable_id !== '') {
            $surface = stti_v119_detail_surface($stable_id);
            if (!empty($surface['route'])) {
                wp_enqueue_style('stti-v121-brand-finish', STTI_URL . 'assets/detail-route-v121.css', array('stti-v120-detail-polish'), STTI_RELEASE_VERSION);
                $payload = is_array($surface['payload'] ?? null) ? $surface['payload'] : array();
                $hero = stti_v121_effective_hero($payload);
                $presentation = stti_v121_presentation($payload);
                $hero_css = $hero !== '' ? 'url("' . esc_url_raw($hero) . '")' : 'none';
                $inline = ':root{--stti-v121-hero:' . $hero_css . ';--stti-v121-overlay:' . number_format((float)$presentation['overlay'], 2, '.', '') . ';--stti-v121-focal-x:' . (float)$presentation['focal_x'] . '%;--stti-v121-focal-y:' . (float)$presentation['focal_y'] . '%;--stti-v121-hero-height:' . (int)$presentation['height'] . 'px;}';
                wp_add_inline_style('stti-v121-brand-finish', $inline);
            }
        }
    }

    if (function_exists('stti_v118_hub_request_active') && stti_v118_hub_request_active()) {
        wp_enqueue_style('stti-v121-hub-finish', STTI_URL . 'assets/tour-hub-v121.css', array('stti-tour-hub-v110'), STTI_RELEASE_VERSION);
        $defaults = stti_v121_visual_defaults();
        $hero = $defaults['hub_hero_image'];
        $hero_css = $hero !== '' ? 'url("' . esc_url_raw($hero) . '")' : 'none';
        wp_add_inline_style('stti-v121-hub-finish', ':root{--stti-v121-hub-hero:' . $hero_css . ';}');
    }
}
add_action('wp_enqueue_scripts', 'stti_v121_enqueue_front_assets', 99);
