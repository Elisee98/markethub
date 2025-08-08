<?php
/**
 * Order Tracking Page
 * Track order status and shipping progress
 */

require_once 'config/config.php';

$page_title = 'Track Your Order';

$tracking_number = sanitizeInput($_GET['tracking'] ?? '');
$order_number = sanitizeInput($_GET['order'] ?? '');

$order = null;
$tracking_events = [];

if ($tracking_number) {
    // Find order by tracking number
    $order = $database->fetch(
        "SELECT o.*, u.first_name, u.last_name, u.email 
         FROM orders o 
         JOIN users u ON o.customer_id = u.id 
         WHERE o.tracking_number = ?",
        [$tracking_number]
    );
} elseif ($order_number) {
    // Find order by order number
    $order = $database->fetch(
        "SELECT o.*, u.first_name, u.last_name, u.email 
         FROM orders o 
         JOIN users u ON o.customer_id = u.id 
         WHERE o.order_number = ?",
        [$order_number]
    );
}

if ($order) {
    // Get order items for display
    $items = $database->fetchAll(
        "SELECT oi.*, p.name, p.image_url 
         FROM order_items oi 
         JOIN products p ON oi.product_id = p.id 
         WHERE oi.order_id = ? 
         LIMIT 3",
        [$order['id']]
    );
    
    // Generate tracking events based on order status
    $tracking_events = generateTrackingEvents($order);
}

function generateTrackingEvents($order) {
    $events = [];
    
    // Order placed
    $events[] = [
        'status' => 'placed',
        'title' => 'Order Placed',
        'description' => 'Your order has been received and is being processed',
        'date' => $order['created_at'],
        'completed' => true,
        'icon' => 'fas fa-shopping-cart'
    ];
    
    // Payment confirmed
    if ($order['payment_status'] === 'paid') {
        $events[] = [
            'status' => 'payment_confirmed',
            'title' => 'Payment Confirmed',
            'description' => 'Payment has been successfully processed',
            'date' => $order['payment_date'] ?? $order['created_at'],
            'completed' => true,
            'icon' => 'fas fa-credit-card'
        ];
    }
    
    // Processing
    if (in_array($order['status'], ['processing', 'shipped', 'delivered'])) {
        $events[] = [
            'status' => 'processing',
            'title' => 'Order Processing',
            'description' => 'Your order is being prepared for shipment',
            'date' => $order['updated_at'],
            'completed' => true,
            'icon' => 'fas fa-cogs'
        ];
    }
    
    // Shipped
    if (in_array($order['status'], ['shipped', 'delivered'])) {
        $events[] = [
            'status' => 'shipped',
            'title' => 'Order Shipped',
            'description' => 'Your order has been shipped and is on its way',
            'date' => $order['shipped_at'] ?? $order['updated_at'],
            'completed' => true,
            'icon' => 'fas fa-truck'
        ];
    }
    
    // Out for delivery
    if ($order['status'] === 'delivered') {
        $events[] = [
            'status' => 'out_for_delivery',
            'title' => 'Out for Delivery',
            'description' => 'Your order is out for delivery',
            'date' => $order['delivered_at'] ?? $order['updated_at'],
            'completed' => true,
            'icon' => 'fas fa-shipping-fast'
        ];
    }
    
    // Delivered
    if ($order['status'] === 'delivered') {
        $events[] = [
            'status' => 'delivered',
            'title' => 'Delivered',
            'description' => 'Your order has been successfully delivered',
            'date' => $order['delivered_at'] ?? $order['updated_at'],
            'completed' => true,
            'icon' => 'fas fa-check-circle'
        ];
    } else {
        // Future delivery event
        $events[] = [
            'status' => 'delivered',
            'title' => 'Delivered',
            'description' => 'Your order will be delivered soon',
            'date' => null,
            'completed' => false,
            'icon' => 'fas fa-check-circle'
        ];
    }
    
    return $events;
}

include 'includes/header.php';
?>

