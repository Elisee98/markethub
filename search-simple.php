<?php
/**
 * Simple Fast Search
 * Lightweight search functionality that loads quickly
 */

require_once 'config/config.php';

$page_title = 'Search Products';

// Get search parameters
$search_query = trim($_GET['q'] ?? '');
$category_slug = trim($_GET['category'] ?? '');
$page = max(1, intval($_GET['page'] ?? 1));
$per_page = 12;
$offset = ($page - 1) * $per_page;

$results = [];
$total_results = 0;

// Only search if we have a query
if (!empty($search_query)) {
    try {
        // Simple, fast search query
        $search_term = "%{$search_query}%";
        
        // Count total results
        $count_sql = "
            SELECT COUNT(*) as total
            FROM products p
            INNER JOIN users u ON p.vendor_id = u.id
            WHERE (p.name LIKE ? OR p.description LIKE ? OR p.brand LIKE ?)
              AND p.status = 'active' 
              AND u.status = 'active' 
              AND u.user_type = 'vendor'
        ";
        
        $total_result = $database->fetch($count_sql, [$search_term, $search_term, $search_term]);
        $total_results = $total_result['total'];
        
        // Get search results
        $results_sql = "
            SELECT p.id, p.name, p.description, p.price, p.compare_price, p.image_url, p.brand, p.stock_quantity,
                   c.name as category_name, u.username as vendor_name, vs.store_name
            FROM products p
            INNER JOIN users u ON p.vendor_id = u.id
            LEFT JOIN categories c ON p.category_id = c.id
            LEFT JOIN vendor_stores vs ON u.id = vs.vendor_id
            WHERE (p.name LIKE ? OR p.description LIKE ? OR p.brand LIKE ?)
              AND p.status = 'active' 
              AND u.status = 'active' 
              AND u.user_type = 'vendor'
            ORDER BY p.name ASC
            LIMIT {$per_page} OFFSET {$offset}
        ";
        
        $results = $database->fetchAll($results_sql, [$search_term, $search_term, $search_term]);
        
    } catch (Exception $e) {
        $error_message = "Search error: " . $e->getMessage();
    }
}

// Calculate pagination
$total_pages = ceil($total_results / $per_page);

include 'includes/header.php';
?>

