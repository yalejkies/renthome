<?php
declare(strict_types=1);
require_once 'config/database.php';
require_once 'includes/auth.php';
require_once 'includes/csrf.php';
require_role('rent_seeker');

$stmt = $pdo->prepare("SELECT p.id, p.title, p.property_type, p.location, p.price, p.capacity, p.gender_preference,
    (SELECT pi.image_path FROM property_images pi WHERE pi.property_id = p.id ORDER BY pi.id LIMIT 1) AS image_path
    FROM favorites f JOIN properties p ON p.id = f.property_id
    WHERE f.user_id = ? AND p.status = 'approved' AND (p.listing_expires_at IS NULL OR p.listing_expires_at > NOW()) ORDER BY f.created_at DESC");
$stmt->execute([$_SESSION['user_id']]);
$properties = $stmt->fetchAll();

$pageTitle = 'Saved Listings - StayFinder';
require_once 'includes/header.php';
?>
<h1>Saved Listings</h1>
<div class="card-grid">
<?php foreach ($properties as $property): ?>
    <div class="card property-card">
        <?php if (!empty($property['image_path'])): ?><img src="<?= property_image_url($property['image_path']) ?>" alt="Property image" class="listing-thumb"><?php endif; ?>
        <h3><?= htmlspecialchars($property['title'], ENT_QUOTES, 'UTF-8') ?></h3>
        <p><strong>₱<?= number_format((float)$property['price'], 2) ?>/month</strong></p>
        <p><?= htmlspecialchars(ucwords(str_replace('_', ' ', $property['property_type'])), ENT_QUOTES, 'UTF-8') ?></p>
        <p><?= htmlspecialchars($property['location'], ENT_QUOTES, 'UTF-8') ?></p>
        <p>Capacity: <?= (int)$property['capacity'] ?> · <?= htmlspecialchars(ucwords(str_replace('_', ' ', $property['gender_preference'])), ENT_QUOTES, 'UTF-8') ?></p>
        <a class="primary-button" href="property.php?id=<?= (int)$property['id'] ?>">View Details</a>
        <form method="post" action="favorite.php" class="inline-form">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="property_id" value="<?= (int)$property['id'] ?>">
            <button type="submit" class="secondary-button">Remove Saved</button>
        </form>
    </div>
<?php endforeach; ?>
</div>
<?php if (!$properties): ?><div class="card"><p>You have no saved listings yet.</p></div><?php endif; ?>
<?php require_once 'includes/footer.php'; ?>
