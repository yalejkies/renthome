<?php
declare(strict_types=1);
require_once '../config/database.php';
require_once '../config/app.php';
require_once '../includes/auth.php';
require_once '../includes/renewal.php';
require_role('admin');
ensure_renewal_schema($pdo);

$stmt = $pdo->query('SELECT a.*, u.full_name AS admin_name FROM admin_audit_logs a LEFT JOIN users u ON u.id = a.admin_id ORDER BY a.created_at DESC LIMIT 200');
$logs = $stmt->fetchAll();
$pageTitle = 'Audit Log - StayFinder';
require_once '../includes/header.php';
$activeAdmin = 'audit';
require_once '../includes/admin_nav.php';
?>
<div class="admin-page-head"><div><p class="admin-eyebrow">Administrator</p><h1>Audit Log</h1><p>Recent administrator actions related to StayFinder system records.</p></div></div>
<div class="admin-table-card"><div class="history-table-wrap"><table class="history-table"><thead><tr><th>Date</th><th>Administrator</th><th>Action</th><th>Record</th><th>Details</th></tr></thead><tbody>
<?php if (!$logs): ?><tr><td colspan="5">No audit records yet.</td></tr><?php else: foreach ($logs as $log): ?><tr><td><?= htmlspecialchars(date('M d, Y g:i A', strtotime((string)$log['created_at'])), ENT_QUOTES, 'UTF-8') ?></td><td><?= htmlspecialchars($log['admin_name'] ?? 'Unknown', ENT_QUOTES, 'UTF-8') ?></td><td><?= htmlspecialchars($log['action'], ENT_QUOTES, 'UTF-8') ?></td><td><?= htmlspecialchars($log['entity_type'] . ($log['entity_id'] ? ' #' . $log['entity_id'] : ''), ENT_QUOTES, 'UTF-8') ?></td><td><?= $log['details'] ? htmlspecialchars($log['details'], ENT_QUOTES, 'UTF-8') : '—' ?></td></tr><?php endforeach; endif; ?>
</tbody></table></div></div>
</section></div>
<?php require_once '../includes/footer.php'; ?>
