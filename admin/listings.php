<?php
declare(strict_types=1);

require_once '../config/database.php';
require_once '../config/app.php';
require_once '../includes/auth.php';
require_once '../includes/csrf.php';

require_role('admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) exit('Invalid form request.');
    $propertyId = filter_var($_POST['property_id'] ?? null, FILTER_VALIDATE_INT);
    $action = $_POST['action'] ?? '';
    if (!$propertyId || !in_array($action, ['approve', 'reject'], true)) exit('Invalid request.');
    $newStatus = $action === 'approve' ? 'approved' : 'rejected';

    if ($action === 'approve') {
        $stmt = $pdo->prepare(
            'UPDATE properties p
             INNER JOIN users u ON u.id = p.owner_id
             SET p.status = ?
             WHERE p.id = ? AND p.status = \'pending\'
               AND u.mayor_permit_path IS NOT NULL AND u.mayor_permit_path <> \'\''
        );
    } else {
        $stmt = $pdo->prepare('UPDATE properties SET status = ? WHERE id = ? AND status = \'pending\'');
    }

    $stmt->execute([$newStatus, $propertyId]);

    if ($action === 'approve' && $stmt->rowCount() !== 1) {
        header('Location: listings.php?permit_required=1');
        exit;
    }

    header('Location: listings.php?updated=' . $newStatus);
    exit;
}

$status = $_GET['status'] ?? 'all';
$allowedStatuses = ['all', 'pending', 'approved', 'rejected'];
if (!in_array($status, $allowedStatuses, true)) $status = 'all';

$sql = "SELECT p.id, p.title, p.property_type, p.location, p.price, p.capacity,
               p.gender_preference, p.status, p.availability_status, p.created_at, u.full_name AS owner_name, u.mayor_permit_path,
               (SELECT pi.image_path FROM property_images pi WHERE pi.property_id = p.id ORDER BY pi.id LIMIT 1) AS image_path
        FROM properties p JOIN users u ON u.id = p.owner_id";
$params = [];
if ($status !== 'all') { $sql .= ' WHERE p.status = ?'; $params[] = $status; }
$sql .= ' ORDER BY p.created_at DESC';
$stmt = $pdo->prepare($sql); $stmt->execute($params); $listings = $stmt->fetchAll();

$pageTitle = 'Manage Listings - StayFinder';
require_once '../includes/header.php';
$activeAdmin = 'listings';
require_once '../includes/admin_nav.php';
?>

<div class="admin-page-head">
    <div><p class="admin-eyebrow">Administrator</p><h1>Manage Listings</h1><p>Review submitted properties and control which listings are public.</p></div>
</div>

<?php if (isset($_GET['updated'])): ?>
    <div class="alert success">Listing <?= htmlspecialchars($_GET['updated'], ENT_QUOTES, 'UTF-8') ?> successfully.</div>
<?php elseif (isset($_GET['deleted'])): ?>
    <div class="alert success">The listing was deleted successfully.</div>
<?php elseif (isset($_GET['permit_required'])): ?>
    <div class="alert error">This listing cannot be approved until the owner's Mayor's Permit has been uploaded.</div>
<?php endif; ?>

<div class="admin-tabs">
    <?php foreach ($allowedStatuses as $tab): ?>
        <a class="<?= $status === $tab ? 'active' : '' ?>" href="listings.php?status=<?= urlencode($tab) ?>"><?= ucfirst($tab) ?></a>
    <?php endforeach; ?>
</div>

