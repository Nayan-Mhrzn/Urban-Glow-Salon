<?php
/**
 * Home Page - Urban Glow Salon
 */
$pageTitle = 'Home';
require_once 'includes/config.php';

// Fetch featured products (newest 4)
$stmt = $pdo->query("SELECT * FROM products WHERE is_active = 1 ORDER BY created_at DESC LIMIT 4");
$featuredProducts = $stmt->fetchAll();

// Fetch popular services (first 6)
$stmt = $pdo->query("SELECT * FROM services WHERE is_active = 1 ORDER BY price DESC LIMIT 6");
$popularServices = $stmt->fetchAll();

// Fetch recent reviews with user info
$stmt = $pdo->query("SELECT r.*, u.username, u.profile_image FROM reviews r JOIN users u ON r.user_id = u.id ORDER BY r.created_at DESC LIMIT 3");
$recentReviews = $stmt->fetchAll();

require_once 'includes/header.php';
?>

<!-- ===== HERO SECTION ===== -->
<style>
@keyframes gentleBounce {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-15px); }
}
.animate-gentle-bounce {
    animation: gentleBounce 3s ease-in-out infinite;
}
</style>
<section class="hero-gradient py-16 md:py-24 relative overflow-hidden">
    <div class="max-w-7xl mx-auto px-6 flex flex-col md:flex-row items-center justify-between gap-12">
        <!-- Hero Content -->
        <div class="flex-1 max-w-2xl text-center md:text-left pt-10">
            <h1 class="text-5xl md:text-6xl lg:text-[76px] font-black leading-[1.05] tracking-tight mb-8 animate-fade-in-up">
                Premium<br>
                <span class="text-[#4f46e5]">Grooming</span><br>
                For All
            </h1>
            <p class="text-gray-600 text-lg md:text-xl lg:text-[22px] leading-relaxed mb-4 max-w-xl">
                Experience the perfect blend of traditional grooming and modern style. At Urban Glow, we craft more than haircuts.
            </p>
            <p class="text-xl md:text-2xl lg:text-[28px] font-bold text-gray-900 mb-10">
                We Create <span class="text-[#4f46e5]">Confidence.</span>
            </p>
            <div class="flex flex-col sm:flex-row gap-5 justify-center md:justify-start">
                <a href="products.php" class="bg-[#4f46e5] hover:bg-[#4338ca] text-white font-bold px-10 py-4 rounded-full transition-all hover:-translate-y-1 hover:shadow-lg text-[17px] inline-flex items-center justify-center gap-2">
                    Shop Now
                </a>
                <a href="book-appointment.php" class="bg-white hover:bg-[#EEF0FF] text-[#4f46e5] font-bold px-10 py-4 rounded-full border-[2px] border-[#4f46e5] transition-all hover:-translate-y-1 hover:shadow-lg text-[17px] inline-flex items-center justify-center gap-2">
                    Book Now
                </a>
            </div>
        </div>

        <!-- Hero Image -->
        <div class="flex-1 flex justify-center">
            <div class="animate-gentle-bounce w-[272px] h-[272px] md:w-[408px] md:h-[408px] lg:w-[510px] lg:h-[510px] rounded-full bg-white shadow-[0_0_0_20px_rgba(255,255,255,0.5),0_0_0_40px_rgba(255,255,255,0.3),0_40px_80px_-10px_rgba(0,0,0,0.3)] flex items-center justify-center overflow-hidden">
                <img src="<?= SITE_URL ?>/uploads/1/barbershop%20full%20of%20clients-rafiki.svg" alt="Urban Glow Salon" class="w-[85%] h-[85%] object-contain">
            </div>
        </div>
    </div>
</section>

