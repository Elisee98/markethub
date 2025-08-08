<?php
/**
 * MarketHub Customer Registration
 * Multi-Vendor E-Commerce Platform
 */

require_once 'config/config.php';

$page_title = 'Create Account';
$error_message = '';
$success_message = '';

// Redirect if already logged in
if (isLoggedIn()) {
    redirect('dashboard.php');
}

// Handle registration form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error_message = 'Invalid security token. Please try again.';
    } else {
        try {
            $first_name = sanitizeInput($_POST['first_name'] ?? '');
            $last_name = sanitizeInput($_POST['last_name'] ?? '');
            $email = sanitizeInput($_POST['email'] ?? '');
            $phone = sanitizeInput($_POST['phone'] ?? '');
            $password = $_POST['password'] ?? '';
            $confirm_password = $_POST['confirm_password'] ?? '';
            $terms_accepted = isset($_POST['terms_accepted']);
            $newsletter_subscribe = isset($_POST['newsletter_subscribe']);
            
            // Validation
            if (empty($first_name) || empty($last_name) || empty($email) || empty($password)) {
                throw new Exception('Please fill in all required fields.');
            }
            
            if (!validateEmailFormat($email)) {
                throw new Exception('Please enter a valid email address.');
            }
            
            if (strlen($password) < 6) {
                throw new Exception('Password must be at least 6 characters long.');
            }
            
            if ($password !== $confirm_password) {
                throw new Exception('Passwords do not match.');
            }
            
            if (!$terms_accepted) {
                throw new Exception('You must accept the Terms and Conditions to create an account.');
            }
            
            // Check if email already exists
            $existing_user = $database->fetch(
                "SELECT id FROM users WHERE email = ?", 
                [$email]
            );
            
            if ($existing_user) {
                throw new Exception('An account with this email address already exists.');
            }
            
            // Create user account
            $password_hash = password_hash($password, PASSWORD_DEFAULT);

            // Set initial status based on configuration
            $initial_status = REQUIRE_ADMIN_APPROVAL ? 'pending' : 'active';

            $sql = "INSERT INTO users (first_name, last_name, email, phone, password_hash,
                                     user_type, status, created_at)
                    VALUES (?, ?, ?, ?, ?, 'customer', ?, NOW())";

            $user_id = $database->insert($sql, [
                $first_name, $last_name, $email, $phone,
                $password_hash, $initial_status
            ]);
            
            // Create customer profile
            $profile_sql = "INSERT INTO customer_profiles (customer_id, newsletter_subscribed, created_at) 
                           VALUES (?, ?, NOW())";
            
            $database->execute($profile_sql, [$user_id, $newsletter_subscribe ? 1 : 0]);
            
            // Log activity
            logActivity($user_id, 'user_registered', 'Customer account created');
            
            // Send appropriate email based on approval requirement
            if (REQUIRE_ADMIN_APPROVAL) {
                $subject = "MarketHub Account - Pending Approval";
                $message = "
                    <h2>Thank you for registering with MarketHub!</h2>
                    <p>Dear $first_name,</p>
                    <p>Your account has been created and is currently pending admin approval.</p>
                    <p><strong>What happens next?</strong></p>
                    <ul>
                        <li>Our admin team will review your account within 24-48 hours</li>
                        <li>You will receive an email notification once approved</li>
                        <li>After approval, you can login and start shopping</li>
                    </ul>
                    <p><strong>Account Details:</strong></p>
                    <ul>
                        <li>Name: $first_name $last_name</li>
                        <li>Email: $email</li>
                        <li>Registration Date: " . date('Y-m-d H:i:s') . "</li>
                    </ul>
                    <p>If you have any questions, feel free to contact our support team.</p>
                    <p>Thank you for your patience!<br>The MarketHub Team</p>
                ";
            } else {
                $subject = "Welcome to MarketHub - Account Created Successfully";
                $message = "
                    <h2>Welcome to MarketHub!</h2>
                    <p>Dear $first_name,</p>
                    <p>Thank you for joining MarketHub, Musanze District's premier online marketplace!</p>
                    <p>Your account has been created and activated successfully. You can now:</p>
                    <ul>
                        <li>Browse products from local vendors</li>
                        <li>Add items to your cart and place orders</li>
                        <li>Track your order status</li>
                        <li>Leave reviews for products</li>
                        <li>Manage your profile and preferences</li>
                    </ul>
                    <p><a href='" . SITE_URL . "login.php' style='background: #2E7D32; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px;'>Login to Your Account</a></p>
                    <p>If you have any questions, feel free to contact our support team.</p>
                    <p>Happy shopping!<br>The MarketHub Team</p>
                ";
            }

            sendEmail($email, $subject, $message);
            
            // Send admin notification
            if (REQUIRE_ADMIN_APPROVAL) {
                $admin_subject = "New Customer Registration - Approval Required";
                $admin_message = "
                    <h2>New Customer Registration - Approval Required</h2>
                    <p>A new customer has registered and requires approval:</p>
                    <p><strong>Name:</strong> $first_name $last_name</p>
                    <p><strong>Email:</strong> $email</p>
                    <p><strong>Phone:</strong> $phone</p>
                    <p><strong>Registration Date:</strong> " . date('Y-m-d H:i:s') . "</p>
                    <p><strong>Status:</strong> Pending Approval</p>
                    <p><a href='" . SITE_URL . "admin/user-management.php' style='background: #2E7D32; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px;'>Review Application</a></p>
                ";
                $success_message = 'Account created successfully! Your account is pending admin approval. You will receive an email notification once approved.';
            } else {
                $admin_subject = "New Customer Registration - $first_name $last_name";
                $admin_message = "
                    <h2>New Customer Registration</h2>
                    <p><strong>Name:</strong> $first_name $last_name</p>
                    <p><strong>Email:</strong> $email</p>
                    <p><strong>Phone:</strong> $phone</p>
                    <p><strong>Registration Date:</strong> " . date('Y-m-d H:i:s') . "</p>
                    <p><strong>Status:</strong> Active</p>
                ";
                $success_message = 'Account created successfully! You can now log in to start shopping.';
            }

            sendEmail(ADMIN_EMAIL, $admin_subject, $admin_message);
            
        } catch (Exception $e) {
            $error_message = $e->getMessage();
        }
    }
}

