<?php
/**
 * MarketHub Order Management
 * Admin panel for managing all orders
 */

require_once '../config/config.php';

$page_title = 'Order Management';

// Check if user is admin
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    redirect('../login.php?error=access_denied');
}

$success_message = '';
$error_message = '';

// Handle order status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error_message = 'Invalid security token. Please try again.';
    } else {
        try {
            $order_id = intval($_POST['order_id'] ?? 0);
            $new_status = sanitizeInput($_POST['status'] ?? '');
            $tracking_number = sanitizeInput($_POST['tracking_number'] ?? '');
            
            if ($order_id <= 0) {
                throw new Exception('Invalid order ID.');
            }
            
            $valid_statuses = ['pending', 'processing', 'shipped', 'delivered', 'cancelled'];
            if (!in_array($new_status, $valid_statuses)) {
                throw new Exception('Invalid order status.');
            }
            
            // Update order status
            $update_sql = "UPDATE orders SET status = ?, updated_at = NOW()";
            $params = [$new_status];
            
            if (!empty($tracking_number) && in_array($new_status, ['shipped', 'delivered'])) {
                $update_sql .= ", tracking_number = ?";
                $params[] = $tracking_number;
            }
            
            if ($new_status === 'shipped') {
                $update_sql .= ", shipped_at = NOW()";
            } elseif ($new_status === 'delivered') {
                $update_sql .= ", delivered_at = NOW()";
            }
            
            $update_sql .= " WHERE id = ?";
            $params[] = $order_id;
            
            $database->execute($update_sql, $params);
            
            // Log activity
            logActivity($_SESSION['user_id'] ?? 0, 'order_status_updated', "Order #$order_id status changed to $new_status");
            
            $success_message = "Order status updated successfully!";
            
        } catch (Exception $e) {
            $error_message = $e->getMessage();
        }
    }
}

// Get filter parameters
$status_filter = sanitizeInput($_GET['status'] ?? '');
$payment_filter = sanitizeInput($_GET['payment'] ?? '');

// Build query
$where_conditions = ['1=1'];
$params = [];

if ($status_filter) {
    $where_conditions[] = "o.status = ?";
    $params[] = $status_filter;
}

if ($payment_filter) {
    $where_conditions[] = "o.payment_status = ?";
    $params[] = $payment_filter;
}

$where_clause = implode(' AND ', $where_conditions);

// Get orders
$orders = $database->fetchAll(
    "SELECT o.*, u.first_name, u.last_name, u.email,
            COUNT(oi.id) as item_count,
            GROUP_CONCAT(p.name SEPARATOR ', ') as product_names
     FROM orders o
     JOIN users u ON o.customer_id = u.id
     LEFT JOIN order_items oi ON o.id = oi.order_id
     LEFT JOIN products p ON oi.product_id = p.id
     WHERE $where_clause
     GROUP BY o.id
     ORDER BY o.created_at DESC
     LIMIT 50",
    $params
);

require_once 'includes/admin_header.php';
?>

<div class="content-header">
    <h1><i class="fas fa-shopping-cart"></i> Order Management</h1>
    <p>Manage customer orders and fulfillment</p>
</div>

<!-- Messages -->
<?php if ($success_message): ?>
    <div class="alert alert-success">
        <i class="fas fa-check-circle"></i>
        <?php echo htmlspecialchars($success_message); ?>
    </div>
<?php endif; ?>

<?php if ($error_message): ?>
    <div class="alert alert-error">
        <i class="fas fa-exclamation-circle"></i>
        <?php echo htmlspecialchars($error_message); ?>
    </div>
<?php endif; ?>

