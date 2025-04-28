<?php

class MP_Shop_Einstellungen_Notifications {
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
		if ( is_null(self::$_instance) ) {
			self::$_instance = new MP_Shop_Einstellungen_Notifications();
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
		add_action('init', array(&$this, 'init_metaboxes'));
	}

	/**
	 * Init metaboxes
	 *
	 * @since 3.0
	 * @access public
	 */
	public function init_metaboxes() {
		$metabox = new PSOURCE_Metabox(array(
			'id' => 'mp-admin-settings-notifications',
			'page_slugs' => array('shop-einstellungen-notifications', 'shop-einstellungen_page_shop-einstellungen-notifications'),
			'title' => __('Admin-Benachrichtigungseinstellungen', 'mp'),
			'option_name' => 'mp_settings',
		));
		$metabox->add_field('text', array(
			'name' => 'store_email',
			'label' => array('text' => __('Benachrichtungs Email', 'mp')),
			'validation' => array(
				'email' => 1,
			),
		));

		$new_order = $metabox->add_field('complex', array(
			'name' => 'email[admin_order]',
			'label' => array('text' => __('Neue Bestellung', 'mp')),
			'layout' => 'rows',
		));

		if ( $new_order instanceof PSOURCE_Field ) {
			$new_order->add_field('checkbox', array(
				'name' => 'send_email',
				'message' => __('Aktiviert', 'mp'),
			));
			$new_order->add_field('text', array(
				'name' => 'subject',
				'label' => array('text' => __('Betreff', 'mp')),
				'validation' => array(
					'required' => true,
				),
			));
			$new_order->add_field('textarea', array(
				'name' => 'text',
				'label' => array('text' => __('Text', 'mp')),
				'custom' => array('rows' => 15),
				'validation' => array(
					'required' => true,
				),
			));
		}

		$customer_metabox = new PSOURCE_Metabox(array(
			'id' => 'mp-customer-settings-notifications',
			'page_slugs' => array('shop-einstellungen-notifications', 'shop-einstellungen_page_shop-einstellungen-notifications'),
			'title' => __('Einstellungen für Kundenbenachrichtigungen', 'mp'),
			'option_name' => 'mp_settings',
		));

		$new_order_section = $customer_metabox->add_field('section', array(
			'name' => 'new_order_section',
			'title' => __('Neue Bestellung', 'mp'),
			'subtitle' => __('Diese Codes werden durch Bestelldaten ersetzt: CUSTOMERNAME, ORDERID, ORDERINFO, SHIPPINGINFO, PAYMENTINFO, TOTAL, TRACKINGURL. Kein HTML erlaubt.<br/>Bei Bestellungen mit manueller Zahlung werden die hier festgelegten E-Mails durch die E-Mails überschrieben, die in den Einstellungen für manuelle Zahlungen auf der Seite Zahlungseinstellungen konfiguriert wurden', 'mp'),
			'before_field' => '<div id="new_order_tabs_wrapper">',
		));

		$new_order_tab_labels = $customer_metabox->add_field('tab_labels', array(
			'name' => 'new_order_tabs_labels',
			'tabs' => array(
				array(
					"active"=> true,
					"label"=> __('Physische Bestellungen', 'mp'),
					"slug"=>"new_order_tab"
				),
				array(
					"active" => false,
					"label" => __( 'Bestellungen für digitale Downloads', 'mp' ),
					"slug" => "new_order_downloads_tab"
				),
				array(
					"active" => false,
					"label" => __( 'Gemischte Bestellungen', 'mp' ),
					"slug" => "new_order_mixed_tab",
				),
			),
		));

		$new_order_tab = $customer_metabox->add_field('tab', array(
			'name' => 'new_order_tab',
			'slug' => 'new_order_tab',
		));

		$new_order = $customer_metabox->add_field('complex', array(
			'name' => 'email[new_order]',
			'label' => array('text' => __('Neue Bestellung', 'mp')),
			'layout' => 'rows',
		));

		if ( $new_order instanceof PSOURCE_Field ) {
			$new_order->add_field('checkbox', array(
				'name' => 'send_email',
				'message' => __('Aktiviert', 'mp'),
			));
			$new_order->add_field('text', array(
				'name' => 'subject',
				'label' => array('text' => __('Betreff', 'mp')),
				'validation' => array(
					'required' => true,
				),
			));
			$new_order->add_field('textarea', array(
				'name' => 'text',
				'label' => array('text' => __('Text', 'mp')),
				'custom' => array('rows' => 15),
				'validation' => array(
					'required' => true,
				),
			));
		}

		$new_order_downloads_tab = $customer_metabox->add_field('tab', array(
			'name' => 'new_order_downloads_tab',
			'slug' => 'new_order_downloads_tab',
		));

		$new_order_downloads = $customer_metabox->add_field('complex', array(
			'name' => 'email[new_order_downloads]',
			'label' => array('text' => __('Neue Bestellung - Digitale Downloads nur Bestellungen', 'mp')),
			'layout' => 'rows',
		));

		if ( $new_order_downloads instanceof PSOURCE_Field ) {
			$new_order_downloads->add_field('checkbox', array(
				'name' => 'send_email',
				'message' => __('Aktiviert', 'mp'),
			));
			$new_order_downloads->add_field('text', array(
				'name' => 'subject',
				'label' => array('text' => __('Betreff', 'mp')),
				'validation' => array(
					'required' => true,
				),
			));
			$new_order_downloads->add_field('textarea', array(
				'name' => 'text',
				'label' => array('text' => __('Text', 'mp')),
				'custom' => array('rows' => 15),
				'validation' => array(
					'required' => true,
				),
			));
		}

		$new_order_mixed_tab = $customer_metabox->add_field('tab', array(
			'name' => 'new_order_mixed_tab',
			'slug' => 'new_order_mixed_tab',
		));

		$new_order_mixed = $customer_metabox->add_field('complex', array(
			'name' => 'email[new_order_mixed]',
			'label' => array('text' => __('Neue Bestellung - Gemischt', 'mp')),
			'layout' => 'rows',
			'after_field' => '</div>', // close the #new_order_tabs_wrapper div
		));

		if ( $new_order_mixed instanceof PSOURCE_Field ) {
			$new_order_mixed->add_field('checkbox', array(
				'name' => 'send_email',
				'message' => __('Aktiviert', 'mp'),
			));
			$new_order_mixed->add_field('text', array(
				'name' => 'subject',
				'label' => array('text' => __('Betreff', 'mp')),
				'validation' => array(
					'required' => true,
				),
			));
			$new_order_mixed->add_field('textarea', array(
				'name' => 'text',
				'label' => array('text' => __('Text', 'mp')),
				'custom' => array('rows' => 15),
				'validation' => array(
					'required' => true,
				),
			));
		}

		$order_shipped_section = $customer_metabox->add_field('section', array(
			'name' => 'order_shipped_section',
			'title' => __('Bestellung versandt', 'mp'),
			'subtitle' => __('Diese Codes werden durch Bestelldaten ersetzt: CUSTOMERNAME, ORDERID, ORDERINFO, SHIPPINGINFO, PAYMENTINFO, TOTAL, TRACKINGURL. Kein HTML erlaubt.', 'mp'),
			'before_field' => '<div id="order_shipped_tabs_wrapper">',
		));

		$order_shiped_tab_labels = $customer_metabox->add_field('tab_labels', array(
			'name' => 'tabs_labels',
			'tabs' => array(
				array(
					"active"=> true,
					"label"=> __('Physische Bestellungen', 'mp'),
					"slug"=>"order_shipped_tab"
				),
				array(
					"active" => false,
					"label" => __( 'Bestellungen für digitale Downloads', 'mp' ),
					"slug" => "order_shipped_downloads_tab"
				),
				array(
					"active" => false,
					"label" => __( 'Gemischte Bestellungen', 'mp' ),
					"slug" => "order_shipped_mixed_tab",
				),
			),
		));

		$order_shipped_tab = $customer_metabox->add_field('tab', array(
			'name' => 'order_shipped_tab',
			'slug' => 'order_shipped_tab',
		));

		$order_shipped = $customer_metabox->add_field('complex', array(
			'name' => 'email[order_shipped]',
			'label' => array('text' => __('Bestellung versandt', 'mp')),
			'layout' => 'rows',
		));

		if ( $order_shipped instanceof PSOURCE_Field ) {
			$order_shipped->add_field('checkbox', array(
				'name' => 'send_email',
				'message' => __('Aktiviert', 'mp'),
			));
			$order_shipped->add_field('text', array(
				'name' => 'subject',
				'label' => array('text' => __('Betreff', 'mp')),
				'validation' => array(
					'required' => true,
				),
			));
			$order_shipped->add_field('textarea', array(
				'name' => 'text',
				'label' => array('text' => __('Text', 'mp')),
				'custom' => array('rows' => 15),
				'validation' => array(
					'required' => true,
				),
			));
		}

		$order_shipped_downloads_tab = $customer_metabox->add_field('tab', array(
			'name' => 'order_shipped_downloads_tab',
			'slug' => 'order_shipped_downloads_tab',
		));

		$order_shipped_downloads = $customer_metabox->add_field( 'complex', array(
			'name' => 'email[order_shipped_downloads]',
			'label' => array( 'text' => __( 'Bestellung versandt - Digitale Downloads nur Bestellungen', 'mp' ) ),
			'layout' => 'rows',
		) );

		if ( $order_shipped_downloads instanceof PSOURCE_Field ) {
			$order_shipped_downloads->add_field('checkbox', array(
				'name' => 'send_email',
				'message' => __('Aktiviert', 'mp'),
			));
			$order_shipped_downloads->add_field( 'text', array(
				'name' => 'subject',
				'label' => array( 'text' => __( 'Betreff', 'mp' ) ),
				'validation' => array(
					'required' => true,
				),
			) );
			$order_shipped_downloads->add_field( 'textarea', array(
				'name' => 'text',
				'label' => array( 'text' => __( 'Text', 'mp' ) ),
				'custom' => array( 'rows' => 15 ),
				'validation' => array(
					'required' => true,
				),
			) );
		}

		$order_shipped_mixed_tab = $customer_metabox->add_field('tab', array(
			'name' => 'order_shipped_mixed_tab',
			'slug' => 'order_shipped_mixed_tab',
		));

		$order_shipped_mixed = $customer_metabox->add_field( 'complex', array(
			'name' => 'email[order_shipped_mixed]',
			'label' => array( 'text' => __( 'Bestellung versandt - Gemischte Bestellungen (mit digitalen und physischen Produkten)', 'mp' ) ),
			'layout' => 'rows',
			'after_field' => '</div>', // close the #order_shipped_tabs_wrapper div
		) );

		if ( $order_shipped_mixed instanceof PSOURCE_Field ) {
			$order_shipped_mixed->add_field('checkbox', array(
				'name' => 'send_email',
				'message' => __('Aktiviert', 'mp'),
			));
			$order_shipped_mixed->add_field( 'text', array(
				'name' => 'subject',
				'label' => array( 'text' => __( 'Betreff', 'mp' ) ),
				'validation' => array(
					'required' => true,
				),
			) );
			$order_shipped_mixed->add_field( 'textarea', array(
				'name' => 'text',
				'label' => array( 'text' => __( 'Text', 'mp' ) ),
				'custom' => array( 'rows' => 15 ),
				'validation' => array(
					'required' => true,
				),
			) );
		}

		// This field is outside the #order_shipped_tabs_wrapper div, so it won't be appended to the last tab
		$customer_metabox->add_field('checkbox', array(
			'name' => 'email_registration_email',
			'message' => __('JA', 'mp'),
			'label' => array( 'text' => __('Benachrichtigung an Registrierungs-E-Mail statt Rechnungs-E-Mail?', 'mp')),
		));
	}
}

MP_Shop_Einstellungen_Notifications::get_instance();