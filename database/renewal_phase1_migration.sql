USE renthome_db;

-- StayFinder Phase 1: three-month listing validity foundation.
-- This does NOT add payment processing yet.

ALTER TABLE properties
    ADD COLUMN IF NOT EXISTS listing_expires_at DATETIME NULL AFTER availability_status,
    ADD COLUMN IF NOT EXISTS last_renewed_at DATETIME NULL AFTER listing_expires_at,
    ADD COLUMN IF NOT EXISTS renewal_count INT UNSIGNED NOT NULL DEFAULT 0 AFTER last_renewed_at;

-- Give existing approved listings a first three-month validity period based
-- on their original creation date. Pending/rejected listings remain NULL
-- until they are approved by an administrator.
UPDATE properties
SET listing_expires_at = DATE_ADD(created_at, INTERVAL 3 MONTH)
WHERE status = 'approved'
  AND listing_expires_at IS NULL;

CREATE INDEX IF NOT EXISTS idx_properties_listing_expiry
    ON properties(status, listing_expires_at);
