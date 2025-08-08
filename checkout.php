<?php
/**
 * MarketHub Secure Checkout
 * Multi-Vendor E-Commerce Platform
 */

require_once 'config/config.php';

$page_title = 'Checkout';

// Require login for checkout
requireLogin();

$customer_id = $_SESSION['user_id'];
$error_message = '';
$success_message = '';

// Get customer information
$customer = $database->fetch("SELECT * FROM users WHERE id = ?", [$customer_id]);

// Get cart items
$cart_sql = "
    SELECT ci.*, p.name, p.price, p.stock_quantity, p.weight, pi.image_url,
           u.id as vendor_id, u.username as vendor_name, vs.store_name,
           (ci.quantity * p.price) as item_total
    FROM cart_items ci
    JOIN products p ON ci.product_id = p.id
    LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
    LEFT JOIN users u ON p.vendor_id = u.id
    LEFT JOIN vendor_stores vs ON u.id = vs.vendor_id
    WHERE ci.customer_id = ? AND p.status = 'active'
    ORDER BY vs.store_name, p.name
";

$cart_items = $database->fetchAll($cart_sql, [$customer_id]);

// Redirect if cart is empty
if (empty($cart_items)) {
    redirect('cart.php');
}

// Group items by vendor
$vendors = [];
foreach ($cart_items as $item) {
    $vendor_key = $item['vendor_id'];
    if (!isset($vendors[$vendor_key])) {
        $vendors[$vendor_key] = [
            'id' => $item['vendor_id'],
            'name' => $item['store_name'] ?: $item['vendor_name'],
            'items' => [],
            'subtotal' => 0,
            'shipping' => 5.00 // Simplified shipping per vendor
        ];
    }
    $vendors[$vendor_key]['items'][] = $item;
    $vendors[$vendor_key]['subtotal'] += $item['item_total'];
}

// Calculate order totals
$order_summary = [
    'subtotal' => 0,
    'shipping' => 0,
    'tax' => 0,
    'total' => 0,
    'item_count' => 0
];

foreach ($cart_items as $item) {
    $order_summary['subtotal'] += $item['item_total'];
    $order_summary['item_count'] += $item['quantity'];
}

$order_summary['shipping'] = count($vendors) * 5; // $5 per vendor
$order_summary['tax'] = $order_summary['subtotal'] * 0.18; // 18% VAT
$order_summary['total'] = $order_summary['subtotal'] + $order_summary['shipping'] + $order_summary['tax'];

