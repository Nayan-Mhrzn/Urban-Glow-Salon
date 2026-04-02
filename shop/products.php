<?php
/**
 * Products / Shop Page - Urban Glow Salon
 */
$pageTitle = 'Shop';
require_once '../config/config.php';

// Get filter parameters
$category = $_GET['category'] ?? '';
$brand = $_GET['brand'] ?? '';
$search = $_GET['search'] ?? '';
$sort = $_GET['sort'] ?? 'featured';

// Build query
$where = ['p.is_active = 1'];
$params = [];

if (!empty($category)) {
    $lowerCat = strtolower($category);
    // Map sidebar categories to tag searches to allow dynamic fetching
    if ($lowerCat === 'hair care') {
        $where[] = 'p.tags LIKE ?';
        $params[] = '%hair%';
    } elseif ($lowerCat === 'beard & moustache') {
        $where[] = '(p.tags LIKE ? OR p.tags LIKE ?)';
        $params[] = '%beard%';
        $params[] = '%moustache%';
    } elseif ($lowerCat === 'color & treatments') {
        $where[] = '(p.tags LIKE ? OR p.tags LIKE ?)';
        $params[] = '%color%';
        $params[] = '%treatment%';
    } else {
        // Fallback to strict category column match if tag mapping isn't found
        $where[] = 'p.category = ?';
        $params[] = $category;
    }
}
if (!empty($brand)) {
    $where[] = 'p.brand = ?';
    $params[] = $brand;
}
if (!empty($search)) {
    $where[] = '(p.name LIKE ? OR p.description LIKE ? OR p.tags LIKE ? OR p.category LIKE ?)';
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$orderBy = match($sort) {
    'price_low' => 'p.price ASC',
    'price_high' => 'p.price DESC',
    'newest' => 'p.created_at DESC',
    default => 'p.created_at DESC'
};

$whereClause = implode(' AND ', $where);
$stmt = $pdo->prepare("SELECT * FROM products p WHERE $whereClause ORDER BY $orderBy");
$stmt->execute($params);
$products = $stmt->fetchAll();

// Get brands for sidebar (hardcoded order with logos from uploads/brands/)
$brands = [
    ['brand' => 'Beardo',                  'brand_logo' => 'uploads/brands/beardo.png'],
    ['brand' => 'Ustraa',                  'brand_logo' => 'uploads/brands/ustraa.png'],
    ['brand' => "L'Oréal Professionnel",   'brand_logo' => 'uploads/brands/loreal-professionnel.png'],
    ['brand' => 'Kérastase',               'brand_logo' => 'uploads/brands/kerastase.png'],
    ['brand' => 'Olaplex',                 'brand_logo' => 'uploads/brands/olaplex.png'],
    ['brand' => 'Minimalist',              'brand_logo' => 'uploads/brands/minimalist.png'],
    ['brand' => 'Arata',                   'brand_logo' => 'uploads/brands/arata.png'],
    ['brand' => 'Garnier',                 'brand_logo' => 'uploads/brands/garnier.png'],
    ['brand' => 'The Man Company',         'brand_logo' => 'uploads/brands/the-man-company.png'],
    ['brand' => 'Plum Goodness',           'brand_logo' => 'uploads/brands/plum.png'],
];

// Get Recommendations (Only show if no active filters/searches are applied)
$isFiltering = (!empty($category) || !empty($brand) || !empty($search));
$recommendedProducts = [];
if (!$isFiltering) {
    require_once '../core/recommender.php';
    $userId = $_SESSION['user_id'] ?? 0;
    $recommendedProducts = getRecommendedProducts($pdo, $userId, 4); // Get 4 recommendations
}

// Categories
$productCategories = ['Hair Care', 'Beard & Moustache', 'Color & Treatments'];

require_once '../partials/header.php';
?>

<!-- Products Page Background -->
<div class="bg-[#f0f4f8] min-h-[calc(100vh-80px)]" style="background-image: url('data:image/svg+xml,%3Csvg width=\'20\' height=\'20\' xmlns=\'http://www.w3.org/2000/svg\'%3E%3Cpath d=\'M1 1h18v18H1V1zm1 1v16h16V2H2z\' fill=\'%23dbeafe\' fill-opacity=\'0.3\' fill-rule=\'evenodd\'/%3E%3C/svg%3E');">
    <div class="max-w-[1536px] mx-auto px-6 py-8 flex flex-col lg:flex-row gap-8">
        
        <!-- Sidebar -->
    <aside class="w-full lg:w-72 flex-shrink-0 lg:sticky lg:top-24 lg:self-start flex flex-col gap-6 lg:max-h-[calc(100vh-120px)] lg:overflow-y-auto lg:pr-1" style="scrollbar-width: thin; scrollbar-color: #cbd5e1 transparent;">
        <!-- Categories Box -->
        <div class="bg-white rounded-[32px] shadow-[0_10px_40px_-10px_rgba(0,0,0,0.06)] border-none p-8">
            <h3 class="text-[22px] font-extrabold text-[#1a2b4c] mb-6 tracking-tight">Categories</h3>
            <ul class="space-y-2 mb-2">
                <?php foreach ($productCategories as $cat): ?>
                <li>
                    <a href="products.php?category=<?= urlencode($cat) ?>" class="flex items-center w-full px-3 py-3 text-[15px] font-[600] rounded-[16px] transition-all <?= $category === $cat ? 'bg-[#f4f8ff] text-[#2563eb]' : 'text-[#1a2b4c] hover:bg-gray-50' ?>">
                        <?= $cat ?>
                    </a>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>

        <!-- Brands Box -->
        <div class="bg-white rounded-[32px] shadow-[0_10px_40px_-10px_rgba(0,0,0,0.06)] border-none p-8">
            <h3 class="text-[22px] font-extrabold text-[#1a2b4c] mb-6 tracking-tight">Brands</h3>
            <div class="space-y-2">
                <?php foreach ($brands as $b): ?>
                <a href="products.php?brand=<?= urlencode($b['brand']) ?>" class="flex items-center gap-4 px-3 py-3 rounded-[16px] text-[15px] font-[600] transition-all <?= $brand === $b['brand'] ? 'bg-[#f4f8ff] text-[#2563eb]' : 'text-[#1a2b4c] hover:bg-gray-50' ?>">
                    <div class="w-12 h-12 rounded-full bg-white border border-gray-200 shadow-sm flex items-center justify-center flex-shrink-0 overflow-hidden">
                        <?php if ($b['brand_logo']): ?>
                            <img src="<?= SITE_URL ?>/<?= $b['brand_logo'] ?>" alt="<?= sanitize($b['brand']) ?>" class="w-full h-full object-cover">
                        <?php else: ?>
                            <span class="text-[10px] font-bold text-gray-400"><?= strtoupper(substr($b['brand'], 0, 2)) ?></span>
                        <?php endif; ?>
                    </div>
                    <?= sanitize($b['brand']) ?>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
    </aside>

    <!-- Content Area -->
    <div class="flex-1 min-w-0 flex flex-col gap-8">
        
        <?php if (!$isFiltering && !empty($recommendedProducts)): ?>
        <!-- Recommended Section -->
        <div class="bg-white rounded-[32px] shadow-[0_8px_40px_rgb(0,0,0,0.04)] p-8 md:p-10 border border-white">
            <h2 class="text-[26px] font-extrabold text-[#1f2937] mb-8">Recommended For You:</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-6">
                <!-- Recommended Products Grid -->
                <?php foreach ($recommendedProducts as $product): ?>
                <div class="group bg-white rounded-2xl border border-gray-100 p-5 hover:shadow-lg hover:shadow-slate-200/60 transition-all duration-300 relative flex flex-col">
                    <?php 
                    $stockClass = 'bg-[#ecfdf5] text-[#10b981]';
                    $stockText = 'In Stock';
                    if ($product['stock_quantity'] <= 0) {
                        $stockClass = 'bg-red-50 text-red-600';
                        $stockText = 'Out of Stock';
                    } elseif ($product['stock_quantity'] <= 10) {
                        $stockClass = 'bg-orange-50 text-orange-600';
                        $stockText = 'Low Stock';
                    }
                    ?>
                    <span class="absolute top-4 right-4 z-10 <?= $stockClass ?> text-[11px] font-bold px-3 py-1.5 rounded-full">
                        <?= $stockText ?>
                    </span>

                    <a href="product-details.php?id=<?= $product['id'] ?>" class="block h-[180px] bg-slate-50 rounded-2xl flex items-center justify-center mb-5">
                        <img src="<?= SITE_URL ?>/assets/images/<?= $product['image'] ?>" alt="<?= sanitize($product['name']) ?>" class="max-h-[160px] object-contain group-hover:scale-105 transition-transform duration-500" onerror="this.src='https://via.placeholder.com/300x200?text=<?= urlencode($product['name']) ?>'">
                    </a>

                    <div class="flex-1 flex flex-col">
                        <a href="product-details.php?id=<?= $product['id'] ?>" class="block">
                            <h3 class="text-[15px] font-bold text-slate-800 mb-1 truncate hover:text-[#4f46e5] transition-colors"><?= sanitize($product['name']) ?></h3>
                        </a>
                        <p class="text-[12px] text-slate-500 mb-4 line-clamp-2 leading-relaxed flex-1"><?= sanitize($product['description']) ?></p>
                        
                        <p class="text-[18px] font-extrabold text-[#3b82f6] mb-4">
                            <?= formatPrice($product['discount_price'] ?? $product['price']) ?>
                            <?php if ($product['discount_price']): ?>
                                <span class="text-xs text-gray-400 line-through font-normal ml-1"><?= formatPrice($product['price']) ?></span>
                            <?php endif; ?>
                        </p>
                        
                        <div class="flex gap-2.5 mt-auto">
                            <button onclick="addToCart(<?= $product['id'] ?>)" class="flex-1 flex items-center justify-center gap-1.5 px-3 py-2.5 text-[13px] font-bold border-2 border-[#4f46e5] text-[#4f46e5] rounded-xl hover:bg-[#EEF0FF] transition-colors" <?= $product['stock_quantity'] < 1 ? 'disabled style="opacity:0.5"' : '' ?>>
                                <i class="fas fa-cart-plus"></i> Cart
                            </button>
                            <a href="../shop/checkout.php" onclick="addToCart(<?= $product['id'] ?>)" class="flex-1 flex items-center justify-center gap-1.5 px-3 py-2.5 text-[13px] font-bold bg-gradient-to-r from-[#4f46e5] to-[#6366f1] text-white rounded-xl hover:from-[#4338ca] hover:to-[#4f46e5] transition-all shadow-md shadow-indigo-200" <?= $product['stock_quantity'] < 1 ? 'style="pointer-events:none;opacity:0.5"' : '' ?>>
                                <i class="fas fa-lock"></i> Buy
                            </a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>


        <!-- Main Products Section -->
        <div class="bg-white rounded-[32px] shadow-[0_8px_40px_rgb(0,0,0,0.04)] p-8 md:p-10 border border-white">
            <!-- Header with Filters -->
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between mb-8 gap-4">
                <h2 class="text-[26px] font-extrabold text-[#1f2937]"><?= $isFiltering ? 'Search Results:' : 'Continue Shopping:' ?></h2>
                <div class="flex items-center gap-3">
                    <!-- Custom Sort Dropdown matching reference site -->
                    <div class="relative" id="sortDropdownContainer">
                        <button onclick="document.getElementById('sortDropdownMenu').classList.toggle('hidden')" class="flex items-center gap-2 bg-white border border-slate-200 hover:border-blue-500 rounded-xl px-4 py-2.5 text-sm font-medium text-slate-700 shadow-sm transition-all shadow-slate-200/50">
                            <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4h13M3 8h9m-9 4h6m4 0l4-4m0 0l4 4m-4-4v12"></path>
                            </svg>
                            <span class="font-medium">
                                <?php 
                                    if ($sort === 'price_low') echo 'Price: Low to High';
                                    elseif ($sort === 'price_high') echo 'Price: High to Low';
                                    else echo 'Sort by: Featured';
                                ?>
                            </span>
                            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </button>

                        <div id="sortDropdownMenu" class="hidden absolute right-0 mt-2 w-48 bg-white rounded-xl shadow-xl shadow-slate-200/50 border border-slate-100 p-2 z-50">
                            <?php 
                            $baseParams = '';
                            if (!empty($category)) $baseParams .= '&category='.urlencode($category);
                            if (!empty($brand)) $baseParams .= '&brand='.urlencode($brand);
                            if (!empty($search)) $baseParams .= '&search='.urlencode($search);
                            ?>
                            <a href="products.php?sort=featured<?= $baseParams ?>" 
                               class="block w-full text-left px-4 py-2 rounded-lg text-sm transition-all <?= $sort==='featured' ? 'font-bold bg-blue-50 text-blue-700' : 'text-slate-700 hover:bg-blue-50 hover:text-blue-600' ?>">
                               Sort by: Featured
                            </a>
                            <a href="products.php?sort=price_low<?= $baseParams ?>" 
                               class="block w-full text-left px-4 py-2 rounded-lg text-sm transition-all <?= $sort==='price_low' ? 'font-bold bg-blue-50 text-blue-700' : 'text-slate-700 hover:bg-blue-50 hover:text-blue-600' ?>">
                               Price: Low to High
                            </a>
                            <a href="products.php?sort=price_high<?= $baseParams ?>" 
                               class="block w-full text-left px-4 py-2 rounded-lg text-sm transition-all <?= $sort==='price_high' ? 'font-bold bg-blue-50 text-blue-700' : 'text-slate-700 hover:bg-blue-50 hover:text-blue-600' ?>">
                               Price: High to Low
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <script>
                // Close dropdown when clicking outside
                document.addEventListener('click', function(event) {
                    const container = document.getElementById('sortDropdownContainer');
                    const menu = document.getElementById('sortDropdownMenu');
                    if (container && menu && !container.contains(event.target)) {
                        menu.classList.add('hidden');
                    }
                });
            </script>

            <?php if ($isFiltering): ?>
            <div class="flex items-center gap-2 mb-6 flex-wrap">
                <span class="text-sm font-semibold text-gray-500">Active Filters:</span>
                <?php if (!empty($category)): ?>
                    <a href="products.php<?= !empty($brand) ? '?brand='.urlencode($brand) : '' ?>" class="inline-flex items-center gap-2 bg-[#EEF0FF] text-[#4f46e5] text-xs font-bold px-3 py-1.5 rounded-full"><?= sanitize($category) ?> <i class="fas fa-times opacity-60 hover:opacity-100"></i></a>
                <?php endif; ?>
                <?php if (!empty($brand)): ?>
                    <a href="products.php<?= !empty($category) ? '?category='.urlencode($category) : '' ?>" class="inline-flex items-center gap-2 bg-[#EEF0FF] text-[#4f46e5] text-xs font-bold px-3 py-1.5 rounded-full"><?= sanitize($brand) ?> <i class="fas fa-times opacity-60 hover:opacity-100"></i></a>
                <?php endif; ?>
                <?php if (!empty($search)): ?>
                    <a href="../shop/products.php" class="inline-flex items-center gap-2 bg-[#EEF0FF] text-[#4f46e5] text-xs font-bold px-3 py-1.5 rounded-full">"<?= sanitize($search) ?>" <i class="fas fa-times opacity-60 hover:opacity-100"></i></a>
                <?php endif; ?>
                <a href="../shop/products.php" class="text-xs font-bold text-red-500 hover:text-red-700 ml-2">Clear all</a>
            </div>
            <?php endif; ?>

            <?php if (count($products) > 0): ?>
            <!-- Product Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                <?php foreach ($products as $product): ?>
                <div class="group bg-white rounded-2xl border border-gray-100 p-5 hover:shadow-lg hover:shadow-slate-200/60 transition-all duration-300 relative flex flex-col">
                    <!-- Stock Badge -->
                    <?php 
                    $stockClass = 'bg-[#ecfdf5] text-[#10b981]';
                    $stockText = 'In Stock';
                    if ($product['stock_quantity'] <= 0) {
                        $stockClass = 'bg-red-50 text-red-600';
                        $stockText = 'Out of Stock';
                    } elseif ($product['stock_quantity'] <= 10) {
                        $stockClass = 'bg-orange-50 text-orange-600';
                        $stockText = 'Low Stock';
                    }
                    ?>
                    <span class="absolute top-4 right-4 z-10 <?= $stockClass ?> text-[11px] font-bold px-3 py-1.5 rounded-full">
                        <?= $stockText ?>
                    </span>

                    <!-- Product Image -->
                    <a href="product-details.php?id=<?= $product['id'] ?>" class="block h-[180px] bg-slate-50 rounded-2xl flex items-center justify-center mb-5">
                        <img src="<?= SITE_URL ?>/assets/images/<?= $product['image'] ?>" alt="<?= sanitize($product['name']) ?>" class="max-h-[160px] object-contain group-hover:scale-105 transition-transform duration-500" onerror="this.src='https://via.placeholder.com/300x200?text=<?= urlencode($product['name']) ?>'">
                    </a>

                    <!-- Product Info -->
                    <div class="flex-1 flex flex-col">
                        <a href="product-details.php?id=<?= $product['id'] ?>" class="block">
                            <h3 class="text-[15px] font-bold text-slate-800 mb-1 truncate hover:text-[#4f46e5] transition-colors"><?= sanitize($product['name']) ?></h3>
                        </a>
                        <p class="text-[12px] text-slate-500 mb-4 line-clamp-2 leading-relaxed flex-1"><?= sanitize($product['description']) ?></p>
                        <p class="text-[18px] font-extrabold text-[#3b82f6] mb-4">
                            <?= formatPrice($product['discount_price'] ?? $product['price']) ?>
                            <?php if ($product['discount_price']): ?>
                                <span class="text-xs text-gray-400 line-through font-normal ml-1"><?= formatPrice($product['price']) ?></span>
                            <?php endif; ?>
                        </p>
                        <div class="flex gap-2.5 mt-auto">
                            <button onclick="addToCart(<?= $product['id'] ?>)" class="flex-1 flex items-center justify-center gap-1.5 px-3 py-2.5 text-[13px] font-bold border-2 border-[#4f46e5] text-[#4f46e5] rounded-xl hover:bg-[#EEF0FF] transition-colors" <?= $product['stock_quantity'] < 1 ? 'disabled style="opacity:0.5"' : '' ?>>
                                <i class="fas fa-cart-plus"></i> Cart
                            </button>
                            <a href="../shop/checkout.php" onclick="addToCart(<?= $product['id'] ?>)" class="flex-1 flex items-center justify-center gap-1.5 px-3 py-2.5 text-[13px] font-bold bg-gradient-to-r from-[#4f46e5] to-[#6366f1] text-white rounded-xl hover:from-[#4338ca] hover:to-[#4f46e5] transition-all shadow-md shadow-indigo-200" <?= $product['stock_quantity'] < 1 ? 'style="pointer-events:none;opacity:0.5"' : '' ?>>
                                <i class="fas fa-lock"></i> Buy
                            </a>
                        </div>
                    </div>
                </div>

                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <div class="text-center py-20 bg-gray-50 rounded-2xl border-2 border-dashed border-gray-200">
                <i class="fas fa-shopping-bag text-6xl text-gray-300 mb-4"></i>
                <h3 class="text-xl font-semibold text-gray-700 mb-2">No products found</h3>
                <p class="text-gray-500 mb-6 font-medium">Try adjusting your search or filters.</p>
                <a href="../shop/products.php" class="bg-[#4f46e5] text-white px-8 py-3 rounded-full text-[14px] font-bold hover:bg-[#4338ca] transition-all">View All Products</a>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once '../partials/footer.php'; ?>
