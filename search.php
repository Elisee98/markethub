<?php
/**
 * MarketHub Advanced Product Search
 * Multi-Vendor E-Commerce Platform
 */

require_once 'config/config.php';

$page_title = 'Search Products';

// Get search parameters
$search_query = $_GET['q'] ?? '';
$category_id = $_GET['category'] ?? '';
$min_price = $_GET['min_price'] ?? '';
$max_price = $_GET['max_price'] ?? '';
$vendor_id = $_GET['vendor'] ?? '';
$rating_filter = $_GET['rating'] ?? '';
$sort_by = $_GET['sort'] ?? 'relevance';
$page = max(1, intval($_GET['page'] ?? 1));

// Advanced filters
$in_stock_only = isset($_GET['in_stock']);
$featured_only = isset($_GET['featured']);
$has_reviews = isset($_GET['has_reviews']);

$results = [];
$total_results = 0;
$search_performed = !empty($search_query) || !empty($category_id) || !empty($vendor_id);

if ($search_performed) {
    // Build WHERE clause - simplified for speed
    $where_conditions = ["p.status = 'active'"];
    $params = [];

    if ($search_query) {
        $where_conditions[] = "(p.name LIKE ? OR p.description LIKE ? OR p.brand LIKE ?)";
        $search_term = "%$search_query%";
        $params[] = $search_term;
        $params[] = $search_term;
        $params[] = $search_term;
    }

    if ($category_id) {
        $where_conditions[] = "p.category_id = ?";
        $params[] = $category_id;
    }

    if ($vendor_id) {
        $where_conditions[] = "p.vendor_id = ?";
        $params[] = $vendor_id;
    }

    if ($min_price) {
        $where_conditions[] = "p.price >= ?";
        $params[] = $min_price;
    }

    if ($max_price) {
        $where_conditions[] = "p.price <= ?";
        $params[] = $max_price;
    }

    if ($in_stock_only) {
        $where_conditions[] = "p.stock_quantity > 0";
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
            $order_by = "avg_rating DESC, review_count DESC";
            break;
        case 'popular':
            $order_by = "review_count DESC, avg_rating DESC";
            break;
        case 'name':
            $order_by = "p.name ASC";
            break;
        case 'newest':
            $order_by = "p.created_at DESC";
            break;
        case 'relevance':
        default:
            if ($search_query) {
                $order_by = "
                    CASE 
                        WHEN p.name LIKE '%$search_query%' THEN 1
                        WHEN p.short_description LIKE '%$search_query%' THEN 2
                        WHEN p.description LIKE '%$search_query%' THEN 3
                        ELSE 4
                    END,
                    avg_rating DESC,
                    review_count DESC
                ";
            }
            break;
    }

    // Get total count
    $count_sql = "
        SELECT COUNT(DISTINCT p.id) as total
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        INNER JOIN users u ON p.vendor_id = u.id
        LEFT JOIN vendor_stores vs ON u.id = vs.vendor_id
        WHERE $where_clause AND u.status = 'active' AND u.user_type = 'vendor'
    ";

    $total_results = $database->fetch($count_sql, $params)['total'];
    $pagination = getPagination($total_results, PRODUCTS_PER_PAGE, $page);

    // Get search results
    $results_sql = "
        SELECT p.*, p.image_url, c.name as category_name,
               u.username as vendor_name, vs.store_name,
               0 as avg_rating, 0 as review_count
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        INNER JOIN users u ON p.vendor_id = u.id
        LEFT JOIN vendor_stores vs ON u.id = vs.vendor_id
        WHERE $where_clause AND u.status = 'active' AND u.user_type = 'vendor'
        ORDER BY $order_by
        LIMIT {$pagination['records_per_page']} OFFSET {$pagination['offset']}
    ";

    $results = $database->fetchAll($results_sql, $params);
}

