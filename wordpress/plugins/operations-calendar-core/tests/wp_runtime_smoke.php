<?php


if (!defined('ABSPATH')) {
    fwrite(STDERR, "WordPress runtime required.\n");
    exit(1);
}

function ops_assert(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }

    echo "[PASS] {$message}\n";
}

global $wpdb;

ops_assert(function_exists('elahi_platform_modules'), 'platform registry API loaded');
ops_assert(function_exists('elahi_ops_calendar_upsert_event'), 'calendar upsert API loaded');
ops_assert(function_exists('elahi_ops_calendar_events'), 'calendar query API loaded');

$platformModules = elahi_platform_modules();
ops_assert(isset($platformModules['platform_core']), 'platform core registered');
ops_assert(isset($platformModules['operations_calendar']), 'operations calendar registered in platform registry');
ops_assert(isset($platformModules['operations_calendar_adapters']), 'calendar adapters registered in platform registry');
ops_assert(($platformModules['operations_calendar']['health'] ?? '') === 'healthy', 'operations calendar registry health is healthy');

$runtimeJobHandler = static function ($result, array $payload, int $jobId) {
    return (($payload['probe'] ?? '') === 'ok' && $jobId > 0)
        ? true
        : new WP_Error('runtime_probe_failed', 'Probe payload mismatch.');
};
add_filter('elahi_platform_job_runtime_probe', $runtimeJobHandler, 10, 3);

$runtimeJobId = elahi_platform_enqueue_unique_job('runtime_probe', ['probe' => 'ok'], ['max_attempts' => 1]);
ops_assert(is_int($runtimeJobId) && $runtimeJobId > 0, 'background job queued');

$runtimeJobDuplicateId = elahi_platform_enqueue_unique_job('runtime_probe', ['probe' => 'ok'], ['max_attempts' => 1]);
ops_assert($runtimeJobDuplicateId === $runtimeJobId, 'duplicate-safe enqueue returns existing queued job');
ops_assert(elahi_platform_process_jobs(5) >= 1, 'background worker processed queued job');

$jobTable = elahi_platform_jobs_table();
$runtimeJobStatus = $wpdb->get_var($wpdb->prepare("SELECT status FROM {$jobTable} WHERE id = %d", $runtimeJobId));
ops_assert($runtimeJobStatus === 'completed', 'registered job handler completes job');

$unhandledJobId = elahi_platform_enqueue_job('runtime_unhandled', ['probe' => 'fail'], ['max_attempts' => 1]);
ops_assert(is_int($unhandledJobId) && $unhandledJobId > 0, 'unhandled background job queued');
ops_assert(elahi_platform_process_jobs(5) >= 1, 'background worker processed unhandled job');

$unhandledStatus = $wpdb->get_var($wpdb->prepare("SELECT status FROM {$jobTable} WHERE id = %d", $unhandledJobId));
ops_assert($unhandledStatus === 'failed', 'unhandled job fails closed after max attempts');
remove_filter('elahi_platform_job_runtime_probe', $runtimeJobHandler, 10);

$table = elahi_ops_calendar_table();
$tableExists = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table));
ops_assert($tableExists === $table, 'calendar projection table exists');

$reconcileSource = 'runtime_reconcile';
$reconcileBase = [
    [
        'event_uid' => 'runtime:one',
        'source_module' => $reconcileSource,
        'source_entity_id' => 'one',
        'event_type' => 'runtime',
        'title' => 'Runtime One',
        'start_at' => '2027-02-01T00:00:00+03:00',
        'end_at' => '2027-02-01T23:59:59+03:00',
        'all_day' => true,
        'status' => 'published',
        'visibility' => 'internal',
        'priority' => 10,
        'metadata' => [],
    ],
    [
        'event_uid' => 'runtime:two',
        'source_module' => $reconcileSource,
        'source_entity_id' => 'two',
        'event_type' => 'runtime',
        'title' => 'Runtime Two',
        'start_at' => '2027-02-02T00:00:00+03:00',
        'end_at' => '2027-02-02T23:59:59+03:00',
        'all_day' => true,
        'status' => 'published',
        'visibility' => 'internal',
        'priority' => 10,
        'metadata' => [],
    ],
];

