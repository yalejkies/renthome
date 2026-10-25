<?php
declare(strict_types=1);

require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/csrf.php';
require_once '../includes/renewal.php';
ensure_renewal_schema($pdo);
require_once '../includes/notifications.php';
require_role('owner');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: dashboard.php');
    exit;
}

if (!verify_csrf($_POST['csrf_token'] ?? '')) {
    http_response_code(419);
    exit('Invalid request token. Please go back and try again.');
}

$ownerId = (int)$_SESSION['user_id'];
$propertyId = filter_var($_POST['property_id'] ?? '', FILTER_VALIDATE_INT);
$method = trim((string)($_POST['payment_method'] ?? ''));
$reference = trim((string)($_POST['reference_number'] ?? ''));
$note = trim((string)($_POST['owner_note'] ?? ''));

if (!$propertyId || !in_array($method, ['gcash', 'bank_transfer'], true)) {
    header('Location: dashboard.php');
    exit;
}

if ($reference === '' || strlen($reference) > 100) {
    header('Location: renew.php?id=' . $propertyId . '&error=' . rawurlencode('Please provide a valid payment reference number.'));
    exit;
}

if (strlen($note) > 500) {
    header('Location: renew.php?id=' . $propertyId . '&error=' . rawurlencode('Your note is too long.'));
    exit;
}

$stmt = $pdo->prepare(
    'SELECT id, title, status FROM properties WHERE id = ? AND owner_id = ? LIMIT 1'
);
$stmt->execute([$propertyId, $ownerId]);
$property = $stmt->fetch();
if (!$property || $property['status'] !== 'approved') {
    header('Location: dashboard.php');
    exit;
}

$pendingStmt = $pdo->prepare("SELECT id FROM renewal_payments WHERE property_id = ? AND owner_id = ? AND status = 'pending' LIMIT 1");
$pendingStmt->execute([$propertyId, $ownerId]);
if ($pendingStmt->fetch()) {
    header('Location: renew.php?id=' . $propertyId . '&error=' . rawurlencode('A renewal payment for this listing is already pending verification.'));
    exit;
}

if (!isset($_FILES['proof']) || !is_array($_FILES['proof'])) {
    header('Location: renew.php?id=' . $propertyId . '&error=' . rawurlencode('Please upload your payment proof.'));
    exit;
}

$file = $_FILES['proof'];
if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
    header('Location: renew.php?id=' . $propertyId . '&error=' . rawurlencode('The payment proof could not be uploaded.'));
    exit;
}

if ((int)$file['size'] > MAX_UPLOAD_SIZE) {
    header('Location: renew.php?id=' . $propertyId . '&error=' . rawurlencode('Payment proof must not exceed 5 MB.'));
    exit;
}

$tmp = (string)$file['tmp_name'];
$finfo = new finfo(FILEINFO_MIME_TYPE);
$mime = $finfo->file($tmp);
$allowed = [
    'image/jpeg' => 'jpg',
    'image/png' => 'png',
    'application/pdf' => 'pdf',
];
if (!isset($allowed[$mime])) {
    header('Location: renew.php?id=' . $propertyId . '&error=' . rawurlencode('Only JPG, PNG, or PDF payment proof is allowed.'));
    exit;
}

$uploadDir = dirname(__DIR__) . '/uploads/payment_proofs';
if (!is_dir($uploadDir) && !mkdir($uploadDir, 0750, true) && !is_dir($uploadDir)) {
    header('Location: renew.php?id=' . $propertyId . '&error=' . rawurlencode('The payment upload folder could not be created.'));
    exit;
}

$filename = 'renewal_' . $ownerId . '_' . $propertyId . '_' . bin2hex(random_bytes(12)) . '.' . $allowed[$mime];
$destination = $uploadDir . DIRECTORY_SEPARATOR . $filename;
if (!move_uploaded_file($tmp, $destination)) {
    header('Location: renew.php?id=' . $propertyId . '&error=' . rawurlencode('The payment proof could not be saved.'));
    exit;
}

$relativePath = 'uploads/payment_proofs/' . $filename;

try {
    $pdo->beginTransaction();
    $insert = $pdo->prepare(
        'INSERT INTO renewal_payments
            (owner_id, property_id, amount, payment_method, reference_number, proof_path, status, owner_note)
         VALUES (?, ?, ?, ?, ?, ?, \'pending\', ?)'
    );
    $insert->execute([$ownerId, $propertyId, renewal_fee(), $method, $reference, $relativePath, $note !== '' ? $note : null]);

    create_notification(
        $pdo,
        $ownerId,
        'Renewal payment submitted',
        'Your renewal payment proof for "' . $property['title'] . '" was submitted and is waiting for administrator verification.',
        'owner/dashboard.php'
    );
    $pdo->commit();
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    @unlink($destination);
    header('Location: renew.php?id=' . $propertyId . '&error=' . rawurlencode('The payment submission could not be completed. Please try again.'));
    exit;
}

header('Location: renew.php?id=' . $propertyId . '&submitted=1');
exit;
