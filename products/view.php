<?php
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo '<script>window.location.href = "' . BASE_URL . 'products/";</script>';
    exit();
}

$product_id = intval($_GET['id']);

$db = new Database();
$db->query("SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE p.id = :id AND p.status = 'active'");
$db->bind(':id', $product_id);
$product = $db->single();

if (!$product) {
    echo '<script>window.location.href = "' . BASE_URL . 'products/";</script>';
    exit();
}

$db->query("SELECT * FROM product_images WHERE product_id = :product_id");
$db->bind(':product_id', $product_id);
$images = $db->resultSet();

$db->query("SELECT * FROM products WHERE category_id = :category_id AND id != :product_id AND status = 'active' LIMIT 4");
$db->bind(':category_id', $product->category_id);
$db->bind(':product_id', $product_id);
$relatedProducts = $db->resultSet();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="row">
    <div class="col-md-12">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>">Home</a></li>
                <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>products/">Products</a></li>
                <?php if ($product->category_id): ?>
                    <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>products/?category=<?php echo $product->category_id; ?>"><?php echo htmlspecialchars($product->category_name); ?></a></li>
                <?php endif; ?>
                <li class="breadcrumb-item active" aria-current="page"><?php echo htmlspecialchars($product->name); ?></li>
            </ol>
        </nav>
    </div>
</div>

<div class="row mb-5">
    <div class="col-md-6">
        <div id="productCarousel" class="carousel slide" data-bs-ride="carousel">
            <div class="carousel-inner">
                <?php if (empty($images)): ?>
                    <div class="carousel-item active">
                        <img src="<?php echo BASE_URL; ?>assets/images/products/default.png" class="d-block w-100" alt="<?php echo htmlspecialchars($product->name); ?>">
                    </div>
                <?php else: ?>
                    <?php foreach ($images as $index => $image): ?>
                        <div class="carousel-item <?php echo $index === 0 ? 'active' : ''; ?>">
                            <img src="<?php echo BASE_URL . 'assets/images/products/' . $image->image_url; ?>" class="d-block w-100" alt="<?php echo htmlspecialchars($product->name); ?>">
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            <button class="carousel-control-prev" type="button" data-bs-target="#productCarousel" data-bs-slide="prev">
                <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                <span class="visually-hidden">Previous</span>
            </button>
            <button class="carousel-control-next" type="button" data-bs-target="#productCarousel" data-bs-slide="next">
                <span class="carousel-control-next-icon" aria-hidden="true"></span>
                <span class="visually-hidden">Next</span>
            </button>
        </div>
        
        <?php if (!empty($images)): ?>
            <div class="row mt-3">
                <?php foreach ($images as $index => $image): ?>
                    <div class="col-3">
                        <img src="<?php echo BASE_URL . 'assets/images/products/' . $image->image_url; ?>" class="img-thumbnail" style="cursor: pointer;" onclick="$('#productCarousel').carousel(<?php echo $index; ?>)" alt="Thumbnail">
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <div class="col-md-6">
        <h2><?php echo htmlspecialchars($product->name); ?></h2>
        <div class="mb-3">
            <?php if ($product->category_id): ?>
                <a href="<?php echo BASE_URL; ?>products/?category=<?php echo $product->category_id; ?>" class="text-decoration-none">
                    <span class="badge bg-secondary"><?php echo htmlspecialchars($product->category_name); ?></span>
                </a>
            <?php endif; ?>
        </div>
        
        <h3 class="text-primary">RM <?php echo number_format($product->price, 2); ?></h3>
        
        <div class="mb-4">
            <?php if ($product->stock_quantity > 0): ?>
                <span class="text-success"><i class="bi bi-check-circle"></i> In Stock</span>
            <?php else: ?>
                <span class="text-danger"><i class="bi bi-x-circle"></i> Out of Stock</span>
            <?php endif; ?>
        </div>
        
        <div class="mb-4">
            <h5>Description</h5>
            <p><?php echo nl2br(htmlspecialchars($product->description)); ?></p>
        </div>
        
        <?php if ($product->stock_quantity > 0): ?>
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="input-group">
                        <button class="btn btn-outline-secondary quantity-minus" type="button">-</button>
                        <input type="number" class="form-control text-center quantity-input" value="1" min="1" max="<?php echo $product->stock_quantity; ?>">
                        <button class="btn btn-outline-secondary quantity-plus" type="button">+</button>
                    </div>
                </div>
                <div class="col-md-8">
                    <button class="btn btn-primary w-100 add-to-cart" data-product-id="<?php echo $product->id; ?>">
                        Add to Cart
                    </button>
                </div>
            </div>
        <?php endif; ?>
        
        <div class="d-flex align-items-center">
            <span class="me-2">Share:</span>
            <a href="#" class="btn btn-sm btn-outline-secondary me-2"><i class="bi bi-facebook"></i></a>
            <a href="#" class="btn btn-sm btn-outline-secondary me-2"><i class="bi bi-twitter"></i></a>
            <a href="#" class="btn btn-sm btn-outline-secondary"><i class="bi bi-whatsapp"></i></a>
        </div>
    </div>
</div>

<?php if (!empty($relatedProducts)): ?>
    <div class="row mt-5">
        <div class="col-12">
            <h3>Related Products</h3>
            <div class="row">
                <?php foreach ($relatedProducts as $relatedProduct): ?>
                    <div class="col-md-3 mb-4">
                        <div class="card h-100">
                            <img src="<?php echo BASE_URL . 'assets/images/products/' . $relatedProduct->image_url; ?>" class="card-img-top" alt="<?php echo htmlspecialchars($relatedProduct->name); ?>">
                            <div class="card-body">
                                <h5 class="card-title"><?php echo htmlspecialchars($relatedProduct->name); ?></h5>
                                <p class="card-text">RM <?php echo number_format($relatedProduct->price, 2); ?></p>
                                <a href="<?php echo BASE_URL; ?>products/view.php?id=<?php echo $relatedProduct->id; ?>" class="btn btn-sm btn-outline-primary">View Details</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
<?php endif; ?>

<script>
$(document).ready(function() {
    $('.quantity-minus').click(function() {
        const input = $(this).siblings('.quantity-input');
        let value = parseInt(input.val());
        if (value > 1) {
            input.val(value - 1);
        }
    });
    
    $('.quantity-plus').click(function() {
        const input = $(this).siblings('.quantity-input');
        let value = parseInt(input.val());
        const max = parseInt(input.attr('max'));
        if (value < max) {
            input.val(value + 1);
        }
    });
    
    $('.add-to-cart').click(function() {
        const productId = $(this).data('product-id');
        const quantity = $('.quantity-input').val();
        
        $.ajax({
            url: '<?php echo BASE_URL; ?>cart/add.php',
            method: 'POST',
            data: { product_id: productId, quantity: quantity },
            success: function(response) {
                if (response.success) {
                    Swal.fire({
                        title: 'Added to Cart',
                        text: 'Product has been added to your cart',
                        icon: 'success',
                        confirmButtonText: 'OK'
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
    });
    
    function updateCartCount(count) {
        $('#cartCount').text(count);
    }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>