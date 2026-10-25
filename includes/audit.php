<?php
declare(strict_types=1);

function ensure_audit_schema(PDO $pdo): void
{
    $pdo->exec("CREATE TABLE IF NOT EXISTS admin_audit_logs (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        admin_id INT UNSIGNED NOT NULL,
        action VARCHAR(100) NOT NULL,
        entity_type VARCHAR(50) NOT NULL,
        entity_id INT UNSIGNED NULL,
        details VARCHAR(1000) NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_audit_admin (admin_id),
        INDEX idx_audit_entity (entity_type, entity_id),
        INDEX idx_audit_created (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
}

function log_admin_action(PDO $pdo, int $adminId, string $action, string $entityType, ?int $entityId, ?string $details = null): void
{
    $stmt = $pdo->prepare('INSERT INTO admin_audit_logs (admin_id, action, entity_type, entity_id, details) VALUES (?, ?, ?, ?, ?)');
    $stmt->execute([$adminId, $action, $entityType, $entityId, $details]);
}
