<?php
function stti_v070_now() {
    return function_exists('current_time') ? current_time('mysql') : gmdate('Y-m-d H:i:s');
}

function stti_v070_actor_id() {
    return function_exists('get_current_user_id') ? (int) get_current_user_id() : 0;
}

function stti_v070_index_by_id($items, $id_field) {
    $out = array();
    foreach ($items as $item) if (is_array($item) && !empty($item[$id_field])) $out[(string)$item[$id_field]] = $item;
    return $out;
}

/** Server owns reviewer identity/timestamp; client input cannot forge it. */
function stti_v070_stamp_review_transitions($new_items, $old_items, $id_field) {
    $old_map = stti_v070_index_by_id($old_items, $id_field);
    $actor = stti_v070_actor_id();
    $now = stti_v070_now();
    foreach ($new_items as &$item) {
        $id = (string)($item[$id_field] ?? '');
        $status = stti_v070_review_status($item['review_status'] ?? 'pending');
        $old = $old_map[$id] ?? null;
        $old_status = is_array($old) ? stti_v070_review_status($old['review_status'] ?? 'pending') : null;
        if ($status === 'pending') {
            $item['reviewed_by'] = null;
            $item['reviewed_at'] = null;
        } elseif ($old_status === $status && !empty($old['reviewed_at'])) {
            $item['reviewed_by'] = isset($old['reviewed_by']) && (int)$old['reviewed_by'] > 0 ? (int)$old['reviewed_by'] : null;
            $item['reviewed_at'] = stti_v070_review_time($old);
        } else {
            $item['reviewed_by'] = $actor > 0 ? $actor : null;
            $item['reviewed_at'] = $now;
        }
    }
    unset($item);
    return $new_items;
}

function stti_v070_decode_variants_json($raw) {
    if (function_exists('wp_unslash')) $raw = wp_unslash((string)$raw);
    $decoded = json_decode((string)$raw, true);
    return stti_v070_normalize_route_variants(is_array($decoded) ? $decoded : array());
}

function stti_v070_existing_payload($row) {
    if (!is_array($row)) return array();
    $payload = json_decode((string)($row['payload'] ?? ''), true);
    return is_array($payload) ? $payload : array();
}

/** Add v0.7 relation semantics to a payload built by the accepted v0.6.5 core. */
function stti_v070_enrich_payload($payload, $existing_payload = array(), $raw_variants = null) {
    $payload = is_array($payload) ? $payload : array();
    $existing_payload = is_array($existing_payload) ? $existing_payload : array();

    $existing_variants = stti_v070_normalize_route_variants($existing_payload['route']['variants'] ?? array());
    $variants = $raw_variants === null ? $existing_variants : stti_v070_decode_variants_json($raw_variants);
    $hotels = stti_v070_normalize_hotel_relations($payload['stays']['hotels'] ?? array());
    $segments = stti_v070_normalize_transport_relations($payload['transport']['segments'] ?? array());

    $variants = stti_v070_stamp_review_transitions($variants, $existing_variants, 'variant_id');
    $hotels = stti_v070_stamp_review_transitions($hotels, stti_v070_normalize_hotel_relations($existing_payload['stays']['hotels'] ?? array()), 'relation_id');
    $segments = stti_v070_stamp_review_transitions($segments, stti_v070_normalize_transport_relations($existing_payload['transport']['segments'] ?? array()), 'segment_id');

    if (!isset($payload['route']) || !is_array($payload['route'])) $payload['route'] = array();
    if (!isset($payload['stays']) || !is_array($payload['stays'])) $payload['stays'] = array();
    if (!isset($payload['transport']) || !is_array($payload['transport'])) $payload['transport'] = array();
    $payload['route']['variants'] = $variants;
    $payload['stays']['hotels'] = $hotels;
    $payload['transport']['segments'] = $segments;
    return $payload;
}
