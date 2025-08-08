<?php
/**
 * Final MarketHub Marketplace Status
 * Complete overview of the fully expanded marketplace
 */

require_once 'config/config.php';

echo "<h1>🎉 MarketHub Marketplace - Final Status</h1>";

// Get comprehensive statistics
$stats = [
    'categories' => $database->fetch("SELECT COUNT(*) as count FROM categories WHERE status = 'active'")['count'],
    'vendors' => $database->fetch("SELECT COUNT(*) as count FROM users WHERE user_type = 'vendor' AND status = 'active'")['count'],
    'products' => $database->fetch("SELECT COUNT(*) as count FROM products WHERE status = 'active'")['count'],
    'customers' => $database->fetch("SELECT COUNT(*) as count FROM users WHERE user_type = 'customer' AND status = 'active'")['count']
];

echo "<div style='background: linear-gradient(135deg, #10b981, #059669); color: white; padding: 30px; border-radius: 15px; text-align: center; margin: 30px 0;'>";
echo "<h2 style='margin: 0 0 20px 0; font-size: 2rem;'>🏪 MarketHub Marketplace</h2>";
echo "<p style='margin: 0; font-size: 1.2rem; opacity: 0.9;'>Professional E-Commerce Platform for Rwanda</p>";
echo "</div>";

echo "<div style='display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin: 30px 0;'>";

echo "<div style='background: white; border: 2px solid #10b981; padding: 20px; border-radius: 10px; text-align: center;'>";
echo "<h2 style='margin: 0; font-size: 2.5rem; color: #10b981;'>{$stats['categories']}</h2>";
echo "<p style='margin: 5px 0 0 0; color: #374151; font-weight: 500;'>Product Categories</p>";
echo "</div>";

echo "<div style='background: white; border: 2px solid #3b82f6; padding: 20px; border-radius: 10px; text-align: center;'>";
echo "<h2 style='margin: 0; font-size: 2.5rem; color: #3b82f6;'>{$stats['vendors']}</h2>";
echo "<p style='margin: 5px 0 0 0; color: #374151; font-weight: 500;'>Active Vendors</p>";
echo "</div>";

echo "<div style='background: white; border: 2px solid #f59e0b; padding: 20px; border-radius: 10px; text-align: center;'>";
echo "<h2 style='margin: 0; font-size: 2.5rem; color: #f59e0b;'>{$stats['products']}</h2>";
echo "<p style='margin: 5px 0 0 0; color: #374151; font-weight: 500;'>Available Products</p>";
echo "</div>";

echo "<div style='background: white; border: 2px solid #8b5cf6; padding: 20px; border-radius: 10px; text-align: center;'>";
echo "<h2 style='margin: 0; font-size: 2.5rem; color: #8b5cf6;'>{$stats['customers']}</h2>";
echo "<p style='margin: 5px 0 0 0; color: #374151; font-weight: 500;'>Registered Customers</p>";
echo "</div>";

echo "</div>";

// Categories breakdown with product counts
echo "<h2>📂 Product Categories</h2>";
$categories_detail = $database->fetchAll(
    "SELECT c.name, c.slug, c.description, COUNT(p.id) as product_count 
     FROM categories c 
     LEFT JOIN products p ON c.id = p.category_id AND p.status = 'active'
     WHERE c.status = 'active' 
     GROUP BY c.id 
     ORDER BY product_count DESC"
);

echo "<div style='display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; margin: 20px 0;'>";
foreach ($categories_detail as $category) {
    echo "<div style='background: white; border: 1px solid #e5e7eb; padding: 20px; border-radius: 10px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);'>";
    echo "<h4 style='margin: 0 0 10px 0; color: #374151; font-size: 1.2rem;'>{$category['name']}</h4>";
    echo "<p style='margin: 0 0 15px 0; color: #6b7280; font-size: 0.9rem;'>{$category['description']}</p>";
    echo "<div style='display: flex; justify-content: space-between; align-items: center;'>";
    echo "<span style='color: #10b981; font-weight: 600;'>{$category['product_count']} products</span>";
    echo "<a href='products.php?category={$category['slug']}' style='color: #10b981; text-decoration: none; font-size: 14px; padding: 5px 10px; border: 1px solid #10b981; border-radius: 5px;'>Browse →</a>";
    echo "</div>";
    echo "</div>";
}
echo "</div>";

