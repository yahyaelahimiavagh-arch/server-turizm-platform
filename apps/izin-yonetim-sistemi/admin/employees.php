<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/bootstrap.php';

require_admin();
$userRepo = new UserRepository(db());
$error = null;

if (is_post()) {
    verify_csrf_or_fail();

    $fullName = trim((string) ($_POST['full_name'] ?? ''));
    $email = mb_strtolower(trim((string) ($_POST['email'] ?? '')));
    $password = (string) ($_POST['password'] ?? '');
    $hireDate = trim((string) ($_POST['hire_date'] ?? '')) ?: null;

    if ($fullName === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Ad soyad ve geçerli e-posta zorunludur.';
    } elseif (mb_strlen($password) < 10) {
        $error = 'İlk şifre en az 10 karakter olmalıdır.';
    } elseif ($userRepo->emailExists($email)) {
        $error = 'Bu e-posta adresi zaten kullanılıyor.';
    } else {
        try {
            $userRepo->createEmployee($fullName, $email, $password, $hireDate, (int) date('Y'));
            flash('success', 'Çalışan oluşturuldu ve bu yıl için varsayılan izin hakkı tanımlandı.');
            redirect('admin/employees.php');
        } catch (Throwable $e) {
            error_log($e->getMessage());
            $error = 'Çalışan oluşturulurken bir hata oluştu.';
        }
    }
}

$employees = $userRepo->listEmployees();
$success = flash('success');
$pageTitle = 'Çalışanlar';
require dirname(__DIR__) . '/templates/header.php';
?>
<div class="page-head"><div><h1>Çalışanlar</h1><p>Çalışan hesapları ve erişim durumları.</p></div></div>
<?php if ($success): ?><div class="alert alert-success"><?= e($success) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>

<div class="grid grid-2">
    <section class="card">
        <h2 class="section-title">Yeni Çalışan</h2>
        <form method="post">
            <?= csrf_field() ?>
            <div class="form-group"><label for="full_name">Ad Soyad</label><input id="full_name" name="full_name" value="<?= old('full_name') ?>" required></div>
            <div class="form-group"><label for="email">E-posta</label><input id="email" name="email" type="email" value="<?= old('email') ?>" required></div>
            <div class="form-group"><label for="password">İlk Şifre</label><input id="password" name="password" type="password" minlength="10" required><div class="form-note">Şifre en az 10 karakter olmalıdır.</div></div>
            <div class="form-group"><label for="hire_date">İşe Giriş Tarihi</label><input id="hire_date" name="hire_date" type="date" value="<?= old('hire_date') ?>"></div>
            <button class="btn btn-primary" type="submit">Çalışan Ekle</button>
        </form>
    </section>

    <section class="card">
        <h2 class="section-title">Çalışan Listesi</h2>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Ad Soyad</th><th>E-posta</th><th>Durum</th><th></th></tr></thead>
                <tbody>
                <?php if (!$employees): ?><tr><td colspan="4">Henüz çalışan yok.</td></tr><?php endif; ?>
                <?php foreach ($employees as $employee): ?>
                    <tr>
                        <td><?= e($employee['full_name']) ?></td>
                        <td><?= e($employee['email']) ?></td>
                        <td><?= (int) $employee['is_active'] === 1 ? 'Aktif' : 'Pasif' ?></td>
                        <td><a class="btn btn-light" href="<?= e(base_path('admin/employee-edit.php?id=' . $employee['id'])) ?>">Düzenle</a></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
</div>
<?php require dirname(__DIR__) . '/templates/footer.php'; ?>
