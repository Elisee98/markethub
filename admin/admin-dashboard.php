<?php
/**
 * MarketHub Admin Dashboard - Redirect to SPA
 * Redirects to the new Single Page Application dashboard
 */

require_once '../config/config.php';

// Redirect to new SPA dashboard
redirect('spa-dashboard.php');
?>

// Get dashboard statistics
$stats = [
    'pending_users' => $database->fetch("SELECT COUNT(*) as count FROM users WHERE status = 'pending'")['count'],
    'total_users' => $database->fetch("SELECT COUNT(*) as count FROM users")['count'],
    'active_vendors' => $database->fetch("SELECT COUNT(*) as count FROM users WHERE user_type = 'vendor' AND status = 'active'")['count'],
    'active_customers' => $database->fetch("SELECT COUNT(*) as count FROM users WHERE user_type = 'customer' AND status = 'active'")['count'],
    'total_products' => $database->fetch("SELECT COUNT(*) as count FROM products WHERE status = 'active'")['count'],
    'total_orders' => $database->fetch("SELECT COUNT(*) as count FROM orders")['count'],
    'pending_orders' => $database->fetch("SELECT COUNT(*) as count FROM orders WHERE status = 'pending'")['count'],
    'total_categories' => $database->fetch("SELECT COUNT(*) as count FROM categories WHERE status = 'active'")['count']
];

// Get recent activities
$recent_activities = $database->fetchAll(
    "SELECT al.*, u.first_name, u.last_name, u.email 
     FROM activity_logs al 
     LEFT JOIN users u ON al.user_id = u.id 
     ORDER BY al.created_at DESC 
     LIMIT 10"
);

// Get pending users for quick approval
$pending_users = $database->fetchAll(
    "SELECT u.*, vs.store_name 
     FROM users u 
     LEFT JOIN vendor_stores vs ON u.id = vs.vendor_id 
     WHERE u.status = 'pending' 
     ORDER BY u.created_at DESC 
     LIMIT 5"
);

// Get recent orders
$recent_orders = $database->fetchAll(
    "SELECT o.*, u.first_name, u.last_name 
     FROM orders o 
     JOIN users u ON o.customer_id = u.id 
     ORDER BY o.created_at DESC 
     LIMIT 5"
);

require_once 'includes/admin_header_new.php';
?>

<!-- Dashboard Content -->
<style>
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 1.5rem;
        margin-bottom: 2rem;
    }

    .stat-card {
        background: white;
        border-radius: 12px;
        padding: 1.5rem;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        border: 1px solid #e2e8f0;
        transition: all 0.2s;
    }

    .stat-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    }

    .stat-card h3 {
        font-size: 2rem;
        font-weight: 700;
        color: #1e293b;
        margin-bottom: 0.5rem;
    }

    .stat-card p {
        color: #64748b;
        font-weight: 500;
        margin-bottom: 0.5rem;
    }

    .stat-card small {
        color: #94a3b8;
        font-size: 0.875rem;
    }

    .stat-icon {
        width: 48px;
        height: 48px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 1.25rem;
        margin-bottom: 1rem;
    }

    .dashboard-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
        gap: 1.5rem;
    }

    .widget {
        background: white;
        border-radius: 12px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        border: 1px solid #e2e8f0;
        overflow: hidden;
    }

    .widget-header {
        padding: 1.25rem 1.5rem;
        background: #f8fafc;
        border-bottom: 1px solid #e2e8f0;
        font-weight: 600;
        color: #1e293b;
    }

    .widget-content {
        padding: 1.5rem;
    }

    .no-data {
        text-align: center;
        color: #94a3b8;
        padding: 2rem;
        font-style: italic;
    }
</style>

<div style="margin-bottom: 2rem;">
    <h2 style="font-size: 1.875rem; font-weight: 700; color: #1e293b; margin-bottom: 0.5rem;">
        <i class="fas fa-chart-line" style="color: #3b82f6; margin-right: 0.5rem;"></i>
        Dashboard Overview
    </h2>
    <p style="color: #64748b; font-size: 1.125rem;">Welcome to MarketHub Administration Panel</p>
