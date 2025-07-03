<?php
// Loader und Integrationslogik für das PayPal Marketplace Gateway
require_once dirname(__FILE__) . '/paypal-marketplace/class-mp-gateway-paypal-marketplace.php';


// Gateway immer als global registrieren, unabhängig vom Status von global_cart
if ( ! function_exists('mp_register_gateway_plugin') ) return;
mp_register_gateway_plugin( 'MP_Gateway_PayPal_Marketplace', 'paypal_marketplace', __( 'PayPal Marketplace (Commerce Platform)', 'mp' ), true );

// Netzwerk-Metabox für PayPal Marketplace Gateway-Settings
add_action( 'mp_multisite_init_metaboxes', 'init_paypal_marketplace_network_settings_metaboxes' );
function init_paypal_marketplace_network_settings_metaboxes() {
    $metabox = new WPMUDEV_Metabox( array(
        'id'               => 'mp-network-settings-paypal-marketplace',
        'page_slugs'       => array( 'network-store-settings' ),
        'title'            => __( 'PayPal Marketplace (Commerce Platform)', 'mp' ),
        'desc'             => __( 'Hier können Sie die API-Zugangsdaten für das PayPal Marketplace Gateway für alle Shops im Netzwerk hinterlegen.', 'mp' ),
        'site_option_name' => 'mp_network_settings',
        'order'            => 17,
    ) );

    $metabox->add_field( 'text', array(
        'name'         => 'paypal_marketplace_client_id',
        'label'        => array( 'text' => __( 'PayPal Client-ID', 'mp' ) ),
        'desc'         => __( 'Ihre PayPal REST API Client-ID (Live oder Sandbox).', 'mp' ),
        'custom'       => array( 'style' => 'width:350px' ),
        'before_field' => '',
    ) );
    $metabox->add_field( 'password', array(
        'name'         => 'paypal_marketplace_secret',
        'label'        => array( 'text' => __( 'PayPal Secret', 'mp' ) ),
        'desc'         => __( 'Ihr PayPal REST API Secret (Live oder Sandbox).', 'mp' ),
        'custom'       => array( 'style' => 'width:350px' ),
        'before_field' => '',
    ) );
    $metabox->add_field( 'text', array(
        'name'         => 'paypal_marketplace_webhook_url',
        'label'        => array( 'text' => __( 'Webhook-URL', 'mp' ) ),
        'desc'         => __( 'Diese URL muss bei PayPal als Webhook hinterlegt werden.', 'mp' ),
        'custom'       => array( 'style' => 'width:100%;font-family:monospace;' ),
        'before_field' => '',
        'default_value'=> home_url( '/?mp_paypal_marketplace_webhook=1' ),
    ) );
    $metabox->add_field( 'text', array(
        'name'         => 'provision',
        'label'        => array( 'text' => __( 'Marktplatz-Provision (%)', 'mp' ) ),
        'desc'         => __( 'Gib den Prozentsatz an, den der Marktplatzbetreiber pro Transaktion erhält (z.B. 2 für 2%).', 'mp' ),
        'custom'       => array( 'style' => 'width:60px' ),
        'before_field' => '',
        'default_value'=> '2',
    ) );
    $metabox->add_field( 'textarea', array(
        'name'         => 'paypal_marketplace_msg',
        'label'        => array( 'text' => __( 'Gateway Hinweistext', 'mp' ) ),
        'desc'         => __( 'Dieser Text wird im Gateway-Settings-Bereich für Shop-Admins angezeigt.', 'mp' ),
        'custom'       => array( 'style' => 'width:400px; height: 100px;' ),
        'before_field' => '',
    ) );
}

