<?php
/**
 * Admin - Settings
 */
$pageTitle = 'Settings';
require_once dirname(__DIR__) . '/config/config.php';

// Handle save
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fields = ['site_name', 'site_tagline', 'site_email', 'site_phone', 'site_address', 'opening_time', 'closing_time', 'payment_cod', 'payment_esewa', 'payment_khalti'];
    $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
    foreach ($fields as $key) {
        $value = $_POST[$key] ?? '';
        // Checkboxes - set to 1 if present, 0 if not
        if (in_array($key, ['payment_cod', 'payment_esewa', 'payment_khalti'])) {
            $value = isset($_POST[$key]) ? '1' : '0';
        }
        $stmt->execute([$key, $value]);
    }
    setFlash('success', 'Settings saved successfully!');
    redirect(SITE_URL . '/admin/settings.php');
}

// (We use get_site_setting from includes/functions.php)

require_once 'header.php';
?>

<form method="POST" class="max-w-4xl">
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        <!-- General Settings -->
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
            <div class="flex items-center gap-3 mb-5">
                <div class="w-10 h-10 rounded-xl bg-primary-bg flex items-center justify-center"><i class="fas fa-store text-primary"></i></div>
                <h3 class="text-lg font-bold text-gray-900">General</h3>
            </div>
            <div class="space-y-4">
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Site Name</label>
                    <input type="text" name="site_name" value="<?= sanitize(get_site_setting('site_name', 'Urban Glow Salon')) ?>" class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:border-primary outline-none">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Tagline</label>
                    <input type="text" name="site_tagline" value="<?= sanitize(get_site_setting('site_tagline', 'Premium Grooming For All')) ?>" class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:border-primary outline-none">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Email</label>
                    <input type="email" name="site_email" value="<?= sanitize(get_site_setting('site_email')) ?>" class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:border-primary outline-none">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Phone</label>
                    <input type="tel" name="site_phone" value="<?= sanitize(get_site_setting('site_phone')) ?>" class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:border-primary outline-none">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Address</label>
                    <textarea name="site_address" rows="2" class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:border-primary outline-none resize-none"><?= sanitize(get_site_setting('site_address')) ?></textarea>
                </div>
            </div>
        </div>

        <!-- Working Hours & Payments -->
        <div class="space-y-6">
            <!-- Working Hours -->
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
                <div class="flex items-center gap-3 mb-5">
                    <div class="w-10 h-10 rounded-xl bg-amber-50 flex items-center justify-center"><i class="fas fa-clock text-amber-500"></i></div>
                    <h3 class="text-lg font-bold text-gray-900">Working Hours</h3>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Opening Time</label>
                        <input type="time" name="opening_time" value="<?= get_site_setting('opening_time', '09:00') ?>" class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:border-primary outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Closing Time</label>
                        <input type="time" name="closing_time" value="<?= get_site_setting('closing_time', '20:00') ?>" class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:border-primary outline-none">
                    </div>
                </div>
            </div>

            <!-- Payment Methods -->
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
                <div class="flex items-center gap-3 mb-5">
                    <div class="w-10 h-10 rounded-xl bg-green-50 flex items-center justify-center"><i class="fas fa-credit-card text-green-500"></i></div>
                    <h3 class="text-lg font-bold text-gray-900">Payment Methods</h3>
                </div>
                <div class="space-y-3">
                    <label class="flex items-center justify-between p-3 bg-gray-50 rounded-xl cursor-pointer hover:bg-gray-100 transition-colors">
                        <div class="flex items-center gap-3">
                            <i class="fas fa-money-bill-wave text-green-500 w-5"></i>
                            <span class="text-sm font-medium text-gray-700">Cash on Delivery</span>
                        </div>
                        <input type="checkbox" name="payment_cod" class="accent-primary w-4 h-4" <?= get_site_setting('payment_cod', '1') === '1' ? 'checked' : '' ?>>
                    </label>
                    <label class="flex items-center justify-between p-3 bg-gray-50 rounded-xl cursor-pointer hover:bg-gray-100 transition-colors">
                        <div class="flex items-center gap-3">
                            <i class="fas fa-mobile-alt text-green-600 w-5"></i>
                            <span class="text-sm font-medium text-gray-700">eSewa</span>
                        </div>
                        <input type="checkbox" name="payment_esewa" class="accent-primary w-4 h-4" <?= get_site_setting('payment_esewa', '0') === '1' ? 'checked' : '' ?>>
                    </label>
                    <label class="flex items-center justify-between p-3 bg-gray-50 rounded-xl cursor-pointer hover:bg-gray-100 transition-colors">
                        <div class="flex items-center gap-3">
                            <i class="fas fa-wallet text-purple-500 w-5"></i>
                            <span class="text-sm font-medium text-gray-700">Khalti</span>
                        </div>
                        <input type="checkbox" name="payment_khalti" class="accent-primary w-4 h-4" <?= get_site_setting('payment_khalti', '0') === '1' ? 'checked' : '' ?>>
                    </label>
                </div>
            </div>
        </div>
    </div>

    <!-- Save Button -->
    <div class="mt-6 flex justify-end">
        <button type="submit" class="px-8 py-3 bg-primary text-white rounded-xl text-sm font-bold hover:bg-primary-dark transition-all shadow-lg shadow-primary/20">
            <i class="fas fa-save mr-2"></i>Save Settings
        </button>
    </div>
</form>

<?php require_once 'footer.php'; ?>

