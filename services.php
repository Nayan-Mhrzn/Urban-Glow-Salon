<?php
/**
 * Services Page - Urban Glow Salon
 */
$pageTitle = 'Services';
require_once 'includes/config.php';

// Get filter parameters
$category = $_GET['category'] ?? '';
$search = $_GET['search'] ?? '';

// Build query
$where = ['s.is_active = 1'];
$params = [];

if (!empty($category)) {
    $where[] = 's.category = ?';
    $params[] = $category;
}

if (!empty($search)) {
    $where[] = '(s.name LIKE ? OR s.description LIKE ? OR s.category LIKE ?)';
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$whereClause = implode(' AND ', $where);
$stmt = $pdo->prepare("SELECT * FROM services s WHERE $whereClause ORDER BY s.category, s.name");
$stmt->execute($params);
$services = $stmt->fetchAll();

// Get categories for sidebar
$categories = ['Hair', 'Skin', 'Body', 'Makeup', 'Nails'];
$categoryColors = [
    'Hair' => 'bg-primary',
    'Skin' => 'bg-orange-500',
    'Body' => 'bg-green-500',
    'Makeup' => 'bg-pink-500',
    'Nails' => 'bg-purple-500'
];

require_once 'includes/header.php';
?>

<!-- Services Page -->
<div class="max-w-7xl mx-auto px-6 py-8 flex flex-col lg:flex-row gap-8">
    
    <!-- Sidebar -->
    <aside class="w-full lg:w-72 flex-shrink-0">
        <div class="bg-white rounded-[32px] shadow-[0_10px_40px_-10px_rgba(0,0,0,0.06)] border-none p-8 lg:sticky lg:top-24">
            <h3 class="text-[22px] font-extrabold text-[#1a2b4c] mb-6 tracking-tight">Categories</h3>
            <ul class="space-y-2 mb-2">
                <li>
                    <a href="services.php" class="flex items-center justify-between w-full px-5 py-4 text-[15px] font-[600] rounded-[16px] transition-all <?= empty($category) ? 'bg-[#f4f8ff] text-[#2563eb]' : 'text-[#4a5568] hover:bg-gray-50 hover:text-gray-900' ?>">
                        All Services
                    </a>
                </li>
                <?php foreach ($categories as $cat): ?>
                <li>
                    <a href="services.php?category=<?= urlencode($cat) ?>" class="flex items-center justify-between w-full px-5 py-4 text-[15px] font-[600] rounded-[16px] transition-all <?= $category === $cat ? 'bg-[#f4f8ff] text-[#2563eb]' : 'text-[#4a5568] hover:bg-gray-50 hover:text-gray-900' ?>">
                        <?= $cat ?>
                    </a>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </aside>

    <!-- Content Area -->
    <div class="flex-1 min-w-0">
        <!-- Header -->
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-2xl font-bold text-gray-900">Explore Our Services</h1>
            <p class="text-sm text-primary font-medium"><?= count($services) ?> services found</p>
        </div>

        <?php if (count($services) > 0): ?>
        <!-- Services Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-6">
            <?php foreach ($services as $service): 
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
                    <p class="text-sm text-gray-500 mb-3 line-clamp-2"><?= sanitize($service['description']) ?></p>
                    <div class="flex items-center gap-4 text-xs text-gray-500 mb-4">
                        <span class="flex items-center gap-1"><i class="far fa-clock"></i> <?= $service['duration_mins'] ?> mins</span>
                        <span class="flex items-center gap-1"><i class="fas fa-users"></i> <?= $service['gender'] ?></span>
                    </div>
                    <a href="book-appointment.php?service=<?= $service['id'] ?>" class="w-full inline-flex items-center justify-center gap-2 bg-primary hover:bg-primary-dark text-white text-sm font-semibold py-2.5 rounded-full transition-all hover:-translate-y-0.5">
                        <i class="fas fa-calendar-alt"></i> Book Now
                    </a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <!-- Empty State -->
        <div class="text-center py-20">
            <i class="fas fa-spa text-6xl text-gray-300 mb-4"></i>
            <h3 class="text-xl font-semibold text-gray-700 mb-2">No services found</h3>
            <p class="text-gray-500 mb-6">Try adjusting your search or category filter.</p>
            <a href="services.php" class="bg-primary text-white px-6 py-2.5 rounded-full text-sm font-semibold hover:bg-primary-dark transition-all">View All Services</a>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
