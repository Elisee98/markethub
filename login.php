<?php
/**
 * MarketHub User Login
 * Multi-Vendor E-Commerce Platform
 */

require_once 'config/config.php';

$page_title = 'Login';
$error_message = '';
$success_message = '';

// Redirect if already logged in
if (isLoggedIn()) {
    if (isset($_GET['redirect'])) {
        $redirect_url = $_GET['redirect'];
    } else {
        // Determine redirect based on user type
        switch ($_SESSION['user_type']) {
            case 'admin':
                $redirect_url = 'admin/spa-dashboard.php';
                break;
            case 'vendor':
                $redirect_url = 'vendor/spa-dashboard.php';
                break;
            default:
                $redirect_url = 'dashboard.php';
                break;
        }
    }
    redirect($redirect_url);
}

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error_message = 'Invalid security token. Please try again.';
    } else {
        $username_or_email = sanitizeInput($_POST['username_or_email'] ?? '');
        $password = $_POST['password'] ?? '';
        $remember_me = isset($_POST['remember_me']);
        
        try {
            $user = loginUser($username_or_email, $password, $remember_me);
            
            // Redirect based on user type
            $redirect_url = isset($_GET['redirect']) ? $_GET['redirect'] : '';
            
            if (empty($redirect_url)) {
                switch ($user['user_type']) {
                    case 'admin':
                        $redirect_url = 'admin/spa-dashboard.php';
                        break;
                    case 'vendor':
                        $redirect_url = 'vendor/spa-dashboard.php';
                        break;
                    default:
                        $redirect_url = 'dashboard.php';
                        break;
                }
            }
            
            redirect($redirect_url);
            
        } catch (Exception $e) {
            $error_message = $e->getMessage();
        }
    }
}

require_once 'includes/header.php';
?>

