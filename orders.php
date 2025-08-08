<?php
/**
 * Customer Orders Page
 * View order history and track orders
 */

require_once 'config/config.php';

$page_title = 'My Orders';

// Require login
requireLogin();

$customer_id = $_SESSION['user_id'];

// Get filter parameters
$status_filter = sanitizeInput($_GET['status'] ?? '');
$date_filter = sanitizeInput($_GET['date'] ?? '');
$search = sanitizeInput($_GET['search'] ?? '');

// Build WHERE clause
$where_conditions = ["o.customer_id = ?"];
$params = [$customer_id];

if ($status_filter && $status_filter !== 'all') {
    $where_conditions[] = "o.status = ?";
    $params[] = $status_filter;
}

if ($date_filter) {
    switch ($date_filter) {
        case 'last_30_days':
            $where_conditions[] = "o.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
            break;
        case 'last_3_months':
            $where_conditions[] = "o.created_at >= DATE_SUB(NOW(), INTERVAL 3 MONTH)";
            break;
        case 'last_year':
            $where_conditions[] = "o.created_at >= DATE_SUB(NOW(), INTERVAL 1 YEAR)";
            break;
    }
}

if ($search) {
    $where_conditions[] = "(o.order_number LIKE ? OR p.name LIKE ?)";
    $search_term = "%$search%";
    $params[] = $search_term;
    $params[] = $search_term;
}

$where_clause = implode(' AND ', $where_conditions);

// Get orders with items
$orders_sql = "
    SELECT o.*, 
           COUNT(oi.id) as item_count,
           GROUP_CONCAT(DISTINCT p.name ORDER BY p.name SEPARATOR ', ') as product_names,
           GROUP_CONCAT(DISTINCT u.username ORDER BY u.username SEPARATOR ', ') as vendor_names,
           MIN(p.image_url) as first_product_image
    FROM orders o
    LEFT JOIN order_items oi ON o.id = oi.order_id
    LEFT JOIN products p ON oi.product_id = p.id
    LEFT JOIN users u ON p.vendor_id = u.id
    WHERE $where_clause
    GROUP BY o.id
    ORDER BY o.created_at DESC
    LIMIT 50
";

$orders = $database->fetchAll($orders_sql, $params);

// Get order statistics
$stats_sql = "
    SELECT 
        COUNT(*) as total_orders,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_orders,
        SUM(CASE WHEN status = 'processing' THEN 1 ELSE 0 END) as processing_orders,
        SUM(CASE WHEN status = 'shipped' THEN 1 ELSE 0 END) as shipped_orders,
        SUM(CASE WHEN status = 'delivered' THEN 1 ELSE 0 END) as delivered_orders,
        SUM(total_amount) as total_spent
    FROM orders 
    WHERE customer_id = ?
";

$stats = $database->fetch($stats_sql, [$customer_id]);

include 'includes/header.php';
?>

