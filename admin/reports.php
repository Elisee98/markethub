<?php
/**
 * MarketHub Admin Reports & Analytics
 * Multi-Vendor E-Commerce Platform
 */

require_once '../config/config.php';

$page_title = 'Reports & Analytics';

// Require admin login
requireRole('admin');

// Get date range from query parameters
$start_date = sanitizeInput($_GET['start_date'] ?? date('Y-m-01')); // First day of current month
$end_date = sanitizeInput($_GET['end_date'] ?? date('Y-m-d')); // Today
$report_type = sanitizeInput($_GET['report_type'] ?? 'overview');

// Validate dates
if (!strtotime($start_date) || !strtotime($end_date)) {
    $start_date = date('Y-m-01');
    $end_date = date('Y-m-d');
}

// Get comprehensive analytics data
$analytics = [];

// Sales Analytics
$sales_sql = "
    SELECT
        COUNT(DISTINCT o.id) as total_orders,
        COUNT(DISTINCT o.customer_id) as unique_customers,
        SUM(o.total_amount) as total_revenue,
        AVG(o.total_amount) as avg_order_value,
        SUM(COALESCE(o.subtotal, o.total_amount)) as subtotal,
        SUM(COALESCE(o.shipping_cost, 0)) as shipping_revenue,
        SUM(COALESCE(o.tax_amount, 0)) as tax_collected
    FROM orders o
    WHERE o.payment_status = 'paid'
    AND DATE(o.created_at) BETWEEN ? AND ?
";

$analytics['sales'] = $database->fetch($sales_sql, [$start_date, $end_date]);

// Product Analytics
$product_sql = "
    SELECT 
        COUNT(DISTINCT p.id) as total_products,
        COUNT(DISTINCT CASE WHEN p.status = 'active' THEN p.id END) as active_products,
        COUNT(DISTINCT CASE WHEN p.status = 'pending' THEN p.id END) as pending_products,
        COUNT(DISTINCT p.vendor_id) as active_vendors,
        AVG(p.price) as avg_product_price
    FROM products p
    WHERE p.created_at BETWEEN ? AND ?
";

$analytics['products'] = $database->fetch($product_sql, [$start_date, $end_date]);

// User Analytics
$user_sql = "
    SELECT 
        COUNT(CASE WHEN user_type = 'customer' THEN 1 END) as new_customers,
        COUNT(CASE WHEN user_type = 'vendor' THEN 1 END) as new_vendors,
        COUNT(*) as total_new_users
    FROM users
    WHERE created_at BETWEEN ? AND ?
";

$analytics['users'] = $database->fetch($user_sql, [$start_date, $end_date]);

// Top Performing Vendors
$top_vendors_sql = "
    SELECT 
        u.id, u.first_name, u.last_name, vs.store_name,
        COUNT(DISTINCT oi.order_id) as total_orders,
        SUM(oi.total_price) as total_revenue,
        COUNT(DISTINCT oi.product_id) as products_sold,
        AVG(pr.rating) as avg_rating
    FROM users u
    JOIN vendor_stores vs ON u.id = vs.vendor_id
    LEFT JOIN order_items oi ON u.id = oi.vendor_id
    LEFT JOIN orders o ON oi.order_id = o.id AND o.payment_status = 'paid' AND DATE(o.created_at) BETWEEN ? AND ?
    LEFT JOIN product_reviews pr ON oi.product_id = pr.product_id AND pr.status = 'approved'
    WHERE u.user_type = 'vendor' AND u.status = 'active'
    GROUP BY u.id
    ORDER BY total_revenue DESC
    LIMIT 10
";

$top_vendors = $database->fetchAll($top_vendors_sql, [$start_date, $end_date]);

