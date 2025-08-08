<?php
/**
 * MarketHub Customer Profile Management
 * Multi-Vendor E-Commerce Platform
 */

require_once 'config/config.php';

$page_title = 'My Profile';

// Require login
requireLogin();

$customer_id = $_SESSION['user_id'];
$success_message = '';
$error_message = '';

// Get customer information
$customer = $database->fetch("SELECT * FROM users WHERE id = ?", [$customer_id]);

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error_message = 'Invalid security token. Please try again.';
    } else {
        $action = $_POST['action'] ?? '';
        
        try {
            switch ($action) {
                case 'update_profile':
                    $first_name = sanitizeInput($_POST['first_name'] ?? '');
                    $last_name = sanitizeInput($_POST['last_name'] ?? '');
                    $email = sanitizeInput($_POST['email'] ?? '');
                    $phone = sanitizeInput($_POST['phone'] ?? '');
                    $date_of_birth = sanitizeInput($_POST['date_of_birth'] ?? '');
                    $gender = sanitizeInput($_POST['gender'] ?? '');
                    
                    // Validate required fields
                    if (empty($first_name) || empty($last_name) || empty($email)) {
                        throw new Exception('First name, last name, and email are required.');
                    }
                    
                    // Validate email format
                    if (!validateEmailFormat($email)) {
                        throw new Exception('Invalid email format.');
                    }
                    
                    // Check if email is already taken by another user
                    $email_check = $database->fetch(
                        "SELECT id FROM users WHERE email = ? AND id != ?", 
                        [$email, $customer_id]
                    );
                    
                    if ($email_check) {
                        throw new Exception('Email address is already in use.');
                    }
                    
                    // Validate phone if provided
                    if (!empty($phone) && !validatePhone($phone)) {
                        throw new Exception('Invalid phone number format.');
                    }
                    
                    // Update profile
                    $sql = "UPDATE users SET 
                            first_name = ?, last_name = ?, email = ?, phone = ?, 
                            date_of_birth = ?, gender = ?, updated_at = NOW() 
                            WHERE id = ?";
                    
                    $database->execute($sql, [
                        $first_name, $last_name, $email, $phone, 
                        $date_of_birth ?: null, $gender ?: null, $customer_id
                    ]);
                    
                    // Update session data
                    $_SESSION['user_name'] = $first_name . ' ' . $last_name;
                    $_SESSION['user_email'] = $email;
                    
                    // Log activity
                    logActivity($customer_id, 'profile_update', 'Profile information updated');
                    
                    $success_message = 'Profile updated successfully!';
                    
                    // Refresh customer data
                    $customer = $database->fetch("SELECT * FROM users WHERE id = ?", [$customer_id]);
                    break;
                    
                case 'change_password':
                    $current_password = $_POST['current_password'] ?? '';
                    $new_password = $_POST['new_password'] ?? '';
                    $confirm_password = $_POST['confirm_password'] ?? '';
                    
                    // Validate current password
                    if (!password_verify($current_password, $customer['password_hash'])) {
                        throw new Exception('Current password is incorrect.');
                    }
                    
                    // Validate new password
                    if (strlen($new_password) < 6) {
                        throw new Exception('New password must be at least 6 characters long.');
                    }
                    
                    if ($new_password !== $confirm_password) {
                        throw new Exception('New passwords do not match.');
                    }
                    
                    // Update password
                    $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
                    $sql = "UPDATE users SET password_hash = ?, updated_at = NOW() WHERE id = ?";
                    $database->execute($sql, [$password_hash, $customer_id]);
                    
                    // Log activity
                    logActivity($customer_id, 'password_change', 'Password changed');
                    
                    $success_message = 'Password changed successfully!';
                    break;
                    
                default:
                    throw new Exception('Invalid action.');
            }
            
        } catch (Exception $e) {
            $error_message = $e->getMessage();
        }
    }
}

require_once 'includes/header.php';
?>

