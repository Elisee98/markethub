<?php
/**
 * Create Test Users for Admin Management Demo
 */

require_once 'config/config.php';

echo "<h1>🧪 Creating Test Users for Admin Demo</h1>";

// Test users to create
$test_users = [
    [
        'username' => 'pending_vendor',
        'email' => 'pending@example.com',
        'password' => password_hash('test123', PASSWORD_DEFAULT),
        'first_name' => 'John',
        'last_name' => 'Pending',
        'phone' => '+250788999001',
        'user_type' => 'vendor',
        'status' => 'pending',
        'store_name' => 'Pending Electronics Store',
        'store_description' => 'Electronics and gadgets store waiting for approval'
    ],
    [
        'username' => 'inactive_customer',
        'email' => 'inactive@example.com',
        'password' => password_hash('test123', PASSWORD_DEFAULT),
        'first_name' => 'Jane',
        'last_name' => 'Inactive',
        'phone' => '+250788999002',
        'user_type' => 'customer',
        'status' => 'inactive'
    ],
    [
        'username' => 'test_customer',
        'email' => 'customer@example.com',
        'password' => password_hash('test123', PASSWORD_DEFAULT),
        'first_name' => 'Bob',
        'last_name' => 'Customer',
        'phone' => '+250788999003',
        'user_type' => 'customer',
        'status' => 'active'
    ],
    [
        'username' => 'rejected_vendor',
        'email' => 'rejected@example.com',
        'password' => password_hash('test123', PASSWORD_DEFAULT),
        'first_name' => 'Alice',
        'last_name' => 'Rejected',
        'phone' => '+250788999004',
        'user_type' => 'vendor',
        'status' => 'rejected',
        'store_name' => 'Rejected Store',
        'store_description' => 'This store was rejected for some reason'
    ]
];

echo "<h2>Creating Test Users</h2>";

foreach ($test_users as $user) {
    try {
        // Check if user already exists
        $existing = $database->fetch("SELECT id FROM users WHERE username = ? OR email = ?", [$user['username'], $user['email']]);
        
        if ($existing) {
            echo "ℹ️ User already exists: {$user['username']}<br>";
            continue;
        }
        
        // Insert user
        $database->execute(
            "INSERT INTO users (username, email, password_hash, first_name, last_name, phone, user_type, status, created_at) 
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())",
            [$user['username'], $user['email'], $user['password'], $user['first_name'], $user['last_name'], $user['phone'], $user['user_type'], $user['status']]
        );
        
        $user_id = $database->lastInsertId();
        
        // If vendor, create store
        if ($user['user_type'] === 'vendor' && isset($user['store_name'])) {
            $store_status = ($user['status'] === 'active') ? 'approved' : 'pending';
            if ($user['status'] === 'rejected') {
                $store_status = 'rejected';
            }
            
            $database->execute(
                "INSERT INTO vendor_stores (vendor_id, store_name, store_description, status, created_at) 
                 VALUES (?, ?, ?, ?, NOW())",
                [$user_id, $user['store_name'], $user['store_description'], $store_status]
            );
        }
        
        echo "✅ Created {$user['user_type']}: {$user['username']} (Status: {$user['status']})<br>";
        
    } catch (Exception $e) {
        echo "❌ Error creating {$user['username']}: " . $e->getMessage() . "<br>";
    }
}

echo "<h2>📊 Current User Statistics</h2>";

// Show current user statistics
$stats = [
    'total' => $database->fetch("SELECT COUNT(*) as count FROM users")['count'],
    'pending' => $database->fetch("SELECT COUNT(*) as count FROM users WHERE status = 'pending'")['count'],
    'active' => $database->fetch("SELECT COUNT(*) as count FROM users WHERE status = 'active'")['count'],
    'inactive' => $database->fetch("SELECT COUNT(*) as count FROM users WHERE status = 'inactive'")['count'],
    'rejected' => $database->fetch("SELECT COUNT(*) as count FROM users WHERE status = 'rejected'")['count'],
    'vendors' => $database->fetch("SELECT COUNT(*) as count FROM users WHERE user_type = 'vendor'")['count'],
    'customers' => $database->fetch("SELECT COUNT(*) as count FROM users WHERE user_type = 'customer'")['count']
];

