<?php
/**
 * MarketHub - Responsive Landing Page
 * Modern, mobile-first design
 */

require_once 'config/config.php';

$page_title = 'Home';
$page_description = 'MarketHub - Your premier multi-vendor marketplace in Musanze District. Shop from local vendors, compare prices, and enjoy secure checkout.';

// Get featured products
$featured_products = $database->fetchAll("
    SELECT p.id, p.name, p.price, p.compare_price, p.image_url, p.stock_quantity,
           u.username as vendor_name, vs.store_name, c.name as category_name,
           AVG(pr.rating) as avg_rating, COUNT(pr.id) as review_count
    FROM products p
    INNER JOIN users u ON p.vendor_id = u.id
    LEFT JOIN vendor_stores vs ON u.id = vs.vendor_id
    LEFT JOIN categories c ON p.category_id = c.id
    LEFT JOIN product_reviews pr ON p.id = pr.product_id AND pr.status = 'approved'
    WHERE p.status = 'active' AND u.status = 'active' AND u.user_type = 'vendor' AND p.stock_quantity > 0
    GROUP BY p.id ORDER BY p.created_at DESC LIMIT 8
");

// Get categories with product counts
$categories = $database->fetchAll("
    SELECT c.*, COUNT(p.id) as product_count
    FROM categories c
    LEFT JOIN products p ON c.id = p.category_id AND p.status = 'active'
    WHERE c.parent_id IS NULL AND c.status = 'active'
    GROUP BY c.id ORDER BY c.sort_order, c.name LIMIT 8
");

// Get platform statistics
$stats = [
    'products' => $database->fetch("SELECT COUNT(*) as count FROM products WHERE status = 'active'")['count'],
    'vendors' => $database->fetch("SELECT COUNT(*) as count FROM users WHERE user_type = 'vendor' AND status = 'active'")['count'],
    'orders' => $database->fetch("SELECT COUNT(*) as count FROM orders WHERE payment_status = 'paid'")['count'],
    'customers' => $database->fetch("SELECT COUNT(*) as count FROM users WHERE user_type = 'customer' AND status = 'active'")['count']
];

require_once 'includes/header.php';
?>

<style>
/* Modern Responsive Styles */
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
}

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
    line-height: 1.6;
    color: var(--text-dark);
}

/* Hero Section */
.hero-section {
    background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
    min-height: 100vh;
    display: flex;
    align-items: center;
    position: relative;
    overflow: hidden;
}

.hero-section::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grid" width="10" height="10" patternUnits="userSpaceOnUse"><path d="M 10 0 L 0 0 0 10" fill="none" stroke="rgba(255,255,255,0.1)" stroke-width="0.5"/></pattern></defs><rect width="100" height="100" fill="url(%23grid)"/></svg>');
    opacity: 0.3;
}

.hero-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 2rem;
    position: relative;
    z-index: 1;
}

.hero-content {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 4rem;
    align-items: center;
}

.hero-title {
    font-size: clamp(2.5rem, 5vw, 4rem);
    font-weight: 800;
    color: white;
    margin-bottom: 1.5rem;
    line-height: 1.1;
}

