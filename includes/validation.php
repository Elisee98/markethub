<?php
/**
 * MarketHub Validation Functions
 * Multi-Vendor E-Commerce Platform
 */

/**
 * Validate required fields
 */
function validateRequired($fields, $data) {
    $errors = [];
    
    foreach ($fields as $field => $label) {
        if (!isset($data[$field]) || empty(trim($data[$field]))) {
            $errors[$field] = "$label is required";
        }
    }
    
    return $errors;
}

/**
 * Validate email format
 */
function validateEmailFormat($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Validate password strength
 */
function validatePassword($password) {
    $errors = [];
    
    if (strlen($password) < 6) {
        $errors[] = "Password must be at least 6 characters long";
    }
    
    if (!preg_match('/[A-Za-z]/', $password)) {
        $errors[] = "Password must contain at least one letter";
    }
    
    if (!preg_match('/[0-9]/', $password)) {
        $errors[] = "Password must contain at least one number";
    }
    
    return $errors;
}

/**
 * Validate phone number
 */
function validatePhone($phone) {
    // Rwanda phone number format: +250XXXXXXXXX or 07XXXXXXXX
    $pattern = '/^(\+250|0)[7][0-9]{8}$/';
    return preg_match($pattern, $phone);
}

/**
 * Validate username
 */
function validateUsername($username) {
    $errors = [];
    
    if (strlen($username) < 3) {
        $errors[] = "Username must be at least 3 characters long";
    }
    
    if (strlen($username) > 50) {
        $errors[] = "Username must not exceed 50 characters";
    }
    
    if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        $errors[] = "Username can only contain letters, numbers, and underscores";
    }
    
    return $errors;
}

/**
 * Validate price
 */
function validatePrice($price) {
    return is_numeric($price) && $price >= 0;
}

/**
 * Validate quantity
 */
function validateQuantity($quantity) {
    return is_numeric($quantity) && $quantity >= 0 && $quantity == floor($quantity);
}

/**
 * Validate file upload
 */
function validateFileUpload($file, $allowed_types = [], $max_size = MAX_FILE_SIZE) {
    $errors = [];
    
    if (!isset($file['error']) || is_array($file['error'])) {
        $errors[] = "Invalid file upload";
        return $errors;
    }
    
    switch ($file['error']) {
        case UPLOAD_ERR_OK:
            break;
        case UPLOAD_ERR_NO_FILE:
            $errors[] = "No file was uploaded";
            return $errors;
        case UPLOAD_ERR_INI_SIZE:
        case UPLOAD_ERR_FORM_SIZE:
            $errors[] = "File size exceeds limit";
            return $errors;
        default:
            $errors[] = "Unknown upload error";
            return $errors;
    }
    
    if ($file['size'] > $max_size) {
        $errors[] = "File size exceeds " . formatBytes($max_size) . " limit";
    }
    
    if (!empty($allowed_types)) {
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, $allowed_types)) {
            $errors[] = "File type not allowed. Allowed types: " . implode(', ', $allowed_types);
        }
    }
    
    return $errors;
}

/**
 * Validate image file
 */
function validateImage($file) {
    $errors = validateFileUpload($file, ALLOWED_IMAGE_TYPES);
    
    if (empty($errors)) {
        $imageInfo = getimagesize($file['tmp_name']);
        if ($imageInfo === false) {
            $errors[] = "File is not a valid image";
        }
    }
    
    return $errors;
}

/**
 * Validate URL
 */
function validateUrl($url) {
    return filter_var($url, FILTER_VALIDATE_URL) !== false;
}

/**
 * Validate date format
 */
function validateDate($date, $format = 'Y-m-d') {
    $d = DateTime::createFromFormat($format, $date);
    return $d && $d->format($format) === $date;
}

/**
 * Validate product data
 */
function validateProductData($data) {
    $errors = [];
    
    // Required fields
    $required = [
        'name' => 'Product name',
        'description' => 'Product description',
        'price' => 'Price',
        'category_id' => 'Category',
        'sku' => 'SKU'
    ];
    
    $errors = array_merge($errors, validateRequired($required, $data));
    
    // Price validation
    if (isset($data['price']) && !validatePrice($data['price'])) {
        $errors['price'] = "Invalid price format";
    }
    
    // Stock quantity validation
    if (isset($data['stock_quantity']) && !validateQuantity($data['stock_quantity'])) {
        $errors['stock_quantity'] = "Invalid stock quantity";
    }
    
    // SKU validation
    if (isset($data['sku']) && !preg_match('/^[A-Z0-9-_]+$/i', $data['sku'])) {
        $errors['sku'] = "SKU can only contain letters, numbers, hyphens, and underscores";
    }
    
    return $errors;
}

