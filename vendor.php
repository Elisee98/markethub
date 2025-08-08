<?php
/**
 * Individual Vendor Profile Page
 */

require_once 'config/config.php';

$vendor_id = intval($_GET['id'] ?? 0);

if (!$vendor_id) {
    header('Location: vendors.php');
    exit;
}

// Get vendor details
$vendor = $database->fetch(
    "SELECT u.id, u.username, u.first_name, u.last_name, u.email, u.phone,
            vs.store_name, vs.store_description, vs.logo_url, vs.store_logo,
            vs.banner_url, vs.store_banner, vs.address, vs.business_hours,
            vs.website, vs.phone as store_phone, vs.email as store_email
     FROM users u
     LEFT JOIN vendor_stores vs ON u.id = vs.vendor_id
     WHERE u.id = ? AND u.user_type = 'vendor' AND u.status = 'active'",
    [$vendor_id]
);

if (!$vendor) {
    header('Location: vendors.php?error=vendor_not_found');
    exit;
}

// Get vendor products
$products = $database->fetchAll(
    "SELECT p.*, pi.image_url,
            AVG(pr.rating) as avg_rating, 
            COUNT(pr.id) as review_count
     FROM products p
     LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
     LEFT JOIN product_reviews pr ON p.id = pr.product_id AND pr.status = 'approved'
     WHERE p.vendor_id = ? AND p.status = 'active'
     GROUP BY p.id
     ORDER BY p.created_at DESC
     LIMIT 12",
    [$vendor_id]
);

// Get vendor stats
$stats = [
    'total_products' => $database->fetch("SELECT COUNT(*) as count FROM products WHERE vendor_id = ? AND status = 'active'", [$vendor_id])['count'],
    'total_reviews' => $database->fetch("SELECT COUNT(*) as count FROM product_reviews pr JOIN products p ON pr.product_id = p.id WHERE p.vendor_id = ? AND pr.status = 'approved'", [$vendor_id])['count'],
    'avg_rating' => $database->fetch("SELECT AVG(pr.rating) as avg FROM product_reviews pr JOIN products p ON pr.product_id = p.id WHERE p.vendor_id = ? AND pr.status = 'approved'", [$vendor_id])['avg'] ?? 0
];

$page_title = ($vendor['store_name'] ?: $vendor['username']) . ' - Vendor Profile';
require_once 'includes/header.php';
?>

