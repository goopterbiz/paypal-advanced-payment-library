<?php

define('GOOPTER_SANDBOX_CLIENT_ID', 'AUCjmZviwYLNMzMOXAxGgfxIB06HO4QaG4tGTiK7VjErSbGiJUcTqTNhvR3X0k58-ROEPj3PWGpBwNJ_');
define('GOOPTER_LIVE_CLIENT_ID', 'ATYIBuWDPfFXuRoYNYx2spSQNyTOi0fm_zLo8G55Pe6oF5gLBKmOZJm7bpDTSlDXfsbiu8-qCd8nt1TY');

class PayPalHelper {
    private static $instance = null;
    private $logFile = 'ppcp_helper.log';
    private $isPaypalLive; // Set to true for PayPal live environment or false for PayPal Sandbox
    private $apiUrl;
    private $ppcpAssertion; 
    private $merchantId; 
    private $softDescriptor; // length limit: up to 22 characters

    /**
     * Constructor to initialize PayPalHelper with credentials and environment details.
     *
     * @param string $merchantId PayPal merchant ID.
     * @param bool $isPayPalLive Indicates whether to use the live PayPal environment.
     * @param string $softDescriptor soft descriptor for the transaction.
     */
    private function __construct($merchantId, $isPayPalLive, $softDescriptor) {
        $this->merchantId = $merchantId;
        $this->isPaypalLive = $isPayPalLive;
        $this->softDescriptor = $softDescriptor;
    }

    /**
     * Singleton method to get or create an instance of PayPalHelper.
     *
     * @param string $merchantId PayPal merchant ID.
     * @param bool $isPayPalLive Indicates whether to use the live PayPal environment.
     * @param string $softDescriptor soft descriptor for the transaction.
     * @return PayPalHelper | null The instance of the PayPalHelper class.
     */
    public static function getInstance($merchantId, $isPayPalLive, $softDescriptor) {
        if (self::$instance === null) {
            self::$instance = new PayPalHelper($merchantId, $isPayPalLive, $softDescriptor);
            self::$instance->setEnvironment();
        }

        // minimum validation for merchantId and softDescriptor
        $error = self::$instance->validateInstance($merchantId, $softDescriptor);
        if(isset($error)) {
            echo $error;
            self::$instance->log($error);
            return null;
        } else {
            return self::$instance;
        }
    }

    // Set the base URL for PayPal API requests based on the environment (live or sandbox).
    public function setEnvironment() {
        $this->apiUrl = 'https://api.goopter.com/api/v8/ppcpRequest';
        $this->ppcpAssertion = $this->generatePayPalAuthAssertion();
    }

    /**
     * Validates the provided merchant ID and soft descriptor.
     *
     * This method performs basic validation checks on the provided `merchantId` and `softDescriptor` values. 
     * It checks if the `merchantId` is valid (i.e., not empty and not set to the default placeholder value), 
     * and whether the `softDescriptor` is provided and does not exceed the maximum allowed length (22 characters).
     * If any of these checks fail, an appropriate error message is returned.
     *
     * @param string $merchantId The merchant ID to validate.
     * @param string $softDescriptor The soft descriptor to validate.
     *
     * @return string|null Returns an error message if validation fails, or `null` if both values are valid.
     */
    public function validateInstance($merchantId, $softDescriptor) {
        $error = null;
        if (!isset($merchantId) || $merchantId == 'YOUR_MERCHANT_ID') {
            $error = "Replace with your Merchant ID which is valid";
        } elseif (!isset($softDescriptor)) {
            $error = "soft descriptor is missing, please add";
        } elseif (strlen($softDescriptor) > 22) {
            $error = "soft descriptor is too long, it should be up to 22 character";
        }
        return $error;
    }
    
    /**
     * Generates the PayPal authorization assertion.
     *
     * @return string The generated PayPal authorization assertion.
     */
    public function generatePayPalAuthAssertion() {
        $temp = array(
            "alg" => "none"
        );
        $returnData = base64_encode(json_encode(value: $temp)) . '.';
        $temp = array(
            "iss" => $this->isPaypalLive ? GOOPTER_LIVE_CLIENT_ID : GOOPTER_SANDBOX_CLIENT_ID,
            "payer_id" => $this->merchantId
        );
        $returnData .= base64_encode(json_encode($temp)) . '.';
        return $returnData;
    }

