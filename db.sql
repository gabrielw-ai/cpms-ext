-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Jan 13, 2025 at 03:57 PM
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
-- Database: `ubuntu`
--

-- --------------------------------------------------------

--
-- Table structure for table `ccs_rules`
--

CREATE TABLE `ccs_rules` (
  `id` int(11) NOT NULL,
  `project` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `nik` varchar(50) NOT NULL,
  `role` varchar(100) NOT NULL,
  `tenure` varchar(100) NOT NULL,
  `case_chronology` text DEFAULT NULL,
  `consequences` varchar(50) NOT NULL,
  `effective_date` date NOT NULL,
  `end_date` date NOT NULL,
  `supporting_doc_url` varchar(255) DEFAULT NULL,
  `status` enum('active','expired') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `ccs_rules`
--

INSERT INTO `ccs_rules` (`id`, `project`, `name`, `nik`, `role`, `tenure`, `case_chronology`, `consequences`, `effective_date`, `end_date`, `supporting_doc_url`, `status`, `created_at`) VALUES
(29, 'gec_st', 'agents', '1111111', 'Agent', '5 days', 'asdfasd', 'WR1', '2025-01-02', '2026-01-01', 'uploads/ccs_docs/doc_6777fdb39b196.xlsx', 'active', '2025-01-03 15:09:39'),
(30, 'gec_st', 'agents_2', '1111112', 'Agent', '5 days', 'asdfa', 'WR1', '2025-01-06', '2026-01-05', 'uploads/ccs_docs/doc_6777feb510a24.xlsx', 'active', '2025-01-03 15:13:57'),
(32, 'gec_st', 'agents', '1', 'Agent', '0 year(s)', 'test', 'WR1', '2025-01-02', '2026-01-01', 'uploads/ccs_docs/doc_6778da58296db.xlsx', 'active', '2025-01-04 06:51:04'),
(33, 'gec_mod', 'agents-mod', '11', 'Agent', '0 year(s)', 'sadfafa', 'WR1', '2025-01-05', '2026-01-04', 'uploads/ccs_docs/doc_677a388d2bca7.xlsx', 'active', '2025-01-05 07:45:17'),
(34, 'gec_st', 'TLST', '22', 'Team Leader', '0 year(s)', 'testing', 'WL2', '2025-01-05', '2025-07-04', 'uploads/ccs_docs/doc_677a3c2fa0f67.xlsx', 'active', '2025-01-05 08:00:47'),
(35, 'gec_st', 'OM_Testing_ST', '6', 'Operational Manager', '0 year(s)', 'dasdfasd', 'WR1', '2025-01-05', '2026-01-04', 'uploads/ccs_docs/doc_677a3c476c38e.xlsx', 'active', '2025-01-05 08:01:11'),
(36, 'gec_st', 'tl', '2', 'Team Leader', '0 year(s)', 'dffasfasdfa', 'WL3', '2025-01-05', '2025-07-04', 'uploads/ccs_docs/doc_677a441c9d976.xlsx', 'active', '2025-01-05 08:34:36');

-- --------------------------------------------------------

--
-- Table structure for table `employee_active`
--

CREATE TABLE `employee_active` (
  `NIK` varchar(20) NOT NULL,
  `employee_name` varchar(100) NOT NULL,
  `employee_email` varchar(100) NOT NULL,
  `role` varchar(50) NOT NULL,
  `project` varchar(100) NOT NULL,
  `join_date` date NOT NULL,
  `password` varchar(500) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `employee_active`
--

INSERT INTO `employee_active` (`NIK`, `employee_name`, `employee_email`, `role`, `project`, `join_date`, `password`) VALUES
('1', 'agents', 'agents_1@gmail.com', 'Agent', 'GEC_ST', '2025-01-04', '$2y$10$GZnYiYE6ta743xEs.m9uB.s5/iJePUZXtPfISZKmEha1y9U7IZ8KG'),
('11', 'agents-mod', 'agents-mod@gmail.com', 'Agent', 'GEC_MOD', '2025-01-04', '$2y$10$.HdxcK1K5DB2yYTUZX75ze9DeUjeowQg/8qf4XEGSoy9mJfT4M5/e'),
('1111111', 'agents', 'agents@gmail.com', 'Agent', 'GEC_ST', '2024-12-29', '$2y$10$/hco2ML9f0IXbuxsItFFDOedyFiroqliww4tZ7GJW3u9kvHk6Ce0C'),
('1111112', 'agents_2', 'agents_2@gmail.com', 'Agent', 'GEC_ST', '2024-12-29', '$2y$10$zDeVHVUfaEbmcmbz7hiCW.tFj6Yzwu1ngKaJBZWTO.7S8dOaeF1jy'),
('2', 'tl', 'tl@gmail.com', 'Team Leader', 'GEC_ST', '2024-12-31', '$2y$10$NA2EunxLYKMfKdAzKdLUM.yQ9NgHS2QGGEKBp0WWF5mRA.U6oDYLa'),
('22', 'TLST', 'tlsttest@gmail.com', 'Team Leader', 'GEC_ST', '2025-01-05', '$2y$10$WDzt3Yi17xKjQA7chsjvhe8FQ0aRQXb8TlfQEt.l0kNEgW7EWqMXK'),
('2210507', 'Gabriel Dwi', 'gabriel.novian@trans-cosmos.co.id', 'Unit Manager', 'GEC_ST', '2022-05-12', '$2y$10$IObE8rrrgmPyP338FcKLCeo8pLhvKCWJyFPodlpyNqJokd3hoqrau'),
('3', 'mis', 'mis@test.com', 'MIS Analyst', 'GEC_ST', '2025-01-05', '$2y$10$vTRpZHZICk.u4tGMw7AmEu0tYtkdVOlFnjxq3M8SDsG7KppkgUu5e'),
('6', 'OM_Testing_ST', 'om@testingst.com', 'Operational Manager', 'GEC_ST', '2025-01-05', '$2y$10$Rft4EEp01V49wsjA0VxXd.K4IDORxBPXB04l01pGlOuwMS2vl1Hce');

-- --------------------------------------------------------

--
-- Table structure for table `individual_staging`
--

CREATE TABLE `individual_staging` (
  `id` int(11) NOT NULL,
  `NIK` varchar(20) DEFAULT NULL,
  `employee_name` varchar(100) DEFAULT NULL,
  `kpi_metrics` varchar(50) DEFAULT NULL,
  `queue` varchar(50) DEFAULT NULL,
  `january` decimal(5,2) DEFAULT NULL,
  `february` decimal(5,2) DEFAULT NULL,
  `march` decimal(5,2) DEFAULT NULL,
  `april` decimal(5,2) DEFAULT NULL,
  `may` decimal(5,2) DEFAULT NULL,
  `june` decimal(5,2) DEFAULT NULL,
  `july` decimal(5,2) DEFAULT NULL,
  `august` decimal(5,2) DEFAULT NULL,
  `september` decimal(5,2) DEFAULT NULL,
  `october` decimal(5,2) DEFAULT NULL,
  `november` decimal(5,2) DEFAULT NULL,
  `december` decimal(5,2) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `individual_staging`
--

INSERT INTO `individual_staging` (`id`, `NIK`, `employee_name`, `kpi_metrics`, `queue`, `january`, `february`, `march`, `april`, `may`, `june`, `july`, `august`, `september`, `october`, `november`, `december`, `created_at`) VALUES
(14, '2242691', 'Aditya Ilham Farohi', 'SL 01', 'Buyer', 100.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2024-12-23 13:10:24'),
(15, '2242691', 'Aditya Ilham Farohi', 'RSAT', 'Buyer', 100.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2024-12-23 13:10:24');

-- --------------------------------------------------------

--
-- Table structure for table `kpi_gec_st`
--

CREATE TABLE `kpi_gec_st` (
  `id` int(11) NOT NULL,
  `queue` varchar(255) NOT NULL,
  `kpi_metrics` varchar(255) NOT NULL,
  `target` varchar(50) NOT NULL,
  `target_type` varchar(20) NOT NULL,
  `week1` decimal(10,2) DEFAULT NULL,
  `week2` decimal(10,2) DEFAULT NULL,
  `week3` decimal(10,2) DEFAULT NULL,
  `week4` decimal(10,2) DEFAULT NULL,
  `week5` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `kpi_gec_st`
--

INSERT INTO `kpi_gec_st` (`id`, `queue`, `kpi_metrics`, `target`, `target_type`, `week1`, `week2`, `week3`, `week4`, `week5`) VALUES
(9, 'Buyer', 'Resolution Rate', '29', 'percentage', NULL, NULL, NULL, NULL, NULL),
(10, 'Seller', 'SSAT', '79', 'percentage', NULL, NULL, NULL, NULL, NULL),
(73, 'OM_test', 'KPI_OM', '01', 'percentage', NULL, NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `kpi_gec_st_individual`
--

CREATE TABLE `kpi_gec_st_individual` (
  `id` int(11) NOT NULL,
  `nik` varchar(50) NOT NULL,
  `employee_name` varchar(255) NOT NULL,
  `queue` varchar(255) NOT NULL,
  `kpi_metrics` varchar(255) NOT NULL,
  `week1` decimal(10,2) DEFAULT NULL,
  `week2` decimal(10,2) DEFAULT NULL,
  `week3` decimal(10,2) DEFAULT NULL,
  `week4` decimal(10,2) DEFAULT NULL,
  `week5` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `kpi_gec_st_individual_mon`
--

CREATE TABLE `kpi_gec_st_individual_mon` (
  `id` int(11) NOT NULL,
  `nik` varchar(50) NOT NULL,
  `employee_name` varchar(255) NOT NULL,
  `queue` varchar(255) NOT NULL,
  `kpi_metrics` varchar(255) NOT NULL,
  `january` decimal(10,2) DEFAULT NULL,
  `february` decimal(10,2) DEFAULT NULL,
  `march` decimal(10,2) DEFAULT NULL,
  `april` decimal(10,2) DEFAULT NULL,
  `may` decimal(10,2) DEFAULT NULL,
  `june` decimal(10,2) DEFAULT NULL,
  `july` decimal(10,2) DEFAULT NULL,
  `august` decimal(10,2) DEFAULT NULL,
  `september` decimal(10,2) DEFAULT NULL,
  `october` decimal(10,2) DEFAULT NULL,
  `november` decimal(10,2) DEFAULT NULL,
  `december` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `kpi_gec_st_individual_mon`
--

INSERT INTO `kpi_gec_st_individual_mon` (`id`, `nik`, `employee_name`, `queue`, `kpi_metrics`, `january`, `february`, `march`, `april`, `may`, `june`, `july`, `august`, `september`, `october`, `november`, `december`) VALUES
(16, '1111111', 'agents', 'Buyer', 'Resolution Rate', 90.00, 90.00, 90.00, 90.00, 90.00, 90.00, NULL, NULL, NULL, NULL, NULL, NULL),
(17, '1111112', 'agents_2', 'Buyer', 'Resolution Rate', 75.00, 75.00, 75.00, 75.00, 75.00, 75.00, NULL, NULL, NULL, NULL, NULL, NULL),
(20, '1', 'agents', 'Buyer', 'Resolution Rate', 90.00, 91.00, 91.00, 91.00, 91.00, 91.00, NULL, NULL, NULL, NULL, NULL, NULL),
(30, '1', 'agents', 'Seller', 'SSAT', 0.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(39, '1', 'agents', 'OM_test', 'KPI_OM', 75.00, 75.00, 75.00, 75.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `kpi_gec_st_mon`
--

CREATE TABLE `kpi_gec_st_mon` (
  `id` int(11) NOT NULL,
  `queue` varchar(255) NOT NULL,
  `kpi_metrics` varchar(255) NOT NULL,
  `target` varchar(50) NOT NULL,
  `target_type` varchar(20) NOT NULL,
  `january` decimal(10,2) DEFAULT NULL,
  `february` decimal(10,2) DEFAULT NULL,
  `march` decimal(10,2) DEFAULT NULL,
  `april` decimal(10,2) DEFAULT NULL,
  `may` decimal(10,2) DEFAULT NULL,
  `june` decimal(10,2) DEFAULT NULL,
  `july` decimal(10,2) DEFAULT NULL,
  `august` decimal(10,2) DEFAULT NULL,
  `september` decimal(10,2) DEFAULT NULL,
  `october` decimal(10,2) DEFAULT NULL,
  `november` decimal(10,2) DEFAULT NULL,
  `december` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `kpi_gec_st_mon`
--

INSERT INTO `kpi_gec_st_mon` (`id`, `queue`, `kpi_metrics`, `target`, `target_type`, `january`, `february`, `march`, `april`, `may`, `june`, `july`, `august`, `september`, `october`, `november`, `december`) VALUES
(9, 'Buyer', 'Resolution Rate', '29', 'percentage', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(10, 'Seller', 'SSAT', '79', 'percentage', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(71, 'OM_test', 'KPI_OM', '01', 'percentage', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `kpi_gec_st_mon_values`
--

CREATE TABLE `kpi_gec_st_mon_values` (
  `id` int(11) NOT NULL,
  `kpi_id` int(11) NOT NULL,
  `month` int(11) NOT NULL,
  `value` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `kpi_gec_st_mon_values`
--

INSERT INTO `kpi_gec_st_mon_values` (`id`, `kpi_id`, `month`, `value`) VALUES
(34, 9, 1, 50.00),
(35, 9, 2, 50.00),
(36, 9, 3, 50.00),
(37, 9, 4, 50.00),
(38, 9, 5, 50.00),
(39, 9, 6, 50.00),
(40, 9, 7, 50.00),
(41, 9, 8, 50.00),
(42, 9, 9, 50.00),
(43, 9, 10, 50.00),
(44, 9, 11, 50.00),
(45, 9, 12, 50.00),
(99, 10, 1, 50.00);

-- --------------------------------------------------------

--
-- Table structure for table `kpi_gec_st_values`
--

CREATE TABLE `kpi_gec_st_values` (
  `id` int(11) NOT NULL,
  `kpi_id` int(11) NOT NULL,
  `week` int(11) NOT NULL,
  `value` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `kpi_gec_st_values`
--

INSERT INTO `kpi_gec_st_values` (`id`, `kpi_id`, `week`, `value`) VALUES
(10, 9, 1, 13.00),
(11, 9, 2, 13.00),
(12, 9, 3, 13.00),
(13, 9, 4, 13.00),
(14, 9, 5, 13.00),
(15, 9, 6, 13.00),
(16, 9, 7, 13.00),
(17, 9, 8, 13.00),
(26, 10, 1, 12.00),
(27, 10, 2, 13.00),
(28, 10, 3, 13.00),
(29, 10, 4, 13.00),
(30, 10, 5, 13.00),
(31, 10, 6, 13.00),
(32, 10, 7, 13.00),
(33, 10, 8, 13.00);

-- --------------------------------------------------------

--
-- Table structure for table `project_namelist`
--

CREATE TABLE `project_namelist` (
  `id` int(11) NOT NULL,
  `main_project` varchar(255) NOT NULL,
  `project_name` varchar(255) NOT NULL,
  `unit_name` varchar(255) NOT NULL,
  `job_code` varchar(50) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `project_namelist`
--

INSERT INTO `project_namelist` (`id`, `main_project`, `project_name`, `unit_name`, `job_code`, `created_at`) VALUES
(17, 'TikTok', 'GEC_ST', 'u', 'dfafasfas', '2024-12-28 10:23:47'),
(24, 'TikTok', 'GEC_MOD', 'Unit 4 OPG ', '12312312', '2025-01-04 07:02:52');

-- --------------------------------------------------------

--
-- Table structure for table `role_mgmt`
--

CREATE TABLE `role_mgmt` (
  `id` int(11) NOT NULL,
  `role` varchar(50) NOT NULL,
  `privileges` int(11) DEFAULT 2
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `role_mgmt`
--

INSERT INTO `role_mgmt` (`id`, `role`, `privileges`) VALUES
(1, 'General Manager', 6),
(2, 'Unit Manager', 6),
(3, 'Sr. Operational Manager', 3),
(4, 'Operational Manager', 4),
(5, 'TQA Manager', 2),
(6, 'Quality Analyst', 3),
(7, 'Trainer', 3),
(8, 'MIS Analyst', 3),
(9, 'Admin', 3),
(10, 'Agent', 1),
(21, 'Team Leader', 2),
(22, 'RTFM', 2),
(23, 'SME', 2),
(24, 'Quality Supervisor', 2),
(25, 'TnQ Leader', 2),
(26, 'Supervisor', 2),
(27, 'WFM', 2),
(28, 'Conselor', 2),
(29, 'Quality Manager', 2);

-- --------------------------------------------------------

--
-- Table structure for table `uac`
--

CREATE TABLE `uac` (
  `id` int(11) NOT NULL,
  `role_name` varchar(50) NOT NULL,
  `menu_access` text NOT NULL,
  `read` enum('0','1') DEFAULT '0',
  `write` enum('0','1') DEFAULT '0',
  `delete` enum('0','1') DEFAULT '0',
  `created_by` varchar(50) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_by` varchar(50) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `uac`
--

INSERT INTO `uac` (`id`, `role_name`, `menu_access`, `read`, `write`, `delete`, `created_by`, `created_at`, `updated_by`, `updated_at`) VALUES
(3, 'Operational Manager', '[\"kpi_metrics\",\"kpi_viewer\",\"chart_generator\",\"employee_list\"]', '1', '1', '0', '2210507', '2024-12-21 08:52:48', '2210507', '2024-12-21 10:38:23'),
(4, 'Unit Manager', '[\"kpi_metrics\",\"kpi_viewer\",\"chart_generator\",\"employee_list\",\"add_ccs_rules\",\"ccs_viewer\",\"project_namelist\",\"role_management\"]', '1', '1', '1', '2210507', '2024-12-21 08:59:57', '2210507', '2024-12-21 12:45:19'),
(5, 'General Manager', '[\"kpi_metrics\",\"kpi_viewer\",\"chart_generator\",\"employee_list\",\"add_ccs_rules\",\"ccs_viewer\",\"project_namelist\",\"role_management\"]', '1', '1', '1', '2210507', '2024-12-21 09:00:30', NULL, '0000-00-00 00:00:00'),
(6, 'Team Leader', '[\"add_ccs_rules\"]', '0', '0', '0', '2210507', '2024-12-21 09:52:16', '2210507', '2024-12-21 09:58:27');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `ccs_rules`
--
ALTER TABLE `ccs_rules`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `employee_active`
--
ALTER TABLE `employee_active`
  ADD PRIMARY KEY (`NIK`);

--
-- Indexes for table `individual_staging`
--
ALTER TABLE `individual_staging`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_employee_kpi` (`NIK`,`kpi_metrics`,`queue`);

--
-- Indexes for table `kpi_gec_st`
--
ALTER TABLE `kpi_gec_st`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_queue_kpi` (`queue`,`kpi_metrics`);

--
-- Indexes for table `kpi_gec_st_individual`
--
ALTER TABLE `kpi_gec_st_individual`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_employee_kpi` (`nik`,`queue`,`kpi_metrics`);

--
-- Indexes for table `kpi_gec_st_individual_mon`
--
ALTER TABLE `kpi_gec_st_individual_mon`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_employee_kpi` (`nik`,`queue`,`kpi_metrics`);

--
-- Indexes for table `kpi_gec_st_mon`
--
ALTER TABLE `kpi_gec_st_mon`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_queue_kpi` (`queue`,`kpi_metrics`);

--
-- Indexes for table `kpi_gec_st_mon_values`
--
ALTER TABLE `kpi_gec_st_mon_values`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_record` (`kpi_id`,`month`);

--
-- Indexes for table `kpi_gec_st_values`
--
ALTER TABLE `kpi_gec_st_values`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_record` (`kpi_id`,`week`);

--
-- Indexes for table `project_namelist`
--
ALTER TABLE `project_namelist`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `role_mgmt`
--
ALTER TABLE `role_mgmt`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `role` (`role`);

--
-- Indexes for table `uac`
--
ALTER TABLE `uac`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_role` (`role_name`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `ccs_rules`
--
ALTER TABLE `ccs_rules`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

--
-- AUTO_INCREMENT for table `individual_staging`
--
ALTER TABLE `individual_staging`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `kpi_gec_st`
--
ALTER TABLE `kpi_gec_st`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=77;

--
-- AUTO_INCREMENT for table `kpi_gec_st_individual`
--
ALTER TABLE `kpi_gec_st_individual`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `kpi_gec_st_individual_mon`
--
ALTER TABLE `kpi_gec_st_individual_mon`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=55;

--
-- AUTO_INCREMENT for table `kpi_gec_st_mon`
--
ALTER TABLE `kpi_gec_st_mon`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=75;

--
-- AUTO_INCREMENT for table `kpi_gec_st_mon_values`
--
ALTER TABLE `kpi_gec_st_mon_values`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=102;

--
-- AUTO_INCREMENT for table `kpi_gec_st_values`
--
ALTER TABLE `kpi_gec_st_values`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=129;

--
-- AUTO_INCREMENT for table `project_namelist`
--
ALTER TABLE `project_namelist`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `role_mgmt`
--
ALTER TABLE `role_mgmt`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

--
-- AUTO_INCREMENT for table `uac`
--
ALTER TABLE `uac`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `kpi_gec_st_mon_values`
--
ALTER TABLE `kpi_gec_st_mon_values`
  ADD CONSTRAINT `kpi_gec_st_mon_values_ibfk_1` FOREIGN KEY (`kpi_id`) REFERENCES `kpi_gec_st_mon` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `kpi_gec_st_values`
--
ALTER TABLE `kpi_gec_st_values`
  ADD CONSTRAINT `kpi_gec_st_values_ibfk_1` FOREIGN KEY (`kpi_id`) REFERENCES `kpi_gec_st` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
