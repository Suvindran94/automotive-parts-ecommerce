<?php
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/admin_auth_check.php';

$db = new Database();

$db->query("SELECT COUNT(*) as count FROM users WHERE role = 'customer'");
$customer_count = $db->single()->count;

$db->query("SELECT COUNT(*) as count FROM products");
$product_count = $db->single()->count;

$db->query("SELECT COUNT(*) as count FROM orders");
$order_count = $db->single()->count;

$db->query("SELECT SUM(total_amount) as total FROM orders WHERE payment_status = 'completed'");
$revenue = $db->single()->total ?? 0;

$db->query("SELECT * FROM orders ORDER BY created_at DESC LIMIT 5");
$recent_orders = $db->resultSet();

$db->query("SELECT p.name, SUM(oi.quantity) as total_sold 
           FROM order_items oi 
           JOIN products p ON oi.product_id = p.id 
           GROUP BY oi.product_id 
           ORDER BY total_sold DESC 
           LIMIT 5");
$top_products = $db->resultSet();

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <nav id="sidebar" class="col-md-3 col-lg-2 d-md-block bg-dark sidebar collapse">
            <div class="position-sticky pt-3">
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link active" href="<?php echo BASE_URL; ?>admin/dashboard.php">
                            <i class="bi bi-speedometer2 me-2"></i>
                            Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo BASE_URL; ?>admin/products/">
                            <i class="bi bi-box-seam me-2"></i>
                            Products
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo BASE_URL; ?>admin/orders/">
                            <i class="bi bi-cart me-2"></i>
                            Orders
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo BASE_URL; ?>admin/inventory/">
                            <i class="bi bi-clipboard-data me-2"></i>
                            Inventory
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo BASE_URL; ?>admin/reports/sales.php">
                            <i class="bi bi-graph-up me-2"></i>
                            Reports
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo BASE_URL; ?>admin/users/">
                            <i class="bi bi-people me-2"></i>
                            Users
                        </a>
                    </li>
                </ul>
                
                <h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-muted">
                    <span>Account</span>
                </h6>
                <ul class="nav flex-column mb-2">
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo BASE_URL; ?>auth/logout.php">
                            <i class="bi bi-box-arrow-right me-2"></i>
                            Logout
                        </a>
                    </li>
                </ul>
            </div>
        </nav>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Dashboard</h1>
            </div>
            
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card bg-primary text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-0">Customers</h6>
                                    <h2 class="mb-0"><?php echo $customer_count; ?></h2>
                                </div>
                                <i class="bi bi-people fs-1"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-success text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-0">Products</h6>
                                    <h2 class="mb-0"><?php echo $product_count; ?></h2>
                                </div>
                                <i class="bi bi-box-seam fs-1"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-info text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-0">Orders</h6>
                                    <h2 class="mb-0"><?php echo $order_count; ?></h2>
                                </div>
                                <i class="bi bi-cart fs-1"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-warning text-dark">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-0">Revenue</h6>
                                    <h2 class="mb-0">RM <?php echo number_format($revenue, 2); ?></h2>
                                </div>
                                <i class="bi bi-currency-dollar fs-1"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-8">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5>Recent Orders</h5>
                        </div>
                        <div class="card-body">
                            <?php if (empty($recent_orders)): ?>
                                <div class="alert alert-info">No recent orders found.</div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>Order #</th>
                                                <th>Customer</th>
                                                <th>Date</th>
                                                <th>Total</th>
                                                <th>Status</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($recent_orders as $order): ?>
                                                <?php
                                                $db->query("SELECT username FROM users WHERE id = :user_id");
                                                $db->bind(':user_id', $order->user_id);
                                                $customer = $db->single();
                                                ?>
                                                <tr>
                                                    <td><?php echo $order->order_number; ?></td>
                                                    <td><?php echo htmlspecialchars($customer->username); ?></td>
                                                    <td><?php echo date('M j, Y', strtotime($order->created_at)); ?></td>
                                                    <td>RM <?php echo number_format($order->total_amount, 2); ?></td>
                                                    <td>
                                                        <span class="badge bg-<?php 
                                                            echo $order->status === 'delivered' ? 'success' : 
                                                                 ($order->status === 'shipped' ? 'info' : 
                                                                 ($order->status === 'processing' ? 'primary' : 'secondary')); 
                                                        ?>">
                                                            <?php echo ucfirst($order->status); ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <a href="<?php echo BASE_URL; ?>admin/orders/view.php?id=<?php echo $order->id; ?>" class="btn btn-sm btn-outline-primary">View</a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header">
                            <h5>Top Selling Products</h5>
                        </div>
                        <div class="card-body">
                            <?php if (empty($top_products)): ?>
                                <div class="alert alert-info">No product sales data available.</div>
                            <?php else: ?>
                                <ul class="list-group">
                                    <?php foreach ($top_products as $product): ?>
                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                            <?php echo htmlspecialchars($product->name); ?>
                                            <span class="badge bg-primary rounded-pill"><?php echo $product->total_sold; ?></span>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>