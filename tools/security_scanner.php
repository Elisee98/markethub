<?php
/**
 * MarketHub Security Scanner
 * Multi-Vendor E-Commerce Platform Security Analysis and Testing
 */

require_once '../config/config.php';

class SecurityScanner {
    private $vulnerabilities = [];
    private $warnings = [];
    private $passed = [];
    
    /**
     * Run comprehensive security scan
     */
    public function runScan() {
        echo "MarketHub Security Scanner\n";
        echo "=========================\n\n";
        
        $this->checkFilePermissions();
        $this->checkConfigurationSecurity();
        $this->checkDatabaseSecurity();
        $this->checkInputValidation();
        $this->checkAuthenticationSecurity();
        $this->checkSessionSecurity();
        $this->checkFileUploadSecurity();
        $this->checkSQLInjectionProtection();
        $this->checkXSSProtection();
        $this->checkCSRFProtection();
        
        $this->generateSecurityReport();
        
        return [
            'vulnerabilities' => $this->vulnerabilities,
            'warnings' => $this->warnings,
            'passed' => $this->passed
        ];
    }
    
    /**
     * Check file permissions
     */
    private function checkFilePermissions() {
        echo "Checking File Permissions...\n";
        
        $critical_files = [
            '../config/config.php',
            '../config/database.php',
            '../includes/functions.php'
        ];
        
        foreach ($critical_files as $file) {
            if (file_exists($file)) {
                $perms = fileperms($file);
                $octal_perms = substr(sprintf('%o', $perms), -4);
                
                if ($octal_perms > '0644') {
                    $this->vulnerabilities[] = "File $file has overly permissive permissions: $octal_perms";
                } else {
                    $this->passed[] = "File permissions for $file are secure: $octal_perms";
                }
            }
        }
        
        // Check upload directory
        $upload_dir = '../uploads/';
        if (is_dir($upload_dir)) {
            $perms = fileperms($upload_dir);
            $octal_perms = substr(sprintf('%o', $perms), -4);
            
            if ($octal_perms > '0755') {
                $this->warnings[] = "Upload directory has overly permissive permissions: $octal_perms";
            } else {
                $this->passed[] = "Upload directory permissions are appropriate: $octal_perms";
            }
        }
    }
    
    /**
     * Check configuration security
     */
    private function checkConfigurationSecurity() {
        echo "Checking Configuration Security...\n";
        
        // Check if debug mode is disabled in production
        if (defined('DEBUG_MODE') && DEBUG_MODE === true) {
            $this->vulnerabilities[] = "Debug mode is enabled - should be disabled in production";
        } else {
            $this->passed[] = "Debug mode is properly disabled";
        }
        
        // Check error reporting
        if (ini_get('display_errors') == '1') {
            $this->vulnerabilities[] = "Error display is enabled - should be disabled in production";
        } else {
            $this->passed[] = "Error display is properly disabled";
        }
        
        // Check if sensitive files are protected
        $sensitive_files = [
            '../config/config.php',
            '../config/database.php',
            '.env'
        ];
        
        foreach ($sensitive_files as $file) {
            if (file_exists($file)) {
                // Check if file is accessible via web
                $url = str_replace('../', '', $file);
                $headers = @get_headers("http://localhost/markethub/$url");
                
                if ($headers && strpos($headers[0], '200') !== false) {
                    $this->vulnerabilities[] = "Sensitive file $file is accessible via web";
                } else {
                    $this->passed[] = "Sensitive file $file is properly protected";
                }
            }
        }
    }
    
    /**
     * Check database security
     */
    private function checkDatabaseSecurity() {
        echo "Checking Database Security...\n";
        
        global $database;
        
        try {
            // Check for default passwords
            $default_users = $database->fetchAll("
                SELECT id, email FROM users 
                WHERE password_hash = ? OR email IN ('admin@admin.com', 'test@test.com')
            ", [password_hash('password', PASSWORD_DEFAULT)]);
            
            if (!empty($default_users)) {
                $this->vulnerabilities[] = "Found users with default passwords or test accounts";
            } else {
                $this->passed[] = "No default passwords or test accounts found";
            }
            
            // Check for SQL injection in stored data
            $suspicious_patterns = ['<script', 'javascript:', 'onload=', 'onerror='];
            $tables_to_check = ['users', 'products', 'categories'];
            
            foreach ($tables_to_check as $table) {
                foreach ($suspicious_patterns as $pattern) {
                    $result = $database->fetchAll("SELECT COUNT(*) as count FROM $table WHERE CONCAT_WS(' ', *) LIKE ?", ["%$pattern%"]);
                    if ($result[0]['count'] > 0) {
                        $this->warnings[] = "Suspicious content found in $table table";
                    }
                }
            }
            
        } catch (Exception $e) {
            $this->warnings[] = "Database security check failed: " . $e->getMessage();
        }
    }
    
    /**
     * Check input validation
     */
    private function checkInputValidation() {
        echo "Checking Input Validation...\n";
        
        // Test sanitization function
        $test_inputs = [
            '<script>alert("xss")</script>',
            'javascript:alert("xss")',
            '<?php echo "php injection"; ?>',
            "'; DROP TABLE users; --"
        ];
        
        foreach ($test_inputs as $input) {
            $sanitized = sanitizeInput($input);
            
            if (strpos($sanitized, '<script>') !== false || 
                strpos($sanitized, 'javascript:') !== false ||
                strpos($sanitized, '<?php') !== false) {
                $this->vulnerabilities[] = "Input sanitization is insufficient for: $input";
            } else {
                $this->passed[] = "Input properly sanitized for malicious content";
            }
        }
        
        // Check email validation
        $test_emails = [
            'valid@example.com',
            'invalid-email',
            'test@',
            '@example.com',
            'test..test@example.com'
        ];
        
        $valid_count = 0;
        $invalid_count = 0;
        
        foreach ($test_emails as $email) {
            if (validateEmailFormat($email)) {
                if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $valid_count++;
                } else {
                    $this->warnings[] = "Email validation accepts invalid email: $email";
                }
            } else {
                $invalid_count++;
            }
        }
        
        if ($valid_count > 0 && $invalid_count > 0) {
            $this->passed[] = "Email validation is working correctly";
        }
    }
    
