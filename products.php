<?php
/**
 * MarketHub Products Listing
 * Multi-Vendor E-Commerce Platform
 */

require_once 'config/config.php';
require_once 'includes/image-helper.php';

$page_title = 'Products';

// Get filters from URL
$category_slug = $_GET['category'] ?? '';
$search_query = $_GET['q'] ?? '';
$sort_by = $_GET['sort'] ?? 'newest';
$min_price = $_GET['min_price'] ?? '';
$max_price = $_GET['max_price'] ?? '';
$vendor_id = $_GET['vendor'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));

// Build WHERE clause (only show products from active vendors)
$where_conditions = [
    "p.status = 'active'",
    "u.status = 'active'",
    "u.user_type = 'vendor'"
];
$params = [];

if ($category_slug) {
    $where_conditions[] = "c.slug = ?";
    $params[] = $category_slug;
}

if ($search_query) {
    $where_conditions[] = "(p.name LIKE ? OR p.description LIKE ? OR p.short_description LIKE ?)";
    $search_term = "%$search_query%";
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
}

if ($min_price) {
    $where_conditions[] = "p.price >= ?";
    $params[] = $min_price;
}

if ($max_price) {
    $where_conditions[] = "p.price <= ?";
    $params[] = $max_price;
}

if ($vendor_id) {
    $where_conditions[] = "p.vendor_id = ?";
    $params[] = $vendor_id;
}

$where_clause = implode(' AND ', $where_conditions);

// Build ORDER BY clause
$order_by = "p.created_at DESC";
switch ($sort_by) {
    case 'price_low':
        $order_by = "p.price ASC";
        break;
    case 'price_high':
        $order_by = "p.price DESC";
        break;
    case 'rating':
        $order_by = "avg_rating DESC";
        break;
    case 'popular':
        $order_by = "review_count DESC";
        break;
    case 'name':
        $order_by = "p.name ASC";
        break;
}

// Get total count for pagination
$count_sql = "
    SELECT COUNT(DISTINCT p.id) as total
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    INNER JOIN users u ON p.vendor_id = u.id
    WHERE $where_clause
";

$total_products = $database->fetch($count_sql, $params)['total'];
$pagination = getPagination($total_products, PRODUCTS_PER_PAGE, $page);

// Get products (simplified query for better performance)
$products_sql = "
    SELECT p.id, p.name, p.price, p.image_url, p.stock_quantity, p.compare_price, p.vendor_id,
           u.username as vendor_name, vs.store_name, c.name as category_name
    FROM products p
    INNER JOIN users u ON p.vendor_id = u.id
    LEFT JOIN vendor_stores vs ON u.id = vs.vendor_id
    LEFT JOIN categories c ON p.category_id = c.id
    WHERE $where_clause
    ORDER BY $order_by
    LIMIT {$pagination['records_per_page']} OFFSET {$pagination['offset']}
";

$products = $database->fetchAll($products_sql, $params);

// Get categories for filter
$categories = $database->fetchAll("SELECT * FROM categories WHERE parent_id IS NULL AND status = 'active' ORDER BY name");

