-- Sample Data for School Result & Attendance Management System

USE school_management;

-- Insert sample teachers (skip if already exists)
-- Password for all teachers: teacher123
INSERT IGNORE INTO users (username, password, role) VALUES 
('teacher1', '$2y$10$8K1p/MQh1.WzqDqR4RXF8.6Y1uuzeYcqcZ2pLm4wuP1wTxlWjO/Oy', 'teacher'),
('teacher2', '$2y$10$8K1p/MQh1.WzqDqR4RXF8.6Y1uuzeYcqcZ2pLm4wuP1wTxlWjO/Oy', 'teacher'),
('teacher3', '$2y$10$8K1p/MQh1.WzqDqR4RXF8.6Y1uuzeYcqcZ2pLm4wuP1wTxlWjO/Oy', 'teacher');

-- Insert teacher details using dynamic user IDs
INSERT IGNORE INTO teachers (user_id, name, email, phone, subject_specialization, qualification, experience_years) 
SELECT u.id, 'John Smith', 'john.smith@school.edu', '+1-234-567-8901', 'Mathematics', 'M.Sc Mathematics, B.Ed', 8 
FROM users u WHERE u.username = 'teacher1' AND u.role = 'teacher';

INSERT IGNORE INTO teachers (user_id, name, email, phone, subject_specialization, qualification, experience_years) 
SELECT u.id, 'Sarah Johnson', 'sarah.johnson@school.edu', '+1-234-567-8902', 'English Literature', 'M.A English, B.Ed', 6 
FROM users u WHERE u.username = 'teacher2' AND u.role = 'teacher';

INSERT IGNORE INTO teachers (user_id, name, email, phone, subject_specialization, qualification, experience_years) 
SELECT u.id, 'Michael Brown', 'michael.brown@school.edu', '+1-234-567-8903', 'Science', 'M.Sc Physics, B.Ed', 10 
FROM users u WHERE u.username = 'teacher3' AND u.role = 'teacher';

-- Insert sample students (skip if already exists)
-- Password for each student is their roll number (STU001, STU002, etc.)
INSERT IGNORE INTO users (username, password, role) VALUES 
('STU001', '$2y$10$7O2FHxjodBL/8alWrd6hO.Jk5d8QxcJjd5.KQJX5qRb5FtXjO/Oy', 'student'),
('STU002', '$2y$10$7O2FHxjodBL/8alWrd6hO.Jk5d8QxcJjd5.KQJX5qRb5FtXjO/Oy', 'student'),
('STU003', '$2y$10$7O2FHxjodBL/8alWrd6hO.Jk5d8QxcJjd5.KQJX5qRb5FtXjO/Oy', 'student'),
('STU004', '$2y$10$7O2FHxjodBL/8alWrd6hO.Jk5d8QxcJjd5.KQJX5qRb5FtXjO/Oy', 'student'),
('STU005', '$2y$10$7O2FHxjodBL/8alWrd6hO.Jk5d8QxcJjd5.KQJX5qRb5FtXjO/Oy', 'student');

-- Insert student details using dynamic user and class IDs
INSERT IGNORE INTO students (user_id, name, roll_no, class_id, parent_name, parent_contact, parent_email, address, date_of_birth, gender, admission_date) 
SELECT u.id, 'Alice Johnson', 'STU001', c.id, 'Robert Johnson', '+1-234-567-9001', 'robert.johnson@email.com', '123 Oak Street, Springfield', '2008-05-15', 'Female', '2023-04-01'
FROM users u, classes c WHERE u.username = 'STU001' AND c.class_name = 'Grade 10' AND c.section = 'A';

INSERT IGNORE INTO students (user_id, name, roll_no, class_id, parent_name, parent_contact, parent_email, address, date_of_birth, gender, admission_date) 
SELECT u.id, 'Bob Smith', 'STU002', c.id, 'David Smith', '+1-234-567-9002', 'david.smith@email.com', '456 Pine Avenue, Springfield', '2008-08-22', 'Male', '2023-04-01'
FROM users u, classes c WHERE u.username = 'STU002' AND c.class_name = 'Grade 10' AND c.section = 'A';

