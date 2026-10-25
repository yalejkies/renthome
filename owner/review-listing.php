<?php
declare(strict_types=1);

require_once '../config/database.php';
require_once '../config/app.php';
require_once '../includes/auth.php';
require_once '../includes/csrf.php';

require_role('owner');

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    http_response_code(400);
    exit('Invalid listing ID.');
}

$stmt = $pdo->prepare(
    'SELECT * FROM properties WHERE id = ? AND owner_id = ? LIMIT 1'
);
$stmt->execute([$id, $_SESSION['user_id']]);
$property = $stmt->fetch();

if (!$property) {
    http_response_code(404);
    exit('Listing not found.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        exit('Invalid form request.');
    }

    if ($property['status'] !== 'pending') {
        exit('This listing cannot be submitted again.');
    }

    $stmt = $pdo->prepare(
        'UPDATE properties SET status = \'pending\' WHERE id = ? AND owner_id = ?'
    );
    $stmt->execute([$id, $_SESSION['user_id']]);

    header('Location: dashboard.php?submitted=1');
    exit;
}

$imgStmt = $pdo->prepare(
    'SELECT image_path FROM property_images WHERE property_id = ? ORDER BY id'
);
$imgStmt->execute([$id]);
$images = $imgStmt->fetchAll();

$pageTitle = 'Review Listing - StayFinder';
require_once '../includes/header.php';
$ownerStep = 5;
require_once 'flow.php';
?>

<style>
.owner-review-summary {
    display: flex;
    gap: 2rem;
    flex-wrap: wrap;
}

/* Left side: images + description stacked vertically */
.image-section {
    flex: 1 1 300px;
    max-width: 600px;
}

.owner-review-photo {
    width: 100%;
    height: auto;
    border-radius: 8px;
    object-fit: cover;
}

/* grid for extra images */
.image-grid {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
    margin-top: 10px;
}

.property-image {
    width: 90px;
    height: 60px;
    object-fit: cover;
    border-radius: 6px;
}

/* Description styling */
.owner-description {
    margin-top: 12px;
    font-size: 0.95rem;
    line-height: 1.4;
    color: #333;
    white-space: pre-line;
}

/* Right side: info details */
.owner-review-details {
    flex: 1 1 250px;
    max-width: 360px;
}

.owner-review-details h2 {
    margin-top: 0;
}

.review-price {
    font-size: 1.25rem;
    font-weight: 700;
    margin: 8px 0 12px;
}

.owner-review-row {
    display: flex;
    justify-content: space-between;
    margin-bottom: 8px;
}

.owner-review-row strong {
    font-weight: 600;
}

.owner-state {
    display: flex;
    gap: 1rem;
    background-color: #fff3cd;
    border: 1px solid #ffeeba;
    border-radius: 6px;
    padding: 1rem;
    margin-top: 1.5rem;
    color: #856404;
    align-items: center;
}

.owner-state-icon {
    font-size: 1.8rem;
    line-height: 1;
    font-weight: 700;
}

.owner-actions {
    margin-top: 1.5rem;
    display: flex;
    gap: 1rem;
}

.owner-actions button,
.owner-actions .secondary-button {
    flex: 1;
    padding: 0.7rem 1.25rem;
    font-weight: 700;
    font-size: 1rem;
    border-radius: 6px;
    cursor: pointer;
    border: none;
    text-align: center;
    text-decoration: none;
    display: inline-block;
}

/* Primary button */
.owner-actions button {
    background-color: #007bff;
    color: white;
    border: none;
    transition: background-color 0.3s ease;
}

.owner-actions button:hover {
    background-color: #0056b3;
}

/* Secondary button style */
.owner-actions .secondary-button {
    background-color: #e2e6ea;
    color: #333;
    border: 1px solid #ccc;
    line-height: 2.2;
}

.owner-actions .secondary-button:hover {
    background-color: #d6dadc;
}
</style>

<div class="owner-form-shell">
    <div class="owner-form-header">
        <h1>Review Your Listing</h1>
        <p>Check everything before submitting your property to the administrator.</p>
    </div>
    <div class="owner-form-card">

        <div class="owner-review-summary">
            <div class="image-section">
                <?php if (!empty($images)): ?>
                    <img src="<?= property_image_url($images[0]['image_path']) ?>" alt="Property image" class="owner-review-photo">
                <?php else: ?>
                    <img src="<?= base_url('assets/img/placeholder.svg') ?>" alt="No property image" class="owner-review-photo">
                <?php endif; ?>

                <?php if (count($images) > 1): ?>
                    <div class="image-grid" style="margin-top:10px;">
                        <?php foreach (array_slice($images, 1) as $image): ?>
                            <img src="<?= property_image_url($image['image_path']) ?>" alt="Property image" class="property-image">
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <h3 style="margin:18px 0 7px;">Description and Other Information</h3>
                <div class="owner-description"><?= nl2br(htmlspecialchars($property['description'] ?? '', ENT_QUOTES, 'UTF-8')) ?></div>
            </div>

            <div class="owner-review-details">
                <h2><?= htmlspecialchars($property['title'], ENT_QUOTES, 'UTF-8') ?></h2>
                <div class="review-price">₱<?= number_format((float)$property['price'], 2) ?>/month</div>
                <div class="owner-review-row"><strong>Location</strong><span><?= htmlspecialchars($property['location'], ENT_QUOTES, 'UTF-8') ?></span></div>
                <div class="owner-review-row"><strong>Property Type</strong><span><?= htmlspecialchars(ucwords(str_replace('_', ' ', $property['property_type'])), ENT_QUOTES, 'UTF-8') ?></span></div>
                <div class="owner-review-row"><strong>Capacity</strong><span><?= (int)$property['capacity'] ?> person(s)</span></div>
                <div class="owner-review-row"><strong>Gender</strong><span><?= htmlspecialchars(ucwords(str_replace('_', ' ', $property['gender_preference'])), ENT_QUOTES, 'UTF-8') ?></span></div>
            </div>
        </div>

        <div class="owner-state warning">
            <div class="owner-state-icon">◷</div>
            <div>
                <h3>Ready for Administrator Review</h3>
                <p>Your listing will remain pending until an administrator approves it.</p>
            </div>
        </div>

        <form method="post">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
            <div class="owner-actions">
                <button type="submit">Submit Listing →</button>
                <a class="secondary-button" href="create-listing.php">Back & Edit</a>
            </div>
        </form>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
