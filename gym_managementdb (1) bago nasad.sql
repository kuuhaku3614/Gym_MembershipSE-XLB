-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 06, 2025 at 08:53 PM
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
(15, 'na', 'na', 'cms_img/offers/67e4069c6dfff_475188469_645727571186517_4162057490619452205_n.jpg', '2025-03-26 13:52:28', '2025-03-26 13:52:28'),
(16, 'na', 'na', 'cms_img/offers/67e406ab53687_478039537_3004914946325748_6232555270493290314_n.jpg', '2025-03-26 13:52:43', '2025-03-26 13:52:43'),
(17, 'na', 'na', 'cms_img/offers/67e406d82cb07_3monthsPromo.jpg', '2025-03-26 13:53:28', '2025-03-26 13:53:28'),
(18, 'na', 'na', 'cms_img/offers/67e406f0aebe5_475188469_645727571186517_4162057490619452205_n.jpg', '2025-03-26 13:53:52', '2025-03-26 13:53:52'),
(20, 'na', 'na', 'cms_img/offers/67e406ff59f98_478039537_3004914946325748_6232555270493290314_n.jpg', '2025-03-26 13:54:07', '2025-03-26 13:54:07');

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
(190, 188, 20, '2025-03-17', '2025-03-26', 500.00, 'expired', 1, '2025-03-18 04:26:25', '2025-03-17 19:40:05', '2025-03-29 13:03:26'),
(191, 189, 20, '2025-03-17', '2025-03-26', 500.00, 'expired', 1, '2025-03-18 04:26:40', '2025-03-17 19:41:30', '2025-03-29 13:03:26'),
(192, 190, 20, '2025-03-17', '2025-03-26', 500.00, 'expired', 1, '2025-03-19 23:29:00', '2025-03-17 19:46:43', '2025-03-29 13:03:26'),
(193, 191, 21, '2025-03-18', '2025-04-18', 400.00, 'active', 1, '2025-03-18 04:26:25', '2025-03-17 20:18:11', '2025-04-01 17:02:47'),
(194, 192, 20, '2025-03-18', '2025-03-27', 500.00, 'expired', 1, '2025-03-18 09:16:21', '2025-03-18 01:16:06', '2025-03-29 13:03:26'),
(195, 194, 20, '2025-03-19', '2025-03-28', 500.00, 'expired', 1, '2025-03-19 23:29:00', '2025-03-19 04:02:45', '2025-03-29 13:03:26'),
(196, 195, 20, '2025-03-19', '2025-03-28', 500.00, 'expired', 1, '2025-03-19 23:29:00', '2025-03-19 04:02:45', '2025-03-29 13:03:26'),
(197, 196, 21, '2025-03-21', '2025-04-21', 400.00, 'active', 0, NULL, '2025-03-19 23:15:42', '2025-04-04 07:09:46'),
(198, 201, 20, '2025-04-01', '2025-04-02', 500.00, 'expired', 0, NULL, '2025-04-01 10:39:33', '2025-04-04 05:45:05'),
(199, 202, 21, '2025-04-02', '2025-05-02', 400.00, 'expiring', 1, '2025-04-06 20:07:34', '2025-04-01 14:14:57', '2025-04-06 12:07:34'),
(202, 206, 26, '2025-04-06', '2025-05-06', 100.00, 'active', 0, NULL, '2025-04-06 12:11:12', '2025-04-06 12:11:12');

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
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `image` varchar(255) NOT NULL
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
(26, 'student', 'standard', 100.00, 1, 2, 'asfsa', '2025-04-02', '2025-04-03', 'active', 0, '2025-04-02 12:07:23', '2025-04-02 12:08:25', '1743595705_jc_logo_2.png');

-- --------------------------------------------------------

--
-- Table structure for table `notification_reads`
--

CREATE TABLE `notification_reads` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `notification_type` varchar(20) NOT NULL,
  `notification_id` int(11) NOT NULL,
  `read_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notification_reads`
--

