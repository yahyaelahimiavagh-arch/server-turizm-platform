<?php

declare(strict_types=1);

function current_user(): ?array
{
    static $resolved = false;
    static $user = null;

    if ($resolved) {
        return $user;
    }

    $resolved = true;
    $userId = $_SESSION['user_id'] ?? null;

    if (!is_int($userId) && !ctype_digit((string) $userId)) {
        return null;
    }

    $stmt = db()->prepare(
        'SELECT id, full_name, email, role, is_active, hire_date
         FROM users
         WHERE id = :id
         LIMIT 1'
    );
    $stmt->execute(['id' => (int) $userId]);
    $row = $stmt->fetch();

    if (!$row || (int) $row['is_active'] !== 1) {
        unset($_SESSION['user_id']);
        return null;
    }

    $user = $row;
    return $user;
}

function attempt_login(string $email, string $password): bool
{
    $email = mb_strtolower(trim($email));

    $stmt = db()->prepare(
        'SELECT id, password_hash, is_active
         FROM users
         WHERE email = :email
         LIMIT 1'
    );
    $stmt->execute(['email' => $email]);
    $user = $stmt->fetch();

    if (!$user || (int) $user['is_active'] !== 1) {
        password_verify($password, '$2y$12$WlGgU1OsWguuFQOaKApUZ.nZ7WcZWaa3oujluryzIIQwu5VgQsIBS');
        return false;
    }

    if (!password_verify($password, (string) $user['password_hash'])) {
        return false;
    }

    session_regenerate_id(true);
    $_SESSION['user_id'] = (int) $user['id'];
    unset($_SESSION['_csrf_token']);

    return true;
}

function logout_user(): void
{
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', [
            'expires' => time() - 42000,
            'path' => $params['path'] ?: '/',
            'domain' => $params['domain'] ?? '',
            'secure' => (bool) ($params['secure'] ?? false),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }

    session_destroy();
}

function require_login(): array
{
    $user = current_user();
    if ($user === null) {
        redirect('login.php');
    }

    return $user;
}

function require_admin(): array
{
    $user = require_login();

    if (($user['role'] ?? '') !== 'admin') {
        http_response_code(403);
        exit('Bu sayfaya erişim yetkiniz yok.');
    }

    return $user;
}
