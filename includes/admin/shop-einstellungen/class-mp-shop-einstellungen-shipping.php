<?php

class MP_Shop_Einstellungen_Shipping {

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
			self::$_instance = new MP_Shop_Einstellungen_Shipping();
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
		add_action( 'admin_footer', array( &$this, 'print_scripts' ) );
	}

	/**
	 * Prints the necessary javascript
	 *
	 * @since 3.0
	 * @access public
	 */
	public function print_scripts() {
		if ( get_current_screen()->id != 'shop-einstellungen_page_shop-einstellungen-shipping' ) {
			return;
		}
		?>
		<script type="text/javascript">
			( function( $ ) {
				$( document ).ready( function() {
					// Select all European countries
					$( 'li.select2-eu' ).on( 'click', function() {
						var $this = $( this ),
							$input = $( '[name="shipping[allowed_countries]"]' ),
							euCountries = $this.attr( 'data-countries' );

						$input.val( euCountries ).trigger( 'change' );
					} );

					// Update weight/dimension labels
					$( 'input[name="shipping[system]"]' ).on('change', function() {
						switch ( $( this ).val() ) {
							case 'english' :
								$( '.mp-dimension-label' ).find( '.mp-units' ).html( 'in' );
								$( '.mp-weight-label' ).find( '.mp-units' ).html( 'lbs' );
								break;

							case 'metric' :
								$( '.mp-dimension-label' ).find( '.mp-units' ).html( 'cm' );
								$( '.mp-weight-label' ).find( '.mp-units' ).html( 'kgs' );
								break;
						}
					} );
				} );
			}( jQuery ) );
		</script>
		<?php

	}

	/**
	 * Add payment gateway settings metaboxes
	 *
	 * @since 3.0
	 * @access public
	 */
	public function add_metaboxes() {
		$metabox = new PSOURCE_Metabox( array(
			'id'			 => 'mp-settings-shipping-plugins',
			'page_slugs'	 => array( 'shop-einstellungen-shipping', 'shop-einstellungen_page_shop-einstellungen-shipping' ),
			'title'			 => __( 'Allgemeine Versandeinstellungen', 'mp' ),
			'option_name'	 => 'mp_settings',
			'order'			 => 1,
		) );

		// Target Countries
		$metabox->add_field( 'advanced_select', array(
			'name'					 => 'shipping[allowed_countries]',
			'label'					 => array( 'text' => __( 'Zielländer', 'mp' ) ),
			'desc'					 => __( 'Dies sind die Länder, in die Du versendest.', 'mp' ),
			'options'				 => mp_popular_country_list() + array( 'all_countries' => __( 'Alle Länder', 'mp' ) ) + mp_country_list(), //all_countries|disabled
			'default_value'			 => array( 'AT' => __( 'Österreich', 'mp' ) ),
			'placeholder'			 => __( 'Länder auswählen', 'mp' ),
			'format_dropdown_header' => '
				<ul class="select2-all-none">
					<li class="select2-none">' . __( 'Keines', 'mp' ) . '</li>
					<li class="select2-all">' . __( 'Alle', 'mp' ) . '</li>
					<li class="select2-eu" data-countries="' . implode( ',', mp()->eu_countries ) . '">' . __( 'EU', 'mp' ) . '</li>
				</ul>',
		) );

		// Shipping Methods
		$options		 = array( 'none' => __( 'Abholung', 'mp' ) );
		$plugins		 = MP_Shipping_API::get_plugins();
		$has_calculated	 = false;

		foreach ( $plugins as $code => $plugin ) {
			if ( $plugin[ 2 ] ) {
				$has_calculated = true;
				continue;
			}

			$options[ $code ] = $plugin[ 1 ];
		}

		if ( $has_calculated ) {
			$options[ 'calculated' ] = __( 'Versandanbieter', 'mp' );
		}

		$metabox->add_field( 'radio_group', array(
			'name'			 => 'shipping[method]',
			'label'			 => array( 'text' => __( 'Versandart', 'mp' ) ),
			'options'		 => $options,
			'default_value'	 => 'none',
		) );

		// Selected Calculated Shipping Options
		$options = array();
		foreach ( $plugins as $slug => $plugin ) {
			if ( $plugin[ 2 ] ) {
				$options[ $slug ] = $plugin[ 1 ];
			}
		}

		$metabox->add_field( 'checkbox_group', array(
			'name'				 => 'shipping[calc_methods]',
			'label'				 => array( 'text' => __( 'Wähle Versandoptionen', 'mp' ) ),
			'desc'				 => __( 'Wähle aus, welche berechneten Versandmethoden der Kunde auswählen kann.', 'mp' ),
			'options'			 => $options,
			'use_options_values' => true,
			'conditional'		 => array(
				'name'	 => 'shipping[method]',
				'value'	 => 'calculated',
				'action' => 'show',
			),
		) );

		// Measurement System
		$metabox->add_field( 'radio_group', array(
			'name'			 => 'shipping[system]',
			'label'			 => array( 'text' => __( 'Masseinheiten', 'mp' ) ),
			'options'		 => array(
				'english'	 => __( 'Pfund', 'mp' ),
				'metric'	 => __( 'Kilogram', 'mp' ),
			),
			'default_value'	 => 'metric',
		) );
	}

}

MP_Shop_Einstellungen_Shipping::get_instance();