-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Dec 22, 2025 at 02:14 PM
-- Server version: 10.6.17-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `chanchal_green_digital`
--

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `admin_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `role` enum('admin','owner','staff') DEFAULT 'admin',
  `created_at` datetime DEFAULT current_timestamp(),
  `status` enum('active','suspended') DEFAULT 'active' COMMENT 'สถานะ: active = ใช้งานได้, suspended = ระงับการใช้งาน'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `articles`
--

CREATE TABLE `articles` (
  `article_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `category` varchar(50) DEFAULT NULL,
  `image_url` varchar(255) DEFAULT NULL,
  `author_id` int(11) DEFAULT NULL,
  `views` int(11) DEFAULT 0,
  `status` enum('draft','published') DEFAULT 'draft',
  `published_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `published_start` datetime DEFAULT current_timestamp() COMMENT 'วันเวลาเริ่มแสดงบทความ',
  `published_end` datetime DEFAULT NULL COMMENT 'วันเวลาสิ้นสุดการแสดง (NULL = ไม่มีวันหมดอายุ)'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `article_comments`
--

CREATE TABLE `article_comments` (
  `comment_id` int(11) NOT NULL,
  `article_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `admin_id` int(11) DEFAULT NULL COMMENT 'Admin ที่แสดงความคิดเห็น (NULL = user)',
  `comment_text` text NOT NULL,
  `parent_comment_id` int(11) DEFAULT NULL COMMENT 'สำหรับการตอบกลับคอมเมนต์ (reply)',
  `status` enum('active','deleted','hidden') DEFAULT 'active' COMMENT 'active=แสดง, deleted=ลบโดยเจ้าของ, hidden=ซ่อนโดย Admin',
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='ความคิดเห็นบทความ';

-- --------------------------------------------------------

--
-- Table structure for table `bookings`
--

