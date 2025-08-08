<?php
/**
 * MarketHub Payment Processing API
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

// Require login
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Authentication required']);
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
$customer_id = $_SESSION['user_id'];

try {
    switch ($action) {
        case 'process_payment':
            $order_id = intval($input['order_id'] ?? 0);
            $payment_method = sanitizeInput($input['payment_method'] ?? '');
            $payment_data = $input['payment_data'] ?? [];
            
            // Validate order
            $order = $database->fetch(
                "SELECT * FROM orders WHERE id = ? AND customer_id = ? AND payment_status = 'pending'",
                [$order_id, $customer_id]
            );
            
            if (!$order) {
                echo json_encode(['success' => false, 'message' => 'Order not found or already processed']);
                exit;
            }
            
            // Process payment based on method
            $payment_result = processPayment($order, $payment_method, $payment_data);
            
            if ($payment_result['success']) {
                // Update order payment status
                $update_sql = "UPDATE orders SET 
                               payment_status = ?, payment_reference = ?, 
                               payment_date = NOW(), updated_at = NOW() 
                               WHERE id = ?";
                
                $database->execute($update_sql, [
                    'paid', 
                    $payment_result['reference'], 
                    $order_id
                ]);
                
                // Create payment record
                $payment_sql = "INSERT INTO payments 
                                (order_id, customer_id, amount, payment_method, 
                                 payment_reference, status, gateway_response, created_at) 
                                VALUES (?, ?, ?, ?, ?, 'completed', ?, NOW())";
                
                $database->execute($payment_sql, [
                    $order_id, $customer_id, $order['total_amount'], 
                    $payment_method, $payment_result['reference'], 
                    json_encode($payment_result['gateway_data'])
                ]);
                
                // Update order status to confirmed
                $database->execute("UPDATE orders SET status = 'confirmed' WHERE id = ?", [$order_id]);
                
                // Log activity
                logActivity($customer_id, 'payment_completed', "Order: {$order['order_number']}, Amount: " . formatCurrency($order['total_amount']));
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Payment processed successfully',
                    'payment_reference' => $payment_result['reference'],
                    'order_number' => $order['order_number']
                ]);
                
            } else {
                // Payment failed
                $payment_sql = "INSERT INTO payments 
                                (order_id, customer_id, amount, payment_method, 
                                 status, gateway_response, created_at) 
                                VALUES (?, ?, ?, ?, 'failed', ?, NOW())";
                
                $database->execute($payment_sql, [
                    $order_id, $customer_id, $order['total_amount'], 
                    $payment_method, json_encode($payment_result)
                ]);
                
                echo json_encode([
                    'success' => false,
                    'message' => $payment_result['message'] ?? 'Payment processing failed'
                ]);
            }
            break;
            
        case 'verify_payment':
            $payment_reference = sanitizeInput($input['payment_reference'] ?? '');
            
            if (empty($payment_reference)) {
                echo json_encode(['success' => false, 'message' => 'Payment reference required']);
                exit;
            }
            
            // Get payment record
            $payment = $database->fetch(
                "SELECT p.*, o.order_number FROM payments p 
                 JOIN orders o ON p.order_id = o.id 
                 WHERE p.payment_reference = ? AND p.customer_id = ?",
                [$payment_reference, $customer_id]
            );
            
            if (!$payment) {
                echo json_encode(['success' => false, 'message' => 'Payment not found']);
                exit;
            }
            
            echo json_encode([
                'success' => true,
                'status' => $payment['status'],
                'order_number' => $payment['order_number']
            ]);
            break;
            
        case 'get_payment_methods':
            // Get available payment methods
            $payment_methods = [
                [
                    'id' => 'credit_card',
                    'name' => 'Credit/Debit Card',
                    'description' => 'Visa, Mastercard, American Express',
                    'icon' => 'fas fa-credit-card',
                    'enabled' => true
                ],
                [
                    'id' => 'mobile_money',
                    'name' => 'Mobile Money',
                    'description' => 'MTN Mobile Money, Airtel Money',
                    'icon' => 'fas fa-mobile-alt',
                    'enabled' => true
                ],
                [
                    'id' => 'bank_transfer',
                    'name' => 'Bank Transfer',
                    'description' => 'Direct bank transfer',
                    'icon' => 'fas fa-university',
                    'enabled' => true
                ]
            ];
            
            echo json_encode([
                'success' => true,
                'payment_methods' => $payment_methods
            ]);
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
            break;
    }
    
} catch (Exception $e) {
    error_log("Payment API Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Payment processing error']);
}

/**
 * Process payment based on method
 */
function processPayment($order, $payment_method, $payment_data) {
    switch ($payment_method) {
        case 'credit_card':
            return processCreditCardPayment($order, $payment_data);
            
        case 'mobile_money':
            return processMobileMoneyPayment($order, $payment_data);
            
        case 'bank_transfer':
            return processBankTransferPayment($order, $payment_data);
            
        default:
            return ['success' => false, 'message' => 'Invalid payment method'];
    }
}

/**
 * Process credit card payment
 */
function processCreditCardPayment($order, $payment_data) {
    // Simulate successful payment for demo
    $reference = 'CC-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -8));
    
    return [
        'success' => true,
        'reference' => $reference,
        'gateway_data' => [
            'method' => 'credit_card',
            'processed_at' => date('Y-m-d H:i:s')
        ]
    ];
}

/**
 * Process mobile money payment
 */
function processMobileMoneyPayment($order, $payment_data) {
    // Simulate successful payment for demo
    $reference = 'MM-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -8));
    
    return [
        'success' => true,
        'reference' => $reference,
        'gateway_data' => [
            'method' => 'mobile_money',
            'processed_at' => date('Y-m-d H:i:s')
        ]
    ];
}

/**
 * Process bank transfer payment
 */
function processBankTransferPayment($order, $payment_data) {
    // Bank transfer requires manual verification
    $reference = 'BT-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -8));
    
    return [
        'success' => true,
        'reference' => $reference,
        'gateway_data' => [
            'method' => 'bank_transfer',
            'status' => 'pending_verification',
            'processed_at' => date('Y-m-d H:i:s')
        ]
    ];
}
?>
