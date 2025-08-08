<?php
/**
 * MarketHub Product Comparison
 * Multi-Vendor E-Commerce Platform
 */

require_once 'config/config.php';

$page_title = 'Compare Products';

// Initialize comparison session if not exists
if (!isset($_SESSION['compare_items'])) {
    $_SESSION['compare_items'] = [];
}

$compare_products = [];
$comparison_data = [];

// Get comparison products if any
if (!empty($_SESSION['compare_items'])) {
    $placeholders = str_repeat('?,', count($_SESSION['compare_items']) - 1) . '?';
    
    $products_sql = "
        SELECT p.*, pi.image_url, c.name as category_name,
               u.username as vendor_name, vs.store_name, vs.store_logo,
               AVG(pr.rating) as avg_rating, COUNT(pr.id) as review_count
        FROM products p
        LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
        LEFT JOIN categories c ON p.category_id = c.id
        LEFT JOIN users u ON p.vendor_id = u.id
        LEFT JOIN vendor_stores vs ON u.id = vs.vendor_id
        LEFT JOIN product_reviews pr ON p.id = pr.product_id AND pr.status = 'approved'
        WHERE p.id IN ($placeholders) AND p.status = 'active'
        GROUP BY p.id
        ORDER BY FIELD(p.id, " . implode(',', $_SESSION['compare_items']) . ")
    ";
    
    $compare_products = $database->fetchAll($products_sql, $_SESSION['compare_items']);
    
    // Get product attributes for comparison
    if (!empty($compare_products)) {
        $product_ids = array_column($compare_products, 'id');
        $placeholders = str_repeat('?,', count($product_ids) - 1) . '?';
        
        $attributes_sql = "
            SELECT product_id, attribute_name, attribute_value
            FROM product_attributes
            WHERE product_id IN ($placeholders)
            ORDER BY attribute_name, product_id
        ";
        
        $attributes = $database->fetchAll($attributes_sql, $product_ids);
        
        // Organize attributes by product
        $product_attributes = [];
        foreach ($attributes as $attr) {
            $product_attributes[$attr['product_id']][$attr['attribute_name']] = $attr['attribute_value'];
        }
        
        // Get all unique attribute names for comparison table
        $all_attributes = [];
        foreach ($attributes as $attr) {
            if (!in_array($attr['attribute_name'], $all_attributes)) {
                $all_attributes[] = $attr['attribute_name'];
            }
        }
        
        $comparison_data = [
            'products' => $compare_products,
            'attributes' => $product_attributes,
            'all_attributes' => $all_attributes
        ];
    }
}

// Get suggested products for comparison (same category as compared products)
$suggested_products = [];
if (!empty($compare_products)) {
    $categories = array_unique(array_column($compare_products, 'category_id'));
    $exclude_ids = array_column($compare_products, 'id');
    
    if (!empty($categories)) {
        $cat_placeholders = str_repeat('?,', count($categories) - 1) . '?';
        $exclude_placeholders = str_repeat('?,', count($exclude_ids) - 1) . '?';
        
        $suggested_sql = "
            SELECT p.id, p.name, p.price, pi.image_url, u.username as vendor_name, vs.store_name,
                   AVG(pr.rating) as avg_rating, COUNT(pr.id) as review_count
            FROM products p
            LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
            LEFT JOIN users u ON p.vendor_id = u.id
            LEFT JOIN vendor_stores vs ON u.id = vs.vendor_id
            LEFT JOIN product_reviews pr ON p.id = pr.product_id AND pr.status = 'approved'
            WHERE p.category_id IN ($cat_placeholders) 
            AND p.id NOT IN ($exclude_placeholders)
            AND p.status = 'active'
            GROUP BY p.id
            ORDER BY avg_rating DESC, review_count DESC
            LIMIT 8
        ";
        
        $suggested_products = $database->fetchAll($suggested_sql, array_merge($categories, $exclude_ids));
    }
}

require_once 'includes/header.php';
?>

<style>
/* Enhanced Responsive Comparison Styles */
:root {
    --primary-color: #10b981;
    --secondary-color: #059669;
    --accent-color: #34d399;
    --text-dark: #1f2937;
    --text-light: #6b7280;
    --bg-light: #f9fafb;
    --white: #ffffff;
    --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    --shadow-lg: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
    --border-radius: 12px;
}

.compare-container {
    max-width: 1400px;
    margin: 0 auto;
    padding: 2rem;
}

.compare-header {
    text-align: center;
    margin-bottom: 3rem;
    padding: 3rem 2rem;
    background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
    color: white;
    border-radius: var(--border-radius);
}

.compare-header h1 {
    font-size: clamp(2rem, 5vw, 3rem);
    font-weight: 800;
    margin-bottom: 1rem;
}

.compare-header p {
    font-size: 1.2rem;
    opacity: 0.9;
    margin-bottom: 2rem;
}

.compare-actions {
    display: flex;
    gap: 1rem;
    justify-content: center;
    flex-wrap: wrap;
}

.btn {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 1rem 2rem;
    border-radius: var(--border-radius);
    text-decoration: none;
    font-weight: 600;
    transition: all 0.3s ease;
    border: 2px solid transparent;
    cursor: pointer;
    font-size: 1rem;
}

.btn-primary {
    background: white;
    color: var(--primary-color);
    box-shadow: var(--shadow);
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow-lg);
}

.btn-outline-white {
    background: transparent;
    color: white;
    border-color: rgba(255, 255, 255, 0.3);
}

.btn-outline-white:hover {
    background: rgba(255, 255, 255, 0.1);
    border-color: white;
}

.empty-state {
    text-align: center;
    padding: 4rem 2rem;
    background: white;
    border-radius: var(--border-radius);
    box-shadow: var(--shadow);
}

.empty-icon {
    font-size: 4rem;
    color: var(--text-light);
    margin-bottom: 2rem;
}

.empty-title {
    font-size: 2rem;
    font-weight: 700;
    color: var(--text-dark);
    margin-bottom: 1rem;
}

