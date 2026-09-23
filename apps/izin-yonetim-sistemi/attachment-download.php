<?php

declare(strict_types=1);

require_once __DIR__ . '/app/bootstrap.php';

$user = require_login();
$attachmentId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$attachmentId) {
    http_response_code(404);
    exit('Dosya bulunamadı.');
}

$stmt = db()->prepare(
    'SELECT la.id, la.original_name, la.stored_name, la.mime_type, la.size_bytes, la.sha256,
            lr.user_id
     FROM leave_attachments la
     INNER JOIN leave_requests lr ON lr.id = la.leave_request_id
     WHERE la.id = :id
     LIMIT 1'
);
$stmt->execute(['id' => (int) $attachmentId]);
$attachment = $stmt->fetch();

if (!$attachment) {
    http_response_code(404);
    exit('Dosya bulunamadı.');
}

$isAdmin = ($user['role'] ?? '') === 'admin';
$isOwner = (int) $attachment['user_id'] === (int) $user['id'];

if (!$isAdmin && !$isOwner) {
    http_response_code(403);
    exit('Bu dosyaya erişim yetkiniz yok.');
}

$storedName = (string) $attachment['stored_name'];
$path = attachment_file_path($storedName);

if (!is_file($path) || !is_readable($path)) {
    error_log('Attachment file missing for attachment id ' . (int) $attachmentId);
    http_response_code(404);
    exit('Dosya bulunamadı.');
}

$actualHash = hash_file('sha256', $path);
if (!is_string($actualHash) || !hash_equals((string) $attachment['sha256'], $actualHash)) {
    error_log('Attachment integrity mismatch for attachment id ' . (int) $attachmentId);
    http_response_code(409);
    exit('Dosya bütünlük kontrolü başarısız.');
}

$mime = (string) $attachment['mime_type'];
if (!array_key_exists($mime, attachment_allowed_mimes())) {
    http_response_code(415);
    exit('Dosya türü desteklenmiyor.');
}

$originalName = (string) $attachment['original_name'];
$encodedName = rawurlencode($originalName);
$fallbackName = 'izin-belgesi-' . (int) $attachmentId;

header('Content-Type: ' . $mime);
header('Content-Length: ' . (string) filesize($path));
header('Content-Disposition: attachment; filename="' . $fallbackName . '"; filename*=UTF-8\'\'' . $encodedName);
header('X-Content-Type-Options: nosniff');
header('Cache-Control: private, no-store, max-age=0');

if (session_status() === PHP_SESSION_ACTIVE) {
    session_write_close();
}

readfile($path);
exit;
