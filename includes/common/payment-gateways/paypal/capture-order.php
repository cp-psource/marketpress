<?php
// Datei: includes/common/payment-gateways/paypal/capture-order.php

require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/class-paypal-client.php';

use PayPalCheckoutSdk\Orders\OrdersCaptureRequest;

header('Content-Type: application/json');

// Absicherung: orderId muss vorhanden sein
if (!isset($_GET['orderId'])) {
  http_response_code(400);
  echo json_encode(['error' => 'orderId fehlt']);
  exit;
}

$orderId = sanitize_text_field($_GET['orderId']);
$request = new OrdersCaptureRequest($orderId);
$request->prefer('return=representation');

try {
  $client = PayPalClient::client(); // Hole API-Client mit Credentials
  $response = $client->execute($request); // Führt Capture durch
  echo json_encode($response->result);    // JSON-Ausgabe für Frontend (z. B. JS)
} catch (Exception $e) {
  http_response_code(500);
  echo json_encode(['error' => $e->getMessage()]);
}
?>
