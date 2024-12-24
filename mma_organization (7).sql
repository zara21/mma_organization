-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 24, 2024 at 12:14 PM
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
-- Database: `mma_organization`
--

DELIMITER $$
--
-- Procedures
--
CREATE DEFINER=`root`@`localhost` PROCEDURE `PopulateFighters` ()   BEGIN
    DECLARE i INT DEFAULT 1;
    DECLARE maxFighters INT DEFAULT 100;
    DECLARE firstNames TEXT DEFAULT 'John,Michael,David,Chris,James,Daniel,Robert,Paul,Mark,Tom';
    DECLARE lastNames TEXT DEFAULT 'Smith,Johnson,Williams,Jones,Brown,Davis,Miller,Wilson,Moore,Taylor';
    DECLARE nationalities TEXT DEFAULT 'USA,Georgia,Brazil,Russia,Canada,UK,Australia,France,Italy,Japan';

    WHILE i <= maxFighters DO
        SET @firstName = ELT(FLOOR(1 + (RAND() * 10)), SUBSTRING_INDEX(firstNames, ',', 1), SUBSTRING_INDEX(SUBSTRING_INDEX(firstNames, ',', 2), ',', -1), SUBSTRING_INDEX(SUBSTRING_INDEX(firstNames, ',', 3), ',', -1), SUBSTRING_INDEX(SUBSTRING_INDEX(firstNames, ',', 4), ',', -1), SUBSTRING_INDEX(SUBSTRING_INDEX(firstNames, ',', 5), ',', -1), SUBSTRING_INDEX(SUBSTRING_INDEX(firstNames, ',', 6), ',', -1), SUBSTRING_INDEX(SUBSTRING_INDEX(firstNames, ',', 7), ',', -1), SUBSTRING_INDEX(SUBSTRING_INDEX(firstNames, ',', 8), ',', -1), SUBSTRING_INDEX(SUBSTRING_INDEX(firstNames, ',', 9), ',', -1), SUBSTRING_INDEX(SUBSTRING_INDEX(firstNames, ',', 10), ',', -1));
        SET @lastName = ELT(FLOOR(1 + (RAND() * 10)), SUBSTRING_INDEX(lastNames, ',', 1), SUBSTRING_INDEX(SUBSTRING_INDEX(lastNames, ',', 2), ',', -1), SUBSTRING_INDEX(SUBSTRING_INDEX(lastNames, ',', 3), ',', -1), SUBSTRING_INDEX(SUBSTRING_INDEX(lastNames, ',', 4), ',', -1), SUBSTRING_INDEX(SUBSTRING_INDEX(lastNames, ',', 5), ',', -1), SUBSTRING_INDEX(SUBSTRING_INDEX(lastNames, ',', 6), ',', -1), SUBSTRING_INDEX(SUBSTRING_INDEX(lastNames, ',', 7), ',', -1), SUBSTRING_INDEX(SUBSTRING_INDEX(lastNames, ',', 8), ',', -1), SUBSTRING_INDEX(SUBSTRING_INDEX(lastNames, ',', 9), ',', -1), SUBSTRING_INDEX(SUBSTRING_INDEX(lastNames, ',', 10), ',', -1));
        SET @nationality = ELT(FLOOR(1 + (RAND() * 10)), SUBSTRING_INDEX(nationalities, ',', 1), SUBSTRING_INDEX(SUBSTRING_INDEX(nationalities, ',', 2), ',', -1), SUBSTRING_INDEX(SUBSTRING_INDEX(nationalities, ',', 3), ',', -1), SUBSTRING_INDEX(SUBSTRING_INDEX(nationalities, ',', 4), ',', -1), SUBSTRING_INDEX(SUBSTRING_INDEX(nationalities, ',', 5), ',', -1), SUBSTRING_INDEX(SUBSTRING_INDEX(nationalities, ',', 6), ',', -1), SUBSTRING_INDEX(SUBSTRING_INDEX(nationalities, ',', 7), ',', -1), SUBSTRING_INDEX(SUBSTRING_INDEX(nationalities, ',', 8), ',', -1), SUBSTRING_INDEX(SUBSTRING_INDEX(nationalities, ',', 9), ',', -1), SUBSTRING_INDEX(SUBSTRING_INDEX(nationalities, ',', 10), ',', -1));

        INSERT INTO fighters (name, nickname, birthdate, age, height, weight, nationality, photo, wins, losses, draws, slug, weight_class_id, ranking)
        VALUES (
            CONCAT(@firstName, ' ', @lastName),
            CONCAT('The ', LEFT(@lastName, 1), '-Fighter'),
            DATE_SUB(CURDATE(), INTERVAL FLOOR(20 + (RAND() * 15)) YEAR),
            FLOOR(20 + (RAND() * 15)),
            ROUND((1.60 + (RAND() * 0.40)), 2),
            ROUND((60 + (RAND() * 40)), 1),
            @nationality,
            IF(RAND() > 0.5, '../uploads/MORENO_BRANDON_L_06-12.png', '../uploads/DVALISHVILI_MERAB_L_BELTMOCK.png'),
            FLOOR(RAND() * 30),
            FLOOR(RAND() * 15),
            FLOOR(RAND() * 5),
            CONCAT(LOWER(REPLACE(@firstName, ' ', '-')), '-', LOWER(REPLACE(@lastName, ' ', '-'))),
            FLOOR(1 + (RAND() * 7)),
            FLOOR(RAND() * 10)
        );

        SET i = i + 1;
    END WHILE;
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `admin_actions`
--

