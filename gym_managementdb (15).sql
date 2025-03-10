-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 09, 2025 at 04:14 PM
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
(0, 'haha', '2024-11-28', '18:36:00', 'administrative', 1, '2024-11-28 10:36:12'),
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

-- --------------------------------------------------------

--
-- Table structure for table `coach_program_types`
--

CREATE TABLE `coach_program_types` (
  `id` int(11) NOT NULL,
  `coach_id` int(11) NOT NULL,
  `program_id` int(11) NOT NULL,
  `type` enum('personal','group','','') NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `description` text DEFAULT NULL,
  `status` enum('active','inactive','pending','') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
(9, 'cms_img/gallery/67b433e84aa2a_67b1d7b91e83f_gallery5.jpg', ''),
(10, 'cms_img/gallery/67b433fa6dbea_67b1e69622c99_475751120_1663639264548208_3184291561432859418_n.jpg', ''),
(11, 'cms_img/gallery/67b4340db3a2e_67b1e69ab11bd_473183774_1361583851952752_6323395937252987995_n.jpg', ''),
(12, 'cms_img/gallery/67b4341ec792f_67b1e91a4395c_476787992_1387925502172232_7754806762146922739_n.jpg', '');

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
(4, 'Coaching', 'coaching for life', 'cms_img/offers/67b4322c39d72_67b1e92b5dae7_475188469_645727571186517_4162057490619452205_n.jpg', '2025-02-18 07:09:32', '2025-02-18 07:09:32'),
(5, 'Student', 'A special offer for students', 'cms_img/offers/67b4324eb069b_67b1e948e8c69_478039537_3004914946325748_6232555270493290314_n.jpg', '2025-02-18 07:10:06', '2025-02-18 07:10:06'),
(7, 'Summer Promo', 'A special offer for this summer only', 'cms_img/offers/67b4329020274_67b432724a462_67500b8c14e9b_3months Promo.jpg', '2025-02-18 07:11:12', '2025-02-18 07:11:12');

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
(20, 'Regular', 'standard', 500.00, 9, 1, 'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.', '2024-12-02', '2024-12-31', 'active', 0, '2024-12-03 14:33:24', '2025-02-02 18:11:02'),
(21, 'Christmas Prom', 'special', 400.00, 1, 2, 'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.', '2024-12-03', '2024-12-31', 'active', 0, '2024-12-03 14:33:59', '2025-01-19 14:06:24'),
(23, 'hatdog', 'standard', 100.00, 1, 2, 'haha', '2025-01-19', '2025-01-21', 'inactive', 1, '2025-01-19 14:06:47', '2025-01-19 14:06:51');

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
(10, 'amino', 'good for the muscles', 'cms_img/products/67b4333669292_67b1e519bf808_458291718_495506383284388_4877429357951674690_n.jpg'),
(11, 'product', 'supplement', 'cms_img/products/67b4334f7539d_67b1e4e95ddfb_459022515_3849345398675399_6099214958007690975_n.jpg'),
(12, 'product 2', 'vitamins', 'cms_img/products/67b43364888d3_67b1e4f32684d_458651943_825429683134590_790091522809318674_n.jpg'),
(13, 'product 3', 'protein', 'cms_img/products/67b433766acab_67b1e4d6a8e2e_458293900_845031824277780_8545647259483522998_n.jpg');

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

-- --------------------------------------------------------

--
-- Table structure for table `programs`
--

CREATE TABLE `programs` (
  `id` int(11) NOT NULL,
  `program_name` varchar(100) NOT NULL,
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

INSERT INTO `programs` (`id`, `program_name`, `duration`, `duration_type_id`, `description`, `status`, `is_removed`, `created_at`, `updated_at`) VALUES
(10, 'Coaching', 10, 1, 'none', 'active', 0, '2025-03-03 10:20:39', '2025-03-03 10:20:39');

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
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
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
(1, 200.00, '2025-01-19 15:29:07');

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
(5, 'Locker', 150.00, 100, 85, 10, 1, 'Lorem ipsum dolor sit amet, ', 'active', 0, '2024-12-03 14:35:06', '2025-02-18 08:33:26');

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
(9, 'Jayson', 'Gym Owner', 'cms_img/staff/67b433b6a1239_67a5d7447ef3c_gallery3.jpg'),
(10, 'Mikaela', 'Staff', 'cms_img/staff/67b433c89ee5b_67b1e8f69d8fb_477906399_1141990593946324_8140196602381044715_n.jpg'),
(11, 'walter', 'coach', 'cms_img/staff/67b433d717274_67b1e90d1fee0_474274014_1291475002070511_6598343183032821899_n.png');

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
('last_attendance_reset', '2025-02-19 02:30:00', '2025-02-19 01:30:00'),
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
(33, 'reign', '$2y$10$Gej8JD.h1Lo9enEaEtRNb.gXcarqSXwo9uJSk3wt7RV1j.nW1RcPS', 3, 1, '2024-12-03 06:29:47', '2025-01-30 18:09:28', 0, '2025-03-09 15:14:10'),
(34, 'admin', '$2y$10$jFClQzi6TkhZvQfTaXjDQOnqnNXOxRSehXfV14EiQ1l96GahuBT92', 1, 1, '2024-12-03 06:30:35', '2024-12-03 06:31:46', 0, '2025-03-09 15:14:10'),
(35, 'coach', '$2y$10$pjk6.DxDk100djz9iZ.u2ul6brXXvm95yUbFDJmzBFAhfHDZQPrWC', 4, 1, '2024-12-03 06:31:06', '2024-12-03 06:31:51', 0, '2025-03-09 15:14:10'),
(36, 'coach2', '$2y$10$u4vKNjQXzbx9dRW.S950meSICjRzNn12/0gjm2GdcPrSvH8qrIvO2', 4, 1, '2024-12-03 06:31:30', '2024-12-03 06:31:56', 0, '2025-03-09 15:14:10'),
(39, 'sofia', '$2y$10$aT9HT0aRE/DCbCaNU/T.z.6UFxe2NROVPK3IZi9jY9hGqJerRxQgC', 3, 1, '2025-01-19 05:46:55', '2025-01-19 05:46:55', 0, '2025-03-09 15:14:10'),
(40, 'kiel', '$2y$10$rnEhRal7OocaGpeHOJeP8OXzFAn91gf5ukjJtZpvqlrlFzT6ZCBi2', 3, 1, '2025-01-19 05:58:19', '2025-01-19 05:59:58', 0, '2025-03-09 15:14:10'),
(53, 'last', '$2y$10$36wnqKJZoLYGQ1IMGF349e.tOsXAYhsR8kXzF74nJjPqU/lSiYgCu', 3, 1, '2025-01-29 09:28:33', '2025-01-29 09:28:33', 0, '2025-03-09 15:14:10'),
(161, 'singles9359', '$2y$10$YrIZq4xXm1IyWC7g2nvG0.w0HQjVEVjWlMJ/toS5TyQ/liC/bN62q', 3, 1, '2025-02-04 07:47:35', '2025-02-04 07:47:35', 0, '2025-03-09 15:14:10');

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
  `longitude` decimal(11,8) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `website_content`
--

INSERT INTO `website_content` (`id`, `section`, `company_name`, `description`, `location`, `phone`, `email`, `latitude`, `longitude`) VALUES
(1, 'welcome', 'JC POWERZONE', 'Your Journey to Wellness Begins Here!\r\n\r\nAt Xiao Long Bai Fitness Center, we believe that fitness is not just a routine; it\'s a lifestyle. Our state-of-the-art facility is dedicated to helping you achieve your health and fitness goals, no matter your level of experience.', NULL, NULL, NULL, NULL, NULL),
(2, 'offers', 'Gym Offers', 'Unlock your fitness potential with our exclusive deals! At JC PowerZone, we believe in making fitness accessible and fun for everyone. Take advantage of our limited-time offers designed to help you get started on your health journey without breaking the bank.', NULL, NULL, NULL, NULL, NULL),
(3, 'about_us', 'About Our Gym', 'Xiao Long Bai Fitness Center: Where Wellness Meets Excellence\r\n\r\nAt Xiao Long Bai Fitness Center, we are more than just a gym; we are a community dedicated to fostering health, wellness, and personal growth. Founded with a passion for fitness and a commitment to excellence, our goal is to create an environment where everyone—from beginners to seasoned athletes—can thrive and reach their full potential.', NULL, NULL, NULL, NULL, NULL),
(4, 'contact', NULL, NULL, 'Mayor M.S. Jaldon Street, Baliwasan, Zamboanga City, Philippines', '09562307646', 'jcpowerzone@gmail.com', 6.91308407, 122.07251215);

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
  ADD PRIMARY KEY (`id`),
  ADD KEY `duration_type_id` (`duration_type_id`);

--
-- Indexes for table `program_subscriptions`
--
ALTER TABLE `program_subscriptions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `program_subscriptions_ibfk_1` (`user_id`),
  ADD KEY `program_subscriptions_ibfk_2` (`coach_program_type_id`);

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `coach_personal_schedule`
--
ALTER TABLE `coach_personal_schedule`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `coach_program_types`
--
ALTER TABLE `coach_program_types`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `duration_types`
--
ALTER TABLE `duration_types`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `gallery_images`
--
ALTER TABLE `gallery_images`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `gym_offers`
--
ALTER TABLE `gym_offers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `memberships`
--
ALTER TABLE `memberships`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=178;

--
-- AUTO_INCREMENT for table `membership_plans`
--
ALTER TABLE `membership_plans`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `notification_reads`
--
ALTER TABLE `notification_reads`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

--
-- AUTO_INCREMENT for table `personal_details`
--
ALTER TABLE `personal_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=163;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `profile_photos`
--
ALTER TABLE `profile_photos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=43;

--
-- AUTO_INCREMENT for table `programs`
--
ALTER TABLE `programs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=86;

--
-- AUTO_INCREMENT for table `rental_services`
--
ALTER TABLE `rental_services`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `rental_subscriptions`
--
ALTER TABLE `rental_subscriptions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=86;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `staff`
--
ALTER TABLE `staff`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `staff_activity_log`
--
ALTER TABLE `staff_activity_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `transactions`
--
ALTER TABLE `transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=182;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=179;

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
-- Constraints for table `programs`
--
ALTER TABLE `programs`
  ADD CONSTRAINT `programs_ibfk_2` FOREIGN KEY (`duration_type_id`) REFERENCES `duration_types` (`id`);

--
-- Constraints for table `program_subscriptions`
--
ALTER TABLE `program_subscriptions`
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
