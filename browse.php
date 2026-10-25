<?php
declare(strict_types=1);
require_once 'config/database.php';
require_once 'includes/auth.php';
require_once 'includes/csrf.php';
require_once 'includes/cabanatuan_barangays.php';

$propertyType = (string)($_GET['property_type'] ?? '');
$capacity = (string)($_GET['capacity'] ?? '');
$gender = (string)($_GET['gender'] ?? '');
$maxPrice = (string)($_GET['max_price'] ?? '');
$search = trim((string)($_GET['q'] ?? ''));
$location = trim((string)($_GET['location'] ?? ''));
$allowedTypes = ['apartment', 'dorm', 'boarding_house'];
$allowedGender = ['female_only', 'male_only', 'mixed'];
if ($location !== '' && !is_valid_cabanatuan_barangay($location)) $location = '';
$errors = [];

if ($propertyType !== '' && !in_array($propertyType, $allowedTypes, true)) $propertyType = '';
if ($gender !== '' && !in_array($gender, $allowedGender, true)) $gender = '';
if ($capacity !== '' && (!ctype_digit($capacity) || (int)$capacity < 1 || (int)$capacity > 999)) $capacity = '';
if ($maxPrice !== '' && (!preg_match('/^\d{1,8}(?:\.\d{1,2})?$/', $maxPrice) || (float)$maxPrice <= 0)) $maxPrice = '';
if (strlen($search) > 80) $search = substr($search, 0, 80);

$sql = "SELECT p.id, p.title, p.property_type, p.location, p.price, p.capacity, p.gender_preference, p.availability_status,
               (SELECT pi.image_path FROM property_images pi WHERE pi.property_id = p.id ORDER BY pi.id LIMIT 1) AS image_path
        FROM properties p
        WHERE p.status = 'approved' AND (p.listing_expires_at IS NULL OR p.listing_expires_at > NOW())";
$params = [];
if ($location !== '') {
    $sql .= ' AND p.location = :location';
    $params[':location'] = $location;
} else {
    $locationParams = [];
    foreach (CABANATUAN_BARANGAYS as $i => $barangay) {
        $key = ':barangay_' . $i;
        $locationParams[$key] = $barangay;
    }
    $placeholders = implode(',', array_keys($locationParams));
    $sql .= " AND p.location IN ($placeholders)";
    $params = array_merge($locationParams, []);
}
if ($search !== '') {
    $sql .= ' AND (p.title LIKE :search OR p.description LIKE :search)';
    $params[':search'] = '%' . $search . '%';
}
if ($propertyType !== '') { $sql .= ' AND p.property_type = :property_type'; $params[':property_type'] = $propertyType; }
if ($capacity !== '') { $sql .= ' AND p.capacity >= :capacity'; $params[':capacity'] = (int)$capacity; }
if ($gender !== '') { $sql .= " AND p.gender_preference IN (:gender, 'mixed')"; $params[':gender'] = $gender; }
if ($maxPrice !== '') { $sql .= ' AND p.price <= :max_price'; $params[':max_price'] = (float)$maxPrice; }
$sql .= " ORDER BY (p.availability_status = 'available') DESC, p.created_at DESC";
$stmt = $pdo->prepare($sql); $stmt->execute($params); $properties = $stmt->fetchAll();

$featuredSql = "SELECT p.id, p.title, p.price, p.location, p.availability_status,
    (SELECT pi.image_path FROM property_images pi WHERE pi.property_id=p.id ORDER BY pi.id LIMIT 1) AS image_path
    FROM properties p WHERE p.status='approved' AND (p.listing_expires_at IS NULL OR p.listing_expires_at > NOW())";
$featuredParams = [];
if ($location !== '') {
    $featuredSql .= ' AND p.location = ?';
    $featuredParams[] = $location;
} else {
    $placeholders = implode(',', array_fill(0, count(CABANATUAN_BARANGAYS), '?'));
    $featuredSql .= " AND p.location IN ($placeholders)";
    $featuredParams = CABANATUAN_BARANGAYS;
}
$featuredSql .= " AND p.availability_status = 'available' ORDER BY p.created_at DESC LIMIT 3";
$featuredStmt = $pdo->prepare($featuredSql);
$featuredStmt->execute($featuredParams);
$featured = $featuredStmt->fetchAll();

$favoriteIds = [];
if (is_logged_in() && ($_SESSION['user']['role'] ?? '') === 'rent_seeker') {
    $favStmt = $pdo->prepare('SELECT property_id FROM favorites WHERE user_id = ?');
    $favStmt->execute([$_SESSION['user_id']]);
    $favoriteIds = array_map('intval', $favStmt->fetchAll(PDO::FETCH_COLUMN));
}

$pageTitle = 'Browse Rentals - StayFinder';
require_once 'includes/header.php';
?>

<div class="browse-top">
    <div>
        <h1>Find Your Next Home</h1>
        <p>Browse available rental properties across the barangays of Cabanatuan City, Nueva Ecija.</p>
    </div>
</div>

<div class="hero-search" style="max-width:100%; margin-bottom:24px; border:1px solid var(--line); box-shadow:0 5px 20px rgba(16,36,58,.04);">
    <input type="search" name="q" form="rental-filters" value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>" placeholder="Search location, city, or property name...">
    <button type="submit" form="rental-filters" aria-label="Search">⌕</button>
</div>

<div class="section-head">
    <h2>Featured Listings</h2>
    <a href="#all-listings">View All</a>
