<?php
/**
 * Customer Order Details Page - Urban Glow Salon
 */
require_once '../config/config.php';
requireLogin();

$orderId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$userId = $_SESSION['user_id'];

// Get order details
$stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ?");
$stmt->execute([$orderId, $userId]);
$order = $stmt->fetch();

if (!$order) {
    setFlash('error', 'Order not found or you do not have permission to view this order.');
    redirect(SITE_URL . '/customer/dashboard.php');
}

// Get order items joined with products for image/name
$stmt = $pdo->prepare("
    SELECT oi.*, p.name, p.image 
    FROM order_items oi
    JOIN products p ON oi.product_id = p.id
    WHERE oi.order_id = ?
");
$stmt->execute([$orderId]);
$items = $stmt->fetchAll();

$pageTitle = 'Order #' . $order['id'];
require_once '../partials/header.php';
?>

<div class="bg-gray-50 min-h-[calc(100vh-80px)] py-12">
    <div class="max-w-4xl mx-auto px-6">
        
        <!-- Back Button -->
        <a href="<?= SITE_URL ?>/customer/dashboard.php" class="inline-flex items-center text-sm font-semibold text-gray-500 hover:text-primary mb-6 transition-colors group">
            <i class="fas fa-arrow-left mr-2 group-hover:-translate-x-1 transition-transform"></i> Back to Dashboard
        </a>

        <!-- Order Header Card -->
        <div class="bg-white rounded-2xl shadow-[0_2px_16px_rgba(67,57,242,0.03)] border border-gray-100 p-8 mb-6 relative overflow-hidden">
            <!-- Decorative accent -->
            <div class="absolute top-0 right-0 w-32 h-32 bg-primary/5 rounded-bl-full -z-0"></div>
            
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 relative z-10">
                <div>
                    <h1 class="text-3xl font-extrabold text-gray-900 mb-2 tracking-tight">Order #<?= sanitize($order['id']) ?></h1>
                    <p class="text-gray-500 text-sm font-medium">Placed on <?= date('F j, Y \a\t g:i A', strtotime($order['created_at'])) ?></p>
                </div>
                
                <div class="text-left md:text-right">
                    <?php 
                        $status = strtolower($order['status'] ?? 'pending');
                        $statusClass = match($status) {
                            'pending' => 'bg-amber-100 text-amber-800 ring-1 ring-amber-200',
                            'processing' => 'bg-blue-100 text-blue-800 ring-1 ring-blue-200',
                            'shipped' => 'bg-purple-100 text-purple-800 ring-1 ring-purple-200',
                            'delivered' => 'bg-green-100 text-green-800 ring-1 ring-green-200',
                            'cancelled' => 'bg-red-100 text-red-800 ring-1 ring-red-200',
                            default => 'bg-gray-100 text-gray-800 ring-1 ring-gray-200'
                        };
                    ?>
                    <div class="inline-block px-4 py-1.5 rounded-full text-[13px] font-extrabold tracking-widest uppercase <?= $statusClass ?> mb-2">
                        <?= sanitize($order['status'] ?? 'Pending') ?>
                    </div>
                </div>
            </div>

            <hr class="my-6 border-gray-100">

            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <!-- Shipping Address -->
                <div>
                    <h3 class="text-xs font-extrabold text-gray-400 uppercase tracking-widest mb-3 flex items-center gap-2">
                        <i class="fas fa-map-marker-alt"></i> Shipping Details
                    </h3>
                    <p class="text-[15px] text-gray-800 font-medium leading-relaxed whitespace-pre-wrap"><?= sanitize($order['shipping_address']) ?></p>
                </div>
                
                <!-- Payment Method -->
                <div>
                    <h3 class="text-xs font-extrabold text-gray-400 uppercase tracking-widest mb-3 flex items-center gap-2">
                        <i class="fas fa-credit-card"></i> Payment Information
                    </h3>
                    <p class="text-[15px] text-gray-800 font-medium leading-relaxed">
                        Method: <?= sanitize($order['payment_method']) ?><br>
                        Total: <span class="text-primary font-bold mt-1 inline-block"><?= formatPrice($order['total_amount']) ?></span>
                    </p>
                </div>
            </div>
        </div>

        <!-- Order Items Loop -->
        <div class="bg-white rounded-2xl shadow-[0_2px_16px_rgba(67,57,242,0.03)] border border-gray-100 overflow-hidden">
            <div class="px-8 py-5 border-b border-gray-100 bg-gray-50/50">
                <h2 class="text-lg font-extrabold text-gray-900 tracking-tight">Items Ordered</h2>
            </div>
            <div class="divide-y divide-gray-100">
                <?php foreach ($items as $item): ?>
                <div class="p-6 sm:px-8 flex items-center gap-6 hover:bg-[#EEF0FF] transition-colors group">
                    <div class="w-20 h-20 bg-white rounded-xl border border-gray-200 flex items-center justify-center p-2 flex-shrink-0 shadow-sm group-hover:border-primary/30 transition-colors">
                        <img src="<?= SITE_URL ?>/assets/images/<?= sanitize($item['image']) ?>" alt="<?= sanitize($item['name']) ?>" class="max-w-full max-h-full object-contain" onerror="this.src='https://via.placeholder.com/80'">
                    </div>
                    
                    <div class="flex-1">
                        <h4 class="font-extrabold text-gray-900 text-[16px] mb-1.5 leading-snug"><a href="<?= SITE_URL ?>/shop/product-details.php?id=<?= $item['product_id'] ?>" class="hover:text-primary transition-colors"><?= sanitize($item['name']) ?></a></h4>
                        <div class="text-sm text-gray-500 font-semibold bg-gray-100 inline-block px-2.5 py-0.5 rounded-md">Qty: <?= $item['quantity'] ?></div>
                    </div>

                    <div class="text-right">
                        <span class="font-extrabold text-gray-900 text-lg"><?= formatPrice($item['price_at_purchase'] * $item['quantity']) ?></span>
                        <div class="text-[13px] text-gray-400 font-semibold mt-1">@ <?= formatPrice($item['price_at_purchase']) ?> each</div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <!-- Summary Footer -->
            <div class="bg-gradient-to-r from-gray-50 to-primary/5 px-8 pt-6 pb-8 border-t border-gray-100">
                <div class="flex justify-between items-center max-w-sm ml-auto">
                    <span class="text-gray-500 font-bold uppercase text-[13px] tracking-widest">Grand Total</span>
                    <span class="text-[28px] font-black text-primary tracking-tight"><?= formatPrice($order['total_amount']) ?></span>
                </div>
            </div>
        </div>

    </div>
</div>

<?php require_once '../partials/footer.php'; ?>
