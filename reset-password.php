<?php
/**
 * Reset Password - Urban Glow Salon
 */
$pageTitle = 'Set New Password';
require_once 'app/Config/config.php';

// Redirect if already logged in
if (isLoggedIn()) {
    redirect(SITE_URL . '/index.php');
}

$token = $_GET['token'] ?? '';
if (empty($token)) {
    setFlash('error', 'Invalid reset link.');
    redirect(SITE_URL . '/login.php');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?> | <?= SITE_NAME ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: { DEFAULT: '#4339F2', light: '#6C63FF', dark: '#3229CC', bg: '#EEF0FF' }
                    },
                    fontFamily: { sans: ['Inter', 'system-ui', 'sans-serif'] }
                }
            }
        }
    </script>
    <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/custom.css">
</head>
<body class="font-sans h-[100vh] bg-[#b9d6fa] relative overflow-hidden flex items-center justify-center">
    
    <!-- Background Decorative Circles -->
    <div class="absolute w-80 h-80 rounded-full bg-[#9bc5fb] opacity-60 -top-24 -left-24"></div>
    <div class="absolute w-36 h-36 rounded-full bg-[#dfccde] opacity-80 top-[45%] left-[8%]"></div>
    <div class="absolute w-52 h-52 rounded-full bg-[#9bc5fb] opacity-60 -bottom-10 left-[15%]"></div>
    <div class="absolute w-32 h-32 rounded-full bg-[#dfccde] opacity-80 top-[20%] right-[12%]"></div>
    <div class="absolute w-44 h-44 rounded-full bg-[#9bc5fb] opacity-60 top-[55%] -right-16"></div>
    <div class="absolute w-64 h-64 rounded-full bg-[#dfccde] opacity-80 -bottom-24 -right-10"></div>

    <!-- Main Card -->
    <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[calc(100%-2rem)] sm:w-full max-w-[420px] bg-white rounded-[32px] shadow-[0_30px_60px_rgba(0,0,0,0.1)] overflow-hidden z-10 border border-white animate-scale-in">
        
        <!-- Illustration -->
        <div class="pt-8 px-6 pb-4 flex justify-center w-full">
            <div class="w-24 h-24 bg-[#EEF0FF] rounded-full flex items-center justify-center mb-2">
                <i class="fas fa-key text-4xl text-[#4339F2]"></i>
            </div>
        </div>

        <!-- Form Body -->
        <div class="px-10 pb-10 text-center">
            <h1 class="text-[30px] font-extrabold text-[#1a375e] mb-2 tracking-tight">New Password</h1>
            <p class="text-[13px] text-gray-500 mb-8 px-2">Please enter your new password below securely.</p>

            <!-- Flash Message -->
            <?php $flash = getFlash(); ?>
            <?php if ($flash): ?>
            <div class="mb-6 p-4 rounded-[14px] text-sm font-medium border
                <?= $flash['type'] === 'error' ? 'bg-red-50 text-red-700 border-red-200' : 'bg-green-50 text-green-700 border-green-200' ?>">
                <div class="flex items-start">
                    <div class="flex-shrink-0 mt-0.5">
                        <?= $flash['type'] === 'error' ? '<i class="fas fa-exclamation-circle"></i>' : '<i class="fas fa-check-circle"></i>' ?>
                    </div>
                    <div class="ml-3 text-left">
                        <?= $flash['message'] ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Form -->
            <form action="<?= SITE_URL ?>/api/reset-password.php" method="POST" class="text-left">
                <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
                
                <div class="mb-5">
                    <label class="block text-[13px] font-[700] text-[#1a375e] mb-2 px-1">New Password</label>
                    <div class="relative">
                        <input type="password" name="password" placeholder="Create a new password" required minlength="6"
                            class="w-full px-5 py-[14px] bg-[#f0f2f6] border-none rounded-[14px] text-[14px] font-medium text-gray-900 focus:ring-2 focus:ring-[#20456c] outline-none transition-all placeholder-gray-400">
                        <span class="password-toggle absolute right-4 top-1/2 -translate-y-1/2 text-gray-500 hover:text-[#1a375e] cursor-pointer transition-colors p-2">
                            <i class="fas fa-eye-slash text-[14px]"></i>
                        </span>
                    </div>
                </div>

                <div class="mb-8">
                    <label class="block text-[13px] font-[700] text-[#1a375e] mb-2 px-1">Confirm Password</label>
                    <div class="relative">
                        <input type="password" name="confirm_password" placeholder="Confirm new password" required minlength="6"
                            class="w-full px-5 py-[14px] bg-[#f0f2f6] border-none rounded-[14px] text-[14px] font-medium text-gray-900 focus:ring-2 focus:ring-[#20456c] outline-none transition-all placeholder-gray-400">
                        <span class="password-toggle absolute right-4 top-1/2 -translate-y-1/2 text-gray-500 hover:text-[#1a375e] cursor-pointer transition-colors p-2">
                            <i class="fas fa-eye-slash text-[14px]"></i>
                        </span>
                    </div>
                </div>

                <button type="submit" class="w-full py-4 bg-[#20456c] text-white rounded-[18px] text-[16px] font-[700] tracking-wide hover:bg-[#112842] hover:shadow-lg transition-all shadow-md mb-6">
                    Update Password
                </button>
            </form>

            <a href="<?= SITE_URL ?>/login.php" class="text-[14px] font-medium text-gray-500 hover:text-[#20456c] transition-colors flex items-center justify-center gap-2">
                <i class="fas fa-arrow-left"></i> Cancel
            </a>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Password toggles
        document.querySelectorAll('.password-toggle').forEach(function(toggle) {
            toggle.addEventListener('click', function() {
                const input = this.previousElementSibling;
                const icon = this.querySelector('i');
                if (input.type === 'password') {
                    input.type = 'text';
                    icon.classList.replace('fa-eye-slash', 'fa-eye');
                } else {
                    input.type = 'password';
                    icon.classList.replace('fa-eye', 'fa-eye-slash');
                }
            });
        });
    });
    </script>
</body>
</html>
