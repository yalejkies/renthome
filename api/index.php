$route = (string) ($_GET['__route'] ?? '');

if ($route === '') {
    $route = 'index.php';
}
