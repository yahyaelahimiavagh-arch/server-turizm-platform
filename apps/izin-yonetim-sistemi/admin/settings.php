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
        $submittedWeights = $_POST['leave_full_day_weight'] ?? [];
        $validModes = array_keys(work_schedule_modes());
        $schedule = [];
        $weights = [];

        for ($day = 1; $day <= 7; $day++) {
            $mode = is_array($submitted)
                ? (string) ($submitted[(string) $day] ?? $submitted[$day] ?? 'off')
                : 'off';
            $schedule[$day] = in_array($mode, $validModes, true) ? $mode : 'off';

            $rawWeight = is_array($submittedWeights)
                ? ($submittedWeights[(string) $day] ?? $submittedWeights[$day] ?? null)
                : null;
            $weight = is_numeric($rawWeight) ? (float) $rawWeight : 0.0;
            if (!in_array($weight, [0.0, 0.5, 1.0], true)) {
                $weight = 0.0;
            }

            if ($schedule[$day] === 'off') {
                $weight = 0.0;
            }

            $weights[$day] = $weight;
        }

        $workingDays = array_keys(array_filter(
            $schedule,
            static fn (string $mode): bool => $mode !== 'off'
        ));

        if ($workingDays === []) {
            $error = 'En az bir çalışma günü tanımlanmalıdır.';
        } elseif (count(array_filter(
            $weights,
            static fn (float $weight): bool => $weight > 0
        )) === 0) {
            $error = 'En az bir çalışma günü için izin gün katsayısı tanımlanmalıdır.';
        } else {
            save_app_settings($pdo, [
                'work_schedule_json' => json_encode(
                    $schedule,
                    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR
                ),
                'leave_full_day_weights_json' => json_encode(
                    $weights,
                    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR
                ),
                // Kept for backward compatibility with older reports/tools.
                'working_weekdays' => implode(',', $workingDays),
            ], isset($admin['id']) ? (int) $admin['id'] : null);
            flash('success', 'Çalışma takvimi güncellendi. Tam gün, yarım gün ve çalışma dışı günler yeni izin hesaplarında uygulanacaktır.');
            redirect('admin/settings.php');
        }
    } elseif ($action === 'annual_leave_policy') {
        $submitted = $_POST['tier_company_days'] ?? [];
        $tiers = annual_leave_policy_tiers($pdo);
        $updates = [];

        if (!is_array($submitted)) {
            $error = 'Yıllık izin politikası verisi geçersiz.';
        } else {
            foreach ($tiers as $tier) {
                $tierId = (int) $tier['id'];
                $raw = $submitted[(string) $tierId] ?? $submitted[$tierId] ?? null;
                $days = filter_var($raw, FILTER_VALIDATE_FLOAT);
                $legalMinimum = (float) $tier['legal_minimum_days'];

                if ($days === false || $days < $legalMinimum || $days > 365) {
                    $error = sprintf(
                        '%d. politika satırı için şirket izni yasal asgari %.1f günün altında olamaz.',
                        $tierId,
                        $legalMinimum
                    );
                    break;
                }

                $updates[$tierId] = (float) $days;
            }
        }

        if ($error === null) {
            $pdo->beginTransaction();
            try {
                $update = $pdo->prepare(
                    'UPDATE annual_leave_policy_tiers
                     SET company_days = :company_days
                     WHERE id = :id'
                );

                foreach ($updates as $tierId => $days) {
                    $update->execute([
                        'company_days' => number_format($days, 2, '.', ''),
                        'id' => $tierId,
                    ]);
                }

                audit_log_event(
                    $pdo,
                    isset($admin['id']) ? (int) $admin['id'] : null,
                    'annual_leave_policy_updated',
                    'annual_leave_policy',
                    'service_year_tiers',
                    ['company_days' => $updates]
                );

                $pdo->commit();
                flash('success', 'Yıllık izin hizmet yılı politikası güncellendi. Mevcut hak ediş snapshotları değişmez; yeni hak edişler yeni politikayı kullanır.');
                redirect('admin/settings.php');
            } catch (Throwable $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                error_log($e->getMessage());
                $error = 'Yıllık izin politikası güncellenemedi.';
            }
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
$leaveFullDayWeights = configured_leave_full_day_weights();
$workingDays = configured_working_weekdays();
$weekdayLabels = weekday_labels();
$maxConcurrentLeave = max(0, (int) app_setting('max_concurrent_leave_employees', '2'));
$annualLeaveTiers = annual_leave_policy_tiers($pdo);
$annualLeaveAgeRules = annual_leave_age_rules($pdo);
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
                <label for="default_annual_allowance_days">Legacy Takvim-Yılı Varsayılanı</label>
                <input id="default_annual_allowance_days" name="default_annual_allowance_days" type="number" min="0" max="365" step="0.5" value="<?= e(format_days($defaultDays)) ?>" required>
                <div class="form-note">Yalnız eski V1 employee/year kayıtlarıyla uyumluluk içindir. Yeni çalışanların yasal yıllık izni aşağıdaki Hizmet Yılı Politikası ile hesaplanır.</div>
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
            Çalışma düzeni ile izin gün hesabı birbirinden ayrı yönetilir.
            Örneğin Cumartesi yarım gün çalışılsa bile tam gün izin talebinde 1 gün düşecek şekilde ayarlanabilir.
        </p>
        <form method="post">
            <?= csrf_field() ?>
            <input type="hidden" name="settings_action" value="workweek">

            <div class="work-schedule-editor">
                <?php foreach ($weekdayLabels as $dayNumber => $label): ?>
                    <div class="work-schedule-row work-schedule-row-3">
                        <label for="work_schedule_<?= e((string) $dayNumber) ?>"><?= e($label) ?></label>
                        <select id="work_schedule_<?= e((string) $dayNumber) ?>" name="work_schedule[<?= e((string) $dayNumber) ?>]" aria-label="<?= e($label) ?> çalışma düzeni">
                            <?php foreach ($workScheduleModes as $modeKey => $mode): ?>
                                <option value="<?= e($modeKey) ?>" <?= (($workSchedule[$dayNumber] ?? 'off') === $modeKey) ? 'selected' : '' ?>>
                                    <?= e((string) $mode['label']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <select name="leave_full_day_weight[<?= e((string) $dayNumber) ?>]" aria-label="<?= e($label) ?> tam gün izin kesintisi">
                            <?php foreach (['1' => '1 Gün', '0.5' => '0,5 Gün', '0' => 'Kesinti Yok'] as $weight => $weightLabel): ?>
                                <option value="<?= e((string) $weight) ?>" <?= abs((float) ($leaveFullDayWeights[$dayNumber] ?? 0) - (float) $weight) < 0.001 ? 'selected' : '' ?>>
                                    <?= e($weightLabel) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="form-note" style="margin-bottom:14px">
                Server Turizm başlangıç politikası: Pazartesi–Cuma tam gün; Cumartesi yarım gün çalışma fakat tam gün izin talebinde 1 gün kesinti; Pazar çalışma yok ve izin kesintisi yok.
            </div>

            <button class="btn btn-primary" type="submit">Çalışma Takvimini Kaydet</button>
        </form>
    </section>

    <section class="card">
        <h2 class="section-title">Yıllık İzin — Hizmet Yılı Politikası</h2>
        <p class="form-note">
            Hak ediş takvim yılına göre sıfırlanmaz; işe giriş yıldönümünde oluşur.
            Kullanılmayan haklar iş ilişkisi devam ettiği sürece devreder. Aktif çalışan için yıllık izin hakkı nakit ödeme ile kapatılamaz.
        </p>
        <form method="post">
            <?= csrf_field() ?>
            <input type="hidden" name="settings_action" value="annual_leave_policy">

            <div class="table-wrap">
                <table>
                    <thead>
                    <tr><th>Tamamlanan Hizmet</th><th>Yasal Asgari</th><th>Şirket Politikası</th></tr>
                    </thead>
                    <tbody>
                    <?php foreach ($annualLeaveTiers as $tier): ?>
                        <?php
                        $minYears = (int) $tier['min_completed_years'];
                        $maxYears = $tier['max_completed_years'] !== null ? (int) $tier['max_completed_years'] : null;
                        $rangeLabel = $maxYears === null
                            ? $minYears . '+ yıl'
                            : $minYears . '–' . $maxYears . ' yıl';
                        ?>
                        <tr>
                            <td><?= e($rangeLabel) ?></td>
                            <td><?= e(format_days((float) $tier['legal_minimum_days'])) ?> gün</td>
                            <td>
                                <input
                                    name="tier_company_days[<?= e((string) $tier['id']) ?>]"
                                    type="number"
                                    min="<?= e((string) $tier['legal_minimum_days']) ?>"
                                    max="365"
                                    step="0.5"
                                    value="<?= e(format_days((float) $tier['company_days'])) ?>"
                                    required
                                >
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="form-note" style="margin:12px 0">
                Yaş koruması:
                <?php foreach ($annualLeaveAgeRules as $index => $rule): ?>
                    <?php
                    $minAge = $rule['min_age'] !== null ? (int) $rule['min_age'] : null;
                    $maxAge = $rule['max_age'] !== null ? (int) $rule['max_age'] : null;
                    if ($minAge === null) {
                        $ageLabel = $maxAge . ' yaş ve altı';
                    } elseif ($maxAge === null) {
                        $ageLabel = $minAge . ' yaş ve üzeri';
                    } else {
                        $ageLabel = $minAge . '–' . $maxAge . ' yaş';
                    }
                    ?>
                    <?= $index > 0 ? ' · ' : '' ?><?= e($ageLabel) ?>: en az <?= e(format_days((float) $rule['legal_minimum_days'])) ?> gün
                <?php endforeach; ?>
            </div>

            <button class="btn btn-primary" type="submit">Yıllık İzin Politikasını Kaydet</button>
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
