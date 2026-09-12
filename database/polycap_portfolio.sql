-- =============================================================
-- Polycap Portfolio — Database Schema + Seed Data
-- Database: polycap_portfolio
-- Engine: InnoDB | Charset: utf8mb4 | Collation: utf8mb4_unicode_ci
-- Import via phpMyAdmin OR: mysql -u root < polycap_portfolio.sql
-- =============================================================

SET NAMES utf8mb4;
SET time_zone = '+00:00';
SET FOREIGN_KEY_CHECKS = 0;

CREATE DATABASE IF NOT EXISTS `polycap_portfolio`
  DEFAULT CHARACTER SET utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

USE `polycap_portfolio`;

-- -------------------------------------------------------------
-- Drop existing tables (safe re-import)
-- -------------------------------------------------------------
DROP TABLE IF EXISTS `messages`;
DROP TABLE IF EXISTS `social_links`;
DROP TABLE IF EXISTS `services`;
DROP TABLE IF EXISTS `experience`;
DROP TABLE IF EXISTS `projects`;
DROP TABLE IF EXISTS `skills`;
DROP TABLE IF EXISTS `education`;
DROP TABLE IF EXISTS `profile`;
DROP TABLE IF EXISTS `users`;

-- =============================================================
-- TABLE: users  (admin authentication)
-- =============================================================
CREATE TABLE `users` (
  `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `username`      VARCHAR(50)  NOT NULL,
  `email`         VARCHAR(120) NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `full_name`     VARCHAR(120) DEFAULT NULL,
  `role`          ENUM('admin','editor') NOT NULL DEFAULT 'admin',
  `last_login`    DATETIME     DEFAULT NULL,
  `created_at`    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_users_username` (`username`),
  UNIQUE KEY `uq_users_email`    (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================
-- TABLE: profile  (single row, id=1)
-- =============================================================
CREATE TABLE `profile` (
  `id`                 INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `full_name`          VARCHAR(120) NOT NULL,
  `professional_title` VARCHAR(200) DEFAULT NULL,
  `tagline`            VARCHAR(255) DEFAULT NULL,
  `short_intro`        TEXT         DEFAULT NULL,
  `bio`                TEXT         DEFAULT NULL,
  `location`           VARCHAR(120) DEFAULT NULL,
  `nationality`        VARCHAR(80)  DEFAULT NULL,
  `languages`          VARCHAR(200) DEFAULT NULL,
  `profession`         VARCHAR(120) DEFAULT NULL,
  `career_interests`   TEXT         DEFAULT NULL,
  `goals`              TEXT         DEFAULT NULL,
  `hobbies`            TEXT         DEFAULT NULL,
  `profile_image`      VARCHAR(255) DEFAULT NULL,
  `cv_file`            VARCHAR(255) DEFAULT NULL,
  `email`              VARCHAR(120) DEFAULT NULL,
  `phone`              VARCHAR(30)  DEFAULT NULL,
  `whatsapp`           VARCHAR(30)  DEFAULT NULL,
  `created_at`         TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`         TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================
-- TABLE: social_links
-- =============================================================
CREATE TABLE `social_links` (
  `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `platform`      VARCHAR(50)  NOT NULL,
  `url`           VARCHAR(255) NOT NULL,
  `icon_class`    VARCHAR(80)  DEFAULT NULL,
  `display_order` INT          NOT NULL DEFAULT 0,
  `is_active`     TINYINT(1)   NOT NULL DEFAULT 1,
  `created_at`    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_social_platform` (`platform`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================
-- TABLE: education
-- =============================================================
CREATE TABLE `education` (
  `id`               INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `institution`      VARCHAR(180) NOT NULL,
  `course`           VARCHAR(180) DEFAULT NULL,
  `level`            VARCHAR(80)  DEFAULT NULL,
  `start_year`       YEAR         DEFAULT NULL,
  `end_year`         YEAR         DEFAULT NULL,
  `description`      TEXT         DEFAULT NULL,
  `certificate_url`  VARCHAR(255) DEFAULT NULL,
  `institution_icon` VARCHAR(80)  NOT NULL DEFAULT 'fa-university',
  `display_order`    INT          NOT NULL DEFAULT 0,
  `created_at`       TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`       TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_edu_years` (`start_year`, `end_year`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================
-- TABLE: skills
-- =============================================================
CREATE TABLE `skills` (
  `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `category`      ENUM('Programming','Database','Web Development','Tools','Technology') NOT NULL,
  `name`          VARCHAR(80)  NOT NULL,
  `level`         TINYINT UNSIGNED NOT NULL DEFAULT 50,
  `icon_class`    VARCHAR(80)  DEFAULT NULL,
  `display_order` INT          NOT NULL DEFAULT 0,
  `created_at`    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_skills_category` (`category`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================
-- TABLE: projects
-- =============================================================
CREATE TABLE `projects` (
  `id`                INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name`              VARCHAR(180) NOT NULL,
  `short_description` VARCHAR(300) DEFAULT NULL,
  `full_description`  TEXT         DEFAULT NULL,
  `image`             VARCHAR(255) DEFAULT NULL,
  `technologies`      VARCHAR(255) DEFAULT NULL,
  `category`          ENUM('Web Development','PHP','JavaScript','Database','AI','Other') NOT NULL DEFAULT 'Web Development',
  `github_url`        VARCHAR(255) DEFAULT NULL,
  `live_url`          VARCHAR(255) DEFAULT NULL,
  `documentation_url` VARCHAR(255) DEFAULT NULL,
  `year`              YEAR         DEFAULT NULL,
  `is_featured`       TINYINT(1)   NOT NULL DEFAULT 0,
  `is_published`      TINYINT(1)   NOT NULL DEFAULT 1,
  `display_order`     INT          NOT NULL DEFAULT 0,
  `created_at`        TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`        TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_projects_category` (`category`),
  KEY `idx_projects_featured` (`is_featured`),
  KEY `idx_projects_year`     (`year`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================
-- TABLE: experience
-- =============================================================
CREATE TABLE `experience` (
  `id`               INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `position`         VARCHAR(150) NOT NULL,
  `organization`     VARCHAR(180) NOT NULL,
  `start_date`       DATE         DEFAULT NULL,
  `end_date`         DATE         DEFAULT NULL,
  `description`      TEXT         DEFAULT NULL,
  `responsibilities` TEXT         DEFAULT NULL,
  `achievements`     TEXT         DEFAULT NULL,
  `display_order`    INT          NOT NULL DEFAULT 0,
  `created_at`       TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`       TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_exp_dates` (`start_date`, `end_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================
-- TABLE: services
-- =============================================================
CREATE TABLE `services` (
  `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `title`         VARCHAR(120) NOT NULL,
  `description`   TEXT         DEFAULT NULL,
  `icon_class`    VARCHAR(80)  NOT NULL DEFAULT 'fa-cogs',
  `display_order` INT          NOT NULL DEFAULT 0,
  `is_active`     TINYINT(1)   NOT NULL DEFAULT 1,
  `created_at`    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================
-- TABLE: messages  (contact form submissions)
-- =============================================================
CREATE TABLE `messages` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name`       VARCHAR(120) NOT NULL,
  `email`      VARCHAR(120) NOT NULL,
  `subject`    VARCHAR(180) NOT NULL,
  `message`    TEXT         NOT NULL,
  `status`     ENUM('unread','read','replied','archived') NOT NULL DEFAULT 'unread',
  `ip_address` VARCHAR(45)  DEFAULT NULL,
  `created_at` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_messages_status`  (`status`),
  KEY `idx_messages_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- =============================================================
-- SEED DATA — clearly marked as SAMPLE for you to replace
-- =============================================================

-- ---------- Admin user ----------
-- Password below is the bcrypt hash of: Admin@123
-- CHANGE IMMEDIATELY after first login.
INSERT INTO `users` (`username`, `email`, `password_hash`, `full_name`, `role`) VALUES
('admin', 'you@example.com', '$2y$10$e0NRzB7KKzI8pZ1T9cVJ4eYh5aV0q7WqZ5f8zVf9n8bN2H4aJ6bOe', 'Polycap Nyamongo Maturwe', 'admin');

-- ---------- Profile (single row) ----------
INSERT INTO `profile`
(`full_name`, `professional_title`, `tagline`, `short_intro`, `bio`,
 `location`, `nationality`, `languages`, `profession`,
 `career_interests`, `goals`, `hobbies`,
 `profile_image`, `cv_file`, `email`, `phone`, `whatsapp`)
VALUES
(
  'Polycap Nyamongo Maturwe',
  'Mathematics & Computer Studies Educator | Web Developer | Technology Enthusiast',
  'Building clean, functional web experiences while teaching the next generation of problem-solvers.',
  'I am a Mathematics and Computer Studies professional with a growing passion for web development, databases, and applied computing. I enjoy turning ideas into working software and explaining technology in ways that make sense.',
  'I am Polycap Nyamongo Maturwe, a Mathematics & Computer Studies educator and aspiring software/web developer based in Nairobi, Kenya. My background combines strong analytical training in mathematics with hands-on computing: programming, database design, and modern web development. I am especially interested in the intersection of education, software, and artificial intelligence — building tools that help people learn and work better. I learn continuously, value clean code, and enjoy collaborating with other developers and educators.',
  'Nairobi, Kenya',
  'Kenyan',
  'English, Kiswahili',
  'Educator & Web Developer',
  'Web development, backend engineering with PHP/MySQL, databases, artificial intelligence, and educational technology.',
  'To grow into a full-stack software developer, contribute to meaningful open-source and educational projects, and eventually build technology products that serve African classrooms and businesses.',
  'Reading, problem-solving, exploring new tech, mentoring students, and football.',
  'assets/images/profile/profile.jpg',
  NULL,
  'you@example.com',
  '+254 7XX XXX XXX',
  '+254 7XX XXX XXX'
);

-- ---------- Social links ----------
INSERT INTO `social_links` (`platform`, `url`, `icon_class`, `display_order`) VALUES
('github',   'https://github.com/yourusername',                'fab fa-github',    1),
('linkedin', 'https://linkedin.com/in/yourusername',           'fab fa-linkedin',  2),
('facebook', 'https://facebook.com/yourusername',              'fab fa-facebook',  3),
('twitter',  'https://x.com/yourusername',                     'fab fa-x-twitter', 4),
('whatsapp', 'https://wa.me/2547XXXXXXXX',                     'fab fa-whatsapp',  5),
('email',    'mailto:you@example.com',                         'fas fa-envelope',  6);

-- ---------- Education ----------
INSERT INTO `education`
(`institution`, `course`, `level`, `start_year`, `end_year`, `description`, `certificate_url`, `institution_icon`, `display_order`)
VALUES
('University of Nairobi', 'Bachelor of Science in Mathematics & Computer Studies', 'Bachelor''s Degree', 2021, 2025,
 'Coursework in calculus, linear algebra, programming (C, C++, Python), databases, web development, and computer systems. Final-year project focused on a web-based solution.',
 NULL, 'fa-university', 1),
('Kenya Institute of Management', 'Certificate in Information Technology', 'Certificate', 2019, 2020,
 'Foundational IT training covering computer applications, networking basics, and computer troubleshooting.',
 NULL, 'fa-certificate', 2),
('Alliance High School', 'Kenya Certificate of Secondary Education (KCSE)', 'Secondary', 2015, 2018,
 'Strong performance in Mathematics, Computer Studies, and Sciences.',
 NULL, 'fa-school', 3);

-- ---------- Skills ----------
INSERT INTO `skills` (`category`, `name`, `level`, `icon_class`, `display_order`) VALUES
-- Programming
('Programming', 'HTML',       80, 'fab fa-html5',    1),
('Programming', 'CSS',        75, 'fab fa-css3-alt', 2),
('Programming', 'JavaScript', 60, 'fab fa-js',       3),
('Programming', 'PHP',        65, 'fab fa-php',      4),
('Programming', 'C',          55, 'fas fa-code',     5),
('Programming', 'C++',        50, 'fas fa-code',     6),
('Programming', 'Python',     55, 'fab fa-python',   7),
-- Database
('Database', 'MySQL',   65, 'fas fa-database', 1),
('Database', 'MariaDB', 60, 'fas fa-database', 2),
('Database', 'SQL',     65, 'fas fa-table',    3),
-- Web Development
('Web Development', 'Frontend Development',  70, 'fas fa-laptop-code', 1),
('Web Development', 'Backend Development',   60, 'fas fa-server',      2),
('Web Development', 'REST APIs',             50, 'fas fa-plug',        3),
('Web Development', 'Responsive Web Design', 75, 'fas fa-mobile-alt',  4),
-- Tools
('Tools', 'Git',      60, 'fab fa-git-alt',   1),
('Tools', 'GitHub',   65, 'fab fa-github',    2),
('Tools', 'XAMPP',    70, 'fas fa-server',    3),
('Tools', 'VS Code',  75, 'fas fa-code',      4),
('Tools', 'Termux',   55, 'fas fa-terminal',  5),
('Tools', 'Canva',    60, 'fas fa-palette',   6),
-- Technology
('Technology', 'Artificial Intelligence',      50, 'fas fa-brain',          1),
('Technology', 'Cybersecurity Fundamentals',   45, 'fas fa-shield-alt',     2),
('Technology', 'Networking',                   50, 'fas fa-network-wired',  3),
('Technology', 'Computer Troubleshooting',     70, 'fas fa-tools',          4);

-- ---------- Projects ----------
INSERT INTO `projects`
(`name`, `short_description`, `full_description`, `image`, `technologies`, `category`,
 `github_url`, `live_url`, `documentation_url`, `year`, `is_featured`, `is_published`, `display_order`)
VALUES
(
 'School Management System',
 'A web-based system to manage students, teachers, classes, and results.',
 'A full-featured school management platform built with PHP and MySQL. Handles student registration, teacher records, class assignment, exam results, and report card generation. Includes an admin dashboard with CRUD operations and role-based access.',
 'assets/images/projects/school-management.jpg',
 'PHP, MySQL, JavaScript, CSS',
 'PHP',
 'https://github.com/yourusername/school-management-system',
 'https://example.com/school-demo',
 NULL, 2025, 1, 1, 1
),
(
 'Personal Portfolio Website',
 'A modern, responsive portfolio built with PHP, MySQL, and vanilla JS.',
 'This very portfolio: a dynamic PHP/MySQL website with an admin panel, dark/light mode, project filtering, modals, and a contact form that stores messages in the database.',
 'assets/images/projects/portfolio.jpg',
 'PHP, MySQL, JavaScript, CSS',
 'Web Development',
 'https://github.com/yourusername/polycap-portfolio',
 'https://example.com',
 NULL, 2026, 1, 1, 2
),
(
 'E-Commerce Website',
 'A simple online store with product listing, cart, and checkout.',
 'An e-commerce demo with product catalog, shopping cart (session-based), user registration, and a simulated checkout flow. Built to practise full-stack PHP/MySQL development and secure form handling.',
 'assets/images/projects/ecommerce.jpg',
 'PHP, MySQL, JavaScript, Bootstrap',
 'PHP',
 'https://github.com/yourusername/ecommerce-demo',
 NULL, NULL, 2024, 0, 1, 3
),
(
 'Student Results Portal',
 'Students can log in to view their exam results securely.',
 'A results portal where students authenticate and view their marks per subject. Teachers upload results via an admin dashboard. Demonstrates authentication, sessions, and role-based access.',
 'assets/images/projects/results-portal.jpg',
 'PHP, MySQL, CSS',
 'Database',
 'https://github.com/yourusername/results-portal',
 NULL, NULL, 2024, 0, 1, 4
),
(
 'AI Chatbot (Rule-Based)',
 'A simple rule-based chatbot demonstrating AI concepts in JavaScript.',
 'A browser-based chatbot that matches user input against keyword rules and responds appropriately. Serves as an introduction to natural-language interaction and AI concepts.',
 'assets/images/projects/chatbot.jpg',
 'JavaScript, HTML, CSS',
 'AI',
 'https://github.com/yourusername/rule-based-chatbot',
 'https://example.com/chatbot',
 NULL, 2025, 0, 1, 5
),
(
 'REST API — Student Records',
 'A small REST API for managing student records.',
 'A PHP-based REST API exposing CRUD endpoints for student records, returning JSON. Includes basic token authentication and clear endpoint documentation.',
 'assets/images/projects/rest-api.jpg',
 'PHP, MySQL, JSON',
 'Web Development',
 'https://github.com/yourusername/student-rest-api',
 NULL, 'https://example.com/api-docs',
 2025, 0, 1, 6
),
(
 'Quiz Application',
 'An interactive quiz app with scoring and timer.',
 'A JavaScript quiz application with multiple-choice questions, a countdown timer, instant feedback, and a final score screen. Fully client-side with question bank stored in JSON.',
 'assets/images/projects/quiz.jpg',
 'JavaScript, HTML, CSS',
 'JavaScript',
 'https://github.com/yourusername/quiz-app',
 'https://example.com/quiz',
 NULL, 2024, 0, 1, 7
);

-- ---------- Experience ----------
INSERT INTO `experience`
(`position`, `organization`, `start_date`, `end_date`, `description`, `responsibilities`, `achievements`, `display_order`)
VALUES
(
 'Mathematics & Computer Studies Teacher',
 'Sample Secondary School, Nairobi',
 '2023-01-01', NULL,
 'Teaching Mathematics and Computer Studies to secondary school students, with a focus on practical computing skills.',
 'Preparing and delivering lessons in Mathematics and Computer Studies.\nGuiding students in practical computer sessions.\nSetting and marking assessments.\nMentoring students in programming projects.',
 'Introduced project-based learning in Computer Studies.\nImproved student pass rates in Mathematics.\nOrganised the school''s first coding club.',
 1
),
(
 'Freelance Web Developer (Part-time)',
 'Self-employed',
 '2024-06-01', NULL,
 'Building small business and personal websites using PHP, MySQL, HTML, CSS, and JavaScript.',
 'Gathering client requirements and translating them into working websites.\nDesigning responsive frontends.\nBuilding PHP/MySQL backends.\nDeploying and maintaining client sites.',
 'Delivered several small business websites.\nBuilt this portfolio as a reference project.\nImproved client turnaround times through reusable components.',
 2
);

-- ---------- Services ----------
INSERT INTO `services` (`title`, `description`, `icon_class`, `display_order`) VALUES
('Website Development',           'Modern, responsive websites built with HTML, CSS, JavaScript, PHP, and MySQL.', 'fas fa-globe',          1),
('Portfolio Website Development', 'Personal and professional portfolio sites for students, freelancers, and professionals.', 'fas fa-id-card',        2),
('School Management Systems',     'Custom web systems for managing students, teachers, classes, and results.',               'fas fa-school',         3),
('Database Design',               'Well-structured MySQL/MariaDB databases with clean schemas and relationships.',           'fas fa-database',       4),
('PHP / MySQL Development',       'Backend development with PHP 8+ and MySQL — including authentication and admin panels.',  'fas fa-server',         5),
('Frontend Development',          'Responsive, accessible frontends with modern CSS and vanilla JavaScript.',                'fas fa-laptop-code',    6),
('Computer Support',              'Basic hardware and software troubleshooting for individuals and small organisations.',    'fas fa-tools',          7),
('Basic IT Support',              'Installation, configuration, and assistance with common software and systems.',           'fas fa-headset',        8),
('Website Maintenance',           'Ongoing updates, backups, security patches, and small changes to existing websites.',     'fas fa-sync-alt',       9),
('Digital / Technology Consultancy', 'Advice on tools, platforms, and technology choices for individuals and small businesses.', 'fas fa-lightbulb',   10);

-- =============================================================
-- END OF SCHEMA
-- =============================================================
