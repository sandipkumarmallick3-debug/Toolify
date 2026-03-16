<?php
/**
 * Toolify Admin — Dashboard
 */
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit();
}

require_once __DIR__ . '/../db_config.php';
$db = getDB();

// Stats
$totalOrders = $db->query("SELECT COUNT(*) FROM orders")->fetchColumn();
$totalRevenue = $db->query("SELECT COALESCE(SUM(total_amount), 0) FROM orders")->fetchColumn();
$totalProducts = $db->query("SELECT COUNT(*) FROM products")->fetchColumn();
$pendingOrders = $db->query("SELECT COUNT(*) FROM orders WHERE status = 'Pending'")->fetchColumn();

// Recent Orders
$recentOrders = $db->query("
    SELECT o.*, 
           (SELECT COUNT(*) FROM order_items WHERE order_id = o.id) as item_count
    FROM orders o 
    ORDER BY o.created_at DESC 
    LIMIT 10
")->fetchAll();

$currentPage = 'dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Toolify Admin — Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', sans-serif;
            background: #0f172a;
            color: #e2e8f0;
            min-height: 100vh;
        }
        
        /* Sidebar */
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            width: 260px;
            height: 100vh;
            background: rgba(30, 41, 59, 0.95);
            backdrop-filter: blur(20px);
            border-right: 1px solid rgba(148, 163, 184, 0.1);
            padding: 24px 16px;
            z-index: 50;
            display: flex;
            flex-direction: column;
        }
        .sidebar-logo {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 0 12px 24px;
            border-bottom: 1px solid rgba(148, 163, 184, 0.1);
            margin-bottom: 24px;
        }
        .sidebar-logo i { color: #f59e0b; font-size: 1.5rem; }
        .sidebar-logo span { font-weight: 700; font-size: 1.25rem; color: #f8fafc; }
        .sidebar-logo small { font-size: 0.7rem; color: #64748b; font-weight: 400; margin-left: 4px; }
        
        .nav-link {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 16px;
            border-radius: 10px;
            color: #94a3b8;
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 500;
            transition: all 0.2s;
            margin-bottom: 4px;
        }
        .nav-link:hover { background: rgba(148, 163, 184, 0.08); color: #e2e8f0; }
        .nav-link.active { background: rgba(245, 158, 11, 0.15); color: #f59e0b; }
        .nav-link i { width: 20px; text-align: center; }
        
        .sidebar-footer {
            margin-top: auto;
            padding-top: 16px;
            border-top: 1px solid rgba(148, 163, 184, 0.1);
        }
        .sidebar-footer .nav-link { color: #64748b; }
        .sidebar-footer .nav-link:hover { color: #ef4444; }
        
        /* Main Content */
        .main-content {
            margin-left: 260px;
            padding: 32px;
            min-height: 100vh;
        }
        
        .page-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 32px;
        }
        .page-header h1 { font-size: 1.75rem; font-weight: 700; color: #f8fafc; }
        .page-header p { color: #64748b; font-size: 0.9rem; margin-top: 4px; }
        
        /* Stat Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
            margin-bottom: 32px;
        }
        .stat-card {
            background: rgba(30, 41, 59, 0.6);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(148, 163, 184, 0.1);
            border-radius: 14px;
            padding: 24px;
            transition: all 0.3s;
        }
        .stat-card:hover {
            border-color: rgba(245, 158, 11, 0.3);
            transform: translateY(-2px);
        }
        .stat-card .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            margin-bottom: 16px;
        }
        .stat-card .stat-value { font-size: 1.75rem; font-weight: 700; color: #f8fafc; }
        .stat-card .stat-label { font-size: 0.85rem; color: #64748b; margin-top: 4px; }
        
        .icon-amber { background: rgba(245, 158, 11, 0.15); color: #f59e0b; }
        .icon-green { background: rgba(34, 197, 94, 0.15); color: #22c55e; }
        .icon-blue { background: rgba(59, 130, 246, 0.15); color: #3b82f6; }
        .icon-red { background: rgba(239, 68, 68, 0.15); color: #ef4444; }
        
        /* Table */
        .table-card {
            background: rgba(30, 41, 59, 0.6);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(148, 163, 184, 0.1);
            border-radius: 14px;
            overflow: hidden;
        }
        .table-card-header {
            padding: 20px 24px;
            border-bottom: 1px solid rgba(148, 163, 184, 0.1);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .table-card-header h2 { font-size: 1.1rem; font-weight: 600; color: #f8fafc; }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th {
            text-align: left;
            padding: 14px 24px;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #64748b;
            font-weight: 600;
            border-bottom: 1px solid rgba(148, 163, 184, 0.08);
        }
        td {
            padding: 16px 24px;
            font-size: 0.9rem;
            border-bottom: 1px solid rgba(148, 163, 184, 0.05);
        }
        tr:hover td { background: rgba(148, 163, 184, 0.03); }
        
        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        .badge-pending { background: rgba(245, 158, 11, 0.15); color: #f59e0b; }
        .badge-processing { background: rgba(59, 130, 246, 0.15); color: #3b82f6; }
        .badge-shipped { background: rgba(168, 85, 247, 0.15); color: #a855f7; }
        .badge-delivered { background: rgba(34, 197, 94, 0.15); color: #22c55e; }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #475569;
        }
        .empty-state i { font-size: 3rem; margin-bottom: 16px; opacity: 0.3; }
        
        /* Responsive */
        @media (max-width: 768px) {
            .sidebar { display: none; }
            .main-content { margin-left: 0; padding: 20px; }
            .stats-grid { grid-template-columns: 1fr 1fr; }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="sidebar-logo">
            <i class="fa-solid fa-hammer"></i>
            <span>Toolify <small>Admin</small></span>
        </div>
        
        <a href="index.php" class="nav-link active">
            <i class="fa-solid fa-chart-line"></i> Dashboard
        </a>
        <a href="orders.php" class="nav-link">
            <i class="fa-solid fa-bag-shopping"></i> Orders
            <?php if ($pendingOrders > 0): ?>
                <span style="margin-left:auto;background:rgba(245,158,11,0.2);color:#f59e0b;font-size:0.7rem;padding:2px 8px;border-radius:10px;"><?php echo $pendingOrders; ?></span>
            <?php endif; ?>
        </a>
        <a href="products.php" class="nav-link">
            <i class="fa-solid fa-boxes-stacked"></i> Products
        </a>
        
        <div class="sidebar-footer">
            <a href="../templates/index.html" class="nav-link">
                <i class="fa-solid fa-store"></i> View Store
            </a>
            <a href="logout.php" class="nav-link">
                <i class="fa-solid fa-right-from-bracket"></i> Logout
            </a>
        </div>
    </aside>
    
    <!-- Main Content -->
    <div class="main-content">
        <div class="page-header">
            <div>
                <h1>Dashboard</h1>
                <p>Welcome back, <?php echo htmlspecialchars($_SESSION['admin_username']); ?>!</p>
            </div>
        </div>
        
        <!-- Stats -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon icon-amber"><i class="fa-solid fa-bag-shopping"></i></div>
                <div class="stat-value"><?php echo $totalOrders; ?></div>
                <div class="stat-label">Total Orders</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon icon-green"><i class="fa-solid fa-indian-rupee-sign"></i></div>
                <div class="stat-value">₹<?php echo number_format($totalRevenue, 0); ?></div>
                <div class="stat-label">Total Revenue</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon icon-blue"><i class="fa-solid fa-boxes-stacked"></i></div>
                <div class="stat-value"><?php echo $totalProducts; ?></div>
                <div class="stat-label">Products</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon icon-red"><i class="fa-solid fa-clock"></i></div>
                <div class="stat-value"><?php echo $pendingOrders; ?></div>
                <div class="stat-label">Pending Orders</div>
            </div>
        </div>
        
        <!-- Recent Orders -->
        <div class="table-card">
            <div class="table-card-header">
                <h2>Recent Orders</h2>
                <a href="orders.php" style="color:#f59e0b;font-size:0.85rem;text-decoration:none;">View All →</a>
            </div>
            
            <?php if (count($recentOrders) > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Customer</th>
                        <th>Items</th>
                        <th>Total</th>
                        <th>Status</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentOrders as $order): ?>
                    <tr>
                        <td style="font-weight:600;">#<?php echo $order['id']; ?></td>
                        <td>
                            <div style="font-weight:500;color:#f8fafc;"><?php echo htmlspecialchars($order['customer_name']); ?></div>
                            <div style="font-size:0.75rem;color:#64748b;"><?php echo htmlspecialchars($order['customer_phone']); ?></div>
                        </td>
                        <td><?php echo $order['item_count']; ?> item<?php echo $order['item_count'] != 1 ? 's' : ''; ?></td>
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
                        <td style="color:#64748b;font-size:0.85rem;"><?php echo date('d M Y, h:i A', strtotime($order['created_at'])); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
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
