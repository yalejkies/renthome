<?php
declare(strict_types=1);
require_once '../config/database.php';
require_once '../config/app.php';
require_once '../includes/auth.php';
require_once '../includes/renewal.php';
ensure_renewal_schema($pdo);
require_role('owner');

$stmt = $pdo->prepare('SELECT rp.*, p.title FROM renewal_payments rp JOIN properties p ON p.id = rp.property_id WHERE rp.owner_id = ? ORDER BY rp.created_at DESC');
$stmt->execute([(int)$_SESSION['user_id']]);
$payments = $stmt->fetchAll();

$pageTitle = 'Renewal History - StayFinder';
require_once '../includes/header.php';
?>
<div class="simple-page-head">
    <a class="back-link" href="<?= base_url('owner/dashboard.php') ?>">← Back to Dashboard</a>
    <h1>Renewal History</h1>
    <p>View your StayFinder listing renewal submissions and verification results.</p>
</div>
<div class="history-card">
<?php if (!$payments): ?>
    <div class="empty-state"><h3>No renewal history yet</h3><p>Your renewal submissions will appear here.</p></div>
<?php else: ?>
    <div class="history-table-wrap"><table class="history-table"><thead><tr><th>Listing</th><th>Amount</th><th>Method</th><th>Reference</th><th>Status</th><th>Submitted</th><th>Admin Note</th></tr></thead><tbody>
    <?php foreach ($payments as $payment): ?>
        <tr>
            <td><strong><?= htmlspecialchars($payment['title'], ENT_QUOTES, 'UTF-8') ?></strong></td>
            <td>₱<?= number_format((float)$payment['amount'], 2) ?></td>
            <td><?= htmlspecialchars(renewal_payment_method_label((string)$payment['payment_method']), ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= htmlspecialchars($payment['reference_number'], ENT_QUOTES, 'UTF-8') ?></td>
            <td><span class="renewal-status-badge <?= htmlspecialchars(renewal_status_class((string)$payment['status']), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars(renewal_status_label((string)$payment['status']), ENT_QUOTES, 'UTF-8') ?></span></td>
            <td><?= htmlspecialchars(date('M d, Y g:i A', strtotime((string)$payment['created_at'])), ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= $payment['admin_note'] ? nl2br(htmlspecialchars($payment['admin_note'], ENT_QUOTES, 'UTF-8')) : '—' ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody></table></div>
<?php endif; ?>
</div>
<?php require_once '../includes/footer.php'; ?>
