<?php
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/admin_auth_check.php';

$db = new Database();

$db->query("SELECT 
           p.id,
           p.name,
           p.sku,
           p.stock_quantity,
           c.name as category_name,
           SUM(oi.quantity) as sold_quantity
           FROM products p
           LEFT JOIN categories c ON p.category_id = c.id
           LEFT JOIN order_items oi ON p.id = oi.product_id
           GROUP BY p.id
           ORDER BY p.stock_quantity ASC");
$inventory = $db->resultSet();

$db->query("SELECT 
           SUM(stock_quantity) as total_stock,
           COUNT(*) as product_count,
           SUM(CASE WHEN stock_quantity <= 0 THEN 1 ELSE 0 END) as out_of_stock,
           SUM(CASE WHEN stock_quantity > 0 AND stock_quantity <= 10 THEN 1 ELSE 0 END) as low_stock
           FROM products");
$summary = $db->single();

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Inventory Reports</h1>
            </div>
            
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card text-white bg-primary">
                        <div class="card-body">
                            <h5 class="card-title">Total Products</h5>
                            <p class="card-text h3"><?php echo $summary->product_count; ?></p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-white bg-success">
                        <div class="card-body">
                            <h5 class="card-title">Total Stock</h5>
                            <p class="card-text h3"><?php echo $summary->total_stock; ?></p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-white bg-warning">
                        <div class="card-body">
                            <h5 class="card-title">Low Stock</h5>
                            <p class="card-text h3"><?php echo $summary->low_stock; ?></p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-white bg-danger">
                        <div class="card-body">
                            <h5 class="card-title">Out of Stock</h5>
                            <p class="card-text h3"><?php echo $summary->out_of_stock; ?></p>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card mb-4">
                <div class="card-header">
                    <h5>Inventory Status</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Product</th>
                                    <th>SKU</th>
                                    <th>Category</th>
                                    <th>Current Stock</th>
                                    <th>Sold</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($inventory as $item): ?>
                                    <tr>
                                        <td><?php echo $item->id; ?></td>
                                        <td><?php echo htmlspecialchars($item->name); ?></td>
                                        <td><?php echo htmlspecialchars($item->sku); ?></td>
                                        <td><?php echo $item->category_name ? htmlspecialchars($item->category_name) : 'Uncategorized'; ?></td>
                                        <td><?php echo $item->stock_quantity; ?></td>
                                        <td><?php echo $item->sold_quantity ?: 0; ?></td>
                                        <td>
                                            <span class="badge bg-<?php 
                                                echo $item->stock_quantity > 10 ? 'success' : 
                                                     ($item->stock_quantity > 0 ? 'warning' : 'danger'); 
                                            ?>">
                                                <?php 
                                                echo $item->stock_quantity > 10 ? 'In Stock' : 
                                                     ($item->stock_quantity > 0 ? 'Low Stock' : 'Out of Stock'); 
                                                ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>