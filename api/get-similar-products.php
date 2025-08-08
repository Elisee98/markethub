<?php
/**
 * Get Similar Products for Comparison
 */

require_once '../config/config.php';

header('Content-Type: application/json');

// Get current comparison products
$compare_items = $_SESSION['compare_items'] ?? [];

if (empty($compare_items)) {
    echo json_encode(['success' => false, 'message' => 'No products in comparison']);
    exit;
}

try {
    // Get categories of current comparison products
    $placeholders = str_repeat('?,', count($compare_items) - 1) . '?';
    $current_products = $database->fetchAll(
        "SELECT DISTINCT category_id, vendor_id FROM products WHERE id IN ($placeholders)",
        $compare_items
    );
    
    $categories = array_unique(array_column($current_products, 'category_id'));
    $vendors = array_unique(array_column($current_products, 'vendor_id'));
    
    // Get similar products (same category, different vendors, not in comparison)
    $category_placeholders = str_repeat('?,', count($categories) - 1) . '?';
    $compare_placeholders = str_repeat('?,', count($compare_items) - 1) . '?';
    
    $similar_products = $database->fetchAll(
        "SELECT p.*, pi.image_url, c.name as category_name,
                u.username as vendor_name, vs.store_name,
                AVG(pr.rating) as avg_rating, COUNT(pr.id) as review_count
         FROM products p
         LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
         LEFT JOIN categories c ON p.category_id = c.id
         LEFT JOIN users u ON p.vendor_id = u.id
         LEFT JOIN vendor_stores vs ON u.id = vs.vendor_id
         LEFT JOIN product_reviews pr ON p.id = pr.product_id AND pr.status = 'approved'
         WHERE p.category_id IN ($category_placeholders) 
         AND p.id NOT IN ($compare_placeholders)
         AND p.status = 'active'
         GROUP BY p.id
         ORDER BY p.created_at DESC
         LIMIT 8",
        array_merge($categories, $compare_items)
    );
    
    // Get trending products in same categories
    $trending_products = $database->fetchAll(
        "SELECT p.*, pi.image_url, c.name as category_name,
                u.username as vendor_name, vs.store_name,
                AVG(pr.rating) as avg_rating, COUNT(pr.id) as review_count,
                COUNT(oi.id) as sales_count
         FROM products p
         LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
         LEFT JOIN categories c ON p.category_id = c.id
         LEFT JOIN users u ON p.vendor_id = u.id
         LEFT JOIN vendor_stores vs ON u.id = vs.vendor_id
         LEFT JOIN product_reviews pr ON p.id = pr.product_id AND pr.status = 'approved'
         LEFT JOIN order_items oi ON p.id = oi.product_id
         WHERE p.category_id IN ($category_placeholders) 
         AND p.id NOT IN ($compare_placeholders)
         AND p.status = 'active'
         GROUP BY p.id
         HAVING sales_count > 0
         ORDER BY sales_count DESC, avg_rating DESC
         LIMIT 6",
        array_merge($categories, $compare_items)
    );
    
    // Get price range recommendations
    $price_range = $database->fetch(
        "SELECT MIN(price) as min_price, MAX(price) as max_price, AVG(price) as avg_price
         FROM products WHERE id IN ($compare_placeholders)",
        $compare_items
    );
    
    $price_similar = $database->fetchAll(
        "SELECT p.*, pi.image_url, c.name as category_name,
                u.username as vendor_name, vs.store_name,
                AVG(pr.rating) as avg_rating, COUNT(pr.id) as review_count
         FROM products p
         LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
         LEFT JOIN categories c ON p.category_id = c.id
         LEFT JOIN users u ON p.vendor_id = u.id
         LEFT JOIN vendor_stores vs ON u.id = vs.vendor_id
         LEFT JOIN product_reviews pr ON p.id = pr.product_id AND pr.status = 'approved'
         WHERE p.price BETWEEN ? AND ?
         AND p.id NOT IN ($compare_placeholders)
         AND p.status = 'active'
         GROUP BY p.id
         ORDER BY ABS(p.price - ?) ASC
         LIMIT 6",
        array_merge([
            $price_range['min_price'] * 0.8,
            $price_range['max_price'] * 1.2,
        ], $compare_items, [$price_range['avg_price']])
    );
    
    echo json_encode([
        'success' => true,
        'similar_products' => $similar_products,
        'trending_products' => $trending_products,
        'price_similar' => $price_similar,
        'price_range' => $price_range,
        'categories' => $categories
    ]);
    
} catch (Exception $e) {
    error_log("Similar products error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error getting similar products'
    ]);
}
?>
