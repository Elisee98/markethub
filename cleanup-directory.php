<?php
/**
 * Directory Cleanup Script
 * Clean up unnecessary files and organize the project structure
 */

echo "<h1>🧹 MarketHub Directory Cleanup</h1>";

// Files to be removed (debug, test, temporary files)
$files_to_remove = [
    // Debug files
    'debug-customer-pages.php',
    'debug-system.php',
    'deep-debug.php',
    'products-debug.php',
    'admin-login-test.php',
    
    // Test files
    'test-all-functionality.php',
    'test-category-filtering.php',
    'test-database.php',
    'test-ecommerce-features.php',
    'test-functions.php',
    'test-product-redirect.php',
    'test-search-functionality.php',
    'test-simple-page.php',
    'minimal-test.php',
    'final-test.php',
    'final-system-test.php',
    
    // Temporary/setup files
    'add-all-images-to-database.php',
    'check-missing-tables.php',
    'complete-system-fix.php',
    'create-admin-user.php',
    'create-test-users.php',
    'create-user-addresses-table.php',
    'expand-marketplace.php',
    'final-marketplace-status.php',
    'fix-activity-logs.php',
    'fix-products-categories.php',
    'marketplace-summary.php',
    'setup-ecommerce-features.php',
    'simple-index.php',
    'update-vendor-profiles.php',
    'verify-all-images-added.php',
    'verify-search-fixed.php',
    
    // Duplicate/old files
    'search-simple.php', // We have search.php
    'products.sql', // Database schema is in database/schema.sql
    'markethub.sql', // Duplicate database file
    
    // Vendor debug files
    'vendor/debug-add-product.php',
    'vendor/debug-ajax.php',
    'vendor/debug-form.php',
    'vendor/debug-navigation.php',
    'vendor/debug-step-by-step.php',
    'vendor/test-add-product.php',
    'vendor/test-buttons.html',
    'vendor/test-buttons.php',
    'vendor/test-categories.php',
    'vendor/test-form.php',
    'vendor/test-image-upload.html',
    'vendor/verify-products.php',
    'vendor/activate-products.php',
    'vendor/create-product-images.php',
    'vendor/add-sample-products.php',
    
    // Admin debug files
    'admin/fix-database.php',
    'admin/email-test.php',
    'admin/create-admin.php',
    
    // Database debug files
    'database/fix_database.php',
];

// Directories to be removed (if empty after cleanup)
$dirs_to_check = [
    'customer', // Empty directory
    'tests', // If we remove test files
];

echo "<h2>📋 Cleanup Plan</h2>";

echo "<h3>🗑️ Files to Remove (" . count($files_to_remove) . " files)</h3>";
echo "<div style='background: #fff3cd; padding: 1rem; border-radius: 6px; margin: 1rem 0;'>";
echo "<strong>Categories:</strong><br>";
echo "• Debug files: " . count(array_filter($files_to_remove, fn($f) => strpos($f, 'debug') !== false)) . " files<br>";
echo "• Test files: " . count(array_filter($files_to_remove, fn($f) => strpos($f, 'test') !== false)) . " files<br>";
echo "• Setup/temporary files: " . count(array_filter($files_to_remove, fn($f) => strpos($f, 'setup') !== false || strpos($f, 'fix') !== false || strpos($f, 'create') !== false)) . " files<br>";
echo "• Duplicate files: " . count(array_filter($files_to_remove, fn($f) => in_array($f, ['search-simple.php', 'products.sql', 'markethub.sql']))) . " files<br>";
echo "</div>";

echo "<h3>📁 Current Directory Structure</h3>";
echo "<div style='background: #f8f9fa; padding: 1rem; border-radius: 6px; margin: 1rem 0; font-family: monospace;'>";

// Show current structure
$structure = [
    '📁 Root Directory' => [
        '🏠 Core Pages' => ['index.php', 'about.php', 'contact.php', 'login.php', 'register.php'],
        '🛍️ E-commerce Pages' => ['products.php', 'product.php', 'categories.php', 'search.php'],
        '❤️ Customer Features' => ['wishlist.php', 'wishlist-enhanced.php', 'cart.php', 'cart-enhanced.php', 'compare.php'],
        '🔒 Checkout & Orders' => ['checkout.php', 'orders.php', 'order-details.php', 'order-tracking.php', 'order-confirmation.php', 'invoice.php'],
        '👤 User Management' => ['profile.php', 'addresses.php', 'dashboard.php'],
        '🏪 Vendor Pages' => ['vendors.php', 'vendor.php', 'vendor-comparison.php'],
        '📄 Special Pages' => ['deals.php', 'install.php'],
    ],
    '📁 admin/' => ['Dashboard', 'Products', 'Orders', 'Customers', 'Vendors', 'Analytics', 'Reports'],
    '📁 vendor/' => ['Dashboard', 'Products Management', 'Analytics', 'Registration'],
    '📁 api/' => ['cart.php', 'wishlist.php', 'compare.php', 'orders.php', 'payment.php', 'recommendations.php', 'reviews.php'],
    '📁 config/' => ['config.php', 'database.php', 'email-setup.php'],
    '📁 includes/' => ['header.php', 'footer.php', 'functions.php', 'auth.php', 'validation.php'],
    '📁 assets/' => ['CSS', 'JavaScript', 'Images'],
    '📁 uploads/' => ['Product Images'],
    '📁 database/' => ['schema.sql', 'missing_tables.sql'],
    '📁 docs/' => ['Documentation'],
    '📁 tools/' => ['Performance', 'Security'],
    '📁 deploy/' => ['Deployment Scripts'],
    '📁 logs/' => ['System Logs'],
];

