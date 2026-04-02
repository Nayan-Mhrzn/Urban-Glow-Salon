<?php
/**
 * Admin Dashboard - Urban Glow Salon
 */
$pageTitle = 'Dashboard';
require_once dirname(__DIR__) . '/includes/config.php';
require_once 'header.php';

// Stats
$totalUsers = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'CUSTOMER'")->fetchColumn();
$totalBookings = $pdo->query("SELECT COUNT(*) FROM bookings")->fetchColumn();
$totalOrders = $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
$totalRevenue = $pdo->query("
    SELECT 
        (SELECT COALESCE(SUM(total_amount), 0) FROM orders WHERE status != 'Cancelled') +
        (SELECT COALESCE(SUM(s.price), 0) FROM bookings b JOIN services s ON b.service_id = s.id WHERE b.status != 'Cancelled')
")->fetchColumn();
$totalServices = $pdo->query("SELECT COUNT(*) FROM services")->fetchColumn();
$totalProducts = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
$pendingBookings = $pdo->query("SELECT COUNT(*) FROM bookings WHERE status = 'Pending'")->fetchColumn();
$totalReviews = $pdo->query("SELECT COUNT(*) FROM reviews")->fetchColumn();

// Today's bookings
$today = date('Y-m-d');
$todayBookings = $pdo->prepare("SELECT b.*, u.full_name, s.name as service_name, s.duration_mins FROM bookings b JOIN users u ON b.user_id = u.id JOIN services s ON b.service_id = s.id WHERE b.booking_date = ? ORDER BY b.booking_time ASC");
$todayBookings->execute([$today]);
$todayAppointments = $todayBookings->fetchAll();

// Revenue last 7 days
$revenueDays = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $label = date('D', strtotime("-$i days"));
    $stmt = $pdo->prepare("
        SELECT 
            (SELECT COALESCE(SUM(total_amount), 0) FROM orders WHERE DATE(created_at) = ? AND status != 'Cancelled') +
            (SELECT COALESCE(SUM(s.price), 0) FROM bookings b JOIN services s ON b.service_id = s.id WHERE DATE(b.created_at) = ? AND b.status != 'Cancelled')
    ");
    $stmt->execute([$date, $date]);
    $revenueDays[] = ['label' => $label, 'value' => (float)$stmt->fetchColumn()];
}

// Bookings last 7 days
$bookingDays = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $label = date('D', strtotime("-$i days"));
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE DATE(created_at) = ?");
    $stmt->execute([$date]);
    $bookingDays[] = ['label' => $label, 'value' => (int)$stmt->fetchColumn()];
}

// Recent bookings
$recentBookings = $pdo->query("SELECT b.*, u.username, u.full_name, s.name as service_name FROM bookings b JOIN users u ON b.user_id = u.id JOIN services s ON b.service_id = s.id ORDER BY b.created_at DESC LIMIT 5")->fetchAll();

// Recent orders
$recentOrders = $pdo->query("SELECT o.*, u.username, u.full_name FROM orders o JOIN users u ON o.user_id = u.id ORDER BY o.created_at DESC LIMIT 5")->fetchAll();