CREATE TABLE `admin_actions` (
  `action_id` int(11) NOT NULL,
  `admin_id` int(11) DEFAULT NULL,
  `action_type` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `events`
--

CREATE TABLE `events` (
  `event_id` int(11) NOT NULL,
  `e_name` varchar(255) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `event_date` datetime DEFAULT NULL,
  `main_fighter1_id` int(11) NOT NULL,
  `main_fighter2_id` int(11) NOT NULL,
  `banner` varchar(255) NOT NULL,
  `ticket_link` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `additional_link` varchar(255) DEFAULT NULL,
  `status` enum('Upcoming','Live','Past') NOT NULL DEFAULT 'Upcoming'
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `events`
--

INSERT INTO `events` (`event_id`, `e_name`, `title`, `slug`, `event_date`, `main_fighter1_id`, `main_fighter2_id`, `banner`, `ticket_link`, `created_at`, `additional_link`, `status`) VALUES
(62, 'UFC 311 Makhachev vs Tsarukyan 2', 'UFC 311 Makhachev vs Tsarukyan 2', 'ufc-311-makhachev-vs-tsarukyan-2', '2025-01-09 12:00:00', 253, 255, '6762845e38e05_47638-17333718351268-1920.avif', 'https://www.youtube.com', '2024-12-18 08:14:22', '', 'Upcoming'),
(63, 'UFC Fight Night Petr Yan vs Merab Dvalishvili', 'UFC Fight Night Petr Yan vs Merab Dvalishvili', 'ufc-fight-night-petr-yan-vs-merab-dvalishvili', '2024-02-28 12:00:00', 253, 256, '6762851574e7f_Petr-Yan-Merab-Dvalishvili-UFC-758x505.jpg', 'https://www.youtube.com', '2024-12-18 08:17:25', '', 'Past'),
(65, 'UFC 322: Merab Dvalishvili Vs Ilia Topuria', 'UFC 322 Merab Dvalishvili Vs Ilia Topuria', 'ufc-322-merab-dvalishvili-vs-ilia-topuria', '2024-12-31 17:06:00', 249, 249, '6762851574e7f_Petr-Yan-Merab-Dvalishvili-UFC-758x505.jpg', '', '2024-12-04 13:06:52', '', 'Upcoming');

-- --------------------------------------------------------

--
-- Table structure for table `event_fights`
--

CREATE TABLE `event_fights` (
  `fight_id` int(11) NOT NULL,
  `event_id` int(11) NOT NULL,
  `fighter1_id` int(11) NOT NULL,
  `fighter2_id` int(11) NOT NULL,
  `result` enum('Win','Loss','Draw','Pending') DEFAULT 'Pending'
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `event_fights`
--

INSERT INTO `event_fights` (`fight_id`, `event_id`, `fighter1_id`, `fighter2_id`, `result`) VALUES
(128, 62, 253, 255, ''),
(129, 62, 250, 249, ''),
(130, 62, 252, 256, ''),
(131, 62, 254, 256, 'Draw'),
(132, 63, 253, 256, 'Loss'),
(133, 63, 249, 250, 'Win'),
(134, 63, 251, 252, 'Win'),
(135, 63, 254, 255, 'Win'),
(140, 65, 249, 252, ''),
(141, 65, 251, 250, ''),
(142, 65, 253, 256, ''),
(143, 65, 254, 255, '');

-- --------------------------------------------------------

--
-- Table structure for table `fighters`
--

CREATE TABLE `fighters` (
  `fighter_id` int(11) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `nickname` varchar(100) DEFAULT NULL,
  `birthdate` date DEFAULT NULL,
  `age` int(11) DEFAULT NULL,
  `height` decimal(3,2) DEFAULT NULL,
  `weight` decimal(4,1) DEFAULT NULL,
  `nationality` varchar(50) DEFAULT NULL,
  `photo` varchar(255) DEFAULT NULL,
  `wins` int(11) DEFAULT 0,
  `losses` int(11) DEFAULT 0,
  `draws` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `slug` varchar(255) NOT NULL,
  `weight_class_id` int(11) DEFAULT NULL,
  `ranking` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `fighters`
--

INSERT INTO `fighters` (`fighter_id`, `name`, `nickname`, `birthdate`, `age`, `height`, `weight`, `nationality`, `photo`, `wins`, `losses`, `draws`, `created_at`, `slug`, `weight_class_id`, `ranking`) VALUES
(249, 'Alexandre Pantoja', 'The Cannibal', '1990-04-16', 34, 1.00, 57.0, 'Brazil', '/uploads/PANTOJA_ALEXANDRE_L_BELT_12-07.png', 31, 6, 0, '2024-12-18 07:55:06', 'alexandre-pantoja', 1, 0),
(250, 'Brandon Royval', 'Raw Dawg', '1992-08-16', 32, 1.00, 57.0, 'USA', '/uploads/ROYVAL_BRANDON_L_10-12.png', 18, 9, 0, '2024-12-18 07:57:56', 'brandon-royval', 1, 1),
(251, 'Brandon Moreno', 'The Assassin Baby', '1993-12-07', 29, 1.00, 57.0, 'Mexico', '/uploads/MORENO_BRANDON_L_06-12.png', 29, 9, 2, '2024-12-18 07:59:37', 'brandon-moreno', 1, 2),
(252, 'Amir Albazi', 'The Prince', '1993-09-27', 31, 1.00, 57.0, 'Iraq', '/uploads/ALBAZI_AMIR_L_06-03.png', 19, 3, 0, '2024-12-18 08:01:03', 'amir-albazi', 1, 3),
(253, 'Merab Dvalishvili', 'The Machine', '1991-01-10', 33, 1.00, 62.0, 'Georgia', '/uploads/DVALISHVILI_MERAB_L_BELTMOCK.png', 21, 4, 0, '2024-12-18 08:03:32', 'merab-dvalishvili', 2, 0),
(254, 'Sean O Malley', 'Suga', '1994-08-24', 30, 9.99, 61.0, 'USA', '/uploads/OMALLEY_SEAN_L_08-19.png', 20, 2, 1, '2024-12-18 08:06:50', 'sean-o-malley', 2, 1),
(255, 'Umar Nurmagomedov', 'Umar ', '1996-03-03', 28, 9.99, 61.0, 'Russia', '/uploads/NURMAGOMEDOV_UMAR_L_03-02.png', 18, 3, 0, '2024-12-18 08:08:52', 'umar-nurmagomedov', 2, 2),
(256, 'Petr Yan', 'No Mercy', '1993-02-11', 31, 9.99, 61.0, 'Russia', '/uploads/YAN_PETR_L_11-23.png', 18, 8, 1, '2024-12-18 08:10:18', 'petr-yan', 2, 3);

-- --------------------------------------------------------

--
-- Table structure for table `fights`
--

CREATE TABLE `fights` (
  `fight_id` int(11) NOT NULL,
  `fighter1_id` int(11) DEFAULT NULL,
  `fighter2_id` int(11) DEFAULT NULL,
  `date` date DEFAULT NULL,
  `location` varchar(100) DEFAULT NULL,
  `result` varchar(50) DEFAULT NULL,
  `method` varchar(50) DEFAULT NULL,
  `event_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `news`
--

CREATE TABLE `news` (
  `news_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `author` varchar(100) DEFAULT NULL,
  `publish_date` datetime DEFAULT current_timestamp(),
  `image_url` varchar(255) DEFAULT NULL,
  `category` enum('News','Live','Review','Schedule','Event','Announcement','Highlights','Interviews','Opinion') NOT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `video_url` varchar(255) DEFAULT NULL,
  `video_file` varchar(255) DEFAULT NULL,
  `gallery_images` text DEFAULT NULL,
  `tags` varchar(255) DEFAULT NULL,
  `view_count` int(11) DEFAULT 0,
  `related_news` text DEFAULT NULL,
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `is_featured` tinyint(1) DEFAULT 0,
  `comments_enabled` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `news`
--

INSERT INTO `news` (`news_id`, `title`, `slug`, `content`, `author`, `publish_date`, `image_url`, `category`, `status`, `video_url`, `video_file`, `gallery_images`, `tags`, `view_count`, `related_news`, `updated_at`, `is_featured`, `comments_enabled`) VALUES
(26, 'The Submissions | 2024 UFC.com Awards', 'the-submissions-2024', 'the highly unofficial awards season continues with the best submissions of 2024 and how we saw them on fight night...', 'Giorgi', '2024-12-18 10:51:17', '676270e58cc65_102624-Khamzat-Chimaev-Submission-GettyImages-2181229425.jpg', 'Highlights', 'active', '', NULL, NULL, '0', 0, NULL, '2024-12-18 10:51:17', 0, 1),
(27, 'UFC Fight Night: Covington vs Buckley', 'ufc-fight-night-covington-vs-buckley', 'As we bid farewell to another incredible year, the Octagon returned to Tampa, Florida for a highlight reel packed night. Welterweight stars Colby Covington and Joaquin Buckley headlined the main event, while Cub Swanson continued to build on his legendary career in the co-main against Billy Quarantillo. Check out who else lit up the stage at Amalie Arena!', 'Giorgi', '2024-12-18 11:08:24', '676274e8af5da_GettyImages-2190011019 (1).jpg', 'News', 'active', '', NULL, NULL, '0', 0, NULL, '2024-12-18 11:08:24', 0, 1),
(28, 'Official Scorecards | UFC 310: Pantoja vs Asakura', 'official-scorecards-ufc-310-pantoja-vs-asakura', 'UFC flyweight champion Alexandre Pantoja closed out UFC\'s 2024 pay-per-view schedule in style, submitting highly touted newcomer Kai Asakura in the second round. In the co-main event, Shavkat Rakhmonov and Ian Machado Garry proved why they\'re two of the best at 170 pounds in a grueling five-round battle. Here\'s how the judges scored every round of every fight:', 'Giorgi', '2024-12-18 11:13:20', '6762761064dce_Alexandre-Pantoja-and-Kai-Asakura.webp', 'News', 'active', '', NULL, NULL, '0', 0, NULL, '2024-12-18 11:13:20', 0, 1),
(29, '11 fights, 11 finishes: Inside the UFC\'s first perfect night, 10 years later', '11-fights-11-finishe-inside-the-ufcs-first-perfect-night-10-years-later', 'Editor\'s note: The story\'s original version identified UFC Fight Night: Rockhold vs. Bisping as the only UFC card to feature finishes in every fight. There was a second \"perfect\" fight card in 2022 at UFC Vegas 59 that featured 10 finishes in 10 fights. The story has been updated to reflect this.\r\n\r\nBy the time 2024 comes to a close, there will have been 716 events in the 31 years that the Ultimate Fighting Championship has existed. But only two fight cards -- unless UFC Fight Night in Tampa, Florida, on Saturday pulls off a miracle -- will have the distinction of being \"perfect.\"', 'Giorgi', '2024-12-18 11:16:56', '676276e86e099_i.jpeg', 'News', 'active', '', NULL, NULL, '0', 0, NULL, '2024-12-18 11:16:56', 0, 1),
(30, '\'Money changes everything\': Inside the seven-year free fall of Conor McGregor', 'money-changes-everything', 'THE FANTASY BECAME a reality as Floyd Mayweather and Conor McGregor stood face-to-face in a memorable picture taken on July 12, 2017, by lauded combat photographer Esther Lin. The two fighters were at the Budweiser Stage in Toronto on the second stop of a world tour to promote their upcoming boxing match on Aug. 26, 2017.\r\n\r\nMcGregor, dressed to the nines in a royal blue suit, his arm outstretched in Mayweather\'s face, beckoning him to bring it on. Mayweather, wearing black jeans, a black The Money Team T-shirt with a matching baseball cap -- and perhaps millions in jewelry -- pointing at his Irish opponent.', 'Giorgi', '2024-12-18 11:19:53', '6762779989035_i (1).jpeg', 'Highlights', 'active', 'https://www.espn.com/video/clip/_/id/23042050', NULL, NULL, '0', 0, NULL, '2024-12-18 11:19:53', 0, 1),
(31, 'Tom Nolan added to UFC 312: \'Going to put myself on the map\'', 'tom-nolan-added-to-ufc-312', '\"Old mate definitely came to fight, which was good,\" Nolan said, \"Pretty tough, he had a lot of movement, he had a game plan to go out and not get finished; I think his last two were pretty bad finishes. I think he honestly knew he wasn\'t going to beat me, but he had a pretty good game plan to stay away from me.\r\n\r\n\"There was a moment when I hurt him pretty bad and I came close to finishing him, but it was all experience for me, time on the clock; staying more patient was a big one for that fight. And I think it gives me pieces of the puzzle now to add to my style to have a patient, finishing game.', '', '2024-12-18 11:21:08', '676277e49ab91_i.jpg', 'News', 'active', '', NULL, NULL, '0', 0, NULL, '2024-12-18 11:21:08', 0, 1),
(32, 'Israel Adesanya vs. Nassourdine Imavov tops', 'israel-adesanya-vs-nassourdine-imavov-tops', 'Former UFC middleweight champion Israel Adesanya will compete in his first nontitle fight since 2019 when he faces Nassourdine Imavov in the main event of UFC Fight Night at the ANB Arena in Riyadh, Saudi Arabia, on Feb. 1.\r\n\r\nAdesanya (24-4) is coming off consecutive losses for the first time in his MMA career. \"The Last Stylebender\" dropped the middleweight title in a stunning upset by Sean Strickland in September 2023 and fell short in his attempt to regain the 185-pound title when he was submitted by current champion Dricus du Plessis in August.', '', '2024-12-18 11:23:10', '6762785e08bf3_mgj8nt609xakmpiojoxe.jpg', 'Highlights', 'active', 'https://www.youtube.com/watch?v=7kPYnUg8i-M&ab_channel=UFC', NULL, NULL, '0', 0, NULL, '2024-12-18 11:23:10', 0, 1),
(33, 'Joaquin Buckley batters Colby Covington as doctor halts bout', 'joaquin-buckley-batters-colby-covington-as-doctor-halts-bout', 'There\'s a new era of welterweights taking over the UFC, and Joaquin Buckley is undeniably one of them.\r\n\r\nBuckley (21-6) extended his winning streak to six Saturday with a third-round TKO of former interim champion Colby Covington (17-5). The 170-pound bout, which headlined the UFC\'s final card of 2024 in Tampa, Florida, was called at the 4:42 mark of the round when a cageside physician stopped it because of a cut over Covington\'s right eye.', '', '2024-12-18 11:25:06', '676278d2f0270_ufc-tampa-joaquin-buckley-colby-covington-19.webp', 'Highlights', 'active', 'https://www.youtube.com/watch?v=W013JNF-Vqg&ab_channel=enderlook', NULL, NULL, '0', 0, NULL, '2024-12-18 11:25:06', 0, 1);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `role` enum('user','admin') DEFAULT 'user',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_admin` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `username`, `password`, `email`, `role`, `created_at`, `is_admin`) VALUES
(2, 'testuser', 'hashed_password', 'testuser@example.com', 'user', '2024-11-04 07:29:38', 0),
(3, 'adminuser', 'hashed_password', 'admin@example.com', 'admin', '2024-11-04 07:29:38', 1),
(4, 'giorgi', '$2y$10$gJIPQlpL7Rsfk3dIYGvZmeGReHqVC29tYOMlUPVDdDvFnm5c9dfgq', 'giorgi.zaraspishvili@gmail.com', 'admin', '2024-11-04 07:30:27', 1),
(5, '', '$2y$10$pc1tFkvhJSGeJLnDJ.0sXOF2qWXhu8dXDsK9Pf.I0Wlmd0URabCYy', 'zaraspishvili@gmail.com', 'user', '2024-11-04 07:40:45', 0),
(6, '1@gmail.com', '$2y$10$VAP31gzWnaEUBHhwtnAGz.w6vcxdroA/OR9FcZOWqlb7cnQwy4Wka', '1@gmail.com', 'user', '2024-11-04 07:52:45', 0),
(7, '1@gmail.com', '$2y$10$fmIB7Gy9DHDe0UoDpzaNIu/MA7kVVXqfqdGmVvs5AThP6jcxgHROK', '1@gmail.com', 'user', '2024-11-04 10:30:08', 0),
(8, '1@gmail.com', '$2y$10$/5SoDGv.3jnR72sgrgKk/.8WoJhMDV8IcE0YT62Ho6i0V4b9iU5TW', '1@gmail.com', 'user', '2024-11-04 10:30:23', 0),
(9, 'usein bolt', '$2y$10$7dJ6IrzOUkQt7C2Dz2VLseUxXcj1ir/0uLqaqMG6j8GnSEKK16yGa', 'bolt@gmail.com', 'user', '2024-12-06 08:14:14', 0);

-- --------------------------------------------------------

--
-- Table structure for table `weight_classes`
--

CREATE TABLE `weight_classes` (
  `weight_class_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `weight_classes`
--

INSERT INTO `weight_classes` (`weight_class_id`, `name`) VALUES
(1, '40'),
(2, '50'),
(3, '60'),
(4, '70'),
(5, '80'),
(6, '90'),
(7, '100');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin_actions`
--
ALTER TABLE `admin_actions`
  ADD PRIMARY KEY (`action_id`),
  ADD KEY `admin_id` (`admin_id`);

--
-- Indexes for table `events`
--
ALTER TABLE `events`
  ADD PRIMARY KEY (`event_id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `main_fighter1_id` (`main_fighter1_id`),
  ADD KEY `main_fighter2_id` (`main_fighter2_id`);

--
-- Indexes for table `event_fights`
--
ALTER TABLE `event_fights`
  ADD PRIMARY KEY (`fight_id`),
  ADD KEY `event_id` (`event_id`),
  ADD KEY `fighter1_id` (`fighter1_id`),
  ADD KEY `fighter2_id` (`fighter2_id`);

--
-- Indexes for table `fighters`
--
ALTER TABLE `fighters`
  ADD PRIMARY KEY (`fighter_id`),
  ADD KEY `weight_class_id` (`weight_class_id`);

--
-- Indexes for table `fights`
--
ALTER TABLE `fights`
  ADD PRIMARY KEY (`fight_id`),
  ADD KEY `fighter1_id` (`fighter1_id`),
  ADD KEY `fighter2_id` (`fighter2_id`);

--
-- Indexes for table `news`
--
ALTER TABLE `news`
  ADD PRIMARY KEY (`news_id`),
  ADD UNIQUE KEY `slug` (`slug`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`);

--
-- Indexes for table `weight_classes`
--
ALTER TABLE `weight_classes`
  ADD PRIMARY KEY (`weight_class_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin_actions`
--
ALTER TABLE `admin_actions`
  MODIFY `action_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `events`
--
ALTER TABLE `events`
  MODIFY `event_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=67;

--
-- AUTO_INCREMENT for table `event_fights`
--
ALTER TABLE `event_fights`
  MODIFY `fight_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=144;

--
-- AUTO_INCREMENT for table `fighters`
--
ALTER TABLE `fighters`
  MODIFY `fighter_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=257;

--
-- AUTO_INCREMENT for table `fights`
--
ALTER TABLE `fights`
  MODIFY `fight_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=60;

--
-- AUTO_INCREMENT for table `news`
--
ALTER TABLE `news`
  MODIFY `news_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=34;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `weight_classes`
--
ALTER TABLE `weight_classes`
  MODIFY `weight_class_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `admin_actions`
--
ALTER TABLE `admin_actions`
  ADD CONSTRAINT `admin_actions_ibfk_1` FOREIGN KEY (`admin_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `events`
--
ALTER TABLE `events`
  ADD CONSTRAINT `events_ibfk_1` FOREIGN KEY (`main_fighter1_id`) REFERENCES `fighters` (`fighter_id`),
  ADD CONSTRAINT `events_ibfk_2` FOREIGN KEY (`main_fighter2_id`) REFERENCES `fighters` (`fighter_id`);

--
-- Constraints for table `event_fights`
--
ALTER TABLE `event_fights`
  ADD CONSTRAINT `event_fights_ibfk_1` FOREIGN KEY (`event_id`) REFERENCES `events` (`event_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `event_fights_ibfk_2` FOREIGN KEY (`fighter1_id`) REFERENCES `fighters` (`fighter_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `event_fights_ibfk_3` FOREIGN KEY (`fighter2_id`) REFERENCES `fighters` (`fighter_id`) ON DELETE CASCADE;

--
-- Constraints for table `fighters`
--
ALTER TABLE `fighters`
  ADD CONSTRAINT `fighters_ibfk_1` FOREIGN KEY (`weight_class_id`) REFERENCES `weight_classes` (`weight_class_id`);

--
-- Constraints for table `fights`
--
ALTER TABLE `fights`
  ADD CONSTRAINT `fights_ibfk_1` FOREIGN KEY (`fighter1_id`) REFERENCES `fighters` (`fighter_id`),
  ADD CONSTRAINT `fights_ibfk_2` FOREIGN KEY (`fighter2_id`) REFERENCES `fighters` (`fighter_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
