<?php
// Test QR Code Generation
include 'libs/OfflineQR.php';
include 'libs/SimpleQR.php';

$test_url = "http://localhost/ResultManagementSystem/verify.php?code=VER123456789";

echo "<h2>QR Code Generation Test</h2>";

// Check PHP extensions
echo "<h3>System Status</h3>";
echo "<div class='status-box'>";
echo "<p><strong>PHP Version:</strong> " . PHP_VERSION . "</p>";
echo "<p><strong>GD Extension:</strong> " . (extension_loaded('gd') ? "✅ Enabled" : "❌ Disabled") . "</p>";
echo "<p><strong>cURL Extension:</strong> " . (extension_loaded('curl') ? "✅ Enabled" : "❌ Disabled") . "</p>";
echo "<p><strong>Allow URL fopen:</strong> " . (ini_get('allow_url_fopen') ? "✅ Enabled" : "❌ Disabled") . "</p>";
echo "</div>";

if (!extension_loaded('gd')) {
    echo "<div class='warning-box'>";
    echo "<h4>⚠️ GD Extension Not Enabled</h4>";
    echo "<p>To enable high-quality QR codes, please enable the GD extension:</p>";
    echo "<ol>";
    echo "<li>Open <code>C:\\xampp\\php\\php.ini</code></li>";
    echo "<li>Find <code>;extension=gd</code></li>";
    echo "<li>Remove the semicolon: <code>extension=gd</code></li>";
    echo "<li>Restart Apache</li>";
    echo "</ol>";
    echo "<p>The system will use SVG fallbacks without GD.</p>";
    echo "</div>";
}

echo "<h3>Method 1: Offline QR Generator</h3>";
try {
    $offline_qr = OfflineQR::getQRDataURL($test_url, 200);
    if ($offline_qr) {
        echo "<img src='$offline_qr' alt='Offline QR Code' style='border: 1px solid #ccc;'><br>";
        if (extension_loaded('gd')) {
            echo "<p>✅ Offline QR generation successful (PNG)!</p>";
        } else {
            echo "<p>✅ Offline QR generation successful (SVG fallback)!</p>";
        }
    } else {
        echo "<p>❌ Offline QR generation failed</p>";
    }
} catch (Exception $e) {
    echo "<p>❌ Offline QR error: " . $e->getMessage() . "</p>";
}

echo "<h3>Method 2: SimpleQR (with fallbacks)</h3>";
try {
    $simple_qr = SimpleQR::getQRDataURL($test_url, 200);
    if ($simple_qr) {
        echo "<img src='$simple_qr' alt='SimpleQR Code' style='border: 1px solid #ccc;'><br>";
        echo "<p>✅ SimpleQR generation successful!</p>";
    } else {
        echo "<p>❌ SimpleQR generation failed</p>";
    }
} catch (Exception $e) {
    echo "<p>❌ SimpleQR error: " . $e->getMessage() . "</p>";
}

echo "<h3>Method 3: Online QR Service (Direct URL)</h3>";
$online_url = SimpleQR::generateQR($test_url, 200);
echo "<p>Online QR URL: <a href='$online_url' target='_blank'>$online_url</a></p>";
echo "<img src='$online_url' alt='Online QR Code' style='border: 1px solid #ccc;' onerror='this.style.display=\"none\"; this.nextElementSibling.style.display=\"block\";'>";
echo "<p style='display:none; color: red;'>❌ Online QR service unavailable</p>";

echo "<h3>Test URL</h3>";
echo "<p>Test verification URL: <a href='$test_url' target='_blank'>$test_url</a></p>";

echo "<div class='info-box'>";
echo "<h4>📋 Summary</h4>";
echo "<p>The QR code system now works with multiple fallback methods:</p>";
echo "<ul>";
echo "<li><strong>Best:</strong> GD Extension enabled → High-quality PNG QR codes</li>";
echo "<li><strong>Good:</strong> Online services → Real QR codes (requires internet)</li>";
echo "<li><strong>Fallback:</strong> SVG placeholders → Always works, shows verification code</li>";
echo "</ul>";
echo "</div>";

echo "<hr>";
echo "<p><strong>Note:</strong> Delete this test file (test_qr.php) after testing for security.</p>";
?>

<style>
body {
    font-family: Arial, sans-serif;
    max-width: 800px;
    margin: 20px auto;
    padding: 20px;
    background: #f5f5f5;
}

h2, h3 {
    color: #2c3e50;
}

img {
    margin: 10px 0;
    background: white;
    padding: 10px;
}

p {
    margin: 10px 0;
}

.status-box, .warning-box, .info-box {
    padding: 15px;
    margin: 15px 0;
    border-radius: 5px;
    border-left: 4px solid;
}

.status-box {
    background: #e8f5e8;
    border-color: #4caf50;
}

.warning-box {
    background: #fff3cd;
    border-color: #ffc107;
}

.info-box {
    background: #e3f2fd;
    border-color: #2196f3;
}

code {
    background: #f4f4f4;
    padding: 2px 6px;
    border-radius: 3px;
    font-family: 'Courier New', monospace;
}

ol, ul {
    margin: 10px 0;
    padding-left: 30px;
}

li {
    margin: 5px 0;
}
</style>
