-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 11, 2026 at 05:48 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `kukena-sacco`
--

-- --------------------------------------------------------

--
-- Table structure for table `customer table`
--

CREATE TABLE `customer table` (
  `UserID` varchar(100) NOT NULL,
  `Full name` varchar(100) DEFAULT NULL,
  `Phone Number` int(15) DEFAULT NULL,
  `Email` varchar(50) NOT NULL,
  `Password` varchar(15) NOT NULL,
  `Creasted at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `passenger booking`
--

CREATE TABLE `passenger booking` (
  `booking_id` int(11) NOT NULL,
  `ticket_ref` int(11) NOT NULL,
  `trip_id` int(11) NOT NULL,
  `seat_number` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `staff accounts table`
--

CREATE TABLE `staff accounts table` (
  `staff_id` int(20) NOT NULL,
  `name` varchar(100) NOT NULL,
  `role` varchar(50) NOT NULL,
  `default_terminal_id` int(11) NOT NULL,
  `pin` varchar(30) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `terminals table`
--

CREATE TABLE `terminals table` (
  `terminal_id` int(10) NOT NULL,
  `name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `transactions log table`
--

CREATE TABLE `transactions log table` (
  `transaction_id` int(11) NOT NULL,
  `reference_code` varchar(50) NOT NULL,
  `booking_id` int(11) NOT NULL,
  `payment_method` enum('Cash','MPesa-STK','Mpesa-Code','Mp-sa') NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `processed_by_staff_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `trip schedules table`
--

CREATE TABLE `trip schedules table` (
  `trip_id` int(11) NOT NULL,
  `origin_terminal_id` int(11) NOT NULL,
  `destination_terminal_id` int(11) NOT NULL,
  `vehicle_id` int(11) NOT NULL,
  `departure_time` time NOT NULL,
  `travel_date` date NOT NULL,
  `fare_amount` decimal(10,2) NOT NULL,
  `status` enum('Open','Boarding','Ready','Dispatched','Cancelled') NOT NULL,
  `created_by_staff_id` int(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `vehicles`
--

CREATE TABLE `vehicles` (
  `vehicle_id` int(10) NOT NULL,
  `registration_number` varchar(30) NOT NULL,
  `capacity` int(11) NOT NULL,
  `is_active` tinyint(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `customer table`
--
ALTER TABLE `customer table`
  ADD PRIMARY KEY (`UserID`);

--
-- Indexes for table `passenger booking`
--
ALTER TABLE `passenger booking`
  ADD PRIMARY KEY (`booking_id`),
  ADD KEY `trip_id` (`trip_id`);

--
-- Indexes for table `staff accounts table`
--
ALTER TABLE `staff accounts table`
  ADD PRIMARY KEY (`staff_id`);

--
-- Indexes for table `terminals table`
--
ALTER TABLE `terminals table`
  ADD PRIMARY KEY (`terminal_id`);

--
-- Indexes for table `transactions log table`
--
ALTER TABLE `transactions log table`
  ADD PRIMARY KEY (`transaction_id`);

--
-- Indexes for table `trip schedules table`
--
ALTER TABLE `trip schedules table`
  ADD PRIMARY KEY (`trip_id`);

--
-- Indexes for table `vehicles`
--
ALTER TABLE `vehicles`
  ADD PRIMARY KEY (`vehicle_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `staff accounts table`
--
ALTER TABLE `staff accounts table`
  MODIFY `staff_id` int(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `transactions log table`
--
ALTER TABLE `transactions log table`
  MODIFY `transaction_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `trip schedules table`
--
ALTER TABLE `trip schedules table`
  MODIFY `trip_id` int(11) NOT NULL AUTO_INCREMENT;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
