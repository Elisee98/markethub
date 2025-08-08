<?php
/**
 * MarketHub Test Runner
 * Multi-Vendor E-Commerce Platform Testing Framework
 */

require_once '../config/config.php';

class TestRunner {
    private $tests = [];
    private $results = [];
    private $database;
    
    public function __construct() {
        global $database;
        $this->database = $database;
    }
    
    /**
     * Add a test to the test suite
     */
    public function addTest($name, $callback) {
        $this->tests[$name] = $callback;
    }
    
    /**
     * Run all tests
     */
    public function runAllTests() {
        echo "MarketHub Test Suite\n";
        echo "===================\n\n";
        
        $passed = 0;
        $failed = 0;
        $start_time = microtime(true);
        
        foreach ($this->tests as $name => $callback) {
            echo "Running: $name... ";
            
            try {
                $test_start = microtime(true);
                $result = call_user_func($callback);
                $test_time = round((microtime(true) - $test_start) * 1000, 2);
                
                if ($result === true) {
                    echo "PASS ({$test_time}ms)\n";
                    $passed++;
                    $this->results[$name] = ['status' => 'PASS', 'time' => $test_time];
                } else {
                    echo "FAIL ({$test_time}ms)\n";
                    if (is_string($result)) {
                        echo "  Error: $result\n";
                    }
                    $failed++;
                    $this->results[$name] = ['status' => 'FAIL', 'time' => $test_time, 'error' => $result];
                }
            } catch (Exception $e) {
                echo "ERROR\n";
                echo "  Exception: " . $e->getMessage() . "\n";
                $failed++;
                $this->results[$name] = ['status' => 'ERROR', 'error' => $e->getMessage()];
            }
        }
        
        $total_time = round((microtime(true) - $start_time) * 1000, 2);
        
        echo "\n===================\n";
        echo "Test Results:\n";
        echo "Passed: $passed\n";
        echo "Failed: $failed\n";
        echo "Total: " . ($passed + $failed) . "\n";
        echo "Time: {$total_time}ms\n";
        
        return $failed === 0;
    }
    
    /**
     * Assert that a condition is true
     */
    public function assertTrue($condition, $message = '') {
        if (!$condition) {
            throw new Exception($message ?: 'Assertion failed');
        }
        return true;
    }
    
    /**
     * Assert that two values are equal
     */
    public function assertEqual($expected, $actual, $message = '') {
        if ($expected !== $actual) {
            $msg = $message ?: "Expected '$expected', got '$actual'";
            throw new Exception($msg);
        }
        return true;
    }
    
    /**
     * Assert that a value is not null
     */
    public function assertNotNull($value, $message = '') {
        if ($value === null) {
            throw new Exception($message ?: 'Value should not be null');
        }
        return true;
    }
    
    /**
     * Assert that an array contains a key
     */
    public function assertArrayHasKey($key, $array, $message = '') {
        if (!array_key_exists($key, $array)) {
            throw new Exception($message ?: "Array does not contain key '$key'");
        }
        return true;
    }
    
    /**
     * Create test user
     */
    public function createTestUser($type = 'customer', $email = null) {
        $email = $email ?: 'test_' . uniqid() . '@example.com';
        $password = password_hash('testpass123', PASSWORD_DEFAULT);
        
        $sql = "INSERT INTO users (first_name, last_name, email, password_hash, user_type, status, created_at) 
                VALUES (?, ?, ?, ?, ?, 'active', NOW())";
        
        $user_id = $this->database->insert($sql, [
            'Test', 'User', $email, $password, $type
        ]);
        
        return [
            'id' => $user_id,
            'email' => $email,
            'password' => 'testpass123',
            'type' => $type
        ];
    }
    
    /**
     * Create test product
     */
    public function createTestProduct($vendor_id, $category_id = 1) {
        $sql = "INSERT INTO products (vendor_id, category_id, name, description, price, stock_quantity, status, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, 'active', NOW())";
        
        $product_id = $this->database->insert($sql, [
            $vendor_id, $category_id, 'Test Product', 'Test Description', 99.99, 10
        ]);
        
        return $product_id;
    }
    
    /**
     * Clean up test data
     */
    public function cleanup() {
        // Remove test users and related data
        $this->database->execute("DELETE FROM users WHERE email LIKE 'test_%@example.com'");
        $this->database->execute("DELETE FROM products WHERE name = 'Test Product'");
        $this->database->execute("DELETE FROM orders WHERE order_number LIKE 'TEST-%'");
    }
    
    /**
     * Generate test report
     */
    public function generateReport() {
        $report = [
            'timestamp' => date('Y-m-d H:i:s'),
            'total_tests' => count($this->results),
            'passed' => count(array_filter($this->results, function($r) { return $r['status'] === 'PASS'; })),
            'failed' => count(array_filter($this->results, function($r) { return $r['status'] !== 'PASS'; })),
            'results' => $this->results
        ];
        
        file_put_contents('test_report.json', json_encode($report, JSON_PRETTY_PRINT));
        return $report;
    }
}

// Database Tests
function testDatabaseConnection() {
    global $database;
    try {
        $result = $database->fetch("SELECT 1 as test");
        return $result['test'] === 1;
    } catch (Exception $e) {
        return "Database connection failed: " . $e->getMessage();
    }
}

function testUserRegistration() {
    $runner = new TestRunner();
    
    // Test user creation
    $user = $runner->createTestUser('customer');
    $runner->assertNotNull($user['id'], 'User ID should not be null');
    
    // Test user retrieval
    global $database;
    $retrieved_user = $database->fetch("SELECT * FROM users WHERE id = ?", [$user['id']]);
    $runner->assertNotNull($retrieved_user, 'User should be retrievable');
    $runner->assertEqual($user['email'], $retrieved_user['email'], 'Email should match');
    
    return true;
}