// Get filter options
$categories = $database->fetchAll("SELECT * FROM categories WHERE status = 'active' ORDER BY name");
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
    <!-- Search Header -->
    <div class="row align-items-center mb-3">
        <div class="col-8">
            <h1>Search Products</h1>
            <?php if ($search_performed): ?>
                <p class="text-muted">
                    <?php if ($search_query): ?>
                        Search results for "<?php echo htmlspecialchars($search_query); ?>" 
                    <?php else: ?>
                        Filtered results 
                    <?php endif; ?>
                    (<?php echo $total_results; ?> products found)
                </p>
            <?php else: ?>
                <p class="text-muted">Use the filters below to find the perfect products</p>
            <?php endif; ?>
        </div>
        <div class="col-4 text-right">
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
        <!-- Advanced Filters Sidebar -->
        <div class="col-3">
            <div class="card">
                <div class="card-header">
                    <h5>Advanced Search</h5>
                </div>
                <div class="card-body">
                    <form method="GET" action="">
                        <!-- Search Query -->
                        <div class="form-group">
                            <label class="form-label">Search Keywords</label>
                            <input type="text" name="q" class="form-control" 
                                   placeholder="Product name, description, SKU..." 
                                   value="<?php echo htmlspecialchars($search_query); ?>">
                        </div>
                        
                        <!-- Category Filter -->
                        <div class="form-group">
                            <label class="form-label">Category</label>
                            <select name="category" class="form-control form-select">
                                <option value="">All Categories</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo $category['id']; ?>" 
                                            <?php echo $category_id == $category['id'] ? 'selected' : ''; ?>>
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
                        
                        <!-- Rating Filter -->
                        <div class="form-group">
                            <label class="form-label">Minimum Rating</label>
                            <select name="rating" class="form-control form-select">
                                <option value="">Any Rating</option>
                                <option value="4" <?php echo $rating_filter == '4' ? 'selected' : ''; ?>>4+ Stars</option>
                                <option value="3" <?php echo $rating_filter == '3' ? 'selected' : ''; ?>>3+ Stars</option>
                                <option value="2" <?php echo $rating_filter == '2' ? 'selected' : ''; ?>>2+ Stars</option>
                                <option value="1" <?php echo $rating_filter == '1' ? 'selected' : ''; ?>>1+ Stars</option>
                            </select>
                        </div>
                        
                        <!-- Advanced Options -->
                        <div class="form-group">
                            <label class="form-label">Additional Filters</label>
                            <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                                <label style="display: flex; align-items: center; cursor: pointer;">
                                    <input type="checkbox" name="in_stock" <?php echo $in_stock_only ? 'checked' : ''; ?> style="margin-right: 0.5rem;">
                                    In Stock Only
                                </label>
                                <label style="display: flex; align-items: center; cursor: pointer;">
                                    <input type="checkbox" name="featured" <?php echo $featured_only ? 'checked' : ''; ?> style="margin-right: 0.5rem;">
                                    Featured Products
                                </label>
                                <label style="display: flex; align-items: center; cursor: pointer;">
                                    <input type="checkbox" name="has_reviews" <?php echo $has_reviews ? 'checked' : ''; ?> style="margin-right: 0.5rem;">
                                    Has Reviews
                                </label>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary" style="width: 100%; margin-bottom: 0.5rem;">Search Products</button>
                        <a href="search.php" class="btn btn-outline" style="width: 100%;">Clear Filters</a>
                    </form>
                </div>
            </div>
            
            <!-- Quick Search Suggestions -->
            <div class="card mt-3">
                <div class="card-header">
                    <h6>Popular Searches</h6>
                </div>
                <div class="card-body">
                    <div style="display: flex; flex-wrap: wrap; gap: 0.5rem;">
                        <a href="?q=electronics" class="btn btn-outline btn-sm">Electronics</a>
                        <a href="?q=clothing" class="btn btn-outline btn-sm">Clothing</a>
                        <a href="?q=books" class="btn btn-outline btn-sm">Books</a>
                        <a href="?q=home" class="btn btn-outline btn-sm">Home & Garden</a>
                        <a href="?featured=1" class="btn btn-outline btn-sm">Featured</a>
                        <a href="?rating=4" class="btn btn-outline btn-sm">Top Rated</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Search Results -->
        <div class="col-9">
            <?php if ($search_performed): ?>
                <!-- Sort Options -->
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <strong><?php echo $total_results; ?></strong> products found
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
                                <option value="relevance" <?php echo $sort_by === 'relevance' ? 'selected' : ''; ?>>Most Relevant</option>
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

                <!-- Results Grid -->
                <?php if (empty($results)): ?>
                    <div class="text-center" style="padding: 4rem 0;">
                        <i class="fas fa-search" style="font-size: 4rem; color: var(--medium-gray); margin-bottom: 1rem;"></i>
                        <h3>No products found</h3>
                        <p class="text-muted">Try adjusting your search criteria or browse all products.</p>
                        <a href="products.php" class="btn btn-primary">Browse All Products</a>
                    </div>
                <?php else: ?>
                    <div class="row">
                        <?php foreach ($results as $product): ?>
                            <div class="col-4 mb-3">
                                <div class="card product-card">
                                    <div style="position: relative;">
                                        <img src="<?php echo $product['image_url'] ?: 'assets/images/product-placeholder.png'; ?>" 
                                             alt="<?php echo htmlspecialchars($product['name']); ?>" 
                                             class="product-image"
                                             onerror="this.src='assets/images/product-placeholder.png'">
                                        
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
                                        
                                        <!-- Product Badges -->
                                        <div style="position: absolute; top: 10px; left: 10px; display: flex; flex-direction: column; gap: 0.5rem;">
                                            <?php if ($product['featured']): ?>
                                                <span style="background: var(--secondary-green); color: white; padding: 0.25rem 0.5rem; border-radius: 12px; font-size: 0.8rem;">Featured</span>
                                            <?php endif; ?>
                                            <?php if ($product['stock_quantity'] <= 0): ?>
                                                <span style="background: #F44336; color: white; padding: 0.25rem 0.5rem; border-radius: 12px; font-size: 0.8rem;">Out of Stock</span>
                                            <?php elseif ($product['stock_quantity'] <= 5): ?>
                                                <span style="background: #FF9800; color: white; padding: 0.25rem 0.5rem; border-radius: 12px; font-size: 0.8rem;">Low Stock</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    
                                    <div class="product-info">
                                        <h6 class="product-title">
                                            <a href="product.php?id=<?php echo $product['id']; ?>" style="color: var(--black); text-decoration: none;">
                                                <?php echo htmlspecialchars($product['name']); ?>
                                            </a>
                                        </h6>
                                        
                                        <div class="product-price">
                                            <?php echo formatCurrency($product['price']); ?>
                                            <?php if ($product['compare_price'] > $product['price']): ?>
                                                <small style="text-decoration: line-through; color: var(--dark-gray); margin-left: 0.5rem;">
                                                    <?php echo formatCurrency($product['compare_price']); ?>
                                                </small>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <div class="product-rating">
                                            <div class="stars">
                                                <?php 
                                                $rating = round($product['avg_rating']);
                                                for ($i = 1; $i <= 5; $i++) {
                                                    echo $i <= $rating ? '★' : '☆';
                                                }
                                                ?>
                                            </div>
                                            <small class="text-muted">(<?php echo $product['review_count']; ?> reviews)</small>
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
                            $base_url = 'search.php?' . http_build_query(array_merge($_GET, ['page' => '']));
                            echo renderPagination($pagination, rtrim($base_url, '='));
                            ?>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            <?php else: ?>
                <!-- Search Suggestions -->
                <div class="text-center" style="padding: 4rem 0;">
                    <i class="fas fa-search" style="font-size: 4rem; color: var(--medium-gray); margin-bottom: 2rem;"></i>
                    <h3>Find Your Perfect Product</h3>
                    <p style="color: var(--dark-gray); font-size: 1.1rem; margin-bottom: 2rem;">
                        Use our advanced search filters to find exactly what you're looking for from our network of trusted vendors.
                    </p>
                    
                    <div style="display: flex; justify-content: center; gap: 1rem; margin-bottom: 3rem;">
                        <a href="products.php" class="btn btn-primary btn-lg">
                            <i class="fas fa-th-large"></i> Browse All Products
                        </a>
                        <a href="categories.php" class="btn btn-outline btn-lg">
                            <i class="fas fa-list"></i> Browse Categories
                        </a>
                    </div>
                </div>
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
// Include the same JavaScript functions from products.php
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
            
            // Update comparison widget
            if (typeof updateComparisonWidget === 'function') {
                updateComparisonWidget();
            }
        } else {
            showNotification(data.message || 'Error updating comparison', 'error');
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
