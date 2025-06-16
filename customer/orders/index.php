<?php
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth_check.php';

$db = new Database();

$user_id = $_SESSION['user_id'];

$db->query("SELECT * FROM orders WHERE user_id = :user_id ORDER BY created_at DESC");
$db->bind(':user_id', $user_id);
$orders = $db->resultSet();

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="row">
    <div class="col-md-3">
        <div class="card mb-4">
            <div class="card-body text-center">
                <div class="mb-3">
                    <i class="bi bi-person-circle fs-1"></i>
                </div>
                <h5><?php echo htmlspecialchars($_SESSION['full_name']); ?></h5>
                <p class="text-muted"><?php echo htmlspecialchars($_SESSION['email']); ?></p>
                <a href="<?php echo BASE_URL; ?>auth/logout.php" class="btn btn-sm btn-outline-danger">Logout</a>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h6>Account Navigation</h6>
            </div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    <li class="list-group-item">
                        <a href="<?php echo BASE_URL; ?>customer/dashboard.php" class="text-decoration-none">Dashboard</a>
                    </li>
                    <li class="list-group-item active">
                        <a href="<?php echo BASE_URL; ?>customer/orders/" class="text-white text-decoration-none">My Orders</a>
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
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">My Orders</h5>
            </div>
            <div class="card-body">
                <?php if (empty($orders)): ?>
                    <div class="alert alert-info">
                        You have not placed any orders yet. <a href="<?php echo BASE_URL; ?>products/">Start shopping</a>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Order #</th>
                                    <th>Date</th>
                                    <th>Total</th>
                                    <th>Payment</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($orders as $order): ?>
                                    <tr>
                                        <td><?php echo $order->order_number; ?></td>
                                        <td><?php echo date('M j, Y', strtotime($order->created_at)); ?></td>
                                        <td>RM <?php echo number_format($order->total_amount, 2); ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo $order->payment_status === 'completed' ? 'success' : 'warning'; ?>">
                                                <?php echo ucfirst($order->payment_status); ?>
                                            </span>
                                        </td>
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
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>