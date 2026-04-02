<?php
/**
 * Admin - Manage Products (with image upload)
 */
$pageTitle = 'Manage Products';
require_once dirname(__DIR__) . '/includes/config.php';

// Ensure upload directory exists
$uploadDir = SITE_ROOT . '/uploads/products/';
if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add' || $action === 'edit') {
        $name = trim($_POST['name']);
        $category = $_POST['category'];
        $brand = trim($_POST['brand']);
        $price = (float)$_POST['price'];
        $discount = !empty($_POST['discount_price']) ? (float)$_POST['discount_price'] : null;
        $stock = (int)$_POST['stock_quantity'];
        $description = trim($_POST['description']);
        $detailed_description = trim($_POST['detailed_description'] ?? '');
        $tags = trim($_POST['tags']);
        $is_active = isset($_POST['is_active']) ? 1 : 0;

        // Handle image upload
        $imageName = null;
        if (!empty($_FILES['product_image']['name']) && $_FILES['product_image']['error'] === 0) {
            $allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
            if (in_array($_FILES['product_image']['type'], $allowed)) {
                $ext = pathinfo($_FILES['product_image']['name'], PATHINFO_EXTENSION);
                $imageName = 'product_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
                move_uploaded_file($_FILES['product_image']['tmp_name'], $uploadDir . $imageName);
                $imageName = 'uploads/products/' . $imageName;
            }
        }

        if ($action === 'add') {
            $imageVal = $imageName ?: 'products/default.jpg';
            $stmt = $pdo->prepare("INSERT INTO products (name, category, brand, price, discount_price, stock_quantity, description, detailed_description, tags, image, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$name, $category, $brand, $price, $discount, $stock, $description, $detailed_description, $tags, $imageVal, $is_active]);
            setFlash('success', 'Product added!');
        } else {
            $id = (int)$_POST['product_id'];
            if ($imageName) {
                $stmt = $pdo->prepare("UPDATE products SET name=?, category=?, brand=?, price=?, discount_price=?, stock_quantity=?, description=?, detailed_description=?, tags=?, image=?, is_active=? WHERE id=?");
                $stmt->execute([$name, $category, $brand, $price, $discount, $stock, $description, $detailed_description, $tags, $imageName, $is_active, $id]);
            } else {
                $stmt = $pdo->prepare("UPDATE products SET name=?, category=?, brand=?, price=?, discount_price=?, stock_quantity=?, description=?, detailed_description=?, tags=?, is_active=? WHERE id=?");
                $stmt->execute([$name, $category, $brand, $price, $discount, $stock, $description, $detailed_description, $tags, $is_active, $id]);
            }
            setFlash('success', 'Product updated!');
        }
    } elseif ($action === 'delete') {
        $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
        $stmt->execute([(int)$_POST['product_id']]);
        setFlash('success', 'Product deleted.');
    }
    redirect(SITE_URL . '/admin/products.php');
}

$editProduct = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([(int)$_GET['edit']]);
    $editProduct = $stmt->fetch();
}

// Search & filter
$search = $_GET['search'] ?? '';
$catFilter = $_GET['category'] ?? '';
$where = '1=1';
$params = [];
if ($search) { $where .= ' AND (name LIKE ? OR brand LIKE ?)'; $params[] = "%$search%"; $params[] = "%$search%"; }
if ($catFilter) { $where .= ' AND category = ?'; $params[] = $catFilter; }

$stmt = $pdo->prepare("SELECT * FROM products WHERE $where ORDER BY created_at DESC");
$stmt->execute($params);
$products = $stmt->fetchAll();

$productCategories = ['Hair Care', 'Beard & Moustache', 'Skin Care', 'Color & Treatments'];

