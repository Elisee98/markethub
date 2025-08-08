<?php
/**
 * Add All Images to Database
 * Comprehensive script to add all available images as products in the database
 */

require_once 'config/config.php';

echo "<h1>📸 Adding All Images to Database</h1>";

$products_added = 0;
$products_updated = 0;
$errors = [];

// Get categories
$categories = [];
$category_results = $database->fetchAll("SELECT id, name, slug FROM categories WHERE status = 'active'");
foreach ($category_results as $cat) {
    $categories[$cat['slug']] = $cat['id'];
}

// Get active vendors
$vendors = $database->fetchAll("SELECT id, username FROM users WHERE user_type = 'vendor' AND status = 'active'");

function getRandomVendor($vendors) {
    return $vendors[array_rand($vendors)];
}

echo "<h2>📱 Adding Smartphone Products</h2>";

// iPhone Products
$iphone_products = [
    [
        'name' => 'iPhone 15 Pro Max 256GB',
        'image' => 'assets/images/iPhone 15 Pro Max.jpg',
        'description' => 'Latest iPhone 15 Pro Max with A17 Pro chip, 256GB storage, titanium design, and advanced Pro camera system.',
        'price' => 1350000,
        'compare_price' => 1500000,
        'sku' => 'IPH15-PM-256',
        'brand' => 'Apple'
    ],
    [
        'name' => 'iPhone 14 Pro 128GB',
        'image' => 'assets/images/iphone 1.jpg',
        'description' => 'iPhone 14 Pro with Dynamic Island, A16 Bionic chip, 128GB storage, and Pro camera system.',
        'price' => 1100000,
        'compare_price' => 1250000,
        'sku' => 'IPH14-PRO-128',
        'brand' => 'Apple'
    ],
    [
        'name' => 'iPhone 13 Pro Max 512GB',
        'image' => 'assets/images/iphone 2.jpg',
        'description' => 'iPhone 13 Pro Max with A15 Bionic, 512GB storage, ProRes video recording, excellent battery life.',
        'price' => 950000,
        'compare_price' => 1100000,
        'sku' => 'IPH13-PM-512',
        'brand' => 'Apple'
    ],
    [
        'name' => 'iPhone 12 Pro 256GB',
        'image' => 'assets/images/ihone 3.jpg',
        'description' => 'iPhone 12 Pro with A14 Bionic chip, 256GB storage, advanced camera system, and 5G connectivity.',
        'price' => 800000,
        'compare_price' => 950000,
        'sku' => 'IPH12-PRO-256',
        'brand' => 'Apple'
    ],
    [
        'name' => 'iPhone 15 Standard 128GB',
        'image' => 'assets/images/iphone 4.jpg',
        'description' => 'iPhone 15 with USB-C, A16 Bionic chip, 128GB storage, improved cameras and battery life.',
        'price' => 850000,
        'compare_price' => 950000,
        'sku' => 'IPH15-STD-128',
        'brand' => 'Apple'
    ]
];

