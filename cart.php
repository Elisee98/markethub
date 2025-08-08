<?php
/**
 * MarketHub Shopping Cart
 * Multi-Vendor E-Commerce Platform
 */

require_once 'config/config.php';

$page_title = 'Shopping Cart';

$cart_items = [];
$cart_summary = [
    'subtotal' => 0,
    'shipping' => 0,
    'tax' => 0,
    'total' => 0,
    'item_count' => 0
];

// Get cart items
$customer_id = isLoggedIn() ? $_SESSION['user_id'] : null;

if ($customer_id) {
    // Logged in user - get from database
    $cart_sql = "
        SELECT ci.*, p.name, p.price, p.stock_quantity, p.weight, pi.image_url,
               u.username as vendor_name, vs.store_name, vs.id as vendor_store_id,
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
    
} else {
    // Guest user - get from session
    if (!empty($_SESSION['cart_items'])) {
        $product_ids = array_column($_SESSION['cart_items'], 'product_id');
        $placeholders = str_repeat('?,', count($product_ids) - 1) . '?';
        
        $products_sql = "
            SELECT p.id, p.name, p.price, p.stock_quantity, p.weight, pi.image_url,
                   u.username as vendor_name, vs.store_name, vs.id as vendor_store_id
            FROM products p
            LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
            LEFT JOIN users u ON p.vendor_id = u.id
            LEFT JOIN vendor_stores vs ON u.id = vs.vendor_id
            WHERE p.id IN ($placeholders) AND p.status = 'active'
        ";
        
        $products = $database->fetchAll($products_sql, $product_ids);
        
        // Merge with session cart data
        foreach ($_SESSION['cart_items'] as $cart_item) {
            $product = array_filter($products, function($p) use ($cart_item) {
                return $p['id'] == $cart_item['product_id'];
            });
            
            if (!empty($product)) {
                $product = array_values($product)[0];
                $cart_items[] = array_merge($product, [
                    'product_id' => $cart_item['product_id'],
                    'quantity' => $cart_item['quantity'],
                    'item_total' => $cart_item['quantity'] * $product['price']
                ]);
            }
        }
    }
}

// Group items by vendor
$vendors = [];
foreach ($cart_items as $item) {
    $vendor_key = $item['vendor_store_id'] ?: $item['vendor_name'];
    if (!isset($vendors[$vendor_key])) {
        $vendors[$vendor_key] = [
            'name' => $item['store_name'] ?: $item['vendor_name'],
            'items' => [],
            'subtotal' => 0
        ];
    }
    $vendors[$vendor_key]['items'][] = $item;
    $vendors[$vendor_key]['subtotal'] += $item['item_total'];
}

// Calculate cart summary
foreach ($cart_items as $item) {
    $cart_summary['subtotal'] += $item['item_total'];
    $cart_summary['item_count'] += $item['quantity'];
}

// Calculate shipping (simplified - $5 per vendor)
$cart_summary['shipping'] = count($vendors) * 5;

// Calculate tax (simplified - 18% VAT)
$cart_summary['tax'] = $cart_summary['subtotal'] * 0.18;

// Calculate total
$cart_summary['total'] = $cart_summary['subtotal'] + $cart_summary['shipping'] + $cart_summary['tax'];

require_once 'includes/header.php';
?>

