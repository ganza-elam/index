-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 29, 2026 at 09:00 AM
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
-- Database: `elam_system`
--

-- --------------------------------------------------------

--
-- Table structure for table `imibare`
--

CREATE TABLE `imibare` (
  `id` int(11) NOT NULL,
  `lesi` varchar(100) DEFAULT NULL,
  `intara_id` int(11) DEFAULT NULL,
  `itorero_id` int(11) DEFAULT NULL,
  `month` tinyint unsigned DEFAULT NULL,
  `ibindi` varchar(1000) DEFAULT NULL,
  `icyacumi` varchar(500) DEFAULT NULL,
  `icyacumi_cya_cms` varchar(500) DEFAULT NULL,
  `amaturo` varchar(500) DEFAULT NULL,
  `amaturo_bya_cms` varchar(500) DEFAULT NULL,
  `umusaruro` varchar(500) DEFAULT NULL,
  `ituro` varchar(500) DEFAULT NULL,
  `filide` varchar(500) DEFAULT NULL,
  `ss` varchar(500) DEFAULT NULL,
  `ubusonga` varchar(500) DEFAULT NULL,
  `mifem` varchar(500) DEFAULT NULL,
  `ja` varchar(500) DEFAULT NULL,
  `total` decimal(15,2) DEFAULT 0.00,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `imibare`
--

INSERT INTO `imibare` (`id`, `lesi`, `intara_id`, `itorero_id`, `month`, `ibindi`, `icyacumi`, `icyacumi_cya_cms`, `amaturo`, `amaturo_bya_cms`, `umusaruro`, `ituro`, `filide`, `ss`, `ubusonga`, `mifem`, `ja`, `total`, `created_at`) VALUES
(1, '1', 1, 2, 4, NULL, '10000,20000 = 30000', NULL, '3000,4000 = 7000 ÷ 2 = 3500', NULL, '4500,7500 = 12000', '7600,8400 = 16000', '6500,7899 = 14399', '3444,8999 = 12443', '76,78 = 154', '9999,759903 = 769902', '980,784 = 1764', 860162.00, '2026-04-23 21:58:34');

-- --------------------------------------------------------

--
-- Table structure for table `intara`
--

CREATE TABLE `intara` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `intara`
--

INSERT INTO `intara` (`id`, `name`, `created_at`, `updated_at`) VALUES
(1, 'Amajyepfo', '2026-04-23 21:56:58', '2026-04-23 21:56:58');

-- --------------------------------------------------------

--
-- Table structure for table `itorero`
--

CREATE TABLE `itorero` (
  `id` int(11) NOT NULL,
  `intara_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `itorero`
--

INSERT INTO `itorero` (`id`, `intara_id`, `name`, `created_at`, `updated_at`) VALUES
(1, 1, 'Gatovu', '2026-04-23 21:57:12', '2026-04-23 21:57:12'),
(2, 1, 'Bishenyi', '2026-04-23 21:57:23', '2026-04-23 21:57:23');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(100) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `created_at`, `updated_at`) VALUES
(1, 'elam', 'elam@gmail.com', '$2y$10$wLcwTQLPHFJiqPN.FV/I8OlVoHXkK7KtczAdWkvV0/CNbr98tr8uu', '2026-04-28 10:02:49', '2026-04-28 10:02:49');
INSERT INTO `users` (`id`, `username`, `email`, `password`, `created_at`, `updated_at`) VALUES
(2, 'niyonsaba', 'niyonsaba@gmail.com', '$2y$10$sFkFAiBsAcVv1yZBEnw7eOB8iXKoQzOnLrctIbRGo6Eupq8zRiKVe', '2026-04-28 10:10:00', '2026-04-28 10:10:00');
INSERT INTO `users` (`id`, `username`, `email`, `password`, `created_at`, `updated_at`) VALUES
(3, 'elamu', 'elamu@gmail.com', '$2y$10$cwM6zrK9Plx/j.XkqZJTbe0pTS6bEHYwRiwO7nv.ZiG8psuR9pEj6', '2026-04-28 10:20:00', '2026-04-28 10:20:00');



--
-- Indexes for dumped tables
--

--
-- Indexes for table `imibare`
--
ALTER TABLE `imibare`
  ADD PRIMARY KEY (`id`),
  ADD KEY `intara_id` (`intara_id`),
  ADD KEY `itorero_id` (`itorero_id`);

--
-- Indexes for table `intara`
--
ALTER TABLE `intara`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `itorero`
--
ALTER TABLE `itorero`
  ADD PRIMARY KEY (`id`),
  ADD KEY `intara_id` (`intara_id`);

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
-- AUTO_INCREMENT for table `imibare`
--
ALTER TABLE `imibare`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `intara`
--
ALTER TABLE `intara`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `itorero`
--
ALTER TABLE `itorero`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `imibare`
--
ALTER TABLE `imibare`
  ADD CONSTRAINT `imibare_ibfk_1` FOREIGN KEY (`intara_id`) REFERENCES `intara` (`id`),
  ADD CONSTRAINT `imibare_ibfk_2` FOREIGN KEY (`itorero_id`) REFERENCES `itorero` (`id`);

--
-- Constraints for table `itorero`
--
ALTER TABLE `itorero`
  ADD CONSTRAINT `itorero_ibfk_1` FOREIGN KEY (`intara_id`) REFERENCES `intara` (`id`) ON DELETE CASCADE;
COMMIT;


  
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;