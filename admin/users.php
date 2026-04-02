<?php
/**
 * Admin - Manage Users (with booking history & search)
 */
$pageTitle = 'Manage Customers';
require_once dirname(__DIR__) . '/includes/config.php';

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'update_role') {
        $userId = (int)$_POST['user_id'];
        $newRole = $_POST['new_role'];
        $allowed = ['CUSTOMER', 'STAFF', 'ADMIN'];
        if (in_array($newRole, $allowed) && $userId !== (int)$_SESSION['user_id']) {
            $stmt = $pdo->prepare("UPDATE users SET role = ? WHERE id = ?");
            $stmt->execute([$newRole, $userId]);
            setFlash('success', 'User role updated.');
        }
    } elseif ($action === 'delete') {
        $userId = (int)$_POST['user_id'];
        if ($userId !== (int)$_SESSION['user_id']) {
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            setFlash('success', 'User deleted.');
        }
    }
    redirect(SITE_URL . '/admin/users.php');
}

// Search
$search = $_GET['search'] ?? '';
$roleFilter = $_GET['role'] ?? '';
$where = '1=1';
$params = [];
if ($search) { $where .= ' AND (full_name LIKE ? OR email LIKE ? OR username LIKE ?)'; $params[] = "%$search%"; $params[] = "%$search%"; $params[] = "%$search%"; }
if ($roleFilter) { $where .= ' AND role = ?'; $params[] = $roleFilter; }

$stmt = $pdo->prepare("SELECT * FROM users WHERE $where ORDER BY created_at DESC");
$stmt->execute($params);
$users = $stmt->fetchAll();

// Get booking counts per user
$bookingCounts = [];
$bcStmt = $pdo->query("SELECT user_id, COUNT(*) as cnt FROM bookings GROUP BY user_id");
foreach ($bcStmt->fetchAll() as $row) { $bookingCounts[$row['user_id']] = $row['cnt']; }

// Get order counts per user
$orderCounts = [];
$ocStmt = $pdo->query("SELECT user_id, COUNT(*) as cnt FROM orders GROUP BY user_id");
foreach ($ocStmt->fetchAll() as $row) { $orderCounts[$row['user_id']] = $row['cnt']; }

require_once 'header.php';
?>

<!-- Filters -->
<div class="flex flex-col sm:flex-row gap-4 items-start sm:items-center justify-between mb-6">
    <div class="flex flex-wrap gap-2">
        <a href="users.php" class="px-4 py-2 rounded-full text-sm font-semibold transition-all <?= !$roleFilter ? 'bg-primary text-white shadow-lg shadow-primary/20' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' ?>">All (<?= count($users) ?>)</a>
        <?php foreach (['CUSTOMER', 'STAFF', 'ADMIN'] as $r): ?>
            <a href="users.php?role=<?= $r ?>" class="px-4 py-2 rounded-full text-sm font-semibold transition-all <?= $roleFilter === $r ? 'bg-primary text-white shadow-lg shadow-primary/20' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' ?>"><?= $r ?></a>
        <?php endforeach; ?>
    </div>
    <form method="GET" class="flex gap-2">
        <?php if ($roleFilter): ?><input type="hidden" name="role" value="<?= $roleFilter ?>"><?php endif; ?>
        <input type="text" name="search" value="<?= sanitize($search) ?>" placeholder="Search users..." class="px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:border-primary outline-none w-56">
        <button class="px-4 py-2.5 bg-primary text-white rounded-xl text-sm font-semibold hover:bg-primary-dark transition-all"><i class="fas fa-search"></i></button>
    </form>
</div>

<div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm text-left">
            <thead class="bg-gray-50 text-gray-600 uppercase text-xs">
                <tr>
                    <th class="px-5 py-3">User</th>
                    <th class="px-5 py-3">Email</th>
                    <th class="px-5 py-3">Phone</th>
                    <th class="px-5 py-3">Activity</th>
                    <th class="px-5 py-3">Role</th>
                    <th class="px-5 py-3">Joined</th>
                    <th class="px-5 py-3">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <?php foreach ($users as $u): ?>
                <tr class="hover:bg-gray-50 transition-colors">
                    <td class="px-5 py-3">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-2xl bg-primary-bg flex items-center justify-center text-primary text-sm font-bold flex-shrink-0">
                                <?= strtoupper(substr($u['username'], 0, 1)) ?>
                            </div>
                            <div>
                                <p class="font-semibold text-gray-900"><?= sanitize($u['full_name'] ?? $u['username']) ?></p>
                                <p class="text-xs text-gray-500">@<?= sanitize($u['username']) ?></p>
                            </div>
                        </div>
                    </td>
                    <td class="px-5 py-3 text-xs text-gray-600"><?= sanitize($u['email']) ?></td>
                    <td class="px-5 py-3 text-xs text-gray-600"><?= sanitize($u['phone'] ?? '—') ?></td>
                    <td class="px-5 py-3">
                        <div class="flex items-center gap-3 text-xs">
                            <span class="text-gray-600"><i class="fas fa-calendar-check text-purple-400 mr-1"></i><?= $bookingCounts[$u['id']] ?? 0 ?></span>
                            <span class="text-gray-600"><i class="fas fa-shopping-bag text-green-400 mr-1"></i><?= $orderCounts[$u['id']] ?? 0 ?></span>
                        </div>
                    </td>
                    <td class="px-5 py-3">
                        <span class="text-xs font-semibold px-2.5 py-1 rounded-full
                            <?= $u['role'] === 'ADMIN' ? 'bg-red-100 text-red-700' : '' ?>
                            <?= $u['role'] === 'STAFF' ? 'bg-blue-100 text-blue-700' : '' ?>
                            <?= $u['role'] === 'CUSTOMER' ? 'bg-gray-100 text-gray-700' : '' ?>">
                            <?= $u['role'] ?>
                        </span>
                    </td>
                    <td class="px-5 py-3 text-xs text-gray-500"><?= date('M d, Y', strtotime($u['created_at'])) ?></td>
                    <td class="px-5 py-3">
                        <?php if ($u['id'] !== (int)$_SESSION['user_id']): ?>
                        <div class="flex items-center gap-2">
                            <form method="POST" class="flex items-center gap-1">
                                <input type="hidden" name="action" value="update_role">
                                <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                <select name="new_role" class="text-xs border border-gray-200 rounded-lg px-2 py-1.5 bg-white focus:border-primary outline-none">
                                    <?php foreach (['CUSTOMER', 'STAFF', 'ADMIN'] as $r): ?>
                                        <option value="<?= $r ?>" <?= $u['role'] === $r ? 'selected' : '' ?>><?= $r ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="submit" class="text-xs bg-primary text-white px-3 py-1.5 rounded-lg hover:bg-primary-dark transition-all">Save</button>
                            </form>
                            <form method="POST" onsubmit="return confirm('Delete this user permanently? This cannot be undone.')">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                <button class="w-8 h-8 flex items-center justify-center rounded-lg text-red-500 hover:bg-red-50 transition-all"><i class="fas fa-trash text-sm"></i></button>
                            </form>
                        </div>
                        <?php else: ?>
                            <span class="text-xs text-gray-400 italic">You</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($users)): ?>
                <tr><td colspan="7" class="px-5 py-10 text-center text-gray-500">No users found</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once 'footer.php'; ?>