$reconcileFirst = elahi_ops_calendar_reconcile_source_events($reconcileSource, $reconcileBase);
ops_assert(is_array($reconcileFirst) && (int) $reconcileFirst['projected'] === 2, 'source reconciliation projects complete event set');

$reconcileSecond = elahi_ops_calendar_reconcile_source_events($reconcileSource, [$reconcileBase[0]]);
ops_assert(is_array($reconcileSecond) && (int) $reconcileSecond['removed'] === 1, 'source reconciliation removes stale event');

$blockedEmpty = elahi_ops_calendar_reconcile_source_events($reconcileSource, []);
ops_assert(is_wp_error($blockedEmpty), 'empty source reconciliation fails closed by default');

$explicitEmpty = elahi_ops_calendar_reconcile_source_events($reconcileSource, [], true);
ops_assert(is_array($explicitEmpty), 'explicit empty source reconciliation is allowed');

$reconcileCount = (int) $wpdb->get_var(
    $wpdb->prepare("SELECT COUNT(*) FROM {$table} WHERE source_module = %s", $reconcileSource)
);
ops_assert($reconcileCount === 0, 'explicit empty reconciliation clears source projection');

$publicUid = 'test:tour:STT-999991';
$internalUid = 'test:leave:999991';
$internalOperationUid = 'test:umrah:STP-999991';

$publicId = elahi_ops_calendar_upsert_event([
    'event_uid' => $publicUid,
    'source_module' => 'tour',
    'source_entity_id' => 'STT-999991',
    'event_type' => 'tour',
    'title' => 'Runtime Public Tour',
    'start_at' => '2027-01-10T00:00:00+03:00',
    'end_at' => '2027-01-12T23:59:59+03:00',
    'all_day' => true,
    'status' => 'published',
    'visibility' => 'public',
    'priority' => 60,
    'location' => 'Istanbul',
    'public_url' => 'https://example.invalid/tour',
    'source_version' => 'runtime-test',
    'metadata' => ['fixture' => true],
]);

ops_assert(is_int($publicId) && $publicId > 0, 'public calendar event inserted');

$internalId = elahi_ops_calendar_upsert_event([
    'event_uid' => $internalUid,
    'source_module' => 'leave',
    'source_entity_id' => '999991',
    'event_type' => 'leave',
    'title' => 'Private Staff Leave',
    'start_at' => '2027-01-11T00:00:00+03:00',
    'end_at' => '2027-01-11T23:59:59+03:00',
    'all_day' => true,
    'status' => 'published',
    'visibility' => 'internal',
    'priority' => 50,
    'metadata' => ['fixture' => true],
]);

ops_assert(is_int($internalId) && $internalId > 0, 'internal calendar event inserted');

$internalOperationId = elahi_ops_calendar_upsert_event([
    'event_uid' => $internalOperationUid,
    'source_module' => 'umrah',
    'source_entity_id' => 'STP-999991',
    'event_type' => 'umrah_program',
    'title' => 'Runtime Internal Umrah',
    'start_at' => '2027-01-13T00:00:00+03:00',
    'end_at' => '2027-01-15T23:59:59+03:00',
    'all_day' => true,
    'status' => 'published',
    'visibility' => 'internal',
    'priority' => 80,
    'location' => 'Mekke · Medine',
    'metadata' => ['fixture' => true],
]);
ops_assert(is_int($internalOperationId) && $internalOperationId > 0, 'internal Umrah operation inserted');

$updatedId = elahi_ops_calendar_upsert_event([
    'event_uid' => $publicUid,
    'source_module' => 'tour',
    'source_entity_id' => 'STT-999991',
    'event_type' => 'tour',
    'title' => 'Runtime Public Tour Updated',
    'start_at' => '2027-01-10T00:00:00+03:00',
    'end_at' => '2027-01-12T23:59:59+03:00',
    'all_day' => true,
    'status' => 'published',
    'visibility' => 'public',
    'priority' => 70,
    'metadata' => ['fixture' => true, 'updated' => true],
]);

ops_assert($updatedId === $publicId, 'stable event_uid updates same projection row');

$count = (int) $wpdb->get_var(
    $wpdb->prepare("SELECT COUNT(*) FROM {$table} WHERE event_uid = %s", $publicUid)
);
ops_assert($count === 1, 'upsert does not duplicate stable event');