// Google Pixel Products
$pixel_products = [
    [
        'name' => 'Google Pixel 8 Pro 256GB',
        'image' => 'assets/images/Google Pixel 8 Pro.jpg',
        'description' => 'Google Pixel 8 Pro with Tensor G3 chip, 256GB storage, advanced AI photography features.',
        'price' => 900000,
        'compare_price' => 1000000,
        'sku' => 'PIX8-PRO-256',
        'brand' => 'Google'
    ],
    [
        'name' => 'Google Pixel 7a 128GB',
        'image' => 'assets/images/pixel1.jpg',
        'description' => 'Affordable Pixel 7a with Google Tensor G2, 128GB storage, excellent camera performance.',
        'price' => 520000,
        'compare_price' => 600000,
        'sku' => 'PIX7A-128',
        'brand' => 'Google'
    ],
    [
        'name' => 'Google Pixel 8 128GB',
        'image' => 'assets/images/pixel2.jpg',
        'description' => 'Google Pixel 8 with Tensor G3, 128GB storage, Magic Eraser, and 7 years of updates.',
        'price' => 750000,
        'compare_price' => 850000,
        'sku' => 'PIX8-STD-128',
        'brand' => 'Google'
    ],
    [
        'name' => 'Google Pixel 6 Pro 256GB',
        'image' => 'assets/images/pixel3.jpg',
        'description' => 'Pixel 6 Pro with Tensor chip, 256GB storage, professional photography features.',
        'price' => 650000,
        'compare_price' => 750000,
        'sku' => 'PIX6-PRO-256',
        'brand' => 'Google'
    ],
    [
        'name' => 'Google Pixel 7 Pro 512GB',
        'image' => 'assets/images/pixel4.jpg',
        'description' => 'Pixel 7 Pro with Tensor G2, 512GB storage, telephoto lens, and advanced computational photography.',
        'price' => 800000,
        'compare_price' => 900000,
        'sku' => 'PIX7-PRO-512',
        'brand' => 'Google'
    ],
    [
        'name' => 'Google Pixel 6a 128GB',
        'image' => 'assets/images/pixel5.jpg',
        'description' => 'Budget-friendly Pixel 6a with Google Tensor, 128GB storage, and flagship camera features.',
        'price' => 450000,
        'compare_price' => 550000,
        'sku' => 'PIX6A-128',
        'brand' => 'Google'
    ],
    [
        'name' => 'Google Pixel 5 128GB',
        'image' => 'assets/images/pixel6.jpg',
        'description' => 'Pixel 5 with Snapdragon 765G, 128GB storage, 5G connectivity, and pure Android experience.',
        'price' => 400000,
        'compare_price' => 500000,
        'sku' => 'PIX5-128',
        'brand' => 'Google'
    ]
];

$smartphone_products = array_merge($iphone_products, $pixel_products);

foreach ($smartphone_products as $product) {
    try {
        $vendor = getRandomVendor($vendors);
        $slug = generateSlug($product['name']);
        
        // Check if product already exists
        $existing = $database->fetch("SELECT id FROM products WHERE slug = ?", [$slug]);
        if (!$existing) {
            $database->execute(
                "INSERT INTO products (vendor_id, category_id, name, slug, description, price, compare_price, stock_quantity, image_url, sku, brand, status, created_at) 
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active', NOW())",
                [
                    $vendor['id'],
                    $categories['smartphones'],
                    $product['name'],
                    $slug,
                    $product['description'],
                    $product['price'],
                    $product['compare_price'],
                    rand(10, 50),
                    $product['image'],
                    $product['sku'],
                    $product['brand']
                ]
            );
            $products_added++;
            echo "✅ Added: {$product['name']}<br>";
        } else {
            // Update existing product with image
            $database->execute(
                "UPDATE products SET image_url = ?, description = ?, price = ?, compare_price = ?, brand = ? WHERE slug = ?",
                [$product['image'], $product['description'], $product['price'], $product['compare_price'], $product['brand'], $slug]
            );
            $products_updated++;
            echo "🔄 Updated: {$product['name']}<br>";
        }
    } catch (Exception $e) {
        $errors[] = "Error adding {$product['name']}: " . $e->getMessage();
        echo "❌ Error adding {$product['name']}: " . $e->getMessage() . "<br>";
    }
}

echo "<h2>💻 Adding Laptop Products</h2>";

