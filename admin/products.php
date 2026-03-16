<?php
/**
 * Toolify Admin — Products Management (CRUD)
 */
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit();
}

require_once __DIR__ . '/../db_config.php';
$db = getDB();

$message = '';
$messageType = '';

// Handle Add Product
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_product'])) {
    $name = trim($_POST['name'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $price = (float) ($_POST['price'] ?? 0);
    $imgUrl = trim($_POST['imgUrl'] ?? '');
    $description = trim($_POST['description'] ?? '');
    
    if ($name && $category && $price > 0 && $description) {
        if (empty($imgUrl)) {
            $imgUrl = 'https://placehold.co/400x300?text=' . urlencode($name);
        }
        $stmt = $db->prepare("INSERT INTO products (name, category, price, imgUrl, description) VALUES (:name, :category, :price, :imgUrl, :description)");
        $stmt->execute(['name' => $name, 'category' => $category, 'price' => $price, 'imgUrl' => $imgUrl, 'description' => $description]);
        $message = 'Product added successfully!';
        $messageType = 'success';
    } else {
        $message = 'Please fill in all required fields.';
        $messageType = 'error';
    }
}

// Handle Delete Product
if (isset($_GET['delete'])) {
    $deleteId = (int) $_GET['delete'];
    $stmt = $db->prepare("DELETE FROM products WHERE id = :id");
    $stmt->execute(['id' => $deleteId]);
    $message = 'Product deleted successfully.';
    $messageType = 'success';
}

// Handle Edit Product
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_product'])) {
    $id = (int) $_POST['product_id'];
    $name = trim($_POST['name'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $price = (float) ($_POST['price'] ?? 0);
    $imgUrl = trim($_POST['imgUrl'] ?? '');
    $description = trim($_POST['description'] ?? '');
    
    if ($name && $category && $price > 0 && $description) {
        $stmt = $db->prepare("UPDATE products SET name = :name, category = :category, price = :price, imgUrl = :imgUrl, description = :description WHERE id = :id");
        $stmt->execute(['name' => $name, 'category' => $category, 'price' => $price, 'imgUrl' => $imgUrl, 'description' => $description, 'id' => $id]);
        $message = 'Product updated successfully!';
        $messageType = 'success';
    }
}

// Fetch products
$products = $db->query("SELECT * FROM products ORDER BY id DESC")->fetchAll();
$pendingOrders = $db->query("SELECT COUNT(*) FROM orders WHERE status = 'Pending'")->fetchColumn();

// Check if editing
$editProduct = null;
if (isset($_GET['edit'])) {
    $editId = (int) $_GET['edit'];
    $stmt = $db->prepare("SELECT * FROM products WHERE id = :id");
    $stmt->execute(['id' => $editId]);
    $editProduct = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Toolify Admin — Products</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #0f172a; color: #e2e8f0; min-height: 100vh; }
        
        .sidebar {
            position: fixed; top: 0; left: 0; width: 260px; height: 100vh;
            background: rgba(30, 41, 59, 0.95); backdrop-filter: blur(20px);
            border-right: 1px solid rgba(148, 163, 184, 0.1); padding: 24px 16px;
            z-index: 50; display: flex; flex-direction: column;
        }
        .sidebar-logo { display: flex; align-items: center; gap: 10px; padding: 0 12px 24px; border-bottom: 1px solid rgba(148, 163, 184, 0.1); margin-bottom: 24px; }
        .sidebar-logo i { color: #f59e0b; font-size: 1.5rem; }
        .sidebar-logo span { font-weight: 700; font-size: 1.25rem; color: #f8fafc; }
        .sidebar-logo small { font-size: 0.7rem; color: #64748b; font-weight: 400; margin-left: 4px; }
        .nav-link { display: flex; align-items: center; gap: 12px; padding: 12px 16px; border-radius: 10px; color: #94a3b8; text-decoration: none; font-size: 0.9rem; font-weight: 500; transition: all 0.2s; margin-bottom: 4px; }
        .nav-link:hover { background: rgba(148, 163, 184, 0.08); color: #e2e8f0; }
        .nav-link.active { background: rgba(245, 158, 11, 0.15); color: #f59e0b; }
        .nav-link i { width: 20px; text-align: center; }
        .sidebar-footer { margin-top: auto; padding-top: 16px; border-top: 1px solid rgba(148, 163, 184, 0.1); }
        .sidebar-footer .nav-link { color: #64748b; }
        .sidebar-footer .nav-link:hover { color: #ef4444; }
        
        .main-content { margin-left: 260px; padding: 32px; min-height: 100vh; }
        .page-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 32px; flex-wrap: wrap; gap: 16px; }
        .page-header h1 { font-size: 1.75rem; font-weight: 700; color: #f8fafc; }
        .page-header p { color: #64748b; font-size: 0.9rem; margin-top: 4px; }
        
        /* Cards */
        .card { background: rgba(30, 41, 59, 0.6); backdrop-filter: blur(10px); border: 1px solid rgba(148, 163, 184, 0.1); border-radius: 14px; overflow: hidden; margin-bottom: 24px; }
        .card-header { padding: 20px 24px; border-bottom: 1px solid rgba(148, 163, 184, 0.1); display: flex; align-items: center; justify-content: space-between; }
        .card-header h2 { font-size: 1.1rem; font-weight: 600; color: #f8fafc; }
        .card-body { padding: 24px; }
        
        /* Form */
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
        .form-group { margin-bottom: 16px; }
        .form-group.full { grid-column: 1 / -1; }
        .form-group label { display: block; font-size: 0.85rem; font-weight: 500; color: #94a3b8; margin-bottom: 6px; }
        .form-group input, .form-group select, .form-group textarea {
            width: 100%; padding: 10px 14px; background: rgba(15, 23, 42, 0.6);
            border: 1px solid rgba(148, 163, 184, 0.2); border-radius: 10px;
            color: #f8fafc; font-size: 0.9rem; font-family: 'Inter', sans-serif; transition: all 0.3s;
        }
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus { outline: none; border-color: #f59e0b; box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.15); }
        .form-group textarea { resize: vertical; min-height: 80px; }
        .form-group select option { background: #1e293b; }
        
        .btn { padding: 10px 20px; border-radius: 10px; font-size: 0.9rem; font-weight: 600; font-family: 'Inter', sans-serif; cursor: pointer; transition: all 0.2s; border: none; display: inline-flex; align-items: center; gap: 8px; }
        .btn-primary { background: linear-gradient(135deg, #f59e0b, #d97706); color: #0f172a; }
        .btn-primary:hover { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(245, 158, 11, 0.3); }
        .btn-secondary { background: rgba(148, 163, 184, 0.1); color: #94a3b8; border: 1px solid rgba(148, 163, 184, 0.2); }
        .btn-secondary:hover { color: #e2e8f0; border-color: rgba(148, 163, 184, 0.4); }
        .btn-danger { background: rgba(239, 68, 68, 0.15); color: #ef4444; }
        .btn-danger:hover { background: rgba(239, 68, 68, 0.25); }
        
        /* Table */
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; padding: 14px 24px; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em; color: #64748b; font-weight: 600; border-bottom: 1px solid rgba(148, 163, 184, 0.08); }
        td { padding: 14px 24px; font-size: 0.9rem; border-bottom: 1px solid rgba(148, 163, 184, 0.05); }
        tr:hover td { background: rgba(148, 163, 184, 0.03); }
        
        .product-img { width: 48px; height: 48px; border-radius: 10px; object-fit: cover; border: 1px solid rgba(148, 163, 184, 0.1); }
        
        .alert { padding: 12px 20px; border-radius: 10px; margin-bottom: 20px; font-size: 0.9rem; display: flex; align-items: center; gap: 10px; }
        .alert-success { background: rgba(34, 197, 94, 0.15); color: #22c55e; border: 1px solid rgba(34, 197, 94, 0.2); }
        .alert-error { background: rgba(239, 68, 68, 0.15); color: #fca5a5; border: 1px solid rgba(239, 68, 68, 0.2); }
        
        .actions { display: flex; gap: 8px; }
        
        @media (max-width: 768px) {
            .sidebar { display: none; }
            .main-content { margin-left: 0; padding: 20px; }
            .form-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <aside class="sidebar">
        <div class="sidebar-logo">
            <i class="fa-solid fa-hammer"></i>
            <span>Toolify <small>Admin</small></span>
        </div>
        <a href="index.php" class="nav-link"><i class="fa-solid fa-chart-line"></i> Dashboard</a>
        <a href="orders.php" class="nav-link">
            <i class="fa-solid fa-bag-shopping"></i> Orders
            <?php if ($pendingOrders > 0): ?>
                <span style="margin-left:auto;background:rgba(245,158,11,0.2);color:#f59e0b;font-size:0.7rem;padding:2px 8px;border-radius:10px;"><?php echo $pendingOrders; ?></span>
            <?php endif; ?>
        </a>
        <a href="products.php" class="nav-link active"><i class="fa-solid fa-boxes-stacked"></i> Products</a>
        <div class="sidebar-footer">
            <a href="../templates/index.html" class="nav-link"><i class="fa-solid fa-store"></i> View Store</a>
            <a href="logout.php" class="nav-link"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
        </div>
    </aside>
    
    <div class="main-content">
        <div class="page-header">
            <div>
                <h1>Products</h1>
                <p>Manage your product catalog</p>
            </div>
        </div>
        
        <?php if ($message): ?>
        <div class="alert alert-<?php echo $messageType; ?>">
            <i class="fa-solid fa-<?php echo $messageType === 'success' ? 'circle-check' : 'circle-exclamation'; ?>"></i>
            <?php echo htmlspecialchars($message); ?>
        </div>
        <?php endif; ?>
        
        <!-- Add/Edit Product Form -->
        <div class="card">
            <div class="card-header">
                <h2><?php echo $editProduct ? 'Edit Product' : 'Add New Product'; ?></h2>
                <?php if ($editProduct): ?>
                <a href="products.php" class="btn btn-secondary" style="font-size:0.8rem;padding:6px 14px;">Cancel Edit</a>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <form method="POST">
                    <?php if ($editProduct): ?>
                    <input type="hidden" name="edit_product" value="1">
                    <input type="hidden" name="product_id" value="<?php echo $editProduct['id']; ?>">
                    <?php else: ?>
                    <input type="hidden" name="add_product" value="1">
                    <?php endif; ?>
                    
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Product Name *</label>
                            <input type="text" name="name" required value="<?php echo htmlspecialchars($editProduct['name'] ?? ''); ?>" placeholder="e.g., ProArc 200 Welder">
                        </div>
                        <div class="form-group">
                            <label>Category *</label>
                            <select name="category" required>
                                <option value="">Select Category</option>
                                <option value="welding" <?php echo ($editProduct['category'] ?? '') === 'welding' ? 'selected' : ''; ?>>Welding</option>
                                <option value="power-tools" <?php echo ($editProduct['category'] ?? '') === 'power-tools' ? 'selected' : ''; ?>>Power Tools</option>
                                <option value="cutting" <?php echo ($editProduct['category'] ?? '') === 'cutting' ? 'selected' : ''; ?>>Cutting</option>
                                <option value="safety" <?php echo ($editProduct['category'] ?? '') === 'safety' ? 'selected' : ''; ?>>Safety</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Price (₹) *</label>
                            <input type="number" name="price" step="0.01" min="1" required value="<?php echo $editProduct['price'] ?? ''; ?>" placeholder="e.g., 18999">
                        </div>
                        <div class="form-group">
                            <label>Image URL</label>
                            <input type="url" name="imgUrl" value="<?php echo htmlspecialchars($editProduct['imgUrl'] ?? ''); ?>" placeholder="https://... (leave empty for auto-generated)">
                        </div>
                        <div class="form-group full">
                            <label>Description *</label>
                            <textarea name="description" required placeholder="Product description..."><?php echo htmlspecialchars($editProduct['description'] ?? ''); ?></textarea>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        <i class="fa-solid fa-<?php echo $editProduct ? 'save' : 'plus'; ?>"></i>
                        <?php echo $editProduct ? 'Update Product' : 'Add Product'; ?>
                    </button>
                </form>
            </div>
        </div>
        
        <!-- Products Table -->
        <div class="card">
            <div class="card-header">
                <h2>All Products (<?php echo count($products); ?>)</h2>
            </div>
            <div style="overflow-x:auto;">
            <table>
                <thead>
                    <tr>
                        <th>Image</th>
                        <th>Product</th>
                        <th>Category</th>
                        <th>Price</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($products as $product): ?>
                    <tr>
                        <td><img class="product-img" src="<?php echo htmlspecialchars($product['imgUrl']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" onerror="this.src='https://placehold.co/48x48?text=N/A'"></td>
                        <td>
                            <div style="font-weight:600;color:#f8fafc;"><?php echo htmlspecialchars($product['name']); ?></div>
                            <div style="font-size:0.75rem;color:#64748b;max-width:250px;line-height:1.4;margin-top:2px;"><?php echo htmlspecialchars($product['description']); ?></div>
                        </td>
                        <td style="text-transform:capitalize;"><?php echo htmlspecialchars(str_replace('-', ' ', $product['category'])); ?></td>
                        <td style="font-weight:600;color:#f59e0b;">₹<?php echo number_format($product['price'], 0); ?></td>
                        <td>
                            <div class="actions">
                                <a href="products.php?edit=<?php echo $product['id']; ?>" class="btn btn-secondary" style="font-size:0.8rem;padding:6px 12px;">
                                    <i class="fa-solid fa-pen"></i>
                                </a>
                                <a href="products.php?delete=<?php echo $product['id']; ?>" class="btn btn-danger" style="font-size:0.8rem;padding:6px 12px;" onclick="return confirm('Delete this product?')">
                                    <i class="fa-solid fa-trash"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            </div>
        </div>
    </div>
</body>
</html>
