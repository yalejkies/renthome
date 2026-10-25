USE renthome_db;

-- Adds the required Mayor's Permit upload step for owner/manager accounts.
ALTER TABLE users
    ADD COLUMN IF NOT EXISTS mayor_permit_path VARCHAR(255) NULL AFTER owner_policy_accepted_at,
    ADD COLUMN IF NOT EXISTS mayor_permit_uploaded_at DATETIME NULL AFTER mayor_permit_path;
