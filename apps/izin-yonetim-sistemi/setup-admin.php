<?php

declare(strict_types=1);

require_once __DIR__ . '/app/bootstrap.php';

$adminCount = (int) db()->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn();
if ($adminCount > 0) {
    http_response_code(403);
    exit('İlk yönetici kurulumu daha önce tamamlanmış.');
}

$error = null;
$success = null;

if (is_post()) {
    verify_csrf_or_fail();

    $fullName = trim((string) ($_POST['full_name'] ?? ''));
    $email = mb_strtolower(trim((string) ($_POST['email'] ?? '')));
    $password = (string) ($_POST['password'] ?? '');
    $setupKey = (string) ($_POST['setup_key'] ?? '');
    $expectedKey = (string) ((app_config('app')['setup_key'] ?? ''));

    if ($expectedKey === '' || str_starts_with($expectedKey, 'CHANGE_')) {
        $error = 'Önce private config dosyasında güçlü bir setup_key tanımlayın.';
    } elseif (!hash_equals($expectedKey, $setupKey)) {
        $error = 'Kurulum anahtarı hatalı.';
    } elseif ($fullName === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Ad soyad ve geçerli bir e-posta girin.';
    } elseif (mb_strlen($password) < 10) {
        $error = 'Şifre en az 10 karakter olmalıdır.';
    } else {
        $stmt = db()->prepare(
            "INSERT INTO users (full_name, email, password_hash, role, is_active)
             VALUES (:full_name, :email, :password_hash, 'admin', 1)"
        );

        try {
            $stmt->execute([
                'full_name' => $fullName,
                'email' => $email,
                'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            ]);
            $success = 'Yönetici hesabı oluşturuldu. Bu sayfa artık kilitlendi; login sayfasına gidebilirsiniz.';
        } catch (PDOException $e) {
            $error = 'Yönetici hesabı oluşturulamadı. E-posta daha önce kullanılmış olabilir.';
        }
    }
}

$pageTitle = 'İlk Yönetici Kurulumu';
require __DIR__ . '/templates/header.php';
?>
<section class="login-shell">
    <div class="card login-card">
        <div class="login-mark">ST</div>
        <div class="page-head">
            <div>
                <h1>İlk Yönetici</h1>
                <p>Bu ekran yalnızca ilk admin hesabı oluşturulana kadar aktiftir.</p>
            </div>
        </div>

        <?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
        <?php if ($success): ?><div class="alert alert-success"><?= e($success) ?></div><?php endif; ?>

        <?php if (!$success): ?>
        <form method="post" autocomplete="off">
            <?= csrf_field() ?>
            <div class="form-group">
                <label for="full_name">Ad Soyad</label>
                <input id="full_name" name="full_name" value="<?= old('full_name') ?>" required>
            </div>
            <div class="form-group">
                <label for="email">E-posta</label>
                <input id="email" name="email" type="email" value="<?= old('email') ?>" required>
            </div>
            <div class="form-group">
                <label for="password">Şifre</label>
                <input id="password" name="password" type="password" minlength="10" required>
            </div>
            <div class="form-group">
                <label for="setup_key">Kurulum Anahtarı</label>
                <input id="setup_key" name="setup_key" type="password" required>
            </div>
            <button class="btn btn-primary" type="submit" style="width:100%">Yönetici Oluştur</button>
        </form>
        <?php else: ?>
            <a class="btn btn-primary" href="<?= e(base_path('login.php')) ?>">Giriş Sayfasına Git</a>
        <?php endif; ?>
    </div>
</section>
<?php require __DIR__ . '/templates/footer.php'; ?>
