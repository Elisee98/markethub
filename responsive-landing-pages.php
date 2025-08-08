<?php
/**
 * Responsive Landing Pages Documentation
 * Showcase of all responsive pages built for MarketHub
 */

require_once 'config/config.php';

$page_title = 'Responsive Landing Pages';
$page_description = 'Modern, mobile-first responsive landing pages for MarketHub marketplace.';

require_once 'includes/header.php';
?>

<style>
:root {
    --primary-color: #10b981;
    --secondary-color: #059669;
    --accent-color: #34d399;
    --text-dark: #1f2937;
    --text-light: #6b7280;
    --bg-light: #f9fafb;
    --white: #ffffff;
    --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    --shadow-lg: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
}

.hero-section {
    background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
    color: white;
    padding: 4rem 0;
    text-align: center;
    position: relative;
    overflow: hidden;
}

.hero-section::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grid" width="10" height="10" patternUnits="userSpaceOnUse"><path d="M 10 0 L 0 0 0 10" fill="none" stroke="rgba(255,255,255,0.1)" stroke-width="0.5"/></pattern></defs><rect width="100" height="100" fill="url(%23grid)"/></svg>');
    opacity: 0.3;
}

.hero-section .container {
    position: relative;
    z-index: 1;
}

.page-card {
    background: white;
    border-radius: 16px;
    overflow: hidden;
    box-shadow: var(--shadow-lg);
    transition: all 0.3s ease;
    height: 100%;
}

.page-card:hover {
    transform: translateY(-10px);
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
}

.page-preview {
    height: 200px;
    background: var(--bg-light);
    position: relative;
    overflow: hidden;
}

.page-preview img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.page-preview::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(45deg, var(--primary-color), var(--accent-color));
    opacity: 0.1;
}

.page-icon {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    font-size: 4rem;
    color: var(--primary-color);
    opacity: 0.3;
}

.page-content {
    padding: 2rem;
}

.page-title {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--text-dark);
    margin-bottom: 1rem;
}

.page-description {
    color: var(--text-light);
    line-height: 1.6;
    margin-bottom: 1.5rem;
}

.page-features {
    list-style: none;
    padding: 0;
    margin-bottom: 2rem;
}

.page-features li {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-bottom: 0.5rem;
    color: var(--text-light);
    font-size: 0.9rem;
}

.page-features li i {
    color: var(--primary-color);
    width: 16px;
}

.page-actions {
    display: flex;
    gap: 1rem;
}

.btn {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1.5rem;
    border-radius: 8px;
    text-decoration: none;
    font-weight: 600;
    transition: all 0.3s ease;
    border: 2px solid transparent;
    cursor: pointer;
    font-size: 0.9rem;
}

.btn-primary {
    background: var(--primary-color);
    color: white;
}

.btn-primary:hover {
    background: var(--secondary-color);
    transform: translateY(-2px);
}

.btn-outline {
    background: transparent;
    color: var(--primary-color);
    border-color: var(--primary-color);
}

.btn-outline:hover {
    background: var(--primary-color);
    color: white;
}

.features-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 2rem;
    margin: 3rem 0;
}

.feature-card {
    background: white;
    padding: 2rem;
    border-radius: 12px;
    box-shadow: var(--shadow);
    text-align: center;
    transition: transform 0.3s ease;
}

.feature-card:hover {
    transform: translateY(-5px);
}

.feature-icon {
    font-size: 3rem;
    color: var(--primary-color);
    margin-bottom: 1rem;
}

.section {
    padding: 4rem 0;
}

.section-title {
    font-size: 2.5rem;
    font-weight: 700;
    color: var(--text-dark);
    text-align: center;
    margin-bottom: 1rem;
}

.section-subtitle {
    font-size: 1.1rem;
    color: var(--text-light);
    text-align: center;
    margin-bottom: 3rem;
    max-width: 600px;
    margin-left: auto;
    margin-right: auto;
}

