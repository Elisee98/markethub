<?php
/**
 * Fix Products and Categories
 * Resolve issues with product status and category assignments
 */

require_once 'config/config.php';

echo "<h1>🔧 Fixing Products and Categories</h1>";

$fixes_applied = [];
$errors = [];

// Fix 1: Update products with empty status to 'active'
echo "<h2>Fix 1: Product Status Issues</h2>";
try {
    $products_with_empty_status = $database->fetchAll(
        "SELECT id, name, status FROM products WHERE status = '' OR status IS NULL"
    );
    
    foreach ($products_with_empty_status as $product) {
        $database->execute(
            "UPDATE products SET status = 'active' WHERE id = ?",
            [$product['id']]
        );
        $fixes_applied[] = "Fixed status for product: {$product['name']}";
        echo "✅ Fixed status for product: {$product['name']}<br>";
    }
    
    if (empty($products_with_empty_status)) {
        echo "ℹ️ All products have valid status<br>";
    }
} catch (Exception $e) {
    $errors[] = "Error fixing product status: " . $e->getMessage();
    echo "❌ Error: " . $e->getMessage() . "<br>";
}

// Fix 2: Move misplaced products to correct categories
echo "<h2>Fix 2: Category Assignment Issues</h2>";
try {
    // Get Fashion & Clothing category ID
    $fashion_category = $database->fetch("SELECT id FROM categories WHERE slug = 'fashion' OR name LIKE '%Fashion%'");
    
    if ($fashion_category) {
        $fashion_category_id = $fashion_category['id'];
        
        // Fix product 92 - move to Fashion category
        $misplaced_product = $database->fetch("SELECT id, name FROM products WHERE id = 92");
        if ($misplaced_product) {
            $database->execute(
                "UPDATE products SET category_id = ? WHERE id = 92",
                [$fashion_category_id]
            );
            $fixes_applied[] = "Moved product 92 to Fashion & Clothing category";
            echo "✅ Moved product 92 to Fashion & Clothing category<br>";
        }
    }
} catch (Exception $e) {
    $errors[] = "Error fixing categories: " . $e->getMessage();
    echo "❌ Error: " . $e->getMessage() . "<br>";
}

// Fix 3: Add more fashion products to populate the category
echo "<h2>Fix 3: Adding More Fashion Products</h2>";
try {
    $fashion_category = $database->fetch("SELECT id FROM categories WHERE slug = 'fashion'");
    $active_vendors = $database->fetchAll("SELECT id, username FROM users WHERE user_type = 'vendor' AND status = 'active'");
    
    if ($fashion_category && !empty($active_vendors)) {
        $fashion_products = [
            [
                'name' => 'Classic Blue Jeans',
                'slug' => 'classic-blue-jeans',
                'description' => 'Comfortable classic blue jeans made from premium denim. Perfect fit for casual and semi-formal occasions.',
                'price' => 45000,
                'compare_price' => 55000,
                'stock_quantity' => 50,
                'sku' => 'JEAN-CLASSIC-BLUE',
                'image_url' => 'assets/images/jean1.jpg'
            ],
            [
                'name' => 'Slim Fit Dark Jeans',
                'slug' => 'slim-fit-dark-jeans',
                'description' => 'Modern slim fit jeans in dark wash. Stylish and comfortable for everyday wear.',
                'price' => 52000,
                'compare_price' => 65000,
                'stock_quantity' => 35,
                'sku' => 'JEAN-SLIM-DARK',
                'image_url' => 'assets/images/jean2.jpg'
            ],
            [
                'name' => 'Distressed Vintage Jeans',
                'slug' => 'distressed-vintage-jeans',
                'description' => 'Trendy distressed jeans with vintage look. Perfect for casual outings and street style.',
                'price' => 48000,
                'compare_price' => 58000,
                'stock_quantity' => 25,
                'sku' => 'JEAN-VINTAGE-DIST',
                'image_url' => 'assets/images/jean3.jpg'
            ],
            [
                'name' => 'High-Waist Skinny Jeans',
                'slug' => 'high-waist-skinny-jeans',
                'description' => 'Fashionable high-waist skinny jeans for women. Flattering fit with stretch fabric.',
                'price' => 55000,
                'compare_price' => 68000,
                'stock_quantity' => 40,
                'sku' => 'JEAN-HW-SKINNY',
                'image_url' => 'assets/images/jean4.jpg'
            ],
            [
                'name' => 'Relaxed Fit Jeans',
                'slug' => 'relaxed-fit-jeans',
                'description' => 'Comfortable relaxed fit jeans for all-day comfort. Classic style that never goes out of fashion.',
                'price' => 42000,
                'compare_price' => 50000,
                'stock_quantity' => 30,
                'sku' => 'JEAN-RELAXED-FIT',
                'image_url' => 'assets/images/jean5.jpg'
            ],
            [
                'name' => 'Premium Designer Jeans',
                'slug' => 'premium-designer-jeans',
                'description' => 'High-end designer jeans with premium finishing. Luxury denim for special occasions.',
                'price' => 85000,
                'compare_price' => 100000,
                'stock_quantity' => 15,
                'sku' => 'JEAN-PREMIUM-DES',
                'image_url' => 'assets/images/jean6.jpg'
            ]
        ];
        
        foreach ($fashion_products as $product) {
            // Check if product already exists
            $existing = $database->fetch("SELECT id FROM products WHERE slug = ?", [$product['slug']]);
            if (!$existing) {
                $vendor = $active_vendors[array_rand($active_vendors)];
                
                $database->execute(
                    "INSERT INTO products (vendor_id, category_id, name, slug, description, price, compare_price, stock_quantity, sku, image_url, status, created_at) 
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active', NOW())",
                    [
                        $vendor['id'],
                        $fashion_category['id'],
                        $product['name'],
                        $product['slug'],
                        $product['description'],
                        $product['price'],
                        $product['compare_price'],
                        $product['stock_quantity'],
                        $product['sku'],
                        $product['image_url']
                    ]
                );
                
                $fixes_applied[] = "Added fashion product: {$product['name']}";
                echo "✅ Added fashion product: {$product['name']}<br>";
            } else {
                echo "ℹ️ Fashion product already exists: {$product['name']}<br>";
            }
        }
    }
} catch (Exception $e) {
    $errors[] = "Error adding fashion products: " . $e->getMessage();
    echo "❌ Error: " . $e->getMessage() . "<br>";
}

