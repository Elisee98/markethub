<?php
/**
 * Shipping Information - MarketHub
 */

require_once 'config/config.php';

$page_title = 'Shipping Information - MarketHub';
$page_description = 'Learn about our shipping policies, delivery times, and shipping costs.';

require_once 'includes/header.php';
?>

<style>
.shipping-hero {
    background: linear-gradient(135deg, #10b981, #059669);
    color: white;
    padding: 4rem 0;
    text-align: center;
}

.shipping-hero h1 {
    font-size: 2.5rem;
    font-weight: 800;
    margin-bottom: 1rem;
}

.shipping-section {
    padding: 4rem 0;
}

.shipping-card {
    background: white;
    border-radius: 12px;
    padding: 2rem;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    margin-bottom: 2rem;
    border-left: 4px solid #10b981;
}

.shipping-icon {
    width: 60px;
    height: 60px;
    background: #10b981;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.5rem;
    margin-bottom: 1rem;
}

.delivery-zones {
    background: #f8f9fa;
    padding: 4rem 0;
}

.zone-card {
    background: white;
    border-radius: 12px;
    padding: 2rem;
    text-align: center;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    margin-bottom: 2rem;
    transition: all 0.3s;
}

.zone-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.15);
}

.zone-icon {
    font-size: 3rem;
    color: #10b981;
    margin-bottom: 1rem;
}

.pricing-table {
    background: white;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
}

.pricing-header {
    background: #10b981;
    color: white;
    padding: 1rem;
    text-align: center;
    font-weight: 600;
}

.pricing-row {
    display: flex;
    padding: 1rem;
    border-bottom: 1px solid #e5e7eb;
}

.pricing-row:last-child {
    border-bottom: none;
}

.pricing-row:nth-child(even) {
    background: #f8f9fa;
}

.pricing-cell {
    flex: 1;
    padding: 0 1rem;
}

