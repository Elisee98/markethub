<?php
/**
 * Frequently Asked Questions - MarketHub
 */

require_once 'config/config.php';

$page_title = 'FAQ - MarketHub';
$page_description = 'Find answers to frequently asked questions about MarketHub services.';

require_once 'includes/header.php';
?>

<style>
.faq-hero {
    background: linear-gradient(135deg, #10b981, #059669);
    color: white;
    padding: 4rem 0;
    text-align: center;
}

.faq-hero h1 {
    font-size: 2.5rem;
    font-weight: 800;
    margin-bottom: 1rem;
}

.search-faq {
    max-width: 600px;
    margin: 2rem auto 0;
    position: relative;
}

.search-faq input {
    width: 100%;
    padding: 1rem 1rem 1rem 3rem;
    border: none;
    border-radius: 50px;
    font-size: 1.1rem;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
}

.search-faq i {
    position: absolute;
    left: 1rem;
    top: 50%;
    transform: translateY(-50%);
    color: #6b7280;
}

.faq-categories {
    padding: 4rem 0;
    background: #f8f9fa;
}

.category-nav {
    display: flex;
    justify-content: center;
    gap: 1rem;
    margin-bottom: 3rem;
    flex-wrap: wrap;
}

.category-btn {
    padding: 1rem 2rem;
    background: white;
    border: 2px solid #e5e7eb;
    border-radius: 50px;
    color: #6b7280;
    text-decoration: none;
    font-weight: 500;
    transition: all 0.3s;
}

.category-btn.active,
.category-btn:hover {
    background: #10b981;
    border-color: #10b981;
    color: white;
}

.faq-section {
    padding: 4rem 0;
}

.faq-category-content {
    display: none;
}

.faq-category-content.active {
    display: block;
}

.faq-item {
    background: white;
    border-radius: 12px;
    margin-bottom: 1rem;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    overflow: hidden;
    border: 1px solid #e5e7eb;
}

.faq-question {
    padding: 1.5rem;
    cursor: pointer;
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-weight: 600;
    color: #1f2937;
    background: #f8f9fa;
    transition: all 0.3s;
}

.faq-question:hover {
    background: #e5e7eb;
}

.faq-answer {
    padding: 0 1.5rem;
    max-height: 0;
    overflow: hidden;
    transition: all 0.3s ease;
}

.faq-answer.active {
    padding: 1.5rem;
    max-height: 500px;
}

.faq-icon {
    transition: transform 0.3s;
}

.faq-icon.active {
    transform: rotate(180deg);
}

.contact-cta {
    background: linear-gradient(135deg, #10b981, #059669);
    color: white;
    padding: 4rem 0;
    text-align: center;
}

.stats-section {
    padding: 3rem 0;
    background: #f8f9fa;
}

.stat-item {
    text-align: center;
    padding: 2rem;
}

.stat-number {
    font-size: 2.5rem;
    font-weight: 800;
    color: #10b981;
    margin-bottom: 0.5rem;
}

.stat-label {
    color: #6b7280;
    font-weight: 500;
}
</style>

<!-- Hero Section -->
<section class="faq-hero">
    <div class="container">
        <h1>❓ Frequently Asked Questions</h1>
        <p>Find quick answers to common questions about MarketHub</p>
        
        <div class="search-faq">
            <i class="fas fa-search"></i>
            <input type="text" placeholder="Search FAQ..." id="faqSearch">
        </div>
    </div>
</section>

<!-- Quick Stats -->
<section class="stats-section">
    <div class="container">
        <div class="row">
            <div class="col-3">
                <div class="stat-item">
                    <div class="stat-number">500+</div>
                    <div class="stat-label">Questions Answered</div>
                </div>
            </div>
            <div class="col-3">
                <div class="stat-item">
                    <div class="stat-number">24/7</div>
                    <div class="stat-label">Support Available</div>
                </div>
            </div>
            <div class="col-3">
                <div class="stat-item">
                    <div class="stat-number">95%</div>
                    <div class="stat-label">Issues Resolved</div>
                </div>
            </div>
            <div class="col-3">
                <div class="stat-item">
                    <div class="stat-number">&lt;2h</div>
                    <div class="stat-label">Average Response</div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- FAQ Categories -->
<section class="faq-categories">
    <div class="container">
        <div class="category-nav">
            <a href="#" class="category-btn active" onclick="showCategory('general')">
                <i class="fas fa-home"></i> General
            </a>
            <a href="#" class="category-btn" onclick="showCategory('orders')">
                <i class="fas fa-shopping-cart"></i> Orders
            </a>
            <a href="#" class="category-btn" onclick="showCategory('payments')">
                <i class="fas fa-credit-card"></i> Payments
            </a>
            <a href="#" class="category-btn" onclick="showCategory('shipping')">
                <i class="fas fa-truck"></i> Shipping
            </a>
            <a href="#" class="category-btn" onclick="showCategory('vendors')">
                <i class="fas fa-store"></i> Vendors
            </a>
            <a href="#" class="category-btn" onclick="showCategory('technical')">
                <i class="fas fa-cog"></i> Technical
            </a>
        </div>
    </div>
</section>

<!-- FAQ Content -->
<section class="faq-section">
    <div class="container">
        <div class="row">
            <div class="col-10 mx-auto">
                
                <!-- General FAQ -->
                <div id="general" class="faq-category-content active">
                    <h3 style="color: #10b981; margin-bottom: 2rem; text-align: center;">🏠 General Questions</h3>
                    
                    <div class="faq-item">
                        <div class="faq-question" onclick="toggleFAQ(this)">
                            What is MarketHub?
                            <i class="fas fa-chevron-down faq-icon"></i>
                        </div>
                        <div class="faq-answer">
                            <p>MarketHub is a multi-vendor e-commerce platform that connects buyers and sellers in Musanze District and across Rwanda. We provide a secure, user-friendly marketplace where local businesses can sell their products and customers can find everything they need in one place.</p>
                        </div>
                    </div>
                    
                    <div class="faq-item">
                        <div class="faq-question" onclick="toggleFAQ(this)">
                            How do I create an account?
                            <i class="fas fa-chevron-down faq-icon"></i>
                        </div>
                        <div class="faq-answer">
                            <p>Creating an account is simple:</p>
                            <ol>
                                <li>Click "Register" in the top navigation</li>
                                <li>Fill in your personal information</li>
                                <li>Choose a secure password</li>
                                <li>Verify your email address</li>
                                <li>Start shopping or selling!</li>
                            </ol>
                        </div>
                    </div>
                    
                    <div class="faq-item">
                        <div class="faq-question" onclick="toggleFAQ(this)">
                            Is MarketHub free to use?
                            <i class="fas fa-chevron-down faq-icon"></i>
                        </div>
                        <div class="faq-answer">
                            <p>Yes! Creating an account and shopping on MarketHub is completely free for customers. Vendors pay a small commission only when they make sales. There are no monthly fees or hidden charges.</p>
                        </div>
                    </div>
                    
                    <div class="faq-item">
                        <div class="faq-question" onclick="toggleFAQ(this)">
                            What areas do you serve?
                            <i class="fas fa-chevron-down faq-icon"></i>
                        </div>
                        <div class="faq-answer">
                            <p>We primarily serve Musanze District with same-day delivery, but we also deliver throughout Northern Province and all of Rwanda. Delivery times and costs vary by location.</p>
                        </div>
                    </div>
                </div>
                
                <!-- Orders FAQ -->
                <div id="orders" class="faq-category-content">
                    <h3 style="color: #10b981; margin-bottom: 2rem; text-align: center;">🛒 Order Questions</h3>
                    
                    <div class="faq-item">
                        <div class="faq-question" onclick="toggleFAQ(this)">
                            How do I place an order?
                            <i class="fas fa-chevron-down faq-icon"></i>
                        </div>
                        <div class="faq-answer">
                            <p>Placing an order is easy:</p>
                            <ol>
                                <li>Browse products and add items to your cart</li>
                                <li>Review your cart and proceed to checkout</li>
                                <li>Enter your shipping and payment information</li>
                                <li>Review and confirm your order</li>
                                <li>Receive order confirmation via email</li>
                            </ol>
                        </div>
                    </div>
                    
                    <div class="faq-item">
                        <div class="faq-question" onclick="toggleFAQ(this)">
                            Can I modify my order after placing it?
                            <i class="fas fa-chevron-down faq-icon"></i>
                        </div>
                        <div class="faq-answer">
                            <p>Order modifications depend on the status:</p>
                            <ul>
                                <li><strong>Pending:</strong> You can usually modify or cancel</li>
                                <li><strong>Processing:</strong> Contact the vendor immediately</li>
                                <li><strong>Shipped:</strong> No modifications possible</li>
                            </ul>
                            <p>Contact customer support for assistance with order changes.</p>
                        </div>
                    </div>
                    
                    <div class="faq-item">
                        <div class="faq-question" onclick="toggleFAQ(this)">
                            How can I track my order?
                            <i class="fas fa-chevron-down faq-icon"></i>
                        </div>
                        <div class="faq-answer">
                            <p>Track your order easily:</p>
                            <ul>
                                <li>Log into your account and visit "My Orders"</li>
                                <li>Use the tracking number from your confirmation email</li>
                                <li>Contact the vendor directly for updates</li>
                                <li>Call our support team for assistance</li>
                            </ul>
                        </div>
                    </div>
                </div>
                
                <!-- Payments FAQ -->
                <div id="payments" class="faq-category-content">
                    <h3 style="color: #10b981; margin-bottom: 2rem; text-align: center;">💳 Payment Questions</h3>
                    
                    <div class="faq-item">
                        <div class="faq-question" onclick="toggleFAQ(this)">
                            What payment methods do you accept?
                            <i class="fas fa-chevron-down faq-icon"></i>
                        </div>
                        <div class="faq-answer">
                            <p>We accept various payment methods:</p>
                            <ul>
                                <li><strong>Mobile Money:</strong> MTN Mobile Money, Airtel Money</li>
                                <li><strong>Bank Transfer:</strong> All major Rwandan banks</li>
                                <li><strong>Cash on Delivery:</strong> Available in Musanze District</li>
                                <li><strong>Credit/Debit Cards:</strong> Visa, Mastercard (coming soon)</li>
                            </ul>
                        </div>
                    </div>
                    
                    <div class="faq-item">
                        <div class="faq-question" onclick="toggleFAQ(this)">
                            Is my payment information secure?
                            <i class="fas fa-chevron-down faq-icon"></i>
                        </div>
                        <div class="faq-answer">
                            <p>Absolutely! We use industry-standard security measures:</p>
                            <ul>
                                <li>SSL encryption for all transactions</li>
                                <li>Secure payment processing partners</li>
                                <li>No storage of complete payment information</li>
                                <li>Regular security audits and monitoring</li>
                            </ul>
                        </div>
                    </div>
                    
                    <div class="faq-item">
                        <div class="faq-question" onclick="toggleFAQ(this)">
                            When will I be charged?
                            <i class="fas fa-chevron-down faq-icon"></i>
                        </div>
                        <div class="faq-answer">
                            <p>Payment timing depends on the method:</p>
                            <ul>
                                <li><strong>Mobile Money/Bank Transfer:</strong> Immediately upon order confirmation</li>
                                <li><strong>Cash on Delivery:</strong> When you receive your order</li>
                                <li><strong>Credit/Debit Cards:</strong> Immediately upon order confirmation</li>
                            </ul>
                        </div>
                    </div>
                </div>
                
                <!-- Shipping FAQ -->
                <div id="shipping" class="faq-category-content">
                    <h3 style="color: #10b981; margin-bottom: 2rem; text-align: center;">🚚 Shipping Questions</h3>
                    
                    <div class="faq-item">
                        <div class="faq-question" onclick="toggleFAQ(this)">
                            How much does shipping cost?
                            <i class="fas fa-chevron-down faq-icon"></i>
                        </div>
                        <div class="faq-answer">
                            <p>Shipping costs vary by location:</p>
                            <ul>
                                <li><strong>Musanze District:</strong> RWF 1,000 (Free over RWF 20,000)</li>
                                <li><strong>Northern Province:</strong> RWF 2,000 (Free over RWF 30,000)</li>
                                <li><strong>Kigali City:</strong> RWF 2,500 (Free over RWF 35,000)</li>
                                <li><strong>Other Provinces:</strong> RWF 3,000 (Free over RWF 50,000)</li>
                            </ul>
                        </div>
                    </div>
                    
                    <div class="faq-item">
                        <div class="faq-question" onclick="toggleFAQ(this)">
                            How long does delivery take?
                            <i class="fas fa-chevron-down faq-icon"></i>
                        </div>
                        <div class="faq-answer">
                            <p>Delivery times by location:</p>
                            <ul>
                                <li><strong>Musanze District:</strong> Same day (orders before 2 PM)</li>
                                <li><strong>Northern Province:</strong> 1-2 business days</li>
                                <li><strong>Kigali City:</strong> 1-2 business days</li>
                                <li><strong>Other Provinces:</strong> 2-5 business days</li>
                            </ul>
                        </div>
                    </div>
                </div>
                
                <!-- Vendors FAQ -->
                <div id="vendors" class="faq-category-content">
                    <h3 style="color: #10b981; margin-bottom: 2rem; text-align: center;">🏪 Vendor Questions</h3>
                    
                    <div class="faq-item">
                        <div class="faq-question" onclick="toggleFAQ(this)">
                            How do I become a vendor?
                            <i class="fas fa-chevron-down faq-icon"></i>
                        </div>
                        <div class="faq-answer">
                            <p>Becoming a vendor is straightforward:</p>
                            <ol>
                                <li>Click "Become a Vendor" in the footer</li>
                                <li>Fill out the vendor application form</li>
                                <li>Provide required business documents</li>
                                <li>Wait for approval (usually 2-3 business days)</li>
                                <li>Set up your store and start selling!</li>
                            </ol>
                        </div>
                    </div>
                    
                    <div class="faq-item">
                        <div class="faq-question" onclick="toggleFAQ(this)">
                            What are the vendor fees?
                            <i class="fas fa-chevron-down faq-icon"></i>
                        </div>
                        <div class="faq-answer">
                            <p>Our vendor fees are simple and transparent:</p>
                            <ul>
                                <li><strong>Setup Fee:</strong> Free</li>
                                <li><strong>Monthly Fee:</strong> Free</li>
                                <li><strong>Commission:</strong> 5% per successful sale</li>
                                <li><strong>Payment Processing:</strong> Included in commission</li>
                            </ul>
                            <p>You only pay when you make sales!</p>
                        </div>
                    </div>
                </div>
                
                <!-- Technical FAQ -->
                <div id="technical" class="faq-category-content">
                    <h3 style="color: #10b981; margin-bottom: 2rem; text-align: center;">⚙️ Technical Questions</h3>
                    
                    <div class="faq-item">
                        <div class="faq-question" onclick="toggleFAQ(this)">
                            What browsers are supported?
                            <i class="fas fa-chevron-down faq-icon"></i>
                        </div>
                        <div class="faq-answer">
                            <p>MarketHub works on all modern browsers:</p>
                            <ul>
                                <li>Google Chrome (recommended)</li>
                                <li>Mozilla Firefox</li>
                                <li>Safari</li>
                                <li>Microsoft Edge</li>
                                <li>Mobile browsers on iOS and Android</li>
                            </ul>
                        </div>
                    </div>
                    
                    <div class="faq-item">
                        <div class="faq-question" onclick="toggleFAQ(this)">
                            Is there a mobile app?
                            <i class="fas fa-chevron-down faq-icon"></i>
                        </div>
                        <div class="faq-answer">
                            <p>Currently, MarketHub is a web-based platform optimized for mobile browsers. A dedicated mobile app is in development and will be available soon for both iOS and Android devices.</p>
                        </div>
                    </div>
                    
                    <div class="faq-item">
                        <div class="faq-question" onclick="toggleFAQ(this)">
                            I'm having trouble with the website. What should I do?
                            <i class="fas fa-chevron-down faq-icon"></i>
                        </div>
                        <div class="faq-answer">
                            <p>If you're experiencing technical issues:</p>
                            <ol>
                                <li>Try refreshing the page</li>
                                <li>Clear your browser cache and cookies</li>
                                <li>Try a different browser</li>
                                <li>Check your internet connection</li>
                                <li>Contact our technical support team</li>
                            </ol>
                        </div>
                    </div>
                </div>
                
            </div>
        </div>
    </div>
</section>

<!-- Contact CTA -->
<section class="contact-cta">
    <div class="container">
        <h2>Still have questions?</h2>
        <p style="font-size: 1.1rem; margin-bottom: 2rem;">Our support team is here to help you 24/7</p>
        
        <div style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
            <a href="contact.php" class="btn btn-white">
                <i class="fas fa-envelope"></i> Contact Support
            </a>
            <a href="help.php" class="btn btn-white">
                <i class="fas fa-life-ring"></i> Help Center
            </a>
            <a href="tel:+250788123456" class="btn btn-white">
                <i class="fas fa-phone"></i> Call Us
            </a>
        </div>
    </div>
</section>

<script>
function showCategory(categoryId) {
    // Hide all categories
    document.querySelectorAll('.faq-category-content').forEach(content => {
        content.classList.remove('active');
    });
    
    // Remove active class from all buttons
    document.querySelectorAll('.category-btn').forEach(btn => {
        btn.classList.remove('active');
    });
    
    // Show selected category
    document.getElementById(categoryId).classList.add('active');
    
    // Add active class to clicked button
    event.target.classList.add('active');
}

function toggleFAQ(element) {
    const answer = element.nextElementSibling;
    const icon = element.querySelector('.faq-icon');
    
    // Close all other FAQ items in the same category
    const category = element.closest('.faq-category-content');
    category.querySelectorAll('.faq-answer').forEach(item => {
        if (item !== answer) {
            item.classList.remove('active');
        }
    });
    
    category.querySelectorAll('.faq-icon').forEach(item => {
        if (item !== icon) {
            item.classList.remove('active');
        }
    });
    
    // Toggle current item
    answer.classList.toggle('active');
    icon.classList.toggle('active');
}

// FAQ search functionality
document.getElementById('faqSearch').addEventListener('input', function() {
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
    
    // Show all categories when searching
    if (query !== '') {
        document.querySelectorAll('.faq-category-content').forEach(content => {
            content.classList.add('active');
        });
    } else {
        // Reset to first category
        document.querySelectorAll('.faq-category-content').forEach(content => {
            content.classList.remove('active');
        });
        document.getElementById('general').classList.add('active');
    }
});
</script>

<?php require_once 'includes/footer.php'; ?>
