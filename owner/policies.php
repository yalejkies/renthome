<?php
declare(strict_types=1);

require_once '../includes/auth.php';
require_once '../includes/csrf.php';
require_login();

if (($_SESSION['user']['role'] ?? '') !== 'owner') {
    http_response_code(403);
    exit('Access denied.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        exit('Invalid form request.');
    }

    if (!isset($_POST['agree'])) {
        header('Location: policies.php?error=1');
        exit;
    }

    require_once '../config/database.php';

    $stmt = $pdo->prepare(
        'UPDATE users SET owner_policy_accepted_at = NOW() WHERE id = ? AND role = \'owner\''
    );
    $stmt->execute([$_SESSION['user_id']]);

    header('Location: permit.php');
    exit;
}

$pageTitle = 'Owner Policies - StayFinder';
require_once '../includes/header.php';
$ownerStep = 2;
require_once 'flow.php';
?>

<style>
.policy-checkbox label {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-weight: 600;
    cursor: pointer;
    user-select: none;
    font-size: 1rem;
    color: #202040;
}

.policy-checkbox input[type="checkbox"] {
    width: 20px;
    height: 20px;
    cursor: pointer;
    margin: 0;
    flex-shrink: 0;
    accent-color: #007bff;
}

.btn-primary {
    width: 100%;
    max-width: 320px;
    padding: 0.7rem 1.25rem;
    background-color: #007bff;
    color: white;
    border: none;
    border-radius: 6px;
    font-weight: 700;
    font-size: 1rem;
    cursor: pointer;
    margin-top: 1.5rem;
    transition: background-color 0.3s ease;
    display: block;
    text-align: center;
}

.btn-primary:hover {
    background-color: #0056b3;
}
</style>

<div class="owner-policy-card form-card">
    <div class="owner-form-header">
        <h1>Terms and Policies</h1>
        <p>Please read and agree to the owner/manager policies before continuing.</p>
    </div>

    <?php if (isset($_GET['error'])): ?>
        <div class="alert error">You must agree to the policies before continuing.</div>
    <?php endif; ?>

    <div class="owner-policy-list">
        <div class="owner-policy-item"><span>✓</span><div><strong>Accurate information.</strong><br>Provide truthful property details, price, location, capacity, and availability.</div></div>
        <div class="owner-policy-item"><span>✓</span><div><strong>Authorized listing.</strong><br>Only advertise a property that you are allowed to manage or represent.</div></div>
        <div class="owner-policy-item"><span>✓</span><div><strong>Appropriate photos.</strong><br>Upload clear and relevant property images only.</div></div>
        <div class="owner-policy-item"><span>✓</span><div><strong>Other information.</strong><br>Put electricity/water bill details, Wi-Fi, curfew, transportation, rules, and other notes in the description.</div></div>
        <div class="owner-policy-item"><span>✓</span><div><strong>Admin review.</strong><br>Every new listing is reviewed before it becomes visible to rent seekers.</div></div>
    </div>

    <div class="owner-info-box">
        <strong>Next:</strong> After agreeing, you will upload your Mayor's Permit, then create your listing, upload photos, review the information, and submit it for administrator approval.
    </div>

    <form method="post" novalidate>
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
        <div class="policy-checkbox" style="margin-top:1rem; margin-bottom:1.5rem;">
            <label>
                <input type="checkbox" name="agree" value="1" required>
                I have read and agree to the owner/manager policies.
            </label>
        </div>
        <button type="submit" class="btn-primary">Agree and Continue →</button>
    </form>
</div>

<?php require_once '../includes/footer.php'; ?>
