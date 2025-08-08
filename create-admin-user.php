<?php
/**
 * Create Admin User
 * Creates an admin account for testing the admin panel
 */

require_once 'config/config.php';

echo "<h1>👑 Creating Admin User</h1>";

// Admin user details
$admin_data = [
    'username' => 'admin',
    'email' => 'admin@markethub.rw',
    'password' => 'admin123', // Change this to a secure password
    'first_name' => 'System',
    'last_name' => 'Administrator',
    'phone' => '+250788000000',
    'user_type' => 'admin',
    'status' => 'active'
];

try {
    // Check if admin already exists
    $existing_admin = $database->fetch(
        "SELECT id FROM users WHERE username = ? OR email = ? OR user_type = 'admin'", 
        [$admin_data['username'], $admin_data['email']]
    );
    
    if ($existing_admin) {
        echo "<div style='background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
        echo "<h3 style='color: #856404; margin: 0 0 10px 0;'>ℹ️ Admin User Already Exists</h3>";
        echo "<p style='color: #856404; margin: 0;'>An admin user already exists in the system. You can use the existing admin credentials to access the admin panel.</p>";
        echo "</div>";
        
        // Show existing admin details (without password)
        $admin = $database->fetch("SELECT * FROM users WHERE user_type = 'admin' LIMIT 1");
        if ($admin) {
            echo "<h2>📋 Existing Admin Details</h2>";
            echo "<div style='background: white; border: 1px solid #ddd; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
            echo "<p><strong>Username:</strong> {$admin['username']}</p>";
            echo "<p><strong>Email:</strong> {$admin['email']}</p>";
            echo "<p><strong>Name:</strong> {$admin['first_name']} {$admin['last_name']}</p>";
            echo "<p><strong>Status:</strong> {$admin['status']}</p>";
            echo "<p><strong>Created:</strong> " . date('M j, Y g:i A', strtotime($admin['created_at'])) . "</p>";
            echo "</div>";
        }
    } else {
        // Create new admin user
        $password_hash = password_hash($admin_data['password'], PASSWORD_DEFAULT);
        
        $database->execute(
            "INSERT INTO users (username, email, password_hash, first_name, last_name, phone, user_type, status, email_verified, created_at) 
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1, NOW())",
            [
                $admin_data['username'],
                $admin_data['email'],
                $password_hash,
                $admin_data['first_name'],
                $admin_data['last_name'],
                $admin_data['phone'],
                $admin_data['user_type'],
                $admin_data['status']
            ]
        );
        
        $admin_id = $database->lastInsertId();
        
        echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
        echo "<h3 style='color: #155724; margin: 0 0 10px 0;'>✅ Admin User Created Successfully!</h3>";
        echo "<p style='color: #155724; margin: 0;'>Admin account has been created with ID: {$admin_id}</p>";
        echo "</div>";
        
        echo "<h2>📋 Admin Login Credentials</h2>";
        echo "<div style='background: #e7f3ff; border: 1px solid #b3d9ff; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
        echo "<p style='color: #0066cc; margin: 0 0 10px 0;'><strong>Username:</strong> {$admin_data['username']}</p>";
        echo "<p style='color: #0066cc; margin: 0 0 10px 0;'><strong>Email:</strong> {$admin_data['email']}</p>";
        echo "<p style='color: #0066cc; margin: 0 0 15px 0;'><strong>Password:</strong> {$admin_data['password']}</p>";
        echo "<p style='color: #d63384; margin: 0; font-size: 0.9rem;'><strong>⚠️ Important:</strong> Please change the default password after first login for security.</p>";
        echo "</div>";
    }
    
    echo "<h2>🚀 Access Admin Panel</h2>";
    echo "<div style='background: #f8f9fa; border: 1px solid #dee2e6; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
    echo "<h3 style='color: #495057; margin: 0 0 15px 0;'>How to Access the Admin Panel:</h3>";
    echo "<ol style='color: #495057; margin: 0;'>";
    echo "<li><strong>Login:</strong> <a href='login.php' target='_blank' style='color: #007bff;'>Go to Login Page</a></li>";
    echo "<li><strong>Use Admin Credentials:</strong> Enter the username and password above</li>";
    echo "<li><strong>Access Dashboard:</strong> <a href='admin/spa-dashboard.php' target='_blank' style='color: #007bff;'>Open Admin Dashboard</a></li>";
    echo "</ol>";
    echo "</div>";
    
    echo "<h2>🎯 Admin Panel Features</h2>";
    echo "<div style='display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px; margin: 20px 0;'>";
    
    $features = [
        [
            'title' => '👥 User Management',
            'description' => 'Approve, reject, activate, and deactivate users',
            'color' => '#007bff'
        ],
        [
            'title' => '🏪 Vendor Management',
            'description' => 'Manage vendor stores and applications',
            'color' => '#28a745'
        ],
        [
            'title' => '📦 Product Management',
            'description' => 'Oversee all products and categories',
            'color' => '#ffc107'
        ],
        [
            'title' => '📊 Analytics & Reports',
            'description' => 'View platform statistics and reports',
            'color' => '#17a2b8'
        ],
        [
            'title' => '🛒 Order Management',
            'description' => 'Monitor and manage all orders',
            'color' => '#6f42c1'
        ],
        [
            'title' => '⚙️ System Settings',
            'description' => 'Configure platform settings',
            'color' => '#fd7e14'
        ]
    ];
    
    foreach ($features as $feature) {
        echo "<div style='background: white; border: 2px solid {$feature['color']}; padding: 15px; border-radius: 8px;'>";
        echo "<h4 style='margin: 0 0 10px 0; color: {$feature['color']};'>{$feature['title']}</h4>";
        echo "<p style='margin: 0; color: #666; font-size: 0.9rem;'>{$feature['description']}</p>";
        echo "</div>";
    }
    
    echo "</div>";
    
    // Show current user statistics
    echo "<h2>📊 Current Platform Statistics</h2>";
    $stats = [
        'total_users' => $database->fetch("SELECT COUNT(*) as count FROM users")['count'],
        'pending_users' => $database->fetch("SELECT COUNT(*) as count FROM users WHERE status = 'pending'")['count'],
        'active_vendors' => $database->fetch("SELECT COUNT(*) as count FROM users WHERE user_type = 'vendor' AND status = 'active'")['count'],
        'total_products' => $database->fetch("SELECT COUNT(*) as count FROM products WHERE status = 'active'")['count'],
        'total_orders' => $database->fetch("SELECT COUNT(*) as count FROM orders")['count'] ?? 0
    ];
    
    echo "<div style='display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px; margin: 20px 0;'>";
    
    $stat_info = [
        'total_users' => ['label' => 'Total Users', 'color' => '#007bff'],
        'pending_users' => ['label' => 'Pending Users', 'color' => '#ffc107'],
        'active_vendors' => ['label' => 'Active Vendors', 'color' => '#28a745'],
        'total_products' => ['label' => 'Total Products', 'color' => '#17a2b8'],
        'total_orders' => ['label' => 'Total Orders', 'color' => '#6f42c1']
    ];
    
    foreach ($stats as $key => $value) {
        $info = $stat_info[$key];
        echo "<div style='background: white; border: 2px solid {$info['color']}; padding: 15px; border-radius: 8px; text-align: center;'>";
        echo "<h3 style='margin: 0; color: {$info['color']}; font-size: 1.5rem;'>{$value}</h3>";
        echo "<p style='margin: 5px 0 0 0; color: #666; font-size: 0.9rem;'>{$info['label']}</p>";
        echo "</div>";
    }
    
    echo "</div>";
    
    if ($stats['pending_users'] > 0) {
        echo "<div style='background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
        echo "<h3 style='color: #856404; margin: 0 0 10px 0;'>⚠️ Action Required</h3>";
        echo "<p style='color: #856404; margin: 0;'>You have {$stats['pending_users']} pending user(s) waiting for approval. Please review them in the admin panel.</p>";
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h3 style='color: #721c24; margin: 0 0 10px 0;'>❌ Error Creating Admin User</h3>";
    echo "<p style='color: #721c24; margin: 0;'>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}

?>

<style>
body { 
    font-family: Arial, sans-serif; 
    padding: 20px; 
    line-height: 1.6; 
    max-width: 1000px; 
    margin: 0 auto; 
    background: #f8f9fa;
}
h1 { 
    color: #007bff; 
    text-align: center; 
    margin-bottom: 30px; 
}
h2 { 
    color: #333; 
    border-bottom: 2px solid #007bff; 
    padding-bottom: 5px; 
    margin-top: 30px; 
}
</style>