require_once 'includes/header.php';
?>

<div class="auth-container">
    <div class="auth-card">
        <!-- Header -->
        <div class="auth-header">
            <div class="auth-logo">
                <img src="assets/images/logo.png" alt="MarketHub" onerror="this.style.display='none'">
                <h2>Create Your Account</h2>
            </div>
            <p>Join MarketHub and discover amazing products from local vendors in Musanze District</p>
        </div>

        <!-- Registration Form -->
        <div class="auth-form">
            <?php if ($success_message): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?php echo htmlspecialchars($success_message); ?>
                    <div style="margin-top: 1rem;">
                        <a href="login.php" class="btn btn-primary">Login Now</a>
                    </div>
                </div>
            <?php else: ?>
                <?php if ($error_message): ?>
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-circle"></i>
                        <?php echo htmlspecialchars($error_message); ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="" class="registration-form">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    
                    <!-- Personal Information -->
                    <div class="form-section">
                        <h4>Personal Information</h4>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="first_name" class="form-label">First Name *</label>
                                <input type="text" id="first_name" name="first_name" class="form-control" 
                                       value="<?php echo htmlspecialchars($_POST['first_name'] ?? ''); ?>" 
                                       placeholder="Enter your first name" required>
                            </div>
                            <div class="form-group">
                                <label for="last_name" class="form-label">Last Name *</label>
                                <input type="text" id="last_name" name="last_name" class="form-control" 
                                       value="<?php echo htmlspecialchars($_POST['last_name'] ?? ''); ?>" 
                                       placeholder="Enter your last name" required>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="email" class="form-label">Email Address *</label>
                            <input type="email" id="email" name="email" class="form-control" 
                                   value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" 
                                   placeholder="Enter your email address" required>
                            <small class="form-help">We'll use this email for order updates and account notifications</small>
                        </div>

                        <div class="form-group">
                            <label for="phone" class="form-label">Phone Number</label>
                            <input type="tel" id="phone" name="phone" class="form-control" 
                                   value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>" 
                                   placeholder="+250 7XX XXX XXX">
                            <small class="form-help">Optional - for order delivery notifications</small>
                        </div>
                    </div>

                    <!-- Security -->
                    <div class="form-section">
                        <h4>Account Security</h4>
                        
                        <div class="form-group">
                            <label for="password" class="form-label">Password *</label>
                            <div class="password-input">
                                <input type="password" id="password" name="password" class="form-control" 
                                       placeholder="Create a strong password" required>
                                <button type="button" class="password-toggle" onclick="togglePassword('password')">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            <div class="password-strength" id="password-strength"></div>
                            <small class="form-help">Minimum 6 characters, include letters and numbers</small>
                        </div>

                        <div class="form-group">
                            <label for="confirm_password" class="form-label">Confirm Password *</label>
                            <div class="password-input">
                                <input type="password" id="confirm_password" name="confirm_password" class="form-control" 
                                       placeholder="Confirm your password" required>
                                <button type="button" class="password-toggle" onclick="togglePassword('confirm_password')">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            <div class="password-match" id="password-match"></div>
                        </div>
                    </div>

                    <!-- Preferences -->
                    <div class="form-section">
                        <h4>Preferences</h4>
                        
                        <div class="checkbox-group">
                            <label class="checkbox-label">
                                <input type="checkbox" name="newsletter_subscribe" value="1" 
                                       <?php echo isset($_POST['newsletter_subscribe']) ? 'checked' : ''; ?>>
                                <span class="checkbox-custom"></span>
                                <span class="checkbox-text">
                                    Subscribe to our newsletter for deals and updates
                                    <small>Get notified about special offers and new products from local vendors</small>
                                </span>
                            </label>
                        </div>
                    </div>

                    <!-- Terms and Conditions -->
                    <div class="form-section">
                        <div class="checkbox-group">
                            <label class="checkbox-label">
                                <input type="checkbox" name="terms_accepted" value="1" required>
                                <span class="checkbox-custom"></span>
                                <span class="checkbox-text">
                                    I agree to the <a href="terms.php" target="_blank">Terms and Conditions</a> 
                                    and <a href="privacy.php" target="_blank">Privacy Policy</a> *
                                </span>
                            </label>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary btn-lg btn-block">
                            <i class="fas fa-user-plus"></i> Create Account
                        </button>
                    </div>
                </form>
            <?php endif; ?>
        </div>

        <!-- Footer -->
        <div class="auth-footer">
            <p>Already have an account? <a href="login.php">Sign in here</a></p>
            <div class="auth-links">
                <a href="vendor-register.php">Become a Vendor</a>
                <span>•</span>
                <a href="contact.php">Need Help?</a>
            </div>
        </div>
    </div>

    <!-- Benefits Section -->
    <div class="benefits-section">
        <h3>Why Join MarketHub?</h3>
        <div class="benefits-grid">
            <div class="benefit-item">
                <div class="benefit-icon">
                    <i class="fas fa-store"></i>
                </div>
                <h5>Local Products</h5>
                <p>Discover unique products from talented vendors in Musanze District</p>
            </div>
            
            <div class="benefit-item">
                <div class="benefit-icon">
                    <i class="fas fa-shipping-fast"></i>
                </div>
                <h5>Fast Delivery</h5>
                <p>Quick and reliable delivery within Musanze District</p>
            </div>
            
            <div class="benefit-item">
                <div class="benefit-icon">
                    <i class="fas fa-shield-alt"></i>
                </div>
                <h5>Secure Shopping</h5>
                <p>Safe and secure payment processing with buyer protection</p>
            </div>
            
            <div class="benefit-item">
                <div class="benefit-icon">
                    <i class="fas fa-headset"></i>
                </div>
                <h5>Local Support</h5>
                <p>Customer support in Kinyarwanda, English, and French</p>
            </div>
        </div>
    </div>
