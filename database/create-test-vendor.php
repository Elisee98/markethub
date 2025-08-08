<?php
/**
 * Create Test Vendor Account
 */

require_once '../config/config.php';

echo "<h2>Create Test Vendor Account</h2>\n";
echo "<pre>\n";

try {
    // Check if test vendor already exists
    $existing_vendor = $database->fetch("SELECT id, username FROM users WHERE username = 'testvendor' AND user_type = 'vendor'");
    
    if ($existing_vendor) {
        echo "✓ Test vendor already exists:\n";
        echo "  Username: testvendor\n";
        echo "  Password: password123\n";
        echo "  ID: " . $existing_vendor['id'] . "\n\n";
    } else {
        echo "Creating test vendor account...\n";
        
        // Create test vendor
        $vendor_id = $database->execute("
            INSERT INTO users (username, email, password, user_type, first_name, last_name, phone, status, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ", [
            'testvendor',
            'vendor@test.com',
            password_hash('password123', PASSWORD_DEFAULT),
            'vendor',
            'Test',
            'Vendor',
            '+250 123 456 789',
            'active'
        ]);
        
        echo "✓ Test vendor created successfully!\n";
        echo "  Username: testvendor\n";
        echo "  Password: password123\n";
        echo "  Email: vendor@test.com\n";
        echo "  ID: " . $vendor_id . "\n\n";
        
        // Create vendor store
        $store_id = $database->execute("
            INSERT INTO vendor_stores (vendor_id, store_name, description, address, city, state, country, phone, email, status, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ", [
            $vendor_id,
            'Test Store',
            'A test store for development and testing purposes',
            '123 Test Street',
            'Musanze',
            'Northern Province',
            'Rwanda',
            '+250 123 456 789',
            'store@test.com',
            'approved'
        ]);
        
        echo "✓ Vendor store created successfully!\n";
        echo "  Store Name: Test Store\n";
        echo "  Store ID: " . $store_id . "\n\n";
        
        // Create some test products
        $products = [
            ['Test Product 1', 'A sample product for testing', 15000, 'Electronics'],
            ['Test Product 2', 'Another sample product', 25000, 'Electronics'],
            ['Test Product 3', 'Third test product', 35000, 'Electronics']
        ];
        
        echo "Creating test products...\n";
        
        foreach ($products as $index => $product) {
            $product_id = $database->execute("
                INSERT INTO products (vendor_id, name, description, price, category_id, sku, stock_quantity, status, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ", [
                $vendor_id,
                $product[0],
                $product[1],
                $product[2],
                1, // Assuming category ID 1 exists
                'TEST-' . str_pad($index + 1, 3, '0', STR_PAD_LEFT),
                100,
                'active'
            ]);
            
            echo "  ✓ Created: " . $product[0] . " (ID: $product_id)\n";
        }
    }
    
    echo "\n";
    
    // Show login instructions
    echo "=== LOGIN INSTRUCTIONS ===\n";
    echo "1. Go to: http://localhost/ange Final/vendor/login.php\n";
    echo "2. Username: testvendor\n";
    echo "3. Password: password123\n";
    echo "4. Then access: http://localhost/ange Final/vendor/spa-dashboard.php\n\n";
    
    echo "✅ Test vendor setup completed!\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "</pre>\n";
echo "<p><a href='../vendor/login.php'>Login as Vendor</a> | <a href='../vendor/spa-dashboard.php'>Vendor Dashboard</a> | <a href='../'>Back to Home</a></p>\n";
?>

<style>
body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    margin: 2rem;
    background: #f8f9fa;
}

h2 {
    color: #10b981;
    margin-bottom: 1rem;
}

pre {
    background: white;
    padding: 2rem;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    overflow-x: auto;
}

a {
    color: #10b981;
    text-decoration: none;
    margin-right: 1rem;
    padding: 0.5rem 1rem;
    background: white;
    border-radius: 4px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

a:hover {
    background: #10b981;
    color: white;
}
</style>
