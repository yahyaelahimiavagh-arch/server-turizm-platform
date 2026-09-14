<?php
/**
 * STTI v0.7.0 — WordPress Review Relations.
 *
 * Private relation-review layer. It never copies Hotel Intelligence facts into
 * STTI, never guesses relationship membership, and never changes Tour public
 * route / SEO locks. Unknown facts stay pending, unknown, or unresolved until
 * a human reviewer supplies evidence.
 */

if (!function_exists('stti_v070_text')) {
    function stti_v070_text($value) {
        $value = trim((string) $value);
        if (function_exists('sanitize_text_field')) {
            return sanitize_text_field($value);
        }
        return preg_replace('/[\x00-\x1F\x7F]/u', '', $value);
    }
}

function stti_v070_ref_list($value) {
    if (is_string($value)) {
        $value = preg_split('/\s*,\s*/', trim($value), -1, PREG_SPLIT_NO_EMPTY);
    }
    if (!is_array($value)) return array();
    $out = array();
    foreach ($value as $item) {
        $item = stti_v070_text($item);
        if ($item !== '' && !in_array($item, $out, true)) $out[] = $item;
    }
    return $out;
}

function stti_v070_review_status($value) {
    $value = stti_v070_text($value);
    return in_array($value, array('pending', 'confirmed', 'rejected'), true) ? $value : 'pending';
}

function stti_v070_variant_role($value) {
    $value = stti_v070_text($value);
    return in_array($value, array('unknown', 'primary', 'alternative'), true) ? $value : 'unknown';
}

function stti_v070_hotel_selection_status($value) {
    $value = stti_v070_text($value);
    return in_array($value, array('unknown', 'selected', 'alternative'), true) ? $value : 'unknown';
}

function stti_v070_review_actor($item) {
    $actor = isset($item['reviewed_by']) ? (int) $item['reviewed_by'] : 0;
    return $actor > 0 ? $actor : null;
}

function stti_v070_review_time($item) {
    $value = stti_v070_text($item['reviewed_at'] ?? '');
    return $value !== '' ? $value : null;
}

function stti_v070_normalize_route_variants($variants) {
    if (!is_array($variants)) return array();
    $out = array();
    foreach (array_values($variants) as $index => $variant) {
        if (!is_array($variant)) continue;
        $id = stti_v070_text($variant['variant_id'] ?? '');
        if ($id === '') $id = 'V' . ($index + 1); // internal relation identity, not a business fact.
        $out[] = array(
            'variant_id' => $id,
            'label' => stti_v070_text($variant['label'] ?? ''),
            'role' => stti_v070_variant_role($variant['role'] ?? 'unknown'),
            'stop_refs' => stti_v070_ref_list($variant['stop_refs'] ?? array()),
            'hotel_relation_refs' => stti_v070_ref_list($variant['hotel_relation_refs'] ?? array()),
            'transport_segment_refs' => stti_v070_ref_list($variant['transport_segment_refs'] ?? array()),
            'review_status' => stti_v070_review_status($variant['review_status'] ?? 'pending'),
            'review_note' => stti_v070_text($variant['review_note'] ?? ''),
            'reviewed_by' => stti_v070_review_actor($variant),
            'reviewed_at' => stti_v070_review_time($variant),
            'note' => stti_v070_text($variant['note'] ?? ''),
        );
    }
    return $out;
}

