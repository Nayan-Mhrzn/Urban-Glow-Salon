<?php
/**
 * Checkout Page - Urban Glow Salon
 */
$pageTitle = 'Checkout';
require_once '../app/Config/config.php';

requireLogin();

// Fetch cart items
$stmt = $pdo->prepare("SELECT c.*, p.name, p.price, p.discount_price, p.image, p.stock_quantity FROM cart c JOIN products p ON c.product_id = p.id WHERE c.user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$cartItems = $stmt->fetchAll();

// Fetch user's saved addresses
$stmt = $pdo->prepare("SELECT home_address, work_address, phone FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$userAddrs = $stmt->fetch();
$homeAddress = $userAddrs['home_address'] ?? '';
$workAddress = $userAddrs['work_address'] ?? '';
$userPhone = $userAddrs['phone'] ?? '';

if (empty($cartItems)) {
    setFlash('warning', 'Your cart is empty.');
    redirect(SITE_URL . '/shop/products.php');
}

$total = 0;
foreach ($cartItems as $item) {
    $total += ($item['discount_price'] ?? $item['price']) * $item['quantity'];
}

// Handle checkout submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        setFlash('error', 'Invalid request.');
        redirect(SITE_URL . '/shop/checkout.php');
    }

    $address = trim($_POST['address'] ?? '');
    $customerPhone = trim($_POST['customer_phone'] ?? '');
    $payment = $_POST['payment_method'] ?? 'Cash on Delivery';

    if (empty($address) || empty($customerPhone)) {
        setFlash('error', 'Please enter shipping address and phone number.');
        redirect(SITE_URL . '/shop/checkout.php');
    }

    // Create order
    $stmt = $pdo->prepare("INSERT INTO orders (user_id, total_amount, payment_method, shipping_address, customer_phone) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$_SESSION['user_id'], $total, $payment, $address, $customerPhone]);
    $orderId = $pdo->lastInsertId();

    // Create order items and reduce stock
    foreach ($cartItems as $item) {
        $price = $item['discount_price'] ?? $item['price'];
        $stmt = $pdo->prepare("INSERT INTO order_items (order_id, product_id, quantity, price_at_purchase) VALUES (?, ?, ?, ?)");
        $stmt->execute([$orderId, $item['product_id'], $item['quantity'], $price]);

        $stmt = $pdo->prepare("UPDATE products SET stock_quantity = stock_quantity - ? WHERE id = ?");
        $stmt->execute([$item['quantity'], $item['product_id']]);
    }

    // Clear cart
    $stmt = $pdo->prepare("DELETE FROM cart WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);

    if ($payment === 'eSewa') {
        // Generate eSewa form and submit
        $transactionUuid = "UGS_ORDER_" . $orderId . "_" . time();
        $message = "total_amount=" . $total . ",transaction_uuid=" . $transactionUuid . ",product_code=" . 'EPAYTEST';
        if (defined('ESEWA_MERCHANT_CODE')) {
            $message = "total_amount=" . $total . ",transaction_uuid=" . $transactionUuid . ",product_code=" . ESEWA_MERCHANT_CODE;
        }
        $secretKey = defined('ESEWA_SECRET_KEY') ? ESEWA_SECRET_KEY : '8gBm/:&EnhH.1/q';
        $signature = base64_encode(hash_hmac('sha256', $message, $secretKey, true));
        
        $successUrl = SITE_URL . "/shop/esewa_success.php";
        $failureUrl = SITE_URL . "/shop/esewa_failure.php?order_id=" . $orderId;
        $esewaUrl = defined('ESEWA_URL') ? ESEWA_URL : 'https://rc-epay.esewa.com.np/api/epay/main/v2/form';

        echo '<!DOCTYPE html><html><head><title>Redirecting to eSewa...</title></head><body style="display:flex;justify-content:center;align-items:center;height:100vh;font-family:sans-serif;background-color:#f9fafb;">';
        echo '<div style="text-align:center;"><div style="width:40px;height:40px;border:4px solid #10b981;border-top:4px solid transparent;border-radius:50%;animation:spin 1s linear infinite;margin:0 auto 20px;"></div><h2>Redirecting to eSewa for payment...</h2><p style="color:#6b7280;">Please wait, do not close or refresh this window.</p>';
        echo '<style>@keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }</style>';
        echo '</div>';
        echo '<form id="esewa-form" action="' . $esewaUrl . '" method="POST" style="display:none;">';
        echo '<input type="hidden" name="amount" value="' . $total . '">';
        echo '<input type="hidden" name="tax_amount" value="0">';
        echo '<input type="hidden" name="total_amount" value="' . $total . '">';
        echo '<input type="hidden" name="transaction_uuid" value="' . $transactionUuid . '">';
        echo '<input type="hidden" name="product_code" value="' . (defined('ESEWA_MERCHANT_CODE') ? ESEWA_MERCHANT_CODE : 'EPAYTEST') . '">';
        echo '<input type="hidden" name="product_service_charge" value="0">';
        echo '<input type="hidden" name="product_delivery_charge" value="0">';
        echo '<input type="hidden" name="success_url" value="' . $successUrl . '">';
        echo '<input type="hidden" name="failure_url" value="' . $failureUrl . '">';
        echo '<input type="hidden" name="signed_field_names" value="total_amount,transaction_uuid,product_code">';
        echo '<input type="hidden" name="signature" value="' . $signature . '">';
        echo '</form>';
        echo '<script>document.getElementById("esewa-form").submit();</script>';
        echo '</body></html>';
        exit;
    }

    // Send order confirmation email ONLY for Cash on Delivery
    require_once CORE_PATH . '/mailer.php';
    $userInfo = getCurrentUser($pdo);
    if ($userInfo) {
        $stmt = $pdo->prepare("SELECT oi.*, p.name FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id = ?");
        $stmt->execute([$orderId]);
        $emailItems = $stmt->fetchAll();
        sendOrderConfirmationEmail($userInfo['email'], $userInfo['full_name'], $orderId, $emailItems, $total, $payment, $address);
    }

    setFlash('success', 'Order placed successfully! Order #' . $orderId);
    redirect(SITE_URL . '/index.php');
}

require_once '../Includes/Partials/header.php';
?>

<div class="max-w-5xl mx-auto px-6 py-8">
    <h1 class="text-2xl font-bold text-gray-900 mb-6">Checkout</h1>

    <form method="POST" class="flex flex-col lg:flex-row gap-8">
        <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">

        <!-- Shipping Details -->
        <div class="flex-1">
            <div class="bg-white rounded-2xl shadow-card border border-gray-100 p-6">
                <h2 class="text-lg font-bold text-gray-900 mb-4">Shipping Details</h2>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Full Name</label>
                    <input type="text" value="<?= sanitize($_SESSION['user_name'] ?? '') ?>" disabled class="w-full px-4 py-3 bg-gray-100 border-2 border-gray-200 rounded-xl text-sm text-gray-600">
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Phone Number *</label>
                    <input type="tel" name="customer_phone" value="<?= sanitize($userPhone) ?>" required pattern="^9\d{9}$" maxlength="10" title="Contact number must start with 9 and be exactly 10 digits" placeholder="e.g. 9812345678" class="w-full px-4 py-3 bg-white border-2 border-gray-200 rounded-xl text-sm focus:border-primary outline-none transition-all shadow-sm">
                </div>

                <div class="mb-4">
                    <div class="flex items-center justify-between mb-1.5">
                        <label class="block text-sm font-medium text-gray-700">Shipping Address *</label>
                        <div class="flex gap-2">
                            <?php if (!empty($homeAddress)): ?>
                                <button type="button" onclick="document.getElementById('shippingAddress').value = `<?= htmlspecialchars($homeAddress, ENT_QUOTES) ?>`" class="text-xs font-semibold bg-primary/10 text-primary hover:bg-primary hover:text-white px-3 py-1.5 rounded-lg transition-all border border-transparent hover:shadow-md">Home</button>
                            <?php endif; ?>
                            <?php if (!empty($workAddress)): ?>
                                <button type="button" onclick="document.getElementById('shippingAddress').value = `<?= htmlspecialchars($workAddress, ENT_QUOTES) ?>`" class="text-xs font-semibold bg-gray-100 text-gray-700 hover:bg-gray-200 px-3 py-1.5 rounded-lg transition-all border border-gray-200 hover:shadow-md">Work</button>
                            <?php endif; ?>
                            <button type="button" onclick="document.getElementById('shippingAddress').value = ''" class="text-xs font-medium text-gray-400 hover:text-gray-700 px-1 py-1.5 transition-colors underline decoration-dotted">Clear</button>
                        </div>
                    </div>
                    <textarea name="address" id="shippingAddress" rows="3" required placeholder="Enter your full address..." class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl text-sm focus:border-primary outline-none transition-all resize-none shadow-sm"><?= sanitize($homeAddress) ?></textarea>
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-3">Payment Method</label>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <!-- eSewa Wallet -->
                        <label class="relative flex flex-col items-center justify-center p-5 bg-white border-2 border-gray-100 rounded-2xl cursor-pointer hover:border-[#60bb46] transition-all [&:has(input:checked)]:border-[#60bb46] [&:has(input:checked)]:shadow-[0_4px_20px_rgba(96,187,70,0.15)] group">
                            <input type="radio" name="payment_method" value="eSewa" class="peer absolute opacity-0 w-0 h-0">
                            <div class="w-14 h-14 mb-3 group-hover:-translate-y-1 transition-transform flex items-center justify-center">
                                <img src="<?= SITE_URL ?>/assets/img/esewa-logo.png" alt="eSewa Logo" class="max-w-full max-h-full object-contain drop-shadow-sm">
                            </div>
                            <span class="text-sm font-bold text-gray-800 text-center mb-0.5">eSewa Mobile Wallet</span>
                        </label>

                        <!-- Cash on Delivery -->
                        <label class="relative flex flex-col items-center justify-center p-5 bg-white border-2 border-gray-100 rounded-2xl cursor-pointer hover:border-[#2b88e9] transition-all [&:has(input:checked)]:border-[#2b88e9] [&:has(input:checked)]:shadow-[0_4px_20px_rgba(43,136,233,0.15)] group">
                            <input type="radio" name="payment_method" value="Cash on Delivery" checked class="peer absolute opacity-0 w-0 h-0">
                            <div class="w-14 h-14 rounded-2xl mb-3 group-hover:-translate-y-1 transition-transform flex items-center justify-center overflow-hidden">
                                <img src="<?= SITE_URL ?>/assets/img/cod-icon.png" alt="Cash on Delivery" class="max-w-full max-h-full object-cover scale-150 drop-shadow-sm mix-blend-multiply">
                            </div>
                            <span class="text-sm font-bold text-gray-800 text-center mb-0.5">Cash on Delivery</span>
                        </label>
                    </div>
                </div>
            </div>
        </div>

        <!-- Order Summary -->
        <div class="w-full lg:w-96">
            <div class="bg-gradient-to-br from-white to-gray-50 border-2 border-gray-200 rounded-2xl p-6 lg:sticky lg:top-24">
                <h3 class="text-lg font-bold text-gray-900 mb-4">Order Summary</h3>
                <div class="space-y-3 mb-4">
                    <?php foreach ($cartItems as $item): 
                        $price = $item['discount_price'] ?? $item['price'];
                    ?>
                    <div class="flex items-center gap-3 text-sm">
                        <div class="w-10 h-10 bg-gray-100 rounded-lg flex-shrink-0 overflow-hidden">
                            <img src="<?= SITE_URL ?>/assets/img/<?= $item['image'] ?>" alt="" class="w-full h-full object-contain p-0.5" onerror="this.src='https://via.placeholder.com/40'">
                        </div>
                        <span class="flex-1 truncate text-gray-700"><?= sanitize($item['name']) ?> × <?= $item['quantity'] ?></span>
                        <span class="font-medium"><?= formatPrice($price * $item['quantity']) ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
                <hr class="my-3 border-gray-200">
                <div class="flex justify-between text-sm mb-2">
                    <span class="text-gray-600">Subtotal</span>
                    <span class="font-medium"><?= formatPrice($total) ?></span>
                </div>
                <div class="flex justify-between text-sm mb-3">
                    <span class="text-gray-600">Shipping</span>
                    <span class="font-medium text-green-600">Free</span>
                </div>
                <div class="flex justify-between text-lg font-bold text-primary pt-2 border-t border-gray-200">
                    <span>Total</span>
                    <span><?= formatPrice($total) ?></span>
                </div>
                <button type="submit" class="w-full mt-5 bg-primary hover:bg-primary-dark text-white text-sm font-semibold py-3.5 rounded-full transition-all hover:-translate-y-0.5 flex items-center justify-center gap-2">
                    <i class="fas fa-check-circle"></i> Place Order
                </button>
            </div>
        </div>
    </form>
</div>

<?php require_once '../Includes/Partials/footer.php'; ?>