CREATE TABLE `bookings` (
  `booking_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `booking_date` date NOT NULL,
  `booking_time` time NOT NULL,
  `pickup_address` text NOT NULL,
  `estimated_weight` decimal(10,2) DEFAULT NULL,
  `estimated_price` decimal(10,2) DEFAULT NULL,
  `status` enum('pending','confirmed','completed','cancelled') DEFAULT 'pending',
  `notes` text DEFAULT NULL,
  `assigned_to` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `booking_items`
--

CREATE TABLE `booking_items` (
  `item_id` int(11) NOT NULL,
  `booking_id` int(11) NOT NULL,
  `type_id` int(11) NOT NULL,
  `quantity` decimal(10,2) NOT NULL COMMENT 'น้ำหนัก (kg)',
  `price_per_kg` decimal(10,2) NOT NULL,
  `subtotal` decimal(10,2) GENERATED ALWAYS AS (`quantity` * `price_per_kg`) STORED
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `carbon_footprint`
--

CREATE TABLE `carbon_footprint` (
  `footprint_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `booking_id` int(11) NOT NULL,
  `co2_reduced` decimal(10,2) NOT NULL COMMENT 'CO2 ที่ลดได้ (kg)',
  `trees_equivalent` decimal(10,2) NOT NULL COMMENT 'เทียบเท่าต้นไม้ (ต้น)',
  `energy_saved` decimal(10,2) NOT NULL COMMENT 'พลังงานที่ประหยัด (kWh)',
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `notification_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL COMMENT 'NULL = แจ้งเตือนสำหรับ admin',
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `type` enum('booking','payment','system','promotion') DEFAULT 'system',
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `point_transactions`
--

CREATE TABLE `point_transactions` (
  `transaction_id` int(11) NOT NULL COMMENT 'รหัสรายการแต้ม',
  `user_id` int(11) NOT NULL COMMENT 'รหัสผู้ใช้',
  `booking_id` int(11) DEFAULT NULL COMMENT 'รหัสการจอง (ถ้ามี)',
  `redemption_id` int(11) DEFAULT NULL COMMENT 'รหัสการแลกของรางวัล (ถ้ามี)',
  `points` int(11) NOT NULL COMMENT 'จำนวนแต้ม (+ คือได้รับ, - คือใช้)',
  `amount` decimal(10,2) DEFAULT NULL COMMENT 'ยอดเงินที่ใช้คำนวณ (ถ้ามี)',
  `transaction_type` enum('earn','redeem','adjustment') NOT NULL DEFAULT 'earn' COMMENT 'ประเภท: earn=ได้รับ, redeem=แลก, adjustment=ปรับปรุง',
  `description` text DEFAULT NULL COMMENT 'คำอธิบายรายการ',
  `created_at` datetime DEFAULT current_timestamp() COMMENT 'วันที่ทำรายการ'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='ประวัติการทำรายการแต้มสะสม';

-- --------------------------------------------------------

--
-- Table structure for table `prices`
--

CREATE TABLE `prices` (
  `price_id` int(11) NOT NULL,
  `type_id` int(11) NOT NULL,
  `price_per_kg` decimal(10,2) NOT NULL,
  `effective_date` date NOT NULL,
  `is_current` tinyint(1) DEFAULT 1,
  `updated_by` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `recycle_types`
--

CREATE TABLE `recycle_types` (
  `type_id` int(11) NOT NULL,
  `type_name` varchar(100) NOT NULL,
  `category` varchar(50) NOT NULL COMMENT 'plastic, paper, metal, glass',
  `description` text DEFAULT NULL,
  `image_url` varchar(255) DEFAULT NULL,
  `co2_reduction` decimal(5,2) DEFAULT 0.00 COMMENT 'CO2 ที่ลดได้ต่อ 1 kg',
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `rewards`
--

CREATE TABLE `rewards` (
  `reward_id` int(11) NOT NULL COMMENT 'รหัสของรางวัล',
  `reward_name` varchar(200) NOT NULL COMMENT 'ชื่อของรางวัล',
  `description` text DEFAULT NULL COMMENT 'รายละเอียดของรางวัล',
  `points_required` int(11) NOT NULL COMMENT 'แต้มที่ต้องใช้แลก',
  `stock_quantity` int(11) DEFAULT 0 COMMENT 'จำนวนคงเหลือ (0 = ไม่จำกัด)',
  `image_url` varchar(255) DEFAULT NULL COMMENT 'รูปภาพของรางวัล',
  `category` enum('food','product','voucher','discount') DEFAULT 'product' COMMENT 'หมวดหมู่',
  `STATUS` enum('active','inactive','out_of_stock') DEFAULT 'active' COMMENT 'สถานะ',
  `created_at` datetime DEFAULT current_timestamp() COMMENT 'วันที่สร้าง',
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT 'วันที่แก้ไข'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='ตารางของรางวัลที่แลกได้';

-- --------------------------------------------------------

--
-- Table structure for table `reward_redemptions`
--

CREATE TABLE `reward_redemptions` (
  `redemption_id` int(11) NOT NULL COMMENT 'รหัสการแลก',
  `user_id` int(11) NOT NULL COMMENT 'ผู้ใช้',
  `redeemed_by` int(11) DEFAULT NULL COMMENT 'Admin ที่แลกให้ลูกค้า',
  `reward_id` int(11) NOT NULL COMMENT 'ของรางวัล',
  `points_used` int(11) NOT NULL COMMENT 'แต้มที่ใช้แลก',
  `quantity` int(11) DEFAULT 1 COMMENT 'จำนวนที่แลก',
  `total_points` int(11) NOT NULL COMMENT 'แต้มรวมทั้งหมด (points_used * quantity)',
  `status` enum('completed','cancelled') DEFAULT 'completed' COMMENT 'สถานะ: completed=เสร็จสิ้น, cancelled=ยกเลิก',
  `redemption_date` datetime DEFAULT current_timestamp() COMMENT 'วันที่แลก',
  `delivery_method` enum('pickup','delivery') DEFAULT 'pickup' COMMENT 'วิธีรับ: มารับเอง/จัดส่ง',
  `delivery_address` text DEFAULT NULL COMMENT 'ที่อยู่จัดส่ง',
  `notes` text DEFAULT NULL COMMENT 'หมายเหตุจากลูกค้า',
  `admin_note` text DEFAULT NULL COMMENT 'หมายเหตุจาก Admin',
  `completed_at` datetime DEFAULT NULL COMMENT 'วันที่เสร็จสิ้น',
  `created_at` datetime DEFAULT current_timestamp() COMMENT 'วันที่สร้าง'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='ตารางประวัติการแลกของรางวัล';

-- --------------------------------------------------------

--
-- Table structure for table `transactions`
--

CREATE TABLE `transactions` (
  `transaction_id` int(11) NOT NULL,
  `booking_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `total_weight` decimal(10,2) NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `payment_method` enum('cash','bank_transfer','promptpay') DEFAULT 'cash',
  `payment_status` enum('pending','paid','failed') DEFAULT 'pending',
  `payment_date` datetime DEFAULT NULL,
  `receipt_url` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `province` varchar(50) DEFAULT NULL,
  `district` varchar(50) DEFAULT NULL,
  `user_level` enum('Bronze','Silver','Gold') DEFAULT 'Bronze',
  `total_points` int(11) DEFAULT 0,
  `status` enum('active','suspended') DEFAULT 'active',
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `points` int(11) DEFAULT 0 COMMENT 'แต้มสะสมปัจจุบัน',
  `security_answer` varchar(255) DEFAULT NULL COMMENT 'คำตอบคำถามความปลอดภัย: เพื่อนสนิทของคุณคือใคร'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_points`
--

CREATE TABLE `user_points` (
  `point_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `booking_id` int(11) DEFAULT NULL,
  `points_earned` int(11) DEFAULT 0,
  `points_used` int(11) DEFAULT 0,
  `balance` int(11) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`admin_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD KEY `idx_username` (`username`),
  ADD KEY `idx_role` (`role`);

--
-- Indexes for table `articles`
--
ALTER TABLE `articles`
  ADD PRIMARY KEY (`article_id`),
  ADD KEY `author_id` (`author_id`),
  ADD KEY `idx_category` (`category`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_published_at` (`published_at`),
  ADD KEY `idx_views` (`views`),
  ADD KEY `idx_published_start` (`published_start`),
  ADD KEY `idx_published_end` (`published_end`);

--
-- Indexes for table `article_comments`
--
ALTER TABLE `article_comments`
  ADD PRIMARY KEY (`comment_id`),
  ADD KEY `idx_article_id` (`article_id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_parent_comment_id` (`parent_comment_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `idx_admin_id` (`admin_id`);

--
-- Indexes for table `bookings`
--
ALTER TABLE `bookings`
  ADD PRIMARY KEY (`booking_id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_booking_date` (`booking_date`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_assigned_to` (`assigned_to`);

--
-- Indexes for table `booking_items`
--
ALTER TABLE `booking_items`
  ADD PRIMARY KEY (`item_id`),
  ADD KEY `idx_booking_id` (`booking_id`),
  ADD KEY `idx_type_id` (`type_id`);

--
-- Indexes for table `carbon_footprint`
--
ALTER TABLE `carbon_footprint`
  ADD PRIMARY KEY (`footprint_id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_booking_id` (`booking_id`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`notification_id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_is_read` (`is_read`),
  ADD KEY `idx_type` (`type`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `point_transactions`
--
ALTER TABLE `point_transactions`
  ADD PRIMARY KEY (`transaction_id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_booking_id` (`booking_id`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `redemption_id` (`redemption_id`);

--
-- Indexes for table `prices`
--
ALTER TABLE `prices`
  ADD PRIMARY KEY (`price_id`),
  ADD KEY `updated_by` (`updated_by`),
  ADD KEY `idx_type_id` (`type_id`),
  ADD KEY `idx_is_current` (`is_current`),
  ADD KEY `idx_effective_date` (`effective_date`);

--
-- Indexes for table `recycle_types`
--
ALTER TABLE `recycle_types`
  ADD PRIMARY KEY (`type_id`),
  ADD KEY `idx_category` (`category`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `rewards`
--
ALTER TABLE `rewards`
  ADD PRIMARY KEY (`reward_id`),
  ADD KEY `idx_status` (`STATUS`),
  ADD KEY `idx_category` (`category`),
  ADD KEY `idx_points` (`points_required`);

--
-- Indexes for table `reward_redemptions`
--
ALTER TABLE `reward_redemptions`
  ADD PRIMARY KEY (`redemption_id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_reward_id` (`reward_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_redemption_date` (`redemption_date`),
  ADD KEY `redeemed_by` (`redeemed_by`);

--
-- Indexes for table `transactions`
--
ALTER TABLE `transactions`
  ADD PRIMARY KEY (`transaction_id`),
  ADD KEY `idx_booking_id` (`booking_id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_payment_status` (`payment_status`),
  ADD KEY `idx_payment_date` (`payment_date`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_phone` (`phone`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `user_points`
--
ALTER TABLE `user_points`
  ADD PRIMARY KEY (`point_id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_booking_id` (`booking_id`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `admin_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `articles`
--
ALTER TABLE `articles`
  MODIFY `article_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `article_comments`
--
ALTER TABLE `article_comments`
  MODIFY `comment_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `bookings`
--
ALTER TABLE `bookings`
  MODIFY `booking_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `booking_items`
--
ALTER TABLE `booking_items`
  MODIFY `item_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `carbon_footprint`
--
ALTER TABLE `carbon_footprint`
  MODIFY `footprint_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `notification_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `point_transactions`
--
ALTER TABLE `point_transactions`
  MODIFY `transaction_id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'รหัสรายการแต้ม';

--
-- AUTO_INCREMENT for table `prices`
--
ALTER TABLE `prices`
  MODIFY `price_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `recycle_types`
--
ALTER TABLE `recycle_types`
  MODIFY `type_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `rewards`
--
ALTER TABLE `rewards`
  MODIFY `reward_id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'รหัสของรางวัล';

--
-- AUTO_INCREMENT for table `reward_redemptions`
--
ALTER TABLE `reward_redemptions`
  MODIFY `redemption_id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'รหัสการแลก';

--
-- AUTO_INCREMENT for table `transactions`
--
ALTER TABLE `transactions`
  MODIFY `transaction_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user_points`
--
ALTER TABLE `user_points`
  MODIFY `point_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `articles`
--
ALTER TABLE `articles`
  ADD CONSTRAINT `articles_ibfk_1` FOREIGN KEY (`author_id`) REFERENCES `admins` (`admin_id`) ON DELETE SET NULL;

--
-- Constraints for table `article_comments`
--
ALTER TABLE `article_comments`
  ADD CONSTRAINT `article_comments_ibfk_1` FOREIGN KEY (`article_id`) REFERENCES `articles` (`article_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `article_comments_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `article_comments_ibfk_3` FOREIGN KEY (`parent_comment_id`) REFERENCES `article_comments` (`comment_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `article_comments_ibfk_4` FOREIGN KEY (`admin_id`) REFERENCES `admins` (`admin_id`) ON DELETE CASCADE;

--
-- Constraints for table `bookings`
--
ALTER TABLE `bookings`
  ADD CONSTRAINT `bookings_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `bookings_ibfk_2` FOREIGN KEY (`assigned_to`) REFERENCES `admins` (`admin_id`) ON DELETE SET NULL;

--
-- Constraints for table `booking_items`
--
ALTER TABLE `booking_items`
  ADD CONSTRAINT `booking_items_ibfk_1` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`booking_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `booking_items_ibfk_2` FOREIGN KEY (`type_id`) REFERENCES `recycle_types` (`type_id`) ON DELETE CASCADE;

--
-- Constraints for table `carbon_footprint`
--
ALTER TABLE `carbon_footprint`
  ADD CONSTRAINT `carbon_footprint_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `carbon_footprint_ibfk_2` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`booking_id`) ON DELETE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `point_transactions`
--
ALTER TABLE `point_transactions`
  ADD CONSTRAINT `point_transactions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `point_transactions_ibfk_2` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`booking_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `point_transactions_ibfk_3` FOREIGN KEY (`redemption_id`) REFERENCES `reward_redemptions` (`redemption_id`) ON DELETE SET NULL;

--
-- Constraints for table `prices`
--
ALTER TABLE `prices`
  ADD CONSTRAINT `prices_ibfk_1` FOREIGN KEY (`type_id`) REFERENCES `recycle_types` (`type_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `prices_ibfk_2` FOREIGN KEY (`updated_by`) REFERENCES `admins` (`admin_id`) ON DELETE SET NULL;

--
-- Constraints for table `reward_redemptions`
--
ALTER TABLE `reward_redemptions`
  ADD CONSTRAINT `reward_redemptions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reward_redemptions_ibfk_2` FOREIGN KEY (`reward_id`) REFERENCES `rewards` (`reward_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reward_redemptions_ibfk_4` FOREIGN KEY (`redeemed_by`) REFERENCES `admins` (`admin_id`) ON DELETE SET NULL;

--
-- Constraints for table `transactions`
--
ALTER TABLE `transactions`
  ADD CONSTRAINT `transactions_ibfk_1` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`booking_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `transactions_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `user_points`
--
ALTER TABLE `user_points`
  ADD CONSTRAINT `user_points_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_points_ibfk_2` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`booking_id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