<div class="container" style="margin: 2rem auto;">
    <!-- Header -->
    <div class="row align-items-center mb-3">
        <div class="col-8">
            <h1>Shopping Cart</h1>
            <p class="text-muted">Review your items and proceed to checkout</p>
        </div>
        <div class="col-4 text-right">
            <a href="products.php" class="btn btn-outline">
                <i class="fas fa-arrow-left"></i> Continue Shopping
            </a>
        </div>
    </div>

    <?php if (empty($cart_items)): ?>
        <!-- Empty Cart -->
        <div class="card">
            <div class="card-body text-center" style="padding: 4rem;">
                <i class="fas fa-shopping-cart" style="font-size: 4rem; color: var(--medium-gray); margin-bottom: 2rem;"></i>
                <h3>Your Cart is Empty</h3>
                <p style="color: var(--dark-gray); font-size: 1.1rem; margin-bottom: 2rem;">
                    Looks like you haven't added any items to your cart yet. Start shopping to fill it up!
                </p>
                
                <div style="display: flex; justify-content: center; gap: 1rem; margin-bottom: 3rem;">
                    <a href="products.php" class="btn btn-primary btn-lg">
                        <i class="fas fa-search"></i> Browse Products
                    </a>
                    <a href="categories.php" class="btn btn-outline btn-lg">
                        <i class="fas fa-th-large"></i> Browse Categories
                    </a>
                </div>
                
                <!-- Shopping Tips -->
                <div style="background: var(--light-gray); padding: 2rem; border-radius: var(--border-radius); text-align: left; max-width: 600px; margin: 0 auto;">
                    <h5 style="color: var(--primary-green); margin-bottom: 1rem;">Shopping Tips:</h5>
                    <ul style="color: var(--dark-gray); line-height: 1.8;">
                        <li>Browse our featured products for the best deals</li>
                        <li>Use the search function to find specific items</li>
                        <li>Add items to your wishlist to save for later</li>
                        <li>Compare products from different vendors</li>
                    </ul>
                </div>
            </div>
        </div>
        
    <?php else: ?>
        <div class="row">
            <!-- Cart Items -->
            <div class="col-8">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 style="margin-bottom: 0;">Cart Items (<?php echo count($cart_items); ?> products)</h5>
                        <button onclick="clearCart()" class="btn btn-outline btn-sm">
                            <i class="fas fa-trash"></i> Clear Cart
                        </button>
                    </div>
                    
                    <div class="card-body">
                        <?php foreach ($vendors as $vendor_key => $vendor): ?>
                            <!-- Vendor Section -->
                            <div class="vendor-section">
                                <div class="vendor-header">
                                    <h6 style="color: var(--primary-green); margin-bottom: 1rem;">
                                        <i class="fas fa-store"></i> <?php echo htmlspecialchars($vendor['name']); ?>
                                        <span style="color: var(--dark-gray); font-weight: normal; font-size: 0.9rem;">
                                            (<?php echo count($vendor['items']); ?> items - <?php echo formatCurrency($vendor['subtotal']); ?>)
                                        </span>
                                    </h6>
                                </div>
                                
                                <?php foreach ($vendor['items'] as $item): ?>
                                    <div class="cart-item" data-product-id="<?php echo $item['product_id']; ?>">
                                        <div class="item-image">
                                            <img src="<?php echo $item['image_url'] ?: 'assets/images/product-placeholder.png'; ?>" 
                                                 alt="<?php echo htmlspecialchars($item['name']); ?>" 
                                                 onerror="this.src='assets/images/product-placeholder.png'">
                                        </div>
                                        
                                        <div class="item-details">
                                            <h6 class="item-title">
                                                <a href="product.php?id=<?php echo $item['product_id']; ?>">
                                                    <?php echo htmlspecialchars($item['name']); ?>
                                                </a>
                                            </h6>
                                            
                                            <div class="item-price">
                                                <?php echo formatCurrency($item['price']); ?> each
                                            </div>
                                            
                                            <div class="item-stock">
                                                <?php if ($item['stock_quantity'] > 0): ?>
                                                    <span style="color: var(--secondary-green);">
                                                        <i class="fas fa-check-circle"></i> In Stock (<?php echo $item['stock_quantity']; ?> available)
                                                    </span>
                                                <?php else: ?>
                                                    <span style="color: #F44336;">
                                                        <i class="fas fa-times-circle"></i> Out of Stock
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        
                                        <div class="item-quantity">
                                            <label class="form-label">Quantity</label>
                                            <div class="quantity-controls">
                                                <button onclick="updateQuantity(<?php echo $item['product_id']; ?>, <?php echo max(1, $item['quantity'] - 1); ?>)" 
                                                        class="quantity-btn" <?php echo $item['quantity'] <= 1 ? 'disabled' : ''; ?>>
                                                    <i class="fas fa-minus"></i>
                                                </button>
                                                <input type="number" 
                                                       value="<?php echo $item['quantity']; ?>" 
                                                       min="1" 
                                                       max="<?php echo $item['stock_quantity']; ?>"
                                                       class="quantity-input"
                                                       onchange="updateQuantity(<?php echo $item['product_id']; ?>, this.value)">
                                                <button onclick="updateQuantity(<?php echo $item['product_id']; ?>, <?php echo $item['quantity'] + 1; ?>)" 
                                                        class="quantity-btn" 
                                                        <?php echo $item['quantity'] >= $item['stock_quantity'] ? 'disabled' : ''; ?>>
                                                    <i class="fas fa-plus"></i>
                                                </button>
                                            </div>
                                        </div>
                                        
                                        <div class="item-total">
                                            <div class="total-price"><?php echo formatCurrency($item['item_total']); ?></div>
                                            <button onclick="removeFromCart(<?php echo $item['product_id']; ?>)" 
                                                    class="btn btn-outline btn-sm remove-btn">
                                                <i class="fas fa-trash"></i> Remove
                                            </button>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <!-- Recommended Products -->
                <div class="card mt-3">
                    <div class="card-header">
                        <h6 style="margin-bottom: 0;">You Might Also Like</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <!-- This would be populated with recommended products -->
                            <div class="col-12 text-center text-muted">
                                <p>Recommended products will appear here based on your cart items.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Cart Summary -->
            <div class="col-4">
                <div class="card cart-summary">
                    <div class="card-header">
                        <h5 style="margin-bottom: 0;">Order Summary</h5>
                    </div>
                    
                    <div class="card-body">
                        <div class="summary-row">
                            <span>Subtotal (<?php echo $cart_summary['item_count']; ?> items)</span>
                            <strong><?php echo formatCurrency($cart_summary['subtotal']); ?></strong>
                        </div>
                        
                        <div class="summary-row">
                            <span>Shipping (<?php echo count($vendors); ?> vendors)</span>
                            <strong><?php echo formatCurrency($cart_summary['shipping']); ?></strong>
                        </div>
                        
                        <div class="summary-row">
                            <span>Tax (18% VAT)</span>
                            <strong><?php echo formatCurrency($cart_summary['tax']); ?></strong>
                        </div>
                        
                        <hr>
                        
                        <div class="summary-row total-row">
                            <span>Total</span>
                            <strong><?php echo formatCurrency($cart_summary['total']); ?></strong>
                        </div>
                        
                        <div style="margin-top: 2rem;">
                            <?php if (isLoggedIn()): ?>
                                <a href="checkout-improved.php" class="btn btn-primary btn-lg" style="width: 100%; margin-bottom: 1rem;">
                                    <i class="fas fa-credit-card"></i> Proceed to Checkout
                                </a>
                            <?php else: ?>
                                <a href="login.php?redirect=checkout-improved.php" class="btn btn-primary btn-lg" style="width: 100%; margin-bottom: 1rem;">
                                    <i class="fas fa-sign-in-alt"></i> Login to Checkout
                                </a>
                            <?php endif; ?>
                            
                            <button onclick="saveForLater()" class="btn btn-outline" style="width: 100%;">
                                <i class="fas fa-heart"></i> Save for Later
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Security Info -->
                <div class="card mt-3">
                    <div class="card-body text-center">
                        <div style="color: var(--primary-green); margin-bottom: 1rem;">
                            <i class="fas fa-shield-alt" style="font-size: 2rem;"></i>
                        </div>
                        <h6>Secure Checkout</h6>
                        <p style="font-size: 0.9rem; color: var(--dark-gray); margin-bottom: 0;">
                            Your payment information is encrypted and secure. We never store your credit card details.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<style>