$publicEvents = elahi_ops_calendar_events([
    'from' => '2027-01-01T00:00:00Z',
    'to' => '2027-01-31T23:59:59Z',
    'visibility' => 'public',
    'status' => 'published',
    'limit' => 50,
]);

$publicUids = array_column($publicEvents, 'event_uid');
ops_assert(in_array($publicUid, $publicUids, true), 'public query includes public tour');
ops_assert(!in_array($internalUid, $publicUids, true), 'public query excludes internal leave');

$badMachineRequest = new WP_REST_Request('GET', '/elahimiavagh/v1/calendar/operations');
$badMachineRequest->set_header('x-elahi-calendar-token', 'wrong-token');
ops_assert(is_wp_error(elahi_ops_calendar_machine_permission($badMachineRequest)), 'internal operations API rejects invalid token');

$machineRequest = new WP_REST_Request('GET', '/elahimiavagh/v1/calendar/operations');
$machineRequest->set_header('x-elahi-calendar-token', 'test-calendar-token-not-a-secret-1234567890');
$machineRequest->set_param('from', '2027-01-01T00:00:00Z');
$machineRequest->set_param('to', '2027-01-31T23:59:59Z');
ops_assert(elahi_ops_calendar_machine_permission($machineRequest) === true, 'internal operations API accepts configured token');

$machineResponse = elahi_ops_calendar_machine_operations($machineRequest);
ops_assert($machineResponse instanceof WP_REST_Response, 'internal operations API returns REST response');
$machineData = $machineResponse->get_data();
$machineUids = array_column((array) ($machineData['events'] ?? []), 'event_uid');
ops_assert(in_array($publicUid, $machineUids, true), 'internal operations API includes public tour');
ops_assert(in_array($internalOperationUid, $machineUids, true), 'internal operations API includes internal Umrah');
ops_assert(!in_array($internalUid, $machineUids, true), 'internal operations API excludes leave source');

$shortcode = do_shortcode('[elahi_operations_calendar months="60" limit="20"]');
ops_assert(str_contains($shortcode, 'Runtime Public Tour Updated'), 'public shortcode renders projected event');
ops_assert(str_contains($shortcode, 'elahimiavagh.com'), 'public shortcode includes developer attribution');
ops_assert(!str_contains($shortcode, 'Private Staff Leave'), 'public shortcode never leaks internal leave');

$monthShortcode = do_shortcode('[elahi_operations_calendar_month month="2027-01" limit_per_day="4"]');
ops_assert(str_contains($monthShortcode, 'Runtime Public Tour Updated'), 'public month calendar renders projected event');
ops_assert(str_contains($monthShortcode, 'Powered by elahimiavagh.com'), 'public month calendar includes developer attribution');
ops_assert(!str_contains($monthShortcode, 'Private Staff Leave'), 'public month calendar never leaks internal leave');

$ics = elahi_ops_calendar_build_public_ics();
ops_assert(str_contains($ics, 'BEGIN:VCALENDAR'), 'public ICS feed renders calendar envelope');
ops_assert(str_contains($ics, 'Runtime Public Tour Updated'), 'public ICS feed includes public tour');
ops_assert(!str_contains($ics, 'Runtime Internal Umrah'), 'public ICS feed excludes internal Umrah');
ops_assert(!str_contains($ics, 'Private Staff Leave'), 'public ICS feed excludes internal leave');
ops_assert(str_contains($ics, 'PRODID:-//elahimiavagh.com//Operations Calendar//TR'), 'public ICS feed includes product identity');

ops_assert(elahi_ops_calendar_remove_event($publicUid), 'public fixture removed');
ops_assert(elahi_ops_calendar_remove_event($internalUid), 'internal fixture removed');
ops_assert(elahi_ops_calendar_remove_event($internalOperationUid), 'internal operation fixture removed');

$wpdb->query(
    $wpdb->prepare(
        "DELETE FROM {$jobTable} WHERE id IN (%d, %d)",
        $runtimeJobId,
        $unhandledJobId
    )
);
echo "[PASS] background job fixtures removed\n";

echo "\nOperations Calendar + Platform Core runtime smoke: PASS\n";
