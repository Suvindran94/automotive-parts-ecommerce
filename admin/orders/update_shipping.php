<?php
require_once __DIR__ . '/../../../config/constants.php';
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../includes/admin_auth_check.php';

$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
    $tracking_number = isset($_POST['tracking_number']) ? trim($_POST['tracking_number']) : '';
    $carrier = isset($_POST['carrier']) ? trim($_POST['carrier']) : '';
    $estimated_delivery = isset($_POST['estimated_delivery']) ? trim($_POST['estimated_delivery']) : null;
    $actual_delivery = isset($_POST['actual_delivery']) ? trim($_POST['actual_delivery']) : null;
    
    if ($order_id <= 0) {
        $response['message'] = 'Invalid order ID';
        echo json_encode($response);
        exit();
    }
    
    $db = new Database();
    
    $db->query("SELECT * FROM shipping WHERE order_id = :order_id");
    $db->bind(':order_id', $order_id);
    $shipping = $db->single();
    
    if ($shipping) {
        $sql = "UPDATE shipping SET 
               tracking_number = :tracking_number, 
               carrier = :carrier, 
               estimated_delivery = :estimated_delivery";
        
        if ($actual_delivery) {
            $sql .= ", actual_delivery = :actual_delivery";
        }
        
        $sql .= " WHERE order_id = :order_id";
        
        $db->query($sql);
        $db->bind(':tracking_number', $tracking_number);
        $db->bind(':carrier', $carrier);
        $db->bind(':estimated_delivery', $estimated_delivery);
        
        if ($actual_delivery) {
            $db->bind(':actual_delivery', $actual_delivery);
        }
        
        $db->bind(':order_id', $order_id);
    } else {
        $db->query("INSERT INTO shipping (order_id, tracking_number, carrier, estimated_delivery, status) 
                   VALUES (:order_id, :tracking_number, :carrier, :estimated_delivery, 'in_transit')");
        $db->bind(':order_id', $order_id);
        $db->bind(':tracking_number', $tracking_number);
        $db->bind(':carrier', $carrier);
        $db->bind(':estimated_delivery', $estimated_delivery);
    }
    
    if ($db->execute()) {
        $db->query("INSERT INTO admin_logs (user_id, action, table_affected, record_id) 
                   VALUES (:user_id, 'update', 'shipping', :record_id)");
        $db->bind(':user_id', $_SESSION['user_id']);
        $db->bind(':record_id', $order_id);
        $db->execute();
        
        $response['success'] = true;
    } else {
        $response['message'] = 'Failed to update shipping details';
    }
}

echo json_encode($response);
?>