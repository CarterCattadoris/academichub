-- ============================================
-- COMPLETE SCHEMA WITH TRIGGER VALIDATION
-- ============================================

DROP TABLE IF EXISTS event_reminders;
DROP TABLE IF EXISTS event_participants;
DROP TABLE IF EXISTS calendar_events;
DROP TABLE IF EXISTS class_members;
DROP TABLE IF EXISTS classes;
DROP TABLE IF EXISTS users;

-- Create users table
CREATE TABLE users (
    user_id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    first_name VARCHAR(50),
    last_name VARCHAR(50),
    user_role ENUM('student', 'admin', 'moderator') DEFAULT 'student',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL
);

-- Create classes table
CREATE TABLE classes (
    class_id INT PRIMARY KEY AUTO_INCREMENT,
    class_name VARCHAR(100) NOT NULL,
    class_code VARCHAR(20) UNIQUE NOT NULL,
    semester VARCHAR(20),
    year INT,
    description TEXT,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(user_id) ON DELETE CASCADE
);

-- Create class_members table
CREATE TABLE class_members (
    member_id INT PRIMARY KEY AUTO_INCREMENT,
    class_id INT NOT NULL,
    user_id INT NOT NULL,
    role ENUM('student', 'instructor', 'ta') DEFAULT 'student',
    joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (class_id) REFERENCES classes(class_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    UNIQUE KEY unique_member (class_id, user_id)
);

-- Create calendar_events table
CREATE TABLE calendar_events (
    event_id INT PRIMARY KEY AUTO_INCREMENT,
    class_id INT NOT NULL,
    creator_user_id INT NOT NULL,
    event_title VARCHAR(100) NOT NULL,
    event_description TEXT,
    event_type ENUM('assignment', 'study_session', 'exam', 'project', 'other') NOT NULL,
    start_datetime DATETIME NOT NULL,
    end_datetime DATETIME NOT NULL,
    location VARCHAR(255),
    is_all_day BOOLEAN DEFAULT FALSE,
    recurrence_rule VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (class_id) REFERENCES classes(class_id) ON DELETE CASCADE,
    FOREIGN KEY (creator_user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- TRIGGER: Validate on INSERT
DELIMITER //
CREATE TRIGGER validate_event_dates_insert
BEFORE INSERT ON calendar_events
FOR EACH ROW
BEGIN
    IF NEW.end_datetime <= NEW.start_datetime THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'End datetime must be after start datetime';
    END IF;
END//
DELIMITER ;

-- TRIGGER: Validate on UPDATE
DELIMITER //
CREATE TRIGGER validate_event_dates_update
BEFORE UPDATE ON calendar_events
FOR EACH ROW
BEGIN
    IF NEW.end_datetime <= NEW.start_datetime THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'End datetime must be after start datetime';
    END IF;
END//
DELIMITER ;

-- Create event_participants
CREATE TABLE event_participants (
    participant_id INT PRIMARY KEY AUTO_INCREMENT,
    event_id INT NOT NULL,
    user_id INT NOT NULL,
    response_status ENUM('going', 'maybe', 'not_going', 'no_response') DEFAULT 'no_response',
    notified BOOLEAN DEFAULT FALSE,
    
    FOREIGN KEY (event_id) REFERENCES calendar_events(event_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    
    UNIQUE KEY unique_participant (event_id, user_id)
);

-- Create event_reminders
CREATE TABLE event_reminders (
    reminder_id INT PRIMARY KEY AUTO_INCREMENT,
    event_id INT NOT NULL,
    user_id INT NOT NULL,
    reminder_datetime DATETIME NOT NULL,
    reminder_type ENUM('email', 'push', 'in_app') DEFAULT 'in_app',
    is_sent BOOLEAN DEFAULT FALSE,
    
    FOREIGN KEY (event_id) REFERENCES calendar_events(event_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- Create indexes
CREATE INDEX idx_event_dates ON calendar_events(start_datetime, end_datetime);
CREATE INDEX idx_class_events ON calendar_events(class_id, start_datetime);
CREATE INDEX idx_creator_events ON calendar_events(creator_user_id);
CREATE INDEX idx_participant_events ON event_participants(user_id, event_id);
CREATE INDEX idx_pending_reminders ON event_reminders(reminder_datetime, is_sent);
CREATE INDEX idx_class_members ON class_members(user_id, class_id);

-- Insert test data (only if tables exist)
INSERT INTO users (username, email, password_hash, first_name, last_name, user_role)
VALUES 
    ('thomas', 'thomas@syr.edu', '$2y$10$abcdefghijklmnopqrstuv', 'Thomas', 'Smith', 'student'),
    ('carter', 'carter@syr.edu', '$2y$10$abcdefghijklmnopqrstuv', 'Carter', 'Johnson', 'student'),
    ('aaron', 'aaron@syr.edu', '$2y$10$abcdefghijklmnopqrstuv', 'Aaron', 'Williams', 'student'),
    ('angelo', 'angelo@syr.edu', '$2y$10$abcdefghijklmnopqrstuv', 'Angelo', 'Brown', 'student'),
    ('glenn', 'glenn@syr.edu', '$2y$10$abcdefghijklmnopqrstuv', 'Glenn', 'Davis', 'student');

INSERT INTO classes (class_name, class_code, semester, year, created_by)
VALUES 
    ('Web Programming', 'CSE389', 'Fall', 2025, 1),
    ('Database Systems', 'CSE381', 'Fall', 2025, 1),
    ('Software Engineering', 'CSE440', 'Fall', 2025, 1);

INSERT INTO class_members (class_id, user_id, role)
VALUES 
    (1, 1, 'student'), (1, 2, 'student'), (1, 3, 'student'),
    (1, 4, 'student'), (1, 5, 'student'), (2, 1, 'student'), (2, 2, 'student');

-- Insert events (mostly future, with 1-2 past events)
-- Current time: 12/14/2025 at 3:30 PM
INSERT INTO calendar_events 
    (class_id, creator_user_id, event_title, event_description, event_type, start_datetime, end_datetime, location)
VALUES
    -- Past event 1 (already happened)
    (1, 1, 'Midterm Exam', 'Chapters 1-5', 'exam', 
     '2025-12-05 10:00:00', '2025-12-05 12:00:00', 'Room 215'),
    
    -- Past event 2 (already happened)
    (1, 1, 'Group Study Session', 'Review for midterm', 'study_session', 
     '2025-12-03 18:00:00', '2025-12-03 20:00:00', 'Library'),
    
    -- Future event 1 (tomorrow)
    (1, 2, 'Project Proposal Due', 'Submit on Blackboard', 'assignment', 
     '2025-12-15 23:59:00', '2025-12-15 23:59:59', 'Online'),
    
    -- Future event 2 (next week)
    (1, 1, 'Guest Lecture', 'Industry professional', 'other', 
     '2025-12-18 14:00:00', '2025-12-18 15:30:00', 'Auditorium'),
    
    -- Future event 3 (next week)
    (1, 1, 'Final Exam Review', 'Prepare for finals', 'study_session', 
     '2025-12-20 10:00:00', '2025-12-20 12:00:00', 'Library'),
    
    -- Future event 4 (end of month)
    (1, 2, 'Final Project Presentation', 'Group presentations', 'project', 
     '2025-12-22 09:00:00', '2025-12-22 11:30:00', 'Room 101'),
    
    -- Future event 5 (January)
    (2, 1, 'Spring Semester Kickoff', 'Welcome back meeting', 'other', 
     '2026-01-15 13:00:00', '2026-01-15 14:00:00', 'Hall of Languages');

INSERT INTO event_participants (event_id, user_id, response_status)
VALUES (1, 1, 'going'), (1, 2, 'going'), (2, 1, 'going'), (3, 2, 'going');

INSERT INTO event_reminders (event_id, user_id, reminder_datetime, reminder_type)
VALUES (3, 1, '2025-12-15 18:00:00', 'email'), (4, 1, '2025-12-17 12:00:00', 'in_app');
