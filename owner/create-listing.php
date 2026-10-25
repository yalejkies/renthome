<?php
declare(strict_types=1);

require_once '../config/database.php';
require_once '../config/app.php';
require_once '../includes/auth.php';
require_once '../includes/csrf.php';
require_once '../includes/listing_validation.php';
require_once '../includes/cabanatuan_barangays.php';

require_role('owner');

$check = $pdo->prepare('SELECT owner_policy_accepted_at, mayor_permit_path FROM users WHERE id = ? AND role = \'owner\'');
$check->execute([$_SESSION['user_id']]);
$owner = $check->fetch();

if (!$owner || empty($owner['owner_policy_accepted_at'])) {
    header('Location: policies.php');
    exit;
}

if (empty($owner['mayor_permit_path'])) {
    header('Location: permit.php');
    exit;
}

$errors = [];
$data = [
    'title' => '',
    'property_type' => '',
    'location' => '',
    'price' => '',
    'capacity' => '',
    'gender_preference' => '',
    'description' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        $errors[] = 'Invalid form request. Please try again.';
    }

    [$data, $validationErrors] = validate_listing_data($_POST);
    $errors = array_merge($errors, $validationErrors);

    [$images, $imageErrors] = validate_uploaded_images($_FILES['images'] ?? []);
    $errors = array_merge($errors, $imageErrors);

    if (!$errors && count($images) < 1) {
        $errors[] = 'Please upload at least one property image.';
    }

    if (!$errors) {
        $pdo->beginTransaction();

        try {
            $stmt = $pdo->prepare(
                "INSERT INTO properties
                (owner_id, title, property_type, location, price, capacity,
                 gender_preference, electricity_included, wifi_available, curfew,
                 transportation, rules, description, status)
                 VALUES
                (:owner_id, :title, :property_type, :location, :price, :capacity,
                 :gender_preference, 0, 0, 0,
                 NULL, NULL, :description, 'pending')"
            );

            $stmt->execute([
                ':owner_id' => $_SESSION['user_id'],
                ':title' => $data['title'],
                ':property_type' => $data['property_type'],
                ':location' => $data['location'],
                ':price' => $data['price'],
                ':capacity' => (int)$data['capacity'],
                ':gender_preference' => $data['gender_preference'],
                ':description' => $data['description'] !== '' ? $data['description'] : null,
            ]);

            $propertyId = (int)$pdo->lastInsertId();

            $uploadDir = dirname(__DIR__) . '/uploads/properties';
            if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true)) {
                throw new RuntimeException('Could not create upload directory.');
            }

            foreach ($images as $image) {
                $filename = bin2hex(random_bytes(16)) . '.' . $image['extension'];
                $destination = $uploadDir . '/' . $filename;

                if (!move_uploaded_file($image['tmp_name'], $destination)) {
                    throw new RuntimeException('Failed to save an uploaded image.');
                }

                $pathForDb = 'uploads/properties/' . $filename;

                $imgStmt = $pdo->prepare(
                    'INSERT INTO property_images (property_id, image_path)
                     VALUES (?, ?)'
                );
                $imgStmt->execute([$propertyId, $pathForDb]);
            }

            $pdo->commit();

            header('Location: review-listing.php?id=' . $propertyId);
            exit;
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $errors[] = 'The listing could not be saved. Please try again.';
        }
    }
}

$pageTitle = 'Create Listing - StayFinder';
require_once '../includes/header.php';
$ownerStep = 4;
require_once 'flow.php';
?>

<div class="owner-form-shell">
<div class="owner-form-header">
    <h1>Create a New Listing</h1>
    <p>Enter the information that rent seekers will use to understand your property.</p>
