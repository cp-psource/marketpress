<?php
add_action( 'wp_ajax_mp_update_currency', array( 'MP_Shop_Einstellungen_General', 'ajax_mp_update_currency' ) );

class MP_Shop_Einstellungen_General {

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
			self::$_instance = new MP_Shop_Einstellungen_General();
		}
		return self::$_instance;
	}

	/**
	 * Gets an updated currency symbol based upon a given currency code
	 *
	 * @since 3.0
	 * @access public
	 * @action wp_ajax_mp_update_currency
	 */
	public static function ajax_mp_update_currency() {
		if ( check_ajax_referer( 'mp_update_currency', 'nonce', false ) ) {
			$currency = mp_format_currency( mp_get_get_value( 'currency' ) );
			wp_send_json_success( $currency );
		}

		wp_send_json_error();
	}

	/**
	 * Constructor function
	 *
	 * @since 3.0
	 * @access private
	 */
	private function __construct() {
		add_action( 'psource_field/print_scripts/base_country', array( &$this, 'update_states_dropdown' ) );
		add_action( 'psource_field/print_scripts/currency', array( &$this, 'update_currency_symbol' ) );
		add_action( 'psource_metabox/after_settings_metabox_saved', array( &$this, 'update_product_post_type' ) );
		add_action( 'init', array( &$this, 'init_metaboxes' ) );

		add_filter( 'psource_field/format_value/tax[rate]', array( &$this, 'format_tax_rate_value' ), 10, 2 );
		add_filter( 'psource_field/sanitize_for_db/tax[rate]', array( &$this, 'save_tax_rate_value' ), 10, 3 );

		foreach ( mp()->provinces['CA'] as $key => $value ) {
			add_filter( 'psource_field/format_value/tax[canada_rate][' . $key . ']', array( &$this, 'format_tax_rate_value' ), 10, 2 );
			add_filter( 'psource_field/sanitize_for_db/tax[canada_rate][' . $key . ']', array( &$this, 'save_tax_rate_value' ), 10, 3 );
		}
	}

	/**
	 * Initialize metaboxes
	 *
	 * @since 3.0
	 * @access public
	 */
	public function init_metaboxes() {
		$this->init_location_settings();
		$this->init_tax_settings();
		if( ! is_multisite() || ! mp_cart()->is_global ) $this->init_currency_settings();
		$this->init_digital_settings();
		$this->init_download_settings();
		$this->init_misc_settings();
		$this->init_advanced_settings();
	}

	/**
	 * Update the product post type
	 *
	 * @since 3.0
	 * @access public
	 * @action psource_metabox/settings_metabox_saved
	 * @uses $wpdb
	 */
	public function update_product_post_type( $metabox ) {
		global $wpdb;

		if ( $metabox->args[ 'id' ] != 'mp-settings-general-advanced-settings' ) {
			return;
		}

		$new_product_post_type = mp_get_setting( 'product_post_type' );
		$old_product_post_type = $new_product_post_type == 'mp_product' ? 'product' : 'mp_product';

		// Check if there is at least 1 product with the old post type
		$check = $wpdb->get_results( "SELECT * FROM {$wpdb->posts} WHERE post_type = '{$old_product_post_type}'", ARRAY_A );
		if ( null === $check ) {
			return;
		}

		$wpdb->update( $wpdb->posts, array( 'post_type' => $new_product_post_type ), array( 'post_type' => $old_product_post_type ) );
		update_option( 'mp_flush_rewrites', 1 );
	}

	/**
	 * Formats the tax rate value from decimal to percentage
	 *
	 * @since 3.0
	 * @access public
	 * @filter psource_field/get_value
	 * @return string
	 */
	public function format_tax_rate_value( $value, $field ) {
		return ( (float)$value * 100 );
	}

	/**
	 * Formats the tax rate value from percentage to decimal prior to saving to db
	 *
	 * @since 3.0
	 * @access public
	 * @filter psource_field/sanitize_for_db
	 * @return string
	 */
	public function save_tax_rate_value( $value, $post_id, $field ) {
		return ( $value > 0 ) ? ($value / 100) : 0;
	}

	/**
	 * Prints javascript for updating the currency symbol when user updates the currency value
	 *
	 * @since 3.0
	 * @access public
	 * @action psource_field/print_scripts/currency
	 */
	public function update_currency_symbol( $field ) {
		?>
		<script type="text/javascript">
			jQuery( document ).ready( function( $ ) {
				var $currency = $( 'select[name="currency"]' );

				$currency.on( 'change', function( e ) {
					var data = [
						{
							"name": "currency",
							"value": $(this).val()
						}, {
							"name": "action",
							"value": "mp_update_currency"
						}, {
							"name": "nonce",
							"value": "<?php echo wp_create_nonce( 'mp_update_currency' ); ?>"
						}
					];

					$currency.mp_select2( 'enable', false ).isWorking( true );

					$.get( ajaxurl, $.param( data ) ).done( function( resp ) {
						$currency.mp_select2( 'enable', true ).isWorking( false );

						if ( resp.success ) {
							$( '.mp-currency-symbol' ).html( resp.data );
						}
					} );
				} );
			} );
		</script>
		<?php
	}

	/**
	 * Prints javascript for updating the base_province dropdown when user updates the base_country value
	 *
	 * @since 3.0
	 * @access public
	 * @action psource_field/print_scripts/base_country
	 */
	public function update_states_dropdown( $field ) {
		?>
		<script type="text/javascript">
			jQuery( document ).ready( function( $ ) {
				var $country = $( 'select[name="base_country"]' ),
					$state = $( 'select[name="base_province"]' );

				$country.on( 'change', function() {
					var data = {
						country: $country.val(),
						action: "mp_update_states_dropdown"
					};

					$country.mp_select2( 'enable', false ).isWorking( true );
					$state.mp_select2( 'enable', false );

					$.post( ajaxurl, data ).done( function( resp ) {
						$country.mp_select2( 'enable', true ).isWorking( false );
						$state.mp_select2( 'enable', true );

						if ( resp.success ) {
							$state.html( resp.data.states );
							$state.trigger( 'change' );
						}
					} );
				} );
			} );
		</script>
		<?php
	}

	/**
	 * Init advanced settings
	 *
	 * @since 3.0
	 * @access public
	 */
	public function init_advanced_settings() {
		$metabox = new PSOURCE_Metabox( array(
			'id'			 => 'mp-settings-general-advanced-settings',
			'page_slugs'	 => array( 'shop-einstellungen', 'toplevel_page_shop-einstellungen' ),
			'title'			 => __( 'Erweiterte Einstellungen', 'mp' ),
			'option_name'	 => 'mp_settings',
		) );

		$metabox->add_field( 'radio_group', array(
			'name'			 => 'product_post_type',
			'label'			 => array( 'text' => __( 'Produktposttyp ändern (Multiple-Shops Lösung)', 'mp' ) ),
			'desc'		 => __( 'Wenn Du Konflikte mit anderen E-Commerce-Plugins hast, ändere diese Einstellung. Dadurch wird der interne Beitragstyp aller Deiner Produkte geändert. <strong> Bitte beachte, dass das Ändern dieser Option möglicherweise Themen oder Plugins von Drittanbietern beschädigt.</strong>', 'mp' ),
			'message'	 => __( 'JA', 'mp' ),
			'default_value'	 => 'product',
			'orientation'	 => 'horizontal',
			'options'		 => array(
				'product'	 => __( 'product (default)', 'mp' ),
				'mp_product'	 => 'mp_product',
			),
		) );
	}

	/**
	 * Init download settings
	 *
	 * @since 3.0
	 * @access public
	 */
	public function init_download_settings() {
		$metabox = new PSOURCE_Metabox( array(
			'id'			 => 'mp-settings-general-downloads',
			'page_slugs'	 => array( 'shop-einstellungen', 'toplevel_page_shop-einstellungen' ),
			'title'			 => __( 'Download Einstellungen', 'mp' ),
			'option_name'	 => 'mp_settings',
		) );
		$metabox->add_field( 'text', array(
			'name'		 => 'max_downloads',
			'label'		 => array( 'text' => __( 'Maximale Downloads', 'mp' ) ),
			'desc'		 => __( 'Wie oft darf ein Kunde eine gekaufte Datei herunterladen? (Es ist am besten, diesen Wert höher als eins einzustellen, falls Probleme beim Herunterladen auftreten.)', 'mp' ),
			'style'		 => 'width:50px;',
			'validation' => array(
				'required'	 => true,
				'digits'	 => true,
			),
		) );
		$metabox->add_field( 'checkbox', array(
			'name'	 => 'use_alt_download_method',
			'label'	 => array( 'text' => __( 'Verwendest Du eine alternative Download-Methode?', 'mp' ) ),
			'desc'	 => __( 'Wenn Du Probleme beim Herunterladen großer Dateien hast und mit Deinem Hosting-Anbieter zusammengearbeitet hast, um Deine Speicherbeschränkungen zu erhöhen, aktiviere diese Option - denke daran, dass dies nicht so sicher ist!', 'mp' ),
		) );
	}

	/**
	 * Init misc settings
	 *
	 * @since 3.0
	 * @access public
	 */
	public function init_misc_settings() {
		$metabox = new PSOURCE_Metabox( array(
			'id'			 => 'mp-settings-general-misc',
			'page_slugs'	 => array( 'shop-einstellungen', 'toplevel_page_shop-einstellungen' ),
			'title'			 => __( 'Verschiedene Einstellungen', 'mp' ),
			'option_name'	 => 'mp_settings',
		) );
		$metabox->add_field( 'text', array(
			'name'		 => 'inventory_threshhold',
			'label'		 => array( 'text' => __( 'Schwellenwert für Inventarwarnung', 'mp' ) ),
			'desc'		 => __( 'Bei welcher geringen Anzahl von Lagerbeständen möchtest Du für Produkte gewarnt werden, für die Du die Bestandsverfolgung aktiviert hast?', 'mp' ),
			'style'		 => 'width:50px;',
			'validation' => array(
				'required'	 => true,
				'digits'	 => true,
			),
		) );
		$metabox->add_field( 'checkbox', array(
			'name'		 => 'inventory_remove',
			'label'		 => array( 'text' => __( 'Nicht vorrätige Produkte ausblenden?', 'mp' ) ),
			'desc'		 => __( 'Dadurch wird das Produkt auf Entwurf gesetzt, wenn das Inventar aller Variationen nicht mehr vorhanden ist.', 'mp' ),
			'message'	 => __( 'JA', 'mp' ),
		) );
		$metabox->add_field( 'checkbox', array(
			'name'		 => 'force_login',
			'label'		 => array( 'text' => __( 'Login erzwingen?', 'mp' ) ),
			'desc'		 => __( 'Gibt an, ob Kunden registriert und zum Auschecken angemeldet sein müssen. (Nicht empfohlen: Durch Aktivieren dieser Option können die Conversions verringert werden.)', 'mp' ),
			'message'	 => __( 'JA', 'mp' ),
		) );
		$metabox->add_field( 'checkbox', array(
			'name'		 => 'disable_cart',
			'label'		 => array( 'text' => __( 'Warenkorb deaktivieren?', 'mp' ) ),
			'desc'		 => __( 'Diese Option verwandelt MarketPress eher in ein Plugin für Produktlisten, wodurch Einkaufswagen, Kaufabwicklung und Bestellverwaltung deaktiviert werden. Dies ist nützlich, wenn Du einfach Produkte auflisten möchtest, die Du in einem anderen Geschäft kaufen kannst, und optional die Schaltflächen "Jetzt kaufen" mit einer externen Website verknüpfen möchtest. Einige Beispiele sind ein Autohaus oder das Verknüpfen mit Songs/Alben in iTunes oder das Verknüpfen mit Produkten auf einer anderen Website mit Ihren eigenen Partnerlinks.', 'mp' ),
			'message'	 => __( 'JA', 'mp' ),
		) );
		$metabox->add_field( 'checkbox', array(
			'name'       => 'show_orders',
			'label'      => array( 'text' => __( 'Seite "Admin-Bestellungen" anzeigen?', 'mp' ) ),
			'desc'		 => __( 'Wenn das Kontrollkästchen deaktiviert ist, wird die Verwaltungsseite für Bestellungen ausgeblendet', 'mp' ),
			'message'	 => __( 'JA', 'mp' ),
			'conditional' => array(
				'name'   => 'disable_cart',
				'value'  => '1',
				'action' => 'show',
			),
		) );
		$metabox->add_field( 'checkbox', array(
			'name'		 => 'disable_minicart',
			'label'		 => array( 'text' => __( 'Deaktiviere den Minikorb', 'mp' ) ),
			'desc'		 => __( 'Diese Option versteckt den schwebenden Minikorb in der oberen rechten Ecke.', 'mp' ),
			'message'	 => __( 'JA', 'mp' ),
		) );
		$metabox->add_field( 'checkbox', array(
			'name'          => 'show_product_image',
			'label'         => array( 'text' => __( 'Produktbild auf Minikorb anzeigen', 'mp' ) ),
			'desc'          => __( 'Möchtest Du das Produktbild auf dem schwebenden Minikorb anzeigen?', 'mp' ),
			'message'       => __( 'JA', 'mp' ),
			'default_value' => true,
		) );
		$metabox->add_field( 'checkbox', array(
			'name'          => 'show_product_qty',
			'label'         => array( 'text' => __( 'Produktbild im Minikorb', 'mp' ) ),
			'desc'          => __( 'Möchtest Du das Produktbild auf einem schwebenden Minikorb anzeigen?', 'mp' ),
			'message'       => __( 'JA', 'mp' ),
			'default_value' => true,
		) );
		$metabox->add_field( 'checkbox', array(
			'name'          => 'show_product_price',
			'label'         => array( 'text' => __( 'Produktpreis im Minikorb', 'mp' ) ),
			'desc'          => __( 'Möchtest Du den Produktpreis im schwebenden Minikorb anzeigen?', 'mp' ),
			'message'       => __( 'JA', 'mp' ),
		) );
		$metabox->add_field( 'radio_group', array(
			'name'			 => 'ga_ecommerce',
			'label'			 => array( 'text' => __( 'Google Analytics Ecommerce Tracking', 'mp' ) ),
			'desc'			 => __( 'Wenn Du Google Analytics bereits für Deine Website verwendest, kannst Du detaillierte E-Commerce-Informationen verfolgen, indem Du diese Einstellung aktivierst. Wähle aus, ob Du den neuen asynchronen oder den alten Tracking-Code verwendest. Bevor Google Analytics E-Commerce-Aktivitäten für Deine Website melden kann, musst Du das E-Commerce-Tracking auf der Seite mit den Profileinstellungen für Deine Website aktivieren. Beachte auch, dass einige Gateways die Belegseite nicht zuverlässig anzeigen, sodass die Nachverfolgung in diesen Fällen möglicherweise nicht korrekt ist. Es wird empfohlen, das PayPal-Gateway zu verwenden, um die genauesten Daten zu erhalten. <a target="_blank" href="http://analytics.blogspot.com/2009/05/how-to-use-ecommerce-tracking-in-google.html">MEHR INFORMATIONEN &raquo;</a>', 'mp' ),
			'default_value'	 => 'none',
			'orientation'	 => 'horizontal',
			'options'		 => array(
				'none'		 => __( 'KEINE', 'mp' ),
				'new'		 => __( 'NEU', 'mp' ),
				'old'		 => __( 'ALT', 'mp' ),
				'universal'	 => __( 'UNIVERSAL', 'mp' ),
			),
		) );
		$metabox->add_field( 'checkbox', array(
			'name'		 => 'special_instructions',
			'label'		 => array( 'text' => __( 'Feld "Spezielle Anweisungen" anzeigen?', 'mp' ) ),
			'desc'		 => __( 'Wenn Du dieses Feld aktivierst, wird auf der Versandkasse ein Textfeld angezeigt, in dem Benutzer spezielle Anweisungen für ihre Bestellung eingeben können. Nützlich für die Produktpersonalisierung usw.', 'mp' ),
			'message'	 => __( 'JA', 'mp' ),
		) );
	}

	/**
	 * Init currency settings
	 *
	 * @since 3.0
	 * @access public
	 */
	public function init_currency_settings() {
		$metabox = new PSOURCE_Metabox( array(
			'id'			 => 'mp-settings-general-currency',
			'page_slugs'	 => array( 'shop-einstellungen', 'toplevel_page_shop-einstellungen' ),
			'title'			 => __( 'Währungseinstellungen', 'mp' ),
			'option_name'	 => 'mp_settings',
		) );

		$currencies	 = mp()->currencies;
		$options	 = array( '' => __( 'Währung wählen', 'mp' ) );

		foreach ( $currencies as $key => $value ) {
			$options[ $key ] = esc_attr( $value[ 0 ] ) . ' - ' . mp_format_currency( $key );
		}

		$metabox->add_field( 'advanced_select', array(
			'name'			 => 'currency',
			'placeholder'	 => __( 'Wähle eine Währung', 'mp' ),
			'multiple'		 => false,
			'label'			 => array( 'text' => __( 'Shopwährung', 'mp' ) ),
			'options'		 => $options,
			'width'			 => 'element',
		) );

		$metabox->add_field( 'radio_group', array(
			'name'			 => 'curr_symbol_position',
			'label'			 => array( 'text' => __( 'Position des Währungssymbols', 'mp' ) ),
			'default_value'	 => '3',
			'orientation'	 => 'horizontal',
			'options'		 => array(
				'1'	 => '<span class="mp-currency-symbol">' . mp_format_currency( mp_get_setting( 'currency', 'EUR' ) ) . '</span>100',
				'2'	 => '<span class="mp-currency-symbol">' . mp_format_currency( mp_get_setting( 'currency', 'EUR' ) ) . '</span> 100',
				'3'	 => '100<span class="mp-currency-symbol">' . mp_format_currency( mp_get_setting( 'currency', 'EUR' ) ) . '</span>',
				'4'	 => '100 <span class="mp-currency-symbol">' . mp_format_currency( mp_get_setting( 'currency', 'EUR' ) ) . '</span>',
			),
		) );

		$metabox->add_field( 'radio_group', array(
			'name'			 => 'price_format',
			'label'			 => array( 'text' => __( 'Preisformat', 'mp' ) ),
			'default_value'	 => 'eu',
			'orientation'	 => 'horizontal',
			'options'		 => array(
				'en'	 => '1,123.45',
				'eu'	 => '1.123,45',
				'frc'	 => '1 123,45',
				'frd'	 => '1 123.45',
			),
		) );

		$metabox->add_field( 'radio_group', array(
			'name'			 => 'curr_decimal',
			'label'			 => array( 'text' => __( 'Dezimalzahl in Preisen anzeigen', 'mp' ) ),
			'default_value'	 => '1',
			'orientation'	 => 'horizontal',
			'options'		 => array(
				'0'	 => '100',
				'1'	 => '100.00',
			),
		) );
	}

	/**
	 * Init tax settings
	 *
	 * @since 3.0
	 * @access public
	 */
	public function init_tax_settings() {
		$metabox = new PSOURCE_Metabox( array(
			'id'			 => 'mp-settings-general-tax',
			'page_slugs'	 => array( 'shop-einstellungen', 'toplevel_page_shop-einstellungen' ),
			'title'			 => __( 'Steuereinstellungen', 'mp' ),
			'option_name'	 => 'mp_settings',
		) );
		$metabox->add_field( 'text', array(
			'name'			 => 'tax[rate]',
			'label'			 => array( 'text' => __( 'Steuersatz', 'mp' ) ),
			'after_field'	 => '%',
			'style'			 => 'width:75px',
			'validation'	 => array(
				'number' => true,
			),
			/*'conditional'	 => array(
				'name'	 => 'base_country',
				'value'	 => 'CA',
				'action' => 'hide',
			),*/
		) );

		// Create field for each canadian province
		foreach ( mp()->provinces['CA'] as $key => $label ) {
			$metabox->add_field( 'text', array(
				'name'			 => 'tax[canada_rate][' . $key . ']',
				'desc'			 => '<a target="_blank" href="http://en.wikipedia.org/wiki/Sales_taxes_in_Canada">' . __( 'Aktuelle Steuersätze', 'mp' ) . '</a>',
				'label'			 => array( 'text' => sprintf( __( '%s Steuersatz', 'mp' ), $label ) ),
				'custom'		 => array( 'style' => 'width:75px' ),
				'after_field'	 => '%',
				'conditional'	 => array(
					'name'	 => 'base_country',
					'value'	 => 'CA',
					'action' => 'show',
				),
			) );
		}

		$metabox->add_field( 'text', array(
			'name'	 => 'tax[label]',
			'label'	 => array( 'text' => __( 'Steueretikett', 'mp' ) ),
			'style'	 => 'width:300px',
			'desc'	 => __( 'Das Etikett für die Steuerposition im Warenkorb. Hier wird die Steuerbezeichnung geändert.', 'mp' ),
		) );
		$metabox->add_field( 'checkbox', array(
			'name'		 => 'tax[tax_shipping]',
			'label'		 => array( 'text' => __( 'Steuer auf Versand', 'mp' ) ),
			'desc'		 => __( 'Bitte beachte die örtlichen Steuergesetze. Die meisten Gebiete erheben Steuern auf Versandkosten.', 'mp' ),
			'message'	 => __( 'JA', 'mp' ),
		) );
		$metabox->add_field( 'checkbox', array(
			'name'		 => 'tax[tax_inclusive]',
			'label'		 => array( 'text' => __( 'Gibst Du Preise inklusive Steuern an?', 'mp' ) ),
			'desc'		 => __( 'Wenn Du diese Option aktivierst, kannst Du alle Preise einschließlich Steuern eingeben und anzeigen, während die Steuersumme weiterhin als Werbebuchung in den Warenkörben aufgeführt wird. Bitte beachte die örtlichen Steuergesetze.', 'mp' ),
			'message'	 => __( 'JA', 'mp' ),
		) );
		$metabox->add_field( 'checkbox', array(
			'name'		 => 'tax[include_tax]',
			'label'		 => array( 'text' => __( 'Preis + Steuer anzeigen', 'mp' ) ),
			'desc'		 => __( 'Wenn Du diese Option aktivierst, wird Preis + Steuern angezeigt, z.B.: Wenn Dein Preis 100€ und Deine Steuer 20% beträgt, beträgt der Preis 120€', 'mp' ),
			'message'	 => __( 'JA', 'mp' ),
		) );
		$metabox->add_field( 'checkbox', array(
			'name'		 => 'tax[tax_label]',
			'label'		 => array( 'text' => __( 'Steueretikett anzeigen', 'mp' ) ),
			'desc'		 => __( 'Wenn Du diese Option aktivierst, wird die Bezeichnung `exkl. Steuer` oder `inkl. Steuer dem Preis hinzugefügt.', 'mp' ),
			'message'	 => __( 'JA', 'mp' ),
		) );
		$metabox->add_field( 'checkbox', array(
			'name'		 => 'tax[tax_digital]',
			'label'		 => array( 'text' => __( 'Steuern auf digitale Produkte anwenden', 'mp' ) ),
			'desc'		 => __( 'Bitte beachte die örtlichen Steuergesetze. Hinweis: Wenn dies aktiviert ist und nur ein Warenkorb zum Herunterladen verfügbar ist, sind die Preise die Standardeinstellungen für Deinen Basisstandort.', 'mp' ),
			'message'	 => __( 'JA', 'mp' ),
		) );
		
		$metabox->add_field( 'radio_group', array(
			'name'			 => 'tax[tax_based]',
			'label'			 => array( 'text' => __( 'Steuersatz berechnen nach?', 'mp' ) ),
			'default_value'	 => 'store_tax',
			'orientation'	 => 'horizontal',
			'options'		 => array(
				'store_tax'	 => __( 'Steuersatz des Shop Basislandes', 'mp' ),
				'user_tax'	 => __( 'Steuersatz nach Kunden Herkunftsland', 'mp' ),
			),
			'conditional' => array(
				'name'   => 'tax[tax_digital]',
				'value'  => '1',
				'action' => 'show',
			),
		) );
		
	}

	/**
	 * Init digital products settings
	 *
	 * @since 3.0
	 * @access public
	 */
	public function init_digital_settings() {
		$metabox = new PSOURCE_Metabox( array(
			'id'			 => 'mp-settings-general-digital',
			'page_slugs'	 => array( 'shop-einstellungen', 'toplevel_page_shop-einstellungen' ),
			'title'			 => __( 'Digitale Einstellungen', 'mp' ),
			'option_name'	 => 'mp_settings',
		) );

		$metabox->add_field( 'checkbox', array(
			'name'		 => 'download_order_limit',
			'label'		 => array( 'text' => __( 'Beschränke digitale Produkte pro Bestellung', 'mp' ) ),
			'desc'		 => __( 'Dadurch wird verhindert, dass dem Warenkorb mehrere gleiche herunterladbare Produktformulare hinzugefügt werden.', 'mp' ),
			'message'	 => __( 'JA', 'mp' ),
		) );

		$metabox->add_field( 'radio_group', array(
			'name'			 => 'details_collection',
			'label'			 => array( 'text' => __( 'Detailsammlung', 'mp' ) ),
			'default_value'	 => 'contact',
			'orientation'	 => 'horizontal',
			'options'		 => array(
				'full'		 => __( 'Vollständige Rechnungsinformationen', 'mp' ),
				'contact'		 => __( 'Nur Kontaktdaten', 'mp' ),
			),
		) );

	}

	/**
	 * Init location settings
	 *
	 * @since 3.0
	 * @access public
	 */
	public function init_location_settings() {
		$metabox = new PSOURCE_Metabox( array(
			'id'			 => 'mp-settings-general-location',
			'page_slugs'	 => array( 'shop-einstellungen', 'toplevel_page_shop-einstellungen' ),
			'title'			 => __( 'Standortseinstellungen', 'mp' ),
			'option_name'	 => 'mp_settings',
		) );
		$metabox->add_field( 'advanced_select', array(
			'name'			 => 'base_country',
			'placeholder'	 => __( 'Land auswählen', 'mp' ),
			'multiple'		 => false,
			'label'			 => array( 'text' => __( 'Basisland', 'mp' ) ),
			'options'		 => array( '' => __( 'Wähle ein Land', 'mp' ) ) + mp_countries(),
			'width'			 => 'element',
			'validation'	 => array(
				'required' => true,
			),
		) );

		$countries_with_states = array();
		foreach ( mp_countries() as $code => $country ) {
			if( property_exists( mp(), $code.'_provinces' ) ) {
				$countries_with_states[] = $code;
			}
		}
		$states = mp_get_states( mp_get_setting( 'base_country' ) );
		$metabox->add_field( 'advanced_select', array(
			'name'			 => 'base_province',
			'placeholder'	 => __( 'Bundesland wählen', 'mp' ),
			'multiple'		 => false,
			'label'			 => array( 'text' => __( 'Bundesland', 'mp' ) ),
			'options'		 => $states,
			'width'			 => 'element',
			'conditional'	 => array(
				'name'	 => 'base_country',
				'value'	 => $countries_with_states,
				'action' => 'show',
			),
			'validation'	 => array(
				'required' => true,
			),
		) );

		$metabox->add_field( 'text', array(
				'name'		 => 'base_adress',
				'label'		 => array( 'text' => __( 'Straße, Hausnummer', 'mp' ) ),
				'custom'	 => array(
					'style' => 'width:300px',
				),
				'validation' => array(
					'required' => true,
				),
			) );
	
		$countries_without_postcode = array_keys( mp()->countries_no_postcode );
		$metabox->add_field( 'text', array(
				'name'			 => 'base_zip',
				'label'			 => array( 'text' => __( 'Postleitzahl', 'mp' ) ),
				'style'			 => 'width:250px;',
				'custom'		 => array(
					'minlength' => 3,
				),
				'conditional'	 => array(
					'name'	 => 'base_country',
					'value'	 => $countries_without_postcode,
					'action' => 'hide',
				),
				'validation'	 => array(
					'required' => true,
				),
			) );
			$metabox->add_field( 'text', array(
				'name'		 => 'zip_label',
				'label'		 => array( 'text' => __( 'Zip/Postal Code Label', 'mp' ) ),
				'custom'	 => array(
					'style' => 'width:300px',
				),
				'validation' => array(
					'required' => true,
				),
			) );
			$metabox->add_field( 'text', array(
				'name'		 => 'base_city',
				'label'		 => array( 'text' => __( 'Stadt/Ort', 'mp' ) ),
				'custom'	 => array(
					'style' => 'width:300px',
				),
				'validation' => array(
					'required' => true,
				),
			) 
		);
	}

}

MP_Shop_Einstellungen_General::get_instance();