<?php
/**
 * Staff - My Appointments
 */
$pageTitle = 'My Appointments';
require_once dirname(__DIR__) . '/includes/config.php';
requireStaff();

// Handle status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'update_status') {
        $bookingId = (int)$_POST['booking_id'];
        $newStatus = $_POST['new_status'];
        $allowed = ['Pending', 'Confirmed', 'Completed', 'Cancelled', 'No Show'];
        
        if (in_array($newStatus, $allowed)) {
            // Ensure this booking actually belongs to the logged-in staff
            $checkStmt = $pdo->prepare("SELECT id FROM bookings WHERE id = ? AND staff_id = ?");
            $checkStmt->execute([$bookingId, $_SESSION['user_id']]);
            
            if ($checkStmt->fetch()) {
                // Map status to outcome for scoring engine
                $outcomeMap = ['Completed' => 'completed', 'Cancelled' => 'cancelled', 'No Show' => 'no_show'];
                $outcome = $outcomeMap[$newStatus] ?? null;
                
                $stmt = $pdo->prepare("UPDATE bookings SET status = ?, outcome = ? WHERE id = ?");
                $stmt->execute([$newStatus, $outcome, $bookingId]);
                setFlash('success', 'Booking #' . $bookingId . ' updated to ' . $newStatus);
            } else {
                setFlash('error', 'Unauthorized or invalid booking.');
            }
        }
    }
    redirect(SITE_URL . '/staff/appointments.php?' . http_build_query($_GET));
}

// Filters
$statusFilter = $_GET['status'] ?? '';
$dateFilter = $_GET['date'] ?? '';
$searchFilter = $_GET['search'] ?? '';
$where = 'b.staff_id = ?';
$params = [$_SESSION['user_id']];

if ($statusFilter) { $where .= ' AND b.status = ?'; $params[] = $statusFilter; }
if ($dateFilter) { $where .= ' AND b.booking_date = ?'; $params[] = $dateFilter; }
if ($searchFilter) { $where .= ' AND (u.full_name LIKE ? OR s.name LIKE ?)'; $params[] = "%$searchFilter%"; $params[] = "%$searchFilter%"; }