<div class="container">
    <div class="row">
        <div class="col-12">
            <!-- Search Header -->
            <div class="search-header" style="background: white; padding: 2rem; border-radius: 10px; margin-bottom: 2rem; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                <h1 style="margin-bottom: 1rem;">🔍 Search Products</h1>
                
                <!-- Search Form -->
                <form method="GET" action="search-simple.php" style="margin-bottom: 1rem;">
                    <div style="display: flex; gap: 1rem; align-items: center;">
                        <input type="text" name="q" class="form-control" 
                               placeholder="Search for products, brands, or descriptions..." 
                               value="<?php echo htmlspecialchars($search_query); ?>"
                               style="flex: 1; padding: 12px; border: 2px solid #e5e7eb; border-radius: 8px; font-size: 16px;">
                        <button type="submit" class="btn btn-primary" style="padding: 12px 24px; white-space: nowrap;">
                            <i class="fas fa-search"></i> Search
                        </button>
                    </div>
                </form>
                
                <?php if (!empty($search_query)): ?>
                    <div style="color: #6b7280; font-size: 0.9rem;">
                        Showing results for: <strong>"<?php echo htmlspecialchars($search_query); ?>"</strong>
                        <?php if ($total_results > 0): ?>
                            (<?php echo $total_results; ?> products found)
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>

            <?php if (isset($error_message)): ?>
                <div class="alert alert-danger">
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($search_query)): ?>
                <?php if (empty($results)): ?>
                    <!-- No Results -->
                    <div style="text-align: center; padding: 3rem; background: white; border-radius: 10px; margin-bottom: 2rem;">
                        <i class="fas fa-search" style="font-size: 3rem; color: #9ca3af; margin-bottom: 1rem;"></i>
                        <h3 style="color: #374151; margin-bottom: 1rem;">No products found</h3>
                        <p style="color: #6b7280; margin-bottom: 2rem;">
                            We couldn't find any products matching "<strong><?php echo htmlspecialchars($search_query); ?></strong>"
                        </p>
                        <div style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
                            <a href="products.php" class="btn btn-primary">Browse All Products</a>
                            <a href="products.php?category=smartphones" class="btn btn-outline">📱 Smartphones</a>
                            <a href="products.php?category=laptops" class="btn btn-outline">💻 Laptops</a>
                            <a href="products.php?category=fashion" class="btn btn-outline">👕 Fashion</a>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- Search Results -->
                    <div class="products-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 2rem; margin-bottom: 3rem;">
                        <?php foreach ($results as $product): ?>
                            <div class="product-card" style="background: white; border-radius: 10px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.1); transition: transform 0.2s;">
                                <!-- Product Image -->
                                <div style="position: relative; height: 250px; overflow: hidden;">
                                    <?php if ($product['image_url']): ?>
                                        <img src="<?php echo htmlspecialchars($product['image_url']); ?>" 
                                             alt="<?php echo htmlspecialchars($product['name']); ?>"
                                             style="width: 100%; height: 100%; object-fit: cover;">
                                    <?php else: ?>
                                        <div style="width: 100%; height: 100%; background: #f3f4f6; display: flex; align-items: center; justify-content: center;">
                                            <i class="fas fa-image" style="font-size: 3rem; color: #9ca3af;"></i>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <!-- Stock Badge -->
                                    <?php if ($product['stock_quantity'] <= 0): ?>
                                        <div style="position: absolute; top: 10px; right: 10px; background: #ef4444; color: white; padding: 4px 8px; border-radius: 4px; font-size: 12px;">
                                            Out of Stock
                                        </div>
                                    <?php elseif ($product['stock_quantity'] <= 5): ?>
                                        <div style="position: absolute; top: 10px; right: 10px; background: #f59e0b; color: white; padding: 4px 8px; border-radius: 4px; font-size: 12px;">
                                            Low Stock
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <!-- Product Info -->
                                <div style="padding: 1.5rem;">
                                    <h3 style="margin: 0 0 0.5rem 0; font-size: 1.1rem; color: #374151;">
                                        <a href="product.php?id=<?php echo $product['id']; ?>" style="text-decoration: none; color: inherit;">
                                            <?php echo htmlspecialchars($product['name']); ?>
                                        </a>
                                    </h3>
                                    
                                    <?php if ($product['brand']): ?>
                                        <p style="margin: 0 0 0.5rem 0; color: #6b7280; font-size: 0.9rem;">
                                            <strong>Brand:</strong> <?php echo htmlspecialchars($product['brand']); ?>
                                        </p>
                                    <?php endif; ?>
                                    
                                    <p style="margin: 0 0 0.5rem 0; color: #6b7280; font-size: 0.9rem;">
                                        <strong>Category:</strong> <?php echo htmlspecialchars($product['category_name'] ?: 'Uncategorized'); ?>
                                    </p>
                                    
                                    <p style="margin: 0 0 1rem 0; color: #6b7280; font-size: 0.9rem;">
                                        <strong>Vendor:</strong> <?php echo htmlspecialchars($product['store_name'] ?: $product['vendor_name']); ?>
                                    </p>
                                    
                                    <!-- Price -->
                                    <div style="margin-bottom: 1rem;">
                                        <span style="font-size: 1.25rem; font-weight: bold; color: #10b981;">
                                            RWF <?php echo number_format($product['price']); ?>
                                        </span>
                                        <?php if ($product['compare_price'] && $product['compare_price'] > $product['price']): ?>
                                            <span style="text-decoration: line-through; color: #9ca3af; margin-left: 0.5rem;">
                                                RWF <?php echo number_format($product['compare_price']); ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <!-- Actions -->
                                    <div style="display: flex; gap: 0.5rem;">
                                        <a href="product.php?id=<?php echo $product['id']; ?>" class="btn btn-primary" style="flex: 1; text-align: center;">
                                            View Details
                                        </a>
                                        <?php if ($product['stock_quantity'] > 0): ?>
                                            <button class="btn btn-outline" style="padding: 0.5rem;">
                                                <i class="fas fa-shopping-cart"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                        <div style="display: flex; justify-content: center; margin: 2rem 0;">
                            <div style="display: flex; gap: 0.5rem;">
                                <?php if ($page > 1): ?>
                                    <a href="?q=<?php echo urlencode($search_query); ?>&page=<?php echo $page - 1; ?>" 
                                       class="btn btn-outline">← Previous</a>
                                <?php endif; ?>
                                
                                <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                    <?php if ($i == $page): ?>
                                        <span class="btn btn-primary"><?php echo $i; ?></span>
                                    <?php else: ?>
                                        <a href="?q=<?php echo urlencode($search_query); ?>&page=<?php echo $i; ?>" 
                                           class="btn btn-outline"><?php echo $i; ?></a>
                                    <?php endif; ?>
                                <?php endfor; ?>
                                
                                <?php if ($page < $total_pages): ?>
                                    <a href="?q=<?php echo urlencode($search_query); ?>&page=<?php echo $page + 1; ?>" 
                                       class="btn btn-outline">Next →</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            <?php else: ?>
                <!-- Search Suggestions -->
                <div style="background: white; padding: 2rem; border-radius: 10px; margin-bottom: 2rem;">
                    <h3 style="margin-bottom: 1.5rem;">Popular Searches</h3>
                    <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
                        <a href="?q=iphone" class="btn btn-outline">📱 iPhone</a>
                        <a href="?q=macbook" class="btn btn-outline">💻 MacBook</a>
                        <a href="?q=nike" class="btn btn-outline">👟 Nike</a>
                        <a href="?q=jean" class="btn btn-outline">👕 Jeans</a>
                        <a href="?q=adidas" class="btn btn-outline">👟 Adidas</a>
                        <a href="?q=pixel" class="btn btn-outline">📱 Pixel</a>
                    </div>
                </div>
                
                <div style="background: white; padding: 2rem; border-radius: 10px;">
                    <h3 style="margin-bottom: 1.5rem;">Browse by Category</h3>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                        <a href="products.php?category=smartphones" class="btn btn-outline" style="padding: 1rem; text-align: center;">
                            📱 Smartphones
                        </a>
                        <a href="products.php?category=laptops" class="btn btn-outline" style="padding: 1rem; text-align: center;">
                            💻 Laptops
                        </a>
                        <a href="products.php?category=fashion" class="btn btn-outline" style="padding: 1rem; text-align: center;">
                            👕 Fashion
                        </a>
                        <a href="products.php?category=shoes" class="btn btn-outline" style="padding: 1rem; text-align: center;">
                            👟 Footwear
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.product-card:hover {
    transform: translateY(-2px);
}

.btn {
    padding: 0.5rem 1rem;
    border-radius: 6px;
    text-decoration: none;
    display: inline-block;
    font-weight: 500;
    transition: all 0.2s;
}

.btn-primary {
    background: #10b981;
    color: white;
    border: 2px solid #10b981;
}

.btn-primary:hover {
    background: #059669;
    border-color: #059669;
}

.btn-outline {
    background: transparent;
    color: #10b981;
    border: 2px solid #10b981;
}

.btn-outline:hover {
    background: #10b981;
    color: white;
}

.form-control {
    border: 2px solid #e5e7eb;
    border-radius: 6px;
    padding: 0.5rem;
}

.form-control:focus {
    outline: none;
    border-color: #10b981;
}
</style>

<?php include 'includes/footer.php'; ?>
