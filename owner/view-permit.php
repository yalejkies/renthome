<?php
declare(strict_types=1);

require_once '../config/database.php';
require_once '../includes/auth.php';
require_role('owner');

$stmt = $pdo->prepare('SELECT mayor_permit_path FROM users WHERE id = ? AND role = \'owner\' LIMIT 1');
$stmt->execute([$_SESSION['user_id']]);
$path = (string)($stmt->fetchColumn() ?: '');

if ($path === '' || !str_starts_with($path, 'uploads/permits/')) {
    http_response_code(404);
    exit('Permit not found.');
}

$file = dirname(__DIR__) . '/' . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);
if (!is_file($file)) {
    http_response_code(404);
    exit('Permit file not found.');
}

$mime = (new finfo(FILEINFO_MIME_TYPE))->file($file) ?: 'application/octet-stream';
$allowed = ['application/pdf', 'image/jpeg', 'image/png'];
if (!in_array($mime, $allowed, true)) {
    http_response_code(415);
    exit('Unsupported permit file.');
}

header('Content-Type: ' . $mime);
header('Content-Disposition: inline; filename="mayors-permit"');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: private, no-store, max-age=0');
readfile($file);
exit;