@media (max-width: 768px) {
    .hero-section {
        padding: 2rem 0;
    }
    
    .section {
        padding: 2rem 0;
    }
    
    .page-content {
        padding: 1.5rem;
    }
    
    .page-actions {
        flex-direction: column;
    }
}
</style>

<!-- Hero Section -->
<section class="hero-section">
    <div class="container">
        <h1 style="font-size: clamp(2.5rem, 5vw, 4rem); font-weight: 800; margin-bottom: 1.5rem;">
            📱 Responsive Landing Pages
        </h1>
        <p style="font-size: 1.25rem; opacity: 0.9; max-width: 600px; margin: 0 auto;">
            Modern, mobile-first responsive pages designed for optimal user experience across all devices
        </p>
    </div>
</section>

<!-- Pages Showcase -->
<section class="section">
    <div class="container">
        <h2 class="section-title">Landing Pages Built</h2>
        <p class="section-subtitle">
            Each page is fully responsive, optimized for performance, and designed with modern UI/UX principles
        </p>
        
        <div class="row">
            <!-- Homepage -->
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="page-card">
                    <div class="page-preview">
                        <div class="page-icon">
                            <i class="fas fa-home"></i>
                        </div>
                    </div>
                    <div class="page-content">
                        <h3 class="page-title">Homepage</h3>
                        <p class="page-description">
                            Modern landing page with hero section, search functionality, and category showcase.
                        </p>
                        <ul class="page-features">
                            <li><i class="fas fa-check"></i> Hero section with statistics</li>
                            <li><i class="fas fa-check"></i> Advanced search with suggestions</li>
                            <li><i class="fas fa-check"></i> Category grid with product counts</li>
                            <li><i class="fas fa-check"></i> Floating animations</li>
                            <li><i class="fas fa-check"></i> Mobile-first responsive design</li>
                        </ul>
                        <div class="page-actions">
                            <a href="index-responsive.php" class="btn btn-primary">
                                <i class="fas fa-eye"></i> View Page
                            </a>
                            <a href="#" onclick="showCode('homepage')" class="btn btn-outline">
                                <i class="fas fa-code"></i> View Code
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- About Page -->
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="page-card">
                    <div class="page-preview">
                        <div class="page-icon">
                            <i class="fas fa-info-circle"></i>
                        </div>
                    </div>
                    <div class="page-content">
                        <h3 class="page-title">About Us</h3>
                        <p class="page-description">
                            Comprehensive about page with mission, features, values, and call-to-action sections.
                        </p>
                        <ul class="page-features">
                            <li><i class="fas fa-check"></i> Platform statistics display</li>
                            <li><i class="fas fa-check"></i> Mission and values sections</li>
                            <li><i class="fas fa-check"></i> Feature cards with hover effects</li>
                            <li><i class="fas fa-check"></i> Call-to-action section</li>
                            <li><i class="fas fa-check"></i> Responsive grid layouts</li>
                        </ul>
                        <div class="page-actions">
                            <a href="about-responsive.php" class="btn btn-primary">
                                <i class="fas fa-eye"></i> View Page
                            </a>
                            <a href="#" onclick="showCode('about')" class="btn btn-outline">
                                <i class="fas fa-code"></i> View Code
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Contact Page -->
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="page-card">
                    <div class="page-preview">
                        <div class="page-icon">
                            <i class="fas fa-envelope"></i>
                        </div>
                    </div>
                    <div class="page-content">
                        <h3 class="page-title">Contact Us</h3>
                        <p class="page-description">
                            Interactive contact page with form, contact information, and FAQ section.
                        </p>
                        <ul class="page-features">
                            <li><i class="fas fa-check"></i> Contact form with validation</li>
                            <li><i class="fas fa-check"></i> Contact information cards</li>
                            <li><i class="fas fa-check"></i> Interactive FAQ section</li>
                            <li><i class="fas fa-check"></i> Form submission handling</li>
                            <li><i class="fas fa-check"></i> Mobile-optimized layout</li>
                        </ul>
                        <div class="page-actions">
                            <a href="contact-responsive.php" class="btn btn-primary">
                                <i class="fas fa-eye"></i> View Page
                            </a>
                            <a href="#" onclick="showCode('contact')" class="btn btn-outline">
                                <i class="fas fa-code"></i> View Code
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Features Section -->
<section class="section" style="background: var(--bg-light);">
    <div class="container">
        <h2 class="section-title">Responsive Design Features</h2>
        <p class="section-subtitle">
            Built with modern web standards and best practices for optimal performance
        </p>
        
        <div class="features-grid">
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-mobile-alt"></i>
                </div>
                <h4 style="color: var(--text-dark); margin-bottom: 1rem;">Mobile-First Design</h4>
                <p style="color: var(--text-light);">
                    Designed for mobile devices first, then enhanced for larger screens using progressive enhancement.
                </p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-tachometer-alt"></i>
                </div>
                <h4 style="color: var(--text-dark); margin-bottom: 1rem;">Performance Optimized</h4>
                <p style="color: var(--text-light);">
                    Optimized CSS, minimal JavaScript, and efficient loading for fast page speeds across all devices.
                </p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-universal-access"></i>
                </div>
                <h4 style="color: var(--text-dark); margin-bottom: 1rem;">Accessibility Ready</h4>
                <p style="color: var(--text-light);">
                    Built with accessibility in mind, including proper semantic HTML and keyboard navigation support.
                </p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-palette"></i>
                </div>
                <h4 style="color: var(--text-dark); margin-bottom: 1rem;">Modern UI/UX</h4>
                <p style="color: var(--text-light);">
                    Clean, modern design with smooth animations, hover effects, and intuitive user interactions.
                </p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-code"></i>
                </div>
                <h4 style="color: var(--text-dark); margin-bottom: 1rem;">Clean Code</h4>
                <p style="color: var(--text-light);">
                    Well-structured, maintainable code with CSS custom properties and modular design patterns.
                </p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-search"></i>
                </div>
                <h4 style="color: var(--text-dark); margin-bottom: 1rem;">SEO Optimized</h4>
                <p style="color: var(--text-light);">
                    Proper meta tags, semantic HTML structure, and optimized content for better search engine visibility.
                </p>
            </div>
        </div>
    </div>
