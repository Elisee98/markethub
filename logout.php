<?php
/**
 * MarketHub Logout
 * Handle user logout and session cleanup
 */

require_once 'config/config.php';

// Log activity before destroying session
if (isset($_SESSION['user_id'])) {
    logActivity($_SESSION['user_id'], 'user_logout', 'User logged out');
}

// Store user type for redirect decision
$user_type = $_SESSION['user_type'] ?? 'customer';

// Destroy session
session_destroy();

// Start new session for flash message
session_start();

// Set logout success message
$_SESSION['logout_message'] = 'You have been successfully logged out.';

// Redirect based on user type
if ($user_type === 'admin') {
    redirect('login.php');
} elseif ($user_type === 'vendor') {
    redirect('vendor/login.php');
} else {
    redirect('login.php');
}
?>