.empty-subtitle {
    font-size: 1.1rem;
    color: var(--text-light);
    margin-bottom: 2rem;
    max-width: 500px;
    margin-left: auto;
    margin-right: auto;
}

.comparison-table-container {
    background: white;
    border-radius: var(--border-radius);
    box-shadow: var(--shadow);
    overflow: hidden;
}

.comparison-header-bar {
    padding: 1.5rem;
    background: var(--bg-light);
    border-bottom: 1px solid #e5e7eb;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 1rem;
}

.comparison-title {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--text-dark);
}

.view-toggle {
    display: flex;
    gap: 0.5rem;
}

.toggle-btn {
    padding: 0.5rem 1rem;
    border: 2px solid var(--primary-color);
    background: transparent;
    color: var(--primary-color);
    border-radius: 8px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.3s;
}

.toggle-btn.active {
    background: var(--primary-color);
    color: white;
}

.toggle-btn:hover {
    background: var(--primary-color);
    color: white;
}

.comparison-table {
    width: 100%;
    border-collapse: collapse;
}

.comparison-table th,
.comparison-table td {
    padding: 1.5rem;
    border-bottom: 1px solid #e5e7eb;
    vertical-align: top;
}

.comparison-table th {
    background: var(--bg-light);
    font-weight: 600;
    color: var(--text-dark);
}

.comparison-table th:first-child {
    width: 200px;
    text-align: left;
}

.comparison-table th:not(:first-child) {
    text-align: center;
    min-width: 250px;
}

.product-header {
    position: relative;
    text-align: center;
}

.remove-product {
    position: absolute;
    top: -10px;
    right: -10px;
    width: 30px;
    height: 30px;
    background: #ef4444;
    color: white;
    border: none;
    border-radius: 50%;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.8rem;
    transition: all 0.3s;
}

.remove-product:hover {
    background: #dc2626;
    transform: scale(1.1);
}

.product-image {
    width: 120px;
    height: 120px;
    object-fit: cover;
    border-radius: var(--border-radius);
    margin-bottom: 1rem;
    box-shadow: var(--shadow);
}

.product-name {
    font-size: 1.1rem;
    font-weight: 600;
    color: var(--text-dark);
    margin-bottom: 0.5rem;
}

.product-vendor {
    font-size: 0.9rem;
    color: var(--text-light);
    margin-bottom: 1rem;
}

.row-label {
    font-weight: 600;
    color: var(--text-dark);
    background: var(--bg-light);
}

.price-cell {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--primary-color);
}

.rating-cell {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
}

.stars {
    color: #fbbf24;
}

