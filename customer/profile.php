<?php
/**
 * Profile Settings Page - Urban Glow Salon
 */
$pageTitle = 'Profile Settings';
require_once '../config/config.php';

requireLogin();

$userId = $_SESSION['user_id'];

// Get fresh user data
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

if (!$user) {
    logoutUser();
    redirect(SITE_URL . '/login.php');
}

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        setFlash('error', 'Invalid request. Please try again.');
        redirect(SITE_URL . '/customer/profile.php');
    }

    $action = $_POST['action'] ?? '';

    // Update Profile Info
    if ($action === 'update_profile') {
        $fullName = sanitize($_POST['full_name'] ?? '');
        $phone = sanitize($_POST['phone'] ?? '');

        if (empty($fullName)) {
            setFlash('error', 'Full Name is required.');
        } else {
            $stmt = $pdo->prepare("UPDATE users SET full_name = ?, phone = ? WHERE id = ?");
            if ($stmt->execute([$fullName, $phone, $userId])) {
                $_SESSION['user_name'] = $fullName; // Update session
                setFlash('success', 'Profile updated successfully.');
            } else {
                setFlash('error', 'Database error occurred.');
            }
        }
        redirect(SITE_URL . '/customer/profile.php');
    }

    // Change Password
    if ($action === 'change_password') {
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
            setFlash('error', 'All password fields are required.');
        } elseif (!password_verify($currentPassword, $user['password'])) {
            setFlash('error', 'Current password is incorrect.');
        } elseif ($newPassword !== $confirmPassword) {
            setFlash('error', 'New passwords do not match.');
        } elseif (strlen($newPassword) < 6) {
            setFlash('error', 'New password must be at least 6 characters.');
        } else {
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            if ($stmt->execute([$hashedPassword, $userId])) {
                setFlash('success', 'Password changed successfully.');
            } else {
                setFlash('error', 'Database error occurred.');
            }
        }
        redirect(SITE_URL . '/customer/profile.php');
    }
}

require_once '../partials/header.php';
?>

<div class="bg-gray-50 min-h-[calc(100vh-80px)] py-12">
    <div class="max-w-3xl mx-auto px-6">
        
        <!-- Back Button -->
        <a href="<?= SITE_URL ?>/customer/dashboard.php" class="inline-flex items-center text-sm font-semibold text-gray-500 hover:text-primary mb-6 transition-colors group">
            <i class="fas fa-arrow-left mr-2 group-hover:-translate-x-1 transition-transform"></i> Back to Dashboard
        </a>

        <div class="mb-8">
            <h1 class="text-3xl font-extrabold text-gray-900 tracking-tight">Account Settings</h1>
            <p class="text-gray-500 mt-2 font-medium">Update your personal information and securely change your password.</p>
        </div>

        <div class="space-y-8">
            <!-- Personal Info Card -->
            <div class="bg-white rounded-2xl shadow-[0_2px_16px_rgba(67,57,242,0.03)] border border-gray-100 p-8 relative overflow-hidden">
                <div class="absolute top-0 right-0 w-32 h-32 bg-primary/5 rounded-bl-full -z-0"></div>
                
                <h2 class="text-lg font-extrabold text-gray-900 mb-6 flex items-center gap-2 relative z-10">
                    <i class="fas fa-user text-primary"></i> Personal Information
                </h2>

                <form method="POST" class="relative z-10">
                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                    <input type="hidden" name="action" value="update_profile">

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-2">Username</label>
                            <input type="text" value="<?= sanitize($user['username']) ?>" disabled class="w-full px-4 py-3 bg-gray-50 border-2 border-gray-100 rounded-xl text-sm text-gray-400 font-medium cursor-not-allowed">
                            <p class="text-xs text-gray-400 mt-1.5"><i class="fas fa-info-circle"></i> Username cannot be changed</p>
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-2">Email Address</label>
                            <input type="email" value="<?= sanitize($user['email']) ?>" disabled class="w-full px-4 py-3 bg-gray-50 border-2 border-gray-100 rounded-xl text-sm text-gray-400 font-medium cursor-not-allowed">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-2">Full Name</label>
                            <input type="text" name="full_name" value="<?= sanitize($user['full_name']) ?>" required class="w-full px-4 py-3 bg-white border-2 border-gray-200 rounded-xl text-sm font-medium focus:border-primary outline-none transition-all shadow-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-2">Phone Number</label>
                            <input type="tel" name="phone" value="<?= sanitize($user['phone'] ?? '') ?>" placeholder="e.g. 9812345678" class="w-full px-4 py-3 bg-white border-2 border-gray-200 rounded-xl text-sm font-medium focus:border-primary outline-none transition-all shadow-sm">
                        </div>
                    </div>

                    <div class="flex justify-end border-t border-gray-100 pt-6">
                        <button type="submit" class="bg-gray-900 hover:bg-black text-white px-8 py-3 rounded-xl text-sm font-bold transition-all hover:shadow-lg flex items-center gap-2">
                            Save Changes
                        </button>
                    </div>
                </form>
            </div>

            <!-- Password Change Card -->
            <div class="bg-white rounded-2xl shadow-[0_2px_16px_rgba(67,57,242,0.03)] border border-gray-100 p-8">
                <h2 class="text-lg font-extrabold text-gray-900 mb-6 flex items-center gap-2">
                    <i class="fas fa-lock text-primary"></i> Change Password
                </h2>

                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                    <input type="hidden" name="action" value="change_password">

                    <div class="mb-5">
                        <label class="block text-sm font-bold text-gray-700 mb-2">Current Password</label>
                        <input type="password" name="current_password" required placeholder="Enter current password" class="w-full px-4 py-3 bg-white border-2 border-gray-200 rounded-xl text-sm font-medium focus:border-primary outline-none transition-all shadow-sm">
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-2">New Password</label>
                            <input type="password" name="new_password" required placeholder="At least 6 characters" minlength="6" class="w-full px-4 py-3 bg-white border-2 border-gray-200 rounded-xl text-sm font-medium focus:border-primary outline-none transition-all shadow-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-2">Confirm New Password</label>
                            <input type="password" name="confirm_password" required placeholder="Repeat new password" minlength="6" class="w-full px-4 py-3 bg-white border-2 border-gray-200 rounded-xl text-sm font-medium focus:border-primary outline-none transition-all shadow-sm">
                        </div>
                    </div>

                    <div class="flex justify-end border-t border-gray-100 pt-6">
                        <button type="submit" class="bg-primary hover:bg-primary-dark text-white px-8 py-3 rounded-xl text-sm font-bold transition-all hover:shadow-[0_8px_20px_rgba(67,57,242,0.25)] flex items-center gap-2">
                            Update Password
                        </button>
                    </div>
                </form>
            </div>
            
        </div>
    </div>
</div>

<?php require_once '../partials/footer.php'; ?>
