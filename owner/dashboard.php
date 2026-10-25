<?php
declare(strict_types=1);

require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/csrf.php';
require_once '../includes/renewal.php';
ensure_renewal_schema($pdo);
require_role('owner');

$ownerStmt = $pdo->prepare('SELECT full_name, mayor_permit_path, mayor_permit_uploaded_at FROM users WHERE id = ? AND role = \'owner\' LIMIT 1');
$ownerStmt->execute([$_SESSION['user_id']]);
$owner = $ownerStmt->fetch() ?: [];

$stmt = $pdo->prepare(
    'SELECT id, title, property_type, location, price, capacity, gender_preference, status, availability_status, listing_expires_at, created_at,
            (SELECT COUNT(*) FROM renewal_payments rp WHERE rp.property_id = properties.id AND rp.owner_id = properties.owner_id AND rp.status = \'pending\') AS pending_renewal_count,
            (SELECT rp.status FROM renewal_payments rp WHERE rp.property_id = properties.id AND rp.owner_id = properties.owner_id ORDER BY rp.created_at DESC LIMIT 1) AS latest_renewal_status
     FROM properties
     WHERE owner_id = ?
     ORDER BY created_at DESC'
);
$stmt->execute([$_SESSION['user_id']]);
$listings = $stmt->fetchAll();

$pendingCount = 0;
$availableCount = 0;
$unavailableCount = 0;
foreach ($listings as $listing) {
    if ($listing['status'] === 'pending') $pendingCount++;
    if ($listing['status'] === 'approved' && $listing['availability_status'] === 'available') $availableCount++;
    if ($listing['status'] === 'approved' && $listing['availability_status'] === 'unavailable') $unavailableCount++;
}

$pageTitle = 'Owner Dashboard - StayFinder';
require_once '../includes/header.php';
$ownerStep = 6;
require_once 'flow.php';
?>

<div class="owner-dashboard-hero">
    <div class="owner-page-intro">
        <h1>Owner / Manager Dashboard</h1>
        <p>Welcome, <?= htmlspecialchars((string)($owner['full_name'] ?? $_SESSION['user']['full_name']), ENT_QUOTES, 'UTF-8') ?>. Manage your rental listings and inquiries here.</p>
    </div>
</div>

<?php if (isset($_GET['submitted'])): ?>
    <div class="alert success">Your listing was submitted successfully and is now under administrator review.</div>
<?php elseif (isset($_GET['availability'])): ?>
    <div class="alert success">Listing marked <?= $_GET['availability'] === 'unavailable' ? 'unavailable' : 'available' ?>.</div>
<?php elseif (isset($_GET['deleted'])): ?>
    <div class="alert success">The listing was deleted successfully.</div>
<?php endif; ?>

<div class="owner-verification-strip <?= !empty($owner['mayor_permit_path']) ? 'verified' : 'required' ?>">
    <div class="owner-verification-icon"><?= !empty($owner['mayor_permit_path']) ? '✓' : '!' ?></div>
    <div class="owner-verification-copy">
        <strong><?= !empty($owner['mayor_permit_path']) ? "Mayor's Permit Uploaded" : "Mayor's Permit Required" ?></strong>
        <span><?= !empty($owner['mayor_permit_path']) ? 'Your permit is on file and can be reviewed by the administrator.' : "Upload your Mayor's Permit before creating a new listing." ?></span>
    </div>
    <a class="secondary-button" href="permit.php"><?= !empty($owner['mayor_permit_path']) ? 'View / Replace Permit' : 'Upload Permit' ?></a>
</div>

<div class="owner-quick-actions">
    <a class="owner-action-card" href="policies.php">
        <div class="owner-action-icon">＋</div>
        <h3>Create a New Listing</h3>
        <p>Review the policies, enter your property information, add photos, and submit it for approval.</p>
    </a>
    <a class="owner-action-card" href="inquiries.php">
        <div class="owner-action-icon">💬</div>
        <h3>Manage Inquiries</h3>
        <p>Read messages from rent seekers and reply to questions about your properties.</p>
    </a>
</div>

<div class="card-grid" style="margin-bottom:24px;">
    <div class="card"><p class="small">Total Listings</p><h2 style="margin:4px 0 0;"><?= count($listings) ?></h2></div>
    <div class="card"><p class="small">Under Review</p><h2 style="margin:4px 0 0;"><?= $pendingCount ?></h2></div>
    <div class="card"><p class="small">Available</p><h2 class="availability-count available-count"><?= $availableCount ?></h2></div>
    <div class="card"><p class="small">Unavailable</p><h2 class="availability-count unavailable-count"><?= $unavailableCount ?></h2></div>
</div>

<div class="section-head">
    <h2>My Listings</h2>
    <a href="policies.php">＋ Add Listing</a>
</div>

<?php if (!$listings): ?>
    <div class="card">
        <h3>No listings yet</h3>
        <p class="small">Start by creating your first rental listing.</p>
        <a class="primary-button" href="policies.php">Create Listing</a>
    </div>
<?php else: ?>
    <div class="card-grid">
        <?php foreach ($listings as $listing): ?>
            <?php
            $status = (string)$listing['status'];
            $availability = (string)$listing['availability_status'];
            $typeLabels = ['apartment' => 'Apartment', 'dorm' => 'Dormitory', 'boarding_house' => 'Boarding House'];
            $genderLabels = ['female_only' => 'Female Only', 'male_only' => 'Male Only', 'mixed' => 'Mixed'];
            ?>
            <div class="card owner-listing-card">
                <div class="owner-listing-body">
                    <p style="margin:0 0 12px"><a class="secondary-button" href="manage-images.php?id=<?= (int)$listing['id'] ?>">Manage Photos</a></p>
                    <div class="owner-listing-top">
                        <div>
                            <h3><?= htmlspecialchars($listing['title'], ENT_QUOTES, 'UTF-8') ?></h3>
                            <p class="small" style="margin:0;"><?= htmlspecialchars($listing['location'], ENT_QUOTES, 'UTF-8') ?></p>
                        </div>
                        <div class="listing-status-stack">
                            <span class="status-pill <?= htmlspecialchars($status, ENT_QUOTES, 'UTF-8') ?>">
                                <?= $status === 'approved' ? 'Approved' : ($status === 'pending' ? 'Under Review' : 'Rejected') ?>
                            </span>
                            <?php if ($status === 'approved'): ?>
                                <span class="availability-pill <?= $availability === 'available' ? 'available' : 'unavailable' ?>">
                                    <?= $availability === 'available' ? '● Available' : '● Unavailable' ?>
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <?php if ($status === 'approved'): ?>
                        <?php $daysRemaining = listing_days_remaining($listing['listing_expires_at'] ?? null); ?>
                        <div class="owner-renewal-strip <?= $daysRemaining !== null && $daysRemaining > 0 ? 'active' : 'expired' ?>">
                            <div>
                                <strong><?= $daysRemaining !== null && $daysRemaining > 0 ? 'Listing Valid Until' : 'Listing Expired' ?></strong>
                                <span><?= htmlspecialchars(format_listing_expiration($listing['listing_expires_at'] ?? null), ENT_QUOTES, 'UTF-8') ?></span>
                            </div>
                            <div class="owner-renewal-days">
                                <?= $daysRemaining !== null && $daysRemaining > 0 ? $daysRemaining . ' day' . ($daysRemaining === 1 ? '' : 's') . ' left' : 'Renewal needed' ?>
                            </div>
                        </div>
                        <?php if (!empty($listing['latest_renewal_status']) && $listing['latest_renewal_status'] !== 'pending'): ?>
                            <div class="owner-renewal-action-row"><span class="renewal-status-badge <?= htmlspecialchars(renewal_status_class((string)$listing['latest_renewal_status']), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars(renewal_status_label((string)$listing['latest_renewal_status']), ENT_QUOTES, 'UTF-8') ?></span><?php if ($listing['latest_renewal_status'] === 'rejected'): ?><a class="secondary-button renewal-small-button" href="renew.php?id=<?= (int)$listing['id'] ?>&rejected=1">Resubmit Renewal</a><?php endif; ?></div>
                        <?php endif; ?>
                        <?php if (($daysRemaining !== null && $daysRemaining <= 30) || !empty($listing['pending_renewal_count'])): ?>
                            <div class="owner-renewal-action-row">
                                <?php if (!empty($listing['pending_renewal_count'])): ?>
                                    <span class="renewal-pending-badge">Payment Pending Verification</span>
                                    <a class="secondary-button renewal-small-button" href="renew.php?id=<?= (int)$listing['id'] ?>">View Renewal</a>
                                <?php else: ?>
                                    <a class="renewal-button" href="renew.php?id=<?= (int)$listing['id'] ?>">Renew Listing · ₱<?= number_format(renewal_fee(), 2) ?></a>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>

                    <div class="owner-listing-meta">
                        <div><strong>Monthly Price</strong><span>₱<?= number_format((float)$listing['price'], 2) ?></span></div>
                        <div><strong>Property Type</strong><span><?= htmlspecialchars($typeLabels[$listing['property_type']] ?? $listing['property_type'], ENT_QUOTES, 'UTF-8') ?></span></div>
                        <div><strong>Capacity</strong><span><?= (int)$listing['capacity'] ?> person(s)</span></div>
                        <div><strong>Gender</strong><span><?= htmlspecialchars($genderLabels[$listing['gender_preference']] ?? $listing['gender_preference'], ENT_QUOTES, 'UTF-8') ?></span></div>
                    </div>

                    <?php if ($status === 'pending'): ?>
                        <div class="owner-state warning" style="margin-bottom:12px;">
                            <div class="owner-state-icon">◷</div>
                            <div><h3>Under Review</h3><p>Your listing is waiting for admin approval.</p></div>
                        </div>
                        <div class="owner-listing-actions">
                            <a class="owner-action-view secondary-button" href="review-listing.php?id=<?= (int)$listing['id'] ?>">View Submission</a>
                            <form method="post" action="delete-listing.php" class="delete-listing-form" onsubmit="return confirm('Delete this listing permanently? This cannot be undone.');">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
                                <input type="hidden" name="property_id" value="<?= (int)$listing['id'] ?>">
                                <button class="delete-listing-button" type="submit">Delete</button>
                            </form>
                        </div>
                    <?php elseif ($status === 'approved'): ?>
                        <div class="owner-state <?= $availability === 'available' ? 'success' : 'unavailable-state' ?>" style="margin-bottom:12px;">
                            <div class="owner-state-icon"><?= $availability === 'available' ? '✓' : '×' ?></div>
                            <div>
                                <h3><?= $availability === 'available' ? 'Available' : 'Unavailable' ?></h3>
                                <p><?= $availability === 'available' ? 'Your property is currently shown as available to rent seekers.' : 'Your property is still listed, but it is marked as no longer available.' ?></p>
                            </div>
                        </div>
                        <div class="owner-listing-actions">
                            <a class="owner-action-view primary-button" href="<?= htmlspecialchars(base_url('property.php?id=' . (int)$listing['id']), ENT_QUOTES, 'UTF-8') ?>">View Public Listing</a>
                            <form method="post" action="toggle-availability.php" class="inline-form">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
                                <input type="hidden" name="property_id" value="<?= (int)$listing['id'] ?>">
                                <input type="hidden" name="availability" value="<?= $availability === 'available' ? 'unavailable' : 'available' ?>">
                                <button class="<?= $availability === 'available' ? 'availability-unavailable-button' : 'availability-available-button' ?>" type="submit">
                                    <?= $availability === 'available' ? 'Mark Unavailable' : 'Mark Available' ?>
                                </button>
                            </form>
                            <form method="post" action="delete-listing.php" class="delete-listing-form" onsubmit="return confirm('Delete this listing permanently? This cannot be undone.');">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
                                <input type="hidden" name="property_id" value="<?= (int)$listing['id'] ?>">
                                <button class="delete-listing-button" type="submit">Delete</button>
                            </form>
                        </div>
                    <?php else: ?>
                        <div class="owner-state" style="margin-bottom:12px;">
                            <div class="owner-state-icon">!</div>
                            <div><h3>Needs Attention</h3><p>This listing was not approved. Review the details and contact the administrator if needed.</p></div>
                        </div>
                        <div class="owner-listing-actions">
                            <a class="owner-action-view secondary-button" href="review-listing.php?id=<?= (int)$listing['id'] ?>">View Submission</a>
                            <form method="post" action="delete-listing.php" class="delete-listing-form" onsubmit="return confirm('Delete this listing permanently? This cannot be undone.');">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
                                <input type="hidden" name="property_id" value="<?= (int)$listing['id'] ?>">
                                <button class="delete-listing-button" type="submit">Delete</button>
                            </form>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php require_once '../includes/footer.php'; ?>
