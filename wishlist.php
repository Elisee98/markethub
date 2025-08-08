<?php
/**
 * MarketHub Customer Wishlist
 * Multi-Vendor E-Commerce Platform
 */

require_once 'config/config.php';

$page_title = 'My Wishlist';

// Require login
requireLogin();

$customer_id = $_SESSION['user_id'];

// Handle bulk actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bulk_action'])) {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error_message = 'Invalid security token.';
    } else {
        $action = $_POST['bulk_action'];
        $selected_items = $_POST['selected_items'] ?? [];
        
        if (!empty($selected_items) && in_array($action, ['remove', 'move_to_cart'])) {
            try {
                $placeholders = str_repeat('?,', count($selected_items) - 1) . '?';
                $params = array_merge($selected_items, [$customer_id]);
                
                if ($action === 'remove') {
                    $sql = "DELETE FROM wishlists WHERE product_id IN ($placeholders) AND customer_id = ?";
                    $database->execute($sql, $params);
                    $success_message = count($selected_items) . ' items removed from wishlist.';
                    
                } elseif ($action === 'move_to_cart') {
                    // Get products that are in stock
                    $products_sql = "
                        SELECT w.product_id, p.stock_quantity 
                        FROM wishlists w 
                        JOIN products p ON w.product_id = p.id 
                        WHERE w.product_id IN ($placeholders) AND w.customer_id = ? AND p.stock_quantity > 0
                    ";
                    $available_products = $database->fetchAll($products_sql, $params);
                    
                    foreach ($available_products as $product) {
                        // Check if already in cart
                        $cart_item = $database->fetch(
                            "SELECT id, quantity FROM cart_items WHERE customer_id = ? AND product_id = ?",
                            [$customer_id, $product['product_id']]
                        );
                        
                        if ($cart_item) {
                            // Update quantity
                            $new_quantity = $cart_item['quantity'] + 1;
                            $sql = "UPDATE cart_items SET quantity = ?, updated_at = NOW() WHERE id = ?";
                            $database->execute($sql, [$new_quantity, $cart_item['id']]);
                        } else {
                            // Add new cart item
                            $sql = "INSERT INTO cart_items (customer_id, product_id, quantity, created_at) VALUES (?, ?, 1, NOW())";
                            $database->execute($sql, [$customer_id, $product['product_id']]);
                        }
                    }
                    
                    // Remove moved items from wishlist
                    $moved_ids = array_column($available_products, 'product_id');
                    if (!empty($moved_ids)) {
                        $moved_placeholders = str_repeat('?,', count($moved_ids) - 1) . '?';
                        $moved_params = array_merge($moved_ids, [$customer_id]);
                        $sql = "DELETE FROM wishlists WHERE product_id IN ($moved_placeholders) AND customer_id = ?";
                        $database->execute($sql, $moved_params);
                    }
                    
                    $success_message = count($available_products) . ' items moved to cart.';
                    if (count($available_products) < count($selected_items)) {
                        $success_message .= ' Some items were out of stock and could not be moved.';
                    }
                }
                
                logActivity($customer_id, 'wishlist_bulk_action', "Action: $action, Items: " . implode(',', $selected_items));
                
            } catch (Exception $e) {
                $error_message = 'Error performing bulk action: ' . $e->getMessage();
            }
        }
    }
}

// Get wishlist items
$wishlist_sql = "
    SELECT w.*, p.name, p.price, p.compare_price, p.stock_quantity, p.short_description,
           pi.image_url, c.name as category_name,
           u.username as vendor_name, vs.store_name,
           AVG(pr.rating) as avg_rating, COUNT(pr.id) as review_count
    FROM wishlists w
    JOIN products p ON w.product_id = p.id
    LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
    LEFT JOIN categories c ON p.category_id = c.id
    LEFT JOIN users u ON p.vendor_id = u.id
    LEFT JOIN vendor_stores vs ON u.id = vs.vendor_id
    LEFT JOIN product_reviews pr ON p.id = pr.product_id AND pr.status = 'approved'
    WHERE w.customer_id = ? AND p.status = 'active'
    GROUP BY w.id
    ORDER BY w.created_at DESC
";

$wishlist_items = $database->fetchAll($wishlist_sql, [$customer_id]);

require_once 'includes/header.php';
?>

