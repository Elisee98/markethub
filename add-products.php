<?php
/**
 * Add Products to MarketHub
 * Populate the marketplace with diverse products using existing images
 */

require_once 'config/config.php';

echo "<h1>📦 Adding Products to MarketHub</h1>";

// Helper function to generate slug from product name
function generateSlug($name) {
    $slug = strtolower($name);
    $slug = preg_replace('/[^a-z0-9\s-]/', '', $slug);
    $slug = preg_replace('/[\s-]+/', '-', $slug);
    $slug = trim($slug, '-');
    return $slug;
}

// Get categories and vendors
$categories = $database->fetchAll("SELECT id, name, slug FROM categories WHERE status = 'active'");
$vendors = $database->fetchAll("SELECT u.id, u.username, vs.store_name FROM users u LEFT JOIN vendor_stores vs ON u.id = vs.vendor_id WHERE u.user_type = 'vendor' AND u.status = 'active'");

echo "<h2>Available Categories: " . count($categories) . "</h2>";
foreach ($categories as $cat) {
    echo "- {$cat['name']} (ID: {$cat['id']})<br>";
}

echo "<h2>Available Vendors: " . count($vendors) . "</h2>";
foreach ($vendors as $vendor) {
    echo "- {$vendor['store_name']} (ID: {$vendor['id']})<br>";
}

// Helper function to get category ID by slug
function getCategoryId($slug, $categories) {
    foreach ($categories as $cat) {
        if ($cat['slug'] === $slug) return $cat['id'];
    }
    return null;
}

// Helper function to get random vendor ID
function getRandomVendorId($vendors) {
    return $vendors[array_rand($vendors)]['id'];
}

echo "<h2>Step 1: Adding Laptop Products</h2>";

$laptop_products = [
    [
        'name' => 'MacBook Pro 16-inch M3 Max',
        'description' => 'Powerful MacBook Pro with M3 Max chip, 32GB RAM, 1TB SSD. Perfect for professional work and creative tasks.',
        'price' => 2500000,
        'compare_price' => 2800000,
        'stock_quantity' => 15,
        'image' => 'assets/images/lap1.jpg',
        'sku' => 'MBP-M3-16-1TB'
    ],
    [
        'name' => 'Dell XPS 13 Plus',
        'description' => 'Ultra-thin Dell XPS 13 with Intel Core i7, 16GB RAM, 512GB SSD. Ideal for business and productivity.',
        'price' => 1200000,
        'compare_price' => 1400000,
        'stock_quantity' => 20,
        'image' => 'assets/images/lap2.jpg',
        'sku' => 'DELL-XPS13-512'
    ],
    [
        'name' => 'HP Pavilion Gaming Laptop',
        'description' => 'Gaming laptop with NVIDIA GTX 1650, AMD Ryzen 5, 8GB RAM, 256GB SSD. Great for gaming and multimedia.',
        'price' => 800000,
        'compare_price' => 950000,
        'stock_quantity' => 12,
        'image' => 'assets/images/lap3.jpg',
        'sku' => 'HP-PAV-GTX1650'
    ],
    [
        'name' => 'Lenovo ThinkPad X1 Carbon',
        'description' => 'Business laptop with Intel Core i5, 16GB RAM, 512GB SSD. Lightweight and durable for professionals.',
        'price' => 1500000,
        'compare_price' => 1700000,
        'stock_quantity' => 18,
        'image' => 'assets/images/lap4.jpg',
        'sku' => 'LEN-X1C-512'
    ]
];

$laptops_category_id = getCategoryId('laptops', $categories);
foreach ($laptop_products as $product) {
    try {
        $vendor_id = getRandomVendorId($vendors);
        $slug = generateSlug($product['name']);

        // Check if product already exists
        $existing = $database->fetch("SELECT id FROM products WHERE slug = ?", [$slug]);
        if ($existing) {
            echo "ℹ️ Product already exists: {$product['name']}<br>";
            continue;
        }

        $database->execute(
            "INSERT INTO products (vendor_id, category_id, name, slug, description, price, compare_price, stock_quantity, image_url, sku, status, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active', NOW())",
            [$vendor_id, $laptops_category_id, $product['name'], $slug, $product['description'], $product['price'], $product['compare_price'], $product['stock_quantity'], $product['image'], $product['sku']]
        );
        echo "✅ Added: {$product['name']}<br>";
    } catch (Exception $e) {
        echo "❌ Error adding {$product['name']}: " . $e->getMessage() . "<br>";
    }
}

