<?php
/**
 * Admin - Manage Bookings (Enhanced with staff assignment & filters)
 */
$pageTitle = 'Manage Bookings';
require_once dirname(__DIR__) . '/includes/config.php';

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'update_status') {
        $bookingId = (int)$_POST['booking_id'];
        $newStatus = $_POST['new_status'];
        $allowed = ['Pending', 'Confirmed', 'Completed', 'Cancelled', 'No Show'];
        if (in_array($newStatus, $allowed)) {
            // Map status to outcome for scoring engine
            $outcomeMap = ['Completed' => 'completed', 'Cancelled' => 'cancelled', 'No Show' => 'no_show'];
            $outcome = $outcomeMap[$newStatus] ?? null;
            $stmt = $pdo->prepare("UPDATE bookings SET status = ?, outcome = ? WHERE id = ?");
            $stmt->execute([$newStatus, $outcome, $bookingId]);
            setFlash('success', 'Booking #' . $bookingId . ' updated to ' . $newStatus);
        }
    } elseif ($action === 'assign_staff') {
        $bookingId = (int)$_POST['booking_id'];
        $staffId = !empty($_POST['staff_id']) ? (int)$_POST['staff_id'] : null;
        $stmt = $pdo->prepare("UPDATE bookings SET staff_id = ? WHERE id = ?");
        $stmt->execute([$staffId, $bookingId]);
        setFlash('success', 'Staff assigned to booking #' . $bookingId);
    }
    redirect(SITE_URL . '/admin/bookings.php?' . http_build_query($_GET));
}

// Filters
$statusFilter = $_GET['status'] ?? '';
$dateFilter = $_GET['date'] ?? '';
$searchFilter = $_GET['search'] ?? '';
$where = '1=1';
$params = [];
if ($statusFilter) { $where .= ' AND b.status = ?'; $params[] = $statusFilter; }
if ($dateFilter) { $where .= ' AND b.booking_date = ?'; $params[] = $dateFilter; }
if ($searchFilter) { $where .= ' AND (u.full_name LIKE ? OR s.name LIKE ?)'; $params[] = "%$searchFilter%"; $params[] = "%$searchFilter%"; }

$stmt = $pdo->prepare("SELECT b.*, u.username, u.full_name, u.phone, s.name as service_name, s.price as service_price, s.duration_mins, st.full_name as staff_name FROM bookings b JOIN users u ON b.user_id = u.id JOIN services s ON b.service_id = s.id LEFT JOIN users st ON b.staff_id = st.id WHERE $where ORDER BY b.booking_date DESC, b.booking_time ASC");
$stmt->execute($params);
$bookings = $stmt->fetchAll();

// Staff list for assignment
$staffList = $pdo->query("SELECT id, full_name FROM users WHERE role = 'STAFF' ORDER BY full_name")->fetchAll();

require_once 'header.php';
?>

<!-- Filters -->
<div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 mb-6">
    <div class="flex flex-col lg:flex-row gap-4 items-start lg:items-center justify-between">
        <!-- Status Tabs -->
        <div class="flex flex-wrap items-center gap-2">
            <a href="bookings.php" class="px-4 py-2 rounded-full text-sm font-semibold transition-all <?= !$statusFilter ? 'bg-primary text-white shadow-lg shadow-primary/20' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' ?>">All</a>
            <?php foreach (['Pending', 'Confirmed', 'Completed', 'Cancelled', 'No Show'] as $s): ?>
                <a href="bookings.php?status=<?= urlencode($s) ?>" class="px-4 py-2 rounded-full text-sm font-semibold transition-all <?= $statusFilter === $s ? 'bg-primary text-white shadow-lg shadow-primary/20' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' ?>"><?= $s ?></a>
            <?php endforeach; ?>
        </div>
        <!-- Search & Date -->
        <form class="flex items-center gap-2" method="GET">
            <?php if ($statusFilter): ?><input type="hidden" name="status" value="<?= $statusFilter ?>"><?php endif; ?>
            <input type="text" name="search" value="<?= sanitize($searchFilter) ?>" placeholder="Search customer or service..." class="px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:border-primary outline-none w-52">
            <input type="date" name="date" value="<?= sanitize($dateFilter) ?>" class="px-3 py-2.5 border border-gray-200 rounded-xl text-sm focus:border-primary outline-none">
            <button type="submit" class="px-4 py-2.5 bg-primary text-white rounded-xl text-sm font-semibold hover:bg-primary-dark transition-all"><i class="fas fa-search"></i></button>
        </form>
    </div>
