<?php

/*
  MarketPress Manual Payments Gateway Plugin
  Author: DerN3rd (WMS N@W)
 */

class MP_Gateway_ManualPayments extends MP_Gateway_API {

	//private gateway slug. Lowercase alpha (a-z) and dashes (-) only please!
	var $plugin_name				 = 'manual_payments';
	//name of your gateway, for the admin side.
	var $admin_name				 = '';
	//public name of your gateway, for lists and such.
	var $public_name				 = 'Manual Payment';
	//url for an image for your checkout method. Displayed on method form
	var $method_img_url			 = '';
	//url for an submit button image for your checkout method. Displayed on checkout form if set
	var $method_button_img_url	 = '';
	//whether or not ssl is needed for checkout page
	var $force_ssl				 = false;
	//always contains the url to send payment notifications to if needed by your gateway. Populated by the parent class
	var $ipn_url;
	//whether if this is the only enabled gateway it can skip the payment_form step
	var $skip_form				 = false;

	/*	 * **** Below are the public methods you may overwrite via a plugin ***** */

	/**
	 * Runs when your class is instantiated. Use to setup your plugin instead of __construct()
	 */
	function on_creation() {
		//set names here to be able to translate
		$this->admin_name	 = __( 'Überweisung/Rechnung', 'mp' );
		$public_name		 = $this->get_setting( 'name', __( 'Überweisung/Rechnung', 'mp' ) );
		$this->public_name	 = empty( $public_name ) ? __( 'Überweisung/Rechnung', 'mp' ) : $public_name;

		add_filter( 'mp_order/notification_body/manual_payments', array( &$this, 'order_confirmation_email' ), 10, 2 );
		add_filter( 'mp_order/confirmation_text/' . $this->plugin_name, array( &$this, 'order_confirmation_text' ), 10, 2 );
	}

	/**
	 * Display the payment form
	 *
	 * @since 3.0
	 * @access public
	 * @param array $cart. Contains the cart contents for the current blog
	 * @param array $shipping_info. Contains shipping info and email in case you need it
	 */
	public function payment_form( $cart, $shipping_info ) {
		return do_shortcode( wpautop($this->get_setting( 'instruction' )) );
	}

	/**
	 * Use this to do the final payment. Create the order then process the payment. If
	 * you know the payment is successful right away go ahead and change the order status
	 * as well.
	 *
	 * @param MP_Cart $cart. Contains the MP_Cart object.
	 * @param array $billing_info. Contains billing info and email in case you need it.
	 * @param array $shipping_info. Contains shipping info and email in case you need it
	 */
	function process_payment( $cart, $billing_info, $shipping_info ) {
		$payment_info							 = array();
		$payment_info[ 'gateway_public_name' ]	 = $this->public_name;
		$payment_info[ 'gateway_private_name' ]	 = $this->admin_name;
		$payment_info[ 'gateway_plugin_name' ]	 = $this->plugin_name;
		$payment_info[ 'status' ][ time() ]		 = __( 'Rechnungsübersicht', 'mp' );
		$payment_info[ 'total' ]				 = $cart->total();
		$payment_info[ 'currency' ]				 = mp_get_setting( 'currency' );
		$payment_info[ 'method' ]				 = __( 'Überweisung/Rechnung', 'mp' );

		$order = new MP_Order();
		/*$order->save( array(
			'payment_info'	 => $payment_info,
			'cart'			 => $cart,
			'paid'			 => false,
		) );*/

		$order->save( array(
			'cart'			 => $cart,
			'payment_info'	 => $payment_info,
			'paid'			 => false
		) );

		wp_redirect( $order->tracking_url( false ) );
		exit;
	}

	/**
	 * Filter the order confirmation email text
	 *
	 * @since 3.0
	 * @access public
	 * @filter mp_order/notification_body/manual_payments
	 */
	function order_confirmation_email( $msg, $order ) {
		if ( $email_text = $this->get_setting( 'email' ) ) {
			$msg = mp_filter_email( $order, $email_text );
		}

		return $msg;
	}