// Top Selling Products
$top_products_sql = "
    SELECT 
        p.id, p.name, p.price, p.sku,
        u.first_name as vendor_first_name, u.last_name as vendor_last_name, vs.store_name,
        COUNT(oi.id) as units_sold,
        SUM(oi.total_price) as total_revenue,
        AVG(pr.rating) as avg_rating
    FROM products p
    JOIN users u ON p.vendor_id = u.id
    LEFT JOIN vendor_stores vs ON u.id = vs.vendor_id
    LEFT JOIN order_items oi ON p.id = oi.product_id
    LEFT JOIN orders o ON oi.order_id = o.id AND o.payment_status = 'paid' AND DATE(o.created_at) BETWEEN ? AND ?
    LEFT JOIN product_reviews pr ON p.id = pr.product_id AND pr.status = 'approved'
    WHERE p.status = 'active'
    GROUP BY p.id
    HAVING units_sold > 0
    ORDER BY units_sold DESC, total_revenue DESC
    LIMIT 10
";

$top_products = $database->fetchAll($top_products_sql, [$start_date, $end_date]);

// Daily Sales Data for Chart
$daily_sales_sql = "
    SELECT 
        DATE(o.created_at) as date,
        COUNT(DISTINCT o.id) as orders,
        SUM(o.total_amount) as revenue,
        COUNT(DISTINCT o.customer_id) as customers
    FROM orders o
    WHERE o.payment_status = 'paid' 
    AND DATE(o.created_at) BETWEEN ? AND ?
    GROUP BY DATE(o.created_at)
    ORDER BY date ASC
";

$daily_sales = $database->fetchAll($daily_sales_sql, [$start_date, $end_date]);

// Category Performance
$category_sql = "
    SELECT 
        c.id, c.name,
        COUNT(DISTINCT p.id) as total_products,
        COUNT(DISTINCT oi.id) as units_sold,
        SUM(oi.total_price) as total_revenue
    FROM categories c
    LEFT JOIN products p ON c.id = p.category_id AND p.status = 'active'
    LEFT JOIN order_items oi ON p.id = oi.product_id
    LEFT JOIN orders o ON oi.order_id = o.id AND o.payment_status = 'paid' AND DATE(o.created_at) BETWEEN ? AND ?
    GROUP BY c.id
    ORDER BY total_revenue DESC
";

$category_performance = $database->fetchAll($category_sql, [$start_date, $end_date]);

// Customer Analytics
$customer_analytics_sql = "
    SELECT 
        COUNT(DISTINCT o.customer_id) as total_customers,
        COUNT(DISTINCT CASE WHEN order_count = 1 THEN o.customer_id END) as new_customers,
        COUNT(DISTINCT CASE WHEN order_count > 1 THEN o.customer_id END) as returning_customers,
        AVG(customer_total) as avg_customer_value
    FROM (
        SELECT 
            customer_id,
            COUNT(*) as order_count,
            SUM(total_amount) as customer_total
        FROM orders 
        WHERE payment_status = 'paid' 
        AND DATE(created_at) BETWEEN ? AND ?
        GROUP BY customer_id
    ) o
";

$customer_analytics = $database->fetch($customer_analytics_sql, [$start_date, $end_date]);

require_once '../includes/header.php';
?>

