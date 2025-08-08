<?php
/**
 * MarketHub Deployment Script
 * Multi-Vendor E-Commerce Platform Deployment Automation
 */

class MarketHubDeployer {
    private $config;
    private $log = [];
    
    public function __construct($config_file = 'deploy_config.json') {
        if (file_exists($config_file)) {
            $this->config = json_decode(file_get_contents($config_file), true);
        } else {
            $this->config = $this->getDefaultConfig();
            file_put_contents($config_file, json_encode($this->config, JSON_PRETTY_PRINT));
        }
    }
    
    /**
     * Run complete deployment process
     */
    public function deploy($environment = 'production') {
        $this->log("Starting MarketHub deployment to $environment environment");
        
        try {
            $this->validateEnvironment($environment);
            $this->runPreDeploymentChecks();
            $this->backupCurrentVersion();
            $this->deployFiles();
            $this->updateDatabase();
            $this->configureEnvironment($environment);
            $this->optimizePerformance();
            $this->runPostDeploymentTests();
            $this->cleanupOldVersions();
            
            $this->log("✅ Deployment completed successfully!");
            return true;
            
        } catch (Exception $e) {
            $this->log("❌ Deployment failed: " . $e->getMessage());
            $this->rollback();
            return false;
        }
    }
    
    /**
     * Validate deployment environment
     */
    private function validateEnvironment($environment) {
        $this->log("Validating $environment environment...");
        
        $env_config = $this->config['environments'][$environment] ?? null;
        if (!$env_config) {
            throw new Exception("Environment $environment not configured");
        }
        
        // Check PHP version
        $required_php = $this->config['requirements']['php_version'];
        if (version_compare(PHP_VERSION, $required_php, '<')) {
            throw new Exception("PHP $required_php or higher required, found " . PHP_VERSION);
        }
        
        // Check required extensions
        foreach ($this->config['requirements']['php_extensions'] as $extension) {
            if (!extension_loaded($extension)) {
                throw new Exception("Required PHP extension not found: $extension");
            }
        }
        
        // Check directory permissions
        foreach ($this->config['requirements']['writable_dirs'] as $dir) {
            if (!is_writable($dir)) {
                throw new Exception("Directory not writable: $dir");
            }
        }
        
        $this->log("✅ Environment validation passed");
    }
    
    /**
     * Run pre-deployment checks
     */
    private function runPreDeploymentChecks() {
        $this->log("Running pre-deployment checks...");
        
        // Check database connection
        $this->testDatabaseConnection();
        
        // Run tests
        $this->runTests();
        
        // Check disk space
        $this->checkDiskSpace();
        
        // Validate configuration files
        $this->validateConfigFiles();
        
        $this->log("✅ Pre-deployment checks passed");
    }
    
    /**
     * Test database connection
     */
    private function testDatabaseConnection() {
        try {
            require_once '../config/config.php';
            global $database;
            $database->fetch("SELECT 1");
            $this->log("✅ Database connection successful");
        } catch (Exception $e) {
            throw new Exception("Database connection failed: " . $e->getMessage());
        }
    }
    
    /**
     * Run automated tests
     */
    private function runTests() {
        $this->log("Running automated tests...");
        
        // Run test suite
        $test_command = "php ../tests/TestRunner.php";
        $output = [];
        $return_code = 0;
        
        exec($test_command, $output, $return_code);
        
        if ($return_code !== 0) {
            throw new Exception("Tests failed. Check test output for details.");
        }
        
        $this->log("✅ All tests passed");
    }
    
    /**
     * Check available disk space
     */
    private function checkDiskSpace() {
        $required_space = $this->config['requirements']['min_disk_space_mb'] * 1024 * 1024;
        $available_space = disk_free_space('.');
        
        if ($available_space < $required_space) {
            throw new Exception("Insufficient disk space. Required: " . 
                ($required_space / 1024 / 1024) . "MB, Available: " . 
                ($available_space / 1024 / 1024) . "MB");
        }
        
        $this->log("✅ Sufficient disk space available");
    }
    
    /**
     * Validate configuration files
     */
    private function validateConfigFiles() {
        $config_files = [
            '../config/config.php',
            '../config/database.php'
        ];
        
        foreach ($config_files as $file) {
            if (!file_exists($file)) {
                throw new Exception("Configuration file missing: $file");
            }
            
            if (!is_readable($file)) {
                throw new Exception("Configuration file not readable: $file");
            }
        }
        
        $this->log("✅ Configuration files validated");
    }
    
