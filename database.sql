-- ============================================================
--  Student Management System — MySQL Schema
--  شغّل الملف ده في phpMyAdmin أو MySQL Workbench
-- ============================================================

CREATE DATABASE IF NOT EXISTS student_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE student_db;

-- ============================================================
--  TABLES
-- ============================================================

CREATE TABLE IF NOT EXISTS Students (
    student_id  INT AUTO_INCREMENT PRIMARY KEY,
    f_name      VARCHAR(100) NOT NULL,
    l_name      VARCHAR(100) NOT NULL,
    department  VARCHAR(150),
    phone       VARCHAR(20),
    year        INT,
    status      VARCHAR(20) DEFAULT 'Active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS Courses (
    course_id    INT AUTO_INCREMENT PRIMARY KEY,
    course_code  VARCHAR(20),
    course_name  VARCHAR(200) NOT NULL,
    credit_hours INT DEFAULT 3
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS Users (
    user_id    INT AUTO_INCREMENT PRIMARY KEY,
    username   VARCHAR(100) NOT NULL UNIQUE,
    password   VARCHAR(255),
    email      VARCHAR(150),
    role       VARCHAR(50) DEFAULT 'Student',
    is_active  TINYINT(1) DEFAULT 1,
    student_id INT UNIQUE,
    FOREIGN KEY (student_id) REFERENCES Students(student_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS Enrollments (
    enrollment_id INT AUTO_INCREMENT PRIMARY KEY,
    student_id    INT NOT NULL,
    course_id     INT NOT NULL,
    enroll_date   DATE,
    semester      VARCHAR(20),
    academic_year VARCHAR(10),
    FOREIGN KEY (student_id) REFERENCES Students(student_id) ON DELETE CASCADE,
    FOREIGN KEY (course_id)  REFERENCES Courses(course_id)  ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS Grades (
    grade_id      INT AUTO_INCREMENT PRIMARY KEY,
    enrollment_id INT NOT NULL,
    exam_type     VARCHAR(50),
    marks         FLOAT,
    grade_letter  VARCHAR(5),
    FOREIGN KEY (enrollment_id) REFERENCES Enrollments(enrollment_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS Attendance (
    attendance_id INT AUTO_INCREMENT PRIMARY KEY,
    enrollment_id INT NOT NULL,
    att_date      DATE,
    status        VARCHAR(20),
    notes         TEXT,
    FOREIGN KEY (enrollment_id) REFERENCES Enrollments(enrollment_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
--  SAMPLE DATA
-- ============================================================

INSERT INTO Students (f_name, l_name, department, phone, year, status) VALUES
('Ahmed',  'Ali',     'Computer Science',      '01000000001', 2026, 'Active'),
('Sara',   'Hassan',  'Information Technology','01000000002', 2026, 'Active'),
('Omar',   'Khaled',  'Information Systems',   '01000000003', 2027, 'Active'),
('Mona',   'Said',    'Computer Science',      '01000000004', 2025, 'Inactive'),
('Layla',  'Youssef', 'Software Engineering',  '01000000005', 2026, 'Active'),
('Hassan', 'Mohamed', 'Cyber Security',        '01000000006', 2025, 'Active'),
('Nour',   'Ibrahim', 'Cyber Security',        '01000000007', 2025, 'Active'),
('Yara',   'Mostafa', 'Software Engineering',  '01000000008', 2026, 'Active'),
('Karim',  'Fathy',   'Computer Science',      '01000000009', 2027, 'Inactive');

INSERT INTO Courses (course_code, course_name, credit_hours) VALUES
('CS101', 'Introduction to Computer Science', 3),
('CS102', 'Data Structures',                  3),
('CS103', 'Algorithms',                       4),
('CS104', 'Database Systems',                 3),
('CS105', 'Computer Networks',                3),
('CS106', 'Operating Systems',                4);

INSERT INTO Users (username, password, email, role, is_active, student_id) VALUES
('ahmedali',     'pass1', 'ahmed@email.com',  'Student', 1, 1),
('sarahassan',   'pass2', 'sara@email.com',   'Student', 1, 2),
('omarkhaled',   'pass3', 'omar@email.com',   'Student', 1, 3),
('monasaid',     'pass4', 'mona@email.com',   'Student', 0, 4),
('laylayoussef', 'pass5', 'layla@email.com',  'Student', 1, 5),
('hassanmohamed','pass6', 'hassan@email.com', 'Student', 1, 6);

INSERT INTO Enrollments (student_id, course_id, enroll_date, semester, academic_year) VALUES
(1, 1, '2026-09-01', 'Fall', '2026'),
(1, 2, '2026-09-01', 'Fall', '2026'),
(2, 1, '2026-09-01', 'Fall', '2026'),
(3, 3, '2026-09-02', 'Fall', '2026'),
(5, 5, '2026-09-01', 'Fall', '2026'),
(6, 6, '2026-09-02', 'Fall', '2026'),
(7, 1, '2026-09-01', 'Fall', '2026'),
(8, 2, '2026-09-01', 'Fall', '2026');

INSERT INTO Grades (enrollment_id, exam_type, marks, grade_letter) VALUES
(1, 'Final',   18.5, 'A'),
(2, 'Midterm', 14.0, 'B+'),
(3, 'Final',   20.0, 'A+'),
(4, 'Midterm', 11.0, 'C+'),
(5, 'Final',   17.0, 'B+'),
(6, 'Midterm', 15.0, 'A-');

INSERT INTO Attendance (enrollment_id, att_date, status, notes) VALUES
(1, '2026-09-01', 'Present', NULL),
(2, '2026-09-01', 'Late',    'Traffic'),
(3, '2026-09-01', 'Present', NULL),
(4, '2026-09-02', 'Absent',  'Sick'),
(5, '2026-09-01', 'Present', NULL),
(6, '2026-09-02', 'Late',    'Bus delay');
