<?php
/**
 * STTI v1.2.3 — lossless Tour editor persistence.
 *
 * The legacy editor rebuilds its canonical payload from form-owned fields.
 * Later modules added publication gates, per-Tour media/presentation and other
 * extension-owned payload facts. This layer makes updates lossless: editor-owned
 * facts are refreshed, while extension-owned/unknown facts survive unchanged.
 */
if (!defined('ABSPATH')) { exit; }

function stti_v123_array_is_list_compat($value) {
    if (!is_array($value)) return false;
    $expected = 0;
    foreach ($value as $key => $_) {
        if ($key !== $expected) return false;
        $expected++;
    }
    return true;
}

function stti_v123_merge_payload_value($existing, $fresh) {
    if (!is_array($existing) || !is_array($fresh)) return $fresh;

    // Ordered collections belong to the editor as a whole. Replacing them avoids
    // stale itinerary/route/service rows surviving after an operator removes one.
    if (stti_v123_array_is_list_compat($existing) || stti_v123_array_is_list_compat($fresh)) {
        return $fresh;
    }

    $merged = $existing;
    foreach ($fresh as $key => $value) {
        if (array_key_exists($key, $merged) && is_array($merged[$key]) && is_array($value)) {
            $merged[$key] = stti_v123_merge_payload_value($merged[$key], $value);
        } else {
            $merged[$key] = $value;
        }
    }
    return $merged;
}

function stti_v123_merge_existing_payload($existing, $fresh) {
    if (!is_array($existing)) return $fresh;
    if (!is_array($fresh)) return $existing;

    // These surfaces are not owned by the legacy canonical editor form.
    // Never let its placeholder defaults erase values written by later modules.
    unset($fresh['media'], $fresh['publication']);

    return stti_v123_merge_payload_value($existing, $fresh);
}

function stti_v123_clamp($value, $min, $max, $default) {
    if (function_exists('stti_v121_clamp')) return stti_v121_clamp($value, $min, $max, $default);
    if (!is_numeric($value)) return $default;
    return max($min, min($max, (float)$value));
}

function stti_v123_apply_editor_visual_fields($payload, $raw) {
    if (!is_array($payload)) $payload = array();
    if (!is_array($raw)) return $payload;

    $has_visuals = array_key_exists('stti_v123_hero_image_url', $raw)
        || array_key_exists('stti_v123_cover_image_url', $raw)
        || array_key_exists('stti_v123_hero_overlay', $raw)
        || array_key_exists('stti_v123_hero_focal_x', $raw)
        || array_key_exists('stti_v123_hero_focal_y', $raw)
        || array_key_exists('stti_v123_hero_height', $raw);
    if (!$has_visuals) return $payload;

    if (!isset($payload['media']) || !is_array($payload['media'])) $payload['media'] = array();
    if (!isset($payload['presentation']) || !is_array($payload['presentation'])) $payload['presentation'] = array();

    if (array_key_exists('stti_v123_hero_image_url', $raw)) {
        $payload['media']['hero_image_url'] = esc_url_raw(wp_unslash($raw['stti_v123_hero_image_url']));
    }
    if (array_key_exists('stti_v123_cover_image_url', $raw)) {
        $payload['media']['cover_image_url'] = esc_url_raw(wp_unslash($raw['stti_v123_cover_image_url']));
    }
    if (array_key_exists('stti_v123_hero_overlay', $raw)) {
        $payload['presentation']['hero_overlay'] = stti_v123_clamp(wp_unslash($raw['stti_v123_hero_overlay']), 0.25, 0.92, 0.72);
    }
    if (array_key_exists('stti_v123_hero_focal_x', $raw)) {
        $payload['presentation']['hero_focal_x'] = stti_v123_clamp(wp_unslash($raw['stti_v123_hero_focal_x']), 0, 100, 72);
    }
    if (array_key_exists('stti_v123_hero_focal_y', $raw)) {
        $payload['presentation']['hero_focal_y'] = stti_v123_clamp(wp_unslash($raw['stti_v123_hero_focal_y']), 0, 100, 50);
    }
    if (array_key_exists('stti_v123_hero_height', $raw)) {
        $payload['presentation']['hero_height'] = (int)round(stti_v123_clamp(wp_unslash($raw['stti_v123_hero_height']), 560, 900, 700));
    }
    return $payload;
}