<!-- ===== NEW ARRIVALS (Products) ===== -->
<section class="py-16 md:py-20" id="newArrivals">
    <div class="max-w-7xl mx-auto px-6">
        <!-- Section Header -->
        <div class="text-center mb-12">
            <h2 class="text-3xl md:text-4xl font-bold mb-2">New <span class="text-primary">Arrivals</span></h2>
            <p class="text-gray-600">Discover our latest grooming products</p>
        </div>

        <!-- Product Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            <?php foreach ($featuredProducts as $product): ?>
            <div class="group bg-white rounded-2xl shadow-card border border-gray-100 overflow-hidden hover:shadow-card-hover hover:-translate-y-1 transition-all duration-300 relative" data-animate>
                <!-- Stock Badge -->
                <?php if ($product['stock_quantity'] > 0): ?>
                <span class="absolute top-4 right-4 z-10 bg-green-500 text-white text-xs font-semibold px-2.5 py-1 rounded-md">In Stock</span>
                <?php else: ?>
                <span class="absolute top-4 right-4 z-10 bg-red-500 text-white text-xs font-semibold px-2.5 py-1 rounded-md">Out of Stock</span>
                <?php endif; ?>

                <!-- Product Image -->
                <div class="h-52 bg-gray-50 flex items-center justify-center p-6 overflow-hidden">
                    <img src="<?= SITE_URL ?>/images/<?= $product['image'] ?>" alt="<?= sanitize($product['name']) ?>" class="max-h-full object-contain group-hover:scale-110 transition-transform duration-500">
                </div>

                <!-- Product Info -->
                <div class="p-5">
                    <h3 class="font-semibold text-gray-900 mb-1 truncate"><?= sanitize($product['name']) ?></h3>
                    <p class="text-sm text-gray-500 mb-2 line-clamp-2"><?= sanitize(truncateText($product['description'], 80)) ?></p>
                    <p class="text-lg font-bold text-primary mb-3">
                        <?= formatPrice($product['discount_price'] ?? $product['price']) ?>
                        <?php if ($product['discount_price']): ?>
                            <span class="text-sm text-gray-400 line-through font-normal ml-2"><?= formatPrice($product['price']) ?></span>
                        <?php endif; ?>
                    </p>
                    <div class="flex gap-2">
                        <button onclick="addToCart(<?= $product['id'] ?>)" class="flex-1 flex items-center justify-center gap-1.5 px-3 py-2 text-xs font-semibold border-2 border-gray-200 rounded-full text-gray-700 hover:border-primary hover:text-primary transition-all">
                            <i class="fas fa-cart-plus"></i> Cart
                        </button>
                        <a href="product-details.php?id=<?= $product['id'] ?>" class="flex-1 flex items-center justify-center gap-1.5 px-3 py-2 text-xs font-semibold bg-primary text-white rounded-full hover:bg-primary-dark transition-all">
                            <i class="fas fa-shopping-bag"></i> Buy
                        </a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- View All Link -->
        <div class="text-center mt-10">
            <a href="products.php" class="inline-flex items-center gap-2 text-primary font-semibold hover:gap-3 transition-all">
                View All Products <i class="fas fa-arrow-right"></i>
            </a>
        </div>
    </div>
</section>