echo "<div style='display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px; margin: 20px 0;'>";

$stat_colors = [
    'total' => '#007bff',
    'pending' => '#ffc107',
    'active' => '#28a745',
    'inactive' => '#6c757d',
    'rejected' => '#dc3545',
    'vendors' => '#17a2b8',
    'customers' => '#6f42c1'
];

foreach ($stats as $key => $value) {
    $color = $stat_colors[$key] ?? '#007bff';
    echo "<div style='background: white; border: 2px solid {$color}; padding: 15px; border-radius: 8px; text-align: center;'>";
    echo "<h3 style='margin: 0; color: {$color}; font-size: 1.5rem;'>{$value}</h3>";
    echo "<p style='margin: 5px 0 0 0; color: #666; text-transform: capitalize;'>{$key}</p>";
    echo "</div>";
}

echo "</div>";

echo "<h2>🎯 Admin Panel Testing</h2>";

echo "<div style='background: #e7f3ff; border: 1px solid #b3d9ff; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
echo "<h3 style='color: #0066cc; margin: 0 0 15px 0;'>🔧 Test the Admin Panel</h3>";
echo "<p style='color: #0066cc; margin: 0 0 15px 0;'>Now you can test the admin user management functionality:</p>";
echo "<ol style='color: #0066cc; margin: 0;'>";
echo "<li><strong>Go to Admin Panel:</strong> <a href='admin/spa-dashboard.php' target='_blank' style='color: #0066cc;'>Open Admin Dashboard</a></li>";
echo "<li><strong>Click 'All Users'</strong> in the User Management section</li>";
echo "<li><strong>Test Actions:</strong>";
echo "<ul>";
echo "<li>✅ <strong>Approve</strong> pending users</li>";
echo "<li>❌ <strong>Reject</strong> pending users</li>";
echo "<li>🔄 <strong>Activate</strong> inactive users</li>";
echo "<li>⏸️ <strong>Deactivate</strong> active users</li>";
echo "<li>👁️ <strong>View</strong> detailed user information</li>";
echo "</ul>";
echo "</li>";
echo "<li><strong>Filter Users:</strong> Use status and type filters</li>";
echo "</ol>";
echo "</div>";

echo "<h2>📋 Test User Accounts</h2>";

echo "<div style='display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 15px; margin: 20px 0;'>";

foreach ($test_users as $user) {
    $status_color = [
        'pending' => '#ffc107',
        'active' => '#28a745',
        'inactive' => '#6c757d',
        'rejected' => '#dc3545'
    ][$user['status']] ?? '#007bff';
    
    echo "<div style='background: white; border: 1px solid #ddd; padding: 15px; border-radius: 8px;'>";
    echo "<h4 style='margin: 0 0 10px 0; color: #333;'>{$user['first_name']} {$user['last_name']}</h4>";
    echo "<p style='margin: 0 0 5px 0; color: #666;'><strong>Username:</strong> {$user['username']}</p>";
    echo "<p style='margin: 0 0 5px 0; color: #666;'><strong>Email:</strong> {$user['email']}</p>";
    echo "<p style='margin: 0 0 5px 0; color: #666;'><strong>Type:</strong> " . ucfirst($user['user_type']) . "</p>";
    echo "<p style='margin: 0 0 10px 0;'><strong>Status:</strong> <span style='background: {$status_color}; color: white; padding: 2px 8px; border-radius: 4px; font-size: 12px;'>" . ucfirst($user['status']) . "</span></p>";
    
    if (isset($user['store_name'])) {
        echo "<p style='margin: 0; color: #666; font-style: italic;'><strong>Store:</strong> {$user['store_name']}</p>";
    }
    
    echo "</div>";
}

echo "</div>";

echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; padding: 20px; border-radius: 8px; margin: 30px 0;'>";
echo "<h3 style='color: #155724; margin: 0 0 10px 0;'>✅ Test Users Created Successfully!</h3>";
echo "<p style='color: #155724; margin: 0;'>You now have test users in different states to demonstrate the admin user management functionality. Go to the admin panel to test approving, rejecting, activating, and deactivating users.</p>";
echo "</div>";

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