// Fix 4: Add products to other categories as well
echo "<h2>Fix 4: Adding Products to Other Categories</h2>";
try {
    $categories = $database->fetchAll("SELECT id, name, slug FROM categories WHERE status = 'active' AND slug IN ('smartphones', 'shoes', 'laptops')");
    $active_vendors = $database->fetchAll("SELECT id, username FROM users WHERE user_type = 'vendor' AND status = 'active'");
    
    foreach ($categories as $category) {
        $existing_count = $database->fetch(
            "SELECT COUNT(*) as count FROM products WHERE category_id = ? AND status = 'active'",
            [$category['id']]
        )['count'];
        
        if ($existing_count < 3) { // Add products if less than 3 exist
            $products_to_add = [];
            
            switch ($category['slug']) {
                case 'smartphones':
                    $products_to_add = [
                        [
                            'name' => 'iPhone 15 Pro Max',
                            'slug' => 'iphone-15-pro-max-new',
                            'description' => 'Latest iPhone 15 Pro Max with A17 Pro chip, 256GB storage, Pro camera system.',
                            'price' => 1200000,
                            'sku' => 'IPH15-PM-256-NEW'
                        ],
                        [
                            'name' => 'Google Pixel 8 Pro',
                            'slug' => 'google-pixel-8-pro-new',
                            'description' => 'Google Pixel 8 Pro with Tensor G3 chip, 256GB storage, advanced AI photography.',
                            'price' => 800000,
                            'sku' => 'PIX8-PRO-256-NEW'
                        ]
                    ];
                    break;
                    
                case 'shoes':
                    $products_to_add = [
                        [
                            'name' => 'Nike Air Max 270',
                            'slug' => 'nike-air-max-270-new',
                            'description' => 'Comfortable Nike Air Max sneakers with excellent cushioning.',
                            'price' => 120000,
                            'sku' => 'NIKE-AM270-NEW'
                        ],
                        [
                            'name' => 'Adidas Ultraboost 22',
                            'slug' => 'adidas-ultraboost-22-new',
                            'description' => 'Premium Adidas running shoes with Boost technology.',
                            'price' => 150000,
                            'sku' => 'ADIDAS-UB22-NEW'
                        ]
                    ];
                    break;
                    
                case 'laptops':
                    $products_to_add = [
                        [
                            'name' => 'Dell XPS 13 Plus',
                            'slug' => 'dell-xps-13-plus-new',
                            'description' => 'Ultra-thin Dell XPS 13 with Intel Core i7, 16GB RAM, 512GB SSD.',
                            'price' => 1200000,
                            'sku' => 'DELL-XPS13-NEW'
                        ]
                    ];
                    break;
            }
            
            foreach ($products_to_add as $product) {
                $existing = $database->fetch("SELECT id FROM products WHERE slug = ?", [$product['slug']]);
                if (!$existing) {
                    $vendor = $active_vendors[array_rand($active_vendors)];
                    
                    $database->execute(
                        "INSERT INTO products (vendor_id, category_id, name, slug, description, price, stock_quantity, sku, status, created_at) 
                         VALUES (?, ?, ?, ?, ?, ?, 20, ?, 'active', NOW())",
                        [
                            $vendor['id'],
                            $category['id'],
                            $product['name'],
                            $product['slug'],
                            $product['description'],
                            $product['price'],
                            $product['sku']
                        ]
                    );
                    
                    $fixes_applied[] = "Added {$category['name']} product: {$product['name']}";
                    echo "✅ Added {$category['name']} product: {$product['name']}<br>";
                }
            }
        }
    }
} catch (Exception $e) {
    $errors[] = "Error adding category products: " . $e->getMessage();
    echo "❌ Error: " . $e->getMessage() . "<br>";
}

