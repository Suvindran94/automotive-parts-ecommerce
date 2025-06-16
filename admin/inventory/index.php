<?php
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/admin_auth_check.php';

$db = new Database();

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$status = isset($_GET['status']) ? $_GET['status'] : '';

$sql = "SELECT p.id, p.name, p.stock_quantity, p.status, p.sku, c.name as category_name 
       FROM products p 
       LEFT JOIN categories c ON p.category_id = c.id";
       
$conditions = [];
$params = [];

if (!empty($search)) {
    $conditions[] = "(p.name LIKE :search OR p.sku LIKE :search)";
    $params[':search'] = "%$search%";
}

if (!empty($status) && in_array($status, ['in_stock', 'low_stock', 'out_of_stock'])) {
    if ($status === 'in_stock') {
        $conditions[] = "p.stock_quantity > 10";
    } elseif ($status === 'low_stock') {
        $conditions[] = "p.stock_quantity <= 10 AND p.stock_quantity > 0";
    } elseif ($status === 'out_of_stock') {
        $conditions[] = "p.stock_quantity = 0";
    }
}

if (!empty($conditions)) {
    $sql .= " WHERE " . implode(" AND ", $conditions);
}

$sql .= " ORDER BY p.stock_quantity ASC, p.name ASC";

$db->query($sql);
foreach ($params as $key => $value) {
    $db->bind($key, $value);
}
$products = $db->resultSet();

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Inventory Management</h1>
            </div>
            
            <div class="card mb-4">
                <div class="card-body">
                    <form id="filterForm" class="row g-3">
                        <div class="col-md-6">
                            <label for="search" class="form-label">Search</label>
                            <input type="text" class="form-control" id="search" name="search" value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                        <div class="col-md-4">
                            <label for="status" class="form-label">Stock Status</label>
                            <select class="form-select" id="status" name="status">
                                <option value="">All Status</option>
                                <option value="in_stock" <?php echo $status === 'in_stock' ? 'selected' : ''; ?>>In Stock</option>
                                <option value="low_stock" <?php echo $status === 'low_stock' ? 'selected' : ''; ?>>Low Stock</option>
                                <option value="out_of_stock" <?php echo $status === 'out_of_stock' ? 'selected' : ''; ?>>Out of Stock</option>
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
                            <th>ID</th>
                            <th>Product</th>
                            <th>SKU</th>
                            <th>Category</th>
                            <th>Stock</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($products)): ?>
                            <tr>
                                <td colspan="7" class="text-center">No products found</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($products as $product): ?>
                                <tr>
                                    <td><?php echo $product->id; ?></td>
                                    <td><?php echo htmlspecialchars($product->name); ?></td>
                                    <td><?php echo htmlspecialchars($product->sku); ?></td>
                                    <td><?php echo $product->category_name ? htmlspecialchars($product->category_name) : 'Uncategorized'; ?></td>
                                    <td>
                                        <span class="badge bg-<?php 
                                            echo $product->stock_quantity > 10 ? 'success' : 
                                                 ($product->stock_quantity > 0 ? 'warning' : 'danger'); 
                                        ?>">
                                            <?php echo $product->stock_quantity; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?php echo $product->status === 'active' ? 'success' : 'secondary'; ?>">
                                            <?php echo ucfirst($product->status); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-primary update-stock" data-id="<?php echo $product->id; ?>" data-name="<?php echo htmlspecialchars($product->name); ?>">
                                            <i class="bi bi-pencil"></i> Update
                                        </button>
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

<div class="modal fade" id="updateStockModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Update Stock</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="updateStockForm">
                <div class="modal-body">
                    <input type="hidden" id="product_id" name="product_id">
                    <p>Product: <strong id="product_name"></strong></p>
                    <div class="mb-3">
                        <label for="stock_quantity" class="form-label">Stock Quantity</label>
                        <input type="number" class="form-control" id="stock_quantity" name="stock_quantity" min="0" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('.update-stock').click(function() {
        const productId = $(this).data('id');
        const productName = $(this).data('name');
        
        $('#product_id').val(productId);
        $('#product_name').text(productName);
        
        $.ajax({
            url: '<?php echo BASE_URL; ?>admin/inventory/get_stock.php',
            method: 'POST',
            data: { product_id: productId },
            success: function(response) {
                if (response.success) {
                    $('#stock_quantity').val(response.stock_quantity);
                    $('#updateStockModal').modal('show');
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
    
    $('#updateStockForm').submit(function(e) {
        e.preventDefault();
        const productId = $('#product_id').val();
        const stockQuantity = $('#stock_quantity').val();
        
        $.ajax({
            url: '<?php echo BASE_URL; ?>admin/inventory/update.php',
            method: 'POST',
            data: { 
                product_id: productId,
                stock_quantity: stockQuantity 
            },
            success: function(response) {
                if (response.success) {
                    Swal.fire({
                        title: 'Success',
                        text: 'Stock quantity updated successfully',
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
    
    $('#filterForm').submit(function(e) {
        e.preventDefault();
        const search = $('#search').val();
        const status = $('#status').val();
        
        let queryParams = [];
        if (search) queryParams.push(`search=${encodeURIComponent(search)}`);
        if (status) queryParams.push(`status=${status}`);
        
        window.location.href = `<?php echo BASE_URL; ?>admin/inventory/?${queryParams.join('&')}`;
    });
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>