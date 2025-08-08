<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - ' . SITE_NAME : SITE_NAME . ' - ' . SITE_TAGLINE; ?></title>
    <meta name="description" content="<?php echo isset($page_description) ? $page_description : 'MarketHub - Your premier multi-vendor marketplace in Musanze District'; ?>">
    
    <!-- CSS -->
    <link rel="stylesheet" href="<?php echo ASSETS_URL; ?>css/style.css">
    <link rel="stylesheet" href="<?php echo ASSETS_URL; ?>css/image-fixes.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="<?php echo ASSETS_URL; ?>images/favicon.ico">
    
    <!-- Additional CSS -->
    <?php if (isset($additional_css)): ?>
        <?php foreach ($additional_css as $css): ?>
            <link rel="stylesheet" href="<?php echo $css; ?>">
        <?php endforeach; ?>
    <?php endif; ?>
</head>
<body>
    <!-- Admin Back Button (only visible to admins viewing as customer) -->
    <?php if (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin'): ?>
        <div id="admin-back-button" style="
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 10000;
            background: linear-gradient(135deg, #1976D2, #1565C0);
            color: white;
            padding: 12px 20px;
            border-radius: 50px;
            box-shadow: 0 4px 20px rgba(25, 118, 210, 0.3);
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            font-family: 'Inter', sans-serif;
        "
        onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 25px rgba(25, 118, 210, 0.4)';"
        onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 20px rgba(25, 118, 210, 0.3)';"
        onclick="window.location.href='admin/spa-dashboard.php'">
            <i class="fas fa-shield-alt"></i>
            <span>Back to Admin</span>
        </div>
    <?php endif; ?>

    <!-- Admin Toolbar (only visible to admins) -->
    <?php if (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin'): ?>
        <div class="admin-toolbar" style="
            background: linear-gradient(135deg, #1976D2, #1565C0);
            color: white;
            padding: 8px 0;
            font-size: 14px;
            box-shadow: 0 2px 10px rgba(25, 118, 210, 0.2);
            position: relative;
            z-index: 1000;
        ">
            <div class="container">
                <div class="d-flex justify-content-between align-items-center">
                    <div style="display: flex; align-items: center; gap: 15px;">
                        <span style="display: flex; align-items: center; gap: 8px;">
                            <i class="fas fa-shield-alt"></i>
                            <strong>Admin Mode</strong>
                        </span>
                        <span style="opacity: 0.9;">You are viewing the store as an administrator</span>
                    </div>
                    <div style="display: flex; align-items: center; gap: 15px;">
                        <a href="admin/spa-dashboard.php" style="
                            color: white;
                            text-decoration: none;
                            background: rgba(255, 255, 255, 0.2);
                            padding: 6px 15px;
                            border-radius: 20px;
                            transition: all 0.3s ease;
                            display: flex;
                            align-items: center;
                            gap: 6px;
                            font-weight: 500;
                        "
                        onmouseover="this.style.background='rgba(255, 255, 255, 0.3)'"
                        onmouseout="this.style.background='rgba(255, 255, 255, 0.2)'">
                            <i class="fas fa-cog"></i>
                            <span>Admin Panel</span>
                        </a>
                        <a href="logout.php" style="
                            color: white;
                            text-decoration: none;
                            background: rgba(244, 67, 54, 0.8);
                            padding: 6px 15px;
                            border-radius: 20px;
                            transition: all 0.3s ease;
                            display: flex;
                            align-items: center;
                            gap: 6px;
                            font-weight: 500;
                        "
                        onmouseover="this.style.background='rgba(244, 67, 54, 1)'"
                        onmouseout="this.style.background='rgba(244, 67, 54, 0.8)'">
                            <i class="fas fa-sign-out-alt"></i>
                            <span>Logout</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Header Top -->
    <div class="header-top">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <span><i class="fas fa-phone"></i> +250 793 949 904</span>
                    <span style="margin-left: 2rem;"><i class="fas fa-envelope"></i> info@markethub.com</span>
                </div>
                <div>
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <span>Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?>!</span>
                        <?php if ($_SESSION['user_type'] !== 'admin'): ?>
                            <a href="logout.php" style="margin-left: 1rem; color: white;">Logout</a>
                        <?php endif; ?>
                    <?php else: ?>
                        <a href="login.php" style="color: white;">Login</a>
                        <a href="register.php" style="margin-left: 1rem; color: white;">Register</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Header Main -->
    <header class="header">
        <div class="header-main">
            <div class="container">
                <div class="d-flex align-items-center">
                    <!-- Logo -->
                    <div class="logo">
                        <a href="<?php echo SITE_URL; ?>" style="color: var(--primary-green); text-decoration: none;">
                            MarketHub
                        </a>
                    </div>

                    <!-- Search Bar -->
                    <div class="search-bar">
                        <form action="search-simple.php" method="GET">
                            <input type="text" name="q" class="search-input" placeholder="Search products, vendors, categories..." value="<?php echo isset($_GET['q']) ? htmlspecialchars($_GET['q']) : ''; ?>">
                            <button type="submit" class="search-btn">
                                <i class="fas fa-search"></i>
                            </button>
                        </form>
                    </div>

                    <!-- Header Actions -->
                    <div class="d-flex align-items-center" style="gap: 1rem;">
                        <!-- Compare -->
                        <a href="compare.php" class="d-flex align-items-center" style="color: var(--black);">
                            <i class="fas fa-balance-scale" style="font-size: 1.2rem;"></i>
                            <span style="margin-left: 0.5rem;">Compare</span>
                            <?php if (isset($_SESSION['compare_items']) && count($_SESSION['compare_items']) > 0): ?>
                                <span class="badge" style="background: var(--primary-green); color: white; border-radius: 50%; padding: 2px 6px; font-size: 0.8rem; margin-left: 0.5rem;">
                                    <?php echo count($_SESSION['compare_items']); ?>
                                </span>
                            <?php endif; ?>
                        </a>

                        <!-- Wishlist -->
                        <?php if (isset($_SESSION['user_id'])): ?>
                        <a href="wishlist.php" class="d-flex align-items-center" style="color: var(--black);">
                            <i class="fas fa-heart" style="font-size: 1.2rem;"></i>
                            <span style="margin-left: 0.5rem;">Wishlist</span>
                        </a>
                        <?php endif; ?>

                        <!-- Cart -->
                        <a href="cart.php" class="d-flex align-items-center" style="color: var(--black);">
                            <i class="fas fa-shopping-cart" style="font-size: 1.2rem;"></i>
                            <span style="margin-left: 0.5rem;">Cart</span>
                            <?php if (isset($_SESSION['cart_items']) && count($_SESSION['cart_items']) > 0): ?>
                                <span class="badge" style="background: var(--primary-green); color: white; border-radius: 50%; padding: 2px 6px; font-size: 0.8rem; margin-left: 0.5rem;">
                                    <?php echo array_sum($_SESSION['cart_items']); ?>
                                </span>
                            <?php endif; ?>
                        </a>

                        <!-- User Account -->
                        <?php if (isset($_SESSION['user_id'])): ?>
                        <div class="dropdown" style="position: relative;">
                            <a href="#" class="d-flex align-items-center" style="color: var(--black);" onclick="toggleDropdown(event)">
                                <i class="fas fa-user" style="font-size: 1.2rem;"></i>
                                <span style="margin-left: 0.5rem;">Account</span>
                                <i class="fas fa-chevron-down" style="margin-left: 0.5rem; font-size: 0.8rem;"></i>
                            </a>
                            <div class="dropdown-menu" style="position: absolute; top: 100%; right: 0; background: white; box-shadow: var(--box-shadow); border-radius: var(--border-radius); min-width: 200px; z-index: 1000; display: none;">
                                <a href="dashboard.php" style="display: block; padding: 0.75rem 1rem; color: var(--black); border-bottom: 1px solid var(--medium-gray);">Dashboard</a>
                                <a href="orders.php" style="display: block; padding: 0.75rem 1rem; color: var(--black); border-bottom: 1px solid var(--medium-gray);">My Orders</a>
                                <a href="profile.php" style="display: block; padding: 0.75rem 1rem; color: var(--black); border-bottom: 1px solid var(--medium-gray);">Profile</a>
                                <?php if ($_SESSION['user_type'] === 'vendor'): ?>
                                <a href="vendor/dashboard.php" style="display: block; padding: 0.75rem 1rem; color: var(--black); border-bottom: 1px solid var(--medium-gray);">Vendor Dashboard</a>
                                <?php endif; ?>
                                <?php if ($_SESSION['user_type'] === 'admin'): ?>
                                <a href="admin/spa-dashboard.php" style="display: block; padding: 0.75rem 1rem; color: var(--black); border-bottom: 1px solid var(--medium-gray);">Admin Panel</a>
                                <?php endif; ?>
                                <a href="logout.php" style="display: block; padding: 0.75rem 1rem; color: var(--black);">Logout</a>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Navigation -->
        <nav class="navbar">
            <div class="container">
                <ul class="nav-menu">
                    <li class="nav-item">
                        <a href="<?php echo SITE_URL; ?>" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>">Home</a>
                    </li>
                    <li class="nav-item">
                        <a href="products.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'products.php' ? 'active' : ''; ?>">All Products</a>
                    </li>
                    <li class="nav-item">
                        <a href="vendors.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'vendors.php' ? 'active' : ''; ?>">Vendors</a>
                    </li>
                    <li class="nav-item">
                        <a href="categories.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'categories.php' ? 'active' : ''; ?>">Categories</a>
                    </li>
                    <li class="nav-item">
                        <a href="vendor-comparison.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'vendor-comparison.php' ? 'active' : ''; ?>">Compare Vendors</a>
                    </li>
                    <li class="nav-item">
                        <a href="deals.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'deals.php' ? 'active' : ''; ?>">Deals</a>
                    </li>
                    <li class="nav-item">
                        <a href="about.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'about.php' ? 'active' : ''; ?>">About</a>
                    </li>
                    <li class="nav-item">
                        <a href="contact.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'contact.php' ? 'active' : ''; ?>">Contact</a>
                    </li>
                </ul>
            </div>
        </nav>
    </header>

    <!-- Main Content -->
    <main>

<script>
function toggleDropdown(event) {
    event.preventDefault();
    const dropdown = event.target.closest('.dropdown');
    const menu = dropdown.querySelector('.dropdown-menu');
    menu.style.display = menu.style.display === 'none' ? 'block' : 'none';
}

// Close dropdown when clicking outside
document.addEventListener('click', function(event) {
    const dropdowns = document.querySelectorAll('.dropdown-menu');
    dropdowns.forEach(dropdown => {
        if (!event.target.closest('.dropdown')) {
            dropdown.style.display = 'none';
        }
    });
});
</script>