<div class="container" style="margin: 2rem auto;">
    <!-- Header -->
    <div class="row align-items-center mb-3">
        <div class="col-6">
            <h1>Reports & Analytics</h1>
            <p class="text-muted">Platform performance insights and data analysis</p>
        </div>
        <div class="col-6 text-right">
            <a href="dashboard.php" class="btn btn-outline">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>
    </div>

    <!-- Date Range Filter -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="" style="display: flex; align-items: center; gap: 1rem;">
                <div>
                    <label class="form-label">Start Date</label>
                    <input type="date" name="start_date" class="form-control" value="<?php echo $start_date; ?>">
                </div>
                <div>
                    <label class="form-label">End Date</label>
                    <input type="date" name="end_date" class="form-control" value="<?php echo $end_date; ?>">
                </div>
                <div>
                    <label class="form-label">Report Type</label>
                    <select name="report_type" class="form-control form-select">
                        <option value="overview" <?php echo $report_type === 'overview' ? 'selected' : ''; ?>>Overview</option>
                        <option value="sales" <?php echo $report_type === 'sales' ? 'selected' : ''; ?>>Sales</option>
                        <option value="vendors" <?php echo $report_type === 'vendors' ? 'selected' : ''; ?>>Vendors</option>
                        <option value="products" <?php echo $report_type === 'products' ? 'selected' : ''; ?>>Products</option>
                        <option value="customers" <?php echo $report_type === 'customers' ? 'selected' : ''; ?>>Customers</option>
                    </select>
                </div>
                <div style="margin-top: 1.5rem;">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i> Generate Report
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Key Metrics -->
    <div class="row mb-4">
        <div class="col-3">
            <div class="metric-card">
                <div class="metric-icon" style="background: var(--primary-green);">
                    <i class="fas fa-money-bill-wave"></i>
                </div>
                <div class="metric-content">
                    <h3><?php echo formatCurrency($analytics['sales']['total_revenue'] ?: 0); ?></h3>
                    <p>Total Revenue</p>
                    <small><?php echo number_format($analytics['sales']['total_orders'] ?: 0); ?> orders</small>
                </div>
            </div>
        </div>
        
        <div class="col-3">
            <div class="metric-card">
                <div class="metric-icon" style="background: var(--secondary-green);">
                    <i class="fas fa-shopping-cart"></i>
                </div>
                <div class="metric-content">
                    <h3><?php echo formatCurrency($analytics['sales']['avg_order_value'] ?: 0); ?></h3>
                    <p>Avg Order Value</p>
                    <small><?php echo number_format($analytics['sales']['unique_customers'] ?: 0); ?> customers</small>
                </div>
            </div>
        </div>
        
        <div class="col-3">
            <div class="metric-card">
                <div class="metric-icon" style="background: #2196F3;">
                    <i class="fas fa-box"></i>
                </div>
                <div class="metric-content">
                    <h3><?php echo number_format($analytics['products']['active_products'] ?: 0); ?></h3>
                    <p>Active Products</p>
                    <small><?php echo number_format($analytics['products']['active_vendors'] ?: 0); ?> vendors</small>
                </div>
            </div>
        </div>
        
        <div class="col-3">
            <div class="metric-card">
                <div class="metric-icon" style="background: #FF9800;">
                    <i class="fas fa-users"></i>
                </div>
                <div class="metric-content">
                    <h3><?php echo number_format($analytics['users']['new_customers'] ?: 0); ?></h3>
                    <p>New Customers</p>
                    <small><?php echo number_format($analytics['users']['new_vendors'] ?: 0); ?> new vendors</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Sales Chart -->
    <div class="card mb-4">
        <div class="card-header">
            <h5>Daily Sales Performance</h5>
        </div>
        <div class="card-body">
            <?php if (empty($daily_sales)): ?>
                <div class="text-center" style="padding: 2rem;">
                    <i class="fas fa-chart-line" style="font-size: 3rem; color: var(--medium-gray); margin-bottom: 1rem;"></i>
                    <h6>No sales data for selected period</h6>
                    <p class="text-muted">Sales data will appear here once orders are placed in the selected date range.</p>
                </div>
            <?php else: ?>
                <div style="height: 400px; overflow-x: auto;">
                    <table style="width: 100%; border-collapse: collapse;">
                        <thead>
                            <tr style="border-bottom: 2px solid var(--light-gray);">
                                <th style="padding: 0.75rem; text-align: left;">Date</th>
                                <th style="padding: 0.75rem; text-align: right;">Orders</th>
                                <th style="padding: 0.75rem; text-align: right;">Revenue</th>
                                <th style="padding: 0.75rem; text-align: right;">Customers</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($daily_sales as $data): ?>
                                <tr style="border-bottom: 1px solid var(--light-gray);">
                                    <td style="padding: 0.75rem;"><?php echo date('M j, Y', strtotime($data['date'])); ?></td>
                                    <td style="padding: 0.75rem; text-align: right;"><?php echo number_format($data['orders']); ?></td>
                                    <td style="padding: 0.75rem; text-align: right; font-weight: 600;"><?php echo formatCurrency($data['revenue']); ?></td>
                                    <td style="padding: 0.75rem; text-align: right;"><?php echo number_format($data['customers']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="row">
        <!-- Top Vendors -->
        <div class="col-6">
            <div class="card">
                <div class="card-header">
                    <h6>Top Performing Vendors</h6>
                </div>
                <div class="card-body">
                    <?php if (empty($top_vendors)): ?>
                        <div class="text-center" style="padding: 1rem;">
                            <i class="fas fa-store" style="font-size: 2rem; color: var(--medium-gray); margin-bottom: 0.5rem;"></i>
                            <p class="text-muted" style="margin-bottom: 0;">No vendor data for selected period</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($top_vendors as $index => $vendor): ?>
                            <div style="display: flex; justify-content: between; align-items: center; padding: 0.75rem 0; border-bottom: 1px solid var(--light-gray);">
                                <div style="display: flex; align-items: center; gap: 0.75rem;">
                                    <div style="width: 32px; height: 32px; background: var(--primary-green); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold;">
                                        <?php echo $index + 1; ?>
                                    </div>
                                    <div>
                                        <strong style="font-size: 0.9rem;"><?php echo htmlspecialchars($vendor['store_name'] ?: $vendor['first_name'] . ' ' . $vendor['last_name']); ?></strong>
                                        <br>
                                        <small class="text-muted"><?php echo number_format($vendor['total_orders'] ?: 0); ?> orders</small>
                                    </div>
                                </div>
                                <div style="text-align: right;">
                                    <strong style="color: var(--primary-green);"><?php echo formatCurrency($vendor['total_revenue'] ?: 0); ?></strong>
                                    <?php if ($vendor['avg_rating']): ?>
                                        <br>
                                        <small style="color: #FFB74D;">★ <?php echo number_format($vendor['avg_rating'], 1); ?></small>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Top Products -->
        <div class="col-6">
            <div class="card">
                <div class="card-header">
                    <h6>Top Selling Products</h6>
                </div>
                <div class="card-body">
                    <?php if (empty($top_products)): ?>
                        <div class="text-center" style="padding: 1rem;">
                            <i class="fas fa-box" style="font-size: 2rem; color: var(--medium-gray); margin-bottom: 0.5rem;"></i>
                            <p class="text-muted" style="margin-bottom: 0;">No product sales for selected period</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($top_products as $index => $product): ?>
                            <div style="display: flex; justify-content: between; align-items: center; padding: 0.75rem 0; border-bottom: 1px solid var(--light-gray);">
                                <div style="display: flex; align-items: center; gap: 0.75rem;">
                                    <div style="width: 32px; height: 32px; background: var(--secondary-green); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold;">
                                        <?php echo $index + 1; ?>
                                    </div>
                                    <div>
                                        <strong style="font-size: 0.9rem;"><?php echo htmlspecialchars($product['name']); ?></strong>
                                        <br>
                                        <small class="text-muted"><?php echo htmlspecialchars($product['store_name'] ?: $product['vendor_first_name'] . ' ' . $product['vendor_last_name']); ?></small>
                                    </div>
                                </div>
                                <div style="text-align: right;">
                                    <strong style="color: var(--primary-green);"><?php echo number_format($product['units_sold'] ?: 0); ?> sold</strong>
                                    <br>
                                    <small class="text-muted"><?php echo formatCurrency($product['total_revenue'] ?: 0); ?></small>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Category Performance -->
    <div class="card mt-3">
        <div class="card-header">
            <h5>Category Performance</h5>
        </div>
        <div class="card-body">
            <?php if (empty($category_performance)): ?>
                <div class="text-center" style="padding: 2rem;">
                    <i class="fas fa-tags" style="font-size: 3rem; color: var(--medium-gray); margin-bottom: 1rem;"></i>
                    <h6>No category data available</h6>
                    <p class="text-muted">Category performance data will appear here once products are sold.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table style="width: 100%; border-collapse: collapse;">
                        <thead>
                            <tr style="border-bottom: 2px solid var(--light-gray);">
                                <th style="padding: 0.75rem; text-align: left;">Category</th>
                                <th style="padding: 0.75rem; text-align: right;">Products</th>
                                <th style="padding: 0.75rem; text-align: right;">Units Sold</th>
                                <th style="padding: 0.75rem; text-align: right;">Revenue</th>
                                <th style="padding: 0.75rem; text-align: right;">Avg Revenue/Product</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($category_performance as $category): ?>
                                <tr style="border-bottom: 1px solid var(--light-gray);">
                                    <td style="padding: 0.75rem;">
                                        <strong><?php echo htmlspecialchars($category['name']); ?></strong>
                                    </td>
                                    <td style="padding: 0.75rem; text-align: right;"><?php echo number_format($category['total_products'] ?: 0); ?></td>
                                    <td style="padding: 0.75rem; text-align: right;"><?php echo number_format($category['units_sold'] ?: 0); ?></td>
                                    <td style="padding: 0.75rem; text-align: right; font-weight: 600;"><?php echo formatCurrency($category['total_revenue'] ?: 0); ?></td>
                                    <td style="padding: 0.75rem; text-align: right;">
                                        <?php 
                                        $avg_revenue = ($category['total_products'] > 0) ? ($category['total_revenue'] / $category['total_products']) : 0;
                                        echo formatCurrency($avg_revenue);
                                        ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Export Options -->
    <div class="text-center mt-4">
        <div style="display: flex; gap: 1rem; justify-content: center;">
            <button onclick="exportReport('csv')" class="btn btn-outline">
                <i class="fas fa-file-csv"></i> Export CSV
            </button>
            <button onclick="exportReport('pdf')" class="btn btn-outline">
                <i class="fas fa-file-pdf"></i> Export PDF
            </button>
            <button onclick="window.print()" class="btn btn-outline">
                <i class="fas fa-print"></i> Print Report
            </button>
        </div>
    </div>
</div>

<style>
.metric-card {
    background: white;
    border-radius: var(--border-radius);
    padding: 1.5rem;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    display: flex;
    align-items: center;
    gap: 1rem;
    transition: var(--transition);
}

.metric-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 20px rgba(0,0,0,0.15);
}

.metric-icon {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.5rem;
}

.metric-content h3 {
    margin-bottom: 0.25rem;
    color: var(--black);
    font-size: 1.5rem;
    font-weight: bold;
}

.metric-content p {
    margin-bottom: 0.25rem;
    color: var(--dark-gray);
    font-weight: 600;
}

.metric-content small {
    color: var(--medium-gray);
    font-size: 0.8rem;
}

@media (max-width: 768px) {
    .col-3, .col-6 {
        flex: 0 0 100%;
        margin-bottom: 1rem;
    }
    
    .metric-card {
        flex-direction: column;
        text-align: center;
    }
}

@media print {
    .btn, .card-header {
        display: none !important;
    }
}
</style>

<script>
function exportReport(format) {
    const params = new URLSearchParams(window.location.search);
    params.set('export', format);
    
    // In a real implementation, this would trigger a download
    alert(`Export to ${format.toUpperCase()} functionality would be implemented here.`);
}

// Auto-refresh every 10 minutes
setInterval(function() {
    location.reload();
}, 600000);
</script>

<?php require_once '../includes/footer.php'; ?>
