<?php

declare(strict_types=1);

require_once __DIR__ . '/app/bootstrap.php';

if (current_user() !== null) {
    redirect('');
}

$error = null;

if (is_post()) {
    verify_csrf_or_fail();

    $email = mb_strtolower(trim((string) ($_POST['email'] ?? '')));
    $password = (string) ($_POST['password'] ?? '');

    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $password === '') {
        $error = 'E-posta ve şifrenizi kontrol edin.';
    } elseif (login_rate_limit_is_blocked($email)) {
        $error = 'Çok fazla başarısız giriş denemesi yapıldı. Lütfen 15 dakika sonra tekrar deneyin.';
    } elseif (!verify_turnstile_response()) {
        $error = 'Güvenlik doğrulaması tamamlanamadı. Lütfen tekrar deneyin.';
    } elseif (!attempt_login($email, $password)) {
        record_login_failure($email);
        usleep(250000);
        $error = 'E-posta veya şifre hatalı.';
    } else {
        clear_login_failures($email);
        redirect('');
    }
}

$pageTitle = 'Giriş — ' . application_name();
require __DIR__ . '/templates/header.php';
?>
<section class="login-shell">
    <div class="card login-card">
        <div class="login-mark">İY</div>
        <div class="page-head">
            <div>
                <h1>Giriş Yap</h1>
                <p><?= e(application_name()) ?></p>
            </div>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?= e($error) ?></div>
        <?php endif; ?>

        <form method="post" autocomplete="on" novalidate>
            <?= csrf_field() ?>
            <div class="form-group">
                <label for="email">E-posta</label>
                <input id="email" name="email" type="email" autocomplete="username" value="<?= old('email') ?>" required>
            </div>
            <div class="form-group">
                <label for="password">Şifre</label>
                <input id="password" name="password" type="password" autocomplete="current-password" required>
            </div>

            <?php if (turnstile_enabled()): ?>
                <div class="turnstile-wrap">
                    <?= turnstile_widget_html() ?>
                </div>
            <?php endif; ?>

            <button type="submit" class="btn btn-primary" style="width:100%">Giriş Yap</button>
        </form>
    </div>
</section>
<?php if (turnstile_enabled()): ?>
<script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
<?php endif; ?>
<?php require __DIR__ . '/templates/footer.php'; ?>