// Get vendors for filter
$vendors = $database->fetchAll("
    SELECT u.id, u.username, vs.store_name 
    FROM users u 
    LEFT JOIN vendor_stores vs ON u.id = vs.vendor_id 
    WHERE u.user_type = 'vendor' AND u.status = 'active'
    ORDER BY COALESCE(vs.store_name, u.username)
");

require_once 'includes/header.php';
?>

<div class="container" style="margin: 2rem auto;">
    <!-- Page Header -->
    <div class="row align-items-center mb-3">
        <div class="col-6">
            <h1>Products</h1>
            <p class="text-muted">
                <?php if ($search_query): ?>
                    Search results for "<?php echo htmlspecialchars($search_query); ?>" 
                <?php elseif ($category_slug): ?>
                    Category: <?php echo htmlspecialchars($category_slug); ?>
                <?php else: ?>
                    Browse all products from our vendors
                <?php endif; ?>
                (<?php echo $total_products; ?> products found)
            </p>
        </div>
        <div class="col-6 text-right">
            <a href="vendor-comparison.php<?php echo !empty($category_slug) ? '?category=' . $category_slug : ''; ?>" class="btn btn-outline">
                <i class="fas fa-store"></i>
                Compare Vendors
            </a>
            <a href="compare.php" class="btn btn-outline">
                <i class="fas fa-balance-scale"></i>
                Compare Products
                <?php if (isset($_SESSION['compare_items']) && count($_SESSION['compare_items']) > 0): ?>
                    <span class="badge" style="background: var(--primary-green); color: white; margin-left: 0.5rem;">
                        <?php echo count($_SESSION['compare_items']); ?>
                    </span>
                <?php endif; ?>
            </a>
        </div>
    </div>

    <div class="row">
        <!-- Filters Sidebar -->
        <div class="col-3">
            <div class="card">
                <div class="card-header">
                    <h5>Filters</h5>
                </div>
                <div class="card-body">
                    <form method="GET" action="">
                        <!-- Search Input -->
                        <div class="form-group">
                            <label class="form-label">Search Products</label>
                            <input type="text" name="q" class="form-control"
                                   placeholder="Search by name, brand, or description..."
                                   value="<?php echo htmlspecialchars($search_query); ?>">
                        </div>

                        <!-- Category Filter -->
                        <div class="form-group">
                            <label class="form-label">Category</label>
                            <select name="category" class="form-control form-select">
                                <option value="">All Categories</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo $category['slug']; ?>" 
                                            <?php echo $category_slug === $category['slug'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($category['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <!-- Price Range -->
                        <div class="form-group">
                            <label class="form-label">Price Range (RWF)</label>
                            <div style="display: flex; gap: 0.5rem;">
                                <input type="number" name="min_price" placeholder="Min" class="form-control" 
                                       value="<?php echo htmlspecialchars($min_price); ?>">
                                <input type="number" name="max_price" placeholder="Max" class="form-control"
                                       value="<?php echo htmlspecialchars($max_price); ?>">
                            </div>
                        </div>
                        
                        <!-- Vendor Filter -->
                        <div class="form-group">
                            <label class="form-label">Vendor</label>
                            <select name="vendor" class="form-control form-select">
                                <option value="">All Vendors</option>
                                <?php foreach ($vendors as $vendor): ?>
                                    <option value="<?php echo $vendor['id']; ?>"
                                            <?php echo $vendor_id == $vendor['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($vendor['store_name'] ?: $vendor['username']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <button type="submit" class="btn btn-primary" style="width: 100%;">Apply Filters</button>
                        <a href="products.php" class="btn btn-outline" style="width: 100%; margin-top: 0.5rem;">Clear Filters</a>
                    </form>
                </div>
            </div>
        </div>

        <!-- Products Grid -->
        <div class="col-9">
            <!-- Sort Options -->
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <strong><?php echo $total_products; ?></strong> products found
                </div>
                <div>
                    <form method="GET" style="display: inline-block;">
                        <!-- Preserve all current filters -->
                        <?php foreach ($_GET as $key => $value): ?>
                            <?php if ($key !== 'sort'): ?>
                                <input type="hidden" name="<?php echo htmlspecialchars($key); ?>" value="<?php echo htmlspecialchars($value); ?>">
                            <?php endif; ?>
                        <?php endforeach; ?>
                        
                        <select name="sort" class="form-control form-select" style="width: auto; display: inline-block;" onchange="this.form.submit()">
                            <option value="newest" <?php echo $sort_by === 'newest' ? 'selected' : ''; ?>>Newest First</option>
                            <option value="price_low" <?php echo $sort_by === 'price_low' ? 'selected' : ''; ?>>Price: Low to High</option>
                            <option value="price_high" <?php echo $sort_by === 'price_high' ? 'selected' : ''; ?>>Price: High to Low</option>
                            <option value="rating" <?php echo $sort_by === 'rating' ? 'selected' : ''; ?>>Highest Rated</option>
                            <option value="popular" <?php echo $sort_by === 'popular' ? 'selected' : ''; ?>>Most Popular</option>
                            <option value="name" <?php echo $sort_by === 'name' ? 'selected' : ''; ?>>Name A-Z</option>
                        </select>
                    </form>
                </div>
            </div>

            <!-- Products Grid -->
            <?php if (empty($products)): ?>
                <div class="text-center" style="padding: 4rem 0;">
                    <i class="fas fa-search" style="font-size: 4rem; color: var(--medium-gray); margin-bottom: 1rem;"></i>
                    <h3>No products found</h3>
                    <p class="text-muted">Try adjusting your search criteria or browse all products.</p>
                    <a href="products.php" class="btn btn-primary">Browse All Products</a>
                </div>
            <?php else: ?>
                <div class="row">
                    <?php foreach ($products as $product): ?>
                        <div class="col-4 mb-3">
                            <div class="card product-card">
                                <div style="position: relative;">
                                    <?php
                                    echo generateImageTag(
                                        $product['image_url'],
                                        htmlspecialchars($product['name']),
                                        [
                                            'class' => 'product-image',
                                            'style' => 'width: 100%; height: 200px; object-fit: cover;'
                                        ],
                                        'product'
                                    );
                                    ?>
                                    
                                    <!-- Quick Actions -->
                                    <div style="position: absolute; top: 10px; right: 10px; display: flex; flex-direction: column; gap: 0.5rem;">
                                        <button onclick="addToCompare(<?php echo $product['id']; ?>)"
                                                class="btn btn-sm compare-btn"
                                                style="background: rgba(255,255,255,0.9); color: var(--primary-green); border: none; border-radius: 50%; width: 40px; height: 40px;"
                                                title="Add to Compare"
                                                data-product-id="<?php echo $product['id']; ?>">
                                            <i class="fas fa-balance-scale"></i>
                                        </button>

                                        <?php if (isLoggedIn()): ?>
                                        <button onclick="toggleWishlist(<?php echo $product['id']; ?>)"
                                                class="btn btn-sm wishlist-btn"
                                                style="background: rgba(255,255,255,0.9); color: var(--primary-green); border: none; border-radius: 50%; width: 40px; height: 40px;"
                                                title="Add to Wishlist"
                                                data-product-id="<?php echo $product['id']; ?>">
                                            <i class="fas fa-heart"></i>
                                        </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <div class="product-info">
                                    <h6 class="product-title">
                                        <a href="product.php?id=<?php echo $product['id']; ?>" style="color: var(--black); text-decoration: none;">
                                            <?php echo htmlspecialchars($product['name']); ?>
                                        </a>
                                    </h6>
                                    
                                    <div class="product-price"><?php echo formatCurrency($product['price']); ?></div>
                                    
                                    <div class="product-rating">
                                        <div class="stars">
                                            <?php
                                            $rating = round($product['avg_rating'] ?? 0);
                                            for ($i = 1; $i <= 5; $i++) {
                                                echo $i <= $rating ? '★' : '☆';
                                            }
                                            ?>
                                        </div>
                                        <small class="text-muted">(<?php echo $product['review_count'] ?? 0; ?> reviews)</small>
                                    </div>
                                    
                                    <small class="text-muted">
                                        by <a href="vendor.php?id=<?php echo $product['vendor_id']; ?>" style="color: var(--primary-green);">
                                            <?php echo htmlspecialchars($product['store_name'] ?: $product['vendor_name']); ?>
                                        </a>
                                    </small>
                                    
                                    <div style="margin-top: 1rem; display: flex; gap: 0.5rem;">
                                        <a href="product.php?id=<?php echo $product['id']; ?>" class="btn btn-primary btn-sm" style="flex: 1;">
                                            View Details
                                        </a>
                                        <button onclick="addToCart(<?php echo $product['id']; ?>)" class="btn btn-outline btn-sm" style="flex: 1;">
                                            Add to Cart
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Pagination -->
                <?php if ($pagination['total_pages'] > 1): ?>
                    <div class="text-center mt-3">
                        <?php
                        $base_url = 'products.php?' . http_build_query(array_merge($_GET, ['page' => '']));
                        echo renderPagination($pagination, rtrim($base_url, '='));
                        ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.product-card {
    transition: var(--transition);
    height: 100%;
}

.product-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.15);
}

.product-image {
    width: 100%;
    height: 200px;
    object-fit: cover;
}

.stars {
    color: #FFD700;
    font-size: 0.9rem;
}

.pagination {
    display: flex;
    gap: 0.5rem;
    justify-content: center;
    list-style: none;
    margin: 0;
    padding: 0;
}

.pagination li {
    display: inline-block;
}

.wishlist-btn.active {
    color: #e91e63 !important;
}

@media (max-width: 768px) {
    .col-3, .col-9 {
        flex: 0 0 100%;
    }
    
    .col-4 {
        flex: 0 0 50%;
    }
}

@media (max-width: 480px) {
    .col-4 {
        flex: 0 0 100%;
    }
}
</style>

<script>
function addToCart(productId) {
    // Add to cart functionality
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
            // Update cart count
            updateCartCount();
        } else {
            showNotification(data.message || 'Error adding to cart', 'error');
        }
    })
    .catch(error => {
        showNotification('Error adding to cart', 'error');
    });
}

