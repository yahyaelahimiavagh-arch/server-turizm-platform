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
        $hireDate = trim((string) ($_POST['hire_date'] ?? ''));
        $birthDate = trim((string) ($_POST['birth_date'] ?? ''));
        $isActive = isset($_POST['is_active']);
        $newPassword = (string) ($_POST['new_password'] ?? '');

        if ($fullName === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Ad soyad ve geçerli e-posta zorunludur.';
        } elseif (parse_leave_date($hireDate) === null || parse_leave_date($birthDate) === null) {
            $error = 'İşe giriş tarihi ve doğum tarihi zorunludur.';
        } elseif ($birthDate >= $hireDate) {
            $error = 'Doğum tarihi işe giriş tarihinden önce olmalıdır.';
        } elseif (
            $userRepo->hasServiceYearEntitlements((int) $id)
            && (string) ($employee['hire_date'] ?? '') !== $hireDate
        ) {
            $error = 'Hak edilmiş yıllık izin kayıtları bulunan çalışanlarda işe giriş tarihi doğrudan değiştirilemez.';
        } elseif ($newPassword !== '' && mb_strlen($newPassword) < 10) {
            $error = 'Yeni şifre en az 10 karakter olmalıdır.';
        } elseif ($userRepo->emailExists($email, (int) $id)) {
            $error = 'Bu e-posta başka bir hesapta kullanılıyor.';
        } else {
            $userRepo->updateEmployee((int) $id, $fullName, $email, $hireDate, $birthDate, $isActive, $newPassword ?: null);
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
$serviceYearBalance = annual_leave_balance(db(), (int) $id);
$nextEntitlement = annual_leave_next_entitlement(db(), (int) $id);
$entitlements = annual_leave_entitlements(db(), (int) $id);
$allowance = $userRepo->getAllowance((int) $id, $year) ?? 0.0;
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
            <div class="form-group"><label for="hire_date">İşe Giriş Tarihi</label><input id="hire_date" name="hire_date" type="date" value="<?= e($employee['hire_date'] ?? '') ?>" required><div class="form-note">Hak edilmiş hizmet yılı kayıtları oluştuktan sonra doğrudan değiştirilemez.</div></div>
            <div class="form-group"><label for="birth_date">Doğum Tarihi</label><input id="birth_date" name="birth_date" type="date" value="<?= e($employee['birth_date'] ?? '') ?>" required></div>
            <div class="form-group"><label for="new_password">Yeni Şifre</label><input id="new_password" name="new_password" type="password" minlength="10"><div class="form-note">Değiştirmek istemiyorsanız boş bırakın.</div></div>
            <div class="form-group"><label><input style="width:auto" type="checkbox" name="is_active" value="1" <?= (int) $employee['is_active'] === 1 ? 'checked' : '' ?>> Hesap aktif</label></div>
            <button class="btn btn-primary" type="submit">Bilgileri Kaydet</button>
        </form>
    </section>

    <section class="card">
        <h2 class="section-title">Yıllık İzin — Hizmet Yılı</h2>
        <div class="grid grid-2">
            <div><strong>Hak Edilmiş Toplam</strong><br><?= e(format_days((float) $serviceYearBalance['entitlement'])) ?> gün</div>
            <div><strong>Kullanılabilir</strong><br><?= e(format_days((float) $serviceYearBalance['available_after_pending'])) ?> gün</div>
        </div>
        <p class="form-note" style="margin-top:12px">
            Kullanılmayan haklar iş ilişkisi devam ettiği sürece devreder; aktif çalışan için yıllık izin hakkı nakde çevrilmez.
        </p>
        <?php if ($nextEntitlement): ?>
            <div class="alert alert-info">
                Sonraki hak ediş: <strong><?= e((string) $nextEntitlement['earned_on']) ?></strong>
                · <?= e(format_days((float) $nextEntitlement['days'])) ?> gün
            </div>
        <?php endif; ?>

        <div class="table-wrap">
            <table>
                <thead><tr><th>Hizmet Yılı</th><th>Hak Edilen Tarih</th><th>Şirket</th><th>Yasal Taban</th><th>Efektif Hak</th></tr></thead>
                <tbody>
                <?php if (!$entitlements): ?><tr><td colspan="5">Henüz yıllık izin hakkı kazanılmadı.</td></tr><?php endif; ?>
                <?php foreach ($entitlements as $row): ?>
                    <tr>
                        <td><?= e((string) $row['service_year_number']) ?></td>
                        <td><?= e((string) $row['earned_on']) ?></td>
                        <td><?= e(format_days((float) $row['company_policy_days'])) ?></td>
                        <td><?= e(format_days(max((float) $row['legal_minimum_days'], (float) $row['age_minimum_days']))) ?></td>
                        <td><strong><?= e(format_days((float) $row['entitlement_days'])) ?></strong></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <details style="margin-top:14px">
            <summary>Eski takvim-yılı manuel hakkı</summary>
            <form method="post" style="margin-top:12px">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= e($employee['id']) ?>">
                <input type="hidden" name="action" value="allowance">
                <div class="form-group"><label for="allowance_year">Yıl</label><input id="allowance_year" name="allowance_year" type="number" min="2000" max="2100" value="<?= e((string) $year) ?>" required></div>
                <div class="form-group"><label for="entitlement_days">Legacy Hak (Gün)</label><input id="entitlement_days" name="entitlement_days" type="number" min="0" max="365" step="0.5" value="<?= e(format_days($allowance)) ?>" required></div>
                <div class="form-note">Sadece geçmiş V1 kayıtlarıyla uyumluluk içindir. Yeni hak ediş motoru hizmet yılı kayıtlarını kullanır.</div>
                <button class="btn btn-light" type="submit">Legacy Kaydı Güncelle</button>
            </form>
        </details>
    </section>
</div>
<?php require dirname(__DIR__) . '/templates/footer.php'; ?>
