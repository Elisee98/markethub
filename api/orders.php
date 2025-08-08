<?php
/**
 * Orders API
 * Handle order-related operations
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once '../config/config.php';

// Check if user is logged in
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Authentication required']);
    exit;
}

// Get request data
$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? $_GET['action'] ?? '';
$customer_id = $_SESSION['user_id'];

try {
    switch ($action) {
        case 'cancel':
            $order_id = intval($input['order_id'] ?? 0);
            
            if (!$order_id) {
                echo json_encode(['success' => false, 'message' => 'Order ID required']);
                exit;
            }
            
            // Get order details
            $order = $database->fetch(
                "SELECT * FROM orders WHERE id = ? AND customer_id = ?",
                [$order_id, $customer_id]
            );
            
            if (!$order) {
                echo json_encode(['success' => false, 'message' => 'Order not found']);
                exit;
            }
            
            // Check if order can be cancelled
            if (!in_array($order['status'], ['pending', 'processing'])) {
                echo json_encode(['success' => false, 'message' => 'Order cannot be cancelled at this stage']);
                exit;
            }
            
            // Start transaction
            $database->beginTransaction();
            
            try {
                // Update order status
                $database->execute(
                    "UPDATE orders SET status = 'cancelled', updated_at = NOW() WHERE id = ?",
                    [$order_id]
                );
                
                // Restore product stock
                $order_items = $database->fetchAll(
                    "SELECT product_id, quantity FROM order_items WHERE order_id = ?",
                    [$order_id]
                );
                
                foreach ($order_items as $item) {
                    $database->execute(
                        "UPDATE products SET stock_quantity = stock_quantity + ? WHERE id = ?",
                        [$item['quantity'], $item['product_id']]
                    );
                }
                
                // If payment was made, create refund record (for manual processing)
                if ($order['payment_status'] === 'paid') {
                    $database->execute(
                        "INSERT INTO refunds (order_id, customer_id, amount, status, reason, created_at) 
                         VALUES (?, ?, ?, 'pending', 'Customer cancellation', NOW())",
                        [$order_id, $customer_id, $order['total_amount']]
                    );
                }
                
                // Log activity
                $database->execute(
                    "INSERT INTO activity_logs (user_id, action, description, created_at) 
                     VALUES (?, 'order_cancelled', ?, NOW())",
                    [$customer_id, "Order #{$order['order_number']} cancelled by customer"]
                );
                
                $database->commit();
                
                // Send cancellation email
                $customer = $database->fetch("SELECT * FROM users WHERE id = ?", [$customer_id]);
                if ($customer) {
                    $subject = "Order Cancellation Confirmation - {$order['order_number']}";
                    $message = "
                        <h2>Order Cancellation Confirmation</h2>
                        <p>Your order has been successfully cancelled.</p>
                        <p><strong>Order Number:</strong> {$order['order_number']}</p>
                        <p><strong>Total Amount:</strong> " . formatCurrency($order['total_amount']) . "</p>
                        " . ($order['payment_status'] === 'paid' ? 
                            "<p><strong>Refund:</strong> A refund will be processed within 3-5 business days.</p>" : 
                            ""
                        ) . "
                        <p>If you have any questions, please contact our support team.</p>
                    ";
                    
                    sendEmail($customer['email'], $subject, $message);
                }
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Order cancelled successfully' . 
                                ($order['payment_status'] === 'paid' ? '. Refund will be processed within 3-5 business days.' : '')
                ]);
                
            } catch (Exception $e) {
                $database->rollback();
                throw $e;
            }
            break;
            
        case 'track':
            $order_id = intval($input['order_id'] ?? $_GET['order_id'] ?? 0);
            $tracking_number = sanitizeInput($input['tracking_number'] ?? $_GET['tracking_number'] ?? '');
            
            $order = null;
            
            if ($order_id) {
                $order = $database->fetch(
                    "SELECT * FROM orders WHERE id = ? AND customer_id = ?",
                    [$order_id, $customer_id]
                );
            } elseif ($tracking_number) {
                $order = $database->fetch(
                    "SELECT * FROM orders WHERE tracking_number = ? AND customer_id = ?",
                    [$tracking_number, $customer_id]
                );
            }
            
            if (!$order) {
                echo json_encode(['success' => false, 'message' => 'Order not found']);
                exit;
            }
            
            // Generate tracking events
            $events = [];
            
            $events[] = [
                'status' => 'placed',
                'title' => 'Order Placed',
                'description' => 'Your order has been received',
                'date' => $order['created_at'],
                'completed' => true
            ];
            
            if ($order['payment_status'] === 'paid') {
                $events[] = [
                    'status' => 'payment_confirmed',
                    'title' => 'Payment Confirmed',
                    'description' => 'Payment has been processed',
                    'date' => $order['payment_date'] ?? $order['created_at'],
                    'completed' => true
                ];
            }
            
            if (in_array($order['status'], ['processing', 'shipped', 'delivered'])) {
                $events[] = [
                    'status' => 'processing',
                    'title' => 'Order Processing',
                    'description' => 'Your order is being prepared',
                    'date' => $order['updated_at'],
                    'completed' => true
                ];
            }
            
            if (in_array($order['status'], ['shipped', 'delivered'])) {
                $events[] = [
                    'status' => 'shipped',
                    'title' => 'Order Shipped',
                    'description' => 'Your order is on its way',
                    'date' => $order['shipped_at'] ?? $order['updated_at'],
                    'completed' => true
                ];
            }
            
            if ($order['status'] === 'delivered') {
                $events[] = [
                    'status' => 'delivered',
                    'title' => 'Delivered',
                    'description' => 'Your order has been delivered',
                    'date' => $order['delivered_at'] ?? $order['updated_at'],
                    'completed' => true
                ];
            }
            
            echo json_encode([
                'success' => true,
                'order' => $order,
                'tracking_events' => $events
            ]);
            break;
            
        case 'get':
            $page = max(1, intval($_GET['page'] ?? 1));
            $limit = min(50, max(1, intval($_GET['limit'] ?? 10)));
            $offset = ($page - 1) * $limit;
            
            $status_filter = sanitizeInput($_GET['status'] ?? '');
            $date_filter = sanitizeInput($_GET['date'] ?? '');
            
            // Build WHERE clause
            $where_conditions = ["o.customer_id = ?"];
            $params = [$customer_id];
            
            if ($status_filter && $status_filter !== 'all') {
                $where_conditions[] = "o.status = ?";
                $params[] = $status_filter;
            }
            
            if ($date_filter) {
                switch ($date_filter) {
                    case 'last_30_days':
                        $where_conditions[] = "o.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
                        break;
                    case 'last_3_months':
                        $where_conditions[] = "o.created_at >= DATE_SUB(NOW(), INTERVAL 3 MONTH)";
                        break;
                    case 'last_year':
                        $where_conditions[] = "o.created_at >= DATE_SUB(NOW(), INTERVAL 1 YEAR)";
                        break;
                }
            }
            
            $where_clause = implode(' AND ', $where_conditions);
            
            // Get orders
            $orders_sql = "
                SELECT o.*, 
                       COUNT(oi.id) as item_count,
                       GROUP_CONCAT(DISTINCT p.name ORDER BY p.name SEPARATOR ', ') as product_names
                FROM orders o
                LEFT JOIN order_items oi ON o.id = oi.order_id
                LEFT JOIN products p ON oi.product_id = p.id
                WHERE $where_clause
                GROUP BY o.id
                ORDER BY o.created_at DESC
                LIMIT $limit OFFSET $offset
            ";
            
            $orders = $database->fetchAll($orders_sql, $params);
            
            // Get total count
            $count_sql = "SELECT COUNT(*) as total FROM orders o WHERE " . implode(' AND ', $where_conditions);
            $total_result = $database->fetch($count_sql, $params);
            $total = $total_result['total'];
            
            echo json_encode([
                'success' => true,
                'orders' => $orders,
                'pagination' => [
                    'page' => $page,
                    'limit' => $limit,
                    'total' => $total,
                    'pages' => ceil($total / $limit)
                ]
            ]);
            break;
            
        case 'details':
            $order_id = intval($_GET['order_id'] ?? 0);
            
            if (!$order_id) {
                echo json_encode(['success' => false, 'message' => 'Order ID required']);
                exit;
            }
            
            // Get order details
            $order = $database->fetch(
                "SELECT o.*, 
                        sa.address_line_1 as shipping_address_1, sa.address_line_2 as shipping_address_2,
                        sa.city as shipping_city, sa.state as shipping_state, sa.postal_code as shipping_postal,
                        sa.country as shipping_country,
                        ba.address_line_1 as billing_address_1, ba.address_line_2 as billing_address_2,
                        ba.city as billing_city, ba.state as billing_state, ba.postal_code as billing_postal,
                        ba.country as billing_country
                 FROM orders o
                 LEFT JOIN user_addresses sa ON o.shipping_address_id = sa.id
                 LEFT JOIN user_addresses ba ON o.billing_address_id = ba.id
                 WHERE o.id = ? AND o.customer_id = ?",
                [$order_id, $customer_id]
            );
            
            if (!$order) {
                echo json_encode(['success' => false, 'message' => 'Order not found']);
                exit;
            }
            
            // Get order items
            $items = $database->fetchAll(
                "SELECT oi.*, p.name, p.image_url, p.slug, p.brand,
                        u.username as vendor_name, vs.store_name
                 FROM order_items oi
                 JOIN products p ON oi.product_id = p.id
                 JOIN users u ON p.vendor_id = u.id
                 LEFT JOIN vendor_stores vs ON u.id = vs.vendor_id
                 WHERE oi.order_id = ?
                 ORDER BY vs.store_name, p.name",
                [$order_id]
            );
            
            // Get payment information
            $payment = $database->fetch(
                "SELECT * FROM payments WHERE order_id = ? ORDER BY created_at DESC LIMIT 1",
                [$order_id]
            );
            
            echo json_encode([
                'success' => true,
                'order' => $order,
                'items' => $items,
                'payment' => $payment
            ]);
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
            break;
    }
    
} catch (Exception $e) {
    error_log("Orders API Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred']);
}
?>
