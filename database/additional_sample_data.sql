-- Additional Sample Data for Testing All Features
-- Run this after importing the main sample_data.sql

USE school_management;

-- Add more attendance records for better testing
-- Add attendance for the past 30 days for all students

-- Generate attendance for STU001 (Alice Johnson) - Good attendance
INSERT IGNORE INTO attendance (student_id, date, status, marked_by)
SELECT s.id, DATE_SUB(CURDATE(), INTERVAL n DAY), 
       CASE 
           WHEN n % 10 = 0 THEN 'Absent'
           WHEN n % 15 = 0 THEN 'Late'
           ELSE 'Present'
       END,
       2
FROM students s
CROSS JOIN (
    SELECT 0 as n UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION
    SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9 UNION
    SELECT 10 UNION SELECT 11 UNION SELECT 12 UNION SELECT 13 UNION SELECT 14 UNION
    SELECT 15 UNION SELECT 16 UNION SELECT 17 UNION SELECT 18 UNION SELECT 19 UNION
    SELECT 20 UNION SELECT 21 UNION SELECT 22 UNION SELECT 23 UNION SELECT 24 UNION
    SELECT 25 UNION SELECT 26 UNION SELECT 27 UNION SELECT 28 UNION SELECT 29
) numbers
WHERE s.roll_no = 'STU001'
AND DAYOFWEEK(DATE_SUB(CURDATE(), INTERVAL n DAY)) NOT IN (1, 7); -- Exclude weekends

-- Generate attendance for STU002 (Bob Smith) - Average attendance
INSERT IGNORE INTO attendance (student_id, date, status, marked_by)
SELECT s.id, DATE_SUB(CURDATE(), INTERVAL n DAY), 
       CASE 
           WHEN n % 7 = 0 THEN 'Absent'
           WHEN n % 12 = 0 THEN 'Late'
           ELSE 'Present'
       END,
       2
FROM students s
CROSS JOIN (
    SELECT 0 as n UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION
    SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9 UNION
    SELECT 10 UNION SELECT 11 UNION SELECT 12 UNION SELECT 13 UNION SELECT 14 UNION
    SELECT 15 UNION SELECT 16 UNION SELECT 17 UNION SELECT 18 UNION SELECT 19 UNION
    SELECT 20 UNION SELECT 21 UNION SELECT 22 UNION SELECT 23 UNION SELECT 24 UNION
    SELECT 25 UNION SELECT 26 UNION SELECT 27 UNION SELECT 28 UNION SELECT 29
) numbers
WHERE s.roll_no = 'STU002'
AND DAYOFWEEK(DATE_SUB(CURDATE(), INTERVAL n DAY)) NOT IN (1, 7); -- Exclude weekends

-- Generate attendance for STU003 (Carol Davis) - Excellent attendance
INSERT IGNORE INTO attendance (student_id, date, status, marked_by)
SELECT s.id, DATE_SUB(CURDATE(), INTERVAL n DAY), 
       CASE 
           WHEN n % 20 = 0 THEN 'Late'
           ELSE 'Present'
       END,
       2
FROM students s
CROSS JOIN (
    SELECT 0 as n UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION
    SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9 UNION
    SELECT 10 UNION SELECT 11 UNION SELECT 12 UNION SELECT 13 UNION SELECT 14 UNION
    SELECT 15 UNION SELECT 16 UNION SELECT 17 UNION SELECT 18 UNION SELECT 19 UNION
    SELECT 20 UNION SELECT 21 UNION SELECT 22 UNION SELECT 23 UNION SELECT 24 UNION
    SELECT 25 UNION SELECT 26 UNION SELECT 27 UNION SELECT 28 UNION SELECT 29
) numbers
WHERE s.roll_no = 'STU003'
AND DAYOFWEEK(DATE_SUB(CURDATE(), INTERVAL n DAY)) NOT IN (1, 7); -- Exclude weekends

