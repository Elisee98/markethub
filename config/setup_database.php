<?php
/**
 * Database Setup Script for MarketHub Vendor System
 * This script safely sets up all required tables and columns
 */

require_once 'config.php';

echo "<h2>MarketHub Database Setup</h2>";

try {
    // 1. Create categories table if it doesn't exist
    echo "<h3>1. Setting up Categories Table</h3>";
    $database->execute("
        CREATE TABLE IF NOT EXISTS categories (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL UNIQUE,
            description TEXT,
            status ENUM('active', 'inactive') DEFAULT 'active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )
    ");
    echo "✅ Categories table created/verified<br>";

    // 2. Add product columns safely
    echo "<h3>2. Updating Products Table</h3>";
    $product_columns = [
        'image_url' => 'VARCHAR(255) DEFAULT NULL',
        'brand' => 'VARCHAR(100) DEFAULT NULL',
        'sku' => 'VARCHAR(50) DEFAULT NULL',
        'weight' => 'DECIMAL(8,2) DEFAULT NULL',
        'dimensions' => 'VARCHAR(100) DEFAULT NULL',
        'tags' => 'TEXT DEFAULT NULL',
        'compare_price' => 'DECIMAL(10,2) DEFAULT NULL'
    ];

    foreach ($product_columns as $column => $definition) {
        try {
            $database->execute("ALTER TABLE products ADD COLUMN $column $definition");
            echo "✅ Added column: $column<br>";
        } catch (Exception $e) {
            if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
                echo "ℹ️ Column $column already exists<br>";
            } else {
                echo "❌ Error adding column $column: " . $e->getMessage() . "<br>";
            }
        }
    }

    // 3. Create product_images table
    echo "<h3>3. Setting up Product Images Table</h3>";
    $database->execute("
        CREATE TABLE IF NOT EXISTS product_images (
            id INT AUTO_INCREMENT PRIMARY KEY,
            product_id INT NOT NULL,
            image_url VARCHAR(255) NOT NULL,
            is_primary BOOLEAN DEFAULT FALSE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_product_id (product_id),
            INDEX idx_primary (product_id, is_primary),
            FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
        )
    ");
    echo "✅ Product images table created/verified<br>";

    // 4. Create vendor_stores table
    echo "<h3>4. Setting up Vendor Stores Table</h3>";
    $database->execute("
        CREATE TABLE IF NOT EXISTS vendor_stores (
            id INT AUTO_INCREMENT PRIMARY KEY,
            vendor_id INT NOT NULL,
            store_name VARCHAR(255) NOT NULL,
            store_description TEXT,
            store_email VARCHAR(255),
            phone VARCHAR(20),
            address TEXT,
            logo_url VARCHAR(255),
            business_name VARCHAR(255),
            business_type ENUM('individual', 'company', 'cooperative') DEFAULT 'individual',
            tax_id VARCHAR(100),
            business_license VARCHAR(100),
            business_description TEXT,
            shipping_fee DECIMAL(10,2) DEFAULT 1000,
            free_shipping_threshold DECIMAL(10,2) DEFAULT 50000,
            processing_time VARCHAR(10) DEFAULT '1',
            delivery_time VARCHAR(10) DEFAULT '1-2',
            shipping_policy TEXT,
            status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY unique_vendor_store (vendor_id),
            FOREIGN KEY (vendor_id) REFERENCES users(id) ON DELETE CASCADE
        )
    ");
    echo "✅ Vendor stores table created/verified<br>";

    // 5. Add user profile columns
    echo "<h3>5. Updating Users Table</h3>";
    $user_columns = [
        'avatar' => 'VARCHAR(255) DEFAULT NULL',
        'date_of_birth' => 'DATE DEFAULT NULL',
        'gender' => "ENUM('male', 'female', 'other') DEFAULT NULL",
        'language' => "VARCHAR(10) DEFAULT 'en'",
        'timezone' => "VARCHAR(50) DEFAULT 'Africa/Kigali'",
        'currency' => "VARCHAR(10) DEFAULT 'RWF'",
        'date_format' => "VARCHAR(20) DEFAULT 'Y-m-d'"
    ];

    foreach ($user_columns as $column => $definition) {
        try {
            $database->execute("ALTER TABLE users ADD COLUMN $column $definition");
            echo "✅ Added user column: $column<br>";
        } catch (Exception $e) {
            if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
                echo "ℹ️ User column $column already exists<br>";
            } else {
                echo "❌ Error adding user column $column: " . $e->getMessage() . "<br>";
            }
        }
    }

    // 6. Add order tracking columns
    echo "<h3>6. Updating Orders Table</h3>";
    $order_columns = [
        'tracking_number' => 'VARCHAR(100) DEFAULT NULL',
        'carrier' => 'VARCHAR(50) DEFAULT NULL'
    ];

    foreach ($order_columns as $column => $definition) {
        try {
            $database->execute("ALTER TABLE orders ADD COLUMN $column $definition");
            echo "✅ Added order column: $column<br>";
        } catch (Exception $e) {
            if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
                echo "ℹ️ Order column $column already exists<br>";
            } else {
                echo "❌ Error adding order column $column: " . $e->getMessage() . "<br>";
            }
        }
    }

    // 7. Add vendor_id to order_items
    echo "<h3>7. Updating Order Items Table</h3>";
    try {
        $database->execute("ALTER TABLE order_items ADD COLUMN vendor_id INT DEFAULT NULL");
        echo "✅ Added vendor_id to order_items<br>";
    } catch (Exception $e) {
        if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
            echo "ℹ️ vendor_id column already exists in order_items<br>";
        } else {
            echo "❌ Error adding vendor_id to order_items: " . $e->getMessage() . "<br>";
        }
    }

    // 8. Create activity_logs table
    echo "<h3>8. Setting up Activity Logs Table</h3>";
    $database->execute("
        CREATE TABLE IF NOT EXISTS activity_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            action VARCHAR(100) NOT NULL,
            description TEXT,
            ip_address VARCHAR(45),
            user_agent TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_user_id (user_id),
            INDEX idx_created_at (created_at),
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )
    ");
    echo "✅ Activity logs table created/verified<br>";

    // 9. Insert sample categories
    echo "<h3>9. Adding Sample Categories</h3>";
    $categories = [
        ['Electronics', 'Electronic devices and gadgets'],
        ['Clothing', 'Fashion and apparel'],
        ['Home & Garden', 'Home improvement and garden supplies'],
        ['Books', 'Books and educational materials'],
        ['Sports', 'Sports and fitness equipment'],
        ['Food & Beverages', 'Food items and drinks'],
        ['Health & Beauty', 'Health and beauty products'],
        ['Automotive', 'Car parts and accessories'],
        ['Toys & Games', 'Toys and gaming products'],
        ['Office Supplies', 'Office and business supplies']
    ];

    foreach ($categories as $cat) {
        try {
            $database->execute(
                "INSERT INTO categories (name, description, status) VALUES (?, ?, 'active')",
                [$cat[0], $cat[1]]
            );
            echo "✅ Added category: {$cat[0]}<br>";
        } catch (Exception $e) {
            if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                echo "ℹ️ Category {$cat[0]} already exists<br>";
            } else {
                echo "❌ Error adding category {$cat[0]}: " . $e->getMessage() . "<br>";
            }
        }
    }

    // 10. Update existing order_items with vendor_id
    echo "<h3>10. Updating Existing Order Items</h3>";
    $updated = $database->execute("
        UPDATE order_items oi 
        JOIN products p ON oi.product_id = p.id 
        SET oi.vendor_id = p.vendor_id 
        WHERE oi.vendor_id IS NULL
    ");
    echo "✅ Updated order items with vendor_id<br>";

    // 11. Add indexes for performance
    echo "<h3>11. Adding Performance Indexes</h3>";
    $indexes = [
        "CREATE INDEX IF NOT EXISTS idx_products_vendor_status ON products(vendor_id, status)",
        "CREATE INDEX IF NOT EXISTS idx_products_category ON products(category_id)",
        "CREATE INDEX IF NOT EXISTS idx_order_items_vendor ON order_items(vendor_id)",
        "CREATE INDEX IF NOT EXISTS idx_orders_status ON orders(status)",
        "CREATE INDEX IF NOT EXISTS idx_orders_payment_status ON orders(payment_status)"
    ];

    foreach ($indexes as $index_sql) {
        try {
            $database->execute($index_sql);
            echo "✅ Index created<br>";
        } catch (Exception $e) {
            echo "ℹ️ Index may already exist<br>";
        }
    }

    echo "<h3>✅ Database Setup Complete!</h3>";
    echo "<p style='color: green; font-weight: bold;'>All tables and columns have been set up successfully.</p>";
    echo "<p><a href='../vendor/spa-dashboard.php'>Go to Vendor Dashboard</a></p>";

} catch (Exception $e) {
    echo "<h3>❌ Setup Error</h3>";
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?>
