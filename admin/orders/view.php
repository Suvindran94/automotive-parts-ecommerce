<?php
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/admin_auth_check.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo '<script>window.location.href = "' . BASE_URL . 'admin/orders/";</script>';
    exit();
}

$order_id = intval($_GET['id']);

$db = new Database();

$db->query("SELECT o.*, u.username, u.email, p.transaction_id, p.payment_date, p.status as payment_status 
           FROM orders o 
           JOIN users u ON o.user_id = u.id 
           LEFT JOIN payments p ON o.id = p.order_id 
           WHERE o.id = :id");
$db->bind(':id', $order_id);
$order = $db->single();

if (!$order) {
    echo '<script>window.location.href = "' . BASE_URL . 'admin/orders/";</script>';
    exit();
}

$db->query("SELECT oi.*, p.name, p.image_url 
           FROM order_items oi 
           JOIN products p ON oi.product_id = p.id 
           WHERE oi.order_id = :order_id");
$db->bind(':order_id', $order_id);
$order_items = $db->resultSet();

$db->query("SELECT * FROM shipping WHERE order_id = :order_id");
$db->bind(':order_id', $order_id);
$shipping = $db->single();

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Order #<?php echo $order->order_number; ?></h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="<?php echo BASE_URL; ?>admin/orders/" class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-arrow-left"></i> Back to Orders
                    </a>
                </div>
            </div>
            
            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5>Order Details</h5>
                        </div>
                        <div class="card-body">
                            <p>
                                <strong>Order Date:</strong> <?php echo date('F j, Y, g:i a', strtotime($order->created_at)); ?><br>
                                <strong>Customer:</strong> <?php echo htmlspecialchars($order->username); ?><br>
                                <strong>Email:</strong> <?php echo htmlspecialchars($order->email); ?><br>
                                <strong>Payment Method:</strong> <?php echo $order->payment_method; ?><br>
                                <strong>Payment Status:</strong> 
                                <span class="badge bg-<?php echo $order->payment_status === 'completed' ? 'success' : 'warning'; ?>">
                                    <?php echo ucfirst($order->payment_status); ?>
                                </span>
                                <?php if ($order->transaction_id): ?>
                                    <br><strong>Transaction ID:</strong> <?php echo $order->transaction_id; ?>
                                <?php endif; ?>
                                <?php if ($order->payment_date): ?>
                                    <br><strong>Payment Date:</strong> <?php echo date('F j, Y, g:i a', strtotime($order->payment_date)); ?>
                                <?php endif; ?>
                            </p>
                        </div>
                    </div>
                    
                    <div class="card">
                        <div class="card-header">
                            <h5>Update Order Status</h5>
                        </div>
                        <div class="card-body">
                            <form id="statusForm">
                                <div class="mb-3">
                                    <select class="form-select" id="status" name="status">
                                        <option value="pending" <?php echo $order->status === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                        <option value="processing" <?php echo $order->status === 'processing' ? 'selected' : ''; ?>>Processing</option>
                                        <option value="shipped" <?php echo $order->status === 'shipped' ? 'selected' : ''; ?>>Shipped</option>
                                        <option value="delivered" <?php echo $order->status === 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                                        <option value="cancelled" <?php echo $order->status === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                    </select>
                                </div>
                                <button type="submit" class="btn btn-primary">Update Status</button>
                            </form>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5>Shipping Information</h5>
                        </div>
                        <div class="card-body">
                            <p>
                                <strong>Name:</strong> <?php echo htmlspecialchars($order->contact_phone); ?><br>
                                <strong>Phone:</strong> <?php echo htmlspecialchars($order->contact_phone); ?><br>
                                <strong>Address:</strong> <?php echo nl2br(htmlspecialchars($order->shipping_address)); ?>
                            </p>
                            
                            <?php if ($shipping): ?>
                                <hr>
                                <p>
                                    <strong>Tracking Number:</strong> <?php echo $shipping->tracking_number ? htmlspecialchars($shipping->tracking_number) : 'Not provided'; ?><br>
                                    <strong>Carrier:</strong> <?php echo $shipping->carrier ? htmlspecialchars($shipping->carrier) : 'Not specified'; ?><br>
                                    <strong>Status:</strong> 
                                    <span class="badge bg-<?php 
                                        echo $shipping->status === 'delivered' ? 'success' : 
                                             ($shipping->status === 'in_transit' ? 'info' : 'secondary'); 
                                    ?>">
                                        <?php echo ucfirst($shipping->status); ?>
                                    </span><br>
                                    <?php if ($shipping->estimated_delivery): ?>
                                        <strong>Estimated Delivery:</strong> <?php echo date('F j, Y', strtotime($shipping->estimated_delivery)); ?><br>
                                    <?php endif; ?>
                                    <?php if ($shipping->actual_delivery): ?>
                                        <strong>Delivered On:</strong> <?php echo date('F j, Y', strtotime($shipping->actual_delivery)); ?>
                                    <?php endif; ?>
                                </p>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <?php if ($order->status === 'shipped' || $order->status === 'delivered'): ?>
                        <div class="card">
                            <div class="card-header">
                                <h5>Shipping Details</h5>
                            </div>
                            <div class="card-body">
                                <form id="shippingForm">
                                    <div class="mb-3">
                                        <label for="tracking_number" class="form-label">Tracking Number</label>
                                        <input type="text" class="form-control" id="tracking_number" name="tracking_number" value="<?php echo $shipping ? htmlspecialchars($shipping->tracking_number) : ''; ?>">
                                    </div>
                                    <div class="mb-3">
                                        <label for="carrier" class="form-label">Carrier</label>
                                        <input type="text" class="form-control" id="carrier" name="carrier" value="<?php echo $shipping ? htmlspecialchars($shipping->carrier) : ''; ?>">
                                    </div>
                                    <div class="mb-3">
                                        <label for="estimated_delivery" class="form-label">Estimated Delivery</label>
                                        <input type="date" class="form-control" id="estimated_delivery" name="estimated_delivery" value="<?php echo $shipping && $shipping->estimated_delivery ? date('Y-m-d', strtotime($shipping->estimated_delivery)) : ''; ?>">
                                    </div>
                                    <?php if ($order->status === 'delivered'): ?>
                                        <div class="mb-3">
                                            <label for="actual_delivery" class="form-label">Actual Delivery Date</label>
                                            <input type="date" class="form-control" id="actual_delivery" name="actual_delivery" value="<?php echo $shipping && $shipping->actual_delivery ? date('Y-m-d', strtotime($shipping->actual_delivery)) : ''; ?>">
                                        </div>
                                    <?php endif; ?>
                                    <button type="submit" class="btn btn-primary">Update Shipping</button>
                                </form>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="card mb-4">
                <div class="card-header">
                    <h5>Order Items</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
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
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-5 ms-auto">
                    <div class="card">
                        <div class="card-header">
                            <h5>Order Summary</h5>
                        </div>
                        <div class="card-body">
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
        </main>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#statusForm').submit(function(e) {
        e.preventDefault();
        const status = $('#status').val();
        
        $.ajax({
            url: '<?php echo BASE_URL; ?>admin/orders/update_status.php',
            method: 'POST',
            data: { 
                order_id: <?php echo $order_id; ?>,
                status: status 
            },
            success: function(response) {
                if (response.success) {
                    Swal.fire({
                        title: 'Success',
                        text: 'Order status updated successfully',
                        icon: 'success',
                        confirmButtonText: 'OK'
                    }).then(() => {
                        window.location.reload();
                    });
                } else {
                    Swal.fire({
                        title: 'Error',
                        text: response.message,
                        icon: 'error',
                        confirmButtonText: 'OK'
                    });
                }
            },
            error: function() {
                Swal.fire({
                    title: 'Error',
                    text: 'Something went wrong. Please try again.',
                    icon: 'error',
                    confirmButtonText: 'OK'
                });
            }
        });
    });
    
    <?php if ($order->status === 'shipped' || $order->status === 'delivered'): ?>
        $('#shippingForm').submit(function(e) {
            e.preventDefault();
            const formData = $(this).serializeArray();
            formData.push({name: 'order_id', value: <?php echo $order_id; ?>});
            
            $.ajax({
                url: '<?php echo BASE_URL; ?>admin/orders/update_shipping.php',
                method: 'POST',
                data: formData,
                success: function(response) {
                    if (response.success) {
                        Swal.fire({
                            title: 'Success',
                            text: 'Shipping details updated successfully',
                            icon: 'success',
                            confirmButtonText: 'OK'
                        }).then(() => {
                            window.location.reload();
                        });
                    } else {
                        Swal.fire({
                            title: 'Error',
                            text: response.message,
                            icon: 'error',
                            confirmButtonText: 'OK'
                        });
                    }
                },
                error: function() {
                    Swal.fire({
                        title: 'Error',
                        text: 'Something went wrong. Please try again.',
                        icon: 'error',
                        confirmButtonText: 'OK'
                    });
                }
            });
        });
    <?php endif; ?>
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>