</div>

<!-- Bookings Table -->
<div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm text-left">
            <thead class="bg-gray-50 text-gray-600 uppercase text-xs">
                <tr>
                    <th class="px-5 py-3">ID</th>
                    <th class="px-5 py-3">Customer</th>
                    <th class="px-5 py-3">Service</th>
                    <th class="px-5 py-3">Date & Time</th>
                    <th class="px-5 py-3">Price</th>
                    <th class="px-5 py-3">Status</th>
                    <th class="px-5 py-3">Staff</th>
                    <th class="px-5 py-3">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <?php foreach ($bookings as $b): ?>
                <tr class="hover:bg-gray-50 transition-colors">
                    <td class="px-5 py-3 font-medium text-gray-500">#<?= $b['id'] ?></td>
                    <td class="px-5 py-3">
                        <p class="font-semibold text-gray-900"><?= sanitize($b['full_name']) ?></p>
                        <p class="text-xs text-gray-500"><?= sanitize($b['phone'] ?? $b['username']) ?></p>
                    </td>
                    <td class="px-5 py-3"><?= sanitize($b['service_name']) ?></td>
                    <td class="px-5 py-3">
                        <p class="font-medium"><?= date('M d, Y', strtotime($b['booking_date'])) ?></p>
                        <p class="text-xs text-gray-500"><?= date('h:i A', strtotime($b['booking_time'])) ?> • <?= $b['duration_mins'] ?> mins</p>
                    </td>
                    <td class="px-5 py-3 font-semibold text-primary"><?= formatPrice($b['service_price']) ?></td>
                    <td class="px-5 py-3">
                        <span class="text-xs font-semibold px-2.5 py-1 rounded-full
                            <?= $b['status'] === 'Pending' ? 'bg-yellow-100 text-yellow-700' : '' ?>
                            <?= $b['status'] === 'Confirmed' ? 'bg-blue-100 text-blue-700' : '' ?>
                            <?= $b['status'] === 'Completed' ? 'bg-green-100 text-green-700' : '' ?>
                            <?= $b['status'] === 'Cancelled' ? 'bg-red-100 text-red-700' : '' ?>
                            <?= $b['status'] === 'No Show' ? 'bg-orange-100 text-orange-700' : '' ?>">
                            <?= $b['status'] ?>
                        </span>
                    </td>
                    <td class="px-5 py-3">
                        <form method="POST" class="flex items-center gap-1">
                            <input type="hidden" name="action" value="assign_staff">
                            <input type="hidden" name="booking_id" value="<?= $b['id'] ?>">
                            <select name="staff_id" onchange="this.form.submit()" class="text-xs border border-gray-200 rounded-lg px-2 py-1.5 bg-white focus:border-primary outline-none min-w-[100px]">
                                <option value="">Unassigned</option>
                                <?php foreach ($staffList as $st): ?>
                                    <option value="<?= $st['id'] ?>" <?= $b['staff_id'] == $st['id'] ? 'selected' : '' ?>><?= sanitize($st['full_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </form>
                    </td>
                    <td class="px-5 py-3">
                        <form method="POST" class="flex items-center gap-1">
                            <input type="hidden" name="action" value="update_status">
                            <input type="hidden" name="booking_id" value="<?= $b['id'] ?>">
                            <select name="new_status" class="text-xs border border-gray-200 rounded-lg px-2 py-1.5 bg-white focus:border-primary outline-none">
                                <?php foreach (['Pending', 'Confirmed', 'Completed', 'Cancelled', 'No Show'] as $s): ?>
                                    <option value="<?= $s ?>" <?= $b['status'] === $s ? 'selected' : '' ?>><?= $s ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit" class="text-xs bg-primary text-white px-3 py-1.5 rounded-lg hover:bg-primary-dark transition-all">Update</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($bookings)): ?>
                <tr><td colspan="8" class="px-5 py-10 text-center text-gray-500">No bookings found</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once 'footer.php'; ?>