</section>

<!-- Technical Details -->
<section class="section">
    <div class="container">
        <h2 class="section-title">Technical Implementation</h2>
        <p class="section-subtitle">
            Modern web technologies and best practices used in development
        </p>
        
        <div class="row">
            <div class="col-lg-6 mb-4">
                <div style="background: white; padding: 2rem; border-radius: 12px; box-shadow: var(--shadow); height: 100%;">
                    <h4 style="color: var(--primary-color); margin-bottom: 1.5rem;">
                        <i class="fas fa-code"></i> Frontend Technologies
                    </h4>
                    <ul style="list-style: none; padding: 0;">
                        <li style="margin-bottom: 0.5rem;"><i class="fab fa-html5" style="color: #e34f26; margin-right: 0.5rem;"></i> HTML5 Semantic Markup</li>
                        <li style="margin-bottom: 0.5rem;"><i class="fab fa-css3-alt" style="color: #1572b6; margin-right: 0.5rem;"></i> CSS3 with Custom Properties</li>
                        <li style="margin-bottom: 0.5rem;"><i class="fab fa-js-square" style="color: #f7df1e; margin-right: 0.5rem;"></i> Vanilla JavaScript</li>
                        <li style="margin-bottom: 0.5rem;"><i class="fas fa-mobile-alt" style="color: var(--primary-color); margin-right: 0.5rem;"></i> CSS Grid & Flexbox</li>
                        <li style="margin-bottom: 0.5rem;"><i class="fas fa-paint-brush" style="color: var(--primary-color); margin-right: 0.5rem;"></i> CSS Animations & Transitions</li>
                        <li style="margin-bottom: 0.5rem;"><i class="fas fa-font" style="color: var(--primary-color); margin-right: 0.5rem;"></i> Font Awesome Icons</li>
                    </ul>
                </div>
            </div>
            
            <div class="col-lg-6 mb-4">
                <div style="background: white; padding: 2rem; border-radius: 12px; box-shadow: var(--shadow); height: 100%;">
                    <h4 style="color: var(--primary-color); margin-bottom: 1.5rem;">
                        <i class="fas fa-server"></i> Backend Integration
                    </h4>
                    <ul style="list-style: none; padding: 0;">
                        <li style="margin-bottom: 0.5rem;"><i class="fab fa-php" style="color: #777bb4; margin-right: 0.5rem;"></i> PHP 8+ Backend</li>
                        <li style="margin-bottom: 0.5rem;"><i class="fas fa-database" style="color: var(--primary-color); margin-right: 0.5rem;"></i> MySQL Database Integration</li>
                        <li style="margin-bottom: 0.5rem;"><i class="fas fa-shield-alt" style="color: var(--primary-color); margin-right: 0.5rem;"></i> Input Validation & Sanitization</li>
                        <li style="margin-bottom: 0.5rem;"><i class="fas fa-envelope" style="color: var(--primary-color); margin-right: 0.5rem;"></i> Email Integration</li>
                        <li style="margin-bottom: 0.5rem;"><i class="fas fa-chart-bar" style="color: var(--primary-color); margin-right: 0.5rem;"></i> Dynamic Statistics</li>
                        <li style="margin-bottom: 0.5rem;"><i class="fas fa-cog" style="color: var(--primary-color); margin-right: 0.5rem;"></i> Configuration Management</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Call to Action -->
