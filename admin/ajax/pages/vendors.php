<?php
/**
 * Vendors Management Page Content
 */

// Get vendors
$vendors = $database->fetchAll(
    "SELECT u.*, vs.store_name, vs.store_description, vs.phone, vs.address,
            COUNT(DISTINCT p.id) as product_count,
            COUNT(DISTINCT o.id) as order_count,
            SUM(o.total_amount) as total_revenue
     FROM users u 
     LEFT JOIN vendor_stores vs ON u.id = vs.vendor_id 
     LEFT JOIN products p ON u.id = p.vendor_id AND p.status = 'active'
     LEFT JOIN order_items oi ON p.id = oi.product_id
     LEFT JOIN orders o ON oi.order_id = o.id AND o.payment_status = 'paid'
     WHERE u.user_type = 'vendor' 
     GROUP BY u.id
     ORDER BY u.created_at DESC 
     LIMIT 50"
);
?>

<style>
    .page-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 2rem;
    }

    .vendors-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
        gap: 1.5rem;
    }

    .vendor-card {
        background: white;
        border-radius: 12px;
        border: 1px solid #e2e8f0;
        overflow: hidden;
        transition: all 0.2s;
    }

    .vendor-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    }

    .vendor-header {
        padding: 1.5rem;
        border-bottom: 1px solid #e2e8f0;
    }

    .vendor-info {
        display: flex;
        align-items: center;
        gap: 1rem;
    }

    .vendor-avatar {
        width: 60px;
        height: 60px;
        background: linear-gradient(135deg, #3b82f6, #1d4ed8);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 1.5rem;
    }

    .vendor-details h3 {
        margin: 0 0 0.5rem 0;
        color: #1e293b;
        font-size: 1.25rem;
    }

    .vendor-details p {
        margin: 0;
        color: #64748b;
        font-size: 0.875rem;
    }

    .vendor-stats {
        padding: 1.5rem;
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 1rem;
    }

    .stat-item {
        text-align: center;
    }

    .stat-number {
        font-size: 1.5rem;
        font-weight: 700;
        color: #1e293b;
        margin-bottom: 0.25rem;
    }

    .stat-label {
        font-size: 0.75rem;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }

    .vendor-actions {
        padding: 1rem 1.5rem;
        background: #f8fafc;
        display: flex;
        gap: 0.75rem;
    }

    .status-badge {
        padding: 0.25rem 0.75rem;
        border-radius: 12px;
        font-size: 0.75rem;
        font-weight: 600;
        margin-bottom: 0.5rem;
        display: inline-block;
    }

    .status-active {
        background: #d1fae5;
        color: #065f46;
    }

    .status-pending {
        background: #fef3c7;
        color: #92400e;
    }

    .status-inactive {
        background: #fee2e2;
        color: #991b1b;
    }

    .btn {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.5rem 1rem;
        border-radius: 6px;
        text-decoration: none;
        font-weight: 500;
        transition: all 0.2s;
        border: none;
        cursor: pointer;
        font-size: 0.875rem;
        flex: 1;
        justify-content: center;
    }

    .btn-primary {
        background: #3b82f6;
        color: white;
    }

    .btn-success {
        background: #10b981;
        color: white;
    }

    .btn-secondary {
        background: #6b7280;
        color: white;
    }

    .btn:hover {
        opacity: 0.9;
        transform: translateY(-1px);
    }

    .no-data {
        text-align: center;
        padding: 3rem;
        color: #94a3b8;
        grid-column: 1 / -1;
    }
</style>

<div class="page-header">
    <h2 style="font-size: 1.875rem; font-weight: 700; color: #1e293b; margin: 0;">
        <i class="fas fa-store" style="color: #3b82f6; margin-right: 0.5rem;"></i>
        Vendor Management
    </h2>
</div>

<div class="vendors-grid">
    <?php if (empty($vendors)): ?>
        <div class="no-data">
            <i class="fas fa-store" style="font-size: 3rem; margin-bottom: 1rem;"></i>
            <h3>No Vendors Found</h3>
            <p>No vendors have registered yet.</p>
        </div>
    <?php else: ?>
        <?php foreach ($vendors as $vendor): ?>
            <div class="vendor-card">
                <div class="vendor-header">
                    <span class="status-badge status-<?php echo $vendor['status']; ?>">
                        <?php echo ucfirst($vendor['status']); ?>
                    </span>
                    <div class="vendor-info">
                        <div class="vendor-avatar">
                            <i class="fas fa-store"></i>
                        </div>
                        <div class="vendor-details">
                            <h3><?php echo htmlspecialchars($vendor['store_name'] ?: $vendor['first_name'] . ' ' . $vendor['last_name']); ?></h3>
                            <p><?php echo htmlspecialchars($vendor['email']); ?></p>
                            <?php if ($vendor['phone']): ?>
                                <p><i class="fas fa-phone"></i> <?php echo htmlspecialchars($vendor['phone']); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php if ($vendor['store_description']): ?>
                        <p style="margin-top: 1rem; color: #64748b; font-size: 0.875rem;">
                            <?php echo htmlspecialchars(substr($vendor['store_description'], 0, 100)); ?>
                            <?php echo strlen($vendor['store_description']) > 100 ? '...' : ''; ?>
                        </p>
                    <?php endif; ?>
                </div>

                <div class="vendor-stats">
                    <div class="stat-item">
                        <div class="stat-number"><?php echo number_format($vendor['product_count'] ?: 0); ?></div>
                        <div class="stat-label">Products</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number"><?php echo number_format($vendor['order_count'] ?: 0); ?></div>
                        <div class="stat-label">Orders</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number"><?php echo formatCurrency($vendor['total_revenue'] ?: 0); ?></div>
                        <div class="stat-label">Revenue</div>
                    </div>
                </div>

                <div class="vendor-actions">
                    <button onclick="viewVendor(<?php echo $vendor['id']; ?>)" class="btn btn-primary">
                        <i class="fas fa-eye"></i> View Details
                    </button>
                    <?php if ($vendor['status'] === 'pending'): ?>
                        <button onclick="approveVendor(<?php echo $vendor['id']; ?>)" class="btn btn-success">
                            <i class="fas fa-check"></i> Approve
                        </button>
                    <?php else: ?>
                        <button onclick="manageVendor(<?php echo $vendor['id']; ?>)" class="btn btn-secondary">
                            <i class="fas fa-cog"></i> Manage
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<script>
function viewVendor(vendorId) {
    showNotification('Vendor details feature coming soon!', 'info');
}

function approveVendor(vendorId) {
    if (confirm('Are you sure you want to approve this vendor?')) {
        fetch('../ajax/quick-user-action.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=approve&user_id=${vendorId}&csrf_token=<?php echo generateCSRFToken(); ?>`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification('Vendor approved successfully!', 'success');
                loadPage('vendors');
                updatePendingCount();
            } else {
                showNotification(data.message || 'Error approving vendor', 'error');
            }
        });
    }
}

function manageVendor(vendorId) {
    showNotification('Vendor management feature coming soon!', 'info');
}
</script>
