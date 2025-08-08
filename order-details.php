<?php
/**
 * Order Details Page
 * View detailed information about a specific order
 */

require_once 'config/config.php';

$page_title = 'Order Details';

// Require login
requireLogin();

$customer_id = $_SESSION['user_id'];
$order_id = intval($_GET['id'] ?? 0);

if (!$order_id) {
    redirect('orders.php');
}

// Get order details
$order_sql = "
    SELECT o.*, 
           sa.address_line_1 as shipping_address_1, sa.address_line_2 as shipping_address_2,
           sa.city as shipping_city, sa.state as shipping_state, sa.postal_code as shipping_postal,
           sa.country as shipping_country,
           ba.address_line_1 as billing_address_1, ba.address_line_2 as billing_address_2,
           ba.city as billing_city, ba.state as billing_state, ba.postal_code as billing_postal,
           ba.country as billing_country
    FROM orders o
    LEFT JOIN user_addresses sa ON o.shipping_address_id = sa.id
    LEFT JOIN user_addresses ba ON o.billing_address_id = ba.id
    WHERE o.id = ? AND o.customer_id = ?
";

$order = $database->fetch($order_sql, [$order_id, $customer_id]);

if (!$order) {
    redirect('orders.php');
}

// Get order items
$items_sql = "
    SELECT oi.*, p.name, p.image_url, p.slug, p.brand,
           u.username as vendor_name, vs.store_name
    FROM order_items oi
    JOIN products p ON oi.product_id = p.id
    JOIN users u ON p.vendor_id = u.id
    LEFT JOIN vendor_stores vs ON u.id = vs.vendor_id
    WHERE oi.order_id = ?
    ORDER BY vs.store_name, p.name
";

$order_items = $database->fetchAll($items_sql, [$order_id]);

// Group items by vendor
$vendors = [];
foreach ($order_items as $item) {
    $vendor_key = $item['vendor_name'];
    if (!isset($vendors[$vendor_key])) {
        $vendors[$vendor_key] = [
            'vendor_name' => $item['vendor_name'],
            'store_name' => $item['store_name'],
            'items' => [],
            'subtotal' => 0
        ];
    }
    $vendors[$vendor_key]['items'][] = $item;
    $vendors[$vendor_key]['subtotal'] += $item['quantity'] * $item['unit_price'];
}

// Get payment information if exists
$payment = null;
if ($order['payment_status'] === 'paid') {
    $payment = $database->fetch(
        "SELECT * FROM payments WHERE order_id = ? ORDER BY created_at DESC LIMIT 1",
        [$order_id]
    );
}

include 'includes/header.php';
?>