INSERT IGNORE INTO students (user_id, name, roll_no, class_id, parent_name, parent_contact, parent_email, address, date_of_birth, gender, admission_date) 
SELECT u.id, 'Carol Davis', 'STU003', c.id, 'Linda Davis', '+1-234-567-9003', 'linda.davis@email.com', '789 Maple Drive, Springfield', '2008-03-10', 'Female', '2023-04-01'
FROM users u, classes c WHERE u.username = 'STU003' AND c.class_name = 'Grade 10' AND c.section = 'A';

INSERT IGNORE INTO students (user_id, name, roll_no, class_id, parent_name, parent_contact, parent_email, address, date_of_birth, gender, admission_date) 
SELECT u.id, 'David Wilson', 'STU004', c.id, 'Mark Wilson', '+1-234-567-9004', 'mark.wilson@email.com', '321 Elm Street, Springfield', '2008-11-05', 'Male', '2023-04-01'
FROM users u, classes c WHERE u.username = 'STU004' AND c.class_name = 'Grade 1' AND c.section = 'A';

INSERT IGNORE INTO students (user_id, name, roll_no, class_id, parent_name, parent_contact, parent_email, address, date_of_birth, gender, admission_date) 
SELECT u.id, 'Emma Brown', 'STU005', c.id, 'Jennifer Brown', '+1-234-567-9005', 'jennifer.brown@email.com', '654 Cedar Lane, Springfield', '2008-07-18', 'Female', '2023-04-01'
FROM users u, classes c WHERE u.username = 'STU005' AND c.class_name = 'Grade 1' AND c.section = 'A';

-- Assign teachers to classes and subjects using dynamic teacher IDs
INSERT IGNORE INTO teacher_classes (teacher_id, class_id, subject_id, is_class_teacher) 
SELECT t.id, c.id, s.id, 1 
FROM teachers t, subjects s, classes c, users u
WHERE u.username = 'teacher1' AND t.user_id = u.id AND s.subject_name = 'Mathematics' AND c.class_name = 'Grade 10' AND s.class_id = c.id LIMIT 1;

INSERT IGNORE INTO teacher_classes (teacher_id, class_id, subject_id, is_class_teacher) 
SELECT t.id, c.id, s.id, 0 
FROM teachers t, subjects s, classes c, users u
WHERE u.username = 'teacher1' AND t.user_id = u.id AND s.subject_name = 'Mathematics' AND c.class_name = 'Grade 1' AND s.class_id = c.id LIMIT 1;

INSERT IGNORE INTO teacher_classes (teacher_id, class_id, subject_id, is_class_teacher) 
SELECT t.id, c.id, s.id, 0 
FROM teachers t, subjects s, classes c, users u
WHERE u.username = 'teacher2' AND t.user_id = u.id AND s.subject_name = 'English' AND c.class_name = 'Grade 10' AND s.class_id = c.id LIMIT 1;

INSERT IGNORE INTO teacher_classes (teacher_id, class_id, subject_id, is_class_teacher) 
SELECT t.id, c.id, s.id, 0 
FROM teachers t, subjects s, classes c, users u
WHERE u.username = 'teacher2' AND t.user_id = u.id AND s.subject_name = 'English' AND c.class_name = 'Grade 1' AND s.class_id = c.id LIMIT 1;

INSERT IGNORE INTO teacher_classes (teacher_id, class_id, subject_id, is_class_teacher) 
SELECT t.id, c.id, s.id, 0 
FROM teachers t, subjects s, classes c, users u
WHERE u.username = 'teacher3' AND t.user_id = u.id AND s.subject_name = 'Physics' AND c.class_name = 'Grade 10' AND s.class_id = c.id LIMIT 1;

INSERT IGNORE INTO teacher_classes (teacher_id, class_id, subject_id, is_class_teacher) 
SELECT t.id, c.id, s.id, 0 
FROM teachers t, subjects s, classes c, users u
WHERE u.username = 'teacher3' AND t.user_id = u.id AND s.subject_name = 'Chemistry' AND c.class_name = 'Grade 10' AND s.class_id = c.id LIMIT 1;

INSERT IGNORE INTO teacher_classes (teacher_id, class_id, subject_id, is_class_teacher) 
SELECT t.id, c.id, s.id, 0 
FROM teachers t, subjects s, classes c, users u
WHERE u.username = 'teacher3' AND t.user_id = u.id AND s.subject_name = 'Science' AND c.class_name = 'Grade 1' AND s.class_id = c.id LIMIT 1;