function testProductManagement() {
    $runner = new TestRunner();
    
    // Create test vendor
    $vendor = $runner->createTestUser('vendor');
    
    // Create test product
    $product_id = $runner->createTestProduct($vendor['id']);
    $runner->assertNotNull($product_id, 'Product ID should not be null');
    
    // Test product retrieval
    global $database;
    $product = $database->fetch("SELECT * FROM products WHERE id = ?", [$product_id]);
    $runner->assertNotNull($product, 'Product should be retrievable');
    $runner->assertEqual('Test Product', $product['name'], 'Product name should match');
    
    return true;
}

function testOrderProcessing() {
    $runner = new TestRunner();
    
    // Create test users and product
    $customer = $runner->createTestUser('customer');
    $vendor = $runner->createTestUser('vendor');
    $product_id = $runner->createTestProduct($vendor['id']);
    
    global $database;
    
    // Create test order
    $order_number = 'TEST-' . uniqid();
    $order_sql = "INSERT INTO orders (customer_id, order_number, total_amount, subtotal, shipping_cost, tax_amount, payment_method, payment_status, status, created_at) 
                  VALUES (?, ?, 99.99, 84.74, 5.00, 10.25, 'credit_card', 'paid', 'confirmed', NOW())";
    
    $order_id = $database->insert($order_sql, [$customer['id'], $order_number]);
    $runner->assertNotNull($order_id, 'Order ID should not be null');
    
    // Create order item
    $item_sql = "INSERT INTO order_items (order_id, product_id, vendor_id, quantity, unit_price, total_price, created_at) 
                 VALUES (?, ?, ?, 1, 99.99, 99.99, NOW())";
    
    $item_id = $database->insert($item_sql, [$order_id, $product_id, $vendor['id']]);
    $runner->assertNotNull($item_id, 'Order item ID should not be null');
    
    return true;
}

function testAPIEndpoints() {
    $runner = new TestRunner();
    
    // Test cart API
    $cart_response = file_get_contents('http://localhost/markethub/api/cart.php', false, stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => 'Content-Type: application/json',
            'content' => json_encode(['action' => 'get'])
        ]
    ]));
    
    $cart_data = json_decode($cart_response, true);
    $runner->assertArrayHasKey('success', $cart_data, 'Cart API should return success field');
    
    return true;
}

function testSecurityFeatures() {
    $runner = new TestRunner();
    
    // Test CSRF token generation
    $token1 = generateCSRFToken();
    $token2 = generateCSRFToken();
    $runner->assertNotNull($token1, 'CSRF token should not be null');
    $runner->assertTrue(strlen($token1) > 10, 'CSRF token should be sufficiently long');
    
    // Test input sanitization
    $dirty_input = '<script>alert("xss")</script>';
    $clean_input = sanitizeInput($dirty_input);
    $runner->assertTrue(strpos($clean_input, '<script>') === false, 'Script tags should be removed');
    
    // Test password hashing
    $password = 'testpassword123';
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $runner->assertTrue(password_verify($password, $hash), 'Password should verify correctly');
    
    return true;
}

function testPerformance() {
    $runner = new TestRunner();
    global $database;
    
    // Test database query performance
    $start_time = microtime(true);
    $result = $database->fetchAll("SELECT * FROM products LIMIT 100");
    $query_time = microtime(true) - $start_time;
    
    $runner->assertTrue($query_time < 1.0, 'Database query should complete within 1 second');
    $runner->assertTrue(count($result) <= 100, 'Query should return expected number of results');
    
    return true;
}

function testEmailFunctionality() {
    $runner = new TestRunner();
    
    // Test email validation
    $valid_email = 'test@example.com';
    $invalid_email = 'invalid-email';
    
    $runner->assertTrue(validateEmailFormat($valid_email), 'Valid email should pass validation');
    $runner->assertTrue(!validateEmailFormat($invalid_email), 'Invalid email should fail validation');
    
    return true;
}

function testFileUpload() {
    $runner = new TestRunner();
    
    // Test file validation
    $valid_extensions = ['jpg', 'jpeg', 'png', 'gif'];
    $test_filename = 'test.jpg';
    
    $extension = strtolower(pathinfo($test_filename, PATHINFO_EXTENSION));
    $runner->assertTrue(in_array($extension, $valid_extensions), 'Valid file extension should be accepted');
    
    return true;
}

// Run tests if called directly
if (basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'])) {
    $runner = new TestRunner();
    
    // Add all tests
    $runner->addTest('Database Connection', 'testDatabaseConnection');
    $runner->addTest('User Registration', 'testUserRegistration');
    $runner->addTest('Product Management', 'testProductManagement');
    $runner->addTest('Order Processing', 'testOrderProcessing');
    $runner->addTest('API Endpoints', 'testAPIEndpoints');
    $runner->addTest('Security Features', 'testSecurityFeatures');
    $runner->addTest('Performance', 'testPerformance');
    $runner->addTest('Email Functionality', 'testEmailFunctionality');
    $runner->addTest('File Upload', 'testFileUpload');
    
    // Run tests
    $success = $runner->runAllTests();
    
    // Generate report
    $report = $runner->generateReport();
    
    // Cleanup
    $runner->cleanup();
    
    // Exit with appropriate code
    exit($success ? 0 : 1);
}
?>
