import { createOrder, onApprove } from './service.js';

async function setupApplepay() {
    const applepay = paypal.Applepay();
    const {
        isEligible,
        countryCode,
        currencyCode,
        merchantCapabilities,
        supportedNetworks,
    } = await applepay.config();
  
    if (!isEligible) {
      throw new Error("applepay is not eligible");
    }
  
    const container = document.getElementById("applepay-container");  
    container.innerHTML =
      '<apple-pay-button id="btn-appl" buttonstyle="black" type="plain" locale="en">';
    container.style.cssText = `
        margin-bottom: 10px;
    `
    const button = document.getElementById("btn-appl");
    button.style.cssText = `
        max-width: 750px;
        width: 100%;
        --apple-pay-button-height: 50px;
    `
    button.addEventListener("click", onClick);
  
    async function onClick() {
        // calculate the total amount
        let totalAmount = 0;
        window.cachedItems.forEach(item => {
            totalAmount += item.price;
        });

        const paymentRequest = {
            countryCode,
            currencyCode,
            merchantCapabilities,
            supportedNetworks,
            requiredBillingContactFields: [
                "name",
                "phone",
                "email",
                "postalAddress",
            ],
            requiredShippingContactFields: [
            ],
            total: {
                label: "Demo Apple Pay Order",
                amount: totalAmount,
                type: "final",
            },
        };

        let session = new ApplePaySession(4, paymentRequest);

        session.onvalidatemerchant = (event) => {
            applepay.validateMerchant({
                validationUrl: event.validationURL,
            }).then((payload) => {
                session.completeMerchantValidation(payload.merchantSession);
            })
            .catch((err) => {
                console.error(err);
                session.abort();
            });
        };

        session.onpaymentmethodselected = () => {
            session.completePaymentMethodSelection({
                newTotal: paymentRequest.total,
            });
        };

        session.onpaymentauthorized = async (event) => {
            try {
                /* Create Order on the Server Side */
                const orderID = await createOrder();

                /**
                 * Confirm Payment 
                 */
                await applepay.confirmOrder({ orderId: orderID, token: event.payment.token, billingContact: event.payment.billingContact , shippingContact: event.payment.shippingContact });

                /*
                * Capture order (must currently be made on server)
                */
                await onApprove({orderID: orderID});

                session.completePayment({
                    status: window.ApplePaySession.STATUS_SUCCESS,
                });
            } catch (err) {
                console.error(err);
                session.completePayment({
                    status: window.ApplePaySession.STATUS_FAILURE,
                });
            }
        };

        session.oncancel  = () => {
            console.log("Apple Pay Cancelled !!")
        }

        session.begin();
    }
}
  
if (ApplePaySession?.supportsVersion(4) && ApplePaySession?.canMakePayments()) {
    setupApplepay().catch(console.error);
}