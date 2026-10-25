<?php
declare(strict_types=1);
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/renewal.php';
ensure_renewal_schema($pdo);
require_role('admin');

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) { http_response_code(400); exit('Invalid payment.'); }
$stmt = $pdo->prepare('SELECT proof_path FROM renewal_payments WHERE id = ? LIMIT 1');
$stmt->execute([$id]);
$payment = $stmt->fetch();
if (!$payment || empty($payment['proof_path'])) { http_response_code(404); exit('Payment proof not found.'); }

$root = realpath(dirname(__DIR__));
$file = realpath(dirname(__DIR__) . '/' . ltrim((string)$payment['proof_path'], '/'));
if (!$root || !$file || !str_starts_with($file, $root . DIRECTORY_SEPARATOR) || !is_file($file)) { http_response_code(404); exit('Payment proof file not found.'); }
$finfo = new finfo(FILEINFO_MIME_TYPE);
$mime = $finfo->file($file) ?: 'application/octet-stream';
$allowed = ['image/jpeg','image/png','application/pdf'];
if (!in_array($mime, $allowed, true)) { http_response_code(415); exit('Unsupported payment proof type.'); }
header('Content-Type: ' . $mime);
header('Content-Length: ' . filesize($file));
header('Content-Disposition: inline; filename="renewal-proof-' . $id . '"');
readfile($file);
exit;