$laptop_products = [
    [
        'name' => 'MacBook Pro 16-inch M3 Max',
        'image' => 'assets/images/MacBook Pro 16-inch M3 Max.jpg',
        'description' => 'Powerful MacBook Pro with M3 Max chip, 32GB RAM, 1TB SSD. Perfect for professional work and creative tasks.',
        'price' => 2800000,
        'compare_price' => 3200000,
        'sku' => 'MBP-M3-16-1TB',
        'brand' => 'Apple'
    ],
    [
        'name' => 'Dell XPS 13 Plus',
        'image' => 'assets/images/Dell XPS 13 Plus.jpg',
        'description' => 'Ultra-thin Dell XPS 13 with Intel Core i7, 16GB RAM, 512GB SSD. Ideal for business and productivity.',
        'price' => 1400000,
        'compare_price' => 1600000,
        'sku' => 'DELL-XPS13-512',
        'brand' => 'Dell'
    ],
    [
        'name' => 'HP Pavilion Gaming Laptop',
        'image' => 'assets/images/lap1.jpg',
        'description' => 'Gaming laptop with NVIDIA GTX 1650, AMD Ryzen 5, 8GB RAM, 256GB SSD. Great for gaming and multimedia.',
        'price' => 950000,
        'compare_price' => 1100000,
        'sku' => 'HP-PAV-GTX1650',
        'brand' => 'HP'
    ],
    [
        'name' => 'Lenovo ThinkPad X1 Carbon',
        'image' => 'assets/images/lap2.jpg',
        'description' => 'Business laptop with Intel Core i5, 16GB RAM, 512GB SSD. Lightweight and durable for professionals.',
        'price' => 1700000,
        'compare_price' => 1900000,
        'sku' => 'LEN-X1C-512',
        'brand' => 'Lenovo'
    ],
    [
        'name' => 'ASUS ROG Gaming Laptop',
        'image' => 'assets/images/lap3.jpg',
        'description' => 'High-performance gaming laptop with NVIDIA RTX 3060, Intel Core i7, 16GB RAM, 1TB SSD.',
        'price' => 1800000,
        'compare_price' => 2000000,
        'sku' => 'ASUS-ROG-RTX3060',
        'brand' => 'ASUS'
    ],
    [
        'name' => 'Acer Swift 3 Ultrabook',
        'image' => 'assets/images/lap4.jpg',
        'description' => 'Lightweight ultrabook with AMD Ryzen 7, 8GB RAM, 512GB SSD. Perfect for students and professionals.',
        'price' => 850000,
        'compare_price' => 1000000,
        'sku' => 'ACER-SWIFT3-512',
        'brand' => 'Acer'
    ]
];

foreach ($laptop_products as $product) {
    try {
        $vendor = getRandomVendor($vendors);
        $slug = generateSlug($product['name']);
        
        $existing = $database->fetch("SELECT id FROM products WHERE slug = ?", [$slug]);
        if (!$existing) {
            $database->execute(
                "INSERT INTO products (vendor_id, category_id, name, slug, description, price, compare_price, stock_quantity, image_url, sku, brand, status, created_at) 
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active', NOW())",
                [
                    $vendor['id'],
                    $categories['laptops'],
                    $product['name'],
                    $slug,
                    $product['description'],
                    $product['price'],
                    $product['compare_price'],
                    rand(5, 25),
                    $product['image'],
                    $product['sku'],
                    $product['brand']
                ]
            );
            $products_added++;
            echo "✅ Added: {$product['name']}<br>";
        } else {
            $database->execute(
                "UPDATE products SET image_url = ?, description = ?, price = ?, compare_price = ?, brand = ? WHERE slug = ?",
                [$product['image'], $product['description'], $product['price'], $product['compare_price'], $product['brand'], $slug]
            );
            $products_updated++;
            echo "🔄 Updated: {$product['name']}<br>";
        }
    } catch (Exception $e) {
        $errors[] = "Error adding {$product['name']}: " . $e->getMessage();
        echo "❌ Error adding {$product['name']}: " . $e->getMessage() . "<br>";
    }
}

echo "<h2>👕 Adding Fashion Products</h2>";

