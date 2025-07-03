<?php
/**
 * MarketPress Gateway: PayPal Commerce Platform (Marketplace)
 *
 * Modernes Gateway für Split Payments & Seller-Onboarding
 *
 * @author AI
 * @since 2025
 */

class MP_Gateway_PayPal_Marketplace extends MP_Gateway_API {

    public $admin_name = 'PayPal Marketplace (Commerce Platform)';
    public $public_name = 'PayPal';
    public $method_img_url = '';
    public $method_button_img_url = '';
    public $force_ssl = true;
    public $skip_form = false;
    public $build = 1;
    public $plugin_name = 'paypal_marketplace';

    public function on_creation() {
        //set names hier, damit sie immer korrekt sind
        if ( function_exists('is_super_admin') && is_super_admin() ) {
            $this->admin_name = __( 'PayPal Marketplace (Commerce Platform)', 'mp' );
        } else {
            $this->admin_name = __( 'PayPal', 'mp' );
        }
        $this->public_name = __( 'PayPal', 'mp' );
        $this->method_img_url        = mp_plugin_url('includes/common/payment-gateways/paypal-marketplace/paypal-marketplace.png');
        $this->method_button_img_url = $this->method_img_url;
        $this->force_ssl = true;
        $this->skip_form = false;
    }

    public function __construct() {
        parent::__construct();
        $this->on_creation();
    }

    // Gateway-Initialisierung (z. B. Registrierung)
    public static function register_gateway( $plugin_name = 'MP_Gateway_PayPal_Marketplace', $args = array() ) {
        if ( empty( $args ) ) {
            $args = array(
                'file'      => __FILE__,
                'class'     => 'MP_Gateway_PayPal_Marketplace',
                'name'      => __( 'PayPal Marketplace (Commerce Platform)', 'mp' ),
                'is_global' => true,
            );
        }
        parent::register_gateway( $plugin_name, $args );
    }

    public function admin_settings( $settings ) {
        $settings[ 'client_id' ] = array(
            'label'       => __( 'PayPal Client-ID', 'mp' ),
            'type'        => 'text',
            'description' => __( 'Deine PayPal REST-API Client-ID (Live oder Sandbox)', 'mp' ),
            'value'       => $this->get_setting( 'client_id', '' ),
        );
        $settings[ 'secret' ] = array(
            'label'       => __( 'PayPal Secret', 'mp' ),
            'type'        => 'password',
            'description' => __( 'Dein PayPal REST-API Secret (Live oder Sandbox)', 'mp' ),
            'value'       => $this->get_setting( 'secret', '' ),
        );
        $settings[ 'webhook_url' ] = array(
            'label'       => __( 'Webhook-URL', 'mp' ),
            'type'        => 'text',
            'custom_html' => '<code>' . esc_html( $this->get_webhook_url() ) . '</code>',
            'description' => __( 'Diese URL in deinem PayPal Developer Dashboard als Webhook eintragen.', 'mp' ),
            'value'       => $this->get_webhook_url(),
            'readonly'    => true,
        );
        // Onboarding-Status und Button direkt in den Gateway-Einstellungen anzeigen
        $merchant_id = get_option( 'mp_paypal_marketplace_merchant_id_' . get_current_blog_id() );
        if ( $merchant_id ) {
            $settings['onboarding_status'] = array(
                'label' => __( 'PayPal-Onboarding', 'mp' ),
                'type'  => 'custom',
                'custom_html' => '<p style="color:green;"><strong>' . __( 'PayPal-Konto verbunden!', 'mp' ) . '</strong></p>'
            );
        } else {
            $onboard_url = esc_url( add_query_arg( array( 'mp_paypal_onboard' => 1 ), admin_url() ) );
            $settings['onboarding_status'] = array(
                'label' => __( 'PayPal-Onboarding', 'mp' ),
                'type'  => 'custom',
                'custom_html' => '<a href="' . $onboard_url . '" class="button button-primary">' . __( 'PayPal-Konto verbinden', 'mp' ) . '</a>'
            );
        }
        return $settings;
    }

    /**
     * Liefert die Webhook-URL für PayPal
     */
    public function get_webhook_url() {
        return home_url( '/?mp_paypal_marketplace_webhook=1' );
    }

