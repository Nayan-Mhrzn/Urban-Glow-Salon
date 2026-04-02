<?php
/**
 * Admin Header / Layout - Urban Glow Salon
 * Includes sidebar + top bar
 */
requireAdmin();

$adminPage = basename($_SERVER['PHP_SELF'], '.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? $pageTitle . ' | Admin' : 'Admin Panel' ?> — <?= SITE_NAME ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: { DEFAULT: '#4339F2', light: '#6C63FF', dark: '#3229CC', bg: '#EEF0FF' },
                        sidebar: { DEFAULT: '#0F172A', hover: '#1E293B', active: '#334155' }
                    },
                    fontFamily: { sans: ['Inter', 'system-ui', 'sans-serif'] }
                }
            }
        }
    </script>
    <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/custom.css">
</head>
<body class="font-sans bg-gray-100 text-gray-800">
    <div class="flex min-h-screen">

    <!-- Sidebar -->
    <aside class="w-64 bg-sidebar text-gray-300 flex-shrink-0 fixed h-full z-40 flex flex-col transition-transform -translate-x-full lg:translate-x-0" id="adminSidebar">
        <!-- Logo -->
        <div class="h-16 flex items-center px-6 border-b border-white/10">
            <a href="<?= SITE_URL ?>/admin/" class="text-xl font-extrabold">
                <span class="text-white">Urban</span><span class="text-primary-light">Glow</span>
                <span class="text-[10px] font-medium text-gray-500 ml-1 uppercase tracking-wider">Admin</span>
            </a>
        </div>

        <!-- Nav Menu -->
        <nav class="flex-1 py-4 px-3 space-y-1 overflow-y-auto">
            <p class="px-4 py-1 text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Main</p>
            <a href="<?= SITE_URL ?>/admin/" class="flex items-center gap-3 px-4 py-2.5 rounded-lg text-sm font-medium transition-all <?= $adminPage === 'index' ? 'bg-primary text-white' : 'text-gray-400 hover:bg-sidebar-hover hover:text-white' ?>">
                <i class="fas fa-tachometer-alt w-5 text-center"></i> Dashboard
            </a>
            <a href="<?= SITE_URL ?>/admin/bookings.php" class="flex items-center gap-3 px-4 py-2.5 rounded-lg text-sm font-medium transition-all <?= $adminPage === 'bookings' ? 'bg-primary text-white' : 'text-gray-400 hover:bg-sidebar-hover hover:text-white' ?>">
                <i class="fas fa-calendar-check w-5 text-center"></i> Bookings
            </a>
            <a href="<?= SITE_URL ?>/admin/services.php" class="flex items-center gap-3 px-4 py-2.5 rounded-lg text-sm font-medium transition-all <?= $adminPage === 'services' ? 'bg-primary text-white' : 'text-gray-400 hover:bg-sidebar-hover hover:text-white' ?>">
                <i class="fas fa-spa w-5 text-center"></i> Services
            </a>

            <p class="px-4 pt-4 pb-1 text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Shop</p>
            <a href="<?= SITE_URL ?>/admin/products.php" class="flex items-center gap-3 px-4 py-2.5 rounded-lg text-sm font-medium transition-all <?= $adminPage === 'products' ? 'bg-primary text-white' : 'text-gray-400 hover:bg-sidebar-hover hover:text-white' ?>">
                <i class="fas fa-box w-5 text-center"></i> Products
            </a>
            <a href="<?= SITE_URL ?>/admin/orders.php" class="flex items-center gap-3 px-4 py-2.5 rounded-lg text-sm font-medium transition-all <?= $adminPage === 'orders' ? 'bg-primary text-white' : 'text-gray-400 hover:bg-sidebar-hover hover:text-white' ?>">
                <i class="fas fa-shopping-bag w-5 text-center"></i> Orders
            </a>

            <p class="px-4 pt-4 pb-1 text-[10px] font-semibold text-gray-500 uppercase tracking-widest">People</p>
            <a href="<?= SITE_URL ?>/admin/users.php" class="flex items-center gap-3 px-4 py-2.5 rounded-lg text-sm font-medium transition-all <?= $adminPage === 'users' ? 'bg-primary text-white' : 'text-gray-400 hover:bg-sidebar-hover hover:text-white' ?>">
                <i class="fas fa-users w-5 text-center"></i> Customers
            </a>
            <a href="<?= SITE_URL ?>/admin/staff.php" class="flex items-center gap-3 px-4 py-2.5 rounded-lg text-sm font-medium transition-all <?= $adminPage === 'staff' ? 'bg-primary text-white' : 'text-gray-400 hover:bg-sidebar-hover hover:text-white' ?>">
                <i class="fas fa-user-tie w-5 text-center"></i> Staff
            </a>
            <a href="<?= SITE_URL ?>/admin/reviews.php" class="flex items-center gap-3 px-4 py-2.5 rounded-lg text-sm font-medium transition-all <?= $adminPage === 'reviews' ? 'bg-primary text-white' : 'text-gray-400 hover:bg-sidebar-hover hover:text-white' ?>">
                <i class="fas fa-star w-5 text-center"></i> Reviews
            </a>

            <p class="px-4 pt-4 pb-1 text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Insights</p>
            <a href="<?= SITE_URL ?>/admin/reports.php" class="flex items-center gap-3 px-4 py-2.5 rounded-lg text-sm font-medium transition-all <?= $adminPage === 'reports' ? 'bg-primary text-white' : 'text-gray-400 hover:bg-sidebar-hover hover:text-white' ?>">
                <i class="fas fa-chart-bar w-5 text-center"></i> Reports
            </a>
            <a href="<?= SITE_URL ?>/admin/settings.php" class="flex items-center gap-3 px-4 py-2.5 rounded-lg text-sm font-medium transition-all <?= $adminPage === 'settings' ? 'bg-primary text-white' : 'text-gray-400 hover:bg-sidebar-hover hover:text-white' ?>">
                <i class="fas fa-cog w-5 text-center"></i> Settings
            </a>
        </nav>

        <!-- Bottom -->
        <div class="p-4 border-t border-white/10">
            <a href="<?= SITE_URL ?>/index.php" class="flex items-center gap-3 px-4 py-2.5 rounded-lg text-sm text-gray-500 hover:bg-sidebar-hover hover:text-white transition-all">
                <i class="fas fa-external-link-alt w-5 text-center"></i> View Site
            </a>
            <a href="<?= SITE_URL ?>/api/logout.php" class="flex items-center gap-3 px-4 py-2.5 rounded-lg text-sm text-gray-500 hover:bg-red-500/20 hover:text-red-400 transition-all">
                <i class="fas fa-sign-out-alt w-5 text-center"></i> Logout
            </a>
        </div>
    </aside>

    <!-- Main Content -->
    <div class="flex-1 lg:ml-64">
        <!-- Top Bar -->
        <header class="h-16 bg-white border-b border-gray-200 flex items-center justify-between px-6 sticky top-0 z-30">
            <div class="flex items-center gap-4">
                <button class="lg:hidden text-gray-700 text-xl" onclick="document.getElementById('adminSidebar').classList.toggle('-translate-x-full')">
                    <i class="fas fa-bars"></i>
                </button>
                <h1 class="text-lg font-bold text-gray-900"><?= $pageTitle ?? 'Dashboard' ?></h1>
            </div>
            <div class="flex items-center gap-3">
                <span class="text-sm text-gray-500">Welcome, <span class="font-semibold text-gray-800"><?= sanitize($_SESSION['username']) ?></span></span>
                <div class="w-9 h-9 rounded-full bg-primary flex items-center justify-center text-white text-sm font-bold">
                    <?= strtoupper(substr($_SESSION['username'], 0, 1)) ?>
                </div>
            </div>
        </header>

        <!-- Flash Messages -->
        <?php $flash = getFlash(); ?>
        <?php if ($flash): ?>
        <div class="mx-6 mt-4 px-4 py-3 rounded-lg text-sm font-medium flex items-center justify-between animate-slide-down
            <?= $flash['type'] === 'success' ? 'bg-green-100 text-green-800' : '' ?>
            <?= $flash['type'] === 'error' ? 'bg-red-100 text-red-800' : '' ?>
            <?= $flash['type'] === 'warning' ? 'bg-yellow-100 text-yellow-800' : '' ?>"
            id="flashMessage">
            <span><?= $flash['message'] ?></span>
            <button onclick="this.parentElement.remove()" class="opacity-70 hover:opacity-100"><i class="fas fa-times"></i></button>
        </div>
        <?php endif; ?>

        <!-- Page Content -->
        <div class="p-6">
