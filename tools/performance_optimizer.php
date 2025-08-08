<?php
/**
 * MarketHub Performance Optimizer
 * Multi-Vendor E-Commerce Platform Performance Analysis and Optimization
 */

require_once '../config/config.php';

class PerformanceOptimizer {
    private $database;
    private $results = [];
    
    public function __construct() {
        global $database;
        $this->database = $database;
    }
    
    /**
     * Run comprehensive performance analysis
     */
    public function runAnalysis() {
        echo "MarketHub Performance Analysis\n";
        echo "=============================\n\n";
        
        $this->analyzeDatabasePerformance();
        $this->analyzeQueryPerformance();
        $this->analyzeFileSystemPerformance();
        $this->analyzeMemoryUsage();
        $this->analyzeCachePerformance();
        $this->generateRecommendations();
        
        return $this->results;
    }
    
    /**
     * Analyze database performance
     */
    private function analyzeDatabasePerformance() {
        echo "Analyzing Database Performance...\n";
        
        $start_time = microtime(true);
        
        // Test connection time
        $connection_start = microtime(true);
        $this->database->fetch("SELECT 1");
        $connection_time = (microtime(true) - $connection_start) * 1000;
        
        // Test query performance
        $queries = [
            'users_count' => "SELECT COUNT(*) as count FROM users",
            'products_count' => "SELECT COUNT(*) as count FROM products",
            'orders_count' => "SELECT COUNT(*) as count FROM orders",
            'complex_join' => "SELECT u.id, u.first_name, COUNT(o.id) as order_count 
                              FROM users u 
                              LEFT JOIN orders o ON u.id = o.customer_id 
                              WHERE u.user_type = 'customer' 
                              GROUP BY u.id 
                              LIMIT 10"
        ];
        
        $query_times = [];
        foreach ($queries as $name => $query) {
            $query_start = microtime(true);
            $this->database->fetchAll($query);
            $query_times[$name] = (microtime(true) - $query_start) * 1000;
        }
        
        $this->results['database'] = [
            'connection_time_ms' => round($connection_time, 2),
            'query_times_ms' => $query_times,
            'total_time_ms' => round((microtime(true) - $start_time) * 1000, 2)
        ];
        
        echo "  Connection Time: " . round($connection_time, 2) . "ms\n";
        echo "  Average Query Time: " . round(array_sum($query_times) / count($query_times), 2) . "ms\n";
    }
    
