<?php
require_once __DIR__ . '/../config/constants.php';

function createToyyibPayBill($order_id, $order_number, $amount, $customer_name, $customer_phone, $customer_email) {
    $endpoint = TOYYIBPAY_SANDBOX ? 'https://dev.toyyibpay.com/' : 'https://toyyibpay.com/';
    
    $data = [
        'userSecretKey' => TOYYIBPAY_USER_SECRET_KEY,
        'categoryCode' => TOYYIBPAY_CATEGORY_CODE,
        'billName' => 'AutoParts Order #' . $order_number,
        'billDescription' => 'Payment for order #' . $order_number,
        'billPriceSetting' => 1,
        'billPayorInfo' => 1,
        'billAmount' => $amount * 100,
        'billReturnUrl' => BASE_URL . 'checkout/return.php',
        'billCallbackUrl' => BASE_URL . 'checkout/callback.php',
        'billExternalReferenceNo' => $order_number,
        'billTo' => $customer_name,
        'billEmail' => $customer_email,
        'billPhone' => $customer_phone,
        'billContentEmail' => 'Thank you for purchasing from AutoParts!',
        'billChargeToCustomer' => 1
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $endpoint . 'index.php/api/createBill');
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $result = curl_exec($ch);
    $info = curl_getinfo($ch);
    curl_close($ch);
    
    $response = json_decode($result, true);
    
    if (isset($response[0]['BillCode'])) {
        $bill_code = $response[0]['BillCode'];
        
        $db = new Database();
        $db->query("INSERT INTO payments (order_id, amount, payment_method, status) VALUES (:order_id, :amount, 'FPX', 'pending')");
        $db->bind(':order_id', $order_id);
        $db->bind(':amount', $amount);
        $db->execute();
        $payment_id = $db->lastInsertId();
        
        $_SESSION['payment_id'] = $payment_id;
        
        return $endpoint . $bill_code;
    }
    
    return false;
}
?>