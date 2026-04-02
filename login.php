<?php
/**
 * Login / Sign Up Page - Urban Glow Salon
 */
$pageTitle = 'Login';
require_once 'config/config.php';

// Redirect if already logged in
if (isLoggedIn()) {
    redirect(SITE_URL . '/index.php');
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
<body class="font-sans h-[100vh]  bg-[#b9d6fa] relative overflow-hidden flex items-center justify-center">
    
    <!-- Background Decorative Circles matching CutLab precisely -->
    <!-- Top Left Blue -->
    <div class="absolute w-80 h-80 rounded-full bg-[#9bc5fb] opacity-60 -top-24 -left-24"></div>
    <!-- Mid Left Pink -->
    <div class="absolute w-36 h-36 rounded-full bg-[#dfccde] opacity-80 top-[45%] left-[8%]"></div>
    <!-- Bottom Left Blue -->
    <div class="absolute w-52 h-52 rounded-full bg-[#9bc5fb] opacity-60 -bottom-10 left-[15%]"></div>
    
    <!-- Top Right Pink -->
    <div class="absolute w-32 h-32 rounded-full bg-[#dfccde] opacity-80 top-[20%] right-[12%]"></div>
    <!-- Mid Right Blue -->
    <div class="absolute w-44 h-44 rounded-full bg-[#9bc5fb] opacity-60 top-[55%] -right-16"></div>
    <!-- Bottom Right Pink -->
    <div class="absolute w-64 h-64 rounded-full bg-[#dfccde] opacity-80 -bottom-24 -right-10"></div>

    <!-- Login Card -->
    <!-- Exact Center Absolute Positioning -->
    <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[calc(100%-2rem)] sm:w-full max-w-[420px] bg-white rounded-[32px] shadow-[0_30px_60px_rgba(0,0,0,0.1)] overflow-hidden z-10 border border-white animate-scale-in">
        
        <!-- Illustration -->
        <div class="pt-8 px-6 pb-4 flex justify-center w-full">
            <img src="<?= SITE_URL ?>/uploads/2/barbershop%20team-pana.svg" alt="Urban Glow Salon Team" class="w-full max-w-[320px] h-auto object-contain pointer-events-none animate-gentle-bounce">
        </div>

        <!-- Form Body -->
        <div class="px-10 pb-10 text-center">
            <p class="text-[10px] uppercase font-[800] tracking-[2.5px] text-[#6d7a8d] mb-2">The Urban Glow</p>
            <h1 class="text-[30px] font-extrabold text-[#1a375e] mb-6 tracking-tight">Welcome Back!</h1>

            <!-- Tabs -->
            <div class="flex justify-center mb-8">
                <div class="flex bg-[#f3f4f7] rounded-full p-1.5 w-[200px]">
                    <button class="login-tab active flex-1 py-1.5 px-4 rounded-full text-[13px] font-[700] transition-all text-white bg-[#20456c]" data-tab="login" id="loginTabBtn">Login</button>
                    <button class="login-tab flex-1 py-1.5 px-4 rounded-full text-[13px] font-[600] text-gray-500 transition-all hover:text-gray-900" data-tab="signup" id="signupTabBtn">Sign up</button>
                </div>
            </div>

            <!-- Flash Message -->
            <?php $flash = getFlash(); ?>
            <?php if ($flash): ?>
            <div class="mb-4 p-3 rounded-lg text-sm font-medium
                <?= $flash['type'] === 'error' ? 'bg-red-100 text-red-700' : 'bg-green-100 text-green-700' ?>">
                <?= $flash['message'] ?>
            </div>
            <?php endif; ?>

            <!-- Login Form -->
            <form id="loginForm" action="<?= SITE_URL ?>/api/login.php" method="POST" class="text-left">
                <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                
                <div class="mb-5">
                    <label class="block text-[13px] font-[700] text-[#1a375e] mb-2 px-1">Username</label>
                    <input type="text" name="username" placeholder="Your username" required
                        class="w-full px-5 py-[14px] bg-[#f0f2f6] border-none rounded-[14px] text-[14px] font-medium text-gray-900 focus:ring-2 focus:ring-[#20456c] outline-none transition-all placeholder-gray-400">
                </div>

                <div class="mb-5">
                    <label class="block text-[13px] font-[700] text-[#1a375e] mb-2 px-1">Password</label>
                    <div class="relative">
                        <input type="password" name="password" placeholder="Your password" required
                            class="w-full px-5 py-[14px] bg-[#f0f2f6] border-none rounded-[14px] text-[14px] font-medium text-gray-900 focus:ring-2 focus:ring-[#20456c] outline-none transition-all placeholder-gray-400">
                        <span class="password-toggle absolute right-4 top-1/2 -translate-y-1/2 text-gray-500 hover:text-[#1a375e] cursor-pointer transition-colors p-2">
                            <i class="fas fa-eye-slash text-[14px]"></i>
                        </span>
                    </div>
                </div>

                <div class="mb-8 flex justify-end">
                    <a href="#" class="text-[12px] font-bold text-[#20456c] hover:text-[#112A46] transition-colors pr-1">Forgot password?</a>
                </div>

                <button type="submit" class="w-full py-4 bg-[#20456c] text-white rounded-[18px] text-[16px] font-[700] tracking-wide hover:bg-[#112842] hover:shadow-lg transition-all shadow-md">
                    Login
                </button>
            </form>

            <!-- Signup Form (hidden by default) -->
            <form id="signupForm" action="<?= SITE_URL ?>/api/register.php" method="POST" class="text-left hidden">
                <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                
                <!-- Step 1 -->
                <div class="step step-1">
                    <div class="mb-4">
                        <label class="block text-[13px] font-[700] text-[#1a375e] mb-2 px-1">Full Name</label>
                        <input type="text" name="full_name" placeholder="Your full name" required
                            class="w-full px-5 py-[14px] bg-[#f0f2f6] border-none rounded-[14px] text-[14px] font-medium text-gray-900 focus:ring-2 focus:ring-[#20456c] outline-none transition-all placeholder-gray-400">
                    </div>

                    <div class="mb-4">
                        <label class="block text-[13px] font-[700] text-[#1a375e] mb-2 px-1">Username</label>
                        <input type="text" name="username" placeholder="Choose a username" required
                            class="w-full px-5 py-[14px] bg-[#f0f2f6] border-none rounded-[14px] text-[14px] font-medium text-gray-900 focus:ring-2 focus:ring-[#20456c] outline-none transition-all placeholder-gray-400">
                    </div>
                </div>

                <!-- Step 2 -->
                <div class="step step-2 hidden">
                    <div class="mb-8">
                        <label class="block text-[13px] font-[700] text-[#1a375e] mb-2 px-1">Email</label>
                        <input type="email" name="email" placeholder="your@email.com" required
                            class="w-full px-5 py-[14px] bg-[#f0f2f6] border-none rounded-[14px] text-[14px] font-medium text-gray-900 focus:ring-2 focus:ring-[#20456c] outline-none transition-all placeholder-gray-400">
                    </div>
                </div>

                <!-- Step 3 -->
                <div class="step step-3 hidden">
                    <div class="mb-4">
                        <label class="block text-[13px] font-[700] text-[#1a375e] mb-2 px-1">Password</label>
                        <div class="relative">
                            <input type="password" name="password" placeholder="Create a password" required
                                class="w-full px-5 py-[14px] bg-[#f0f2f6] border-none rounded-[14px] text-[14px] font-medium text-gray-900 focus:ring-2 focus:ring-[#20456c] outline-none transition-all placeholder-gray-400">
                            <span class="password-toggle absolute right-4 top-1/2 -translate-y-1/2 text-gray-500 hover:text-[#1a375e] cursor-pointer transition-colors p-2">
                                <i class="fas fa-eye-slash text-[14px]"></i>
                            </span>
                        </div>
                    </div>

                    <div class="mb-8">
                        <label class="block text-[13px] font-[700] text-[#1a375e] mb-2 px-1">Confirm Password</label>
                        <div class="relative">
                            <input type="password" name="confirm_password" placeholder="Confirm your password" required
                                class="w-full px-5 py-[14px] bg-[#f0f2f6] border-none rounded-[14px] text-[14px] font-medium text-gray-900 focus:ring-2 focus:ring-[#20456c] outline-none transition-all placeholder-gray-400">
                            <span class="password-toggle absolute right-4 top-1/2 -translate-y-1/2 text-gray-500 hover:text-[#1a375e] cursor-pointer transition-colors p-2">
                                <i class="fas fa-eye-slash text-[14px]"></i>
                            </span>
                        </div>
                    </div>
                </div>

                <div class="flex gap-3 justify-between mt-2">
                    <button type="button" id="prevStepBtn" class="flex-1 py-4 bg-[#f0f2f6] text-[#1a375e] rounded-[18px] text-[15px] font-[700] tracking-wide hover:bg-[#e2e8f0] transition-all hidden">
                        Back
                    </button>
                    <button type="button" id="nextStepBtn" class="flex-1 py-4 bg-[#20456c] text-white rounded-[18px] text-[15px] font-[700] tracking-wide hover:bg-[#112842] hover:shadow-lg transition-all shadow-md">
                        Next
                    </button>
                    <button type="submit" id="submitSignupBtn" class="flex-1 py-4 bg-[#20456c] text-white rounded-[18px] text-[15px] font-[700] tracking-wide hover:bg-[#112842] hover:shadow-lg transition-all shadow-md hidden">
                        Sign Up
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const loginTab = document.getElementById('loginTabBtn');
        const signupTab = document.getElementById('signupTabBtn');
        const loginForm = document.getElementById('loginForm');
        const signupForm = document.getElementById('signupForm');

        // Multi-Step Form Logic
        const steps = document.querySelectorAll('.step');
        const nextBtn = document.getElementById('nextStepBtn');
        const prevBtn = document.getElementById('prevStepBtn');
        const submitBtn = document.getElementById('submitSignupBtn');
        let currentStep = 0;

        function updateSteps() {
            steps.forEach((step, index) => {
                if (index === currentStep) {
                    step.classList.remove('hidden');
                } else {
                    step.classList.add('hidden');
                }
            });

            if (currentStep === 0) {
                prevBtn.classList.add('hidden');
            } else {
                prevBtn.classList.remove('hidden');
            }

            if (currentStep === steps.length - 1) {
                nextBtn.classList.add('hidden');
                submitBtn.classList.remove('hidden');
            } else {
                nextBtn.classList.remove('hidden');
                submitBtn.classList.add('hidden');
            }
        }

        nextBtn.addEventListener('click', () => {
            // Validate current step inputs before moving forward
            const currentInputs = steps[currentStep].querySelectorAll('input[required]');
            let valid = true;
            currentInputs.forEach(input => {
                // Clear custom validity first to allow native checks
                input.setCustomValidity('');

                // Custom @gmail.com validation
                if (input.type === 'email' && input.value) {
                    if (!input.value.toLowerCase().endsWith('@gmail.com')) {
                        input.setCustomValidity('Please enter a valid @gmail.com address.');
                    }
                }

                if (!input.checkValidity()) {
                    input.reportValidity();
                    valid = false;
                }
            });

            if (valid) {
                currentStep++;
                updateSteps();
            }
        });

        // Clear custom validity as the user types
        document.querySelectorAll('.step input').forEach(input => {
            input.addEventListener('input', function() {
                this.setCustomValidity('');
            });
        });

        prevBtn.addEventListener('click', () => {
            currentStep--;
            updateSteps();
        });

        loginTab.addEventListener('click', function() {
            loginTab.classList.add('active');
            loginTab.classList.remove('text-gray-500');
            signupTab.classList.remove('active');
            signupTab.classList.add('text-gray-500');
            loginForm.classList.remove('hidden');
            signupForm.classList.add('hidden');
        });

        signupTab.addEventListener('click', function() {
            signupTab.classList.add('active');
            signupTab.classList.remove('text-gray-500');
            loginTab.classList.remove('active');
            loginTab.classList.add('text-gray-500');
            signupForm.classList.remove('hidden');
            loginForm.classList.add('hidden');
            
            // Reset to first step when tab is clicked
            currentStep = 0;
            updateSteps();
        });

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

    <style>
        .login-tab.active { background: #20456c; color: white; }
    </style>
</body>
</html>