// Get customer addresses
$addresses_sql = "SELECT * FROM customer_addresses WHERE customer_id = ? ORDER BY is_default DESC, created_at DESC";
$addresses = $database->fetchAll($addresses_sql, [$customer_id]);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error_message = 'Invalid security token. Please try again.';
    } else {
        try {
            $shipping_address_id = intval($_POST['shipping_address'] ?? 0);
            $billing_address_id = intval($_POST['billing_address'] ?? 0);
            $payment_method = sanitizeInput($_POST['payment_method'] ?? '');
            $special_instructions = sanitizeInput($_POST['special_instructions'] ?? '');
            
            // Validate addresses
            if (!$shipping_address_id || !$billing_address_id) {
                throw new Exception('Please select shipping and billing addresses.');
            }
            
            // Validate payment method
            if (!in_array($payment_method, ['credit_card', 'mobile_money', 'bank_transfer'])) {
                throw new Exception('Please select a valid payment method.');
            }
            
            // Verify addresses belong to customer
            $shipping_address = $database->fetch(
                "SELECT * FROM customer_addresses WHERE id = ? AND customer_id = ?",
                [$shipping_address_id, $customer_id]
            );
            
            $billing_address = $database->fetch(
                "SELECT * FROM customer_addresses WHERE id = ? AND customer_id = ?",
                [$billing_address_id, $customer_id]
            );
            
            if (!$shipping_address || !$billing_address) {
                throw new Exception('Invalid address selection.');
            }
            
            // Start transaction
            $database->beginTransaction();
            
            try {
                // Create main order
                $order_number = 'ORD-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
                
                $order_sql = "INSERT INTO orders 
                              (customer_id, order_number, total_amount, subtotal, shipping_cost, tax_amount,
                               payment_method, payment_status, status, shipping_address_id, billing_address_id,
                               special_instructions, created_at) 
                              VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', 'pending', ?, ?, ?, NOW())";
                
                $order_id = $database->insert($order_sql, [
                    $customer_id, $order_number, $order_summary['total'], $order_summary['subtotal'],
                    $order_summary['shipping'], $order_summary['tax'], $payment_method,
                    $shipping_address_id, $billing_address_id, $special_instructions
                ]);
                
                // Create order items
                foreach ($cart_items as $item) {
                    $order_item_sql = "INSERT INTO order_items 
                                       (order_id, product_id, vendor_id, quantity, unit_price, total_price, created_at) 
                                       VALUES (?, ?, ?, ?, ?, ?, NOW())";
                    
                    $database->execute($order_item_sql, [
                        $order_id, $item['product_id'], $item['vendor_id'],
                        $item['quantity'], $item['price'], $item['item_total']
                    ]);
                    
                    // Update product stock
                    $stock_sql = "UPDATE products SET stock_quantity = stock_quantity - ? WHERE id = ?";
                    $database->execute($stock_sql, [$item['quantity'], $item['product_id']]);
                }
                
                // Clear cart
                $clear_cart_sql = "DELETE FROM cart_items WHERE customer_id = ?";
                $database->execute($clear_cart_sql, [$customer_id]);
                
                // Commit transaction
                $database->commit();
                
                // Log activity
                logActivity($customer_id, 'order_placed', "Order: $order_number, Total: " . formatCurrency($order_summary['total']));
                
                // Send confirmation email
                $subject = "Order Confirmation - $order_number";
                $message = "
                    <h2>Order Confirmation</h2>
                    <p>Thank you for your order! Here are the details:</p>
                    <p><strong>Order Number:</strong> $order_number</p>
                    <p><strong>Total Amount:</strong> " . formatCurrency($order_summary['total']) . "</p>
                    <p><strong>Payment Method:</strong> " . ucwords(str_replace('_', ' ', $payment_method)) . "</p>
                    <p>We'll send you updates as your order is processed.</p>
                ";
                
                sendEmail($customer['email'], $subject, $message);
                
                // Redirect to order confirmation
                redirect("order-confirmation.php?order=$order_number");
                
            } catch (Exception $e) {
                $database->rollback();
                throw $e;
            }
            
        } catch (Exception $e) {
            $error_message = $e->getMessage();
        }
    }
}

require_once 'includes/header.php';
?>

