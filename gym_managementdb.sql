-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 21, 2025 at 02:27 PM
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
(1, 'hehe', '2024-11-28', '18:35:00', 'activity', 0, '2024-11-28 10:35:48'),
(2, 'haha', '2024-11-28', '18:36:00', 'administrative', 0, '2024-11-28 10:36:12'),
(3, 'hehe', '2024-11-28', '18:35:00', 'activity', 1, '2024-11-28 10:35:48'),
(4, 'haha', '2024-11-28', '18:36:00', 'administrative', 0, '2024-11-28 10:36:12'),
(5, 'haha', '2025-03-18', '11:11:00', 'administrative', 1, '2025-03-17 16:21:34'),
(6, 'sample', '2025-03-19', '11:11:00', 'administrative', 1, '2025-03-19 11:20:20');

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

-- --------------------------------------------------------

--
-- Table structure for table `coach_group_schedule`
--

CREATE TABLE `coach_group_schedule` (
  `id` int(11) NOT NULL,
  `coach_program_type_id` int(11) NOT NULL,
  `capacity` int(11) NOT NULL,
  `day` enum('Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday') NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `coach_group_schedule`
--

INSERT INTO `coach_group_schedule` (`id`, `coach_program_type_id`, `capacity`, `day`, `start_time`, `end_time`, `price`, `created_at`, `updated_at`) VALUES
(7, 18, 12, 'Monday', '20:00:00', '22:00:00', 20.00, '2025-03-09 15:24:12', '2025-03-09 15:34:56');

-- --------------------------------------------------------

--
-- Table structure for table `coach_personal_schedule`
--

CREATE TABLE `coach_personal_schedule` (
  `id` int(11) NOT NULL,
  `coach_program_type_id` int(11) NOT NULL,
  `day` enum('Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday') NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `duration_rate` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `coach_personal_schedule`
--

INSERT INTO `coach_personal_schedule` (`id`, `coach_program_type_id`, `day`, `start_time`, `end_time`, `duration_rate`, `price`, `created_at`, `updated_at`) VALUES
(9, 17, 'Monday', '10:30:00', '12:00:00', 60, 100.00, '2025-03-09 15:25:41', '2025-03-09 15:25:41');

-- --------------------------------------------------------

--
-- Table structure for table `coach_program_types`
--

CREATE TABLE `coach_program_types` (
  `id` int(11) NOT NULL,
  `coach_id` int(11) NOT NULL,
  `program_id` int(11) NOT NULL,
  `type` enum('personal','group','','') NOT NULL,
  `description` text DEFAULT NULL,
  `status` enum('active','inactive','pending','') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `coach_program_types`
--

INSERT INTO `coach_program_types` (`id`, `coach_id`, `program_id`, `type`, `description`, `status`, `created_at`, `updated_at`) VALUES
(17, 35, 10, 'personal', 'ferson', 'active', '2025-03-09 15:18:18', '2025-03-19 22:38:58'),
(18, 35, 10, 'group', 'hakdog', 'active', '2025-03-09 15:22:42', '2025-03-19 22:38:58');

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
(18, 'cms_img/gallery/67e40610421f1_476787992_1387925502172232_7754806762146922739_n.jpg', ''),
(19, 'cms_img/gallery/67e4061820281_475751120_1663639264548208_3184291561432859418_n.jpg', ''),
(21, 'cms_img/gallery/67e406296d2eb_476583676_558958270508330_2625154709478558915_n.jpg', ''),
(22, 'cms_img/gallery/67e4064b7e90d_gallery5.jpg', '');

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
(11, '', '', 'cms_img/offers/68056826152ac_478039537_3004914946325748_6232555270493290314_n.jpg', '2025-04-20 21:33:26', '2025-04-20 21:33:26'),
(12, '', '', 'cms_img/offers/6805682ce3fd4_475188469_645727571186517_4162057490619452205_n.jpg', '2025-04-20 21:33:32', '2025-04-20 21:33:32');

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
(216, 221, 21, '2025-04-16', '2025-05-16', 400.00, 'active', 0, NULL, '2025-04-16 12:38:31', '2025-04-19 13:32:56');

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
  `description` varchar(255) DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `status` enum('active','inactive') NOT NULL,
  `is_removed` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `image` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `membership_plans`
--

INSERT INTO `membership_plans` (`id`, `plan_name`, `plan_type`, `price`, `duration`, `duration_type_id`, `description`, `start_date`, `end_date`, `status`, `is_removed`, `created_at`, `updated_at`, `image`) VALUES
(20, 'Regular', 'standard', 500.00, 9, 1, 'na', '2024-12-02', '2024-12-31', 'active', 0, '2024-12-03 14:33:24', '2025-04-02 12:05:57', '012eb9f6ceebb21355c6cb535c0d292e.jpg'),
(21, 'Christmas Prom', 'special', 400.00, 1, 2, 'na', '2024-12-03', '2024-12-31', 'active', 0, '2024-12-03 14:33:59', '2025-04-02 12:08:18', '1743595698_jc_logo_2.png'),
(23, 'hatdog', 'standard', 100.00, 1, 2, 'haha', '2025-01-19', '2025-01-21', 'inactive', 1, '2025-01-19 14:06:47', '2025-01-19 14:06:51', ''),
(24, '&lt;script&gt;alert(&#039;XSS Test&#039;);&lt;/script&gt;', 'special', 1.00, 1, 1, '&lt;script&gt;alert(&#039;XSS Test&#039;);&lt;/script&gt;', '2025-04-01', '2025-04-02', 'inactive', 1, '2025-03-31 17:41:44', '2025-03-31 17:41:51', '67ead3d8f031b_65d020b6f0906617.jpg'),
(25, 'student', 'standard', 1.00, 1, 2, 'wdqw', '2025-04-01', '2025-04-02', 'inactive', 1, '2025-03-31 17:44:01', '2025-03-31 17:45:54', '67ead461788ec_2051b65ebe415575.jpg'),
(26, 'student', 'special', 100.00, 1, 2, 'asfsa', '2025-04-02', '2025-04-03', 'active', 0, '2025-04-02 12:07:23', '2025-04-20 21:53:47', '1743595705_jc_logo_2.png'),
(27, 'double trouble', 'special', 1500.00, 2, 2, '', '2025-04-21', '2025-04-30', 'inactive', 1, '2025-04-20 21:55:17', '2025-04-21 08:55:13', '68056d4543ff4_jc_logo_2.png'),
(28, 'double trouble', 'standard', 1000.00, 2, 2, '', '2025-04-21', '2025-04-23', 'active', 0, '2025-04-21 08:55:47', '2025-04-21 08:55:47', '68060813a21ab_462536676_1103511134699240_5940032338491165384_n.jpg');

-- --------------------------------------------------------

--
-- Table structure for table `notification_reads`
--

CREATE TABLE `notification_reads` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `notification_type` varchar(50) NOT NULL,
  `notification_id` int(11) NOT NULL,
  `read_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notification_reads`
--

INSERT INTO `notification_reads` (`id`, `user_id`, `notification_type`, `notification_id`, `read_at`) VALUES
(419, 188, 'announcements', 6, '2025-04-21 12:23:50'),
(420, 188, 'announcements', 5, '2025-04-21 12:23:50'),
(421, 188, 'announcements', 3, '2025-04-21 12:23:50');

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
(29, 33, 'Reign', 'Carreon', 'Magno', 'Male', '2004-08-02', '12312312312', '2024-12-03 06:29:47', '2024-12-03 06:29:47'),
(31, 35, 'coach', 'de', 'jaysons', 'Female', '2024-12-03', '12312312312', '2024-12-03 06:31:06', '2025-04-21 12:03:21'),
(166, 182, 'Gerby', NULL, 'Hallasgo', 'Male', '2025-03-18', '09750555564', '2025-03-17 19:40:05', '2025-03-17 19:40:05'),
(172, 188, 'jerard', '', 'gedrald', 'Male', '2000-11-11', '09562307646', '2025-04-21 12:04:19', '2025-04-21 12:04:19');

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
(16, 'Rule 1', 'NA', 'cms_img/products/67e4016469f3d_458935169_984694713428384_7002601172150561970_n.jpg'),
(17, 'Rule 1', 'na', 'cms_img/products/67e40191e5cf4_458651943_825429683134590_790091522809318674_n.jpg'),
(18, 'Amino 2222', 'na', 'cms_img/products/67e4019ec96e1_458291718_495506383284388_4877429357951674690_n.jpg'),
(19, 'Rule 1', 'na', 'cms_img/products/67e401b2439e9_458376327_1454293141905163_1689636008975345724_n.jpg'),
(20, 'Isopure', 'na', 'cms_img/products/67e401bcc8b11_459022515_3849345398675399_6099214958007690975_n.jpg');

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
(45, 33, 'uploads/profile_33_67d84cb62cf60.jpg', 1, '2025-03-17 16:24:22'),
(46, 182, 'uploads/profile_182_6803d632d3087.png', 1, '2025-03-17 20:00:08'),
(47, 34, 'uploads/profile_34_67ef899652e9c.jpg', 1, '2025-04-04 07:26:14'),
(48, 35, 'uploads/profile_35_6803db1d62ee8.png', 1, '2025-04-19 06:28:03');

-- --------------------------------------------------------

--
-- Table structure for table `programs`
--

CREATE TABLE `programs` (
  `id` int(11) NOT NULL,
  `program_name` varchar(100) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `status` enum('active','inactive') NOT NULL,
  `is_removed` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `image` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `programs`
--

INSERT INTO `programs` (`id`, `program_name`, `description`, `status`, `is_removed`, `created_at`, `updated_at`, `image`) VALUES
(10, 'Coachings', '', 'inactive', 1, '2025-03-03 10:20:39', '2025-04-21 08:54:58', '1745186575_jc_logo_2.png'),
(11, 'Legs/Upper', 'leg day leg day', 'inactive', 1, '2025-03-17 15:34:32', '2025-04-20 22:23:49', '1745187210_jc_logo_2.png'),
(12, 'legs', 'leg days', 'inactive', 1, '2025-04-02 12:00:36', '2025-04-02 12:01:08', '1743595262_263a2ee5_b39c_4e56_ba5c_04b4a735321f.jpg'),
(13, 'abdominal training', '', 'inactive', 1, '2025-04-21 08:32:56', '2025-04-21 08:42:12', 'program_1745224376_jc_logo_2.png'),
(14, 'dqw', '', 'inactive', 1, '2025-04-21 08:54:05', '2025-04-21 08:54:10', ''),
(15, 'hahaha', '', 'inactive', 1, '2025-04-21 08:56:56', '2025-04-21 08:57:10', ''),
(16, 'dsa', '', 'inactive', 1, '2025-04-21 08:57:15', '2025-04-21 08:57:35', ''),
(17, 'fefw', '', 'inactive', 1, '2025-04-21 08:57:20', '2025-04-21 08:57:33', ''),
(18, 'fqq', '', 'inactive', 1, '2025-04-21 08:57:25', '2025-04-21 08:57:31', ''),
(19, 'GAG', '', 'inactive', 1, '2025-04-21 09:26:15', '2025-04-21 10:12:33', ''),
(20, 'EQWE', '', 'inactive', 1, '2025-04-21 09:36:18', '2025-04-21 10:12:35', ''),
(21, 'E12E12', 'DQW', 'inactive', 1, '2025-04-21 09:36:32', '2025-04-21 10:12:41', ''),
(22, 'EQWEQW', '', 'inactive', 1, '2025-04-21 09:37:23', '2025-04-21 10:12:43', ''),
(23, 'DQWD', '', 'inactive', 1, '2025-04-21 10:07:28', '2025-04-21 10:12:45', ''),
(24, 'DQWDQW', '', 'inactive', 1, '2025-04-21 10:08:28', '2025-04-21 10:12:47', ''),
(25, 'DQWDQW', 'DQW', 'inactive', 1, '2025-04-21 10:12:28', '2025-04-21 10:12:49', '');

-- --------------------------------------------------------

--
-- Table structure for table `program_subscriptions`
--

CREATE TABLE `program_subscriptions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `coach_program_type_id` int(11) NOT NULL,
  `status` varchar(20) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `transaction_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `program_subscriptions`
--

INSERT INTO `program_subscriptions` (`id`, `user_id`, `coach_program_type_id`, `status`, `created_at`, `updated_at`, `transaction_id`) VALUES
(9, 182, 17, 'active', '2025-04-16 12:38:31', '2025-04-18 19:43:18', 221);

-- --------------------------------------------------------

--
-- Table structure for table `program_subscription_schedule`
--

CREATE TABLE `program_subscription_schedule` (
  `id` int(11) NOT NULL,
  `program_subscription_id` int(11) NOT NULL,
  `coach_group_schedule_id` int(11) DEFAULT NULL,
  `coach_personal_schedule_id` int(11) DEFAULT NULL,
  `date` date NOT NULL,
  `day` enum('Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday') NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `is_paid` tinyint(1) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `status` enum('scheduled','cancelled','completed') NOT NULL DEFAULT 'scheduled',
  `cancellation_reason` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `program_subscription_schedule`
--

INSERT INTO `program_subscription_schedule` (`id`, `program_subscription_id`, `coach_group_schedule_id`, `coach_personal_schedule_id`, `date`, `day`, `start_time`, `end_time`, `amount`, `is_paid`, `created_at`, `updated_at`, `status`, `cancellation_reason`) VALUES
(5, 9, NULL, 9, '2025-04-21', 'Monday', '10:30:00', '11:30:00', 100.00, 0, '2025-04-16 12:38:31', '2025-04-19 16:01:43', 'scheduled', ''),
(6, 9, NULL, 9, '2025-04-28', 'Monday', '10:30:00', '11:30:00', 100.00, 0, '2025-04-16 12:38:31', '2025-04-19 16:01:50', 'scheduled', ''),
(7, 9, NULL, 9, '2025-05-05', 'Monday', '10:30:00', '11:30:00', 100.00, 0, '2025-04-16 12:38:31', '2025-04-16 12:38:31', 'scheduled', NULL),
(8, 9, NULL, 9, '2025-05-12', 'Monday', '10:30:00', '11:30:00', 100.00, 0, '2025-04-16 12:38:31', '2025-04-16 12:38:31', 'scheduled', NULL);

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
(1, 200.00, '2025-04-21 08:30:34');

-- --------------------------------------------------------

--
-- Table structure for table `registration_records`
--

CREATE TABLE `registration_records` (
  `id` int(11) NOT NULL,
  `transaction_id` int(11) NOT NULL,
  `registration_id` int(11) NOT NULL DEFAULT 1,
  `amount` decimal(10,2) NOT NULL,
  `is_paid` tinyint(1) NOT NULL,
  `payment_date` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
  `description` varchar(255) DEFAULT NULL,
  `status` enum('active','inactive') NOT NULL,
  `is_removed` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `image` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `rental_services`
--

INSERT INTO `rental_services` (`id`, `service_name`, `price`, `total_slots`, `available_slots`, `duration`, `duration_type_id`, `description`, `status`, `is_removed`, `created_at`, `updated_at`, `image`) VALUES
(5, 'Locker', 150.00, 100, 84, 20, 1, 'na', 'inactive', 1, '2024-12-03 14:35:06', '2025-04-21 08:55:03', '1744807327_461323753_504421532393159_5499801553050918367_n.jpg'),
(6, 'sauna', 200.00, 2, 2, 1, 1, '', 'inactive', 1, '2025-04-20 22:12:33', '2025-04-21 08:34:05', '6805715131869_jc_logo_2.png'),
(7, 'sauna', 400.00, 2, 2, 1, 1, '', 'inactive', 1, '2025-04-21 08:44:49', '2025-04-21 08:53:17', '1745225585_icon_xlb.png'),
(8, 'sauna', 400.00, 1, 1, 1, 1, '', 'inactive', 1, '2025-04-21 11:05:07', '2025-04-21 11:05:23', '1745233520_462536676_1103511134699240_5940032338491165384_n.jpg'),
(9, 'WDQ', 1.00, 1, 1, 1, 1, '', 'inactive', 1, '2025-04-21 11:06:26', '2025-04-21 11:06:30', NULL),
(10, 'sauna', 1.00, 1, 1, 1, 1, '', 'inactive', 1, '2025-04-21 11:09:00', '2025-04-21 11:09:03', NULL);

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
(6, 'coach/staff'),
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
(14, 'Jayson', 'Coach', 'cms_img/staff/67e4063e478c0_gallery3.jpg'),
(15, 'Walter', 'Coach', 'cms_img/staff/67e40665075c2_474274014_1291475002070511_6598343183032821899_n.png'),
(16, 'Ate', 'Staff', 'cms_img/staff/67e4067762bd3_477906399_1141990593946324_8140196602381044715_n.jpg');

-- --------------------------------------------------------

--
-- Table structure for table `staff_activity_log`
--

CREATE TABLE `staff_activity_log` (
  `id` int(11) NOT NULL,
  `activity` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp(),
  `staff_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `staff_activity_log`
--

INSERT INTO `staff_activity_log` (`id`, `activity`, `description`, `timestamp`, `staff_id`) VALUES
(97, 'Update Walk-in Price', 'Updated walk-in price to ₱70.00', '2025-04-21 12:07:34', 188);

-- --------------------------------------------------------

--
-- Table structure for table `system_controls`
--

CREATE TABLE `system_controls` (
  `key_name` varchar(50) NOT NULL,
  `value` text NOT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `system_controls`
--

INSERT INTO `system_controls` (`key_name`, `value`, `updated_at`) VALUES
('last_attendance_reset', '2025-03-31 11:35:28', '2025-03-31 09:35:28'),
('last_missed_attendance_record', '2025-02-17 05:43:17', '2025-02-17 04:43:17');

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
(215, NULL, 182, 'confirmed', '2025-04-14 20:08:58'),
(220, NULL, 182, 'confirmed', '2025-04-14 20:58:24'),
(221, NULL, 182, 'cancelled', '2025-04-16 12:38:31'),
(223, NULL, NULL, 'confirmed', '2025-04-21 11:43:46');

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
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `is_banned` tinyint(1) DEFAULT 0,
  `last_password_change` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `role_id`, `is_active`, `created_at`, `updated_at`, `is_banned`, `last_password_change`) VALUES
(33, 'reign', '$2y$10$Gej8JD.h1Lo9enEaEtRNb.gXcarqSXwo9uJSk3wt7RV1j.nW1RcPS', 3, 1, '2024-12-03 06:29:47', '2025-04-20 22:22:41', 0, '2025-03-09 15:14:10'),
(34, 'administrator', '$2y$10$cFUAP6seiyxgJpd/kpTK1uYhnaIOiULmq3n/mWwVaecQK/BbpKXHu', 1, 1, '2024-12-03 06:30:35', '2025-04-21 12:01:58', 0, '2025-03-09 15:14:10'),
(35, 'coachjay', '$2y$10$pjk6.DxDk100djz9iZ.u2ul6brXXvm95yUbFDJmzBFAhfHDZQPrWC', 6, 1, '2024-12-03 06:31:06', '2025-04-21 12:03:32', 0, '2025-03-09 15:14:10'),
(182, 'Gerby123', '$2y$10$Xo7PCcFBKzOMb6YETPutxeQ3gxFzCgVqURYmty73AnoNgm71UwuZ.', 3, 1, '2025-03-17 19:40:05', '2025-04-19 16:58:08', 0, '2025-03-17 19:40:05'),
(188, 'jerard', '$2y$10$mcFX754r6lXgb4rvKfM48.ZTc.l6Ys9.436nsXmyAzasqDr7Kx2Gy', 6, 1, '2025-04-21 12:04:19', '2025-04-21 12:04:19', 0, '2025-04-21 12:04:19');

-- --------------------------------------------------------

--
-- Table structure for table `verification_codes`
--

CREATE TABLE `verification_codes` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `code` varchar(10) NOT NULL,
  `created_at` datetime NOT NULL,
  `expires_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
(1, 70.00, 1, 1, '2025-04-21 12:07:34');

-- --------------------------------------------------------

--
-- Table structure for table `walk_in_records`
--

CREATE TABLE `walk_in_records` (
  `id` int(11) NOT NULL,
  `transaction_id` int(11) NOT NULL,
  `walk_in_id` int(11) NOT NULL DEFAULT 1,
  `first_name` varchar(50) NOT NULL,
  `middle_name` varchar(50) DEFAULT NULL,
  `last_name` varchar(50) NOT NULL,
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

INSERT INTO `walk_in_records` (`id`, `transaction_id`, `walk_in_id`, `first_name`, `middle_name`, `last_name`, `phone_number`, `date`, `time_in`, `amount`, `is_paid`, `status`) VALUES
(29, 223, 1, 'Gerbyt', '', 'Hallasgow', '09562307646', '2025-04-21', '19:43:46', 50.00, 1, 'walked-in');

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
  `email` varchar(100) DEFAULT NULL,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `days` varchar(255) DEFAULT NULL,
  `hours` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `website_content`
--

INSERT INTO `website_content` (`id`, `section`, `company_name`, `description`, `location`, `phone`, `email`, `latitude`, `longitude`, `days`, `hours`) VALUES
(1, 'welcome', 'JC POWERZONE', 'Your Journey to Wellness Begins Here!\r\n\r\nAt JC POWERZONE Fitness Center, we believe that fitness is not just a routine, it&#039;s a lifestyle. Our state-of-the-art facility is dedicated to helping you achieve your health and fitness goals, no matter your level of experience.', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(2, 'offers', 'Gym Offers', 'Unlock your fitness potential with our exclusive deals! At JC POWERZONE, we believe in making fitness accessible and fun for everyone. Take advantage of our limited-time offers designed to help you get started on your health journey without breaking the bank.', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(3, 'about_us', 'About Our Gym', 'JC POWERZONE Fitness Center: Where Wellness Meets Excellence\r\n\r\nAt JC POWERZONE Fitness Center, we are more than just a gym; we are a community dedicated to fostering health, wellness, and personal growth. Founded with a passion for fitness and a commitment to excellence, our goal is to create an environment where everyone—from beginners to seasoned athletes—can thrive and reach their full potential.', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(4, 'contact', NULL, NULL, 'Mayor M.S. Jaldon Street, Canelar, Zamboanga City, Zamboanga Peninsula, 7000, Philippines', '09562307645', 'jcpowerzone@gmail.com', 6.91316887, 122.07250618, NULL, NULL),
(5, 'logo', NULL, NULL, 'cms_img/logo/67e54f286a306_jc_logo_2.png', NULL, NULL, NULL, NULL, NULL, NULL),
(6, 'color', NULL, NULL, NULL, NULL, NULL, 0.96099883, 0.69537364, NULL, NULL),
(7, 'schedule', NULL, '', NULL, NULL, NULL, 0.00000000, 0.00000000, 'Monday - Sunday', '05:30 AM - 10:00 PM');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `announcements`
--
ALTER TABLE `announcements`
  ADD PRIMARY KEY (`id`);

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
-- Indexes for table `coach_group_schedule`
--
ALTER TABLE `coach_group_schedule`
  ADD PRIMARY KEY (`id`),
  ADD KEY `coach_group_schedule_ibfk_1` (`coach_program_type_id`);

--
-- Indexes for table `coach_personal_schedule`
--
ALTER TABLE `coach_personal_schedule`
  ADD PRIMARY KEY (`id`),
  ADD KEY `coach_personal_schedule_ibfk_1` (`coach_program_type_id`);

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
-- Indexes for table `notification_reads`
--
ALTER TABLE `notification_reads`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_read` (`user_id`,`notification_type`,`notification_id`);

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
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `program_subscriptions`
--
ALTER TABLE `program_subscriptions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `program_subscriptions_ibfk_1` (`user_id`),
  ADD KEY `program_subscriptions_ibfk_2` (`coach_program_type_id`),
  ADD KEY `fk_transaction_id` (`transaction_id`);

--
-- Indexes for table `program_subscription_schedule`
--
ALTER TABLE `program_subscription_schedule`
  ADD PRIMARY KEY (`id`),
  ADD KEY `program_subscription_schedule_ibfk_1` (`coach_group_schedule_id`),
  ADD KEY `program_subscription_schedule_ibfk_2` (`coach_personal_schedule_id`),
  ADD KEY `program_subscription_schedule_ibfk_3` (`program_subscription_id`);

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
-- Indexes for table `staff_activity_log`
--
ALTER TABLE `staff_activity_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_activity` (`activity`),
  ADD KEY `staff_id` (`staff_id`);

--
-- Indexes for table `system_controls`
--
ALTER TABLE `system_controls`
  ADD PRIMARY KEY (`key_name`);

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
-- Indexes for table `verification_codes`
--
ALTER TABLE `verification_codes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

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
-- AUTO_INCREMENT for table `announcements`
--
ALTER TABLE `announcements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `attendance`
--
ALTER TABLE `attendance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=43;

--
-- AUTO_INCREMENT for table `attendance_history`
--
ALTER TABLE `attendance_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=78;

--
-- AUTO_INCREMENT for table `coach_group_schedule`
--
ALTER TABLE `coach_group_schedule`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `coach_personal_schedule`
--
ALTER TABLE `coach_personal_schedule`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `coach_program_types`
--
ALTER TABLE `coach_program_types`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `duration_types`
--
ALTER TABLE `duration_types`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `gallery_images`
--
ALTER TABLE `gallery_images`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT for table `gym_offers`
--
ALTER TABLE `gym_offers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `memberships`
--
ALTER TABLE `memberships`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=217;

--
-- AUTO_INCREMENT for table `membership_plans`
--
ALTER TABLE `membership_plans`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT for table `notification_reads`
--
ALTER TABLE `notification_reads`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=422;

--
-- AUTO_INCREMENT for table `personal_details`
--
ALTER TABLE `personal_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=173;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `profile_photos`
--
ALTER TABLE `profile_photos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=50;

--
-- AUTO_INCREMENT for table `programs`
--
ALTER TABLE `programs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `program_subscriptions`
--
ALTER TABLE `program_subscriptions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `program_subscription_schedule`
--
ALTER TABLE `program_subscription_schedule`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=92;

--
-- AUTO_INCREMENT for table `rental_services`
--
ALTER TABLE `rental_services`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `rental_subscriptions`
--
ALTER TABLE `rental_subscriptions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=99;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `staff`
--
ALTER TABLE `staff`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `staff_activity_log`
--
ALTER TABLE `staff_activity_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=98;

--
-- AUTO_INCREMENT for table `transactions`
--
ALTER TABLE `transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=224;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=189;

--
-- AUTO_INCREMENT for table `verification_codes`
--
ALTER TABLE `verification_codes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `walk_in`
--
ALTER TABLE `walk_in`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `walk_in_records`
--
ALTER TABLE `walk_in_records`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT for table `website_content`
--
ALTER TABLE `website_content`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

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
-- Constraints for table `coach_group_schedule`
--
ALTER TABLE `coach_group_schedule`
  ADD CONSTRAINT `coach_group_schedule_ibfk_1` FOREIGN KEY (`coach_program_type_id`) REFERENCES `coach_program_types` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `coach_personal_schedule`
--
ALTER TABLE `coach_personal_schedule`
  ADD CONSTRAINT `coach_personal_schedule_ibfk_1` FOREIGN KEY (`coach_program_type_id`) REFERENCES `coach_program_types` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `coach_program_types`
--
ALTER TABLE `coach_program_types`
  ADD CONSTRAINT `coach_program_types_ibfk_1` FOREIGN KEY (`coach_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `coach_program_types_ibfk_2` FOREIGN KEY (`program_id`) REFERENCES `programs` (`id`) ON DELETE CASCADE;

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
-- Constraints for table `program_subscriptions`
--
ALTER TABLE `program_subscriptions`
  ADD CONSTRAINT `fk_transaction_id` FOREIGN KEY (`transaction_id`) REFERENCES `transactions` (`id`),
  ADD CONSTRAINT `program_subscriptions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `program_subscriptions_ibfk_2` FOREIGN KEY (`coach_program_type_id`) REFERENCES `coach_program_types` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `program_subscription_schedule`
--
ALTER TABLE `program_subscription_schedule`
  ADD CONSTRAINT `program_subscription_schedule_ibfk_1` FOREIGN KEY (`coach_group_schedule_id`) REFERENCES `coach_group_schedule` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `program_subscription_schedule_ibfk_2` FOREIGN KEY (`coach_personal_schedule_id`) REFERENCES `coach_personal_schedule` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `program_subscription_schedule_ibfk_3` FOREIGN KEY (`program_subscription_id`) REFERENCES `program_subscriptions` (`id`) ON DELETE CASCADE;

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
-- Constraints for table `staff_activity_log`
--
ALTER TABLE `staff_activity_log`
  ADD CONSTRAINT `staff_activity_log_ibfk_1` FOREIGN KEY (`staff_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION;

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
-- Constraints for table `verification_codes`
--
ALTER TABLE `verification_codes`
  ADD CONSTRAINT `verification_codes_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

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