foreach ($structure as $folder => $contents) {
    echo "<strong>$folder</strong><br>";
    if (is_array($contents)) {
        foreach ($contents as $category => $items) {
            if (is_array($items)) {
                echo "  └── <em>$category:</em> " . implode(', ', $items) . "<br>";
            } else {
                echo "  └── $items<br>";
            }
        }
    }
    echo "<br>";
}

echo "</div>";

echo "<h3>✨ After Cleanup Structure</h3>";
echo "<div style='background: #d4edda; padding: 1rem; border-radius: 6px; margin: 1rem 0;'>";
echo "<strong>Clean, organized structure with:</strong><br>";
echo "• Production-ready files only<br>";
echo "• Clear separation of concerns<br>";
echo "• No debug or test files<br>";
echo "• Organized documentation<br>";
echo "• Optimized for deployment<br>";
echo "</div>";

echo "<h2>🚀 Execute Cleanup</h2>";

if (isset($_POST['execute_cleanup'])) {
    echo "<div style='background: #d1ecf1; padding: 1rem; border-radius: 6px; margin: 1rem 0;'>";
    echo "<h4>🧹 Executing Cleanup...</h4>";
    
    $removed_count = 0;
    $failed_count = 0;
    
    foreach ($files_to_remove as $file) {
        if (file_exists($file)) {
            if (unlink($file)) {
                echo "✅ Removed: $file<br>";
                $removed_count++;
            } else {
                echo "❌ Failed to remove: $file<br>";
                $failed_count++;
            }
        } else {
            echo "ℹ️ Not found: $file<br>";
        }
    }
    
    // Check and remove empty directories
    foreach ($dirs_to_check as $dir) {
        if (is_dir($dir)) {
            $files = scandir($dir);
            $files = array_diff($files, ['.', '..']);
            if (empty($files)) {
                if (rmdir($dir)) {
                    echo "✅ Removed empty directory: $dir<br>";
                } else {
                    echo "❌ Failed to remove directory: $dir<br>";
                }
            } else {
                echo "ℹ️ Directory not empty: $dir (" . count($files) . " files)<br>";
            }
        }
    }
    
    echo "<br><strong>📊 Cleanup Summary:</strong><br>";
    echo "• Files removed: $removed_count<br>";
    echo "• Failed removals: $failed_count<br>";
    echo "• Total processed: " . count($files_to_remove) . "<br>";
    
    if ($failed_count === 0) {
        echo "<br><div style='background: #d4edda; padding: 1rem; border-radius: 6px;'>";
        echo "🎉 <strong>Cleanup completed successfully!</strong><br>";
        echo "Your MarketHub directory is now clean and organized.";
        echo "</div>";
    }
    
    echo "</div>";
    
    // Create a summary file
    $summary = "# MarketHub Directory Cleanup Summary\n\n";
    $summary .= "**Date:** " . date('Y-m-d H:i:s') . "\n";
    $summary .= "**Files Removed:** $removed_count\n";
    $summary .= "**Failed Removals:** $failed_count\n\n";
    $summary .= "## Removed Files:\n";
    foreach ($files_to_remove as $file) {
        if (!file_exists($file)) {
            $summary .= "- $file\n";
        }
    }
    
    file_put_contents('cleanup-summary.md', $summary);
    echo "<p>📄 Cleanup summary saved to: <strong>cleanup-summary.md</strong></p>";
    
} else {
    echo "<form method='POST' style='margin: 2rem 0;'>";
    echo "<div style='background: #fff3cd; padding: 1.5rem; border-radius: 6px; margin-bottom: 1rem;'>";
    echo "<h4>⚠️ Warning</h4>";
    echo "<p>This will permanently delete " . count($files_to_remove) . " files from your directory. ";
    echo "Make sure you have a backup if needed.</p>";
    echo "<p><strong>Files to be removed include:</strong></p>";
    echo "<ul>";
    echo "<li>All debug and test files</li>";
    echo "<li>Temporary setup and fix scripts</li>";
    echo "<li>Duplicate and old files</li>";
    echo "<li>Development-only files</li>";
    echo "</ul>";
    echo "</div>";
    
    echo "<div style='text-align: center;'>";
    echo "<button type='submit' name='execute_cleanup' style='background: #dc3545; color: white; padding: 1rem 2rem; border: none; border-radius: 6px; font-size: 1.1rem; cursor: pointer;'>";
    echo "🗑️ Execute Cleanup";
    echo "</button>";
    echo "</div>";
    echo "</form>";
    
    echo "<div style='background: #d1ecf1; padding: 1rem; border-radius: 6px;'>";
    echo "<h4>📋 What will remain after cleanup:</h4>";
    echo "<ul>";
    echo "<li><strong>Core Application:</strong> All main PHP pages and functionality</li>";
    echo "<li><strong>E-commerce Features:</strong> Complete shopping cart, wishlist, comparison, checkout</li>";
    echo "<li><strong>Admin Panel:</strong> Full admin dashboard and management</li>";
    echo "<li><strong>Vendor System:</strong> Vendor registration and management</li>";
    echo "<li><strong>APIs:</strong> All REST APIs for frontend functionality</li>";
    echo "<li><strong>Configuration:</strong> Database and system configuration</li>";
    echo "<li><strong>Assets:</strong> CSS, JavaScript, and images</li>";
    echo "<li><strong>Documentation:</strong> User guides and deployment docs</li>";
    echo "</ul>";
    echo "</div>";
}

