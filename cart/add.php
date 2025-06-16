<?php
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../config/database.php';

session_start();

$response = ['success' => false, 'message' => '', 'cart_count' => 0];

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
        
        $db->query("SELECT id FROM carts WHERE user_id = :user_id");
        $db->bind(':user_id', $user_id);
        $cart = $db->single();
        
        if (!$cart) {
            $db->query("INSERT INTO carts (user_id) VALUES (:user_id)");
            $db->bind(':user_id', $user_id);
            $db->execute();
            $cart_id = $db->lastInsertId();
        } else {
            $cart_id = $cart->id;
        }
        
        $db->query("SELECT * FROM cart_items WHERE cart_id = :cart_id AND product_id = :product_id");
        $db->bind(':cart_id', $cart_id);
        $db->bind(':product_id', $product_id);
        $existing_item = $db->single();
        
        if ($existing_item) {
            $new_quantity = $existing_item->quantity + $quantity;
            if ($new_quantity > $product->stock_quantity) {
                $response['message'] = 'Not enough stock available';
                echo json_encode($response);
                exit();
            }
            
            $db->query("UPDATE cart_items SET quantity = :quantity WHERE id = :id");
            $db->bind(':quantity', $new_quantity);
            $db->bind(':id', $existing_item->id);
            $db->execute();
        } else {
            $db->query("INSERT INTO cart_items (cart_id, product_id, quantity) VALUES (:cart_id, :product_id, :quantity)");
            $db->bind(':cart_id', $cart_id);
            $db->bind(':product_id', $product_id);
            $db->bind(':quantity', $quantity);
            $db->execute();
        }
        
        $db->query("SELECT COUNT(*) as count FROM cart_items WHERE cart_id = :cart_id");
        $db->bind(':cart_id', $cart_id);
        $count = $db->single()->count;
    } else {
        if (!isset($_SESSION['cart'])) {
            $_SESSION['cart'] = [];
        }
        
        if (isset($_SESSION['cart'][$product_id])) {
            $new_quantity = $_SESSION['cart'][$product_id] + $quantity;
            if ($new_quantity > $product->stock_quantity) {
                $response['message'] = 'Not enough stock available';
                echo json_encode($response);
                exit();
            }
            $_SESSION['cart'][$product_id] = $new_quantity;
        } else {
            $_SESSION['cart'][$product_id] = $quantity;
        }
        
        $count = count($_SESSION['cart']);
    }
    
    $response['success'] = true;
    $response['cart_count'] = $count;
}

echo json_encode($response);
?>