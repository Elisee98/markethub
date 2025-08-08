<?php
/**
 * MarketHub Utility Functions
 * Multi-Vendor E-Commerce Platform
 */

/**
 * Sanitize input data
 */
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

/**
 * Validate email address
 */
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

/**
 * Generate secure password hash
 */
function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

/**
 * Verify password against hash
 */
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

/**
 * Generate random string
 */
function generateRandomString($length = 10) {
    return bin2hex(random_bytes($length / 2));
}

/**
 * Upload file with validation
 */
function uploadFile($file, $upload_dir, $allowed_types = [], $max_size = MAX_FILE_SIZE) {
    if (!isset($file['error']) || is_array($file['error'])) {
        throw new RuntimeException('Invalid parameters.');
    }

    switch ($file['error']) {
        case UPLOAD_ERR_OK:
            break;
        case UPLOAD_ERR_NO_FILE:
            throw new RuntimeException('No file sent.');
        case UPLOAD_ERR_INI_SIZE:
        case UPLOAD_ERR_FORM_SIZE:
            throw new RuntimeException('Exceeded filesize limit.');
        default:
            throw new RuntimeException('Unknown errors.');
    }

    if ($file['size'] > $max_size) {
        throw new RuntimeException('Exceeded filesize limit.');
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime_type = $finfo->file($file['tmp_name']);
    
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    if (!empty($allowed_types) && !in_array($extension, $allowed_types)) {
        throw new RuntimeException('Invalid file format.');
    }

    $filename = sprintf('%s.%s', generateRandomString(20), $extension);
    $filepath = $upload_dir . '/' . $filename;

    if (!move_uploaded_file($file['tmp_name'], $filepath)) {
        throw new RuntimeException('Failed to move uploaded file.');
    }

    return $filename;
}

/**
 * Resize image
 */
function resizeImage($source, $destination, $width, $height, $quality = 90) {
    $info = getimagesize($source);
    $mime = $info['mime'];

    switch ($mime) {
        case 'image/jpeg':
            $image = imagecreatefromjpeg($source);
            break;
        case 'image/png':
            $image = imagecreatefrompng($source);
            break;
        case 'image/gif':
            $image = imagecreatefromgif($source);
            break;
        default:
            throw new Exception('Unsupported image type');
    }

    $original_width = imagesx($image);
    $original_height = imagesy($image);

    $ratio = min($width / $original_width, $height / $original_height);
    $new_width = $original_width * $ratio;
    $new_height = $original_height * $ratio;

    $new_image = imagecreatetruecolor($new_width, $new_height);
    
    if ($mime == 'image/png') {
        imagealphablending($new_image, false);
        imagesavealpha($new_image, true);
    }

    imagecopyresampled($new_image, $image, 0, 0, 0, 0, $new_width, $new_height, $original_width, $original_height);

    switch ($mime) {
        case 'image/jpeg':
            imagejpeg($new_image, $destination, $quality);
            break;
        case 'image/png':
            imagepng($new_image, $destination);
            break;
        case 'image/gif':
            imagegif($new_image, $destination);
            break;
    }

    imagedestroy($image);
    imagedestroy($new_image);
}

/**
 * Send email
 */
function sendEmail($to, $subject, $message, $from = ADMIN_EMAIL) {
    // For development environment, log emails instead of sending
    if (defined('DEVELOPMENT_MODE') && DEVELOPMENT_MODE) {
        return logEmailForDevelopment($to, $subject, $message, $from);
    }

    try {
        $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
        $headers .= "From: " . SITE_NAME . " <" . $from . ">" . "\r\n";

        return mail($to, $subject, $message, $headers);
    } catch (Exception $e) {
        // Log error and return false
        error_log("Email sending failed: " . $e->getMessage());
        return false;
    }
}

/**
 * Log email for development environment
 */
function logEmailForDevelopment($to, $subject, $message, $from = ADMIN_EMAIL) {
    $timestamp = date('Y-m-d H:i:s');

    $emailLog = "\n" . str_repeat("=", 80) . "\n";
    $emailLog .= "EMAIL LOG - $timestamp\n";
    $emailLog .= str_repeat("=", 80) . "\n";
    $emailLog .= "TO: $to\n";
    $emailLog .= "FROM: $from\n";
    $emailLog .= "SUBJECT: $subject\n";
    $emailLog .= str_repeat("-", 80) . "\n";
    $emailLog .= "MESSAGE:\n";
    $emailLog .= strip_tags($message) . "\n";
    $emailLog .= str_repeat("=", 80) . "\n\n";

    // Log to file
    $logFile = __DIR__ . '/../logs/email.log';
    $logDir = dirname($logFile);

    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }

    file_put_contents($logFile, $emailLog, FILE_APPEND | LOCK_EX);

    // Also log to PHP error log for immediate visibility
    error_log("EMAIL LOGGED: To: $to, Subject: $subject");

    return true; // Return true to simulate successful sending
}

