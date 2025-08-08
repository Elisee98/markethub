<?php
/**
 * Customer Addresses Management
 */

require_once 'config/config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit;
}

$user_id = $_SESSION['user_id'];

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add_address') {
        $address_line_1 = sanitizeInput($_POST['address_line_1'] ?? '');
        $address_line_2 = sanitizeInput($_POST['address_line_2'] ?? '');
        $city = sanitizeInput($_POST['city'] ?? '');
        $state = sanitizeInput($_POST['state'] ?? '');
        $postal_code = sanitizeInput($_POST['postal_code'] ?? '');
        $country = sanitizeInput($_POST['country'] ?? 'Rwanda');
        $is_default = isset($_POST['is_default']) ? 1 : 0;
        
        if ($address_line_1 && $city) {
            // If this is set as default, unset other defaults
            if ($is_default) {
                $database->execute("UPDATE user_addresses SET is_default = 0 WHERE user_id = ?", [$user_id]);
            }
            
            $database->execute(
                "INSERT INTO user_addresses (user_id, address_line_1, address_line_2, city, state, postal_code, country, is_default, created_at) 
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())",
                [$user_id, $address_line_1, $address_line_2, $city, $state, $postal_code, $country, $is_default]
            );
            
            $success_message = "Address added successfully!";
        } else {
            $error_message = "Please fill in all required fields.";
        }
    }
}

// Get user addresses
$addresses = $database->fetchAll(
    "SELECT * FROM user_addresses WHERE user_id = ? ORDER BY is_default DESC, created_at DESC",
    [$user_id]
);

$page_title = 'My Addresses';
require_once 'includes/header.php';
?>