	/**
	 * Filter the order confirmation text that shows up on the order status page
	 *
	 * @since 3.0
	 * @access public
	 * @filter mp_order/confirmation_text/manual_payments
	 */
	function order_confirmation_text( $content, $order ) {
		return $content . wpautop( str_replace( array( 'TOTAL', 'ORDERID' ), array( mp_format_currency( $order->get_meta( 'mp_payment_info->currency' ), $order->get_meta( 'mp_payment_info->total' ) ) , $order->get_id() ), $this->get_setting( 'confirmation' ) ) );
	}

	/**
	 * Initialize the settings metabox
	 *
	 * @since 3.0
	 * @access public
	 */
	public function init_settings_metabox() {
		$metabox = new PSOURCE_Metabox( array(
			'id'			 => $this->generate_metabox_id(),
			'page_slugs'	 => array( 'shop-einstellungen-payments', 'shop-einstellungen_page_shop-einstellungen-payments' ),
			'title'			 => sprintf( __( '%s Einstellungen', 'mp' ), $this->admin_name ),
			'option_name'	 => 'mp_settings',
			'desc'			 => __( 'Erfasse Zahlungen manuell, z. B. per Bargeld, Scheck oder Überweisung.', 'mp' ),
			'conditional'	 => array(
				'name'	 => 'gateways[allowed][' . $this->plugin_name . ']',
				'value'	 => 1,
				'action' => 'show',
			),
		) );
		$metabox->add_field( 'text', array(
			'name'			 => $this->get_field_name( 'name' ),
			'default_value'	 => $this->public_name,
			'label'			 => array( 'text' => __( 'Zahlungsart Bezeichnung', 'mp' ) ),
			'desc'			 => __( 'Gib einen öffentlichen Namen für diese Zahlungsmethode ein, der den Benutzern angezeigt wird - Kein HTML', 'mp' ),
			'save_callback'	 => array( 'strip_tags' ),
		) );
		$metabox->add_field( 'wysiwyg', array(
			'name'	 => $this->get_field_name( 'instruction' ),
			'label'	 => array( 'text' => __( 'Bezahlanleitung', 'mp' ) ),
			'desc'	 => __( 'Dies sind die manuellen Zahlungsanweisungen, die auf dem Zahlungsbildschirm angezeigt werden.', 'mp' ),
		) );
		$metabox->add_field( 'wysiwyg', array(
			'name'	 => $this->get_field_name( 'confirmation' ),
			'label'	 => array( 'text' => __( 'Bestätigung Benutzeranweisungen', 'mp' ) ),
			'desc'	 => __( 'Dies sind die manuellen Zahlungsanweisungen, die auf dem Bestellbestätigungsbildschirm angezeigt werden. TOTAL wird durch die Bestellsumme ersetzt und ORDERID wird durch die Bestellnummer ersetzt.', 'mp' ),
		) );
		$metabox->add_field( 'textarea', array(
			'name'			 => $this->get_field_name( 'email' ),
			'label'			 => array( 'text' => __( 'Bestellbestätigungs-E-Mail', 'mp' ) ),
			'desc'			 => __( 'Dies ist der E-Mail-Text, der an diejenigen gesendet werden soll, die manuelle Zahlungsabwicklungen durchgeführt haben. Du solltest hier Deine manuellen Zahlungsanweisungen einfügen. Es überschreibt die Standard-Bestell-Checkout-E-Mail. Diese Codes werden durch Bestelldaten ersetzt: CUSTOMERNAME, ORDERID, ORDERINFO, SHIPPINGINFO, PAYMENTINFO, TOTAL, TRACKINGURL. Kein HTML erlaubt.', 'mp' ),
			'custom'		 => array( 'rows' => 10 ),
			'save_callback'	 => array( 'strip_tags' ),
		) );
	}

}

mp_register_gateway_plugin( 'MP_Gateway_ManualPayments', 'manual_payments', __( 'Überweisung/Rechnung', 'mp' ) );