<?php
/**
 * Test PDF Generation with wkhtmltopdf
 */

include 'libs/PDFGenerator.php';

echo '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>PDF Generation Test</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; background: #f5f5f5; }
        .container { 
            background: white; 
            padding: 30px; 
            border-radius: 10px; 
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            max-width: 800px;
            margin: 0 auto;
        }
        .status { 
            padding: 15px; 
            border-radius: 5px; 
            margin: 15px 0;
            border-left: 4px solid;
        }
        .success { background: #d4edda; border-color: #28a745; color: #155724; }
        .error { background: #f8d7da; border-color: #dc3545; color: #721c24; }
        .info { background: #d1ecf1; border-color: #17a2b8; color: #0c5460; }
        .btn { 
            display: inline-block;
            padding: 10px 20px; 
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
        .test-content {
            border: 2px solid #333;
            padding: 20px;
            margin: 20px 0;
            background: #f8f9fa;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🧪 PDF Generation Test</h1>';

// Test 1: Check if wkhtmltopdf is available using our updated method
echo '<h2>Test 1: wkhtmltopdf Availability (Updated)</h2>';

// Test the full path method
$output = [];
$return_var = 0;
exec('"C:\\Program Files\\wkhtmltopdf\\bin\\wkhtmltopdf.exe" --version 2>&1', $output, $return_var);

if ($return_var === 0) {
    echo '<div class="status success">
        ✅ <strong>wkhtmltopdf is available with full path!</strong><br>
        Version: ' . implode(' ', $output) . '<br>
        Path: C:\\Program Files\\wkhtmltopdf\\bin\\wkhtmltopdf.exe
    </div>';
    $wkhtmltopdf_available = true;
} else {
    echo '<div class="status error">
        ❌ <strong>wkhtmltopdf still not working</strong><br>
        Please check the installation.
    </div>';
    $wkhtmltopdf_available = false;
}

// Test 2: Test PDF generation
echo '<h2>Test 2: PDF Generation Test</h2>';

if (isset($_GET['test_pdf']) && $wkhtmltopdf_available) {
    // Generate a test PDF
    $test_html = '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>Test PDF Document</title>
        <style>
            body { font-family: Arial, sans-serif; margin: 20px; }
            .header { text-align: center; border-bottom: 2px solid #333; padding-bottom: 20px; }
            .content { margin: 20px 0; }
            table { width: 100%; border-collapse: collapse; margin: 20px 0; }
            th, td { border: 1px solid #333; padding: 8px; text-align: center; }
            th { background: #f0f0f0; }
        </style>
    </head>
    <body>
        <div class="header">
            <h1>Test PDF Document</h1>
            <p>Generated on: ' . date('F j, Y \a\t g:i A') . '</p>
        </div>
        
        <div class="content">
            <h2>Sample Content</h2>
            <p>This is a test PDF generated using wkhtmltopdf.</p>
            
            <table>
                <thead>
                    <tr>
                        <th>Item</th>
                        <th>Description</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>PDF Generation</td>
                        <td>wkhtmltopdf Integration</td>
                        <td>✅ Working</td>
                    </tr>
                    <tr>
                        <td>Result Cards</td>
                        <td>Student Result PDF</td>
                        <td>✅ Ready</td>
                    </tr>
                </tbody>
            </table>
            
            <p><strong>System Information:</strong></p>
            <ul>
                <li>PHP Version: ' . PHP_VERSION . '</li>
                <li>Server: ' . $_SERVER['SERVER_SOFTWARE'] . '</li>
                <li>Generated: ' . date('Y-m-d H:i:s') . '</li>
            </ul>
        </div>
    </body>
    </html>';
    
    // Try to generate PDF
    if (PDFGenerator::generateFromHTML($test_html, 'test_document.pdf')) {
        // PDF generation was successful and file was sent
        exit();
    } else {
        echo '<div class="status error">
            ❌ <strong>PDF generation failed</strong><br>
            There was an error generating the PDF file.
        </div>';
    }
} else {
    if ($wkhtmltopdf_available) {
        echo '<div class="status info">
            ℹ️ <strong>Ready to test PDF generation</strong><br>
            Click the button below to generate a test PDF.
        </div>
        
        <div style="text-align: center;">
            <a href="?test_pdf=1" class="btn btn-success">
                📄 Generate Test PDF
            </a>
        </div>';
    } else {
        echo '<div class="status error">
            ❌ <strong>Cannot test PDF generation</strong><br>
            wkhtmltopdf must be available first.
        </div>';
    }
}

// Test 3: Result Card PDF Test
echo '<h2>Test 3: Result Card PDF</h2>';

if ($wkhtmltopdf_available) {
    echo '<div class="status success">
        ✅ <strong>Result Card PDF is ready!</strong><br>
        Students can now download actual PDF files instead of HTML.
    </div>
    
    <div style="text-align: center;">
        <a href="student/login.php" class="btn">
            👨‍🎓 Test Student Login
        </a>
        <a href="admin/login.php" class="btn">
            👨‍💼 Test Admin Login
        </a>
    </div>';
} else {
    echo '<div class="status error">
        ❌ <strong>Result Card PDF not available</strong><br>
        Will fallback to browser-based PDF generation.
    </div>';
}

echo '
        <div class="test-content">
            <h3>📋 Next Steps:</h3>
            <ol>
                <li><strong>Test PDF Generation:</strong> Click "Generate Test PDF" above</li>
                <li><strong>Test Result Cards:</strong> Login as student and download a result card</li>
                <li><strong>Verify Quality:</strong> Check that PDFs have proper formatting</li>
            </ol>
            
            <h3>🔧 Troubleshooting:</h3>
            <ul>
                <li>If wkhtmltopdf not found: Restart Apache/XAMPP after PATH changes</li>
                <li>If PDF generation fails: Check file permissions in temp directory</li>
                <li>If layout issues: The system will auto-optimize for PDF output</li>
            </ul>
        </div>
        
        <div style="text-align: center; margin-top: 30px;">
            <a href="index.php" class="btn">
                🏠 Back to Home
            </a>
        </div>
    </div>
</body>
</html>';
?>
