<?php
/**
 * Enhanced Shopping Cart Page
 * Multi-Vendor E-Commerce Platform
 */

require_once 'config/config.php';

$page_title = 'Shopping Cart';

// Handle guest and logged-in users
$customer_id = isLoggedIn() ? $_SESSION['user_id'] : null;
$session_id = null;

if (!$customer_id) {
    if (!isset($_SESSION['cart_session_id'])) {
        $_SESSION['cart_session_id'] = uniqid('cart_', true);
    }
    $session_id = $_SESSION['cart_session_id'];
}

// Get cart items with detailed information
$where_clause = $customer_id ? "ci.customer_id = ?" : "ci.session_id = ?";
$where_param = $customer_id ?: $session_id;

$cart_sql = "
    SELECT ci.*, p.name, p.price, p.compare_price, p.stock_quantity, p.image_url, p.slug, p.brand,
           u.username as vendor_name, vs.store_name, vs.store_logo, vs.shipping_policy, 
           c.name as category_name, (ci.quantity * p.price) as subtotal,
           CASE WHEN p.stock_quantity <= 0 THEN 'out_of_stock'
                WHEN p.stock_quantity < ci.quantity THEN 'insufficient_stock'
                WHEN p.stock_quantity <= 5 THEN 'low_stock'
                ELSE 'in_stock' END as stock_status
    FROM cart_items ci
    JOIN products p ON ci.product_id = p.id
    JOIN users u ON p.vendor_id = u.id
    LEFT JOIN vendor_stores vs ON u.id = vs.vendor_id
    LEFT JOIN categories c ON p.category_id = c.id
    WHERE {$where_clause} AND p.status = 'active' AND u.status = 'active'
    ORDER BY ci.created_at DESC
";

$cart_items = $database->fetchAll($cart_sql, [$where_param]);

// Group items by vendor
$vendors = [];
$total_items = 0;
$total_amount = 0;
$has_issues = false;

foreach ($cart_items as $item) {
    $vendor_key = $item['vendor_name'];
    if (!isset($vendors[$vendor_key])) {
        $vendors[$vendor_key] = [
            'vendor_name' => $item['vendor_name'],
            'store_name' => $item['store_name'],
            'store_logo' => $item['store_logo'],
            'shipping_policy' => $item['shipping_policy'],
            'items' => [],
            'subtotal' => 0,
            'item_count' => 0
        ];
    }
    
    $vendors[$vendor_key]['items'][] = $item;
    $vendors[$vendor_key]['subtotal'] += $item['subtotal'];
    $vendors[$vendor_key]['item_count'] += $item['quantity'];
    
    $total_items += $item['quantity'];
    $total_amount += $item['subtotal'];
    
    if (in_array($item['stock_status'], ['out_of_stock', 'insufficient_stock'])) {
        $has_issues = true;
    }
}

// Get recommendations based on cart items
$recommendations = [];
if (!empty($cart_items)) {
    $product_ids = array_column($cart_items, 'product_id');
    $category_ids = array_unique(array_column($cart_items, 'category_id'));
    
    if (!empty($category_ids)) {
        $placeholders = str_repeat('?,', count($category_ids) - 1) . '?';
        $product_placeholders = str_repeat('?,', count($product_ids) - 1) . '?';
        
        $recommendations_sql = "
            SELECT p.id, p.name, p.price, p.image_url, p.slug, p.brand,
                   u.username as vendor_name, vs.store_name
            FROM products p
            JOIN users u ON p.vendor_id = u.id
            LEFT JOIN vendor_stores vs ON u.id = vs.vendor_id
            WHERE p.category_id IN ($placeholders)
              AND p.id NOT IN ($product_placeholders)
              AND p.status = 'active' 
              AND u.status = 'active'
            ORDER BY RAND()
            LIMIT 6
        ";
        
        $params = array_merge($category_ids, $product_ids);
        $recommendations = $database->fetchAll($recommendations_sql, $params);
    }
}

include 'includes/header.php';
?>

