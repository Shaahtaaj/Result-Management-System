<?php
/**
 * Advanced PDF Generator using wkhtmltopdf or browser-based generation
 */

// Include Composer autoloader if available
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
}

class PDFGenerator {
    
    public static function generateFromHTML($html, $filename = 'document.pdf', $options = []) {
        // Try different PDF generation methods in order of preference
        
        // Method 1: Try wkhtmltopdf if available
        if (self::isWkhtmltopdfAvailable()) {
            return self::generateWithWkhtmltopdf($html, $filename, $options);
        }
        
    
        
        // Method 4: Fallback to browser-based PDF generation
        return self::generateWithBrowser($html, $filename, $options);
    }
    
    private static function isWkhtmltopdfAvailable() {
        // Check if wkhtmltopdf is installed - try multiple paths
        $commands = [
            'wkhtmltopdf --version',
            '"C:\\Program Files\\wkhtmltopdf\\bin\\wkhtmltopdf.exe" --version',
            '"C:\\Program Files (x86)\\wkhtmltopdf\\bin\\wkhtmltopdf.exe" --version'
        ];
        
        foreach ($commands as $cmd) {
            $output = [];
            $return_var = 0;
            exec($cmd . ' 2>&1', $output, $return_var);
            if ($return_var === 0) {
                return true;
            }
        }
        return false;
    }
    
    private static function getWkhtmltopdfCommand() {
        // Get the correct wkhtmltopdf command path
        $commands = [
            'wkhtmltopdf',
            '"C:\\Program Files\\wkhtmltopdf\\bin\\wkhtmltopdf.exe"',
            '"C:\\Program Files (x86)\\wkhtmltopdf\\bin\\wkhtmltopdf.exe"'
        ];
        
        foreach ($commands as $cmd) {
            $output = [];
            $return_var = 0;
            exec($cmd . ' --version 2>&1', $output, $return_var);
            if ($return_var === 0) {
                return $cmd;
            }
        }
        return 'wkhtmltopdf'; // fallback
    }
    
    private static function generateWithWkhtmltopdf($html, $filename, $options) {
        // Create temporary HTML file
        $temp_html = tempnam(sys_get_temp_dir(), 'pdf_') . '.html';
        file_put_contents($temp_html, $html);
        
        // Create temporary PDF file
        $temp_pdf = tempnam(sys_get_temp_dir(), 'pdf_') . '.pdf';
        
        // Build wkhtmltopdf command with correct path
        $wkhtmltopdf_cmd = self::getWkhtmltopdfCommand();
        $cmd = $wkhtmltopdf_cmd;
        $cmd .= ' --page-size A4';
        $cmd .= ' --margin-top 15mm';
        $cmd .= ' --margin-right 15mm';
        $cmd .= ' --margin-bottom 15mm';
        $cmd .= ' --margin-left 15mm';
        $cmd .= ' --encoding UTF-8';
        $cmd .= ' --print-media-type';
        $cmd .= ' --disable-smart-shrinking';
        $cmd .= ' --quiet'; // Suppress output
        $cmd .= ' "' . $temp_html . '"';
        $cmd .= ' "' . $temp_pdf . '"';
        
        // Execute command
        exec($cmd . ' 2>&1', $output, $return_var);
        
        if ($return_var === 0 && file_exists($temp_pdf)) {
            // Success - output PDF
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Content-Length: ' . filesize($temp_pdf));
            readfile($temp_pdf);
            
            // Clean up
            unlink($temp_html);
            unlink($temp_pdf);
            return true;
        }
        
        // Clean up on failure
        if (file_exists($temp_html)) unlink($temp_html);
        if (file_exists($temp_pdf)) unlink($temp_pdf);
        
        return false;
    }
    
    
 
    private static function generateWithBrowser($html, $filename, $options) {
        // Create a special page that opens print dialog automatically
        $pdf_page = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>PDF Generation - ' . htmlspecialchars($filename) . '</title>
            <style>
                body { 
                    font-family: Arial, sans-serif; 
                    margin: 40px; 
                    background: #f5f5f5; 
                }
                .container { 
                    background: white; 
                    padding: 30px; 
                    border-radius: 10px; 
                    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
                    max-width: 600px;
                    margin: 0 auto;
                    text-align: center;
                }
                .btn { 
                    display: inline-block;
                    padding: 12px 24px; 
                    background: #667eea; 
                    color: white; 
                    text-decoration: none; 
                    border-radius: 5px; 
                    margin: 10px;
                    font-weight: bold;
                    border: none;
                    cursor: pointer;
                    font-size: 14px;
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
                    text-align: left;
                }
                .hidden-content {
                    display: none;
                }
                @media print {
                    .container { display: none; }
                    .hidden-content { display: block; }
                }
            </style>
        </head>
        <body>
            <div class="container">
                <h2>📄 PDF Generation Ready</h2>
                <p>Your document is ready to be saved as PDF.</p>
                
                <div class="steps">
                    <h3>📋 Instructions:</h3>
                    <ol>
                        <li>Click "Generate PDF" below</li>
                        <li>Press <strong>Ctrl+P</strong> (Windows) or <strong>Cmd+P</strong> (Mac)</li>
                        <li>Select "Save as PDF" as destination</li>
                        <li>Choose your save location and click "Save"</li>
                    </ol>
                </div>
                
                <button onclick="openPrintDialog()" class="btn btn-success">
                    🖨️ Generate PDF
                </button>
                <button onclick="window.close()" class="btn">
                    ✖️ Close
                </button>
            </div>
            
            <div class="hidden-content">
                ' . $html . '
            </div>
            
            <script>
                function openPrintDialog() {
                    // Hide the instructions and show the content
                    document.querySelector(".container").style.display = "none";
                    document.querySelector(".hidden-content").style.display = "block";
                    
                    // Trigger print dialog after a short delay
                    setTimeout(function() {
                        window.print();
                        
                        // Show instructions again after print dialog
                        setTimeout(function() {
                            document.querySelector(".container").style.display = "block";
                            document.querySelector(".hidden-content").style.display = "none";
                        }, 1000);
                    }, 500);
                }
                
                // Auto-open print dialog if requested
                if (window.location.search.includes("auto_print=1")) {
                    setTimeout(openPrintDialog, 1000);
                }
            </script>
        </body>
        </html>';
        
        echo $pdf_page;
        return true;
    }
}
?>
