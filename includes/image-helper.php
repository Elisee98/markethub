<?php
/**
 * Image Helper Functions for MarketHub
 * Handles image display, fallbacks, and path normalization
 */

/**
 * Get the correct image URL with fallback handling
 * @param string|null $image_url The image URL from database
 * @param string $fallback_type Type of fallback ('product', 'user', 'vendor')
 * @return string The final image URL to use
 */
function getImageUrl($image_url, $fallback_type = 'product') {
    // If no image URL provided, return fallback immediately
    if (empty($image_url)) {
        return getFallbackImage($fallback_type);
    }
    
    // Normalize the path
    $normalized_url = normalizeImagePath($image_url);
    
    // Check if file exists
    if (file_exists($normalized_url)) {
        return $normalized_url;
    }
    
    // If file doesn't exist, return fallback
    return getFallbackImage($fallback_type);
}

/**
 * Normalize image path to ensure consistent format
 * @param string $path The image path to normalize
 * @return string Normalized path
 */
function normalizeImagePath($path) {
    // Remove leading ./ if present
    if (strpos($path, './') === 0) {
        $path = substr($path, 2);
    }
    
    // Remove leading / if present
    if (strpos($path, '/') === 0) {
        $path = substr($path, 1);
    }
    
    // Ensure path doesn't have double slashes
    $path = preg_replace('/\/+/', '/', $path);
    
    return $path;
}

/**
 * Get fallback image based on type
 * @param string $type The type of fallback needed
 * @return string Path to fallback image
 */
function getFallbackImage($type = 'product') {
    $fallbacks = [
        'product' => 'assets/images/no-product-image.jpg',
        'user' => 'assets/images/no-user-avatar.jpg',
        'vendor' => 'assets/images/no-vendor-logo.jpg',
        'category' => 'assets/images/no-category-image.jpg'
    ];
    
    $fallback_path = $fallbacks[$type] ?? $fallbacks['product'];
    
    // If fallback doesn't exist, create a data URL placeholder
    if (!file_exists($fallback_path)) {
        return createPlaceholderDataUrl($type);
    }
    
    return $fallback_path;
}

/**
 * Create a data URL placeholder image
 * @param string $type The type of placeholder
 * @return string Data URL for placeholder image
 */
function createPlaceholderDataUrl($type = 'product') {
    $colors = [
        'product' => '#e5e7eb',
        'user' => '#d1d5db',
        'vendor' => '#f3f4f6',
        'category' => '#f9fafb'
    ];
    
    $color = $colors[$type] ?? $colors['product'];
    
    // Create a simple SVG placeholder
    $svg = '<svg width="300" height="300" xmlns="http://www.w3.org/2000/svg">
        <rect width="100%" height="100%" fill="' . $color . '"/>
        <text x="50%" y="50%" text-anchor="middle" dy=".3em" font-family="Arial, sans-serif" font-size="16" fill="#9ca3af">
            No Image
        </text>
    </svg>';
    
    return 'data:image/svg+xml;base64,' . base64_encode($svg);
}

/**
 * Generate HTML img tag with proper error handling
 * @param string|null $image_url The image URL
 * @param string $alt Alt text for the image
 * @param array $attributes Additional HTML attributes
 * @param string $fallback_type Type of fallback image
 * @return string HTML img tag
 */
function generateImageTag($image_url, $alt = '', $attributes = [], $fallback_type = 'product') {
    $src = getImageUrl($image_url, $fallback_type);
    $fallback = getFallbackImage($fallback_type);
    
    // Build attributes string
    $attr_string = '';
    foreach ($attributes as $key => $value) {
        $attr_string .= ' ' . htmlspecialchars($key) . '="' . htmlspecialchars($value) . '"';
    }
    
    // Add error handling
    $onerror = "this.onerror=null; this.src='" . htmlspecialchars($fallback) . "';";
    
    return '<img src="' . htmlspecialchars($src) . '" alt="' . htmlspecialchars($alt) . '" onerror="' . $onerror . '"' . $attr_string . '>';
}

/**
 * Get all images for a product with fallback handling
 * Uses the existing getProductImages() function from functions.php
 * @param int $product_id The product ID
 * @return array Array of processed image URLs
 */
function getProductImagesWithFallback($product_id) {
    global $database;

    // Use the existing function from functions.php
    $images = getProductImages($product_id);

    $processed_images = [];
    foreach ($images as $image) {
        $processed_images[] = [
            'url' => getImageUrl($image['image_url'], 'product'),
            'is_primary' => $image['is_primary'],
            'sort_order' => $image['sort_order'] ?? 0
        ];
    }

    // If no images found, check main product table
    if (empty($processed_images)) {
        $product = $database->fetch("SELECT image_url FROM products WHERE id = ?", [$product_id]);
        if ($product && !empty($product['image_url'])) {
            $processed_images[] = [
                'url' => getImageUrl($product['image_url'], 'product'),
                'is_primary' => true,
                'sort_order' => 0
            ];
        }
    }

    // If still no images, add fallback
    if (empty($processed_images)) {
        $processed_images[] = [
            'url' => getFallbackImage('product'),
            'is_primary' => true,
            'sort_order' => 0
        ];
    }

    return $processed_images;
}

/**
 * Create fallback image files if they don't exist
 */
function createFallbackImages() {
    $fallback_dir = 'assets/images';
    if (!is_dir($fallback_dir)) {
        mkdir($fallback_dir, 0755, true);
    }
    
    $fallbacks = [
        'no-product-image.jpg' => 'Product Image Not Available',
        'no-user-avatar.jpg' => 'User Avatar',
        'no-vendor-logo.jpg' => 'Vendor Logo',
        'no-category-image.jpg' => 'Category Image'
    ];
    
    foreach ($fallbacks as $filename => $text) {
        $filepath = $fallback_dir . '/' . $filename;
        if (!file_exists($filepath)) {
            // Create a simple placeholder image using GD if available
            if (extension_loaded('gd')) {
                $img = imagecreate(300, 300);
                imagecolorallocate($img, 240, 240, 240); // Background color
                $text_color = imagecolorallocate($img, 120, 120, 120);
                
                // Add text
                $font_size = 3;
                $text_width = imagefontwidth($font_size) * strlen($text);
                $text_height = imagefontheight($font_size);
                $x = (300 - $text_width) / 2;
                $y = (300 - $text_height) / 2;
                
                imagestring($img, $font_size, $x, $y, $text, $text_color);
                
                // Save as JPEG
                imagejpeg($img, $filepath, 80);
                imagedestroy($img);
            }
        }
    }
}

// Initialize fallback images
createFallbackImages();
?>