<div class="container" style="max-width: 800px; margin: 2rem auto; padding: 2rem;">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1>My Profile</h1>
            <p class="text-muted">Manage your account information and preferences</p>
        </div>
        <a href="dashboard.php" class="btn btn-outline">
            <i class="fas fa-arrow-left"></i> Back to Dashboard
        </a>
    </div>

    <!-- Success/Error Messages -->
    <?php if ($success_message): ?>
        <div class="alert alert-success">
            <?php echo htmlspecialchars($success_message); ?>
        </div>
    <?php endif; ?>
    
    <?php if ($error_message): ?>
        <div class="alert alert-error">
            <?php echo htmlspecialchars($error_message); ?>
        </div>
    <?php endif; ?>

    <!-- Profile Tabs -->
    <div class="profile-tabs">
        <button class="tab-btn active" onclick="showTab('profile-info')">
            <i class="fas fa-user"></i> Profile Information
        </button>
        <button class="tab-btn" onclick="showTab('security')">
            <i class="fas fa-lock"></i> Security
        </button>
        <button class="tab-btn" onclick="showTab('preferences')">
            <i class="fas fa-cog"></i> Preferences
        </button>
    </div>

    <!-- Profile Information Tab -->
    <div id="profile-info" class="tab-content active">
        <div class="card">
            <div class="card-header">
                <h5>Profile Information</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    <input type="hidden" name="action" value="update_profile">
                    
                    <!-- Profile Picture Section -->
                    <div style="text-align: center; margin-bottom: 2rem;">
                        <div style="width: 120px; height: 120px; background: var(--primary-green); border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; color: white; font-size: 3rem; font-weight: bold; margin-bottom: 1rem;">
                            <?php echo strtoupper(substr($customer['first_name'], 0, 1) . substr($customer['last_name'], 0, 1)); ?>
                        </div>
                        <div>
                            <h4><?php echo htmlspecialchars($customer['first_name'] . ' ' . $customer['last_name']); ?></h4>
                            <p class="text-muted">Member since <?php echo date('F Y', strtotime($customer['created_at'])); ?></p>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-6">
                            <div class="form-group">
                                <label for="first_name" class="form-label">First Name *</label>
                                <input type="text" id="first_name" name="first_name" class="form-control" 
                                       value="<?php echo htmlspecialchars($customer['first_name']); ?>" required>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-group">
                                <label for="last_name" class="form-label">Last Name *</label>
                                <input type="text" id="last_name" name="last_name" class="form-control" 
                                       value="<?php echo htmlspecialchars($customer['last_name']); ?>" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="email" class="form-label">Email Address *</label>
                        <input type="email" id="email" name="email" class="form-control" 
                               value="<?php echo htmlspecialchars($customer['email']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="phone" class="form-label">Phone Number</label>
                        <input type="tel" id="phone" name="phone" class="form-control" 
                               placeholder="+250 788 123 456" 
                               value="<?php echo htmlspecialchars($customer['phone'] ?? ''); ?>">
                    </div>
                    
                    <div class="row">
                        <div class="col-6">
                            <div class="form-group">
                                <label for="date_of_birth" class="form-label">Date of Birth</label>
                                <input type="date" id="date_of_birth" name="date_of_birth" class="form-control" 
                                       value="<?php echo htmlspecialchars($customer['date_of_birth'] ?? ''); ?>">
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-group">
                                <label for="gender" class="form-label">Gender</label>
                                <select id="gender" name="gender" class="form-control form-select">
                                    <option value="">Select Gender</option>
                                    <option value="male" <?php echo ($customer['gender'] ?? '') === 'male' ? 'selected' : ''; ?>>Male</option>
                                    <option value="female" <?php echo ($customer['gender'] ?? '') === 'female' ? 'selected' : ''; ?>>Female</option>
                                    <option value="other" <?php echo ($customer['gender'] ?? '') === 'other' ? 'selected' : ''; ?>>Other</option>
                                    <option value="prefer_not_to_say" <?php echo ($customer['gender'] ?? '') === 'prefer_not_to_say' ? 'selected' : ''; ?>>Prefer not to say</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Update Profile
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Security Tab -->
    <div id="security" class="tab-content">
        <div class="card">
            <div class="card-header">
                <h5>Security Settings</h5>
            </div>
            <div class="card-body">
                <!-- Change Password -->
                <h6 style="color: var(--primary-green); margin-bottom: 1rem;">Change Password</h6>
                <form method="POST" action="">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    <input type="hidden" name="action" value="change_password">
                    
                    <div class="form-group">
                        <label for="current_password" class="form-label">Current Password</label>
                        <input type="password" id="current_password" name="current_password" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="new_password" class="form-label">New Password</label>
                        <input type="password" id="new_password" name="new_password" class="form-control" required>
                        <small class="text-muted">Minimum 6 characters</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password" class="form-label">Confirm New Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" class="form-control" required>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-key"></i> Change Password
                    </button>
                </form>
                
                <!-- Account Security Info -->
                <div style="margin-top: 2rem; padding: 1rem; background: var(--light-gray); border-radius: var(--border-radius);">
                    <h6>Account Security</h6>
                    <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                        <span>Last login:</span>
                        <strong><?php echo $customer['last_login'] ? date('M j, Y g:i A', strtotime($customer['last_login'])) : 'Never'; ?></strong>
                    </div>
                    <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                        <span>Account created:</span>
                        <strong><?php echo date('M j, Y', strtotime($customer['created_at'])); ?></strong>
                    </div>
                    <div style="display: flex; justify-content: space-between;">
                        <span>Account status:</span>
                        <span style="color: var(--secondary-green); font-weight: 600;">Active</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Preferences Tab -->
    <div id="preferences" class="tab-content">
        <div class="card">
            <div class="card-header">
                <h5>Notification Preferences</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    <input type="hidden" name="action" value="update_preferences">
                    
                    <div class="form-group">
                        <h6 style="color: var(--primary-green); margin-bottom: 1rem;">Email Notifications</h6>
                        
                        <label style="display: flex; align-items: center; cursor: pointer; margin-bottom: 0.75rem;">
                            <input type="checkbox" name="email_notifications" <?php echo ($preferences['email_notifications'] ?? 1) ? 'checked' : ''; ?> style="margin-right: 0.5rem;">
                            <div>
                                <strong>Order Updates</strong>
                                <br>
                                <small class="text-muted">Receive emails about order status, shipping, and delivery</small>
                            </div>
                        </label>
                        
                        <label style="display: flex; align-items: center; cursor: pointer; margin-bottom: 0.75rem;">
                            <input type="checkbox" name="newsletter" <?php echo ($preferences['newsletter'] ?? 1) ? 'checked' : ''; ?> style="margin-right: 0.5rem;">
                            <div>
                                <strong>Newsletter</strong>
                                <br>
                                <small class="text-muted">Weekly newsletter with new products and deals</small>
                            </div>
                        </label>
                        
                        <label style="display: flex; align-items: center; cursor: pointer; margin-bottom: 1rem;">
                            <input type="checkbox" name="marketing_emails" <?php echo ($preferences['marketing_emails'] ?? 0) ? 'checked' : ''; ?> style="margin-right: 0.5rem;">
                            <div>
                                <strong>Marketing Emails</strong>
                                <br>
                                <small class="text-muted">Promotional emails and special offers</small>
                            </div>
                        </label>
                    </div>
                    
                    <div class="form-group">
                        <h6 style="color: var(--primary-green); margin-bottom: 1rem;">SMS Notifications</h6>
                        
                        <label style="display: flex; align-items: center; cursor: pointer; margin-bottom: 1rem;">
                            <input type="checkbox" name="sms_notifications" <?php echo ($preferences['sms_notifications'] ?? 0) ? 'checked' : ''; ?> style="margin-right: 0.5rem;">
                            <div>
                                <strong>SMS Updates</strong>
                                <br>
                                <small class="text-muted">Receive SMS for important order updates</small>
                            </div>
                        </label>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Save Preferences
                    </button>
                </form>
            </div>
        </div>
        
        <!-- Account Actions -->
        <div class="card mt-3">
            <div class="card-header">
                <h5>Account Actions</h5>
            </div>
            <div class="card-body">
                <div style="display: flex; gap: 1rem;">
                    <a href="export-data.php" class="btn btn-outline">
                        <i class="fas fa-download"></i> Export My Data
                    </a>
                    <button onclick="deactivateAccount()" class="btn btn-outline" style="color: #F44336; border-color: #F44336;">
                        <i class="fas fa-user-times"></i> Deactivate Account
                    </button>
                </div>
                <small class="text-muted" style="display: block; margin-top: 0.5rem;">
                    Account deactivation will disable your account but preserve your data. You can reactivate anytime.
                </small>
            </div>
        </div>
    </div>
