<?php

declare(strict_types=1);

function google_sheets_backup_runtime_config(): array
{
    $config = app_config('google_sheets_backup');
    return is_array($config) ? $config : [];
}

function google_sheets_backup_tables_ready(PDO $pdo): bool
{
    try {
        $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
        return in_array('google_sheet_sync_queue', $tables, true)
            && in_array('google_sheet_backup_registry', $tables, true);
    } catch (Throwable) {
        return false;
    }
}

function google_sheets_backup_employee_ref(int $userId): string
{
    return 'EMP-' . str_pad((string) $userId, 6, '0', STR_PAD_LEFT);
}

function google_sheets_backup_uuid(): string
{
    $bytes = random_bytes(16);
    $bytes[6] = chr((ord($bytes[6]) & 0x0f) | 0x40);
    $bytes[8] = chr((ord($bytes[8]) & 0x3f) | 0x80);
    $hex = bin2hex($bytes);

    return substr($hex, 0, 8) . '-'
        . substr($hex, 8, 4) . '-'
        . substr($hex, 12, 4) . '-'
        . substr($hex, 16, 4) . '-'
        . substr($hex, 20);
}

function google_sheets_backup_normalize_spreadsheet_id(string $value): string
{
    $value = trim($value);
    if (preg_match('~/spreadsheets/d/([A-Za-z0-9_-]+)~', $value, $matches)) {
        return (string) $matches[1];
    }

    return preg_match('/^[A-Za-z0-9_-]{20,}$/', $value) ? $value : '';
}

function google_sheets_backup_credentials(): ?array
{
    $config = google_sheets_backup_runtime_config();
    $path = trim((string) ($config['credentials_file'] ?? ''));

    if ($path === '' || !is_file($path) || !is_readable($path)) {
        return null;
    }

    try {
        $decoded = json_decode((string) file_get_contents($path), true, 32, JSON_THROW_ON_ERROR);
    } catch (Throwable) {
        return null;
    }

    if (
        !is_array($decoded)
        || trim((string) ($decoded['client_email'] ?? '')) === ''
        || trim((string) ($decoded['private_key'] ?? '')) === ''
    ) {
        return null;
    }

    return $decoded;
}

function google_sheets_backup_service_account_email(): ?string
{
    $credentials = google_sheets_backup_credentials();
    return is_array($credentials) ? trim((string) ($credentials['client_email'] ?? '')) : null;
}

function google_sheets_backup_enabled(): bool
{
    return app_setting_bool('google_sheets_backup_enabled', false)
        && google_sheets_backup_normalize_spreadsheet_id(
            (string) app_setting('google_sheets_spreadsheet_id', '')
        ) !== ''
        && google_sheets_backup_credentials() !== null;
}

