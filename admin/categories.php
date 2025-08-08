<?php
/**
 * MarketHub Category Management
 * Admin panel for managing product categories
 */

require_once '../config/config.php';

$page_title = 'Category Management';

// Check if user is admin
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    redirect('../login.php?error=access_denied');
}

$success_message = '';
$error_message = '';

// Handle category actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error_message = 'Invalid security token. Please try again.';
    } else {
        $action = sanitizeInput($_POST['action'] ?? '');
        
        try {
            if ($action === 'add') {
                $name = sanitizeInput($_POST['name'] ?? '');
                $description = sanitizeInput($_POST['description'] ?? '');
                $icon = sanitizeInput($_POST['icon'] ?? '');
                $image_url = sanitizeInput($_POST['image_url'] ?? '');
                
                if (empty($name)) {
                    throw new Exception('Category name is required.');
                }
                
                // Check if category already exists
                $existing = $database->fetch("SELECT id FROM categories WHERE name = ?", [$name]);
                if ($existing) {
                    throw new Exception('A category with this name already exists.');
                }
                
                $database->execute(
                    "INSERT INTO categories (name, description, icon, image_url, status, created_at) 
                     VALUES (?, ?, ?, ?, 'active', NOW())",
                    [$name, $description, $icon, $image_url]
                );
                
                logActivity($_SESSION['user_id'] ?? 0, 'category_created', "Category '$name' created");
                $success_message = 'Category created successfully!';
                
            } elseif ($action === 'edit') {
                $id = intval($_POST['id'] ?? 0);
                $name = sanitizeInput($_POST['name'] ?? '');
                $description = sanitizeInput($_POST['description'] ?? '');
                $icon = sanitizeInput($_POST['icon'] ?? '');
                $image_url = sanitizeInput($_POST['image_url'] ?? '');
                $status = sanitizeInput($_POST['status'] ?? 'active');
                
                if ($id <= 0 || empty($name)) {
                    throw new Exception('Invalid category data.');
                }
                
                $database->execute(
                    "UPDATE categories SET name = ?, description = ?, icon = ?, image_url = ?, status = ?, updated_at = NOW() 
                     WHERE id = ?",
                    [$name, $description, $icon, $image_url, $status, $id]
                );
                
                logActivity($_SESSION['user_id'] ?? 0, 'category_updated', "Category '$name' updated");
                $success_message = 'Category updated successfully!';
                
            } elseif ($action === 'delete') {
                $id = intval($_POST['id'] ?? 0);
                
                if ($id <= 0) {
                    throw new Exception('Invalid category ID.');
                }
                
                // Check if category has products
                $product_count = $database->fetch("SELECT COUNT(*) as count FROM products WHERE category_id = ?", [$id])['count'];
                if ($product_count > 0) {
                    throw new Exception("Cannot delete category. It has $product_count products associated with it.");
                }
                
                $category = $database->fetch("SELECT name FROM categories WHERE id = ?", [$id]);
                $database->execute("DELETE FROM categories WHERE id = ?", [$id]);
                
                logActivity($_SESSION['user_id'] ?? 0, 'category_deleted', "Category '{$category['name']}' deleted");
                $success_message = 'Category deleted successfully!';
            }
            
        } catch (Exception $e) {
            $error_message = $e->getMessage();
        }
    }
}

// Get categories with product counts
$categories = $database->fetchAll(
    "SELECT c.*, COUNT(p.id) as product_count 
     FROM categories c 
     LEFT JOIN products p ON c.id = p.category_id AND p.status = 'active'
     GROUP BY c.id 
     ORDER BY c.name ASC"
);

require_once 'includes/admin_header_new.php';
?>

<div class="content-header">
    <h1><i class="fas fa-tags"></i> Category Management</h1>
    <p>Manage product categories and organization</p>
</div>

<!-- Messages -->
<?php if ($success_message): ?>
    <div class="alert alert-success">
        <i class="fas fa-check-circle"></i>
        <?php echo htmlspecialchars($success_message); ?>
    </div>
<?php endif; ?>

<?php if ($error_message): ?>
    <div class="alert alert-error">
        <i class="fas fa-exclamation-circle"></i>
        <?php echo htmlspecialchars($error_message); ?>
    </div>
<?php endif; ?>