<div class="container">
    <div class="row">
        <div class="col-12">
            <!-- Cart Header -->
            <div class="cart-header" style="background: white; padding: 2rem; border-radius: 10px; margin-bottom: 2rem; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                    <h1 style="margin: 0; color: #007bff;">
                        <i class="fas fa-shopping-cart"></i> Shopping Cart
                    </h1>
                    <div style="display: flex; gap: 1rem; align-items: center;">
                        <span style="color: #666; font-size: 0.9rem;">
                            <?php echo $total_items; ?> items from <?php echo count($vendors); ?> vendor<?php echo count($vendors) !== 1 ? 's' : ''; ?>
                        </span>
                        <?php if (!empty($cart_items)): ?>
                            <button onclick="clearCart()" class="btn btn-outline-danger btn-sm">
                                <i class="fas fa-trash"></i> Clear Cart
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
                
                <?php if ($has_issues): ?>
                    <div style="background: #fef2f2; border: 1px solid #fecaca; padding: 1rem; border-radius: 6px; margin-bottom: 1rem;">
                        <p style="margin: 0; color: #dc2626;">
                            <i class="fas fa-exclamation-triangle"></i> 
                            Some items in your cart have stock issues. Please review and update quantities.
                        </p>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($cart_items)): ?>
                    <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
                        <button onclick="moveAllToWishlist()" class="btn btn-outline-danger">
                            <i class="fas fa-heart"></i> Move All to Wishlist
                        </button>
                        <button onclick="compareCartItems()" class="btn btn-outline-success">
                            <i class="fas fa-balance-scale"></i> Compare Items
                        </button>
                        <button onclick="saveForLater()" class="btn btn-outline-secondary">
                            <i class="fas fa-bookmark"></i> Save for Later
                        </button>
                    </div>
                <?php endif; ?>
            </div>

            <?php if (empty($cart_items)): ?>
                <!-- Empty Cart -->
                <div style="text-align: center; padding: 4rem 2rem; background: white; border-radius: 10px;">
                    <i class="fas fa-shopping-cart" style="font-size: 4rem; color: #007bff; margin-bottom: 1rem; opacity: 0.3;"></i>
                    <h3 style="color: #374151; margin-bottom: 1rem;">Your cart is empty</h3>
                    <p style="color: #6b7280; margin-bottom: 2rem;">
                        Add some products to your cart and they will appear here!
                    </p>
                    <div style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
                        <a href="products.php" class="btn btn-primary">
                            <i class="fas fa-shopping-bag"></i> Browse Products
                        </a>
                        <a href="wishlist-enhanced.php" class="btn btn-outline-danger">
                            <i class="fas fa-heart"></i> View Wishlist
                        </a>
                        <a href="products.php?category=smartphones" class="btn btn-outline-primary">📱 Smartphones</a>
                        <a href="products.php?category=laptops" class="btn btn-outline-primary">💻 Laptops</a>
                    </div>
                </div>
            <?php else: ?>
                <div class="row">
                    <!-- Cart Items -->
                    <div class="col-lg-8">
                        <?php foreach ($vendors as $vendor): ?>
                            <div class="vendor-section" style="background: white; border-radius: 10px; margin-bottom: 2rem; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                                <!-- Vendor Header -->
                                <div style="background: #f8fafc; padding: 1.5rem; border-bottom: 1px solid #e5e7eb;">
                                    <div style="display: flex; justify-content: space-between; align-items: center;">
                                        <div style="display: flex; align-items: center; gap: 1rem;">
                                            <?php if ($vendor['store_logo']): ?>
                                                <img src="<?php echo htmlspecialchars($vendor['store_logo']); ?>" 
                                                     alt="<?php echo htmlspecialchars($vendor['store_name']); ?>"
                                                     style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover;">
                                            <?php endif; ?>
                                            <div>
                                                <h4 style="margin: 0; color: #374151;">
                                                    <?php echo htmlspecialchars($vendor['store_name'] ?: $vendor['vendor_name']); ?>
                                                </h4>
                                                <p style="margin: 0; color: #6b7280; font-size: 0.9rem;">
                                                    <?php echo $vendor['item_count']; ?> items • RWF <?php echo number_format($vendor['subtotal']); ?>
                                                </p>
                                            </div>
                                        </div>
                                        <div style="text-align: right;">
                                            <?php if ($vendor['shipping_policy']): ?>
                                                <p style="margin: 0; color: #10b981; font-size: 0.8rem;">
                                                    <i class="fas fa-shipping-fast"></i> <?php echo htmlspecialchars($vendor['shipping_policy']); ?>
                                                </p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>

                                <!-- Vendor Items -->
                                <div style="padding: 1.5rem;">
                                    <?php foreach ($vendor['items'] as $item): ?>
                                        <div class="cart-item" data-id="<?php echo $item['product_id']; ?>" style="display: flex; gap: 1.5rem; padding: 1.5rem 0; border-bottom: 1px solid #f3f4f6;">
                                            <!-- Product Image -->
                                            <div style="flex-shrink: 0;">
                                                <div style="width: 100px; height: 100px; border-radius: 8px; overflow: hidden;">
                                                    <?php if ($item['image_url']): ?>
                                                        <img src="<?php echo htmlspecialchars($item['image_url']); ?>" 
                                                             alt="<?php echo htmlspecialchars($item['name']); ?>"
                                                             style="width: 100%; height: 100%; object-fit: cover;">
                                                    <?php else: ?>
                                                        <div style="width: 100%; height: 100%; background: #f3f4f6; display: flex; align-items: center; justify-content: center;">
                                                            <i class="fas fa-image" style="color: #9ca3af;"></i>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>

                                            <!-- Product Details -->
                                            <div style="flex: 1;">
                                                <h5 style="margin: 0 0 0.5rem 0; color: #374151;">
                                                    <a href="product.php?id=<?php echo $item['product_id']; ?>" style="text-decoration: none; color: inherit;">
                                                        <?php echo htmlspecialchars($item['name']); ?>
                                                    </a>
                                                </h5>
                                                
                                                <?php if ($item['brand']): ?>
                                                    <p style="margin: 0 0 0.5rem 0; color: #6b7280; font-size: 0.9rem;">
                                                        <strong>Brand:</strong> <?php echo htmlspecialchars($item['brand']); ?>
                                                    </p>
                                                <?php endif; ?>
                                                
                                                <p style="margin: 0 0 0.5rem 0; color: #6b7280; font-size: 0.9rem;">
                                                    <strong>Category:</strong> <?php echo htmlspecialchars($item['category_name'] ?: 'Uncategorized'); ?>
                                                </p>

                                                <!-- Stock Status -->
                                                <?php
                                                $status_colors = [
                                                    'out_of_stock' => '#ef4444',
                                                    'insufficient_stock' => '#f59e0b',
                                                    'low_stock' => '#f59e0b',
                                                    'in_stock' => '#10b981'
                                                ];
                                                $status_texts = [
                                                    'out_of_stock' => 'Out of Stock',
                                                    'insufficient_stock' => 'Insufficient Stock',
                                                    'low_stock' => 'Low Stock',
                                                    'in_stock' => 'In Stock'
                                                ];
                                                ?>
                                                <div style="margin-bottom: 1rem;">
                                                    <span style="background: <?php echo $status_colors[$item['stock_status']]; ?>; color: white; padding: 2px 6px; border-radius: 3px; font-size: 12px;">
                                                        <?php echo $status_texts[$item['stock_status']]; ?>
                                                    </span>
                                                    <?php if ($item['stock_status'] === 'insufficient_stock'): ?>
                                                        <span style="color: #f59e0b; font-size: 0.8rem; margin-left: 0.5rem;">
                                                            (Only <?php echo $item['stock_quantity']; ?> available)
                                                        </span>
                                                    <?php endif; ?>
                                                </div>

                                                <!-- Actions -->
                                                <div style="display: flex; gap: 1rem; align-items: center;">
                                                    <button onclick="removeFromCart(<?php echo $item['product_id']; ?>)" class="btn btn-sm btn-outline-danger">
                                                        <i class="fas fa-trash"></i> Remove
                                                    </button>
                                                    <button onclick="moveToWishlist(<?php echo $item['product_id']; ?>)" class="btn btn-sm btn-outline-secondary">
                                                        <i class="fas fa-heart"></i> Wishlist
                                                    </button>
                                                    <button onclick="addToCompare(<?php echo $item['product_id']; ?>)" class="btn btn-sm btn-outline-success">
                                                        <i class="fas fa-balance-scale"></i> Compare
                                                    </button>
                                                </div>
                                            </div>

                                            <!-- Price and Quantity -->
                                            <div style="text-align: right; min-width: 150px;">
                                                <!-- Price -->
                                                <div style="margin-bottom: 1rem;">
                                                    <div style="font-size: 1.1rem; font-weight: bold; color: #007bff;">
                                                        RWF <?php echo number_format($item['price']); ?>
                                                    </div>
                                                    <?php if ($item['compare_price'] && $item['compare_price'] > $item['price']): ?>
                                                        <div style="text-decoration: line-through; color: #9ca3af; font-size: 0.9rem;">
                                                            RWF <?php echo number_format($item['compare_price']); ?>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>

                                                <!-- Quantity Controls -->
                                                <div style="display: flex; align-items: center; justify-content: flex-end; margin-bottom: 1rem;">
                                                    <button onclick="updateQuantity(<?php echo $item['product_id']; ?>, <?php echo $item['quantity'] - 1; ?>)" 
                                                            class="btn btn-sm btn-outline-secondary" 
                                                            style="padding: 0.25rem 0.5rem;"
                                                            <?php echo $item['quantity'] <= 1 ? 'disabled' : ''; ?>>
                                                        <i class="fas fa-minus"></i>
                                                    </button>
                                                    <span style="margin: 0 1rem; font-weight: bold; min-width: 30px; text-align: center;">
                                                        <?php echo $item['quantity']; ?>
                                                    </span>
                                                    <button onclick="updateQuantity(<?php echo $item['product_id']; ?>, <?php echo $item['quantity'] + 1; ?>)" 
                                                            class="btn btn-sm btn-outline-secondary" 
                                                            style="padding: 0.25rem 0.5rem;"
                                                            <?php echo $item['quantity'] >= $item['stock_quantity'] ? 'disabled' : ''; ?>>
                                                        <i class="fas fa-plus"></i>
                                                    </button>
                                                </div>

                                                <!-- Subtotal -->
                                                <div style="font-size: 1.2rem; font-weight: bold; color: #374151;">
                                                    RWF <?php echo number_format($item['subtotal']); ?>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Cart Summary -->
                    <div class="col-lg-4">
                        <div class="cart-summary" style="background: white; border-radius: 10px; padding: 2rem; box-shadow: 0 2px 10px rgba(0,0,0,0.1); position: sticky; top: 20px;">
                            <h3 style="margin: 0 0 1.5rem 0; color: #374151;">Order Summary</h3>
                            
                            <!-- Summary Details -->
                            <div style="border-bottom: 1px solid #e5e7eb; padding-bottom: 1rem; margin-bottom: 1rem;">
                                <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                                    <span>Items (<?php echo $total_items; ?>):</span>
                                    <span>RWF <?php echo number_format($total_amount); ?></span>
                                </div>
                                <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                                    <span>Shipping:</span>
                                    <span style="color: #10b981;">Calculated at checkout</span>
                                </div>
                                <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                                    <span>Tax:</span>
                                    <span style="color: #10b981;">Calculated at checkout</span>
                                </div>
                            </div>
                            
                            <!-- Total -->
                            <div style="display: flex; justify-content: space-between; font-size: 1.2rem; font-weight: bold; color: #374151; margin-bottom: 2rem;">
                                <span>Total:</span>
                                <span>RWF <?php echo number_format($total_amount); ?></span>
                            </div>
                            
                            <!-- Checkout Button -->
                            <?php if (!$has_issues): ?>
                                <button onclick="proceedToCheckout()" class="btn btn-primary" style="width: 100%; padding: 1rem; font-size: 1.1rem; margin-bottom: 1rem;">
                                    <i class="fas fa-lock"></i> Proceed to Checkout
                                </button>
                            <?php else: ?>
                                <button class="btn btn-secondary" style="width: 100%; padding: 1rem; font-size: 1.1rem; margin-bottom: 1rem;" disabled>
                                    <i class="fas fa-exclamation-triangle"></i> Fix Issues to Checkout
                                </button>
                            <?php endif; ?>
                            
                            <!-- Continue Shopping -->
                            <a href="products.php" class="btn btn-outline-primary" style="width: 100%; text-align: center; padding: 0.75rem;">
                                <i class="fas fa-arrow-left"></i> Continue Shopping
                            </a>
                            
                            <!-- Security Info -->
                            <div style="margin-top: 2rem; padding: 1rem; background: #f0f9ff; border-radius: 6px;">
                                <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem;">
                                    <i class="fas fa-shield-alt" style="color: #0ea5e9;"></i>
                                    <span style="font-weight: bold; color: #0369a1;">Secure Checkout</span>
                                </div>
                                <p style="margin: 0; color: #0369a1; font-size: 0.8rem;">
                                    Your payment information is encrypted and secure.
                                </p>
                            </div>
                            
                            <!-- Vendor Count -->
                            <div style="margin-top: 1rem; padding: 1rem; background: #f8fafc; border-radius: 6px;">
                                <p style="margin: 0; color: #6b7280; font-size: 0.9rem; text-align: center;">
                                    <i class="fas fa-store"></i> 
                                    Items from <?php echo count($vendors); ?> vendor<?php echo count($vendors) !== 1 ? 's' : ''; ?>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Recommendations Section -->
            <?php if (!empty($recommendations)): ?>
                <div style="background: white; padding: 2rem; border-radius: 10px; margin-bottom: 2rem;">
                    <h3 style="margin-bottom: 1.5rem; color: #374151;">
                        <i class="fas fa-lightbulb"></i> You might also like
                    </h3>
                    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 1.5rem;">
                        <?php foreach ($recommendations as $rec): ?>
                            <div style="border: 1px solid #e5e7eb; border-radius: 8px; overflow: hidden; transition: transform 0.2s;">
                                <div style="height: 150px; overflow: hidden;">
                                    <?php if ($rec['image_url']): ?>
                                        <img src="<?php echo htmlspecialchars($rec['image_url']); ?>" 
                                             alt="<?php echo htmlspecialchars($rec['name']); ?>"
                                             style="width: 100%; height: 100%; object-fit: cover;">
                                    <?php else: ?>
                                        <div style="width: 100%; height: 100%; background: #f3f4f6; display: flex; align-items: center; justify-content: center;">
                                            <i class="fas fa-image" style="color: #9ca3af;"></i>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div style="padding: 1rem;">
                                    <h4 style="margin: 0 0 0.5rem 0; font-size: 0.9rem; color: #374151;">
                                        <a href="product.php?id=<?php echo $rec['id']; ?>" style="text-decoration: none; color: inherit;">
                                            <?php echo htmlspecialchars($rec['name']); ?>
                                        </a>
                                    </h4>
                                    <p style="margin: 0 0 0.5rem 0; color: #007bff; font-weight: bold;">
                                        RWF <?php echo number_format($rec['price']); ?>
                                    </p>
                                    <div style="display: flex; gap: 0.25rem;">
                                        <button onclick="addToWishlist(<?php echo $rec['id']; ?>)" class="btn btn-sm btn-outline-danger" style="flex: 1;">
                                            <i class="fas fa-heart"></i>
                                        </button>
                                        <button onclick="addToCart(<?php echo $rec['id']; ?>)" class="btn btn-sm btn-primary" style="flex: 2;">
                                            <i class="fas fa-cart-plus"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
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

