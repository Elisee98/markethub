<?php
/**
 * Vendor Guide - MarketHub
 */

require_once 'config/config.php';

$page_title = 'Vendor Guide - MarketHub';
$page_description = 'Complete guide for vendors on how to sell successfully on MarketHub.';

require_once 'includes/header.php';
?>

<style>
.guide-hero {
    background: linear-gradient(135deg, #10b981, #059669);
    color: white;
    padding: 4rem 0;
    text-align: center;
}

.guide-hero h1 {
    font-size: 2.5rem;
    font-weight: 800;
    margin-bottom: 1rem;
}

.guide-section {
    padding: 4rem 0;
}

.guide-card {
    background: white;
    border-radius: 12px;
    padding: 2rem;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    margin-bottom: 2rem;
    border-left: 4px solid #10b981;
}

.guide-icon {
    width: 80px;
    height: 80px;
    background: #10b981;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 2rem;
    margin: 0 auto 1rem;
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
    margin-bottom: 1rem;
}

.benefits-section {
    background: #f8f9fa;
    padding: 4rem 0;
}

.benefit-card {
    background: white;
    border-radius: 12px;
    padding: 2rem;
    text-align: center;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    margin-bottom: 2rem;
    transition: all 0.3s;
}

.benefit-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.15);
}

