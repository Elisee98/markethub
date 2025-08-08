<?php
/**
 * MarketHub Deals & Offers Page
 * Multi-Vendor E-Commerce Platform
 */

require_once 'config/config.php';

$page_title = 'Deals & Offers';

// Get featured deals (products with discounts)
$featured_deals_sql = "
    SELECT p.*, pi.image_url, c.name as category_name,
           u.username as vendor_name, vs.store_name,
           CASE
               WHEN p.original_price IS NOT NULL AND p.original_price > p.price
               THEN ROUND(((p.original_price - p.price) / p.original_price) * 100)
               ELSE 0
           END as discount_percentage
    FROM products p
    LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
    LEFT JOIN categories c ON p.category_id = c.id
    LEFT JOIN users u ON p.vendor_id = u.id
    LEFT JOIN vendor_stores vs ON u.id = vs.vendor_id
    WHERE p.status = 'active'
    AND p.stock_quantity > 0
    AND (p.original_price IS NULL OR p.original_price > p.price)
    ORDER BY discount_percentage DESC
    LIMIT 8
";

$featured_deals = $database->fetchAll($featured_deals_sql);

// Get flash deals (limited time offers)
$flash_deals_sql = "
    SELECT p.*, pi.image_url, c.name as category_name,
           u.username as vendor_name, vs.store_name,
           CASE
               WHEN p.original_price IS NOT NULL AND p.original_price > p.price
               THEN ROUND(((p.original_price - p.price) / p.original_price) * 100)
               ELSE 0
           END as discount_percentage
    FROM products p
    LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
    LEFT JOIN categories c ON p.category_id = c.id
    LEFT JOIN users u ON p.vendor_id = u.id
    LEFT JOIN vendor_stores vs ON u.id = vs.vendor_id
    WHERE p.status = 'active'
    AND p.stock_quantity > 0
    AND p.stock_quantity <= 10
    AND (p.original_price IS NULL OR p.original_price > p.price)
    ORDER BY p.stock_quantity ASC, discount_percentage DESC
    LIMIT 6
";

$flash_deals = $database->fetchAll($flash_deals_sql);

// Get category deals
$category_deals_sql = "
    SELECT c.id, c.name, COUNT(p.id) as deal_count,
           AVG(CASE
               WHEN p.original_price IS NOT NULL AND p.original_price > p.price
               THEN ROUND(((p.original_price - p.price) / p.original_price) * 100)
               ELSE 0
           END) as avg_discount
    FROM categories c
    JOIN products p ON c.id = p.category_id
    WHERE p.status = 'active'
    AND p.stock_quantity > 0
    AND (p.original_price IS NULL OR p.original_price > p.price)
    GROUP BY c.id
    HAVING deal_count > 0
    ORDER BY avg_discount DESC
    LIMIT 6
";

$category_deals = $database->fetchAll($category_deals_sql);

// Get vendor spotlight deals
$vendor_deals_sql = "
    SELECT u.id, u.username, vs.store_name, vs.description,
           COUNT(p.id) as deal_count,
           AVG(CASE
               WHEN p.original_price IS NOT NULL AND p.original_price > p.price
               THEN ROUND(((p.original_price - p.price) / p.original_price) * 100)
               ELSE 0
           END) as avg_discount,
           MIN(p.price) as min_price
    FROM users u
    JOIN vendor_stores vs ON u.id = vs.vendor_id
    JOIN products p ON u.id = p.vendor_id
    WHERE u.user_type = 'vendor' AND u.status = 'active'
    AND p.status = 'active'
    AND p.stock_quantity > 0
    AND (p.original_price IS NULL OR p.original_price > p.price)
    GROUP BY u.id
    HAVING deal_count >= 1
    ORDER BY avg_discount DESC
    LIMIT 4
";

$vendor_deals = $database->fetchAll($vendor_deals_sql);

require_once 'includes/header.php';
?>