function google_sheets_backup_build_employee_snapshot(PDO $pdo, int $userId): ?array
{
    $employeeStmt = $pdo->prepare(
        "SELECT id, full_name, email, is_active, hire_date, birth_date, created_at, updated_at
         FROM users
         WHERE id = :id AND role = 'employee'
         LIMIT 1"
    );
    $employeeStmt->execute(['id' => $userId]);
    $employee = $employeeStmt->fetch();

    if (!$employee) {
        return null;
    }

    $balance = null;
    try {
        $balance = annual_leave_balance($pdo, $userId, date('Y-m-d'));
    } catch (Throwable $e) {
        error_log('[google-sheets-backup] balance snapshot failed: ' . $e->getMessage());
    }

    $entitlementStmt = $pdo->prepare(
        "SELECT service_year_number, service_period_start, service_period_end, earned_on,
                entitlement_days, company_policy_days, legal_minimum_days, age_minimum_days
         FROM annual_leave_entitlements
         WHERE user_id = :user_id
         ORDER BY service_year_number ASC"
    );
    $entitlementStmt->execute(['user_id' => $userId]);

    $requestStmt = $pdo->prepare(
        "SELECT lr.id, lt.code AS leave_type_code, lt.name AS leave_type_name,
                lr.start_date, lr.end_date, lr.duration_type, lr.half_day_period,
                lr.requested_days, lr.status, lr.created_at, lr.processed_at,
                processor.full_name AS processed_by_name,
                CASE WHEN la.id IS NULL THEN 0 ELSE 1 END AS has_attachment
         FROM leave_requests lr
         INNER JOIN leave_types lt ON lt.id = lr.leave_type_id
         LEFT JOIN users processor ON processor.id = lr.processed_by
         LEFT JOIN leave_attachments la ON la.leave_request_id = lr.id
         WHERE lr.user_id = :user_id
         ORDER BY lr.created_at ASC, lr.id ASC"
    );
    $requestStmt->execute(['user_id' => $userId]);

    return [
        'schema_version' => '1.0',
        'employee_ref' => google_sheets_backup_employee_ref($userId),
        'generated_at' => date(DATE_ATOM),
        'profile' => [
            'user_id' => (int) $employee['id'],
            'full_name' => (string) $employee['full_name'],
            'email' => (string) $employee['email'],
            'is_active' => (int) $employee['is_active'] === 1,
            'hire_date' => $employee['hire_date'] !== null ? (string) $employee['hire_date'] : null,
            'birth_date' => $employee['birth_date'] !== null ? (string) $employee['birth_date'] : null,
            'created_at' => (string) $employee['created_at'],
            'updated_at' => (string) $employee['updated_at'],
            'backup_status' => (int) $employee['is_active'] === 1 ? 'active' : 'inactive',
            'deleted_at' => null,
        ],
        'annual_leave_balance' => is_array($balance) ? $balance : [],
        'entitlements' => $entitlementStmt->fetchAll(),
        'leave_requests' => $requestStmt->fetchAll(),
        'privacy' => [
            'passwords_exported' => false,
            'attachment_files_exported' => false,
            'free_text_notes_exported' => false,
        ],
    ];
}

function google_sheets_backup_queue_payload(PDO $pdo, array $snapshot, string $eventType): string
{
    if (!google_sheets_backup_tables_ready($pdo)) {
        throw new RuntimeException('Google Sheets yedek tabloları henüz kurulmamış.');
    }

    $employeeRef = trim((string) ($snapshot['employee_ref'] ?? ''));
    if ($employeeRef === '') {
        throw new InvalidArgumentException('Google Sheets yedek çalışan kimliği eksik.');
    }

    $eventUuid = google_sheets_backup_uuid();
    $payloadJson = json_encode(
        $snapshot,
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR
    );

    $stmt = $pdo->prepare(
        "INSERT INTO google_sheet_sync_queue
         (event_uuid, employee_ref, event_type, payload_json, status, attempts, available_at)
         VALUES
         (:event_uuid, :employee_ref, :event_type, :payload_json, 'pending', 0, NOW())"
    );
    $stmt->execute([
        'event_uuid' => $eventUuid,
        'employee_ref' => $employeeRef,
        'event_type' => mb_substr(trim($eventType), 0, 80),
        'payload_json' => $payloadJson,
    ]);

    return $eventUuid;
}

function google_sheets_backup_queue_payload_safely(PDO $pdo, array $snapshot, string $eventType): ?string
{
    try {
        return google_sheets_backup_queue_payload($pdo, $snapshot, $eventType);
    } catch (Throwable $e) {
        error_log('[google-sheets-backup] queue failed: ' . $e->getMessage());
        return null;
    }
}

function google_sheets_backup_queue_employee(PDO $pdo, int $userId, string $eventType): ?string
{
    $snapshot = google_sheets_backup_build_employee_snapshot($pdo, $userId);
    return is_array($snapshot)
        ? google_sheets_backup_queue_payload($pdo, $snapshot, $eventType)
        : null;
}

function google_sheets_backup_queue_employee_safely(PDO $pdo, int $userId, string $eventType): ?string
{
    try {
        return google_sheets_backup_queue_employee($pdo, $userId, $eventType);
    } catch (Throwable $e) {
        error_log('[google-sheets-backup] employee queue failed: ' . $e->getMessage());
        return null;
    }
}

function google_sheets_backup_queue_all_employees(PDO $pdo): int
{
    if (!google_sheets_backup_tables_ready($pdo)) {
        throw new RuntimeException('Önce 008 Google Sheets yedek migration dosyasını çalıştırın.');
    }

    $ids = $pdo->query(
        "SELECT id FROM users WHERE role = 'employee' ORDER BY id ASC"
    )->fetchAll(PDO::FETCH_COLUMN);

    $queued = 0;
    foreach ($ids as $id) {
        if (google_sheets_backup_queue_employee($pdo, (int) $id, 'full_backup') !== null) {
            $queued++;
        }
    }

    return $queued;
}

