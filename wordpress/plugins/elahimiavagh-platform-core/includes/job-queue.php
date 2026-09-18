<?php

if (!defined('ABSPATH')) {
    exit;
}

function elahi_platform_jobs_table(): string
{
    global $wpdb;
    return $wpdb->prefix . 'elahi_jobs';
}

function elahi_platform_install_job_queue(): void
{
    global $wpdb;

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';

    $table = elahi_platform_jobs_table();
    $charset = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE {$table} (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        job_type varchar(80) NOT NULL,
        payload_json longtext NOT NULL,
        status varchar(20) NOT NULL DEFAULT 'queued',
        attempts smallint(5) unsigned NOT NULL DEFAULT 0,
        max_attempts smallint(5) unsigned NOT NULL DEFAULT 3,
        available_at datetime NOT NULL,
        locked_at datetime NULL,
        locked_by varchar(64) NULL,
        last_error text NULL,
        created_at datetime NOT NULL,
        updated_at datetime NOT NULL,
        completed_at datetime NULL,
        PRIMARY KEY  (id),
        KEY status_available (status, available_at, id),
        KEY job_type_status (job_type, status, id)
    ) {$charset};";

    dbDelta($sql);
    update_option('elahi_platform_db_version', ELAHI_PLATFORM_CORE_VERSION, false);
}

function elahi_platform_enqueue_job(string $jobType, array $payload = [], array $options = [])
{
    global $wpdb;

    $jobType = sanitize_key($jobType);
    if ($jobType === '' || strlen($jobType) > 80) {
        return new WP_Error('invalid_job_type', 'A valid job type is required.');
    }

    try {
        $payloadJson = wp_json_encode(
            $payload,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR
        );
    } catch (Throwable $e) {
        return new WP_Error('invalid_job_payload', 'Job payload cannot be encoded.');
    }

    if (!is_string($payloadJson) || strlen($payloadJson) > 262144) {
        return new WP_Error('job_payload_too_large', 'Job payload exceeds 256 KB.');
    }

    $maxAttempts = max(1, min(10, (int) ($options['max_attempts'] ?? 3)));
    $delaySeconds = max(0, min(86400 * 30, (int) ($options['delay_seconds'] ?? 0)));
    $availableAt = gmdate('Y-m-d H:i:s', time() + $delaySeconds);
    $now = current_time('mysql', true);

    $inserted = $wpdb->insert(
        elahi_platform_jobs_table(),
        [
            'job_type' => $jobType,
            'payload_json' => $payloadJson,
            'status' => 'queued',
            'attempts' => 0,
            'max_attempts' => $maxAttempts,
            'available_at' => $availableAt,
            'created_at' => $now,
            'updated_at' => $now,
        ],
        ['%s', '%s', '%s', '%d', '%d', '%s', '%s', '%s']
    );

    if ($inserted === false) {
        return new WP_Error('job_insert_failed', 'Job could not be queued.');
    }

    return (int) $wpdb->insert_id;
}

function elahi_platform_enqueue_unique_job(
    string $jobType,
    array $payload = [],
    array $options = []
) {
    global $wpdb;

    $jobType = sanitize_key($jobType);
    if ($jobType === '' || strlen($jobType) > 80) {
        return new WP_Error('invalid_job_type', 'A valid job type is required.');
    }

    $table = elahi_platform_jobs_table();
    $existingId = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT id
             FROM {$table}
             WHERE job_type = %s
               AND status IN ('queued', 'running')
             ORDER BY id ASC
             LIMIT 1",
            $jobType
        )
    );

    if ($existingId) {
        return (int) $existingId;
    }

    return elahi_platform_enqueue_job($jobType, $payload, $options);
}

