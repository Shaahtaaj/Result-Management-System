# Enable GD Extension in XAMPP

The QR code generation requires the GD extension to be enabled in PHP. Here's how to enable it:

## Method 1: Enable GD Extension (Recommended)

1. **Open XAMPP Control Panel**
2. **Stop Apache** if it's running
3. **Navigate to XAMPP installation folder** (usually `C:\xampp\`)
4. **Open the file:** `php\php.ini`
5. **Find the line:** `;extension=gd` (around line 930)
6. **Remove the semicolon** to uncomment it: `extension=gd`
7. **Save the file**
8. **Start Apache** again

## Method 2: Alternative (If GD cannot be enabled)

If you cannot enable GD extension, the system will automatically fall back to:
1. Online QR code services
2. SVG placeholder QR codes

## Verify GD Extension

Create a test file `phpinfo.php` with this content:
```php
<?php phpinfo(); ?>
```

Visit `http://localhost/phpinfo.php` and search for "gd" to verify it's enabled.

## Current Status

- ✅ **SVG Fallback:** Working (no GD required)
- ✅ **Online QR:** Working (requires internet)
- ⚠️ **Offline PNG QR:** Requires GD extension

The system will work without GD, but enabling it provides the best QR code quality.
