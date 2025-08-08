<?php
/**
 * Contact Page - Responsive Design
 */

require_once 'config/config.php';

$page_title = 'Contact Us';
$page_description = 'Get in touch with MarketHub. We\'re here to help vendors and customers with any questions or support needs.';

// Handle contact form submission
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitizeInput($_POST['name'] ?? '');
    $email = sanitizeInput($_POST['email'] ?? '');
    $subject = sanitizeInput($_POST['subject'] ?? '');
    $message = sanitizeInput($_POST['message'] ?? '');
    $user_type = sanitizeInput($_POST['user_type'] ?? '');
    
    // Validation
    if (empty($name) || empty($email) || empty($subject) || empty($message)) {
        $error_message = 'Please fill in all required fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = 'Please enter a valid email address.';
    } else {
        // Save to database
        try {
            $sql = "INSERT INTO contact_messages (name, email, subject, message, user_type, created_at) VALUES (?, ?, ?, ?, ?, NOW())";
            $database->execute($sql, [$name, $email, $subject, $message, $user_type]);
            
            // Send email notification (if email is configured)
            $admin_email = 'admin@markethub.rw';
            $email_subject = "New Contact Message: $subject";
            $email_body = "
                <h3>New Contact Message from MarketHub</h3>
                <p><strong>Name:</strong> $name</p>
                <p><strong>Email:</strong> $email</p>
                <p><strong>User Type:</strong> $user_type</p>
                <p><strong>Subject:</strong> $subject</p>
                <p><strong>Message:</strong></p>
                <p>$message</p>
            ";
            
            // Try to send email (will fail silently if not configured)
            @sendEmail($admin_email, $email_subject, $email_body);
            
            $success_message = 'Thank you for your message! We\'ll get back to you within 24 hours.';
            
            // Clear form data
            $name = $email = $subject = $message = $user_type = '';
            
        } catch (Exception $e) {
            $error_message = 'Sorry, there was an error sending your message. Please try again.';
        }
    }
}

require_once 'includes/header.php';
?>

<style>
/* Responsive Contact Page Styles */
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

.contact-hero {
    background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
    color: white;
    padding: 4rem 0;
    text-align: center;
    position: relative;
    overflow: hidden;
}

.contact-hero::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grid" width="10" height="10" patternUnits="userSpaceOnUse"><path d="M 10 0 L 0 0 0 10" fill="none" stroke="rgba(255,255,255,0.1)" stroke-width="0.5"/></pattern></defs><rect width="100" height="100" fill="url(%23grid)"/></svg>');
    opacity: 0.3;
}

.contact-hero .container {
    position: relative;
    z-index: 1;
}

.contact-hero h1 {
    font-size: clamp(2.5rem, 5vw, 3.5rem);
    font-weight: 800;
    margin-bottom: 1rem;
}

.contact-hero p {
    font-size: 1.2rem;
    opacity: 0.9;
    max-width: 600px;
    margin: 0 auto;
}

.contact-section {
    padding: 4rem 0;
}

.contact-form {
    background: white;
    padding: 3rem;
    border-radius: 16px;
    box-shadow: var(--shadow-lg);
}

.form-group {
    margin-bottom: 1.5rem;
}

.form-label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 600;
    color: var(--text-dark);
}

.form-control {
    width: 100%;
    padding: 1rem;
    border: 2px solid #e5e7eb;
    border-radius: 8px;
    font-size: 1rem;
    transition: border-color 0.3s;
}

.form-control:focus {
    outline: none;
    border-color: var(--primary-color);
    box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
}

.form-select {
    width: 100%;
    padding: 1rem;
    border: 2px solid #e5e7eb;
    border-radius: 8px;
    font-size: 1rem;
    background: white;
    transition: border-color 0.3s;
}

.form-select:focus {
    outline: none;
    border-color: var(--primary-color);
    box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
}

.btn {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 1rem 2rem;
    border-radius: 8px;
    text-decoration: none;
    font-weight: 600;
    transition: all 0.3s ease;
    border: none;
    cursor: pointer;
    font-size: 1rem;
}

.btn-primary {
    background: var(--primary-color);
    color: white;
}

.btn-primary:hover {
    background: var(--secondary-color);
    transform: translateY(-2px);
}

.contact-info {
    background: white;
    padding: 3rem;
    border-radius: 16px;
    box-shadow: var(--shadow-lg);
}

