<?php
/**
 * Fix Activity Logs - Clean up invalid entries and check constraints
 */

require_once 'config/config.php';

echo "<h1>🔧 Activity Logs Fix</h1>";

// Check users table
echo "<h2>1. Users Table Check</h2>";
try {
    $users = $database->fetchAll("SELECT id, username, user_type, status FROM users ORDER BY id");
    echo "✅ Found " . count($users) . " users:<br>";
    
    foreach ($users as $user) {
        echo "&nbsp;&nbsp;- ID: {$user['id']}, Username: {$user['username']}, Type: {$user['user_type']}, Status: {$user['status']}<br>";
    }
    
} catch (Exception $e) {
    echo "❌ Error checking users: " . $e->getMessage() . "<br>";
}

// Check activity_logs table
echo "<h2>2. Activity Logs Table Check</h2>";
try {
    $activity_count = $database->fetch("SELECT COUNT(*) as count FROM activity_logs");
    echo "✅ Activity logs table exists with {$activity_count['count']} entries<br>";
    
    // Check for invalid user_ids
    $invalid_logs = $database->fetchAll(
        "SELECT al.id, al.user_id, al.action, al.created_at 
         FROM activity_logs al 
         LEFT JOIN users u ON al.user_id = u.id 
         WHERE u.id IS NULL AND al.user_id IS NOT NULL"
    );
    
    if (!empty($invalid_logs)) {
        echo "⚠️ Found " . count($invalid_logs) . " activity logs with invalid user_ids:<br>";
        foreach ($invalid_logs as $log) {
            echo "&nbsp;&nbsp;- Log ID: {$log['id']}, Invalid User ID: {$log['user_id']}, Action: {$log['action']}<br>";
        }
        
        // Option to clean them up
        echo "<br><a href='?cleanup=1' style='background: #ef4444; color: white; padding: 10px; text-decoration: none; border-radius: 5px;'>Clean Up Invalid Logs</a><br>";
    } else {
        echo "✅ All activity logs have valid user_ids<br>";
    }
    
} catch (Exception $e) {
    echo "❌ Error checking activity logs: " . $e->getMessage() . "<br>";
}

// Clean up invalid logs if requested
if (isset($_GET['cleanup']) && $_GET['cleanup'] == '1') {
    echo "<h2>3. Cleaning Up Invalid Logs</h2>";
    try {
        $deleted = $database->execute(
            "DELETE al FROM activity_logs al 
             LEFT JOIN users u ON al.user_id = u.id 
             WHERE u.id IS NULL AND al.user_id IS NOT NULL"
        );
        
        echo "✅ Cleaned up invalid activity log entries<br>";
        echo "<a href='?' style='background: #10b981; color: white; padding: 10px; text-decoration: none; border-radius: 5px;'>Refresh Page</a><br>";
        
    } catch (Exception $e) {
        echo "❌ Error cleaning up: " . $e->getMessage() . "<br>";
    }
}

// Test activity logging with valid user
echo "<h2>4. Test Activity Logging</h2>";
try {
    $valid_user = $database->fetch("SELECT id FROM users WHERE status = 'active' LIMIT 1");
    
    if ($valid_user) {
        echo "✅ Testing with valid user ID: {$valid_user['id']}<br>";
        
        // Test the logActivity function
        $result = logActivity($valid_user['id'], 'test_fix', 'Testing activity logging fix');
        
        if ($result) {
            echo "✅ Activity logging test successful<br>";
            
            // Clean up test entry
            $database->execute("DELETE FROM activity_logs WHERE action = 'test_fix' AND description = 'Testing activity logging fix'");
            echo "✅ Test entry cleaned up<br>";
        } else {
            echo "❌ Activity logging test failed<br>";
        }
    } else {
        echo "❌ No valid users found for testing<br>";
    }
    
} catch (Exception $e) {
    echo "❌ Test error: " . $e->getMessage() . "<br>";
}

// Check foreign key constraints
echo "<h2>5. Foreign Key Constraints Check</h2>";
try {
    $constraints = $database->fetchAll(
        "SELECT CONSTRAINT_NAME, COLUMN_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME 
         FROM information_schema.KEY_COLUMN_USAGE 
         WHERE TABLE_SCHEMA = 'markethub' 
         AND TABLE_NAME = 'activity_logs' 
         AND REFERENCED_TABLE_NAME IS NOT NULL"
    );
    
    if (!empty($constraints)) {
        echo "✅ Foreign key constraints found:<br>";
        foreach ($constraints as $constraint) {
            echo "&nbsp;&nbsp;- {$constraint['CONSTRAINT_NAME']}: {$constraint['COLUMN_NAME']} → {$constraint['REFERENCED_TABLE_NAME']}.{$constraint['REFERENCED_COLUMN_NAME']}<br>";
        }
    } else {
        echo "⚠️ No foreign key constraints found<br>";
    }
    
} catch (Exception $e) {
    echo "❌ Error checking constraints: " . $e->getMessage() . "<br>";
}

// Recommendations
echo "<h2>6. Recommendations</h2>";
echo "<div style='background: #f0f9ff; border: 1px solid #0ea5e9; padding: 15px; border-radius: 5px;'>";
echo "<strong>✅ Activity Logging Fix Applied:</strong><br>";
echo "• Updated logActivity() function to validate user_id before inserting<br>";
echo "• Function now checks if user exists before logging activity<br>";
echo "• Invalid user_ids are silently skipped to prevent errors<br>";
echo "• This prevents foreign key constraint violations<br>";
echo "</div>";

?>

<style>
body { font-family: Arial, sans-serif; padding: 20px; line-height: 1.6; max-width: 800px; margin: 0 auto; }
h1 { color: #10b981; }
h2 { color: #374151; border-bottom: 2px solid #e5e7eb; padding-bottom: 5px; margin-top: 30px; }
</style>