    /**
     * Logging-Helfer für Gateway-Fehler und wichtige Events
     */
    protected function log($msg) {
        if ( defined('WP_DEBUG') && WP_DEBUG ) {
            error_log('[MP PayPal Marketplace] ' . $msg);
        }
    }

    /**
     * Fängt den Onboarding-Redirect ab und speichert die Merchant-ID (echter Flow)
     */
    public static function maybe_handle_onboarding() {
        if ( isset( $_GET['mp_paypal_onboard'] ) && current_user_can( 'manage_options' ) ) {
            // ECHTER PAYPAL OAUTH-FLOW (Partner Onboarding)
            $client_id = get_site_option('mp_paypal_marketplace_partner_client_id', ''); // Muss im Netzwerk-Admin hinterlegt werden
            $redirect_uri = admin_url(); // Muss mit PayPal App übereinstimmen
            $scope = 'openid https://uri.paypal.com/services/paypalattributes https://uri.paypal.com/services/partnermerchant onboarding';
            // 1. Wenn kein Code vorhanden: Weiterleitung zu PayPal
            if ( ! isset($_GET['code']) ) {
                $state = wp_create_nonce('mp_paypal_onboard');
                $onboard_url = 'https://www.sandbox.paypal.com/connect?flowEntry=static&client_id=' . urlencode($client_id)
                    . '&scope=' . urlencode($scope)
                    . '&redirect_uri=' . urlencode($redirect_uri)
                    . '&state=' . urlencode($state);
                wp_redirect($onboard_url);
                exit;
            }
            // 2. Rückkehr von PayPal: Code gegen Merchant-ID tauschen
            if ( isset($_GET['code']) && isset($_GET['state']) && wp_verify_nonce($_GET['state'], 'mp_paypal_onboard') ) {
                $code = sanitize_text_field($_GET['code']);
                $client_secret = get_site_option('mp_paypal_marketplace_partner_secret', '');
                $token_url = 'https://api.sandbox.paypal.com/v1/oauth2/token';
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $token_url);
                curl_setopt($ch, CURLOPT_USERPWD, $client_id . ':' . $client_secret);
                curl_setopt($ch, CURLOPT_POSTFIELDS, 'grant_type=authorization_code&code=' . urlencode($code));
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1 );
                curl_setopt($ch, CURLOPT_POST, 1 );
                curl_setopt($ch, CURLOPT_HTTPHEADER, array('Accept: application/json'));
                $result = curl_exec($ch);
                $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                if ($result === false || $httpcode !== 200) {
                    curl_close($ch);
                    wp_die(__('PayPal Onboarding fehlgeschlagen: ', 'mp') . curl_error($ch));
                }
                $data = json_decode($result, true);
                curl_close($ch);
                if (isset($data['merchant_id'])) {
                    update_option('mp_paypal_marketplace_merchant_id_' . get_current_blog_id(), sanitize_text_field($data['merchant_id']));
                    wp_safe_redirect(admin_url('options-general.php?page=paypal-marketplace-payout&onboard=1'));
                    exit;
                } else {
                    wp_die(__('PayPal-Onboarding-Flow: Keine Merchant-ID erhalten.', 'mp'));
                }
            }
            wp_die( __( 'PayPal-Onboarding-Flow: Fehler oder abgebrochen.', 'mp' ) );
        }
    }

    /**
     * Liest die Provision aus den Netzwerk-Einstellungen (wie bei Chained)
     */
    protected function get_network_provision() {
        $settings = get_site_option('mp_network_settings', array());
        $provision = 0.0;
        if (isset($settings['gateways']['paypal_marketplace']['provision'])) {
            $provision = floatval(str_replace(',', '.', $settings['gateways']['paypal_marketplace']['provision'])) / 100;
        }
        return $provision;
    }

    /**
     * Ermittelt Seller und deren Anteile für Split Payments im globalen Warenkorb
     * Gibt ein Array mit Merchant-IDs und Beträgen zurück
     */
    public function get_split_payments( $cart ) {
        $splits = array();
        if ( ! is_array( $cart ) ) return $splits;
        foreach ( $cart as $item ) {
            $blog_id = isset( $item['blog_id'] ) ? $item['blog_id'] : get_current_blog_id();
            $merchant_id = get_option( 'mp_paypal_marketplace_merchant_id_' . $blog_id );
            if ( ! $merchant_id ) {
                $this->log('Fehlende Merchant-ID für Blog ' . $blog_id);
                continue;
            }
            if ( ! isset( $splits[ $merchant_id ] ) ) {
                $splits[ $merchant_id ] = 0;
            }
            $splits[ $merchant_id ] += floatval( $item['price'] ) * intval( $item['qty'] );
        }
        // Provision aus Netzwerk-Einstellungen holen
        $provision = $this->get_network_provision();
        if ($provision > 0) {
            foreach ($splits as $merchant_id => &$amount) {
                $amount = round($amount * (1 - $provision), 2);
            }
            unset($amount);
            // Marktplatz-Betreiber als eigenen Payee hinzufügen
            $network_merchant = get_option('mp_paypal_marketplace_merchant_id_network');
            if ($network_merchant) {
                $total = 0;
                foreach ($cart as $item) {
                    $total += floatval( $item['price'] ) * intval( $item['qty'] );
                }
                $splits[$network_merchant] = round($total * $provision, 2);
            }
        }
        return $splits;
    }

    /**
     * Erstellt eine PayPal-Order mit mehreren Payees (Split Payment)
     * $cart: Array mit Warenkorb-Items
     * $order: MarketPress-Bestellung
     * Gibt die vorbereiteten Daten für die PayPal-API zurück
     */
    public function create_paypal_order_data( $cart, $order ) {
        $splits = $this->get_split_payments( $cart );
        $items = array();
        foreach ( $cart as $item ) {
            $items[] = array(
                'name' => $item['name'],
                'unit_amount' => array(
                    'currency_code' => $order->currency,
                    'value' => number_format( floatval( $item['price'] ), 2, '.', '' ),
                ),
                'quantity' => intval( $item['qty'] ),
            );
        }
        $payees = array();
        foreach ( $splits as $merchant_id => $amount ) {
            $payees[] = array(
                'payee' => array('merchant_id' => $merchant_id),
                'amount' => array(
                    'currency_code' => $order->currency,
                    'value' => number_format( floatval( $amount ), 2, '.', '' ),
                ),
            );
        }
        $order_data = array(
            'intent' => 'CAPTURE',
            'purchase_units' => $payees,
            'items' => $items,
            // Weitere Felder wie shipping, payer etc. können ergänzt werden
        );
        // Hier müsste der echte API-Call zu PayPal erfolgen
        // $response = $this->paypal_api_create_order( $order_data );
        // return $response;
        return $order_data;
    }

    /**
     * Erstellt eine Order bei PayPal via REST-API und gibt die Approval-URL zurück
     */
    public function paypal_api_create_order( $order_data ) {
        $client_id = $this->get_setting( 'client_id', '' );
        $secret    = $this->get_setting( 'secret', '' );
        $sandbox   = true; // TODO: Option für Live/Sandbox
        $api_base  = $sandbox ? 'https://api.sandbox.paypal.com' : 'https://api.paypal.com';

        // 1. Access Token holen
        $ch = curl_init();
        curl_setopt( $ch, CURLOPT_URL, $api_base . '/v1/oauth2/token' );
        curl_setopt( $ch, CURLOPT_HTTPHEADER, array( 'Accept: application/json', 'Accept-Language: de_DE' ) );
        curl_setopt( $ch, CURLOPT_USERPWD, $client_id . ':' . $secret );
        curl_setopt( $ch, CURLOPT_POSTFIELDS, 'grant_type=client_credentials' );
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
        curl_setopt( $ch, CURLOPT_POST, 1 );
        $result = curl_exec( $ch );
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if ($result === false || $httpcode !== 200) {
            curl_close($ch);
            return new WP_Error('paypal_auth', 'PayPal Auth fehlgeschlagen: ' . curl_error($ch));
        }
        $token_data = json_decode( $result, true );
        curl_close($ch);
        $access_token = $token_data['access_token'];

        // 2. Order anlegen
        $ch = curl_init();
        curl_setopt( $ch, CURLOPT_URL, $api_base . '/v2/checkout/orders' );
        curl_setopt( $ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Authorization: Bearer ' . $access_token,
        ) );
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
        curl_setopt( $ch, CURLOPT_POST, 1 );
        curl_setopt( $ch, CURLOPT_POSTFIELDS, json_encode( $order_data ) );
        $result = curl_exec( $ch );
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if ($result === false || ($httpcode !== 201 && $httpcode !== 200)) {
            $err = curl_error($ch);
            curl_close($ch);
            return new WP_Error('paypal_order', 'PayPal Order fehlgeschlagen: ' . $err . ' | Antwort: ' . $result);
        }
        $response = json_decode( $result, true );
        curl_close($ch);
        // Approval-Link extrahieren
        $approval_url = '';
        if ( isset( $response['links'] ) ) {
            foreach ( $response['links'] as $link ) {
                if ( $link['rel'] === 'approve' ) {
                    $approval_url = $link['href'];
                    break;
                }
            }
        }
        if ( ! $approval_url ) {
            return new WP_Error('paypal_no_approval', 'Keine Approval-URL von PayPal erhalten.');
        }
        return array(
            'id' => $response['id'],
            'approval_url' => $approval_url,
            'raw' => $response,
        );
    }

    /**
     * Wird beim Checkout aufgerufen: Erstellt die PayPal-Order und gibt die Redirect-URL zurück
     * @param MP_Cart $cart
     * @param array $billing_info
     * @param array $shipping_info
     */
    public function process_payment( $cart, $billing_info, $shipping_info ) {
        // Hole ggf. Order-ID oder erstelle sie wie benötigt
        $order_id = isset( $cart->order_id ) ? $cart->order_id : 0;
        $order = null;
        if ( $order_id ) {
            $order = get_post( $order_id );
        }
        $order_data = $this->create_paypal_order_data( $cart, $order );
        $api_result = $this->paypal_api_create_order( $order_data );
        if ( is_wp_error( $api_result ) ) {
            return array(
                'result' => 'fail',
                'message' => $api_result->get_error_message(),
            );
        }
        // Order-ID in der Bestellung speichern
        if ( $order && isset( $order->ID ) ) {
            update_post_meta( $order->ID, '_paypal_marketplace_order_id', $api_result['id'] );
        }
        return array(
            'result'   => 'redirect',
            'redirect' => $api_result['approval_url'],
        );
    }

    /**
     * Webhook-Handler für PayPal: Logging und Fehlerbehandlung
     */
    public static function maybe_handle_webhook() {
        if ( isset( $_GET['mp_paypal_marketplace_webhook'] ) ) {
            $body = file_get_contents('php://input');
            $data = json_decode( $body, true );
            $order_id = isset( $data['resource']['id'] ) ? $data['resource']['id'] : '';
            $status   = isset( $data['resource']['status'] ) ? $data['resource']['status'] : '';
            // MarketPress-Bestellung finden
            $args = array(
                'post_type'  => 'mp_order',
                'meta_query' => array(
                    array(
                        'key'   => '_paypal_marketplace_order_id',
                        'value' => $order_id,
                    ),
                ),
            );
            $orders = get_posts( $args );
            if ( ! empty( $orders ) ) {
                $mp_order_id = $orders[0]->ID;
                $mp_status = ($status === 'COMPLETED') ? 'paid' : strtolower($status);
                update_post_meta( $mp_order_id, 'mp_status', $mp_status );
                if ( defined('WP_DEBUG') && WP_DEBUG ) {
                    error_log('[MP PayPal Marketplace] Webhook: Order ' . $mp_order_id . ' Status: ' . $mp_status);
                }
            } else {
                if ( defined('WP_DEBUG') && WP_DEBUG ) {
                    error_log('[MP PayPal Marketplace] Webhook: Keine Bestellung gefunden für PayPal-Order ' . $order_id);
                }
            }
            http_response_code(200);
            exit;
        }
    }
}

// Registrierung IMMER, unabhängig vom Kontext
add_action('plugins_loaded', function() {
    MP_Gateway_PayPal_Marketplace::register_gateway();
}, 1);
// Hook für das Onboarding im Admin-Bereich
add_action( 'admin_init', array( MP_Gateway_PayPal_Marketplace::class, 'maybe_handle_onboarding' ) );
// Webhook-Handler aktivieren
add_action( 'init', array( MP_Gateway_PayPal_Marketplace::class, 'maybe_handle_webhook' ) );
// Gateway immer in die Gateway-Liste injizieren (Netzwerk & Shop)
add_filter('mp_gateways', function($gateways) {
    $gateways['paypal_marketplace'] = array(
        'file' => __FILE__,
        'class' => 'MP_Gateway_PayPal_Marketplace',
        'name' => __('PayPal Marketplace (Commerce Platform)', 'mp'),
        'is_global' => true,
    );
    return $gateways;
}, 1);
