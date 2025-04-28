<?php

class MP_Shop_Einstellungen_Admin {

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
			self::$_instance = new MP_Shop_Einstellungen_Admin();
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
		mp_include_dir( mp_plugin_dir( 'includes/admin/shop-einstellungen/' ) );

		// Add menu items
		add_action( 'admin_menu', array( &$this, 'add_menu_items' ) );
		// Print scripts for setting the active admin menu item when on the product tag page
		add_action( 'admin_footer', array( &$this, 'print_product_tag_scripts' ) );
		// Print scripts for setting the active admin menu item when on the product category page
		add_action( 'admin_footer', array( &$this, 'print_product_category_scripts' ) );
		// Move product categories and tags to store settings menu
		add_action( 'parent_file', array( &$this, 'set_menu_item_parent' ) );

		if ( mp_get_get_value( 'action' ) == 'mp_add_product_attribute' || mp_get_get_value( 'action' ) == 'mp_edit_product_attribute' ) {
			MP_Product_Attributes_Admin::add_product_attribute_metaboxes();
			add_action( 'psource_metabox/before_save_fields/mp-shop-einstellungen-product-attributes-add', array( 'MP_Product_Attributes_Admin', 'save_product_attribute' ) );
			add_action( 'shop-einstellungen_page_shop-einstellungen-productattributes', array( &$this, 'display_settings_form' ) );

			if ( mp_get_get_value( 'action' ) == 'mp_edit_product_attribute' ) {
				add_filter( 'psource_field/before_get_value', array( 'MP_Product_Attributes_Admin', 'get_product_attribute_value' ), 10, 4 );
			}
		} else {
			$screen_ids = array(
				'toplevel_page_shop-einstellungen',
				'shop-einstellungen_page_shop-einstellungen-presentation',
				'shop-einstellungen_page_shop-einstellungen-notifications',
				'shop-einstellungen_page_shop-einstellungen-shipping',
				'shop-einstellungen_page_shop-einstellungen-payments',
				'shop-einstellungen_page_shop-einstellungen-importers',
				'shop-einstellungen_page_shop-einstellungen-exporters',
				'shop-einstellungen_page_shop-einstellungen-capabilities',
				'shop-einstellungen_page_shop-einstellungen-import',
				'shop-einstellungen_page_store-setup-wizard',
			);

			foreach ( $screen_ids as $screen_id ) {
				add_action( $screen_id, array( &$this, 'display_settings_form' ) );
			}

			// Product attributes list.
			add_action( 'shop-einstellungen_page_shop-einstellungen-productattributes', array( 'MP_Product_Attributes_Admin', 'display_product_attributes' ) );
		}
        
