<?php

declare(strict_types=1);

require_once __DIR__ . '/app/bootstrap.php';

$user = require_login();
if (($user['role'] ?? '') === 'admin') {
    redirect('admin/dashboard.php');
}

$leaveRepo = new LeaveRepository(db());
$leaveTypes = $leaveRepo->activeLeaveTypes();
$error = null;
$calculatedDays = null;

if (is_post()) {
    verify_csrf_or_fail();

    $leaveTypeId = filter_var($_POST['leave_type_id'] ?? null, FILTER_VALIDATE_INT);
    $startDate = trim((string) ($_POST['start_date'] ?? ''));
    $endDate = trim((string) ($_POST['end_date'] ?? ''));
    $durationType = (string) ($_POST['duration_type'] ?? 'full_day');
    $halfDayPeriod = $durationType === 'half_day' ? (string) ($_POST['half_day_period'] ?? '') : null;
    $comment = trim((string) ($_POST['comment'] ?? ''));

    if (!$leaveTypeId || $startDate === '' || $endDate === '') {
        $error = 'İzin türü ve tarih alanları zorunludur.';
    } elseif (mb_strlen($comment) > 2000) {
        $error = 'Açıklama en fazla 2000 karakter olabilir.';
    } else {
        try {
            $calculatedDays = calculate_leave_days($startDate, $endDate, $durationType, $halfDayPeriod);
            $leaveRepo->createRequest(
                (int) $user['id'],
                (int) $leaveTypeId,
                $startDate,
                $endDate,
                $durationType,
                $halfDayPeriod,
                $comment !== '' ? $comment : null,
                $calculatedDays
            );

            flash('success', 'İzin talebiniz kaydedildi ve yönetici onayına gönderildi.');
            redirect('my-leaves.php');
        } catch (InvalidArgumentException|DomainException $e) {
            $error = $e->getMessage();
        } catch (Throwable $e) {
            error_log($e->getMessage());
            $error = 'Talep kaydedilirken bir hata oluştu. Lütfen tekrar deneyin.';
        }
    }
}

$pageTitle = 'Yeni İzin Talebi';
require __DIR__ . '/templates/header.php';
?>
<div class="page-head">
    <div>
        <h1>Yeni İzin Talebi</h1>
        <p>Hafta sonları ve tanımlı resmî tatiller otomatik olarak hesap dışı bırakılır.</p>
    </div>
</div>

<?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>

<section class="card" style="max-width:760px">
    <form method="post">
        <?= csrf_field() ?>
        <div class="form-group">
            <label for="leave_type_id">İzin Türü</label>
            <select id="leave_type_id" name="leave_type_id" required>
                <option value="">Seçiniz</option>
                <?php foreach ($leaveTypes as $type): ?>
                    <option value="<?= e($type['id']) ?>" <?= ((string) ($_POST['leave_type_id'] ?? '') === (string) $type['id']) ? 'selected' : '' ?>><?= e($type['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="grid grid-2">
            <div class="form-group">
                <label for="start_date">Başlangıç Tarihi</label>
                <input id="start_date" name="start_date" type="date" value="<?= old('start_date') ?>" required>
            </div>
            <div class="form-group">
                <label for="end_date">Bitiş Tarihi</label>
                <input id="end_date" name="end_date" type="date" value="<?= old('end_date') ?>" required>
            </div>
        </div>

        <div class="form-group">
            <label for="duration_type">Süre</label>
            <select id="duration_type" name="duration_type" data-duration-type>
                <option value="full_day" <?= (($_POST['duration_type'] ?? 'full_day') === 'full_day') ? 'selected' : '' ?>>Tam Gün</option>
                <option value="half_day" <?= (($_POST['duration_type'] ?? '') === 'half_day') ? 'selected' : '' ?>>Yarım Gün</option>
            </select>
        </div>

        <div class="form-group" data-half-day-wrap hidden>
            <label for="half_day_period">Yarım Gün Dönemi</label>
            <select id="half_day_period" name="half_day_period">
                <option value="morning" <?= (($_POST['half_day_period'] ?? '') === 'morning') ? 'selected' : '' ?>>Sabah</option>
                <option value="afternoon" <?= (($_POST['half_day_period'] ?? '') === 'afternoon') ? 'selected' : '' ?>>Öğleden Sonra</option>
            </select>
        </div>

        <div class="form-group">
            <label for="comment">Açıklama</label>
            <textarea id="comment" name="comment" maxlength="2000"><?= old('comment') ?></textarea>
        </div>

        <div class="actions">
            <button class="btn btn-primary" type="submit">Talebi Gönder</button>
            <a class="btn btn-light" href="<?= e(base_path('dashboard.php')) ?>">Vazgeç</a>
        </div>
    </form>
</section>
<?php require __DIR__ . '/templates/footer.php'; ?>
