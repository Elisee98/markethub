<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title ?? 'Admin Panel'; ?> - MarketHub</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: #f8fafc;
            color: #334155;
            line-height: 1.6;
        }

        .admin-layout {
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar */
        .sidebar {
            width: 280px;
            background: #ffffff;
            border-right: 1px solid #e2e8f0;
            position: fixed;
            height: 100vh;
            left: 0;
            top: 0;
            z-index: 1000;
            overflow-y: auto;
            transition: transform 0.3s ease;
        }

        .sidebar-header {
            padding: 1.5rem;
            border-bottom: 1px solid #e2e8f0;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-size: 1.25rem;
            font-weight: 700;
            color: #1e293b;
        }

        .logo i {
            width: 32px;
            height: 32px;
            background: linear-gradient(135deg, #3b82f6, #1d4ed8);
            color: white;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .nav-menu {
            padding: 1rem 0;
        }

        .nav-section {
            margin-bottom: 2rem;
        }

        .nav-section-title {
            padding: 0 1.5rem;
            margin-bottom: 0.5rem;
            font-size: 0.75rem;
            font-weight: 600;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .nav-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem 1.5rem;
            color: #64748b;
            text-decoration: none;
            transition: all 0.2s;
            position: relative;
        }

        .nav-item:hover {
            background: #f1f5f9;
            color: #1e293b;
        }

        .nav-item.active {
            background: #3b82f6;
            color: white;
        }

        .nav-item.active::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 3px;
            background: #1d4ed8;
        }

        .nav-item i {
            width: 20px;
            text-align: center;
        }

        .nav-badge {
            background: #ef4444;
            color: white;
            font-size: 0.75rem;
            padding: 0.125rem 0.5rem;
            border-radius: 10px;
            margin-left: auto;
        }

        /* Main Content */
        .main-content {
            flex: 1;
            margin-left: 280px;
            display: flex;
            flex-direction: column;
        }

        .top-header {
            background: white;
            border-bottom: 1px solid #e2e8f0;
            padding: 1rem 2rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .page-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: #1e293b;
        }

        .header-actions {
            display: flex;
            align-items: center;
            gap: 1rem;
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
        }

        .btn-primary {
            background: #3b82f6;
            color: white;
        }

        .btn-primary:hover {
            background: #2563eb;
        }

        .content-area {
            flex: 1;
            padding: 2rem;
        }

        .mobile-toggle {
            display: none;
            background: none;
            border: none;
            font-size: 1.25rem;
            color: #64748b;
            cursor: pointer;
        }

        /* Mobile */
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }

            .sidebar.open {
                transform: translateX(0);
            }

            .main-content {
                margin-left: 0;
            }

            .mobile-toggle {
                display: block;
            }

            .top-header {
                padding: 1rem;
            }

            .content-area {
                padding: 1rem;
            }
        }

        .overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 999;
            display: none;
        }

        .overlay.active {
            display: block;
        }

        /* Modal Styles for User Details */
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
    </style>
