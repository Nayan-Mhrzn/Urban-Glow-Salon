<?php
/**
 * Admin - Staff Management
 */
$pageTitle = 'Manage Staff';
require_once dirname(__DIR__) . '/includes/config.php';

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $username = trim($_POST['username']);
        $email = trim($_POST['email']);
        $fullName = trim($_POST['full_name']);
        $phone = trim($_POST['phone']);
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

        // Check duplicate
        $check = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $check->execute([$username, $email]);
        if ($check->fetch()) {
            setFlash('error', 'Username or email already exists.');
        } else {
            $stmt = $pdo->prepare("INSERT INTO users (username, email, password, full_name, phone, role) VALUES (?, ?, ?, ?, ?, 'STAFF')");
            $stmt->execute([$username, $email, $password, $fullName, $phone]);
            $staffId = $pdo->lastInsertId();

            // Assign services
            if (!empty($_POST['services'])) {
                $sStmt = $pdo->prepare("INSERT INTO staff_services (staff_id, service_id) VALUES (?, ?)");
                foreach ($_POST['services'] as $svcId) {
                    $sStmt->execute([$staffId, (int)$svcId]);
                }
            }
            setFlash('success', 'Staff member added successfully!');
        }
    } elseif ($action === 'edit') {
        $staffId = (int)$_POST['staff_id'];
        $fullName = trim($_POST['full_name']);
        $email = trim($_POST['email']);
        $phone = trim($_POST['phone']);

        $stmt = $pdo->prepare("UPDATE users SET full_name = ?, email = ?, phone = ? WHERE id = ? AND role = 'STAFF'");
        $stmt->execute([$fullName, $email, $phone, $staffId]);

        // Update services
        $pdo->prepare("DELETE FROM staff_services WHERE staff_id = ?")->execute([$staffId]);
        if (!empty($_POST['services'])) {
            $sStmt = $pdo->prepare("INSERT INTO staff_services (staff_id, service_id) VALUES (?, ?)");
            foreach ($_POST['services'] as $svcId) {
                $sStmt->execute([$staffId, (int)$svcId]);
            }
        }
        setFlash('success', 'Staff member updated!');
    } elseif ($action === 'delete') {
        $staffId = (int)$_POST['staff_id'];
        $pdo->prepare("DELETE FROM users WHERE id = ? AND role = 'STAFF'")->execute([$staffId]);
        setFlash('success', 'Staff member removed.');
    }
    redirect(SITE_URL . '/admin/staff.php');
}

// Get all staff
$staffMembers = $pdo->query("SELECT * FROM users WHERE role = 'STAFF' ORDER BY full_name")->fetchAll();

// Get services for assignment
$allServices = $pdo->query("SELECT id, name, category FROM services WHERE is_active = 1 ORDER BY category, name")->fetchAll();

// Get assigned services per staff
$staffServiceMap = [];
$ssStmt = $pdo->query("SELECT staff_id, service_id FROM staff_services");
foreach ($ssStmt->fetchAll() as $row) {
    $staffServiceMap[$row['staff_id']][] = $row['service_id'];
}

// Get booking counts per staff
$staffBookings = [];
$sbStmt = $pdo->query("SELECT staff_id, COUNT(*) as cnt FROM bookings WHERE staff_id IS NOT NULL GROUP BY staff_id");
foreach ($sbStmt->fetchAll() as $row) {
    $staffBookings[$row['staff_id']] = $row['cnt'];
}

// Edit mode
$editStaff = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND role = 'STAFF'");
    $stmt->execute([(int)$_GET['edit']]);
    $editStaff = $stmt->fetch();
}

