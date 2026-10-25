<?php
declare(strict_types=1);

require_once '../config/database.php';
require_once '../config/app.php';
require_once '../includes/auth.php';
require_once '../includes/renewal.php';
require_once '../includes/csrf.php';

ensure_renewal_schema($pdo);

require_role('admin');


/* =========================================================
   DASHBOARD COUNTS
========================================================= */

$pendingCount = (int)$pdo->query(
    "SELECT COUNT(*) 
     FROM properties 
     WHERE status = 'pending'"
)->fetchColumn();


$userCount = (int)$pdo->query(
    "SELECT COUNT(*) 
     FROM users"
)->fetchColumn();


$reportCount = (int)$pdo->query(
    "SELECT COUNT(*) 
     FROM reports 
     WHERE status = 'pending'"
)->fetchColumn();


$renewalPendingCount = (int)$pdo->query(
    "SELECT COUNT(*) 
     FROM renewal_payments 
     WHERE status = 'pending'"
)->fetchColumn();


$approvedCount = (int)$pdo->query(
    "SELECT COUNT(*) 
     FROM properties 
     WHERE status = 'approved'"
)->fetchColumn();


/* =========================================================
   PENDING LISTINGS
========================================================= */

$stmt = $pdo->query(
    "SELECT 
        p.id,
        p.title,
        p.property_type,
        p.location,
        p.price,
        p.capacity,
        p.gender_preference,
        p.created_at,
        u.full_name AS owner_name,

        (
            SELECT pi.image_path
            FROM property_images pi
            WHERE pi.property_id = p.id
            ORDER BY pi.id
            LIMIT 1
        ) AS image_path

     FROM properties p

     JOIN users u 
        ON u.id = p.owner_id

     WHERE p.status = 'pending'

     ORDER BY p.created_at DESC

     LIMIT 8"
);

$pendingListings = $stmt->fetchAll();


/* =========================================================
   PAGE
========================================================= */

$pageTitle = 'Admin Dashboard - StayFinder';

require_once '../includes/header.php';

$activeAdmin = 'dashboard';

require_once '../includes/admin_nav.php';

?>


<!-- ========================================================
     ADMIN DASHBOARD HEADER
========================================================= -->

<div class="admin-page-head admin-dashboard-head">

    <div>

        <p class="admin-eyebrow">
            Administrator
        </p>

        <h1>
            Admin Dashboard
        </h1>

        <p>
            Monitor StayFinder and review rental listings that are waiting for approval.
        </p>

    </div>


    <div class="admin-account-pill">

        ●

        <?= htmlspecialchars(
            $_SESSION['user']['full_name'],
            ENT_QUOTES,
            'UTF-8'
        ) ?>

    </div>

</div>



<!-- ========================================================
     AUTOMATION NOTICE
========================================================= -->

<?php if (!empty($_GET['automation'])): ?>

    <div class="admin-automation-notice">

        <?= htmlspecialchars(
            (string)$_GET['automation'],
            ENT_QUOTES,
            'UTF-8'
        ) ?>

    </div>

<?php endif; ?>



<!-- ========================================================
     STATISTICS
========================================================= -->

<div class="admin-stat-grid admin-stat-grid-clean">


    <!-- NEEDS APPROVAL -->

    <a
        class="admin-stat-card warning"
        href="listings.php?status=pending"
    >

        <span class="admin-stat-icon">
            ◷
        </span>

        <span class="admin-stat-content">

            <strong>
                <?= $pendingCount ?>
            </strong>

            <small>
                Needs Approval
            </small>

        </span>

    </a>



    <!-- APPROVED LISTINGS -->

    <a
        class="admin-stat-card"
        href="listings.php?status=approved"
    >

        <span class="admin-stat-icon">
            ✓
        </span>

        <span class="admin-stat-content">

            <strong>
                <?= $approvedCount ?>
            </strong>

            <small>
                Approved Listings
            </small>

        </span>

    </a>



    <!-- REGISTERED USERS -->

    <a
        class="admin-stat-card"
        href="users.php"
    >

        <span class="admin-stat-icon">
            ♙
        </span>

        <span class="admin-stat-content">

            <strong>
                <?= $userCount ?>
            </strong>

            <small>
                Registered Users
            </small>

        </span>

    </a>



    <!-- PENDING RENEWALS -->

    <a
        class="admin-stat-card renewal-stat"
        href="renewals.php?status=pending"
    >

        <span class="admin-stat-icon">
            ↻
        </span>

        <span class="admin-stat-content">

            <strong>
                <?= $renewalPendingCount ?>
            </strong>

            <small>
                Pending Renewals
            </small>

        </span>

    </a>



    <!-- PENDING REPORTS -->

    <a
        class="admin-stat-card danger"
        href="reports.php"
    >

        <span class="admin-stat-icon">
            !
        </span>

        <span class="admin-stat-content">

            <strong>
                <?= $reportCount ?>
            </strong>

            <small>
                Pending Reports
            </small>

        </span>

    </a>

</div>



<!-- ========================================================
     MAIN DASHBOARD
========================================================= -->

