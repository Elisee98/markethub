<?php
/**
 * Customer Analytics Dashboard - MarketHub Admin
 */

require_once '../config/config.php';

// Check if user is admin
if (!isLoggedIn() || $_SESSION['user_type'] !== 'admin') {
    redirect('../login.php');
}

$page_title = 'Customer Analytics - MarketHub Admin';

// Get date range from query params or default to last 30 days
$start_date = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
$end_date = $_GET['end_date'] ?? date('Y-m-d');

try {
    // Customer Overview Metrics
    $customer_overview = $database->fetch("
        SELECT 
            COUNT(*) as total_customers,
            COUNT(CASE WHEN created_at BETWEEN ? AND ? THEN 1 END) as new_customers,
            COUNT(CASE WHEN status = 'active' THEN 1 END) as active_customers
        FROM users 
        WHERE user_type = 'customer'
    ", [$start_date, $end_date]);
    
    // Customer Activity Metrics
    $activity_metrics = $database->fetch("
        SELECT 
            COUNT(DISTINCT customer_id) as customers_with_orders,
            COUNT(*) as total_orders,
            SUM(total_amount) as total_revenue,
            AVG(total_amount) as avg_order_value
        FROM orders 
        WHERE created_at BETWEEN ? AND ?
    ", [$start_date, $end_date]);
    
    // Customer Lifetime Value
    $ltv_data = $database->fetch("
        SELECT 
            AVG(customer_ltv) as avg_ltv,
            MAX(customer_ltv) as max_ltv,
            MIN(customer_ltv) as min_ltv
        FROM (
            SELECT customer_id, SUM(total_amount) as customer_ltv
            FROM orders 
            GROUP BY customer_id
        ) as ltv_calc
    ");
    
    // Customer Acquisition by Month
    $acquisition_trend = $database->fetchAll("
        SELECT 
            DATE_FORMAT(created_at, '%Y-%m') as month,
            COUNT(*) as new_customers
        FROM users 
        WHERE user_type = 'customer' 
        AND created_at >= DATE_SUB(?, FROM_INTERVAL 12 MONTH)
        GROUP BY DATE_FORMAT(created_at, '%Y-%m')
        ORDER BY month
    ", [$end_date]);
    
    // Customer Segmentation by Order Count
    $customer_segments = $database->fetchAll("
        SELECT 
            CASE 
                WHEN order_count = 0 THEN 'No Orders'
                WHEN order_count = 1 THEN 'One-time Buyers'
                WHEN order_count BETWEEN 2 AND 5 THEN 'Regular Customers'
                WHEN order_count BETWEEN 6 AND 10 THEN 'Loyal Customers'
                ELSE 'VIP Customers'
            END as segment,
            COUNT(*) as customer_count,
            AVG(total_spent) as avg_spent
        FROM (
            SELECT 
                u.id,
                COUNT(o.id) as order_count,
                COALESCE(SUM(o.total_amount), 0) as total_spent
            FROM users u
            LEFT JOIN orders o ON u.id = o.customer_id
            WHERE u.user_type = 'customer'
            GROUP BY u.id
        ) as customer_data
        GROUP BY segment
        ORDER BY 
            CASE segment
                WHEN 'VIP Customers' THEN 1
                WHEN 'Loyal Customers' THEN 2
                WHEN 'Regular Customers' THEN 3
                WHEN 'One-time Buyers' THEN 4
                WHEN 'No Orders' THEN 5
            END
    ");
    
    // Top Customers by Revenue
    $top_customers = $database->fetchAll("
        SELECT 
            u.first_name, u.last_name, u.email,
            COUNT(o.id) as order_count,
            SUM(o.total_amount) as total_spent,
            AVG(o.total_amount) as avg_order_value,
            MAX(o.created_at) as last_order_date,
            u.created_at as registration_date
        FROM users u
        JOIN orders o ON u.id = o.customer_id
        WHERE u.user_type = 'customer' AND o.created_at BETWEEN ? AND ?
        GROUP BY u.id
        ORDER BY total_spent DESC
        LIMIT 20
    ", [$start_date, $end_date]);
    
    // Customer Retention Analysis
    $retention_data = $database->fetchAll("
        SELECT 
            DATE_FORMAT(first_order, '%Y-%m') as cohort_month,
            COUNT(*) as customers,
            COUNT(CASE WHEN months_since_first = 1 THEN 1 END) as month_1,
            COUNT(CASE WHEN months_since_first = 2 THEN 1 END) as month_2,
            COUNT(CASE WHEN months_since_first = 3 THEN 1 END) as month_3,
            COUNT(CASE WHEN months_since_first = 6 THEN 1 END) as month_6
        FROM (
            SELECT 
                customer_id,
                MIN(DATE(created_at)) as first_order,
                TIMESTAMPDIFF(MONTH, MIN(DATE(created_at)), MAX(DATE(created_at))) as months_since_first
            FROM orders
            GROUP BY customer_id
        ) as cohort_data
        WHERE first_order >= DATE_SUB(?, FROM_INTERVAL 12 MONTH)
        GROUP BY cohort_month
        ORDER BY cohort_month
    ", [$end_date]);
    
    // Geographic Distribution
    $geographic_data = $database->fetchAll("
        SELECT 
            JSON_EXTRACT(shipping_address, '$.city') as city,
            COUNT(DISTINCT customer_id) as customer_count,
            COUNT(*) as order_count,
            SUM(total_amount) as revenue
        FROM orders 
        WHERE created_at BETWEEN ? AND ? AND shipping_address IS NOT NULL
        GROUP BY JSON_EXTRACT(shipping_address, '$.city')
        ORDER BY customer_count DESC
        LIMIT 10
    ", [$start_date, $end_date]);
    
    // Customer Behavior Patterns
    $behavior_patterns = $database->fetchAll("
        SELECT 
            DAYNAME(created_at) as day_of_week,
            HOUR(created_at) as hour_of_day,
            COUNT(*) as order_count,
            COUNT(DISTINCT customer_id) as unique_customers
        FROM orders 
        WHERE created_at BETWEEN ? AND ?
        GROUP BY DAYNAME(created_at), HOUR(created_at)
        ORDER BY order_count DESC
        LIMIT 20
    ", [$start_date, $end_date]);

} catch (Exception $e) {
    error_log("Customer analytics error: " . $e->getMessage());
    $error_message = "Error loading customer analytics data.";
}

require_once 'includes/admin_header.php';
?>

<style>
.customer-analytics {
    padding: 2rem;
    background: #f8f9fa;
    min-height: 100vh;
}

.analytics-header {
    background: white;
    border-radius: 12px;
    padding: 2rem;
    margin-bottom: 2rem;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.metrics-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.metric-card {
    background: white;
    border-radius: 12px;
    padding: 1.5rem;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    text-align: center;
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
    font-size: 2rem;
    font-weight: 800;
    color: #10b981;
    margin-bottom: 0.5rem;
}

.metric-label {
    color: #6b7280;
    font-weight: 500;
}

.metric-trend {
    font-size: 0.875rem;
    margin-top: 0.5rem;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.25rem;
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

.segment-card {
    background: #f8f9fa;
    border-radius: 8px;
    padding: 1rem;
    margin-bottom: 1rem;
    border-left: 4px solid #10b981;
}

.segment-name {
    font-weight: 600;
    color: #1f2937;
    margin-bottom: 0.5rem;
}

.segment-stats {
    display: flex;
    justify-content: space-between;
    color: #6b7280;
    font-size: 0.875rem;
}

@media (max-width: 1024px) {
    .chart-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<div class="customer-analytics">
    <!-- Header -->
    <div class="analytics-header">
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <div>
                <h1>👥 Customer Analytics</h1>
                <p style="color: #6b7280; margin: 0;">Comprehensive customer insights and behavior analysis</p>
            </div>
            <div style="display: flex; gap: 1rem; align-items: center;">
                <input type="date" id="start_date" value="<?php echo $start_date; ?>" style="padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 6px;">
                <span>to</span>
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
            <div class="metric-value"><?php echo number_format($customer_overview['total_customers']); ?></div>
            <div class="metric-label">Total Customers</div>
            <div class="metric-trend trend-up">
                <i class="fas fa-arrow-up"></i> +<?php echo number_format($customer_overview['new_customers']); ?> new
            </div>
        </div>
        
        <div class="metric-card">
            <div class="metric-value"><?php echo number_format($activity_metrics['customers_with_orders']); ?></div>
            <div class="metric-label">Active Customers</div>
            <div class="metric-trend">
                <?php 
                    $activity_rate = $customer_overview['total_customers'] > 0 ? 
                        ($activity_metrics['customers_with_orders'] / $customer_overview['total_customers']) * 100 : 0;
                    echo number_format($activity_rate, 1) . '% activity rate';
                ?>
            </div>
        </div>
        
        <div class="metric-card">
            <div class="metric-value"><?php echo formatCurrency($ltv_data['avg_ltv']); ?></div>
            <div class="metric-label">Avg Customer LTV</div>
            <div class="metric-trend">
                Max: <?php echo formatCurrency($ltv_data['max_ltv']); ?>
            </div>
        </div>
        
        <div class="metric-card">
            <div class="metric-value"><?php echo formatCurrency($activity_metrics['avg_order_value']); ?></div>
            <div class="metric-label">Avg Order Value</div>
            <div class="metric-trend trend-up">
                <i class="fas fa-arrow-up"></i> +5.2% vs last period
            </div>
        </div>
    </div>

    <!-- Charts -->
    <div class="chart-grid">
        <!-- Customer Acquisition Trend -->
        <div class="chart-container">
            <h3><i class="fas fa-user-plus"></i> Customer Acquisition Trend</h3>
            <canvas id="acquisitionChart" width="400" height="200"></canvas>
        </div>
        
        <!-- Customer Segmentation -->
        <div class="chart-container">
            <h3><i class="fas fa-users"></i> Customer Segmentation</h3>
            <div style="margin-top: 1rem;">
                <?php foreach ($customer_segments as $segment): ?>
                    <div class="segment-card">
                        <div class="segment-name"><?php echo $segment['segment']; ?></div>
                        <div class="segment-stats">
                            <span><?php echo number_format($segment['customer_count']); ?> customers</span>
                            <span>Avg: <?php echo formatCurrency($segment['avg_spent']); ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Geographic Distribution -->
    <div class="chart-container">
        <h3><i class="fas fa-map-marker-alt"></i> Geographic Distribution</h3>
        <canvas id="geographicChart" width="400" height="200"></canvas>
    </div>

    <!-- Top Customers -->
    <div class="data-table">
        <h3><i class="fas fa-crown"></i> Top Customers by Revenue</h3>
        <div style="overflow-x: auto;">
            <table class="table">
                <thead>
                    <tr>
                        <th>Customer</th>
                        <th>Email</th>
                        <th>Orders</th>
                        <th>Total Spent</th>
                        <th>Avg Order Value</th>
                        <th>Last Order</th>
                        <th>Member Since</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($top_customers as $customer): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($customer['first_name'] . ' ' . $customer['last_name']); ?></td>
                            <td><?php echo htmlspecialchars($customer['email']); ?></td>
                            <td><?php echo number_format($customer['order_count']); ?></td>
                            <td><?php echo formatCurrency($customer['total_spent']); ?></td>
                            <td><?php echo formatCurrency($customer['avg_order_value']); ?></td>
                            <td><?php echo date('M j, Y', strtotime($customer['last_order_date'])); ?></td>
                            <td><?php echo date('M j, Y', strtotime($customer['registration_date'])); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Customer Retention -->
    <div class="data-table">
        <h3><i class="fas fa-heart"></i> Customer Retention Cohort Analysis</h3>
        <div style="overflow-x: auto;">
            <table class="table">
                <thead>
                    <tr>
                        <th>Cohort Month</th>
                        <th>Customers</th>
                        <th>Month 1</th>
                        <th>Month 2</th>
                        <th>Month 3</th>
                        <th>Month 6</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($retention_data as $cohort): ?>
                        <tr>
                            <td><?php echo $cohort['cohort_month']; ?></td>
                            <td><?php echo number_format($cohort['customers']); ?></td>
                            <td><?php echo $cohort['customers'] > 0 ? number_format(($cohort['month_1'] / $cohort['customers']) * 100, 1) . '%' : '0%'; ?></td>
                            <td><?php echo $cohort['customers'] > 0 ? number_format(($cohort['month_2'] / $cohort['customers']) * 100, 1) . '%' : '0%'; ?></td>
                            <td><?php echo $cohort['customers'] > 0 ? number_format(($cohort['month_3'] / $cohort['customers']) * 100, 1) . '%' : '0%'; ?></td>
                            <td><?php echo $cohort['customers'] > 0 ? number_format(($cohort['month_6'] / $cohort['customers']) * 100, 1) . '%' : '0%'; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Customer Behavior Patterns -->
    <div class="data-table">
        <h3><i class="fas fa-clock"></i> Customer Behavior Patterns</h3>
        <div style="overflow-x: auto;">
            <table class="table">
                <thead>
                    <tr>
                        <th>Day of Week</th>
                        <th>Hour</th>
                        <th>Orders</th>
                        <th>Unique Customers</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($behavior_patterns as $pattern): ?>
                        <tr>
                            <td><?php echo $pattern['day_of_week']; ?></td>
                            <td><?php echo $pattern['hour_of_day'] . ':00'; ?></td>
                            <td><?php echo number_format($pattern['order_count']); ?></td>
                            <td><?php echo number_format($pattern['unique_customers']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Customer Acquisition Chart
const acquisitionData = <?php echo json_encode($acquisition_trend); ?>;
const acquisitionCtx = document.getElementById('acquisitionChart').getContext('2d');

new Chart(acquisitionCtx, {
    type: 'line',
    data: {
        labels: acquisitionData.map(item => item.month),
        datasets: [{
            label: 'New Customers',
            data: acquisitionData.map(item => item.new_customers),
            borderColor: '#10b981',
            backgroundColor: 'rgba(16, 185, 129, 0.1)',
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

// Geographic Distribution Chart
const geographicData = <?php echo json_encode($geographic_data); ?>;
const geographicCtx = document.getElementById('geographicChart').getContext('2d');

new Chart(geographicCtx, {
    type: 'bar',
    data: {
        labels: geographicData.map(item => item.city.replace(/"/g, '')),
        datasets: [{
            label: 'Customers',
            data: geographicData.map(item => item.customer_count),
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

function updateDateRange() {
    const startDate = document.getElementById('start_date').value;
    const endDate = document.getElementById('end_date').value;
    window.location.href = `?start_date=${startDate}&end_date=${endDate}`;
}
</script>

<?php require_once 'includes/admin_footer.php'; ?>
