<?php
declare(strict_types=1);

// StayFinder Phase 4 daily maintenance task.
// Run with the XAMPP PHP CLI, for example:
// C:\xampp\php\php.exe C:\xampp\htdocs\renthome\tasks\renewal_automation.php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/renewal_automation.php';

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("This maintenance script can only be run from the command line.\n");
}

$started = microtime(true);
try {
    $result = run_renewal_automation($pdo);
    $elapsed = number_format(microtime(true) - $started, 3);
    echo "StayFinder renewal automation completed in {$elapsed}s\n";
    echo "Checked: {$result['checked']}\n";
    echo "7-day reminders: {$result['seven_day']}\n";
    echo "3-day reminders: {$result['three_day']}\n";
    echo "Expired notices: {$result['expired']}\n";
    echo "Already handled/skipped: {$result['skipped']}\n";
    echo "Errors: {$result['errors']}\n";
    exit($result['errors'] > 0 ? 1 : 0);
} catch (Throwable $e) {
    fwrite(STDERR, "StayFinder renewal automation failed: " . $e->getMessage() . "\n");
    exit(1);
}
