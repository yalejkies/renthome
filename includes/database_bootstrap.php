<?php
declare(strict_types=1);

/**
 * StayFinder portable database bootstrap.
 * Creates the database and upgrades/creates all required tables automatically.
 * Designed for a fresh XAMPP installation as well as an existing StayFinder DB.
 */
function stayfinder_column_exists(PDO $pdo, string $table, string $column): bool
{
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?');
    $stmt->execute([$table, $column]);
    return (int)$stmt->fetchColumn() > 0;
}

function stayfinder_add_column(PDO $pdo, string $table, string $column, string $definition): void
{
    if (!stayfinder_column_exists($pdo, $table, $column)) {
        $pdo->exec("ALTER TABLE `$table` ADD COLUMN `$column` $definition");
    }
}

function stayfinder_index_exists(PDO $pdo, string $table, string $index): bool
{
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ?');
    $stmt->execute([$table, $index]);
    return (int)$stmt->fetchColumn() > 0;
}

function stayfinder_ensure_schema(PDO $pdo): void
{
    // Core accounts.
    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        full_name VARCHAR(100) NOT NULL,
        email VARCHAR(150) NOT NULL UNIQUE,
        phone VARCHAR(30) NULL,
        password VARCHAR(255) NOT NULL,
        role ENUM('rent_seeker','owner','admin') NOT NULL DEFAULT 'rent_seeker',
        status ENUM('active','inactive','suspended') NOT NULL DEFAULT 'active',
        owner_policy_accepted_at DATETIME NULL,
        mayor_permit_path VARCHAR(255) NULL,
        mayor_permit_uploaded_at DATETIME NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Existing older databases may have been created before newer columns were added.
    stayfinder_add_column($pdo, 'users', 'status', "ENUM('active','inactive','suspended') NOT NULL DEFAULT 'active'");
    stayfinder_add_column($pdo, 'users', 'owner_policy_accepted_at', 'DATETIME NULL');
    stayfinder_add_column($pdo, 'users', 'mayor_permit_path', 'VARCHAR(255) NULL');
    stayfinder_add_column($pdo, 'users', 'mayor_permit_uploaded_at', 'DATETIME NULL');

    // Listings.
    $pdo->exec("CREATE TABLE IF NOT EXISTS properties (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        owner_id INT UNSIGNED NOT NULL,
        title VARCHAR(150) NOT NULL,
        property_type ENUM('apartment','dorm','boarding_house') NOT NULL,
        location VARCHAR(255) NOT NULL,
        price DECIMAL(10,2) NOT NULL,
        capacity INT UNSIGNED NOT NULL,
        gender_preference ENUM('female_only','male_only','mixed') NOT NULL,
        electricity_included TINYINT(1) NOT NULL DEFAULT 0,
        wifi_available TINYINT(1) NOT NULL DEFAULT 0,
        curfew TINYINT(1) NOT NULL DEFAULT 0,
        transportation VARCHAR(255) NULL,
        rules TEXT NULL,
        description TEXT NULL,
        status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
        availability_status ENUM('available','unavailable') NOT NULL DEFAULT 'available',
        listing_expires_at DATETIME NULL,
        last_renewed_at DATETIME NULL,
        renewal_count INT UNSIGNED NOT NULL DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    stayfinder_add_column($pdo, 'properties', 'availability_status', "ENUM('available','unavailable') NOT NULL DEFAULT 'available'");
    stayfinder_add_column($pdo, 'properties', 'listing_expires_at', 'DATETIME NULL');
    stayfinder_add_column($pdo, 'properties', 'last_renewed_at', 'DATETIME NULL');
    stayfinder_add_column($pdo, 'properties', 'renewal_count', 'INT UNSIGNED NOT NULL DEFAULT 0');

    if (!stayfinder_index_exists($pdo, 'properties', 'idx_properties_listing_expiry')) {
        $pdo->exec('CREATE INDEX idx_properties_listing_expiry ON properties(status, listing_expires_at)');
    }

    $pdo->exec("UPDATE properties SET listing_expires_at = DATE_ADD(created_at, INTERVAL 3 MONTH)
                WHERE status = 'approved' AND listing_expires_at IS NULL");

    // Core supporting tables.
    $pdo->exec("CREATE TABLE IF NOT EXISTS property_images (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        property_id INT UNSIGNED NOT NULL,
        image_path VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_property_images_property (property_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $pdo->exec("CREATE TABLE IF NOT EXISTS inquiries (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        property_id INT UNSIGNED NOT NULL,
        sender_id INT UNSIGNED NOT NULL,
        message TEXT NOT NULL,
        status ENUM('unread','read','replied') NOT NULL DEFAULT 'unread',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_inquiries_property (property_id),
        INDEX idx_inquiries_sender (sender_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $pdo->exec("CREATE TABLE IF NOT EXISTS inquiry_replies (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        inquiry_id INT UNSIGNED NOT NULL,
        sender_id INT UNSIGNED NOT NULL,
        message TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_replies_inquiry (inquiry_id),
        INDEX idx_replies_sender (sender_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $pdo->exec("CREATE TABLE IF NOT EXISTS favorites (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        user_id INT UNSIGNED NOT NULL,
        property_id INT UNSIGNED NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uq_favorite (user_id, property_id),
        INDEX idx_favorites_property (property_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $pdo->exec("CREATE TABLE IF NOT EXISTS notifications (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        user_id INT UNSIGNED NOT NULL,
        title VARCHAR(150) NOT NULL,
        message TEXT NOT NULL,
        link VARCHAR(255) NULL,
        is_read TINYINT(1) NOT NULL DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_notifications_user_read (user_id, is_read)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $pdo->exec("CREATE TABLE IF NOT EXISTS reports (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        reported_by INT UNSIGNED NOT NULL,
        property_id INT UNSIGNED NULL,
        report_type VARCHAR(100) NOT NULL,
        description TEXT NOT NULL,
        status ENUM('pending','reviewed','resolved') NOT NULL DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_reports_reporter (reported_by),
        INDEX idx_reports_property (property_id),
        INDEX idx_reports_status (status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Renewal/payment tables.
    $pdo->exec("CREATE TABLE IF NOT EXISTS renewal_payments (
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
        INDEX idx_renewal_owner (owner_id),
        INDEX idx_renewal_property (property_id),
        INDEX idx_renewal_status (status),
        INDEX idx_renewal_reference (reference_number)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $pdo->exec("CREATE TABLE IF NOT EXISTS renewal_notice_log (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        property_id INT UNSIGNED NOT NULL,
        expiry_at DATETIME NOT NULL,
        notice_type VARCHAR(30) NOT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uq_renewal_notice (property_id, expiry_at, notice_type),
        INDEX idx_notice_property (property_id),
        INDEX idx_notice_type (notice_type)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

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
