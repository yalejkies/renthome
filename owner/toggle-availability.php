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
$availability = (string)($_POST['availability'] ?? '');

if (!$propertyId || !in_array($availability, ['available', 'unavailable'], true)) {
    http_response_code(400);
    exit('Invalid listing availability.');
}

$stmt = $pdo->prepare(
    "UPDATE properties SET availability_status = ? WHERE id = ? AND owner_id = ? AND status = 'approved'"
);
$stmt->execute([$availability, $propertyId, $_SESSION['user_id']]);

header('Location: dashboard.php?availability=' . urlencode($availability));
exit;