</head>
<body>
    <div class="admin-layout">
        <!-- Sidebar -->
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <div class="logo">
                    <i class="fas fa-shield-alt"></i>
                    <span>MarketHub Admin</span>
                </div>
            </div>

            <nav class="nav-menu">
                <!-- Dashboard -->
                <a href="#" class="nav-item active" data-page="dashboard">
                    <i class="fas fa-home"></i>
                    <span>Dashboard</span>
                </a>

                <!-- User Management -->
                <div class="nav-section">
                    <div class="nav-section-title">User Management</div>
                    <a href="#" class="nav-item" data-page="users">
                        <i class="fas fa-users"></i>
                        <span>All Users</span>
                        <?php
                        try {
                            $pending_count = $database->fetch("SELECT COUNT(*) as count FROM users WHERE status = 'pending'")['count'];
                            if ($pending_count > 0):
                        ?>
                            <span class="nav-badge" id="pending-badge"><?php echo $pending_count; ?></span>
                        <?php
                            endif;
                        } catch (Exception $e) {
                            // Ignore database errors in header
                        }
                        ?>
                    </a>
                    <a href="#" class="nav-item" data-page="vendors">
                        <i class="fas fa-store"></i>
                        <span>Vendors</span>
                    </a>
                    <a href="#" class="nav-item" data-page="customers">
                        <i class="fas fa-user-friends"></i>
                        <span>Customers</span>
                    </a>
                </div>

                <!-- Content Management -->
                <div class="nav-section">
                    <div class="nav-section-title">Content</div>
                    <a href="#" class="nav-item" data-page="products">
                        <i class="fas fa-box"></i>
                        <span>Products</span>
                    </a>
                    <a href="#" class="nav-item" data-page="categories">
                        <i class="fas fa-tags"></i>
                        <span>Categories</span>
                    </a>
                    <a href="#" class="nav-item" data-page="orders">
                        <i class="fas fa-shopping-cart"></i>
                        <span>Orders</span>
                    </a>
                </div>

                <!-- Analytics -->
                <div class="nav-section">
                    <div class="nav-section-title">Analytics</div>
                    <a href="#" class="nav-item" data-page="analytics">
                        <i class="fas fa-chart-line"></i>
                        <span>Analytics</span>
                    </a>
                    <a href="#" class="nav-item" data-page="reports">
                        <i class="fas fa-file-alt"></i>
                        <span>Reports</span>
                    </a>
                </div>

                <!-- System -->
                <div class="nav-section">
                    <div class="nav-section-title">System</div>
                    <a href="#" class="nav-item" data-page="system">
                        <i class="fas fa-server"></i>
                        <span>System Status</span>
                    </a>
                    <a href="#" class="nav-item" data-page="email">
                        <i class="fas fa-envelope"></i>
                        <span>Email Test</span>
                    </a>
                </div>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Top Header -->
            <header class="top-header">
                <button class="mobile-toggle" onclick="toggleSidebar()">
                    <i class="fas fa-bars"></i>
                </button>
                <h1 class="page-title" id="page-title">Dashboard</h1>
                <div class="header-actions">
                    <a href="../index.php" class="btn btn-primary" target="_blank">
                        <i class="fas fa-external-link-alt"></i>
                        <span>View Store</span>
                    </a>
                    <a href="../logout.php" class="btn" style="background: #ef4444; color: white;">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>Logout</span>
                    </a>
                </div>
            </header>

            <!-- Loading Indicator -->
            <div id="loading" style="display: none; padding: 2rem; text-align: center; color: #64748b;">
                <i class="fas fa-spinner fa-spin" style="font-size: 2rem; margin-bottom: 1rem;"></i>
                <p>Loading...</p>
            </div>

            <!-- Content Area -->
            <div class="content-area" id="content-area">

            <script>
            // Global User Management Functions
            function approveUser(userId) {
                if (confirm('Are you sure you want to approve this user?')) {
                    fetch('ajax/quick-user-action.php', {
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
                    })
                    .catch(error => {
                        showNotification('Error approving user', 'error');
                    });
                }
            }

            function rejectUser(userId) {
                if (confirm('Are you sure you want to reject this user? This action cannot be undone.')) {
                    fetch('ajax/quick-user-action.php', {
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
                    })
                    .catch(error => {
                        showNotification('Error rejecting user', 'error');
                    });
                }
            }

            function activateUser(userId) {
                if (confirm('Are you sure you want to activate this user?')) {
                    fetch('ajax/quick-user-action.php', {
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

            function deactivateUser(userId) {
                if (confirm('Are you sure you want to deactivate this user? They will not be able to access their account.')) {
                    fetch('ajax/quick-user-action.php', {
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
                fetch(`ajax/get-user-details.php?user_id=${userId}`)
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
            </script>