<div class="container" style="max-width: 1200px; margin: 2rem auto;">
    <!-- Header -->
    <div class="checkout-header">
        <h1>Secure Checkout</h1>
        <div class="checkout-steps">
            <div class="step active">
                <span class="step-number">1</span>
                <span class="step-label">Review Order</span>
            </div>
            <div class="step active">
                <span class="step-number">2</span>
                <span class="step-label">Shipping & Payment</span>
            </div>
            <div class="step">
                <span class="step-number">3</span>
                <span class="step-label">Confirmation</span>
            </div>
        </div>
    </div>

    <!-- Error Message -->
    <?php if ($error_message): ?>
        <div class="alert alert-error">
            <?php echo htmlspecialchars($error_message); ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="" id="checkout-form">
        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
        
        <div class="row">
            <!-- Checkout Form -->
            <div class="col-8">
                <!-- Order Review -->
                <div class="card mb-3">
                    <div class="card-header">
                        <h5>Order Review</h5>
                    </div>
                    <div class="card-body">
                        <?php foreach ($vendors as $vendor): ?>
                            <div class="vendor-section">
                                <h6 style="color: var(--primary-green); margin-bottom: 1rem;">
                                    <i class="fas fa-store"></i> <?php echo htmlspecialchars($vendor['name']); ?>
                                </h6>
                                
                                <?php foreach ($vendor['items'] as $item): ?>
                                    <div class="checkout-item">
                                        <img src="<?php echo $item['image_url'] ?: 'assets/images/product-placeholder.png'; ?>" 
                                             alt="<?php echo htmlspecialchars($item['name']); ?>" 
                                             class="item-image">
                                        <div class="item-details">
                                            <h6><?php echo htmlspecialchars($item['name']); ?></h6>
                                            <p>Quantity: <?php echo $item['quantity']; ?> × <?php echo formatCurrency($item['price']); ?></p>
                                        </div>
                                        <div class="item-total">
                                            <?php echo formatCurrency($item['item_total']); ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Shipping Address -->
                <div class="card mb-3">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5>Shipping Address</h5>
                        <a href="addresses.php" class="btn btn-outline btn-sm">Manage Addresses</a>
                    </div>
                    <div class="card-body">
                        <?php if (empty($addresses)): ?>
                            <div class="text-center">
                                <p>No addresses found. Please add a shipping address.</p>
                                <a href="addresses.php" class="btn btn-primary">Add Address</a>
                            </div>
                        <?php else: ?>
                            <?php foreach ($addresses as $address): ?>
                                <label class="address-option">
                                    <input type="radio" name="shipping_address" value="<?php echo $address['id']; ?>" 
                                           <?php echo $address['is_default'] ? 'checked' : ''; ?> required>
                                    <div class="address-card">
                                        <div class="address-header">
                                            <strong><?php echo htmlspecialchars($address['full_name']); ?></strong>
                                            <?php if ($address['is_default']): ?>
                                                <span class="badge" style="background: var(--primary-green); color: white;">Default</span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="address-details">
                                            <?php echo htmlspecialchars($address['address_line_1']); ?><br>
                                            <?php if ($address['address_line_2']): ?>
                                                <?php echo htmlspecialchars($address['address_line_2']); ?><br>
                                            <?php endif; ?>
                                            <?php echo htmlspecialchars($address['city'] . ', ' . $address['state'] . ' ' . $address['postal_code']); ?><br>
                                            <?php echo htmlspecialchars($address['country']); ?>
                                        </div>
                                        <?php if ($address['phone']): ?>
                                            <div class="address-phone">
                                                <i class="fas fa-phone"></i> <?php echo htmlspecialchars($address['phone']); ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </label>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Billing Address -->
                <div class="card mb-3">
                    <div class="card-header">
                        <h5>Billing Address</h5>
                    </div>
                    <div class="card-body">
                        <label style="display: flex; align-items: center; margin-bottom: 1rem; cursor: pointer;">
                            <input type="checkbox" id="same-as-shipping" style="margin-right: 0.5rem;" checked>
                            Same as shipping address
                        </label>
                        
                        <div id="billing-addresses" style="display: none;">
                            <?php foreach ($addresses as $address): ?>
                                <label class="address-option">
                                    <input type="radio" name="billing_address" value="<?php echo $address['id']; ?>" 
                                           <?php echo $address['is_default'] ? 'checked' : ''; ?>>
                                    <div class="address-card">
                                        <div class="address-header">
                                            <strong><?php echo htmlspecialchars($address['full_name']); ?></strong>
                                            <?php if ($address['is_default']): ?>
                                                <span class="badge" style="background: var(--primary-green); color: white;">Default</span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="address-details">
                                            <?php echo htmlspecialchars($address['address_line_1']); ?><br>
                                            <?php if ($address['address_line_2']): ?>
                                                <?php echo htmlspecialchars($address['address_line_2']); ?><br>
                                            <?php endif; ?>
                                            <?php echo htmlspecialchars($address['city'] . ', ' . $address['state'] . ' ' . $address['postal_code']); ?><br>
                                            <?php echo htmlspecialchars($address['country']); ?>
                                        </div>
                                    </div>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- Payment Method -->
                <div class="card mb-3">
                    <div class="card-header">
                        <h5>Payment Method</h5>
                    </div>
                    <div class="card-body">
                        <div class="payment-methods">
                            <label class="payment-option">
                                <input type="radio" name="payment_method" value="credit_card" required>
                                <div class="payment-card">
                                    <i class="fas fa-credit-card" style="font-size: 1.5rem; color: var(--primary-green);"></i>
                                    <div>
                                        <strong>Credit/Debit Card</strong>
                                        <p>Visa, Mastercard, American Express</p>
                                    </div>
                                </div>
                            </label>
                            
                            <label class="payment-option">
                                <input type="radio" name="payment_method" value="mobile_money" required>
                                <div class="payment-card">
                                    <i class="fas fa-mobile-alt" style="font-size: 1.5rem; color: var(--secondary-green);"></i>
                                    <div>
                                        <strong>Mobile Money</strong>
                                        <p>MTN Mobile Money, Airtel Money</p>
                                    </div>
                                </div>
                            </label>
                            
                            <label class="payment-option">
                                <input type="radio" name="payment_method" value="bank_transfer" required>
                                <div class="payment-card">
                                    <i class="fas fa-university" style="font-size: 1.5rem; color: var(--primary-green);"></i>
                                    <div>
                                        <strong>Bank Transfer</strong>
                                        <p>Direct bank transfer</p>
                                    </div>
                                </div>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Special Instructions -->
                <div class="card mb-3">
                    <div class="card-header">
                        <h5>Special Instructions</h5>
                    </div>
                    <div class="card-body">
                        <textarea name="special_instructions" class="form-control" rows="3" 
                                  placeholder="Any special delivery instructions or notes for the vendors..."></textarea>
                    </div>
                </div>
            </div>

            <!-- Order Summary -->
            <div class="col-4">
                <div class="card order-summary">
                    <div class="card-header">
                        <h5>Order Summary</h5>
                    </div>
                    
                    <div class="card-body">
                        <div class="summary-row">
                            <span>Subtotal (<?php echo $order_summary['item_count']; ?> items)</span>
                            <strong><?php echo formatCurrency($order_summary['subtotal']); ?></strong>
                        </div>
                        
                        <div class="summary-row">
                            <span>Shipping (<?php echo count($vendors); ?> vendors)</span>
                            <strong><?php echo formatCurrency($order_summary['shipping']); ?></strong>
                        </div>
                        
                        <div class="summary-row">
                            <span>Tax (18% VAT)</span>
                            <strong><?php echo formatCurrency($order_summary['tax']); ?></strong>
                        </div>
                        
                        <hr>
                        
                        <div class="summary-row total-row">
                            <span>Total</span>
                            <strong><?php echo formatCurrency($order_summary['total']); ?></strong>
                        </div>
                        
                        <button type="submit" class="btn btn-primary btn-lg" style="width: 100%; margin-top: 2rem;">
                            <i class="fas fa-lock"></i> Place Order
                        </button>
                        
                        <div style="text-align: center; margin-top: 1rem;">
                            <small class="text-muted">
                                By placing this order, you agree to our 
                                <a href="terms.php">Terms of Service</a> and 
                                <a href="privacy.php">Privacy Policy</a>
                            </small>
                        </div>
                    </div>
                </div>
                
                <!-- Security Info -->
                <div class="card mt-3">
                    <div class="card-body text-center">
                        <div style="color: var(--primary-green); margin-bottom: 1rem;">
                            <i class="fas fa-shield-alt" style="font-size: 2rem;"></i>
                        </div>
                        <h6>256-bit SSL Encryption</h6>
                        <p style="font-size: 0.9rem; color: var(--dark-gray); margin-bottom: 0;">
                            Your payment information is encrypted and secure.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<style>
