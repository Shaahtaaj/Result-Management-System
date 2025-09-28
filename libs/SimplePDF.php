<?php
/**
 * Simple PDF Generator for Result Cards
 * Uses multiple approaches for PDF generation
 */

class SimplePDF {
    
    public static function generateResultCard($student_data, $exam_data, $results_data, $school_settings, $verification_code, $output_type = 'pdf') {
        // Calculate totals and percentage
        $total_marks_obtained = 0;
        $total_marks_possible = 0;
        
        foreach ($results_data as $result) {
            $total_marks_obtained += $result['marks_obtained'];
            $total_marks_possible += $result['total_marks'];
        }
        
        $percentage = $total_marks_possible > 0 ? round(($total_marks_obtained / $total_marks_possible) * 100, 2) : 0;
        
        // Determine grade
        $grade = self::calculateGrade($percentage);
        
        // Generate QR code URL for verification
        $verification_url = "http://" . $_SERVER['HTTP_HOST'] . "/ResultManagementSystem/verify.php?code=" . $verification_code;
        
        if ($output_type === 'pdf') {
            return self::generatePDF($student_data, $exam_data, $results_data, $school_settings, $total_marks_obtained, $total_marks_possible, $percentage, $grade, $verification_url);
        } else {
            // Create HTML content
            $html = self::generateHTML($student_data, $exam_data, $results_data, $school_settings, $total_marks_obtained, $total_marks_possible, $percentage, $grade, $verification_url);
            return $html;
        }
    }
    
    private static function calculateGrade($percentage) {
        if ($percentage >= 90) return 'A+';
        if ($percentage >= 80) return 'A';
        if ($percentage >= 70) return 'B+';
        if ($percentage >= 60) return 'B';
        if ($percentage >= 50) return 'C+';
        if ($percentage >= 40) return 'C';
        if ($percentage >= 33) return 'D';
        return 'F';
    }
    
