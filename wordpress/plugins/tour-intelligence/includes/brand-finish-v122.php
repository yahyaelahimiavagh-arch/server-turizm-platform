<?php
/**
 * STTI v1.2.2 — customer alignment, dynamic route timeline and in-tour media UX.
 *
 * Presentation-only follow-up over v1.2.1. Keeps every publication/SEO gate
 * unchanged while moving per-Tour image controls into Tur Düzenle → Medya.
 */
if (!defined('ABSPATH')) { exit; }

function stti_v122_detail_request_active() {
    if (!function_exists('stti_v119_request_stable_id') || !function_exists('stti_v119_detail_surface')) return false;
    $stable_id = stti_v119_request_stable_id();
    if ($stable_id === '') return false;
    $surface = stti_v119_detail_surface($stable_id);
    return !empty($surface['route']);
}

function stti_v122_route_meta() {
    $stable_id = function_exists('stti_v119_request_stable_id') ? stti_v119_request_stable_id() : '';
    $row = $stable_id !== '' ? stti_get_candidate($stable_id) : null;
    $payload = $row && function_exists('stti_v110_payload') ? stti_v110_payload($row) : array();
    $dest = is_array($payload['destinations'] ?? null) ? $payload['destinations'] : array();
    return array(
        'stableId'      => $stable_id,
        'departureCity' => trim((string)($dest['departure_city'] ?? '')),
        'returnCity'    => trim((string)($dest['return_city'] ?? '')),
    );
}

function stti_v122_front_assets() {
    if (!stti_v122_detail_request_active()) return;
    wp_enqueue_style(
        'stti-v122-detail-fixes',
        STTI_URL . 'assets/detail-route-v122.css',
        array(),
        STTI_RELEASE_VERSION
    );
    wp_enqueue_script(
        'stti-v122-detail-ui',
        STTI_URL . 'assets/detail-route-v122.js',
        array(),
        STTI_RELEASE_VERSION,
        true
    );
    wp_localize_script('stti-v122-detail-ui', 'STTI_V122_ROUTE_META', stti_v122_route_meta());
}
add_action('wp_enqueue_scripts', 'stti_v122_front_assets', 140);

function stti_v122_editor_screen() {
    return function_exists('stti_v111_operator_editor_is_screen') && stti_v111_operator_editor_is_screen();
}

function stti_v122_editor_stable_id() {
    if (!stti_v122_editor_screen()) return '';
    $stable_id = isset($_GET['tour']) ? sanitize_text_field(wp_unslash($_GET['tour'])) : '';
    if ($stable_id === '' || !preg_match('/^STT-\d{6}$/', $stable_id)) return '';
    return stti_get_candidate($stable_id) ? $stable_id : '';
}

function stti_v122_admin_assets() {
    $editor = stti_v122_editor_screen();
    $visual_defaults = function_exists('stti_v121_is_visual_admin') && stti_v121_is_visual_admin();
    if (!$editor && !$visual_defaults) return;

    wp_enqueue_media();
    wp_enqueue_style(
        'stti-v122-editor-media',
        STTI_URL . 'assets/operator-media-v122.css',
        array(),
        STTI_RELEASE_VERSION
    );
    wp_enqueue_script(
        'stti-v122-editor-media',
        STTI_URL . 'assets/operator-media-v122.js',
        array('jquery'),
        STTI_RELEASE_VERSION,
        true
    );

    $config = array(
        'mode' => $visual_defaults ? 'defaults' : 'editor',
    );

    if ($editor) {
        $stable_id = stti_v122_editor_stable_id();
        $row = $stable_id !== '' ? stti_get_candidate($stable_id) : null;
        $payload = $row && function_exists('stti_v110_payload') ? stti_v110_payload($row) : array();
        $media = function_exists('stti_v121_media') ? stti_v121_media($payload) : (is_array($payload['media'] ?? null) ? $payload['media'] : array());
        $presentation = function_exists('stti_v121_presentation') ? stti_v121_presentation($payload) : array('overlay'=>0.72,'focal_x'=>72,'focal_y'=>50,'height'=>700);
        $dest = is_array($payload['destinations'] ?? null) ? $payload['destinations'] : array();
        $config = array_merge($config, array(
            'ajaxUrl'       => admin_url('admin-ajax.php'),
            'nonce'         => wp_create_nonce('stti_v122_editor_visuals_' . $stable_id),
            'stableId'      => $stable_id,
            'hero'          => esc_url_raw((string)($media['hero_image_url'] ?? '')),
            'cover'         => esc_url_raw((string)($media['cover_image_url'] ?? '')),
            'overlay'       => (float)($presentation['overlay'] ?? 0.72),
            'focalX'        => (float)($presentation['focal_x'] ?? 72),
            'focalY'        => (float)($presentation['focal_y'] ?? 50),
            'height'        => (int)($presentation['height'] ?? 700),
            'departureCity' => trim((string)($dest['departure_city'] ?? '')),
            'returnCity'    => trim((string)($dest['return_city'] ?? '')),
        ));
    }

    wp_localize_script('stti-v122-editor-media', 'STTI_V122_EDITOR_MEDIA', $config);
}
add_action('admin_enqueue_scripts', 'stti_v122_admin_assets', 150);