-- Insert sample exams
INSERT INTO exams (exam_name, exam_type, class_id, exam_date, academic_year, status) VALUES 
('First Term Examination', 'Midterm', 12, '2024-07-15', '2024-25', 'Completed'),
('Second Term Examination', 'Midterm', 12, '2024-11-20', '2024-25', 'Completed'),
('Final Examination', 'Final', 12, '2024-03-15', '2024-25', 'Scheduled'),
('Monthly Test - September', 'Monthly', 1, '2024-09-25', '2024-25', 'Completed'),
('First Term Examination', 'Midterm', 1, '2024-07-15', '2024-25', 'Completed');

-- Insert sample results using dynamic subject and student IDs (skip if already exists)
-- First, let's insert results for Grade 10 students (Alice, Bob, Carol) - First Term Exam

-- Alice Johnson (STU001) - First Term
INSERT IGNORE INTO results (student_id, subject_id, exam_id, marks_obtained, total_marks)
SELECT st.id, sub.id, 1, 85, sub.total_marks FROM students st, subjects sub, classes c 
WHERE st.roll_no = 'STU001' AND sub.subject_name = 'English' AND sub.class_id = c.id AND c.class_name = 'Grade 10';

INSERT IGNORE INTO results (student_id, subject_id, exam_id, marks_obtained, total_marks)
SELECT st.id, sub.id, 1, 92, sub.total_marks FROM students st, subjects sub, classes c 
WHERE st.roll_no = 'STU001' AND sub.subject_name = 'Mathematics' AND sub.class_id = c.id AND c.class_name = 'Grade 10';

INSERT IGNORE INTO results (student_id, subject_id, exam_id, marks_obtained, total_marks)
SELECT st.id, sub.id, 1, 78, sub.total_marks FROM students st, subjects sub, classes c 
WHERE st.roll_no = 'STU001' AND sub.subject_name = 'Physics' AND sub.class_id = c.id AND c.class_name = 'Grade 10';

INSERT IGNORE INTO results (student_id, subject_id, exam_id, marks_obtained, total_marks)
SELECT st.id, sub.id, 1, 88, sub.total_marks FROM students st, subjects sub, classes c 
WHERE st.roll_no = 'STU001' AND sub.subject_name = 'Chemistry' AND sub.class_id = c.id AND c.class_name = 'Grade 10';

INSERT IGNORE INTO results (student_id, subject_id, exam_id, marks_obtained, total_marks)
SELECT st.id, sub.id, 1, 90, sub.total_marks FROM students st, subjects sub, classes c 
WHERE st.roll_no = 'STU001' AND sub.subject_name = 'Biology' AND sub.class_id = c.id AND c.class_name = 'Grade 10';

INSERT IGNORE INTO results (student_id, subject_id, exam_id, marks_obtained, total_marks)
SELECT st.id, sub.id, 1, 95, sub.total_marks FROM students st, subjects sub, classes c 
WHERE st.roll_no = 'STU001' AND sub.subject_name = 'Computer Science' AND sub.class_id = c.id AND c.class_name = 'Grade 10';

-- Bob Smith (STU002) - First Term
INSERT IGNORE INTO results (student_id, subject_id, exam_id, marks_obtained, total_marks)
SELECT st.id, sub.id, 1, 75, sub.total_marks FROM students st, subjects sub, classes c 
WHERE st.roll_no = 'STU002' AND sub.subject_name = 'English' AND sub.class_id = c.id AND c.class_name = 'Grade 10';

INSERT IGNORE INTO results (student_id, subject_id, exam_id, marks_obtained, total_marks)
SELECT st.id, sub.id, 1, 82, sub.total_marks FROM students st, subjects sub, classes c 
WHERE st.roll_no = 'STU002' AND sub.subject_name = 'Mathematics' AND sub.class_id = c.id AND c.class_name = 'Grade 10';

INSERT IGNORE INTO results (student_id, subject_id, exam_id, marks_obtained, total_marks)
SELECT st.id, sub.id, 1, 70, sub.total_marks FROM students st, subjects sub, classes c 
WHERE st.roll_no = 'STU002' AND sub.subject_name = 'Physics' AND sub.class_id = c.id AND c.class_name = 'Grade 10';

INSERT IGNORE INTO results (student_id, subject_id, exam_id, marks_obtained, total_marks)
SELECT st.id, sub.id, 1, 78, sub.total_marks FROM students st, subjects sub, classes c 
WHERE st.roll_no = 'STU002' AND sub.subject_name = 'Chemistry' AND sub.class_id = c.id AND c.class_name = 'Grade 10';

