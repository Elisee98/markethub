<?php
/**
 * Improved Checkout Page - Enhanced UX and Payment Flow
 */

require_once 'config/config.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirect('login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
}

$customer_id = $_SESSION['user_id'];
$customer = $database->fetch("SELECT * FROM users WHERE id = ?", [$customer_id]);

$page_title = 'Checkout';
$error_message = '';
$success_message = '';

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

// Get customer addresses
$addresses_sql = "SELECT *,
                         COALESCE(full_name, '') as full_name,
                         COALESCE(phone, '') as phone
                  FROM user_addresses
                  WHERE user_id = ?
                  ORDER BY is_default DESC, created_at DESC";
$addresses = $database->fetchAll($addresses_sql, [$customer_id]);

// Calculate order summary
$order_summary = [
    'subtotal' => 0,
    'item_count' => 0,
    'shipping' => 0,
    'tax' => 0,
    'total' => 0
];

$vendors = [];
foreach ($cart_items as $item) {
    $order_summary['subtotal'] += $item['item_total'];
    $order_summary['item_count'] += $item['quantity'];
    
    if (!isset($vendors[$item['vendor_id']])) {
        $vendors[$item['vendor_id']] = [
            'id' => $item['vendor_id'],
            'name' => $item['store_name'] ?: $item['vendor_name'],
            'items' => []
        ];
    }
    $vendors[$item['vendor_id']]['items'][] = $item;
}

// Calculate shipping (simplified - 2000 RWF per vendor)
$order_summary['shipping'] = count($vendors) * 2000;

// Calculate tax (simplified - 18% VAT)
$order_summary['tax'] = $order_summary['subtotal'] * 0.18;

// Calculate total
$order_summary['total'] = $order_summary['subtotal'] + $order_summary['shipping'] + $order_summary['tax'];

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
            if (!in_array($payment_method, ['mobile_money', 'bank_transfer', 'cash_on_delivery'])) {
                throw new Exception('Please select a valid payment method.');
            }
            
            $database->beginTransaction();
            
            try {
                // Create main order
                $order_number = 'ORD-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));

                // Check if new columns exist, if not use basic order creation
                $columns_check = $database->fetch("SHOW COLUMNS FROM orders LIKE 'order_number'");

                if ($columns_check) {
                    // Use new enhanced order structure
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
                } else {
                    // Use basic order structure for compatibility
                    $order_sql = "INSERT INTO orders
                                  (customer_id, total_amount, payment_method, payment_status, status, notes, created_at)
                                  VALUES (?, ?, ?, 'pending', 'pending', ?, NOW())";

                    $notes = "Order Number: $order_number\nSpecial Instructions: $special_instructions";
                    $order_id = $database->insert($order_sql, [
                        $customer_id, $order_summary['total'], $payment_method, $notes
                    ]);
                }
                
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
                $database->execute("DELETE FROM cart_items WHERE customer_id = ?", [$customer_id]);
                
                $database->commit();
                
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

<style>
/* Enhanced Checkout Styles */
:root {
    --primary-color: #10b981;
    --secondary-color: #059669;
    --accent-color: #34d399;
    --text-dark: #1f2937;
    --text-light: #6b7280;
    --bg-light: #f9fafb;
    --white: #ffffff;
    --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    --shadow-lg: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
    --border-radius: 12px;
}

.checkout-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 2rem;
}

.checkout-header {
    text-align: center;
    margin-bottom: 3rem;
}

.checkout-header h1 {
    font-size: 2.5rem;
    font-weight: 700;
    color: var(--text-dark);
    margin-bottom: 0.5rem;
}

.checkout-header p {
    color: var(--text-light);
    font-size: 1.1rem;
}

.checkout-steps {
    display: flex;
    justify-content: center;
    align-items: center;
    margin: 2rem 0;
    gap: 1rem;
}

.step {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.5rem;
}

.step-number {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: #e5e7eb;
    color: var(--text-light);
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    transition: all 0.3s;
}

.step.active .step-number {
    background: var(--primary-color);
    color: white;
}

