<?php
include 'config/database.php';

$verification_code = isset($_GET['code']) ? sanitize_input($_GET['code']) : '';
$error = '';
$verification_data = null;

if (!empty($verification_code)) {
    // Get verification data
    $verify_query = "
        SELECT rv.*, s.name, s.roll_no, s.photo, c.class_name, c.section, e.exam_name, e.exam_type, e.exam_date
        FROM result_verifications rv
        JOIN students s ON rv.student_id = s.id
        JOIN classes c ON s.class_id = c.id
        JOIN exams e ON rv.exam_id = e.id
        WHERE rv.verification_code = ? AND rv.is_active = 1 AND rv.expires_at > NOW()
    ";
    $verify_stmt = $conn->prepare($verify_query);
    $verify_stmt->bind_param("s", $verification_code);
    $verify_stmt->execute();
    $verify_result = $verify_stmt->get_result();
    
    if ($verify_result->num_rows > 0) {
        $verification_data = $verify_result->fetch_assoc();
        
        // Get results for this verification
        $results_query = "
            SELECT r.*, sub.subject_name
            FROM results r
            JOIN subjects sub ON r.subject_id = sub.id
            WHERE r.student_id = ? AND r.exam_id = ?
            ORDER BY sub.subject_name
        ";
        $results_stmt = $conn->prepare($results_query);
        $results_stmt->bind_param("ii", $verification_data['student_id'], $verification_data['exam_id']);
        $results_stmt->execute();
        $results_result = $results_stmt->get_result();
        
        $results_data = [];
        $total_obtained = 0;
        $total_possible = 0;
        
        while ($row = $results_result->fetch_assoc()) {
            $results_data[] = $row;
            $total_obtained += $row['marks_obtained'];
            $total_possible += $row['total_marks'];
        }
        
        $percentage = $total_possible > 0 ? round(($total_obtained / $total_possible) * 100, 2) : 0;
        
        // Calculate grade
        if ($percentage >= 90) $grade = 'A+';
        elseif ($percentage >= 80) $grade = 'A';
        elseif ($percentage >= 70) $grade = 'B+';
        elseif ($percentage >= 60) $grade = 'B';
        elseif ($percentage >= 50) $grade = 'C+';
        elseif ($percentage >= 40) $grade = 'C';
        elseif ($percentage >= 33) $grade = 'D';
        else $grade = 'F';
        
    } else {
        $error = "Invalid or expired verification code.";
    }
} else {
    $error = "No verification code provided.";
}

