<?php
/**
 * AJAX Page Loader for Admin Panel
 * Loads page content dynamically without full page refresh
 */

require_once '../../config/config.php';

// Check if user is admin
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    http_response_code(403);
    echo '<div style="text-align: center; padding: 3rem; color: #ef4444;">
            <i class="fas fa-lock" style="font-size: 3rem; margin-bottom: 1rem;"></i>
            <h3>Access Denied</h3>
            <p>You do not have permission to access this page.</p>
          </div>';
    exit;
}

$page = sanitizeInput($_GET['page'] ?? 'dashboard');

// Define allowed pages
$allowed_pages = [
    'dashboard',
    'users', 
    'vendors',
    'customers',
    'products',
    'categories',
    'orders',
    'analytics',
    'reports',
    'system',
    'email'
];

if (!in_array($page, $allowed_pages)) {
    http_response_code(404);
    echo '<div style="text-align: center; padding: 3rem; color: #ef4444;">
            <i class="fas fa-exclamation-triangle" style="font-size: 3rem; margin-bottom: 1rem;"></i>
            <h3>Page Not Found</h3>
            <p>The requested page could not be found.</p>
          </div>';
    exit;
}

// Load page content based on requested page
switch($page) {
    case 'dashboard':
        include 'pages/dashboard.php';
        break;
        
    case 'users':
        include 'pages/users.php';
        break;
        
    case 'vendors':
        include 'pages/vendors.php';
        break;
        
    case 'customers':
        include 'pages/customers.php';
        break;
        
    case 'products':
        include 'pages/products.php';
        break;
        
    case 'categories':
        include 'pages/categories.php';
        break;
        
    case 'orders':
        include 'pages/orders.php';
        break;
        
    case 'analytics':
        include 'pages/analytics.php';
        break;
        
    case 'reports':
        include 'pages/reports.php';
        break;
        
    case 'system':
        include 'pages/system.php';
        break;
        
    case 'email':
        include 'pages/email.php';
        break;
        
    default:
        echo '<div style="text-align: center; padding: 3rem; color: #ef4444;">
                <i class="fas fa-exclamation-triangle" style="font-size: 3rem; margin-bottom: 1rem;"></i>
                <h3>Page Not Available</h3>
                <p>This page is currently under development.</p>
              </div>';
        break;
}
?>
