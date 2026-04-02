/**
 * Urban Glow Salon — Main JavaScript
 */

document.addEventListener('DOMContentLoaded', function () {

    // ===== Mobile Navigation Toggle =====
    const mobileToggle = document.getElementById('mobileToggle');
    const mobileMenu = document.getElementById('mobileMenu');

    if (mobileToggle && mobileMenu) {
        mobileToggle.addEventListener('click', function () {
            mobileMenu.classList.toggle('hidden');
            const icon = this.querySelector('i');
            icon.classList.toggle('fa-bars');
            icon.classList.toggle('fa-times');
        });
    }

    // ===== User Dropdown Toggle =====
    const userTrigger = document.getElementById('userTrigger');
    const userDropdown = document.getElementById('userDropdown');
    const dropdownMenu = document.getElementById('dropdownMenu');

    if (userTrigger && userDropdown && dropdownMenu) {
        userTrigger.addEventListener('click', function (e) {
            e.stopPropagation();
            const isOpen = userDropdown.classList.toggle('dropdown-open');
            if (isOpen) {
                dropdownMenu.style.opacity = '1';
                dropdownMenu.style.visibility = 'visible';
                dropdownMenu.style.transform = 'translateY(0)';
            } else {
                dropdownMenu.style.opacity = '0';
                dropdownMenu.style.visibility = 'hidden';
                dropdownMenu.style.transform = 'translateY(-8px)';
            }
        });

        document.addEventListener('click', function (e) {
            if (!userDropdown.contains(e.target)) {
                userDropdown.classList.remove('dropdown-open');
                dropdownMenu.style.opacity = '0';
                dropdownMenu.style.visibility = 'hidden';
                dropdownMenu.style.transform = 'translateY(-8px)';
            }
        });
    }

    // ===== Flash Message Auto-dismiss =====
    const flashMessage = document.getElementById('flashMessage');
    if (flashMessage) {
        setTimeout(function () {
            flashMessage.style.opacity = '0';
            flashMessage.style.transform = 'translateY(-10px)';
            flashMessage.style.transition = 'all 0.3s ease';
            setTimeout(function () { flashMessage.remove(); }, 300);
        }, 4000);
    }

    // ===== Navbar Scroll Shadow =====
    const navbar = document.getElementById('mainNav');
    if (navbar) {
        window.addEventListener('scroll', function () {
            if (window.scrollY > 10) {
                navbar.classList.add('shadow-md');
            } else {
                navbar.classList.remove('shadow-md');
            }
        });
    }

    // ===== Password Toggle =====
    document.querySelectorAll('.password-toggle').forEach(function (toggle) {
        toggle.addEventListener('click', function () {
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

    // ===== Scroll Reveal Animations =====
    const observerOptions = { threshold: 0.1, rootMargin: '0px 0px -50px 0px' };
    const observer = new IntersectionObserver(function (entries) {
        entries.forEach(function (entry) {
            if (entry.isIntersecting) {
                entry.target.classList.remove('opacity-0', 'translate-y-5');
                entry.target.classList.add('opacity-100', 'translate-y-0');
                observer.unobserve(entry.target);
            }
        });
    }, observerOptions);

    document.querySelectorAll('[data-animate]').forEach(function (el) {
        el.classList.add('opacity-0', 'translate-y-5', 'transition-all', 'duration-500', 'ease-out');
        observer.observe(el);
    });
});

// ===== Cart Functions =====
function addToCart(productId) {
    fetch(siteUrl + '/api/add-to-cart.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ product_id: productId, quantity: 1 })
    })
        .then(function (res) { return res.json(); })
        .then(function (data) {
            if (data.success) {
                updateCartBadge(data.cartCount);
                showToast('Product added to cart!', 'success');
            } else {
                showToast(data.message || 'Failed to add to cart', 'error');
            }
        })
        .catch(function () {
            showToast('Something went wrong', 'error');
        });
}

function updateCartBadge(count) {
    let badge = document.getElementById('cartBadge');
    const cartIcon = document.getElementById('cartIcon');
    if (count > 0) {
        if (!badge && cartIcon) {
            badge = document.createElement('span');
            badge.className = 'absolute top-0.5 right-0.5 w-[18px] h-[18px] bg-red-500 text-white text-[10px] font-bold rounded-full flex items-center justify-center';
            badge.id = 'cartBadge';
            cartIcon.appendChild(badge);
        }
        if (badge) badge.textContent = count;
    } else if (badge) {
        badge.remove();
    }
}

function showToast(message, type) {
    const existing = document.querySelector('.toast-notification');
    if (existing) existing.remove();

    const toast = document.createElement('div');
    toast.className = 'toast-notification fixed top-20 right-5 px-6 py-3 rounded-xl text-white text-sm font-medium z-[9999] flex items-center gap-2 animate-slide-down shadow-lg';
    toast.classList.add(type === 'success' ? 'bg-green-500' : 'bg-red-500');
    toast.innerHTML = '<i class="fas fa-' + (type === 'success' ? 'check-circle' : 'exclamation-circle') + '"></i> ' + message;
    document.body.appendChild(toast);

    setTimeout(function () {
        toast.style.opacity = '0';
        toast.style.transform = 'translateX(20px)';
        toast.style.transition = 'all 0.3s ease';
        setTimeout(function () { toast.remove(); }, 300);
    }, 3000);
}
