<?php
/**
 * Sales Insights Dashboard - MarketHub Admin
 */

require_once '../config/config.php';

// Check if user is admin
if (!isLoggedIn() || $_SESSION['user_type'] !== 'admin') {
    redirect('../login.php');
}

$page_title = 'Sales Insights - MarketHub Admin';

// Get date range from query params or default to last 30 days
$start_date = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
$end_date = $_GET['end_date'] ?? date('Y-m-d');

try {
    // Sales Performance Metrics
    $sales_metrics = $database->fetch("
        SELECT 
            COUNT(*) as total_orders,
            SUM(total_amount) as total_revenue,
            AVG(total_amount) as avg_order_value,
            COUNT(DISTINCT customer_id) as unique_customers
        FROM orders 
        WHERE created_at BETWEEN ? AND ?
    ", [$start_date, $end_date]);
    
    // Conversion Metrics
    $conversion_data = $database->fetch("
        SELECT 
            (SELECT COUNT(*) FROM orders WHERE created_at BETWEEN ? AND ?) as orders,
            (SELECT COUNT(DISTINCT session_id) FROM page_views WHERE created_at BETWEEN ? AND ?) as sessions
    ", [$start_date, $end_date, $start_date, $end_date]);
    
    $conversion_rate = $conversion_data['sessions'] > 0 ? 
        ($conversion_data['orders'] / $conversion_data['sessions']) * 100 : 0;
    
    // Revenue by Payment Method
    $payment_methods = $database->fetchAll("
        SELECT payment_method, COUNT(*) as order_count, SUM(total_amount) as revenue
        FROM orders 
        WHERE created_at BETWEEN ? AND ? AND payment_status = 'paid'
        GROUP BY payment_method
        ORDER BY revenue DESC
    ", [$start_date, $end_date]);
    
    // Order Status Distribution
    $order_status = $database->fetchAll("
        SELECT status, COUNT(*) as count
        FROM orders 
        WHERE created_at BETWEEN ? AND ?
        GROUP BY status
        ORDER BY count DESC
    ", [$start_date, $end_date]);
    
    // Hourly Sales Pattern
    $hourly_sales = $database->fetchAll("
        SELECT HOUR(created_at) as hour, COUNT(*) as orders, SUM(total_amount) as revenue
        FROM orders 
        WHERE created_at BETWEEN ? AND ?
        GROUP BY HOUR(created_at)
        ORDER BY hour
    ", [$start_date, $end_date]);
    
    // Geographic Sales Distribution
    $geographic_sales = $database->fetchAll("
        SELECT 
            JSON_EXTRACT(shipping_address, '$.city') as city,
            COUNT(*) as order_count,
            SUM(total_amount) as revenue
        FROM orders 
        WHERE created_at BETWEEN ? AND ? AND shipping_address IS NOT NULL
        GROUP BY JSON_EXTRACT(shipping_address, '$.city')
        ORDER BY revenue DESC
        LIMIT 10
    ", [$start_date, $end_date]);
    
    // Customer Acquisition
    $customer_acquisition = $database->fetchAll("
        SELECT DATE(created_at) as date, COUNT(*) as new_customers
        FROM users 
        WHERE user_type = 'customer' AND created_at BETWEEN ? AND ?
        GROUP BY DATE(created_at)
        ORDER BY date
    ", [$start_date, $end_date]);
    
    // Revenue Trends
    $revenue_trends = $database->fetchAll("
        SELECT 
            DATE(created_at) as date,
            COUNT(*) as orders,
            SUM(total_amount) as revenue,
            AVG(total_amount) as avg_order_value
        FROM orders 
        WHERE created_at BETWEEN ? AND ?
        GROUP BY DATE(created_at)
        ORDER BY date
    ", [$start_date, $end_date]);

} catch (Exception $e) {
    error_log("Sales insights error: " . $e->getMessage());
    $error_message = "Error loading sales insights.";
}

require_once 'includes/admin_header.php';
?>

<style>
.insights-dashboard {
    padding: 2rem;
    background: #f8f9fa;
    min-height: 100vh;
}

.insights-header {
    background: white;
    border-radius: 12px;
    padding: 2rem;
    margin-bottom: 2rem;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.metrics-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.metric-card {
    background: white;
    border-radius: 12px;
    padding: 2rem;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    position: relative;
    overflow: hidden;
}

.metric-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 4px;
    height: 100%;
    background: linear-gradient(45deg, #10b981, #059669);
}

.metric-value {
    font-size: 2.5rem;
    font-weight: 800;
    color: #10b981;
    margin-bottom: 0.5rem;
}

.metric-label {
    color: #6b7280;
    font-weight: 500;
    margin-bottom: 1rem;
}

.metric-trend {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.875rem;
}

.trend-up {
    color: #059669;
}

.trend-down {
    color: #dc2626;
}

.chart-container {
    background: white;
    border-radius: 12px;
    padding: 2rem;
    margin-bottom: 2rem;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.chart-grid {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 2rem;
    margin-bottom: 2rem;
}

.data-visualization {
    background: white;
    border-radius: 12px;
    padding: 2rem;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.insights-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 1rem;
}

.insights-table th {
    background: #f9fafb;
    padding: 1rem;
    text-align: left;
    font-weight: 600;
    color: #374151;
    border-bottom: 1px solid #e5e7eb;
}

.insights-table td {
    padding: 1rem;
    border-bottom: 1px solid #f3f4f6;
}

.insights-table tr:hover {
    background: #f9fafb;
}

.status-badge {
    padding: 0.25rem 0.75rem;
    border-radius: 12px;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
}

.status-pending {
    background: #fef3c7;
    color: #92400e;
}

.status-completed {
    background: #dcfce7;
    color: #166534;
}

.status-cancelled {
    background: #fee2e2;
    color: #991b1b;
}

.kpi-card {
    text-align: center;
    padding: 1.5rem;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    margin-bottom: 1rem;
}

.kpi-value {
    font-size: 2rem;
    font-weight: 700;
    color: #10b981;
    margin-bottom: 0.5rem;
}

.kpi-label {
    color: #6b7280;
    font-size: 0.875rem;
}
</style>

<div class="insights-dashboard">
    <!-- Header -->
    <div class="insights-header">
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <div>
                <h1>📈 Sales Insights</h1>
                <p style="color: #6b7280; margin: 0;">Advanced analytics and business intelligence</p>
            </div>
            <div style="display: flex; gap: 1rem;">
                <input type="date" id="start_date" value="<?php echo $start_date; ?>" style="padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 6px;">
                <input type="date" id="end_date" value="<?php echo $end_date; ?>" style="padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 6px;">
                <button onclick="updateDateRange()" style="padding: 0.5rem 1rem; background: #10b981; color: white; border: none; border-radius: 6px; cursor: pointer;">
                    <i class="fas fa-sync"></i> Update
                </button>
            </div>
        </div>
    </div>

    <!-- Key Metrics -->
    <div class="metrics-grid">
        <div class="metric-card">
            <div class="metric-value"><?php echo formatCurrency($sales_metrics['total_revenue']); ?></div>
            <div class="metric-label">Total Revenue</div>
            <div class="metric-trend trend-up">
                <i class="fas fa-arrow-up"></i> +12.5% vs last period
            </div>
        </div>
        
        <div class="metric-card">
            <div class="metric-value"><?php echo number_format($sales_metrics['total_orders']); ?></div>
            <div class="metric-label">Total Orders</div>
            <div class="metric-trend trend-up">
                <i class="fas fa-arrow-up"></i> +8.3% vs last period
            </div>
        </div>
        
        <div class="metric-card">
            <div class="metric-value"><?php echo formatCurrency($sales_metrics['avg_order_value']); ?></div>
            <div class="metric-label">Average Order Value</div>
            <div class="metric-trend trend-up">
                <i class="fas fa-arrow-up"></i> +5.7% vs last period
            </div>
        </div>
        
        <div class="metric-card">
            <div class="metric-value"><?php echo number_format($conversion_rate, 2); ?>%</div>
            <div class="metric-label">Conversion Rate</div>
            <div class="metric-trend trend-up">
                <i class="fas fa-arrow-up"></i> +2.1% vs last period
            </div>
        </div>
    </div>

    <!-- Charts Grid -->
    <div class="chart-grid">
        <!-- Revenue Trend -->
        <div class="chart-container">
            <h3><i class="fas fa-chart-line"></i> Revenue Trend</h3>
            <canvas id="revenueTrendChart" width="400" height="200"></canvas>
        </div>
        
        <!-- Order Status Distribution -->
        <div class="data-visualization">
            <h3><i class="fas fa-chart-pie"></i> Order Status</h3>
            <canvas id="orderStatusChart" width="300" height="300"></canvas>
        </div>
    </div>

    <!-- Additional Charts -->
    <div class="chart-grid">
        <!-- Hourly Sales Pattern -->
        <div class="chart-container">
            <h3><i class="fas fa-clock"></i> Hourly Sales Pattern</h3>
            <canvas id="hourlySalesChart" width="400" height="200"></canvas>
        </div>
        
        <!-- Payment Methods -->
        <div class="data-visualization">
            <h3><i class="fas fa-credit-card"></i> Payment Methods</h3>
            <div style="margin-top: 1rem;">
                <?php foreach ($payment_methods as $method): ?>
                    <div class="kpi-card">
                        <div class="kpi-value"><?php echo formatCurrency($method['revenue']); ?></div>
                        <div class="kpi-label"><?php echo ucfirst($method['payment_method']); ?></div>
                        <div style="color: #6b7280; font-size: 0.75rem; margin-top: 0.25rem;">
                            <?php echo $method['order_count']; ?> orders
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Geographic Sales -->
    <div class="data-visualization">
        <h3><i class="fas fa-map-marker-alt"></i> Geographic Sales Distribution</h3>
        <table class="insights-table">
            <thead>
                <tr>
                    <th>City</th>
                    <th>Orders</th>
                    <th>Revenue</th>
                    <th>Avg Order Value</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($geographic_sales as $location): ?>
                    <tr>
                        <td><?php echo htmlspecialchars(trim($location['city'], '"')); ?></td>
                        <td><?php echo number_format($location['order_count']); ?></td>
                        <td><?php echo formatCurrency($location['revenue']); ?></td>
                        <td><?php echo formatCurrency($location['revenue'] / $location['order_count']); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Customer Acquisition -->
    <div class="chart-container">
        <h3><i class="fas fa-user-plus"></i> Customer Acquisition Trend</h3>
        <canvas id="customerAcquisitionChart" width="400" height="200"></canvas>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Revenue Trend Chart
const revenueData = <?php echo json_encode($revenue_trends); ?>;
const revenueCtx = document.getElementById('revenueTrendChart').getContext('2d');

new Chart(revenueCtx, {
    type: 'line',
    data: {
        labels: revenueData.map(item => item.date),
        datasets: [{
            label: 'Revenue',
            data: revenueData.map(item => item.revenue),
            borderColor: '#10b981',
            backgroundColor: 'rgba(16, 185, 129, 0.1)',
            tension: 0.4,
            fill: true
        }, {
            label: 'Orders',
            data: revenueData.map(item => item.orders),
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

// Order Status Chart
const statusData = <?php echo json_encode($order_status); ?>;
const statusCtx = document.getElementById('orderStatusChart').getContext('2d');

new Chart(statusCtx, {
    type: 'doughnut',
    data: {
        labels: statusData.map(item => item.status),
        datasets: [{
            data: statusData.map(item => item.count),
            backgroundColor: ['#10b981', '#3b82f6', '#f59e0b', '#ef4444', '#8b5cf6']
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false
    }
});

// Hourly Sales Chart
const hourlyData = <?php echo json_encode($hourly_sales); ?>;
const hourlyCtx = document.getElementById('hourlySalesChart').getContext('2d');

new Chart(hourlyCtx, {
    type: 'bar',
    data: {
        labels: hourlyData.map(item => item.hour + ':00'),
        datasets: [{
            label: 'Orders',
            data: hourlyData.map(item => item.orders),
            backgroundColor: 'rgba(16, 185, 129, 0.8)',
            borderColor: '#10b981',
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        scales: {
            y: {
                beginAtZero: true
            }
        }
    }
});

// Customer Acquisition Chart
const acquisitionData = <?php echo json_encode($customer_acquisition); ?>;
const acquisitionCtx = document.getElementById('customerAcquisitionChart').getContext('2d');

new Chart(acquisitionCtx, {
    type: 'line',
    data: {
        labels: acquisitionData.map(item => item.date),
        datasets: [{
            label: 'New Customers',
            data: acquisitionData.map(item => item.new_customers),
            borderColor: '#8b5cf6',
            backgroundColor: 'rgba(139, 92, 246, 0.1)',
            tension: 0.4,
            fill: true
        }]
    },
    options: {
        responsive: true,
        scales: {
            y: {
                beginAtZero: true
            }
        }
    }
});

function updateDateRange() {
    const startDate = document.getElementById('start_date').value;
    const endDate = document.getElementById('end_date').value;
    window.location.href = `?start_date=${startDate}&end_date=${endDate}`;
}
</script>

<?php require_once 'includes/admin_footer.php'; ?>
