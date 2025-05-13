-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 13, 2025 at 07:41 AM
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
-- Database: `floodguard`
--

-- --------------------------------------------------------

--
-- Table structure for table `chatbox`
--

CREATE TABLE `chatbox` (
  `message_id` int(11) NOT NULL,
  `sender_id` varchar(20) NOT NULL,
  `receiver_id` varchar(20) NOT NULL,
  `message` text NOT NULL,
  `sent_at` datetime DEFAULT current_timestamp(),
  `status` enum('read','unread') DEFAULT 'unread'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `chatbox`
--

INSERT INTO `chatbox` (`message_id`, `sender_id`, `receiver_id`, `message`, `sent_at`, `status`) VALUES
(1, 'USER-1747076156', 'USER-1747076156', 'hi', '2025-05-13 02:42:35', 'unread'),
(2, 'USER-1747075299', 'USER-1747076156', 'hi', '2025-05-13 02:44:42', 'unread'),
(3, 'USER-1747076156', 'USER-1747076156', 'hello', '2025-05-13 02:44:52', 'unread'),
(4, 'USER-1747075299', 'USER-1747076156', 'hi', '2025-05-13 02:45:10', 'unread'),
(5, 'USER-1747075299', 'USER-1747076156', 'hi', '2025-05-13 02:48:11', 'unread'),
(6, 'USER-1747076156', 'USER-1747075299', 'hi', '2025-05-13 02:50:11', 'unread'),
(7, 'USER-1747075299', 'USER-1747076156', 'what do ?', '2025-05-13 02:50:27', 'unread'),
(8, 'USER-1747080512', 'USER-1747076156', 'hi', '2025-05-13 02:58:16', 'unread'),
(9, 'USER-1747076156', 'USER-1747080512', '370', '2025-05-13 02:58:27', 'unread'),
(10, 'USER-1747076156', 'USER-1747080512', 'hello', '2025-05-13 03:19:00', 'unread');

-- --------------------------------------------------------

--
-- Table structure for table `distribution_records`
--

CREATE TABLE `distribution_records` (
  `distribution_id` varchar(50) NOT NULL,
  `distribution_date` datetime DEFAULT NULL,
  `volunteer_id` varchar(50) DEFAULT NULL,
  `victim_id` varchar(50) DEFAULT NULL,
  `inventory_id` varchar(50) DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `quantity` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `distribution_records`
--

INSERT INTO `distribution_records` (`distribution_id`, `distribution_date`, `volunteer_id`, `victim_id`, `inventory_id`, `location`, `quantity`, `created_at`) VALUES
('DIST-1747112723', '2025-05-13 11:05:23', 'USER-1747095909', 'VIC-1747111116', 'INV-1747095044', 'Shylet', 2, '2025-05-13 05:05:23');

-- --------------------------------------------------------

--
-- Table structure for table `distribution_tasks`
--

CREATE TABLE `distribution_tasks` (
  `task_id` varchar(20) NOT NULL,
  `volunteer_id` varchar(20) DEFAULT NULL,
  `inventory_id` varchar(20) DEFAULT NULL,
  `victim_id` varchar(20) DEFAULT NULL,
  `quantity` int(11) DEFAULT NULL,
  `location` varchar(100) DEFAULT NULL,
  `status` enum('pending','completed','cancelled') DEFAULT 'pending',
  `assigned_date` datetime DEFAULT current_timestamp(),
  `completion_date` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `distribution_tasks`
--

INSERT INTO `distribution_tasks` (`task_id`, `volunteer_id`, `inventory_id`, `victim_id`, `quantity`, `location`, `status`, `assigned_date`, `completion_date`) VALUES
('TASK-1747111610', 'USER-1747095909', 'INV-1747095044', 'VIC-1747111116', 1, 'Shylet', 'pending', '2025-05-13 10:46:50', NULL),
('TASK-1747111926', 'USER-1747095909', 'INV-1747097486', 'VIC-1747111116', 2, 'Shylet', 'pending', '2025-05-13 10:52:06', NULL),
('TASK-1747112347', 'USER-1747095909', 'INV-1747094180', 'VIC-1747111116', 2, 'shylet', 'completed', '2025-05-13 10:59:07', '2025-05-13 10:59:11'),
('TASK-1747112716', 'USER-1747095909', 'INV-1747095044', 'VIC-1747111116', 2, 'Shylet', 'completed', '2025-05-13 11:05:16', '2025-05-13 11:05:23');

-- --------------------------------------------------------

--
-- Table structure for table `donations`
--

CREATE TABLE `donations` (
  `donation_id` varchar(20) NOT NULL,
  `donor_id` varchar(20) NOT NULL,
  `donation_type` enum('monetary','food','clothing','medical','other') NOT NULL,
  `amount` decimal(12,2) DEFAULT NULL,
  `items` varchar(255) DEFAULT NULL,
  `quantity` int(11) DEFAULT NULL,
  `payment_method` varchar(50) DEFAULT NULL,
  `transaction_id` varchar(100) DEFAULT NULL,
  `donation_date` datetime NOT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `approved_by` varchar(20) DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `expiry_date` date DEFAULT NULL COMMENT 'Expiry date for perishable donations (food, medicine)'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `donations`
--

INSERT INTO `donations` (`donation_id`, `donor_id`, `donation_type`, `amount`, `items`, `quantity`, `payment_method`, `transaction_id`, `donation_date`, `status`, `approved_by`, `approved_at`, `expiry_date`) VALUES
('DON-1747093430', 'USER-1747085071', 'monetary', 100.00, NULL, NULL, 'bank', 'bk-24lk', '2025-05-13 01:43:50', 'approved', 'USER-1747076156', '2025-05-13 05:55:53', NULL),
('DON-1747093836', 'USER-1747085071', 'monetary', 100.00, NULL, NULL, 'bank', 'bk-24lk', '2025-05-13 01:50:36', 'rejected', 'USER-1747076156', '2025-05-13 05:54:55', NULL),
('DON-1747093944', 'USER-1747085071', 'monetary', 100.00, NULL, NULL, 'bank', 'bk-24lk', '2025-05-13 01:52:24', 'rejected', 'USER-1747076156', '2025-05-13 05:54:54', NULL),
('DON-1747093963', 'USER-1747085071', 'monetary', 100.00, NULL, NULL, 'bank', '100', '2025-05-13 01:52:43', 'approved', 'USER-1747076156', '2025-05-13 05:54:49', NULL),
('DON-1747094111', 'USER-1747085071', 'monetary', 100.00, NULL, NULL, 'bank', '100', '2025-05-13 01:55:11', 'rejected', 'USER-1747076156', '2025-05-13 05:56:11', NULL),
('DON-1747094136', 'USER-1747085071', 'medical', NULL, 'Napa', 10, NULL, NULL, '2025-05-13 01:55:36', 'approved', 'USER-1747076156', '2025-05-13 05:58:00', NULL),
('DON-1747094186', 'USER-1747085071', 'medical', NULL, 'Napa', 10, NULL, NULL, '2025-05-13 01:56:26', 'rejected', 'USER-1747076156', '2025-05-13 06:05:03', NULL),
('DON-1747094381', 'USER-1747085071', 'medical', NULL, 'Napa', 10, NULL, NULL, '2025-05-13 01:59:41', 'rejected', 'USER-1747076156', '2025-05-13 06:05:05', NULL),
('DON-1747094403', 'USER-1747085071', 'medical', NULL, 'Napa', 10, NULL, NULL, '2025-05-13 02:00:03', 'rejected', 'USER-1747076156', '2025-05-13 06:05:08', NULL),
('DON-1747094658', 'USER-1747085071', 'clothing', NULL, 'winter cloth ', 2, NULL, NULL, '2025-05-13 02:04:18', 'approved', 'USER-1747076156', '2025-05-13 06:10:44', NULL),
('DON-1747095037', 'USER-1747085071', 'monetary', 100.00, NULL, NULL, 'bank', 'bkl-56', '2025-05-13 02:10:37', 'approved', 'USER-1747076156', '2025-05-13 06:10:57', NULL),
('DON-1747097445', 'USER-1747085071', 'medical', NULL, 'Fazil', 10, NULL, NULL, '2025-05-13 02:50:45', 'approved', 'USER-1747076156', '2025-05-13 06:51:26', '2025-05-31'),
('DON-1747097609', 'USER-1747085071', 'monetary', 10.00, NULL, NULL, 'credit', '12345', '2025-05-13 02:53:29', 'rejected', 'USER-1747076156', '2025-05-13 06:55:32', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `donor_profiles`
--

CREATE TABLE `donor_profiles` (
  `donor_id` varchar(20) NOT NULL,
  `donor_type` enum('individual','corporation','organization') DEFAULT NULL,
  `total_donations` int(11) DEFAULT 0,
  `total_amount` decimal(12,2) DEFAULT 0.00,
  `last_donation_date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `donor_profiles`
--

INSERT INTO `donor_profiles` (`donor_id`, `donor_type`, `total_donations`, `total_amount`, `last_donation_date`) VALUES
('USER-1747085071', 'individual', 0, 0.00, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `inventory`
--

CREATE TABLE `inventory` (
  `inventory_id` varchar(20) NOT NULL,
  `item_type` enum('food','clothing','medical','other') NOT NULL,
  `quantity` int(11) NOT NULL,
  `item_name` varchar(255) NOT NULL,
  `item_description` text DEFAULT NULL,
  `added_date` datetime DEFAULT current_timestamp(),
  `source_donation_id` varchar(20) DEFAULT NULL,
  `status` enum('available','reserved','distributed') DEFAULT 'available',
  `expiry_date` date DEFAULT NULL COMMENT 'Expiry date for perishable items'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `inventory`
--

INSERT INTO `inventory` (`inventory_id`, `item_type`, `quantity`, `item_name`, `item_description`, `added_date`, `source_donation_id`, `status`, `expiry_date`) VALUES
('INV-1747094180', 'medical', 10, 'Napa', '', '2025-05-13 05:56:20', 'DON-1747094136', 'available', '2025-05-30'),
('INV-1747095044', 'clothing', 0, 'winter cloth ', NULL, '2025-05-13 06:10:44', 'DON-1747094658', 'available', NULL),
('INV-1747097486', 'medical', 10, 'Fazil', '', '2025-05-13 06:51:26', 'DON-1747097445', 'available', '2025-09-27');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `notification_id` varchar(20) NOT NULL,
  `user_id` varchar(20) NOT NULL,
  `title` varchar(100) NOT NULL,
  `message` text NOT NULL,
  `type` enum('donation_approved','donation_rejected','other') NOT NULL,
  `reference_id` varchar(20) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `is_read` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `relief_camps`
--

CREATE TABLE `relief_camps` (
  `camp_id` varchar(20) NOT NULL,
  `camp_name` varchar(100) NOT NULL,
  `location` varchar(255) NOT NULL,
  `capacity` int(11) NOT NULL,
  `current_occupancy` int(11) DEFAULT 0,
  `managed_by` varchar(20) DEFAULT NULL,
  `last_updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `relief_camps`
--

INSERT INTO `relief_camps` (`camp_id`, `camp_name`, `location`, `capacity`, `current_occupancy`, `managed_by`, `last_updated`) VALUES
('CAMP-1747099861', 'Shylet Zila High School ', 'Zinda Bazar, Shylet', 400, 27, 'USER-1747095909', '2025-05-13 05:34:59');

-- --------------------------------------------------------

--
-- Table structure for table `tasks`
--

CREATE TABLE `tasks` (
  `task_id` varchar(20) NOT NULL,
  `title` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `priority` enum('high','medium','low') NOT NULL,
  `due_date` datetime NOT NULL,
  `location` varchar(255) NOT NULL,
  `assigned_to` varchar(20) NOT NULL,
  `status` enum('pending','in_progress','completed') DEFAULT 'pending',
  `created_at` datetime DEFAULT current_timestamp(),
  `completed_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tasks`
--

INSERT INTO `tasks` (`task_id`, `title`, `description`, `priority`, `due_date`, `location`, `assigned_to`, `status`, `created_at`, `completed_at`) VALUES
('TASK-1747083392', 'help', 'qqwer', 'medium', '2025-05-14 02:56:00', '', 'USER-1747075299', 'completed', '2025-05-13 02:56:32', '2025-05-13 02:56:51'),
('TSK-1747111926', 'Relief Distribution Task', 'Distribute 2 units of Fazil to Shariar at Shylet. Distribution Task ID: TASK-1747111926', 'high', '2025-05-14 10:52:06', 'Shylet', 'USER-1747095909', 'completed', '2025-05-13 10:52:06', '2025-05-13 10:52:34'),
('TSK-1747112347', 'Relief Distribution Task', 'Distribute 2 units of Napa to Shariar at shylet. Distribution Task ID: TASK-1747112347', 'high', '2025-05-14 10:59:07', 'shylet', 'USER-1747095909', 'completed', '2025-05-13 10:59:07', '2025-05-13 10:59:11'),
('TSK-1747112716', 'Relief Distribution Task', 'Distribute 2 units of winter cloth  to Shariar at Shylet. Distribution Task ID: TASK-1747112716', 'high', '2025-05-14 11:05:16', 'Shylet', 'USER-1747095909', 'completed', '2025-05-13 11:05:16', '2025-05-13 11:05:23');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` varchar(20) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `role` enum('admin','volunteer','donor') NOT NULL,
  `profile_image` varchar(255) DEFAULT NULL,
  `registration_date` datetime DEFAULT current_timestamp(),
  `last_login` datetime DEFAULT NULL,
  `status` enum('active','inactive','suspended') DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `first_name`, `last_name`, `email`, `password`, `phone`, `role`, `profile_image`, `registration_date`, `last_login`, `status`) VALUES
('USER-1747075299', 'David', '1', 'test@gmail.com', '$2y$10$dZVsYukIy47.vh.aEZdhP.MxmZFNrAJoIM7EIrpIi5eeLCXF6GttG', '01975180401', 'volunteer', NULL, '2025-05-13 00:41:39', '2025-05-13 02:29:57', 'active'),
('USER-1747076156', 'Irtiza', 'Tasnimah', 'irtiza@gmail.com', '$2y$10$yBcGW1kTAwNDW/g8.2vECugfSgZDOpKCfC6CfYI7IKUYutNi2V7o2', '01975180401', 'admin', NULL, '2025-05-13 00:55:56', '2025-05-13 11:38:28', 'active'),
('USER-1747080512', 'Abdur', 'Rahman', 'abddur@gmail.com', '$2y$10$kPtxmZ1z07VfujzcjtlmdeetTQsU02adRNQuhi7r4xxUdzGsnp4ZO', '01975180401', 'volunteer', NULL, '2025-05-13 02:08:32', '2025-05-13 02:58:02', 'active'),
('USER-1747085071', 'Subhana', 'Rodela', 'rodela@gmail.com', '$2y$10$UII7IzMtO78OZSsFahKcW.VcFoMtHYhyHx/T8/b4ZVFcowGuKh2vW', '01783456790', 'donor', NULL, '2025-05-13 03:24:31', '2025-05-13 11:37:30', 'active'),
('USER-1747095909', 'Nur', 'Hasan', 'nur@gmail.com', '$2y$10$JnBpiPEF7.y/t0fQl2lY4uZ4eNOkGfCmU7BKfGM3z5a.HIsANBTt6', '01834946711', 'volunteer', NULL, '2025-05-13 06:25:09', '2025-05-13 11:16:01', 'active');

-- --------------------------------------------------------

--
-- Table structure for table `victims`
--

CREATE TABLE `victims` (
  `victim_id` varchar(20) NOT NULL,
  `name` varchar(100) NOT NULL,
  `family_size` int(11) NOT NULL,
  `phone` varchar(15) DEFAULT NULL,
  `location` varchar(100) NOT NULL,
  `priority` enum('high','medium','low') NOT NULL,
  `needs` varchar(255) NOT NULL,
  `status` enum('pending','assisted','relocated') DEFAULT 'pending',
  `registration_date` datetime DEFAULT current_timestamp(),
  `assisted_by` varchar(20) DEFAULT NULL,
  `assisted_date` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `victims`
--

INSERT INTO `victims` (`victim_id`, `name`, `family_size`, `phone`, `location`, `priority`, `needs`, `status`, `registration_date`, `assisted_by`, `assisted_date`) VALUES
('VIC-1747111116', 'Shariar', 4, '01847464549', 'Shylet', 'high', 'Food', 'pending', '2025-05-13 10:38:36', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `volunteer_profiles`
--

CREATE TABLE `volunteer_profiles` (
  `volunteer_id` varchar(20) NOT NULL,
  `skill_type` varchar(100) DEFAULT NULL,
  `location` varchar(100) DEFAULT NULL,
  `is_available` tinyint(1) DEFAULT 1,
  `people_helped` int(11) DEFAULT 0,
  `emergency_contact_name` varchar(100) DEFAULT NULL,
  `emergency_contact_phone` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `volunteer_profiles`
--

INSERT INTO `volunteer_profiles` (`volunteer_id`, `skill_type`, `location`, `is_available`, `people_helped`, `emergency_contact_name`, `emergency_contact_phone`) VALUES
('USER-1747075299', NULL, 'Noyakhali', 1, 1, NULL, NULL),
('USER-1747080512', 'Medical', 'Chittagong', 0, 0, NULL, NULL),
('USER-1747095909', 'Logistics', 'Shylet', 1, 3, NULL, NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `chatbox`
--
ALTER TABLE `chatbox`
  ADD PRIMARY KEY (`message_id`),
  ADD KEY `sender_id` (`sender_id`),
  ADD KEY `receiver_id` (`receiver_id`);

--
-- Indexes for table `distribution_records`
--
ALTER TABLE `distribution_records`
  ADD PRIMARY KEY (`distribution_id`),
  ADD KEY `volunteer_id` (`volunteer_id`),
  ADD KEY `victim_id` (`victim_id`),
  ADD KEY `inventory_id` (`inventory_id`);

--
-- Indexes for table `distribution_tasks`
--
ALTER TABLE `distribution_tasks`
  ADD PRIMARY KEY (`task_id`),
  ADD KEY `volunteer_id` (`volunteer_id`),
  ADD KEY `inventory_id` (`inventory_id`),
  ADD KEY `victim_id` (`victim_id`);

--
-- Indexes for table `donations`
--
ALTER TABLE `donations`
  ADD PRIMARY KEY (`donation_id`),
  ADD KEY `donor_id` (`donor_id`),
  ADD KEY `approved_by` (`approved_by`);

--
-- Indexes for table `donor_profiles`
--
ALTER TABLE `donor_profiles`
  ADD PRIMARY KEY (`donor_id`);

--
-- Indexes for table `inventory`
--
ALTER TABLE `inventory`
  ADD PRIMARY KEY (`inventory_id`),
  ADD KEY `source_donation_id` (`source_donation_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`notification_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `relief_camps`
--
ALTER TABLE `relief_camps`
  ADD PRIMARY KEY (`camp_id`),
  ADD KEY `managed_by` (`managed_by`);

--
-- Indexes for table `tasks`
--
ALTER TABLE `tasks`
  ADD PRIMARY KEY (`task_id`),
  ADD KEY `assigned_to` (`assigned_to`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `victims`
--
ALTER TABLE `victims`
  ADD PRIMARY KEY (`victim_id`),
  ADD KEY `fk_assisted_by` (`assisted_by`);

--
-- Indexes for table `volunteer_profiles`
--
ALTER TABLE `volunteer_profiles`
  ADD PRIMARY KEY (`volunteer_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `chatbox`
--
ALTER TABLE `chatbox`
  MODIFY `message_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `chatbox`
--
ALTER TABLE `chatbox`
  ADD CONSTRAINT `chatbox_ibfk_1` FOREIGN KEY (`sender_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `chatbox_ibfk_2` FOREIGN KEY (`receiver_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `distribution_records`
--
ALTER TABLE `distribution_records`
  ADD CONSTRAINT `distribution_records_ibfk_1` FOREIGN KEY (`volunteer_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `distribution_records_ibfk_2` FOREIGN KEY (`victim_id`) REFERENCES `victims` (`victim_id`),
  ADD CONSTRAINT `distribution_records_ibfk_3` FOREIGN KEY (`inventory_id`) REFERENCES `inventory` (`inventory_id`);

--
-- Constraints for table `distribution_tasks`
--
ALTER TABLE `distribution_tasks`
  ADD CONSTRAINT `distribution_tasks_ibfk_1` FOREIGN KEY (`volunteer_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `distribution_tasks_ibfk_2` FOREIGN KEY (`inventory_id`) REFERENCES `inventory` (`inventory_id`),
  ADD CONSTRAINT `distribution_tasks_ibfk_3` FOREIGN KEY (`victim_id`) REFERENCES `victims` (`victim_id`);

--
-- Constraints for table `donations`
--
ALTER TABLE `donations`
  ADD CONSTRAINT `donations_ibfk_1` FOREIGN KEY (`donor_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `donations_ibfk_2` FOREIGN KEY (`approved_by`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `donor_profiles`
--
ALTER TABLE `donor_profiles`
  ADD CONSTRAINT `donor_profiles_ibfk_1` FOREIGN KEY (`donor_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `inventory`
--
ALTER TABLE `inventory`
  ADD CONSTRAINT `inventory_ibfk_1` FOREIGN KEY (`source_donation_id`) REFERENCES `donations` (`donation_id`);

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `relief_camps`
--
ALTER TABLE `relief_camps`
  ADD CONSTRAINT `relief_camps_ibfk_1` FOREIGN KEY (`managed_by`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `tasks`
--
ALTER TABLE `tasks`
  ADD CONSTRAINT `tasks_ibfk_1` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `victims`
--
ALTER TABLE `victims`
  ADD CONSTRAINT `fk_assisted_by` FOREIGN KEY (`assisted_by`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `volunteer_profiles`
--
ALTER TABLE `volunteer_profiles`
  ADD CONSTRAINT `volunteer_profiles_ibfk_1` FOREIGN KEY (`volunteer_id`) REFERENCES `users` (`user_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