<div class="container" style="margin: 2rem auto;">
    <!-- Header -->
    <div class="deals-header text-center mb-4">
        <h1 style="background: linear-gradient(45deg, var(--primary-green), var(--secondary-green)); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;">
            🔥 Deals & Offers
        </h1>
        <p style="font-size: 1.2rem; color: var(--dark-gray);">
            Discover amazing deals from local vendors in Musanze District
        </p>
        <div class="deal-timer" style="background: #FF5722; color: white; padding: 1rem; border-radius: var(--border-radius); display: inline-block; margin-top: 1rem;">
            <i class="fas fa-clock"></i> Limited Time Offers - Don't Miss Out!
        </div>
    </div>

    <!-- Flash Deals -->
    <?php if (!empty($flash_deals)): ?>
        <div class="section mb-5">
            <div class="section-header">
                <h2 style="color: #FF5722;">⚡ Flash Deals</h2>
                <p>Limited stock - grab them while they last!</p>
            </div>
            
            <div class="flash-deals-container">
                <div class="row">
                    <?php foreach ($flash_deals as $deal): ?>
                        <div class="col-4 mb-3">
                            <div class="deal-card flash-deal">
                                <div class="deal-badge">
                                    <?php echo $deal['discount_percentage']; ?>% OFF
                                </div>
                                <div class="stock-badge">
                                    Only <?php echo $deal['stock_quantity']; ?> left!
                                </div>
                                
                                <div class="deal-image">
                                    <img src="<?php echo $deal['image_url'] ?: 'assets/images/product-placeholder.png'; ?>" 
                                         alt="<?php echo htmlspecialchars($deal['name']); ?>"
                                         onerror="this.src='assets/images/product-placeholder.png'">
                                </div>
                                
                                <div class="deal-content">
                                    <h4><?php echo htmlspecialchars($deal['name']); ?></h4>
                                    <p class="vendor-name">by <?php echo htmlspecialchars($deal['store_name'] ?: $deal['vendor_name']); ?></p>
                                    
                                    <div class="price-section">
                                        <span class="current-price"><?php echo formatCurrency($deal['price']); ?></span>
                                        <?php if ($deal['original_price'] > $deal['price']): ?>
                                            <span class="original-price"><?php echo formatCurrency($deal['original_price']); ?></span>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="deal-actions">
                                        <a href="product.php?id=<?php echo $deal['id']; ?>" class="btn btn-primary btn-sm">
                                            View Deal
                                        </a>
                                        <button onclick="addToCart(<?php echo $deal['id']; ?>)" class="btn btn-outline btn-sm">
                                            <i class="fas fa-cart-plus"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Featured Deals -->
    <?php if (!empty($featured_deals)): ?>
        <div class="section mb-5">
            <div class="section-header">
                <h2 style="color: var(--primary-green);">🌟 Featured Deals</h2>
                <p>Best discounts from our top vendors</p>
            </div>
            
            <div class="featured-deals-grid">
                <?php foreach ($featured_deals as $deal): ?>
                    <div class="deal-card featured-deal">
                        <div class="deal-badge">
                            <?php echo $deal['discount_percentage']; ?>% OFF
                        </div>
                        
                        <div class="deal-image">
                            <img src="<?php echo $deal['image_url'] ?: 'assets/images/product-placeholder.png'; ?>" 
                                 alt="<?php echo htmlspecialchars($deal['name']); ?>"
                                 onerror="this.src='assets/images/product-placeholder.png'">
                        </div>
                        
                        <div class="deal-content">
                            <span class="category-tag"><?php echo htmlspecialchars($deal['category_name']); ?></span>
                            <h4><?php echo htmlspecialchars($deal['name']); ?></h4>
                            <p class="vendor-name">by <?php echo htmlspecialchars($deal['store_name'] ?: $deal['vendor_name']); ?></p>
                            
                            <div class="price-section">
                                <span class="current-price"><?php echo formatCurrency($deal['price']); ?></span>
                                <?php if ($deal['original_price'] > $deal['price']): ?>
                                    <span class="original-price"><?php echo formatCurrency($deal['original_price']); ?></span>
                                    <span class="savings">Save <?php echo formatCurrency($deal['original_price'] - $deal['price']); ?></span>
                                <?php endif; ?>
                            </div>
                            
                            <div class="deal-actions">
                                <a href="product.php?id=<?php echo $deal['id']; ?>" class="btn btn-primary">
                                    View Deal
                                </a>
                                <button onclick="addToCart(<?php echo $deal['id']; ?>)" class="btn btn-outline">
                                    Add to Cart
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Category Deals -->
    <?php if (!empty($category_deals)): ?>
        <div class="section mb-5">
            <div class="section-header">
                <h2 style="color: var(--secondary-green);">🏷️ Deals by Category</h2>
                <p>Explore discounts in your favorite categories</p>
            </div>
            
            <div class="category-deals-grid">
                <?php foreach ($category_deals as $category): ?>
                    <div class="category-deal-card">
                        <div class="category-deal-content">
                            <h4><?php echo htmlspecialchars($category['name']); ?></h4>
                            <p><?php echo number_format($category['deal_count']); ?> deals available</p>
                            <div class="avg-discount">
                                Average <?php echo round($category['avg_discount']); ?>% OFF
                            </div>
                            <a href="products.php?category=<?php echo $category['id']; ?>&deals=1" class="btn btn-outline">
                                Browse Deals
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Vendor Spotlight -->
    <?php if (!empty($vendor_deals)): ?>
        <div class="section mb-5">
            <div class="section-header">
                <h2 style="color: #d3b013ff;">🏪 Vendor Spotlight</h2>
                <p>Top vendors with the best deals</p>
            </div>
            
            <div class="vendor-deals-grid">
                <?php foreach ($vendor_deals as $vendor): ?>
                    <div class="vendor-deal-card">
                        <div class="vendor-deal-header">
                            <h4><?php echo htmlspecialchars($vendor['store_name'] ?: $vendor['username']); ?></h4>
                            <div class="vendor-stats">
                                <span class="deal-count"><?php echo $vendor['deal_count']; ?> deals</span>
                                <span class="avg-discount">Avg <?php echo round($vendor['avg_discount']); ?>% OFF</span>
                            </div>
                        </div>
                        
                        <div class="vendor-deal-content">
                            <?php if ($vendor['description']): ?>
                                <p><?php echo htmlspecialchars(substr($vendor['description'], 0, 100)); ?>...</p>
                            <?php endif; ?>
                            <div class="price-info">
                                Deals starting from <?php echo formatCurrency($vendor['min_price']); ?>
                            </div>
                        </div>
                        
                        <div class="vendor-deal-actions">
                            <a href="vendor.php?id=<?php echo $vendor['id']; ?>" class="btn btn-primary">
                                View Store
                            </a>
                            <a href="products.php?vendor=<?php echo $vendor['id']; ?>&deals=1" class="btn btn-outline">
                                View Deals
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Deal Newsletter -->
    <div class="section">
        <div class="newsletter-section">
            <div class="newsletter-content">
                <h3>Never Miss a Deal!</h3>
                <p>Subscribe to our newsletter and get notified about the latest deals and offers from Musanze District vendors.</p>
                
                <form class="newsletter-form" onsubmit="subscribeNewsletter(event)">
                    <div class="form-group">
                        <input type="email" class="form-control" placeholder="Enter your email address" required>
                        <button type="submit" class="btn btn-primary">
                            Subscribe
                        </button>
                    </div>
                </form>
                
                <div class="newsletter-benefits">
                    <div class="benefit">
                        <i class="fas fa-bell"></i>
                        <span>Instant deal alerts</span>
                    </div>
                    <div class="benefit">
                        <i class="fas fa-percent"></i>
                        <span>Exclusive discounts</span>
                    </div>
                    <div class="benefit">
                        <i class="fas fa-gift"></i>
                        <span>Special offers</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.deals-header {
    background: linear-gradient(135deg, #f8f9fa, #e9ecef);
    padding: 3rem 2rem;
    border-radius: var(--border-radius);
    margin-bottom: 3rem;
}

.section-header {
    text-align: center;
    margin-bottom: 2rem;
}

.section-header h2 {
    margin-bottom: 0.5rem;
}

.section-header p {
    color: var(--dark-gray);
    font-size: 1.1rem;
}

.deal-card {
    background: white;
    border-radius: var(--border-radius);
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    overflow: hidden;
    transition: var(--transition);
    position: relative;
    height: 100%;
}

.deal-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.15);
}

