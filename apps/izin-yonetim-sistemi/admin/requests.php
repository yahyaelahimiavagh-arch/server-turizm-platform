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
$historyStmt = db()->query(
    "SELECT lr.id, u.full_name, lt.name AS leave_type_name, lr.start_date, lr.end_date,
            lr.requested_days, lr.status, lr.admin_note, lr.processed_at,
            processor.full_name AS processed_by_name,
            la.id AS attachment_id, la.original_name AS attachment_name
     FROM leave_requests lr
     INNER JOIN users u ON u.id = lr.user_id
     INNER JOIN leave_types lt ON lt.id = lr.leave_type_id
     LEFT JOIN users processor ON processor.id = lr.processed_by
     LEFT JOIN leave_attachments la ON la.leave_request_id = lr.id
     WHERE lr.status IN ('approved', 'rejected')
     ORDER BY lr.processed_at DESC, lr.id DESC
     LIMIT 100"
);
$history = $historyStmt->fetchAll();
$success = flash('success');
$error = flash('error');
$pageTitle = 'İzin Talepleri';
require dirname(__DIR__) . '/templates/header.php';
?>
<div class="page-head">
    <div><h1>İzin Talepleri</h1><p>Bekleyen talepleri işleyin ve son kararları görüntüleyin.</p></div>
</div>
<?php if ($success): ?><div class="alert alert-success"><?= e($success) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>

<h2 class="section-title">Bekleyen Talepler</h2>
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
        <?php if (!empty($row['attachment_id'])): ?>
            <p><strong>Belge:</strong> <a class="btn btn-light" href="<?= e(base_path('attachment-download.php?id=' . $row['attachment_id'])) ?>">Belgeyi İndir · <?= e($row['attachment_name']) ?></a></p>
        <?php endif; ?>
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

<section class="card mt-24">
    <h2 class="section-title">Son İşlenen Talepler</h2>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Çalışan</th><th>Tür</th><th>Tarih</th><th>Gün</th><th>Belge</th><th>Durum</th><th>İşleyen</th><th>Not</th></tr></thead>
            <tbody>
            <?php if (!$history): ?><tr><td colspan="8">Henüz işlenmiş talep yok.</td></tr><?php endif; ?>
            <?php foreach ($history as $row): ?>
                <tr>
                    <td><?= e($row['full_name']) ?></td>
                    <td><?= e($row['leave_type_name']) ?></td>
                    <td><?= e($row['start_date']) ?><?= $row['start_date'] !== $row['end_date'] ? ' — ' . e($row['end_date']) : '' ?></td>
                    <td><?= e(format_days((float) $row['requested_days'])) ?></td>
                    <td><?php if (!empty($row['attachment_id'])): ?><a href="<?= e(base_path('attachment-download.php?id=' . $row['attachment_id'])) ?>">İndir</a><?php else: ?>—<?php endif; ?></td>
                    <td><span class="badge <?= e(status_badge_class($row['status'])) ?>"><?= e(status_label($row['status'])) ?></span></td>
                    <td><?= e($row['processed_by_name'] ?: '—') ?></td>
                    <td><?= e($row['admin_note'] ?: '—') ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
<?php require dirname(__DIR__) . '/templates/footer.php'; ?>