$fashion_products = [
    [
        'name' => 'Classic Blue Jeans',
        'image' => 'assets/images/jean1.jpg',
        'description' => 'Comfortable classic blue jeans made from premium denim. Perfect fit for casual and semi-formal occasions.',
        'price' => 45000,
        'compare_price' => 55000,
        'sku' => 'JEAN-CLASSIC-BLUE',
        'brand' => 'Denim Co'
    ],
    [
        'name' => 'Slim Fit Dark Jeans',
        'image' => 'assets/images/jean2.jpg',
        'description' => 'Modern slim fit jeans in dark wash. Stylish and comfortable for everyday wear.',
        'price' => 52000,
        'compare_price' => 65000,
        'sku' => 'JEAN-SLIM-DARK',
        'brand' => 'Urban Style'
    ],
    [
        'name' => 'Distressed Vintage Jeans',
        'image' => 'assets/images/jean3.jpg',
        'description' => 'Trendy distressed jeans with vintage look. Perfect for casual outings and street style.',
        'price' => 48000,
        'compare_price' => 58000,
        'sku' => 'JEAN-VINTAGE-DIST',
        'brand' => 'Retro Fashion'
    ],
    [
        'name' => 'High-Waist Skinny Jeans',
        'image' => 'assets/images/jean4.jpg',
        'description' => 'Fashionable high-waist skinny jeans for women. Flattering fit with stretch fabric.',
        'price' => 55000,
        'compare_price' => 68000,
        'sku' => 'JEAN-HW-SKINNY',
        'brand' => 'Fashion Forward'
    ],
    [
        'name' => 'Relaxed Fit Jeans',
        'image' => 'assets/images/jean5.jpg',
        'description' => 'Comfortable relaxed fit jeans for all-day comfort. Classic style that never goes out of fashion.',
        'price' => 42000,
        'compare_price' => 50000,
        'sku' => 'JEAN-RELAXED-FIT',
        'brand' => 'Comfort Wear'
    ],
    [
        'name' => 'Premium Designer Jeans',
        'image' => 'assets/images/jean6.jpg',
        'description' => 'High-end designer jeans with premium finishing. Luxury denim for special occasions.',
        'price' => 85000,
        'compare_price' => 100000,
        'sku' => 'JEAN-PREMIUM-DES',
        'brand' => 'Designer Label'
    ]
];

foreach ($fashion_products as $product) {
    try {
        $vendor = getRandomVendor($vendors);
        $slug = generateSlug($product['name']);
        
        $existing = $database->fetch("SELECT id FROM products WHERE slug = ?", [$slug]);
        if (!$existing) {
            $database->execute(
                "INSERT INTO products (vendor_id, category_id, name, slug, description, price, compare_price, stock_quantity, image_url, sku, brand, status, created_at) 
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active', NOW())",
                [
                    $vendor['id'],
                    $categories['fashion'],
                    $product['name'],
                    $slug,
                    $product['description'],
                    $product['price'],
                    $product['compare_price'],
                    rand(20, 60),
                    $product['image'],
                    $product['sku'],
                    $product['brand']
                ]
            );
            $products_added++;
            echo "✅ Added: {$product['name']}<br>";
        } else {
            $database->execute(
                "UPDATE products SET image_url = ?, description = ?, price = ?, compare_price = ?, brand = ? WHERE slug = ?",
                [$product['image'], $product['description'], $product['price'], $product['compare_price'], $product['brand'], $slug]
            );
            $products_updated++;
            echo "🔄 Updated: {$product['name']}<br>";
        }
    } catch (Exception $e) {
        $errors[] = "Error adding {$product['name']}: " . $e->getMessage();
        echo "❌ Error adding {$product['name']}: " . $e->getMessage() . "<br>";
    }
}

echo "<h2>👟 Adding Footwear Products</h2>";

