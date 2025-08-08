<?php
/**
 * MarketHub Categories Page
 * Multi-Vendor E-Commerce Platform
 */

require_once 'config/config.php';

$page_title = 'Product Categories';

// Get all categories with product counts
$categories_sql = "
    SELECT c.*, COUNT(p.id) as product_count,
           MIN(p.price) as min_price, MAX(p.price) as max_price
    FROM categories c
    LEFT JOIN products p ON c.id = p.category_id AND p.status = 'active'
    WHERE c.status = 'active'
    GROUP BY c.id
    ORDER BY c.name ASC
";

$categories = $database->fetchAll($categories_sql);

// Get featured categories (categories with most products)
$featured_categories_sql = "
    SELECT c.*, COUNT(p.id) as product_count, c.image_url
    FROM categories c
    LEFT JOIN products p ON c.id = p.category_id AND p.status = 'active'
    WHERE c.status = 'active'
    GROUP BY c.id
    HAVING product_count > 0
    ORDER BY product_count DESC
    LIMIT 6
";

$featured_categories = $database->fetchAll($featured_categories_sql);

require_once 'includes/header.php';
?>

<div class="container" style="margin: 2rem auto;">
    <!-- Header -->
    <div class="text-center mb-4">
        <h1>Product Categories</h1>
        <p style="font-size: 1.1rem; color: var(--dark-gray);">
            Discover products from local vendors across Musanze District
        </p>
    </div>

    <!-- Featured Categories -->
    <div class="section mb-5">
        <h2 style="color: var(--primary-green); margin-bottom: 2rem; text-align: center;">Featured Categories</h2>
        <div class="row">
            <?php foreach ($featured_categories as $category): ?>
                <div class="col-4 mb-3">
                    <div class="category-card featured">
                        <div class="category-image">
                            <img src="<?php echo $category['image_url'] ?: 'assets/images/category-placeholder.png'; ?>" 
                                 alt="<?php echo htmlspecialchars($category['name']); ?>"
                                 onerror="this.src='assets/images/category-placeholder.png'">
                            <div class="category-overlay">
                                <h3><?php echo htmlspecialchars($category['name']); ?></h3>
                                <p><?php echo number_format($category['product_count']); ?> products</p>
                                <a href="products.php?category=<?php echo $category['id']; ?>" class="btn btn-primary">
                                    Browse Products
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- All Categories -->
    <div class="section">
        <h2 style="color: var(--primary-green); margin-bottom: 2rem;">All Categories</h2>
        
        <?php if (empty($categories)): ?>
            <div class="text-center" style="padding: 4rem;">
                <i class="fas fa-tags" style="font-size: 4rem; color: var(--medium-gray); margin-bottom: 2rem;"></i>
                <h3>No Categories Available</h3>
                <p style="color: var(--dark-gray); font-size: 1.1rem;">
                    Categories will appear here once they are added by administrators.
                </p>
            </div>
        <?php else: ?>
            <div class="categories-grid">
                <?php foreach ($categories as $category): ?>
                    <div class="category-item">
                        <div class="category-icon">
                            <i class="fas fa-<?php echo getCategoryIcon($category['name']); ?>"></i>
                        </div>
                        <div class="category-info">
                            <h4>
                                <a href="products.php?category=<?php echo $category['id']; ?>">
                                    <?php echo htmlspecialchars($category['name']); ?>
                                </a>
                            </h4>
                            <?php if ($category['description']): ?>
                                <p class="category-description">
                                    <?php echo htmlspecialchars($category['description']); ?>
                                </p>
                            <?php endif; ?>
                            <div class="category-stats">
                                <span class="product-count">
                                    <i class="fas fa-box"></i>
                                    <?php echo number_format($category['product_count']); ?> products
                                </span>
                                <?php if ($category['min_price'] && $category['max_price']): ?>
                                    <span class="price-range">
                                        <i class="fas fa-tag"></i>
                                        <?php echo formatCurrency($category['min_price']); ?> - <?php echo formatCurrency($category['max_price']); ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            <div class="category-actions">
                                <a href="products.php?category=<?php echo $category['id']; ?>" class="btn btn-outline">
                                    View Products
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Category Benefits -->
    <div class="section mt-5">
        <div class="card" style="background: linear-gradient(135deg, var(--primary-green), var(--secondary-green)); color: white;">
            <div class="card-body text-center" style="padding: 3rem;">
                <h2 style="color: white; margin-bottom: 2rem;">Why Shop by Category?</h2>
                <div class="row">
                    <div class="col-4">
                        <div style="margin-bottom: 1rem;">
                            <i class="fas fa-search" style="font-size: 2.5rem; margin-bottom: 1rem;"></i>
                        </div>
                        <h4 style="color: white;">Easy Discovery</h4>
                        <p>Find exactly what you're looking for with organized product categories</p>
                    </div>
                    <div class="col-4">
                        <div style="margin-bottom: 1rem;">
                            <i class="fas fa-store" style="font-size: 2.5rem; margin-bottom: 1rem;"></i>
                        </div>
                        <h4 style="color: white;">Local Vendors</h4>
                        <p>Support local businesses and vendors in your community</p>
                    </div>
                    <div class="col-4">
                        <div style="margin-bottom: 1rem;">
                            <i class="fas fa-star" style="font-size: 2.5rem; margin-bottom: 1rem;"></i>
                        </div>
                        <h4 style="color: white;">Quality Products</h4>
                        <p>Browse curated products with reviews from verified customers</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.category-card {
    border-radius: var(--border-radius);
    overflow: hidden;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    transition: var(--transition);
    height: 250px;
}

