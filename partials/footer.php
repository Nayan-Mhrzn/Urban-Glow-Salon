    </main>

    <!-- Footer -->
    <footer class="bg-gray-900 text-gray-400 pt-16 mt-16">
        <div class="max-w-7xl mx-auto px-6">
            <!-- Footer Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-10">
                <!-- Brand Column -->
                <div>
                    <a href="<?= SITE_URL ?>/index.php" class="text-2xl font-extrabold inline-block mb-4">
                        <span class="text-white">Urban</span><span class="text-primary-light">Glow</span>
                    </a>
                    <p class="text-sm leading-relaxed mb-5"><?= sanitize(get_site_setting('site_tagline', 'Experience the perfect blend of traditional grooming and modern style. We create confidence.')) ?></p>
                    <div class="flex gap-2">
                        <a href="#" class="w-9 h-9 flex items-center justify-center rounded-full bg-white/10 text-gray-400 text-sm hover:bg-primary hover:text-white hover:-translate-y-0.5 transition-all" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" class="w-9 h-9 flex items-center justify-center rounded-full bg-white/10 text-gray-400 text-sm hover:bg-primary hover:text-white hover:-translate-y-0.5 transition-all" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
                        <a href="#" class="w-9 h-9 flex items-center justify-center rounded-full bg-white/10 text-gray-400 text-sm hover:bg-primary hover:text-white hover:-translate-y-0.5 transition-all" aria-label="Twitter"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="w-9 h-9 flex items-center justify-center rounded-full bg-white/10 text-gray-400 text-sm hover:bg-primary hover:text-white hover:-translate-y-0.5 transition-all" aria-label="YouTube"><i class="fab fa-youtube"></i></a>
                    </div>
                </div>

                <!-- Quick Links -->
                <div>
                    <h4 class="text-white text-base font-semibold mb-5">Quick Links</h4>
                    <ul class="space-y-2">
                        <li><a href="<?= SITE_URL ?>/index.php" class="text-sm text-gray-400 hover:text-white hover:translate-x-1 transition-all inline-block">Home</a></li>
                        <li><a href="<?= SITE_URL ?>/shop/products.php" class="text-sm text-gray-400 hover:text-white hover:translate-x-1 transition-all inline-block">Shop</a></li>
                        <li><a href="<?= SITE_URL ?>/booking/services.php" class="text-sm text-gray-400 hover:text-white hover:translate-x-1 transition-all inline-block">Services</a></li>
                        <li><a href="<?= SITE_URL ?>/customer/reviews.php" class="text-sm text-gray-400 hover:text-white hover:translate-x-1 transition-all inline-block">Reviews</a></li>
                        <li><a href="<?= SITE_URL ?>/booking/book-appointment.php" class="text-sm text-gray-400 hover:text-white hover:translate-x-1 transition-all inline-block">Book Appointment</a></li>
                    </ul>
                </div>

                <!-- Services -->
                <div>
                    <h4 class="text-white text-base font-semibold mb-5">Our Services</h4>
                    <ul class="space-y-2">
                        <li><a href="<?= SITE_URL ?>/booking/services.php?category=Hair" class="text-sm text-gray-400 hover:text-white hover:translate-x-1 transition-all inline-block">Hair Styling</a></li>
                        <li><a href="<?= SITE_URL ?>/booking/services.php?category=Skin" class="text-sm text-gray-400 hover:text-white hover:translate-x-1 transition-all inline-block">Skin Care</a></li>
                        <li><a href="<?= SITE_URL ?>/booking/services.php?category=Body" class="text-sm text-gray-400 hover:text-white hover:translate-x-1 transition-all inline-block">Body Massage</a></li>
                        <li><a href="<?= SITE_URL ?>/booking/services.php?category=Makeup" class="text-sm text-gray-400 hover:text-white hover:translate-x-1 transition-all inline-block">Bridal Makeup</a></li>
                        <li><a href="<?= SITE_URL ?>/booking/services.php?category=Nails" class="text-sm text-gray-400 hover:text-white hover:translate-x-1 transition-all inline-block">Nail Services</a></li>
                    </ul>
                </div>

                <!-- Contact Info -->
                <div>
                    <h4 class="text-white text-base font-semibold mb-5">Contact Us</h4>
                    <ul class="space-y-3">
                        <li class="flex items-start gap-3 text-sm"><i class="fas fa-map-marker-alt text-primary-light mt-0.5 w-4 flex-shrink-0"></i> <span><?= sanitize(get_site_setting('site_address', '123 Main Street, City')) ?></span></li>
                        <li class="flex items-start gap-3 text-sm"><i class="fas fa-phone text-primary-light mt-0.5 w-4 flex-shrink-0"></i> <span><?= sanitize(get_site_setting('site_phone', '+977 98XXXXXXXX')) ?></span></li>
                        <li class="flex items-start gap-3 text-sm"><i class="fas fa-envelope text-primary-light mt-0.5 w-4 flex-shrink-0"></i> <span><?= sanitize(get_site_setting('site_email', 'info@urbanglowsalon.com')) ?></span></li>
                        <li class="flex items-start gap-3 text-sm"><i class="fas fa-clock text-primary-light mt-0.5 w-4 flex-shrink-0"></i> <span>Mon - Sat: <?= date('g A', strtotime(get_site_setting('opening_time', '09:00'))) ?> - <?= date('g A', strtotime(get_site_setting('closing_time', '20:00'))) ?></span></li>
                    </ul>
                </div>
            </div>

            <!-- Footer Bottom -->
            <div class="mt-10 py-5 border-t border-white/10 text-center text-sm">
                <p>&copy; <?= date('Y') ?> <?= SITE_NAME ?>. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <!-- Scroll to Top Button -->
    <button id="scrollToTopBtn" class="fixed bottom-6 right-6 w-12 h-12 bg-primary text-white rounded-full shadow-lg flex items-center justify-center opacity-0 invisible transition-all duration-300 hover:bg-primary-dark hover:-translate-y-1 z-40">
        <i class="fas fa-arrow-up"></i>
    </button>

    <!-- Page Loader -->
    <div id="pageLoader" class="fixed inset-0 z-[9999] bg-white flex items-center justify-center transition-opacity duration-500">
        <div class="relative w-16 h-16">
            <div class="absolute inset-0 rounded-full border-4 border-gray-100"></div>
            <div class="absolute inset-0 rounded-full border-4 border-primary border-t-transparent animate-spin"></div>
            <div class="absolute inset-0 flex items-center justify-center text-primary text-xs font-bold">UG</div>
        </div>
    </div>

    <!-- Global JS Config -->
    <script>const siteUrl = '<?= SITE_URL ?>';</script>
    
    <!-- Scripts -->
    <script src="<?= SITE_URL ?>/assets/js/main.js"></script>
    <?php if (isset($extraJS)): ?>
        <?php foreach ($extraJS as $js): ?>
            <script src="<?= SITE_URL ?>/assets/js/<?= $js ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>

    <!-- Loader & Scroll JS -->
    <script>
        // Page Loader
        // Page Loader - hide immediately when HTML is parsed or fallback timeout
        const hideLoader = () => {
            const loader = document.getElementById('pageLoader');
            if (loader && loader.style.display !== 'none') {
                loader.style.opacity = '0';
                setTimeout(() => loader.style.display = 'none', 500);
            }
        };
        
        window.addEventListener('load', hideLoader);
        document.addEventListener('DOMContentLoaded', hideLoader);
        setTimeout(hideLoader, 3000); // 3-second explicit fallback


        // Scroll to Top
        const scrollBtn = document.getElementById('scrollToTopBtn');
        window.addEventListener('scroll', () => {
            if (window.scrollY > 300) {
                scrollBtn.classList.remove('opacity-0', 'invisible');
                scrollBtn.classList.add('opacity-100', 'visible');
            } else {
                scrollBtn.classList.add('opacity-0', 'invisible');
                scrollBtn.classList.remove('opacity-100', 'visible');
            }
        });
        scrollBtn.addEventListener('click', () => {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    </script>
</body>
</html>
