<?php
/**
 * API - Update User Address
 */
require_once '../config/config.php';

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

// Get input data
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['type']) || !isset($data['address'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

$type = $data['type'] === 'work' ? 'work_address' : 'home_address';
$address = trim($data['address']);
$userId = $_SESSION['user_id'];

try {
    // Update the specific address type
    $stmt = $pdo->prepare("UPDATE users SET $type = ? WHERE id = ?");
    $stmt->execute([$address, $userId]);
    
    echo json_encode(['success' => true, 'message' => 'Address updated successfully']);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
