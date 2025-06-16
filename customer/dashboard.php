<?php
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth_check.php';

$db = new Database();

$user_id = $_SESSION['user_id'];

$db->query("SELECT * FROM users WHERE id = :id");
$db->bind(':id', $user_id);
$user = $db->single();

$db->query("SELECT COUNT(*) as count FROM orders WHERE user_id = :user_id");
$db->bind(':user_id', $user_id);
$order_count = $db->single()->count;

$db->query("SELECT * FROM orders WHERE user_id = :user_id ORDER BY created_at DESC LIMIT 5");
$db->bind(':user_id', $user_id);
$recent_orders = $db->resultSet();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="row">
    <div class="col-md-3">
        <div class="card mb-4">
            <div class="card-body text-center">
                <div class="mb-3">
                    <i class="bi bi-person-circle fs-1"></i>
                </div>
                <h5><?php echo htmlspecialchars($user->full_name); ?></h5>
                <p class="text-muted"><?php echo htmlspecialchars($user->email); ?></p>
                <a href="<?php echo BASE_URL; ?>auth/logout.php" class="btn btn-sm btn-outline-danger">Logout</a>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h6>Account Navigation</h6>
            </div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    <li class="list-group-item active">
                        <a href="<?php echo BASE_URL; ?>customer/dashboard.php" class="text-white text-decoration-none">Dashboard</a>
                    </li>
                    <li class="list-group-item">
                        <a href="<?php echo BASE_URL; ?>customer/orders/" class="text-decoration-none">My Orders</a>
                    </li>
                    <li class="list-group-item">
                        <a href="<?php echo BASE_URL; ?>auth/update_profile.php" class="text-decoration-none">Account Information</a>
                    </li>
                    <li class="list-group-item">
                        <a href="<?php echo BASE_URL; ?>auth/change_password.php" class="text-decoration-none">Change Password</a>
                    </li>
                </ul>
            </div>
        </div>
    </div>
    
    <div class="col-md-9">
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Dashboard</h5>
            </div>
            <div class="card-body">
                <div class="alert alert-info">
                    Hello, <?php echo htmlspecialchars($user->full_name); ?>! From your account dashboard you can view your recent orders, manage your shipping and billing addresses, and edit your password and account details.
                </div>
                
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="card bg-primary text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-0">Total Orders</h6>
                                        <h2 class="mb-0"><?php echo $order_count; ?></h2>
                                    </div>
                                    <i class="bi bi-cart fs-1"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card bg-success text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-0">Account Since</h6>
                                        <h6 class="mb-0"><?php echo date('F j, Y', strtotime($user->created_at)); ?></h6>
                                    </div>
                                    <i class="bi bi-calendar fs-1"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <h5 class="mb-3">Recent Orders</h5>
                
                <?php if (empty($recent_orders)): ?>
                    <div class="alert alert-info">You have not placed any orders yet.</div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Order #</th>
                                    <th>Date</th>
                                    <th>Total</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_orders as $order): ?>
                                    <tr>
                                        <td><?php echo $order->order_number; ?></td>
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
                                            <a href="<?php echo BASE_URL; ?>customer/orders/view.php?id=<?php echo $order->id; ?>" class="btn btn-sm btn-outline-primary">View</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="text-end">
                        <a href="<?php echo BASE_URL; ?>customer/orders/" class="btn btn-outline-primary">View All Orders</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>