</div>
<div class="featured-grid">
    <?php if ($featured): foreach ($featured as $item): ?>
        <a class="featured-card" href="property.php?id=<?= (int)$item['id'] ?>">
            <?php if (!empty($item['image_path'])): ?><img src="<?= property_image_url($item['image_path']) ?>" alt="Rental photo"><?php else: ?><img src="<?= base_url('assets/img/placeholder.svg') ?>" alt="No photo available"><?php endif; ?>
            <div class="featured-body">
                <span class="availability-pill available">● Available</span>
                <h3><?= htmlspecialchars($item['title'], ENT_QUOTES, 'UTF-8') ?></h3>
                <div class="price">₱<?= number_format((float)$item['price'], 0) ?>/month</div>
                <div class="location-line">⌖ <?= htmlspecialchars($item['location'], ENT_QUOTES, 'UTF-8') ?></div>
            </div>
        </a>
    <?php endforeach; else: ?>
        <div class="card"><p>No featured listings yet.</p></div>
    <?php endif; ?>
</div>

<div class="section-head" id="all-listings"><h2>Browse & Filter</h2></div>
<div class="filter-card">
    <form method="get" id="rental-filters">
        <div class="filter-grid">
            <!-- Keep the barangay selector first. With 89 options, placing it near the
                 bottom of the form can make Chrome open its native menu upward. -->
            <div class="form-group filter-location">
                <label for="location">Barangay / Location</label>
                <select id="location" name="location">
                    <option value="">All Barangays</option>
                    <?php foreach (CABANATUAN_BARANGAYS as $barangay): ?>
                        <option value="<?= htmlspecialchars($barangay, ENT_QUOTES, 'UTF-8') ?>" <?= $location === $barangay ? 'selected' : '' ?>><?= htmlspecialchars($barangay, ENT_QUOTES, 'UTF-8') ?></option>
                    <?php endforeach; ?>
                </select>
                <p class="small">Cabanatuan City, Nueva Ecija only.</p>
            </div>
            <div class="form-group">
                <label for="property_type">Property Type</label>
                <select id="property_type" name="property_type">
                    <option value="">All Types</option>
                    <option value="boarding_house" <?= $propertyType === 'boarding_house' ? 'selected' : '' ?>>Boarding House</option>
                    <option value="apartment" <?= $propertyType === 'apartment' ? 'selected' : '' ?>>Apartment</option>
                    <option value="dorm" <?= $propertyType === 'dorm' ? 'selected' : '' ?>>Dormitory</option>
                </select>
            </div>
            <div class="form-group">
                <label for="max_price">Maximum Price</label>
                <input id="max_price" name="max_price" inputmode="decimal" value="<?= htmlspecialchars($maxPrice, ENT_QUOTES, 'UTF-8') ?>" placeholder="₱50,000+">
            </div>
            <div class="form-group">
                <label for="capacity">Capacity</label>
                <input id="capacity" name="capacity" type="number" min="1" max="999" value="<?= htmlspecialchars($capacity, ENT_QUOTES, 'UTF-8') ?>" placeholder="Any">
            </div>
            <div class="form-group">
                <label for="gender">Gender</label>
                <select id="gender" name="gender">
                    <option value="">Any</option>
                    <option value="female_only" <?= $gender === 'female_only' ? 'selected' : '' ?>>Female</option>
                    <option value="male_only" <?= $gender === 'male_only' ? 'selected' : '' ?>>Male</option>
                    <option value="mixed" <?= $gender === 'mixed' ? 'selected' : '' ?>>Mixed</option>
                </select>
            </div>
        </div>
        <div class="filter-actions" style="margin-top:14px;">
            <a class="secondary-button" href="browse.php">Reset</a>
            <button type="submit">Apply Filters</button>
        </div>
    </form>
</div>

<?php if ($errors): ?><div class="alert error"><?php foreach ($errors as $error): ?><div><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div><?php endforeach; ?></div><?php endif; ?>

<div class="listing-grid">
<?php foreach ($properties as $property): ?>
    <article class="property-card">
        <?php if (!empty($property['image_path'])): ?><img src="<?= property_image_url($property['image_path']) ?>" alt="Property image" class="listing-thumb"><?php else: ?><div class="listing-thumb"></div><?php endif; ?>
        <div class="property-card-body">
            <h3><?= htmlspecialchars($property['title'], ENT_QUOTES, 'UTF-8') ?></h3>
            <p class="price-line">₱<?= number_format((float)$property['price'], 0) ?>/month</p>
            <p>⌖ <?= htmlspecialchars($property['location'], ENT_QUOTES, 'UTF-8') ?></p>
            <p><?= htmlspecialchars(ucwords(str_replace('_', ' ', $property['property_type'])), ENT_QUOTES, 'UTF-8') ?> · Capacity <?= (int)$property['capacity'] ?> · <?= htmlspecialchars(ucwords(str_replace('_', ' ', $property['gender_preference'])), ENT_QUOTES, 'UTF-8') ?></p>
            <div class="card-actions">
                <div class="availability-row">
                    <span class="availability-pill <?= $property['availability_status'] === 'available' ? 'available' : 'unavailable' ?>">
                        <?= $property['availability_status'] === 'available' ? '● Available' : '● Unavailable' ?>
                    </span>
                </div>
                <a class="primary-button" href="property.php?id=<?= (int)$property['id'] ?>">View Details</a>
                <?php if (is_logged_in() && ($_SESSION['user']['role'] ?? '') === 'rent_seeker'): ?>
                    <form method="post" action="favorite.php" class="inline-form">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
                        <input type="hidden" name="property_id" value="<?= (int)$property['id'] ?>">
                        <button type="submit" class="secondary-button">♡ <?= in_array((int)$property['id'], $favoriteIds, true) ? 'Saved' : 'Save' ?></button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </article>
<?php endforeach; ?>
</div>
<?php if (!$properties): ?><div class="card" style="margin-top:18px;"><p>No approved listings matched your filters.</p></div><?php endif; ?>

<?php require_once 'includes/footer.php'; ?>