        add_action( 'psource_metabox/after_settings_metabox_saved/mp-settings-presentation-pages-slugs', array( $this, 'reset_store_pages_cache' ) );
	}

	/**
	 * Set menu item parent file
	 *
	 * @since 3.0
	 * @access public
	 * @action parent_file
	 */
	public function set_menu_item_parent( $parent_file ) {
		switch ( get_current_screen()->taxonomy ) {
			case 'product_category' :
			case 'product_tag' :
				$parent_file = 'shop-einstellungen';
				break;
		}

		return $parent_file;
	}

	/**
	 * Add items to the admin menu
	 *
	 * @since 3.0
	 * @access public
	 * @uses $wp_version
	 */
	public function add_menu_items() {
		global $wp_version;

		//store settings
		$cap = apply_filters( 'mp_store_settings_cap', 'manage_store_settings' );

		
		add_menu_page( __( 'Shopeinstellungen', 'mp' ), __( 'Shopeinstellungen', 'mp' ), $cap, 'shop-einstellungen', null, 'dashicons-store', 99.33 );
		
		add_submenu_page( 'shop-einstellungen', __( 'Shopeinstellungen: Einstellungen', 'mp' ), __( 'Einstellungen', 'mp' ), $cap, 'shop-einstellungen', array( &$this, 'display_settings_form' ) );
		add_submenu_page( 'shop-einstellungen', __( 'Shopeinstellungen: Darstellung', 'mp' ), __( 'Darstellung', 'mp' ), $cap, 'shop-einstellungen-presentation', array( &$this, 'display_settings_form' ) );
		add_submenu_page( 'shop-einstellungen', __( 'Shopeinstellungen: Benachrichtigung', 'mp' ), __( 'Benachrichtigungen', 'mp' ), $cap, 'shop-einstellungen-notifications', array( &$this, 'display_settings_form' ) );
		add_submenu_page( 'shop-einstellungen', __( 'Shopeinstellungen: Versand', 'mp' ), __( 'Versand', 'mp' ), $cap, 'shop-einstellungen-shipping', array( &$this, 'display_settings_form' ) );
		add_submenu_page( 'shop-einstellungen', __( 'Shopeinstellungen: Zahlungen', 'mp' ), __( 'Zahlungen', 'mp' ), $cap, 'shop-einstellungen-payments', array( &$this, 'display_settings_form' ) );
		add_submenu_page( 'shop-einstellungen', __( 'Shopeinstellungen: Eigenschaften', 'mp' ), __( 'Eigenschaften', 'mp' ), $cap, 'shop-einstellungen-productattributes', array( 'MP_Product_Attributes_Admin', 'display_product_attributes' ) );
		add_submenu_page( 'shop-einstellungen', __( 'Shopeinstellungen: Shopkategorien', 'mp' ), __( 'Shopkategorien', 'mp' ), apply_filters( 'mp_manage_product_categories_cap', 'manage_product_categories' ), 'edit-tags.php?taxonomy=product_category&post_type=' . MP_Product::get_post_type() );
		add_submenu_page( 'shop-einstellungen', __( 'Shopeinstellungen: Schlagworte', 'mp' ), __( 'Schlagworte', 'mp' ), apply_filters( 'mp_manage_product_tags_cap', 'manage_product_tags' ), 'edit-tags.php?taxonomy=product_tag&post_type=' . MP_Product::get_post_type() );
		add_submenu_page( 'shop-einstellungen', __( 'Shopeinstellungen: Benutzerrechte', 'mp' ), __( 'Benutzerrechte', 'mp' ), $cap, 'shop-einstellungen-capabilities', array( &$this, 'display_settings_form' ) );
		add_submenu_page( 'shop-einstellungen', __( 'Shopeinstellungen: Import/Export', 'mp' ), __( 'Import/Export', 'mp' ), $cap, 'shop-einstellungen-import', array( MP_Shop_Einstellungen_Import::get_instance(), 'display_settings' ) );
		//add_submenu_page('shop-einstellungen', __('Shopeinstellungen: Importers', 'mp'), __('Importers', 'mp'), $cap, 'shop-einstellungen-importers', false);
		//add_submenu_page('shop-einstellungen', __('Shopeinstellungen: Exporters', 'mp'), __('Exporters', 'mp'), $cap, 'shop-einstellungen-exporters', false);
		add_submenu_page( 'shop-einstellungen', __( 'Shopeinstellungen: Erweiterungen', 'mp' ), __( 'Erweiterungen', 'mp' ), $cap, 'shop-einstellungen-addons', array( MP_Shop_Einstellungen_Addons::get_instance(), 'display_settings' ) );

		$mp_needs_quick_setup = get_option( 'mp_needs_quick_setup', 1 );

		// Zeigt Schnelleinrichtungs Link, auch wenn der Administrator das Setup "übersprungen" hat
		if ( $mp_needs_quick_setup == 'skip' || $mp_needs_quick_setup == 1 && current_user_can( 'manage_options' ) || (isset( $_GET[ 'quick_setup_step' ] )) ) {
			add_submenu_page( 'shop-einstellungen', __( 'Schnelleinrichtung', 'mp' ), __( 'Schnelleinrichtung', 'mp' ), $cap, 'store-setup-wizard', array( &$this, 'display_settings_form' ) );
		}

		if ( !PSOURCE_REMOVE_BRANDING ) {
			add_action( 'load-toplevel_page_shop-einstellungen', array( &$this, 'add_help_tab' ) );
			add_action( 'shop-einstellungen_page_shop-einstellungen-presentation', array( &$this, 'add_help_tab' ) );
			add_action( 'shop-einstellungen_page_shop-einstellungen-notifications', array( &$this, 'add_help_tab' ) );
			add_action( 'shop-einstellungen_page_shop-einstellungen-shipping', array( &$this, 'add_help_tab' ) );
			add_action( 'shop-einstellungen_page_shop-einstellungen-payments', array( &$this, 'add_help_tab' ) );
			add_action( 'shop-einstellungen_page_shop-einstellungen-product-attributes', array( &$this, 'add_help_tab' ) );
			add_action( 'shop-einstellungen_page_shop-einstellungen-capabilities', array( &$this, 'add_help_tab' ) );
			add_action( 'shop-einstellungen_page_shop-einstellungen-importers', array( &$this, 'add_help_tab' ) );
			add_action( 'shop-einstellungen_page_shop-einstellungen-exporters', array( &$this, 'add_help_tab' ) );
			add_action( 'shop-einstellungen_page_store-setup-wizard', array( &$this, 'add_help_tab' ) );
			add_action( 'shop-einstellungen_page_shop-einstellungen-addons', array( &$this, 'add_help_tab' ) );
		}
	}

	/**
	 * Add help tab to current screen
	 *
	 * @since 3.0
	 * @access public
	 */
	public function add_help_tab() {
		MP_Admin::get_instance()->add_help_tab();
	}

	/**
	 * Print scripts for setting the active admin menu item when on the product tag page
	 *
	 * @since 3.0
	 * @access public
	 */
	public function print_product_tag_scripts() {
		if ( mp_get_current_screen()->id != 'edit-product_tag' ) {
			return false;
		}
		?>
		<script type="text/javascript">
			jQuery( document ).ready( function( $ ) {
				$( 'a[href="edit-tags.php?taxonomy=product_tag&post_type=<?php echo MP_Product::get_post_type(); ?>"]' ).addClass( 'current' ).parent().addClass( 'current' );
			} );
		</script>
		<?php
	}

	/**
	 * Print scripts for setting the active admin menu item when on the product category page
	 *
	 * @since 3.0
	 * @access public
	 */
	public function print_product_category_scripts() {
		if ( mp_get_current_screen()->id != 'edit-product_category' ) {
			return false;
		}
		?>
		<script type="text/javascript">
			jQuery( document ).ready( function( $ ) {
				$( 'a[href="edit-tags.php?taxonomy=product_category&post_type=<?php echo MP_Product::get_post_type(); ?>"]' ).addClass( 'current' ).parent().addClass( 'current' );
			} );
		</script>
		<?php
	}

	/**
	 * Gets an appropriate message by it's key
	 *
	 * @since 3.0
	 * @access public
	 */
	public function get_message_by_key( $key ) {
		$messages = array(
			'mp_product_attribute_added'	 => __( 'Produktattribut erfolgreich hinzugefügt.', 'mp' ),
			'mp_product_attribute_updated'	 => __( 'Produktattribut erfolgreich aktualisiert.', 'mp' ),
		);

		return ( isset( $messages[ $key ] ) ) ? $messages[ $key ] : sprintf( __( 'Für den Schlüssel „%s“ konnte keine passende Nachricht gefunden werden.', 'mp' ), $key );
	}

	/**
	 * Displays the settings form/metaboxes
	 *
	 * @since 3.0
	 * @access public
	 */
	public function display_settings_form() {
		$updated = false;
		$title	 = __( 'Shopeinstellungen', 'mp' ) . ': ';

		switch ( mp_get_current_screen()->id ) {
			case 'shop-einstellungen_page_shop-einstellungen-presentation' :
				$title .= __( 'Darstellung', 'mp' );
				break;

			case 'shop-einstellungen_page_shop-einstellungen-notifications' :
				$title .= __( 'Benachrichtigung', 'mp' );
				break;

			case 'shop-einstellungen_page_shop-einstellungen-shipping' :
				$title .= __( 'Versand', 'mp' );
				break;

			case 'shop-einstellungen_page_shop-einstellungen-payments' :
				$title .= __( 'Zahlungen', 'mp' );
				break;

			case 'shop-einstellungen_page_shop-einstellungen-shortcodes' :
				$title .= __( 'Shortcodes', 'mp' );
				break;

			case 'shop-einstellungen_page_shop-einstellungen-importers' :
				$title .= __( 'Importers', 'mp' );
				break;

			case 'shop-einstellungen_page_shop-einstellungen-exporters' :
				$title .= __( 'Exporters', 'mp' );
				break;

			case 'shop-einstellungen_page_shop-einstellungen-productattributes' :
				$title = ( mp_get_get_value( 'action' ) == 'mp_add_product_attribute' ) ? __( 'Produkteigenschaft hinzufügen', 'mp' ) : sprintf( __( 'Bearbeite Produkteigenschaft %s', 'mp' ), '<a class="add-new-h2" href="' . admin_url( 'admin.php?page=shop-einstellungen-productattributes&amp;action=mp_add_product_attribute' ) . '">' . __( 'Produkteigenschaft hinzufügen', 'mp' ) . '</a>' );
				break;

			case 'shop-einstellungen_page_shop-einstellungen-capabilities' :
				$title .= __( 'Benutzerrechte', 'mp' );
				break;

			case 'shop-einstellungen_page_shop-einstellungen-import' :
	            $title .= __( 'Import/Export', 'mp' );
	            break;

			case 'shop-einstellungen_page_store-setup-wizard' :
				$title = __( 'Schnelleinrichtung', 'mp' );
				break;

			default :
				$title .= __( 'Einstellungen', 'mp' );
				break;
		}
		?>
		<div class="wrap mp-wrap">
			<div class="icon32"><img src="<?php echo mp_plugin_url( 'ui/images/settings.png' ); ?>" /></div>
			<h2 class="mp-settings-title"><?php echo $title; ?></h2>
			<div class="clear"></div>
			<?php if ( $message_key = mp_get_get_value( 'mp_message' ) ) : ?>
				<div class="updated"><p><?php echo $this->get_message_by_key( $message_key ); ?></p></div>
			<?php endif;
			?> 	
			<div class="mp-settings">
				<form id="mp-main-form" method="post" action="<?php echo add_query_arg( array() ); ?>">
					<?php
					/**
					 * Render PSOURCE Metabox settings
					 *
					 * @since 3.0
					 */
					do_action( 'psource_metabox/render_settings_metaboxes' );

					/**
					 * Render settings
					 *
					 * @since 3.0
					 */
					do_action( 'mp_render_settings/' . mp_get_current_screen()->id );
					?>
				</form>
			</div>
		</div>
		<?php
	}
    
    /**
	 * Resets the store pages ids in wp cache
	 *
	 * @since 3.2.5
	 * @access public
	 */
    public function reset_store_pages_cache( $metabox ){

		if( ! $metabox instanceof PSOURCE_Metabox ){
			return;
		}

		$store_pages_ids = array();

		foreach ( $metabox->fields as $field ) {
			$field_key 			= $field->get_post_key( $field->args['name'] );
			$value    			= $field->get_post_value( $field_key );
			$store_pages_ids[] 	= $value;
		}

		wp_cache_set( 'store_pages_ids', $store_pages_ids, 'marketpress' );

	}

}

MP_Shop_Einstellungen_Admin::get_instance();
