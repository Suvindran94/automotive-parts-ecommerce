<?php
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';

$data = json_decode(file_get_contents('php://input'), true);

if (isset($data['refno']) && isset($data['status']) && $data['status'] === '1') {
    $order_number = $data['refno'];
    $transaction_id = $data['transaction_id'];
    
    $db = new Database();
    
    $db->query("SELECT id FROM orders WHERE order_number = :order_number");
    $db->bind(':order_number', $order_number);
    $order = $db->single();
    
    if ($order) {
        $db->query("UPDATE orders SET status = 'processing', payment_status = 'completed' WHERE id = :id");
        $db->bind(':id', $order->id);
        $db->execute();
        
        $db->query("UPDATE payments SET status = 'completed', transaction_id = :transaction_id, payment_date = NOW() WHERE order_id = :order_id");
        $db->bind(':transaction_id', $transaction_id);
        $db->bind(':order_id', $order->id);
        $db->execute();
    }
}

http_response_code(200);
?>