<?php
/**
 * MarketHub Customer Dashboard
 * Multi-Vendor E-Commerce Platform
 */

require_once 'config/config.php';

$page_title = 'My Dashboard';

// Require customer login
requireLogin();

$customer_id = $_SESSION['user_id'];

// Get customer information
$customer_query = "SELECT * FROM users WHERE id = ?";
$customer = $database->fetch($customer_query, [$customer_id]);

// Get dashboard statistics
$stats_queries = [
    'total_orders' => "SELECT COUNT(*) as count FROM orders WHERE customer_id = ?",
    'pending_orders' => "SELECT COUNT(*) as count FROM orders WHERE customer_id = ? AND status IN ('pending', 'confirmed')",
    'completed_orders' => "SELECT COUNT(*) as count FROM orders WHERE customer_id = ? AND status = 'delivered'",
    'total_spent' => "SELECT COALESCE(SUM(total_amount), 0) as total FROM orders WHERE customer_id = ? AND payment_status = 'paid'",
    'wishlist_items' => "SELECT COUNT(*) as count FROM wishlists WHERE customer_id = ?",
    'reviews_written' => "SELECT COUNT(*) as count FROM product_reviews WHERE customer_id = ?"
];

$stats = [];
foreach ($stats_queries as $key => $query) {
    $result = $database->fetch($query, [$customer_id]);
    $stats[$key] = $result['count'] ?? $result['total'] ?? 0;
}

// Get recent orders
$recent_orders_query = "
    SELECT o.*, COUNT(oi.id) as item_count
    FROM orders o
    LEFT JOIN order_items oi ON o.id = oi.order_id
    WHERE o.customer_id = ?
    GROUP BY o.id
    ORDER BY o.created_at DESC
    LIMIT 5
";

$recent_orders = $database->fetchAll($recent_orders_query, [$customer_id]);

// Get wishlist items
$wishlist_query = "
    SELECT w.*, p.name, p.price, p.stock_quantity, pi.image_url,
           u.username as vendor_name, vs.store_name
    FROM wishlists w
    JOIN products p ON w.product_id = p.id
    LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
    LEFT JOIN users u ON p.vendor_id = u.id
    LEFT JOIN vendor_stores vs ON u.id = vs.vendor_id
    WHERE w.customer_id = ? AND p.status = 'active'
    ORDER BY w.created_at DESC
    LIMIT 6
";

$wishlist_items = $database->fetchAll($wishlist_query, [$customer_id]);

// Get recent reviews
$recent_reviews_query = "
    SELECT pr.*, p.name as product_name, pi.image_url
    FROM product_reviews pr
    JOIN products p ON pr.product_id = p.id
    LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
    WHERE pr.customer_id = ?
    ORDER BY pr.created_at DESC
    LIMIT 5
";

$recent_reviews = $database->fetchAll($recent_reviews_query, [$customer_id]);

// Get recommended products (based on purchase history and wishlist)
$recommended_query = "
    SELECT DISTINCT p.id, p.name, p.price, pi.image_url,
           u.username as vendor_name, vs.store_name,
           AVG(pr.rating) as avg_rating, COUNT(pr.id) as review_count
    FROM products p
    LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
    LEFT JOIN users u ON p.vendor_id = u.id
    LEFT JOIN vendor_stores vs ON u.id = vs.vendor_id
    LEFT JOIN product_reviews pr ON p.id = pr.product_id AND pr.status = 'approved'
    WHERE p.status = 'active'
    AND p.category_id IN (
        SELECT DISTINCT p2.category_id 
        FROM order_items oi 
        JOIN products p2 ON oi.product_id = p2.id 
        JOIN orders o ON oi.order_id = o.id 
        WHERE o.customer_id = ?
        UNION
        SELECT DISTINCT p3.category_id 
        FROM wishlists w 
        JOIN products p3 ON w.product_id = p3.id 
        WHERE w.customer_id = ?
    )
    AND p.id NOT IN (
        SELECT DISTINCT oi.product_id 
        FROM order_items oi 
        JOIN orders o ON oi.order_id = o.id 
        WHERE o.customer_id = ?
    )
    AND p.id NOT IN (
        SELECT product_id FROM wishlists WHERE customer_id = ?
    )
    GROUP BY p.id
    ORDER BY avg_rating DESC, review_count DESC
    LIMIT 8
";

$recommended_products = $database->fetchAll($recommended_query, [$customer_id, $customer_id, $customer_id, $customer_id]);

require_once 'includes/header.php';
?>

