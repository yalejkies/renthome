<?php
declare(strict_types=1);

require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/csrf.php';
require_once '../includes/renewal.php';
require_once '../includes/notifications.php';
require_once '../includes/audit.php';
ensure_renewal_schema($pdo);
ensure_audit_schema($pdo);
require_role('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verify_csrf($_POST['csrf_token'] ?? '')) {
    http_response_code(419); exit('Invalid form request.');
}

$paymentId = filter_var($_POST['payment_id'] ?? null, FILTER_VALIDATE_INT);
$action = (string)($_POST['action'] ?? '');
$adminNote = trim((string)($_POST['admin_note'] ?? ''));
if (!$paymentId || !in_array($action, ['approve', 'reject'], true)) { http_response_code(400); exit('Invalid renewal request.'); }
if (strlen($adminNote) > 500) { header('Location: renewals.php?status=pending&error=' . rawurlencode('Administrator note is too long.')); exit; }

$stmt = $pdo->prepare(
    'SELECT rp.*, p.title, p.listing_expires_at, p.status AS property_status
     FROM renewal_payments rp JOIN properties p ON p.id = rp.property_id
     WHERE rp.id = ? LIMIT 1'
);
$stmt->execute([$paymentId]);
$payment = $stmt->fetch();
if (!$payment || $payment['status'] !== 'pending') {
    header('Location: renewals.php?status=pending&error=' . rawurlencode('This renewal is no longer pending.')); exit;
}

try {
    $pdo->beginTransaction();
    if ($action === 'approve') {
        // Extend from the later of the current expiry or now, so an early renewal never loses remaining validity.
        $update = $pdo->prepare(
            'UPDATE properties
             SET listing_expires_at = DATE_ADD(GREATEST(COALESCE(listing_expires_at, NOW()), NOW()), INTERVAL 3 MONTH),
                 last_renewed_at = NOW(),
                 renewal_count = renewal_count + 1
             WHERE id = ?'
        );
        $update->execute([(int)$payment['property_id']]);
        if ($update->rowCount() !== 1) { throw new RuntimeException('Listing could not be extended.'); }

        $pay = $pdo->prepare('UPDATE renewal_payments SET status = \'paid\', admin_note = ?, verified_at = NOW(), verified_by = ? WHERE id = ? AND status = \'pending\'');
        $pay->execute([$adminNote !== '' ? $adminNote : null, (int)$_SESSION['user_id'], $paymentId]);
        if ($pay->rowCount() !== 1) { throw new RuntimeException('Renewal status could not be updated.'); }

        log_admin_action($pdo, (int)$_SESSION['user_id'], 'Approved renewal payment', 'renewal_payment', $paymentId, 'Property #' . (int)$payment['property_id'] . ' extended by 3 months.');
        create_notification($pdo, (int)$payment['owner_id'], 'Renewal approved', 'Your renewal payment for "' . $payment['title'] . '" was verified. Your listing has been extended for another 3 months.', 'owner/renew.php?id=' . (int)$payment['property_id']);
        $pdo->commit();
        header('Location: renewals.php?status=pending&updated=paid'); exit;
    }

    $pay = $pdo->prepare('UPDATE renewal_payments SET status = \'rejected\', admin_note = ?, verified_at = NOW(), verified_by = ? WHERE id = ? AND status = \'pending\'');
    $pay->execute([$adminNote !== '' ? $adminNote : null, (int)$_SESSION['user_id'], $paymentId]);
    if ($pay->rowCount() !== 1) { throw new RuntimeException('Renewal status could not be updated.'); }
    log_admin_action($pdo, (int)$_SESSION['user_id'], 'Rejected renewal payment', 'renewal_payment', $paymentId, 'Property #' . (int)$payment['property_id'] . ($adminNote !== '' ? ': ' . $adminNote : ''));
    create_notification($pdo, (int)$payment['owner_id'], 'Renewal payment rejected', 'Your renewal payment for "' . $payment['title'] . '" was rejected by the administrator.' . ($adminNote !== '' ? ' Note: ' . $adminNote : ''), 'owner/renew.php?id=' . (int)$payment['property_id']);
    $pdo->commit();
    header('Location: renewals.php?status=pending&updated=rejected'); exit;
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    header('Location: renewals.php?status=pending&error=' . rawurlencode('The renewal action could not be completed. Please try again.')); exit;
}
