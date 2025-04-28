<?php

class MP_Shop_Einstellungen_Presentation {

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
			self::$_instance = new MP_Shop_Einstellungen_Presentation();
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
		add_filter( 'psource_field/after_field', array( &$this, 'display_create_page_button' ), 10, 2 );
		add_action( 'psource_field/print_scripts', array( &$this, 'create_store_page_js' ) );

		if ( mp_get_get_value( 'page' ) == 'shop-einstellungen-presentation' ) {
			add_action( 'init', array( &$this, 'init_metaboxes' ) );
			add_action( 'psource_metabox/after_settings_metabox_saved', array( &$this, 'link_store_pages' ) );
		}
	}

	/**
	 *
	 * @param $psource_metabox
	 */
	public function link_store_pages( $psource_metabox ) {
		if ( $psource_metabox->args['id'] == 'mp-settings-presentation-pages-slugs' ) {
			$pages = mp_get_post_value( 'pages' );
			foreach ( $pages as $type => $page ) {
				MP_Pages_Admin::get_instance()->save_store_page_value( $type, $page, false );
			}
			// Refresh rewrite rules.
			update_option( 'mp_flush_rewrites', 1 );
		}
	}

	/**
	 * Print scripts for creating store page
	 *
	 * @since 3.0
	 * @access public
	 * @action psource_field/print_scripts
	 */
	public function create_store_page_js( $field ) {
		if ( $field->args['original_name'] !== 'pages[store]' ) {
			return;
		}
		?>
		<script type="text/javascript">
			jQuery(document).ready(function ($) {
				$('.mp-create-page-button').on('click', function (e) {
					e.preventDefault();

					var $this = $(this),
						$select = $this.siblings('[name^="pages"]');

					$this.isWorking(true);

					$.getJSON($this.attr('href'), function (resp) {
						if (resp.success) {
							$select.attr('data-select2-value', resp.data.select2_value).mp_select2('val', resp.data.post_id).trigger('change');
							$this.isWorking(false).replaceWith(resp.data.button_html);
						} else {
							alert('<?php _e( 'Beim Erstellen der Shopseite ist ein Fehler aufgetreten. Bitte versuche es erneut.', 'mp' ); ?>');
							$this.isWorking(false);
						}
					});
				});
			});
		</script>
		<?php
	}

	/**
	 * Initialize metaboxes
	 *
	 * @since 3.0
	 * @access public
	 */
	public function init_metaboxes() {
		$this->init_general_settings();
		$this->init_product_page_settings();
		$this->init_related_product_settings();
		$this->init_product_list_settings();
		$this->init_social_settings();
		$this->init_store_pages_slugs_settings();
		$this->init_miscellaneous_settings();
	}

	/**
	 * Gets the appropriate image size label for a given size.
	 *
	 * @since 3.0
	 * @access public
	 *
	 * @param string $size The image size.
	 *
	 * @return string
	 */
	public function get_image_size_label( $size ) {
		$width  = get_option( "{$size}_size_w" );
		$height = get_option( "{$size}_size_h" );
		$crop   = get_option( "{$size}_crop" );

		return "{$width} x {$height} (" . ( ( $crop ) ? __( 'geschnitten', 'mp' ) : __( 'ungeschnitten', 'mp' ) ) . ')';
	}

	/**
	 * Display "create page" button next to a given field
	 *
	 * @since 3.0
	 * @access public
	 * filter psource_field/after_field
	 */
	public function display_create_page_button( $html, $field ) {
		switch ( $field->args['original_name'] ) {
			case 'pages[store]' :
				$type = 'store';
				break;

			case 'pages[products]' :
				$type = 'products';
				break;

			case 'pages[cart]' :
				$type = 'cart';
				break;

			case 'pages[checkout]' :
				$type = 'checkout';
				break;

			case 'pages[order_status]' :
				$type = 'order_status';
				break;
				
			/*case 'pages[mp_agb]' :
				$type = 'mp_agb';
				break;*/
		}

		if ( isset( $type ) ) {
			if ( ( $post_id = mp_get_setting( "pages->$type" ) ) && get_post_status( $post_id ) !== false ) {
				return '<a target="_blank" class="button mp-edit-page-button" href="' . add_query_arg( array(
					'post'   => $post_id,
					'action' => 'edit',
				), get_admin_url( null, 'post.php' ) ) . '">' . __( 'Seite bearbeiten', 'mp' ) . '</a>';
			} else {
				return '<a class="button mp-create-page-button" href="' . wp_nonce_url( get_admin_url( null, 'admin-ajax.php?action=mp_create_store_page&type=' . $type ), 'mp_create_store_page' ) . '">' . __( 'Seite erstellen', 'mp' ) . '</a>';
			}
		}

		return $html;
	}

	/**
	 * Init the store page/slugs settings
	 *
	 * @since 3.0
	 * @access public
	 */
	public function init_store_pages_slugs_settings() {
		$metabox = new PSOURCE_Metabox( array(
			'id'          => 'mp-settings-presentation-pages-slugs',
			'page_slugs'  => array( 'shop-einstellungen-presentation', 'shop-einstellungen_page_shop-einstellungen-presentation' ),
			'title'       => __( 'Shopseiten', 'mp' ),
			'option_name' => 'mp_settings',
		) );
		$metabox->add_field( 'post_select', array(
			'name'        => 'pages[store]',
			'label'       => array( 'text' => __( 'Shopportal', 'mp' ) ),
			'desc'        => __( 'Diese Seite wird als Stammverzeichnis für Deinen MarketPress Shop verwendet.', 'mp' ),
			'query'       => array( 'post_type' => 'page', 'orderby' => 'title', 'order' => 'ASC' ),
			'placeholder' => __( 'Eine Seite auswählen', 'mp' ),
			'validation'  => array(
				'required' => true,
			),
		) );
		$metabox->add_field( 'post_select', array(
			'name'        => 'pages[products]',
			'label'       => array( 'text' => __( 'Produktliste', 'mp' ) ),
			'query'       => array( 'post_type' => 'page', 'orderby' => 'title', 'order' => 'ASC' ),
			'placeholder' => __( 'Wähle eine Seite', 'mp' ),
			'validation'  => array(
				'required' => true,
			),
		) );
		$metabox->add_field( 'post_select', array(
			'name'        => 'pages[cart]',
			'label'       => array( 'text' => __( 'Warenkorb', 'mp' ) ),
			'query'       => array( 'post_type' => 'page', 'orderby' => 'title', 'order' => 'ASC' ),
			'placeholder' => __( 'Wähle eine Seite', 'mp' ),
			'validation'  => array(
				'required' => true,
			),
		) );
		$metabox->add_field( 'post_select', array(
			'name'        => 'pages[checkout]',
			'label'       => array( 'text' => __( 'Kassa', 'mp' ) ),
			'query'       => array( 'post_type' => 'page', 'orderby' => 'title', 'order' => 'ASC' ),
			'placeholder' => __( 'Wähle eine Seite', 'mp' ),
			'validation'  => array(
				'required' => true,
			),
		) );
		$metabox->add_field( 'post_select', array(
			'name'        => 'pages[order_status]',
			'label'       => array( 'text' => __( 'Bestellungen', 'mp' ) ),
			'query'       => array( 'post_type' => 'page', 'orderby' => 'title', 'order' => 'ASC' ),
			'placeholder' => __( 'Wähle eine Seite', 'mp' ),
			'validation'  => array(
				'required' => true,
			),
		) );
		/*$metabox->add_field( 'post_select', array(
			'name'        => 'pages[mp_agb]',
			'label'       => array( 'text' => __( 'Allgemeine Geschäftsbedingungen', 'mp' ) ),
			'query'       => array( 'post_type' => 'page', 'orderby' => 'title', 'order' => 'ASC' ),
			'placeholder' => __( 'Oder wähle eine Seite mit dem Slug: "mp_agb"', 'mp' ),
			'validation'  => array(
				'required' => true,
			),
		) );*/
	}

	/**
	 * Init the product list settings
	 *
	 * @since 3.0
	 * @access public
	 */
	public function init_social_settings() {
		$metabox = new PSOURCE_Metabox( array(
			'id'          => 'mp-settings-presentation-social',
			'page_slugs'  => array( 'shop-einstellungen-presentation', 'shop-einstellungen_page_shop-einstellungen-presentation' ),
			'title'       => __( 'Soziale Netzwerke', 'mp' ),
			'option_name' => 'mp_settings',
		) );

		$metabox->add_field( 'section', array(
			'name'  => 'section_pinterest',
			'title' => __( 'Pinterest', 'mp' ),
		) );

		$metabox->add_field( 'radio_group', array(
			'name'          => 'social[pinterest][show_pinit_button]',
			'label'         => array( 'text' => __( 'Zeige "Pin It" Button', 'mp' ) ),
			'options'       => array(
				'off'         => __( 'Aus', 'mp' ),
				'single_view' => __( 'Einzelansicht', 'mp' ),
				'all_view'    => __( 'Alle Ansichten', 'mp' ),
			),
			'default_value' => 'off',
		) );

		$metabox->add_field( 'radio_group', array(
			'name'    => 'social[pinterest][show_pin_count]',
			'label'   => array( 'text' => __( 'Pin Count', 'mp' ) ),
			'options' => array(
				'none'   => __( 'Nein', 'mp' ),
				'above'  => __( 'Oberhalb', 'mp' ),
				'beside' => __( 'Seitlich', 'mp' ),
			),
		) );

		$metabox->add_field( 'section', array(
			'name'  => 'section_facebook',
			'title' => __( 'Facebook', 'mp' ),
		) );

		$metabox->add_field( 'radio_group', array(
			'name'          => 'social[facebook][show_facebook_like_button]',
			'label'         => array( 'text' => __( 'Zeige Facebook Like Button', 'mp' ) ),
			'options'       => array(
				'off'         => __( 'Aus', 'mp' ),
				'single_view' => __( 'Einzelansicht', 'mp' ),
				'all_view'    => __( 'Alle Ansichten', 'mp' ),
			),
			'default_value' => 'off',
		) );

		$metabox->add_field( 'radio_group', array(
			'name'    => 'social[facebook][action]',
			'label'   => array( 'text' => __( 'Action', 'mp' ) ),
			'options' => array(
				'like'      => __( 'Like', 'mp' ),
				'recommend' => __( 'Empfehlen', 'mp' ),
			),
		) );

		$metabox->add_field( 'checkbox', array(
			'name'    => 'social[facebook][show_share]',
			'label'   => array( 'text' => __( 'Zeige Teilen Button', 'mp' ) ),
			'message' => __( 'JA', 'mp' ),
		) );

		$metabox->add_field( 'section', array(
			'name'  => 'section_twitter',
			'title' => __( 'Twitter', 'mp' ),
		) );

		$metabox->add_field( 'radio_group', array(
			'name'    => 'social[twitter][show_twitter_button]',
			'label'   => array( 'text' => __( 'Zeige Twitter Button', 'mp' ) ),
			'options' => array(
				'off'         => __( 'Aus', 'mp' ),
				'single_view' => __( 'Einzelansicht', 'mp' ),
				'all_view'    => __( 'Alle Ansichten', 'mp' ),
			),
		) );
	}

	/**
	 * Init the product list settings
	 *
	 * @since 3.0
	 * @access public
	 */
	public function init_product_list_settings() {
		$metabox = new PSOURCE_Metabox( array(
			'id'          => 'mp-settings-presentation-product-list',
			'page_slugs'  => array( 'shop-einstellungen-presentation', 'shop-einstellungen_page_shop-einstellungen-presentation' ),
			'title'       => __( 'Produktliste/Rastereinstellungen', 'mp' ),
			'desc'        => __( 'Zeige Deine Produkte als Liste oder als Raster.', 'mp' ),
			'option_name' => 'mp_settings',
		) );
		$metabox->add_field( 'radio_group', array(
			'name'    => 'list_view',
			'label'   => array( 'text' => __( 'Produktlayout', 'mp' ) ),
			'options' => array(
				'list' => __( 'Als Liste anzeigen', 'mp' ),
				'grid' => __( 'Als Raster anzeigen', 'mp' ),
			),
			'default_value' => 'grid',
		) );
		$metabox->add_field( 'radio_group', array(
			'name'          => 'per_row',
			'label'         => array( 'text' => __( 'Wie viele Produkte pro Reihe?', 'mp' ) ),
			'desc'          => __( 'Stelle die Anzahl der Produkte ein, die in einer Rasterzeile angezeigt werden, damit sie am besten zu Deinem Theme passen', 'mp' ),
			'default_value' => 3,
			'options'       => array(
				1 => __( 'Eins', 'mp' ),
				2 => __( 'Zwei', 'mp' ),
				3 => __( 'Drei', 'mp' ),
				4 => __( 'Vier', 'mp' ),
			),
			'conditional'   => array(
				'name'   => 'list_view',
				'value'  => 'grid',
				'action' => 'show',
			),
		) );
		$metabox->add_field( 'radio_group', array(
			'name'    => 'list_button_type',
			'label'   => array( 'text' => __( 'In den Warenkorb Aktion', 'mp' ) ),
			'desc'    => __( 'MarketPress unterstützt zwei "Flows" zum Hinzufügen von Produkten zum Warenkorb. Nach dem Hinzufügen eines Produkts zum Warenkorb können zwei Dinge passieren:', 'mp' ),
			'options' => array(
				'addcart' => __( 'Auf der aktuellen Produktseite bleiben', 'mp' ),
				'buynow'  => __( 'Zur sofortigen Kaufabwicklung auf die Warenkorbseite umleiten', 'mp' ),
			),
		) );
		$metabox->add_field( 'checkbox', array(
			'name'    => 'show_thumbnail',
			'label'   => array( 'text' => __( 'Produkt-Miniaturansicht anzeigen?', 'mp' ) ),
			'message' => __( 'JA', 'mp' ),
		) );

		$metabox->add_field( 'checkbox', array(
			'name'    => 'show_thumbnail_placeholder',
			'label'   => array( 'text' => __( 'Miniaturansicht des Standardproduktplatzhalters anzeigen, wenn das Produktbild nicht verfügbar ist?', 'mp' ) ),
			'message' => __( 'JA', 'mp' ),
		) );

		$metabox->add_field( 'file', array(
			'name'        => 'thumbnail_placeholder',
			'label'       => array( 'text' => __( 'Wählen Sie die Standard-Miniaturansicht des Platzhalterbilds aus, wenn das Produktbild nicht verfügbar ist (falls leer, wird das integrierte Bild des Plugins verwendet).', 'mp' ) ),
			'message'     => __( 'JA', 'mp' ),
			'conditional' => array(
				'name'   => 'show_thumbnail_placeholder',
				'value'  => '1',
				'action' => 'show',
			),
		) );

		$metabox->add_field( 'select', array(
			'name'        => 'list_img_size',
			'label'       => array( 'text' => __( 'Bildgröße', 'mp' ) ),
			'options'     => array(
				'thumbnail' => sprintf( __( 'Vorschau - %s', 'mp' ), $this->get_image_size_label( 'thumbnail' ) ),
				'medium'    => sprintf( __( 'Medium - %s', 'mp' ), $this->get_image_size_label( 'medium' ) ),
				'large'     => sprintf( __( 'Groß - %s', 'mp' ), $this->get_image_size_label( 'large' ) ),
				'custom'    => __( 'Benutzerdefiniert', 'mp' ),
			),
			'conditional' => array(
				'name'   => 'show_thumbnail',
				'value'  => '1',
				'action' => 'show',
			),
		) );
		$custom_size = $metabox->add_field( 'complex', array(
			'name'        => 'list_img_size_custom',
			'label'       => array( 'text' => __( 'Benutzerdefinierte Bildgröße', 'mp' ) ),
			'conditional' => array(
				'operator' => 'AND',
				'action'   => 'show',
				array(
					'name'  => 'show_thumbnail',
					'value' => '1',
				),
				array(
					'name'  => 'list_img_size',
					'value' => 'custom',
				)
			),
		) );

		if ( $custom_size instanceof PSOURCE_Field ) {
			$custom_size->add_field( 'text', array(
				'name'       => 'width',
				'label'      => array( 'text' => __( 'Weite', 'mp' ) ),
				'validation' => array(
					'required' => true,
					'digits'   => true,
					'min'      => 0,
				),
			) );
			$custom_size->add_field( 'text', array(
				'name'       => 'height',
				'label'      => array( 'text' => __( 'Höhe', 'mp' ) ),
				'validation' => array(
					'required' => true,
					'digits'   => true,
					'min'      => 0,
				),
			) );
		}

		$metabox->add_field( 'radio_group', array(
			'name'        => 'image_alignment_list',
			'label'       => array( 'text' => __( 'Bildbeschreibungen', 'mp' ) ),
			'options'     => array(
				'alignnone'		 => __( 'Keine', 'mp' ),
				'aligncenter'	 => __( 'Zentriert', 'mp' ),
				'alignleft'  => __( 'Links', 'mp' ),
				'alignright' => __( 'Rechts', 'mp' ),
			),
			'default_value' => 'alignleft',
			'conditional' => array(
				'operator' => 'AND',
				'action'   => 'show',
				array(
					'name'  => 'show_thumbnail',
					'value' => '1',
				),
				array(
					'name'  => 'list_view',
					'value' => 'list',
				),
			),
		) );
		$metabox->add_field( 'checkbox', array(
			'name'    => 'show_excerpts',
			'label'   => array( 'text' => __( 'Zeige Auszüge?', 'mp' ) ),
			'message' => __( 'JA', 'mp' ),
		) );
		$metabox->add_field( 'text', array(
			'name'          => 'excerpts_length',
			'label'         => array( 'text' => __( 'Auszugslänge', 'mp' ) ),
			'conditional'   => array(
				'name'   => 'show_excerpts',
				'value'  => '1',
				'action' => 'show',
			),
			'validation'    => array(
				'required' => true,
				'digits'   => 1,
			),
			'default_value' => 55
		) );
		$metabox->add_field( 'checkbox', array(
			'name'    => 'paginate',
			'label'   => array( 'text' => __( 'Produkte paginieren?', 'mp' ) ),
			'message' => __( 'JA', 'mp' ),
		) );
		$metabox->add_field( 'text', array(
			'name'        => 'per_page',
			'label'       => array( 'text' => __( 'Anzahl Produkte pro Seite', 'mp' ) ),
			'conditional' => array(
				'name'   => 'paginate',
				'value'  => '1',
				'action' => 'show',
			),
			'validation'  => array(
				'required' => true,
				'digits'   => 1,
			),
		) );
		$metabox->add_field( 'select', array(
			'name'    => 'order_by',
			'label'   => array( 'text' => __( 'Produkte sortieren nach', 'mp' ) ),
			'options' => array(
				'title'  => __( 'Produktname', 'mp' ),
				'date'   => __( 'Veröffentlichung', 'mp' ),
				'ID'     => __( 'Produkt ID', 'mp' ),
				'author' => __( 'Verkäufer', 'mp' ),
				'sales'  => __( 'Verkäufe', 'mp' ),
				'price'  => __( 'Preis', 'mp' ),
				'rand'   => __( 'Zufällig', 'mp' ),
			),
		) );
		$metabox->add_field( 'radio_group', array(
			'name'    => 'order',
			'label'   => array( 'text' => __( 'Sortierreihenfolge', 'mp' ) ),
			'options' => array(
				'DESC' => __( 'Absteigend', 'mp' ),
				'ASC'  => __( 'Aufsteigend', 'mp' ),
			),
		) );
		$metabox->add_field( 'checkbox', array(
			'name'    => 'hide_products_filter',
			'label'   => array( 'text' => __( 'Produktfilter verbergen?', 'mp' ) ),
			'message' => __( 'JA', 'mp' ),
			'desc'    => __( 'Wenn diese Option aktiviert ist, können Benutzer Produkte nicht nach Kategorien filtern und/oder nach Veröffentlichungsdatum/Name/Preis bestellen.', 'mp' ),
			'default_value' => 0
		) );
	}

	public function init_miscellaneous_settings() {
		$metabox = new PSOURCE_Metabox( array(
			'id'          => 'mp-settings-miscellaneous-product-list',
			'page_slugs'  => array( 'shop-einstellungen-presentation', 'shop-einstellungen_page_shop-einstellungen-presentation' ),
			'title'       => __( 'Verschiedene Einstellungen', 'mp' ),
			'desc'        => __( '', 'mp' ),
			'option_name' => 'mp_settings',
		) );

		$metabox->add_field( 'text', array(
			'name'          => 'per_page_order_history',
			'label'         => array( 'text' => __( 'Auftragsstatuseinträge pro Seite', 'mp' ) ),
			'default_value' => get_option( 'posts_per_page' ),
			'validation'    => array(
				'required' => true,
				'digits'   => 1,
			),
		) );
	}

	/**
	 * Init the related product settings
	 *
	 * @since 3.0
	 * @access public
	 */
	public function init_related_product_settings() {
		$metabox = new PSOURCE_Metabox( array(
			'id'          => 'mp-settings-presentation-product-related',
			'page_slugs'  => array( 'shop-einstellungen-presentation', 'shop-einstellungen_page_shop-einstellungen-presentation' ),
			'title'       => __( 'Ähnliche Produkteinstellungen', 'mp' ),
			'option_name' => 'mp_settings',
		) );
		$metabox->add_field( 'checkbox', array(
			'name'    => 'related_products[show]',
			'label'   => array( 'text' => __( 'Zeige ähnliche Produkte?', 'mp' ) ),
			'message' => __( 'JA', 'mp' ),
		) );
		$metabox->add_field( 'text', array(
			'name'        => 'related_products[show_limit]',
			'label'       => array( 'text' => __( 'Ähnliche Produkte Limit', 'mp' ) ),
			'conditional' => array(
				'name'   => 'related_products[show]',
				'value'  => '1',
				'action' => 'show',
			),
			'validation'  => array(
				'required' => true,
				'digits'   => 1,
			),
		) );
		$metabox->add_field( 'select', array(
			'name'        => 'related_products[relate_by]',
			'label'       => array( 'text' => __( 'Ähnliche Produkte nach', 'mp' ) ),
			'options'     => array(
				'both'     => __( 'Kategorie &amp; Tags', 'mp' ),
				'category' => __( 'Nur Kategorien', 'mp' ),
				'tags'     => __( 'Nur Tags', 'mp' ),
			),
			'conditional' => array(
				'name'   => 'related_products[show]',
				'value'  => '1',
				'action' => 'show',
			),
		) );
		$metabox->add_field( 'radio_group', array(
			'name'        => 'related_products[view]',
			'label'       => array( 'text' => __( 'Ähnliche Produkte Darstellung', 'mp' ) ),
			'message'     => __( 'JA', 'mp' ),
			'options'     => array(
				'list' => __( 'Als Liste anzeigen', 'mp' ),
				'grid' => __( 'Als Raster anzeigen', 'mp' ),
			),
			'default_value' => 'grid',
			'conditional' => array(
				'name'   => 'related_products[show]',
				'value'  => '3',
				'action' => 'show',
			),
		) );

		$metabox->add_field( 'radio_group', array(
			'name'          => 'related_products[per_row]',
			'label'         => array( 'text' => __( 'Wie viele Produkte pro Reihe?', 'mp' ) ),
			'desc'          => __( 'Stelle die Anzahl der Produkte ein, die in einer Rasterzeile angezeigt werden, damit sie am besten zu Deinem Thema passen', 'mp' ),
			'default_value' => 3,
			'options'       => array(
				1 => __( 'One', 'mp' ),
				2 => __( 'Two', 'mp' ),
				3 => __( 'Three', 'mp' ),
				4 => __( 'Four', 'mp' ),
			),
			'conditional'   => array(
				'name'   => 'related_products[view]',
				'value'  => 'grid',
				'action' => 'show',
			),
		) );
	}

	/**
	 * Init the general settings
	 *
	 * @since 3.0
	 * @access public
	 */
	public function init_product_page_settings() {
		$metabox = new PSOURCE_Metabox( array(
			'id'          => 'mp-settings-presentation-product-page',
			'page_slugs'  => array( 'shop-einstellungen-presentation', 'shop-einstellungen_page_shop-einstellungen-presentation' ),
			'title'       => __( 'Produktseiteneinstellungen', 'mp' ),
			'desc'        => __( 'Einstellungen für die Anzeige einzelner Produktseiten.', 'mp' ),
			'option_name' => 'mp_settings',
		) );
		$metabox->add_field( 'radio_group', array(
			'name'    => 'product_button_type',
			'label'   => array( 'text' => __( 'In den Warenkorb Aktion', 'mp' ) ),
			'desc'    => __( 'MarketPress unterstützt zwei "Flows" zum Hinzufügen von Produkten zum Warenkorb. Nach dem Hinzufügen eines Produkts zum Warenkorb können zwei Dinge passieren:', 'mp' ),
			'options' => array(
				'addcart' => __( 'Auf der aktuellen Produktseite bleiben', 'mp' ),
				'buynow'  => __( 'Zur sofortigen Kaufabwicklung auf die Warenkorbseite umleiten', 'mp' ),
			),
		) );
		$metabox->add_field( 'checkbox', array(
			'name'    => 'show_quantity',
			'label'   => array( 'text' => __( 'Mengenfeld anzeigen?', 'mp' ) ),
			'message' => __( 'JA', 'mp' ),
			'desc'    => __( 'Wenn diese Option aktiviert ist, können Benutzer auswählen, wie viele Produkte sie kaufen möchten, bevor sie sie in ihren Warenkorb legen. Wenn nicht aktiviert, kann die Menge später auf der Warenkorbseite geändert werden.', 'mp' ),
		) );
		$metabox->add_field( 'checkbox', array(
			'name'          => 'show_single_excerpt',
			'label'         => array( 'text' => __( 'Produktauszug anzeigen', 'mp' ) ),
			'message'       => __( 'JA', 'mp' ),
			'desc'          => __( 'Wenn aktiviert, wird der Beschreibungsauszug über "In den Warenkorb" hinzugefügt.', 'mp' ),
			'default_value' => 1,
		) );
		$metabox->add_field( 'checkbox', array(
			'name'    => 'show_single_categories',
			'label'   => array( 'text' => __( 'Kategorieliste anzeigen', 'mp' ) ),
			'message' => __( 'JA', 'mp' ),
			'desc'    => __( 'Möchtest Du eine Liste der Produktkategorien anzeigen?', 'mp' ),
		) );
		$metabox->add_field( 'checkbox', array(
			'name'    => 'show_single_tags',
			'label'   => array( 'text' => __( 'Tags Liste anzeigen', 'mp' ) ),
			'message' => __( 'JA', 'mp' ),
			'desc'    => __( 'Möchtest Du eine Liste mit Produkttags anzeigen lassen?', 'mp' ),
		) );
		$metabox->add_field( 'checkbox', array(
			'name'    => 'show_img',
			'label'   => array( 'text' => __( 'Produktbild anzeigen?', 'mp' ) ),
			'message' => __( 'JA', 'mp' ),
		) );
		$metabox->add_field( 'select', array(
			'name'        => 'product_img_size',
			'label'       => array( 'text' => __( 'Bildgröße', 'mp' ) ),
			'options'     => array(
				'thumbnail' => sprintf( __( 'Vorschau - %s', 'mp' ), $this->get_image_size_label( 'thumbnail' ) ),
				'medium'    => sprintf( __( 'Medium - %s', 'mp' ), $this->get_image_size_label( 'medium' ) ),
				'large'     => sprintf( __( 'Groß - %s', 'mp' ), $this->get_image_size_label( 'large' ) ),
				'custom'    => __( 'Benutzerdefiniert', 'mp' ),
			),
			'conditional' => array(
				'name'   => 'show_img',
				'value'  => '1',
				'action' => 'show',
			),
		) );
		$custom_size = $metabox->add_field( 'complex', array(
			'name'        => 'product_img_size_custom',
			'label'       => array( 'text' => __( 'Benutzerdefinierte Bildgröße', 'mp' ) ),
			'conditional' => array(
				'operator' => 'AND',
				'action'   => 'show',
				array(
					'name'  => 'show_img',
					'value' => '1',
				),
				array(
					'name'  => 'product_img_size',
					'value' => 'custom',
				)
			),
		) );

		if ( $custom_size instanceof PSOURCE_Field ) {
			$custom_size->add_field( 'text', array(
				'name'       => 'width',
				'label'      => array( 'text' => __( 'Weite', 'mp' ) ),
				'validation' => array(
					'required' => true,
					'digits'   => true,
					'min'      => 0,
				),
			) );
			$custom_size->add_field( 'text', array(
				'name'       => 'height',
				'label'      => array( 'text' => __( 'Höhe', 'mp' ) ),
				'validation' => array(
					'required' => true,
					'digits'   => true,
					'min'      => 0,
				),
			) );
		}

		$metabox->add_field( 'radio_group', array(
			'name'        => 'image_alignment_single',
			'label'       => array( 'text' => __( 'Bildausrichtung', 'mp' ) ),
			'options'     => array(
				//'alignnone'		 => __( 'None', 'mp' ),
				'alignleft'   => __( 'Links', 'mp' ),
				'aligncenter' => __( 'Zentriert', 'mp' ),
				'alignright'  => __( 'Rechts', 'mp' ),
			),
			'default_value' => 'alignleft',
			'conditional' => array(
				'name'   => 'show_img',
				'value'  => '1',
				'action' => 'show',
			),
		) );
		$metabox->add_field( 'checkbox', array(
			'name'        => 'disable_large_image',
			'label'       => array( 'text' => __( 'Anzeige großer Bilder deaktivieren?', 'mp' ) ),
			'message'     => __( 'JA', 'mp' ),
			'conditional' => array(
				'name'   => 'show_img',
				'value'  => '1',
				'action' => 'show',
			),
		) );
		$metabox->add_field( 'checkbox', array(
			'name'        => 'show_lightbox',
			'label'       => array( 'text' => __( 'Verwende die integrierte Lightbox für Bilder?', 'mp' ) ),
			'desc'        => __( 'Wenn Du Konflikte mit der Lightbox-Bibliothek Deines Themes oder eines anderen Plugins hast, solltest Du dies deaktivieren.', 'mp' ),
			'message'     => __( 'JA', 'mp' ),
			'conditional' => array(
				'operator' => 'AND',
				'action'   => 'show',
				array(
					'name'  => 'show_img',
					'value' => '1',
				),
				array(
					'name'  => 'disable_large_image',
					'value' => '-1',
				),
			),
		) );
	}

	/**
	 * Init the general settings
	 *
	 * @since 3.0
	 * @access public
	 */
	public function init_general_settings() {
		$metabox = new PSOURCE_Metabox( array(
			'id'          => 'mp-settings-presentation-general',
			'page_slugs'  => array( 'shop-einstellungen-presentation', 'shop-einstellungen_page_shop-einstellungen-presentation' ),
			'title'       => __( 'Allgemeine Einstellungen', 'mp' ),
			'option_name' => 'mp_settings',
		) );

		$metabox->add_field( 'radio_group', array(
			'name'    => 'store_theme',
			'desc'    => sprintf( __( 'Diese Option ändert die integrierten CSS-Stile für Shopseiten. Speicher für einen benutzerdefinierten CSS-Stil Deine CSS-Datei mit der Kopfzeile <strong>/* MarketPress Style: Dein CSS-Themenname hier */</strong> im Ordner <strong> "%s" </strong>, (wir haben diesen Ordner bereits in das MarketPress integriert, Du musst ihn nur noch verschieben) dann erscheinen sie in dieser Liste, damit Du sie auswählen kannst. Du solltest "Keine" auswählen, wenn Du keine benutzerdefinierten CSS-Stile verwenden möchtest oder wenn Du Standard-Designvorlagen oder benutzerdefinierte Designvorlagen und CSS verwendest, um Dein eigenes, völlig einzigartiges Shop-Design zu erstellen (z.B.: mit Hilfe des Upfront-Kreators). Weitere Informationen zu benutzerdefinierten Designvorlagen findest Du <a target="_blank" href="%s">hier &raquo;</a>.', 'mp' ), trailingslashit( WP_CONTENT_DIR ) . 'marketpress-styles/', mp_plugin_url( 'ui/themes/Theming_MarketPress.txt' ) ),
			'label'   => array( 'text' => __( 'Shopdesign', 'mp' ) ),
			'options' => mp_get_theme_list() + array(
				'default' => __( 'Standard - Verwenden von Standard-CSS', 'mp' ),
				'none' => __( 'Keine - Ohne spezielles CSS', 'mp' ),
				),
			'width'   => '50%',
		) );
		$metabox->add_field( 'checkbox', array(
			'name'		 => 'show_purchase_breadcrumbs',
			'label'		 => array( 'text' => __( 'Zeige Brotkrumen?', 'mp' ) ),
			'message'	 => __( 'JA', 'mp' ),
			'desc'		 => __( 'Zeigt vorherige, aktuelle und nächste Schritte beim Auschecken eines Kunden an - angezeigt unter dem Titel.', 'mp' ),
		) );
	}

}

MP_Shop_Einstellungen_Presentation::get_instance();