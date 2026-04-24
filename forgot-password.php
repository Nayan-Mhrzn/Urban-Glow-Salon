<?php
/**
 * Forgot Password - Urban Glow Salon
 */
$pageTitle = 'Forgot Password';
require_once 'app/Config/config.php';

if (isLoggedIn()) {
    redirect(SITE_URL . '/index.php');
}

$codeSent = isset($_SESSION['reset_code_sent']) && $_SESSION['reset_code_sent'];
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
        
        <!-- Icon -->
        <div class="pt-8 px-6 pb-4 flex justify-center w-full">
            <div class="w-24 h-24 bg-[#EEF0FF] rounded-full flex items-center justify-center">
                <i class="fas <?= $codeSent ? 'fa-shield-halved' : 'fa-lock' ?> text-4xl text-[#4339F2]"></i>
            </div>
        </div>

        <div class="px-10 pb-10 text-center">

            <!-- Flash Message -->
            <?php $flash = getFlash(); ?>
            <?php if ($flash): ?>
            <div class="mb-5 p-4 rounded-[14px] text-sm font-medium border <?= $flash['type'] === 'error' ? 'bg-red-50 text-red-700 border-red-200' : 'bg-green-50 text-green-700 border-green-200' ?>">
                <div class="flex items-start gap-2">
                    <i class="fas <?= $flash['type'] === 'error' ? 'fa-exclamation-circle' : 'fa-check-circle' ?> mt-0.5 flex-shrink-0"></i>
                    <span class="text-left"><?= $flash['message'] ?></span>
                </div>
            </div>
            <?php endif; ?>

            <?php if (!$codeSent): ?>
            <!-- ===== STEP 1: Enter Email ===== -->
            <h1 class="text-[28px] font-extrabold text-[#1a375e] mb-2 tracking-tight">Forgot Password?</h1>
            <p class="text-[13px] text-gray-500 mb-7 px-2">Enter your email and we'll send a 6-digit verification code.</p>

            <form action="<?= SITE_URL ?>/api/forgot-password.php" method="POST" class="text-left">
                <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                
                <div class="mb-6">
                    <label class="block text-[13px] font-[700] text-[#1a375e] mb-2 px-1">Email Address</label>
                    <input type="email" name="email" placeholder="Enter your account email" required
                        class="w-full px-5 py-[14px] bg-[#f0f2f6] border-none rounded-[14px] text-[14px] font-medium text-gray-900 focus:ring-2 focus:ring-[#20456c] outline-none transition-all placeholder-gray-400">
                </div>

                <button type="submit" class="w-full py-4 bg-[#20456c] text-white rounded-[18px] text-[16px] font-[700] tracking-wide hover:bg-[#112842] hover:shadow-lg transition-all shadow-md mb-5">
                    Send Verification Code
                </button>
            </form>

            <?php else: ?>
            <!-- ===== STEP 2: Enter 6-digit Code ===== -->
            <h1 class="text-[28px] font-extrabold text-[#1a375e] mb-2 tracking-tight">Enter Code</h1>
            <p class="text-[13px] text-gray-500 mb-2 px-2">We sent a 6-digit code to</p>
            <p class="text-[14px] font-bold text-[#4339F2] mb-7"><?= htmlspecialchars($_SESSION['reset_email_display'] ?? '') ?></p>

            <?php if (isset($_SESSION['reset_code_fallback'])): ?>
            <!-- Fallback: show code if email failed (local demo) -->
            <div class="mb-6 p-4 bg-amber-50 border border-amber-200 rounded-[14px] text-left">
                <p class="text-xs font-bold text-amber-700 uppercase tracking-wider mb-1">📬 Demo Mode — Email not sent</p>
                <p class="text-sm text-amber-800">Your code is: <strong class="text-xl tracking-widest"><?= $_SESSION['reset_code_fallback'] ?></strong></p>
            </div>
            <?php endif; ?>

            <form action="<?= SITE_URL ?>/api/verify-reset-code.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                
                <!-- 6 Individual Code Boxes -->
                <p class="text-[13px] font-[700] text-[#1a375e] mb-3 text-left px-1">Verification Code</p>
                <div class="flex gap-2 justify-between mb-7" id="codeInputs">
                    <?php foreach(range(1,6) as $i): ?>
                    <input type="text" name="code<?= $i ?>" id="code<?= $i ?>"
                        maxlength="1" inputmode="numeric" pattern="[0-9]"
                        class="w-full aspect-square text-center text-xl font-bold bg-[#f0f2f6] border-2 border-transparent rounded-[12px] text-[#1a375e] focus:ring-0 focus:border-[#4339F2] outline-none transition-all"
                        autocomplete="off" required>
                    <?php endforeach; ?>
                </div>

                <button type="submit" class="w-full py-4 bg-[#4339F2] text-white rounded-[18px] text-[16px] font-[700] tracking-wide hover:bg-[#3229CC] hover:shadow-lg transition-all shadow-md mb-5">
                    Verify Code
                </button>
            </form>

            <!-- Resend -->
            <form action="<?= SITE_URL ?>/api/forgot-password.php" method="POST" class="mb-4">
                <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                <input type="hidden" name="email" value="<?= htmlspecialchars($_SESSION['reset_email_display'] ?? '') ?>">
                <button type="submit" class="text-[13px] text-gray-500 hover:text-[#20456c] transition-colors underline decoration-dotted">
                    Didn't receive it? Resend code
                </button>
            </form>
            <?php endif; ?>

            <a href="<?= SITE_URL ?>/login.php" class="text-[14px] font-medium text-gray-400 hover:text-[#20456c] transition-colors flex items-center justify-center gap-2">
                <i class="fas fa-arrow-left text-xs"></i> Back to Login
            </a>
        </div>
    </div>

    <script>
    // Auto-advance between code boxes
    document.addEventListener('DOMContentLoaded', function () {
        const inputs = document.querySelectorAll('#codeInputs input');
        if (!inputs.length) return;

        inputs.forEach((input, index) => {
            input.addEventListener('input', function () {
                this.value = this.value.replace(/[^0-9]/g, '');
                if (this.value.length === 1 && index < inputs.length - 1) {
                    inputs[index + 1].focus();
                }
            });
            input.addEventListener('keydown', function (e) {
                if (e.key === 'Backspace' && !this.value && index > 0) {
                    inputs[index - 1].focus();
                }
            });
            input.addEventListener('paste', function (e) {
                e.preventDefault();
                const pasted = (e.clipboardData || window.clipboardData).getData('text').replace(/[^0-9]/g, '');
                [...pasted].forEach((char, i) => {
                    if (inputs[index + i]) inputs[index + i].value = char;
                });
                const nextEmpty = [...inputs].findIndex(inp => !inp.value);
                if (nextEmpty !== -1) inputs[nextEmpty].focus();
            });
        });

        // Focus first input
        if (inputs[0]) inputs[0].focus();
    });
    </script>
</body>
</html>
