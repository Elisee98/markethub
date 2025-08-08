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
$stats_queries = [
    'total_users' => "SELECT COUNT(*) as count FROM users WHERE status = 'active'",
    'total_vendors' => "SELECT COUNT(*) as count FROM users WHERE user_type = 'vendor' AND status = 'active'",
    'total_customers' => "SELECT COUNT(*) as count FROM users WHERE user_type = 'customer' AND status = 'active'",
    'pending_vendors' => "SELECT COUNT(*) as count FROM users WHERE user_type = 'vendor' AND status = 'pending'",
    'total_products' => "SELECT COUNT(*) as count FROM products WHERE status = 'active'",
    'total_orders' => "SELECT COUNT(*) as count FROM orders",
    'pending_orders' => "SELECT COUNT(*) as count FROM orders WHERE status IN ('pending', 'confirmed')",
    'total_revenue' => "SELECT COALESCE(SUM(total_amount), 0) as total FROM orders WHERE payment_status = 'paid'",
    'monthly_revenue' => "SELECT COALESCE(SUM(total_amount), 0) as total FROM orders WHERE payment_status = 'paid' AND MONTH(created_at) = MONTH(CURRENT_DATE()) AND YEAR(created_at) = YEAR(CURRENT_DATE())",
    'pending_reviews' => "SELECT COUNT(*) as count FROM product_reviews WHERE status = 'pending'"
];

$stats = [];
foreach ($stats_queries as $key => $query) {
    $result = $database->fetch($query);
    $stats[$key] = $result['count'] ?? $result['total'] ?? 0;
}

// Get recent activities
$recent_activities_sql = "
    SELECT al.*, u.first_name, u.last_name, u.user_type
    FROM activity_logs al
    JOIN users u ON al.user_id = u.id
    ORDER BY al.created_at DESC
    LIMIT 10
";

$recent_activities = $database->fetchAll($recent_activities_sql);

// Get recent orders
$recent_orders_sql = "
    SELECT o.*, u.first_name, u.last_name, COUNT(oi.id) as item_count
    FROM orders o
    JOIN users u ON o.customer_id = u.id
    LEFT JOIN order_items oi ON o.id = oi.order_id
    GROUP BY o.id
    ORDER BY o.created_at DESC
    LIMIT 10
";

$recent_orders = $database->fetchAll($recent_orders_sql);

// Get pending vendor applications
$pending_vendors_sql = "
    SELECT u.*, vs.store_name, vs.business_license, vs.tax_id
    FROM users u
    LEFT JOIN vendor_stores vs ON u.id = vs.vendor_id
    WHERE u.user_type = 'vendor' AND u.status = 'pending'
    ORDER BY u.created_at DESC
    LIMIT 5
";

$pending_vendors = $database->fetchAll($pending_vendors_sql);

// Get sales data for chart (last 30 days)
$sales_chart_sql = "
    SELECT DATE(created_at) as date, COUNT(*) as orders, SUM(total_amount) as revenue
    FROM orders 
    WHERE payment_status = 'paid' 
    AND created_at >= DATE_SUB(CURRENT_DATE(), INTERVAL 30 DAY)
    GROUP BY DATE(created_at)
    ORDER BY date ASC
";

$sales_data = $database->fetchAll($sales_chart_sql);

// Get top performing vendors
$top_vendors_sql = "
    SELECT u.id, u.first_name, u.last_name, vs.store_name,
           COUNT(DISTINCT o.id) as total_orders,
           SUM(oi.total_price) as total_revenue,
           AVG(pr.rating) as avg_rating
    FROM users u
    JOIN vendor_stores vs ON u.id = vs.vendor_id
    LEFT JOIN order_items oi ON u.id = oi.vendor_id
    LEFT JOIN orders o ON oi.order_id = o.id AND o.payment_status = 'paid'
    LEFT JOIN product_reviews pr ON oi.product_id = pr.product_id AND pr.status = 'approved'
    WHERE u.user_type = 'vendor' AND u.status = 'active'
    GROUP BY u.id
    ORDER BY total_revenue DESC
    LIMIT 10
";

$top_vendors = $database->fetchAll($top_vendors_sql);

require_once '../includes/header.php';
?>