echo "<h2>Step 2: Adding Fashion Products</h2>";

$fashion_products = [
    [
        'name' => 'Classic Blue Jeans',
        'description' => 'Comfortable classic blue jeans made from premium denim. Perfect fit for casual and semi-formal occasions.',
        'price' => 45000,
        'compare_price' => 55000,
        'stock_quantity' => 50,
        'image' => 'assets/images/jean1.jpg',
        'sku' => 'JEAN-CLASSIC-BLUE'
    ],
    [
        'name' => 'Slim Fit Dark Jeans',
        'description' => 'Modern slim fit jeans in dark wash. Stylish and comfortable for everyday wear.',
        'price' => 52000,
        'compare_price' => 65000,
        'stock_quantity' => 35,
        'image' => 'assets/images/jean2.jpg',
        'sku' => 'JEAN-SLIM-DARK'
    ],
    [
        'name' => 'Distressed Vintage Jeans',
        'description' => 'Trendy distressed jeans with vintage look. Perfect for casual outings and street style.',
        'price' => 48000,
        'compare_price' => 58000,
        'stock_quantity' => 25,
        'image' => 'assets/images/jean3.jpg',
        'sku' => 'JEAN-VINTAGE-DIST'
    ],
    [
        'name' => 'High-Waist Skinny Jeans',
        'description' => 'Fashionable high-waist skinny jeans for women. Flattering fit with stretch fabric.',
        'price' => 55000,
        'compare_price' => 68000,
        'stock_quantity' => 40,
        'image' => 'assets/images/jean4.jpg',
        'sku' => 'JEAN-HW-SKINNY'
    ],
    [
        'name' => 'Relaxed Fit Jeans',
        'description' => 'Comfortable relaxed fit jeans for all-day comfort. Classic style that never goes out of fashion.',
        'price' => 42000,
        'compare_price' => 50000,
        'stock_quantity' => 30,
        'image' => 'assets/images/jean5.jpg',
        'sku' => 'JEAN-RELAXED-FIT'
    ],
    [
        'name' => 'Premium Designer Jeans',
        'description' => 'High-end designer jeans with premium finishing. Luxury denim for special occasions.',
        'price' => 85000,
        'compare_price' => 100000,
        'stock_quantity' => 15,
        'image' => 'assets/images/jean6.jpg',
        'sku' => 'JEAN-PREMIUM-DES'
    ]
];

$fashion_category_id = getCategoryId('fashion', $categories);
foreach ($fashion_products as $product) {
    try {
        $vendor_id = getRandomVendorId($vendors);
        $slug = generateSlug($product['name']);

        // Check if product already exists
        $existing = $database->fetch("SELECT id FROM products WHERE slug = ?", [$slug]);
        if ($existing) {
            echo "ℹ️ Product already exists: {$product['name']}<br>";
            continue;
        }

        $database->execute(
            "INSERT INTO products (vendor_id, category_id, name, slug, description, price, compare_price, stock_quantity, image_url, sku, status, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active', NOW())",
            [$vendor_id, $fashion_category_id, $product['name'], $slug, $product['description'], $product['price'], $product['compare_price'], $product['stock_quantity'], $product['image'], $product['sku']]
        );
        echo "✅ Added: {$product['name']}<br>";
    } catch (Exception $e) {
        echo "❌ Error adding {$product['name']}: " . $e->getMessage() . "<br>";
    }
}

echo "<h2>Step 3: Adding Footwear Products</h2>";

