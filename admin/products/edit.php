<?php
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/admin_auth_check.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo '<script>window.location.href = "' . BASE_URL . 'admin/products/";</script>';
    exit();
}

$product_id = intval($_GET['id']);

$db = new Database();

$db->query("SELECT * FROM products WHERE id = :id");
$db->bind(':id', $product_id);
$product = $db->single();

if (!$product) {
    echo '<script>window.location.href = "' . BASE_URL . 'admin/products/";</script>';
    exit();
}

$db->query("SELECT * FROM categories");
$categories = $db->resultSet();

$db->query("SELECT * FROM product_images WHERE product_id = :product_id");
$db->bind(':product_id', $product_id);
$product_images = $db->resultSet();

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_data = [
        'name' => trim($_POST['name']),
        'description' => trim($_POST['description']),
        'category_id' => isset($_POST['category_id']) ? intval($_POST['category_id']) : null,
        'price' => trim($_POST['price']),
        'stock_quantity' => trim($_POST['stock_quantity']),
        'sku' => trim($_POST['sku']),
        'status' => trim($_POST['status']),
        'image_url' => $product->image_url
    ];
    
    if (empty($product_data['name'])) {
        $errors['name'] = 'Product name is required';
    }
    
    if (empty($product_data['description'])) {
        $errors['description'] = 'Description is required';
    }
    
    if (!is_numeric($product_data['price']) || $product_data['price'] <= 0) {
        $errors['price'] = 'Price must be a positive number';
    }
    
    if (!is_numeric($product_data['stock_quantity']) || $product_data['stock_quantity'] < 0) {
        $errors['stock_quantity'] = 'Stock quantity must be a non-negative number';
    }
    
    if (empty($product_data['sku'])) {
        $errors['sku'] = 'SKU is required';
    } else {
        $db->query("SELECT id FROM products WHERE sku = :sku AND id != :id");
        $db->bind(':sku', $product_data['sku']);
        $db->bind(':id', $product_id);
        if ($db->single()) {
            $errors['sku'] = 'SKU already exists';
        }
    }
    
    if (empty($errors)) {
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = __DIR__ . '/../../assets/images/products/';
            $file_ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
            $allowed_ext = ['jpg', 'jpeg', 'png', 'gif'];
            
            if (in_array($file_ext, $allowed_ext)) {
                $new_image_url = uniqid('product_', true) . '.' . $file_ext;
                if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_dir . $new_image_url)) {
                    if ($product->image_url !== 'default.png') {
                        @unlink($upload_dir . $product->image_url);
                    }
                    $product_data['image_url'] = $new_image_url;
                }
            }
        }
        
        $db->query("UPDATE products SET 
                   name = :name, 
                   description = :description, 
                   category_id = :category_id, 
                   price = :price, 
                   stock_quantity = :stock_quantity, 
                   sku = :sku, 
                   image_url = :image_url, 
                   status = :status 
                   WHERE id = :id");
        
        $db->bind(':name', $product_data['name']);
        $db->bind(':description', $product_data['description']);
        $db->bind(':category_id', $product_data['category_id']);
        $db->bind(':price', $product_data['price']);
        $db->bind(':stock_quantity', $product_data['stock_quantity']);
        $db->bind(':sku', $product_data['sku']);
        $db->bind(':image_url', $product_data['image_url']);
        $db->bind(':status', $product_data['status']);
        $db->bind(':id', $product_id);
        
        if ($db->execute()) {
            if (!empty($_FILES['additional_images']['name'][0])) {
                $upload_dir = __DIR__ . '/../../assets/images/products/';
                $allowed_ext = ['jpg', 'jpeg', 'png', 'gif'];
                
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
                       VALUES (:user_id, 'update', 'products', :record_id)");
            $db->bind(':user_id', $_SESSION['user_id']);
            $db->bind(':record_id', $product_id);
            $db->execute();
            
            echo '<script>
                Swal.fire({
                    title: "Success",
                    text: "Product updated successfully",
                    icon: "success",
                    confirmButtonText: "OK"
                }).then(() => {
                    window.location.href = "' . BASE_URL . 'admin/products/";
                });
            </script>';
            exit();
        } else {
            $errors['general'] = 'Failed to update product. Please try again.';
        }
    }
} else {
    $product_data = (array)$product;
}

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Edit Product</h1>
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
                                <input type="text" class="form-control <?php echo isset($errors['name']) ? 'is-invalid' : ''; ?>" id="name" name="name" value="<?php echo htmlspecialchars($product_data['name']); ?>" required>
                                <?php if (isset($errors['name'])): ?>
                                    <div class="invalid-feedback"><?php echo $errors['name']; ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-6">
                                <label for="sku" class="form-label">SKU</label>
                                <input type="text" class="form-control <?php echo isset($errors['sku']) ? 'is-invalid' : ''; ?>" id="sku" name="sku" value="<?php echo htmlspecialchars($product_data['sku']); ?>" required>
                                <?php if (isset($errors['sku'])): ?>
                                    <div class="invalid-feedback"><?php echo $errors['sku']; ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control <?php echo isset($errors['description']) ? 'is-invalid' : ''; ?>" id="description" name="description" rows="3" required><?php echo htmlspecialchars($product_data['description']); ?></textarea>
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
                                        <option value="<?php echo $category->id; ?>" <?php echo $product_data['category_id'] == $category->id ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($category->name); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label for="price" class="form-label">Price (RM)</label>
                                <input type="number" step="0.01" class="form-control <?php echo isset($errors['price']) ? 'is-invalid' : ''; ?>" id="price" name="price" value="<?php echo htmlspecialchars($product_data['price']); ?>" required>
                                <?php if (isset($errors['price'])): ?>
                                    <div class="invalid-feedback"><?php echo $errors['price']; ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-4">
                                <label for="stock_quantity" class="form-label">Stock Quantity</label>
                                <input type="number" class="form-control <?php echo isset($errors['stock_quantity']) ? 'is-invalid' : ''; ?>" id="stock_quantity" name="stock_quantity" value="<?php echo htmlspecialchars($product_data['stock_quantity']); ?>" required>
                                <?php if (isset($errors['stock_quantity'])): ?>
                                    <div class="invalid-feedback"><?php echo $errors['stock_quantity']; ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="status" class="form-label">Status</label>
                                <select class="form-select" id="status" name="status">
                                    <option value="active" <?php echo $product_data['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                                    <option value="inactive" <?php echo $product_data['status'] === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="image" class="form-label">Main Image</label>
                                <input type="file" class="form-control" id="image" name="image" accept="image/*">
                                <?php if ($product_data['image_url']): ?>
                                    <div class="mt-2">
                                        <img src="<?php echo BASE_URL . 'assets/images/products/' . $product_data['image_url']; ?>" alt="Current Image" width="100" class="img-thumbnail">
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="additional_images" class="form-label">Additional Images</label>
                            <input type="file" class="form-control" id="additional_images" name="additional_images[]" multiple accept="image/*">
                            
                            <?php if (!empty($product_images)): ?>
                                <div class="mt-3">
                                    <h6>Current Additional Images</h6>
                                    <div class="row">
                                        <?php foreach ($product_images as $image): ?>
                                            <div class="col-md-2 mb-3">
                                                <div class="position-relative">
                                                    <img src="<?php echo BASE_URL . 'assets/images/products/' . $image->image_url; ?>" alt="Additional Image" class="img-thumbnail" width="100">
                                                    <button type="button" class="btn btn-sm btn-danger position-absolute top-0 end-0 delete-image" data-id="<?php echo $image->id; ?>">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">Update Product</button>
                    </form>
                </div>
            </div>
        </main>
    </div>
</div>

<script>
$(document).ready(function() {
    $('.delete-image').click(function() {
        const imageId = $(this).data('id');
        
        Swal.fire({
            title: 'Are you sure?',
            text: "You won't be able to revert this!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '<?php echo BASE_URL; ?>admin/products/delete_image.php',
                    method: 'POST',
                    data: { id: imageId },
                    success: function(response) {
                        if (response.success) {
                            Swal.fire(
                                'Deleted!',
                                'Image has been deleted.',
                                'success'
                            ).then(() => {
                                window.location.reload();
                            });
                        } else {
                            Swal.fire(
                                'Error!',
                                response.message,
                                'error'
                            );
                        }
                    },
                    error: function() {
                        Swal.fire(
                            'Error!',
                            'Something went wrong.',
                            'error'
                        );
                    }
                });
            }
        });
    });
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>