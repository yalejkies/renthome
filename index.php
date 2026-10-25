<?php
declare(strict_types=1);
$pageTitle='StayFinder - Find Your Next Home';
require_once 'config/database.php';
require_once 'includes/cabanatuan_barangays.php';

$locationPlaceholders = implode(',', array_fill(0, count(CABANATUAN_BARANGAYS), '?'));

// Keep the dashboard limited to the existing StayFinder listing functionality:
// approved listings, search, browse/filter, and property details.
$featuredStmt = $pdo->prepare("SELECT p.id,p.title,p.property_type,p.price,p.location,p.capacity,p.gender_preference,
    (SELECT pi.image_path FROM property_images pi WHERE pi.property_id=p.id ORDER BY pi.id LIMIT 1) image_path
    FROM properties p
    WHERE p.status='approved' AND (p.listing_expires_at IS NULL OR p.listing_expires_at > NOW()) AND p.availability_status='available' AND p.location IN ($locationPlaceholders)
    ORDER BY p.created_at DESC LIMIT 6");
$featuredStmt->execute(CABANATUAN_BARANGAYS);
$listings = $featuredStmt->fetchAll();

$heroImage = '';
foreach ($listings as $listing) {
    if (!empty($listing['image_path'])) { $heroImage = (string)$listing['image_path']; break; }
}

$typeLabels = [
    'apartment' => 'Apartment',
    'dorm' => 'Dormitory',
    'boarding_house' => 'Boarding House'
];

require_once 'includes/header.php';
?>

<section class="market-hero">
    <?php if ($heroImage): ?>
        <img class="market-hero-image" src="<?= property_image_url($heroImage) ?>" alt="Rental property">
    <?php endif; ?>
    <div class="market-hero-overlay"></div>
    <div class="market-hero-content">
        <span class="market-kicker">RENTAL PROPERTIES IN CABANATUAN</span>
        <h1>Find a place that feels like home.</h1>
        <p>Browse available apartments, dormitories, and boarding houses around Cabanatuan City, Nueva Ecija.</p>
        <form class="market-search" action="browse.php" method="get">
            <span class="market-search-icon">⌕</span>
            <input type="search" name="q" placeholder="Search by property name or location..." aria-label="Search rentals">
            <button type="submit">Search</button>
        </form>
    </div>
</section>

<section class="dashboard-section">
    <div class="dashboard-section-head">
        <div>
            <span class="section-eyebrow">EXPLORE RENTALS</span>
            <h2>Featured rental properties</h2>
            <p>Recently approved listings available through StayFinder.</p>
        </div>
        <a class="section-link" href="browse.php">View all listings →</a>
    </div>

    <?php if ($listings): ?>
        <div class="market-listing-grid">
            <?php foreach ($listings as $item): ?>
                <article class="market-listing-card">
                    <a class="market-listing-image-wrap" href="property.php?id=<?= (int)$item['id'] ?>">
                        <?php if (!empty($item['image_path'])): ?>
                            <img src="<?= property_image_url($item['image_path']) ?>" alt="<?= htmlspecialchars($item['title'], ENT_QUOTES, 'UTF-8') ?>">
                        <?php else: ?>
                            <img src="<?= base_url('assets/img/placeholder.svg') ?>" alt="No property photo available">
                        <?php endif; ?>
                        <span class="listing-type-badge"><?= htmlspecialchars($typeLabels[$item['property_type']] ?? ucwords(str_replace('_',' ',$item['property_type'])), ENT_QUOTES, 'UTF-8') ?></span>
                        <span class="availability-pill available market-availability-badge">● Available</span>
                    </a>
                    <div class="market-listing-body">
                        <div class="market-price">₱<?= number_format((float)$item['price'], 0) ?><span>/month</span></div>
                        <h3><a href="property.php?id=<?= (int)$item['id'] ?>"><?= htmlspecialchars($item['title'], ENT_QUOTES, 'UTF-8') ?></a></h3>
                        <p class="market-location">⌖ <?= htmlspecialchars($item['location'], ENT_QUOTES, 'UTF-8') ?>, Cabanatuan</p>
                        <div class="market-meta">
                            <span>Capacity <?= (int)$item['capacity'] ?></span>
                            <span><?= htmlspecialchars(ucwords(str_replace('_',' ',$item['gender_preference'])), ENT_QUOTES, 'UTF-8') ?></span>
                        </div>
                        <a class="market-view-button" href="property.php?id=<?= (int)$item['id'] ?>">View Details</a>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="empty-dashboard-card">
            <h3>No approved listings yet</h3>
            <p>Approved rental properties will appear here once they are available.</p>
            <a class="primary-button" href="browse.php">Browse Rentals</a>
        </div>
    <?php endif; ?>
</section>

<section class="dashboard-section dashboard-browse-strip">
    <div>
        <span class="section-eyebrow">BROWSE BY PROPERTY TYPE</span>
        <h2>What type of place are you looking for?</h2>
        <p>Use the existing StayFinder filters to narrow down available listings.</p>
    </div>
    <div class="type-links">
        <a href="browse.php?property_type=boarding_house"><strong>Boarding Houses</strong><span>View listings →</span></a>
        <a href="browse.php?property_type=apartment"><strong>Apartments</strong><span>View listings →</span></a>
        <a href="browse.php?property_type=dorm"><strong>Dormitories</strong><span>View listings →</span></a>
    </div>
</section>

<section class="dashboard-section dashboard-cta">
    <div>
        <span class="section-eyebrow">LOOKING FOR A PLACE?</span>
        <h2>Browse all available rentals in Cabanatuan.</h2>
        <p>Filter listings by barangay, price, property type, capacity, and gender.</p>
    </div>
    <a class="primary-button dashboard-cta-button" href="browse.php">Browse Rentals</a>
</section>

<?php require_once 'includes/footer.php'; ?>
