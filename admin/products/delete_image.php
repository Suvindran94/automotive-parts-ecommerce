<?php
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/admin_auth_check.php';

$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    
    if ($id <= 0) {
        $response['message'] = 'Invalid image ID';
        echo json_encode($response);
        exit();
    }
    
    $db = new Database();
    
    $db->query("SELECT image_url, product_id FROM product_images WHERE id = :id");
    $db->bind(':id', $id);
    $image = $db->single();
    
    if (!$image) {
        $response['message'] = 'Image not found';
        echo json_encode($response);
        exit();
    }
    
    $upload_dir = __DIR__ . '/../../assets/images/products/';
    
    if (file_exists($upload_dir . $image->image_url)) {
        if (unlink($upload_dir . $image->image_url)) {
            $db->query("DELETE FROM product_images WHERE id = :id");
            $db->bind(':id', $id);
            
            if ($db->execute()) {
                $db->query("INSERT INTO admin_logs (user_id, action, table_affected, record_id) 
                           VALUES (:user_id, 'delete', 'product_images', :record_id)");
                $db->bind(':user_id', $_SESSION['user_id']);
                $db->bind(':record_id', $id);
                $db->execute();
                
                $response['success'] = true;
            } else {
                $response['message'] = 'Failed to delete image record from database';
            }
        } else {
            $response['message'] = 'Failed to delete image file';
        }
    } else {
        $db->query("DELETE FROM product_images WHERE id = :id");
        $db->bind(':id', $id);
        
        if ($db->execute()) {
            $response['success'] = true;
        } else {
            $response['message'] = 'Failed to delete image record from database';
        }
    }
}

echo json_encode($response);
?>