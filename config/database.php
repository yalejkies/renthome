
<?php
declare(strict_types=1);

$charset = 'utf8mb4';
$isVercel = getenv('VERCEL') === '1';

$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

try {
    if ($isVercel) {
        // Production: Aiven MySQL credentials from Vercel environment variables.
        $host = getenv('DB_HOST');
        $port = getenv('DB_PORT') ?: '26607';
        $db   = getenv('DB_NAME') ?: 'defaultdb';
        $user = getenv('DB_USER');
        $pass = getenv('DB_PASSWORD');

        // Use the CA certificate included in the project by default.
        $ca = getenv('DB_SSL_CA_PATH')
            ?: dirname(__DIR__) . '/certs/aiven-ca.pem';

        if (!$host || !$user || !$pass || !is_file($ca)) {
            throw new RuntimeException(
                'Database environment configuration or SSL certificate is missing.'
            );
        }

        $dsn = "mysql:host=$host;port=$port;dbname=$db;charset=$charset";

        if (!defined('PDO::MYSQL_ATTR_SSL_CA')) {
            throw new RuntimeException(
                'The PHP MySQL PDO driver does not support the required SSL configuration.'
            );
        }

        $options[PDO::MYSQL_ATTR_SSL_CA] = $ca;

        if (defined('PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT')) {
            $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = true;
        }

        $pdo = new PDO($dsn, $user, $pass, $options);

    } else {
        // Local development: XAMPP.
        $host = '127.0.0.1';
        $db   = 'renthome_db';
        $user = 'root';
        $pass = '';

        $serverPdo = new PDO(
            "mysql:host=$host;charset=$charset",
            $user,
            $pass,
            $options
        );

        $serverPdo->exec(
            "CREATE DATABASE IF NOT EXISTS `$db`
             CHARACTER SET utf8mb4
             COLLATE utf8mb4_unicode_ci"
        );

        $dsn = "mysql:host=$host;dbname=$db;charset=$charset";
        $pdo = new PDO($dsn, $user, $pass, $options);
    }

    require_once __DIR__ . '/../includes/database_bootstrap.php';

    // Initialize schema only on the local XAMPP environment.
    if (!$isVercel) {
        stayfinder_ensure_schema($pdo);
    }

} catch (Throwable $e) {
    error_log('Database connection/setup failed: ' . $e->getMessage());

    http_response_code(500);
    exit(
        'Database connection failed. Check the server configuration and database logs.'
    );
}
