<?php
/**
 * MarketHub Order Confirmation
 * Multi-Vendor E-Commerce Platform
 */

require_once 'config/config.php';

$page_title = 'Order Confirmation';

// Require login
requireLogin();

$customer_id = $_SESSION['user_id'];
$order_number = sanitizeInput($_GET['order'] ?? '');

if (empty($order_number)) {
    redirect('dashboard.php');
}

// Get order details with safe column access
$order_sql = "SELECT * FROM orders WHERE order_number = ? AND customer_id = ?";
$order = $database->fetch($order_sql, [$order_number, $customer_id]);

// Get shipping address if available
$shipping_address = null;
$billing_address = null;

if ($order && !empty($order['shipping_address_id'])) {
    try {
        $shipping_sql = "SELECT * FROM user_addresses WHERE id = ?";
        $shipping_address = $database->fetch($shipping_sql, [$order['shipping_address_id']]);
    } catch (Exception $e) {
        // Address table might not exist or have different structure
        $shipping_address = null;
    }
}

if ($order && !empty($order['billing_address_id'])) {
    try {
        $billing_sql = "SELECT * FROM user_addresses WHERE id = ?";
        $billing_address = $database->fetch($billing_sql, [$order['billing_address_id']]);
    } catch (Exception $e) {
        // Address table might not exist or have different structure
        $billing_address = null;
    }
}

if (!$order) {
    redirect('orders.php');
}

// Get order items grouped by vendor
$items_sql = "
    SELECT oi.*, p.name, p.sku, pi.image_url,
           u.id as vendor_id, u.username as vendor_name, vs.store_name
    FROM order_items oi
    JOIN products p ON oi.product_id = p.id
    LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
    LEFT JOIN users u ON oi.vendor_id = u.id
    LEFT JOIN vendor_stores vs ON u.id = vs.vendor_id
    WHERE oi.order_id = ?
    ORDER BY vs.store_name, p.name
";

$order_items = $database->fetchAll($items_sql, [$order['id']]);

// Group items by vendor
$vendors = [];
foreach ($order_items as $item) {
    $vendor_key = $item['vendor_id'];
    if (!isset($vendors[$vendor_key])) {
        $vendors[$vendor_key] = [
            'id' => $item['vendor_id'],
            'name' => $item['store_name'] ?: $item['vendor_name'],
            'items' => [],
            'subtotal' => 0
        ];
    }
    $vendors[$vendor_key]['items'][] = $item;
    $vendors[$vendor_key]['subtotal'] += $item['total_price'];
}

// Get payment information
$payment = $database->fetch(
    "SELECT * FROM payments WHERE order_id = ? ORDER BY created_at DESC LIMIT 1",
    [$order['id']]
);

require_once 'includes/header.php';
?>

