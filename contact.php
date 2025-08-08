<?php
/**
 * MarketHub Contact Page
 * Multi-Vendor E-Commerce Platform
 */

require_once 'config/config.php';

$page_title = 'Contact Us';
$success_message = '';
$error_message = '';

// Handle contact form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error_message = 'Invalid security token. Please try again.';
    } else {
        try {
            $name = sanitizeInput($_POST['name'] ?? '');
            $email = sanitizeInput($_POST['email'] ?? '');
            $subject = sanitizeInput($_POST['subject'] ?? '');
            $message = sanitizeInput($_POST['message'] ?? '');
            $inquiry_type = sanitizeInput($_POST['inquiry_type'] ?? '');
            
            // Validation
            if (empty($name) || empty($email) || empty($subject) || empty($message)) {
                throw new Exception('Please fill in all required fields.');
            }
            
            if (!validateEmailFormat($email)) {
                throw new Exception('Please enter a valid email address.');
            }
            
            if (strlen($message) < 10) {
                throw new Exception('Message must be at least 10 characters long.');
            }
            
            // Save contact inquiry to database
            $sql = "INSERT INTO contact_inquiries (name, email, subject, message, inquiry_type, status, created_at) 
                    VALUES (?, ?, ?, ?, ?, 'new', NOW())";
            
            $inquiry_id = $database->insert($sql, [$name, $email, $subject, $message, $inquiry_type]);
            
            // Send confirmation email to customer
            $customer_subject = "Thank you for contacting MarketHub";
            $customer_message = "
                <h2>Thank you for reaching out!</h2>
                <p>Dear $name,</p>
                <p>We have received your inquiry and will respond within 24 hours.</p>
                <p><strong>Your inquiry details:</strong></p>
                <p><strong>Subject:</strong> $subject</p>
                <p><strong>Message:</strong> $message</p>
                <p><strong>Reference ID:</strong> #$inquiry_id</p>
                <p>Best regards,<br>MarketHub Support Team</p>
            ";
            
            sendEmail($email, $customer_subject, $customer_message);
            
            // Send notification to admin
            $admin_subject = "New Contact Inquiry - $subject";
            $admin_message = "
                <h2>New Contact Inquiry</h2>
                <p><strong>From:</strong> $name ($email)</p>
                <p><strong>Type:</strong> $inquiry_type</p>
                <p><strong>Subject:</strong> $subject</p>
                <p><strong>Message:</strong></p>
                <p>$message</p>
                <p><strong>Reference ID:</strong> #$inquiry_id</p>
            ";
            
            sendEmail(ADMIN_EMAIL, $admin_subject, $admin_message);
            
            // Log activity
            logActivity(0, 'contact_inquiry', "Inquiry: $subject");
            
            $success_message = 'Thank you for your message! We will respond within 24 hours.';
            
        } catch (Exception $e) {
            $error_message = $e->getMessage();
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

.alert-error {
    background: #fee2e2;
    color: #991b1b;
    border: 1px solid #fca5a5;
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
                            <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success_message); ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($error_message): ?>
                        <div class="alert alert-error">
                            <i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($error_message); ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">Full Name *</label>
                                    <input type="text" name="name" class="form-control"
                                           placeholder="Enter your full name"
                                           value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">Email Address *</label>
                                    <input type="email" name="email" class="form-control"
                                           placeholder="Enter your email address"
                                           value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">Inquiry Type *</label>
                                    <select name="inquiry_type" class="form-select" required>
                                        <option value="">Select inquiry type</option>
                                        <option value="general" <?php echo ($_POST['inquiry_type'] ?? '') === 'general' ? 'selected' : ''; ?>>General Inquiry</option>
                                        <option value="support" <?php echo ($_POST['inquiry_type'] ?? '') === 'support' ? 'selected' : ''; ?>>Technical Support</option>
                                        <option value="vendor" <?php echo ($_POST['inquiry_type'] ?? '') === 'vendor' ? 'selected' : ''; ?>>Vendor Application</option>
                                        <option value="billing" <?php echo ($_POST['inquiry_type'] ?? '') === 'billing' ? 'selected' : ''; ?>>Billing & Payments</option>
                                        <option value="partnership" <?php echo ($_POST['inquiry_type'] ?? '') === 'partnership' ? 'selected' : ''; ?>>Partnership</option>
                                        <option value="feedback" <?php echo ($_POST['inquiry_type'] ?? '') === 'feedback' ? 'selected' : ''; ?>>Feedback</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">Subject *</label>
                                    <input type="text" name="subject" class="form-control"
                                           placeholder="What is this about?"
                                           value="<?php echo htmlspecialchars($_POST['subject'] ?? ''); ?>" required>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Message *</label>
                            <textarea name="message" class="form-control" rows="6"
                                      placeholder="Tell us how we can help you..." required><?php echo htmlspecialchars($_POST['message'] ?? ''); ?></textarea>
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

            <!-- Quick Links -->
            <div class="card mb-3">
                <div class="card-header">
                    <h4>Quick Help</h4>
                </div>
                <div class="card-body">
                    <div class="quick-links">
                        <a href="faq.php" class="quick-link">
                            <i class="fas fa-question-circle"></i>
                            <span>Frequently Asked Questions</span>
                        </a>
                        <a href="vendor-register.php" class="quick-link">
                            <i class="fas fa-store"></i>
                            <span>Become a Vendor</span>
                        </a>
                        <a href="help.php" class="quick-link">
                            <i class="fas fa-life-ring"></i>
                            <span>Help Center</span>
                        </a>
                        <a href="terms.php" class="quick-link">
                            <i class="fas fa-file-contract"></i>
                            <span>Terms & Conditions</span>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Social Media -->
            <div class="card">
                <div class="card-header">
                    <h4>Follow Us</h4>
                </div>
                <div class="card-body">
                    <div class="social-links">
                        <a href="#" class="social-link facebook">
                            <i class="fab fa-facebook-f"></i>
                            <span>Facebook</span>
                        </a>
                        <a href="#" class="social-link twitter">
                            <i class="fab fa-twitter"></i>
                            <span>Twitter</span>
                        </a>
                        <a href="#" class="social-link instagram">
                            <i class="fab fa-instagram"></i>
                            <span>Instagram</span>
                        </a>
                        <a href="#" class="social-link linkedin">
                            <i class="fab fa-linkedin-in"></i>
                            <span>LinkedIn</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>

