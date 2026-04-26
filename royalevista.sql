-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Apr 26, 2026 at 06:45 AM
-- Server version: 8.0.43
-- PHP Version: 8.3.14

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `royalevista`
--

-- --------------------------------------------------------

--
-- Table structure for table `activity_log`
--

DROP TABLE IF EXISTS `activity_log`;
CREATE TABLE IF NOT EXISTS `activity_log` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `details` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `booked_rooms`
--

DROP TABLE IF EXISTS `booked_rooms`;
CREATE TABLE IF NOT EXISTS `booked_rooms` (
  `id` int NOT NULL AUTO_INCREMENT,
  `booking_ref` varchar(30) NOT NULL,
  `room_type_id` int NOT NULL,
  `room_type_name` varchar(100) NOT NULL,
  `room_number` varchar(20) DEFAULT NULL,
  `quantity` int DEFAULT '1',
  `price_usd` decimal(10,2) NOT NULL,
  `nights` int NOT NULL,
  `total_usd` decimal(10,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `booking_ref` (`booking_ref`),
  KEY `room_type_id` (`room_type_id`)
) ENGINE=InnoDB AUTO_INCREMENT=35 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `booked_rooms`
--

INSERT INTO `booked_rooms` (`id`, `booking_ref`, `room_type_id`, `room_type_name`, `room_number`, `quantity`, `price_usd`, `nights`, `total_usd`) VALUES
(1, 'BK2026A1', 1, 'Deluxe Room', 'D012', 1, 120.00, 3, 360.00),
(2, 'BK2026B2', 2, 'Exclusive Room', 'E023', 1, 300.00, 3, 900.00),
(3, 'BK2026C3', 4, 'Presidential Suite', 'P001', 1, 600.00, 2, 1200.00),
(4, 'BK202664211', 1, 'Deluxe Room', NULL, 1, 120.00, 2, 240.00),
(5, 'BK202663C34', 1, 'Deluxe Room', NULL, 1, 120.00, 2, 240.00),
(6, 'BK20260CCC8', 2, 'Exclusive Room', NULL, 2, 300.00, 2, 1200.00),
(7, 'BK20260CCC8', 3, 'Family Room', NULL, 1, 250.00, 2, 500.00),
(8, 'BK2026511D4', 1, 'Deluxe Room', NULL, 2, 120.00, 1, 240.00),
(9, 'BK2026CCBDD', 3, 'Family Room', NULL, 1, 250.00, 2, 500.00),
(10, 'BK2026A74D6', 5, 'Test Suites', NULL, 1, 999.00, 2, 1998.00),
(11, 'BK2026F65B9', 1, 'Deluxe Room', NULL, 1, 120.00, 2, 240.00),
(12, 'BK20268E5AC', 1, 'Deluxe Room', NULL, 1, 120.00, 2, 240.00),
(14, 'BK20264132E', 1, 'Deluxe Room', NULL, 1, 120.00, 2, 240.00),
(15, 'BK20262823E', 1, 'Deluxe Room', NULL, 1, 120.00, 2, 240.00),
(16, 'BK20269BA39', 1, 'Deluxe Room', NULL, 1, 120.00, 2, 240.00),
(17, 'BK20266898D', 1, 'Deluxe Room', NULL, 1, 120.00, 2, 240.00),
(18, 'BK2026689AA', 1, 'Deluxe Room', NULL, 1, 120.00, 2, 240.00),
(19, 'BK2026C5218', 1, 'Deluxe Room', NULL, 1, 120.00, 2, 240.00),
(20, 'BK20265DB38', 1, 'Deluxe Room', NULL, 2, 120.00, 1, 240.00),
(21, 'BK202699416', 1, 'Deluxe Room', NULL, 1, 120.00, 2, 240.00),
(22, 'BK2026730A8', 1, 'Deluxe Room', NULL, 1, 120.00, 2, 240.00),
(23, 'BK202646E33', 4, 'Presidential Suite', NULL, 1, 600.00, 2, 1200.00),
(24, 'BK20265A5B7', 1, 'Deluxe Room', NULL, 1, 120.00, 2, 240.00),
(25, 'BK2026436AF', 1, 'Deluxe Room', NULL, 1, 120.00, 2, 240.00),
(26, 'BK20265A464', 1, 'Deluxe Room', NULL, 1, 120.00, 2, 240.00),
(27, 'BK202672D08', 3, 'Family Room', NULL, 1, 250.00, 2, 500.00),
(28, 'BK20261DD41', 3, 'Family Room', NULL, 1, 250.00, 2, 500.00),
(29, 'BK2026A312F', 3, 'Family Room', NULL, 2, 250.00, 6, 3000.00),
(30, 'BK202682CBD', 1, 'Deluxe Room', NULL, 1, 120.00, 2, 240.00),
(32, 'BK2026BC2E0', 1, 'Deluxe Room', NULL, 2, 120.00, 2, 480.00),
(33, 'BK20263316F', 1, 'Deluxe Room', NULL, 1, 120.00, 8, 960.00),
(34, 'BK202613CDE', 1, 'Deluxe Room', NULL, 1, 120.00, 2, 240.00);

-- --------------------------------------------------------

--
-- Table structure for table `bookings`
--

DROP TABLE IF EXISTS `bookings`;
CREATE TABLE IF NOT EXISTS `bookings` (
  `id` int NOT NULL AUTO_INCREMENT,
  `booking_ref` varchar(30) NOT NULL,
  `invoice_no` varchar(30) DEFAULT NULL,
  `user_id` int DEFAULT NULL,
  `guest_name` varchar(200) NOT NULL,
  `guest_email` varchar(191) NOT NULL,
  `guest_phone` varchar(30) DEFAULT NULL,
  `check_in` date NOT NULL,
  `check_out` date NOT NULL,
  `nights` int NOT NULL,
  `adults` int DEFAULT '1',
  `children` int DEFAULT '0',
  `special_req` text,
  `total_usd` decimal(10,2) NOT NULL,
  `discount_usd` decimal(10,2) DEFAULT '0.00',
  `taxes_usd` decimal(10,2) DEFAULT '0.00',
  `final_usd` decimal(10,2) NOT NULL,
  `currency` varchar(10) DEFAULT 'USD',
  `offer_code` varchar(50) DEFAULT NULL,
  `member_number` varchar(50) DEFAULT NULL,
  `pay_method` enum('card','upi','paypal','hotel') DEFAULT 'hotel',
  `pay_status` enum('pending','paid','failed','refunded') DEFAULT 'pending',
  `transaction_id` varchar(200) DEFAULT NULL,
  `paid_at` timestamp NULL DEFAULT NULL,
  `status` enum('confirmed','cancelled','checked_in','checked_out') DEFAULT 'confirmed',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `booking_ref` (`booking_ref`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=38 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `bookings`
--

INSERT INTO `bookings` (`id`, `booking_ref`, `invoice_no`, `user_id`, `guest_name`, `guest_email`, `guest_phone`, `check_in`, `check_out`, `nights`, `adults`, `children`, `special_req`, `total_usd`, `discount_usd`, `taxes_usd`, `final_usd`, `currency`, `offer_code`, `member_number`, `pay_method`, `pay_status`, `transaction_id`, `paid_at`, `status`, `created_at`) VALUES
(1, 'BK2026A1', 'INV2026A1', 2, 'John Smith', 'john@example.com', '+1-555-0102', '2026-04-02', '2026-04-05', 3, 2, 0, NULL, 360.00, 0.00, 64.80, 424.80, 'USD', NULL, NULL, 'card', 'paid', NULL, NULL, 'checked_out', '2026-03-28 05:18:15'),
(2, 'BK2026B2', 'INV2026B2', 3, 'Priya Patel', 'priya@example.com', '+91-98765-43210', '2026-04-09', '2026-04-12', 3, 3, 0, NULL, 900.00, 0.00, 162.00, 1062.00, 'INR', NULL, NULL, 'upi', 'paid', NULL, NULL, 'checked_out', '2026-03-28 05:18:15'),
(3, 'BK2026C3', 'INV2026C3', 4, 'James Wilson', 'james@example.com', '+44-7700-900123', '2026-03-31', '2026-04-02', 2, 2, 0, NULL, 1200.00, 0.00, 216.00, 1416.00, 'GBP', NULL, NULL, 'card', 'paid', NULL, NULL, 'checked_out', '2026-03-28 05:18:15'),
(4, 'BK202664211', 'INV202619314', 1, 'Admin User', 'admin@royalevista.com', '', '2026-03-28', '2026-03-30', 2, 2, 0, '', 240.00, 0.00, 43.20, 283.20, 'USD', NULL, NULL, 'hotel', 'refunded', NULL, NULL, 'checked_out', '2026-03-28 06:24:24'),
(5, 'BK202663C34', 'INV2026FF5E3', 1, 'Admin User', 'admin@royalevista.com', '', '2026-03-28', '2026-03-30', 2, 2, 0, '', 240.00, 0.00, 43.20, 283.20, 'USD', NULL, NULL, 'hotel', 'refunded', NULL, NULL, 'checked_out', '2026-03-28 08:40:36'),
(6, 'BK20260CCC8', 'INV202620991', 1, 'Admin User', 'admin@royalevista.com', '', '2026-03-28', '2026-03-30', 2, 2, 0, '', 1700.00, 0.00, 306.00, 2006.00, 'USD', NULL, NULL, 'hotel', 'refunded', NULL, NULL, 'cancelled', '2026-03-28 08:42:09'),
(7, 'BK2026511D4', 'INV20266B20A', 1, 'Admin User', 'admin@royalevista.com', '', '2026-03-29', '2026-03-30', 1, 2, 0, '', 240.00, 0.00, 43.20, 283.20, 'USD', NULL, NULL, 'hotel', 'refunded', NULL, NULL, 'cancelled', '2026-03-28 22:59:21'),
(8, 'BK2026CCBDD', 'INV2026B3391', 1, 'Admin User', 'admin@royalevista.com', '', '2026-03-30', '2026-04-01', 2, 2, 0, '', 500.00, 0.00, 90.00, 90.00, 'BRL', NULL, NULL, 'hotel', 'pending', NULL, NULL, 'checked_out', '2026-03-30 08:51:04'),
(9, 'BK2026A74D6', 'INV202671C1D', 1, 'Admin User', 'admin@royalevista.com', '', '2026-03-30', '2026-04-01', 2, 2, 0, '', 1998.00, 0.00, 359.64, 2357.64, 'INR', NULL, NULL, 'upi', 'paid', NULL, '2026-03-30 09:17:03', 'checked_out', '2026-03-30 14:47:03'),
(10, 'BK2026F65B9', 'INV20261270B', 1, 'Admin User', 'admin@royalevista.com', '', '2026-03-30', '2026-04-01', 2, 2, 0, '', 240.00, 0.00, 43.20, 283.20, 'INR', NULL, NULL, 'hotel', 'pending', NULL, NULL, 'checked_out', '2026-03-30 20:58:14'),
(11, 'BK20268E5AC', 'INV20264A9C2', 1, 'Admin User', 'admin@royalevista.com', '', '2026-03-30', '2026-04-01', 2, 2, 0, '', 240.00, 0.00, 43.20, 283.20, 'INR', NULL, NULL, 'hotel', 'pending', NULL, NULL, 'checked_out', '2026-03-30 21:17:48'),
(13, 'BK20264132E', 'INV202677A11', 1, 'Admin User', 'admin@royalevista.com', '', '2026-03-31', '2026-04-02', 2, 2, 0, '', 240.00, 0.00, 43.20, 283.20, 'USD', NULL, NULL, 'hotel', 'pending', NULL, NULL, 'checked_out', '2026-03-31 05:28:26'),
(15, 'BK20262823E', 'INV2026C06A6', 1, 'Admin User', 'admin@royalevista.com', '', '2026-03-31', '2026-04-02', 2, 2, 0, '', 240.00, 0.00, 43.20, 283.20, 'USD', NULL, NULL, 'hotel', 'pending', NULL, NULL, 'checked_out', '2026-03-31 05:57:43'),
(19, 'BK20269BA39', 'INV20264A15A', 1, 'Admin User', 'admin@royalevista.com', '', '2026-03-31', '2026-04-02', 2, 2, 0, '', 240.00, 0.00, 43.20, 283.20, 'USD', NULL, NULL, 'hotel', 'pending', NULL, NULL, 'checked_out', '2026-03-31 06:09:17'),
(20, 'BK20266898D', 'INV2026649FE', 7, 'Ayaan Khalifa', 'ayaankhalifa4432@gmail.com', '', '2026-03-31', '2026-04-02', 2, 2, 0, '', 240.00, 0.00, 43.20, 283.20, 'USD', NULL, NULL, 'hotel', 'pending', NULL, NULL, 'checked_out', '2026-03-31 07:51:52'),
(21, 'BK2026689AA', 'INV202660028', 1, 'Admin User', 'admin@royalevista.com', '', '2026-03-31', '2026-04-02', 2, 2, 0, '', 240.00, 0.00, 43.20, 283.20, 'USD', NULL, NULL, 'hotel', 'pending', NULL, NULL, 'checked_out', '2026-03-31 07:56:28'),
(22, 'BK2026C5218', 'INV20266F8CF', 8, 'Radhey', 'dcu3071@gmail.com', '', '2026-03-31', '2026-04-02', 2, 2, 0, '', 240.00, 0.00, 43.20, 283.20, 'USD', NULL, NULL, 'hotel', 'pending', NULL, NULL, 'checked_out', '2026-03-31 08:08:24'),
(23, 'BK20265DB38', 'INV20261F180', 8, 'Radhey', 'dcu3071@gmail.com', '', '2026-04-03', '2026-04-04', 1, 2, 0, '', 240.00, 0.00, 43.20, 283.20, 'USD', NULL, NULL, 'hotel', 'pending', NULL, NULL, 'checked_out', '2026-03-31 08:09:43'),
(24, 'BK202699416', 'INV2026B889A', 1, 'Admin User', 'admin@royalevista.com', '', '2026-03-31', '2026-04-02', 2, 2, 0, '', 240.00, 0.00, 43.20, 283.20, 'USD', NULL, NULL, 'hotel', 'pending', NULL, NULL, 'checked_out', '2026-03-31 11:21:01'),
(25, 'BK2026730A8', 'INV20264EC92', 1, 'Admin User', 'admin@royalevista.com', '', '2026-03-31', '2026-04-02', 2, 2, 0, '', 240.00, 0.00, 43.20, 283.20, 'USD', NULL, NULL, 'hotel', 'pending', NULL, NULL, 'checked_out', '2026-03-31 11:21:56'),
(26, 'BK202646E33', 'INV2026B81C6', 1, 'Admin User', 'admin@royalevista.com', '', '2026-03-31', '2026-04-02', 2, 2, 0, '', 1200.00, 0.00, 216.00, 1416.00, 'USD', NULL, NULL, 'hotel', 'pending', NULL, NULL, 'checked_out', '2026-03-31 12:56:11'),
(27, 'BK20265A5B7', 'INV2026D36BF', 7, 'Ayaan Khalifa', 'ayaankhalifa4432@gmail.com', '', '2026-03-31', '2026-04-02', 2, 2, 0, '', 240.00, 0.00, 43.20, 283.20, 'USD', NULL, NULL, 'hotel', 'pending', NULL, NULL, 'checked_out', '2026-03-31 15:54:18'),
(28, 'BK2026436AF', 'INV2026DEAE4', 10, 'Ayaan Khalifa', 'ayaankhalifa2006@gmail.com', '', '2026-03-31', '2026-04-02', 2, 2, 0, '', 240.00, 0.00, 43.20, 283.20, 'USD', NULL, NULL, 'hotel', 'pending', NULL, NULL, 'checked_out', '2026-03-31 15:59:15'),
(29, 'BK20265A464', 'INV20264B4D3', 7, 'Ayaan Khalifa', 'ayaankhalifa4432@gmail.com', '', '2026-03-31', '2026-04-02', 2, 2, 0, '', 240.00, 0.00, 43.20, 283.20, 'USD', NULL, NULL, 'hotel', 'pending', NULL, NULL, 'checked_out', '2026-03-31 16:12:21'),
(30, 'BK202672D08', 'INV2026CFE30', 12, 'Ismail laher', 'isulaher@gmail.com', '', '2026-04-02', '2026-04-04', 2, 2, 0, '', 500.00, 0.00, 90.00, 590.00, 'USD', NULL, NULL, 'hotel', 'pending', NULL, NULL, 'checked_out', '2026-04-02 05:40:47'),
(31, 'BK20261DD41', 'INV20264D793', 12, 'Ismail laher', 'isulaher@gmail.com', '', '2026-04-02', '2026-04-04', 2, 2, 0, '', 500.00, 0.00, 90.00, 590.00, 'USD', NULL, NULL, 'hotel', 'pending', NULL, NULL, 'checked_out', '2026-04-02 05:43:02'),
(32, 'BK2026A312F', 'INV20268905D', 7, 'Ayaan Khalifa', 'ayaankhalifa4432@gmail.com', '', '2026-04-08', '2026-04-14', 6, 2, 0, '', 3000.00, 0.00, 540.00, 3540.00, 'USD', NULL, NULL, 'hotel', 'pending', NULL, NULL, 'checked_out', '2026-04-02 08:27:10'),
(33, 'BK202682CBD', 'INV2026A8A46', 7, 'Ayaan Khalifa', 'ayaankhalifa4432@gmail.com', '', '2026-04-07', '2026-04-09', 2, 2, 0, '', 240.00, 0.00, 43.20, 283.20, 'USD', NULL, NULL, 'hotel', 'pending', NULL, NULL, 'checked_out', '2026-04-07 09:12:01'),
(35, 'BK2026BC2E0', 'INV2026E07A3', 7, 'Ayaan Khalifa', 'ayaankhalifa4432@gmail.com', '', '2026-04-07', '2026-04-09', 2, 2, 0, '', 480.00, 0.00, 86.40, 566.40, 'USD', NULL, NULL, 'hotel', 'pending', NULL, NULL, 'checked_out', '2026-04-07 09:12:58'),
(36, 'BK20263316F', 'INV2026275DE', 7, 'Ayaan Khalifa', 'ayaankhalifa4432@gmail.com', '', '2026-04-10', '2026-04-18', 8, 2, 0, '', 960.00, 0.00, 172.80, 1132.80, 'USD', NULL, NULL, 'hotel', 'pending', NULL, NULL, 'cancelled', '2026-04-07 09:13:46'),
(37, 'BK202613CDE', 'INV2026F4E76', 7, 'Ayaan Khalifa', 'ayaankhalifa4432@gmail.com', '+918799097673', '2026-04-18', '2026-04-20', 2, 2, 0, '', 240.00, 74.00, 29.88, 195.88, 'INR', 'WELCOME10', NULL, 'hotel', 'pending', NULL, NULL, 'confirmed', '2026-04-18 06:21:24');

-- --------------------------------------------------------

--
-- Table structure for table `booking_room_assignments`
--

DROP TABLE IF EXISTS `booking_room_assignments`;
CREATE TABLE IF NOT EXISTS `booking_room_assignments` (
  `id` int NOT NULL AUTO_INCREMENT,
  `booking_ref` varchar(20) NOT NULL,
  `room_id` int NOT NULL,
  `room_number` varchar(20) NOT NULL,
  `room_type_id` int NOT NULL,
  `room_type_name` varchar(100) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `booking_ref` (`booking_ref`),
  KEY `room_id` (`room_id`)
) ENGINE=InnoDB AUTO_INCREMENT=28 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `booking_room_assignments`
--

INSERT INTO `booking_room_assignments` (`id`, `booking_ref`, `room_id`, `room_number`, `room_type_id`, `room_type_name`, `created_at`) VALUES
(1, 'BK20260CCC8', 64, 'E001', 2, 'Exclusive Room', '2026-03-28 08:42:09'),
(2, 'BK20260CCC8', 65, 'E002', 2, 'Exclusive Room', '2026-03-28 08:42:09'),
(3, 'BK20260CCC8', 127, 'F001', 3, 'Family Room', '2026-03-28 08:42:09'),
(4, 'BK2026511D4', 50, 'D050', 1, 'Deluxe Room', '2026-03-28 22:59:21'),
(5, 'BK20268E5AC', 1, 'D001', 1, 'Deluxe Room', '2026-03-30 21:17:48'),
(10, 'BK20269BA39', 2, 'D002', 1, 'Deluxe Room', '2026-03-31 06:09:17'),
(11, 'BK20265DB38', 3, 'D003', 1, 'Deluxe Room', '2026-03-31 08:09:43'),
(12, 'BK20265DB38', 4, 'D004', 1, 'Deluxe Room', '2026-03-31 08:09:43'),
(13, 'BK202699416', 5, 'D005', 1, 'Deluxe Room', '2026-03-31 11:21:01'),
(14, 'BK2026730A8', 6, 'D006', 1, 'Deluxe Room', '2026-03-31 11:21:56'),
(15, 'BK202646E33', 190, 'P001', 4, 'Presidential Suite', '2026-03-31 12:56:11'),
(16, 'BK20265A5B7', 7, 'D007', 1, 'Deluxe Room', '2026-03-31 15:54:18'),
(17, 'BK2026436AF', 8, 'D008', 1, 'Deluxe Room', '2026-03-31 15:59:15'),
(18, 'BK20265A464', 9, 'D009', 1, 'Deluxe Room', '2026-03-31 16:12:21'),
(19, 'BK202672D08', 127, 'F001', 3, 'Family Room', '2026-04-02 05:40:47'),
(20, 'BK20261DD41', 128, 'F002', 3, 'Family Room', '2026-04-02 05:43:02'),
(21, 'BK2026A312F', 129, 'F003', 3, 'Family Room', '2026-04-02 08:27:10'),
(22, 'BK2026A312F', 130, 'F004', 3, 'Family Room', '2026-04-02 08:27:10'),
(23, 'BK202682CBD', 1, 'D001', 1, 'Deluxe Room', '2026-04-07 09:12:01'),
(24, 'BK2026BC2E0', 2, 'D002', 1, 'Deluxe Room', '2026-04-07 09:12:58'),
(25, 'BK2026BC2E0', 3, 'D003', 1, 'Deluxe Room', '2026-04-07 09:12:58'),
(26, 'BK20263316F', 4, 'D004', 1, 'Deluxe Room', '2026-04-07 09:13:47'),
(27, 'BK202613CDE', 1, 'D001', 1, 'Deluxe Room', '2026-04-18 06:21:24');

-- --------------------------------------------------------

--
-- Table structure for table `chat_messages`
--

DROP TABLE IF EXISTS `chat_messages`;
CREATE TABLE IF NOT EXISTS `chat_messages` (
  `id` int NOT NULL AUTO_INCREMENT,
  `session_id` varchar(40) NOT NULL,
  `role` enum('user','assistant') DEFAULT 'user',
  `content` text NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `session_id` (`session_id`)
) ENGINE=InnoDB AUTO_INCREMENT=159 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `chat_messages`
--

INSERT INTO `chat_messages` (`id`, `session_id`, `role`, `content`, `created_at`) VALUES
(1, 'shama_1774685029417', 'user', '🌍 Our Properties', '2026-03-28 08:11:30'),
(2, 'shama_1774685029417', 'assistant', 'Thank you for reaching out to Royale Vista! I\'d be delighted to help with room bookings, hotel facilities, special offers, or anything else about your stay. What can I assist you with?', '2026-03-28 08:11:30'),
(3, 'shama_1774685029417', 'user', '🎁 Offers &amp; Deals', '2026-03-28 08:11:33'),
(4, 'shama_1774685029417', 'assistant', 'We have some wonderful offers right now! Active codes: FLAT50, FAMILY30, HOLIDAY25, SUMMER20. Apply them at checkout for instant savings. Our members also enjoy up to 25% off every booking!', '2026-03-28 08:11:33'),
(5, 'shama_1774685029417', 'user', '👑 Get Membership', '2026-03-28 08:11:39'),
(6, 'shama_1774685029417', 'assistant', 'Our membership plans start from $100: Silver (5% off), Gold (15% off + priority service), and Platinum (25% off + VIP treatment + complimentary breakfast + airport transfers). Members earn bonus loyalty points too!', '2026-03-28 08:11:51'),
(7, 'shama_1774744604719', 'user', '🌍 Our Properties', '2026-03-29 00:36:54'),
(8, 'shama_1774744604719', 'assistant', 'Thank you for reaching out to Royale Vista! I\'d be delighted to help with room bookings, hotel facilities, special offers, or anything else about your stay. What can I assist you with?', '2026-03-29 00:36:54'),
(9, 'shama_1774744604719', 'user', '🛏 View Rooms', '2026-03-29 00:36:56'),
(10, 'shama_1774744604719', 'assistant', 'We offer 4 stunning room categories: Deluxe ($120/night), Exclusive ($300/night), Family ($250/night), and our magnificent Presidential Suite ($600/night). All rooms feature premium amenities and world-class service. Which would you like to know more about?', '2026-03-29 00:36:56'),
(11, 'shama_1774746750238', 'user', 'Hello', '2026-03-29 01:13:26'),
(12, 'shama_1774746750238', 'assistant', 'Welcome to Royale Vista! 🏨✨ I\'m Shama, your personal concierge. Whether you\'re planning a stay, want to know about our facilities, or need assistance with a booking — I\'m here for you!', '2026-03-29 01:13:27'),
(13, 'habibi_1774787325816', 'user', 'hello', '2026-03-29 12:29:08'),
(14, 'habibi_1774787325816', 'assistant', 'Welcome to Royale Vista! 🏨✨ I\'m Habibi, your personal concierge. Whether you\'re planning a stay, want to know about our facilities, or need assistance with a booking — I\'m here for you!', '2026-03-29 12:29:08'),
(15, 'habibi_1774787325816', 'user', '💰 Check Prices', '2026-03-29 12:29:19'),
(16, 'habibi_1774787325816', 'assistant', 'We offer 4 stunning room categories: Deluxe ($120/night), Exclusive ($300/night), Family ($250/night), and our magnificent Presidential Suite ($600/night). All rooms feature premium amenities and world-class service. Which would you like to know more about?', '2026-03-29 12:29:20'),
(17, 'habibi_1774787325816', 'user', '👑 Presidential Suite', '2026-03-29 12:29:24'),
(18, 'habibi_1774787325816', 'assistant', 'We offer 4 stunning room categories: Deluxe ($120/night), Exclusive ($300/night), Family ($250/night), and our magnificent Presidential Suite ($600/night). All rooms feature premium amenities and world-class service. Which would you like to know more about?', '2026-03-29 12:29:24'),
(19, 'habibi_1774787325816', 'user', 'offrer', '2026-03-29 12:29:32'),
(20, 'habibi_1774787325816', 'assistant', 'Thank you for reaching out to Royale Vista! I\'d be delighted to help with room bookings, hotel facilities, special offers, or anything else about your stay. What can I assist you with?', '2026-03-29 12:29:32'),
(21, 'habibi_1774787325816', 'user', '📞 Contact Us', '2026-03-29 12:29:38'),
(22, 'habibi_1774787325816', 'assistant', 'Our team is available 24/7! Reach us at: 📧 stay@royalevista.com | 📞 +971 800 ROYALE | Or visit our Contact page. We typically respond to emails within 2 hours.', '2026-03-29 12:29:38'),
(23, 'habibi_1774787325816', 'user', '📍 Location', '2026-03-29 12:29:41'),
(24, 'habibi_1774787325816', 'assistant', 'Thank you for reaching out to Royale Vista! I\'d be delighted to help with room bookings, hotel facilities, special offers, or anything else about your stay. What can I assist you with?', '2026-03-29 12:29:41'),
(25, 'habibi_1774787325816', 'user', '🎁 Offers &amp; Deals', '2026-03-29 12:29:45'),
(26, 'habibi_1774787325816', 'assistant', 'We have some wonderful offers right now! Active codes: FLAT50, FAMILY30, HOLIDAY25, SUMMER20. Apply them at checkout for instant savings. Our members also enjoy up to 25% off every booking!', '2026-03-29 12:29:45'),
(27, 'habibi_1774865379604', 'user', '📅 Book Now', '2026-03-30 10:09:56'),
(28, 'habibi_1774865379604', 'assistant', 'Here is our current live availability by room type:• Deluxe Room: 50 available• Exclusive Room: 50 available• Family Room: 51 available• Presidential Suite: 50 available• Test Suites: 0 availableSelect dates and secure your room here!', '2026-03-30 10:09:56'),
(29, 'habibi_1774866841384', 'user', 'hi', '2026-03-30 10:34:05'),
(30, 'habibi_1774866841384', 'assistant', 'Welcome to Royale Vista! 🏨✨ I\'m Habibi, your personal concierge. Whether you\'re planning a stay, want to know about our facilities, or need assistance with a booking — I\'m here for you!', '2026-03-30 10:34:05'),
(31, 'habibi_1774866841384', 'user', '🛏 View Rooms', '2026-03-30 10:34:14'),
(32, 'habibi_1774866841384', 'assistant', 'We offer 4 stunning room categories: Deluxe ($120/night), Exclusive ($300/night), Family ($250/night), and our magnificent Presidential Suite ($600/night). All rooms feature premium amenities and world-class service. Which would you like to know more about?', '2026-03-30 10:34:15'),
(33, 'habibi_1774866841384', 'user', 'price', '2026-03-30 10:34:27'),
(34, 'habibi_1774866841384', 'assistant', 'We offer 4 stunning room categories: Deluxe ($120/night), Exclusive ($300/night), Family ($250/night), and our magnificent Presidential Suite ($600/night). All rooms feature premium amenities and world-class service. Which would you like to know more about?', '2026-03-30 10:34:28'),
(35, 'habibi_1774870442714', 'user', 'hello', '2026-03-30 11:34:17'),
(36, 'habibi_1774870442714', 'assistant', 'Welcome to Royale Vista! 🏨✨ I\'m Habibi, your personal concierge. Whether you\'re planning a stay, want to know about our facilities, or need assistance with a booking — I\'m here for you!', '2026-03-30 11:34:17'),
(37, 'habibi_1774870442714', 'user', '🛏 View Rooms', '2026-03-30 11:34:21'),
(38, 'habibi_1774870442714', 'assistant', 'We offer 4 stunning room categories: Deluxe ($120/night), Exclusive ($300/night), Family ($250/night), and our magnificent Presidential Suite ($600/night). All rooms feature premium amenities and world-class service. Which would you like to know more about?', '2026-03-30 11:34:21'),
(39, 'habibi_1774870969909', 'user', '🌍 Our Properties', '2026-03-30 11:42:53'),
(40, 'habibi_1774870969909', 'assistant', 'Thank you for reaching out to Royale Vista! I\'d be delighted to help with room bookings, hotel facilities, special offers, or anything else about your stay. What can I assist you with?', '2026-03-30 11:42:53'),
(41, 'habibi_1774870969909', 'user', '🛏 View Rooms', '2026-03-30 11:42:56'),
(42, 'habibi_1774870969909', 'assistant', 'We offer 4 stunning room categories: Deluxe ($120/night), Exclusive ($300/night), Family ($250/night), and our magnificent Presidential Suite ($600/night). All rooms feature premium amenities and world-class service. Which would you like to know more about?', '2026-03-30 11:42:56'),
(43, 'habibi_1774870969909', 'user', '👨‍👩‍👧 Family Room', '2026-03-30 11:42:59'),
(44, 'habibi_1774870969909', 'assistant', 'We offer 4 stunning room categories: Deluxe ($120/night), Exclusive ($300/night), Family ($250/night), and our magnificent Presidential Suite ($600/night). All rooms feature premium amenities and world-class service. Which would you like to know more about?', '2026-03-30 11:42:59'),
(45, 'habibi_1774877326154', 'user', '🛏 Explore Rooms', '2026-03-30 13:29:18'),
(46, 'habibi_1774877326154', 'assistant', 'We offer 4 stunning room categories: Deluxe ($120/night), Exclusive ($300/night), Family ($250/night), and our magnificent Presidential Suite ($600/night). All rooms feature premium amenities and world-class service. Which would you like to know more about?', '2026-03-30 13:29:18'),
(47, 'habibi_1774905253867', 'user', '🛏 Explore Rooms', '2026-03-30 21:14:21'),
(48, 'habibi_1774905253867', 'assistant', 'We offer 4 stunning room categories: Deluxe ($120/night), Exclusive ($300/night), Family ($250/night), and our magnificent Presidential Suite ($600/night). All rooms feature premium amenities and world-class service. Which would you like to know more about?', '2026-03-30 21:14:21'),
(49, 'habibi_1774905253867', 'user', '👑 Presidential Suite', '2026-03-30 21:14:25'),
(50, 'habibi_1774905253867', 'assistant', 'We offer 4 stunning room categories: Deluxe ($120/night), Exclusive ($300/night), Family ($250/night), and our magnificent Presidential Suite ($600/night). All rooms feature premium amenities and world-class service. Which would you like to know more about?', '2026-03-30 21:14:25'),
(51, 'habibi_1774937552136', 'user', '🌍 Our Properties', '2026-03-31 06:12:34'),
(52, 'habibi_1774937552136', 'assistant', 'Thank you for reaching out to Royale Vista! I\'d be delighted to help with room bookings, hotel facilities, special offers, or anything else about your stay. What can I assist you with?', '2026-03-31 06:12:34'),
(53, 'habibi_1774937552136', 'user', '💰 Check Prices', '2026-03-31 06:12:54'),
(54, 'habibi_1774937552136', 'assistant', 'We offer 4 stunning room categories: Deluxe ($120/night), Exclusive ($300/night), Family ($250/night), and our magnificent Presidential Suite ($600/night). All rooms feature premium amenities and world-class service. Which would you like to know more about?', '2026-03-31 06:12:54'),
(55, 'habibi_1774937552136', 'user', '🛏 Deluxe Room', '2026-03-31 06:12:57'),
(56, 'habibi_1774937552136', 'assistant', 'We offer 4 stunning room categories: Deluxe ($120/night), Exclusive ($300/night), Family ($250/night), and our magnificent Presidential Suite ($600/night). All rooms feature premium amenities and world-class service. Which would you like to know more about?', '2026-03-31 06:12:57'),
(57, 'habibi_1774937552136', 'user', '👨‍👩‍👧 Family Room', '2026-03-31 06:12:59'),
(58, 'habibi_1774937552136', 'assistant', 'We offer 4 stunning room categories: Deluxe ($120/night), Exclusive ($300/night), Family ($250/night), and our magnificent Presidential Suite ($600/night). All rooms feature premium amenities and world-class service. Which would you like to know more about?', '2026-03-31 06:12:59'),
(59, 'habibi_1774961605768', 'user', '🛏 Explore Rooms', '2026-03-31 12:53:30'),
(60, 'habibi_1774961605768', 'assistant', 'We offer 4 stunning room categories: Deluxe ($120/night), Exclusive ($300/night), Family ($250/night), and our magnificent Presidential Suite ($600/night). All rooms feature premium amenities and world-class service. Which would you like to know more about?', '2026-03-31 12:53:30'),
(61, 'habibi_1774962563907', 'user', '🌍 Our Properties', '2026-03-31 13:09:26'),
(62, 'habibi_1774962563907', 'assistant', 'Thank you for reaching out to Royale Vista! I\'d be delighted to help with room bookings, hotel facilities, special offers, or anything else about your stay. What can I assist you with?', '2026-03-31 13:09:26'),
(63, 'habibi_1774962945291', 'user', '🌍 Our Properties', '2026-03-31 13:15:48'),
(64, 'habibi_1774962945291', 'assistant', 'Thank you for reaching out to Royale Vista! I\'d be delighted to help with room bookings, hotel facilities, special offers, or anything else about your stay. What can I assist you with?', '2026-03-31 13:15:48'),
(65, 'habibi_1774962945291', 'user', '🛏 View Rooms', '2026-03-31 13:15:51'),
(66, 'habibi_1774962945291', 'assistant', 'We offer 4 stunning room categories: Deluxe ($120/night), Exclusive ($300/night), Family ($250/night), and our magnificent Presidential Suite ($600/night). All rooms feature premium amenities and world-class service. Which would you like to know more about?', '2026-03-31 13:15:51'),
(67, 'habibi_1774962945291', 'user', '🛏 Deluxe Room', '2026-03-31 13:15:55'),
(68, 'habibi_1774962945291', 'assistant', 'We offer 4 stunning room categories: Deluxe ($120/night), Exclusive ($300/night), Family ($250/night), and our magnificent Presidential Suite ($600/night). All rooms feature premium amenities and world-class service. Which would you like to know more about?', '2026-03-31 13:15:55'),
(69, 'habibi_1774962945291', 'user', '👑 Presidential Suite', '2026-03-31 13:15:58'),
(70, 'habibi_1774962945291', 'assistant', 'We offer 4 stunning room categories: Deluxe ($120/night), Exclusive ($300/night), Family ($250/night), and our magnificent Presidential Suite ($600/night). All rooms feature premium amenities and world-class service. Which would you like to know more about?', '2026-03-31 13:15:58'),
(71, 'habibi_1774962945291', 'user', '👨‍👩‍👧 Family Room', '2026-03-31 13:16:00'),
(72, 'habibi_1774962945291', 'assistant', 'We offer 4 stunning room categories: Deluxe ($120/night), Exclusive ($300/night), Family ($250/night), and our magnificent Presidential Suite ($600/night). All rooms feature premium amenities and world-class service. Which would you like to know more about?', '2026-03-31 13:16:00'),
(73, 'habibi_1774971230596', 'user', '🌍 Our Properties', '2026-03-31 15:33:56'),
(74, 'habibi_1774971230596', 'assistant', 'Thank you for reaching out to Royale Vista! I\'d be delighted to help with room bookings, hotel facilities, special offers, or anything else about your stay. What can I assist you with?', '2026-03-31 15:33:57'),
(75, 'habibi_1775118486767', 'user', '🛏 Explore Rooms', '2026-04-02 08:28:10'),
(76, 'habibi_1775118486767', 'assistant', 'We offer 4 stunning room categories: Deluxe ($120/night), Exclusive ($300/night), Family ($250/night), and our magnificent Presidential Suite ($600/night). All rooms feature premium amenities and world-class service. Which would you like to know more about?', '2026-04-02 08:28:10'),
(77, 'habibi_1775296831317', 'user', '🌍 Our Properties', '2026-04-04 10:00:34'),
(78, 'habibi_1775296831317', 'assistant', 'Thank you for reaching out to Royale Vista! I\'d be delighted to help with room bookings, hotel facilities, special offers, or anything else about your stay. What can I assist you with?', '2026-04-04 10:00:34'),
(79, 'habibi_1775296831317', 'user', '🛏 View Rooms', '2026-04-04 10:00:36'),
(80, 'habibi_1775296831317', 'assistant', 'We offer 4 stunning room categories: Deluxe ($120/night), Exclusive ($300/night), Family ($250/night), and our magnificent Presidential Suite ($600/night). All rooms feature premium amenities and world-class service. Which would you like to know more about?', '2026-04-04 10:00:36'),
(81, 'habibi_1775296831317', 'user', '👨‍👩‍👧 Family Room', '2026-04-04 10:00:39'),
(82, 'habibi_1775296831317', 'assistant', 'We offer 4 stunning room categories: Deluxe ($120/night), Exclusive ($300/night), Family ($250/night), and our magnificent Presidential Suite ($600/night). All rooms feature premium amenities and world-class service. Which would you like to know more about?', '2026-04-04 10:00:39'),
(83, 'habibi_1775296831317', 'user', '🛏 Deluxe Room', '2026-04-04 10:00:42'),
(84, 'habibi_1775296831317', 'assistant', 'We offer 4 stunning room categories: Deluxe ($120/night), Exclusive ($300/night), Family ($250/night), and our magnificent Presidential Suite ($600/night). All rooms feature premium amenities and world-class service. Which would you like to know more about?', '2026-04-04 10:00:43'),
(85, 'habibi_1775296831317', 'user', '👨‍👩‍👧 Family Room', '2026-04-04 10:00:45'),
(86, 'habibi_1775296831317', 'assistant', 'We offer 4 stunning room categories: Deluxe ($120/night), Exclusive ($300/night), Family ($250/night), and our magnificent Presidential Suite ($600/night). All rooms feature premium amenities and world-class service. Which would you like to know more about?', '2026-04-04 10:00:45'),
(87, 'habibi_1775296831317', 'user', 'hii', '2026-04-04 10:00:50'),
(88, 'habibi_1775296831317', 'assistant', 'Welcome to Royale Vista! 🏨✨ I\'m Habibi, your personal concierge. Whether you\'re planning a stay, want to know about our facilities, or need assistance with a booking — I\'m here for you!', '2026-04-04 10:00:51'),
(89, 'habibi_1775296831317', 'user', '📞 Contact', '2026-04-04 10:01:13'),
(90, 'habibi_1775296831317', 'assistant', 'Our team is available 24/7! Reach us at: 📧 stay@royalevista.com | 📞 +971 800 ROYALE | Or visit our Contact page. We typically respond to emails within 2 hours.', '2026-04-04 10:01:13'),
(91, 'habibi_1775297183758', 'user', '🌍 Our Properties', '2026-04-04 10:06:26'),
(92, 'habibi_1775297183758', 'assistant', 'Thank you for reaching out to Royale Vista! I\'d be delighted to help with room bookings, hotel facilities, special offers, or anything else about your stay. What can I assist you with?', '2026-04-04 10:06:26'),
(93, 'habibi_1775297183758', 'user', '🎁 Offers &amp; Deals', '2026-04-04 10:06:30'),
(94, 'habibi_1775297183758', 'assistant', 'We have some wonderful offers right now! Active codes: FLAT50, FAMILY30, HOLIDAY25, SUMMER20. Apply them at checkout for instant savings. Our members also enjoy up to 25% off every booking!', '2026-04-04 10:06:30'),
(95, 'habibi_1775297317414', 'user', '🛏 Explore Rooms', '2026-04-04 10:08:39'),
(96, 'habibi_1775297317414', 'assistant', 'We offer 4 stunning room categories: Deluxe ($120/night), Exclusive ($300/night), Family ($250/night), and our magnificent Presidential Suite ($600/night). All rooms feature premium amenities and world-class service. Which would you like to know more about?', '2026-04-04 10:08:39'),
(97, 'habibi_1775297317414', 'user', '👨‍👩‍👧 Family Room', '2026-04-04 10:09:03'),
(98, 'habibi_1775297317414', 'assistant', 'We offer 4 stunning room categories: Deluxe ($120/night), Exclusive ($300/night), Family ($250/night), and our magnificent Presidential Suite ($600/night). All rooms feature premium amenities and world-class service. Which would you like to know more about?', '2026-04-04 10:09:03'),
(99, 'habibi_1775297370898', 'user', '🍽 Reserve Dining', '2026-04-04 10:09:33'),
(100, 'habibi_1775297370898', 'assistant', 'Here is our current live availability by room type:• Deluxe Room: 50 available• Exclusive Room: 50 available• Family Room: 49 available• Presidential Suite: 50 available• Test Suites: 0 availableSelect dates and secure your room here!', '2026-04-04 10:09:33'),
(101, 'habibi_1775297380887', 'user', '💎 Membership', '2026-04-04 10:09:43'),
(102, 'habibi_1775297380887', 'assistant', 'Our membership plans start from $100: Silver (5% off), Gold (15% off + priority service), and Platinum (25% off + VIP treatment + complimentary breakfast + airport transfers). Members earn bonus loyalty points too!', '2026-04-04 10:09:43'),
(103, 'habibi_1775297380887', 'user', '🥇 Gold Plan', '2026-04-04 10:09:46'),
(104, 'habibi_1775297380887', 'assistant', 'Thank you for reaching out to Royale Vista! I\'d be delighted to help with room bookings, hotel facilities, special offers, or anything else about your stay. What can I assist you with?', '2026-04-04 10:09:46'),
(105, 'habibi_1775297380887', 'user', '🛏 View Rooms', '2026-04-04 10:09:50'),
(106, 'habibi_1775297380887', 'assistant', 'We offer 4 stunning room categories: Deluxe ($120/night), Exclusive ($300/night), Family ($250/night), and our magnificent Presidential Suite ($600/night). All rooms feature premium amenities and world-class service. Which would you like to know more about?', '2026-04-04 10:09:50'),
(107, 'habibi_1775297380887', 'user', '🛏 Deluxe Room', '2026-04-04 10:09:53'),
(108, 'habibi_1775297380887', 'assistant', 'We offer 4 stunning room categories: Deluxe ($120/night), Exclusive ($300/night), Family ($250/night), and our magnificent Presidential Suite ($600/night). All rooms feature premium amenities and world-class service. Which would you like to know more about?', '2026-04-04 10:09:53'),
(109, 'habibi_1775297406172', 'user', '🍽 Reserve Dining', '2026-04-04 10:10:08'),
(110, 'habibi_1775297406172', 'assistant', 'Here is our current live availability by room type:• Deluxe Room: 50 available• Exclusive Room: 50 available• Family Room: 49 available• Presidential Suite: 50 available• Test Suites: 0 availableSelect dates and secure your room here!', '2026-04-04 10:10:08'),
(111, 'habibi_1775297406172', 'user', '📞 Contact Support', '2026-04-04 10:10:15'),
(112, 'habibi_1775297406172', 'assistant', 'Our team is available 24/7! Reach us at: 📧 stay@royalevista.com | 📞 +971 800 ROYALE | Or visit our Contact page. We typically respond to emails within 2 hours.', '2026-04-04 10:10:15'),
(113, 'habibi_1775552925939', 'user', '💎 Membership', '2026-04-07 09:09:20'),
(114, 'habibi_1775552925939', 'assistant', 'Our membership plans start from $100: Silver (5% off), Gold (15% off + priority service), and Platinum (25% off + VIP treatment + complimentary breakfast + airport transfers). Members earn bonus loyalty points too!', '2026-04-07 09:09:20'),
(115, 'habibi_1775552925939', 'user', '👑 Platinum Plan', '2026-04-07 09:09:30'),
(116, 'habibi_1775552925939', 'assistant', 'Thank you for reaching out to Royale Vista! I\'d be delighted to help with room bookings, hotel facilities, special offers, or anything else about your stay. What can I assist you with?', '2026-04-07 09:09:30'),
(117, 'cs_1775994894753_0s569o', 'user', '💰 Check Prices', '2026-04-12 11:54:57'),
(118, 'cs_1775994894753_0s569o', 'assistant', 'We offer 4 stunning room categories: Deluxe ($120/night), Exclusive ($300/night), Family ($250/night), and our magnificent Presidential Suite ($600/night). All rooms feature premium amenities and world-class service. Which would you like to know more about?', '2026-04-12 11:54:57'),
(119, 'cs_1775994894753_0s569o', 'user', '👑 Presidential Suite', '2026-04-12 11:54:59'),
(120, 'cs_1775994894753_0s569o', 'assistant', 'We offer 4 stunning room categories: Deluxe ($120/night), Exclusive ($300/night), Family ($250/night), and our magnificent Presidential Suite ($600/night). All rooms feature premium amenities and world-class service. Which would you like to know more about?', '2026-04-12 11:54:59'),
(121, 'cs_1775994894753_0s569o', 'user', '👨‍👩‍👧 Family Room', '2026-04-12 11:55:01'),
(122, 'cs_1775994894753_0s569o', 'assistant', 'We offer 4 stunning room categories: Deluxe ($120/night), Exclusive ($300/night), Family ($250/night), and our magnificent Presidential Suite ($600/night). All rooms feature premium amenities and world-class service. Which would you like to know more about?', '2026-04-12 11:55:01'),
(123, 'cs_1775994894753_0s569o', 'user', '🛏 Deluxe Room', '2026-04-12 11:55:02'),
(124, 'cs_1775994894753_0s569o', 'assistant', 'We offer 4 stunning room categories: Deluxe ($120/night), Exclusive ($300/night), Family ($250/night), and our magnificent Presidential Suite ($600/night). All rooms feature premium amenities and world-class service. Which would you like to know more about?', '2026-04-12 11:55:02'),
(125, 'cs_1775994943826_6mgrjm', 'user', '📞 Contact Us', '2026-04-12 11:55:53'),
(126, 'cs_1775994943826_6mgrjm', 'assistant', 'Our team is available 24/7! Reach us at: 📧 stay@royalevista.com | 📞 +971 800 ROYALE | Or visit our Contact page. We typically respond to emails within 2 hours.', '2026-04-12 11:55:53'),
(127, 'cs_1775994943826_6mgrjm', 'user', '📍 Location', '2026-04-12 11:55:56'),
(128, 'cs_1775994943826_6mgrjm', 'assistant', 'Thank you for reaching out to Royale Vista! I\'d be delighted to help with room bookings, hotel facilities, special offers, or anything else about your stay. What can I assist you with?', '2026-04-12 11:55:56'),
(129, 'cs_1775994943826_6mgrjm', 'user', '🎁 Offers &amp; Deals', '2026-04-12 11:56:04'),
(130, 'cs_1775994943826_6mgrjm', 'assistant', 'We have some wonderful offers right now! Active codes: FLAT50, FAMILY30, HOLIDAY25, SUMMER20. Apply them at checkout for instant savings. Our members also enjoy up to 25% off every booking!', '2026-04-12 11:56:04'),
(131, 'cs_1775994943826_6mgrjm', 'user', '👑 Get Membership', '2026-04-12 11:56:09'),
(132, 'cs_1775994943826_6mgrjm', 'assistant', 'Our membership plans start from $100: Silver (5% off), Gold (15% off + priority service), and Platinum (25% off + VIP treatment + complimentary breakfast + airport transfers). Members earn bonus loyalty points too!', '2026-04-12 11:56:09'),
(133, 'cs_1775994943826_6mgrjm', 'user', '🥇 Gold Plan', '2026-04-12 11:56:11'),
(134, 'cs_1775994943826_6mgrjm', 'assistant', 'Thank you for reaching out to Royale Vista! I\'d be delighted to help with room bookings, hotel facilities, special offers, or anything else about your stay. What can I assist you with?', '2026-04-12 11:56:11'),
(135, 'cs_1775994943826_6mgrjm', 'user', '📞 Contact Us', '2026-04-12 11:56:12'),
(136, 'cs_1775994943826_6mgrjm', 'assistant', 'Our team is available 24/7! Reach us at: 📧 stay@royalevista.com | 📞 +971 800 ROYALE | Or visit our Contact page. We typically respond to emails within 2 hours.', '2026-04-12 11:56:12'),
(137, 'cs_1775994943826_6mgrjm', 'user', '📧 Contact Us', '2026-04-12 11:56:13'),
(138, 'cs_1775994943826_6mgrjm', 'assistant', 'Our team is available 24/7! Reach us at: 📧 stay@royalevista.com | 📞 +971 800 ROYALE | Or visit our Contact page. We typically respond to emails within 2 hours.', '2026-04-12 11:56:13'),
(139, 'cs_1775995091609_eyk05r', 'user', '🛏 View Rooms', '2026-04-12 11:58:15'),
(140, 'cs_1775995091609_eyk05r', 'assistant', 'We offer 4 stunning room categories: Deluxe ($120/night), Exclusive ($300/night), Family ($250/night), and our magnificent Presidential Suite ($600/night). All rooms feature premium amenities and world-class service. Which would you like to know more about?', '2026-04-12 11:58:15'),
(141, 'cs_1775995091609_eyk05r', 'user', '🛏 Deluxe Room', '2026-04-12 11:58:19'),
(142, 'cs_1775995091609_eyk05r', 'assistant', 'We offer 4 stunning room categories: Deluxe ($120/night), Exclusive ($300/night), Family ($250/night), and our magnificent Presidential Suite ($600/night). All rooms feature premium amenities and world-class service. Which would you like to know more about?', '2026-04-12 11:58:19'),
(143, 'cs_1775995091609_eyk05r', 'user', '🛏 Deluxe Room', '2026-04-12 11:58:21'),
(144, 'cs_1775995091609_eyk05r', 'assistant', 'We offer 4 stunning room categories: Deluxe ($120/night), Exclusive ($300/night), Family ($250/night), and our magnificent Presidential Suite ($600/night). All rooms feature premium amenities and world-class service. Which would you like to know more about?', '2026-04-12 11:58:21'),
(145, 'cs_1775995091609_eyk05r', 'user', '👑 Presidential Suite', '2026-04-12 11:58:22'),
(146, 'cs_1775995091609_eyk05r', 'assistant', 'We offer 4 stunning room categories: Deluxe ($120/night), Exclusive ($300/night), Family ($250/night), and our magnificent Presidential Suite ($600/night). All rooms feature premium amenities and world-class service. Which would you like to know more about?', '2026-04-12 11:58:22'),
(147, 'habibi_1775995444634', 'user', '🌍 Our Properties', '2026-04-12 12:04:10'),
(148, 'habibi_1775995444634', 'assistant', 'Thank you for reaching out to Royale Vista! I\'d be delighted to help with room bookings, hotel facilities, special offers, or anything else about your stay. What can I assist you with?', '2026-04-12 12:04:10'),
(149, 'shama_1775996012421', 'user', '🌍 Our Properties', '2026-04-12 12:13:44'),
(150, 'shama_1775996012421', 'assistant', 'Thank you for reaching out to Royale Vista! I\'d be delighted to help with room bookings, hotel facilities, special offers, or anything else about your stay. What can I assist you with?', '2026-04-12 12:13:44'),
(151, 'shama_1775996012421', 'user', '💰 Check Prices', '2026-04-12 12:13:46'),
(152, 'shama_1775996012421', 'assistant', 'We offer 4 stunning room categories: Deluxe ($120/night), Exclusive ($300/night), Family ($250/night), and our magnificent Presidential Suite ($600/night). All rooms feature premium amenities and world-class service. Which would you like to know more about?', '2026-04-12 12:13:46'),
(153, 'shama_1775996012421', 'user', '👑 Presidential Suite', '2026-04-12 12:13:47'),
(154, 'shama_1775996012421', 'assistant', 'We offer 4 stunning room categories: Deluxe ($120/night), Exclusive ($300/night), Family ($250/night), and our magnificent Presidential Suite ($600/night). All rooms feature premium amenities and world-class service. Which would you like to know more about?', '2026-04-12 12:13:48'),
(155, 'habibi_1775996476906', 'user', '🌍 Our Properties', '2026-04-12 12:21:19'),
(156, 'habibi_1775996476906', 'assistant', 'Thank you for reaching out to Royale Vista! I\'d be delighted to help with room bookings, hotel facilities, special offers, or anything else about your stay. What can I assist you with?', '2026-04-12 12:21:19'),
(157, 'habibi_1775996544045', 'user', '💎 Membership', '2026-04-12 12:22:26'),
(158, 'habibi_1775996544045', 'assistant', 'Our membership plans start from $100: Silver (5% off), Gold (15% off + priority service), and Platinum (25% off + VIP treatment + complimentary breakfast + airport transfers). Members earn bonus loyalty points too!', '2026-04-12 12:22:26');

-- --------------------------------------------------------

--
-- Table structure for table `chat_sessions`
--

DROP TABLE IF EXISTS `chat_sessions`;
CREATE TABLE IF NOT EXISTS `chat_sessions` (
  `id` varchar(40) NOT NULL,
  `user_id` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `chat_sessions`
--

INSERT INTO `chat_sessions` (`id`, `user_id`, `created_at`) VALUES
('cs_1775994894753_0s569o', NULL, '2026-04-12 11:54:57'),
('cs_1775994943826_6mgrjm', NULL, '2026-04-12 11:55:53'),
('cs_1775995091609_eyk05r', NULL, '2026-04-12 11:58:15'),
('habibi_1774787325816', 1, '2026-03-29 12:29:08'),
('habibi_1774865379604', 1, '2026-03-30 10:09:56'),
('habibi_1774866841384', 1, '2026-03-30 10:34:05'),
('habibi_1774870442714', 1, '2026-03-30 11:34:17'),
('habibi_1774870969909', 1, '2026-03-30 11:42:53'),
('habibi_1774877326154', 1, '2026-03-30 13:29:18'),
('habibi_1774905253867', 1, '2026-03-30 21:14:21'),
('habibi_1774937552136', 1, '2026-03-31 06:12:34'),
('habibi_1774961605768', NULL, '2026-03-31 12:53:30'),
('habibi_1774962563907', 7, '2026-03-31 13:09:26'),
('habibi_1774962945291', 7, '2026-03-31 13:15:48'),
('habibi_1774971230596', 1, '2026-03-31 15:33:56'),
('habibi_1775118486767', 7, '2026-04-02 08:28:10'),
('habibi_1775296831317', 7, '2026-04-04 10:00:34'),
('habibi_1775297183758', 7, '2026-04-04 10:06:26'),
('habibi_1775297317414', 7, '2026-04-04 10:08:39'),
('habibi_1775297370898', 7, '2026-04-04 10:09:33'),
('habibi_1775297380887', 7, '2026-04-04 10:09:43'),
('habibi_1775297406172', 7, '2026-04-04 10:10:08'),
('habibi_1775552925939', 7, '2026-04-07 09:09:20'),
('habibi_1775995444634', 7, '2026-04-12 12:04:10'),
('habibi_1775996476906', 7, '2026-04-12 12:21:19'),
('habibi_1775996544045', 7, '2026-04-12 12:22:26'),
('shama_1774685029417', 1, '2026-03-28 08:11:30'),
('shama_1774744604719', 1, '2026-03-29 00:36:54'),
('shama_1774746750238', NULL, '2026-03-29 01:13:26'),
('shama_1775996012421', 7, '2026-04-12 12:13:44');

-- --------------------------------------------------------

--
-- Table structure for table `concierge_requests`
--

DROP TABLE IF EXISTS `concierge_requests`;
CREATE TABLE IF NOT EXISTS `concierge_requests` (
  `id` int NOT NULL AUTO_INCREMENT,
  `ref` varchar(30) NOT NULL,
  `user_id` int DEFAULT NULL,
  `booking_ref` varchar(30) DEFAULT NULL,
  `category` enum('transport','restaurant','activity','shopping','medical','other') DEFAULT 'other',
  `request` text NOT NULL,
  `preferred_date` date DEFAULT NULL,
  `preferred_time` varchar(20) DEFAULT NULL,
  `status` enum('pending','in_progress','completed','cancelled') DEFAULT 'pending',
  `response` text,
  `is_read` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ref` (`ref`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `contact_messages`
--

DROP TABLE IF EXISTS `contact_messages`;
CREATE TABLE IF NOT EXISTS `contact_messages` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(200) NOT NULL,
  `email` varchar(191) NOT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `subject` varchar(300) DEFAULT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `dining_reservations`
--

DROP TABLE IF EXISTS `dining_reservations`;
CREATE TABLE IF NOT EXISTS `dining_reservations` (
  `id` int NOT NULL AUTO_INCREMENT,
  `ref` varchar(30) NOT NULL,
  `user_id` int DEFAULT NULL,
  `venue_name` varchar(200) DEFAULT NULL,
  `date` date NOT NULL,
  `time` varchar(10) NOT NULL,
  `guests` int DEFAULT '2',
  `occasion` varchar(100) DEFAULT NULL,
  `requests` text,
  `name` varchar(200) NOT NULL,
  `email` varchar(191) NOT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `status` enum('confirmed','cancelled','seated','completed') DEFAULT 'confirmed',
  `admin_status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `admin_note` text,
  `decided_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ref` (`ref`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `dining_reservations`
--

INSERT INTO `dining_reservations` (`id`, `ref`, `user_id`, `venue_name`, `date`, `time`, `guests`, `occasion`, `requests`, `name`, `email`, `phone`, `status`, `admin_status`, `admin_note`, `decided_at`, `created_at`) VALUES
(1, 'DR202672AC7', NULL, 'Al Qibla', '2026-04-08', '20:20', 2, NULL, NULL, 'Ayaan Khalifa', 'ayaankhalifa4432@gmail.com', '08799097673', 'confirmed', 'pending', NULL, NULL, '2026-04-07 08:45:25'),
(2, 'DR2026D2DC0', NULL, 'Al Qibla', '2026-04-08', '20:20', 2, NULL, NULL, 'Ayaan Khalifa', 'ayaankhalifa4432@gmail.com', '08799097673', 'confirmed', 'approved', NULL, '2026-04-07 14:25:20', '2026-04-07 08:45:34');

-- --------------------------------------------------------

--
-- Table structure for table `events`
--

DROP TABLE IF EXISTS `events`;
CREATE TABLE IF NOT EXISTS `events` (
  `id` int NOT NULL AUTO_INCREMENT,
  `type` enum('wedding','corporate','birthday','gala','conference','private') DEFAULT 'corporate',
  `name` varchar(200) NOT NULL,
  `description` text,
  `capacity` int DEFAULT '100',
  `price_from` decimal(10,2) DEFAULT '0.00',
  `hero_image` varchar(600) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `sort_order` int DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `events`
--

INSERT INTO `events` (`id`, `type`, `name`, `description`, `capacity`, `price_from`, `hero_image`, `is_active`, `sort_order`) VALUES
(1, 'wedding', 'Royal Wedding', 'The wedding of your dreams. Our dedicated wedding planners craft a ceremony and reception that is uniquely yours.', 400, 25000.00, 'https://images.unsplash.com/photo-1519225421980-715cb0215aed?w=900&q=80', 1, 1),
(2, 'corporate', 'Executive Conference', 'State-of-the-art conference facilities for the world\'s most discerning corporate clients.', 500, 8000.00, 'https://images.unsplash.com/photo-1540575467063-178a50c2df87?w=900&q=80', 1, 2),
(3, 'gala', 'Black Tie Gala', 'For charity galas, anniversary celebrations, and exclusive social events.', 600, 15000.00, 'https://images.unsplash.com/photo-1519167758481-83f550bb49b3?w=900&q=80', 1, 3),
(4, 'birthday', 'Milestone Celebration', 'From intimate dinners to lavish birthday parties, we create personalised celebrations.', 100, 3000.00, 'https://images.unsplash.com/photo-1530103862676-de8c9debad1d?w=900&q=80', 1, 4),
(5, 'conference', 'Business Summit', 'Multi-day conference packages with breakout rooms, premium technology, and dedicated event managers.', 300, 5000.00, 'https://images.unsplash.com/photo-1475721027785-f74eccf877e2?w=900&q=80', 1, 5),
(6, 'private', 'Private Dining', 'Exclusive private dining experiences with personalised menus crafted by our Michelin-starred chefs.', 24, 1200.00, 'https://images.unsplash.com/photo-1414235077428-338989a2e8c0?w=900&q=80', 1, 6);

-- --------------------------------------------------------

--
-- Table structure for table `event_bookings`
--

DROP TABLE IF EXISTS `event_bookings`;
CREATE TABLE IF NOT EXISTS `event_bookings` (
  `id` int NOT NULL AUTO_INCREMENT,
  `ref` varchar(30) NOT NULL,
  `event_id` int DEFAULT NULL,
  `user_id` int DEFAULT NULL,
  `name` varchar(200) NOT NULL,
  `email` varchar(191) NOT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `event_date` date DEFAULT NULL,
  `guests` int DEFAULT '50',
  `budget_usd` decimal(10,2) DEFAULT NULL,
  `message` text,
  `status` enum('enquiry','quoted','confirmed','cancelled') DEFAULT 'enquiry',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ref` (`ref`),
  KEY `event_id` (`event_id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `experiences`
--

DROP TABLE IF EXISTS `experiences`;
CREATE TABLE IF NOT EXISTS `experiences` (
  `id` int NOT NULL AUTO_INCREMENT,
  `title` varchar(200) NOT NULL,
  `category` varchar(100) DEFAULT NULL,
  `duration` varchar(80) DEFAULT NULL,
  `price_usd` decimal(10,2) DEFAULT '0.00',
  `max_guests` int DEFAULT '10',
  `description` text,
  `image_url` varchar(600) DEFAULT NULL,
  `sort_order` int DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `experiences`
--

INSERT INTO `experiences` (`id`, `title`, `category`, `duration`, `price_usd`, `max_guests`, `description`, `image_url`, `sort_order`) VALUES
(1, 'Helicopter Sunset Tour', 'Adventure', '90 min', 850.00, 4, 'Soar above the skyline in a private helicopter as the sun sets. Includes champagne and canapés.', 'https://images.unsplash.com/photo-1538593273645-e5c1a440a73c?w=800&q=80', 1),
(2, 'Private Truffle Hunt', 'Culinary', 'Half Day', 1200.00, 6, 'Join our Executive Chef on an exclusive truffle hunt followed by a Michelin-starred cooking class.', 'https://images.unsplash.com/photo-1556909114-f6e7ad7d3136?w=800&q=80', 2),
(3, 'Underwater Spa Journey', 'Wellness', '3 Hours', 480.00, 2, 'Glass-bottom treatment rooms suspended 3 metres below the surface. Marine-inspired treatments.', 'https://images.unsplash.com/photo-1540555700478-4be289fbecef?w=800&q=80', 3),
(4, 'Private Art Gallery Tour', 'Culture', '2 Hours', 320.00, 8, 'Exclusive after-hours access to the city finest gallery with our resident art curator.', 'https://images.unsplash.com/photo-1513475382585-d06e58bcb0e0?w=800&q=80', 4),
(5, 'Yacht Sunrise Champagne', 'Luxury', '3 Hours', 950.00, 8, 'Private yacht departure at dawn. Champagne breakfast and live music on the water.', 'https://images.unsplash.com/photo-1567899378494-47b22a2ae96a?w=800&q=80', 5),
(6, 'Desert Safari & Stargazing', 'Adventure', 'Full Day', 680.00, 4, 'Dune bashing at sunset, Bedouin camp dinner under the stars.', 'https://images.unsplash.com/photo-1509316785289-025f5b846b35?w=800&q=80', 6),
(7, 'Caviar & Vodka Masterclass', 'Culinary', '2 Hours', 420.00, 6, 'Learn to distinguish Beluga from Ossetra with our sommelier, paired with vintage Krug.', 'https://images.unsplash.com/photo-1414235077428-338989a2e8c0?w=800&q=80', 7),
(8, 'Private Royal Hammam', 'Wellness', '2 Hours', 380.00, 2, 'Authentic Moroccan hammam for two in our private marble suite with argan oil massage.', 'https://images.unsplash.com/photo-1544367567-0f2fcb009e0b?w=800&q=80', 8);

-- --------------------------------------------------------

--
-- Table structure for table `gallery_images`
--

DROP TABLE IF EXISTS `gallery_images`;
CREATE TABLE IF NOT EXISTS `gallery_images` (
  `id` int NOT NULL AUTO_INCREMENT,
  `title` varchar(200) DEFAULT NULL,
  `category` varchar(80) DEFAULT 'hotel',
  `image_url` varchar(600) NOT NULL,
  `is_local` tinyint(1) DEFAULT '0',
  `filename` varchar(200) DEFAULT NULL,
  `sort_order` int DEFAULT '0',
  `is_active` tinyint(1) DEFAULT '1',
  `uploaded_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `media_type` enum('image','video') NOT NULL DEFAULT 'image',
  PRIMARY KEY (`id`),
  KEY `uploaded_by` (`uploaded_by`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `gallery_images`
--

INSERT INTO `gallery_images` (`id`, `title`, `category`, `image_url`, `is_local`, `filename`, `sort_order`, `is_active`, `uploaded_by`, `created_at`, `media_type`) VALUES
(1, 'Grand Lobby', 'lobby', 'https://images.unsplash.com/photo-1566073771259-6a8506099945?w=900&q=80', 0, NULL, 1, 1, NULL, '2026-03-28 05:18:15', 'image'),
(2, 'Infinity Pool', 'pool', 'https://images.unsplash.com/photo-1571896349842-33c89424de2d?w=900&q=80', 0, NULL, 2, 1, NULL, '2026-03-28 05:18:15', 'image'),
(3, 'Fine Dining', 'restaurant', 'https://images.unsplash.com/photo-1414235077428-338989a2e8c0?w=900&q=80', 0, NULL, 3, 1, NULL, '2026-03-28 05:18:15', 'image'),
(4, 'Deluxe Room', 'rooms', 'https://images.unsplash.com/photo-1631049307264-da0ec9d70304?w=900&q=80', 0, NULL, 4, 1, NULL, '2026-03-28 05:18:15', 'image'),
(5, 'Spa & Wellness', 'spa', 'https://images.unsplash.com/photo-1540555700478-4be289fbecef?w=900&q=80', 0, NULL, 5, 1, NULL, '2026-03-28 05:18:15', 'image'),
(6, 'Rooftop Bar', 'bar', 'https://images.unsplash.com/photo-1470337458703-46ad1756a187?w=900&q=80', 0, NULL, 6, 1, NULL, '2026-03-28 05:18:15', 'image'),
(7, 'Garden Terrace', 'outdoor', 'https://images.unsplash.com/photo-1520250497591-112f2f40a3f4?w=900&q=80', 0, NULL, 7, 1, NULL, '2026-03-28 05:18:15', 'image'),
(8, 'Presidential Suite', 'rooms', 'https://images.unsplash.com/photo-1582719508461-905c673771fd?w=900&q=80', 0, NULL, 8, 1, NULL, '2026-03-28 05:18:15', 'image'),
(9, 'Ballroom', 'events', 'https://images.unsplash.com/photo-1519167758481-83f550bb49b3?w=900&q=80', 0, NULL, 9, 1, NULL, '2026-03-28 05:18:15', 'image'),
(10, 'Beach Club', 'outdoor', 'https://images.unsplash.com/photo-1507525428034-b723cf961d3e?w=900&q=80', 0, NULL, 10, 1, NULL, '2026-03-28 05:18:15', 'image'),
(11, 'Gym & Fitness', 'facilities', 'https://images.unsplash.com/photo-1534438327276-14e5300c3a48?w=900&q=80', 0, NULL, 11, 1, NULL, '2026-03-28 05:18:15', 'image'),
(12, 'Family Room', 'rooms', 'https://images.unsplash.com/photo-1595526114035-0d45ed16cfbf?w=900&q=80', 0, NULL, 12, 1, NULL, '2026-03-28 05:18:15', 'image');

-- --------------------------------------------------------

--
-- Table structure for table `gift_cards`
--

DROP TABLE IF EXISTS `gift_cards`;
CREATE TABLE IF NOT EXISTS `gift_cards` (
  `id` int NOT NULL AUTO_INCREMENT,
  `code` varchar(20) NOT NULL,
  `value_usd` decimal(10,2) NOT NULL,
  `balance_usd` decimal(10,2) NOT NULL,
  `purchased_by` int DEFAULT NULL,
  `for_name` varchar(200) DEFAULT NULL,
  `for_email` varchar(191) DEFAULT NULL,
  `message` text,
  `is_active` tinyint(1) DEFAULT '1',
  `expires_at` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`),
  KEY `purchased_by` (`purchased_by`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `gift_cards`
--

INSERT INTO `gift_cards` (`id`, `code`, `value_usd`, `balance_usd`, `purchased_by`, `for_name`, `for_email`, `message`, `is_active`, `expires_at`, `created_at`) VALUES
(1, '53AC-99CA-0E0C-B7F7', 500.00, 0.00, 1, 'A', 'ayaankhalifa4432@gmail.com', 'i love you bro', 1, '2028-03-30', '2026-03-30 08:03:26');

-- --------------------------------------------------------

--
-- Table structure for table `gift_card_usage`
--

DROP TABLE IF EXISTS `gift_card_usage`;
CREATE TABLE IF NOT EXISTS `gift_card_usage` (
  `id` int NOT NULL AUTO_INCREMENT,
  `card_id` int NOT NULL,
  `booking_ref` varchar(30) DEFAULT NULL,
  `amount_used` decimal(10,2) NOT NULL,
  `used_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `card_id` (`card_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `gift_card_usage`
--

INSERT INTO `gift_card_usage` (`id`, `card_id`, `booking_ref`, `amount_used`, `used_at`) VALUES
(1, 1, 'BK2026CCBDD', 500.00, '2026-03-30 08:51:04');

-- --------------------------------------------------------

--
-- Table structure for table `hotel_properties`
--

DROP TABLE IF EXISTS `hotel_properties`;
CREATE TABLE IF NOT EXISTS `hotel_properties` (
  `id` int NOT NULL AUTO_INCREMENT,
  `slug` varchar(100) NOT NULL,
  `name` varchar(200) NOT NULL,
  `city` varchar(100) NOT NULL,
  `country` varchar(100) NOT NULL,
  `continent` varchar(50) DEFAULT NULL,
  `lat` decimal(10,7) DEFAULT NULL,
  `lng` decimal(10,7) DEFAULT NULL,
  `hero_image` varchar(600) DEFAULT NULL,
  `description` text,
  `rooms_count` int DEFAULT '0',
  `is_flagship` tinyint(1) DEFAULT '0',
  `sort_order` int DEFAULT '0',
  `amenities` text,
  `phone` varchar(50) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `since_year` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `hotel_properties`
--

INSERT INTO `hotel_properties` (`id`, `slug`, `name`, `city`, `country`, `continent`, `lat`, `lng`, `hero_image`, `description`, `rooms_count`, `is_flagship`, `sort_order`, `amenities`, `phone`, `email`, `since_year`) VALUES
(1, 'dubai', 'Royale Vista Dubai', 'Dubai', 'UAE', 'Asia', 25.1124000, 55.1389000, 'https://images.unsplash.com/photo-1548438294-1ad5d5f4f063?w=800&q=80', 'Palm Jumeirah flagship rising above the Arabian Gulf.', 400, 1, 1, 'Infinity Pool,Private Beach,Butler Service,Helipad', '+971 4 123 4567', 'dubai@royalevista.com', 2019),
(2, 'new-york', 'Royale Vista New York', 'New York', 'USA', 'Americas', 40.7614000, -73.9776000, 'https://images.unsplash.com/photo-1534430480872-3498386e7856?w=800&q=80', 'Midtown Manhattan landmark overlooking Central Park.', 350, 0, 2, 'Rooftop Pool,Michelin Dining,Central Park View,Spa', '+1 212 555 0100', 'newyork@royalevista.com', 2017),
(3, 'paris', 'Royale Vista Paris', 'Paris', 'France', 'Europe', 48.8659000, 2.3041000, 'https://images.unsplash.com/photo-1502602898657-3e91760cbb34?w=800&q=80', 'Set on Avenue Montaigne, steps from the Eiffel Tower.', 220, 0, 3, 'Eiffel View,French Cuisine,Couture Concierge,Garden', '+33 1 44 55 66 00', 'paris@royalevista.com', 2015),
(4, 'london', 'Royale Vista London', 'London', 'UK', 'Europe', 51.5034000, -0.1540000, 'https://images.unsplash.com/photo-1513635269975-59663e0ac1ad?w=800&q=80', 'Historic Mayfair townhouse overlooking Hyde Park.', 180, 0, 4, 'Hyde Park View,Afternoon Tea,Royal Butler,Drawing Room', '+44 20 7123 4567', 'london@royalevista.com', 2016),
(5, 'singapore', 'Royale Vista Singapore', 'Singapore', 'Singapore', 'Asia', 1.2782000, 103.8521000, 'https://images.unsplash.com/photo-1525625293386-3f8f99389edd?w=800&q=80', 'Soaring above Marina Bay with harbour panoramas.', 300, 0, 5, 'Marina Bay View,Infinity Pool,Gardens by the Bay,Spa', '+65 6123 4567', 'singapore@royalevista.com', 2020),
(6, 'tokyo', 'Royale Vista Tokyo', 'Tokyo', 'Japan', 'Asia', 35.6829000, 139.7654000, 'https://images.unsplash.com/photo-1540959733332-eab4deabeeaf?w=800&q=80', 'Marunouchi elegance overlooking Imperial Palace.', 250, 0, 6, 'Imperial View,Kaiseki Dining,Onsen Spa,Tea Ceremony', '+81 3 1234 5678', 'tokyo@royalevista.com', 2021),
(7, 'mumbai', 'Royale Vista Mumbai', 'Mumbai', 'India', 'Asia', 18.9256000, 72.8242000, 'https://images.unsplash.com/photo-1567157577867-05ccb1388e66?w=800&q=80', 'Overlooking the Arabian Sea at Nariman Point.', 280, 0, 7, 'Sea View,Bollywood Lounge,Pool Deck,Ayurveda Spa', '+91 22 1234 5678', 'mumbai@royalevista.com', 2018),
(8, 'sydney', 'Royale Vista Sydney', 'Sydney', 'Australia', 'Pacific', -33.8589000, 151.2101000, 'https://images.unsplash.com/photo-1506905925346-21bda4d32df4?w=800&q=80', 'Opera House and Harbour Bridge views from every room.', 200, 0, 8, 'Harbour View,Rooftop Bar,Opera House Terrace,Yacht Club', '+61 2 9123 4567', 'sydney@royalevista.com', 2022);

-- --------------------------------------------------------

--
-- Table structure for table `job_applications`
--

DROP TABLE IF EXISTS `job_applications`;
CREATE TABLE IF NOT EXISTS `job_applications` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(150) NOT NULL,
  `email` varchar(200) NOT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `position` varchar(200) DEFAULT NULL,
  `location` varchar(150) DEFAULT NULL,
  `experience_years` varchar(20) DEFAULT NULL,
  `cover_letter` text,
  `cv_path` varchar(255) DEFAULT NULL,
  `status` enum('new','reviewing','shortlisted','rejected') DEFAULT 'new',
  `applied_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_status` (`status`),
  KEY `idx_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `loyalty_points`
--

DROP TABLE IF EXISTS `loyalty_points`;
CREATE TABLE IF NOT EXISTS `loyalty_points` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `total_points` int DEFAULT '0',
  `lifetime_points` int DEFAULT '0',
  `tier` enum('bronze','silver','gold','platinum') DEFAULT 'bronze',
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=54 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `loyalty_points`
--

INSERT INTO `loyalty_points` (`id`, `user_id`, `total_points`, `lifetime_points`, `tier`, `updated_at`) VALUES
(1, 1, 83452, 94898, 'bronze', '2026-03-31 12:56:11'),
(2, 2, 4248, 4248, 'silver', '2026-03-28 05:18:15'),
(3, 3, 10620, 10620, 'gold', '2026-03-28 05:18:15'),
(4, 4, 14160, 14160, 'platinum', '2026-03-28 05:18:15'),
(22, 7, 55015, 65679, 'bronze', '2026-04-18 06:21:24'),
(24, 8, 5664, 5664, 'bronze', '2026-03-31 08:09:43'),
(30, 10, 2832, 2832, 'bronze', '2026-03-31 15:59:15'),
(32, 12, 11800, 11800, 'bronze', '2026-04-02 05:43:02');

-- --------------------------------------------------------

--
-- Table structure for table `loyalty_transactions`
--

DROP TABLE IF EXISTS `loyalty_transactions`;
CREATE TABLE IF NOT EXISTS `loyalty_transactions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `booking_ref` varchar(30) DEFAULT NULL,
  `type` enum('earn','redeem','bonus','expire','admin') DEFAULT 'earn',
  `points` int NOT NULL,
  `balance_after` int NOT NULL,
  `description` varchar(200) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=29 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `loyalty_transactions`
--

INSERT INTO `loyalty_transactions` (`id`, `user_id`, `booking_ref`, `type`, `points`, `balance_after`, `description`, `created_at`) VALUES
(1, 1, 'BK202664211', 'earn', 2832, 2832, 'Booking BK202664211', '2026-03-28 06:24:24'),
(2, 1, 'BK202663C34', 'earn', 2832, 5664, 'Booking BK202663C34', '2026-03-28 08:40:36'),
(3, 1, 'BK20260CCC8', 'earn', 20060, 25724, 'Earned for BK20260CCC8', '2026-03-28 08:42:09'),
(4, 1, 'BK2026511D4', 'earn', 2832, 18526, 'Earned for BK2026511D4', '2026-03-28 22:59:21'),
(5, 1, 'BK2026CCBDD', 'earn', 5900, 23060, 'Booking BK2026CCBDD', '2026-03-30 08:51:04'),
(6, 1, 'BK2026A74D6', 'earn', 23576, 46636, 'Earned for BK2026A74D6', '2026-03-30 14:47:03'),
(7, 1, 'BK2026F65B9', 'earn', 2832, 49468, 'Booking BK2026F65B9', '2026-03-30 20:58:14'),
(8, 1, 'BK20268E5AC', 'earn', 2832, 52300, 'Earned for BK20268E5AC', '2026-03-30 21:17:48'),
(9, 1, 'BK20264132E', 'earn', 2832, 55132, 'Booking BK20264132E', '2026-03-31 05:28:26'),
(10, 1, 'BK20262823E', 'earn', 2832, 57964, 'Booking BK20262823E', '2026-03-31 05:57:43'),
(11, 1, 'BK20269BA39', 'earn', 2832, 60796, 'Earned for BK20269BA39', '2026-03-31 06:09:17'),
(12, 7, 'BK20266898D', 'earn', 2832, 2832, 'Booking BK20266898D', '2026-03-31 07:51:52'),
(13, 1, 'BK2026689AA', 'earn', 2832, 63628, 'Booking BK2026689AA', '2026-03-31 07:56:28'),
(14, 8, 'BK2026C5218', 'earn', 2832, 2832, 'Booking BK2026C5218', '2026-03-31 08:08:24'),
(15, 8, 'BK20265DB38', 'earn', 2832, 5664, 'Earned for BK20265DB38', '2026-03-31 08:09:43'),
(16, 1, 'BK202699416', 'earn', 2832, 66460, 'Earned for BK202699416', '2026-03-31 11:21:01'),
(17, 1, 'BK2026730A8', 'earn', 2832, 69292, 'Booking BK2026730A8', '2026-03-31 11:21:56'),
(18, 1, 'BK202646E33', 'earn', 14160, 83452, 'Booking BK202646E33', '2026-03-31 12:56:11'),
(19, 7, 'BK20265A5B7', 'earn', 2832, 5664, 'Booking BK20265A5B7', '2026-03-31 15:54:18'),
(20, 10, 'BK2026436AF', 'earn', 2832, 2832, 'Earned for BK2026436AF', '2026-03-31 15:59:15'),
(21, 7, 'BK20265A464', 'earn', 2832, 8496, 'Booking BK20265A464', '2026-03-31 16:12:21'),
(22, 12, 'BK202672D08', 'earn', 5900, 5900, 'Booking BK202672D08', '2026-04-02 05:40:47'),
(23, 12, 'BK20261DD41', 'earn', 5900, 11800, 'Booking BK20261DD41', '2026-04-02 05:43:02'),
(24, 7, 'BK2026A312F', 'earn', 35400, 43896, 'Earned for BK2026A312F', '2026-04-02 08:27:10'),
(25, 7, 'BK202682CBD', 'earn', 2832, 46728, 'Booking BK202682CBD', '2026-04-07 09:12:01'),
(26, 7, 'BK2026BC2E0', 'earn', 5664, 52392, 'Earned for BK2026BC2E0', '2026-04-07 09:12:58'),
(27, 7, 'BK20263316F', 'earn', 11328, 63720, 'Booking BK20263316F', '2026-04-07 09:13:47'),
(28, 7, 'BK202613CDE', 'earn', 1959, 55015, 'Booking BK202613CDE', '2026-04-18 06:21:24');

-- --------------------------------------------------------

--
-- Table structure for table `memberships`
--

DROP TABLE IF EXISTS `memberships`;
CREATE TABLE IF NOT EXISTS `memberships` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `price_usd` decimal(10,2) NOT NULL,
  `discount_pct` int DEFAULT '0',
  `duration_days` int DEFAULT '365',
  `description` text,
  `badge_text` varchar(80) DEFAULT NULL,
  `badge_color` varchar(20) DEFAULT '#d4af37',
  `gradient_from` varchar(7) DEFAULT '#d4af37',
  `gradient_to` varchar(7) DEFAULT '#b89628',
  `icon` varchar(10) DEFAULT '⭐',
  `is_popular` tinyint(1) DEFAULT '0',
  `sort_order` int DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `memberships`
--

INSERT INTO `memberships` (`id`, `name`, `price_usd`, `discount_pct`, `duration_days`, `description`, `badge_text`, `badge_color`, `gradient_from`, `gradient_to`, `icon`, `is_popular`, `sort_order`) VALUES
(1, 'Silver', 100.00, 5, 180, '5% off all bookings + free cancellation up to 24h', 'Entry Level', '#94a3b8', '#94a3b8', '#64748b', '🥉', 0, 1),
(2, 'Gold', 200.00, 15, 365, '15% off + priority support + complimentary room upgrade', 'Best Value', '#f6c343', '#f59e0b', '#d97706', '🥇', 1, 2),
(3, 'Platinum', 300.00, 25, 365, '25% off + VIP treatment + complimentary breakfast daily', 'VIP Exclusive', '#a78bfa', '#8b5cf6', '#7c3aed', '👑', 0, 3);

-- --------------------------------------------------------

--
-- Table structure for table `membership_features`
--

DROP TABLE IF EXISTS `membership_features`;
CREATE TABLE IF NOT EXISTS `membership_features` (
  `id` int NOT NULL AUTO_INCREMENT,
  `membership_id` int NOT NULL,
  `feature` varchar(200) NOT NULL,
  `icon` varchar(10) DEFAULT NULL,
  `is_highlight` tinyint(1) DEFAULT '0',
  `sort_order` int DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `membership_id` (`membership_id`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `membership_features`
--

INSERT INTO `membership_features` (`id`, `membership_id`, `feature`, `icon`, `is_highlight`, `sort_order`) VALUES
(1, 1, '5% discount on all bookings', '%', 0, 1),
(2, 1, 'Free cancellation up to 24h', '🔄', 0, 2),
(3, 1, 'Member email support', '📧', 0, 3),
(4, 1, 'Monthly newsletter & deals', '📰', 0, 4),
(5, 2, '15% discount on all bookings', '%', 0, 1),
(6, 2, 'Free cancellation up to 48h', '🔄', 0, 2),
(7, 2, 'Priority 24/7 support', '💬', 1, 3),
(8, 2, 'Room upgrade on availability', '⬆️', 1, 4),
(9, 2, 'Late checkout 2PM', '🕑', 0, 5),
(10, 3, '25% discount on all bookings', '%', 0, 1),
(11, 3, 'Free cancellation anytime', '🔄', 0, 2),
(12, 3, 'Dedicated concierge 24/7', '🤵', 1, 3),
(13, 3, 'Guaranteed room upgrade', '👑', 1, 4),
(14, 3, 'Daily complimentary breakfast', '🍳', 1, 5),
(15, 3, 'Airport limousine pickup', '🚗', 1, 6);

-- --------------------------------------------------------

--
-- Table structure for table `newsletter_subscribers`
--

DROP TABLE IF EXISTS `newsletter_subscribers`;
CREATE TABLE IF NOT EXISTS `newsletter_subscribers` (
  `id` int NOT NULL AUTO_INCREMENT,
  `email` varchar(191) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

DROP TABLE IF EXISTS `notifications`;
CREATE TABLE IF NOT EXISTS `notifications` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int DEFAULT NULL,
  `type` enum('booking','cancellation','payment','review','system','offer','loyalty') DEFAULT 'system',
  `title` varchar(200) NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) DEFAULT '0',
  `link` varchar(400) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=35 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `type`, `title`, `message`, `is_read`, `link`, `created_at`) VALUES
(1, 1, 'booking', 'Booking Confirmed — BK202664211', 'Check-in: 28 Mar 2026. Ref: BK202664211', 1, 'http://localhost/wtf/bookings.php', '2026-03-28 06:24:24'),
(2, 1, 'booking', 'Booking Confirmed — BK202663C34', 'Check-in: 28 Mar 2026. Ref: BK202663C34', 1, 'http://localhost/wtf/bookings.php', '2026-03-28 08:40:36'),
(3, 1, 'booking', 'Booking Confirmed — BK20260CCC8', 'Your reservation has been confirmed for 2 night(s). Check-in: 2026-03-28.', 1, 'http://localhost/wtf/bookings.php', '2026-03-28 08:42:09'),
(4, 1, 'cancellation', 'Booking Cancelled — BK20260CCC8', 'Your booking BK20260CCC8 has been cancelled successfully.', 1, 'http://localhost/wtf/bookings.php', '2026-03-28 11:18:01'),
(5, 1, 'booking', 'Booking Confirmed — BK2026511D4', 'Your reservation has been confirmed for 1 night(s). Check-in: 2026-03-29.', 1, 'http://localhost/wtf/bookings.php', '2026-03-28 22:59:21'),
(6, 2, 'system', 'good', 'good morning have a good day', 1, NULL, '2026-03-28 23:18:43'),
(7, 3, 'system', 'good', 'good morning have a good day', 0, NULL, '2026-03-28 23:18:43'),
(8, 4, 'system', 'good', 'good morning have a good day', 0, NULL, '2026-03-28 23:18:43'),
(9, 1, 'cancellation', 'Booking Cancelled — BK2026511D4', 'Your booking BK2026511D4 has been cancelled successfully.', 1, 'http://localhost/wtf/bookings.php', '2026-03-28 23:46:38'),
(10, 1, 'booking', 'Booking Confirmed — BK2026CCBDD', 'Check-in: 30 Mar 2026. Ref: BK2026CCBDD', 1, 'http://localhost/wtf/bookings.php', '2026-03-30 08:51:04'),
(11, 1, 'booking', 'Booking Confirmed — BK2026A74D6', 'Your reservation has been confirmed for 2 night(s). Check-in: 2026-03-30.', 1, 'http://localhost/wtf/bookings.php', '2026-03-30 14:47:03'),
(12, 1, 'booking', 'Booking Confirmed — BK2026F65B9', 'Check-in: 30 Mar 2026. Ref: BK2026F65B9', 1, 'http://localhost/wtf4/bookings.php', '2026-03-30 20:58:14'),
(13, 1, 'booking', 'Booking Confirmed — BK20268E5AC', 'Your reservation has been confirmed for 2 night(s). Check-in: 2026-03-30.', 1, 'http://localhost/wtf2/bookings.php', '2026-03-30 21:17:48'),
(14, 1, 'booking', 'Booking Confirmed — BK20264132E', 'Check-in: 31 Mar 2026. Ref: BK20264132E', 1, 'http://localhost/wtf5/bookings.php', '2026-03-31 05:28:26'),
(15, 1, 'booking', 'Booking Confirmed — BK20262823E', 'Check-in: 31 Mar 2026. Ref: BK20262823E', 1, 'http://localhost/wtf5/bookings.php', '2026-03-31 05:57:43'),
(16, 1, 'booking', 'Booking Confirmed — BK20269BA39', 'Your reservation has been confirmed for 2 night(s). Check-in: 2026-03-31.', 1, 'http://localhost/wtf5/bookings.php', '2026-03-31 06:09:17'),
(17, 7, 'booking', 'Booking Confirmed — BK20266898D', 'Check-in: 31 Mar 2026. Ref: BK20266898D', 1, 'http://localhost/wtf5/bookings.php', '2026-03-31 07:51:52'),
(18, 1, 'booking', 'Booking Confirmed — BK2026689AA', 'Check-in: 31 Mar 2026. Ref: BK2026689AA', 1, 'http://localhost/wtf5/bookings.php', '2026-03-31 07:56:28'),
(19, 8, 'booking', 'Booking Confirmed — BK2026C5218', 'Check-in: 31 Mar 2026. Ref: BK2026C5218', 1, 'http://localhost/wtf5/bookings.php', '2026-03-31 08:08:24'),
(20, 8, 'booking', 'Booking Confirmed — BK20265DB38', 'Your reservation has been confirmed for 1 night(s). Check-in: 2026-04-03.', 1, 'http://localhost/wtf5/bookings.php', '2026-03-31 08:09:43'),
(21, 1, 'booking', 'Booking Confirmed — BK202699416', 'Your reservation has been confirmed for 2 night(s). Check-in: 2026-03-31.', 1, 'http://localhost/wtf5/bookings.php', '2026-03-31 11:21:01'),
(22, 1, 'booking', 'Booking Confirmed — BK2026730A8', 'Check-in: 31 Mar 2026. Ref: BK2026730A8', 1, 'http://localhost/wtf5/bookings.php', '2026-03-31 11:21:56'),
(23, 1, 'booking', 'Booking Confirmed — BK202646E33', 'Check-in: 31 Mar 2026. Ref: BK202646E33', 1, 'http://localhost/wtf5/wtf5/bookings.php', '2026-03-31 12:56:11'),
(24, 7, 'booking', 'Booking Confirmed — BK20265A5B7', 'Check-in: 31 Mar 2026. Ref: BK20265A5B7', 1, 'http://localhost/wtf5/wtf6/bookings.php', '2026-03-31 15:54:18'),
(25, 10, 'booking', 'Booking Confirmed — BK2026436AF', 'Your reservation has been confirmed for 2 night(s). Check-in: 2026-03-31.', 1, 'http://localhost/wtf5/wtf6/bookings.php', '2026-03-31 15:59:15'),
(26, 7, 'booking', 'Booking Confirmed — BK20265A464', 'Check-in: 31 Mar 2026. Ref: BK20265A464', 1, 'http://localhost/wtf5/wtf6/bookings.php', '2026-03-31 16:12:21'),
(27, 12, 'booking', 'Booking Confirmed — BK202672D08', 'Check-in: 02 Apr 2026. Ref: BK202672D08', 0, 'http://localhost/wtf7/bookings.php', '2026-04-02 05:40:47'),
(28, 12, 'booking', 'Booking Confirmed — BK20261DD41', 'Check-in: 02 Apr 2026. Ref: BK20261DD41', 0, 'http://localhost/wtf7/bookings.php', '2026-04-02 05:43:02'),
(29, 7, 'booking', 'Booking Confirmed — BK2026A312F', 'Your reservation has been confirmed for 6 night(s). Check-in: 2026-04-08.', 1, 'http://localhost/wtf7/bookings.php', '2026-04-02 08:27:15'),
(30, 7, 'booking', 'Booking Confirmed — BK202682CBD', 'Check-in: 07 Apr 2026. Ref: BK202682CBD', 0, 'http://localhost/wtf7/bookings.php', '2026-04-07 09:12:01'),
(31, 7, 'booking', 'Booking Confirmed — BK2026BC2E0', 'Your reservation has been confirmed for 2 night(s). Check-in: 2026-04-07.', 0, 'http://localhost/wtf7/bookings.php', '2026-04-07 09:13:02'),
(32, 7, 'booking', 'Booking Confirmed — BK20263316F', 'Check-in: 10 Apr 2026. Ref: BK20263316F', 0, 'http://localhost/wtf7/bookings.php', '2026-04-07 09:13:47'),
(33, 7, 'cancellation', 'Booking Cancelled — BK20263316F', 'Your booking BK20263316F has been cancelled successfully.', 0, 'http://localhost/wtf7/bookings.php', '2026-04-07 09:15:36'),
(34, 7, 'booking', 'Booking Confirmed — BK202613CDE', 'Check-in: 18 Apr 2026. Ref: BK202613CDE', 1, 'http://localhost/wtf7/bookings.php', '2026-04-18 06:21:24');

-- --------------------------------------------------------

--
-- Table structure for table `offers`
--

DROP TABLE IF EXISTS `offers`;
CREATE TABLE IF NOT EXISTS `offers` (
  `id` int NOT NULL AUTO_INCREMENT,
  `code` varchar(50) NOT NULL,
  `type` enum('percent','fixed') DEFAULT 'percent',
  `value` decimal(10,2) NOT NULL,
  `description` text,
  `valid_from` date DEFAULT NULL,
  `valid_to` date DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `uses_max` int DEFAULT NULL,
  `uses_count` int DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `offers`
--

INSERT INTO `offers` (`id`, `code`, `type`, `value`, `description`, `valid_from`, `valid_to`, `is_active`, `uses_max`, `uses_count`, `created_at`) VALUES
(1, 'WELCOME10', 'percent', 10.00, 'Welcome gift — 10% off your first stay!', '2024-01-01', '2027-12-31', 1, NULL, 1, '2026-03-28 05:18:15'),
(2, 'SUMMER20', 'percent', 20.00, 'Summer special — 20% off all rooms.', '2024-06-01', '2027-09-30', 1, NULL, 0, '2026-03-28 05:18:15'),
(3, 'HOLIDAY25', 'percent', 25.00, 'Holiday magic — 25% off festive stays.', '2024-11-01', '2027-01-15', 1, NULL, 0, '2026-03-28 05:18:15'),
(4, 'WEEKEND15', 'percent', 15.00, 'Weekend escape — 15% off Fri–Sun.', '2024-01-01', '2027-12-31', 1, NULL, 0, '2026-03-28 05:18:15'),
(5, 'FAMILY30', 'percent', 30.00, 'Family fun — 30% off Family Rooms.', '2024-01-01', '2027-12-31', 1, NULL, 0, '2026-03-28 05:18:15'),
(6, 'FLAT50', 'fixed', 50.00, 'Flat $50 off any booking.', '2024-01-01', '2027-12-31', 1, NULL, 0, '2026-03-28 05:18:15');

-- --------------------------------------------------------

--
-- Table structure for table `payment_logs`
--

DROP TABLE IF EXISTS `payment_logs`;
CREATE TABLE IF NOT EXISTS `payment_logs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `booking_ref` varchar(30) NOT NULL,
  `user_id` int DEFAULT NULL,
  `amount_usd` decimal(10,2) NOT NULL,
  `method` varchar(50) DEFAULT NULL,
  `status` enum('pending','success','failed','refunded') DEFAULT 'pending',
  `transaction_id` varchar(200) DEFAULT NULL,
  `gateway_response` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `payment_logs`
--

INSERT INTO `payment_logs` (`id`, `booking_ref`, `user_id`, `amount_usd`, `method`, `status`, `transaction_id`, `gateway_response`, `created_at`) VALUES
(1, 'BK202664211', 1, 283.20, 'hotel', 'pending', 'TXN6C1F1C4730', NULL, '2026-03-28 06:24:24'),
(2, 'BK202663C34', 1, 283.20, 'hotel', 'pending', 'TXN1FC37B6E6A', NULL, '2026-03-28 08:40:36'),
(3, 'BK2026CCBDD', 1, 90.00, 'hotel', 'pending', 'TXN8A5F7C1743', NULL, '2026-03-30 08:51:04'),
(4, 'BK2026F65B9', 1, 283.20, 'hotel', 'pending', 'TXNE0F2AA0640', NULL, '2026-03-30 20:58:14'),
(5, 'BK20264132E', 1, 283.20, 'hotel', 'pending', 'TXNE0BB8C1602', NULL, '2026-03-31 05:28:26'),
(6, 'BK20262823E', 1, 283.20, 'hotel', 'pending', 'TXN4CDF1F6EC7', NULL, '2026-03-31 05:57:43'),
(7, 'BK20266898D', 7, 283.20, 'hotel', 'pending', 'TXN4D50A0FC53', NULL, '2026-03-31 07:51:52'),
(8, 'BK2026689AA', 1, 283.20, 'hotel', 'pending', 'TXNB6EFEFB364', NULL, '2026-03-31 07:56:28'),
(9, 'BK2026C5218', 8, 283.20, 'hotel', 'pending', 'TXN7686DA0C8A', NULL, '2026-03-31 08:08:24'),
(10, 'BK2026730A8', 1, 283.20, 'hotel', 'pending', 'TXNE6C26E8DAD', NULL, '2026-03-31 11:21:56'),
(11, 'BK202646E33', 1, 1416.00, 'hotel', 'pending', 'TXNCEBF2BBCF6', NULL, '2026-03-31 12:56:11'),
(12, 'BK20265A5B7', 7, 283.20, 'hotel', 'pending', 'TXN7289AB1053', NULL, '2026-03-31 15:54:18'),
(13, 'BK20265A464', 7, 283.20, 'hotel', 'pending', 'TXN8287E1BAF8', NULL, '2026-03-31 16:12:21'),
(14, 'BK202672D08', 12, 590.00, 'hotel', 'pending', 'TXNEE6D2F358B', NULL, '2026-04-02 05:40:47'),
(15, 'BK20261DD41', 12, 590.00, 'hotel', 'pending', 'TXNCE1351D7C7', NULL, '2026-04-02 05:43:02'),
(16, 'BK202682CBD', 7, 283.20, 'hotel', 'pending', 'TXN15B451D072', NULL, '2026-04-07 09:12:01'),
(17, 'BK20263316F', 7, 1132.80, 'hotel', 'pending', 'TXNAAB85BF66D', NULL, '2026-04-07 09:13:47'),
(18, 'BK202613CDE', 7, 195.88, 'hotel', 'pending', 'TXND213C9C279', NULL, '2026-04-18 06:21:24');

-- --------------------------------------------------------

--
-- Table structure for table `price_overrides`
--

DROP TABLE IF EXISTS `price_overrides`;
CREATE TABLE IF NOT EXISTS `price_overrides` (
  `id` int NOT NULL AUTO_INCREMENT,
  `room_type_id` int NOT NULL,
  `date_from` date NOT NULL,
  `date_to` date NOT NULL,
  `price_usd` decimal(10,2) NOT NULL,
  `reason` varchar(200) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `room_type_id` (`room_type_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `properties`
--

DROP TABLE IF EXISTS `properties`;
CREATE TABLE IF NOT EXISTS `properties` (
  `id` int NOT NULL AUTO_INCREMENT,
  `code` varchar(10) NOT NULL,
  `slug` varchar(100) DEFAULT NULL,
  `name` varchar(200) NOT NULL,
  `city` varchar(100) NOT NULL,
  `country` varchar(100) NOT NULL,
  `country_code` varchar(3) DEFAULT NULL,
  `continent` varchar(50) DEFAULT NULL,
  `tagline` varchar(300) DEFAULT NULL,
  `headline` varchar(300) DEFAULT NULL,
  `description` text,
  `address` text,
  `lat` decimal(10,7) DEFAULT NULL,
  `lng` decimal(10,7) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `email` varchar(191) DEFAULT NULL,
  `hero_image` varchar(600) DEFAULT NULL,
  `thumb_image` varchar(600) DEFAULT NULL,
  `rooms_count` int DEFAULT '0',
  `min_price_usd` decimal(10,2) DEFAULT NULL,
  `stars` int DEFAULT '5',
  `year_opened` int DEFAULT NULL,
  `is_flagship` tinyint(1) DEFAULT '0',
  `is_active` tinyint(1) DEFAULT '1',
  `sort_order` int DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `properties`
--

INSERT INTO `properties` (`id`, `code`, `slug`, `name`, `city`, `country`, `country_code`, `continent`, `tagline`, `headline`, `description`, `address`, `lat`, `lng`, `phone`, `email`, `hero_image`, `thumb_image`, `rooms_count`, `min_price_usd`, `stars`, `year_opened`, `is_flagship`, `is_active`, `sort_order`, `created_at`) VALUES
(1, 'DXB', 'royale-vista-dubai', 'Royale Vista Dubai', 'Dubai', 'United Arab Emirates', 'AE', 'Asia', 'The pinnacle of Arabian luxury', 'The pinnacle of Arabian luxury', 'Our flagship property rising above the Palm Jumeirah.', 'Palm Jumeirah, Dubai UAE', 25.1124000, 55.1389000, '+971 4 123 4567', 'dubai@royalevista.com', 'https://images.unsplash.com/photo-1548438294-1ad5d5f4f063?w=1400&q=80', 'https://images.unsplash.com/photo-1548438294-1ad5d5f4f063?w=600&q=80', 400, 120.00, 5, 2019, 1, 1, 1, '2026-03-28 05:18:15'),
(2, 'NYC', 'royale-vista-new-york', 'Royale Vista New York', 'New York', 'United States', 'US', 'Americas', 'Where the world comes to stay', 'Where the world comes to stay', 'A Midtown Manhattan landmark with unparalleled views of Central Park.', '720 Fifth Avenue, New York NY 10019', 40.7614000, -73.9776000, '+1 212 555 0100', 'newyork@royalevista.com', 'https://images.unsplash.com/photo-1534430480872-3498386e7856?w=1400&q=80', 'https://images.unsplash.com/photo-1534430480872-3498386e7856?w=600&q=80', 350, 120.00, 5, 2017, 0, 1, 2, '2026-03-28 05:18:15'),
(3, 'PAR', 'royale-vista-paris', 'Royale Vista Paris', 'Paris', 'France', 'FR', 'Europe', 'L élégance parisienne, revisitée', 'L élégance parisienne, revisitée', 'Set on the legendary Avenue Montaigne, steps from the Eiffel Tower.', '32 Avenue Montaigne, 75008 Paris', 48.8659000, 2.3041000, '+33 1 44 55 66 00', 'paris@royalevista.com', 'https://images.unsplash.com/photo-1502602898657-3e91760cbb34?w=1400&q=80', 'https://images.unsplash.com/photo-1502602898657-3e91760cbb34?w=600&q=80', 220, 120.00, 5, 2015, 0, 1, 3, '2026-03-28 05:18:15'),
(4, 'LON', 'royale-vista-london', 'Royale Vista London', 'London', 'United Kingdom', 'GB', 'Europe', 'Classic British grandeur, reimagined', 'Classic British grandeur, reimagined', 'A historic Mayfair townhouse transformed into one of London most celebrated hotels.', '45 Park Lane, Mayfair, London W1K', 51.5034000, -0.1540000, '+44 20 7123 4567', 'london@royalevista.com', 'https://images.unsplash.com/photo-1513635269975-59663e0ac1ad?w=1400&q=80', 'https://images.unsplash.com/photo-1513635269975-59663e0ac1ad?w=600&q=80', 180, 120.00, 5, 2016, 0, 1, 4, '2026-03-28 05:18:15'),
(5, 'SIN', 'royale-vista-singapore', 'Royale Vista Singapore', 'Singapore', 'Singapore', 'SG', 'Asia', 'A jewel in the Lion City', 'A jewel in the Lion City', 'Soaring above Marina Bay with panoramic views of the glittering harbour.', '10 Marina Blvd, Singapore 018981', 1.2782000, 103.8521000, '+65 6123 4567', 'singapore@royalevista.com', 'https://images.unsplash.com/photo-1525625293386-3f8f99389edd?w=1400&q=80', 'https://images.unsplash.com/photo-1525625293386-3f8f99389edd?w=600&q=80', 300, 120.00, 5, 2020, 0, 1, 5, '2026-03-28 05:18:15'),
(6, 'TOK', 'royale-vista-tokyo', 'Royale Vista Tokyo', 'Tokyo', 'Japan', 'JP', 'Asia', 'Harmony of tradition and modernity', 'Harmony of tradition and modernity', 'In the heart of Marunouchi overlooking the Imperial Palace Gardens.', '2-1 Marunouchi, Chiyoda, Tokyo', 35.6829000, 139.7654000, '+81 3 1234 5678', 'tokyo@royalevista.com', 'https://images.unsplash.com/photo-1540959733332-eab4deabeeaf?w=1400&q=80', 'https://images.unsplash.com/photo-1540959733332-eab4deabeeaf?w=600&q=80', 250, 120.00, 5, 2021, 0, 1, 6, '2026-03-28 05:18:15'),
(7, 'MUM', 'royale-vista-mumbai', 'Royale Vista Mumbai', 'Mumbai', 'India', 'IN', 'Asia', 'Gateway to Indian elegance', 'Gateway to Indian elegance', 'Overlooking the Arabian Sea in Nariman Point.', 'Nariman Point, Marine Drive, Mumbai', 18.9256000, 72.8242000, '+91 22 1234 5678', 'mumbai@royalevista.com', 'https://images.unsplash.com/photo-1567157577867-05ccb1388e66?w=1400&q=80', 'https://images.unsplash.com/photo-1567157577867-05ccb1388e66?w=600&q=80', 280, 120.00, 5, 2018, 0, 1, 7, '2026-03-28 05:18:15'),
(8, 'SYD', 'royale-vista-sydney', 'Royale Vista Sydney', 'Sydney', 'Australia', 'AU', 'Pacific', 'Iconic harbour, timeless luxury', 'Iconic harbour, timeless luxury', 'Perched above Sydney Harbour with views of the Opera House.', '61 Macquarie Street, Sydney NSW 2000', -33.8589000, 151.2101000, '+61 2 9123 4567', 'sydney@royalevista.com', 'https://images.unsplash.com/photo-1506905925346-21bda4d32df4?w=1400&q=80', 'https://images.unsplash.com/photo-1506905925346-21bda4d32df4?w=600&q=80', 200, 120.00, 5, 2022, 0, 1, 8, '2026-03-28 05:18:15');

-- --------------------------------------------------------

--
-- Table structure for table `property_amenities`
--

DROP TABLE IF EXISTS `property_amenities`;
CREATE TABLE IF NOT EXISTS `property_amenities` (
  `id` int NOT NULL AUTO_INCREMENT,
  `property_id` int NOT NULL,
  `icon` varchar(100) DEFAULT NULL,
  `name` varchar(100) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `property_id` (`property_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `property_images`
--

DROP TABLE IF EXISTS `property_images`;
CREATE TABLE IF NOT EXISTS `property_images` (
  `id` int NOT NULL AUTO_INCREMENT,
  `property_id` int NOT NULL,
  `image_url` varchar(600) NOT NULL,
  `caption` varchar(200) DEFAULT NULL,
  `sort_order` int DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `property_id` (`property_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `review_images`
--

DROP TABLE IF EXISTS `review_images`;
CREATE TABLE IF NOT EXISTS `review_images` (
  `id` int NOT NULL AUTO_INCREMENT,
  `review_id` int NOT NULL,
  `image_url` varchar(600) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `review_id` (`review_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `review_media`
--

DROP TABLE IF EXISTS `review_media`;
CREATE TABLE IF NOT EXISTS `review_media` (
  `id` int NOT NULL AUTO_INCREMENT,
  `review_id` int NOT NULL,
  `type` enum('image','video') DEFAULT 'image',
  `filename` varchar(200) DEFAULT NULL,
  `url` varchar(600) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `review_id` (`review_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `rooms`
--

DROP TABLE IF EXISTS `rooms`;
CREATE TABLE IF NOT EXISTS `rooms` (
  `id` int NOT NULL AUTO_INCREMENT,
  `room_number` varchar(20) NOT NULL,
  `room_type_id` int NOT NULL,
  `floor` int DEFAULT '1',
  `view_type` varchar(80) DEFAULT NULL,
  `status` enum('available','occupied','maintenance') DEFAULT 'available',
  PRIMARY KEY (`id`),
  UNIQUE KEY `room_number` (`room_number`),
  KEY `room_type_id` (`room_type_id`)
) ENGINE=InnoDB AUTO_INCREMENT=241 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `rooms`
--

INSERT INTO `rooms` (`id`, `room_number`, `room_type_id`, `floor`, `view_type`, `status`) VALUES
(1, 'D001', 1, 1, 'Garden View', 'occupied'),
(2, 'D002', 1, 1, 'City View', 'available'),
(3, 'D003', 1, 1, 'Garden View', 'available'),
(4, 'D004', 1, 1, 'City View', 'available'),
(5, 'D005', 1, 1, 'Garden View', 'available'),
(6, 'D006', 1, 1, 'City View', 'available'),
(7, 'D007', 1, 1, 'Garden View', 'available'),
(8, 'D008', 1, 1, 'City View', 'available'),
(9, 'D009', 1, 1, 'Garden View', 'available'),
(10, 'D010', 1, 1, 'City View', 'available'),
(11, 'D011', 1, 2, 'Garden View', 'available'),
(12, 'D012', 1, 2, 'City View', 'available'),
(13, 'D013', 1, 2, 'Garden View', 'available'),
(14, 'D014', 1, 2, 'City View', 'available'),
(15, 'D015', 1, 2, 'Garden View', 'available'),
(16, 'D016', 1, 2, 'City View', 'available'),
(17, 'D017', 1, 2, 'Garden View', 'available'),
(18, 'D018', 1, 2, 'City View', 'available'),
(19, 'D019', 1, 2, 'Garden View', 'available'),
(20, 'D020', 1, 2, 'City View', 'available'),
(21, 'D021', 1, 3, 'Garden View', 'available'),
(22, 'D022', 1, 3, 'City View', 'available'),
(23, 'D023', 1, 3, 'Garden View', 'available'),
(24, 'D024', 1, 3, 'City View', 'available'),
(25, 'D025', 1, 3, 'Garden View', 'available'),
(26, 'D026', 1, 3, 'City View', 'available'),
(27, 'D027', 1, 3, 'Garden View', 'available'),
(28, 'D028', 1, 3, 'City View', 'available'),
(29, 'D029', 1, 3, 'Garden View', 'available'),
(30, 'D030', 1, 3, 'City View', 'available'),
(31, 'D031', 1, 4, 'Garden View', 'available'),
(32, 'D032', 1, 4, 'City View', 'available'),
(33, 'D033', 1, 4, 'Garden View', 'available'),
(34, 'D034', 1, 4, 'City View', 'available'),
(35, 'D035', 1, 4, 'Garden View', 'available'),
(36, 'D036', 1, 4, 'City View', 'available'),
(37, 'D037', 1, 4, 'Garden View', 'available'),
(38, 'D038', 1, 4, 'City View', 'available'),
(39, 'D039', 1, 4, 'Garden View', 'available'),
(40, 'D040', 1, 4, 'City View', 'available'),
(41, 'D041', 1, 5, 'Garden View', 'available'),
(42, 'D042', 1, 5, 'City View', 'available'),
(43, 'D043', 1, 5, 'Garden View', 'available'),
(44, 'D044', 1, 5, 'City View', 'available'),
(45, 'D045', 1, 5, 'Garden View', 'available'),
(46, 'D046', 1, 5, 'City View', 'available'),
(47, 'D047', 1, 5, 'Garden View', 'available'),
(48, 'D048', 1, 5, 'City View', 'available'),
(49, 'D049', 1, 5, 'Garden View', 'available'),
(50, 'D050', 1, 5, 'City View', 'available'),
(64, 'E001', 2, 6, 'Ocean View', 'available'),
(65, 'E002', 2, 6, 'Ocean View', 'available'),
(66, 'E003', 2, 6, 'Ocean View', 'available'),
(67, 'E004', 2, 6, 'Ocean View', 'available'),
(68, 'E005', 2, 6, 'Ocean View', 'available'),
(69, 'E006', 2, 6, 'Ocean View', 'available'),
(70, 'E007', 2, 6, 'Ocean View', 'available'),
(71, 'E008', 2, 6, 'Ocean View', 'available'),
(72, 'E009', 2, 6, 'Ocean View', 'available'),
(73, 'E010', 2, 6, 'Ocean View', 'available'),
(74, 'E011', 2, 7, 'Ocean View', 'available'),
(75, 'E012', 2, 7, 'Ocean View', 'available'),
(76, 'E013', 2, 7, 'Ocean View', 'available'),
(77, 'E014', 2, 7, 'Ocean View', 'available'),
(78, 'E015', 2, 7, 'Ocean View', 'available'),
(79, 'E016', 2, 7, 'Ocean View', 'available'),
(80, 'E017', 2, 7, 'Ocean View', 'available'),
(81, 'E018', 2, 7, 'City View', 'available'),
(82, 'E019', 2, 7, 'City View', 'available'),
(83, 'E020', 2, 7, 'City View', 'available'),
(84, 'E021', 2, 8, 'City View', 'available'),
(85, 'E022', 2, 8, 'City View', 'available'),
(86, 'E023', 2, 8, 'City View', 'available'),
(87, 'E024', 2, 8, 'City View', 'available'),
(88, 'E025', 2, 8, 'City View', 'available'),
(89, 'E026', 2, 8, 'City View', 'available'),
(90, 'E027', 2, 8, 'City View', 'available'),
(91, 'E028', 2, 8, 'City View', 'available'),
(92, 'E029', 2, 8, 'City View', 'available'),
(93, 'E030', 2, 8, 'City View', 'available'),
(94, 'E031', 2, 9, 'City View', 'available'),
(95, 'E032', 2, 9, 'City View', 'available'),
(96, 'E033', 2, 9, 'City View', 'available'),
(97, 'E034', 2, 9, 'City View', 'available'),
(98, 'E035', 2, 9, 'Garden View', 'available'),
(99, 'E036', 2, 9, 'Garden View', 'available'),
(100, 'E037', 2, 9, 'Garden View', 'available'),
(101, 'E038', 2, 9, 'Garden View', 'available'),
(102, 'E039', 2, 9, 'Garden View', 'available'),
(103, 'E040', 2, 9, 'Garden View', 'available'),
(104, 'E041', 2, 10, 'Garden View', 'available'),
(105, 'E042', 2, 10, 'Garden View', 'available'),
(106, 'E043', 2, 10, 'Garden View', 'available'),
(107, 'E044', 2, 10, 'Garden View', 'available'),
(108, 'E045', 2, 10, 'Garden View', 'available'),
(109, 'E046', 2, 10, 'Garden View', 'available'),
(110, 'E047', 2, 10, 'Garden View', 'available'),
(111, 'E048', 2, 10, 'Garden View', 'available'),
(112, 'E049', 2, 10, 'Garden View', 'available'),
(113, 'E050', 2, 10, 'Garden View', 'available'),
(127, 'F001', 3, 11, 'Garden View', 'available'),
(128, 'F002', 3, 11, 'Garden View', 'available'),
(129, 'F003', 3, 11, 'Garden View', 'available'),
(130, 'F004', 3, 11, 'Garden View', 'available'),
(131, 'F005', 3, 11, 'Garden View', 'available'),
(132, 'F006', 3, 11, 'Garden View', 'available'),
(133, 'F007', 3, 11, 'Garden View', 'available'),
(134, 'F008', 3, 11, 'Garden View', 'available'),
(135, 'F009', 3, 11, 'Garden View', 'available'),
(136, 'F010', 3, 11, 'Garden View', 'available'),
(137, 'F011', 3, 12, 'Garden View', 'available'),
(138, 'F012', 3, 12, 'Garden View', 'available'),
(139, 'F013', 3, 12, 'Garden View', 'available'),
(140, 'F014', 3, 12, 'Garden View', 'available'),
(141, 'F015', 3, 12, 'Garden View', 'available'),
(142, 'F016', 3, 12, 'Garden View', 'available'),
(143, 'F017', 3, 12, 'Garden View', 'available'),
(144, 'F018', 3, 12, 'Garden View', 'available'),
(145, 'F019', 3, 12, 'Garden View', 'available'),
(146, 'F020', 3, 12, 'Garden View', 'available'),
(147, 'F021', 3, 13, 'Garden View', 'available'),
(148, 'F022', 3, 13, 'Garden View', 'available'),
(149, 'F023', 3, 13, 'Garden View', 'available'),
(150, 'F024', 3, 13, 'Garden View', 'available'),
(151, 'F025', 3, 13, 'Garden View', 'available'),
(152, 'F026', 3, 13, 'Garden View', 'available'),
(153, 'F027', 3, 13, 'Garden View', 'available'),
(154, 'F028', 3, 13, 'Garden View', 'available'),
(155, 'F029', 3, 13, 'Garden View', 'available'),
(156, 'F030', 3, 13, 'Garden View', 'available'),
(157, 'F031', 3, 14, 'Garden View', 'available'),
(158, 'F032', 3, 14, 'Garden View', 'available'),
(159, 'F033', 3, 14, 'Garden View', 'available'),
(160, 'F034', 3, 14, 'Garden View', 'available'),
(161, 'F035', 3, 14, 'Garden View', 'available'),
(162, 'F036', 3, 14, 'Garden View', 'available'),
(163, 'F037', 3, 14, 'Garden View', 'available'),
(164, 'F038', 3, 14, 'Garden View', 'available'),
(165, 'F039', 3, 14, 'Garden View', 'available'),
(166, 'F040', 3, 14, 'Garden View', 'available'),
(167, 'F041', 3, 15, 'Garden View', 'available'),
(168, 'F042', 3, 15, 'Garden View', 'available'),
(169, 'F043', 3, 15, 'Garden View', 'available'),
(170, 'F044', 3, 15, 'Garden View', 'available'),
(171, 'F045', 3, 15, 'Garden View', 'available'),
(172, 'F046', 3, 15, 'Garden View', 'available'),
(173, 'F047', 3, 15, 'Garden View', 'available'),
(174, 'F048', 3, 15, 'Garden View', 'available'),
(175, 'F049', 3, 15, 'Garden View', 'available'),
(176, 'F050', 3, 15, 'Garden View', 'available'),
(190, 'P001', 4, 16, 'Panoramic View', 'available'),
(191, 'P002', 4, 16, 'Panoramic View', 'available'),
(192, 'P003', 4, 16, 'Panoramic View', 'available'),
(193, 'P004', 4, 16, 'Panoramic View', 'available'),
(194, 'P005', 4, 16, 'Panoramic View', 'available'),
(195, 'P006', 4, 16, 'Panoramic View', 'available'),
(196, 'P007', 4, 16, 'Panoramic View', 'available'),
(197, 'P008', 4, 16, 'Panoramic View', 'available'),
(198, 'P009', 4, 16, 'Panoramic View', 'available'),
(199, 'P010', 4, 16, 'Panoramic View', 'available'),
(200, 'P011', 4, 17, 'Panoramic View', 'available'),
(201, 'P012', 4, 17, 'Panoramic View', 'available'),
(202, 'P013', 4, 17, 'Panoramic View', 'available'),
(203, 'P014', 4, 17, 'Panoramic View', 'available'),
(204, 'P015', 4, 17, 'Panoramic View', 'available'),
(205, 'P016', 4, 17, 'Panoramic View', 'available'),
(206, 'P017', 4, 17, 'Panoramic View', 'available'),
(207, 'P018', 4, 17, 'Panoramic View', 'available'),
(208, 'P019', 4, 17, 'Panoramic View', 'available'),
(209, 'P020', 4, 17, 'Panoramic View', 'available'),
(210, 'P021', 4, 18, 'Panoramic View', 'available'),
(211, 'P022', 4, 18, 'Panoramic View', 'available'),
(212, 'P023', 4, 18, 'Panoramic View', 'available'),
(213, 'P024', 4, 18, 'Panoramic View', 'available'),
(214, 'P025', 4, 18, 'Panoramic View', 'available'),
(215, 'P026', 4, 18, 'Panoramic View', 'available'),
(216, 'P027', 4, 18, 'Panoramic View', 'available'),
(217, 'P028', 4, 18, 'Panoramic View', 'available'),
(218, 'P029', 4, 18, 'Panoramic View', 'available'),
(219, 'P030', 4, 18, 'Panoramic View', 'available'),
(220, 'P031', 4, 19, 'Panoramic View', 'available'),
(221, 'P032', 4, 19, 'Panoramic View', 'available'),
(222, 'P033', 4, 19, 'Panoramic View', 'available'),
(223, 'P034', 4, 19, 'Panoramic View', 'available'),
(224, 'P035', 4, 19, 'Panoramic View', 'available'),
(225, 'P036', 4, 19, 'Panoramic View', 'available'),
(226, 'P037', 4, 19, 'Panoramic View', 'available'),
(227, 'P038', 4, 19, 'Panoramic View', 'available'),
(228, 'P039', 4, 19, 'Panoramic View', 'available'),
(229, 'P040', 4, 19, 'Panoramic View', 'available'),
(230, 'P041', 4, 20, 'Panoramic View', 'available'),
(231, 'P042', 4, 20, 'Panoramic View', 'available'),
(232, 'P043', 4, 20, 'Panoramic View', 'available'),
(233, 'P044', 4, 20, 'Panoramic View', 'available'),
(234, 'P045', 4, 20, 'Panoramic View', 'available'),
(235, 'P046', 4, 20, 'Panoramic View', 'available'),
(236, 'P047', 4, 20, 'Panoramic View', 'available'),
(237, 'P048', 4, 20, 'Panoramic View', 'available'),
(238, 'P049', 4, 20, 'Panoramic View', 'available'),
(239, 'P050', 4, 20, 'Panoramic View', 'available'),
(240, 'T001', 3, 4, 'City View', 'available');

-- --------------------------------------------------------

--
-- Table structure for table `room_assignments`
--

DROP TABLE IF EXISTS `room_assignments`;
CREATE TABLE IF NOT EXISTS `room_assignments` (
  `id` int NOT NULL AUTO_INCREMENT,
  `booking_ref` varchar(30) NOT NULL,
  `room_id` int NOT NULL,
  `assigned_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `room_id` (`room_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `room_availability`
--

DROP TABLE IF EXISTS `room_availability`;
CREATE TABLE IF NOT EXISTS `room_availability` (
  `id` int NOT NULL AUTO_INCREMENT,
  `room_type_id` int NOT NULL,
  `date` date NOT NULL,
  `available` tinyint(1) DEFAULT '1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `rt_date` (`room_type_id`,`date`)
) ENGINE=InnoDB AUTO_INCREMENT=173 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `room_availability`
--

INSERT INTO `room_availability` (`id`, `room_type_id`, `date`, `available`) VALUES
(1, 4, '2026-03-28', 1),
(2, 3, '2026-03-28', 1),
(3, 2, '2026-03-28', 1),
(4, 1, '2026-03-28', 1),
(5, 4, '2026-03-29', 1),
(6, 3, '2026-03-29', 1),
(7, 2, '2026-03-29', 1),
(8, 1, '2026-03-29', 1),
(9, 4, '2026-03-30', 1),
(10, 3, '2026-03-30', 1),
(11, 2, '2026-03-30', 1),
(12, 1, '2026-03-30', 0),
(13, 4, '2026-03-31', 0),
(14, 3, '2026-03-31', 1),
(15, 2, '2026-03-31', 1),
(16, 1, '2026-03-31', 1),
(17, 4, '2026-04-01', 1),
(18, 3, '2026-04-01', 1),
(19, 2, '2026-04-01', 1),
(20, 1, '2026-04-01', 0),
(21, 4, '2026-04-02', 1),
(22, 3, '2026-04-02', 1),
(23, 2, '2026-04-02', 1),
(24, 1, '2026-04-02', 1),
(25, 4, '2026-04-03', 1),
(26, 3, '2026-04-03', 1),
(27, 2, '2026-04-03', 1),
(28, 1, '2026-04-03', 0),
(29, 4, '2026-04-04', 1),
(30, 3, '2026-04-04', 1),
(31, 2, '2026-04-04', 1),
(32, 1, '2026-04-04', 1),
(33, 4, '2026-04-05', 1),
(34, 3, '2026-04-05', 1),
(35, 2, '2026-04-05', 1),
(36, 1, '2026-04-05', 1),
(37, 4, '2026-04-06', 1),
(38, 3, '2026-04-06', 1),
(39, 2, '2026-04-06', 1),
(40, 1, '2026-04-06', 1),
(41, 4, '2026-04-07', 1),
(42, 3, '2026-04-07', 1),
(43, 2, '2026-04-07', 1),
(44, 1, '2026-04-07', 1),
(45, 4, '2026-04-08', 1),
(46, 3, '2026-04-08', 0),
(47, 2, '2026-04-08', 1),
(48, 1, '2026-04-08', 0),
(49, 4, '2026-04-09', 1),
(50, 3, '2026-04-09', 0),
(51, 2, '2026-04-09', 1),
(52, 1, '2026-04-09', 1),
(53, 4, '2026-04-10', 1),
(54, 3, '2026-04-10', 1),
(55, 2, '2026-04-10', 1),
(56, 1, '2026-04-10', 1),
(57, 4, '2026-04-11', 1),
(58, 3, '2026-04-11', 1),
(59, 2, '2026-04-11', 1),
(60, 1, '2026-04-11', 1),
(61, 4, '2026-04-12', 1),
(62, 3, '2026-04-12', 1),
(63, 2, '2026-04-12', 1),
(64, 1, '2026-04-12', 0),
(65, 4, '2026-04-13', 1),
(66, 3, '2026-04-13', 0),
(67, 2, '2026-04-13', 1),
(68, 1, '2026-04-13', 1),
(69, 4, '2026-04-14', 1),
(70, 3, '2026-04-14', 1),
(71, 2, '2026-04-14', 1),
(72, 1, '2026-04-14', 0),
(73, 4, '2026-04-15', 1),
(74, 3, '2026-04-15', 1),
(75, 2, '2026-04-15', 0),
(76, 1, '2026-04-15', 1),
(77, 4, '2026-04-16', 1),
(78, 3, '2026-04-16', 1),
(79, 2, '2026-04-16', 0),
(80, 1, '2026-04-16', 1),
(81, 4, '2026-04-17', 1),
(82, 3, '2026-04-17', 1),
(83, 2, '2026-04-17', 1),
(84, 1, '2026-04-17', 1),
(85, 4, '2026-04-18', 1),
(86, 3, '2026-04-18', 1),
(87, 2, '2026-04-18', 1),
(88, 1, '2026-04-18', 1),
(89, 4, '2026-04-19', 1),
(90, 3, '2026-04-19', 1),
(91, 2, '2026-04-19', 1),
(92, 1, '2026-04-19', 1),
(93, 4, '2026-04-20', 0),
(94, 3, '2026-04-20', 0),
(95, 2, '2026-04-20', 1),
(96, 1, '2026-04-20', 1),
(97, 4, '2026-04-21', 1),
(98, 3, '2026-04-21', 1),
(99, 2, '2026-04-21', 1),
(100, 1, '2026-04-21', 1),
(101, 4, '2026-04-22', 0),
(102, 3, '2026-04-22', 1),
(103, 2, '2026-04-22', 1),
(104, 1, '2026-04-22', 1),
(105, 4, '2026-04-23', 1),
(106, 3, '2026-04-23', 1),
(107, 2, '2026-04-23', 1),
(108, 1, '2026-04-23', 1),
(109, 4, '2026-04-24', 1),
(110, 3, '2026-04-24', 1),
(111, 2, '2026-04-24', 1),
(112, 1, '2026-04-24', 1),
(113, 4, '2026-04-25', 1),
(114, 3, '2026-04-25', 1),
(115, 2, '2026-04-25', 1),
(116, 1, '2026-04-25', 1),
(117, 4, '2026-04-26', 1),
(118, 3, '2026-04-26', 1),
(119, 2, '2026-04-26', 1),
(120, 1, '2026-04-26', 1),
(121, 4, '2026-04-27', 0),
(122, 3, '2026-04-27', 1),
(123, 2, '2026-04-27', 1),
(124, 1, '2026-04-27', 1),
(125, 4, '2026-05-02', 1),
(126, 3, '2026-05-02', 1),
(127, 2, '2026-05-02', 1),
(128, 1, '2026-05-02', 1),
(129, 4, '2026-05-07', 1),
(130, 3, '2026-05-07', 0),
(131, 2, '2026-05-07', 1),
(132, 1, '2026-05-07', 1),
(133, 4, '2026-05-12', 1),
(134, 3, '2026-05-12', 1),
(135, 2, '2026-05-12', 1),
(136, 1, '2026-05-12', 1),
(137, 4, '2026-05-17', 0),
(138, 3, '2026-05-17', 1),
(139, 2, '2026-05-17', 1),
(140, 1, '2026-05-17', 1),
(141, 4, '2026-05-22', 1),
(142, 3, '2026-05-22', 1),
(143, 2, '2026-05-22', 1),
(144, 1, '2026-05-22', 1),
(145, 4, '2026-05-27', 1),
(146, 3, '2026-05-27', 1),
(147, 2, '2026-05-27', 1),
(148, 1, '2026-05-27', 1),
(149, 4, '2026-06-01', 1),
(150, 3, '2026-06-01', 1),
(151, 2, '2026-06-01', 0),
(152, 1, '2026-06-01', 1),
(153, 4, '2026-06-06', 1),
(154, 3, '2026-06-06', 1),
(155, 2, '2026-06-06', 1),
(156, 1, '2026-06-06', 1),
(157, 4, '2026-06-11', 1),
(158, 3, '2026-06-11', 1),
(159, 2, '2026-06-11', 1),
(160, 1, '2026-06-11', 1),
(161, 4, '2026-06-16', 1),
(162, 3, '2026-06-16', 1),
(163, 2, '2026-06-16', 0),
(164, 1, '2026-06-16', 1),
(165, 4, '2026-06-21', 1),
(166, 3, '2026-06-21', 0),
(167, 2, '2026-06-21', 1),
(168, 1, '2026-06-21', 1),
(169, 4, '2026-06-25', 1),
(170, 3, '2026-06-25', 1),
(171, 2, '2026-06-25', 0),
(172, 1, '2026-06-25', 1);

-- --------------------------------------------------------

--
-- Table structure for table `room_cart`
--

DROP TABLE IF EXISTS `room_cart`;
CREATE TABLE IF NOT EXISTS `room_cart` (
  `id` int NOT NULL AUTO_INCREMENT,
  `session_key` varchar(80) NOT NULL,
  `room_type_id` int NOT NULL,
  `quantity` int DEFAULT '1',
  `check_in` date NOT NULL,
  `check_out` date NOT NULL,
  `guests` int DEFAULT '2',
  `added_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `room_type_id` (`room_type_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `room_facilities`
--

DROP TABLE IF EXISTS `room_facilities`;
CREATE TABLE IF NOT EXISTS `room_facilities` (
  `id` int NOT NULL AUTO_INCREMENT,
  `room_type_id` int NOT NULL,
  `name` varchar(100) NOT NULL,
  `icon` varchar(60) DEFAULT NULL,
  `sort_order` int DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `room_type_id` (`room_type_id`)
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `room_facilities`
--

INSERT INTO `room_facilities` (`id`, `room_type_id`, `name`, `icon`, `sort_order`) VALUES
(1, 1, 'King Size Bed', 'fas fa-bed', 1),
(2, 1, 'Free WiFi', 'fas fa-wifi', 2),
(3, 1, 'City View', 'fas fa-city', 3),
(4, 1, 'Room Service', 'fas fa-concierge-bell', 4),
(5, 1, 'Smart TV', 'fas fa-tv', 5),
(6, 1, 'Mini Bar', 'fas fa-wine-glass', 6),
(7, 2, 'King Bed', 'fas fa-bed', 1),
(8, 2, 'Executive Lounge', 'fas fa-door-open', 2),
(9, 2, 'Breakfast Included', 'fas fa-utensils', 3),
(10, 2, 'Butler Service', 'fas fa-user-tie', 4),
(11, 2, 'Work Desk', 'fas fa-laptop', 5),
(12, 2, 'Premium Bar', 'fas fa-wine-bottle', 6),
(13, 3, 'Twin & Double Beds', 'fas fa-bed', 1),
(14, 3, 'Kids Play Area', 'fas fa-child', 2),
(15, 3, 'Living Room', 'fas fa-couch', 3),
(16, 3, 'Kitchenette', 'fas fa-utensils', 4),
(17, 3, 'Games & Toys', 'fas fa-dice', 5),
(18, 3, 'Breakfast Included', 'fas fa-utensils', 6),
(19, 4, 'King Bed + Jacuzzi', 'fas fa-hot-tub', 1),
(20, 4, 'Private Butler', 'fas fa-user-tie', 2),
(21, 4, 'Private Terrace', 'fas fa-sun', 3),
(22, 4, 'Grand Piano', 'fas fa-music', 4),
(23, 4, 'Home Cinema', 'fas fa-film', 5),
(24, 4, 'Personal Chef', 'fas fa-utensils', 6);

-- --------------------------------------------------------

--
-- Table structure for table `room_images`
--

DROP TABLE IF EXISTS `room_images`;
CREATE TABLE IF NOT EXISTS `room_images` (
  `id` int NOT NULL AUTO_INCREMENT,
  `room_type_id` int NOT NULL,
  `media_type` enum('image','video') DEFAULT 'image',
  `image_url` varchar(600) NOT NULL,
  `is_primary` tinyint(1) DEFAULT '0',
  `sort_order` int DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `room_type_id` (`room_type_id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `room_images`
--

INSERT INTO `room_images` (`id`, `room_type_id`, `media_type`, `image_url`, `is_primary`, `sort_order`) VALUES
(1, 1, 'image', 'https://cdn.prod.website-files.com/6373a5ea055283f3e21cf181/6380bac42a93aa5b1f6376bd_Deluxe-Duplex-Suite-2-Bedroom-3.jpg', 1, 0),
(2, 1, 'image', 'https://images.unsplash.com/photo-1540518614846-7eded433c457?w=900&q=80', 0, 1),
(3, 1, 'image', 'https://images.unsplash.com/photo-1584132967334-10e028bd69f7?w=900&q=80', 0, 2),
(4, 2, 'image', 'https://gokulamhotels.com/gokulamgrandbangalore/images/accommodation/etr/executive-club-room-slide-2.jpg', 1, 0),
(5, 2, 'image', 'https://images.unsplash.com/photo-1578683010236-d716f9a3f461?w=900&q=80', 0, 1),
(6, 3, 'image', 'https://st.hzcdn.com/simgs/d411bd4206e86ee4_14-6092/home-design.jpg', 1, 0),
(7, 3, 'image', 'https://images.unsplash.com/photo-1566195992011-5f6b21e539aa?w=900&q=80', 0, 1),
(8, 4, 'image', 'https://img.grandlisboa.com/assets/gl-accomodation-detail-presidential-suite-hero-dt-01.jpg?x-oss-process=image/quality,q_70', 1, 0),
(9, 4, 'image', 'https://images.unsplash.com/photo-1630660664869-c9d3cc676880?w=900&q=80', 0, 1);

-- --------------------------------------------------------

--
-- Table structure for table `room_ratings`
--

DROP TABLE IF EXISTS `room_ratings`;
CREATE TABLE IF NOT EXISTS `room_ratings` (
  `id` int NOT NULL AUTO_INCREMENT,
  `room_type_id` int NOT NULL,
  `user_id` int DEFAULT NULL,
  `booking_ref` varchar(30) DEFAULT NULL,
  `guest_name` varchar(100) DEFAULT NULL,
  `rating` tinyint DEFAULT '5',
  `title` varchar(200) DEFAULT NULL,
  `review` text,
  `is_verified` tinyint(1) DEFAULT '0',
  `is_approved` tinyint(1) DEFAULT '0',
  `helpful_count` int DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `room_type_id` (`room_type_id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `room_ratings`
--

INSERT INTO `room_ratings` (`id`, `room_type_id`, `user_id`, `booking_ref`, `guest_name`, `rating`, `title`, `review`, `is_verified`, `is_approved`, `helpful_count`, `created_at`) VALUES
(1, 1, NULL, NULL, 'Sarah M.', 5, 'Absolutely perfect', 'The room was impeccable. Stunning views and exceptional service.', 1, 1, 1, '2026-03-28 05:18:15'),
(2, 1, NULL, NULL, 'David L.', 4, 'Great value stay', 'Clean, beautifully appointed. Would definitely return.', 1, 1, 0, '2026-03-28 05:18:15'),
(3, 2, NULL, NULL, 'Emma R.', 5, 'Pure luxury', 'Executive lounge access was a game-changer. Worth every penny.', 1, 1, 0, '2026-03-28 05:18:15'),
(4, 3, NULL, NULL, 'The Kumar Family', 5, 'Kids had a blast!', 'Spacious, thoughtfully designed. Kids loved the play corner.', 1, 1, 0, '2026-03-28 05:18:15'),
(5, 4, NULL, NULL, 'Sheikh A.', 5, 'Beyond expectations', 'The Presidential Suite is extraordinary. Butler was amazing.', 1, 1, 1, '2026-03-28 05:18:15'),
(6, 2, NULL, NULL, 'Michael T.', 5, 'Best business trip ever', 'The work desk setup and lounge were perfect for my meetings.', 1, 1, 0, '2026-03-28 05:18:15'),
(7, 5, 1, NULL, 'Admin User', 5, 'good', 'i love this room and hotel.', 0, 1, 3, '2026-03-29 12:23:17');

-- --------------------------------------------------------

--
-- Table structure for table `room_types`
--

DROP TABLE IF EXISTS `room_types`;
CREATE TABLE IF NOT EXISTS `room_types` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `description` text,
  `price_usd` decimal(10,2) NOT NULL,
  `max_guests` int DEFAULT '2',
  `has_breakfast` tinyint(1) DEFAULT '0',
  `avg_rating` decimal(3,2) DEFAULT '4.50',
  `review_count` int DEFAULT '0',
  `sort_order` int DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `room_types`
--

INSERT INTO `room_types` (`id`, `name`, `slug`, `description`, `price_usd`, `max_guests`, `has_breakfast`, `avg_rating`, `review_count`, `sort_order`, `created_at`) VALUES
(1, 'Deluxe Room', 'deluxe', 'Elegant room with garden or city views, premium bedding and marble bathroom.', 120.00, 2, 0, 4.60, 127, 1, '2026-03-28 05:18:15'),
(2, 'Exclusive Room', 'exclusive', 'Premium room with executive lounge access, complimentary minibar and butler.', 300.00, 3, 1, 4.75, 89, 2, '2026-03-28 05:18:15'),
(3, 'Family Room', 'family', 'Spacious room designed for families with separate sleeping areas and kids corner.', 250.00, 5, 1, 4.55, 64, 3, '2026-03-28 05:18:15'),
(4, 'Presidential Suite', 'presidential', 'The pinnacle of luxury — private jacuzzi, panoramic views, full butler service.', 600.00, 4, 1, 4.92, 42, 4, '2026-03-28 05:18:15'),
(5, 'Kids Suites', 'kids-suites', 'Awesome kids suite', 500.00, 4, 1, 5.00, 1, 0, '2026-03-29 01:23:11');

-- --------------------------------------------------------

--
-- Table structure for table `services_catalog`
--

DROP TABLE IF EXISTS `services_catalog`;
CREATE TABLE IF NOT EXISTS `services_catalog` (
  `id` int NOT NULL AUTO_INCREMENT,
  `icon` varchar(20) DEFAULT NULL,
  `category` varchar(100) NOT NULL,
  `name` varchar(200) NOT NULL,
  `image_url` varchar(600) DEFAULT NULL,
  `description` text,
  `hours` varchar(120) DEFAULT NULL,
  `cta_link` varchar(300) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `sort_order` int DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `spa_appointments`
--

DROP TABLE IF EXISTS `spa_appointments`;
CREATE TABLE IF NOT EXISTS `spa_appointments` (
  `id` int NOT NULL AUTO_INCREMENT,
  `ref` varchar(30) NOT NULL,
  `user_id` int DEFAULT NULL,
  `treatment` varchar(200) NOT NULL,
  `therapist` varchar(100) DEFAULT NULL,
  `date` date NOT NULL,
  `time` varchar(10) NOT NULL,
  `duration_min` int DEFAULT '60',
  `price_usd` decimal(10,2) DEFAULT '0.00',
  `guests` int DEFAULT '1',
  `name` varchar(200) NOT NULL,
  `email` varchar(191) NOT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `requests` text,
  `status` enum('confirmed','cancelled','completed') DEFAULT 'confirmed',
  `admin_status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `admin_note` text,
  `decided_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ref` (`ref`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE IF NOT EXISTS `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(200) NOT NULL,
  `email` varchar(191) NOT NULL,
  `google_id` varchar(255) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `address` text,
  `profile_img` varchar(500) DEFAULT NULL,
  `id_type` varchar(50) DEFAULT 'passport',
  `id_number` varchar(100) DEFAULT NULL,
  `dob` date DEFAULT NULL,
  `country` varchar(100) DEFAULT NULL,
  `gender` varchar(20) DEFAULT NULL,
  `language` varchar(10) DEFAULT 'en',
  `currency` varchar(10) DEFAULT 'USD',
  `role` varchar(20) DEFAULT 'user',
  `is_admin` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `reset_otp` varchar(10) DEFAULT NULL,
  `reset_otp_expiry` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `google_id` (`google_id`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `google_id`, `password`, `phone`, `address`, `profile_img`, `id_type`, `id_number`, `dob`, `country`, `gender`, `language`, `currency`, `role`, `is_admin`, `created_at`, `updated_at`, `reset_otp`, `reset_otp_expiry`) VALUES
(1, 'Admin User', 'admin@royalevista.com', NULL, '$2y$10$jnZyKeaH3UOLdehuAF2l6eliE9pUNutFHiBO5EzrjMoaykBA/Ypqi', '', '', 'avatar_1_1774739560.jpg', 'passport', NULL, NULL, '', NULL, 'en', 'USD', 'admin', 1, '2026-03-28 05:18:15', '2026-04-04 09:05:29', '176011', '2026-04-04 09:20:29'),
(2, 'John Smith', 'john@example.com', NULL, '$2y$10$jnZyKeaH3UOLdehuAF2l6eliE9pUNutFHiBO5EzrjMoaykBA/Ypqi', '+1-555-0102', NULL, 'avatar_2_1774707234.jpg', 'passport', NULL, NULL, 'USA', NULL, 'hi', 'USD', 'user', 0, '2026-03-28 05:18:15', '2026-03-28 14:14:31', NULL, NULL),
(3, 'Priya Patel', 'priya@example.com', NULL, '$2y$10$jnZyKeaH3UOLdehuAF2l6eliE9pUNutFHiBO5EzrjMoaykBA/Ypqi', '+91-98765-43210', NULL, NULL, 'passport', NULL, NULL, 'India', NULL, 'en', 'USD', 'user', 0, '2026-03-28 05:18:15', '2026-03-28 05:18:15', NULL, NULL),
(4, 'James Wilson', 'james@example.com', NULL, '$2y$10$jnZyKeaH3UOLdehuAF2l6eliE9pUNutFHiBO5EzrjMoaykBA/Ypqi', '+44-7700-900123', NULL, NULL, 'passport', NULL, NULL, 'UK', NULL, 'en', 'USD', 'user', 0, '2026-03-28 05:18:15', '2026-03-28 05:18:15', NULL, NULL),
(5, 'Test Agent', 'agent@test.com', NULL, '$2y$10$htqEkawFgdjsrZyqKJ72u.dsAi/4O5RxjhedO9ZmUuwz0h/sZkypC', '', NULL, NULL, 'passport', NULL, NULL, '', NULL, 'en', 'USD', 'user', 0, '2026-03-29 01:24:40', '2026-03-29 01:24:40', NULL, NULL),
(6, 'Test User', 'test@test.com', NULL, '$2y$10$PeAK.hEHmrYaWyv5wlSENex6tG7LkZtleJdJW//MemGD1hEKtx8Je', '', NULL, NULL, 'passport', NULL, NULL, '', NULL, 'en', 'USD', 'user', 0, '2026-03-29 05:33:13', '2026-03-29 05:33:13', NULL, NULL),
(7, 'Ayaan Khalifa', 'ayaankhalifa4432@gmail.com', NULL, '$2y$10$PO3nn64pVA5j3r40NESFSu5g6We/z1S7ZHqXFXeDx2HfarCLAhUDK', '', '', 'avatar_7_1775552757.jpg', 'passport', NULL, NULL, '', NULL, 'ar', 'INR', 'admin', 1, '2026-03-31 07:36:05', '2026-04-18 06:56:53', NULL, NULL),
(8, 'Radhey', 'dcu3071@gmail.com', NULL, '$2y$10$zElgdxKIFZ8gpT35CFWo/OZvqnAVqJSJ5iaEwkoKx3PrQWxGO.N36', '08799097673', NULL, NULL, 'passport', NULL, NULL, 'India', NULL, 'en', 'USD', 'user', 0, '2026-03-31 07:58:36', '2026-03-31 08:06:19', NULL, NULL),
(9, 'Allarakhu Khalifa', 'allarakhu1212@gmail.com', NULL, '$2y$10$ShI06t6Ias3nR8fdKvj1FeRxf5zLxua3A2njxfTZwEjDNNGJCDjdy', '08799097673', NULL, NULL, 'passport', NULL, NULL, 'India', NULL, 'en', 'USD', 'user', 0, '2026-03-31 11:27:32', '2026-03-31 11:27:32', NULL, NULL),
(10, 'Ayaan Khalifa', 'ayaankhalifa2006@gmail.com', NULL, '$2y$10$vsMA0eVk9DHo4pEE3DxDE.RnkvsZRIsOts2eIhk3OXhjw62RKzRCS', '08799097673', NULL, NULL, 'passport', NULL, NULL, 'India', NULL, 'en', 'USD', 'user', 0, '2026-03-31 15:56:53', '2026-03-31 15:58:12', NULL, NULL),
(11, 'priyanka', 'priyanka7434952889@gmail.com', NULL, '$2y$10$2/1KhawekZSku/dlLiBfl.wq6eG4bJwDrYVQkMrUKFOCBWng.sCAa', '034299097673', NULL, NULL, 'passport', NULL, NULL, 'India', NULL, 'en', 'USD', 'user', 0, '2026-04-02 04:54:18', '2026-04-02 05:39:05', NULL, NULL),
(12, 'Ismail laher', 'isulaher@gmail.com', NULL, '$2y$10$upBJPi3fqydfBdd9oJTxX.TJ.YmdXP5Lx4TItmHDnXwIe.KNM58DC', '08799097673', NULL, NULL, 'passport', NULL, NULL, 'India', NULL, 'en', 'USD', 'user', 0, '2026-04-02 05:40:09', '2026-04-02 05:40:09', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `user_memberships`
--

DROP TABLE IF EXISTS `user_memberships`;
CREATE TABLE IF NOT EXISTS `user_memberships` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `membership_id` int NOT NULL,
  `member_number` varchar(50) NOT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `status` enum('active','expired','cancelled') DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `member_number` (`member_number`),
  KEY `user_id` (`user_id`),
  KEY `membership_id` (`membership_id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `user_memberships`
--

INSERT INTO `user_memberships` (`id`, `user_id`, `membership_id`, `member_number`, `expires_at`, `status`, `created_at`) VALUES
(1, 2, 1, 'SILVER-JOHN-001', '2026-09-24 05:18:15', 'cancelled', '2026-03-28 05:18:15'),
(2, 3, 2, 'GOLD-PRIYA-001', '2027-03-28 05:18:15', 'active', '2026-03-28 05:18:15'),
(3, 4, 3, 'PLAT-JAMES-001', '2027-03-28 05:18:15', 'active', '2026-03-28 05:18:15'),
(4, 2, 2, 'RV-9995A3AC', '2027-03-28 22:06:21', 'active', '2026-03-29 03:36:21');

-- --------------------------------------------------------

--
-- Table structure for table `virtual_tours`
--

DROP TABLE IF EXISTS `virtual_tours`;
CREATE TABLE IF NOT EXISTS `virtual_tours` (
  `id` int NOT NULL AUTO_INCREMENT,
  `room_type_id` int DEFAULT NULL,
  `title` varchar(200) DEFAULT NULL,
  `embed_url` varchar(600) DEFAULT NULL,
  `thumb` varchar(600) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `room_type_id` (`room_type_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wishlists`
--

DROP TABLE IF EXISTS `wishlists`;
CREATE TABLE IF NOT EXISTS `wishlists` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `room_type_id` int NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uw` (`user_id`,`room_type_id`),
  KEY `room_type_id` (`room_type_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `wishlists`
--

INSERT INTO `wishlists` (`id`, `user_id`, `room_type_id`, `created_at`) VALUES
(1, 1, 1, '2026-03-29 12:23:41');

--
-- Constraints for dumped tables
--

--
-- Constraints for table `booked_rooms`
--
ALTER TABLE `booked_rooms`
  ADD CONSTRAINT `booked_rooms_ibfk_1` FOREIGN KEY (`booking_ref`) REFERENCES `bookings` (`booking_ref`) ON DELETE CASCADE,
  ADD CONSTRAINT `booked_rooms_ibfk_2` FOREIGN KEY (`room_type_id`) REFERENCES `room_types` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `bookings`
--
ALTER TABLE `bookings`
  ADD CONSTRAINT `bookings_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `chat_messages`
--
ALTER TABLE `chat_messages`
  ADD CONSTRAINT `chat_messages_ibfk_1` FOREIGN KEY (`session_id`) REFERENCES `chat_sessions` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `chat_sessions`
--
ALTER TABLE `chat_sessions`
  ADD CONSTRAINT `chat_sessions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `concierge_requests`
--
ALTER TABLE `concierge_requests`
  ADD CONSTRAINT `concierge_requests_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `dining_reservations`
--
ALTER TABLE `dining_reservations`
  ADD CONSTRAINT `dining_reservations_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `event_bookings`
--
ALTER TABLE `event_bookings`
  ADD CONSTRAINT `event_bookings_ibfk_1` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `event_bookings_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `gallery_images`
--
ALTER TABLE `gallery_images`
  ADD CONSTRAINT `gallery_images_ibfk_1` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `gift_cards`
--
ALTER TABLE `gift_cards`
  ADD CONSTRAINT `gift_cards_ibfk_1` FOREIGN KEY (`purchased_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `gift_card_usage`
--
ALTER TABLE `gift_card_usage`
  ADD CONSTRAINT `gift_card_usage_ibfk_1` FOREIGN KEY (`card_id`) REFERENCES `gift_cards` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `loyalty_points`
--
ALTER TABLE `loyalty_points`
  ADD CONSTRAINT `loyalty_points_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `loyalty_transactions`
--
ALTER TABLE `loyalty_transactions`
  ADD CONSTRAINT `loyalty_transactions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `membership_features`
--
ALTER TABLE `membership_features`
  ADD CONSTRAINT `membership_features_ibfk_1` FOREIGN KEY (`membership_id`) REFERENCES `memberships` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `price_overrides`
--
ALTER TABLE `price_overrides`
  ADD CONSTRAINT `price_overrides_ibfk_1` FOREIGN KEY (`room_type_id`) REFERENCES `room_types` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `property_amenities`
--
ALTER TABLE `property_amenities`
  ADD CONSTRAINT `property_amenities_ibfk_1` FOREIGN KEY (`property_id`) REFERENCES `properties` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `property_images`
--
ALTER TABLE `property_images`
  ADD CONSTRAINT `property_images_ibfk_1` FOREIGN KEY (`property_id`) REFERENCES `properties` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `review_images`
--
ALTER TABLE `review_images`
  ADD CONSTRAINT `review_images_ibfk_1` FOREIGN KEY (`review_id`) REFERENCES `room_ratings` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `review_media`
--
ALTER TABLE `review_media`
  ADD CONSTRAINT `review_media_ibfk_1` FOREIGN KEY (`review_id`) REFERENCES `room_ratings` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `rooms`
--
ALTER TABLE `rooms`
  ADD CONSTRAINT `rooms_ibfk_1` FOREIGN KEY (`room_type_id`) REFERENCES `room_types` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `room_assignments`
--
ALTER TABLE `room_assignments`
  ADD CONSTRAINT `room_assignments_ibfk_1` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `room_availability`
--
ALTER TABLE `room_availability`
  ADD CONSTRAINT `room_availability_ibfk_1` FOREIGN KEY (`room_type_id`) REFERENCES `room_types` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `room_cart`
--
ALTER TABLE `room_cart`
  ADD CONSTRAINT `room_cart_ibfk_1` FOREIGN KEY (`room_type_id`) REFERENCES `room_types` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `room_facilities`
--
ALTER TABLE `room_facilities`
  ADD CONSTRAINT `room_facilities_ibfk_1` FOREIGN KEY (`room_type_id`) REFERENCES `room_types` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `room_images`
--
ALTER TABLE `room_images`
  ADD CONSTRAINT `room_images_ibfk_1` FOREIGN KEY (`room_type_id`) REFERENCES `room_types` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `room_ratings`
--
ALTER TABLE `room_ratings`
  ADD CONSTRAINT `room_ratings_ibfk_1` FOREIGN KEY (`room_type_id`) REFERENCES `room_types` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `room_ratings_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `spa_appointments`
--
ALTER TABLE `spa_appointments`
  ADD CONSTRAINT `spa_appointments_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `user_memberships`
--
ALTER TABLE `user_memberships`
  ADD CONSTRAINT `user_memberships_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_memberships_ibfk_2` FOREIGN KEY (`membership_id`) REFERENCES `memberships` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `virtual_tours`
--
ALTER TABLE `virtual_tours`
  ADD CONSTRAINT `virtual_tours_ibfk_1` FOREIGN KEY (`room_type_id`) REFERENCES `room_types` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `wishlists`
--
ALTER TABLE `wishlists`
  ADD CONSTRAINT `wishlists_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `wishlists_ibfk_2` FOREIGN KEY (`room_type_id`) REFERENCES `room_types` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
