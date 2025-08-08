<?php
// Simple index test - no complex includes
session_start();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Simple Index Test</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; line-height: 1.6; }
        .container { max-width: 1200px; margin: 0 auto; }
        .product-card { border: 1px solid #ddd; padding: 15px; margin: 10px; border-radius: 5px; display: inline-block; width: 250px; }
        .btn { background: #10b981; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🏪 MarketHub - Simple Test</h1>
        <p>This is a simplified version to test if the basic functionality works.</p>
        
        <h2>Products</h2>
        
        <?php
        try {
            // Direct database connection
            $pdo = new PDO('mysql:host=localhost;dbname=markethub;charset=utf8mb4', 'root', '');
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Get products directly
            $stmt = $pdo->prepare("SELECT id, name, price, image_url FROM products WHERE status = 'active' LIMIT 6");
            $stmt->execute();
            $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (empty($products)) {
                echo "<p>No products found.</p>";
            } else {
                foreach ($products as $product) {
                    echo "<div class='product-card'>";
                    echo "<h3>" . htmlspecialchars($product['name']) . "</h3>";
                    echo "<p>Price: RWF " . number_format($product['price'], 2) . "</p>";
                    if ($product['image_url']) {
                        echo "<img src='" . htmlspecialchars($product['image_url']) . "' style='width: 100px; height: 100px; object-fit: cover;' onerror='this.style.display=\"none\"'>";
                    }
                    echo "<br><br>";
                    echo "<a href='simple-product.php?id=" . $product['id'] . "' class='btn'>View Product</a>";
                    echo "</div>";
                }
            }
            
        } catch (Exception $e) {
            echo "<p style='color: red;'>Database Error: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
        ?>
        
        <h2>Navigation Test</h2>
        <p>
            <a href="simple-index.php" class="btn">Simple Home</a>
            <a href="index.php" class="btn">Full Index</a>
            <a href="products.php" class="btn">Full Products</a>
            <a href="minimal-test.php" class="btn">Minimal Test</a>
        </p>
        
        <h2>Debug Info</h2>
        <p>
            <strong>Current Time:</strong> <?php echo date('Y-m-d H:i:s'); ?><br>
            <strong>PHP Version:</strong> <?php echo phpversion(); ?><br>
            <strong>Session Status:</strong> <?php echo session_status() === PHP_SESSION_ACTIVE ? 'Active' : 'Inactive'; ?><br>
            <strong>Memory Usage:</strong> <?php echo number_format(memory_get_usage() / 1024 / 1024, 2); ?> MB<br>
        </p>
    </div>
    
    <script>
        console.log('✅ Simple index page loaded successfully');
        console.log('Current URL:', window.location.href);
        console.log('Document ready state:', document.readyState);
        
        // Test if page loads completely
        window.addEventListener('load', function() {
            console.log('✅ Simple index page fully loaded');
            document.body.innerHTML += '<div style="background: #d4edda; padding: 10px; margin: 20px 0; border-radius: 5px;"><strong>✅ Page Load Complete</strong> - Simple index loaded successfully</div>';
        });
    </script>
</body>
</html>
