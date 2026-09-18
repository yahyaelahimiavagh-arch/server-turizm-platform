<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/bootstrap.php';

$admin = require_admin();
$pdo = db();
$error = null;

if (is_post()) {
    verify_csrf_or_fail();
    $action = (string) ($_POST['backup_action'] ?? '');

    try {
        if ($action === 'save') {
            $enabled = isset($_POST['enabled']) ? '1' : '0';
            $spreadsheetId = google_sheets_backup_normalize_spreadsheet_id(
                (string) ($_POST['spreadsheet_id'] ?? '')
            );
            $batchSize = filter_var($_POST['batch_size'] ?? 20, FILTER_VALIDATE_INT);

            if ($enabled === '1' && $spreadsheetId === '') {
                throw new DomainException('Etkinleştirmek için geçerli Google Spreadsheet ID veya URL girin.');
            }
            if ($batchSize === false || $batchSize < 1 || $batchSize > 100) {
                throw new DomainException('Batch boyutu 1 ile 100 arasında olmalıdır.');
            }

            $stmt = $pdo->prepare(
                "INSERT INTO app_settings (setting_key, setting_value)
                 VALUES (:key, :value)
                 ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)"
            );
            foreach ([
                'google_sheets_backup_enabled' => $enabled,
                'google_sheets_spreadsheet_id' => $spreadsheetId,
                'google_sheets_backup_batch_size' => (string) $batchSize,
            ] as $key => $value) {
                $stmt->execute(['key' => $key, 'value' => $value]);
            }

            audit_log_event(
                $pdo,
                isset($admin['id']) ? (int) $admin['id'] : null,
                'google_sheets_backup_settings_updated',
                'google_sheets_backup',
                'global',
                ['enabled' => $enabled === '1', 'batch_size' => $batchSize]
            );

            flash('success', 'Google Sheets yedek ayarları kaydedildi.');
            redirect('admin/google-sheets-backup.php');
        }

        if ($action === 'queue_all') {
            $count = google_sheets_backup_queue_all_employees($pdo);
            flash('success', $count . ' çalışan tam yedek kuyruğuna alındı.');
            redirect('admin/google-sheets-backup.php');
        }

        if ($action === 'retry_failed') {
            $count = google_sheets_backup_retry_failed($pdo);
            flash('success', $count . ' başarısız kayıt yeniden deneme kuyruğuna alındı.');
            redirect('admin/google-sheets-backup.php');
        }

        if ($action === 'sync_now') {
            $limit = max(1, min(100, (int) app_setting('google_sheets_backup_batch_size', '20')));
            $result = google_sheets_backup_process_queue($pdo, $limit);
            flash(
                'success',
                sprintf(
                    'Senkronizasyon tamamlandı: %d işlendi, %d başarılı, %d hatalı.',
                    (int) $result['processed'],
                    (int) $result['succeeded'],
                    (int) $result['failed']
                )
            );
            redirect('admin/google-sheets-backup.php');
        }

        throw new DomainException('Geçersiz yedek işlemi.');
    } catch (DomainException|RuntimeException $e) {
        $error = $e->getMessage();
    } catch (Throwable $e) {
        error_log('[google-sheets-backup-admin] ' . $e->getMessage());
        $error = 'Google Sheets yedek işlemi tamamlanamadı.';
    }
}

$status = google_sheets_backup_status($pdo);
$enabled = app_setting_bool('google_sheets_backup_enabled', false);
$spreadsheetId = (string) app_setting('google_sheets_spreadsheet_id', '');
$batchSize = max(1, min(100, (int) app_setting('google_sheets_backup_batch_size', '20')));
$credentialsReady = google_sheets_backup_credentials() !== null;
$serviceEmail = google_sheets_backup_service_account_email();
$success = flash('success');

$pageTitle = 'Google Sheets Yedek';
require dirname(__DIR__) . '/templates/header.php';
?>
<div class="page-head">
    <div>
        <h1>Google Sheets — Tek Yönlü Yedek</h1>
        <p>Web sitesi Google Sheet'e yazar. Google Sheet web sitesine erişemez ve ana veri kaynağı değildir.</p>
    </div>
    <a class="btn btn-light" href="<?= e(base_path('admin/settings.php')) ?>">Ayarlara Dön</a>
</div>

<?php if ($success): ?><div class="alert alert-success"><?= e($success) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>

