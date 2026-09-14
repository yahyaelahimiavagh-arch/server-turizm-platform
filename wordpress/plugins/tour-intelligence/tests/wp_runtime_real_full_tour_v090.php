<?php
/** STTI v0.9.0 First Real Full Tour — disposable WordPress runtime evidence. */
if (!defined('ABSPATH') || !defined('WP_CLI')) throw new RuntimeException('Run through WP-CLI only.');
$ok = static function($condition, $message) {
    if (!$condition) throw new RuntimeException($message);
    WP_CLI::log('PASS: ' . $message);
};
$fixture_path = STTI_DIR . 'tests/fixtures/STT-000001-v090-real-tour.json';
$raw = file_get_contents($fixture_path);
$fixture = $raw === false ? null : json_decode($raw, true);
$ok(is_array($fixture), 'v0.9 real-tour fixture loads');
$ok(($fixture['pilot_contract'] ?? '') === 'STTI-REAL-TOUR-PILOT-1.0.0', 'real-tour pilot contract is exact');
$ok(($fixture['production_approval_claimed'] ?? true) === false, 'fixture does not claim production approval');
$payload = $fixture['canonical_payload'] ?? null;
$ok(is_array($payload), 'canonical real-tour payload exists');
$ok(defined('STTI_RELEASE_VERSION') && STTI_RELEASE_VERSION === '0.9.0', 'STTI release 0.9.0 is loaded');
$ok(defined('STTI_VERSION') && STTI_VERSION === '0.7.1', 'accepted compatibility runtime remains 0.7.1');
$ok(($payload['stable_id'] ?? '') === 'STT-000001', 'real pilot keeps authoritative STT-000001 identity');
$ok(($payload['identity']['tour_code'] ?? '') === 'IRN-2026-01', 'operator program code is preserved');
$ok(($payload['date']['start_date'] ?? '') === '2027-01-26' && ($payload['date']['end_date'] ?? '') === '2027-02-12', 'current operator dates supersede historical fixture dates');
$ok(($payload['date']['duration_days'] ?? 0) === 18 && ($payload['date']['duration_nights'] ?? 0) === 17, '18-day / 17-night duration is deterministic');
$ok(count($payload['route']['stops'] ?? array()) === 5, 'five operator route stops are present');
$ok(count($payload['itinerary'] ?? array()) === 18, '18-day structural itinerary skeleton is present');
$ok(($payload['itinerary'][0]['date'] ?? '') === '2027-01-26' && ($payload['itinerary'][17]['date'] ?? '') === '2027-02-12', 'itinerary skeleton spans exact operator dates');
$ok(($payload['stays']['hotels'] ?? null) === array(), 'missing hotel facts remain empty instead of invented');
$ok(($payload['transport']['segments'] ?? null) === array(), 'missing transport facts remain empty instead of invented');
$ok(($payload['requirements']['visa_status'] ?? '') === 'unknown', 'raw Vize FALSE is not semantically over-interpreted');
$ok(($payload['pricing']['amount'] ?? null) === 899 && ($payload['pricing']['currency'] ?? '') === 'EUR' && ($payload['pricing']['basis'] ?? '') === 'unknown', '899 EUR is preserved while price basis stays unknown');

$relations = stti_v070_relation_review($payload);
$ok(($relations['ready_for_editorial_approval'] ?? false) === true, 'source-exact primary route relation graph is review-ready without invented hotel/transport');
$geo = stti_v071_geo_review($payload);
$ok(($geo['ready_for_renderer'] ?? false) === true && ($geo['counts']['confirmed'] ?? 0) === 5, 'five explicit external-reference geo points are renderer-ready');
$model = stti_v080_renderer_model($payload, 'STT-000001', 'fixture-checksum');
$ok(($model['ready'] ?? false) === true, 'accepted v0.8 renderer is ready for the real-tour fixture');
$ok(($model['variant_state']['active']['variant_id'] ?? '') === 'V1', 'confirmed primary V1 drives the real renderer');
$ok(count($model['route_stops'] ?? array()) === 5 && count($model['map']['stops'] ?? array()) === 5, 'renderer projects all five source route stops and canonical geo points');
$ok(count($model['hotels'] ?? array()) === 0 && count($model['transport'] ?? array()) === 0, 'renderer does not fabricate absent hotel or transport');
$ok(($model['public'] ?? true) === false && ($model['indexable'] ?? true) === false && ($model['sitemap'] ?? true) === false && ($model['schema'] ?? true) === false && ($model['canonical'] ?? true) === false, 'all public and SEO exposure stays OFF');

wp_set_current_user(1);
global $wpdb;
$tables = stti_tables();
$snapshot = static function() use ($wpdb, $tables) {
    return array(
        'tours' => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tables['tours']}"),
        'audit' => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tables['audit']}"),
        'sequence' => (int) get_option('stti_next_sequence', 0),
    );
};
$before = $snapshot();
$ok(stti_get_candidate('STT-000001') === null, 'disposable runtime begins without STT-000001');
update_option('stti_next_sequence', 1, false);
$stable_id = stti_allocate_stable_id();
$ok(!is_wp_error($stable_id) && $stable_id === 'STT-000001', 'stable allocator assigns STT-000001 deterministically in disposable runtime');
$payload['stable_id'] = $stable_id;
$json = wp_json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
$checksum = hash('sha256', $json);
$now = current_time('mysql');
$inserted = $wpdb->insert($tables['tours'], array(
    'stable_id' => $stable_id,
    'schema_version' => STTI_SCHEMA_VERSION,
    'public_title' => $payload['identity']['public_title'],
    'slug' => $payload['identity']['slug'],
    'editorial' => $payload['lifecycle']['editorial'],
    'schedule_status' => $payload['lifecycle']['schedule'],
    'availability' => $payload['lifecycle']['availability'],
    'temporal' => $payload['lifecycle']['temporal'],
    'source_completeness' => $payload['provenance']['source_completeness'],
    'payload' => $json,
    'checksum' => $checksum,
    'created_by' => get_current_user_id(),
    'created_at' => $now,
    'updated_at' => $now,
), array('%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%d','%s','%s'));
$ok($inserted === 1, 'real-tour candidate persists in disposable canonical table');
stti_audit_event($stable_id, 'v090_real_tour_pilot_created', null, $payload);
$stored = stti_get_candidate($stable_id);
$ok(is_array($stored) && hash_equals($checksum, (string) $stored['checksum']), 'stored candidate checksum matches canonical JSON');
$stored_payload = json_decode((string) $stored['payload'], true);
$stored_model = stti_v080_renderer_model($stored_payload, $stable_id, (string) $stored['checksum']);
$ok(($stored_model['ready'] ?? false) === true && count($stored_model['map']['stops'] ?? array()) === 5, 'stored canonical record renders end-to-end');
$audit = stti_get_audit($stable_id, 10);
$ok((bool) array_filter($audit, static fn($event) => ($event['event'] ?? '') === 'v090_real_tour_pilot_created'), 'real-tour persistence emits audit evidence');

$wpdb->delete($tables['audit'], array('stable_id' => $stable_id), array('%s'));
$wpdb->delete($tables['tours'], array('stable_id' => $stable_id), array('%s'));
update_option('stti_next_sequence', $before['sequence'], false);
$after = $snapshot();
$ok($after === $before, 'disposable real-tour runtime cleans up tables and Stable-ID sequence');
WP_CLI::success('STTI v0.9.0 First Real Full Tour runtime: PASS');
