<?php
/**
 * MarketHub User Management
 * Admin panel for approving/rejecting user applications
 */

require_once '../config/config.php';

$page_title = 'User Management';
$success_message = '';
$error_message = '';

// Check if user is admin (basic check for now)
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    // For now, allow access if no admin is logged in (for initial setup)
    if (isset($_SESSION['user_type'])) {
        die('Access denied. Admin privileges required.');
    }
}

// Handle user approval/rejection
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error_message = 'Invalid security token. Please try again.';
    } else {
        $user_id = intval($_POST['user_id'] ?? 0);
        $action = sanitizeInput($_POST['action'] ?? '');
        $admin_notes = sanitizeInput($_POST['admin_notes'] ?? '');
        
        if ($user_id > 0 && in_array($action, ['approve', 'reject'])) {
            try {
                // Get user details
                $user = $database->fetch(
                    "SELECT u.*, vs.store_name, vs.store_description 
                     FROM users u 
                     LEFT JOIN vendor_stores vs ON u.id = vs.vendor_id 
                     WHERE u.id = ?", 
                    [$user_id]
                );
                
                if (!$user) {
                    throw new Exception('User not found.');
                }
                
                $new_status = ($action === 'approve') ? 'active' : 'rejected';
                
                // Update user status
                $database->execute(
                    "UPDATE users SET status = ?, updated_at = NOW() WHERE id = ?", 
                    [$new_status, $user_id]
                );
                
                // Update vendor store status if applicable
                if ($user['user_type'] === 'vendor') {
                    $store_status = ($action === 'approve') ? 'approved' : 'rejected';
                    $database->execute(
                        "UPDATE vendor_stores SET status = ?, updated_at = NOW() WHERE vendor_id = ?", 
                        [$store_status, $user_id]
                    );
                }
                
                // Log activity
                logActivity(
                    $_SESSION['user_id'] ?? 0, 
                    'user_' . $action, 
                    "User {$user['email']} {$action}ed by admin"
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
                        " . (!empty($admin_notes) ? "<p><strong>Reason:</strong> $admin_notes</p>" : "") . "
                        <p>If you believe this is an error or would like to reapply, please contact our support team.</p>
                        <p>Best regards,<br>The MarketHub Team</p>
                    ";
                }
                
                sendEmail($user['email'], $subject, $message);
                
                $success_message = "User {$user['email']} has been {$action}ed successfully.";
                
            } catch (Exception $e) {
                $error_message = $e->getMessage();
            }
        } else {
            $error_message = 'Invalid request parameters.';
        }
    }
}

// Get pending users
$pending_users = $database->fetchAll(
    "SELECT u.*, vs.store_name, vs.store_description, vs.business_license, vs.city, vs.state 
     FROM users u 
     LEFT JOIN vendor_stores vs ON u.id = vs.vendor_id 
     WHERE u.status = 'pending' 
     ORDER BY u.created_at DESC"
);

// Get recent approved/rejected users
$recent_users = $database->fetchAll(
    "SELECT u.*, vs.store_name 
     FROM users u 
     LEFT JOIN vendor_stores vs ON u.id = vs.vendor_id 
     WHERE u.status IN ('active', 'rejected') 
     ORDER BY u.updated_at DESC 
     LIMIT 10"
);