<div class="container" style="padding: 2rem 0;">
    <!-- Vendor Header -->
    <div class="vendor-header" style="background: white; padding: 2rem; border-radius: 10px; margin-bottom: 2rem; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
        <div class="row align-items-center">
            <div class="col-3">
                <div class="vendor-logo" style="text-align: center;">
                    <?php
                    $logo = $vendor['logo_url'] ?: $vendor['store_logo'];
                    if ($logo): ?>
                        <img src="<?php echo htmlspecialchars($logo); ?>"
                             alt="<?php echo htmlspecialchars($vendor['store_name'] ?: $vendor['username']); ?>"
                             style="width: 120px; height: 120px; object-fit: cover; border-radius: 50%; border: 3px solid #e5e7eb;">
                    <?php else: ?>
                        <div style="width: 120px; height: 120px; background: #f3f4f6; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto;">
                            <i class="fas fa-store" style="font-size: 3rem; color: #9ca3af;"></i>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="col-9">
                <h1 style="margin-bottom: 0.5rem;"><?php echo htmlspecialchars($vendor['store_name'] ?: $vendor['username']); ?></h1>
                
                <?php if ($vendor['store_description']): ?>
                    <p style="color: #6b7280; margin-bottom: 1rem;"><?php echo htmlspecialchars($vendor['store_description']); ?></p>
                <?php endif; ?>
                
                <div style="display: flex; gap: 2rem; margin-bottom: 1rem;">
                    <div>
                        <strong><?php echo $stats['total_products']; ?></strong>
                        <span style="color: #6b7280;">Products</span>
                    </div>
                    <div>
                        <strong><?php echo $stats['total_reviews']; ?></strong>
                        <span style="color: #6b7280;">Reviews</span>
                    </div>
                    <?php if ($stats['avg_rating'] > 0): ?>
                    <div>
                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                            <div class="rating">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <i class="fas fa-star" style="color: <?php echo $i <= round($stats['avg_rating']) ? '#fbbf24' : '#e5e7eb'; ?>;"></i>
                                <?php endfor; ?>
                            </div>
                            <span style="color: #6b7280;"><?php echo number_format($stats['avg_rating'], 1); ?></span>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                
                <div style="display: flex; gap: 1rem;">
                    <?php
                    $phone = $vendor['store_phone'] ?: $vendor['phone'];
                    $email = $vendor['store_email'] ?: $vendor['email'];
                    ?>
                    <?php if ($phone): ?>
                        <a href="tel:<?php echo htmlspecialchars($phone); ?>" class="btn btn-outline">
                            <i class="fas fa-phone"></i> Call
                        </a>
                    <?php endif; ?>
                    <?php if ($email): ?>
                        <a href="mailto:<?php echo htmlspecialchars($email); ?>" class="btn btn-outline">
                            <i class="fas fa-envelope"></i> Email
                        </a>
                    <?php endif; ?>
                    <?php if ($vendor['website']): ?>
                        <a href="<?php echo htmlspecialchars($vendor['website']); ?>" target="_blank" class="btn btn-outline">
                            <i class="fas fa-globe"></i> Website
                        </a>
                    <?php endif; ?>
                    <a href="products.php?vendor=<?php echo $vendor['id']; ?>" class="btn btn-primary">
                        <i class="fas fa-eye"></i> View All Products
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Vendor Info -->
    <?php if ((isset($vendor['address']) && $vendor['address']) || (isset($vendor['business_hours']) && $vendor['business_hours'])): ?>
    <div class="vendor-info" style="background: white; padding: 2rem; border-radius: 10px; margin-bottom: 2rem; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
        <h3 style="margin-bottom: 1rem;">Store Information</h3>
        <div class="row">
            <?php if (isset($vendor['address']) && $vendor['address']): ?>
            <div class="col-6">
                <h4><i class="fas fa-map-marker-alt"></i> Address</h4>
                <p style="color: #6b7280;"><?php echo nl2br(htmlspecialchars($vendor['address'])); ?></p>
            </div>
            <?php endif; ?>

            <?php if (isset($vendor['business_hours']) && $vendor['business_hours']): ?>
            <div class="col-6">
                <h4><i class="fas fa-clock"></i> Business Hours</h4>
                <p style="color: #6b7280;"><?php echo nl2br(htmlspecialchars($vendor['business_hours'])); ?></p>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Products -->
    <div class="vendor-products">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
            <h3>Products</h3>
            <a href="products.php?vendor=<?php echo $vendor_id; ?>" class="btn btn-outline">View All</a>
        </div>
        
        <?php if (empty($products)): ?>
            <div style="text-align: center; padding: 3rem; color: #6b7280;">
                <i class="fas fa-box-open" style="font-size: 3rem; margin-bottom: 1rem;"></i>
                <h3>No products available</h3>
                <p>This vendor hasn't added any products yet.</p>
            </div>
        <?php else: ?>
            <div class="products-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 1.5rem;">
                <?php foreach ($products as $product): ?>
                    <div class="product-card" style="background: white; border-radius: 10px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.1); transition: transform 0.2s;">
                        <div class="product-image" style="height: 200px; overflow: hidden;">
                            <?php if ($product['image_url']): ?>
                                <img src="<?php echo htmlspecialchars($product['image_url']); ?>" 
                                     alt="<?php echo htmlspecialchars($product['name']); ?>"
                                     style="width: 100%; height: 100%; object-fit: cover;">
                            <?php else: ?>
                                <div style="width: 100%; height: 100%; background: #f3f4f6; display: flex; align-items: center; justify-content: center;">
                                    <i class="fas fa-image" style="font-size: 2rem; color: #9ca3af;"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div style="padding: 1rem;">
                            <h4 style="margin-bottom: 0.5rem; font-size: 1rem;">
                                <a href="product.php?id=<?php echo $product['id']; ?>" style="text-decoration: none; color: #1f2937;">
                                    <?php echo htmlspecialchars($product['name']); ?>
                                </a>
                            </h4>
                            
                            <?php if ($product['avg_rating']): ?>
                                <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem;">
                                    <div class="rating">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <i class="fas fa-star" style="color: <?php echo $i <= round($product['avg_rating']) ? '#fbbf24' : '#e5e7eb'; ?>; font-size: 0.875rem;"></i>
                                        <?php endfor; ?>
                                    </div>
                                    <span style="color: #6b7280; font-size: 0.875rem;">(<?php echo $product['review_count']; ?>)</span>
                                </div>
                            <?php endif; ?>
                            
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <span style="font-size: 1.25rem; font-weight: 600; color: #10b981;">
                                    RWF <?php echo number_format($product['price'], 2); ?>
                                </span>
                                <a href="product.php?id=<?php echo $product['id']; ?>" class="btn btn-sm btn-primary">View</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
.product-card:hover {
    transform: translateY(-2px);
}

.btn-sm {
    padding: 0.5rem 1rem;
    font-size: 0.875rem;
}
</style>

<?php require_once 'includes/footer.php'; ?>
