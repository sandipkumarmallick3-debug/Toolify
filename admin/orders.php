<?php
/**
 * Toolify Admin — Orders Management
 */
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit();
}

require_once __DIR__ . '/../db_config.php';
$db = getDB();

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $orderId = (int) $_POST['order_id'];
    $newStatus = $_POST['new_status'];
    $allowedStatuses = ['Pending', 'Processing', 'Shipped', 'Delivered'];
    
    if (in_array($newStatus, $allowedStatuses)) {
        $stmt = $db->prepare("UPDATE orders SET status = :status WHERE id = :id");
        $stmt->execute(['status' => $newStatus, 'id' => $orderId]);
    }
    header('Location: orders.php');
    exit();
}

// Fetch orders with items
$orders = $db->query("
    SELECT o.*,
           GROUP_CONCAT(p.name || ' (x' || oi.quantity || ')', ', ') as items_summary
    FROM orders o
    LEFT JOIN order_items oi ON oi.order_id = o.id
    LEFT JOIN products p ON p.id = oi.product_id
    GROUP BY o.id
    ORDER BY o.created_at DESC
")->fetchAll();

$pendingOrders = $db->query("SELECT COUNT(*) FROM orders WHERE status = 'Pending'")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Toolify Admin — Orders</title>
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
        .page-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 32px; }
        .page-header h1 { font-size: 1.75rem; font-weight: 700; color: #f8fafc; }
        .page-header p { color: #64748b; font-size: 0.9rem; margin-top: 4px; }
        
        .table-card { background: rgba(30, 41, 59, 0.6); backdrop-filter: blur(10px); border: 1px solid rgba(148, 163, 184, 0.1); border-radius: 14px; overflow: hidden; }
        .table-card-header { padding: 20px 24px; border-bottom: 1px solid rgba(148, 163, 184, 0.1); }
        .table-card-header h2 { font-size: 1.1rem; font-weight: 600; color: #f8fafc; }
        
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; padding: 14px 24px; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em; color: #64748b; font-weight: 600; border-bottom: 1px solid rgba(148, 163, 184, 0.08); }
        td { padding: 16px 24px; font-size: 0.9rem; border-bottom: 1px solid rgba(148, 163, 184, 0.05); vertical-align: top; }
        tr:hover td { background: rgba(148, 163, 184, 0.03); }
        
        .badge { display: inline-block; padding: 4px 12px; border-radius: 20px; font-size: 0.75rem; font-weight: 600; }
        .badge-pending { background: rgba(245, 158, 11, 0.15); color: #f59e0b; }
        .badge-processing { background: rgba(59, 130, 246, 0.15); color: #3b82f6; }
        .badge-shipped { background: rgba(168, 85, 247, 0.15); color: #a855f7; }
        .badge-delivered { background: rgba(34, 197, 94, 0.15); color: #22c55e; }
        
        .status-form select {
            background: rgba(15, 23, 42, 0.6); border: 1px solid rgba(148, 163, 184, 0.2);
            color: #e2e8f0; padding: 6px 10px; border-radius: 8px; font-size: 0.8rem;
            font-family: 'Inter', sans-serif; cursor: pointer;
        }
        .status-form select:focus { outline: none; border-color: #f59e0b; }
        .status-form button {
            background: linear-gradient(135deg, #f59e0b, #d97706); border: none;
            color: #0f172a; padding: 6px 12px; border-radius: 8px; font-size: 0.8rem;
            font-weight: 600; cursor: pointer; font-family: 'Inter', sans-serif;
            transition: all 0.2s; margin-left: 6px;
        }
        .status-form button:hover { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(245, 158, 11, 0.3); }
        
        .items-list { font-size: 0.8rem; color: #94a3b8; max-width: 250px; line-height: 1.5; }
        
        .empty-state { text-align: center; padding: 60px 20px; color: #475569; }
        .empty-state i { font-size: 3rem; margin-bottom: 16px; opacity: 0.3; }
        
        @media (max-width: 768px) {
            .sidebar { display: none; }
            .main-content { margin-left: 0; padding: 20px; }
            table { font-size: 0.8rem; }
            th, td { padding: 10px 12px; }
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
        <a href="orders.php" class="nav-link active">
            <i class="fa-solid fa-bag-shopping"></i> Orders
            <?php if ($pendingOrders > 0): ?>
                <span style="margin-left:auto;background:rgba(245,158,11,0.2);color:#f59e0b;font-size:0.7rem;padding:2px 8px;border-radius:10px;"><?php echo $pendingOrders; ?></span>
            <?php endif; ?>
        </a>
        <a href="products.php" class="nav-link"><i class="fa-solid fa-boxes-stacked"></i> Products</a>
        <div class="sidebar-footer">
            <a href="../templates/index.html" class="nav-link"><i class="fa-solid fa-store"></i> View Store</a>
            <a href="logout.php" class="nav-link"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
        </div>
    </aside>
    
    <div class="main-content">
        <div class="page-header">
            <div>
                <h1>Orders</h1>
                <p>Manage and track customer orders</p>
            </div>
        </div>
        
        <div class="table-card">
            <div class="table-card-header">
                <h2>All Orders (<?php echo count($orders); ?>)</h2>
            </div>
            
            <?php if (count($orders) > 0): ?>
            <div style="overflow-x:auto;">
            <table>
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Customer</th>
                        <th>Items</th>
                        <th>Total</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th>Update Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $order): ?>
                    <tr>
                        <td style="font-weight:600;">#<?php echo $order['id']; ?></td>
                        <td>
                            <div style="font-weight:500;color:#f8fafc;"><?php echo htmlspecialchars($order['customer_name']); ?></div>
                            <div style="font-size:0.75rem;color:#64748b;"><?php echo htmlspecialchars($order['customer_phone']); ?></div>
                            <div style="font-size:0.75rem;color:#475569;margin-top:2px;"><?php echo htmlspecialchars($order['customer_address']); ?></div>
                        </td>
                        <td><div class="items-list"><?php echo htmlspecialchars($order['items_summary'] ?? 'N/A'); ?></div></td>
                        <td style="font-weight:600;color:#f59e0b;">₹<?php echo number_format($order['total_amount'], 0); ?></td>
                        <td>
                            <?php
                            $statusClass = 'badge-pending';
                            if ($order['status'] === 'Processing') $statusClass = 'badge-processing';
                            if ($order['status'] === 'Shipped') $statusClass = 'badge-shipped';
                            if ($order['status'] === 'Delivered') $statusClass = 'badge-delivered';
                            ?>
                            <span class="badge <?php echo $statusClass; ?>"><?php echo $order['status']; ?></span>
                        </td>
                        <td style="color:#64748b;font-size:0.85rem;white-space:nowrap;"><?php echo date('d M Y', strtotime($order['created_at'])); ?><br><span style="font-size:0.75rem;"><?php echo date('h:i A', strtotime($order['created_at'])); ?></span></td>
                        <td>
                            <form class="status-form" method="POST" style="display:flex;align-items:center;">
                                <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                <input type="hidden" name="update_status" value="1">
                                <select name="new_status">
                                    <option value="Pending" <?php echo $order['status'] === 'Pending' ? 'selected' : ''; ?>>Pending</option>
                                    <option value="Processing" <?php echo $order['status'] === 'Processing' ? 'selected' : ''; ?>>Processing</option>
                                    <option value="Shipped" <?php echo $order['status'] === 'Shipped' ? 'selected' : ''; ?>>Shipped</option>
                                    <option value="Delivered" <?php echo $order['status'] === 'Delivered' ? 'selected' : ''; ?>>Delivered</option>
                                </select>
                                <button type="submit"><i class="fa-solid fa-check"></i></button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            </div>
            <?php else: ?>
            <div class="empty-state">
                <i class="fa-solid fa-inbox"></i>
                <p>No orders yet. They'll appear here when customers start ordering.</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
