<?php
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/admin_auth_check.php';

$db = new Database();

$db->query("SELECT * FROM categories");
$categories = $db->resultSet();

$errors = [];
$product = [
    'name' => '',
    'description' => '',
    'category_id' => '',
    'price' => '',
    'stock_quantity' => '',
    'sku' => '',
    'status' => 'active'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product['name'] = trim($_POST['name']);
    $product['description'] = trim($_POST['description']);
    $product['category_id'] = isset($_POST['category_id']) ? intval($_POST['category_id']) : null;
    $product['price'] = trim($_POST['price']);
    $product['stock_quantity'] = trim($_POST['stock_quantity']);
    $product['sku'] = trim($_POST['sku']);
    $product['status'] = trim($_POST['status']);
    
    if (empty($product['name'])) {
        $errors['name'] = 'Product name is required';
    }
    
    if (empty($product['description'])) {
        $errors['description'] = 'Description is required';
    }
    
    if (!is_numeric($product['price']) || $product['price'] <= 0) {
        $errors['price'] = 'Price must be a positive number';
    }
    
    if (!is_numeric($product['stock_quantity']) || $product['stock_quantity'] < 0) {
        $errors['stock_quantity'] = 'Stock quantity must be a non-negative number';
    }
    
    if (empty($product['sku'])) {
        $errors['sku'] = 'SKU is required';
    } else {
        $db->query("SELECT id FROM products WHERE sku = :sku");
        $db->bind(':sku', $product['sku']);
        if ($db->single()) {
            $errors['sku'] = 'SKU already exists';
        }
    }
    
    if (empty($errors)) {
        $image_url = 'default.png';
        
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = __DIR__ . '/../../assets/images/products/';
            $file_ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
            $allowed_ext = ['jpg', 'jpeg', 'png', 'gif'];
            
            if (in_array($file_ext, $allowed_ext)) {
                $image_url = uniqid('product_', true) . '.' . $file_ext;
                move_uploaded_file($_FILES['image']['tmp_name'], $upload_dir . $image_url);
            }
        }
        
        $db->query("INSERT INTO products (name, description, category_id, price, stock_quantity, sku, image_url, status) 
                   VALUES (:name, :description, :category_id, :price, :stock_quantity, :sku, :image_url, :status)");
        $db->bind(':name', $product['name']);
        $db->bind(':description', $product['description']);
        $db->bind(':category_id', $product['category_id']);
        $db->bind(':price', $product['price']);
        $db->bind(':stock_quantity', $product['stock_quantity']);
        $db->bind(':sku', $product['sku']);
        $db->bind(':image_url', $image_url);
        $db->bind(':status', $product['status']);
        
        if ($db->execute()) {
            $product_id = $db->lastInsertId();
            
            if (!empty($_FILES['additional_images']['name'][0])) {
                $upload_dir = __DIR__ . '/../../assets/images/products/';
                
                foreach ($_FILES['additional_images']['tmp_name'] as $key => $tmp_name) {
                    if ($_FILES['additional_images']['error'][$key] === UPLOAD_ERR_OK) {
                        $file_ext = strtolower(pathinfo($_FILES['additional_images']['name'][$key], PATHINFO_EXTENSION));
                        
                        if (in_array($file_ext, $allowed_ext)) {
                            $image_name = uniqid('product_' . $product_id . '_', true) . '.' . $file_ext;
                            move_uploaded_file($tmp_name, $upload_dir . $image_name);
                            
                            $db->query("INSERT INTO product_images (product_id, image_url) VALUES (:product_id, :image_url)");
                            $db->bind(':product_id', $product_id);
                            $db->bind(':image_url', $image_name);
                            $db->execute();
                        }
                    }
                }
            }
            
            $db->query("INSERT INTO admin_logs (user_id, action, table_affected, record_id) 
                       VALUES (:user_id, 'create', 'products', :record_id)");
            $db->bind(':user_id', $_SESSION['user_id']);
            $db->bind(':record_id', $product_id);
            $db->execute();
            
            echo '<script>
                Swal.fire({
                    title: "Success",
                    text: "Product added successfully",
                    icon: "success",
                    confirmButtonText: "OK"
                }).then(() => {
                    window.location.href = "' . BASE_URL . 'admin/products/";
                });
            </script>';
            exit();
        } else {
            $errors['general'] = 'Failed to add product. Please try again.';
        }
    }
}

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Add Product</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="<?php echo BASE_URL; ?>admin/products/" class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-arrow-left"></i> Back to Products
                    </a>
                </div>
            </div>
            
            <?php if (!empty($errors['general'])): ?>
                <div class="alert alert-danger"><?php echo $errors['general']; ?></div>
            <?php endif; ?>
            
            <div class="card">
                <div class="card-body">
                    <form method="POST" enctype="multipart/form-data">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="name" class="form-label">Product Name</label>
                                <input type="text" class="form-control <?php echo isset($errors['name']) ? 'is-invalid' : ''; ?>" id="name" name="name" value="<?php echo htmlspecialchars($product['name']); ?>" required>
                                <?php if (isset($errors['name'])): ?>
                                    <div class="invalid-feedback"><?php echo $errors['name']; ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-6">
                                <label for="sku" class="form-label">SKU</label>
                                <input type="text" class="form-control <?php echo isset($errors['sku']) ? 'is-invalid' : ''; ?>" id="sku" name="sku" value="<?php echo htmlspecialchars($product['sku']); ?>" required>
                                <?php if (isset($errors['sku'])): ?>
                                    <div class="invalid-feedback"><?php echo $errors['sku']; ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control <?php echo isset($errors['description']) ? 'is-invalid' : ''; ?>" id="description" name="description" rows="3" required><?php echo htmlspecialchars($product['description']); ?></textarea>
                            <?php if (isset($errors['description'])): ?>
                                <div class="invalid-feedback"><?php echo $errors['description']; ?></div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label for="category_id" class="form-label">Category</label>
                                <select class="form-select" id="category_id" name="category_id">
                                    <option value="">Select Category</option>
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?php echo $category->id; ?>" <?php echo $product['category_id'] == $category->id ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($category->name); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label for="price" class="form-label">Price (RM)</label>
                                <input type="number" step="0.01" class="form-control <?php echo isset($errors['price']) ? 'is-invalid' : ''; ?>" id="price" name="price" value="<?php echo htmlspecialchars($product['price']); ?>" required>
                                <?php if (isset($errors['price'])): ?>
                                    <div class="invalid-feedback"><?php echo $errors['price']; ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-4">
                                <label for="stock_quantity" class="form-label">Stock Quantity</label>
                                <input type="number" class="form-control <?php echo isset($errors['stock_quantity']) ? 'is-invalid' : ''; ?>" id="stock_quantity" name="stock_quantity" value="<?php echo htmlspecialchars($product['stock_quantity']); ?>" required>
                                <?php if (isset($errors['stock_quantity'])): ?>
                                    <div class="invalid-feedback"><?php echo $errors['stock_quantity']; ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="status" class="form-label">Status</label>
                                <select class="form-select" id="status" name="status">
                                    <option value="active" <?php echo $product['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                                    <option value="inactive" <?php echo $product['status'] === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="image" class="form-label">Main Image</label>
                                <input type="file" class="form-control" id="image" name="image" accept="image/*">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="additional_images" class="form-label">Additional Images</label>
                            <input type="file" class="form-control" id="additional_images" name="additional_images[]" multiple accept="image/*">
                        </div>
                        
                        <button type="submit" class="btn btn-primary">Save Product</button>
                    </form>
                </div>
            </div>
        </main>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>