<div class="container">
    <div class="row">
        <div class="col-12">
            <!-- Orders Header -->
            <div style="background: white; padding: 2rem; border-radius: 10px; margin-bottom: 2rem; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                    <h1 style="margin: 0; color: #374151;">
                        <i class="fas fa-shopping-bag"></i> My Orders
                    </h1>
                    <div style="display: flex; gap: 1rem; align-items: center;">
                        <span style="color: #666; font-size: 0.9rem;">
                            <?php echo $stats['total_orders']; ?> total orders
                        </span>
                        <a href="products.php" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Shop More
                        </a>
                    </div>
                </div>

                <!-- Order Statistics -->
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 1rem; margin-bottom: 2rem;">
                    <div style="text-align: center; padding: 1rem; background: #f8fafc; border-radius: 8px;">
                        <div style="font-size: 1.5rem; font-weight: bold; color: #ffc107;"><?php echo $stats['pending_orders']; ?></div>
                        <div style="color: #666; font-size: 0.9rem;">Pending</div>
                    </div>
                    <div style="text-align: center; padding: 1rem; background: #f8fafc; border-radius: 8px;">
                        <div style="font-size: 1.5rem; font-weight: bold; color: #007bff;"><?php echo $stats['processing_orders']; ?></div>
                        <div style="color: #666; font-size: 0.9rem;">Processing</div>
                    </div>
                    <div style="text-align: center; padding: 1rem; background: #f8fafc; border-radius: 8px;">
                        <div style="font-size: 1.5rem; font-weight: bold; color: #17a2b8;"><?php echo $stats['shipped_orders']; ?></div>
                        <div style="color: #666; font-size: 0.9rem;">Shipped</div>
                    </div>
                    <div style="text-align: center; padding: 1rem; background: #f8fafc; border-radius: 8px;">
                        <div style="font-size: 1.5rem; font-weight: bold; color: #28a745;"><?php echo $stats['delivered_orders']; ?></div>
                        <div style="color: #666; font-size: 0.9rem;">Delivered</div>
                    </div>
                    <div style="text-align: center; padding: 1rem; background: #f8fafc; border-radius: 8px;">
                        <div style="font-size: 1.5rem; font-weight: bold; color: #6f42c1;">RWF <?php echo number_format($stats['total_spent']); ?></div>
                        <div style="color: #666; font-size: 0.9rem;">Total Spent</div>
                    </div>
                </div>

                <!-- Filters -->
                <form method="GET" style="display: flex; gap: 1rem; flex-wrap: wrap; align-items: center;">
                    <div>
                        <select name="status" class="form-control" style="min-width: 150px;">
                            <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>All Orders</option>
                            <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="processing" <?php echo $status_filter === 'processing' ? 'selected' : ''; ?>>Processing</option>
                            <option value="shipped" <?php echo $status_filter === 'shipped' ? 'selected' : ''; ?>>Shipped</option>
                            <option value="delivered" <?php echo $status_filter === 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                            <option value="cancelled" <?php echo $status_filter === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                        </select>
                    </div>
                    <div>
                        <select name="date" class="form-control" style="min-width: 150px;">
                            <option value="" <?php echo $date_filter === '' ? 'selected' : ''; ?>>All Time</option>
                            <option value="last_30_days" <?php echo $date_filter === 'last_30_days' ? 'selected' : ''; ?>>Last 30 Days</option>
                            <option value="last_3_months" <?php echo $date_filter === 'last_3_months' ? 'selected' : ''; ?>>Last 3 Months</option>
                            <option value="last_year" <?php echo $date_filter === 'last_year' ? 'selected' : ''; ?>>Last Year</option>
                        </select>
                    </div>
                    <div style="flex: 1; min-width: 200px;">
                        <input type="text" name="search" class="form-control" placeholder="Search orders..." value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    <button type="submit" class="btn btn-outline-primary">
                        <i class="fas fa-filter"></i> Filter
                    </button>
                    <a href="orders.php" class="btn btn-outline-secondary">
                        <i class="fas fa-times"></i> Clear
                    </a>
                </form>
            </div>

            <?php if (empty($orders)): ?>
                <!-- No Orders -->
                <div style="text-align: center; padding: 4rem 2rem; background: white; border-radius: 10px;">
                    <i class="fas fa-shopping-bag" style="font-size: 4rem; color: #007bff; margin-bottom: 1rem; opacity: 0.3;"></i>
                    <h3 style="color: #374151; margin-bottom: 1rem;">No orders found</h3>
                    <p style="color: #6b7280; margin-bottom: 2rem;">
                        <?php if ($status_filter || $date_filter || $search): ?>
                            No orders match your current filters. Try adjusting your search criteria.
                        <?php else: ?>
                            You haven't placed any orders yet. Start shopping to see your orders here!
                        <?php endif; ?>
                    </p>
                    <div style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
                        <a href="products.php" class="btn btn-primary">
                            <i class="fas fa-shopping-bag"></i> Start Shopping
                        </a>
                        <?php if ($status_filter || $date_filter || $search): ?>
                            <a href="orders.php" class="btn btn-outline-secondary">
                                <i class="fas fa-times"></i> Clear Filters
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php else: ?>
                <!-- Orders List -->
                <div style="display: flex; flex-direction: column; gap: 1.5rem;">
                    <?php foreach ($orders as $order): ?>
                        <div class="order-card" style="background: white; border-radius: 10px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                            <!-- Order Header -->
                            <div style="background: #f8fafc; padding: 1.5rem; border-bottom: 1px solid #e5e7eb;">
                                <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
                                    <div>
                                        <h3 style="margin: 0 0 0.5rem 0; color: #374151;">
                                            Order #<?php echo htmlspecialchars($order['order_number']); ?>
                                        </h3>
                                        <p style="margin: 0; color: #6b7280; font-size: 0.9rem;">
                                            Placed on <?php echo date('F j, Y', strtotime($order['created_at'])); ?> • 
                                            <?php echo $order['item_count']; ?> item<?php echo $order['item_count'] !== '1' ? 's' : ''; ?>
                                        </p>
                                    </div>
                                    <div style="text-align: right;">
                                        <div style="font-size: 1.25rem; font-weight: bold; color: #374151; margin-bottom: 0.5rem;">
                                            RWF <?php echo number_format($order['total_amount']); ?>
                                        </div>
                                        <?php
                                        $status_colors = [
                                            'pending' => '#ffc107',
                                            'processing' => '#007bff',
                                            'shipped' => '#17a2b8',
                                            'delivered' => '#28a745',
                                            'cancelled' => '#dc3545'
                                        ];
                                        $status_color = $status_colors[$order['status']] ?? '#6c757d';
                                        ?>
                                        <span style="background: <?php echo $status_color; ?>; color: white; padding: 4px 12px; border-radius: 20px; font-size: 0.8rem; font-weight: bold;">
                                            <?php echo ucfirst($order['status']); ?>
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <!-- Order Content -->
                            <div style="padding: 1.5rem;">
                                <div style="display: flex; gap: 1.5rem; align-items: center;">
                                    <!-- Product Image -->
                                    <div style="flex-shrink: 0;">
                                        <div style="width: 80px; height: 80px; border-radius: 8px; overflow: hidden; background: #f3f4f6;">
                                            <?php if ($order['first_product_image']): ?>
                                                <img src="<?php echo htmlspecialchars($order['first_product_image']); ?>" 
                                                     alt="Order items"
                                                     style="width: 100%; height: 100%; object-fit: cover;">
                                            <?php else: ?>
                                                <div style="width: 100%; height: 100%; display: flex; align-items: center; justify-content: center;">
                                                    <i class="fas fa-box" style="color: #9ca3af; font-size: 1.5rem;"></i>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <!-- Order Details -->
                                    <div style="flex: 1;">
                                        <h4 style="margin: 0 0 0.5rem 0; color: #374151;">
                                            <?php 
                                            $product_names = explode(', ', $order['product_names']);
                                            if (count($product_names) > 2) {
                                                echo htmlspecialchars($product_names[0]) . ', ' . htmlspecialchars($product_names[1]) . ' and ' . (count($product_names) - 2) . ' more';
                                            } else {
                                                echo htmlspecialchars($order['product_names']);
                                            }
                                            ?>
                                        </h4>
                                        <p style="margin: 0 0 0.5rem 0; color: #6b7280; font-size: 0.9rem;">
                                            <strong>Vendors:</strong> <?php echo htmlspecialchars($order['vendor_names']); ?>
                                        </p>
                                        <p style="margin: 0; color: #6b7280; font-size: 0.9rem;">
                                            <strong>Payment:</strong> <?php echo ucwords(str_replace('_', ' ', $order['payment_method'])); ?> • 
                                            <span style="color: <?php echo $order['payment_status'] === 'paid' ? '#28a745' : '#ffc107'; ?>;">
                                                <?php echo ucfirst($order['payment_status']); ?>
                                            </span>
                                        </p>
                                    </div>

                                    <!-- Actions -->
                                    <div style="display: flex; flex-direction: column; gap: 0.5rem; align-items: flex-end;">
                                        <a href="invoice-improved.php?order=<?php echo $order['id']; ?>" class="btn btn-outline-primary btn-sm">
                                            <i class="fas fa-file-invoice"></i> Invoice
                                        </a>
                                        <a href="order-details.php?id=<?php echo $order['id']; ?>" class="btn btn-outline-primary btn-sm">
                                            <i class="fas fa-eye"></i> View Details
                                        </a>
                                        <?php if ($order['status'] === 'delivered'): ?>
                                            <button onclick="reorderItems(<?php echo $order['id']; ?>)" class="btn btn-outline-success btn-sm">
                                                <i class="fas fa-redo"></i> Reorder
                                            </button>
                                        <?php endif; ?>
                                        <?php if (in_array($order['status'], ['shipped', 'delivered']) && $order['tracking_number']): ?>
                                            <button onclick="trackOrder('<?php echo $order['tracking_number']; ?>')" class="btn btn-outline-info btn-sm">
                                                <i class="fas fa-truck"></i> Track
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.btn {
    padding: 0.5rem 1rem;
    border-radius: 6px;
    text-decoration: none;
    display: inline-block;
    font-weight: 500;
    transition: all 0.2s;
    border: 2px solid transparent;
    cursor: pointer;
}

