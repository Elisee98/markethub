<?php
/**
 * MarketHub Vendor Management
 * Admin panel for managing vendors
 */

require_once '../config/config.php';

$page_title = 'Vendor Management';

// Check if user is admin
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    redirect('../login.php?error=access_denied');
}

// Get vendors with their store information
$vendors = $database->fetchAll(
    "SELECT u.*, vs.store_name, vs.store_description, vs.business_license, 
            vs.city, vs.state, vs.phone as store_phone, vs.email as store_email,
            vs.status as store_status, vs.rating, vs.total_sales,
            COUNT(DISTINCT p.id) as total_products,
            COUNT(DISTINCT o.id) as total_orders
     FROM users u 
     LEFT JOIN vendor_stores vs ON u.id = vs.vendor_id 
     LEFT JOIN products p ON u.id = p.vendor_id AND p.status = 'active'
     LEFT JOIN order_items oi ON p.id = oi.product_id
     LEFT JOIN orders o ON oi.order_id = o.id AND o.payment_status = 'paid'
     WHERE u.user_type = 'vendor' 
     GROUP BY u.id
     ORDER BY u.status ASC, u.created_at DESC"
);

require_once 'includes/admin_header_new.php';
?>

<div class="content-header">
    <h1><i class="fas fa-store"></i> Vendor Management</h1>
    <p>Manage vendor accounts and store information</p>
</div>

<!-- Statistics -->
<div class="stats-grid">
    <?php
    $vendor_stats = [
        'total' => count($vendors),
        'active' => count(array_filter($vendors, fn($v) => $v['status'] === 'active')),
        'pending' => count(array_filter($vendors, fn($v) => $v['status'] === 'pending')),
        'rejected' => count(array_filter($vendors, fn($v) => $v['status'] === 'rejected'))
    ];
    ?>
    
    <div class="stat-card">
        <div class="stat-icon" style="background: #2196F3;">
            <i class="fas fa-store"></i>
        </div>
        <div class="stat-content">
            <h3><?php echo $vendor_stats['total']; ?></h3>
            <p>Total Vendors</p>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon" style="background: #4CAF50;">
            <i class="fas fa-check-circle"></i>
        </div>
        <div class="stat-content">
            <h3><?php echo $vendor_stats['active']; ?></h3>
            <p>Active Vendors</p>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon" style="background: #FF9800;">
            <i class="fas fa-clock"></i>
        </div>
        <div class="stat-content">
            <h3><?php echo $vendor_stats['pending']; ?></h3>
            <p>Pending Approval</p>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon" style="background: #f44336;">
            <i class="fas fa-times-circle"></i>
        </div>
        <div class="stat-content">
            <h3><?php echo $vendor_stats['rejected']; ?></h3>
            <p>Rejected</p>
        </div>
    </div>
</div>

