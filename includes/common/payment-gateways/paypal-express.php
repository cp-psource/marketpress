<?php

class MP_Gateway_Paypal_Express extends MP_Gateway_API {

    var $plugin_name   = 'paypal_express';
    var $admin_name    = 'PayPal Express';
    var $public_name   = 'PayPal Express';

    function __construct($no_ui = false) {
        if (!$no_ui) {
            $this->on_creation();
        }
    }

    function on_creation() {
        $this->init_settings_metabox();

        add_action('mp_order/payment_process/' . $this->plugin_name, array($this, 'process_payment'));
    }

    public function init_settings_metabox() {
        $metabox = new WPMUDEV_Metabox(array(
            'id'            => $this->generate_metabox_id(),
            'page_slugs'    => array('store-settings-payments', 'store-settings_page_store-settings-payments'),
            'title'         => sprintf(__('Einstellungen für %s', 'mp'), $this->admin_name),
            'option_name'   => 'mp_settings',
            'desc'          => __('PayPal Express API Einstellungen', 'mp'),
            'conditional'   => array(
                'name'  => 'gateways[allowed][' . $this->plugin_name . ']',
                'value' => 1,
                'action'=> 'show',
            ),
        ));

        $metabox->add_field('text', array(
            'name'          => $this->get_field_name('client_id'),
            'default_value' => '',
            'label'         => array('text' => __('Client ID', 'mp')),
            'desc'          => __('Deine PayPal API Client ID', 'mp'),
        ));

        $metabox->add_field('password', array(
            'name'          => $this->get_field_name('client_secret'),
            'default_value' => '',
            'label'         => array('text' => __('Client Secret', 'mp')),
            'desc'          => __('Dein PayPal API Client Secret', 'mp'),
        ));

        $metabox->add_field('checkbox', array(
            'name'          => $this->get_field_name('sandbox'),
            'label'         => array('text' => __('Sandbox Modus aktivieren', 'mp')),
            'desc'          => __('Zum Testen Sandbox Modus einschalten', 'mp'),
        ));
    }

    public function process_payment($cart, $billing_info, $shipping_info) {
        $client_id     = $this->get_setting('client_id');
        $client_secret = $this->get_setting('client_secret');
        $sandbox       = $this->get_setting('sandbox');
        $is_sandbox    = !empty($sandbox);

        $base_url = $is_sandbox ? 'https://api.sandbox.paypal.com' : 'https://api.paypal.com';

        // 1. Access Token holen
        $token = $this->get_access_token($base_url, $client_id, $client_secret);

        if (!$token) {
            wp_die(__('Fehler beim Abrufen des PayPal Tokens.', 'mp'));
        }

        // 2. Order erstellen
        $order = $this->create_order_request($base_url, $token, $cart);

        if (!isset($order['id'])) {
            wp_die(__('Fehler beim Erstellen der PayPal Bestellung.', 'mp'));
        }

        // 3. Umleiten zu PayPal Checkout
        foreach ($order['links'] as $link) {
            if ($link['rel'] === 'approve') {
                wp_redirect($link['href']);
                exit;
            }
        }

        wp_die(__('Keine Weiterleitungs-URL gefunden.', 'mp'));
    }

    public function capture_payment() {
        $client_id     = $this->get_setting('client_id');
        $client_secret = $this->get_setting('client_secret');
        $sandbox       = $this->get_setting('sandbox');
        $is_sandbox    = !empty($sandbox);

        $base_url = $is_sandbox ? 'https://api.sandbox.paypal.com' : 'https://api.paypal.com';
        $token    = $this->get_access_token($base_url, $client_id, $client_secret);

        if (!$token || !isset($_GET['token'])) {
            wp_die(__('Zahlung konnte nicht bestätigt werden.', 'mp'));
        }

        $order_id = sanitize_text_field($_GET['token']);
        $response = wp_remote_post("$base_url/v2/checkout/orders/$order_id/capture", array(
            'headers' => array(
                'Authorization' => "Bearer $token",
                'Content-Type'  => 'application/json',
            ),
        ));

        $body = json_decode(wp_remote_retrieve_body($response), true);

        if (isset($body['status']) && $body['status'] === 'COMPLETED') {
            $payment_info = array(
                'gateway_public_name'  => $this->public_name,
                'gateway_private_name' => $this->admin_name,
                'gateway_plugin_name'  => $this->plugin_name,
                'status'               => array(time() => __('Bezahlt', 'mp')),
                'total'                => $body['purchase_units'][0]['payments']['captures'][0]['amount']['value'],
                'currency'             => $body['purchase_units'][0]['payments']['captures'][0]['amount']['currency_code'],
                'method'               => __('PayPal Express', 'mp'),
            );

            $order = new MP_Order();
            $order->save(array(
                'cart'         => new MP_Cart(),
                'payment_info' => $payment_info,
                'paid'         => true,
            ));

            wp_redirect($order->tracking_url(false));
            exit;
        } else {
            wp_die(__('Zahlung fehlgeschlagen.', 'mp'));
        }
    }

    private function get_access_token($base_url, $client_id, $client_secret) {
        $response = wp_remote_post("$base_url/v1/oauth2/token", array(
            'headers' => array(
                'Authorization' => 'Basic ' . base64_encode("$client_id:$client_secret"),
                'Accept'        => 'application/json',
                'Content-Type'  => 'application/x-www-form-urlencoded',
            ),
            'body' => 'grant_type=client_credentials',
        ));

        $body = json_decode(wp_remote_retrieve_body($response), true);
        return isset($body['access_token']) ? $body['access_token'] : false;
    }

    private function create_order_request($base_url, $token, $cart) {
        $response = wp_remote_post("$base_url/v2/checkout/orders", array(
            'headers' => array(
                'Authorization' => "Bearer $token",
                'Content-Type'  => 'application/json',
            ),
            'body' => json_encode(array(
                'intent'              => 'CAPTURE',
                'purchase_units'      => array(array(
                    'amount' => array(
                        'currency_code' => mp_get_setting('currency'),
                        'value'         => $cart->total(),
                    ),
                )),
                'application_context' => array(
                    'return_url' => add_query_arg('paypal-express-return', '1', home_url()),
                    'cancel_url' => mp_cart_url(),
                ),
            )),
        ));

        return json_decode(wp_remote_retrieve_body($response), true);
    }

    public function get_field_name($field) {
        return "gateways[{$this->plugin_name}][{$field}]";
    }
}

// Init-Hook fürs Capturing, ohne doppelte Metaboxen etc.
add_action('init', function () {
    if (isset($_GET['paypal-express-return'])) {
        $gateway = new MP_Gateway_Paypal_Express(true);
        $gateway->capture_payment();
        exit;
    }
});

mp_register_gateway_plugin('MP_Gateway_Paypal_Express', 'paypal_express', __('PayPal Express', 'mp'));


