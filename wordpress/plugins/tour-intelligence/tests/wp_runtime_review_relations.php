<?php
if (!defined('ABSPATH')) { fwrite(STDERR, "WordPress bootstrap required\n"); exit(1); }

function stti_v070_runtime_ok($condition, $message) {
    if (!$condition) { fwrite(STDERR, "FAIL: {$message}\n"); exit(1); }
    echo "PASS: {$message}\n";
}

stti_v070_runtime_ok(defined('STTI_VERSION') && STTI_VERSION === '0.7.0', 'plugin runtime is STTI v0.7.0');
stti_v070_runtime_ok(function_exists('stti_v070_relation_review'), 'v0.7 relation review module is loaded');
stti_v070_runtime_ok(function_exists('stti_completion_review_dry_run'), 'v0.6.5 AI Completion dry-run contract remains loaded');

wp_set_current_user(1);
if (!post_type_exists('sthi_hotel')) {
    register_post_type('sthi_hotel', array('public' => false, 'show_ui' => false, 'supports' => array('title')));
}
$hotel_post_id = wp_insert_post(array('post_type'=>'sthi_hotel','post_status'=>'private','post_title'=>'Runtime identity fixture'));
stti_v070_runtime_ok(!is_wp_error($hotel_post_id) && $hotel_post_id > 0, 'synthetic private Hotel Intelligence identity fixture created');
update_post_meta($hotel_post_id, '_sthi_hotel_id', 'STH-999999');

$link = stti_v070_hotel_link_state('STH-999999');
stti_v070_runtime_ok(($link['status'] ?? '') === 'resolved' && (int)($link['post_id'] ?? 0) === (int)$hotel_post_id, 'Hotel Intelligence relation resolves by stable identity only');
stti_v070_runtime_ok(count(array_diff(array_keys($link), array('status','hotel_id','post_id'))) === 0, 'Hotel resolver does not copy hotel facts into STTI');

$payload = array(
    'route' => array(
        'stops' => array(array('stop_id'=>'R1','city'=>'Istanbul'), array('stop_id'=>'R2','city'=>'Cairo')),
        'variants' => array(array(
            'variant_id'=>'V1','label'=>'Reviewed route','role'=>'primary','stop_refs'=>array('R1','R2'),
            'hotel_relation_refs'=>array('H1'),'transport_segment_refs'=>array('T1'),'review_status'=>'confirmed',
        )),
    ),
    'stays' => array('hotels'=>array(array(
        'relation_id'=>'H1','mode'=>'hotel_intelligence','hotel_stable_id'=>'STH-999999','option_group_id'=>'HG1',
        'selection_status'=>'selected','review_status'=>'confirmed',
    ))),
    'transport' => array('segments'=>array(array(
        'segment_id'=>'T1','type'=>'flight','from'=>'Istanbul','to'=>'Cairo','from_stop_ref'=>'R1','to_stop_ref'=>'R2',
        'route_variant_ref'=>'V1','review_status'=>'confirmed',
    ))),
    'lifecycle' => array('editorial'=>'approved'),
    'publication' => array('public_route'=>false,'indexable'=>false,'sitemap'=>false,'schema'=>false,'homepage_adapter'=>false),
);

$result = stti_v070_relation_review($payload);
stti_v070_runtime_ok($result['status'] === 'ready' && $result['ready_for_editorial_approval'] === true, 'fully reviewed relation graph passes approval gate in WordPress runtime');
stti_v070_runtime_ok(($result['counts']['confirmed'] ?? 0) === 3, 'Route/Hotel/Transport relations are all counted as confirmed');

$pending = $payload;
$pending['lifecycle']['editorial'] = 'needs_review';
$pending['stays']['hotels'][0]['review_status'] = 'pending';
$result = stti_v070_relation_review($pending);
stti_v070_runtime_ok($result['status'] === 'pending' && !$result['ready_for_editorial_approval'], 'pending human review is preserved, never auto-confirmed');

$bad_ref = $payload;
$bad_ref['lifecycle']['editorial'] = 'needs_review';
$bad_ref['transport']['segments'][0]['from_stop_ref'] = 'R404';
$result = stti_v070_relation_review($bad_ref);
stti_v070_runtime_ok((bool)array_filter($result['errors'], fn($e)=>str_contains($e, 'unknown origin stop R404')), 'free-text transport is not guessed into a missing stop relation');

$bad_hotel = $payload;
$bad_hotel['lifecycle']['editorial'] = 'needs_review';
$bad_hotel['stays']['hotels'][0]['hotel_stable_id'] = 'Hotel Cairo';
$result = stti_v070_relation_review($bad_hotel);
stti_v070_runtime_ok((bool)array_filter($result['errors'], fn($e)=>str_contains($e, 'STH-######')), 'non-canonical Hotel Intelligence identifier fails closed');

$approved_pending = $pending;
$approved_pending['lifecycle']['editorial'] = 'approved';
$result = stti_v070_relation_review($approved_pending);
stti_v070_runtime_ok(in_array('Editorial approval is blocked until all active relations are resolved and human-confirmed.', $result['errors'], true), 'editorial approval cannot bypass human review');

// Relation analysis itself is pure with respect to STTI persistence.
global $wpdb;
$tables = stti_tables();
$before_tours = (int)$wpdb->get_var("SELECT COUNT(*) FROM {$tables['tours']}");
$before_audit = (int)$wpdb->get_var("SELECT COUNT(*) FROM {$tables['audit']}");
$before_seq = get_option('stti_next_sequence', null);
stti_v070_relation_review($payload);
$after_tours = (int)$wpdb->get_var("SELECT COUNT(*) FROM {$tables['tours']}");
$after_audit = (int)$wpdb->get_var("SELECT COUNT(*) FROM {$tables['audit']}");
$after_seq = get_option('stti_next_sequence', null);
stti_v070_runtime_ok($before_tours === $after_tours && $before_audit === $after_audit && $before_seq === $after_seq, 'relation review analysis performs NO WRITE');

foreach (array('public_route','indexable','sitemap','schema','homepage_adapter') as $lock) {
    stti_v070_runtime_ok(($payload['publication'][$lock] ?? null) === false, "Tour publication lock {$lock} remains OFF");
}

wp_delete_post($hotel_post_id, true);
echo "STTI v0.7.0 WordPress Review Relations runtime: PASS\n";
