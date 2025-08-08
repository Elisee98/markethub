<?php
/**
 * Check and Create Missing Tables
 * Comprehensive check for all tables that might be missing
 */

require_once 'config/config.php';

echo "<h1>🔍 Checking for Missing Database Tables</h1>";

// List of tables that should exist based on the codebase
$required_tables = [
    'users' => 'Core user accounts',
    'products' => 'Product catalog',
    'categories' => 'Product categories',
    'orders' => 'Customer orders',
    'order_items' => 'Order line items',
    'vendor_stores' => 'Vendor store information',
    'user_addresses' => 'Customer delivery addresses',
    'activity_logs' => 'System activity tracking',
    'contact_inquiries' => 'Contact form submissions',
    'customer_profiles' => 'Extended customer information',
    'payments' => 'Payment transactions',
    'vendor_applications' => 'Vendor registration applications',
    'wishlists' => 'Customer wishlists',
    'cart_items' => 'Shopping cart items',
    'product_reviews' => 'Product reviews and ratings',
    'category_images' => 'Category image management'
];

echo "<h2>📊 Table Existence Check</h2>";

$existing_tables = [];
$missing_tables = [];

echo "<table border='1' style='border-collapse: collapse; width: 100%; margin: 20px 0;'>";
echo "<tr style='background: #f8f9fa;'>";
echo "<th style='padding: 10px;'>Table Name</th>";
echo "<th style='padding: 10px;'>Description</th>";
echo "<th style='padding: 10px;'>Status</th>";
echo "<th style='padding: 10px;'>Action</th>";
echo "</tr>";

foreach ($required_tables as $table => $description) {
    try {
        $result = $database->fetch("SELECT 1 FROM {$table} LIMIT 1");
        $existing_tables[] = $table;
        $status = "✅ Exists";
        $color = "#28a745";
        $action = "No action needed";
    } catch (Exception $e) {
        $missing_tables[] = $table;
        $status = "❌ Missing";
        $color = "#dc3545";
        $action = "Needs creation";
    }
    
    echo "<tr>";
    echo "<td style='padding: 8px; font-weight: bold;'>{$table}</td>";
    echo "<td style='padding: 8px;'>{$description}</td>";
    echo "<td style='padding: 8px; color: {$color}; font-weight: bold;'>{$status}</td>";
    echo "<td style='padding: 8px; color: {$color};'>{$action}</td>";
    echo "</tr>";
}

echo "</table>";

// Show summary
echo "<h2>📈 Summary</h2>";
echo "<div style='display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin: 20px 0;'>";

$total_tables = count($required_tables);
$existing_count = count($existing_tables);
$missing_count = count($missing_tables);

echo "<div style='background: white; border: 2px solid #28a745; padding: 15px; border-radius: 8px; text-align: center;'>";
echo "<h3 style='margin: 0; color: #28a745; font-size: 1.5rem;'>{$existing_count}</h3>";
echo "<p style='margin: 5px 0 0 0; color: #666;'>Existing Tables</p>";
echo "</div>";

echo "<div style='background: white; border: 2px solid #dc3545; padding: 15px; border-radius: 8px; text-align: center;'>";
echo "<h3 style='margin: 0; color: #dc3545; font-size: 1.5rem;'>{$missing_count}</h3>";
echo "<p style='margin: 5px 0 0 0; color: #666;'>Missing Tables</p>";
echo "</div>";

$percentage = round(($existing_count / $total_tables) * 100, 1);
$perf_color = $percentage >= 90 ? '#28a745' : ($percentage >= 70 ? '#ffc107' : '#dc3545');

echo "<div style='background: white; border: 2px solid {$perf_color}; padding: 15px; border-radius: 8px; text-align: center;'>";
echo "<h3 style='margin: 0; color: {$perf_color}; font-size: 1.5rem;'>{$percentage}%</h3>";
echo "<p style='margin: 5px 0 0 0; color: #666;'>Database Complete</p>";
echo "</div>";

echo "</div>";