<!-- ===== POPULAR SERVICES ===== -->
<section class="py-16 md:py-20 bg-gray-50" id="popularServices">
    <div class="max-w-7xl mx-auto px-6">
        <!-- Section Header -->
        <div class="text-center mb-12">
            <h2 class="text-3xl md:text-4xl font-bold mb-2">Our <span class="text-primary">Services</span></h2>
            <p class="text-gray-600">Professional grooming services for everyone</p>
        </div>

        <!-- Services Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php 
            $categoryColors = [
                'Hair' => 'bg-primary',
                'Skin' => 'bg-orange-500',
                'Body' => 'bg-green-500',
                'Makeup' => 'bg-pink-500',
                'Nails' => 'bg-purple-500'
            ];
            foreach ($popularServices as $service): 
                $badgeColor = $categoryColors[$service['category']] ?? 'bg-gray-500';
            ?>
            <div class="group bg-white rounded-2xl shadow-card border border-gray-100 overflow-hidden hover:shadow-card-hover hover:-translate-y-1 transition-all duration-300 relative" data-animate>
                <!-- Category Badge -->
                <span class="absolute top-4 left-4 z-10 <?= $badgeColor ?> text-white text-xs font-semibold px-3 py-1 rounded-md"><?= $service['category'] ?></span>

                <!-- Service Image -->
                <div class="h-48 bg-gray-100 overflow-hidden">
                    <img src="<?= SITE_URL ?>/images/<?= $service['image'] ?>" alt="<?= sanitize($service['name']) ?>" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500" onerror="this.src='https://via.placeholder.com/400x200?text=<?= urlencode($service['name']) ?>'">
                </div>

                <!-- Service Info -->
                <div class="p-5">
                    <h3 class="font-semibold text-gray-900 text-lg mb-1"><?= sanitize($service['name']) ?></h3>
                    <p class="text-xl font-bold text-primary mb-2"><?= formatPrice($service['price']) ?></p>
                    <p class="text-sm text-gray-500 mb-3"><?= sanitize(truncateText($service['description'], 80)) ?></p>
                    <div class="flex items-center gap-4 text-xs text-gray-500">
                        <span class="flex items-center gap-1"><i class="far fa-clock"></i> <?= $service['duration_mins'] ?> mins</span>
                        <span class="flex items-center gap-1"><i class="fas fa-users"></i> <?= $service['gender'] ?></span>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- View All Link -->
        <div class="text-center mt-10">
            <a href="services.php" class="inline-flex items-center gap-2 text-primary font-semibold hover:gap-3 transition-all">
                Explore All Services <i class="fas fa-arrow-right"></i>
            </a>
        </div>
    </div>
</section>

<!-- ===== WHY CHOOSE US ===== -->
<section class="py-16 md:py-20">
    <div class="max-w-7xl mx-auto px-6">
        <div class="text-center mb-12">
            <h2 class="text-3xl md:text-4xl font-bold mb-2">Why Choose <span class="text-primary">Us</span></h2>
            <p class="text-gray-600">What makes Urban Glow Salon special</p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8">
            <div class="text-center group" data-animate>
                <div class="w-16 h-16 mx-auto mb-4 bg-primary-bg rounded-2xl flex items-center justify-center text-primary text-2xl group-hover:bg-primary group-hover:text-white group-hover:scale-110 group-hover:rotate-6 transition-all duration-300">
                    <i class="fas fa-cut"></i>
                </div>
                <h4 class="font-semibold text-gray-900 mb-2">Expert Stylists</h4>
                <p class="text-sm text-gray-500">Trained professionals with years of experience</p>
            </div>
            <div class="text-center group" data-animate>
                <div class="w-16 h-16 mx-auto mb-4 bg-primary-bg rounded-2xl flex items-center justify-center text-primary text-2xl group-hover:bg-primary group-hover:text-white group-hover:scale-110 group-hover:rotate-6 transition-all duration-300">
                    <i class="fas fa-calendar-check"></i>
                </div>
                <h4 class="font-semibold text-gray-900 mb-2">Easy Booking</h4>
                <p class="text-sm text-gray-500">Book your appointment online in seconds</p>
            </div>
            <div class="text-center group" data-animate>
                <div class="w-16 h-16 mx-auto mb-4 bg-primary-bg rounded-2xl flex items-center justify-center text-primary text-2xl group-hover:bg-primary group-hover:text-white group-hover:scale-110 group-hover:rotate-6 transition-all duration-300">
                    <i class="fas fa-gem"></i>
                </div>
                <h4 class="font-semibold text-gray-900 mb-2">Premium Products</h4>
                <p class="text-sm text-gray-500">Only the best quality grooming products</p>
            </div>
            <div class="text-center group" data-animate>
                <div class="w-16 h-16 mx-auto mb-4 bg-primary-bg rounded-2xl flex items-center justify-center text-primary text-2xl group-hover:bg-primary group-hover:text-white group-hover:scale-110 group-hover:rotate-6 transition-all duration-300">
                    <i class="fas fa-heart"></i>
                </div>
                <h4 class="font-semibold text-gray-900 mb-2">Unisex Salon</h4>
                <p class="text-sm text-gray-500">Services designed for men and women</p>
            </div>
        </div>
    </div>
</section>