.cta-section {
    background: linear-gradient(135deg, #10b981, #059669);
    color: white;
    padding: 4rem 0;
    text-align: center;
}

.btn-white {
    background: white;
    color: #10b981;
    border: 2px solid white;
    padding: 1rem 2rem;
    border-radius: 8px;
    text-decoration: none;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    transition: all 0.3s;
}

.btn-white:hover {
    background: transparent;
    color: white;
}
</style>

<!-- Hero Section -->
<section class="guide-hero">
    <div class="container">
        <h1>📚 Vendor Success Guide</h1>
        <p>Everything you need to know to succeed as a MarketHub vendor</p>
    </div>
</section>

<!-- Getting Started -->
<section class="guide-section">
    <div class="container">
        <h2 class="text-center mb-4">Getting Started</h2>
        <p class="text-center mb-5" style="font-size: 1.1rem; color: #6b7280;">
            Follow these steps to set up your vendor account and start selling
        </p>
        
        <div class="row">
            <div class="col-4">
                <div class="guide-card text-center">
                    <div class="step-number mx-auto">1</div>
                    <h4>Create Your Account</h4>
                    <p>Sign up as a vendor with your business information and required documents.</p>
                    <ul style="text-align: left; margin-top: 1rem;">
                        <li>Business registration certificate</li>
                        <li>Tax identification number</li>
                        <li>Bank account details</li>
                        <li>Valid ID or passport</li>
                    </ul>
                </div>
            </div>
            <div class="col-4">
                <div class="guide-card text-center">
                    <div class="step-number mx-auto">2</div>
                    <h4>Set Up Your Store</h4>
                    <p>Create an attractive store profile that builds customer trust and confidence.</p>
                    <ul style="text-align: left; margin-top: 1rem;">
                        <li>Store name and description</li>
                        <li>Logo and banner images</li>
                        <li>Contact information</li>
                        <li>Business hours and policies</li>
                    </ul>
                </div>
            </div>
            <div class="col-4">
                <div class="guide-card text-center">
                    <div class="step-number mx-auto">3</div>
                    <h4>Add Your Products</h4>
                    <p>List your products with detailed descriptions and high-quality images.</p>
                    <ul style="text-align: left; margin-top: 1rem;">
                        <li>Product photos (multiple angles)</li>
                        <li>Detailed descriptions</li>
                        <li>Accurate pricing</li>
                        <li>Inventory management</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Benefits -->
<section class="benefits-section">
    <div class="container">
        <h2 class="text-center mb-4">Why Sell on MarketHub?</h2>
        
        <div class="row">
            <div class="col-4">
                <div class="benefit-card">
                    <div class="guide-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <h4>Large Customer Base</h4>
                    <p>Access thousands of customers across Musanze District and Rwanda looking for quality products.</p>
                </div>
            </div>
            <div class="col-4">
                <div class="benefit-card">
                    <div class="guide-icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <h4>Grow Your Business</h4>
                    <p>Expand your reach beyond physical location and grow your sales with our marketing tools.</p>
                </div>
            </div>
            <div class="col-4">
                <div class="benefit-card">
                    <div class="guide-icon">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <h4>Secure Payments</h4>
                    <p>Get paid securely and on time with our reliable payment processing system.</p>
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-4">
                <div class="benefit-card">
                    <div class="guide-icon">
                        <i class="fas fa-tools"></i>
                    </div>
                    <h4>Easy-to-Use Tools</h4>
                    <p>Manage your inventory, orders, and customers with our intuitive vendor dashboard.</p>
                </div>
            </div>
            <div class="col-4">
                <div class="benefit-card">
                    <div class="guide-icon">
                        <i class="fas fa-headset"></i>
                    </div>
                    <h4>Dedicated Support</h4>
                    <p>Get help when you need it with our dedicated vendor support team.</p>
                </div>
            </div>
            <div class="col-4">
                <div class="benefit-card">
                    <div class="guide-icon">
                        <i class="fas fa-percentage"></i>
                    </div>
                    <h4>Low Fees</h4>
                    <p>Keep more of your profits with our competitive 5% commission rate and no monthly fees.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Best Practices -->
<section class="guide-section">
    <div class="container">
        <h2 class="text-center mb-4">Best Practices for Success</h2>
        
        <div class="row">
            <div class="col-6">
                <div class="guide-card">
                    <h4><i class="fas fa-camera" style="color: #10b981; margin-right: 0.5rem;"></i> Product Photography</h4>
                    <ul>
                        <li>Use high-resolution images (at least 1000x1000 pixels)</li>
                        <li>Show multiple angles and details</li>
                        <li>Use good lighting and clean backgrounds</li>
                        <li>Include lifestyle shots when appropriate</li>
                        <li>Ensure images accurately represent the product</li>
                    </ul>
                </div>
                
                <div class="guide-card">
                    <h4><i class="fas fa-edit" style="color: #10b981; margin-right: 0.5rem;"></i> Product Descriptions</h4>
                    <ul>
                        <li>Write clear, detailed descriptions</li>
                        <li>Include key features and benefits</li>
                        <li>Mention dimensions, materials, and specifications</li>
                        <li>Use bullet points for easy reading</li>
                        <li>Include care instructions if applicable</li>
                    </ul>
                </div>
            </div>
            
            <div class="col-6">
                <div class="guide-card">
                    <h4><i class="fas fa-dollar-sign" style="color: #10b981; margin-right: 0.5rem;"></i> Pricing Strategy</h4>
                    <ul>
                        <li>Research competitor pricing</li>
                        <li>Consider your costs and desired profit margin</li>
                        <li>Offer competitive prices for similar products</li>
                        <li>Use promotional pricing strategically</li>
                        <li>Update prices regularly based on market conditions</li>
                    </ul>
                </div>
                
                <div class="guide-card">
                    <h4><i class="fas fa-shipping-fast" style="color: #10b981; margin-right: 0.5rem;"></i> Order Fulfillment</h4>
                    <ul>
                        <li>Process orders within 24 hours</li>
                        <li>Package items securely to prevent damage</li>
                        <li>Provide tracking information when available</li>
                        <li>Communicate with customers about delays</li>
                        <li>Maintain adequate inventory levels</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Customer Service -->
<section class="guide-section" style="background: #f8f9fa;">
    <div class="container">
        <h2 class="text-center mb-4">Excellent Customer Service</h2>
        
        <div class="row">
            <div class="col-8 mx-auto">
                <div class="guide-card">
                    <h4 style="color: #10b981; margin-bottom: 2rem;">Tips for Outstanding Customer Service</h4>
                    
                    <div class="row">
                        <div class="col-6">
                            <h5><i class="fas fa-comments"></i> Communication</h5>
                            <ul>
                                <li>Respond to messages within 24 hours</li>
                                <li>Be professional and friendly</li>
                                <li>Provide clear and helpful information</li>
                                <li>Keep customers updated on order status</li>
                            </ul>
                        </div>
                        <div class="col-6">
                            <h5><i class="fas fa-undo"></i> Returns & Refunds</h5>
                            <ul>
                                <li>Have a clear return policy</li>
                                <li>Process returns promptly</li>
                                <li>Be understanding of customer concerns</li>
                                <li>Use returns as learning opportunities</li>
                            </ul>
                        </div>
                    </div>
                    
                    <div style="background: #e0f2fe; padding: 1.5rem; border-radius: 8px; margin-top: 2rem;">
                        <h6 style="color: #01579b; margin-bottom: 1rem;">💡 Pro Tip</h6>
                        <p style="color: #01579b; margin-bottom: 0;">
                            Happy customers leave positive reviews and become repeat buyers. 
                            Invest time in providing excellent service - it pays off in the long run!
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Marketing Tips -->
<section class="guide-section">
    <div class="container">
        <h2 class="text-center mb-4">Marketing Your Products</h2>
        
        <div class="row">
            <div class="col-4">
                <div class="guide-card text-center">
                    <div class="guide-icon">
                        <i class="fas fa-search"></i>
                    </div>
                    <h4>SEO Optimization</h4>
                    <p>Use relevant keywords in your product titles and descriptions to help customers find your products.</p>
                </div>
            </div>
            <div class="col-4">
                <div class="guide-card text-center">
                    <div class="guide-icon">
                        <i class="fas fa-star"></i>
                    </div>
                    <h4>Build Reviews</h4>
                    <p>Encourage satisfied customers to leave reviews. Positive reviews build trust and increase sales.</p>
                </div>
            </div>
            <div class="col-4">
                <div class="guide-card text-center">
                    <div class="guide-icon">
                        <i class="fas fa-tags"></i>
                    </div>
                    <h4>Promotions</h4>
                    <p>Run special promotions and discounts to attract new customers and boost sales during slow periods.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Performance Metrics -->
<section class="guide-section" style="background: #f8f9fa;">
    <div class="container">
        <h2 class="text-center mb-4">Track Your Performance</h2>
        
        <div class="row">
            <div class="col-6">
                <div class="guide-card">
                    <h4><i class="fas fa-chart-bar" style="color: #10b981; margin-right: 0.5rem;"></i> Key Metrics to Monitor</h4>
                    <ul>
                        <li><strong>Sales Volume:</strong> Track your monthly and weekly sales</li>
                        <li><strong>Conversion Rate:</strong> Percentage of visitors who buy</li>
                        <li><strong>Average Order Value:</strong> How much customers spend per order</li>
                        <li><strong>Customer Reviews:</strong> Monitor your rating and feedback</li>
                        <li><strong>Return Rate:</strong> Percentage of orders returned</li>
                    </ul>
                </div>
            </div>
            <div class="col-6">
                <div class="guide-card">
                    <h4><i class="fas fa-target" style="color: #10b981; margin-right: 0.5rem;"></i> Setting Goals</h4>
                    <ul>
                        <li>Set realistic monthly sales targets</li>
                        <li>Aim for high customer satisfaction ratings</li>
                        <li>Work towards reducing return rates</li>
                        <li>Increase your product catalog gradually</li>
                        <li>Build a loyal customer base</li>
                    </ul>
                    
                    <div style="background: #f0fdf4; padding: 1rem; border-radius: 8px; margin-top: 1rem; border-left: 4px solid #10b981;">
                        <p style="margin-bottom: 0; color: #065f46;">
                            <strong>Success Tip:</strong> Use the vendor dashboard analytics to track your progress and identify areas for improvement.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- CTA Section -->
<section class="cta-section">
    <div class="container">
        <h2>Ready to Start Selling?</h2>
        <p style="font-size: 1.2rem; margin-bottom: 2rem; opacity: 0.9;">
            Join thousands of successful vendors on MarketHub and grow your business today!
        </p>
        <div style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
            <a href="vendor/register.php" class="btn-white">
                <i class="fas fa-user-plus"></i> Become a Vendor
            </a>
            <a href="vendor/login.php" class="btn-white">
                <i class="fas fa-sign-in-alt"></i> Vendor Login
            </a>
            <a href="contact.php" class="btn-white">
                <i class="fas fa-envelope"></i> Contact Support
            </a>
        </div>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>
