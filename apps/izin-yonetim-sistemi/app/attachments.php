<?php

declare(strict_types=1);

function attachment_storage_dir(): string
{
    $storage = app_config('storage');
    $configured = is_array($storage) ? trim((string) ($storage['attachments_dir'] ?? '')) : '';

    if ($configured !== '') {
        return rtrim($configured, '/\\');
    }

    $documentRoot = $_SERVER['DOCUMENT_ROOT'] ?? dirname(__DIR__, 2);
    return dirname(rtrim((string) $documentRoot, '/\\')) . '/izin-private/attachments';
}

function attachment_max_bytes(): int
{
    $mb = (float) app_setting('attachment_max_mb', '10');
    $mb = max(1.0, min(50.0, $mb));

    return (int) round($mb * 1024 * 1024);
}

function attachment_allowed_mimes(): array
{
    return [
        'application/pdf' => 'PDF',
        'image/jpeg' => 'JPEG',
        'image/png' => 'PNG',
    ];
}

function ensure_attachment_storage(): string
{
    $dir = attachment_storage_dir();
    $documentRoot = realpath((string) ($_SERVER['DOCUMENT_ROOT'] ?? ''));
    $existingDir = realpath($dir);

    if ($existingDir !== false && $documentRoot !== false) {
        $normalizedDir = rtrim(str_replace('\\', '/', $existingDir), '/') . '/';
        $normalizedRoot = rtrim(str_replace('\\', '/', $documentRoot), '/') . '/';

        if (str_starts_with($normalizedDir, $normalizedRoot)) {
            throw new RuntimeException('Dosya depolama alanı public web root dışında olmalıdır.');
        }
    }

    if (!is_dir($dir) && !mkdir($dir, 0700, true) && !is_dir($dir)) {
        throw new RuntimeException('Özel dosya depolama klasörü oluşturulamadı.');
    }

    if (!is_writable($dir)) {
        throw new RuntimeException('Özel dosya depolama klasörü yazılabilir değil.');
    }

    @chmod($dir, 0700);

    return $dir;
}

function uploaded_file_present(?array $file): bool
{
    if (!is_array($file)) {
        return false;
    }

    return (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;
}

function prepare_leave_attachment(array $file): array
{
    $error = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);

    if ($error === UPLOAD_ERR_NO_FILE) {
        throw new DomainException('Dosya seçilmedi.');
    }

    if ($error !== UPLOAD_ERR_OK) {
        throw new DomainException('Dosya yüklenemedi. Lütfen tekrar deneyin.');
    }

    $tmpName = (string) ($file['tmp_name'] ?? '');
    $size = (int) ($file['size'] ?? 0);
    $originalName = trim((string) ($file['name'] ?? 'dosya'));

    if ($tmpName === '' || !is_uploaded_file($tmpName) || $size <= 0) {
        throw new DomainException('Geçerli bir dosya yükleyin.');
    }

    if ($size > attachment_max_bytes()) {
        $maxMb = attachment_max_bytes() / 1024 / 1024;
        throw new DomainException('Dosya boyutu en fazla ' . format_days($maxMb) . ' MB olabilir.');
    }

    if (!class_exists('finfo')) {
        throw new RuntimeException('Sunucuda fileinfo eklentisi etkin değil.');
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = (string) $finfo->file($tmpName);
    $allowed = attachment_allowed_mimes();

    if (!isset($allowed[$mime])) {
        throw new DomainException('Yalnızca PDF, JPEG veya PNG dosyaları yüklenebilir.');
    }

    $safeOriginalName = mb_substr(basename(str_replace('\\', '/', $originalName)), 0, 255);
    if ($safeOriginalName === '') {
        $safeOriginalName = 'dosya';
    }

    $storedName = bin2hex(random_bytes(32));
    $dir = ensure_attachment_storage();
    $destination = $dir . DIRECTORY_SEPARATOR . $storedName;

    if (!move_uploaded_file($tmpName, $destination)) {
        throw new RuntimeException('Dosya güvenli depolama alanına taşınamadı.');
    }

    @chmod($destination, 0600);

    $sha256 = hash_file('sha256', $destination);
    if (!is_string($sha256) || $sha256 === '') {
        @unlink($destination);
        throw new RuntimeException('Dosya bütünlük özeti oluşturulamadı.');
    }

    return [
        'original_name' => $safeOriginalName,
        'stored_name' => $storedName,
        'mime_type' => $mime,
        'size_bytes' => $size,
        'sha256' => $sha256,
        'path' => $destination,
    ];
}

function cleanup_prepared_attachment(?array $attachment): void
{
    if (!is_array($attachment)) {
        return;
    }

    $path = (string) ($attachment['path'] ?? '');
    if ($path !== '' && is_file($path)) {
        @unlink($path);
    }
}

function attachment_file_path(string $storedName): string
{
    if (!preg_match('/^[a-f0-9]{64}$/', $storedName)) {
        throw new InvalidArgumentException('Geçersiz dosya kimliği.');
    }

    return attachment_storage_dir() . DIRECTORY_SEPARATOR . $storedName;
}
