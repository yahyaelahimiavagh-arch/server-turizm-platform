<?php

declare(strict_types=1);

const LOGIN_FAILURE_LIMIT = 8;
const LOGIN_FAILURE_WINDOW_MINUTES = 15;

function login_rate_limit_is_blocked(string $email): bool
{
    try {
        $stmt = db()->prepare(
            'SELECT COUNT(*)
             FROM login_failures
             WHERE email_hash = :email_hash
               AND ip_hash = :ip_hash
               AND attempted_at >= (NOW() - INTERVAL 15 MINUTE)'
        );
        $stmt->execute(login_failure_identity($email));

        return (int) $stmt->fetchColumn() >= LOGIN_FAILURE_LIMIT;
    } catch (Throwable $e) {
        error_log('Login rate-limit check failed: ' . $e->getMessage());
        return false;
    }
}

function record_login_failure(string $email): void
{
    try {
        $identity = login_failure_identity($email);
        $stmt = db()->prepare(
            'INSERT INTO login_failures (email_hash, ip_hash)
             VALUES (:email_hash, :ip_hash)'
        );
        $stmt->execute($identity);

        if (random_int(1, 20) === 1) {
            db()->exec('DELETE FROM login_failures WHERE attempted_at < (NOW() - INTERVAL 2 DAY)');
        }
    } catch (Throwable $e) {
        error_log('Login rate-limit record failed: ' . $e->getMessage());
    }
}

function clear_login_failures(string $email): void
{
    try {
        $stmt = db()->prepare(
            'DELETE FROM login_failures
             WHERE email_hash = :email_hash
               AND ip_hash = :ip_hash'
        );
        $stmt->execute(login_failure_identity($email));
    } catch (Throwable $e) {
        error_log('Login rate-limit clear failed: ' . $e->getMessage());
    }
}

function login_failure_identity(string $email): array
{
    $normalizedEmail = mb_strtolower(trim($email));
    $remoteAddress = (string) ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
    $app = app_config('app');
    $secret = (string) ($app['setup_key'] ?? '');

    if ($secret === '' || str_starts_with($secret, 'CHANGE_')) {
        $secret = (string) ($app['session_name'] ?? 'server_turizm_izin');
    }

    return [
        'email_hash' => hash_hmac('sha256', $normalizedEmail, $secret),
        'ip_hash' => hash_hmac('sha256', $remoteAddress, $secret),
    ];
}
