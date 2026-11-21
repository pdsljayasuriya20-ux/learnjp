<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const sidebar = document.getElementById('adminSidebar');
            const toggleButton = document.getElementById('sidebarToggle');
            const overlay = document.getElementById('sidebar-overlay');
            const content = document.querySelector('.content');

            // Sidebar එක Toggle කිරීමේ ශ්‍රිතය
            function toggleSidebar() {
                sidebar.classList.toggle('active');
                overlay.classList.toggle('active');
            }

            // බොත්තම ක්ලික් කළ විට
            toggleButton.addEventListener('click', toggleSidebar);

            // Overlay එක ක්ලික් කළ විට (Sidebar එක වැසීමට)
            overlay.addEventListener('click', toggleSidebar);

            // ජංගම දුරකථනයෙන් link එකක් ක්ලික් කළ පසු Sidebar එක වැසීම
            document.querySelectorAll('.sidebar a').forEach(link => {
                link.addEventListener('click', function() {
                    if (window.innerWidth <= 768) {
                         // සුළු ප්‍රමාදයකින් වසන්න, එවිට link එක ක්‍රියාත්මක වීමට ඉඩ ලැබේ.
                        setTimeout(() => {
                           if (sidebar.classList.contains('active')) {
                                toggleSidebar();
                           }
                        }, 300);
                    }
                });
            });
        });
    </script>
</body>
</html>