<?php
/**
 * Debug wkhtmltopdf Installation
 */

echo '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>wkhtmltopdf Debug</title>
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
        .warning { background: #fff3cd; border-color: #ffc107; color: #856404; }
        .code { 
            background: #f8f9fa; 
            padding: 10px; 
            border-radius: 3px; 
            font-family: monospace;
            border: 1px solid #ddd;
            margin: 10px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 wkhtmltopdf Debug Information</h1>';

// Test 1: Check PATH environment variable
echo '<h2>1. System PATH Check</h2>';
$path = getenv('PATH');
echo '<div class="code">PATH = ' . htmlspecialchars($path) . '</div>';

// Test 2: Try different command variations
echo '<h2>2. Command Tests</h2>';

$commands = [
    'wkhtmltopdf --version',
    'wkhtmltopdf.exe --version',
    '"C:\\Program Files\\wkhtmltopdf\\bin\\wkhtmltopdf.exe" --version',
    '"C:\\Program Files (x86)\\wkhtmltopdf\\bin\\wkhtmltopdf.exe" --version'
];

foreach ($commands as $cmd) {
    echo '<h3>Testing: <code>' . htmlspecialchars($cmd) . '</code></h3>';
    
    $output = [];
    $return_var = 0;
    exec($cmd . ' 2>&1', $output, $return_var);
    
    if ($return_var === 0) {
        echo '<div class="status success">
            ✅ <strong>SUCCESS!</strong><br>
            Output: ' . htmlspecialchars(implode(' ', $output)) . '
        </div>';
        $working_command = $cmd;
        break;
    } else {
        echo '<div class="status error">
            ❌ <strong>Failed</strong> (Return code: ' . $return_var . ')<br>
            Output: ' . htmlspecialchars(implode(' ', $output)) . '
        </div>';
    }
}

// Test 3: Check common installation paths
echo '<h2>3. Common Installation Paths</h2>';

$common_paths = [
    'C:\\Program Files\\wkhtmltopdf\\bin\\wkhtmltopdf.exe',
    'C:\\Program Files (x86)\\wkhtmltopdf\\bin\\wkhtmltopdf.exe',
    'C:\\wkhtmltopdf\\bin\\wkhtmltopdf.exe',
    'C:\\xampp\\wkhtmltopdf\\wkhtmltopdf.exe'
];

foreach ($common_paths as $path) {
    if (file_exists($path)) {
        echo '<div class="status success">
            ✅ <strong>Found:</strong> ' . htmlspecialchars($path) . '
        </div>';
        $found_path = $path;
    } else {
        echo '<div class="status error">
            ❌ <strong>Not found:</strong> ' . htmlspecialchars($path) . '
        </div>';
    }
}

// Test 4: PHP exec() function test
echo '<h2>4. PHP exec() Function Test</h2>';

if (function_exists('exec')) {
    echo '<div class="status success">✅ exec() function is available</div>';
    
    // Test with a simple command
    $output = [];
    exec('echo "PHP exec test" 2>&1', $output, $return_var);
    
    if ($return_var === 0) {
        echo '<div class="status success">✅ exec() is working: ' . implode(' ', $output) . '</div>';
    } else {
        echo '<div class="status error">❌ exec() failed</div>';
    }
} else {
    echo '<div class="status error">❌ exec() function is disabled</div>';
}

// Test 5: System info
echo '<h2>5. System Information</h2>';
echo '<div class="code">';
echo 'PHP Version: ' . PHP_VERSION . '<br>';
echo 'Operating System: ' . PHP_OS . '<br>';
echo 'Server Software: ' . ($_SERVER['SERVER_SOFTWARE'] ?? 'Unknown') . '<br>';
echo 'PHP SAPI: ' . php_sapi_name() . '<br>';
echo '</div>';

// Solutions section
echo '<h2>🔧 Troubleshooting Solutions</h2>';

if (isset($working_command)) {
    echo '<div class="status success">
        <h3>✅ Solution Found!</h3>
        <p>wkhtmltopdf is working with command: <code>' . htmlspecialchars($working_command) . '</code></p>
        <p>I can update the PDFGenerator to use this specific path.</p>
    </div>';
} else if (isset($found_path)) {
    echo '<div class="status warning">
        <h3>⚠️ Found but not in PATH</h3>
        <p>wkhtmltopdf found at: <code>' . htmlspecialchars($found_path) . '</code></p>
        <p>But it\'s not accessible via PATH. Solutions:</p>
        <ol>
            <li><strong>Add to PATH:</strong> Add the directory to your system PATH</li>
            <li><strong>Use full path:</strong> I can update the code to use the full path</li>
        </ol>
    </div>';
} else {
    echo '<div class="status error">
        <h3>❌ wkhtmltopdf Not Found</h3>
        <p>wkhtmltopdf doesn\'t appear to be installed. Please:</p>
        <ol>
            <li><strong>Download:</strong> <a href="https://wkhtmltopdf.org/downloads.html" target="_blank">https://wkhtmltopdf.org/downloads.html</a></li>
            <li><strong>Install:</strong> Run the installer as Administrator</li>
            <li><strong>Restart:</strong> Restart XAMPP/Apache after installation</li>
            <li><strong>Test again:</strong> Refresh this page</li>
        </ol>
    </div>';
}

echo '<div class="status info">
    <h3>💡 Alternative Solutions</h3>
    <p>Even without wkhtmltopdf, the PDF system will work using:</p>
    <ul>
        <li><strong>Browser-based PDF:</strong> Users can print to PDF (Ctrl+P)</li>
        <li><strong>DomPDF:</strong> Install via Composer for automatic PDFs</li>
        <li><strong>mPDF:</strong> Another Composer option</li>
    </ul>
    <p>The system is designed to work with or without wkhtmltopdf!</p>
</div>';

echo '    </div>
</body>
</html>';
?>
