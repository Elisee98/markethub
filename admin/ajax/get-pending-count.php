<?php
/**
 * Get Pending Users Count
 * AJAX endpoint for real-time pending count updates
 */

require_once '../../config/config.php';

// Set JSON header
header('Content-Type: application/json');

// Check if user is admin
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit;
}

try {
    $pending_count = $database->fetch("SELECT COUNT(*) as count FROM users WHERE status = 'pending'")['count'];
    
    echo json_encode([
        'success' => true,
        'count' => intval($pending_count)
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'count' => 0
    ]);
}
?>