</div>

<style>
.profile-tabs {
    display: flex;
    border-bottom: 2px solid var(--light-gray);
    margin-bottom: 2rem;
}

.tab-btn {
    background: none;
    border: none;
    padding: 1rem 1.5rem;
    cursor: pointer;
    border-bottom: 3px solid transparent;
    transition: all 0.3s ease;
    color: var(--dark-gray);
    font-weight: 500;
}

.tab-btn:hover {
    color: var(--primary-green);
    background: var(--light-gray);
}

.tab-btn.active {
    color: var(--primary-green);
    border-bottom-color: var(--primary-green);
}

.tab-content {
    display: none;
}

.tab-content.active {
    display: block;
}

.form-control:focus {
    border-color: var(--primary-green);
    box-shadow: 0 0 0 3px rgba(46, 125, 50, 0.1);
}

@media (max-width: 768px) {
    .col-6 {
        flex: 0 0 100%;
        margin-bottom: 1rem;
    }
    
    .profile-tabs {
        flex-direction: column;
    }
    
    .tab-btn {
        text-align: left;
        border-bottom: 1px solid var(--light-gray);
        border-right: 3px solid transparent;
    }
    
    .tab-btn.active {
        border-bottom-color: var(--light-gray);
        border-right-color: var(--primary-green);
    }
}
</style>

<script>
function showTab(tabId) {
    // Hide all tab contents
    document.querySelectorAll('.tab-content').forEach(tab => {
        tab.classList.remove('active');
    });
    
    // Remove active class from all tab buttons
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.classList.remove('active');
    });
    
    // Show selected tab content
    document.getElementById(tabId).classList.add('active');
    
    // Add active class to clicked button
    event.target.classList.add('active');
}

// Password confirmation validation
document.addEventListener('DOMContentLoaded', function() {
    const newPassword = document.getElementById('new_password');
    const confirmPassword = document.getElementById('confirm_password');
    
    function validatePassword() {
        if (newPassword.value !== confirmPassword.value) {
            confirmPassword.setCustomValidity('Passwords do not match');
        } else {
            confirmPassword.setCustomValidity('');
        }
    }
    
    if (newPassword && confirmPassword) {
        newPassword.addEventListener('change', validatePassword);
        confirmPassword.addEventListener('keyup', validatePassword);
    }
});

function deactivateAccount() {
    if (confirm('Are you sure you want to deactivate your account? You can reactivate it anytime by logging in.')) {
        // Implement account deactivation
        alert('Account deactivation feature will be implemented.');
    }
}
</script>

<?php require_once 'includes/footer.php'; ?>