INSERT IGNORE INTO results (student_id, subject_id, exam_id, marks_obtained, total_marks)
SELECT st.id, sub.id, 1, 85, sub.total_marks FROM students st, subjects sub, classes c 
WHERE st.roll_no = 'STU002' AND sub.subject_name = 'Biology' AND sub.class_id = c.id AND c.class_name = 'Grade 10';

INSERT IGNORE INTO results (student_id, subject_id, exam_id, marks_obtained, total_marks)
SELECT st.id, sub.id, 1, 88, sub.total_marks FROM students st, subjects sub, classes c 
WHERE st.roll_no = 'STU002' AND sub.subject_name = 'Computer Science' AND sub.class_id = c.id AND c.class_name = 'Grade 10';

-- Carol Davis (STU003) - First Term
INSERT IGNORE INTO results (student_id, subject_id, exam_id, marks_obtained, total_marks)
SELECT st.id, sub.id, 1, 95, sub.total_marks FROM students st, subjects sub, classes c 
WHERE st.roll_no = 'STU003' AND sub.subject_name = 'English' AND sub.class_id = c.id AND c.class_name = 'Grade 10';

INSERT IGNORE INTO results (student_id, subject_id, exam_id, marks_obtained, total_marks)
SELECT st.id, sub.id, 1, 88, sub.total_marks FROM students st, subjects sub, classes c 
WHERE st.roll_no = 'STU003' AND sub.subject_name = 'Mathematics' AND sub.class_id = c.id AND c.class_name = 'Grade 10';

INSERT IGNORE INTO results (student_id, subject_id, exam_id, marks_obtained, total_marks)
SELECT st.id, sub.id, 1, 92, sub.total_marks FROM students st, subjects sub, classes c 
WHERE st.roll_no = 'STU003' AND sub.subject_name = 'Physics' AND sub.class_id = c.id AND c.class_name = 'Grade 10';

INSERT IGNORE INTO results (student_id, subject_id, exam_id, marks_obtained, total_marks)
SELECT st.id, sub.id, 1, 90, sub.total_marks FROM students st, subjects sub, classes c 
WHERE st.roll_no = 'STU003' AND sub.subject_name = 'Chemistry' AND sub.class_id = c.id AND c.class_name = 'Grade 10';

INSERT IGNORE INTO results (student_id, subject_id, exam_id, marks_obtained, total_marks)
SELECT st.id, sub.id, 1, 87, sub.total_marks FROM students st, subjects sub, classes c 
WHERE st.roll_no = 'STU003' AND sub.subject_name = 'Biology' AND sub.class_id = c.id AND c.class_name = 'Grade 10';

INSERT IGNORE INTO results (student_id, subject_id, exam_id, marks_obtained, total_marks)
SELECT st.id, sub.id, 1, 93, sub.total_marks FROM students st, subjects sub, classes c 
WHERE st.roll_no = 'STU003' AND sub.subject_name = 'Computer Science' AND sub.class_id = c.id AND c.class_name = 'Grade 10';

-- Grade 1 students results for Monthly Test
-- David Wilson (STU004) - Grade 1 Monthly Test
INSERT IGNORE INTO results (student_id, subject_id, exam_id, marks_obtained, total_marks)
SELECT st.id, sub.id, 4, 45, sub.total_marks FROM students st, subjects sub, classes c 
WHERE st.roll_no = 'STU004' AND sub.subject_name = 'English' AND sub.class_id = c.id AND c.class_name = 'Grade 1';

INSERT IGNORE INTO results (student_id, subject_id, exam_id, marks_obtained, total_marks)
SELECT st.id, sub.id, 4, 48, sub.total_marks FROM students st, subjects sub, classes c 
WHERE st.roll_no = 'STU004' AND sub.subject_name = 'Mathematics' AND sub.class_id = c.id AND c.class_name = 'Grade 1';

INSERT IGNORE INTO results (student_id, subject_id, exam_id, marks_obtained, total_marks)
SELECT st.id, sub.id, 4, 42, sub.total_marks FROM students st, subjects sub, classes c 
WHERE st.roll_no = 'STU004' AND sub.subject_name = 'Science' AND sub.class_id = c.id AND c.class_name = 'Grade 1';

