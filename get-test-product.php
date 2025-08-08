<?php
require_once 'config/config.php';

$product = $database->fetch('SELECT id, name FROM products LIMIT 1');
if ($product) {
    echo "Test product found: ID {$product['id']} - {$product['name']}<br>";
    echo "<a href='product.php?id={$product['id']}'>View Product Page</a>";
} else {
    echo "No products found in database";
}
?>