function elahi_platform_recover_stale_jobs(): int
{
    global $wpdb;

    $table = elahi_platform_jobs_table();
    $cutoff = gmdate('Y-m-d H:i:s', time() - 15 * MINUTE_IN_SECONDS);
    $now = current_time('mysql', true);

    $retryable = $wpdb->query(
        $wpdb->prepare(
            "UPDATE {$table}
             SET status = 'queued',
                 available_at = %s,
                 locked_at = NULL,
                 locked_by = NULL,
                 last_error = 'Recovered stale worker lock.',
                 updated_at = %s
             WHERE status = 'running'
               AND locked_at IS NOT NULL
               AND locked_at < %s
               AND attempts < max_attempts",
            $now,
            $now,
            $cutoff
        )
    );

    $failed = $wpdb->query(
        $wpdb->prepare(
            "UPDATE {$table}
             SET status = 'failed',
                 locked_at = NULL,
                 locked_by = NULL,
                 last_error = 'Job exceeded max attempts after stale worker recovery.',
                 updated_at = %s
             WHERE status = 'running'
               AND locked_at IS NOT NULL
               AND locked_at < %s
               AND attempts >= max_attempts",
            $now,
            $cutoff
        )
    );

    return max(0, (int) $retryable) + max(0, (int) $failed);
}

function elahi_platform_claim_job(string $workerId): ?array
{
    global $wpdb;

    $table = elahi_platform_jobs_table();
    $now = current_time('mysql', true);

    $wpdb->query('START TRANSACTION');

    try {
        $job = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT *
                 FROM {$table}
                 WHERE status = 'queued'
                   AND available_at <= %s
                 ORDER BY available_at ASC, id ASC
                 LIMIT 1
                 FOR UPDATE",
                $now
            ),
            ARRAY_A
        );

        if (!$job) {
            $wpdb->query('COMMIT');
            return null;
        }

        $updated = $wpdb->update(
            $table,
            [
                'status' => 'running',
                'attempts' => (int) $job['attempts'] + 1,
                'locked_at' => $now,
                'locked_by' => $workerId,
                'updated_at' => $now,
            ],
            [
                'id' => (int) $job['id'],
                'status' => 'queued',
            ],
            ['%s', '%d', '%s', '%s', '%s'],
            ['%d', '%s']
        );

        if ($updated !== 1) {
            $wpdb->query('ROLLBACK');
            return null;
        }

        $wpdb->query('COMMIT');

        $job['status'] = 'running';
        $job['attempts'] = (int) $job['attempts'] + 1;
        $job['locked_at'] = $now;
        $job['locked_by'] = $workerId;

        return $job;
    } catch (Throwable $e) {
        $wpdb->query('ROLLBACK');
        error_log('Elahimiavagh job claim failed: ' . $e->getMessage());
        return null;
    }
}

function elahi_platform_job_backoff_seconds(int $attempt): int
{
    return match (true) {
        $attempt <= 1 => 60,
        $attempt === 2 => 300,
        $attempt === 3 => 900,
        default => min(21600, 900 * (2 ** min(4, $attempt - 3))),
    };
}

function elahi_platform_complete_job(int $jobId): bool
{
    global $wpdb;

    $now = current_time('mysql', true);
    return $wpdb->update(
        elahi_platform_jobs_table(),
        [
            'status' => 'completed',
            'locked_at' => null,
            'locked_by' => null,
            'last_error' => null,
            'updated_at' => $now,
            'completed_at' => $now,
        ],
        ['id' => $jobId, 'status' => 'running'],
        ['%s', '%s', '%s', '%s', '%s', '%s'],
        ['%d', '%s']
    ) === 1;
}

function elahi_platform_fail_or_retry_job(array $job, string $error): void
{
    global $wpdb;

    $attempts = (int) ($job['attempts'] ?? 1);
    $maxAttempts = (int) ($job['max_attempts'] ?? 3);
    $error = trim(wp_strip_all_tags($error));
    if ($error === '') {
        $error = 'Job handler returned an error.';
    }
    $error = mb_substr($error, 0, 2000);
    $now = current_time('mysql', true);

    if ($attempts < $maxAttempts) {
        $availableAt = gmdate('Y-m-d H:i:s', time() + elahi_platform_job_backoff_seconds($attempts));
        $wpdb->update(
            elahi_platform_jobs_table(),
            [
                'status' => 'queued',
                'available_at' => $availableAt,
                'locked_at' => null,
                'locked_by' => null,
                'last_error' => $error,
                'updated_at' => $now,
            ],
            ['id' => (int) $job['id']],
            ['%s', '%s', '%s', '%s', '%s', '%s'],
            ['%d']
        );
        return;
    }

    $wpdb->update(
        elahi_platform_jobs_table(),
        [
            'status' => 'failed',
            'locked_at' => null,
            'locked_by' => null,
            'last_error' => $error,
            'updated_at' => $now,
        ],
        ['id' => (int) $job['id']],
        ['%s', '%s', '%s', '%s', '%s'],
        ['%d']
    );
}

