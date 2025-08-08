<?php
/**
 * Help Center - MarketHub
 */

require_once 'config/config.php';

$page_title = 'Help Center - MarketHub';
$page_description = 'Find answers to your questions and get help with MarketHub services.';

require_once 'includes/header.php';
?>

<style>
.help-hero {
    background: linear-gradient(135deg, #10b981, #059669);
    color: white;
    padding: 4rem 0;
    text-align: center;
}

.help-hero h1 {
    font-size: 2.5rem;
    font-weight: 800;
    margin-bottom: 1rem;
}

.search-help {
    max-width: 600px;
    margin: 2rem auto 0;
    position: relative;
}

.search-help input {
    width: 100%;
    padding: 1rem 1rem 1rem 3rem;
    border: none;
    border-radius: 50px;
    font-size: 1.1rem;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
}

.search-help i {
    position: absolute;
    left: 1rem;
    top: 50%;
    transform: translateY(-50%);
    color: #6b7280;
}

.help-categories {
    padding: 4rem 0;
    background: #f8f9fa;
}

.help-category {
    background: white;
    border-radius: 12px;
    padding: 2rem;
    text-align: center;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    transition: all 0.3s;
    margin-bottom: 2rem;
}

.help-category:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.15);
}

.help-icon {
    width: 80px;
    height: 80px;
    background: #10b981;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 1rem;
    color: white;
    font-size: 2rem;
}

.faq-section {
    padding: 4rem 0;
}

.faq-item {
    background: white;
    border-radius: 12px;
    margin-bottom: 1rem;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    overflow: hidden;
}

.faq-question {
    padding: 1.5rem;
    background: #f8f9fa;
    cursor: pointer;
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-weight: 600;
    color: #1f2937;
}

.faq-answer {
    padding: 1.5rem;
    display: none;
    border-top: 1px solid #e5e7eb;
}

.faq-answer.active {
    display: block;
}

