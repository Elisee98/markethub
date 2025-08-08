<?php
/**
 * Invoice Generation Page
 * Generate and display order invoices
 */

require_once 'config/config.php';

// Require login
requireLogin();

$customer_id = $_SESSION['user_id'];
$order_id = intval($_GET['order'] ?? 0);

if (!$order_id) {
    redirect('orders.php');
}

// Get order details
$order_sql = "
    SELECT o.*, u.first_name, u.last_name, u.email,
           sa.address_line_1 as shipping_address_1, sa.address_line_2 as shipping_address_2,
           sa.city as shipping_city, sa.state as shipping_state, sa.postal_code as shipping_postal,
           sa.country as shipping_country,
           ba.address_line_1 as billing_address_1, ba.address_line_2 as billing_address_2,
           ba.city as billing_city, ba.state as billing_state, ba.postal_code as billing_postal,
           ba.country as billing_country
    FROM orders o
    JOIN users u ON o.customer_id = u.id
    LEFT JOIN user_addresses sa ON o.shipping_address_id = sa.id
    LEFT JOIN user_addresses ba ON o.billing_address_id = ba.id
    WHERE o.id = ? AND o.customer_id = ?
";

$order = $database->fetch($order_sql, [$order_id, $customer_id]);

if (!$order) {
    redirect('orders.php');
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
    // Use unit_price instead of price
    $vendors[$vendor_key]['subtotal'] += $item['quantity'] * $item['unit_price'];
}

// Get payment information
$payment = $database->fetch(
    "SELECT * FROM payments WHERE order_id = ? ORDER BY created_at DESC LIMIT 1",
    [$order_id]
);

$page_title = "Invoice - Order #{$order['order_number']}";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            background: white;
        }
        
        .invoice-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #007bff;
        }
        
        .company-info h1 {
            color: #007bff;
            margin: 0;
            font-size: 2rem;
        }
        
        .company-info p {
            margin: 0;
            color: #666;
        }
        
        .invoice-details {
            text-align: right;
        }
        
        .invoice-details h2 {
            color: #333;
            margin: 0 0 0.5rem 0;
        }
        
        .invoice-meta {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
            margin: 2rem 0;
        }
        
        .address-section h3 {
            color: #007bff;
            margin-bottom: 0.5rem;
            font-size: 1.1rem;
        }
        
        .address-section p {
            margin: 0;
            color: #666;
        }
        
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin: 2rem 0;
        }
        
        .items-table th,
        .items-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        
        .items-table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #333;
        }
        
        .vendor-header {
            background: #e3f2fd;
            font-weight: bold;
            color: #1976d2;
        }
        
        .vendor-subtotal {
            background: #f5f5f5;
            font-weight: bold;
        }
        
        .totals-section {
            margin-top: 2rem;
            display: flex;
            justify-content: flex-end;
        }
        
        .totals-table {
            width: 300px;
        }
        
        .totals-table td {
            padding: 8px 12px;
            border: none;
        }
        
        .totals-table .total-row {
            border-top: 2px solid #007bff;
            font-weight: bold;
            font-size: 1.1rem;
            color: #007bff;
        }
        
        .payment-info {
            margin: 2rem 0;
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 6px;
        }
        
        .footer {
            margin-top: 3rem;
            padding-top: 1rem;
            border-top: 1px solid #ddd;
            text-align: center;
            color: #666;
            font-size: 0.9rem;
        }
        
        .print-button {
            position: fixed;
            top: 20px;
            right: 20px;
            background: #007bff;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 500;
        }
        
        .print-button:hover {
            background: #0056b3;
        }
        
        @media print {
            .print-button {
                display: none;
            }
            
            body {
                padding: 0;
            }
        }
    </style>
