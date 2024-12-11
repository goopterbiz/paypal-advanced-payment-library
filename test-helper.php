<?php
require 'PayPalHelper.php';

// PayPal credentials
$merchantId = 'AHW9X43DTUZWN'; // <<<<<< Replace with your Merchant ID, please contact support@goopter.com to get onboard and receive your merchantId
// $merchantId = '6S6RCMVAH36RG'; // Production Merchant ID for testing
$isPayPalLive = false; // Set to true for PayPal live environment or false for PayPal Sandbox
// $isPayPalLive = true; // Production environment

// The Soft Descriptor is the merchant name displayed on the customer's credit card statement. Replace it with your own name, up to 22 characters.
$softDescriptor = "PPCP_Test_getIns_This_is_for_length_test"; 

// Initialize PayPalMultipartyHelper instance
$paypalHelper = PayPalHelper::getInstance($merchantId, $isPayPalLive, $softDescriptor);

// Payment information, replace with real credit card info for production, billing address is optional
$testCard = [
    'number' => '4111111111111111',
    'expiry' => '2025-12',
    'security_code' => '123',
    'name' => 'John Doe',
    'billing_address' => [
        'address_line_1' => '123 Main St',
        'address_line_2' => 'Apt 4B',
        'admin_area_2' => 'San Francisco',
        'admin_area_1' => 'CA',
        'postal_code' => '94107',
        'country_code' => 'US'
    ]
];

$cardwithoutAddress = [
    'number' => '4111111111111111',
    'expiry' => '2025-12',
    'security_code' => '123',
    'name' => 'John Doe'
];

$shippingAddress = [
    'address_line_1' => '123 Main St',
    'address_line_2' => 'Apt 4B',
    'admin_area_2' => 'San Francisco',
    'admin_area_1' => 'CA',
    'postal_code' => '94107',
    'country_code' => 'US'
];

// Test items
$testItem1 = [
    [
        "name" => "Sample Product 1",
        "price" => 2.00,
        "quantity" => 2,
        "description" => "",
        "sku" => "TS123"
    ],
    [
        "name" => "Sample Product 2",
        "price" => 5.00,
        "quantity" => 1,
        "description" => "",
        "sku" => "JN456"
    ]
];
$testItem2 = [
    [
        'name' => 'Sample Product 1',
        'price' => 5.00,
        'quantity' => 1,
        'category' => 'PHYSICAL_GOODS'
    ],
    [
        'name' => 'Sample Product 2',
        'price' => 2.50,
        'quantity' => 3,
        'category' => 'PHYSICAL_GOODS'
    ]
];

// $testItem1 = [
//     [
//         "name" => "T-shirt",
//         "price" => 1.00,
//         "quantity" => 2,
//         "description" => "Cotton T-shirt",
//         "sku" => "TS123"
//     ],
//     [
//         "name" => "Jeans",
//         "price" => 2.00,
//         "quantity" => 1,
//         "description" => "Denim jeans",
//         "sku" => "JN456"
//     ]
// ];
// $testItem2 = [
//     [
//         'name' => 'T-shirt',
//         'price' => 2.00,
//         'quantity' => 2,
//         'description' => 'Cotton T-shirt',
//         'sku' => 'TS123'
//     ],
//     [
//         'name' => 'Jeans',
//         'price' => 3.00,
//         'quantity' => 1,
//         'description' => 'Denim jeans',
//         'sku' => 'JN456'
//     ]
// ]; // Production test items

// sale order without item information
try {
    $amount = 12.00; 
    $currency = 'USD'; 
    $partnerFee = 1.00; 
    $paymentSource = [
        'card' => $testCard
    ];

    echo "Test case 1: Processing sale payment without item information...\n";
    $saleResponse = $paypalHelper->salePayment($currency, $paymentSource, $partnerFee, $amount, null, 0, null);

    if (isset($saleResponse['id']) && isset($saleResponse['status']) && $saleResponse['status'] === 'COMPLETED') {
        echo "Sale payment completed successfully!\n";
        echo "Sale Response: " . json_encode($saleResponse, JSON_PRETTY_PRINT) . "\n";
    } else {
        echo "Sale payment failed or incomplete.\n";
        echo "Sale Response: " . json_encode($saleResponse, JSON_PRETTY_PRINT) . "\n";
    }
} catch (Exception $e) {
    echo "An error occurred: " . $e->getMessage() . "\n";
}

// create and capture order without item information
try {
    $amount = 10.00; 
    $currency = 'USD'; 
    $partnerFee = 2.00; 
    $paymentSource = [
        'card' => $testCard
    ];

    echo "Test case 2: Creating and Authorizing payment without item information...\n";
    $createResponse = $paypalHelper->createOrder($currency, $paymentSource, $partnerFee, $amount, null, 0, null);

    if (isset($createResponse['purchase_units'][0]['payments']['authorizations'][0]['id'])) {
        $authorizationId = $createResponse['purchase_units'][0]['payments']['authorizations'][0]['id'];
        echo "Payment authorized successfully!\n";
        echo "Authorization Response: " . json_encode($createResponse, JSON_PRETTY_PRINT) . "\n";

        echo "Test case 3: Capturing payment without item information...\n";
        $captureResponse = $paypalHelper->captureOrder($authorizationId, $partnerFee, $currency);

        if (isset($captureResponse['status']) && $captureResponse['status'] === 'COMPLETED') {
            echo "Payment captured successfully!\n";
            echo "Capture Response: " . json_encode($captureResponse, JSON_PRETTY_PRINT) . "\n";
        } else {
            echo "Failed to capture payment.\n";
            echo "Capture Response: " . json_encode($captureResponse, JSON_PRETTY_PRINT) . "\n";
        }
    } else {
        echo "Failed to authorize payment.\n";
        echo "Authorization Response: " . json_encode($createResponse, JSON_PRETTY_PRINT) . "\n";
    }
} catch (Exception $e) {
    echo "An error occurred: " . $e->getMessage() . "\n";
}