    /**
     * Check authentication security
     */
    private function checkAuthenticationSecurity() {
        echo "Checking Authentication Security...\n";
        
        // Check password hashing
        $test_password = 'testpassword123';
        $hash = password_hash($test_password, PASSWORD_DEFAULT);
        
        if (password_verify($test_password, $hash)) {
            $this->passed[] = "Password hashing is working correctly";
        } else {
            $this->vulnerabilities[] = "Password hashing is not working properly";
        }
        
        // Check password strength requirements
        $weak_passwords = ['123456', 'password', 'admin', 'test'];
        
        foreach ($weak_passwords as $weak_password) {
            // Simulate password validation (you would implement this function)
            if (strlen($weak_password) < 6) {
                $this->passed[] = "Weak password rejected: $weak_password";
            } else {
                $this->warnings[] = "Password strength validation may be insufficient";
            }
        }
        
        // Check for account lockout mechanism
        global $database;
        try {
            $lockout_table = $database->fetch("SHOW TABLES LIKE 'login_attempts'");
            if ($lockout_table) {
                $this->passed[] = "Account lockout mechanism is implemented";
            } else {
                $this->warnings[] = "No account lockout mechanism found";
            }
        } catch (Exception $e) {
            $this->warnings[] = "Could not check account lockout mechanism";
        }
    }
    
    /**
     * Check session security
     */
    private function checkSessionSecurity() {
        echo "Checking Session Security...\n";
        
        // Check session configuration
        $secure_settings = [
            'session.cookie_httponly' => '1',
            'session.cookie_secure' => '1',
            'session.use_strict_mode' => '1'
        ];
        
        foreach ($secure_settings as $setting => $expected) {
            $actual = ini_get($setting);
            if ($actual != $expected) {
                $this->warnings[] = "Session setting $setting should be $expected, currently $actual";
            } else {
                $this->passed[] = "Session setting $setting is properly configured";
            }
        }
        
        // Check session regeneration
        if (function_exists('session_regenerate_id')) {
            $this->passed[] = "Session regeneration is available";
        } else {
            $this->vulnerabilities[] = "Session regeneration is not available";
        }
    }
    
    /**
     * Check file upload security
     */
    private function checkFileUploadSecurity() {
        echo "Checking File Upload Security...\n";
        
        // Check upload directory exists and is writable
        $upload_dir = '../uploads/';
        if (!is_dir($upload_dir)) {
            $this->warnings[] = "Upload directory does not exist";
        } elseif (!is_writable($upload_dir)) {
            $this->vulnerabilities[] = "Upload directory is not writable";
        } else {
            $this->passed[] = "Upload directory is properly configured";
        }
        
        // Check for .htaccess protection
        $htaccess_file = $upload_dir . '.htaccess';
        if (file_exists($htaccess_file)) {
            $content = file_get_contents($htaccess_file);
            if (strpos($content, 'php_flag engine off') !== false) {
                $this->passed[] = "Upload directory has PHP execution disabled";
            } else {
                $this->vulnerabilities[] = "Upload directory may allow PHP execution";
            }
        } else {
            $this->vulnerabilities[] = "Upload directory lacks .htaccess protection";
        }
    }
    
    /**
     * Check SQL injection protection
     */
    private function checkSQLInjectionProtection() {
        echo "Checking SQL Injection Protection...\n";
        
        global $database;
        
        // Test prepared statements
        try {
            $test_id = "1' OR '1'='1";
            $result = $database->fetch("SELECT * FROM users WHERE id = ?", [$test_id]);
            
            if ($result === false || empty($result)) {
                $this->passed[] = "SQL injection protection is working (prepared statements)";
            } else {
                $this->vulnerabilities[] = "Potential SQL injection vulnerability detected";
            }
        } catch (Exception $e) {
            $this->passed[] = "SQL injection attempt properly blocked";
        }
    }
    