<!-- Statistics -->
<div class="stats-grid">
    <?php
    $order_stats = [
        'total' => count($orders),
        'pending' => count(array_filter($orders, fn($o) => $o['status'] === 'pending')),
        'processing' => count(array_filter($orders, fn($o) => $o['status'] === 'processing')),
        'shipped' => count(array_filter($orders, fn($o) => $o['status'] === 'shipped')),
        'delivered' => count(array_filter($orders, fn($o) => $o['status'] === 'delivered'))
    ];
    ?>
    
    <div class="stat-card">
        <div class="stat-icon" style="background: #2196F3;">
            <i class="fas fa-shopping-cart"></i>
        </div>
        <div class="stat-content">
            <h3><?php echo $order_stats['total']; ?></h3>
            <p>Total Orders</p>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon" style="background: #FF9800;">
            <i class="fas fa-clock"></i>
        </div>
        <div class="stat-content">
            <h3><?php echo $order_stats['pending']; ?></h3>
            <p>Pending Orders</p>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon" style="background: #9C27B0;">
            <i class="fas fa-cog"></i>
        </div>
        <div class="stat-content">
            <h3><?php echo $order_stats['processing']; ?></h3>
            <p>Processing</p>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon" style="background: #4CAF50;">
            <i class="fas fa-truck"></i>
        </div>
        <div class="stat-content">
            <h3><?php echo $order_stats['shipped']; ?></h3>
            <p>Shipped</p>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="dashboard-widget">
    <div class="widget-header">
        <h4><i class="fas fa-filter"></i> Filters</h4>
    </div>
    <div class="widget-content">
        <form method="GET" class="filter-form">
            <div class="filter-row">
                <div class="filter-group">
                    <label for="status">Order Status</label>
                    <select name="status" id="status" class="form-control">
                        <option value="">All Statuses</option>
                        <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="processing" <?php echo $status_filter === 'processing' ? 'selected' : ''; ?>>Processing</option>
                        <option value="shipped" <?php echo $status_filter === 'shipped' ? 'selected' : ''; ?>>Shipped</option>
                        <option value="delivered" <?php echo $status_filter === 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                        <option value="cancelled" <?php echo $status_filter === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label for="payment">Payment Status</label>
                    <select name="payment" id="payment" class="form-control">
                        <option value="">All Payment Status</option>
                        <option value="pending" <?php echo $payment_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="paid" <?php echo $payment_filter === 'paid' ? 'selected' : ''; ?>>Paid</option>
                        <option value="failed" <?php echo $payment_filter === 'failed' ? 'selected' : ''; ?>>Failed</option>
                        <option value="refunded" <?php echo $payment_filter === 'refunded' ? 'selected' : ''; ?>>Refunded</option>
                    </select>
                </div>
                
                <div class="filter-group">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i> Filter
                    </button>
                    <a href="orders.php" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Clear
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Orders List -->
<div class="dashboard-widget">
    <div class="widget-header">
        <h4><i class="fas fa-list"></i> Orders (<?php echo count($orders); ?>)</h4>
    </div>
    <div class="widget-content">
        <?php if (empty($orders)): ?>
            <div class="no-data">
                <i class="fas fa-shopping-cart" style="font-size: 3rem; color: #ccc; margin-bottom: 1rem;"></i>
                <h3>No Orders Found</h3>
                <p>No orders match your current filters.</p>
                <a href="orders.php" class="btn btn-secondary">Clear Filters</a>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Order #</th>
                            <th>Customer</th>
                            <th>Items</th>
                            <th>Total</th>
                            <th>Status</th>
                            <th>Payment</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $order): ?>
                            <tr>
                                <td>
                                    <strong>#<?php echo $order['id']; ?></strong>
                                    <?php if ($order['tracking_number']): ?>
                                        <br><small>Track: <?php echo htmlspecialchars($order['tracking_number']); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="customer-info">
                                        <strong><?php echo htmlspecialchars($order['first_name'] . ' ' . $order['last_name']); ?></strong>
                                        <br><small><?php echo htmlspecialchars($order['email']); ?></small>
                                    </div>
                                </td>
                                <td>
                                    <strong><?php echo $order['item_count']; ?> items</strong>
                                    <?php if ($order['product_names']): ?>
                                        <br><small title="<?php echo htmlspecialchars($order['product_names']); ?>">
                                            <?php echo htmlspecialchars(substr($order['product_names'], 0, 30)); ?>
                                            <?php echo strlen($order['product_names']) > 30 ? '...' : ''; ?>
                                        </small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <strong><?php echo formatCurrency($order['total_amount']); ?></strong>
                                </td>
                                <td>
                                    <span class="status-badge status-<?php echo $order['status']; ?>">
                                        <?php echo ucfirst($order['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="payment-badge payment-<?php echo $order['payment_status']; ?>">
                                        <?php echo ucfirst($order['payment_status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <small><?php echo date('M j, Y', strtotime($order['created_at'])); ?></small>
                                    <br><small><?php echo date('H:i', strtotime($order['created_at'])); ?></small>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="order-details.php?id=<?php echo $order['id']; ?>" 
                                           class="btn btn-sm btn-primary" title="View Details">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <button onclick="updateOrderStatus(<?php echo $order['id']; ?>, '<?php echo $order['status']; ?>', '<?php echo htmlspecialchars($order['tracking_number'] ?? ''); ?>')" 
                                                class="btn btn-sm btn-secondary" title="Update Status">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Update Status Modal -->
<div id="statusModal" class="modal">
    <div class="modal-content">
        <span class="close">&times;</span>
        <h3>Update Order Status</h3>
        
        <form method="POST" id="statusForm">
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
            <input type="hidden" name="update_status" value="1">
            <input type="hidden" name="order_id" id="statusOrderId">
            
            <div class="form-group">
                <label for="statusSelect">Order Status</label>
                <select id="statusSelect" name="status" class="form-control" required>
                    <option value="pending">Pending</option>
                    <option value="processing">Processing</option>
                    <option value="shipped">Shipped</option>
                    <option value="delivered">Delivered</option>
                    <option value="cancelled">Cancelled</option>
                </select>
            </div>
            
            <div class="form-group" id="trackingGroup" style="display: none;">
                <label for="trackingNumber">Tracking Number</label>
                <input type="text" id="trackingNumber" name="tracking_number" class="form-control" 
                       placeholder="Enter tracking number">
            </div>
            
            <div class="modal-actions">
                <button type="button" onclick="closeStatusModal()" class="btn btn-secondary">Cancel</button>
                <button type="submit" class="btn btn-primary">Update Status</button>
            </div>
        </form>
    </div>
</div>

<style>
.filter-form {
    background: #f8f9fa;
    padding: 1rem;
    border-radius: 6px;
}

.filter-row {
    display: flex;
    gap: 1rem;
    align-items: end;
    flex-wrap: wrap;
}

.filter-group {
    flex: 1;
    min-width: 150px;
}

.filter-group label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 600;
    color: var(--admin-dark);
}

.form-control {
    width: 100%;
    padding: 0.5rem;
    border: 1px solid #ddd;
    border-radius: 4px;
}

.admin-table {
    width: 100%;
    border-collapse: collapse;
    background: white;
}

.admin-table th,
.admin-table td {
    padding: 1rem;
    text-align: left;
    border-bottom: 1px solid #e0e0e0;
}

.admin-table th {
    background: #f8f9fa;
    font-weight: 600;
    color: var(--admin-dark);
}

.admin-table tbody tr:hover {
    background: #f8f9fa;
}

.customer-info strong {
    color: var(--admin-dark);
}

.payment-badge {
    padding: 0.25rem 0.75rem;
    border-radius: 15px;
    font-size: 0.8rem;
    font-weight: bold;
}

.payment-pending {
    background: #fff3cd;
    color: #856404;
}

.payment-paid {
    background: #d4edda;
    color: #155724;
}

.payment-failed {
    background: #f8d7da;
    color: #721c24;
}

.payment-refunded {
    background: #cce5ff;
    color: #004085;
}

.action-buttons {
    display: flex;
    gap: 0.25rem;
}

.btn-sm {
    padding: 0.5rem;
    font-size: 0.8rem;
    min-width: 32px;
    text-align: center;
}

.modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.5);
}

.modal-content {
    background-color: white;
    margin: 10% auto;
    padding: 2rem;
    border-radius: 8px;
    width: 90%;
    max-width: 500px;
}

.close {
    color: #aaa;
    float: right;
    font-size: 28px;
    font-weight: bold;
    cursor: pointer;
}

.close:hover {
    color: black;
}

.form-group {
    margin-bottom: 1rem;
}

.form-group label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 600;
    color: var(--admin-dark);
}

