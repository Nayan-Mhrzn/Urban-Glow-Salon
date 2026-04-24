        </div><!-- end p-6 -->
    </div><!-- end main content -->
    </div><!-- end flex -->

    <script>
    // Auto-dismiss flash messages
    const flash = document.getElementById('flashMessage');
    if (flash) setTimeout(() => { flash.style.opacity = '0'; setTimeout(() => flash.remove(), 300); }, 4000);
    
    // Close sidebar on mobile when clicking outside
    document.addEventListener('click', function(e) {
        const sidebar = document.getElementById('adminSidebar');
        if (window.innerWidth < 1024 && !sidebar.contains(e.target) && !e.target.closest('button')) {
            sidebar.classList.add('-translate-x-full');
        }
    });
    </script>
</body>
</html>