$school_settings = get_school_settings();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Result Verification - School Management System</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .verification-container {
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
        }
        .verification-header {
            text-align: center;
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
        }
        .verification-content {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }
        .student-info-card {
            display: flex;
            align-items: center;
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        .student-photo {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
            margin-right: 20px;
            border: 3px solid #667eea;
        }
        .student-details h3 {
            margin: 0 0 10px 0;
            color: #2c3e50;
        }
        .student-details p {
            margin: 5px 0;
            color: #7f8c8d;
        }
        .results-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        .results-table th,
        .results-table td {
            border: 1px solid #e9ecef;
            padding: 12px;
            text-align: center;
        }
        .results-table th {
            background: #667eea;
            color: white;
            font-weight: 600;
        }
        .results-table tr:nth-child(even) {
            background: #f8f9fa;
        }
        .summary-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }
        .summary-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
        }
        .summary-card h3 {
            font-size: 2rem;
            margin: 0 0 10px 0;
        }
        .verification-badge {
            background: #28a745;
            color: white;
            padding: 10px 20px;
            border-radius: 25px;
            display: inline-block;
            margin: 20px 0;
        }
        .error-message {
            background: #f8d7da;
            color: #721c24;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>
<body>
    <div class="verification-container">
        <div class="verification-header">
            <?php if ($school_settings && $school_settings['logo']): ?>
                <img src="uploads/school/<?php echo $school_settings['logo']; ?>" alt="School Logo" style="width: 80px; height: 80px; border-radius: 50%; margin-bottom: 15px;">
            <?php endif; ?>
            <h1><?php echo $school_settings ? $school_settings['school_name'] : 'School Management System'; ?></h1>
            <p style="color: #7f8c8d; font-size: 1.1rem;">Result Verification Portal</p>
            
            <?php if ($verification_data): ?>
                <div class="verification-badge">
                    <i class="fas fa-check-circle"></i> Verified Result
                </div>
            <?php endif; ?>
        </div>
        
        <?php if ($error): ?>
            <div class="verification-content">
                <div class="error-message">
                    <i class="fas fa-exclamation-triangle" style="font-size: 2rem; margin-bottom: 15px;"></i>
                    <h3>Verification Failed</h3>
                    <p><?php echo $error; ?></p>
                    <a href="index.php" class="btn btn-primary" style="margin-top: 15px;">
                        <i class="fas fa-home"></i> Back to Home
                    </a>
                </div>
            </div>
        <?php else: ?>
            <div class="verification-content">
                <h2 style="text-align: center; color: #2c3e50; margin-bottom: 30px;">
                    <i class="fas fa-certificate"></i> Official Result Card
                </h2>
                
                <!-- Student Information -->
                <div class="student-info-card">
                    <?php if ($verification_data['photo']): ?>
                        <img src="uploads/students/<?php echo $verification_data['photo']; ?>" alt="Student Photo" class="student-photo">
                    <?php else: ?>
                        <div class="student-photo" style="background: #f8f9fa; display: flex; align-items: center; justify-content: center;">
                            <i class="fas fa-user" style="font-size: 2rem; color: #667eea;"></i>
                        </div>
                    <?php endif; ?>
                    
                    <div class="student-details">
                        <h3><?php echo htmlspecialchars($verification_data['name']); ?></h3>
                        <p><strong>Roll Number:</strong> <?php echo htmlspecialchars($verification_data['roll_no']); ?></p>
                        <p><strong>Class:</strong> <?php echo htmlspecialchars($verification_data['class_name'] . ' - ' . $verification_data['section']); ?></p>
                        <p><strong>Exam:</strong> <?php echo htmlspecialchars($verification_data['exam_name'] . ' (' . $verification_data['exam_type'] . ')'); ?></p>
                        <p><strong>Exam Date:</strong> <?php echo date('F j, Y', strtotime($verification_data['exam_date'])); ?></p>
                    </div>
                </div>
                
                <!-- Results Summary -->
                <div class="summary-cards">
                    <div class="summary-card">
                        <h3><?php echo $total_obtained; ?>/<?php echo $total_possible; ?></h3>
                        <p>Total Marks</p>
                    </div>
                    <div class="summary-card">
                        <h3><?php echo $percentage; ?>%</h3>
                        <p>Percentage</p>
                    </div>
                    <div class="summary-card">
                        <h3><?php echo $grade; ?></h3>
                        <p>Grade</p>
                    </div>
                    <div class="summary-card">
                        <h3 style="color: <?php echo $percentage >= 33 ? '#28a745' : '#dc3545'; ?>">
                            <?php echo $percentage >= 33 ? 'PASS' : 'FAIL'; ?>
                        </h3>
                        <p>Result</p>
                    </div>
                </div>
                
                <!-- Detailed Results -->
                <h3 style="color: #2c3e50; margin: 30px 0 15px 0;">Subject-wise Results</h3>
                <table class="results-table">
                    <thead>
                        <tr>
                            <th>Subject</th>
                            <th>Marks Obtained</th>
                            <th>Total Marks</th>
                            <th>Percentage</th>
                            <th>Grade</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($results_data as $result): ?>
                            <?php 
                            $subject_percentage = round(($result['marks_obtained'] / $result['total_marks']) * 100, 1);
                            if ($subject_percentage >= 90) $subject_grade = 'A+';
                            elseif ($subject_percentage >= 80) $subject_grade = 'A';
                            elseif ($subject_percentage >= 70) $subject_grade = 'B+';
                            elseif ($subject_percentage >= 60) $subject_grade = 'B';
                            elseif ($subject_percentage >= 50) $subject_grade = 'C+';
                            elseif ($subject_percentage >= 40) $subject_grade = 'C';
                            elseif ($subject_percentage >= 33) $subject_grade = 'D';
                            else $subject_grade = 'F';
                            ?>
                            <tr>
                                <td><?php echo htmlspecialchars($result['subject_name']); ?></td>
                                <td><?php echo $result['marks_obtained']; ?></td>
                                <td><?php echo $result['total_marks']; ?></td>
                                <td><?php echo $subject_percentage; ?>%</td>
                                <td><?php echo $subject_grade; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <!-- Verification Info -->
                <div style="background: #e8f5e8; padding: 20px; border-radius: 10px; margin-top: 30px; text-align: center;">
                    <h4 style="color: #28a745; margin: 0 0 10px 0;">
                        <i class="fas fa-shield-alt"></i> Verification Details
                    </h4>
                    <p style="margin: 5px 0; color: #155724;">
                        <strong>Verification Code:</strong> <?php echo htmlspecialchars($verification_code); ?>
                    </p>
                    <p style="margin: 5px 0; color: #155724;">
                        <strong>Generated On:</strong> <?php echo date('F j, Y g:i A', strtotime($verification_data['generated_at'])); ?>
                    </p>
                    <p style="margin: 5px 0; color: #155724;">
                        <strong>Valid Until:</strong> <?php echo date('F j, Y', strtotime($verification_data['expires_at'])); ?>
                    </p>
                </div>
                
                <div style="text-align: center; margin-top: 30px;">
                    <a href="index.php" class="btn btn-primary">
                        <i class="fas fa-home"></i> Back to Home
                    </a>
                    <button onclick="window.print()" class="btn btn-secondary">
                        <i class="fas fa-print"></i> Print Result
                    </button>
                </div>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