// Top vendors by product count
echo "<h2>🏪 Top Vendors</h2>";
$top_vendors = $database->fetchAll(
    "SELECT u.id, u.username, vs.store_name, vs.store_description, COUNT(p.id) as product_count 
     FROM users u 
     LEFT JOIN vendor_stores vs ON u.id = vs.vendor_id 
     LEFT JOIN products p ON u.id = p.vendor_id AND p.status = 'active'
     WHERE u.user_type = 'vendor' AND u.status = 'active' 
     GROUP BY u.id 
     ORDER BY product_count DESC
     LIMIT 6"
);

echo "<div style='display: grid; grid-template-columns: repeat(auto-fit, minmax(350px, 1fr)); gap: 20px; margin: 20px 0;'>";
foreach ($top_vendors as $vendor) {
    echo "<div style='background: white; border: 1px solid #e5e7eb; padding: 20px; border-radius: 10px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);'>";
    echo "<div style='display: flex; align-items: center; margin-bottom: 15px;'>";
    echo "<div style='width: 50px; height: 50px; background: #10b981; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-right: 15px;'>";
    echo "<i class='fas fa-store' style='color: white; font-size: 1.2rem;'></i>";
    echo "</div>";
    echo "<div>";
    echo "<h4 style='margin: 0; color: #374151;'>" . ($vendor['store_name'] ?: $vendor['username']) . "</h4>";
    echo "<p style='margin: 0; color: #6b7280; font-size: 0.9rem;'>@{$vendor['username']}</p>";
    echo "</div>";
    echo "</div>";
    
    if ($vendor['store_description']) {
        echo "<p style='margin: 0 0 15px 0; color: #6b7280; font-size: 0.9rem;'>" . substr($vendor['store_description'], 0, 100) . "...</p>";
    }
    
    echo "<div style='display: flex; justify-content: space-between; align-items: center;'>";
    echo "<span style='color: #10b981; font-weight: 600;'>{$vendor['product_count']} products</span>";
    echo "<a href='vendor.php?id={$vendor['id']}' style='color: #10b981; text-decoration: none; font-size: 14px; padding: 5px 10px; border: 1px solid #10b981; border-radius: 5px;'>View Store →</a>";
    echo "</div>";
    echo "</div>";
}
echo "</div>";

// Price range distribution
echo "<h2>💰 Price Range Distribution</h2>";
$price_ranges = $database->fetchAll(
    "SELECT 
        CASE 
            WHEN price < 50000 THEN 'Under RWF 50K'
            WHEN price < 100000 THEN 'RWF 50K - 100K'
            WHEN price < 500000 THEN 'RWF 100K - 500K'
            WHEN price < 1000000 THEN 'RWF 500K - 1M'
            ELSE 'Over RWF 1M'
        END as price_range,
        COUNT(*) as product_count,
        MIN(price) as min_price,
        MAX(price) as max_price
     FROM products 
     WHERE status = 'active' 
     GROUP BY price_range 
     ORDER BY MIN(price)"
);

echo "<div style='background: white; border: 1px solid #e5e7eb; padding: 25px; border-radius: 10px; margin: 20px 0; box-shadow: 0 2px 4px rgba(0,0,0,0.1);'>";
foreach ($price_ranges as $range) {
    $percentage = round(($range['product_count'] / $stats['products']) * 100, 1);
    echo "<div style='margin-bottom: 20px;'>";
    echo "<div style='display: flex; justify-content: space-between; margin-bottom: 8px;'>";
    echo "<span style='color: #374151; font-weight: 500;'>{$range['price_range']}</span>";
    echo "<span style='color: #6b7280;'>{$range['product_count']} products ({$percentage}%)</span>";
    echo "</div>";
    echo "<div style='background: #f3f4f6; height: 10px; border-radius: 5px; overflow: hidden;'>";
    echo "<div style='background: linear-gradient(90deg, #10b981, #059669); height: 10px; width: {$percentage}%; transition: width 0.3s ease;'></div>";
    echo "</div>";
    echo "</div>";
}
echo "</div>";

