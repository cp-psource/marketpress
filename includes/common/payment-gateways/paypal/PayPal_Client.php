<?php
// Lädt PayPal SDK und initialisiert Client mit Optionen aus DB (Sandbox, Credentials, Mode)
require_once __DIR__ . '/../../../../vendor/autoload.php';

use PayPalCheckoutSdk\Core\PayPalHttpClient;
use PayPalCheckoutSdk\Core\SandboxEnvironment;
use PayPalCheckoutSdk\Core\ProductionEnvironment;

class PayPal_Client {
    private $client;

    public function __construct( $client_id, $client_secret, $sandbox = true ) {
        if ( $sandbox ) {
            $environment = new SandboxEnvironment( $client_id, $client_secret );
        } else {
            $environment = new ProductionEnvironment( $client_id, $client_secret );
        }
        $this->client = new PayPalHttpClient( $environment );
    }

    public function get_client() {
        return $this->client;
    }
}