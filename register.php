<?php
declare(strict_types=1);

require_once 'config/database.php';
require_once 'includes/csrf.php';
require_once 'includes/auth.php';
require_once 'includes/validation.php';

if (is_logged_in()) {
    redirect_by_role();
}

$errors = [];
$old = [
    'full_name' => '',
    'email' => '',
    'phone' => '',
    'role' => 'rent_seeker'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rawFullName = (string)($_POST['full_name'] ?? '');
    $rawEmail = (string)($_POST['email'] ?? '');
    $rawPhone = (string)($_POST['phone'] ?? '');
    $rawRole = (string)($_POST['role'] ?? '');
    $password = (string)($_POST['password'] ?? '');
    $confirmPassword = (string)($_POST['confirm_password'] ?? '');

    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        $errors[] = 'Invalid form request. Please refresh the page and try again.';
    }

    [$nameOk, $nameError, $fullName] = validate_full_name($rawFullName);
    if (!$nameOk) {
        $errors[] = $nameError;
    }

    [$emailOk, $emailError, $email] = validate_email_address($rawEmail);
    if (!$emailOk) {
        $errors[] = $emailError;
    }

    [$phoneOk, $phoneError, $phone] = validate_phone_number($rawPhone);
    if (!$phoneOk) {
        $errors[] = $phoneError;
    }

    if (!validate_account_role($rawRole)) {
        $errors[] = 'Please choose a valid account type.';
        $role = 'rent_seeker';
    } else {
        $role = $rawRole;
    }

    [$passwordOk, $passwordError] = validate_password($password, $email, $fullName);
    if (!$passwordOk) {
        $errors[] = $passwordError;
    }

    if (!hash_equals($password, $confirmPassword)) {
        $errors[] = 'Passwords do not match.';
    }

    // Only show cleaned, non-sensitive values back in the form.
    $old['full_name'] = $fullName;
    $old['email'] = $email;
    $old['phone'] = $phone ?? '';
    $old['role'] = $role;

    if (!$errors) {
        try {
            $check = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
            $check->execute([$email]);

            if ($check->fetch()) {
                $errors[] = 'An account with that email already exists.';
            } else {
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

                if ($hashedPassword === false) {
                    $errors[] = 'Unable to secure the password. Please try again.';
                } else {
                    $stmt = $pdo->prepare(
                        'INSERT INTO users (full_name, email, phone, password, role)
                         VALUES (:full_name, :email, :phone, :password, :role)'
                    );

                    $stmt->execute([
                        ':full_name' => $fullName,
                        ':email' => $email,
                        ':phone' => $phone,
                        ':password' => $hashedPassword,
                        ':role' => $role
                    ]);

                    header('Location: login.php?registered=1');
                    exit;
                }
            }
        } catch (PDOException $e) {
            // Do not expose SQL errors to the user.
            if ((int)$e->errorInfo[1] === 1062) {
                $errors[] = 'An account with that email already exists.';
            } else {
                $errors[] = 'Registration could not be completed. Please try again later.';
            }
        }
    }
}

$pageTitle = 'Register - StayFinder';
require_once 'includes/header.php';
?>

<div class="form-card">
    <h1 style="text-align:center;">Create Your Account</h1>
    <p class="small" style="text-align:center;">Choose whether you are looking for a rental or managing a property.</p>

    <?php if ($errors): ?>
        <div class="alert error">
            <?php foreach (array_unique($errors) as $error): ?>
                <div><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <form method="post" novalidate autocomplete="on">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>">

        <div class="form-group">
            <label for="full_name">Full Name</label>
            <input id="full_name" name="full_name" maxlength="100" required
                   autocomplete="name" value="<?= htmlspecialchars($old['full_name'], ENT_QUOTES, 'UTF-8') ?>">
        </div>

        <div class="form-group">
            <label for="email">Email</label>
            <input id="email" type="email" name="email" maxlength="150" required
                   autocomplete="email" value="<?= htmlspecialchars($old['email'], ENT_QUOTES, 'UTF-8') ?>">
        </div>

        <div class="form-group">
            <label for="phone">Philippine Mobile Number</label>
            <input id="phone" name="phone" maxlength="20"
                   placeholder="09171234567" autocomplete="tel"
                   value="<?= htmlspecialchars($old['phone'], ENT_QUOTES, 'UTF-8') ?>">
        </div>

        <div class="form-group" style="max-width: 320px;">
            <label>Account Type</label>
            <div class="radio-group">
                <label class="role-button <?= $old['role'] === 'rent_seeker' ? 'active' : '' ?>">
                    <input type="radio" name="role" value="rent_seeker"
                           <?= $old['role'] === 'rent_seeker' ? 'checked' : '' ?> hidden>
                    Rent Seeker
                </label>
                <label class="role-button <?= $old['role'] === 'owner' ? 'active' : '' ?>">
                    <input type="radio" name="role" value="owner"
                           <?= $old['role'] === 'owner' ? 'checked' : '' ?> hidden>
                    Owner / Manager
                </label>
            </div>
        </div>

        <div class="form-group">
            <label for="password">Password</label>
            <div class="password-field">
                <input
                    id="password"
                    type="password"
                    name="password"
                    minlength="10"
                    maxlength="72"
                    required
                    autocomplete="new-password"
                >
                <button
                    type="button"
                    class="password-toggle"
                    data-password-target="password"
                    aria-label="Show password"
                    title="Show password"
                >
                    <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                        <path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z" fill="none" stroke="currentColor" stroke-width="1.8"/>
                        <circle cx="12" cy="12" r="2.5" fill="none" stroke="currentColor" stroke-width="1.8"/>
                    </svg>
                </button>
            </div>
            <p class="small">At least 10 characters with uppercase, lowercase, number, and special character.</p>
        </div>

        <div class="form-group">
            <label for="confirm_password">Confirm Password</label>
            <div class="password-field">
                <input
                    id="confirm_password"
                    type="password"
                    name="confirm_password"
                    minlength="10"
                    maxlength="72"
                    required
                    autocomplete="new-password"
                >
                <button
                    type="button"
                    class="password-toggle"
                    data-password-target="confirm_password"
                    aria-label="Show confirm password"
                    title="Show password"
                >
                    <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                        <path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z" fill="none" stroke="currentColor" stroke-width="1.8"/>
                        <circle cx="12" cy="12" r="2.5" fill="none" stroke="currentColor" stroke-width="1.8"/>
                    </svg>
                </button>
            </div>
        </div>

        <button type="submit" style="width: 300px; display: block; margin: 1.5rem auto;">Register</button>
    </form>

    <p style="text-align:center;">Already have an account? <a href="login.php">Login</a></p>
</div>

<script>
document.querySelectorAll('.password-toggle').forEach(function (button) {
    button.addEventListener('click', function () {
        const input = document.getElementById(
            button.dataset.passwordTarget
        );

        const isHidden = input.type === 'password';

        input.type = isHidden ? 'text' : 'password';

        button.classList.toggle('is-visible', isHidden);

        button.setAttribute(
            'aria-label',
            isHidden ? 'Hide password' : 'Show password'
        );

        button.setAttribute(
            'title',
            isHidden ? 'Hide password' : 'Show password'
        );
    });
});

document.querySelectorAll('.role-button input[type="radio"]').forEach(function(radio) {
    radio.addEventListener('change', function() {
        if (radio.checked) {
            document.querySelectorAll('.role-button').forEach(function(label) {
                label.classList.remove('active');
            });
            radio.parentElement.classList.add('active');
        }
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>
