<?php
/**
 * Common Header - Urban Glow Salon
 * Uses Tailwind CSS v4 CDN
 */
$cartCount = isLoggedIn() ? getCartCount($pdo) : 0;
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Urban Glow Salon - Premium grooming services for all. Book appointments, shop hair care, beard care & skin care products.">
    <meta name="keywords" content="salon, haircut, beard trim, facial, bridal makeup, manicure, massage, grooming products">
    <meta property="og:title" content="<?= SITE_NAME ?> - <?= SITE_TAGLINE ?>">
    <meta property="og:description" content="Experience premium grooming services. Book appointments and shop quality products online.">
    <title><?= isset($pageTitle) ? $pageTitle . ' | ' . SITE_NAME : SITE_NAME . ' - ' . SITE_TAGLINE ?></title>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <!-- Tailwind CSS v4 CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: { DEFAULT: '#4339F2', light: '#6C63FF', dark: '#3229CC', bg: '#EEF0FF' },
                        salon: { 50: '#f5f3ff', 100: '#ede9fe', 200: '#ddd6fe', 500: '#8b5cf6', 600: '#7c3aed' }
                    },
                    fontFamily: {
                        sans: ['Inter', 'system-ui', '-apple-system', 'sans-serif']
                    },
                    boxShadow: {
                        'card': '0 2px 16px rgba(67, 57, 242, 0.06)',
                        'card-hover': '0 8px 30px rgba(67, 57, 242, 0.12)',
                    }
                }
            }
        }
    </script>

    <!-- Custom Styles (Tailwind overrides & custom components) -->
    <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/custom.css">
    <?php if (isset($extraCSS)): ?>
        <?php foreach ($extraCSS as $css): ?>
            <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/<?= $css ?>">
        <?php endforeach; ?>
    <?php endif; ?>