<!-- Statistics -->
<div class="stats-grid">
    <?php
    $category_stats = [
        'total' => count($categories),
        'active' => count(array_filter($categories, fn($c) => $c['status'] === 'active')),
        'with_products' => count(array_filter($categories, fn($c) => $c['product_count'] > 0)),
        'empty' => count(array_filter($categories, fn($c) => $c['product_count'] == 0))
    ];
    ?>
    
    <div class="stat-card">
        <div class="stat-icon" style="background: #2196F3;">
            <i class="fas fa-tags"></i>
        </div>
        <div class="stat-content">
            <h3><?php echo $category_stats['total']; ?></h3>
            <p>Total Categories</p>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon" style="background: #4CAF50;">
            <i class="fas fa-check-circle"></i>
        </div>
        <div class="stat-content">
            <h3><?php echo $category_stats['active']; ?></h3>
            <p>Active Categories</p>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon" style="background: #9C27B0;">
            <i class="fas fa-box"></i>
        </div>
        <div class="stat-content">
            <h3><?php echo $category_stats['with_products']; ?></h3>
            <p>With Products</p>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon" style="background: #FF9800;">
            <i class="fas fa-exclamation-triangle"></i>
        </div>
        <div class="stat-content">
            <h3><?php echo $category_stats['empty']; ?></h3>
            <p>Empty Categories</p>
        </div>
    </div>
</div>

<!-- Add Category Form -->
<div class="dashboard-widget">
    <div class="widget-header">
        <h4><i class="fas fa-plus"></i> Add New Category</h4>
    </div>
    <div class="widget-content">
        <form method="POST" class="category-form">
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
            <input type="hidden" name="action" value="add">
            
            <div class="form-row">
                <div class="form-group">
                    <label for="name">Category Name *</label>
                    <input type="text" id="name" name="name" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label for="icon">Icon (Font Awesome class)</label>
                    <input type="text" id="icon" name="icon" class="form-control" 
                           placeholder="e.g., fas fa-laptop">
                </div>
            </div>
            
            <div class="form-group">
                <label for="description">Description</label>
                <textarea id="description" name="description" class="form-control" rows="3"></textarea>
            </div>
            
            <div class="form-group">
                <label for="image_url">Image URL</label>
                <input type="url" id="image_url" name="image_url" class="form-control">
            </div>
            
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-plus"></i> Add Category
            </button>
        </form>
    </div>
</div>

<!-- Categories List -->
<div class="dashboard-widget">
    <div class="widget-header">
        <h4><i class="fas fa-list"></i> All Categories (<?php echo count($categories); ?>)</h4>
    </div>
    <div class="widget-content">
        <?php if (empty($categories)): ?>
            <div class="no-data">
                <i class="fas fa-tags" style="font-size: 3rem; color: #ccc; margin-bottom: 1rem;"></i>
                <h3>No Categories Found</h3>
                <p>Create your first category using the form above.</p>
            </div>
        <?php else: ?>
            <div class="categories-grid">
                <?php foreach ($categories as $category): ?>
                    <div class="category-card">
                        <div class="category-header">
                            <div class="category-icon">
                                <?php if ($category['icon']): ?>
                                    <i class="<?php echo htmlspecialchars($category['icon']); ?>"></i>
                                <?php else: ?>
                                    <i class="fas fa-tag"></i>
                                <?php endif; ?>
                            </div>
                            <div class="category-info">
                                <h5><?php echo htmlspecialchars($category['name']); ?></h5>
                                <span class="status-badge status-<?php echo $category['status']; ?>">
                                    <?php echo ucfirst($category['status']); ?>
                                </span>
                            </div>
                        </div>
                        
                        <?php if ($category['description']): ?>
                            <div class="category-description">
                                <p><?php echo htmlspecialchars($category['description']); ?></p>
                            </div>
                        <?php endif; ?>
                        
                        <div class="category-stats">
                            <div class="stat-item">
                                <span class="stat-number"><?php echo $category['product_count']; ?></span>
                                <span class="stat-label">Products</span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-number"><?php echo date('M j, Y', strtotime($category['created_at'])); ?></span>
                                <span class="stat-label">Created</span>
                            </div>
                        </div>
                        
                        <div class="category-actions">
                            <button onclick="editCategory(<?php echo htmlspecialchars(json_encode($category)); ?>)" 
                                    class="btn btn-sm btn-primary">
                                <i class="fas fa-edit"></i> Edit
                            </button>
                            <a href="../categories.php?category=<?php echo $category['id']; ?>" 
                               class="btn btn-sm btn-secondary" target="_blank">
                                <i class="fas fa-external-link-alt"></i> View
                            </a>
                            <?php if ($category['product_count'] == 0): ?>
                                <button onclick="deleteCategory(<?php echo $category['id']; ?>, '<?php echo htmlspecialchars($category['name']); ?>')" 
                                        class="btn btn-sm btn-danger">
                                    <i class="fas fa-trash"></i> Delete
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Edit Category Modal -->
<div id="editModal" class="modal">
    <div class="modal-content">
        <span class="close">&times;</span>
        <h3>Edit Category</h3>
        
        <form method="POST" id="editForm">
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" id="editId">
            
            <div class="form-row">
                <div class="form-group">
                    <label for="editName">Category Name *</label>
                    <input type="text" id="editName" name="name" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label for="editIcon">Icon</label>
                    <input type="text" id="editIcon" name="icon" class="form-control">
                </div>
            </div>
            
            <div class="form-group">
                <label for="editDescription">Description</label>
                <textarea id="editDescription" name="description" class="form-control" rows="3"></textarea>
            </div>
            
            <div class="form-group">
                <label for="editImageUrl">Image URL</label>
                <input type="url" id="editImageUrl" name="image_url" class="form-control">
            </div>
            
            <div class="form-group">
                <label for="editStatus">Status</label>
                <select id="editStatus" name="status" class="form-control">
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>
            
            <div class="modal-actions">
                <button type="button" onclick="closeModal()" class="btn btn-secondary">Cancel</button>
                <button type="submit" class="btn btn-primary">Update Category</button>
            </div>
        </form>
    </div>
