<?php
/**
 * Admin - Manage Product Images
 * Upload, delete, and drag-to-reorder images for a product
 */
$pageTitle = 'Product Images';
require_once dirname(__DIR__) . '/config/config.php';

$productId = (int)($_GET['id'] ?? 0);
if (!$productId) {
    setFlash('error', 'No product selected.');
    redirect(SITE_URL . '/admin/products.php');
}

// Get product
$stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
$stmt->execute([$productId]);
$product = $stmt->fetch();
if (!$product) {
    setFlash('error', 'Product not found.');
    redirect(SITE_URL . '/admin/products.php');
}

$uploadDir = SITE_ROOT . '/images/products/';
if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'upload') {
        // Upload multiple images
        if (!empty($_FILES['images']['name'][0])) {
            $allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
            // Get current max sort_order
            $maxOrder = $pdo->prepare("SELECT COALESCE(MAX(sort_order), 0) FROM product_images WHERE product_id = ?");
            $maxOrder->execute([$productId]);
            $sortOrder = (int)$maxOrder->fetchColumn();

            $insertStmt = $pdo->prepare("INSERT INTO product_images (product_id, image_url, sort_order) VALUES (?, ?, ?)");

            foreach ($_FILES['images']['name'] as $i => $name) {
                if ($_FILES['images']['error'][$i] === 0 && in_array($_FILES['images']['type'][$i], $allowed)) {
                    $ext = pathinfo($name, PATHINFO_EXTENSION);
                    $fileName = 'prod_' . $productId . '_' . time() . '_' . $i . '.' . $ext;
                    if (move_uploaded_file($_FILES['images']['tmp_name'][$i], $uploadDir . $fileName)) {
                        $sortOrder++;
                        $insertStmt->execute([$productId, 'images/products/' . $fileName, $sortOrder]);
                    }
                }
            }
            setFlash('success', 'Images uploaded!');
        }
    } elseif ($action === 'delete') {
        $imageId = (int)$_POST['image_id'];
        // Get image path before deleting
        $imgStmt = $pdo->prepare("SELECT image_url FROM product_images WHERE id = ? AND product_id = ?");
        $imgStmt->execute([$imageId, $productId]);
        $imgPath = $imgStmt->fetchColumn();
        if ($imgPath) {
            // Delete file from disk
            $fullPath = SITE_ROOT . '/' . $imgPath;
            if (file_exists($fullPath)) unlink($fullPath);
            // Delete from DB
            $pdo->prepare("DELETE FROM product_images WHERE id = ?")->execute([$imageId]);
            setFlash('success', 'Image deleted.');
        }
    } elseif ($action === 'reorder') {
        // Ajax reorder - array of image IDs in new order
        $order = json_decode($_POST['order'] ?? '[]', true);
        if (is_array($order)) {
            $stmt = $pdo->prepare("UPDATE product_images SET sort_order = ? WHERE id = ? AND product_id = ?");
            foreach ($order as $pos => $id) {
                $stmt->execute([$pos + 1, (int)$id, $productId]);
            }
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
                header('Content-Type: application/json');
                echo json_encode(['success' => true]);
                exit;
            }
            setFlash('success', 'Order updated!');
        }
    } elseif ($action === 'set_primary') {
        $imageId = (int)$_POST['image_id'];
        $imgStmt = $pdo->prepare("SELECT image_url FROM product_images WHERE id = ? AND product_id = ?");
        $imgStmt->execute([$imageId, $productId]);
        $imgUrl = $imgStmt->fetchColumn();
        if ($imgUrl) {
            $pdo->prepare("UPDATE products SET image = ? WHERE id = ?")->execute([$imgUrl, $productId]);
            setFlash('success', 'Primary image updated!');
        }
    }

    if (empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
        redirect(SITE_URL . '/admin/product-images.php?id=' . $productId);
    }
}

// Get all images for this product
$images = $pdo->prepare("SELECT * FROM product_images WHERE product_id = ? ORDER BY sort_order ASC");
$images->execute([$productId]);
$productImages = $images->fetchAll();

require_once 'header.php';
?>

