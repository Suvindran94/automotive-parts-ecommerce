<?php
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth_check.php';

session_start();

$status_id = isset($_GET['status_id']) ? $_GET['status_id'] : null;

if ($status_id) {
    $endpoint = TOYYIBPAY_SANDBOX ? 'https://dev.toyyibpay.com/' : 'https://toyyibpay.com/';
    
    $data = [
        'userSecretKey' => TOYYIBPAY_USER_SECRET_KEY,
        'billCode' => $status_id
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $endpoint . 'index.php/api/getBillTransactions');
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $result = curl_exec($ch);
    $info = curl_getinfo($ch);
    curl_close($ch);
    
    $response = json_decode($result, true);
    
    if (isset($response[0]['billpaymentStatus']) && $response[0]['billpaymentStatus'] === '1') {
        $db = new Database();
        
        $order_number = $response[0]['bill_externalReferenceNo'];
        
        $db->query("SELECT id FROM orders WHERE order_number = :order_number");
        $db->bind(':order_number', $order_number);
        $order = $db->single();
        
        if ($order) {
            $db->query("UPDATE orders SET status = 'processing', payment_status = 'completed' WHERE id = :id");
            $db->bind(':id', $order->id);
            $db->execute();
            
            $db->query("UPDATE payments SET status = 'completed', transaction_id = :transaction_id, payment_date = NOW() WHERE order_id = :order_id");
            $db->bind(':transaction_id', $response[0]['billpaymentInvoiceNo']);
            $db->bind(':order_id', $order->id);
            $db->execute();
            
            echo '<script>window.location.href = "' . BASE_URL . 'customer/orders/view.php?id=' . $order->id . '";</script>';
            exit();
        }
    }
}

echo '<script>window.location.href = "' . BASE_URL . 'customer/orders/";</script>';
?>