require_once 'header.php';
?>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Form -->
    <div class="lg:col-span-1">
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 sticky top-24">
            <h3 class="text-lg font-bold text-gray-900 mb-4"><?= $editProduct ? 'Edit Product' : 'Add Product' ?></h3>
            <form method="POST" enctype="multipart/form-data" class="space-y-3">
                <input type="hidden" name="action" value="<?= $editProduct ? 'edit' : 'add' ?>">
                <?php if ($editProduct): ?><input type="hidden" name="product_id" value="<?= $editProduct['id'] ?>"><?php endif; ?>

                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Name *</label>
                    <input type="text" name="name" required value="<?= $editProduct ? sanitize($editProduct['name']) : '' ?>" class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm focus:border-primary outline-none">
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Category</label>
                        <select name="category" class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm focus:border-primary outline-none bg-white">
                            <?php foreach ($productCategories as $c): ?>
                                <option value="<?= $c ?>" <?= ($editProduct && $editProduct['category'] === $c) ? 'selected' : '' ?>><?= $c ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Brand</label>
                        <input type="text" name="brand" value="<?= $editProduct ? sanitize($editProduct['brand']) : '' ?>" class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm focus:border-primary outline-none">
                    </div>
                </div>
                <div class="grid grid-cols-3 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Price *</label>
                        <input type="number" name="price" required step="0.01" value="<?= $editProduct ? $editProduct['price'] : '' ?>" class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm focus:border-primary outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Discount</label>
                        <input type="number" name="discount_price" step="0.01" value="<?= $editProduct ? $editProduct['discount_price'] : '' ?>" class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm focus:border-primary outline-none" placeholder="Opt.">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Stock</label>
                        <input type="number" name="stock_quantity" value="<?= $editProduct ? $editProduct['stock_quantity'] : '0' ?>" class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm focus:border-primary outline-none">
                    </div>
                </div>

                <!-- Image Upload -->
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Product Image</label>
                    <?php if ($editProduct && $editProduct['image']): ?>
                    <div class="mb-2 flex items-center gap-3">
                        <img src="<?= SITE_URL ?>/images/<?= $editProduct['image'] ?>" alt="Current" class="w-16 h-16 rounded-lg object-cover border border-gray-200" onerror="this.src='<?= SITE_URL ?>/<?= $editProduct['image'] ?>'">
                        <span class="text-xs text-gray-500">Current image</span>
                    </div>
                    <?php endif; ?>
                    <input type="file" name="product_image" accept="image/*" class="w-full text-sm file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-primary-bg file:text-primary hover:file:bg-primary hover:file:text-white transition-all cursor-pointer border border-gray-200 rounded-xl">
                </div>

                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Short Description</label>
                    <textarea name="description" rows="2" class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm focus:border-primary outline-none resize-none" placeholder="Brief tagline shown under product image"><?= $editProduct ? sanitize($editProduct['description']) : '' ?></textarea>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Detailed Description</label>
                    <textarea name="detailed_description" rows="6" class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm focus:border-primary outline-none resize-y" placeholder="Full product description shown on product details page"><?= $editProduct ? sanitize($editProduct['detailed_description'] ?? '') : '' ?></textarea>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Tags (comma separated)</label>
                    <input type="text" name="tags" value="<?= $editProduct ? sanitize($editProduct['tags']) : '' ?>" placeholder="hair, serum, repair" class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm focus:border-primary outline-none">
                </div>
                <div class="flex items-center gap-2">
                    <input type="checkbox" name="is_active" id="prod_active" class="accent-primary" <?= (!$editProduct || $editProduct['is_active']) ? 'checked' : '' ?>>
                    <label for="prod_active" class="text-sm text-gray-700">Active</label>
                </div>
                <div class="flex gap-2">
                    <button type="submit" class="flex-1 bg-primary text-white py-2.5 rounded-xl text-sm font-semibold hover:bg-primary-dark transition-all"><?= $editProduct ? 'Update' : 'Add' ?> Product</button>
                    <?php if ($editProduct): ?>
                        <a href="products.php" class="px-4 py-2.5 border border-gray-200 rounded-xl text-sm text-gray-700 hover:bg-gray-50 transition-all">Cancel</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <!-- Products List -->
    <div class="lg:col-span-2">
        <!-- Search & Filter -->
        <div class="flex flex-col sm:flex-row gap-3 mb-5">
            <form class="flex-1 flex gap-2" method="GET">
                <input type="text" name="search" value="<?= sanitize($search) ?>" placeholder="Search products..." class="flex-1 px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:border-primary outline-none bg-white">
                <button type="submit" class="px-4 py-2.5 bg-primary text-white rounded-xl text-sm font-semibold hover:bg-primary-dark transition-all"><i class="fas fa-search"></i></button>
            </form>
            <div class="flex gap-2 flex-wrap">
                <a href="products.php" class="px-3 py-2 rounded-xl text-xs font-semibold transition-all <?= !$catFilter ? 'bg-primary text-white' : 'bg-white border border-gray-200 text-gray-700 hover:bg-gray-50' ?>">All</a>
                <?php foreach ($productCategories as $c): ?>
                <a href="products.php?category=<?= urlencode($c) ?>" class="px-3 py-2 rounded-xl text-xs font-semibold transition-all <?= $catFilter === $c ? 'bg-primary text-white' : 'bg-white border border-gray-200 text-gray-700 hover:bg-gray-50' ?>"><?= $c ?></a>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="bg-gray-50 text-gray-600 uppercase text-xs">
                        <tr>
                            <th class="px-4 py-3">Product</th>
                            <th class="px-4 py-3">Price</th>
                            <th class="px-4 py-3">Stock</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <?php foreach ($products as $p): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    <img src="<?= SITE_URL ?>/images/<?= $p['image'] ?>" alt="" class="w-10 h-10 rounded-lg object-cover border border-gray-100" onerror="this.src='<?= SITE_URL ?>/<?= $p['image'] ?>'">
                                    <div>
                                        <p class="font-medium text-gray-900"><?= sanitize($p['name']) ?></p>
                                        <p class="text-xs text-gray-500"><?= sanitize($p['category']) ?> • <?= sanitize($p['brand']) ?></p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <p class="font-medium text-primary"><?= formatPrice($p['discount_price'] ?? $p['price']) ?></p>
                                <?php if ($p['discount_price']): ?>
                                    <p class="text-xs text-gray-400 line-through"><?= formatPrice($p['price']) ?></p>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-3">
                                <span class="<?= $p['stock_quantity'] < 5 ? 'text-red-500 font-semibold' : 'text-gray-700' ?>"><?= $p['stock_quantity'] ?></span>
                            </td>
                            <td class="px-4 py-3">
                                <span class="text-xs font-semibold px-2.5 py-1 rounded-full <?= $p['is_active'] ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500' ?>">
                                    <?= $p['is_active'] ? 'Active' : 'Inactive' ?>
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-1">
                                    <a href="product-images.php?id=<?= $p['id'] ?>" class="w-8 h-8 flex items-center justify-center rounded-lg text-purple-600 hover:bg-purple-50" title="Manage Images"><i class="fas fa-images text-sm"></i></a>
                                    <a href="products.php?edit=<?= $p['id'] ?>" class="w-8 h-8 flex items-center justify-center rounded-lg text-blue-600 hover:bg-blue-50"><i class="fas fa-edit text-sm"></i></a>
                                    <form method="POST" onsubmit="return confirm('Delete this product?')">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
                                        <button class="w-8 h-8 flex items-center justify-center rounded-lg text-red-500 hover:bg-red-50"><i class="fas fa-trash text-sm"></i></button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($products)): ?>
                        <tr><td colspan="5" class="px-5 py-10 text-center text-gray-500">No products found</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>
