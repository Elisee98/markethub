<?php
/**
 * Returns & Refunds Policy - MarketHub
 */

require_once 'config/config.php';

$page_title = 'Returns & Refunds - MarketHub';
$page_description = 'Learn about our return policy, refund process, and how to return items.';

require_once 'includes/header.php';
?>

<style>
.returns-hero {
    background: linear-gradient(135deg, #10b981, #059669);
    color: white;
    padding: 4rem 0;
    text-align: center;
}

.returns-hero h1 {
    font-size: 2.5rem;
    font-weight: 800;
    margin-bottom: 1rem;
}

.policy-section {
    padding: 4rem 0;
}

.policy-card {
    background: white;
    border-radius: 12px;
    padding: 2rem;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    margin-bottom: 2rem;
    border-left: 4px solid #10b981;
}

.policy-icon {
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

.process-section {
    background: #f8f9fa;
    padding: 4rem 0;
}

.process-step {
    text-align: center;
    margin-bottom: 2rem;
}

.step-number {
    width: 60px;
    height: 60px;
    background: #10b981;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.5rem;
    font-weight: 700;
    margin: 0 auto 1rem;
}

.conditions-section {
    padding: 4rem 0;
}

.condition-item {
    display: flex;
    align-items: flex-start;
    gap: 1rem;
    margin-bottom: 1.5rem;
    padding: 1rem;
    background: #f8f9fa;
    border-radius: 8px;
}

.condition-icon {
    width: 40px;
    height: 40px;
    background: #10b981;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1rem;
    flex-shrink: 0;
}

.refund-timeline {
    background: linear-gradient(135deg, #10b981, #059669);
    color: white;
    padding: 4rem 0;
}

.timeline-item {
    background: rgba(255,255,255,0.1);
    border-radius: 12px;
    padding: 2rem;
    margin-bottom: 2rem;
    text-align: center;
}

.timeline-icon {
    font-size: 2.5rem;
    margin-bottom: 1rem;
}

.contact-section {
    padding: 4rem 0;
    background: #f8f9fa;
}

.contact-card {
    background: white;
    border-radius: 12px;
    padding: 2rem;
    text-align: center;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    margin-bottom: 2rem;
}

.contact-icon {
    font-size: 2.5rem;
    color: #10b981;
    margin-bottom: 1rem;
}
</style>

<!-- Hero Section -->
<section class="returns-hero">
    <div class="container">
        <h1>🔄 Returns & Refunds</h1>
        <p>Easy returns and hassle-free refunds for your peace of mind</p>
    </div>
</section>

<!-- Policy Overview -->
<section class="policy-section">
    <div class="container">
        <div class="row">
            <div class="col-4">
                <div class="policy-card">
                    <div class="policy-icon">
                        <i class="fas fa-calendar-alt"></i>
                    </div>
                    <h4>30-Day Return Window</h4>
                    <p>You have 30 days from delivery to return most items. Some categories may have different return periods.</p>
                </div>
            </div>
            <div class="col-4">
                <div class="policy-card">
                    <div class="policy-icon">
                        <i class="fas fa-money-bill-wave"></i>
                    </div>
                    <h4>Full Refunds</h4>
                    <p>Get your money back in full for eligible returns. Refunds are processed to your original payment method.</p>
                </div>
            </div>
            <div class="col-4">
                <div class="policy-card">
                    <div class="policy-icon">
                        <i class="fas fa-shipping-fast"></i>
                    </div>
                    <h4>Free Return Shipping</h4>
                    <p>We provide free return shipping labels for defective items and our mistakes. Other returns may have shipping fees.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Return Process -->
<section class="process-section">
    <div class="container">
        <h2 class="text-center mb-4">How to Return an Item</h2>
        <p class="text-center mb-5" style="font-size: 1.1rem; color: #6b7280;">
            Follow these simple steps to return your item
        </p>
        
        <div class="row">
            <div class="col-3">
                <div class="process-step">
                    <div class="step-number">1</div>
                    <h5>Initiate Return</h5>
                    <p>Log into your account and go to "My Orders". Find the item you want to return and click "Return Item".</p>
                </div>
            </div>
            <div class="col-3">
                <div class="process-step">
                    <div class="step-number">2</div>
                    <h5>Select Reason</h5>
                    <p>Choose the reason for your return and provide any additional details. Upload photos if the item is damaged.</p>
                </div>
            </div>
            <div class="col-3">
                <div class="process-step">
                    <div class="step-number">3</div>
                    <h5>Package & Ship</h5>
                    <p>Pack the item securely in its original packaging. Print the return label and drop off at any courier location.</p>
                </div>
            </div>
            <div class="col-3">
                <div class="process-step">
                    <div class="step-number">4</div>
                    <h5>Get Refund</h5>
                    <p>Once we receive and inspect your return, we'll process your refund within 3-5 business days.</p>
                </div>
            </div>
        </div>
        
        <div class="text-center mt-4">
            <a href="orders.php" class="btn btn-primary">
                <i class="fas fa-undo"></i> Start a Return
            </a>
        </div>
    </div>
</section>

<!-- Return Conditions -->
<section class="conditions-section">
    <div class="container">
        <div class="row">
            <div class="col-6">
                <h3 style="color: #10b981; margin-bottom: 2rem;">✅ Items You Can Return</h3>
                
                <div class="condition-item">
                    <div class="condition-icon">
                        <i class="fas fa-check"></i>
                    </div>
                    <div>
                        <h6>Unused Items</h6>
                        <p>Items in original condition with all tags and packaging intact.</p>
                    </div>
                </div>
                
                <div class="condition-item">
                    <div class="condition-icon">
                        <i class="fas fa-check"></i>
                    </div>
                    <div>
                        <h6>Defective Products</h6>
                        <p>Items that arrived damaged or don't work as described.</p>
                    </div>
                </div>
                
                <div class="condition-item">
                    <div class="condition-icon">
                        <i class="fas fa-check"></i>
                    </div>
                    <div>
                        <h6>Wrong Items</h6>
                        <p>If you received the wrong item or size, we'll make it right.</p>
                    </div>
                </div>
                
                <div class="condition-item">
                    <div class="condition-icon">
                        <i class="fas fa-check"></i>
                    </div>
                    <div>
                        <h6>Size/Fit Issues</h6>
                        <p>Clothing and shoes can be returned if they don't fit properly.</p>
                    </div>
                </div>
            </div>
            
            <div class="col-6">
                <h3 style="color: #ef4444; margin-bottom: 2rem;">❌ Items You Cannot Return</h3>
                
                <div class="condition-item" style="background: #fef2f2; border-left: 4px solid #ef4444;">
                    <div class="condition-icon" style="background: #ef4444;">
                        <i class="fas fa-times"></i>
                    </div>
                    <div>
                        <h6>Perishable Goods</h6>
                        <p>Food items, fresh produce, and other perishables cannot be returned.</p>
                    </div>
                </div>
                
                <div class="condition-item" style="background: #fef2f2; border-left: 4px solid #ef4444;">
                    <div class="condition-icon" style="background: #ef4444;">
                        <i class="fas fa-times"></i>
                    </div>
                    <div>
                        <h6>Personal Care Items</h6>
                        <p>Cosmetics, toiletries, and personal hygiene products for health reasons.</p>
                    </div>
                </div>
                
                <div class="condition-item" style="background: #fef2f2; border-left: 4px solid #ef4444;">
                    <div class="condition-icon" style="background: #ef4444;">
                        <i class="fas fa-times"></i>
                    </div>
                    <div>
                        <h6>Custom/Personalized Items</h6>
                        <p>Items made specifically for you or with personal customizations.</p>
                    </div>
                </div>
                
                <div class="condition-item" style="background: #fef2f2; border-left: 4px solid #ef4444;">
                    <div class="condition-icon" style="background: #ef4444;">
                        <i class="fas fa-times"></i>
                    </div>
                    <div>
                        <h6>Digital Products</h6>
                        <p>Software, digital downloads, and gift cards cannot be returned.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Refund Timeline -->
<section class="refund-timeline">
    <div class="container">
        <h2 class="text-center mb-4">Refund Timeline</h2>
        <p class="text-center mb-5" style="font-size: 1.1rem; opacity: 0.9;">
            Here's what to expect after you return an item
        </p>
        
        <div class="row">
            <div class="col-4">
                <div class="timeline-item">
                    <div class="timeline-icon">
                        <i class="fas fa-box"></i>
                    </div>
                    <h5>Item Received</h5>
                    <p><strong>Day 1-2:</strong> We receive your returned item and send you a confirmation email.</p>
                </div>
            </div>
            <div class="col-4">
                <div class="timeline-item">
                    <div class="timeline-icon">
                        <i class="fas fa-search"></i>
                    </div>
                    <h5>Quality Check</h5>
                    <p><strong>Day 2-3:</strong> Our team inspects the item to ensure it meets return conditions.</p>
                </div>
            </div>
            <div class="col-4">
                <div class="timeline-item">
                    <div class="timeline-icon">
                        <i class="fas fa-money-check-alt"></i>
                    </div>
                    <h5>Refund Processed</h5>
                    <p><strong>Day 3-5:</strong> Your refund is processed and you receive confirmation.</p>
                </div>
            </div>
        </div>
        
        <div style="background: rgba(255,255,255,0.1); padding: 2rem; border-radius: 12px; margin-top: 3rem; text-align: center;">
            <h4>💡 Refund Methods</h4>
            <div class="row" style="margin-top: 2rem;">
                <div class="col-3">
                    <p><strong>Mobile Money</strong><br>1-2 business days</p>
                </div>
                <div class="col-3">
                    <p><strong>Bank Transfer</strong><br>2-3 business days</p>
                </div>
                <div class="col-3">
                    <p><strong>Credit Card</strong><br>3-5 business days</p>
                </div>
                <div class="col-3">
                    <p><strong>Store Credit</strong><br>Instant</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Special Cases -->
<section class="policy-section">
    <div class="container">
        <h2 class="text-center mb-4">Special Return Cases</h2>
        
        <div class="row">
            <div class="col-6">
                <div class="policy-card">
                    <h4><i class="fas fa-exclamation-triangle" style="color: #f59e0b; margin-right: 0.5rem;"></i> Damaged Items</h4>
                    <p>If your item arrived damaged:</p>
                    <ul>
                        <li>Take photos of the damage immediately</li>
                        <li>Contact us within 48 hours of delivery</li>
                        <li>We'll arrange free return shipping</li>
                        <li>Full refund or replacement guaranteed</li>
                    </ul>
                </div>
            </div>
            <div class="col-6">
                <div class="policy-card">
                    <h4><i class="fas fa-gift" style="color: #10b981; margin-right: 0.5rem;"></i> Gift Returns</h4>
                    <p>Returning a gift is easy:</p>
                    <ul>
                        <li>No receipt needed for gift returns</li>
                        <li>Refund issued as store credit</li>
                        <li>Original purchaser can request cash refund</li>
                        <li>Same 30-day return window applies</li>
                    </ul>
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-6">
                <div class="policy-card">
                    <h4><i class="fas fa-exchange-alt" style="color: #6366f1; margin-right: 0.5rem;"></i> Exchanges</h4>
                    <p>Want a different size or color?</p>
                    <ul>
                        <li>Free exchanges for size/color changes</li>
                        <li>Subject to availability</li>
                        <li>Price difference may apply</li>
                        <li>Faster than return + new order</li>
                    </ul>
                </div>
            </div>
            <div class="col-6">
                <div class="policy-card">
                    <h4><i class="fas fa-clock" style="color: #ef4444; margin-right: 0.5rem;"></i> Late Returns</h4>
                    <p>Missed the 30-day window?</p>
                    <ul>
                        <li>Contact us - we may still help</li>
                        <li>Defective items accepted beyond 30 days</li>
                        <li>Store credit may be offered</li>
                        <li>Case-by-case evaluation</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Contact Section -->
<section class="contact-section">
    <div class="container">
        <h2 class="text-center mb-4">Need Help with Returns?</h2>
        <p class="text-center mb-5" style="color: #6b7280;">
            Our customer service team is here to help with any return questions
        </p>
        
        <div class="row">
            <div class="col-4">
                <div class="contact-card">
                    <div class="contact-icon">
                        <i class="fas fa-envelope"></i>
                    </div>
                    <h5>Email Support</h5>
                    <p>returns@markethub.com</p>
                    <p><small>Response within 24 hours</small></p>
                </div>
            </div>
            <div class="col-4">
                <div class="contact-card">
                    <div class="contact-icon">
                        <i class="fas fa-phone"></i>
                    </div>
                    <h5>Phone Support</h5>
                    <p>+250 788 123 456</p>
                    <p><small>Mon-Fri: 8AM-6PM</small></p>
                </div>
            </div>
            <div class="col-4">
                <div class="contact-card">
                    <div class="contact-icon">
                        <i class="fas fa-comments"></i>
                    </div>
                    <h5>Live Chat</h5>
                    <p>Available on website</p>
                    <p><small>Mon-Fri: 9AM-5PM</small></p>
                </div>
            </div>
        </div>
        
        <div class="text-center mt-4">
            <a href="contact.php" class="btn btn-primary">
                <i class="fas fa-headset"></i> Contact Support
            </a>
        </div>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>
