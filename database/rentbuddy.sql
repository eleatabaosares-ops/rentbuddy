-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 25, 2026 at 06:53 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `rentbuddy`
--

-- --------------------------------------------------------

--
-- Table structure for table `documents`
--

CREATE TABLE `documents` (
  `id` int(11) NOT NULL,
  `tenant_id` int(11) DEFAULT NULL,
  `property_id` int(11) DEFAULT NULL,
  `doc_type` enum('contract','lease','receipt','other') DEFAULT 'other',
  `title` varchar(150) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `file_size` int(11) DEFAULT NULL,
  `uploaded_by` varchar(100) DEFAULT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `leases`
--

CREATE TABLE `leases` (
  `id` int(11) NOT NULL,
  `tenant_id` int(11) NOT NULL,
  `unit_id` int(11) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `monthly_rent` decimal(10,2) NOT NULL,
  `deposit` decimal(10,2) DEFAULT 0.00,
  `status` enum('active','expiring','expired','terminated') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `leases`
--

INSERT INTO `leases` (`id`, `tenant_id`, `unit_id`, `start_date`, `end_date`, `monthly_rent`, `deposit`, `status`, `created_at`) VALUES
(1, 1, 1, '2024-01-01', '2025-12-31', 1200.00, 1200.00, 'expired', '2026-09-24 03:37:23'),
(2, 2, 2, '2024-03-01', '2025-02-28', 1200.00, 1200.00, 'expired', '2026-09-24 03:37:23'),
(3, 3, 4, '2024-02-01', '2025-01-31', 1800.00, 1800.00, 'expired', '2026-09-24 03:37:23'),
(4, 4, 6, '2024-06-01', '2025-05-31', 2500.00, 2500.00, 'expired', '2026-09-24 03:37:23');

-- --------------------------------------------------------

--
-- Table structure for table `login_attempts`
--

CREATE TABLE `login_attempts` (
  `id` int(11) NOT NULL,
  `identifier` varchar(100) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `attempted_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `maintenance_requests`
--

CREATE TABLE `maintenance_requests` (
  `id` int(11) NOT NULL,
  `tenant_id` int(11) NOT NULL,
  `unit_id` int(11) DEFAULT NULL,
  `category` varchar(50) NOT NULL,
  `description` text NOT NULL,
  `priority` enum('low','medium','high','urgent') DEFAULT 'medium',
  `status` enum('submitted','under_review','in_progress','completed','cancelled') DEFAULT 'submitted',
  `photo_path` varchar(255) DEFAULT NULL,
  `assigned_to` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `maintenance_requests`
--

INSERT INTO `maintenance_requests` (`id`, `tenant_id`, `unit_id`, `category`, `description`, `priority`, `status`, `photo_path`, `assigned_to`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 'Plumbing', 'Kitchen sink is leaking badly.', 'high', 'in_progress', NULL, 'Mike the Plumber', '2026-09-24 03:37:23', '2026-09-24 03:37:23'),
(2, 2, 2, 'Electrical', 'Living room outlet not working.', 'medium', 'submitted', NULL, NULL, '2026-09-24 03:37:23', '2026-09-24 03:37:23'),
(3, 3, 4, 'HVAC', 'Air conditioner making loud noise.', 'urgent', 'under_review', NULL, 'CoolAir Services', '2026-09-24 03:37:23', '2026-09-24 03:37:23'),
(4, 4, 6, 'Appliance', 'Refrigerator not cooling properly.', 'medium', 'completed', NULL, 'Appliance Pro', '2026-09-24 03:37:23', '2026-09-24 03:37:23'),
(5, 10, 5, 'Electrical', 'HELP ME!', 'high', 'submitted', NULL, NULL, '2026-09-25 15:43:59', '2026-09-25 15:43:59');

-- --------------------------------------------------------

--
-- Table structure for table `maintenance_updates`
--

CREATE TABLE `maintenance_updates` (
  `id` int(11) NOT NULL,
  `request_id` int(11) NOT NULL,
  `status` varchar(50) DEFAULT NULL,
  `note` text DEFAULT NULL,
  `created_by` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `maintenance_updates`
--

INSERT INTO `maintenance_updates` (`id`, `request_id`, `status`, `note`, `created_by`, `created_at`) VALUES
(1, 1, 'submitted', 'Request received.', 'System', '2026-09-24 03:37:23'),
(2, 1, 'under_review', 'Assigned to plumber.', 'Admin', '2026-09-24 03:37:23'),
(3, 1, 'in_progress', 'Plumber scheduled for tomorrow.', 'Admin', '2026-09-24 03:37:23'),
(4, 4, 'completed', 'Replaced compressor. Working now.', 'Appliance Pro', '2026-09-24 03:37:23');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `tenant_id` int(11) DEFAULT NULL,
  `title` varchar(150) NOT NULL,
  `message` text NOT NULL,
  `type` varchar(50) DEFAULT 'info',
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `tenant_id`, `title`, `message`, `type`, `is_read`, `created_at`) VALUES
(1, NULL, 10, 'Payment Successful', 'Your payment of $1,500.00 was received. Ref: RB-2026-6356', 'success', 1, '2026-09-24 06:05:57'),
(2, NULL, 10, 'Payment Due Notice', 'A manual payment notice: ₱5,000.00 is due on Sep 25, 2026.', 'due', 1, '2026-09-24 14:49:08'),
(3, NULL, 10, 'Payment Reminder', 'Reminder: Your rent payment of ₱5,000.00 is due on Sep 25, 2026.', 'info', 0, '2026-09-24 14:58:55'),
(4, NULL, 10, 'Payment Reminder', 'RentBuddy Reminder: Your rent payment of ₱5,000.00 is due on Sep 25, 2026.', 'info', 0, '2026-09-24 15:12:50'),
(5, NULL, 10, 'Payment Reminder', 'RentBuddy Reminder: Your rent payment of ₱5,000.00 is due on Sep 25, 2026.', 'info', 0, '2026-09-24 15:14:33'),
(6, NULL, 10, 'Payment Reminder', 'RentBuddy Reminder: Your rent payment of ₱5,000.00 is due on Sep 25, 2026.', 'info', 0, '2026-09-24 15:17:59'),
(7, NULL, 10, 'Payment Reminder', 'RentBuddy Reminder: Your rent payment of ₱5,000.00 is due on Sep 25, 2026.', 'info', 0, '2026-09-25 15:33:22'),
(8, NULL, 10, 'Payment Reminder', 'RentBuddy Reminder: Your rent payment of ₱5,000.00 is due on Sep 25, 2026.', 'info', 0, '2026-09-25 15:34:07'),
(9, NULL, 10, 'Payment Successful', 'Your payment of ₱5,000.00 was received. Ref: RB-2026-0558', 'success', 0, '2026-09-25 15:40:03'),
(10, NULL, 10, 'Maintenance Submitted', 'Your request (#5) has been received.', 'info', 0, '2026-09-25 15:43:59'),
(11, NULL, 11, 'Payment Reminder', 'RentBuddy Reminder: Your rent payment of ₱5,000.00 is due on Sep 26, 2026.', 'info', 0, '2026-09-25 15:47:43'),
(12, NULL, 11, 'Payment Reminder', 'RentBuddy Reminder: Your rent payment of ₱5,000.00 is due on Sep 26, 2026. Note: PLEASE PAY YOUR FEES!', 'info', 0, '2026-09-25 15:51:43'),
(13, NULL, 11, 'Payment Reminder', 'RentBuddy Reminder: Your rent payment of ₱5,000.00 is due on Sep 26, 2026. Note: BAYAD HOI', 'info', 0, '2026-09-27 16:01:42'),
(14, NULL, 10, 'Payment Reminder', 'RentBuddy Reminder: Your rent payment of ₱5,000.00 is due on Sep 29, 2026. Note: BAYAD!!!!!!', 'info', 0, '2026-09-27 16:02:55'),
(15, NULL, 10, 'Overdue Rent Notice', 'RentBuddy Alert: Hi Albert Fernandez, your rent payment of ₱5,000.00 was due yesterday and is now 1 day overdue. Please settle your account immediately.', 'overdue', 0, '2026-09-29 16:03:37'),
(17, NULL, 10, 'Payment Reminder', 'RentBuddy Reminder: Hi Albert Fernandez, your rent of ₱5,000.00 is due on Sep 29, 2026. Note: pay', 'due', 0, '2026-09-25 16:47:28');

-- --------------------------------------------------------

--
-- Table structure for table `properties`
--

CREATE TABLE `properties` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `address` varchar(255) NOT NULL,
  `city` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `properties`
--

INSERT INTO `properties` (`id`, `name`, `address`, `city`, `description`, `created_at`) VALUES
(1, 'Sunrise Apartments', '123 Main Street', 'Springfield', 'Modern 12-unit apartment complex', '2026-09-24 03:37:23'),
(2, 'Oakwood Villas', '456 Oak Avenue', 'Riverside', 'Luxury townhouse community', '2026-09-24 03:37:23');

-- --------------------------------------------------------

--
-- Table structure for table `rent_payments`
--

CREATE TABLE `rent_payments` (
  `id` int(11) NOT NULL,
  `tenant_id` int(11) NOT NULL,
  `lease_id` int(11) DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `due_date` date NOT NULL,
  `payment_date` datetime DEFAULT NULL,
  `submitted_at` datetime DEFAULT NULL,
  `verified_by` varchar(100) DEFAULT NULL,
  `payment_method` varchar(50) DEFAULT 'demo',
  `reference_no` varchar(50) DEFAULT NULL,
  `transaction_ref` varchar(100) DEFAULT NULL,
  `status` enum('paid','unpaid','pending','overdue') DEFAULT 'unpaid',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `rent_payments`
--

INSERT INTO `rent_payments` (`id`, `tenant_id`, `lease_id`, `amount`, `due_date`, `payment_date`, `submitted_at`, `verified_by`, `payment_method`, `reference_no`, `transaction_ref`, `status`, `notes`, `created_at`) VALUES
(1, 1, 1, 1200.00, '2024-09-01', '2024-08-30 10:15:00', NULL, NULL, 'demo', 'RB-2024-0001', NULL, 'paid', NULL, '2026-09-24 03:37:23'),
(2, 1, 1, 1200.00, '2024-10-01', '2024-09-29 09:00:00', NULL, NULL, 'demo', 'RB-2024-0002', NULL, 'paid', NULL, '2026-09-24 03:37:23'),
(3, 1, 1, 1200.00, '2024-11-01', NULL, NULL, NULL, 'demo', NULL, NULL, 'overdue', NULL, '2026-09-24 03:37:23'),
(4, 2, 2, 1200.00, '2024-10-01', '2024-09-28 14:30:00', NULL, NULL, 'demo', 'RB-2024-0003', NULL, 'paid', NULL, '2026-09-24 03:37:23'),
(5, 2, 2, 1200.00, '2024-11-01', NULL, NULL, NULL, 'demo', NULL, NULL, 'overdue', NULL, '2026-09-24 03:37:23'),
(6, 3, 3, 1800.00, '2024-09-15', NULL, NULL, NULL, 'demo', NULL, NULL, 'overdue', NULL, '2026-09-24 03:37:23'),
(7, 3, 3, 1800.00, '2024-10-15', NULL, NULL, NULL, 'demo', NULL, NULL, 'overdue', NULL, '2026-09-24 03:37:23'),
(8, 4, 4, 2500.00, '2024-10-05', '2024-10-03 11:20:00', NULL, NULL, 'demo', 'RB-2024-0004', NULL, 'paid', NULL, '2026-09-24 03:37:23'),
(15, 11, NULL, 5000.00, '2026-09-26', NULL, NULL, NULL, 'demo', 'RB-2026-6623', NULL, 'unpaid', 'PAY NA BAYRANAN!', '2026-09-25 15:51:30'),
(16, 10, NULL, 5000.00, '2026-09-29', NULL, NULL, NULL, 'demo', 'RB-2026-7108', NULL, 'unpaid', '', '2026-09-27 16:02:45');

-- --------------------------------------------------------

--
-- Table structure for table `rent_reminders`
--

CREATE TABLE `rent_reminders` (
  `id` int(11) NOT NULL,
  `tenant_id` int(11) NOT NULL,
  `payment_id` int(11) DEFAULT NULL,
  `reminder_type` enum('upcoming','due','overdue') NOT NULL,
  `message` text NOT NULL,
  `sent_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_read` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `rent_reminders`
--

INSERT INTO `rent_reminders` (`id`, `tenant_id`, `payment_id`, `reminder_type`, `message`, `sent_at`, `is_read`) VALUES
(1, 10, 16, 'overdue', 'RentBuddy Alert: Hi Albert Fernandez, your rent payment of ₱5,000.00 was due yesterday and is now 1 day overdue. Please settle your account immediately.', '2026-09-29 16:03:37', 0),
(2, 10, 16, 'upcoming', 'RentBuddy Reminder: Hi Albert Fernandez, your rent of ₱5,000.00 is due on Sep 29, 2026. Note: pay', '2026-09-25 16:47:28', 0);

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `id` int(11) NOT NULL,
  `setting_key` varchar(50) NOT NULL,
  `setting_value` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`id`, `setting_key`, `setting_value`) VALUES
(1, 'reminder_days_before', '3'),
(2, 'reminder_send_time', '08:00'),
(3, 'reminder_overdue_frequency', 'daily'),
(4, 'company_name', 'RentBuddy Property Management'),
(5, 'currency', 'PHP'),
(12, 'admin_phone', '+639307708222'),
(17, 'sms_enabled', '1'),
(18, 'automation_last_run', '2026-09-30 00:45:58'),
(20, 'schema_version', '2');

-- --------------------------------------------------------

--
-- Table structure for table `tenants`
--

CREATE TABLE `tenants` (
  `id` int(11) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `contact_number` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `unit_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tenants`
--

INSERT INTO `tenants` (`id`, `full_name`, `contact_number`, `email`, `address`, `unit_id`, `created_at`) VALUES
(1, 'John Smith', '555-0101', 'john.smith@email.com', '123 Main St, Springfield', 1, '2026-09-24 03:37:23'),
(2, 'Maria Garcia', '555-0102', 'maria.garcia@email.com', '123 Main St, Springfield', 2, '2026-09-24 03:37:23'),
(3, 'David Chen', '555-0103', 'david.chen@email.com', '123 Main St, Springfield', 4, '2026-09-24 03:37:23'),
(4, 'Sarah Johnson', '555-0104', 'sarah.johnson@email.com', '456 Oak Ave, Riverside', 6, '2026-09-24 03:37:23'),
(10, 'Albert Fernandez', '09307708222', 'albert@gmail.com', 'castlevenia', 5, '2026-09-24 06:04:38'),
(11, 'Portia Carmel', '09651267080', 'portia@gmail.com', 'castlevenia', 6, '2026-09-25 15:45:37');

-- --------------------------------------------------------

--
-- Table structure for table `units`
--

CREATE TABLE `units` (
  `id` int(11) NOT NULL,
  `property_id` int(11) NOT NULL,
  `unit_number` varchar(20) NOT NULL,
  `bedrooms` int(11) DEFAULT 1,
  `bathrooms` int(11) DEFAULT 1,
  `monthly_rent` decimal(10,2) NOT NULL,
  `status` enum('available','occupied','maintenance') DEFAULT 'available',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `units`
--

INSERT INTO `units` (`id`, `property_id`, `unit_number`, `bedrooms`, `bathrooms`, `monthly_rent`, `status`, `created_at`) VALUES
(1, 1, 'A-101', 2, 1, 1200.00, 'occupied', '2026-09-24 03:37:23'),
(2, 1, 'A-102', 2, 1, 1200.00, 'occupied', '2026-09-24 03:37:23'),
(3, 1, 'A-103', 1, 1, 950.00, 'available', '2026-09-24 03:37:23'),
(4, 1, 'B-201', 3, 2, 1800.00, 'occupied', '2026-09-24 03:37:23'),
(5, 2, 'V-01', 3, 2, 2000.00, 'occupied', '2026-09-24 03:37:23'),
(6, 2, 'V-02', 4, 3, 2500.00, 'occupied', '2026-09-24 03:37:23');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('admin','tenant') NOT NULL DEFAULT 'tenant',
  `tenant_id` int(11) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_login` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password_hash`, `role`, `tenant_id`, `is_active`, `created_at`, `last_login`) VALUES
(1, 'admin', 'admin@rentbuddy.com', '$2y$10$PLACEHOLDER', 'admin', NULL, 1, '2026-09-24 03:37:23', NULL),
(2, 'john', 'john.smith@email.com', '$2y$10$PLACEHOLDER', 'tenant', 1, 1, '2026-09-24 03:37:23', NULL),
(3, 'maria', 'maria.garcia@email.com', '$2y$10$PLACEHOLDER', 'tenant', 2, 1, '2026-09-24 03:37:23', NULL),
(4, 'david', 'david.chen@email.com', '$2y$10$PLACEHOLDER', 'tenant', 3, 1, '2026-09-24 03:37:23', NULL),
(5, 'sarah', 'sarah.johnson@email.com', '$2y$10$PLACEHOLDER', 'tenant', 4, 1, '2026-09-24 03:37:23', NULL),
(7, 'admin1', 'admin1@gmail.com.com', '$2y$10$FVeDhh0KqoA0R6M0YuWS8em5lWt29cbhhwTNjUwvi0JVVPn5mhFT6', 'admin', NULL, 1, '2026-09-24 03:49:30', '2026-09-30 00:47:00'),
(8, 'alex_j', 'jojo@gmail.com', '$2y$10$wrtX.ksVcDm0XgExeFC.q.GUx6EiG02ZjuoQd7KQWJ5jkBnnJ2Cdq', 'tenant', 5, 0, '2026-09-24 03:49:30', '2026-09-24 12:01:22'),
(11, 'albert', 'albert@gmail.com', '$2y$10$xXtCi2YOsA6.CR1JENz3XeirrMNfS8U6VeL/DPNAVHB50tBwiqH8O', 'tenant', 10, 1, '2026-09-24 06:04:39', '2026-09-30 00:45:58'),
(12, 'portia', 'portia@gmail.com', '$2y$10$xNGDVymVW09DkonTaZxjVezjoBZHQ0PeJEawWvN860c1uyIvvLBzO', 'tenant', 11, 1, '2026-09-25 15:45:38', '2026-09-25 23:46:19');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `documents`
--
ALTER TABLE `documents`
  ADD PRIMARY KEY (`id`),
  ADD KEY `tenant_id` (`tenant_id`),
  ADD KEY `property_id` (`property_id`);

--
-- Indexes for table `leases`
--
ALTER TABLE `leases`
  ADD PRIMARY KEY (`id`),
  ADD KEY `tenant_id` (`tenant_id`),
  ADD KEY `unit_id` (`unit_id`);

--
-- Indexes for table `login_attempts`
--
ALTER TABLE `login_attempts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_identifier_time` (`identifier`,`attempted_at`),
  ADD KEY `idx_ip_time` (`ip_address`,`attempted_at`);

--
-- Indexes for table `maintenance_requests`
--
ALTER TABLE `maintenance_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `tenant_id` (`tenant_id`),
  ADD KEY `unit_id` (`unit_id`);

--
-- Indexes for table `maintenance_updates`
--
ALTER TABLE `maintenance_updates`
  ADD PRIMARY KEY (`id`),
  ADD KEY `request_id` (`request_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `tenant_id` (`tenant_id`);

--
-- Indexes for table `properties`
--
ALTER TABLE `properties`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `rent_payments`
--
ALTER TABLE `rent_payments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `reference_no` (`reference_no`),
  ADD KEY `tenant_id` (`tenant_id`),
  ADD KEY `lease_id` (`lease_id`),
  ADD KEY `idx_status_due` (`status`,`due_date`);

--
-- Indexes for table `rent_reminders`
--
ALTER TABLE `rent_reminders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `tenant_id` (`tenant_id`),
  ADD KEY `payment_id` (`payment_id`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`);

--
-- Indexes for table `tenants`
--
ALTER TABLE `tenants`
  ADD PRIMARY KEY (`id`),
  ADD KEY `unit_id` (`unit_id`);

--
-- Indexes for table `units`
--
ALTER TABLE `units`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_unit` (`property_id`,`unit_number`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `documents`
--
ALTER TABLE `documents`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `leases`
--
ALTER TABLE `leases`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `login_attempts`
--
ALTER TABLE `login_attempts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `maintenance_requests`
--
ALTER TABLE `maintenance_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `maintenance_updates`
--
ALTER TABLE `maintenance_updates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `properties`
--
ALTER TABLE `properties`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `rent_payments`
--
ALTER TABLE `rent_payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `rent_reminders`
--
ALTER TABLE `rent_reminders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `settings`
--
ALTER TABLE `settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

--
-- AUTO_INCREMENT for table `tenants`
--
ALTER TABLE `tenants`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `units`
--
ALTER TABLE `units`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `documents`
--
ALTER TABLE `documents`
  ADD CONSTRAINT `documents_ibfk_1` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `documents_ibfk_2` FOREIGN KEY (`property_id`) REFERENCES `properties` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `leases`
--
ALTER TABLE `leases`
  ADD CONSTRAINT `leases_ibfk_1` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `leases_ibfk_2` FOREIGN KEY (`unit_id`) REFERENCES `units` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `maintenance_requests`
--
ALTER TABLE `maintenance_requests`
  ADD CONSTRAINT `maintenance_requests_ibfk_1` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `maintenance_requests_ibfk_2` FOREIGN KEY (`unit_id`) REFERENCES `units` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `maintenance_updates`
--
ALTER TABLE `maintenance_updates`
  ADD CONSTRAINT `maintenance_updates_ibfk_1` FOREIGN KEY (`request_id`) REFERENCES `maintenance_requests` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `notifications_ibfk_2` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `rent_payments`
--
ALTER TABLE `rent_payments`
  ADD CONSTRAINT `rent_payments_ibfk_1` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `rent_payments_ibfk_2` FOREIGN KEY (`lease_id`) REFERENCES `leases` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `rent_reminders`
--
ALTER TABLE `rent_reminders`
  ADD CONSTRAINT `rent_reminders_ibfk_1` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `rent_reminders_ibfk_2` FOREIGN KEY (`payment_id`) REFERENCES `rent_payments` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `tenants`
--
ALTER TABLE `tenants`
  ADD CONSTRAINT `tenants_ibfk_1` FOREIGN KEY (`unit_id`) REFERENCES `units` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `units`
--
ALTER TABLE `units`
  ADD CONSTRAINT `units_ibfk_1` FOREIGN KEY (`property_id`) REFERENCES `properties` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
