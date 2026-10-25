USE renthome_db;

-- Adds owner-controlled listing availability without changing admin approval status.
-- Run this once in phpMyAdmin if your existing database was created from an older schema.
ALTER TABLE properties
    ADD COLUMN IF NOT EXISTS availability_status ENUM('available', 'unavailable') NOT NULL DEFAULT 'available' AFTER status;

UPDATE properties
SET availability_status = 'available'
WHERE availability_status IS NULL OR availability_status = '';
