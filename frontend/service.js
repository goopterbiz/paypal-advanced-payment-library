// update the SERVER_URL to your server URL if not running the demo on your local machine
const SERVER_URL = "http://localhost:8000";

let cachedItems = [];

async function getInitData() {
  try {
    const response = await fetch(`${SERVER_URL}/backend/api/config-and-products.php`, {
      method: "GET",
    });
    
    const responseData = await response.json();
    const config = responseData["config"];
    const items = responseData["items"];

    cachedItems = items || [];

    return  [config, items];
  } catch (error) {
    console.error(error);
    resultMessage(`Could not get initial data...<br><br>${error}`);
  }
}

async function createOrder() {
  try {
    const cart = cachedItems.map(item => ({
      id: item.id,
      quantity: 1
    }));

    const response = await fetch(`${SERVER_URL}/backend/api/create-order.php`, {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify({ cart }),
      // or you can just send total amount for the order instead of item's id and qty
      // ex) body: JSON.stringify({ amount: 10 }),
      
    });

    const orderData = await response.json();
    
    if (orderData.id) {
      return orderData.id;
    } else {
      const errorDetail = orderData?.details?.[0];
      const errorMessage = errorDetail
        ? `${errorDetail.issue} ${errorDetail.description} (${orderData.debug_id})`
        : JSON.stringify(orderData);
      
      throw new Error(errorMessage);
    }
  } catch (error) {
    console.error(error);
    resultMessage(`Could not initiate PayPal Checkout...<br><br>${error}`);
  }
}

async function onApprove(data, actions) {
  try {
    const response = await fetch(`${SERVER_URL}/backend/api/capture-order.php`, {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify({
        authorization_id: data.orderID,
      }),
    });
    
    const orderData = await response.json();
    // Three cases to handle:
    //   (1) Recoverable INSTRUMENT_DECLINED -> call actions.restart()
    //   (2) Other non-recoverable errors -> Show a failure message
    //   (3) Successful transaction -> Show confirmation or thank you message
    const errorDetail = orderData?.details?.[0];
    
    if (errorDetail?.issue === "INSTRUMENT_DECLINED") {
      // (1) Recoverable INSTRUMENT_DECLINED -> call actions.restart()
      // recoverable state, per https://developer.paypal.com/docs/checkout/standard/customize/handle-funding-failures/
      return actions.restart();
    } else if (errorDetail) {
      // (2) Other non-recoverable errors -> Show a failure message
      throw new Error(`${errorDetail.description} (${orderData.debug_id})`);
    } else if (!orderData.purchase_units) {
      throw new Error(JSON.stringify(orderData));
    } else {
      // (3) Successful transaction -> Show confirmation or thank you message
      // Or go to another URL:  actions.redirect('thank_you.html');
      const transaction =
        orderData?.purchase_units?.[0]?.payments?.captures?.[0] ||
        orderData?.purchase_units?.[0]?.payments?.authorizations?.[0];
      resultMessage(
        `Transaction ${transaction.status}: ${transaction.id}<br><br>See console for completed order details`,
      );
      console.log(
        "Capture result",
        orderData,
        JSON.stringify(orderData, null, 2),
      );
    }
  } catch (error) {
    console.error(error);
    resultMessage(
      `Sorry, your transaction could not be processed...<br><br>${error}`,
    );
  }
}

function resultMessage(message) {
  document.querySelector('#item-list').style.display = 'none';
  document.querySelector('#paypal-button-container').style.display = 'none';
  const container = document.querySelector("#result-message");
  container.innerHTML = message;
}

export { getInitData, createOrder, onApprove };