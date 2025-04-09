import { createOrder, onApprove } from './service.js';

window.paypal
  .Buttons({createOrder, onApprove})
  .render("#paypal-button-container");