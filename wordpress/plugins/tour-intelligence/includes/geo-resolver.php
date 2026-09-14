<?php
/** STTI v0.7.1 — Canonical Geo Resolver. Private/editorial only; no public/SEO unlock. */

/** STTI v0.7.1 — Canonical Geo Resolver foundation. Private/editorial only. */

function stti_v071_float_or_null($value) {
    if ($value === null || $value === '') return null;
    if (is_string($value)) $value = str_replace(',', '.', trim($value));
    return is_numeric($value) ? (float) $value : null;
}

function stti_v071_geo_source_type($value) {
    $value = stti_v070_text($value);
    $allowed = array('unknown','source','manual_verified','external_reference','hotel_intelligence');
    return in_array($value, $allowed, true) ? $value : 'unknown';
}

function stti_v071_normalize_geo_records($records) {
    if (!is_array($records)) return array();
    $out = array();
    foreach (array_values($records) as $record) {
        if (!is_array($record)) continue;
        $stop_ref = stti_v070_text($record['stop_ref'] ?? '');
        $lat = stti_v071_float_or_null($record['latitude'] ?? null);
        $lng = stti_v071_float_or_null($record['longitude'] ?? null);
        $resolved = $lat !== null && $lng !== null && $lat >= -90 && $lat <= 90 && $lng >= -180 && $lng <= 180;
        $out[] = array(
            'stop_ref' => $stop_ref,
            'state' => $resolved ? 'resolved' : 'unresolved',
            'latitude' => $resolved ? $lat : null,
            'longitude' => $resolved ? $lng : null,
            'source_type' => stti_v071_geo_source_type($record['source_type'] ?? 'unknown'),
            'source_ref' => stti_v070_text($record['source_ref'] ?? ''),
            'review_status' => stti_v070_review_status($record['review_status'] ?? 'pending'),
            'review_note' => stti_v070_text($record['review_note'] ?? ''),
            'reviewed_by' => stti_v070_review_actor($record),
            'reviewed_at' => stti_v070_review_time($record),
        );
    }
    return $out;
}

function stti_v071_decode_geo_json($raw) {
    if (function_exists('wp_unslash')) $raw = wp_unslash((string) $raw);
    $decoded = json_decode((string) $raw, true);
    return stti_v071_normalize_geo_records(is_array($decoded) ? $decoded : array());
}

function stti_v071_enrich_payload($payload, $existing_payload = array(), $raw_geo = null) {
    $payload = is_array($payload) ? $payload : array();
    $existing_payload = is_array($existing_payload) ? $existing_payload : array();
    $existing = stti_v071_normalize_geo_records($existing_payload['geo']['route_stops'] ?? array());
    $records = $raw_geo === null ? $existing : stti_v071_decode_geo_json($raw_geo);
    $records = stti_v070_stamp_review_transitions($records, $existing, 'stop_ref');
    $payload['geo'] = array(
        'contract' => 'STTI-GEO-1.0.0',
        'route_stops' => $records,
    );
    return $payload;
}

/** Renderer consumes only confirmed canonical coordinates. */
function stti_v071_customer_map_config($payload, $stable_id, $checksum) {
    $payload = is_array($payload) ? $payload : array();
    $route = is_array($payload['route'] ?? null) ? $payload['route'] : array();
    $stops = is_array($route['stops'] ?? null) ? array_values($route['stops']) : array();
    $geo = stti_v071_normalize_geo_records($payload['geo']['route_stops'] ?? array());
    $geo_by_ref = array();
    foreach ($geo as $record) if ($record['stop_ref'] !== '') $geo_by_ref[$record['stop_ref']] = $record;

    $map_stops = array();
    $unresolved = array();
    foreach ($stops as $i => $stop) {
        if (!is_array($stop)) continue;
        $stop_ref = stti_v070_text($stop['stop_id'] ?? '');
        $city = stti_v070_text($stop['city'] ?? '');
        $label = stti_v070_text($stop['label'] ?? '');
        $name = $city !== '' ? $city : ($label !== '' ? $label : 'Durak');
        $record = $stop_ref !== '' ? ($geo_by_ref[$stop_ref] ?? null) : null;
        if (!$record || $record['state'] !== 'resolved' || $record['review_status'] !== 'confirmed') {
            $unresolved[] = array('order'=>$i+1,'stop_id'=>$stop_ref,'name'=>$name,'reason'=>$stop_ref === '' ? 'missing_stop_id' : 'geo_not_confirmed');
            continue;
        }
        $map_stops[] = array(
            'order' => $i + 1,
            'stop_id' => $stop_ref,
            'name' => $name,
            'lat' => (float) $record['latitude'],
            'lng' => (float) $record['longitude'],
            'source_type' => $record['source_type'],
            'reviewed_at' => $record['reviewed_at'],
        );
    }

    return array(
        'contract' => 'STTI-GEO-1.0.0',
        'stableId' => (string) $stable_id,
        'checksum' => (string) $checksum,
        'stops' => $map_stops,
        'unresolved' => $unresolved,
        'tiles' => 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
        'tileAttribution' => '&copy; OpenStreetMap contributors',
        'maxZoom' => 13,
        'minZoom' => 2,
        'segmentDurationMs' => 700,
        'markerRevealMs' => 160,
    );
}

