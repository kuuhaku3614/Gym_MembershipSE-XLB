-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Feb 19, 2025 at 02:33 AM
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
-- Table structure for table `coach_availability`
--

CREATE TABLE `coach_availability` (
  `id` int(11) NOT NULL,
  `coach_program_type_id` int(11) NOT NULL,
  `day` enum('Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday') NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `coach_availability`
--

INSERT INTO `coach_availability` (`id`, `coach_program_type_id`, `day`, `start_time`, `end_time`, `created_at`, `updated_at`) VALUES
(1, 4, 'Monday', '08:00:00', '11:00:00', '2025-02-17 15:35:25', '2025-02-17 17:21:18'),
(2, 4, 'Tuesday', '08:00:00', '18:00:00', '2025-02-17 15:38:32', '2025-02-17 15:38:32'),
(3, 4, 'Monday', '13:00:00', '17:00:00', '2025-02-17 17:21:47', '2025-02-17 17:24:49'),
(4, 12, 'Monday', '06:00:00', '13:00:00', '2025-02-17 21:07:53', '2025-02-17 21:12:57'),
(5, 10, 'Monday', '06:00:00', '18:00:00', '2025-02-17 21:09:54', '2025-02-17 21:09:54'),
(6, 13, 'Wednesday', '06:11:00', '21:11:00', '2025-02-17 21:11:55', '2025-02-17 21:11:55'),
(7, 4, 'Wednesday', '08:00:00', '22:00:00', '2025-02-18 10:00:20', '2025-02-18 10:00:20');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `coach_availability`
--
ALTER TABLE `coach_availability`
  ADD PRIMARY KEY (`id`),
  ADD KEY `coach_availability_ibfk_1` (`coach_program_type_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `coach_availability`
--
ALTER TABLE `coach_availability`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `coach_availability`
--
ALTER TABLE `coach_availability`
  ADD CONSTRAINT `coach_availability_ibfk_1` FOREIGN KEY (`coach_program_type_id`) REFERENCES `coach_program_types` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
