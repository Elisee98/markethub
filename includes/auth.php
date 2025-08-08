<?php
/**
 * MarketHub Authentication System
 * Multi-Vendor E-Commerce Platform
 */

// registerUser function is now defined in includes/functions.php

/**
 * User login
 */
function loginUser($username_or_email, $password, $remember_me = false) {
    global $database;
    
    // Get user by username or email (check all statuses)
    $sql = "SELECT * FROM users WHERE (username = ? OR email = ?)";
    $user = $database->fetch($sql, [$username_or_email, $username_or_email]);
    
    if (!$user) {
        throw new Exception('Invalid credentials');
    }
    
    // Verify password
    if (!verifyPassword($password, $user['password_hash'])) {
        throw new Exception('Invalid credentials');
    }

    // Check user status
    if ($user['status'] === 'pending') {
        throw new Exception('Your account is pending admin approval. You will receive an email notification once approved.');
    } elseif ($user['status'] === 'rejected') {
        throw new Exception('Your account application was rejected. Please contact support for more information.');
    } elseif ($user['status'] !== 'active') {
        throw new Exception('Your account is not active. Please contact support.');
    }

    // Create session
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_type'] = $user['user_type'];
    $_SESSION['login_time'] = time();
    
    // Handle remember me
    if ($remember_me) {
        $token = generateRandomString(32);
        $expires = time() + (30 * 24 * 60 * 60); // 30 days
        
        // Store token in database
        $sql = "INSERT INTO user_tokens (user_id, token, type, expires_at, created_at) 
                VALUES (?, ?, 'remember_me', FROM_UNIXTIME(?), NOW())";
        $database->execute($sql, [$user['id'], $token, $expires]);
        
        // Set cookie
        setcookie('remember_token', $token, $expires, '/', '', false, true);
    }
    
    // Update last login
    $sql = "UPDATE users SET updated_at = NOW() WHERE id = ?";
    $database->execute($sql, [$user['id']]);
    
    // Log login activity
    logActivity($user['id'], 'user_login', 'Successful login');
    
    return $user;
}

/**
 * User logout
 */
function logoutUser() {
    if (isset($_SESSION['user_id'])) {
        // Log logout activity
        logActivity($_SESSION['user_id'], 'user_logout', 'User logged out');
        
        // Remove remember me token if exists
        if (isset($_COOKIE['remember_token'])) {
            global $database;
            $sql = "DELETE FROM user_tokens WHERE token = ? AND type = 'remember_me'";
            $database->execute($sql, [$_COOKIE['remember_token']]);
            
            // Clear cookie
            setcookie('remember_token', '', time() - 3600, '/', '', false, true);
        }
    }
    
    // Destroy session
    session_destroy();
    
    // Clear session variables
    $_SESSION = array();
}

/**
 * Check remember me token
 */
function checkRememberMe() {
    if (!isLoggedIn() && isset($_COOKIE['remember_token'])) {
        global $database;
        
        $sql = "SELECT ut.*, u.* FROM user_tokens ut 
                JOIN users u ON ut.user_id = u.id 
                WHERE ut.token = ? AND ut.type = 'remember_me' 
                AND ut.expires_at > NOW() AND u.status = 'active'";
        
        $result = $database->fetch($sql, [$_COOKIE['remember_token']]);
        
        if ($result) {
            // Auto login user
            $_SESSION['user_id'] = $result['user_id'];
            $_SESSION['user_name'] = $result['first_name'] . ' ' . $result['last_name'];
            $_SESSION['username'] = $result['username'];
            $_SESSION['user_email'] = $result['email'];
            $_SESSION['user_type'] = $result['user_type'];
            $_SESSION['login_time'] = time();
            
            // Auto login successful (activity logging removed)
            
            return true;
        } else {
            // Invalid or expired token, clear cookie
            setcookie('remember_token', '', time() - 3600, '/', '', false, true);
        }
    }
    
    return false;
}

/**
 * Password reset request
 */
