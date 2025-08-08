    </main>

    <!-- Comparison Widget -->
    <?php require_once 'comparison-widget.php'; ?>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="row">
                <!-- Company Info -->
                <div class="col-3">
                    <div class="footer-section">
                        <h5 class="footer-title">MarketHub</h5>
                        <p style="color: var(--medium-gray); margin-bottom: 1rem;">Your premier multi-vendor marketplace connecting buyers and sellers in Musanze District and beyond.</p>
                        <div style="display: flex; gap: 1rem;">
                            <a href="#" class="footer-link" style="font-size: 1.2rem;"><i class="fab fa-facebook"></i></a>
                            <a href="#" class="footer-link" style="font-size: 1.2rem;"><i class="fab fa-twitter"></i></a>
                            <a href="#" class="footer-link" style="font-size: 1.2rem;"><i class="fab fa-instagram"></i></a>
                            <a href="#" class="footer-link" style="font-size: 1.2rem;"><i class="fab fa-linkedin"></i></a>
                        </div>
                    </div>
                </div>

                <!-- Quick Links -->
                <div class="col-3">
                    <div class="footer-section">
                        <h5 class="footer-title"><i class="fas fa-bolt"></i> Quick Links</h5>
                        <a href="<?php echo SITE_URL; ?>" class="footer-link"><i class="fas fa-home"></i> Home</a>
                        <a href="products.php" class="footer-link"><i class="fas fa-shopping-bag"></i> All Products</a>
                        <a href="compare.php" class="footer-link"><i class="fas fa-balance-scale"></i> Compare Products</a>
                        <a href="categories.php" class="footer-link"><i class="fas fa-th-large"></i> Categories</a>
                        <a href="deals.php" class="footer-link"><i class="fas fa-fire"></i> Hot Deals</a>
                        <a href="vendors.php" class="footer-link"><i class="fas fa-store"></i> Our Vendors</a>
                        <a href="about.php" class="footer-link"><i class="fas fa-info-circle"></i> About Us</a>
                    </div>
                </div>

                <!-- Customer Service -->
                <div class="col-3">
                    <div class="footer-section">
                        <h5 class="footer-title"><i class="fas fa-headset"></i> Customer Service</h5>
                        <a href="help.php" class="footer-link"><i class="fas fa-life-ring"></i> Help Center</a>
                        <a href="shipping.php" class="footer-link"><i class="fas fa-truck"></i> Shipping Info</a>
                        <a href="returns.php" class="footer-link"><i class="fas fa-undo"></i> Returns & Refunds</a>
                        <a href="faq.php" class="footer-link"><i class="fas fa-question-circle"></i> FAQ</a>
                        <a href="contact.php" class="footer-link"><i class="fas fa-envelope"></i> Contact Us</a>
                        <a href="privacy.php" class="footer-link"><i class="fas fa-shield-alt"></i> Privacy Policy</a>
                        <a href="terms.php" class="footer-link"><i class="fas fa-file-contract"></i> Terms of Service</a>
                    </div>
                </div>

                <!-- For Vendors -->
                <div class="col-3">
                    <div class="footer-section">
                        <h5 class="footer-title"><i class="fas fa-store-alt"></i> For Vendors</h5>
                        <a href="vendor/register.php" class="footer-link"><i class="fas fa-user-plus"></i> Become a Vendor</a>
                        <a href="vendor/login.php" class="footer-link"><i class="fas fa-sign-in-alt"></i> Vendor Login</a>
                        <a href="vendor/spa-dashboard.php" class="footer-link"><i class="fas fa-tachometer-alt"></i> Vendor Dashboard</a>
                        <a href="vendor-guide.php" class="footer-link"><i class="fas fa-book"></i> Seller Guide</a>
                        <a href="vendor-fees.php" class="footer-link"><i class="fas fa-calculator"></i> Fees & Pricing</a>
                        <a href="vendor-support.php" class="footer-link"><i class="fas fa-hands-helping"></i> Vendor Support</a>
                    </div>
                </div>
            </div>

            <!-- Newsletter Signup -->
            <div style="background: var(--dark-gray); padding: 2rem; border-radius: var(--border-radius); margin: 2rem 0;">
                <div class="row align-items-center">
                    <div class="col-6">
                        <h4 style="color: white; margin-bottom: 0.5rem;">Stay Updated</h4>
                        <p style="color: var(--medium-gray); margin-bottom: 0;">Subscribe to our newsletter for the latest deals and updates.</p>
                    </div>
                    <div class="col-6">
                        <form action="newsletter.php" method="POST" style="display: flex; gap: 1rem;">
                            <input type="email" name="email" placeholder="Enter your email" required 
                                   style="flex: 1; padding: 12px; border: none; border-radius: var(--border-radius); font-size: 1rem;">
                            <button type="submit" class="btn btn-primary">Subscribe</button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Contact Info -->
            <div style="background: var(--primary-green); padding: 2rem; border-radius: var(--border-radius); margin: 2rem 0; color: white;">
                <div class="row">
                    <div class="col-4 text-center">
                        <i class="fas fa-map-marker-alt" style="font-size: 2rem; margin-bottom: 1rem;"></i>
                        <h6 style="color: white;">Address</h6>
                        <p style="color: white; margin-bottom: 0;">Musanze District<br>Northern Province, Rwanda</p>
                    </div>
                    <div class="col-4 text-center">
                        <i class="fas fa-phone" style="font-size: 2rem; margin-bottom: 1rem;"></i>
                        <h6 style="color: white;">Phone</h6>
                        <p style="color: white; margin-bottom: 0;">+250 793 949 904<br>+250 722 803 290</p>
                    </div>
                    <div class="col-4 text-center">
                        <i class="fas fa-envelope" style="font-size: 2rem; margin-bottom: 1rem;"></i>
                        <h6 style="color: white;">Email</h6>
                        <p style="color: white; margin-bottom: 0;">info@markethub.com<br>support@markethub.com</p>
                    </div>
                </div>
            </div>

            <!-- Footer Bottom -->
            <div class="footer-bottom">
                <div class="row align-items-center">
                    <div class="col-6">
                        <p>&copy; <?php echo date('Y'); ?> MarketHub. All rights reserved.</p>
                    </div>
                    <div class="col-6 text-right">
                        <p>Developed for Musanze District | by Ange | Software Engineer</p>
                    </div>
                </div>
            </div>
        </div>
    </footer>

    <!-- JavaScript -->
    <script src="<?php echo ASSETS_URL; ?>js/main.js"></script>
    
    <!-- Additional JavaScript -->
    <?php if (isset($additional_js)): ?>
        <?php foreach ($additional_js as $js): ?>
            <script src="<?php echo $js; ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>

    <!-- Inline JavaScript -->
    <script>
        // Search suggestions
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.querySelector('.search-input');
            if (searchInput) {
                searchInput.addEventListener('input', function() {
                    const query = this.value;
                    if (query.length > 2) {
                        // Implement search suggestions here
                        // You can make an AJAX call to get suggestions
                    }
                });
            }

            // Cart update notifications
            function updateCartCount() {
                // Implement cart count update
            }

            // Wishlist toggle
            function toggleWishlist(productId) {
                // Implement wishlist toggle functionality
                fetch('api/wishlist.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        product_id: productId,
                        action: 'toggle'
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Update UI
                        const wishlistBtn = document.querySelector(`[data-product-id="${productId}"]`);
                        if (wishlistBtn) {
                            wishlistBtn.classList.toggle('active');
                        }
                    }
                });
            }

            // Compare products
            function addToCompare(productId) {
                fetch('api/compare.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        product_id: productId,
                        action: 'add'
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Update compare count
                        const compareCount = document.querySelector('.compare-count');
                        if (compareCount) {
                            compareCount.textContent = data.count;
                        }
                        
                        // Show notification
                        showNotification('Product added to comparison', 'success');
                    } else {
                        showNotification(data.message || 'Error adding to comparison', 'error');
                    }
                });
            }

            // Show notifications
            function showNotification(message, type = 'info') {
                const notification = document.createElement('div');
                notification.className = `alert alert-${type}`;
                notification.style.cssText = 'position: fixed; top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
                notification.textContent = message;
                
                document.body.appendChild(notification);
                
                setTimeout(() => {
                    notification.remove();
                }, 3000);
            }

            // Smooth scrolling for anchor links
            document.querySelectorAll('a[href^="#"]').forEach(anchor => {
                anchor.addEventListener('click', function (e) {
                    e.preventDefault();
                    const target = document.querySelector(this.getAttribute('href'));
                    if (target) {
                        target.scrollIntoView({
                            behavior: 'smooth'
                        });
                    }
                });
            });
        });
    </script>
</body>
</html>
