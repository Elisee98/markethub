<?php
/**
 * MarketHub Analytics Dashboard
 * Admin panel for platform analytics and insights
 */

require_once '../config/config.php';

$page_title = 'Analytics Dashboard';

// Check if user is admin
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    redirect('../login.php?error=access_denied');
}

// Get date range (default to last 30 days)
$end_date = date('Y-m-d');
$start_date = date('Y-m-d', strtotime('-30 days'));

// Platform Overview
$platform_stats = [
    'total_users' => $database->fetch("SELECT COUNT(*) as count FROM users")['count'],
    'active_users' => $database->fetch("SELECT COUNT(*) as count FROM users WHERE status = 'active'")['count'],
    'total_vendors' => $database->fetch("SELECT COUNT(*) as count FROM users WHERE user_type = 'vendor'")['count'],
    'active_vendors' => $database->fetch("SELECT COUNT(*) as count FROM users WHERE user_type = 'vendor' AND status = 'active'")['count'],
    'total_products' => $database->fetch("SELECT COUNT(*) as count FROM products")['count'],
    'active_products' => $database->fetch("SELECT COUNT(*) as count FROM products WHERE status = 'active'")['count'],
    'total_orders' => $database->fetch("SELECT COUNT(*) as count FROM orders")['count'],
    'paid_orders' => $database->fetch("SELECT COUNT(*) as count FROM orders WHERE payment_status = 'paid'")['count']
];

