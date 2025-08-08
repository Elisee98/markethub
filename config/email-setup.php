<?php
/**
 * MarketHub Email Setup Helper
 * Automatically configure XAMPP sendmail for email functionality
 */

// Check if running from command line or web
$is_cli = php_sapi_name() === 'cli';

if (!$is_cli) {
    echo "<!DOCTYPE html>
    <html lang='en'>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>Email Setup Helper - MarketHub</title>
        <style>
            body { font-family: Arial, sans-serif; margin: 2rem; background: #f5f5f5; }
            .container { max-width: 800px; margin: 0 auto; background: white; padding: 2rem; border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
            .header { background: linear-gradient(135deg, #2196F3, #1976D2); color: white; padding: 2rem; border-radius: 8px; text-align: center; margin-bottom: 2rem; }
            .success { color: green; background: #d4edda; padding: 1rem; border-radius: 5px; margin: 1rem 0; }
            .error { color: red; background: #f8d7da; padding: 1rem; border-radius: 5px; margin: 1rem 0; }
            .warning { color: #856404; background: #fff3cd; padding: 1rem; border-radius: 5px; margin: 1rem 0; }
            .info { color: #0c5460; background: #d1ecf1; padding: 1rem; border-radius: 5px; margin: 1rem 0; }
            pre { background: #f8f9fa; padding: 1rem; border-radius: 5px; overflow-x: auto; }
            .btn { padding: 10px 20px; background: #2196F3; color: white; text-decoration: none; border-radius: 5px; display: inline-block; margin: 0.5rem; }
            .btn:hover { background: #1976D2; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>📧 Email Setup Helper</h1>
                <p>Configure email functionality for MarketHub</p>
            </div>";
}

function output($message, $type = 'info') {
    global $is_cli;
    
    if ($is_cli) {
        echo $message . "\n";
    } else {
        $class = $type;
        echo "<div class='$class'>$message</div>";
    }
}

function checkXamppInstallation() {
    $xampp_paths = [
        'C:\\xampp\\',
        'C:\\XAMPP\\',
        '/opt/lampp/',
        '/Applications/XAMPP/'
    ];
    
    foreach ($xampp_paths as $path) {
        if (is_dir($path)) {
            return $path;
        }
    }
    
    return false;
}

function createSendmailConfig($xampp_path, $email, $password) {
    $sendmail_ini = $xampp_path . 'sendmail/sendmail.ini';
    
    if (!file_exists(dirname($sendmail_ini))) {
        output("Sendmail directory not found: " . dirname($sendmail_ini), 'error');
        return false;
    }
    
    $config = "[sendmail]
smtp_server=smtp.gmail.com
smtp_port=587
smtp_ssl=tls
error_logfile=error.log
debug_logfile=debug.log
auth_username=$email
auth_password=$password
force_sender=$email
force_recipient=
hostname=localhost
";
    
    // Backup existing config
    if (file_exists($sendmail_ini)) {
        copy($sendmail_ini, $sendmail_ini . '.backup.' . date('Y-m-d-H-i-s'));
        output("Backed up existing sendmail.ini", 'info');
    }
    
    if (file_put_contents($sendmail_ini, $config)) {
        output("✅ Created sendmail.ini configuration", 'success');
        return true;
    } else {
        output("❌ Failed to write sendmail.ini - check permissions", 'error');
        return false;
    }
}

function updatePhpIni($xampp_path) {
    $php_ini = $xampp_path . 'php/php.ini';
    
    if (!file_exists($php_ini)) {
        output("php.ini not found: $php_ini", 'error');
        return false;
    }
    
    $content = file_get_contents($php_ini);
    
    // Backup existing php.ini
    copy($php_ini, $php_ini . '.backup.' . date('Y-m-d-H-i-s'));
    output("Backed up existing php.ini", 'info');
    
    // Update sendmail_path
    $sendmail_path = str_replace('/', '\\', $xampp_path) . 'sendmail\\sendmail.exe -t';
    
    if (strpos($content, 'sendmail_path') !== false) {
        $content = preg_replace('/^;?sendmail_path\s*=.*$/m', 'sendmail_path = "' . $sendmail_path . '"', $content);
    } else {
        $content .= "\n; MarketHub Email Configuration\nsendmail_path = \"$sendmail_path\"\n";
    }
    
    if (file_put_contents($php_ini, $content)) {
        output("✅ Updated php.ini configuration", 'success');
        return true;
    } else {
        output("❌ Failed to write php.ini - check permissions", 'error');
        return false;
    }
}

// Main setup process
output("🚀 Starting MarketHub Email Setup...", 'info');

// Check for XAMPP installation
$xampp_path = checkXamppInstallation();

if (!$xampp_path) {
    output("❌ XAMPP installation not found. Please install XAMPP first.", 'error');
    if (!$is_cli) {
        echo "<div class='info'>
            <h3>Manual Setup Instructions:</h3>
            <ol>
                <li>Install XAMPP from <a href='https://www.apachefriends.org/' target='_blank'>apachefriends.org</a></li>
                <li>Configure sendmail manually following the instructions in the admin panel</li>
                <li>Or keep DEVELOPMENT_MODE = true to log emails instead of sending them</li>
            </ol>
        </div>";
    }
} else {
    output("✅ Found XAMPP installation at: $xampp_path", 'success');
    
    if (!$is_cli) {
        // Web interface for configuration
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = $_POST['email'] ?? '';
            $password = $_POST['password'] ?? '';
            
            if (empty($email) || empty($password)) {
                output("❌ Please provide both email and password", 'error');
            } else {
                output("📧 Configuring email with: $email", 'info');
                
                $sendmail_success = createSendmailConfig($xampp_path, $email, $password);
                $php_success = updatePhpIni($xampp_path);
                
                if ($sendmail_success && $php_success) {
                    output("🎉 Email configuration completed successfully!", 'success');
                    output("⚠️ Please restart Apache for changes to take effect", 'warning');
                    echo "<div class='success'>
                        <h3>Next Steps:</h3>
                        <ol>
                            <li>Restart Apache in XAMPP Control Panel</li>
                            <li>Set DEVELOPMENT_MODE = false in config/config.php (optional)</li>
                            <li>Test email functionality using the <a href='../admin/email-test.php'>Email Test Page</a></li>
                        </ol>
                        <a href='../admin/email-test.php' class='btn'>Test Email Now</a>
                    </div>";
                } else {
                    output("❌ Configuration failed. Check file permissions.", 'error');
                }
            }
        } else {
            // Show configuration form
            echo "<div class='info'>
                <h3>Gmail SMTP Configuration</h3>
                <p>To send emails through Gmail, you'll need:</p>
                <ul>
                    <li>A Gmail account</li>
                    <li>An App Password (not your regular password)</li>
                </ul>
                <p><strong>How to get an App Password:</strong></p>
                <ol>
                    <li>Go to your Google Account settings</li>
                    <li>Enable 2-Factor Authentication</li>
                    <li>Go to Security → App passwords</li>
                    <li>Generate a new app password for 'Mail'</li>
                    <li>Use that 16-character password below</li>
                </ol>
            </div>
            
            <form method='POST'>
                <div style='margin: 1rem 0;'>
                    <label for='email'><strong>Gmail Address:</strong></label><br>
                    <input type='email' id='email' name='email' style='width: 100%; padding: 8px; margin: 5px 0;' 
                           placeholder='your-email@gmail.com' required>
                </div>
                
                <div style='margin: 1rem 0;'>
                    <label for='password'><strong>App Password:</strong></label><br>
                    <input type='password' id='password' name='password' style='width: 100%; padding: 8px; margin: 5px 0;' 
                           placeholder='16-character app password' required>
                </div>
                
                <button type='submit' class='btn'>Configure Email</button>
            </form>
            
            <div class='warning'>
                <h3>Alternative: Keep Development Mode</h3>
                <p>If you prefer not to configure SMTP, you can keep <code>DEVELOPMENT_MODE = true</code> in your config. 
                   This will log all emails to files instead of sending them, which is perfect for development and testing.</p>
                <a href='../admin/email-test.php' class='btn'>Test Current Setup</a>
            </div>";
        }
    }
}

if (!$is_cli) {
    echo "<div style='margin-top: 2rem; text-align: center;'>
        <a href='../index.php' class='btn'>← Back to Homepage</a>
        <a href='../admin/email-test.php' class='btn'>Email Test Page</a>
    </div>
    </div>
    </body>
    </html>";
}
?>