<div class="container">
    <div class="row">
        <div class="col-12">
            <!-- Header -->
            <div style="background: white; padding: 2rem; border-radius: 10px; margin-bottom: 2rem; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                <h1 style="margin: 0 0 1rem 0; color: #374151;">
                    <i class="fas fa-truck"></i> Track Your Order
                </h1>
                
                <?php if (!$tracking_number && !$order_number): ?>
                    <!-- Tracking Form -->
                    <div style="max-width: 500px;">
                        <p style="color: #6b7280; margin-bottom: 1.5rem;">
                            Enter your tracking number or order number to track your package
                        </p>
                        
                        <form method="GET" style="display: flex; gap: 1rem;">
                            <input type="text" 
                                   name="tracking" 
                                   class="form-control" 
                                   placeholder="Enter tracking number or order number"
                                   style="flex: 1;"
                                   required>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search"></i> Track
                            </button>
                        </form>
                        
                        <p style="color: #6b7280; font-size: 0.9rem; margin-top: 1rem;">
                            You can find your tracking number in your order confirmation email or in your account dashboard.
                        </p>
                    </div>
                <?php endif; ?>
            </div>

            <?php if ($order): ?>
                <!-- Order Information -->
                <div style="background: white; padding: 2rem; border-radius: 10px; margin-bottom: 2rem; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                    <div style="display: flex; justify-content: between; align-items: center; flex-wrap: wrap; gap: 2rem;">
                        <!-- Order Details -->
                        <div style="flex: 1;">
                            <h3 style="margin: 0 0 1rem 0; color: #374151;">
                                Order #<?php echo htmlspecialchars($order['order_number']); ?>
                            </h3>
                            
                            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                                <div>
                                    <strong>Order Date:</strong><br>
                                    <?php echo date('F j, Y', strtotime($order['created_at'])); ?>
                                </div>
                                <div>
                                    <strong>Total Amount:</strong><br>
                                    RWF <?php echo number_format($order['total_amount']); ?>
                                </div>
                                <?php if ($order['tracking_number']): ?>
                                    <div>
                                        <strong>Tracking Number:</strong><br>
                                        <code style="background: #f8fafc; padding: 2px 6px; border-radius: 4px;">
                                            <?php echo htmlspecialchars($order['tracking_number']); ?>
                                        </code>
                                    </div>
                                <?php endif; ?>
                                <div>
                                    <strong>Status:</strong><br>
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
                        
                        <!-- Order Items Preview -->
                        <div style="display: flex; gap: 0.5rem;">
                            <?php foreach ($items as $index => $item): ?>
                                <?php if ($index < 3): ?>
                                    <div style="width: 60px; height: 60px; border-radius: 8px; overflow: hidden; background: #f3f4f6;">
                                        <?php if ($item['image_url']): ?>
                                            <img src="<?php echo htmlspecialchars($item['image_url']); ?>" 
                                                 alt="<?php echo htmlspecialchars($item['name']); ?>"
                                                 style="width: 100%; height: 100%; object-fit: cover;">
                                        <?php else: ?>
                                            <div style="width: 100%; height: 100%; display: flex; align-items: center; justify-content: center;">
                                                <i class="fas fa-box" style="color: #9ca3af;"></i>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- Tracking Timeline -->
                <div style="background: white; padding: 2rem; border-radius: 10px; margin-bottom: 2rem; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                    <h3 style="margin: 0 0 2rem 0; color: #374151;">Tracking Timeline</h3>
                    
                    <div class="tracking-timeline">
                        <?php foreach ($tracking_events as $index => $event): ?>
                            <div class="timeline-item <?php echo $event['completed'] ? 'completed' : 'pending'; ?>" 
                                 style="display: flex; gap: 1.5rem; margin-bottom: 2rem; position: relative;">
                                
                                <!-- Timeline Icon -->
                                <div class="timeline-icon" 
                                     style="flex-shrink: 0; width: 50px; height: 50px; border-radius: 50%; display: flex; align-items: center; justify-content: center; position: relative; z-index: 2;
                                            background: <?php echo $event['completed'] ? '#28a745' : '#e5e7eb'; ?>; 
                                            color: <?php echo $event['completed'] ? 'white' : '#9ca3af'; ?>;">
                                    <i class="<?php echo $event['icon']; ?>"></i>
                                </div>
                                
                                <!-- Timeline Content -->
                                <div style="flex: 1; padding-top: 0.5rem;">
                                    <h4 style="margin: 0 0 0.5rem 0; color: <?php echo $event['completed'] ? '#374151' : '#9ca3af'; ?>;">
                                        <?php echo $event['title']; ?>
                                    </h4>
                                    <p style="margin: 0 0 0.5rem 0; color: <?php echo $event['completed'] ? '#6b7280' : '#9ca3af'; ?>;">
                                        <?php echo $event['description']; ?>
                                    </p>
                                    <?php if ($event['date']): ?>
                                        <p style="margin: 0; color: <?php echo $event['completed'] ? '#6b7280' : '#9ca3af'; ?>; font-size: 0.9rem;">
                                            <?php echo date('F j, Y \a\t g:i A', strtotime($event['date'])); ?>
                                        </p>
                                    <?php endif; ?>
                                </div>
                                
                                <!-- Timeline Line -->
                                <?php if ($index < count($tracking_events) - 1): ?>
                                    <div style="position: absolute; left: 24px; top: 50px; width: 2px; height: 40px; 
                                                background: <?php echo $event['completed'] ? '#28a745' : '#e5e7eb'; ?>; z-index: 1;"></div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Additional Information -->
                <div style="background: white; padding: 2rem; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                    <h3 style="margin: 0 0 1.5rem 0; color: #374151;">Need Help?</h3>
                    
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 2rem;">
                        <div>
                            <h4 style="color: #007bff; margin-bottom: 1rem;">
                                <i class="fas fa-headset"></i> Customer Support
                            </h4>
                            <p style="color: #6b7280; margin-bottom: 1rem;">
                                Have questions about your order? Our support team is here to help.
                            </p>
                            <a href="contact.php" class="btn btn-outline-primary">Contact Support</a>
                        </div>
                        
                        <div>
                            <h4 style="color: #28a745; margin-bottom: 1rem;">
                                <i class="fas fa-redo"></i> Reorder Items
                            </h4>
                            <p style="color: #6b7280; margin-bottom: 1rem;">
                                Love your order? Reorder the same items with just one click.
                            </p>
                            <button onclick="reorderItems(<?php echo $order['id']; ?>)" class="btn btn-outline-success">
                                Reorder
                            </button>
                        </div>
                        
                        <div>
                            <h4 style="color: #6f42c1; margin-bottom: 1rem;">
                                <i class="fas fa-file-invoice"></i> Order Details
                            </h4>
                            <p style="color: #6b7280; margin-bottom: 1rem;">
                                View complete order details, invoice, and shipping information.
                            </p>
                            <?php if (isLoggedIn() && $_SESSION['user_id'] == $order['customer_id']): ?>
                                <a href="order-details.php?id=<?php echo $order['id']; ?>" class="btn btn-outline-secondary">
                                    View Details
                                </a>
                            <?php else: ?>
                                <a href="login.php" class="btn btn-outline-secondary">
                                    Login to View
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

            <?php elseif ($tracking_number || $order_number): ?>
                <!-- Order Not Found -->
                <div style="text-align: center; padding: 4rem 2rem; background: white; border-radius: 10px;">
                    <i class="fas fa-search" style="font-size: 4rem; color: #dc3545; margin-bottom: 1rem; opacity: 0.3;"></i>
                    <h3 style="color: #374151; margin-bottom: 1rem;">Order Not Found</h3>
                    <p style="color: #6b7280; margin-bottom: 2rem;">
                        We couldn't find an order with the tracking number or order number you provided.<br>
                        Please check the number and try again.
                    </p>
                    <div style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
                        <a href="order-tracking.php" class="btn btn-primary">
                            <i class="fas fa-search"></i> Try Again
                        </a>
                        <a href="contact.php" class="btn btn-outline-secondary">
                            <i class="fas fa-headset"></i> Contact Support
                        </a>
                    </div>
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

.btn-outline-success {
    background: transparent;
    color: #28a745;
    border-color: #28a745;
}

.btn-outline-success:hover {
    background: #28a745;
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

.form-control {
    padding: 0.75rem;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    font-size: 1rem;
}

.form-control:focus {
    outline: none;
    border-color: #007bff;
    box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.1);
}

.timeline-item.completed .timeline-icon {
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0% {
        box-shadow: 0 0 0 0 rgba(40, 167, 69, 0.7);
    }
    70% {
        box-shadow: 0 0 0 10px rgba(40, 167, 69, 0);
    }
    100% {
        box-shadow: 0 0 0 0 rgba(40, 167, 69, 0);
    }
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
