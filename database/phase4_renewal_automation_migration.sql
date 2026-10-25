-- StayFinder Phase 4: renewal automation notice log
-- Safe to run manually, although the application also creates this table automatically.

CREATE TABLE IF NOT EXISTS renewal_notice_log (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    property_id INT UNSIGNED NOT NULL,
    expiry_at DATETIME NOT NULL,
    notice_type VARCHAR(30) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_renewal_notice (property_id, expiry_at, notice_type),
    INDEX idx_renewal_notice_property (property_id),
    INDEX idx_renewal_notice_type (notice_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
