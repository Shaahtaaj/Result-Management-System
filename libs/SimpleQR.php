<?php
/**
 * Simple QR Code Generator
 * A lightweight QR code generator using alternative methods
 */
class SimpleQR {
    
    public static function generateQR($text, $size = 200) {
        // Use QR Server API as alternative to Google Charts
        $url = "https://api.qrserver.com/v1/create-qr-code/?size={$size}x{$size}&data=" . urlencode($text);
        return $url;
    }
    
    public static function saveQR($text, $filename, $size = 200) {
        $url = self::generateQR($text, $size);
        
        // Try to get QR code data with error handling
        $context = stream_context_create([
            'http' => [
                'timeout' => 10,
                'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
            ]
        ]);
        
        $qr_data = @file_get_contents($url, false, $context);
        
        if ($qr_data !== false) {
            return file_put_contents($filename, $qr_data);
        }
        
        // Fallback: Generate a simple placeholder QR code
        return self::generateFallbackQR($text, $filename, $size);
    }
    
    public static function getQRDataURL($text, $size = 200) {
        // Try offline QR generator first (most reliable if GD is available)
        if (class_exists('OfflineQR')) {
            $offline_result = OfflineQR::getQRDataURL($text, $size);
            if ($offline_result !== false) {
                return $offline_result;
            }
        }
        
        // Try online QR service
        $url = self::generateQR($text, $size);
        
        // Try to get QR code data with error handling
        $context = stream_context_create([
            'http' => [
                'timeout' => 5,
                'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
            ]
        ]);
        
        $qr_data = @file_get_contents($url, false, $context);
        
        if ($qr_data !== false && strlen($qr_data) > 100) { // Basic validation
            return 'data:image/png;base64,' . base64_encode($qr_data);
        }
        
        // Fallback: Return a placeholder or try alternative method
        return self::getFallbackQRDataURL($text, $size);
    }
    
    private static function generateFallbackQR($text, $filename, $size = 200) {
        // Check if GD extension is available
        if (!extension_loaded('gd')) {
            return false;
        }
        
        // Create a simple placeholder image with the verification code
        $image = imagecreate($size, $size);
        $white = imagecolorallocate($image, 255, 255, 255);
        $black = imagecolorallocate($image, 0, 0, 0);
        $gray = imagecolorallocate($image, 128, 128, 128);
        
        // Fill background
        imagefill($image, 0, 0, $white);
        
        // Draw border
        imagerectangle($image, 0, 0, $size-1, $size-1, $black);
        
        // Add text
        $font_size = 3;
        $text_width = imagefontwidth($font_size) * strlen("QR CODE");
        $text_height = imagefontheight($font_size);
        $x = ($size - $text_width) / 2;
        $y = ($size - $text_height) / 2 - 20;
        
        imagestring($image, $font_size, $x, $y, "QR CODE", $black);
        
        // Add verification info
        $code = substr($text, strrpos($text, '=') + 1);
        $code_width = imagefontwidth(2) * strlen($code);
        $code_x = ($size - $code_width) / 2;
        imagestring($image, 2, $code_x, $y + 30, $code, $gray);
        
        // Save image
        $result = imagepng($image, $filename);
        imagedestroy($image);
        
        return $result;
    }
    
    private static function getFallbackQRDataURL($text, $size = 200) {
        // Try to create PNG fallback if GD is available
        if (extension_loaded('gd')) {
            $temp_file = tempnam(sys_get_temp_dir(), 'qr_');
            
            if (self::generateFallbackQR($text, $temp_file, $size)) {
                $data = file_get_contents($temp_file);
                unlink($temp_file);
                
                if ($data !== false) {
                    return 'data:image/png;base64,' . base64_encode($data);
                }
            }
        }
        
        // Ultimate fallback: return a simple SVG QR placeholder
        return self::getSVGPlaceholder($text, $size);
    }
    
    private static function getSVGPlaceholder($text, $size = 200) {
        $code = substr($text, strrpos($text, '=') + 1);
        
        $svg = '<svg width="' . $size . '" height="' . $size . '" xmlns="http://www.w3.org/2000/svg">
            <rect width="' . $size . '" height="' . $size . '" fill="white" stroke="black" stroke-width="2"/>
            <text x="50%" y="40%" text-anchor="middle" font-family="Arial" font-size="14" fill="black">QR CODE</text>
            <text x="50%" y="60%" text-anchor="middle" font-family="Arial" font-size="12" fill="gray">' . htmlspecialchars($code) . '</text>
        </svg>';
        
        return 'data:image/svg+xml;base64,' . base64_encode($svg);
    }
    
    // Alternative method using a different QR service
    public static function generateQRAlternative($text, $size = 200) {
        // Use QR Code Generator API as backup
        return "https://qr-code-generator24.p.rapidapi.com/qrcode?data=" . urlencode($text) . "&size={$size}";
    }
}
?>