/**
 * Register a new user
 */
function registerUser($username, $email, $password, $first_name, $last_name, $phone = '', $user_type = 'customer') {
    global $database;

    // Check if user already exists
    $existing_user = $database->fetch(
        "SELECT id FROM users WHERE email = ? OR username = ?",
        [$email, $username]
    );

    if ($existing_user) {
        throw new Exception('A user with this email or username already exists.');
    }

    // Hash password
    $password_hash = password_hash($password, PASSWORD_DEFAULT);

    // Set initial status based on configuration
    $initial_status = REQUIRE_ADMIN_APPROVAL ? 'pending' : 'active';

    // Insert user
    $sql = "INSERT INTO users (username, email, password_hash, first_name, last_name, phone,
                              user_type, status, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";

    $user_id = $database->insert($sql, [
        $username, $email, $password_hash, $first_name, $last_name,
        $phone, $user_type, $initial_status
    ]);

    // Log activity
    logActivity($user_id, 'user_registered', "User registered as $user_type");

    return $user_id;
}

/**
 * Log activity
 */
function logActivity($user_id, $action, $details = '') {
    global $database;

    try {
        // Check if activity_logs table exists
        $database->fetch("SELECT 1 FROM activity_logs LIMIT 1");

        // Validate user_id exists before logging
        if ($user_id) {
            $user_exists = $database->fetch("SELECT id FROM users WHERE id = ?", [$user_id]);
            if (!$user_exists) {
                // User doesn't exist, skip logging
                return false;
            }
        }

        $sql = "INSERT INTO activity_logs (user_id, action, description, ip_address, user_agent, created_at)
                VALUES (?, ?, ?, ?, ?, NOW())";

        $params = [
            $user_id,
            $action,
            $details,
            $_SERVER['REMOTE_ADDR'] ?? '',
            $_SERVER['HTTP_USER_AGENT'] ?? ''
        ];

        return $database->execute($sql, $params);
    } catch (Exception $e) {
        // Log error but don't break the application
        error_log("Activity logging failed: " . $e->getMessage());
        // Table might not exist yet, which is okay during initial setup
        return false;
    }
}

/**
 * Get user by ID
 */
function getUserById($user_id) {
    global $database;
    
    $sql = "SELECT * FROM users WHERE id = ? AND status = 'active'";
    return $database->fetch($sql, [$user_id]);
}

/**
 * Get product by ID with vendor info
 */
function getProductById($product_id) {
    global $database;
    
    $sql = "
        SELECT p.*, u.username as vendor_name, vs.store_name, vs.store_logo,
               AVG(pr.rating) as avg_rating, COUNT(pr.id) as review_count
        FROM products p
        LEFT JOIN users u ON p.vendor_id = u.id
        LEFT JOIN vendor_stores vs ON u.id = vs.vendor_id
        LEFT JOIN product_reviews pr ON p.id = pr.product_id AND pr.status = 'approved'
        WHERE p.id = ? AND p.status = 'active'
        GROUP BY p.id
    ";
    
    return $database->fetch($sql, [$product_id]);
}

