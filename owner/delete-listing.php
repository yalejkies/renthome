<?php
declare(strict_types=1);

require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/csrf.php';

require_role('owner');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verify_csrf($_POST['csrf_token'] ?? null)) {
    http_response_code(400);
    exit('Invalid form request.');
}

$propertyId = filter_var($_POST['property_id'] ?? null, FILTER_VALIDATE_INT);
if (!$propertyId) {
    http_response_code(400);
    exit('Invalid listing.');
}

// Fetch the owner's listing and its image paths first so uploaded files can be removed too.
$stmt = $pdo->prepare('SELECT id FROM properties WHERE id = ? AND owner_id = ? LIMIT 1');
$stmt->execute([$propertyId, $_SESSION['user_id']]);
if (!$stmt->fetch()) {
    http_response_code(404);
    exit('Listing not found.');
}

$imageStmt = $pdo->prepare('SELECT image_path FROM property_images WHERE property_id = ?');
$imageStmt->execute([$propertyId]);
$imagePaths = $imageStmt->fetchAll(PDO::FETCH_COLUMN);

try {
    $pdo->beginTransaction();

    // The database schema uses ON DELETE CASCADE for property images, inquiries, favorites, etc.
    $delete = $pdo->prepare('DELETE FROM properties WHERE id = ? AND owner_id = ?');
    $delete->execute([$propertyId, $_SESSION['user_id']]);

    if ($delete->rowCount() !== 1) {
        throw new RuntimeException('Listing could not be deleted.');
    }

    $pdo->commit();

    // Remove the physical image files after the database deletion succeeds.
    foreach ($imagePaths as $relativePath) {
        $relativePath = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, (string)$relativePath);
        $file = dirname(__DIR__) . DIRECTORY_SEPARATOR . ltrim($relativePath, DIRECTORY_SEPARATOR);
        if (is_file($file)) {
            @unlink($file);
        }
    }

    header('Location: dashboard.php?deleted=1');
    exit;
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    exit('The listing could not be deleted. Please try again.');
}
