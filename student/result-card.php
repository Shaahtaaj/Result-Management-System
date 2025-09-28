<?php
session_start();
include '../config/database.php';
include '../libs/SimplePDF.php';
include '../libs/PDFGenerator.php';
check_login('student');

$exam_id = isset($_GET['exam_id']) ? (int)$_GET['exam_id'] : 0;
$download = isset($_GET['download']) ? true : false;

if ($exam_id <= 0) {
    header("Location: dashboard.php");
    exit();
}

// Get student information
$student_query = "
    SELECT s.*, c.class_name, c.section 
    FROM students s 
    JOIN classes c ON s.class_id = c.id 
    WHERE s.user_id = ?
";
$student_stmt = $conn->prepare($student_query);
$student_stmt->bind_param("i", $_SESSION['user_id']);
$student_stmt->execute();
$student_result = $student_stmt->get_result();
$student_data = $student_result->fetch_assoc();

// Get exam information
$exam_query = "SELECT * FROM exams WHERE id = ?";
$exam_stmt = $conn->prepare($exam_query);
$exam_stmt->bind_param("i", $exam_id);
$exam_stmt->execute();
$exam_result = $exam_stmt->get_result();
$exam_data = $exam_result->fetch_assoc();

if (!$exam_data) {
    header("Location: dashboard.php");
    exit();
}

// Get results for this student and exam
$results_query = "
    SELECT r.*, s.subject_name
    FROM results r
    JOIN subjects s ON r.subject_id = s.id
    WHERE r.student_id = ? AND r.exam_id = ?
    ORDER BY s.subject_name
";
$results_stmt = $conn->prepare($results_query);
$results_stmt->bind_param("ii", $student_data['id'], $exam_id);
$results_stmt->execute();
$results_result = $results_stmt->get_result();

$results_data = [];
while ($row = $results_result->fetch_assoc()) {
    $results_data[] = $row;
}

if (empty($results_data)) {
    header("Location: dashboard.php");
    exit();
}

// Get school settings
$school_settings = get_school_settings();

// Generate or get verification code
$verification_query = "SELECT verification_code FROM result_verifications WHERE student_id = ? AND exam_id = ?";
$verification_stmt = $conn->prepare($verification_query);
$verification_stmt->bind_param("ii", $student_data['id'], $exam_id);
$verification_stmt->execute();
$verification_result = $verification_stmt->get_result();

if ($verification_result->num_rows > 0) {
    $verification_code = $verification_result->fetch_assoc()['verification_code'];
} else {
    // Generate new verification code
    $verification_code = 'VER' . $student_data['id'] . $exam_id . time();
    
    $insert_verification = "INSERT INTO result_verifications (verification_code, student_id, exam_id, expires_at) VALUES (?, ?, ?, DATE_ADD(NOW(), INTERVAL 1 YEAR))";
    $insert_stmt = $conn->prepare($insert_verification);
    $insert_stmt->bind_param("sii", $verification_code, $student_data['id'], $exam_id);
    $insert_stmt->execute();
}

if ($download) {
    // Generate PDF-optimized HTML content
    $html_content = SimplePDF::generateResultCard($student_data, $exam_data, $results_data, $school_settings, $verification_code, 'pdf');
    
    // Generate filename
    $filename = 'Result_Card_' . $student_data['roll_no'] . '_' . str_replace(' ', '_', $exam_data['exam_name']) . '.pdf';
    
    // Try to generate actual PDF
    if (PDFGenerator::generateFromHTML($html_content, $filename)) {
        // PDF was generated and sent
        exit();
    } else {
        // Fallback to browser-based PDF generation
        echo '<!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>Generate PDF - Result Card</title>
            <style>
                body { font-family: Arial, sans-serif; margin: 40px; background: #f5f5f5; }
                .instructions { 
                    background: white; 
                    padding: 30px; 
                    border-radius: 10px; 
                    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
                    max-width: 600px;
                    margin: 0 auto;
                }
                .btn { 
                    display: inline-block;
                    padding: 12px 24px; 
                    background: #667eea; 
                    color: white; 
                    text-decoration: none; 
                    border-radius: 5px; 
                    margin: 10px 5px;
                    font-weight: bold;
                }
                .btn:hover { background: #5a67d8; }
                .btn-success { background: #28a745; }
                .btn-success:hover { background: #218838; }
                .steps { 
                    background: #f8f9fa; 
                    padding: 20px; 
                    border-radius: 5px; 
                    margin: 20px 0;
                    border-left: 4px solid #667eea;
                }
                .steps ol { margin: 0; padding-left: 20px; }
                .steps li { margin: 8px 0; }
            </style>
        </head>
        <body>
            <div class="instructions">
                <h2>📄 Generate PDF Result Card</h2>
                <p><strong>Student:</strong> ' . htmlspecialchars($student_data['name']) . ' (' . htmlspecialchars($student_data['roll_no']) . ')</p>
                <p><strong>Exam:</strong> ' . htmlspecialchars($exam_data['exam_name']) . '</p>
                
                <div class="steps">
                    <h3>📋 How to Generate PDF:</h3>
                    <ol>
                        <li>Click "Open Print-Ready Version" below</li>
                        <li>In the new window, press <strong>Ctrl+P</strong> (Windows) or <strong>Cmd+P</strong> (Mac)</li>
                        <li>Select "Save as PDF" as destination</li>
                        <li>Click "Save" to download your PDF</li>
                    </ol>
                </div>
                
                <div style="text-align: center; margin-top: 30px;">
                    <a href="result-card.php?exam_id=' . $exam_id . '&pdf_view=1" target="_blank" class="btn btn-success">
                        🖨️ Open Print-Ready Version
                    </a>
                    <a href="results.php" class="btn">
                        ← Back to Results
                    </a>
                </div>
                
                <div style="margin-top: 30px; padding: 15px; background: #e3f2fd; border-radius: 5px; font-size: 14px;">
                    <strong>💡 Tip:</strong> The print-ready version is optimized for PDF generation with proper formatting, fonts, and layout.
                </div>
            </div>
        </body>
        </html>';
        exit();
    }
} else if (isset($_GET['pdf_view'])) {
    // Generate PDF-optimized content for printing
    $pdf_content = SimplePDF::generateResultCard($student_data, $exam_data, $results_data, $school_settings, $verification_code, 'pdf');
    echo $pdf_content;
} else {
    // Display regular HTML version in browser
    $html_content = SimplePDF::generateResultCard($student_data, $exam_data, $results_data, $school_settings, $verification_code, 'html');
    echo $html_content;
}
?>