function stti_v122_ajax_save_editor_visuals() {
    if (!current_user_can('manage_options')) wp_send_json_error(array('message'=>'Yetki yok.'), 403);

    $stable_id = isset($_POST['stable_id']) ? sanitize_text_field(wp_unslash($_POST['stable_id'])) : '';
    if ($stable_id === '' || !preg_match('/^STT-\d{6}$/', $stable_id)) {
        wp_send_json_error(array('message'=>'Geçersiz Stable ID.'), 400);
    }
    check_ajax_referer('stti_v122_editor_visuals_' . $stable_id, 'nonce');

    $row = stti_get_candidate($stable_id);
    if (!$row) wp_send_json_error(array('message'=>'Tur bulunamadı.'), 404);
    $payload = function_exists('stti_v110_payload') ? stti_v110_payload($row) : json_decode((string)($row['payload'] ?? ''), true);
    if (!is_array($payload)) wp_send_json_error(array('message'=>'Tur verisi geçersiz.'), 400);

    $before = $payload;
    if (!isset($payload['media']) || !is_array($payload['media'])) $payload['media'] = array();
    if (!isset($payload['presentation']) || !is_array($payload['presentation'])) $payload['presentation'] = array();

    $payload['media']['hero_image_url'] = isset($_POST['hero_image_url']) ? esc_url_raw(wp_unslash($_POST['hero_image_url'])) : '';
    $payload['media']['cover_image_url'] = isset($_POST['cover_image_url']) ? esc_url_raw(wp_unslash($_POST['cover_image_url'])) : '';
    $clamp = function($value, $min, $max, $default) {
        if (function_exists('stti_v121_clamp')) return stti_v121_clamp($value, $min, $max, $default);
        if (!is_numeric($value)) return $default;
        return max($min, min($max, (float)$value));
    };
    $payload['presentation']['hero_overlay'] = $clamp($_POST['hero_overlay'] ?? 0.72, 0.25, 0.92, 0.72);
    $payload['presentation']['hero_focal_x'] = $clamp($_POST['hero_focal_x'] ?? 72, 0, 100, 72);
    $payload['presentation']['hero_focal_y'] = $clamp($_POST['hero_focal_y'] ?? 50, 0, 100, 50);
    $payload['presentation']['hero_height'] = (int)round($clamp($_POST['hero_height'] ?? 700, 560, 900, 700));

    $json = wp_json_encode($payload, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
    $checksum = hash('sha256', $json);
    $now = current_time('mysql');
    global $wpdb;
    $tables = stti_tables();
    $updated = $wpdb->update(
        $tables['tours'],
        array('payload'=>$json,'checksum'=>$checksum,'updated_at'=>$now),
        array('stable_id'=>$stable_id),
        array('%s','%s','%s'),
        array('%s')
    );
    if ($updated === false) wp_send_json_error(array('message'=>'Görseller kaydedilemedi.'), 500);
    if (function_exists('stti_audit_event')) stti_audit_event($stable_id, 'visual_presentation_updated', $before, $payload);

    wp_send_json_success(array(
        'message'=>'Tur görselleri kaydedildi.',
        'checksum'=>$checksum,
    ));
}
add_action('wp_ajax_stti_v122_save_editor_visuals', 'stti_v122_ajax_save_editor_visuals');