.checkout-header {
    text-align: center;
    margin-bottom: 2rem;
}

.checkout-steps {
    display: flex;
    justify-content: center;
    gap: 2rem;
    margin-top: 1rem;
}

.step {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: var(--medium-gray);
}

.step.active {
    color: var(--primary-green);
}

.step-number {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: var(--light-gray);
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
}

.step.active .step-number {
    background: var(--primary-green);
    color: white;
}

.vendor-section {
    margin-bottom: 2rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid var(--light-gray);
}

.vendor-section:last-child {
    border-bottom: none;
    margin-bottom: 0;
}

.checkout-item {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem 0;
    border-bottom: 1px solid var(--light-gray);
}

.checkout-item:last-child {
    border-bottom: none;
}

.item-image {
    width: 60px;
    height: 60px;
    object-fit: cover;
    border-radius: var(--border-radius);
}

.item-details {
    flex: 1;
}

.item-details h6 {
    margin-bottom: 0.25rem;
}

.item-details p {
    margin-bottom: 0;
    color: var(--dark-gray);
}

.item-total {
    font-weight: bold;
    color: var(--primary-green);
}

.address-option {
    display: block;
    margin-bottom: 1rem;
    cursor: pointer;
}

.address-card {
    border: 2px solid var(--light-gray);
    border-radius: var(--border-radius);
    padding: 1rem;
    transition: all 0.3s ease;
}

