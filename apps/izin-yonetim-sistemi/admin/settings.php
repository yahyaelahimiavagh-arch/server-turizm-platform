<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/bootstrap.php';
require_admin();
$admin = current_user();

$pdo = db();
$error = null;

function save_app_settings(PDO $pdo, array $values, ?int $actorUserId): void
{
    $read = $pdo->prepare('SELECT setting_value FROM app_settings WHERE setting_key = :setting_key LIMIT 1');
    $write = $pdo->prepare(
        'INSERT INTO app_settings (setting_key, setting_value)
         VALUES (:setting_key, :setting_value)
         ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)'
    );

    $changes = [];

    foreach ($values as $key => $value) {
        $key = (string) $key;
        $value = (string) $value;

        $read->execute(['setting_key' => $key]);
        $before = $read->fetchColumn();
        $before = $before === false ? null : (string) $before;

        if ($before === $value) {
            continue;
        }

        $write->execute([
            'setting_key' => $key,
            'setting_value' => $value,
        ]);

        $changes[$key] = [
            'before' => $before,
            'after' => $value,
        ];
    }

    if ($changes !== []) {
        audit_log_event(
            $pdo,
            $actorUserId,
            'company_policy_updated',
            'company_policy',
            'global',
            ['changes' => $changes]
        );
    }
}

if (is_post()) {
    verify_csrf_or_fail();
    $action = (string) ($_POST['settings_action'] ?? '');

    if ($action === 'general') {
        $defaultDays = filter_var($_POST['default_annual_allowance_days'] ?? null, FILTER_VALIDATE_FLOAT);
        $attachmentMaxMb = filter_var($_POST['attachment_max_mb'] ?? null, FILTER_VALIDATE_FLOAT);
        $companyName = trim((string) ($_POST['company_name'] ?? ''));
        $appName = trim((string) ($_POST['app_name'] ?? ''));

        if ($defaultDays === false || $defaultDays < 0 || $defaultDays > 365) {
            $error = 'Varsayılan izin hakkı 0 ile 365 gün arasında olmalıdır.';
        } elseif ($attachmentMaxMb === false || $attachmentMaxMb < 1 || $attachmentMaxMb > 50) {
            $error = 'Belge yükleme limiti 1 ile 50 MB arasında olmalıdır.';
        } elseif ($companyName === '' || mb_strlen($companyName) > 150) {
            $error = 'Şirket adı zorunludur ve 150 karakteri geçemez.';
        } elseif ($appName === '' || mb_strlen($appName) > 180) {
            $error = 'Uygulama adı zorunludur ve 180 karakteri geçemez.';
        } else {
            save_app_settings($pdo, [
                'default_annual_allowance_days' => number_format((float) $defaultDays, 2, '.', ''),
                'attachment_max_mb' => number_format((float) $attachmentMaxMb, 1, '.', ''),
                'company_name' => $companyName,
                'app_name' => $appName,
            ], isset($admin['id']) ? (int) $admin['id'] : null);
            flash('success', 'Genel şirket ve izin ayarları güncellendi.');
            redirect('admin/settings.php');
        }
    } elseif ($action === 'workweek') {
        $submitted = $_POST['work_schedule'] ?? [];
        $validModes = array_keys(work_schedule_modes());
        $schedule = [];

        if (is_array($submitted)) {
            for ($day = 1; $day <= 7; $day++) {
                $mode = (string) ($submitted[(string) $day] ?? $submitted[$day] ?? 'off');
                $schedule[$day] = in_array($mode, $validModes, true) ? $mode : 'off';
            }
        }

        $workingDays = array_keys(array_filter(
            $schedule,
            static fn (string $mode): bool => $mode !== 'off'
        ));

        if ($workingDays === []) {
            $error = 'En az bir çalışma günü tanımlanmalıdır.';
        } else {
            save_app_settings($pdo, [
                'work_schedule_json' => json_encode(
                    $schedule,
                    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR
                ),
                // Kept for backward compatibility with older reports/tools.
                'working_weekdays' => implode(',', $workingDays),
            ], isset($admin['id']) ? (int) $admin['id'] : null);
            flash('success', 'Çalışma takvimi güncellendi. Tam gün, yarım gün ve çalışma dışı günlar yeni izin hesaplarında uygulanacaktır.');
            redirect('admin/settings.php');
        }
    } elseif ($action === 'staffing') {
        $maxConcurrent = filter_var(
            $_POST['max_concurrent_leave_employees'] ?? null,
            FILTER_VALIDATE_INT
        );

        if ($maxConcurrent === false || $maxConcurrent < 0 || $maxConcurrent > 100) {
            $error = 'Eşzamanlı izin eşiği 0 ile 100 arasında olmalıdır.';
        } else {
            save_app_settings($pdo, [
                'max_concurrent_leave_employees' => (string) $maxConcurrent,
            ], isset($admin['id']) ? (int) $admin['id'] : null);
            flash('success', 'Ekip kapasitesi uyarı politikası güncellendi.');
            redirect('admin/settings.php');
        }
    } else {
        $error = 'Geçersiz ayar işlemi.';
    }
}

