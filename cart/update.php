<?php
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../config/database.php';

session_start();

$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
    $quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 1;
    
    if ($product_id <= 0 || $quantity <= 0) {
        $response['message'] = 'Invalid product or quantity';
        echo json_encode($response);
        exit();
    }
    
    $db = new Database();
    $db->query("SELECT * FROM products WHERE id = :id AND status = 'active'");
    $db->bind(':id', $product_id);
    $product = $db->single();
    
    if (!$product) {
        $response['message'] = 'Product not found';
        echo json_encode($response);
        exit();
    }
    
    if ($quantity > $product->stock_quantity) {
        $response['message'] = 'Not enough stock available';
        echo json_encode($response);
        exit();
    }
    
    if (isset($_SESSION['user_id'])) {
        $user_id = $_SESSION['user_id'];
        
        $db->query("SELECT ci.id FROM cart_items ci JOIN carts c ON ci.cart_id = c.id WHERE c.user_id = :user_id AND ci.product_id = :product_id");
        $db->bind(':user_id', $user_id);
        $db->bind(':product_id', $product_id);
        $cart_item = $db->single();
        
        if ($cart_item) {
            $db->query("UPDATE cart_items SET quantity = :quantity WHERE id = :id");
            $db->bind(':quantity', $quantity);
            $db->bind(':id', $cart_item->id);
            $db->execute();
            
            $response['success'] = true;
        } else {
            $response['message'] = 'Item not found in cart';
        }
    } else {
        if (isset($_SESSION['cart'][$product_id])) {
            $_SESSION['cart'][$product_id] = $quantity;
            $response['success'] = true;
        } else {
            $response['message'] = 'Item not found in cart';
        }
    }
}

echo json_encode($response);
?>