function addToCompare(productId) {
    fetch('api/compare.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            product_id: productId,
            action: 'toggle'
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification(data.message, 'success');

            // Update compare button state
            const compareBtn = document.querySelector(`[data-product-id="${productId}"].compare-btn`);
            if (compareBtn) {
                if (data.action === 'added') {
                    compareBtn.style.background = 'rgba(76, 175, 80, 0.9)';
                    compareBtn.style.color = 'white';
                } else {
                    compareBtn.style.background = 'rgba(255,255,255,0.9)';
                    compareBtn.style.color = 'var(--primary-green)';
                }
            }

            // Update compare count in header
            updateCompareCount(data.count);
        } else {
            showNotification(data.message || 'Error updating comparison', 'error');
        }
    })
    .catch(error => {
        showNotification('Error updating comparison', 'error');
    });
}

function updateCompareCount(count) {
    // Update compare count badge in header
    const compareLink = document.querySelector('a[href="compare.php"]');
    if (compareLink) {
        let badge = compareLink.querySelector('.badge');
        if (count > 0) {
            if (!badge) {
                badge = document.createElement('span');
                badge.className = 'badge';
                badge.style.cssText = 'background: var(--primary-green); color: white; border-radius: 50%; padding: 2px 6px; font-size: 0.8rem; margin-left: 0.5rem;';
                compareLink.appendChild(badge);
            }
            badge.textContent = count;
        } else if (badge) {
            badge.remove();
        }
    }
}