.contact-item {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-bottom: 2rem;
    padding: 1.5rem;
    background: var(--bg-light);
    border-radius: 12px;
    transition: transform 0.3s ease;
}

.contact-item:hover {
    transform: translateY(-2px);
}

.contact-icon {
    width: 50px;
    height: 50px;
    background: var(--primary-color);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.2rem;
}

.contact-details h4 {
    margin: 0 0 0.5rem 0;
    color: var(--text-dark);
    font-weight: 600;
}

.contact-details p {
    margin: 0;
    color: var(--text-light);
}

.alert {
    padding: 1rem 1.5rem;
    border-radius: 8px;
    margin-bottom: 1.5rem;
    font-weight: 500;
}

.alert-success {
    background: #d1fae5;
    color: #065f46;
    border: 1px solid #a7f3d0;
}

.alert-danger {
    background: #fee2e2;
    color: #991b1b;
    border: 1px solid #fca5a5;
}

.faq-section {
    background: var(--bg-light);
    padding: 4rem 0;
}

.faq-item {
    background: white;
    margin-bottom: 1rem;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: var(--shadow);
}

.faq-question {
    padding: 1.5rem;
    background: white;
    border: none;
    width: 100%;
    text-align: left;
    font-weight: 600;
    color: var(--text-dark);
    cursor: pointer;
    display: flex;
    justify-content: space-between;
    align-items: center;
    transition: background 0.3s;
}

.faq-question:hover {
    background: var(--bg-light);
}

.faq-answer {
    padding: 0 1.5rem 1.5rem;
    color: var(--text-light);
    line-height: 1.6;
    display: none;
}

.faq-answer.active {
    display: block;
}

/* Responsive Design */
@media (max-width: 768px) {
    .contact-hero {
        padding: 2rem 0;
    }
    
    .contact-section {
        padding: 2rem 0;
    }
    
    .contact-form,
    .contact-info {
        padding: 2rem;
        margin-bottom: 2rem;
    }
    
    .contact-item {
        flex-direction: column;
        text-align: center;
    }
}

@media (max-width: 480px) {
    .contact-form,
    .contact-info {
        padding: 1.5rem;
    }
    
    .btn {
        width: 100%;
        justify-content: center;
    }
}
</style>

<!-- Hero Section -->
<section class="contact-hero">
    <div class="container">
        <h1>Contact Us</h1>
        <p>
            We're here to help! Get in touch with our team for any questions, support, or feedback.
        </p>
    </div>
</section>

<!-- Contact Section -->
<section class="contact-section">
    <div class="container">
        <div class="row">
            <!-- Contact Form -->
            <div class="col-lg-8 mb-4">
                <div class="contact-form">
                    <h2 style="color: var(--text-dark); margin-bottom: 1.5rem;">Send us a Message</h2>
                    
                    <?php if ($success_message): ?>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle"></i> <?php echo $success_message; ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($error_message): ?>
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle"></i> <?php echo $error_message; ?>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">Full Name *</label>
                                    <input type="text" name="name" class="form-control" 
                                           placeholder="Enter your full name" 
                                           value="<?php echo htmlspecialchars($name ?? ''); ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">Email Address *</label>
                                    <input type="email" name="email" class="form-control" 
                                           placeholder="Enter your email address" 
                                           value="<?php echo htmlspecialchars($email ?? ''); ?>" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">I am a *</label>
                                    <select name="user_type" class="form-select" required>
                                        <option value="">Select your role</option>
                                        <option value="customer" <?php echo ($user_type ?? '') === 'customer' ? 'selected' : ''; ?>>Customer</option>
                                        <option value="vendor" <?php echo ($user_type ?? '') === 'vendor' ? 'selected' : ''; ?>>Vendor</option>
                                        <option value="potential_vendor" <?php echo ($user_type ?? '') === 'potential_vendor' ? 'selected' : ''; ?>>Potential Vendor</option>
                                        <option value="other" <?php echo ($user_type ?? '') === 'other' ? 'selected' : ''; ?>>Other</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">Subject *</label>
                                    <input type="text" name="subject" class="form-control" 
                                           placeholder="What is this about?" 
                                           value="<?php echo htmlspecialchars($subject ?? ''); ?>" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Message *</label>
                            <textarea name="message" class="form-control" rows="6" 
                                      placeholder="Tell us how we can help you..." required><?php echo htmlspecialchars($message ?? ''); ?></textarea>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-paper-plane"></i> Send Message
                        </button>
                    </form>
                </div>
            </div>
            
            <!-- Contact Information -->
            <div class="col-lg-4">
                <div class="contact-info">
                    <h3 style="color: var(--text-dark); margin-bottom: 2rem;">Get in Touch</h3>
                    
                    <div class="contact-item">
                        <div class="contact-icon">
                            <i class="fas fa-map-marker-alt"></i>
                        </div>
                        <div class="contact-details">
                            <h4>Address</h4>
                            <p>Musanze District<br>Northern Province, Rwanda</p>
                        </div>
                    </div>
                    
                    <div class="contact-item">
                        <div class="contact-icon">
                            <i class="fas fa-envelope"></i>
                        </div>
                        <div class="contact-details">
                            <h4>Email</h4>
                            <p>info@markethub.rw<br>support@markethub.rw</p>
                        </div>
                    </div>
                    
                    <div class="contact-item">
                        <div class="contact-icon">
                            <i class="fas fa-phone"></i>
                        </div>
                        <div class="contact-details">
                            <h4>Phone</h4>
                            <p>+250 XXX XXX XXX<br>Available 8AM - 6PM</p>
                        </div>
                    </div>
                    
                    <div class="contact-item">
                        <div class="contact-icon">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="contact-details">
                            <h4>Business Hours</h4>
                            <p>Monday - Friday: 8AM - 6PM<br>Saturday: 9AM - 4PM</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- FAQ Section -->
