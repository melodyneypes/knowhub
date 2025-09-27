-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 27, 2025 at 09:02 AM
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
-- Database: `archive2`
--

-- --------------------------------------------------------

--
-- Table structure for table `access_requests`
--

CREATE TABLE `access_requests` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL,
  `reason` text NOT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `request_type` varchar(50) DEFAULT 'general'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `activities`
--

CREATE TABLE `activities` (
  `id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL,
  `instructor_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `due_date` datetime NOT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `activities`
--

INSERT INTO `activities` (`id`, `subject_id`, `instructor_id`, `title`, `description`, `due_date`, `file_path`, `created_at`) VALUES
(3, 34, 3, 'SIA LAB Activity 1', '', '2025-09-22 00:00:00', 'E:\\CAP101-DANG FILES\\archive-system/uploads/activities/34_1758176044_SIA-lab1-activity.docx', '2025-09-18 14:14:04');

-- --------------------------------------------------------

--
-- Table structure for table `activity_reminders`
--

CREATE TABLE `activity_reminders` (
  `id` int(11) NOT NULL,
  `activity_id` int(11) NOT NULL,
  `instructor_id` int(11) NOT NULL,
  `reminder_date` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `admin_documents`
--

CREATE TABLE `admin_documents` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `file_path` varchar(255) NOT NULL,
  `file_type` varchar(20) DEFAULT NULL,
  `uploader_id` int(11) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin_documents`
--

INSERT INTO `admin_documents` (`id`, `title`, `description`, `file_path`, `file_type`, `uploader_id`, `created_at`, `updated_at`) VALUES
(1, 'thtrh', 'ntntn', '../../uploads/68d51cc457ddf_thtrh.docx', 'docx', 4, '2025-09-25 18:43:16', '2025-09-25 18:43:16');

-- --------------------------------------------------------

--
-- Table structure for table `alumni_requests`
--

CREATE TABLE `alumni_requests` (
  `id` int(11) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `status` varchar(50) DEFAULT 'pending',
  `email` varchar(100) DEFAULT NULL,
  `batch_year` int(11) DEFAULT NULL,
  `requested_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `alumni_requests`
--

INSERT INTO `alumni_requests` (`id`, `name`, `status`, `email`, `batch_year`, `requested_at`) VALUES
(1, 'Mikee Neypes', 'approved', 'habaduhabadiw@gmail.com', 2022, '2025-09-18 13:36:20');

-- --------------------------------------------------------

--
-- Table structure for table `downloads`
--

CREATE TABLE `downloads` (
  `id` int(11) NOT NULL,
  `resource_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `downloaded_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `exams`
--

CREATE TABLE `exams` (
  `id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `file_path` varchar(500) DEFAULT NULL,
  `exam_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `external_resources`
--

CREATE TABLE `external_resources` (
  `id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `url` varchar(255) NOT NULL,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `file_manager`
--

CREATE TABLE `file_manager` (
  `id` int(11) NOT NULL,
  `parent_id` int(11) DEFAULT NULL,
  `item_type` enum('file','folder') NOT NULL,
  `name` varchar(255) NOT NULL,
  `file_path` varchar(500) DEFAULT NULL,
  `uploader_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `instructor_resources`
--

CREATE TABLE `instructor_resources` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `title` varchar(255) NOT NULL,
  `url` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `instructor_resources`
--

INSERT INTO `instructor_resources` (`id`, `user_id`, `subject`, `title`, `url`, `created_at`) VALUES
(1, 3, 'Introduction to Computing', 'Introduction to Computer information Systems', 'https://en.wikibooks.org/wiki/Introduction_to_Computer_Information_Systems', '2025-08-30 03:07:41'),
(2, 3, 'Introduction to Computing', 'Computer Applications', 'https://www.tutorialspoint.com/articles/index.php', '2025-08-30 03:08:58'),
(3, 3, 'Introduction to Computing', '12 Technical Skills You Need', 'https://www.pgcareers.com/global/en/blogarticle/top-12-technical-skills-you-need', '2025-08-30 03:09:28'),
(4, 3, 'Introduction to Computing', 'Introduction to Computer Information Systems - Wikibooks', 'https://en.wikibooks.org/wiki/Introduction_to_Computer_Information_Systems', '2025-08-30 03:16:32'),
(5, 3, 'Introduction to Computing', 'Computer Applications - Tutorialspoint', 'https://www.tutorialspoint.com/computer_applications', '2025-08-30 03:16:32'),
(6, 3, 'Introduction to Computing', '10 Technical Skills - PG Careers', 'https://www.pgcareers.com/10-technical-skills', '2025-08-30 03:16:32'),
(7, 3, 'Introduction to Computing', 'Information Technology Sectors - ValuePenguin', 'https://www.valuepenguin.com/sectors/information-technology', '2025-08-30 03:16:32'),
(8, 3, 'Introduction to Computing', 'IT Certifications - ITCareerFinder', 'https://www.itcareerfinder.com/it-certifications.html', '2025-08-30 03:16:32'),
(9, 3, 'Introduction to Computing', 'Uses of Computers in Different Fields - InformationQ', 'https://www.informationq.com/uses-of-computers-in-different-fields-areas-sectors-industries-education/', '2025-08-30 03:16:32'),
(10, 3, 'Introduction to Computing', '2020 Emerging Technology Top 10 List - CompTIA', 'https://www.comptia.org/content/infographic/2020-emerging-technology-top-10-list', '2025-08-30 03:16:32'),
(11, 3, 'Introduction to Computing', 'ResearchGate Publication on Computing', 'https://www.researchgate.net/publication/281974460', '2025-08-30 03:16:32'),
(12, 3, 'Introduction to Computing', 'ICT Essentials - ICTCertified', 'https://www.ictcertified.com/ict-essentials/ict-essentials.php#', '2025-08-30 03:16:32'),
(13, 3, 'Introduction to Computing', 'Best Computer Jobs for the Future - ITCareerFinder', 'https://www.itcareerfinder.com/brain-food/blog/entry/best-computer-jobs-for-the-future.html', '2025-08-30 03:16:32'),
(14, 3, 'Introduction to Computing', 'Computing Disciplines Quick Guide - CERIC', 'https://ceric.ca/resource/computing-disciplines-quick-guide-prospective-students-career-advisors/', '2025-08-30 03:16:32'),
(15, 3, 'Introduction to Computing', 'Computer Generations - Tutorialspoint', 'https://www.tutorialspoint.com/computer_fundamentals/computer_generations.htm', '2025-08-30 03:16:32'),
(16, 3, 'Introduction to Computing', 'Computer Types - Tutorialspoint', 'https://www.tutorialspoint.com/computer_fundamentals/computer_types.htm', '2025-08-30 03:16:32'),
(17, 3, 'Introduction to Computing', 'Computer Components - Tutorialspoint', 'https://www.tutorialspoint.com/computer_fundamentals/computer_components.htm', '2025-08-30 03:16:32'),
(18, 3, 'Introduction to Computing', 'Types of Transmission Media - GeeksforGeeks', 'https://www.geeksforgeeks.org/types-transmission-media/', '2025-08-30 03:16:32'),
(19, 3, 'Introduction to Computing', 'Difference Between Straight Through and Crossover Cable', 'http://www.cables-solutions.com/difference-between-straight-through-and-crossover-cable.html', '2025-08-30 03:16:32'),
(20, 3, 'Introduction to Computing', 'Computer Security Overview - Tutorialspoint', 'https://www.tutorialspoint.com/computer_security/computer_security_overview.htm', '2025-08-30 03:16:32'),
(21, 3, 'Introduction to Computing', 'Computers and Society Security Privacy - Cal State LA', 'https://calstatela.edu/.../ComputersandSocietySecurityPrivacy thavy.ppt', '2025-08-30 03:16:32'),
(22, 3, 'Introduction to Computing', 'Computer Security PowerPoint - Granby High School', 'https://granby.k12.ct.us/HSStaff/dillon/Powerpointfiles/Computer Security.ppt', '2025-08-30 03:16:32'),
(23, 3, 'Introduction to Computing', 'Computer Number System - Tutorialspoint', 'https://www.tutorialspoint.com/computer_fundamentals/computer_number_system.htm', '2025-08-30 03:16:32'),
(24, 3, 'Introduction to Computing', 'Basics of Computers Number System - Tutorialspoint', 'https://www.tutorialspoint.com/basics_of_computers/basics_of_computers_number_system.htm', '2025-08-30 03:16:32'),
(25, 3, 'Introduction to Computing', 'HTML Text Links - Tutorialspoint', 'https://www.tutorialspoint.com/html/html_text_links.htm', '2025-08-30 03:16:32'),
(26, 3, 'Introduction to Computing', 'CSS Tutorial - Tutorialspoint', 'https://www.tutorialspoint.com/css', '2025-08-30 03:16:32'),
(27, 3, 'Introduction to Computing', 'HTML Tutorial - W3Schools', 'https://www.w3schools.com/html', '2025-08-30 03:16:32'),
(28, 3, 'Introduction to Computing', 'CSS Tutorial - W3Schools', 'https://www.w3schools.com/css', '2025-08-30 03:16:32'),
(29, 3, 'Introduction to Computing', '12 Technical Skills You Need', 'https://www.pgcareers.com/global/en/blogarticle/top-12-technical-skills-you-need', '2025-08-30 03:16:41'),
(30, 3, 'Fundamentals of Programming', 'Introduction to Computers and Programming', 'https://www.pearsonhighered.com/assets/samplechapter/0/3/2/1/0321537114.pdf', '2025-08-30 03:18:23');

-- --------------------------------------------------------

--
-- Table structure for table `messages`
--

CREATE TABLE `messages` (
  `id` int(11) NOT NULL,
  `room_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `content` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `migrations`
--

CREATE TABLE `migrations` (
  `id` int(10) UNSIGNED NOT NULL,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `migrations`
--

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
(1, '2025_08_26_124938_create_alumni_requests_table', 0),
(2, '2025_08_26_124938_create_downloads_table', 0),
(3, '2025_08_26_124938_create_external_resources_table', 0),
(4, '2025_08_26_124938_create_notifications_table', 0),
(5, '2025_08_26_124938_create_posts_table', 0),
(6, '2025_08_26_124938_create_replies_table', 0),
(7, '2025_08_26_124938_create_resource_tags_table', 0),
(8, '2025_08_26_124938_create_resource_versions_table', 0),
(9, '2025_08_26_124938_create_resources_table', 0),
(10, '2025_08_26_124938_create_subject_instructors_table', 0),
(11, '2025_08_26_124938_create_subject_students_table', 0),
(12, '2025_08_26_124938_create_subjects_table', 0),
(13, '2025_08_26_124938_create_threads_table', 0),
(14, '2025_08_26_124938_create_user_logs_table', 0),
(15, '2025_08_26_124938_create_users_table', 0),
(16, '2025_08_26_124941_add_foreign_keys_to_downloads_table', 0),
(17, '2025_08_26_124941_add_foreign_keys_to_external_resources_table', 0),
(18, '2025_08_26_124941_add_foreign_keys_to_notifications_table', 0),
(19, '2025_08_26_124941_add_foreign_keys_to_posts_table', 0),
(20, '2025_08_26_124941_add_foreign_keys_to_replies_table', 0),
(21, '2025_08_26_124941_add_foreign_keys_to_resource_tags_table', 0),
(22, '2025_08_26_124941_add_foreign_keys_to_resource_versions_table', 0),
(23, '2025_08_26_124941_add_foreign_keys_to_resources_table', 0),
(24, '2025_08_26_124941_add_foreign_keys_to_subject_instructors_table', 0),
(25, '2025_08_26_124941_add_foreign_keys_to_subject_students_table', 0),
(26, '2025_08_26_124941_add_foreign_keys_to_user_logs_table', 0),
(27, '2025_08_26_125829_create_sessions_table', 1),
(28, '2025_08_26_162828_create_alumni_requests_table', 0),
(29, '2025_08_26_162828_create_downloads_table', 0),
(30, '2025_08_26_162828_create_external_resources_table', 0),
(31, '2025_08_26_162828_create_notifications_table', 0),
(32, '2025_08_26_162828_create_posts_table', 0),
(33, '2025_08_26_162828_create_replies_table', 0),
(34, '2025_08_26_162828_create_resource_tags_table', 0),
(35, '2025_08_26_162828_create_resource_versions_table', 0),
(36, '2025_08_26_162828_create_resources_table', 0),
(37, '2025_08_26_162828_create_sessions_table', 0),
(38, '2025_08_26_162828_create_subject_instructors_table', 0),
(39, '2025_08_26_162828_create_subject_students_table', 0),
(40, '2025_08_26_162828_create_subjects_table', 0),
(41, '2025_08_26_162828_create_threads_table', 0),
(42, '2025_08_26_162828_create_user_logs_table', 0),
(43, '2025_08_26_162828_create_users_table', 0),
(44, '2025_08_26_162831_add_foreign_keys_to_downloads_table', 0),
(45, '2025_08_26_162831_add_foreign_keys_to_external_resources_table', 0),
(46, '2025_08_26_162831_add_foreign_keys_to_notifications_table', 0),
(47, '2025_08_26_162831_add_foreign_keys_to_posts_table', 0),
(48, '2025_08_26_162831_add_foreign_keys_to_replies_table', 0),
(49, '2025_08_26_162831_add_foreign_keys_to_resource_tags_table', 0),
(50, '2025_08_26_162831_add_foreign_keys_to_resource_versions_table', 0),
(51, '2025_08_26_162831_add_foreign_keys_to_resources_table', 0),
(52, '2025_08_26_162831_add_foreign_keys_to_subject_instructors_table', 0),
(53, '2025_08_26_162831_add_foreign_keys_to_subject_students_table', 0),
(54, '2025_08_26_162831_add_foreign_keys_to_user_logs_table', 0);

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp(),
  `title` varchar(255) DEFAULT 'Notification',
  `type` varchar(50) DEFAULT 'general',
  `sender_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `message`, `is_read`, `created_at`, `title`, `type`, `sender_id`) VALUES
(1, 3, 'admin replied to your post: Re: shfefinekmofdjew', 1, '2025-08-30 17:18:13', 'New Reply to Your Post', 'reply', NULL),
(4, 2, 'Instructor replied to your post: Re: hellooo\r\n', 0, '2025-09-26 18:07:25', 'New Reply to Your Post', 'reply', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `posts`
--

CREATE TABLE `posts` (
  `id` int(11) NOT NULL,
  `thread_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `content` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `quizzes`
--

CREATE TABLE `quizzes` (
  `id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `file_path` varchar(500) DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `replies`
--

CREATE TABLE `replies` (
  `id` int(11) NOT NULL,
  `thread_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `content` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `replies`
--

INSERT INTO `replies` (`id`, `thread_id`, `user_id`, `content`, `created_at`) VALUES
(3, 5, 2, 'helloo', '2025-08-25 02:56:33'),
(4, 4, 2, 'hi', '2025-08-25 02:56:41'),
(5, 4, 2, 'hehe', '2025-08-25 03:09:44'),
(6, 5, 3, 'hi', '2025-08-30 06:17:27'),
(7, 14, 3, 'kbhbuui', '2025-08-30 09:15:13'),
(8, 14, 3, 'frrgr', '2025-08-30 09:17:02'),
(9, 14, 4, 'eferr', '2025-08-30 09:18:13'),
(10, 5, 3, 'HOHOHOH', '2025-09-26 10:07:25');

-- --------------------------------------------------------

--
-- Table structure for table `resources`
--

CREATE TABLE `resources` (
  `id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL,
  `uploader_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `file_path` varchar(255) NOT NULL,
  `file_type` varchar(20) DEFAULT NULL,
  `version` int(11) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `is_active` tinyint(1) DEFAULT 1,
  `academic_year` varchar(20) DEFAULT NULL,
  `semester` varchar(20) DEFAULT NULL,
  `download_count` int(11) DEFAULT 0,
  `visibility` enum('private','shared') DEFAULT 'private',
  `shared_with` enum('none','all_instructors','all_students','selected_instructors') DEFAULT 'none'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `resources`
--

INSERT INTO `resources` (`id`, `subject_id`, `uploader_id`, `title`, `description`, `file_path`, `file_type`, `version`, `created_at`, `updated_at`, `is_active`, `academic_year`, `semester`, `download_count`, `visibility`, `shared_with`) VALUES
(1, 23, 2, 'Title Defense Notes', 'Notes for title defense', 'uploads/title-defense-notes.txt', NULL, 1, '2025-06-24 19:38:50', '2025-06-24 19:38:50', 1, NULL, NULL, 0, 'private', 'none'),
(2, 23, 2, 'Discussion for chosen title', 'Discussion for chosen title', 'uploads/Discussions-for-chosen-titles.txt', NULL, 1, '2025-06-24 20:53:37', '2025-06-24 20:53:37', 1, NULL, NULL, 0, 'private', 'none'),
(3, 23, 2, 'Capstone Knowhub Prototype', 'Prototype of capstone Project knowhub', 'uploads/Doc1.pdf', NULL, 1, '2025-08-17 11:57:36', '2025-08-17 11:57:36', 1, NULL, NULL, 0, 'private', 'none'),
(4, 23, 2, 'Capstone Knowhub Prototype in Dox', 'Capstone Knowhub Prototype in Dox', 'uploads/Doc1.docx', NULL, 1, '2025-08-17 11:58:18', '2025-08-17 11:58:18', 1, NULL, NULL, 0, 'private', 'none');

-- --------------------------------------------------------

--
-- Table structure for table `resource_comments`
--

CREATE TABLE `resource_comments` (
  `id` int(11) NOT NULL,
  `resource_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `comment` text NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `resource_shares`
--

CREATE TABLE `resource_shares` (
  `id` int(11) NOT NULL,
  `resource_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `resource_tags`
--

CREATE TABLE `resource_tags` (
  `id` int(11) NOT NULL,
  `resource_id` int(11) NOT NULL,
  `tag` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `resource_versions`
--

CREATE TABLE `resource_versions` (
  `id` int(11) NOT NULL,
  `resource_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `file_path` varchar(500) NOT NULL,
  `uploader_id` int(11) NOT NULL,
  `uploaded_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `rooms`
--

CREATE TABLE `rooms` (
  `id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `room_members`
--

CREATE TABLE `room_members` (
  `id` int(11) NOT NULL,
  `room_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `joined_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sessions`
--

CREATE TABLE `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `payload` longtext NOT NULL,
  `last_activity` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sessions`
--

INSERT INTO `sessions` (`id`, `user_id`, `ip_address`, `user_agent`, `payload`, `last_activity`) VALUES
('ILl9uO7qwFAEwelzJP1ypao5LBzKr494d2ltT6L5', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 Edg/139.0.0.0', 'YTo1OntzOjY6Il90b2tlbiI7czo0MDoiNVJtOVY0cUZNYzdOdTV4dFZSekNKODlyWkVIb09SUEg4VXVqcnZNSiI7czozOiJ1cmwiO2E6MTp7czo4OiJpbnRlbmRlZCI7czozOToiaHR0cDovL2xvY2FsaG9zdDo4MDAwL3N0dWRlbnQvZGFzaGJvYXJkIjt9czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MzU5OiJodHRwOi8vbG9jYWxob3N0OjgwMDAvbG9naW4vZ29vZ2xlL2NhbGxiYWNrP2F1dGh1c2VyPTEmY29kZT00JTJGMEFWTUJzSmlMTkJuZHZqUm1ZLXotcVpPeUFJT0xKTnRSSTY1ZUVfZ18xMFc1cm5icXR2V2hiVUFzSXU0cnlwTXJwak9PYVEmaGQ9cHN1LmVkdS5waCZwcm9tcHQ9bm9uZSZzY29wZT1lbWFpbCUyMHByb2ZpbGUlMjBvcGVuaWQlMjBodHRwcyUzQSUyRiUyRnd3dy5nb29nbGVhcGlzLmNvbSUyRmF1dGglMkZ1c2VyaW5mby5lbWFpbCUyMGh0dHBzJTNBJTJGJTJGd3d3Lmdvb2dsZWFwaXMuY29tJTJGYXV0aCUyRnVzZXJpbmZvLnByb2ZpbGUmc3RhdGU9UG9HSzA5ak1QN1pCcTRHbTVzdWtwRWhlaEpyenExaXc3SXlwWGtMdCI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fXM6NToic3RhdGUiO3M6NDA6IlBvR0swOWpNUDdaQnE0R201c3VrcEVoZWhKcnpxMWl3N0l5cFhrTHQiO30=', 1756263465),
('mafwqCV5g02Z7HlAgW4G9pl9hsRVcOaEN5C14eo8', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 Edg/139.0.0.0', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiZGhydEFCSzF6YmF1SllVQkhtMGlRclZxVk5ieTJzbDVhZ1VBUkpKaSI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MjE6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMCI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=', 1756221580),
('txRMromPvJ0a1wzlzPhZXznfd7Z7FOo12CQDbHg4', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 Edg/139.0.0.0', 'YTo0OntzOjY6Il90b2tlbiI7czo0MDoiOEdRNHlZNmdqcWJrTDB1RjBHYTkxclNtTzFEMVJtMU8yYnN2Tk9leSI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MzQ6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMC9sb2dpbi9nb29nbGUiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX1zOjU6InN0YXRlIjtzOjQwOiI4UlcweXpiSmUzZGZzaGtUMVMzdWhKQkNLMFlwVFM1QkpvQXBFWTZXIjt9', 1756265735),
('W6YHDYJOE8QguE6qRKMiZS4DA9vLx8dxvKcTrpcL', 2, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 Edg/139.0.0.0', 'YTo0OntzOjY6Il90b2tlbiI7czo0MDoiYVlqNFdicWZ1UGFGYVdqRzdDYnFOU2lZaHJGMWM0aUkxQ1pSaURadCI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6Mzk6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMC9zdHVkZW50L2Rhc2hib2FyZCI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fXM6NTA6ImxvZ2luX3dlYl81OWJhMzZhZGRjMmIyZjk0MDE1ODBmMDE0YzdmNThlYTRlMzA5ODlkIjtpOjI7fQ==', 1756219277),
('y8IdtWOEfN2C6bara5IFwV30WSMa0qPD9ztdhq8H', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 Edg/139.0.0.0', 'YTo1OntzOjY6Il90b2tlbiI7czo0MDoiUnV4elBhQ3dXYXdXczM5VFd2OVNaM2xTN0VMdGx6SkxHMDBid0VHZCI7czozOiJ1cmwiO2E6MTp7czo4OiJpbnRlbmRlZCI7czozOToiaHR0cDovL2xvY2FsaG9zdDo4MDAwL3N0dWRlbnQvZGFzaGJvYXJkIjt9czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6Mjc6Imh0dHA6Ly9sb2NhbGhvc3Q6ODAwMC9sb2dpbiI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fXM6NToic3RhdGUiO3M6NDA6IkZ5cWJMU1pZSG8xTFpqS2lOZ2ViV1FYSWhmbnNUc0dnMkpLUU9ZMjUiO30=', 1756266451);

-- --------------------------------------------------------

--
-- Stand-in structure for view `student_submissions`
-- (See below for the actual view)
--
CREATE TABLE `student_submissions` (
`submission_id` int(11)
,`activity_id` int(11)
,`quiz_id` int(11)
,`exam_id` int(11)
,`student_id` int(11)
,`submission_file` varchar(500)
,`submitted_at` timestamp
,`grade` decimal(5,2)
,`feedback` text
,`student_name` varchar(255)
,`student_number` varchar(50)
,`student_email` varchar(255)
,`assignment_title` varchar(255)
,`submission_type` varchar(8)
);

-- --------------------------------------------------------

--
-- Table structure for table `subjects`
--

CREATE TABLE `subjects` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `year_level` int(11) NOT NULL,
  `semester` varchar(10) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `subjects`
--

INSERT INTO `subjects` (`id`, `name`, `year_level`, `semester`, `description`, `created_at`) VALUES
(1, 'Introduction to Computing', 1, '1st', 'Basic concepts of computing and information technology.', '2025-06-24 19:13:01'),
(2, 'Fundamentals of Programming', 1, '1st', 'Introduction to programming using C language.', '2025-06-24 19:13:01'),
(3, 'Intermediate Programming', 1, '2nd', 'Advanced programming concepts and data structures.', '2025-06-24 19:13:01'),
(4, 'Computer Organization', 1, '2nd', 'Study of computer hardware and architecture.', '2025-06-24 19:13:01'),
(5, 'Discrete Mathematics', 1, '2nd', 'Mathematical foundations for computer science.', '2025-06-24 19:13:01'),
(6, 'Data Structures and Algorithm', 2, '1st', 'Design and analysis of data structures and algorithms.', '2025-06-24 19:13:01'),
(7, 'Living in the IT Era', 2, '1st', 'Impact of IT in society and daily life.', '2025-06-24 19:13:01'),
(8, 'Human Computer Interaction 1', 2, '1st', 'Principles of human-computer interaction.', '2025-06-24 19:13:01'),
(9, 'Object-Oriented Programming', 2, '1st', 'Object-oriented concepts using Java.', '2025-06-24 19:13:01'),
(10, 'Information Management 1', 2, '2nd', 'Introduction to databases and SQL.', '2025-06-24 19:13:01'),
(11, 'Human Computer Interaction 2', 2, '2nd', 'Advanced topics in HCI.', '2025-06-24 19:13:01'),
(12, 'Multimedia Technologies', 2, '2nd', 'Fundamentals of multimedia systems.', '2025-06-24 19:13:01'),
(13, 'Networking 1', 2, '2nd', 'Basics of computer networking.', '2025-06-24 19:13:01'),
(14, 'System Analysis and Design', 2, '2nd', 'Systems development life cycle and methodologies.', '2025-06-24 19:13:01'),
(15, 'Web Development 1', 2, '2nd', 'Web technologies and development.', '2025-06-24 19:13:01'),
(16, 'Application Development and Emerging Technologies', 3, '1st', 'Modern app development and new tech.', '2025-06-24 19:13:01'),
(17, 'Information Management 2', 3, '1st', 'Advanced database topics.', '2025-06-24 19:13:01'),
(18, 'Mobile Application Development 1', 3, '1st', 'Mobile app development fundamentals.', '2025-06-24 19:13:01'),
(19, 'Quantitative Methods', 3, '1st', 'Statistics and quantitative analysis.', '2025-06-24 19:13:01'),
(20, 'Networking 2', 3, '1st', 'Advanced networking concepts.', '2025-06-24 19:13:01'),
(21, 'Operating Systems', 3, '1st', 'Operating system principles and design.', '2025-06-24 19:13:01'),
(22, 'Web System and Technologies 1', 3, '1st', 'Full stack web development.', '2025-06-24 19:13:01'),
(23, 'Capstone Project 1', 3, '2nd', 'Initial phase of capstone project.', '2025-06-24 19:13:01'),
(24, 'Elective 1', 3, '2nd', 'Specialized elective subject.', '2025-06-24 19:13:01'),
(25, 'Elective 2', 3, '2nd', 'Specialized elective subject.', '2025-06-24 19:13:01'),
(26, 'Information Assurance and Security 1', 3, '2nd', 'Cybersecurity fundamentals.', '2025-06-24 19:13:01'),
(27, 'Integrated Programming and Technologies', 3, '2nd', 'Integration of various programming technologies.', '2025-06-24 19:13:01'),
(28, 'Technopreneurship', 3, '2nd', 'Entrepreneurship in technology.', '2025-06-24 19:13:01'),
(29, 'Capstone Project 2', 4, '1st', 'Final phase of capstone project.', '2025-06-24 19:13:01'),
(30, 'Elective 3', 4, '1st', 'Specialized elective subject.', '2025-06-24 19:13:01'),
(31, 'Elective 4', 4, '1st', 'Specialized elective subject.', '2025-06-24 19:13:01'),
(32, 'Information Assurance and Security 2', 4, '1st', 'Advanced cybersecurity topics.', '2025-06-24 19:13:01'),
(33, 'System Administration and Maintenance', 4, '1st', 'System admin and maintenance practices.', '2025-06-24 19:13:01'),
(34, 'System Integration and Architecture', 4, '1st', 'System integration and architecture design.', '2025-06-24 19:13:01');

-- --------------------------------------------------------

--
-- Table structure for table `subject_instructors`
--

CREATE TABLE `subject_instructors` (
  `id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL,
  `instructor_id` int(11) NOT NULL,
  `assigned_at` datetime DEFAULT current_timestamp(),
  `block` enum('A','B','AB') NOT NULL DEFAULT 'A'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `subject_instructors`
--

INSERT INTO `subject_instructors` (`id`, `subject_id`, `instructor_id`, `assigned_at`, `block`) VALUES
(1, 2, 3, '2025-07-02 08:42:35', 'A'),
(3, 4, 3, '2025-07-02 08:42:48', 'B'),
(15, 29, 3, '2025-09-18 14:05:50', 'A'),
(16, 29, 3, '2025-09-18 14:06:13', 'B'),
(17, 34, 3, '2025-09-18 14:11:07', 'A'),
(18, 34, 3, '2025-09-18 14:11:07', 'B');

-- --------------------------------------------------------

--
-- Table structure for table `subject_students`
--

CREATE TABLE `subject_students` (
  `id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `enrolled_at` datetime DEFAULT current_timestamp(),
  `block` varchar(10) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `submissions`
--

CREATE TABLE `submissions` (
  `id` int(11) NOT NULL,
  `activity_id` int(11) DEFAULT NULL,
  `quiz_id` int(11) DEFAULT NULL,
  `exam_id` int(11) DEFAULT NULL,
  `student_id` int(11) NOT NULL,
  `file_path` varchar(500) DEFAULT NULL,
  `submitted_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `grade` decimal(5,2) DEFAULT NULL,
  `feedback` text DEFAULT NULL,
  `status` enum('on-time','late') DEFAULT 'on-time'
) ;

--
-- Dumping data for table `submissions`
--

INSERT INTO `submissions` (`id`, `activity_id`, `quiz_id`, `exam_id`, `student_id`, `file_path`, `submitted_at`, `grade`, `feedback`, `status`) VALUES
(1, 3, NULL, NULL, 2, 'E:\\CAP101-DANG FILES\\archive-system/uploads/submissions/3_2_1758181068_sia.docx', '2025-09-18 07:37:49', NULL, NULL, 'on-time');

-- --------------------------------------------------------

--
-- Table structure for table `tags`
--

CREATE TABLE `tags` (
  `id` int(11) NOT NULL,
  `tag_name` varchar(50) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tasks`
--

CREATE TABLE `tasks` (
  `id` int(11) NOT NULL,
  `task` varchar(255) NOT NULL,
  `due` date NOT NULL,
  `assigned_to` int(11) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tasks`
--

INSERT INTO `tasks` (`id`, `task`, `due`, `assigned_to`, `created_at`) VALUES
(1, 'Manage Instructor', '2025-09-24', 4, '2025-09-23 21:11:30');

-- --------------------------------------------------------

--
-- Table structure for table `threads`
--

CREATE TABLE `threads` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `forum_id` varchar(50) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `threads`
--

INSERT INTO `threads` (`id`, `user_id`, `forum_id`, `title`, `description`, `created_by`, `created_at`) VALUES
(4, 2, 'doit', 'Re: helloooo', 'helloooo hohohoh olols', 0, '2025-08-25 02:52:46'),
(5, 2, 'doit', 'Re: hellooo\r\n', 'hellooo\r\n', 0, '2025-08-25 02:56:08'),
(14, 3, 'instructors', 'Re: shfefinekmofdjew', 'shfefinekmofdFF', 0, '2025-08-30 09:15:08');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `google_id` varchar(255) DEFAULT NULL,
  `email` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `picture` varchar(255) DEFAULT NULL,
  `role` enum('student','instructor','admin') NOT NULL DEFAULT 'student',
  `year_level` varchar(50) DEFAULT NULL,
  `student_number` varchar(50) DEFAULT NULL,
  `block` varchar(10) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `google_id`, `email`, `name`, `picture`, `role`, `year_level`, `student_number`, `block`) VALUES
(1, NULL, 'melodycantilloneypes@gmail.com', 'Melody Cantillo Neypes', 'https://lh3.googleusercontent.com/a/ACg8ocJS_3YVys27UnnBMbyp-Go1VwlFlCDTDXi43xxTyJ55ZVbVpEs=s96-c', 'instructor', NULL, NULL, NULL),
(2, '112230390921844601455', '22ac0396_ms@psu.edu.ph', 'Melody Neypes', 'https://lh3.googleusercontent.com/a/ACg8ocLYOkM1iQXankz_cVmdoZFRHzpvrtyW8Ts5j9YMmGcKjIQ9gcU=s96-c', 'student', '4', '22-ac-0396', NULL),
(3, NULL, 'it.faculty.ac@gmail.com', 'Instructor', 'https://lh3.googleusercontent.com/a/ACg8ocJJJa4rjYDRFDanWmjlbv9AZBWD5OJFDGteYNzzNNJoyO1_hg=s96-c', 'instructor', NULL, NULL, NULL),
(4, NULL, 'alaminos.admn@gmail.com', 'admin', 'https://lh3.googleusercontent.com/a/ACg8ocJvY2WDDJasmdtjgWx6uKT5vrIGbXHhDEY7Zn8a7A6_qCS_rw=s96-c', 'admin', NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `user_logs`
--

CREATE TABLE `user_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `role` varchar(50) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `action` varchar(255) NOT NULL,
  `details` text DEFAULT NULL,
  `timestamp` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_logs`
--

INSERT INTO `user_logs` (`id`, `user_id`, `role`, `email`, `action`, `details`, `timestamp`) VALUES
(1, 4, NULL, NULL, 'logout', 'User logged out', '2025-09-15 11:26:50'),
(2, 4, 'admin', 'alaminos.admn@gmail.com', 'logout', 'User logged out', '2025-09-18 13:49:33'),
(3, 2, 'student', '22ac0396_ms@psu.edu.ph', 'logout', 'User logged out', '2025-09-18 13:49:41'),
(4, 4, 'admin', 'alaminos.admn@gmail.com', 'logout', 'User logged out', '2025-09-18 13:59:12'),
(5, 4, 'admin', 'alaminos.admn@gmail.com', 'logout', 'User logged out', '2025-09-18 14:04:35'),
(6, 3, 'instructor', 'it.faculty.ac@gmail.com', 'login', 'User logged in', '2025-09-18 14:04:41'),
(7, 3, 'instructor', 'it.faculty.ac@gmail.com', 'logout', 'User logged out', '2025-09-18 14:05:12'),
(8, 4, 'admin', 'alaminos.admn@gmail.com', 'login', 'User logged in', '2025-09-18 14:05:19'),
(9, 4, 'admin', 'alaminos.admn@gmail.com', 'logout', 'User logged out', '2025-09-18 14:06:25'),
(10, 3, 'instructor', 'it.faculty.ac@gmail.com', 'login', 'User logged in', '2025-09-18 14:06:36'),
(11, 3, 'instructor', 'it.faculty.ac@gmail.com', 'logout', 'User logged out', '2025-09-18 14:10:12'),
(12, 4, 'admin', 'alaminos.admn@gmail.com', 'login', 'User logged in', '2025-09-18 14:10:18'),
(13, 4, 'admin', 'alaminos.admn@gmail.com', 'logout', 'User logged out', '2025-09-18 14:11:24'),
(14, 3, 'instructor', 'it.faculty.ac@gmail.com', 'login', 'User logged in', '2025-09-18 14:11:36'),
(15, 3, 'instructor', 'it.faculty.ac@gmail.com', 'logout', 'User logged out', '2025-09-18 14:14:12'),
(16, 2, 'student', '22ac0396_ms@psu.edu.ph', 'login', 'User logged in', '2025-09-18 14:14:20'),
(17, 2, 'student', '22ac0396_ms@psu.edu.ph', 'login', 'User logged in', '2025-09-18 14:29:50'),
(18, 2, 'student', '22ac0396_ms@psu.edu.ph', 'login', 'User logged in', '2025-09-18 22:08:36'),
(19, 2, 'student', '22ac0396_ms@psu.edu.ph', 'logout', 'User logged out', '2025-09-18 22:47:14'),
(20, 3, 'instructor', 'it.faculty.ac@gmail.com', 'login', 'User logged in', '2025-09-18 22:47:43'),
(21, 2, 'student', '22ac0396_ms@psu.edu.ph', 'login', 'User logged in', '2025-09-21 19:38:08'),
(22, 4, 'admin', 'alaminos.admn@gmail.com', 'login', 'User logged in', '2025-09-23 20:55:03'),
(23, 4, 'admin', 'alaminos.admn@gmail.com', 'logout', 'User logged out', '2025-09-23 21:12:00'),
(24, 2, 'student', '22ac0396_ms@psu.edu.ph', 'login', 'User logged in', '2025-09-23 21:14:26'),
(25, 2, 'student', '22ac0396_ms@psu.edu.ph', 'logout', 'User logged out', '2025-09-23 21:20:28'),
(26, 4, 'admin', 'alaminos.admn@gmail.com', 'login', 'User logged in', '2025-09-23 21:22:02'),
(27, 4, 'admin', 'alaminos.admn@gmail.com', 'logout', 'User logged out', '2025-09-23 21:24:09'),
(28, 2, 'student', '22ac0396_ms@psu.edu.ph', 'login', 'User logged in', '2025-09-25 16:13:13'),
(29, 2, 'student', '22ac0396_ms@psu.edu.ph', 'logout', 'User logged out', '2025-09-25 16:17:15'),
(30, 4, 'admin', 'alaminos.admn@gmail.com', 'login', 'User logged in', '2025-09-25 16:17:49'),
(31, 4, 'admin', 'alaminos.admn@gmail.com', 'logout', 'User logged out', '2025-09-25 16:39:12'),
(32, 4, 'admin', 'alaminos.admn@gmail.com', 'login', 'User logged in', '2025-09-25 16:39:20'),
(33, 4, 'admin', 'alaminos.admn@gmail.com', 'logout', 'User logged out', '2025-09-25 16:41:15'),
(34, 4, 'admin', 'alaminos.admn@gmail.com', 'login', 'User logged in', '2025-09-25 16:41:21'),
(35, 4, 'admin', 'alaminos.admn@gmail.com', 'logout', 'User logged out', '2025-09-25 16:43:32'),
(36, 4, 'admin', 'alaminos.admn@gmail.com', 'login', 'User logged in', '2025-09-25 16:43:38'),
(37, 4, 'admin', 'alaminos.admn@gmail.com', 'logout', 'User logged out', '2025-09-25 19:10:24'),
(38, 3, 'instructor', 'it.faculty.ac@gmail.com', 'login', 'User logged in', '2025-09-25 19:11:09'),
(39, 3, 'instructor', 'it.faculty.ac@gmail.com', 'logout', 'User logged out', '2025-09-25 19:44:48'),
(40, 3, 'instructor', 'it.faculty.ac@gmail.com', 'login', 'User logged in', '2025-09-25 19:44:55'),
(41, 3, 'instructor', 'it.faculty.ac@gmail.com', 'login', 'User logged in', '2025-09-26 17:55:01'),
(42, 3, 'instructor', 'it.faculty.ac@gmail.com', 'logout', 'User logged out', '2025-09-26 18:11:13'),
(43, 2, 'student', '22ac0396_ms@psu.edu.ph', 'login', 'User logged in', '2025-09-26 18:12:17'),
(44, 2, 'student', '22ac0396_ms@psu.edu.ph', 'logout', 'User logged out', '2025-09-26 18:23:34'),
(45, 2, 'student', '22ac0396_ms@psu.edu.ph', 'login', 'User logged in', '2025-09-26 18:23:41'),
(46, 2, 'student', '22ac0396_ms@psu.edu.ph', 'logout', 'User logged out', '2025-09-26 18:40:20'),
(47, 3, 'instructor', 'it.faculty.ac@gmail.com', 'login', 'User logged in', '2025-09-26 18:40:29'),
(48, 3, 'instructor', 'it.faculty.ac@gmail.com', 'login', 'User logged in', '2025-09-26 19:23:41'),
(49, 3, 'instructor', 'it.faculty.ac@gmail.com', 'login', 'User logged in', '2025-09-27 12:00:38'),
(50, 3, 'instructor', 'it.faculty.ac@gmail.com', 'logout', 'User logged out', '2025-09-27 14:46:34'),
(51, 2, 'student', '22ac0396_ms@psu.edu.ph', 'login', 'User logged in', '2025-09-27 14:47:15');

-- --------------------------------------------------------

--
-- Structure for view `student_submissions`
--
DROP TABLE IF EXISTS `student_submissions`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `student_submissions`  AS SELECT `s`.`id` AS `submission_id`, `s`.`activity_id` AS `activity_id`, `s`.`quiz_id` AS `quiz_id`, `s`.`exam_id` AS `exam_id`, `s`.`student_id` AS `student_id`, `s`.`file_path` AS `submission_file`, `s`.`submitted_at` AS `submitted_at`, `s`.`grade` AS `grade`, `s`.`feedback` AS `feedback`, `u`.`name` AS `student_name`, `u`.`student_number` AS `student_number`, `u`.`email` AS `student_email`, coalesce(`a`.`title`,`q`.`title`,`e`.`title`) AS `assignment_title`, CASE WHEN `s`.`activity_id` is not null THEN 'Activity' WHEN `s`.`quiz_id` is not null THEN 'Quiz' WHEN `s`.`exam_id` is not null THEN 'Exam' END AS `submission_type` FROM ((((`submissions` `s` join `users` `u` on(`s`.`student_id` = `u`.`id`)) left join `activities` `a` on(`s`.`activity_id` = `a`.`id`)) left join `quizzes` `q` on(`s`.`quiz_id` = `q`.`id`)) left join `exams` `e` on(`s`.`exam_id` = `e`.`id`)) ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `access_requests`
--
ALTER TABLE `access_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `subject_id` (`subject_id`);

--
-- Indexes for table `activities`
--
ALTER TABLE `activities`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_instructor` (`instructor_id`);

--
-- Indexes for table `activity_reminders`
--
ALTER TABLE `activity_reminders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `activity_id` (`activity_id`),
  ADD KEY `instructor_id` (`instructor_id`);

--
-- Indexes for table `admin_documents`
--
ALTER TABLE `admin_documents`
  ADD PRIMARY KEY (`id`),
  ADD KEY `uploader_id` (`uploader_id`);

--
-- Indexes for table `alumni_requests`
--
ALTER TABLE `alumni_requests`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `downloads`
--
ALTER TABLE `downloads`
  ADD PRIMARY KEY (`id`),
  ADD KEY `resource_id` (`resource_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `exams`
--
ALTER TABLE `exams`
  ADD PRIMARY KEY (`id`),
  ADD KEY `subject_id` (`subject_id`);

--
-- Indexes for table `external_resources`
--
ALTER TABLE `external_resources`
  ADD PRIMARY KEY (`id`),
  ADD KEY `subject_id` (`subject_id`);

--
-- Indexes for table `file_manager`
--
ALTER TABLE `file_manager`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_parent_id` (`parent_id`),
  ADD KEY `idx_uploader_id` (`uploader_id`),
  ADD KEY `idx_item_type` (`item_type`);

--
-- Indexes for table `instructor_resources`
--
ALTER TABLE `instructor_resources`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `room_id` (`room_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `fk_sender` (`sender_id`);

--
-- Indexes for table `posts`
--
ALTER TABLE `posts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `thread_id` (`thread_id`);

--
-- Indexes for table `quizzes`
--
ALTER TABLE `quizzes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `subject_id` (`subject_id`);

--
-- Indexes for table `replies`
--
ALTER TABLE `replies`
  ADD PRIMARY KEY (`id`),
  ADD KEY `thread_id` (`thread_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `resources`
--
ALTER TABLE `resources`
  ADD PRIMARY KEY (`id`),
  ADD KEY `subject_id` (`subject_id`),
  ADD KEY `uploader_id` (`uploader_id`);

--
-- Indexes for table `resource_shares`
--
ALTER TABLE `resource_shares`
  ADD PRIMARY KEY (`id`),
  ADD KEY `resource_id` (`resource_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `resource_tags`
--
ALTER TABLE `resource_tags`
  ADD PRIMARY KEY (`id`),
  ADD KEY `resource_id` (`resource_id`);

--
-- Indexes for table `resource_versions`
--
ALTER TABLE `resource_versions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `resource_id` (`resource_id`),
  ADD KEY `uploader_id` (`uploader_id`);

--
-- Indexes for table `rooms`
--
ALTER TABLE `rooms`
  ADD PRIMARY KEY (`id`),
  ADD KEY `subject_id` (`subject_id`);

--
-- Indexes for table `room_members`
--
ALTER TABLE `room_members`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_room_user` (`room_id`,`user_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `sessions`
--
ALTER TABLE `sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sessions_user_id_index` (`user_id`),
  ADD KEY `sessions_last_activity_index` (`last_activity`);

--
-- Indexes for table `subjects`
--
ALTER TABLE `subjects`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `subject_instructors`
--
ALTER TABLE `subject_instructors`
  ADD PRIMARY KEY (`id`),
  ADD KEY `subject_id` (`subject_id`),
  ADD KEY `instructor_id` (`instructor_id`);

--
-- Indexes for table `subject_students`
--
ALTER TABLE `subject_students`
  ADD PRIMARY KEY (`id`),
  ADD KEY `subject_id` (`subject_id`),
  ADD KEY `student_id` (`student_id`);

--
-- Indexes for table `submissions`
--
ALTER TABLE `submissions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `activity_id` (`activity_id`),
  ADD KEY `quiz_id` (`quiz_id`),
  ADD KEY `exam_id` (`exam_id`),
  ADD KEY `student_id` (`student_id`);

--
-- Indexes for table `tags`
--
ALTER TABLE `tags`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `tag_name` (`tag_name`);

--
-- Indexes for table `tasks`
--
ALTER TABLE `tasks`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `threads`
--
ALTER TABLE `threads`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `google_id` (`google_id`);

--
-- Indexes for table `user_logs`
--
ALTER TABLE `user_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `access_requests`
--
ALTER TABLE `access_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `activities`
--
ALTER TABLE `activities`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `activity_reminders`
--
ALTER TABLE `activity_reminders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `admin_documents`
--
ALTER TABLE `admin_documents`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `alumni_requests`
--
ALTER TABLE `alumni_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `downloads`
--
ALTER TABLE `downloads`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `exams`
--
ALTER TABLE `exams`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `external_resources`
--
ALTER TABLE `external_resources`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `file_manager`
--
ALTER TABLE `file_manager`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `instructor_resources`
--
ALTER TABLE `instructor_resources`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT for table `messages`
--
ALTER TABLE `messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=55;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `posts`
--
ALTER TABLE `posts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `quizzes`
--
ALTER TABLE `quizzes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `replies`
--
ALTER TABLE `replies`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `resources`
--
ALTER TABLE `resources`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `resource_shares`
--
ALTER TABLE `resource_shares`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `resource_tags`
--
ALTER TABLE `resource_tags`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `resource_versions`
--
ALTER TABLE `resource_versions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `rooms`
--
ALTER TABLE `rooms`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `room_members`
--
ALTER TABLE `room_members`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `subjects`
--
ALTER TABLE `subjects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=69;

--
-- AUTO_INCREMENT for table `subject_instructors`
--
ALTER TABLE `subject_instructors`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `subject_students`
--
ALTER TABLE `subject_students`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `submissions`
--
ALTER TABLE `submissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tags`
--
ALTER TABLE `tags`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tasks`
--
ALTER TABLE `tasks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `threads`
--
ALTER TABLE `threads`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT for table `user_logs`
--
ALTER TABLE `user_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=52;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `access_requests`
--
ALTER TABLE `access_requests`
  ADD CONSTRAINT `access_requests_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `access_requests_ibfk_2` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `activities`
--
ALTER TABLE `activities`
  ADD CONSTRAINT `fk_instructor` FOREIGN KEY (`instructor_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `activity_reminders`
--
ALTER TABLE `activity_reminders`
  ADD CONSTRAINT `activity_reminders_ibfk_1` FOREIGN KEY (`activity_id`) REFERENCES `activities` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `activity_reminders_ibfk_2` FOREIGN KEY (`instructor_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `admin_documents`
--
ALTER TABLE `admin_documents`
  ADD CONSTRAINT `admin_documents_ibfk_1` FOREIGN KEY (`uploader_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `downloads`
--
ALTER TABLE `downloads`
  ADD CONSTRAINT `downloads_ibfk_1` FOREIGN KEY (`resource_id`) REFERENCES `resources` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `downloads_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `exams`
--
ALTER TABLE `exams`
  ADD CONSTRAINT `exams_ibfk_1` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `external_resources`
--
ALTER TABLE `external_resources`
  ADD CONSTRAINT `external_resources_ibfk_1` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`);

--
-- Constraints for table `file_manager`
--
ALTER TABLE `file_manager`
  ADD CONSTRAINT `file_manager_ibfk_1` FOREIGN KEY (`parent_id`) REFERENCES `file_manager` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `file_manager_ibfk_2` FOREIGN KEY (`uploader_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `instructor_resources`
--
ALTER TABLE `instructor_resources`
  ADD CONSTRAINT `instructor_resources_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `messages`
--
ALTER TABLE `messages`
  ADD CONSTRAINT `messages_ibfk_1` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `messages_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `fk_sender` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `posts`
--
ALTER TABLE `posts`
  ADD CONSTRAINT `posts_ibfk_1` FOREIGN KEY (`thread_id`) REFERENCES `threads` (`id`);

--
-- Constraints for table `quizzes`
--
ALTER TABLE `quizzes`
  ADD CONSTRAINT `quizzes_ibfk_1` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `replies`
--
ALTER TABLE `replies`
  ADD CONSTRAINT `replies_ibfk_1` FOREIGN KEY (`thread_id`) REFERENCES `threads` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `replies_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `resources`
--
ALTER TABLE `resources`
  ADD CONSTRAINT `resources_ibfk_1` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`),
  ADD CONSTRAINT `resources_ibfk_2` FOREIGN KEY (`uploader_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `resource_shares`
--
ALTER TABLE `resource_shares`
  ADD CONSTRAINT `resource_shares_ibfk_1` FOREIGN KEY (`resource_id`) REFERENCES `resources` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `resource_shares_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `resource_tags`
--
ALTER TABLE `resource_tags`
  ADD CONSTRAINT `resource_tags_ibfk_1` FOREIGN KEY (`resource_id`) REFERENCES `resources` (`id`);

--
-- Constraints for table `resource_versions`
--
ALTER TABLE `resource_versions`
  ADD CONSTRAINT `resource_versions_ibfk_1` FOREIGN KEY (`resource_id`) REFERENCES `resources` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `resource_versions_ibfk_2` FOREIGN KEY (`uploader_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `rooms`
--
ALTER TABLE `rooms`
  ADD CONSTRAINT `rooms_ibfk_1` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `room_members`
--
ALTER TABLE `room_members`
  ADD CONSTRAINT `room_members_ibfk_1` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `room_members_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `subject_instructors`
--
ALTER TABLE `subject_instructors`
  ADD CONSTRAINT `subject_instructors_ibfk_1` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`),
  ADD CONSTRAINT `subject_instructors_ibfk_2` FOREIGN KEY (`instructor_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `subject_students`
--
ALTER TABLE `subject_students`
  ADD CONSTRAINT `subject_students_ibfk_1` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `subject_students_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `submissions`
--
ALTER TABLE `submissions`
  ADD CONSTRAINT `submissions_ibfk_1` FOREIGN KEY (`activity_id`) REFERENCES `activities` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `submissions_ibfk_2` FOREIGN KEY (`quiz_id`) REFERENCES `quizzes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `submissions_ibfk_3` FOREIGN KEY (`exam_id`) REFERENCES `exams` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `submissions_ibfk_4` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_logs`
--
ALTER TABLE `user_logs`
  ADD CONSTRAINT `user_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
