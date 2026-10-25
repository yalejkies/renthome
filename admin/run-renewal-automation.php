<?php
declare(strict_types=1);

require_once '../config/database.php';
require_once '../config/app.php';
require_once '../includes/auth.php';
require_once '../includes/csrf.php';
require_once '../includes/renewal_automation.php';
require_role('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verify_csrf($_POST['csrf_token'] ?? '')) {
    http_response_code(419);
    exit('Invalid form request.');
}

try {
    $result = run_renewal_automation($pdo);
    $summary = sprintf(
        'Automation completed. %d checked, %d seven-day reminders, %d three-day reminders, %d expired notices.',
        $result['checked'], $result['seven_day'], $result['three_day'], $result['expired']
    );
    if ($result['errors'] > 0) {
        $summary .= ' Errors: ' . $result['errors'] . '.';
    }
    header('Location: dashboard.php?automation=' . rawurlencode($summary));
    exit;
} catch (Throwable $e) {
    header('Location: dashboard.php?automation=' . rawurlencode('Automation could not be completed. Please try again.'));
    exit;
}