.brand-highlight {
    background: linear-gradient(45deg, var(--accent-color), #fbbf24);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.hero-subtitle {
    font-size: 1.25rem;
    color: rgba(255, 255, 255, 0.9);
    margin-bottom: 2rem;
    line-height: 1.6;
}

.hero-stats {
    display: flex;
    gap: 2rem;
    margin-bottom: 3rem;
}

.stat-item {
    text-align: center;
}

.stat-number {
    display: block;
    font-size: 2rem;
    font-weight: 700;
    color: var(--accent-color);
}

.stat-label {
    font-size: 0.9rem;
    color: rgba(255, 255, 255, 0.8);
}

.hero-actions {
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
}

.btn {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 1rem 2rem;
    border-radius: 12px;
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

.btn-outline {
    background: transparent;
    color: white;
    border-color: rgba(255, 255, 255, 0.3);
}

.btn-outline:hover {
    background: rgba(255, 255, 255, 0.1);
    border-color: white;
}

.btn-lg {
    padding: 1.25rem 2.5rem;
    font-size: 1.1rem;
}

/* Hero Image */
.hero-image {
    position: relative;
}

.hero-image-container {
    position: relative;
    max-width: 500px;
    margin: 0 auto;
}

.hero-image img {
    width: 100%;
    height: auto;
    border-radius: 20px;
    box-shadow: var(--shadow-lg);
}

.hero-image-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    pointer-events: none;
}

.floating-card {
    position: absolute;
    background: white;
    padding: 1rem 1.5rem;
    border-radius: 12px;
    box-shadow: var(--shadow);
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-weight: 600;
    color: var(--text-dark);
    animation: float 3s ease-in-out infinite;
}

.floating-card:nth-child(1) {
    top: 20%;
    right: -10%;
    animation-delay: 0s;
}

.floating-card:nth-child(2) {
    bottom: 30%;
    left: -10%;
    animation-delay: 1s;
}

.floating-card:nth-child(3) {
    top: 60%;
    right: 10%;
    animation-delay: 2s;
}

@keyframes float {
    0%, 100% { transform: translateY(0px); }
    50% { transform: translateY(-10px); }
}

/* Search Section */
.search-section {
    padding: 4rem 2rem;
    background: var(--bg-light);
}

.search-container {
    max-width: 800px;
    margin: 0 auto;
    text-align: center;
}

.search-title {
    font-size: 2.5rem;
    font-weight: 700;
    color: var(--text-dark);
    margin-bottom: 1rem;
}

.search-subtitle {
    font-size: 1.1rem;
    color: var(--text-light);
    margin-bottom: 3rem;
}

.search-form {
    margin-bottom: 2rem;
}

.search-input-group {
    display: flex;
    max-width: 600px;
    margin: 0 auto 2rem;
    box-shadow: var(--shadow-lg);
    border-radius: 16px;
    overflow: hidden;
    background: white;
}

.search-input {
    flex: 1;
    padding: 1.5rem;
    border: none;
    font-size: 1rem;
    outline: none;
}

.search-button {
    padding: 1.5rem 2rem;
    background: var(--primary-color);
    color: white;
    border: none;
    cursor: pointer;
    font-weight: 600;
    transition: background 0.3s;
}

.search-button:hover {
    background: var(--secondary-color);
}

.search-suggestions {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 1rem;
    flex-wrap: wrap;
}

.suggestion-label {
    color: var(--text-light);
    font-weight: 500;
}

.suggestion-tag {
    padding: 0.5rem 1rem;
    background: white;
    color: var(--text-dark);
    text-decoration: none;
    border-radius: 20px;
    font-size: 0.9rem;
    transition: all 0.3s;
    box-shadow: var(--shadow);
}

.suggestion-tag:hover {
    background: var(--primary-color);
    color: white;
    transform: translateY(-2px);
}

/* Categories Section */
.categories-section {
    padding: 4rem 2rem;
    background: white;
}

.categories-container {
    max-width: 1200px;
    margin: 0 auto;
}

.section-header {
    text-align: center;
    margin-bottom: 3rem;
}

.section-title {
    font-size: 2.5rem;
    font-weight: 700;
    color: var(--text-dark);
    margin-bottom: 1rem;
}

.section-subtitle {
    font-size: 1.1rem;
    color: var(--text-light);
}

.categories-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 2rem;
    margin-bottom: 3rem;
}

.category-card {
    background: white;
    padding: 2rem;
    border-radius: 16px;
    text-decoration: none;
    color: inherit;
    box-shadow: var(--shadow);
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.category-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, var(--primary-color), var(--accent-color));
    transform: scaleX(0);
    transition: transform 0.3s;
}

.category-card:hover {
    transform: translateY(-5px);
    box-shadow: var(--shadow-lg);
}

.category-card:hover::before {
    transform: scaleX(1);
}

.category-icon {
    font-size: 3rem;
    margin-bottom: 1rem;
    text-align: center;
}

.category-name {
    font-size: 1.25rem;
    font-weight: 600;
    margin-bottom: 0.5rem;
    color: var(--text-dark);
}

.category-count {
    color: var(--text-light);
    font-size: 0.9rem;
    margin-bottom: 1rem;
}

.category-arrow {
    color: var(--primary-color);
    transition: transform 0.3s;
}

.category-card:hover .category-arrow {
    transform: translateX(5px);
}

.categories-footer {
    text-align: center;
}

/* Responsive Design */
@media (max-width: 768px) {
    .hero-content {
        grid-template-columns: 1fr;
        gap: 2rem;
        text-align: center;
    }
    
    .hero-stats {
        justify-content: center;
    }
    
    .hero-actions {
        justify-content: center;
    }
    
    .search-input-group {
        flex-direction: column;
    }
    
    .search-button {
        border-radius: 0 0 16px 16px;
    }
    
    .search-input {
        border-radius: 16px 16px 0 0;
    }
    
    .categories-grid {
        grid-template-columns: 1fr;
    }
    
    .floating-card {
        display: none;
    }
}

