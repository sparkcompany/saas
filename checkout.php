<?php
// checkout.php

if (!isset($_GET['user_id'])) {
    die("User ID is required to initiate payment.");
}

$userId = $_GET['user_id'];

// TODO: Apni AsaanPay Merchant Credentials yahan dalein
$merchantId = "e50c5d31-384d-4a27-89f4-738c48dca948";
$apiKey = "SPAY-ebea-e7e1-004a-8d06";
$apiEndpoint = "https://api.asaanpay.com/v1/checkout/session"; // Check documentation for exact URL

$orderId = "ORD-" . time() . "-" . $userId;
$amount = 10; // Rs. 10
$redirectUrl = "http://" . $_SERVER['HTTP_HOST'] . "/index.html"; // Payment ke baad wapis aane ke liye
$webhookUrl = "http://" . $_SERVER['HTTP_HOST'] . "/webhook.php";   // AsaanPay ka data receive karne ke liye

// Request Payload AsaanPay ke standard ke mutabiq
$payload = [
    'merchant_id'  => $merchantId,
    'order_id'     => $orderId,
    'amount'       => $amount,
    'currency'     => 'PKR',
    'redirect_url' => $redirectUrl,
    'webhook_url'  => $webhookUrl,
    'metadata'     => [
        'user_id'  => $userId
    ]
];

// cURL Call ke zariye API Request hit karna
$ch = curl_init($apiEndpoint);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Authorization: Bearer ' . $apiKey
]);

$response = curl_exec($ch);
$err = curl_error($ch);
curl_close($ch);

if ($err) {
    die("cURL Error: " . $err);
}

$result = json_decode($response, true);

// Agar session successfully create ho jaye to redirect karein
if (isset($result['checkout_url'])) {
    header("Location: " . $result['checkout_url']);
    exit();
} else {
    echo "Payment session creation failed. Error: " . ($result['message'] ?? 'Unknown Error');
}
?>