.btn-outline-danger {
    background: transparent;
    color: #ef4444;
    border-color: #ef4444;
}

.btn-outline-danger:hover {
    background: #ef4444;
    color: white;
}

.btn-outline-success {
    background: transparent;
    color: #10b981;
    border-color: #10b981;
}

.btn-outline-success:hover {
    background: #10b981;
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

.btn-secondary {
    background: #6b7280;
    color: white;
    border-color: #6b7280;
}

.btn-sm {
    padding: 0.25rem 0.5rem;
    font-size: 0.875rem;
}

.cart-item:last-child {
    border-bottom: none !important;
}
</style>

<script>
// Cart functionality
function updateQuantity(productId, newQuantity) {
    if (newQuantity < 1) {
        removeFromCart(productId);
        return;
    }
    
    fetch('/ange Final/api/cart.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'update',
            product_id: productId,
            quantity: newQuantity
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Quantity updated', 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            showNotification(data.message || 'Error updating quantity', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Error updating quantity', 'error');
    });
}

function removeFromCart(productId) {
    if (!confirm('Remove this item from your cart?')) return;
    
    fetch('/ange Final/api/cart.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'remove',
            product_id: productId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Item removed from cart', 'success');
            
            // Remove item from DOM
            const item = document.querySelector(`[data-id="${productId}"]`);
            if (item) {
                item.style.transition = 'opacity 0.3s';
                item.style.opacity = '0';
                setTimeout(() => {
                    item.remove();
                    // Reload if no items left
                    if (document.querySelectorAll('.cart-item').length === 1) {
                        location.reload();
                    }
                }, 300);
            }
        } else {
            showNotification(data.message || 'Error removing item', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Error removing item', 'error');
    });
}