<div class="container" style="margin: 2rem auto;">
    <!-- Dashboard Header -->
    <div class="row align-items-center mb-3">
        <div class="col-8">
            <h1>Welcome back, <?php echo htmlspecialchars($customer['first_name']); ?>!</h1>
            <p class="text-muted">Manage your orders, wishlist, and account settings</p>
        </div>
        <div class="col-4 text-right">
            <div class="d-flex justify-content-end" style="gap: 1rem;">
                <a href="products.php" class="btn btn-primary">
                    <i class="fas fa-shopping-bag"></i> Continue Shopping
                </a>
                <a href="profile.php" class="btn btn-outline">
                    <i class="fas fa-user-cog"></i> Edit Profile
                </a>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-3">
        <div class="col-3">
            <div class="card">
                <div class="card-body text-center">
                    <i class="fas fa-shopping-cart" style="font-size: 2rem; color: var(--primary-green); margin-bottom: 0.5rem;"></i>
                    <h3 style="color: var(--primary-green); margin-bottom: 0.5rem;"><?php echo $stats['total_orders']; ?></h3>
                    <p style="margin-bottom: 0; color: var(--dark-gray);">Total Orders</p>
                    <small class="text-muted"><?php echo $stats['pending_orders']; ?> pending</small>
                </div>
            </div>
        </div>
        <div class="col-3">
            <div class="card">
                <div class="card-body text-center">
                    <i class="fas fa-money-bill-wave" style="font-size: 2rem; color: var(--secondary-green); margin-bottom: 0.5rem;"></i>
                    <h3 style="color: var(--secondary-green); margin-bottom: 0.5rem;"><?php echo formatCurrency($stats['total_spent']); ?></h3>
                    <p style="margin-bottom: 0; color: var(--dark-gray);">Total Spent</p>
                    <small class="text-muted">All time</small>
                </div>
            </div>
        </div>
        <div class="col-3">
            <div class="card">
                <div class="card-body text-center">
                    <i class="fas fa-heart" style="font-size: 2rem; color: #E91E63; margin-bottom: 0.5rem;"></i>
                    <h3 style="color: #E91E63; margin-bottom: 0.5rem;"><?php echo $stats['wishlist_items']; ?></h3>
                    <p style="margin-bottom: 0; color: var(--dark-gray);">Wishlist Items</p>
                    <small class="text-muted">Saved products</small>
                </div>
            </div>
        </div>
        <div class="col-3">
            <div class="card">
                <div class="card-body text-center">
                    <i class="fas fa-star" style="font-size: 2rem; color: #FFB74D; margin-bottom: 0.5rem;"></i>
                    <h3 style="color: #FFB74D; margin-bottom: 0.5rem;"><?php echo $stats['reviews_written']; ?></h3>
                    <p style="margin-bottom: 0; color: var(--dark-gray);">Reviews Written</p>
                    <small class="text-muted">Product reviews</small>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Recent Orders -->
        <div class="col-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 style="margin-bottom: 0;">Recent Orders</h5>
                    <a href="orders.php" class="btn btn-outline btn-sm">View All Orders</a>
                </div>
                <div class="card-body">
                    <?php if (empty($recent_orders)): ?>
                        <div class="text-center" style="padding: 2rem;">
                            <i class="fas fa-shopping-cart" style="font-size: 3rem; color: var(--medium-gray); margin-bottom: 1rem;"></i>
                            <h6>No orders yet</h6>
                            <p class="text-muted">Start shopping to see your orders here.</p>
                            <a href="products.php" class="btn btn-primary">Start Shopping</a>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table style="width: 100%; border-collapse: collapse;">
                                <thead>
                                    <tr style="border-bottom: 2px solid var(--light-gray);">
                                        <th style="padding: 0.75rem; text-align: left;">Order #</th>
                                        <th style="padding: 0.75rem; text-align: left;">Items</th>
                                        <th style="padding: 0.75rem; text-align: left;">Total</th>
                                        <th style="padding: 0.75rem; text-align: left;">Status</th>
                                        <th style="padding: 0.75rem; text-align: left;">Date</th>
                                        <th style="padding: 0.75rem; text-align: left;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recent_orders as $order): ?>
                                        <tr style="border-bottom: 1px solid var(--light-gray);">
                                            <td style="padding: 0.75rem;">
                                                <strong><?php echo htmlspecialchars($order['order_number']); ?></strong>
                                            </td>
                                            <td style="padding: 0.75rem;"><?php echo $order['item_count']; ?> items</td>
                                            <td style="padding: 0.75rem; font-weight: 600;"><?php echo formatCurrency($order['total_amount']); ?></td>
                                            <td style="padding: 0.75rem;">
                                                <span class="badge" style="background: <?php 
                                                    echo $order['status'] === 'pending' ? '#FFA726' : 
                                                        ($order['status'] === 'confirmed' ? '#66BB6A' : 
                                                        ($order['status'] === 'shipped' ? '#42A5F5' : 
                                                        ($order['status'] === 'delivered' ? '#4CAF50' : '#9E9E9E'))); 
                                                ?>; color: white; padding: 0.25rem 0.5rem; border-radius: 12px; font-size: 0.8rem;">
                                                    <?php echo ucfirst($order['status']); ?>
                                                </span>
                                            </td>
                                            <td style="padding: 0.75rem; color: var(--dark-gray);">
                                                <?php echo date('M j, Y', strtotime($order['created_at'])); ?>
                                            </td>
                                            <td style="padding: 0.75rem;">
                                                <a href="order-details.php?id=<?php echo $order['id']; ?>" class="btn btn-outline btn-sm">
                                                    View Details
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Quick Actions Sidebar -->
        <div class="col-4">
            <!-- Account Summary -->
            <div class="card mb-3">
                <div class="card-header">
                    <h6 style="margin-bottom: 0;">Account Summary</h6>
                </div>
                <div class="card-body">
                    <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 1rem;">
                        <div style="width: 60px; height: 60px; background: var(--primary-green); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-size: 1.5rem; font-weight: bold;">
                            <?php echo strtoupper(substr($customer['first_name'], 0, 1) . substr($customer['last_name'], 0, 1)); ?>
                        </div>
                        <div>
                            <strong><?php echo htmlspecialchars($customer['first_name'] . ' ' . $customer['last_name']); ?></strong>
                            <br>
                            <small class="text-muted"><?php echo htmlspecialchars($customer['email']); ?></small>
                        </div>
                    </div>
                    <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                        <span>Member since:</span>
                        <strong><?php echo date('M Y', strtotime($customer['created_at'])); ?></strong>
                    </div>
                    <div style="display: flex; justify-content: space-between;">
                        <span>Account status:</span>
                        <span style="color: var(--secondary-green); font-weight: 600;">Active</span>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="card">
                <div class="card-header">
                    <h6 style="margin-bottom: 0;">Quick Actions</h6>
                </div>
                <div class="card-body">
                    <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                        <a href="wishlist.php" class="btn btn-outline" style="justify-content: flex-start; display: flex; align-items: center; gap: 0.5rem;">
                            <i class="fas fa-heart"></i> View Wishlist (<?php echo $stats['wishlist_items']; ?>)
                        </a>
                        <a href="orders.php" class="btn btn-outline" style="justify-content: flex-start; display: flex; align-items: center; gap: 0.5rem;">
                            <i class="fas fa-clipboard-list"></i> Order History
                        </a>
                        <a href="addresses.php" class="btn btn-outline" style="justify-content: flex-start; display: flex; align-items: center; gap: 0.5rem;">
                            <i class="fas fa-map-marker-alt"></i> Manage Addresses
                        </a>
                        <a href="reviews.php" class="btn btn-outline" style="justify-content: flex-start; display: flex; align-items: center; gap: 0.5rem;">
                            <i class="fas fa-star"></i> My Reviews
                        </a>
                        <a href="compare.php" class="btn btn-outline" style="justify-content: flex-start; display: flex; align-items: center; gap: 0.5rem;">
                            <i class="fas fa-balance-scale"></i> Compare Products
                        </a>
                        <a href="profile.php" class="btn btn-outline" style="justify-content: flex-start; display: flex; align-items: center; gap: 0.5rem;">
                            <i class="fas fa-user-cog"></i> Account Settings
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Wishlist Preview -->
    <?php if (!empty($wishlist_items)): ?>
        <div class="card mt-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 style="margin-bottom: 0;">Your Wishlist</h5>
                <a href="wishlist.php" class="btn btn-outline btn-sm">View All</a>
            </div>
            <div class="card-body">
                <div class="row">
                    <?php foreach ($wishlist_items as $item): ?>
                        <div class="col-4 mb-2">
                            <div style="border: 1px solid var(--light-gray); border-radius: var(--border-radius); padding: 1rem; text-align: center; position: relative;">
                                <button onclick="removeFromWishlist(<?php echo $item['product_id']; ?>)" 
                                        style="position: absolute; top: 5px; right: 5px; background: #F44336; color: white; border: none; border-radius: 50%; width: 24px; height: 24px; font-size: 0.8rem; cursor: pointer;">
                                    ×
                                </button>
                                <img src="<?php echo $item['image_url'] ?: 'assets/images/product-placeholder.png'; ?>" 
                                     alt="<?php echo htmlspecialchars($item['name']); ?>" 
                                     style="width: 80px; height: 80px; object-fit: cover; border-radius: 4px; margin-bottom: 0.5rem;">
                                <h6 style="font-size: 0.9rem; margin-bottom: 0.5rem;"><?php echo htmlspecialchars(substr($item['name'], 0, 30)); ?><?php echo strlen($item['name']) > 30 ? '...' : ''; ?></h6>
                                <div style="color: var(--primary-green); font-weight: 600; margin-bottom: 0.5rem;">
                                    <?php echo formatCurrency($item['price']); ?>
                                </div>
                                <small class="text-muted">by <?php echo htmlspecialchars($item['store_name'] ?: $item['vendor_name']); ?></small>
                                <div style="margin-top: 0.5rem;">
                                    <?php if ($item['stock_quantity'] > 0): ?>
                                        <button onclick="addToCart(<?php echo $item['product_id']; ?>)" class="btn btn-primary btn-sm" style="width: 100%;">
                                            Add to Cart
                                        </button>
                                    <?php else: ?>
                                        <button class="btn btn-outline btn-sm" style="width: 100%;" disabled>
                                            Out of Stock
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Recommended Products -->
    <?php if (!empty($recommended_products)): ?>
        <div class="card mt-3">
            <div class="card-header">
                <h5 style="margin-bottom: 0;">Recommended for You</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <?php foreach ($recommended_products as $product): ?>
                        <div class="col-3 mb-2">
                            <div style="border: 1px solid var(--light-gray); border-radius: var(--border-radius); padding: 1rem; text-align: center;">
                                <img src="<?php echo $product['image_url'] ?: 'assets/images/product-placeholder.png'; ?>" 
                                     alt="<?php echo htmlspecialchars($product['name']); ?>" 
                                     style="width: 100px; height: 100px; object-fit: cover; border-radius: 4px; margin-bottom: 0.5rem;">
                                <h6 style="font-size: 0.9rem; margin-bottom: 0.5rem;"><?php echo htmlspecialchars(substr($product['name'], 0, 25)); ?><?php echo strlen($product['name']) > 25 ? '...' : ''; ?></h6>
                                <div style="color: var(--primary-green); font-weight: 600; margin-bottom: 0.5rem;">
                                    <?php echo formatCurrency($product['price']); ?>
                                </div>
                                <?php if ($product['review_count'] > 0): ?>
                                    <div class="stars" style="color: #FFD700; font-size: 0.8rem; margin-bottom: 0.5rem;">
                                        <?php 
                                        $rating = round($product['avg_rating']);
                                        for ($i = 1; $i <= 5; $i++) {
                                            echo $i <= $rating ? '★' : '☆';
                                        }
                                        ?>
                                    </div>
                                <?php endif; ?>
                                <div style="display: flex; gap: 0.25rem;">
                                    <a href="product.php?id=<?php echo $product['id']; ?>" class="btn btn-outline btn-sm" style="flex: 1;">View</a>
                                    <button onclick="addToWishlist(<?php echo $product['id']; ?>)" class="btn btn-outline btn-sm" title="Add to Wishlist">
                                        <i class="fas fa-heart"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<style>
.card {
    transition: var(--transition);
}

.card:hover {
    box-shadow: 0 8px 25px rgba(0,0,0,0.15);
}

.badge {
    font-size: 0.75rem;
    padding: 0.25rem 0.5rem;
    border-radius: 12px;
}

.stars {
    font-size: 0.9rem;
}

@media (max-width: 768px) {
    .col-3, .col-4, .col-8 {
        flex: 0 0 100%;
        margin-bottom: 1rem;
    }
    
    .d-flex {
        flex-direction: column;
        gap: 0.5rem;
    }
}
</style>

<script>
function addToCart(productId) {
    fetch('api/cart.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            product_id: productId,
            action: 'add',
            quantity: 1
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Product added to cart!', 'success');
        } else {
            showNotification(data.message || 'Error adding to cart', 'error');
        }
    });
}

function addToWishlist(productId) {
    fetch('api/wishlist.php', {
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
            showNotification('Product added to wishlist!', 'success');
        } else {
            showNotification(data.message || 'Error adding to wishlist', 'error');
        }
    });
}

function removeFromWishlist(productId) {
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
            location.reload();
        } else {
            showNotification(data.message || 'Error removing from wishlist', 'error');
        }
    });
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
</script>

<?php require_once 'includes/footer.php'; ?>
