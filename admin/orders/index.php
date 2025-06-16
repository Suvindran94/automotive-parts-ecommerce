<?php
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/admin_auth_check.php';

$db = new Database();

$status = isset($_GET['status']) ? $_GET['status'] : '';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

$sql = "SELECT o.*, u.username 
       FROM orders o 
       JOIN users u ON o.user_id = u.id";
       
$conditions = [];
$params = [];

if (!empty($status) && in_array($status, ['pending', 'processing', 'shipped', 'delivered', 'cancelled'])) {
    $conditions[] = "o.status = :status";
    $params[':status'] = $status;
}

if (!empty($search)) {
    $conditions[] = "(o.order_number LIKE :search OR u.username LIKE :search)";
    $params[':search'] = "%$search%";
}

if (!empty($conditions)) {
    $sql .= " WHERE " . implode(" AND ", $conditions);
}

$sql .= " ORDER BY o.created_at DESC";

$db->query($sql);
foreach ($params as $key => $value) {
    $db->bind($key, $value);
}
$orders = $db->resultSet();

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Orders</h1>
            </div>
            
            <div class="card mb-4">
                <div class="card-body">
                    <form id="filterForm" class="row g-3">
                        <div class="col-md-6">
                            <label for="search" class="form-label">Search</label>
                            <input type="text" class="form-control" id="search" name="search" value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                        <div class="col-md-4">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select" id="status" name="status">
                                <option value="">All Status</option>
                                <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="processing" <?php echo $status === 'processing' ? 'selected' : ''; ?>>Processing</option>
                                <option value="shipped" <?php echo $status === 'shipped' ? 'selected' : ''; ?>>Shipped</option>
                                <option value="delivered" <?php echo $status === 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                                <option value="cancelled" <?php echo $status === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                            </select>
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary w-100">Filter</button>
                        </div>
                    </form>
                </div>
            </div>
            
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>Order #</th>
                            <th>Customer</th>
                            <th>Date</th>
                            <th>Total</th>
                            <th>Payment</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($orders)): ?>
                            <tr>
                                <td colspan="7" class="text-center">No orders found</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($orders as $order): ?>
                                <tr>
                                    <td><?php echo $order->order_number; ?></td>
                                    <td><?php echo htmlspecialchars($order->username); ?></td>
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
                                        <a href="<?php echo BASE_URL; ?>admin/orders/view.php?id=<?php echo $order->id; ?>" class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-eye"></i> View
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#filterForm').submit(function(e) {
        e.preventDefault();
        const search = $('#search').val();
        const status = $('#status').val();
        
        let queryParams = [];
        if (search) queryParams.push(`search=${encodeURIComponent(search)}`);
        if (status) queryParams.push(`status=${status}`);
        
        window.location.href = `<?php echo BASE_URL; ?>admin/orders/?${queryParams.join('&')}`;
    });
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>