.action-buttons {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.btn-sm {
    padding: 0.5rem 1rem;
    font-size: 0.9rem;
}

.btn-success {
    background: var(--primary-color);
    color: white;
    border: 2px solid var(--primary-color);
}

.btn-success:hover {
    background: var(--secondary-color);
    border-color: var(--secondary-color);
}

.btn-outline {
    background: transparent;
    color: var(--primary-color);
    border: 2px solid var(--primary-color);
}

.btn-outline:hover {
    background: var(--primary-color);
    color: white;
}

/* Responsive Design */
@media (max-width: 768px) {
    .compare-container {
        padding: 1rem;
    }

    .compare-header {
        padding: 2rem 1rem;
    }

    .comparison-header-bar {
        flex-direction: column;
        text-align: center;
    }

    .comparison-table {
        font-size: 0.9rem;
    }

    .comparison-table th,
    .comparison-table td {
        padding: 1rem;
    }

    .comparison-table th:first-child {
        width: 150px;
    }

    .comparison-table th:not(:first-child) {
        min-width: 200px;
    }

    .product-image {
        width: 80px;
        height: 80px;
    }

    .action-buttons {
        gap: 0.25rem;
    }
}

@media (max-width: 480px) {
    .comparison-table {
        font-size: 0.8rem;
    }

    .comparison-table th:first-child {
        width: 120px;
    }

    .comparison-table th:not(:first-child) {
        min-width: 180px;
    }

    .product-image {
        width: 60px;
        height: 60px;
    }

    .price-cell {
        font-size: 1.2rem;
    }
}

/* Advanced Comparison Controls */
.comparison-controls {
    background: white;
    border-radius: var(--border-radius);
    box-shadow: var(--shadow);
    margin-bottom: 2rem;
    overflow: hidden;
}

.controls-section {
    padding: 2rem;
    border-bottom: 1px solid #e5e7eb;
}

.controls-section h3 {
    color: var(--text-dark);
    margin-bottom: 1.5rem;
    font-size: 1.25rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.control-buttons {
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
}

.control-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1.5rem;
    background: var(--primary-color);
    color: white;
    border: none;
    border-radius: 8px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.3s ease;
    font-size: 0.9rem;
}

.control-btn:hover {
    background: var(--secondary-color);
    transform: translateY(-2px);
    box-shadow: var(--shadow-lg);
}

.control-btn:active {
    transform: translateY(0);
}

.comparison-filters {
    padding: 2rem;
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 2rem;
    background: var(--bg-light);
}

.filter-group {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.filter-group label {
    font-weight: 600;
    color: var(--text-dark);
    font-size: 0.9rem;
}

.filter-group select {
    padding: 0.75rem;
    border: 2px solid #e5e7eb;
    border-radius: 8px;
    background: white;
    font-size: 0.9rem;
    transition: border-color 0.3s;
}

.filter-group select:focus {
    outline: none;
    border-color: var(--primary-color);
}

.price-range {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.price-range input[type="range"] {
    width: 100%;
    height: 6px;
    border-radius: 3px;
    background: #e5e7eb;
    outline: none;
    -webkit-appearance: none;
}

.price-range input[type="range"]::-webkit-slider-thumb {
    -webkit-appearance: none;
    appearance: none;
    width: 20px;
    height: 20px;
    border-radius: 50%;
    background: var(--primary-color);
    cursor: pointer;
    box-shadow: 0 2px 4px rgba(0,0,0,0.2);
}

.price-range input[type="range"]::-moz-range-thumb {
    width: 20px;
    height: 20px;
    border-radius: 50%;
    background: var(--primary-color);
    cursor: pointer;
    border: none;
    box-shadow: 0 2px 4px rgba(0,0,0,0.2);
}

.price-display {
    text-align: center;
    font-weight: 600;
    color: var(--primary-color);
    font-size: 0.9rem;
}

.best-value {
    position: relative;
    background: linear-gradient(135deg, #fef3c7, #fbbf24) !important;
    border: 3px solid #f59e0b !important;
}

.best-value::before {
    content: "🏆 BEST VALUE";
    position: absolute;
    top: -15px;
    left: 50%;
    transform: translateX(-50%);
    background: #f59e0b;
    color: white;
    padding: 0.25rem 1rem;
    border-radius: 12px;
    font-size: 0.75rem;
    font-weight: 700;
    z-index: 10;
}

.comparison-highlight {
    animation: highlight 2s ease-in-out;
}

@keyframes highlight {
    0%, 100% { background: transparent; }
    50% { background: rgba(16, 185, 129, 0.1); }
}
</style>

<div class="compare-container">
    <!-- Header -->
    <div class="compare-header">
        <h1>🔍 Compare Products</h1>
        <p>Compare products from different vendors to make the best choice for your needs</p>
        <div class="compare-actions">
            <?php if (!empty($_SESSION['compare_items'])): ?>
                <button onclick="clearComparison()" class="btn btn-outline-white">
                    <i class="fas fa-trash"></i> Clear All
                </button>
            <?php endif; ?>
            <a href="products.php" class="btn btn-primary">
                <i class="fas fa-plus"></i> Add Products
            </a>
        </div>
    </div>

    <?php if (empty($compare_products)): ?>
        <!-- Empty State -->
        <div class="empty-state">
            <div class="empty-icon">
                <i class="fas fa-balance-scale"></i>
            </div>
            <h2 class="empty-title">No Products to Compare</h2>
            <p class="empty-subtitle">
                Start by adding products to your comparison list. You can compare up to 4 products at once to find the perfect match for your needs.
            </p>
            <div style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap; margin-bottom: 3rem;">
                <a href="products.php" class="btn btn-primary">
                    <i class="fas fa-shopping-bag"></i> Browse Products
                </a>
                <a href="categories.php" class="btn btn-outline">
                    <i class="fas fa-th-large"></i> View Categories
                </a>
            </div>
                
                <!-- How to Compare Guide -->
                <div style="background: var(--light-gray); padding: 2rem; border-radius: var(--border-radius); text-align: left; max-width: 600px; margin: 0 auto;">
                    <h5 style="color: var(--primary-green); margin-bottom: 1rem;">How to Compare Products:</h5>
                    <ol style="color: var(--dark-gray); line-height: 1.8;">
                        <li>Browse products and click the <i class="fas fa-balance-scale" style="color: var(--primary-green);"></i> icon</li>
                        <li>Add up to 4 products from different vendors</li>
                        <li>Compare prices, features, ratings, and vendor information</li>
                        <li>Make an informed decision and purchase the best option</li>
                    </ol>
                </div>
            </div>
        </div>
        
    <?php else: ?>
        <!-- Advanced Comparison Controls -->
        <div class="comparison-controls">
            <div class="controls-section">
                <h3><i class="fas fa-sliders-h"></i> Comparison Tools</h3>
                <div class="control-buttons">
                    <button onclick="sortByPrice()" class="control-btn">
                        <i class="fas fa-sort-amount-down"></i> Sort by Price
                    </button>
                    <button onclick="sortByRating()" class="control-btn">
                        <i class="fas fa-star"></i> Sort by Rating
                    </button>
                    <button onclick="highlightBestValue()" class="control-btn">
                        <i class="fas fa-trophy"></i> Highlight Best Value
                    </button>
                    <button onclick="shareComparison()" class="control-btn">
                        <i class="fas fa-share"></i> Share Comparison
                    </button>
                    <button onclick="exportComparison()" class="control-btn">
                        <i class="fas fa-download"></i> Export PDF
                    </button>
                </div>
            </div>

            <div class="comparison-filters">
                <div class="filter-group">
                    <label>Price Range:</label>
                    <div class="price-range">
                        <input type="range" id="minPrice" min="0" max="1000000" value="0" oninput="filterByPrice()">
                        <input type="range" id="maxPrice" min="0" max="1000000" value="1000000" oninput="filterByPrice()">
                        <div class="price-display">
                            <span id="minPriceDisplay">RWF 0</span> - <span id="maxPriceDisplay">RWF 1,000,000</span>
                        </div>
                    </div>
                </div>

                <div class="filter-group">
                    <label>Minimum Rating:</label>
                    <select id="minRating" onchange="filterByRating()">
                        <option value="0">Any Rating</option>
                        <option value="1">1+ Stars</option>
                        <option value="2">2+ Stars</option>
                        <option value="3">3+ Stars</option>
                        <option value="4">4+ Stars</option>
                        <option value="5">5 Stars Only</option>
                    </select>
                </div>

                <div class="filter-group">
                    <label>Vendor:</label>
                    <select id="vendorFilter" onchange="filterByVendor()">
                        <option value="">All Vendors</option>
                        <?php
                        $vendors = array_unique(array_column($compare_products, 'vendor_name'));
                        foreach ($vendors as $vendor): ?>
                            <option value="<?php echo htmlspecialchars($vendor); ?>"><?php echo htmlspecialchars($vendor); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>

        <!-- Comparison Table -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 style="margin-bottom: 0;">Comparing <?php echo count($compare_products); ?> Products</h5>
                <div style="display: flex; gap: 1rem;">
                    <button onclick="toggleComparisonMode('basic')" class="btn btn-outline btn-sm comparison-mode-btn active" data-mode="basic">
                        Basic View
                    </button>
                    <button onclick="toggleComparisonMode('detailed')" class="btn btn-outline btn-sm comparison-mode-btn" data-mode="detailed">
                        Detailed View
                    </button>
                </div>
            </div>
            
            <div class="card-body">
                <!-- Basic Comparison View -->
                <div id="basic-comparison" class="comparison-view">
                    <div class="table-responsive">
                        <table style="width: 100%; border-collapse: collapse;">
                            <thead>
                                <tr>
                                    <th style="padding: 1rem; text-align: left; width: 200px; border-bottom: 2px solid var(--light-gray);">Product</th>
                                    <?php foreach ($compare_products as $product): ?>
                                        <th style="padding: 1rem; text-align: center; border-bottom: 2px solid var(--light-gray); min-width: 250px;">
                                            <div style="position: relative;">
                                                <button onclick="removeFromComparison(<?php echo $product['id']; ?>)" 
                                                        style="position: absolute; top: -10px; right: -10px; background: #F44336; color: white; border: none; border-radius: 50%; width: 24px; height: 24px; font-size: 0.8rem; cursor: pointer;">
                                                    ×
                                                </button>
                                                <img src="<?php echo $product['image_url'] ?: 'assets/images/product-placeholder.png'; ?>" 
                                                     alt="<?php echo htmlspecialchars($product['name']); ?>" 
                                                     style="width: 120px; height: 120px; object-fit: cover; border-radius: var(--border-radius); margin-bottom: 1rem;">
                                                <h6 style="margin-bottom: 0.5rem;"><?php echo htmlspecialchars($product['name']); ?></h6>
                                                <small class="text-muted">by <?php echo htmlspecialchars($product['store_name'] ?: $product['vendor_name']); ?></small>
                                            </div>
                                        </th>
                                    <?php endforeach; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Price Row -->
                                <tr style="border-bottom: 1px solid var(--light-gray);">
                                    <td style="padding: 1rem; font-weight: 600; background: var(--light-gray);">Price</td>
                                    <?php foreach ($compare_products as $product): ?>
                                        <td style="padding: 1rem; text-align: center;">
                                            <div style="font-size: 1.5rem; font-weight: bold; color: var(--primary-green);">
                                                <?php echo formatCurrency($product['price']); ?>
                                            </div>
                                            <?php if ($product['compare_price'] > $product['price']): ?>
                                                <small style="text-decoration: line-through; color: var(--dark-gray);">
                                                    <?php echo formatCurrency($product['compare_price']); ?>
                                                </small>
                                            <?php endif; ?>
                                        </td>
                                    <?php endforeach; ?>
                                </tr>
                                
                                <!-- Rating Row -->
                                <tr style="border-bottom: 1px solid var(--light-gray);">
                                    <td style="padding: 1rem; font-weight: 600; background: var(--light-gray);">Rating</td>
                                    <?php foreach ($compare_products as $product): ?>
                                        <td style="padding: 1rem; text-align: center;">
                                            <?php if ($product['review_count'] > 0): ?>
                                                <div class="stars" style="color: #FFD700; font-size: 1.2rem; margin-bottom: 0.5rem;">
                                                    <?php 
                                                    $rating = round($product['avg_rating']);
                                                    for ($i = 1; $i <= 5; $i++) {
                                                        echo $i <= $rating ? '★' : '☆';
                                                    }
                                                    ?>
                                                </div>
                                                <small class="text-muted"><?php echo $product['review_count']; ?> reviews</small>
                                            <?php else: ?>
                                                <span class="text-muted">No reviews yet</span>
                                            <?php endif; ?>
                                        </td>
                                    <?php endforeach; ?>
                                </tr>
                                
                                <!-- Stock Row -->
                                <tr style="border-bottom: 1px solid var(--light-gray);">
                                    <td style="padding: 1rem; font-weight: 600; background: var(--light-gray);">Availability</td>
                                    <?php foreach ($compare_products as $product): ?>
                                        <td style="padding: 1rem; text-align: center;">
                                            <?php if ($product['stock_quantity'] > 0): ?>
                                                <span style="color: var(--secondary-green); font-weight: 600;">
                                                    <i class="fas fa-check-circle"></i> In Stock (<?php echo $product['stock_quantity']; ?>)
                                                </span>
                                            <?php else: ?>
                                                <span style="color: #F44336; font-weight: 600;">
                                                    <i class="fas fa-times-circle"></i> Out of Stock
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                    <?php endforeach; ?>
                                </tr>
                                
                                <!-- Category Row -->
                                <tr style="border-bottom: 1px solid var(--light-gray);">
                                    <td style="padding: 1rem; font-weight: 600; background: var(--light-gray);">Category</td>
                                    <?php foreach ($compare_products as $product): ?>
                                        <td style="padding: 1rem; text-align: center;">
                                            <?php echo htmlspecialchars($product['category_name']); ?>
                                        </td>
                                    <?php endforeach; ?>
                                </tr>
                                
                                <!-- Actions Row -->
                                <tr>
                                    <td style="padding: 1rem; font-weight: 600; background: var(--light-gray);">Actions</td>
                                    <?php foreach ($compare_products as $product): ?>
                                        <td style="padding: 1rem; text-align: center;">
                                            <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                                                <a href="product.php?id=<?php echo $product['id']; ?>" 
                                                   class="btn btn-primary btn-sm" style="width: 100%;">
                                                    View Details
                                                </a>
                                                <button onclick="addToCart(<?php echo $product['id']; ?>)" 
                                                        class="btn btn-outline btn-sm" style="width: 100%;">
                                                    Add to Cart
                                                </button>
                                                <?php if (isLoggedIn()): ?>
                                                <button onclick="toggleWishlist(<?php echo $product['id']; ?>)" 
                                                        class="btn btn-outline btn-sm" style="width: 100%;">
                                                    <i class="fas fa-heart"></i> Wishlist
                                                </button>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    <?php endforeach; ?>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- Detailed Comparison View -->
                <div id="detailed-comparison" class="comparison-view" style="display: none;">
                    <div class="table-responsive">
                        <table style="width: 100%; border-collapse: collapse;">
                            <thead>
                                <tr>
                                    <th style="padding: 1rem; text-align: left; width: 200px; border-bottom: 2px solid var(--light-gray);">Specification</th>
                                    <?php foreach ($compare_products as $product): ?>
                                        <th style="padding: 1rem; text-align: center; border-bottom: 2px solid var(--light-gray);">
                                            <?php echo htmlspecialchars($product['name']); ?>
                                        </th>
                                    <?php endforeach; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Description Row -->
                                <tr style="border-bottom: 1px solid var(--light-gray);">
                                    <td style="padding: 1rem; font-weight: 600; background: var(--light-gray);">Description</td>
                                    <?php foreach ($compare_products as $product): ?>
                                        <td style="padding: 1rem; text-align: center;">
                                            <div style="max-height: 100px; overflow-y: auto; text-align: left;">
                                                <?php echo htmlspecialchars($product['short_description']); ?>
                                            </div>
                                        </td>
                                    <?php endforeach; ?>
                                </tr>
                                
                                <!-- SKU Row -->
                                <tr style="border-bottom: 1px solid var(--light-gray);">
                                    <td style="padding: 1rem; font-weight: 600; background: var(--light-gray);">SKU</td>
                                    <?php foreach ($compare_products as $product): ?>
                                        <td style="padding: 1rem; text-align: center;">
                                            <code><?php echo htmlspecialchars($product['sku']); ?></code>
                                        </td>
                                    <?php endforeach; ?>
                                </tr>
                                
                                <!-- Weight Row -->
                                <?php if (array_filter(array_column($compare_products, 'weight'))): ?>
                                <tr style="border-bottom: 1px solid var(--light-gray);">
                                    <td style="padding: 1rem; font-weight: 600; background: var(--light-gray);">Weight</td>
                                    <?php foreach ($compare_products as $product): ?>
                                        <td style="padding: 1rem; text-align: center;">
                                            <?php echo $product['weight'] ? $product['weight'] . ' kg' : 'N/A'; ?>
                                        </td>
                                    <?php endforeach; ?>
                                </tr>
                                <?php endif; ?>
                                
                                <!-- Dimensions Row -->
                                <?php if (array_filter(array_column($compare_products, 'dimensions'))): ?>
                                <tr style="border-bottom: 1px solid var(--light-gray);">
                                    <td style="padding: 1rem; font-weight: 600; background: var(--light-gray);">Dimensions</td>
                                    <?php foreach ($compare_products as $product): ?>
                                        <td style="padding: 1rem; text-align: center;">
                                            <?php echo $product['dimensions'] ?: 'N/A'; ?>
                                        </td>
                                    <?php endforeach; ?>
                                </tr>
                                <?php endif; ?>
                                
                                <!-- Product Attributes -->
                                <?php if (!empty($comparison_data['all_attributes'])): ?>
                                    <?php foreach ($comparison_data['all_attributes'] as $attr_name): ?>
                                        <tr style="border-bottom: 1px solid var(--light-gray);">
                                            <td style="padding: 1rem; font-weight: 600; background: var(--light-gray);">
                                                <?php echo htmlspecialchars($attr_name); ?>
                                            </td>
                                            <?php foreach ($compare_products as $product): ?>
                                                <td style="padding: 1rem; text-align: center;">
                                                    <?php 
                                                    echo isset($comparison_data['attributes'][$product['id']][$attr_name]) 
                                                        ? htmlspecialchars($comparison_data['attributes'][$product['id']][$attr_name])
                                                        : 'N/A';
                                                    ?>
                                                </td>
                                            <?php endforeach; ?>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                                
                                <!-- Vendor Information -->
                                <tr style="border-bottom: 1px solid var(--light-gray);">
                                    <td style="padding: 1rem; font-weight: 600; background: var(--light-gray);">Vendor</td>
                                    <?php foreach ($compare_products as $product): ?>
                                        <td style="padding: 1rem; text-align: center;">
                                            <div>
                                                <strong><?php echo htmlspecialchars($product['store_name'] ?: $product['vendor_name']); ?></strong>
                                                <br>
                                                <a href="vendor.php?id=<?php echo $product['vendor_id']; ?>" 
                                                   style="color: var(--primary-green); font-size: 0.9rem;">
                                                    View Store
                                                </a>
                                            </div>
                                        </td>
                                    <?php endforeach; ?>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Comparison Tools -->
        <div class="card mt-3">
            <div class="card-header">
                <h6 style="margin-bottom: 0;">Comparison Tools</h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-3">
                        <button onclick="highlightBestPrice()" class="btn btn-outline" style="width: 100%;">
                            <i class="fas fa-dollar-sign"></i> Highlight Best Price
                        </button>
                    </div>
                    <div class="col-3">
                        <button onclick="highlightBestRated()" class="btn btn-outline" style="width: 100%;">
                            <i class="fas fa-star"></i> Highlight Best Rated
                        </button>
                    </div>
                    <div class="col-3">
                        <button onclick="exportComparison()" class="btn btn-outline" style="width: 100%;">
                            <i class="fas fa-download"></i> Export Comparison
                        </button>
                    </div>
                    <div class="col-3">
                        <button onclick="shareComparison()" class="btn btn-outline" style="width: 100%;">
                            <i class="fas fa-share"></i> Share Comparison
                        </button>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
    
    <!-- Suggested Products -->
    <?php if (!empty($suggested_products)): ?>
        <div class="card mt-3">
            <div class="card-header">
                <h6 style="margin-bottom: 0;">You Might Also Want to Compare</h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <?php foreach ($suggested_products as $product): ?>
                        <div class="col-3 mb-2">
                            <div style="border: 1px solid var(--light-gray); border-radius: var(--border-radius); padding: 1rem; text-align: center;">
                                <img src="<?php echo $product['image_url'] ?: 'assets/images/product-placeholder.png'; ?>" 
                                     alt="<?php echo htmlspecialchars($product['name']); ?>" 
                                     style="width: 80px; height: 80px; object-fit: cover; border-radius: 4px; margin-bottom: 0.5rem;">
                                <h6 style="font-size: 0.9rem; margin-bottom: 0.5rem;"><?php echo htmlspecialchars($product['name']); ?></h6>
                                <div style="color: var(--primary-green); font-weight: 600; margin-bottom: 0.5rem;">
                                    <?php echo formatCurrency($product['price']); ?>
                                </div>
                                <button onclick="addToComparison(<?php echo $product['id']; ?>)" 
                                        class="btn btn-outline btn-sm" style="width: 100%;">
                                    <i class="fas fa-plus"></i> Add to Compare
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<style>
.comparison-view table th,
.comparison-view table td {
    vertical-align: top;
}

.comparison-mode-btn.active {
    background: var(--primary-green);
    color: white;
}

.highlight-best-price {
    background: #E8F5E8 !important;
    border: 2px solid var(--secondary-green) !important;
}

.highlight-best-rated {
    background: #FFF8E1 !important;
    border: 2px solid #FFB74D !important;
}

@media (max-width: 768px) {
    .col-3 {
        flex: 0 0 50%;
        margin-bottom: 1rem;
    }
    
    .comparison-view table {
        font-size: 0.9rem;
    }
    
    .comparison-view th,
    .comparison-view td {
        padding: 0.5rem !important;
        min-width: 150px;
    }
}

@media (max-width: 480px) {
    .col-3 {
        flex: 0 0 100%;
    }
}
</style>

<script>
function toggleComparisonMode(mode) {
    // Update button states
    document.querySelectorAll('.comparison-mode-btn').forEach(btn => {
        btn.classList.remove('active');
    });
    document.querySelector(`[data-mode="${mode}"]`).classList.add('active');
    
    // Show/hide comparison views
    document.getElementById('basic-comparison').style.display = mode === 'basic' ? 'block' : 'none';
    document.getElementById('detailed-comparison').style.display = mode === 'detailed' ? 'block' : 'none';
}

function removeFromComparison(productId) {
    fetch('api/compare.php', {
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
            showNotification(data.message || 'Error removing product', 'error');
        }
    })
    .catch(error => {
        showNotification('Error removing product', 'error');
    });
}

function clearComparison() {
    if (confirm('Are you sure you want to clear all products from comparison?')) {
        fetch('api/compare.php', {
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
                showNotification(data.message || 'Error clearing comparison', 'error');
            }
        });
    }
}

// Advanced Comparison Functions
function sortByPrice() {
    const table = document.querySelector('.comparison-table tbody');
    if (!table) return;

    const products = Array.from(document.querySelectorAll('.comparison-table thead th:not(:first-child)'));
    const prices = products.map(product => {
        const priceText = product.closest('table').querySelector('tbody tr:first-child td:nth-child(' + (products.indexOf(product) + 2) + ')').textContent;
        return parseFloat(priceText.replace(/[^\d.]/g, ''));
    });

    // Sort indices by price
    const sortedIndices = prices.map((price, index) => ({ price, index }))
        .sort((a, b) => a.price - b.price)
        .map(item => item.index);

    // Reorder columns
    reorderColumns(sortedIndices);
    showNotification('Products sorted by price (low to high)', 'success');
}

function sortByRating() {
    const table = document.querySelector('.comparison-table tbody');
    if (!table) return;

    const products = Array.from(document.querySelectorAll('.comparison-table thead th:not(:first-child)'));
    const ratings = products.map(product => {
        const ratingRow = Array.from(table.querySelectorAll('tr')).find(row =>
            row.querySelector('td:first-child').textContent.includes('Rating')
        );
        if (ratingRow) {
            const ratingCell = ratingRow.querySelector('td:nth-child(' + (products.indexOf(product) + 2) + ')');
            const stars = ratingCell.querySelectorAll('.fa-star').length;
            return stars;
        }
        return 0;
    });

    // Sort indices by rating
    const sortedIndices = ratings.map((rating, index) => ({ rating, index }))
        .sort((a, b) => b.rating - a.rating)
        .map(item => item.index);

    // Reorder columns
    reorderColumns(sortedIndices);
    showNotification('Products sorted by rating (high to low)', 'success');
}

function highlightBestValue() {
    const products = Array.from(document.querySelectorAll('.comparison-table thead th:not(:first-child)'));

    // Remove existing highlights
    products.forEach(product => {
        product.classList.remove('best-value');
    });

    // Calculate value scores (rating/price ratio)
    const valueScores = products.map((product, index) => {
        const priceRow = document.querySelector('.comparison-table tbody tr:first-child');
        const ratingRow = Array.from(document.querySelectorAll('.comparison-table tbody tr')).find(row =>
            row.querySelector('td:first-child').textContent.includes('Rating')
        );

        const priceText = priceRow.querySelector('td:nth-child(' + (index + 2) + ')').textContent;
        const price = parseFloat(priceText.replace(/[^\d.]/g, ''));

        let rating = 3; // Default rating
        if (ratingRow) {
            const ratingCell = ratingRow.querySelector('td:nth-child(' + (index + 2) + ')');
            const stars = ratingCell.querySelectorAll('.fa-star').length;
            rating = stars || 3;
        }

        return { index, score: rating / (price / 10000) }; // Normalize price
    });

    // Find best value
    const bestValue = valueScores.reduce((best, current) =>
        current.score > best.score ? current : best
    );

    // Highlight best value
    products[bestValue.index].classList.add('best-value');
    showNotification('Best value product highlighted!', 'success');
}

function shareComparison() {
    const url = window.location.href;
    const title = 'Product Comparison - MarketHub';

    if (navigator.share) {
        navigator.share({
            title: title,
            text: 'Check out this product comparison on MarketHub',
            url: url
        }).then(() => {
            showNotification('Comparison shared successfully!', 'success');
        }).catch(() => {
            copyToClipboard(url);
        });
    } else {
        copyToClipboard(url);
    }
}

function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(() => {
        showNotification('Comparison link copied to clipboard!', 'success');
    }).catch(() => {
        // Fallback for older browsers
        const textArea = document.createElement('textarea');
        textArea.value = text;
        document.body.appendChild(textArea);
        textArea.select();
        document.execCommand('copy');
        document.body.removeChild(textArea);
        showNotification('Comparison link copied to clipboard!', 'success');
    });
}

function exportComparison() {
    showNotification('PDF export feature coming soon!', 'info');
    // In a real implementation, you would generate a PDF of the comparison
}

function reorderColumns(sortedIndices) {
    const table = document.querySelector('.comparison-table');
    const thead = table.querySelector('thead tr');
    const tbody = table.querySelector('tbody');

    // Store original columns
    const headerCells = Array.from(thead.querySelectorAll('th:not(:first-child)'));
    const bodyRows = Array.from(tbody.querySelectorAll('tr'));

    // Reorder header
    sortedIndices.forEach((originalIndex, newIndex) => {
        thead.appendChild(headerCells[originalIndex]);
    });

    // Reorder body cells
    bodyRows.forEach(row => {
        const cells = Array.from(row.querySelectorAll('td:not(:first-child)'));
        sortedIndices.forEach((originalIndex, newIndex) => {
            row.appendChild(cells[originalIndex]);
        });
    });
}

// Filter Functions
function filterByPrice() {
    const minPrice = parseInt(document.getElementById('minPrice').value);
    const maxPrice = parseInt(document.getElementById('maxPrice').value);

    document.getElementById('minPriceDisplay').textContent = 'RWF ' + minPrice.toLocaleString();
    document.getElementById('maxPriceDisplay').textContent = 'RWF ' + maxPrice.toLocaleString();

    // Apply filter logic here
    showNotification(`Filtering products between RWF ${minPrice.toLocaleString()} - RWF ${maxPrice.toLocaleString()}`, 'info');
}

function filterByRating() {
    const minRating = parseInt(document.getElementById('minRating').value);
    showNotification(`Filtering products with ${minRating}+ star rating`, 'info');
}

function filterByVendor() {
    const vendor = document.getElementById('vendorFilter').value;
    if (vendor) {
        showNotification(`Filtering products from ${vendor}`, 'info');
    } else {
        showNotification('Showing products from all vendors', 'info');
    }
}

// Initialize comparison features
document.addEventListener('DOMContentLoaded', function() {
    // Set initial price range values
    const prices = Array.from(document.querySelectorAll('.comparison-table tbody tr:first-child td:not(:first-child)'))
        .map(cell => parseFloat(cell.textContent.replace(/[^\d.]/g, '')))
        .filter(price => !isNaN(price));

    if (prices.length > 0) {
        const minPrice = Math.min(...prices);
        const maxPrice = Math.max(...prices);

        document.getElementById('minPrice').min = minPrice;
        document.getElementById('minPrice').max = maxPrice;
        document.getElementById('minPrice').value = minPrice;

        document.getElementById('maxPrice').min = minPrice;
        document.getElementById('maxPrice').max = maxPrice;
        document.getElementById('maxPrice').value = maxPrice;

        filterByPrice();
    }
});

function addToComparison(productId) {
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
            setTimeout(() => location.reload(), 1000);
        } else {
            showNotification(data.message || 'Error adding product', 'error');
        }
    });
}

