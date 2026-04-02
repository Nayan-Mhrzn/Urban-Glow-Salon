<?php
/**
 * Staff - Profile Management
 */
$pageTitle = 'My Profile';
require_once dirname(__DIR__) . '/config/config.php';
requireStaff();

$user = getCurrentUser($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = trim($_POST['full_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($fullName)) {
        setFlash('error', 'Full name is required.');
    } else {
        if (!empty($password)) {
            // Update with password
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET full_name = ?, phone = ?, password = ? WHERE id = ?");
            $stmt->execute([$fullName, $phone, $hashed, $_SESSION['user_id']]);
        } else {
            // Update without password
            $stmt = $pdo->prepare("UPDATE users SET full_name = ?, phone = ? WHERE id = ?");
            $stmt->execute([$fullName, $phone, $_SESSION['user_id']]);
        }
        
        // Update session name if changed
        $_SESSION['user_name'] = $fullName;
        
        setFlash('success', 'Profile updated successfully.');
        redirect(SITE_URL . '/staff/profile.php');
    }
}

require_once 'header.php';
?>

<div class="max-w-2xl">
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-8">
        <h2 class="text-xl font-bold text-gray-900 mb-6">Account Settings</h2>
        
        <form method="POST" class="space-y-5">
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1.5">Username (Read Only)</label>
                <input type="text" value="<?= sanitize($user['username']) ?>" readonly
                    class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm text-gray-500 outline-none cursor-not-allowed">
            </div>
            
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1.5">Email (Read Only)</label>
                <input type="email" value="<?= sanitize($user['email']) ?>" readonly
                    class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm text-gray-500 outline-none cursor-not-allowed">
            </div>
            
            <hr class="border-gray-100 my-6">

            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1.5">Full Name</label>
                <input type="text" name="full_name" value="<?= sanitize($user['full_name']) ?>" required
                    class="w-full px-4 py-3 bg-white border border-gray-200 rounded-xl text-sm focus:border-primary focus:ring-2 focus:ring-primary/20 outline-none transition-all">
            </div>
            
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1.5">Phone Number</label>
                <input type="tel" name="phone" value="<?= sanitize($user['phone']) ?>"
                    class="w-full px-4 py-3 bg-white border border-gray-200 rounded-xl text-sm focus:border-primary focus:ring-2 focus:ring-primary/20 outline-none transition-all">
            </div>
            
            <div class="pt-4">
                <label class="block text-sm font-semibold text-gray-700 mb-1.5">New Password</label>
                <input type="password" name="password" placeholder="Leave blank to keep current password"
                    class="w-full px-4 py-3 bg-white border border-gray-200 rounded-xl text-sm focus:border-primary focus:ring-2 focus:ring-primary/20 outline-none transition-all">
                <p class="text-xs text-gray-400 mt-2">Only fill this if you want to change your password.</p>
            </div>
            
            <div class="pt-6">
                <button type="submit" class="w-full py-3.5 bg-primary text-white rounded-xl text-sm font-bold tracking-wide hover:bg-primary-dark hover:shadow-lg transition-all shadow-md shadow-primary/20">
                    Save Changes
                </button>
            </div>
        </form>
    </div>
</div>

<?php require_once 'footer.php'; ?>

