<?php
/**
 * MarketHub Comparison Widget
 * Floating comparison bar for easy access
 */

// Initialize comparison session if not exists
if (!isset($_SESSION['compare_items'])) {
    $_SESSION['compare_items'] = [];
}

$compare_count = count($_SESSION['compare_items']);
$compare_products = [];

// Get comparison products for preview
if ($compare_count > 0) {
    $placeholders = str_repeat('?,', $compare_count - 1) . '?';
    $preview_sql = "
        SELECT p.id, p.name, p.price, pi.image_url
        FROM products p
        LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
        WHERE p.id IN ($placeholders) AND p.status = 'active'
        ORDER BY FIELD(p.id, " . implode(',', $_SESSION['compare_items']) . ")
        LIMIT 4
    ";
    
    $compare_products = $database->fetchAll($preview_sql, $_SESSION['compare_items']);
}
?>

<!-- Comparison Widget -->
<div id="comparison-widget" class="comparison-widget" style="<?php echo $compare_count > 0 ? '' : 'display: none;'; ?>">
    <div class="comparison-widget-content">
        <div class="comparison-header">
            <div class="comparison-title">
                <i class="fas fa-balance-scale"></i>
                <span>Compare Products (<span id="compare-count"><?php echo $compare_count; ?></span>)</span>
            </div>
            <div class="comparison-actions">
                <button onclick="toggleComparisonWidget()" class="widget-toggle-btn" title="Toggle widget">
                    <i class="fas fa-chevron-up" id="widget-toggle-icon"></i>
                </button>
                <button onclick="clearComparison()" class="widget-clear-btn" title="Clear all">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        </div>
        
        <div class="comparison-body" id="comparison-body">
            <div class="comparison-products" id="comparison-products">
                <?php foreach ($compare_products as $product): ?>
                    <div class="comparison-product-item" data-product-id="<?php echo $product['id']; ?>">
                        <button onclick="removeFromComparison(<?php echo $product['id']; ?>)" class="remove-product-btn">
                            <i class="fas fa-times"></i>
                        </button>
                        <img src="<?php echo $product['image_url'] ?: 'assets/images/product-placeholder.png'; ?>" 
                             alt="<?php echo htmlspecialchars($product['name']); ?>" 
                             class="comparison-product-image">
                        <div class="comparison-product-info">
                            <div class="comparison-product-name"><?php echo htmlspecialchars(substr($product['name'], 0, 30)); ?><?php echo strlen($product['name']) > 30 ? '...' : ''; ?></div>
                            <div class="comparison-product-price"><?php echo formatCurrency($product['price']); ?></div>
                        </div>
                    </div>
                <?php endforeach; ?>
                
                <!-- Empty slots -->
                <?php for ($i = $compare_count; $i < 4; $i++): ?>
                    <div class="comparison-product-slot">
                        <div class="empty-slot">
                            <i class="fas fa-plus"></i>
                            <span>Add Product</span>
                        </div>
                    </div>
                <?php endfor; ?>
            </div>
            
            <div class="comparison-footer">
                <a href="compare.php" class="btn btn-primary comparison-view-btn">
                    <i class="fas fa-eye"></i> View Comparison
                </a>
            </div>
        </div>
    </div>
</div>

<style>
.comparison-widget {
    position: fixed;
    bottom: 0;
    left: 50%;
    transform: translateX(-50%);
    background: var(--white);
    border: 1px solid var(--medium-gray);
    border-bottom: none;
    border-radius: 12px 12px 0 0;
    box-shadow: 0 -4px 20px rgba(0,0,0,0.15);
    z-index: 1000;
    max-width: 800px;
    width: 90%;
    transition: all 0.3s ease;
}

.comparison-widget.collapsed .comparison-body {
    display: none;
}

.comparison-widget.collapsed {
    border-radius: 12px;
    bottom: 20px;
}

.comparison-widget-content {
    padding: 0;
}

.comparison-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem 1.5rem;
    background: var(--primary-green);
    color: var(--white);
    border-radius: 12px 12px 0 0;
    cursor: pointer;
}

.comparison-widget.collapsed .comparison-header {
    border-radius: 12px;
}

.comparison-title {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-weight: 600;
}

.comparison-actions {
    display: flex;
    gap: 0.5rem;
}

.widget-toggle-btn,
.widget-clear-btn {
    background: rgba(255,255,255,0.2);
    border: none;
    color: var(--white);
    padding: 0.5rem;
    border-radius: 4px;
    cursor: pointer;
    transition: all 0.3s ease;
}

.widget-toggle-btn:hover,
.widget-clear-btn:hover {
    background: rgba(255,255,255,0.3);
}

.comparison-body {
    padding: 1rem;
}

.comparison-products {
    display: flex;
    gap: 1rem;
    margin-bottom: 1rem;
    overflow-x: auto;
    padding-bottom: 0.5rem;
}

.comparison-product-item {
    position: relative;
    min-width: 150px;
    background: var(--light-gray);
    border-radius: var(--border-radius);
    padding: 1rem;
    text-align: center;
}

.comparison-product-slot {
    min-width: 150px;
    background: var(--light-gray);
    border: 2px dashed var(--medium-gray);
    border-radius: var(--border-radius);
    padding: 1rem;
    text-align: center;
}

.empty-slot {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.5rem;
    color: var(--dark-gray);
    height: 120px;
    justify-content: center;
}

.remove-product-btn {
    position: absolute;
    top: -8px;
    right: -8px;
    background: #F44336;
    color: var(--white);
    border: none;
    border-radius: 50%;
    width: 24px;
    height: 24px;
    font-size: 0.8rem;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
}

.comparison-product-image {
    width: 60px;
    height: 60px;
    object-fit: cover;
    border-radius: 4px;
    margin-bottom: 0.5rem;
}

