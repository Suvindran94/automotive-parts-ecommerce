<?php
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth_check.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo '<script>window.location.href = "' . BASE_URL . 'customer/orders/";</script>';
    exit();
}

$order_id = intval($_GET['id']);
$user_id = $_SESSION['user_id'];

$db = new Database();

$db->query("SELECT o.*, p.transaction_id, p.payment_date 
           FROM orders o 
           LEFT JOIN payments p ON o.id = p.order_id 
           WHERE o.id = :id AND o.user_id = :user_id");
$db->bind(':id', $order_id);
$db->bind(':user_id', $user_id);
$order = $db->single();

if (!$order) {
    echo '<script>window.location.href = "' . BASE_URL . 'customer/orders/";</script>';
    exit();
}

$db->query("SELECT oi.*, p.name, p.image_url 
           FROM order_items oi 
           JOIN products p ON oi.product_id = p.id 
           WHERE oi.order_id = :order_id");
$db->bind(':order_id', $order_id);
$order_items = $db->resultSet();

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
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Order #<?php echo $order->order_number; ?></h5>
                    <span class="badge bg-<?php 
                        echo $order->status === 'delivered' ? 'success' : 
                             ($order->status === 'shipped' ? 'info' : 
                             ($order->status === 'processing' ? 'primary' : 'secondary')); 
                    ?>">
                        <?php echo ucfirst($order->status); ?>
                    </span>
                </div>
            </div>
            <div class="card-body">
                <div class="row mb-4">
                    <div class="col-md-6">
                        <h6>Order Details</h6>
                        <p>
                            <strong>Order Date:</strong> <?php echo date('F j, Y, g:i a', strtotime($order->created_at)); ?><br>
                            <strong>Payment Method:</strong> <?php echo $order->payment_method; ?><br>
                            <strong>Payment Status:</strong> 
                            <span class="badge bg-<?php echo $order->payment_status === 'completed' ? 'success' : 'warning'; ?>">
                                <?php echo ucfirst($order->payment_status); ?>
                            </span>
                            <?php if ($order->transaction_id): ?>
                                <br><strong>Transaction ID:</strong> <?php echo $order->transaction_id; ?>
                            <?php endif; ?>
                        </p>
                    </div>
                    <div class="col-md-6">
                        <h6>Shipping Information</h6>
                        <p>
                            <strong>Name:</strong> <?php echo htmlspecialchars($order->contact_phone); ?><br>
                            <strong>Phone:</strong> <?php echo htmlspecialchars($order->contact_phone); ?><br>
                            <strong>Address:</strong> <?php echo nl2br(htmlspecialchars($order->shipping_address)); ?>
                        </p>
                    </div>
                </div>
                
                <div class="table-responsive mb-4">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Price</th>
                                <th>Quantity</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($order_items as $item): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <img src="<?php echo BASE_URL . 'assets/images/products/' . $item->image_url; ?>" alt="<?php echo htmlspecialchars($item->name); ?>" width="60" class="me-3">
                                            <div>
                                                <h6 class="mb-0"><?php echo htmlspecialchars($item->name); ?></h6>
                                            </div>
                                        </div>
                                    </td>
                                    <td>RM <?php echo number_format($item->price, 2); ?></td>
                                    <td><?php echo $item->quantity; ?></td>
                                    <td>RM <?php echo number_format($item->price * $item->quantity, 2); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <div class="row">
                    <div class="col-md-5 ms-auto">
                        <div class="table-responsive">
                            <table class="table">
                                <tbody>
                                    <tr>
                                        <th>Subtotal</th>
                                        <td>RM <?php echo number_format($order->total_amount, 2); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Shipping</th>
                                        <td>Free</td>
                                    </tr>
                                    <tr>
                                        <th>Total</th>
                                        <td>RM <?php echo number_format($order->total_amount, 2); ?></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>