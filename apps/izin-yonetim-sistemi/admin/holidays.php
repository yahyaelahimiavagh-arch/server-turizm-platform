<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/bootstrap.php';
$admin = require_admin();

$pdo = db();
$error = null;

if (is_post()) {
    verify_csrf_or_fail();
    $action = (string) ($_POST['action'] ?? 'save');

    if ($action === 'policy') {
        $value = isset($_POST['annual_leave_public_holidays_deducted']) ? '1' : '0';
        $stmt = $pdo->prepare(
            "INSERT INTO app_settings (setting_key, setting_value)
             VALUES ('annual_leave_public_holidays_deducted', :value)
             ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)"
        );
        $stmt->execute(['value' => $value]);

        audit_log_event(
            $pdo,
            (int) $admin['id'],
            'public_holiday_leave_policy_updated',
            'company_policy',
            'annual_leave_public_holidays_deducted',
            ['deducted' => $value === '1']
        );

        flash('success', 'Resmî tatil yıllık izin politikası güncellendi.');
        redirect('admin/holidays.php');
    }

    if ($action === 'import') {
        try {
            $rows = holiday_import_parse_uploaded_file($_FILES['holiday_import'] ?? []);
            $result = import_public_holiday_rows($pdo, $rows, (int) $admin['id'], 'import');

            $message = sprintf(
                'Tatil importu tamamlandı: %d yeni, %d güncellendi, %d değişmedi.',
                (int) $result['created'],
                (int) $result['updated'],
                (int) $result['unchanged']
            );
            if ((int) $result['affected_pending_requests'] > 0) {
                $message .= ' ' . (int) $result['affected_pending_requests']
                    . ' bekleyen izin talebi yeni/değişen tatil tarihleriyle çakışıyor; onay öncesi kontrol edin.';
            }

            flash('success', $message);
            redirect('admin/holidays.php');
        } catch (DomainException $e) {
            $error = $e->getMessage();
        } catch (Throwable $e) {
            error_log('[holiday-import] ' . $e->getMessage());
            $error = 'Tatil importu tamamlanamadı.';
        }
    }

    if ($action === 'delete') {
        $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
        if ($id) {
            $stmt = $pdo->prepare('DELETE FROM public_holidays WHERE id = :id');
            $stmt->execute(['id' => $id]);
            flash('success', 'Resmî tatil silindi. Geçmiş onaylı izin kayıtları değişmedi.');
        }
        redirect('admin/holidays.php');
    }

    if ($action === 'save') {
        $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT) ?: null;
        $date = trim((string) ($_POST['holiday_date'] ?? ''));
        $name = trim((string) ($_POST['name'] ?? ''));
        $isHalfDay = isset($_POST['is_half_day']);
        $period = $isHalfDay ? (string) ($_POST['half_day_period'] ?? '') : null;
        $dateObj = DateTimeImmutable::createFromFormat('!Y-m-d', $date);

        if (!$dateObj || $dateObj->format('Y-m-d') !== $date || $name === '') {
            $error = 'Geçerli tarih ve tatil adı girin.';
        } elseif ($isHalfDay && !in_array($period, ['morning', 'afternoon'], true)) {
            $error = 'Yarım gün tatil için dönem seçin.';
        } else {
            try {
                $params = [
                    'holiday_date' => $date,
                    'name' => $name,
                    'holiday_year' => (int) $dateObj->format('Y'),
                    'is_half_day' => $isHalfDay ? 1 : 0,
                    'half_day_period' => $isHalfDay ? $period : null,
                ];

                if ($id) {
                    $params['id'] = $id;
                    $stmt = $pdo->prepare(
                        "UPDATE public_holidays
                         SET holiday_date=:holiday_date, name=:name, holiday_year=:holiday_year,
                             is_half_day=:is_half_day, half_day_period=:half_day_period,
                             source_type='manual', source_uid=NULL
                         WHERE id=:id"
                    );
                } else {
                    $stmt = $pdo->prepare(
                        "INSERT INTO public_holidays
                         (holiday_date, name, holiday_year, is_half_day, half_day_period, source_type)
                         VALUES
                         (:holiday_date, :name, :holiday_year, :is_half_day, :half_day_period, 'manual')"
                    );
                }

                $stmt->execute($params);
                flash('success', 'Resmî tatil kaydedildi.');
                redirect('admin/holidays.php');
            } catch (PDOException) {
                $error = 'Bu tarih zaten tanımlı olabilir.';
            }
        }
    }
}

$editId = filter_input(INPUT_GET, 'edit', FILTER_VALIDATE_INT);
$edit = null;
if ($editId) {
    $stmt = $pdo->prepare('SELECT * FROM public_holidays WHERE id = :id');
    $stmt->execute(['id' => $editId]);
    $edit = $stmt->fetch() ?: null;
}

$year = filter_input(INPUT_GET, 'year', FILTER_VALIDATE_INT) ?: (int) date('Y');
if ($year < 2000 || $year > 2100) {
    $year = (int) date('Y');
}
$stmt = $pdo->prepare('SELECT * FROM public_holidays WHERE holiday_year = :year ORDER BY holiday_date');
$stmt->execute(['year' => $year]);
$rows = $stmt->fetchAll();
$success = flash('success');
$pageTitle = 'Resmî Tatiller';
require dirname(__DIR__) . '/templates/header.php';
?>
<div class="page-head">
    <div>
        <h1>Resmî Tatiller</h1>
        <p>Takvim, yıllık izin hesabı ve yıllık import kaynağını buradan yönetin.</p>
    </div>