.deal-badge {
    position: absolute;
    top: 1rem;
    right: 1rem;
    background: #FF5722;
    color: white;
    padding: 0.5rem 1rem;
    border-radius: 20px;
    font-weight: bold;
    font-size: 0.9rem;
    z-index: 2;
}

.stock-badge {
    position: absolute;
    top: 1rem;
    left: 1rem;
    background: #FF9800;
    color: white;
    padding: 0.25rem 0.75rem;
    border-radius: 15px;
    font-size: 0.8rem;
    font-weight: bold;
    z-index: 2;
}

.deal-image {
    height: 200px;
    overflow: hidden;
}

.deal-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: var(--transition);
}

.deal-card:hover .deal-image img {
    transform: scale(1.1);
}

.deal-content {
    padding: 1.5rem;
}

.category-tag {
    background: var(--light-gray);
    color: var(--dark-gray);
    padding: 0.25rem 0.75rem;
    border-radius: 15px;
    font-size: 0.8rem;
    display: inline-block;
    margin-bottom: 0.5rem;
}

.deal-content h4 {
    margin-bottom: 0.5rem;
    font-size: 1.1rem;
}

.vendor-name {
    color: var(--dark-gray);
    font-size: 0.9rem;
    margin-bottom: 1rem;
}

.price-section {
    margin-bottom: 1.5rem;
}

