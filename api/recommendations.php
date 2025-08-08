<?php
/**
 * MarketHub Smart Recommendations API
 * AI-Powered Product & Vendor Recommendations
 */

require_once '../config/config.php';

header('Content-Type: application/json');

// Allow GET requests for recommendations
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$type = $_GET['type'] ?? 'products';
$user_id = isLoggedIn() ? $_SESSION['user_id'] : null;
$product_id = intval($_GET['product_id'] ?? 0);
$category_id = intval($_GET['category_id'] ?? 0);
$limit = min(20, max(1, intval($_GET['limit'] ?? 8)));

try {
    switch ($type) {
        case 'products':
            $recommendations = getProductRecommendations($user_id, $product_id, $category_id, $limit);
            break;
            
        case 'vendors':
            $recommendations = getVendorRecommendations($user_id, $limit);
            break;
            
        case 'popular':
            $recommendations = getPopularProducts($category_id, $limit);
            break;
            
        case 'trending':
            $recommendations = getTrendingProducts($limit);
            break;
            
        case 'similar':
            if (!$product_id) {
                echo json_encode(['success' => false, 'message' => 'Product ID required for similar products']);
                exit;
            }
            $recommendations = getSimilarProducts($product_id, $limit);
            break;
            
        case 'cross_sell':
            if (!$product_id) {
                echo json_encode(['success' => false, 'message' => 'Product ID required for cross-sell']);
                exit;
            }
            $recommendations = getCrossSellProducts($product_id, $limit);
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid recommendation type']);
            exit;
    }
    
    echo json_encode([
        'success' => true,
        'type' => $type,
        'recommendations' => $recommendations,
        'count' => count($recommendations)
    ]);
    
} catch (Exception $e) {
    error_log("Recommendations API Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred']);
}

/**
 * Get personalized product recommendations for a user
 */
function getProductRecommendations($user_id, $product_id = null, $category_id = null, $limit = 8) {
    global $database;
    
    $recommendations = [];
    
    if ($user_id) {
        // Collaborative filtering - users who liked similar products
        $collaborative = getCollaborativeRecommendations($user_id, $limit / 2);
        $recommendations = array_merge($recommendations, $collaborative);
        
        // Content-based filtering - similar to user's interests
        $content_based = getContentBasedRecommendations($user_id, $limit / 2);
        $recommendations = array_merge($recommendations, $content_based);
    }
    
    // Fill remaining slots with popular products
    $remaining = $limit - count($recommendations);
    if ($remaining > 0) {
        $popular = getPopularProducts($category_id, $remaining, array_column($recommendations, 'id'));
        $recommendations = array_merge($recommendations, $popular);
    }
    
    // Remove duplicates and limit results
    $seen_ids = [];
    $unique_recommendations = [];
    
    foreach ($recommendations as $item) {
        if (!in_array($item['id'], $seen_ids) && count($unique_recommendations) < $limit) {
            $seen_ids[] = $item['id'];
            $unique_recommendations[] = $item;
        }
    }
    
    return $unique_recommendations;
}

/**
 * Collaborative filtering recommendations
 */
function getCollaborativeRecommendations($user_id, $limit = 4) {
    global $database;
    
    $sql = "
        SELECT p.id, p.name, p.price, p.image_url, p.slug, p.brand,
               u.username as vendor_name, vs.store_name, c.name as category_name,
               COUNT(ui2.user_id) as similarity_score,
               'collaborative' as recommendation_type
        FROM user_interactions ui1
        JOIN user_interactions ui2 ON ui1.product_id = ui2.product_id AND ui1.user_id != ui2.user_id
        JOIN products p ON ui2.product_id = p.id
        JOIN users u ON p.vendor_id = u.id
        LEFT JOIN vendor_stores vs ON u.id = vs.vendor_id
        LEFT JOIN categories c ON p.category_id = c.id
        WHERE ui1.user_id = ? 
          AND ui1.interaction_type IN ('wishlist', 'cart', 'purchase')
          AND ui2.interaction_type IN ('wishlist', 'cart', 'purchase')
          AND p.status = 'active' 
          AND u.status = 'active'
          AND p.id NOT IN (
              SELECT product_id FROM user_interactions 
              WHERE user_id = ? AND interaction_type IN ('wishlist', 'cart', 'purchase')
          )
        GROUP BY p.id
        ORDER BY similarity_score DESC, p.created_at DESC
        LIMIT ?
    ";
    
    return $database->fetchAll($sql, [$user_id, $user_id, $limit]);
}

/**
 * Content-based filtering recommendations
 */
function getContentBasedRecommendations($user_id, $limit = 4) {
    global $database;
    
    // Get user's preferred categories and brands
    $user_preferences = $database->fetchAll("
        SELECT p.category_id, p.brand, COUNT(*) as interaction_count
        FROM user_interactions ui
        JOIN products p ON ui.product_id = p.id
        WHERE ui.user_id = ? AND ui.interaction_type IN ('wishlist', 'cart', 'purchase')
        GROUP BY p.category_id, p.brand
        ORDER BY interaction_count DESC
        LIMIT 5
    ", [$user_id]);
    
    if (empty($user_preferences)) {
        return [];
    }
    
    $category_ids = array_unique(array_column($user_preferences, 'category_id'));
    $brands = array_unique(array_filter(array_column($user_preferences, 'brand')));
    
    $where_conditions = [];
    $params = [];
    
    if (!empty($category_ids)) {
        $placeholders = str_repeat('?,', count($category_ids) - 1) . '?';
        $where_conditions[] = "p.category_id IN ($placeholders)";
        $params = array_merge($params, $category_ids);
    }
    
    if (!empty($brands)) {
        $placeholders = str_repeat('?,', count($brands) - 1) . '?';
        $where_conditions[] = "p.brand IN ($placeholders)";
        $params = array_merge($params, $brands);
    }
    
    $where_clause = implode(' OR ', $where_conditions);
    $params[] = $user_id;
    $params[] = $limit;
    
    $sql = "
        SELECT p.id, p.name, p.price, p.image_url, p.slug, p.brand,
               u.username as vendor_name, vs.store_name, c.name as category_name,
               'content_based' as recommendation_type
        FROM products p
        JOIN users u ON p.vendor_id = u.id
        LEFT JOIN vendor_stores vs ON u.id = vs.vendor_id
        LEFT JOIN categories c ON p.category_id = c.id
        WHERE ($where_clause)
          AND p.status = 'active' 
          AND u.status = 'active'
          AND p.id NOT IN (
              SELECT product_id FROM user_interactions 
              WHERE user_id = ? AND interaction_type IN ('wishlist', 'cart', 'purchase')
          )
        ORDER BY p.created_at DESC
        LIMIT ?
    ";
    
    return $database->fetchAll($sql, $params);
}

/**
 * Get popular products
 */
function getPopularProducts($category_id = null, $limit = 8, $exclude_ids = []) {
    global $database;
    
    $where_conditions = ["p.status = 'active'", "u.status = 'active'"];
    $params = [];
    
    if ($category_id) {
        $where_conditions[] = "p.category_id = ?";
        $params[] = $category_id;
    }
    
    if (!empty($exclude_ids)) {
        $placeholders = str_repeat('?,', count($exclude_ids) - 1) . '?';
        $where_conditions[] = "p.id NOT IN ($placeholders)";
        $params = array_merge($params, $exclude_ids);
    }
    
    $where_clause = implode(' AND ', $where_conditions);
    $params[] = $limit;
    
    $sql = "
        SELECT p.id, p.name, p.price, p.image_url, p.slug, p.brand,
               u.username as vendor_name, vs.store_name, c.name as category_name,
               COUNT(ui.id) as interaction_count,
               'popular' as recommendation_type
        FROM products p
        JOIN users u ON p.vendor_id = u.id
        LEFT JOIN vendor_stores vs ON u.id = vs.vendor_id
        LEFT JOIN categories c ON p.category_id = c.id
        LEFT JOIN user_interactions ui ON p.id = ui.product_id
        WHERE $where_clause
        GROUP BY p.id
        ORDER BY interaction_count DESC, p.created_at DESC
        LIMIT ?
    ";
    
    return $database->fetchAll($sql, $params);
}

/**
 * Get trending products (recent interactions)
 */
function getTrendingProducts($limit = 8) {
    global $database;
    
    $sql = "
        SELECT p.id, p.name, p.price, p.image_url, p.slug, p.brand,
               u.username as vendor_name, vs.store_name, c.name as category_name,
               COUNT(ui.id) as recent_interactions,
               'trending' as recommendation_type
        FROM products p
        JOIN users u ON p.vendor_id = u.id
        LEFT JOIN vendor_stores vs ON u.id = vs.vendor_id
        LEFT JOIN categories c ON p.category_id = c.id
        LEFT JOIN user_interactions ui ON p.id = ui.product_id AND ui.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        WHERE p.status = 'active' AND u.status = 'active'
        GROUP BY p.id
        HAVING recent_interactions > 0
        ORDER BY recent_interactions DESC, p.created_at DESC
        LIMIT ?
    ";
    
    return $database->fetchAll($sql, [$limit]);
}

/**
 * Get similar products based on category and attributes
 */
function getSimilarProducts($product_id, $limit = 6) {
    global $database;
    
    // Get the reference product
    $reference = $database->fetch("
        SELECT category_id, brand, price FROM products WHERE id = ? AND status = 'active'
    ", [$product_id]);
    
    if (!$reference) {
        return [];
    }
    
    $price_range = $reference['price'] * 0.3; // 30% price range
    
    $sql = "
        SELECT p.id, p.name, p.price, p.image_url, p.slug, p.brand,
               u.username as vendor_name, vs.store_name, c.name as category_name,
               'similar' as recommendation_type
        FROM products p
        JOIN users u ON p.vendor_id = u.id
        LEFT JOIN vendor_stores vs ON u.id = vs.vendor_id
        LEFT JOIN categories c ON p.category_id = c.id
        WHERE p.category_id = ? 
          AND p.id != ?
          AND p.status = 'active' 
          AND u.status = 'active'
          AND p.price BETWEEN ? AND ?
        ORDER BY 
          CASE WHEN p.brand = ? THEN 0 ELSE 1 END,
          ABS(p.price - ?) ASC,
          p.created_at DESC
        LIMIT ?
    ";
    
    return $database->fetchAll($sql, [
        $reference['category_id'],
        $product_id,
        $reference['price'] - $price_range,
        $reference['price'] + $price_range,
        $reference['brand'],
        $reference['price'],
        $limit
    ]);
}

/**
 * Get cross-sell products (frequently bought together)
 */
function getCrossSellProducts($product_id, $limit = 4) {
    global $database;
    
    $sql = "
        SELECT p.id, p.name, p.price, p.image_url, p.slug, p.brand,
               u.username as vendor_name, vs.store_name, c.name as category_name,
               COUNT(*) as co_occurrence,
               'cross_sell' as recommendation_type
        FROM user_interactions ui1
        JOIN user_interactions ui2 ON ui1.user_id = ui2.user_id 
          AND ui1.product_id != ui2.product_id
          AND ABS(TIMESTAMPDIFF(HOUR, ui1.created_at, ui2.created_at)) <= 24
        JOIN products p ON ui2.product_id = p.id
        JOIN users u ON p.vendor_id = u.id
        LEFT JOIN vendor_stores vs ON u.id = vs.vendor_id
        LEFT JOIN categories c ON p.category_id = c.id
        WHERE ui1.product_id = ?
          AND ui1.interaction_type IN ('cart', 'purchase')
          AND ui2.interaction_type IN ('cart', 'purchase')
          AND p.status = 'active'
          AND u.status = 'active'
        GROUP BY p.id
        ORDER BY co_occurrence DESC, p.created_at DESC
        LIMIT ?
    ";
    
    return $database->fetchAll($sql, [$product_id, $limit]);
}

/**
 * Get vendor recommendations
 */
function getVendorRecommendations($user_id, $limit = 6) {
    global $database;
    
    if (!$user_id) {
        // For guests, show top-rated vendors
        $sql = "
            SELECT u.id, u.username, vs.store_name, vs.store_description,
                   COUNT(p.id) as product_count,
                   AVG(ui.interaction_value) as avg_interaction,
                   'top_rated' as recommendation_type
            FROM users u
            LEFT JOIN vendor_stores vs ON u.id = vs.vendor_id
            LEFT JOIN products p ON u.id = p.vendor_id AND p.status = 'active'
            LEFT JOIN user_interactions ui ON u.id = ui.vendor_id
            WHERE u.user_type = 'vendor' AND u.status = 'active'
            GROUP BY u.id
            ORDER BY avg_interaction DESC, product_count DESC
            LIMIT ?
        ";
        
        return $database->fetchAll($sql, [$limit]);
    }
    
    // For logged-in users, recommend based on interaction history
    $sql = "
        SELECT u.id, u.username, vs.store_name, vs.store_description,
               COUNT(ui.id) as interaction_count,
               SUM(ui.interaction_value) as total_interaction_value,
               'personalized' as recommendation_type
        FROM user_interactions ui
        JOIN users u ON ui.vendor_id = u.id
        LEFT JOIN vendor_stores vs ON u.id = vs.vendor_id
        WHERE ui.user_id != ? 
          AND u.user_type = 'vendor' 
          AND u.status = 'active'
          AND ui.vendor_id NOT IN (
              SELECT DISTINCT vendor_id FROM user_interactions 
              WHERE user_id = ? AND interaction_type IN ('cart', 'purchase')
          )
        GROUP BY u.id
        ORDER BY interaction_count DESC, total_interaction_value DESC
        LIMIT ?
    ";
    
    return $database->fetchAll($sql, [$user_id, $user_id, $limit]);
}
?>