<div class="container" style="margin: 2rem auto;">
    <!-- Header -->
    <div class="row align-items-center mb-3">
        <div class="col-8">
            <h1>My Wishlist</h1>
            <p class="text-muted">Save products you love and purchase them later</p>
        </div>
        <div class="col-4 text-right">
            <div style="display: flex; gap: 1rem; justify-content: flex-end;">
                <?php if (!empty($wishlist_items)): ?>
                    <button onclick="clearWishlist()" class="btn btn-outline">
                        <i class="fas fa-trash"></i> Clear All
                    </button>
                <?php endif; ?>
                <a href="products.php" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Add More Products
                </a>
            </div>
        </div>
    </div>

    <!-- Success/Error Messages -->
    <?php if (isset($success_message)): ?>
        <div class="alert alert-success">
            <?php echo htmlspecialchars($success_message); ?>
        </div>
    <?php endif; ?>
    
    <?php if (isset($error_message)): ?>
        <div class="alert alert-error">
            <?php echo htmlspecialchars($error_message); ?>
        </div>
    <?php endif; ?>

    <?php if (empty($wishlist_items)): ?>
        <!-- Empty Wishlist -->
        <div class="card">
            <div class="card-body text-center" style="padding: 4rem;">
                <i class="fas fa-heart" style="font-size: 4rem; color: var(--medium-gray); margin-bottom: 2rem;"></i>
                <h3>Your Wishlist is Empty</h3>
                <p style="color: var(--dark-gray); font-size: 1.1rem; margin-bottom: 2rem;">
                    Save products you love by clicking the heart icon on any product. They'll appear here for easy access later.
                </p>
                
                <div style="display: flex; justify-content: center; gap: 1rem; margin-bottom: 3rem;">
                    <a href="products.php" class="btn btn-primary btn-lg">
                        <i class="fas fa-search"></i> Browse Products
                    </a>
                    <a href="categories.php" class="btn btn-outline btn-lg">
                        <i class="fas fa-th-large"></i> Browse Categories
                    </a>
                </div>
                
                <!-- How to Use Wishlist -->
                <div style="background: var(--light-gray); padding: 2rem; border-radius: var(--border-radius); text-align: left; max-width: 600px; margin: 0 auto;">
                    <h5 style="color: var(--primary-green); margin-bottom: 1rem;">How to Use Your Wishlist:</h5>
                    <ol style="color: var(--dark-gray); line-height: 1.8;">
                        <li>Browse products and click the <i class="fas fa-heart" style="color: #E91E63;"></i> icon</li>
                        <li>Products are saved here for easy access later</li>
                        <li>Compare prices and check availability</li>
                        <li>Move items to cart when ready to purchase</li>
                    </ol>
                </div>
            </div>
        </div>
        
    <?php else: ?>
        <!-- Wishlist Items -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 style="margin-bottom: 0;">Your Saved Products (<?php echo count($wishlist_items); ?> items)</h5>
                
                <!-- Bulk Actions -->
                <form method="POST" style="display: flex; align-items: center; gap: 1rem;">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    <select name="bulk_action" class="form-control form-select" style="width: auto;">
                        <option value="">Bulk Actions</option>
                        <option value="move_to_cart">Move to Cart</option>
                        <option value="remove">Remove Selected</option>
                    </select>
                    <button type="submit" class="btn btn-outline btn-sm" onclick="return confirmBulkAction()">Apply</button>
                </form>
            </div>
            
            <div class="card-body">
                <div class="wishlist-items">
                    <?php foreach ($wishlist_items as $item): ?>
                        <div class="wishlist-item" data-product-id="<?php echo $item['product_id']; ?>">
                            <div class="item-checkbox">
                                <input type="checkbox" name="selected_items[]" value="<?php echo $item['product_id']; ?>" class="item-checkbox-input">
                            </div>
                            
                            <div class="item-image">
                                <img src="<?php echo $item['image_url'] ?: 'assets/images/product-placeholder.png'; ?>" 
                                     alt="<?php echo htmlspecialchars($item['name']); ?>" 
                                     onerror="this.src='assets/images/product-placeholder.png'">
                                
                                <!-- Stock Status Badge -->
                                <?php if ($item['stock_quantity'] <= 0): ?>
                                    <span class="stock-badge out-of-stock">Out of Stock</span>
                                <?php elseif ($item['stock_quantity'] <= 5): ?>
                                    <span class="stock-badge low-stock">Low Stock</span>
                                <?php endif; ?>
                            </div>
                            
                            <div class="item-details">
                                <h6 class="item-title">
                                    <a href="product.php?id=<?php echo $item['product_id']; ?>">
                                        <?php echo htmlspecialchars($item['name']); ?>
                                    </a>
                                </h6>
                                
                                <p class="item-description">
                                    <?php echo htmlspecialchars(substr($item['short_description'], 0, 100)); ?>
                                    <?php if (strlen($item['short_description']) > 100): ?>...<?php endif; ?>
                                </p>
                                
                                <div class="item-meta">
                                    <span class="item-category"><?php echo htmlspecialchars($item['category_name']); ?></span>
                                    <span class="item-vendor">
                                        by <a href="vendor.php?id=<?php echo $item['vendor_id']; ?>">
                                            <?php echo htmlspecialchars($item['store_name'] ?: $item['vendor_name']); ?>
                                        </a>
                                    </span>
                                </div>
                                
                                <?php if ($item['review_count'] > 0): ?>
                                    <div class="item-rating">
                                        <div class="stars">
                                            <?php 
                                            $rating = round($item['avg_rating']);
                                            for ($i = 1; $i <= 5; $i++) {
                                                echo $i <= $rating ? '★' : '☆';
                                            }
                                            ?>
                                        </div>
                                        <small>(<?php echo $item['review_count']; ?> reviews)</small>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="item-added">
                                    <small class="text-muted">Added <?php echo timeAgo($item['created_at']); ?></small>
                                </div>
                            </div>
                            
                            <div class="item-price">
                                <div class="current-price"><?php echo formatCurrency($item['price']); ?></div>
                                <?php if ($item['compare_price'] > $item['price']): ?>
                                    <div class="compare-price"><?php echo formatCurrency($item['compare_price']); ?></div>
                                    <div class="discount-badge">
                                        <?php echo round((($item['compare_price'] - $item['price']) / $item['compare_price']) * 100); ?>% OFF
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="item-actions">
                                <?php if ($item['stock_quantity'] > 0): ?>
                                    <button onclick="moveToCart(<?php echo $item['product_id']; ?>)" class="btn btn-primary btn-sm">
                                        <i class="fas fa-shopping-cart"></i> Add to Cart
                                    </button>
                                <?php else: ?>
                                    <button class="btn btn-outline btn-sm" disabled>
                                        <i class="fas fa-times"></i> Out of Stock
                                    </button>
                                <?php endif; ?>
                                
                                <button onclick="addToCompare(<?php echo $item['product_id']; ?>)" class="btn btn-outline btn-sm">
                                    <i class="fas fa-balance-scale"></i> Compare
                                </button>
                                
                                <button onclick="removeFromWishlist(<?php echo $item['product_id']; ?>)" class="btn btn-outline btn-sm" style="color: #F44336; border-color: #F44336;">
                                    <i class="fas fa-trash"></i> Remove
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- Select All -->
                <div style="margin-top: 2rem; padding-top: 1rem; border-top: 1px solid var(--light-gray);">
                    <label style="display: flex; align-items: center; cursor: pointer;">
                        <input type="checkbox" id="select-all" onchange="toggleAllItems()" style="margin-right: 0.5rem;">
                        Select All Items
                    </label>
                </div>
            </div>
        </div>
        
        <!-- Wishlist Summary -->
        <div class="card mt-3">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-6">
                        <h6>Wishlist Summary</h6>
                        <p style="margin-bottom: 0;">
                            <strong><?php echo count($wishlist_items); ?></strong> items saved • 
                            <strong><?php echo count(array_filter($wishlist_items, function($item) { return $item['stock_quantity'] > 0; })); ?></strong> in stock
                        </p>
                    </div>
                    <div class="col-6 text-right">
                        <div style="display: flex; gap: 1rem; justify-content: flex-end;">
                            <button onclick="moveAllToCart()" class="btn btn-primary">
                                <i class="fas fa-shopping-cart"></i> Move All to Cart
                            </button>
                            <a href="compare.php" class="btn btn-outline">
                                <i class="fas fa-balance-scale"></i> Compare Products
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<style>
.wishlist-item {
    display: flex;
    align-items: flex-start;
    gap: 1rem;
    padding: 1.5rem;
    border-bottom: 1px solid var(--light-gray);
    transition: background-color 0.3s ease;
}