.btn-primary {
    background: #007bff;
    color: white;
    border-color: #007bff;
}

.btn-primary:hover {
    background: #0056b3;
    border-color: #0056b3;
}

.btn-outline-primary {
    background: transparent;
    color: #007bff;
    border-color: #007bff;
}

.btn-outline-primary:hover {
    background: #007bff;
    color: white;
}

.btn-outline-secondary {
    background: transparent;
    color: #6b7280;
    border-color: #6b7280;
}

.btn-outline-secondary:hover {
    background: #6b7280;
    color: white;
}

.btn-outline-success {
    background: transparent;
    color: #28a745;
    border-color: #28a745;
}

.btn-outline-success:hover {
    background: #28a745;
    color: white;
}

.btn-outline-info {
    background: transparent;
    color: #17a2b8;
    border-color: #17a2b8;
}

.btn-outline-info:hover {
    background: #17a2b8;
    color: white;
}

.btn-sm {
    padding: 0.25rem 0.5rem;
    font-size: 0.875rem;
}

.form-control {
    padding: 0.5rem;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    font-size: 0.9rem;
}

.form-control:focus {
    outline: none;
    border-color: #007bff;
    box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.1);
}

.order-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 20px rgba(0,0,0,0.15);
    transition: all 0.3s;
}
</style>

