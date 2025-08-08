<?php
/**
 * MarketHub System Status
 * Check system health and configuration
 */

require_once '../config/config.php';

$page_title = 'System Status';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - MarketHub</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .status-container {
            max-width: 1000px;
            margin: 2rem auto;
            padding: 2rem;
        }
        
        .status-header {
            background: linear-gradient(135deg, #2E7D32, #4CAF50);
            color: white;
            padding: 2rem;
            border-radius: 8px;
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .status-section {
            background: white;
            border-radius: 8px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .status-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
        }
        
        .status-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem;
            border: 1px solid #e0e0e0;
            border-radius: 5px;
            margin-bottom: 0.5rem;
        }
        
        .status-ok {
            border-left: 4px solid #4CAF50;
            background: #f1f8e9;
        }
        
        .status-warning {
            border-left: 4px solid #FF9800;
            background: #fff8e1;
        }
        
        .status-error {
            border-left: 4px solid #f44336;
            background: #ffebee;
        }
        
        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: bold;
        }
        
        .badge-ok {
            background: #4CAF50;
            color: white;
        }
        
        .badge-warning {
            background: #FF9800;
            color: white;
        }
        
        .badge-error {
            background: #f44336;
            color: white;
        }
        
        .quick-actions {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
            justify-content: center;
            margin-top: 2rem;
        }
        
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            text-decoration: none;
            display: inline-block;
            transition: opacity 0.3s;
        }
        
        .btn:hover {
            opacity: 0.9;
        }
        
        .btn-primary {
            background: #2196F3;
            color: white;
        }
        
        .btn-success {
            background: #4CAF50;
            color: white;
        }
        
        .btn-warning {
            background: #FF9800;
            color: white;
        }
    </style>