<div class="container">
    <div class="row">
        <div class="col-12">
            <!-- Breadcrumb -->
            <nav style="margin-bottom: 2rem;">
                <ol style="display: flex; list-style: none; padding: 0; margin: 0; color: #6b7280;">
                    <li><a href="orders.php" style="color: #007bff; text-decoration: none;">My Orders</a></li>
                    <li style="margin: 0 0.5rem;">/</li>
                    <li>Order #<?php echo htmlspecialchars($order['order_number']); ?></li>
                </ol>
            </nav>

            <!-- Order Header -->
            <div style="background: white; padding: 2rem; border-radius: 10px; margin-bottom: 2rem; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; margin-bottom: 1.5rem;">
                    <div>
                        <h1 style="margin: 0 0 0.5rem 0; color: #374151;">
                            Order #<?php echo htmlspecialchars($order['order_number']); ?>
                        </h1>
                        <p style="margin: 0; color: #6b7280;">
                            Placed on <?php echo date('F j, Y \a\t g:i A', strtotime($order['created_at'])); ?>
                        </p>
                    </div>
                    <div style="text-align: right;">
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
                        <div style="margin-bottom: 0.5rem;">
                            <span style="background: <?php echo $status_color; ?>; color: white; padding: 6px 16px; border-radius: 20px; font-size: 0.9rem; font-weight: bold;">
                                <?php echo ucfirst($order['status']); ?>
                            </span>
                        </div>
                        <div style="font-size: 1.5rem; font-weight: bold; color: #374151;">
                            RWF <?php echo number_format($order['total_amount']); ?>
                        </div>
                    </div>
                </div>

                <!-- Order Actions -->
                <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
                    <?php if ($order['status'] === 'delivered'): ?>
                        <button onclick="reorderItems()" class="btn btn-primary">
                            <i class="fas fa-redo"></i> Reorder Items
                        </button>
                    <?php endif; ?>
                    
                    <?php if (in_array($order['status'], ['shipped', 'delivered']) && $order['tracking_number']): ?>
                        <button onclick="trackOrder('<?php echo $order['tracking_number']; ?>')" class="btn btn-outline-info">
                            <i class="fas fa-truck"></i> Track Package
                        </button>
                    <?php endif; ?>
                    
                    <button onclick="downloadInvoice()" class="btn btn-outline-secondary">
                        <i class="fas fa-download"></i> Download Invoice
                    </button>
                    
                    <?php if ($order['status'] === 'pending'): ?>
                        <button onclick="cancelOrder()" class="btn btn-outline-danger">
                            <i class="fas fa-times"></i> Cancel Order
                        </button>
                    <?php endif; ?>
                </div>
            </div>

            <div class="row">
                <!-- Order Items -->
                <div class="col-lg-8">
                    <div style="background: white; padding: 2rem; border-radius: 10px; margin-bottom: 2rem; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                        <h3 style="margin: 0 0 1.5rem 0; color: #374151;">Order Items</h3>
                        
                        <?php foreach ($vendors as $vendor): ?>
                            <div class="vendor-section" style="margin-bottom: 2rem; padding-bottom: 2rem; border-bottom: 1px solid #e5e7eb;">
                                <!-- Vendor Header -->
                                <div style="background: #f8fafc; padding: 1rem; border-radius: 8px; margin-bottom: 1rem;">
                                    <h4 style="margin: 0; color: #374151;">
                                        <?php echo htmlspecialchars($vendor['store_name'] ?: $vendor['vendor_name']); ?>
                                    </h4>
                                    <p style="margin: 0; color: #6b7280; font-size: 0.9rem;">
                                        Subtotal: RWF <?php echo number_format($vendor['subtotal']); ?>
                                    </p>
                                </div>

                                <!-- Vendor Items -->
                                <?php foreach ($vendor['items'] as $item): ?>
                                    <div style="display: flex; gap: 1rem; padding: 1rem 0; border-bottom: 1px solid #f3f4f6;">
                                        <!-- Product Image -->
                                        <div style="flex-shrink: 0;">
                                            <div style="width: 80px; height: 80px; border-radius: 8px; overflow: hidden; background: #f3f4f6;">
                                                <?php if ($item['image_url']): ?>
                                                    <img src="<?php echo htmlspecialchars($item['image_url']); ?>" 
                                                         alt="<?php echo htmlspecialchars($item['name']); ?>"
                                                         style="width: 100%; height: 100%; object-fit: cover;">
                                                <?php else: ?>
                                                    <div style="width: 100%; height: 100%; display: flex; align-items: center; justify-content: center;">
                                                        <i class="fas fa-image" style="color: #9ca3af;"></i>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>

                                        <!-- Product Details -->
                                        <div style="flex: 1;">
                                            <h5 style="margin: 0 0 0.5rem 0; color: #374151;">
                                                <?php echo htmlspecialchars($item['name']); ?>
                                            </h5>
                                            <?php if ($item['brand']): ?>
                                                <p style="margin: 0 0 0.5rem 0; color: #6b7280; font-size: 0.9rem;">
                                                    Brand: <?php echo htmlspecialchars($item['brand']); ?>
                                                </p>
                                            <?php endif; ?>
                                            <p style="margin: 0; color: #6b7280; font-size: 0.9rem;">
                                                Quantity: <?php echo $item['quantity']; ?> × RWF <?php echo number_format($item['unit_price']); ?>
                                            </p>
                                        </div>

                                        <!-- Item Total -->
                                        <div style="text-align: right; align-self: center;">
                                            <div style="font-weight: bold; color: #374151;">
                                                RWF <?php echo number_format($item['quantity'] * $item['unit_price']); ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Order Summary & Details -->
                <div class="col-lg-4">
                    <!-- Order Summary -->
                    <div style="background: white; padding: 2rem; border-radius: 10px; margin-bottom: 2rem; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                        <h3 style="margin: 0 0 1.5rem 0; color: #374151;">Order Summary</h3>
                        
                        <div style="border-bottom: 1px solid #e5e7eb; padding-bottom: 1rem; margin-bottom: 1rem;">
                            <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                                <span>Subtotal:</span>
                                <span>RWF <?php echo number_format($order['subtotal']); ?></span>
                            </div>
                            <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                                <span>Shipping:</span>
                                <span>RWF <?php echo number_format($order['shipping_cost']); ?></span>
                            </div>
                            <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                                <span>Tax (18%):</span>
                                <span>RWF <?php echo number_format($order['tax_amount']); ?></span>
                            </div>
                        </div>
                        
                        <div style="display: flex; justify-content: space-between; font-size: 1.1rem; font-weight: bold; color: #374151;">
                            <span>Total:</span>
                            <span>RWF <?php echo number_format($order['total_amount']); ?></span>
                        </div>
                    </div>

                    <!-- Payment Information -->
                    <div style="background: white; padding: 2rem; border-radius: 10px; margin-bottom: 2rem; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                        <h3 style="margin: 0 0 1.5rem 0; color: #374151;">Payment Information</h3>
                        
                        <div style="margin-bottom: 1rem;">
                            <strong>Payment Method:</strong><br>
                            <?php echo ucwords(str_replace('_', ' ', $order['payment_method'])); ?>
                        </div>
                        
                        <div style="margin-bottom: 1rem;">
                            <strong>Payment Status:</strong><br>
                            <span style="color: <?php echo $order['payment_status'] === 'paid' ? '#28a745' : '#ffc107'; ?>;">
                                <?php echo ucfirst($order['payment_status']); ?>
                            </span>
                        </div>
                        
                        <?php if ($payment): ?>
                            <div style="margin-bottom: 1rem;">
                                <strong>Payment Reference:</strong><br>
                                <code style="background: #f8fafc; padding: 2px 6px; border-radius: 4px; font-size: 0.9rem;">
                                    <?php echo htmlspecialchars($payment['payment_reference']); ?>
                                </code>
                            </div>
                            
                            <div>
                                <strong>Payment Date:</strong><br>
                                <?php echo date('F j, Y \a\t g:i A', strtotime($payment['created_at'])); ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Shipping Address -->
                    <div style="background: white; padding: 2rem; border-radius: 10px; margin-bottom: 2rem; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                        <h3 style="margin: 0 0 1.5rem 0; color: #374151;">Shipping Address</h3>
                        
                        <div style="color: #6b7280; line-height: 1.6;">
                            <?php echo htmlspecialchars($order['shipping_address_1']); ?><br>
                            <?php if ($order['shipping_address_2']): ?>
                                <?php echo htmlspecialchars($order['shipping_address_2']); ?><br>
                            <?php endif; ?>
                            <?php echo htmlspecialchars($order['shipping_city']); ?>, <?php echo htmlspecialchars($order['shipping_state']); ?><br>
                            <?php echo htmlspecialchars($order['shipping_postal']); ?><br>
                            <?php echo htmlspecialchars($order['shipping_country']); ?>
                        </div>
                    </div>

                    <!-- Billing Address -->
                    <div style="background: white; padding: 2rem; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                        <h3 style="margin: 0 0 1.5rem 0; color: #374151;">Billing Address</h3>
                        
                        <div style="color: #6b7280; line-height: 1.6;">
                            <?php echo htmlspecialchars($order['billing_address_1']); ?><br>
                            <?php if ($order['billing_address_2']): ?>
                                <?php echo htmlspecialchars($order['billing_address_2']); ?><br>
                            <?php endif; ?>
                            <?php echo htmlspecialchars($order['billing_city']); ?>, <?php echo htmlspecialchars($order['billing_state']); ?><br>
                            <?php echo htmlspecialchars($order['billing_postal']); ?><br>
                            <?php echo htmlspecialchars($order['billing_country']); ?>
                        </div>
                    </div>
                </div>
            </div>
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

.btn-outline-info {
    background: transparent;
    color: #17a2b8;
    border-color: #17a2b8;
}

.btn-outline-info:hover {
    background: #17a2b8;
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

.btn-outline-danger {
    background: transparent;
    color: #dc3545;
    border-color: #dc3545;
}

.btn-outline-danger:hover {
    background: #dc3545;
    color: white;
}
</style>

<script>
function reorderItems() {
    if (confirm('Add all items from this order to your cart?')) {
        fetch('api/cart.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'reorder',
                order_id: <?php echo $order_id; ?>
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
    window.open(`order-tracking.php?tracking=${trackingNumber}`, '_blank');
}

function downloadInvoice() {
    window.open(`invoice.php?order=<?php echo $order_id; ?>`, '_blank');
}

function cancelOrder() {
    if (confirm('Are you sure you want to cancel this order? This action cannot be undone.')) {
        fetch('api/orders.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'cancel',
                order_id: <?php echo $order_id; ?>
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification('Order cancelled successfully', 'success');
                setTimeout(() => {
                    location.reload();
                }, 1000);
            } else {
                showNotification(data.message || 'Error cancelling order', 'error');
            }
        })
        .catch(error => {
            showNotification('Error cancelling order', 'error');
        });
    }
}

function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
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
