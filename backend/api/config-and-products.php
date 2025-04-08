<?php
require_once '../config.php';
require_once '../helper.php';
require_once '../sample_items.php';

header('Content-Type: application/json');

define('PAYPAL_PARTNER_ATTRIBUTION_ID', 'GoopterHoldingsLtdPPCP_Ecom-qCd8nt1TY');

$config = [
    'client_id' => IS_PAYPAL_LIVE ? GOOPTER_LIVE_CLIENT_ID : GOOPTER_SANDBOX_CLIENT_ID,
    'merchant_id' => MERCHANT_ID,
    'paypal_partner_attribution_id' => PAYPAL_PARTNER_ATTRIBUTION_ID,
];
$items = SAMPLE_ITEMS;

$response = [
    'config' => $config,
    'items' => $items
];
echo json_encode($response, JSON_PRETTY_PRINT);
exit;