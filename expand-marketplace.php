<?php
/**
 * Expand MarketHub Marketplace
 * Add more categories, products, and vendors for a comprehensive marketplace
 */

require_once 'config/config.php';

echo "<h1>🚀 Expanding MarketHub Marketplace</h1>";

// Step 1: Add new categories
echo "<h2>Step 1: Adding Categories</h2>";

$categories = [
    ['name' => 'Laptops & Computers', 'slug' => 'laptops', 'description' => 'High-performance laptops and desktop computers'],
    ['name' => 'Smartphones', 'slug' => 'smartphones', 'description' => 'Latest smartphones and mobile devices'],
    ['name' => 'Fashion & Clothing', 'slug' => 'fashion', 'description' => 'Trendy clothing and fashion accessories'],
    ['name' => 'Footwear', 'slug' => 'shoes', 'description' => 'Comfortable and stylish shoes for all occasions'],
    ['name' => 'Electronics', 'slug' => 'electronics', 'description' => 'Consumer electronics and gadgets'],
    ['name' => 'Accessories', 'slug' => 'accessories', 'description' => 'Tech accessories and lifestyle products']
];

foreach ($categories as $category) {
    try {
        // Check if category already exists
        $existing = $database->fetch("SELECT id FROM categories WHERE slug = ?", [$category['slug']]);
        
        if (!$existing) {
            $database->execute(
                "INSERT INTO categories (name, slug, description, status, created_at) VALUES (?, ?, ?, 'active', NOW())",
                [$category['name'], $category['slug'], $category['description']]
            );
            echo "✅ Added category: {$category['name']}<br>";
        } else {
            echo "ℹ️ Category already exists: {$category['name']}<br>";
        }
    } catch (Exception $e) {
        echo "❌ Error adding category {$category['name']}: " . $e->getMessage() . "<br>";
    }
}

// Step 2: Add new vendors
echo "<h2>Step 2: Adding Vendors</h2>";

$vendors = [
    [
        'username' => 'techstore_rw',
        'email' => 'info@techstore.rw',
        'password' => password_hash('vendor123', PASSWORD_DEFAULT),
        'first_name' => 'Jean',
        'last_name' => 'Uwimana',
        'phone' => '+250788123456',
        'store_name' => 'TechStore Rwanda',
        'store_description' => 'Your trusted partner for laptops, smartphones and electronics in Rwanda'
    ],
    [
        'username' => 'fashion_hub',
        'email' => 'contact@fashionhub.rw',
        'password' => password_hash('vendor123', PASSWORD_DEFAULT),
        'first_name' => 'Marie',
        'last_name' => 'Mukamana',
        'phone' => '+250788234567',
        'store_name' => 'Fashion Hub',
        'store_description' => 'Trendy clothing and fashion accessories for modern lifestyle'
    ],
    [
        'username' => 'shoe_palace',
        'email' => 'sales@shoepalace.rw',
        'password' => password_hash('vendor123', PASSWORD_DEFAULT),
        'first_name' => 'David',
        'last_name' => 'Nkurunziza',
        'phone' => '+250788345678',
        'store_name' => 'Shoe Palace',
        'store_description' => 'Premium footwear collection for men, women and children'
    ],
    [
        'username' => 'gadget_world',
        'email' => 'hello@gadgetworld.rw',
        'password' => password_hash('vendor123', PASSWORD_DEFAULT),
        'first_name' => 'Grace',
        'last_name' => 'Uwimana',
        'phone' => '+250788456789',
        'store_name' => 'Gadget World',
        'store_description' => 'Latest gadgets and electronic accessories at competitive prices'
    ],
    [
        'username' => 'pixel_store',
        'email' => 'support@pixelstore.rw',
        'password' => password_hash('vendor123', PASSWORD_DEFAULT),
        'first_name' => 'Patrick',
        'last_name' => 'Habimana',
        'phone' => '+250788567890',
        'store_name' => 'Pixel Store',
        'store_description' => 'Google Pixel devices and premium smartphone accessories'
    ]
];