function stti_v071_geo_review($payload) {
    $errors = array(); $warnings = array(); $blockers = array();
    $route = is_array($payload['route'] ?? null) ? $payload['route'] : array();
    $stops = is_array($route['stops'] ?? null) ? array_values($route['stops']) : array();
    $geo = stti_v071_normalize_geo_records($payload['geo']['route_stops'] ?? array());

    $stop_ids = array();
    foreach ($stops as $i => $stop) {
        if (!is_array($stop)) continue;
        $id = stti_v070_text($stop['stop_id'] ?? '');
        if ($id === '') { $blockers[] = sprintf('Route stop #%d has no canonical stop_id; geo cannot be resolved safely.', $i + 1); continue; }
        if (isset($stop_ids[$id])) $errors[] = sprintf('Duplicate route stop_id %s.', $id);
        $stop_ids[$id] = true;
    }

    $seen = array();
    foreach ($geo as $i => $record) {
        $ref = $record['stop_ref'];
        if ($ref === '') { $errors[] = sprintf('Geo record #%d is missing stop_ref.', $i + 1); continue; }
        if (isset($seen[$ref])) $errors[] = sprintf('Duplicate geo stop_ref %s.', $ref);
        $seen[$ref] = true;
        if (!isset($stop_ids[$ref])) $errors[] = sprintf('Geo record references unknown stop %s.', $ref);

        $raw_state = $record['state'];
        if ($record['review_status'] === 'rejected') continue;
        if ($raw_state === 'resolved') {
            if ($record['latitude'] < -90 || $record['latitude'] > 90) $errors[] = sprintf('Geo %s latitude is out of range.', $ref);
            if ($record['longitude'] < -180 || $record['longitude'] > 180) $errors[] = sprintf('Geo %s longitude is out of range.', $ref);
            if ($record['source_type'] === 'unknown') $blockers[] = sprintf('Geo %s has coordinates but no verified source type.', $ref);
            if ($record['source_ref'] === '') $blockers[] = sprintf('Geo %s has coordinates but no source reference.', $ref);
            if ($record['review_status'] !== 'confirmed') $blockers[] = sprintf('Geo %s coordinates still require human confirmation.', $ref);
        } else {
            if ($record['review_status'] === 'confirmed') $errors[] = sprintf('Geo %s cannot be confirmed while unresolved.', $ref);
            $blockers[] = sprintf('Geo %s remains unresolved.', $ref);
        }
    }

    foreach (array_keys($stop_ids) as $ref) {
        if (!isset($seen[$ref])) $blockers[] = sprintf('Route stop %s has no geo record.', $ref);
    }

    $blockers = array_values(array_unique(array_merge($blockers, $errors)));
    $editorial = stti_v070_text($payload['lifecycle']['editorial'] ?? 'needs_review');
    $ready = !$errors && !$blockers;
    if ($editorial === 'approved' && !$ready) $errors[] = 'Editorial approval is blocked until all route-stop coordinates are canonical, source-backed, and human-confirmed.';

    return array(
        'status' => $ready ? 'ready' : 'pending',
        'ready_for_renderer' => $ready,
        'errors' => array_values(array_unique($errors)),
        'warnings' => array_values(array_unique($warnings)),
        'blockers' => array_values(array_unique($blockers)),
        'counts' => array('route_stops'=>count($stops),'geo_records'=>count($geo),'confirmed'=>count(array_filter($geo, fn($r)=>$r['state']==='resolved' && $r['review_status']==='confirmed'))),
    );
}