// Quick navigation
echo "<h2>🚀 Explore MarketHub</h2>";
echo "<div style='display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin: 20px 0;'>";

$nav_items = [
    ['url' => 'index.php', 'title' => '🏠 Homepage', 'desc' => 'Browse featured products', 'color' => '#10b981'],
    ['url' => 'products.php', 'title' => '🛍️ All Products', 'desc' => 'Complete product catalog', 'color' => '#3b82f6'],
    ['url' => 'vendors.php', 'title' => '🏪 Vendors', 'desc' => 'Meet our vendors', 'color' => '#8b5cf6'],
    ['url' => 'vendor/spa-dashboard.php', 'title' => '⚙️ Vendor Dashboard', 'desc' => 'Manage your store', 'color' => '#f59e0b']
];

foreach ($nav_items as $item) {
    echo "<a href='{$item['url']}' style='text-decoration: none; color: inherit;'>";
    echo "<div style='background: white; border: 2px solid {$item['color']}; padding: 20px; border-radius: 10px; text-align: center; transition: transform 0.2s; cursor: pointer;' onmouseover='this.style.transform=\"translateY(-2px)\"' onmouseout='this.style.transform=\"translateY(0)\"'>";
    echo "<h4 style='margin: 0 0 10px 0; color: {$item['color']};'>{$item['title']}</h4>";
    echo "<p style='margin: 0; color: #6b7280; font-size: 0.9rem;'>{$item['desc']}</p>";
    echo "</div>";
    echo "</a>";
}
echo "</div>";

// Success message
echo "<div style='background: linear-gradient(135deg, #d4edda, #c3e6cb); border: 2px solid #28a745; padding: 30px; border-radius: 15px; margin: 40px 0; text-align: center;'>";
echo "<h3 style='color: #155724; margin: 0 0 15px 0; font-size: 1.5rem;'>🎉 Marketplace Expansion Complete!</h3>";
echo "<p style='color: #155724; margin: 0 0 20px 0; font-size: 1.1rem;'>MarketHub is now a comprehensive e-commerce platform with diverse products, multiple vendors, and professional features.</p>";
echo "<div style='display: flex; justify-content: center; gap: 15px; flex-wrap: wrap;'>";
echo "<span style='background: #28a745; color: white; padding: 8px 15px; border-radius: 20px; font-size: 0.9rem;'>✅ Multi-Vendor Platform</span>";
echo "<span style='background: #28a745; color: white; padding: 8px 15px; border-radius: 20px; font-size: 0.9rem;'>✅ Professional Design</span>";
echo "<span style='background: #28a745; color: white; padding: 8px 15px; border-radius: 20px; font-size: 0.9rem;'>✅ Complete Product Catalog</span>";
echo "<span style='background: #28a745; color: white; padding: 8px 15px; border-radius: 20px; font-size: 0.9rem;'>✅ Ready for Production</span>";
echo "</div>";
echo "</div>";

?>

<style>
body { 
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
    padding: 20px; 
    line-height: 1.6; 
    max-width: 1200px; 
    margin: 0 auto; 
    background: #f8fafc; 
}
h1 { 
    color: #10b981; 
    text-align: center; 
    margin-bottom: 30px; 
    font-size: 2.5rem;
}
h2 { 
    color: #374151; 
    border-bottom: 3px solid #10b981; 
    padding-bottom: 10px; 
    margin-top: 40px; 
    font-size: 1.5rem;
}
</style>
