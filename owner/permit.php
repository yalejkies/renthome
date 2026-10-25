<?php
declare(strict_types=1);

require_once '../config/database.php';
require_once '../config/app.php';
require_once '../includes/auth.php';
require_once '../includes/csrf.php';

require_role('owner');

$check = $pdo->prepare(
    'SELECT owner_policy_accepted_at, mayor_permit_path, mayor_permit_uploaded_at
     FROM users WHERE id = ? AND role = \'owner\' LIMIT 1'
);
$check->execute([$_SESSION['user_id']]);
$owner = $check->fetch();

if (!$owner || empty($owner['owner_policy_accepted_at'])) {
    header('Location: policies.php');
    exit;
}

$errors = [];
$allowedMime = [
    'application/pdf' => 'pdf',
    'image/jpeg' => 'jpg',
    'image/png' => 'png',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        $errors[] = 'Invalid form request. Please try again.';
    }

    $file = $_FILES['permit'] ?? null;
    if (!$errors && (!$file || !isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK)) {
        $errors[] = 'Please upload a Mayor\'s Permit.';
    }

    if (!$errors) {
        if ((int)$file['size'] > MAX_UPLOAD_SIZE) {
            $errors[] = 'The permit file must not exceed 5 MB.';
        } else {
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime = $finfo->file($file['tmp_name']);
            if (!isset($allowedMime[$mime])) {
                $errors[] = 'Only PDF, JPG, or PNG files are allowed.';
            }
        }
    }

    if (!$errors) {
        $extension = $allowedMime[$mime];
        $uploadDir = dirname(__DIR__) . '/uploads/permits';
        if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true)) {
            $errors[] = 'The permit upload folder could not be created.';
        }
    }

    if (!$errors) {
        $filename = bin2hex(random_bytes(16)) . '.' . $extension;
        $destination = $uploadDir . '/' . $filename;
        $relativePath = 'uploads/permits/' . $filename;

        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            $errors[] = 'The permit could not be saved. Please try again.';
        } else {
            $oldPath = (string)($owner['mayor_permit_path'] ?? '');

            $stmt = $pdo->prepare(
                'UPDATE users
                 SET mayor_permit_path = ?, mayor_permit_uploaded_at = NOW()
                 WHERE id = ? AND role = \'owner\''
            );
            $stmt->execute([$relativePath, $_SESSION['user_id']]);

            if ($oldPath !== '') {
                $oldFile = dirname(__DIR__) . '/' . ltrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $oldPath), '/\\');
                if (is_file($oldFile)) {
                    @unlink($oldFile);
                }
            }

            header('Location: create-listing.php');
            exit;
        }
    }
}

$pageTitle = 'Mayor\'s Permit - StayFinder';
require_once '../includes/header.php';
$ownerStep = 3;
require_once 'flow.php';
?>

<div class="owner-form-shell">
    <div class="owner-form-header">
        <h1>Upload Mayor's Permit</h1>
        <p>After agreeing to the terms and policies, upload your Mayor's Permit before creating a rental listing.</p>
    </div>

    <div class="owner-form-card permit-card">
        <?php if ($errors): ?>
            <div class="alert error">
                <?php foreach ($errors as $error): ?>
                    <div><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="permit-requirement-box">
            <div class="permit-icon">▣</div>
            <div>
                <h2>Required before creating a listing</h2>
                <p>Submit a clear and readable copy of the Mayor's Permit for your rental property or business.</p>
                <ul>
                    <li>Accepted files: PDF, JPG, or PNG</li>
                    <li>Maximum file size: 5 MB</li>
                    <li>The permit is used for administrator review.</li>
                </ul>
            </div>
        </div>

        <?php if (!empty($owner['mayor_permit_path'])): ?>
            <div class="permit-uploaded-box">
                <div>
                    <strong>Mayor's Permit uploaded</strong>
                    <span class="small">Uploaded <?= htmlspecialchars(date('M d, Y', strtotime((string)$owner['mayor_permit_uploaded_at'])), ENT_QUOTES, 'UTF-8') ?></span>
                </div>
                <a class="secondary-button" target="_blank" rel="noopener" href="<?= htmlspecialchars(base_url('owner/view-permit.php'), ENT_QUOTES, 'UTF-8') ?>">View Permit</a>
            </div>
            <p class="small">Uploading a new file will replace your previous permit.</p>
        <?php endif; ?>

        <form method="post" enctype="multipart/form-data" class="permit-upload-form">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
            <div class="form-group">
                <label for="permit">Mayor's Permit</label>
                <input id="permit" type="file" name="permit" accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png" required>
            </div>
            <div class="owner-actions">
                <button type="submit"><?= empty($owner['mayor_permit_path']) ? 'Upload Permit and Continue →' : 'Replace Permit and Continue →' ?></button>
                <a class="secondary-button" href="dashboard.php">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