<!-- Breadcrumb -->
<div class="flex items-center gap-2 mb-6 text-sm">
    <a href="../shop/products.php" class="text-gray-500 hover:text-primary transition-colors"><i class="fas fa-arrow-left mr-1"></i>Products</a>
    <span class="text-gray-300">/</span>
    <span class="text-gray-900 font-semibold"><?= sanitize($product['name']) ?></span>
    <span class="text-gray-300">/</span>
    <span class="text-primary font-semibold">Images</span>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Upload Card -->
    <div class="lg:col-span-1">
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 sticky top-24">
            <h3 class="text-lg font-bold text-gray-900 mb-2">Upload Images</h3>
            <p class="text-xs text-gray-500 mb-4">Drag and drop or click to upload. Supports JPG, PNG, WebP.</p>

            <form method="POST" enctype="multipart/form-data" id="uploadForm">
                <input type="hidden" name="action" value="upload">
                <label for="imageInput" class="block border-2 border-dashed border-gray-200 rounded-2xl p-8 text-center cursor-pointer hover:border-primary hover:bg-primary-bg/30 transition-all group">
                    <i class="fas fa-cloud-upload-alt text-3xl text-gray-300 group-hover:text-primary transition-colors mb-3"></i>
                    <p class="text-sm font-medium text-gray-700 group-hover:text-primary transition-colors">Click to upload images</p>
                    <p class="text-xs text-gray-400 mt-1">Multiple files allowed</p>
                </label>
                <input type="file" name="images[]" id="imageInput" multiple accept="image/*" class="hidden" onchange="previewFiles(this)">

                <!-- Preview zone -->
                <div id="previewZone" class="grid grid-cols-3 gap-2 mt-4 hidden"></div>

                <button type="submit" id="uploadBtn" class="w-full mt-4 bg-primary text-white py-2.5 rounded-xl text-sm font-semibold hover:bg-primary-dark transition-all hidden">
                    <i class="fas fa-upload mr-1"></i>Upload Images
                </button>
            </form>

            <!-- Product Info -->
            <div class="mt-6 pt-6 border-t border-gray-100">
                <div class="flex items-center gap-3 mb-3">
                    <?php if ($product['image']): ?>
                    <img src="<?= SITE_URL ?>/<?= $product['image'] ?>" alt="" class="w-12 h-12 rounded-xl object-cover border border-gray-100" onerror="this.src='<?= SITE_URL ?>/images/<?= $product['image'] ?>'">
                    <?php endif; ?>
                    <div>
                        <p class="font-semibold text-gray-900 text-sm"><?= sanitize($product['name']) ?></p>
                        <p class="text-xs text-gray-500"><?= sanitize($product['brand']) ?> • <?= sanitize($product['category']) ?></p>
                    </div>
                </div>
                <p class="text-xs text-gray-500"><i class="fas fa-images mr-1"></i><?= count($productImages) ?> images</p>
            </div>
        </div>
    </div>

    <!-- Images Grid (Sortable) -->
    <div class="lg:col-span-2">
        <?php if (empty($productImages)): ?>
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-16 text-center">
            <i class="fas fa-images text-5xl text-gray-200 mb-4"></i>
            <h3 class="text-lg font-semibold text-gray-700 mb-2">No Images Yet</h3>
            <p class="text-sm text-gray-500">Upload images using the form to get started.</p>
        </div>
        <?php else: ?>
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
            <div class="flex items-center justify-between mb-5">
                <h3 class="font-bold text-gray-900"><i class="fas fa-grip-vertical text-gray-400 mr-2"></i>Drag to Reorder</h3>
                <span class="text-xs font-medium text-gray-500 bg-gray-100 px-3 py-1 rounded-full"><?= count($productImages) ?> images</span>
            </div>
            <div id="sortableGrid" class="grid grid-cols-2 sm:grid-cols-3 gap-4">
                <?php foreach ($productImages as $img): ?>
                <div class="sortable-item relative group rounded-2xl overflow-hidden border-2 border-gray-100 hover:border-primary transition-all cursor-grab active:cursor-grabbing" data-id="<?= $img['id'] ?>">
                    <img src="<?= SITE_URL ?>/<?= $img['image_url'] ?>" alt="Product image" class="w-full aspect-square object-cover" onerror="this.src='<?= SITE_URL ?>/images/<?= $img['image_url'] ?>'">

                    <!-- Sort Handle Overlay -->
                    <div class="absolute inset-0 bg-gradient-to-t from-black/50 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity">
                        <div class="absolute top-2 left-2">
                            <span class="text-xs font-bold text-white bg-black/40 backdrop-blur px-2 py-1 rounded-lg">#<?= $img['sort_order'] ?></span>
                        </div>
                        <div class="absolute top-2 right-2 flex gap-1">
                            <!-- Set as Primary -->
                            <form method="POST">
                                <input type="hidden" name="action" value="set_primary">
                                <input type="hidden" name="image_id" value="<?= $img['id'] ?>">
                                <button title="Set as primary" class="w-8 h-8 rounded-lg bg-white/90 backdrop-blur flex items-center justify-center text-amber-500 hover:bg-amber-500 hover:text-white transition-all shadow-sm">
                                    <i class="fas fa-star text-xs"></i>
                                </button>
                            </form>
                            <!-- Delete -->
                            <form method="POST" onsubmit="return confirm('Delete this image?')">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="image_id" value="<?= $img['id'] ?>">
                                <button title="Delete" class="w-8 h-8 rounded-lg bg-white/90 backdrop-blur flex items-center justify-center text-red-500 hover:bg-red-500 hover:text-white transition-all shadow-sm">
                                    <i class="fas fa-trash text-xs"></i>
                                </button>
                            </form>
                        </div>
                        <div class="absolute bottom-3 left-0 right-0 text-center">
                            <span class="text-xs text-white font-medium"><i class="fas fa-grip-vertical mr-1"></i>Drag to move</span>
                        </div>
                    </div>

                    <?php if ($product['image'] === $img['image_url']): ?>
                    <div class="absolute top-2 left-2 group-hover:hidden">
                        <span class="text-[10px] font-bold text-white bg-amber-500 px-2 py-0.5 rounded-full shadow"><i class="fas fa-star mr-0.5"></i>Primary</span>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- SortableJS CDN -->
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script>
// File preview
function previewFiles(input) {
    const zone = document.getElementById('previewZone');
    const btn = document.getElementById('uploadBtn');
    zone.innerHTML = '';
    if (input.files.length > 0) {
        zone.classList.remove('hidden');
        btn.classList.remove('hidden');
        Array.from(input.files).forEach(file => {
            const reader = new FileReader();
            reader.onload = e => {
                zone.innerHTML += `<div class="relative"><img src="${e.target.result}" class="w-full aspect-square object-cover rounded-xl border border-gray-200"><span class="absolute bottom-1 right-1 text-[9px] bg-black/50 text-white px-1.5 py-0.5 rounded">${(file.size/1024).toFixed(0)}KB</span></div>`;
            };
            reader.readAsDataURL(file);
        });
    } else {
        zone.classList.add('hidden');
        btn.classList.add('hidden');
    }
}

// Drag-and-drop reorder
const grid = document.getElementById('sortableGrid');
if (grid) {
    new Sortable(grid, {
        animation: 200,
        ghostClass: 'opacity-30',
        chosenClass: 'ring-2 ring-primary scale-95',
        dragClass: 'shadow-2xl',
        onEnd: function() {
            // Collect new order
            const items = grid.querySelectorAll('.sortable-item');
            const order = Array.from(items).map(item => item.dataset.id);

            // Send reorder request
            const formData = new FormData();
            formData.append('action', 'reorder');
            formData.append('order', JSON.stringify(order));

            fetch(window.location.href, {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            }).then(r => r.json()).then(data => {
                if (data.success) {
                    // Update sort numbers visually
                    items.forEach((item, i) => {
                        const badge = item.querySelector('span');
                        if (badge) badge.textContent = '#' + (i + 1);
                    });
                }
            });
        }
    });
}
</script>

<?php require_once 'footer.php'; ?>