// Month comparison
$thisMonth = date('Y-m');
$lastMonth = date('Y-m', strtotime('-1 month'));
$thisMonthRev = $pdo->query("
    SELECT 
        (SELECT COALESCE(SUM(total_amount), 0) FROM orders WHERE DATE_FORMAT(created_at, '%Y-%m') = '$thisMonth' AND status != 'Cancelled') +
        (SELECT COALESCE(SUM(s.price), 0) FROM bookings b JOIN services s ON b.service_id = s.id WHERE DATE_FORMAT(b.created_at, '%Y-%m') = '$thisMonth' AND b.status != 'Cancelled')
")->fetchColumn();

$lastMonthRev = $pdo->query("
    SELECT 
        (SELECT COALESCE(SUM(total_amount), 0) FROM orders WHERE DATE_FORMAT(created_at, '%Y-%m') = '$lastMonth' AND status != 'Cancelled') +
        (SELECT COALESCE(SUM(s.price), 0) FROM bookings b JOIN services s ON b.service_id = s.id WHERE DATE_FORMAT(b.created_at, '%Y-%m') = '$lastMonth' AND b.status != 'Cancelled')
")->fetchColumn();

$revGrowth = $lastMonthRev > 0 ? round((($thisMonthRev - $lastMonthRev) / $lastMonthRev) * 100, 1) : 0;

$thisMonthBookings = $pdo->query("SELECT COUNT(*) FROM bookings WHERE DATE_FORMAT(created_at, '%Y-%m') = '$thisMonth'")->fetchColumn();
$lastMonthBookings = $pdo->query("SELECT COUNT(*) FROM bookings WHERE DATE_FORMAT(created_at, '%Y-%m') = '$lastMonth'")->fetchColumn();
$bookGrowth = $lastMonthBookings > 0 ? round((($thisMonthBookings - $lastMonthBookings) / $lastMonthBookings) * 100, 1) : 0;
?>

<!-- Stats Grid -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5 mb-8">
    <div class="bg-white rounded-2xl p-6 border border-gray-100 shadow-sm hover:shadow-md transition-all">
        <div class="flex items-center justify-between mb-4">
            <div class="w-12 h-12 rounded-2xl bg-blue-50 flex items-center justify-center"><i class="fas fa-users text-lg text-blue-500"></i></div>
            <span class="text-xs font-semibold text-green-600 bg-green-50 px-2.5 py-1 rounded-full"><i class="fas fa-arrow-up mr-0.5"></i>Active</span>
        </div>
        <p class="text-3xl font-extrabold text-gray-900 mb-1"><?= number_format($totalUsers) ?></p>
        <p class="text-sm font-medium text-gray-500">Total Customers</p>
    </div>
    <div class="bg-white rounded-2xl p-6 border border-gray-100 shadow-sm hover:shadow-md transition-all">
        <div class="flex items-center justify-between mb-4">
            <div class="w-12 h-12 rounded-2xl bg-purple-50 flex items-center justify-center"><i class="fas fa-calendar-check text-lg text-purple-500"></i></div>
            <?php if ($bookGrowth != 0): ?>
            <span class="text-xs font-semibold <?= $bookGrowth > 0 ? 'text-green-600 bg-green-50' : 'text-red-600 bg-red-50' ?> px-2.5 py-1 rounded-full">
                <i class="fas fa-arrow-<?= $bookGrowth > 0 ? 'up' : 'down' ?> mr-0.5"></i><?= abs($bookGrowth) ?>%
            </span>
            <?php else: ?>
            <span class="text-xs font-semibold text-orange-600 bg-orange-50 px-2.5 py-1 rounded-full"><?= $pendingBookings ?> Pending</span>
            <?php endif; ?>
        </div>
        <p class="text-3xl font-extrabold text-gray-900 mb-1"><?= number_format($totalBookings) ?></p>
        <p class="text-sm font-medium text-gray-500">Total Bookings</p>
    </div>
    <div class="bg-white rounded-2xl p-6 border border-gray-100 shadow-sm hover:shadow-md transition-all">
        <div class="flex items-center justify-between mb-4">
            <div class="w-12 h-12 rounded-2xl bg-green-50 flex items-center justify-center"><i class="fas fa-shopping-bag text-lg text-green-500"></i></div>
        </div>
        <p class="text-3xl font-extrabold text-gray-900 mb-1"><?= number_format($totalOrders) ?></p>
        <p class="text-sm font-medium text-gray-500">Total Orders</p>
    </div>
    <div class="bg-white rounded-2xl p-6 border border-gray-100 shadow-sm hover:shadow-md transition-all">
        <div class="flex items-center justify-between mb-4">
            <div class="w-12 h-12 rounded-2xl bg-emerald-50 flex items-center justify-center"><i class="fas fa-rupee-sign text-lg text-emerald-500"></i></div>
            <?php if ($revGrowth != 0): ?>
            <span class="text-xs font-semibold <?= $revGrowth > 0 ? 'text-green-600 bg-green-50' : 'text-red-600 bg-red-50' ?> px-2.5 py-1 rounded-full">
                <i class="fas fa-arrow-<?= $revGrowth > 0 ? 'up' : 'down' ?> mr-0.5"></i><?= abs($revGrowth) ?>%
            </span>
            <?php endif; ?>
        </div>
        <p class="text-3xl font-extrabold text-gray-900 mb-1"><?= formatPrice($totalRevenue) ?></p>
        <p class="text-sm font-medium text-gray-500">Total Revenue</p>
    </div>
</div>

<!-- Quick Stats Row -->
<div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-8">
    <div class="bg-gradient-to-br from-primary to-primary-light rounded-2xl p-5 text-white text-center shadow-lg shadow-primary/20">
        <p class="text-2xl font-extrabold"><?= $totalServices ?></p>
        <p class="text-xs font-medium opacity-80 mt-1">Services</p>
    </div>
    <div class="bg-gradient-to-br from-pink-500 to-rose-500 rounded-2xl p-5 text-white text-center shadow-lg shadow-pink-500/20">
        <p class="text-2xl font-extrabold"><?= $totalProducts ?></p>
        <p class="text-xs font-medium opacity-80 mt-1">Products</p>
    </div>
    <div class="bg-gradient-to-br from-amber-500 to-orange-500 rounded-2xl p-5 text-white text-center shadow-lg shadow-amber-500/20">
        <p class="text-2xl font-extrabold"><?= $totalReviews ?></p>
        <p class="text-xs font-medium opacity-80 mt-1">Reviews</p>
    </div>
    <div class="bg-gradient-to-br from-cyan-500 to-teal-500 rounded-2xl p-5 text-white text-center shadow-lg shadow-cyan-500/20">
        <p class="text-2xl font-extrabold"><?= $pendingBookings ?></p>
        <p class="text-xs font-medium opacity-80 mt-1">Pending</p>
    </div>
</div>

<!-- Charts Row -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
        <h3 class="font-bold text-gray-900 mb-4">Revenue (Last 7 Days)</h3>
        <div style="position:relative; height:220px;">
            <canvas id="revenueChart"></canvas>
        </div>
    </div>
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
        <h3 class="font-bold text-gray-900 mb-4">Bookings (Last 7 Days)</h3>
        <div style="position:relative; height:220px;">
            <canvas id="bookingsChart"></canvas>
        </div>
    </div>
</div>

<!-- Today's Appointments -->
<?php if (!empty($todayAppointments)): ?>
<div class="bg-white rounded-2xl border border-gray-100 shadow-sm mb-8">
    <div class="flex items-center justify-between p-6 border-b border-gray-100">
        <h3 class="font-bold text-gray-900"><i class="fas fa-sun text-amber-400 mr-2"></i>Today's Appointments (<?= date('M d') ?>)</h3>
        <span class="text-xs font-semibold text-primary bg-primary-bg px-3 py-1 rounded-full"><?= count($todayAppointments) ?> total</span>
    </div>
    <div class="divide-y divide-gray-50">
        <?php foreach ($todayAppointments as $apt): ?>
        <div class="px-6 py-4 flex items-center justify-between hover:bg-gray-50 transition-colors">
            <div class="flex items-center gap-4">
                <div class="w-10 h-10 rounded-xl bg-primary-bg flex items-center justify-center text-primary font-bold text-sm">
                    <?= date('h:i', strtotime($apt['booking_time'])) ?>
                </div>
                <div>
                    <p class="text-sm font-semibold text-gray-900"><?= sanitize($apt['full_name']) ?></p>
                    <p class="text-xs text-gray-500"><?= sanitize($apt['service_name']) ?> • <?= $apt['duration_mins'] ?> mins</p>
                </div>
            </div>
            <span class="text-xs font-semibold px-2.5 py-1 rounded-full
                <?= $apt['status'] === 'Pending' ? 'bg-yellow-100 text-yellow-700' : '' ?>
                <?= $apt['status'] === 'Confirmed' ? 'bg-blue-100 text-blue-700' : '' ?>
                <?= $apt['status'] === 'Completed' ? 'bg-green-100 text-green-700' : '' ?>
                <?= $apt['status'] === 'Cancelled' ? 'bg-red-100 text-red-700' : '' ?>">
                <?= $apt['status'] ?>
            </span>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<!-- Recent Activity -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <!-- Recent Bookings -->
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm">
        <div class="flex items-center justify-between p-6 border-b border-gray-100">
            <h3 class="font-bold text-gray-900">Recent Bookings</h3>
            <a href="bookings.php" class="text-primary text-sm font-semibold hover:underline">View All →</a>
        </div>
        <div class="divide-y divide-gray-50">
            <?php if (count($recentBookings) > 0): ?>
                <?php foreach ($recentBookings as $b): ?>
                <div class="px-6 py-4 flex items-center justify-between hover:bg-gray-50 transition-colors">
                    <div>
                        <p class="text-sm font-semibold text-gray-900"><?= sanitize($b['full_name']) ?></p>
                        <p class="text-xs text-gray-500"><?= sanitize($b['service_name']) ?> • <?= date('M d', strtotime($b['booking_date'])) ?> at <?= date('h:i A', strtotime($b['booking_time'])) ?></p>
                    </div>
                    <span class="text-xs font-semibold px-2.5 py-1 rounded-full
                        <?= $b['status'] === 'Pending' ? 'bg-yellow-100 text-yellow-700' : '' ?>
                        <?= $b['status'] === 'Confirmed' ? 'bg-blue-100 text-blue-700' : '' ?>
                        <?= $b['status'] === 'Completed' ? 'bg-green-100 text-green-700' : '' ?>
                        <?= $b['status'] === 'Cancelled' ? 'bg-red-100 text-red-700' : '' ?>">
                        <?= $b['status'] ?>
                    </span>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="p-8 text-center text-sm text-gray-500">No bookings yet</div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Recent Orders -->
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm">
        <div class="flex items-center justify-between p-6 border-b border-gray-100">
            <h3 class="font-bold text-gray-900">Recent Orders</h3>
            <a href="orders.php" class="text-primary text-sm font-semibold hover:underline">View All →</a>
        </div>
        <div class="divide-y divide-gray-50">
            <?php if (count($recentOrders) > 0): ?>
                <?php foreach ($recentOrders as $o): ?>
                <div class="px-6 py-4 flex items-center justify-between hover:bg-gray-50 transition-colors">
                    <div>
                        <p class="text-sm font-semibold text-gray-900">Order #<?= $o['id'] ?> — <?= sanitize($o['full_name']) ?></p>
                        <p class="text-xs text-gray-500"><?= formatPrice($o['total_amount']) ?> • <?= date('M d, Y', strtotime($o['created_at'])) ?></p>
                    </div>
                    <span class="text-xs font-semibold px-2.5 py-1 rounded-full
                        <?= $o['status'] === 'Pending' ? 'bg-yellow-100 text-yellow-700' : '' ?>
                        <?= $o['status'] === 'Processing' ? 'bg-blue-100 text-blue-700' : '' ?>
                        <?= $o['status'] === 'Delivered' ? 'bg-green-100 text-green-700' : '' ?>
                        <?= $o['status'] === 'Cancelled' ? 'bg-red-100 text-red-700' : '' ?>">
                        <?= $o['status'] ?>
                    </span>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="p-8 text-center text-sm text-gray-500">No orders yet</div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Revenue Chart
    new Chart(document.getElementById('revenueChart'), {
        type: 'bar',
        data: {
            labels: <?= json_encode(array_column($revenueDays, 'label')) ?>,
            datasets: [{
                label: 'Revenue (Rs.)',
                data: <?= json_encode(array_column($revenueDays, 'value')) ?>,
                backgroundColor: 'rgba(67, 57, 242, 0.75)',
                borderColor: '#4339F2',
                borderWidth: 2,
                borderRadius: 8,
                borderSkipped: false,
                barPercentage: 0.6,
                categoryPercentage: 0.7
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                x: {
                    display: true,
                    grid: { display: false },
                    ticks: {
                        display: true,
                        font: { family: 'Inter', weight: '600', size: 12 },
                        color: '#6b7280'
                    }
                },
                y: {
                    display: true,
                    beginAtZero: true,
                    grid: { color: '#f3f4f6' },
                    ticks: {
                        display: true,
                        font: { family: 'Inter', size: 11 },
                        color: '#9ca3af',
                        precision: 0,
                        callback: function(value) {
                            if (value >= 1000) return 'Rs. ' + (value / 1000).toFixed(0) + 'k';
                            return 'Rs. ' + value;
                        }
                    }
                }
            }
        }
    });

    // Bookings Chart
    new Chart(document.getElementById('bookingsChart'), {
        type: 'line',
        data: {
            labels: <?= json_encode(array_column($bookingDays, 'label')) ?>,
            datasets: [{
                label: 'Bookings',
                data: <?= json_encode(array_column($bookingDays, 'value')) ?>,
                borderColor: '#8b5cf6',
                backgroundColor: 'rgba(139, 92, 246, 0.12)',
                borderWidth: 3,
                fill: true,
                tension: 0.4,
                pointBackgroundColor: '#8b5cf6',
                pointBorderColor: '#fff',
                pointBorderWidth: 2,
                pointRadius: 6,
                pointHoverRadius: 8
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                x: {
                    display: true,
                    grid: { display: false },
                    ticks: {
                        display: true,
                        font: { family: 'Inter', weight: '600', size: 12 },
                        color: '#6b7280'
                    }
                },
                y: {
                    display: true,
                    beginAtZero: true,
                    grid: { color: '#f3f4f6' },
                    ticks: {
                        display: true,
                        font: { family: 'Inter', size: 11 },
                        color: '#9ca3af',
                        precision: 0,
                        stepSize: 1
                    }
                }
            }
        }
    });
});
</script>

<?php require_once 'footer.php'; ?>