<div class="grid grid-4">
    <div class="card stat-card"><div class="label">Durum</div><div class="value" style="font-size:22px"><?= $enabled ? 'Açık' : 'Kapalı' ?></div></div>
    <div class="card stat-card"><div class="label">Bekleyen</div><div class="value"><?= e((string) $status['pending']) ?></div></div>
    <div class="card stat-card"><div class="label">Hatalı</div><div class="value"><?= e((string) $status['failed']) ?></div></div>
    <div class="card stat-card"><div class="label">Yedeklenen Çalışan</div><div class="value"><?= e((string) $status['registry']) ?></div></div>
</div>

<div class="grid grid-2 mt-24">
    <section class="card">
        <h2 class="section-title">Bağlantı Ayarları</h2>
        <form method="post">
            <?= csrf_field() ?>
            <input type="hidden" name="backup_action" value="save">

            <label class="checkbox-row" style="margin-bottom:16px">
                <input type="checkbox" name="enabled" value="1" <?= $enabled ? 'checked' : '' ?>>
                Google Sheets otomatik yedeğini etkinleştir
            </label>

            <div class="form-group">
                <label for="spreadsheet_id">Google Spreadsheet ID veya URL</label>
                <input id="spreadsheet_id" name="spreadsheet_id" value="<?= e($spreadsheetId) ?>">
                <div class="form-note">Dosyayı siz oluşturursunuz; sistem sadece bu dosyaya yazma yetkisi kullanır.</div>
            </div>

            <div class="form-group">
                <label for="batch_size">Tek Çalıştırmada Maksimum Kayıt</label>
                <input id="batch_size" name="batch_size" type="number" min="1" max="100" value="<?= e((string) $batchSize) ?>">
            </div>

            <button class="btn btn-primary" type="submit">Yedek Ayarlarını Kaydet</button>
        </form>
    </section>

    <section class="card">
        <h2 class="section-title">Private Kimlik Durumu</h2>
        <p><strong>Service Account:</strong> <?= $credentialsReady ? 'Hazır' : 'Eksik / okunamıyor' ?></p>
        <?php if ($serviceEmail): ?>
            <p class="form-note">Google Sheet'i aşağıdaki e-posta ile <strong>Editor</strong> olarak paylaşın:</p>
            <p><code><?= e($serviceEmail) ?></code></p>
        <?php endif; ?>
        <p class="form-note">
            Service-account JSON dosyası yalnızca <code>/home/.../izin-private/</code> altında tutulur.
            GitHub'a, public_html'a veya Google Sheet hücrelerine yazılmaz.
        </p>
        <p><strong>Son başarılı sync:</strong> <?= e((string) ($status['last_success_at'] ?? 'Henüz yok')) ?></p>
    </section>

    <section class="card">
        <h2 class="section-title">Yedek İşlemleri</h2>
        <div class="actions" style="flex-wrap:wrap">
            <form method="post">
                <?= csrf_field() ?>
                <input type="hidden" name="backup_action" value="queue_all">
                <button class="btn btn-light" type="submit">Tüm Çalışanları Kuyruğa Al</button>
            </form>
            <form method="post">
                <?= csrf_field() ?>
                <input type="hidden" name="backup_action" value="sync_now">
                <button class="btn btn-primary" type="submit">Şimdi Senkronize Et</button>
            </form>
            <form method="post">
                <?= csrf_field() ?>
                <input type="hidden" name="backup_action" value="retry_failed">
                <button class="btn btn-light" type="submit">Hatalıları Yeniden Dene</button>
            </form>
        </div>
        <p class="form-note" style="margin-top:14px">
            Her çalışan için ayrı sekme oluşturulur. Silinen çalışan sekmesi silinmez; <strong>DELETED - EMP-...</strong> adıyla arşivlenir.
        </p>
    </section>

    <section class="card">
        <h2 class="section-title">Gizlilik ve Veri Kapsamı</h2>
        <p class="form-note">
            Şifreler, password hash'leri, session/token bilgileri, tıbbi belge dosyaları ve serbest metin açıklamalar Google Sheet'e aktarılmaz.
            Belge için yalnızca <strong>Var/Yok</strong> bilgisi yedeklenir.
        </p>
        <p class="form-note">
            Sistem ayrıca <strong>_INDEX</strong>, <strong>_EVENTS</strong> ve <strong>_SYNC_LOG</strong> sekmelerini otomatik yönetir.
        </p>
    </section>
</div>
<?php require dirname(__DIR__) . '/templates/footer.php'; ?>
