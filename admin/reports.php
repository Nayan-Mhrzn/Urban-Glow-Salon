<?php
/**
 * Admin - Reports & Analytics
 */
$pageTitle = 'Reports & Analytics';
require_once dirname(__DIR__) . '/includes/config.php';

// Date range
$range = $_GET['range'] ?? 'month';
$customFrom = $_GET['from'] ?? '';
$customTo = $_GET['to'] ?? '';

switch ($range) {
    case 'today':
        $dateFrom = date('Y-m-d');
        $dateTo = date('Y-m-d');
        break;
    case 'week':
        $dateFrom = date('Y-m-d', strtotime('-7 days'));
        $dateTo = date('Y-m-d');
        break;
    case 'year':
        $dateFrom = date('Y-01-01');
        $dateTo = date('Y-12-31');
        break;
    case 'custom':
        $dateFrom = $customFrom ?: date('Y-m-01');
        $dateTo = $customTo ?: date('Y-m-d');
        break;
    default: // month
        $dateFrom = date('Y-m-01');
        $dateTo = date('Y-m-d');
}

// Revenue stats
$bookingRevenue = $pdo->prepare("SELECT COALESCE(SUM(s.price), 0) FROM bookings b JOIN services s ON b.service_id = s.id WHERE b.booking_date BETWEEN ? AND ? AND b.status != 'Cancelled'");
$bookingRevenue->execute([$dateFrom, $dateTo]);
$bookingRev = (float)$bookingRevenue->fetchColumn();

$orderRevenue = $pdo->prepare("SELECT COALESCE(SUM(total_amount), 0) FROM orders WHERE DATE(created_at) BETWEEN ? AND ? AND status != 'Cancelled'");
$orderRevenue->execute([$dateFrom, $dateTo]);
$orderRev = (float)$orderRevenue->fetchColumn();

$totalRev = $bookingRev + $orderRev;

// Booking count in range
$bookingCount = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE booking_date BETWEEN ? AND ?");
$bookingCount->execute([$dateFrom, $dateTo]);
$totalBookingsRange = (int)$bookingCount->fetchColumn();

$orderCount = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE DATE(created_at) BETWEEN ? AND ?");
$orderCount->execute([$dateFrom, $dateTo]);
$totalOrdersRange = (int)$orderCount->fetchColumn();

// New customers in range
$newCustomers = $pdo->prepare("SELECT COUNT(*) FROM users WHERE role = 'CUSTOMER' AND DATE(created_at) BETWEEN ? AND ?");
$newCustomers->execute([$dateFrom, $dateTo]);
$newCustCount = (int)$newCustomers->fetchColumn();

// Top 5 services
$topServices = $pdo->prepare("SELECT s.name, COUNT(b.id) as total_bookings, SUM(s.price) as revenue FROM bookings b JOIN services s ON b.service_id = s.id WHERE b.booking_date BETWEEN ? AND ? AND b.status != 'Cancelled' GROUP BY s.id ORDER BY total_bookings DESC LIMIT 5");
$topServices->execute([$dateFrom, $dateTo]);
$popularServices = $topServices->fetchAll();

// Top 5 products
$topProducts = $pdo->prepare("SELECT p.name, SUM(oi.quantity) as total_sold, SUM(oi.price_at_purchase * oi.quantity) as revenue FROM order_items oi JOIN products p ON oi.product_id = p.id JOIN orders o ON oi.order_id = o.id WHERE DATE(o.created_at) BETWEEN ? AND ? AND o.status != 'Cancelled' GROUP BY p.id ORDER BY total_sold DESC LIMIT 5");
$topProducts->execute([$dateFrom, $dateTo]);
$bestProducts = $topProducts->fetchAll();

