<?php
// Datei: includes/common/payment-gateways/create-order.php

require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/class-paypal-client.php';

use PayPalCheckoutSdk\Orders\OrdersCreateRequest;

header('Content-Type: application/json');

$request = new OrdersCreateRequest();
$request->prefer('return=representation');
$request->body = [
  'intent' => 'CAPTURE',
  'purchase_units' => [[
    'amount' => [
      'currency_code' => 'EUR',
      'value' => '19.99'
    ]
  ]],
  'application_context' => [
    'return_url' => home_url('/danke'),
    'cancel_url' => home_url('/abgebrochen')
  ]
];

try {
  $client = PayPalClient::client();
  $response = $client->execute($request);
  echo json_encode(['id' => $response->result->id]);
} catch (Exception $e) {
  http_response_code(500);
  echo json_encode(['error' => $e->getMessage()]);
}
?>