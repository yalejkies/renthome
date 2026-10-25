<?php
declare(strict_types=1);
require_once __DIR__ . '/config/database.php';

$message = '';
$error = '';
$adminExists = (bool)$pdo->query("SELECT 1 FROM users WHERE role = 'admin' LIMIT 1")->fetchColumn();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$adminExists) {
    $name = trim((string)($_POST['full_name'] ?? ''));
    $email = trim((string)($_POST['email'] ?? ''));
    $password = (string)($_POST['password'] ?? '');
    $confirm = (string)($_POST['confirm_password'] ?? '');

    if ($name === '' || strlen($name) > 100) $error = 'Please enter a valid administrator name.';
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 150) $error = 'Please enter a valid email address.';
    elseif (strlen($password) < 10 || strlen($password) > 72) $error = 'Password must be 10 to 72 characters.';
    elseif ($password !== $confirm) $error = 'Passwords do not match.';
    elseif (!preg_match('/[A-Z]/', $password) || !preg_match('/[a-z]/', $password) || !preg_match('/\d/', $password) || !preg_match('/[^A-Za-z0-9]/', $password)) $error = 'Password must contain uppercase, lowercase, number, and special character.';
    else {
        try {
            $stmt = $pdo->prepare('INSERT INTO users (full_name,email,password,role,status) VALUES (?,?,?,\'admin\',\'active\')');
            $stmt->execute([$name, $email, password_hash($password, PASSWORD_DEFAULT)]);
            $adminExists = true;
            $message = 'Setup complete. Your administrator account has been created. Delete or rename setup.php after setup for extra safety, then log in.';
        } catch (PDOException $e) {
            $error = 'The administrator account could not be created. Please try another email address.';
        }
    }
}
?>
<!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>StayFinder Setup</title>
<style>body{font-family:Arial,sans-serif;background:#f4f7fb;margin:0;padding:40px}.box{max-width:520px;margin:auto;background:#fff;padding:30px;border-radius:14px;box-shadow:0 8px 30px rgba(0,0,0,.08)}h1{color:#073b6d}label{display:block;margin:14px 0 6px;font-weight:600}input{width:100%;box-sizing:border-box;padding:11px;border:1px solid #ccd6e2;border-radius:8px}button{margin-top:20px;width:100%;padding:12px;border:0;border-radius:8px;background:#126db3;color:#fff;font-weight:700;cursor:pointer}.ok{background:#eaf8ef;padding:12px;border-radius:8px;color:#166534}.err{background:#fff0f0;padding:12px;border-radius:8px;color:#991b1b}.small{color:#64748b;font-size:14px}a{color:#126db3}</style></head>
<body><div class="box"><h1>StayFinder Setup</h1>
<p class="small">The database and required tables have already been created automatically.</p>
<?php if ($message): ?><div class="ok"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></div><p><a href="login.php">Go to Login</a></p>
<?php elseif ($adminExists): ?><div class="ok">StayFinder is already initialized and an administrator account exists.</div><p><a href="login.php">Go to Login</a></p>
<?php else: ?>
<?php if ($error): ?><div class="err"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
<form method="post"><label>Administrator Name</label><input name="full_name" required maxlength="100"><label>Administrator Email</label><input type="email" name="email" required maxlength="150"><label>Password</label><input type="password" name="password" required minlength="10" maxlength="72"><label>Confirm Password</label><input type="password" name="confirm_password" required minlength="10" maxlength="72"><button type="submit">Create Administrator Account</button></form>
<p class="small">Use this page only during first-time setup. After creating the admin account, remove or rename <strong>setup.php</strong>.</p>
<?php endif; ?></div></body></html>
