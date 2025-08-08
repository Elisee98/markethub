<?php
/**
 * MarketHub Vendor Comparison Page
 * Multi-Vendor E-Commerce Platform
 */

require_once 'config/config.php';

$page_title = 'Compare Vendors';

// Get category filter
$category_id = intval($_GET['category'] ?? 0);
$search_query = sanitizeInput($_GET['search'] ?? '');

// Get all categories for filter
$categories = $database->fetchAll(
    "SELECT * FROM categories WHERE status = 'active' ORDER BY name ASC"
);

// Build vendor query with filters
$vendor_sql = "
    SELECT vs.*, u.first_name, u.last_name, u.email as user_email,
           COUNT(DISTINCT p.id) as total_products,
           COUNT(DISTINCT CASE WHEN p.status = 'active' THEN p.id END) as active_products,
           AVG(p.price) as avg_price,
           MIN(p.price) as min_price,
           MAX(p.price) as max_price,
           COUNT(DISTINCT o.id) as total_orders,
           AVG(pr.rating) as avg_rating,
           COUNT(DISTINCT pr.id) as review_count
    FROM vendor_stores vs
    JOIN users u ON vs.vendor_id = u.id
    LEFT JOIN products p ON vs.vendor_id = p.vendor_id
    LEFT JOIN order_items oi ON p.id = oi.product_id
    LEFT JOIN orders o ON oi.order_id = o.id AND o.payment_status = 'paid'
    LEFT JOIN product_reviews pr ON p.id = pr.product_id AND pr.status = 'approved'
    WHERE u.user_type = 'vendor' AND u.status = 'active'
";

$params = [];

if ($category_id > 0) {
    $vendor_sql .= " AND p.category_id = ?";
    $params[] = $category_id;
}

if (!empty($search_query)) {
    $vendor_sql .= " AND (vs.store_name LIKE ? OR vs.description LIKE ? OR u.first_name LIKE ? OR u.last_name LIKE ?)";
    $search_param = "%$search_query%";
    $params = array_merge($params, [$search_param, $search_param, $search_param, $search_param]);
}

$vendor_sql .= "
    GROUP BY vs.vendor_id
    HAVING active_products > 0
    ORDER BY avg_rating DESC, total_orders DESC, active_products DESC
";

$vendors = $database->fetchAll($vendor_sql, $params);

// Get selected category info
$selected_category = null;
if ($category_id > 0) {
    $selected_category = $database->fetch(
        "SELECT * FROM categories WHERE id = ?", 
        [$category_id]
    );
}

require_once 'includes/header.php';
?>

