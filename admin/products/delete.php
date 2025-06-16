<?php
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/admin_auth_check.php';

$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    
    if ($id <= 0) {
        $response['message'] = 'Invalid product ID';
        echo json_encode($response);
        exit();
    }
    
    $db = new Database();
    
    try {
        $db->beginTransaction();
        
        $db->query("SELECT image_url FROM products WHERE id = :id");
        $db->bind(':id', $id);
        $product = $db->single();
        
        if (!$product) {
            $response['message'] = 'Product not found';
            echo json_encode($response);
            exit();
        }
        
        $db->query("SELECT image_url FROM product_images WHERE product_id = :product_id");
        $db->bind(':product_id', $id);
        $images = $db->resultSet();
        
        $db->query("DELETE FROM products WHERE id = :id");
        $db->bind(':id', $id);
        $db->execute();
        
        $db->query("DELETE FROM product_images WHERE product_id = :product_id");
        $db->bind(':product_id', $id);
        $db->execute();
        
        $db->query("DELETE FROM cart_items WHERE product_id = :product_id");
        $db->bind(':product_id', $id);
        $db->execute();
        
        $upload_dir = __DIR__ . '/../../assets/images/products/';
        
        if ($product->image_url !== 'default.png') {
            @unlink($upload_dir . $product->image_url);
        }
        
        foreach ($images as $image) {
            @unlink($upload_dir . $image->image_url);
        }
        
        $db->query("INSERT INTO admin_logs (user_id, action, table_affected, record_id) 
                   VALUES (:user_id, 'delete', 'products', :record_id)");
        $db->bind(':user_id', $_SESSION['user_id']);
        $db->bind(':record_id', $id);
        $db->execute();
        
        $db->commit();
        
        $response['success'] = true;
    } catch (Exception $e) {
        $db->rollBack();
        $response['message'] = 'Failed to delete product: ' . $e->getMessage();
    }
}

echo json_encode($response);
?>