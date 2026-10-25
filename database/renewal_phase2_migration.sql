USE renthome_db;

-- StayFinder Phase 2: renewal payment submission foundation.
-- This records StayFinder renewal/service-fee payments only.
-- It does NOT process rent, deposits, utilities, or renter-to-owner payments.

CREATE TABLE IF NOT EXISTS renewal_payments (
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
    INDEX idx_renewal_payments_reference (reference_number),
    CONSTRAINT fk_renewal_payments_owner FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_renewal_payments_property FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE,
    CONSTRAINT fk_renewal_payments_verified_by FOREIGN KEY (verified_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