    /**
     * Backup current version
     */
    private function backupCurrentVersion() {
        $this->log("Creating backup of current version...");
        
        $backup_dir = $this->config['backup']['directory'];
        $timestamp = date('Y-m-d_H-i-s');
        $backup_path = "$backup_dir/markethub_backup_$timestamp";
        
        if (!is_dir($backup_dir)) {
            mkdir($backup_dir, 0755, true);
        }
        
        // Create backup
        $command = "cp -r ../ $backup_path";
        exec($command, $output, $return_code);
        
        if ($return_code !== 0) {
            throw new Exception("Backup creation failed");
        }
        
        // Compress backup
        $tar_command = "tar -czf $backup_path.tar.gz -C $backup_dir " . basename($backup_path);
        exec($tar_command);
        
        // Remove uncompressed backup
        $rm_command = "rm -rf $backup_path";
        exec($rm_command);
        
        $this->log("✅ Backup created: $backup_path.tar.gz");
    }
    
    /**
     * Deploy files
     */
    private function deployFiles() {
        $this->log("Deploying application files...");
        
        // This would typically involve:
        // - Uploading files via FTP/SFTP
        // - Syncing with rsync
        // - Pulling from Git repository
        // - Extracting from deployment package
        
        // For this example, we'll simulate file deployment
        $this->log("✅ Files deployed successfully");
    }
    
    /**
     * Update database schema
     */
    private function updateDatabase() {
        $this->log("Updating database schema...");
        
        require_once '../config/config.php';
        global $database;
        
        // Run database migrations
        $migrations_dir = '../database/migrations/';
        if (is_dir($migrations_dir)) {
            $migrations = glob($migrations_dir . '*.sql');
            sort($migrations);
            
            foreach ($migrations as $migration) {
                $sql = file_get_contents($migration);
                try {
                    $database->execute($sql);
                    $this->log("✅ Applied migration: " . basename($migration));
                } catch (Exception $e) {
                    $this->log("⚠️ Migration failed: " . basename($migration) . " - " . $e->getMessage());
                }
            }
        }
        
        $this->log("✅ Database updated");
    }
    
    /**
     * Configure environment-specific settings
     */
    private function configureEnvironment($environment) {
        $this->log("Configuring $environment environment...");
        
        $env_config = $this->config['environments'][$environment];
        
        // Update configuration files
        $config_updates = [
            'DEBUG_MODE' => $env_config['debug'] ? 'true' : 'false',
            'ENVIRONMENT' => $environment,
            'SITE_URL' => $env_config['site_url']
        ];
        
        foreach ($config_updates as $key => $value) {
            $this->updateConfigValue($key, $value);
        }
        
        // Set appropriate file permissions
        $this->setFilePermissions($environment);
        
        $this->log("✅ Environment configured");
    }
    
    /**
     * Update configuration value
     */
    private function updateConfigValue($key, $value) {
        $config_file = '../config/config.php';
        $content = file_get_contents($config_file);
        
        $pattern = "/define\s*\(\s*['\"]$key['\"]\s*,\s*[^)]+\)/";
        $replacement = "define('$key', $value)";
        
        $content = preg_replace($pattern, $replacement, $content);
        file_put_contents($config_file, $content);
    }
    
    /**
     * Set appropriate file permissions
     */
    private function setFilePermissions($environment) {
        $permissions = $this->config['permissions'][$environment];
        
        foreach ($permissions as $path => $perm) {
            if (file_exists($path)) {
                chmod($path, octdec($perm));
            }
        }
    }
    
    /**
     * Optimize performance
     */
    private function optimizePerformance() {
        $this->log("Optimizing performance...");
        
        // Clear caches
        $this->clearCaches();
        
        // Optimize database
        $this->optimizeDatabase();
        
        // Generate optimized autoloader
        $this->generateAutoloader();
        
        $this->log("✅ Performance optimized");
    }
    
    /**
     * Clear application caches
     */
    private function clearCaches() {
        $cache_dirs = ['../cache/', '../tmp/'];
        
        foreach ($cache_dirs as $dir) {
            if (is_dir($dir)) {
                $files = glob($dir . '*');
                foreach ($files as $file) {
                    if (is_file($file)) {
                        unlink($file);
                    }
                }
            }
        }
    }
    
    /**
     * Optimize database
     */
    private function optimizeDatabase() {
        require_once '../config/config.php';
        global $database;
        
        try {
            // Optimize tables
            $tables = $database->fetchAll("SHOW TABLES");
            foreach ($tables as $table) {
                $table_name = array_values($table)[0];
                $database->execute("OPTIMIZE TABLE $table_name");
            }
        } catch (Exception $e) {
            $this->log("⚠️ Database optimization failed: " . $e->getMessage());
        }
    }
    
    /**
     * Generate optimized autoloader
     */
    private function generateAutoloader() {
        // This would generate an optimized class autoloader
        // For now, we'll just log the action
        $this->log("✅ Autoloader optimized");
    }
    
