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
} else {
    $cartItems = [];
    $total = 0;
    
    if (isset($_SESSION['cart'])) {
        $product_ids = array_keys($_SESSION['cart']);
        if (!empty($product_ids)) {
            $placeholders = implode(',', array_fill(0, count($product_ids), '?'));
            $db->query("SELECT id, name, price, image_url, stock_quantity FROM products WHERE id IN ($placeholders)");
            foreach ($product_ids as $index => $id) {
                $db->bind($index + 1, $id);
            }
            $products = $db->resultSet();
            
            foreach ($products as $product) {
                $cartItems[] = (object)[
                    'product_id' => $product->id,
                    'name' => $product->name,
                    'price' => $product->price,
                    'image_url' => $product->image_url,
                    'stock_quantity' => $product->stock_quantity,
                    'quantity' => $_SESSION['cart'][$product->id]
                ];
                $total += $product->price * $_SESSION['cart'][$product->id];
            }
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="row">
    <div class="col-md-8">
        <h2>Shopping Cart</h2>
        
        <?php if (empty($cartItems)): ?>
            <div class="alert alert-info">
                Your cart is empty. <a href="<?php echo BASE_URL; ?>products/">Continue shopping</a>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Price</th>
                            <th>Quantity</th>
                            <th>Total</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($cartItems as $item): ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <img src="<?php echo BASE_URL . 'assets/images/products/' . $item->image_url; ?>" alt="<?php echo htmlspecialchars($item->name); ?>" width="60" class="me-3">
                                        <div>
                                            <h6 class="mb-0"><?php echo htmlspecialchars($item->name); ?></h6>
                                            <small class="text-muted">SKU: <?php echo $item->product_id; ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td>RM <?php echo number_format($item->price, 2); ?></td>
                                <td>
                                    <div class="input-group" style="width: 120px;">
                                        <button class="btn btn-outline-secondary update-quantity" data-action="decrease" data-product-id="<?php echo $item->product_id; ?>">-</button>
                                        <input type="number" class="form-control text-center quantity" value="<?php echo $item->quantity; ?>" min="1" max="<?php echo $item->stock_quantity; ?>">
                                        <button class="btn btn-outline-secondary update-quantity" data-action="increase" data-product-id="<?php echo $item->product_id; ?>">+</button>
                                    </div>
                                </td>
                                <td>RM <?php echo number_format($item->price * $item->quantity, 2); ?></td>
                                <td>
                                    <button class="btn btn-sm btn-danger remove-item" data-product-id="<?php echo $item->product_id; ?>">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="d-flex justify-content-between">
                <a href="<?php echo BASE_URL; ?>products/" class="btn btn-outline-primary">Continue Shopping</a>
                <button class="btn btn-outline-danger" id="clear-cart">Clear Cart</button>
            </div>
        <?php endif; ?>
    </div>
    
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Order Summary</h5>
            </div>
            <div class="card-body">
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
                
                <?php if (!empty($cartItems)): ?>
                    <a href="<?php echo BASE_URL; ?>checkout/" class="btn btn-primary w-100">Proceed to Checkout</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('.update-quantity').click(function() {
        const action = $(this).data('action');
        const productId = $(this).data('product-id');
        const input = $(this).siblings('.quantity');
        let quantity = parseInt(input.val());
        
        if (action === 'increase') {
            const max = parseInt(input.attr('max'));
            if (quantity < max) {
                quantity++;
            }
        } else if (action === 'decrease') {
            if (quantity > 1) {
                quantity--;
            }
        }
        
        input.val(quantity);
        updateCartItem(productId, quantity);
    });
    
    $('.quantity').change(function() {
        const productId = $(this).closest('tr').find('.update-quantity').data('product-id');
        const quantity = parseInt($(this).val());
        const max = parseInt($(this).attr('max'));
        
        if (quantity < 1) {
            $(this).val(1);
            updateCartItem(productId, 1);
        } else if (quantity > max) {
            $(this).val(max);
            updateCartItem(productId, max);
        } else {
            updateCartItem(productId, quantity);
        }
    });
    
    $('.remove-item').click(function() {
        const productId = $(this).data('product-id');
        
        Swal.fire({
            title: 'Remove Item',
            text: 'Are you sure you want to remove this item from your cart?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, remove it',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                removeCartItem(productId);
            }
        });
    });
    
    $('#clear-cart').click(function() {
        Swal.fire({
            title: 'Clear Cart',
            text: 'Are you sure you want to clear your entire cart?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, clear it',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                clearCart();
            }
        });
    });
    
    function updateCartItem(productId, quantity) {
        $.ajax({
            url: '<?php echo BASE_URL; ?>cart/update.php',
            method: 'POST',
            data: { product_id: productId, quantity: quantity },
            success: function(response) {
                if (response.success) {
                    window.location.reload();
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
    }
    
    function removeCartItem(productId) {
        $.ajax({
            url: '<?php echo BASE_URL; ?>cart/remove.php',
            method: 'POST',
            data: { product_id: productId },
            success: function(response) {
                if (response.success) {
                    Swal.fire({
                        title: 'Removed',
                        text: 'Item has been removed from your cart',
                        icon: 'success',
                        confirmButtonText: 'OK'
                    }).then(() => {
                        window.location.reload();
                    });
                    updateCartCount(response.cart_count);
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
    }
    
    function clearCart() {
        $.ajax({
            url: '<?php echo BASE_URL; ?>cart/clear.php',
            method: 'POST',
            success: function(response) {
                if (response.success) {
                    Swal.fire({
                        title: 'Cleared',
                        text: 'Your cart has been cleared',
                        icon: 'success',
                        confirmButtonText: 'OK'
                    }).then(() => {
                        window.location.reload();
                    });
                    updateCartCount(0);
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
    }
    
    function updateCartCount(count) {
        $('#cartCount').text(count);
    }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>