function highlightBestPrice() {
    // Remove existing highlights
    document.querySelectorAll('.highlight-best-price').forEach(el => {
        el.classList.remove('highlight-best-price');
    });
    
    // Find best price
    const priceRows = document.querySelectorAll('tr td:first-child');
    let priceRow = null;
    
    priceRows.forEach(row => {
        if (row.textContent.trim() === 'Price') {
            priceRow = row.parentElement;
        }
    });
    
    if (priceRow) {
        const priceCells = priceRow.querySelectorAll('td:not(:first-child)');
        let minPrice = Infinity;
        let bestCell = null;
        
        priceCells.forEach(cell => {
            const priceText = cell.textContent.replace(/[^\d.]/g, '');
            const price = parseFloat(priceText);
            if (price < minPrice) {
                minPrice = price;
                bestCell = cell;
            }
        });
        
        if (bestCell) {
            bestCell.classList.add('highlight-best-price');
            showNotification('Best price highlighted!', 'success');
        }
    }
}

function highlightBestRated() {
    // Remove existing highlights
    document.querySelectorAll('.highlight-best-rated').forEach(el => {
        el.classList.remove('highlight-best-rated');
    });
    
    // Find rating row
    const ratingRows = document.querySelectorAll('tr td:first-child');
    let ratingRow = null;
    
    ratingRows.forEach(row => {
        if (row.textContent.trim() === 'Rating') {
            ratingRow = row.parentElement;
        }
    });
    
    if (ratingRow) {
        const ratingCells = ratingRow.querySelectorAll('td:not(:first-child)');
        let maxRating = 0;
        let bestCell = null;
        
        ratingCells.forEach(cell => {
            const stars = cell.querySelectorAll('.stars');
            if (stars.length > 0) {
                const filledStars = (stars[0].textContent.match(/★/g) || []).length;
                if (filledStars > maxRating) {
                    maxRating = filledStars;
                    bestCell = cell;
                }
            }
        });
        
        if (bestCell) {
            bestCell.classList.add('highlight-best-rated');
            showNotification('Best rated product highlighted!', 'success');
        }
    }
}

