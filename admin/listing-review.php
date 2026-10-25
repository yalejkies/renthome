<?php
declare(strict_types=1);

require_once '../config/database.php';
require_once '../config/app.php';
require_once '../includes/auth.php';
require_once '../includes/csrf.php';

require_role('admin');

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) { http_response_code(400); exit('Invalid listing ID.'); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) exit('Invalid form request.');
    $action = $_POST['action'] ?? '';
    if (!in_array($action, ['approve', 'reject'], true)) exit('Invalid action.');
    $status = $action === 'approve' ? 'approved' : 'rejected';

    if ($action === 'approve') {
        $stmt = $pdo->prepare(
            'UPDATE properties p
             INNER JOIN users u ON u.id = p.owner_id
             SET p.status = ?,
                 p.listing_expires_at = DATE_ADD(NOW(), INTERVAL 3 MONTH)
             WHERE p.id = ? AND p.status = \'pending\'
               AND u.mayor_permit_path IS NOT NULL AND u.mayor_permit_path <> \'\''
        );
    } else {
        $stmt = $pdo->prepare('UPDATE properties SET status = ? WHERE id = ? AND status = \'pending\'');
    }

    $stmt->execute([$status, $id]);

    if ($action === 'approve' && $stmt->rowCount() !== 1) {
        header('Location: listing-review.php?id=' . $id . '&permit_required=1');
        exit;
    }

    header('Location: listing-review.php?id=' . $id . '&updated=' . $status);
    exit;
}

$stmt = $pdo->prepare('SELECT p.*, u.full_name AS owner_name, u.email AS owner_email, u.phone AS owner_phone, u.mayor_permit_path, u.mayor_permit_uploaded_at FROM properties p JOIN users u ON u.id = p.owner_id WHERE p.id = ? LIMIT 1');
$stmt->execute([$id]);
$property = $stmt->fetch();
if (!$property) { http_response_code(404); exit('Listing not found.'); }

$imgStmt = $pdo->prepare('SELECT image_path FROM property_images WHERE property_id = ? ORDER BY id');
$imgStmt->execute([$id]);
$images = $imgStmt->fetchAll();

$pageTitle = 'Review Listing - StayFinder';
require_once '../includes/header.php';
$activeAdmin = 'listings';
require_once '../includes/admin_nav.php';
?>

<div class="admin-review-head">
    <a class="admin-back" href="listings.php">← Back to Listings</a>
    <span class="admin-status <?= htmlspecialchars($property['status'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars(ucfirst($property['status']), ENT_QUOTES, 'UTF-8') ?></span>
</div>

<?php if (isset($_GET['updated'])): ?><div class="alert success">Listing <?= htmlspecialchars($_GET['updated'], ENT_QUOTES, 'UTF-8') ?> successfully.</div><?php elseif (isset($_GET['permit_required'])): ?><div class="alert error">This listing cannot be approved until the owner's Mayor's Permit has been uploaded.</div><?php endif; ?>