/**
 * Validate user registration data
 */
function validateUserRegistration($data) {
    $errors = [];
    
    // Required fields
    $required = [
        'username' => 'Username',
        'email' => 'Email',
        'password' => 'Password',
        'first_name' => 'First name',
        'last_name' => 'Last name'
    ];
    
    $errors = array_merge($errors, validateRequired($required, $data));
    
    // Username validation
    if (isset($data['username'])) {
        $username_errors = validateUsername($data['username']);
        if (!empty($username_errors)) {
            $errors['username'] = implode(', ', $username_errors);
        }
    }
    
    // Email validation
    if (isset($data['email']) && !validateEmailFormat($data['email'])) {
        $errors['email'] = "Invalid email format";
    }
    
    // Password validation
    if (isset($data['password'])) {
        $password_errors = validatePassword($data['password']);
        if (!empty($password_errors)) {
            $errors['password'] = implode(', ', $password_errors);
        }
    }
    
    // Phone validation (if provided)
    if (isset($data['phone']) && !empty($data['phone']) && !validatePhone($data['phone'])) {
        $errors['phone'] = "Invalid phone number format";
    }
    
    return $errors;
}

/**
 * Validate vendor store data
 */
function validateVendorStore($data) {
    $errors = [];
    
    // Required fields
    $required = [
        'store_name' => 'Store name',
        'store_description' => 'Store description',
        'business_license' => 'Business license',
        'address' => 'Address',
        'city' => 'City',
        'phone' => 'Phone number',
        'email' => 'Email'
    ];
    
    $errors = array_merge($errors, validateRequired($required, $data));
    
    // Email validation
    if (isset($data['email']) && !validateEmailFormat($data['email'])) {
        $errors['email'] = "Invalid email format";
    }
    
    // Phone validation
    if (isset($data['phone']) && !validatePhone($data['phone'])) {
        $errors['phone'] = "Invalid phone number format";
    }
    
    // Website validation (if provided)
    if (isset($data['website']) && !empty($data['website']) && !validateUrl($data['website'])) {
        $errors['website'] = "Invalid website URL";
    }
    
    return $errors;
}

/**
 * Validate order data
 */
function validateOrderData($data) {
    $errors = [];
    
    // Required fields
    $required = [
        'billing_address' => 'Billing address',
        'shipping_address' => 'Shipping address'
    ];
    
    $errors = array_merge($errors, validateRequired($required, $data));
    
    // Validate amounts
    if (isset($data['subtotal']) && !validatePrice($data['subtotal'])) {
        $errors['subtotal'] = "Invalid subtotal amount";
    }
    
    if (isset($data['total_amount']) && !validatePrice($data['total_amount'])) {
        $errors['total_amount'] = "Invalid total amount";
    }
    
    return $errors;
}

/**
 * Validate review data
 */
function validateReviewData($data) {
    $errors = [];
    
    // Required fields
    $required = [
        'rating' => 'Rating',
        'review_text' => 'Review text'
    ];
    
    $errors = array_merge($errors, validateRequired($required, $data));
    
    // Rating validation
    if (isset($data['rating'])) {
        $rating = intval($data['rating']);
        if ($rating < 1 || $rating > 5) {
            $errors['rating'] = "Rating must be between 1 and 5";
        }
    }
    
    return $errors;
}

/**
 * Format bytes for display
 */
function formatBytes($bytes, $precision = 2) {
    $units = array('B', 'KB', 'MB', 'GB', 'TB');
    
    for ($i = 0; $bytes > 1024; $i++) {
        $bytes /= 1024;
    }
    
    return round($bytes, $precision) . ' ' . $units[$i];
}

/**
 * Clean and validate input data
 */
function cleanInput($data) {
    if (is_array($data)) {
        return array_map('cleanInput', $data);
    }
    
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

/**
 * Validate CSRF token
 */
function validateCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}
?>
