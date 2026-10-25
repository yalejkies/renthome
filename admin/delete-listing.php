<?php
declare(strict_types=1);

require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/csrf.php';

require_role('admin');

if (
    $_SERVER['REQUEST_METHOD'] !== 'POST' ||
    !verify_csrf($_POST['csrf_token'] ?? null)
) {
    http_response_code(400);
    exit('Invalid form request.');
}

$propertyId = filter_var($_POST['property_id'] ?? null, FILTER_VALIDATE_INT);
if (!$propertyId) {
    http_response_code(400);
    exit('Invalid listing.');
}

$stmt = $pdo->prepare('SELECT id FROM properties WHERE id = ? LIMIT 1');
$stmt->execute([$propertyId]);
if (!$stmt->fetch()) {
    http_response_code(404);
    exit('Listing not found.');
}

$imageStmt = $pdo->prepare('SELECT image_path FROM property_images WHERE property_id = ?');
$imageStmt->execute([$propertyId]);
$imagePaths = $imageStmt->fetchAll(PDO::FETCH_COLUMN);

try {
    $pdo->beginTransaction();

    $delete = $pdo->prepare('DELETE FROM properties WHERE id = ?');
    $delete->execute([$propertyId]);

    if ($delete->rowCount() !== 1) {
        throw new RuntimeException('Listing could not be deleted.');
    }

    $pdo->commit();

    foreach ($imagePaths as $relativePath) {
        $relativePath = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, (string)$relativePath);
        $file = dirname(__DIR__) . DIRECTORY_SEPARATOR . ltrim($relativePath, DIRECTORY_SEPARATOR);
        if (is_file($file)) {
            @unlink($file);
        }
    }

    header('Location: listings.php?deleted=1');
    exit;
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    exit('The listing could not be deleted. Please try again.');
}
