
<?php
declare(strict_types=1);

// Project root: one level above the api folder.
$projectRoot = dirname(__DIR__);
chdir($projectRoot);

// Keep the existing API test endpoint working when
// no page has been requested through the router.
$route = $_GET['__route'] ?? '';

if ($route === '') {
    echo 'RentHome PHP is working!';
    exit;
}

// Only allow public root pages and the admin/owner pages.
$route = str_replace('\\', '/', (string) $route);

if (
    str_contains($route, '..') ||
    str_starts_with($route, '/') ||
    !str_ends_with($route, '.php')
) {
    http_response_code(404);
    exit('Page not found.');
}

$allowedRootPages = [
    'index.php',
    'browse.php',
    'favorite.php',
    'favorites.php',
    'inquiry.php',
    'login.php',
    'logout.php',
    'my-inquiries.php',
    'notifications.php',
    'property.php',
    'register.php',
    'setup.php',
];

$allowed =
    in_array($route, $allowedRootPages, true) ||
    preg_match('~^(admin|owner)/[a-zA-Z0-9_-]+\.php$~', $route);

if (!$allowed) {
    http_response_code(404);
    exit('Page not found.');
}

$file = realpath($projectRoot . '/' . $route);

if (
    $file === false ||
    !str_starts_with($file, $projectRoot . DIRECTORY_SEPARATOR) ||
    !is_file($file)
) {
    http_response_code(404);
    exit('Page not found.');
}

// Execute the requested PHP page.
require $file;