/**
 * Get product images
 */
function getProductImages($product_id) {
    global $database;
    
    $sql = "SELECT * FROM product_images WHERE product_id = ? ORDER BY is_primary DESC, sort_order";
    return $database->fetchAll($sql, [$product_id]);
}

/**
 * Calculate shipping cost
 */
function calculateShipping($weight, $distance = 0) {
    $base_rate = 1000; // Base shipping rate in RWF
    $weight_rate = 100; // Per kg rate
    $distance_rate = 50; // Per km rate
    
    $shipping_cost = $base_rate + ($weight * $weight_rate) + ($distance * $distance_rate);
    
    return max($shipping_cost, $base_rate);
}

/**
 * Calculate tax
 */
function calculateTax($amount, $tax_rate = 18) {
    return ($amount * $tax_rate) / 100;
}

/**
 * Generate order number
 */
function generateOrderNumber() {
    return 'MH' . date('Y') . date('m') . date('d') . strtoupper(generateRandomString(6));
}

/**
 * Get cart total
 */
function getCartTotal($cart_items) {
    global $database;
    
    if (empty($cart_items)) {
        return 0;
    }
    
    $product_ids = array_keys($cart_items);
    $placeholders = str_repeat('?,', count($product_ids) - 1) . '?';
    
    $sql = "SELECT id, price FROM products WHERE id IN ($placeholders) AND status = 'active'";
    $products = $database->fetchAll($sql, $product_ids);
    
    $total = 0;
    foreach ($products as $product) {
        $quantity = $cart_items[$product['id']];
        $total += $product['price'] * $quantity;
    }
    
    return $total;
}

/**
 * Check if user is logged in
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Check if user has role
 */
function hasRole($role) {
    return isset($_SESSION['user_type']) && $_SESSION['user_type'] === $role;
}

/**
 * Require login
 */
function requireLogin() {
    if (!isLoggedIn()) {
        redirect('login.php?redirect=' . urlencode(getCurrentUrl()));
    }
}

/**
 * Require role
 */
function requireRole($role) {
    requireLogin();
    if (!hasRole($role)) {
        redirect('unauthorized.php');
    }
}

/**
 * Get pagination data
 */
function getPagination($total_records, $records_per_page, $current_page = 1) {
    $total_pages = ceil($total_records / $records_per_page);
    $offset = ($current_page - 1) * $records_per_page;
    
    return [
        'total_records' => $total_records,
        'total_pages' => $total_pages,
        'current_page' => $current_page,
        'records_per_page' => $records_per_page,
        'offset' => $offset,
        'has_previous' => $current_page > 1,
        'has_next' => $current_page < $total_pages
    ];
}

/**
 * Render pagination links
 */
function renderPagination($pagination, $base_url) {
    if ($pagination['total_pages'] <= 1) {
        return '';
    }
    
    $html = '<nav class="pagination-nav"><ul class="pagination">';
    
    // Previous button
    if ($pagination['has_previous']) {
        $prev_page = $pagination['current_page'] - 1;
        $html .= '<li><a href="' . $base_url . '&page=' . $prev_page . '" class="btn btn-outline btn-sm">Previous</a></li>';
    }
    
    // Page numbers
    $start = max(1, $pagination['current_page'] - 2);
    $end = min($pagination['total_pages'], $pagination['current_page'] + 2);
    
    for ($i = $start; $i <= $end; $i++) {
        $active = $i == $pagination['current_page'] ? 'btn-primary' : 'btn-outline';
        $html .= '<li><a href="' . $base_url . '&page=' . $i . '" class="btn ' . $active . ' btn-sm">' . $i . '</a></li>';
    }
    
    // Next button
    if ($pagination['has_next']) {
        $next_page = $pagination['current_page'] + 1;
        $html .= '<li><a href="' . $base_url . '&page=' . $next_page . '" class="btn btn-outline btn-sm">Next</a></li>';
    }
    
    $html .= '</ul></nav>';
    
    return $html;
}
?>