$stmt = $pdo->prepare("SELECT b.*, u.username, u.full_name, u.phone, s.name as service_name, s.price as service_price, s.duration_mins 
                       FROM bookings b 
                       JOIN users u ON b.user_id = u.id 
                       JOIN services s ON b.service_id = s.id 
                       WHERE $where 
                       ORDER BY b.booking_date DESC, b.booking_time ASC");
$stmt->execute($params);
$bookings = $stmt->fetchAll();

require_once 'header.php';
?>

<!-- Filters -->
<div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 mb-6">
    <div class="flex flex-col lg:flex-row gap-4 items-start lg:items-center justify-between">
        <!-- Status Tabs -->
        <div class="flex flex-wrap items-center gap-2">
            <a href="appointments.php" class="px-4 py-2 rounded-full text-sm font-semibold transition-all <?= !$statusFilter ? 'bg-primary text-white shadow-lg shadow-primary/20' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' ?>">All</a>
            <?php foreach (['Pending', 'Confirmed', 'Completed', 'Cancelled', 'No Show'] as $s): ?>
                <a href="appointments.php?status=<?= urlencode($s) ?>" class="px-4 py-2 rounded-full text-sm font-semibold transition-all <?= $statusFilter === $s ? 'bg-primary text-white shadow-lg shadow-primary/20' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' ?>"><?= $s ?></a>
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
<div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden mb-8">
    <div class="overflow-x-auto">
        <table class="w-full text-sm text-left">
            <thead class="bg-gray-50 text-gray-600 uppercase text-xs border-b border-gray-100">
                <tr>
                    <th class="px-6 py-4">ID / Time</th>
                    <th class="px-6 py-4">Customer Info</th>
                    <th class="px-6 py-4">Service Details</th>
                    <th class="px-6 py-4">Current Status</th>
                    <th class="px-6 py-4 text-right">Update Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <?php foreach ($bookings as $b): ?>
                <tr class="hover:bg-gray-50 transition-colors group">
                    <td class="px-6 py-4">
                        <div class="font-bold text-gray-900 mb-1">
                            #<?= $b['id'] ?>
                        </div>
                        <div class="text-xs text-gray-500 font-medium">
                            <?= date('M d, Y', strtotime($b['booking_date'])) ?><br>
                            <?= date('h:i A', strtotime($b['booking_time'])) ?>
                        </div>
                    </td>
                    <td class="px-6 py-4">
                        <div class="font-bold text-gray-900 text-base mb-1">
                            <?= sanitize($b['full_name']) ?>
                        </div>
                        <div class="flex items-center gap-2 text-xs text-gray-500">
                            <i class="fas fa-phone-alt text-gray-400"></i> <?= sanitize($b['phone'] ?? $b['username']) ?>
                        </div>
                    </td>
                    <td class="px-6 py-4">
                        <div class="font-semibold text-primary mb-1 text-[13px]">
                            <?= sanitize($b['service_name']) ?>
                        </div>
                        <div class="text-xs text-gray-500 inline-flex items-center gap-1.5 bg-gray-100 px-2 py-0.5 rounded">
                            <i class="fas fa-stopwatch text-gray-400"></i> <?= $b['duration_mins'] ?> mins
                        </div>
                    </td>
                    <td class="px-6 py-4">
                        <span class="text-xs font-bold px-3 py-1.5 rounded-full whitespace-nowrap inline-flex items-center gap-1.5
                            <?= $b['status'] === 'Pending' ? 'bg-yellow-100 text-yellow-700' : '' ?>
                            <?= $b['status'] === 'Confirmed' ? 'bg-blue-100 text-blue-700' : '' ?>
                            <?= $b['status'] === 'Completed' ? 'bg-green-100 text-green-700' : '' ?>
                            <?= $b['status'] === 'Cancelled' ? 'bg-red-100 text-red-700' : '' ?>
                            <?= $b['status'] === 'No Show' ? 'bg-orange-100 text-orange-700' : '' ?>">
                            <i class="fas <?= $b['status'] === 'Completed' ? 'fa-check' : ($b['status'] === 'Pending' ? 'fa-clock' : 'fa-circle') ?> text-[10px]"></i>
                            <?= $b['status'] ?>
                        </span>
                    </td>
                    <td class="px-6 py-4 text-right">
                        <form method="POST" class="flex items-center justify-end gap-2 opacity-100">
                            <input type="hidden" name="action" value="update_status">
                            <input type="hidden" name="booking_id" value="<?= $b['id'] ?>">
                            <select name="new_status" class="text-sm font-medium border border-gray-200 rounded-lg px-3 py-2 bg-white focus:border-primary focus:ring-2 focus:ring-primary/20 outline-none cursor-pointer">
                                <?php foreach (['Pending', 'Confirmed', 'Completed', 'Cancelled', 'No Show'] as $s): ?>
                                    <option value="<?= $s ?>" <?= $b['status'] === $s ? 'selected' : '' ?>><?= $s ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit" class="text-sm font-bold bg-primary text-white px-4 py-2 rounded-lg hover:bg-primary-dark hover:-translate-y-0.5 focus:ring-2 focus:ring-primary/50 transition-all shadow-md shadow-primary/20">
                                Save
                            </button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($bookings)): ?>
                <tr>
                    <td colspan="5" class="px-6 py-16 text-center">
                        <div class="w-16 h-16 bg-gray-50 rounded-full flex items-center justify-center mx-auto mb-4 text-gray-400 text-2xl">
                            <i class="fas fa-calendar-times"></i>
                        </div>
                        <h4 class="text-gray-900 font-bold mb-1">No appointments found</h4>
                        <p class="text-gray-500 text-sm">You have no assigned appointments matching this filter.</p>
                    </td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once 'footer.php'; ?>
