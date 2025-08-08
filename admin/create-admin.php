<?php
/**
 * MarketHub Admin Account Creator
 * Create the first admin account for the system
 */

require_once '../config/config.php';

$page_title = 'Create Admin Account';
$success_message = '';
$error_message = '';

// Check if admin already exists
$existing_admin = $database->fetch("SELECT id FROM users WHERE user_type = 'admin' LIMIT 1");

if ($existing_admin && !isset($_GET['force'])) {
    $error_message = 'An admin account already exists. If you need to create another admin, add ?force=1 to the URL.';
}

// Handle admin creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$existing_admin || isset($_GET['force'])) {
    try {
        $username = sanitizeInput($_POST['username'] ?? '');
        $email = sanitizeInput($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        $first_name = sanitizeInput($_POST['first_name'] ?? '');
        $last_name = sanitizeInput($_POST['last_name'] ?? '');
        
        // Validation
        if (empty($username) || empty($email) || empty($password) || empty($first_name) || empty($last_name)) {
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
        
        // Check if username or email already exists
        $existing_user = $database->fetch(
            "SELECT id FROM users WHERE email = ? OR username = ?", 
            [$email, $username]
        );
        
        if ($existing_user) {
            throw new Exception('A user with this email or username already exists.');
        }
        
        // Create admin account
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        
        $sql = "INSERT INTO users (username, email, password_hash, first_name, last_name, 
                                 user_type, status, created_at) 
                VALUES (?, ?, ?, ?, ?, 'admin', 'active', NOW())";
        
        $admin_id = $database->insert($sql, [
            $username, $email, $password_hash, $first_name, $last_name
        ]);
        
        // Log activity
        logActivity($admin_id, 'admin_created', 'Admin account created');
        
        $success_message = 'Admin account created successfully! You can now login with your credentials.';
        
    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - MarketHub</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .admin-setup-container {
            max-width: 500px;
            margin: 3rem auto;
            padding: 2rem;
        }
        
        .admin-header {
            background: linear-gradient(135deg, #2E7D32, #4CAF50);
            color: white;
            padding: 2rem;
            border-radius: 8px;
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .setup-card {
            background: white;
            border-radius: 8px;
            padding: 2rem;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: var(--dark-gray);
        }
        
        .form-control {
            width: 100%;
            padding: 12px;
            border: 2px solid var(--light-gray);
            border-radius: 6px;
            font-size: 1rem;
            transition: border-color 0.3s;
        }
        
        .form-control:focus {
            outline: none;
            border-color: var(--primary-green);
            box-shadow: 0 0 0 3px rgba(46, 125, 50, 0.1);
        }
        
        .btn {
            width: 100%;
            padding: 12px;
            background: var(--primary-green);
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.3s;
        }
        
        .btn:hover {
            background: var(--secondary-green);
        }
        
        .alert {
            padding: 1rem;
            border-radius: 6px;
            margin-bottom: 1rem;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .alert-warning {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }
        
        .info-box {
            background: #e3f2fd;
            color: #0d47a1;
            padding: 1rem;
            border-radius: 6px;
            margin-bottom: 2rem;
        }
        
        .quick-links {
            text-align: center;
            margin-top: 2rem;
        }
        
        .quick-links a {
            display: inline-block;
            margin: 0.5rem;
            padding: 8px 16px;
            background: #f8f9fa;
            color: var(--dark-gray);
            text-decoration: none;
            border-radius: 5px;
            transition: background 0.3s;
        }
        
        .quick-links a:hover {
            background: #e9ecef;
        }
    </style>
</head>
<body>
    <div class="admin-setup-container">
        <div class="admin-header">
            <h1><i class="fas fa-user-shield"></i> Create Admin Account</h1>
            <p>Set up the first administrator account for MarketHub</p>
        </div>
        
        <div class="setup-card">
            <?php if ($success_message): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?php echo htmlspecialchars($success_message); ?>
                </div>
                
                <div class="quick-links">
                    <a href="../login.php">
                        <i class="fas fa-sign-in-alt"></i> Login as Admin
                    </a>
                    <a href="user-management.php">
                        <i class="fas fa-users-cog"></i> User Management
                    </a>
                    <a href="../index.php">
                        <i class="fas fa-home"></i> Homepage
                    </a>
                </div>
                
            <?php elseif ($existing_admin && !isset($_GET['force'])): ?>
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle"></i>
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
                
                <div class="info-box">
                    <h4>Admin Account Already Exists</h4>
                    <p>An administrator account has already been created for this MarketHub installation.</p>
                    <p>If you need to:</p>
                    <ul>
                        <li><strong>Login:</strong> Use the existing admin credentials</li>
                        <li><strong>Reset password:</strong> Contact your system administrator</li>
                        <li><strong>Create another admin:</strong> <a href="?force=1">Click here</a> (not recommended)</li>
                    </ul>
                </div>
                
                <div class="quick-links">
                    <a href="../login.php">
                        <i class="fas fa-sign-in-alt"></i> Login
                    </a>
                    <a href="user-management.php">
                        <i class="fas fa-users-cog"></i> User Management
                    </a>
                    <a href="../index.php">
                        <i class="fas fa-home"></i> Homepage
                    </a>
                </div>
                
            <?php else: ?>
                <?php if ($error_message): ?>
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-circle"></i>
                        <?php echo htmlspecialchars($error_message); ?>
                    </div>
                <?php endif; ?>
                
                <div class="info-box">
                    <h4>Administrator Setup</h4>
                    <p>Create the first administrator account to manage MarketHub. This account will have full access to:</p>
                    <ul>
                        <li>User management and approvals</li>
                        <li>Vendor application reviews</li>
                        <li>System configuration</li>
                        <li>Reports and analytics</li>
                    </ul>
                </div>
                
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="first_name" class="form-label">First Name *</label>
                        <input type="text" id="first_name" name="first_name" class="form-control" 
                               value="<?php echo htmlspecialchars($_POST['first_name'] ?? ''); ?>" 
                               placeholder="Enter first name" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="last_name" class="form-label">Last Name *</label>
                        <input type="text" id="last_name" name="last_name" class="form-control" 
                               value="<?php echo htmlspecialchars($_POST['last_name'] ?? ''); ?>" 
                               placeholder="Enter last name" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="username" class="form-label">Username *</label>
                        <input type="text" id="username" name="username" class="form-control" 
                               value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" 
                               placeholder="Choose a username" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="email" class="form-label">Email Address *</label>
                        <input type="email" id="email" name="email" class="form-control" 
                               value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" 
                               placeholder="Enter email address" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="password" class="form-label">Password *</label>
                        <input type="password" id="password" name="password" class="form-control" 
                               placeholder="Create a strong password" required>
                        <small style="color: var(--medium-gray); font-size: 0.85rem;">
                            Minimum 6 characters, include letters and numbers
                        </small>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password" class="form-label">Confirm Password *</label>
                        <input type="password" id="confirm_password" name="confirm_password" class="form-control" 
                               placeholder="Confirm your password" required>
                    </div>
                    
                    <button type="submit" class="btn">
                        <i class="fas fa-user-plus"></i> Create Admin Account
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Password confirmation validation
        document.getElementById('confirm_password').addEventListener('input', function() {
            const password = document.getElementById('password').value;
            const confirmPassword = this.value;
            
            if (password !== confirmPassword) {
                this.setCustomValidity('Passwords do not match');
            } else {
                this.setCustomValidity('');
            }
        });
        
        // Form submission
        document.querySelector('form').addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            if (password !== confirmPassword) {
                e.preventDefault();
                alert('Passwords do not match.');
                return false;
            }
            
            if (password.length < 6) {
                e.preventDefault();
                alert('Password must be at least 6 characters long.');
                return false;
            }
            
            // Show loading state
            const submitBtn = this.querySelector('button[type="submit"]');
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Creating Account...';
            submitBtn.disabled = true;
        });
    </script>
</body>
</html>
