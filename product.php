<?php
/**
 * MarketHub - Individual Product Page
 */

require_once 'config/config.php';
require_once 'includes/image-helper.php';

$product_id = intval($_GET['id'] ?? 0);

if (!$product_id) {
    header('Location: products.php');
    exit;
}

// Get product details with vendor info (simplified)
$product_query = "
    SELECT p.*,
           u.username as vendor_name,
           vs.store_name, vs.store_description,
           c.name as category_name
    FROM products p
    LEFT JOIN users u ON p.vendor_id = u.id
    LEFT JOIN vendor_stores vs ON u.id = vs.vendor_id
    LEFT JOIN categories c ON p.category_id = c.id
    WHERE p.id = ? AND p.status = 'active'
";

$product = $database->fetch($product_query, [$product_id]);

if (!$product) {
    header('Location: products.php?error=product_not_found');
    exit;
}

// Get product images using the helper function
$images = getProductImagesWithFallback($product_id);

// Skip reviews for now
$reviews = [];

// Get related products (simplified)
$related_products = [];
if ($product && $product['category_id']) {
    $related_products = $database->fetchAll(
        "SELECT p.id, p.name, p.price, p.image_url
         FROM products p
         WHERE p.category_id = ? AND p.id != ? AND p.status = 'active'
         ORDER BY p.created_at DESC
         LIMIT 4",
        [$product['category_id'], $product_id]
    );
}

$page_title = $product['name'] . ' - MarketHub';
require_once 'includes/header.php';
?>