    /**
    * Creates a PayPal order.
    *
    * @param string $currency The currency code (e.g., USD, EUR).
    * @param array | null $paymentSource The payment source details.
    * @param float $partnerFee The partner fee amount.
    * @param float|null $amount The order amount (optional).
    * @param array|null $items The list of purchase items (optional)
    * @param float $shippingFee The shipping fee amount. Defaults to 0 if not provided.
    * @param array|null $shippingAddress The shipping address details (optional)
    * @return mixed The response from the PayPal API.
    */
    public function createOrder($currency, $paymentSource = null, $amount = null, $items = null, $shippingFee = 0, $shippingAddress = null) {
        $paypal_url = "/v2/checkout/orders";
        $this->log("Create Order Request URL: $paypal_url");

        if (!empty($items)) {
            $purchaseItems = [];
            $calculatedAmount = 0;

            foreach ($items as $item) {
                $purchaseItems[] = [
                    'name' => $item['name'],
                    'unit_amount' => [
                        'currency_code' => $currency,
                        'value' => $item['price']
                    ],
                    'quantity' => $item['quantity'],
                    'category' => $item['category'] ?? 'PHYSICAL_GOODS'
                ];
                $calculatedAmount += $item['price'] * $item['quantity'];
            }
            $amount = $calculatedAmount + $shippingFee;
        } else {
            $purchaseItems = []; 
            $amount = (float) $amount + $shippingFee; 
        }

        $paypal_header = [
            "Content-Type" => "application/json",
            "Merchant-Id" => $this->merchantId,
            "PayPal-Request-Id" => uniqid("request_", true)
        ];

        $paypal_body = [
            "intent" => "AUTHORIZE", 
            "purchase_units" => [[
                "amount" => [
                    "currency_code" => $currency,
                    "value" => $amount,
                    "breakdown" => [
                        "item_total" => [
                            "currency_code" => $currency,
                            "value" => ($amount - $shippingFee)
                        ],
                        "shipping" => [
                            "currency_code" => $currency,
                            "value" => $shippingFee
                        ]
                    ]
                ],
                "payee" => [
                    "merchant_id" => $this->merchantId
                ],
                "soft_descriptor" => $this->softDescriptor,
            ]],
            "payment_source" => $paymentSource
        ];

        if (!empty($purchaseItems)) {
            $paypal_body['purchase_units'][0]['items'] = $purchaseItems;
            if (!empty($shippingAddress)) {
                $paypal_body['purchase_units'][0]['shipping'] = [
                    'name' => [
                        'full_name' => $shippingAddress['name'] ?? 'John Doe'
                    ],
                    'address' => [
                        'address_line_1' => $shippingAddress['address_line_1'] ?? '123 Main St',
                        'address_line_2' => $shippingAddress['address_line_2'] ?? null,
                        'admin_area_2' => $shippingAddress['admin_area_2'] ?? 'San Francisco',
                        'admin_area_1' => $shippingAddress['admin_area_1'] ?? 'CA',
                        'postal_code' => $shippingAddress['postal_code'] ?? '94107',
                        'country_code' => $shippingAddress['country_code'] ?? 'US'
                    ]
                ];
            }
        }

        $this->log("Authorize Order Request: " . json_encode($paypal_body));

        return $this->makeRequest('POST', $paypal_url, $paypal_header, $paypal_body);
    }


    /**
    * Captures an authorized PayPal order.
    *
    * @param string $authorizationId The ID of the authorized payment to capture.
    * @param float $partnerFee The platform partner fee amount.
    * @param string $currency The currency code (e.g., USD, EUR).
    * @return mixed The response from the PayPal API.
    */
    public function captureOrder($authorizationId, $partnerFee, $currency) {
        $paypal_url = "/v2/payments/authorizations/{$authorizationId}/capture";

        $this->log("Capture Order Request URL: $paypal_url");

        $paypal_header = [
            "Content-Type" => "application/json",
            "Merchant-Id" => $this->merchantId,
        ];
    
        $paypal_body = [
            "payment_instruction" => [
                "platform_fees" => [[
                    "amount" => [
                        "currency_code" => $currency,
                        "value" => $partnerFee
                    ]
                ]]
            ]
        ];

        $this->log("Capture Request: " . json_encode($paypal_body));

        return $this->makeRequest('POST', $paypal_url, $paypal_header, $paypal_body);
    }

    /**
    * Captures an Sale PayPal order.
    *
    * @param string $authorizationId The ID of the authorized payment to capture.
    * @return mixed The response from the PayPal API.
    */
    public function captureSaleOrder($authorizationId) {
        $paypal_url = "/v2/checkout/orders/{$authorizationId}/capture";

        $this->log("Capture Sale Order Request URL: $paypal_url");

        $paypal_header = [
            "Content-Type" => "application/json",
            "Merchant-Id" => $this->merchantId,
        ];
    
        $paypal_body = null;

        $this->log("Capture Request: " . json_encode($paypal_body));

        return $this->makeRequest('POST', $paypal_url, $paypal_header, $paypal_body);
    }