</head>
<body>
    <button class="print-button" onclick="window.print()">
        🖨️ Print Invoice
    </button>

    <!-- Invoice Header -->
    <div class="invoice-header">
        <div class="company-info">
            <h1>MarketHub</h1>
            <p>Multi-Vendor E-Commerce Platform</p>
            <p>Musanze District, Rwanda</p>
            <p>Email: info@markethub.rw</p>
        </div>
        <div class="invoice-details">
            <h2>INVOICE</h2>
            <p><strong>Invoice #:</strong> INV-<?php echo $order['order_number']; ?></p>
            <p><strong>Date:</strong> <?php echo date('F j, Y', strtotime($order['created_at'])); ?></p>
            <?php if ($order['payment_date']): ?>
                <p><strong>Payment Date:</strong> <?php echo date('F j, Y', strtotime($order['payment_date'])); ?></p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Customer and Order Information -->
    <div class="invoice-meta">
        <div class="billing-address">
            <div class="address-section">
                <h3>Bill To:</h3>
                <p><strong><?php echo htmlspecialchars($order['first_name'] . ' ' . $order['last_name']); ?></strong></p>
                <p><?php echo htmlspecialchars($order['billing_address_1']); ?></p>
                <?php if ($order['billing_address_2']): ?>
                    <p><?php echo htmlspecialchars($order['billing_address_2']); ?></p>
                <?php endif; ?>
                <p><?php echo htmlspecialchars($order['billing_city'] . ', ' . $order['billing_state']); ?></p>
                <p><?php echo htmlspecialchars($order['billing_postal'] . ', ' . $order['billing_country']); ?></p>
                <p><?php echo htmlspecialchars($order['email']); ?></p>
            </div>
        </div>
        
        <div class="shipping-address">
            <div class="address-section">
                <h3>Ship To:</h3>
                <p><?php echo htmlspecialchars($order['shipping_address_1']); ?></p>
                <?php if ($order['shipping_address_2']): ?>
                    <p><?php echo htmlspecialchars($order['shipping_address_2']); ?></p>
                <?php endif; ?>
                <p><?php echo htmlspecialchars($order['shipping_city'] . ', ' . $order['shipping_state']); ?></p>
                <p><?php echo htmlspecialchars($order['shipping_postal'] . ', ' . $order['shipping_country']); ?></p>
            </div>
            
            <div class="address-section" style="margin-top: 1rem;">
                <h3>Order Details:</h3>
                <p><strong>Order #:</strong> <?php echo htmlspecialchars($order['order_number']); ?></p>
                <p><strong>Status:</strong> <?php echo ucfirst($order['status']); ?></p>
                <p><strong>Payment Method:</strong> <?php echo ucwords(str_replace('_', ' ', $order['payment_method'])); ?></p>
                <p><strong>Payment Status:</strong> <?php echo ucfirst($order['payment_status']); ?></p>
                <?php if ($order['tracking_number']): ?>
                    <p><strong>Tracking #:</strong> <?php echo htmlspecialchars($order['tracking_number']); ?></p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Order Items -->
    <table class="items-table">
        <thead>
            <tr>
                <th>Item</th>
                <th>SKU</th>
                <th>Qty</th>
                <th>Unit Price</th>
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($vendors as $vendor): ?>
                <tr class="vendor-header">
                    <td colspan="5">
                        <strong><?php echo htmlspecialchars($vendor['store_name'] ?: $vendor['vendor_name']); ?></strong>
                    </td>
                </tr>
                
                <?php foreach ($vendor['items'] as $item): ?>
                    <tr>
                        <td>
                            <strong><?php echo htmlspecialchars($item['name']); ?></strong>
                            <?php if ($item['brand']): ?>
                                <br><small>Brand: <?php echo htmlspecialchars($item['brand']); ?></small>
                            <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars($item['sku'] ?: 'N/A'); ?></td>
                        <td><?php echo $item['quantity']; ?></td>
                        <td>RWF <?php echo number_format($item['unit_price']); ?></td>
                        <td>RWF <?php echo number_format($item['quantity'] * $item['unit_price']); ?></td>
                    </tr>
                <?php endforeach; ?>
                
                <tr class="vendor-subtotal">
                    <td colspan="4" style="text-align: right;"><strong>Vendor Subtotal:</strong></td>
                    <td><strong>RWF <?php echo number_format($vendor['subtotal']); ?></strong></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <!-- Totals -->
    <div class="totals-section">
        <table class="totals-table">
            <tr>
                <td>Subtotal:</td>
                <td style="text-align: right;">RWF <?php echo number_format($order['subtotal']); ?></td>
            </tr>
            <tr>
                <td>Shipping:</td>
                <td style="text-align: right;">RWF <?php echo number_format($order['shipping_cost']); ?></td>
            </tr>
            <tr>
                <td>Tax (18%):</td>
                <td style="text-align: right;">RWF <?php echo number_format($order['tax_amount']); ?></td>
            </tr>
            <tr class="total-row">
                <td><strong>Total:</strong></td>
                <td style="text-align: right;"><strong>RWF <?php echo number_format($order['total_amount']); ?></strong></td>
            </tr>
        </table>
    </div>

    <!-- Payment Information -->
    <?php if ($payment): ?>
        <div class="payment-info">
            <h3 style="margin: 0 0 1rem 0; color: #007bff;">Payment Information</h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                <div>
                    <strong>Payment Reference:</strong><br>
                    <?php echo htmlspecialchars($payment['payment_reference']); ?>
                </div>
                <div>
                    <strong>Payment Method:</strong><br>
                    <?php echo ucwords(str_replace('_', ' ', $payment['payment_method'])); ?>
                </div>
                <div>
                    <strong>Payment Date:</strong><br>
                    <?php echo date('F j, Y \a\t g:i A', strtotime($payment['created_at'])); ?>
                </div>
                <div>
                    <strong>Amount Paid:</strong><br>
                    RWF <?php echo number_format($payment['amount']); ?>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Special Instructions -->
    <?php if ($order['special_instructions']): ?>
        <div style="margin: 2rem 0; padding: 1rem; background: #fff3cd; border-radius: 6px;">
            <h3 style="margin: 0 0 0.5rem 0; color: #856404;">Special Instructions:</h3>
            <p style="margin: 0; color: #856404;">
                <?php echo htmlspecialchars($order['special_instructions']); ?>
            </p>
        </div>
    <?php endif; ?>

    <!-- Footer -->
    <div class="footer">
        <p><strong>Thank you for your business!</strong></p>
        <p>This is a computer-generated invoice. No signature required.</p>
        <p>For questions about this invoice, please contact us at info@markethub.rw</p>
        <p style="margin-top: 1rem; font-size: 0.8rem;">
            Generated on <?php echo date('F j, Y \a\t g:i A'); ?>
        </p>
    </div>

    <script>
        // Auto-print if requested
        if (window.location.search.includes('print=1')) {
            window.onload = function() {
                window.print();
            };
        }
    </script>
</body>
</html>
