<?php
/**
 * Product Performance Analytics - MarketHub Admin
 */

require_once '../config/config.php';

// Check if user is admin
if (!isLoggedIn() || $_SESSION['user_type'] !== 'admin') {
    redirect('../login.php');
}

$page_title = 'Product Analytics - MarketHub Admin';

// Get date range from query params or default to last 30 days
$start_date = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
$end_date = $_GET['end_date'] ?? date('Y-m-d');

// Get category filter if provided
$category_id = $_GET['category_id'] ?? null;
$vendor_id = $_GET['vendor_id'] ?? null;

try {
    // Get categories for filter
    $categories = $database->fetchAll("SELECT id, name FROM categories WHERE status = 'active' ORDER BY name");
    
    // Get vendors for filter
    $vendors = $database->fetchAll("
        SELECT u.id, u.username, vs.store_name 
        FROM users u 
        LEFT JOIN vendor_stores vs ON u.id = vs.vendor_id
        WHERE u.user_type = 'vendor' AND u.status = 'active' 
        ORDER BY vs.store_name, u.username
    ");
    
    // Build query conditions
    $conditions = ["p.status = 'active'"];
    $params = [];
    
    if ($category_id) {
        $conditions[] = "p.category_id = ?";
        $params[] = $category_id;
    }
    
    if ($vendor_id) {
        $conditions[] = "p.vendor_id = ?";
        $params[] = $vendor_id;
    }
    
    $where_clause = !empty($conditions) ? "WHERE " . implode(" AND ", $conditions) : "";
    
    // Product Performance Overview
    $product_overview = $database->fetch("
        SELECT 
            COUNT(DISTINCT p.id) as total_products,
            SUM(p.stock_quantity) as total_inventory,
            AVG(p.price) as avg_price,
            COUNT(DISTINCT CASE WHEN p.stock_quantity = 0 THEN p.id END) as out_of_stock
        FROM products p
        $where_clause
    ", $params);
    
    // Top Selling Products
    $params_with_date = array_merge([$start_date, $end_date], $params);
    $top_products = $database->fetchAll("
        SELECT 
            p.id, p.name, p.price, p.stock_quantity, c.name as category_name,
            u.username as vendor_name, vs.store_name,
            COUNT(oi.id) as order_count, SUM(oi.quantity) as total_sold,
            SUM(oi.quantity * oi.unit_price) as revenue,
            AVG(pr.rating) as avg_rating, COUNT(pr.id) as review_count
        FROM products p
        LEFT JOIN order_items oi ON p.id = oi.product_id
        LEFT JOIN orders o ON oi.order_id = o.id AND o.created_at BETWEEN ? AND ?
        LEFT JOIN product_reviews pr ON p.id = pr.product_id
        LEFT JOIN categories c ON p.category_id = c.id
        LEFT JOIN users u ON p.vendor_id = u.id
        LEFT JOIN vendor_stores vs ON u.id = vs.vendor_id
        $where_clause
        GROUP BY p.id
        ORDER BY total_sold DESC
        LIMIT 20
    ", $params_with_date);
    
    // Category Performance
    $category_performance = $database->fetchAll("
        SELECT 
            c.id, c.name, COUNT(DISTINCT p.id) as product_count,
            SUM(oi.quantity) as total_sold, SUM(oi.quantity * oi.unit_price) as revenue,
            AVG(oi.unit_price) as avg_price
        FROM categories c
        JOIN products p ON c.id = p.category_id
        LEFT JOIN order_items oi ON p.id = oi.product_id
        LEFT JOIN orders o ON oi.order_id = o.id AND o.created_at BETWEEN ? AND ?
        GROUP BY c.id
        ORDER BY total_sold DESC
    ", [$start_date, $end_date]);
    
    // Price Range Analysis
    $price_ranges = $database->fetchAll("
        SELECT 
            CASE 
                WHEN p.price < 1000 THEN 'Under 1,000 RWF'
                WHEN p.price BETWEEN 1000 AND 5000 THEN '1,000 - 5,000 RWF'
                WHEN p.price BETWEEN 5001 AND 10000 THEN '5,001 - 10,000 RWF'
                WHEN p.price BETWEEN 10001 AND 50000 THEN '10,001 - 50,000 RWF'
                ELSE 'Over 50,000 RWF'
            END as price_range,
            COUNT(DISTINCT p.id) as product_count,
            SUM(oi.quantity) as total_sold,
            SUM(oi.quantity * oi.unit_price) as revenue
        FROM products p
        LEFT JOIN order_items oi ON p.id = oi.product_id
        LEFT JOIN orders o ON oi.order_id = o.id AND o.created_at BETWEEN ? AND ?
        $where_clause
        GROUP BY price_range
        ORDER BY MIN(p.price)
    ", array_merge([$start_date, $end_date], $params));
    
    // Product View to Purchase Conversion
    $product_conversion = $database->fetchAll("
        SELECT 
            p.id, p.name, 
            COUNT(DISTINCT pv.id) as view_count,
            COUNT(DISTINCT oi.id) as purchase_count,
            CASE 
                WHEN COUNT(DISTINCT pv.id) > 0 
                THEN (COUNT(DISTINCT oi.id) / COUNT(DISTINCT pv.id)) * 100 
                ELSE 0 
            END as conversion_rate
        FROM products p
        LEFT JOIN product_views pv ON p.id = pv.product_id AND pv.created_at BETWEEN ? AND ?
        LEFT JOIN order_items oi ON p.id = oi.product_id
        LEFT JOIN orders o ON oi.order_id = o.id AND o.created_at BETWEEN ? AND ?
        $where_clause
        GROUP BY p.id
        HAVING view_count > 0
        ORDER BY conversion_rate DESC
        LIMIT 20
    ", array_merge([$start_date, $end_date, $start_date, $end_date], $params));
    
    // Product Rating Analysis
    $rating_analysis = $database->fetchAll("
        SELECT 
            FLOOR(rating) as rating_value,
            COUNT(*) as review_count,
            AVG(rating) as avg_rating
        FROM product_reviews pr
        JOIN products p ON pr.product_id = p.id
        $where_clause
        GROUP BY FLOOR(rating)
        ORDER BY rating_value
    ", $params);
    
    // Low Stock Products
    $low_stock = $database->fetchAll("
        SELECT 
            p.id, p.name, p.price, p.stock_quantity, c.name as category_name,
            u.username as vendor_name, vs.store_name
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        LEFT JOIN users u ON p.vendor_id = u.id
        LEFT JOIN vendor_stores vs ON u.id = vs.vendor_id
        $where_clause
        AND p.stock_quantity <= 5 AND p.stock_quantity > 0
        ORDER BY p.stock_quantity
        LIMIT 20
    ", $params);

} catch (Exception $e) {
    error_log("Product analytics error: " . $e->getMessage());
    $error_message = "Error loading product analytics data.";
}

require_once 'includes/admin_header.php';
?>

<style>
.product-analytics {
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

.filters {
    display: flex;
    gap: 1rem;
    align-items: center;
    margin-top: 1.5rem;
    flex-wrap: wrap;
}

.filter-select {
    padding: 0.5rem;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    min-width: 200px;
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

.data-card {
    background: white;
    border-radius: 12px;
    padding: 2rem;
    margin-bottom: 2rem;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.data-card h3 {
    color: #1f2937;
    margin-bottom: 1.5rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.data-table {
    width: 100%;
    border-collapse: collapse;
}

.data-table th {
    background: #f9fafb;
    padding: 1rem;
    text-align: left;
    font-weight: 600;
    color: #374151;
    border-bottom: 1px solid #e5e7eb;
}

.data-table td {
    padding: 1rem;
    border-bottom: 1px solid #f3f4f6;
}

.data-table tr:hover {
    background: #f9fafb;
}

.stock-badge {
    padding: 0.25rem 0.75rem;
    border-radius: 12px;
    font-size: 0.75rem;
    font-weight: 600;
}

.out-of-stock {
    background: #fee2e2;
    color: #b91c1c;
}

.low-stock {
    background: #fef3c7;
    color: #92400e;
}

.in-stock {
    background: #dcfce7;
    color: #166534;
}

.rating {
    color: #f59e0b;
}

.chart-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 2rem;
    margin-bottom: 2rem;
}

@media (max-width: 1024px) {
    .chart-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<div class="product-analytics">
    <!-- Header -->
    <div class="analytics-header">
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <div>
                <h1>📊 Product Analytics</h1>
                <p style="color: #6b7280; margin: 0;">Comprehensive product performance insights</p>
            </div>
            <div>
                <button onclick="exportData()" style="padding: 0.5rem 1rem; background: #10b981; color: white; border: none; border-radius: 6px; cursor: pointer;">
                    <i class="fas fa-download"></i> Export Report
                </button>
            </div>
        </div>
        
        <!-- Filters -->
        <div class="filters">
            <div>
                <label>Date Range:</label>
                <input type="date" id="start_date" value="<?php echo $start_date; ?>" style="padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 6px;">
                <span>to</span>
                <input type="date" id="end_date" value="<?php echo $end_date; ?>" style="padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 6px;">
            </div>
            
            <select id="category_filter" class="filter-select">
                <option value="">All Categories</option>
                <?php foreach ($categories as $category): ?>
                    <option value="<?php echo $category['id']; ?>" <?php echo $category_id == $category['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($category['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            
            <select id="vendor_filter" class="filter-select">
                <option value="">All Vendors</option>
                <?php foreach ($vendors as $vendor): ?>
                    <option value="<?php echo $vendor['id']; ?>" <?php echo $vendor_id == $vendor['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($vendor['store_name'] ?: $vendor['username']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            
            <button onclick="applyFilters()" style="padding: 0.5rem 1rem; background: #10b981; color: white; border: none; border-radius: 6px; cursor: pointer;">
                <i class="fas fa-filter"></i> Apply Filters
            </button>
        </div>
    </div>

    <!-- Key Metrics -->
    <div class="metrics-grid">
        <div class="metric-card">
            <div class="metric-value"><?php echo number_format($product_overview['total_products']); ?></div>
            <div class="metric-label">Total Products</div>
        </div>
        
        <div class="metric-card">
            <div class="metric-value"><?php echo number_format($product_overview['total_inventory']); ?></div>
            <div class="metric-label">Total Inventory</div>
        </div>
        
        <div class="metric-card">
            <div class="metric-value"><?php echo formatCurrency($product_overview['avg_price']); ?></div>
            <div class="metric-label">Average Price</div>
        </div>
        
        <div class="metric-card">
            <div class="metric-value"><?php echo number_format($product_overview['out_of_stock']); ?></div>
            <div class="metric-label">Out of Stock Products</div>
        </div>
    </div>

    <!-- Charts -->
    <div class="chart-grid">
        <!-- Category Performance -->
        <div class="data-card">
            <h3><i class="fas fa-th-large"></i> Category Performance</h3>
            <canvas id="categoryChart" width="400" height="300"></canvas>
        </div>
        
        <!-- Price Range Analysis -->
        <div class="data-card">
            <h3><i class="fas fa-dollar-sign"></i> Price Range Analysis</h3>
            <canvas id="priceRangeChart" width="400" height="300"></canvas>
        </div>
    </div>

    <!-- Top Selling Products -->
    <div class="data-card">
        <h3><i class="fas fa-star"></i> Top Selling Products</h3>
        <div style="overflow-x: auto;">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Category</th>
                        <th>Vendor</th>
                        <th>Price</th>
                        <th>Sold</th>
                        <th>Revenue</th>
                        <th>Rating</th>
                        <th>Stock</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($top_products as $product): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($product['name']); ?></td>
                            <td><?php echo htmlspecialchars($product['category_name']); ?></td>
                            <td><?php echo htmlspecialchars($product['store_name'] ?: $product['vendor_name']); ?></td>
                            <td><?php echo formatCurrency($product['price']); ?></td>
                            <td><?php echo number_format($product['total_sold'] ?? 0); ?></td>
                            <td><?php echo formatCurrency($product['revenue'] ?? 0); ?></td>
                            <td class="rating">
                                <?php 
                                    $rating = round($product['avg_rating'] ?? 0);
                                    for ($i = 1; $i <= 5; $i++) {
                                        echo $i <= $rating ? '<i class="fas fa-star"></i>' : '<i class="far fa-star"></i>';
                                    }
                                    echo ' (' . ($product['review_count'] ?? 0) . ')';
                                ?>
                            </td>
                            <td>
                                <?php if ($product['stock_quantity'] == 0): ?>
                                    <span class="stock-badge out-of-stock">Out of Stock</span>
                                <?php elseif ($product['stock_quantity'] <= 5): ?>
                                    <span class="stock-badge low-stock">Low: <?php echo $product['stock_quantity']; ?></span>
                                <?php else: ?>
                                    <span class="stock-badge in-stock"><?php echo $product['stock_quantity']; ?></span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Product Conversion -->
    <div class="data-card">
        <h3><i class="fas fa-exchange-alt"></i> Product View to Purchase Conversion</h3>
        <div style="overflow-x: auto;">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Views</th>
                        <th>Purchases</th>
                        <th>Conversion Rate</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($product_conversion as $product): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($product['name']); ?></td>
                            <td><?php echo number_format($product['view_count']); ?></td>
                            <td><?php echo number_format($product['purchase_count']); ?></td>
                            <td><?php echo number_format($product['conversion_rate'], 2); ?>%</td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Low Stock Products -->
    <div class="data-card">
        <h3><i class="fas fa-exclamation-triangle"></i> Low Stock Products</h3>
        <div style="overflow-x: auto;">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Category</th>
                        <th>Vendor</th>
                        <th>Price</th>
                        <th>Stock</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($low_stock as $product): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($product['name']); ?></td>
                            <td><?php echo htmlspecialchars($product['category_name']); ?></td>
                            <td><?php echo htmlspecialchars($product['store_name'] ?: $product['vendor_name']); ?></td>
                            <td><?php echo formatCurrency($product['price']); ?></td>
                            <td>
                                <span class="stock-badge low-stock"><?php echo $product['stock_quantity']; ?></span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Category Performance Chart
const categoryData = <?php echo json_encode($category_performance); ?>;
const categoryCtx = document.getElementById('categoryChart').getContext('2d');

new Chart(categoryCtx, {
    type: 'bar',
    data: {
        labels: categoryData.map(item => item.name),
        datasets: [{
            label: 'Total Sold',
            data: categoryData.map(item => item.total_sold),
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

// Price Range Chart
const priceData = <?php echo json_encode($price_ranges); ?>;
const priceCtx = document.getElementById('priceRangeChart').getContext('2d');

new Chart(priceCtx, {
    type: 'pie',
    data: {
        labels: priceData.map(item => item.price_range),
        datasets: [{
            data: priceData.map(item => item.product_count),
            backgroundColor: ['#10b981', '#3b82f6', '#f59e0b', '#ef4444', '#8b5cf6']
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false
    }
});

function applyFilters() {
    const startDate = document.getElementById('start_date').value;
    const endDate = document.getElementById('end_date').value;
    const categoryId = document.getElementById('category_filter').value;
    const vendorId = document.getElementById('vendor_filter').value;
    
    let url = `?start_date=${startDate}&end_date=${endDate}`;
    if (categoryId) url += `&category_id=${categoryId}`;
    if (vendorId) url += `&vendor_id=${vendorId}`;
    
    window.location.href = url;
}

function exportData() {
    const startDate = document.getElementById('start_date').value;
    const endDate = document.getElementById('end_date').value;
    const categoryId = document.getElementById('category_filter').value;
    const vendorId = document.getElementById('vendor_filter').value;
    
    let url = `export-product-analytics.php?start_date=${startDate}&end_date=${endDate}&format=csv`;
    if (categoryId) url += `&category_id=${categoryId}`;
    if (vendorId) url += `&vendor_id=${vendorId}`;
    
    window.open(url);
}
</script>

<?php require_once 'includes/admin_footer.php'; ?>