function clearCart() {
    if (!confirm('Are you sure you want to clear your entire cart?')) return;
    
    fetch('/ange Final/api/cart.php', {
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
            showNotification('Cart cleared', 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            showNotification(data.message || 'Error clearing cart', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Error clearing cart', 'error');
    });
}

function moveToWishlist(productId) {
    // First add to wishlist
    fetch('/ange Final/api/wishlist.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'add',
            product_id: productId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Then remove from cart
            return fetch('/ange Final/api/cart.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'remove',
                    product_id: productId
                })
            });
        } else {
            throw new Error(data.message || 'Error adding to wishlist');
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Item moved to wishlist', 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            showNotification(data.message || 'Error moving to wishlist', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Error moving to wishlist', 'error');
    });
}

function addToCompare(productId) {
    fetch('/ange Final/api/compare.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'add',
            product_id: productId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Added to comparison!', 'success');
        } else {
            showNotification(data.message || 'Error adding to comparison', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Error adding to comparison', 'error');
    });
}

function addToCart(productId) {
    fetch('/ange Final/api/cart.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'add',
            product_id: productId,
            quantity: 1
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Added to cart!', 'success');
            updateCartCount();
        } else {
            showNotification(data.message || 'Error adding to cart', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Error adding to cart', 'error');
    });
}

