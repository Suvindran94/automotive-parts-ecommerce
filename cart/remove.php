<?php
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../config/database.php';

session_start();

$response = ['success' => false, 'message' => '', 'cart_count' => 0];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
    
    if ($product_id <= 0) {
        $response['message'] = 'Invalid product';
        echo json_encode($response);
        exit();
    }
    
    if (isset($_SESSION['user_id'])) {
        $user_id = $_SESSION['user_id'];
        
        $db = new Database();
        $db->query("SELECT ci.id FROM cart_items ci JOIN carts c ON ci.cart_id = c.id WHERE c.user_id = :user_id AND ci.product_id = :product_id");
        $db->bind(':user_id', $user_id);
        $db->bind(':product_id', $product_id);
        $cart_item = $db->single();
        
        if ($cart_item) {
            $db->query("DELETE FROM cart_items WHERE id = :id");
            $db->bind(':id', $cart_item->id);
            $db->execute();
            
            $db->query("SELECT COUNT(*) as count FROM cart_items ci JOIN carts c ON ci.cart_id = c.id WHERE c.user_id = :user_id");
            $db->bind(':user_id', $user_id);
            $count = $db->single()->count;
            
            $response['success'] = true;
            $response['cart_count'] = $count;
        } else {
            $response['message'] = 'Item not found in cart';
        }
    } else {
        if (isset($_SESSION['cart'][$product_id])) {
            unset($_SESSION['cart'][$product_id]);
            $count = isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0;
            
            $response['success'] = true;
            $response['cart_count'] = $count;
        } else {
            $response['message'] = 'Item not found in cart';
        }
    }
}

echo json_encode($response);
?>