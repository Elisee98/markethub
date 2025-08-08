<?php
/**
 * MarketHub Marketplace Summary
 * Complete overview of the expanded marketplace
 */

require_once 'config/config.php';

echo "<h1>🏪 MarketHub Marketplace Summary</h1>";

// Get comprehensive statistics
$stats = [
    'categories' => $database->fetch("SELECT COUNT(*) as count FROM categories WHERE status = 'active'")['count'],
    'vendors' => $database->fetch("SELECT COUNT(*) as count FROM users WHERE user_type = 'vendor' AND status = 'active'")['count'],
    'products' => $database->fetch("SELECT COUNT(*) as count FROM products WHERE status = 'active'")['count'],
    'customers' => $database->fetch("SELECT COUNT(*) as count FROM users WHERE user_type = 'customer' AND status = 'active'")['count']
];

echo "<div style='display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin: 30px 0;'>";
echo "<div style='background: linear-gradient(135deg, #10b981, #059669); color: white; padding: 20px; border-radius: 10px; text-align: center;'>";
echo "<h2 style='margin: 0; font-size: 2.5rem;'>{$stats['categories']}</h2>";
echo "<p style='margin: 5px 0 0 0;'>Categories</p>";
echo "</div>";

echo "<div style='background: linear-gradient(135deg, #3b82f6, #1d4ed8); color: white; padding: 20px; border-radius: 10px; text-align: center;'>";
echo "<h2 style='margin: 0; font-size: 2.5rem;'>{$stats['vendors']}</h2>";
echo "<p style='margin: 5px 0 0 0;'>Vendors</p>";
echo "</div>";

echo "<div style='background: linear-gradient(135deg, #f59e0b, #d97706); color: white; padding: 20px; border-radius: 10px; text-align: center;'>";
echo "<h2 style='margin: 0; font-size: 2.5rem;'>{$stats['products']}</h2>";
echo "<p style='margin: 5px 0 0 0;'>Products</p>";
echo "</div>";

echo "<div style='background: linear-gradient(135deg, #8b5cf6, #7c3aed); color: white; padding: 20px; border-radius: 10px; text-align: center;'>";
echo "<h2 style='margin: 0; font-size: 2.5rem;'>{$stats['customers']}</h2>";
echo "<p style='margin: 5px 0 0 0;'>Customers</p>";
echo "</div>";
echo "</div>";

// Categories breakdown
echo "<h2>📂 Categories Overview</h2>";
$categories_detail = $database->fetchAll(
    "SELECT c.name, c.slug, COUNT(p.id) as product_count 
     FROM categories c 
     LEFT JOIN products p ON c.id = p.category_id AND p.status = 'active'
     WHERE c.status = 'active' 
     GROUP BY c.id 
     ORDER BY product_count DESC"
);

echo "<div style='display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px; margin: 20px 0;'>";
foreach ($categories_detail as $category) {
    echo "<div style='background: white; border: 1px solid #e5e7eb; padding: 15px; border-radius: 8px;'>";
    echo "<h4 style='margin: 0 0 10px 0; color: #374151;'>{$category['name']}</h4>";
    echo "<p style='margin: 0; color: #6b7280;'>{$category['product_count']} products</p>";
    echo "<a href='products.php?category={$category['slug']}' style='color: #10b981; text-decoration: none; font-size: 14px;'>Browse →</a>";
    echo "</div>";
}
echo "</div>";

// Vendors overview
echo "<h2>🏪 Vendors Overview</h2>";
$vendors_detail = $database->fetchAll(
    "SELECT u.id, u.username, vs.store_name, COUNT(p.id) as product_count 
     FROM users u 
     LEFT JOIN vendor_stores vs ON u.id = vs.vendor_id 
     LEFT JOIN products p ON u.id = p.vendor_id AND p.status = 'active'
     WHERE u.user_type = 'vendor' AND u.status = 'active' 
     GROUP BY u.id 
     ORDER BY product_count DESC"
);

echo "<div style='display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 15px; margin: 20px 0;'>";
foreach ($vendors_detail as $vendor) {
    echo "<div style='background: white; border: 1px solid #e5e7eb; padding: 15px; border-radius: 8px;'>";
    echo "<h4 style='margin: 0 0 5px 0; color: #374151;'>" . ($vendor['store_name'] ?: $vendor['username']) . "</h4>";
    echo "<p style='margin: 0 0 10px 0; color: #6b7280; font-size: 14px;'>@{$vendor['username']}</p>";
    echo "<p style='margin: 0; color: #10b981; font-weight: 500;'>{$vendor['product_count']} products</p>";
    echo "<a href='vendor.php?id={$vendor['id']}' style='color: #10b981; text-decoration: none; font-size: 14px;'>View Store →</a>";
    echo "</div>";
}
echo "</div>";