/** v0.7.1 manual save: v0.7 relations + canonical geo, validated before any Stable-ID allocation/write. */
function stti_v071_handle_save_candidate() {
    if (!function_exists('current_user_can') || !current_user_can('manage_options')) wp_die('Unauthorized');
    check_admin_referer('stti_save_candidate');
    $f = stti_sanitize_form($_POST);
    $validation = stti_validate_form($f);

    global $wpdb; $t = stti_tables();
    $is_new = $f['stable_id'] === '';
    if ($is_new && !empty($f['origin_fixture_id'])) {
        $already = stti_find_candidate_by_origin_fixture($f['origin_fixture_id']);
        if ($already) { wp_safe_redirect(add_query_arg(array('page'=>'stti-tour-intelligence','view'=>'editor','tour'=>$already['stable_id'],'stti_saved'=>'fixture_exists'), admin_url('admin.php'))); exit; }
    }
    $existing = $is_new ? null : stti_get_candidate($f['stable_id']);
    if (!$is_new && !$existing) wp_die('Unknown STT stable ID. Fail closed.');
    $existing_payload = stti_v070_existing_payload($existing);

    $preview_id = $is_new ? 'STT-000000' : $f['stable_id'];
    $payload = stti_build_payload($f, $preview_id);
    $raw_variants = array_key_exists('route_variants_json', $_POST) ? $_POST['route_variants_json'] : null;
    $raw_geo = array_key_exists('route_geo_json', $_POST) ? $_POST['route_geo_json'] : null;
    $payload = stti_v070_enrich_payload($payload, $existing_payload, $raw_variants);
    $payload = stti_v071_enrich_payload($payload, $existing_payload, $raw_geo);

    $relations = stti_v070_relation_review($payload);
    $geo = stti_v071_geo_review($payload);
    $validation['errors'] = array_values(array_unique(array_merge($validation['errors'], $relations['errors'], $geo['errors'])));
    $validation['warnings'] = array_values(array_unique(array_merge($validation['warnings'], $relations['warnings'], $relations['blockers'], $geo['warnings'], $geo['blockers'])));
    if ($validation['errors']) { wp_safe_redirect(add_query_arg(array('page'=>'stti-tour-intelligence','view'=>'editor','tour'=>$f['stable_id'],'stti_error'=>rawurlencode(implode(' | ', $validation['errors']))), admin_url('admin.php'))); exit; }

    $stable_id = $is_new ? stti_allocate_stable_id() : $f['stable_id'];
    if (is_wp_error($stable_id)) wp_die(esc_html($stable_id->get_error_message()));
    $payload['stable_id'] = $stable_id;
    $json = wp_json_encode($payload, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
    $checksum = hash('sha256', $json); $now = current_time('mysql');

    if ($is_new) {
        $wpdb->insert($t['tours'], array('stable_id'=>$stable_id,'schema_version'=>STTI_SCHEMA_VERSION,'public_title'=>$f['public_title'],'slug'=>$f['slug'],'editorial'=>$f['editorial'],'schedule_status'=>$f['schedule_status'],'availability'=>$f['availability'],'temporal'=>$f['temporal'],'source_completeness'=>$f['source_completeness'],'payload'=>$json,'checksum'=>$checksum,'created_by'=>get_current_user_id(),'created_at'=>$now,'updated_at'=>$now), array('%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%d','%s','%s'));
        if (!$wpdb->insert_id) wp_die('Private candidate insert failed.');
        stti_audit_event($stable_id, 'candidate_created', null, $payload); $event='created';
    } else {
        $before = $existing_payload;
        if (hash_equals((string)$existing['checksum'], $checksum)) { stti_audit_event($stable_id,'candidate_unchanged',$before,$payload); $event='unchanged'; }
        else {
            $wpdb->update($t['tours'], array('schema_version'=>STTI_SCHEMA_VERSION,'public_title'=>$f['public_title'],'slug'=>$f['slug'],'editorial'=>$f['editorial'],'schedule_status'=>$f['schedule_status'],'availability'=>$f['availability'],'temporal'=>$f['temporal'],'source_completeness'=>$f['source_completeness'],'payload'=>$json,'checksum'=>$checksum,'updated_at'=>$now), array('stable_id'=>$stable_id), array('%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s'), array('%s'));
            stti_audit_event($stable_id,'candidate_updated',$before,$payload); $event='updated';
        }
    }
    wp_safe_redirect(add_query_arg(array('page'=>'stti-tour-intelligence','view'=>'editor','tour'=>$stable_id,'stti_saved'=>$event), admin_url('admin.php'))); exit;
}

function stti_v071_admin_assets($hook) {
    if ($hook !== 'toplevel_page_stti-tour-intelligence') return;
    $geo = array();
    $stable_id = isset($_GET['tour']) ? stti_v070_text(function_exists('wp_unslash') ? wp_unslash($_GET['tour']) : $_GET['tour']) : '';
    if ($stable_id !== '' && function_exists('stti_get_candidate')) {
        $row = stti_get_candidate($stable_id);
        $payload = stti_v070_existing_payload($row);
        $geo = stti_v071_normalize_geo_records($payload['geo']['route_stops'] ?? array());
    }
    wp_add_inline_style('stti-admin', '.stti-v071-geo-panel{margin-top:20px}.stti-v071-geo-grid{display:grid;gap:14px}.stti-v071-geo-row{border:1px solid #dcdcde;border-radius:10px;padding:14px;background:#fff}.stti-v071-geo-row header{display:flex;justify-content:space-between;gap:12px;align-items:center;margin-bottom:12px}.stti-v071-fields{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:10px}.stti-v071-fields label{display:grid;gap:5px;font-weight:600}.stti-v071-fields input,.stti-v071-fields select{width:100%}.stti-v071-fields .wide{grid-column:span 3}.stti-v071-blocker{margin:10px 0 0;color:#8a2424;font-weight:700}@media(max-width:900px){.stti-v071-fields{grid-template-columns:1fr}.stti-v071-fields .wide{grid-column:auto}}');
    wp_enqueue_script('stti-v071-geo', STTI_URL . 'assets/geo-resolver.js', array('stti-v070-review-relations'), STTI_VERSION, true);
    wp_localize_script('stti-v071-geo', 'STTI_V071_GEO', array('version'=>'0.7.1','records'=>$geo,'publicLocks'=>false));
}

function stti_v071_customer_geo_assets() {
    if (!function_exists('stti_customer_preview_is_request') || !stti_customer_preview_is_request()) return;
    $stable_id = isset($_GET['tour']) ? stti_v070_text(function_exists('wp_unslash') ? wp_unslash($_GET['tour']) : $_GET['tour']) : '';
    if ($stable_id === '' || !function_exists('stti_get_candidate')) return;
    $row = stti_get_candidate($stable_id); if (!$row) return;
    $payload = stti_v070_existing_payload($row);
    $config = stti_v071_customer_map_config($payload, $stable_id, (string)($row['checksum'] ?? ''));
    // Remove the pilot handle (and its localized Nominatim config), then load the same shell JS
    // under a new handle with an empty route config. Header/layout behavior is preserved, but
    // browser geocoding cannot run. Canonical map rendering is a separate v0.7.1 layer.
    wp_dequeue_script('stti-customer-preview');
    wp_enqueue_script('stti-customer-preview-shell-v071', STTI_URL . 'assets/customer-preview.js', array('stti-leaflet'), STTI_VERSION, true);
    wp_localize_script('stti-customer-preview-shell-v071', 'STTI_CX_MAP', array('stops'=>array()));
    wp_enqueue_script('stti-v071-canonical-geo-map', STTI_URL . 'assets/canonical-geo-map.js', array('stti-customer-preview-shell-v071','stti-leaflet'), STTI_VERSION, true);
    wp_localize_script('stti-v071-canonical-geo-map', 'STTI_V071_GEO_MAP', $config);
}

if (function_exists('add_action') && function_exists('remove_action')) {
    remove_action('admin_post_stti_save_candidate', 'stti_v070_handle_save_candidate');
    add_action('admin_post_stti_save_candidate', 'stti_v071_handle_save_candidate');
    add_action('admin_enqueue_scripts', 'stti_v071_admin_assets', 40);
    add_action('wp_enqueue_scripts', 'stti_v071_customer_geo_assets', 999);
}
