<?php
/**
 * MarketHub Database Fix Script
 * Run this script to add missing tables and columns
 */

require_once __DIR__ . '/../config/config.php';

echo "MarketHub Database Fix Script\n";
echo "============================\n\n";

try {
    // Read the SQL file
    $sql_file = __DIR__ . '/missing_tables.sql';
    
    if (!file_exists($sql_file)) {
        throw new Exception("SQL file not found: $sql_file");
    }
    
    $sql_content = file_get_contents($sql_file);
    
    if (!$sql_content) {
        throw new Exception("Could not read SQL file");
    }
    
    echo "Reading SQL file... ✓\n";
    
    // Split SQL into individual statements
    $statements = array_filter(
        array_map('trim', explode(';', $sql_content)),
        function($stmt) {
            return !empty($stmt) && !preg_match('/^\s*--/', $stmt);
        }
    );
    
    echo "Found " . count($statements) . " SQL statements\n\n";
    
    $success_count = 0;
    $error_count = 0;
    
    // Execute each statement
    foreach ($statements as $index => $statement) {
        $statement = trim($statement);
        if (empty($statement)) continue;
        
        try {
            // Get the first line for display
            $first_line = strtok($statement, "\n");
            $display_text = strlen($first_line) > 60 ? substr($first_line, 0, 60) . '...' : $first_line;
            
            echo "Executing: $display_text\n";
            
            $database->execute($statement);
            echo "  ✓ Success\n";
            $success_count++;
            
        } catch (Exception $e) {
            $error_message = $e->getMessage();
            
            // Check if it's a harmless error (table/column already exists)
            if (strpos($error_message, 'already exists') !== false || 
                strpos($error_message, 'Duplicate column') !== false ||
                strpos($error_message, 'Duplicate key') !== false) {
                echo "  ⚠ Already exists (skipped)\n";
                $success_count++;
            } else {
                echo "  ✗ Error: " . $error_message . "\n";
                $error_count++;
            }
        }
        
        echo "\n";
    }
    
    echo "============================\n";
    echo "Database Fix Complete!\n";
    echo "Successful: $success_count\n";
    echo "Errors: $error_count\n";
    
    if ($error_count === 0) {
        echo "\n✅ All database fixes applied successfully!\n";
        echo "You can now use all features of MarketHub.\n";
    } else {
        echo "\n⚠️ Some errors occurred. Please check the output above.\n";
    }
    
    // Test the fixes
    echo "\nTesting database fixes...\n";
    testDatabaseFixes();
    
} catch (Exception $e) {
    echo "❌ Fatal error: " . $e->getMessage() . "\n";
    exit(1);
}

function testDatabaseFixes() {
    global $database;
    
    $tests = [
        'activity_logs table' => "SELECT 1 FROM activity_logs LIMIT 1",
        'category_images table' => "SELECT 1 FROM category_images LIMIT 1",
        'contact_inquiries table' => "SELECT 1 FROM contact_inquiries LIMIT 1",
        'customer_profiles table' => "SELECT 1 FROM customer_profiles LIMIT 1",
        'payments table' => "SELECT 1 FROM payments LIMIT 1",
        'products.original_price column' => "SELECT original_price FROM products LIMIT 1",
        'users.email_verification_token column' => "SELECT email_verification_token FROM users LIMIT 1",
        'orders.special_instructions column' => "SELECT special_instructions FROM orders LIMIT 1",
        'categories.image_url column' => "SELECT image_url FROM categories LIMIT 1"
    ];
    
    $passed = 0;
    $failed = 0;
    
    foreach ($tests as $test_name => $query) {
        try {
            $database->fetch($query);
            echo "  ✓ $test_name\n";
            $passed++;
        } catch (Exception $e) {
            echo "  ✗ $test_name: " . $e->getMessage() . "\n";
            $failed++;
        }
    }
    
    echo "\nTest Results: $passed passed, $failed failed\n";
    
    if ($failed === 0) {
        echo "🎉 All tests passed! Database is ready.\n";
    }
}

// If running from command line
if (php_sapi_name() === 'cli') {
    echo "\nPress Enter to continue or Ctrl+C to cancel...";
    fgets(STDIN);
}
?>