<section class="section" style="background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%); color: white;">
    <div class="container text-center">
        <h2 style="font-size: 2.5rem; font-weight: 700; margin-bottom: 1rem;">Ready to Experience MarketHub?</h2>
        <p style="font-size: 1.1rem; opacity: 0.9; margin-bottom: 2rem; max-width: 600px; margin-left: auto; margin-right: auto;">
            Explore our responsive marketplace platform and see how we're revolutionizing e-commerce in Musanze District.
        </p>
        <div style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
            <a href="index-responsive.php" style="background: white; color: var(--primary-color); padding: 1rem 2rem; border-radius: 8px; text-decoration: none; font-weight: 600; display: inline-flex; align-items: center; gap: 0.5rem;">
                <i class="fas fa-home"></i> Visit Homepage
            </a>
            <a href="products.php" style="background: transparent; color: white; border: 2px solid rgba(255,255,255,0.3); padding: 1rem 2rem; border-radius: 8px; text-decoration: none; font-weight: 600; display: inline-flex; align-items: center; gap: 0.5rem;">
                <i class="fas fa-shopping-bag"></i> Browse Products
            </a>
        </div>
    </div>
</section>

<script>
function showCode(pageType) {
    const codeUrls = {
        'homepage': 'index-responsive.php',
        'about': 'about-responsive.php',
        'contact': 'contact-responsive.php'
    };
    
    if (codeUrls[pageType]) {
        window.open(`view-source:${window.location.origin}/ange Final/${codeUrls[pageType]}`, '_blank');
    }
}

// Add smooth scrolling for anchor links
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        e.preventDefault();
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
            target.scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });
        }
    });
});

// Add loading animation for page cards
document.addEventListener('DOMContentLoaded', function() {
    const cards = document.querySelectorAll('.page-card');
    cards.forEach((card, index) => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(20px)';
        
        setTimeout(() => {
            card.style.transition = 'all 0.6s ease';
            card.style.opacity = '1';
            card.style.transform = 'translateY(0)';
        }, index * 200);
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>
