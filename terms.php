<?php
/**
 * Terms of Service - MarketHub
 */

require_once 'config/config.php';

$page_title = 'Terms of Service - MarketHub';
$page_description = 'Read the terms and conditions for using MarketHub services.';

require_once 'includes/header.php';
?>

<style>
.terms-hero {
    background: linear-gradient(135deg, #10b981, #059669);
    color: white;
    padding: 4rem 0;
    text-align: center;
}

.terms-hero h1 {
    font-size: 2.5rem;
    font-weight: 800;
    margin-bottom: 1rem;
}

.terms-content {
    padding: 4rem 0;
}

.terms-section {
    background: white;
    border-radius: 12px;
    padding: 2rem;
    margin-bottom: 2rem;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    border-left: 4px solid #10b981;
}

.terms-section h3 {
    color: #10b981;
    margin-bottom: 1.5rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.terms-section h4 {
    color: #1f2937;
    margin: 1.5rem 0 1rem 0;
}

.terms-section p {
    color: #4b5563;
    line-height: 1.6;
    margin-bottom: 1rem;
}

.terms-section ul {
    color: #4b5563;
    margin-bottom: 1rem;
}

.terms-section li {
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

.important-notice {
    background: #fef3c7;
    border: 1px solid #f59e0b;
    border-radius: 8px;
    padding: 1.5rem;
    margin: 2rem 0;
}

.important-notice h5 {
    color: #92400e;
    margin-bottom: 1rem;
}

.important-notice p {
    color: #92400e;
    margin-bottom: 0;
}

.contact-terms {
    background: linear-gradient(135deg, #10b981, #059669);
    color: white;
    padding: 3rem 0;
    text-align: center;
    border-radius: 12px;
    margin-top: 2rem;
}
</style>

<!-- Hero Section -->
<section class="terms-hero">
    <div class="container">
        <h1>📋 Terms of Service</h1>
        <p>Please read these terms carefully before using MarketHub</p>
    </div>
</section>

<!-- Terms Content -->
<section class="terms-content">
    <div class="container">
        <div class="row">
            <div class="col-3">
                <!-- Table of Contents -->
                <div class="toc">
                    <h4>Table of Contents</h4>
                    <ul>
                        <li><a href="#acceptance">Acceptance of Terms</a></li>
                        <li><a href="#description">Service Description</a></li>
                        <li><a href="#eligibility">Eligibility</a></li>
                        <li><a href="#accounts">User Accounts</a></li>
                        <li><a href="#conduct">User Conduct</a></li>
                        <li><a href="#vendor-terms">Vendor Terms</a></li>
                        <li><a href="#payments">Payments & Fees</a></li>
                        <li><a href="#intellectual-property">Intellectual Property</a></li>
                        <li><a href="#privacy">Privacy</a></li>
                        <li><a href="#disclaimers">Disclaimers</a></li>
                        <li><a href="#limitation">Limitation of Liability</a></li>
                        <li><a href="#termination">Termination</a></li>
                        <li><a href="#governing-law">Governing Law</a></li>
                        <li><a href="#changes">Changes to Terms</a></li>
                        <li><a href="#contact">Contact Information</a></li>
                    </ul>
                </div>
            </div>
            
            <div class="col-9">
                <div class="last-updated">
                    <strong>Last Updated:</strong> <?php echo date('F j, Y'); ?>
                </div>
                
                <div class="important-notice">
                    <h5>⚠️ Important Notice</h5>
                    <p>
                        These Terms of Service constitute a legally binding agreement between you and MarketHub. 
                        By using our services, you agree to be bound by these terms. Please read them carefully.
                    </p>
                </div>
                
                <!-- Acceptance of Terms -->
                <div id="acceptance" class="terms-section">
                    <h3><i class="fas fa-handshake"></i> Acceptance of Terms</h3>
                    <p>
                        Welcome to MarketHub! These Terms of Service ("Terms") govern your use of the MarketHub website and services operated by MarketHub ("we," "us," or "our").
                    </p>
                    <p>
                        By accessing or using our service, you agree to be bound by these Terms. If you disagree with any part of these terms, then you may not access the service.
                    </p>
                    <p>
                        These Terms apply to all visitors, users, customers, vendors, and others who access or use the service.
                    </p>
                </div>
                
                <!-- Service Description -->
                <div id="description" class="terms-section">
                    <h3><i class="fas fa-store"></i> Service Description</h3>
                    <p>
                        MarketHub is a multi-vendor e-commerce platform that connects buyers and sellers in Musanze District and across Rwanda. Our services include:
                    </p>
                    <ul>
                        <li>Online marketplace for buying and selling products</li>
                        <li>Payment processing and order management</li>
                        <li>Shipping and delivery coordination</li>
                        <li>Customer support and dispute resolution</li>
                        <li>Vendor tools and analytics</li>
                    </ul>
                    <p>
                        We reserve the right to modify, suspend, or discontinue any part of our service at any time with or without notice.
                    </p>
                </div>
                
                <!-- Eligibility -->
                <div id="eligibility" class="terms-section">
                    <h3><i class="fas fa-user-check"></i> Eligibility</h3>
                    <p>To use MarketHub, you must:</p>
                    <ul>
                        <li>Be at least 18 years old or have parental consent</li>
                        <li>Have the legal capacity to enter into binding contracts</li>
                        <li>Not be prohibited from using our services under applicable law</li>
                        <li>Provide accurate and complete information</li>
                        <li>Comply with all local, state, and national laws</li>
                    </ul>
                    <p>
                        We reserve the right to refuse service to anyone for any reason at any time.
                    </p>
                </div>
                
                <!-- User Accounts -->
                <div id="accounts" class="terms-section">
                    <h3><i class="fas fa-user-cog"></i> User Accounts</h3>
                    
                    <h4>Account Creation</h4>
                    <p>To access certain features, you must create an account. You agree to:</p>
                    <ul>
                        <li>Provide accurate, current, and complete information</li>
                        <li>Maintain and update your information</li>
                        <li>Keep your password secure and confidential</li>
                        <li>Accept responsibility for all activities under your account</li>
                        <li>Notify us immediately of any unauthorized use</li>
                    </ul>
                    
                    <h4>Account Security</h4>
                    <p>
                        You are responsible for maintaining the security of your account and password. 
                        MarketHub cannot and will not be liable for any loss or damage from your failure to comply with this security obligation.
                    </p>
                </div>
                
                <!-- User Conduct -->
                <div id="conduct" class="terms-section">
                    <h3><i class="fas fa-gavel"></i> User Conduct</h3>
                    
                    <p>You agree not to:</p>
                    <ul>
                        <li>Use the service for any unlawful purpose or to solicit unlawful acts</li>
                        <li>Violate any international, federal, provincial, or state regulations or laws</li>
                        <li>Infringe upon or violate our intellectual property rights or the intellectual property rights of others</li>
                        <li>Harass, abuse, insult, harm, defame, slander, disparage, intimidate, or discriminate</li>
                        <li>Submit false or misleading information</li>
                        <li>Upload viruses or any other type of malicious code</li>
                        <li>Spam, phish, pharm, pretext, spider, crawl, or scrape</li>
                        <li>Interfere with or circumvent the security features of the service</li>
                    </ul>
                    
                    <h4>Prohibited Activities</h4>
                    <ul>
                        <li>Selling counterfeit, stolen, or illegal items</li>
                        <li>Manipulating reviews or ratings</li>
                        <li>Creating multiple accounts to circumvent restrictions</li>
                        <li>Attempting to gain unauthorized access to other accounts</li>
                    </ul>
                </div>
                
                <!-- Vendor Terms -->
                <div id="vendor-terms" class="terms-section">
                    <h3><i class="fas fa-store-alt"></i> Vendor Terms</h3>
                    
                    <h4>Vendor Responsibilities</h4>
                    <p>As a vendor, you agree to:</p>
                    <ul>
                        <li>Provide accurate product descriptions and images</li>
                        <li>Honor all sales and maintain adequate inventory</li>
                        <li>Ship orders promptly and securely</li>
                        <li>Provide excellent customer service</li>
                        <li>Comply with all applicable laws and regulations</li>
                        <li>Handle returns and refunds according to your stated policies</li>
                    </ul>
                    
                    <h4>Product Listings</h4>
                    <ul>
                        <li>You retain ownership of your product content</li>
                        <li>You grant us a license to display and promote your products</li>
                        <li>You are responsible for the accuracy of all product information</li>
                        <li>We reserve the right to remove listings that violate our policies</li>
                    </ul>
                </div>
                
                <!-- Payments & Fees -->
                <div id="payments" class="terms-section">
                    <h3><i class="fas fa-credit-card"></i> Payments & Fees</h3>
                    
                    <h4>Customer Payments</h4>
                    <ul>
                        <li>All prices are listed in Rwandan Francs (RWF)</li>
                        <li>Payment is due at the time of purchase</li>
                        <li>We accept various payment methods as listed on our site</li>
                        <li>All transactions are processed securely</li>
                    </ul>
                    
                    <h4>Vendor Fees</h4>
                    <ul>
                        <li>Commission: 5% of each successful sale</li>
                        <li>Payment processing fees are included in the commission</li>
                        <li>No setup fees or monthly charges</li>
                        <li>Fees are automatically deducted from vendor payments</li>
                    </ul>
                    
                    <h4>Refunds and Disputes</h4>
                    <p>
                        Refund policies are set by individual vendors. MarketHub may facilitate dispute resolution but is not responsible for refunds unless required by law.
                    </p>
                </div>
                
                <!-- Intellectual Property -->
                <div id="intellectual-property" class="terms-section">
                    <h3><i class="fas fa-copyright"></i> Intellectual Property</h3>
                    
                    <h4>MarketHub Content</h4>
                    <p>
                        The service and its original content, features, and functionality are and will remain the exclusive property of MarketHub and its licensors. The service is protected by copyright, trademark, and other laws.
                    </p>
                    
                    <h4>User Content</h4>
                    <p>
                        You retain ownership of content you submit to MarketHub. By submitting content, you grant us a worldwide, non-exclusive, royalty-free license to use, display, and distribute your content in connection with the service.
                    </p>
                    
                    <h4>Copyright Infringement</h4>
                    <p>
                        We respect intellectual property rights. If you believe your copyright has been infringed, please contact us with details of the alleged infringement.
                    </p>
                </div>
                
                <!-- Privacy -->
                <div id="privacy" class="terms-section">
                    <h3><i class="fas fa-shield-alt"></i> Privacy</h3>
                    <p>
                        Your privacy is important to us. Please review our Privacy Policy, which also governs your use of the service, to understand our practices.
                    </p>
                    <p>
                        By using our service, you consent to the collection and use of information in accordance with our Privacy Policy.
                    </p>
                </div>
                
                <!-- Disclaimers -->
                <div id="disclaimers" class="terms-section">
                    <h3><i class="fas fa-exclamation-triangle"></i> Disclaimers</h3>
                    
                    <p>
                        THE INFORMATION ON THIS WEBSITE IS PROVIDED ON AN "AS IS" BASIS. TO THE FULLEST EXTENT PERMITTED BY LAW, THIS COMPANY:
                    </p>
                    <ul>
                        <li>EXCLUDES ALL REPRESENTATIONS AND WARRANTIES RELATING TO THIS WEBSITE AND ITS CONTENTS</li>
                        <li>EXCLUDES ALL LIABILITY FOR DAMAGES ARISING OUT OF OR IN CONNECTION WITH YOUR USE OF THIS WEBSITE</li>
                        <li>DOES NOT GUARANTEE THE ACCURACY, COMPLETENESS, OR TIMELINESS OF INFORMATION</li>
                        <li>DOES NOT WARRANT THAT THE SERVICE WILL BE UNINTERRUPTED OR ERROR-FREE</li>
                    </ul>
                    
                    <p>
                        MarketHub is a platform that facilitates transactions between buyers and sellers. We are not a party to the actual transaction and do not guarantee the quality, safety, or legality of products listed.
                    </p>
                </div>
                
                <!-- Limitation of Liability -->
                <div id="limitation" class="terms-section">
                    <h3><i class="fas fa-balance-scale"></i> Limitation of Liability</h3>
                    
                    <p>
                        IN NO EVENT SHALL MARKETHUB, ITS DIRECTORS, EMPLOYEES, PARTNERS, AGENTS, SUPPLIERS, OR AFFILIATES BE LIABLE FOR:
                    </p>
                    <ul>
                        <li>ANY INDIRECT, INCIDENTAL, SPECIAL, CONSEQUENTIAL, OR PUNITIVE DAMAGES</li>
                        <li>ANY LOSS OF PROFITS, REVENUES, DATA, OR USE</li>
                        <li>ANY DAMAGES EXCEEDING THE AMOUNT PAID BY YOU TO MARKETHUB IN THE 12 MONTHS PRECEDING THE CLAIM</li>
                    </ul>
                    
                    <p>
                        This limitation applies whether the alleged liability is based on contract, tort, negligence, strict liability, or any other basis.
                    </p>
                </div>
                
                <!-- Termination -->
                <div id="termination" class="terms-section">
                    <h3><i class="fas fa-times-circle"></i> Termination</h3>
                    
                    <p>
                        We may terminate or suspend your account and bar access to the service immediately, without prior notice or liability, under our sole discretion, for any reason whatsoever, including but not limited to a breach of the Terms.
                    </p>
                    
                    <p>
                        If you wish to terminate your account, you may simply discontinue using the service or contact us to request account deletion.
                    </p>
                    
                    <p>
                        Upon termination, your right to use the service will cease immediately. All provisions of the Terms which by their nature should survive termination shall survive termination.
                    </p>
                </div>
                
                <!-- Governing Law -->
                <div id="governing-law" class="terms-section">
                    <h3><i class="fas fa-landmark"></i> Governing Law</h3>
                    
                    <p>
                        These Terms shall be interpreted and governed by the laws of Rwanda, without regard to its conflict of law provisions.
                    </p>
                    
                    <p>
                        Our failure to enforce any right or provision of these Terms will not be considered a waiver of those rights.
                    </p>
                    
                    <p>
                        If any provision of these Terms is held to be invalid or unenforceable by a court, the remaining provisions of these Terms will remain in effect.
                    </p>
                </div>
                
                <!-- Changes to Terms -->
                <div id="changes" class="terms-section">
                    <h3><i class="fas fa-edit"></i> Changes to Terms</h3>
                    
                    <p>
                        We reserve the right, at our sole discretion, to modify or replace these Terms at any time. If a revision is material, we will provide at least 30 days notice prior to any new terms taking effect.
                    </p>
                    
                    <p>
                        What constitutes a material change will be determined at our sole discretion. By continuing to access or use our service after any revisions become effective, you agree to be bound by the revised terms.
                    </p>
                </div>
                
                <!-- Contact Information -->
                <div id="contact" class="terms-section">
                    <h3><i class="fas fa-envelope"></i> Contact Information</h3>
                    
                    <p>If you have any questions about these Terms of Service, please contact us:</p>
                    
                    <div style="background: #f8f9fa; padding: 1.5rem; border-radius: 8px; margin-top: 1rem;">
                        <h5>MarketHub Legal Team</h5>
                        <p style="margin-bottom: 0.5rem;"><strong>Email:</strong> legal@markethub.com</p>
                        <p style="margin-bottom: 0.5rem;"><strong>Phone:</strong> +250 788 123 456</p>
                        <p style="margin-bottom: 0.5rem;"><strong>Address:</strong> Musanze District, Northern Province, Rwanda</p>
                        <p style="margin-bottom: 0;"><strong>Business Hours:</strong> Monday - Friday, 8:00 AM - 6:00 PM</p>
                    </div>
                </div>
                
                <!-- Contact CTA -->
                <div class="contact-terms">
                    <h4>Questions About These Terms?</h4>
                    <p style="margin-bottom: 2rem;">Our legal team is available to help clarify any questions you may have.</p>
                    <a href="contact.php" class="btn btn-white">
                        <i class="fas fa-envelope"></i> Contact Legal Team
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
    const sections = document.querySelectorAll('.terms-section');
    const tocLinks = document.querySelectorAll('.toc a');
    
    let current = '';
    sections.forEach(section => {
        const sectionTop = section.offsetTop;
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