<div class="admin-review-layout">
    <section class="admin-review-main">
        <div class="admin-review-gallery">
            <?php if ($images): ?>
                <img id="adminMainImage" src="<?= property_image_url($images[0]['image_path']) ?>" alt="Property photo">
                <div class="admin-thumb-row">
                    <?php foreach ($images as $image): ?><img src="<?= property_image_url($image['image_path']) ?>" alt="Property thumbnail" onclick="document.getElementById('adminMainImage').src=this.src"><?php endforeach; ?>
                </div>
            <?php else: ?>
                <img id="adminMainImage" src="<?= base_url('assets/img/placeholder.svg') ?>" alt="No property photo">
            <?php endif; ?>
        </div>

        <div class="admin-review-card">
            <div class="admin-card-title-row"><div><h1><?= htmlspecialchars($property['title'], ENT_QUOTES, 'UTF-8') ?></h1><p><?= htmlspecialchars($property['location'], ENT_QUOTES, 'UTF-8') ?></p></div></div>
            <div class="admin-review-price">₱<?= number_format((float)$property['price'], 2) ?>/month</div>
            <div class="admin-review-meta">
                <div><strong>Property Type</strong><span><?= htmlspecialchars(ucwords(str_replace('_', ' ', $property['property_type'])), ENT_QUOTES, 'UTF-8') ?></span></div>
                <div><strong>Capacity</strong><span><?= (int)$property['capacity'] ?></span></div>
                <div><strong>Gender</strong><span><?= htmlspecialchars(ucwords(str_replace('_', ' ', $property['gender_preference'])), ENT_QUOTES, 'UTF-8') ?></span></div>
                <div><strong>Submitted</strong><span><?= htmlspecialchars(date('M d, Y', strtotime($property['created_at'])), ENT_QUOTES, 'UTF-8') ?></span></div>
            </div>
            <h3>Description & Other Information</h3>
            <div class="admin-description"><?= nl2br(htmlspecialchars($property['description'] ?? 'No description provided.', ENT_QUOTES, 'UTF-8')) ?></div>
        </div>
    </section>

    <aside class="admin-review-side">
        <div class="admin-review-card">
            <p class="admin-eyebrow">Owner / Manager</p>
            <h2><?= htmlspecialchars($property['owner_name'], ENT_QUOTES, 'UTF-8') ?></h2>
            <p><?= htmlspecialchars($property['owner_email'], ENT_QUOTES, 'UTF-8') ?></p>
            <p><?= htmlspecialchars($property['owner_phone'], ENT_QUOTES, 'UTF-8') ?></p>
        </div>
        <div class="admin-permit-card">
            <p class="admin-eyebrow">Owner Verification</p>
            <h3>Mayor's Permit</h3>
            <?php if (!empty($property['mayor_permit_path'])): ?>
                <p class="permit-status-ok">✓ Permit uploaded<?= !empty($property['mayor_permit_uploaded_at']) ? ' on ' . htmlspecialchars(date('M d, Y', strtotime((string)$property['mayor_permit_uploaded_at'])), ENT_QUOTES, 'UTF-8') : '' ?></p>
                <a class="admin-outline-btn admin-full-btn" target="_blank" rel="noopener" href="<?= htmlspecialchars(base_url('admin/view-permit.php?owner_id=' . (int)$property['owner_id']), ENT_QUOTES, 'UTF-8') ?>">View Mayor's Permit</a>
            <?php else: ?>
                <p class="permit-status-missing">! No Mayor's Permit is attached to this owner account.</p>
            <?php endif; ?>
        </div>

        <?php if ($property['status'] === 'pending'): ?>
            <div class="admin-decision-card">
                <p class="admin-eyebrow">Approval Decision</p>
                <h3>Approve or Reject</h3>
                <p>Check the listing information, photos, and permit before making it public.</p>
                <form method="post" class="admin-decision-actions">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
                    <?php if (!empty($property['mayor_permit_path'])): ?>
                        <button class="admin-primary-btn" type="submit" name="action" value="approve">✓ Approve Listing</button>
                    <?php else: ?>
                        <button class="admin-disabled-btn" type="button" disabled>Permit Required Before Approval</button>
                    <?php endif; ?>
                    <button class="admin-danger-btn" type="submit" name="action" value="reject">✕ Reject Listing</button>
                </form>
            </div>
        <?php else: ?>
            <div class="admin-review-card"><h3>Decision Complete</h3><p>This listing is currently <strong><?= htmlspecialchars($property['status'], ENT_QUOTES, 'UTF-8') ?></strong>.</p></div>
        <?php endif; ?>

        <div class="admin-danger-zone">
            <p class="admin-eyebrow">Permanent Action</p>
            <h3>Delete Listing</h3>
            <p>Remove this listing and its uploaded property images permanently.</p>
            <form method="post" action="delete-listing.php" onsubmit="return confirm('Delete this listing permanently? This cannot be undone.');">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="property_id" value="<?= (int)$property['id'] ?>">
                <button class="admin-delete-btn admin-full-btn" type="submit">Delete Listing</button>
            </form>
        </div>
    </aside>
</div>

</section></div>
<?php require_once '../includes/footer.php'; ?>
