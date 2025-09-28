-- School Result & Attendance Management System Database Schema

CREATE DATABASE IF NOT EXISTS school_management;
USE school_management;

-- Users table for authentication
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'teacher', 'student') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Classes table
CREATE TABLE classes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    class_name VARCHAR(20) NOT NULL,
    section VARCHAR(10) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Subjects table
CREATE TABLE subjects (
    id INT PRIMARY KEY AUTO_INCREMENT,
    subject_name VARCHAR(100) NOT NULL,
    class_id INT,
    total_marks INT DEFAULT 100,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE
);

-- Students table
CREATE TABLE students (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    name VARCHAR(100) NOT NULL,
    roll_no VARCHAR(20) UNIQUE NOT NULL,
    photo VARCHAR(255),
    class_id INT,
    parent_name VARCHAR(100),
    parent_contact VARCHAR(15),
    parent_email VARCHAR(100),
    address TEXT,
    date_of_birth DATE,
    gender ENUM('Male', 'Female', 'Other'),
    admission_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE SET NULL
);

-- Teachers table
CREATE TABLE teachers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100),
    phone VARCHAR(15),
    subject_specialization VARCHAR(100),
    qualification VARCHAR(200),
    experience_years INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Teacher-Class assignments
CREATE TABLE teacher_classes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    teacher_id INT,
    class_id INT,
    subject_id INT,
    is_class_teacher BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (teacher_id) REFERENCES teachers(id) ON DELETE CASCADE,
    FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE,
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE
);

-- Exams table
CREATE TABLE exams (
    id INT PRIMARY KEY AUTO_INCREMENT,
    exam_name VARCHAR(100) NOT NULL,
    exam_type ENUM('Midterm', 'Final', 'Monthly', 'Weekly', 'Unit Test') NOT NULL,
    class_id INT,
    exam_date DATE,
    academic_year VARCHAR(10),
    status ENUM('Scheduled', 'Ongoing', 'Completed') DEFAULT 'Scheduled',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE
);

-- Results table
CREATE TABLE results (
    id INT PRIMARY KEY AUTO_INCREMENT,
    student_id INT,
    subject_id INT,
    exam_id INT,
    marks_obtained DECIMAL(5,2),
    total_marks DECIMAL(5,2) DEFAULT 100,
    remarks TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE,
    FOREIGN KEY (exam_id) REFERENCES exams(id) ON DELETE CASCADE,
    UNIQUE KEY unique_result (student_id, subject_id, exam_id)
);

-- Grades table
CREATE TABLE grades (
    id INT PRIMARY KEY AUTO_INCREMENT,
    grade_name VARCHAR(5) NOT NULL,
    min_percentage DECIMAL(5,2) NOT NULL,
    max_percentage DECIMAL(5,2) NOT NULL,
    grade_point DECIMAL(3,2),
    description VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Attendance table
CREATE TABLE attendance (
    id INT PRIMARY KEY AUTO_INCREMENT,
    student_id INT,
    date DATE NOT NULL,
    status ENUM('Present', 'Absent', 'Late', 'Excused') NOT NULL,
    remarks TEXT,
    marked_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (marked_by) REFERENCES users(id) ON DELETE SET NULL,
    UNIQUE KEY unique_attendance (student_id, date)
);

-- School settings table
CREATE TABLE school_settings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    school_name VARCHAR(200) NOT NULL,
    logo VARCHAR(255),
    motto TEXT,
    address TEXT,
    phone VARCHAR(15),
    email VARCHAR(100),
    website VARCHAR(100),
    principal_name VARCHAR(100),
    principal_signature VARCHAR(255),
    academic_year VARCHAR(10),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Result verification table (for QR codes)
CREATE TABLE result_verifications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    verification_code VARCHAR(50) UNIQUE NOT NULL,
    student_id INT,
    exam_id INT,
    generated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP,
    is_active BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (exam_id) REFERENCES exams(id) ON DELETE CASCADE
);

-- Insert default admin user
INSERT INTO users (username, password, role) VALUES 
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

-- Insert default school settings
INSERT INTO school_settings (school_name, motto, address, phone, email, academic_year) VALUES 
('Greenwood High School', 'Excellence in Education, Character in Life', '123 Education Street, Knowledge City', '+1-234-567-8900', 'info@greenwoodhigh.edu', '2024-25');

-- Insert default grade scale
INSERT INTO grades (grade_name, min_percentage, max_percentage, grade_point, description) VALUES 
('A+', 90.00, 100.00, 4.00, 'Outstanding'),
('A', 80.00, 89.99, 3.70, 'Excellent'),
('B+', 70.00, 79.99, 3.30, 'Very Good'),
('B', 60.00, 69.99, 3.00, 'Good'),
('C+', 50.00, 59.99, 2.70, 'Satisfactory'),
('C', 40.00, 49.99, 2.00, 'Acceptable'),
('D', 33.00, 39.99, 1.00, 'Below Average'),
('F', 0.00, 32.99, 0.00, 'Fail');

-- Insert sample classes
INSERT INTO classes (class_name, section) VALUES 
('Grade 1', 'A'),
('Grade 1', 'B'),
('Grade 2', 'A'),
('Grade 2', 'B'),
('Grade 3', 'A'),
('Grade 4', 'A'),
('Grade 5', 'A'),
('Grade 6', 'A'),
('Grade 7', 'A'),
('Grade 8', 'A'),
('Grade 9', 'A'),
('Grade 10', 'A');

-- Insert sample subjects for different grades
INSERT INTO subjects (subject_name, class_id, total_marks) VALUES 
-- Grade 1 subjects
('English', 1, 100),
('Mathematics', 1, 100),
('Science', 1, 100),
('Social Studies', 1, 100),
('Art & Craft', 1, 50),
-- Grade 2 subjects
('English', 3, 100),
('Mathematics', 3, 100),
('Science', 3, 100),
('Social Studies', 3, 100),
('Art & Craft', 3, 50),
-- Grade 10 subjects
('English', 12, 100),
('Mathematics', 12, 100),
('Physics', 12, 100),
('Chemistry', 12, 100),
('Biology', 12, 100),
('Computer Science', 12, 100);
