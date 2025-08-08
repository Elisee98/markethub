<?php
/**
 * Users Management Page Content
 */

// Get filter parameters
$status_filter = sanitizeInput($_GET['status'] ?? '');
$type_filter = sanitizeInput($_GET['type'] ?? '');

// Build query
$where_conditions = ['1=1'];
$params = [];

if ($status_filter) {
    $where_conditions[] = "u.status = ?";
    $params[] = $status_filter;
}

if ($type_filter) {
    $where_conditions[] = "u.user_type = ?";
    $params[] = $type_filter;
}

$where_clause = implode(' AND ', $where_conditions);

// Get users
$users = $database->fetchAll(
    "SELECT u.*, vs.store_name 
     FROM users u 
     LEFT JOIN vendor_stores vs ON u.id = vs.vendor_id 
     WHERE $where_clause 
     ORDER BY u.created_at DESC 
     LIMIT 50",
    $params
);

// Get statistics
$user_stats = [
    'total' => $database->fetch("SELECT COUNT(*) as count FROM users")['count'],
    'pending' => $database->fetch("SELECT COUNT(*) as count FROM users WHERE status = 'pending'")['count'],
    'active' => $database->fetch("SELECT COUNT(*) as count FROM users WHERE status = 'active'")['count'],
    'vendors' => $database->fetch("SELECT COUNT(*) as count FROM users WHERE user_type = 'vendor'")['count'],
    'customers' => $database->fetch("SELECT COUNT(*) as count FROM users WHERE user_type = 'customer'")['count']
];
?>

<style>
    .page-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 2rem;
    }

    .page-title {
        font-size: 1.875rem;
        font-weight: 700;
        color: #1e293b;
        margin: 0;
    }

    .stats-row {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1rem;
        margin-bottom: 2rem;
    }

    .stat-box {
        background: white;
        padding: 1.5rem;
        border-radius: 8px;
        border: 1px solid #e2e8f0;
        text-align: center;
    }

    .stat-number {
        font-size: 2rem;
        font-weight: 700;
        color: #1e293b;
        margin-bottom: 0.5rem;
    }

    .stat-label {
        color: #64748b;
        font-weight: 500;
    }

    .filters {
        background: white;
        padding: 1.5rem;
        border-radius: 8px;
        border: 1px solid #e2e8f0;
        margin-bottom: 2rem;
    }

    .filter-row {
        display: flex;
        gap: 1rem;
        align-items: end;
    }

    .filter-group {
        flex: 1;
    }

    .filter-group label {
        display: block;
        margin-bottom: 0.5rem;
        font-weight: 500;
        color: #374151;
    }

    .form-control {
        width: 100%;
        padding: 0.5rem;
        border: 1px solid #d1d5db;
        border-radius: 6px;
        font-size: 0.875rem;
    }

    .users-table {
        background: white;
        border-radius: 8px;
        border: 1px solid #e2e8f0;
        overflow: hidden;
    }

    .table {
        width: 100%;
        border-collapse: collapse;
    }

    .table th,
    .table td {
        padding: 1rem;
        text-align: left;
        border-bottom: 1px solid #e2e8f0;
    }

    .table th {
        background: #f8fafc;
        font-weight: 600;
        color: #374151;
    }

    .table tbody tr:hover {
        background: #f8fafc;
    }

    .user-avatar {
        width: 40px;
        height: 40px;
        background: #e2e8f0;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #64748b;
        margin-right: 0.75rem;
    }

    .user-info {
        display: flex;
        align-items: center;
    }

    .user-details h5 {
        margin: 0 0 0.25rem 0;
        color: #1e293b;
        font-weight: 600;
    }

    .user-details small {
        color: #64748b;
    }

    .status-badge {
        padding: 0.25rem 0.75rem;
        border-radius: 12px;
        font-size: 0.75rem;
        font-weight: 600;
    }

    .status-pending {
        background: #fef3c7;
        color: #92400e;
    }

    .status-active {
        background: #d1fae5;
        color: #065f46;
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
    }

    .btn-sm {
        padding: 0.375rem 0.75rem;
        font-size: 0.8rem;
    }

    .btn-primary {
        background: #3b82f6;
        color: white;
    }

    .btn-success {
        background: #10b981;
        color: white;
    }

    .btn-danger {
        background: #ef4444;
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

    .action-buttons {
        display: flex;
        gap: 0.5rem;
    }

    .no-data {
        text-align: center;
        padding: 3rem;
        color: #94a3b8;
    }

    /* Modal Styles */
    .modal-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 1000;
    }

    .modal-content {
        background: white;
        border-radius: 8px;
        width: 90%;
        max-width: 800px;
        max-height: 90vh;
        overflow: hidden;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
    }

    .modal-header {
        padding: 1rem 1.5rem;
        border-bottom: 1px solid #eee;
        display: flex;
        justify-content: space-between;
        align-items: center;
        background: #f8f9fa;
    }

    .modal-header h3 {
        margin: 0;
        color: #333;
    }

    .close-btn {
        background: none;
        border: none;
        font-size: 1.5rem;
        cursor: pointer;
        color: #666;
        padding: 0;
        width: 30px;
        height: 30px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
    }

    .close-btn:hover {
        background: #e9ecef;
        color: #333;
    }

    .modal-body {
        padding: 1.5rem;
        overflow-y: auto;
        max-height: calc(90vh - 80px);
    }

    @media (max-width: 768px) {
        .filter-row {
            flex-direction: column;
        }

        .page-header {
            flex-direction: column;
            align-items: flex-start;
            gap: 1rem;
        }

        .stats-row {
            grid-template-columns: repeat(2, 1fr);
        }
    }