.contact-help {
    background: linear-gradient(135deg, #10b981, #059669);
    color: white;
    padding: 4rem 0;
    text-align: center;
}

.contact-method {
    background: rgba(255,255,255,0.1);
    border-radius: 12px;
    padding: 2rem;
    margin-bottom: 2rem;
}

.contact-icon {
    font-size: 2.5rem;
    margin-bottom: 1rem;
}
</style>

<!-- Hero Section -->
<section class="help-hero">
    <div class="container">
        <h1>🆘 Help Center</h1>
        <p>Find answers to your questions and get the help you need</p>
        
        <div class="search-help">
            <i class="fas fa-search"></i>
            <input type="text" placeholder="Search for help topics..." id="helpSearch">
        </div>
    </div>
</section>

<!-- Help Categories -->
<section class="help-categories">
    <div class="container">
        <h2 class="text-center mb-4">How can we help you?</h2>
        
        <div class="row">
            <div class="col-4">
                <div class="help-category">
                    <div class="help-icon">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                    <h4>Orders & Shopping</h4>
                    <p>Help with placing orders, tracking shipments, and managing your purchases.</p>
                    <a href="#orders-faq" class="btn btn-primary">Learn More</a>
                </div>
            </div>
            <div class="col-4">
                <div class="help-category">
                    <div class="help-icon">
                        <i class="fas fa-user"></i>
                    </div>
                    <h4>Account & Profile</h4>
                    <p>Manage your account settings, profile information, and security preferences.</p>
                    <a href="#account-faq" class="btn btn-primary">Learn More</a>
                </div>
            </div>
            <div class="col-4">
                <div class="help-category">
                    <div class="help-icon">
                        <i class="fas fa-credit-card"></i>
                    </div>
                    <h4>Payments & Billing</h4>
                    <p>Information about payment methods, billing, and transaction issues.</p>
                    <a href="#payment-faq" class="btn btn-primary">Learn More</a>
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-4">
                <div class="help-category">
                    <div class="help-icon">
                        <i class="fas fa-truck"></i>
                    </div>
                    <h4>Shipping & Delivery</h4>
                    <p>Track your orders, understand delivery times, and shipping policies.</p>
                    <a href="#shipping-faq" class="btn btn-primary">Learn More</a>
                </div>
            </div>
            <div class="col-4">
                <div class="help-category">
                    <div class="help-icon">
                        <i class="fas fa-undo"></i>
                    </div>
                    <h4>Returns & Refunds</h4>
                    <p>Learn about our return policy and how to process refunds.</p>
                    <a href="#returns-faq" class="btn btn-primary">Learn More</a>
                </div>
            </div>
            <div class="col-4">
                <div class="help-category">
                    <div class="help-icon">
                        <i class="fas fa-store"></i>
                    </div>
                    <h4>For Vendors</h4>
                    <p>Help for vendors on managing stores, products, and orders.</p>
                    <a href="vendor-support.php" class="btn btn-primary">Vendor Help</a>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- FAQ Section -->
<section class="faq-section">
    <div class="container">
        <h2 class="text-center mb-4">Frequently Asked Questions</h2>
        
        <div class="row">
            <div class="col-8 mx-auto">
                <!-- Orders FAQ -->
                <div id="orders-faq">
                    <h3 style="color: #10b981; margin-bottom: 2rem;">📦 Orders & Shopping</h3>
                    
                    <div class="faq-item">
                        <div class="faq-question" onclick="toggleFAQ(this)">
                            How do I place an order?
                            <i class="fas fa-chevron-down"></i>
                        </div>
                        <div class="faq-answer">
                            <p>To place an order:</p>
                            <ol>
                                <li>Browse products and add items to your cart</li>
                                <li>Click on the cart icon and review your items</li>
                                <li>Proceed to checkout and enter your shipping information</li>
                                <li>Choose your payment method and complete the purchase</li>
                                <li>You'll receive an order confirmation email</li>
                            </ol>
                        </div>
                    </div>
                    
                    <div class="faq-item">
                        <div class="faq-question" onclick="toggleFAQ(this)">
                            How can I track my order?
                            <i class="fas fa-chevron-down"></i>
                        </div>
                        <div class="faq-answer">
                            <p>You can track your order by:</p>
                            <ul>
                                <li>Logging into your account and visiting the "My Orders" section</li>
                                <li>Using the tracking number provided in your confirmation email</li>
                                <li>Contacting the vendor directly for updates</li>
                            </ul>
                        </div>
                    </div>
                    
                    <div class="faq-item">
                        <div class="faq-question" onclick="toggleFAQ(this)">
                            Can I modify or cancel my order?
                            <i class="fas fa-chevron-down"></i>
                        </div>
                        <div class="faq-answer">
                            <p>Order modifications depend on the order status:</p>
                            <ul>
                                <li><strong>Pending:</strong> You can usually modify or cancel</li>
                                <li><strong>Processing:</strong> Contact the vendor immediately</li>
                                <li><strong>Shipped:</strong> Modifications not possible, but returns may be available</li>
                            </ul>
                        </div>
                    </div>
                </div>
                
                <!-- Account FAQ -->
                <div id="account-faq" style="margin-top: 3rem;">
                    <h3 style="color: #10b981; margin-bottom: 2rem;">👤 Account & Profile</h3>
                    
                    <div class="faq-item">
                        <div class="faq-question" onclick="toggleFAQ(this)">
                            How do I create an account?
                            <i class="fas fa-chevron-down"></i>
                        </div>
                        <div class="faq-answer">
                            <p>Creating an account is easy:</p>
                            <ol>
                                <li>Click "Register" in the top navigation</li>
                                <li>Fill in your personal information</li>
                                <li>Choose a secure password</li>
                                <li>Verify your email address</li>
                                <li>Start shopping!</li>
                            </ol>
                        </div>
                    </div>
                    
                    <div class="faq-item">
                        <div class="faq-question" onclick="toggleFAQ(this)">
                            How do I reset my password?
                            <i class="fas fa-chevron-down"></i>
                        </div>
                        <div class="faq-answer">
                            <p>To reset your password:</p>
                            <ol>
                                <li>Go to the login page</li>
                                <li>Click "Forgot Password?"</li>
                                <li>Enter your email address</li>
                                <li>Check your email for reset instructions</li>
                                <li>Follow the link to create a new password</li>
                            </ol>
                        </div>
                    </div>
                </div>
                
                <!-- Payment FAQ -->
                <div id="payment-faq" style="margin-top: 3rem;">
                    <h3 style="color: #10b981; margin-bottom: 2rem;">💳 Payments & Billing</h3>
                    
                    <div class="faq-item">
                        <div class="faq-question" onclick="toggleFAQ(this)">
                            What payment methods do you accept?
                            <i class="fas fa-chevron-down"></i>
                        </div>
                        <div class="faq-answer">
                            <p>We accept various payment methods:</p>
                            <ul>
                                <li>Mobile Money (MTN Mobile Money, Airtel Money)</li>
                                <li>Bank transfers</li>
                                <li>Cash on Delivery (where available)</li>
                                <li>Credit/Debit cards (Visa, Mastercard)</li>
                            </ul>
                        </div>
                    </div>
                    
                    <div class="faq-item">
                        <div class="faq-question" onclick="toggleFAQ(this)">
                            Is my payment information secure?
                            <i class="fas fa-chevron-down"></i>
                        </div>
                        <div class="faq-answer">
                            <p>Yes, your payment information is completely secure:</p>
                            <ul>
                                <li>We use SSL encryption for all transactions</li>
                                <li>Payment data is processed by certified payment providers</li>
                                <li>We never store your complete payment information</li>
                                <li>All transactions are monitored for fraud</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Contact Help -->
<section class="contact-help">
    <div class="container">
        <h2>Still need help?</h2>
        <p style="font-size: 1.1rem; margin-bottom: 3rem;">Our support team is here to assist you</p>
        
        <div class="row">
            <div class="col-4">
                <div class="contact-method">
                    <div class="contact-icon">
                        <i class="fas fa-envelope"></i>
                    </div>
                    <h4>Email Support</h4>
                    <p>support@markethub.com</p>
                    <p><small>Response within 24 hours</small></p>
                </div>
            </div>
            <div class="col-4">
                <div class="contact-method">
                    <div class="contact-icon">
                        <i class="fas fa-phone"></i>
                    </div>
                    <h4>Phone Support</h4>
                    <p>+250 788 123 456</p>
                    <p><small>Mon-Fri: 8AM-6PM</small></p>
                </div>
            </div>
            <div class="col-4">
                <div class="contact-method">
                    <div class="contact-icon">
                        <i class="fas fa-comments"></i>
                    </div>
                    <h4>Live Chat</h4>
                    <p>Available on website</p>
                    <p><small>Mon-Fri: 9AM-5PM</small></p>
                </div>
            </div>
        </div>
        
        <div style="margin-top: 2rem;">
            <a href="contact.php" class="btn btn-white">
                <i class="fas fa-paper-plane"></i> Contact Us
            </a>
        </div>
    </div>
</section>

<script>
function toggleFAQ(element) {
    const answer = element.nextElementSibling;
    const icon = element.querySelector('i');
    
    // Close all other FAQ items
    document.querySelectorAll('.faq-answer').forEach(item => {
        if (item !== answer) {
            item.classList.remove('active');
        }
    });
    
    document.querySelectorAll('.faq-question i').forEach(item => {
        if (item !== icon) {
            item.className = 'fas fa-chevron-down';
        }
    });
    
    // Toggle current item
    answer.classList.toggle('active');
    icon.className = answer.classList.contains('active') ? 'fas fa-chevron-up' : 'fas fa-chevron-down';
}

// Help search functionality
document.getElementById('helpSearch').addEventListener('input', function() {
    const query = this.value.toLowerCase();
    const faqItems = document.querySelectorAll('.faq-item');
    
    faqItems.forEach(item => {
        const question = item.querySelector('.faq-question').textContent.toLowerCase();
        const answer = item.querySelector('.faq-answer').textContent.toLowerCase();
        
        if (question.includes(query) || answer.includes(query)) {
            item.style.display = 'block';
        } else {
            item.style.display = query === '' ? 'block' : 'none';
        }
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>
