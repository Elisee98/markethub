<?php
/**
 * MarketHub Shopping Cart API
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

// For guest users, use session-based cart
$customer_id = isLoggedIn() ? $_SESSION['user_id'] : null;

// Initialize session cart for guests
if (!$customer_id && !isset($_SESSION['cart_items'])) {
    $_SESSION['cart_items'] = [];
}

try {
    switch ($action) {
        case 'add':
            $quantity = max(1, intval($input['quantity'] ?? 1));
            
            // Check if product exists and is active
            $product = $database->fetch(
                "SELECT id, name, price, stock_quantity, vendor_id FROM products WHERE id = ? AND status = 'active'", 
                [$product_id]
            );
            
            if (!$product) {
                echo json_encode(['success' => false, 'message' => 'Product not found or inactive']);
                exit;
            }
            
            // Check stock availability
            if ($product['stock_quantity'] < $quantity) {
                echo json_encode(['success' => false, 'message' => 'Insufficient stock available']);
                exit;
            }
            
            if ($customer_id) {
                // Logged in user - database cart
                $existing = $database->fetch(
                    "SELECT id, quantity FROM cart_items WHERE customer_id = ? AND product_id = ?",
                    [$customer_id, $product_id]
                );
                
                if ($existing) {
                    $new_quantity = $existing['quantity'] + $quantity;
                    if ($new_quantity > $product['stock_quantity']) {
                        echo json_encode(['success' => false, 'message' => 'Cannot add more items than available in stock']);
                        exit;
                    }
                    
                    $sql = "UPDATE cart_items SET quantity = ?, updated_at = NOW() WHERE id = ?";
                    $database->execute($sql, [$new_quantity, $existing['id']]);
                } else {
                    $sql = "INSERT INTO cart_items (customer_id, product_id, quantity, created_at) VALUES (?, ?, ?, NOW())";
                    $database->execute($sql, [$customer_id, $product_id, $quantity]);
                }
                
                // Get updated cart count
                $cart_count = $database->fetch("SELECT COUNT(*) as count FROM cart_items WHERE customer_id = ?", [$customer_id])['count'];
                
            } else {
                // Guest user - session cart
                $cart_key = array_search($product_id, array_column($_SESSION['cart_items'], 'product_id'));
                
                if ($cart_key !== false) {
                    $new_quantity = $_SESSION['cart_items'][$cart_key]['quantity'] + $quantity;
                    if ($new_quantity > $product['stock_quantity']) {
                        echo json_encode(['success' => false, 'message' => 'Cannot add more items than available in stock']);
                        exit;
                    }
                    $_SESSION['cart_items'][$cart_key]['quantity'] = $new_quantity;
                } else {
                    $_SESSION['cart_items'][] = [
                        'product_id' => $product_id,
                        'quantity' => $quantity,
                        'added_at' => time()
                    ];
                }
                
                $cart_count = count($_SESSION['cart_items']);
            }
            
            // Log activity for logged in users
            if ($customer_id) {
                logActivity($customer_id, 'cart_add', "Product ID: $product_id, Quantity: $quantity");
            }
            
            echo json_encode([
                'success' => true, 
                'message' => 'Product added to cart',
                'count' => $cart_count,
                'product_name' => $product['name']
            ]);
            break;
            
        case 'update':
            $quantity = max(0, intval($input['quantity'] ?? 1));
            
            if ($customer_id) {
                // Logged in user
                if ($quantity > 0) {
                    // Check stock
                    $product = $database->fetch(
                        "SELECT stock_quantity FROM products WHERE id = ? AND status = 'active'", 
                        [$product_id]
                    );
                    
                    if (!$product || $quantity > $product['stock_quantity']) {
                        echo json_encode(['success' => false, 'message' => 'Insufficient stock available']);
                        exit;
                    }
                    
                    $sql = "UPDATE cart_items SET quantity = ?, updated_at = NOW() WHERE customer_id = ? AND product_id = ?";
                    $database->execute($sql, [$quantity, $customer_id, $product_id]);
                } else {
                    // Remove item if quantity is 0
                    $sql = "DELETE FROM cart_items WHERE customer_id = ? AND product_id = ?";
                    $database->execute($sql, [$customer_id, $product_id]);
                }
                
                $cart_count = $database->fetch("SELECT COUNT(*) as count FROM cart_items WHERE customer_id = ?", [$customer_id])['count'];
                
            } else {
                // Guest user
                $cart_key = array_search($product_id, array_column($_SESSION['cart_items'], 'product_id'));
                
                if ($cart_key !== false) {
                    if ($quantity > 0) {
                        $_SESSION['cart_items'][$cart_key]['quantity'] = $quantity;
                    } else {
                        unset($_SESSION['cart_items'][$cart_key]);
                        $_SESSION['cart_items'] = array_values($_SESSION['cart_items']);
                    }
                }
                
                $cart_count = count($_SESSION['cart_items']);
            }
            
            if ($customer_id) {
                logActivity($customer_id, 'cart_update', "Product ID: $product_id, Quantity: $quantity");
            }
            
            echo json_encode([
                'success' => true, 
                'message' => $quantity > 0 ? 'Cart updated' : 'Item removed from cart',
                'count' => $cart_count
            ]);
            break;
            
        case 'remove':
            if ($customer_id) {
                // Logged in user
                $sql = "DELETE FROM cart_items WHERE customer_id = ? AND product_id = ?";
                $database->execute($sql, [$customer_id, $product_id]);
                
                $cart_count = $database->fetch("SELECT COUNT(*) as count FROM cart_items WHERE customer_id = ?", [$customer_id])['count'];
                
            } else {
                // Guest user
                $cart_key = array_search($product_id, array_column($_SESSION['cart_items'], 'product_id'));
                
                if ($cart_key !== false) {
                    unset($_SESSION['cart_items'][$cart_key]);
                    $_SESSION['cart_items'] = array_values($_SESSION['cart_items']);
                }
                
                $cart_count = count($_SESSION['cart_items']);
            }
            
            if ($customer_id) {
                logActivity($customer_id, 'cart_remove', "Product ID: $product_id");
            }
            
            echo json_encode([
                'success' => true, 
                'message' => 'Item removed from cart',
                'count' => $cart_count
            ]);
            break;
            
        case 'clear':
            if ($customer_id) {
                // Logged in user
                $count_result = $database->fetch("SELECT COUNT(*) as count FROM cart_items WHERE customer_id = ?", [$customer_id]);
                $count = $count_result['count'];
                
                $sql = "DELETE FROM cart_items WHERE customer_id = ?";
                $database->execute($sql, [$customer_id]);
                
                logActivity($customer_id, 'cart_clear', "Cleared $count items");
                
            } else {
                // Guest user
                $count = count($_SESSION['cart_items']);
                $_SESSION['cart_items'] = [];
            }
            
            echo json_encode([
                'success' => true, 
                'message' => 'Cart cleared',
                'count' => 0
            ]);
            break;
            
        case 'get':
            $cart_items = [];
            $cart_total = 0;
            $cart_count = 0;
            
            if ($customer_id) {
                // Logged in user
                $cart_sql = "
                    SELECT ci.*, p.name, p.price, p.stock_quantity, pi.image_url,
                           u.username as vendor_name, vs.store_name,
                           (ci.quantity * p.price) as item_total
                    FROM cart_items ci
                    JOIN products p ON ci.product_id = p.id
                    LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
                    LEFT JOIN users u ON p.vendor_id = u.id
                    LEFT JOIN vendor_stores vs ON u.id = vs.vendor_id
                    WHERE ci.customer_id = ? AND p.status = 'active'
                    ORDER BY ci.created_at DESC
                ";
                
                $cart_items = $database->fetchAll($cart_sql, [$customer_id]);
                
            } else {
                // Guest user
                if (!empty($_SESSION['cart_items'])) {
                    $product_ids = array_column($_SESSION['cart_items'], 'product_id');
                    $placeholders = str_repeat('?,', count($product_ids) - 1) . '?';
                    
                    $products_sql = "
                        SELECT p.id, p.name, p.price, p.stock_quantity, pi.image_url,
                               u.username as vendor_name, vs.store_name
                        FROM products p
                        LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
                        LEFT JOIN users u ON p.vendor_id = u.id
                        LEFT JOIN vendor_stores vs ON u.id = vs.vendor_id
                        WHERE p.id IN ($placeholders) AND p.status = 'active'
                    ";
                    
                    $products = $database->fetchAll($products_sql, $product_ids);
                    
                    // Merge with session cart data
                    foreach ($_SESSION['cart_items'] as $cart_item) {
                        $product = array_filter($products, function($p) use ($cart_item) {
                            return $p['id'] == $cart_item['product_id'];
                        });
                        
                        if (!empty($product)) {
                            $product = array_values($product)[0];
                            $cart_items[] = array_merge($product, [
                                'product_id' => $cart_item['product_id'],
                                'quantity' => $cart_item['quantity'],
                                'item_total' => $cart_item['quantity'] * $product['price']
                            ]);
                        }
                    }
                }
            }
            
            // Calculate totals
            foreach ($cart_items as $item) {
                $cart_total += $item['item_total'];
                $cart_count += $item['quantity'];
            }
            
            echo json_encode([
                'success' => true,
                'items' => $cart_items,
                'count' => count($cart_items),
                'total_items' => $cart_count,
                'subtotal' => $cart_total,
                'total' => $cart_total // Will be updated with shipping, tax, etc.
            ]);
            break;
            
        case 'merge':
            // Merge guest cart with user cart after login
            if (!$customer_id) {
                echo json_encode(['success' => false, 'message' => 'User not logged in']);
                exit;
            }
            
            if (!empty($_SESSION['cart_items'])) {
                foreach ($_SESSION['cart_items'] as $cart_item) {
                    $product_id = $cart_item['product_id'];
                    $quantity = $cart_item['quantity'];
                    
                    // Check if item already exists in user's cart
                    $existing = $database->fetch(
                        "SELECT id, quantity FROM cart_items WHERE customer_id = ? AND product_id = ?",
                        [$customer_id, $product_id]
                    );
                    
                    if ($existing) {
                        // Update quantity
                        $new_quantity = $existing['quantity'] + $quantity;
                        $sql = "UPDATE cart_items SET quantity = ?, updated_at = NOW() WHERE id = ?";
                        $database->execute($sql, [$new_quantity, $existing['id']]);
                    } else {
                        // Add new item
                        $sql = "INSERT INTO cart_items (customer_id, product_id, quantity, created_at) VALUES (?, ?, ?, NOW())";
                        $database->execute($sql, [$customer_id, $product_id, $quantity]);
                    }
                }
                
                // Clear session cart
                $_SESSION['cart_items'] = [];
                
                logActivity($customer_id, 'cart_merge', 'Guest cart merged with user cart');
            }
            
            $cart_count = $database->fetch("SELECT COUNT(*) as count FROM cart_items WHERE customer_id = ?", [$customer_id])['count'];
            
            echo json_encode([
                'success' => true, 
                'message' => 'Cart merged successfully',
                'count' => $cart_count
            ]);
            break;

        case 'reorder':
            $order_id = intval($input['order_id'] ?? 0);

            if (!$customer_id) {
                echo json_encode(['success' => false, 'message' => 'Please log in to reorder']);
                exit;
            }

            if (!$order_id) {
                echo json_encode(['success' => false, 'message' => 'Order ID required']);
                exit;
            }

            // Verify order belongs to customer
            $order = $database->fetch(
                "SELECT id FROM orders WHERE id = ? AND customer_id = ?",
                [$order_id, $customer_id]
            );

            if (!$order) {
                echo json_encode(['success' => false, 'message' => 'Order not found']);
                exit;
            }

            // Get order items
            $order_items = $database->fetchAll(
                "SELECT oi.product_id, oi.quantity, p.name, p.price, p.stock_quantity, p.status
                 FROM order_items oi
                 JOIN products p ON oi.product_id = p.id
                 WHERE oi.order_id = ?",
                [$order_id]
            );

            if (empty($order_items)) {
                echo json_encode(['success' => false, 'message' => 'No items found in order']);
                exit;
            }

            $added_count = 0;
            $skipped_items = [];

            foreach ($order_items as $item) {
                // Check if product is still active and in stock
                if ($item['status'] !== 'active') {
                    $skipped_items[] = $item['name'] . ' (no longer available)';
                    continue;
                }

                if ($item['stock_quantity'] < $item['quantity']) {
                    if ($item['stock_quantity'] > 0) {
                        // Add available quantity
                        $quantity_to_add = $item['stock_quantity'];
                        $skipped_items[] = $item['name'] . ' (only ' . $quantity_to_add . ' available)';
                    } else {
                        $skipped_items[] = $item['name'] . ' (out of stock)';
                        continue;
                    }
                } else {
                    $quantity_to_add = $item['quantity'];
                }

                // Check if item already in cart
                $existing = $database->fetch(
                    "SELECT id, quantity FROM cart_items WHERE customer_id = ? AND product_id = ?",
                    [$customer_id, $item['product_id']]
                );

                if ($existing) {
                    $new_quantity = $existing['quantity'] + $quantity_to_add;
                    if ($new_quantity > $item['stock_quantity']) {
                        $new_quantity = $item['stock_quantity'];
                    }

                    $sql = "UPDATE cart_items SET quantity = ?, updated_at = NOW() WHERE id = ?";
                    $database->execute($sql, [$new_quantity, $existing['id']]);
                } else {
                    $sql = "INSERT INTO cart_items (customer_id, product_id, quantity, created_at) VALUES (?, ?, ?, NOW())";
                    $database->execute($sql, [$customer_id, $item['product_id'], $quantity_to_add]);
                }

                $added_count++;
            }

            // Get updated cart count
            $cart_count_result = $database->fetch("SELECT COUNT(*) as count FROM cart_items WHERE customer_id = ?", [$customer_id]);
            $cart_count = $cart_count_result['count'];

            $message = "$added_count items added to cart";
            if (!empty($skipped_items)) {
                $message .= ". Some items were skipped: " . implode(', ', array_slice($skipped_items, 0, 3));
                if (count($skipped_items) > 3) {
                    $message .= " and " . (count($skipped_items) - 3) . " more";
                }
            }

            echo json_encode([
                'success' => true,
                'message' => $message,
                'count' => $cart_count,
                'added_count' => $added_count,
                'skipped_count' => count($skipped_items)
            ]);
            break;

        case 'count':
            $cart_count = 0;

            if ($customer_id) {
                $result = $database->fetch("SELECT SUM(quantity) as total FROM cart_items WHERE customer_id = ?", [$customer_id]);
                $cart_count = intval($result['total'] ?? 0);
            } else {
                $cart_count = array_sum($_SESSION['cart_items'] ?? []);
            }

            echo json_encode([
                'success' => true,
                'total_items' => $cart_count
            ]);
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
            break;
    }
    
} catch (Exception $e) {
    error_log("Cart API Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred']);
}
?>
