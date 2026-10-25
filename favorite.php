<?php
declare(strict_types=1);

require_once 'config/database.php';
require_once 'includes/auth.php';
require_once 'includes/csrf.php';

require_role('rent_seeker');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verify_csrf($_POST['csrf_token'] ?? null)) {
    http_response_code(400);
    exit('Invalid form request.');
}

$propertyId = filter_var($_POST['property_id'] ?? null, FILTER_VALIDATE_INT);
if (!$propertyId) {
    exit('Invalid property.');
}

$check = $pdo->prepare("SELECT id FROM properties WHERE id = ? AND status = 'approved' AND (listing_expires_at IS NULL OR listing_expires_at > NOW()) LIMIT 1");
$check->execute([$propertyId]);
if (!$check->fetch()) {
    http_response_code(404);
    exit('Property not found.');
}

$existing = $pdo->prepare('SELECT id FROM favorites WHERE user_id = ? AND property_id = ? LIMIT 1');
$existing->execute([$_SESSION['user_id'], $propertyId]);
$fav = $existing->fetch();

if ($fav) {
    $stmt = $pdo->prepare('DELETE FROM favorites WHERE id = ? AND user_id = ?');
    $stmt->execute([$fav['id'], $_SESSION['user_id']]);
} else {
    $stmt = $pdo->prepare('INSERT INTO favorites (user_id, property_id) VALUES (?, ?)');
    $stmt->execute([$_SESSION['user_id'], $propertyId]);
}

$back = $_SERVER['HTTP_REFERER'] ?? 'browse.php';
if (!str_contains($back, 'browse.php') && !str_contains($back, 'property.php')) {
    $back = 'browse.php';
}
header('Location: ' . $back);
exit;