echo "<h2>📁 Recommended Final Structure</h2>";
echo "<div style='background: #f8f9fa; padding: 1.5rem; border-radius: 6px; font-family: monospace; font-size: 0.9rem;'>";
echo "<strong>markethub/</strong><br>";
echo "├── 📄 <strong>Core Pages</strong><br>";
echo "│   ├── index.php, about.php, contact.php<br>";
echo "│   ├── login.php, register.php, profile.php<br>";
echo "│   └── dashboard.php, addresses.php<br>";
echo "├── 🛍️ <strong>E-commerce</strong><br>";
echo "│   ├── products.php, product.php, categories.php<br>";
echo "│   ├── search.php, deals.php<br>";
echo "│   ├── wishlist.php, wishlist-enhanced.php<br>";
echo "│   ├── cart.php, cart-enhanced.php<br>";
echo "│   └── compare.php<br>";
echo "├── 🔒 <strong>Checkout & Orders</strong><br>";
echo "│   ├── checkout.php, order-confirmation.php<br>";
echo "│   ├── orders.php, order-details.php<br>";
echo "│   ├── order-tracking.php, invoice.php<br>";
echo "├── 🏪 <strong>Vendor System</strong><br>";
echo "│   ├── vendors.php, vendor.php<br>";
echo "│   ├── vendor-comparison.php<br>";
echo "│   └── vendor/ (vendor dashboard)<br>";
echo "├── 👑 <strong>Admin System</strong><br>";
echo "│   └── admin/ (admin dashboard)<br>";
echo "├── 🔌 <strong>APIs</strong><br>";
echo "│   └── api/ (REST endpoints)<br>";
echo "├── ⚙️ <strong>Configuration</strong><br>";
echo "│   ├── config/ (system config)<br>";
echo "│   ├── includes/ (shared code)<br>";
echo "│   └── database/ (schema)<br>";
echo "├── 🎨 <strong>Assets</strong><br>";
echo "│   ├── assets/ (CSS, JS, images)<br>";
echo "│   └── uploads/ (user uploads)<br>";
echo "├── 📚 <strong>Documentation</strong><br>";
echo "│   ├── docs/ (guides)<br>";
echo "│   ├── ecommerce-features-documentation.php<br>";
echo "│   └── checkout-features-documentation.php<br>";
echo "├── 🛠️ <strong>Tools & Deployment</strong><br>";
echo "│   ├── tools/ (utilities)<br>";
echo "│   ├── deploy/ (deployment)<br>";
echo "│   └── logs/ (system logs)<br>";
echo "└── 📋 <strong>Setup</strong><br>";
echo "    └── install.php<br>";
echo "</div>";

echo "<div style='background: #d4edda; padding: 1.5rem; border-radius: 6px; margin: 2rem 0;'>";
echo "<h3>🎯 Benefits of Cleanup:</h3>";
echo "<ul>";
echo "<li><strong>🚀 Performance:</strong> Faster file system operations</li>";
echo "<li><strong>🔒 Security:</strong> No debug files exposing system info</li>";
echo "<li><strong>📦 Deployment:</strong> Smaller, cleaner deployment packages</li>";
echo "<li><strong>🧹 Maintenance:</strong> Easier to navigate and maintain</li>";
echo "<li><strong>👥 Team Work:</strong> Clear structure for developers</li>";
echo "<li><strong>📊 Professional:</strong> Production-ready appearance</li>";
echo "</ul>";
echo "</div>";

?>

<style>
body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    line-height: 1.6;
    color: #333;
    max-width: 1000px;
    margin: 0 auto;
    padding: 20px;
    background: #f8fafc;
}

h1, h2, h3 {
    color: #2d3748;
}

h1 {
    text-align: center;
    color: #10b981;
    font-size: 2.5rem;
    margin-bottom: 2rem;
}

button:hover {
    background: #c82333 !important;
    transform: translateY(-2px);
    transition: all 0.3s;
}

ul {
    padding-left: 1.5rem;
}

li {
    margin-bottom: 0.5rem;
}
</style>