@media (max-width: 480px) {
    .hero-container {
        padding: 1rem;
    }
    
    .search-section {
        padding: 2rem 1rem;
    }
    
    .categories-section {
        padding: 2rem 1rem;
    }
    
    .hero-stats {
        gap: 1rem;
    }
    
    .stat-number {
        font-size: 1.5rem;
    }
}
</style>

<!-- Hero Section -->
<section class="hero-section">
    <div class="hero-container">
        <div class="hero-content">
            <div class="hero-text">
                <h1 class="hero-title">
                    Welcome to <span class="brand-highlight">MarketHub</span>
                </h1>
                <p class="hero-subtitle">
                    Your premier multi-vendor marketplace connecting you with the best local sellers in Musanze District
                </p>
                <div class="hero-stats">
                    <div class="stat-item">
                        <span class="stat-number"><?php echo number_format($stats['products']); ?>+</span>
                        <span class="stat-label">Products</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-number"><?php echo number_format($stats['vendors']); ?>+</span>
                        <span class="stat-label">Vendors</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-number"><?php echo number_format($stats['customers']); ?>+</span>
                        <span class="stat-label">Customers</span>
                    </div>
                </div>
                <div class="hero-actions">
                    <a href="products.php" class="btn btn-primary btn-lg">
                        <i class="fas fa-shopping-bag"></i> Shop Now
                    </a>
                    <a href="vendor/register.php" class="btn btn-outline btn-lg">
                        <i class="fas fa-store"></i> Become a Vendor
                    </a>
                </div>
            </div>
            <div class="hero-image">
                <div class="hero-image-container">
                    <img src="assets/images/hero-marketplace.png" alt="MarketHub Marketplace" onerror="this.style.display='none'">
                    <div class="hero-image-overlay">
                        <div class="floating-card">
                            <i class="fas fa-shield-check" style="color: var(--primary-color);"></i>
                            <span>Secure Shopping</span>
                        </div>
                        <div class="floating-card">
                            <i class="fas fa-truck-fast" style="color: var(--primary-color);"></i>
                            <span>Fast Delivery</span>
                        </div>
                        <div class="floating-card">
                            <i class="fas fa-star" style="color: #fbbf24;"></i>
                            <span>Top Rated</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Search Section -->
<section class="search-section">
    <div class="search-container">
        <div class="search-content">
            <h2 class="search-title">Find What You're Looking For</h2>
            <p class="search-subtitle">Search through thousands of products from local vendors</p>
            <form action="search.php" method="GET" class="search-form">
                <div class="search-input-group">
                    <input type="text" 
                           name="q" 
                           placeholder="Search products, vendors, categories..." 
                           class="search-input"
                           value="<?php echo htmlspecialchars($_GET['q'] ?? ''); ?>">
                    <button type="submit" class="search-button">
                        <i class="fas fa-search"></i>
                        <span class="search-button-text">Search</span>
                    </button>
                </div>
                <div class="search-suggestions">
                    <span class="suggestion-label">Popular:</span>
                    <a href="search.php?q=smartphones" class="suggestion-tag">Smartphones</a>
                    <a href="search.php?q=fashion" class="suggestion-tag">Fashion</a>
                    <a href="search.php?q=electronics" class="suggestion-tag">Electronics</a>
                    <a href="search.php?q=home" class="suggestion-tag">Home & Garden</a>
                </div>
            </form>
        </div>
    </div>
</section>

<!-- Categories Section -->
<section class="categories-section">
    <div class="categories-container">
        <div class="section-header">
            <h2 class="section-title">Shop by Category</h2>
            <p class="section-subtitle">Discover products organized by categories</p>
        </div>
        <div class="categories-grid">
            <?php foreach ($categories as $category): ?>
                <a href="products.php?category=<?php echo $category['id']; ?>" class="category-card">
                    <div class="category-icon">
                        <?php
                        $category_icons = [
                            'Electronics' => '📱',
                            'Fashion' => '👕', 
                            'Home & Garden' => '🏠',
                            'Sports' => '⚽',
                            'Books' => '📚',
                            'Health' => '💊',
                            'Beauty' => '💄',
                            'Automotive' => '🚗'
                        ];
                        echo $category_icons[$category['name']] ?? '🛍️';
                        ?>
                    </div>
                    <h3 class="category-name"><?php echo htmlspecialchars($category['name']); ?></h3>
                    <p class="category-count"><?php echo number_format($category['product_count']); ?> products</p>
                    <div class="category-arrow">
                        <i class="fas fa-arrow-right"></i>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
        <div class="categories-footer">
            <a href="categories.php" class="btn btn-outline">
                <i class="fas fa-th-large"></i> View All Categories
            </a>
        </div>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>