</div>
<div class="owner-form-card">

    <?php if ($errors): ?>
        <div class="alert error">
            <?php foreach ($errors as $error): ?>
                <div><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="owner-info-box"><strong>Search filters:</strong> only price, location, property type, capacity, and gender are used as filters. Electricity/water bills, Wi-Fi, curfew, transportation, rules, and other details belong in the description.</div>

    <form method="post" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>">

        <div class="owner-form-grid">
        <div class="form-group">
            <label for="title">Property Title</label>
            <input id="title" name="title" maxlength="150" required
                   value="<?= htmlspecialchars($data['title'], ENT_QUOTES, 'UTF-8') ?>"
                   placeholder="Example: Cozy 2-Person Apartment">
        </div>

        <div class="form-group">
            <label for="property_type">Property Type</label>
            <select id="property_type" name="property_type" required>
                <option value="">Select type</option>
                <option value="apartment" <?= $data['property_type'] === 'apartment' ? 'selected' : '' ?>>Apartment</option>
                <option value="dorm" <?= $data['property_type'] === 'dorm' ? 'selected' : '' ?>>Dorm</option>
                <option value="boarding_house" <?= $data['property_type'] === 'boarding_house' ? 'selected' : '' ?>>Boarding House</option>
            </select>
        </div>

        <div class="form-group">
            <label for="location">Barangay / Location</label>
            <select id="location" name="location" required>
                <option value="">Select barangay</option>
                <?php foreach (CABANATUAN_BARANGAYS as $barangay): ?>
                    <option value="<?= htmlspecialchars($barangay, ENT_QUOTES, 'UTF-8') ?>" <?= $data['location'] === $barangay ? 'selected' : '' ?>><?= htmlspecialchars($barangay, ENT_QUOTES, 'UTF-8') ?></option>
                <?php endforeach; ?>
            </select>
            <p class="small">Only barangays within Cabanatuan City, Nueva Ecija are available.</p>
        </div>

        <div class="form-group">
            <label for="price">Monthly Price (₱)</label>
            <input id="price" name="price" inputmode="decimal" maxlength="11" required
                   value="<?= htmlspecialchars($data['price'], ENT_QUOTES, 'UTF-8') ?>"
                   placeholder="5000.00">
        </div>

        <div class="form-group">
            <label for="capacity">Capacity</label>
            <input id="capacity" name="capacity" type="number" min="1" max="999" required
                   value="<?= htmlspecialchars($data['capacity'], ENT_QUOTES, 'UTF-8') ?>">
        </div>

        <div class="form-group">
            <label for="gender_preference">Gender Preference</label>
            <select id="gender_preference" name="gender_preference" required>
                <option value="">Select preference</option>
                <option value="female_only" <?= $data['gender_preference'] === 'female_only' ? 'selected' : '' ?>>Female Only</option>
                <option value="male_only" <?= $data['gender_preference'] === 'male_only' ? 'selected' : '' ?>>Male Only</option>
                <option value="mixed" <?= $data['gender_preference'] === 'mixed' ? 'selected' : '' ?>>Mixed</option>
            </select>
        </div>

        <div class="form-group full">
            <label for="description">Description and Other Information</label>
            <textarea id="description" name="description" maxlength="5000" required
                      placeholder="Example: Electricity and water bills are included/separate. Wi-Fi is available. Curfew is 10:00 PM. Transportation is accessible by tricycle, jeepney, or motorcycle. Visitors are allowed until 9:00 PM. Add other important house rules and details here."><?= htmlspecialchars($data['description'], ENT_QUOTES, 'UTF-8') ?></textarea>
            <p class="small">Put electricity and water bill information, Wi-Fi, curfew, transportation, house rules, and other details here. These are not separate filters.</p>
        </div>

        <div class="form-group full">
            <label for="images">Property Images</label>
            <input id="images" type="file" name="images[]" accept=".jpg,.jpeg,.png,.webp" multiple required>
            <p class="small">Upload 1–8 images. JPG, PNG, or WEBP only. Maximum 5 MB each.</p>
        </div>

        </div>
        <div class="owner-actions">
            <button type="submit">Next: Review Listing →</button>
            <a class="secondary-button" href="dashboard.php">Cancel</a>
        </div>
    </form>
</div>
</div>

<?php require_once '../includes/footer.php'; ?>
