<?php
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../config/database.php';

session_start();

$response = ['success' => false, 'message' => ''];

if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    
    $db = new Database();
    $db->query("SELECT id FROM carts WHERE user_id = :user_id");
    $db->bind(':user_id', $user_id);
    $cart = $db->single();
    
    if ($cart) {
        $db->query("DELETE FROM cart_items WHERE cart_id = :cart_id");
        $db->bind(':cart_id', $cart->id);
        $db->execute();
        
        $response['success'] = true;
    } else {
        $response['message'] = 'Cart not found';
    }
} else {
    if (isset($_SESSION['cart'])) {
        unset($_SESSION['cart']);
        $response['success'] = true;
    } else {
        $response['message'] = 'Cart is already empty';
    }
}

echo json_encode($response);
?>