// Shop-Metabox für PayPal Marketplace Gateway-Settings (pro Sub-Shop)
add_action( 'mp_settings_metaboxes', 'init_paypal_marketplace_shop_settings_metaboxes' );
function init_paypal_marketplace_shop_settings_metaboxes() {
    // Nur anzeigen, wenn global_cart aktiv und PayPal Marketplace als globales Gateway gewählt ist
    if (
        function_exists('mp_get_network_setting') &&
        mp_get_network_setting('global_cart') &&
        mp_get_network_setting('global_gateway') === 'paypal_marketplace'
    ) {
        $metabox = new WPMUDEV_Metabox( array(
            'id'               => 'mp-settings-gateway-paypal-marketplace',
            'page_slugs'       => array( 'store-settings-payments' ),
            'title'            => __( 'PayPal Marketplace (Commerce Platform)', 'mp' ),
            'desc'             => __( 'Hier kannst du dein PayPal-Konto für Auszahlungen verbinden. Die globalen API-Daten werden vom Netzwerk-Admin gesetzt.', 'mp' ),
            'option_name'      => 'mp_settings',
            'order'            => 17,
        ) );

        $metabox->add_field( 'text', array(
            'name'         => 'paypal_marketplace_merchant_id',
            'label'        => array( 'text' => __( 'PayPal Merchant-ID', 'mp' ) ),
            'desc'         => __( 'Deine PayPal Merchant-ID (wird nach Onboarding automatisch gesetzt).', 'mp' ),
            'custom'       => array( 'style' => 'width:350px' ),
            'before_field' => '',
            'readonly'     => true,
            'value'        => get_option( 'mp_paypal_marketplace_merchant_id_' . get_current_blog_id() ),
        ) );
        $metabox->add_field( 'custom', array(
            'name'         => 'paypal_marketplace_onboarding',
            'custom_html'  => '<a href="' . esc_url( add_query_arg( array( 'mp_paypal_onboard' => 1 ), admin_url() ) ) . '" class="button button-primary">' . __( 'PayPal-Konto verbinden', 'mp' ) . '</a>',
            'desc'         => __( 'Starte hier das Onboarding, um dein PayPal-Konto zu verbinden.', 'mp' ),
        ) );
        $metabox->add_field( 'textarea', array(
            'name'         => 'paypal_marketplace_shop_msg',
            'label'        => array( 'text' => __( 'Shop Hinweistext', 'mp' ) ),
            'desc'         => __( 'Dieser Text wird im Gateway-Settings-Bereich für dich angezeigt.', 'mp' ),
            'custom'       => array( 'style' => 'width:400px; height: 100px;' ),
            'before_field' => '',
        ) );
    }
}

// Sichtbare PayPal-Auszahlungsseite NUR wenn global_cart aktiv und PayPal Marketplace als globales Gateway gewählt ist
add_action( 'admin_menu', function() {
    if (
        !is_network_admin() &&
        current_user_can('manage_options') &&
        function_exists('mp_get_network_setting') &&
        mp_get_network_setting('global_cart') &&
        mp_get_network_setting('global_gateway') === 'paypal_marketplace'
    ) {
        add_submenu_page(
            'options-general.php',
            __( 'PayPal-Auszahlung', 'mp' ),
            __( 'PayPal-Auszahlung', 'mp' ),
            'manage_options',
            'paypal-marketplace-payout',
            'mp_paypal_marketplace_payout_page'
        );
    }
});

function mp_paypal_marketplace_payout_page() {
    echo '<div class="wrap"><h1>' . __( 'PayPal-Auszahlungskonto', 'mp' ) . '</h1>';
    $merchant_id = get_option( 'mp_paypal_marketplace_merchant_id_' . get_current_blog_id() );
    if ( $merchant_id ) {
        echo '<p><strong>' . __( 'Dein PayPal-Konto ist verbunden!', 'mp' ) . '</strong><br>';
        echo __( 'Merchant-ID:', 'mp' ) . ' <code>' . esc_html( $merchant_id ) . '</code></p>';
    } else {
        $onboard_url = esc_url( add_query_arg( array( 'mp_paypal_onboard' => 1 ), admin_url() ) );
        echo '<a href="' . $onboard_url . '" class="button button-primary">' . __( 'PayPal-Konto verbinden', 'mp' ) . '</a>';
        echo '<p>' . __( 'Verbinde dein PayPal-Konto, um Auszahlungen zu erhalten.', 'mp' ) . '</p>';
    }
    echo '</div>';
}