.address-option input:checked + .address-card {
    border-color: var(--primary-green);
    background: rgba(46, 125, 50, 0.05);
}

.address-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.5rem;
}

.address-details {
    color: var(--dark-gray);
    line-height: 1.4;
    margin-bottom: 0.5rem;
}

.address-phone {
    color: var(--dark-gray);
    font-size: 0.9rem;
}

.payment-option {
    display: block;
    margin-bottom: 1rem;
    cursor: pointer;
}

.payment-card {
    border: 2px solid var(--light-gray);
    border-radius: var(--border-radius);
    padding: 1rem;
    display: flex;
    align-items: center;
    gap: 1rem;
    transition: all 0.3s ease;
}

.payment-option input:checked + .payment-card {
    border-color: var(--primary-green);
    background: rgba(46, 125, 50, 0.05);
}

.payment-card p {
    margin-bottom: 0;
    color: var(--dark-gray);
    font-size: 0.9rem;
}

.order-summary {
    position: sticky;
    top: 2rem;
}

.summary-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
}

.total-row {
    font-size: 1.2rem;
    font-weight: bold;
    color: var(--primary-green);
}

@media (max-width: 768px) {
    .col-4, .col-8 {
        flex: 0 0 100%;
        margin-bottom: 2rem;
    }
    
    .checkout-steps {
        flex-direction: column;
        gap: 1rem;
    }
    
    .order-summary {
        position: static;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const sameAsShipping = document.getElementById('same-as-shipping');
    const billingAddresses = document.getElementById('billing-addresses');
    const shippingRadios = document.querySelectorAll('input[name="shipping_address"]');
    const billingRadios = document.querySelectorAll('input[name="billing_address"]');
    
    // Handle same as shipping checkbox
    sameAsShipping.addEventListener('change', function() {
        if (this.checked) {
            billingAddresses.style.display = 'none';
            // Copy shipping address selection to billing
            const selectedShipping = document.querySelector('input[name="shipping_address"]:checked');
            if (selectedShipping) {
                const correspondingBilling = document.querySelector(`input[name="billing_address"][value="${selectedShipping.value}"]`);
                if (correspondingBilling) {
                    correspondingBilling.checked = true;
                }
            }
        } else {
            billingAddresses.style.display = 'block';
        }
    });
    
    // Auto-sync billing address when shipping changes (if same as shipping is checked)
    shippingRadios.forEach(radio => {
        radio.addEventListener('change', function() {
            if (sameAsShipping.checked) {
                const correspondingBilling = document.querySelector(`input[name="billing_address"][value="${this.value}"]`);
                if (correspondingBilling) {
                    correspondingBilling.checked = true;
                }
            }
        });
    });
    
    // Form validation
    document.getElementById('checkout-form').addEventListener('submit', function(e) {
        const shippingAddress = document.querySelector('input[name="shipping_address"]:checked');
        const billingAddress = document.querySelector('input[name="billing_address"]:checked');
        const paymentMethod = document.querySelector('input[name="payment_method"]:checked');
        
        if (!shippingAddress) {
            alert('Please select a shipping address.');
            e.preventDefault();
            return false;
        }
        
        if (!billingAddress) {
            alert('Please select a billing address.');
            e.preventDefault();
            return false;
        }
        
        if (!paymentMethod) {
            alert('Please select a payment method.');
            e.preventDefault();
            return false;
        }
        
        // Show loading state
        const submitBtn = this.querySelector('button[type="submit"]');
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
        submitBtn.disabled = true;
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>
