<?php
/**
 * Checkout Page - Urban Glow Salon
 */
$pageTitle = 'Checkout';
require_once '../config/config.php';

requireLogin();

// Fetch cart items
$stmt = $pdo->prepare("SELECT c.*, p.name, p.price, p.discount_price, p.image, p.stock_quantity FROM cart c JOIN products p ON c.product_id = p.id WHERE c.user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$cartItems = $stmt->fetchAll();

// Fetch user's saved addresses
$stmt = $pdo->prepare("SELECT home_address, work_address FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$userAddrs = $stmt->fetch();
$homeAddress = $userAddrs['home_address'] ?? '';
$workAddress = $userAddrs['work_address'] ?? '';

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
    $payment = $_POST['payment_method'] ?? 'Cash on Delivery';

    if (empty($address)) {
        setFlash('error', 'Please enter shipping address.');
        redirect(SITE_URL . '/shop/checkout.php');
    }

    // Create order
    $stmt = $pdo->prepare("INSERT INTO orders (user_id, total_amount, payment_method, shipping_address) VALUES (?, ?, ?, ?)");
    $stmt->execute([$_SESSION['user_id'], $total, $payment, $address]);
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

    setFlash('success', 'Order placed successfully! Order #' . $orderId);
    redirect(SITE_URL . '/index.php');
}

require_once '../partials/header.php';
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
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Payment Method</label>
                    <select name="payment_method" class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl text-sm focus:border-primary outline-none bg-white">
                        <option value="Cash on Delivery">Cash on Delivery</option>
                        <option value="eSewa">eSewa</option>
                        <option value="Khalti">Khalti</option>
                    </select>
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
                            <img src="<?= SITE_URL ?>/images/<?= $item['image'] ?>" alt="" class="w-full h-full object-contain p-0.5" onerror="this.src='https://via.placeholder.com/40'">
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

<?php require_once '../partials/footer.php'; ?>

