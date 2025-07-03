<?php

class MP_Store_Settings_Payments {

	/**
	 * Refers to a single instance of the class
	 *
	 * @since 3.0
	 * @access private
	 * @var object
	 */
	private static $_instance = null;

	/**
	 * Gets the single instance of the class
	 *
	 * @since 3.0
	 * @access public
	 * @return object
	 */
	public static function get_instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new MP_Store_Settings_Payments();
		}

		return self::$_instance;
	}

	/**
	 * Constructor function
	 *
	 * @since 3.0
	 * @access private
	 */
	private function __construct() {
		add_action( 'init', array( &$this, 'add_metaboxes' ) );
		add_action( 'admin_head', array( &$this, 'print_styles' ) );
		add_action( 'admin_init', array( $this, 'maybe_handle_paypal_disconnect' ) );
	}

	/**
	 * Add payment gateway settings metaboxes
	 *
	 * @since 3.0
	 * @access public
	 */
	public function add_metaboxes() {
		$metabox = new WPMUDEV_Metabox( array(
			'id'          => 'mp-settings-payments',
			'page_slugs'  => array( 'store-settings-payments', 'store-settings_page_store-settings-payments' ),
			'title'       => __( 'Payment Gateways', 'mp' ),
			'option_name' => 'mp_settings',
			'order'       => 1,
		) );

		$gateways = MP_Gateway_API::get_gateways( true );

		$options = array();

		foreach ( $gateways as $slug => $gateway ) {
			$options[ $slug ] = $gateway[1];
		}

		$metabox->add_field( 'checkbox_group', array(
			'name'    => 'gateways[allowed]',
			'label'   => array( 'text' => __( 'Enabled Gateways', 'mp' ) ),
			'desc'    => __( 'Choose the gateway(s) that you would like to be available for checkout.', 'mp' ),
			'options' => $options,
			'width'   => '50%',
		) );

		// PayPal Marketplace Onboarding-Metabox für Shop-Admins
		if ( isset( $options['paypal_marketplace'] ) && in_array( 'paypal_marketplace', array_keys( $options ) ) ) {
			$merchant_id = get_option( 'mp_paypal_marketplace_merchant_id_' . get_current_blog_id() );
			$error_msg = get_option( 'mp_paypal_marketplace_onboard_error_' . get_current_blog_id() );
			$onboard_html = '';
			$onboard_html .= '<p>' . __( 'Verbinde dein PayPal-Konto, um Auszahlungen und Split Payments über den Marktplatz zu ermöglichen. Du wirst zu PayPal weitergeleitet und kannst den Vorgang jederzeit abbrechen.', 'mp' ) . '</p>';
			if ( $merchant_id ) {
				$onboard_html .= '<p style="color:green;"><strong>' . __( 'PayPal-Konto verbunden!', 'mp' ) . '</strong></p>';
				$onboard_html .= '<form method="post" style="margin-top:10px;">';
				$onboard_html .= '<input type="hidden" name="mp_paypal_disconnect" value="1">';
				$onboard_html .= '<button type="submit" class="button">' . __( 'Verbindung trennen', 'mp' ) . '</button>';
				$onboard_html .= wp_nonce_field( 'mp_paypal_disconnect', 'mp_paypal_disconnect_nonce', true, false );
				$onboard_html .= '</form>';
			} else {
				$onboard_url = esc_url( add_query_arg( array( 'mp_paypal_onboard' => 1 ), admin_url() ) );
				$onboard_html .= '<a href="' . $onboard_url . '" class="button button-primary">' . __( 'PayPal-Konto verbinden', 'mp' ) . '</a>';
			}
			if ( $error_msg ) {
				$onboard_html .= '<p style="color:red;">' . esc_html( $error_msg ) . '</p>';
				delete_option( 'mp_paypal_marketplace_onboard_error_' . get_current_blog_id() );
			}
			$onboard_metabox = new WPMUDEV_Metabox( array(
				'id'          => 'mp-settings-paypal-marketplace-onboarding',
				'page_slugs'  => array( 'store-settings-payments', 'store-settings_page_store-settings-payments' ),
				'title'       => __( 'PayPal Marketplace Onboarding', 'mp' ),
				'option_name' => '',
				'order'       => 2,
			) );
			$onboard_metabox->add_field( 'text', array(
				'name'  => 'paypal_marketplace_onboarding_dummy',
				'label' => array( 'text' => '' ),
				'desc'  => $onboard_html,
				'custom' => array('style' => 'display:none'), // Feld selbst verstecken, nur Beschreibung anzeigen
			) );
		}
	}

	/**
	 * Print styles
	 *
	 * @since 3.0
	 * @access public
	 * @action admin_head
	 */
	public function print_styles() {
		if ( 'store-settings_page_store-settings-payments' != get_current_screen()->id || ! ( is_plugin_active_for_network( mp_get_plugin_slug() ) && mp_get_network_setting( 'global_cart' ) ) ) {
			// bail - either not on payments settings screen or global cart is not enabled
			return;
		}

		echo '<style type="text/css">
			#mp-settings-payments, #mp-settings-payments + p.submit { display: none; }
			</style>';
	}

	/**
	 * Handle PayPal disconnect action
	 */
	public function maybe_handle_paypal_disconnect() {
		if ( isset( $_POST['mp_paypal_disconnect'] ) && check_admin_referer( 'mp_paypal_disconnect', 'mp_paypal_disconnect_nonce' ) ) {
			delete_option( 'mp_paypal_marketplace_merchant_id_' . get_current_blog_id() );
			// Optional: Feedback für den User
			add_action( 'admin_notices', function() {
				echo '<div class="notice notice-success is-dismissible"><p>' . __( 'PayPal-Verbindung wurde getrennt.', 'mp' ) . '</p></div>';
			} );
		}
	}

}

MP_Store_Settings_Payments::get_instance();
