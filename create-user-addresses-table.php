<?php
/**
 * Create User Addresses Table
 * Set up the database table for customer address management
 */

require_once 'config/config.php';

echo "<h1>🏠 Creating User Addresses Table</h1>";

try {
    // Create user_addresses table
    $create_table_sql = "
    CREATE TABLE IF NOT EXISTS user_addresses (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        address_line_1 VARCHAR(255) NOT NULL,
        address_line_2 VARCHAR(255) DEFAULT NULL,
        city VARCHAR(100) NOT NULL,
        state VARCHAR(100) DEFAULT NULL,
        postal_code VARCHAR(20) DEFAULT NULL,
        country VARCHAR(100) NOT NULL DEFAULT 'Rwanda',
        is_default TINYINT(1) DEFAULT 0,
        address_type ENUM('home', 'work', 'other') DEFAULT 'home',
        recipient_name VARCHAR(255) DEFAULT NULL,
        recipient_phone VARCHAR(20) DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        INDEX idx_user_id (user_id),
        INDEX idx_is_default (is_default)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ";
    
    $database->execute($create_table_sql);
    echo "✅ Created user_addresses table successfully<br>";
    
    // Add some sample addresses for existing users
    echo "<h2>📍 Adding Sample Addresses</h2>";
    
    // Get some existing customers
    $customers = $database->fetchAll(
        "SELECT id, username, first_name, last_name, phone FROM users WHERE user_type = 'customer' LIMIT 5"
    );
    
    $sample_addresses = [
        [
            'address_line_1' => 'KG 15 Ave, House #123',
            'address_line_2' => 'Near City Market',
            'city' => 'Kigali',
            'state' => 'Kigali City',
            'postal_code' => '00001',
            'address_type' => 'home'
        ],
        [
            'address_line_1' => 'KN 3 Rd, Building A, Apt 45',
            'address_line_2' => 'Kimisagara Sector',
            'city' => 'Kigali',
            'state' => 'Kigali City', 
            'postal_code' => '00002',
            'address_type' => 'work'
        ],
        [
            'address_line_1' => 'KG 9 Ave, Plot 67',
            'address_line_2' => 'Gikondo Industrial Zone',
            'city' => 'Kigali',
            'state' => 'Kigali City',
            'postal_code' => '00003',
            'address_type' => 'home'
        ],
        [
            'address_line_1' => 'Musanze Town Center',
            'address_line_2' => 'Near Volcanoes National Park',
            'city' => 'Musanze',
            'state' => 'Northern Province',
            'postal_code' => '00101',
            'address_type' => 'home'
        ],
        [
            'address_line_1' => 'Huye University Campus',
            'address_line_2' => 'Student Accommodation Block C',
            'city' => 'Huye',
            'state' => 'Southern Province',
            'postal_code' => '00201',
            'address_type' => 'other'
        ]
    ];
    
    foreach ($customers as $index => $customer) {
        if (isset($sample_addresses[$index])) {
            $address = $sample_addresses[$index];
            
            // Check if customer already has addresses
            $existing = $database->fetch(
                "SELECT COUNT(*) as count FROM user_addresses WHERE user_id = ?",
                [$customer['id']]
            );
            
            if ($existing['count'] == 0) {
                $recipient_name = $customer['first_name'] . ' ' . $customer['last_name'];
                $recipient_phone = $customer['phone'];
                
                $database->execute(
                    "INSERT INTO user_addresses (user_id, address_line_1, address_line_2, city, state, postal_code, country, is_default, address_type, recipient_name, recipient_phone, created_at) 
                     VALUES (?, ?, ?, ?, ?, ?, 'Rwanda', 1, ?, ?, ?, NOW())",
                    [
                        $customer['id'],
                        $address['address_line_1'],
                        $address['address_line_2'],
                        $address['city'],
                        $address['state'],
                        $address['postal_code'],
                        $address['address_type'],
                        $recipient_name,
                        $recipient_phone
                    ]
                );
                
                echo "✅ Added address for customer: {$customer['username']} in {$address['city']}<br>";
            } else {
                echo "ℹ️ Customer {$customer['username']} already has addresses<br>";
            }
        }
    }
    
    // Show current addresses
    echo "<h2>📊 Current Addresses in Database</h2>";
    
    $all_addresses = $database->fetchAll(
        "SELECT ua.*, u.username, u.first_name, u.last_name 
         FROM user_addresses ua 
         JOIN users u ON ua.user_id = u.id 
         ORDER BY ua.created_at DESC"
    );
    
    if (!empty($all_addresses)) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%; margin: 20px 0;'>";
        echo "<tr style='background: #f8f9fa;'>";
        echo "<th style='padding: 10px;'>Customer</th>";
        echo "<th style='padding: 10px;'>Address</th>";
        echo "<th style='padding: 10px;'>City</th>";
        echo "<th style='padding: 10px;'>Type</th>";
        echo "<th style='padding: 10px;'>Default</th>";
        echo "</tr>";
        
        foreach ($all_addresses as $addr) {
            $default_badge = $addr['is_default'] ? '<span style="background: #28a745; color: white; padding: 2px 6px; border-radius: 3px; font-size: 12px;">Default</span>' : '';
            
            echo "<tr>";
            echo "<td style='padding: 8px;'>{$addr['first_name']} {$addr['last_name']} ({$addr['username']})</td>";
            echo "<td style='padding: 8px;'>{$addr['address_line_1']}<br><small style='color: #666;'>{$addr['address_line_2']}</small></td>";
            echo "<td style='padding: 8px;'>{$addr['city']}, {$addr['state']}</td>";
            echo "<td style='padding: 8px;'>" . ucfirst($addr['address_type']) . "</td>";
            echo "<td style='padding: 8px;'>{$default_badge}</td>";
            echo "</tr>";
        }
        
        echo "</table>";
    }
    
    // Show statistics
    echo "<h2>📈 Address Statistics</h2>";
    
    $stats = [
        'Total Addresses' => $database->fetch("SELECT COUNT(*) as count FROM user_addresses")['count'],
        'Customers with Addresses' => $database->fetch("SELECT COUNT(DISTINCT user_id) as count FROM user_addresses")['count'],
        'Default Addresses' => $database->fetch("SELECT COUNT(*) as count FROM user_addresses WHERE is_default = 1")['count'],
        'Kigali Addresses' => $database->fetch("SELECT COUNT(*) as count FROM user_addresses WHERE city = 'Kigali'")['count']
    ];
    
    echo "<div style='display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin: 20px 0;'>";
    
    foreach ($stats as $label => $value) {
        $color = $value > 0 ? '#28a745' : '#6c757d';
        echo "<div style='background: white; border: 2px solid {$color}; padding: 15px; border-radius: 8px; text-align: center;'>";
        echo "<h3 style='margin: 0; color: {$color}; font-size: 1.5rem;'>{$value}</h3>";
        echo "<p style='margin: 5px 0 0 0; color: #666;'>{$label}</p>";
        echo "</div>";
    }
    
    echo "</div>";
    
    echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
    echo "<h3 style='color: #155724; margin: 0 0 10px 0;'>✅ User Addresses Table Created Successfully!</h3>";
    echo "<p style='color: #155724; margin: 0;'>The user_addresses table has been created with sample data. Customers can now manage their delivery addresses.</p>";
    echo "</div>";
    
    echo "<h2>🧪 Test Address Management</h2>";
    echo "<div style='display: flex; gap: 15px; flex-wrap: wrap; margin: 20px 0;'>";
    echo "<a href='addresses.php' style='background: #007bff; color: white; padding: 12px 20px; text-decoration: none; border-radius: 6px;'>🏠 Manage Addresses</a>";
    echo "<a href='login.php' style='background: #28a745; color: white; padding: 12px 20px; text-decoration: none; border-radius: 6px;'>🔐 Login as Customer</a>";
    echo "<a href='register.php' style='background: #ffc107; color: black; padding: 12px 20px; text-decoration: none; border-radius: 6px;'>📝 Register New Account</a>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h3 style='color: #721c24; margin: 0 0 10px 0;'>❌ Error Creating Table</h3>";
    echo "<p style='color: #721c24; margin: 0;'>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}

?>

<style>
body { 
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
    padding: 20px; 
    line-height: 1.6; 
    max-width: 1200px; 
    margin: 0 auto; 
    background: #f8fafc; 
}
h1 { 
    color: #10b981; 
    text-align: center; 
    margin-bottom: 30px; 
    font-size: 2.5rem;
}
h2 { 
    color: #374151; 
    border-bottom: 3px solid #10b981; 
    padding-bottom: 10px; 
    margin-top: 40px; 
    font-size: 1.5rem;
}
table {
    font-size: 14px;
}
th {
    background: #f8f9fa !important;
}
</style>
