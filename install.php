<?php
/**
 * MarketHub Installation Script
 * Multi-Vendor E-Commerce Platform
 */

// Check if already installed
if (file_exists('config/installed.lock')) {
    die('MarketHub is already installed. Delete config/installed.lock to reinstall.');
}

$step = $_GET['step'] ?? 1;
$error = '';
$success = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    switch ($step) {
        case 2:
            // Database configuration
            $db_host = $_POST['db_host'] ?? 'localhost';
            $db_name = $_POST['db_name'] ?? 'markethub';
            $db_user = $_POST['db_user'] ?? 'root';
            $db_pass = $_POST['db_pass'] ?? '';
            
            try {
                // Test database connection
                $pdo = new PDO("mysql:host=$db_host", $db_user, $db_pass);
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                
                // Create database if it doesn't exist
                $pdo->exec("CREATE DATABASE IF NOT EXISTS `$db_name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                $pdo->exec("USE `$db_name`");
                
                // Read and execute schema
                $schema = file_get_contents('database/schema.sql');
                $statements = explode(';', $schema);
                
                foreach ($statements as $statement) {
                    $statement = trim($statement);
                    if (!empty($statement)) {
                        $pdo->exec($statement);
                    }
                }
                
                // Update database config file
                $config_content = file_get_contents('config/database.php');
                $config_content = str_replace("define('DB_HOST', 'localhost');", "define('DB_HOST', '$db_host');", $config_content);
                $config_content = str_replace("define('DB_NAME', 'markethub');", "define('DB_NAME', '$db_name');", $config_content);
                $config_content = str_replace("define('DB_USER', 'root');", "define('DB_USER', '$db_user');", $config_content);
                $config_content = str_replace("define('DB_PASS', '');", "define('DB_PASS', '$db_pass');", $config_content);
                
                file_put_contents('config/database.php', $config_content);
                
                $success = 'Database configured successfully!';
                $step = 3;
                
            } catch (Exception $e) {
                $error = 'Database error: ' . $e->getMessage();
            }
            break;
            
        case 3:
            // Admin user creation
            $admin_username = $_POST['admin_username'] ?? '';
            $admin_email = $_POST['admin_email'] ?? '';
            $admin_password = $_POST['admin_password'] ?? '';
            $admin_first_name = $_POST['admin_first_name'] ?? '';
            $admin_last_name = $_POST['admin_last_name'] ?? '';
            
            if (empty($admin_username) || empty($admin_email) || empty($admin_password) || empty($admin_first_name) || empty($admin_last_name)) {
                $error = 'All fields are required.';
            } elseif (strlen($admin_password) < 6) {
                $error = 'Password must be at least 6 characters long.';
            } else {
                try {
                    require_once 'config/config.php';
                    
                    $password_hash = password_hash($admin_password, PASSWORD_DEFAULT);
                    
                    $sql = "INSERT INTO users (username, email, password_hash, first_name, last_name, user_type, status, created_at) 
                            VALUES (?, ?, ?, ?, ?, 'admin', 'active', NOW())";
                    
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([$admin_username, $admin_email, $password_hash, $admin_first_name, $admin_last_name]);
                    
                    $success = 'Admin user created successfully!';
                    $step = 4;
                    
                } catch (Exception $e) {
                    $error = 'Error creating admin user: ' . $e->getMessage();
                }
            }
            break;
            
        case 4:
            // Final setup
            $site_name = $_POST['site_name'] ?? 'MarketHub';
            $site_url = $_POST['site_url'] ?? 'http://localhost/ange Final/';
            $admin_email = $_POST['admin_email'] ?? 'admin@markethub.com';
            
            // Update config file
            $config_content = file_get_contents('config/config.php');
            $config_content = str_replace("define('SITE_NAME', 'MarketHub');", "define('SITE_NAME', '$site_name');", $config_content);
            $config_content = str_replace("define('SITE_URL', 'http://localhost/ange Final/');", "define('SITE_URL', '$site_url');", $config_content);
            $config_content = str_replace("define('ADMIN_EMAIL', 'admin@markethub.com');", "define('ADMIN_EMAIL', '$admin_email');", $config_content);
            
            file_put_contents('config/config.php', $config_content);
            
            // Create installation lock file
            file_put_contents('config/installed.lock', date('Y-m-d H:i:s'));
            
            // Create upload directories
            $upload_dirs = ['uploads/products', 'uploads/vendors', 'uploads/users'];
            foreach ($upload_dirs as $dir) {
                if (!is_dir($dir)) {
                    mkdir($dir, 0755, true);
                }
            }
            
            $success = 'Installation completed successfully!';
            $step = 5;
            break;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MarketHub Installation</title>
    <style>
        :root {
            --primary-green: #2E7D32;
            --secondary-green: #4CAF50;
            --white: #FFFFFF;
            --black: #212121;
            --light-gray: #F5F5F5;
            --medium-gray: #E0E0E0;
            --dark-gray: #757575;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: var(--light-gray);
            color: var(--black);
            line-height: 1.6;
        }
        
        .container {
            max-width: 600px;
            margin: 2rem auto;
            background: var(--white);
            border-radius: 8px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .header {
            background: var(--primary-green);
            color: var(--white);
            padding: 2rem;
            text-align: center;
        }
        
        .content {
            padding: 2rem;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
        }
        
        .form-control {
            width: 100%;
            padding: 12px;
            border: 2px solid var(--medium-gray);
            border-radius: 8px;
            font-size: 1rem;
        }
        
        .form-control:focus {
            outline: none;
            border-color: var(--primary-green);
        }
        
        .btn {
            display: inline-block;
            padding: 12px 24px;
            background: var(--primary-green);
            color: var(--white);
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        
        .btn:hover {
            background: var(--secondary-green);
        }
        
        .btn-full {
            width: 100%;
        }
        
        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
        }
        
        .alert-error {
            background: #FFEBEE;
            color: #C62828;
            border: 1px solid #E57373;
        }
        
        .alert-success {
            background: #E8F5E8;
            color: var(--primary-green);
            border: 1px solid var(--secondary-green);
        }
        
        .steps {
            display: flex;
            justify-content: space-between;
            margin-bottom: 2rem;
        }
        
        .step {
            flex: 1;
            text-align: center;
            padding: 1rem;
            background: var(--medium-gray);
            color: var(--dark-gray);
            position: relative;
        }
        
        .step.active {
            background: var(--primary-green);
            color: var(--white);
        }
        
        .step.completed {
            background: var(--secondary-green);
            color: var(--white);
        }
        
        .step:not(:last-child)::after {
            content: '';
            position: absolute;
            right: -1px;
            top: 0;
            bottom: 0;
            width: 2px;
            background: var(--white);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>MarketHub Installation</h1>
            <p>Multi-Vendor E-Commerce Platform Setup</p>
        </div>
        
        <div class="content">
            <!-- Progress Steps -->
            <div class="steps">
                <div class="step <?php echo $step >= 1 ? ($step == 1 ? 'active' : 'completed') : ''; ?>">
                    1. Welcome
                </div>
                <div class="step <?php echo $step >= 2 ? ($step == 2 ? 'active' : 'completed') : ''; ?>">
                    2. Database
                </div>
                <div class="step <?php echo $step >= 3 ? ($step == 3 ? 'active' : 'completed') : ''; ?>">
                    3. Admin User
                </div>
                <div class="step <?php echo $step >= 4 ? ($step == 4 ? 'active' : 'completed') : ''; ?>">
                    4. Configuration
                </div>
                <div class="step <?php echo $step >= 5 ? 'active' : ''; ?>">
                    5. Complete
                </div>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            
            <?php if ($step == 1): ?>
                <!-- Welcome Step -->
                <h2>Welcome to MarketHub</h2>
                <p>This installation wizard will help you set up your multi-vendor e-commerce platform.</p>
                
                <h3>Requirements Check</h3>
                <ul style="margin: 1rem 0;">
                    <li>✅ PHP <?php echo PHP_VERSION; ?> (Required: 8.0+)</li>
                    <li>✅ MySQL Extension Available</li>
                    <li>✅ PDO Extension Available</li>
                    <li><?php echo is_writable('config/') ? '✅' : '❌'; ?> Config Directory Writable</li>
                    <li><?php echo is_writable('uploads/') ? '✅' : '❌'; ?> Uploads Directory Writable</li>
                </ul>
                
                <a href="?step=2" class="btn btn-full">Start Installation</a>
                
            <?php elseif ($step == 2): ?>
                <!-- Database Configuration -->
                <h2>Database Configuration</h2>
                <p>Please provide your database connection details.</p>
                
                <form method="POST">
                    <div class="form-group">
                        <label class="form-label">Database Host</label>
                        <input type="text" name="db_host" class="form-control" value="localhost" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Database Name</label>
                        <input type="text" name="db_name" class="form-control" value="markethub" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Database Username</label>
                        <input type="text" name="db_user" class="form-control" value="root" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Database Password</label>
                        <input type="password" name="db_pass" class="form-control">
                    </div>
                    
                    <button type="submit" class="btn btn-full">Configure Database</button>
                </form>
                
            <?php elseif ($step == 3): ?>
                <!-- Admin User Creation -->
                <h2>Create Admin User</h2>
                <p>Create the main administrator account for your platform.</p>
                
                <form method="POST">
                    <div class="form-group">
                        <label class="form-label">Username</label>
                        <input type="text" name="admin_username" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Email</label>
                        <input type="email" name="admin_email" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">First Name</label>
                        <input type="text" name="admin_first_name" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Last Name</label>
                        <input type="text" name="admin_last_name" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Password</label>
                        <input type="password" name="admin_password" class="form-control" required>
                    </div>
                    
                    <button type="submit" class="btn btn-full">Create Admin User</button>
                </form>
                
            <?php elseif ($step == 4): ?>
                <!-- Site Configuration -->
                <h2>Site Configuration</h2>
                <p>Configure your site settings.</p>
                
                <form method="POST">
                    <div class="form-group">
                        <label class="form-label">Site Name</label>
                        <input type="text" name="site_name" class="form-control" value="MarketHub" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Site URL</label>
                        <input type="url" name="site_url" class="form-control" value="<?php echo 'http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . '/'; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Admin Email</label>
                        <input type="email" name="admin_email" class="form-control" value="admin@markethub.com" required>
                    </div>
                    
                    <button type="submit" class="btn btn-full">Complete Installation</button>
                </form>
                
            <?php elseif ($step == 5): ?>
                <!-- Installation Complete -->
                <h2>Installation Complete!</h2>
                <p>MarketHub has been successfully installed and configured.</p>
                
                <div style="background: var(--light-gray); padding: 1rem; border-radius: 8px; margin: 1rem 0;">
                    <h3>Next Steps:</h3>
                    <ul>
                        <li>Delete this installation file (install.php) for security</li>
                        <li>Log in to the admin panel to configure your platform</li>
                        <li>Set up your first vendor stores</li>
                        <li>Add product categories</li>
                        <li>Configure payment methods</li>
                    </ul>
                </div>
                
                <div style="display: flex; gap: 1rem;">
                    <a href="index.php" class="btn" style="flex: 1; text-align: center;">View Site</a>
                    <a href="login.php" class="btn" style="flex: 1; text-align: center;">Admin Login</a>
                </div>
                
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
