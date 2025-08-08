<?php
/**
 * MarketHub Customer Management
 * Admin panel for managing customers
 */

require_once '../config/config.php';

$page_title = 'Customer Management';

// Check if user is admin
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    redirect('../login.php?error=access_denied');
}

// Get customers with their order information
$customers = $database->fetchAll(
    "SELECT u.*, cp.newsletter_subscribed, cp.preferred_language,
            COUNT(DISTINCT o.id) as total_orders,
            SUM(CASE WHEN o.payment_status = 'paid' THEN o.total_amount ELSE 0 END) as total_spent,
            MAX(o.created_at) as last_order_date
     FROM users u 
     LEFT JOIN customer_profiles cp ON u.id = cp.customer_id 
     LEFT JOIN orders o ON u.id = o.customer_id
     WHERE u.user_type = 'customer' 
     GROUP BY u.id
     ORDER BY u.status ASC, u.created_at DESC"
);

require_once 'includes/admin_header_new.php';
?>

<div class="content-header">
    <h1><i class="fas fa-users"></i> Customer Management</h1>
    <p>Manage customer accounts and profiles</p>
</div>

<!-- Statistics -->
<div class="stats-grid">
    <?php
    $customer_stats = [
        'total' => count($customers),
        'active' => count(array_filter($customers, fn($c) => $c['status'] === 'active')),
        'pending' => count(array_filter($customers, fn($c) => $c['status'] === 'pending')),
        'with_orders' => count(array_filter($customers, fn($c) => $c['total_orders'] > 0))
    ];
    ?>
    
    <div class="stat-card">
        <div class="stat-icon" style="background: #2196F3;">
            <i class="fas fa-users"></i>
        </div>
        <div class="stat-content">
            <h3><?php echo $customer_stats['total']; ?></h3>
            <p>Total Customers</p>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon" style="background: #4CAF50;">
            <i class="fas fa-user-check"></i>
        </div>
        <div class="stat-content">
            <h3><?php echo $customer_stats['active']; ?></h3>
            <p>Active Customers</p>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon" style="background: #FF9800;">
            <i class="fas fa-user-clock"></i>
        </div>
        <div class="stat-content">
            <h3><?php echo $customer_stats['pending']; ?></h3>
            <p>Pending Approval</p>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon" style="background: #9C27B0;">
            <i class="fas fa-shopping-cart"></i>
        </div>
        <div class="stat-content">
            <h3><?php echo $customer_stats['with_orders']; ?></h3>
            <p>With Orders</p>
        </div>
    </div>
</div>

<!-- Customers List -->
<div class="dashboard-widget">
    <div class="widget-header">
        <h4><i class="fas fa-list"></i> All Customers</h4>
        <div class="widget-actions">
            <a href="user-management.php?filter=customer" class="btn btn-primary">
                <i class="fas fa-plus"></i> Add Customer
            </a>
        </div>
    </div>
    <div class="widget-content">
        <?php if (empty($customers)): ?>
            <div class="no-data">
                <i class="fas fa-users" style="font-size: 3rem; color: #ccc; margin-bottom: 1rem;"></i>
                <h3>No Customers Found</h3>
                <p>No customer accounts have been created yet.</p>
                <a href="user-management.php?action=create&type=customer" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Add First Customer
                </a>
            </div>
        <?php else: ?>
            <div class="customers-table">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Customer</th>
                            <th>Contact</th>
                            <th>Status</th>
                            <th>Orders</th>
                            <th>Total Spent</th>
                            <th>Last Order</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($customers as $customer): ?>
                            <tr>
                                <td>
                                    <div class="customer-info">
                                        <h5><?php echo htmlspecialchars($customer['first_name'] . ' ' . $customer['last_name']); ?></h5>
                                        <small class="text-muted">
                                            Joined: <?php echo date('M j, Y', strtotime($customer['created_at'])); ?>
                                        </small>
                                    </div>
                                </td>
                                <td>
                                    <div class="contact-info">
                                        <div><?php echo htmlspecialchars($customer['email']); ?></div>
                                        <small><?php echo htmlspecialchars($customer['phone'] ?: 'No phone'); ?></small>
                                    </div>
                                </td>
                                <td>
                                    <span class="status-badge status-<?php echo $customer['status']; ?>">
                                        <?php echo ucfirst($customer['status']); ?>
                                    </span>
                                    <?php if ($customer['newsletter_subscribed']): ?>
                                        <br><small class="newsletter-badge">Newsletter</small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="order-stats">
                                        <strong><?php echo $customer['total_orders']; ?></strong>
                                        <small>orders</small>
                                    </div>
                                </td>
                                <td>
                                    <div class="spending-stats">
                                        <strong><?php echo formatCurrency($customer['total_spent'] ?: 0); ?></strong>
                                    </div>
                                </td>
                                <td>
                                    <?php if ($customer['last_order_date']): ?>
                                        <small><?php echo date('M j, Y', strtotime($customer['last_order_date'])); ?></small>
                                    <?php else: ?>
                                        <small class="text-muted">Never</small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="customer-details.php?id=<?php echo $customer['id']; ?>" 
                                           class="btn btn-sm btn-primary" title="View Details">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="customer-orders.php?id=<?php echo $customer['id']; ?>" 
                                           class="btn btn-sm btn-secondary" title="View Orders">
                                            <i class="fas fa-shopping-cart"></i>
                                        </a>
                                        <a href="user-edit.php?id=<?php echo $customer['id']; ?>" 
                                           class="btn btn-sm btn-warning" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
.admin-table {
    width: 100%;
    border-collapse: collapse;
    background: white;
}

.admin-table th,
.admin-table td {
    padding: 1rem;
    text-align: left;
    border-bottom: 1px solid #e0e0e0;
}

.admin-table th {
    background: #f8f9fa;
    font-weight: 600;
    color: var(--admin-dark);
}

.admin-table tbody tr:hover {
    background: #f8f9fa;
}

.customer-info h5 {
    margin: 0 0 0.25rem 0;
    color: var(--admin-dark);
}

.contact-info div {
    margin-bottom: 0.25rem;
}

.newsletter-badge {
    background: var(--admin-success);
    color: white;
    padding: 0.2rem 0.5rem;
    border-radius: 10px;
    font-size: 0.7rem;
}

.order-stats,
.spending-stats {
    text-align: center;
}

.order-stats strong,
.spending-stats strong {
    display: block;
    font-size: 1.1rem;
    color: var(--admin-primary);
}

.action-buttons {
    display: flex;
    gap: 0.25rem;
}

.btn-sm {
    padding: 0.5rem;
    font-size: 0.8rem;
    min-width: 32px;
    text-align: center;
}

.btn-warning {
    background: var(--admin-warning);
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
    .customers-table {
        overflow-x: auto;
    }
    
    .admin-table {
        min-width: 800px;
    }
    
    .action-buttons {
        flex-direction: column;
    }
}
</style>

<?php require_once 'includes/admin_footer_new.php'; ?>
