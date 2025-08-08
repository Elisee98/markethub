<?php
/**
 * Setup E-Commerce Features
 * Create additional tables for product comparison and recommendations
 */

require_once 'config/config.php';

echo "<h1>🛒 Setting Up Advanced E-Commerce Features</h1>";

try {
    // Create product_comparisons table
    echo "<h2>📊 Creating Product Comparison Tables</h2>";
    
    $comparison_table_sql = "
    CREATE TABLE IF NOT EXISTS product_comparisons (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT DEFAULT NULL,
        session_id VARCHAR(255) DEFAULT NULL,
        product_id INT NOT NULL,
        comparison_group_id VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        INDEX idx_user_id (user_id),
        INDEX idx_session_id (session_id),
        INDEX idx_comparison_group (comparison_group_id),
        INDEX idx_product_id (product_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ";
    
    $database->execute($comparison_table_sql);
    echo "✅ Created product_comparisons table<br>";
    
    // Create user_interactions table for recommendations
    echo "<h2>🤖 Creating Recommendation System Tables</h2>";
    
    $interactions_table_sql = "
    CREATE TABLE IF NOT EXISTS user_interactions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT DEFAULT NULL,
        session_id VARCHAR(255) DEFAULT NULL,
        product_id INT NOT NULL,
        vendor_id INT NOT NULL,
        interaction_type ENUM('view', 'wishlist', 'cart', 'purchase', 'compare', 'search') NOT NULL,
        interaction_value DECIMAL(10,2) DEFAULT NULL,
        metadata JSON DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
        FOREIGN KEY (vendor_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        INDEX idx_user_id (user_id),
        INDEX idx_session_id (session_id),
        INDEX idx_product_id (product_id),
        INDEX idx_vendor_id (vendor_id),
        INDEX idx_interaction_type (interaction_type),
        INDEX idx_created_at (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ";
    
    $database->execute($interactions_table_sql);
    echo "✅ Created user_interactions table<br>";
    
    // Create product_recommendations table
    $recommendations_table_sql = "
    CREATE TABLE IF NOT EXISTS product_recommendations (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT DEFAULT NULL,
        product_id INT NOT NULL,
        recommended_product_id INT NOT NULL,
        recommendation_type ENUM('collaborative', 'content_based', 'popular', 'vendor_based', 'category_based') NOT NULL,
        score DECIMAL(5,3) NOT NULL DEFAULT 0.000,
        reason TEXT DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        expires_at TIMESTAMP DEFAULT NULL,
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
        FOREIGN KEY (recommended_product_id) REFERENCES products(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        INDEX idx_user_id (user_id),
        INDEX idx_product_id (product_id),
        INDEX idx_recommended_product_id (recommended_product_id),
        INDEX idx_recommendation_type (recommendation_type),
        INDEX idx_score (score),
        INDEX idx_expires_at (expires_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ";
    
    $database->execute($recommendations_table_sql);
    echo "✅ Created product_recommendations table<br>";
    
    // Create shared_lists table for sharing wishlists/comparisons
    echo "<h2>🔗 Creating Sharing Features Tables</h2>";
    
    $shared_lists_sql = "
    CREATE TABLE IF NOT EXISTS shared_lists (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        list_type ENUM('wishlist', 'comparison', 'cart') NOT NULL,
        list_name VARCHAR(255) NOT NULL,
        share_token VARCHAR(255) UNIQUE NOT NULL,
        is_public TINYINT(1) DEFAULT 0,
        password_hash VARCHAR(255) DEFAULT NULL,
        expires_at TIMESTAMP DEFAULT NULL,
        view_count INT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        INDEX idx_user_id (user_id),
        INDEX idx_share_token (share_token),
        INDEX idx_list_type (list_type),
        INDEX idx_is_public (is_public)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ";
    
    $database->execute($shared_lists_sql);
    echo "✅ Created shared_lists table<br>";
    
    // Create shared_list_items table
    $shared_list_items_sql = "
    CREATE TABLE IF NOT EXISTS shared_list_items (
        id INT AUTO_INCREMENT PRIMARY KEY,
        shared_list_id INT NOT NULL,
        product_id INT NOT NULL,
        quantity INT DEFAULT 1,
        notes TEXT DEFAULT NULL,
        added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (shared_list_id) REFERENCES shared_lists(id) ON DELETE CASCADE,
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
        INDEX idx_shared_list_id (shared_list_id),
        INDEX idx_product_id (product_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ";
    
    $database->execute($shared_list_items_sql);
    echo "✅ Created shared_list_items table<br>";
    
    // Create product_tags table for better categorization
    echo "<h2>🏷️ Creating Product Tags System</h2>";
    
    $product_tags_sql = "
    CREATE TABLE IF NOT EXISTS product_tags (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL UNIQUE,
        slug VARCHAR(100) NOT NULL UNIQUE,
        color VARCHAR(7) DEFAULT '#007bff',
        description TEXT DEFAULT NULL,
        is_system_tag TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_slug (slug),
        INDEX idx_is_system_tag (is_system_tag)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ";
    
    $database->execute($product_tags_sql);
    echo "✅ Created product_tags table<br>";
    
    // Create product_tag_assignments table
    $product_tag_assignments_sql = "
    CREATE TABLE IF NOT EXISTS product_tag_assignments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        product_id INT NOT NULL,
        tag_id INT NOT NULL,
        assigned_by INT DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
        FOREIGN KEY (tag_id) REFERENCES product_tags(id) ON DELETE CASCADE,
        FOREIGN KEY (assigned_by) REFERENCES users(id) ON DELETE SET NULL,
        UNIQUE KEY unique_product_tag (product_id, tag_id),
        INDEX idx_product_id (product_id),
        INDEX idx_tag_id (tag_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ";
    
    $database->execute($product_tag_assignments_sql);
    echo "✅ Created product_tag_assignments table<br>";
    
    // Add some default system tags
    echo "<h2>🏷️ Adding Default Product Tags</h2>";
    
    $default_tags = [
        ['name' => 'Best Value', 'slug' => 'best-value', 'color' => '#28a745', 'description' => 'Great quality for the price'],
        ['name' => 'Top Seller', 'slug' => 'top-seller', 'color' => '#ffc107', 'description' => 'Most popular product'],
        ['name' => 'New Arrival', 'slug' => 'new-arrival', 'color' => '#17a2b8', 'description' => 'Recently added product'],
        ['name' => 'Limited Stock', 'slug' => 'limited-stock', 'color' => '#fd7e14', 'description' => 'Low inventory warning'],
        ['name' => 'Premium Quality', 'slug' => 'premium-quality', 'color' => '#6f42c1', 'description' => 'High-end product'],
        ['name' => 'Fast Shipping', 'slug' => 'fast-shipping', 'color' => '#20c997', 'description' => 'Quick delivery available'],
        ['name' => 'Eco Friendly', 'slug' => 'eco-friendly', 'color' => '#198754', 'description' => 'Environmentally conscious'],
        ['name' => 'Customer Choice', 'slug' => 'customer-choice', 'color' => '#e91e63', 'description' => 'Highly rated by customers']
    ];
    
    foreach ($default_tags as $tag) {
        $existing = $database->fetch("SELECT id FROM product_tags WHERE slug = ?", [$tag['slug']]);
        if (!$existing) {
            $database->execute(
                "INSERT INTO product_tags (name, slug, color, description, is_system_tag) VALUES (?, ?, ?, ?, 1)",
                [$tag['name'], $tag['slug'], $tag['color'], $tag['description']]
            );
            echo "✅ Added tag: {$tag['name']}<br>";
        } else {
            echo "ℹ️ Tag already exists: {$tag['name']}<br>";
        }
    }
    
    // Add some sample interactions for testing recommendations
    echo "<h2>🤖 Adding Sample User Interactions</h2>";
    
    $customers = $database->fetchAll("SELECT id FROM users WHERE user_type = 'customer' LIMIT 3");
    $products = $database->fetchAll("SELECT id, vendor_id FROM products WHERE status = 'active' LIMIT 10");
    
    if (!empty($customers) && !empty($products)) {
        $interaction_types = ['view', 'wishlist', 'cart', 'compare'];
        $interactions_added = 0;
        
        foreach ($customers as $customer) {
            foreach ($products as $product) {
                if (rand(1, 3) == 1) { // 33% chance to create interaction
                    $interaction_type = $interaction_types[array_rand($interaction_types)];
                    
                    $existing = $database->fetch(
                        "SELECT id FROM user_interactions WHERE user_id = ? AND product_id = ? AND interaction_type = ?",
                        [$customer['id'], $product['id'], $interaction_type]
                    );
                    
                    if (!$existing) {
                        $database->execute(
                            "INSERT INTO user_interactions (user_id, product_id, vendor_id, interaction_type, created_at) 
                             VALUES (?, ?, ?, ?, NOW() - INTERVAL FLOOR(RAND() * 30) DAY)",
                            [$customer['id'], $product['id'], $product['vendor_id'], $interaction_type]
                        );
                        $interactions_added++;
                    }
                }
            }
        }
        
        echo "✅ Added {$interactions_added} sample interactions<br>";
    }
    
    // Show summary
    echo "<h2>📊 Setup Summary</h2>";
    
    $tables_created = [
        'product_comparisons' => 'Product comparison functionality',
        'user_interactions' => 'User behavior tracking for recommendations',
        'product_recommendations' => 'AI-powered product suggestions',
        'shared_lists' => 'Shareable wishlists and comparisons',
        'shared_list_items' => 'Items in shared lists',
        'product_tags' => 'Product tagging system',
        'product_tag_assignments' => 'Product-tag relationships'
    ];
    
    echo "<div style='display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 15px; margin: 20px 0;'>";
    
    foreach ($tables_created as $table => $description) {
        try {
            $count = $database->fetch("SELECT COUNT(*) as count FROM {$table}")['count'];
            echo "<div style='background: white; border: 2px solid #28a745; padding: 15px; border-radius: 8px;'>";
            echo "<h4 style='margin: 0 0 10px 0; color: #28a745;'>{$table}</h4>";
            echo "<p style='margin: 0 0 10px 0; color: #666; font-size: 0.9rem;'>{$description}</p>";
            echo "<p style='margin: 0; color: #28a745; font-weight: bold;'>✅ {$count} records</p>";
            echo "</div>";
        } catch (Exception $e) {
            echo "<div style='background: white; border: 2px solid #dc3545; padding: 15px; border-radius: 8px;'>";
            echo "<h4 style='margin: 0 0 10px 0; color: #dc3545;'>{$table}</h4>";
            echo "<p style='margin: 0; color: #dc3545;'>❌ Error: " . $e->getMessage() . "</p>";
            echo "</div>";
        }
    }
    
    echo "</div>";
    
    echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
    echo "<h3 style='color: #155724; margin: 0 0 15px 0;'>🎉 E-Commerce Features Setup Complete!</h3>";
    echo "<ul style='color: #155724; margin: 0;'>";
    echo "<li>✅ Product comparison system ready</li>";
    echo "<li>✅ Recommendation engine database prepared</li>";
    echo "<li>✅ Sharing functionality enabled</li>";
    echo "<li>✅ Product tagging system active</li>";
    echo "<li>✅ User interaction tracking configured</li>";
    echo "</ul>";
    echo "</div>";
    
    echo "<h2>🧪 Next Steps</h2>";
    echo "<div style='display: flex; gap: 15px; flex-wrap: wrap; margin: 20px 0;'>";
    echo "<a href='wishlist.php' style='background: #e91e63; color: white; padding: 12px 20px; text-decoration: none; border-radius: 6px;'>❤️ Test Wishlist</a>";
    echo "<a href='cart.php' style='background: #007bff; color: white; padding: 12px 20px; text-decoration: none; border-radius: 6px;'>🛒 Test Cart</a>";
    echo "<a href='compare.php' style='background: #28a745; color: white; padding: 12px 20px; text-decoration: none; border-radius: 6px;'>📊 Test Compare</a>";
    echo "<a href='products.php' style='background: #ffc107; color: black; padding: 12px 20px; text-decoration: none; border-radius: 6px;'>🛍️ Browse Products</a>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h3 style='color: #721c24; margin: 0 0 10px 0;'>❌ Setup Error</h3>";
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
</style>