// Recent products
echo "<h2>🆕 Recent Products</h2>";
$recent_products = $database->fetchAll(
    "SELECT p.id, p.name, p.price, p.image_url, vs.store_name, u.username 
     FROM products p 
     LEFT JOIN users u ON p.vendor_id = u.id 
     LEFT JOIN vendor_stores vs ON u.id = vs.vendor_id 
     WHERE p.status = 'active' 
     ORDER BY p.created_at DESC 
     LIMIT 8"
);

echo "<div style='display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin: 20px 0;'>";
foreach ($recent_products as $product) {
    echo "<div style='background: white; border: 1px solid #e5e7eb; border-radius: 8px; overflow: hidden;'>";
    
    if ($product['image_url']) {
        echo "<img src='{$product['image_url']}' style='width: 100%; height: 150px; object-fit: cover;'>";
    } else {
        echo "<div style='width: 100%; height: 150px; background: #f3f4f6; display: flex; align-items: center; justify-content: center;'>";
        echo "<i class='fas fa-image' style='font-size: 2rem; color: #9ca3af;'></i>";
        echo "</div>";
    }
    
    echo "<div style='padding: 15px;'>";
    echo "<h5 style='margin: 0 0 5px 0; font-size: 14px; color: #374151;'>" . htmlspecialchars($product['name']) . "</h5>";
    echo "<p style='margin: 0 0 5px 0; color: #10b981; font-weight: 600;'>RWF " . number_format($product['price'], 2) . "</p>";
    echo "<p style='margin: 0; color: #6b7280; font-size: 12px;'>by " . ($product['store_name'] ?: $product['username']) . "</p>";
    echo "<a href='product.php?id={$product['id']}' style='color: #10b981; text-decoration: none; font-size: 12px;'>View Product →</a>";
    echo "</div>";
    echo "</div>";
}
echo "</div>";

// Price ranges
echo "<h2>💰 Price Range Analysis</h2>";
$price_ranges = $database->fetchAll(
    "SELECT 
        CASE 
            WHEN price < 50000 THEN 'Under RWF 50,000'
            WHEN price < 100000 THEN 'RWF 50,000 - 100,000'
            WHEN price < 500000 THEN 'RWF 100,000 - 500,000'
            WHEN price < 1000000 THEN 'RWF 500,000 - 1,000,000'
            ELSE 'Over RWF 1,000,000'
        END as price_range,
        COUNT(*) as product_count
     FROM products 
     WHERE status = 'active' 
     GROUP BY price_range 
     ORDER BY MIN(price)"
);

echo "<div style='background: white; border: 1px solid #e5e7eb; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
foreach ($price_ranges as $range) {
    $percentage = round(($range['product_count'] / $stats['products']) * 100, 1);
    echo "<div style='margin-bottom: 15px;'>";
    echo "<div style='display: flex; justify-content: space-between; margin-bottom: 5px;'>";
    echo "<span style='color: #374151;'>{$range['price_range']}</span>";
    echo "<span style='color: #6b7280;'>{$range['product_count']} products ({$percentage}%)</span>";
    echo "</div>";
    echo "<div style='background: #f3f4f6; height: 8px; border-radius: 4px;'>";
    echo "<div style='background: #10b981; height: 8px; border-radius: 4px; width: {$percentage}%;'></div>";
    echo "</div>";
    echo "</div>";
}
echo "</div>";

// Quick actions
echo "<h2>🚀 Quick Actions</h2>";
echo "<div style='display: flex; gap: 15px; flex-wrap: wrap; margin: 20px 0;'>";
echo "<a href='index.php' style='background: #10b981; color: white; padding: 12px 20px; text-decoration: none; border-radius: 6px;'>🏠 Homepage</a>";
echo "<a href='products.php' style='background: #3b82f6; color: white; padding: 12px 20px; text-decoration: none; border-radius: 6px;'>🛍️ Browse Products</a>";
echo "<a href='vendors.php' style='background: #8b5cf6; color: white; padding: 12px 20px; text-decoration: none; border-radius: 6px;'>🏪 View Vendors</a>";
echo "<a href='vendor/spa-dashboard.php' style='background: #f59e0b; color: white; padding: 12px 20px; text-decoration: none; border-radius: 6px;'>⚙️ Vendor Dashboard</a>";
echo "</div>";

echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; padding: 20px; border-radius: 8px; margin: 30px 0;'>";
echo "<h3 style='color: #155724; margin: 0 0 10px 0;'>🎉 Marketplace Expansion Complete!</h3>";
echo "<p style='color: #155724; margin: 0;'>Your MarketHub platform now has a comprehensive product catalog with multiple categories, diverse vendors, and a wide range of products. The marketplace is ready to serve customers with a professional e-commerce experience!</p>";
echo "</div>";

?>

<style>
body { font-family: Arial, sans-serif; padding: 20px; line-height: 1.6; max-width: 1200px; margin: 0 auto; background: #f8fafc; }
h1 { color: #10b981; text-align: center; margin-bottom: 30px; }
h2 { color: #374151; border-bottom: 2px solid #e5e7eb; padding-bottom: 5px; margin-top: 40px; }
</style>
