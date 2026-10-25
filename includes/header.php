<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/auth.php';
if (is_logged_in()) { require_once __DIR__ . '/../config/database.php'; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? 'StayFinder', ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="stylesheet" href="<?= htmlspecialchars(base_url('assets/css/style.css?v=20261004-eye-fix'), ENT_QUOTES, 'UTF-8') ?>">
</head>
<body>
<header class="site-header">
    <div class="container nav">
        <a class="brand" href="<?= base_url() ?>"><img class="brand-logo" src="<?= base_url('assets/img/stayfinder-navbar.svg') ?>" alt="StayFinder"></a>
        <nav>
            <a href="<?= base_url() ?>">Home</a>
            <a href="<?= base_url('browse.php') ?>">Browse</a>
            <?php if (is_logged_in()): ?>
                <?php if (($_SESSION['user']['role'] ?? '') === 'owner'): ?>
                    <a href="<?= base_url('owner/dashboard.php') ?>">Dashboard</a>
                    <a href="<?= base_url('owner/inquiries.php') ?>">Inquiries</a>
                <?php elseif (($_SESSION['user']['role'] ?? '') === 'admin'): ?>
                    <a href="<?= base_url('admin/dashboard.php') ?>">Dashboard</a>
                <?php elseif (($_SESSION['user']['role'] ?? '') === 'rent_seeker'): ?>
                    <a href="<?= base_url('favorites.php') ?>">Saved</a>
                    <a href="<?= base_url('my-inquiries.php') ?>">My Inquiries</a>
                <?php endif; ?>
                <?php
                    require_once __DIR__ . '/notifications.php';
                    $notificationCountStmt = $pdo->prepare('SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0');
                    $notificationCountStmt->execute([$_SESSION['user_id']]);
                    $notificationCount = (int)$notificationCountStmt->fetchColumn();
                ?>
                <a href="<?= base_url('notifications.php') ?>">Notifications<?= $notificationCount ? ' (' . $notificationCount . ')' : '' ?></a>
                <a href="<?= base_url('logout.php') ?>">Logout</a>
            <?php else: ?>
                <a href="<?= base_url('login.php') ?>">Login</a>
                <a class="nav-button" href="<?= base_url('register.php') ?>">Register</a>
            <?php endif; ?>
        </nav>
    </div>
</header>
<main class="container page">