<div class="container" style="max-width: 800px; margin: 2rem auto;">
    <!-- Success Header -->
    <div class="text-center mb-4">
        <div style="color: var(--secondary-green); margin-bottom: 1rem;">
            <i class="fas fa-check-circle" style="font-size: 4rem;"></i>
        </div>
        <h1 style="color: var(--primary-green);">Order Confirmed!</h1>
        <p style="font-size: 1.2rem; color: var(--dark-gray);">
            Thank you for your order. We've received your payment and are processing your items.
        </p>
    </div>

    <!-- Order Summary Card -->
    <div class="card mb-3">
        <div class="card-header">
            <div class="row align-items-center">
                <div class="col-6">
                    <h5 style="margin-bottom: 0;">Order Details</h5>
                </div>
                <div class="col-6 text-right">
                    <span class="badge" style="background: var(--secondary-green); color: white; padding: 0.5rem 1rem; font-size: 0.9rem;">
                        <?php echo ucfirst($order['status']); ?>
                    </span>
                </div>
            </div>
        </div>
        
        <div class="card-body">
            <div class="row mb-3">
                <div class="col-6">
                    <strong>Order Number:</strong><br>
                    <span style="font-size: 1.1rem; color: var(--primary-green);"><?php echo htmlspecialchars($order['order_number']); ?></span>
                </div>
                <div class="col-6">
                    <strong>Order Date:</strong><br>
                    <?php echo date('F j, Y g:i A', strtotime($order['created_at'])); ?>
                </div>
            </div>
            
            <div class="row mb-3">
                <div class="col-6">
                    <strong>Payment Method:</strong><br>
                    <?php echo ucwords(str_replace('_', ' ', $order['payment_method'])); ?>
                </div>
                <div class="col-6">
                    <strong>Payment Status:</strong><br>
                    <span style="color: var(--secondary-green); font-weight: 600;">
                        <?php echo ucfirst($order['payment_status']); ?>
                    </span>
                </div>
            </div>
            
            <?php if ($payment && $payment['payment_reference']): ?>
                <div class="row">
                    <div class="col-12">
                        <strong>Payment Reference:</strong><br>
                        <code style="background: var(--light-gray); padding: 0.25rem 0.5rem; border-radius: 4px;">
                            <?php echo htmlspecialchars($payment['payment_reference']); ?>
                        </code>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Order Items -->
    <div class="card mb-3">
        <div class="card-header">
            <h5 style="margin-bottom: 0;">Order Items</h5>
        </div>
        
        <div class="card-body">
            <?php foreach ($vendors as $vendor): ?>
                <div class="vendor-section">
                    <h6 style="color: var(--primary-green); margin-bottom: 1rem; padding-bottom: 0.5rem; border-bottom: 1px solid var(--light-gray);">
                        <i class="fas fa-store"></i> <?php echo htmlspecialchars($vendor['name']); ?>
                        <span style="color: var(--dark-gray); font-weight: normal; font-size: 0.9rem; float: right;">
                            Subtotal: <?php echo formatCurrency($vendor['subtotal']); ?>
                        </span>
                    </h6>
                    
                    <?php foreach ($vendor['items'] as $item): ?>
                        <div class="order-item">
                            <img src="<?php echo $item['image_url'] ?: 'assets/images/product-placeholder.png'; ?>" 
                                 alt="<?php echo htmlspecialchars($item['name']); ?>" 
                                 class="item-image">
                            
                            <div class="item-details">
                                <h6><?php echo htmlspecialchars($item['name']); ?></h6>
                                <p style="color: var(--dark-gray); margin-bottom: 0.25rem;">
                                    SKU: <?php echo htmlspecialchars($item['sku']); ?>
                                </p>
                                <p style="color: var(--dark-gray); margin-bottom: 0;">
                                    Quantity: <?php echo $item['quantity']; ?> × <?php echo formatCurrency($item['unit_price']); ?>
                                </p>
                            </div>
                            
                            <div class="item-total">
                                <?php echo formatCurrency($item['total_price']); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Order Totals -->
    <div class="card mb-3">
        <div class="card-header">
            <h5 style="margin-bottom: 0;">Order Summary</h5>
        </div>
        
        <div class="card-body">
            <div class="summary-row">
                <span>Subtotal:</span>
                <strong><?php echo formatCurrency($order['subtotal']); ?></strong>
            </div>
            
            <div class="summary-row">
                <span>Shipping:</span>
                <strong><?php echo formatCurrency($order['shipping_cost']); ?></strong>
            </div>
            
            <div class="summary-row">
                <span>Tax (18% VAT):</span>
                <strong><?php echo formatCurrency($order['tax_amount']); ?></strong>
            </div>
            
            <hr>
            
            <div class="summary-row total-row">
                <span>Total:</span>
                <strong><?php echo formatCurrency($order['total_amount']); ?></strong>
            </div>
        </div>
    </div>

    <!-- Shipping Information -->
    <div class="row">
        <div class="col-6">
            <div class="card">
                <div class="card-header">
                    <h6 style="margin-bottom: 0;">Shipping Address</h6>
                </div>
                <div class="card-body">
                    <?php if ($shipping_address): ?>
                        <strong><?php echo htmlspecialchars($shipping_address['full_name'] ?? 'Customer'); ?></strong><br>
                        <?php echo htmlspecialchars($shipping_address['address_line_1']); ?><br>
                        <?php if (!empty($shipping_address['address_line_2'])): ?>
                            <?php echo htmlspecialchars($shipping_address['address_line_2']); ?><br>
                        <?php endif; ?>
                        <?php echo htmlspecialchars($shipping_address['city'] . ', ' . $shipping_address['state'] . ' ' . ($shipping_address['postal_code'] ?? '')); ?><br>
                        <?php echo htmlspecialchars($shipping_address['country'] ?? 'Rwanda'); ?>
                        <?php if (!empty($shipping_address['phone'])): ?>
                            <br><br><i class="fas fa-phone"></i> <?php echo htmlspecialchars($shipping_address['phone']); ?>
                        <?php endif; ?>
                    <?php else: ?>
                        <p style="color: #6b7280; font-style: italic;">Shipping address information not available</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="col-6">
            <div class="card">
                <div class="card-header">
                    <h6 style="margin-bottom: 0;">Billing Address</h6>
                </div>
                <div class="card-body">
                    <?php if ($billing_address): ?>
                        <strong><?php echo htmlspecialchars($billing_address['full_name'] ?? 'Customer'); ?></strong><br>
                        <?php echo htmlspecialchars($billing_address['address_line_1']); ?><br>
                        <?php if (!empty($billing_address['address_line_2'])): ?>
                            <?php echo htmlspecialchars($billing_address['address_line_2']); ?><br>
                        <?php endif; ?>
                        <?php echo htmlspecialchars($billing_address['city'] . ', ' . $billing_address['state'] . ' ' . ($billing_address['postal_code'] ?? '')); ?><br>
                        <?php echo htmlspecialchars($billing_address['country'] ?? 'Rwanda'); ?>
                        <?php if (!empty($billing_address['phone'])): ?>
                            <br><i class="fas fa-phone"></i> <?php echo htmlspecialchars($billing_address['phone']); ?>
                        <?php endif; ?>
                    <?php else: ?>
                        <p style="color: #6b7280; font-style: italic;">Same as shipping address</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Special Instructions -->
    <?php if ($order['special_instructions']): ?>
        <div class="card mt-3">
            <div class="card-header">
                <h6 style="margin-bottom: 0;">Special Instructions</h6>
            </div>
            <div class="card-body">
                <p style="margin-bottom: 0;"><?php echo nl2br(htmlspecialchars($order['special_instructions'])); ?></p>
            </div>
        </div>
    <?php endif; ?>

    <!-- Next Steps -->
    <div class="card mt-3">
        <div class="card-header">
            <h6 style="margin-bottom: 0;">What Happens Next?</h6>
        </div>
        <div class="card-body">
            <div class="next-steps">
                <div class="step">
                    <div class="step-icon completed">
                        <i class="fas fa-check"></i>
                    </div>
                    <div class="step-content">
                        <strong>Order Confirmed</strong>
                        <p>Your order has been received and payment confirmed.</p>
                    </div>
                </div>
                
                <div class="step">
                    <div class="step-icon pending">
                        <i class="fas fa-box"></i>
                    </div>
                    <div class="step-content">
                        <strong>Processing</strong>
                        <p>Vendors are preparing your items for shipment.</p>
                    </div>
                </div>
                
                <div class="step">
                    <div class="step-icon pending">
                        <i class="fas fa-truck"></i>
                    </div>
                    <div class="step-content">
                        <strong>Shipped</strong>
                        <p>You'll receive tracking information once items are shipped.</p>
                    </div>
                </div>
                
                <div class="step">
                    <div class="step-icon pending">
                        <i class="fas fa-home"></i>
                    </div>
                    <div class="step-content">
                        <strong>Delivered</strong>
                        <p>Your order will be delivered to your specified address.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Action Buttons -->
    <div class="text-center mt-4">
        <div style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
            <a href="invoice-improved.php?order=<?php echo $order['id']; ?>" class="btn btn-primary">
                <i class="fas fa-file-invoice"></i> View Invoice
            </a>
            <a href="order-details.php?id=<?php echo $order['id']; ?>" class="btn btn-outline">
                <i class="fas fa-eye"></i> Track Order
            </a>
            <a href="products.php" class="btn btn-outline">
                <i class="fas fa-shopping-bag"></i> Continue Shopping
            </a>
            <a href="dashboard.php" class="btn btn-outline">
                <i class="fas fa-tachometer-alt"></i> Go to Dashboard
            </a>
        </div>
    </div>

    <!-- Contact Support -->
    <div class="text-center mt-3">
        <p style="color: var(--dark-gray);">
            Need help with your order? 
            <a href="contact.php" style="color: var(--primary-green);">Contact our support team</a>
        </p>
    </div>
