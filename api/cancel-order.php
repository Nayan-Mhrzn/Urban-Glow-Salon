<?php
/**
 * API - Cancel Order
 */
require_once '../includes/config.php';

header('Content-Type: application/json');

// Check authentication
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['order_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing order ID']);
    exit;
}

$orderId = (int)$data['order_id'];
$userId = $_SESSION['user_id'];

try {
    // Only allow cancelling Pending or Processing orders that belong to the user
    $stmt = $pdo->prepare("UPDATE orders SET status = 'Cancelled' WHERE id = ? AND user_id = ? AND status IN ('Pending', 'Processing')");
    $stmt->execute([$orderId, $userId]);
    
    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true, 'message' => 'Order cancelled successfully']);
    } else {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Order cannot be cancelled.']);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