INSERT INTO `notification_reads` (`id`, `user_id`, `notification_type`, `notification_id`, `read_at`) VALUES
(293, 34, 'expiring_membership', 199, '2025-04-04 07:19:23'),
(294, 34, 'expired_membership', 198, '2025-04-04 07:19:23'),
(295, 34, 'expired_rental', 96, '2025-04-04 07:19:23'),
(296, 34, 'expired_membership', 195, '2025-04-04 07:19:23'),
(297, 34, 'expired_membership', 196, '2025-04-04 07:19:23'),
(298, 34, 'expired_rental', 94, '2025-04-04 07:19:23'),
(299, 34, 'expired_rental', 95, '2025-04-04 07:19:23'),
(300, 34, 'expired_membership', 194, '2025-04-04 07:19:23'),
(301, 34, 'expired_rental', 92, '2025-04-04 07:19:23'),
(302, 34, 'expired_rental', 93, '2025-04-04 07:19:23'),
(303, 34, 'expired_membership', 190, '2025-04-04 07:19:23'),
(304, 34, 'expired_membership', 191, '2025-04-04 07:19:23'),
(305, 34, 'expired_membership', 192, '2025-04-04 07:19:23'),
(306, 182, 'transactions', 203, '2025-04-04 07:22:28'),
(307, 182, 'transactions', 199, '2025-04-04 07:22:28'),
(308, 182, 'transactions', 196, '2025-04-04 07:22:28'),
(309, 182, 'transactions', 191, '2025-04-04 07:22:28'),
(310, 182, 'transactions', 188, '2025-04-04 07:22:28'),
(311, 182, 'memberships', 190, '2025-04-04 07:22:28'),
(312, 182, 'announcements', 6, '2025-04-04 07:22:28'),
(313, 182, 'announcements', 5, '2025-04-04 07:22:28'),
(314, 182, 'announcements', 3, '2025-04-04 07:22:28'),
(315, 34, 'announcements', 6, '2025-04-04 07:26:00'),
(316, 34, 'announcements', 5, '2025-04-04 07:26:00'),
(317, 34, 'announcements', 3, '2025-04-04 07:26:00'),
(318, 182, 'transactions', 204, '2025-04-04 13:39:23');

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
(30, 34, 'admin', '', 'admin', 'Male', '2024-12-03', '12312312312', '2024-12-03 06:30:35', '2024-12-03 06:30:35'),
(31, 35, 'coach', '', 'jay', 'Female', '2024-12-03', '12312312312', '2024-12-03 06:31:06', '2025-03-24 15:50:07'),
(32, 36, 'coach2', '', 'nos', 'Female', '2024-12-03', '12312312312', '2024-12-03 06:31:30', '2025-03-24 15:50:19'),
(35, 39, 'sofia', 'the', 'first', 'Female', '2000-08-11', '09876543211', '2025-01-19 05:46:55', '2025-01-19 05:46:55'),
(36, 40, 'kiel', 'the', 'great', 'Male', '2025-01-19', '09123456789', '2025-01-19 05:58:19', '2025-01-19 05:58:19'),
(47, 53, 'last', 'last', 'last', 'Male', '2025-01-02', '09752441070', '2025-01-29 09:28:33', '2025-01-29 09:28:33'),
(148, 161, 'singles', 'inferno', 's4', 'Male', '2002-03-03', '09752441070', '2025-02-04 07:47:35', '2025-02-04 07:47:35'),
(163, 179, 'hello', 'Carreon', 'hi', 'Male', '2025-03-09', '09998065631', '2025-03-09 22:27:52', '2025-03-09 22:27:52'),
(164, 180, 'walter', '', 'agudoy', 'Male', '2005-01-18', '09562307649', '2025-03-17 16:10:30', '2025-03-17 16:11:09'),
(165, 181, 'Hui Fon', '', 'Tulawe', 'Female', '2003-09-25', '09562307641', '2025-03-17 16:15:54', '2025-03-19 19:07:49'),
(166, 182, 'Gerby', NULL, 'Hallasgo', 'Male', '2025-03-18', '09750555564', '2025-03-17 19:40:05', '2025-03-17 19:40:05'),
(167, 183, 'Gerby2', NULL, 'Hallasgo', 'Male', '2025-03-18', '09750555564', '2025-03-17 19:41:30', '2025-03-17 19:41:30'),
(168, 184, 'Hui Fon', NULL, 'Hallasgo', 'Male', '2025-03-18', '09750555564', '2025-03-17 19:46:43', '2025-03-17 19:46:43'),
(169, 185, 'jam', NULL, 'mal', 'Female', '2025-03-18', '09750555564', '2025-03-18 01:16:06', '2025-03-18 01:16:06'),
(170, 186, 'Gerby', 'p', 'Hallasgo', 'Male', '2000-11-11', '09562307646', '2025-04-01 10:39:33', '2025-04-01 10:39:33');

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
(43, 180, 'uploads/profile_180_67d84b935aa39.png', 1, '2025-03-17 16:10:30'),
(44, 181, 'uploads/profile_181_67d84aba7a28b.png', 1, '2025-03-17 16:15:54'),
(45, 33, 'uploads/profile_33_67d84cb62cf60.jpg', 1, '2025-03-17 16:24:22'),
(46, 182, 'uploads/profile_182_67e7f4fd7fed8.jpg', 1, '2025-03-17 20:00:08'),
(47, 34, 'uploads/profile_34_67ef899652e9c.jpg', 1, '2025-04-04 07:26:14');

