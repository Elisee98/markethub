<?php
/**
 * MarketHub Product Comparison API
 * Multi-Vendor E-Commerce Platform
 */

require_once '../config/config.php';

header('Content-Type: application/json');

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid JSON input']);
    exit;
}

$action = $input['action'] ?? '';
$product_id = intval($input['product_id'] ?? 0);

// Initialize comparison session if not exists
if (!isset($_SESSION['compare_items'])) {
    $_SESSION['compare_items'] = [];
}

try {
    switch ($action) {
        case 'add':
            // Check if product exists and is active
            $product_check = $database->fetch(
                "SELECT id, name, vendor_id FROM products WHERE id = ? AND status = 'active'", 
                [$product_id]
            );
            
            if (!$product_check) {
                echo json_encode(['success' => false, 'message' => 'Product not found or inactive']);
                exit;
            }
            
            // Check comparison limit (max 4 products)
            if (count($_SESSION['compare_items']) >= 4) {
                echo json_encode(['success' => false, 'message' => 'Maximum 4 products can be compared at once']);
                exit;
            }
            
            // Check if product already in comparison
            if (in_array($product_id, $_SESSION['compare_items'])) {
                echo json_encode(['success' => false, 'message' => 'Product already in comparison']);
                exit;
            }
            
            // Add to comparison
            $_SESSION['compare_items'][] = $product_id;
            
            // Log activity if user is logged in
            if (isLoggedIn()) {
                logActivity($_SESSION['user_id'], 'product_compare_add', "Product ID: $product_id");
            }
            
            echo json_encode([
                'success' => true, 
                'message' => 'Product added to comparison',
                'count' => count($_SESSION['compare_items']),
                'product_name' => $product_check['name']
            ]);
            break;
            
        case 'remove':
            // Remove from comparison
            $key = array_search($product_id, $_SESSION['compare_items']);
            if ($key !== false) {
                unset($_SESSION['compare_items'][$key]);
                $_SESSION['compare_items'] = array_values($_SESSION['compare_items']); // Reindex array
                
                // Log activity if user is logged in
                if (isLoggedIn()) {
                    logActivity($_SESSION['user_id'], 'product_compare_remove', "Product ID: $product_id");
                }
                
                echo json_encode([
                    'success' => true, 
                    'message' => 'Product removed from comparison',
                    'count' => count($_SESSION['compare_items'])
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Product not in comparison']);
            }
            break;
            
        case 'clear':
            // Clear all comparison items
            $count = count($_SESSION['compare_items']);
            $_SESSION['compare_items'] = [];
            
            // Log activity if user is logged in
            if (isLoggedIn()) {
                logActivity($_SESSION['user_id'], 'product_compare_clear', "Cleared $count products");
            }
            
            echo json_encode([
                'success' => true, 
                'message' => 'Comparison cleared',
                'count' => 0
            ]);
            break;
            
        case 'get':
            // Get current comparison items
            $compare_products = [];
            
            if (!empty($_SESSION['compare_items'])) {
                $placeholders = str_repeat('?,', count($_SESSION['compare_items']) - 1) . '?';
                $products_sql = "
                    SELECT p.id, p.name, p.price, p.short_description, pi.image_url,
                           u.username as vendor_name, vs.store_name
                    FROM products p
                    LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
                    LEFT JOIN users u ON p.vendor_id = u.id
                    LEFT JOIN vendor_stores vs ON u.id = vs.vendor_id
                    WHERE p.id IN ($placeholders) AND p.status = 'active'
                    ORDER BY FIELD(p.id, " . implode(',', $_SESSION['compare_items']) . ")
                ";
                
                $compare_products = $database->fetchAll($products_sql, $_SESSION['compare_items']);
            }
            
            echo json_encode([
                'success' => true,
                'count' => count($_SESSION['compare_items']),
                'products' => $compare_products
            ]);
            break;
            
        case 'toggle':
            // Toggle product in comparison (add if not present, remove if present)
            $key = array_search($product_id, $_SESSION['compare_items']);
            
            if ($key !== false) {
                // Remove from comparison
                unset($_SESSION['compare_items'][$key]);
                $_SESSION['compare_items'] = array_values($_SESSION['compare_items']);
                
                echo json_encode([
                    'success' => true, 
                    'message' => 'Product removed from comparison',
                    'action' => 'removed',
                    'count' => count($_SESSION['compare_items'])
                ]);
            } else {
                // Check comparison limit
                if (count($_SESSION['compare_items']) >= 4) {
                    echo json_encode(['success' => false, 'message' => 'Maximum 4 products can be compared at once']);
                    exit;
                }
                
                // Check if product exists
                $product_check = $database->fetch(
                    "SELECT id, name FROM products WHERE id = ? AND status = 'active'", 
                    [$product_id]
                );
                
                if (!$product_check) {
                    echo json_encode(['success' => false, 'message' => 'Product not found or inactive']);
                    exit;
                }
                
                // Add to comparison
                $_SESSION['compare_items'][] = $product_id;
                
                echo json_encode([
                    'success' => true, 
                    'message' => 'Product added to comparison',
                    'action' => 'added',
                    'count' => count($_SESSION['compare_items']),
                    'product_name' => $product_check['name']
                ]);
            }
            
            // Log activity if user is logged in
            if (isLoggedIn()) {
                $log_action = ($key !== false) ? 'product_compare_remove' : 'product_compare_add';
                logActivity($_SESSION['user_id'], $log_action, "Product ID: $product_id");
            }
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
            break;
    }
    
} catch (Exception $e) {
    error_log("Compare API Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred']);
}
?>
