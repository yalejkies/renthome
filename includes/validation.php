<?php
declare(strict_types=1);

/**
 * Centralized input validation for StayFinder.
 *
 * Important:
 * - Validate input before saving it.
 * - Use prepared statements for database queries.
 * - Escape output with htmlspecialchars() when displaying user data.
 * - Do NOT htmlspecialchars() passwords before hashing/verifying them.
 */

function clean_text(string $value, int $maxLength): string
{
    // Remove control characters and normalize repeated whitespace.
    $value = preg_replace('/[\x00-\x1F\x7F]/u', '', $value) ?? '';
    $value = trim($value);
    $value = preg_replace('/\s+/u', ' ', $value) ?? $value;

    if (function_exists('mb_substr')) {
        return mb_substr($value, 0, $maxLength, 'UTF-8');
    }

    return substr($value, 0, $maxLength);
}

function text_length(string $value): int
{
    return function_exists('mb_strlen')
        ? mb_strlen($value, 'UTF-8')
        : strlen($value);
}

function normalize_email(string $email): string
{
    // Email addresses are case-insensitive for the domain and normally treated
    // case-insensitively by the application for account lookup.
    return strtolower(trim($email));
}

function normalize_phone(string $phone): ?string
{
    $phone = trim($phone);

    if ($phone === '') {
        return null;
    }

    // Accept common Philippine formats and normalize to +639XXXXXXXXX.
    $digits = preg_replace('/[\s().-]+/', '', $phone) ?? '';

    if (preg_match('/^09\d{9}$/', $digits)) {
        return '+63' . substr($digits, 1);
    }

    if (preg_match('/^\+639\d{9}$/', $digits)) {
        return $digits;
    }

    if (preg_match('/^639\d{9}$/', $digits)) {
        return '+' . $digits;
    }

    return null;
}

function validate_full_name(string $name): array
{
    $name = clean_text($name, 100);

    if ($name === '') {
        return [false, 'Full name is required.', ''];
    }

    if (text_length($name) < 2) {
        return [false, 'Full name must contain at least 2 characters.', $name];
    }

    // Allows letters from different languages, spaces, periods, apostrophes and hyphens.
    if (!preg_match("/^[\\p{L}\\p{M} .'-]+$/u", $name)) {
        return [false, 'Full name contains invalid characters.', $name];
    }

    return [true, '', $name];
}

function validate_email_address(string $email): array
{
    $email = normalize_email($email);

    if ($email === '') {
        return [false, 'Email address is required.', ''];
    }

    if (strlen($email) > 150) {
        return [false, 'Email address is too long.', $email];
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return [false, 'Please enter a valid email address.', $email];
    }

    return [true, '', $email];
}

function validate_phone_number(string $phone): array
{
    $normalized = normalize_phone($phone);

    if (trim($phone) === '') {
        return [true, '', null];
    }

    if ($normalized === null) {
        return [false, 'Please enter a valid Philippine mobile number, such as 09171234567.', null];
    }

    return [true, '', $normalized];
}

function validate_password(string $password, string $email = '', string $fullName = ''): array
{
    if ($password === '') {
        return [false, 'Password is required.'];
    }

    if (strlen($password) < 10) {
        return [false, 'Password must be at least 10 characters long.'];
    }

    if (strlen($password) > 72) {
        return [false, 'Password must not exceed 72 bytes.'];
    }

    // Reject control characters without modifying the password.
    if (preg_match('/[\x00-\x1F\x7F]/', $password)) {
        return [false, 'Password contains invalid control characters.'];
    }

    if (!preg_match('/[A-Z]/', $password)) {
        return [false, 'Password must contain at least one uppercase letter.'];
    }

    if (!preg_match('/[a-z]/', $password)) {
        return [false, 'Password must contain at least one lowercase letter.'];
    }

    if (!preg_match('/\d/', $password)) {
        return [false, 'Password must contain at least one number.'];
    }

    if (!preg_match('/[^A-Za-z0-9]/', $password)) {
        return [false, 'Password must contain at least one special character.'];
    }

    $lowerPassword = strtolower($password);
    $emailLocal = strtolower((string)strstr($email, '@', true));
    $nameParts = preg_split('/\s+/u', strtolower($fullName), -1, PREG_SPLIT_NO_EMPTY) ?: [];

    if ($emailLocal !== '' && strlen($emailLocal) >= 4 && str_contains($lowerPassword, $emailLocal)) {
        return [false, 'Password should not contain your email name.'];
    }

    foreach ($nameParts as $part) {
        if (text_length($part) >= 4 && str_contains($lowerPassword, $part)) {
            return [false, 'Password should not contain part of your name.'];
        }
    }

    $commonPasswords = [
        'password123!', 'password123', 'password!', 'qwerty123!',
        'qwerty123', '1234567890', '12345678!', 'admin123!', 'admin123'
    ];

    if (in_array($lowerPassword, $commonPasswords, true)) {
        return [false, 'Please choose a less common password.'];
    }

    return [true, ''];
}

function validate_account_role(string $role): bool
{
    return in_array($role, ['rent_seeker', 'owner'], true);
}
