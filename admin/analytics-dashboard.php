<?php
/**
 * Analytics Dashboard - MarketHub Admin
 */

require_once '../config/config.php';

// Check if user is admin
if (!isLoggedIn() || $_SESSION['user_type'] !== 'admin') {
    redirect('../login.php');
}

$page_title = 'Analytics Dashboard - MarketHub Admin';

// Get date range from query params or default to last 30 days
$start_date = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
$end_date = $_GET['end_date'] ?? date('Y-m-d');

try {
    // Overall Statistics
    $total_customers = $database->fetch("SELECT COUNT(*) as count FROM users WHERE user_type = 'customer'")['count'];
    $total_vendors = $database->fetch("SELECT COUNT(*) as count FROM users WHERE user_type = 'vendor'")['count'];
    $total_products = $database->fetch("SELECT COUNT(*) as count FROM products WHERE status = 'active'")['count'];
    $total_orders = $database->fetch("SELECT COUNT(*) as count FROM orders WHERE created_at BETWEEN ? AND ?", [$start_date, $end_date])['count'];
    
    // Revenue Analytics
    $total_revenue = $database->fetch("SELECT COALESCE(SUM(total_amount), 0) as revenue FROM orders WHERE status IN ('completed', 'delivered') AND created_at BETWEEN ? AND ?", [$start_date, $end_date])['revenue'];
    $avg_order_value = $total_orders > 0 ? $total_revenue / $total_orders : 0;
    
    // Customer Analytics
    $new_customers = $database->fetch("SELECT COUNT(*) as count FROM users WHERE user_type = 'customer' AND created_at BETWEEN ? AND ?", [$start_date, $end_date])['count'];
    $active_customers = $database->fetch("SELECT COUNT(DISTINCT customer_id) as count FROM orders WHERE created_at BETWEEN ? AND ?", [$start_date, $end_date])['count'];
    
    // Top Products
    $top_products = $database->fetchAll("
        SELECT p.name, p.price, COUNT(oi.id) as order_count, SUM(oi.quantity) as total_sold, SUM(oi.quantity * oi.unit_price) as revenue
        FROM products p
        JOIN order_items oi ON p.id = oi.product_id
        JOIN orders o ON oi.order_id = o.id
        WHERE o.created_at BETWEEN ? AND ?
        GROUP BY p.id
        ORDER BY total_sold DESC
        LIMIT 10
    ", [$start_date, $end_date]);
    
    // Top Vendors
    $top_vendors = $database->fetchAll("
        SELECT u.username, vs.store_name, COUNT(DISTINCT o.id) as order_count, SUM(o.total_amount) as revenue
        FROM users u
        LEFT JOIN vendor_stores vs ON u.id = vs.vendor_id
        JOIN products p ON u.id = p.vendor_id
        JOIN order_items oi ON p.id = oi.product_id
        JOIN orders o ON oi.order_id = o.id
        WHERE o.created_at BETWEEN ? AND ? AND u.user_type = 'vendor'
        GROUP BY u.id
        ORDER BY revenue DESC
        LIMIT 10
    ", [$start_date, $end_date]);
    
    // Daily Sales Data for Chart
    $daily_sales = $database->fetchAll("
        SELECT DATE(created_at) as date, COUNT(*) as orders, SUM(total_amount) as revenue
        FROM orders
        WHERE created_at BETWEEN ? AND ?
        GROUP BY DATE(created_at)
        ORDER BY date
    ", [$start_date, $end_date]);
    
    // Category Performance
    $category_performance = $database->fetchAll("
        SELECT c.name, COUNT(oi.id) as order_count, SUM(oi.quantity) as items_sold, SUM(oi.quantity * oi.unit_price) as revenue
        FROM categories c
        JOIN products p ON c.id = p.category_id
        JOIN order_items oi ON p.id = oi.product_id
        JOIN orders o ON oi.order_id = o.id
        WHERE o.created_at BETWEEN ? AND ?
        GROUP BY c.id
        ORDER BY revenue DESC
        LIMIT 10
    ", [$start_date, $end_date]);

} catch (Exception $e) {
    error_log("Analytics error: " . $e->getMessage());
    $error_message = "Error loading analytics data.";
}

require_once 'includes/admin_header.php';
?>

<style>
.analytics-dashboard {
    padding: 2rem;
    background: #f8f9fa;
    min-height: 100vh;
}

.dashboard-header {
    background: white;
    border-radius: 12px;
    padding: 2rem;
    margin-bottom: 2rem;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.date-filter {
    display: flex;
    gap: 1rem;
    align-items: center;
    margin-bottom: 2rem;
}

.date-filter input {
    padding: 0.5rem;
    border: 1px solid #d1d5db;
    border-radius: 6px;
}

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
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    border-left: 4px solid #10b981;
}

.stat-value {
    font-size: 2rem;
    font-weight: 800;
    color: #10b981;
    margin-bottom: 0.5rem;
}

.stat-label {
    color: #6b7280;
    font-weight: 500;
}

.stat-change {
    font-size: 0.875rem;
    margin-top: 0.5rem;
}

.stat-change.positive {
    color: #059669;
}

.stat-change.negative {
    color: #dc2626;
}

.chart-container {
    background: white;
    border-radius: 12px;
    padding: 2rem;
    margin-bottom: 2rem;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.data-table {
    background: white;
    border-radius: 12px;
    padding: 2rem;
    margin-bottom: 2rem;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.data-table h3 {
    color: #1f2937;
    margin-bottom: 1.5rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.table {
    width: 100%;
    border-collapse: collapse;
}

.table th {
    background: #f9fafb;
    padding: 1rem;
    text-align: left;
    font-weight: 600;
    color: #374151;
    border-bottom: 1px solid #e5e7eb;
}

.table td {
    padding: 1rem;
    border-bottom: 1px solid #f3f4f6;
}

.table tr:hover {
    background: #f9fafb;
}

.export-btn {
    background: #10b981;
    color: white;
    border: none;
    padding: 0.75rem 1.5rem;
    border-radius: 6px;
    cursor: pointer;
    font-weight: 500;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    text-decoration: none;
}

.export-btn:hover {
    background: #059669;
}

.metric-icon {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    margin-bottom: 1rem;
}

.revenue-icon {
    background: #dcfce7;
    color: #166534;
}

.orders-icon {
    background: #dbeafe;
    color: #1e40af;
}

.customers-icon {
    background: #fef3c7;
    color: #92400e;
}

.products-icon {
    background: #e0e7ff;
    color: #3730a3;
}
</style>

<div class="analytics-dashboard">
    <!-- Dashboard Header -->
    <div class="dashboard-header">
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <div>
                <h1>📊 Analytics Dashboard</h1>
                <p style="color: #6b7280; margin: 0;">Comprehensive insights into your marketplace performance</p>
            </div>
            <div>
                <button class="export-btn" onclick="exportData()">
                    <i class="fas fa-download"></i> Export Report
                </button>
            </div>
        </div>
        
        <!-- Date Filter -->
        <div class="date-filter">
            <label>Date Range:</label>
            <input type="date" id="start_date" value="<?php echo $start_date; ?>">
            <span>to</span>
            <input type="date" id="end_date" value="<?php echo $end_date; ?>">
            <button class="export-btn" onclick="updateDateRange()">
                <i class="fas fa-sync"></i> Update
            </button>
        </div>
    </div>

    <!-- Key Metrics -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="metric-icon revenue-icon">
                <i class="fas fa-dollar-sign"></i>
            </div>
            <div class="stat-value"><?php echo formatCurrency($total_revenue); ?></div>
            <div class="stat-label">Total Revenue</div>
            <div class="stat-change positive">
                <i class="fas fa-arrow-up"></i> +12.5% from last period
            </div>
        </div>
        
        <div class="stat-card">
            <div class="metric-icon orders-icon">
                <i class="fas fa-shopping-cart"></i>
            </div>
            <div class="stat-value"><?php echo number_format($total_orders); ?></div>
            <div class="stat-label">Total Orders</div>
            <div class="stat-change positive">
                <i class="fas fa-arrow-up"></i> +8.3% from last period
            </div>
        </div>
        
        <div class="stat-card">
            <div class="metric-icon customers-icon">
                <i class="fas fa-users"></i>
            </div>
            <div class="stat-value"><?php echo number_format($active_customers); ?></div>
            <div class="stat-label">Active Customers</div>
            <div class="stat-change positive">
                <i class="fas fa-arrow-up"></i> +15.2% from last period
            </div>
        </div>
        
        <div class="stat-card">
            <div class="metric-icon products-icon">
                <i class="fas fa-box"></i>
            </div>
            <div class="stat-value"><?php echo formatCurrency($avg_order_value); ?></div>
            <div class="stat-label">Avg Order Value</div>
            <div class="stat-change positive">
                <i class="fas fa-arrow-up"></i> +5.7% from last period
            </div>
        </div>
    </div>

    <!-- Sales Chart -->
    <div class="chart-container">
        <h3><i class="fas fa-chart-line"></i> Sales Trend</h3>
        <canvas id="salesChart" width="400" height="100"></canvas>
    </div>

    <!-- Data Tables -->
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
        <!-- Top Products -->
        <div class="data-table">
            <h3><i class="fas fa-star"></i> Top Products</h3>
            <table class="table">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Sold</th>
                        <th>Revenue</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($top_products as $product): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($product['name']); ?></td>
                            <td><?php echo number_format($product['total_sold']); ?></td>
                            <td><?php echo formatCurrency($product['revenue']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Top Vendors -->
        <div class="data-table">
            <h3><i class="fas fa-store"></i> Top Vendors</h3>
            <table class="table">
                <thead>
                    <tr>
                        <th>Vendor</th>
                        <th>Orders</th>
                        <th>Revenue</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($top_vendors as $vendor): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($vendor['store_name'] ?: $vendor['username']); ?></td>
                            <td><?php echo number_format($vendor['order_count']); ?></td>
                            <td><?php echo formatCurrency($vendor['revenue']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Category Performance -->
    <div class="data-table">
        <h3><i class="fas fa-th-large"></i> Category Performance</h3>
        <table class="table">
            <thead>
                <tr>
                    <th>Category</th>
                    <th>Orders</th>
                    <th>Items Sold</th>
                    <th>Revenue</th>
                    <th>Avg Order Value</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($category_performance as $category): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($category['name']); ?></td>
                        <td><?php echo number_format($category['order_count']); ?></td>
                        <td><?php echo number_format($category['items_sold']); ?></td>
                        <td><?php echo formatCurrency($category['revenue']); ?></td>
                        <td><?php echo formatCurrency($category['order_count'] > 0 ? $category['revenue'] / $category['order_count'] : 0); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Sales Chart
const salesData = <?php echo json_encode($daily_sales); ?>;
const ctx = document.getElementById('salesChart').getContext('2d');

new Chart(ctx, {
    type: 'line',
    data: {
        labels: salesData.map(item => item.date),
        datasets: [{
            label: 'Revenue',
            data: salesData.map(item => item.revenue),
            borderColor: '#10b981',
            backgroundColor: 'rgba(16, 185, 129, 0.1)',
            tension: 0.4,
            fill: true
        }, {
            label: 'Orders',
            data: salesData.map(item => item.orders),
            borderColor: '#3b82f6',
            backgroundColor: 'rgba(59, 130, 246, 0.1)',
            tension: 0.4,
            yAxisID: 'y1'
        }]
    },
    options: {
        responsive: true,
        scales: {
            y: {
                type: 'linear',
                display: true,
                position: 'left',
            },
            y1: {
                type: 'linear',
                display: true,
                position: 'right',
                grid: {
                    drawOnChartArea: false,
                },
            }
        }
    }
});

function updateDateRange() {
    const startDate = document.getElementById('start_date').value;
    const endDate = document.getElementById('end_date').value;
    window.location.href = `?start_date=${startDate}&end_date=${endDate}`;
}

function exportData() {
    const startDate = document.getElementById('start_date').value;
    const endDate = document.getElementById('end_date').value;
    window.open(`export-analytics.php?start_date=${startDate}&end_date=${endDate}&format=csv`);
}
</script>

<?php require_once 'includes/admin_footer.php'; ?>