function exportComparison() {
    // Create CSV content
    const table = document.querySelector('.comparison-view table');
    let csv = '';
    
    // Get headers
    const headers = Array.from(table.querySelectorAll('thead th')).map(th => th.textContent.trim());
    csv += headers.join(',') + '\n';
    
    // Get rows
    const rows = table.querySelectorAll('tbody tr');
    rows.forEach(row => {
        const cells = Array.from(row.querySelectorAll('td')).map(td => {
            return '"' + td.textContent.trim().replace(/"/g, '""') + '"';
        });
        csv += cells.join(',') + '\n';
    });
    
    // Download CSV
    const blob = new Blob([csv], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'product-comparison.csv';
    a.click();
    window.URL.revokeObjectURL(url);
    
    showNotification('Comparison exported successfully!', 'success');
}

function shareComparison() {
    if (navigator.share) {
        navigator.share({
            title: 'Product Comparison - MarketHub',
            text: 'Check out this product comparison on MarketHub',
            url: window.location.href
        });
    } else {
        // Fallback: copy URL to clipboard
        navigator.clipboard.writeText(window.location.href).then(() => {
            showNotification('Comparison URL copied to clipboard!', 'success');
        });
    }
}

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

// Load recommendations when page loads
document.addEventListener('DOMContentLoaded', function() {
    if (document.querySelector('.recommendations-section')) {
        loadRecommendations();
    }
});

function loadRecommendations() {
    fetch('api/get-similar-products.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayRecommendations(data);
            }
        })
        .catch(error => {
            console.error('Error loading recommendations:', error);
        });
}