    /**
    * Processes a PayPal sale order.
    *
    * @param string $currency The currency code (e.g., USD, EUR).
    * @param array | null $paymentSource The payment source details.
    * @param float $partnerFee The partner fee amount.
    * @param float|null $amount The total order amount (optional).
    * @param array|null $items The list of purchase items (optional). 
    * @param float $shippingFee The shipping fee amount. Defaults to 0 if not provided.
    * @param array|null $shippingAddress The shipping address details (optional). 
    * @return mixed The response from the PayPal API.
    */
    public function salePayment($currency, $paymentSource, $partnerFee, $amount = null, $items = null, $shippingFee = 0, $shippingAddress = null) {
        $paypal_url = "/v2/checkout/orders";

        $this->log("Sale Payment Request URL: $paypal_url");
    
        $paypal_header = [
            "Content-Type" => "application/json",
            "PayPal-Request-Id" => uniqid("request_", true),
        ];
    
        $itemTotal = 0;

        if (!empty($items)) {
            $itemDetails = [];
            foreach ($items as $item) {
                $itemDetails[] = [
                    "name" => $item['name'],
                    "unit_amount" => [
                        "currency_code" => $currency,
                        "value" => $item['price']
                    ],
                    "quantity" => $item['quantity']
                ];
            }
    
            $itemTotal = array_reduce($items, function ($total, $item) {
                return $total + ($item['price'] * $item['quantity']);
            }, 0);

            $amount = $itemTotal + $shippingFee;
    
            $purchaseUnits = [
                "amount" => [
                    "currency_code" => $currency,
                    "value" => $amount,
                    "breakdown" => [
                        "item_total" => [
                            "currency_code" => $currency,
                            "value" => $itemTotal
                        ],
                        "shipping" => [
                            "currency_code" => $currency,
                            "value" => $shippingFee
                        ]
                    ]
                ],
                "items" => $itemDetails,
                "payee" => [
                    "merchant_id" => $this->merchantId
                ],
                "soft_descriptor" => $this->softDescriptor,
                "payment_instruction" => [
                    "platform_fees" => [[
                        "amount" => [
                            "currency_code" => $currency,
                            "value" => $partnerFee
                        ]
                    ]]
                ]
            ];

            if (!empty($shippingAddress)) {
                $purchaseUnits['shipping'] = [
                    'name' => [
                        'full_name' => $shippingAddress['name'] ?? 'John Doe'
                    ],
                    'address' => [
                        'address_line_1' => $shippingAddress['address_line_1'] ?? '123 Main St',
                        'address_line_2' => $shippingAddress['address_line_2'] ?? null,
                        'admin_area_2' => $shippingAddress['admin_area_2'] ?? 'San Francisco',
                        'admin_area_1' => $shippingAddress['admin_area_1'] ?? 'CA',
                        'postal_code' => $shippingAddress['postal_code'] ?? '94107',
                        'country_code' => $shippingAddress['country_code'] ?? 'US'
                    ]
                ];
            }
        } else {
            $purchaseUnits = [
                "amount" => [
                    "currency_code" => $currency,
                    "value" => $amount,
                    "breakdown" => [
                        "item_total" => [
                            "currency_code" => $currency,
                            "value" => $amount
                        ]
                    ]
                ],
                "payee" => [
                    "merchant_id" => $this->merchantId
                ],
                "soft_descriptor" => $this->softDescriptor,
                "payment_instruction" => [
                    "platform_fees" => [[
                        "amount" => [
                            "currency_code" => $currency,
                            "value" => $partnerFee
                        ]
                    ]]
                ]
            ];
        }

        $paymentSourceObject = $paymentSource; 

        $paypal_body = [
            "intent" => "CAPTURE", 
            "purchase_units" => [$purchaseUnits],
            "payment_source" => $paymentSourceObject
        ];
    
        $this->log("Sale Payment Request: " . json_encode($paypal_body));

        return $this->makeRequest('POST', $paypal_url, $paypal_header, $paypal_body);
    }    

