<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/bootstrap.php';
require_admin();

$pdo = db();
$error = null;

if (is_post()) {
    verify_csrf_or_fail();
    $defaultDays = filter_var($_POST['default_annual_allowance_days'] ?? null, FILTER_VALIDATE_FLOAT);

    if ($defaultDays === false || $defaultDays < 0 || $defaultDays > 365) {
        $error = 'Varsayılan izin hakkı 0 ile 365 gün arasında olmalıdır.';
    } else {
        $stmt = $pdo->prepare(
            "INSERT INTO app_settings (setting_key, setting_value)
             VALUES ('default_annual_allowance_days', :value)
             ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)"
        );
        $stmt->execute(['value' => number_format((float) $defaultDays, 2, '.', '')]);
        flash('success', 'Varsayılan yıllık izin hakkı güncellendi. Mevcut çalışan/yıl kayıtları değişmedi.');
        redirect('admin/settings.php');
    }
}

$stmt = $pdo->query("SELECT setting_value FROM app_settings WHERE setting_key = 'default_annual_allowance_days' LIMIT 1");
$defaultDays = (float) ($stmt->fetchColumn() ?: 20.0);
$success = flash('success');
$pageTitle = 'Ayarlar';
require dirname(__DIR__) . '/templates/header.php';
?>
<div class="page-head"><div><h1>Ayarlar</h1><p>Sistem varsayılanları ve yönetim bağlantıları.</p></div></div>
<?php if ($success): ?><div class="alert alert-success"><?= e($success) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>

<div class="grid grid-2">
    <section class="card">
        <h2 class="section-title">Varsayılan Yıllık İzin Hakkı</h2>
        <p class="form-note">Bu değer yalnızca yeni oluşturulan employee/year kayıtlarında kullanılır. Mevcut yıllık hakları geriye dönük değiştirmez.</p>
        <form method="post">
            <?= csrf_field() ?>
            <div class="form-group"><label for="default_annual_allowance_days">Gün</label><input id="default_annual_allowance_days" name="default_annual_allowance_days" type="number" min="0" max="365" step="0.5" value="<?= e(format_days($defaultDays)) ?>" required></div>
            <button class="btn btn-primary" type="submit">Kaydet</button>
        </form>
    </section>
    <section class="card">
        <h2 class="section-title">Yönetim</h2>
        <div class="actions">
            <a class="btn btn-light" href="<?= e(base_path('admin/leave-types.php')) ?>">İzin Türleri</a>
            <a class="btn btn-light" href="<?= e(base_path('admin/holidays.php')) ?>">Resmî Tatiller</a>
        </div>
    </section>
</div>
<?php require dirname(__DIR__) . '/templates/footer.php'; ?>
