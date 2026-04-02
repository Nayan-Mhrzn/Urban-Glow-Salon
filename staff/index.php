<?php
/**
 * Staff Dashboard
 */
$pageTitle = 'Dashboard';
require_once dirname(__DIR__) . '/config/config.php';
requireStaff();

setupDependencies();

function setupDependencies() {
    global $pdo;

    $staffId = $_SESSION['user_id'];
    $today = date('Y-m-d');
    $startOfMonth = date('Y-m-01');

    // Stats
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE staff_id = ? AND booking_date = ? AND status != 'Cancelled'");
    $stmt->execute([$staffId, $today]);
    $todayCount = $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE staff_id = ? AND status = 'Pending' AND booking_date >= ?");
    $stmt->execute([$staffId, $today]);
    $pendingCount = $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE staff_id = ? AND status = 'Completed' AND booking_date >= ?");
    $stmt->execute([$staffId, $startOfMonth]);
    $completedCount = $stmt->fetchColumn();

    // Today's schedule
    $stmt = $pdo->prepare("SELECT b.*, u.full_name as customer_name, s.name as service_name, s.duration_mins 
                           FROM bookings b 
                           JOIN users u ON b.user_id = u.id 
                           JOIN services s ON b.service_id = s.id 
                           WHERE b.staff_id = ? AND b.booking_date = ? 
                           ORDER BY b.booking_time ASC");
    $stmt->execute([$staffId, $today]);
    $todaySchedule = $stmt->fetchAll();

    return [$todayCount, $pendingCount, $completedCount, $todaySchedule];
}

list($todayCount, $pendingCount, $completedCount, $todaySchedule) = setupDependencies();

require_once 'header.php';
?>

<!-- Stats Grid -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
    <!-- Today's Count -->
    <div class="bg-white rounded-2xl border border-gray-100 p-6 shadow-sm hover:shadow-md transition-shadow relative overflow-hidden group">
        <div class="absolute top-0 right-0 w-32 h-32 bg-primary/5 rounded-bl-full -z-10 group-hover:bg-primary/10 transition-colors"></div>
        <div class="flex items-start justify-between">
            <div>
                <p class="text-sm font-semibold text-gray-500 mb-1">Today's Appointments</p>
                <h3 class="text-3xl font-bold text-gray-900"><?= number_format($todayCount) ?></h3>
            </div>
            <div class="w-12 h-12 rounded-xl bg-primary/10 text-primary flex items-center justify-center text-xl">
                <i class="fas fa-calendar-day"></i>
            </div>
        </div>
    </div>

    <!-- Pending Count -->
    <div class="bg-white rounded-2xl border border-gray-100 p-6 shadow-sm hover:shadow-md transition-shadow relative overflow-hidden group">
        <div class="absolute top-0 right-0 w-32 h-32 bg-yellow-500/5 rounded-bl-full -z-10 group-hover:bg-yellow-500/10 transition-colors"></div>
        <div class="flex items-start justify-between">
            <div>
                <p class="text-sm font-semibold text-gray-500 mb-1">Upcoming Pending</p>
                <h3 class="text-3xl font-bold text-gray-900"><?= number_format($pendingCount) ?></h3>
            </div>
            <div class="w-12 h-12 rounded-xl bg-yellow-50 text-yellow-500 flex items-center justify-center text-xl">
                <i class="fas fa-clock"></i>
            </div>
        </div>
    </div>

    <!-- Completed Month -->
    <div class="bg-white rounded-2xl border border-gray-100 p-6 shadow-sm hover:shadow-md transition-shadow relative overflow-hidden group">
        <div class="absolute top-0 right-0 w-32 h-32 bg-green-500/5 rounded-bl-full -z-10 group-hover:bg-green-500/10 transition-colors"></div>
        <div class="flex items-start justify-between">
            <div>
                <p class="text-sm font-semibold text-gray-500 mb-1">Completed This Month</p>
                <h3 class="text-3xl font-bold text-gray-900"><?= number_format($completedCount) ?></h3>
            </div>
            <div class="w-12 h-12 rounded-xl bg-green-50 text-green-500 flex items-center justify-center text-xl">
                <i class="fas fa-check-circle"></i>
            </div>
        </div>
    </div>
</div>

<!-- Today's Schedule -->
<div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden mb-8">
    <div class="p-6 border-b border-gray-100 flex items-center justify-between">
        <h3 class="text-lg font-bold text-gray-900 flex items-center"><i class="fas fa-list-ul text-primary mr-2"></i> Today's Schedule</h3>
        <a href="appointments.php" class="text-sm font-semibold text-primary hover:underline">View All</a>
    </div>
    
    <?php if (empty($todaySchedule)): ?>
        <div class="p-10 text-center">
            <div class="w-16 h-16 bg-gray-50 rounded-full flex items-center justify-center mx-auto mb-4 text-gray-400 text-2xl">
                <i class="fas fa-mug-hot"></i>
            </div>
            <h4 class="text-gray-900 font-bold mb-1">No appointments today</h4>
            <p class="text-gray-500 text-sm">Enjoy your free time or check upcoming bookings.</p>
        </div>
    <?php else: ?>
        <ul class="divide-y divide-gray-100">
            <?php foreach ($todaySchedule as $booking): ?>
                <li class="p-6 hover:bg-gray-50 transition-colors flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div class="flex items-start gap-4">
                        <div class="w-12 h-12 rounded-xl bg-primary/10 flex flex-col items-center justify-center flex-shrink-0 text-primary">
                            <span class="text-xs font-bold leading-none"><?= date('h:i', strtotime($booking['booking_time'])) ?></span>
                            <span class="text-[10px] font-semibold uppercase mt-0.5"><?= date('A', strtotime($booking['booking_time'])) ?></span>
                        </div>
                        <div>
                            <h4 class="font-bold text-gray-900 text-base mb-0.5"><?= sanitize($booking['customer_name']) ?></h4>
                            <p class="text-sm text-gray-500 flex items-center gap-1.5">
                                <i class="fas fa-spa text-xs text-gray-400"></i> <?= sanitize($booking['service_name']) ?>
                                <span class="text-gray-300">•</span>
                                <i class="fas fa-stopwatch text-xs text-gray-400"></i> <?= $booking['duration_mins'] ?> mins
                            </p>
                        </div>
                    </div>
                    
                    <div class="flex items-center gap-3 self-start md:self-auto">
                        <span class="text-xs font-semibold px-3 py-1.5 rounded-full inline-block
                            <?= $booking['status'] === 'Pending' ? 'bg-yellow-100 text-yellow-700' : '' ?>
                            <?= $booking['status'] === 'Confirmed' ? 'bg-blue-100 text-blue-700' : '' ?>
                            <?= $booking['status'] === 'Completed' ? 'bg-green-100 text-green-700' : '' ?>
                            <?= $booking['status'] === 'Cancelled' ? 'bg-red-100 text-red-700' : '' ?>
                            <?= $booking['status'] === 'No Show' ? 'bg-orange-100 text-orange-700' : '' ?>">
                            <?= $booking['status'] ?>
                        </span>
                        
                        <?php if (in_array($booking['status'], ['Pending', 'Confirmed'])): ?>
                            <form action="appointments.php" method="POST" class="inline">
                                <input type="hidden" name="action" value="update_status">
                                <input type="hidden" name="booking_id" value="<?= $booking['id'] ?>">
                                <input type="hidden" name="new_status" value="Completed">
                                <button type="submit" class="text-sm bg-green-50 text-green-600 hover:bg-green-100 hover:text-green-700 px-3 py-1.5 rounded-lg font-semibold transition-colors" title="Mark Completed" onclick="return confirm('Mark this appointment as completed?')">
                                    <i class="fas fa-check mr-1"></i> Done
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</div>

<?php require_once 'footer.php'; ?>

