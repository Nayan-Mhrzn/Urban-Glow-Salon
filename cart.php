<?php
/**
 * Cart Page - Urban Glow Salon
 */
$pageTitle = 'Cart';
require_once 'includes/config.php';

requireLogin();

// Handle cart actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $cart_id = (int)($_POST['cart_id'] ?? 0);
    
    if ($action === 'update' && $cart_id) {
        $qty = max(1, (int)($_POST['quantity'] ?? 1));
        $stmt = $pdo->prepare("UPDATE cart SET quantity = ? WHERE id = ? AND user_id = ?");
        $stmt->execute([$qty, $cart_id, $_SESSION['user_id']]);
    } elseif ($action === 'remove' && $cart_id) {
        $stmt = $pdo->prepare("DELETE FROM cart WHERE id = ? AND user_id = ?");
        $stmt->execute([$cart_id, $_SESSION['user_id']]);
        setFlash('success', 'Item removed from cart.');
    }
    redirect(SITE_URL . '/cart.php');
}

// Fetch cart items
$stmt = $pdo->prepare("SELECT c.*, p.name, p.price, p.discount_price, p.image, p.stock_quantity FROM cart c JOIN products p ON c.product_id = p.id WHERE c.user_id = ? ORDER BY c.added_at DESC");
$stmt->execute([$_SESSION['user_id']]);
$cartItems = $stmt->fetchAll();

$total = 0;
foreach ($cartItems as $item) {
    $itemPrice = $item['discount_price'] ?? $item['price'];
    $total += $itemPrice * $item['quantity'];
}

require_once 'includes/header.php';
?>

<div class="max-w-7xl mx-auto px-6 py-8">
    <h1 class="text-2xl font-bold text-gray-900 mb-6">Shopping Cart</h1>

    <?php if (count($cartItems) > 0): ?>
    <div class="flex flex-col lg:flex-row gap-8">
        <!-- Cart Items -->
        <div class="flex-1">
            <div class="space-y-3">
                <?php foreach ($cartItems as $item): 
                    $itemPrice = $item['discount_price'] ?? $item['price'];
                ?>
                <div class="bg-white rounded-xl shadow-card border border-gray-100 p-4 flex items-center gap-4">
                    <!-- Image -->
                    <div class="w-16 h-16 rounded-lg bg-gray-100 flex-shrink-0 overflow-hidden">
                        <img src="<?= SITE_URL ?>/images/<?= $item['image'] ?>" alt="" class="w-full h-full object-contain p-1" onerror="this.src='https://via.placeholder.com/64'">
                    </div>
                    
                    <!-- Info -->
                    <div class="flex-1 min-w-0">
                        <h3 class="font-semibold text-gray-900 text-sm truncate"><?= sanitize($item['name']) ?></h3>
                        <p class="text-primary font-bold text-sm"><?= formatPrice($itemPrice) ?></p>
                    </div>

                    <!-- Quantity -->
                    <div class="flex items-center border-2 border-gray-200 rounded-lg overflow-hidden">
                        <form method="POST" class="flex items-center">
                            <input type="hidden" name="action" value="update">
                            <input type="hidden" name="cart_id" value="<?= $item['id'] ?>">
                            <button type="submit" name="quantity" value="<?= max(1, $item['quantity'] - 1) ?>" class="w-8 h-8 flex items-center justify-center text-gray-700 hover:bg-primary-bg hover:text-primary transition-all">−</button>
                            <span class="w-8 h-8 flex items-center justify-center font-semibold text-sm"><?= $item['quantity'] ?></span>
                            <button type="submit" name="quantity" value="<?= $item['quantity'] + 1 ?>" class="w-8 h-8 flex items-center justify-center text-gray-700 hover:bg-primary-bg hover:text-primary transition-all">+</button>
                        </form>
                    </div>

                    <!-- Subtotal -->
                    <div class="text-right hidden sm:block">
                        <p class="font-bold text-gray-900 text-sm"><?= formatPrice($itemPrice * $item['quantity']) ?></p>
                    </div>

                    <!-- Remove -->
                    <form method="POST">
                        <input type="hidden" name="action" value="remove">
                        <input type="hidden" name="cart_id" value="<?= $item['id'] ?>">
                        <button type="submit" class="text-gray-400 hover:text-red-500 transition-colors text-lg">
                            <i class="fas fa-trash-alt"></i>
                        </button>
                    </form>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Order Summary -->
        <div class="w-full lg:w-96 flex-shrink-0">
            <div class="bg-gradient-to-br from-white to-gray-50 border-2 border-gray-200 rounded-2xl p-6 lg:sticky lg:top-24">
                <h3 class="text-lg font-bold text-gray-900 mb-4">Order Summary</h3>
                <div class="space-y-3 mb-4">
                    <div class="flex justify-between text-sm border-b border-gray-100 pb-2">
                        <span class="text-gray-600">Subtotal (<?= count($cartItems) ?> items)</span>
                        <span class="font-medium"><?= formatPrice($total) ?></span>
                    </div>
                    <div class="flex justify-between text-sm border-b border-gray-100 pb-2">
                        <span class="text-gray-600">Shipping</span>
                        <span class="font-medium text-green-600">Free</span>
                    </div>
                    <div class="flex justify-between text-base pt-2 font-bold text-primary">
                        <span>Total</span>
                        <span><?= formatPrice($total) ?></span>
                    </div>
                </div>
                <a href="checkout.php" class="w-full bg-primary hover:bg-primary-dark text-white text-sm font-semibold py-3 rounded-full transition-all hover:-translate-y-0.5 flex items-center justify-center gap-2">
                    <i class="fas fa-lock"></i> Proceed to Checkout
                </a>
                <a href="products.php" class="w-full mt-3 border-2 border-gray-200 text-gray-700 text-sm font-semibold py-3 rounded-full hover:bg-gray-50 transition-all flex items-center justify-center gap-2">
                    <i class="fas fa-arrow-left"></i> Continue Shopping
                </a>
            </div>
        </div>
    </div>
    <?php else: ?>
    <div class="text-center py-20">
        <i class="fas fa-shopping-cart text-6xl text-gray-300 mb-4"></i>
        <h3 class="text-xl font-semibold text-gray-700 mb-2">Your cart is empty</h3>
        <p class="text-gray-500 mb-6">Add some products to get started!</p>
        <a href="products.php" class="bg-primary text-white px-8 py-3 rounded-full text-sm font-semibold hover:bg-primary-dark transition-all">
            <i class="fas fa-shopping-bag mr-1"></i> Browse Products
        </a>
    </div>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>
