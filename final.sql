-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 23, 2025 at 01:41 PM
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
-- Database: `final`
--

-- --------------------------------------------------------

--
-- Table structure for table `agencies`
--

CREATE TABLE `agencies` (
  `id` int(11) NOT NULL,
  `agency_name` varchar(255) NOT NULL,
  `agent_name` varchar(255) NOT NULL,
  `agency_code` varchar(50) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `address` text NOT NULL,
  `email` varchar(255) NOT NULL,
  `profile_image` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `agencies`
--

INSERT INTO `agencies` (`id`, `agency_name`, `agent_name`, `agency_code`, `phone`, `address`, `email`, `profile_image`, `created_at`) VALUES
(1, 'wholesale kitchen', 'kailash', 'svks001', '9876543210', '10/278, NRKR Road sivakasi', 'wholesalekitchen@gmail.com', '../uploads/agency_profiles/agency_67c1599d36d65.png', '2025-02-28 06:31:14'),
(2, 'kitchenflip', 'kishore', 'mdu001', '9876543210', '19/678, near periyar bus stand madurai', 'kitchenflip@gmail.com', '../uploads/agency_profiles/agency_67c15c41a4625.jpeg', '2025-02-28 06:48:33');

-- --------------------------------------------------------

--
-- Table structure for table `bills`
--

CREATE TABLE `bills` (
  `id` int(11) NOT NULL,
  `bill_no` varchar(10) NOT NULL,
  `customer_name` varchar(255) NOT NULL,
  `customer_phone` varchar(20) NOT NULL,
  `customer_email` varchar(255) NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `gst_percentage` decimal(5,2) NOT NULL,
  `gst_amount` decimal(10,2) NOT NULL,
  `grand_total` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bills`
--

INSERT INTO `bills` (`id`, `bill_no`, `customer_name`, `customer_phone`, `customer_email`, `total_amount`, `gst_percentage`, `gst_amount`, `grand_total`, `created_at`) VALUES
(3, 'BL102', 'suman', '9874561230', 'suman@gmail.com', 4016.00, 18.00, 722.88, 4738.88, '2025-03-03 15:28:43'),
(5, 'BL103', 'kishore', '9856741230', 'kishore@gmail.com', 1464.00, 18.00, 263.52, 1727.52, '2025-03-04 04:17:35'),
(6, 'BL104', 'sivan', '9632587410', 'sivan@gmail.com', 1792.00, 18.00, 322.56, 2114.56, '2025-03-04 14:50:29'),
(7, 'BL105', 'rafith', '7845963210', 'rafith@gmail.com', 6144.00, 18.00, 1105.92, 7249.92, '2025-03-09 16:33:58'),
(8, 'BL106', 'Ravi kumar', '08438322435', 'kumarravirk013@gmail.com', 1848.00, 18.00, 332.64, 2180.64, '2025-03-13 10:43:39'),
(9, 'BL107', 'sivan', '9632587410', 'rafith@gmail.com', 3037.50, 18.00, 546.75, 3584.25, '2025-03-17 13:33:57'),
(10, 'BL108', 'Ravi kumar', '08438322435', 'kishore@gmail.com', 354.00, 18.00, 63.72, 417.72, '2025-03-19 06:42:37'),
(12, 'BL109', 'Ravi kumar', '9854761230', 'kishore@gmail.com', 162.00, 18.00, 29.16, 191.16, '2025-03-26 09:41:20'),
(13, 'BL110', 'sivan', '7845963210', 'sivan@gmail.com', 12558.00, 18.00, 2260.44, 14818.44, '2025-03-26 14:42:15'),
(14, 'BL111', 'subarau', '9632587410', 'rafith@gmail.com', 480.00, 18.00, 86.40, 566.40, '2025-03-26 14:44:42'),
(15, 'BL112', 'Ravi kumar', '9854761230', 'kishore@gmail.com', 1500.00, 18.00, 270.00, 1770.00, '2025-03-31 07:58:52'),
(18, 'BL113', 'Ravi kumar', '9854761230', 'kumarravirk013@gmail.com', 480.00, 18.00, 86.40, 566.40, '2025-04-02 06:31:53'),
(20, 'BL114', 'ram', '9854761230', 'kishore@gmail.com', 1300.00, 0.00, 0.00, 1300.00, '2025-04-22 16:17:57'),
(21, 'BL115', 'Ravi kumar', '9854761230', 'kishore@gmail.com', 231.84, 18.00, 41.73, 273.57, '2025-04-26 05:58:39'),
(23, 'BL116', 'Ravi kumar', '9854761230', 'kishore@gmail.com', 5400.00, 18.00, 972.00, 6372.00, '2025-05-11 12:04:37'),
(24, 'BL117', 'kailash', '9854761230', 'kishore@gmail.com', 2080.00, 18.00, 374.40, 2454.40, '2025-05-11 12:08:01'),
(25, 'BL118', 'Ravi kumar', '9854761230', 'kishore@gmail.com', 180.32, 18.00, 32.46, 212.78, '2025-05-15 05:21:59'),
(26, 'BL119', 'Ravi kumar', '9854761230', 'kishore@gmail.com', 912.82, 18.00, 164.31, 1077.13, '2025-05-15 05:22:49');

-- --------------------------------------------------------

--
-- Table structure for table `bill_items`
--

CREATE TABLE `bill_items` (
  `id` int(11) NOT NULL,
  `bill_id` int(11) NOT NULL,
  `product_id` varchar(20) NOT NULL,
  `product_name` varchar(255) NOT NULL,
  `quantity` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `total` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bill_items`
--

INSERT INTO `bill_items` (`id`, `bill_id`, `product_id`, `product_name`, `quantity`, `price`, `total`) VALUES
(6, 3, 'PROD120', 'curd container', 8, 490.00, 3920.00),
(7, 3, 'PROD113', 'Spoon', 4, 24.00, 96.00),
(10, 5, 'PROD112', 'Cutting Board (Premium)', 1, 264.00, 264.00),
(11, 5, 'PROD118', 'ladle for gravies', 4, 300.00, 1200.00),
(12, 6, 'PROD116', 'appam pan', 4, 448.00, 1792.00),
(13, 7, 'PROD121', 'rice storage container', 5, 1200.00, 6000.00),
(14, 7, 'PROD111', 'copper glass', 4, 36.00, 144.00),
(15, 8, 'PROD112', 'Cutting Board (Premium)', 7, 264.00, 1848.00),
(16, 9, 'PROD122', 'traditional lamp ', 3, 1012.50, 3037.50),
(17, 10, 'PROD118', 'ladle for gravies', 1, 300.00, 300.00),
(18, 10, 'PROD125', 'copper plate', 1, 54.00, 54.00),
(20, 12, 'PROD125', 'copper plate', 3, 54.00, 162.00),
(21, 13, 'PROD117', 'milk boiling ptot', 7, 1794.00, 12558.00),
(22, 14, 'PROD127', 'lemon squeezing machine', 8, 60.00, 480.00),
(23, 15, 'PROD112', 'Cake Moulds', 4, 375.00, 1500.00),
(28, 18, 'PROD139', 'EverSilver Dinner Plates', 4, 120.00, 480.00),
(30, 20, 'PROD137', 'EverSilver Lunch Box', 5, 260.00, 1300.00),
(31, 21, 'PROD141', 'Breeding Box', 9, 25.76, 231.84),
(33, 23, 'PROD133', 'Serving Bowls', 8, 675.00, 5400.00),
(34, 24, 'PROD111', 'Baking Sheets', 8, 260.00, 2080.00),
(35, 25, 'PROD141', 'Breeding Box', 7, 25.76, 180.32),
(36, 26, 'PROD141', 'Breeding Box', 7, 25.76, 180.32),
(37, 26, 'PROD111', 'Baking Sheets', 1, 260.00, 260.00),
(38, 26, 'PROD121', 'Airtight Jars', 1, 472.50, 472.50);

-- --------------------------------------------------------

--
-- Table structure for table `canceled_bills`
--

CREATE TABLE `canceled_bills` (
  `id` int(11) NOT NULL,
  `bill_no` varchar(10) NOT NULL,
  `customer_name` varchar(255) NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `grand_total` decimal(10,2) NOT NULL,
  `reason` text DEFAULT NULL,
  `canceled_by` varchar(50) NOT NULL,
  `canceled_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `canceled_bills`
--

INSERT INTO `canceled_bills` (`id`, `bill_no`, `customer_name`, `total_amount`, `grand_total`, `reason`, `canceled_by`, `canceled_at`) VALUES
(1, 'BL102', 'moorthy', 2460.00, 2902.80, 'no', 'Anandha7201', '2025-03-03 15:13:21'),
(2, 'BL101', 'Ravi kumar', 204.00, 240.72, 'some fault in the product ', 'Ashok', '2025-03-04 09:58:22'),
(3, 'BL109', 'kailash', 2700.00, 3186.00, 'damage scratch', 'Ashok', '2025-03-25 07:01:14'),
(4, 'BL113', 'kailash', 3825.00, 4513.50, 'its damage', 'Ashok', '2025-03-31 08:36:25'),
(5, 'BL113', 'Ravi kumar', 2860.00, 3374.80, 'summa', 'Ashok', '2025-04-02 06:25:03'),
(6, 'BL114', 'kailash', 472.50, 557.55, 'nothing', 'Ashok', '2025-04-22 14:43:16'),
(7, 'BL116', 'kailash', 9720.00, 11469.60, 'some damage in this ', 'Ashok', '2025-04-30 06:47:44');

-- --------------------------------------------------------

--
-- Table structure for table `canceled_bill_items`
--

CREATE TABLE `canceled_bill_items` (
  `id` int(11) NOT NULL,
  `canceled_bill_id` int(11) NOT NULL,
  `product_id` varchar(20) NOT NULL,
  `product_name` varchar(255) NOT NULL,
  `quantity` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `total` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `canceled_bill_items`
--

INSERT INTO `canceled_bill_items` (`id`, `canceled_bill_id`, `product_id`, `product_name`, `quantity`, `price`, `total`) VALUES
(1, 1, 'PROD118', 'ladle for gravies', 5, 300.00, 1500.00),
(2, 1, 'PROD119', 'rice serving spoon', 4, 195.00, 780.00),
(3, 1, 'PROD111', 'copper glass', 5, 36.00, 180.00),
(4, 2, 'PROD111', 'copper glass', 5, 36.00, 180.00),
(5, 2, 'PROD113', 'Spoon', 1, 24.00, 24.00),
(6, 3, 'PROD118', 'ladle for gravies', 9, 300.00, 2700.00),
(7, 4, 'PROD112', 'Cake Moulds', 5, 375.00, 1875.00),
(8, 4, 'PROD113', 'Non-stick Frying Pan', 3, 650.00, 1950.00),
(9, 5, 'PROD137', 'EverSilver Lunch Box', 3, 260.00, 780.00),
(10, 5, 'PROD111', 'Baking Sheets', 8, 260.00, 2080.00),
(11, 6, 'PROD121', 'Airtight Jars', 1, 472.50, 472.50),
(12, 7, 'PROD116', 'Cast Iron Tawa', 9, 1080.00, 9720.00);

-- --------------------------------------------------------

--
-- Table structure for table `cart`
--

CREATE TABLE `cart` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cart`
--

INSERT INTO `cart` (`id`, `user_id`, `created_at`) VALUES
(1, 6, '2025-03-15 08:25:20'),
(2, 7, '2025-03-16 09:13:50'),
(3, 10, '2025-03-17 10:33:14'),
(4, 12, '2025-03-18 15:14:05');

-- --------------------------------------------------------

--
-- Table structure for table `cart_items`
--

CREATE TABLE `cart_items` (
  `id` int(11) NOT NULL,
  `cart_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cart_items`
--

INSERT INTO `cart_items` (`id`, `cart_id`, `product_id`, `quantity`) VALUES
(79, 1, 164, 2),
(80, 4, 144, 1),
(81, 1, 165, 1);

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `category_name` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `category_name`, `created_at`) VALUES
(1, 'Utensils & Cutlery', '2025-03-29 13:28:57'),
(2, 'bakeware', '2025-03-29 14:23:57'),
(3, 'cookware', '2025-03-29 16:34:33'),
(4, 'kitchen tools & gatgets', '2025-03-31 08:47:44'),
(5, 'dining & serving essentials', '2025-03-31 08:53:21');

-- --------------------------------------------------------

--
-- Table structure for table `ecancel`
--

CREATE TABLE `ecancel` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `upi_id` varchar(100) NOT NULL,
  `reason` text NOT NULL,
  `status` enum('pending','processed','rejected') NOT NULL DEFAULT 'pending',
  `admin_notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `processed_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ecancel_items`
--

CREATE TABLE `ecancel_items` (
  `id` int(11) NOT NULL,
  `cancel_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `product_name` varchar(255) NOT NULL,
  `quantity` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `total` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `esales`
--

CREATE TABLE `esales` (
  `id` int(11) NOT NULL,
  `order_id` varchar(20) NOT NULL,
  `user_id` int(11) NOT NULL,
  `transaction_id` varchar(100) NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `order_confirmation` enum('pending','confirmed','cancelled') NOT NULL DEFAULT 'pending',
  `delivery_confirmation` enum('pending','confirmed','cancelled') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `esales`
--

INSERT INTO `esales` (`id`, `order_id`, `user_id`, `transaction_id`, `total_amount`, `order_confirmation`, `delivery_confirmation`, `created_at`) VALUES
(1, 'ORD20250317073407416', 7, 'ffdkljflkdjfdjfd', 2870.50, 'confirmed', 'confirmed', '2025-03-17 06:34:07'),
(2, 'ORD20250318113203349', 7, '956556jhj', 1462.50, 'confirmed', 'confirmed', '2025-03-18 10:32:03'),
(3, 'ORD20250318115112588', 6, '956556jhjh3434lj', 900.00, 'confirmed', 'confirmed', '2025-03-18 10:51:12'),
(4, 'ORD20250318170355951', 12, 'abcdefghijklmnopqrst', 1012.50, 'confirmed', 'confirmed', '2025-03-18 16:03:55'),
(5, 'ORD20250323143434470', 7, '508226892182', 3.00, 'confirmed', 'confirmed', '2025-03-23 13:34:34'),
(6, 'ORD20250325080933998', 7, '102023408187', 3.00, 'confirmed', 'confirmed', '2025-03-25 07:09:33'),
(7, 'ORD20250325081140784', 7, 'hghjgjhgjhgjh', 1200.00, 'confirmed', 'confirmed', '2025-03-25 07:11:40'),
(8, 'ORD20250325091918316', 7, 'abcdefghijklmnopqrst', 54.00, 'confirmed', 'confirmed', '2025-03-25 08:19:18'),
(9, 'ORD20250326104409750', 7, 'wwerfwerg354', 108.00, 'confirmed', 'confirmed', '2025-03-26 09:44:09'),
(10, 'ORD20250330154903799', 7, '956556545464', 125.00, 'confirmed', 'confirmed', '2025-03-30 13:49:03'),
(11, 'ORD20250331100156929', 7, 'hghjgjhgjhgjh', 1990.00, 'confirmed', 'confirmed', '2025-03-31 08:01:56'),
(12, 'ORD20250331100306992', 7, '12345678923', 750.00, 'confirmed', 'confirmed', '2025-03-31 08:03:06'),
(13, 'ORD20250331102102357', 7, '956556545464', 1039.00, 'confirmed', 'confirmed', '2025-03-31 08:21:02'),
(14, 'ORD20250331104334802', 7, '102023408187', 375.00, 'confirmed', 'confirmed', '2025-03-31 08:43:34'),
(15, 'ORD20250401120724671', 12, '102023408187', 1500.00, 'confirmed', 'confirmed', '2025-04-01 10:07:24'),
(16, 'ORD20250401153524627', 12, 'abcdefghijklmnopqrst', 650.00, 'confirmed', 'confirmed', '2025-04-01 13:35:24'),
(17, 'ORD20250401154914306', 7, '1234567892309', 910.00, 'cancelled', 'pending', '2025-04-01 13:49:14'),
(18, 'ORD20250401155520174', 7, 'hghjgjhgjhgjh', 810.00, 'confirmed', 'confirmed', '2025-04-01 13:55:20'),
(19, 'ORD20250419062748721', 7, '123854679', 350.00, 'cancelled', 'pending', '2025-04-19 04:27:48'),
(20, 'ORD20250507160744873', 7, '789876465445', 180.00, 'cancelled', 'pending', '2025-05-07 14:07:44'),
(21, 'ORD20250514113948862', 7, '641656161651651651651', 180.00, 'confirmed', 'confirmed', '2025-05-14 09:39:48'),
(22, 'ORD20250515071036420', 7, '87484151615', 1080.00, 'confirmed', 'confirmed', '2025-05-15 05:10:36');

-- --------------------------------------------------------

--
-- Table structure for table `esales_items`
--

CREATE TABLE `esales_items` (
  `id` int(11) NOT NULL,
  `esales_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `product_name` varchar(255) NOT NULL,
  `quantity` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `total` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `esales_items`
--

INSERT INTO `esales_items` (`id`, `esales_id`, `product_id`, `product_name`, `quantity`, `price`, `total`) VALUES
(15, 11, 141, 'Saucepan', 1, 910.00, 910.00),
(16, 11, 142, 'Cast Iron Tawa', 1, 1080.00, 1080.00),
(17, 12, 145, 'Rolling Pin', 1, 210.00, 210.00),
(18, 12, 144, 'Muffin Trays', 1, 540.00, 540.00),
(19, 13, 150, 'Oil Dispensers', 1, 364.00, 364.00),
(20, 13, 149, 'Food Storage Boxes', 1, 675.00, 675.00),
(21, 14, 138, 'Cake Moulds', 1, 375.00, 375.00),
(22, 15, 140, 'Non-stick Frying Pan.', 1, 1500.00, 1500.00),
(23, 16, 139, 'Non-stick Frying Pan', 1, 650.00, 650.00),
(24, 17, 141, 'Saucepan', 1, 910.00, 910.00),
(25, 18, 164, 'Copper Bottom Kadai', 1, 810.00, 810.00),
(26, 19, 137, 'Baking Sheets', 1, 260.00, 260.00),
(28, 20, 170, 'whisks', 1, 180.00, 180.00),
(29, 21, 170, 'whisks', 1, 180.00, 180.00),
(30, 22, 142, 'Cast Iron Tawa', 1, 1080.00, 1080.00);

-- --------------------------------------------------------

--
-- Table structure for table `materials`
--

CREATE TABLE `materials` (
  `id` int(11) NOT NULL,
  `material_name` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `materials`
--

INSERT INTO `materials` (`id`, `material_name`, `created_at`) VALUES
(1, 'copper', '2025-03-31 06:29:49'),
(2, 'eversilver', '2025-03-31 07:19:30'),
(3, 'aluminium', '2025-03-31 07:24:16'),
(4, 'cast Iron', '2025-03-31 07:29:55'),
(5, 'wooden', '2025-03-31 07:44:38'),
(6, 'plastic', '2025-03-31 08:11:30'),
(7, 'glass', '2025-03-31 08:55:08'),
(8, 'cotton', '2025-03-31 08:59:03');

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `product_id` varchar(20) NOT NULL,
  `product_name` varchar(255) NOT NULL,
  `product_quantity` int(11) NOT NULL,
  `reorder_level` int(11) DEFAULT 10,
  `barcode_path` varchar(255) DEFAULT NULL,
  `product_price` decimal(10,2) NOT NULL,
  `agency_id` int(11) NOT NULL,
  `profit_percent` decimal(5,2) NOT NULL,
  `selling_price` decimal(10,2) NOT NULL,
  `product_image` varchar(255) DEFAULT NULL,
  `material` varchar(100) NOT NULL,
  `product_category` varchar(100) NOT NULL,
  `is_visible` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  `status` varchar(20) DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `product_id`, `product_name`, `product_quantity`, `reorder_level`, `barcode_path`, `product_price`, `agency_id`, `profit_percent`, `selling_price`, `product_image`, `material`, `product_category`, `is_visible`, `created_at`, `updated_at`, `status`) VALUES
(137, 'PROD111', 'Baking Sheets', 171, 10, NULL, 200.00, 2, 30.00, 260.00, '../uploads/product_images/product_67ea42499f171.jpeg', 'eversilver', 'bakeware', 1, '2025-03-31 07:20:41', '2025-05-23 11:22:29', 'active'),
(138, 'PROD112', 'Cake Moulds', 65, 10, NULL, 300.00, 1, 25.00, 375.00, '../uploads/product_images/product_67ea42a90e742.jpeg', 'eversilver', 'bakeware', 1, '2025-03-31 07:22:17', '2025-05-07 13:34:45', 'active'),
(139, 'PROD113', 'Non-stick Frying Pan', 44, 10, NULL, 500.00, 2, 30.00, 650.00, '../uploads/product_images/product_67ea437ed44a0.jpeg', 'aluminium', 'cookware', 1, '2025-03-31 07:25:50', '2025-05-23 11:22:29', 'active'),
(140, 'PROD114', 'Non-stick Frying Pan.', 59, 10, NULL, 1200.00, 1, 25.00, 1500.00, '../uploads/product_images/product_67ea43c4c65e2.jpeg', 'eversilver', 'cookware', 1, '2025-03-31 07:27:00', '2025-05-07 13:34:45', 'active'),
(141, 'PROD115', 'Saucepan', 79, 10, NULL, 700.00, 2, 30.00, 910.00, '../uploads/product_images/product_67ea444912024.jpeg', 'eversilver', 'cookware', 1, '2025-03-31 07:29:13', '2025-05-23 11:22:29', 'active'),
(142, 'PROD116', 'Cast Iron Tawa', 54, 10, NULL, 800.00, 1, 35.00, 1080.00, '../uploads/product_images/product_67ea449f3c415.jpeg', 'cast Iron', 'cookware', 1, '2025-03-31 07:30:39', '2025-05-07 13:34:45', 'active'),
(143, 'PROD117', 'Grill Pan', 90, 10, NULL, 950.00, 2, 30.00, 1235.00, '../uploads/product_images/product_67ea44cced48f.jpeg', 'cast Iron', 'cookware', 1, '2025-03-31 07:31:24', '2025-05-23 11:22:29', 'active'),
(144, 'PROD118', 'Muffin Trays', 79, 10, NULL, 400.00, 1, 35.00, 540.00, '../uploads/product_images/product_67ea452713469.jpeg', 'cast Iron', 'bakeware', 1, '2025-03-31 07:32:55', '2025-05-07 13:34:45', 'active'),
(145, 'PROD119', 'Rolling Pin', 79, 10, NULL, 150.00, 1, 40.00, 210.00, '../uploads/product_images/product_67ea4823c7ccf.jpeg', 'wooden', 'bakeware', 1, '2025-03-31 07:45:39', '2025-05-07 13:34:45', 'active'),
(146, 'PROD120', 'Measuring Cups & Spoons', 90, 10, NULL, 200.00, 2, 30.00, 260.00, '../uploads/product_images/product_67ea48cde346c.jpeg', 'eversilver', 'bakeware', 1, '2025-03-31 07:48:29', '2025-05-23 11:22:29', 'active'),
(147, 'PROD121', 'Airtight Jars', 100, 10, NULL, 350.00, 2, 35.00, 472.50, '../uploads/product_images/product_67ea4e6dd8f0b.jpeg', 'plastic', 'Utensils & Cutlery', 1, '2025-03-31 08:12:29', '2025-05-23 11:22:29', 'active'),
(148, 'PROD122', 'Spice Racks', 80, 10, NULL, 450.00, 1, 30.00, 585.00, '../uploads/product_images/product_67ea4ee145f67.jpeg', 'wooden', 'Utensils & Cutlery', 1, '2025-03-31 08:14:25', '2025-05-07 13:34:45', 'active'),
(149, 'PROD123', 'Food Storage Boxes', 77, 10, NULL, 500.00, 2, 35.00, 675.00, '../uploads/product_images/product_67ea4f0b62afa.jpeg', 'plastic', 'Utensils & Cutlery', 1, '2025-03-31 08:15:07', '2025-05-23 11:22:29', 'active'),
(150, 'PROD124', 'Oil Dispensers', 89, 10, NULL, 280.00, 2, 30.00, 364.00, '../uploads/product_images/product_67ea4f4c26901.jpeg', 'plastic', 'Utensils & Cutlery', 1, '2025-03-31 08:16:12', '2025-05-23 11:22:29', 'active'),
(151, 'PROD125', 'Lunch Boxes', 90, 10, NULL, 600.00, 1, 25.00, 750.00, '../uploads/product_images/product_67ea4f9862f35.jpeg', 'plastic', 'Utensils & Cutlery', 1, '2025-03-31 08:17:28', '2025-05-07 13:34:45', 'active'),
(152, 'PROD126', 'Vegetable Chopper', 55, 10, NULL, 450.00, 2, 40.00, 630.00, '../uploads/product_images/product_67ea56e531f8b.jpeg', 'plastic', 'kitchen tools & gatgets', 1, '2025-03-31 08:48:37', '2025-05-23 11:22:29', 'active'),
(154, 'PROD128', 'Cutting Board', 85, 10, NULL, 400.00, 1, 30.00, 520.00, '../uploads/product_images/product_67ea5778b2e24.jpeg', 'wooden', 'kitchen tools & gatgets', 1, '2025-03-31 08:51:04', '2025-05-07 13:34:45', 'active'),
(155, 'PROD129', 'Whisk & Spatula', 75, 10, NULL, 250.00, 2, 30.00, 325.00, '../uploads/product_images/product_67ea57b17a275.jpeg', 'eversilver', 'kitchen tools & gatgets', 1, '2025-03-31 08:52:01', '2025-05-23 11:22:29', 'active'),
(158, 'PROD132', 'Glassware & Mugs', 45, 10, NULL, 350.00, 1, 30.00, 455.00, '../uploads/product_images/product_67ea58ac85056.jpeg', 'glass', 'dining & serving essentials', 1, '2025-03-31 08:56:12', '2025-05-07 13:34:45', 'active'),
(159, 'PROD133', 'Serving Bowls', 67, 10, NULL, 500.00, 2, 35.00, 675.00, '../uploads/product_images/product_67ea58d96466e.jpeg', 'glass', 'dining & serving essentials', 1, '2025-03-31 08:56:57', '2025-05-23 11:22:29', 'active'),
(161, 'PROD135', 'Table Mats & Coasters', 100, 10, NULL, 300.00, 1, 35.00, 405.00, '../uploads/product_images/product_67ea5a3660759.jpeg', 'cotton', 'dining & serving essentials', 1, '2025-03-31 08:59:35', '2025-05-07 13:34:45', 'active'),
(162, 'PROD136', 'Copper Water Bottle', 80, 10, NULL, 600.00, 2, 35.00, 810.00, '../uploads/product_images/product_67ea59ea6c59a.jpeg', 'copper', 'dining & serving essentials', 0, '2025-03-31 09:01:30', '2025-04-01 07:31:25', 'active'),
(164, 'PROD138', 'Copper Bottom Kadai', 74, 10, NULL, 600.00, 1, 35.00, 810.00, '../uploads/product_images/product_67ea5b01c0598.jpeg', 'copper', 'cookware', 1, '2025-03-31 09:06:09', '2025-05-07 13:34:45', 'active'),
(165, 'PROD139', 'EverSilver Dinner Plates', 76, 10, NULL, 100.00, 2, 20.00, 120.00, '../uploads/product_images/product_67ea5b3a4a4da.jpeg', 'eversilver', 'Utensils & Cutlery', 1, '2025-03-31 09:07:06', '2025-05-23 11:22:29', 'active'),
(167, 'PROD141', 'Breeding Box', 110, 10, '../uploads/barcodes/PROD141.png', 23.00, 2, 12.00, 25.76, '../uploads/product_images/product_680c50940dbb9.jpeg', 'cast Iron', 'bakeware', 1, '2025-04-19 05:04:22', '2025-05-23 11:22:29', 'active'),
(168, 'PROD142', 'copper thali set', 34, 10, NULL, 45.00, 2, 10.00, 49.50, '../uploads/product_images/product_680c4ffc08fb3.jpeg', 'copper', 'kitchen tools & gatgets', 1, '2025-04-19 05:12:51', '2025-05-23 11:22:29', 'active'),
(170, 'PROD143', 'whisks', 20, 10, NULL, 150.00, 2, 20.00, 180.00, '../uploads/product_images/product_680c5134c7084.jpeg', 'eversilver', 'bakeware', 1, '2025-04-26 03:21:24', '2025-05-23 11:22:29', 'active');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `address_line1` varchar(255) NOT NULL,
  `city` varchar(100) NOT NULL,
  `state` varchar(100) NOT NULL,
  `postal_code` varchar(20) NOT NULL,
  `user_type` enum('user','admin') NOT NULL,
  `password` varchar(255) NOT NULL,
  `profile_image` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `theme_preference` varchar(10) DEFAULT 'light',
  `color_scheme` varchar(10) DEFAULT 'default',
  `font_size` varchar(10) DEFAULT 'medium',
  `auto_logout` int(11) DEFAULT 30,
  `theme` varchar(10) DEFAULT 'light',
  `primary_color` varchar(7) DEFAULT '#5D3FD3',
  `secondary_color` varchar(7) DEFAULT '#f39c12',
  `font_color` varchar(7) DEFAULT '#333333',
  `auto_logout_time` int(11) DEFAULT 1800
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `phone`, `address_line1`, `city`, `state`, `postal_code`, `user_type`, `password`, `profile_image`, `created_at`, `theme_preference`, `color_scheme`, `font_size`, `auto_logout`, `theme`, `primary_color`, `secondary_color`, `font_color`, `auto_logout_time`) VALUES
(6, 'deepak', 'deepak123@gmail.com', '9632580147', 'pasuvanthanai', 'srivi', 'Tamil Nadu', '626189', 'user', '$2y$10$zl98KCvLoQeiwx4M9c67WuB1C.QnLmEXTtN8mn9TvvI0gGwoHbd/e', 'uploads/profilephoto/profile_67c0904bd1059.jpg', '2025-02-27 15:35:21', 'light', 'default', 'medium', 30, 'light', '#5D3FD3', '#f39c12', '#333333', 1800),
(7, 'vicky', 'vicky@gmail.com', '9876543210', 'pilayar patti', 'sivakasi', 'Tamil Nadu', '626189', 'user', '$2y$10$SB47qhv2yGvM9NrLrkwXO.i0uR1xFjMOpSw5GPWY.6dDnmec18fs6', 'uploads/profilephoto/profile_67c087d98e6d5.jpg', '2025-02-27 15:42:17', 'dark', 'default', '18px', 30, 'custom', '#bce34f', '#947cd5', '#ea9999', 120),
(8, 'Ashok', 'ashok@gmail.com', '987456133', '10, 2nd street velayuthapuram kovilpatti', 'Madurai', 'Tamil Nadu', '628501', 'admin', '$2y$10$ZpDfDhsCI9WilJ0WCvZuiuj2chOnc2VtRgZRTV1SwcgdVTJWIP3bm', 'uploads/profilephoto/profile_67e56fefeee05.jpg', '2025-02-27 15:45:12', 'light', 'default', 'small', 1, 'light', '#5D3FD3', '#f39c12', '#333333', 1800),
(9, 'Anandha', 'anandha@gmail.com', '9874561230', '10, 2nd street velayuthapuram kovilpatti', 'kovilpatti', 'Tamil Nadu', '628501', 'admin', '$2y$10$Zdoq0.qEKXgAAG6GMvPRTutN02sXSbQQDHeOKOhNz2uNd6uAyE0kG', 'uploads/profilephoto/profile_67cd5a28ccae6.jpg', '2025-02-27 16:16:43', 'dark', 'default', 'medium', 30, 'light', '#5D3FD3', '#f39c12', '#333333', 1800),
(10, 'kailash', 'kai@gmail.com', '9874563210', '10 2nd street velayuthapuram', 'kovilpatti', 'tamilnadu', '628501', 'user', '$2y$10$faw96OBjeExh5kO8VsyKA.9O8C5oI6o2m89oreAjX.6Ln1Fj0P90m', 'uploads/profilephoto/profile_67d7fa098c059.jpg', '2025-03-17 10:31:37', 'light', 'default', 'medium', 30, 'light', '#5D3FD3', '#f39c12', '#333333', 1800),
(11, 'Anandha krishnan', 'anandhakrishnangv@gmail.com', '9597702956', '10, 2nd street velayuthapuram kovilpatti', 'Madurai', 'Tamil Nadu', '628501', '', '$2y$10$HnBx2TFa8KOxj/xrpex7lOGqfLFKWQyx9H5KcY18FK24DOpGg7Cg6', 'uploads/profilephoto/profile_67d98a116c992.jpg', '2025-03-18 14:58:25', 'light', 'default', 'medium', 30, 'light', '#5D3FD3', '#f39c12', '#333333', 1800),
(12, 'java', 'java@gmail.com', '8637634523', 'veeravanchi nagar virthachalam', 'viruthachalam', 'tamilnadu', '678001', 'user', '$2y$10$P9tfCJjvzMofSBQDRkHhA.b8bPCU2Y28QhSWQByEmWRAJg7XVt4tu', 'uploads/profilephoto/profile_67d98a9d489b1.jpg', '2025-03-18 15:00:45', 'light', 'default', 'medium', 30, 'light', '#5D3FD3', '#f39c12', '#333333', 1800),
(13, 'dhanush', 'dhanushkraja@gmail.com', '9874561230', '123 2nd street vsrivi', 'thiruvarur', 'tamilnadu', '684562', 'user', '$2y$10$pbgFDv37xvSVMe9KAJCGiev2SCjEHIwMB.wAucNLlgXQ95.o6L6au', 'uploads/profilephoto/profile_67e0f78b0de07.jpg', '2025-03-24 06:11:23', 'light', 'default', 'medium', 30, 'light', '#5D3FD3', '#f39c12', '#333333', 1800),
(16, 'bike', 'bike@gmail.com', '9874563201', '67/567 srivi street', 'sivakasi', 'Tamil Nadu', '685210', 'admin', '$2y$10$CRRksO0t3Ly7eLh6OmmMb.p/MSczGkua2xBl1FhMMJnV/itAgiXoq', 'uploads/profilephoto/profile_67e0fc0305703.jpg', '2025-03-24 06:30:27', 'light', 'default', 'medium', 30, 'light', '#5D3FD3', '#f39c12', '#333333', 1800),
(17, 'godfather', 'godfather@gmail.com', '9876543210', 'newyork', 'queens', 'srivi', '869521', 'admin', '$2y$10$ycOXjPyo/jrNI66lcFvyReVZ44GnEVD/y.KXweioex5XglSpRnVEe', 'uploads/profilephoto/profile_67e56fc6e7f90.jpg', '2025-03-27 15:31:03', 'light', 'default', 'medium', 30, 'light', '#5D3FD3', '#f39c12', '#333333', 1800),
(18, 'Mari kishore', 'kishore@gmail.com', '9632587410', '12/234 srivi sivakasi', 'sivakasi', 'tamilnadu', '987456', 'user', '$2y$10$Id0jWR9/rXrJcyuY2UUzmOw4Ib4OaJoQGctz9.FRxrOHCB/UGF7ry', 'uploads/profilephoto/profile_6824733908656.jpg', '2025-05-14 10:40:57', 'light', 'default', 'medium', 30, 'light', '#5D3FD3', '#f39c12', '#333333', 1800);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `agencies`
--
ALTER TABLE `agencies`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `agency_code` (`agency_code`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `bills`
--
ALTER TABLE `bills`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `bill_no` (`bill_no`);

--
-- Indexes for table `bill_items`
--
ALTER TABLE `bill_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `bill_id` (`bill_id`);

--
-- Indexes for table `canceled_bills`
--
ALTER TABLE `canceled_bills`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `canceled_bill_items`
--
ALTER TABLE `canceled_bill_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `canceled_bill_id` (`canceled_bill_id`);

--
-- Indexes for table `cart`
--
ALTER TABLE `cart`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `cart_items`
--
ALTER TABLE `cart_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `cart_id` (`cart_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `ecancel`
--
ALTER TABLE `ecancel`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `ecancel_items`
--
ALTER TABLE `ecancel_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `cancel_id` (`cancel_id`);

--
-- Indexes for table `esales`
--
ALTER TABLE `esales`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `order_id` (`order_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `esales_items`
--
ALTER TABLE `esales_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `esales_id` (`esales_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `materials`
--
ALTER TABLE `materials`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `material_name` (`material_name`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `product_id` (`product_id`),
  ADD KEY `agency_id` (`agency_id`);

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
-- AUTO_INCREMENT for table `agencies`
--
ALTER TABLE `agencies`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `bills`
--
ALTER TABLE `bills`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT for table `bill_items`
--
ALTER TABLE `bill_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=39;

--
-- AUTO_INCREMENT for table `canceled_bills`
--
ALTER TABLE `canceled_bills`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `canceled_bill_items`
--
ALTER TABLE `canceled_bill_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `cart`
--
ALTER TABLE `cart`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `cart_items`
--
ALTER TABLE `cart_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=87;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `ecancel`
--
ALTER TABLE `ecancel`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ecancel_items`
--
ALTER TABLE `ecancel_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `esales`
--
ALTER TABLE `esales`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `esales_items`
--
ALTER TABLE `esales_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT for table `materials`
--
ALTER TABLE `materials`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=171;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `bill_items`
--
ALTER TABLE `bill_items`
  ADD CONSTRAINT `bill_items_ibfk_1` FOREIGN KEY (`bill_id`) REFERENCES `bills` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `canceled_bill_items`
--
ALTER TABLE `canceled_bill_items`
  ADD CONSTRAINT `canceled_bill_items_ibfk_1` FOREIGN KEY (`canceled_bill_id`) REFERENCES `canceled_bills` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `cart`
--
ALTER TABLE `cart`
  ADD CONSTRAINT `cart_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `cart_items`
--
ALTER TABLE `cart_items`
  ADD CONSTRAINT `cart_items_ibfk_1` FOREIGN KEY (`cart_id`) REFERENCES `cart` (`id`),
  ADD CONSTRAINT `cart_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`);

--
-- Constraints for table `ecancel`
--
ALTER TABLE `ecancel`
  ADD CONSTRAINT `ecancel_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `esales` (`id`),
  ADD CONSTRAINT `ecancel_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `ecancel_items`
--
ALTER TABLE `ecancel_items`
  ADD CONSTRAINT `ecancel_items_ibfk_1` FOREIGN KEY (`cancel_id`) REFERENCES `ecancel` (`id`);

--
-- Constraints for table `esales`
--
ALTER TABLE `esales`
  ADD CONSTRAINT `esales_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `esales_items`
--
ALTER TABLE `esales_items`
  ADD CONSTRAINT `esales_items_ibfk_1` FOREIGN KEY (`esales_id`) REFERENCES `esales` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `esales_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`agency_id`) REFERENCES `agencies` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