<div class="admin-dashboard-grid">


    <!-- ====================================================
         APPROVAL QUEUE
    ===================================================== -->

    <section class="admin-panel admin-approval-panel">


        <!-- PANEL HEADER -->

        <div class="admin-panel-head admin-dashboard-panel-head">

            <div>

                <p class="admin-eyebrow">
                    Approval Queue
                </p>

                <h2>
                    Listings Waiting for Review
                </h2>

                <p>
                    Open a listing to check its details, photos, and Mayor's Permit before approving or rejecting it.
                </p>

            </div>


            <a
                class="admin-outline-btn"
                href="listings.php?status=pending"
            >
                View All
            </a>

        </div>



        <!-- =================================================
             EMPTY STATE
        ================================================== -->

        <?php if (!$pendingListings): ?>

            <div class="admin-empty compact">

                <div class="admin-empty-icon">
                    ✓
                </div>

                <h3>
                    All caught up
                </h3>

                <p>
                    There are no listings waiting for administrator review.
                </p>

            </div>


        <?php else: ?>


            <!-- =============================================
                 LISTINGS
            ============================================== -->

            <div class="admin-queue-list">


                <?php foreach ($pendingListings as $listing): ?>


                    <article class="admin-queue-row">


                        <!-- =================================
                             PROPERTY IMAGE
                        ================================== -->

                        <div class="admin-queue-image">

                            <img
                                src="<?= property_image_url(
                                    $listing['image_path'] ?? null
                                ) ?>"
                                alt="Property photo"
                            >

                        </div>



                        <!-- =================================
                             PROPERTY INFORMATION
                        ================================== -->

                        <div class="admin-queue-info">


                            <div class="admin-queue-title-row">

                                <div>

                                    <h3>
                                        <?= htmlspecialchars(
                                            $listing['title'],
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>
                                    </h3>

                                    <p>
                                        <?= htmlspecialchars(
                                            $listing['location'],
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>
                                    </p>

                                </div>

                            </div>



                            <!-- PROPERTY DETAILS -->

                            <div class="admin-queue-meta">


                                <span>
                                    ₱<?= number_format(
                                        (float)$listing['price'],
                                        2
                                    ) ?>/month
                                </span>


                                <span>

                                    <?= htmlspecialchars(
                                        ucwords(
                                            str_replace(
                                                '_',
                                                ' ',
                                                $listing['property_type']
                                            )
                                        ),
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>

                                </span>


                                <span>

                                    Owner:
                                    <?= htmlspecialchars(
                                        $listing['owner_name'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>

                                </span>


                            </div>

                        </div>



                        <!-- =================================
                             STATUS + REVIEW
                        ================================== -->

                        <div class="admin-queue-actions">


                            <!-- SMALL PENDING -->

                            <span class="admin-status pending">
                                Pending
                            </span>


                            <!-- BIG REVIEW -->

                            <a
                                class="admin-primary-btn"
                                href="listing-review.php?id=<?= (int)$listing['id'] ?>"
                            >
                                Review
                            </a>


                        </div>


                    </article>


                <?php endforeach; ?>


            </div>


        <?php endif; ?>


    </section>



    <!-- ====================================================
         RIGHT SIDE
    ===================================================== -->

    <aside class="admin-dashboard-side">


        <!-- ================================================
             QUICK ACTIONS
        ================================================= -->

        <section class="admin-panel admin-quick-panel">

            <p class="admin-eyebrow">
                Quick Actions
            </p>

            <h2>
                Manage StayFinder
            </h2>



            <!-- REVIEW LISTINGS -->

            <a
                class="admin-quick-link"
                href="listings.php?status=pending"
            >

                <span>
                    ▣
                </span>

                <div>

                    <strong>
                        Review Listings
                    </strong>

                    <small>
                        Approve or reject pending submissions.
                    </small>

                </div>

            </a>



            <!-- MANAGE USERS -->

            <a
                class="admin-quick-link"
                href="users.php"
            >

                <span>
                    ♙
                </span>

                <div>

                    <strong>
                        Manage Users
                    </strong>

                    <small>
                        View registered rent seekers and owners.
                    </small>

                </div>

            </a>



            <!-- REVIEW REPORTS -->

            <a
                class="admin-quick-link"
                href="reports.php"
            >

                <span>
                    ⚠
                </span>

                <div>

                    <strong>
                        Review Reports
                    </strong>

                    <small>
                        Check submitted reports that need attention.
                    </small>

                </div>

            </a>



            <!-- REVIEW RENEWALS -->

            <a
                class="admin-quick-link"
                href="renewals.php?status=pending"
            >

                <span>
                    ↻
                </span>

                <div>

                    <strong>
                        Review Renewals
                    </strong>

                    <small>
                        Verify renewal payments and extend listings.
                    </small>

                </div>

            </a>



            <!-- RUN RENEWAL CHECK -->

            <form
                method="post"
                action="run-renewal-automation.php"
                class="admin-automation-form"
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


                <button
                    type="submit"
                    class="admin-quick-link admin-automation-button"
                >

                    <span>
                        ⟳
                    </span>

                    <div>

                        <strong>
                            Run Renewal Check
                        </strong>

                        <small>
                            Send due reminders and expired-listing notices now.
                        </small>

                    </div>

                </button>

            </form>


        </section>



        <!-- ================================================
             APPROVAL REMINDER
        ================================================= -->

        <section class="admin-panel admin-note-panel">

            <p class="admin-eyebrow">
                Approval Reminder
            </p>

            <h2>
                Before approving
            </h2>


            <ul>

                <li>
                    Check the property information.
                </li>

                <li>
                    Check the uploaded property photos.
                </li>

                <li>
                    Check the owner's Mayor's Permit.
                </li>

                <li>
                    Approve only when the submission is complete.
                </li>

            </ul>

        </section>


    </aside>


</div>



</section>
</div>


<?php require_once '../includes/footer.php'; ?>