<section class="faq-section">
    <div class="container">
        <div class="text-center mb-5">
            <h2 style="color: var(--text-dark); margin-bottom: 1rem;">Frequently Asked Questions</h2>
            <p style="color: var(--text-light); font-size: 1.1rem;">Quick answers to common questions</p>
        </div>
        
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="faq-item">
                    <button class="faq-question" onclick="toggleFaq(this)">
                        How do I become a vendor on MarketHub?
                        <i class="fas fa-chevron-down"></i>
                    </button>
                    <div class="faq-answer">
                        To become a vendor, visit our vendor registration page, fill out the application form with your business details, 
                        and submit required documents. Our team will review your application within 2-3 business days.
                    </div>
                </div>
                
                <div class="faq-item">
                    <button class="faq-question" onclick="toggleFaq(this)">
                        What payment methods do you accept?
                        <i class="fas fa-chevron-down"></i>
                    </button>
                    <div class="faq-answer">
                        We accept various payment methods including mobile money (MTN Mobile Money, Airtel Money), 
                        bank transfers, and credit/debit cards. All payments are processed securely.
                    </div>
                </div>
                
                <div class="faq-item">
                    <button class="faq-question" onclick="toggleFaq(this)">
                        How long does delivery take?
                        <i class="fas fa-chevron-down"></i>
                    </button>
                    <div class="faq-answer">
                        Delivery times vary by vendor and location within Musanze District. Most orders are delivered within 1-3 business days. 
                        You can track your order status in your account dashboard.
                    </div>
                </div>
                
                <div class="faq-item">
                    <button class="faq-question" onclick="toggleFaq(this)">
                        Can I return or exchange products?
                        <i class="fas fa-chevron-down"></i>
                    </button>
                    <div class="faq-answer">
                        Return and exchange policies vary by vendor. Each vendor sets their own return policy, 
                        which you can view on their store page or product listings. Contact the vendor directly for returns.
                    </div>
                </div>
                
                <div class="faq-item">
                    <button class="faq-question" onclick="toggleFaq(this)">
                        Is my personal information secure?
                        <i class="fas fa-chevron-down"></i>
                    </button>
                    <div class="faq-answer">
                        Yes, we take data security seriously. All personal information is encrypted and stored securely. 
                        We never share your information with third parties without your consent.
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
function toggleFaq(button) {
    const answer = button.nextElementSibling;
    const icon = button.querySelector('i');
    
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
    
    // Toggle current FAQ item
    answer.classList.toggle('active');
    
    if (answer.classList.contains('active')) {
        icon.className = 'fas fa-chevron-up';
    } else {
        icon.className = 'fas fa-chevron-down';
    }
}
</script>

<?php require_once 'includes/footer.php'; ?>