<script>
function reorderItems(orderId) {
    if (confirm('Add all items from this order to your cart?')) {
        fetch('api/cart.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'reorder',
                order_id: orderId
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification('Items added to cart!', 'success');
                setTimeout(() => {
                    window.location.href = 'cart-enhanced.php';
                }, 1000);
            } else {
                showNotification(data.message || 'Error adding items to cart', 'error');
            }
        })
        .catch(error => {
            showNotification('Error processing reorder', 'error');
        });
    }
}

function trackOrder(trackingNumber) {
    // Open tracking in new window/tab
    window.open(`order-tracking.php?tracking=${trackingNumber}`, '_blank');
}

function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `alert alert-${type === 'error' ? 'danger' : type === 'success' ? 'success' : 'info'}`;
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 9999;
        min-width: 300px;
        animation: slideIn 0.3s ease-out;
        padding: 1rem;
        border-radius: 6px;
        color: white;
        background: ${type === 'error' ? '#dc3545' : type === 'success' ? '#28a745' : '#007bff'};
    `;
    notification.innerHTML = `
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <span>${message}</span>
            <button type="button" onclick="this.parentElement.parentElement.remove()" style="background: none; border: none; color: white; font-size: 1.2rem; cursor: pointer;">
                ×
            </button>
        </div>
    `;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        if (notification.parentElement) {
            notification.remove();
        }
    }, 5000);
}

// Add CSS animation
const style = document.createElement('style');
style.textContent = `
    @keyframes slideIn {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
`;
document.head.appendChild(style);
</script>

<?php include 'includes/footer.php'; ?>