</head>
<body class="font-sans text-gray-800 bg-white min-h-screen">
    <!-- Navigation Bar -->
    <nav class="sticky top-0 z-50 bg-white/95 backdrop-blur-md border-b border-gray-200" id="mainNav">
        <div class="max-w-7xl mx-auto px-6 h-[88px] flex items-center justify-between gap-6">
            <!-- Logo -->
            <a href="<?= SITE_URL ?>/index.php" class="text-[32px] tracking-tight font-extrabold flex-shrink-0">
                <span class="text-gray-900">Urban</span><span class="text-[#4f46e5]">Glow</span>
            </a>

            <!-- Search Bar (shown on shop/services pages) -->
            <?php if (in_array($currentPage, ['products', 'services', 'product-details'])): ?>
            <div class="flex-1 max-w-md hidden md:block">
                <form action="<?= SITE_URL ?>/<?= $currentPage === 'services' ? 'services' : 'products' ?>.php" method="GET" class="flex items-center bg-gray-100 rounded-full px-5 py-2 border-2 border-transparent focus-within:border-primary focus-within:bg-white focus-within:shadow-[0_0_0_4px_rgba(67,57,242,0.1)] transition-all">
                    <input type="text" name="search" placeholder="Search <?= $currentPage === 'services' ? 'services (e.g. Haircut)' : 'products' ?>..." value="<?= isset($_GET['search']) ? sanitize($_GET['search']) : '' ?>" id="searchInput" class="flex-1 bg-transparent border-none outline-none text-sm text-gray-800 placeholder-gray-500">
                    <button type="submit" id="searchBtn" class="text-gray-500 hover:text-primary transition-colors p-1"><i class="fas fa-search"></i></button>
                </form>
            </div>
            <?php endif; ?>

            <!-- Nav Links -->
            <ul class="hidden md:flex items-center gap-4" id="navLinks">
                <li><a href="<?= SITE_URL ?>/shop/products.php" class="px-3 py-2 text-[17px] font-semibold rounded-lg transition-all <?= $currentPage === 'products' ? 'text-[#4f46e5]' : 'text-gray-700 hover:text-[#4f46e5]' ?>">Shop</a></li>
                <li><a href="<?= SITE_URL ?>/booking/services.php" class="px-3 py-2 text-[17px] font-semibold rounded-lg transition-all <?= $currentPage === 'services' ? 'text-[#4f46e5]' : 'text-gray-700 hover:text-[#4f46e5]' ?>">Services</a></li>
                <li><a href="<?= SITE_URL ?>/customer/reviews.php" class="px-3 py-2 text-[17px] font-semibold rounded-lg transition-all <?= $currentPage === 'reviews' ? 'text-[#4f46e5]' : 'text-gray-700 hover:text-[#4f46e5]' ?>">Reviews</a></li>
            </ul>

            <!-- Nav Actions -->
            <div class="flex items-center gap-3">
                <?php if (in_array($currentPage, ['products', 'services', 'product-details'])): ?>
                <a href="<?= SITE_URL ?>/shop/cart.php" class="relative w-10 h-10 flex items-center justify-center rounded-full text-gray-700 hover:bg-primary-bg hover:text-primary transition-all" id="cartIcon">
                    <i class="fas fa-shopping-cart text-lg"></i>
                    <?php if ($cartCount > 0): ?>
                        <span class="absolute top-0.5 right-0.5 w-[18px] h-[18px] bg-red-500 text-white text-[10px] font-bold rounded-full flex items-center justify-center" id="cartBadge"><?= $cartCount ?></span>
                    <?php endif; ?>
                </a>
                <a href="#" class="w-10 h-10 flex items-center justify-center rounded-full text-gray-700 hover:bg-primary-bg hover:text-primary transition-all" id="notificationIcon">
                    <i class="fas fa-bell text-lg"></i>
                </a>
                <?php endif; ?>

                <?php if (isLoggedIn()): ?>
                    <div class="relative" id="userDropdown">
                        <button class="flex items-center gap-2 px-4 py-2.5 rounded-full text-[16px] font-bold text-gray-700 hover:bg-[#EEF0FF] hover:text-[#4f46e5] transition-all" id="userTrigger">
                            <?php if (isset($_SESSION['profile_image']) && !empty($_SESSION['profile_image'])): ?>
                                <img src="<?= SITE_URL ?>/images/profiles/<?= sanitize($_SESSION['profile_image']) ?>" alt="Profile" class="w-7 h-7 rounded-full object-cover border border-gray-200">
                            <?php else: ?>
                                <i class="fas fa-user-circle text-2xl"></i>
                            <?php endif; ?>
                            <span class="hidden sm:inline"><?= sanitize($_SESSION['username']) ?></span>
                            <i class="fas fa-chevron-down text-[10px] transition-transform" id="dropdownChevron"></i>
                        </button>
                        <div class="absolute top-full right-0 mt-3 min-w-[220px] bg-white rounded-xl shadow-[0_8px_30px_rgb(0,0,0,0.12)] border border-gray-100 py-2 opacity-0 invisible -translate-y-2 transition-all duration-200" id="dropdownMenu">
                            <a href="<?= SITE_URL ?>/customer/dashboard.php" class="flex items-center gap-3 px-5 py-2.5 text-[15px] font-medium text-gray-700 hover:bg-[#EEF0FF] hover:text-[#4f46e5] transition-all"><i class="fas fa-tachometer-alt w-4 text-center"></i> Dashboard</a>
                            <?php if (isAdmin()): ?>
                                <a href="<?= SITE_URL ?>/admin/" class="flex items-center gap-3 px-5 py-2.5 text-[15px] font-medium text-gray-700 hover:bg-[#EEF0FF] hover:text-[#4f46e5] transition-all"><i class="fas fa-cog w-4 text-center"></i> Admin Panel</a>
                            <?php endif; ?>
                            <hr class="my-2 border-gray-100">
                            <a href="<?= SITE_URL ?>/api/logout.php" class="flex items-center gap-3 px-5 py-2.5 text-[15px] font-medium text-red-600 hover:bg-red-50 transition-all"><i class="fas fa-sign-out-alt w-4 text-center"></i> Logout</a>
                        </div>
                    </div>
                <?php else: ?>
                    <a href="<?= SITE_URL ?>/login.php" class="bg-[#4f46e5] hover:bg-[#4338ca] text-white text-[15px] font-bold px-8 py-3 rounded-full transition-all hover:-translate-y-0.5 hover:shadow-lg" id="loginBtn">Login</a>
                <?php endif; ?>
            </div>

            <!-- Mobile Menu Toggle -->
            <button class="md:hidden text-xl text-gray-700 p-2" id="mobileToggle">
                <i class="fas fa-bars"></i>
            </button>
        </div>

        <!-- Mobile Nav Menu -->
        <div class="hidden md:hidden bg-white border-t border-gray-200 shadow-lg" id="mobileMenu">
            <div class="px-4 py-3 space-y-1">
                <a href="<?= SITE_URL ?>/shop/products.php" class="block px-4 py-3 text-sm font-medium rounded-lg <?= $currentPage === 'products' ? 'text-primary bg-primary-bg' : 'text-gray-700 hover:bg-gray-100' ?>">Shop</a>
                <a href="<?= SITE_URL ?>/booking/services.php" class="block px-4 py-3 text-sm font-medium rounded-lg <?= $currentPage === 'services' ? 'text-primary bg-primary-bg' : 'text-gray-700 hover:bg-gray-100' ?>">Services</a>
                <a href="<?= SITE_URL ?>/customer/reviews.php" class="block px-4 py-3 text-sm font-medium rounded-lg <?= $currentPage === 'reviews' ? 'text-primary bg-primary-bg' : 'text-gray-700 hover:bg-gray-100' ?>">Reviews</a>
            </div>
        </div>
    </nav>

    <!-- Flash Messages -->
    <?php $flash = getFlash(); ?>
    <?php if ($flash): ?>
    <div class="flex items-center justify-between px-6 py-3 text-sm font-medium animate-slide-down
        <?= $flash['type'] === 'success' ? 'bg-green-100 text-green-800 border-b-2 border-green-500' : '' ?>
        <?= $flash['type'] === 'error' ? 'bg-red-100 text-red-800 border-b-2 border-red-500' : '' ?>
        <?= $flash['type'] === 'warning' ? 'bg-yellow-100 text-yellow-800 border-b-2 border-yellow-500' : '' ?>
        <?= $flash['type'] === 'info' ? 'bg-blue-100 text-blue-800 border-b-2 border-blue-500' : '' ?>"
        id="flashMessage">
        <span><?= $flash['message'] ?></span>
        <button class="opacity-70 hover:opacity-100 text-lg transition-opacity" onclick="this.parentElement.remove()"><i class="fas fa-times"></i></button>
    </div>
    <?php endif; ?>

    <!-- Main Content -->
    <main>
