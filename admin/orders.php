<?php
/**
 * Admin - Manage Orders (with order items)
 */
$pageTitle = 'Manage Orders';
require_once dirname(__DIR__) . '/config/config.php';

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $orderId = (int)$_POST['order_id'];
    $newStatus = $_POST['new_status'];
    $allowed = ['Pending', 'Processing', 'Shipped', 'Delivered', 'Cancelled'];
    if (in_array($newStatus, $allowed)) {
        $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
        $stmt->execute([$newStatus, $orderId]);
        setFlash('success', 'Order #' . $orderId . ' updated to ' . $newStatus);
    }
    redirect(SITE_URL . '/admin/orders.php');
}

// Filter
$statusFilter = $_GET['status'] ?? '';
$searchFilter = $_GET['search'] ?? '';
$where = '1=1';
$params = [];
if ($statusFilter) { $where .= ' AND o.status = ?'; $params[] = $statusFilter; }
if ($searchFilter) { $where .= ' AND (u.full_name LIKE ? OR o.id LIKE ?)'; $params[] = "%$searchFilter%"; $params[] = "%$searchFilter%"; }

$stmt = $pdo->prepare("SELECT o.*, u.username, u.full_name, u.phone FROM orders o JOIN users u ON o.user_id = u.id WHERE $where ORDER BY o.created_at DESC");
$stmt->execute($params);
$orders = $stmt->fetchAll();

// Get order items for each order
$orderItems = [];
if (!empty($orders)) {
    $orderIds = array_column($orders, 'id');
    $placeholders = implode(',', array_fill(0, count($orderIds), '?'));
    $itemsStmt = $pdo->prepare("SELECT oi.*, p.name as product_name, p.image as product_image FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id IN ($placeholders)");
    $itemsStmt->execute($orderIds);
    foreach ($itemsStmt->fetchAll() as $item) {
        $orderItems[$item['order_id']][] = $item;
    }
}

require_once 'header.php';
?>

<!-- Filters -->
<div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 mb-6">
    <div class="flex flex-col sm:flex-row gap-4 items-start sm:items-center justify-between">
        <div class="flex flex-wrap items-center gap-2">
            <a href="orders.php" class="px-4 py-2 rounded-full text-sm font-semibold transition-all <?= !$statusFilter ? 'bg-primary text-white shadow-lg shadow-primary/20' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' ?>">All</a>
            <?php foreach (['Pending', 'Processing', 'Shipped', 'Delivered', 'Cancelled'] as $s): ?>
                <a href="orders.php?status=<?= $s ?>" class="px-4 py-2 rounded-full text-sm font-semibold transition-all <?= $statusFilter === $s ? 'bg-primary text-white shadow-lg shadow-primary/20' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' ?>"><?= $s ?></a>
            <?php endforeach; ?>
        </div>
        <form method="GET" class="flex gap-2">
            <?php if ($statusFilter): ?><input type="hidden" name="status" value="<?= $statusFilter ?>"><?php endif; ?>
            <input type="text" name="search" value="<?= sanitize($searchFilter) ?>" placeholder="Search by name or order #..." class="px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:border-primary outline-none w-56">
            <button class="px-4 py-2.5 bg-primary text-white rounded-xl text-sm font-semibold hover:bg-primary-dark transition-all"><i class="fas fa-search"></i></button>
        </form>
    </div>
</div>

<!-- Orders -->
<div class="space-y-4">
    <?php foreach ($orders as $o): ?>
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
        <!-- Order Header -->
        <div class="px-6 py-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-gray-50">
            <div class="flex items-center gap-4">
                <div class="w-10 h-10 rounded-xl bg-primary-bg flex items-center justify-center text-primary font-bold text-sm">#<?= $o['id'] ?></div>
                <div>
                    <p class="font-semibold text-gray-900"><?= sanitize($o['full_name']) ?></p>
                    <p class="text-xs text-gray-500"><?= sanitize($o['phone'] ?? $o['username']) ?> • <?= date('M d, Y h:i A', strtotime($o['created_at'])) ?></p>
                </div>
            </div>
            <div class="flex items-center gap-3">
                <span class="text-lg font-extrabold text-primary"><?= formatPrice($o['total_amount']) ?></span>
                <span class="text-xs font-semibold px-2.5 py-1 rounded-full
                    <?= $o['status'] === 'Pending' ? 'bg-yellow-100 text-yellow-700' : '' ?>
                    <?= $o['status'] === 'Processing' ? 'bg-blue-100 text-blue-700' : '' ?>
                    <?= $o['status'] === 'Shipped' ? 'bg-indigo-100 text-indigo-700' : '' ?>
                    <?= $o['status'] === 'Delivered' ? 'bg-green-100 text-green-700' : '' ?>
                    <?= $o['status'] === 'Cancelled' ? 'bg-red-100 text-red-700' : '' ?>">
                    <?= $o['status'] ?>
                </span>
            </div>
        </div>

        <!-- Order Items (Expandable) -->
        <div class="order-items hidden" id="items-<?= $o['id'] ?>">
            <?php if (!empty($orderItems[$o['id']])): ?>
            <div class="px-6 py-3 bg-gray-50 divide-y divide-gray-100">
                <?php foreach ($orderItems[$o['id']] as $item): ?>
                <div class="flex items-center gap-3 py-2">
                    <img src="<?= SITE_URL ?>/images/<?= $item['product_image'] ?>" alt="" class="w-8 h-8 rounded-lg object-cover" onerror="this.src='<?= SITE_URL ?>/<?= $item['product_image'] ?>'">
                    <span class="text-sm text-gray-700 flex-1"><?= sanitize($item['product_name']) ?></span>
                    <span class="text-xs text-gray-500">x<?= $item['quantity'] ?></span>
                    <span class="text-sm font-semibold text-gray-900"><?= formatPrice($item['price_at_purchase'] * $item['quantity']) ?></span>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
            <?php if ($o['shipping_address']): ?>
            <div class="px-6 py-3 bg-gray-50 border-t border-gray-100">
                <p class="text-xs text-gray-500"><i class="fas fa-map-marker-alt mr-1"></i><?= sanitize($o['shipping_address']) ?></p>
            </div>
            <?php endif; ?>
        </div>

        <!-- Order Footer -->
        <div class="px-6 py-3 flex items-center justify-between bg-white">
            <button onclick="document.getElementById('items-<?= $o['id'] ?>').classList.toggle('hidden')" class="text-xs text-primary font-semibold hover:underline">
                <i class="fas fa-chevron-down mr-1"></i>View Items
            </button>
            <div class="flex items-center gap-2">
                <span class="text-xs text-gray-500"><?= sanitize($o['payment_method']) ?></span>
                <form method="POST" class="flex items-center gap-2">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="order_id" value="<?= $o['id'] ?>">
                    <select name="new_status" class="text-xs border border-gray-200 rounded-lg px-2 py-1.5 bg-white focus:border-primary outline-none">
                        <?php foreach (['Pending', 'Processing', 'Shipped', 'Delivered', 'Cancelled'] as $s): ?>
                            <option value="<?= $s ?>" <?= $o['status'] === $s ? 'selected' : '' ?>><?= $s ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" class="text-xs bg-primary text-white px-3 py-1.5 rounded-lg hover:bg-primary-dark transition-all">Update</button>
                </form>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
    <?php if (empty($orders)): ?>
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-12 text-center">
        <i class="fas fa-shopping-bag text-5xl text-gray-300 mb-4"></i>
        <p class="text-gray-500">No orders found</p>
    </div>
    <?php endif; ?>
</div>

<?php require_once 'footer.php'; ?>


