<?php
/**
 * MarketHub Wishlist API
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

// Require login for wishlist operations
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Login required']);
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
$customer_id = $_SESSION['user_id'];

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
            
            // Check if already in wishlist
            $existing = $database->fetch(
                "SELECT id FROM wishlists WHERE customer_id = ? AND product_id = ?",
                [$customer_id, $product_id]
            );
            
            if ($existing) {
                echo json_encode(['success' => false, 'message' => 'Product already in wishlist']);
                exit;
            }
            
            // Add to wishlist
            $sql = "INSERT INTO wishlists (customer_id, product_id, created_at) VALUES (?, ?, NOW())";
            $database->execute($sql, [$customer_id, $product_id]);
            
            // Log activity
            logActivity($customer_id, 'wishlist_add', "Product ID: $product_id");
            
            // Get updated wishlist count
            $count_result = $database->fetch("SELECT COUNT(*) as count FROM wishlists WHERE customer_id = ?", [$customer_id]);
            $wishlist_count = $count_result['count'];
            
            echo json_encode([
                'success' => true, 
                'message' => 'Product added to wishlist',
                'count' => $wishlist_count,
                'product_name' => $product_check['name']
            ]);
            break;
            
        case 'remove':
            // Remove from wishlist
            $sql = "DELETE FROM wishlists WHERE customer_id = ? AND product_id = ?";
            $result = $database->execute($sql, [$customer_id, $product_id]);
            
            if ($result) {
                // Log activity
                logActivity($customer_id, 'wishlist_remove', "Product ID: $product_id");
                
                // Get updated wishlist count
                $count_result = $database->fetch("SELECT COUNT(*) as count FROM wishlists WHERE customer_id = ?", [$customer_id]);
                $wishlist_count = $count_result['count'];
                
                echo json_encode([
                    'success' => true, 
                    'message' => 'Product removed from wishlist',
                    'count' => $wishlist_count
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Product not in wishlist']);
            }
            break;
            
        case 'toggle':
            // Check if product exists
            $product_check = $database->fetch(
                "SELECT id, name FROM products WHERE id = ? AND status = 'active'", 
                [$product_id]
            );
            
            if (!$product_check) {
                echo json_encode(['success' => false, 'message' => 'Product not found or inactive']);
                exit;
            }
            
            // Check if already in wishlist
            $existing = $database->fetch(
                "SELECT id FROM wishlists WHERE customer_id = ? AND product_id = ?",
                [$customer_id, $product_id]
            );
            
            if ($existing) {
                // Remove from wishlist
                $sql = "DELETE FROM wishlists WHERE customer_id = ? AND product_id = ?";
                $database->execute($sql, [$customer_id, $product_id]);
                
                logActivity($customer_id, 'wishlist_remove', "Product ID: $product_id");
                
                $action_performed = 'removed';
                $message = 'Product removed from wishlist';
            } else {
                // Add to wishlist
                $sql = "INSERT INTO wishlists (customer_id, product_id, created_at) VALUES (?, ?, NOW())";
                $database->execute($sql, [$customer_id, $product_id]);
                
                logActivity($customer_id, 'wishlist_add', "Product ID: $product_id");
                
                $action_performed = 'added';
                $message = 'Product added to wishlist';
            }
            
            // Get updated wishlist count
            $count_result = $database->fetch("SELECT COUNT(*) as count FROM wishlists WHERE customer_id = ?", [$customer_id]);
            $wishlist_count = $count_result['count'];
            
            echo json_encode([
                'success' => true, 
                'message' => $message,
                'action' => $action_performed,
                'count' => $wishlist_count,
                'product_name' => $product_check['name']
            ]);
            break;
            
        case 'get':
            // Get wishlist items
            $wishlist_sql = "
                SELECT w.*, p.name, p.price, p.stock_quantity, pi.image_url,
                       u.username as vendor_name, vs.store_name
                FROM wishlists w
                JOIN products p ON w.product_id = p.id
                LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
                LEFT JOIN users u ON p.vendor_id = u.id
                LEFT JOIN vendor_stores vs ON u.id = vs.vendor_id
                WHERE w.customer_id = ? AND p.status = 'active'
                ORDER BY w.created_at DESC
            ";
            
            $wishlist_items = $database->fetchAll($wishlist_sql, [$customer_id]);
            
            echo json_encode([
                'success' => true,
                'count' => count($wishlist_items),
                'items' => $wishlist_items
            ]);
            break;
            
        case 'clear':
            // Clear entire wishlist
            $count_result = $database->fetch("SELECT COUNT(*) as count FROM wishlists WHERE customer_id = ?", [$customer_id]);
            $count = $count_result['count'];
            
            $sql = "DELETE FROM wishlists WHERE customer_id = ?";
            $database->execute($sql, [$customer_id]);
            
            // Log activity
            logActivity($customer_id, 'wishlist_clear', "Cleared $count items");
            
            echo json_encode([
                'success' => true, 
                'message' => 'Wishlist cleared',
                'count' => 0
            ]);
            break;
            
        case 'move_to_cart':
            // Move wishlist item to cart
            $wishlist_item = $database->fetch(
                "SELECT w.*, p.name, p.price, p.stock_quantity FROM wishlists w 
                 JOIN products p ON w.product_id = p.id 
                 WHERE w.customer_id = ? AND w.product_id = ? AND p.status = 'active'",
                [$customer_id, $product_id]
            );
            
            if (!$wishlist_item) {
                echo json_encode(['success' => false, 'message' => 'Product not found in wishlist']);
                exit;
            }
            
            if ($wishlist_item['stock_quantity'] <= 0) {
                echo json_encode(['success' => false, 'message' => 'Product is out of stock']);
                exit;
            }
            
            // Add to cart (assuming cart API exists)
            $quantity = intval($input['quantity'] ?? 1);
            
            // Check if already in cart
            $cart_item = $database->fetch(
                "SELECT id, quantity FROM cart_items WHERE customer_id = ? AND product_id = ?",
                [$customer_id, $product_id]
            );
            
            if ($cart_item) {
                // Update quantity
                $new_quantity = $cart_item['quantity'] + $quantity;
                $sql = "UPDATE cart_items SET quantity = ?, updated_at = NOW() WHERE id = ?";
                $database->execute($sql, [$new_quantity, $cart_item['id']]);
            } else {
                // Add new cart item
                $sql = "INSERT INTO cart_items (customer_id, product_id, quantity, created_at) VALUES (?, ?, ?, NOW())";
                $database->execute($sql, [$customer_id, $product_id, $quantity]);
            }
            
            // Remove from wishlist
            $sql = "DELETE FROM wishlists WHERE customer_id = ? AND product_id = ?";
            $database->execute($sql, [$customer_id, $product_id]);
            
            // Log activities
            logActivity($customer_id, 'cart_add', "Product ID: $product_id, Quantity: $quantity");
            logActivity($customer_id, 'wishlist_remove', "Product ID: $product_id (moved to cart)");
            
            // Get updated counts
            $wishlist_count_result = $database->fetch("SELECT COUNT(*) as count FROM wishlists WHERE customer_id = ?", [$customer_id]);
            $cart_count_result = $database->fetch("SELECT COUNT(*) as count FROM cart_items WHERE customer_id = ?", [$customer_id]);
            
            echo json_encode([
                'success' => true, 
                'message' => 'Product moved to cart',
                'wishlist_count' => $wishlist_count_result['count'],
                'cart_count' => $cart_count_result['count'],
                'product_name' => $wishlist_item['name']
            ]);
            break;
            
        case 'check':
            // Check if product is in wishlist
            $existing = $database->fetch(
                "SELECT id FROM wishlists WHERE customer_id = ? AND product_id = ?",
                [$customer_id, $product_id]
            );
            
            echo json_encode([
                'success' => true,
                'in_wishlist' => !empty($existing)
            ]);
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
            break;
    }
    
} catch (Exception $e) {
    error_log("Wishlist API Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred']);
}
?>
