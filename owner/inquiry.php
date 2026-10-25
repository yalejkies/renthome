<?php
declare(strict_types=1);
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/csrf.php';
require_once '../includes/notifications.php';
require_role('owner');

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) { exit('Invalid inquiry.'); }

$stmt = $pdo->prepare("SELECT i.*, p.title, p.owner_id, u.full_name AS sender_name, u.email AS sender_email
    FROM inquiries i JOIN properties p ON p.id = i.property_id JOIN users u ON u.id = i.sender_id
    WHERE i.id = ? AND p.owner_id = ? LIMIT 1");
$stmt->execute([$id, $_SESSION['user_id']]);
$inquiry = $stmt->fetch();
if (!$inquiry) { http_response_code(404); exit('Inquiry not found.'); }

$replyErrors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) $replyErrors[] = 'Invalid form request.';
    $message = trim((string)($_POST['message'] ?? ''));
    if ($message === '' || mb_strlen($message) < 2 || mb_strlen($message) > 3000) $replyErrors[] = 'Reply must be between 2 and 3000 characters.';

    if (!$replyErrors) {
        $pdo->beginTransaction();
        try {
            $reply = $pdo->prepare('INSERT INTO inquiry_replies (inquiry_id, sender_id, message) VALUES (?, ?, ?)');
            $reply->execute([$id, $_SESSION['user_id'], $message]);
            $update = $pdo->prepare('UPDATE inquiries SET status = \'replied\' WHERE id = ?');
            $update->execute([$id]);
            create_notification($pdo, (int)$inquiry['sender_id'], 'New inquiry reply', 'The owner/manager replied to your inquiry about ' . $inquiry['title'] . '.', 'my-inquiries.php?id=' . $id);
            $pdo->commit();
            header('Location: inquiry.php?id=' . $id . '&replied=1'); exit;
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $replyErrors[] = 'The reply could not be sent.';
        }
    }
}

$read = $pdo->prepare('UPDATE inquiries SET status = CASE WHEN status = \'unread\' THEN \'read\' ELSE status END WHERE id = ?');
$read->execute([$id]);

$replies = $pdo->prepare("SELECT r.*, u.full_name FROM inquiry_replies r JOIN users u ON u.id = r.sender_id WHERE r.inquiry_id = ? ORDER BY r.created_at ASC");
$replies->execute([$id]);
$replyRows = $replies->fetchAll();

$pageTitle = 'Inquiry - StayFinder';
require_once '../includes/header.php';
?>
<div class="card">
<h1>Inquiry</h1>
<p><strong>Property:</strong> <?= htmlspecialchars($inquiry['title'], ENT_QUOTES, 'UTF-8') ?></p>
<p><strong>From:</strong> <?= htmlspecialchars($inquiry['sender_name'], ENT_QUOTES, 'UTF-8') ?> (<?= htmlspecialchars($inquiry['sender_email'], ENT_QUOTES, 'UTF-8') ?>)</p>
<div class="message-box"><strong>Original Message</strong><p><?= nl2br(htmlspecialchars($inquiry['message'], ENT_QUOTES, 'UTF-8')) ?></p></div>
<h3>Conversation</h3>
<?php foreach ($replyRows as $reply): ?><div class="message-box"><strong><?= htmlspecialchars($reply['full_name'], ENT_QUOTES, 'UTF-8') ?></strong><span class="small"> · <?= htmlspecialchars($reply['created_at'], ENT_QUOTES, 'UTF-8') ?></span><p><?= nl2br(htmlspecialchars($reply['message'], ENT_QUOTES, 'UTF-8')) ?></p></div><?php endforeach; ?>
<?php if (!$replyRows): ?><p class="small">No replies yet.</p><?php endif; ?>
<?php if ($replyErrors): ?><div class="alert error"><?php foreach ($replyErrors as $e): ?><div><?= htmlspecialchars($e, ENT_QUOTES, 'UTF-8') ?></div><?php endforeach; ?></div><?php endif; ?>
<form method="post">
<input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
<div class="form-group"><label for="message">Reply</label><textarea id="message" name="message" maxlength="3000" required></textarea></div>
<button type="submit">Send Reply</button> <a class="secondary-button" href="inquiries.php">Back</a>
</form>
</div>
<?php require_once '../includes/footer.php'; ?>