<div class="container" style="padding: 2rem 0;">
    <div class="row">
        <!-- Product Images -->
        <div class="col-6">
            <div class="product-images">
                <div class="main-image" style="margin-bottom: 1rem;">
                    <?php
                    $main_image_url = !empty($images) ? $images[0]['url'] : getImageUrl($product['image_url'], 'product');
                    echo generateImageTag(
                        $main_image_url,
                        htmlspecialchars($product['name']),
                        [
                            'id' => 'mainImage',
                            'style' => 'width: 100%; height: 400px; object-fit: cover; border-radius: 8px; border: 1px solid #e2e8f0;'
                        ],
                        'product'
                    );
                    ?>
                </div>
                
                <?php if (count($images) > 1): ?>
                <div class="thumbnail-images" style="display: flex; gap: 0.5rem; overflow-x: auto;">
                    <?php foreach ($images as $index => $image): ?>
                        <?php
                        echo generateImageTag(
                            $image['url'],
                            "Product image " . ($index + 1),
                            [
                                'onclick' => "changeMainImage('" . htmlspecialchars($image['url']) . "')",
                                'style' => 'width: 80px; height: 80px; object-fit: cover; border-radius: 4px; cursor: pointer; border: 2px solid transparent;',
                                'onmouseover' => "this.style.borderColor='#10b981'",
                                'onmouseout' => "this.style.borderColor='transparent'"
                            ],
                            'product'
                        );
                        ?>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Product Details -->
        <div class="col-6">
            <div class="product-details" style="padding-left: 2rem;">
                <h1 style="font-size: 2rem; margin-bottom: 0.5rem; color: #1f2937;"><?php echo htmlspecialchars($product['name']); ?></h1>
                
                <div style="margin-bottom: 1rem;">
                    <span style="color: #6b7280;">by</span>
                    <a href="vendor-profile.php?id=<?php echo $product['vendor_id']; ?>" style="color: #10b981; text-decoration: none; font-weight: 500;">
                        <?php echo htmlspecialchars($product['store_name'] ?: $product['vendor_name']); ?>
                    </a>
                </div>

                <?php if (isset($product['avg_rating']) && $product['avg_rating'] > 0): ?>
                <div style="margin-bottom: 1rem;">
                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                        <div class="rating">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <i class="fas fa-star" style="color: <?php echo $i <= round($product['avg_rating']) ? '#fbbf24' : '#e5e7eb'; ?>;"></i>
                            <?php endfor; ?>
                        </div>
                        <span style="color: #6b7280;"><?php echo number_format($product['avg_rating'], 1); ?> (<?php echo $product['review_count'] ?? 0; ?> reviews)</span>
                    </div>
                </div>
                <?php endif; ?>

                <div style="margin-bottom: 2rem;">
                    <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 1rem;">
                        <span style="font-size: 2rem; font-weight: 700; color: #10b981;">RWF <?php echo number_format($product['price'], 2); ?></span>
                        <?php if ($product['compare_price'] && $product['compare_price'] > $product['price']): ?>
                            <span style="font-size: 1.2rem; color: #6b7280; text-decoration: line-through;">RWF <?php echo number_format($product['compare_price'], 2); ?></span>
                            <span style="background: #ef4444; color: white; padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.875rem;">
                                <?php echo round((($product['compare_price'] - $product['price']) / $product['compare_price']) * 100); ?>% OFF
                            </span>
                        <?php endif; ?>
                    </div>
                    
                    <div style="margin-bottom: 1rem;">
                        <span style="color: #6b7280;">Stock: </span>
                        <span style="color: <?php echo $product['stock_quantity'] > 0 ? '#10b981' : '#ef4444'; ?>; font-weight: 500;">
                            <?php echo $product['stock_quantity'] > 0 ? $product['stock_quantity'] . ' available' : 'Out of stock'; ?>
                        </span>
                    </div>
                </div>

                <?php if ($product['stock_quantity'] > 0): ?>
                <div style="margin-bottom: 2rem;">
                    <div style="display: flex; gap: 1rem; margin-bottom: 1rem;">
                        <div style="display: flex; align-items: center; border: 1px solid #d1d5db; border-radius: 4px;">
                            <button onclick="decreaseQuantity()" style="padding: 0.5rem; border: none; background: none; cursor: pointer;">-</button>
                            <input type="number" id="quantity" value="1" min="1" max="<?php echo $product['stock_quantity']; ?>" 
                                   style="width: 60px; text-align: center; border: none; padding: 0.5rem;">
                            <button onclick="increaseQuantity()" style="padding: 0.5rem; border: none; background: none; cursor: pointer;">+</button>
                        </div>
                    </div>
                    
                    <div style="display: flex; gap: 1rem;">
                        <button onclick="addToCart(<?php echo $product['id']; ?>)" class="btn btn-primary" style="flex: 1;">
                            <i class="fas fa-shopping-cart"></i> Add to Cart
                        </button>
                        <button onclick="addToWishlist(<?php echo $product['id']; ?>)" class="btn btn-secondary">
                            <i class="fas fa-heart"></i>
                        </button>
                    </div>
                </div>
                <?php endif; ?>

                <?php if ($product['description']): ?>
                <div style="margin-bottom: 2rem;">
                    <h3 style="margin-bottom: 1rem;">Description</h3>
                    <p style="color: #6b7280; line-height: 1.6;"><?php echo nl2br(htmlspecialchars($product['description'])); ?></p>
                </div>
                <?php endif; ?>

                <div style="border-top: 1px solid #e5e7eb; padding-top: 1rem;">
                    <p style="color: #6b7280; font-size: 0.875rem;">
                        <strong>Category:</strong> <?php echo htmlspecialchars($product['category_name']); ?><br>
                        <strong>SKU:</strong> <?php echo htmlspecialchars($product['sku']); ?><br>
                        <?php if ($product['brand']): ?>
                            <strong>Brand:</strong> <?php echo htmlspecialchars($product['brand']); ?><br>
                        <?php endif; ?>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function changeMainImage(imageUrl) {
    document.getElementById('mainImage').src = imageUrl;
}

function increaseQuantity() {
    const input = document.getElementById('quantity');
    const max = parseInt(input.getAttribute('max'));
    if (parseInt(input.value) < max) {
        input.value = parseInt(input.value) + 1;
    }
}

function decreaseQuantity() {
    const input = document.getElementById('quantity');
    if (parseInt(input.value) > 1) {
        input.value = parseInt(input.value) - 1;
    }
}

function addToCart(productId) {
    const quantity = document.getElementById('quantity').value;
    
    fetch('api/cart.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'add',
            product_id: productId,
            quantity: quantity
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Product added to cart!');
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        alert('Error adding to cart');
    });
}

function addToWishlist(productId) {
    fetch('api/wishlist.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'add',
            product_id: productId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Product added to wishlist!');
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        alert('Error adding to wishlist');
    });
}
</script>

<?php require_once 'includes/footer.php'; ?>
