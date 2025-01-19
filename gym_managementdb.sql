-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jan 19, 2025 at 02:37 PM
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
(36, 40, '2024-12-04', '00:33:35', '00:33:37', '2024-12-04 23:33:35', 'checked_out'),
(37, 51, '2024-12-14', '11:11:35', '11:11:37', '2024-12-14 10:11:35', 'checked_out');

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
(51, 36, '00:33:35', NULL, '2024-12-04 23:33:35', 'checked_in'),
(52, 36, '00:33:35', '00:33:37', '2024-12-04 23:33:37', 'checked_out'),
(53, 37, '11:11:35', NULL, '2024-12-14 10:11:35', 'checked_in'),
(54, 37, '11:11:35', '11:11:37', '2024-12-14 10:11:37', 'checked_out');

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
(4, 35, 3, 500.00, 'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.', 'active', '2024-12-03 14:35:24', '2024-12-03 14:35:24'),
(10, 36, 3, 450.00, 'Lorem ipsum odor amet, consectetuer adipiscing elit. Sollicitudin donec dolor sagittis per egestas montes tellus vel. Cursus imperdiet faucibus habitasse finibus accumsan pellentesque eget. Senectus nam integer laoreet ornare cursus metus. Lobortis aliquet cras himenaeos neque lectus pharetra condimentum ante. Auctor erat mattis class metus mollis lacus ex. Euismod hac habitant ac aenean mauris. Mus eros vestibulum interdum fermentum tempor quisque. Ante porttitor maecenas ornare ex vel fringilla euismod lacus bibendum.', 'active', '2024-12-03 16:14:34', '2024-12-03 16:14:34'),
(11, 35, 6, 400.00, 'test', 'active', '2024-12-14 10:14:10', '2024-12-14 10:14:10');

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
(9, 'cms_img/gallery/675d5e84f132b_gallery3.jpg', 'jayson');

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
(3, 'special', 'none', 'cms_img/offers/675d5e1b30e47_461323753_504421532393159_5499801553050918367_n.jpg', '2024-12-14 10:29:47', '2024-12-14 10:29:47');

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
(82, 75, 20, '2024-12-04', '2025-01-04', 500.00, 'active', 1, '2024-12-05 07:33:05', '2024-12-04 23:33:05', '2024-12-05 00:00:22'),
(84, 77, 20, '2024-12-04', '2025-01-04', 500.00, 'active', 1, '2024-12-05 07:44:49', '2024-12-04 23:44:49', '2024-12-04 23:44:49'),
(85, 78, 20, '2024-12-04', '2025-01-04', 500.00, 'active', 1, '2024-12-05 07:52:38', '2024-12-04 23:52:38', '2024-12-04 23:52:38'),
(86, 79, 20, '2024-12-05', '2025-01-05', 500.00, 'active', 1, '2024-12-05 08:13:43', '2024-12-05 00:13:43', '2024-12-05 00:13:43'),
(87, 80, 21, '2024-12-05', '2025-01-05', 400.00, 'active', 1, '2024-12-05 09:17:09', '2024-12-05 01:17:09', '2024-12-05 01:17:09'),
(88, 81, 21, '2024-12-05', '2025-01-05', 400.00, 'active', 1, '2024-12-05 09:24:17', '2024-12-05 01:24:17', '2024-12-05 01:24:17'),
(91, 86, 20, '2024-12-06', '2025-01-06', 500.00, 'active', 0, NULL, '2024-12-05 02:25:08', '2024-12-05 02:25:08'),
(92, 87, 20, '2024-12-05', '2025-01-05', 500.00, 'active', 1, '2024-12-05 11:11:37', '2024-12-05 03:11:37', '2024-12-05 03:13:11'),
(93, 88, 21, '2024-12-14', '2025-01-14', 400.00, 'active', 1, '2024-12-14 18:10:24', '2024-12-14 10:10:24', '2024-12-14 10:10:24'),
(94, 89, 24, '2024-12-15', '2025-01-15', 400.00, 'active', 1, NULL, '2024-12-14 10:22:15', '2024-12-14 10:23:35');

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
(21, 'Christmas Promo', 'special', 400.00, 1, 2, 'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.', '2024-12-03', '2024-12-31', 'active', 0, '2024-12-03 14:33:59', '2024-12-03 14:33:59'),
(23, 'balik alindog ', 'special', 1000.00, 1, 2, '', '2024-12-05', '2024-12-06', 'active', 0, '2024-12-05 02:39:34', '2024-12-05 02:39:34'),
(24, 'test', 'standard', 400.00, 1, 2, 'test', '2024-12-14', '2025-01-02', 'active', 0, '2024-12-14 10:12:45', '2024-12-14 10:12:45');

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
(30, 34, 'admin', '', 'admin', 'Male', '2024-12-03', '12312312312', '2024-12-03 14:30:35', '2024-12-03 14:30:35'),
(31, 35, 'coach', '', 'coach', 'Female', '2024-12-03', '12312312312', '2024-12-03 14:31:06', '2024-12-03 14:31:06'),
(32, 36, 'coach2', '', 'coach2', 'Male', '2024-12-03', '12312312312', '2024-12-03 14:31:30', '2024-12-03 14:31:30'),
(33, 37, 'gerby', '', 'hallasgo', 'Male', '2024-12-05', '09562307646', '2024-12-04 23:25:33', '2024-12-04 23:25:33'),
(36, 40, 'reign', NULL, 'magno', 'Male', '2024-12-05', '09562307648', '2024-12-04 23:33:05', '2024-12-04 23:33:05'),
(38, 43, 'hui', NULL, 'fon', 'Male', '2024-12-05', '09562307647', '2024-12-04 23:44:49', '2024-12-04 23:44:49'),
(39, 44, 'nosjay', NULL, 'nosjay', 'Male', '2024-12-05', '09562307647', '2024-12-04 23:52:38', '2024-12-04 23:52:38'),
(40, 45, 'powerzone', NULL, 'powerzone', 'Male', '2024-12-05', '09562307647', '2024-12-05 00:13:43', '2024-12-05 00:13:43'),
(41, 46, 'jamal', NULL, 'jamal', 'Female', '2024-12-05', '09562307646', '2024-12-05 01:17:09', '2024-12-05 01:17:09'),
(42, 47, 'sample', NULL, 'sample', 'Male', '2024-12-05', '09562307646', '2024-12-05 01:24:17', '2024-12-05 01:24:17'),
(43, 48, 'jerd', '', 'regals', 'Male', '2024-12-05', '09562307646', '2024-12-05 02:21:22', '2024-12-05 02:21:22'),
(44, 50, 'sample', NULL, 'sample', 'Male', '2024-12-05', '09562307646', '2024-12-05 03:11:37', '2024-12-05 03:11:37'),
(45, 51, 'test', NULL, 'test', 'Female', '2024-12-14', '09562307647', '2024-12-14 10:10:24', '2024-12-14 10:10:24'),
(46, 52, 'test', '', 'test', 'Female', '2024-12-14', '09562307646', '2024-12-14 10:18:37', '2024-12-14 10:18:37'),
(47, 53, 'jade', '', 'from', 'Male', '2024-12-14', '09562307646', '2024-12-14 10:19:49', '2024-12-14 10:19:49');

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
(9, 'product 4', '100', 'cms_img/products/674eea2122479_pexels-caleboquendo-7772559.jpg'),
(10, 'product 5', 'none', 'cms_img/products/675d5e5340df0_461323753_504421532393159_5499801553050918367_n.jpg');

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
(20, 34, 'uploads/profile_34_674f160b69d41.jpg', 1, '2024-12-03 14:30:35'),
(21, 35, 'uploads/profile_35_674f162a2c762.png', 1, '2024-12-03 14:31:06'),
(22, 36, 'uploads/profile_36_674f164225640.png', 1, '2024-12-03 14:31:30'),
(23, 37, 'uploads/profile_37_6750e4ed76a0b.jpg', 1, '2024-12-04 23:25:33'),
(26, 40, 'uploads/profile_40_6750e6b1eb489.jpg', 1, '2024-12-04 23:33:05'),
(27, 43, 'uploads/profile_43_6750e971d31ba.jpg', 1, '2024-12-04 23:44:49'),
(28, 44, 'uploads/profile_44_6750eb46a4582.jpg', 1, '2024-12-04 23:52:38'),
(29, 45, 'uploads/profile_45_6750f037508e9.jpg', 1, '2024-12-05 00:13:43'),
(30, 46, 'uploads/profile_46_6750ff15a2dd9.jpg', 1, '2024-12-05 01:17:09'),
(31, 47, 'uploads/profile_47_675100c1db9bc.jpg', 1, '2024-12-05 01:24:17'),
(32, 48, 'uploads/profile_48_67510e227b4d2.jpg', 1, '2024-12-05 02:21:22'),
(33, 50, 'uploads/profile_50_675119e9d49bd.jpg', 1, '2024-12-05 03:11:37'),
(34, 51, 'uploads/profile_51_675d59904a0ec.jpg', 1, '2024-12-14 10:10:24'),
(35, 53, 'uploads/profile_53_675d5bc5a9e95.png', 1, '2024-12-14 10:19:49');

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
(6, 'test', 1, 1, 2, 'test', 'active', 0, '2024-12-14 10:13:54', '2024-12-14 10:13:54');

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
(38, 80, 3, 35, '2024-12-05', '2025-01-05', 500.00, 'active', 1, '2024-12-05 09:17:09', '2024-12-05 01:17:09', '2024-12-05 01:17:09'),
(39, 81, 3, 36, '2024-12-05', '2025-01-05', 450.00, 'active', 1, '2024-12-05 09:24:17', '2024-12-05 01:24:17', '2024-12-05 01:24:17'),
(41, 88, 3, 36, '2024-12-14', '2025-01-14', 450.00, 'active', 1, '2024-12-14 18:10:24', '2024-12-14 10:10:24', '2024-12-14 10:10:24'),
(42, 89, 6, 35, '2024-12-15', '2025-01-15', 400.00, 'active', 1, NULL, '2024-12-14 10:22:15', '2024-12-14 10:23:35');

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
-- Error reading structure for table gym_managementdb.registration: #1932 - Table &#039;gym_managementdb.registration&#039; doesn&#039;t exist in engine
-- Error reading data for table gym_managementdb.registration: #1064 - You have an error in your SQL syntax; check the manual that corresponds to your MariaDB server version for the right syntax to use near &#039;FROM `gym_managementdb`.`registration`&#039; at line 1

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
(22, 75, 1, 150.00, '2024-12-04 23:33:05'),
(24, 77, 1, 150.00, '2024-12-04 23:44:49'),
(25, 78, 1, 150.00, '2024-12-04 23:52:38'),
(26, 79, 1, 150.00, '2024-12-05 00:13:43'),
(27, 80, 1, 150.00, '2024-12-05 01:17:09'),
(28, 81, 1, 150.00, '2024-12-05 01:24:17'),
(31, 86, 1, 150.00, '2024-12-05 02:25:08'),
(32, 87, 1, 150.00, '2024-12-05 03:11:37'),
(33, 88, 1, 150.00, '2024-12-14 10:10:24'),
(34, 89, 1, 1000.00, '2024-12-14 10:22:14');

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
(5, 'Locker', 150.00, 100, 81, 1, 2, 'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.', 'active', 0, '2024-12-03 14:35:06', '2024-12-14 10:22:15'),
(6, 'test', 100.00, 15, 15, 1, 1, 'test', 'active', 0, '2024-12-14 10:16:53', '2024-12-14 10:16:53');

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
(36, 75, 5, '2024-12-04', '2025-01-04', 150.00, 'active', 1, '2024-12-05 07:33:05', '2024-12-04 23:33:05', '2024-12-04 23:33:05'),
(37, 77, 5, '2024-12-04', '2025-01-04', 150.00, 'active', 1, '2024-12-05 07:44:49', '2024-12-04 23:44:49', '2024-12-04 23:44:49'),
(38, 78, 5, '2024-12-04', '2025-01-04', 150.00, 'active', 1, '2024-12-05 07:52:38', '2024-12-04 23:52:38', '2024-12-04 23:52:38'),
(39, 79, 5, '2024-12-05', '2025-01-05', 150.00, 'active', 1, '2024-12-05 08:13:43', '2024-12-05 00:13:43', '2024-12-05 00:13:43'),
(40, 80, 5, '2024-12-05', '2025-01-05', 150.00, 'active', 1, '2024-12-05 09:17:09', '2024-12-05 01:17:09', '2024-12-05 01:17:09'),
(41, 81, 5, '2024-12-05', '2025-01-05', 150.00, 'active', 1, '2024-12-05 09:24:17', '2024-12-05 01:24:17', '2024-12-05 01:24:17'),
(43, 87, 5, '2024-12-05', '2025-01-05', 150.00, 'active', 1, '2024-12-05 11:11:37', '2024-12-05 03:11:37', '2024-12-05 03:13:11'),
(44, 88, 5, '2024-12-14', '2025-01-14', 150.00, 'active', 1, '2024-12-14 18:10:24', '2024-12-14 10:10:24', '2024-12-14 10:10:24'),
(45, 89, 5, '2024-12-16', '2025-01-16', 150.00, 'active', 1, NULL, '2024-12-14 10:22:15', '2024-12-14 10:23:35');

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
(9, 'Jayson', 'Manager', 'cms_img/staff/675d5e6bdf11b_gallery3.jpg');

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
(75, 34, 40, 'confirmed', '2024-12-04 23:33:05'),
(77, 34, 43, 'confirmed', '2024-12-04 23:44:49'),
(78, 34, 44, 'confirmed', '2024-12-04 23:52:38'),
(79, 34, 45, 'confirmed', '2024-12-05 00:13:43'),
(80, 34, 46, 'confirmed', '2024-12-05 01:17:09'),
(81, 34, 47, 'confirmed', '2024-12-05 01:24:17'),
(85, NULL, 37, 'confirmed', '2024-12-05 01:51:53'),
(86, NULL, 48, 'confirmed', '2024-12-05 02:25:08'),
(87, 34, 50, 'confirmed', '2024-12-05 03:11:37'),
(88, 34, 51, 'confirmed', '2024-12-14 10:10:24'),
(89, NULL, 53, 'confirmed', '2024-12-14 10:22:14');

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
(34, 'admin', '$2y$10$jFClQzi6TkhZvQfTaXjDQOnqnNXOxRSehXfV14EiQ1l96GahuBT92', 1, 1, '2024-12-03 14:30:35', '2024-12-03 14:31:46'),
(35, 'coach', '$2y$10$pjk6.DxDk100djz9iZ.u2ul6brXXvm95yUbFDJmzBFAhfHDZQPrWC', 4, 1, '2024-12-03 14:31:06', '2024-12-03 14:31:51'),
(36, 'coach2', '$2y$10$u4vKNjQXzbx9dRW.S950meSICjRzNn12/0gjm2GdcPrSvH8qrIvO2', 4, 1, '2024-12-03 14:31:30', '2024-12-03 14:31:56'),
(37, 'gerby', '$2y$10$pkXiAolClIQkRwOURNRKLu8L0M4bhSn3YUrlFrLKfEzkpfr9aqBn2', 5, 1, '2024-12-04 23:25:33', '2024-12-04 23:25:33'),
(40, 'reign', '$2y$10$A8r73GMMN2ZqTzk/ThukOedbvZqRleQeZPsKzjskbyYhcjYbIZ5Da', 3, 1, '2024-12-04 23:33:05', '2024-12-04 23:33:05'),
(43, 'hui', '$2y$10$/JnNSykI2530K.IoWkBX2.ULQlLt.H4v7fQxSdqQTbm/uJvwsEBfe', 3, 1, '2024-12-04 23:44:49', '2024-12-04 23:44:49'),
(44, 'nosjay', '$2y$10$V9cyRvgJnuNxy5.hWC51Eu/RlOB623gXzNKeT5ClTgRh.Ixr1PeJe', 3, 1, '2024-12-04 23:52:38', '2024-12-04 23:52:38'),
(45, 'powerzone', '$2y$10$O461eXYx.W1dY0OHozLZv.kvIaRjITdL8fCWRv/ggGmtHGg99ZFG.', 3, 1, '2024-12-05 00:13:43', '2024-12-05 00:13:43'),
(46, 'jamal', '$2y$10$7u9yn2Tqobpg3W2Vb/XHDuWoa04OcnYLhGWv9PD21ch9li5eiGPWe', 3, 1, '2024-12-05 01:17:09', '2024-12-05 01:17:09'),
(47, 'sample', '$2y$10$9QlI4erahihjiukS1uTGuOB.lxrCwXYB37LjdOOYJ17J3coYInxWO', 3, 1, '2024-12-05 01:24:17', '2024-12-05 01:24:17'),
(48, 'jerd', '$2y$10$PDiG.fvlIsWwXer13.Pz0.JDP2dGIc9tljKzUJNijYazY68sAcbDe', 3, 1, '2024-12-05 02:21:22', '2024-12-05 02:25:55'),
(50, 'sample12', '$2y$10$syhkxJvjkkb2Npb1L0XrXeRmzGb1axiUNve9wDfd44cK9KEbH7DFm', 3, 1, '2024-12-05 03:11:37', '2024-12-05 03:11:37'),
(51, 'test', '$2y$10$zvK.hyQOaca2DyL7r2VzceiUkbAeg9DiG2LpoOIgrW/Z0I7ORXcr6', 3, 1, '2024-12-14 10:10:24', '2024-12-14 10:10:24'),
(52, 'tests', '$2y$10$A1HVgtPgSS4ogtseDpBgBOp.bzIH7oqX/PtHttcvND7Mr.gJu7sBS', 4, 1, '2024-12-14 10:18:37', '2024-12-14 10:18:37'),
(53, 'jade', '$2y$10$OR2KU6QZL1stSLfMGaJO4uUHyTSZ/94mhAq9xkZ8GpmxeTCGhFuT.', 3, 1, '2024-12-14 10:19:49', '2024-12-14 10:23:17');

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
(17, 85, 1, 'gerby hallasgo', '09562307646', '2024-12-05', NULL, 50.00, 0, 'pending');

