<?php
function stti_v070_relation_review($payload, $hotel_resolver = null) {
    $errors = array();
    $warnings = array();
    $blockers = array();

    $route = is_array($payload['route'] ?? null) ? $payload['route'] : array();
    $stops = is_array($route['stops'] ?? null) ? array_values($route['stops']) : array();
    $variants = stti_v070_normalize_route_variants($route['variants'] ?? array());
    $hotels = stti_v070_normalize_hotel_relations($payload['stays']['hotels'] ?? array());
    $segments = stti_v070_normalize_transport_relations($payload['transport']['segments'] ?? array());

    // Legacy stops without stop_id remain valid until a v0.7 relation actually references stops.
    $stop_refs_used = false;
    foreach ($variants as $variant) if ($variant['review_status'] !== 'rejected' && $variant['stop_refs']) $stop_refs_used = true;
    foreach ($segments as $segment) if ($segment['review_status'] !== 'rejected' && ($segment['from_stop_ref'] !== '' || $segment['to_stop_ref'] !== '')) $stop_refs_used = true;

    $stop_ids = stti_v070_unique_ids($stops, 'stop_id', $errors, 'Route stop', $stop_refs_used);
    $variant_ids = stti_v070_unique_ids($variants, 'variant_id', $errors, 'Route variant');
    $hotel_ids = stti_v070_unique_ids($hotels, 'relation_id', $errors, 'Hotel relation');
    $segment_ids = stti_v070_unique_ids($segments, 'segment_id', $errors, 'Transport segment');

    $counts = array(
        'route_variants' => count($variants), 'hotel_relations' => count($hotels), 'transport_segments' => count($segments),
        'pending' => 0, 'confirmed' => 0, 'rejected' => 0, 'unresolved_hotel_links' => 0,
    );

    $review_item = static function ($kind, $id, $status) use (&$counts, &$blockers) {
        $status = stti_v070_review_status($status);
        $counts[$status]++;
        if ($status === 'pending') $blockers[] = sprintf('%s %s still requires human review.', $kind, $id);
    };

    $primary_variants = 0;
    foreach ($variants as $variant) {
        $id = $variant['variant_id'];
        $review_item('Route variant', $id, $variant['review_status']);
        if ($variant['review_status'] === 'rejected') continue;
        if ($variant['role'] === 'primary') $primary_variants++;
        foreach ($variant['stop_refs'] as $ref) if (!isset($stop_ids[$ref])) $errors[] = sprintf('Route variant %s references unknown stop %s.', $id, $ref);
        foreach ($variant['hotel_relation_refs'] as $ref) if (!isset($hotel_ids[$ref])) $errors[] = sprintf('Route variant %s references unknown hotel relation %s.', $id, $ref);
        foreach ($variant['transport_segment_refs'] as $ref) if (!isset($segment_ids[$ref])) $errors[] = sprintf('Route variant %s references unknown transport segment %s.', $id, $ref);
        if (!$variant['stop_refs']) $warnings[] = sprintf('Route variant %s has no stop refs; unknown route membership was not inferred.', $id);
    }
    if ($primary_variants > 1) $errors[] = 'More than one active Route Variant is marked primary.';

    // Hotel option groups may remain unknown, but contradictory multiple selections are blocked.
    $selected_by_group = array();
    $hotel_resolver = is_callable($hotel_resolver) ? $hotel_resolver : 'stti_v070_hotel_link_state';
    foreach ($hotels as $hotel) {
        $id = $hotel['relation_id'];
        $review_item('Hotel relation', $id, $hotel['review_status']);
        if ($hotel['review_status'] === 'rejected') continue;
        if ($hotel['option_group_id'] !== '' && $hotel['selection_status'] === 'selected') {
            $group = $hotel['option_group_id'];
            $selected_by_group[$group] = ($selected_by_group[$group] ?? 0) + 1;
        }
        if ($hotel['mode'] === 'hotel_intelligence') {
            $link = call_user_func($hotel_resolver, $hotel['hotel_stable_id']);
            $link_status = is_array($link) ? ($link['status'] ?? 'integration_unavailable') : 'integration_unavailable';
            if ($link_status !== 'resolved') {
                $counts['unresolved_hotel_links']++;
                $blockers[] = sprintf('Hotel relation %s link is %s.', $id, $link_status);
                if ($link_status === 'invalid') $errors[] = sprintf('Hotel relation %s must use an STH-###### stable ID.', $id);
                else $warnings[] = sprintf('Hotel relation %s Hotel Intelligence link is %s.', $id, $link_status);
            }
        } elseif ($hotel['unresolved_name'] === '') {
            $warnings[] = sprintf('Hotel relation %s has no source hotel name; unknown was preserved.', $id);
            $blockers[] = sprintf('Hotel relation %s remains unresolved.', $id);
        } else {
            $blockers[] = sprintf('Hotel relation %s remains an unresolved source hotel.', $id);
        }
    }
    foreach ($selected_by_group as $group => $count) if ($count > 1) $errors[] = sprintf('Hotel option group %s has more than one selected option.', $group);

    $rejected_variants = array();
    foreach ($variants as $variant) if ($variant['review_status'] === 'rejected') $rejected_variants[$variant['variant_id']] = true;
    foreach ($segments as $segment) {
        $id = $segment['segment_id'];
        $review_item('Transport segment', $id, $segment['review_status']);
        if ($segment['review_status'] === 'rejected') continue;
        foreach (array('from_stop_ref' => 'origin', 'to_stop_ref' => 'destination') as $field => $label) {
            $ref = $segment[$field];
            if ($ref !== '' && !isset($stop_ids[$ref])) $errors[] = sprintf('Transport segment %s references unknown %s stop %s.', $id, $label, $ref);
        }
        $variant_ref = $segment['route_variant_ref'];
        if ($variant_ref !== '' && !isset($variant_ids[$variant_ref])) $errors[] = sprintf('Transport segment %s references unknown route variant %s.', $id, $variant_ref);
        if ($variant_ref !== '' && isset($rejected_variants[$variant_ref])) $errors[] = sprintf('Transport segment %s references rejected route variant %s.', $id, $variant_ref);
    }

    // Rejected rows may remain as private review evidence, but active variants cannot reference them.
    $rejected_hotels = array();
    foreach ($hotels as $hotel) if ($hotel['review_status'] === 'rejected') $rejected_hotels[$hotel['relation_id']] = true;
    $rejected_segments = array();
    foreach ($segments as $segment) if ($segment['review_status'] === 'rejected') $rejected_segments[$segment['segment_id']] = true;
    foreach ($variants as $variant) {
        if ($variant['review_status'] === 'rejected') continue;
        foreach ($variant['hotel_relation_refs'] as $ref) if (isset($rejected_hotels[$ref])) $errors[] = sprintf('Route variant %s references rejected hotel relation %s.', $variant['variant_id'], $ref);
        foreach ($variant['transport_segment_refs'] as $ref) if (isset($rejected_segments[$ref])) $errors[] = sprintf('Route variant %s references rejected transport segment %s.', $variant['variant_id'], $ref);
    }

    $blockers = array_values(array_unique(array_merge($blockers, $errors)));
    $editorial = stti_v070_text($payload['lifecycle']['editorial'] ?? 'needs_review');
    $ready = !$errors && !$blockers;
    if ($editorial === 'approved' && !$ready) $errors[] = 'Editorial approval is blocked until all active relations are resolved and human-confirmed.';

    return array(
        'status' => $ready ? 'ready' : 'pending',
        'ready_for_editorial_approval' => $ready,
        'counts' => $counts,
        'errors' => array_values(array_unique($errors)),
        'warnings' => array_values(array_unique($warnings)),
        'blockers' => array_values(array_unique($blockers)),
        'normalized' => array('route_variants' => $variants, 'hotel_relations' => $hotels, 'transport_segments' => $segments),
    );
}
