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
ops_assert(($platformModules['operations_calendar']['health'] ?? '') === 'healthy', 'operations calendar registry health is healthy');

$runtimeJobHandler = static function ($result, array $payload, int $jobId) {
    return (($payload['probe'] ?? '') === 'ok' && $jobId > 0)
        ? true
        : new WP_Error('runtime_probe_failed', 'Probe payload mismatch.');
};
add_filter('elahi_platform_job_runtime_probe', $runtimeJobHandler, 10, 3);

$runtimeJobId = elahi_platform_enqueue_job('runtime_probe', ['probe' => 'ok'], ['max_attempts' => 1]);
ops_assert(is_int($runtimeJobId) && $runtimeJobId > 0, 'background job queued');
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

$publicUid = 'test:tour:STT-999991';
$internalUid = 'test:leave:999991';

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

$shortcode = do_shortcode('[elahi_operations_calendar months="60" limit="20"]');
ops_assert(str_contains($shortcode, 'Runtime Public Tour Updated'), 'public shortcode renders projected event');
ops_assert(str_contains($shortcode, 'elahimiavagh.com'), 'public shortcode includes developer attribution');
ops_assert(!str_contains($shortcode, 'Private Staff Leave'), 'public shortcode never leaks internal leave');

$monthShortcode = do_shortcode('[elahi_operations_calendar_month month="2027-01" limit_per_day="4"]');
ops_assert(str_contains($monthShortcode, 'Runtime Public Tour Updated'), 'public month calendar renders projected event');
ops_assert(str_contains($monthShortcode, 'Powered by elahimiavagh.com'), 'public month calendar includes developer attribution');
ops_assert(!str_contains($monthShortcode, 'Private Staff Leave'), 'public month calendar never leaks internal leave');

ops_assert(elahi_ops_calendar_remove_event($publicUid), 'public fixture removed');
ops_assert(elahi_ops_calendar_remove_event($internalUid), 'internal fixture removed');

$wpdb->query(
    $wpdb->prepare(
        "DELETE FROM {$jobTable} WHERE id IN (%d, %d)",
        $runtimeJobId,
        $unhandledJobId
    )
);
echo "[PASS] background job fixtures removed\n";

echo "\nOperations Calendar + Platform Core runtime smoke: PASS\n";
