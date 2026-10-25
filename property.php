<?php
declare(strict_types=1);

require_once 'config/database.php';
require_once 'includes/auth.php';
require_once 'includes/csrf.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id) {
    http_response_code(400);
    exit('Invalid property ID.');
}

$stmt = $pdo->prepare("
    SELECT
        p.*,
        u.full_name AS owner_name
    FROM properties p
    JOIN users u ON u.id = p.owner_id
    WHERE p.id = ?
      AND p.status = 'approved'
      AND (
          p.listing_expires_at IS NULL
          OR p.listing_expires_at > NOW()
      )
    LIMIT 1
");

$stmt->execute([$id]);

$property = $stmt->fetch();

if (!$property) {
    http_response_code(404);
    exit('Property not found.');
}


/* =========================================================
   PROPERTY IMAGES
========================================================= */

$imgStmt = $pdo->prepare("
    SELECT image_path
    FROM property_images
    WHERE property_id = ?
    ORDER BY id
");

$imgStmt->execute([$id]);

$images = $imgStmt->fetchAll();


/* =========================================================
   FAVORITE STATUS
========================================================= */

$isFavorite = false;

if (
    is_logged_in() &&
    ($_SESSION['user']['role'] ?? '') === 'rent_seeker'
) {

    $fav = $pdo->prepare("
        SELECT id
        FROM favorites
        WHERE user_id = ?
          AND property_id = ?
        LIMIT 1
    ");

    $fav->execute([
        $_SESSION['user_id'],
        $id
    ]);

    $isFavorite = (bool)$fav->fetch();
}


/* =========================================================
   PAGE
========================================================= */

$pageTitle = $property['title'] . ' - StayFinder';

require_once 'includes/header.php';

$mainImage = $images[0]['image_path'] ?? '';

?>

<div class="property-detail">


    <!-- =====================================================
         PROPERTY HEADER
    ====================================================== -->

    <div class="detail-header">

        <div>

            <div class="detail-status-row">

                <span
                    class="availability-pill <?= $property['availability_status'] === 'available' ? 'available' : 'unavailable' ?>"
                >
                    <?= $property['availability_status'] === 'available'
                        ? '● Available'
                        : '● Unavailable'
                    ?>
                </span>

            </div>


            <h1>
                <?= htmlspecialchars(
                    $property['title'],
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>
            </h1>


            <div class="detail-price">

                ₱<?= number_format(
                    (float)$property['price'],
                    0
                ) ?>/month

            </div>

        </div>


        <!-- =================================================
             ACTION BUTTONS
        ================================================== -->

        <div class="detail-actions">

            <?php if (
                is_logged_in() &&
                ($_SESSION['user']['role'] ?? '') === 'rent_seeker'
            ): ?>

                <form
                    method="post"
                    action="favorite.php"
                    class="inline-form"
                >

                    <input
                        type="hidden"
                        name="csrf_token"
                        value="<?= htmlspecialchars(
                            csrf_token(),
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>"
                    >

                    <input
                        type="hidden"
                        name="property_id"
                        value="<?= (int)$property['id'] ?>"
                    >


                    <!-- SAVE BUTTON -->

                    <button
                        class="icon-button blue-action-button"
                        type="submit"
                        title="Save listing"
                    >
                        <?= $isFavorite ? '♥' : '♡' ?>
                    </button>

                </form>

            <?php endif; ?>


            <!-- SHARE BUTTON -->

            <button
                class="icon-button blue-action-button"
                type="button"
                title="Share"
                onclick="
                    if (navigator.share) {
                        navigator.share({
                            title: document.title,
                            url: location.href
                        });
                    } else if (navigator.clipboard) {
                        navigator.clipboard.writeText(location.href);
                    }
                "
            >
                ↗
            </button>

        </div>

    </div>


    <!-- =====================================================
         MAIN PROPERTY IMAGE
    ====================================================== -->

    <?php if ($mainImage): ?>

        <img
            id="mainPropertyImage"
            class="detail-hero"
            src="<?= property_image_url($mainImage) ?>"
            alt="<?= htmlspecialchars(
                $property['title'],
                ENT_QUOTES,
                'UTF-8'
            ) ?>"
        >

    <?php else: ?>

        <div class="detail-hero"></div>

    <?php endif; ?>


    <!-- =====================================================
         IMAGE GALLERY
    ====================================================== -->

    <?php if (count($images) > 1): ?>

        <div class="gallery-strip">

            <?php foreach ($images as $image): ?>

                <img
                    src="<?= property_image_url(
                        $image['image_path']
                    ) ?>"
                    alt="Property photo"
                    onclick="
                        document.getElementById(
                            'mainPropertyImage'
                        ).src = this.src
                    "
                >

            <?php endforeach; ?>

        </div>

    <?php endif; ?>


    <!-- =====================================================
         PROPERTY INFORMATION
    ====================================================== -->

    <div class="detail-meta">


        <div class="meta-box">

            <strong>
                Property Type
            </strong>

            <span>
                <?= htmlspecialchars(
                    ucwords(
                        str_replace(
                            '_',
                            ' ',
                            $property['property_type']
                        )
                    ),
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>
            </span>

        </div>


        <div class="meta-box">

            <strong>
                Capacity
            </strong>

            <span>
                <?= (int)$property['capacity'] ?> person(s)
            </span>

        </div>


        <div class="meta-box">

            <strong>
                Gender
            </strong>

            <span>
                <?= htmlspecialchars(
                    ucwords(
                        str_replace(
                            '_',
                            ' ',
                            $property['gender_preference']
                        )
                    ),
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>
            </span>

        </div>


        <div class="meta-box">

            <strong>
                Location
            </strong>

            <span>
                <?= htmlspecialchars(
                    $property['location'],
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>
            </span>

        </div>


    </div>


    <!-- =====================================================
         DESCRIPTION
    ====================================================== -->

    <div class="description-box">

        <h2>
            Description
        </h2>

        <p>
            <?= nl2br(
                htmlspecialchars(
                    $property['description']
                        ?? 'No description provided.',
                    ENT_QUOTES,
                    'UTF-8'
                )
            ) ?>
        </p>


        <p class="small">

            <strong>
                Owner/Manager:
            </strong>

            <?= htmlspecialchars(
                $property['owner_name'],
                ENT_QUOTES,
                'UTF-8'
            ) ?>

        </p>

    </div>


    <!-- =====================================================
         ACTION ROW
    ====================================================== -->

    <div class="action-row">


        <?php if (
            is_logged_in() &&
            ($_SESSION['user']['role'] ?? '') === 'rent_seeker' &&
            $property['availability_status'] === 'available'
        ): ?>

            <a
                class="primary-button"
                href="inquiry.php?property_id=<?= (int)$property['id'] ?>"
            >
                Send Inquiry
            </a>


        <?php elseif (
            $property['availability_status'] === 'unavailable'
        ): ?>

            <span class="unavailable-notice">
                This listing is currently unavailable.
            </span>


        <?php elseif (!is_logged_in()): ?>

            <a
                class="primary-button"
                href="login.php"
            >
                Login to Send Inquiry
            </a>

        <?php endif; ?>


        <a
            class="secondary-button"
            href="browse.php"
        >
            ← Back to Browse
        </a>


    </div>


</div>


<?php require_once 'includes/footer.php'; ?>