function stti_v070_normalize_hotel_relations($relations) {
    if (!is_array($relations)) return array();
    $out = array();
    foreach (array_values($relations) as $index => $relation) {
        if (!is_array($relation)) continue;
        // Preserve existing source/stay context; add relation-review fields only.
        $id = stti_v070_text($relation['relation_id'] ?? '');
        if ($id === '') $id = 'H' . ($index + 1); // internal relation identity only.
        $relation['relation_id'] = $id;
        $relation['mode'] = in_array(($relation['mode'] ?? ''), array('hotel_intelligence', 'unresolved'), true)
            ? $relation['mode'] : 'unresolved';
        $relation['hotel_stable_id'] = strtoupper(stti_v070_text($relation['hotel_stable_id'] ?? ''));
        $relation['unresolved_name'] = stti_v070_text($relation['unresolved_name'] ?? '');
        $relation['option_group_id'] = stti_v070_text($relation['option_group_id'] ?? '');
        $relation['selection_status'] = stti_v070_hotel_selection_status($relation['selection_status'] ?? 'unknown');
        $relation['review_status'] = stti_v070_review_status($relation['review_status'] ?? 'pending');
        $relation['review_note'] = stti_v070_text($relation['review_note'] ?? '');
        $relation['reviewed_by'] = stti_v070_review_actor($relation);
        $relation['reviewed_at'] = stti_v070_review_time($relation);
        $out[] = $relation;
    }
    return $out;
}

function stti_v070_normalize_transport_relations($segments) {
    if (!is_array($segments)) return array();
    $out = array();
    foreach (array_values($segments) as $index => $segment) {
        if (!is_array($segment)) continue;
        $id = stti_v070_text($segment['segment_id'] ?? '');
        if ($id === '') $id = 'T' . ($index + 1); // internal relation identity only.
        $segment['segment_id'] = $id;
        $segment['from_stop_ref'] = stti_v070_text($segment['from_stop_ref'] ?? '');
        $segment['to_stop_ref'] = stti_v070_text($segment['to_stop_ref'] ?? '');
        $segment['route_variant_ref'] = stti_v070_text($segment['route_variant_ref'] ?? '');
        $segment['review_status'] = stti_v070_review_status($segment['review_status'] ?? 'pending');
        $segment['review_note'] = stti_v070_text($segment['review_note'] ?? '');
        $segment['reviewed_by'] = stti_v070_review_actor($segment);
        $segment['reviewed_at'] = stti_v070_review_time($segment);
        $out[] = $segment;
    }
    return $out;
}

/** Resolve only the identity edge to Hotel Intelligence; never copy hotel facts. */
function stti_v070_hotel_link_state($hotel_id) {
    $hotel_id = strtoupper(stti_v070_text($hotel_id));
    if (!preg_match('/^STH-\d{6}$/', $hotel_id)) {
        return array('status' => 'invalid', 'hotel_id' => $hotel_id);
    }
    if (!function_exists('post_type_exists') || !post_type_exists('sthi_hotel') || !function_exists('get_posts')) {
        return array('status' => 'integration_unavailable', 'hotel_id' => $hotel_id);
    }
    $ids = get_posts(array(
        'post_type' => 'sthi_hotel',
        'post_status' => array('publish', 'draft', 'pending', 'private'),
        'posts_per_page' => 2,
        'fields' => 'ids',
        'meta_key' => '_sthi_hotel_id',
        'meta_value' => $hotel_id,
        'no_found_rows' => true,
    ));
    if (count($ids) === 0) return array('status' => 'unresolved', 'hotel_id' => $hotel_id);
    if (count($ids) !== 1) return array('status' => 'duplicate', 'hotel_id' => $hotel_id);
    return array('status' => 'resolved', 'hotel_id' => $hotel_id, 'post_id' => (int) $ids[0]);
}

function stti_v070_unique_ids($items, $field, &$errors, $label, $required = true) {
    $ids = array();
    foreach ($items as $index => $item) {
        $id = stti_v070_text(is_array($item) ? ($item[$field] ?? '') : '');
        if ($id === '') {
            if ($required) $errors[] = sprintf('%s #%d is missing %s.', $label, $index + 1, $field);
            continue;
        }
        if (isset($ids[$id])) {
            $errors[] = sprintf('Duplicate %s %s.', $field, $id);
            continue;
        }
        $ids[$id] = true;
    }
    return $ids;
}
