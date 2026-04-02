<?php
/**
 * Admin - Manage Services
 */
$pageTitle = 'Manage Services';
require_once dirname(__DIR__) . '/includes/config.php';

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add' || $action === 'edit') {
        $name = trim($_POST['name']);
        $category = $_POST['category'];
        $price = (float)$_POST['price'];
        $duration = (int)$_POST['duration_mins'];
        $gender = $_POST['gender'];
        $description = trim($_POST['description']);
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        
        if ($action === 'add') {
            $stmt = $pdo->prepare("INSERT INTO services (name, category, price, duration_mins, gender, description, is_active) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$name, $category, $price, $duration, $gender, $description, $is_active]);
            setFlash('success', 'Service added successfully!');
        } else {
            $id = (int)$_POST['service_id'];
            $stmt = $pdo->prepare("UPDATE services SET name=?, category=?, price=?, duration_mins=?, gender=?, description=?, is_active=? WHERE id=?");
            $stmt->execute([$name, $category, $price, $duration, $gender, $description, $is_active, $id]);
            setFlash('success', 'Service updated successfully!');
        }
    } elseif ($action === 'delete') {
        $id = (int)$_POST['service_id'];
        $stmt = $pdo->prepare("DELETE FROM services WHERE id = ?");
        $stmt->execute([$id]);
        setFlash('success', 'Service deleted.');
    } elseif ($action === 'toggle') {
        $id = (int)$_POST['service_id'];
        $pdo->prepare("UPDATE services SET is_active = NOT is_active WHERE id = ?")->execute([$id]);
        setFlash('success', 'Service status toggled.');
    }
    redirect(SITE_URL . '/admin/services.php');
}

// Edit mode?
$editService = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM services WHERE id = ?");
    $stmt->execute([(int)$_GET['edit']]);
    $editService = $stmt->fetch();
}

$services = $pdo->query("SELECT * FROM services ORDER BY category, name")->fetchAll();
$categories = ['Hair', 'Skin', 'Body', 'Makeup', 'Nails'];

require_once 'header.php';
?>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Form Card -->
    <div class="lg:col-span-1">
        <div class="bg-white rounded-xl border border-gray-200 p-5 sticky top-24">
            <h3 class="text-lg font-bold text-gray-900 mb-4"><?= $editService ? 'Edit Service' : 'Add Service' ?></h3>
            <form method="POST" class="space-y-3">
                <input type="hidden" name="action" value="<?= $editService ? 'edit' : 'add' ?>">
                <?php if ($editService): ?><input type="hidden" name="service_id" value="<?= $editService['id'] ?>"><?php endif; ?>
                
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Name *</label>
                    <input type="text" name="name" required value="<?= $editService ? sanitize($editService['name']) : '' ?>" class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:border-primary outline-none">
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Category</label>
                        <select name="category" class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:border-primary outline-none bg-white">
                            <?php foreach ($categories as $c): ?>
                                <option value="<?= $c ?>" <?= ($editService && $editService['category'] === $c) ? 'selected' : '' ?>><?= $c ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Gender</label>
                        <select name="gender" class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:border-primary outline-none bg-white">
                            <?php foreach (['Unisex', 'Male', 'Female'] as $g): ?>
                                <option value="<?= $g ?>" <?= ($editService && $editService['gender'] === $g) ? 'selected' : '' ?>><?= $g ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Price (Rs.)</label>
                        <input type="number" name="price" required step="0.01" value="<?= $editService ? $editService['price'] : '' ?>" class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:border-primary outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Duration (mins)</label>
                        <input type="number" name="duration_mins" required value="<?= $editService ? $editService['duration_mins'] : '30' ?>" class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:border-primary outline-none">
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Description</label>
                    <textarea name="description" rows="2" class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:border-primary outline-none resize-none"><?= $editService ? sanitize($editService['description']) : '' ?></textarea>
                </div>
                <div class="flex items-center gap-2">
                    <input type="checkbox" name="is_active" id="is_active" class="accent-primary" <?= (!$editService || $editService['is_active']) ? 'checked' : '' ?>>
                    <label for="is_active" class="text-sm text-gray-700">Active</label>
                </div>
                <div class="flex gap-2">
                    <button type="submit" class="flex-1 bg-primary text-white py-2 rounded-lg text-sm font-semibold hover:bg-primary-dark transition-all"><?= $editService ? 'Update' : 'Add' ?> Service</button>
                    <?php if ($editService): ?>
                        <a href="services.php" class="px-4 py-2 border border-gray-200 rounded-lg text-sm text-gray-700 hover:bg-gray-50 transition-all">Cancel</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <!-- Services Table -->
    <div class="lg:col-span-2">
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="bg-gray-50 text-gray-600 uppercase text-xs">
                        <tr>
                            <th class="px-4 py-3">Service</th>
                            <th class="px-4 py-3">Category</th>
                            <th class="px-4 py-3">Price</th>
                            <th class="px-4 py-3">Duration</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <?php foreach ($services as $s): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3">
                                <p class="font-medium text-gray-900"><?= sanitize($s['name']) ?></p>
                                <p class="text-xs text-gray-500"><?= $s['gender'] ?></p>
                            </td>
                            <td class="px-4 py-3"><span class="text-xs font-semibold px-2 py-1 rounded-full bg-primary-bg text-primary"><?= $s['category'] ?></span></td>
                            <td class="px-4 py-3 font-medium"><?= formatPrice($s['price']) ?></td>
                            <td class="px-4 py-3"><?= $s['duration_mins'] ?> min</td>
                            <td class="px-4 py-3">
                                <form method="POST" class="inline">
                                    <input type="hidden" name="action" value="toggle">
                                    <input type="hidden" name="service_id" value="<?= $s['id'] ?>">
                                    <button type="submit" class="text-xs font-semibold px-2.5 py-1 rounded-full cursor-pointer <?= $s['is_active'] ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500' ?>">
                                        <?= $s['is_active'] ? 'Active' : 'Inactive' ?>
                                    </button>
                                </form>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-1">
                                    <a href="services.php?edit=<?= $s['id'] ?>" class="w-8 h-8 flex items-center justify-center rounded-lg text-blue-600 hover:bg-blue-50 transition-all"><i class="fas fa-edit text-sm"></i></a>
                                    <form method="POST" onsubmit="return confirm('Delete this service?')">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="service_id" value="<?= $s['id'] ?>">
                                        <button class="w-8 h-8 flex items-center justify-center rounded-lg text-red-500 hover:bg-red-50 transition-all"><i class="fas fa-trash text-sm"></i></button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>
