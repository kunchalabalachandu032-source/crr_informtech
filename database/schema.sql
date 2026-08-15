-- ========================================================================
-- SIR C.R. REDDY COLLEGE OF ENGINEERING - DEPARTMENT OF IT
-- DATABASE SCHEMA: crrinformtech
-- ========================================================================

CREATE DATABASE IF NOT EXISTS `crrinformtech`;
USE `crrinformtech`;

-- 1. CR ACCOUNTS TABLE
CREATE TABLE IF NOT EXISTS `cr_accounts` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `roll_number` VARCHAR(50) NOT NULL UNIQUE,
  `year` VARCHAR(10) NOT NULL,
  `section` VARCHAR(10) NOT NULL,
  `email` VARCHAR(100) NOT NULL UNIQUE,
  `phone` VARCHAR(20) NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. SUBJECTS TABLE
CREATE TABLE IF NOT EXISTS `subjects` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `year` VARCHAR(10) NOT NULL,
  `section` VARCHAR(10) NOT NULL,
  `subject_name` VARCHAR(150) NOT NULL,
  `subject_type` ENUM('Theory', 'Lab') NOT NULL DEFAULT 'Theory',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. CLASS WORKS TABLE
CREATE TABLE IF NOT EXISTS `class_works` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `year` VARCHAR(10) NOT NULL,
  `section` VARCHAR(10) NOT NULL,
  `subject_name` VARCHAR(150) NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `description` TEXT,
  `file_path` VARCHAR(255) DEFAULT NULL,
  `uploaded_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. ASSIGNMENTS TABLE
CREATE TABLE IF NOT EXISTS `assignments` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `year` VARCHAR(10) NOT NULL,
  `section` VARCHAR(10) NOT NULL,
  `subject_name` VARCHAR(150) NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `description` TEXT,
  `due_date` DATE DEFAULT NULL,
  `file_path` VARCHAR(255) DEFAULT NULL,
  `uploaded_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. MID MARKS TABLE
CREATE TABLE IF NOT EXISTS `mid_marks` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `year` VARCHAR(10) NOT NULL,
  `section` VARCHAR(10) NOT NULL,
  `subject_name` VARCHAR(150) NOT NULL,
  `roll_number` VARCHAR(50) NOT NULL,
  `student_name` VARCHAR(100) NOT NULL,
  `mid1_marks` FLOAT DEFAULT 0,
  `mid2_marks` FLOAT DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 6. IMPORTANT QUESTIONS TABLE
CREATE TABLE IF NOT EXISTS `important_questions` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `year` VARCHAR(10) NOT NULL,
  `section` VARCHAR(10) NOT NULL,
  `subject_name` VARCHAR(150) NOT NULL,
  `unit_no` VARCHAR(50) NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `questions_text` TEXT,
  `file_path` VARCHAR(255) DEFAULT NULL,
  `uploaded_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 7. LAB OBSERVATIONS TABLE
CREATE TABLE IF NOT EXISTS `lab_observations` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `year` VARCHAR(10) NOT NULL,
  `section` VARCHAR(10) NOT NULL,
  `subject_name` VARCHAR(150) NOT NULL,
  `experiment_no` INT NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `description` TEXT,
  `file_path` VARCHAR(255) DEFAULT NULL,
  `uploaded_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 8. LAB RECORD PDFS TABLE
CREATE TABLE IF NOT EXISTS `lab_records` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `year` VARCHAR(10) NOT NULL,
  `section` VARCHAR(10) NOT NULL,
  `subject_name` VARCHAR(150) NOT NULL,
  `experiment_no` INT NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `file_path` VARCHAR(255) NOT NULL,
  `uploaded_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 9. FACULTY TABLE
CREATE TABLE IF NOT EXISTS `faculty` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `designation` VARCHAR(100) NOT NULL,
  `qualification` VARCHAR(100) DEFAULT NULL,
  `email` VARCHAR(100) NOT NULL,
  `phone` VARCHAR(20) DEFAULT NULL,
  `photo_path` VARCHAR(255) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 10. ANNOUNCEMENTS TABLE
CREATE TABLE IF NOT EXISTS `announcements` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `title` VARCHAR(255) NOT NULL,
  `content` TEXT NOT NULL,
  `target_audience` VARCHAR(50) DEFAULT 'All',
  `posted_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ========================================================================
-- INITIAL SEED DATA FOR TESTING
-- ========================================================================

-- Seed CR Accounts
INSERT INTO `cr_accounts` (`name`, `roll_number`, `year`, `section`, `email`, `phone`, `password`) VALUES
('K. Rajesh', '21B91A1201', '2', 'IT2A', 'rajesh.it2a@crr.ac.in', '9876543210', 'cr123'),
('P. Anitha', '21B91A1265', '2', 'IT2B', 'anitha.it2b@crr.ac.in', '9876543211', 'cr123'),
('M. Suresh', '20B91A1205', '3', 'IT3A', 'suresh.it3a@crr.ac.in', '9876543212', 'cr123')
ON DUPLICATE KEY UPDATE `name`=`name`;

-- Seed Subjects
INSERT INTO `subjects` (`year`, `section`, `subject_name`, `subject_type`) VALUES
('2', 'IT2A', 'Artificial Intelligence', 'Theory'),
('2', 'IT2A', 'AT & CD', 'Theory'),
('2', 'IT2A', 'Computer Networks', 'Theory'),
('2', 'IT2A', 'Advanced Java', 'Theory'),
('2', 'IT2A', 'Entrepreneurship', 'Theory'),
('2', 'IT2A', 'CN LAB', 'Lab'),
('2', 'IT2A', 'UIDF LAB', 'Lab'),
('2', 'IT2A', 'FSD LAB', 'Lab'),
('2', 'IT2A', 'ADJ LAB', 'Lab'),
('3', 'IT3A', 'Machine Learning', 'Theory'),
('3', 'IT3A', 'Web Technologies', 'Lab')
ON DUPLICATE KEY UPDATE `subject_name`=`subject_name`;

-- Seed Announcements
INSERT INTO `announcements` (`title`, `content`, `target_audience`) VALUES
('Mid-1 Examinations Schedule', 'Mid-1 examinations for 2nd and 3rd year students will commence from next Monday.', 'All'),
('Technical Symposium Registration', 'Annual IT Department Symposium registration is now open. Contact CRs for details.', 'All');

-- Seed Faculty
INSERT INTO `faculty` (`name`, `designation`, `qualification`, `email`, `phone`) VALUES
('Dr. S. Krishna Rao', 'HOD & Professor', 'Ph.D in CSE', 'hod.it@crr.ac.in', '9440123456'),
('Mr. V. Suresh Kumar', 'Assistant Professor', 'M.Tech', 'sureshkumar@crr.ac.in', '9440654321');
