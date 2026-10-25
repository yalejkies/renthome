<?php
declare(strict_types=1);

$host = '127.0.0.1';
$db   = 'renthome_db';
$user = 'root';
$pass = ''; // Default XAMPP MySQL/MariaDB password is usually blank.
$charset = 'utf8mb4';

$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

try {
    // First connect without selecting a database so a fresh XAMPP machine can initialize itself.
    $serverDsn = "mysql:host=$host;charset=$charset";
    $serverPdo = new PDO($serverDsn, $user, $pass, $options);
    $serverPdo->exec("CREATE DATABASE IF NOT EXISTS `$db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

    $dsn = "mysql:host=$host;dbname=$db;charset=$charset";
    $pdo = new PDO($dsn, $user, $pass, $options);

    require_once __DIR__ . '/../includes/database_bootstrap.php';
    stayfinder_ensure_schema($pdo);
} catch (PDOException $e) {
    exit('Database setup failed. Make sure XAMPP MySQL/MariaDB is running and that the database credentials in config/database.php are correct.');
}