INSERT IGNORE INTO results (student_id, subject_id, exam_id, marks_obtained, total_marks)
SELECT st.id, sub.id, 4, 46, sub.total_marks FROM students st, subjects sub, classes c 
WHERE st.roll_no = 'STU004' AND sub.subject_name = 'Social Studies' AND sub.class_id = c.id AND c.class_name = 'Grade 1';

INSERT IGNORE INTO results (student_id, subject_id, exam_id, marks_obtained, total_marks)
SELECT st.id, sub.id, 4, 25, sub.total_marks FROM students st, subjects sub, classes c 
WHERE st.roll_no = 'STU004' AND sub.subject_name = 'Art & Craft' AND sub.class_id = c.id AND c.class_name = 'Grade 1';

-- Emma Brown (STU005) - Grade 1 Monthly Test
INSERT IGNORE INTO results (student_id, subject_id, exam_id, marks_obtained, total_marks)
SELECT st.id, sub.id, 4, 47, sub.total_marks FROM students st, subjects sub, classes c 
WHERE st.roll_no = 'STU005' AND sub.subject_name = 'English' AND sub.class_id = c.id AND c.class_name = 'Grade 1';

INSERT IGNORE INTO results (student_id, subject_id, exam_id, marks_obtained, total_marks)
SELECT st.id, sub.id, 4, 45, sub.total_marks FROM students st, subjects sub, classes c 
WHERE st.roll_no = 'STU005' AND sub.subject_name = 'Mathematics' AND sub.class_id = c.id AND c.class_name = 'Grade 1';

INSERT IGNORE INTO results (student_id, subject_id, exam_id, marks_obtained, total_marks)
SELECT st.id, sub.id, 4, 48, sub.total_marks FROM students st, subjects sub, classes c 
WHERE st.roll_no = 'STU005' AND sub.subject_name = 'Science' AND sub.class_id = c.id AND c.class_name = 'Grade 1';

INSERT IGNORE INTO results (student_id, subject_id, exam_id, marks_obtained, total_marks)
SELECT st.id, sub.id, 4, 44, sub.total_marks FROM students st, subjects sub, classes c 
WHERE st.roll_no = 'STU005' AND sub.subject_name = 'Social Studies' AND sub.class_id = c.id AND c.class_name = 'Grade 1';

INSERT IGNORE INTO results (student_id, subject_id, exam_id, marks_obtained, total_marks)
SELECT st.id, sub.id, 4, 24, sub.total_marks FROM students st, subjects sub, classes c 
WHERE st.roll_no = 'STU005' AND sub.subject_name = 'Art & Craft' AND sub.class_id = c.id AND c.class_name = 'Grade 1';

-- Insert sample attendance data for current month using dynamic student IDs
-- Alice Johnson attendance (STU001)
INSERT IGNORE INTO attendance (student_id, date, status, marked_by)
SELECT s.id, '2024-09-01', 'Present', 2 FROM students s WHERE s.roll_no = 'STU001';
INSERT IGNORE INTO attendance (student_id, date, status, marked_by)
SELECT s.id, '2024-09-02', 'Present', 2 FROM students s WHERE s.roll_no = 'STU001';
INSERT IGNORE INTO attendance (student_id, date, status, marked_by)
SELECT s.id, '2024-09-03', 'Present', 2 FROM students s WHERE s.roll_no = 'STU001';
INSERT IGNORE INTO attendance (student_id, date, status, marked_by)
SELECT s.id, '2024-09-04', 'Late', 2 FROM students s WHERE s.roll_no = 'STU001';
INSERT IGNORE INTO attendance (student_id, date, status, marked_by)
SELECT s.id, '2024-09-05', 'Present', 2 FROM students s WHERE s.roll_no = 'STU001';
INSERT IGNORE INTO attendance (student_id, date, status, marked_by)
SELECT s.id, '2024-09-06', 'Present', 2 FROM students s WHERE s.roll_no = 'STU001';
INSERT IGNORE INTO attendance (student_id, date, status, marked_by)
SELECT s.id, '2024-09-09', 'Present', 2 FROM students s WHERE s.roll_no = 'STU001';
INSERT IGNORE INTO attendance (student_id, date, status, marked_by)
SELECT s.id, '2024-09-10', 'Absent', 2 FROM students s WHERE s.roll_no = 'STU001';
INSERT IGNORE INTO attendance (student_id, date, status, marked_by)
SELECT s.id, '2024-09-11', 'Present', 2 FROM students s WHERE s.roll_no = 'STU001';
INSERT IGNORE INTO attendance (student_id, date, status, marked_by)
SELECT s.id, '2024-09-12', 'Present', 2 FROM students s WHERE s.roll_no = 'STU001';