.vendor-section {
    margin-bottom: 2rem;
    padding-bottom: 2rem;
    border-bottom: 1px solid var(--light-gray);
}

.vendor-section:last-child {
    border-bottom: none;
    margin-bottom: 0;
}

.cart-item {
    display: flex;
    align-items: flex-start;
    gap: 1rem;
    padding: 1.5rem 0;
    border-bottom: 1px solid var(--light-gray);
}

.cart-item:last-child {
    border-bottom: none;
}

.item-image {
    flex-shrink: 0;
}

.item-image img {
    width: 100px;
    height: 100px;
    object-fit: cover;
    border-radius: var(--border-radius);
}

.item-details {
    flex: 1;
    min-width: 0;
}

.item-title {
    margin-bottom: 0.5rem;
}

.item-title a {
    color: var(--black);
    text-decoration: none;
    font-weight: 600;
}

.item-title a:hover {
    color: var(--primary-green);
}

.item-price {
    font-size: 1.1rem;
    font-weight: 600;
    color: var(--primary-green);
    margin-bottom: 0.5rem;
}

.item-stock {
    font-size: 0.9rem;
}

.item-quantity {
    flex-shrink: 0;
    text-align: center;
    min-width: 120px;
}

.quantity-controls {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    justify-content: center;
}

