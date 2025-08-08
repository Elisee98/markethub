<?php
/**
 * MarketHub Reviews API
 * Multi-Vendor E-Commerce Platform
 */

require_once '../config/config.php';

header('Content-Type: application/json');

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Require login for review operations
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Login required']);
    exit;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid JSON input']);
    exit;
}

$action = $input['action'] ?? '';
$customer_id = $_SESSION['user_id'];

try {
    switch ($action) {
        case 'add':
            $product_id = intval($input['product_id'] ?? 0);
            $rating = intval($input['rating'] ?? 0);
            $review_text = sanitizeInput($input['review_text'] ?? '');
            $title = sanitizeInput($input['title'] ?? '');
            
            // Validate input
            $review_data = [
                'rating' => $rating,
                'review_text' => $review_text
            ];
            
            $validation_errors = validateReviewData($review_data);
            
            if (!empty($validation_errors)) {
                echo json_encode(['success' => false, 'message' => implode(', ', $validation_errors)]);
                exit;
            }
            
            // Check if product exists
            $product = $database->fetch(
                "SELECT id, name, vendor_id FROM products WHERE id = ? AND status = 'active'", 
                [$product_id]
            );
            
            if (!$product) {
                echo json_encode(['success' => false, 'message' => 'Product not found']);
                exit;
            }
            
            // Check if customer has purchased this product
            $purchase_check = $database->fetch(
                "SELECT COUNT(*) as count FROM order_items oi 
                 JOIN orders o ON oi.order_id = o.id 
                 WHERE o.customer_id = ? AND oi.product_id = ? AND o.payment_status = 'paid'",
                [$customer_id, $product_id]
            );
            
            if ($purchase_check['count'] == 0) {
                echo json_encode(['success' => false, 'message' => 'You can only review products you have purchased']);
                exit;
            }
            
            // Check if customer has already reviewed this product
            $existing_review = $database->fetch(
                "SELECT id FROM product_reviews WHERE customer_id = ? AND product_id = ?",
                [$customer_id, $product_id]
            );
            
            if ($existing_review) {
                echo json_encode(['success' => false, 'message' => 'You have already reviewed this product']);
                exit;
            }
            
            // Add review
            $sql = "INSERT INTO product_reviews 
                    (customer_id, product_id, vendor_id, rating, title, review_text, status, created_at) 
                    VALUES (?, ?, ?, ?, ?, ?, 'pending', NOW())";
            
            $review_id = $database->insert($sql, [$customer_id, $product_id, $product['vendor_id'], $rating, $title, $review_text]);
            
            // Log activity
            logActivity($customer_id, 'review_add', "Product ID: $product_id, Rating: $rating");
            
            // Send notification to vendor
            $vendor_email_sql = "SELECT email FROM users WHERE id = ?";
            $vendor = $database->fetch($vendor_email_sql, [$product['vendor_id']]);
            
            if ($vendor) {
                $subject = "New Product Review - " . SITE_NAME;
                $message = "
                    <h2>New Product Review</h2>
                    <p>A customer has left a review for your product:</p>
                    <p><strong>Product:</strong> {$product['name']}</p>
                    <p><strong>Rating:</strong> $rating/5 stars</p>
                    <p><strong>Review:</strong> $review_text</p>
                    <p>Please log in to your vendor dashboard to view and respond to this review.</p>
                ";
                
                sendEmail($vendor['email'], $subject, $message);
            }
            
            echo json_encode([
                'success' => true, 
                'message' => 'Review submitted successfully and is pending approval',
                'review_id' => $review_id
            ]);
            break;
            
        case 'update':
            $review_id = intval($input['review_id'] ?? 0);
            $rating = intval($input['rating'] ?? 0);
            $review_text = sanitizeInput($input['review_text'] ?? '');
            $title = sanitizeInput($input['title'] ?? '');
            
            // Validate input
            $review_data = [
                'rating' => $rating,
                'review_text' => $review_text
            ];
            
            $validation_errors = validateReviewData($review_data);
            
            if (!empty($validation_errors)) {
                echo json_encode(['success' => false, 'message' => implode(', ', $validation_errors)]);
                exit;
            }
            
            // Check if review exists and belongs to customer
            $review = $database->fetch(
                "SELECT id, status FROM product_reviews WHERE id = ? AND customer_id = ?",
                [$review_id, $customer_id]
            );
            
            if (!$review) {
                echo json_encode(['success' => false, 'message' => 'Review not found']);
                exit;
            }
            
            // Update review
            $sql = "UPDATE product_reviews 
                    SET rating = ?, title = ?, review_text = ?, status = 'pending', updated_at = NOW() 
                    WHERE id = ? AND customer_id = ?";
            
            $database->execute($sql, [$rating, $title, $review_text, $review_id, $customer_id]);
            
            // Log activity
            logActivity($customer_id, 'review_update', "Review ID: $review_id");
            
            echo json_encode([
                'success' => true, 
                'message' => 'Review updated successfully and is pending approval'
            ]);
            break;
            
        case 'delete':
            $review_id = intval($input['review_id'] ?? 0);
            
            // Check if review exists and belongs to customer
            $review = $database->fetch(
                "SELECT id FROM product_reviews WHERE id = ? AND customer_id = ?",
                [$review_id, $customer_id]
            );
            
            if (!$review) {
                echo json_encode(['success' => false, 'message' => 'Review not found']);
                exit;
            }
            
            // Delete review
            $sql = "DELETE FROM product_reviews WHERE id = ? AND customer_id = ?";
            $database->execute($sql, [$review_id, $customer_id]);
            
            // Log activity
            logActivity($customer_id, 'review_delete', "Review ID: $review_id");
            
            echo json_encode([
                'success' => true, 
                'message' => 'Review deleted successfully'
            ]);
            break;
            
        case 'helpful':
            $review_id = intval($input['review_id'] ?? 0);
            $is_helpful = $input['is_helpful'] ?? true;
            
            // Check if review exists
            $review = $database->fetch(
                "SELECT id FROM product_reviews WHERE id = ? AND status = 'approved'",
                [$review_id]
            );
            
            if (!$review) {
                echo json_encode(['success' => false, 'message' => 'Review not found']);
                exit;
            }
            
            // Check if customer has already marked this review
            $existing = $database->fetch(
                "SELECT id, is_helpful FROM review_helpfulness WHERE review_id = ? AND customer_id = ?",
                [$review_id, $customer_id]
            );
            
            if ($existing) {
                if ($existing['is_helpful'] == $is_helpful) {
                    // Remove the vote
                    $sql = "DELETE FROM review_helpfulness WHERE id = ?";
                    $database->execute($sql, [$existing['id']]);
                    $message = 'Vote removed';
                } else {
                    // Update the vote
                    $sql = "UPDATE review_helpfulness SET is_helpful = ?, updated_at = NOW() WHERE id = ?";
                    $database->execute($sql, [$is_helpful, $existing['id']]);
                    $message = 'Vote updated';
                }
            } else {
                // Add new vote
                $sql = "INSERT INTO review_helpfulness (review_id, customer_id, is_helpful, created_at) VALUES (?, ?, ?, NOW())";
                $database->execute($sql, [$review_id, $customer_id, $is_helpful]);
                $message = 'Vote recorded';
            }
            
            // Get updated counts
            $helpful_count = $database->fetch(
                "SELECT COUNT(*) as count FROM review_helpfulness WHERE review_id = ? AND is_helpful = 1",
                [$review_id]
            )['count'];
            
            $not_helpful_count = $database->fetch(
                "SELECT COUNT(*) as count FROM review_helpfulness WHERE review_id = ? AND is_helpful = 0",
                [$review_id]
            )['count'];
            
            echo json_encode([
                'success' => true, 
                'message' => $message,
                'helpful_count' => $helpful_count,
                'not_helpful_count' => $not_helpful_count
            ]);
            break;
            
        case 'get_product_reviews':
            $product_id = intval($input['product_id'] ?? 0);
            $page = max(1, intval($input['page'] ?? 1));
            $sort_by = $input['sort'] ?? 'newest';
            $rating_filter = intval($input['rating_filter'] ?? 0);
            
            // Build WHERE clause
            $where_conditions = ["pr.product_id = ?", "pr.status = 'approved'"];
            $params = [$product_id];
            
            if ($rating_filter > 0) {
                $where_conditions[] = "pr.rating = ?";
                $params[] = $rating_filter;
            }
            
            $where_clause = implode(' AND ', $where_conditions);
            
            // Build ORDER BY clause
            $order_by = "pr.created_at DESC";
            switch ($sort_by) {
                case 'oldest':
                    $order_by = "pr.created_at ASC";
                    break;
                case 'rating_high':
                    $order_by = "pr.rating DESC, pr.created_at DESC";
                    break;
                case 'rating_low':
                    $order_by = "pr.rating ASC, pr.created_at DESC";
                    break;
                case 'helpful':
                    $order_by = "helpful_count DESC, pr.created_at DESC";
                    break;
            }
            
            // Get total count
            $count_sql = "SELECT COUNT(*) as total FROM product_reviews pr WHERE $where_clause";
            $total_reviews = $database->fetch($count_sql, $params)['total'];
            
            // Get reviews
            $limit = 10;
            $offset = ($page - 1) * $limit;
            
            $reviews_sql = "
                SELECT pr.*, u.first_name, u.last_name,
                       COALESCE(h.helpful_count, 0) as helpful_count,
                       COALESCE(nh.not_helpful_count, 0) as not_helpful_count
                FROM product_reviews pr
                JOIN users u ON pr.customer_id = u.id
                LEFT JOIN (
                    SELECT review_id, COUNT(*) as helpful_count 
                    FROM review_helpfulness 
                    WHERE is_helpful = 1 
                    GROUP BY review_id
                ) h ON pr.id = h.review_id
                LEFT JOIN (
                    SELECT review_id, COUNT(*) as not_helpful_count 
                    FROM review_helpfulness 
                    WHERE is_helpful = 0 
                    GROUP BY review_id
                ) nh ON pr.id = nh.review_id
                WHERE $where_clause
                ORDER BY $order_by
                LIMIT $limit OFFSET $offset
            ";
            
            $reviews = $database->fetchAll($reviews_sql, $params);
            
            echo json_encode([
                'success' => true,
                'reviews' => $reviews,
                'total' => $total_reviews,
                'page' => $page,
                'total_pages' => ceil($total_reviews / $limit)
            ]);
            break;
            
        case 'get_customer_reviews':
            $page = max(1, intval($input['page'] ?? 1));
            $limit = 10;
            $offset = ($page - 1) * $limit;
            
            // Get customer's reviews
            $reviews_sql = "
                SELECT pr.*, p.name as product_name, pi.image_url
                FROM product_reviews pr
                JOIN products p ON pr.product_id = p.id
                LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
                WHERE pr.customer_id = ?
                ORDER BY pr.created_at DESC
                LIMIT $limit OFFSET $offset
            ";
            
            $reviews = $database->fetchAll($reviews_sql, [$customer_id]);
            
            // Get total count
            $count_sql = "SELECT COUNT(*) as total FROM product_reviews WHERE customer_id = ?";
            $total_reviews = $database->fetch($count_sql, [$customer_id])['total'];
            
            echo json_encode([
                'success' => true,
                'reviews' => $reviews,
                'total' => $total_reviews,
                'page' => $page,
                'total_pages' => ceil($total_reviews / $limit)
            ]);
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
            break;
    }
    
} catch (Exception $e) {
    error_log("Reviews API Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred']);
}
?>
