<?php
const MERCHANT_ID = 'YOUR_MERCHANT_ID'; // <<<<<< Replace with your Merchant ID, please contact support@goopter.com to get onboard and receive your merchantId and soft descriptor value.
// The Soft Descriptor is the merchant name that appears on the customer's credit card statement. 
// Please ensure you use the soft descriptor value from the support@goopter.com, as the API call will fail if a non-defined value is used.
const SOFT_DESCRIPTOR = "YOUR_SOFT_DESCRIPTOR"; 
const IS_PAYPAL_LIVE = false; // Set to true for PayPal live environment or false for PayPal Sandbox
const CURRENCY_CODE = "USD"; // OR "CAD"