<div class="container" style="max-width: 500px; margin: 4rem auto; padding: 2rem;">
    <div class="card">
        <div class="card-header text-center">
            <h2 style="color: var(--primary-green); margin-bottom: 0.5rem;">Welcome Back</h2>
            <p style="color: var(--dark-gray); margin-bottom: 0;">Sign in to your MarketHub account</p>
        </div>
        
        <div class="card-body">
            <?php if ($error_message): ?>
                <div class="alert alert-error">
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($success_message): ?>
                <div class="alert alert-success">
                    <?php echo htmlspecialchars($success_message); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                
                <div class="form-group">
                    <label for="username_or_email" class="form-label">Username or Email</label>
                    <input type="text" 
                           id="username_or_email" 
                           name="username_or_email" 
                           class="form-control" 
                           placeholder="Enter your username or email"
                           value="<?php echo htmlspecialchars($_POST['username_or_email'] ?? ''); ?>"
                           required>
                </div>
                
                <div class="form-group">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" 
                           id="password" 
                           name="password" 
                           class="form-control" 
                           placeholder="Enter your password"
                           required>
                </div>
                
                <div class="form-group">
                    <label style="display: flex; align-items: center; cursor: pointer;">
                        <input type="checkbox" name="remember_me" style="margin-right: 0.5rem;">
                        Remember me for 30 days
                    </label>
                </div>
                
                <button type="submit" class="btn btn-primary" style="width: 100%; margin-bottom: 1rem;">
                    Sign In
                </button>
                
                <div class="text-center">
                    <a href="forgot-password.php" style="color: var(--primary-green);">Forgot your password?</a>
                </div>
            </form>
        </div>
        
        <div class="card-footer text-center">
            <p style="margin-bottom: 0;">Don't have an account? 
                <a href="register.php" style="color: var(--primary-green); font-weight: 600;">Sign up here</a>
            </p>
        </div>
    </div>
    
    <!-- Social Login Options -->
    <div class="card" style="margin-top: 2rem;">
        <div class="card-body text-center">
            <p style="margin-bottom: 1rem; color: var(--dark-gray);">Or continue with</p>
            <div style="display: flex; gap: 1rem; justify-content: center;">
                <button class="btn btn-outline" style="flex: 1;">
                    <i class="fab fa-google" style="margin-right: 0.5rem;"></i>
                    Google
                </button>
                <button class="btn btn-outline" style="flex: 1;">
                    <i class="fab fa-facebook" style="margin-right: 0.5rem;"></i>
                    Facebook
                </button>
            </div>
        </div>
    </div>
    
    <!-- Quick Access for Different User Types -->
    <div class="card" style="margin-top: 2rem;">
        <div class="card-header text-center">
            <h5 style="margin-bottom: 0;">Quick Access</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-4 text-center">
                    <div style="padding: 1rem; border: 2px solid var(--light-gray); border-radius: var(--border-radius); margin-bottom: 1rem;">
                        <i class="fas fa-shopping-cart" style="font-size: 2rem; color: var(--primary-green); margin-bottom: 0.5rem;"></i>
                        <h6>Customer</h6>
                        <p style="font-size: 0.9rem; color: var(--dark-gray); margin-bottom: 1rem;">Shop from multiple vendors</p>
                        <a href="register.php?type=customer" class="btn btn-outline btn-sm">Register</a>
                    </div>
                </div>
                <div class="col-4 text-center">
                    <div style="padding: 1rem; border: 2px solid var(--light-gray); border-radius: var(--border-radius); margin-bottom: 1rem;">
                        <i class="fas fa-store" style="font-size: 2rem; color: var(--primary-green); margin-bottom: 0.5rem;"></i>
                        <h6>Vendor</h6>
                        <p style="font-size: 0.9rem; color: var(--dark-gray); margin-bottom: 1rem;">Start selling your products</p>
                        <a href="vendor/register.php" class="btn btn-outline btn-sm">Register</a>
                    </div>
                </div>
                <div class="col-4 text-center">
                    <div style="padding: 1rem; border: 2px solid var(--light-gray); border-radius: var(--border-radius); margin-bottom: 1rem;">
                        <i class="fas fa-cog" style="font-size: 2rem; color: var(--primary-green); margin-bottom: 0.5rem;"></i>
                        <h6>Admin</h6>
                        <p style="font-size: 0.9rem; color: var(--dark-gray); margin-bottom: 1rem;">Platform management</p>
                        <a href="admin/login.php" class="btn btn-outline btn-sm">Admin Login</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.card {
    box-shadow: 0 4px 20px rgba(0,0,0,0.1);
    border: none;
}

.form-control:focus {
    border-color: var(--primary-green);
    box-shadow: 0 0 0 3px rgba(46, 125, 50, 0.1);
}

.btn:hover {
    transform: translateY(-2px);
}

.badge {
    position: absolute;
    top: -8px;
    right: -8px;
    min-width: 20px;
    height: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.75rem;
}

@media (max-width: 768px) {
    .container {
        margin: 2rem auto;
        padding: 1rem;
    }
    
    .col-4 {
        flex: 0 0 100%;
        margin-bottom: 1rem;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Add loading state to form submission
    const form = document.querySelector('form');
    const submitBtn = document.querySelector('button[type="submit"]');
    
    form.addEventListener('submit', function() {
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Signing In...';
        submitBtn.disabled = true;
    });
    
    // Auto-focus first input
    document.getElementById('username_or_email').focus();
    
    // Show/hide password functionality
    const passwordInput = document.getElementById('password');
    const togglePassword = document.createElement('button');
    togglePassword.type = 'button';
    togglePassword.innerHTML = '<i class="fas fa-eye"></i>';
    togglePassword.style.cssText = 'position: absolute; right: 10px; top: 50%; transform: translateY(-50%); border: none; background: none; color: var(--dark-gray); cursor: pointer;';
    
    passwordInput.parentElement.style.position = 'relative';
    passwordInput.parentElement.appendChild(togglePassword);
    
    togglePassword.addEventListener('click', function() {
        const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
        passwordInput.setAttribute('type', type);
        this.innerHTML = type === 'password' ? '<i class="fas fa-eye"></i>' : '<i class="fas fa-eye-slash"></i>';
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>