// sale order with item information
try {
    $items = $testItem1; 
    $shippingFee = 0.50; 
    $currency = 'USD'; 
    $partnerFee = 1.50; 
    $paymentSource = [
        'card' => $testCard
    ];

    echo "Test case 4: Processing sale payment with items information...\n";
    $saleResponse = $paypalHelper->salePayment($currency, $paymentSource, $partnerFee, null, $items, $shippingFee, $shippingAddress);

    if (isset($saleResponse['id']) && isset($saleResponse['status']) && $saleResponse['status'] === 'COMPLETED') {
        echo "Sale payment completed successfully!\n";
        echo "Sale Response: " . json_encode($saleResponse, JSON_PRETTY_PRINT) . "\n"; 
    } else {
        echo "Sale payment failed or incomplete.\n";
        echo "Sale Response: " . json_encode($saleResponse, JSON_PRETTY_PRINT) . "\n";
    }
} catch (Exception $e) {
    echo "An error occurred: " . $e->getMessage() . "\n";
}

// create order with item
try {
    $items = $testItem2; 
    $currency = 'USD'; 
    $partnerFee = 0.50; 
    $shippingFee = 1.00; 

    $paymentSource = [
        'card' => $testCard
    ];

    echo "Test case 5: Creating and Authorizing order with items...\n";
    $orderResponse = $paypalHelper->createOrder($currency, $paymentSource, $partnerFee, null, $items, $shippingFee, $shippingAddress);

    if (isset($orderResponse['id'])) {
        echo "Order created successfully! Order ID: " . $orderResponse['id'] . "\n";
        echo "Order Response: " . json_encode($orderResponse, JSON_PRETTY_PRINT); 

        $authorizationId = $orderResponse['purchase_units'][0]['payments']['authorizations'][0]['id'];
        echo "Test case 6: Capturing order with item information...\n";

        $captureResponse = $paypalHelper->captureOrder($authorizationId, $partnerFee, $currency);

        if (isset($captureResponse['id'])) {
            echo "Order captured successfully!\n";
            echo "Capture Response: " . json_encode($captureResponse, JSON_PRETTY_PRINT) . "\n";
        } else {
            echo "Failed to capture order.\n";
            echo "Capture Response: " . json_encode($captureResponse, JSON_PRETTY_PRINT) . "\n";
        }
    } else {
        echo "Failed to create order.\n";
        echo "Order Response: " . json_encode($orderResponse, JSON_PRETTY_PRINT) . "\n";
    }
} catch (Exception $e) {
    echo "An error occurred: " . $e->getMessage() . "\n";
}

// refund
try {
    echo "Test case 7: Refund Payment\n";
    echo "Creating a sale payment...\n";
    $currency = "USD";
    $amount = 10.00; 
    $partnerFee = 1.50; 
    $shippingFee = 0.50; 

    $paymentSource = [
        "card" => $testCard
    ];

    $response = $paypalHelper->salePayment($currency, $paymentSource, $partnerFee, $amount, null, $shippingFee, null);

    if (isset($response['error'])) {
        throw new Exception("Sale payment error: " . $response['error_description']);
    }

    $captureId = $response['purchase_units'][0]['payments']['captures'][0]['id'];
    echo "Capture ID: $captureId\n";

    echo "Test case 8: Partial Refund\n";
    $partialRefundAmount = 7.00; 
    $refundResponse = $paypalHelper->refundPayment($captureId, $partialRefundAmount, $currency);

    echo "Partial Refund Response: " . json_encode($refundResponse, JSON_PRETTY_PRINT) . "\n";

    if (isset($refundResponse['status']) && $refundResponse['status'] === 'COMPLETED') {
        echo "Refund successfully completed.\n";
    } else {
        echo "Refund status: " . ($refundResponse['status'] ?? 'UNKNOWN') . "\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

// create order without billing and shipping address
try {
    $items = $testItem2; 
    $currency = 'USD'; 
    $partnerFee = 0.50; 
    $shippingFee = 1.00; 

    $paymentSource = [
        'card' => $cardwithoutAddress
    ];

    echo "Test case 8: Creating and Authorizing order with items information and without address...\n";
    $orderResponse = $paypalHelper->createOrder($currency, $paymentSource, $partnerFee, null, $items, $shippingFee);

    if (isset($orderResponse['id'])) {
        echo "Order created successfully! Order ID: " . $orderResponse['id'] . "\n";
        echo "Order Response: " . json_encode($orderResponse, JSON_PRETTY_PRINT); 

        $authorizationId = $orderResponse['purchase_units'][0]['payments']['authorizations'][0]['id'];
        echo "Test case 9: Capturing order with item information and without address...\n";

        $captureResponse = $paypalHelper->captureOrder($authorizationId, $partnerFee, $currency);

        if (isset($captureResponse['id'])) {
            echo "Order captured successfully!\n";
            echo "Capture Response: " . json_encode($captureResponse, JSON_PRETTY_PRINT) . "\n";
        } else {
            echo "Failed to capture order.\n";
            echo "Capture Response: " . json_encode($captureResponse, JSON_PRETTY_PRINT) . "\n";
        }
    } else {
        echo "Failed to create order.\n";
        echo "Order Response: " . json_encode($orderResponse, JSON_PRETTY_PRINT) . "\n";
    }
} catch (Exception $e) {
    echo "An error occurred: " . $e->getMessage() . "\n";
}

