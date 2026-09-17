<?php

declare(strict_types=1);

$pageTitle = $pageTitle ?? 'Server Turizm İzin Yönetim Sistemi';
$user = current_user();
?>
<!doctype html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <title><?= e($pageTitle) ?></title>
    <link rel="stylesheet" href="<?= e(base_path('assets/css/app.css')) ?>">
</head>
<body>
<header class="site-header">
    <div class="container nav-wrap">
        <a class="brand" href="<?= e(base_path('')) ?>">Server Turizm <span>İzin</span></a>
        <?php if ($user): ?>
            <nav class="main-nav" aria-label="Ana menü">
                <?php if (($user['role'] ?? '') === 'admin'): ?>
                    <a href="<?= e(base_path('admin/dashboard.php')) ?>">Yönetim</a>
                    <a href="<?= e(base_path('admin/requests.php')) ?>">Talepler</a>
                    <a href="<?= e(base_path('admin/employees.php')) ?>">Çalışanlar</a>
                    <a href="<?= e(base_path('admin/reports.php')) ?>">Raporlar</a>
                <?php else: ?>
                    <a href="<?= e(base_path('dashboard.php')) ?>">Dashboard</a>
                    <a href="<?= e(base_path('leave-new.php')) ?>">Yeni Talep</a>
                    <a href="<?= e(base_path('my-leaves.php')) ?>">İzinlerim</a>
                    <a href="<?= e(base_path('calendar.php')) ?>">Takvim</a>
                <?php endif; ?>
                <form method="post" action="<?= e(base_path('logout.php')) ?>" class="logout-form">
                    <?= csrf_field() ?>
                    <button type="submit" class="link-button">Çıkış</button>
                </form>
            </nav>
        <?php endif; ?>
    </div>
</header>
<main class="container page-shell">
