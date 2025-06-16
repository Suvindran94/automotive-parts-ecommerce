<?php
require_once __DIR__ . '/../../../config/constants.php';
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../includes/admin_auth_check.php';

$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
    $status = isset($_POST['status']) ? trim($_POST['status']) : '';
    
    if ($order_id <= 0) {
        $response['message'] = 'Invalid order ID';
        echo json_encode($response);
        exit();
    }
    
    if (!in_array($status, ['pending', 'processing', 'shipped', 'delivered', 'cancelled'])) {
        $response['message'] = 'Invalid status';
        echo json_encode($response);
        exit();
    }
    
    $db = new Database();
    
    $db->query("UPDATE orders SET status = :status WHERE id = :id");
    $db->bind(':status', $status);
    $db->bind(':id', $order_id);
    
    if ($db->execute()) {
        if ($status === 'delivered') {
            $db->query("UPDATE shipping SET status = 'delivered', actual_delivery = CURDATE() WHERE order_id = :order_id");
            $db->bind(':order_id', $order_id);
            $db->execute();
        }
        
        $db->query("INSERT INTO admin_logs (user_id, action, table_affected, record_id) 
                   VALUES (:user_id, 'update', 'orders', :record_id)");
        $db->bind(':user_id', $_SESSION['user_id']);
        $db->bind(':record_id', $order_id);
        $db->execute();
        
        $response['success'] = true;
    } else {
        $response['message'] = 'Failed to update order status';
    }
}

echo json_encode($response);
?>