require_once 'header.php';
?>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Add/Edit Form -->
    <div class="lg:col-span-1">
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 sticky top-24">
            <h3 class="text-lg font-bold text-gray-900 mb-4"><?= $editStaff ? 'Edit Staff Member' : 'Add Staff Member' ?></h3>
            <form method="POST" class="space-y-3">
                <input type="hidden" name="action" value="<?= $editStaff ? 'edit' : 'add' ?>">
                <?php if ($editStaff): ?><input type="hidden" name="staff_id" value="<?= $editStaff['id'] ?>"><?php endif; ?>

                <?php if (!$editStaff): ?>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Username *</label>
                    <input type="text" name="username" required class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm focus:border-primary outline-none">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Password *</label>
                    <input type="password" name="password" required minlength="6" class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm focus:border-primary outline-none">
                </div>
                <?php endif; ?>

                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Full Name *</label>
                    <input type="text" name="full_name" required value="<?= $editStaff ? sanitize($editStaff['full_name']) : '' ?>" class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm focus:border-primary outline-none">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Email *</label>
                    <input type="email" name="email" required value="<?= $editStaff ? sanitize($editStaff['email']) : '' ?>" class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm focus:border-primary outline-none">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Phone</label>
                    <input type="tel" name="phone" value="<?= $editStaff ? sanitize($editStaff['phone']) : '' ?>" class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm focus:border-primary outline-none">
                </div>

                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-2">Assigned Services</label>
                    <div class="max-h-48 overflow-y-auto border border-gray-200 rounded-xl p-3 space-y-2">
                        <?php
                        $editServices = $editStaff ? ($staffServiceMap[$editStaff['id']] ?? []) : [];
                        foreach ($allServices as $svc):
                        ?>
                        <label class="flex items-center gap-2 text-sm cursor-pointer hover:bg-gray-50 px-2 py-1 rounded-lg">
                            <input type="checkbox" name="services[]" value="<?= $svc['id'] ?>" class="accent-primary" <?= in_array($svc['id'], $editServices) ? 'checked' : '' ?>>
                            <?= sanitize($svc['name']) ?> <span class="text-xs text-gray-400">(<?= $svc['category'] ?>)</span>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="flex gap-2 pt-2">
                    <button type="submit" class="flex-1 bg-primary text-white py-2.5 rounded-xl text-sm font-semibold hover:bg-primary-dark transition-all"><?= $editStaff ? 'Update' : 'Add' ?> Staff</button>
                    <?php if ($editStaff): ?>
                        <a href="staff.php" class="px-4 py-2.5 border border-gray-200 rounded-xl text-sm text-gray-700 hover:bg-gray-50 transition-all">Cancel</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <!-- Staff Grid -->
    <div class="lg:col-span-2">
        <?php if (empty($staffMembers)): ?>
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-12 text-center">
            <i class="fas fa-user-tie text-5xl text-gray-300 mb-4"></i>
            <h3 class="text-lg font-semibold text-gray-700 mb-2">No Staff Members</h3>
            <p class="text-sm text-gray-500">Add your first staff member using the form.</p>
        </div>
        <?php else: ?>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <?php foreach ($staffMembers as $s): ?>
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 hover:shadow-md transition-all">
                <div class="flex items-start justify-between mb-4">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 rounded-2xl bg-primary-bg flex items-center justify-center text-primary text-lg font-bold">
                            <?= strtoupper(substr($s['full_name'], 0, 1)) ?>
                        </div>
                        <div>
                            <p class="font-bold text-gray-900"><?= sanitize($s['full_name']) ?></p>
                            <p class="text-xs text-gray-500">@<?= sanitize($s['username']) ?></p>
                        </div>
                    </div>
                    <div class="flex items-center gap-1">
                        <a href="staff.php?edit=<?= $s['id'] ?>" class="w-8 h-8 flex items-center justify-center rounded-lg text-blue-600 hover:bg-blue-50 transition-all"><i class="fas fa-edit text-sm"></i></a>
                        <form method="POST" onsubmit="return confirm('Remove this staff member?')">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="staff_id" value="<?= $s['id'] ?>">
                            <button class="w-8 h-8 flex items-center justify-center rounded-lg text-red-500 hover:bg-red-50 transition-all"><i class="fas fa-trash text-sm"></i></button>
                        </form>
                    </div>
                </div>
                <div class="space-y-2 text-sm">
                    <div class="flex items-center gap-2 text-gray-600">
                        <i class="fas fa-envelope w-4 text-center text-xs text-gray-400"></i> <?= sanitize($s['email']) ?>
                    </div>
                    <?php if ($s['phone']): ?>
                    <div class="flex items-center gap-2 text-gray-600">
                        <i class="fas fa-phone w-4 text-center text-xs text-gray-400"></i> <?= sanitize($s['phone']) ?>
                    </div>
                    <?php endif; ?>
                    <div class="flex items-center gap-2 text-gray-600">
                        <i class="fas fa-calendar-check w-4 text-center text-xs text-gray-400"></i> <?= $staffBookings[$s['id']] ?? 0 ?> bookings
                    </div>
                </div>
                <?php
                $assignedSvcs = $staffServiceMap[$s['id']] ?? [];
                if (!empty($assignedSvcs)):
                    $svcNames = $pdo->query("SELECT name FROM services WHERE id IN (" . implode(',', array_map('intval', $assignedSvcs)) . ")")->fetchAll(PDO::FETCH_COLUMN);
                ?>
                <div class="mt-3 flex flex-wrap gap-1">
                    <?php foreach ($svcNames as $name): ?>
                    <span class="text-[10px] font-semibold px-2 py-0.5 rounded-full bg-primary-bg text-primary"><?= sanitize($name) ?></span>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once 'footer.php'; ?>