function addToWishlist(productId) {
    fetch('/ange Final/api/wishlist.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'add',
            product_id: productId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Added to wishlist!', 'success');
        } else {
            showNotification(data.message || 'Error adding to wishlist', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Error adding to wishlist', 'error');
    });
}

function moveAllToWishlist() {
    const items = document.querySelectorAll('.cart-item');
    let promises = [];
    
    items.forEach(item => {
        const productId = item.dataset.id;
        promises.push(
            fetch('/ange Final/api/wishlist.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'add',
                    product_id: parseInt(productId)
                })
            })
        );
    });
    
    Promise.all(promises)
        .then(responses => Promise.all(responses.map(r => r.json())))
        .then(results => {
            const successful = results.filter(r => r.success).length;
            if (successful > 0) {
                // Clear cart after moving to wishlist
                clearCart();
            }
            showNotification(`${successful} items moved to wishlist!`, 'success');
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('Error moving items to wishlist', 'error');
        });
}

function compareCartItems() {
    const items = document.querySelectorAll('.cart-item');
    const productIds = Array.from(items).map(item => item.dataset.id);
    
    if (productIds.length < 2) {
        showNotification('Need at least 2 items to compare', 'warning');
        return;
    }
    
    if (productIds.length > 4) {
        showNotification('Can compare maximum 4 items. First 4 will be selected.', 'warning');
    }
    
    const compareIds = productIds.slice(0, 4);
    window.location.href = `compare.php?products=${compareIds.join(',')}`;
}

function saveForLater() {
    showNotification('Save for later feature coming soon!', 'info');
}

function proceedToCheckout() {
    window.location.href = 'checkout.php';
}

function updateCartCount() {
    fetch('/ange Final/api/cart.php?action=count')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const cartBadge = document.querySelector('.cart-count');
                if (cartBadge) {
                    cartBadge.textContent = data.total_items;
                }
            }
        })
        .catch(error => console.error('Error updating cart count:', error));
}

function showNotification(message, type = 'info') {
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `alert alert-${type === 'error' ? 'danger' : type === 'warning' ? 'warning' : type === 'success' ? 'success' : 'info'}`;
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 9999;
        min-width: 300px;
        animation: slideIn 0.3s ease-out;
    `;
    notification.innerHTML = `
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <span>${message}</span>
            <button type="button" class="close" onclick="this.parentElement.parentElement.remove()">
                <span>&times;</span>
            </button>
        </div>
    `;
    
    document.body.appendChild(notification);
    
    // Auto remove after 5 seconds
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
