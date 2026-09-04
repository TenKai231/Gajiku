    <!-- Bootstrap 5 JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmxc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>

    <!-- Custom JS -->
    <script src="/assets/js/app.js"></script>

    <!-- Manual Dropdown Fallback if Bootstrap JS fails to load -->
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            // Cek apakah bootstrap JS berhasil jalan, kalau berhasil jangan jalankan manual toggle
            if (typeof bootstrap !== 'undefined') {
                return;
            }

            var dropdowns = document.querySelectorAll('.dropdown-toggle');
            dropdowns.forEach(function(dd) {
                dd.addEventListener('click', function(e) {
                    e.preventDefault();
                    var menu = this.nextElementSibling;
                    if (menu && menu.classList.contains('dropdown-menu')) {
                        var isShowing = menu.classList.contains('show');

                        // Tutup semua yang terbuka dulu
                        document.querySelectorAll('.dropdown-menu.show').forEach(function(openMenu) {
                            openMenu.classList.remove('show');
                            if (openMenu.previousElementSibling) {
                                openMenu.previousElementSibling.setAttribute('aria-expanded', 'false');
                            }
                        });

                        // Buka jika tadinya tertutup
                        if (!isShowing) {
                            menu.classList.add('show');
                            this.setAttribute('aria-expanded', 'true');
                        }
                    }
                });
            });

            // Close dropdown when clicking outside
            window.addEventListener('click', function(e) {
                if (!e.target.matches('.dropdown-toggle') && !e.target.closest('.dropdown-toggle')) {
                    var menus = document.querySelectorAll('.dropdown-menu.show');
                    menus.forEach(function(menu) {
                        menu.classList.remove('show');
                        if (menu.previousElementSibling) {
                            menu.previousElementSibling.setAttribute('aria-expanded', 'false');
                        }
                    });
                }
            });
        });
    </script>
    <!-- Sidebar Collapse Fix -->
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            // Because we changed the structure from top navbar to sidebar, 
            // let's ensure the Bootstrap collapse works manually if the data-bs-toggle is somehow blocked
            var collapseToggles = document.querySelectorAll('[data-bs-toggle="collapse"]');
            collapseToggles.forEach(function(toggle) {
                toggle.addEventListener('click', function(e) {
                    var targetId = this.getAttribute('href') || this.getAttribute('data-bs-target');
                    if (targetId && targetId.startsWith('#')) {
                        var targetElement = document.querySelector(targetId);
                        if (targetElement && targetElement.classList.contains('collapse')) {
                            // If we click the Master Data toggle in the sidebar
                            if (this.closest('.sidebar')) {
                                e.preventDefault();
                                var isShowing = targetElement.classList.contains('show');
                                
                                if (isShowing) {
                                    targetElement.classList.remove('show');
                                    this.classList.add('collapsed');
                                    this.setAttribute('aria-expanded', 'false');
                                } else {
                                    targetElement.classList.add('show');
                                    this.classList.remove('collapsed');
                                    this.setAttribute('aria-expanded', 'true');
                                }
                            }
                        }
                    }
                });
            });
        });
    </script>
</body>
</html>