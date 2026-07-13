-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Jul 13, 2026 at 09:34 AM
-- Server version: 10.6.27-MariaDB-cll-lve
-- PHP Version: 8.2.31

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `odofasti_gold`
--

-- --------------------------------------------------------

--
-- Table structure for table `banners`
--

CREATE TABLE `banners` (
  `id` int(11) NOT NULL,
  `image_url` varchar(255) NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `banners`
--

INSERT INTO `banners` (`id`, `image_url`, `is_active`, `created_at`) VALUES
(3, 'uploads/banners/banner_6a49f1b1b3101.png', 1, '2026-07-05 05:54:57');

-- --------------------------------------------------------

--
-- Table structure for table `delivery_requests`
--

CREATE TABLE `delivery_requests` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `transaction_id` int(11) DEFAULT NULL,
  `gold_grams` decimal(10,4) NOT NULL,
  `metal_type` enum('gold','silver') DEFAULT 'gold',
  `delivery_address` text NOT NULL,
  `delivery_city` varchar(100) DEFAULT NULL,
  `delivery_state` varchar(100) DEFAULT NULL,
  `delivery_pincode` varchar(10) DEFAULT NULL,
  `status` enum('pending','processing','dispatched','delivered','cancelled') DEFAULT 'pending',
  `tracking_number` varchar(100) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `total_cost` decimal(10,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `delivery_requests`
--

INSERT INTO `delivery_requests` (`id`, `user_id`, `transaction_id`, `gold_grams`, `metal_type`, `delivery_address`, `delivery_city`, `delivery_state`, `delivery_pincode`, `status`, `tracking_number`, `notes`, `created_at`, `updated_at`, `total_cost`) VALUES
(1, 5, 32, 1.0012, 'silver', 'belun, kolkata, West Bengal - 713140', 'kolkata', 'West Bengal', '713140', 'pending', NULL, NULL, '2026-06-27 14:44:22', '2026-06-27 14:44:22', 300.00);

-- --------------------------------------------------------

--
-- Table structure for table `gold_rates`
--

CREATE TABLE `gold_rates` (
  `id` int(11) NOT NULL,
  `rate_per_gram` decimal(10,2) NOT NULL,
  `rate_date` date NOT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `gold_rates`
--

INSERT INTO `gold_rates` (`id`, `rate_per_gram`, `rate_date`, `updated_by`, `created_at`) VALUES
(1, 6850.00, '2026-06-23', NULL, '2026-06-23 16:30:03'),
(2, 5000.00, '2026-07-13', 1, '2026-07-13 07:49:37');

-- --------------------------------------------------------

--
-- Table structure for table `lock_in_plans`
--

CREATE TABLE `lock_in_plans` (
  `id` int(11) NOT NULL,
  `months` int(11) NOT NULL,
  `return_percentage` decimal(5,2) NOT NULL,
  `metal_type` enum('gold','silver') DEFAULT 'gold',
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `plan_name` varchar(100) DEFAULT NULL,
  `min_investment` decimal(10,2) DEFAULT 1.00,
  `max_investment` decimal(10,2) DEFAULT NULL,
  `penalty_percentage` decimal(5,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `lock_in_plans`
--

INSERT INTO `lock_in_plans` (`id`, `months`, `return_percentage`, `metal_type`, `status`, `created_at`, `plan_name`, `min_investment`, `max_investment`, `penalty_percentage`) VALUES
(1, 6, 5.00, 'gold', 'active', '2026-06-23 16:30:03', NULL, 1.00, NULL, 0.00),
(2, 12, 8.00, 'gold', 'active', '2026-06-23 16:30:03', NULL, 1.00, NULL, 0.00),
(3, 24, 10.00, 'gold', 'active', '2026-06-23 16:30:03', NULL, 1.00, NULL, 0.00),
(4, 36, 12.00, 'gold', 'active', '2026-06-23 16:30:03', NULL, 1.00, NULL, 0.00),
(5, 52, 10.00, 'silver', 'active', '2026-06-25 16:33:21', 'test', 0.00, 0.00, 10.00),
(6, 6, 5.00, 'silver', 'active', '2026-06-25 16:40:11', NULL, 1.00, NULL, 0.00),
(7, 12, 8.00, 'silver', 'active', '2026-06-25 16:40:11', NULL, 1.00, NULL, 0.00),
(8, 24, 10.00, 'silver', 'active', '2026-06-25 16:40:11', NULL, 1.00, NULL, 0.00),
(9, 36, 12.00, 'silver', 'active', '2026-06-25 16:40:11', NULL, 1.00, NULL, 0.00);

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `title` varchar(200) NOT NULL,
  `message` text NOT NULL,
  `type` enum('transaction','gold_rate','delivery','general') DEFAULT 'general',
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `title`, `message`, `type`, `is_read`, `created_at`) VALUES
(1, 2, 'Silver Purchase Successful', 'You bought 5.8824g silver for ?500', 'transaction', 0, '2026-06-25 16:29:40'),
(2, 2, 'Silver Purchase Successful', 'You bought 11.7647g silver for ?1000', 'transaction', 0, '2026-06-25 16:29:50'),
(3, 2, 'Silver Purchase Successful', 'You bought 58.8235g silver for ?5000', 'transaction', 0, '2026-06-25 16:29:54'),
(4, 2, 'Silver Sold Successful', 'You sold 5g silver for ?425', 'transaction', 0, '2026-06-25 16:30:08'),
(5, 2, 'Silver Sold Successful', 'You sold 50g silver for ?4250', 'transaction', 0, '2026-06-25 16:33:43'),
(6, 2, 'Silver Sold Successful', 'You sold 0.0011g silver for ?0.09', 'transaction', 0, '2026-06-25 16:42:11'),
(7, 2, 'Silver Purchase Successful', 'You bought 5.8824g silver for ?500', 'transaction', 0, '2026-06-25 16:42:23'),
(8, 2, 'Silver Sold Successful', 'You sold 1.9999g silver for ?169.99', 'transaction', 0, '2026-06-25 16:43:33'),
(9, 2, 'Gold Purchase Successful', 'You bought 0.0146g gold for ?100', 'transaction', 0, '2026-06-25 16:47:27'),
(10, 2, 'Silver Sold Successful', 'You sold 4.9999g silver for ?424.99', 'transaction', 0, '2026-06-25 16:48:17'),
(11, 2, 'Silver Sold Successful', 'You sold 0.0009g silver for ?0.08', 'transaction', 0, '2026-06-25 16:51:08'),
(12, 2, 'Silver Sold Successful', 'You sold 0.0006g silver for ?0.05', 'transaction', 0, '2026-06-25 16:55:03'),
(13, 2, 'Silver Sold Successful', 'You sold 0.0002g silver for ?0.02', 'transaction', 0, '2026-06-25 16:55:20'),
(14, 2, 'Silver Sold Successful', 'You sold 0.0002g silver for ?0.02', 'transaction', 0, '2026-06-25 16:55:27'),
(15, 2, 'Silver Sold Successful', 'You sold 0.0002g silver for ?0.02', 'transaction', 0, '2026-06-25 16:56:00'),
(16, 2, 'Silver Sold Successful', 'You sold 0.0002g silver for ?0.02', 'transaction', 0, '2026-06-25 16:57:27'),
(17, 2, 'Silver Sold Successful', 'You sold 0.0004g silver for ?0.03', 'transaction', 0, '2026-06-25 16:57:35'),
(18, 2, 'Silver Sold Successful', 'You sold 0.0002g silver for ?0.02', 'transaction', 0, '2026-06-25 16:57:44'),
(19, NULL, 'Today offer', 'Buy ?1000 gold 10% extra', 'general', 0, '2026-06-26 04:28:32'),
(20, 4, 'Gold Purchase Successful', 'You bought 0.073g gold for ?500', 'transaction', 0, '2026-06-27 08:29:55'),
(21, 4, 'Silver Purchase Successful', 'You bought 5.8824g silver for ?500', 'transaction', 0, '2026-06-27 08:31:58'),
(22, 5, 'Silver Purchase Successful', 'You bought 5.8824g silver for ?500', 'transaction', 0, '2026-06-27 14:40:07'),
(23, 4, 'Gold Purchase Successful', 'You bought 0.073g gold for ?500', 'transaction', 0, '2026-06-29 07:59:26'),
(24, 4, 'Silver Purchase Successful', 'You bought 5.8824g silver for ?500', 'transaction', 0, '2026-06-29 08:03:23'),
(25, 6, 'Gold Purchase Successful', 'You bought 0.073g gold for ?500', 'transaction', 0, '2026-07-04 16:13:01'),
(26, 3, 'Gold Purchase Successful', 'You bought 0.073g gold for ?500', 'transaction', 0, '2026-07-05 04:48:57'),
(27, 3, 'Gold Purchase Successful', 'You bought 0.073g gold for ?500', 'transaction', 0, '2026-07-05 10:29:44'),
(28, 4, 'Gold Purchase Successful', 'You bought 0.073g gold for ?500', 'transaction', 0, '2026-07-05 10:32:31'),
(29, 4, 'Gold Purchase Successful', 'You bought 0.0749g gold for ?513', 'transaction', 0, '2026-07-05 10:40:58'),
(30, 4, 'Gold Purchase Successful', 'You bought 0.073g gold for ?500', 'transaction', 0, '2026-07-10 18:43:30'),
(31, 4, 'Gold Purchase Successful', 'You bought 0.073g gold for ?500', 'transaction', 0, '2026-07-10 18:44:33'),
(32, 2, 'Silver Purchase Successful', 'You bought 5.8824g silver for ?500', 'transaction', 0, '2026-07-10 18:48:16'),
(33, 4, 'Gold Purchase Successful', 'You bought 0.073g gold for ?500', 'transaction', 0, '2026-07-10 19:27:53'),
(34, 2, 'Gold Purchase Successful', 'You bought 0.073g gold for ?500', 'transaction', 0, '2026-07-10 19:53:46'),
(35, 2, 'Gold Purchase Successful', 'You bought 0.073g gold for ?500', 'transaction', 0, '2026-07-10 20:06:48'),
(36, 2, 'Gold Purchase Successful', 'You bought 0.0146g gold for ?100', 'transaction', 0, '2026-07-10 20:14:53'),
(37, 2, 'Gold Purchase Successful', 'You bought 0.0146g gold for ?100', 'transaction', 0, '2026-07-10 20:19:28'),
(38, 4, 'Gold Purchase Successful', 'You bought 0.073g gold for ?500', 'transaction', 0, '2026-07-11 01:54:59'),
(39, 4, 'Silver Purchase Successful', 'You bought 5.8824g silver for ?500', 'transaction', 0, '2026-07-11 01:56:29'),
(40, 4, 'Gold Purchase Successful', 'You bought 0.292g gold for ?2000', 'transaction', 0, '2026-07-12 14:39:49'),
(41, 2, 'Gold Purchase Successful', 'You bought 0.0146g gold for ?100', 'transaction', 0, '2026-07-12 14:51:25'),
(42, 2, 'Gold Purchase Successful', 'You bought 0.0263g gold for ?180', 'transaction', 0, '2026-07-12 14:55:15'),
(43, 4, 'Gold Purchase Successful', 'You bought 0.073g gold for ?500', 'transaction', 0, '2026-07-12 17:30:48'),
(44, 4, 'Gold Purchase Successful', 'You bought 0.073g gold for ?500', 'transaction', 0, '2026-07-12 17:32:03'),
(45, 2, 'Gold Rate Updated', 'Today\'s gold rate: ?5,000.00/gram', 'gold_rate', 0, '2026-07-13 07:49:37'),
(46, 3, 'Gold Rate Updated', 'Today\'s gold rate: ?5,000.00/gram', 'gold_rate', 0, '2026-07-13 07:49:37'),
(47, 4, 'Gold Rate Updated', 'Today\'s gold rate: ?5,000.00/gram', 'gold_rate', 0, '2026-07-13 07:49:37'),
(48, 5, 'Gold Rate Updated', 'Today\'s gold rate: ?5,000.00/gram', 'gold_rate', 0, '2026-07-13 07:49:37'),
(49, 6, 'Gold Rate Updated', 'Today\'s gold rate: ?5,000.00/gram', 'gold_rate', 0, '2026-07-13 07:49:37'),
(50, 7, 'Gold Rate Updated', 'Today\'s gold rate: ?5,000.00/gram', 'gold_rate', 0, '2026-07-13 07:49:37');

-- --------------------------------------------------------

--
-- Table structure for table `referral_rewards`
--

CREATE TABLE `referral_rewards` (
  `id` int(11) NOT NULL,
  `referrer_id` int(11) NOT NULL,
  `referred_user_id` int(11) NOT NULL,
  `reward_type` enum('silver','gold','inr') DEFAULT 'silver',
  `reward_amount` decimal(10,4) NOT NULL,
  `transaction_id` int(11) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `silver_rates`
--

CREATE TABLE `silver_rates` (
  `id` int(11) NOT NULL,
  `rate_per_gram` decimal(10,2) NOT NULL,
  `rate_date` date NOT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `silver_rates`
--

INSERT INTO `silver_rates` (`id`, `rate_per_gram`, `rate_date`, `updated_by`, `created_at`) VALUES
(1, 85.00, '2026-06-23', NULL, '2026-06-23 16:30:03');

-- --------------------------------------------------------

--
-- Table structure for table `sip_plans`
--

CREATE TABLE `sip_plans` (
  `id` int(11) NOT NULL,
  `plan_name` varchar(255) NOT NULL,
  `min_amount` decimal(10,2) DEFAULT 500.00,
  `max_amount` decimal(10,2) DEFAULT 999999.00,
  `frequency` enum('daily','monthly') DEFAULT 'monthly',
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `sip_plans`
--

INSERT INTO `sip_plans` (`id`, `plan_name`, `min_amount`, `max_amount`, `frequency`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Weekly', 10.00, 0.00, '', 'active', '2026-06-29 08:37:23', '2026-06-29 08:37:23'),
(2, 'Monthly', 10.00, 0.00, 'monthly', 'active', '2026-06-29 08:37:35', '2026-06-29 08:37:35'),
(3, 'Yearly ', 10.00, 0.00, '', 'active', '2026-06-29 08:37:44', '2026-06-29 08:37:44'),
(4, 'Daily Saver', 100.00, 999999.00, 'daily', 'active', '2026-07-12 14:30:58', '2026-07-12 14:30:58'),
(5, 'Weekly Saver', 500.00, 999999.00, '', 'active', '2026-07-12 14:30:58', '2026-07-12 14:30:58'),
(6, 'Yearly Wealth', 10000.00, 999999.00, '', 'active', '2026-07-12 14:30:58', '2026-07-12 14:30:58'),
(7, 'Daily Saver', 10.00, 999999.00, 'daily', 'active', '2026-07-12 15:01:01', '2026-07-12 15:01:01'),
(8, 'Weekly Saver', 50.00, 999999.00, '', 'active', '2026-07-12 15:01:01', '2026-07-12 15:01:01'),
(9, 'Monthly Wealth', 100.00, 999999.00, 'monthly', 'active', '2026-07-12 15:01:01', '2026-07-12 15:01:01'),
(10, 'Yearly Wealth', 1000.00, 999999.00, '', 'active', '2026-07-12 15:01:01', '2026-07-12 15:01:01');

-- --------------------------------------------------------

--
-- Table structure for table `support_tickets`
--

CREATE TABLE `support_tickets` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `status` enum('open','pending','closed') DEFAULT 'open',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `system_settings`
--

CREATE TABLE `system_settings` (
  `setting_key` varchar(50) NOT NULL,
  `setting_value` varchar(255) NOT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `system_settings`
--

INSERT INTO `system_settings` (`setting_key`, `setting_value`, `updated_at`) VALUES
('delivery_charge', '150', '2026-06-23 16:30:03'),
('forwarding_charge', '100', '2026-06-23 16:30:03'),
('package_charge', '50', '2026-06-23 16:30:03'),
('sip_penalty_charge', '50', '2026-06-23 16:30:03');

-- --------------------------------------------------------

--
-- Table structure for table `transactions`
--

CREATE TABLE `transactions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `type` enum('buy','sell','delivery','deposit','sip_penalty') NOT NULL,
  `amount_inr` decimal(12,2) DEFAULT NULL,
  `gold_grams` decimal(10,4) DEFAULT NULL,
  `gold_rate` decimal(10,2) DEFAULT NULL,
  `metal_type` enum('gold','silver','fiat') DEFAULT 'gold',
  `transaction_source` enum('direct','sip','referral','wallet') DEFAULT 'direct',
  `status` enum('pending','completed','rejected','processing') DEFAULT 'pending',
  `payment_method` varchar(50) DEFAULT NULL,
  `payment_id` varchar(100) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `transactions`
--

INSERT INTO `transactions` (`id`, `user_id`, `type`, `amount_inr`, `gold_grams`, `gold_rate`, `metal_type`, `transaction_source`, `status`, `payment_method`, `payment_id`, `notes`, `created_by`, `created_at`, `updated_at`, `description`) VALUES
(1, 2, 'buy', 500.00, 5.8824, 85.00, 'silver', 'direct', 'completed', 'UPI', NULL, NULL, NULL, '2026-06-25 16:29:40', '2026-06-25 16:29:40', NULL),
(2, 2, 'buy', 1000.00, 11.7647, 85.00, 'silver', 'direct', 'completed', 'UPI', NULL, NULL, NULL, '2026-06-25 16:29:50', '2026-06-25 16:29:50', NULL),
(3, 2, 'buy', 5000.00, 58.8235, 85.00, 'silver', 'direct', 'completed', 'UPI', NULL, NULL, NULL, '2026-06-25 16:29:54', '2026-06-25 16:29:54', NULL),
(4, 2, 'sell', 425.00, 5.0000, 85.00, 'silver', 'direct', 'completed', NULL, NULL, NULL, NULL, '2026-06-25 16:30:08', '2026-06-25 16:30:08', NULL),
(5, 2, 'sell', 4250.00, 50.0000, 85.00, 'silver', 'direct', 'completed', NULL, NULL, NULL, NULL, '2026-06-25 16:33:43', '2026-06-25 16:33:43', NULL),
(6, 2, 'sell', 0.09, 0.0011, 85.00, 'silver', 'direct', 'completed', NULL, NULL, NULL, NULL, '2026-06-25 16:42:11', '2026-06-25 16:42:11', NULL),
(7, 2, 'buy', 500.00, 5.8824, 85.00, 'silver', 'direct', 'completed', 'UPI', NULL, NULL, NULL, '2026-06-25 16:42:23', '2026-06-25 16:42:23', NULL),
(8, 2, 'sell', 169.99, 1.9999, 85.00, 'silver', 'direct', 'completed', NULL, NULL, NULL, NULL, '2026-06-25 16:43:33', '2026-06-25 16:43:33', NULL),
(9, 2, 'buy', 100.00, 0.0146, 6850.00, 'gold', 'direct', 'completed', 'inr_wallet', 'WALLET_TXN', NULL, NULL, '2026-06-25 16:47:27', '2026-06-25 16:47:27', NULL),
(10, 2, 'sell', 424.99, 4.9999, 85.00, 'silver', 'direct', 'completed', NULL, NULL, NULL, NULL, '2026-06-25 16:48:17', '2026-06-25 16:48:17', NULL),
(11, 2, 'sell', 0.00, 1.0000, NULL, 'silver', 'direct', 'completed', NULL, NULL, 'Locked 1 g of silver for 6 months', NULL, '2026-06-25 16:50:18', '2026-06-25 16:50:18', NULL),
(12, 2, 'sell', 0.08, 0.0009, 85.00, 'silver', 'direct', 'completed', NULL, NULL, NULL, NULL, '2026-06-25 16:51:08', '2026-06-25 16:51:08', NULL),
(13, 2, 'sell', 0.05, 0.0006, 85.00, 'silver', 'direct', 'completed', NULL, NULL, NULL, NULL, '2026-06-25 16:55:03', '2026-06-25 16:55:03', NULL),
(14, 2, 'sell', 0.02, 0.0002, 85.00, 'silver', 'direct', 'completed', NULL, NULL, NULL, NULL, '2026-06-25 16:55:20', '2026-06-25 16:55:20', NULL),
(15, 2, 'sell', 0.02, 0.0002, 85.00, 'silver', 'direct', 'completed', NULL, NULL, NULL, NULL, '2026-06-25 16:55:27', '2026-06-25 16:55:27', NULL),
(16, 2, 'sell', 0.02, 0.0002, 85.00, 'silver', 'direct', 'completed', NULL, NULL, NULL, NULL, '2026-06-25 16:56:00', '2026-06-25 16:56:00', NULL),
(17, 2, 'sell', 0.02, 0.0002, 85.00, 'silver', 'direct', 'completed', NULL, NULL, NULL, NULL, '2026-06-25 16:57:27', '2026-06-25 16:57:27', NULL),
(18, 2, 'sell', 0.03, 0.0004, 85.00, 'silver', 'direct', 'completed', NULL, NULL, NULL, NULL, '2026-06-25 16:57:35', '2026-06-25 16:57:35', NULL),
(19, 2, 'sell', 0.02, 0.0002, 85.00, 'silver', 'direct', 'completed', NULL, NULL, NULL, NULL, '2026-06-25 16:57:44', '2026-06-25 16:57:44', NULL),
(20, 4, 'deposit', 10000.00, NULL, NULL, 'gold', 'wallet', 'completed', NULL, NULL, 'INR Deposit', NULL, '2026-06-27 08:29:09', '2026-06-27 08:29:09', NULL),
(21, 4, 'buy', 1000.00, 0.1460, 6850.00, 'gold', 'wallet', 'completed', NULL, NULL, 'Auto SIP conversion', NULL, '2026-06-27 08:29:09', '2026-06-27 08:29:09', NULL),
(22, 4, 'deposit', 10000.00, NULL, NULL, 'gold', 'wallet', 'completed', NULL, NULL, 'Japsan Coin Deposit', NULL, '2026-06-27 08:29:25', '2026-06-27 08:29:25', NULL),
(23, 4, 'buy', 500.00, 0.0730, 6850.00, 'gold', 'direct', 'completed', 'inr_wallet', 'WALLET_TXN', NULL, NULL, '2026-06-27 08:29:55', '2026-06-27 08:29:55', NULL),
(24, 4, 'sell', 0.00, 0.2190, NULL, 'gold', 'direct', 'completed', NULL, NULL, 'Locked 0.219 g of gold for 36 months', NULL, '2026-06-27 08:30:28', '2026-06-27 08:30:28', NULL),
(25, 4, 'buy', 500.00, 5.8824, 85.00, 'silver', 'direct', 'completed', 'inr_wallet', NULL, NULL, NULL, '2026-06-27 08:31:58', '2026-06-27 08:31:58', NULL),
(26, 3, 'deposit', 1000.00, NULL, NULL, 'gold', 'wallet', 'completed', NULL, NULL, 'INR Deposit', NULL, '2026-06-27 08:40:44', '2026-06-27 08:40:44', NULL),
(27, 3, 'buy', 1000.00, 0.1460, 6850.00, 'gold', 'wallet', 'completed', NULL, NULL, 'Auto SIP conversion', NULL, '2026-06-27 08:40:44', '2026-06-27 08:40:44', NULL),
(28, 3, 'deposit', 1000.00, NULL, NULL, 'gold', 'wallet', 'completed', NULL, NULL, 'Japsan Coin Deposit', NULL, '2026-06-27 08:41:07', '2026-06-27 08:41:07', NULL),
(29, 3, 'deposit', 1000.00, NULL, NULL, 'gold', 'wallet', 'completed', NULL, NULL, 'INR Deposit', NULL, '2026-06-27 08:41:14', '2026-06-27 08:41:14', NULL),
(30, 3, 'buy', 1000.00, 0.1460, 6850.00, 'gold', 'wallet', 'completed', NULL, NULL, 'Auto SIP conversion', NULL, '2026-06-27 08:41:14', '2026-06-27 08:41:14', NULL),
(31, 5, 'buy', 500.00, 5.8824, 85.00, 'silver', 'direct', 'completed', 'UPI', NULL, NULL, NULL, '2026-06-27 14:40:07', '2026-06-27 14:40:07', NULL),
(32, 5, 'delivery', 300.00, 1.0012, NULL, 'silver', 'direct', 'pending', NULL, NULL, NULL, NULL, '2026-06-27 14:44:22', '2026-06-27 14:44:22', 'Delivery request to belun, kolkata, West Bengal - 713140 (Charges: ?300)'),
(33, 5, 'sell', 0.00, 1.0000, NULL, 'silver', 'direct', 'completed', NULL, NULL, 'Locked 1 g of silver for 6 months', NULL, '2026-06-27 14:49:00', '2026-06-27 14:49:00', NULL),
(34, 3, 'deposit', 1000.00, NULL, NULL, 'gold', 'wallet', 'completed', NULL, NULL, 'INR Deposit', NULL, '2026-06-27 15:31:10', '2026-06-27 15:31:10', NULL),
(35, 3, 'buy', 1000.00, 0.1460, 6850.00, 'gold', 'wallet', 'completed', NULL, NULL, 'Auto SIP conversion', NULL, '2026-06-27 15:31:10', '2026-06-27 15:31:10', NULL),
(36, 4, 'buy', 500.00, 0.0730, 6850.00, 'gold', 'direct', 'completed', 'inr_wallet', 'WALLET_TXN', NULL, NULL, '2026-06-29 07:59:26', '2026-06-29 07:59:26', NULL),
(37, 4, 'sell', 0.00, 0.0730, NULL, 'gold', 'direct', 'completed', NULL, NULL, 'Locked 0.073 g of gold for 6 months', NULL, '2026-06-29 07:59:47', '2026-06-29 07:59:47', NULL),
(38, 4, 'buy', 500.00, 5.8824, 85.00, 'silver', 'direct', 'completed', 'inr_wallet', NULL, NULL, NULL, '2026-06-29 08:03:23', '2026-06-29 08:03:23', NULL),
(39, 4, 'sell', 0.00, 2.0000, NULL, 'silver', 'direct', 'completed', NULL, NULL, 'Locked 2 g of silver for 12 months', NULL, '2026-06-29 08:04:44', '2026-06-29 08:04:44', NULL),
(40, 3, 'deposit', 10000.00, NULL, NULL, 'gold', 'wallet', 'completed', NULL, NULL, 'Japsan Coin Deposit', NULL, '2026-06-29 08:40:35', '2026-06-29 08:40:35', NULL),
(41, 3, 'deposit', 10000.00, NULL, NULL, 'gold', 'wallet', 'completed', NULL, NULL, 'INR Deposit', NULL, '2026-06-29 08:40:42', '2026-06-29 08:40:42', NULL),
(42, 3, 'buy', 1000.00, 0.1460, 6850.00, 'gold', 'wallet', 'completed', NULL, NULL, 'Auto SIP conversion', NULL, '2026-06-29 08:40:42', '2026-06-29 08:40:42', NULL),
(43, 6, 'buy', 500.00, 0.0730, 6850.00, 'gold', 'direct', 'completed', 'UPI', 'pay_T9UGO9cr8e0I7K', NULL, NULL, '2026-07-04 16:13:01', '2026-07-04 16:13:01', NULL),
(44, 3, 'buy', 500.00, 0.0730, 6850.00, 'gold', 'direct', 'completed', 'UPI', 'pay_T9h98f4RxLu25Q', NULL, NULL, '2026-07-05 04:48:57', '2026-07-05 04:48:57', NULL),
(45, 3, 'sell', 0.00, 0.6570, NULL, 'gold', 'direct', 'completed', NULL, NULL, 'Locked 0.657 g of gold for 12 months', NULL, '2026-07-05 04:49:55', '2026-07-05 04:49:55', NULL),
(46, 2, 'deposit', 22.00, NULL, NULL, 'gold', 'wallet', 'completed', NULL, NULL, 'INR Deposit', NULL, '2026-07-05 07:05:08', '2026-07-05 07:05:08', NULL),
(47, 2, 'buy', 1000.00, 0.1460, 6850.00, 'gold', 'wallet', 'completed', NULL, NULL, 'Auto SIP conversion', NULL, '2026-07-05 07:05:08', '2026-07-05 07:05:08', NULL),
(48, 2, 'deposit', 57.00, NULL, NULL, 'gold', 'wallet', 'completed', NULL, NULL, 'INR Deposit', NULL, '2026-07-05 07:05:25', '2026-07-05 07:05:25', NULL),
(49, 2, 'buy', 1000.00, 0.1460, 6850.00, 'gold', 'wallet', 'completed', NULL, NULL, 'Auto SIP conversion', NULL, '2026-07-05 07:05:25', '2026-07-05 07:05:25', NULL),
(50, 2, 'deposit', 19.00, NULL, NULL, 'gold', 'wallet', 'completed', NULL, NULL, 'INR Deposit', NULL, '2026-07-05 07:39:15', '2026-07-05 07:39:15', NULL),
(51, 2, 'buy', 1000.00, 0.1460, 6850.00, 'gold', 'wallet', 'completed', NULL, NULL, 'Auto SIP conversion', NULL, '2026-07-05 07:39:15', '2026-07-05 07:39:15', NULL),
(52, 2, 'deposit', 28.00, NULL, NULL, 'gold', 'wallet', 'completed', NULL, NULL, 'INR Deposit', NULL, '2026-07-05 07:47:15', '2026-07-05 07:47:15', NULL),
(53, 2, 'buy', 1000.00, 0.1460, 6850.00, 'gold', 'wallet', 'completed', NULL, NULL, 'Auto SIP conversion', NULL, '2026-07-05 07:47:15', '2026-07-05 07:47:15', NULL),
(54, 2, 'deposit', 14.00, NULL, NULL, 'gold', 'wallet', 'completed', NULL, NULL, 'INR Deposit', NULL, '2026-07-05 07:52:18', '2026-07-05 07:52:18', NULL),
(55, 2, 'buy', 1000.00, 0.1460, 6850.00, 'gold', 'wallet', 'completed', NULL, NULL, 'Auto SIP conversion', NULL, '2026-07-05 07:52:18', '2026-07-05 07:52:18', NULL),
(56, 2, 'deposit', 21.00, NULL, NULL, 'gold', 'wallet', 'completed', NULL, NULL, 'INR Deposit', NULL, '2026-07-05 07:55:39', '2026-07-05 07:55:39', NULL),
(57, 2, 'buy', 1000.00, 0.1460, 6850.00, 'gold', 'wallet', 'completed', NULL, NULL, 'Auto SIP conversion', NULL, '2026-07-05 07:55:39', '2026-07-05 07:55:39', NULL),
(58, 3, 'buy', 500.00, 0.0730, 6850.00, 'gold', 'direct', 'completed', 'UPI', 'pay_T9mx3Lgk7MR6Wd', NULL, NULL, '2026-07-05 10:29:44', '2026-07-05 10:29:44', NULL),
(59, 4, 'deposit', 3000.00, NULL, NULL, 'gold', 'wallet', 'completed', NULL, NULL, 'INR Deposit', NULL, '2026-07-05 10:29:56', '2026-07-05 10:29:56', NULL),
(60, 4, 'buy', 1000.00, 0.1460, 6850.00, 'gold', 'wallet', 'completed', NULL, NULL, 'Auto SIP conversion', NULL, '2026-07-05 10:29:56', '2026-07-05 10:29:56', NULL),
(61, 3, 'sell', 0.00, 0.0730, NULL, 'gold', 'direct', 'completed', NULL, NULL, 'Locked 0.073 g of gold for 6 months', NULL, '2026-07-05 10:30:08', '2026-07-05 10:30:08', NULL),
(62, 4, 'buy', 500.00, 0.0730, 6850.00, 'gold', 'direct', 'completed', 'inr_wallet', 'WALLET_TXN', NULL, NULL, '2026-07-05 10:32:31', '2026-07-05 10:32:31', NULL),
(63, 4, 'sell', 0.00, 0.2190, NULL, 'gold', 'direct', 'completed', NULL, NULL, 'Locked 0.219 g of gold for 36 months', NULL, '2026-07-05 10:32:42', '2026-07-05 10:32:42', NULL),
(64, 4, 'sell', 0.00, 5.0000, NULL, 'silver', 'direct', 'completed', NULL, NULL, 'Locked 5 g of silver for 36 months', NULL, '2026-07-05 10:32:58', '2026-07-05 10:32:58', NULL),
(65, 4, 'buy', 513.00, 0.0749, 6850.00, 'gold', 'direct', 'completed', 'inr_wallet', 'WALLET_TXN', NULL, NULL, '2026-07-05 10:40:58', '2026-07-05 10:40:58', NULL),
(66, 4, 'deposit', 500.00, NULL, NULL, 'gold', 'wallet', 'completed', NULL, NULL, 'INR Deposit', NULL, '2026-07-10 16:50:56', '2026-07-10 16:50:56', NULL),
(67, 4, 'buy', 1000.00, 0.1460, 6850.00, 'gold', 'wallet', 'completed', NULL, NULL, 'Auto SIP conversion', NULL, '2026-07-10 16:50:56', '2026-07-10 16:50:56', NULL),
(68, 4, 'buy', 500.00, 0.0730, 6850.00, 'gold', 'direct', 'completed', 'inr_wallet', 'WALLET_TXN', NULL, NULL, '2026-07-10 18:43:30', '2026-07-10 18:43:30', NULL),
(69, 4, 'buy', 500.00, 0.0730, 6850.00, 'gold', 'direct', 'completed', 'inr_wallet', 'WALLET_TXN', NULL, NULL, '2026-07-10 18:44:33', '2026-07-10 18:44:33', NULL),
(70, 2, 'buy', 500.00, 5.8824, 85.00, 'silver', 'direct', 'completed', 'inr_wallet', NULL, NULL, NULL, '2026-07-10 18:48:16', '2026-07-10 18:48:16', NULL),
(71, 4, 'buy', 500.00, 0.0730, 6850.00, 'gold', 'direct', 'completed', 'inr_wallet', 'WALLET_TXN', NULL, NULL, '2026-07-10 19:27:53', '2026-07-10 19:27:53', NULL),
(72, 2, 'buy', 500.00, 0.0730, 6850.00, 'gold', 'direct', 'completed', 'inr_wallet', 'WALLET_TXN', NULL, NULL, '2026-07-10 19:53:46', '2026-07-10 19:53:46', NULL),
(73, 2, 'buy', 500.00, 0.0730, 6850.00, 'gold', 'direct', 'completed', 'inr_wallet', 'WALLET_TXN', NULL, NULL, '2026-07-10 20:06:48', '2026-07-10 20:06:48', NULL),
(74, 2, 'buy', 100.00, 0.0146, 6850.00, 'gold', 'direct', 'completed', 'inr_wallet', 'WALLET_TXN', NULL, NULL, '2026-07-10 20:14:53', '2026-07-10 20:14:53', NULL),
(75, 2, 'buy', 100.00, 0.0146, 6850.00, 'gold', 'direct', 'completed', 'inr_wallet', 'WALLET_TXN', NULL, NULL, '2026-07-10 20:19:28', '2026-07-10 20:19:28', NULL),
(76, 2, 'sell', 0.00, 1.0000, NULL, 'gold', 'direct', 'completed', NULL, NULL, 'Locked 1 g of gold for 6 months', NULL, '2026-07-10 20:19:42', '2026-07-10 20:19:42', NULL),
(77, 4, 'buy', 500.00, 0.0730, 6850.00, 'gold', 'direct', 'completed', 'inr_wallet', 'WALLET_TXN', NULL, NULL, '2026-07-11 01:54:59', '2026-07-11 01:54:59', NULL),
(78, 4, 'sell', 0.00, 0.5129, NULL, 'gold', 'direct', 'completed', NULL, NULL, 'Locked 0.5129 g of gold for 12 months', NULL, '2026-07-11 01:55:19', '2026-07-11 01:55:19', NULL),
(79, 4, 'buy', 500.00, 5.8824, 85.00, 'silver', 'direct', 'completed', 'inr_wallet', 'WALLET_TXN', NULL, NULL, '2026-07-11 01:56:29', '2026-07-11 01:56:29', NULL),
(80, 4, 'sell', 0.00, 10.6472, NULL, 'silver', 'direct', 'completed', NULL, NULL, 'Locked 10.6472 g of silver for 12 months', NULL, '2026-07-11 01:56:40', '2026-07-11 01:56:40', NULL),
(81, 4, 'buy', 2000.00, 0.2920, 6850.00, 'gold', 'direct', 'completed', 'inr_wallet', 'WALLET_TXN', NULL, NULL, '2026-07-12 14:39:49', '2026-07-12 14:39:49', NULL),
(82, 4, 'sell', 0.00, 0.2920, NULL, 'gold', 'direct', 'completed', NULL, NULL, 'Locked 0.292 g of gold for 6 months', NULL, '2026-07-12 14:39:59', '2026-07-12 14:39:59', NULL),
(83, 2, 'buy', 100.00, 0.0146, 6850.00, 'gold', 'direct', 'completed', 'inr_wallet', 'WALLET_TXN', NULL, NULL, '2026-07-12 14:51:25', '2026-07-12 14:51:25', NULL),
(84, 2, 'buy', 180.00, 0.0263, 6850.00, 'gold', 'direct', 'completed', 'inr_wallet', 'WALLET_TXN', NULL, NULL, '2026-07-12 14:55:15', '2026-07-12 14:55:15', NULL),
(85, 2, 'sell', 0.00, 0.1067, NULL, 'gold', 'direct', 'completed', NULL, NULL, 'Locked 0.1067 g of gold for 6 months', NULL, '2026-07-12 14:55:28', '2026-07-12 14:55:28', NULL),
(86, 4, 'buy', 500.00, 0.0730, 6850.00, 'gold', 'direct', 'completed', 'inr_wallet', 'WALLET_TXN', NULL, NULL, '2026-07-12 17:30:48', '2026-07-12 17:30:48', NULL),
(87, 4, 'sell', 0.00, 0.0730, NULL, 'gold', 'direct', 'completed', NULL, NULL, 'Locked 0.073 g of gold for 12 months', NULL, '2026-07-12 17:30:59', '2026-07-12 17:30:59', NULL),
(88, 4, 'buy', 500.00, 0.0730, 6850.00, 'gold', 'direct', 'completed', 'inr_wallet', 'WALLET_TXN', NULL, NULL, '2026-07-12 17:32:03', '2026-07-12 17:32:03', NULL),
(89, 4, 'sell', 0.00, 0.0730, NULL, 'gold', 'direct', 'completed', NULL, NULL, 'Locked 0.073 g of gold for 6 months', NULL, '2026-07-12 17:32:11', '2026-07-12 17:32:11', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `mobile` varchar(15) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `state` varchar(100) DEFAULT NULL,
  `pincode` varchar(10) DEFAULT NULL,
  `aadhar_number` varchar(20) DEFAULT NULL,
  `aadhar_front` varchar(255) DEFAULT NULL,
  `aadhar_back` varchar(255) DEFAULT NULL,
  `pan_number` varchar(20) DEFAULT NULL,
  `pan_image` varchar(255) DEFAULT NULL,
  `profile_photo` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `is_admin` tinyint(1) DEFAULT 0,
  `otp` varchar(10) DEFAULT NULL,
  `otp_expires_at` datetime DEFAULT NULL,
  `fcm_token` varchar(255) DEFAULT NULL,
  `inr_wallet` decimal(12,2) DEFAULT 0.00,
  `silver_wallet` decimal(10,4) DEFAULT 0.0000,
  `sip_active` tinyint(1) DEFAULT 0,
  `sip_amount` decimal(10,2) DEFAULT 0.00,
  `sip_frequency` enum('daily','weekly','monthly','yearly') DEFAULT 'monthly',
  `sip_last_deducted` datetime DEFAULT NULL,
  `referred_by` int(11) DEFAULT NULL,
  `japsan_wallet` decimal(12,2) DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `dob` varchar(20) DEFAULT NULL,
  `bank_name` varchar(100) DEFAULT NULL,
  `account_number` varchar(50) DEFAULT NULL,
  `ifsc_code` varchar(20) DEFAULT NULL,
  `account_holder_name` varchar(100) DEFAULT NULL,
  `referral_bonus_paid` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `mobile`, `email`, `address`, `city`, `state`, `pincode`, `aadhar_number`, `aadhar_front`, `aadhar_back`, `pan_number`, `pan_image`, `profile_photo`, `is_active`, `is_admin`, `otp`, `otp_expires_at`, `fcm_token`, `inr_wallet`, `silver_wallet`, `sip_active`, `sip_amount`, `sip_frequency`, `sip_last_deducted`, `referred_by`, `japsan_wallet`, `created_at`, `updated_at`, `dob`, `bank_name`, `account_number`, `ifsc_code`, `account_holder_name`, `referral_bonus_paid`) VALUES
(1, 'Admin', '9999999999', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 1, NULL, NULL, 'cBpQA8ADQZi2ChUGLvTzQV:APA91bF2tfe9IF_DmJDFNCuo74hdXi7S3CcxQnrO9l9rG1XqPd4b6wI-0VMWvqDsSRQEbl-HWCsTf2vI89olzU3dGRMAZcpqoUjD3DTRVeUVynb6Nu_jvi8', 0.00, 0.0000, 0, 0.00, 'monthly', NULL, NULL, 0.00, '2026-06-23 16:30:03', '2026-07-13 07:49:11', NULL, NULL, NULL, NULL, NULL, 0),
(2, 'Koushik ghoshhh', '6296488643', 'koushik3111@gmail.com', 'belun', 'kolkata', 'West Bengal', '713140', '202020202020', NULL, NULL, 'ABCDE1234F', NULL, NULL, 1, 0, NULL, NULL, 'eHXgVz_hTbWIOL_nuE1K8L:APA91bFoU3Kg7DStCZMdNUqBIncNTFJILm5YOhS-LwEN--ZERJXvbqEEUqdfQD3JIRscK-E_ISRzwthhW7S5UqVq1gKvZlRkWevYYxkuc3lbRi5bJOY-O8k', 92081.00, 0.0000, 1, 100.00, 'daily', NULL, NULL, 0.00, '2026-06-23 16:35:33', '2026-07-12 18:54:44', '', '', '', '', NULL, 0),
(3, 'Provat mondal', '9647457831', 'apptechvisas@gmail.com', 'Kolkata park street ', 'Kolkata ', 'West Bengal', '700001', '000088889999', NULL, NULL, 'HUDTM6472G', NULL, NULL, 1, 0, NULL, NULL, 'dvZbZUcJRjm1pjM-JKgf3D:APA91bG5u-L0rOu5Wypf06rkk6G94tuCnRzeFkibps42h_zygdSySYr4_iC8ZWFCvjEqIrlYjpFoQTcWR8QrJRyU-wK4uCeWY5LTUXWNfDhAp503IiVzkQI', 9000.00, 0.0000, 1, 1000.00, 'monthly', NULL, NULL, 11000.00, '2026-06-26 04:24:07', '2026-07-13 07:59:34', '', 'Airtel bank ', '9647457831', 'AIR000006', NULL, 0),
(4, 'SANDIP', '9033733550', 'SANDIPBARCHHA77@GMAI.COM', 'rajkot', 'rajkot', 'gujarat', '362002', '999999999999', 'uploads/kyc/4_aadhar_front_1783877379.jpg', 'uploads/kyc/4_aadhar_back_1783877379.jpg', 'axbpb3950c', 'uploads/kyc/4_pan_image_1783877379.jpg', NULL, 1, 0, NULL, NULL, 'cgI4rb6IQtuBaLmy3KjpC1:APA91bHtwy7K2QfCzhIni6Ap7hr11EOTn3IFwMZKPikCubbsh5o_Xqyw33OXNauoTsFVjTtXOuACjDzWwqmVMSddyjtOdwIbFsHxKKWXG2_IZnV9k1AHOuU', 1987.00, 0.0000, 1, 1000.00, 'monthly', NULL, NULL, 10000.00, '2026-06-27 08:27:03', '2026-07-13 04:53:31', '2026-07-12', 'test', '737382', 'hsjsjw272288', NULL, 0),
(5, 'Koushik ghosh', '6296488555', 'kg@gmail.com', '', '', '', '', '', NULL, NULL, '', NULL, NULL, 1, 0, '123456', '2026-06-27 11:19:34', NULL, 0.00, 0.0000, 0, 0.00, 'monthly', NULL, NULL, 0.00, '2026-06-27 14:39:36', '2026-06-27 15:14:34', NULL, NULL, NULL, NULL, NULL, 0),
(6, 'Koushik ghosh', '6296488645', 'admin@gopotu.com', '', '', '', '', '', NULL, NULL, '', NULL, NULL, 1, 0, '123456', '2026-07-05 01:02:32', NULL, 0.00, 0.0000, 0, 0.00, 'monthly', NULL, 2, 50.00, '2026-07-04 15:25:31', '2026-07-05 04:57:32', NULL, NULL, NULL, NULL, NULL, 0),
(7, 'Apptechvisa', '8918498730', 'theprovatmondal@gmail.com', '', '', '', '', '', NULL, NULL, '', NULL, NULL, 1, 0, '123456', '2026-07-05 01:03:33', NULL, 0.00, 0.0000, 0, 0.00, 'monthly', NULL, 3, 50.00, '2026-07-05 04:51:06', '2026-07-05 04:58:33', NULL, NULL, NULL, NULL, NULL, 0);

-- --------------------------------------------------------

--
-- Stand-in structure for view `user_gold_summary`
-- (See below for the actual view)
--
CREATE TABLE `user_gold_summary` (
`user_id` int(11)
,`name` varchar(100)
,`mobile` varchar(15)
,`total_gold_grams` decimal(33,4)
,`total_invested_inr` decimal(34,2)
);

-- --------------------------------------------------------

--
-- Table structure for table `user_lock_ins`
--

CREATE TABLE `user_lock_ins` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `plan_id` int(11) NOT NULL,
  `gold_grams` decimal(10,4) DEFAULT 0.0000,
  `silver_grams` decimal(10,4) DEFAULT 0.0000,
  `metal_type` enum('gold','silver') DEFAULT 'gold',
  `start_date` datetime NOT NULL,
  `end_date` datetime NOT NULL,
  `status` enum('active','completed','early_unlock','unlocked','cancelled') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `user_lock_ins`
--

INSERT INTO `user_lock_ins` (`id`, `user_id`, `plan_id`, `gold_grams`, `silver_grams`, `metal_type`, `start_date`, `end_date`, `status`, `created_at`) VALUES
(1, 2, 6, 0.0000, 1.0000, 'silver', '2026-06-25 12:50:18', '2026-12-25 12:50:18', 'active', '2026-06-25 16:50:18'),
(2, 4, 4, 0.2190, 0.0000, 'gold', '2026-06-27 04:30:28', '2029-06-27 04:30:28', 'active', '2026-06-27 08:30:28'),
(3, 5, 6, 0.0000, 1.0000, 'silver', '2026-06-27 10:49:00', '2026-12-27 10:49:00', 'active', '2026-06-27 14:49:00'),
(4, 4, 1, 0.0730, 0.0000, 'gold', '2026-06-29 03:59:47', '2026-12-29 03:59:47', 'active', '2026-06-29 07:59:47'),
(5, 4, 7, 0.0000, 2.0000, 'silver', '2026-06-29 04:04:44', '2027-06-29 04:04:44', 'active', '2026-06-29 08:04:44'),
(6, 3, 2, 0.6570, 0.0000, 'gold', '2026-07-05 00:49:55', '2027-07-05 00:49:55', 'active', '2026-07-05 04:49:55'),
(7, 3, 1, 0.0730, 0.0000, 'gold', '2026-07-05 06:30:08', '2027-01-05 06:30:08', 'active', '2026-07-05 10:30:08'),
(8, 4, 4, 0.2190, 0.0000, 'gold', '2026-07-05 06:32:42', '2029-07-05 06:32:42', 'active', '2026-07-05 10:32:42'),
(9, 4, 9, 0.0000, 5.0000, 'silver', '2026-07-05 06:32:58', '2029-07-05 06:32:58', 'active', '2026-07-05 10:32:58'),
(10, 2, 1, 1.0000, 0.0000, 'gold', '2026-07-10 16:19:42', '2027-01-10 16:19:42', 'active', '2026-07-10 20:19:42'),
(11, 4, 2, 0.5129, 0.0000, 'gold', '2026-07-10 21:55:19', '2027-07-10 21:55:19', 'active', '2026-07-11 01:55:19'),
(12, 4, 7, 0.0000, 10.6472, 'silver', '2026-07-10 21:56:40', '2027-07-10 21:56:40', 'active', '2026-07-11 01:56:40'),
(13, 4, 1, 0.2920, 0.0000, 'gold', '2026-07-12 10:39:59', '2027-01-12 10:39:59', 'active', '2026-07-12 14:39:59'),
(14, 2, 1, 0.1067, 0.0000, 'gold', '2026-07-12 10:55:28', '2027-01-12 10:55:28', 'active', '2026-07-12 14:55:28'),
(15, 4, 2, 0.0730, 0.0000, 'gold', '2026-07-12 13:30:59', '2027-07-12 13:30:59', 'active', '2026-07-12 17:30:59'),
(16, 4, 1, 0.0730, 0.0000, 'gold', '2026-07-12 13:32:11', '2027-01-12 13:32:11', 'active', '2026-07-12 17:32:11');

-- --------------------------------------------------------

--
-- Table structure for table `user_sips`
--

CREATE TABLE `user_sips` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `frequency` enum('daily','weekly','monthly','yearly') NOT NULL,
  `metal_type` enum('gold','silver') DEFAULT 'gold',
  `status` enum('active','paused','cancelled') DEFAULT 'active',
  `last_deducted` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `user_sips`
--

INSERT INTO `user_sips` (`id`, `user_id`, `amount`, `frequency`, `metal_type`, `status`, `last_deducted`, `created_at`, `updated_at`) VALUES
(1, 4, 1000.00, 'monthly', 'gold', 'active', NULL, '2026-07-13 04:53:31', '2026-07-13 04:53:31'),
(2, 3, 1000.00, 'monthly', 'gold', 'active', NULL, '2026-07-13 05:37:50', '2026-07-13 07:59:34');

-- --------------------------------------------------------

--
-- Stand-in structure for view `user_summary`
-- (See below for the actual view)
--
CREATE TABLE `user_summary` (
`user_id` int(11)
,`name` varchar(100)
,`mobile` varchar(15)
,`inr_wallet` decimal(12,2)
,`silver_wallet` decimal(10,4)
,`japsan_wallet` decimal(12,2)
,`total_gold_grams` decimal(33,4)
,`total_silver_grams` decimal(33,4)
,`total_invested_inr` decimal(34,2)
);

-- --------------------------------------------------------

--
-- Table structure for table `withdrawal_requests`
--

CREATE TABLE `withdrawal_requests` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `bank_name` varchar(100) DEFAULT NULL,
  `account_number` varchar(50) DEFAULT NULL,
  `ifsc_code` varchar(20) DEFAULT NULL,
  `account_holder_name` varchar(100) DEFAULT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `admin_notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `banners`
--
ALTER TABLE `banners`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `delivery_requests`
--
ALTER TABLE `delivery_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `transaction_id` (`transaction_id`);

--
-- Indexes for table `gold_rates`
--
ALTER TABLE `gold_rates`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `rate_date` (`rate_date`),
  ADD KEY `updated_by` (`updated_by`);

--
-- Indexes for table `lock_in_plans`
--
ALTER TABLE `lock_in_plans`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `referral_rewards`
--
ALTER TABLE `referral_rewards`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `silver_rates`
--
ALTER TABLE `silver_rates`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `rate_date` (`rate_date`),
  ADD KEY `updated_by` (`updated_by`);

--
-- Indexes for table `sip_plans`
--
ALTER TABLE `sip_plans`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `support_tickets`
--
ALTER TABLE `support_tickets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `system_settings`
--
ALTER TABLE `system_settings`
  ADD PRIMARY KEY (`setting_key`);

--
-- Indexes for table `transactions`
--
ALTER TABLE `transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `mobile` (`mobile`);

--
-- Indexes for table `user_lock_ins`
--
ALTER TABLE `user_lock_ins`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `user_sips`
--
ALTER TABLE `user_sips`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `withdrawal_requests`
--
ALTER TABLE `withdrawal_requests`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `banners`
--
ALTER TABLE `banners`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `delivery_requests`
--
ALTER TABLE `delivery_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `gold_rates`
--
ALTER TABLE `gold_rates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `lock_in_plans`
--
ALTER TABLE `lock_in_plans`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=52;

--
-- AUTO_INCREMENT for table `referral_rewards`
--
ALTER TABLE `referral_rewards`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `silver_rates`
--
ALTER TABLE `silver_rates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `sip_plans`
--
ALTER TABLE `sip_plans`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `support_tickets`
--
ALTER TABLE `support_tickets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `transactions`
--
ALTER TABLE `transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=90;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `user_lock_ins`
--
ALTER TABLE `user_lock_ins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `user_sips`
--
ALTER TABLE `user_sips`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `withdrawal_requests`
--
ALTER TABLE `withdrawal_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

-- --------------------------------------------------------

--
-- Structure for view `user_gold_summary`
--
DROP TABLE IF EXISTS `user_gold_summary`;

CREATE ALGORITHM=UNDEFINED DEFINER=`odofasti_gold`@`localhost` SQL SECURITY DEFINER VIEW `user_gold_summary`  AS SELECT `u`.`id` AS `user_id`, `u`.`name` AS `name`, `u`.`mobile` AS `mobile`, coalesce(sum(case when `t`.`type` = 'buy' and `t`.`status` = 'completed' and (`t`.`metal_type` = 'gold' or `t`.`metal_type` is null) then `t`.`gold_grams` else 0 end),0) - coalesce(sum(case when `t`.`type` in ('sell','delivery') and `t`.`status` = 'completed' and (`t`.`metal_type` = 'gold' or `t`.`metal_type` is null) then `t`.`gold_grams` else 0 end),0) AS `total_gold_grams`, coalesce(sum(case when `t`.`type` = 'buy' and `t`.`status` = 'completed' and (`t`.`metal_type` = 'gold' or `t`.`metal_type` is null) then `t`.`amount_inr` else 0 end),0) AS `total_invested_inr` FROM (`users` `u` left join `transactions` `t` on(`u`.`id` = `t`.`user_id`)) WHERE `u`.`is_admin` = 0 GROUP BY `u`.`id`, `u`.`name`, `u`.`mobile` ;

-- --------------------------------------------------------

--
-- Structure for view `user_summary`
--
DROP TABLE IF EXISTS `user_summary`;

CREATE ALGORITHM=UNDEFINED DEFINER=`odofasti_gold`@`localhost` SQL SECURITY DEFINER VIEW `user_summary`  AS SELECT `u`.`id` AS `user_id`, `u`.`name` AS `name`, `u`.`mobile` AS `mobile`, `u`.`inr_wallet` AS `inr_wallet`, `u`.`silver_wallet` AS `silver_wallet`, `u`.`japsan_wallet` AS `japsan_wallet`, coalesce(sum(case when `t`.`type` = 'buy' and `t`.`status` = 'completed' and `t`.`metal_type` = 'gold' then `t`.`gold_grams` else 0 end),0) - coalesce(sum(case when `t`.`type` in ('sell','delivery') and `t`.`status` = 'completed' and `t`.`metal_type` = 'gold' then `t`.`gold_grams` else 0 end),0) AS `total_gold_grams`, coalesce(sum(case when `t`.`type` = 'buy' and `t`.`status` = 'completed' and `t`.`metal_type` = 'silver' then `t`.`gold_grams` else 0 end),0) - coalesce(sum(case when `t`.`type` in ('sell','delivery') and `t`.`status` = 'completed' and `t`.`metal_type` = 'silver' then `t`.`gold_grams` else 0 end),0) AS `total_silver_grams`, coalesce(sum(case when `t`.`type` = 'buy' and `t`.`status` = 'completed' then `t`.`amount_inr` else 0 end),0) AS `total_invested_inr` FROM (`users` `u` left join `transactions` `t` on(`u`.`id` = `t`.`user_id`)) WHERE `u`.`is_admin` = 0 GROUP BY `u`.`id`, `u`.`name`, `u`.`mobile`, `u`.`inr_wallet`, `u`.`silver_wallet`, `u`.`japsan_wallet` ;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `delivery_requests`
--
ALTER TABLE `delivery_requests`
  ADD CONSTRAINT `delivery_requests_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `delivery_requests_ibfk_2` FOREIGN KEY (`transaction_id`) REFERENCES `transactions` (`id`);

--
-- Constraints for table `gold_rates`
--
ALTER TABLE `gold_rates`
  ADD CONSTRAINT `gold_rates_ibfk_1` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `silver_rates`
--
ALTER TABLE `silver_rates`
  ADD CONSTRAINT `silver_rates_ibfk_1` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `support_tickets`
--
ALTER TABLE `support_tickets`
  ADD CONSTRAINT `support_tickets_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `transactions`
--
ALTER TABLE `transactions`
  ADD CONSTRAINT `transactions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `transactions_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
