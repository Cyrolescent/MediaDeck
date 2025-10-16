-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 16, 2025 at 08:24 AM
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
-- Database: `mediadeck`
--

-- --------------------------------------------------------

--
-- Table structure for table `media`
--

CREATE TABLE `media` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `type` enum('image','video','audio','text') NOT NULL,
  `storage_type` enum('upload','link') NOT NULL,
  `file_path` varchar(500) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `rating` tinyint(4) DEFAULT 0 CHECK (`rating` between 0 and 5),
  `is_favorite` tinyint(1) DEFAULT 0,
  `thumbnail` varchar(500) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `media`
--

INSERT INTO `media` (`id`, `title`, `type`, `storage_type`, `file_path`, `notes`, `rating`, `is_favorite`, `thumbnail`, `created_at`, `updated_at`) VALUES
(2, 'Dog image test', 'image', 'link', 'https://media.istockphoto.com/id/1340073038/photo/samoyed-a-white-big-fluffy-dog-in-the-park.jpg?s=1024x1024&w=is&k=20&c=14js5IlI1wrDeW0kT62Hs08jc7zDUpc1ox4ooi2uUoU=', 'CUTEST FLUFFLIEST DOG EVER', 4, 1, NULL, '2025-10-04 14:58:17', '2025-10-12 23:14:34'),
(3, 'Sunset Beach Picture', 'image', 'link', 'https://img.freepik.com/free-photo/sunset-time-tropical-beach-sea-with-coconut-palm-tree_74190-1075.jpg?semt=ais_hybrid&w=740&q=80', 'a picture of a sunset at the beach', 4, 0, NULL, '2025-10-04 20:26:49', '2025-10-04 20:26:49'),
(4, 'Pop In 2 music video', 'video', 'link', 'https://youtu.be/BTRpMqyzBFg', 'music video popin2 youtube video', 3, 0, NULL, '2025-10-09 11:36:03', '2025-10-09 11:36:30'),
(12, 'doc', 'text', 'upload', 'uploads/2025-10-15_11-29-01_68ef695d4a144.txt', 'random readme', 0, 1, NULL, '2025-10-15 17:29:01', '2025-10-15 17:30:23'),
(13, 'frame img', 'image', 'upload', 'uploads/2025-10-15_12-20-47_68ef757f51c32.png', 'fram tes', 1, 1, NULL, '2025-10-15 18:20:47', '2025-10-15 18:20:47'),
(14, 'Test video', 'video', 'upload', 'uploads/2025-10-16_02-04-38_68f0369605463.mp4', '', 0, 0, NULL, '2025-10-16 08:04:38', '2025-10-16 08:04:38'),
(15, 'better when im dancing', 'audio', 'upload', 'uploads/2025-10-16_07-11-04_68f07e68edb0c.mp3', 'song exam ple', 5, 1, 'uploads/thumb_2025-10-16_07-11-04_68f07e68f2fab.jpg', '2025-10-16 13:11:04', '2025-10-16 13:11:04');

-- --------------------------------------------------------

--
-- Table structure for table `media_tags`
--

CREATE TABLE `media_tags` (
  `media_id` int(11) NOT NULL,
  `tag_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tags`
--

CREATE TABLE `tags` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `category` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `media`
--
ALTER TABLE `media`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `media_tags`
--
ALTER TABLE `media_tags`
  ADD PRIMARY KEY (`media_id`,`tag_id`),
  ADD KEY `tag_id` (`tag_id`);

--
-- Indexes for table `tags`
--
ALTER TABLE `tags`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `media`
--
ALTER TABLE `media`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `tags`
--
ALTER TABLE `tags`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `media_tags`
--
ALTER TABLE `media_tags`
  ADD CONSTRAINT `media_tags_ibfk_1` FOREIGN KEY (`media_id`) REFERENCES `media` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `media_tags_ibfk_2` FOREIGN KEY (`tag_id`) REFERENCES `tags` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
