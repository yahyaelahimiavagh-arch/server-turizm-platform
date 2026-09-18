<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/bootstrap.php';
require_admin();

$pdo = db();
$error = null;

if (is_post()) {
    verify_csrf_or_fail();
    $action = (string) ($_POST['action'] ?? 'save');

    if ($action === 'toggle') {
        $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
        if ($id) {
            $stmt = $pdo->prepare('UPDATE leave_types SET is_active = IF(is_active = 1, 0, 1) WHERE id = :id');
            $stmt->execute(['id' => $id]);
            flash('success', 'İzin türü durumu güncellendi.');
        }
        redirect('admin/leave-types.php');
    }

    $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT) ?: null;
    $code = strtolower(trim((string) ($_POST['code'] ?? '')));
    $name = trim((string) ($_POST['name'] ?? ''));
    $deducts = isset($_POST['deducts_annual_allowance']) ? 1 : 0;
    $requiresAttachment = isset($_POST['requires_attachment']) ? 1 : 0;
    $color = strtoupper(trim((string) ($_POST['color_hex'] ?? '#071B4D')));
    $sortOrder = filter_var($_POST['sort_order'] ?? 0, FILTER_VALIDATE_INT);

    if (!preg_match('/^[a-z0-9_-]{2,50}$/', $code) || $name === '') {
        $error = 'Kod ve ad alanlarını kontrol edin.';
    } elseif (!preg_match('/^#[0-9A-F]{6}$/', $color)) {
        $error = 'Renk #RRGGBB formatında olmalıdır.';
    }

    if ($error === null && $id) {
        $currentStmt = $pdo->prepare(
            'SELECT deducts_annual_allowance
             FROM leave_types
             WHERE id = :id
             LIMIT 1'
        );
        $currentStmt->execute(['id' => $id]);
        $currentDeducts = $currentStmt->fetchColumn();

        if ($currentDeducts === false) {
            $error = 'İzin türü bulunamadı.';
        } elseif ((int) $currentDeducts !== $deducts) {
            $usageStmt = $pdo->prepare(
                'SELECT COUNT(*)
                 FROM leave_requests
                 WHERE leave_type_id = :id'
            );
            $usageStmt->execute(['id' => $id]);

            if ((int) $usageStmt->fetchColumn() > 0) {
                $error = 'Daha önce kullanılmış bir izin türünün yıllık haktan düşme davranışı değiştirilemez.';
            }
        }
    }

    if ($error === null) {
        try {
            if ($id) {
                $stmt = $pdo->prepare(
                    'UPDATE leave_types
                     SET code = :code, name = :name, deducts_annual_allowance = :deducts,
                         requires_attachment = :requires_attachment, color_hex = :color, sort_order = :sort_order
                     WHERE id = :id'
                );
                $stmt->execute([
                    'code' => $code,
                    'name' => $name,
                    'deducts' => $deducts,
                    'requires_attachment' => $requiresAttachment,
                    'color' => $color,
                    'sort_order' => $sortOrder ?: 0,
                    'id' => $id,
                ]);
            } else {
                $stmt = $pdo->prepare(
                    'INSERT INTO leave_types (code, name, deducts_annual_allowance, requires_attachment, color_hex, is_active, sort_order)
                     VALUES (:code, :name, :deducts, :requires_attachment, :color, 1, :sort_order)'
                );
                $stmt->execute([
                    'code' => $code,
                    'name' => $name,
                    'deducts' => $deducts,
                    'requires_attachment' => $requiresAttachment,
                    'color' => $color,
                    'sort_order' => $sortOrder ?: 0,
                ]);
            }
            flash('success', 'İzin türü kaydedildi.');
            redirect('admin/leave-types.php');
        } catch (PDOException $e) {
            $error = 'İzin türü kaydedilemedi. Kod benzersiz olmalıdır.';
        }
    }
}

