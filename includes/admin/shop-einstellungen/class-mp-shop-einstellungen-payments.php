<?php

class MP_Shop_Einstellungen_Payments {

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
			self::$_instance = new MP_Shop_Einstellungen_Payments();
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
	}

	/**
	 * Add payment gateway settings metaboxes
	 *
	 * @since 3.0
	 * @access public
	 */
	public function add_metaboxes() {
		$metabox = new PSOURCE_Metabox( array(
			'id'          => 'mp-settings-payments',
			'page_slugs'  => array( 'shop-einstellungen-payments', 'shop-einstellungen_page_shop-einstellungen-payments' ),
			'title'       => __( 'Zahlungs Gateways', 'mp' ),
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
			'label'   => array( 'text' => __( 'Aktivierte Gateways', 'mp' ) ),
			'desc'    => __( 'Wähle die Gateways aus, die für die Kasse verfügbar sein sollen.', 'mp' ),
			'options' => $options,
			'width'   => '50%',
		) );
	}

	/**
	 * Print styles
	 *
	 * @since 3.0
	 * @access public
	 * @action admin_head
	 */
	public function print_styles() {
		if ( 'shop-einstellungen_page_shop-einstellungen-payments' != get_current_screen()->id || ! ( is_plugin_active_for_network( mp_get_plugin_slug() ) && mp_get_network_setting( 'global_cart' ) ) ) {
			// bail - either not on payments settings screen or global cart is not enabled
			return;
		}

		echo '<style type="text/css">
			#mp-settings-payments, #mp-settings-payments + p.submit { display: none; }
			</style>';
	}

}

MP_Shop_Einstellungen_Payments::get_instance();