if (!empty($missing_tables)) {
    echo "<h2>🔧 Creating Missing Tables</h2>";
    
    // Create missing tables
    $tables_created = [];
    $creation_errors = [];
    
    foreach ($missing_tables as $table) {
        try {
            switch ($table) {
                case 'activity_logs':
                    $sql = "CREATE TABLE activity_logs (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        user_id INT DEFAULT NULL,
                        action VARCHAR(100) NOT NULL,
                        description TEXT,
                        ip_address VARCHAR(45) DEFAULT NULL,
                        user_agent TEXT,
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
                        INDEX idx_user_id (user_id),
                        INDEX idx_action (action),
                        INDEX idx_created_at (created_at)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
                    break;
                    
                case 'contact_inquiries':
                    $sql = "CREATE TABLE contact_inquiries (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        name VARCHAR(255) NOT NULL,
                        email VARCHAR(255) NOT NULL,
                        phone VARCHAR(20) DEFAULT NULL,
                        subject VARCHAR(255) NOT NULL,
                        message TEXT NOT NULL,
                        status ENUM('new', 'in_progress', 'resolved', 'closed') DEFAULT 'new',
                        admin_notes TEXT DEFAULT NULL,
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                        INDEX idx_status (status),
                        INDEX idx_created_at (created_at)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
                    break;
                    
                case 'customer_profiles':
                    $sql = "CREATE TABLE customer_profiles (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        user_id INT NOT NULL,
                        date_of_birth DATE DEFAULT NULL,
                        gender ENUM('male', 'female', 'other') DEFAULT NULL,
                        preferences JSON DEFAULT NULL,
                        newsletter_subscribed TINYINT(1) DEFAULT 1,
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                        UNIQUE KEY unique_user_id (user_id)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
                    break;
                    
                case 'payments':
                    $sql = "CREATE TABLE payments (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        order_id INT NOT NULL,
                        payment_method ENUM('card', 'mobile_money', 'bank_transfer', 'cash') NOT NULL,
                        payment_reference VARCHAR(255) DEFAULT NULL,
                        amount DECIMAL(10,2) NOT NULL,
                        currency VARCHAR(3) DEFAULT 'RWF',
                        status ENUM('pending', 'processing', 'completed', 'failed', 'refunded') DEFAULT 'pending',
                        transaction_id VARCHAR(255) DEFAULT NULL,
                        gateway_response JSON DEFAULT NULL,
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                        FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
                        INDEX idx_order_id (order_id),
                        INDEX idx_status (status),
                        INDEX idx_payment_method (payment_method)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
                    break;
                    
                case 'vendor_applications':
                    $sql = "CREATE TABLE vendor_applications (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        user_id INT NOT NULL,
                        business_name VARCHAR(255) NOT NULL,
                        business_type VARCHAR(100) DEFAULT NULL,
                        business_registration VARCHAR(255) DEFAULT NULL,
                        tax_id VARCHAR(100) DEFAULT NULL,
                        business_address TEXT DEFAULT NULL,
                        business_phone VARCHAR(20) DEFAULT NULL,
                        business_email VARCHAR(255) DEFAULT NULL,
                        business_description TEXT DEFAULT NULL,
                        status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
                        admin_notes TEXT DEFAULT NULL,
                        submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        reviewed_at TIMESTAMP NULL DEFAULT NULL,
                        reviewed_by INT DEFAULT NULL,
                        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                        FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE SET NULL,
                        INDEX idx_status (status),
                        INDEX idx_submitted_at (submitted_at)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
                    break;
                    
                case 'wishlists':
                    $sql = "CREATE TABLE wishlists (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        customer_id INT NOT NULL,
                        product_id INT NOT NULL,
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        FOREIGN KEY (customer_id) REFERENCES users(id) ON DELETE CASCADE,
                        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
                        UNIQUE KEY unique_customer_product (customer_id, product_id),
                        INDEX idx_customer_id (customer_id),
                        INDEX idx_product_id (product_id)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
                    break;
                    
                case 'cart_items':
                    $sql = "CREATE TABLE cart_items (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        customer_id INT NOT NULL,
                        product_id INT NOT NULL,
                        quantity INT NOT NULL DEFAULT 1,
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                        FOREIGN KEY (customer_id) REFERENCES users(id) ON DELETE CASCADE,
                        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
                        UNIQUE KEY unique_customer_product (customer_id, product_id),
                        INDEX idx_customer_id (customer_id),
                        INDEX idx_product_id (product_id)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
                    break;
                    
                case 'product_reviews':
                    $sql = "CREATE TABLE product_reviews (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        product_id INT NOT NULL,
                        customer_id INT NOT NULL,
                        order_id INT DEFAULT NULL,
                        rating INT NOT NULL CHECK (rating >= 1 AND rating <= 5),
                        title VARCHAR(255) DEFAULT NULL,
                        review_text TEXT DEFAULT NULL,
                        status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
                        helpful_count INT DEFAULT 0,
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
                        FOREIGN KEY (customer_id) REFERENCES users(id) ON DELETE CASCADE,
                        FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE SET NULL,
                        UNIQUE KEY unique_customer_product_order (customer_id, product_id, order_id),
                        INDEX idx_product_id (product_id),
                        INDEX idx_customer_id (customer_id),
                        INDEX idx_rating (rating),
                        INDEX idx_status (status)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
                    break;
                    
                case 'category_images':
                    $sql = "CREATE TABLE category_images (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        category_id INT NOT NULL,
                        image_url VARCHAR(500) NOT NULL,
                        alt_text VARCHAR(255) DEFAULT NULL,
                        is_primary TINYINT(1) DEFAULT 0,
                        sort_order INT DEFAULT 0,
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE,
                        INDEX idx_category_id (category_id),
                        INDEX idx_is_primary (is_primary)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
                    break;
                    
                default:
                    continue 2; // Skip unknown tables
            }
            
            $database->execute($sql);
            $tables_created[] = $table;
            echo "✅ Created table: {$table}<br>";
            
        } catch (Exception $e) {
            $creation_errors[] = "Error creating {$table}: " . $e->getMessage();
            echo "❌ Error creating {$table}: " . $e->getMessage() . "<br>";
        }
    }
    
    // Show results
    if (!empty($tables_created)) {
        echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
        echo "<h3 style='color: #155724; margin: 0 0 10px 0;'>✅ Tables Created Successfully!</h3>";
        echo "<ul style='color: #155724; margin: 0;'>";
        foreach ($tables_created as $table) {
            echo "<li>{$table}</li>";
        }
        echo "</ul>";
        echo "</div>";
    }
    
    if (!empty($creation_errors)) {
        echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
        echo "<h3 style='color: #721c24; margin: 0 0 10px 0;'>❌ Creation Errors</h3>";
        echo "<ul style='color: #721c24; margin: 0;'>";
        foreach ($creation_errors as $error) {
            echo "<li>{$error}</li>";
        }
        echo "</ul>";
        echo "</div>";
    }
    
} else {
    echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
    echo "<h3 style='color: #155724; margin: 0;'>✅ All Required Tables Exist!</h3>";
    echo "<p style='color: #155724; margin: 10px 0 0 0;'>Your database is complete and ready to use.</p>";
    echo "</div>";
}

echo "<h2>🧪 Test Database Functionality</h2>";
echo "<div style='display: flex; gap: 15px; flex-wrap: wrap; margin: 20px 0;'>";
echo "<a href='addresses.php' style='background: #007bff; color: white; padding: 12px 20px; text-decoration: none; border-radius: 6px;'>🏠 Test Addresses</a>";
echo "<a href='contact.php' style='background: #28a745; color: white; padding: 12px 20px; text-decoration: none; border-radius: 6px;'>📞 Test Contact</a>";
echo "<a href='products.php' style='background: #ffc107; color: black; padding: 12px 20px; text-decoration: none; border-radius: 6px;'>🛍️ Test Products</a>";
echo "<a href='admin/dashboard.php' style='background: #6f42c1; color: white; padding: 12px 20px; text-decoration: none; border-radius: 6px;'>⚙️ Admin Dashboard</a>";
echo "</div>";

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
