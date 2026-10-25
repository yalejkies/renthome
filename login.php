<?php
declare(strict_types=1);
require_once 'config/database.php'; require_once 'includes/csrf.php'; require_once 'includes/auth.php'; require_once 'includes/validation.php';
if (is_logged_in()) redirect_by_role();
$errors=[]; $email='';
if ($_SERVER['REQUEST_METHOD']==='POST') {
    $rawEmail=(string)($_POST['email']??''); $password=(string)($_POST['password']??'');
    if (!verify_csrf($_POST['csrf_token']??null)) $errors[]='Invalid form request. Please refresh the page and try again.';
    [$emailOk,$emailError,$email]=validate_email_address($rawEmail); if(!$emailOk)$errors[]=$emailError;
    if($password==='')$errors[]='Please enter your password.';
    if(!$errors){
        try{
            $stmt=$pdo->prepare('SELECT id,full_name,email,phone,password,role,status FROM users WHERE email=? LIMIT 1'); $stmt->execute([$email]); $user=$stmt->fetch();
            if(!$user || !password_verify($password,$user['password'])) $errors[]='Incorrect email or password.';
            elseif($user['status']!=='active') $errors[]='This account is not currently active.';
            else { session_regenerate_id(true); unset($user['password']); $_SESSION['user_id']=$user['id']; $_SESSION['user']=$user; redirect_by_role(); }
        }catch(PDOException $e){$errors[]='Login could not be completed. Please try again later.';}
    }
}
$pageTitle='Login - StayFinder'; require_once 'includes/header.php';
?>
<div class="auth-shell">
<div class="form-card">
    <div class="auth-tabs"><a class="active" href="login.php">Login</a><a href="register.php">Register</a></div>
    <h1 style="text-align:center;font-size:22px;margin-top:0;">Welcome Back</h1>
    <p class="small" style="text-align:center;margin-bottom:22px;">Sign in to save listings and send inquiries.</p>
    <?php if(isset($_GET['registered'])): ?><div class="alert success">Registration successful. You can now log in.</div><?php endif; ?>
    <?php if($errors): ?><div class="alert error"><?php foreach(array_unique($errors) as $error): ?><div><?= htmlspecialchars($error,ENT_QUOTES,'UTF-8') ?></div><?php endforeach; ?></div><?php endif; ?>
    <form method="post" novalidate autocomplete="on">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token(),ENT_QUOTES,'UTF-8') ?>">
        <div class="form-group"><label for="email">Email or Mobile Number</label><input id="email" type="email" name="email" maxlength="150" required autocomplete="email" placeholder="example@email.com" value="<?= htmlspecialchars($email,ENT_QUOTES,'UTF-8') ?>"></div>
        <div class="form-group"><label for="password">Password</label><input id="password" type="password" name="password" maxlength="72" required autocomplete="current-password" placeholder="Enter your password"></div>
        <button type="submit" style="width:100%;">Login</button>
    </form>
    <div style="text-align:center;margin:18px 0;color:#94a3b8;">OR</div>
    <a class="secondary-button" style="width:100%;" href="register.php">Create an Account</a>
</div>
</div>
<?php require_once 'includes/footer.php'; ?>
