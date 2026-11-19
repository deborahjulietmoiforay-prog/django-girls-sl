-- =============================================================================
--  Django Girls Sierra Leone - Community & Organisation Management Platform
--  DCOMP207 Web Programming Final Project - MySQL / MariaDB database
--
--  HOW TO IMPORT (XAMPP):
--    1. Start Apache and MySQL in the XAMPP Control Panel.
--    2. Open http://localhost/phpmyadmin  ->  Import  ->  choose this file  ->  Go.
--       (or command line:  C:\xampp\mysql\bin\mysql -u root < djangogirls_sl.sql)
--    3. The script creates the database `djangogirls_sl`, all tables, and
--       starter data. Re-importing resets the database to this starting state.
--
--  DEMO ACCOUNTS
--    Administrator : admin@djangogirls.sl    / Admin@12345
--    Mentor        : aminata@djangogirls.sl  / Mentor@12345
--    Member        : isatu@djangogirls.sl    / Member@12345
-- =============================================================================

SET NAMES utf8mb4;
SET time_zone = '+00:00';
SET FOREIGN_KEY_CHECKS = 0;

CREATE DATABASE IF NOT EXISTS djangogirls_sl
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE djangogirls_sl;

DROP VIEW  IF EXISTS v_member_engagement;
DROP TABLE IF EXISTS mentorships;
DROP TABLE IF EXISTS replies;
DROP TABLE IF EXISTS topics;
DROP TABLE IF EXISTS resource_progress;
DROP TABLE IF EXISTS resources;
DROP TABLE IF EXISTS event_registrations;
DROP TABLE IF EXISTS events;
DROP TABLE IF EXISTS users;