<!-- Vendors List -->
<div class="dashboard-widget">
    <div class="widget-header">
        <h4><i class="fas fa-list"></i> All Vendors</h4>
        <div class="widget-actions">
            <a href="user-management.php?filter=vendor" class="btn btn-primary">
                <i class="fas fa-plus"></i> Add Vendor
            </a>
        </div>
    </div>
    <div class="widget-content">
        <?php if (empty($vendors)): ?>
            <div class="no-data">
                <i class="fas fa-store" style="font-size: 3rem; color: #ccc; margin-bottom: 1rem;"></i>
                <h3>No Vendors Found</h3>
                <p>No vendor accounts have been created yet.</p>
                <a href="user-management.php?action=create&type=vendor" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Add First Vendor
                </a>
            </div>
        <?php else: ?>
            <div class="vendors-grid">
                <?php foreach ($vendors as $vendor): ?>
                    <div class="vendor-card">
                        <div class="vendor-header">
                            <div class="vendor-info">
                                <h4><?php echo htmlspecialchars($vendor['store_name'] ?: $vendor['first_name'] . ' ' . $vendor['last_name']); ?></h4>
                                <p><?php echo htmlspecialchars($vendor['first_name'] . ' ' . $vendor['last_name']); ?></p>
                                <span class="status-badge status-<?php echo $vendor['status']; ?>">
                                    <?php echo ucfirst($vendor['status']); ?>
                                </span>
                            </div>
                            <div class="vendor-actions">
                                <a href="vendor-details.php?id=<?php echo $vendor['id']; ?>" class="btn btn-sm btn-primary">
                                    <i class="fas fa-eye"></i> View
                                </a>
                                <a href="vendor-edit.php?id=<?php echo $vendor['id']; ?>" class="btn btn-sm btn-secondary">
                                    <i class="fas fa-edit"></i> Edit
                                </a>
                            </div>
                        </div>
                        
                        <div class="vendor-details">
                            <div class="detail-row">
                                <span class="detail-label">Email:</span>
                                <span class="detail-value"><?php echo htmlspecialchars($vendor['email']); ?></span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Phone:</span>
                                <span class="detail-value"><?php echo htmlspecialchars($vendor['phone'] ?: 'Not provided'); ?></span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Business License:</span>
                                <span class="detail-value"><?php echo htmlspecialchars($vendor['business_license'] ?: 'Not provided'); ?></span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Location:</span>
                                <span class="detail-value"><?php echo htmlspecialchars(($vendor['city'] ?: '') . ($vendor['state'] ? ', ' . $vendor['state'] : '')); ?></span>
                            </div>
                        </div>
                        
                        <div class="vendor-stats">
                            <div class="stat-item">
                                <span class="stat-number"><?php echo $vendor['total_products']; ?></span>
                                <span class="stat-label">Products</span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-number"><?php echo $vendor['total_orders']; ?></span>
                                <span class="stat-label">Orders</span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-number"><?php echo $vendor['rating'] ? number_format($vendor['rating'], 1) : '0.0'; ?></span>
                                <span class="stat-label">Rating</span>
                            </div>
                        </div>
                        
                        <?php if ($vendor['store_description']): ?>
                            <div class="vendor-description">
                                <p><?php echo htmlspecialchars(substr($vendor['store_description'], 0, 100)); ?>
                                   <?php echo strlen($vendor['store_description']) > 100 ? '...' : ''; ?></p>
                            </div>
                        <?php endif; ?>
                        
                        <div class="vendor-footer">
                            <small class="text-muted">
                                Joined: <?php echo date('M j, Y', strtotime($vendor['created_at'])); ?>
                            </small>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
.vendors-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
    gap: 1.5rem;
}

.vendor-card {
    background: white;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    padding: 1.5rem;
    transition: box-shadow 0.3s;
}

.vendor-card:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.vendor-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 1rem;
}

.vendor-info h4 {
    margin: 0 0 0.25rem 0;
    color: var(--admin-dark);
}

.vendor-info p {
    margin: 0 0 0.5rem 0;
    color: #666;
    font-size: 0.9rem;
}

.vendor-actions {
    display: flex;
    gap: 0.5rem;
}

.btn-sm {
    padding: 0.5rem 0.75rem;
    font-size: 0.85rem;
}

.vendor-details {
    margin-bottom: 1rem;
}

.detail-row {
    display: flex;
    justify-content: space-between;
    margin-bottom: 0.5rem;
    font-size: 0.9rem;
}

.detail-label {
    font-weight: 600;
    color: #666;
}

.detail-value {
    color: var(--admin-dark);
}

.vendor-stats {
    display: flex;
    justify-content: space-around;
    padding: 1rem;
    background: #f8f9fa;
    border-radius: 6px;
    margin-bottom: 1rem;
}

.stat-item {
    text-align: center;
}

.stat-number {
    display: block;
    font-size: 1.25rem;
    font-weight: bold;
    color: var(--admin-primary);
}

.stat-label {
    font-size: 0.8rem;
    color: #666;
}

.vendor-description {
    margin-bottom: 1rem;
    padding: 0.75rem;
    background: #f8f9fa;
    border-radius: 6px;
    font-size: 0.9rem;
    color: #666;
}

.vendor-footer {
    border-top: 1px solid #e0e0e0;
    padding-top: 0.75rem;
    text-align: center;
}

.text-muted {
    color: #999 !important;
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
    .vendors-grid {
        grid-template-columns: 1fr;
    }
    
    .vendor-header {
        flex-direction: column;
        gap: 1rem;
    }
    
    .vendor-actions {
        align-self: stretch;
    }
    
    .vendor-stats {
        flex-direction: column;
        gap: 0.5rem;
    }
    
    .stat-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
}
</style>

<?php require_once 'includes/admin_footer_new.php'; ?>
