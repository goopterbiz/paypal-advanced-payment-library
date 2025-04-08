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

$currency = CURRENCY_CODE;

// Only one of $amount or $cart is needed, don't send both.
$amount = $inputData['amount'] ?? null;
$cart = $inputData['cart'] ?? null;

if (isset($cart)) {
    $items = [];
    // make to $items array with the required format
    foreach ($cart as $cart_item) {
        foreach (SAMPLE_ITEMS as $sample_item) {
            // Find item's name and price using id in cart data
            if ($cart_item['id'] === $sample_item['id']) {
                $items[] = [
                    'name' => $sample_item['name'],
                    'price' => $sample_item['price'],
                    'quantity' => $cart_item['quantity'],
                ];
            }
        }
    }
}

// add partner fee if you want
$partnerFee = 1;

try {
    $response = $paypalHelper->salePayment($currency, null, $partnerFee, $amount, $items, 0, null);
} catch (Exception $ex) {
    echo "Failed to make salePayment, please check ppcp_helper.log file.\n";
    die();
}

echo json_encode($response);
exit;