-- Add more exam results for Grade 10 students - Second Term Examination
INSERT IGNORE INTO results (student_id, subject_id, exam_id, marks_obtained, total_marks)
SELECT st.id, sub.id, 2, 88, sub.total_marks FROM students st, subjects sub, classes c 
WHERE st.roll_no = 'STU001' AND sub.subject_name = 'English' AND sub.class_id = c.id AND c.class_name = 'Grade 10';

INSERT IGNORE INTO results (student_id, subject_id, exam_id, marks_obtained, total_marks)
SELECT st.id, sub.id, 2, 94, sub.total_marks FROM students st, subjects sub, classes c 
WHERE st.roll_no = 'STU001' AND sub.subject_name = 'Mathematics' AND sub.class_id = c.id AND c.class_name = 'Grade 10';

INSERT IGNORE INTO results (student_id, subject_id, exam_id, marks_obtained, total_marks)
SELECT st.id, sub.id, 2, 82, sub.total_marks FROM students st, subjects sub, classes c 
WHERE st.roll_no = 'STU001' AND sub.subject_name = 'Physics' AND sub.class_id = c.id AND c.class_name = 'Grade 10';

INSERT IGNORE INTO results (student_id, subject_id, exam_id, marks_obtained, total_marks)
SELECT st.id, sub.id, 2, 91, sub.total_marks FROM students st, subjects sub, classes c 
WHERE st.roll_no = 'STU001' AND sub.subject_name = 'Chemistry' AND sub.class_id = c.id AND c.class_name = 'Grade 10';

INSERT IGNORE INTO results (student_id, subject_id, exam_id, marks_obtained, total_marks)
SELECT st.id, sub.id, 2, 87, sub.total_marks FROM students st, subjects sub, classes c 
WHERE st.roll_no = 'STU001' AND sub.subject_name = 'Biology' AND sub.class_id = c.id AND c.class_name = 'Grade 10';

INSERT IGNORE INTO results (student_id, subject_id, exam_id, marks_obtained, total_marks)
SELECT st.id, sub.id, 2, 96, sub.total_marks FROM students st, subjects sub, classes c 
WHERE st.roll_no = 'STU001' AND sub.subject_name = 'Computer Science' AND sub.class_id = c.id AND c.class_name = 'Grade 10';

-- Bob Smith (STU002) - Second Term
INSERT IGNORE INTO results (student_id, subject_id, exam_id, marks_obtained, total_marks)
SELECT st.id, sub.id, 2, 78, sub.total_marks FROM students st, subjects sub, classes c 
WHERE st.roll_no = 'STU002' AND sub.subject_name = 'English' AND sub.class_id = c.id AND c.class_name = 'Grade 10';

INSERT IGNORE INTO results (student_id, subject_id, exam_id, marks_obtained, total_marks)
SELECT st.id, sub.id, 2, 85, sub.total_marks FROM students st, subjects sub, classes c 
WHERE st.roll_no = 'STU002' AND sub.subject_name = 'Mathematics' AND sub.class_id = c.id AND c.class_name = 'Grade 10';

INSERT IGNORE INTO results (student_id, subject_id, exam_id, marks_obtained, total_marks)
SELECT st.id, sub.id, 2, 73, sub.total_marks FROM students st, subjects sub, classes c 
WHERE st.roll_no = 'STU002' AND sub.subject_name = 'Physics' AND sub.class_id = c.id AND c.class_name = 'Grade 10';

INSERT IGNORE INTO results (student_id, subject_id, exam_id, marks_obtained, total_marks)
SELECT st.id, sub.id, 2, 81, sub.total_marks FROM students st, subjects sub, classes c 
WHERE st.roll_no = 'STU002' AND sub.subject_name = 'Chemistry' AND sub.class_id = c.id AND c.class_name = 'Grade 10';

INSERT IGNORE INTO results (student_id, subject_id, exam_id, marks_obtained, total_marks)
SELECT st.id, sub.id, 2, 88, sub.total_marks FROM students st, subjects sub, classes c 
WHERE st.roll_no = 'STU002' AND sub.subject_name = 'Biology' AND sub.class_id = c.id AND c.class_name = 'Grade 10';

