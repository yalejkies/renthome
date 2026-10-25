<?php
declare(strict_types=1);
require_once 'config/database.php';
require_once 'includes/auth.php';
require_role('rent_seeker');

$selectedId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$stmt = $pdo->prepare("SELECT i.*, p.title, p.owner_id, u.full_name AS owner_name FROM inquiries i JOIN properties p ON p.id = i.property_id JOIN users u ON u.id = p.owner_id WHERE i.sender_id = ? ORDER BY i.created_at DESC");
$stmt->execute([$_SESSION['user_id']]);
$inquiries = $stmt->fetchAll();
$selected = null; $replies = [];
if ($selectedId) {
    $s = $pdo->prepare("SELECT i.*, p.title, u.full_name AS owner_name FROM inquiries i JOIN properties p ON p.id = i.property_id JOIN users u ON u.id = p.owner_id WHERE i.id = ? AND i.sender_id = ? LIMIT 1");
    $s->execute([$selectedId, $_SESSION['user_id']]); $selected = $s->fetch();
    if ($selected) {
        $r = $pdo->prepare("SELECT r.*, u.full_name FROM inquiry_replies r JOIN users u ON u.id = r.sender_id WHERE r.inquiry_id = ? ORDER BY r.created_at ASC");
        $r->execute([$selectedId]); $replies = $r->fetchAll();
    }
}
$pageTitle = 'My Inquiries - StayFinder'; require_once 'includes/header.php';
?>
<h1>My Inquiries</h1>
<div class="card-grid">
<?php foreach ($inquiries as $i): ?><div class="card"><h3><?= htmlspecialchars($i['title'], ENT_QUOTES, 'UTF-8') ?></h3><p>Owner: <?= htmlspecialchars($i['owner_name'], ENT_QUOTES, 'UTF-8') ?></p><p>Status: <strong><?= htmlspecialchars($i['status'], ENT_QUOTES, 'UTF-8') ?></strong></p><p><?= nl2br(htmlspecialchars($i['message'], ENT_QUOTES, 'UTF-8')) ?></p><a class="primary-button" href="my-inquiries.php?id=<?= (int)$i['id'] ?>">View Conversation</a></div><?php endforeach; ?>
</div>
<?php if (!$inquiries): ?><div class="card"><p>You have not sent any inquiries yet.</p></div><?php endif; ?>
<?php if ($selected): ?><div class="card"><h2>Conversation: <?= htmlspecialchars($selected['title'], ENT_QUOTES, 'UTF-8') ?></h2><p><?= nl2br(htmlspecialchars($selected['message'], ENT_QUOTES, 'UTF-8')) ?></p><?php foreach ($replies as $r): ?><div class="message-box"><strong><?= htmlspecialchars($r['full_name'], ENT_QUOTES, 'UTF-8') ?></strong><span class="small"> · <?= htmlspecialchars($r['created_at'], ENT_QUOTES, 'UTF-8') ?></span><p><?= nl2br(htmlspecialchars($r['message'], ENT_QUOTES, 'UTF-8')) ?></p></div><?php endforeach; ?></div><?php endif; ?>
<?php require_once 'includes/footer.php'; ?>