</head>
<body>
    <div class="status-container">
        <div class="status-header">
            <h1><i class="fas fa-heartbeat"></i> System Status</h1>
            <p>MarketHub system health and configuration overview</p>
        </div>
        
        <!-- Core Functions -->
        <div class="status-section">
            <h3>Core Functions</h3>
            <div class="status-grid">
                <div>
                    <?php
                    $core_functions = [
                        'registerUser' => function_exists('registerUser'),
                        'loginUser' => function_exists('loginUser'),
                        'sendEmail' => function_exists('sendEmail'),
                        'logActivity' => function_exists('logActivity'),
                        'validateEmailFormat' => function_exists('validateEmailFormat'),
                        'sanitizeInput' => function_exists('sanitizeInput'),
                        'generateCSRFToken' => function_exists('generateCSRFToken'),
                        'verifyCSRFToken' => function_exists('verifyCSRFToken'),
                        'formatCurrency' => function_exists('formatCurrency'),
                        'isLoggedIn' => function_exists('isLoggedIn')
                    ];
                    
                    foreach ($core_functions as $func => $exists) {
                        $class = $exists ? 'status-ok' : 'status-error';
                        $badge = $exists ? 'badge-ok' : 'badge-error';
                        $status = $exists ? 'OK' : 'MISSING';
                        
                        echo "<div class='status-item $class'>";
                        echo "<span>$func()</span>";
                        echo "<span class='status-badge $badge'>$status</span>";
                        echo "</div>";
                    }
                    ?>
                </div>
            </div>
        </div>
        
        <!-- Configuration -->
        <div class="status-section">
            <h3>Configuration</h3>
            <div class="status-grid">
                <div>
                    <?php
                    $config_items = [
                        'SITE_NAME' => [
                            'value' => defined('SITE_NAME') ? SITE_NAME : 'NOT DEFINED',
                            'status' => defined('SITE_NAME') ? 'ok' : 'error'
                        ],
                        'ADMIN_EMAIL' => [
                            'value' => defined('ADMIN_EMAIL') ? ADMIN_EMAIL : 'NOT DEFINED',
                            'status' => defined('ADMIN_EMAIL') ? 'ok' : 'error'
                        ],
                        'DEVELOPMENT_MODE' => [
                            'value' => defined('DEVELOPMENT_MODE') ? (DEVELOPMENT_MODE ? 'Enabled' : 'Disabled') : 'NOT DEFINED',
                            'status' => defined('DEVELOPMENT_MODE') ? 'ok' : 'error'
                        ],
                        'REQUIRE_ADMIN_APPROVAL' => [
                            'value' => defined('REQUIRE_ADMIN_APPROVAL') ? (REQUIRE_ADMIN_APPROVAL ? 'Enabled' : 'Disabled') : 'NOT DEFINED',
                            'status' => defined('REQUIRE_ADMIN_APPROVAL') ? 'ok' : 'error'
                        ],
                        'REQUIRE_EMAIL_VERIFICATION' => [
                            'value' => defined('REQUIRE_EMAIL_VERIFICATION') ? (REQUIRE_EMAIL_VERIFICATION ? 'Enabled' : 'Disabled') : 'NOT DEFINED',
                            'status' => defined('REQUIRE_EMAIL_VERIFICATION') ? 'ok' : 'error'
                        ]
                    ];
                    
                    foreach ($config_items as $config => $info) {
                        $class = 'status-' . $info['status'];
                        $badge = 'badge-' . $info['status'];
                        
                        echo "<div class='status-item $class'>";
                        echo "<span>$config</span>";
                        echo "<span class='status-badge $badge'>{$info['value']}</span>";
                        echo "</div>";
                    }
                    ?>
                </div>
            </div>
        </div>
        
        <!-- Database -->
        <div class="status-section">
            <h3>Database</h3>
            <div class="status-grid">
                <div>
                    <?php
                    $db_tests = [
                        'Connection' => false,
                        'Users Table' => false,
                        'Products Table' => false,
                        'Categories Table' => false,
                        'Orders Table' => false,
                        'Vendor Stores Table' => false,
                        'Activity Logs Table' => false,
                        'Contact Inquiries Table' => false
                    ];
                    
                    try {
                        // Test connection
                        $test = $database->fetch("SELECT 1 as test");
                        if ($test && $test['test'] == 1) {
                            $db_tests['Connection'] = true;
                        }
                        
                        // Test tables
                        $tables = [
                            'Users Table' => 'users',
                            'Products Table' => 'products',
                            'Categories Table' => 'categories',
                            'Orders Table' => 'orders',
                            'Vendor Stores Table' => 'vendor_stores',
                            'Activity Logs Table' => 'activity_logs',
                            'Contact Inquiries Table' => 'contact_inquiries'
                        ];
                        
                        foreach ($tables as $name => $table) {
                            try {
                                $database->fetch("SELECT 1 FROM $table LIMIT 1");
                                $db_tests[$name] = true;
                            } catch (Exception $e) {
                                // Table doesn't exist or has issues
                            }
                        }
                        
                    } catch (Exception $e) {
                        // Database connection failed
                    }
                    
                    foreach ($db_tests as $test => $passed) {
                        $class = $passed ? 'status-ok' : 'status-error';
                        $badge = $passed ? 'badge-ok' : 'badge-error';
                        $status = $passed ? 'OK' : 'FAILED';
                        
                        echo "<div class='status-item $class'>";
                        echo "<span>$test</span>";
                        echo "<span class='status-badge $badge'>$status</span>";
                        echo "</div>";
                    }
                    ?>
                </div>
            </div>
        </div>
        
        <!-- User Statistics -->
        <div class="status-section">
            <h3>User Statistics</h3>
            <div class="status-grid">
                <div>
                    <?php
                    try {
                        $stats = [
                            'Total Users' => $database->fetch("SELECT COUNT(*) as count FROM users")['count'],
                            'Pending Users' => $database->fetch("SELECT COUNT(*) as count FROM users WHERE status = 'pending'")['count'],
                            'Active Users' => $database->fetch("SELECT COUNT(*) as count FROM users WHERE status = 'active'")['count'],
                            'Active Vendors' => $database->fetch("SELECT COUNT(*) as count FROM users WHERE user_type = 'vendor' AND status = 'active'")['count'],
                            'Active Customers' => $database->fetch("SELECT COUNT(*) as count FROM users WHERE user_type = 'customer' AND status = 'active'")['count'],
                            'Admin Users' => $database->fetch("SELECT COUNT(*) as count FROM users WHERE user_type = 'admin'")['count']
                        ];
                        
                        foreach ($stats as $stat => $count) {
                            $class = 'status-ok';
                            $badge = 'badge-ok';
                            
                            if ($stat === 'Pending Users' && $count > 0) {
                                $class = 'status-warning';
                                $badge = 'badge-warning';
                            }
                            
                            if ($stat === 'Admin Users' && $count == 0) {
                                $class = 'status-error';
                                $badge = 'badge-error';
                            }
                            
                            echo "<div class='status-item $class'>";
                            echo "<span>$stat</span>";
                            echo "<span class='status-badge $badge'>$count</span>";
                            echo "</div>";
                        }
                        
                    } catch (Exception $e) {
                        echo "<div class='status-item status-error'>";
                        echo "<span>Database Error</span>";
                        echo "<span class='status-badge badge-error'>FAILED</span>";
                        echo "</div>";
                    }
                    ?>
                </div>
            </div>
        </div>
        
        <!-- Quick Actions -->
        <div class="quick-actions">
            <a href="../index.php" class="btn btn-primary">
                <i class="fas fa-home"></i> Homepage
            </a>
            <a href="create-admin.php" class="btn btn-success">
                <i class="fas fa-user-shield"></i> Create Admin
            </a>
            <a href="user-management.php" class="btn btn-primary">
                <i class="fas fa-users-cog"></i> User Management
            </a>
            <a href="email-test.php" class="btn btn-warning">
                <i class="fas fa-envelope"></i> Email Test
            </a>
            <a href="fix-database.php" class="btn btn-warning">
                <i class="fas fa-database"></i> Database Fix
            </a>
            <a href="../test-functions.php" class="btn btn-primary">
                <i class="fas fa-code"></i> Function Test
            </a>
        </div>
    </div>
</body>
</html>