// Get user statistics
$stats = [
    'pending' => $database->fetch("SELECT COUNT(*) as count FROM users WHERE status = 'pending'")['count'],
    'active' => $database->fetch("SELECT COUNT(*) as count FROM users WHERE status = 'active'")['count'],
    'rejected' => $database->fetch("SELECT COUNT(*) as count FROM users WHERE status = 'rejected'")['count'],
    'vendors' => $database->fetch("SELECT COUNT(*) as count FROM users WHERE user_type = 'vendor' AND status = 'active'")['count'],
    'customers' => $database->fetch("SELECT COUNT(*) as count FROM users WHERE user_type = 'customer' AND status = 'active'")['count']
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - MarketHub Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .admin-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 2rem;
        }
        
        .admin-header {
            background: linear-gradient(135deg, #2E7D32, #4CAF50);
            color: white;
            padding: 2rem;
            border-radius: 8px;
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: var(--primary-green);
            margin-bottom: 0.5rem;
        }
        
        .stat-label {
            color: var(--dark-gray);
            font-size: 0.9rem;
        }
        
        .user-card {
            background: white;
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .user-header {
            display: flex;
            justify-content: between;
            align-items: center;
            margin-bottom: 1rem;
        }
        
        .user-info {
            flex: 1;
        }
        
        .user-actions {
            display: flex;
            gap: 0.5rem;
        }
        
        .user-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 1rem;
        }
        
        .detail-item {
            display: flex;
            flex-direction: column;
        }
        
        .detail-label {
            font-weight: bold;
            color: var(--dark-gray);
            font-size: 0.8rem;
            margin-bottom: 0.25rem;
        }
        
        .detail-value {
            color: var(--black);
        }
        
        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            font-size: 0.9rem;
        }
        
        .btn-approve {
            background: #4CAF50;
            color: white;
        }
        
        .btn-reject {
            background: #f44336;
            color: white;
        }
        
        .btn-view {
            background: #2196F3;
            color: white;
        }
        
        .btn:hover {
            opacity: 0.9;
        }
        
        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: bold;
        }
        
        .status-pending {
            background: #fff3cd;
            color: #856404;
        }
        
        .status-active {
            background: #d4edda;
            color: #155724;
        }
        
        .status-rejected {
            background: #f8d7da;
            color: #721c24;
        }
        
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }
        
        .modal-content {
            background-color: white;
            margin: 5% auto;
            padding: 2rem;
            border-radius: 8px;
            width: 90%;
            max-width: 500px;
        }
        
        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }
        
        .close:hover {
            color: black;
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <div class="admin-header">
            <h1><i class="fas fa-users-cog"></i> User Management</h1>
            <p>Approve or reject user applications and manage user accounts</p>
        </div>
        
        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['pending']; ?></div>
                <div class="stat-label">Pending Approval</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['active']; ?></div>
                <div class="stat-label">Active Users</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['vendors']; ?></div>
                <div class="stat-label">Active Vendors</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['customers']; ?></div>
                <div class="stat-label">Active Customers</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['rejected']; ?></div>
                <div class="stat-label">Rejected</div>
            </div>
        </div>
        
        <!-- Messages -->
        <?php if ($success_message): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <?php echo htmlspecialchars($success_message); ?>
            </div>
        <?php endif; ?>

        <?php if ($error_message): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>
        
        <!-- Pending Applications -->
        <div class="section">
            <h2>Pending Applications (<?php echo count($pending_users); ?>)</h2>
            
            <?php if (empty($pending_users)): ?>
                <div class="user-card">
                    <div style="text-align: center; padding: 2rem; color: var(--dark-gray);">
                        <i class="fas fa-check-circle" style="font-size: 3rem; margin-bottom: 1rem; color: var(--primary-green);"></i>
                        <h3>No Pending Applications</h3>
                        <p>All user applications have been processed.</p>
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($pending_users as $user): ?>
                    <div class="user-card">
                        <div class="user-header">
                            <div class="user-info">
                                <h4><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>
                                    <span class="status-badge status-pending">Pending</span>
                                </h4>
                                <p style="color: var(--dark-gray); margin: 0;">
                                    <?php echo ucfirst($user['user_type']); ?> • 
                                    Registered: <?php echo date('M j, Y', strtotime($user['created_at'])); ?>
                                </p>
                            </div>
                            <div class="user-actions">
                                <button onclick="approveUser(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['email']); ?>')" 
                                        class="btn btn-approve">
                                    <i class="fas fa-check"></i> Approve
                                </button>
                                <button onclick="rejectUser(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['email']); ?>')" 
                                        class="btn btn-reject">
                                    <i class="fas fa-times"></i> Reject
                                </button>
                            </div>
                        </div>
                        
                        <div class="user-details">
                            <div class="detail-item">
                                <span class="detail-label">Email</span>
                                <span class="detail-value"><?php echo htmlspecialchars($user['email']); ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Phone</span>
                                <span class="detail-value"><?php echo htmlspecialchars($user['phone'] ?: 'Not provided'); ?></span>
                            </div>
                            <?php if ($user['user_type'] === 'vendor'): ?>
                                <div class="detail-item">
                                    <span class="detail-label">Store Name</span>
                                    <span class="detail-value"><?php echo htmlspecialchars($user['store_name'] ?: 'Not provided'); ?></span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Business License</span>
                                    <span class="detail-value"><?php echo htmlspecialchars($user['business_license'] ?: 'Not provided'); ?></span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Location</span>
                                    <span class="detail-value"><?php echo htmlspecialchars(($user['city'] ?: '') . ($user['state'] ? ', ' . $user['state'] : '')); ?></span>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <?php if ($user['user_type'] === 'vendor' && $user['store_description']): ?>
                            <div style="margin-top: 1rem;">
                                <span class="detail-label">Store Description</span>
                                <p style="margin-top: 0.5rem; color: var(--dark-gray);">
                                    <?php echo htmlspecialchars($user['store_description']); ?>
                                </p>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <!-- Recent Actions -->
        <div class="section" style="margin-top: 3rem;">
            <h2>Recent Actions</h2>
            
            <?php if (empty($recent_users)): ?>
                <div class="user-card">
                    <div style="text-align: center; padding: 2rem; color: var(--dark-gray);">
                        <p>No recent actions.</p>
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($recent_users as $user): ?>
                    <div class="user-card">
                        <div class="user-header">
                            <div class="user-info">
                                <h4><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>
                                    <span class="status-badge status-<?php echo $user['status']; ?>">
                                        <?php echo ucfirst($user['status']); ?>
                                    </span>
                                </h4>
                                <p style="color: var(--dark-gray); margin: 0;">
                                    <?php echo ucfirst($user['user_type']); ?> • 
                                    <?php echo htmlspecialchars($user['email']); ?> • 
                                    Updated: <?php echo date('M j, Y', strtotime($user['updated_at'])); ?>
                                </p>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <!-- Quick Actions -->
        <div style="margin-top: 3rem; text-align: center;">
            <a href="../index.php" class="btn btn-view">
                <i class="fas fa-home"></i> Go to Homepage
            </a>
            <a href="dashboard.php" class="btn btn-view">
                <i class="fas fa-tachometer-alt"></i> Admin Dashboard
            </a>
            <a href="email-test.php" class="btn btn-view">
                <i class="fas fa-envelope"></i> Email Test
            </a>
        </div>
    </div>
    
    <!-- Approval Modal -->
    <div id="approvalModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h3>Approve User</h3>
            <p>Are you sure you want to approve this user?</p>
            <p><strong>Email:</strong> <span id="approveEmail"></span></p>
            
            <form method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                <input type="hidden" name="user_id" id="approveUserId">
                <input type="hidden" name="action" value="approve">
                
                <div style="margin: 1rem 0;">
                    <label for="approve_notes">Admin Notes (optional):</label>
                    <textarea name="admin_notes" id="approve_notes" class="form-control" rows="3" 
                              placeholder="Optional notes about the approval..."></textarea>
                </div>
                
                <div style="text-align: right; margin-top: 1rem;">
                    <button type="button" onclick="closeModal()" class="btn" style="background: #ccc;">Cancel</button>
                    <button type="submit" class="btn btn-approve">Approve User</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Rejection Modal -->
    <div id="rejectionModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h3>Reject User</h3>
            <p>Are you sure you want to reject this user?</p>
            <p><strong>Email:</strong> <span id="rejectEmail"></span></p>
            
            <form method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                <input type="hidden" name="user_id" id="rejectUserId">
                <input type="hidden" name="action" value="reject">
                
                <div style="margin: 1rem 0;">
                    <label for="reject_notes">Reason for Rejection:</label>
                    <textarea name="admin_notes" id="reject_notes" class="form-control" rows="3" 
                              placeholder="Please provide a reason for rejection..." required></textarea>
                </div>
                
                <div style="text-align: right; margin-top: 1rem;">
                    <button type="button" onclick="closeModal()" class="btn" style="background: #ccc;">Cancel</button>
                    <button type="submit" class="btn btn-reject">Reject User</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function approveUser(userId, email) {
            document.getElementById('approveUserId').value = userId;
            document.getElementById('approveEmail').textContent = email;
            document.getElementById('approvalModal').style.display = 'block';
        }
        
        function rejectUser(userId, email) {
            document.getElementById('rejectUserId').value = userId;
            document.getElementById('rejectEmail').textContent = email;
            document.getElementById('rejectionModal').style.display = 'block';
        }
        
        function closeModal() {
            document.getElementById('approvalModal').style.display = 'none';
            document.getElementById('rejectionModal').style.display = 'none';
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            const approvalModal = document.getElementById('approvalModal');
            const rejectionModal = document.getElementById('rejectionModal');
            
            if (event.target === approvalModal) {
                approvalModal.style.display = 'none';
            }
            if (event.target === rejectionModal) {
                rejectionModal.style.display = 'none';
            }
        }
        
        // Close modal with X button
        document.querySelectorAll('.close').forEach(closeBtn => {
            closeBtn.onclick = closeModal;
        });
    </script>
</body>
</html>