function stti_v123_handle_save_candidate() {
    if (!current_user_can('manage_options')) wp_die('Unauthorized');
    check_admin_referer('stti_save_candidate');

    $f = stti_sanitize_form($_POST);
    $validation = stti_validate_form($f);
    if ($validation['errors']) {
        $args = array(
            'page' => 'stti-tour-intelligence',
            'view' => 'editor',
            'stti_error' => rawurlencode(implode(' | ', $validation['errors'])),
        );
        if ($f['stable_id'] !== '') $args['tour'] = $f['stable_id'];
        wp_safe_redirect(add_query_arg($args, admin_url('admin.php')));
        exit;
    }

    global $wpdb;
    $tables = stti_tables();
    $is_new = $f['stable_id'] === '';

    if ($is_new && !empty($f['origin_fixture_id'])) {
        $already = stti_find_candidate_by_origin_fixture($f['origin_fixture_id']);
        if ($already) {
            wp_safe_redirect(add_query_arg(array(
                'page'=>'stti-tour-intelligence','view'=>'editor','tour'=>$already['stable_id'],'stti_saved'=>'fixture_exists'
            ), admin_url('admin.php')));
            exit;
        }
    }

    $stable_id = $is_new ? stti_allocate_stable_id() : $f['stable_id'];
    if (is_wp_error($stable_id)) wp_die(esc_html($stable_id->get_error_message()));

    $existing = $is_new ? null : stti_get_candidate($stable_id);
    if (!$is_new && !$existing) wp_die('Unknown STT stable ID. Fail closed.');

    $fresh = stti_build_payload($f, $stable_id);
    $before = $existing ? json_decode((string)$existing['payload'], true) : null;
    $payload = $is_new ? $fresh : stti_v123_merge_existing_payload(is_array($before) ? $before : array(), $fresh);
    $payload = stti_v123_apply_editor_visual_fields($payload, $_POST);

    $json = wp_json_encode($payload, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
    $checksum = hash('sha256', $json);
    $now = current_time('mysql');

    if ($is_new) {
        $wpdb->insert($tables['tours'], array(
            'stable_id'=>$stable_id,'schema_version'=>STTI_SCHEMA_VERSION,'public_title'=>$f['public_title'],'slug'=>$f['slug'],
            'editorial'=>$f['editorial'],'schedule_status'=>$f['schedule_status'],'availability'=>$f['availability'],'temporal'=>$f['temporal'],
            'source_completeness'=>$f['source_completeness'],'payload'=>$json,'checksum'=>$checksum,'created_by'=>get_current_user_id(),'created_at'=>$now,'updated_at'=>$now,
        ), array('%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%d','%s','%s'));
        if (!$wpdb->insert_id) wp_die('Private candidate insert failed.');
        stti_audit_event($stable_id, 'candidate_created', null, $payload);
        $event = 'created';
    } else {
        if (hash_equals((string)$existing['checksum'], $checksum)) {
            stti_audit_event($stable_id, 'candidate_unchanged', $before, $payload);
            $event = 'unchanged';
        } else {
            $updated = $wpdb->update($tables['tours'], array(
                'schema_version'=>STTI_SCHEMA_VERSION,'public_title'=>$f['public_title'],'slug'=>$f['slug'],'editorial'=>$f['editorial'],'schedule_status'=>$f['schedule_status'],
                'availability'=>$f['availability'],'temporal'=>$f['temporal'],'source_completeness'=>$f['source_completeness'],'payload'=>$json,'checksum'=>$checksum,'updated_at'=>$now,
            ), array('stable_id'=>$stable_id), array('%s','%s','%s','%s','%s','%s','%s','%s','%s','%s'), array('%s'));
            if ($updated === false) wp_die('Private candidate update failed.');
            stti_audit_event($stable_id, 'candidate_updated_lossless', $before, $payload);
            $event = 'updated';
        }
    }

    wp_safe_redirect(add_query_arg(array(
        'page'=>'stti-tour-intelligence','view'=>'editor','tour'=>$stable_id,'stti_saved'=>$event
    ), admin_url('admin.php')));
    exit;
}

// Core registered the legacy rebuild handler before this module loaded.
// Replace it with the lossless handler without changing the endpoint/nonce contract.
remove_action('admin_post_stti_save_candidate', 'stti_handle_save_candidate');
add_action('admin_post_stti_save_candidate', 'stti_v123_handle_save_candidate');

function stti_v123_enqueue_assets() {
    if (function_exists('stti_v118_hub_request_active') && stti_v118_hub_request_active()) {
        wp_enqueue_style(
            'stti-v123-hub-layout',
            STTI_URL . 'assets/hub-layout-v123.css',
            array('stti-v121-hub-finish'),
            STTI_RELEASE_VERSION
        );
    }

    if (function_exists('stti_v111_operator_editor_is_screen') && stti_v111_operator_editor_is_screen()) {
        wp_enqueue_script(
            'stti-v123-editor-persistence',
            STTI_URL . 'assets/operator-persistence-v123.js',
            array('stti-v122-editor-media'),
            STTI_RELEASE_VERSION,
            true
        );
    }
}
add_action('wp_enqueue_scripts', 'stti_v123_enqueue_assets', 170);
add_action('admin_enqueue_scripts', 'stti_v123_enqueue_assets', 170);