function requestPasswordReset($email) {
    global $database;
    
    // Check if user exists
    $sql = "SELECT id, first_name FROM users WHERE email = ? AND status = 'active'";
    $user = $database->fetch($sql, [$email]);
    
    if (!$user) {
        throw new Exception('Email address not found');
    }
    
    // Generate reset token
    $token = generateRandomString(32);
    $expires = time() + (24 * 60 * 60); // 24 hours
    
    // Store token in database
    $sql = "INSERT INTO user_tokens (user_id, token, type, expires_at, created_at) 
            VALUES (?, ?, 'password_reset', FROM_UNIXTIME(?), NOW())";
    $database->execute($sql, [$user['id'], $token, $expires]);
    
    // Send reset email
    $reset_url = SITE_URL . 'reset-password.php?token=' . $token;
    $subject = "Password Reset Request - " . SITE_NAME;
    $message = "
        <h2>Password Reset Request</h2>
        <p>Dear {$user['first_name']},</p>
        <p>You have requested to reset your password. Click the link below to reset your password:</p>
        <p><a href='$reset_url' style='background: " . PRIMARY_COLOR . "; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Reset Password</a></p>
        <p>This link will expire in 24 hours.</p>
        <p>If you did not request this reset, please ignore this email.</p>
        <p>Best regards,<br>The MarketHub Team</p>
    ";
    
    if (sendEmail($email, $subject, $message)) {
        // Password reset requested (activity logging removed)
        return true;
    }
    
    throw new Exception('Failed to send reset email');
}

/**
 * Reset password with token
 */
function resetPassword($token, $new_password) {
    global $database;
    
    if (strlen($new_password) < 6) {
        throw new Exception('Password must be at least 6 characters long');
    }
    
    // Verify token
    $sql = "SELECT ut.*, u.email FROM user_tokens ut 
            JOIN users u ON ut.user_id = u.id 
            WHERE ut.token = ? AND ut.type = 'password_reset' 
            AND ut.expires_at > NOW()";
    
    $result = $database->fetch($sql, [$token]);
    
    if (!$result) {
        throw new Exception('Invalid or expired reset token');
    }
    
    // Hash new password
    $password_hash = hashPassword($new_password);
    
    // Update password
    $sql = "UPDATE users SET password_hash = ?, updated_at = NOW() WHERE id = ?";
    $database->execute($sql, [$password_hash, $result['user_id']]);
    
    // Delete used token
    $sql = "DELETE FROM user_tokens WHERE token = ?";
    $database->execute($sql, [$token]);
    
    // Log password change
    logActivity($result['user_id'], 'password_reset', 'Password reset successfully');
    
    // Send confirmation email
    $subject = "Password Changed - " . SITE_NAME;
    $message = "
        <h2>Password Changed</h2>
        <p>Your password has been successfully changed.</p>
        <p>If you did not make this change, please contact us immediately.</p>
        <p>Best regards,<br>The MarketHub Team</p>
    ";
    
    sendEmail($result['email'], $subject, $message);
    
    return true;
}

/**
 * Change password (for logged in users)
 */
function changePassword($user_id, $current_password, $new_password) {
    global $database;
    
    // Get current user
    $user = getUserById($user_id);
    if (!$user) {
        throw new Exception('User not found');
    }
    
    // Verify current password
    if (!verifyPassword($current_password, $user['password_hash'])) {
        throw new Exception('Current password is incorrect');
    }
    
    if (strlen($new_password) < 6) {
        throw new Exception('New password must be at least 6 characters long');
    }
    
    // Hash new password
    $password_hash = hashPassword($new_password);
    
    // Update password
    $sql = "UPDATE users SET password_hash = ?, updated_at = NOW() WHERE id = ?";
    $database->execute($sql, [$password_hash, $user_id]);
    
    // Log password change
    logActivity($user_id, 'password_changed', 'Password changed by user');
    
    return true;
}

/**
 * Update user profile
 */
function updateUserProfile($user_id, $data) {
    global $database;
    
    $allowed_fields = ['first_name', 'last_name', 'phone'];
    $update_fields = [];
    $params = [];
    
    foreach ($allowed_fields as $field) {
        if (isset($data[$field])) {
            $update_fields[] = "$field = ?";
            $params[] = $data[$field];
        }
    }
    
    if (empty($update_fields)) {
        throw new Exception('No valid fields to update');
    }
    
    $params[] = $user_id;
    
    $sql = "UPDATE users SET " . implode(', ', $update_fields) . ", updated_at = NOW() WHERE id = ?";
    $result = $database->execute($sql, $params);
    
    if ($result) {
        logActivity($user_id, 'profile_updated', 'Profile information updated');
        return true;
    }
    
    throw new Exception('Failed to update profile');
}

// Check remember me on page load
checkRememberMe();
?>