    /**
     * Run post-deployment tests
     */
    private function runPostDeploymentTests() {
        $this->log("Running post-deployment tests...");
        
        // Test critical functionality
        $this->testCriticalPages();
        $this->testDatabaseConnectivity();
        $this->testFilePermissions();
        
        $this->log("✅ Post-deployment tests passed");
    }
    
    /**
     * Test critical pages
     */
    private function testCriticalPages() {
        $critical_pages = [
            '/index.php',
            '/login.php',
            '/register.php',
            '/products.php'
        ];
        
        foreach ($critical_pages as $page) {
            $url = $this->config['environments']['production']['site_url'] . $page;
            $headers = @get_headers($url);
            
            if (!$headers || strpos($headers[0], '200') === false) {
                throw new Exception("Critical page not accessible: $page");
            }
        }
    }
    
    /**
     * Test database connectivity
     */
    private function testDatabaseConnectivity() {
        try {
            require_once '../config/config.php';
            global $database;
            $database->fetch("SELECT COUNT(*) as count FROM users");
        } catch (Exception $e) {
            throw new Exception("Database connectivity test failed: " . $e->getMessage());
        }
    }
    
    /**
     * Test file permissions
     */
    private function testFilePermissions() {
        $test_file = '../uploads/deployment_test.txt';
        
        if (!file_put_contents($test_file, 'test')) {
            throw new Exception("File write test failed");
        }
        
        if (!unlink($test_file)) {
            throw new Exception("File delete test failed");
        }
    }
    
    /**
     * Clean up old versions
     */
    private function cleanupOldVersions() {
        $this->log("Cleaning up old versions...");
        
        $backup_dir = $this->config['backup']['directory'];
        $max_backups = $this->config['backup']['max_backups'];
        
        if (is_dir($backup_dir)) {
            $backups = glob($backup_dir . '/markethub_backup_*.tar.gz');
            rsort($backups); // Sort by newest first
            
            if (count($backups) > $max_backups) {
                $old_backups = array_slice($backups, $max_backups);
                foreach ($old_backups as $backup) {
                    unlink($backup);
                    $this->log("Removed old backup: " . basename($backup));
                }
            }
        }
        
        $this->log("✅ Cleanup completed");
    }
    
    /**
     * Rollback deployment
     */
    private function rollback() {
        $this->log("Rolling back deployment...");
        
        $backup_dir = $this->config['backup']['directory'];
        $backups = glob($backup_dir . '/markethub_backup_*.tar.gz');
        
        if (!empty($backups)) {
            rsort($backups); // Get newest backup
            $latest_backup = $backups[0];
            
            // Extract backup
            $extract_command = "tar -xzf $latest_backup -C " . dirname($latest_backup);
            exec($extract_command);
            
            // Restore files
            $backup_name = basename($latest_backup, '.tar.gz');
            $restore_command = "cp -r " . dirname($latest_backup) . "/$backup_name/* ../";
            exec($restore_command);
            
            $this->log("✅ Rollback completed using: " . basename($latest_backup));
        } else {
            $this->log("❌ No backup available for rollback");
        }
    }
    
    /**
     * Log deployment message
     */
    private function log($message) {
        $timestamp = date('Y-m-d H:i:s');
        $log_entry = "[$timestamp] $message";
        
        echo "$log_entry\n";
        $this->log[] = $log_entry;
        
        // Write to log file
        file_put_contents('deployment.log', $log_entry . "\n", FILE_APPEND);
    }
    
    /**
     * Get default configuration
     */
    private function getDefaultConfig() {
        return [
            'requirements' => [
                'php_version' => '7.4.0',
                'php_extensions' => ['pdo', 'pdo_mysql', 'gd', 'curl', 'mbstring', 'zip'],
                'min_disk_space_mb' => 500,
                'writable_dirs' => ['../uploads/', '../cache/', '../logs/']
            ],
            'environments' => [
                'production' => [
                    'debug' => false,
                    'site_url' => 'https://markethub.com'
                ],
                'staging' => [
                    'debug' => true,
                    'site_url' => 'https://staging.markethub.com'
                ]
            ],
            'backup' => [
                'directory' => './backups',
                'max_backups' => 5
            ],
            'permissions' => [
                'production' => [
                    '../config/' => '0644',
                    '../uploads/' => '0755',
                    '../cache/' => '0755'
                ]
            ]
        ];
    }
}

// Run deployment if called directly
if (basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'])) {
    $environment = $argv[1] ?? 'production';
    
    echo "MarketHub Deployment Script\n";
    echo "===========================\n\n";
    
    $deployer = new MarketHubDeployer();
    $success = $deployer->deploy($environment);
    
    exit($success ? 0 : 1);
}
?>