    private static function generateHTML($student_data, $exam_data, $results_data, $school_settings, $total_obtained, $total_possible, $percentage, $grade, $verification_url) {
        include_once '../libs/OfflineQR.php';
        include_once '../libs/SimpleQR.php';
        $qr_data_url = SimpleQR::getQRDataURL($verification_url, 150);
        
        $html = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>Result Card - ' . htmlspecialchars($student_data['name']) . '</title>
            <style>
                body { 
                    font-family: Arial, sans-serif; 
                    margin: 0; 
                    padding: 20px; 
                    background: white;
                    color: #333;
                }
                .result-card {
                    max-width: 800px;
                    margin: 0 auto;
                    border: 2px solid #333;
                    padding: 20px;
                    background: white;
                }
                .header {
                    text-align: center;
                    border-bottom: 2px solid #333;
                    padding-bottom: 20px;
                    margin-bottom: 20px;
                }
                .school-logo {
                    width: 80px;
                    height: 80px;
                    border-radius: 50%;
                    margin-bottom: 10px;
                }
                .school-name {
                    font-size: 24px;
                    font-weight: bold;
                    margin: 10px 0;
                    color: #2c3e50;
                }
                .school-motto {
                    font-style: italic;
                    color: #7f8c8d;
                    margin-bottom: 10px;
                }
                .result-title {
                    font-size: 20px;
                    font-weight: bold;
                    margin: 15px 0;
                    color: #e74c3c;
                }
                .student-info {
                    display: table;
                    width: 100%;
                    margin-bottom: 20px;
                }
                .student-details {
                    display: table-cell;
                    width: 70%;
                    vertical-align: top;
                }
                .student-photo {
                    display: table-cell;
                    width: 30%;
                    text-align: right;
                    vertical-align: top;
                }
                .student-photo img {
                    width: 120px;
                    height: 120px;
                    border: 2px solid #333;
                    border-radius: 10px;
                    object-fit: cover;
                }
                .info-row {
                    margin: 8px 0;
                    font-size: 14px;
                }
                .info-label {
                    font-weight: bold;
                    display: inline-block;
                    width: 120px;
                }
                .marks-table {
                    width: 100%;
                    border-collapse: collapse;
                    margin: 20px 0;
                }
                .marks-table th,
                .marks-table td {
                    border: 1px solid #333;
                    padding: 8px;
                    text-align: center;
                }
                .marks-table th {
                    background: #f8f9fa;
                    font-weight: bold;
                }
                .summary {
                    display: table;
                    width: 100%;
                    margin: 20px 0;
                }
                .summary-left {
                    display: table-cell;
                    width: 60%;
                    vertical-align: top;
                }
                .summary-right {
                    display: table-cell;
                    width: 40%;
                    text-align: center;
                    vertical-align: top;
                }
                .grade-box {
                    border: 2px solid #333;
                    padding: 20px;
                    margin: 10px;
                    background: #f8f9fa;
                }
                .grade {
                    font-size: 36px;
                    font-weight: bold;
                    color: #e74c3c;
                }
                .percentage {
                    font-size: 24px;
                    font-weight: bold;
                    color: #27ae60;
                }
                .footer {
                    margin-top: 30px;
                    display: table;
                    width: 100%;
                }
                .signature-section {
                    display: table-cell;
                    width: 50%;
                    text-align: center;
                    vertical-align: bottom;
                }
                .qr-section {
                    display: table-cell;
                    width: 50%;
                    text-align: center;
                    vertical-align: top;
                }
                .signature-line {
                    border-top: 1px solid #333;
                    margin-top: 50px;
                    padding-top: 5px;
                    font-size: 12px;
                }
                .qr-code {
                    width: 100px;
                    height: 100px;
                }
                .verification-text {
                    font-size: 10px;
                    margin-top: 5px;
                }
                @media print {
                    body { margin: 0; padding: 10px; }
                    .result-card { border: 2px solid #000; }
                }
            </style>
        </head>
        <body>
            <div class="result-card">
                <div class="header">
                    ' . ($school_settings['logo'] ? '<img src="../uploads/school/' . $school_settings['logo'] . '" alt="School Logo" class="school-logo">' : '') . '
                    <div class="school-name">' . htmlspecialchars($school_settings['school_name']) . '</div>
                    <div class="school-motto">' . htmlspecialchars($school_settings['motto']) . '</div>
                    <div>' . htmlspecialchars($school_settings['address']) . '</div>
                    <div class="result-title">ACADEMIC RESULT CARD</div>
                    <div><strong>' . htmlspecialchars($exam_data['exam_name']) . ' - ' . htmlspecialchars($exam_data['exam_type']) . '</strong></div>
                    <div>Academic Year: ' . htmlspecialchars($school_settings['academic_year']) . '</div>
                </div>
                
                <div class="student-info">
                    <div class="student-details">
                        <div class="info-row">
                            <span class="info-label">Student Name:</span>
                            <strong>' . htmlspecialchars($student_data['name']) . '</strong>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Roll Number:</span>
                            <strong>' . htmlspecialchars($student_data['roll_no']) . '</strong>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Class:</span>
                            <strong>' . htmlspecialchars($student_data['class_name'] . ' - ' . $student_data['section']) . '</strong>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Date of Birth:</span>
                            ' . ($student_data['date_of_birth'] ? date('d-m-Y', strtotime($student_data['date_of_birth'])) : 'Not Available') . '
                        </div>
                        <div class="info-row">
                            <span class="info-label">Father\'s Name:</span>
                            ' . htmlspecialchars($student_data['parent_name'] ?: 'Not Available') . '
                        </div>
                        <div class="info-row">
                            <span class="info-label">Exam Date:</span>
                            ' . date('d-m-Y', strtotime($exam_data['exam_date'])) . '
                        </div>
                    </div>
                    <div class="student-photo">
                        ' . ($student_data['photo'] ? '<img src="../uploads/students/' . $student_data['photo'] . '" alt="Student Photo">' : '<div style="width:120px;height:120px;border:2px solid #333;display:flex;align-items:center;justify-content:center;background:#f8f9fa;"><i class="fas fa-user" style="font-size:40px;color:#667eea;"></i></div>') . '
                    </div>
                </div>
                
                <table class="marks-table">
                    <thead>
                        <tr>
                            <th>S.No.</th>
                            <th>Subject</th>
                            <th>Marks Obtained</th>
                            <th>Total Marks</th>
                            <th>Percentage</th>
                            <th>Grade</th>
                        </tr>
                    </thead>
                    <tbody>';
        
        $sno = 1;
        foreach ($results_data as $result) {
            $subject_percentage = round(($result['marks_obtained'] / $result['total_marks']) * 100, 1);
            $subject_grade = self::calculateGrade($subject_percentage);
            
            $html .= '
                        <tr>
                            <td>' . $sno++ . '</td>
                            <td>' . htmlspecialchars($result['subject_name']) . '</td>
                            <td>' . $result['marks_obtained'] . '</td>
                            <td>' . $result['total_marks'] . '</td>
                            <td>' . $subject_percentage . '%</td>
                            <td>' . $subject_grade . '</td>
                        </tr>';
        }
        
        $html .= '
                        <tr style="background: #f8f9fa; font-weight: bold;">
                            <td colspan="2">TOTAL</td>
                            <td>' . $total_obtained . '</td>
                            <td>' . $total_possible . '</td>
                            <td>' . $percentage . '%</td>
                            <td>' . $grade . '</td>
                        </tr>
                    </tbody>
                </table>
                
                <div class="summary">
                    <div class="summary-left">
                        <div class="info-row">
                            <span class="info-label">Total Marks:</span>
                            <strong>' . $total_obtained . ' / ' . $total_possible . '</strong>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Percentage:</span>
                            <strong class="percentage">' . $percentage . '%</strong>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Grade:</span>
                            <strong class="grade" style="font-size: 18px;">' . $grade . '</strong>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Result:</span>
                            <strong style="color: ' . ($percentage >= 33 ? '#27ae60' : '#e74c3c') . ';">' . ($percentage >= 33 ? 'PASS' : 'FAIL') . '</strong>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Attendance:</span>
                            <strong>85%</strong> (Sample)
                        </div>
                    </div>
                    <div class="summary-right">
                        <div class="grade-box">
                            <div>OVERALL GRADE</div>
                            <div class="grade">' . $grade . '</div>
                            <div class="percentage">' . $percentage . '%</div>
                        </div>
                    </div>
                </div>
                
                <div class="footer">
                    <div class="signature-section">
                        ' . ($school_settings['principal_signature'] ? '<img src="../uploads/school/' . $school_settings['principal_signature'] . '" alt="Principal Signature" style="width: 150px; height: 75px;">' : '') . '
                        <div class="signature-line">
                            Principal<br>
                            ' . htmlspecialchars($school_settings['principal_name'] ?: $school_settings['school_name']) . '
                        </div>
                    </div>
                    <div class="qr-section">
                        ' . ($qr_data_url ? '<img src="' . $qr_data_url . '" alt="QR Code" class="qr-code">' : '') . '
                        <div class="verification-text">
                            Scan QR code to verify result online<br>
                            Generated on: ' . date('d-m-Y H:i:s') . '
                        </div>
                    </div>
                </div>
            </div>
        </body>
        </html>';
        
        return $html;
    }
    
    private static function generatePDF($student_data, $exam_data, $results_data, $school_settings, $total_obtained, $total_possible, $percentage, $grade, $verification_url) {
        // Get HTML content first
        $html = self::generateHTML($student_data, $exam_data, $results_data, $school_settings, $total_obtained, $total_possible, $percentage, $grade, $verification_url);
        
        // Try to use DomPDF if available, otherwise use wkhtmltopdf, otherwise fallback to browser print
        if (class_exists('Dompdf\Dompdf')) {
            return self::generateWithDomPDF($html, $student_data, $exam_data);
        } else {
            // Use a simple approach with proper headers for PDF generation
            return self::generateWithBrowserPrint($html, $student_data, $exam_data);
        }
    }
    
    private static function generateWithBrowserPrint($html, $student_data, $exam_data) {
        // Create a version optimized for browser PDF generation
        $pdf_html = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>Result Card - ' . htmlspecialchars($student_data['name']) . '</title>
            <style>
                @page {
                    size: A4;
                    margin: 15mm;
                }
                
                body { 
                    font-family: "Times New Roman", serif; 
                    margin: 0; 
                    padding: 0; 
                    background: white;
                    color: #000;
                    font-size: 12pt;
                    line-height: 1.4;
                }
                
                .result-card {
                    width: 100%;
                    border: 2px solid #000;
                    padding: 15px;
                    background: white;
                    page-break-inside: avoid;
                }
                
                .header {
                    text-align: center;
                    border-bottom: 2px solid #000;
                    padding-bottom: 15px;
                    margin-bottom: 15px;
                }
                
                .school-logo {
                    width: 60px;
                    height: 60px;
                    border-radius: 50%;
                    margin-bottom: 8px;
                }
                
                .school-name {
                    font-size: 18pt;
                    font-weight: bold;
                    margin: 8px 0;
                    color: #000;
                }
                
                .school-motto {
                    font-style: italic;
                    color: #333;
                    margin-bottom: 8px;
                    font-size: 10pt;
                }
                
                .result-title {
                    font-size: 16pt;
                    font-weight: bold;
                    margin: 10px 0;
                    color: #000;
                    text-decoration: underline;
                }
                
                .student-info {
                    width: 100%;
                    margin-bottom: 15px;
                }
                
                .student-details {
                    float: left;
                    width: 65%;
                }
                
                .student-photo {
                    float: right;
                    width: 30%;
                    text-align: right;
                }
                
                .student-photo img {
                    width: 100px;
                    height: 100px;
                    border: 2px solid #000;
                    border-radius: 8px;
                }
                
                .info-row {
                    margin: 6px 0;
                    font-size: 11pt;
                }
                
                .info-label {
                    font-weight: bold;
                    display: inline-block;
                    width: 110px;
                }
                
                .marks-table {
                    width: 100%;
                    border-collapse: collapse;
                    margin: 15px 0;
                    font-size: 10pt;
                }
                
                .marks-table th,
                .marks-table td {
                    border: 1px solid #000;
                    padding: 6px;
                    text-align: center;
                }
                
                .marks-table th {
                    background: #f0f0f0;
                    font-weight: bold;
                }
                
                .summary {
                    width: 100%;
                    margin: 15px 0;
                }
                
                .summary-left {
                    float: left;
                    width: 55%;
                }
                
                .summary-right {
                    float: right;
                    width: 40%;
                    text-align: center;
                }
                
                .grade-box {
                    border: 2px solid #000;
                    padding: 15px;
                    margin: 8px;
                    background: #f8f8f8;
                    text-align: center;
                }
                
                .grade {
                    font-size: 24pt;
                    font-weight: bold;
                    color: #000;
                }
                
                .percentage {
                    font-size: 18pt;
                    font-weight: bold;
                    color: #000;
                }
                
                .footer {
                    margin-top: 25px;
                    width: 100%;
                    clear: both;
                }
                
                .signature-section {
                    float: left;
                    width: 45%;
                    text-align: center;
                }
                
                .qr-section {
                    float: right;
                    width: 45%;
                    text-align: center;
                }
                
                .signature-line {
                    border-top: 1px solid #000;
                    margin-top: 40px;
                    padding-top: 5px;
                    font-size: 10pt;
                }
                
                .qr-code {
                    width: 80px;
                    height: 80px;
                }
                
                .verification-text {
                    font-size: 8pt;
                    margin-top: 5px;
                }
                
                .clearfix::after {
                    content: "";
                    display: table;
                    clear: both;
                }
                
                @media print {
                    body { 
                        margin: 0; 
                        padding: 0; 
                        -webkit-print-color-adjust: exact;
                        color-adjust: exact;
                    }
                    .result-card { 
                        border: 2px solid #000; 
                        page-break-inside: avoid;
                    }
                }
            </style>
            <script>
                window.onload = function() {
                    // Auto-trigger print dialog for PDF generation
                    if (window.location.search.includes("auto_print=1")) {
                        setTimeout(function() {
                            window.print();
                        }, 1000);
                    }
                }
            </script>
        </head>
        <body>';
        
        // Extract body content from original HTML and modify it for PDF
        $body_start = strpos($html, '<body>') + 6;
        $body_end = strpos($html, '</body>');
        $body_content = substr($html, $body_start, $body_end - $body_start);
        
        // Add clearfix classes for proper floating
        $body_content = str_replace('<div class="student-info">', '<div class="student-info clearfix">', $body_content);
        $body_content = str_replace('<div class="summary">', '<div class="summary clearfix">', $body_content);
        $body_content = str_replace('<div class="footer">', '<div class="footer clearfix">', $body_content);
        
        $pdf_html .= $body_content;
        $pdf_html .= '</body></html>';
        
        return $pdf_html;
    }
    
    private static function generateWithDomPDF($html, $student_data, $exam_data) {
        // This would be used if DomPDF is available
        // For now, return the browser-optimized version
        return self::generateWithBrowserPrint($html, $student_data, $exam_data);
    }
}
?>