</div>

<style>
.auth-container {
    min-height: 100vh;
    background: linear-gradient(135deg, #f8f9fa, #e9ecef);
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 2rem;
    gap: 3rem;
}

.auth-card {
    background: white;
    border-radius: var(--border-radius);
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    width: 100%;
    max-width: 500px;
    overflow: hidden;
}

.auth-header {
    background: linear-gradient(135deg, var(--primary-green), var(--secondary-green));
    color: white;
    padding: 2rem;
    text-align: center;
}

.auth-logo img {
    height: 40px;
    margin-bottom: 1rem;
}

.auth-header h2 {
    color: white;
    margin-bottom: 0.5rem;
}

.auth-header p {
    opacity: 0.9;
    margin-bottom: 0;
}

.auth-form {
    padding: 2rem;
}

.form-section {
    margin-bottom: 2rem;
    padding-bottom: 1.5rem;
    border-bottom: 1px solid var(--light-gray);
}

.form-section:last-child {
    border-bottom: none;
    margin-bottom: 0;
}

.form-section h4 {
    color: var(--primary-green);
    margin-bottom: 1rem;
    font-size: 1.1rem;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
}

.form-group {
    margin-bottom: 1.5rem;
}

.form-help {
    color: var(--medium-gray);
    font-size: 0.85rem;
    margin-top: 0.25rem;
    display: block;
}

.password-input {
    position: relative;
}

.password-toggle {
    position: absolute;
    right: 12px;
    top: 50%;
    transform: translateY(-50%);
    background: none;
    border: none;
    color: var(--medium-gray);
    cursor: pointer;
    padding: 0;
    width: 20px;
    height: 20px;
}

.password-strength {
    margin-top: 0.5rem;
    height: 4px;
    background: var(--light-gray);
    border-radius: 2px;
    overflow: hidden;
}

.password-strength.weak { background: #F44336; }
.password-strength.medium { background: #FF9800; }
.password-strength.strong { background: #4CAF50; }

.password-match {
    margin-top: 0.25rem;
    font-size: 0.85rem;
}

.password-match.match { color: #4CAF50; }
.password-match.no-match { color: #F44336; }

.checkbox-group {
    margin-bottom: 1rem;
}

.checkbox-label {
    display: flex;
    align-items: flex-start;
    gap: 0.75rem;
    cursor: pointer;
    line-height: 1.5;
}

.checkbox-label input[type="checkbox"] {
    display: none;
}

.checkbox-custom {
    width: 20px;
    height: 20px;
    border: 2px solid var(--medium-gray);
    border-radius: 4px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    margin-top: 2px;
    transition: var(--transition);
}

.checkbox-label input[type="checkbox"]:checked + .checkbox-custom {
    background: var(--primary-green);
    border-color: var(--primary-green);
}

.checkbox-label input[type="checkbox"]:checked + .checkbox-custom::after {
    content: '✓';
    color: white;
    font-size: 12px;
    font-weight: bold;
}

.checkbox-text {
    flex: 1;
}

.checkbox-text small {
    display: block;
    color: var(--medium-gray);
    font-size: 0.85rem;
    margin-top: 0.25rem;
}

.checkbox-text a {
    color: var(--primary-green);
    text-decoration: none;
}

.checkbox-text a:hover {
    text-decoration: underline;
}

.form-actions {
    margin-top: 2rem;
}

.auth-footer {
    background: #f8f9fa;
    padding: 1.5rem 2rem;
    text-align: center;
    border-top: 1px solid var(--light-gray);
}

.auth-footer p {
    margin-bottom: 1rem;
}

.auth-footer a {
    color: var(--primary-green);
    text-decoration: none;
    font-weight: 600;
}

.auth-footer a:hover {
    text-decoration: underline;
}

.auth-links {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 1rem;
    font-size: 0.9rem;
}

.benefits-section {
    background: white;
    border-radius: var(--border-radius);
    padding: 2rem;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    max-width: 400px;
}

.benefits-section h3 {
    color: var(--primary-green);
    margin-bottom: 1.5rem;
    text-align: center;
}

.benefits-grid {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.benefit-item {
    display: flex;
    align-items: flex-start;
    gap: 1rem;
}

.benefit-icon {
    width: 40px;
    height: 40px;
    background: var(--primary-green);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    flex-shrink: 0;
}

.benefit-item h5 {
    margin-bottom: 0.25rem;
    color: var(--black);
}

.benefit-item p {
    color: var(--dark-gray);
    font-size: 0.9rem;
    margin-bottom: 0;
    line-height: 1.4;
}

@media (max-width: 768px) {
    .auth-container {
        flex-direction: column;
        padding: 1rem;
    }
    
    .auth-card {
        max-width: 100%;
    }
    
    .benefits-section {
        max-width: 100%;
    }
    
    .form-row {
        grid-template-columns: 1fr;
    }
    
    .auth-links {
        flex-direction: column;
        gap: 0.5rem;
    }
    
    .auth-links span {
        display: none;
    }
}
</style>

<script>
function togglePassword(fieldId) {
    const field = document.getElementById(fieldId);
    const toggle = field.nextElementSibling.querySelector('i');
    
    if (field.type === 'password') {
        field.type = 'text';
        toggle.className = 'fas fa-eye-slash';
    } else {
        field.type = 'password';
        toggle.className = 'fas fa-eye';
    }
}

// Password strength checker
document.getElementById('password').addEventListener('input', function() {
    const password = this.value;
    const strengthBar = document.getElementById('password-strength');
    
    let strength = 0;
    if (password.length >= 6) strength++;
    if (password.match(/[a-z]/)) strength++;
    if (password.match(/[A-Z]/)) strength++;
    if (password.match(/[0-9]/)) strength++;
    if (password.match(/[^a-zA-Z0-9]/)) strength++;
    
    strengthBar.className = 'password-strength';
    if (strength >= 3) {
        strengthBar.classList.add('strong');
    } else if (strength >= 2) {
        strengthBar.classList.add('medium');
    } else if (strength >= 1) {
        strengthBar.classList.add('weak');
    }
});

// Password match checker
document.getElementById('confirm_password').addEventListener('input', function() {
    const password = document.getElementById('password').value;
    const confirmPassword = this.value;
    const matchIndicator = document.getElementById('password-match');
    
    if (confirmPassword.length > 0) {
        if (password === confirmPassword) {
            matchIndicator.textContent = '✓ Passwords match';
            matchIndicator.className = 'password-match match';
        } else {
            matchIndicator.textContent = '✗ Passwords do not match';
            matchIndicator.className = 'password-match no-match';
        }
    } else {
        matchIndicator.textContent = '';
        matchIndicator.className = 'password-match';
    }
});

// Form validation
document.querySelector('.registration-form').addEventListener('submit', function(e) {
    const password = document.getElementById('password').value;
    const confirmPassword = document.getElementById('confirm_password').value;
    const termsAccepted = document.querySelector('input[name="terms_accepted"]').checked;
    
    if (password !== confirmPassword) {
        e.preventDefault();
        alert('Passwords do not match.');
        return false;
    }
    
    if (!termsAccepted) {
        e.preventDefault();
        alert('You must accept the Terms and Conditions to create an account.');
        return false;
    }
    
    // Show loading state
    const submitBtn = this.querySelector('button[type="submit"]');
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Creating Account...';
    submitBtn.disabled = true;
});
</script>

<?php require_once 'includes/footer.php'; ?>
