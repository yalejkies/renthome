<?php
declare(strict_types=1);
require_once '../config/database.php';
require_once '../config/app.php';
require_once '../includes/auth.php';
require_once '../includes/csrf.php';
require_role('admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) exit('Invalid form request.');
    $userId = filter_var($_POST['user_id'] ?? null, FILTER_VALIDATE_INT);
    $status = $_POST['status'] ?? '';
    if (!$userId || !in_array($status, ['active','inactive','suspended'], true)) exit('Invalid request.');
    if ($userId === (int)$_SESSION['user_id']) exit('You cannot change your own account status here.');
    $stmt = $pdo->prepare('UPDATE users SET status = ? WHERE id = ?'); $stmt->execute([$status, $userId]);
    header('Location: users.php?updated=1'); exit;
}
$users = $pdo->query('SELECT id, full_name, email, phone, role, status, created_at FROM users ORDER BY created_at DESC')->fetchAll();
$pageTitle = 'Manage Users - StayFinder';
require_once '../includes/header.php'; $activeAdmin='users'; require_once '../includes/admin_nav.php';
?>
<div class="admin-page-head"><div><p class="admin-eyebrow">Administrator</p><h1>Manage Users</h1><p>View registered accounts and manage account status.</p></div></div>
<?php if (isset($_GET['updated'])): ?><div class="alert success">User status updated.</div><?php endif; ?>
<div class="admin-table-card">
    <div class="admin-table-top"><h2>All Users</h2><input id="userSearch" type="search" placeholder="Search users..."></div>
    <div class="table-wrap"><table class="admin-table" id="userTable"><thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Status</th><th>Joined</th><th>Action</th></tr></thead><tbody>
    <?php foreach ($users as $user): ?>
        <tr>
            <td><strong><?= htmlspecialchars($user['full_name'], ENT_QUOTES, 'UTF-8') ?></strong><small><?= htmlspecialchars($user['phone'], ENT_QUOTES, 'UTF-8') ?></small></td>
            <td><?= htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8') ?></td>
            <td><span class="admin-role <?= htmlspecialchars($user['role'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars(ucwords(str_replace('_',' ',$user['role'])), ENT_QUOTES, 'UTF-8') ?></span></td>
            <td><span class="admin-user-status <?= htmlspecialchars($user['status'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars(ucfirst($user['status']), ENT_QUOTES, 'UTF-8') ?></span></td>
            <td><?= htmlspecialchars(date('M d, Y', strtotime($user['created_at'])), ENT_QUOTES, 'UTF-8') ?></td>
            <td><?php if ((int)$user['id'] !== (int)$_SESSION['user_id']): ?><form method="post" class="admin-status-form"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>"><input type="hidden" name="user_id" value="<?= (int)$user['id'] ?>"><select name="status" onchange="this.form.submit()"><option value="">Change...</option><option value="active">Active</option><option value="inactive">Inactive</option><option value="suspended">Suspended</option></select></form><?php else: ?><span class="admin-current">Current Admin</span><?php endif; ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody></table></div>
</div>
<script>
document.getElementById('userSearch')?.addEventListener('input', function(){const q=this.value.toLowerCase();document.querySelectorAll('#userTable tbody tr').forEach(r=>r.style.display=r.textContent.toLowerCase().includes(q)?'':'none');});
</script>
</section></div><?php require_once '../includes/footer.php'; ?>
