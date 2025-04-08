<?php
require_once '../config.php';
require_once '../helper.php';
require_once '../sample_items.php';

header('Content-Type: application/json');

// payload from frontend
$inputData = json_decode(file_get_contents('php://input'), true);

// Initialize PayPalHelper instance
$paypalHelper = PayPalHelper::getInstance(MERCHANT_ID, IS_PAYPAL_LIVE, SOFT_DESCRIPTOR);
if (!isset($paypalHelper)) {
    echo "Failed to init paypalHelper, please check ppcp_helper.log file.\n";
    die();
}

$authorizationId = $inputData['authorization_id'];

try {
    // call the capture sale order
    $response = $paypalHelper->captureSaleOrder($authorizationId);
} catch (Exception $ex) {
    echo "Failed to make captureSaleOrder, please check ppcp_helper.log file.\n";
    die();
}

echo json_encode($response);
exit;