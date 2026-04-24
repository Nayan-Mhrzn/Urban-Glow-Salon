<?php
/**
 * eSewa Failure Handler - Urban Glow Salon
 */
require_once '../app/Config/config.php';

if (!isset($_GET['order_id'])) {
    setFlash('error', 'eSewa Payment Failed or Cancelled.');
    redirect(SITE_URL . '/index.php');
}

$orderId = (int)$_GET['order_id'];

// Check order in database
$stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ?");
$stmt->execute([$orderId]);
$order = $stmt->fetch();

if (!$order) {
    setFlash('error', 'eSewa Payment Failed or Cancelled.');
    redirect(SITE_URL . '/index.php');
}

// If the order is already cancelled or processing, do not touch stock again
if ($order['status'] !== 'Pending') {
    setFlash('warning', 'Payment failed, but order was already updated.');
    redirect(SITE_URL . '/index.php');
}

$pdo->beginTransaction();
try {
    // Update order status to Cancelled
    $updateStmt = $pdo->prepare("UPDATE orders SET status = 'Cancelled' WHERE id = ?");
    $updateStmt->execute([$orderId]);

    // Restore stock for the products in this order
    $itemStmt = $pdo->prepare("SELECT product_id, quantity FROM order_items WHERE order_id = ?");
    $itemStmt->execute([$orderId]);
    $items = $itemStmt->fetchAll();

    foreach ($items as $item) {
        $restoreStmt = $pdo->prepare("UPDATE products SET stock_quantity = stock_quantity + ? WHERE id = ?");
        $restoreStmt->execute([$item['quantity'], $item['product_id']]);
    }

    $pdo->commit();
    setFlash('error', 'eSewa Payment was cancelled or failed. Your order has been cancelled and stock restored.');

} catch (Exception $e) {
    $pdo->rollBack();
    setFlash('error', 'eSewa Payment failed. Could not correctly cancel the order. Please contact support.');
}

redirect(SITE_URL . '/shop/cart.php');