// Monthly revenue for chart (last 6 months)
$monthlyRevenue = [];
$baseDate = date('Y-m-01');
for ($i = 5; $i >= 0; $i--) {
    $month = date('Y-m', strtotime("$baseDate -$i months"));
    $label = date('M', strtotime("$baseDate -$i months"));
    $stmt = $pdo->prepare("
        SELECT 
            (SELECT COALESCE(SUM(total_amount), 0) FROM orders WHERE DATE_FORMAT(created_at, '%Y-%m') = ? AND status != 'Cancelled') +
            (SELECT COALESCE(SUM(s.price), 0) FROM bookings b JOIN services s ON b.service_id = s.id WHERE DATE_FORMAT(b.created_at, '%Y-%m') = ? AND b.status != 'Cancelled')
    ");
    $stmt->execute([$month, $month]);
    $monthlyRevenue[] = ['label' => $label, 'value' => (float)$stmt->fetchColumn()];
}

// Customer growth (last 6 months)
$customerGrowth = [];
$baseDate = date('Y-m-01');
for ($i = 5; $i >= 0; $i--) {
    $month = date('Y-m', strtotime("$baseDate -$i months"));
    $label = date('M', strtotime("$baseDate -$i months"));
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE role = 'CUSTOMER' AND DATE_FORMAT(created_at, '%Y-%m') = ?");
    $stmt->execute([$month]);
    $customerGrowth[] = ['label' => $label, 'value' => (int)$stmt->fetchColumn()];
}

require_once 'header.php';
?>

<!-- Date Filters -->
<div class="flex flex-wrap items-center gap-3 mb-8">
    <?php foreach (['today' => 'Today', 'week' => 'This Week', 'month' => 'This Month', 'year' => 'This Year'] as $key => $label): ?>
        <a href="reports.php?range=<?= $key ?>" class="px-4 py-2 rounded-full text-sm font-semibold transition-all <?= $range === $key ? 'bg-primary text-white shadow-lg shadow-primary/20' : 'bg-white border border-gray-200 text-gray-700 hover:bg-gray-50' ?>"><?= $label ?></a>
    <?php endforeach; ?>
    <form class="flex items-center gap-2 ml-auto" method="GET">
        <input type="hidden" name="range" value="custom">
        <input type="date" name="from" value="<?= $customFrom ?>" class="px-3 py-2 border border-gray-200 rounded-xl text-sm focus:border-primary outline-none">
        <span class="text-xs text-gray-400">to</span>
        <input type="date" name="to" value="<?= $customTo ?>" class="px-3 py-2 border border-gray-200 rounded-xl text-sm focus:border-primary outline-none">
        <button type="submit" class="px-4 py-2 bg-primary text-white rounded-xl text-sm font-semibold hover:bg-primary-dark transition-all">Filter</button>
    </form>
</div>

<!-- Summary Cards -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5 mb-8">
    <div class="bg-white rounded-2xl p-6 border border-gray-100 shadow-sm">
        <div class="w-10 h-10 rounded-xl bg-emerald-50 flex items-center justify-center mb-3"><i class="fas fa-rupee-sign text-emerald-500"></i></div>
        <p class="text-2xl font-extrabold text-gray-900"><?= formatPrice($totalRev) ?></p>
        <p class="text-sm text-gray-500 mt-1">Total Revenue</p>
    </div>
    <div class="bg-white rounded-2xl p-6 border border-gray-100 shadow-sm">
        <div class="w-10 h-10 rounded-xl bg-purple-50 flex items-center justify-center mb-3"><i class="fas fa-calendar-check text-purple-500"></i></div>
        <p class="text-2xl font-extrabold text-gray-900"><?= $totalBookingsRange ?></p>
        <p class="text-sm text-gray-500 mt-1">Bookings</p>
    </div>
    <div class="bg-white rounded-2xl p-6 border border-gray-100 shadow-sm">
        <div class="w-10 h-10 rounded-xl bg-blue-50 flex items-center justify-center mb-3"><i class="fas fa-shopping-bag text-blue-500"></i></div>
        <p class="text-2xl font-extrabold text-gray-900"><?= $totalOrdersRange ?></p>
        <p class="text-sm text-gray-500 mt-1">Orders</p>
    </div>
    <div class="bg-white rounded-2xl p-6 border border-gray-100 shadow-sm">
        <div class="w-10 h-10 rounded-xl bg-pink-50 flex items-center justify-center mb-3"><i class="fas fa-user-plus text-pink-500"></i></div>
        <p class="text-2xl font-extrabold text-gray-900"><?= $newCustCount ?></p>
        <p class="text-sm text-gray-500 mt-1">New Customers</p>
    </div>
</div>

<!-- Revenue Breakdown -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
        <h3 class="font-bold text-gray-900 mb-4">Revenue Breakdown</h3>
        <canvas id="revenueBreakdown" height="200"></canvas>
        <div class="mt-4 space-y-2">
            <div class="flex items-center justify-between text-sm">
                <span class="flex items-center gap-2"><span class="w-3 h-3 rounded-full bg-primary inline-block"></span>Services</span>
                <span class="font-bold"><?= formatPrice($bookingRev) ?></span>
            </div>
            <div class="flex items-center justify-between text-sm">
                <span class="flex items-center gap-2"><span class="w-3 h-3 rounded-full bg-pink-500 inline-block"></span>Products</span>
                <span class="font-bold"><?= formatPrice($orderRev) ?></span>
            </div>
        </div>
    </div>
    <div class="lg:col-span-2 bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
        <h3 class="font-bold text-gray-900 mb-4">Monthly Revenue Trend</h3>
        <div style="position:relative; height:220px;">
            <canvas id="monthlyRevenueChart"></canvas>
        </div>
    </div>
</div>

<!-- Top Lists -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
    <!-- Top Services -->
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm">
        <div class="p-6 border-b border-gray-100">
            <h3 class="font-bold text-gray-900"><i class="fas fa-trophy text-amber-400 mr-2"></i>Top Services</h3>
        </div>
        <div class="divide-y divide-gray-50">
            <?php if (!empty($popularServices)): ?>
                <?php foreach ($popularServices as $i => $svc): ?>
                <div class="px-6 py-4 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <span class="w-7 h-7 rounded-lg bg-primary-bg text-primary text-xs font-bold flex items-center justify-center">#<?= $i + 1 ?></span>
                        <span class="text-sm font-medium text-gray-900"><?= sanitize($svc['name']) ?></span>
                    </div>
                    <div class="text-right">
                        <p class="text-sm font-bold text-primary"><?= formatPrice($svc['revenue']) ?></p>
                        <p class="text-xs text-gray-500"><?= $svc['total_bookings'] ?> bookings</p>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="p-8 text-center text-sm text-gray-500">No data for this period</div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Top Products -->
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm">
        <div class="p-6 border-b border-gray-100">
            <h3 class="font-bold text-gray-900"><i class="fas fa-fire text-orange-400 mr-2"></i>Best Selling Products</h3>
        </div>
        <div class="divide-y divide-gray-50">
            <?php if (!empty($bestProducts)): ?>
                <?php foreach ($bestProducts as $i => $prod): ?>
                <div class="px-6 py-4 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <span class="w-7 h-7 rounded-lg bg-orange-50 text-orange-500 text-xs font-bold flex items-center justify-center">#<?= $i + 1 ?></span>
                        <span class="text-sm font-medium text-gray-900"><?= sanitize($prod['name']) ?></span>
                    </div>
                    <div class="text-right">
                        <p class="text-sm font-bold text-primary"><?= formatPrice($prod['revenue']) ?></p>
                        <p class="text-xs text-gray-500"><?= $prod['total_sold'] ?> sold</p>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="p-8 text-center text-sm text-gray-500">No data for this period</div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Customer Growth -->
<div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 mb-8">
    <h3 class="font-bold text-gray-900 mb-4">Customer Growth (Last 6 Months)</h3>
    <div style="position:relative; height:180px;">
        <canvas id="customerGrowthChart"></canvas>
    </div>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
<script>
const chartFont = { family: 'Inter', weight: '600', size: 12 };

document.addEventListener('DOMContentLoaded', function() {
    // Revenue Breakdown Doughnut
    new Chart(document.getElementById('revenueBreakdown'), {
        type: 'doughnut',
        data: {
            labels: ['Services', 'Products'],
            datasets: [{ data: [<?= $bookingRev ?>, <?= $orderRev ?>], backgroundColor: ['#4339F2', '#ec4899'], borderWidth: 0, borderRadius: 4 }]
        },
        options: { responsive: true, plugins: { legend: { display: false } }, cutout: '70%' }
    });

    // Monthly Revenue
    new Chart(document.getElementById('monthlyRevenueChart'), {
        type: 'bar',
        data: {
            labels: <?= json_encode(array_column($monthlyRevenue, 'label')) ?>,
            datasets: [{ 
                label: 'Revenue (Rs.)',
                data: <?= json_encode(array_column($monthlyRevenue, 'value')) ?>, 
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
            responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } },
            scales: { 
                x: { 
                    display: true, grid: { display: false }, 
                    ticks: { display: true, font: chartFont, color: '#6b7280' } 
                }, 
                y: { 
                    display: true, grid: { color: '#f3f4f6' }, 
                    ticks: { 
                        display: true, font: { size: 11 }, color: '#9ca3af', precision: 0,
                        callback: function(value) {
                            if (value >= 1000) return 'Rs. ' + (value / 1000).toFixed(0) + 'k';
                            return 'Rs. ' + value;
                        }
                    }, 
                    beginAtZero: true 
                } 
            }
        }
    });

    // Customer Growth
    new Chart(document.getElementById('customerGrowthChart'), {
        type: 'line',
        data: {
            labels: <?= json_encode(array_column($customerGrowth, 'label')) ?>,
            datasets: [{ 
                label: 'Customers',
                data: <?= json_encode(array_column($customerGrowth, 'value')) ?>, 
                borderColor: '#ec4899', 
                backgroundColor: 'rgba(236, 72, 153, 0.12)', 
                borderWidth: 3, 
                fill: true, 
                tension: 0.4, 
                pointBackgroundColor: '#ec4899', 
                pointBorderColor: '#fff', 
                pointBorderWidth: 2, 
                pointRadius: 6,
                pointHoverRadius: 8
            }]
        },
        options: {
            responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } },
            scales: { 
                x: { 
                    display: true, grid: { display: false }, 
                    ticks: { display: true, font: chartFont, color: '#6b7280' } 
                }, 
                y: { 
                    display: true, grid: { color: '#f3f4f6' }, 
                    ticks: { display: true, font: { size: 11 }, color: '#9ca3af', precision: 0, stepSize: 1 }, 
                    beginAtZero: true 
                } 
            }
        }
    });
});
</script>

<?php require_once 'footer.php'; ?>
