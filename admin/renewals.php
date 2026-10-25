<?php
declare(strict_types=1);

require_once '../config/database.php';
require_once '../config/app.php';
require_once '../includes/auth.php';
require_once '../includes/csrf.php';
require_once '../includes/renewal.php';
ensure_renewal_schema($pdo);
require_role('admin');

$status = $_GET['status'] ?? 'pending';
$allowed = ['pending', 'paid', 'rejected', 'all'];
if (!in_array($status, $allowed, true)) $status = 'pending';

$sql = "SELECT rp.*, p.title, p.location, p.listing_expires_at, u.full_name AS owner_name, u.email AS owner_email,
               admin.full_name AS verifier_name
        FROM renewal_payments rp
        JOIN properties p ON p.id = rp.property_id
        JOIN users u ON u.id = rp.owner_id
        LEFT JOIN users admin ON admin.id = rp.verified_by";
$params = [];
if ($status !== 'all') { $sql .= ' WHERE rp.status = ?'; $params[] = $status; }
$sql .= ' ORDER BY rp.created_at DESC';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$payments = $stmt->fetchAll();

$counts = [];
foreach (['pending','paid','rejected'] as $s) {
    $q = $pdo->prepare('SELECT COUNT(*) FROM renewal_payments WHERE status = ?');
    $q->execute([$s]);
    $counts[$s] = (int)$q->fetchColumn();
}

$pageTitle = 'Renewal Payments - StayFinder';
require_once '../includes/header.php';
$activeAdmin = 'renewals';
require_once '../includes/admin_nav.php';
?>
<div class="admin-page-head">
    <div><p class="admin-eyebrow">Administrator</p><h1>Renewal Payments</h1><p>Verify StayFinder listing renewal payments before extending listing validity.</p></div>
</div>

<?php if (isset($_GET['updated'])): ?>
    <div class="alert <?= ($_GET['updated'] === 'rejected') ? 'error' : 'success' ?>"><?= $_GET['updated'] === 'paid' ? 'Renewal approved and the listing was extended for 3 months.' : 'Renewal payment rejected. The owner was notified.' ?></div>
<?php elseif (isset($_GET['error'])): ?><div class="alert error"><?= htmlspecialchars((string)$_GET['error'], ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>

<div class="admin-tabs renewal-tabs">
    <a class="<?= $status === 'pending' ? 'active' : '' ?>" href="renewals.php?status=pending">Pending <span><?= $counts['pending'] ?></span></a>
    <a class="<?= $status === 'paid' ? 'active' : '' ?>" href="renewals.php?status=paid">Approved <span><?= $counts['paid'] ?></span></a>
    <a class="<?= $status === 'rejected' ? 'active' : '' ?>" href="renewals.php?status=rejected">Rejected <span><?= $counts['rejected'] ?></span></a>
    <a class="<?= $status === 'all' ? 'active' : '' ?>" href="renewals.php?status=all">All</a>
</div>

<div class="admin-renewal-list">
<?php if (!$payments): ?>
    <div class="admin-empty"><div class="admin-empty-icon">↻</div><h3>No renewal payments found</h3><p>There are no payments in this category.</p></div>
<?php else: foreach ($payments as $payment): ?>
    <article class="admin-renewal-card">
        <div class="admin-renewal-main">
            <div class="admin-renewal-title-row"><div><h2><?= htmlspecialchars($payment['title'], ENT_QUOTES, 'UTF-8') ?></h2><p><?= htmlspecialchars($payment['location'], ENT_QUOTES, 'UTF-8') ?></p></div><span class="admin-status <?= renewal_status_class((string)$payment['status']) ?>"><?= htmlspecialchars(renewal_status_label((string)$payment['status']), ENT_QUOTES, 'UTF-8') ?></span></div>
            <div class="admin-renewal-meta">
                <div><span>Owner / Manager</span><strong><?= htmlspecialchars($payment['owner_name'], ENT_QUOTES, 'UTF-8') ?></strong><small><?= htmlspecialchars($payment['owner_email'], ENT_QUOTES, 'UTF-8') ?></small></div>
                <div><span>Payment</span><strong>₱<?= number_format((float)$payment['amount'], 2) ?></strong><small><?= htmlspecialchars(renewal_payment_method_label($payment['payment_method']), ENT_QUOTES, 'UTF-8') ?></small></div>
                <div><span>Reference</span><strong><?= htmlspecialchars($payment['reference_number'], ENT_QUOTES, 'UTF-8') ?></strong><small><?= htmlspecialchars(date('M d, Y g:i A', strtotime((string)$payment['created_at'])), ENT_QUOTES, 'UTF-8') ?></small></div>
                <div><span>Current Expiry</span><strong><?= htmlspecialchars(format_listing_expiration($payment['listing_expires_at']), ENT_QUOTES, 'UTF-8') ?></strong><small><?= listing_is_expired($payment['listing_expires_at']) ? 'Expired' : 'Still active' ?></small></div>
            </div>
            <?php if (!empty($payment['owner_note'])): ?><div class="admin-renewal-note"><strong>Owner note:</strong> <?= nl2br(htmlspecialchars($payment['owner_note'], ENT_QUOTES, 'UTF-8')) ?></div><?php endif; ?>
            <?php if (!empty($payment['admin_note'])): ?><div class="admin-renewal-note admin-renewal-admin-note"><strong>Administrator note:</strong> <?= nl2br(htmlspecialchars($payment['admin_note'], ENT_QUOTES, 'UTF-8')) ?></div><?php endif; ?>
        </div>
        <div class="admin-renewal-actions">
            <a class="admin-outline-btn" target="_blank" rel="noopener" href="view-renewal-proof.php?id=<?= (int)$payment['id'] ?>">View Payment Proof</a>
            <?php if ($payment['status'] === 'pending'): ?>
                <form method="post" action="process-renewal.php" class="renewal-decision-form">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="payment_id" value="<?= (int)$payment['id'] ?>">
                    <button class="admin-primary-btn" type="submit" name="action" value="approve" onclick="return confirm('Approve this payment and extend the listing for 3 months?');">✓ Approve & Extend 3 Months</button>
                    <textarea name="admin_note" maxlength="500" rows="2" placeholder="Optional note for the owner when rejecting"></textarea>
                    <button class="admin-danger-btn" type="submit" name="action" value="reject" onclick="return confirm('Reject this renewal payment?');">✕ Reject Payment</button>
                </form>
            <?php elseif ($payment['status'] === 'paid'): ?>
                <span class="admin-renewal-verified">✓ Verified<?= !empty($payment['verifier_name']) ? ' by ' . htmlspecialchars($payment['verifier_name'], ENT_QUOTES, 'UTF-8') : '' ?></span>
            <?php endif; ?>
        </div>
    </article>
<?php endforeach; endif; ?>
</div>
</section></div>
<?php require_once '../includes/footer.php'; ?>
