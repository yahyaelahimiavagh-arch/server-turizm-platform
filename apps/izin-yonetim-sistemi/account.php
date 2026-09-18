<?php

declare(strict_types=1);

require_once __DIR__ . '/app/bootstrap.php';

$user = require_login();
$pdo = db();
$error = null;

if (is_post()) {
    verify_csrf_or_fail();

    $currentPassword = (string) ($_POST['current_password'] ?? '');
    $newPassword = (string) ($_POST['new_password'] ?? '');
    $newPasswordConfirm = (string) ($_POST['new_password_confirm'] ?? '');
    $fullName = trim((string) ($_POST['full_name'] ?? ($user['full_name'] ?? '')));
    $email = mb_strtolower(trim((string) ($_POST['email'] ?? ($user['email'] ?? ''))));
    $isAdmin = ($user['role'] ?? '') === 'admin';

    $stmt = $pdo->prepare('SELECT password_hash FROM users WHERE id=:id LIMIT 1');
    $stmt->execute(['id' => (int) $user['id']]);
    $passwordHash = (string) ($stmt->fetchColumn() ?: '');

    if ($currentPassword === '' || !password_verify($currentPassword, $passwordHash)) {
        $error = 'Mevcut şifreniz doğrulanamadı.';
    } elseif ($newPassword !== '' && mb_strlen($newPassword) < 10) {
        $error = 'Yeni şifre en az 10 karakter olmalıdır.';
    } elseif ($newPassword !== '' && $newPassword !== $newPasswordConfirm) {
        $error = 'Yeni şifre ve tekrarı eşleşmiyor.';
    } elseif ($isAdmin && ($fullName === '' || !filter_var($email, FILTER_VALIDATE_EMAIL))) {
        $error = 'Yönetici adı ve geçerli e-posta zorunludur.';
    } else {
        if ($isAdmin) {
            $duplicate = $pdo->prepare('SELECT COUNT(*) FROM users WHERE email=:email AND id<>:id');
            $duplicate->execute(['email' => $email, 'id' => (int) $user['id']]);
            if ((int) $duplicate->fetchColumn() > 0) {
                $error = 'Bu e-posta başka bir hesapta kullanılıyor.';
            }
        }

        if ($error === null) {
            $fields = [];
            $params = ['id' => (int) $user['id']];

            if ($isAdmin) {
                $fields[] = 'full_name=:full_name';
                $fields[] = 'email=:email';
                $params['full_name'] = $fullName;
                $params['email'] = $email;
            }

            if ($newPassword !== '') {
                $fields[] = 'password_hash=:password_hash';
                $params['password_hash'] = password_hash($newPassword, PASSWORD_DEFAULT);
            }

            if ($fields === []) {
                $error = 'Değiştirilecek bir bilgi girilmedi.';
            } else {
                $update = $pdo->prepare('UPDATE users SET ' . implode(', ', $fields) . ' WHERE id=:id');
                $update->execute($params);

                audit_log_event(
                    $pdo,
                    (int) $user['id'],
                    'account_updated',
                    'user',
                    (int) $user['id'],
                    [
                        'admin_identity_updated' => $isAdmin,
                        'password_changed' => $newPassword !== '',
                    ]
                );

                session_regenerate_id(true);
                flash('success', 'Hesap bilgileriniz güncellendi.');
                redirect('account.php');
            }
        }
    }
}

$user = require_login();
$success = flash('success');
$pageTitle = 'Hesabım';
require __DIR__ . '/templates/header.php';
?>
<div class="page-head">
    <div>
        <h1>Hesabım</h1>
        <p>Hesap ve güvenlik bilgilerinizi yönetin.</p>
    </div>
</div>

<?php if ($success): ?><div class="alert alert-success"><?= e($success) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>

<section class="card" style="max-width:720px">
    <form method="post" autocomplete="off">
        <?= csrf_field() ?>

        <?php if (($user['role'] ?? '') === 'admin'): ?>
            <div class="form-group">
                <label for="full_name">Yönetici Ad Soyad</label>
                <input id="full_name" name="full_name" value="<?= e((string) $user['full_name']) ?>" required>
            </div>
            <div class="form-group">
                <label for="email">Yönetici E-posta</label>
                <input id="email" name="email" type="email" value="<?= e((string) $user['email']) ?>" required>
            </div>
            <div class="form-note" style="margin-bottom:18px">
                Yönetim hesabını başka bir yetkiliye devrederken ad ve e-posta buradan değiştirilebilir.
            </div>
        <?php else: ?>
            <div class="form-group">
                <label>Ad Soyad</label>
                <input value="<?= e((string) $user['full_name']) ?>" disabled>
            </div>
            <div class="form-group">
                <label>E-posta</label>
                <input value="<?= e((string) $user['email']) ?>" disabled>
            </div>
        <?php endif; ?>

        <div class="form-group">
            <label for="current_password">Mevcut Şifre</label>
            <input id="current_password" name="current_password" type="password" required autocomplete="current-password">
        </div>

        <div class="form-group">
            <label for="new_password">Yeni Şifre</label>
            <input id="new_password" name="new_password" type="password" minlength="10" autocomplete="new-password">
            <div class="form-note">Şifre değiştirmek istemiyorsanız boş bırakın. En az 10 karakter.</div>
        </div>

        <div class="form-group">
            <label for="new_password_confirm">Yeni Şifre Tekrar</label>
            <input id="new_password_confirm" name="new_password_confirm" type="password" minlength="10" autocomplete="new-password">
        </div>

        <button class="btn btn-primary" type="submit">Hesabı Güncelle</button>
    </form>
</section>
<?php require __DIR__ . '/templates/footer.php'; ?>
