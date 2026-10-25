<?php
declare(strict_types=1);
require_once '../config/database.php';
require_once '../includes/auth.php';
require_role('owner');

$stmt = $pdo->prepare("SELECT i.id, i.message, i.status, i.created_at, p.id AS property_id, p.title,
    u.full_name AS sender_name, u.email AS sender_email
    FROM inquiries i JOIN properties p ON p.id = i.property_id JOIN users u ON u.id = i.sender_id
    WHERE p.owner_id = ? ORDER BY i.created_at DESC");
$stmt->execute([$_SESSION['user_id']]);
$inquiries = $stmt->fetchAll();

$pageTitle = 'Inquiries - StayFinder';
require_once '../includes/header.php';
?>
<h1>Inquiries</h1>
<p class="small">Messages from people interested in your rental listings.</p>
<div class="card-grid">
<?php foreach ($inquiries as $inquiry): ?>
    <div class="card">
        <h3><?= htmlspecialchars($inquiry['title'], ENT_QUOTES, 'UTF-8') ?></h3>
        <p><strong>From:</strong> <?= htmlspecialchars($inquiry['sender_name'], ENT_QUOTES, 'UTF-8') ?></p>
        <p><strong>Email:</strong> <?= htmlspecialchars($inquiry['sender_email'], ENT_QUOTES, 'UTF-8') ?></p>
        <p><?= nl2br(htmlspecialchars($inquiry['message'], ENT_QUOTES, 'UTF-8')) ?></p>
        <p class="small"><?= htmlspecialchars($inquiry['status'], ENT_QUOTES, 'UTF-8') ?> · <?= htmlspecialchars($inquiry['created_at'], ENT_QUOTES, 'UTF-8') ?></p>
        <a class="primary-button" href="inquiry.php?id=<?= (int)$inquiry['id'] ?>">View / Reply</a>
    </div>
<?php endforeach; ?>
</div>
<?php if (!$inquiries): ?><div class="card"><p>No inquiries yet.</p></div><?php endif; ?>
<?php require_once '../includes/footer.php'; ?>