function showRecommendations(type) {
    // Update active tab
    document.querySelectorAll('.rec-tab').forEach(tab => tab.classList.remove('active'));
    event.target.classList.add('active');

    // Show corresponding recommendations
    const content = document.getElementById('recommendations-content');
    content.innerHTML = '<div class="loading-recommendations"><i class="fas fa-spinner fa-spin"></i> Loading...</div>';

    // Simulate loading and show content
    setTimeout(() => {
        content.innerHTML = `<div class="rec-products-grid" id="${type}-products"></div>`;
        // Load specific recommendation type
    }, 500);
}

function displayRecommendations(data) {
    const content = document.getElementById('recommendations-content');
    content.innerHTML = `
        <div class="rec-products-grid" id="similar-products">
            ${data.similar_products.map(product => createProductCard(product)).join('')}
        </div>
    `;
}

function createProductCard(product) {
    return `
        <div class="rec-product-card">
            <img src="${product.image_url || 'assets/images/product-placeholder.png'}" alt="${product.name}">
            <h4>${product.name}</h4>
            <p class="vendor">by ${product.store_name || product.vendor_name}</p>
            <p class="price">${formatCurrency(product.price)}</p>
            <div class="rating">
                ${generateStars(product.avg_rating || 0)}
                <span>(${product.review_count || 0})</span>
            </div>
            <button onclick="addToComparison(${product.id})" class="btn btn-primary btn-sm">
                <i class="fas fa-plus"></i> Add to Compare
            </button>
        </div>
    `;
}