function toggleWishlist(productId) {
    fetch('api/wishlist.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'toggle',
            product_id: productId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification(data.message, 'success');

            // Update wishlist button state
            const wishlistBtn = document.querySelector(`[data-product-id="${productId}"].wishlist-btn`);
            if (wishlistBtn) {
                if (data.action === 'added') {
                    wishlistBtn.classList.add('active');
                    wishlistBtn.style.color = '#e91e63';
                } else {
                    wishlistBtn.classList.remove('active');
                    wishlistBtn.style.color = 'var(--primary-green)';
                }
            }

            // Update wishlist count if available
            updateWishlistCount();
        } else {
            showNotification(data.message || 'Error updating wishlist', 'error');
        }
    })
    .catch(error => {
        showNotification('Error updating wishlist', 'error');
    });
}

function updateWishlistCount() {
    fetch('api/wishlist.php?action=count')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Update wishlist count in header if there's a wishlist link
                const wishlistLink = document.querySelector('a[href*="wishlist"]');
                if (wishlistLink) {
                    let badge = wishlistLink.querySelector('.badge');
                    if (data.count > 0) {
                        if (!badge) {
                            badge = document.createElement('span');
                            badge.className = 'badge';
                            badge.style.cssText = 'background: #e91e63; color: white; border-radius: 50%; padding: 2px 6px; font-size: 0.8rem; margin-left: 0.5rem;';
                            wishlistLink.appendChild(badge);
                        }
                        badge.textContent = data.count;
                    } else if (badge) {
                        badge.remove();
                    }
                }
            }
        })
        .catch(error => console.error('Error updating wishlist count:', error));
}

function updateCartCount() {
    fetch('api/cart.php?action=count')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Update cart count in header if there's a cart link
                const cartLink = document.querySelector('a[href*="cart"]');
                if (cartLink) {
                    let badge = cartLink.querySelector('.badge');
                    if (data.total_items > 0) {
                        if (!badge) {
                            badge = document.createElement('span');
                            badge.className = 'badge';
                            badge.style.cssText = 'background: #007bff; color: white; border-radius: 50%; padding: 2px 6px; font-size: 0.8rem; margin-left: 0.5rem;';
                            cartLink.appendChild(badge);
                        }
                        badge.textContent = data.total_items;
                    } else if (badge) {
                        badge.remove();
                    }
                }
            }
        })
        .catch(error => console.error('Error updating cart count:', error));
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
