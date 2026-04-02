<?php
/**
 * Admin - Manage Reviews
 */
$pageTitle = 'Manage Reviews';
require_once dirname(__DIR__) . '/includes/config.php';

// Handle delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] === 'delete') {
    $stmt = $pdo->prepare("DELETE FROM reviews WHERE id = ?");
    $stmt->execute([(int)$_POST['review_id']]);
    setFlash('success', 'Review deleted.');
    redirect(SITE_URL . '/admin/reviews.php');
}

$reviews = $pdo->query("SELECT r.*, u.username, u.full_name FROM reviews r JOIN users u ON r.user_id = u.id ORDER BY r.created_at DESC")->fetchAll();

require_once 'header.php';
?>

<div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm text-left">
            <thead class="bg-gray-50 text-gray-600 uppercase text-xs">
                <tr>
                    <th class="px-5 py-3">Customer</th>
                    <th class="px-5 py-3">Type</th>
                    <th class="px-5 py-3">Rating</th>
                    <th class="px-5 py-3">Comment</th>
                    <th class="px-5 py-3">Date</th>
                    <th class="px-5 py-3">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <?php foreach ($reviews as $r): ?>
                <tr class="hover:bg-gray-50">
                    <td class="px-5 py-3">
                        <p class="font-medium text-gray-900"><?= sanitize($r['full_name'] ?? $r['username']) ?></p>
                        <p class="text-xs text-gray-500">@<?= sanitize($r['username']) ?></p>
                    </td>
                    <td class="px-5 py-3"><span class="text-xs font-semibold px-2 py-1 rounded-full bg-primary-bg text-primary"><?= $r['review_type'] ?></span></td>
                    <td class="px-5 py-3">
                        <div class="flex gap-0.5">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <i class="fas fa-star text-xs <?= $i <= $r['rating'] ? 'text-amber-400' : 'text-gray-300' ?>"></i>
                            <?php endfor; ?>
                        </div>
                    </td>
                    <td class="px-5 py-3 max-w-xs">
                        <p class="text-sm text-gray-700 truncate"><?= sanitize($r['comment']) ?></p>
                    </td>
                    <td class="px-5 py-3 text-xs text-gray-500"><?= date('M d, Y', strtotime($r['created_at'])) ?></td>
                    <td class="px-5 py-3">
                        <form method="POST" onsubmit="return confirm('Delete this review?')">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="review_id" value="<?= $r['id'] ?>">
                            <button class="w-8 h-8 flex items-center justify-center rounded-lg text-red-500 hover:bg-red-50 transition-all"><i class="fas fa-trash text-sm"></i></button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($reviews)): ?>
                <tr><td colspan="6" class="px-5 py-10 text-center text-gray-500">No reviews found</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once 'footer.php'; ?>
