-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 05, 2025 at 02:40 AM
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
-- Database: `ski_rental`
--

-- --------------------------------------------------------

--
-- Table structure for table `equipment`
--

CREATE TABLE `equipment` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `category` varchar(50) NOT NULL,
  `size` varchar(50) DEFAULT NULL,
  `daily_rate` decimal(8,2) NOT NULL,
  `status` enum('available','rented','maintenance') NOT NULL DEFAULT 'available'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `equipment`
--

INSERT INTO `equipment` (`id`, `name`, `category`, `size`, `daily_rate`, `status`) VALUES
(1, 'Skis', 'Skis', 'Adult', 45.00, 'available'),
(2, 'Skis', 'Skis', 'Child', 35.00, 'available'),
(3, 'Snowboard', 'Snowboard', 'Adult', 42.00, 'available'),
(4, 'Snowboard', 'Snowboard', 'Child', 32.00, 'available'),
(5, 'Ski Helmet', 'Helmet', 'Adult', 10.00, 'available'),
(6, 'Ski Helmet', 'Helmet', 'Child', 8.00, 'available'),
(7, 'Ski Jacket', 'Clothing', 'Small', 18.00, 'available'),
(8, 'Ski Jacket', 'Clothing', 'Medium', 18.00, 'available'),
(9, 'Ski Jacket', 'Clothing', 'Large', 18.00, 'available'),
(10, 'Ski Pants', 'Clothing', 'Small', 18.00, 'rented'),
(11, 'Ski Pants', 'Clothing', 'Medium', 18.00, 'available'),
(12, 'Ski Pants', 'Clothing', 'Large', 18.00, 'available'),
(13, 'Snow Goggles', 'Accessories', 'Standard', 8.00, 'rented'),
(14, 'GoPro Hero 12', 'Camera', 'Standard', 25.00, 'available');

-- --------------------------------------------------------

--
-- Table structure for table `rental_items`
--

CREATE TABLE `rental_items` (
  `id` int(11) NOT NULL,
  `rental_id` int(11) NOT NULL,
  `equipment_id` int(11) NOT NULL,
  `daily_rate` decimal(10,2) NOT NULL,
  `line_total` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `rental_items`
--

INSERT INTO `rental_items` (`id`, `rental_id`, `equipment_id`, `daily_rate`, `line_total`) VALUES
(1, 1, 13, 8.00, 112.00),
(2, 1, 9, 18.00, 252.00),
(3, 2, 13, 8.00, 32.00),
(4, 2, 11, 18.00, 72.00),
(5, 2, 3, 42.00, 168.00),
(6, 3, 10, 18.00, 72.00),
(7, 3, 1, 45.00, 180.00),
(8, 4, 12, 18.00, 36.00),
(9, 4, 4, 32.00, 64.00),
(10, 5, 10, 18.00, 144.00),
(11, 5, 13, 8.00, 64.00);

-- --------------------------------------------------------

--
-- Table structure for table `rental_orders`
--

CREATE TABLE `rental_orders` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `total_price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `status` enum('active','returned','cancelled') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `rental_orders`
--

INSERT INTO `rental_orders` (`id`, `user_id`, `start_date`, `end_date`, `total_price`, `status`, `created_at`) VALUES
(1, 1, '2025-12-22', '2026-01-04', 364.00, 'returned', '2025-12-05 00:38:03'),
(2, 1, '2025-12-06', '2025-12-09', 272.00, 'returned', '2025-12-05 00:39:23'),
(3, 10, '2025-12-06', '2025-12-09', 252.00, 'returned', '2025-12-05 00:40:54'),
(4, 2, '2025-12-06', '2025-12-07', 100.00, 'returned', '2025-12-05 00:43:55'),
(5, 3, '2025-12-06', '2025-12-13', 208.00, 'active', '2025-12-05 00:53:46');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `full_name`, `email`) VALUES
(1, 'Monkey D. Luffy', 'luffy@aokijis-icegear.com'),
(2, 'Roronoa Zoro', 'zoro@aokijis-icegear.com'),
(3, 'Nami', 'nami@aokijis-icegear.com'),
(4, 'Usopp', 'usopp@aokijis-icegear.com'),
(5, 'Sanji Vinsmoke', 'sanji@aokijis-icegear.com'),
(6, 'Nico Robin', 'robin@aokijis-icegear.com'),
(7, 'Trafalgar D. Water Law', 'law@aokijis-icegear.com'),
(8, 'Kuzan (Aokiji)', 'aokiji@aokijis-icegear.com'),
(9, 'Portgas D. Ace', 'ace@aokijis-icegear.com'),
(10, 'Boa Hancock', 'hancock@aokijis-icegear.com');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `equipment`
--
ALTER TABLE `equipment`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `rental_items`
--
ALTER TABLE `rental_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_rental_items_order` (`rental_id`),
  ADD KEY `fk_rental_items_equipment` (`equipment_id`);

--
-- Indexes for table `rental_orders`
--
ALTER TABLE `rental_orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_rental_orders_user` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `equipment`
--
ALTER TABLE `equipment`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `rental_items`
--
ALTER TABLE `rental_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `rental_orders`
--
ALTER TABLE `rental_orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `rental_items`
--
ALTER TABLE `rental_items`
  ADD CONSTRAINT `fk_rental_items_equipment` FOREIGN KEY (`equipment_id`) REFERENCES `equipment` (`id`),
  ADD CONSTRAINT `fk_rental_items_order` FOREIGN KEY (`rental_id`) REFERENCES `rental_orders` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `rental_orders`
--
ALTER TABLE `rental_orders`
  ADD CONSTRAINT `fk_rental_orders_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