-- --------------------------------------------------------

--
-- Table structure for table `website_content`
--

CREATE TABLE `website_content` (
  `id` int(11) NOT NULL,
  `section` varchar(50) NOT NULL,
  `company_name` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `location_name` varchar(255) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `website_content`
--

INSERT INTO `website_content` (`id`, `section`, `company_name`, `description`, `location_name`, `phone`, `email`, `latitude`, `longitude`) VALUES
(1, 'welcome', 'Xiao Long Bai\'s Gym Management System', 'Welcome to JC Powerzone, your ultimate destination for fitness and wellness! Whether you\'re a beginner taking the first steps on your fitness journey or a seasoned athlete looking to push your limits, we’re here to support you every step of the way. Step into a space where your health and goals come first—because at JC Powerzone, it’s not just about working out, it’s about building a lifestyle you love.', NULL, NULL, NULL, NULL, NULL),
(2, 'offers', 'Gym Offers', '💪 Exclusive Offers to Keep You Moving!\r\n\r\nNew Member Special: Get 50% off your first 3 months when you sign up today!\r\nBuddy Pass: Bring a friend for free every weekend and double the fun!\r\nStudent Discount: Stay fit and save big with 20% off all plans for students.\r\nAnnual Membership Perks: Free personal training session, gym kit, and unlimited access to all group classes.\r\nDon’t wait—these offers are available for a limited time only. Join today and take the first step toward a healthier you!', NULL, NULL, NULL, NULL, NULL),
(3, 'about_us', 'About Our Gym', 'At JC Powerzone, we believe fitness is for everyone. Founded in 2019, our mission is to create a welcoming and inclusive environment where individuals of all fitness levels can thrive.\r\n\r\nWith state-of-the-art equipment, certified trainers, and a wide variety of classes—including strength training, yoga, HIIT, and more—we cater to every goal and lifestyle. Our team is passionate about empowering you to achieve your personal best while enjoying the journey.\r\n\r\nMore than just a gym, we’re a community that inspires, motivates, and celebrates every milestone together. Let’s transform your fitness aspirations into reality at  JC Powerzone.', NULL, NULL, NULL, NULL, NULL),
(4, 'contact', NULL, NULL, 'Teachers\' Village, Cabaluay, Zamboanga City, Zamboanga Peninsula, 7000, Philippines', '09562307646', 'hallasgogerby@gmail.com', 6.98878798, 122.16355705);

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=38;

--
-- AUTO_INCREMENT for table `attendance_history`
--
ALTER TABLE `attendance_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=55;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `gym_offers`
--
ALTER TABLE `gym_offers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `memberships`
--
ALTER TABLE `memberships`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=95;

--
-- AUTO_INCREMENT for table `membership_plans`
--
ALTER TABLE `membership_plans`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `personal_details`
--
ALTER TABLE `personal_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=48;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `profile_photos`
--
ALTER TABLE `profile_photos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- AUTO_INCREMENT for table `programs`
--
ALTER TABLE `programs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `program_subscriptions`
--
ALTER TABLE `program_subscriptions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=43;

--
-- AUTO_INCREMENT for table `program_types`
--
ALTER TABLE `program_types`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `registration_records`
--
ALTER TABLE `registration_records`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35;

--
-- AUTO_INCREMENT for table `rental_services`
--
ALTER TABLE `rental_services`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `rental_subscriptions`
--
ALTER TABLE `rental_subscriptions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=46;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `staff`
--
ALTER TABLE `staff`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `transactions`
--
ALTER TABLE `transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=90;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=54;

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
