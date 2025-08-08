<?php
/**
 * Analytics API - MarketHub
 * Provides analytics data for dashboards
 */

require_once '../config/config.php';

header('Content-Type: application/json');

// Check authentication
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Authentication required']);
    exit;
}

$action = $_GET['action'] ?? '';
$user_type = $_SESSION['user_type'];
$user_id = $_SESSION['user_id'];

// Get date range
$start_date = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
$end_date = $_GET['end_date'] ?? date('Y-m-d');

try {
    switch ($action) {
        case 'overview':
            echo json_encode(getOverviewAnalytics($user_type, $user_id, $start_date, $end_date));
            break;
            
        case 'sales_trend':
            echo json_encode(getSalesTrend($user_type, $user_id, $start_date, $end_date));
            break;
            
        case 'product_performance':
            echo json_encode(getProductPerformance($user_type, $user_id, $start_date, $end_date));
            break;
            
        case 'customer_analytics':
            echo json_encode(getCustomerAnalytics($user_type, $user_id, $start_date, $end_date));
            break;
            
        case 'category_performance':
            echo json_encode(getCategoryPerformance($user_type, $user_id, $start_date, $end_date));
            break;
            
        case 'inventory_status':
            echo json_encode(getInventoryStatus($user_type, $user_id));
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Invalid action']);
    }
} catch (Exception $e) {
    error_log("Analytics API error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
}

function getOverviewAnalytics($user_type, $user_id, $start_date, $end_date) {
    global $database;
    
    if ($user_type === 'admin') {
        // Admin overview
        $total_revenue = $database->fetch("
            SELECT COALESCE(SUM(total_amount), 0) as revenue 
            FROM orders 
            WHERE status IN ('completed', 'delivered') AND created_at BETWEEN ? AND ?
        ", [$start_date, $end_date])['revenue'];
        
        $total_orders = $database->fetch("
            SELECT COUNT(*) as count 
            FROM orders 
            WHERE created_at BETWEEN ? AND ?
        ", [$start_date, $end_date])['count'];
        
        $active_customers = $database->fetch("
            SELECT COUNT(DISTINCT customer_id) as count 
            FROM orders 
            WHERE created_at BETWEEN ? AND ?
        ", [$start_date, $end_date])['count'];
        
        $total_vendors = $database->fetch("
            SELECT COUNT(*) as count 
            FROM users 
            WHERE user_type = 'vendor' AND status = 'active'
        ")['count'];
        
        return [
            'total_revenue' => $total_revenue,
            'total_orders' => $total_orders,
            'active_customers' => $active_customers,
            'total_vendors' => $total_vendors,
            'avg_order_value' => $total_orders > 0 ? $total_revenue / $total_orders : 0
        ];
        
    } else if ($user_type === 'vendor') {
        // Vendor overview
        $total_revenue = $database->fetch("
            SELECT COALESCE(SUM(oi.quantity * oi.unit_price), 0) as revenue
            FROM order_items oi
            JOIN products p ON oi.product_id = p.id
            JOIN orders o ON oi.order_id = o.id
            WHERE p.vendor_id = ? AND o.status IN ('completed', 'delivered') AND o.created_at BETWEEN ? AND ?
        ", [$user_id, $start_date, $end_date])['revenue'];
        
        $total_orders = $database->fetch("
            SELECT COUNT(DISTINCT o.id) as count 
            FROM orders o
            JOIN order_items oi ON o.id = oi.order_id
            JOIN products p ON oi.product_id = p.id
            WHERE p.vendor_id = ? AND o.created_at BETWEEN ? AND ?
        ", [$user_id, $start_date, $end_date])['count'];
        
        $unique_customers = $database->fetch("
            SELECT COUNT(DISTINCT o.customer_id) as count
            FROM orders o
            JOIN order_items oi ON o.id = oi.order_id
            JOIN products p ON oi.product_id = p.id
            WHERE p.vendor_id = ? AND o.created_at BETWEEN ? AND ?
        ", [$user_id, $start_date, $end_date])['count'];
        
        $total_products = $database->fetch("
            SELECT COUNT(*) as count 
            FROM products 
            WHERE vendor_id = ? AND status = 'active'
        ", [$user_id])['count'];
        
        return [
            'total_revenue' => $total_revenue,
            'total_orders' => $total_orders,
            'unique_customers' => $unique_customers,
            'total_products' => $total_products,
            'avg_order_value' => $total_orders > 0 ? $total_revenue / $total_orders : 0
        ];
    }
    
    return ['error' => 'Unauthorized'];
}

function getSalesTrend($user_type, $user_id, $start_date, $end_date) {
    global $database;
    
    if ($user_type === 'admin') {
        return $database->fetchAll("
            SELECT DATE(created_at) as date, COUNT(*) as orders, SUM(total_amount) as revenue
            FROM orders
            WHERE created_at BETWEEN ? AND ?
            GROUP BY DATE(created_at)
            ORDER BY date
        ", [$start_date, $end_date]);
        
    } else if ($user_type === 'vendor') {
        return $database->fetchAll("
            SELECT DATE(o.created_at) as date, COUNT(DISTINCT o.id) as orders, 
                   SUM(oi.quantity * oi.unit_price) as revenue
            FROM orders o
            JOIN order_items oi ON o.id = oi.order_id
            JOIN products p ON oi.product_id = p.id
            WHERE p.vendor_id = ? AND o.created_at BETWEEN ? AND ?
            GROUP BY DATE(o.created_at)
            ORDER BY date
        ", [$user_id, $start_date, $end_date]);
    }
    
    return [];
}

function getProductPerformance($user_type, $user_id, $start_date, $end_date) {
    global $database;
    
    if ($user_type === 'admin') {
        return $database->fetchAll("
            SELECT p.name, p.price, COUNT(oi.id) as order_count, SUM(oi.quantity) as total_sold, 
                   SUM(oi.quantity * oi.unit_price) as revenue, u.username as vendor_name
            FROM products p
            JOIN order_items oi ON p.id = oi.product_id
            JOIN orders o ON oi.order_id = o.id
            JOIN users u ON p.vendor_id = u.id
            WHERE o.created_at BETWEEN ? AND ?
            GROUP BY p.id
            ORDER BY total_sold DESC
            LIMIT 20
        ", [$start_date, $end_date]);
        
    } else if ($user_type === 'vendor') {
        return $database->fetchAll("
            SELECT p.id, p.name, p.price, p.stock_quantity, 
                   COUNT(oi.id) as order_count, SUM(oi.quantity) as total_sold, 
                   SUM(oi.quantity * oi.unit_price) as revenue,
                   AVG(pr.rating) as avg_rating, COUNT(pr.id) as review_count
            FROM products p
            LEFT JOIN order_items oi ON p.id = oi.product_id
            LEFT JOIN orders o ON oi.order_id = o.id AND o.created_at BETWEEN ? AND ?
            LEFT JOIN product_reviews pr ON p.id = pr.product_id
            WHERE p.vendor_id = ? AND p.status = 'active'
            GROUP BY p.id
            ORDER BY revenue DESC
        ", [$start_date, $end_date, $user_id]);
    }
    
    return [];
}

function getCustomerAnalytics($user_type, $user_id, $start_date, $end_date) {
    global $database;
    
    if ($user_type === 'admin') {
        $new_customers = $database->fetch("
            SELECT COUNT(*) as count 
            FROM users 
            WHERE user_type = 'customer' AND created_at BETWEEN ? AND ?
        ", [$start_date, $end_date])['count'];
        
        $repeat_customers = $database->fetch("
            SELECT COUNT(DISTINCT customer_id) as count
            FROM orders
            WHERE customer_id IN (
                SELECT customer_id 
                FROM orders 
                GROUP BY customer_id 
                HAVING COUNT(*) > 1
            ) AND created_at BETWEEN ? AND ?
        ", [$start_date, $end_date])['count'];
        
        $top_customers = $database->fetchAll("
            SELECT u.first_name, u.last_name, u.email, COUNT(o.id) as order_count, 
                   SUM(o.total_amount) as total_spent
            FROM users u
            JOIN orders o ON u.id = o.customer_id
            WHERE o.created_at BETWEEN ? AND ?
            GROUP BY u.id
            ORDER BY total_spent DESC
            LIMIT 10
        ", [$start_date, $end_date]);
        
        return [
            'new_customers' => $new_customers,
            'repeat_customers' => $repeat_customers,
            'top_customers' => $top_customers
        ];
        
    } else if ($user_type === 'vendor') {
        $top_customers = $database->fetchAll("
            SELECT u.first_name, u.last_name, u.email, COUNT(DISTINCT o.id) as order_count, 
                   SUM(oi.quantity * oi.unit_price) as total_spent
            FROM users u
            JOIN orders o ON u.id = o.customer_id
            JOIN order_items oi ON o.id = oi.order_id
            JOIN products p ON oi.product_id = p.id
            WHERE p.vendor_id = ? AND o.created_at BETWEEN ? AND ?
            GROUP BY u.id
            ORDER BY total_spent DESC
            LIMIT 10
        ", [$user_id, $start_date, $end_date]);
        
        return [
            'top_customers' => $top_customers
        ];
    }
    
    return [];
}

function getCategoryPerformance($user_type, $user_id, $start_date, $end_date) {
    global $database;
    
    if ($user_type === 'admin') {
        return $database->fetchAll("
            SELECT c.name, COUNT(oi.id) as order_count, SUM(oi.quantity) as items_sold, 
                   SUM(oi.quantity * oi.unit_price) as revenue
            FROM categories c
            JOIN products p ON c.id = p.category_id
            JOIN order_items oi ON p.id = oi.product_id
            JOIN orders o ON oi.order_id = o.id
            WHERE o.created_at BETWEEN ? AND ?
            GROUP BY c.id
            ORDER BY revenue DESC
        ", [$start_date, $end_date]);
        
    } else if ($user_type === 'vendor') {
        return $database->fetchAll("
            SELECT c.name, COUNT(oi.id) as order_count, SUM(oi.quantity) as items_sold, 
                   SUM(oi.quantity * oi.unit_price) as revenue
            FROM categories c
            JOIN products p ON c.id = p.category_id
            JOIN order_items oi ON p.id = oi.product_id
            JOIN orders o ON oi.order_id = o.id
            WHERE p.vendor_id = ? AND o.created_at BETWEEN ? AND ?
            GROUP BY c.id
            ORDER BY revenue DESC
        ", [$user_id, $start_date, $end_date]);
    }
    
    return [];
}

function getInventoryStatus($user_type, $user_id) {
    global $database;
    
    if ($user_type === 'admin') {
        return $database->fetchAll("
            SELECT 
                SUM(CASE WHEN stock_quantity = 0 THEN 1 ELSE 0 END) as out_of_stock,
                SUM(CASE WHEN stock_quantity BETWEEN 1 AND 5 THEN 1 ELSE 0 END) as low_stock,
                SUM(CASE WHEN stock_quantity > 5 THEN 1 ELSE 0 END) as in_stock,
                COUNT(*) as total_products
            FROM products
            WHERE status = 'active'
        ");
        
    } else if ($user_type === 'vendor') {
        return $database->fetchAll("
            SELECT 
                SUM(CASE WHEN stock_quantity = 0 THEN 1 ELSE 0 END) as out_of_stock,
                SUM(CASE WHEN stock_quantity BETWEEN 1 AND 5 THEN 1 ELSE 0 END) as low_stock,
                SUM(CASE WHEN stock_quantity > 5 THEN 1 ELSE 0 END) as in_stock,
                COUNT(*) as total_products
            FROM products
            WHERE vendor_id = ? AND status = 'active'
        ", [$user_id]);
    }
    
    return [];
}
?>