</div>

    <!-- Statistics Cards -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon" style="background: #FF5722;">
                <i class="fas fa-user-clock"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo number_format($stats['pending_users']); ?></h3>
                <p>Pending Approvals</p>
                <?php if ($stats['pending_users'] > 0): ?>
                    <a href="user-management.php" class="stat-link">Review Now</a>
                <?php endif; ?>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon" style="background: #2196F3;">
                <i class="fas fa-users"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo number_format($stats['total_users']); ?></h3>
                <p>Total Users</p>
                <small><?php echo $stats['active_vendors']; ?> vendors, <?php echo $stats['active_customers']; ?> customers</small>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon" style="background: #4CAF50;">
                <i class="fas fa-box"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo number_format($stats['total_products']); ?></h3>
                <p>Active Products</p>
                <a href="products.php" class="stat-link">Manage Products</a>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon" style="background: #9C27B0;">
                <i class="fas fa-shopping-cart"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo number_format($stats['total_orders']); ?></h3>
                <p>Total Orders</p>
                <?php if ($stats['pending_orders'] > 0): ?>
                    <small><?php echo $stats['pending_orders']; ?> pending</small>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="quick-actions">
        <h3>Quick Actions</h3>
        <div class="action-buttons">
            <?php if ($stats['pending_users'] > 0): ?>
                <a href="user-management.php" class="action-btn urgent">
                    <i class="fas fa-user-check"></i>
                    <span>Approve Users (<?php echo $stats['pending_users']; ?>)</span>
                </a>
            <?php endif; ?>
            
            <a href="user-management.php?action=create" class="action-btn">
                <i class="fas fa-user-plus"></i>
                <span>Add New User</span>
            </a>
            
            <a href="categories.php" class="action-btn">
                <i class="fas fa-tags"></i>
                <span>Manage Categories</span>
            </a>
            
            <a href="system-settings.php" class="action-btn">
                <i class="fas fa-cog"></i>
                <span>System Settings</span>
            </a>
            
            <a href="../index.php" class="action-btn customer">
                <i class="fas fa-shopping-bag"></i>
                <span>Shop as Customer</span>
            </a>
        </div>
    </div>

    <!-- Dashboard Grid -->
    <div class="dashboard-grid">
        <!-- Pending Users -->
        <?php if (!empty($pending_users)): ?>
            <div class="dashboard-widget">
                <div class="widget-header">
                    <h4><i class="fas fa-user-clock"></i> Pending User Approvals</h4>
                    <a href="user-management.php" class="widget-link">View All</a>
                </div>
                <div class="widget-content">
                    <?php foreach ($pending_users as $user): ?>
                        <div class="pending-user-item">
                            <div class="user-info">
                                <h5><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></h5>
                                <p><?php echo htmlspecialchars($user['email']); ?></p>
                                <span class="user-type"><?php echo ucfirst($user['user_type']); ?></span>
                                <?php if ($user['store_name']): ?>
                                    <span class="store-name"><?php echo htmlspecialchars($user['store_name']); ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="user-actions">
                                <button onclick="quickApprove(<?php echo $user['id']; ?>)" class="btn-quick approve">
                                    <i class="fas fa-check"></i>
                                </button>
                                <button onclick="quickReject(<?php echo $user['id']; ?>)" class="btn-quick reject">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Recent Orders -->
        <div class="dashboard-widget">
            <div class="widget-header">
                <h4><i class="fas fa-shopping-cart"></i> Recent Orders</h4>
                <a href="orders.php" class="widget-link">View All</a>
            </div>
            <div class="widget-content">
                <?php if (empty($recent_orders)): ?>
                    <p class="no-data">No orders yet</p>
                <?php else: ?>
                    <?php foreach ($recent_orders as $order): ?>
                        <div class="order-item">
                            <div class="order-info">
                                <h5>Order #<?php echo $order['id']; ?></h5>
                                <p><?php echo htmlspecialchars($order['first_name'] . ' ' . $order['last_name']); ?></p>
                                <span class="order-total"><?php echo formatCurrency($order['total_amount']); ?></span>
                            </div>
                            <div class="order-status">
                                <span class="status-badge status-<?php echo $order['status']; ?>">
                                    <?php echo ucfirst($order['status']); ?>
                                </span>
                                <small><?php echo date('M j, Y', strtotime($order['created_at'])); ?></small>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Recent Activities -->
        <div class="dashboard-widget">
            <div class="widget-header">
                <h4><i class="fas fa-history"></i> Recent Activities</h4>
                <a href="activity-logs.php" class="widget-link">View All</a>
            </div>
            <div class="widget-content">
                <?php if (empty($recent_activities)): ?>
                    <p class="no-data">No recent activities</p>
                <?php else: ?>
                    <?php foreach ($recent_activities as $activity): ?>
                        <div class="activity-item">
                            <div class="activity-info">
                                <p><strong><?php echo htmlspecialchars($activity['action']); ?></strong></p>
                                <?php if ($activity['first_name']): ?>
                                    <small>by <?php echo htmlspecialchars($activity['first_name'] . ' ' . $activity['last_name']); ?></small>
                                <?php endif; ?>
                                <small class="activity-time"><?php echo date('M j, Y H:i', strtotime($activity['created_at'])); ?></small>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- System Status -->
        <div class="dashboard-widget">
            <div class="widget-header">
                <h4><i class="fas fa-heartbeat"></i> System Status</h4>
                <a href="system-status.php" class="widget-link">Full Status</a>
            </div>
            <div class="widget-content">
                <div class="status-item">
                    <span>Database</span>
                    <span class="status-ok">Online</span>
                </div>
                <div class="status-item">
                    <span>Email System</span>
                    <span class="status-<?php echo DEVELOPMENT_MODE ? 'warning' : 'ok'; ?>">
                        <?php echo DEVELOPMENT_MODE ? 'Dev Mode' : 'Active'; ?>
                    </span>
                </div>
                <div class="status-item">
                    <span>User Approval</span>
                    <span class="status-<?php echo REQUIRE_ADMIN_APPROVAL ? 'ok' : 'warning'; ?>">
                        <?php echo REQUIRE_ADMIN_APPROVAL ? 'Enabled' : 'Disabled'; ?>
                    </span>
                </div>
                <div class="status-item">
                    <span>Categories</span>
                    <span class="status-ok"><?php echo $stats['total_categories']; ?> Active</span>
                </div>
            </div>
        </div>


<script>
function quickApprove(userId) {
    if (confirm('Are you sure you want to approve this user?')) {
        fetch('ajax/quick-user-action.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                user_id: userId,
                action: 'approve'
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification(data.message, 'success');
                location.reload();
            } else {
                showNotification('Error: ' + data.message, 'error');
            }
        })
        .catch(error => {
            showNotification('Error processing request', 'error');
        });
    }
}

function quickReject(userId) {
    const reason = prompt('Please provide a reason for rejection:');
    if (reason) {
        fetch('ajax/quick-user-action.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                user_id: userId,
                action: 'reject',
                reason: reason
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification(data.message, 'success');
                location.reload();
            } else {
                showNotification('Error: ' + data.message, 'error');
            }
        })
        .catch(error => {
            showNotification('Error processing request', 'error');
        });
    }
}
</script>

<?php require_once 'includes/admin_footer_new.php'; ?>