$footwear_products = [
    [
        'name' => 'Nike Air Max 270',
        'image' => 'assets/images/Nike Air Max 270.jpg',
        'description' => 'Comfortable Nike Air Max sneakers with excellent cushioning. Perfect for running and casual wear.',
        'price' => 140000,
        'compare_price' => 160000,
        'sku' => 'NIKE-AM270-BLK',
        'brand' => 'Nike'
    ],
    [
        'name' => 'Adidas Ultraboost 22',
        'image' => 'assets/images/Adidas Ultraboost 22.jpg',
        'description' => 'Premium Adidas running shoes with Boost technology. Superior comfort and performance.',
        'price' => 175000,
        'compare_price' => 200000,
        'sku' => 'ADIDAS-UB22-WHT',
        'brand' => 'Adidas'
    ],
    [
        'name' => 'Classic White Sneakers',
        'image' => 'assets/images/shoe1.jpg',
        'description' => 'Versatile white sneakers perfect for any casual outfit. Comfortable and stylish.',
        'price' => 85000,
        'compare_price' => 100000,
        'sku' => 'SNEAK-WHT-CLASSIC',
        'brand' => 'Urban Style'
    ],
    [
        'name' => 'Black Running Shoes',
        'image' => 'assets/images/shoe2.jpg',
        'description' => 'Professional running shoes with advanced cushioning and breathable material.',
        'price' => 120000,
        'compare_price' => 140000,
        'sku' => 'RUN-BLK-PRO',
        'brand' => 'SportTech'
    ],
    [
        'name' => 'Canvas High-Top Sneakers',
        'image' => 'assets/images/shoe3.jpg',
        'description' => 'Classic canvas high-top sneakers. Timeless design for casual and street style.',
        'price' => 75000,
        'compare_price' => 90000,
        'sku' => 'CANVAS-HT-CLASSIC',
        'brand' => 'Retro Style'
    ],
    [
        'name' => 'Formal Leather Shoes',
        'image' => 'assets/images/shoe4.jpg',
        'description' => 'Elegant formal leather shoes for business and special occasions. Handcrafted quality.',
        'price' => 110000,
        'compare_price' => 130000,
        'sku' => 'FORMAL-LEATH-BRN',
        'brand' => 'Executive'
    ],
    [
        'name' => 'Casual Canvas Sneakers',
        'image' => 'assets/images/shoe5.jpg',
        'description' => 'Comfortable canvas sneakers for everyday wear. Lightweight and breathable.',
        'price' => 55000,
        'compare_price' => 70000,
        'sku' => 'CANVAS-SNEAK-BLU',
        'brand' => 'Comfort Plus'
    ],
    [
        'name' => 'Sport Training Shoes',
        'image' => 'assets/images/shoe6.jpg',
        'description' => 'Multi-purpose training shoes for gym and outdoor activities. Durable and supportive.',
        'price' => 95000,
        'compare_price' => 115000,
        'sku' => 'SPORT-TRAIN-GRY',
        'brand' => 'FitGear'
    ],
    [
        'name' => 'Lifestyle Sneakers',
        'image' => 'assets/images/shoe7.jpg',
        'description' => 'Trendy lifestyle sneakers with modern design. Perfect for casual outings.',
        'price' => 80000,
        'compare_price' => 95000,
        'sku' => 'LIFE-SNEAK-MIX',
        'brand' => 'Urban Life'
    ],
    [
        'name' => 'Premium Walking Shoes',
        'image' => 'assets/images/shoe8.jpg',
        'description' => 'Premium walking shoes with orthopedic support. Ideal for long walks and daily comfort.',
        'price' => 130000,
        'compare_price' => 150000,
        'sku' => 'WALK-PREM-ORTH',
        'brand' => 'ComfortWalk'
    ],
    [
        'name' => 'Designer Fashion Sneakers',
        'image' => 'assets/images/shoe9.jpg',
        'description' => 'High-end designer sneakers with unique styling. Fashion-forward footwear for trendsetters.',
        'price' => 200000,
        'compare_price' => 250000,
        'sku' => 'DESIGN-FASH-LUX',
        'brand' => 'Designer Label'
    ]
];