.current-price {
    font-size: 1.3rem;
    font-weight: bold;
    color: var(--primary-green);
}

.original-price {
    text-decoration: line-through;
    color: var(--medium-gray);
    margin-left: 0.5rem;
}

.savings {
    display: block;
    color: #FF5722;
    font-size: 0.9rem;
    font-weight: 600;
    margin-top: 0.25rem;
}

.deal-actions {
    display: flex;
    gap: 0.5rem;
}

.featured-deals-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 2rem;
}

.category-deals-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1.5rem;
}

.category-deal-card {
    background: white;
    border-radius: var(--border-radius);
    padding: 2rem;
    text-align: center;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    transition: var(--transition);
}

.category-deal-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 4px 20px rgba(0,0,0,0.15);
}

.avg-discount {
    background: var(--secondary-green);
    color: white;
    padding: 0.5rem 1rem;
    border-radius: 20px;
    font-weight: bold;
    margin: 1rem 0;
    display: inline-block;
}

.vendor-deals-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 2rem;
}

.vendor-deal-card {
    background: white;
    border-radius: var(--border-radius);
    padding: 2rem;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    transition: var(--transition);
}

.vendor-deal-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 4px 20px rgba(0,0,0,0.15);
}

.vendor-deal-header {
    margin-bottom: 1rem;
}

.vendor-stats {
    display: flex;
    gap: 1rem;
    margin-top: 0.5rem;
}

.vendor-stats span {
    background: var(--light-gray);
    padding: 0.25rem 0.75rem;
    border-radius: 15px;
    font-size: 0.8rem;
}

.newsletter-section {
    background: linear-gradient(135deg, var(--primary-green), var(--secondary-green));
    color: white;
    padding: 3rem;
    border-radius: var(--border-radius);
    text-align: center;
}

.newsletter-form {
    max-width: 400px;
    margin: 2rem auto;
}

.newsletter-form .form-group {
    display: flex;
    gap: 0.5rem;
}

.newsletter-benefits {
    display: flex;
    justify-content: center;
    gap: 2rem;
    margin-top: 2rem;
}

.benefit {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

@media (max-width: 768px) {
    .col-4 {
        flex: 0 0 100%;
        margin-bottom: 2rem;
    }
    
    .featured-deals-grid,
    .vendor-deals-grid {
        grid-template-columns: 1fr;
    }
    
    .category-deals-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .newsletter-benefits {
        flex-direction: column;
        gap: 1rem;
    }
    
    .newsletter-form .form-group {
        flex-direction: column;
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
            updateCartCount(data.count);
        } else {
            showNotification(data.message || 'Error adding to cart', 'error');
        }
    })
    .catch(error => {
        showNotification('Error adding to cart', 'error');
    });
}

function subscribeNewsletter(event) {
    event.preventDefault();
    const email = event.target.querySelector('input[type="email"]').value;
    
    // Simulate newsletter subscription
    showNotification('Thank you for subscribing to our newsletter!', 'success');
    event.target.reset();
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

function updateCartCount(count) {
    const cartBadge = document.querySelector('.cart-count');
    if (cartBadge) {
        cartBadge.textContent = count;
    }
}
</script>

<?php require_once 'includes/footer.php'; ?>
