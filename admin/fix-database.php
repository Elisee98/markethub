<?php
/**
 * MarketHub Database Fix Web Interface
 * Run this page to fix database issues
 */

require_once '../config/config.php';

$page_title = 'Database Fix';
$output = '';
$success = false;

// Check if user is admin (basic check)
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    // For now, allow access if no admin is logged in (for initial setup)
    if (isset($_SESSION['user_type'])) {
        die('Access denied. Admin privileges required.');
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['run_fix'])) {
    ob_start();
    
    try {
        echo "MarketHub Database Fix\n";
        echo "=====================\n\n";
        
        // Read the SQL file
        $sql_file = '../database/missing_tables.sql';
        
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
        
        echo "=====================\n";
        echo "Database Fix Complete!\n";
        echo "Successful: $success_count\n";
        echo "Errors: $error_count\n";
        
        if ($error_count === 0) {
            echo "\n✅ All database fixes applied successfully!\n";
            echo "You can now use all features of MarketHub.\n";
            $success = true;
        } else {
            echo "\n⚠️ Some errors occurred. Please check the output above.\n";
        }
        
        // Test the fixes
        echo "\nTesting database fixes...\n";
        
        $tests = [
            'activity_logs table' => "SELECT 1 FROM activity_logs LIMIT 1",
            'category_images table' => "SELECT 1 FROM category_images LIMIT 1", 
            'contact_inquiries table' => "SELECT 1 FROM contact_inquiries LIMIT 1",
            'customer_profiles table' => "SELECT 1 FROM customer_profiles LIMIT 1",
            'payments table' => "SELECT 1 FROM payments LIMIT 1",
            'products.original_price column' => "SELECT original_price FROM products LIMIT 1",
            'users.email_verification_token column' => "SELECT email_verification_token FROM users LIMIT 1"
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
        
    } catch (Exception $e) {
        echo "❌ Fatal error: " . $e->getMessage() . "\n";
    }
    
    $output = ob_get_clean();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - MarketHub</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .fix-container {
            max-width: 800px;
            margin: 2rem auto;
            padding: 2rem;
        }
        
        .fix-header {
            background: linear-gradient(135deg, #f44336, #d32f2f);
            color: white;
            padding: 2rem;
            border-radius: 8px;
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .fix-content {
            background: white;
            border-radius: 8px;
            padding: 2rem;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .output-container {
            background: #1e1e1e;
            color: #ffffff;
            padding: 1.5rem;
            border-radius: 8px;
            font-family: 'Courier New', monospace;
            font-size: 0.9rem;
            line-height: 1.4;
            white-space: pre-wrap;
            max-height: 500px;
            overflow-y: auto;
            margin-top: 1rem;
        }
        
        .warning-box {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            color: #856404;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 2rem;
        }
        
        .success-box {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 2rem;
        }
        
        .btn-danger {
            background: #f44336;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 6px;
            font-size: 1rem;
            cursor: pointer;
            transition: background 0.3s;
        }
        
        .btn-danger:hover {
            background: #d32f2f;
        }
        
        .btn-success {
            background: #4caf50;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 6px;
            font-size: 1rem;
            text-decoration: none;
            display: inline-block;
            transition: background 0.3s;
        }
        
        .btn-success:hover {
            background: #45a049;
        }
    </style>
</head>
<body>
    <div class="fix-container">
        <div class="fix-header">
            <h1><i class="fas fa-database"></i> Database Fix Required</h1>
            <p>Some database tables and columns are missing. Run this fix to resolve the issues.</p>
        </div>
        
        <div class="fix-content">
            <?php if (!$output): ?>
                <div class="warning-box">
                    <h4><i class="fas fa-exclamation-triangle"></i> Database Issues Detected</h4>
                    <p>The following issues have been detected:</p>
                    <ul>
                        <li>Missing <code>activity_logs</code> table</li>
                        <li>Missing <code>category_images</code> table</li>
                        <li>Missing <code>contact_inquiries</code> table</li>
                        <li>Missing <code>customer_profiles</code> table</li>
                        <li>Missing <code>payments</code> table</li>
                        <li>Missing <code>original_price</code> column in products table</li>
                        <li>Missing various other columns and indexes</li>
                    </ul>
                    <p><strong>This fix will:</strong></p>
                    <ul>
                        <li>Create missing database tables</li>
                        <li>Add missing columns to existing tables</li>
                        <li>Create necessary indexes for performance</li>
                        <li>Insert default system settings</li>
                    </ul>
                </div>
                
                <form method="POST" action="" onsubmit="return confirm('Are you sure you want to run the database fix? This will modify your database structure.');">
                    <button type="submit" name="run_fix" class="btn-danger">
                        <i class="fas fa-tools"></i> Run Database Fix
                    </button>
                </form>
                
            <?php else: ?>
                <?php if ($success): ?>
                    <div class="success-box">
                        <h4><i class="fas fa-check-circle"></i> Database Fix Completed Successfully!</h4>
                        <p>All database issues have been resolved. You can now use all features of MarketHub.</p>
                        <a href="../index.php" class="btn-success">
                            <i class="fas fa-home"></i> Go to Homepage
                        </a>
                        <a href="dashboard.php" class="btn-success">
                            <i class="fas fa-tachometer-alt"></i> Go to Admin Dashboard
                        </a>
                    </div>
                <?php endif; ?>
                
                <h4>Fix Output:</h4>
                <div class="output-container"><?php echo htmlspecialchars($output); ?></div>
                
                <div style="margin-top: 2rem;">
                    <a href="<?php echo $_SERVER['PHP_SELF']; ?>" class="btn-success">
                        <i class="fas fa-redo"></i> Run Fix Again
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