<div class="container" style="padding: 2rem 0;">
    <div class="row">
        <div class="col-3">
            <!-- Account Sidebar -->
            <div class="account-sidebar">
                <h4>My Account</h4>
                <ul style="list-style: none; padding: 0;">
                    <li><a href="profile.php">Profile</a></li>
                    <li><a href="addresses.php" class="active">Addresses</a></li>
                    <li><a href="orders.php">Orders</a></li>
                    <li><a href="wishlist.php">Wishlist</a></li>
                </ul>
            </div>
        </div>
        
        <div class="col-9">
            <div class="account-content">
                <h2>My Addresses</h2>
                
                <?php if (isset($success_message)): ?>
                    <div class="alert alert-success"><?php echo $success_message; ?></div>
                <?php endif; ?>
                
                <?php if (isset($error_message)): ?>
                    <div class="alert alert-error"><?php echo $error_message; ?></div>
                <?php endif; ?>
                
                <!-- Add New Address Button -->
                <div style="margin-bottom: 2rem;">
                    <button onclick="showAddAddressForm()" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Add New Address
                    </button>
                </div>
                
                <!-- Add Address Form (Hidden by default) -->
                <div id="addAddressForm" style="display: none; background: #f8f9fa; padding: 2rem; border-radius: 8px; margin-bottom: 2rem;">
                    <h3>Add New Address</h3>
                    <form method="POST">
                        <input type="hidden" name="action" value="add_address">
                        
                        <div class="form-group">
                            <label>Address Line 1 *</label>
                            <input type="text" name="address_line_1" required class="form-control">
                        </div>
                        
                        <div class="form-group">
                            <label>Address Line 2</label>
                            <input type="text" name="address_line_2" class="form-control">
                        </div>
                        
                        <div class="row">
                            <div class="col-6">
                                <div class="form-group">
                                    <label>City *</label>
                                    <input type="text" name="city" required class="form-control">
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="form-group">
                                    <label>State/Province</label>
                                    <input type="text" name="state" class="form-control">
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-6">
                                <div class="form-group">
                                    <label>Postal Code</label>
                                    <input type="text" name="postal_code" class="form-control">
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="form-group">
                                    <label>Country</label>
                                    <select name="country" class="form-control">
                                        <option value="Rwanda">Rwanda</option>
                                        <option value="Uganda">Uganda</option>
                                        <option value="Kenya">Kenya</option>
                                        <option value="Tanzania">Tanzania</option>
                                        <option value="Burundi">Burundi</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>
                                <input type="checkbox" name="is_default"> Set as default address
                            </label>
                        </div>
                        
                        <div style="display: flex; gap: 1rem;">
                            <button type="submit" class="btn btn-primary">Save Address</button>
                            <button type="button" onclick="hideAddAddressForm()" class="btn btn-secondary">Cancel</button>
                        </div>
                    </form>
                </div>
                
                <!-- Existing Addresses -->
                <div class="addresses-list">
                    <?php if (empty($addresses)): ?>
                        <div style="text-align: center; padding: 3rem; color: #6b7280;">
                            <i class="fas fa-map-marker-alt" style="font-size: 3rem; margin-bottom: 1rem;"></i>
                            <h3>No addresses found</h3>
                            <p>Add your first address to get started.</p>
                        </div>
                    <?php else: ?>
                        <div class="row">
                            <?php foreach ($addresses as $address): ?>
                                <div class="col-6" style="margin-bottom: 1rem;">
                                    <div class="address-card" style="border: 1px solid #e5e7eb; border-radius: 8px; padding: 1.5rem; position: relative;">
                                        <?php if ($address['is_default']): ?>
                                            <span style="position: absolute; top: 10px; right: 10px; background: #10b981; color: white; padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.75rem;">Default</span>
                                        <?php endif; ?>
                                        
                                        <div style="margin-bottom: 1rem;">
                                            <strong><?php echo htmlspecialchars($address['address_line_1']); ?></strong><br>
                                            <?php if ($address['address_line_2']): ?>
                                                <?php echo htmlspecialchars($address['address_line_2']); ?><br>
                                            <?php endif; ?>
                                            <?php echo htmlspecialchars($address['city']); ?>
                                            <?php if ($address['state']): ?>, <?php echo htmlspecialchars($address['state']); ?><?php endif; ?>
                                            <?php if ($address['postal_code']): ?> <?php echo htmlspecialchars($address['postal_code']); ?><?php endif; ?><br>
                                            <?php echo htmlspecialchars($address['country']); ?>
                                        </div>
                                        
                                        <div style="display: flex; gap: 0.5rem;">
                                            <button class="btn btn-sm btn-outline">Edit</button>
                                            <?php if (!$address['is_default']): ?>
                                                <button class="btn btn-sm btn-outline">Delete</button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function showAddAddressForm() {
    document.getElementById('addAddressForm').style.display = 'block';
}

function hideAddAddressForm() {
    document.getElementById('addAddressForm').style.display = 'none';
}
</script>

<style>
.account-sidebar {
    background: white;
    padding: 1.5rem;
    border-radius: 8px;
    border: 1px solid #e5e7eb;
}

.account-sidebar ul li {
    margin-bottom: 0.5rem;
}

.account-sidebar ul li a {
    display: block;
    padding: 0.5rem 1rem;
    text-decoration: none;
    color: #6b7280;
    border-radius: 4px;
    transition: all 0.2s;
}

.account-sidebar ul li a:hover,
.account-sidebar ul li a.active {
    background: #10b981;
    color: white;
}

.account-content {
    background: white;
    padding: 2rem;
    border-radius: 8px;
    border: 1px solid #e5e7eb;
}

.form-group {
    margin-bottom: 1rem;
}

.form-group label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 500;
    color: #374151;
}

.form-control {
    width: 100%;
    padding: 0.75rem;
    border: 1px solid #d1d5db;
    border-radius: 4px;
    font-size: 1rem;
}

.form-control:focus {
    outline: none;
    border-color: #10b981;
    box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
}

.alert {
    padding: 1rem;
    border-radius: 4px;
    margin-bottom: 1rem;
}

.alert-success {
    background: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.alert-error {
    background: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}
</style>

<?php require_once 'includes/footer.php'; ?>
