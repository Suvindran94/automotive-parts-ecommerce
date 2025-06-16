<?php
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';

$db = new Database();

$category_id = isset($_GET['category']) ? intval($_GET['category']) : null;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$min_price = isset($_GET['min_price']) ? floatval($_GET['min_price']) : null;
$max_price = isset($_GET['max_price']) ? floatval($_GET['max_price']) : null;

$sql = "SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE p.status = 'active'";
$params = [];

if ($category_id) {
    $sql .= " AND p.category_id = :category_id";
    $params[':category_id'] = $category_id;
}

if (!empty($search)) {
    $sql .= " AND (p.name LIKE :search OR p.description LIKE :search)";
    $params[':search'] = "%$search%";
}

if ($min_price !== null) {
    $sql .= " AND p.price >= :min_price";
    $params[':min_price'] = $min_price;
}

if ($max_price !== null) {
    $sql .= " AND p.price <= :max_price";
    $params[':max_price'] = $max_price;
}

$sql .= " ORDER BY p.created_at DESC";

$db->query($sql);
foreach ($params as $key => $value) {
    $db->bind($key, $value);
}
$products = $db->resultSet();

$db->query("SELECT * FROM categories WHERE parent_id IS NULL");
$categories = $db->resultSet();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="row">
    <div class="col-md-3">
        <div class="card mb-4">
            <div class="card-header">
                <h5>Categories</h5>
            </div>
            <div class="card-body">
                <ul class="list-group">
                    <li class="list-group-item">
                        <a href="<?php echo BASE_URL; ?>products/">All Categories</a>
                    </li>
                    <?php foreach ($categories as $category): ?>
                        <li class="list-group-item">
                            <a href="<?php echo BASE_URL; ?>products/?category=<?php echo $category->id; ?>">
                                <?php echo htmlspecialchars($category->name); ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
        
        <div class="card mb-4">
            <div class="card-header">
                <h5>Filter</h5>
            </div>
            <div class="card-body">
                <form id="filterForm">
                    <div class="mb-3">
                        <label for="search" class="form-label">Search</label>
                        <input type="text" class="form-control" id="search" name="search" value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    <div class="mb-3">
                        <label for="min_price" class="form-label">Min Price (RM)</label>
                        <input type="number" class="form-control" id="min_price" name="min_price" min="0" step="0.01" value="<?php echo $min_price; ?>">
                    </div>
                    <div class="mb-3">
                        <label for="max_price" class="form-label">Max Price (RM)</label>
                        <input type="number" class="form-control" id="max_price" name="max_price" min="0" step="0.01" value="<?php echo $max_price; ?>">
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Apply Filters</button>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-md-9">
        <div class="row mb-4">
            <div class="col-md-6">
                <h2>
                    <?php 
                    if ($category_id) {
                        echo "Products in " . htmlspecialchars($products[0]->category_name ?? 'Category');
                    } else {
                        echo "All Products";
                    }
                    ?>
                </h2>
            </div>
            <div class="col-md-6 text-end">
                <div class="dropdown">
                    <button class="btn btn-outline-secondary dropdown-toggle" type="button" id="sortDropdown" data-bs-toggle="dropdown">
                        Sort By
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="?sort=price_asc">Price: Low to High</a></li>
                        <li><a class="dropdown-item" href="?sort=price_desc">Price: High to Low</a></li>
                        <li><a class="dropdown-item" href="?sort=newest">Newest First</a></li>
                        <li><a class="dropdown-item" href="?sort=popular">Most Popular</a></li>
                    </ul>
                </div>
            </div>
        </div>
        
        <div class="row">
            <?php if (empty($products)): ?>
                <div class="col-12">
                    <div class="alert alert-info">No products found matching your criteria.</div>
                </div>
            <?php else: ?>
                <?php foreach ($products as $product): ?>
                    <div class="col-md-4 mb-4">
                        <div class="card h-100">
                            <img src="<?php echo BASE_URL . 'assets/images/products/' . $product->image_url; ?>" class="card-img-top" alt="<?php echo htmlspecialchars($product->name); ?>">
                            <div class="card-body">
                                <h5 class="card-title"><?php echo htmlspecialchars($product->name); ?></h5>
                                <p class="card-text"><?php echo htmlspecialchars(substr($product->description, 0, 100)); ?>...</p>
                                <p class="card-text"><strong>RM <?php echo number_format($product->price, 2); ?></strong></p>
                                <div class="d-flex justify-content-between">
                                    <a href="<?php echo BASE_URL; ?>products/view.php?id=<?php echo $product->id; ?>" class="btn btn-sm btn-outline-primary">Details</a>
                                    <button class="btn btn-sm btn-primary add-to-cart" data-product-id="<?php echo $product->id; ?>">Add to Cart</button>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('.add-to-cart').click(function() {
        const productId = $(this).data('product-id');
        
        $.ajax({
            url: '<?php echo BASE_URL; ?>cart/add.php',
            method: 'POST',
            data: { product_id: productId, quantity: 1 },
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
    
    $('#filterForm').submit(function(e) {
        e.preventDefault();
        const search = $('#search').val();
        const min_price = $('#min_price').val();
        const max_price = $('#max_price').val();
        
        let queryParams = [];
        if (search) queryParams.push(`search=${encodeURIComponent(search)}`);
        if (min_price) queryParams.push(`min_price=${min_price}`);
        if (max_price) queryParams.push(`max_price=${max_price}`);
        
        window.location.href = `<?php echo BASE_URL; ?>products/?${queryParams.join('&')}`;
    });
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>