// Revenue Analytics
$revenue_stats = $database->fetch("
    SELECT 
        COUNT(*) as total_orders,
        SUM(total_amount) as total_revenue,
        AVG(total_amount) as avg_order_value,
        SUM(COALESCE(shipping_cost, 0)) as shipping_revenue,
        SUM(COALESCE(tax_amount, 0)) as tax_collected
    FROM orders 
    WHERE payment_status = 'paid'
    AND DATE(created_at) BETWEEN ? AND ?
", [$start_date, $end_date]);

// Growth Analytics (last 7 days vs previous 7 days)
$current_week_start = date('Y-m-d', strtotime('-7 days'));
$previous_week_start = date('Y-m-d', strtotime('-14 days'));
$previous_week_end = date('Y-m-d', strtotime('-8 days'));

$current_week_stats = $database->fetch("
    SELECT 
        COUNT(DISTINCT CASE WHEN user_type = 'customer' THEN id END) as new_customers,
        COUNT(DISTINCT CASE WHEN user_type = 'vendor' THEN id END) as new_vendors,
        COUNT(*) as new_users
    FROM users 
    WHERE DATE(created_at) BETWEEN ? AND ?
", [$current_week_start, $end_date]);

$previous_week_stats = $database->fetch("
    SELECT 
        COUNT(DISTINCT CASE WHEN user_type = 'customer' THEN id END) as new_customers,
        COUNT(DISTINCT CASE WHEN user_type = 'vendor' THEN id END) as new_vendors,
        COUNT(*) as new_users
    FROM users 
    WHERE DATE(created_at) BETWEEN ? AND ?
", [$previous_week_start, $previous_week_end]);

// Calculate growth percentages
function calculateGrowth($current, $previous) {
    if ($previous == 0) return $current > 0 ? 100 : 0;
    return round((($current - $previous) / $previous) * 100, 1);
}

$growth_stats = [
    'users' => calculateGrowth($current_week_stats['new_users'], $previous_week_stats['new_users']),
    'customers' => calculateGrowth($current_week_stats['new_customers'], $previous_week_stats['new_customers']),
    'vendors' => calculateGrowth($current_week_stats['new_vendors'], $previous_week_stats['new_vendors'])
];

// Top Categories by Product Count
$top_categories = $database->fetchAll("
    SELECT c.name, c.icon, COUNT(p.id) as product_count,
           COUNT(DISTINCT p.vendor_id) as vendor_count
    FROM categories c
    LEFT JOIN products p ON c.id = p.category_id AND p.status = 'active'
    GROUP BY c.id
    ORDER BY product_count DESC
    LIMIT 8
");

// Recent Activity Summary
$activity_summary = $database->fetchAll("
    SELECT action, COUNT(*) as count
    FROM activity_logs
    WHERE DATE(created_at) BETWEEN ? AND ?
    GROUP BY action
    ORDER BY count DESC
    LIMIT 10
", [$start_date, $end_date]);

require_once 'includes/admin_header.php';
?>

<div class="content-header">
    <h1><i class="fas fa-chart-line"></i> Analytics Dashboard</h1>
    <p>Platform insights and performance metrics</p>
</div>

<!-- Platform Overview -->
<div class="analytics-section">
    <h3><i class="fas fa-globe"></i> Platform Overview</h3>
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon" style="background: #2196F3;">
                <i class="fas fa-users"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo number_format($platform_stats['active_users']); ?></h3>
                <p>Active Users</p>
                <small><?php echo number_format($platform_stats['total_users']); ?> total</small>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon" style="background: #4CAF50;">
                <i class="fas fa-store"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo number_format($platform_stats['active_vendors']); ?></h3>
                <p>Active Vendors</p>
                <small><?php echo number_format($platform_stats['total_vendors']); ?> total</small>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon" style="background: #FF9800;">
                <i class="fas fa-box"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo number_format($platform_stats['active_products']); ?></h3>
                <p>Active Products</p>
                <small><?php echo number_format($platform_stats['total_products']); ?> total</small>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon" style="background: #9C27B0;">
                <i class="fas fa-shopping-cart"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo number_format($platform_stats['paid_orders']); ?></h3>
                <p>Paid Orders</p>
                <small><?php echo number_format($platform_stats['total_orders']); ?> total</small>
            </div>
        </div>
    </div>
</div>

<!-- Revenue Analytics -->
<div class="analytics-section">
    <h3><i class="fas fa-dollar-sign"></i> Revenue Analytics (Last 30 Days)</h3>
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon" style="background: #4CAF50;">
                <i class="fas fa-money-bill-wave"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo formatCurrency($revenue_stats['total_revenue'] ?: 0); ?></h3>
                <p>Total Revenue</p>
                <small><?php echo number_format($revenue_stats['total_orders'] ?: 0); ?> orders</small>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon" style="background: #2196F3;">
                <i class="fas fa-chart-line"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo formatCurrency($revenue_stats['avg_order_value'] ?: 0); ?></h3>
                <p>Avg Order Value</p>
                <small>Per transaction</small>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon" style="background: #FF9800;">
                <i class="fas fa-truck"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo formatCurrency($revenue_stats['shipping_revenue'] ?: 0); ?></h3>
                <p>Shipping Revenue</p>
                <small>Additional income</small>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon" style="background: #9C27B0;">
                <i class="fas fa-receipt"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo formatCurrency($revenue_stats['tax_collected'] ?: 0); ?></h3>
                <p>Tax Collected</p>
                <small>Government taxes</small>
            </div>
        </div>
    </div>
</div>

<!-- Growth Analytics -->
<div class="analytics-section">
    <h3><i class="fas fa-trending-up"></i> Growth Analytics (Last 7 Days vs Previous 7 Days)</h3>
    <div class="growth-grid">
        <div class="growth-card">
            <div class="growth-header">
                <h4>New Users</h4>
                <div class="growth-indicator <?php echo $growth_stats['users'] >= 0 ? 'positive' : 'negative'; ?>">
                    <i class="fas fa-arrow-<?php echo $growth_stats['users'] >= 0 ? 'up' : 'down'; ?>"></i>
                    <?php echo abs($growth_stats['users']); ?>%
                </div>
            </div>
            <div class="growth-numbers">
                <span class="current"><?php echo $current_week_stats['new_users']; ?></span>
                <span class="vs">vs</span>
                <span class="previous"><?php echo $previous_week_stats['new_users']; ?></span>
            </div>
        </div>
        
        <div class="growth-card">
            <div class="growth-header">
                <h4>New Customers</h4>
                <div class="growth-indicator <?php echo $growth_stats['customers'] >= 0 ? 'positive' : 'negative'; ?>">
                    <i class="fas fa-arrow-<?php echo $growth_stats['customers'] >= 0 ? 'up' : 'down'; ?>"></i>
                    <?php echo abs($growth_stats['customers']); ?>%
                </div>
            </div>
            <div class="growth-numbers">
                <span class="current"><?php echo $current_week_stats['new_customers']; ?></span>
                <span class="vs">vs</span>
                <span class="previous"><?php echo $previous_week_stats['new_customers']; ?></span>
            </div>
        </div>
        
        <div class="growth-card">
            <div class="growth-header">
                <h4>New Vendors</h4>
                <div class="growth-indicator <?php echo $growth_stats['vendors'] >= 0 ? 'positive' : 'negative'; ?>">
                    <i class="fas fa-arrow-<?php echo $growth_stats['vendors'] >= 0 ? 'up' : 'down'; ?>"></i>
                    <?php echo abs($growth_stats['vendors']); ?>%
                </div>
            </div>
            <div class="growth-numbers">
                <span class="current"><?php echo $current_week_stats['new_vendors']; ?></span>
                <span class="vs">vs</span>
                <span class="previous"><?php echo $previous_week_stats['new_vendors']; ?></span>
            </div>
        </div>
    </div>
</div>

<!-- Category Performance & Activity Summary -->
<div class="dashboard-grid">
    <!-- Top Categories -->
    <div class="dashboard-widget">
        <div class="widget-header">
            <h4><i class="fas fa-tags"></i> Top Categories</h4>
        </div>
        <div class="widget-content">
            <div class="categories-list">
                <?php foreach ($top_categories as $category): ?>
                    <div class="category-item">
                        <div class="category-icon">
                            <?php if ($category['icon']): ?>
                                <i class="<?php echo htmlspecialchars($category['icon']); ?>"></i>
                            <?php else: ?>
                                <i class="fas fa-tag"></i>
                            <?php endif; ?>
                        </div>
                        <div class="category-info">
                            <h5><?php echo htmlspecialchars($category['name']); ?></h5>
                            <p><?php echo $category['product_count']; ?> products • <?php echo $category['vendor_count']; ?> vendors</p>
                        </div>
                        <div class="category-progress">
                            <div class="progress-bar" style="width: <?php echo min(100, ($category['product_count'] / max(1, $platform_stats['active_products'])) * 100); ?>%"></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Activity Summary -->
    <div class="dashboard-widget">
        <div class="widget-header">
            <h4><i class="fas fa-activity"></i> Recent Activity (Last 30 Days)</h4>
        </div>
        <div class="widget-content">
            <?php if (empty($activity_summary)): ?>
                <p class="no-data">No activity recorded</p>
            <?php else: ?>
                <div class="activity-list">
                    <?php foreach ($activity_summary as $activity): ?>
                        <div class="activity-item">
                            <div class="activity-name"><?php echo htmlspecialchars(str_replace('_', ' ', ucwords($activity['action'], '_'))); ?></div>
                            <div class="activity-count"><?php echo number_format($activity['count']); ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.analytics-section {
    margin-bottom: 3rem;
}

.analytics-section h3 {
    margin-bottom: 1.5rem;
    color: var(--admin-dark);
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.growth-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.5rem;
}

.growth-card {
    background: white;
    border-radius: 12px;
    padding: 1.5rem;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.growth-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
}

.growth-header h4 {
    margin: 0;
    color: var(--admin-dark);
}

.growth-indicator {
    display: flex;
    align-items: center;
    gap: 0.25rem;
    font-weight: bold;
    font-size: 0.9rem;
}

.growth-indicator.positive {
    color: var(--admin-success);
}

.growth-indicator.negative {
    color: var(--admin-error);
}

.growth-numbers {
    display: flex;
    align-items: center;
    gap: 1rem;
    font-size: 1.5rem;
}

.growth-numbers .current {
    font-weight: bold;
    color: var(--admin-primary);
}

.growth-numbers .vs {
    font-size: 0.9rem;
    color: #666;
}

.growth-numbers .previous {
    color: #999;
}

.categories-list {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.category-item {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem;
    background: #f8f9fa;
    border-radius: 8px;
}

.category-icon {
    width: 40px;
    height: 40px;
    background: var(--admin-primary);
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
}

.category-info {
    flex: 1;
}

.category-info h5 {
    margin: 0 0 0.25rem 0;
    color: var(--admin-dark);
}

.category-info p {
    margin: 0;
    color: #666;
    font-size: 0.85rem;
}

.category-progress {
    width: 60px;
    height: 4px;
    background: #e0e0e0;
    border-radius: 2px;
    overflow: hidden;
}

.progress-bar {
    height: 100%;
    background: var(--admin-primary);
    transition: width 0.3s;
}

.activity-list {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.activity-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.75rem;
    background: #f8f9fa;
    border-radius: 6px;
}

.activity-name {
    color: var(--admin-dark);
    font-weight: 500;
}

.activity-count {
    background: var(--admin-primary);
    color: white;
    padding: 0.25rem 0.75rem;
    border-radius: 15px;
    font-size: 0.85rem;
    font-weight: bold;
}

.no-data {
    text-align: center;
    color: #999;
    font-style: italic;
    padding: 2rem;
}

@media (max-width: 768px) {
    .growth-grid {
        grid-template-columns: 1fr;
    }
    
    .category-item {
        flex-direction: column;
        text-align: center;
    }
    
    .category-progress {
        width: 100%;
    }
}
</style>

<?php require_once 'includes/admin_footer.php'; ?>