.step-label {
    font-size: 0.9rem;
    color: var(--text-light);
    font-weight: 500;
}

.step.active .step-label {
    color: var(--primary-color);
    font-weight: 600;
}

.step-line {
    width: 60px;
    height: 2px;
    background: #e5e7eb;
}

.checkout-content {
    display: grid;
    grid-template-columns: 1fr 400px;
    gap: 3rem;
}

.checkout-form {
    display: flex;
    flex-direction: column;
    gap: 2rem;
}

.form-section {
    background: white;
    border-radius: var(--border-radius);
    box-shadow: var(--shadow);
    overflow: hidden;
}

.section-header {
    padding: 1.5rem;
    background: var(--bg-light);
    border-bottom: 1px solid #e5e7eb;
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.section-header h3 {
    margin: 0;
    font-size: 1.25rem;
    font-weight: 600;
    color: var(--text-dark);
}

.section-content {
    padding: 1.5rem;
}

/* Responsive Design */
@media (max-width: 768px) {
    .checkout-container {
        padding: 1rem;
    }
    
    .checkout-content {
        grid-template-columns: 1fr;
        gap: 2rem;
    }
    
    .checkout-steps {
        gap: 0.5rem;
    }
    
    .step-line {
        width: 40px;
    }
}
</style>

<div class="checkout-container">
    <!-- Header -->
    <div class="checkout-header">
        <h1>🛒 Secure Checkout</h1>
        <p>Complete your purchase safely and securely</p>
        
        <!-- Progress Steps -->
        <div class="checkout-steps">
            <div class="step active">
                <div class="step-number">1</div>
                <div class="step-label">Review</div>
            </div>
            <div class="step-line"></div>
            <div class="step active">
                <div class="step-number">2</div>
                <div class="step-label">Payment</div>
            </div>
            <div class="step-line"></div>
            <div class="step">
                <div class="step-number">3</div>
                <div class="step-label">Complete</div>
            </div>
        </div>
    </div>

    <?php if ($error_message): ?>
        <div style="background: #fee2e2; color: #991b1b; padding: 1rem 1.5rem; border-radius: var(--border-radius); margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.75rem;">
            <i class="fas fa-exclamation-circle"></i>
            <?php echo htmlspecialchars($error_message); ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="" id="checkout-form">
        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
        
        <div class="checkout-content">
            <!-- Left Column - Form -->
            <div class="checkout-form">

                <!-- Order Review -->
                <div class="form-section">
                    <div class="section-header">
                        <i class="fas fa-shopping-cart"></i>
                        <h3>Order Review (<?php echo $order_summary['item_count']; ?> items)</h3>
                    </div>
                    <div class="section-content">
                        <?php foreach ($vendors as $vendor): ?>
                            <div style="margin-bottom: 2rem; padding-bottom: 1.5rem; border-bottom: 1px solid #e5e7eb;">
                                <h4 style="color: var(--primary-color); margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem;">
                                    <i class="fas fa-store"></i> <?php echo htmlspecialchars($vendor['name']); ?>
                                </h4>
                                <?php foreach ($vendor['items'] as $item): ?>
                                    <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 1rem; padding: 1rem; background: var(--bg-light); border-radius: 8px;">
                                        <?php if ($item['image_url']): ?>
                                            <img src="<?php echo htmlspecialchars($item['image_url']); ?>"
                                                 alt="<?php echo htmlspecialchars($item['name']); ?>"
                                                 style="width: 60px; height: 60px; object-fit: cover; border-radius: 8px;">
                                        <?php else: ?>
                                            <div style="width: 60px; height: 60px; background: #e5e7eb; border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                                                <i class="fas fa-image" style="color: #9ca3af;"></i>
                                            </div>
                                        <?php endif; ?>
                                        <div style="flex: 1;">
                                            <h5 style="margin: 0 0 0.25rem 0; color: var(--text-dark);"><?php echo htmlspecialchars($item['name']); ?></h5>
                                            <p style="margin: 0; color: var(--text-light); font-size: 0.9rem;">
                                                Qty: <?php echo $item['quantity']; ?> × <?php echo formatCurrency($item['price']); ?>
                                            </p>
                                        </div>
                                        <div style="text-align: right;">
                                            <strong style="color: var(--text-dark);"><?php echo formatCurrency($item['item_total']); ?></strong>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Shipping Address -->
                <div class="form-section">
                    <div class="section-header">
                        <i class="fas fa-truck"></i>
                        <h3>Delivery Address</h3>
                        <div style="margin-left: auto;">
                            <a href="addresses.php" style="color: var(--primary-color); text-decoration: none; font-size: 0.9rem;">
                                <i class="fas fa-plus"></i> Add New
                            </a>
                        </div>
                    </div>
                    <div class="section-content">
                        <?php if (empty($addresses)): ?>
                            <div style="text-align: center; padding: 2rem;">
                                <i class="fas fa-map-marker-alt" style="font-size: 3rem; color: var(--text-light); margin-bottom: 1rem;"></i>
                                <p style="color: var(--text-light); margin-bottom: 1.5rem;">No delivery addresses found</p>
                                <a href="addresses.php" style="background: var(--primary-color); color: white; padding: 0.75rem 1.5rem; border-radius: 8px; text-decoration: none;">
                                    <i class="fas fa-plus"></i> Add Address
                                </a>
                            </div>
                        <?php else: ?>
                            <div style="display: grid; gap: 1rem;">
                                <?php foreach ($addresses as $address): ?>
                                    <label style="display: block; cursor: pointer; position: relative;">
                                        <input type="radio" name="shipping_address" value="<?php echo $address['id']; ?>"
                                               <?php echo $address['is_default'] ? 'checked' : ''; ?>
                                               style="position: absolute; opacity: 0;" required>
                                        <div style="border: 2px solid #e5e7eb; border-radius: 12px; padding: 1.5rem; transition: all 0.3s; background: white;" class="address-card">
                                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem;">
                                                <strong style="color: var(--text-dark);"><?php echo htmlspecialchars($address['full_name'] ?? $customer['username']); ?></strong>
                                                <?php if ($address['is_default']): ?>
                                                    <span style="background: var(--primary-color); color: white; padding: 0.25rem 0.75rem; border-radius: 20px; font-size: 0.75rem;">Default</span>
                                                <?php endif; ?>
                                            </div>
                                            <div style="color: var(--text-light); line-height: 1.5;">
                                                <?php echo htmlspecialchars($address['address_line_1']); ?><br>
                                                <?php if (!empty($address['address_line_2'])): ?>
                                                    <?php echo htmlspecialchars($address['address_line_2']); ?><br>
                                                <?php endif; ?>
                                                <?php echo htmlspecialchars($address['city'] . ', ' . $address['state'] . ' ' . ($address['postal_code'] ?? '')); ?><br>
                                                <?php echo htmlspecialchars($address['country'] ?? 'Rwanda'); ?>
                                            </div>
                                            <?php if (!empty($address['phone'])): ?>
                                                <div style="margin-top: 0.5rem; color: var(--text-light); font-size: 0.9rem;">
                                                    <i class="fas fa-phone"></i> <?php echo htmlspecialchars($address['phone']); ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Billing Address -->
                <div class="form-section">
                    <div class="section-header">
                        <i class="fas fa-receipt"></i>
                        <h3>Billing Address</h3>
                    </div>
                    <div class="section-content">
                        <label style="display: flex; align-items: center; margin-bottom: 1.5rem; cursor: pointer;">
                            <input type="checkbox" id="same-as-shipping" style="margin-right: 0.75rem;" checked>
                            <span style="color: var(--text-dark); font-weight: 500;">Same as delivery address</span>
                        </label>

                        <div id="billing-addresses" style="display: none;">
                            <div style="display: grid; gap: 1rem;">
                                <?php foreach ($addresses as $address): ?>
                                    <label style="display: block; cursor: pointer; position: relative;">
                                        <input type="radio" name="billing_address" value="<?php echo $address['id']; ?>"
                                               <?php echo $address['is_default'] ? 'checked' : ''; ?>
                                               style="position: absolute; opacity: 0;">
                                        <div style="border: 2px solid #e5e7eb; border-radius: 12px; padding: 1.5rem; transition: all 0.3s; background: white;" class="address-card">
                                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem;">
                                                <strong style="color: var(--text-dark);"><?php echo htmlspecialchars($address['full_name'] ?? $customer['username']); ?></strong>
                                                <?php if ($address['is_default']): ?>
                                                    <span style="background: var(--primary-color); color: white; padding: 0.25rem 0.75rem; border-radius: 20px; font-size: 0.75rem;">Default</span>
                                                <?php endif; ?>
                                            </div>
                                            <div style="color: var(--text-light); line-height: 1.5;">
                                                <?php echo htmlspecialchars($address['address_line_1']); ?><br>
                                                <?php if (!empty($address['address_line_2'])): ?>
                                                    <?php echo htmlspecialchars($address['address_line_2']); ?><br>
                                                <?php endif; ?>
                                                <?php echo htmlspecialchars($address['city'] . ', ' . $address['state'] . ' ' . ($address['postal_code'] ?? '')); ?><br>
                                                <?php echo htmlspecialchars($address['country'] ?? 'Rwanda'); ?>
                                            </div>
                                        </div>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Payment Methods -->
                <div class="form-section">
                    <div class="section-header">
                        <i class="fas fa-credit-card"></i>
                        <h3>Payment Method</h3>
                    </div>
                    <div class="section-content">
                        <div style="display: grid; gap: 1rem;">

                            <!-- Mobile Money -->
                            <label style="display: block; cursor: pointer; position: relative;">
                                <input type="radio" name="payment_method" value="mobile_money" style="position: absolute; opacity: 0;" required>
                                <div style="border: 2px solid #e5e7eb; border-radius: 12px; padding: 1.5rem; display: flex; align-items: center; gap: 1rem; transition: all 0.3s; background: white;" class="payment-card">
                                    <div style="font-size: 2rem; color: var(--primary-color);">📱</div>
                                    <div style="flex: 1;">
                                        <h4 style="margin: 0 0 0.25rem 0; font-size: 1.1rem; font-weight: 600; color: var(--text-dark);">Mobile Money</h4>
                                        <p style="margin: 0; color: var(--text-light); font-size: 0.9rem;">MTN Mobile Money, Airtel Money</p>
                                        <div style="margin-top: 0.5rem; display: flex; gap: 0.5rem;">
                                            <span style="background: #ffcc02; color: #000; padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.75rem; font-weight: 600;">MTN</span>
                                            <span style="background: #e60012; color: white; padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.75rem; font-weight: 600;">Airtel</span>
                                        </div>
                                    </div>
                                    <div style="color: var(--primary-color); font-weight: 600;">
                                        <i class="fas fa-check-circle" style="font-size: 1.5rem;"></i>
                                    </div>
                                </div>
                            </label>

                            <!-- Bank Transfer -->
                            <label style="display: block; cursor: pointer; position: relative;">
                                <input type="radio" name="payment_method" value="bank_transfer" style="position: absolute; opacity: 0;">
                                <div style="border: 2px solid #e5e7eb; border-radius: 12px; padding: 1.5rem; display: flex; align-items: center; gap: 1rem; transition: all 0.3s; background: white;" class="payment-card">
                                    <div style="font-size: 2rem; color: var(--primary-color);">🏦</div>
                                    <div style="flex: 1;">
                                        <h4 style="margin: 0 0 0.25rem 0; font-size: 1.1rem; font-weight: 600; color: var(--text-dark);">Bank Transfer</h4>
                                        <p style="margin: 0; color: var(--text-light); font-size: 0.9rem;">Direct bank transfer to vendor accounts</p>
                                        <div style="margin-top: 0.5rem;">
                                            <span style="background: var(--bg-light); color: var(--text-dark); padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.75rem;">2-3 business days</span>
                                        </div>
                                    </div>
                                    <div style="color: var(--primary-color); font-weight: 600;">
                                        <i class="fas fa-university" style="font-size: 1.5rem;"></i>
                                    </div>
                                </div>
                            </label>

                            <!-- Cash on Delivery -->
                            <label style="display: block; cursor: pointer; position: relative;">
                                <input type="radio" name="payment_method" value="cash_on_delivery" style="position: absolute; opacity: 0;">
                                <div style="border: 2px solid #e5e7eb; border-radius: 12px; padding: 1.5rem; display: flex; align-items: center; gap: 1rem; transition: all 0.3s; background: white;" class="payment-card">
                                    <div style="font-size: 2rem; color: var(--primary-color);">💵</div>
                                    <div style="flex: 1;">
                                        <h4 style="margin: 0 0 0.25rem 0; font-size: 1.1rem; font-weight: 600; color: var(--text-dark);">Cash on Delivery</h4>
                                        <p style="margin: 0; color: var(--text-light); font-size: 0.9rem;">Pay when you receive your order</p>
                                        <div style="margin-top: 0.5rem;">
                                            <span style="background: #fef3c7; color: #92400e; padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.75rem;">Most Popular</span>
                                        </div>
                                    </div>
                                    <div style="color: var(--primary-color); font-weight: 600;">
                                        <i class="fas fa-hand-holding-usd" style="font-size: 1.5rem;"></i>
                                    </div>
                                </div>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Special Instructions -->
                <div class="form-section">
                    <div class="section-header">
                        <i class="fas fa-sticky-note"></i>
                        <h3>Special Instructions (Optional)</h3>
                    </div>
                    <div class="section-content">
                        <textarea name="special_instructions"
                                  style="width: 100%; padding: 1rem; border: 2px solid #e5e7eb; border-radius: 8px; font-size: 1rem; resize: vertical; min-height: 100px;"
                                  placeholder="Any special delivery instructions, gift message, or notes for the vendors..."></textarea>
                        <div style="margin-top: 0.5rem; color: var(--text-light); font-size: 0.9rem;">
                            <i class="fas fa-info-circle"></i> This will be shared with all vendors in your order
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column - Order Summary -->
            <div style="position: sticky; top: 2rem; height: fit-content;">
                <div style="background: white; border-radius: var(--border-radius); box-shadow: var(--shadow); overflow: hidden;">
                    <div style="padding: 1.5rem; background: var(--bg-light); border-bottom: 1px solid #e5e7eb;">
                        <h3 style="margin: 0; font-size: 1.25rem; font-weight: 600; color: var(--text-dark); display: flex; align-items: center; gap: 0.5rem;">
                            <i class="fas fa-receipt"></i> Order Summary
                        </h3>
                    </div>

                    <div style="padding: 1.5rem;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                            <span style="color: var(--text-light);">Subtotal (<?php echo $order_summary['item_count']; ?> items)</span>
                            <strong style="color: var(--text-dark);"><?php echo formatCurrency($order_summary['subtotal']); ?></strong>
                        </div>

                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                            <span style="color: var(--text-light);">Shipping (<?php echo count($vendors); ?> vendors)</span>
                            <strong style="color: var(--text-dark);"><?php echo formatCurrency($order_summary['shipping']); ?></strong>
                        </div>

                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                            <span style="color: var(--text-light);">Tax (18% VAT)</span>
                            <strong style="color: var(--text-dark);"><?php echo formatCurrency($order_summary['tax']); ?></strong>
                        </div>

                        <hr style="border: none; border-top: 2px solid #e5e7eb; margin: 1.5rem 0;">

                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
                            <span style="font-size: 1.1rem; font-weight: 600; color: var(--text-dark);">Total</span>
                            <strong style="font-size: 1.5rem; color: var(--primary-color);"><?php echo formatCurrency($order_summary['total']); ?></strong>
                        </div>

                        <button type="submit" style="width: 100%; background: var(--primary-color); color: white; border: none; padding: 1.25rem; border-radius: var(--border-radius); font-size: 1.1rem; font-weight: 600; cursor: pointer; transition: all 0.3s; display: flex; align-items: center; justify-content: center; gap: 0.5rem;">
                            <i class="fas fa-lock"></i> Complete Order
                        </button>

                        <div style="text-align: center; margin-top: 1.5rem; padding: 1rem; background: var(--bg-light); border-radius: 8px;">
                            <div style="color: var(--primary-color); margin-bottom: 0.5rem;">
                                <i class="fas fa-shield-alt" style="font-size: 2rem;"></i>
                            </div>
                            <h6 style="margin: 0 0 0.5rem 0; color: var(--text-dark);">Secure Checkout</h6>
                            <p style="margin: 0; font-size: 0.9rem; color: var(--text-light);">
                                Your information is encrypted and secure
                            </p>
                        </div>

                        <div style="text-align: center; margin-top: 1rem;">
                            <small style="color: var(--text-light);">
                                By placing this order, you agree to our
                                <a href="terms.php" style="color: var(--primary-color);">Terms of Service</a> and
                                <a href="privacy.php" style="color: var(--primary-color);">Privacy Policy</a>
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
// Enhanced checkout functionality
document.addEventListener('DOMContentLoaded', function() {
    // Handle address card selection
    const addressCards = document.querySelectorAll('.address-card');
    const paymentCards = document.querySelectorAll('.payment-card');

    // Address selection styling
    document.querySelectorAll('input[name="shipping_address"]').forEach(radio => {
        radio.addEventListener('change', function() {
            addressCards.forEach(card => {
                card.style.borderColor = '#e5e7eb';
                card.style.background = 'white';
            });
            if (this.checked) {
                const card = this.nextElementSibling;
                card.style.borderColor = 'var(--primary-color)';
                card.style.background = 'rgba(16, 185, 129, 0.05)';
            }
        });
    });

    // Payment method selection styling
    document.querySelectorAll('input[name="payment_method"]').forEach(radio => {
        radio.addEventListener('change', function() {
            paymentCards.forEach(card => {
                card.style.borderColor = '#e5e7eb';
                card.style.background = 'white';
            });
            if (this.checked) {
                const card = this.nextElementSibling;
                card.style.borderColor = 'var(--primary-color)';
                card.style.background = 'rgba(16, 185, 129, 0.05)';
            }
        });
    });

    // Same as shipping checkbox
    const sameAsShipping = document.getElementById('same-as-shipping');
    const billingAddresses = document.getElementById('billing-addresses');

    sameAsShipping.addEventListener('change', function() {
        if (this.checked) {
            billingAddresses.style.display = 'none';
            // Copy shipping address to billing
            const shippingAddress = document.querySelector('input[name="shipping_address"]:checked');
            if (shippingAddress) {
                const billingRadio = document.querySelector(`input[name="billing_address"][value="${shippingAddress.value}"]`);
                if (billingRadio) {
                    billingRadio.checked = true;
                }
            }
        } else {
            billingAddresses.style.display = 'block';
        }
    });

    // Form validation
    const form = document.getElementById('checkout-form');
    form.addEventListener('submit', function(e) {
        const shippingAddress = document.querySelector('input[name="shipping_address"]:checked');
        const paymentMethod = document.querySelector('input[name="payment_method"]:checked');

        if (!shippingAddress) {
            e.preventDefault();
            alert('Please select a delivery address.');
            return;
        }

        if (!paymentMethod) {
            e.preventDefault();
            alert('Please select a payment method.');
            return;
        }

        // Show loading state
        const submitBtn = form.querySelector('button[type="submit"]');
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
        submitBtn.disabled = true;
    });

    // Initialize default selections
    const defaultShipping = document.querySelector('input[name="shipping_address"]:checked');
    if (defaultShipping) {
        defaultShipping.dispatchEvent(new Event('change'));
    }

    // Auto-select mobile money as default
    const mobileMoneyRadio = document.querySelector('input[name="payment_method"][value="mobile_money"]');
    if (mobileMoneyRadio) {
        mobileMoneyRadio.checked = true;
        mobileMoneyRadio.dispatchEvent(new Event('change'));
    }
});
</script>

<?php require_once 'includes/footer.php'; ?>
