<?php
/**
 * Privacy Policy - MarketHub
 */

require_once 'config/config.php';

$page_title = 'Privacy Policy - MarketHub';
$page_description = 'Learn how MarketHub protects your privacy and handles your personal information.';

require_once 'includes/header.php';
?>

<style>
.privacy-hero {
    background: linear-gradient(135deg, #10b981, #059669);
    color: white;
    padding: 4rem 0;
    text-align: center;
}

.privacy-hero h1 {
    font-size: 2.5rem;
    font-weight: 800;
    margin-bottom: 1rem;
}

.privacy-content {
    padding: 4rem 0;
}

.privacy-section {
    background: white;
    border-radius: 12px;
    padding: 2rem;
    margin-bottom: 2rem;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    border-left: 4px solid #10b981;
}

.privacy-section h3 {
    color: #10b981;
    margin-bottom: 1.5rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.privacy-section h4 {
    color: #1f2937;
    margin: 1.5rem 0 1rem 0;
}

.privacy-section p {
    color: #4b5563;
    line-height: 1.6;
    margin-bottom: 1rem;
}

.privacy-section ul {
    color: #4b5563;
    margin-bottom: 1rem;
}

.privacy-section li {
    margin-bottom: 0.5rem;
}

.toc {
    background: #f8f9fa;
    border-radius: 12px;
    padding: 2rem;
    margin-bottom: 2rem;
    position: sticky;
    top: 2rem;
}

.toc h4 {
    color: #10b981;
    margin-bottom: 1rem;
}

.toc ul {
    list-style: none;
    padding: 0;
}

.toc li {
    margin-bottom: 0.5rem;
}

.toc a {
    color: #6b7280;
    text-decoration: none;
    padding: 0.5rem;
    display: block;
    border-radius: 6px;
    transition: all 0.3s;
}

.toc a:hover {
    background: #10b981;
    color: white;
}

.last-updated {
    background: #e0f2fe;
    border: 1px solid #0288d1;
    border-radius: 8px;
    padding: 1rem;
    margin-bottom: 2rem;
    text-align: center;
    color: #01579b;
}

.contact-privacy {
    background: linear-gradient(135deg, #10b981, #059669);
    color: white;
    padding: 3rem 0;
    text-align: center;
    border-radius: 12px;
    margin-top: 2rem;
}
</style>

<!-- Hero Section -->
<section class="privacy-hero">
    <div class="container">
        <h1>🔒 Privacy Policy</h1>
        <p>Your privacy is important to us. Learn how we protect your information.</p>
    </div>
</section>

<!-- Privacy Content -->
<section class="privacy-content">
    <div class="container">
        <div class="row">
            <div class="col-3">
                <!-- Table of Contents -->
                <div class="toc">
                    <h4>Table of Contents</h4>
                    <ul>
                        <li><a href="#overview">Overview</a></li>
                        <li><a href="#information-we-collect">Information We Collect</a></li>
                        <li><a href="#how-we-use">How We Use Information</a></li>
                        <li><a href="#information-sharing">Information Sharing</a></li>
                        <li><a href="#data-security">Data Security</a></li>
                        <li><a href="#cookies">Cookies & Tracking</a></li>
                        <li><a href="#your-rights">Your Rights</a></li>
                        <li><a href="#data-retention">Data Retention</a></li>
                        <li><a href="#children">Children's Privacy</a></li>
                        <li><a href="#changes">Policy Changes</a></li>
                        <li><a href="#contact">Contact Us</a></li>
                    </ul>
                </div>
            </div>
            
            <div class="col-9">
                <div class="last-updated">
                    <strong>Last Updated:</strong> <?php echo date('F j, Y'); ?>
                </div>
                
                <!-- Overview -->
                <div id="overview" class="privacy-section">
                    <h3><i class="fas fa-info-circle"></i> Overview</h3>
                    <p>
                        MarketHub ("we," "our," or "us") is committed to protecting your privacy. This Privacy Policy explains how we collect, use, disclose, and safeguard your information when you visit our website and use our services.
                    </p>
                    <p>
                        By using MarketHub, you consent to the data practices described in this policy. If you do not agree with the practices described in this policy, please do not use our services.
                    </p>
                    <p>
                        This policy applies to all users of MarketHub, including customers, vendors, and visitors to our website.
                    </p>
                </div>
                
                <!-- Information We Collect -->
                <div id="information-we-collect" class="privacy-section">
                    <h3><i class="fas fa-database"></i> Information We Collect</h3>
                    
                    <h4>Personal Information</h4>
                    <p>We may collect personal information that you provide directly to us, including:</p>
                    <ul>
                        <li><strong>Account Information:</strong> Name, email address, phone number, password</li>
                        <li><strong>Profile Information:</strong> Profile picture, preferences, communication settings</li>
                        <li><strong>Contact Information:</strong> Billing and shipping addresses</li>
                        <li><strong>Payment Information:</strong> Payment method details (processed securely by third parties)</li>
                        <li><strong>Communication:</strong> Messages, reviews, support requests</li>
                    </ul>
                    
                    <h4>Business Information (For Vendors)</h4>
                    <ul>
                        <li>Business name and registration details</li>
                        <li>Tax identification numbers</li>
                        <li>Bank account information for payments</li>
                        <li>Business licenses and certifications</li>
                    </ul>
                    
                    <h4>Automatically Collected Information</h4>
                    <ul>
                        <li><strong>Usage Data:</strong> Pages visited, time spent, click patterns</li>
                        <li><strong>Device Information:</strong> IP address, browser type, operating system</li>
                        <li><strong>Location Data:</strong> General location based on IP address</li>
                        <li><strong>Cookies:</strong> See our Cookies section below</li>
                    </ul>
                </div>
                
                <!-- How We Use Information -->
                <div id="how-we-use" class="privacy-section">
                    <h3><i class="fas fa-cogs"></i> How We Use Your Information</h3>
                    
                    <p>We use the information we collect for various purposes:</p>
                    
                    <h4>Service Provision</h4>
                    <ul>
                        <li>Create and manage your account</li>
                        <li>Process orders and payments</li>
                        <li>Provide customer support</li>
                        <li>Facilitate communication between buyers and sellers</li>
                    </ul>
                    
                    <h4>Improvement and Personalization</h4>
                    <ul>
                        <li>Personalize your experience and recommendations</li>
                        <li>Analyze usage patterns to improve our services</li>
                        <li>Develop new features and functionality</li>
                        <li>Conduct research and analytics</li>
                    </ul>
                    
                    <h4>Communication</h4>
                    <ul>
                        <li>Send order confirmations and updates</li>
                        <li>Provide customer support responses</li>
                        <li>Send marketing communications (with your consent)</li>
                        <li>Notify you of important changes or updates</li>
                    </ul>
                    
                    <h4>Legal and Security</h4>
                    <ul>
                        <li>Comply with legal obligations</li>
                        <li>Prevent fraud and abuse</li>
                        <li>Enforce our terms of service</li>
                        <li>Protect the rights and safety of users</li>
                    </ul>
                </div>
                
                <!-- Information Sharing -->
                <div id="information-sharing" class="privacy-section">
                    <h3><i class="fas fa-share-alt"></i> Information Sharing and Disclosure</h3>
                    
                    <p>We do not sell, trade, or rent your personal information to third parties. We may share your information in the following circumstances:</p>
                    
                    <h4>With Vendors</h4>
                    <ul>
                        <li>Order details and shipping information for order fulfillment</li>
                        <li>Contact information for customer service purposes</li>
                        <li>Payment information is not shared with vendors</li>
                    </ul>
                    
                    <h4>Service Providers</h4>
                    <ul>
                        <li>Payment processors for secure transaction handling</li>
                        <li>Shipping companies for order delivery</li>
                        <li>Email service providers for communications</li>
                        <li>Analytics providers for service improvement</li>
                    </ul>
                    
                    <h4>Legal Requirements</h4>
                    <ul>
                        <li>When required by law or legal process</li>
                        <li>To protect our rights and property</li>
                        <li>To prevent fraud or illegal activities</li>
                        <li>In connection with business transfers or mergers</li>
                    </ul>
                </div>
                
                <!-- Data Security -->
                <div id="data-security" class="privacy-section">
                    <h3><i class="fas fa-shield-alt"></i> Data Security</h3>
                    
                    <p>We implement appropriate technical and organizational measures to protect your personal information:</p>
                    
                    <ul>
                        <li><strong>Encryption:</strong> All data transmission is encrypted using SSL/TLS</li>
                        <li><strong>Access Controls:</strong> Limited access to personal information on a need-to-know basis</li>
                        <li><strong>Regular Audits:</strong> Regular security assessments and updates</li>
                        <li><strong>Secure Storage:</strong> Data stored on secure servers with backup systems</li>
                        <li><strong>Employee Training:</strong> Staff trained on privacy and security practices</li>
                    </ul>
                    
                    <p>
                        While we strive to protect your information, no method of transmission over the internet or electronic storage is 100% secure. We cannot guarantee absolute security but are committed to protecting your information using industry-standard practices.
                    </p>
                </div>
                
                <!-- Cookies -->
                <div id="cookies" class="privacy-section">
                    <h3><i class="fas fa-cookie-bite"></i> Cookies and Tracking Technologies</h3>
                    
                    <p>We use cookies and similar technologies to enhance your experience:</p>
                    
                    <h4>Types of Cookies We Use</h4>
                    <ul>
                        <li><strong>Essential Cookies:</strong> Required for basic site functionality</li>
                        <li><strong>Performance Cookies:</strong> Help us understand how you use our site</li>
                        <li><strong>Functional Cookies:</strong> Remember your preferences and settings</li>
                        <li><strong>Marketing Cookies:</strong> Used to deliver relevant advertisements</li>
                    </ul>
                    
                    <h4>Managing Cookies</h4>
                    <p>You can control cookies through your browser settings. However, disabling certain cookies may affect site functionality.</p>
                </div>
                
                <!-- Your Rights -->
                <div id="your-rights" class="privacy-section">
                    <h3><i class="fas fa-user-shield"></i> Your Privacy Rights</h3>
                    
                    <p>You have the following rights regarding your personal information:</p>
                    
                    <ul>
                        <li><strong>Access:</strong> Request a copy of your personal information</li>
                        <li><strong>Correction:</strong> Update or correct inaccurate information</li>
                        <li><strong>Deletion:</strong> Request deletion of your personal information</li>
                        <li><strong>Portability:</strong> Receive your data in a portable format</li>
                        <li><strong>Objection:</strong> Object to certain processing activities</li>
                        <li><strong>Restriction:</strong> Request limitation of processing</li>
                    </ul>
                    
                    <p>To exercise these rights, please contact us using the information provided below.</p>
                </div>
                
                <!-- Data Retention -->
                <div id="data-retention" class="privacy-section">
                    <h3><i class="fas fa-clock"></i> Data Retention</h3>
                    
                    <p>We retain your personal information for as long as necessary to:</p>
                    <ul>
                        <li>Provide our services to you</li>
                        <li>Comply with legal obligations</li>
                        <li>Resolve disputes and enforce agreements</li>
                        <li>Improve our services</li>
                    </ul>
                    
                    <p>When information is no longer needed, we securely delete or anonymize it.</p>
                </div>
                
                <!-- Children's Privacy -->
                <div id="children" class="privacy-section">
                    <h3><i class="fas fa-child"></i> Children's Privacy</h3>
                    
                    <p>
                        MarketHub is not intended for children under 13 years of age. We do not knowingly collect personal information from children under 13. If we become aware that we have collected personal information from a child under 13, we will take steps to delete such information.
                    </p>
                    
                    <p>
                        If you are a parent or guardian and believe your child has provided us with personal information, please contact us immediately.
                    </p>
                </div>
                
                <!-- Policy Changes -->
                <div id="changes" class="privacy-section">
                    <h3><i class="fas fa-edit"></i> Changes to This Privacy Policy</h3>
                    
                    <p>
                        We may update this Privacy Policy from time to time. We will notify you of any changes by posting the new Privacy Policy on this page and updating the "Last Updated" date.
                    </p>
                    
                    <p>
                        We encourage you to review this Privacy Policy periodically for any changes. Changes to this Privacy Policy are effective when they are posted on this page.
                    </p>
                </div>
                
                <!-- Contact -->
                <div id="contact" class="privacy-section">
                    <h3><i class="fas fa-envelope"></i> Contact Us</h3>
                    
                    <p>If you have any questions about this Privacy Policy or our privacy practices, please contact us:</p>
                    
                    <div style="background: #f8f9fa; padding: 1.5rem; border-radius: 8px; margin-top: 1rem;">
                        <h5>MarketHub Privacy Team</h5>
                        <p style="margin-bottom: 0.5rem;"><strong>Email:</strong> privacy@markethub.com</p>
                        <p style="margin-bottom: 0.5rem;"><strong>Phone:</strong> +250 788 123 456</p>
                        <p style="margin-bottom: 0.5rem;"><strong>Address:</strong> Musanze District, Northern Province, Rwanda</p>
                        <p style="margin-bottom: 0;"><strong>Response Time:</strong> We aim to respond within 48 hours</p>
                    </div>
                </div>
                
                <!-- Contact CTA -->
                <div class="contact-privacy">
                    <h4>Questions About Your Privacy?</h4>
                    <p style="margin-bottom: 2rem;">Our privacy team is here to help you understand and exercise your rights.</p>
                    <a href="contact.php" class="btn btn-white">
                        <i class="fas fa-envelope"></i> Contact Privacy Team
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
// Smooth scrolling for table of contents links
document.querySelectorAll('.toc a').forEach(anchor => {
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

// Highlight current section in TOC
window.addEventListener('scroll', function() {
    const sections = document.querySelectorAll('.privacy-section');
    const tocLinks = document.querySelectorAll('.toc a');
    
    let current = '';
    sections.forEach(section => {
        const sectionTop = section.offsetTop;
        const sectionHeight = section.clientHeight;
        if (window.pageYOffset >= sectionTop - 100) {
            current = section.getAttribute('id');
        }
    });
    
    tocLinks.forEach(link => {
        link.style.background = '';
        link.style.color = '';
        if (link.getAttribute('href') === '#' + current) {
            link.style.background = '#10b981';
            link.style.color = 'white';
        }
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>
