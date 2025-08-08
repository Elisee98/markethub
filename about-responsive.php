<?php
/**
 * About Page - Responsive Design
 */

require_once 'config/config.php';

$page_title = 'About Us';
$page_description = 'Learn about MarketHub - Your premier multi-vendor marketplace in Musanze District, connecting local vendors with customers.';

// Get platform statistics
$stats = [
    'vendors' => $database->fetch("SELECT COUNT(*) as count FROM users WHERE user_type = 'vendor' AND status = 'active'")['count'],
    'products' => $database->fetch("SELECT COUNT(*) as count FROM products WHERE status = 'active'")['count'],
    'orders' => $database->fetch("SELECT COUNT(*) as count FROM orders WHERE payment_status = 'paid'")['count'],
    'customers' => $database->fetch("SELECT COUNT(*) as count FROM users WHERE user_type = 'customer' AND status = 'active'")['count']
];

require_once 'includes/header.php';
?>

<style>
/* Responsive About Page Styles */
:root {
    --primary-color: #10b981;
    --secondary-color: #2be8acff;
    --accent-color: #5df3bcff;
    --text-dark: #1f2937;
    --text-light: #6b7280;
    --bg-light: #f9fafb;
    --white: #ffffff;
    --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    --shadow-lg: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
}

.about-hero {
    background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
    color: white;
    padding: 6rem 0;
    position: relative;
    overflow: hidden;
}

.about-hero::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grid" width="10" height="10" patternUnits="userSpaceOnUse"><path d="M 10 0 L 0 0 0 10" fill="none" stroke="rgba(255,255,255,0.1)" stroke-width="0.5"/></pattern></defs><rect width="100" height="100" fill="url(%23grid)"/></svg>');
    opacity: 0.3;
}

.about-hero .container {
    position: relative;
    z-index: 1;
}

.about-hero h1 {
    font-size: clamp(2.5rem, 5vw, 4rem);
    font-weight: 800;
    margin-bottom: 1.5rem;
    line-height: 1.1;
}

.about-hero p {
    font-size: 1.25rem;
    opacity: 0.9;
    max-width: 600px;
    margin: 0 auto;
}

.about-section {
    padding: 5rem 0;
}

.section-header {
    text-align: center;
    margin-bottom: 4rem;
}

.section-title {
    font-size: 2.5rem;
    font-weight: 700;
    color: var(--text-dark);
    margin-bottom: 1rem;
}

.section-subtitle {
    font-size: 1.1rem;
    color: var(--text-light);
    max-width: 600px;
    margin: 0 auto;
}

.feature-card {
    background: white;
    padding: 2.5rem;
    border-radius: 16px;
    box-shadow: var(--shadow);
    text-align: center;
    transition: all 0.3s ease;
    height: 100%;
    position: relative;
    overflow: hidden;
}

.feature-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, var(--primary-color), var(--accent-color));
    transform: scaleX(0);
    transition: transform 0.3s;
}

.feature-card:hover {
    transform: translateY(-10px);
    box-shadow: var(--shadow-lg);
}

.feature-card:hover::before {
    transform: scaleX(1);
}

.feature-icon {
    font-size: 3.5rem;
    color: var(--primary-color);
    margin-bottom: 1.5rem;
    display: block;
}

.feature-title {
    font-size: 1.25rem;
    font-weight: 600;
    color: var(--text-dark);
    margin-bottom: 1rem;
}

.feature-description {
    color: var(--text-light);
    line-height: 1.6;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 2rem;
    margin: 3rem 0;
}

.stat-card {
    background: white;
    padding: 2rem;
    border-radius: 16px;
    box-shadow: var(--shadow);
    text-align: center;
    transition: transform 0.3s ease;
}

.stat-card:hover {
    transform: translateY(-5px);
}

.stat-number {
    font-size: 3rem;
    font-weight: 800;
    color: var(--primary-color);
    display: block;
    margin-bottom: 0.5rem;
}

.stat-label {
    color: var(--text-light);
    font-size: 1rem;
    font-weight: 500;
}

.mission-content {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 4rem;
    align-items: center;
}

.mission-text {
    font-size: 1.1rem;
    line-height: 1.8;
    color: var(--text-light);
}

.mission-image {
    position: relative;
}

.mission-image img {
    width: 100%;
    height: auto;
    border-radius: 16px;
    box-shadow: var(--shadow-lg);
}

.values-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 2rem;
}