</div>

<style>
.vendor-section {
    margin-bottom: 2rem;
}

.vendor-section:last-child {
    margin-bottom: 0;
}

.order-item {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem 0;
    border-bottom: 1px solid var(--light-gray);
}

.order-item:last-child {
    border-bottom: none;
}

.item-image {
    width: 60px;
    height: 60px;
    object-fit: cover;
    border-radius: var(--border-radius);
    flex-shrink: 0;
}

.item-details {
    flex: 1;
}

.item-details h6 {
    margin-bottom: 0.25rem;
    font-weight: 600;
}

.item-total {
    font-weight: bold;
    color: var(--primary-green);
    flex-shrink: 0;
}

.summary-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.75rem;
}

.total-row {
    font-size: 1.2rem;
    font-weight: bold;
    color: var(--primary-green);
}

.next-steps {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.step {
    display: flex;
    align-items: flex-start;
    gap: 1rem;
}

.step-icon {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    font-weight: bold;
}

.step-icon.completed {
    background: var(--secondary-green);
    color: white;
}

.step-icon.pending {
    background: var(--light-gray);
    color: var(--dark-gray);
}

.step-content {
    flex: 1;
}

.step-content strong {
    display: block;
    margin-bottom: 0.25rem;
    color: var(--black);
}

.step-content p {
    margin-bottom: 0;
    color: var(--dark-gray);
    font-size: 0.9rem;
}

@media (max-width: 768px) {
    .col-6 {
        flex: 0 0 100%;
        margin-bottom: 1rem;
    }
    
    .order-item {
        flex-direction: column;
        text-align: center;
    }
    
    .next-steps {
        gap: 1.5rem;
    }
}
</style>

<script>
// Auto-refresh order status every 30 seconds
setInterval(function() {
    // In a real implementation, you might want to check for order status updates
    // and refresh the page or update specific elements
}, 30000);

// Print order functionality
function printOrder() {
    window.print();
}

// Add print button if needed
document.addEventListener('DOMContentLoaded', function() {
    // You can add a print button here if desired
});
</script>

<?php require_once 'includes/footer.php'; ?>