    /**
     * Analyze slow queries
     */
    private function analyzeQueryPerformance() {
        echo "\nAnalyzing Query Performance...\n";
        
        // Enable query logging (MySQL specific)
        try {
            $this->database->execute("SET SESSION long_query_time = 0.1");
            $this->database->execute("SET SESSION slow_query_log = 1");
            
            // Run sample queries and measure performance
            $slow_queries = [];
            
            // Test product search query
            $start = microtime(true);
            $this->database->fetchAll("
                SELECT p.*, pi.image_url, c.name as category_name 
                FROM products p 
                LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
                LEFT JOIN categories c ON p.category_id = c.id 
                WHERE p.status = 'active' 
                ORDER BY p.created_at DESC 
                LIMIT 20
            ");
            $time = (microtime(true) - $start) * 1000;
            $slow_queries['product_search'] = round($time, 2);
            
            // Test order history query
            $start = microtime(true);
            $this->database->fetchAll("
                SELECT o.*, COUNT(oi.id) as item_count, SUM(oi.total_price) as total
                FROM orders o
                LEFT JOIN order_items oi ON o.id = oi.order_id
                GROUP BY o.id
                ORDER BY o.created_at DESC
                LIMIT 20
            ");
            $time = (microtime(true) - $start) * 1000;
            $slow_queries['order_history'] = round($time, 2);
            
            $this->results['queries'] = $slow_queries;
            
            foreach ($slow_queries as $query => $time) {
                echo "  $query: {$time}ms\n";
            }
            
        } catch (Exception $e) {
            echo "  Query analysis failed: " . $e->getMessage() . "\n";
        }
    }
    
    /**
     * Analyze file system performance
     */
    private function analyzeFileSystemPerformance() {
        echo "\nAnalyzing File System Performance...\n";
        
        $upload_dir = '../uploads/';
        $test_file = $upload_dir . 'performance_test.txt';
        $test_data = str_repeat('Performance test data ', 1000);
        
        // Test write performance
        $start = microtime(true);
        file_put_contents($test_file, $test_data);
        $write_time = (microtime(true) - $start) * 1000;
        
        // Test read performance
        $start = microtime(true);
        $read_data = file_get_contents($test_file);
        $read_time = (microtime(true) - $start) * 1000;
        
        // Test file operations
        $start = microtime(true);
        $file_exists = file_exists($test_file);
        $file_size = filesize($test_file);
        $file_ops_time = (microtime(true) - $start) * 1000;
        
        // Cleanup
        if (file_exists($test_file)) {
            unlink($test_file);
        }
        
        $this->results['filesystem'] = [
            'write_time_ms' => round($write_time, 2),
            'read_time_ms' => round($read_time, 2),
            'file_ops_time_ms' => round($file_ops_time, 2),
            'test_file_size_bytes' => strlen($test_data)
        ];
        
        echo "  Write Time: " . round($write_time, 2) . "ms\n";
        echo "  Read Time: " . round($read_time, 2) . "ms\n";
        echo "  File Operations: " . round($file_ops_time, 2) . "ms\n";
    }
    
    /**
     * Analyze memory usage
     */
    private function analyzeMemoryUsage() {
        echo "\nAnalyzing Memory Usage...\n";
        
        $memory_start = memory_get_usage();
        $memory_peak = memory_get_peak_usage();
        
        // Simulate memory-intensive operation
        $large_array = [];
        for ($i = 0; $i < 10000; $i++) {
            $large_array[] = [
                'id' => $i,
                'data' => str_repeat('x', 100),
                'timestamp' => time()
            ];
        }
        
        $memory_after = memory_get_usage();
        $memory_peak_after = memory_get_peak_usage();
        
        // Cleanup
        unset($large_array);
        
        $this->results['memory'] = [
            'initial_usage_mb' => round($memory_start / 1024 / 1024, 2),
            'peak_usage_mb' => round($memory_peak / 1024 / 1024, 2),
            'after_test_mb' => round($memory_after / 1024 / 1024, 2),
            'peak_after_test_mb' => round($memory_peak_after / 1024 / 1024, 2),
            'memory_limit' => ini_get('memory_limit')
        ];
        
        echo "  Initial Usage: " . round($memory_start / 1024 / 1024, 2) . "MB\n";
        echo "  Peak Usage: " . round($memory_peak_after / 1024 / 1024, 2) . "MB\n";
        echo "  Memory Limit: " . ini_get('memory_limit') . "\n";
    }
    
    /**
     * Analyze cache performance
     */
    private function analyzeCachePerformance() {
        echo "\nAnalyzing Cache Performance...\n";
        
        // Test session cache
        $start = microtime(true);
        $_SESSION['performance_test'] = 'test_data_' . time();
        $session_write_time = (microtime(true) - $start) * 1000;
        
        $start = microtime(true);
        $session_data = $_SESSION['performance_test'];
        $session_read_time = (microtime(true) - $start) * 1000;
        
        // Test file cache simulation
        $cache_file = '../cache/performance_test.cache';
        $cache_data = json_encode(['test' => 'data', 'timestamp' => time()]);
        
        if (!is_dir('../cache')) {
            mkdir('../cache', 0755, true);
        }
        
        $start = microtime(true);
        file_put_contents($cache_file, $cache_data);
        $file_cache_write_time = (microtime(true) - $start) * 1000;
        
        $start = microtime(true);
        $cached_data = file_get_contents($cache_file);
        $file_cache_read_time = (microtime(true) - $start) * 1000;
        
        // Cleanup
        if (file_exists($cache_file)) {
            unlink($cache_file);
        }
        
        $this->results['cache'] = [
            'session_write_ms' => round($session_write_time, 2),
            'session_read_ms' => round($session_read_time, 2),
            'file_cache_write_ms' => round($file_cache_write_time, 2),
            'file_cache_read_ms' => round($file_cache_read_time, 2)
        ];
        
        echo "  Session Write: " . round($session_write_time, 2) . "ms\n";
        echo "  Session Read: " . round($session_read_time, 2) . "ms\n";
        echo "  File Cache Write: " . round($file_cache_write_time, 2) . "ms\n";
        echo "  File Cache Read: " . round($file_cache_read_time, 2) . "ms\n";
    }
    
    /**
     * Generate optimization recommendations
     */
    private function generateRecommendations() {
        echo "\nGenerating Recommendations...\n";
        
        $recommendations = [];
        
        // Database recommendations
        if ($this->results['database']['connection_time_ms'] > 100) {
            $recommendations[] = "Database connection time is high. Consider connection pooling.";
        }
        
        $avg_query_time = array_sum($this->results['queries']) / count($this->results['queries']);
        if ($avg_query_time > 50) {
            $recommendations[] = "Average query time is high. Consider adding database indexes.";
        }
        
        // Memory recommendations
        if ($this->results['memory']['peak_usage_mb'] > 64) {
            $recommendations[] = "Peak memory usage is high. Consider optimizing data structures.";
        }
        
        // File system recommendations
        if ($this->results['filesystem']['write_time_ms'] > 10) {
            $recommendations[] = "File write performance is slow. Consider using SSD storage.";
        }
        
        // Cache recommendations
        if ($this->results['cache']['file_cache_read_ms'] > 5) {
            $recommendations[] = "File cache performance is slow. Consider using Redis or Memcached.";
        }
        
        $this->results['recommendations'] = $recommendations;
        
        if (empty($recommendations)) {
            echo "  No performance issues detected. System is well optimized!\n";
        } else {
            foreach ($recommendations as $recommendation) {
                echo "  - $recommendation\n";
            }
        }
    }
    
    /**
     * Generate performance report
     */
    public function generateReport() {
        $report = [
            'timestamp' => date('Y-m-d H:i:s'),
            'server_info' => [
                'php_version' => PHP_VERSION,
                'memory_limit' => ini_get('memory_limit'),
                'max_execution_time' => ini_get('max_execution_time'),
                'upload_max_filesize' => ini_get('upload_max_filesize')
            ],
            'performance_results' => $this->results
        ];
        
        file_put_contents('performance_report.json', json_encode($report, JSON_PRETTY_PRINT));
        return $report;
    }
    
    /**
     * Optimize database indexes
     */
    public function optimizeDatabase() {
        echo "\nOptimizing Database...\n";
        
        $optimizations = [
            "CREATE INDEX IF NOT EXISTS idx_products_vendor_status ON products(vendor_id, status)",
            "CREATE INDEX IF NOT EXISTS idx_orders_customer_status ON orders(customer_id, status)",
            "CREATE INDEX IF NOT EXISTS idx_order_items_order_product ON order_items(order_id, product_id)",
            "CREATE INDEX IF NOT EXISTS idx_users_type_status ON users(user_type, status)",
            "CREATE INDEX IF NOT EXISTS idx_products_category_status ON products(category_id, status)",
            "CREATE INDEX IF NOT EXISTS idx_product_reviews_product_status ON product_reviews(product_id, status)"
        ];
        
        foreach ($optimizations as $sql) {
            try {
                $this->database->execute($sql);
                echo "  ✓ Applied: " . substr($sql, 0, 50) . "...\n";
            } catch (Exception $e) {
                echo "  ✗ Failed: " . $e->getMessage() . "\n";
            }
        }
    }
    
    /**
     * Clean up old data
     */
    public function cleanupData() {
        echo "\nCleaning up old data...\n";
        
        $cleanups = [
            "DELETE FROM activity_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL 90 DAY)",
            "DELETE FROM sessions WHERE last_activity < UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL 30 DAY))",
            "DELETE FROM password_resets WHERE created_at < DATE_SUB(NOW(), INTERVAL 24 HOUR)"
        ];
        
        foreach ($cleanups as $sql) {
            try {
                $affected = $this->database->execute($sql);
                echo "  ✓ Cleaned up $affected records\n";
            } catch (Exception $e) {
                echo "  ✗ Cleanup failed: " . $e->getMessage() . "\n";
            }
        }
    }
}

// Run performance analysis if called directly
if (basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'])) {
    $optimizer = new PerformanceOptimizer();
    
    // Run analysis
    $results = $optimizer->runAnalysis();
    
    // Generate report
    $report = $optimizer->generateReport();
    
    echo "\n=============================\n";
    echo "Performance analysis complete!\n";
    echo "Report saved to: performance_report.json\n";
    
    // Ask for optimization
    echo "\nWould you like to apply optimizations? (y/n): ";
    $handle = fopen("php://stdin", "r");
    $input = trim(fgets($handle));
    fclose($handle);
    
    if (strtolower($input) === 'y') {
        $optimizer->optimizeDatabase();
        $optimizer->cleanupData();
        echo "\nOptimizations applied!\n";
    }
}
?>
