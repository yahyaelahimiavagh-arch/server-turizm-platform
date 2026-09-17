<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/bootstrap.php';

require_admin();
$userRepo = new UserRepository(db());

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
if (!$id) {
    http_response_code(404);
    exit('Çalışan bulunamadı.');
}

$employee = $userRepo->find((int) $id);
if (!$employee || $employee['role'] !== 'employee') {
    http_response_code(404);
    exit('Çalışan bulunamadı.');
}

$error = null;
$year = filter_input(INPUT_GET, 'year', FILTER_VALIDATE_INT) ?: (int) date('Y');
if ($year < 2000 || $year > 2100) {
    $year = (int) date('Y');
}

if (is_post()) {
    verify_csrf_or_fail();
    $action = (string) ($_POST['action'] ?? '');

    if ($action === 'profile') {
        $fullName = trim((string) ($_POST['full_name'] ?? ''));
        $email = mb_strtolower(trim((string) ($_POST['email'] ?? '')));
        $hireDate = trim((string) ($_POST['hire_date'] ?? '')) ?: null;
        $isActive = isset($_POST['is_active']);
        $newPassword = (string) ($_POST['new_password'] ?? '');

        if ($fullName === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Ad soyad ve geçerli e-posta zorunludur.';
        } elseif ($newPassword !== '' && mb_strlen($newPassword) < 10) {
            $error = 'Yeni şifre en az 10 karakter olmalıdır.';
        } elseif ($userRepo->emailExists($email, (int) $id)) {
            $error = 'Bu e-posta başka bir hesapta kullanılıyor.';
        } else {
            $userRepo->updateEmployee((int) $id, $fullName, $email, $hireDate, $isActive, $newPassword ?: null);
            flash('success', 'Çalışan bilgileri güncellendi.');
            redirect('admin/employee-edit.php?id=' . $id . '&year=' . $year);
        }
    } elseif ($action === 'allowance') {
        $allowanceYear = filter_var($_POST['allowance_year'] ?? null, FILTER_VALIDATE_INT);
        $days = filter_var($_POST['entitlement_days'] ?? null, FILTER_VALIDATE_FLOAT);

        if (!$allowanceYear || $allowanceYear < 2000 || $allowanceYear > 2100 || $days === false || $days < 0 || $days > 365) {
            $error = 'Geçerli yıl ve izin günü girin.';
        } else {
            $userRepo->setAllowance((int) $id, (int) $allowanceYear, (float) $days);
            flash('success', 'Yıllık izin hakkı güncellendi.');
            redirect('admin/employee-edit.php?id=' . $id . '&year=' . $allowanceYear);
        }
    }
}

$employee = $userRepo->find((int) $id);
$allowance = $userRepo->ensureAllowance((int) $id, $year);
$success = flash('success');
$pageTitle = 'Çalışan Düzenle';
require dirname(__DIR__) . '/templates/header.php';
?>
<div class="page-head">
    <div><h1><?= e($employee['full_name']) ?></h1><p>Çalışan bilgileri ve yıllık izin hakkı.</p></div>
    <a class="btn btn-light" href="<?= e(base_path('admin/employees.php')) ?>">Çalışanlara Dön</a>
</div>
<?php if ($success): ?><div class="alert alert-success"><?= e($success) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>

<div class="grid grid-2">
    <section class="card">
        <h2 class="section-title">Hesap Bilgileri</h2>
        <form method="post">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= e($employee['id']) ?>">
            <input type="hidden" name="action" value="profile">
            <div class="form-group"><label for="full_name">Ad Soyad</label><input id="full_name" name="full_name" value="<?= e($employee['full_name']) ?>" required></div>
            <div class="form-group"><label for="email">E-posta</label><input id="email" name="email" type="email" value="<?= e($employee['email']) ?>" required></div>
            <div class="form-group"><label for="hire_date">İşe Giriş Tarihi</label><input id="hire_date" name="hire_date" type="date" value="<?= e($employee['hire_date'] ?? '') ?>"></div>
            <div class="form-group"><label for="new_password">Yeni Şifre</label><input id="new_password" name="new_password" type="password" minlength="10"><div class="form-note">Değiştirmek istemiyorsanız boş bırakın.</div></div>
            <div class="form-group"><label><input style="width:auto" type="checkbox" name="is_active" value="1" <?= (int) $employee['is_active'] === 1 ? 'checked' : '' ?>> Hesap aktif</label></div>
            <button class="btn btn-primary" type="submit">Bilgileri Kaydet</button>
        </form>
    </section>

    <section class="card">
        <h2 class="section-title">Yıllık İzin Hakkı</h2>
        <form method="post">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= e($employee['id']) ?>">
            <input type="hidden" name="action" value="allowance">
            <div class="form-group"><label for="allowance_year">Yıl</label><input id="allowance_year" name="allowance_year" type="number" min="2000" max="2100" value="<?= e((string) $year) ?>" required></div>
            <div class="form-group"><label for="entitlement_days">Yıllık Hak (Gün)</label><input id="entitlement_days" name="entitlement_days" type="number" min="0" max="365" step="0.5" value="<?= e(format_days($allowance)) ?>" required></div>
            <button class="btn btn-primary" type="submit">İzin Hakkını Kaydet</button>
        </form>
    </section>
</div>
<?php require dirname(__DIR__) . '/templates/footer.php'; ?>
