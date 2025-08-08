            </div>
        </main>
    </div>

    <!-- Mobile Overlay -->
    <div class="overlay" id="overlay" onclick="closeSidebar()"></div>

    <script>
        // Sidebar Functions
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('overlay');

            sidebar.classList.toggle('open');
            overlay.classList.toggle('active');
        }

        function closeSidebar() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('overlay');

            sidebar.classList.remove('open');
            overlay.classList.remove('active');
        }

        // Close sidebar on window resize if desktop
        window.addEventListener('resize', function() {
            if (window.innerWidth > 768) {
                closeSidebar();
            }
        });

        // AJAX Navigation System
        let currentPage = 'dashboard';

        // Page titles mapping
        const pageTitles = {
            'dashboard': 'Dashboard',
            'users': 'User Management',
            'vendors': 'Vendor Management',
            'customers': 'Customer Management',
            'products': 'Product Management',
            'categories': 'Category Management',
            'orders': 'Order Management',
            'analytics': 'Analytics',
            'reports': 'Reports',
            'system': 'System Status',
            'email': 'Email Test'
        };

        // Load page content
        function loadPage(page) {
            if (page === currentPage) return;

            const loading = document.getElementById('loading');
            const contentArea = document.getElementById('content-area');
            const pageTitle = document.getElementById('page-title');

            // Show loading
            loading.style.display = 'block';
            contentArea.style.display = 'none';

            // Update page title
            pageTitle.textContent = pageTitles[page] || 'Admin Panel';

            // Update active navigation
            document.querySelectorAll('.nav-item').forEach(item => {
                item.classList.remove('active');
            });
            document.querySelector(`[data-page="${page}"]`).classList.add('active');

            // Load content via AJAX
            fetch(`ajax/load-page.php?page=${page}`)
                .then(response => response.text())
                .then(html => {
                    contentArea.innerHTML = html;
                    loading.style.display = 'none';
                    contentArea.style.display = 'block';
                    currentPage = page;

                    // Close sidebar on mobile after navigation
                    if (window.innerWidth <= 768) {
                        closeSidebar();
                    }

                    // Initialize any page-specific JavaScript
                    initPageScripts(page);
                })
                .catch(error => {
                    console.error('Error loading page:', error);
                    contentArea.innerHTML = `
                        <div style="text-align: center; padding: 3rem; color: #ef4444;">
                            <i class="fas fa-exclamation-triangle" style="font-size: 3rem; margin-bottom: 1rem;"></i>
                            <h3>Error Loading Page</h3>
                            <p>Unable to load the requested page. Please try again.</p>
                            <button onclick="loadPage('dashboard')" class="btn btn-primary" style="margin-top: 1rem;">
                                <i class="fas fa-home"></i> Return to Dashboard
                            </button>
                        </div>
                    `;
                    loading.style.display = 'none';
                    contentArea.style.display = 'block';
                });
        }

        // Initialize page-specific scripts
        function initPageScripts(page) {
            // Add any page-specific JavaScript initialization here
            switch(page) {
                case 'dashboard':
                    // Initialize dashboard charts, etc.
                    break;
                case 'users':
                    // Initialize user management scripts
                    break;
                // Add more cases as needed
            }
        }

        // Navigation click handlers
        document.addEventListener('DOMContentLoaded', function() {
            // Load initial dashboard content
            loadPage('dashboard');

            // Add click handlers to navigation items
            document.querySelectorAll('.nav-item[data-page]').forEach(item => {
                item.addEventListener('click', function(e) {
                    e.preventDefault();
                    const page = this.getAttribute('data-page');
                    loadPage(page);
                });
            });

            // Update pending count periodically
            setInterval(updatePendingCount, 30000); // Every 30 seconds
        });

        // Update pending user count
        function updatePendingCount() {
            fetch('ajax/get-pending-count.php')
                .then(response => response.json())
                .then(data => {
                    const badge = document.getElementById('pending-badge');
                    if (data.count > 0) {
                        if (badge) {
                            badge.textContent = data.count;
                        } else {
                            // Create badge if it doesn't exist
                            const userNavItem = document.querySelector('[data-page="users"]');
                            const newBadge = document.createElement('span');
                            newBadge.className = 'nav-badge';
                            newBadge.id = 'pending-badge';
                            newBadge.textContent = data.count;
                            userNavItem.appendChild(newBadge);
                        }
                    } else if (badge) {
                        badge.remove();
                    }
                })
                .catch(error => console.error('Error updating pending count:', error));
        }

        // Utility functions
        function showNotification(message, type = 'info') {
            const notification = document.createElement('div');
            notification.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                background: ${type === 'success' ? '#10b981' : type === 'error' ? '#ef4444' : '#3b82f6'};
                color: white;
                padding: 1rem 1.5rem;
                border-radius: 8px;
                box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                z-index: 10000;
                max-width: 300px;
                animation: slideIn 0.3s ease-out;
            `;
            notification.innerHTML = `
                <div style="display: flex; align-items: center; gap: 0.5rem;">
                    <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'}"></i>
                    <span>${message}</span>
                </div>
            `;

            document.body.appendChild(notification);

            // Auto remove after 5 seconds
            setTimeout(() => {
                notification.style.animation = 'slideOut 0.3s ease-in';
                setTimeout(() => notification.remove(), 300);
            }, 5000);
        }

        // Add CSS animations
        const style = document.createElement('style');
        style.textContent = `
            @keyframes slideIn {
                from { transform: translateX(100%); opacity: 0; }
                to { transform: translateX(0); opacity: 1; }
            }
            @keyframes slideOut {
                from { transform: translateX(0); opacity: 1; }
                to { transform: translateX(100%); opacity: 0; }
            }
        `;
        document.head.appendChild(style);
    </script>
</body>
</html>
