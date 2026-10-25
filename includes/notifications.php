<?php
declare(strict_types=1);

function create_notification(PDO $pdo, int $userId, string $title, string $message, ?string $link = null): void
{
    $stmt = $pdo->prepare('INSERT INTO notifications (user_id, title, message, link) VALUES (?, ?, ?, ?)');
    $stmt->execute([$userId, $title, $message, $link]);
}
