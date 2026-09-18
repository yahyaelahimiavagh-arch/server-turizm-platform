<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/bootstrap.php';

function calendar_export_assert(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }

    echo "[PASS] {$message}\n";
}

calendar_export_assert(leave_calendar_export_enabled(), 'approved-leave calendar export is enabled in runtime config');

$events = leave_calendar_export_events('2027-01-01', '2027-01-31');
calendar_export_assert($events !== [], 'approved leave produces calendar export events');

$leaveEvents = array_values(array_filter(
    $events,
    static fn (array $event): bool => ($event['source_module'] ?? '') === 'leave'
));
calendar_export_assert($leaveEvents !== [], 'calendar export uses leave source module');

$approvedFixture = null;
foreach ($leaveEvents as $event) {
    if (($event['title'] ?? '') === 'Runtime B — İzinli') {
        $approvedFixture = $event;
        break;
    }
}

calendar_export_assert(is_array($approvedFixture), 'approved employee fixture is exported');
calendar_export_assert(($approvedFixture['visibility'] ?? '') === 'internal', 'leave projection is internal only');
calendar_export_assert(($approvedFixture['status'] ?? '') === 'published', 'approved leave projection has published projection status');
calendar_export_assert(($approvedFixture['event_type'] ?? '') === 'employee_leave', 'full-day approved leave uses expected event type');
calendar_export_assert(($approvedFixture['public_url'] ?? 'not-null') === null, 'leave projection never exposes public URL');

foreach ([
    'email',
    'employee_comment',
    'admin_note',
    'attachment',
    'attachment_name',
    'mime_type',
    'sha256',
    'medical',
    'leave_type_name',
] as $forbiddenKey) {
    calendar_export_assert(
        !array_key_exists($forbiddenKey, $approvedFixture),
        'leave projection omits private field: ' . $forbiddenKey
    );
}

$metadata = is_array($approvedFixture['metadata'] ?? null) ? $approvedFixture['metadata'] : [];
calendar_export_assert(
    array_keys($metadata) === ['day_value', 'half_day_period'],
    'leave projection metadata is minimal and allowlisted'
);

calendar_export_assert(
    !str_contains(json_encode($approvedFixture, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), '@example.invalid'),
    'leave projection never exposes employee email'
);

echo "\nApproved leave calendar export DB test: PASS\n";
