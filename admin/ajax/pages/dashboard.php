<?php
/**
 * Dashboard Page Content
 */

// Get dashboard statistics
$stats = [
    'pending_users' => $database->fetch("SELECT COUNT(*) as count FROM users WHERE status = 'pending'")['count'],
    'total_users' => $database->fetch("SELECT COUNT(*) as count FROM users")['count'],
    'active_vendors' => $database->fetch("SELECT COUNT(*) as count FROM users WHERE user_type = 'vendor' AND status = 'active'")['count'],
    'active_customers' => $database->fetch("SELECT COUNT(*) as count FROM users WHERE user_type = 'customer' AND status = 'active'")['count'],
    'total_products' => $database->fetch("SELECT COUNT(*) as count FROM products WHERE status = 'active'")['count'],
    'total_orders' => $database->fetch("SELECT COUNT(*) as count FROM orders")['count'],
    'pending_orders' => $database->fetch("SELECT COUNT(*) as count FROM orders WHERE status = 'pending'")['count'],
    'total_revenue' => $database->fetch("SELECT SUM(total_amount) as total FROM orders WHERE payment_status = 'paid'")['total'] ?? 0
];

// Get recent activities
$recent_activities = $database->fetchAll(
    "SELECT al.*, u.first_name, u.last_name 
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
?>

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
        display: flex;
        align-items: center;
        justify-content: space-between;
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

    .user-item, .order-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 1rem;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        margin-bottom: 0.75rem;
    }

    .user-info h5, .order-info h5 {
        margin: 0 0 0.25rem 0;
        color: #1e293b;
    }

    .user-info small, .order-info small {
        color: #64748b;
    }

    .btn {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.5rem 1rem;
        border-radius: 6px;
        text-decoration: none;
        font-weight: 500;
        transition: all 0.2s;
        border: none;
        cursor: pointer;
        font-size: 0.875rem;
    }

    .btn-sm {
        padding: 0.375rem 0.75rem;
        font-size: 0.8rem;
    }

    .btn-primary {
        background: #3b82f6;
        color: white;
    }

    .btn-success {
        background: #10b981;
        color: white;
    }

    .btn-danger {
        background: #ef4444;
        color: white;
    }

    .btn:hover {
        opacity: 0.9;
        transform: translateY(-1px);
    }

    .status-badge {
        padding: 0.25rem 0.75rem;
        border-radius: 12px;
        font-size: 0.75rem;
        font-weight: 600;
    }

    .status-pending {
        background: #fef3c7;
        color: #92400e;
    }

    .status-active {
        background: #d1fae5;
        color: #065f46;
    }

    .status-paid {
        background: #d1fae5;
        color: #065f46;
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
        <div class="stat-icon" style="background: #ef4444;">
            <i class="fas fa-user-clock"></i>
        </div>
        <h3><?php echo number_format($stats['pending_users']); ?></h3>
        <p>Pending Approvals</p>
        <?php if ($stats['pending_users'] > 0): ?>
            <small><a href="#" onclick="loadPage('users')" style="color: #3b82f6; text-decoration: none;">Review Now →</a></small>
        <?php endif; ?>
    </div>

    <div class="stat-card">
        <div class="stat-icon" style="background: #3b82f6;">
            <i class="fas fa-users"></i>
        </div>
        <h3><?php echo number_format($stats['total_users']); ?></h3>
        <p>Total Users</p>
        <small><?php echo $stats['active_vendors']; ?> vendors, <?php echo $stats['active_customers']; ?> customers</small>
    </div>

    <div class="stat-card">
        <div class="stat-icon" style="background: #10b981;">
            <i class="fas fa-box"></i>
        </div>
        <h3><?php echo number_format($stats['total_products']); ?></h3>
        <p>Active Products</p>
        <small><a href="#" onclick="loadPage('products')" style="color: #3b82f6; text-decoration: none;">Manage Products →</a></small>
    </div>

    <div class="stat-card">
        <div class="stat-icon" style="background: #8b5cf6;">
            <i class="fas fa-shopping-cart"></i>
        </div>
        <h3><?php echo number_format($stats['total_orders']); ?></h3>
        <p>Total Orders</p>
        <?php if ($stats['pending_orders'] > 0): ?>
            <small><?php echo $stats['pending_orders']; ?> pending</small>
        <?php endif; ?>
    </div>
</div>

<!-- Dashboard Widgets -->
<div class="dashboard-grid">
    <!-- Pending Users -->
    <div class="widget">
        <div class="widget-header">
            <span><i class="fas fa-user-clock"></i> Pending Approvals</span>
            <a href="#" onclick="loadPage('users')" style="color: #3b82f6; text-decoration: none; font-size: 0.875rem;">View All</a>
        </div>
        <div class="widget-content">
            <?php if (empty($pending_users)): ?>
                <div class="no-data">
                    <i class="fas fa-check-circle" style="font-size: 2rem; margin-bottom: 1rem; color: #10b981;"></i>
                    <p>No pending approvals</p>
                </div>
            <?php else: ?>
                <?php foreach ($pending_users as $user): ?>
                    <div class="user-item">
                        <div class="user-info">
                            <h5><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></h5>
                            <small>
                                <?php echo ucfirst($user['user_type']); ?>
                                <?php if ($user['store_name']): ?>
                                    - <?php echo htmlspecialchars($user['store_name']); ?>
                                <?php endif; ?>
                            </small>
                        </div>
                        <div class="user-actions">
                            <button onclick="approveUser(<?php echo $user['id']; ?>)" class="btn btn-sm btn-success">
                                <i class="fas fa-check"></i> Approve
                            </button>
                            <button onclick="rejectUser(<?php echo $user['id']; ?>)" class="btn btn-sm btn-danger">
                                <i class="fas fa-times"></i> Reject
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Recent Orders -->
    <div class="widget">
        <div class="widget-header">
            <span><i class="fas fa-shopping-cart"></i> Recent Orders</span>
            <a href="#" onclick="loadPage('orders')" style="color: #3b82f6; text-decoration: none; font-size: 0.875rem;">View All</a>
        </div>
        <div class="widget-content">
            <?php if (empty($recent_orders)): ?>
                <div class="no-data">
                    <i class="fas fa-shopping-cart" style="font-size: 2rem; margin-bottom: 1rem; color: #94a3b8;"></i>
                    <p>No recent orders</p>
                </div>
            <?php else: ?>
                <?php foreach ($recent_orders as $order): ?>
                    <div class="order-item">
                        <div class="order-info">
                            <h5>Order #<?php echo $order['id']; ?></h5>
                            <small>
                                <?php echo htmlspecialchars($order['first_name'] . ' ' . $order['last_name']); ?> - 
                                <?php echo formatCurrency($order['total_amount']); ?>
                            </small>
                        </div>
                        <div class="order-status">
                            <span class="status-badge status-<?php echo $order['payment_status']; ?>">
                                <?php echo ucfirst($order['payment_status']); ?>
                            </span>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
// Quick user approval functions
function approveUser(userId) {
    if (confirm('Are you sure you want to approve this user?')) {
        fetch('../ajax/quick-user-action.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=approve&user_id=${userId}&csrf_token=<?php echo generateCSRFToken(); ?>`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification('User approved successfully!', 'success');
                loadPage('dashboard'); // Reload dashboard
                updatePendingCount(); // Update badge
            } else {
                showNotification(data.message || 'Error approving user', 'error');
            }
        })
        .catch(error => {
            showNotification('Error approving user', 'error');
        });
    }
}

function rejectUser(userId) {
    if (confirm('Are you sure you want to reject this user? This action cannot be undone.')) {
        fetch('../ajax/quick-user-action.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=reject&user_id=${userId}&csrf_token=<?php echo generateCSRFToken(); ?>`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification('User rejected successfully!', 'success');
                loadPage('dashboard'); // Reload dashboard
                updatePendingCount(); // Update badge
            } else {
                showNotification(data.message || 'Error rejecting user', 'error');
            }
        })
        .catch(error => {
            showNotification('Error rejecting user', 'error');
        });
    }
}
</script>
