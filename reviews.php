<?php
/**
 * Reviews Page - Urban Glow Salon
 */
$pageTitle = 'Reviews';
require_once 'includes/config.php';

// Fetch all reviews with user info
$stmt = $pdo->query("SELECT r.*, u.username, u.profile_image FROM reviews r JOIN users u ON r.user_id = u.id ORDER BY r.created_at DESC");
$reviews = $stmt->fetchAll();

require_once 'includes/header.php';
?>

<!-- Reviews Hero -->
<section class="hero-gradient py-12 md:py-16 text-center">
    <div class="max-w-4xl mx-auto px-6">
        <h1 class="text-3xl md:text-5xl font-bold mb-3">Customer <span class="text-primary">Reviews</span></h1>
        <p class="text-gray-600">See what our customers think about Urban Glow Salon</p>
    </div>
</section>

<!-- Reviews Content -->
<div class="max-w-5xl mx-auto px-6 py-12">
    <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
        
        <!-- Share Experience Card -->
        <div class="md:col-span-1">
            <div class="bg-white rounded-3xl shadow-card border border-gray-100 p-8 text-center sticky top-24">
                <div class="w-16 h-16 bg-primary-bg rounded-2xl flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-comment-dots text-primary text-2xl"></i>
                </div>
                <h3 class="text-xl font-bold text-gray-900 mb-2">Share Your Experience</h3>
                <p class="text-sm text-gray-600 mb-5">Help us improve by sharing your feedback</p>
                
                <?php if (isLoggedIn()): ?>
                    <!-- Review Form -->
                    <form action="<?= SITE_URL ?>/api/submit-review.php" method="POST" class="text-left">
                        <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                        
                        <div class="mb-3">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Type</label>
                            <select name="review_type" required class="w-full px-3 py-2 border-2 border-gray-200 rounded-xl text-sm focus:border-primary outline-none transition-all">
                                <option value="Service">Service</option>
                                <option value="Product">Product</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Rating</label>
                            <div class="star-rating-input flex justify-start">
                                <?php for ($i = 5; $i >= 1; $i--): ?>
                                    <input type="radio" id="star<?= $i ?>" name="rating" value="<?= $i ?>" <?= $i === 5 ? 'checked' : '' ?>>
                                    <label for="star<?= $i ?>"><i class="fas fa-star"></i></label>
                                <?php endfor; ?>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Your Review</label>
                            <textarea name="comment" rows="3" required placeholder="Tell us about your experience..." class="w-full px-3 py-2 border-2 border-gray-200 rounded-xl text-sm focus:border-primary outline-none transition-all resize-none"></textarea>
                        </div>

                        <button type="submit" class="w-full bg-primary text-white py-2.5 rounded-full text-sm font-semibold hover:bg-primary-dark transition-all">
                            Submit Review
                        </button>
                    </form>
                <?php else: ?>
                    <p class="text-sm text-gray-500">Please <a href="login.php" class="text-primary font-semibold underline">login</a> to submit a review</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Reviews List -->
        <div class="md:col-span-2 space-y-4">
            <?php if (count($reviews) > 0): ?>
                <?php foreach ($reviews as $review): ?>
                <div class="bg-white rounded-2xl shadow-card border border-gray-100 p-6 hover:shadow-card-hover transition-all duration-300" data-animate>
                    <div class="flex items-center gap-3 mb-3">
                        <div class="w-12 h-12 rounded-full bg-gray-200 flex items-center justify-center overflow-hidden flex-shrink-0">
                            <?php if ($review['profile_image']): ?>
                                <img src="<?= SITE_URL ?>/uploads/<?= $review['profile_image'] ?>" alt="" class="w-full h-full object-cover">
                            <?php else: ?>
                                <i class="fas fa-user text-gray-400 text-lg"></i>
                            <?php endif; ?>
                        </div>
                        <div class="flex-1">
                            <p class="font-semibold text-gray-900 text-sm"><?= sanitize($review['username']) ?></p>
                            <div class="flex items-center gap-2">
                                <div class="flex gap-0.5">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <i class="fas fa-star text-xs <?= $i <= $review['rating'] ? 'text-amber-400' : 'text-gray-300' ?>"></i>
                                    <?php endfor; ?>
                                </div>
                                <span class="text-xs text-gray-400"><?= date('M d, Y', strtotime($review['created_at'])) ?></span>
                            </div>
                        </div>
                    </div>
                    <p class="font-semibold text-gray-800 text-sm mb-1"><?= sanitize($review['review_type']) ?></p>
                    <p class="text-sm text-gray-600 leading-relaxed"><?= sanitize($review['comment']) ?></p>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="text-center py-16">
                    <i class="fas fa-comments text-6xl text-gray-300 mb-4"></i>
                    <h3 class="text-xl font-semibold text-gray-700 mb-2">No reviews yet</h3>
                    <p class="text-gray-500">Be the first to share your experience!</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