<div class="container" style="margin: 2rem auto;">
    <!-- Header -->
    <div class="comparison-header">
        <h1>Compare Vendors</h1>
        <p>Find the best vendors in Musanze District by comparing their products, prices, and ratings</p>
    </div>

    <!-- Filters -->
    <div class="filters-section">
        <div class="card">
            <div class="card-body">
                <form method="GET" action="" class="filter-form">
                    <div class="filter-row">
                        <div class="filter-group">
                            <label for="category" class="form-label">Category</label>
                            <select name="category" id="category" class="form-control form-select">
                                <option value="">All Categories</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo $category['id']; ?>" 
                                            <?php echo $category_id == $category['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($category['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="filter-group">
                            <label for="search" class="form-label">Search Vendors</label>
                            <input type="text" name="search" id="search" class="form-control" 
                                   value="<?php echo htmlspecialchars($search_query); ?>" 
                                   placeholder="Search by vendor name or description">
                        </div>
                        
                        <div class="filter-group">
                            <label class="form-label">&nbsp;</label>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search"></i> Filter
                            </button>
                            <a href="vendor-comparison.php" class="btn btn-outline">
                                <i class="fas fa-times"></i> Clear
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Results Header -->
    <div class="results-header">
        <h3>
            <?php if ($selected_category): ?>
                Vendors in <?php echo htmlspecialchars($selected_category['name']); ?>
            <?php elseif (!empty($search_query)): ?>
                Search Results for "<?php echo htmlspecialchars($search_query); ?>"
            <?php else: ?>
                All Vendors
            <?php endif; ?>
        </h3>
        <p><?php echo count($vendors); ?> vendors found</p>
    </div>

    <!-- Vendor Comparison Grid -->
    <?php if (empty($vendors)): ?>
        <div class="no-results">
            <div class="text-center" style="padding: 4rem;">
                <i class="fas fa-store-slash" style="font-size: 4rem; color: var(--medium-gray); margin-bottom: 2rem;"></i>
                <h3>No Vendors Found</h3>
                <p style="color: var(--dark-gray); font-size: 1.1rem;">
                    <?php if ($category_id > 0): ?>
                        No vendors found in this category. Try selecting a different category.
                    <?php elseif (!empty($search_query)): ?>
                        No vendors match your search criteria. Try different keywords.
                    <?php else: ?>
                        No active vendors found. Please check back later.
                    <?php endif; ?>
                </p>
                <a href="vendor-comparison.php" class="btn btn-primary">View All Vendors</a>
            </div>
        </div>
    <?php else: ?>
        <div class="vendor-comparison-grid">
            <?php foreach ($vendors as $vendor): ?>
                <div class="vendor-card">
                    <!-- Vendor Header -->
                    <div class="vendor-header">
                        <div class="vendor-logo">
                            <?php if ($vendor['logo_url']): ?>
                                <img src="<?php echo htmlspecialchars($vendor['logo_url']); ?>" 
                                     alt="<?php echo htmlspecialchars($vendor['store_name']); ?>"
                                     onerror="this.style.display='none'">
                            <?php else: ?>
                                <div class="vendor-initials">
                                    <?php echo strtoupper(substr($vendor['store_name'], 0, 2)); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="vendor-info">
                            <h4><?php echo htmlspecialchars($vendor['store_name']); ?></h4>
                            <p class="vendor-owner">by <?php echo htmlspecialchars($vendor['first_name'] . ' ' . $vendor['last_name']); ?></p>
                            <?php if ($vendor['avg_rating'] > 0): ?>
                                <div class="vendor-rating">
                                    <div class="stars">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <?php if ($i <= $vendor['avg_rating']): ?>
                                                <i class="fas fa-star"></i>
                                            <?php elseif ($i - 0.5 <= $vendor['avg_rating']): ?>
                                                <i class="fas fa-star-half-alt"></i>
                                            <?php else: ?>
                                                <i class="far fa-star"></i>
                                            <?php endif; ?>
                                        <?php endfor; ?>
                                    </div>
                                    <span class="rating-text">
                                        <?php echo number_format($vendor['avg_rating'], 1); ?> 
                                        (<?php echo $vendor['review_count']; ?> reviews)
                                    </span>
                                </div>
                            <?php else: ?>
                                <div class="vendor-rating">
                                    <span class="rating-text">No reviews yet</span>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Vendor Stats -->
                    <div class="vendor-stats">
                        <div class="stat-item">
                            <div class="stat-icon">
                                <i class="fas fa-box"></i>
                            </div>
                            <div class="stat-content">
                                <span class="stat-number"><?php echo number_format($vendor['active_products']); ?></span>
                                <span class="stat-label">Products</span>
                            </div>
                        </div>
                        
                        <div class="stat-item">
                            <div class="stat-icon">
                                <i class="fas fa-shopping-cart"></i>
                            </div>
                            <div class="stat-content">
                                <span class="stat-number"><?php echo number_format($vendor['total_orders'] ?: 0); ?></span>
                                <span class="stat-label">Orders</span>
                            </div>
                        </div>
                        
                        <div class="stat-item">
                            <div class="stat-icon">
                                <i class="fas fa-tag"></i>
                            </div>
                            <div class="stat-content">
                                <span class="stat-number"><?php echo formatCurrency($vendor['min_price'] ?: 0); ?></span>
                                <span class="stat-label">From</span>
                            </div>
                        </div>
                    </div>

                    <!-- Vendor Description -->
                    <?php if ($vendor['description']): ?>
                        <div class="vendor-description">
                            <p><?php echo htmlspecialchars(substr($vendor['description'], 0, 120)); ?>
                               <?php echo strlen($vendor['description']) > 120 ? '...' : ''; ?></p>
                        </div>
                    <?php endif; ?>

                    <!-- Vendor Features -->
                    <div class="vendor-features">
                        <?php if ($vendor['delivery_fee'] !== null): ?>
                            <div class="feature">
                                <i class="fas fa-truck"></i>
                                <span>Delivery: <?php echo $vendor['delivery_fee'] > 0 ? formatCurrency($vendor['delivery_fee']) : 'Free'; ?></span>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($vendor['minimum_order'] !== null): ?>
                            <div class="feature">
                                <i class="fas fa-shopping-basket"></i>
                                <span>Min Order: <?php echo formatCurrency($vendor['minimum_order']); ?></span>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($vendor['phone']): ?>
                            <div class="feature">
                                <i class="fas fa-phone"></i>
                                <span><?php echo htmlspecialchars($vendor['phone']); ?></span>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Price Range -->
                    <div class="price-range">
                        <h5>Price Range</h5>
                        <div class="price-info">
                            <span class="price-from"><?php echo formatCurrency($vendor['min_price'] ?: 0); ?></span>
                            <span class="price-separator">-</span>
                            <span class="price-to"><?php echo formatCurrency($vendor['max_price'] ?: 0); ?></span>
                        </div>
                        <?php if ($vendor['avg_price']): ?>
                            <small class="avg-price">Avg: <?php echo formatCurrency($vendor['avg_price']); ?></small>
                        <?php endif; ?>
                    </div>

                    <!-- Actions -->
                    <div class="vendor-actions">
                        <a href="vendor.php?id=<?php echo $vendor['vendor_id']; ?>" class="btn btn-primary">
                            <i class="fas fa-store"></i> Visit Store
                        </a>
                        <a href="products.php?vendor=<?php echo $vendor['vendor_id']; ?><?php echo $category_id ? '&category=' . $category_id : ''; ?>" 
                           class="btn btn-outline">
                            <i class="fas fa-eye"></i> View Products
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- Comparison Tips -->
    <div class="comparison-tips">
        <div class="card">
            <div class="card-header">
                <h4><i class="fas fa-lightbulb"></i> Comparison Tips</h4>
            </div>
            <div class="card-body">
                <div class="tips-grid">
                    <div class="tip">
                        <h5>Check Ratings</h5>
                        <p>Look for vendors with high ratings and positive reviews from other customers.</p>
                    </div>
                    <div class="tip">
                        <h5>Compare Prices</h5>
                        <p>Compare price ranges and average prices to find the best value for your budget.</p>
                    </div>
                    <div class="tip">
                        <h5>Delivery Options</h5>
                        <p>Consider delivery fees and minimum order requirements when making your choice.</p>
                    </div>
                    <div class="tip">
                        <h5>Product Variety</h5>
                        <p>Vendors with more products may offer better selection and one-stop shopping.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.comparison-header {
    text-align: center;
    margin-bottom: 2rem;
    padding: 2rem;
    background: linear-gradient(135deg, #2e5b09ff, #0c57a3ff);
    border-radius: var(--border-radius);
}

.filters-section {
    margin-bottom: 2rem;
}

.filter-form {
    display: flex;
    align-items: end;
    gap: 1rem;
    flex-wrap: wrap;
}

.filter-row {
    display: flex;
    gap: 1rem;
    width: 100%;
    align-items: end;
}

.filter-group {
    flex: 1;
    min-width: 200px;
}

.results-header {
    margin-bottom: 2rem;
    padding-bottom: 1rem;
    border-bottom: 2px solid var(--light-gray);
}

.vendor-comparison-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
    gap: 2rem;
    margin-bottom: 3rem;
}

.vendor-card {
    background: white;
    border-radius: var(--border-radius);
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    padding: 2rem;
    transition: var(--transition);
}

.vendor-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.15);
}

