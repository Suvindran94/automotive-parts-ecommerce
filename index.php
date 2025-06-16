<?php
require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/config/database.php';

$db = new Database();
$db->query("SELECT * FROM products WHERE status = 'active' ORDER BY created_at DESC LIMIT 8");
$featuredProducts = $db->resultSet();

require_once __DIR__ . '/includes/header.php';
?>

<div class="hero-section bg-dark text-white py-5">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-md-6">
                <h1 class="display-4 fw-bold">Quality Automotive Parts</h1>
                <p class="lead">Find the perfect parts for your vehicle at competitive prices.</p>
                <a href="<?php echo BASE_URL; ?>products/" class="btn btn-primary btn-lg">Shop Now</a>
            </div>
            <div class="col-md-6">
                <img src="<?php echo BASE_URL; ?>assets/images/hero-image.png" alt="Auto Parts" class="img-fluid">
            </div>
        </div>
    </div>
</div>

<div class="container my-5">
    <h2 class="text-center mb-4">Featured Products</h2>
    <div class="row">
        <?php foreach ($featuredProducts as $product): ?>
            <div class="col-md-3 mb-4">
                <div class="card h-100">
                    <img src="<?php echo BASE_URL . 'assets/images/products/' . $product->image_url; ?>" class="card-img-top" alt="<?php echo htmlspecialchars($product->name); ?>">
                    <div class="card-body">
                        <h5 class="card-title"><?php echo htmlspecialchars($product->name); ?></h5>
                        <p class="card-text">RM <?php echo number_format($product->price, 2); ?></p>
                        <a href="<?php echo BASE_URL; ?>products/view.php?id=<?php echo $product->id; ?>" class="btn btn-primary">View Details</a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    <div class="text-center mt-4">
        <a href="<?php echo BASE_URL; ?>products/" class="btn btn-outline-primary">View All Products</a>
    </div>
</div>

<div class="bg-light py-5">
    <div class="container">
        <div class="row">
            <div class="col-md-4 text-center mb-4 mb-md-0">
                <div class="p-3">
                    <i class="bi bi-truck fs-1 text-primary"></i>
                    <h3 class="mt-3">Free Shipping</h3>
                    <p>On orders over RM100 within Malaysia</p>
                </div>
            </div>
            <div class="col-md-4 text-center mb-4 mb-md-0">
                <div class="p-3">
                    <i class="bi bi-arrow-repeat fs-1 text-primary"></i>
                    <h3 class="mt-3">Easy Returns</h3>
                    <p>30-day return policy for most items</p>
                </div>
            </div>
            <div class="col-md-4 text-center">
                <div class="p-3">
                    <i class="bi bi-shield-check fs-1 text-primary"></i>
                    <h3 class="mt-3">Secure Payment</h3>
                    <p>100% secure payment with ToyyibPay</p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>