INSERT IGNORE INTO results (student_id, subject_id, exam_id, marks_obtained, total_marks)
SELECT st.id, sub.id, 2, 91, sub.total_marks FROM students st, subjects sub, classes c 
WHERE st.roll_no = 'STU002' AND sub.subject_name = 'Computer Science' AND sub.class_id = c.id AND c.class_name = 'Grade 10';

-- Carol Davis (STU003) - Second Term
INSERT IGNORE INTO results (student_id, subject_id, exam_id, marks_obtained, total_marks)
SELECT st.id, sub.id, 2, 97, sub.total_marks FROM students st, subjects sub, classes c 
WHERE st.roll_no = 'STU003' AND sub.subject_name = 'English' AND sub.class_id = c.id AND c.class_name = 'Grade 10';

INSERT IGNORE INTO results (student_id, subject_id, exam_id, marks_obtained, total_marks)
SELECT st.id, sub.id, 2, 91, sub.total_marks FROM students st, subjects sub, classes c 
WHERE st.roll_no = 'STU003' AND sub.subject_name = 'Mathematics' AND sub.class_id = c.id AND c.class_name = 'Grade 10';

INSERT IGNORE INTO results (student_id, subject_id, exam_id, marks_obtained, total_marks)
SELECT st.id, sub.id, 2, 95, sub.total_marks FROM students st, subjects sub, classes c 
WHERE st.roll_no = 'STU003' AND sub.subject_name = 'Physics' AND sub.class_id = c.id AND c.class_name = 'Grade 10';

INSERT IGNORE INTO results (student_id, subject_id, exam_id, marks_obtained, total_marks)
SELECT st.id, sub.id, 2, 93, sub.total_marks FROM students st, subjects sub, classes c 
WHERE st.roll_no = 'STU003' AND sub.subject_name = 'Chemistry' AND sub.class_id = c.id AND c.class_name = 'Grade 10';

INSERT IGNORE INTO results (student_id, subject_id, exam_id, marks_obtained, total_marks)
SELECT st.id, sub.id, 2, 89, sub.total_marks FROM students st, subjects sub, classes c 
WHERE st.roll_no = 'STU003' AND sub.subject_name = 'Biology' AND sub.class_id = c.id AND c.class_name = 'Grade 10';

INSERT IGNORE INTO results (student_id, subject_id, exam_id, marks_obtained, total_marks)
SELECT st.id, sub.id, 2, 96, sub.total_marks FROM students st, subjects sub, classes c 
WHERE st.roll_no = 'STU003' AND sub.subject_name = 'Computer Science' AND sub.class_id = c.id AND c.class_name = 'Grade 10';

-- Add Grade 1 student results for First Term Examination
-- David Wilson (STU004) - Grade 1 First Term
INSERT IGNORE INTO results (student_id, subject_id, exam_id, marks_obtained, total_marks)
SELECT st.id, sub.id, 5, 42, sub.total_marks FROM students st, subjects sub, classes c 
WHERE st.roll_no = 'STU004' AND sub.subject_name = 'English' AND sub.class_id = c.id AND c.class_name = 'Grade 1';

INSERT IGNORE INTO results (student_id, subject_id, exam_id, marks_obtained, total_marks)
SELECT st.id, sub.id, 5, 46, sub.total_marks FROM students st, subjects sub, classes c 
WHERE st.roll_no = 'STU004' AND sub.subject_name = 'Mathematics' AND sub.class_id = c.id AND c.class_name = 'Grade 1';

INSERT IGNORE INTO results (student_id, subject_id, exam_id, marks_obtained, total_marks)
SELECT st.id, sub.id, 5, 40, sub.total_marks FROM students st, subjects sub, classes c 
WHERE st.roll_no = 'STU004' AND sub.subject_name = 'Science' AND sub.class_id = c.id AND c.class_name = 'Grade 1';

