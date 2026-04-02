<?php
/**
 * Product Details Page - Urban Glow Salon
 */
require_once '../config/config.php';

$product_id = (int)($_GET['id'] ?? 0);
if (!$product_id) {
    setFlash('error', 'Product not found.');
    redirect(SITE_URL . '/shop/products.php');
}

// Fetch product
$stmt = $pdo->prepare("SELECT * FROM products WHERE id = ? AND is_active = 1");
$stmt->execute([$product_id]);
$product = $stmt->fetch();

if (!$product) {
    setFlash('error', 'Product not found.');
    redirect(SITE_URL . '/shop/products.php');
}

$pageTitle = $product['name'];

// Fetch product images
$stmt = $pdo->prepare("SELECT * FROM product_images WHERE product_id = ? ORDER BY sort_order");
$stmt->execute([$product_id]);
$images = $stmt->fetchAll();

// Fetch related products (same category)
$stmt = $pdo->prepare("SELECT * FROM products WHERE category = ? AND id != ? AND is_active = 1 LIMIT 4");
$stmt->execute([$product['category'], $product_id]);
$relatedProducts = $stmt->fetchAll();

// Fetch reviews for this product
$stmt = $pdo->prepare("SELECT r.*, u.username, u.profile_image FROM reviews r JOIN users u ON r.user_id = u.id WHERE r.review_type = 'Product' AND r.reference_id = ? ORDER BY r.created_at DESC LIMIT 5");
$stmt->execute([$product_id]);
$productReviews = $stmt->fetchAll();

require_once '../partials/header.php';
?>