.tracking-section {
    background: linear-gradient(135deg, #10b981, #059669);
    color: white;
    padding: 4rem 0;
    text-align: center;
}

.tracking-input {
    max-width: 500px;
    margin: 2rem auto;
    display: flex;
    gap: 1rem;
}

.tracking-input input {
    flex: 1;
    padding: 1rem;
    border: none;
    border-radius: 8px;
    font-size: 1rem;
}

.tracking-input button {
    padding: 1rem 2rem;
    background: white;
    color: #10b981;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
}
</style>

<!-- Hero Section -->
<section class="shipping-hero">
    <div class="container">
        <h1>🚚 Shipping Information</h1>
        <p>Fast, reliable delivery across Rwanda and beyond</p>
    </div>
</section>

<!-- Shipping Overview -->
<section class="shipping-section">
    <div class="container">
        <div class="row">
            <div class="col-4">
                <div class="shipping-card">
                    <div class="shipping-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <h4>Fast Processing</h4>
                    <p>Orders are processed within 1-2 business days. Most vendors ship within 24 hours of order confirmation.</p>
                </div>
            </div>
            <div class="col-4">
                <div class="shipping-card">
                    <div class="shipping-icon">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <h4>Secure Packaging</h4>
                    <p>All items are carefully packaged to ensure they arrive in perfect condition. Fragile items receive extra protection.</p>
                </div>
            </div>
            <div class="col-4">
                <div class="shipping-card">
                    <div class="shipping-icon">
                        <i class="fas fa-map-marked-alt"></i>
                    </div>
                    <h4>Real-time Tracking</h4>
                    <p>Track your order from dispatch to delivery with our real-time tracking system and SMS notifications.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Delivery Zones -->
<section class="delivery-zones">
    <div class="container">
        <h2 class="text-center mb-4">Delivery Zones & Times</h2>
        
        <div class="row">
            <div class="col-4">
                <div class="zone-card">
                    <div class="zone-icon">
                        <i class="fas fa-home"></i>
                    </div>
                    <h4>Musanze District</h4>
                    <p><strong>Same Day Delivery</strong></p>
                    <p>Orders placed before 2 PM are delivered the same day within Musanze town and surrounding areas.</p>
                    <ul style="text-align: left; margin-top: 1rem;">
                        <li>Musanze Town: 2-4 hours</li>
                        <li>Kinigi: 4-6 hours</li>
                        <li>Nyange: 4-6 hours</li>
                        <li>Shingiro: 6-8 hours</li>
                    </ul>
                </div>
            </div>
            <div class="col-4">
                <div class="zone-card">
                    <div class="zone-icon">
                        <i class="fas fa-city"></i>
                    </div>
                    <h4>Northern Province</h4>
                    <p><strong>1-2 Business Days</strong></p>
                    <p>Fast delivery to all major towns in Northern Province with reliable courier services.</p>
                    <ul style="text-align: left; margin-top: 1rem;">
                        <li>Gicumbi: 1 day</li>
                        <li>Burera: 1-2 days</li>
                        <li>Gakenke: 1-2 days</li>
                        <li>Rulindo: 1-2 days</li>
                    </ul>
                </div>
            </div>
            <div class="col-4">
                <div class="zone-card">
                    <div class="zone-icon">
                        <i class="fas fa-globe-africa"></i>
                    </div>
                    <h4>All Rwanda</h4>
                    <p><strong>2-5 Business Days</strong></p>
                    <p>Nationwide delivery to all provinces with trusted logistics partners.</p>
                    <ul style="text-align: left; margin-top: 1rem;">
                        <li>Kigali: 1-2 days</li>
                        <li>Other Provinces: 2-3 days</li>
                        <li>Remote Areas: 3-5 days</li>
                        <li>Express Available: +1 day</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Shipping Costs -->
<section class="shipping-section">
    <div class="container">
        <h2 class="text-center mb-4">Shipping Costs</h2>
        
        <div class="row">
            <div class="col-8 mx-auto">
                <div class="pricing-table">
                    <div class="pricing-header">
                        Shipping Rates by Zone
                    </div>
                    <div class="pricing-row">
                        <div class="pricing-cell"><strong>Delivery Zone</strong></div>
                        <div class="pricing-cell"><strong>Standard</strong></div>
                        <div class="pricing-cell"><strong>Express</strong></div>
                        <div class="pricing-cell"><strong>Free Shipping*</strong></div>
                    </div>
                    <div class="pricing-row">
                        <div class="pricing-cell">Musanze District</div>
                        <div class="pricing-cell">RWF 1,000</div>
                        <div class="pricing-cell">RWF 2,000</div>
                        <div class="pricing-cell">Orders > RWF 20,000</div>
                    </div>
                    <div class="pricing-row">
                        <div class="pricing-cell">Northern Province</div>
                        <div class="pricing-cell">RWF 2,000</div>
                        <div class="pricing-cell">RWF 3,500</div>
                        <div class="pricing-cell">Orders > RWF 30,000</div>
                    </div>
                    <div class="pricing-row">
                        <div class="pricing-cell">Kigali City</div>
                        <div class="pricing-cell">RWF 2,500</div>
                        <div class="pricing-cell">RWF 4,000</div>
                        <div class="pricing-cell">Orders > RWF 35,000</div>
                    </div>
                    <div class="pricing-row">
                        <div class="pricing-cell">Other Provinces</div>
                        <div class="pricing-cell">RWF 3,000</div>
                        <div class="pricing-cell">RWF 5,000</div>
                        <div class="pricing-cell">Orders > RWF 50,000</div>
                    </div>
                </div>
                
                <div style="margin-top: 2rem; padding: 1.5rem; background: #f0fdf4; border-radius: 8px; border-left: 4px solid #10b981;">
                    <h5 style="color: #059669; margin-bottom: 1rem;">💡 Money-Saving Tips</h5>
                    <ul style="color: #065f46;">
                        <li>Combine orders from multiple vendors to reach free shipping thresholds</li>
                        <li>Join our newsletter for exclusive free shipping promotions</li>
                        <li>Consider standard shipping for non-urgent items</li>
                        <li>Local pickup available from some Musanze vendors</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Special Services -->
<section class="shipping-section" style="background: #f8f9fa;">
    <div class="container">
        <h2 class="text-center mb-4">Special Services</h2>
        
        <div class="row">
            <div class="col-6">
                <div class="shipping-card">
                    <h4><i class="fas fa-box-open" style="color: #10b981; margin-right: 0.5rem;"></i> Fragile Item Handling</h4>
                    <p>Special packaging and handling for delicate items like electronics, glassware, and artwork. Additional RWF 500 fee applies.</p>
                </div>
                
                <div class="shipping-card">
                    <h4><i class="fas fa-calendar-check" style="color: #10b981; margin-right: 0.5rem;"></i> Scheduled Delivery</h4>
                    <p>Choose your preferred delivery date and time slot. Perfect for gifts or when you need to be present for delivery.</p>
                </div>
            </div>
            <div class="col-6">
                <div class="shipping-card">
                    <h4><i class="fas fa-hand-holding-heart" style="color: #10b981; margin-right: 0.5rem;"></i> White Glove Service</h4>
                    <p>Premium delivery service including unpacking, setup, and installation for large items. Available in Musanze District.</p>
                </div>
                
                <div class="shipping-card">
                    <h4><i class="fas fa-store" style="color: #10b981; margin-right: 0.5rem;"></i> Pickup Points</h4>
                    <p>Collect your orders from convenient pickup points in Musanze town. Free service with extended collection hours.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Order Tracking -->
<section class="tracking-section">
    <div class="container">
        <h2>Track Your Order</h2>
        <p style="font-size: 1.1rem; margin-bottom: 2rem;">Enter your tracking number to see real-time delivery updates</p>
        
        <div class="tracking-input">
            <input type="text" placeholder="Enter tracking number (e.g., MH123456789)" id="trackingNumber">
            <button onclick="trackOrder()">
                <i class="fas fa-search"></i> Track
            </button>
        </div>
        
        <div style="margin-top: 2rem;">
            <p><small>Don't have a tracking number? Check your order confirmation email or log into your account.</small></p>
        </div>
    </div>
</section>

<!-- Important Information -->
<section class="shipping-section">
    <div class="container">
        <div class="row">
            <div class="col-6">
                <h3 style="color: #10b981; margin-bottom: 2rem;">📋 Important Information</h3>
                
                <div style="background: #fff3cd; padding: 1.5rem; border-radius: 8px; border-left: 4px solid #ffc107; margin-bottom: 2rem;">
                    <h5 style="color: #856404;">⚠️ Delivery Requirements</h5>
                    <ul style="color: #856404; margin-bottom: 0;">
                        <li>Someone must be present to receive the delivery</li>
                        <li>Valid ID required for high-value items</li>
                        <li>Accurate phone number essential for delivery coordination</li>
                        <li>Clear delivery address with landmarks</li>
                    </ul>
                </div>
                
                <div style="background: #d1ecf1; padding: 1.5rem; border-radius: 8px; border-left: 4px solid #17a2b8;">
                    <h5 style="color: #0c5460;">📞 Delivery Issues?</h5>
                    <p style="color: #0c5460; margin-bottom: 0;">
                        If you experience any delivery issues, contact our support team at 
                        <strong>+250 788 123 456</strong> or email <strong>shipping@markethub.com</strong>
                    </p>
                </div>
            </div>
            <div class="col-6">
                <h3 style="color: #10b981; margin-bottom: 2rem;">🎁 Gift Delivery</h3>
                
                <div style="background: #f8f9fa; padding: 2rem; border-radius: 12px;">
                    <p>Sending a gift? We offer special gift services:</p>
                    <ul>
                        <li><strong>Gift Wrapping:</strong> Beautiful wrapping available for RWF 500</li>
                        <li><strong>Gift Messages:</strong> Include a personal message with your gift</li>
                        <li><strong>Surprise Delivery:</strong> Coordinate surprise deliveries</li>
                        <li><strong>Gift Receipts:</strong> Price-free receipts for gift recipients</li>
                    </ul>
                    
                    <div style="margin-top: 1.5rem; text-align: center;">
                        <a href="products.php" class="btn btn-primary">
                            <i class="fas fa-gift"></i> Shop for Gifts
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
function trackOrder() {
    const trackingNumber = document.getElementById('trackingNumber').value.trim();
    
    if (!trackingNumber) {
        alert('Please enter a tracking number');
        return;
    }
    
    // In a real implementation, this would make an API call
    alert('Tracking feature will be implemented soon. For now, please contact support for order updates.');
}

// Auto-format tracking number input
document.getElementById('trackingNumber').addEventListener('input', function() {
    let value = this.value.replace(/[^a-zA-Z0-9]/g, '').toUpperCase();
    if (value.length > 2 && !value.startsWith('MH')) {
        value = 'MH' + value;
    }
    this.value = value;
});
</script>

<?php require_once 'includes/footer.php'; ?>
