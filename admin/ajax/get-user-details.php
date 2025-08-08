<?php
/**
 * Get User Details
 * AJAX endpoint for fetching detailed user information
 */

require_once '../../config/config.php';

// Set JSON header
header('Content-Type: application/json');

// Check if user is admin
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit;
}

try {
    $user_id = intval($_GET['user_id'] ?? 0);
    
    if ($user_id <= 0) {
        throw new Exception('Invalid user ID');
    }
    
    // Get user details with vendor store information
    $user = $database->fetch(
        "SELECT u.*, vs.store_name, vs.store_description, vs.logo_url, vs.address, 
                vs.business_hours, vs.website, vs.phone as store_phone, vs.email as store_email,
                vs.status as store_status, vs.created_at as store_created_at
         FROM users u 
         LEFT JOIN vendor_stores vs ON u.id = vs.vendor_id 
         WHERE u.id = ?", 
        [$user_id]
    );
    
    if (!$user) {
        throw new Exception('User not found');
    }
    
    // Get user statistics
    $stats = [];
    if ($user['user_type'] === 'vendor') {
        $stats = [
            'total_products' => $database->fetch("SELECT COUNT(*) as count FROM products WHERE vendor_id = ?", [$user_id])['count'],
            'active_products' => $database->fetch("SELECT COUNT(*) as count FROM products WHERE vendor_id = ? AND status = 'active'", [$user_id])['count'],
            'total_orders' => $database->fetch("SELECT COUNT(*) as count FROM orders o JOIN products p ON o.product_id = p.id WHERE p.vendor_id = ?", [$user_id])['count'] ?? 0,
            'total_revenue' => $database->fetch("SELECT SUM(o.total_amount) as total FROM orders o JOIN products p ON o.product_id = p.id WHERE p.vendor_id = ? AND o.status = 'completed'", [$user_id])['total'] ?? 0
        ];
    } else {
        $stats = [
            'total_orders' => $database->fetch("SELECT COUNT(*) as count FROM orders WHERE customer_id = ?", [$user_id])['count'] ?? 0,
            'total_spent' => $database->fetch("SELECT SUM(total_amount) as total FROM orders WHERE customer_id = ? AND status = 'completed'", [$user_id])['total'] ?? 0
        ];
    }
    
    // Generate HTML content
    ob_start();
    ?>
    
    <div class="user-details">
        <!-- Basic Information -->
        <div class="detail-section">
            <h4><i class="fas fa-user"></i> Basic Information</h4>
            <div class="detail-grid">
                <div class="detail-item">
                    <label>Full Name:</label>
                    <span><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></span>
                </div>
                <div class="detail-item">
                    <label>Username:</label>
                    <span><?php echo htmlspecialchars($user['username']); ?></span>
                </div>
                <div class="detail-item">
                    <label>Email:</label>
                    <span><?php echo htmlspecialchars($user['email']); ?></span>
                </div>
                <div class="detail-item">
                    <label>Phone:</label>
                    <span><?php echo htmlspecialchars($user['phone'] ?: 'Not provided'); ?></span>
                </div>
                <div class="detail-item">
                    <label>User Type:</label>
                    <span class="badge badge-<?php echo $user['user_type']; ?>"><?php echo ucfirst($user['user_type']); ?></span>
                </div>
                <div class="detail-item">
                    <label>Status:</label>
                    <span class="badge badge-<?php echo $user['status']; ?>"><?php echo ucfirst($user['status']); ?></span>
                </div>
                <div class="detail-item">
                    <label>Registered:</label>
                    <span><?php echo date('M j, Y g:i A', strtotime($user['created_at'])); ?></span>
                </div>
                <div class="detail-item">
                    <label>Last Updated:</label>
                    <span><?php echo date('M j, Y g:i A', strtotime($user['updated_at'])); ?></span>
                </div>
            </div>
        </div>
        
        <?php if ($user['user_type'] === 'vendor' && $user['store_name']): ?>
        <!-- Vendor Store Information -->
        <div class="detail-section">
            <h4><i class="fas fa-store"></i> Store Information</h4>
            <div class="detail-grid">
                <div class="detail-item">
                    <label>Store Name:</label>
                    <span><?php echo htmlspecialchars($user['store_name']); ?></span>
                </div>
                <div class="detail-item">
                    <label>Store Status:</label>
                    <span class="badge badge-<?php echo $user['store_status']; ?>"><?php echo ucfirst($user['store_status']); ?></span>
                </div>
                <?php if ($user['store_description']): ?>
                <div class="detail-item full-width">
                    <label>Description:</label>
                    <span><?php echo nl2br(htmlspecialchars($user['store_description'])); ?></span>
                </div>
                <?php endif; ?>
                <?php if ($user['address']): ?>
                <div class="detail-item full-width">
                    <label>Address:</label>
                    <span><?php echo nl2br(htmlspecialchars($user['address'])); ?></span>
                </div>
                <?php endif; ?>
                <?php if ($user['store_phone']): ?>
                <div class="detail-item">
                    <label>Store Phone:</label>
                    <span><?php echo htmlspecialchars($user['store_phone']); ?></span>
                </div>
                <?php endif; ?>
                <?php if ($user['store_email']): ?>
                <div class="detail-item">
                    <label>Store Email:</label>
                    <span><?php echo htmlspecialchars($user['store_email']); ?></span>
                </div>
                <?php endif; ?>
                <?php if ($user['website']): ?>
                <div class="detail-item">
                    <label>Website:</label>
                    <span><a href="<?php echo htmlspecialchars($user['website']); ?>" target="_blank"><?php echo htmlspecialchars($user['website']); ?></a></span>
                </div>
                <?php endif; ?>
                <?php if ($user['business_hours']): ?>
                <div class="detail-item full-width">
                    <label>Business Hours:</label>
                    <span><?php echo nl2br(htmlspecialchars($user['business_hours'])); ?></span>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Statistics -->
        <div class="detail-section">
            <h4><i class="fas fa-chart-bar"></i> Statistics</h4>
            <div class="stats-grid">
                <?php if ($user['user_type'] === 'vendor'): ?>
                    <div class="stat-item">
                        <div class="stat-value"><?php echo $stats['total_products']; ?></div>
                        <div class="stat-label">Total Products</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value"><?php echo $stats['active_products']; ?></div>
                        <div class="stat-label">Active Products</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value"><?php echo $stats['total_orders']; ?></div>
                        <div class="stat-label">Total Orders</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value">RWF <?php echo number_format($stats['total_revenue'], 2); ?></div>
                        <div class="stat-label">Total Revenue</div>
                    </div>
                <?php else: ?>
                    <div class="stat-item">
                        <div class="stat-value"><?php echo $stats['total_orders']; ?></div>
                        <div class="stat-label">Total Orders</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value">RWF <?php echo number_format($stats['total_spent'], 2); ?></div>
                        <div class="stat-label">Total Spent</div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <style>
        .user-details {
            max-height: 70vh;
            overflow-y: auto;
        }
        
        .detail-section {
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #eee;
        }
        
        .detail-section:last-child {
            border-bottom: none;
        }
        
        .detail-section h4 {
            color: #333;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .detail-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
        }
        
        .detail-item {
            display: flex;
            flex-direction: column;
        }
        
        .detail-item.full-width {
            grid-column: 1 / -1;
        }
        
        .detail-item label {
            font-weight: bold;
            color: #666;
            margin-bottom: 0.25rem;
            font-size: 0.9rem;
        }
        
        .detail-item span {
            color: #333;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1rem;
        }
        
        .stat-item {
            text-align: center;
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 8px;
        }
        
        .stat-value {
            font-size: 1.5rem;
            font-weight: bold;
            color: #007bff;
        }
        
        .stat-label {
            color: #666;
            font-size: 0.9rem;
            margin-top: 0.25rem;
        }
        
        .badge {
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.8rem;
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .badge-customer { background: #e3f2fd; color: #1976d2; }
        .badge-vendor { background: #f3e5f5; color: #7b1fa2; }
        .badge-admin { background: #ffebee; color: #c62828; }
        .badge-active { background: #e8f5e8; color: #2e7d32; }
        .badge-pending { background: #fff3e0; color: #f57c00; }
        .badge-inactive { background: #fafafa; color: #616161; }
        .badge-rejected { background: #ffebee; color: #c62828; }
        .badge-approved { background: #e8f5e8; color: #2e7d32; }
        .badge-suspended { background: #ffebee; color: #c62828; }
    </style>
    
    <?php
    $html = ob_get_clean();
    
    echo json_encode([
        'success' => true,
        'html' => $html
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