</style>

<div class="page-header">
    <h2 class="page-title">
        <i class="fas fa-users" style="color: #3b82f6; margin-right: 0.5rem;"></i>
        User Management
    </h2>
</div>

<!-- Statistics -->
<div class="stats-row">
    <div class="stat-box">
        <div class="stat-number"><?php echo number_format($user_stats['total']); ?></div>
        <div class="stat-label">Total Users</div>
    </div>
    <div class="stat-box">
        <div class="stat-number" style="color: #ef4444;"><?php echo number_format($user_stats['pending']); ?></div>
        <div class="stat-label">Pending Approval</div>
    </div>
    <div class="stat-box">
        <div class="stat-number" style="color: #10b981;"><?php echo number_format($user_stats['active']); ?></div>
        <div class="stat-label">Active Users</div>
    </div>
    <div class="stat-box">
        <div class="stat-number" style="color: #3b82f6;"><?php echo number_format($user_stats['vendors']); ?></div>
        <div class="stat-label">Vendors</div>
    </div>
    <div class="stat-box">
        <div class="stat-number" style="color: #8b5cf6;"><?php echo number_format($user_stats['customers']); ?></div>
        <div class="stat-label">Customers</div>
    </div>
</div>

<!-- Filters -->
<div class="filters">
    <div class="filter-row">
        <div class="filter-group">
            <label for="status">Status</label>
            <select id="status" class="form-control" onchange="filterUsers()">
                <option value="">All Statuses</option>
                <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                <option value="active" <?php echo $status_filter === 'active' ? 'selected' : ''; ?>>Active</option>
                <option value="inactive" <?php echo $status_filter === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
            </select>
        </div>
        
        <div class="filter-group">
            <label for="type">User Type</label>
            <select id="type" class="form-control" onchange="filterUsers()">
                <option value="">All Types</option>
                <option value="customer" <?php echo $type_filter === 'customer' ? 'selected' : ''; ?>>Customers</option>
                <option value="vendor" <?php echo $type_filter === 'vendor' ? 'selected' : ''; ?>>Vendors</option>
                <option value="admin" <?php echo $type_filter === 'admin' ? 'selected' : ''; ?>>Admins</option>
            </select>
        </div>
        
        <div class="filter-group">
            <button onclick="clearFilters()" class="btn btn-secondary">
                <i class="fas fa-times"></i> Clear
            </button>
        </div>
    </div>
</div>

<!-- Users Table -->
<div class="users-table">
    <?php if (empty($users)): ?>
        <div class="no-data">
            <i class="fas fa-users" style="font-size: 3rem; margin-bottom: 1rem;"></i>
            <h3>No Users Found</h3>
            <p>No users match your current filters.</p>
        </div>
    <?php else: ?>
        <table class="table">
            <thead>
                <tr>
                    <th>User</th>
                    <th>Type</th>
                    <th>Status</th>
                    <th>Joined</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                    <tr>
                        <td>
                            <div class="user-info">
                                <div class="user-avatar">
                                    <i class="fas fa-user"></i>
                                </div>
                                <div class="user-details">
                                    <h5><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></h5>
                                    <small><?php echo htmlspecialchars($user['email']); ?></small>
                                    <?php if ($user['store_name']): ?>
                                        <br><small><strong><?php echo htmlspecialchars($user['store_name']); ?></strong></small>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span style="text-transform: capitalize; font-weight: 500;">
                                <?php echo $user['user_type']; ?>
                            </span>
                        </td>
                        <td>
                            <span class="status-badge status-<?php echo $user['status']; ?>">
                                <?php echo ucfirst($user['status']); ?>
                            </span>
                        </td>
                        <td>
                            <small><?php echo date('M j, Y', strtotime($user['created_at'])); ?></small>
                        </td>
                        <td>
                            <div class="action-buttons">
                                <?php if ($user['status'] === 'pending'): ?>
                                    <button onclick="approveUser(<?php echo $user['id']; ?>)" class="btn btn-sm btn-success">
                                        <i class="fas fa-check"></i> Approve
                                    </button>
                                    <button onclick="rejectUser(<?php echo $user['id']; ?>)" class="btn btn-sm btn-danger">
                                        <i class="fas fa-times"></i> Reject
                                    </button>
                                <?php else: ?>
                                    <button onclick="viewUser(<?php echo $user['id']; ?>)" class="btn btn-sm btn-primary">
                                        <i class="fas fa-eye"></i> View
                                    </button>
                                    <?php if ($user['status'] === 'active'): ?>
                                        <button onclick="deactivateUser(<?php echo $user['id']; ?>)" class="btn btn-sm btn-danger">
                                            <i class="fas fa-ban"></i> Deactivate
                                        </button>
                                    <?php else: ?>
                                        <button onclick="activateUser(<?php echo $user['id']; ?>)" class="btn btn-sm btn-success">
                                            <i class="fas fa-check"></i> Activate
                                        </button>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<script>