.value-card {
    background: white;
    padding: 2rem;
    border-radius: 16px;
    box-shadow: var(--shadow);
    transition: transform 0.3s ease;
}

.value-card:hover {
    transform: translateY(-5px);
}

.value-icon {
    width: 60px;
    height: 60px;
    background: linear-gradient(135deg, var(--primary-color), var(--accent-color));
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 1.5rem;
}

.value-icon i {
    font-size: 1.5rem;
    color: white;
}

.value-title {
    font-size: 1.25rem;
    font-weight: 600;
    color: var(--text-dark);
    margin-bottom: 1rem;
}

.value-description {
    color: var(--text-light);
    line-height: 1.6;
}

.cta-section {
    background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
    color: white;
    padding: 4rem 0;
    text-align: center;
    border-radius: 20px;
    margin: 2rem;
}

.cta-title {
    font-size: 2.5rem;
    font-weight: 700;
    margin-bottom: 1rem;
}

.cta-subtitle {
    font-size: 1.1rem;
    opacity: 0.9;
    margin-bottom: 2rem;
}

.btn {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 1rem 2rem;
    border-radius: 12px;
    text-decoration: none;
    font-weight: 600;
    transition: all 0.3s ease;
    border: 2px solid transparent;
    cursor: pointer;
    font-size: 1rem;
}

.btn-white {
    background: white;
    color: var(--primary-color);
    box-shadow: var(--shadow);
}

.btn-white:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow-lg);
}

.btn-outline-white {
    background: transparent;
    color: white;
    border-color: rgba(255, 255, 255, 0.3);
}

.btn-outline-white:hover {
    background: rgba(255, 255, 255, 0.1);
    border-color: white;
}

/* Responsive Design */
@media (max-width: 768px) {
    .about-hero {
        padding: 3rem 0;
    }
    
    .about-section {
        padding: 3rem 0;
    }
    
    .mission-content {
        grid-template-columns: 1fr;
        gap: 2rem;
    }
    
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 1rem;
    }
    
    .values-grid {
        grid-template-columns: 1fr;
    }
    
    .cta-section {
        margin: 1rem;
        padding: 2rem 1rem;
    }
}

@media (max-width: 480px) {
    .stats-grid {
        grid-template-columns: 1fr;
    }
    
    .feature-card {
        padding: 1.5rem;
    }
    
    .stat-number {
        font-size: 2rem;
    }
}
</style>

<!-- Hero Section -->
<section class="about-hero">
    <div class="container">
        <h1>About MarketHub</h1>
        <p>
            Connecting local vendors in Musanze District with customers through our innovative multi-vendor marketplace platform
        </p>
    </div>
</section>

<!-- Statistics Section -->
<section class="about-section" style="background: var(--bg-light);">
    <div class="container">
        <div class="section-header">
            <h2 class="section-title">Our Impact</h2>
            <p class="section-subtitle">
                See how MarketHub is transforming commerce in Musanze District
            </p>
        </div>
        
        <div class="stats-grid">
            <div class="stat-card">
                <span class="stat-number"><?php echo number_format($stats['vendors']); ?>+</span>
                <span class="stat-label">Active Vendors</span>
            </div>
            <div class="stat-card">
                <span class="stat-number"><?php echo number_format($stats['products']); ?>+</span>
                <span class="stat-label">Products Listed</span>
            </div>
            <div class="stat-card">
                <span class="stat-number"><?php echo number_format($stats['customers']); ?>+</span>
                <span class="stat-label">Happy Customers</span>
            </div>
            <div class="stat-card">
                <span class="stat-number"><?php echo number_format($stats['orders']); ?>+</span>
                <span class="stat-label">Orders Completed</span>
            </div>
        </div>
    </div>
</section>

<!-- Mission Section -->
<section class="about-section">
    <div class="container">
        <div class="mission-content">
            <div>
                <h2 style="font-size: 2.5rem; font-weight: 700; color: var(--text-dark); margin-bottom: 2rem;">Our Mission</h2>
                <div class="mission-text">
                    <p>
                        MarketHub is dedicated to empowering local businesses in Musanze District by providing them with a modern, 
                        digital platform to reach customers and grow their businesses. We believe in supporting our local economy 
                        while providing customers with convenient access to quality products and services.
                    </p>
                    <p>
                        Our platform bridges the gap between traditional commerce and digital innovation, creating opportunities 
                        for vendors of all sizes to thrive in the digital marketplace while maintaining the personal touch that 
                        makes local businesses special.
                    </p>
                </div>
            </div>
            <div class="mission-image">
                <img src="assets/images/mission-image.jpg" alt="Local vendors in Musanze District" 
                     onerror="this.style.display='none'">
            </div>
        </div>
    </div>
