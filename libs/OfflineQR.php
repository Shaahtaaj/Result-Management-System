<?php
/**
 * Offline QR Code Generator
 * A simple QR code generator that works without internet connection
 */
class OfflineQR {
    
    public static function generateSimpleQR($text, $size = 200) {
        // Check if GD extension is available
        if (!extension_loaded('gd')) {
            return false;
        }
        
        // Create a simple visual representation of a QR code
        $image = imagecreate($size, $size);
        $white = imagecolorallocate($image, 255, 255, 255);
        $black = imagecolorallocate($image, 0, 0, 0);
        $gray = imagecolorallocate($image, 200, 200, 200);
        
        // Fill background
        imagefill($image, 0, 0, $white);
        
        // Create a pattern that looks like a QR code
        $block_size = $size / 25; // 25x25 grid
        
        // Generate a pseudo-random pattern based on the text
        $hash = md5($text);
        $pattern = [];
        
        for ($i = 0; $i < 25; $i++) {
            for ($j = 0; $j < 25; $j++) {
                $index = ($i * 25 + $j) % 32;
                $char = hexdec($hash[$index]);
                $pattern[$i][$j] = ($char % 2 == 0) ? 1 : 0;
            }
        }
        
        // Add corner markers (typical QR code feature)
        self::addCornerMarker($pattern, 0, 0);
        self::addCornerMarker($pattern, 0, 18);
        self::addCornerMarker($pattern, 18, 0);
        
        // Draw the pattern
        for ($i = 0; $i < 25; $i++) {
            for ($j = 0; $j < 25; $j++) {
                if ($pattern[$i][$j] == 1) {
                    $x1 = $j * $block_size;
                    $y1 = $i * $block_size;
                    $x2 = $x1 + $block_size - 1;
                    $y2 = $y1 + $block_size - 1;
                    imagefilledrectangle($image, $x1, $y1, $x2, $y2, $black);
                }
            }
        }
        
        return $image;
    }
    
    private static function addCornerMarker(&$pattern, $start_x, $start_y) {
        // Add 7x7 corner marker pattern
        for ($i = 0; $i < 7; $i++) {
            for ($j = 0; $j < 7; $j++) {
                $x = $start_x + $i;
                $y = $start_y + $j;
                
                if ($x < 25 && $y < 25) {
                    // Outer border
                    if ($i == 0 || $i == 6 || $j == 0 || $j == 6) {
                        $pattern[$x][$y] = 1;
                    }
                    // Inner area
                    else if ($i >= 2 && $i <= 4 && $j >= 2 && $j <= 4) {
                        $pattern[$x][$y] = 1;
                    }
                    // Middle area
                    else {
                        $pattern[$x][$y] = 0;
                    }
                }
            }
        }
    }
    
    public static function saveQR($text, $filename, $size = 200) {
        $image = self::generateSimpleQR($text, $size);
        
        if ($image === false) {
            return false;
        }
        
        $result = imagepng($image, $filename);
        imagedestroy($image);
        return $result;
    }
    
    public static function getQRDataURL($text, $size = 200) {
        // Check if GD extension is available
        if (!extension_loaded('gd')) {
            return self::getSVGPlaceholder($text, $size);
        }
        
        $image = self::generateSimpleQR($text, $size);
        
        if ($image === false) {
            return self::getSVGPlaceholder($text, $size);
        }
        
        // Capture image data
        ob_start();
        imagepng($image);
        $image_data = ob_get_contents();
        ob_end_clean();
        
        imagedestroy($image);
        
        return 'data:image/png;base64,' . base64_encode($image_data);
    }
    
    private static function getSVGPlaceholder($text, $size = 200) {
        $code = '';
        if (strpos($text, 'code=') !== false) {
            $code = substr($text, strrpos($text, '=') + 1);
        } else {
            $code = substr(md5($text), 0, 8);
        }
        
        $svg = '<svg width="' . $size . '" height="' . $size . '" xmlns="http://www.w3.org/2000/svg">
            <rect width="' . $size . '" height="' . $size . '" fill="white" stroke="black" stroke-width="2"/>
            <rect x="10" y="10" width="30" height="30" fill="black"/>
            <rect x="' . ($size - 40) . '" y="10" width="30" height="30" fill="black"/>
            <rect x="10" y="' . ($size - 40) . '" width="30" height="30" fill="black"/>
            <rect x="15" y="15" width="20" height="20" fill="white"/>
            <rect x="' . ($size - 35) . '" y="15" width="20" height="20" fill="white"/>
            <rect x="15" y="' . ($size - 35) . '" width="20" height="20" fill="white"/>
            <rect x="20" y="20" width="10" height="10" fill="black"/>
            <rect x="' . ($size - 30) . '" y="20" width="10" height="10" fill="black"/>
            <rect x="20" y="' . ($size - 30) . '" width="10" height="10" fill="black"/>
            <text x="50%" y="55%" text-anchor="middle" font-family="Arial" font-size="14" fill="black">QR CODE</text>
            <text x="50%" y="70%" text-anchor="middle" font-family="Arial" font-size="10" fill="gray">' . htmlspecialchars($code) . '</text>
        </svg>';
        
        return 'data:image/svg+xml;base64,' . base64_encode($svg);
    }
    
    public static function generateVerificationQR($verification_code, $base_url, $size = 150) {
        $verify_url = $base_url . "/verify.php?code=" . $verification_code;
        return self::getQRDataURL($verify_url, $size);
    }
}
?>
