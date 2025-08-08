<?php
/**
 * Enhanced Invoice with Store Information
 */

require_once 'config/config.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirect('login.php');
}

$customer_id = $_SESSION['user_id'];
$order_id = intval($_GET['order'] ?? 0);

if (!$order_id) {
    redirect('orders.php');
}

// Get order details
$order_sql = "SELECT * FROM orders WHERE id = ? AND customer_id = ?";
$order = $database->fetch($order_sql, [$order_id, $customer_id]);

if (!$order) {
    redirect('orders.php');
}

// Get customer details
$customer_sql = "SELECT * FROM users WHERE id = ?";
$customer = $database->fetch($customer_sql, [$customer_id]);

// Get shipping address if available
$shipping_address = null;
$billing_address = null;

if (!empty($order['shipping_address_id'])) {
    try {
        $shipping_sql = "SELECT * FROM user_addresses WHERE id = ?";
        $shipping_address = $database->fetch($shipping_sql, [$order['shipping_address_id']]);
    } catch (Exception $e) {
        $shipping_address = null;
    }
}

if (!empty($order['billing_address_id'])) {
    try {
        $billing_sql = "SELECT * FROM user_addresses WHERE id = ?";
        $billing_address = $database->fetch($billing_sql, [$order['billing_address_id']]);
    } catch (Exception $e) {
        $billing_address = null;
    }
}

// Get order items with store information
$items_sql = "
    SELECT oi.*, p.name, p.sku, p.brand, p.description,
           u.username as vendor_name, u.email as vendor_email, u.phone as vendor_phone,
           vs.store_name, vs.store_description, vs.description as store_desc,
           vs.address as store_address, vs.phone as store_phone, vs.email as store_email
    FROM order_items oi
    JOIN products p ON oi.product_id = p.id
    JOIN users u ON p.vendor_id = u.id
    LEFT JOIN vendor_stores vs ON u.id = vs.vendor_id
    WHERE oi.order_id = ?
    ORDER BY vs.store_name, p.name
";

$order_items = $database->fetchAll($items_sql, [$order_id]);