</div>
<?php if ($success): ?><div class="alert alert-success"><?= e($success) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>

<div class="grid grid-2">
    <section class="card">
        <h2 class="section-title">Yıllık İzin Politikası</h2>
        <form method="post">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="policy">
            <label class="checkbox-row">
                <input type="checkbox" name="annual_leave_public_holidays_deducted" value="1"
                    <?= annual_leave_public_holidays_deducted() ? 'checked' : '' ?>>
                Resmî tatilleri yıllık izin bakiyesinden düş
            </label>
            <p class="form-note" style="margin-top:12px">
                Varsayılan: kapalı. Türkiye'de 4857 sayılı İş Kanunu Md.56 kapsamında yıllık ücretli izin süresine
                rastlayan ulusal bayram, hafta tatili ve genel tatil günleri izin süresinden sayılmaz.
                Server Turizm için bu seçeneğin kapalı tutulması gerekir.
            </p>
            <button class="btn btn-primary" type="submit">Tatil Politikasını Kaydet</button>
        </form>
    </section>

    <section class="card">
        <h2 class="section-title">Yıllık Tatil Importu</h2>
        <p class="form-note">
            CSV veya JSON yükleyin. Sistem tarihi mevcut kayıtlarla karşılaştırır:
            yeni tarihleri ekler, değişen kayıtları günceller, aynı kayıtları değiştirmez.
            Import dosyasında olmayan mevcut tatiller otomatik silinmez.
        </p>
        <form method="post" enctype="multipart/form-data">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="import">
            <div class="form-group">
                <label for="holiday_import">CSV / JSON</label>
                <input id="holiday_import" name="holiday_import" type="file" accept=".csv,.json,text/csv,application/json" required>
            </div>
            <div class="form-note" style="margin-bottom:14px">
                Kolonlar: <code>holiday_date,name,is_half_day,half_day_period,source_uid</code>.
                Yarım gün dönemi: <code>morning</code> veya <code>afternoon</code>.
            </div>
            <button class="btn btn-primary" type="submit">Tatil Dosyasını İçe Aktar</button>
        </form>
    </section>

    <section class="card">
        <h2 class="section-title"><?= $edit ? 'Tatili Düzenle' : 'Yeni Tatil' ?></h2>
        <form method="post">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="save">
            <?php if ($edit): ?><input type="hidden" name="id" value="<?= e($edit['id']) ?>"><?php endif; ?>
            <div class="form-group"><label for="holiday_date">Tarih</label><input id="holiday_date" name="holiday_date" type="date" value="<?= e($edit['holiday_date'] ?? '') ?>" required></div>
            <div class="form-group"><label for="name">Ad</label><input id="name" name="name" value="<?= e($edit['name'] ?? '') ?>" required></div>
            <div class="form-group"><label><input style="width:auto" type="checkbox" name="is_half_day" value="1" <?= !empty($edit['is_half_day']) ? 'checked' : '' ?>> Yarım gün</label></div>
            <div class="form-group"><label for="half_day_period">Yarım Gün Dönemi</label><select id="half_day_period" name="half_day_period"><option value="afternoon" <?= (($edit['half_day_period'] ?? '') === 'afternoon') ? 'selected' : '' ?>>Öğleden Sonra</option><option value="morning" <?= (($edit['half_day_period'] ?? '') === 'morning') ? 'selected' : '' ?>>Sabah</option></select></div>
            <div class="actions"><button class="btn btn-primary" type="submit">Kaydet</button><?php if ($edit): ?><a class="btn btn-light" href="<?= e(base_path('admin/holidays.php?year=' . $year)) ?>">İptal</a><?php endif; ?></div>
        </form>
    </section>

    <section class="card">
        <div class="page-head">
            <div><h2 class="section-title"><?= e((string) $year) ?> Tatilleri</h2></div>
            <div class="actions">
                <a class="btn btn-light" href="<?= e(base_path('admin/holidays.php?year=' . ($year - 1))) ?>">← <?= e((string) ($year - 1)) ?></a>
                <a class="btn btn-light" href="<?= e(base_path('admin/holidays.php?year=' . ($year + 1))) ?>"><?= e((string) ($year + 1)) ?> →</a>
            </div>
        </div>
        <div class="table-wrap"><table><thead><tr><th>Tarih</th><th>Ad</th><th>Tip</th><th>Kaynak</th><th></th></tr></thead><tbody>
        <?php if (!$rows): ?><tr><td colspan="5">Bu yıl için tatil tanımlanmamış.</td></tr><?php endif; ?>
        <?php foreach ($rows as $row): ?>
            <tr>
                <td><?= e($row['holiday_date']) ?></td>
                <td><?= e($row['name']) ?></td>
                <td><?= (int) $row['is_half_day'] === 1 ? 'Yarım Gün' : 'Tam Gün' ?></td>
                <td><?= e(($row['source_type'] ?? 'manual') === 'manual' ? 'Manuel' : 'Import') ?></td>
                <td class="actions">
                    <a class="btn btn-light" href="<?= e(base_path('admin/holidays.php?year=' . $year . '&edit=' . $row['id'])) ?>">Düzenle</a>
                    <form method="post" style="display:inline" onsubmit="return confirm('Bu tatili silmek istediğinize emin misiniz?')">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= e($row['id']) ?>">
                        <button class="btn btn-danger" type="submit">Sil</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody></table></div>
    </section>
</div>
<?php require dirname(__DIR__) . '/templates/footer.php'; ?>