function generateStars(rating) {
    const fullStars = Math.floor(rating);
    const halfStar = rating % 1 >= 0.5;
    let stars = '';

    for (let i = 0; i < fullStars; i++) {
        stars += '<i class="fas fa-star"></i>';
    }
    if (halfStar) {
        stars += '<i class="fas fa-star-half-alt"></i>';
    }
    for (let i = fullStars + (halfStar ? 1 : 0); i < 5; i++) {
        stars += '<i class="far fa-star"></i>';
    }

    return stars;
}

function formatCurrency(amount) {
    return 'RWF ' + parseInt(amount).toLocaleString();
}
</script>

    <!-- Smart Recommendations -->
    <?php if (!empty($compare_products)): ?>
        <div class="recommendations-section">
            <h2><i class="fas fa-lightbulb"></i> Smart Recommendations</h2>
            <p>Based on your comparison, here are some products you might also like:</p>

            <div class="recommendations-tabs">
                <button class="rec-tab active" onclick="showRecommendations('similar')">
                    <i class="fas fa-layer-group"></i> Similar Products
                </button>
                <button class="rec-tab" onclick="showRecommendations('trending')">
                    <i class="fas fa-fire"></i> Trending
                </button>
                <button class="rec-tab" onclick="showRecommendations('price')">
                    <i class="fas fa-dollar-sign"></i> Similar Price
                </button>
            </div>

            <div id="recommendations-content">
                <div class="loading-recommendations">
                    <i class="fas fa-spinner fa-spin"></i> Loading recommendations...
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<style>
.recommendations-section {
    margin-top: 3rem;
    background: white;
    border-radius: var(--border-radius);
    box-shadow: var(--shadow);
    padding: 2rem;
}