$editId = filter_input(INPUT_GET, 'edit', FILTER_VALIDATE_INT);
$edit = null;
if ($editId) {
    $stmt = $pdo->prepare('SELECT * FROM leave_types WHERE id = :id');
    $stmt->execute(['id' => $editId]);
    $edit = $stmt->fetch() ?: null;
}
$rows = $pdo->query('SELECT * FROM leave_types ORDER BY sort_order, name')->fetchAll();
$success = flash('success');
$pageTitle = 'İzin Türleri';
require dirname(__DIR__) . '/templates/header.php';
?>
<div class="page-head"><div><h1>İzin Türleri</h1><p>İzin türlerini, yıllık haktan düşme ve belge zorunluluğu politikalarını yönetin.</p></div></div>
<?php if ($success): ?><div class="alert alert-success"><?= e($success) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
<div class="grid grid-2">
    <section class="card">
        <h2 class="section-title"><?= $edit ? 'İzin Türünü Düzenle' : 'Yeni İzin Türü' ?></h2>
        <form method="post">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="save">
            <?php if ($edit): ?><input type="hidden" name="id" value="<?= e($edit['id']) ?>"><?php endif; ?>
            <div class="form-group"><label for="code">Kod</label><input id="code" name="code" value="<?= e($edit['code'] ?? '') ?>" pattern="[a-z0-9_-]{2,50}" required></div>
            <div class="form-group"><label for="name">Ad</label><input id="name" name="name" value="<?= e($edit['name'] ?? '') ?>" required></div>
            <div class="form-group"><label for="color_hex">Renk</label><input id="color_hex" name="color_hex" value="<?= e($edit['color_hex'] ?? '#071B4D') ?>" required></div>
            <div class="form-group"><label for="sort_order">Sıra</label><input id="sort_order" name="sort_order" type="number" value="<?= e((string) ($edit['sort_order'] ?? 0)) ?>"></div>
            <div class="form-group"><label><input style="width:auto" type="checkbox" name="deducts_annual_allowance" value="1" <?= !empty($edit['deducts_annual_allowance']) ? 'checked' : '' ?>> Yıllık izin hakkından düş</label></div>
            <div class="form-group"><label><input style="width:auto" type="checkbox" name="requires_attachment" value="1" <?= !empty($edit['requires_attachment']) ? 'checked' : '' ?>> Belge yüklemek zorunlu</label><div class="form-note">Örneğin raporlu izin için PDF/JPEG/PNG belge zorunlu yapılabilir.</div></div>
            <?php if ($edit): ?><p class="form-note">Bu tür daha önce kullanıldıysa yıllık haktan düşme davranışı değiştirilemez. Belge zorunluluğu yalnızca yeni taleplere uygulanır.</p><?php endif; ?>
            <div class="actions"><button class="btn btn-primary" type="submit">Kaydet</button><?php if ($edit): ?><a class="btn btn-light" href="<?= e(base_path('admin/leave-types.php')) ?>">İptal</a><?php endif; ?></div>
        </form>
    </section>
    <section class="card">
        <h2 class="section-title">Tanımlı Türler</h2>
        <div class="table-wrap"><table><thead><tr><th>Ad</th><th>Yıllık Hak</th><th>Belge</th><th>Durum</th><th></th></tr></thead><tbody>
        <?php foreach ($rows as $row): ?>
            <tr><td><?= e($row['name']) ?></td><td><?= (int) $row['deducts_annual_allowance'] === 1 ? 'Düşer' : 'Düşmez' ?></td><td><?= (int) ($row['requires_attachment'] ?? 0) === 1 ? 'Zorunlu' : 'İsteğe bağlı' ?></td><td><?= (int) $row['is_active'] === 1 ? 'Aktif' : 'Pasif' ?></td><td class="actions"><a class="btn btn-light" href="<?= e(base_path('admin/leave-types.php?edit=' . $row['id'])) ?>">Düzenle</a><form method="post" style="display:inline"><?= csrf_field() ?><input type="hidden" name="action" value="toggle"><input type="hidden" name="id" value="<?= e($row['id']) ?>"><button class="btn btn-light" type="submit"><?= (int) $row['is_active'] === 1 ? 'Pasifleştir' : 'Aktifleştir' ?></button></form></td></tr>
        <?php endforeach; ?>
        </tbody></table></div>
    </section>
</div>
<?php require dirname(__DIR__) . '/templates/footer.php'; ?>