</div>

<!-- Delete Form -->
<form id="deleteForm" method="POST" style="display: none;">
    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
    <input type="hidden" name="action" value="delete">
    <input type="hidden" name="id" id="deleteId">
</form>

<style>
.category-form {
    background: #f8f9fa;
    padding: 1.5rem;
    border-radius: 8px;
}

.form-row {
    display: flex;
    gap: 1rem;
}

.form-group {
    flex: 1;
    margin-bottom: 1rem;
}

.form-group label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 600;
    color: var(--admin-dark);
}

.form-control {
    width: 100%;
    padding: 0.75rem;
    border: 1px solid #ddd;
    border-radius: 6px;
    font-size: 1rem;
}

.categories-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 1.5rem;
}

.category-card {
    background: white;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    padding: 1.5rem;
    transition: box-shadow 0.3s;
}

.category-card:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.category-header {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-bottom: 1rem;
}

.category-icon {
    width: 50px;
    height: 50px;
    background: var(--admin-primary);
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
}

.category-info h5 {
    margin: 0 0 0.5rem 0;
    color: var(--admin-dark);
}

.category-description {
    margin-bottom: 1rem;
    color: #666;
    font-size: 0.9rem;
}

.category-stats {
    display: flex;
    justify-content: space-around;
    padding: 1rem;
    background: #f8f9fa;
    border-radius: 6px;
    margin-bottom: 1rem;
}

.stat-item {
    text-align: center;
}

.stat-number {
    display: block;
    font-size: 1.25rem;
    font-weight: bold;
    color: var(--admin-primary);
}

.stat-label {
    font-size: 0.8rem;
    color: #666;
}

.category-actions {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
}

.btn-sm {
    padding: 0.5rem 0.75rem;
    font-size: 0.85rem;
}

.btn-danger {
    background: var(--admin-error);
    color: white;
}

.modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.5);
}

.modal-content {
    background-color: white;
    margin: 5% auto;
    padding: 2rem;
    border-radius: 8px;
    width: 90%;
    max-width: 600px;
}

.close {
    color: #aaa;
    float: right;
    font-size: 28px;
    font-weight: bold;
    cursor: pointer;
}

.close:hover {
    color: black;
}

.modal-actions {
    display: flex;
    gap: 1rem;
    justify-content: flex-end;
    margin-top: 1rem;
}

.alert {
    padding: 1rem;
    border-radius: 6px;
    margin-bottom: 1rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
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

@media (max-width: 768px) {
    .form-row {
        flex-direction: column;
    }
    
    .categories-grid {
        grid-template-columns: 1fr;
    }
    
    .category-actions {
        flex-direction: column;
    }
}
</style>

<script>
function editCategory(category) {
    document.getElementById('editId').value = category.id;
    document.getElementById('editName').value = category.name;
    document.getElementById('editIcon').value = category.icon || '';
    document.getElementById('editDescription').value = category.description || '';
    document.getElementById('editImageUrl').value = category.image_url || '';
    document.getElementById('editStatus').value = category.status;
    
    document.getElementById('editModal').style.display = 'block';
}

function deleteCategory(id, name) {
    if (confirm(`Are you sure you want to delete the category "${name}"? This action cannot be undone.`)) {
        document.getElementById('deleteId').value = id;
        document.getElementById('deleteForm').submit();
    }
}

function closeModal() {
    document.getElementById('editModal').style.display = 'none';
}

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('editModal');
    if (event.target === modal) {
        modal.style.display = 'none';
    }
}

// Close modal with X button
document.querySelector('.close').onclick = closeModal;
</script>

<?php require_once 'includes/admin_footer_new.php'; ?>