.recommendations-section h2 {
    color: var(--primary-color);
    margin-bottom: 1rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.recommendations-tabs {
    display: flex;
    gap: 1rem;
    margin: 2rem 0;
    border-bottom: 2px solid #e5e7eb;
}

.rec-tab {
    padding: 1rem 2rem;
    border: none;
    background: transparent;
    color: var(--text-light);
    cursor: pointer;
    border-bottom: 3px solid transparent;
    transition: all 0.3s;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-weight: 500;
}

.rec-tab.active {
    color: var(--primary-color);
    border-bottom-color: var(--primary-color);
}

.rec-tab:hover {
    color: var(--primary-color);
}

.rec-products-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: 1.5rem;
    margin-top: 2rem;
}

.rec-product-card {
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    padding: 1.5rem;
    text-align: center;
    transition: all 0.3s;
}

.rec-product-card:hover {
    box-shadow: var(--shadow-lg);
    transform: translateY(-4px);
}

.rec-product-card img {
    width: 100%;
    height: 150px;
    object-fit: cover;
    border-radius: 8px;
    margin-bottom: 1rem;
}

.rec-product-card h4 {
    margin-bottom: 0.5rem;
    color: var(--text-dark);
}

.rec-product-card .vendor {
    color: var(--text-light);
    font-size: 0.9rem;
    margin-bottom: 0.5rem;
}

.rec-product-card .price {
    font-size: 1.25rem;
    font-weight: 700;
    color: var(--primary-color);
    margin-bottom: 0.5rem;
}

.rec-product-card .rating {
    color: #fbbf24;
    margin-bottom: 1rem;
}

.loading-recommendations {
    text-align: center;
    padding: 3rem;
    color: var(--text-light);
}
</style>

<?php require_once 'includes/footer.php'; ?>
