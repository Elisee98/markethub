<?php
/**
 * Checkout Database Migration Script
 * Run this script to add missing columns for the improved checkout system
 */

require_once '../config/config.php';

echo "<h2>Checkout Database Migration</h2>\n";
echo "<pre>\n";

try {
    // Check if we can connect to database
    echo "Connecting to database...\n";
    
    // Add missing columns to user_addresses table
    echo "\n1. Adding missing columns to user_addresses table...\n";
    
    try {
        $database->execute("ALTER TABLE `user_addresses` ADD COLUMN `full_name` varchar(255) DEFAULT NULL AFTER `user_id`");
        echo "✓ Added full_name column to user_addresses\n";
    } catch (Exception $e) {
        if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
            echo "✓ full_name column already exists in user_addresses\n";
        } else {
            echo "✗ Error adding full_name column: " . $e->getMessage() . "\n";
        }
    }
    
    try {
        $database->execute("ALTER TABLE `user_addresses` ADD COLUMN `phone` varchar(20) DEFAULT NULL AFTER `country`");
        echo "✓ Added phone column to user_addresses\n";
    } catch (Exception $e) {
        if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
            echo "✓ phone column already exists in user_addresses\n";
        } else {
            echo "✗ Error adding phone column: " . $e->getMessage() . "\n";
        }
    }
    
    // Add missing columns to orders table
    echo "\n2. Adding missing columns to orders table...\n";
    
    $order_columns = [
        'order_number' => "varchar(50) DEFAULT NULL AFTER `id`",
        'shipping_address_id' => "int(11) DEFAULT NULL AFTER `customer_id`",
        'billing_address_id' => "int(11) DEFAULT NULL AFTER `shipping_address_id`",
        'subtotal' => "decimal(10,2) DEFAULT NULL AFTER `total_amount`",
        'shipping_cost' => "decimal(10,2) DEFAULT 0.00 AFTER `subtotal`",
        'tax_amount' => "decimal(10,2) DEFAULT 0.00 AFTER `shipping_cost`",
        'special_instructions' => "text DEFAULT NULL"
    ];
    
    foreach ($order_columns as $column => $definition) {
        try {
            $database->execute("ALTER TABLE `orders` ADD COLUMN `$column` $definition");
            echo "✓ Added $column column to orders\n";
        } catch (Exception $e) {
            if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
                echo "✓ $column column already exists in orders\n";
            } else {
                echo "✗ Error adding $column column: " . $e->getMessage() . "\n";
            }
        }
    }
    
    // Add indexes
    echo "\n3. Adding database indexes...\n";
    
    $indexes = [
        "CREATE INDEX IF NOT EXISTS `idx_orders_order_number` ON `orders` (`order_number`)",
        "CREATE INDEX IF NOT EXISTS `idx_orders_shipping_address` ON `orders` (`shipping_address_id`)",
        "CREATE INDEX IF NOT EXISTS `idx_orders_billing_address` ON `orders` (`billing_address_id`)",
        "CREATE INDEX IF NOT EXISTS `idx_user_addresses_user_default` ON `user_addresses` (`user_id`, `is_default`)"
    ];
    
    foreach ($indexes as $index_sql) {
        try {
            $database->execute($index_sql);
            echo "✓ Added index\n";
        } catch (Exception $e) {
            echo "✓ Index already exists or created\n";
        }
    }
    
    // Update existing orders with order numbers
    echo "\n4. Updating existing orders with order numbers...\n";
    
    try {
        $result = $database->execute("
            UPDATE `orders` 
            SET `order_number` = CONCAT('ORD-', DATE_FORMAT(`created_at`, '%Y%m%d'), '-', UPPER(SUBSTRING(MD5(CONCAT(`id`, `created_at`)), 1, 6)))
            WHERE `order_number` IS NULL OR `order_number` = ''
        ");
        echo "✓ Updated existing orders with order numbers\n";
    } catch (Exception $e) {
        echo "✗ Error updating order numbers: " . $e->getMessage() . "\n";
    }
    
    // Create sample addresses for testing
    echo "\n5. Creating sample addresses for testing...\n";
    
    try {
        $customers = $database->fetchAll("SELECT id, username FROM users WHERE user_type = 'customer' LIMIT 5");
        
        foreach ($customers as $customer) {
            // Check if customer already has addresses
            $existing = $database->fetch("SELECT COUNT(*) as count FROM user_addresses WHERE user_id = ?", [$customer['id']]);
            
            if ($existing['count'] == 0) {
                $database->execute("
                    INSERT INTO `user_addresses` 
                    (`user_id`, `full_name`, `address_line_1`, `city`, `state`, `postal_code`, `country`, `phone`, `is_default`, `created_at`)
                    VALUES (?, ?, 'Sample Address Line 1', 'Musanze', 'Northern Province', '12345', 'Rwanda', '+250 XXX XXX XXX', 1, NOW())
                ", [$customer['id'], $customer['username']]);
                
                echo "✓ Created sample address for customer: " . $customer['username'] . "\n";
            } else {
                echo "✓ Customer " . $customer['username'] . " already has addresses\n";
            }
        }
    } catch (Exception $e) {
        echo "✗ Error creating sample addresses: " . $e->getMessage() . "\n";
    }
    
    echo "\n✅ Migration completed successfully!\n";
    echo "\nYou can now use the improved checkout system at: checkout-improved.php\n";
    
} catch (Exception $e) {
    echo "\n❌ Migration failed: " . $e->getMessage() . "\n";
}

echo "</pre>\n";
echo "<p><a href='../checkout-improved.php'>Test Improved Checkout</a> | <a href='../'>Back to Home</a></p>\n";
?>
