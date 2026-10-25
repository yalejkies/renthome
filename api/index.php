
<?php
declare(strict_types=1);

$projectRoot = dirname(__DIR__);
chdir($projectRoot);

$route = (string) ($_GET['__route'] ?? '');

// Opening the website root should load the homepage.
if ($route === '') {
    $route = 'index.php';
}

// Keep the API test endpoint available.
if ($route === 'api/index.php') {
    echo 'RentHome PHP is working!';
    exit;
}

$route = str_replace('\\', '/', $route);

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

require $file;
