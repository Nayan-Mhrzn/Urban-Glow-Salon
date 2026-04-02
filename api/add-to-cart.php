<?php
/**
 * Add to Cart API - Urban Glow Salon
 */
require_once dirname(__DIR__) . '/config/config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Please login to add items to cart']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$product_id = (int)($data['product_id'] ?? 0);
$quantity = (int)($data['quantity'] ?? 1);

if (!$product_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid product']);
    exit;
}

// Check product exists and is in stock
$stmt = $pdo->prepare("SELECT id, stock_quantity FROM products WHERE id = ? AND is_active = 1");
$stmt->execute([$product_id]);
$product = $stmt->fetch();

if (!$product || $product['stock_quantity'] < 1) {
    echo json_encode(['success' => false, 'message' => 'Product not available']);
    exit;
}

// Add or update cart
$stmt = $pdo->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE quantity = quantity + ?");
$stmt->execute([$_SESSION['user_id'], $product_id, $quantity, $quantity]);

$cartCount = getCartCount($pdo);
echo json_encode(['success' => true, 'cartCount' => $cartCount, 'message' => 'Added to cart']);

