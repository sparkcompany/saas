<?php
// webhook.php

// AsaanPay se aane wali raw JSON request ko read karna
$rawInput = file_get_contents('php://input');
$notification = json_decode($rawInput, true);

// Logger: Debugging ke liye data log file mein save karna (Aap check kar sakte hain data aa raha hai ya nahi)
file_put_contents('payment_log.txt', $rawInput . PHP_EOL, FILE_APPEND);

if (!$notification) {
    http_response_code(400);
    die("Invalid payload");
}

// AsaanPay standard response structure ke mutabiq status aur metadata check karna
$paymentStatus = $notification['status'] ?? ''; // e.g., 'PAID' ya 'SUCCESS'
$userId = $notification['metadata']['user_id'] ?? '';

// TODO: Apni Firebase Database URL yahan enter karein (End mein slash zaroor lagayein)
$firebaseDbUrl = "https://YOUR_PROJECT_ID-default-rtdb.firebaseio.com/";

if (($paymentStatus === 'PAID' || $paymentStatus === 'SUCCESS') && !empty($userId)) {
    
    // Firebase REST API endpoint user ka node update karne ke liye
    $targetUrl = $firebaseDbUrl . "users/" . $userId . ".json";
    
    // Status ko active karne ka data structure
    $updateData = [
        "status" => "active",
        "updated_at" => time()
    ];
    
    // PATCH request config taake baki user data delete na ho, sirf status update ho
    $options = [
        'http' => [
            'header'  => "Content-Type: application/json\r\n",
            'method'  => 'PATCH',
            'content' => json_encode($updateData)
        ]
    ];
    
    $context  = stream_context_create($options);
    $firebaseResponse = file_get_contents($targetUrl, false, $context);
    
    if ($firebaseResponse !== false) {
        // Success response AsaanPay server ko send karein
        http_response_code(200);
        echo json_encode(["status" => "success", "message" => "Firebase status updated to active."]);
    } else {
        http_response_code(500);
        echo json_encode(["status" => "error", "message" => "Failed to update Firebase."]);
    }
} else {
    // Agar payment fail hui hai ya details poori nahi hain
    http_response_code(200); 
    echo json_encode(["status" => "ignored", "message" => "Payment not successful or missing User ID."]);
}
?>