.category-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.15);
}

.category-image {
    position: relative;
    height: 100%;
    overflow: hidden;
}

.category-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: var(--transition);
}

.category-card:hover .category-image img {
    transform: scale(1.1);
}

.category-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(to bottom, rgba(0,0,0,0.3), rgba(0,0,0,0.7));
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    color: white;
    text-align: center;
    padding: 2rem;
}

.category-overlay h3 {
    font-size: 1.5rem;
    margin-bottom: 0.5rem;
    color: white;
}

.category-overlay p {
    margin-bottom: 1rem;
    opacity: 0.9;
}

.categories-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 2rem;
}

.category-item {
    background: white;
    border-radius: var(--border-radius);
    padding: 2rem;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    transition: var(--transition);
    display: flex;
    align-items: flex-start;
    gap: 1.5rem;
}

.category-item:hover {
    transform: translateY(-3px);
    box-shadow: 0 4px 20px rgba(0,0,0,0.15);
}

.category-icon {
    width: 60px;
    height: 60px;
    background: var(--primary-green);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.5rem;
    flex-shrink: 0;
}

.category-info {
    flex: 1;
}

.category-info h4 {
    margin-bottom: 0.5rem;
}

.category-info h4 a {
    color: var(--black);
    text-decoration: none;
    transition: var(--transition);
}

.category-info h4 a:hover {
    color: var(--primary-green);
}

.category-description {
    color: var(--dark-gray);
    margin-bottom: 1rem;
    line-height: 1.5;
}

.category-stats {
    display: flex;
    gap: 1rem;
    margin-bottom: 1rem;
    flex-wrap: wrap;
}

.category-stats span {
    display: flex;
    align-items: center;
    gap: 0.25rem;
    color: var(--dark-gray);
    font-size: 0.9rem;
}

.category-stats i {
    color: var(--primary-green);
}

@media (max-width: 768px) {
    .col-4 {
        flex: 0 0 100%;
        margin-bottom: 2rem;
    }
    
    .categories-grid {
        grid-template-columns: 1fr;
    }
    
    .category-item {
        flex-direction: column;
        text-align: center;
    }
    
    .category-stats {
        justify-content: center;
    }
}
</style>

<?php
/**
 * Get category icon based on category name
 */
function getCategoryIcon($categoryName) {
    $icons = [
        'Electronics' => 'laptop',
        'Clothing' => 'tshirt',
        'Food' => 'utensils',
        'Books' => 'book',
        'Home' => 'home',
        'Sports' => 'futbol',
        'Beauty' => 'spa',
        'Automotive' => 'car',
        'Health' => 'heartbeat',
        'Toys' => 'gamepad',
        'Jewelry' => 'gem',
        'Garden' => 'seedling'
    ];
    
    foreach ($icons as $category => $icon) {
        if (stripos($categoryName, $category) !== false) {
            return $icon;
        }
    }
    
    return 'tag'; // Default icon
}

require_once 'includes/footer.php';
?>
