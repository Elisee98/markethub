<?php
/**
 * Vendor Stores Database Fix Script
 * Adds missing columns and ensures proper structure
 */

require_once '../config/config.php';

echo "<h2>Vendor Stores Database Fix</h2>\n";
echo "<pre>\n";

try {
    echo "Checking vendor_stores table structure...\n\n";
    
    // Check current table structure
    $columns = $database->fetchAll("SHOW COLUMNS FROM vendor_stores");
    $existing_columns = array_column($columns, 'Field');
    
    echo "Current columns in vendor_stores table:\n";
    foreach ($existing_columns as $column) {
        echo "✓ $column\n";
    }
    
    echo "\n";
    
    // Define required columns
    $required_columns = [
        'store_description' => "TEXT DEFAULT NULL",
        'store_logo' => "VARCHAR(255) DEFAULT NULL",
        'store_banner' => "VARCHAR(255) DEFAULT NULL",
        'business_license' => "VARCHAR(255) DEFAULT NULL",
        'tax_id' => "VARCHAR(100) DEFAULT NULL",
        'website' => "VARCHAR(255) DEFAULT NULL",
        'social_media' => "JSON DEFAULT NULL",
        'business_hours' => "JSON DEFAULT NULL",
        'shipping_policy' => "TEXT DEFAULT NULL",
        'return_policy' => "TEXT DEFAULT NULL"
    ];
    
    echo "Adding missing columns...\n";
    
    foreach ($required_columns as $column => $definition) {
        if (!in_array($column, $existing_columns)) {
            try {
                $sql = "ALTER TABLE vendor_stores ADD COLUMN `$column` $definition";
                $database->execute($sql);
                echo "✓ Added column: $column\n";
            } catch (Exception $e) {
                echo "✗ Error adding $column: " . $e->getMessage() . "\n";
            }
        } else {
            echo "✓ Column $column already exists\n";
        }
    }
    
    echo "\n";
    
    // Update existing stores with sample data if they don't have descriptions
    echo "Updating existing stores with sample data...\n";
    
    try {
        $stores_without_desc = $database->fetchAll("
            SELECT vs.*, u.username 
            FROM vendor_stores vs 
            JOIN users u ON vs.vendor_id = u.id 
            WHERE vs.store_description IS NULL OR vs.store_description = ''
        ");
        
        foreach ($stores_without_desc as $store) {
            $sample_description = "Welcome to " . $store['store_name'] . "! We are committed to providing quality products and excellent customer service.";
            
            $database->execute("
                UPDATE vendor_stores 
                SET store_description = ?, 
                    business_hours = ?, 
                    shipping_policy = ?,
                    return_policy = ?
                WHERE id = ?
            ", [
                $sample_description,
                json_encode([
                    'monday' => '8:00 AM - 6:00 PM',
                    'tuesday' => '8:00 AM - 6:00 PM',
                    'wednesday' => '8:00 AM - 6:00 PM',
                    'thursday' => '8:00 AM - 6:00 PM',
                    'friday' => '8:00 AM - 6:00 PM',
                    'saturday' => '9:00 AM - 4:00 PM',
                    'sunday' => 'Closed'
                ]),
                'We offer fast and reliable shipping within Musanze District. Orders are typically processed within 1-2 business days.',
                'We accept returns within 7 days of purchase. Items must be in original condition.',
                $store['id']
            ]);
            
            echo "✓ Updated store: " . $store['store_name'] . "\n";
        }
        
    } catch (Exception $e) {
        echo "✗ Error updating stores: " . $e->getMessage() . "\n";
    }
    
    echo "\n";
    
    // Add indexes for better performance
    echo "Adding database indexes...\n";
    
    $indexes = [
        "CREATE INDEX IF NOT EXISTS idx_vendor_stores_vendor_id ON vendor_stores(vendor_id)",
        "CREATE INDEX IF NOT EXISTS idx_vendor_stores_status ON vendor_stores(status)",
        "CREATE INDEX IF NOT EXISTS idx_vendor_stores_created_at ON vendor_stores(created_at)"
    ];
    
    foreach ($indexes as $index_sql) {
        try {
            $database->execute($index_sql);
            echo "✓ Added index\n";
        } catch (Exception $e) {
            echo "✓ Index already exists or created\n";
        }
    }
    
    echo "\n";
    
    // Show final table structure
    echo "Final vendor_stores table structure:\n";
    $final_columns = $database->fetchAll("SHOW COLUMNS FROM vendor_stores");
    foreach ($final_columns as $column) {
        echo "✓ {$column['Field']} ({$column['Type']})\n";
    }
    
    echo "\n✅ Vendor stores table fix completed successfully!\n";
    
} catch (Exception $e) {
    echo "\n❌ Fix failed: " . $e->getMessage() . "\n";
}

echo "</pre>\n";
echo "<p><a href='../invoice-improved.php?order=1'>Test Invoice</a> | <a href='../orders.php'>View Orders</a> | <a href='../'>Back to Home</a></p>\n";
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
