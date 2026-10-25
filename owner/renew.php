<?php
declare(strict_types=1);

require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/csrf.php';
require_once '../includes/renewal.php';
ensure_renewal_schema($pdo);
require_role('owner');

$ownerId = (int)$_SESSION['user_id'];
$propertyId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$propertyId) { http_response_code(400); exit('Invalid listing.'); }

$stmt = $pdo->prepare(
    'SELECT id, title, location, price, status, availability_status, listing_expires_at
     FROM properties WHERE id = ? AND owner_id = ? LIMIT 1'
);
$stmt->execute([$propertyId, $ownerId]);
$property = $stmt->fetch();
if (!$property) { http_response_code(404); exit('Listing not found.'); }
if ($property['status'] !== 'approved') { http_response_code(400); exit('Only approved listings can be renewed.'); }

$paymentStmt = $pdo->prepare(
    "SELECT id, amount, payment_method, reference_number, status, owner_note, admin_note, created_at, verified_at
     FROM renewal_payments
     WHERE property_id = ? AND owner_id = ?
     ORDER BY created_at DESC LIMIT 1"
);
$paymentStmt->execute([$propertyId, $ownerId]);
$latestPayment = $paymentStmt->fetch() ?: null;
$pendingPayment = ($latestPayment && $latestPayment['status'] === 'pending') ? $latestPayment : null;
$daysRemaining = listing_days_remaining($property['listing_expires_at'] ?? null);

$pageTitle = 'Renew Listing - StayFinder';
require_once '../includes/header.php';
?>
<div class="owner-form-shell">
    <div class="owner-form-header">
        <h1>Renew Your Listing</h1>
        <p>Keep your rental listing active for another three months.</p>
    </div>

    <?php if (isset($_GET['submitted'])): ?>
        <div class="alert success">Your renewal payment proof was submitted. Please wait for administrator verification.</div>
    <?php elseif (isset($_GET['approved'])): ?>
        <div class="alert success">Renewal approved! Your listing has been extended for another 3 months.</div>
    <?php elseif (isset($_GET['rejected'])): ?>
        <div class="alert error">Your renewal payment was rejected. Please review the administrator's note and submit a new proof if needed.</div>
    <?php elseif (isset($_GET['error'])): ?>
        <div class="alert error"><?= htmlspecialchars((string)$_GET['error'], ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <div class="owner-form-card">
        <div class="renewal-property-summary">
            <div><span class="renewal-label">Listing</span><strong><?= htmlspecialchars($property['title'], ENT_QUOTES, 'UTF-8') ?></strong><span><?= htmlspecialchars($property['location'], ENT_QUOTES, 'UTF-8') ?></span></div>
            <div class="renewal-price-box"><span>Renewal Fee</span><strong>₱<?= number_format(renewal_fee(), 2) ?></strong></div>
        </div>

        <div class="owner-info-box">
            <strong>Three-month renewal</strong><br>
            After the administrator verifies your payment, the listing will receive another <strong>3 months</strong> of validity.
            <?php if ($daysRemaining !== null && $daysRemaining > 0): ?>
                <br>Current validity: <strong><?= $daysRemaining ?> day<?= $daysRemaining === 1 ? '' : 's' ?> remaining</strong> (until <?= htmlspecialchars(format_listing_expiration($property['listing_expires_at']), ENT_QUOTES, 'UTF-8') ?>).
            <?php else: ?>
                <br>Current status: <strong>Expired</strong>. A successful renewal will make it active again.
            <?php endif; ?>
        </div>

        <?php if ($latestPayment && $latestPayment['status'] !== 'pending'): ?>
            <div class="renewal-status-box <?= renewal_status_class((string)$latestPayment['status']) ?>">
                <strong>Latest Renewal: <?= htmlspecialchars(renewal_status_label((string)$latestPayment['status']), ENT_QUOTES, 'UTF-8') ?></strong>
                <p class="small">Reference: <?= htmlspecialchars($latestPayment['reference_number'], ENT_QUOTES, 'UTF-8') ?> · <?= htmlspecialchars(renewal_payment_method_label($latestPayment['payment_method']), ENT_QUOTES, 'UTF-8') ?></p>
                <?php if ($latestPayment['status'] === 'paid' && !empty($latestPayment['verified_at'])): ?>
                    <p class="small">Verified <?= htmlspecialchars(date('M d, Y g:i A', strtotime((string)$latestPayment['verified_at'])), ENT_QUOTES, 'UTF-8') ?>.</p>
                <?php elseif ($latestPayment['status'] === 'rejected' && !empty($latestPayment['admin_note'])): ?>
                    <p class="small"><strong>Administrator note:</strong> <?= nl2br(htmlspecialchars($latestPayment['admin_note'], ENT_QUOTES, 'UTF-8')) ?></p>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if ($pendingPayment): ?>
            <div class="renewal-pending-box">
                <strong>Renewal payment pending verification</strong>
                <p class="small">Reference: <?= htmlspecialchars($pendingPayment['reference_number'], ENT_QUOTES, 'UTF-8') ?> · <?= htmlspecialchars(renewal_payment_method_label($pendingPayment['payment_method']), ENT_QUOTES, 'UTF-8') ?></p>
                <p class="small" style="margin:0;">Please wait for administrator verification. Do not submit another payment proof while this one is pending.</p>
            </div>
        <?php else: ?>
            <form method="post" action="submit-renewal.php" enctype="multipart/form-data" class="renewal-form">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="property_id" value="<?= (int)$propertyId ?>">
                <div class="form-group"><label for="payment_method">Payment Method</label><select id="payment_method" name="payment_method" required><option value="">Select payment method</option><option value="gcash">GCash</option><option value="bank_transfer">Bank Transfer</option></select><p class="field-help">Payment proof is manually verified by the administrator.</p></div>
                <div class="form-group"><label for="reference_number">Payment Reference Number</label><input id="reference_number" type="text" name="reference_number" maxlength="100" required placeholder="Enter the transaction/reference number"></div>
                <div class="form-group"><label for="proof">Upload Payment Proof</label><input id="proof" type="file" name="proof" accept="image/jpeg,image/png,application/pdf" required><p class="field-help">Accepted: JPG, PNG, or PDF. Maximum file size: 5 MB.</p></div>
                <div class="form-group"><label for="owner_note">Note <span class="optional-label">(Optional)</span></label><textarea id="owner_note" name="owner_note" maxlength="500" rows="3" placeholder="Add a short note about your payment if needed."></textarea></div>
                <div class="renewal-payment-summary"><span>Amount to pay</span><strong>₱<?= number_format(renewal_fee(), 2) ?></strong></div>
                <div class="owner-actions"><a class="secondary-button" href="dashboard.php">Cancel</a><button class="primary-button" type="submit">Submit Payment Proof</button></div>
            </form>
        <?php endif; ?>
    </div>
</div>
<?php require_once '../includes/footer.php'; ?>
