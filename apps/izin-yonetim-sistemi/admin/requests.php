<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/bootstrap.php';

$admin = require_admin();
$leaveRepo = new LeaveRepository(db());

if (is_post()) {
    verify_csrf_or_fail();

    $requestId = filter_var($_POST['request_id'] ?? null, FILTER_VALIDATE_INT);
    $decision = (string) ($_POST['decision'] ?? '');
    $adminNote = trim((string) ($_POST['admin_note'] ?? ''));

    if (!$requestId || !in_array($decision, ['approved', 'rejected'], true)) {
        flash('error', 'Geçersiz işlem.');
        redirect('admin/requests.php');
    }

    try {
        $leaveRepo->processRequest((int) $requestId, (int) $admin['id'], $decision, $adminNote !== '' ? $adminNote : null);
        flash('success', $decision === 'approved' ? 'İzin talebi onaylandı.' : 'İzin talebi reddedildi.');
    } catch (DomainException $e) {
        flash('error', $e->getMessage());
    } catch (Throwable $e) {
        error_log($e->getMessage());
        flash('error', 'İşlem sırasında bir hata oluştu.');
    }

    redirect('admin/requests.php');
}

$pending = $leaveRepo->pendingRequests();
$success = flash('success');
$error = flash('error');
$pageTitle = 'İzin Talepleri';
require dirname(__DIR__) . '/templates/header.php';
?>
<div class="page-head">
    <div><h1>Bekleyen İzin Talepleri</h1><p>Onay veya red kararı verin.</p></div>
</div>
<?php if ($success): ?><div class="alert alert-success"><?= e($success) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>

<?php if (!$pending): ?>
    <div class="card">Bekleyen izin talebi yok.</div>
<?php endif; ?>

<div class="grid">
<?php foreach ($pending as $row): ?>
    <section class="card" id="request-<?= e($row['id']) ?>">
        <div class="page-head">
            <div>
                <h2 class="section-title"><?= e($row['full_name']) ?> — <?= e($row['leave_type_name']) ?></h2>
                <p><?= e($row['start_date']) ?><?= $row['start_date'] !== $row['end_date'] ? ' — ' . e($row['end_date']) : '' ?> · <?= e(format_days((float) $row['requested_days'])) ?> gün</p>
            </div>
            <span class="badge badge-pending">Bekliyor</span>
        </div>
        <?php if ($row['employee_comment']): ?><p><strong>Açıklama:</strong> <?= e($row['employee_comment']) ?></p><?php endif; ?>
        <form method="post">
            <?= csrf_field() ?>
            <input type="hidden" name="request_id" value="<?= e($row['id']) ?>">
            <div class="form-group">
                <label for="note-<?= e($row['id']) ?>">Yönetici Notu (opsiyonel)</label>
                <textarea id="note-<?= e($row['id']) ?>" name="admin_note" maxlength="2000"></textarea>
            </div>
            <div class="actions">
                <button class="btn btn-primary" type="submit" name="decision" value="approved">Onayla</button>
                <button class="btn btn-danger" type="submit" name="decision" value="rejected">Reddet</button>
            </div>
        </form>
    </section>
<?php endforeach; ?>
</div>
<?php require dirname(__DIR__) . '/templates/footer.php'; ?>