$shoe_products = [
    [
        'name' => 'Nike Air Max 270',
        'description' => 'Comfortable Nike Air Max sneakers with excellent cushioning. Perfect for running and casual wear.',
        'price' => 120000,
        'compare_price' => 140000,
        'stock_quantity' => 25,
        'image' => 'assets/images/shoe1.jpg',
        'sku' => 'NIKE-AM270-BLK'
    ],
    [
        'name' => 'Adidas Ultraboost 22',
        'description' => 'Premium Adidas running shoes with Boost technology. Superior comfort and performance.',
        'price' => 150000,
        'compare_price' => 175000,
        'stock_quantity' => 20,
        'image' => 'assets/images/shoe2.jpg',
        'sku' => 'ADIDAS-UB22-WHT'
    ],
    [
        'name' => 'Converse Chuck Taylor',
        'description' => 'Classic Converse All Star sneakers. Timeless design for casual and street style.',
        'price' => 65000,
        'compare_price' => 75000,
        'stock_quantity' => 35,
        'image' => 'assets/images/shoe3.jpg',
        'sku' => 'CONV-CT-CLASSIC'
    ],
    [
        'name' => 'Formal Leather Shoes',
        'description' => 'Elegant formal leather shoes for business and special occasions. Handcrafted quality.',
        'price' => 95000,
        'compare_price' => 110000,
        'stock_quantity' => 15,
        'image' => 'assets/images/shoe4.jpg',
        'sku' => 'FORMAL-LEATH-BRN'
    ],
    [
        'name' => 'Casual Canvas Sneakers',
        'description' => 'Comfortable canvas sneakers for everyday wear. Lightweight and breathable.',
        'price' => 45000,
        'compare_price' => 55000,
        'stock_quantity' => 40,
        'image' => 'assets/images/shoe5.jpg',
        'sku' => 'CANVAS-SNEAK-BLU'
    ]
];

$shoes_category_id = getCategoryId('shoes', $categories);
foreach ($shoe_products as $product) {
    try {
        $vendor_id = getRandomVendorId($vendors);
        $slug = generateSlug($product['name']);

        // Check if product already exists
        $existing = $database->fetch("SELECT id FROM products WHERE slug = ?", [$slug]);
        if ($existing) {
            echo "ℹ️ Product already exists: {$product['name']}<br>";
            continue;
        }

        $database->execute(
            "INSERT INTO products (vendor_id, category_id, name, slug, description, price, compare_price, stock_quantity, image_url, sku, status, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active', NOW())",
            [$vendor_id, $shoes_category_id, $product['name'], $slug, $product['description'], $product['price'], $product['compare_price'], $product['stock_quantity'], $product['image'], $product['sku']]
        );
        echo "✅ Added: {$product['name']}<br>";
    } catch (Exception $e) {
        echo "❌ Error adding {$product['name']}: " . $e->getMessage() . "<br>";
    }
}

echo "<h2>Step 4: Adding Smartphone Products</h2>";