.wishlist-item:hover {
    background-color: var(--light-gray);
}

.wishlist-item:last-child {
    border-bottom: none;
}

.item-checkbox {
    display: flex;
    align-items: center;
    padding-top: 0.5rem;
}

.item-image {
    position: relative;
    flex-shrink: 0;
}

.item-image img {
    width: 120px;
    height: 120px;
    object-fit: cover;
    border-radius: var(--border-radius);
}

.stock-badge {
    position: absolute;
    top: 5px;
    left: 5px;
    padding: 0.25rem 0.5rem;
    border-radius: 12px;
    font-size: 0.8rem;
    font-weight: 600;
}

.stock-badge.out-of-stock {
    background: #F44336;
    color: white;
}

.stock-badge.low-stock {
    background: #FF9800;
    color: white;
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

.item-description {
    color: var(--dark-gray);
    margin-bottom: 0.5rem;
    line-height: 1.4;
}

.item-meta {
    display: flex;
    gap: 1rem;
    margin-bottom: 0.5rem;
    font-size: 0.9rem;
}

.item-category {
    color: var(--dark-gray);
}

.item-vendor a {
    color: var(--primary-green);
    text-decoration: none;
}

.item-rating {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-bottom: 0.5rem;
}

.stars {
    color: #FFD700;
    font-size: 0.9rem;
}

.item-added {
    font-size: 0.9rem;
}

.item-price {
    text-align: right;
    flex-shrink: 0;
    margin-right: 1rem;
}

.current-price {
    font-size: 1.25rem;
    font-weight: bold;
    color: var(--primary-green);
    margin-bottom: 0.25rem;
}

.compare-price {
    font-size: 0.9rem;
    color: var(--dark-gray);
    text-decoration: line-through;
    margin-bottom: 0.25rem;
}

.discount-badge {
    background: #4CAF50;
    color: white;
    padding: 0.25rem 0.5rem;
    border-radius: 12px;
    font-size: 0.8rem;
    font-weight: 600;
}

.item-actions {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    flex-shrink: 0;
}

.item-actions .btn {
    min-width: 120px;
    justify-content: center;
}

@media (max-width: 768px) {
    .wishlist-item {
        flex-direction: column;
        text-align: center;
    }
    
    .item-price,
    .item-actions {
        margin-right: 0;
        text-align: center;
    }
    
    .item-actions {
        flex-direction: row;
        flex-wrap: wrap;
        justify-content: center;
    }
    
    .item-actions .btn {
        min-width: auto;
        flex: 1;
    }
}
</style>

<script>
function toggleAllItems() {
    const selectAll = document.getElementById('select-all');
    const checkboxes = document.querySelectorAll('.item-checkbox-input');
    
    checkboxes.forEach(checkbox => {
        checkbox.checked = selectAll.checked;
    });
}

function confirmBulkAction() {
    const selected = document.querySelectorAll('.item-checkbox-input:checked');
    const action = document.querySelector('select[name="bulk_action"]').value;
    
    if (selected.length === 0) {
        alert('Please select at least one item.');
        return false;
    }
    
    if (!action) {
        alert('Please select an action.');
        return false;
    }
    
    const actionText = action === 'remove' ? 'remove' : 'move to cart';
    return confirm(`Are you sure you want to ${actionText} ${selected.length} selected item(s)?`);
}

function moveToCart(productId) {
    fetch('api/wishlist.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            product_id: productId,
            action: 'move_to_cart',
            quantity: 1
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification(data.message, 'success');
            // Remove item from page
            const item = document.querySelector(`[data-product-id="${productId}"]`);
            if (item) {
                item.style.animation = 'slideOut 0.3s ease forwards';
                setTimeout(() => item.remove(), 300);
            }
        } else {
            showNotification(data.message || 'Error moving to cart', 'error');
        }
    });
}

