<?php
/**
 * Export Analytics Data - MarketHub Admin
 */

require_once '../config/config.php';

// Check if user is admin
if (!isLoggedIn() || $_SESSION['user_type'] !== 'admin') {
    http_response_code(403);
    exit('Access denied');
}

$format = $_GET['format'] ?? 'csv';
$report_type = $_GET['type'] ?? 'overview';
$start_date = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
$end_date = $_GET['end_date'] ?? date('Y-m-d');

try {
    switch ($report_type) {
        case 'overview':
            exportOverviewReport($format, $start_date, $end_date);
            break;
        case 'sales':
            exportSalesReport($format, $start_date, $end_date);
            break;
        case 'products':
            exportProductReport($format, $start_date, $end_date);
            break;
        case 'customers':
            exportCustomerReport($format, $start_date, $end_date);
            break;
        case 'vendors':
            exportVendorReport($format, $start_date, $end_date);
            break;
        default:
            exportOverviewReport($format, $start_date, $end_date);
    }
} catch (Exception $e) {
    error_log("Export error: " . $e->getMessage());
    http_response_code(500);
    echo "Error generating report";
}

function exportOverviewReport($format, $start_date, $end_date) {
    global $database;
    
    // Get overview data
    $data = [
        ['Metric', 'Value', 'Period'],
        ['Report Period', "$start_date to $end_date", ''],
        ['', '', ''], // Empty row
    ];
    
    // Revenue metrics
    $revenue = $database->fetch("
        SELECT 
            COUNT(*) as total_orders,
            SUM(total_amount) as total_revenue,
            AVG(total_amount) as avg_order_value
        FROM orders 
        WHERE created_at BETWEEN ? AND ?
    ", [$start_date, $end_date]);
    
    $data[] = ['Total Orders', number_format($revenue['total_orders']), "$start_date to $end_date"];
    $data[] = ['Total Revenue', formatCurrency($revenue['total_revenue']), "$start_date to $end_date"];
    $data[] = ['Average Order Value', formatCurrency($revenue['avg_order_value']), "$start_date to $end_date"];
    
    // Customer metrics
    $customers = $database->fetch("
        SELECT 
            COUNT(DISTINCT customer_id) as active_customers,
            (SELECT COUNT(*) FROM users WHERE user_type = 'customer' AND created_at BETWEEN ? AND ?) as new_customers
        FROM orders 
        WHERE created_at BETWEEN ? AND ?
    ", [$start_date, $end_date, $start_date, $end_date]);
    
    $data[] = ['Active Customers', number_format($customers['active_customers']), "$start_date to $end_date"];
    $data[] = ['New Customers', number_format($customers['new_customers']), "$start_date to $end_date"];
    
    // Product metrics
    $products = $database->fetch("
        SELECT 
            COUNT(*) as total_products,
            SUM(CASE WHEN stock_quantity = 0 THEN 1 ELSE 0 END) as out_of_stock
        FROM products 
        WHERE status = 'active'
    ");
    
    $data[] = ['Total Products', number_format($products['total_products']), 'Current'];
    $data[] = ['Out of Stock Products', number_format($products['out_of_stock']), 'Current'];
    
    outputData($data, $format, "overview_report_$start_date" . "_to_$end_date");
}

function exportSalesReport($format, $start_date, $end_date) {
    global $database;
    
    // Daily sales data
    $sales = $database->fetchAll("
        SELECT 
            DATE(created_at) as date,
            COUNT(*) as orders,
            SUM(total_amount) as revenue,
            AVG(total_amount) as avg_order_value,
            COUNT(DISTINCT customer_id) as unique_customers
        FROM orders 
        WHERE created_at BETWEEN ? AND ?
        GROUP BY DATE(created_at)
        ORDER BY date
    ", [$start_date, $end_date]);
    
    $data = [
        ['Date', 'Orders', 'Revenue', 'Avg Order Value', 'Unique Customers']
    ];
    
    foreach ($sales as $row) {
        $data[] = [
            $row['date'],
            number_format($row['orders']),
            number_format($row['revenue'], 2),
            number_format($row['avg_order_value'], 2),
            number_format($row['unique_customers'])
        ];
    }
    
    outputData($data, $format, "sales_report_$start_date" . "_to_$end_date");
}

function exportProductReport($format, $start_date, $end_date) {
    global $database;
    
    // Product performance data
    $products = $database->fetchAll("
        SELECT 
            p.name, c.name as category, u.username as vendor,
            p.price, p.stock_quantity,
            COUNT(oi.id) as order_count,
            SUM(oi.quantity) as total_sold,
            SUM(oi.quantity * oi.unit_price) as revenue,
            AVG(pr.rating) as avg_rating,
            COUNT(pr.id) as review_count
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        LEFT JOIN users u ON p.vendor_id = u.id
        LEFT JOIN order_items oi ON p.id = oi.product_id
        LEFT JOIN orders o ON oi.order_id = o.id AND o.created_at BETWEEN ? AND ?
        LEFT JOIN product_reviews pr ON p.id = pr.product_id
        WHERE p.status = 'active'
        GROUP BY p.id
        ORDER BY total_sold DESC
    ", [$start_date, $end_date]);
    
    $data = [
        ['Product Name', 'Category', 'Vendor', 'Price', 'Stock', 'Orders', 'Sold', 'Revenue', 'Avg Rating', 'Reviews']
    ];
    
    foreach ($products as $row) {
        $data[] = [
            $row['name'],
            $row['category'],
            $row['vendor'],
            number_format($row['price'], 2),
            number_format($row['stock_quantity']),
            number_format($row['order_count'] ?? 0),
            number_format($row['total_sold'] ?? 0),
            number_format($row['revenue'] ?? 0, 2),
            number_format($row['avg_rating'] ?? 0, 2),
            number_format($row['review_count'] ?? 0)
        ];
    }
    
    outputData($data, $format, "product_report_$start_date" . "_to_$end_date");
}

function exportCustomerReport($format, $start_date, $end_date) {
    global $database;
    
    // Customer data
    $customers = $database->fetchAll("
        SELECT 
            u.first_name, u.last_name, u.email, u.phone,
            u.created_at as registration_date,
            COUNT(o.id) as total_orders,
            SUM(o.total_amount) as total_spent,
            AVG(o.total_amount) as avg_order_value,
            MAX(o.created_at) as last_order_date
        FROM users u
        LEFT JOIN orders o ON u.id = o.customer_id AND o.created_at BETWEEN ? AND ?
        WHERE u.user_type = 'customer'
        GROUP BY u.id
        ORDER BY total_spent DESC
    ", [$start_date, $end_date]);
    
    $data = [
        ['First Name', 'Last Name', 'Email', 'Phone', 'Registration Date', 'Total Orders', 'Total Spent', 'Avg Order Value', 'Last Order Date']
    ];
    
    foreach ($customers as $row) {
        $data[] = [
            $row['first_name'],
            $row['last_name'],
            $row['email'],
            $row['phone'],
            $row['registration_date'],
            number_format($row['total_orders'] ?? 0),
            number_format($row['total_spent'] ?? 0, 2),
            number_format($row['avg_order_value'] ?? 0, 2),
            $row['last_order_date'] ?? 'Never'
        ];
    }
    
    outputData($data, $format, "customer_report_$start_date" . "_to_$end_date");
}

function exportVendorReport($format, $start_date, $end_date) {
    global $database;
    
    // Vendor performance data
    $vendors = $database->fetchAll("
        SELECT 
            u.username, vs.store_name, u.email, u.phone,
            u.created_at as registration_date,
            COUNT(DISTINCT p.id) as total_products,
            COUNT(DISTINCT o.id) as total_orders,
            SUM(oi.quantity * oi.unit_price) as total_revenue,
            AVG(oi.unit_price) as avg_product_price,
            AVG(pr.rating) as avg_rating
        FROM users u
        LEFT JOIN vendor_stores vs ON u.id = vs.vendor_id
        LEFT JOIN products p ON u.id = p.vendor_id
        LEFT JOIN order_items oi ON p.id = oi.product_id
        LEFT JOIN orders o ON oi.order_id = o.id AND o.created_at BETWEEN ? AND ?
        LEFT JOIN product_reviews pr ON p.id = pr.product_id
        WHERE u.user_type = 'vendor'
        GROUP BY u.id
        ORDER BY total_revenue DESC
    ", [$start_date, $end_date]);
    
    $data = [
        ['Username', 'Store Name', 'Email', 'Phone', 'Registration Date', 'Total Products', 'Total Orders', 'Total Revenue', 'Avg Product Price', 'Avg Rating']
    ];
    
    foreach ($vendors as $row) {
        $data[] = [
            $row['username'],
            $row['store_name'] ?? '',
            $row['email'],
            $row['phone'],
            $row['registration_date'],
            number_format($row['total_products'] ?? 0),
            number_format($row['total_orders'] ?? 0),
            number_format($row['total_revenue'] ?? 0, 2),
            number_format($row['avg_product_price'] ?? 0, 2),
            number_format($row['avg_rating'] ?? 0, 2)
        ];
    }
    
    outputData($data, $format, "vendor_report_$start_date" . "_to_$end_date");
}

function outputData($data, $format, $filename) {
    if ($format === 'csv') {
        outputCSV($data, $filename);
    } elseif ($format === 'json') {
        outputJSON($data, $filename);
    } else {
        outputCSV($data, $filename);
    }
}

function outputCSV($data, $filename) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '.csv"');
    header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
    header('Pragma: public');
    
    $output = fopen('php://output', 'w');
    
    foreach ($data as $row) {
        fputcsv($output, $row);
    }
    
    fclose($output);
}

function outputJSON($data, $filename) {
    header('Content-Type: application/json');
    header('Content-Disposition: attachment; filename="' . $filename . '.json"');
    header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
    header('Pragma: public');
    
    // Convert to associative array
    $headers = array_shift($data);
    $json_data = [];
    
    foreach ($data as $row) {
        $json_data[] = array_combine($headers, $row);
    }
    
    echo json_encode($json_data, JSON_PRETTY_PRINT);
}
?>
