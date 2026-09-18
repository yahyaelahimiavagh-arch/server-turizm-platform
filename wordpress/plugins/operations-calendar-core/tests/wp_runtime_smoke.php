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

ops_assert(function_exists('elahi_ops_calendar_upsert_event'), 'calendar upsert API loaded');
ops_assert(function_exists('elahi_ops_calendar_events'), 'calendar query API loaded');

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

ops_assert(elahi_ops_calendar_remove_event($publicUid), 'public fixture removed');
ops_assert(elahi_ops_calendar_remove_event($internalUid), 'internal fixture removed');

echo "\nOperations Calendar Core runtime smoke: PASS\n";