<div class="bg-[#eef2f9] min-h-[calc(100vh-80px)] py-10">
    <div class="max-w-[1300px] mx-auto px-6 mb-16">
        <!-- Breadcrumb -->
        <nav class="text-[15px] font-medium text-[#4b5563] mb-8 flex flex-wrap items-center gap-2">
            <a href="index.php" class="hover:text-primary transition-colors">Home</a>
            <span class="text-gray-300">/</span>
            <a href="../shop/products.php" class="hover:text-primary transition-colors">Shop</a>
            <span class="text-gray-300">/</span>
            <a href="products.php?category=<?= urlencode($product['category']) ?>" class="hover:text-primary transition-colors"><?= sanitize($product['category']) ?></a>
            <span class="text-gray-300">/</span>
            <span class="text-[#111827] font-semibold"><?= sanitize($product['name']) ?></span>
        </nav>

        <!-- Main Card -->
        <div class="bg-white rounded-[24px] shadow-[0_6px_25px_rgb(0,0,0,0.035)] p-10 lg:p-14">
            <div class="flex flex-col lg:flex-row gap-12 lg:gap-20 pb-10">
                
                <!-- Left: Images -->
                <div class="lg:w-1/2 flex flex-col items-center">
                    <!-- Main Image Wrapper -->
                    <div class="bg-white rounded-[24px] flex items-center justify-center p-10 h-[500px] w-full relative mb-8">
                        <!-- Simulated Carousel controls -->
                        <button class="absolute -left-6 w-12 h-12 bg-white rounded-full shadow-[0_4px_15px_rgb(0,0,0,0.05)] border border-gray-50 flex items-center justify-center text-[#111827] hover:text-primary transition-colors"><i class="fas fa-chevron-left text-[16px]"></i></button>
                        <img src="<?= SITE_URL ?>/assets/images/<?= $product['image'] ?>" alt="<?= sanitize($product['name']) ?>" class="max-h-[420px] object-contain drop-shadow-2xl" id="mainImage" onerror="this.src='https://via.placeholder.com/400x300?text=<?= urlencode($product['name']) ?>'">
                        <button class="absolute -right-6 w-12 h-12 bg-white rounded-full shadow-[0_4px_15px_rgb(0,0,0,0.05)] border border-gray-50 flex items-center justify-center text-[#111827] hover:text-primary transition-colors"><i class="fas fa-chevron-right text-[16px]"></i></button>
                    </div>

                    <!-- Thumbnails -->
                    <?php if (count($images) > 1): ?>
                    <div class="flex gap-4 mb-8 overflow-x-auto pb-2 justify-center w-full">
                        <?php foreach ($images as $index => $img): ?>
                        <div class="w-[84px] h-[84px] flex-shrink-0 rounded-[14px] bg-black border-2 <?= $index === 0 ? 'border-[#3b5ae6]' : 'border-transparent hover:border-gray-200' ?> cursor-pointer transition-all overflow-hidden relative thumbnail-btn" 
                             onclick="document.getElementById('mainImage').src='<?= SITE_URL ?>/assets/images/<?= $img['image_url'] ?>'; document.querySelectorAll('.thumbnail-btn').forEach(b => { b.classList.remove('border-[#3b5ae6]'); b.classList.add('border-transparent'); }); this.classList.remove('border-transparent'); this.classList.add('border-[#3b5ae6]');">
                            <img src="<?= SITE_URL ?>/assets/images/<?= $img['image_url'] ?>" alt="" class="w-full h-full object-cover opacity-90 hover:opacity-100 transition-opacity">
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>

                    <!-- Short description / Tagline -->
                    <p class="text-[15px] text-[#8ba3c7] font-medium text-center leading-relaxed w-[95%] mx-auto">
                        <?= sanitize(truncateText($product['description'], 120)) ?>
                    </p>
                </div>

                <!-- Right: Product Info -->
                <div class="lg:w-1/2 flex flex-col pt-4 lg:pl-4">
                    <h1 class="text-[36px] font-extrabold text-[#111827] mb-8 leading-tight tracking-tight"><?= sanitize($product['name']) ?></h1>
                    
                    <div class="mb-9 border-b border-gray-100 pb-9">
                        <span class="text-[13px] font-bold text-[#8ba3c7] tracking-widest uppercase mb-2 block">Price</span>
                        <div class="flex items-center gap-5">
                            <span class="text-[40px] font-extrabold text-[#3a5cea] tracking-tight"><?= formatPrice($product['discount_price'] ?? $product['price']) ?></span>
                            <?php if ($product['discount_price']): ?>
                                <span class="text-[20px] text-[#9ca3af] line-through font-semibold"><?= formatPrice($product['price']) ?></span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Details Grid -->
                    <div class="grid grid-cols-2 gap-y-7 gap-x-10 mb-9 border-b border-gray-100 pb-9">
                        <?php if ($product['brand']): ?>
                        <div>
                            <span class="text-[13px] font-bold text-[#8ba3c7] tracking-widest uppercase block mb-3">Brand</span>
                            <div class="flex items-center gap-3">
                                <?php if ($product['brand_logo']): ?>
                                <div class="w-8 h-8 bg-[#111] rounded-full flex items-center justify-center p-1.5 overflow-hidden">
                                     <img src="<?= SITE_URL ?>/assets/images/<?= $product['brand_logo'] ?>" class="w-full h-full object-contain filter invert bg-white">
                                </div>
                                <?php endif; ?>
                                <span class="text-[17px] font-bold text-[#1f2937]"><?= sanitize($product['brand']) ?></span>
                            </div>
                        </div>
                        <?php endif; ?>

                        <div>
                            <span class="text-[13px] font-bold text-[#8ba3c7] tracking-widest uppercase block mb-3">Category</span>
                            <span class="text-[17px] font-semibold text-[#4b5563]"><?= sanitize($product['category']) ?></span>
                        </div>
                    </div>

                    <div class="grid grid-cols-3 gap-6 mb-9 border-b border-gray-100 pb-9">
                        <div>
                            <span class="text-[13px] font-bold text-[#8ba3c7] tracking-widest uppercase block mb-2">SKU</span>
                            <span class="text-[16px] font-semibold text-[#4b5563] uppercase">UG-PRD-<?= sprintf('%03d', $product['id']) ?></span>
                        </div>
                        <div>
                            <span class="text-[13px] font-bold text-[#8ba3c7] tracking-widest uppercase block mb-2">Size</span>
                            <span class="text-[16px] font-semibold text-[#4b5563]">50 ml</span>
                        </div>
                        <div>
                            <span class="text-[13px] font-bold text-[#8ba3c7] tracking-widest uppercase block mb-2">Stock</span>
                            <span class="text-[16px] font-semibold text-[#4b5563]"><?= $product['stock_quantity'] ?> in stock</span>
                        </div>
                    </div>

                    <!-- Description -->
                    <div class="mb-10">
                        <span class="text-[13px] font-bold text-[#8ba3c7] tracking-widest uppercase block mb-4">Description</span>
                        <p class="text-[17px] text-[#6b7280] leading-[1.8]"><?= sanitize($product['detailed_description'] ?? $product['description']) ?></p>
                    </div>

                    <!-- Tags -->
                    <?php if ($product['tags']): ?>
                    <div class="mb-12 border-b border-gray-100 pb-12">
                        <span class="text-[13px] font-bold text-[#8ba3c7] tracking-widest uppercase block mb-4">Tags</span>
                        <div class="flex flex-wrap gap-3">
                            <?php foreach (explode(',', $product['tags']) as $tag): ?>
                                <span class="bg-[#f3f4f6] text-[#6b7280] text-[11px] font-bold uppercase tracking-widest px-4 py-2 rounded-[8px]"><?= trim($tag) ?></span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Action Buttons (matching reference) -->
                    <div class="grid grid-cols-5 gap-4 mt-auto">
                        <button onclick="addToCart(<?= $product['id'] ?>)" class="col-span-3 flex items-center justify-center gap-3 px-8 py-5 bg-gradient-to-r from-[#4f46e5] to-[#6366f1] text-white text-[17px] font-bold rounded-xl hover:from-[#4338ca] hover:to-[#4f46e5] hover:-translate-y-1 transition-all shadow-[0_4px_15px_rgb(79,70,229,0.3)]" <?= $product['stock_quantity'] < 1 ? 'disabled style="opacity:0.5"' : '' ?>>
                            <i class="fas fa-cart-plus"></i> Add to Cart
                        </button>
                        <a href="../shop/checkout.php" onclick="addToCart(<?= $product['id'] ?>)" class="col-span-2 flex items-center justify-center gap-3 px-8 py-5 border-[2px] border-[#e5e7eb] text-[#4f46e5] bg-white text-[17px] font-bold rounded-xl hover:border-[#4f46e5] hover:-translate-y-1 transition-all shadow-sm" <?= $product['stock_quantity'] < 1 ? 'style="pointer-events:none;opacity:0.5"' : '' ?>>
                            <i class="fas fa-lock"></i> Buy Now
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Related Products -->
    <?php if (count($relatedProducts) > 0): ?>
    <section class="max-w-[1200px] mx-auto px-6 pb-10">
        <h2 class="text-3xl font-bold text-[#111827] mb-8">Related Products</h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            <?php foreach ($relatedProducts as $rp): ?>
            <a href="product-details.php?id=<?= $rp['id'] ?>" class="bg-white rounded-[20px] p-6 shadow-sm hover:shadow-md hover:-translate-y-1 transition-all duration-300">
                <div class="h-44 bg-transparent flex items-center justify-center mb-5">
                    <img src="<?= SITE_URL ?>/assets/images/<?= $rp['image'] ?>" alt="<?= sanitize($rp['name']) ?>" class="max-h-full object-contain" onerror="this.src='https://via.placeholder.com/200?text=<?= urlencode($rp['name']) ?>'">
                </div>
                <div>
                    <h3 class="font-bold text-[16px] text-[#111827] truncate mb-1.5"><?= sanitize($rp['name']) ?></h3>
                    <p class="text-[#3a5cea] font-extrabold text-[17px]"><?= formatPrice($rp['discount_price'] ?? $rp['price']) ?></p>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>
</div>

<?php require_once '../partials/footer.php'; ?>