.comparison-product-info {
    font-size: 0.9rem;
}

.comparison-product-name {
    font-weight: 600;
    margin-bottom: 0.25rem;
    color: var(--black);
}

.comparison-product-price {
    color: var(--primary-green);
    font-weight: 600;
}

.comparison-footer {
    text-align: center;
    padding-top: 1rem;
    border-top: 1px solid var(--light-gray);
}

.comparison-view-btn {
    padding: 0.75rem 2rem;
    font-weight: 600;
}

/* Mobile Responsive */
@media (max-width: 768px) {
    .comparison-widget {
        width: 95%;
        max-width: none;
    }
    
    .comparison-header {
        padding: 0.75rem 1rem;
        font-size: 0.9rem;
    }
    
    .comparison-products {
        gap: 0.5rem;
    }
    
    .comparison-product-item,
    .comparison-product-slot {
        min-width: 120px;
        padding: 0.75rem;
    }
    
    .comparison-product-image {
        width: 50px;
        height: 50px;
    }
    
    .comparison-product-info {
        font-size: 0.8rem;
    }
}

/* Animation for adding/removing products */
.comparison-product-item {
    animation: slideIn 0.3s ease;
}

@keyframes slideIn {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.comparison-product-item.removing {
    animation: slideOut 0.3s ease forwards;
}

@keyframes slideOut {
    from {
        opacity: 1;
        transform: translateY(0);
    }
    to {
        opacity: 0;
        transform: translateY(-20px);
    }
}
</style>

<script>
let widgetCollapsed = false;

function toggleComparisonWidget() {
    const widget = document.getElementById('comparison-widget');
    const body = document.getElementById('comparison-body');
    const icon = document.getElementById('widget-toggle-icon');
    
    widgetCollapsed = !widgetCollapsed;
    
    if (widgetCollapsed) {
        widget.classList.add('collapsed');
        icon.className = 'fas fa-chevron-down';
    } else {
        widget.classList.remove('collapsed');
        icon.className = 'fas fa-chevron-up';
    }
}

function updateComparisonWidget() {
    fetch('api/compare.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'get'
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const widget = document.getElementById('comparison-widget');
            const countElement = document.getElementById('compare-count');
            const productsContainer = document.getElementById('comparison-products');
            
            // Update count
            countElement.textContent = data.count;
            
            // Show/hide widget
            if (data.count > 0) {
                widget.style.display = 'block';
                
                // Update products
                productsContainer.innerHTML = '';
                
                // Add existing products
                data.products.forEach(product => {
                    const productElement = createProductElement(product);
                    productsContainer.appendChild(productElement);
                });
                
                // Add empty slots
                for (let i = data.count; i < 4; i++) {
                    const slotElement = createEmptySlot();
                    productsContainer.appendChild(slotElement);
                }
            } else {
                widget.style.display = 'none';
            }
        }
    })
    .catch(error => {
        console.error('Error updating comparison widget:', error);
    });
}

function createProductElement(product) {
    const div = document.createElement('div');
    div.className = 'comparison-product-item';
    div.setAttribute('data-product-id', product.id);
    
    div.innerHTML = `
        <button onclick="removeFromComparison(${product.id})" class="remove-product-btn">
            <i class="fas fa-times"></i>
        </button>
        <img src="${product.image_url || 'assets/images/product-placeholder.png'}" 
             alt="${product.name}" 
             class="comparison-product-image">
        <div class="comparison-product-info">
            <div class="comparison-product-name">${product.name.length > 30 ? product.name.substring(0, 30) + '...' : product.name}</div>
            <div class="comparison-product-price">${formatCurrency(product.price)}</div>
        </div>
    `;
    
    return div;
}

function createEmptySlot() {
    const div = document.createElement('div');
    div.className = 'comparison-product-slot';
    
    div.innerHTML = `
        <div class="empty-slot">
            <i class="fas fa-plus"></i>
            <span>Add Product</span>
        </div>
    `;
    
    return div;
}

function removeFromComparison(productId) {
    const productElement = document.querySelector(`[data-product-id="${productId}"]`);
    if (productElement) {
        productElement.classList.add('removing');
    }
    
    fetch('api/compare.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            product_id: productId,
            action: 'remove'
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            setTimeout(() => {
                updateComparisonWidget();
            }, 300);
            
            // Update compare buttons on page
            const compareButtons = document.querySelectorAll(`[data-product-id="${productId}"].compare-btn`);
            compareButtons.forEach(btn => {
                btn.style.background = 'rgba(255,255,255,0.9)';
                btn.style.color = 'var(--primary-green)';
            });
        }
    })
    .catch(error => {
        console.error('Error removing from comparison:', error);
        if (productElement) {
            productElement.classList.remove('removing');
        }
    });
}

function clearComparison() {
    if (confirm('Are you sure you want to clear all products from comparison?')) {
        fetch('api/compare.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'clear'
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateComparisonWidget();
                
                // Reset all compare buttons
                document.querySelectorAll('.compare-btn').forEach(btn => {
                    btn.style.background = 'rgba(255,255,255,0.9)';
                    btn.style.color = 'var(--primary-green)';
                });
            }
        });
    }
}

// Auto-collapse widget on mobile
if (window.innerWidth <= 768) {
    widgetCollapsed = true;
    const widget = document.getElementById('comparison-widget');
    const icon = document.getElementById('widget-toggle-icon');
    
    if (widget) {
        widget.classList.add('collapsed');
        if (icon) {
            icon.className = 'fas fa-chevron-down';
        }
    }
}

// Helper function for currency formatting
function formatCurrency(amount) {
    return 'RWF ' + new Intl.NumberFormat().format(amount);
}
</script>
