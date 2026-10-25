<?php
$activeAdmin = $activeAdmin ?? 'dashboard';
?>
<div class="admin-layout">
    <aside class="admin-sidebar">
        <a class="admin-brand" href="<?= base_url('admin/dashboard.php') ?>">
            <img class="admin-brand-logo" src="<?= base_url('assets/img/stayFinder-logo-admin.svg') ?>" alt="StayFinder">
        </a>
        <nav class="admin-side-nav">
            <a class="<?= $activeAdmin === 'dashboard' ? 'active' : '' ?>" href="<?= base_url('admin/dashboard.php') ?>"><span>⌂</span> Dashboard</a>
            <a class="<?= $activeAdmin === 'listings' ? 'active' : '' ?>" href="<?= base_url('admin/listings.php') ?>"><span>▣</span> Listings</a>
            <a class="<?= $activeAdmin === 'renewals' ? 'active' : '' ?>" href="<?= base_url('admin/renewals.php') ?>"><span>↻</span> Renewals</a>
            <a class="<?= $activeAdmin === 'users' ? 'active' : '' ?>" href="<?= base_url('admin/users.php') ?>"><span>♙</span> Users</a>
            <a class="<?= $activeAdmin === 'reports' ? 'active' : '' ?>" href="<?= base_url('admin/reports.php') ?>"><span>⚠</span> Reports</a>
            <a class="<?= $activeAdmin === 'settings' ? 'active' : '' ?>" href="<?= base_url('admin/settings.php') ?>"><span>⚙</span> Settings</a>
            <a class="<?= $activeAdmin === 'audit' ? 'active' : '' ?>" href="<?= base_url('admin/audit-log.php') ?>"><span>▤</span> Audit Log</a>
        </nav>
        <div class="admin-sidebar-bottom">
            <a href="<?= base_url() ?>">← View Site</a>
            <a href="<?= base_url('logout.php') ?>">↪ Logout</a>
        </div>
    </aside>
    <section class="admin-content">