// Group items by vendor/store
$vendors = [];
foreach ($order_items as $item) {
    $vendor_key = $item['store_name'] ?: $item['vendor_name'];
    if (!isset($vendors[$vendor_key])) {
        $vendors[$vendor_key] = [
            'vendor_name' => $item['vendor_name'],
            'vendor_email' => $item['vendor_email'],
            'vendor_phone' => $item['vendor_phone'],
            'store_name' => $item['store_name'],
            'store_description' => $item['store_description'],
            'store_address' => $item['store_address'],
            'store_phone' => $item['store_phone'],
            'store_email' => $item['store_email'],
            'items' => [],
            'subtotal' => 0
        ];
    }
    $vendors[$vendor_key]['items'][] = $item;
    $vendors[$vendor_key]['subtotal'] += $item['quantity'] * $item['unit_price'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice - Order #<?php echo htmlspecialchars($order['order_number'] ?? $order['id']); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            background: #f8f9fa;
            padding: 2rem;
        }
        
        .invoice-container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .invoice-header {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            padding: 2rem;
            text-align: center;
        }
        
        .invoice-header h1 {
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
        }
        
        .invoice-header p {
            opacity: 0.9;
            font-size: 1.1rem;
        }
        
        .invoice-meta {
            padding: 2rem;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
        }
        
        .meta-section h3 {
            color: #10b981;
            margin-bottom: 1rem;
            font-size: 1.2rem;
        }
        
        .meta-section p {
            margin-bottom: 0.5rem;
        }
        
        .vendor-section {
            margin: 2rem;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            overflow: hidden;
        }
        
        .vendor-header {
            background: #f9fafb;
            padding: 1.5rem;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .vendor-name {
            font-size: 1.5rem;
            font-weight: 700;
            color: #10b981;
            margin-bottom: 0.5rem;
        }
        
        .vendor-info {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-top: 1rem;
        }
        
        .vendor-contact {
            font-size: 0.9rem;
            color: #6b7280;
        }
        
        .items-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .items-table th {
            background: #f9fafb;
            padding: 1rem;
            text-align: left;
            font-weight: 600;
            color: #374151;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .items-table td {
            padding: 1rem;
            border-bottom: 1px solid #f3f4f6;
        }
        
        .items-table tr:hover {
            background: #f9fafb;
        }
        
        .price {
            font-weight: 600;
            color: #10b981;
        }
        
        .vendor-total {
            background: #f0fdf4;
            padding: 1rem 1.5rem;
            text-align: right;
            font-weight: 600;
            color: #059669;
        }
        
        .order-summary {
            margin: 2rem;
            padding: 2rem;
            background: #f9fafb;
            border-radius: 12px;
        }
        
        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.5rem;
        }
        
        .summary-total {
            border-top: 2px solid #e5e7eb;
            padding-top: 1rem;
            margin-top: 1rem;
            font-size: 1.2rem;
            font-weight: 700;
            color: #10b981;
        }
        
        .print-btn {
            position: fixed;
            top: 20px;
            right: 20px;
            background: #10b981;
            color: white;
            border: none;
            padding: 1rem 2rem;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
            transition: all 0.3s;
        }
        
        .print-btn:hover {
            background: #059669;
            transform: translateY(-2px);
        }
        
        .back-btn {
            position: fixed;
            top: 20px;
            left: 20px;
            background: #6b7280;
            color: white;
            border: none;
            padding: 1rem 2rem;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .back-btn:hover {
            background: #4b5563;
        }
        
        @media print {
            .print-btn, .back-btn {
                display: none;
            }
            
            body {
                background: white;
                padding: 0;
            }
            
            .invoice-container {
                box-shadow: none;
                border-radius: 0;
            }
        }
        
        @media (max-width: 768px) {
            body {
                padding: 1rem;
            }
            
            .invoice-meta {
                grid-template-columns: 1fr;
                gap: 1rem;
            }
            
            .vendor-info {
                grid-template-columns: 1fr;
            }
            
            .items-table {
                font-size: 0.9rem;
            }
            
            .print-btn, .back-btn {
                position: static;
                margin: 1rem;
                width: calc(50% - 1rem);
            }
        }
    </style>
</head>
<body>
    <a href="orders.php" class="back-btn">
        <i class="fas fa-arrow-left"></i> Back to Orders
    </a>
    
    <button class="print-btn" onclick="window.print()">
        <i class="fas fa-print"></i> Print Invoice
    </button>

    <div class="invoice-container">
        <!-- Invoice Header -->
        <div class="invoice-header">
            <h1>📄 INVOICE</h1>
            <p>Order #<?php echo htmlspecialchars($order['order_number'] ?? $order['id']); ?></p>
            <p>Date: <?php echo date('F j, Y', strtotime($order['created_at'])); ?></p>
        </div>

        <!-- Invoice Meta Information -->
        <div class="invoice-meta">
            <div class="meta-section">
                <h3>📍 Customer Information</h3>
                <p><strong><?php echo htmlspecialchars($customer['username']); ?></strong></p>
                <p><?php echo htmlspecialchars($customer['email']); ?></p>
                <?php if ($shipping_address): ?>
                    <h4 style="margin-top: 1rem; color: #374151;">Delivery Address:</h4>
                    <p><?php echo htmlspecialchars($shipping_address['address_line_1']); ?></p>
                    <?php if (!empty($shipping_address['address_line_2'])): ?>
                        <p><?php echo htmlspecialchars($shipping_address['address_line_2']); ?></p>
                    <?php endif; ?>
                    <p><?php echo htmlspecialchars($shipping_address['city'] . ', ' . $shipping_address['state']); ?></p>
                    <p><?php echo htmlspecialchars($shipping_address['country'] ?? 'Rwanda'); ?></p>
                <?php endif; ?>
            </div>
            
            <div class="meta-section">
                <h3>💳 Payment Information</h3>
                <p><strong>Payment Method:</strong> <?php echo ucwords(str_replace('_', ' ', $order['payment_method'])); ?></p>
                <p><strong>Payment Status:</strong> 
                    <span style="color: <?php echo $order['payment_status'] === 'paid' ? '#10b981' : '#f59e0b'; ?>;">
                        <?php echo ucfirst($order['payment_status']); ?>
                    </span>
                </p>
                <p><strong>Order Status:</strong> 
                    <span style="color: <?php echo $order['status'] === 'completed' ? '#10b981' : '#f59e0b'; ?>;">
                        <?php echo ucfirst($order['status']); ?>
                    </span>
                </p>
            </div>
        </div>

        <!-- Vendor Sections -->
        <?php foreach ($vendors as $vendor_key => $vendor): ?>
            <div class="vendor-section">
                <div class="vendor-header">
                    <div class="vendor-name">
                        🏪 <?php echo htmlspecialchars($vendor['store_name'] ?: $vendor['vendor_name']); ?>
                    </div>

                    <div class="vendor-info">
                        <div class="vendor-contact">
                            <?php if ($vendor['store_description']): ?>
                                <p><strong>About:</strong> <?php echo htmlspecialchars($vendor['store_description']); ?></p>
                            <?php endif; ?>
                            <?php if ($vendor['store_address']): ?>
                                <p><strong>Address:</strong> <?php echo htmlspecialchars($vendor['store_address']); ?></p>
                            <?php endif; ?>
                        </div>
                        <div class="vendor-contact">
                            <?php if ($vendor['store_email'] || $vendor['vendor_email']): ?>
                                <p><strong>Email:</strong> <?php echo htmlspecialchars($vendor['store_email'] ?: $vendor['vendor_email']); ?></p>
                            <?php endif; ?>
                            <?php if ($vendor['store_phone'] || $vendor['vendor_phone']): ?>
                                <p><strong>Phone:</strong> <?php echo htmlspecialchars($vendor['store_phone'] ?: $vendor['vendor_phone']); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <table class="items-table">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>SKU</th>
                            <th>Qty</th>
                            <th>Unit Price</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($vendor['items'] as $item): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($item['name']); ?></strong>
                                    <?php if ($item['brand']): ?>
                                        <br><small style="color: #6b7280;">Brand: <?php echo htmlspecialchars($item['brand']); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($item['sku'] ?? 'N/A'); ?></td>
                                <td><?php echo $item['quantity']; ?></td>
                                <td class="price"><?php echo formatCurrency($item['unit_price']); ?></td>
                                <td class="price"><?php echo formatCurrency($item['quantity'] * $item['unit_price']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <div class="vendor-total">
                    Store Subtotal: <?php echo formatCurrency($vendor['subtotal']); ?>
                </div>
            </div>
        <?php endforeach; ?>

        <!-- Order Summary -->
        <div class="order-summary">
            <h3 style="color: #10b981; margin-bottom: 1rem;">📊 Order Summary</h3>

            <div class="summary-row">
                <span>Subtotal:</span>
                <span class="price"><?php echo formatCurrency($order['subtotal'] ?? $order['total_amount']); ?></span>
            </div>

            <?php if (!empty($order['shipping_cost'])): ?>
                <div class="summary-row">
                    <span>Shipping:</span>
                    <span class="price"><?php echo formatCurrency($order['shipping_cost']); ?></span>
                </div>
            <?php endif; ?>

            <?php if (!empty($order['tax_amount'])): ?>
                <div class="summary-row">
                    <span>Tax (18% VAT):</span>
                    <span class="price"><?php echo formatCurrency($order['tax_amount']); ?></span>
                </div>
            <?php endif; ?>

            <div class="summary-row summary-total">
                <span>Total Amount:</span>
                <span><?php echo formatCurrency($order['total_amount']); ?></span>
            </div>
        </div>

        <!-- Footer -->
        <div style="padding: 2rem; text-align: center; background: #f9fafb; color: #6b7280;">
            <p><strong>MarketHub</strong> - Multi-Vendor E-Commerce Platform</p>
            <p>Musanze District, Northern Province, Rwanda</p>
            <p>Email: info@markethub.rw | Website: www.markethub.rw</p>
            <p style="margin-top: 1rem; font-size: 0.9rem;">
                Thank you for shopping with MarketHub! For any questions about this order,
                please contact the respective vendors listed above.
            </p>
        </div>
    </div>

    <script>
        // Auto-print functionality
        function printInvoice() {
            window.print();
        }

        // Add keyboard shortcut for printing
        document.addEventListener('keydown', function(e) {
            if (e.ctrlKey && e.key === 'p') {
                e.preventDefault();
                printInvoice();
            }
        });
    </script>
</body>
</html>