function google_sheets_backup_retry_failed(PDO $pdo): int
{
    $stmt = $pdo->prepare(
        "UPDATE google_sheet_sync_queue
         SET status = 'pending',
             attempts = 0,
             available_at = NOW(),
             last_error = NULL,
             updated_at = NOW()
         WHERE status = 'failed'"
    );
    $stmt->execute();
    return $stmt->rowCount();
}

function google_sheets_backup_status(PDO $pdo): array
{
    if (!google_sheets_backup_tables_ready($pdo)) {
        return [
            'tables_ready' => false,
            'pending' => 0,
            'failed' => 0,
            'succeeded' => 0,
            'registry' => 0,
            'last_success_at' => null,
        ];
    }

    $counts = ['pending' => 0, 'failed' => 0, 'succeeded' => 0];
    foreach ($pdo->query(
        "SELECT status, COUNT(*) AS total
         FROM google_sheet_sync_queue
         GROUP BY status"
    )->fetchAll() as $row) {
        $status = (string) $row['status'];
        if (array_key_exists($status, $counts)) {
            $counts[$status] = (int) $row['total'];
        }
    }

    return [
        'tables_ready' => true,
        'pending' => $counts['pending'],
        'failed' => $counts['failed'],
        'succeeded' => $counts['succeeded'],
        'registry' => (int) $pdo->query('SELECT COUNT(*) FROM google_sheet_backup_registry')->fetchColumn(),
        'last_success_at' => $pdo->query(
            "SELECT MAX(processed_at)
             FROM google_sheet_sync_queue
             WHERE status = 'succeeded'"
        )->fetchColumn() ?: null,
    ];
}

function google_sheets_backup_base64url(string $value): string
{
    return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
}

function google_sheets_backup_access_token(): string
{
    static $cached = null;
    static $expiresAt = 0;

    if (is_string($cached) && $cached !== '' && $expiresAt > time() + 60) {
        return $cached;
    }

    $credentials = google_sheets_backup_credentials();
    if (!is_array($credentials)) {
        throw new RuntimeException('Google service-account kimlik dosyası okunamadı.');
    }
    if (!extension_loaded('openssl') || !function_exists('curl_init')) {
        throw new RuntimeException('Google Sheets yedeği için OpenSSL ve cURL gereklidir.');
    }

    $tokenUri = trim((string) ($credentials['token_uri'] ?? 'https://oauth2.googleapis.com/token'));
    $now = time();
    $header = ['alg' => 'RS256', 'typ' => 'JWT'];
    $claims = [
        'iss' => (string) $credentials['client_email'],
        'scope' => 'https://www.googleapis.com/auth/spreadsheets',
        'aud' => $tokenUri,
        'iat' => $now,
        'exp' => $now + 3600,
    ];

    $unsigned = google_sheets_backup_base64url(json_encode($header, JSON_THROW_ON_ERROR))
        . '.'
        . google_sheets_backup_base64url(json_encode($claims, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));

    $signature = '';
    if (!openssl_sign($unsigned, $signature, (string) $credentials['private_key'], OPENSSL_ALGO_SHA256)) {
        throw new RuntimeException('Google service-account JWT imzalanamadı.');
    }

    $assertion = $unsigned . '.' . google_sheets_backup_base64url($signature);
    $ch = curl_init($tokenUri);
    if ($ch === false) {
        throw new RuntimeException('Google OAuth isteği başlatılamadı.');
    }

    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_TIMEOUT => 12,
        CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
        CURLOPT_POSTFIELDS => http_build_query([
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion' => $assertion,
        ]),
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_USERAGENT => 'Elahimiavagh-Leave-Backup/1.0',
    ]);

    $body = curl_exec($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    curl_close($ch);

    if (!is_string($body) || $status !== 200) {
        throw new RuntimeException('Google OAuth doğrulaması başarısız. HTTP ' . $status . '.');
    }

    $decoded = json_decode($body, true);
    $token = is_array($decoded) ? trim((string) ($decoded['access_token'] ?? '')) : '';
    if ($token === '') {
        throw new RuntimeException('Google OAuth access token alınamadı.');
    }

    $cached = $token;
    $expiresAt = $now + max(300, (int) ($decoded['expires_in'] ?? 3600));
    return $cached;
}

