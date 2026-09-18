<?php

declare(strict_types=1);

function holiday_import_normalize_header(string $value): string
{
    $value = mb_strtolower(trim($value));
    $value = str_replace([' ', '-', '.', 'ı', 'ğ', 'ü', 'ş', 'ö', 'ç'], ['_', '_', '_', 'i', 'g', 'u', 's', 'o', 'c'], $value);
    return preg_replace('/[^a-z0-9_]/', '', $value) ?? '';
}

function holiday_import_bool(mixed $value): bool
{
    if (is_bool($value)) {
        return $value;
    }

    return in_array(
        mb_strtolower(trim((string) $value)),
        ['1', 'true', 'yes', 'evet', 'e', 'yarim', 'yarım'],
        true
    );
}

function holiday_import_period(mixed $value): ?string
{
    $value = mb_strtolower(trim((string) $value));
    if ($value === '') {
        return null;
    }

    if (in_array($value, ['morning', 'sabah'], true)) {
        return 'morning';
    }

    if (in_array($value, ['afternoon', 'ogle', 'öğle', 'ogleden_sonra', 'öğleden sonra', 'ogle sonrası'], true)) {
        return 'afternoon';
    }

    return null;
}

function holiday_import_normalize_row(array $row, int $rowNumber): array
{
    $normalized = [];
    foreach ($row as $key => $value) {
        $normalized[holiday_import_normalize_header((string) $key)] = $value;
    }

    $date = trim((string) (
        $normalized['holiday_date']
        ?? $normalized['date']
        ?? $normalized['tarih']
        ?? ''
    ));
    $name = trim((string) (
        $normalized['name']
        ?? $normalized['ad']
        ?? $normalized['tatil_adi']
        ?? ''
    ));
    $isHalfDay = holiday_import_bool(
        $normalized['is_half_day']
        ?? $normalized['half_day']
        ?? $normalized['yarim_gun']
        ?? false
    );
    $period = holiday_import_period(
        $normalized['half_day_period']
        ?? $normalized['period']
        ?? $normalized['yarim_gun_donemi']
        ?? ''
    );
    $sourceUid = trim((string) (
        $normalized['source_uid']
        ?? $normalized['source_id']
        ?? $normalized['kaynak_id']
        ?? ''
    ));

    if (parse_leave_date($date) === null || $name === '') {
        throw new DomainException("İçe aktarma satırı {$rowNumber}: tarih veya ad geçersiz.");
    }

    if ($isHalfDay && $period === null) {
        throw new DomainException("İçe aktarma satırı {$rowNumber}: yarım gün için sabah/öğleden sonra dönemi gerekli.");
    }

    return [
        'holiday_date' => $date,
        'name' => mb_substr($name, 0, 150),
        'is_half_day' => $isHalfDay ? 1 : 0,
        'half_day_period' => $isHalfDay ? $period : null,
        'source_uid' => $sourceUid !== '' ? mb_substr($sourceUid, 0, 191) : null,
    ];
}

function holiday_import_parse_uploaded_file(array $file): array
{
    $error = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
    if ($error !== UPLOAD_ERR_OK) {
        throw new DomainException('CSV veya JSON tatil dosyası seçin.');
    }

    $size = (int) ($file['size'] ?? 0);
    if ($size <= 0 || $size > 2 * 1024 * 1024) {
        throw new DomainException('Tatil import dosyası 2 MB sınırını aşamaz.');
    }

    $tmp = (string) ($file['tmp_name'] ?? '');
    $name = (string) ($file['name'] ?? '');
    $extension = mb_strtolower(pathinfo($name, PATHINFO_EXTENSION));
    $contents = is_file($tmp) ? file_get_contents($tmp) : false;

    if (!is_string($contents) || trim($contents) === '') {
        throw new DomainException('Tatil import dosyası okunamadı.');
    }

    $rows = [];

    if ($extension === 'json') {
        try {
            $decoded = json_decode($contents, true, 64, JSON_THROW_ON_ERROR);
        } catch (Throwable) {
            throw new DomainException('JSON dosyası geçerli değil.');
        }

        if (!is_array($decoded)) {
            throw new DomainException('JSON kök değeri kayıt listesi olmalıdır.');
        }

        foreach (array_values($decoded) as $index => $row) {
            if (!is_array($row)) {
                throw new DomainException('JSON içindeki her kayıt nesne olmalıdır.');
            }
            $rows[] = holiday_import_normalize_row($row, $index + 1);
        }
    } elseif ($extension === 'csv') {
        $lines = preg_split('/\R/u', trim($contents)) ?: [];
        if (count($lines) < 2) {
            throw new DomainException('CSV dosyasında başlık ve en az bir kayıt olmalıdır.');
        }

        $first = (string) array_shift($lines);
        $delimiter = substr_count($first, ';') > substr_count($first, ',') ? ';' : ',';
        $headers = str_getcsv($first, $delimiter);

        foreach ($lines as $index => $line) {
            if (trim((string) $line) === '') {
                continue;
            }
            $values = str_getcsv((string) $line, $delimiter);
            $row = [];
            foreach ($headers as $column => $header) {
                $row[(string) $header] = $values[$column] ?? '';
            }
            $rows[] = holiday_import_normalize_row($row, $index + 2);
        }
    } else {
        throw new DomainException('Yalnızca .csv veya .json tatil dosyaları desteklenir.');
    }

    if ($rows === []) {
        throw new DomainException('Import dosyasında geçerli tatil kaydı yok.');
    }

    $seen = [];
    foreach ($rows as $row) {
        $date = (string) $row['holiday_date'];
        if (isset($seen[$date])) {
            throw new DomainException("Import dosyasında {$date} tarihi birden fazla kez bulunuyor.");
        }
        $seen[$date] = true;
    }

    return $rows;
}