function elahi_platform_run_claimed_job(array $job): void
{
    $payload = json_decode((string) ($job['payload_json'] ?? ''), true);
    if (!is_array($payload)) {
        elahi_platform_fail_or_retry_job($job, 'Stored job payload is invalid.');
        return;
    }

    $jobType = (string) ($job['job_type'] ?? '');
    $hook = 'elahi_platform_job_' . sanitize_key($jobType);

    try {
        $result = apply_filters($hook, null, $payload, (int) $job['id']);

        if ($result === true) {
            elahi_platform_complete_job((int) $job['id']);
            return;
        }

        if (is_wp_error($result)) {
            elahi_platform_fail_or_retry_job($job, $result->get_error_message());
            return;
        }

        if ($result === null) {
            elahi_platform_fail_or_retry_job($job, 'No handler registered for this job type.');
            return;
        }

        elahi_platform_fail_or_retry_job($job, 'Job handler did not return success.');
    } catch (Throwable $e) {
        error_log('Elahimiavagh job failed [' . $jobType . ']: ' . $e->getMessage());
        elahi_platform_fail_or_retry_job($job, 'Job handler threw an exception.');
    }
}

function elahi_platform_process_jobs(int $limit = 20): int
{
    $limit = max(1, min(100, $limit));
    $workerId = substr(hash('sha256', wp_generate_uuid4() . '|' . microtime(true)), 0, 32);

    elahi_platform_recover_stale_jobs();

    $processed = 0;
    while ($processed < $limit) {
        $job = elahi_platform_claim_job($workerId);
        if ($job === null) {
            break;
        }

        elahi_platform_run_claimed_job($job);
        $processed++;
    }

    return $processed;
}

function elahi_platform_job_stats(): array
{
    global $wpdb;

    $table = elahi_platform_jobs_table();
    $rows = $wpdb->get_results(
        "SELECT status, COUNT(*) AS total
         FROM {$table}
         GROUP BY status",
        ARRAY_A
    ) ?: [];

    $stats = [
        'queued' => 0,
        'running' => 0,
        'completed' => 0,
        'failed' => 0,
    ];

    foreach ($rows as $row) {
        $status = (string) ($row['status'] ?? '');
        if (array_key_exists($status, $stats)) {
            $stats[$status] = (int) $row['total'];
        }
    }

    return $stats;
}

function elahi_platform_recent_jobs(int $limit = 20): array
{
    global $wpdb;

    $limit = max(1, min(100, $limit));
    $table = elahi_platform_jobs_table();

    return $wpdb->get_results(
        "SELECT id, job_type, status, attempts, max_attempts, available_at,
                locked_at, last_error, created_at, updated_at, completed_at
         FROM {$table}
         ORDER BY id DESC
         LIMIT {$limit}",
        ARRAY_A
    ) ?: [];
}

function elahi_platform_cron_schedules(array $schedules): array
{
    if (!isset($schedules['elahi_every_minute'])) {
        $schedules['elahi_every_minute'] = [
            'interval' => MINUTE_IN_SECONDS,
            'display' => 'Every Minute — Elahimiavagh Platform',
        ];
    }

    return $schedules;
}
add_filter('cron_schedules', 'elahi_platform_cron_schedules');

function elahi_platform_ensure_job_schedule(): void
{
    if (!wp_next_scheduled('elahi_platform_process_jobs')) {
        wp_schedule_event(time() + MINUTE_IN_SECONDS, 'elahi_every_minute', 'elahi_platform_process_jobs');
    }
}
add_action('init', 'elahi_platform_ensure_job_schedule');

function elahi_platform_run_scheduled_jobs(): void
{
    elahi_platform_process_jobs(20);
}
add_action('elahi_platform_process_jobs', 'elahi_platform_run_scheduled_jobs');

function elahi_platform_clear_job_schedule(): void
{
    wp_clear_scheduled_hook('elahi_platform_process_jobs');
}