// Verification
echo "<h2>📊 Final Verification</h2>";
try {
    $category_stats = $database->fetchAll(
        "SELECT c.name, c.slug, COUNT(p.id) as product_count
         FROM categories c
         LEFT JOIN products p ON c.id = p.category_id AND p.status = 'active'
         WHERE c.status = 'active'
         GROUP BY c.id
         ORDER BY product_count DESC"
    );
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%; margin: 20px 0;'>";
    echo "<tr style='background: #f8f9fa;'>";
    echo "<th style='padding: 10px;'>Category</th>";
    echo "<th style='padding: 10px;'>Slug</th>";
    echo "<th style='padding: 10px;'>Active Products</th>";
    echo "</tr>";
    
    foreach ($category_stats as $stat) {
        $color = $stat['product_count'] > 0 ? '#28a745' : '#dc3545';
        echo "<tr>";
        echo "<td style='padding: 8px;'>{$stat['name']}</td>";
        echo "<td style='padding: 8px;'>{$stat['slug']}</td>";
        echo "<td style='padding: 8px; color: {$color}; font-weight: bold;'>{$stat['product_count']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
} catch (Exception $e) {
    echo "❌ Error getting verification data: " . $e->getMessage() . "<br>";
}

// Summary
echo "<h2>📋 Fix Summary</h2>";

if (!empty($fixes_applied)) {
    echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h3 style='color: #155724; margin: 0 0 10px 0;'>✅ Fixes Applied (" . count($fixes_applied) . ")</h3>";
    echo "<ul style='color: #155724; margin: 0;'>";
    foreach ($fixes_applied as $fix) {
        echo "<li>{$fix}</li>";
    }
    echo "</ul>";
    echo "</div>";
}

if (!empty($errors)) {
    echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h3 style='color: #721c24; margin: 0 0 10px 0;'>❌ Errors (" . count($errors) . ")</h3>";
    echo "<ul style='color: #721c24; margin: 0;'>";
    foreach ($errors as $error) {
        echo "<li>{$error}</li>";
    }
    echo "</ul>";
    echo "</div>";
}

echo "<h2>🧪 Test the Fixed Categories</h2>";
echo "<div style='display: flex; gap: 15px; flex-wrap: wrap; margin: 20px 0;'>";
echo "<a href='products.php' style='background: #007bff; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px;'>🛍️ All Products</a>";
echo "<a href='products.php?category=fashion' style='background: #e91e63; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px;'>👗 Fashion & Clothing</a>";
echo "<a href='products.php?category=smartphones' style='background: #9c27b0; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px;'>📱 Smartphones</a>";
echo "<a href='products.php?category=laptops' style='background: #3f51b5; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px;'>💻 Laptops</a>";
echo "<a href='products.php?category=shoes' style='background: #ff9800; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px;'>👟 Shoes</a>";
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
table {
    font-size: 14px;
}
th {
    background: #f8f9fa !important;
}
</style>