function filterUsers() {
    const status = document.getElementById('status').value;
    const type = document.getElementById('type').value;
    
    let url = 'ajax/load-page.php?page=users';
    const params = [];
    
    if (status) params.push(`status=${status}`);
    if (type) params.push(`type=${type}`);
    
    if (params.length > 0) {
        url += '&' + params.join('&');
    }
    
    fetch(url)
        .then(response => response.text())
        .then(html => {
            document.getElementById('content-area').innerHTML = html;
        });
}

function clearFilters() {
    document.getElementById('status').value = '';
    document.getElementById('type').value = '';
    filterUsers();
}

function approveUser(userId) {
    if (confirm('Are you sure you want to approve this user?')) {
        fetch('../ajax/quick-user-action.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=approve&user_id=${userId}&csrf_token=<?php echo generateCSRFToken(); ?>`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification('User approved successfully!', 'success');
                loadPage('users');
                updatePendingCount();
            } else {
                showNotification(data.message || 'Error approving user', 'error');
            }
        });
    }
}

function rejectUser(userId) {
    if (confirm('Are you sure you want to reject this user? This action cannot be undone.')) {
        fetch('../ajax/quick-user-action.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=reject&user_id=${userId}&csrf_token=<?php echo generateCSRFToken(); ?>`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification('User rejected successfully!', 'success');
                loadPage('users');
                updatePendingCount();
            } else {
                showNotification(data.message || 'Error rejecting user', 'error');
            }
        });
    }
}

function viewUser(userId) {
    // Create modal for user details
    const modal = document.createElement('div');
    modal.className = 'modal-overlay';
    modal.innerHTML = `
        <div class="modal-content" style="max-width: 600px;">
            <div class="modal-header">
                <h3>User Details</h3>
                <button onclick="this.closest('.modal-overlay').remove()" class="close-btn">&times;</button>
            </div>
            <div class="modal-body">
                <div id="user-details-content">Loading...</div>
            </div>
        </div>
    `;

    document.body.appendChild(modal);

    // Fetch user details
    fetch(`../ajax/get-user-details.php?user_id=${userId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('user-details-content').innerHTML = data.html;
            } else {
                document.getElementById('user-details-content').innerHTML = '<p>Error loading user details.</p>';
            }
        })
        .catch(error => {
            document.getElementById('user-details-content').innerHTML = '<p>Error loading user details.</p>';
        });
}

function deactivateUser(userId) {
    if (confirm('Are you sure you want to deactivate this user? They will not be able to access their account.')) {
        fetch('../ajax/quick-user-action.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=deactivate&user_id=${userId}&csrf_token=<?php echo generateCSRFToken(); ?>`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification('User deactivated successfully!', 'success');
                loadPage('users');
                updatePendingCount();
            } else {
                showNotification(data.message || 'Error deactivating user', 'error');
            }
        })
        .catch(error => {
            showNotification('Error deactivating user', 'error');
        });
    }
}

function activateUser(userId) {
    if (confirm('Are you sure you want to activate this user?')) {
        fetch('../ajax/quick-user-action.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=activate&user_id=${userId}&csrf_token=<?php echo generateCSRFToken(); ?>`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification('User activated successfully!', 'success');
                loadPage('users');
                updatePendingCount();
            } else {
                showNotification(data.message || 'Error activating user', 'error');
            }
        })
        .catch(error => {
            showNotification('Error activating user', 'error');
        });
    }
}
</script>
