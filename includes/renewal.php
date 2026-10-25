<?php
declare(strict_types=1);

/**
 * StayFinder renewal rules and schema bootstrap.
 *
 * This local XAMPP build automatically creates any missing renewal columns/table
 * so the renewal pages do not crash when the migration SQL has not been run yet.
 */
const STAYFINDER_RENEWAL_MONTHS = 3;
const STAYFINDER_RENEWAL_NOTICE_DAYS = 7;
const STAYFINDER_RENEWAL_FEE = 20.00;

/** Ensure the Phase 1/2 renewal schema exists in the current database. */
function ensure_renewal_schema(PDO $pdo): void
{
    // Phase 1 columns.
    $columns = [
        'listing_expires_at' => 'DATETIME NULL',
        'last_renewed_at' => 'DATETIME NULL',
        'renewal_count' => 'INT UNSIGNED NOT NULL DEFAULT 0',
    ];

    foreach ($columns as $column => $definition) {
        $check = $pdo->prepare(
            "SELECT COUNT(*) FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'properties' AND COLUMN_NAME = ?"
        );
        $check->execute([$column]);

        if ((int)$check->fetchColumn() === 0) {
            $pdo->exec("ALTER TABLE properties ADD COLUMN `$column` $definition");
        }
    }

    // Initialize the first three-month validity period for already-approved listings.
    $pdo->exec(
        "UPDATE properties
         SET listing_expires_at = DATE_ADD(created_at, INTERVAL 3 MONTH)
         WHERE status = 'approved' AND listing_expires_at IS NULL"
    );

    // Phase 2 renewal payment table.
    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS renewal_payments (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            owner_id INT UNSIGNED NOT NULL,
            property_id INT UNSIGNED NOT NULL,
            amount DECIMAL(10,2) NOT NULL DEFAULT 20.00,
            payment_method ENUM('gcash','bank_transfer') NOT NULL,
            reference_number VARCHAR(100) NOT NULL,
            proof_path VARCHAR(255) NOT NULL,
            status ENUM('pending','paid','rejected') NOT NULL DEFAULT 'pending',
            owner_note VARCHAR(500) NULL,
            admin_note VARCHAR(500) NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            verified_at DATETIME NULL,
            verified_by INT UNSIGNED NULL,
            INDEX idx_renewal_payments_owner (owner_id),
            INDEX idx_renewal_payments_property (property_id),
            INDEX idx_renewal_payments_status (status),
            INDEX idx_renewal_payments_reference (reference_number)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );

    // Final-phase administrator audit trail.
    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS admin_audit_logs (
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );
}

function calculate_listing_expiration(?string $baseDate = null): string
{
    $date = $baseDate
        ? new DateTimeImmutable($baseDate)
        : new DateTimeImmutable('now');

    return $date->modify('+' . STAYFINDER_RENEWAL_MONTHS . ' months')->format('Y-m-d H:i:s');
}

function listing_is_expired(?string $expiresAt): bool
{
    if (!$expiresAt) {
        return false;
    }

    return new DateTimeImmutable($expiresAt) <= new DateTimeImmutable('now');
}

function listing_days_remaining(?string $expiresAt): ?int
{
    if (!$expiresAt) {
        return null;
    }

    $now = new DateTimeImmutable('now');
    $expiry = new DateTimeImmutable($expiresAt);

    if ($expiry <= $now) {
        return 0;
    }

    $seconds = $expiry->getTimestamp() - $now->getTimestamp();
    return (int)ceil($seconds / 86400);
}

function format_listing_expiration(?string $expiresAt): string
{
    if (!$expiresAt) {
        return 'Not set';
    }

    return date('M d, Y', strtotime($expiresAt));
}

function renewal_fee(): float
{
    return STAYFINDER_RENEWAL_FEE;
}

function renewal_payment_method_label(string $method): string
{
    return match ($method) {
        'gcash' => 'GCash',
        'bank_transfer' => 'Bank Transfer',
        default => 'Unknown',
    };
}


function renewal_status_label(string $status): string
{
    return match ($status) {
        'pending' => 'Pending Verification',
        'paid' => 'Approved',
        'rejected' => 'Rejected',
        default => ucfirst($status),
    };
}

function renewal_status_class(string $status): string
{
    return match ($status) {
        'pending' => 'pending',
        'paid' => 'approved',
        'rejected' => 'rejected',
        default => 'pending',
    };
}