-- -----------------------------------------------------------------------------
-- 1. USERS  (members, mentors and administrators; extends Assignment 1 `users`)
-- -----------------------------------------------------------------------------
CREATE TABLE users (
  id               INT UNSIGNED NOT NULL AUTO_INCREMENT,
  full_name        VARCHAR(100) NOT NULL,
  email            VARCHAR(190) NOT NULL,
  password_hash    VARCHAR(255) NOT NULL,              -- bcrypt via password_hash()
  role             ENUM('member','admin') NOT NULL DEFAULT 'member',
  interests        VARCHAR(255) NOT NULL DEFAULT '',   -- comma-separated topics
  experience_level ENUM('beginner','intermediate','advanced') NOT NULL DEFAULT 'beginner',
  bio              TEXT NOT NULL DEFAULT '',
  avatar           VARCHAR(255) NULL,                  -- file name in uploads/avatars
  is_mentor        TINYINT(1) NOT NULL DEFAULT 0,
  active           TINYINT(1) NOT NULL DEFAULT 1,
  created_at       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  last_login       DATETIME NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_users_email (email),
  KEY idx_users_role (role),
  KEY idx_users_mentor (is_mentor, active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 2. EVENTS  (events, workshops and training sessions)
-- -----------------------------------------------------------------------------
CREATE TABLE events (
  id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  title       VARCHAR(150) NOT NULL,
  description TEXT NOT NULL,
  type        VARCHAR(30)  NOT NULL DEFAULT 'Event',   -- Event | Workshop | Training session
  topic       VARCHAR(50)  NOT NULL DEFAULT 'General',
  event_date  DATETIME     NOT NULL,
  location    VARCHAR(150) NOT NULL,
  capacity    INT UNSIGNED NOT NULL DEFAULT 30,
  PRIMARY KEY (id),
  KEY idx_events_date (event_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 3. EVENT_REGISTRATIONS  (who registered / attended which event)
-- -----------------------------------------------------------------------------
CREATE TABLE event_registrations (
  id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  event_id   INT UNSIGNED NOT NULL,
  user_id    INT UNSIGNED NOT NULL,
  status     ENUM('registered','attended','cancelled') NOT NULL DEFAULT 'registered',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_reg_event_user (event_id, user_id),
  KEY idx_reg_user (user_id),
  CONSTRAINT fk_reg_event FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
  CONSTRAINT fk_reg_user  FOREIGN KEY (user_id)  REFERENCES users(id)  ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 4. RESOURCES  (learning materials and tutorials)
-- -----------------------------------------------------------------------------
CREATE TABLE resources (
  id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  title       VARCHAR(150) NOT NULL,
  description TEXT NOT NULL,
  category    VARCHAR(50)  NOT NULL,
  level       ENUM('beginner','intermediate','advanced') NOT NULL DEFAULT 'beginner',
  url         VARCHAR(500) NOT NULL,
  PRIMARY KEY (id),
  KEY idx_resources_category (category)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 5. RESOURCE_PROGRESS  (training progress: which member completed what)
-- -----------------------------------------------------------------------------
CREATE TABLE resource_progress (
  user_id      INT UNSIGNED NOT NULL,
  resource_id  INT UNSIGNED NOT NULL,
  completed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (user_id, resource_id),
  KEY idx_progress_resource (resource_id),
  CONSTRAINT fk_progress_user     FOREIGN KEY (user_id)     REFERENCES users(id)     ON DELETE CASCADE,
  CONSTRAINT fk_progress_resource FOREIGN KEY (resource_id) REFERENCES resources(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 6. TOPICS and 7. REPLIES  (community discussion forum)
-- -----------------------------------------------------------------------------
CREATE TABLE topics (
  id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id    INT UNSIGNED NOT NULL,
  title      VARCHAR(150) NOT NULL,
  body       TEXT NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_topics_user (user_id),
  CONSTRAINT fk_topics_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE replies (
  id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  topic_id   INT UNSIGNED NOT NULL,
  user_id    INT UNSIGNED NOT NULL,
  body       TEXT NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_replies_topic (topic_id),
  KEY idx_replies_user (user_id),
  CONSTRAINT fk_replies_topic FOREIGN KEY (topic_id) REFERENCES topics(id) ON DELETE CASCADE,
  CONSTRAINT fk_replies_user  FOREIGN KEY (user_id)  REFERENCES users(id)  ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 8. MENTORSHIPS  (mentee -> mentor requests)
-- -----------------------------------------------------------------------------
CREATE TABLE mentorships (
  id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  mentee_id  INT UNSIGNED NOT NULL,
  mentor_id  INT UNSIGNED NOT NULL,
  message    VARCHAR(300) NOT NULL DEFAULT '',
  status     ENUM('pending','accepted','declined') NOT NULL DEFAULT 'pending',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_mentorship_pair (mentee_id, mentor_id),
  KEY idx_mentorship_mentor (mentor_id, status),
  CONSTRAINT fk_mentorship_mentee FOREIGN KEY (mentee_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_mentorship_mentor FOREIGN KEY (mentor_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- REPORTING VIEW: one row per active member with engagement totals
-- -----------------------------------------------------------------------------
CREATE VIEW v_member_engagement AS
SELECT u.id, u.full_name, u.email, u.role, u.is_mentor, u.created_at,
       (SELECT COUNT(*) FROM topics t  WHERE t.user_id = u.id)
     + (SELECT COUNT(*) FROM replies r WHERE r.user_id = u.id)                          AS forum_posts,
       (SELECT COUNT(*) FROM event_registrations er
         WHERE er.user_id = u.id AND er.status <> 'cancelled')                           AS events_joined,
       (SELECT COUNT(*) FROM event_registrations er
         WHERE er.user_id = u.id AND er.status = 'attended')                             AS events_attended,
       (SELECT COUNT(*) FROM resource_progress rp WHERE rp.user_id = u.id)             AS resources_completed
FROM users u
WHERE u.active = 1;

SET FOREIGN_KEY_CHECKS = 1;

-- =============================================================================
-- STARTER DATA
-- =============================================================================

INSERT INTO users (id, full_name, email, password_hash, role, interests, experience_level, bio, is_mentor, created_at) VALUES
(1, 'Django Girls Admin', 'admin@djangogirls.sl',
    '$2y$12$5LfLEDckFYUiIfnOaOTqqOrCDQC9j07D/vQkl1.azrA413zhQSDiO', 'admin',
    'Python,Django,HTML & CSS', 'advanced',
    'Lead organiser and community administrator for Django Girls Sierra Leone in Freetown.', 1, NOW() - INTERVAL 75 DAY),
(2, 'Aminata Kamara', 'aminata@djangogirls.sl',
    '$2y$12$X4ZF9AfHJTTdFZWkIeU6meh.Czj7JIoyyoF2HHxsFS/4Oi9FVcLE.', 'member',
    'Python,Django,Data science', 'advanced',
    'Backend developer working with Python and Django in Freetown. I mentor women who are starting out in software development and data analysis.', 1, NOW() - INTERVAL 70 DAY),
(3, 'Fatmata Sesay', 'fatmata@djangogirls.sl',
    '$2y$12$X4ZF9AfHJTTdFZWkIeU6meh.Czj7JIoyyoF2HHxsFS/4Oi9FVcLE.', 'member',
    'HTML & CSS,JavaScript,UI/UX design', 'advanced',
    'Front-end developer. I help beginners build clean, accessible web pages with HTML, CSS and JavaScript.', 1, NOW() - INTERVAL 64 DAY),
(4, 'Mariama Koroma', 'mariama@djangogirls.sl',
    '$2y$12$X4ZF9AfHJTTdFZWkIeU6meh.Czj7JIoyyoF2HHxsFS/4Oi9FVcLE.', 'member',
    'Python,Django,HTML & CSS', 'intermediate',
    'Full-stack developer and Django Girls workshop alumna. Happy to guide new members through their first project.', 1, NOW() - INTERVAL 40 DAY),
(5, 'Isatu Bangura', 'isatu@djangogirls.sl',
    '$2y$12$98.urZXx0NLPEkKaJ3UbvOAkMZgshWD.//hT/RRBi/UgUKgGtxxo2', 'member',
    'Python,HTML & CSS', 'beginner',
    'Computer science student learning Python and building my first website.', 0, NOW() - INTERVAL 21 DAY),
(6, 'Hawa Conteh', 'hawa@djangogirls.sl',
    '$2y$12$98.urZXx0NLPEkKaJ3UbvOAkMZgshWD.//hT/RRBi/UgUKgGtxxo2', 'member',
    'JavaScript,UI/UX design', 'intermediate',
    'Graphic designer moving into web design and front-end development.', 0, NOW() - INTERVAL 9 DAY);

-- Event dates are relative to the import date, so there is always one past
-- event (for attendance history) and several upcoming ones.
INSERT INTO events (id, title, description, type, topic, event_date, location, capacity) VALUES
(1, 'Web Programming Orientation Bootcamp',
    'Introduction to the community platform, setting up your laptop, and the basics of how websites work.',
    'Workshop', 'Python', TIMESTAMP(CURDATE() - INTERVAL 10 DAY, '10:00:00'), 'Freetown, Limkokwing Lab 1', 35),
(2, 'Intro to Python Workshop',
    'Write your first Python programs. No experience needed; laptops are available for members who need one.',
    'Workshop', 'Python', TIMESTAMP(CURDATE() + INTERVAL 6 DAY, '10:00:00'), 'Freetown, Limkokwing Lab 2', 30),
(3, 'Build Your First Django Site',
    'Follow the Django Girls tutorial step by step with a mentor at your table.',
    'Workshop', 'Django', TIMESTAMP(CURDATE() + INTERVAL 13 DAY, '09:30:00'), 'Freetown, Limkokwing Lab 1', 25),
(4, 'HTML & CSS Training Session',
    'Learn page structure, Flexbox and responsive layouts by building a personal profile page.',
    'Training session', 'HTML & CSS', TIMESTAMP(CURDATE() + INTERVAL 20 DAY, '14:00:00'), 'Online (Zoom link sent by email)', 40),
(5, 'Women in Tech Meetup and Career Talk',
    'Meet other members, share your projects and hear from women working in technology in Sierra Leone.',
    'Event', 'General', TIMESTAMP(CURDATE() + INTERVAL 27 DAY, '16:00:00'), 'Freetown, Community Hall', 60),
(6, 'JavaScript for Interactive Web Pages',
    'Add interactivity to your pages with JavaScript: events, the DOM and form validation.',
    'Workshop', 'JavaScript', TIMESTAMP(CURDATE() + INTERVAL 34 DAY, '11:00:00'), 'Freetown, Limkokwing Lab 3', 30);

INSERT INTO event_registrations (event_id, user_id, status, created_at) VALUES
(1, 2, 'attended',   NOW() - INTERVAL 15 DAY),
(1, 3, 'attended',   NOW() - INTERVAL 15 DAY),
(1, 4, 'attended',   NOW() - INTERVAL 14 DAY),
(1, 5, 'attended',   NOW() - INTERVAL 13 DAY),
(1, 6, 'registered', NOW() - INTERVAL 12 DAY),
(2, 5, 'registered', NOW() - INTERVAL 3 DAY),
(2, 6, 'registered', NOW() - INTERVAL 2 DAY),
(3, 5, 'registered', NOW() - INTERVAL 1 DAY),
(4, 6, 'registered', NOW() - INTERVAL 1 DAY);

INSERT INTO resources (id, title, description, category, level, url) VALUES
(1, 'Django Girls Tutorial', 'The official step-by-step tutorial: build and deploy a blog with Python and Django.', 'Django', 'beginner', 'https://tutorial.djangogirls.org/'),
(2, 'Python Official Tutorial', 'A guided tour of the Python language from the official documentation.', 'Python', 'beginner', 'https://docs.python.org/3/tutorial/'),
(3, 'Django Documentation: Writing Your First App', 'Build a polls application and learn how models, views and templates fit together.', 'Django', 'intermediate', 'https://docs.djangoproject.com/en/stable/intro/tutorial01/'),
(4, 'MDN Learn Web Development', 'Structured lessons covering HTML, CSS and JavaScript fundamentals.', 'HTML & CSS', 'beginner', 'https://developer.mozilla.org/en-US/docs/Learn_web_development'),
(5, 'The Modern JavaScript Tutorial', 'From the basics to advanced topics, with clear explanations and exercises.', 'JavaScript', 'intermediate', 'https://javascript.info/'),
(6, 'Kaggle Learn: Python and Pandas', 'Short practical courses in Python, pandas and data visualisation.', 'Data science', 'beginner', 'https://www.kaggle.com/learn'),
(7, '10 Usability Heuristics for User Interface Design', 'Ten rules of thumb for designing interfaces that people can use.', 'UI/UX design', 'beginner', 'https://www.nngroup.com/articles/ten-usability-heuristics/'),
(8, 'CSS Flexbox and Grid Guide', 'Visual guides to modern CSS layout with Flexbox and Grid.', 'HTML & CSS', 'intermediate', 'https://css-tricks.com/snippets/css/a-guide-to-flexbox/');

INSERT INTO resource_progress (user_id, resource_id, completed_at) VALUES
(4, 1, NOW() - INTERVAL 30 DAY), (4, 2, NOW() - INTERVAL 28 DAY), (4, 3, NOW() - INTERVAL 20 DAY), (4, 4, NOW() - INTERVAL 18 DAY),
(5, 2, NOW() - INTERVAL 12 DAY), (5, 4, NOW() - INTERVAL 6 DAY),
(6, 4, NOW() - INTERVAL 5 DAY), (6, 7, NOW() - INTERVAL 3 DAY), (6, 8, NOW() - INTERVAL 1 DAY);

INSERT INTO topics (id, user_id, title, body, created_at) VALUES
(1, 1, 'Welcome to the Django Girls Sierra Leone community',
    'Welcome to our community platform.\n\nYou can register for workshops on the Events page, follow the learning resources and track your progress, and request a mentor.\n\nPlease introduce yourself below: tell us where you are studying or working and what you would like to learn this term.',
    NOW() - INTERVAL 20 DAY),
(2, 3, 'Tips for making your project pages look clean and professional',
    'A few things that make a big difference:\n1. Use a small, consistent colour palette with calm colours.\n2. Give content enough spacing and keep text easy to read.\n3. Always pair form inputs with clear labels and helpful error messages.\n\nWhat are you working on this week?',
    NOW() - INTERVAL 8 DAY),
(3, 4, 'Preparing for the Intro to Python Workshop',
    'The next workshop is at Limkokwing Lab 2. Please register on the Events page so we know how many laptops to prepare. If you can, install Python 3 before the session.',
    NOW() - INTERVAL 2 DAY);

INSERT INTO replies (topic_id, user_id, body, created_at) VALUES
(1, 5, 'Hello everyone! I am Isatu, a first-year computer science student. I want to learn Python and build my own website.', NOW() - INTERVAL 19 DAY),
(1, 2, 'Welcome Isatu! The Python Official Tutorial on the Resources page is a great place to start. Feel free to send me a mentorship request.', NOW() - INTERVAL 18 DAY),
(2, 6, 'Thank you Fatmata, this is helpful. I am redesigning my portfolio page with a simpler colour scheme.', NOW() - INTERVAL 6 DAY),
(3, 5, 'Registered! I have installed Python 3.12 on my laptop.', NOW() - INTERVAL 1 DAY);

INSERT INTO mentorships (mentee_id, mentor_id, message, status, created_at) VALUES
(5, 2, 'I would like guidance on learning Python and preparing for the Django workshop.', 'accepted', NOW() - INTERVAL 17 DAY),
(6, 3, 'Could you review my portfolio page layout and give feedback?', 'pending', NOW() - INTERVAL 2 DAY);
