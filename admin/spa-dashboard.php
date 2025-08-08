<?php
/**
 * MarketHub Admin SPA Dashboard
 * Single Page Application with AJAX Navigation
 */

require_once '../config/config.php';

$page_title = 'Admin Dashboard';

// Check if user is admin
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    redirect('../login.php?error=access_denied');
}

require_once 'includes/admin_header_new.php';
require_once 'includes/admin_footer_new.php';
?>