function removeFromWishlist(productId) {
    if (confirm('Are you sure you want to remove this item from your wishlist?')) {
        fetch('api/wishlist.php', {
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
                showNotification(data.message, 'success');
                // Remove item from page
                const item = document.querySelector(`[data-product-id="${productId}"]`);
                if (item) {
                    item.style.animation = 'slideOut 0.3s ease forwards';
                    setTimeout(() => item.remove(), 300);
                }
            } else {
                showNotification(data.message || 'Error removing from wishlist', 'error');
            }
        });
    }
}

function addToCompare(productId) {
    fetch('api/compare.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            product_id: productId,
            action: 'add'
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification(data.message, 'success');
        } else {
            showNotification(data.message || 'Error adding to comparison', 'error');
        }
    });
}

function moveAllToCart() {
    const inStockItems = Array.from(document.querySelectorAll('.wishlist-item')).filter(item => {
        return !item.querySelector('.out-of-stock');
    });
    
    if (inStockItems.length === 0) {
        showNotification('No items in stock to move to cart', 'error');
        return;
    }
    
    if (confirm(`Move all ${inStockItems.length} in-stock items to cart?`)) {
        // Select all in-stock items
        inStockItems.forEach(item => {
            const checkbox = item.querySelector('.item-checkbox-input');
            if (checkbox) checkbox.checked = true;
        });
        
        // Trigger bulk action
        document.querySelector('select[name="bulk_action"]').value = 'move_to_cart';
        document.querySelector('form').submit();
    }
}

function clearWishlist() {
    if (confirm('Are you sure you want to clear your entire wishlist? This action cannot be undone.')) {
        fetch('api/wishlist.php', {
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
                showNotification(data.message || 'Error clearing wishlist', 'error');
            }
        });
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
