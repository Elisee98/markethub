<?php
/**
 * Fix Image Database URLs
 * This script corrects image paths in the database and ensures proper image handling
 */

require_once 'config/config.php';
require_once 'includes/image-helper.php';

echo "<h1>Image Database Fix</h1>";

// Function to normalize and validate image paths
function fixImagePath($image_url) {
    if (empty($image_url)) {
        return null;
    }
    
    // Normalize the path
    $normalized = normalizeImagePath($image_url);
    
    // Check if file exists
    if (file_exists($normalized)) {
        return $normalized;
    }
    
    // Try common variations
    $variations = [
        'assets/images/' . basename($normalized),
        'uploads/products/' . basename($normalized),
        str_replace('uploads/products/', 'assets/images/', $normalized),
        str_replace('assets/images/', 'uploads/products/', $normalized)
    ];
    
    foreach ($variations as $variation) {
        if (file_exists($variation)) {
            return $variation;
        }
    }
    
    // If no file found, return null to use fallback
    return null;
}

// Fix products table
echo "<h2>Fixing Products Table</h2>";
$products = $database->fetchAll("SELECT id, name, image_url FROM products WHERE image_url IS NOT NULL");
$products_fixed = 0;
$products_removed = 0;

foreach ($products as $product) {
    $old_url = $product['image_url'];
    $new_url = fixImagePath($old_url);
    
    if ($new_url !== $old_url) {
        if ($new_url === null) {
            // Remove invalid image URL
            $database->execute("UPDATE products SET image_url = NULL WHERE id = ?", [$product['id']]);
            echo "<p>❌ Removed invalid image for product '{$product['name']}': {$old_url}</p>";
            $products_removed++;
        } else {
            // Update with corrected path
            $database->execute("UPDATE products SET image_url = ? WHERE id = ?", [$new_url, $product['id']]);
            echo "<p>✅ Fixed product '{$product['name']}': {$old_url} → {$new_url}</p>";
            $products_fixed++;
        }
    }
}

echo "<p><strong>Products table: {$products_fixed} fixed, {$products_removed} removed</strong></p>";

// Fix product_images table
echo "<h2>Fixing Product Images Table</h2>";
$images = $database->fetchAll("SELECT id, product_id, image_url FROM product_images");
$images_fixed = 0;
$images_removed = 0;

foreach ($images as $image) {
    $old_url = $image['image_url'];
    $new_url = fixImagePath($old_url);
    
    if ($new_url !== $old_url) {
        if ($new_url === null) {
            // Remove invalid image
            $database->execute("DELETE FROM product_images WHERE id = ?", [$image['id']]);
            echo "<p>❌ Removed invalid image: {$old_url}</p>";
            $images_removed++;
        } else {
            // Update with corrected path
            $database->execute("UPDATE product_images SET image_url = ? WHERE id = ?", [$new_url, $image['id']]);
            echo "<p>✅ Fixed image: {$old_url} → {$new_url}</p>";
            $images_fixed++;
        }
    }
}

echo "<p><strong>Product images table: {$images_fixed} fixed, {$images_removed} removed</strong></p>";

// Add missing primary images from assets/images
echo "<h2>Adding Missing Product Images</h2>";
$products_without_images = $database->fetchAll("
    SELECT p.id, p.name 
    FROM products p 
    LEFT JOIN product_images pi ON p.id = pi.product_id 
    WHERE p.image_url IS NULL AND pi.id IS NULL
");

$images_added = 0;
$available_images = [];

// Scan assets/images directory
if (is_dir('assets/images')) {
    $files = scandir('assets/images');
    foreach ($files as $file) {
        if (in_array(strtolower(pathinfo($file, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
            $available_images[] = 'assets/images/' . $file;
        }
    }
}

foreach ($products_without_images as $product) {
    // Try to find a matching image based on product name
    $product_name_clean = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $product['name']));
    
    foreach ($available_images as $image_path) {
        $image_name_clean = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', pathinfo($image_path, PATHINFO_FILENAME)));
        
        // Check for partial matches
        if (strpos($image_name_clean, $product_name_clean) !== false || 
            strpos($product_name_clean, $image_name_clean) !== false) {
            
            // Add to product_images table
            $database->execute(
                "INSERT INTO product_images (product_id, image_url, is_primary, created_at) VALUES (?, ?, 1, NOW())",
                [$product['id'], $image_path]
            );
            
            // Update main product table
            $database->execute(
                "UPDATE products SET image_url = ? WHERE id = ?",
                [$image_path, $product['id']]
            );
            
            echo "<p>✅ Added image for '{$product['name']}': {$image_path}</p>";
            $images_added++;
            
            // Remove from available images to avoid duplicates
            $available_images = array_filter($available_images, function($img) use ($image_path) {
                return $img !== $image_path;
            });
            break;
        }
    }
}

echo "<p><strong>Added {$images_added} new product images</strong></p>";

// Create fallback images
echo "<h2>Creating Fallback Images</h2>";
createFallbackImages();
echo "<p>✅ Fallback images created/verified</p>";

// Summary
echo "<h2>Summary</h2>";
echo "<ul>";
echo "<li>Products table: {$products_fixed} fixed, {$products_removed} removed</li>";
echo "<li>Product images table: {$images_fixed} fixed, {$images_removed} removed</li>";
echo "<li>New images added: {$images_added}</li>";
echo "<li>Fallback images created</li>";
echo "</ul>";

echo "<h3>Next Steps</h3>";
echo "<ol>";
echo "<li><a href='debug-images.php'>View Debug Report</a> to verify fixes</li>";
echo "<li><a href='index.php'>Check Homepage</a> to see if images display correctly</li>";
echo "<li><a href='products.php'>Check Products Page</a> to verify product listings</li>";
echo "</ol>";

// Test a few products
echo "<h2>Test Product Display</h2>";
$test_products = $database->fetchAll("SELECT id, name, image_url FROM products LIMIT 3");
foreach ($test_products as $product) {
    echo "<div style='border: 1px solid #ccc; padding: 10px; margin: 10px 0; display: inline-block;'>";
    echo "<h4>" . htmlspecialchars($product['name']) . "</h4>";
    echo generateImageTag(
        $product['image_url'], 
        $product['name'], 
        ['style' => 'width: 150px; height: 150px; object-fit: cover;'],
        'product'
    );
    echo "<p>URL: " . htmlspecialchars($product['image_url'] ?: 'None') . "</p>";
    echo "</div>";
}

?>
