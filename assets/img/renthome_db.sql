-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 25, 2026 at 05:13 AM
-- Server version: 10.11.16-MariaDB
-- PHP Version: 8.3.36

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `renthome_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin_audit_logs`
--

CREATE TABLE `admin_audit_logs` (
  `id` int(10) UNSIGNED NOT NULL,
  `admin_id` int(10) UNSIGNED NOT NULL,
  `action` varchar(100) NOT NULL,
  `entity_type` varchar(50) NOT NULL,
  `entity_id` int(10) UNSIGNED DEFAULT NULL,
  `details` varchar(1000) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `favorites`
--

CREATE TABLE `favorites` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `property_id` int(10) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `favorites`
--

INSERT INTO `favorites` (`id`, `user_id`, `property_id`, `created_at`) VALUES
(3, 4, 2, '2026-10-25 03:23:05');

-- --------------------------------------------------------

--
-- Table structure for table `inquiries`
--

CREATE TABLE `inquiries` (
  `id` int(10) UNSIGNED NOT NULL,
  `property_id` int(10) UNSIGNED NOT NULL,
  `sender_id` int(10) UNSIGNED NOT NULL,
  `message` text NOT NULL,
  `status` enum('unread','read','replied') NOT NULL DEFAULT 'unread',
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `inquiry_replies`
--

CREATE TABLE `inquiry_replies` (
  `id` int(10) UNSIGNED NOT NULL,
  `inquiry_id` int(10) UNSIGNED NOT NULL,
  `sender_id` int(10) UNSIGNED NOT NULL,
  `message` text NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `title` varchar(150) NOT NULL,
  `message` text NOT NULL,
  `link` varchar(255) DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `properties`
--

CREATE TABLE `properties` (
  `id` int(10) UNSIGNED NOT NULL,
  `owner_id` int(10) UNSIGNED NOT NULL,
  `title` varchar(150) NOT NULL,
  `property_type` enum('apartment','dorm','boarding_house') NOT NULL,
  `location` varchar(255) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `capacity` int(10) UNSIGNED NOT NULL,
  `gender_preference` enum('female_only','male_only','mixed') NOT NULL,
  `electricity_included` tinyint(1) NOT NULL DEFAULT 0,
  `wifi_available` tinyint(1) NOT NULL DEFAULT 0,
  `curfew` tinyint(1) NOT NULL DEFAULT 0,
  `transportation` varchar(255) DEFAULT NULL,
  `rules` text DEFAULT NULL,
  `description` text DEFAULT NULL,
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `availability_status` enum('available','unavailable') NOT NULL DEFAULT 'available',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `listing_expires_at` datetime DEFAULT NULL,
  `last_renewed_at` datetime DEFAULT NULL,
  `renewal_count` int(10) UNSIGNED NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `properties`
--

INSERT INTO `properties` (`id`, `owner_id`, `title`, `property_type`, `location`, `price`, `capacity`, `gender_preference`, `electricity_included`, `wifi_available`, `curfew`, `transportation`, `rules`, `description`, `status`, `availability_status`, `created_at`, `updated_at`, `listing_expires_at`, `last_renewed_at`, `renewal_count`) VALUES
(1, 1, 'two bed room', 'dorm', 'Barrera', 2500.00, 2, 'mixed', 0, 0, 0, NULL, NULL, 'Electricity and water bills are included/separate. Wi-Fi is available. Curfew is 10:00 PM. Transportation is accessible\nby tricycle, jeepney, or motorcycle. Visitors are allowed until 9:00 PM. Add other important house rules and details here.', 'approved', 'available', '2026-10-25 01:25:37', '2026-10-25 03:13:04', '2027-01-25 10:52:55', NULL, 0),
(2, 3, 'Classic 2200 Series', 'dorm', 'Bonifacio', 2500.00, 2, 'mixed', 0, 0, 0, NULL, NULL, 'Monthly rent covers internet and utilities. Parking is onsite and free. Quiet hours start at 9:00 PM. Public transportation options include bus and taxi. Guests must sign in at the front desk.', 'approved', 'available', '2026-10-25 03:11:24', '2026-10-25 03:12:32', '2027-01-25 11:12:32', NULL, 0);

-- --------------------------------------------------------

--
-- Table structure for table `property_images`
--

CREATE TABLE `property_images` (
  `id` int(10) UNSIGNED NOT NULL,
  `property_id` int(10) UNSIGNED NOT NULL,
  `image_path` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `property_images`
--

INSERT INTO `property_images` (`id`, `property_id`, `image_path`, `created_at`) VALUES
(1, 1, 'uploads/properties/6333de99d4578e268ef231d71e37ccc0.jpg', '2026-10-25 01:25:37'),
(2, 2, 'uploads/properties/1e2628817546e31f92991e51b2d26047.jpg', '2026-10-25 03:11:24');

-- --------------------------------------------------------

--
-- Table structure for table `renewal_notice_log`
--

CREATE TABLE `renewal_notice_log` (
  `id` int(10) UNSIGNED NOT NULL,
  `property_id` int(10) UNSIGNED NOT NULL,
  `expiry_at` datetime NOT NULL,
  `notice_type` varchar(30) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `renewal_payments`
--

CREATE TABLE `renewal_payments` (
  `id` int(10) UNSIGNED NOT NULL,
  `owner_id` int(10) UNSIGNED NOT NULL,
  `property_id` int(10) UNSIGNED NOT NULL,
  `amount` decimal(10,2) NOT NULL DEFAULT 20.00,
  `payment_method` enum('gcash','bank_transfer') NOT NULL,
  `reference_number` varchar(100) NOT NULL,
  `proof_path` varchar(255) NOT NULL,
  `status` enum('pending','paid','rejected') NOT NULL DEFAULT 'pending',
  `owner_note` varchar(500) DEFAULT NULL,
  `admin_note` varchar(500) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `verified_at` datetime DEFAULT NULL,
  `verified_by` int(10) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reports`
--

CREATE TABLE `reports` (
  `id` int(10) UNSIGNED NOT NULL,
  `reported_by` int(10) UNSIGNED NOT NULL,
  `property_id` int(10) UNSIGNED DEFAULT NULL,
  `report_type` varchar(100) NOT NULL,
  `description` text NOT NULL,
  `status` enum('pending','reviewed','resolved') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(10) UNSIGNED NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('rent_seeker','owner','admin') NOT NULL DEFAULT 'rent_seeker',
  `status` enum('active','inactive','suspended') NOT NULL DEFAULT 'active',
  `owner_policy_accepted_at` datetime DEFAULT NULL,
  `mayor_permit_path` varchar(255) DEFAULT NULL,
  `mayor_permit_uploaded_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `full_name`, `email`, `phone`, `password`, `role`, `status`, `owner_policy_accepted_at`, `mayor_permit_path`, `mayor_permit_uploaded_at`, `created_at`, `updated_at`) VALUES
(1, 'Maria Angela Sese', 'sesemariaangela4@gmail.com', '+639350458593', '$2y$10$cnf8qaDIJp4un7wUzVpTEeDkLpvrOY1mXa9PR0.R9GBoDSWuDUt4S', 'owner', 'active', '2026-10-25 09:17:09', 'uploads/permits/4f1f44a2c682c7a408fca2d684848907.jpg', '2026-10-25 09:20:32', '2026-10-25 00:52:01', '2026-10-25 01:20:32'),
(2, 'StayFinder', 'stayfiner@gmail.com', '+639000000000', '$2y$10$7SZDNbJ3mM3aqDR8MBxEHOTujnhyeu1Col5VmXjFnewHvwtB3H/ca', 'admin', 'active', NULL, NULL, NULL, '2026-10-25 00:57:24', '2026-10-25 01:05:08'),
(3, 'Jumeirah Serrano', 'serrano@gmail.com', '+639350458597', '$2y$10$CbEaeuT6ic.lI8FzcR2Gn.DNUxLGR43XBd7FU1mzYQNCWdiOMf6je', 'owner', 'active', '2026-10-25 11:07:32', 'uploads/permits/61d3c88c91d9b90a6b94da1f3c1595e6.jpg', '2026-10-25 11:07:51', '2026-10-25 03:07:16', '2026-10-25 03:07:51'),
(4, 'Cecille Benitez', 'benitez@gmail.com', '+639350458597', '$2y$10$6GJWZ9uFRj.keTH2kucwdeVvwNaD4DCveekw6acB/7qzXpb24Eabi', 'rent_seeker', 'active', NULL, NULL, NULL, '2026-10-25 03:15:26', '2026-10-25 03:15:26');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin_audit_logs`
--
ALTER TABLE `admin_audit_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_audit_admin` (`admin_id`),
  ADD KEY `idx_audit_entity` (`entity_type`,`entity_id`),
  ADD KEY `idx_audit_created` (`created_at`);

--
-- Indexes for table `favorites`
--
ALTER TABLE `favorites`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_favorite` (`user_id`,`property_id`),
  ADD KEY `idx_favorites_property` (`property_id`);

--
-- Indexes for table `inquiries`
--
ALTER TABLE `inquiries`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_inquiries_property` (`property_id`),
  ADD KEY `fk_inquiries_sender` (`sender_id`);

--
-- Indexes for table `inquiry_replies`
--
ALTER TABLE `inquiry_replies`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_replies_inquiry` (`inquiry_id`),
  ADD KEY `idx_replies_sender` (`sender_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_notifications_user_read` (`user_id`,`is_read`);

--
-- Indexes for table `properties`
--
ALTER TABLE `properties`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_properties_owner` (`owner_id`),
  ADD KEY `idx_properties_listing_expiry` (`status`,`listing_expires_at`);

--
-- Indexes for table `property_images`
--
ALTER TABLE `property_images`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_property_images_property` (`property_id`);

--
-- Indexes for table `renewal_notice_log`
--
ALTER TABLE `renewal_notice_log`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_renewal_notice` (`property_id`,`expiry_at`,`notice_type`),
  ADD KEY `idx_notice_property` (`property_id`),
  ADD KEY `idx_notice_type` (`notice_type`);

--
-- Indexes for table `renewal_payments`
--
ALTER TABLE `renewal_payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_renewal_owner` (`owner_id`),
  ADD KEY `idx_renewal_property` (`property_id`),
  ADD KEY `idx_renewal_status` (`status`),
  ADD KEY `idx_renewal_reference` (`reference_number`);

--
-- Indexes for table `reports`
--
ALTER TABLE `reports`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_reports_user` (`reported_by`),
  ADD KEY `fk_reports_property` (`property_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin_audit_logs`
--
ALTER TABLE `admin_audit_logs`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `favorites`
--
ALTER TABLE `favorites`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `inquiries`
--
ALTER TABLE `inquiries`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `inquiry_replies`
--
ALTER TABLE `inquiry_replies`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `properties`
--
ALTER TABLE `properties`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `property_images`
--
ALTER TABLE `property_images`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `renewal_notice_log`
--
ALTER TABLE `renewal_notice_log`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `renewal_payments`
--
ALTER TABLE `renewal_payments`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `reports`
--
ALTER TABLE `reports`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `inquiries`
--
ALTER TABLE `inquiries`
  ADD CONSTRAINT `fk_inquiries_property` FOREIGN KEY (`property_id`) REFERENCES `properties` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_inquiries_sender` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `properties`
--
ALTER TABLE `properties`
  ADD CONSTRAINT `fk_properties_owner` FOREIGN KEY (`owner_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `property_images`
--
ALTER TABLE `property_images`
  ADD CONSTRAINT `fk_property_images_property` FOREIGN KEY (`property_id`) REFERENCES `properties` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `reports`
--
ALTER TABLE `reports`
  ADD CONSTRAINT `fk_reports_property` FOREIGN KEY (`property_id`) REFERENCES `properties` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_reports_user` FOREIGN KEY (`reported_by`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