function google_sheets_backup_api(string $method, string $url, ?array $payload = null): array
{
    $token = google_sheets_backup_access_token();
    $ch = curl_init($url);
    if ($ch === false) {
        throw new RuntimeException('Google Sheets API isteği başlatılamadı.');
    }

    $headers = [
        'Authorization: Bearer ' . $token,
        'Accept: application/json',
    ];

    $options = [
        CURLOPT_CUSTOMREQUEST => strtoupper($method),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_USERAGENT => 'Elahimiavagh-Leave-Backup/1.0',
    ];

    if ($payload !== null) {
        $headers[] = 'Content-Type: application/json';
        $options[CURLOPT_POSTFIELDS] = json_encode(
            $payload,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR
        );
    }

    $options[CURLOPT_HTTPHEADER] = $headers;
    curl_setopt_array($ch, $options);

    $body = curl_exec($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    curl_close($ch);

    if (!is_string($body) || $status < 200 || $status >= 300) {
        $hint = $status === 403
            ? ' Spreadsheet dosyasını service-account e-postasıyla Editor olarak paylaşın.'
            : '';

        $googleMessage = '';
        if (is_string($body) && trim($body) !== '') {
            $decodedError = json_decode($body, true);
            if (is_array($decodedError)) {
                $googleMessage = trim((string) ($decodedError['error']['message'] ?? ''));
            }
        }

        $message = 'Google Sheets API HTTP ' . $status . '.';
        if ($googleMessage !== '') {
            $message .= ' ' . mb_substr($googleMessage, 0, 250);
        }

        throw new RuntimeException($message . $hint);
    }

    if (trim($body) === '') {
        return [];
    }

    $decoded = json_decode($body, true);
    return is_array($decoded) ? $decoded : [];
}

function google_sheets_backup_spreadsheet_id(): string
{
    $id = google_sheets_backup_normalize_spreadsheet_id(
        (string) app_setting('google_sheets_spreadsheet_id', '')
    );

    if ($id === '') {
        throw new RuntimeException('Google Spreadsheet ID ayarlanmamış.');
    }

    return $id;
}

function google_sheets_backup_sheet_title(string $value): string
{
    $value = preg_replace('/[:\\\/\?\*\[\]]/u', '-', trim($value)) ?? '';
    $value = preg_replace('/\s+/u', ' ', $value) ?? '';
    return mb_substr($value !== '' ? $value : 'Yedek', 0, 95);
}

function google_sheets_backup_sheet_metadata(): array
{
    $id = google_sheets_backup_spreadsheet_id();
    $url = 'https://sheets.googleapis.com/v4/spreadsheets/' . rawurlencode($id)
        . '?fields=sheets.properties(sheetId,title,index,gridProperties)';
    $decoded = google_sheets_backup_api('GET', $url);
    return is_array($decoded['sheets'] ?? null) ? $decoded['sheets'] : [];
}

function google_sheets_backup_ensure_sheet(string $title, ?string $employeeRef = null): array
{
    $title = google_sheets_backup_sheet_title($title);
    $sheets = google_sheets_backup_sheet_metadata();

    foreach ($sheets as $sheet) {
        $props = is_array($sheet['properties'] ?? null) ? $sheet['properties'] : [];
        $existingTitle = (string) ($props['title'] ?? '');
        if (
            $existingTitle === $title
            || ($employeeRef !== null && $employeeRef !== '' && str_contains($existingTitle, $employeeRef))
        ) {
            $sheetId = (int) ($props['sheetId'] ?? 0);
            if ($sheetId > 0 && $existingTitle !== $title) {
                $id = google_sheets_backup_spreadsheet_id();
                google_sheets_backup_api(
                    'POST',
                    'https://sheets.googleapis.com/v4/spreadsheets/' . rawurlencode($id) . ':batchUpdate',
                    [
                        'requests' => [[
                            'updateSheetProperties' => [
                                'properties' => [
                                    'sheetId' => $sheetId,
                                    'title' => $title,
                                ],
                                'fields' => 'title',
                            ],
                        ]],
                    ]
                );
            }

            return ['sheet_id' => $sheetId, 'title' => $title];
        }
    }

    $id = google_sheets_backup_spreadsheet_id();
    $result = google_sheets_backup_api(
        'POST',
        'https://sheets.googleapis.com/v4/spreadsheets/' . rawurlencode($id) . ':batchUpdate',
        [
            'requests' => [[
                'addSheet' => [
                    'properties' => [
                        'title' => $title,
                        'gridProperties' => [
                            'rowCount' => 5000,
                            'columnCount' => 12,
                        ],
                    ],
                ],
            ]],
        ]
    );

    $sheetId = (int) ($result['replies'][0]['addSheet']['properties']['sheetId'] ?? 0);
    if ($sheetId <= 0) {
        throw new RuntimeException('Google Sheet sekmesi oluşturulamadı.');
    }

    return ['sheet_id' => $sheetId, 'title' => $title];
}

function google_sheets_backup_a1_range(string $title, string $cells = 'A1:Z5000'): string
{
    $escapedTitle = str_replace("'", "''", google_sheets_backup_sheet_title($title));
    return "'" . $escapedTitle . "'!" . $cells;
}

function google_sheets_backup_write_rows(string $title, array $rows): void
{
    $id = google_sheets_backup_spreadsheet_id();
    $clearRange = google_sheets_backup_a1_range($title, 'A1:Z5000');
    $writeRange = google_sheets_backup_a1_range($title, 'A1');

    google_sheets_backup_api(
        'POST',
        'https://sheets.googleapis.com/v4/spreadsheets/' . rawurlencode($id)
            . '/values/' . rawurlencode($clearRange) . ':clear',
        []
    );

    google_sheets_backup_api(
        'PUT',
        'https://sheets.googleapis.com/v4/spreadsheets/' . rawurlencode($id)
            . '/values/' . rawurlencode($writeRange)
            . '?valueInputOption=RAW',
        [
            'range' => $writeRange,
            'majorDimension' => 'ROWS',
            'values' => $rows,
        ]
    );
}

function google_sheets_backup_employee_rows(array $snapshot): array
{
    $profile = is_array($snapshot['profile'] ?? null) ? $snapshot['profile'] : [];
    $balance = is_array($snapshot['annual_leave_balance'] ?? null) ? $snapshot['annual_leave_balance'] : [];
    $deleted = (($profile['backup_status'] ?? '') === 'deleted');

    $rows = [
        ['ÇALIŞAN YEDEĞİ', (string) ($snapshot['employee_ref'] ?? '')],
        ['Durum', $deleted ? 'SİLİNMİŞ / ARŞİV' : strtoupper((string) ($profile['backup_status'] ?? 'active'))],
        ['Son snapshot', (string) ($snapshot['generated_at'] ?? '')],
        ['Not', 'Google Sheet tek yönlü yedektir; ana veri kaynağı web sitesinin MySQL veritabanıdır.'],
        [],
        ['PROFİL'],
        ['Alan', 'Değer'],
        ['Ad Soyad', (string) ($profile['full_name'] ?? '')],
        ['E-posta', (string) ($profile['email'] ?? '')],
        ['İşe Giriş Tarihi', (string) ($profile['hire_date'] ?? '')],
        ['Doğum Tarihi', (string) ($profile['birth_date'] ?? '')],
        ['Hesap Aktif', !empty($profile['is_active']) ? 'Evet' : 'Hayır'],
        ['Silinme Tarihi', (string) ($profile['deleted_at'] ?? '')],
        [],
        ['YILLIK İZİN ÖZETİ'],
        ['Hak Edilmiş Toplam', (string) ($balance['earned_total'] ?? $balance['entitlement'] ?? '')],
        ['Onaylanan / Ayrılan', (string) ($balance['approved'] ?? $balance['approved_used'] ?? '')],
        ['Bekleyen', (string) ($balance['pending'] ?? '')],
        ['Kullanılabilir', (string) ($balance['available'] ?? $balance['available_after_pending'] ?? '')],
        [],
        ['HİZMET YILI HAK EDİŞLERİ'],
        ['Hizmet Yılı', 'Dönem Başlangıç', 'Dönem Bitiş', 'Hak Ediş Tarihi', 'Efektif Hak', 'Şirket Politikası', 'Yasal Taban', 'Yaş Tabanı'],
    ];

    foreach ((array) ($snapshot['entitlements'] ?? []) as $row) {
        $rows[] = [
            (string) ($row['service_year_number'] ?? ''),
            (string) ($row['service_period_start'] ?? ''),
            (string) ($row['service_period_end'] ?? ''),
            (string) ($row['earned_on'] ?? ''),
            (string) ($row['entitlement_days'] ?? ''),
            (string) ($row['company_policy_days'] ?? ''),
            (string) ($row['legal_minimum_days'] ?? ''),
            (string) ($row['age_minimum_days'] ?? ''),
        ];
    }

    $rows[] = [];
    $rows[] = ['İZİN GEÇMİŞİ'];
    $rows[] = ['Talep ID', 'Tür', 'Başlangıç', 'Bitiş', 'Süre', 'Gün', 'Durum', 'Belge', 'İşleyen', 'Oluşturulma', 'İşlenme'];

    foreach ((array) ($snapshot['leave_requests'] ?? []) as $row) {
        $rows[] = [
            (string) ($row['id'] ?? ''),
            (string) ($row['leave_type_name'] ?? ''),
            (string) ($row['start_date'] ?? ''),
            (string) ($row['end_date'] ?? ''),
            (string) ($row['duration_type'] ?? ''),
            (string) ($row['requested_days'] ?? ''),
            (string) ($row['status'] ?? ''),
            ((int) ($row['has_attachment'] ?? 0) === 1) ? 'Var' : 'Yok',
            (string) ($row['processed_by_name'] ?? ''),
            (string) ($row['created_at'] ?? ''),
            (string) ($row['processed_at'] ?? ''),
        ];
    }

    $rows[] = [];
    $rows[] = ['GİZLİLİK'];
    $rows[] = ['Şifre / hash', 'Google Sheet’e aktarılmaz'];
    $rows[] = ['Tıbbi belge dosyası', 'Google Sheet’e aktarılmaz; yalnızca Belge=Var/Yok'];
    $rows[] = ['Serbest metin açıklama / yönetici notu', 'Varsayılan olarak Google Sheet’e aktarılmaz'];

    return $rows;
}

function google_sheets_backup_google_write_employee(array $snapshot): array
{
    $profile = is_array($snapshot['profile'] ?? null) ? $snapshot['profile'] : [];
    $employeeRef = (string) ($snapshot['employee_ref'] ?? '');
    $name = trim((string) ($profile['full_name'] ?? 'Çalışan'));
    $deleted = (($profile['backup_status'] ?? '') === 'deleted');

    $title = ($deleted ? 'DELETED - ' : '') . $employeeRef . ' - ' . $name;
    $sheet = google_sheets_backup_ensure_sheet($title, $employeeRef);
    google_sheets_backup_write_rows((string) $sheet['title'], google_sheets_backup_employee_rows($snapshot));

    return $sheet;
}

function google_sheets_backup_registry_upsert(PDO $pdo, array $snapshot, array $sheet, string $eventUuid): void
{
    $profile = is_array($snapshot['profile'] ?? null) ? $snapshot['profile'] : [];
    $status = (($profile['backup_status'] ?? '') === 'deleted')
        ? 'deleted'
        : (!empty($profile['is_active']) ? 'active' : 'inactive');

    $deletedAt = null;
    if ($status === 'deleted') {
        $rawDeletedAt = trim((string) ($profile['deleted_at'] ?? ''));
        try {
            $deletedAt = $rawDeletedAt !== ''
                ? (new DateTimeImmutable($rawDeletedAt))->format('Y-m-d H:i:s')
                : date('Y-m-d H:i:s');
        } catch (Throwable) {
            $deletedAt = date('Y-m-d H:i:s');
        }
    }

    $stmt = $pdo->prepare(
        "INSERT INTO google_sheet_backup_registry
         (employee_ref, user_id, display_name, email, status, sheet_id, sheet_title,
          last_synced_at, last_event_uuid, deleted_at)
         VALUES
         (:employee_ref, :user_id, :display_name, :email, :status, :sheet_id, :sheet_title,
          NOW(), :last_event_uuid, :deleted_at)
         ON DUPLICATE KEY UPDATE
             user_id = VALUES(user_id),
             display_name = VALUES(display_name),
             email = VALUES(email),
             status = VALUES(status),
             sheet_id = VALUES(sheet_id),
             sheet_title = VALUES(sheet_title),
             last_synced_at = NOW(),
             last_event_uuid = VALUES(last_event_uuid),
             deleted_at = VALUES(deleted_at)"
    );
    $stmt->execute([
        'employee_ref' => (string) ($snapshot['employee_ref'] ?? ''),
        'user_id' => isset($profile['user_id']) ? (int) $profile['user_id'] : null,
        'display_name' => (string) ($profile['full_name'] ?? ''),
        'email' => (string) ($profile['email'] ?? ''),
        'status' => $status,
        'sheet_id' => (int) ($sheet['sheet_id'] ?? 0),
        'sheet_title' => (string) ($sheet['title'] ?? ''),
        'last_event_uuid' => $eventUuid,
        'deleted_at' => $deletedAt,
    ]);
}

function google_sheets_backup_refresh_system_sheets(PDO $pdo): void
{
    $index = google_sheets_backup_ensure_sheet('_INDEX');
    $indexRows = [[
        'Employee Ref', 'Ad Soyad', 'E-posta', 'Durum', 'Sheet', 'Son Sync', 'Silinme Tarihi'
    ]];
    foreach ($pdo->query(
        "SELECT employee_ref, display_name, email, status, sheet_title, last_synced_at, deleted_at
         FROM google_sheet_backup_registry
         ORDER BY status='deleted' ASC, display_name ASC, employee_ref ASC"
    )->fetchAll() as $row) {
        $indexRows[] = [
            (string) $row['employee_ref'],
            (string) $row['display_name'],
            (string) $row['email'],
            (string) $row['status'],
            (string) $row['sheet_title'],
            (string) $row['last_synced_at'],
            (string) ($row['deleted_at'] ?? ''),
        ];
    }
    google_sheets_backup_write_rows((string) $index['title'], $indexRows);

    $events = google_sheets_backup_ensure_sheet('_EVENTS');
    $eventRows = [['Event UUID', 'Employee Ref', 'Event Type', 'Status', 'Attempts', 'Created At', 'Processed At']];
    foreach ($pdo->query(
        "SELECT event_uuid, employee_ref, event_type, status, attempts, created_at, processed_at
         FROM google_sheet_sync_queue
         WHERE status IN ('succeeded','processing')
         ORDER BY id ASC
         LIMIT 10000"
    )->fetchAll() as $row) {
        $eventRows[] = [
            (string) $row['event_uuid'],
            (string) $row['employee_ref'],
            (string) $row['event_type'],
            (string) $row['status'],
            (string) $row['attempts'],
            (string) $row['created_at'],
            (string) ($row['processed_at'] ?? ''),
        ];
    }
    google_sheets_backup_write_rows((string) $events['title'], $eventRows);

    $sync = google_sheets_backup_ensure_sheet('_SYNC_LOG');
    $syncRows = [['Event UUID', 'Employee Ref', 'Status', 'Attempts', 'Last Error', 'Updated At']];
    foreach ($pdo->query(
        "SELECT event_uuid, employee_ref, status, attempts, last_error, updated_at
         FROM google_sheet_sync_queue
         ORDER BY id DESC
         LIMIT 500"
    )->fetchAll() as $row) {
        $syncRows[] = [
            (string) $row['event_uuid'],
            (string) $row['employee_ref'],
            (string) $row['status'],
            (string) $row['attempts'],
            (string) ($row['last_error'] ?? ''),
            (string) $row['updated_at'],
        ];
    }
    google_sheets_backup_write_rows((string) $sync['title'], $syncRows);
}

function google_sheets_backup_safe_error(Throwable $e): string
{
    $message = trim($e->getMessage());
    $message = preg_replace('/Bearer\s+[A-Za-z0-9._-]+/i', 'Bearer [REDACTED]', $message) ?? $message;
    return mb_substr($message !== '' ? $message : 'Bilinmeyen senkronizasyon hatası.', 0, 500);
}

function google_sheets_backup_process_queue(PDO $pdo, int $limit = 20, ?callable $writer = null): array
{
    if (!google_sheets_backup_tables_ready($pdo)) {
        throw new RuntimeException('Google Sheets yedek tabloları henüz kurulmamış.');
    }

    $limit = max(1, min(100, $limit));

    if ($writer === null && !google_sheets_backup_enabled()) {
        throw new RuntimeException('Google Sheets yedeği etkin değil veya private kimlik dosyası eksik.');
    }

    $lockName = 'izin_google_sheets_backup_worker';
    $lockStmt = $pdo->prepare('SELECT GET_LOCK(:lock_name, 0)');
    $lockStmt->execute(['lock_name' => $lockName]);
    if ((int) $lockStmt->fetchColumn() !== 1) {
        return ['processed' => 0, 'succeeded' => 0, 'failed' => 0, 'locked' => true];
    }

    $result = ['processed' => 0, 'succeeded' => 0, 'failed' => 0, 'locked' => false];

    try {
        $pdo->exec(
            "UPDATE google_sheet_sync_queue
             SET status='pending', locked_at=NULL
             WHERE status='processing'
               AND locked_at < DATE_SUB(NOW(), INTERVAL 15 MINUTE)"
        );

        $rows = $pdo->query(
            "SELECT id, event_uuid, employee_ref, event_type, payload_json, attempts
             FROM google_sheet_sync_queue
             WHERE status IN ('pending','failed')
               AND attempts < 5
               AND available_at <= NOW()
             ORDER BY id ASC
             LIMIT {$limit}"
        )->fetchAll();

        foreach ($rows as $row) {
            $result['processed']++;
            $id = (int) $row['id'];
            $attempts = (int) $row['attempts'];

            $claim = $pdo->prepare(
                "UPDATE google_sheet_sync_queue
                 SET status='processing', locked_at=NOW(), attempts=attempts+1, updated_at=NOW()
                 WHERE id=:id AND status IN ('pending','failed')"
            );
            $claim->execute(['id' => $id]);
            if ($claim->rowCount() !== 1) {
                continue;
            }

            try {
                $snapshot = json_decode((string) $row['payload_json'], true, 512, JSON_THROW_ON_ERROR);
                if (!is_array($snapshot)) {
                    throw new RuntimeException('Yedek payload verisi geçersiz.');
                }

                $sheet = $writer !== null
                    ? $writer($pdo, $row, $snapshot)
                    : google_sheets_backup_google_write_employee($snapshot);

                if (!is_array($sheet)) {
                    throw new RuntimeException('Google Sheet writer sonucu geçersiz.');
                }

                google_sheets_backup_registry_upsert(
                    $pdo,
                    $snapshot,
                    $sheet,
                    (string) $row['event_uuid']
                );

                if ($writer === null) {
                    google_sheets_backup_refresh_system_sheets($pdo);
                }

                $done = $pdo->prepare(
                    "UPDATE google_sheet_sync_queue
                     SET status='succeeded', processed_at=NOW(), locked_at=NULL,
                         last_error=NULL, updated_at=NOW()
                     WHERE id=:id"
                );
                $done->execute(['id' => $id]);
                $result['succeeded']++;
            } catch (Throwable $e) {
                $delayMinutes = min(60, 2 ** min(5, $attempts + 1));
                $availableAt = (new DateTimeImmutable('now'))
                    ->modify('+' . $delayMinutes . ' minutes')
                    ->format('Y-m-d H:i:s');

                $failed = $pdo->prepare(
                    "UPDATE google_sheet_sync_queue
                     SET status='failed', available_at=:available_at, locked_at=NULL,
                         last_error=:last_error, updated_at=NOW()
                     WHERE id=:id"
                );
                $failed->execute([
                    'available_at' => $availableAt,
                    'last_error' => google_sheets_backup_safe_error($e),
                    'id' => $id,
                ]);
                error_log('[google-sheets-backup] sync failed: ' . google_sheets_backup_safe_error($e));
                $result['failed']++;
            }
        }
    } finally {
        $release = $pdo->prepare('SELECT RELEASE_LOCK(:lock_name)');
        $release->execute(['lock_name' => $lockName]);
    }

    return $result;
}