.vendor-header {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.vendor-logo {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    overflow: hidden;
    background: var(--light-gray);
    display: flex;
    align-items: center;
    justify-content: center;
}

.vendor-logo img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.vendor-initials {
    font-size: 1.2rem;
    font-weight: bold;
    color: var(--primary-green);
}

.vendor-info h4 {
    margin-bottom: 0.25rem;
    color: var(--black);
}

.vendor-owner {
    color: var(--dark-gray);
    font-size: 0.9rem;
    margin-bottom: 0.5rem;
}

.vendor-rating {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.stars {
    color: #FFB74D;
}

.rating-text {
    font-size: 0.9rem;
    color: var(--dark-gray);
}

.vendor-stats {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 1rem;
    margin-bottom: 1.5rem;
    padding: 1rem;
    background: #f8f9fa;
    border-radius: var(--border-radius);
}

.stat-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.stat-icon {
    width: 30px;
    height: 30px;
    background: var(--primary-green);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 0.8rem;
}

.stat-content {
    display: flex;
    flex-direction: column;
}

.stat-number {
    font-weight: bold;
    color: var(--black);
    font-size: 0.9rem;
}

.stat-label {
    font-size: 0.7rem;
    color: var(--dark-gray);
}

.vendor-description {
    margin-bottom: 1.5rem;
    color: var(--dark-gray);
    line-height: 1.5;
}

.vendor-features {
    margin-bottom: 1.5rem;
}

.feature {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-bottom: 0.5rem;
    font-size: 0.9rem;
    color: var(--dark-gray);
}

.feature i {
    color: var(--primary-green);
    width: 16px;
}

.price-range {
    margin-bottom: 1.5rem;
    padding: 1rem;
    background: linear-gradient(135deg, var(--primary-green), var(--secondary-green));
    color: white;
    border-radius: var(--border-radius);
    text-align: center;
}

.price-range h5 {
    color: white;
    margin-bottom: 0.5rem;
}

.price-info {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    margin-bottom: 0.25rem;
}

.price-from, .price-to {
    font-weight: bold;
    font-size: 1.1rem;
}

.price-separator {
    opacity: 0.7;
}

.avg-price {
    opacity: 0.8;
    font-size: 0.8rem;
}

.vendor-actions {
    display: flex;
    gap: 0.5rem;
}

.vendor-actions .btn {
    flex: 1;
    text-align: center;
}

.comparison-tips {
    margin-top: 3rem;
}

.tips-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 2rem;
}

.tip h5 {
    color: var(--primary-green);
    margin-bottom: 0.5rem;
}

.tip p {
    color: var(--dark-gray);
    font-size: 0.9rem;
    line-height: 1.5;
}

@media (max-width: 768px) {
    .filter-row {
        flex-direction: column;
    }
    
    .filter-group {
        min-width: 100%;
    }
    
    .vendor-comparison-grid {
        grid-template-columns: 1fr;
    }
    
    .vendor-stats {
        grid-template-columns: 1fr;
    }
    
    .vendor-actions {
        flex-direction: column;
    }
    
    .tips-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
// Auto-submit form when category changes
document.getElementById('category').addEventListener('change', function() {
    this.form.submit();
});

// Search functionality
document.getElementById('search').addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
        this.form.submit();
    }
});
</script>

<?php require_once 'includes/footer.php'; ?>
