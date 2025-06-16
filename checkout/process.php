<?php
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth_check.php';

session_start();

$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['user_id'])) {
    $full_name = trim($_POST['full_name']);
    $contact_phone = trim($_POST['contact_phone']);
    $shipping_address = trim($_POST['shipping_address']);
    $billing_address = trim($_POST['billing_address']) ?: $shipping_address;
    $notes = trim($_POST['notes'] ?? '');
    $payment_method = trim($_POST['payment_method']);
    
    if (empty($full_name) || empty($contact_phone) || empty($shipping_address)) {
        $response['message'] = 'Please fill in all required fields';
        echo json_encode($response);
        exit();
    }
    
    $db = new Database();
    
    $db->query("SELECT ci.*, p.price, p.stock_quantity 
               FROM cart_items ci 
               JOIN carts c ON ci.cart_id = c.id 
               JOIN products p ON ci.product_id = p.id 
               WHERE c.user_id = :user_id");
    $db->bind(':user_id', $_SESSION['user_id']);
    $cartItems = $db->resultSet();
    
    if (empty($cartItems)) {
        $response['message'] = 'Your cart is empty';
        echo json_encode($response);
        exit();
    }
    
    $db->query("SELECT SUM(ci.quantity * p.price) as total 
               FROM cart_items ci 
               JOIN carts c ON ci.cart_id = c.id 
               JOIN products p ON ci.product_id = p.id 
               WHERE c.user_id = :user_id");
    $db->bind(':user_id', $_SESSION['user_id']);
    $total = $db->single()->total ?? 0;
    
    $order_number = 'ORD-' . strtoupper(uniqid());
    
    try {
        $db->beginTransaction();
        
        $db->query("INSERT INTO orders (user_id, order_number, total_amount, status, payment_method, payment_status, shipping_address, billing_address, contact_phone) 
                   VALUES (:user_id, :order_number, :total_amount, 'pending', :payment_method, 'pending', :shipping_address, :billing_address, :contact_phone)");
        $db->bind(':user_id', $_SESSION['user_id']);
        $db->bind(':order_number', $order_number);
        $db->bind(':total_amount', $total);
        $db->bind(':payment_method', $payment_method);
        $db->bind(':shipping_address', $shipping_address);
        $db->bind(':billing_address', $billing_address);
        $db->bind(':contact_phone', $contact_phone);
        $db->execute();
        
        $order_id = $db->lastInsertId();
        
        foreach ($cartItems as $item) {
            $db->query("INSERT INTO order_items (order_id, product_id, quantity, price) 
                       VALUES (:order_id, :product_id, :quantity, :price)");
            $db->bind(':order_id', $order_id);
            $db->bind(':product_id', $item->product_id);
            $db->bind(':quantity', $item->quantity);
            $db->bind(':price', $item->price);
            $db->execute();
            
            $db->query("UPDATE products SET stock_quantity = stock_quantity - :quantity WHERE id = :id");
            $db->bind(':quantity', $item->quantity);
            $db->bind(':id', $item->product_id);
            $db->execute();
        }
        
        $db->query("DELETE ci FROM cart_items ci JOIN carts c ON ci.cart_id = c.id WHERE c.user_id = :user_id");
        $db->bind(':user_id', $_SESSION['user_id']);
        $db->execute();
        
        $db->commit();
        
        if ($payment_method === 'FPX') {
            require_once __DIR__ . '/../../includes/toyyibpay.php';
            
            $payment_url = createToyyibPayBill($order_id, $order_number, $total, $full_name, $contact_phone, $email = $_SESSION['email']);
            
            $response['success'] = true;
            $response['payment_url'] = $payment_url;
        } else {
            $response['success'] = true;
            $response['order_id'] = $order_id;
        }
    } catch (Exception $e) {
        $db->rollBack();
        $response['message'] = 'Failed to process order: ' . $e->getMessage();
    }
}

echo json_encode($response);
?>