$smartphone_products = [
    [
        'name' => 'iPhone 15 Pro Max',
        'description' => 'Latest iPhone 15 Pro Max with A17 Pro chip, 256GB storage, Pro camera system with titanium design.',
        'price' => 1200000,
        'compare_price' => 1350000,
        'stock_quantity' => 10,
        'image' => 'assets/images/iphone 1.jpg',
        'sku' => 'IPH15-PM-256'
    ],
    [
        'name' => 'iPhone 14 Pro',
        'description' => 'iPhone 14 Pro with Dynamic Island, A16 Bionic chip, 128GB storage, advanced camera system.',
        'price' => 950000,
        'compare_price' => 1100000,
        'stock_quantity' => 15,
        'image' => 'assets/images/iphone 2.jpg',
        'sku' => 'IPH14-PRO-128'
    ],
    [
        'name' => 'iPhone 13 Pro Max',
        'description' => 'iPhone 13 Pro Max with A15 Bionic, 512GB storage, ProRes video recording, excellent battery life.',
        'price' => 850000,
        'compare_price' => 950000,
        'stock_quantity' => 12,
        'image' => 'assets/images/ihone 3.jpg',
        'sku' => 'IPH13-PM-512'
    ],
    [
        'name' => 'iPhone 15 Standard',
        'description' => 'iPhone 15 with USB-C, A16 Bionic chip, 128GB storage, improved cameras and battery life.',
        'price' => 750000,
        'compare_price' => 850000,
        'stock_quantity' => 20,
        'image' => 'assets/images/iphone 4.jpg',
        'sku' => 'IPH15-STD-128'
    ],
    [
        'name' => 'Google Pixel 8 Pro',
        'description' => 'Google Pixel 8 Pro with Tensor G3 chip, 256GB storage, advanced AI photography features.',
        'price' => 800000,
        'compare_price' => 900000,
        'stock_quantity' => 18,
        'image' => 'assets/images/pixel1.jpg',
        'sku' => 'PIX8-PRO-256'
    ],
    [
        'name' => 'Google Pixel 7a',
        'description' => 'Affordable Pixel 7a with Google Tensor G2, 128GB storage, excellent camera performance.',
        'price' => 450000,
        'compare_price' => 520000,
        'stock_quantity' => 25,
        'image' => 'assets/images/pixel2.jpg',
        'sku' => 'PIX7A-128'
    ],
    [
        'name' => 'Google Pixel 8',
        'description' => 'Google Pixel 8 with Tensor G3, 128GB storage, Magic Eraser, and 7 years of updates.',
        'price' => 650000,
        'compare_price' => 750000,
        'stock_quantity' => 22,
        'image' => 'assets/images/pixel3.jpg',
        'sku' => 'PIX8-STD-128'
    ],
    [
        'name' => 'Google Pixel 6 Pro',
        'description' => 'Pixel 6 Pro with Tensor chip, 256GB storage, professional photography features.',
        'price' => 550000,
        'compare_price' => 650000,
        'stock_quantity' => 15,
        'image' => 'assets/images/pixel4.jpg',
        'sku' => 'PIX6-PRO-256'
    ]
];

$smartphones_category_id = getCategoryId('smartphones', $categories);
foreach ($smartphone_products as $product) {
    try {
        $vendor_id = getRandomVendorId($vendors);
        $slug = generateSlug($product['name']);

        // Check if product already exists
        $existing = $database->fetch("SELECT id FROM products WHERE slug = ?", [$slug]);
        if ($existing) {
            echo "ℹ️ Product already exists: {$product['name']}<br>";
            continue;
        }

        $database->execute(
            "INSERT INTO products (vendor_id, category_id, name, slug, description, price, compare_price, stock_quantity, image_url, sku, status, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active', NOW())",
            [$vendor_id, $smartphones_category_id, $product['name'], $slug, $product['description'], $product['price'], $product['compare_price'], $product['stock_quantity'], $product['image'], $product['sku']]
        );
        echo "✅ Added: {$product['name']}<br>";
    } catch (Exception $e) {
        echo "❌ Error adding {$product['name']}: " . $e->getMessage() . "<br>";
    }
}

echo "<h2>📊 Final Statistics</h2>";
$final_stats = [
    'categories' => $database->fetch("SELECT COUNT(*) as count FROM categories WHERE status = 'active'")['count'],
    'vendors' => $database->fetch("SELECT COUNT(*) as count FROM users WHERE user_type = 'vendor' AND status = 'active'")['count'],
    'products' => $database->fetch("SELECT COUNT(*) as count FROM products WHERE status = 'active'")['count']
];

echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; padding: 20px; border-radius: 5px; margin: 20px 0;'>";
echo "<h3>🎉 Marketplace Expansion Complete!</h3>";
echo "<ul>";
echo "<li><strong>Categories:</strong> {$final_stats['categories']}</li>";
echo "<li><strong>Vendors:</strong> {$final_stats['vendors']}</li>";
echo "<li><strong>Products:</strong> {$final_stats['products']}</li>";
echo "</ul>";
echo "<p><a href='products.php' style='background: #10b981; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px;'>🛍️ Browse Products</a> ";
echo "<a href='vendors.php' style='background: #3b82f6; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px;'>🏪 View Vendors</a></p>";
echo "</div>";

?>

<style>
body { font-family: Arial, sans-serif; padding: 20px; line-height: 1.6; max-width: 1000px; margin: 0 auto; }
h1 { color: #10b981; text-align: center; }
h2 { color: #374151; border-bottom: 2px solid #e5e7eb; padding-bottom: 5px; margin-top: 30px; }
</style>
