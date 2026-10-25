<?php
declare(strict_types=1);
require_once '../config/database.php';
require_once '../config/app.php';
require_once '../includes/auth.php';
require_once '../includes/csrf.php';
require_role('admin');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) exit('Invalid form request.');
    $reportId=filter_var($_POST['report_id']??null,FILTER_VALIDATE_INT); $status=$_POST['status']??'';
    if(!$reportId||!in_array($status,['pending','reviewed','resolved'],true)) exit('Invalid request.');
    $stmt=$pdo->prepare('UPDATE reports SET status=? WHERE id=?'); $stmt->execute([$status,$reportId]); header('Location: reports.php?updated=1'); exit;
}
$reports=$pdo->query('SELECT r.id,r.report_type,r.description,r.status,r.created_at,u.full_name AS reporter,p.title AS property_title FROM reports r JOIN users u ON u.id=r.reported_by LEFT JOIN properties p ON p.id=r.property_id ORDER BY r.created_at DESC')->fetchAll();
$pageTitle='Reports - StayFinder'; require_once '../includes/header.php'; $activeAdmin='reports'; require_once '../includes/admin_nav.php';
?>
<div class="admin-page-head"><div><p class="admin-eyebrow">Administrator</p><h1>Reports</h1><p>Review complaints and reports submitted by StayFinder users.</p></div></div>
<?php if(isset($_GET['updated'])):?><div class="alert success">Report status updated.</div><?php endif; ?>
<div class="admin-report-grid">
<?php foreach($reports as $report): ?>
<article class="admin-report-card">
    <div class="admin-report-icon">⚑</div>
    <div class="admin-report-body">
        <div class="admin-card-title-row"><div><h3><?= htmlspecialchars($report['report_type'],ENT_QUOTES,'UTF-8') ?></h3><p><?= htmlspecialchars($report['property_title']??'No property attached',ENT_QUOTES,'UTF-8') ?></p></div><span class="admin-status <?= htmlspecialchars($report['status'],ENT_QUOTES,'UTF-8') ?>"><?= htmlspecialchars(ucfirst($report['status']),ENT_QUOTES,'UTF-8') ?></span></div>
        <p class="admin-report-description"><?= nl2br(htmlspecialchars($report['description'],ENT_QUOTES,'UTF-8')) ?></p>
        <div class="admin-report-meta">Reported by <?= htmlspecialchars($report['reporter'],ENT_QUOTES,'UTF-8') ?> · <?= htmlspecialchars(date('M d, Y',strtotime($report['created_at'])),ENT_QUOTES,'UTF-8') ?></div>
        <form method="post" class="admin-report-actions"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token(),ENT_QUOTES,'UTF-8') ?>"><input type="hidden" name="report_id" value="<?= (int)$report['id'] ?>"><select name="status"><option value="pending" <?= $report['status']==='pending'?'selected':'' ?>>Pending</option><option value="reviewed" <?= $report['status']==='reviewed'?'selected':'' ?>>Reviewed</option><option value="resolved" <?= $report['status']==='resolved'?'selected':'' ?>>Resolved</option></select><button class="admin-outline-btn" type="submit">Update Report</button></form>
    </div>
</article>
<?php endforeach; ?>
</div>
<?php if(!$reports): ?><div class="admin-empty"><div class="admin-empty-icon">✓</div><h3>No reports</h3><p>No user reports have been submitted.</p></div><?php endif; ?>
</section></div><?php require_once '../includes/footer.php'; ?>