    /**
    * Refunds a captured PayPal payment with a specified amount and currency.
     *
    * @param string $captureId The ID of the captured payment to refund.
    * @param float $refundAmount The amount to refund.
    * @param string $currency The currency code of the refund (e.g., USD, EUR).
    * @return mixed The response from the PayPal API.
    */
    public function refundPayment($captureId, $refundAmount, $currency)
    {
        $paypal_url = "/v2/payments/captures/{$captureId}/refund";

        $this->log("Refund Request URL: $paypal_url");

        $paypal_header = [
            "Content-Type" => "application/json",
            "PayPal-Auth-Assertion" => $this->ppcpAssertion,
        ];

        $paypal_body = [
            "amount" => [
                "value" => $refundAmount,
                "currency_code" => $currency
            ]
        ];

        $this->log("Refund Request: " . json_encode($paypal_body));

        $response = $this->makeRequest("POST", $paypal_url, $paypal_header, $paypal_body);

        if (isset($response['error'])) {
            throw new Exception("PayPal API Error: {$response['error_description']}");
        }

        if (isset($response['details']) && is_array($response['details'])) {
            foreach ($response['details'] as $detail) {
                if ($detail['issue'] === "REFUND_AMOUNT_EXCEEDED") {
                    $this->log("Refund amount exceeds balance. Refunding remaining balance.");
                    return $this->refundPaymentFully($captureId);
                }
            }
        }

        if (isset($response['details']) && is_array($response['details'])) {
            foreach ($response['details'] as $detail) {
                if ($detail['issue'] === "CAPTURE_FULLY_REFUNDED") {
                    $description = $detail['description']; 
                    $this->log($description); 
                    return $response;
                }
            }
        }

        return $response;
    }

    // Fully refund a captured payment.
    // Logically handle full refunds if the refund amount exceeds the captured balance.
    public function refundPaymentFully($captureId)
    {
        $getCaptureDetailsUrl = "/v2/payments/captures/{$captureId}";
    
        $paypal_header = [
            "Content-Type" => "application/json",
            "PayPal-Auth-Assertion" => $this->ppcpAssertion,
        ];

        $captureDetails = $this->makeRequest("GET", $getCaptureDetailsUrl, $paypal_header);

        if (isset($captureDetails['error'])) {
            throw new Exception("PayPal API Error: {$captureDetails['error_description']}");
        }

        if (!isset($captureDetails['seller_receivable_breakdown']['gross_amount'])) {
            throw new Exception("Unable to retrieve the captured amount from PayPal.");
        }
    
        $capturedAmount = $captureDetails['seller_receivable_breakdown']['gross_amount']['value'];
        $currency = $captureDetails['seller_receivable_breakdown']['gross_amount']['currency_code'];

        $refundUrl = "/v2/payments/captures/{$captureId}/refund";

        $refundBody = [
            "amount" => [
                "value" => $capturedAmount,
                "currency_code" => $currency
            ]
        ];

        $refundResponse = $this->makeRequest("POST", $refundUrl, $paypal_header, $refundBody);

        if (isset($refundResponse['error'])) {
            throw new Exception("PayPal API Error during refund: {$refundResponse['error_description']}");
        }

        if (isset($refundResponse['id'])) {
            $this->log("Refund completed successfully. Refund ID: " . $refundResponse['id']);
            return $refundResponse['id'];
        } else {
            throw new Exception("Refund completed but no Refund ID was returned.");
        }
    }

    /**
     * Makes a generic PayPal API request with specified method, URL, headers, and body.
     *
     * @param string $paypal_method The HTTP method for the API request (e.g., GET, POST).
     * @param string $paypal_url The endpoint URL for the API request.
     * @param array $paypal_header The headers for the API request.
     * @param array|null $paypal_body The body of the API request.
     * @return array The response from the PayPal API.
     */
    private function makeRequest($paypal_method, $paypal_url, $paypal_header = [], $paypal_body = null) {
        $requestTime = date('Y-m-d H:i:s');
        $this->log("Request sent at: $requestTime");

        $headers = [
            "Content-Type: application/json",
            "Merchant-Id: " . $this->merchantId,
            "Soft-Descriptor: " . $this->softDescriptor,
        ];
        
        $body = [
            "testmode" => $this->isPaypalLive ? "no" : "yes",
            "paypal_url" => $paypal_url,
            "paypal_method" => $paypal_method,
            "paypal_header" => $paypal_header,
            "paypal_body" => $paypal_body,
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->apiUrl);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        $responseTime = date('Y-m-d H:i:s');
        $this->log("Response received at: $responseTime");

        if (curl_errno($ch)) {
            $error = curl_error($ch);
            $this->log("CURL Error: $error");
        }

        curl_close($ch);

        $logData = [
            'request_time' => $requestTime,
            'response_time' => $responseTime,
            'method' => $paypal_method,
            'url' => $paypal_url,
            'http_code' => $httpCode,
            'response_body' => json_decode($response, true)
        ];

        $this->log("Request to $paypal_url with method $paypal_method - HTTP $httpCode: $response");
        $this->log("Request and Response Log:\n" . json_encode($logData, JSON_PRETTY_PRINT));

        return json_decode($response, true);
    }

    /**
     * Logs a message to the configured log file.
     *
     * @param string $message The message to log.
     */
    private function log($message) {
        file_put_contents($this->logFile, date('Y-m-d H:i:s') . " - $message" . PHP_EOL, FILE_APPEND);
    }
}