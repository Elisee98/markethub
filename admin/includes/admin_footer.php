            </div>
        </main>
    </div>

    <!-- Mobile Overlay -->
    <div class="mobile-overlay" onclick="closeSidebar()"></div>

    <script>
        // Sidebar Toggle Functions
        function toggleSidebar() {
            const container = document.querySelector('.admin-container');
            const overlay = document.querySelector('.mobile-overlay');

            container.classList.toggle('sidebar-open');
            overlay.classList.toggle('active');
        }

        function closeSidebar() {
            const container = document.querySelector('.admin-container');
            const overlay = document.querySelector('.mobile-overlay');

            container.classList.remove('sidebar-open');
            overlay.classList.remove('active');
        }

        // Close sidebar on window resize if mobile
        window.addEventListener('resize', function() {
            if (window.innerWidth > 1024) {
                closeSidebar();
            }
        });

        // Auto-refresh pending user count
        function updatePendingCount() {
            fetch('ajax/get-pending-count.php')
                .then(response => response.json())
                .then(data => {
                    const badge = document.querySelector('.nav-badge');
                    if (badge) {
                        if (data.count > 0) {
                            badge.textContent = data.count;
                            badge.style.display = 'inline-block';
                        } else {
                            badge.style.display = 'none';
                        }
                    }
                })
                .catch(error => {
                    console.log('Error updating pending count:', error);
                });
        }

        // Update pending count every 30 seconds
        setInterval(updatePendingCount, 30000);

        // Notification system
        function showNotification(message, type = 'info') {
            const notification = document.createElement('div');
            notification.className = `admin-notification ${type}`;
            notification.innerHTML = `
                <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'}"></i>
                <span>${message}</span>
                <button onclick="this.parentElement.remove()"><i class="fas fa-times"></i></button>
            `;
            
            document.body.appendChild(notification);
            
            // Auto-remove after 5 seconds
            setTimeout(() => {
                if (notification.parentElement) {
                    notification.remove();
                }
            }, 5000);
        }

        // Confirm dialogs for dangerous actions
        function confirmAction(message, callback) {
            if (confirm(message)) {
                callback();
            }
        }

        // Loading state for buttons
        function setButtonLoading(button, loading = true) {
            if (loading) {
                button.disabled = true;
                button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
            } else {
                button.disabled = false;
                // Restore original content - you might want to store this
            }
        }

        // Format numbers with commas
        function formatNumber(num) {
            return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
        }

        // Format currency
        function formatCurrency(amount) {
            return 'RWF ' + formatNumber(amount);
        }

        // Copy to clipboard
        function copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(function() {
                showNotification('Copied to clipboard!', 'success');
            }, function(err) {
                showNotification('Failed to copy to clipboard', 'error');
            });
        }

        // Initialize tooltips (if using a tooltip library)
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize any tooltips or other components here
            
            // Add loading states to forms
            const forms = document.querySelectorAll('form');
            forms.forEach(form => {
                form.addEventListener('submit', function() {
                    const submitBtn = form.querySelector('button[type="submit"]');
                    if (submitBtn) {
                        setButtonLoading(submitBtn, true);
                    }
                });
            });
        });
    </script>
</body>
</html>