.quantity-btn {
    background: var(--light-gray);
    border: 1px solid var(--medium-gray);
    width: 32px;
    height: 32px;
    border-radius: 4px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.3s ease;
}

.quantity-btn:hover:not(:disabled) {
    background: var(--primary-green);
    color: white;
}

.quantity-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.quantity-input {
    width: 60px;
    text-align: center;
    border: 1px solid var(--medium-gray);
    border-radius: 4px;
    padding: 0.5rem;
}

.item-total {
    flex-shrink: 0;
    text-align: right;
    min-width: 120px;
}

.total-price {
    font-size: 1.2rem;
    font-weight: bold;
    color: var(--primary-green);
    margin-bottom: 0.5rem;
}

.remove-btn {
    color: #F44336;
    border-color: #F44336;
}

.remove-btn:hover {
    background: #F44336;
    color: white;
}

.cart-summary {
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
    
    .cart-item {
        flex-direction: column;
        text-align: center;
    }
    
    .item-quantity,
    .item-total {
        min-width: auto;
        text-align: center;
    }
    
    .cart-summary {
        position: static;
    }
}
</style>

<script>
function updateQuantity(productId, quantity) {
    quantity = Math.max(1, parseInt(quantity));
    
    fetch('api/cart.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            product_id: productId,
            action: 'update',
            quantity: quantity
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            showNotification(data.message || 'Error updating quantity', 'error');
        }
    })
    .catch(error => {
        showNotification('Error updating quantity', 'error');
    });
}

function removeFromCart(productId) {
    if (confirm('Are you sure you want to remove this item from your cart?')) {
        fetch('api/cart.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                product_id: productId,
                action: 'remove'
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Animate item removal
                const item = document.querySelector(`[data-product-id="${productId}"]`);
                if (item) {
                    item.style.animation = 'slideOut 0.3s ease forwards';
                    setTimeout(() => location.reload(), 300);
                }
            } else {
                showNotification(data.message || 'Error removing item', 'error');
            }
        });
    }
}

function clearCart() {
    if (confirm('Are you sure you want to clear your entire cart? This action cannot be undone.')) {
        fetch('api/cart.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'clear'
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                showNotification(data.message || 'Error clearing cart', 'error');
            }
        });
    }
}

function saveForLater() {
    // Move all cart items to wishlist
    if (confirm('Move all cart items to your wishlist?')) {
        // This would require implementation of moving cart items to wishlist
        showNotification('Feature coming soon!', 'info');
    }
}

function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `alert alert-${type}`;
    notification.style.cssText = 'position: fixed; top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
    notification.textContent = message;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.remove();
    }, 3000);
}

// Animation for removing items
const style = document.createElement('style');
style.textContent = `
    @keyframes slideOut {
        from {
            opacity: 1;
            transform: translateX(0);
        }
        to {
            opacity: 0;
            transform: translateX(100%);
        }
    }
`;
document.head.appendChild(style);
</script>

<?php require_once 'includes/footer.php'; ?>