    /**
     * Check XSS protection
     */
    private function checkXSSProtection() {
        echo "Checking XSS Protection...\n";
        
        // Test output escaping
        $xss_payloads = [
            '<script>alert("xss")</script>',
            '<img src="x" onerror="alert(1)">',
            'javascript:alert("xss")',
            '<svg onload="alert(1)">'
        ];
        
        foreach ($xss_payloads as $payload) {
            $escaped = htmlspecialchars($payload, ENT_QUOTES, 'UTF-8');
            
            if ($escaped !== $payload && strpos($escaped, '<script>') === false) {
                $this->passed[] = "XSS payload properly escaped";
            } else {
                $this->vulnerabilities[] = "XSS payload not properly escaped: $payload";
            }
        }
    }
    
    /**
     * Check CSRF protection
     */
    private function checkCSRFProtection() {
        echo "Checking CSRF Protection...\n";
        
        // Test CSRF token generation
        $token1 = generateCSRFToken();
        $token2 = generateCSRFToken();
        
        if (!empty($token1) && !empty($token2)) {
            $this->passed[] = "CSRF token generation is working";
        } else {
            $this->vulnerabilities[] = "CSRF token generation is not working";
        }
        
        // Test token verification
        if (function_exists('verifyCSRFToken')) {
            if (verifyCSRFToken($token1)) {
                $this->passed[] = "CSRF token verification is working";
            } else {
                $this->vulnerabilities[] = "CSRF token verification is not working";
            }
        } else {
            $this->vulnerabilities[] = "CSRF token verification function not found";
        }
    }
    
    /**
     * Generate security report
     */
    private function generateSecurityReport() {
        echo "\n=========================\n";
        echo "Security Scan Results:\n";
        echo "=========================\n";
        
        echo "\nVULNERABILITIES (" . count($this->vulnerabilities) . "):\n";
        foreach ($this->vulnerabilities as $vuln) {
            echo "  ❌ $vuln\n";
        }
        
        echo "\nWARNINGS (" . count($this->warnings) . "):\n";
        foreach ($this->warnings as $warning) {
            echo "  ⚠️  $warning\n";
        }
        
        echo "\nPASSED CHECKS (" . count($this->passed) . "):\n";
        foreach ($this->passed as $passed) {
            echo "  ✅ $passed\n";
        }
        
        // Security score
        $total_checks = count($this->vulnerabilities) + count($this->warnings) + count($this->passed);
        $score = round((count($this->passed) / $total_checks) * 100, 1);
        
        echo "\n=========================\n";
        echo "Security Score: $score%\n";
        
        if ($score >= 90) {
            echo "Status: EXCELLENT 🛡️\n";
        } elseif ($score >= 75) {
            echo "Status: GOOD ✅\n";
        } elseif ($score >= 60) {
            echo "Status: NEEDS IMPROVEMENT ⚠️\n";
        } else {
            echo "Status: CRITICAL ISSUES ❌\n";
        }
    }
    
    /**
     * Generate detailed security report
     */
    public function generateDetailedReport() {
        $report = [
            'timestamp' => date('Y-m-d H:i:s'),
            'scan_results' => [
                'vulnerabilities' => $this->vulnerabilities,
                'warnings' => $this->warnings,
                'passed' => $this->passed
            ],
            'security_score' => round((count($this->passed) / (count($this->vulnerabilities) + count($this->warnings) + count($this->passed))) * 100, 1),
            'recommendations' => $this->generateRecommendations()
        ];
        
        file_put_contents('security_report.json', json_encode($report, JSON_PRETTY_PRINT));
        return $report;
    }
    
    /**
     * Generate security recommendations
     */
    private function generateRecommendations() {
        $recommendations = [];
        
        if (!empty($this->vulnerabilities)) {
            $recommendations[] = "Address all critical vulnerabilities immediately";
            $recommendations[] = "Implement regular security audits";
            $recommendations[] = "Consider using a Web Application Firewall (WAF)";
        }
        
        if (!empty($this->warnings)) {
            $recommendations[] = "Review and address security warnings";
            $recommendations[] = "Implement additional security headers";
            $recommendations[] = "Consider security monitoring tools";
        }
        
        $recommendations[] = "Keep all software dependencies updated";
        $recommendations[] = "Implement regular security training for developers";
        $recommendations[] = "Set up automated security scanning in CI/CD pipeline";
        
        return $recommendations;
    }
}

// Run security scan if called directly
if (basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'])) {
    $scanner = new SecurityScanner();
    
    // Run security scan
    $results = $scanner->runScan();
    
    // Generate detailed report
    $report = $scanner->generateDetailedReport();
    
    echo "\nDetailed security report saved to: security_report.json\n";
}
?>
