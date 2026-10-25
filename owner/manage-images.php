<?php
declare(strict_types=1);
require_once '../config/database.php';
require_once '../config/app.php';
require_once '../includes/auth.php';
require_once '../includes/csrf.php';
require_once '../includes/listing_validation.php';
require_role('owner');

$ownerId = (int)$_SESSION['user_id'];
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) { http_response_code(400); exit('Invalid listing ID.'); }
$stmt = $pdo->prepare('SELECT id, title, status FROM properties WHERE id = ? AND owner_id = ? LIMIT 1');
$stmt->execute([$id, $ownerId]);
$property = $stmt->fetch();
if (!$property) { http_response_code(404); exit('Listing not found.'); }
$errors = []; $success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) { $errors[] = 'Invalid form request. Please try again.'; }
    [$images, $imageErrors] = validate_uploaded_images($_FILES['images'] ?? []);
    $errors = array_merge($errors, $imageErrors);
    if (!$errors && count($images) < 1) $errors[] = 'Choose at least one JPG, PNG, or WEBP image.';
    if (!$errors) {
        $uploadDir = dirname(__DIR__) . '/uploads/properties';
        if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true) && !is_dir($uploadDir)) $errors[] = 'Could not create the property image folder.';
        if (!$errors) {
            try {
                $pdo->beginTransaction();
                // Remove only database references to files that are already missing.
                $old = $pdo->prepare('SELECT id, image_path FROM property_images WHERE property_id = ?');
                $old->execute([$id]);
                $deleteIds = [];
                foreach ($old->fetchAll() as $row) {
                    $relative = ltrim(str_replace('\\', '/', (string)$row['image_path']), '/');
                    $relative = preg_replace('~^renthome/~', '', $relative) ?? $relative;
                    if (str_contains($relative, '..') || !is_file(dirname(__DIR__) . '/' . $relative)) $deleteIds[] = (int)$row['id'];
                }
                if ($deleteIds) {
                    $del = $pdo->prepare('DELETE FROM property_images WHERE id = ? AND property_id = ?');
                    foreach ($deleteIds as $imageId) $del->execute([$imageId, $id]);
                }
                foreach ($images as $image) {
                    $filename = bin2hex(random_bytes(16)) . '.' . $image['extension'];
                    if (!move_uploaded_file($image['tmp_name'], $uploadDir . '/' . $filename)) throw new RuntimeException('An image could not be saved.');
                    $img = $pdo->prepare('INSERT INTO property_images (property_id, image_path) VALUES (?, ?)');
                    $img->execute([$id, 'uploads/properties/' . $filename]);
                }
                $pdo->commit();
                header('Location: manage-images.php?id=' . $id . '&saved=1'); exit;
            } catch (Throwable $e) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                $errors[] = 'The images could not be saved. Check that Apache can write to the uploads folder, then try again.';
            }
        }
    }
}
$imgStmt = $pdo->prepare('SELECT id, image_path FROM property_images WHERE property_id = ? ORDER BY id');
$imgStmt->execute([$id]); $images = $imgStmt->fetchAll();
$pageTitle = 'Manage Property Photos - StayFinder'; require_once '../includes/header.php';
?>
<main class="container" style="max-width:980px;padding-top:32px;padding-bottom:48px">
  <a href="dashboard.php">← Back to Dashboard</a>
  <h1 style="margin-top:18px">Manage Property Photos</h1>
  <p><?= htmlspecialchars($property['title'], ENT_QUOTES, 'UTF-8') ?></p>
  <?php if (isset($_GET['saved'])): ?><div class="alert success">Photos uploaded successfully. Refresh the listing to view them.</div><?php endif; ?>
  <?php if ($errors): ?><div class="alert error"><?php foreach ($errors as $e): ?><p><?= htmlspecialchars($e, ENT_QUOTES, 'UTF-8') ?></p><?php endforeach; ?></div><?php endif; ?>
  <div class="card" style="margin:18px 0">
    <h2>Current photos</h2>
    <?php if (!$images): ?><p>No photo records are attached to this listing yet.</p><?php else: ?>
      <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:14px">
      <?php foreach ($images as $image): ?><div><img src="<?= htmlspecialchars(property_image_url($image['image_path']), ENT_QUOTES, 'UTF-8') ?>" alt="Property photo" style="width:100%;height:130px;object-fit:cover;border-radius:8px"><p class="small"><?= is_file(dirname(__DIR__) . '/' . ltrim((string)$image['image_path'], '/')) ? 'File found' : 'Original file missing — placeholder shown' ?></p></div><?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
  <div class="card">
    <h2>Upload replacement photos</h2>
    <p>Choose photos from your computer. JPG, PNG, or WEBP; up to 8 images, 5 MB each. Missing image references will be cleaned up automatically; existing image files will not be deleted.</p>
    <form method="post" enctype="multipart/form-data">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
      <label for="images">Property photos</label><input id="images" type="file" name="images[]" accept=".jpg,.jpeg,.png,.webp" multiple required>
      <div style="margin-top:18px"><button type="submit">Upload Photos</button></div>
    </form>
  </div>
</main>
<?php require_once '../includes/footer.php'; ?>
