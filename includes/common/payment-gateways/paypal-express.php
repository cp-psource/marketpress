<?php

class MP_Gateway_Paypal_Express extends MP_Gateway_API {

    var $plugin_name   = 'paypal_express';
    var $admin_name    = 'PayPal Express';
    var $public_name   = 'PayPal Express';

function on_creation() {
    // Einstellungen laden
    //$this->init_settings_metabox();

    // Zahlungsprozess-Callback registrieren
    add_action('mp_order/payment_process/' . $this->plugin_name, array($this, 'process_payment'));

    // Beispiel aus Manual Payment anpassen auf PayPal
    $this->admin_name = __('PayPal Express', 'mp');

    $public_name = $this->get_setting('name', __('PayPal Express', 'mp'));
    $this->public_name = empty($public_name) ? __('PayPal Express', 'mp') : $public_name;

    // Optional: Email-Notification Filter registrieren, falls du Bestätigungsmails anpassen willst
    add_filter('mp_order/notification_body/' . $this->plugin_name, array($this, 'order_confirmation_email'), 10, 2);

    // Optional: Bestätigungstext nach Zahlung
    add_filter('mp_order/confirmation_text/' . $this->plugin_name, array($this, 'order_confirmation_text'), 10, 2);
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
                'status'               => array(time() => __('Paid', 'mp')),
                'total'                => $body['purchase_units'][0]['payments']['captures'][0]['amount']['value'],
                'currency'             => $body['purchase_units'][0]['payments']['captures'][0]['amount']['currency_code'],
                'method'               => __('PayPal Express', 'mp'),
                'transaction_id'       => $body['purchase_units'][0]['payments']['captures'][0]['id'],
            );

            $order = new MP_Order();
            $order->save(array(
                'cart'         => new MP_Cart(),
                'payment_info' => $payment_info,
                'paid'         => true,
            ));

            update_post_meta($order->_order_id, 'transaction_id', $payment_info['transaction_id']);

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

        if (is_wp_error($response)) {
            error_log('PayPal token request failed: ' . $response->get_error_message());
            return false;
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (empty($data['access_token'])) {
            error_log('PayPal token response: ' . $body);
            return false;
        }

        return $data['access_token'];
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
                    'cancel_url' => $cart->cart_url(),
                ),
            )),
        ));

        return json_decode(wp_remote_retrieve_body($response), true);
    }

    public function get_field_name($field) {
        return "gateways[{$this->plugin_name}][{$field}]";
    }

    /**
     * Output additional order confirmation text
     *
     * @param MP_Order $order
     * @return string
     */
    public function order_confirmation_text( $order ) {
        if ( is_string( $order ) ) {
            $order = new MP_Order( $order );
        }

        $transaction_id = $order->get_meta( 'transaction_id' );
        if (!$transaction_id && !empty($order->payment_info['transaction_id'])) {
            $transaction_id = $order->payment_info['transaction_id'];
        }

        return __('Thank you for your order. Your payment was successful.', 'mp');
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



