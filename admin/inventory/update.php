<?php
require_once __DIR__ . '/../../../config/constants.php';
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../includes/admin_auth_check.php';

$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
    $stock_quantity = isset($_POST['stock_quantity']) ? intval($_POST['stock_quantity']) : 0;
    
    if ($product_id <= 0) {
        $response['message'] = 'Invalid product ID';
        echo json_encode($response);
        exit();
    }
    
    if ($stock_quantity < 0) {
        $response['message'] = 'Stock quantity cannot be negative';
        echo json_encode($response);
        exit();
    }
    
    $db = new Database();
    
    $db->query("UPDATE products SET stock_quantity = :stock_quantity WHERE id = :id");
    $db->bind(':stock_quantity', $stock_quantity);
    $db->bind(':id', $product_id);
    
    if ($db->execute()) {
        $db->query("INSERT INTO admin_logs (user_id, action, table_affected, record_id) 
                   VALUES (:user_id, 'update', 'products', :record_id)");
        $db->bind(':user_id', $_SESSION['user_id']);
        $db->bind(':record_id', $product_id);
        $db->execute();
        
        $response['success'] = true;
    } else {
        $response['message'] = 'Failed to update stock quantity';
    }
}

echo json_encode($response);
?>