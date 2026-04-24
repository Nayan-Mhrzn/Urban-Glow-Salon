<?php
/**
 * eSewa Success Handler - Urban Glow Salon
 */
require_once '../app/Config/config.php';

if (!isset($_GET['data'])) {
    setFlash('error', 'Invalid payment response from eSewa.');
    redirect(SITE_URL . '/index.php');
}

// Decode eSewa v2 response
$base64Data = $_GET['data'];
$decodedData = base64_decode($base64Data);
$paymentData = json_decode($decodedData, true);

if (!$paymentData || !isset($paymentData['status'])) {
    setFlash('error', 'Unverifiable payment response.');
    redirect(SITE_URL . '/index.php');
}

if ($paymentData['status'] !== 'COMPLETE') {
    setFlash('error', 'Payment was not completed successfully.');
    redirect(SITE_URL . '/index.php');
}

// Extract Order ID from transaction_uuid
// Format was "UGS_ORDER_{orderId}_{timestamp}"
$transactionUuid = $paymentData['transaction_uuid'];
$parts = explode('_', $transactionUuid);

// Determine order ID. If our prefix "UGS_ORDER_" was used, the ID is at index 2.
$orderId = null;
if (count($parts) >= 3 && $parts[0] === 'UGS' && $parts[1] === 'ORDER') {
    $orderId = (int)$parts[2];
}

if (!$orderId) {
    setFlash('warning', 'Payment successful, but order reference is missing. Please contact support.');
    redirect(SITE_URL . '/index.php');
}

// Check order in database
$stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ?");
$stmt->execute([$orderId]);
$order = $stmt->fetch();

if (!$order) {
    setFlash('warning', 'Payment successful, but an invalid order was provided. Please contact support.');
    redirect(SITE_URL . '/index.php');
}

// Update order status to Processing (Since payment is successful)
$updateStmt = $pdo->prepare("UPDATE orders SET status = 'Processing' WHERE id = ?");
$updateStmt->execute([$orderId]);

// Send order confirmation email for eSewa payment
require_once CORE_PATH . '/mailer.php';
$userInfo = getCurrentUser($pdo);
if ($userInfo) {
    $stmt = $pdo->prepare("SELECT oi.*, p.name FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id = ?");
    $stmt->execute([$orderId]);
    $emailItems = $stmt->fetchAll();
    sendOrderConfirmationEmail($userInfo['email'], $userInfo['full_name'], $orderId, $emailItems, $order['total_amount'], 'eSewa (Paid)', $order['shipping_address']);
}

setFlash('success', 'eSewa Payment Successful! Your order #' . $orderId . ' is now being processed.');
redirect(SITE_URL . '/customer/order-details.php?id=' . $orderId);
