<?php
/**
 * MarketHub Admin Management API
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

// Require admin login
if (!isLoggedIn() || $_SESSION['user_type'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Admin access required']);
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
$admin_id = $_SESSION['user_id'];

try {
    switch ($action) {
        case 'approve_vendor':
            $vendor_id = intval($input['vendor_id'] ?? 0);
            
            // Validate vendor exists and is pending
            $vendor = $database->fetch(
                "SELECT * FROM users WHERE id = ? AND user_type = 'vendor' AND status = 'pending'",
                [$vendor_id]
            );
            
            if (!$vendor) {
                echo json_encode(['success' => false, 'message' => 'Vendor not found or already processed']);
                exit;
            }
            
            // Update vendor status
            $sql = "UPDATE users SET status = 'active', updated_at = NOW() WHERE id = ?";
            $database->execute($sql, [$vendor_id]);
            
            // Log activity
            logActivity($admin_id, 'vendor_approved', "Vendor ID: $vendor_id");
            
            // Send approval email
            $subject = "Vendor Application Approved - " . SITE_NAME;
            $message = "
                <h2>Congratulations!</h2>
                <p>Your vendor application has been approved. You can now start selling on our platform.</p>
                <p>Please log in to your vendor dashboard to set up your store and add products.</p>
                <p><a href='" . SITE_URL . "/vendor/dashboard.php'>Access Vendor Dashboard</a></p>
            ";
            
            sendEmail($vendor['email'], $subject, $message);
            
            echo json_encode([
                'success' => true,
                'message' => 'Vendor approved successfully'
            ]);
            break;
            
        case 'reject_vendor':
            $vendor_id = intval($input['vendor_id'] ?? 0);
            $reason = sanitizeInput($input['reason'] ?? 'Application did not meet requirements');
            
            // Validate vendor exists and is pending
            $vendor = $database->fetch(
                "SELECT * FROM users WHERE id = ? AND user_type = 'vendor' AND status = 'pending'",
                [$vendor_id]
            );
            
            if (!$vendor) {
                echo json_encode(['success' => false, 'message' => 'Vendor not found or already processed']);
                exit;
            }
            
            // Update vendor status
            $sql = "UPDATE users SET status = 'rejected', updated_at = NOW() WHERE id = ?";
            $database->execute($sql, [$vendor_id]);
            
            // Log activity
            logActivity($admin_id, 'vendor_rejected', "Vendor ID: $vendor_id, Reason: $reason");
            
            // Send rejection email
            $subject = "Vendor Application Update - " . SITE_NAME;
            $message = "
                <h2>Application Status Update</h2>
                <p>Thank you for your interest in becoming a vendor on our platform.</p>
                <p>After careful review, we are unable to approve your application at this time.</p>
                <p><strong>Reason:</strong> $reason</p>
                <p>You may reapply in the future if you address the concerns mentioned above.</p>
            ";
            
            sendEmail($vendor['email'], $subject, $message);
            
            echo json_encode([
                'success' => true,
                'message' => 'Vendor application rejected'
            ]);
            break;
            
        case 'suspend_user':
            $user_id = intval($input['user_id'] ?? 0);
            $reason = sanitizeInput($input['reason'] ?? 'Policy violation');
            
            // Validate user exists and is not admin
            $user = $database->fetch(
                "SELECT * FROM users WHERE id = ? AND user_type != 'admin'",
                [$user_id]
            );
            
            if (!$user) {
                echo json_encode(['success' => false, 'message' => 'User not found or cannot be suspended']);
                exit;
            }
            
            // Update user status
            $sql = "UPDATE users SET status = 'suspended', updated_at = NOW() WHERE id = ?";
            $database->execute($sql, [$user_id]);
            
            // Log activity
            logActivity($admin_id, 'user_suspended', "User ID: $user_id, Reason: $reason");
            
            // Send suspension email
            $subject = "Account Suspended - " . SITE_NAME;
            $message = "
                <h2>Account Suspension Notice</h2>
                <p>Your account has been temporarily suspended.</p>
                <p><strong>Reason:</strong> $reason</p>
                <p>If you believe this is an error, please contact our support team.</p>
            ";
            
            sendEmail($user['email'], $subject, $message);
            
            echo json_encode([
                'success' => true,
                'message' => 'User suspended successfully'
            ]);
            break;
            
        case 'activate_user':
            $user_id = intval($input['user_id'] ?? 0);
            
            // Validate user exists
            $user = $database->fetch(
                "SELECT * FROM users WHERE id = ? AND user_type != 'admin'",
                [$user_id]
            );
            
            if (!$user) {
                echo json_encode(['success' => false, 'message' => 'User not found']);
                exit;
            }
            
            // Update user status
            $sql = "UPDATE users SET status = 'active', updated_at = NOW() WHERE id = ?";
            $database->execute($sql, [$user_id]);
            
            // Log activity
            logActivity($admin_id, 'user_activated', "User ID: $user_id");
            
            // Send activation email
            $subject = "Account Reactivated - " . SITE_NAME;
            $message = "
                <h2>Account Reactivated</h2>
                <p>Your account has been reactivated. You can now access all platform features.</p>
                <p>Thank you for your patience.</p>
            ";
            
            sendEmail($user['email'], $subject, $message);
            
            echo json_encode([
                'success' => true,
                'message' => 'User activated successfully'
            ]);
            break;
            
        case 'approve_product':
            $product_id = intval($input['product_id'] ?? 0);
            
            // Validate product exists and is pending
            $product = $database->fetch(
                "SELECT * FROM products WHERE id = ? AND status = 'pending'",
                [$product_id]
            );
            
            if (!$product) {
                echo json_encode(['success' => false, 'message' => 'Product not found or already processed']);
                exit;
            }
            
            // Update product status
            $sql = "UPDATE products SET status = 'active', updated_at = NOW() WHERE id = ?";
            $database->execute($sql, [$product_id]);
            
            // Log activity
            logActivity($admin_id, 'product_approved', "Product ID: $product_id");
            
            // Notify vendor
            $vendor = $database->fetch("SELECT email FROM users WHERE id = ?", [$product['vendor_id']]);
            if ($vendor) {
                $subject = "Product Approved - " . SITE_NAME;
                $message = "
                    <h2>Product Approved</h2>
                    <p>Your product '{$product['name']}' has been approved and is now live on the platform.</p>
                    <p>Customers can now view and purchase this product.</p>
                ";
                
                sendEmail($vendor['email'], $subject, $message);
            }
            
            echo json_encode([
                'success' => true,
                'message' => 'Product approved successfully'
            ]);
            break;
            
        case 'reject_product':
            $product_id = intval($input['product_id'] ?? 0);
            $reason = sanitizeInput($input['reason'] ?? 'Product does not meet guidelines');
            
            // Validate product exists and is pending
            $product = $database->fetch(
                "SELECT * FROM products WHERE id = ? AND status = 'pending'",
                [$product_id]
            );
            
            if (!$product) {
                echo json_encode(['success' => false, 'message' => 'Product not found or already processed']);
                exit;
            }
            
            // Update product status
            $sql = "UPDATE products SET status = 'rejected', updated_at = NOW() WHERE id = ?";
            $database->execute($sql, [$product_id]);
            
            // Log activity
            logActivity($admin_id, 'product_rejected', "Product ID: $product_id, Reason: $reason");
            
            // Notify vendor
            $vendor = $database->fetch("SELECT email FROM users WHERE id = ?", [$product['vendor_id']]);
            if ($vendor) {
                $subject = "Product Review Update - " . SITE_NAME;
                $message = "
                    <h2>Product Review Update</h2>
                    <p>Your product '{$product['name']}' requires revision before it can be approved.</p>
                    <p><strong>Reason:</strong> $reason</p>
                    <p>Please update your product and resubmit for review.</p>
                ";
                
                sendEmail($vendor['email'], $subject, $message);
            }
            
            echo json_encode([
                'success' => true,
                'message' => 'Product rejected with feedback sent to vendor'
            ]);
            break;
            
        case 'get_platform_stats':
            // Get comprehensive platform statistics
            $platform_stats = [
                'users' => [
                    'total' => $database->fetch("SELECT COUNT(*) as count FROM users WHERE status = 'active'")['count'],
                    'customers' => $database->fetch("SELECT COUNT(*) as count FROM users WHERE user_type = 'customer' AND status = 'active'")['count'],
                    'vendors' => $database->fetch("SELECT COUNT(*) as count FROM users WHERE user_type = 'vendor' AND status = 'active'")['count'],
                    'pending_vendors' => $database->fetch("SELECT COUNT(*) as count FROM users WHERE user_type = 'vendor' AND status = 'pending'")['count']
                ],
                'products' => [
                    'total' => $database->fetch("SELECT COUNT(*) as count FROM products WHERE status = 'active'")['count'],
                    'pending' => $database->fetch("SELECT COUNT(*) as count FROM products WHERE status = 'pending'")['count']
                ],
                'orders' => [
                    'total' => $database->fetch("SELECT COUNT(*) as count FROM orders")['count'],
                    'pending' => $database->fetch("SELECT COUNT(*) as count FROM orders WHERE status IN ('pending', 'confirmed')")['count'],
                    'completed' => $database->fetch("SELECT COUNT(*) as count FROM orders WHERE status = 'delivered'")['count']
                ],
                'revenue' => [
                    'total' => $database->fetch("SELECT COALESCE(SUM(total_amount), 0) as total FROM orders WHERE payment_status = 'paid'")['total'],
                    'monthly' => $database->fetch("SELECT COALESCE(SUM(total_amount), 0) as total FROM orders WHERE payment_status = 'paid' AND MONTH(created_at) = MONTH(CURRENT_DATE()) AND YEAR(created_at) = YEAR(CURRENT_DATE())")['total']
                ]
            ];
            
            echo json_encode([
                'success' => true,
                'stats' => $platform_stats
            ]);
            break;
            
        case 'get_recent_activities':
            $limit = intval($input['limit'] ?? 20);
            
            $activities_sql = "
                SELECT al.*, u.first_name, u.last_name, u.user_type
                FROM activity_logs al
                JOIN users u ON al.user_id = u.id
                ORDER BY al.created_at DESC
                LIMIT ?
            ";
            
            $activities = $database->fetchAll($activities_sql, [$limit]);
            
            echo json_encode([
                'success' => true,
                'activities' => $activities
            ]);
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
            break;
    }
    
} catch (Exception $e) {
    error_log("Admin API Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred']);
}
?>
