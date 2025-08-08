<?php
/**
 * Enhanced Wishlist Page
 * Multi-Vendor E-Commerce Platform
 */

require_once 'config/config.php';

// Redirect if not logged in
if (!isLoggedIn()) {
    header('Location: login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit;
}

$page_title = 'My Wishlist';
$customer_id = $_SESSION['user_id'];

// Get wishlist items with detailed information
$wishlist_sql = "
    SELECT w.*, p.name, p.price, p.compare_price, p.stock_quantity, p.image_url, p.slug, p.brand,
           u.username as vendor_name, vs.store_name, c.name as category_name,
           AVG(pr.rating) as avg_rating, COUNT(pr.id) as review_count,
           CASE WHEN p.stock_quantity <= 0 THEN 'out_of_stock'
                WHEN p.stock_quantity <= 5 THEN 'low_stock'
                ELSE 'in_stock' END as stock_status
    FROM wishlists w
    JOIN products p ON w.product_id = p.id
    JOIN users u ON p.vendor_id = u.id
    LEFT JOIN vendor_stores vs ON u.id = vs.vendor_id
    LEFT JOIN categories c ON p.category_id = c.id
    LEFT JOIN product_reviews pr ON p.id = pr.product_id AND pr.status = 'approved'
    WHERE w.customer_id = ? AND p.status = 'active' AND u.status = 'active'
    GROUP BY w.id
    ORDER BY w.created_at DESC
";

$wishlist_items = $database->fetchAll($wishlist_sql, [$customer_id]);

// Get recommendations based on wishlist
$recommendations = [];
if (!empty($wishlist_items)) {
    // Get similar products to wishlist items
    $product_ids = array_column($wishlist_items, 'product_id');
    $category_ids = array_unique(array_column($wishlist_items, 'category_id'));
    
    if (!empty($category_ids)) {
        $placeholders = str_repeat('?,', count($category_ids) - 1) . '?';
        $product_placeholders = str_repeat('?,', count($product_ids) - 1) . '?';
        
        $recommendations_sql = "
            SELECT p.id, p.name, p.price, p.image_url, p.slug, p.brand,
                   u.username as vendor_name, vs.store_name, c.name as category_name
            FROM products p
            JOIN users u ON p.vendor_id = u.id
            LEFT JOIN vendor_stores vs ON u.id = vs.vendor_id
            LEFT JOIN categories c ON p.category_id = c.id
            WHERE p.category_id IN ($placeholders)
              AND p.id NOT IN ($product_placeholders)
              AND p.status = 'active' 
              AND u.status = 'active'
            ORDER BY p.created_at DESC
            LIMIT 8
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
            <!-- Wishlist Header -->
            <div class="wishlist-header" style="background: white; padding: 2rem; border-radius: 10px; margin-bottom: 2rem; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                    <h1 style="margin: 0; color: #e91e63;">
                        <i class="fas fa-heart"></i> My Wishlist
                    </h1>
                    <div style="display: flex; gap: 1rem; align-items: center;">
                        <span style="color: #666; font-size: 0.9rem;">
                            <?php echo count($wishlist_items); ?> items
                        </span>
                        <?php if (!empty($wishlist_items)): ?>
                            <button onclick="clearWishlist()" class="btn btn-outline-danger btn-sm">
                                <i class="fas fa-trash"></i> Clear All
                            </button>
                            <button onclick="shareWishlist()" class="btn btn-outline-primary btn-sm">
                                <i class="fas fa-share"></i> Share
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
                
                <?php if (!empty($wishlist_items)): ?>
                    <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
                        <button onclick="moveAllToCart()" class="btn btn-primary">
                            <i class="fas fa-shopping-cart"></i> Move All to Cart
                        </button>
                        <button onclick="compareSelected()" class="btn btn-outline-success">
                            <i class="fas fa-balance-scale"></i> Compare Selected
                        </button>
                        <div class="dropdown" style="display: inline-block;">
                            <button class="btn btn-outline-secondary dropdown-toggle" type="button" data-toggle="dropdown">
                                <i class="fas fa-filter"></i> Filter
                            </button>
                            <div class="dropdown-menu">
                                <a class="dropdown-item" href="#" onclick="filterWishlist('all')">All Items</a>
                                <a class="dropdown-item" href="#" onclick="filterWishlist('in_stock')">In Stock</a>
                                <a class="dropdown-item" href="#" onclick="filterWishlist('low_stock')">Low Stock</a>
                                <a class="dropdown-item" href="#" onclick="filterWishlist('out_of_stock')">Out of Stock</a>
                                <div class="dropdown-divider"></div>
                                <a class="dropdown-item" href="#" onclick="sortWishlist('price_asc')">Price: Low to High</a>
                                <a class="dropdown-item" href="#" onclick="sortWishlist('price_desc')">Price: High to Low</a>
                                <a class="dropdown-item" href="#" onclick="sortWishlist('name')">Name A-Z</a>
                                <a class="dropdown-item" href="#" onclick="sortWishlist('newest')">Newest First</a>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <?php if (empty($wishlist_items)): ?>
                <!-- Empty Wishlist -->
                <div style="text-align: center; padding: 4rem 2rem; background: white; border-radius: 10px;">
                    <i class="fas fa-heart" style="font-size: 4rem; color: #e91e63; margin-bottom: 1rem; opacity: 0.3;"></i>
                    <h3 style="color: #374151; margin-bottom: 1rem;">Your wishlist is empty</h3>
                    <p style="color: #6b7280; margin-bottom: 2rem;">
                        Save items you love to your wishlist and never lose track of them!
                    </p>
                    <div style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
                        <a href="products.php" class="btn btn-primary">
                            <i class="fas fa-shopping-bag"></i> Browse Products
                        </a>
                        <a href="products.php?category=smartphones" class="btn btn-outline-primary">📱 Smartphones</a>
                        <a href="products.php?category=laptops" class="btn btn-outline-primary">💻 Laptops</a>
                        <a href="products.php?category=fashion" class="btn btn-outline-primary">👕 Fashion</a>
                    </div>
                </div>
            <?php else: ?>
                <!-- Wishlist Items -->
                <div id="wishlist-items" class="wishlist-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 2rem; margin-bottom: 3rem;">
                    <?php foreach ($wishlist_items as $item): ?>
                        <div class="wishlist-item" data-id="<?php echo $item['product_id']; ?>" data-stock="<?php echo $item['stock_status']; ?>" data-price="<?php echo $item['price']; ?>" data-name="<?php echo htmlspecialchars($item['name']); ?>" data-date="<?php echo $item['created_at']; ?>">
                            <div style="background: white; border-radius: 10px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.1); transition: transform 0.2s;">
                                <!-- Product Image -->
                                <div style="position: relative; height: 250px; overflow: hidden;">
                                    <?php if ($item['image_url']): ?>
                                        <img src="<?php echo htmlspecialchars($item['image_url']); ?>" 
                                             alt="<?php echo htmlspecialchars($item['name']); ?>"
                                             style="width: 100%; height: 100%; object-fit: cover;">
                                    <?php else: ?>
                                        <div style="width: 100%; height: 100%; background: #f3f4f6; display: flex; align-items: center; justify-content: center;">
                                            <i class="fas fa-image" style="font-size: 3rem; color: #9ca3af;"></i>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <!-- Stock Status Badge -->
                                    <?php
                                    $badge_colors = [
                                        'out_of_stock' => '#ef4444',
                                        'low_stock' => '#f59e0b',
                                        'in_stock' => '#10b981'
                                    ];
                                    $badge_texts = [
                                        'out_of_stock' => 'Out of Stock',
                                        'low_stock' => 'Low Stock',
                                        'in_stock' => 'In Stock'
                                    ];
                                    ?>
                                    <div style="position: absolute; top: 10px; right: 10px; background: <?php echo $badge_colors[$item['stock_status']]; ?>; color: white; padding: 4px 8px; border-radius: 4px; font-size: 12px;">
                                        <?php echo $badge_texts[$item['stock_status']]; ?>
                                    </div>
                                    
                                    <!-- Action Buttons -->
                                    <div style="position: absolute; top: 10px; left: 10px; display: flex; gap: 5px;">
                                        <button onclick="removeFromWishlist(<?php echo $item['product_id']; ?>)" 
                                                class="btn btn-sm" 
                                                style="background: rgba(239, 68, 68, 0.9); color: white; border: none; border-radius: 50%; width: 35px; height: 35px;"
                                                title="Remove from wishlist">
                                            <i class="fas fa-times"></i>
                                        </button>
                                        <button onclick="addToCompare(<?php echo $item['product_id']; ?>)" 
                                                class="btn btn-sm" 
                                                style="background: rgba(16, 185, 129, 0.9); color: white; border: none; border-radius: 50%; width: 35px; height: 35px;"
                                                title="Add to compare">
                                            <i class="fas fa-balance-scale"></i>
                                        </button>
                                    </div>
                                    
                                    <!-- Selection Checkbox -->
                                    <div style="position: absolute; bottom: 10px; left: 10px;">
                                        <input type="checkbox" class="item-select" value="<?php echo $item['product_id']; ?>" 
                                               style="transform: scale(1.2);">
                                    </div>
                                </div>

                                <!-- Product Info -->
                                <div style="padding: 1.5rem;">
                                    <h3 style="margin: 0 0 0.5rem 0; font-size: 1.1rem; color: #374151;">
                                        <a href="product.php?id=<?php echo $item['product_id']; ?>" style="text-decoration: none; color: inherit;">
                                            <?php echo htmlspecialchars($item['name']); ?>
                                        </a>
                                    </h3>
                                    
                                    <?php if ($item['brand']): ?>
                                        <p style="margin: 0 0 0.5rem 0; color: #6b7280; font-size: 0.9rem;">
                                            <strong>Brand:</strong> <?php echo htmlspecialchars($item['brand']); ?>
                                        </p>
                                    <?php endif; ?>
                                    
                                    <p style="margin: 0 0 0.5rem 0; color: #6b7280; font-size: 0.9rem;">
                                        <strong>Vendor:</strong> <?php echo htmlspecialchars($item['store_name'] ?: $item['vendor_name']); ?>
                                    </p>
                                    
                                    <p style="margin: 0 0 1rem 0; color: #6b7280; font-size: 0.9rem;">
                                        <strong>Category:</strong> <?php echo htmlspecialchars($item['category_name'] ?: 'Uncategorized'); ?>
                                    </p>
                                    
                                    <!-- Price -->
                                    <div style="margin-bottom: 1rem;">
                                        <span style="font-size: 1.25rem; font-weight: bold; color: #e91e63;">
                                            RWF <?php echo number_format($item['price']); ?>
                                        </span>
                                        <?php if ($item['compare_price'] && $item['compare_price'] > $item['price']): ?>
                                            <span style="text-decoration: line-through; color: #9ca3af; margin-left: 0.5rem;">
                                                RWF <?php echo number_format($item['compare_price']); ?>
                                            </span>
                                            <?php
                                            $discount = round((($item['compare_price'] - $item['price']) / $item['compare_price']) * 100);
                                            ?>
                                            <span style="background: #ef4444; color: white; padding: 2px 6px; border-radius: 3px; font-size: 12px; margin-left: 0.5rem;">
                                                -<?php echo $discount; ?>%
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <!-- Rating -->
                                    <?php if ($item['avg_rating'] > 0): ?>
                                        <div style="margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem;">
                                            <div style="color: #fbbf24;">
                                                <?php
                                                $rating = round($item['avg_rating']);
                                                for ($i = 1; $i <= 5; $i++) {
                                                    echo $i <= $rating ? '★' : '☆';
                                                }
                                                ?>
                                            </div>
                                            <span style="color: #6b7280; font-size: 0.9rem;">
                                                (<?php echo $item['review_count']; ?> reviews)
                                            </span>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <!-- Actions -->
                                    <div style="display: flex; gap: 0.5rem;">
                                        <?php if ($item['stock_status'] !== 'out_of_stock'): ?>
                                            <button onclick="addToCart(<?php echo $item['product_id']; ?>)" class="btn btn-primary" style="flex: 1;">
                                                <i class="fas fa-shopping-cart"></i> Add to Cart
                                            </button>
                                        <?php else: ?>
                                            <button class="btn btn-secondary" style="flex: 1;" disabled>
                                                <i class="fas fa-ban"></i> Out of Stock
                                            </button>
                                        <?php endif; ?>
                                        <a href="product.php?id=<?php echo $item['product_id']; ?>" class="btn btn-outline-primary" style="padding: 0.5rem;">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </div>
                                    
                                    <!-- Added Date -->
                                    <div style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid #e5e7eb;">
                                        <small style="color: #9ca3af;">
                                            Added <?php echo date('M j, Y', strtotime($item['created_at'])); ?>
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
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
                                    <p style="margin: 0 0 0.5rem 0; color: #e91e63; font-weight: bold;">
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

<!-- Share Modal -->
<div class="modal fade" id="shareModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Share Your Wishlist</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label>Share Link</label>
                    <div class="input-group">
                        <input type="text" id="shareLink" class="form-control" readonly>
                        <div class="input-group-append">
                            <button class="btn btn-outline-secondary" onclick="copyShareLink()">
                                <i class="fas fa-copy"></i> Copy
                            </button>
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <label>
                        <input type="checkbox" id="sharePublic"> Make public (anyone with link can view)
                    </label>
                </div>
                <div class="form-group">
                    <label>Password Protection (optional)</label>
                    <input type="password" id="sharePassword" class="form-control" placeholder="Enter password">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="generateShareLink()">Generate Link</button>
            </div>
        </div>
    </div>
</div>

<style>
.wishlist-item:hover {
    transform: translateY(-2px);
}

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
    background: #e91e63;
    color: white;
    border-color: #e91e63;
}

.btn-primary:hover {
    background: #c2185b;
    border-color: #c2185b;
}

.btn-outline-primary {
    background: transparent;
    color: #e91e63;
    border-color: #e91e63;
}

.btn-outline-primary:hover {
    background: #e91e63;
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
</style>

<script>
// Wishlist functionality
function removeFromWishlist(productId) {
    if (!confirm('Remove this item from your wishlist?')) return;
    
    fetch('/ange Final/api/wishlist.php', {
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
            // Remove item from DOM
            const item = document.querySelector(`[data-id="${productId}"]`);
            if (item) {
                item.style.transition = 'opacity 0.3s';
                item.style.opacity = '0';
                setTimeout(() => item.remove(), 300);
            }
            
            showNotification('Item removed from wishlist', 'success');
            
            // Reload page if no items left
            setTimeout(() => {
                if (document.querySelectorAll('.wishlist-item').length === 1) {
                    location.reload();
                }
            }, 500);
        } else {
            showNotification(data.message || 'Error removing item', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Error removing item', 'error');
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

function clearWishlist() {
    if (!confirm('Are you sure you want to clear your entire wishlist?')) return;
    
    fetch('/ange Final/api/wishlist.php', {
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
            showNotification('Wishlist cleared', 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            showNotification(data.message || 'Error clearing wishlist', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Error clearing wishlist', 'error');
    });
}

function moveAllToCart() {
    const items = document.querySelectorAll('.wishlist-item');
    let promises = [];
    
    items.forEach(item => {
        const productId = item.dataset.id;
        const stockStatus = item.dataset.stock;
        
        if (stockStatus !== 'out_of_stock') {
            promises.push(
                fetch('/ange Final/api/cart.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        action: 'add',
                        product_id: parseInt(productId),
                        quantity: 1
                    })
                })
            );
        }
    });
    
    Promise.all(promises)
        .then(responses => Promise.all(responses.map(r => r.json())))
        .then(results => {
            const successful = results.filter(r => r.success).length;
            showNotification(`${successful} items moved to cart!`, 'success');
            updateCartCount();
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('Error moving items to cart', 'error');
        });
}

function compareSelected() {
    const selected = document.querySelectorAll('.item-select:checked');
    if (selected.length < 2) {
        showNotification('Please select at least 2 items to compare', 'warning');
        return;
    }
    
    if (selected.length > 4) {
        showNotification('You can compare maximum 4 items at once', 'warning');
        return;
    }
    
    const productIds = Array.from(selected).map(cb => cb.value);
    window.location.href = `compare.php?products=${productIds.join(',')}`;
}

function filterWishlist(filter) {
    const items = document.querySelectorAll('.wishlist-item');
    
    items.forEach(item => {
        const stockStatus = item.dataset.stock;
        let show = true;
        
        switch (filter) {
            case 'in_stock':
                show = stockStatus === 'in_stock';
                break;
            case 'low_stock':
                show = stockStatus === 'low_stock';
                break;
            case 'out_of_stock':
                show = stockStatus === 'out_of_stock';
                break;
            case 'all':
            default:
                show = true;
                break;
        }
        
        item.style.display = show ? 'block' : 'none';
    });
}

function sortWishlist(sortBy) {
    const container = document.getElementById('wishlist-items');
    const items = Array.from(container.children);
    
    items.sort((a, b) => {
        switch (sortBy) {
            case 'price_asc':
                return parseFloat(a.dataset.price) - parseFloat(b.dataset.price);
            case 'price_desc':
                return parseFloat(b.dataset.price) - parseFloat(a.dataset.price);
            case 'name':
                return a.dataset.name.localeCompare(b.dataset.name);
            case 'newest':
                return new Date(b.dataset.date) - new Date(a.dataset.date);
            default:
                return 0;
        }
    });
    
    items.forEach(item => container.appendChild(item));
}

function shareWishlist() {
    $('#shareModal').modal('show');
}

function generateShareLink() {
    const isPublic = document.getElementById('sharePublic').checked;
    const password = document.getElementById('sharePassword').value;
    
    fetch('/ange Final/api/share.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'create',
            list_type: 'wishlist',
            is_public: isPublic,
            password: password
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            document.getElementById('shareLink').value = data.share_url;
            showNotification('Share link generated!', 'success');
        } else {
            showNotification(data.message || 'Error generating share link', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Error generating share link', 'error');
    });
}

function copyShareLink() {
    const shareLink = document.getElementById('shareLink');
    shareLink.select();
    document.execCommand('copy');
    showNotification('Link copied to clipboard!', 'success');
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
