<?php

declare(strict_types=1);

require_once __DIR__ . '/app/bootstrap.php';

$user = current_user();

if ($user === null) {
    redirect('login.php');
}

if (($user['role'] ?? '') === 'admin') {
    redirect('admin/dashboard.php');
}

redirect('dashboard.php');