-- --------------------------------------------------------

--
-- Table structure for table `programs`
--

CREATE TABLE `programs` (
  `id` int(11) NOT NULL,
  `program_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `status` enum('active','inactive') NOT NULL,
  `is_removed` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `image` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `programs`
--

INSERT INTO `programs` (`id`, `program_name`, `description`, `status`, `is_removed`, `created_at`, `updated_at`, `image`) VALUES
(10, 'Coaching', 'none', 'active', 0, '2025-03-03 10:20:39', '2025-03-17 15:34:16', '1742225656_478039537_3004914946325748_6232555270493290314_n.jpg'),
(11, 'legs', '', 'active', 0, '2025-03-17 15:34:32', '2025-04-02 12:01:16', 'program_1742225672.jpg'),
(12, 'legs', 'leg days', 'inactive', 1, '2025-04-02 12:00:36', '2025-04-02 12:01:08', '1743595262_263a2ee5_b39c_4e56_ba5c_04b4a735321f.jpg');

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
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
(1, 150.00, '2025-04-02 12:06:55');

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

--
-- Dumping data for table `registration_records`
--

INSERT INTO `registration_records` (`id`, `transaction_id`, `registration_id`, `amount`, `is_paid`, `payment_date`, `created_at`) VALUES
(87, 188, 1, 150.00, 1, '2025-03-18 04:25:26', '2025-03-17 19:40:05'),
(88, 189, 1, 150.00, 1, '2025-03-18 04:26:40', '2025-03-17 19:41:30'),
(89, 190, 1, 150.00, 1, '2025-03-19 23:29:00', '2025-03-17 19:46:43'),
(90, 192, 1, 150.00, 1, '2025-03-18 09:16:21', '2025-03-18 01:16:06'),
(91, 201, 1, 100.00, 0, NULL, '2025-04-01 10:39:33');

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
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `image` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `rental_services`
--

INSERT INTO `rental_services` (`id`, `service_name`, `price`, `total_slots`, `available_slots`, `duration`, `duration_type_id`, `description`, `status`, `is_removed`, `created_at`, `updated_at`, `image`) VALUES
(5, 'Locker', 150.00, 100, 84, 10, 1, 'na', 'active', 0, '2024-12-03 14:35:06', '2025-03-17 20:18:11', '1742171066_475188469_645727571186517_4162057490619452205_n.jpg');

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
(92, 189, 5, '2025-03-17', '2025-03-27', 150.00, 'expired', 1, '2025-03-18 04:26:49', '2025-03-17 19:41:30', '2025-03-29 13:03:26'),
(93, 190, 5, '2025-03-17', '2025-03-27', 150.00, 'expired', 0, NULL, '2025-03-17 19:46:43', '2025-03-29 13:03:26'),
(94, 191, 5, '2025-03-18', '2025-03-28', 150.00, 'expired', 1, '2025-03-18 04:26:25', '2025-03-17 20:18:11', '2025-03-29 13:03:26'),
(95, 192, 5, '2025-03-18', '2025-03-28', 150.00, 'expired', 1, '2025-03-18 09:16:22', '2025-03-18 01:16:06', '2025-03-29 13:03:26'),
(96, 194, 5, '2025-03-19', '2025-03-29', 150.00, 'expired', 0, NULL, '2025-03-19 04:02:45', '2025-03-30 07:32:07'),
(98, 206, 5, '2025-04-06', '2025-04-16', 150.00, 'active', 0, NULL, '2025-04-06 12:11:12', '2025-04-06 12:11:12');

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
(10, 'User Banned', 'Banned user reign - Reasons: Inappropriate Behavior', '2025-03-17 14:01:13', 34),
(11, 'User Unbanned', 'Unbanned user reign', '2025-03-17 14:01:15', 34);

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
(183, 34, 53, 'confirmed', '2025-03-09 19:49:49'),
(185, 34, 33, 'confirmed', '2025-03-09 21:00:26'),
(186, NULL, 179, 'confirmed', '2025-03-09 22:27:52'),
(187, NULL, NULL, 'confirmed', '2025-03-17 13:59:53'),
(188, NULL, 182, 'confirmed', '2025-03-17 19:40:05'),
(189, NULL, 183, 'confirmed', '2025-03-17 19:41:30'),
(190, NULL, 184, 'confirmed', '2025-03-17 19:46:43'),
(191, NULL, 182, 'confirmed', '2025-03-17 20:18:11'),
(192, NULL, 185, 'confirmed', '2025-03-18 01:16:06'),
(193, NULL, NULL, 'confirmed', '2025-03-18 01:17:02'),
(194, NULL, 184, 'confirmed', '2025-03-19 04:02:45'),
(195, NULL, 184, 'confirmed', '2025-03-19 04:02:45'),
(196, NULL, 182, 'confirmed', '2025-03-19 23:15:42'),
(197, NULL, NULL, 'confirmed', '2025-03-29 10:02:16'),
(199, NULL, 182, 'confirmed', '2025-03-29 13:23:09'),
(200, NULL, NULL, 'cancelled', '2025-03-31 17:07:33'),
(201, NULL, 186, 'confirmed', '2025-04-01 10:39:33'),
(202, NULL, 40, 'confirmed', '2025-04-01 14:14:57'),
(203, NULL, 182, 'cancelled', '2025-04-02 14:32:36'),
(206, NULL, 182, 'confirmed', '2025-04-06 12:11:12');

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
(33, 'reign', '$2y$10$Gej8JD.h1Lo9enEaEtRNb.gXcarqSXwo9uJSk3wt7RV1j.nW1RcPS', 3, 1, '2024-12-03 06:29:47', '2025-03-17 14:01:15', 0, '2025-03-09 15:14:10'),
(34, 'admin', '$2y$10$jFClQzi6TkhZvQfTaXjDQOnqnNXOxRSehXfV14EiQ1l96GahuBT92', 1, 1, '2024-12-03 06:30:35', '2024-12-03 06:31:46', 0, '2025-03-09 15:14:10'),
(35, 'coach', '$2y$10$pjk6.DxDk100djz9iZ.u2ul6brXXvm95yUbFDJmzBFAhfHDZQPrWC', 4, 1, '2024-12-03 06:31:06', '2024-12-03 06:31:51', 0, '2025-03-09 15:14:10'),
(36, 'coach2', '$2y$10$u4vKNjQXzbx9dRW.S950meSICjRzNn12/0gjm2GdcPrSvH8qrIvO2', 4, 1, '2024-12-03 06:31:30', '2024-12-03 06:31:56', 0, '2025-03-09 15:14:10'),
(39, 'sofia', '$2y$10$aT9HT0aRE/DCbCaNU/T.z.6UFxe2NROVPK3IZi9jY9hGqJerRxQgC', 3, 1, '2025-01-19 05:46:55', '2025-01-19 05:46:55', 0, '2025-03-09 15:14:10'),
(40, 'kiel', '$2y$10$rnEhRal7OocaGpeHOJeP8OXzFAn91gf5ukjJtZpvqlrlFzT6ZCBi2', 3, 1, '2025-01-19 05:58:19', '2025-01-19 05:59:58', 0, '2025-03-09 15:14:10'),
(53, 'last', '$2y$10$36wnqKJZoLYGQ1IMGF349e.tOsXAYhsR8kXzF74nJjPqU/lSiYgCu', 3, 1, '2025-01-29 09:28:33', '2025-01-29 09:28:33', 0, '2025-03-09 15:14:10'),
(161, 'singles9359', '$2y$10$YrIZq4xXm1IyWC7g2nvG0.w0HQjVEVjWlMJ/toS5TyQ/liC/bN62q', 3, 1, '2025-02-04 07:47:35', '2025-02-04 07:47:35', 0, '2025-03-09 15:14:10'),
(179, 'hello4919', '$2y$10$Ko1Vls8e1j45jdeEuHRbMeTa/FahDXYIzVh/0BzbrNV6RbMlV.pRO', 5, 1, '2025-03-09 22:27:52', '2025-03-11 18:17:49', 0, '2025-03-09 22:27:52'),
(180, 'Walter123', '$2y$10$vuKybnDKLLCD2MfhBSey..iOJTKlt6n2ygHq/3bD5CfB9yRas0f1a', 3, 1, '2025-03-17 16:10:30', '2025-03-17 16:10:30', 0, '2025-03-17 16:10:30'),
(181, 'oddhui', '$2y$10$Syuq6AwT7ubnND1qezOUauURy.vSaZZ47MEhs8AHkCdqNoExfRGFu', 3, 1, '2025-03-17 16:15:54', '2025-03-17 16:15:54', 0, '2025-03-17 16:15:54'),
(182, 'Gerby123', '$2y$10$Xo7PCcFBKzOMb6YETPutxeQ3gxFzCgVqURYmty73AnoNgm71UwuZ.', 3, 1, '2025-03-17 19:40:05', '2025-04-04 13:04:02', 0, '2025-03-17 19:40:05'),
(183, 'gerby', '$2y$10$.701KxM3e4uXdai2GwiRTejkMjweyKjWVmeWccL31.TPUBX54sKJe', 3, 1, '2025-03-17 19:41:30', '2025-03-17 19:41:30', 0, '2025-03-17 19:41:30'),
(184, 'hui fon9085', '$2y$10$oOn6/1ygl6k1pSSUZdBMReJ3BDwXL9.06Be2CNP/VOIu63sSlBgwW', 3, 1, '2025-03-17 19:46:43', '2025-03-17 19:46:43', 0, '2025-03-17 19:46:43'),
(185, 'jam6322', '$2y$10$BMDRpViUicsck/LSkLxpceYc1k0YozzTtOFiGgFAAP7QMnfHioEZq', 3, 1, '2025-03-18 01:16:06', '2025-03-18 01:16:06', 0, '2025-03-18 01:16:06'),
(186, 'gerby3836', '$2y$10$fAHoAxnTu8rjPDKtpz6oseyfzctQ71aOxBno4v8cbElqZyCWwgqWe', 3, 1, '2025-04-01 10:39:33', '2025-04-01 10:39:33', 0, '2025-04-01 10:39:33');

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
(1, 100.00, 1, 1, '2025-03-17 13:59:42');

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
(21, 197, 1, 'Gerby', 'p', 'Hallasgo', '09562307646', '2025-03-29', '18:02:16', 100.00, 1, 'walked-in'),
(22, 199, 1, 'Gerby', NULL, 'Hallasgo', '09750555564', '2025-03-29', '22:08:15', 100.00, 1, 'walked-in'),
(24, 203, 1, 'Gerby', NULL, 'Hallasgo', '09750555564', '2025-04-03', NULL, 100.00, 0, 'pending');

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
(1, 'welcome', 'JC POWERZONE', 'Your Journey to Wellness Begins Here!\r\n\r\nAt JC POWERZONE Fitness Center, we believe that fitness is not just a routine; it&amp;amp;amp;amp;#039;s a lifestyle. Our state-of-the-art facility is dedicated to helping you achieve your health and fitness goals, no matter your level of experience.', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(2, 'offers', 'Gym Offers', 'Unlock your fitness potential with our exclusive deals! At JC POWERZONE, we believe in making fitness accessible and fun for everyone. Take advantage of our limited-time offers designed to help you get started on your health journey without breaking the bank.', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(3, 'about_us', 'About Our Gym', 'JC POWERZONE Fitness Center: Where Wellness Meets Excellence\r\n\r\nAt JC POWERZONE Fitness Center, we are more than just a gym; we are a community dedicated to fostering health, wellness, and personal growth. Founded with a passion for fitness and a commitment to excellence, our goal is to create an environment where everyone—from beginners to seasoned athletes—can thrive and reach their full potential.', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(4, 'contact', NULL, NULL, 'Zamboanga City Alliance Evangelical Church, 241, Mayor M.S. Jaldon Street, Canelar, Baliwasan, Zamboanga City, Zamboanga Peninsula, 7000, Philippines', '09562307645', 'jcpowerzone@gmail.com', 6.91305704, 122.07253037, NULL, NULL),
(5, 'logo', NULL, NULL, 'cms_img/logo/67e54f286a306_jc_logo_2.png', NULL, NULL, NULL, NULL, NULL, NULL),
(6, 'color', NULL, NULL, NULL, NULL, NULL, 0.99672163, 0.37516852, NULL, NULL),
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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

--
-- AUTO_INCREMENT for table `memberships`
--
ALTER TABLE `memberships`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=203;

--
-- AUTO_INCREMENT for table `membership_plans`
--
ALTER TABLE `membership_plans`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT for table `notification_reads`
--
ALTER TABLE `notification_reads`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=321;

--
-- AUTO_INCREMENT for table `personal_details`
--
ALTER TABLE `personal_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=171;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `profile_photos`
--
ALTER TABLE `profile_photos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=48;

--
-- AUTO_INCREMENT for table `programs`
--
ALTER TABLE `programs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `program_subscriptions`
--
ALTER TABLE `program_subscriptions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `program_subscription_schedule`
--
ALTER TABLE `program_subscription_schedule`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `rental_subscriptions`
--
ALTER TABLE `rental_subscriptions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=99;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `staff`
--
ALTER TABLE `staff`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `staff_activity_log`
--
ALTER TABLE `staff_activity_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `transactions`
--
ALTER TABLE `transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=207;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=187;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

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
