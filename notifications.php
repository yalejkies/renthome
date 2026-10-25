<?php
declare(strict_types=1);
require_once 'config/database.php';
require_once 'includes/auth.php';
require_role($_SESSION['user']['role'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
    if ($id) {
        $stmt = $pdo->prepare('UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?');
        $stmt->execute([$id, $_SESSION['user_id']]);
    }
    header('Location: notifications.php'); exit;
}
$stmt = $pdo->prepare('SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 100');
$stmt->execute([$_SESSION['user_id']]);
$notifications = $stmt->fetchAll();
$pageTitle = 'Notifications - StayFinder'; require_once 'includes/header.php';
?>
<h1>Notifications</h1>
<?php foreach ($notifications as $n): ?><div class="card notification <?= !$n['is_read'] ? 'unread' : '' ?>"><h3><?= htmlspecialchars($n['title'], ENT_QUOTES, 'UTF-8') ?></h3><p><?= nl2br(htmlspecialchars($n['message'], ENT_QUOTES, 'UTF-8')) ?></p><p class="small"><?= htmlspecialchars($n['created_at'], ENT_QUOTES, 'UTF-8') ?></p><?php if ($n['link']): ?><a class="primary-button" href="<?= htmlspecialchars($n['link'], ENT_QUOTES, 'UTF-8') ?>">Open</a><?php endif; ?><form method="post" class="inline-form"><input type="hidden" name="id" value="<?= (int)$n['id'] ?>"><button type="submit" class="secondary-button">Mark Read</button></form></div><?php endforeach; ?>
<?php if (!$notifications): ?><div class="card"><p>No notifications.</p></div><?php endif; ?>
<?php require_once 'includes/footer.php'; ?>