</section>

<!-- Features Section -->
<section class="about-section" style="background: var(--bg-light);">
    <div class="container">
        <div class="section-header">
            <h2 class="section-title">What We Offer</h2>
            <p class="section-subtitle">
                Comprehensive solutions designed for both vendors and customers
            </p>
        </div>
        
        <div class="row">
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="feature-card">
                    <i class="fas fa-store feature-icon"></i>
                    <h4 class="feature-title">Multi-Vendor Platform</h4>
                    <p class="feature-description">
                        A comprehensive platform where multiple vendors can showcase and sell their products to a wide customer base.
                    </p>
                </div>
            </div>
            
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="feature-card">
                    <i class="fas fa-shield-alt feature-icon"></i>
                    <h4 class="feature-title">Secure Payments</h4>
                    <p class="feature-description">
                        Safe and secure payment processing with multiple payment options including mobile money and bank transfers.
                    </p>
                </div>
            </div>
            
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="feature-card">
                    <i class="fas fa-balance-scale feature-icon"></i>
                    <h4 class="feature-title">Product Comparison</h4>
                    <p class="feature-description">
                        Advanced comparison tools to help customers make informed decisions by comparing products and vendors.
                    </p>
                </div>
            </div>
            
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="feature-card">
                    <i class="fas fa-chart-line feature-icon"></i>
                    <h4 class="feature-title">Analytics Dashboard</h4>
                    <p class="feature-description">
                        Comprehensive analytics and reporting tools for vendors to track sales, inventory, and customer insights.
                    </p>
                </div>
            </div>
            
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="feature-card">
                    <i class="fas fa-headset feature-icon"></i>
                    <h4 class="feature-title">Customer Support</h4>
                    <p class="feature-description">
                        Dedicated customer support team available to help both vendors and customers with any questions or issues.
                    </p>
                </div>
            </div>
            
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="feature-card">
                    <i class="fas fa-mobile-alt feature-icon"></i>
                    <h4 class="feature-title">Mobile Responsive</h4>
                    <p class="feature-description">
                        Fully responsive design that works perfectly on all devices - desktop, tablet, and mobile phones.
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Values Section -->
<section class="about-section">
    <div class="container">
        <div class="section-header">
            <h2 class="section-title">Our Values</h2>
            <p class="section-subtitle">
                The principles that guide everything we do
            </p>
        </div>
        
        <div class="values-grid">
            <div class="value-card">
                <div class="value-icon">
                    <i class="fas fa-heart"></i>
                </div>
                <h4 class="value-title">Community First</h4>
                <p class="value-description">
                    We prioritize the needs of our local community, supporting businesses and customers in Musanze District.
                </p>
            </div>
            
            <div class="value-card">
                <div class="value-icon">
                    <i class="fas fa-handshake"></i>
                </div>
                <h4 class="value-title">Trust & Transparency</h4>
                <p class="value-description">
                    We build trust through transparent practices, honest communication, and reliable service delivery.
                </p>
            </div>
            
            <div class="value-card">
                <div class="value-icon">
                    <i class="fas fa-rocket"></i>
                </div>
                <h4 class="value-title">Innovation</h4>
                <p class="value-description">
                    We continuously innovate to provide cutting-edge solutions that meet evolving market needs.
                </p>
            </div>
            
            <div class="value-card">
                <div class="value-icon">
                    <i class="fas fa-users"></i>
                </div>
                <h4 class="value-title">Inclusivity</h4>
                <p class="value-description">
                    We welcome vendors and customers of all sizes, creating opportunities for everyone to participate and succeed.
                </p>
            </div>
        </div>
    </div>
</section>

<!-- Call to Action Section -->
<section class="about-section">
    <div class="container">
        <div class="cta-section">
            <h2 class="cta-title">Ready to Join MarketHub?</h2>
            <p class="cta-subtitle">
                Whether you're a vendor looking to grow your business or a customer seeking quality products, we're here for you.
            </p>
            <div style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
                <a href="vendor/register.php" class="btn btn-white">
                    <i class="fas fa-store"></i> Become a Vendor
                </a>
                <a href="register.php" class="btn btn-outline-white">
                    <i class="fas fa-user-plus"></i> Join as Customer
                </a>
            </div>
        </div>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>
