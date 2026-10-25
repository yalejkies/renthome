<?php
declare(strict_types=1);
require_once '../config/app.php';
require_once '../includes/auth.php';
require_role('admin');
$pageTitle='Admin Settings - StayFinder'; require_once '../includes/header.php'; $activeAdmin='settings'; require_once '../includes/admin_nav.php';
?>
<div class="admin-page-head"><div><p class="admin-eyebrow">Administrator</p><h1>Settings</h1><p>Basic system information for the local StayFinder project.</p></div></div>
<div class="admin-settings-grid">
    <div class="admin-review-card"><h2>System</h2><div class="admin-setting-row"><strong>Application</strong><span>StayFinder</span></div><div class="admin-setting-row"><strong>Location</strong><span>Cabanatuan City, Nueva Ecija</span></div><div class="admin-setting-row"><strong>Role</strong><span>Administrator</span></div></div>
    <div class="admin-review-card"><h2>Listing Rules</h2><p class="admin-description">Public search filters are limited to price, location, property type, capacity, and gender. Electricity/water bills, Wi-Fi, curfew, transportation, rules, and other information are shown in each listing description.</p></div>
</div>
</section></div><?php require_once '../includes/footer.php'; ?>