-- Bob Smith attendance (STU002)
INSERT IGNORE INTO attendance (student_id, date, status, marked_by)
SELECT s.id, '2024-09-01', 'Present', 2 FROM students s WHERE s.roll_no = 'STU002';
INSERT IGNORE INTO attendance (student_id, date, status, marked_by)
SELECT s.id, '2024-09-02', 'Absent', 2 FROM students s WHERE s.roll_no = 'STU002';
INSERT IGNORE INTO attendance (student_id, date, status, marked_by)
SELECT s.id, '2024-09-03', 'Present', 2 FROM students s WHERE s.roll_no = 'STU002';
INSERT IGNORE INTO attendance (student_id, date, status, marked_by)
SELECT s.id, '2024-09-04', 'Present', 2 FROM students s WHERE s.roll_no = 'STU002';
INSERT IGNORE INTO attendance (student_id, date, status, marked_by)
SELECT s.id, '2024-09-05', 'Present', 2 FROM students s WHERE s.roll_no = 'STU002';
INSERT IGNORE INTO attendance (student_id, date, status, marked_by)
SELECT s.id, '2024-09-06', 'Late', 2 FROM students s WHERE s.roll_no = 'STU002';
INSERT IGNORE INTO attendance (student_id, date, status, marked_by)
SELECT s.id, '2024-09-09', 'Present', 2 FROM students s WHERE s.roll_no = 'STU002';
INSERT IGNORE INTO attendance (student_id, date, status, marked_by)
SELECT s.id, '2024-09-10', 'Present', 2 FROM students s WHERE s.roll_no = 'STU002';
INSERT IGNORE INTO attendance (student_id, date, status, marked_by)
SELECT s.id, '2024-09-11', 'Absent', 2 FROM students s WHERE s.roll_no = 'STU002';
INSERT IGNORE INTO attendance (student_id, date, status, marked_by)
SELECT s.id, '2024-09-12', 'Present', 2 FROM students s WHERE s.roll_no = 'STU002';

-- Carol Davis attendance (STU003)
INSERT IGNORE INTO attendance (student_id, date, status, marked_by)
SELECT s.id, '2024-09-01', 'Present', 2 FROM students s WHERE s.roll_no = 'STU003';
INSERT IGNORE INTO attendance (student_id, date, status, marked_by)
SELECT s.id, '2024-09-02', 'Present', 2 FROM students s WHERE s.roll_no = 'STU003';
INSERT IGNORE INTO attendance (student_id, date, status, marked_by)
SELECT s.id, '2024-09-03', 'Present', 2 FROM students s WHERE s.roll_no = 'STU003';
INSERT IGNORE INTO attendance (student_id, date, status, marked_by)
SELECT s.id, '2024-09-04', 'Present', 2 FROM students s WHERE s.roll_no = 'STU003';
INSERT IGNORE INTO attendance (student_id, date, status, marked_by)
SELECT s.id, '2024-09-05', 'Present', 2 FROM students s WHERE s.roll_no = 'STU003';
INSERT IGNORE INTO attendance (student_id, date, status, marked_by)
SELECT s.id, '2024-09-06', 'Present', 2 FROM students s WHERE s.roll_no = 'STU003';
INSERT IGNORE INTO attendance (student_id, date, status, marked_by)
SELECT s.id, '2024-09-09', 'Present', 2 FROM students s WHERE s.roll_no = 'STU003';
INSERT IGNORE INTO attendance (student_id, date, status, marked_by)
SELECT s.id, '2024-09-10', 'Present', 2 FROM students s WHERE s.roll_no = 'STU003';
INSERT IGNORE INTO attendance (student_id, date, status, marked_by)
SELECT s.id, '2024-09-11', 'Present', 2 FROM students s WHERE s.roll_no = 'STU003';
INSERT IGNORE INTO attendance (student_id, date, status, marked_by)
SELECT s.id, '2024-09-12', 'Present', 2 FROM students s WHERE s.roll_no = 'STU003';

-- Create upload directories (Note: This needs to be done manually on the server)
-- mkdir -p uploads/school
-- mkdir -p uploads/students
-- chmod 755 uploads/school uploads/students