.modal-actions {
    display: flex;
    gap: 1rem;
    justify-content: flex-end;
    margin-top: 1rem;
}

.alert {
    padding: 1rem;
    border-radius: 6px;
    margin-bottom: 1rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.alert-success {
    background: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.alert-error {
    background: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

.table-responsive {
    overflow-x: auto;
}

.no-data {
    text-align: center;
    color: #999;
    font-style: italic;
    padding: 2rem;
}

@media (max-width: 768px) {
    .filter-row {
        flex-direction: column;
    }
    
    .filter-group {
        min-width: 100%;
    }
    
    .admin-table {
        min-width: 800px;
    }
    
    .action-buttons {
        flex-direction: column;
    }
}
</style>

<script>
function updateOrderStatus(orderId, currentStatus, trackingNumber) {
    document.getElementById('statusOrderId').value = orderId;
    document.getElementById('statusSelect').value = currentStatus;
    document.getElementById('trackingNumber').value = trackingNumber;
    
    // Show/hide tracking number field based on status
    toggleTrackingField(currentStatus);
    
    document.getElementById('statusModal').style.display = 'block';
}

function toggleTrackingField(status) {
    const trackingGroup = document.getElementById('trackingGroup');
    if (status === 'shipped' || status === 'delivered') {
        trackingGroup.style.display = 'block';
    } else {
        trackingGroup.style.display = 'none';
    }
}

function closeStatusModal() {
    document.getElementById('statusModal').style.display = 'none';
}

// Show/hide tracking field when status changes
document.getElementById('statusSelect').addEventListener('change', function() {
    toggleTrackingField(this.value);
});

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('statusModal');
    if (event.target === modal) {
        modal.style.display = 'none';
    }
}

// Close modal with X button
document.querySelector('.close').onclick = closeStatusModal;
</script>

<?php require_once 'includes/admin_footer.php'; ?>