foreach ($vendors as $vendor) {
    try {
        // Check if vendor already exists
        $existing = $database->fetch("SELECT id FROM users WHERE username = ? OR email = ?", [$vendor['username'], $vendor['email']]);
        
        if (!$existing) {
            // Insert user
            $database->execute(
                "INSERT INTO users (username, email, password, first_name, last_name, phone, user_type, status, created_at) 
                 VALUES (?, ?, ?, ?, ?, ?, 'vendor', 'active', NOW())",
                [$vendor['username'], $vendor['email'], $vendor['password'], $vendor['first_name'], $vendor['last_name'], $vendor['phone']]
            );
            
            $vendor_id = $database->lastInsertId();
            
            // Insert vendor store
            $database->execute(
                "INSERT INTO vendor_stores (vendor_id, store_name, store_description, created_at) VALUES (?, ?, ?, NOW())",
                [$vendor_id, $vendor['store_name'], $vendor['store_description']]
            );
            
            echo "✅ Added vendor: {$vendor['store_name']} (ID: {$vendor_id})<br>";
        } else {
            echo "ℹ️ Vendor already exists: {$vendor['username']}<br>";
        }
    } catch (Exception $e) {
        echo "❌ Error adding vendor {$vendor['username']}: " . $e->getMessage() . "<br>";
    }
}

echo "<h2>Step 3: Current Database Status</h2>";

// Show current categories
$categories_count = $database->fetch("SELECT COUNT(*) as count FROM categories WHERE status = 'active'");
echo "✅ Active Categories: {$categories_count['count']}<br>";

// Show current vendors
$vendors_count = $database->fetch("SELECT COUNT(*) as count FROM users WHERE user_type = 'vendor' AND status = 'active'");
echo "✅ Active Vendors: {$vendors_count['count']}<br>";

// Show current products
$products_count = $database->fetch("SELECT COUNT(*) as count FROM products WHERE status = 'active'");
echo "✅ Active Products: {$products_count['count']}<br>";

echo "<h2>Step 4: Next Steps</h2>";
echo "<div style='background: #f0f9ff; border: 1px solid #0ea5e9; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
echo "<h3>🎯 Ready to Add Products!</h3>";
echo "<p>Now that we have categories and vendors, let's add products:</p>";
echo "<ol>";
echo "<li><strong>Laptops</strong> - Using lap1.jpg to lap4.jpg images</li>";
echo "<li><strong>Smartphones</strong> - Using iphone and pixel images</li>";
echo "<li><strong>Fashion</strong> - Using jean1.jpg to jean6.jpg images</li>";
echo "<li><strong>Shoes</strong> - Using shoe1.jpg to shoe9.jpg images</li>";
echo "</ol>";
echo "<p><a href='add-products.php' style='background: #10b981; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px;'>➡️ Add Products Now</a></p>";
echo "</div>";

echo "<h2>Step 5: Available Images</h2>";
$image_files = glob('assets/images/*.jpg');
echo "<div style='display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 10px; margin: 20px 0;'>";
foreach ($image_files as $image) {
    $filename = basename($image);
    echo "<div style='border: 1px solid #ddd; padding: 10px; border-radius: 5px; text-align: center;'>";
    echo "<img src='{$image}' style='width: 100%; height: 120px; object-fit: cover; border-radius: 5px; margin-bottom: 5px;'>";
    echo "<small>{$filename}</small>";
    echo "</div>";
}
echo "</div>";

?>

<style>
body { font-family: Arial, sans-serif; padding: 20px; line-height: 1.6; max-width: 1200px; margin: 0 auto; }
h1 { color: #10b981; text-align: center; }
h2 { color: #374151; border-bottom: 2px solid #e5e7eb; padding-bottom: 5px; margin-top: 30px; }
h3 { color: #059669; }
</style>