$defaultDays = (float) app_setting('default_annual_allowance_days', '20.00');
$attachmentMaxMb = (float) app_setting('attachment_max_mb', '10');
$companyName = (string) app_setting('company_name', 'Şirket');
$appName = (string) app_setting('app_name', $companyName . ' İzin Yönetim Sistemi');
$workSchedule = configured_work_schedule();
$workScheduleModes = work_schedule_modes();
$workingDays = configured_working_weekdays();
$weekdayLabels = weekday_labels();
$maxConcurrentLeave = max(0, (int) app_setting('max_concurrent_leave_employees', '2'));
$success = flash('success');
$pageTitle = 'Ayarlar';
require dirname(__DIR__) . '/templates/header.php';
?>
<div class="page-head">
    <div>
        <h1>Şirket Politikaları</h1>
        <p>İş kuralları kod içinde kilitli değildir; yetkili yönetici tarafından buradan yönetilir.</p>
    </div>
</div>

<?php if ($success): ?><div class="alert alert-success"><?= e($success) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>

<div class="grid grid-2">
    <section class="card">
        <h2 class="section-title">Genel Ayarlar</h2>
        <form method="post">
            <?= csrf_field() ?>
            <input type="hidden" name="settings_action" value="general">

            <div class="form-group">
                <label for="company_name">Şirket Adı</label>
                <input id="company_name" name="company_name" maxlength="150" value="<?= e($companyName) ?>" required>
                <div class="form-note">Arayüz markası olarak kullanılır. Uygulama çekirdeği belirli bir şirkete bağlı değildir.</div>
            </div>

            <div class="form-group">
                <label for="app_name">Uygulama Adı</label>
                <input id="app_name" name="app_name" maxlength="180" value="<?= e($appName) ?>" required>
            </div>

            <div class="form-group">
                <label for="default_annual_allowance_days">Varsayılan Yıllık İzin Hakkı</label>
                <input id="default_annual_allowance_days" name="default_annual_allowance_days" type="number" min="0" max="365" step="0.5" value="<?= e(format_days($defaultDays)) ?>" required>
                <div class="form-note">Yeni employee/year kayıtlarında kullanılır; geçmiş kayıtları geriye dönük değiştirmez.</div>
            </div>

            <div class="form-group">
                <label for="attachment_max_mb">Belge Yükleme Limiti (MB)</label>
                <input id="attachment_max_mb" name="attachment_max_mb" type="number" min="1" max="50" step="0.5" value="<?= e(format_days($attachmentMaxMb)) ?>" required>
                <div class="form-note">PDF/JPEG/PNG dosyaları için uygulama limiti. Sunucunun PHP upload limiti daha düşükse sunucu limiti geçerlidir.</div>
            </div>

            <button class="btn btn-primary" type="submit">Genel Ayarları Kaydet</button>
        </form>
    </section>

    <section class="card">
        <h2 class="section-title">Çalışma Takvimi</h2>
        <p class="form-note">
            Her gün için tam gün, yarım gün veya çalışma dışı seçilebilir.
            İzin hesabı yalnız o günün gerçek çalışma süresini düşer.
        </p>
        <form method="post">
            <?= csrf_field() ?>
            <input type="hidden" name="settings_action" value="workweek">

            <div class="work-schedule-editor">
                <?php foreach ($weekdayLabels as $dayNumber => $label): ?>
                    <div class="work-schedule-row">
                        <label for="work_schedule_<?= e((string) $dayNumber) ?>"><?= e($label) ?></label>
                        <select id="work_schedule_<?= e((string) $dayNumber) ?>" name="work_schedule[<?= e((string) $dayNumber) ?>]">
                            <?php foreach ($workScheduleModes as $modeKey => $mode): ?>
                                <option value="<?= e($modeKey) ?>" <?= (($workSchedule[$dayNumber] ?? 'off') === $modeKey) ? 'selected' : '' ?>>
                                    <?= e((string) $mode['label']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="form-note" style="margin-bottom:14px">
                Örnek Server Turizm: Pazartesi–Cuma tam gün, Cumartesi yarım gün sabah, Pazar çalışma yok.
            </div>

            <button class="btn btn-primary" type="submit">Çalışma Takvimini Kaydet</button>
        </form>
    </section>

    <section class="card">
        <h2 class="section-title">Ekip Kapasitesi</h2>
        <p class="form-note">
            Çalışan izin tarihi seçtiğinde sistem diğer çalışanların onaylı ve bekleyen izinlerini isim göstermeden toplu olarak analiz eder.
            Bu eşik talebi otomatik reddetmez; çalışan ve yöneticiye operasyonel uyarı üretir.
        </p>
        <form method="post">
            <?= csrf_field() ?>
            <input type="hidden" name="settings_action" value="staffing">

            <div class="form-group">
                <label for="max_concurrent_leave_employees">Eşzamanlı İzin Uyarı Eşiği (Kişi)</label>
                <input
                    id="max_concurrent_leave_employees"
                    name="max_concurrent_leave_employees"
                    type="number"
                    min="0"
                    max="100"
                    step="1"
                    value="<?= e((string) $maxConcurrentLeave) ?>"
                    required
                >
                <div class="form-note">
                    Örnek: 2 seçilirse, talep onaylandığında aynı gün izinli olabilecek kişi sayısı 2'yi aşarsa uyarı gösterilir.
                    0 seçilirse eşik uyarısı kapatılır; ekip sayıları yine gösterilir.
                </div>
            </div>

            <button class="btn btn-primary" type="submit">Ekip Politikasını Kaydet</button>
        </form>
    </section>

    <section class="card">
        <h2 class="section-title">Güvenlik</h2>
        <p><strong>Cloudflare Turnstile:</strong> <?= turnstile_enabled() ? 'Aktif' : 'Kapalı / yapılandırılmadı' ?></p>
        <p class="form-note">Site key ve secret key public veritabanına yazılmaz; private config dosyasında tutulur. Login rate-limit ve CSRF koruması ayrıca aktif kalır.</p>
    </section>

    <section class="card">
        <h2 class="section-title">Operasyon Takvimi Entegrasyonu</h2>
        <p><strong>Durum:</strong> <?= operations_calendar_enabled() ? 'Aktif' : 'Kapalı / yapılandırılmadı' ?></p>
        <p class="form-note">
            Aktif olduğunda çalışan izin tarihi seçerken Tour ve Umre operasyon yoğunluğunu da görür.
            Endpoint ve erişim token'ı yalnız private config dosyasında tutulur; bu ekrandan secret okunmaz veya yazılmaz.
        </p>
    </section>

    <section class="card">
        <h2 class="section-title">Onaylı İzin Takvim Dışa Aktarımı</h2>
        <p><strong>Durum:</strong> <?= leave_calendar_export_enabled() ? 'Aktif' : 'Kapalı / yapılandırılmadı' ?></p>
        <p class="form-note">
            Aktif olduğunda yalnız onaylanmış izin günleri ortak operasyon takvimine private/internal projection olarak aktarılabilir.
            E-posta, açıklama, rapor içeriği ve belge dosyaları bu feed'e girmez. Token yalnız private config dosyasında tutulur.
        </p>
    </section>

    <section class="card">
        <h2 class="section-title">Yönetim</h2>
        <div class="actions">
            <a class="btn btn-light" href="<?= e(base_path('admin/leave-types.php')) ?>">İzin Türleri</a>
            <a class="btn btn-light" href="<?= e(base_path('admin/holidays.php')) ?>">Resmî Tatiller</a>
        </div>
        <p class="form-note">Geliştirici imzası: <a href="<?= e(developer_url()) ?>" target="_blank" rel="noopener noreferrer"><?= e(developer_name()) ?></a></p>
    </section>
</div>
<?php require dirname(__DIR__) . '/templates/footer.php'; ?>