INSERT IGNORE INTO results (student_id, subject_id, exam_id, marks_obtained, total_marks)
SELECT st.id, sub.id, 5, 44, sub.total_marks FROM students st, subjects sub, classes c 
WHERE st.roll_no = 'STU004' AND sub.subject_name = 'Social Studies' AND sub.class_id = c.id AND c.class_name = 'Grade 1';

INSERT IGNORE INTO results (student_id, subject_id, exam_id, marks_obtained, total_marks)
SELECT st.id, sub.id, 5, 23, sub.total_marks FROM students st, subjects sub, classes c 
WHERE st.roll_no = 'STU004' AND sub.subject_name = 'Art & Craft' AND sub.class_id = c.id AND c.class_name = 'Grade 1';

-- Emma Brown (STU005) - Grade 1 First Term
INSERT IGNORE INTO results (student_id, subject_id, exam_id, marks_obtained, total_marks)
SELECT st.id, sub.id, 5, 49, sub.total_marks FROM students st, subjects sub, classes c 
WHERE st.roll_no = 'STU005' AND sub.subject_name = 'English' AND sub.class_id = c.id AND c.class_name = 'Grade 1';

INSERT IGNORE INTO results (student_id, subject_id, exam_id, marks_obtained, total_marks)
SELECT st.id, sub.id, 5, 47, sub.total_marks FROM students st, subjects sub, classes c 
WHERE st.roll_no = 'STU005' AND sub.subject_name = 'Mathematics' AND sub.class_id = c.id AND c.class_name = 'Grade 1';

INSERT IGNORE INTO results (student_id, subject_id, exam_id, marks_obtained, total_marks)
SELECT st.id, sub.id, 5, 50, sub.total_marks FROM students st, subjects sub, classes c 
WHERE st.roll_no = 'STU005' AND sub.subject_name = 'Science' AND sub.class_id = c.id AND c.class_name = 'Grade 1';

INSERT IGNORE INTO results (student_id, subject_id, exam_id, marks_obtained, total_marks)
SELECT st.id, sub.id, 5, 46, sub.total_marks FROM students st, subjects sub, classes c 
WHERE st.roll_no = 'STU005' AND sub.subject_name = 'Social Studies' AND sub.class_id = c.id AND c.class_name = 'Grade 1';

INSERT IGNORE INTO results (student_id, subject_id, exam_id, marks_obtained, total_marks)
SELECT st.id, sub.id, 5, 25, sub.total_marks FROM students st, subjects sub, classes c 
WHERE st.roll_no = 'STU005' AND sub.subject_name = 'Art & Craft' AND sub.class_id = c.id AND c.class_name = 'Grade 1';

-- Update school settings with sample data
UPDATE school_settings SET 
    school_name = 'Greenwood High School',
    motto = 'Excellence in Education, Character in Life',
    address = '123 Education Street, Academic City, State 12345',
    phone = '+1-234-567-8900',
    email = 'info@greenwoodhigh.edu',
    website = 'www.greenwoodhigh.edu',
    academic_year = '2024-25',
    principal_name = 'Dr. Sarah Mitchell',
    vice_principal_name = 'Mr. Robert Anderson'
WHERE id = 1;

-- Add some teacher-class assignments for better testing
INSERT IGNORE INTO teacher_classes (teacher_id, class_id, subject_id, is_class_teacher) 
SELECT t.id, c.id, s.id, 0 
FROM teachers t, subjects s, classes c, users u
WHERE u.username = 'teacher2' AND t.user_id = u.id AND s.subject_name = 'English' AND c.class_name = 'Grade 1' AND s.class_id = c.id LIMIT 1;

INSERT IGNORE INTO teacher_classes (teacher_id, class_id, subject_id, is_class_teacher) 
SELECT t.id, c.id, s.id, 0 
FROM teachers t, subjects s, classes c, users u
WHERE u.username = 'teacher3' AND t.user_id = u.id AND s.subject_name = 'Biology' AND c.class_name = 'Grade 10' AND s.class_id = c.id LIMIT 1;

-- Add some notification/announcement data (if you want to extend the system later)
-- This is optional and can be used for future enhancements

COMMIT;
