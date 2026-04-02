<?php
/**
 * Booking Success Page - Urban Glow Salon
 */
$pageTitle = 'Booking Confirmed';
require_once 'includes/config.php';
requireLogin();

$bookingId = (int)($_GET['id'] ?? 0);

if (!$bookingId) {
    redirect(SITE_URL . '/index.php');
}

// Fetch booking details
$stmt = $pdo->prepare("
    SELECT b.*, s.name as service_name, s.price, s.duration_mins, s.image as service_image 
    FROM bookings b 
    JOIN services s ON b.service_id = s.id 
    WHERE b.id = ? AND b.user_id = ?
");
$stmt->execute([$bookingId, $_SESSION['user_id']]);
$booking = $stmt->fetch();

if (!$booking) {
    redirect(SITE_URL . '/index.php');
}

// Get Cross-Sell Recommendations
require_once 'includes/recommender.php';
// getCrossSellRecommendations($pdo, $serviceId, $topN)
$crossSells = getCrossSellRecommendations($pdo, $booking['service_id'], 3);

require_once 'includes/header.php';
?>

<div class="bg-[#f0f4f8] min-h-[calc(100vh-80px)] py-12">
    <div class="max-w-[1200px] mx-auto px-6">
        
        <!-- Success Banner -->
        <div class="bg-white rounded-[32px] shadow-[0_8px_40px_rgb(0,0,0,0.04)] p-8 md:p-12 text-center mb-10 max-w-3xl mx-auto border-t-8 border-[#10b981]">
            <div class="w-20 h-20 bg-[#ecfdf5] rounded-full mx-auto flex items-center justify-center mb-6">
                <i class="fas fa-check-circle text-5xl text-[#10b981]"></i>
            </div>
            <h1 class="text-3xl md:text-4xl font-extrabold text-gray-900 mb-4 tracking-tight">Booking Confirmed!</h1>
            <p class="text-[16px] text-gray-600 mb-8 max-w-lg mx-auto">Your appointment for <span class="font-bold text-gray-900"><?= sanitize($booking['service_name']) ?></span> has been successfully scheduled. We look forward to seeing you!</p>
            
            <div class="bg-gray-50 rounded-2xl p-6 flex flex-col sm:flex-row justify-center items-center gap-6 sm:gap-12 text-left mb-8 border border-gray-100">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 bg-white rounded-full shadow-sm flex items-center justify-center text-primary">
                        <i class="far fa-calendar-alt text-xl"></i>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 font-semibold uppercase tracking-wider mb-1">Date</p>
                        <p class="text-base font-bold text-gray-900"><?= date('l, M j, Y', strtotime($booking['booking_date'])) ?></p>
                    </div>
                </div>
                <div class="hidden sm:block w-px h-12 bg-gray-200"></div>
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 bg-white rounded-full shadow-sm flex items-center justify-center text-primary">
                        <i class="far fa-clock text-xl"></i>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 font-semibold uppercase tracking-wider mb-1">Time</p>
                        <p class="text-base font-bold text-gray-900">
                            <?= date('h:i A', strtotime($booking['booking_time'])) ?> 
                            <span class="text-sm font-medium text-gray-500 ml-1">(<?= $booking['duration_mins'] ?> mins)</span>
                        </p>
                    </div>
                </div>
            </div>
            
            <a href="my-bookings.php" class="inline-flex justify-center items-center gap-2 bg-[#1f2937] hover:bg-black text-white px-8 py-3.5 rounded-full font-bold text-[15px] transition-all"><i class="fas fa-list"></i> View All My Appointments</a>
        </div>

        <?php if (!empty($crossSells)): ?>
        <!-- Cross-Sell Recommendation Engine Section -->
        <div class="mt-16">
            <div class="flex flex-col items-center text-center mb-10">
                <span class="bg-indigo-50 text-[#4f46e5] font-bold text-xs px-3 py-1 rounded-full uppercase tracking-widest mb-3 border border-indigo-100">Hand-Picked For You</span>
                <h2 class="text-3xl font-extrabold text-[#111827]">Complete Your Experience</h2>
                <p class="text-gray-500 mt-2 max-w-xl">Enhance your <?= sanitize($booking['service_name']) ?> results with these specialized aftercare products.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <?php foreach ($crossSells as $product): ?>
                <div class="group bg-white rounded-3xl shadow-[0_4px_25px_rgb(0,0,0,0.03)] hover:shadow-[0_10px_40px_rgb(0,0,0,0.08)] border border-gray-100 overflow-hidden transition-all duration-300 flex flex-col relative transform hover:-translate-y-1">
                    
                    <!-- Reason Badge -->
                    <div class="absolute top-4 inset-x-0 flex justify-center z-10 w-full px-4">
                        <span class="bg-white/95 backdrop-blur-sm text-gray-800 text-[11px] font-bold px-4 py-2 rounded-full shadow-sm border border-gray-100 flex items-center gap-1.5 w-full text-center">
                            <span class="w-2 h-2 rounded-full bg-green-500 animate-pulse flex-shrink-0"></span> <span class="truncate"><?= sanitize($product['reason']) ?></span>
                        </span>
                    </div>

                    <!-- Image -->
                    <a href="product-details.php?id=<?= $product['id'] ?>" class="block pt-16 pb-6 bg-[#f8fafc] flex items-center justify-center">
                        <img src="<?= SITE_URL ?>/images/<?= $product['image'] ?>" alt="<?= sanitize($product['name']) ?>" class="h-[200px] object-contain group-hover:scale-105 transition-transform duration-500 drop-shadow-sm" onerror="this.src='https://via.placeholder.com/300x200?text=Product'">
                    </a>

                    <!-- Details -->
                    <div class="p-6 flex-1 flex flex-col bg-white">
                        <a href="product-details.php?id=<?= $product['id'] ?>" class="block flex-1">
                            <h3 class="text-[17px] font-extrabold text-gray-900 mb-2 leading-tight group-hover:text-[#4f46e5] transition-colors"><?= sanitize($product['name']) ?></h3>
                        </a>
                        
                        <div class="flex items-center justify-between mt-4 pb-4 border-b border-gray-100 mb-4">
                            <p class="text-[20px] font-black text-[#4f46e5]">
                                <?= formatPrice($product['discount_price'] ?? $product['price']) ?>
                                <?php if ($product['discount_price']): ?>
                                    <span class="text-xs text-gray-400 line-through font-normal ml-2"><?= formatPrice($product['price']) ?></span>
                                <?php endif; ?>
                            </p>
                            <!-- Score Debug info (Visible on hover for admin/dev or hidden completely; staying invisible normally) -->
                            <div class="w-8 h-8 rounded-full bg-indigo-50 flex items-center justify-center text-[#4f46e5] cursor-help" title="Algorithm Score: <?= round($product['cross_sell_score'] * 100) ?>%">
                                <i class="fas fa-sparkles text-sm"></i>
                            </div>
                        </div>

                        <!-- Add to Cart Action -->
                        <div class="flex gap-3">
                            <button onclick="addToCart(<?= $product['id'] ?>)" class="flex-1 bg-[#EEF0FF] hover:bg-[#4f46e5] hover:text-white text-[#4f46e5] py-3 rounded-xl text-[14px] font-bold transition-all flex items-center justify-center gap-2">
                                <i class="fas fa-cart-plus"></i> Add to Cart
                            </button>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <div class="text-center mt-10">
                <a href="products.php" class="text-[#6b7280] hover:text-[#4f46e5] font-semibold text-sm transition-colors decoration-2 hover:underline underline-offset-4">Browse full shop catalog &rarr;</a>
            </div>
        </div>
        <?php endif; ?>

    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
