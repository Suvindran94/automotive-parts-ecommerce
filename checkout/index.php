<?php
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth_check.php';

$db = new Database();

if (isset($_SESSION['user_id'])) {
    $db->query("SELECT ci.*, p.name, p.price, p.image_url, p.stock_quantity 
               FROM cart_items ci 
               JOIN carts c ON ci.cart_id = c.id 
               JOIN products p ON ci.product_id = p.id 
               WHERE c.user_id = :user_id");
    $db->bind(':user_id', $_SESSION['user_id']);
    $cartItems = $db->resultSet();
    
    $db->query("SELECT SUM(ci.quantity * p.price) as total 
               FROM cart_items ci 
               JOIN carts c ON ci.cart_id = c.id 
               JOIN products p ON ci.product_id = p.id 
               WHERE c.user_id = :user_id");
    $db->bind(':user_id', $_SESSION['user_id']);
    $total = $db->single()->total ?? 0;
    
    $db->query("SELECT * FROM users WHERE id = :user_id");
    $db->bind(':user_id', $_SESSION['user_id']);
    $user = $db->single();
} else {
    echo '<script>window.location.href = "' . BASE_URL . 'auth/login.php";</script>';
    exit();
}

if (empty($cartItems)) {
    echo '<script>window.location.href = "' . BASE_URL . 'cart/";</script>';
    exit();
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="row">
    <div class="col-md-8">
        <div class="card mb-4">
            <div class="card-header">
                <h5>Shipping Information</h5>
            </div>
            <div class="card-body">
                <form id="checkoutForm">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="full_name" class="form-label">Full Name</label>
                            <input type="text" class="form-control" id="full_name" name="full_name" value="<?php echo htmlspecialchars($user->full_name); ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label for="contact_phone" class="form-label">Phone Number</label>
                            <input type="tel" class="form-control" id="contact_phone" name="contact_phone" value="<?php echo htmlspecialchars($user->phone ?? ''); ?>" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="shipping_address" class="form-label">Shipping Address</label>
                        <textarea class="form-control" id="shipping_address" name="shipping_address" rows="3" required><?php echo htmlspecialchars($user->address ?? ''); ?></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="billing_address" class="form-label">Billing Address (if different)</label>
                        <textarea class="form-control" id="billing_address" name="billing_address" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="notes" class="form-label">Order Notes (optional)</label>
                        <textarea class="form-control" id="notes" name="notes" rows="3"></textarea>
                    </div>
                </form>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h5>Payment Method</h5>
            </div>
            <div class="card-body">
                <div class="form-check mb-3">
                    <input class="form-check-input" type="radio" name="payment_method" id="paymentFPX" value="FPX" checked>
                    <label class="form-check-label" for="paymentFPX">
                        <i class="bi bi-bank"></i> FPX Online Banking
                    </label>
                </div>
                <div class="form-check mb-3">
                    <input class="form-check-input" type="radio" name="payment_method" id="paymentCOD" value="COD" disabled>
                    <label class="form-check-label" for="paymentCOD">
                        <i class="bi bi-cash"></i> Cash on Delivery (Currently unavailable)
                    </label>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h5>Order Summary</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($cartItems as $item): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($item->name); ?> × <?php echo $item->quantity; ?></td>
                                    <td>RM <?php echo number_format($item->price * $item->quantity, 2); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <div class="d-flex justify-content-between mb-3">
                    <span>Subtotal</span>
                    <span>RM <?php echo number_format($total, 2); ?></span>
                </div>
                <div class="d-flex justify-content-between mb-3">
                    <span>Shipping</span>
                    <span>Free</span>
                </div>
                <hr>
                <div class="d-flex justify-content-between mb-3">
                    <strong>Total</strong>
                    <strong>RM <?php echo number_format($total, 2); ?></strong>
                </div>
                
                <button type="button" class="btn btn-primary w-100" id="placeOrderBtn">Place Order</button>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#placeOrderBtn').click(function() {
        const formData = $('#checkoutForm').serializeArray();
        const paymentMethod = $('input[name="payment_method"]:checked').val();
        
        formData.push({name: 'payment_method', value: paymentMethod});
        
        $.ajax({
            url: '<?php echo BASE_URL; ?>checkout/process.php',
            method: 'POST',
            data: formData,
            beforeSend: function() {
                $('#placeOrderBtn').prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Processing...');
            },
            success: function(response) {
                if (response.success) {
                    if (response.payment_url) {
                        window.location.href = response.payment_url;
                    } else {
                        window.location.href = '<?php echo BASE_URL; ?>customer/orders/view.php?id=' + response.order_id;
                    }
                } else {
                    Swal.fire({
                        title: 'Error',
                        text: response.message,
                        icon: 'error',
                        confirmButtonText: 'OK'
                    });
                    $('#placeOrderBtn').prop('disabled', false).text('Place Order');
                }
            },
            error: function() {
                Swal.fire({
                    title: 'Error',
                    text: 'Something went wrong. Please try again.',
                    icon: 'error',
                    confirmButtonText: 'OK'
                });
                $('#placeOrderBtn').prop('disabled', false).text('Place Order');
            }
        });
    });
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>