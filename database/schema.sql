-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Aug 01, 2026 at 09:17 PM
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
-- Database: `gamebook_template`
--

-- --------------------------------------------------------

--
-- Table structure for table `characters`
--

CREATE TABLE `characters` (
  `id` int(11) NOT NULL,
  `name` longtext NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `choice`
--

CREATE TABLE `choice` (
  `id` int(11) NOT NULL,
  `decision_id` int(11) NOT NULL,
  `label` varchar(255) NOT NULL,
  `place_id` int(11) DEFAULT NULL,
  `text` longtext NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `decision`
--

CREATE TABLE `decision` (
  `id` int(11) NOT NULL,
  `name` longtext NOT NULL,
  `finished` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `decision_links`
--

CREATE TABLE `decision_links` (
  `id` int(11) NOT NULL,
  `decision_id` int(11) NOT NULL,
  `link_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `links`
--

CREATE TABLE `links` (
  `id` int(11) NOT NULL,
  `place_id` int(11) DEFAULT NULL,
  `choice_id` int(11) DEFAULT NULL,
  `page_id` int(11) NOT NULL,
  `first_date` datetime DEFAULT NULL,
  `last_date` datetime DEFAULT NULL,
  `text_place` longtext DEFAULT NULL,
  `text_page` longtext DEFAULT NULL,
  `text_decision` longtext DEFAULT NULL,
  `text_navigation` longtext DEFAULT NULL,
  `bubble_field_index` int(11) NOT NULL DEFAULT 0,
  `length` int(11) NOT NULL DEFAULT 1,
  `regenerated` int(11) NOT NULL DEFAULT 0,
  `finished` int(11) NOT NULL DEFAULT 0,
  `page_nr` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `link_objects`
--

CREATE TABLE `link_objects` (
  `id` int(11) NOT NULL,
  `link_id` int(11) NOT NULL,
  `object_id` int(11) NOT NULL,
  `object_type` text NOT NULL,
  `number1` int(11) NOT NULL DEFAULT 0,
  `number2` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `objects`
--

CREATE TABLE `objects` (
  `id` int(11) NOT NULL,
  `name` longtext NOT NULL,
  `set_text` longtext DEFAULT '',
  `unset_text` longtext DEFAULT '',
  `if_text` longtext DEFAULT '',
  `if_not_text` longtext DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `pages`
--

CREATE TABLE `pages` (
  `id` int(11) NOT NULL,
  `text` text DEFAULT NULL,
  `comments` longtext DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `places`
--

CREATE TABLE `places` (
  `id` INT NOT NULL,
  `name` LONGTEXT NOT NULL,
  `text1` LONGTEXT DEFAULT '',
  `text2` LONGTEXT DEFAULT '',
  `text3` LONGTEXT DEFAULT '',
  `text4` LONGTEXT DEFAULT '',
  `text5` LONGTEXT DEFAULT '',
  `parent_id` INT DEFAULT NULL,

  PRIMARY KEY (`id`)
) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `skills`
--

CREATE TABLE `skills` (
  `id` int(11) NOT NULL,
  `name` longtext NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ways`
--

CREATE TABLE `ways` (
  `id` INT NOT NULL,
  `start` INT NOT NULL,
  `end` INT NOT NULL,
  `bidirectional` INT NOT NULL,

  PRIMARY KEY (`id`),

  CONSTRAINT `fk_ways_start`
    FOREIGN KEY (`start`)
    REFERENCES `places`(`id`),

  CONSTRAINT `fk_ways_end`
    FOREIGN KEY (`end`)
    REFERENCES `places`(`id`)
) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `way_objects`
--

CREATE TABLE `way_objects` (
  `id` int(11) NOT NULL,
  `way_id` int(11) NOT NULL,
  `object_id` int(11) NOT NULL,
  `object_type` text NOT NULL,
  `number1` int(11) NOT NULL DEFAULT 0,
  `number2` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `characters`
--
ALTER TABLE `characters`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `choice`
--
ALTER TABLE `choice`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `choice`
  ADD CONSTRAINT `fk_choice_decision`
    FOREIGN KEY (`decision_id`) REFERENCES `decision` (`id`),
  ADD CONSTRAINT `fk_choice_place`
    FOREIGN KEY (`place_id`) REFERENCES `places` (`id`);

--
-- Indexes for table `decision`
--
ALTER TABLE `decision`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `decision_links`
--
ALTER TABLE `decision_links`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `decision_links`
  ADD CONSTRAINT `fk_decision_links_decision`
    FOREIGN KEY (`decision_id`) REFERENCES `decision` (`id`),
  ADD CONSTRAINT `fk_decision_links_link`
    FOREIGN KEY (`link_id`) REFERENCES `links` (`id`);

--
-- Indexes for table `links`
--
ALTER TABLE `links`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `links`
  ADD CONSTRAINT `fk_links_place`
    FOREIGN KEY (`place_id`) REFERENCES `places` (`id`),
  ADD CONSTRAINT `fk_links_choice`
    FOREIGN KEY (`choice_id`) REFERENCES `choice` (`id`),
  ADD CONSTRAINT `fk_links_page`
    FOREIGN KEY (`page_id`) REFERENCES `pages` (`id`);

--
-- Indexes for table `link_objects`
--
ALTER TABLE `link_objects`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `link_objects`
  ADD CONSTRAINT `fk_link_objects_link`
    FOREIGN KEY (`link_id`) REFERENCES `links` (`id`);

--
-- Indexes for table `objects`
--
ALTER TABLE `objects`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `pages`
--
ALTER TABLE `pages`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `places`
--
ALTER TABLE `places`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `places`
  ADD CONSTRAINT `fk_places_parent`
    FOREIGN KEY (`parent_id`) REFERENCES `places` (`id`);

--
-- Indexes for table `skills`
--
ALTER TABLE `skills`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `ways`
--
ALTER TABLE `ways`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `way_objects`
--
ALTER TABLE `way_objects`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `way_objects`
  ADD CONSTRAINT `fk_way_objects_way`
    FOREIGN KEY (`way_id`) REFERENCES `ways` (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `characters`
--
ALTER TABLE `characters`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `choice`
--
ALTER TABLE `choice`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=193;

--
-- AUTO_INCREMENT for table `decision`
--
ALTER TABLE `decision`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=84;

--
-- AUTO_INCREMENT for table `decision_links`
--
ALTER TABLE `decision_links`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=81;

--
-- AUTO_INCREMENT for table `links`
--
ALTER TABLE `links`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=386;

--
-- AUTO_INCREMENT for table `link_objects`
--
ALTER TABLE `link_objects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=309;

--
-- AUTO_INCREMENT for table `objects`
--
ALTER TABLE `objects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT for table `pages`
--
ALTER TABLE `pages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=392;

--
-- AUTO_INCREMENT for table `places`
--
ALTER TABLE `places`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=83;

--
-- AUTO_INCREMENT for table `skills`
--
ALTER TABLE `skills`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `ways`
--
ALTER TABLE `ways`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=117;

--
-- AUTO_INCREMENT for table `way_objects`
--
ALTER TABLE `way_objects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