<!-- ===== CUSTOMER REVIEWS ===== -->
<section class="py-16 md:py-20 hero-gradient" id="reviews">
    <div class="max-w-7xl mx-auto px-6">
        <div class="text-center mb-12">
            <h2 class="text-3xl md:text-4xl font-bold mb-2">Customer <span class="text-primary">Reviews</span></h2>
            <p class="text-gray-600">See what our customers say about us</p>
        </div>

        <?php if (count($recentReviews) > 0): ?>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <?php foreach ($recentReviews as $review): ?>
            <div class="bg-white rounded-2xl shadow-card border border-gray-100 p-6 hover:shadow-card-hover transition-all duration-300" data-animate>
                <!-- Review Header -->
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-12 h-12 rounded-full bg-gray-200 flex items-center justify-center overflow-hidden">
                        <?php if ($review['profile_image']): ?>
                            <img src="<?= SITE_URL ?>/uploads/<?= $review['profile_image'] ?>" alt="" class="w-full h-full object-cover">
                        <?php else: ?>
                            <i class="fas fa-user text-gray-400 text-lg"></i>
                        <?php endif; ?>
                    </div>
                    <div>
                        <p class="font-semibold text-gray-900 text-sm"><?= sanitize($review['username']) ?></p>
                        <div class="flex gap-0.5 mb-0.5">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <i class="fas fa-star text-xs <?= $i <= $review['rating'] ? 'text-amber-400' : 'text-gray-300' ?>"></i>
                            <?php endfor; ?>
                        </div>
                        <p class="text-xs text-gray-400"><?= date('M d, Y', strtotime($review['created_at'])) ?></p>
                    </div>
                </div>
                <!-- Review Content -->
                <p class="font-semibold text-gray-800 text-sm mb-1"><?= sanitize($review['review_type']) ?></p>
                <p class="text-sm text-gray-600 leading-relaxed"><?= sanitize(truncateText($review['comment'], 150)) ?></p>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="text-center py-10">
            <i class="fas fa-star text-5xl text-gray-300 mb-4"></i>
            <p class="text-gray-500">No reviews yet. Be the first to share your experience!</p>
        </div>
        <?php endif; ?>

        <div class="text-center mt-10">
            <a href="reviews.php" class="inline-flex items-center gap-2 text-primary font-semibold hover:gap-3 transition-all">
                View All Reviews <i class="fas fa-arrow-right"></i>
            </a>
        </div>
    </div>
</section>

<!-- ===== CTA SECTION ===== -->
<section class="py-16 md:py-20">
    <div class="max-w-4xl mx-auto px-6 text-center">
        <div class="bg-gradient-to-br from-primary to-primary-light rounded-3xl p-12 md:p-16 text-white relative overflow-hidden">
            <!-- Decorative circles -->
            <div class="absolute top-0 right-0 w-40 h-40 bg-white/10 rounded-full -translate-y-1/2 translate-x-1/2"></div>
            <div class="absolute bottom-0 left-0 w-32 h-32 bg-white/10 rounded-full translate-y-1/2 -translate-x-1/2"></div>
            
            <h2 class="text-3xl md:text-4xl font-bold mb-4 relative z-10">Ready for a Fresh Look?</h2>
            <p class="text-lg text-white/80 mb-8 max-w-lg mx-auto relative z-10">Book your appointment today and experience premium grooming at Urban Glow Salon.</p>
            <div class="flex flex-col sm:flex-row gap-4 justify-center relative z-10">
                <a href="book-appointment.php" class="bg-white text-primary font-semibold px-8 py-3.5 rounded-full hover:-translate-y-1 hover:shadow-xl transition-all text-sm inline-flex items-center justify-center gap-2">
                    <i class="fas fa-calendar-alt"></i> Book Appointment
                </a>
                <a href="services.php" class="bg-transparent text-white font-semibold px-8 py-3.5 rounded-full border-2 border-white/50 hover:bg-white/10 hover:-translate-y-1 transition-all text-sm inline-flex items-center justify-center gap-2">
                    <i class="fas fa-spa"></i> Explore Services
                </a>
            </div>
        </div>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>