function import_public_holiday_rows(PDO $pdo, array $rows, ?int $actorUserId, string $sourceLabel = 'import'): array
{
    $created = 0;
    $updated = 0;
    $unchanged = 0;
    $changedDates = [];

    $select = $pdo->prepare(
        'SELECT id, name, is_half_day, half_day_period, source_type, source_uid
         FROM public_holidays
         WHERE holiday_date=:holiday_date
         LIMIT 1'
    );
    $insert = $pdo->prepare(
        'INSERT INTO public_holidays
         (holiday_date, name, holiday_year, is_half_day, half_day_period, source_type, source_uid, imported_at)
         VALUES
         (:holiday_date, :name, :holiday_year, :is_half_day, :half_day_period, :source_type, :source_uid, NOW())'
    );
    $update = $pdo->prepare(
        'UPDATE public_holidays
         SET name=:name,
             holiday_year=:holiday_year,
             is_half_day=:is_half_day,
             half_day_period=:half_day_period,
             source_type=:source_type,
             source_uid=:source_uid,
             imported_at=NOW()
         WHERE id=:id'
    );

    $pdo->beginTransaction();

    try {
        foreach ($rows as $index => $rawRow) {
            $row = holiday_import_normalize_row($rawRow, $index + 1);
            $date = (string) $row['holiday_date'];

            $select->execute(['holiday_date' => $date]);
            $existing = $select->fetch();

            $params = [
                'holiday_date' => $date,
                'name' => (string) $row['name'],
                'holiday_year' => (int) substr($date, 0, 4),
                'is_half_day' => (int) $row['is_half_day'],
                'half_day_period' => $row['half_day_period'],
                'source_type' => mb_substr($sourceLabel, 0, 20),
                'source_uid' => $row['source_uid'],
            ];

            if (!$existing) {
                $insert->execute($params);
                $created++;
                $changedDates[] = $date;
                continue;
            }

            $same = (string) $existing['name'] === (string) $row['name']
                && (int) $existing['is_half_day'] === (int) $row['is_half_day']
                && (($existing['half_day_period'] ?: null) === $row['half_day_period']);

            if ($same) {
                $unchanged++;
                continue;
            }

            unset($params['holiday_date']);
            $params['id'] = (int) $existing['id'];
            $update->execute($params);
            $updated++;
            $changedDates[] = $date;
        }

        $affectedPending = 0;
        if ($changedDates !== []) {
            $conditions = [];
            $params = [];
            foreach ($changedDates as $date) {
                $conditions[] = '(lr.start_date <= ? AND lr.end_date >= ?)';
                $params[] = $date;
                $params[] = $date;
            }

            $stmt = $pdo->prepare(
                "SELECT COUNT(DISTINCT lr.id)
                 FROM leave_requests lr
                 WHERE lr.status='pending'
                   AND (" . implode(' OR ', $conditions) . ")"
            );
            $stmt->execute($params);
            $affectedPending = (int) $stmt->fetchColumn();
        }

        audit_log_event(
            $pdo,
            $actorUserId,
            'public_holidays_imported',
            'public_holiday_import',
            date('YmdHis'),
            [
                'created' => $created,
                'updated' => $updated,
                'unchanged' => $unchanged,
                'affected_pending_requests' => $affectedPending,
            ]
        );

        $pdo->commit();

        return [
            'created' => $created,
            'updated' => $updated,
            'unchanged' => $unchanged,
            'affected_pending_requests' => $affectedPending,
        ];
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}