<div class="container" style="margin: 2rem auto;">
    <!-- Header -->
    <div class="row align-items-center mb-3">
        <div class="col-8">
            <h1>Admin Dashboard</h1>
            <p class="text-muted">Platform overview and management</p>
        </div>
        <div class="col-4 text-right">
            <div style="display: flex; gap: 1rem; justify-content: flex-end;">
                <a href="reports.php" class="btn btn-outline">
                    <i class="fas fa-chart-bar"></i> Reports
                </a>
                <a href="settings.php" class="btn btn-primary">
                    <i class="fas fa-cog"></i> Settings
                </a>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-3">
            <div class="stat-card">
                <div class="stat-icon" style="background: var(--primary-green);">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo number_format($stats['total_users']); ?></h3>
                    <p>Total Users</p>
                    <small><?php echo $stats['total_customers']; ?> customers, <?php echo $stats['total_vendors']; ?> vendors</small>
                </div>
            </div>
        </div>
        
        <div class="col-3">
            <div class="stat-card">
                <div class="stat-icon" style="background: var(--secondary-green);">
                    <i class="fas fa-shopping-cart"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo number_format($stats['total_orders']); ?></h3>
                    <p>Total Orders</p>
                    <small><?php echo $stats['pending_orders']; ?> pending</small>
                </div>
            </div>
        </div>
        
        <div class="col-3">
            <div class="stat-card">
                <div class="stat-icon" style="background: #2196F3;">
                    <i class="fas fa-money-bill-wave"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo formatCurrency($stats['total_revenue']); ?></h3>
                    <p>Total Revenue</p>
                    <small>This month: <?php echo formatCurrency($stats['monthly_revenue']); ?></small>
                </div>
            </div>
        </div>
        
        <div class="col-3">
            <div class="stat-card">
                <div class="stat-icon" style="background: #FF9800;">
                    <i class="fas fa-box"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo number_format($stats['total_products']); ?></h3>
                    <p>Active Products</p>
                    <small><?php echo $stats['pending_reviews']; ?> pending reviews</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5>Quick Actions</h5>
                </div>
                <div class="card-body">
                    <div class="quick-actions">
                        <a href="users.php" class="quick-action">
                            <i class="fas fa-users"></i>
                            <span>Manage Users</span>
                        </a>
                        <a href="vendors.php" class="quick-action">
                            <i class="fas fa-store"></i>
                            <span>Vendor Applications</span>
                            <?php if ($stats['pending_vendors'] > 0): ?>
                                <span class="badge"><?php echo $stats['pending_vendors']; ?></span>
                            <?php endif; ?>
                        </a>
                        <a href="products.php" class="quick-action">
                            <i class="fas fa-box"></i>
                            <span>Products</span>
                        </a>
                        <a href="orders.php" class="quick-action">
                            <i class="fas fa-shopping-cart"></i>
                            <span>Orders</span>
                            <?php if ($stats['pending_orders'] > 0): ?>
                                <span class="badge"><?php echo $stats['pending_orders']; ?></span>
                            <?php endif; ?>
                        </a>
                        <a href="reviews.php" class="quick-action">
                            <i class="fas fa-star"></i>
                            <span>Reviews</span>
                            <?php if ($stats['pending_reviews'] > 0): ?>
                                <span class="badge"><?php echo $stats['pending_reviews']; ?></span>
                            <?php endif; ?>
                        </a>
                        <a href="categories.php" class="quick-action">
                            <i class="fas fa-tags"></i>
                            <span>Categories</span>
                        </a>
                        <a href="reports.php" class="quick-action">
                            <i class="fas fa-chart-bar"></i>
                            <span>Reports</span>
                        </a>
                        <a href="settings.php" class="quick-action">
                            <i class="fas fa-cog"></i>
                            <span>Settings</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Sales Chart -->
        <div class="col-8">
            <div class="card">
                <div class="card-header">
                    <h5>Sales Overview (Last 30 Days)</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($sales_data)): ?>
                        <div class="text-center" style="padding: 2rem;">
                            <i class="fas fa-chart-line" style="font-size: 3rem; color: var(--medium-gray); margin-bottom: 1rem;"></i>
                            <h6>No sales data available</h6>
                            <p class="text-muted">Sales data will appear here once orders are placed.</p>
                        </div>
                    <?php else: ?>
                        <div style="height: 300px; overflow-x: auto;">
                            <table style="width: 100%; border-collapse: collapse;">
                                <thead>
                                    <tr style="border-bottom: 2px solid var(--light-gray);">
                                        <th style="padding: 0.5rem; text-align: left;">Date</th>
                                        <th style="padding: 0.5rem; text-align: right;">Orders</th>
                                        <th style="padding: 0.5rem; text-align: right;">Revenue</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($sales_data as $data): ?>
                                        <tr style="border-bottom: 1px solid var(--light-gray);">
                                            <td style="padding: 0.5rem;"><?php echo date('M j, Y', strtotime($data['date'])); ?></td>
                                            <td style="padding: 0.5rem; text-align: right;"><?php echo $data['orders']; ?></td>
                                            <td style="padding: 0.5rem; text-align: right; font-weight: 600;"><?php echo formatCurrency($data['revenue']); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Pending Vendor Applications -->
        <div class="col-4">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6>Pending Vendor Applications</h6>
                    <a href="vendors.php" class="btn btn-outline btn-sm">View All</a>
                </div>
                <div class="card-body">
                    <?php if (empty($pending_vendors)): ?>
                        <div class="text-center" style="padding: 1rem;">
                            <i class="fas fa-check-circle" style="font-size: 2rem; color: var(--secondary-green); margin-bottom: 0.5rem;"></i>
                            <p class="text-muted" style="margin-bottom: 0;">No pending applications</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($pending_vendors as $vendor): ?>
                            <div style="padding: 0.75rem 0; border-bottom: 1px solid var(--light-gray);">
                                <div style="display: flex; justify-content-between; align-items: center;">
                                    <div>
                                        <strong style="font-size: 0.9rem;"><?php echo htmlspecialchars($vendor['store_name'] ?: $vendor['first_name'] . ' ' . $vendor['last_name']); ?></strong>
                                        <br>
                                        <small class="text-muted"><?php echo htmlspecialchars($vendor['email']); ?></small>
                                        <br>
                                        <small class="text-muted">Applied <?php echo timeAgo($vendor['created_at']); ?></small>
                                    </div>
                                    <div style="display: flex; gap: 0.25rem;">
                                        <button onclick="approveVendor(<?php echo $vendor['id']; ?>)" class="btn btn-sm" style="background: var(--secondary-green); color: white;" title="Approve">
                                            <i class="fas fa-check"></i>
                                        </button>
                                        <button onclick="rejectVendor(<?php echo $vendor['id']; ?>)" class="btn btn-sm" style="background: #F44336; color: white;" title="Reject">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Orders and Activities -->
    <div class="row mt-3">
        <div class="col-6">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6>Recent Orders</h6>
                    <a href="orders.php" class="btn btn-outline btn-sm">View All</a>
                </div>
                <div class="card-body">
                    <?php if (empty($recent_orders)): ?>
                        <div class="text-center" style="padding: 1rem;">
                            <i class="fas fa-shopping-cart" style="font-size: 2rem; color: var(--medium-gray); margin-bottom: 0.5rem;"></i>
                            <p class="text-muted" style="margin-bottom: 0;">No recent orders</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($recent_orders as $order): ?>
                            <div style="display: flex; justify-content-between; align-items: center; padding: 0.5rem 0; border-bottom: 1px solid var(--light-gray);">
                                <div>
                                    <strong style="font-size: 0.9rem;"><?php echo htmlspecialchars($order['order_number']); ?></strong>
                                    <br>
                                    <small class="text-muted"><?php echo htmlspecialchars($order['first_name'] . ' ' . $order['last_name']); ?></small>
                                    <br>
                                    <small class="text-muted"><?php echo $order['item_count']; ?> items</small>
                                </div>
                                <div style="text-align: right;">
                                    <strong style="color: var(--primary-green);"><?php echo formatCurrency($order['total_amount']); ?></strong>
                                    <br>
                                    <span class="badge" style="background: <?php 
                                        echo $order['status'] === 'pending' ? '#FFA726' : 
                                            ($order['status'] === 'confirmed' ? '#66BB6A' : 
                                            ($order['status'] === 'shipped' ? '#42A5F5' : 
                                            ($order['status'] === 'delivered' ? '#4CAF50' : '#9E9E9E'))); 
                                    ?>; color: white; font-size: 0.7rem;">
                                        <?php echo ucfirst($order['status']); ?>
                                    </span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-6">
            <div class="card">
                <div class="card-header">
                    <h6>Recent Activities</h6>
                </div>
                <div class="card-body">
                    <?php if (empty($recent_activities)): ?>
                        <div class="text-center" style="padding: 1rem;">
                            <i class="fas fa-history" style="font-size: 2rem; color: var(--medium-gray); margin-bottom: 0.5rem;"></i>
                            <p class="text-muted" style="margin-bottom: 0;">No recent activities</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($recent_activities as $activity): ?>
                            <div style="display: flex; align-items: center; gap: 0.75rem; padding: 0.5rem 0; border-bottom: 1px solid var(--light-gray);">
                                <div style="width: 32px; height: 32px; background: var(--light-gray); border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                    <i class="fas fa-user" style="font-size: 0.8rem; color: var(--dark-gray);"></i>
                                </div>
                                <div style="flex: 1; min-width: 0;">
                                    <div style="font-size: 0.9rem;">
                                        <strong><?php echo htmlspecialchars($activity['first_name'] . ' ' . $activity['last_name']); ?></strong>
                                        <span class="text-muted"><?php echo htmlspecialchars($activity['action']); ?></span>
                                    </div>
                                    <small class="text-muted"><?php echo timeAgo($activity['created_at']); ?></small>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Top Vendors -->
    <div class="card mt-3">
        <div class="card-header">
            <h5>Top Performing Vendors</h5>
        </div>
        <div class="card-body">
            <?php if (empty($top_vendors)): ?>
                <div class="text-center" style="padding: 2rem;">
                    <i class="fas fa-store" style="font-size: 3rem; color: var(--medium-gray); margin-bottom: 1rem;"></i>
                    <h6>No vendor data available</h6>
                    <p class="text-muted">Vendor performance data will appear here once sales are made.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table style="width: 100%; border-collapse: collapse;">
                        <thead>
                            <tr style="border-bottom: 2px solid var(--light-gray);">
                                <th style="padding: 0.75rem; text-align: left;">Vendor</th>
                                <th style="padding: 0.75rem; text-align: right;">Orders</th>
                                <th style="padding: 0.75rem; text-align: right;">Revenue</th>
                                <th style="padding: 0.75rem; text-align: right;">Rating</th>
                                <th style="padding: 0.75rem; text-align: center;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($top_vendors as $vendor): ?>
                                <tr style="border-bottom: 1px solid var(--light-gray);">
                                    <td style="padding: 0.75rem;">
                                        <strong><?php echo htmlspecialchars($vendor['store_name'] ?: $vendor['first_name'] . ' ' . $vendor['last_name']); ?></strong>
                                    </td>
                                    <td style="padding: 0.75rem; text-align: right;"><?php echo $vendor['total_orders'] ?: 0; ?></td>
                                    <td style="padding: 0.75rem; text-align: right; font-weight: 600;"><?php echo formatCurrency($vendor['total_revenue'] ?: 0); ?></td>
                                    <td style="padding: 0.75rem; text-align: right;">
                                        <?php if ($vendor['avg_rating']): ?>
                                            <span style="color: #FFB74D;">★ <?php echo number_format($vendor['avg_rating'], 1); ?></span>
                                        <?php else: ?>
                                            <span class="text-muted">No ratings</span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="padding: 0.75rem; text-align: center;">
                                        <a href="vendor-details.php?id=<?php echo $vendor['id']; ?>" class="btn btn-outline btn-sm">View</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.stat-card {
    background: white;
    border-radius: var(--border-radius);
    padding: 1.5rem;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    display: flex;
    align-items: center;
    gap: 1rem;
    transition: var(--transition);
}

.stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 20px rgba(0,0,0,0.15);
}

.stat-icon {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.5rem;
}

.stat-content h3 {
    margin-bottom: 0.25rem;
    color: var(--black);
    font-size: 1.8rem;
    font-weight: bold;
}

.stat-content p {
    margin-bottom: 0.25rem;
    color: var(--dark-gray);
    font-weight: 600;
}

.stat-content small {
    color: var(--medium-gray);
    font-size: 0.8rem;
}

.quick-actions {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 1rem;
}

.quick-action {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.5rem;
    padding: 1rem;
    border: 1px solid var(--light-gray);
    border-radius: var(--border-radius);
    text-decoration: none;
    color: var(--dark-gray);
    transition: var(--transition);
    position: relative;
}

.quick-action:hover {
    border-color: var(--primary-green);
    color: var(--primary-green);
    transform: translateY(-2px);
}

.quick-action i {
    font-size: 1.5rem;
}

.quick-action .badge {
    position: absolute;
    top: 0.5rem;
    right: 0.5rem;
    background: #F44336;
    color: white;
    border-radius: 50%;
    padding: 0.25rem 0.5rem;
    font-size: 0.7rem;
    min-width: 20px;
    text-align: center;
}

@media (max-width: 768px) {
    .col-3, .col-4, .col-6, .col-8 {
        flex: 0 0 100%;
        margin-bottom: 1rem;
    }
    
    .quick-actions {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .stat-card {
        flex-direction: column;
        text-align: center;
    }
}
</style>

<script>
function approveVendor(vendorId) {
    if (confirm('Are you sure you want to approve this vendor application?')) {
        fetch('../api/admin.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'approve_vendor',
                vendor_id: vendorId
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification('Vendor approved successfully', 'success');
                location.reload();
            } else {
                showNotification(data.message || 'Error approving vendor', 'error');
            }
        });
    }
}

function rejectVendor(vendorId) {
    if (confirm('Are you sure you want to reject this vendor application?')) {
        fetch('../api/admin.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'reject_vendor',
                vendor_id: vendorId
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification('Vendor application rejected', 'success');
                location.reload();
            } else {
                showNotification(data.message || 'Error rejecting vendor', 'error');
            }
        });
    }
}

function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `alert alert-${type}`;
    notification.style.cssText = 'position: fixed; top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
    notification.textContent = message;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.remove();
    }, 3000);
}

// Auto-refresh dashboard every 5 minutes
setInterval(function() {
    location.reload();
}, 300000);
</script>

<?php require_once '../includes/footer.php'; ?>
