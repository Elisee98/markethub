<?php
/**
 * MarketHub - Vendors Directory
 */

require_once 'config/config.php';

// Get all active vendors with complete information
$vendors = $database->fetchAll(
    "SELECT u.id, u.username, u.first_name, u.last_name, u.email, u.phone,
            vs.store_name, vs.store_description, vs.logo_url, vs.store_logo,
            vs.address, vs.phone as store_phone, vs.website,
            COUNT(p.id) as product_count
     FROM users u
     LEFT JOIN vendor_stores vs ON u.id = vs.vendor_id
     LEFT JOIN products p ON u.id = p.vendor_id AND p.status = 'active'
     WHERE u.user_type = 'vendor' AND u.status = 'active'
     GROUP BY u.id
     HAVING product_count > 0
     ORDER BY product_count DESC"
);

$page_title = 'Our Vendors';
require_once 'includes/header.php';
?>

<div class="container" style="padding: 2rem 0;">
    <!-- Page Header -->
    <div class="page-header" style="text-align: center; margin-bottom: 3rem;">
        <h1 style="font-size: 2.5rem; margin-bottom: 1rem; color: #1f2937;">Our Vendors</h1>
        <p style="font-size: 1.125rem; color: #6b7280; max-width: 600px; margin: 0 auto;">
            Discover amazing products from our trusted vendor partners across Rwanda
        </p>
    </div>

    <!-- Vendors Grid -->
    <?php if (empty($vendors)): ?>
        <div style="text-align: center; padding: 4rem 0; color: #6b7280;">
            <i class="fas fa-store" style="font-size: 4rem; margin-bottom: 1rem; color: #d1d5db;"></i>
            <h3>No vendors found</h3>
            <p>We're working on bringing you amazing vendors. Check back soon!</p>
        </div>
    <?php else: ?>
        <div class="vendors-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)); gap: 2rem;">
            <?php foreach ($vendors as $vendor): ?>
                <div class="vendor-card" style="background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,0.1); transition: transform 0.3s, box-shadow 0.3s;">
                    <!-- Vendor Banner -->
                    <div class="vendor-banner" style="height: 120px; background: linear-gradient(135deg, #10b981, #059669); position: relative;">
                        <!-- Simple gradient background, no banner image for now -->

                        <!-- Vendor Logo -->
                        <div style="position: absolute; bottom: -30px; left: 20px;">
                            <?php
                            $logo = $vendor['logo_url'] ?: $vendor['store_logo'];
                            if ($logo): ?>
                                <img src="<?php echo htmlspecialchars($logo); ?>"
                                     alt="<?php echo htmlspecialchars($vendor['store_name'] ?: $vendor['username']); ?>"
                                     style="width: 60px; height: 60px; border-radius: 50%; border: 3px solid white; object-fit: cover; background: white;">
                            <?php else: ?>
                                <div style="width: 60px; height: 60px; border-radius: 50%; border: 3px solid white; background: #f3f4f6; display: flex; align-items: center; justify-content: center;">
                                    <i class="fas fa-store" style="color: #9ca3af; font-size: 1.5rem;"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Vendor Info -->
                    <div style="padding: 2rem 1.5rem 1.5rem;">
                        <div style="margin-top: 10px;">
                            <h3 style="margin-bottom: 0.5rem; font-size: 1.25rem;">
                                <a href="vendor.php?id=<?php echo $vendor['id']; ?>" style="text-decoration: none; color: #1f2937;">
                                    <?php echo htmlspecialchars($vendor['store_name'] ?: $vendor['username']); ?>
                                </a>
                            </h3>
                            
                            <?php if ($vendor['store_description']): ?>
                                <p style="color: #6b7280; margin-bottom: 1rem; font-size: 0.875rem; line-height: 1.5;">
                                    <?php echo htmlspecialchars(substr($vendor['store_description'], 0, 100)); ?>
                                    <?php if (strlen($vendor['store_description']) > 100): ?>...<?php endif; ?>
                                </p>
                            <?php endif; ?>

                            <!-- Stats -->
                            <div style="display: flex; gap: 1rem; margin-bottom: 1rem; font-size: 0.875rem;">
                                <div>
                                    <strong style="color: #10b981;"><?php echo $vendor['product_count']; ?></strong>
                                    <span style="color: #6b7280;">Products</span>
                                </div>
                            </div>

                            <!-- Contact Info -->
                            <?php
                            $phone = $vendor['store_phone'] ?: $vendor['phone'];
                            if ($phone): ?>
                                <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem; font-size: 0.875rem; color: #6b7280;">
                                    <i class="fas fa-phone"></i>
                                    <span><?php echo htmlspecialchars($phone); ?></span>
                                </div>
                            <?php endif; ?>

                            <?php if ($vendor['address']): ?>
                                <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem; font-size: 0.875rem; color: #6b7280;">
                                    <i class="fas fa-map-marker-alt"></i>
                                    <span><?php echo htmlspecialchars(substr($vendor['address'], 0, 50)); ?>...</span>
                                </div>
                            <?php endif; ?>

                            <?php if ($vendor['website']): ?>
                                <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 1rem; font-size: 0.875rem; color: #6b7280;">
                                    <i class="fas fa-globe"></i>
                                    <a href="<?php echo htmlspecialchars($vendor['website']); ?>" target="_blank" style="color: #10b981; text-decoration: none;">Visit Website</a>
                                </div>
                            <?php endif; ?>

                            <!-- Actions -->
                            <div style="display: flex; gap: 0.5rem;">
                                <a href="vendor.php?id=<?php echo $vendor['id']; ?>" class="btn btn-primary" style="flex: 1; text-align: center;">
                                    <i class="fas fa-eye"></i> View Store
                                </a>
                                <a href="products.php?vendor=<?php echo $vendor['id']; ?>" class="btn btn-outline">
                                    <i class="fas fa-box"></i> Products
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- Call to Action -->
    <div style="text-align: center; margin-top: 4rem; padding: 3rem; background: #f8fafc; border-radius: 12px;">
        <h3 style="margin-bottom: 1rem;">Want to become a vendor?</h3>
        <p style="color: #6b7280; margin-bottom: 2rem;">Join our marketplace and start selling your products to customers across Rwanda</p>
        <a href="vendor/register.php" class="btn btn-primary">
            <i class="fas fa-store"></i> Become a Vendor
        </a>
    </div>
</div>

<style>
.vendor-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 30px rgba(0,0,0,0.15);
}

@media (max-width: 768px) {
    .vendors-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<?php require_once 'includes/footer.php'; ?>