foreach ($footwear_products as $product) {
    try {
        $vendor = getRandomVendor($vendors);
        $slug = generateSlug($product['name']);

        $existing = $database->fetch("SELECT id FROM products WHERE slug = ?", [$slug]);
        if (!$existing) {
            $database->execute(
                "INSERT INTO products (vendor_id, category_id, name, slug, description, price, compare_price, stock_quantity, image_url, sku, brand, status, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active', NOW())",
                [
                    $vendor['id'],
                    $categories['shoes'],
                    $product['name'],
                    $slug,
                    $product['description'],
                    $product['price'],
                    $product['compare_price'],
                    rand(15, 40),
                    $product['image'],
                    $product['sku'],
                    $product['brand']
                ]
            );
            $products_added++;
            echo "✅ Added: {$product['name']}<br>";
        } else {
            $database->execute(
                "UPDATE products SET image_url = ?, description = ?, price = ?, compare_price = ?, brand = ? WHERE slug = ?",
                [$product['image'], $product['description'], $product['price'], $product['compare_price'], $product['brand'], $slug]
            );
            $products_updated++;
            echo "🔄 Updated: {$product['name']}<br>";
        }
    } catch (Exception $e) {
        $errors[] = "Error adding {$product['name']}: " . $e->getMessage();
        echo "❌ Error adding {$product['name']}: " . $e->getMessage() . "<br>";
    }
}

echo "<h2>📊 Summary</h2>";
echo "<div style='display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin: 20px 0;'>";

echo "<div style='background: white; border: 2px solid #28a745; padding: 15px; border-radius: 8px; text-align: center;'>";
echo "<h3 style='margin: 0; color: #28a745; font-size: 1.5rem;'>{$products_added}</h3>";
echo "<p style='margin: 5px 0 0 0; color: #666;'>Products Added</p>";
echo "</div>";

echo "<div style='background: white; border: 2px solid #007bff; padding: 15px; border-radius: 8px; text-align: center;'>";
echo "<h3 style='margin: 0; color: #007bff; font-size: 1.5rem;'>{$products_updated}</h3>";
echo "<p style='margin: 5px 0 0 0; color: #666;'>Products Updated</p>";
echo "</div>";

echo "<div style='background: white; border: 2px solid #dc3545; padding: 15px; border-radius: 8px; text-align: center;'>";
echo "<h3 style='margin: 0; color: #dc3545; font-size: 1.5rem;'>" . count($errors) . "</h3>";
echo "<p style='margin: 5px 0 0 0; color: #666;'>Errors</p>";
echo "</div>";

echo "</div>";

if (!empty($errors)) {
    echo "<h3>❌ Errors</h3>";
    echo "<ul>";
    foreach ($errors as $error) {
        echo "<li style='color: #dc3545;'>{$error}</li>";
    }
    echo "</ul>";
}

echo "<h2>🧪 Test Your Products</h2>";
echo "<div style='display: flex; gap: 15px; flex-wrap: wrap; margin: 20px 0;'>";
echo "<a href='index.php' style='background: #28a745; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px;'>🏠 Homepage</a>";
echo "<a href='products.php' style='background: #007bff; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px;'>🛍️ All Products</a>";
echo "<a href='products.php?category=smartphones' style='background: #9c27b0; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px;'>📱 Smartphones</a>";
echo "<a href='products.php?category=laptops' style='background: #3f51b5; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px;'>💻 Laptops</a>";
echo "<a href='products.php?category=fashion' style='background: #e91e63; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px;'>👕 Fashion</a>";
echo "<a href='products.php?category=shoes' style='background: #ff9800; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px;'>👟 Footwear</a>";
echo "</div>";

?>

<style>
body { 
    font-family: Arial, sans-serif; 
    padding: 20px; 
    line-height: 1.6; 
    max-width: 1000px; 
    margin: 0 auto; 
    background: #f8f9fa;
}
h1 { 
    color: #007bff; 
    text-align: center; 
    margin-bottom: 30px; 
}
h2 { 
    color: #333; 
    border-bottom: 2px solid #007bff; 
    padding-bottom: 5px; 
    margin-top: 30px; 
}
</style>
