<?php
require_once __DIR__ . '/../../../config/constants.php';
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../includes/admin_auth_check.php';

$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
    
    if ($product_id <= 0) {
        $response['message'] = 'Invalid product ID';
        echo json_encode($response);
        exit();
    }
    
    $db = new Database();
    $db->query("SELECT stock_quantity FROM products WHERE id = :id");
    $db->bind(':id', $product_id);
    $product = $db->single();
    
    if ($product) {
        $response['success'] = true;
        $response['stock_quantity'] = $product->stock_quantity;
    } else {
        $response['message'] = 'Product not found';
    }
}

echo json_encode($response);
?>