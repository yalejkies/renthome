USE renthome_db;

ALTER TABLE users
    ADD COLUMN owner_policy_accepted_at DATETIME NULL AFTER status;
