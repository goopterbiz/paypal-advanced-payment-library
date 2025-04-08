The PayPalHelper is a PHP helper class designed to simplify the integration of your application with PayPal's Complete Payment solution.  **There is no additional service fee** on top of the standard PayPal rates. You pay only the standard PayPal rate. 

**Please Note:**  Onboarding with Goopter eCommerce is required before you can use this helper class. This library is intended for use in your secured server environment.

## Prerequisites
- **PHP 8.0 or higher**
- **NODE 16.20.0 or higher**
- **Onboarding with Goopter eCommerce merchant account** (Sandbox or Live)
- **PayPal merchant account** (Sandbox or Live)
- For assistance, please contact support@goopter.com, it's free!

## Setup Instructions

### 1. Clone or Download the Repository
```bash
git clone https://github.com/goopterbiz/paypal-advanced-payment-library.git
cd paypal-advanced-payment-library
```

### 2. Configure Your PayPal Credentials
1. Open the **`/backend/config.php`** file.
2. Update **`MERCHANT_ID`** with your PayPal merchant ID.
3. Update **`SOFT_DESCRIPTOR`** with your merchant name displayed on the customer's credit card statement.
4. If you are using a Live merchant ID, set **`IS_PAYPAL_LIVE`** to **`true`**.

### 3. To run the Demo on local environment, run the following command to open the test web page:
```bash
npm install
npm start
```
### 4. For server deployment, just put the files on the Apache+PHP or Nginx+PHP server and edit SERVER_URL in /frontend/service.js

### 5. To run the Command Line Demo, run following command to test the credit card payment:
```bash
cd backend
php test-helper.php
```

## Merchant Fees ##
* **Canadian Merchants:** (Link to fees page: [https://www.paypal.com/ca/webapps/mpp/merchant-fees](https://www.paypal.com/ca/webapps/mpp/merchant-fees))
    * Advanced Credit and Debit Card Payments: 2.7% + $0.30 CAD
    * Advanced Credit and Debit Card Payments (American Express): 3.50%
    * Receiving International Transactions:
        * US Transactions: 3.50% + fixed fee
        * Other International Transactions: 3.70% + fixed fee
        * Failure to Implement Express Checkout (after 30-day notice): + 1.00% per transaction
    * Monthly Fee: No Fee
* **USA Merchants:** (Link to fees page: [https://www.paypal.com/us/business/paypal-business-fees#fixed-fees-commercialtrans](https://www.paypal.com/us/business/paypal-business-fees#fixed-fees-commercialtrans))
    * Advanced Credit and Debit Card Payments: 2.59% + $0.49 USD
    * PayPal Checkout, Pay with Venmo, PayPal Pay Later offers, or PayPal Guest Checkout:  3.49% + $0.49 USD
    * PayPal Guest Checkout: 3.49% + $0.49 USD
    * Monthly Fee: No Fee

test-helper.php - command line PHP unit test code for PayPalHelper.
