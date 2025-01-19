-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jan 19, 2025 at 04:23 PM
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
-- Database: `gym_managementdb`
--

-- --------------------------------------------------------

--
-- Table structure for table `announcements`
--

CREATE TABLE `announcements` (
  `id` int(11) NOT NULL,
  `message` text NOT NULL,
  `applied_date` date NOT NULL,
  `applied_time` time NOT NULL,
  `announcement_type` enum('administrative','activity') NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `announcements`
--

INSERT INTO `announcements` (`id`, `message`, `applied_date`, `applied_time`, `announcement_type`, `is_active`, `created_at`) VALUES
(0, 'hehe', '2024-11-28', '18:35:00', 'activity', 1, '2024-11-28 10:35:48'),
(0, 'haha', '2024-11-28', '18:36:00', 'administrative', 1, '2024-11-28 10:36:12');

-- --------------------------------------------------------

--
-- Table structure for table `attendance`
--

CREATE TABLE `attendance` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `date` date NOT NULL,
  `time_in` time DEFAULT NULL,
  `time_out` time DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('checked_in','checked_out','missed') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `attendance`
--

INSERT INTO `attendance` (`id`, `user_id`, `date`, `time_in`, `time_out`, `created_at`, `status`) VALUES
(36, 39, '2025-01-19', '14:53:24', '14:53:26', '2025-01-19 13:53:24', 'checked_out');

-- --------------------------------------------------------

--
-- Table structure for table `attendance_history`
--

CREATE TABLE `attendance_history` (
  `id` int(11) NOT NULL,
  `attendance_id` int(11) NOT NULL,
  `time_in` time DEFAULT NULL,
  `time_out` time DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('checked_in','checked_out','missed') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `attendance_history`
--

INSERT INTO `attendance_history` (`id`, `attendance_id`, `time_in`, `time_out`, `created_at`, `status`) VALUES
(51, 36, '14:53:24', NULL, '2025-01-19 13:53:24', 'checked_in'),
(52, 36, '14:53:24', '14:53:26', '2025-01-19 13:53:26', 'checked_out');

-- --------------------------------------------------------

--
-- Table structure for table `coach_program_types`
--

CREATE TABLE `coach_program_types` (
  `id` int(11) NOT NULL,
  `coach_id` int(11) NOT NULL,
  `program_id` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `description` text DEFAULT NULL,
  `status` enum('active','inactive','pending','') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `coach_program_types`
--

INSERT INTO `coach_program_types` (`id`, `coach_id`, `program_id`, `price`, `description`, `status`, `created_at`, `updated_at`) VALUES
(4, 35, 3, 500.00, 'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.', 'inactive', '2024-12-03 14:35:24', '2024-12-05 01:04:59'),
(10, 36, 3, 450.00, 'Lorem ipsum odor amet, consectetuer adipiscing elit. Sollicitudin donec dolor sagittis per egestas montes tellus vel. Cursus imperdiet faucibus habitasse finibus accumsan pellentesque eget. Senectus nam integer laoreet ornare cursus metus. Lobortis aliquet cras himenaeos neque lectus pharetra condimentum ante. Auctor erat mattis class metus mollis lacus ex. Euismod hac habitant ac aenean mauris. Mus eros vestibulum interdum fermentum tempor quisque. Ante porttitor maecenas ornare ex vel fringilla euismod lacus bibendum.', 'active', '2024-12-03 16:14:34', '2024-12-03 16:14:34'),
(11, 35, 3, 150.00, '', 'active', '2025-01-19 14:07:34', '2025-01-19 14:07:34');

-- --------------------------------------------------------

--
-- Table structure for table `duration_types`
--

CREATE TABLE `duration_types` (
  `id` int(11) NOT NULL,
  `type_name` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `duration_types`
--

INSERT INTO `duration_types` (`id`, `type_name`) VALUES
(1, 'days'),
(2, 'months'),
(3, 'year');

-- --------------------------------------------------------

--
-- Table structure for table `gallery_images`
--

CREATE TABLE `gallery_images` (
  `id` int(11) NOT NULL,
  `image_path` varchar(255) NOT NULL,
  `alt_text` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `gallery_images`
--

INSERT INTO `gallery_images` (`id`, `image_path`, `alt_text`) VALUES
(5, 'cms_img/gallery/674eea5fcefdd_3d1d304e-c58e-4838-9f04-e65597c09dfb.jpg', 'image'),
(6, 'cms_img/gallery/674eea67340d3_3d1d304e-c58e-4838-9f04-e65597c09dfb.jpg', 'image 2'),
(7, 'cms_img/gallery/674eea6d46d28_3d1d304e-c58e-4838-9f04-e65597c09dfb.jpg', 'image 3'),
(8, 'cms_img/gallery/674eea7452fb3_3d1d304e-c58e-4838-9f04-e65597c09dfb.jpg', 'image 4');

-- --------------------------------------------------------

--
-- Table structure for table `gym_offers`
--

CREATE TABLE `gym_offers` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `image_path` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `gym_offers`
--

INSERT INTO `gym_offers` (`id`, `title`, `description`, `image_path`, `created_at`, `updated_at`) VALUES
(1, 'standard', '100', 'cms_img/offers/674ee394e32d1_461323753_504421532393159_5499801553050918367_n.jpg', '2024-12-03 10:55:16', '2024-12-03 10:55:16'),
(2, '1231', '4wqe', 'cms_img/offers/674ee9cd2cbab_1805200.jpg', '2024-12-03 11:21:49', '2024-12-03 11:21:49'),
(3, 'rqrqwr', 'rqw', 'cms_img/offers/67510b32320ae_462638319_1025131135758893_3299917056877386691_n.jpg', '2024-12-05 02:08:50', '2024-12-05 02:08:50');

-- --------------------------------------------------------

--
-- Table structure for table `memberships`
--

CREATE TABLE `memberships` (
  `id` int(11) NOT NULL,
  `transaction_id` int(11) NOT NULL,
  `membership_plan_id` int(11) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `status` enum('active','expiring','expired') NOT NULL,
  `is_paid` tinyint(1) NOT NULL DEFAULT 0,
  `payment_date` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `memberships`
--

INSERT INTO `memberships` (`id`, `transaction_id`, `membership_plan_id`, `start_date`, `end_date`, `amount`, `status`, `is_paid`, `payment_date`, `created_at`, `updated_at`) VALUES
(80, 73, 20, '2024-12-06', '2025-01-06', 500.00, 'active', 1, NULL, '2024-12-04 23:51:05', '2024-12-05 01:55:13'),
(81, 74, 20, '2024-12-06', '2025-01-06', 500.00, 'active', 1, NULL, '2024-12-05 01:45:22', '2024-12-05 01:55:13'),
(82, 75, 20, '2024-12-05', '2025-01-05', 500.00, 'active', 1, '2024-12-05 10:01:36', '2024-12-05 02:01:36', '2024-12-05 02:01:36'),
(83, 76, 21, '2024-12-05', '2025-01-05', 400.00, 'active', 1, '2024-12-05 10:06:06', '2024-12-05 02:06:06', '2024-12-05 02:06:06'),
(84, 77, 20, '2025-01-19', '2025-02-19', 500.00, 'active', 1, '2025-01-19 21:46:55', '2025-01-19 13:46:55', '2025-01-19 13:46:55'),
(85, 78, 20, '2025-01-19', '2025-02-19', 500.00, 'active', 0, NULL, '2025-01-19 13:59:32', '2025-01-19 13:59:32'),
(86, 81, 21, '2025-01-19', '2025-02-19', 400.00, 'active', 0, NULL, '2025-01-19 14:16:36', '2025-01-19 14:16:36'),
(87, 82, 20, '2025-01-21', '2025-02-21', 500.00, 'active', 0, NULL, '2025-01-19 14:19:50', '2025-01-19 14:19:50');

-- --------------------------------------------------------

--
-- Table structure for table `membership_plans`
--

CREATE TABLE `membership_plans` (
  `id` int(11) NOT NULL,
  `plan_name` varchar(100) NOT NULL,
  `plan_type` enum('standard','special') NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `duration` int(11) NOT NULL,
  `duration_type_id` int(11) NOT NULL,
  `description` text DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `status` enum('active','inactive') NOT NULL,
  `is_removed` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `membership_plans`
--

INSERT INTO `membership_plans` (`id`, `plan_name`, `plan_type`, `price`, `duration`, `duration_type_id`, `description`, `start_date`, `end_date`, `status`, `is_removed`, `created_at`, `updated_at`) VALUES
(20, 'Regular', 'standard', 500.00, 1, 2, 'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.', '2024-12-02', '2024-12-31', 'active', 0, '2024-12-03 14:33:24', '2024-12-03 14:33:24'),
(21, 'Christmas Prom', 'special', 400.00, 1, 2, 'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.', '2024-12-03', '2024-12-31', 'active', 0, '2024-12-03 14:33:59', '2025-01-19 14:06:24'),
(23, 'hatdog', 'standard', 100.00, 1, 2, 'haha', '2025-01-19', '2025-01-21', 'inactive', 1, '2025-01-19 14:06:47', '2025-01-19 14:06:51');

-- --------------------------------------------------------

--
-- Table structure for table `personal_details`
--

CREATE TABLE `personal_details` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `middle_name` varchar(50) DEFAULT NULL,
  `last_name` varchar(50) NOT NULL,
  `sex` enum('Male','Female') NOT NULL,
  `birthdate` date NOT NULL,
  `phone_number` varchar(20) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `personal_details`
--

INSERT INTO `personal_details` (`id`, `user_id`, `first_name`, `middle_name`, `last_name`, `sex`, `birthdate`, `phone_number`, `created_at`, `updated_at`) VALUES
(29, 33, 'Reign', 'Carreon', 'Magno', 'Male', '2004-08-02', '12312312312', '2024-12-03 14:29:47', '2024-12-03 14:29:47'),
(30, 34, 'admin', '', 'admin', 'Male', '2024-12-03', '12312312312', '2024-12-03 14:30:35', '2024-12-03 14:30:35'),
(31, 35, 'coach', '', 'coach', 'Female', '2024-12-03', '12312312312', '2024-12-03 14:31:06', '2024-12-03 14:31:06'),
(32, 36, 'coach2', '', 'coach2', 'Male', '2024-12-03', '12312312312', '2024-12-03 14:31:30', '2024-12-03 14:31:30'),
(33, 37, 'bai', NULL, 'bai', 'Female', '2024-12-05', '09752441070', '2024-12-05 02:01:36', '2024-12-05 02:01:36'),
(34, 38, 'bai', NULL, 'bai', 'Female', '2024-12-03', '09752441070', '2024-12-05 02:06:06', '2024-12-05 02:06:06'),
(35, 39, 'sofia', 'the', 'first', 'Female', '2000-08-11', '09876543211', '2025-01-19 13:46:55', '2025-01-19 13:46:55'),
(36, 40, 'kiel', 'the', 'great', 'Male', '2025-01-19', '09123456789', '2025-01-19 13:58:19', '2025-01-19 13:58:19');

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `image_path` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `name`, `description`, `image_path`) VALUES
(6, 'product', '100', 'cms_img/products/674ee9eb68c0e_206268.jpg'),
(7, 'product 2', '100', 'cms_img/products/674ee9fa55ac3_1805129.jpg'),
(8, 'product 3', '100', 'cms_img/products/674eea0eb8ef8_peakpx2.jpg'),
(9, 'product 4', '100', 'cms_img/products/674eea2122479_pexels-caleboquendo-7772559.jpg');

-- --------------------------------------------------------

--
-- Table structure for table `profile_photos`
--

CREATE TABLE `profile_photos` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `photo_path` varchar(255) NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `profile_photos`
--

INSERT INTO `profile_photos` (`id`, `user_id`, `photo_path`, `is_active`, `uploaded_at`) VALUES
(19, 33, 'uploads/profile_33_674f15db64255.jpg', 1, '2024-12-03 14:29:47'),
(20, 34, 'uploads/profile_34_674f160b69d41.jpg', 1, '2024-12-03 14:30:35'),
(21, 35, 'uploads/profile_35_674f162a2c762.png', 1, '2024-12-03 14:31:06'),
(22, 36, 'uploads/profile_36_674f164225640.png', 1, '2024-12-03 14:31:30'),
(23, 37, 'uploads/profile_37_675109803519e.png', 1, '2024-12-05 02:01:36'),
(24, 38, 'uploads/profile_38_67510a8ecc7b7.jpg', 1, '2024-12-05 02:06:06'),
(25, 39, 'uploads/profile_39_678d024fb276b.png', 1, '2025-01-19 13:46:55'),
(26, 40, 'uploads/profile_40_678d04fb9e423.png', 1, '2025-01-19 13:58:19');

-- --------------------------------------------------------

--
-- Table structure for table `programs`
--

CREATE TABLE `programs` (
  `id` int(11) NOT NULL,
  `program_name` varchar(100) NOT NULL,
  `program_type_id` int(11) NOT NULL,
  `duration` int(11) NOT NULL,
  `duration_type_id` int(11) NOT NULL,
  `description` text DEFAULT NULL,
  `status` enum('active','inactive') NOT NULL,
  `is_removed` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `programs`
--

INSERT INTO `programs` (`id`, `program_name`, `program_type_id`, `duration`, `duration_type_id`, `description`, `status`, `is_removed`, `created_at`, `updated_at`) VALUES
(3, 'Coaching', 1, 1, 2, 'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.', 'active', 0, '2024-12-03 14:34:54', '2024-12-03 15:41:47'),
(6, 'ako', 1, 1, 2, 'dadada', 'active', 0, '2025-01-19 14:08:05', '2025-01-19 14:08:05');

-- --------------------------------------------------------

--
-- Table structure for table `program_subscriptions`
--

CREATE TABLE `program_subscriptions` (
  `id` int(11) NOT NULL,
  `transaction_id` int(11) NOT NULL,
  `program_id` int(11) NOT NULL,
  `coach_id` int(11) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `status` enum('active','expiring','expired') NOT NULL,
  `is_paid` tinyint(1) NOT NULL DEFAULT 0,
  `payment_date` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `program_subscriptions`
--

INSERT INTO `program_subscriptions` (`id`, `transaction_id`, `program_id`, `coach_id`, `start_date`, `end_date`, `amount`, `status`, `is_paid`, `payment_date`, `created_at`, `updated_at`) VALUES
(35, 73, 3, 35, '2024-12-06', '2025-01-06', 450.00, 'active', 1, NULL, '2024-12-04 23:51:05', '2024-12-05 01:55:13'),
(36, 76, 3, 36, '2024-12-05', '2025-01-05', 450.00, 'active', 1, '2024-12-05 10:06:06', '2024-12-05 02:06:06', '2024-12-05 02:06:06');

-- --------------------------------------------------------

--
-- Table structure for table `program_types`
--

CREATE TABLE `program_types` (
  `id` int(11) NOT NULL,
  `type_name` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `program_types`
--

INSERT INTO `program_types` (`id`, `type_name`) VALUES
(2, 'group'),
(1, 'personal');

-- --------------------------------------------------------

--
-- Table structure for table `registration`
--

CREATE TABLE `registration` (
  `id` int(11) NOT NULL,
  `membership_fee` decimal(10,2) NOT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `registration`
--

INSERT INTO `registration` (`id`, `membership_fee`, `updated_at`) VALUES
(1, 150.00, '2025-01-19 15:23:29');

-- --------------------------------------------------------

--
-- Table structure for table `registration_records`
--

CREATE TABLE `registration_records` (
  `id` int(11) NOT NULL,
  `transaction_id` int(11) NOT NULL,
  `registration_id` int(11) NOT NULL DEFAULT 1,
  `amount` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `registration_records`
--

INSERT INTO `registration_records` (`id`, `transaction_id`, `registration_id`, `amount`, `created_at`) VALUES
(20, 73, 1, 150.00, '2024-12-04 23:51:05'),
(21, 75, 1, 150.00, '2024-12-05 02:01:36'),
(22, 76, 1, 150.00, '2024-12-05 02:06:06'),
(23, 77, 1, 150.00, '2025-01-19 13:46:55'),
(24, 78, 1, 150.00, '2025-01-19 13:59:32');

-- --------------------------------------------------------

--
-- Table structure for table `rental_services`
--

CREATE TABLE `rental_services` (
  `id` int(11) NOT NULL,
  `service_name` varchar(100) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `total_slots` int(11) NOT NULL,
  `available_slots` int(11) NOT NULL,
  `duration` int(11) NOT NULL,
  `duration_type_id` int(11) NOT NULL,
  `description` text DEFAULT NULL,
  `status` enum('active','inactive') NOT NULL,
  `is_removed` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `rental_services`
--

INSERT INTO `rental_services` (`id`, `service_name`, `price`, `total_slots`, `available_slots`, `duration`, `duration_type_id`, `description`, `status`, `is_removed`, `created_at`, `updated_at`) VALUES
(5, 'Locker', 150.00, 100, 89, 1, 2, 'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.', 'active', 0, '2024-12-03 14:35:06', '2024-12-05 02:06:06');

-- --------------------------------------------------------

--
-- Table structure for table `rental_subscriptions`
--

CREATE TABLE `rental_subscriptions` (
  `id` int(11) NOT NULL,
  `transaction_id` int(11) NOT NULL,
  `rental_service_id` int(11) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `status` enum('active','expiring','expired') NOT NULL,
  `is_paid` tinyint(1) NOT NULL DEFAULT 0,
  `payment_date` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `rental_subscriptions`
--

INSERT INTO `rental_subscriptions` (`id`, `transaction_id`, `rental_service_id`, `start_date`, `end_date`, `amount`, `status`, `is_paid`, `payment_date`, `created_at`, `updated_at`) VALUES
(36, 73, 5, '2024-12-06', '2025-01-06', 150.00, 'active', 1, NULL, '2024-12-04 23:51:05', '2024-12-05 01:55:13'),
(37, 76, 5, '2024-12-05', '2025-01-05', 150.00, 'active', 1, '2024-12-05 10:06:06', '2024-12-05 02:06:06', '2024-12-05 02:06:06');

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `id` int(11) NOT NULL,
  `role_name` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`id`, `role_name`) VALUES
(1, 'admin'),
(4, 'coach'),
(3, 'member'),
(2, 'staff'),
(5, 'user');

-- --------------------------------------------------------

--
-- Table structure for table `staff`
--

CREATE TABLE `staff` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `status` varchar(100) DEFAULT NULL,
  `image_path` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `staff`
--

INSERT INTO `staff` (`id`, `name`, `status`, `image_path`) VALUES
(5, 'gerby', 'Trainer', 'cms_img/staff/674eea3654d6a_3d1d304e-c58e-4838-9f04-e65597c09dfb.jpg'),
(6, 'jamal', 'Trainer', 'cms_img/staff/674eea3e5c85e_3d1d304e-c58e-4838-9f04-e65597c09dfb.jpg'),
(7, 'reign', 'Trainer', 'cms_img/staff/674eea472ace9_3d1d304e-c58e-4838-9f04-e65597c09dfb.jpg'),
(8, 'jerd', 'Trainer', 'cms_img/staff/674eea4df3dc3_3d1d304e-c58e-4838-9f04-e65597c09dfb.jpg');

-- --------------------------------------------------------

--
-- Table structure for table `transactions`
--

CREATE TABLE `transactions` (
  `id` int(11) NOT NULL,
  `staff_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `status` enum('confirmed','pending','cancelled') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `transactions`
--

INSERT INTO `transactions` (`id`, `staff_id`, `user_id`, `status`, `created_at`) VALUES
(73, NULL, 33, 'confirmed', '2024-12-04 23:51:05'),
(74, NULL, 33, 'confirmed', '2024-12-05 01:45:22'),
(75, 34, 37, 'confirmed', '2024-12-05 02:01:36'),
(76, 34, 38, 'confirmed', '2024-12-05 02:06:06'),
(77, 34, 39, 'confirmed', '2025-01-19 13:46:55'),
(78, NULL, 40, 'confirmed', '2025-01-19 13:59:32'),
(79, NULL, NULL, 'confirmed', '2025-01-19 14:03:09'),
(80, NULL, 40, 'confirmed', '2025-01-19 14:03:25'),
(81, NULL, 33, 'confirmed', '2025-01-19 14:16:36'),
(82, NULL, 33, 'pending', '2025-01-19 14:19:50');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role_id` int(11) NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `role_id`, `is_active`, `created_at`, `updated_at`) VALUES
(33, 'reign', '$2y$10$Gej8JD.h1Lo9enEaEtRNb.gXcarqSXwo9uJSk3wt7RV1j.nW1RcPS', 3, 1, '2024-12-03 14:29:47', '2024-12-04 23:52:08'),
(34, 'admin', '$2y$10$jFClQzi6TkhZvQfTaXjDQOnqnNXOxRSehXfV14EiQ1l96GahuBT92', 1, 1, '2024-12-03 14:30:35', '2024-12-03 14:31:46'),
(35, 'coach', '$2y$10$pjk6.DxDk100djz9iZ.u2ul6brXXvm95yUbFDJmzBFAhfHDZQPrWC', 4, 1, '2024-12-03 14:31:06', '2024-12-03 14:31:51'),
(36, 'coach2', '$2y$10$u4vKNjQXzbx9dRW.S950meSICjRzNn12/0gjm2GdcPrSvH8qrIvO2', 4, 1, '2024-12-03 14:31:30', '2024-12-03 14:31:56'),
(37, 'bai', '$2y$10$zVaoEdg8TGb/LJEGuS06p.M.nocykoEs.ak2FAPY36VOkgnDyW2Yq', 3, 1, '2024-12-05 02:01:36', '2024-12-05 02:01:36'),
(38, 'bai2', '$2y$10$UEX0b7O0upwCRPloDYBFdesuXWkwMpUYaLvYuSvtQzh9ihkQemmRu', 3, 1, '2024-12-05 02:06:06', '2024-12-05 02:06:06'),
(39, 'sofia', '$2y$10$aT9HT0aRE/DCbCaNU/T.z.6UFxe2NROVPK3IZi9jY9hGqJerRxQgC', 3, 1, '2025-01-19 13:46:55', '2025-01-19 13:46:55'),
(40, 'kiel', '$2y$10$rnEhRal7OocaGpeHOJeP8OXzFAn91gf5ukjJtZpvqlrlFzT6ZCBi2', 3, 1, '2025-01-19 13:58:19', '2025-01-19 13:59:58');

-- --------------------------------------------------------

--
-- Table structure for table `walk_in`
--

CREATE TABLE `walk_in` (
  `id` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `duration` int(11) NOT NULL DEFAULT 1,
  `duration_type_id` int(11) NOT NULL DEFAULT 1,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `walk_in`
--

INSERT INTO `walk_in` (`id`, `price`, `duration`, `duration_type_id`, `updated_at`) VALUES
(1, 50.00, 1, 1, '2024-12-04 11:55:09');

-- --------------------------------------------------------

--
-- Table structure for table `walk_in_records`
--

CREATE TABLE `walk_in_records` (
  `id` int(11) NOT NULL,
  `transaction_id` int(11) NOT NULL,
  `walk_in_id` int(11) NOT NULL DEFAULT 1,
  `name` varchar(50) DEFAULT NULL,
  `phone_number` varchar(20) DEFAULT NULL,
  `date` date NOT NULL,
  `time_in` time DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `is_paid` tinyint(1) NOT NULL,
  `status` enum('walked-in','pending') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `walk_in_records`
--

INSERT INTO `walk_in_records` (`id`, `transaction_id`, `walk_in_id`, `name`, `phone_number`, `date`, `time_in`, `amount`, `is_paid`, `status`) VALUES
(16, 79, 1, '8080', '09876543211', '2025-01-19', '22:03:09', 50.00, 1, 'walked-in'),
(17, 80, 1, 'kiel great', '09123456789', '2025-01-19', '22:03:57', 50.00, 1, 'walked-in');

-- --------------------------------------------------------

--
-- Table structure for table `website_content`
--

CREATE TABLE `website_content` (
  `id` int(11) NOT NULL,
  `section` varchar(50) NOT NULL,
  `company_name` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `website_content`
--

INSERT INTO `website_content` (`id`, `section`, `company_name`, `description`, `location`, `phone`, `email`) VALUES
(1, 'welcome', 'Xiao Long Bai need\'s help', 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum. please let us pass hehe', NULL, NULL, NULL),
(2, 'offers', 'Gym Offers', 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum. please let us pass hehe', NULL, NULL, NULL),
(3, 'about_us', 'About Our Gym', 'We are dedicated to helping gerby', NULL, NULL, NULL),
(4, 'contact', NULL, NULL, 'gerby\'s house', '09562307646', 'hallasgogerby@gmail.com');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `attendance`
--
ALTER TABLE `attendance`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `attendance_history`
--
ALTER TABLE `attendance_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `attendance_id` (`attendance_id`);

--
-- Indexes for table `coach_program_types`
--
ALTER TABLE `coach_program_types`
  ADD PRIMARY KEY (`id`),
  ADD KEY `program_id` (`program_id`),
  ADD KEY `coach_program_types_ibfk_1` (`coach_id`);

--
-- Indexes for table `duration_types`
--
ALTER TABLE `duration_types`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `type_name` (`type_name`);

--
-- Indexes for table `gallery_images`
--
ALTER TABLE `gallery_images`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `gym_offers`
--
ALTER TABLE `gym_offers`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `memberships`
--
ALTER TABLE `memberships`
  ADD PRIMARY KEY (`id`),
  ADD KEY `memberships_ibfk_1` (`membership_plan_id`),
  ADD KEY `transaction_id` (`transaction_id`);

--
-- Indexes for table `membership_plans`
--
ALTER TABLE `membership_plans`
  ADD PRIMARY KEY (`id`),
  ADD KEY `duration_type_id` (`duration_type_id`);

--
-- Indexes for table `personal_details`
--
ALTER TABLE `personal_details`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `profile_photos`
--
ALTER TABLE `profile_photos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `programs`
--
ALTER TABLE `programs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `program_type_id` (`program_type_id`),
  ADD KEY `duration_type_id` (`duration_type_id`);

--
-- Indexes for table `program_subscriptions`
--
ALTER TABLE `program_subscriptions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `program_id` (`program_id`),
  ADD KEY `coach_id` (`coach_id`),
  ADD KEY `transaction_id` (`transaction_id`);

--
-- Indexes for table `program_types`
--
ALTER TABLE `program_types`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `type_name` (`type_name`);

--
-- Indexes for table `registration`
--
ALTER TABLE `registration`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `registration_records`
--
ALTER TABLE `registration_records`
  ADD PRIMARY KEY (`id`),
  ADD KEY `registration_id` (`registration_id`),
  ADD KEY `registration_records_ibfk_2` (`transaction_id`);

--
-- Indexes for table `rental_services`
--
ALTER TABLE `rental_services`
  ADD PRIMARY KEY (`id`),
  ADD KEY `duration_type_id` (`duration_type_id`);

--
-- Indexes for table `rental_subscriptions`
--
ALTER TABLE `rental_subscriptions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `rental_service_id` (`rental_service_id`),
  ADD KEY `transaction_id` (`transaction_id`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `role_name` (`role_name`);

--
-- Indexes for table `staff`
--
ALTER TABLE `staff`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `transactions`
--
ALTER TABLE `transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `staff_id` (`staff_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD KEY `role_id` (`role_id`);

--
-- Indexes for table `walk_in`
--
ALTER TABLE `walk_in`
  ADD PRIMARY KEY (`id`),
  ADD KEY `duration_type_id` (`duration_type_id`);

--
-- Indexes for table `walk_in_records`
--
ALTER TABLE `walk_in_records`
  ADD PRIMARY KEY (`id`),
  ADD KEY `transaction_id` (`transaction_id`),
  ADD KEY `walk_in_id` (`walk_in_id`);

--
-- Indexes for table `website_content`
--
ALTER TABLE `website_content`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `section` (`section`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `attendance`
--
ALTER TABLE `attendance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

--
-- AUTO_INCREMENT for table `attendance_history`
--
ALTER TABLE `attendance_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=53;

--
-- AUTO_INCREMENT for table `coach_program_types`
--
ALTER TABLE `coach_program_types`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `duration_types`
--
ALTER TABLE `duration_types`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `gallery_images`
--
ALTER TABLE `gallery_images`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `gym_offers`
--
ALTER TABLE `gym_offers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `memberships`
--
ALTER TABLE `memberships`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=88;

--
-- AUTO_INCREMENT for table `membership_plans`
--
ALTER TABLE `membership_plans`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `personal_details`
--
ALTER TABLE `personal_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `profile_photos`
--
ALTER TABLE `profile_photos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT for table `programs`
--
ALTER TABLE `programs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `program_subscriptions`
--
ALTER TABLE `program_subscriptions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

--
-- AUTO_INCREMENT for table `program_types`
--
ALTER TABLE `program_types`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `registration`
--
ALTER TABLE `registration`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `registration_records`
--
ALTER TABLE `registration_records`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `rental_services`
--
ALTER TABLE `rental_services`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `rental_subscriptions`
--
ALTER TABLE `rental_subscriptions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=38;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `staff`
--
ALTER TABLE `staff`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `transactions`
--
ALTER TABLE `transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=83;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

--
-- AUTO_INCREMENT for table `walk_in`
--
ALTER TABLE `walk_in`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `walk_in_records`
--
ALTER TABLE `walk_in_records`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `website_content`
--
ALTER TABLE `website_content`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `attendance`
--
ALTER TABLE `attendance`
  ADD CONSTRAINT `attendance_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `attendance_history`
--
ALTER TABLE `attendance_history`
  ADD CONSTRAINT `attendance_history_ibfk_1` FOREIGN KEY (`attendance_id`) REFERENCES `attendance` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `coach_program_types`
--
ALTER TABLE `coach_program_types`
  ADD CONSTRAINT `coach_program_types_ibfk_1` FOREIGN KEY (`coach_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `coach_program_types_ibfk_2` FOREIGN KEY (`program_id`) REFERENCES `programs` (`id`);

--
-- Constraints for table `memberships`
--
ALTER TABLE `memberships`
  ADD CONSTRAINT `memberships_ibfk_1` FOREIGN KEY (`membership_plan_id`) REFERENCES `membership_plans` (`id`),
  ADD CONSTRAINT `memberships_ibfk_2` FOREIGN KEY (`transaction_id`) REFERENCES `transactions` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `membership_plans`
--
ALTER TABLE `membership_plans`
  ADD CONSTRAINT `membership_plans_ibfk_1` FOREIGN KEY (`duration_type_id`) REFERENCES `duration_types` (`id`);

--
-- Constraints for table `personal_details`
--
ALTER TABLE `personal_details`
  ADD CONSTRAINT `personal_details_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `profile_photos`
--
ALTER TABLE `profile_photos`
  ADD CONSTRAINT `profile_photos_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `programs`
--
ALTER TABLE `programs`
  ADD CONSTRAINT `programs_ibfk_1` FOREIGN KEY (`program_type_id`) REFERENCES `program_types` (`id`),
  ADD CONSTRAINT `programs_ibfk_2` FOREIGN KEY (`duration_type_id`) REFERENCES `duration_types` (`id`);

--
-- Constraints for table `program_subscriptions`
--
ALTER TABLE `program_subscriptions`
  ADD CONSTRAINT `program_subscriptions_ibfk_2` FOREIGN KEY (`program_id`) REFERENCES `programs` (`id`),
  ADD CONSTRAINT `program_subscriptions_ibfk_3` FOREIGN KEY (`coach_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `program_subscriptions_ibfk_4` FOREIGN KEY (`transaction_id`) REFERENCES `transactions` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `registration_records`
--
ALTER TABLE `registration_records`
  ADD CONSTRAINT `registration_records_ibfk_1` FOREIGN KEY (`registration_id`) REFERENCES `registration` (`id`),
  ADD CONSTRAINT `registration_records_ibfk_2` FOREIGN KEY (`transaction_id`) REFERENCES `transactions` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `rental_services`
--
ALTER TABLE `rental_services`
  ADD CONSTRAINT `rental_services_ibfk_1` FOREIGN KEY (`duration_type_id`) REFERENCES `duration_types` (`id`);

--
-- Constraints for table `rental_subscriptions`
--
ALTER TABLE `rental_subscriptions`
  ADD CONSTRAINT `rental_subscriptions_ibfk_1` FOREIGN KEY (`rental_service_id`) REFERENCES `rental_services` (`id`),
  ADD CONSTRAINT `rental_subscriptions_ibfk_2` FOREIGN KEY (`transaction_id`) REFERENCES `transactions` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `transactions`
--
ALTER TABLE `transactions`
  ADD CONSTRAINT `transactions_ibfk_1` FOREIGN KEY (`staff_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION,
  ADD CONSTRAINT `transactions_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`);

--
-- Constraints for table `walk_in`
--
ALTER TABLE `walk_in`
  ADD CONSTRAINT `walk_in_ibfk_1` FOREIGN KEY (`duration_type_id`) REFERENCES `duration_types` (`id`);

--
-- Constraints for table `walk_in_records`
--
ALTER TABLE `walk_in_records`
  ADD CONSTRAINT `walk_in_records_ibfk_1` FOREIGN KEY (`transaction_id`) REFERENCES `transactions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `walk_in_records_ibfk_2` FOREIGN KEY (`walk_in_id`) REFERENCES `walk_in` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
