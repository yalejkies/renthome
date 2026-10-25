<?php
declare(strict_types=1);

require_once __DIR__ . '/renewal.php';
require_once __DIR__ . '/notifications.php';

/**
 * Phase 4: automatic renewal reminders and expiration notices.
 * Safe to run repeatedly; each notice is logged once per listing expiration cycle.
 */
function ensure_renewal_automation_schema(PDO $pdo): void
{
    ensure_renewal_schema($pdo);

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS renewal_notice_log (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            property_id INT UNSIGNED NOT NULL,
            expiry_at DATETIME NOT NULL,
            notice_type VARCHAR(30) NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uq_renewal_notice (property_id, expiry_at, notice_type),
            INDEX idx_renewal_notice_property (property_id),
            INDEX idx_renewal_notice_type (notice_type)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );
}

function renewal_notice_already_sent(PDO $pdo, int $propertyId, string $expiryAt, string $noticeType): bool
{
    $stmt = $pdo->prepare(
        'SELECT id FROM renewal_notice_log WHERE property_id = ? AND expiry_at = ? AND notice_type = ? LIMIT 1'
    );
    $stmt->execute([$propertyId, $expiryAt, $noticeType]);
    return (bool)$stmt->fetchColumn();
}

function record_renewal_notice(PDO $pdo, int $propertyId, string $expiryAt, string $noticeType): void
{
    $stmt = $pdo->prepare(
        'INSERT IGNORE INTO renewal_notice_log (property_id, expiry_at, notice_type) VALUES (?, ?, ?)'
    );
    $stmt->execute([$propertyId, $expiryAt, $noticeType]);
}

/**
 * Run the daily renewal maintenance job.
 * Returns counts for the CLI/admin summary.
 */
function run_renewal_automation(PDO $pdo): array
{
    ensure_renewal_automation_schema($pdo);

    $stmt = $pdo->query(
        "SELECT p.id, p.title, p.owner_id, p.listing_expires_at
         FROM properties p
         WHERE p.status = 'approved'
           AND p.listing_expires_at IS NOT NULL
         ORDER BY p.listing_expires_at ASC"
    );
    $listings = $stmt->fetchAll();

    $result = [
        'checked' => 0,
        'seven_day' => 0,
        'three_day' => 0,
        'expired' => 0,
        'skipped' => 0,
        'errors' => 0,
    ];

    $now = new DateTimeImmutable('now');

    foreach ($listings as $listing) {
        $result['checked']++;
        try {
            $expiry = new DateTimeImmutable((string)$listing['listing_expires_at']);
            $seconds = $expiry->getTimestamp() - $now->getTimestamp();
            $days = (int)floor($seconds / 86400);
            $propertyId = (int)$listing['id'];
            $ownerId = (int)$listing['owner_id'];
            $title = (string)$listing['title'];
            $expiryAt = $expiry->format('Y-m-d H:i:s');

            if ($seconds <= 0) {
                $noticeType = 'expired';
                if (renewal_notice_already_sent($pdo, $propertyId, $expiryAt, $noticeType)) {
                    $result['skipped']++;
                    continue;
                }

                create_notification(
                    $pdo,
                    $ownerId,
                    'Listing expired',
                    'Your listing "' . $title . '" has expired and is no longer visible to rent seekers. Submit a renewal payment to extend it for another 3 months.',
                    'owner/renew.php?id=' . $propertyId
                );
                record_renewal_notice($pdo, $propertyId, $expiryAt, $noticeType);
                $result['expired']++;
                continue;
            }

            if ($days <= 3) {
                $noticeType = 'three_day';
                if (renewal_notice_already_sent($pdo, $propertyId, $expiryAt, $noticeType)) {
                    $result['skipped']++;
                    continue;
                }

                create_notification(
                    $pdo,
                    $ownerId,
                    'Listing expires soon',
                    'Your listing "' . $title . '" will expire within 3 days. Renew it now to keep it visible on StayFinder.',
                    'owner/renew.php?id=' . $propertyId
                );
                record_renewal_notice($pdo, $propertyId, $expiryAt, $noticeType);
                $result['three_day']++;
                continue;
            }

            if ($days <= STAYFINDER_RENEWAL_NOTICE_DAYS) {
                $noticeType = 'seven_day';
                if (renewal_notice_already_sent($pdo, $propertyId, $expiryAt, $noticeType)) {
                    $result['skipped']++;
                    continue;
                }

                create_notification(
                    $pdo,
                    $ownerId,
                    'Listing renewal reminder',
                    'Your listing "' . $title . '" will expire within 7 days. Renew it early to keep it visible on StayFinder.',
                    'owner/renew.php?id=' . $propertyId
                );
                record_renewal_notice($pdo, $propertyId, $expiryAt, $noticeType);
                $result['seven_day']++;
                continue;
            }
        } catch (Throwable $e) {
            $result['errors']++;
        }
    }

    return $result;
}