<div class="admin-listing-table">
    <div class="admin-listing-header">
        <div>Property</div>
        <div>Listing Information</div>
        <div>Owner / Verification</div>
        <div>Status</div>
        <div class="admin-listing-header-actions">Actions</div>
    </div>

    <?php foreach ($listings as $listing): ?>
        <?php
            $propertyType = ucwords(str_replace('_', ' ', (string)$listing['property_type']));
            $gender = ucwords(str_replace('_', ' ', (string)$listing['gender_preference']));
            $hasPermit = !empty($listing['mayor_permit_path']);
            $isAvailable = ($listing['availability_status'] ?? 'available') === 'available';
        ?>
        <article class="admin-listing-row">
            <div class="admin-listing-photo-cell">
                <div class="admin-listing-photo">
                    <img src="<?= property_image_url($listing['image_path'] ?? null) ?>" alt="<?= htmlspecialchars($listing['title'], ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <span class="admin-listing-id">Listing #<?= (int)$listing['id'] ?></span>
            </div>

            <div class="admin-listing-info">
                <div class="admin-listing-title-line">
                    <h3><?= htmlspecialchars($listing['title'], ENT_QUOTES, 'UTF-8') ?></h3>
                    <span class="availability-pill <?= $isAvailable ? 'available' : 'unavailable' ?>">
                        <?= $isAvailable ? '● Available' : '● Unavailable' ?>
                    </span>
                </div>
                <p class="admin-listing-location">⌖ <?= htmlspecialchars($listing['location'], ENT_QUOTES, 'UTF-8') ?></p>
                <div class="admin-listing-facts">
                    <div><span>Monthly Rent</span><strong>₱<?= number_format((float)$listing['price'], 2) ?></strong></div>
                    <div><span>Type</span><strong><?= htmlspecialchars($propertyType, ENT_QUOTES, 'UTF-8') ?></strong></div>
                    <div><span>Capacity</span><strong><?= (int)$listing['capacity'] ?> person<?= (int)$listing['capacity'] === 1 ? '' : 's' ?></strong></div>
                    <div><span>Gender</span><strong><?= htmlspecialchars($gender, ENT_QUOTES, 'UTF-8') ?></strong></div>
                </div>
                <p class="admin-listing-date">Submitted <?= htmlspecialchars(date('M d, Y', strtotime((string)$listing['created_at'])), ENT_QUOTES, 'UTF-8') ?></p>
            </div>

            <div class="admin-listing-owner">
                <span class="admin-owner-label">Owner / Manager</span>
                <strong><?= htmlspecialchars($listing['owner_name'], ENT_QUOTES, 'UTF-8') ?></strong>
                <?php if ($hasPermit): ?>
                    <span class="admin-permit-badge verified">✓ Mayor's Permit uploaded</span>
                <?php else: ?>
                    <span class="admin-permit-badge missing">! Mayor's Permit missing</span>
                <?php endif; ?>
            </div>

            <div class="admin-listing-status-cell">
                <span class="admin-status <?= htmlspecialchars($listing['status'], ENT_QUOTES, 'UTF-8') ?>">
                    <?= htmlspecialchars(ucfirst($listing['status']), ENT_QUOTES, 'UTF-8') ?>
                </span>
                <?php if ($listing['status'] === 'pending' && !$hasPermit): ?>
                    <small>Approval blocked</small>
                <?php elseif ($listing['status'] === 'pending'): ?>
                    <small>Ready for review</small>
                <?php elseif ($listing['status'] === 'approved'): ?>
                    <small>Public listing</small>
                <?php else: ?>
                    <small>Not public</small>
                <?php endif; ?>
            </div>

            <div class="admin-listing-actions-cell">
                <a class="admin-action-view" href="listing-review.php?id=<?= (int)$listing['id'] ?>">View Details</a>
                <?php if ($listing['status'] === 'pending'): ?>
                    <form method="post" class="admin-approval-actions">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
                        <input type="hidden" name="property_id" value="<?= (int)$listing['id'] ?>">
                        <?php if ($hasPermit): ?>
                            <button class="admin-primary-btn admin-compact-btn" type="submit" name="action" value="approve">✓ Approve</button>
                        <?php else: ?>
                            <button class="admin-disabled-btn admin-compact-btn" type="button" disabled>Permit Required</button>
                        <?php endif; ?>
                        <button class="admin-danger-btn admin-compact-btn" type="submit" name="action" value="reject">Reject</button>
                    </form>
                <?php endif; ?>
                <form method="post" action="delete-listing.php" class="admin-delete-form admin-listing-delete" onsubmit="return confirm('Delete this listing permanently? This cannot be undone.');">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="property_id" value="<?= (int)$listing['id'] ?>">
                    <button class="admin-delete-btn admin-compact-btn" type="submit">Delete</button>
                </form>
            </div>
        </article>
    <?php endforeach; ?>
</div>

<?php if (!$listings): ?><div class="admin-empty"><div class="admin-empty-icon">⌂</div><h3>No listings found</h3><p>There are no listings in this category.</p></div><?php endif; ?>

</section></div>
<?php require_once '../includes/footer.php'; ?>
