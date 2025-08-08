<?php
/**
 * MarketHub Product Management
 * Admin panel for managing all products
 */

require_once '../config/config.php';
require_once '../includes/image-helper.php';

$page_title = 'Product Management';

// Check if user is admin
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    redirect('../login.php?error=access_denied');
}

// Get filter parameters
$status_filter = sanitizeInput($_GET['status'] ?? '');
$category_filter = intval($_GET['category'] ?? 0);
$vendor_filter = intval($_GET['vendor'] ?? 0);

// Build query
$where_conditions = [];
$params = [];

if ($status_filter) {
    $where_conditions[] = "p.status = ?";
    $params[] = $status_filter;
}

if ($category_filter > 0) {
    $where_conditions[] = "p.category_id = ?";
    $params[] = $category_filter;
}

if ($vendor_filter > 0) {
    $where_conditions[] = "p.vendor_id = ?";
    $params[] = $vendor_filter;
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Get products
$products = $database->fetchAll(
    "SELECT p.*, c.name as category_name, u.first_name, u.last_name, vs.store_name,
            pi.image_url,
            COUNT(DISTINCT oi.id) as total_sales,
            AVG(pr.rating) as avg_rating,
            COUNT(DISTINCT pr.id) as review_count
     FROM products p
     LEFT JOIN categories c ON p.category_id = c.id
     LEFT JOIN users u ON p.vendor_id = u.id
     LEFT JOIN vendor_stores vs ON u.id = vs.vendor_id
     LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
     LEFT JOIN order_items oi ON p.id = oi.product_id
     LEFT JOIN product_reviews pr ON p.id = pr.product_id AND pr.status = 'approved'
     $where_clause
     GROUP BY p.id
     ORDER BY p.created_at DESC",
    $params
);

// Get categories for filter
$categories = $database->fetchAll("SELECT * FROM categories WHERE status = 'active' ORDER BY name");

// Get vendors for filter
$vendors = $database->fetchAll(
    "SELECT u.id, u.first_name, u.last_name, vs.store_name 
     FROM users u 
     LEFT JOIN vendor_stores vs ON u.id = vs.vendor_id 
     WHERE u.user_type = 'vendor' AND u.status = 'active' 
     ORDER BY vs.store_name, u.first_name"
);

require_once 'includes/admin_header_new.php';
?>

<div class="content-header">
    <h1><i class="fas fa-box"></i> Product Management</h1>
    <p>Manage all products across the platform</p>
</div>

<!-- Statistics -->
<div class="stats-grid">
    <?php
    $product_stats = [
        'total' => count($products),
        'active' => count(array_filter($products, fn($p) => $p['status'] === 'active')),
        'pending' => count(array_filter($products, fn($p) => $p['status'] === 'pending')),
        'out_of_stock' => count(array_filter($products, fn($p) => $p['stock_quantity'] <= 0))
    ];
    ?>
    
    <div class="stat-card">
        <div class="stat-icon" style="background: #2196F3;">
            <i class="fas fa-box"></i>
        </div>
        <div class="stat-content">
            <h3><?php echo $product_stats['total']; ?></h3>
            <p>Total Products</p>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon" style="background: #4CAF50;">
            <i class="fas fa-check-circle"></i>
        </div>
        <div class="stat-content">
            <h3><?php echo $product_stats['active']; ?></h3>
            <p>Active Products</p>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon" style="background: #FF9800;">
            <i class="fas fa-clock"></i>
        </div>
        <div class="stat-content">
            <h3><?php echo $product_stats['pending']; ?></h3>
            <p>Pending Approval</p>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon" style="background: #f44336;">
            <i class="fas fa-exclamation-triangle"></i>
        </div>
        <div class="stat-content">
            <h3><?php echo $product_stats['out_of_stock']; ?></h3>
            <p>Out of Stock</p>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="dashboard-widget">
    <div class="widget-header">
        <h4><i class="fas fa-filter"></i> Filters</h4>
    </div>
    <div class="widget-content">
        <form method="GET" class="filter-form">
            <div class="filter-row">
                <div class="filter-group">
                    <label for="status">Status</label>
                    <select name="status" id="status" class="form-control">
                        <option value="">All Statuses</option>
                        <option value="active" <?php echo $status_filter === 'active' ? 'selected' : ''; ?>>Active</option>
                        <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="inactive" <?php echo $status_filter === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label for="category">Category</label>
                    <select name="category" id="category" class="form-control">
                        <option value="">All Categories</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?php echo $category['id']; ?>" 
                                    <?php echo $category_filter == $category['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($category['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label for="vendor">Vendor</label>
                    <select name="vendor" id="vendor" class="form-control">
                        <option value="">All Vendors</option>
                        <?php foreach ($vendors as $vendor): ?>
                            <option value="<?php echo $vendor['id']; ?>" 
                                    <?php echo $vendor_filter == $vendor['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($vendor['store_name'] ?: $vendor['first_name'] . ' ' . $vendor['last_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="filter-group">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i> Filter
                    </button>
                    <a href="products.php" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Clear
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Products List -->
<div class="dashboard-widget">
    <div class="widget-header">
        <h4><i class="fas fa-list"></i> Products (<?php echo count($products); ?>)</h4>
        <div class="widget-actions">
            <a href="product-add.php" class="btn btn-primary">
                <i class="fas fa-plus"></i> Add Product
            </a>
        </div>
    </div>
    <div class="widget-content">
        <?php if (empty($products)): ?>
            <div class="no-data">
                <i class="fas fa-box" style="font-size: 3rem; color: #ccc; margin-bottom: 1rem;"></i>
                <h3>No Products Found</h3>
                <p>No products match your current filters.</p>
                <a href="products.php" class="btn btn-secondary">Clear Filters</a>
            </div>
        <?php else: ?>
            <div class="products-grid">
                <?php foreach ($products as $product): ?>
                    <div class="product-card">
                        <div class="product-image">
                            <?php
                            // For admin directory, we need to adjust the path
                            $admin_image_url = $product['image_url'];
                            if ($admin_image_url && !str_starts_with($admin_image_url, '../')) {
                                $admin_image_url = '../' . $admin_image_url;
                            }

                            if ($admin_image_url && file_exists($admin_image_url)) {
                                echo '<img src="' . htmlspecialchars($admin_image_url) . '" alt="' . htmlspecialchars($product['name']) . '" style="width: 100%; height: 100%; object-fit: cover;">';
                            } else {
                                echo '<div class="no-image"><i class="fas fa-image"></i></div>';
                            }
                            ?>
                            <div class="product-status">
                                <span class="status-badge status-<?php echo $product['status']; ?>">
                                    <?php echo ucfirst($product['status']); ?>
                                </span>
                            </div>
                        </div>
                        
                        <div class="product-info">
                            <h5><?php echo htmlspecialchars($product['name']); ?></h5>
                            <p class="product-category"><?php echo htmlspecialchars($product['category_name']); ?></p>
                            <p class="product-vendor">
                                by <?php echo htmlspecialchars($product['store_name'] ?: $product['first_name'] . ' ' . $product['last_name']); ?>
                            </p>
                            
                            <div class="product-price">
                                <span class="current-price"><?php echo formatCurrency($product['price']); ?></span>
                                <?php if ($product['original_price'] && $product['original_price'] > $product['price']): ?>
                                    <span class="original-price"><?php echo formatCurrency($product['original_price']); ?></span>
                                <?php endif; ?>
                            </div>
                            
                            <div class="product-stats">
                                <div class="stat-item">
                                    <span class="stat-label">Stock:</span>
                                    <span class="stat-value <?php echo $product['stock_quantity'] <= 0 ? 'text-danger' : ''; ?>">
                                        <?php echo $product['stock_quantity']; ?>
                                    </span>
                                </div>
                                <div class="stat-item">
                                    <span class="stat-label">Sales:</span>
                                    <span class="stat-value"><?php echo $product['total_sales']; ?></span>
                                </div>
                                <div class="stat-item">
                                    <span class="stat-label">Rating:</span>
                                    <span class="stat-value">
                                        <?php echo $product['avg_rating'] ? number_format($product['avg_rating'], 1) : '0.0'; ?>
                                        (<?php echo $product['review_count']; ?>)
                                    </span>
                                </div>
                            </div>
                            
                            <div class="product-actions">
                                <a href="product-details.php?id=<?php echo $product['id']; ?>" 
                                   class="btn btn-sm btn-primary">
                                    <i class="fas fa-eye"></i> View
                                </a>
                                <a href="product-edit.php?id=<?php echo $product['id']; ?>" 
                                   class="btn btn-sm btn-secondary">
                                    <i class="fas fa-edit"></i> Edit
                                </a>
                                <a href="../product.php?id=<?php echo $product['id']; ?>" 
                                   class="btn btn-sm btn-success" target="_blank">
                                    <i class="fas fa-external-link-alt"></i> View Live
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
.filter-form {
    background: #f8f9fa;
    padding: 1rem;
    border-radius: 6px;
}

.filter-row {
    display: flex;
    gap: 1rem;
    align-items: end;
    flex-wrap: wrap;
}

.filter-group {
    flex: 1;
    min-width: 150px;
}

.filter-group label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 600;
    color: var(--admin-dark);
}

.form-control {
    width: 100%;
    padding: 0.5rem;
    border: 1px solid #ddd;
    border-radius: 4px;
}

.products-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 1.5rem;
}

.product-card {
    background: white;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    overflow: hidden;
    transition: box-shadow 0.3s;
}

.product-card:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.product-image {
    position: relative;
    height: 200px;
    background: #f8f9fa;
    display: flex;
    align-items: center;
    justify-content: center;
}

.product-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.no-image {
    color: #ccc;
    font-size: 3rem;
}

.product-status {
    position: absolute;
    top: 0.5rem;
    right: 0.5rem;
}

.product-info {
    padding: 1rem;
}

.product-info h5 {
    margin: 0 0 0.5rem 0;
    color: var(--admin-dark);
    font-size: 1.1rem;
}

.product-category,
.product-vendor {
    margin: 0 0 0.5rem 0;
    color: #666;
    font-size: 0.9rem;
}

.product-price {
    margin-bottom: 1rem;
}

.current-price {
    font-size: 1.25rem;
    font-weight: bold;
    color: var(--admin-success);
}

.original-price {
    margin-left: 0.5rem;
    text-decoration: line-through;
    color: #999;
    font-size: 0.9rem;
}

.product-stats {
    margin-bottom: 1rem;
    font-size: 0.85rem;
}

.stat-item {
    display: flex;
    justify-content: space-between;
    margin-bottom: 0.25rem;
}

.stat-label {
    color: #666;
}

.stat-value {
    font-weight: 600;
    color: var(--admin-dark);
}

.text-danger {
    color: var(--admin-error) !important;
}

.product-actions {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
}

.btn-sm {
    padding: 0.5rem 0.75rem;
    font-size: 0.8rem;
}

.btn-success {
    background: var(--admin-success);
    color: white;
}

.widget-actions {
    display: flex;
    gap: 0.5rem;
}

.btn {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1rem;
    border: none;
    border-radius: 6px;
    text-decoration: none;
    font-weight: 500;
    transition: all 0.3s;
    cursor: pointer;
}

.btn-primary {
    background: var(--admin-primary);
    color: white;
}

.btn-secondary {
    background: #6c757d;
    color: white;
}

.btn:hover {
    opacity: 0.9;
    transform: translateY(-1px);
}

@media (max-width: 768px) {
    .filter-row {
        flex-direction: column;
    }
    
    .filter-group {
        min-width: 100%;
    }
    
    .products-grid {
        grid-template-columns: 1fr;
    }
    
    .product-actions {
        flex-direction: column;
    }
}
</style>

<?php require_once 'includes/admin_footer_new.php'; ?>
