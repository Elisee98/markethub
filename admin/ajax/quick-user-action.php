<?php
/**
 * Quick User Action Handler
 * AJAX endpoint for approving/rejecting users
 */

require_once '../../config/config.php';

// Set JSON header
header('Content-Type: application/json');

// Check if user is admin
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit;
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

try {
    // Handle both JSON and form data
    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input) {
        // Fallback to POST data
        $input = $_POST;
    }

    $user_id = intval($input['user_id'] ?? 0);
    $action = sanitizeInput($input['action'] ?? '');
    $reason = sanitizeInput($input['reason'] ?? '');

    if ($user_id <= 0) {
        throw new Exception('Invalid user ID');
    }

    if (!in_array($action, ['approve', 'reject', 'activate', 'deactivate'])) {
        throw new Exception('Invalid action');
    }
    
    // Get user details
    $user = $database->fetch(
        "SELECT u.*, vs.store_name, vs.store_description 
         FROM users u 
         LEFT JOIN vendor_stores vs ON u.id = vs.vendor_id 
         WHERE u.id = ?", 
        [$user_id]
    );
    
    if (!$user) {
        throw new Exception('User not found');
    }
    
    // Determine new status based on action
    switch ($action) {
        case 'approve':
            if ($user['status'] !== 'pending') {
                throw new Exception('User is not pending approval');
            }
            $new_status = 'active';
            $store_status = 'approved';
            break;

        case 'reject':
            if ($user['status'] !== 'pending') {
                throw new Exception('User is not pending approval');
            }
            $new_status = 'rejected';
            $store_status = 'rejected';
            break;

        case 'activate':
            if ($user['status'] === 'active') {
                throw new Exception('User is already active');
            }
            $new_status = 'active';
            $store_status = 'approved';
            break;

        case 'deactivate':
            if ($user['status'] !== 'active') {
                throw new Exception('User is not active');
            }
            $new_status = 'inactive';
            $store_status = 'suspended';
            break;

        default:
            throw new Exception('Invalid action');
    }

    // Update user status
    $database->execute(
        "UPDATE users SET status = ?, updated_at = NOW() WHERE id = ?",
        [$new_status, $user_id]
    );

    // Update vendor store status if applicable
    if ($user['user_type'] === 'vendor') {
        $database->execute(
            "UPDATE vendor_stores SET status = ?, updated_at = NOW() WHERE vendor_id = ?",
            [$store_status, $user_id]
        );
    }
    
    // Log activity
    logActivity(
        $_SESSION['user_id'] ?? 0, 
        'user_' . $action, 
        "User {$user['email']} {$action}ed by admin via quick action"
    );
    
    // Send notification email to user
    $subject = "MarketHub Account " . ucfirst($action) . "d";
    
    if ($action === 'approve') {
        $message = "
            <h2>Account Approved!</h2>
            <p>Dear {$user['first_name']},</p>
            <p>Great news! Your MarketHub account has been approved and is now active.</p>
            " . ($user['user_type'] === 'vendor' ? "
            <p><strong>Store Details:</strong></p>
            <ul>
                <li>Store Name: {$user['store_name']}</li>
                <li>Status: Approved</li>
            </ul>
            <p>You can now:</p>
            <ul>
                <li>Login to your vendor dashboard</li>
                <li>Add and manage your products</li>
                <li>Process customer orders</li>
                <li>View sales analytics</li>
            </ul>
            <p><a href='" . SITE_URL . "vendor/dashboard.php' style='background: #2E7D32; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px;'>Access Vendor Dashboard</a></p>
            " : "
            <p>You can now:</p>
            <ul>
                <li>Browse and purchase products</li>
                <li>Add items to your cart</li>
                <li>Track your orders</li>
                <li>Leave product reviews</li>
            </ul>
            <p><a href='" . SITE_URL . "login.php' style='background: #2E7D32; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px;'>Login to Your Account</a></p>
            ") . "
            <p>Welcome to MarketHub!<br>The MarketHub Team</p>
        ";
    } else {
        $message = "
            <h2>Account Application Status</h2>
            <p>Dear {$user['first_name']},</p>
            <p>Thank you for your interest in MarketHub. After careful review, we are unable to approve your account at this time.</p>
            " . (!empty($reason) ? "<p><strong>Reason:</strong> $reason</p>" : "") . "
            <p>If you believe this is an error or would like to reapply, please contact our support team.</p>
            <p>Best regards,<br>The MarketHub Team</p>
        ";
    }
    
    sendEmail($user['email'], $subject, $message);
    
    echo json_encode([
        'success' => true, 
        'message' => "User {$user['email']} has been {$action}ed successfully."
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false, 
        'message' => $e->getMessage()
    ]);
}
?>
