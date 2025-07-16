

<?php

class MP_Store_Settings_Admin {
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
			self::$_instance = new MP_Store_Settings_Admin();
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
	mp_include_dir( mp_plugin_dir( 'includes/admin/store-settings/' ) );

	   // jQuery UI für Produktattribute-Seite sicherstellen
	   add_action('admin_enqueue_scripts', function($hook) {
		   $screen = function_exists('get_current_screen') ? get_current_screen() : null;
		   if ($screen && strpos($screen->id, 'productattributes') !== false) {
			   // uniqueId ist Teil von jQuery UI Core ab 1.12, aber in WP oft einzeln
			   wp_enqueue_script('jquery-ui-core');
			   wp_enqueue_script('jquery-ui-widget');
			   wp_enqueue_script('jquery-ui-mouse');
			   wp_enqueue_script('jquery-ui-sortable');
			   // uniqueId explizit, falls als eigenes Script vorhanden
			   if (wp_script_is('jquery-ui-unique-id', 'registered')) {
				   wp_enqueue_script('jquery-ui-unique-id');
			   }
		   }
	   });

	// Handle redirects early to avoid header issues
	add_action( 'admin_init', array( &$this, 'handle_redirects' ) );
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
			   add_action( 'wpmudev_metabox/before_save_fields/mp-store-settings-product-attributes-add', array( 'MP_Product_Attributes_Admin', 'save_product_attribute' ) );

			   // Keine dynamische Registrierung von display_settings_form für Produktattribute!

			   if ( mp_get_get_value( 'action' ) == 'mp_edit_product_attribute' ) {
					   add_filter( 'wpmudev_field/before_get_value', array( 'MP_Product_Attributes_Admin', 'get_product_attribute_value' ), 10, 4 );
			   }
	   } else {
	   // Dynamisch für alle relevanten Screens die Action registrieren
	   add_action( 'current_screen', function() {
			   $screen = get_current_screen();
			   // NICHT für Addon-Detailseite und NICHT für Produktattribute!
		  if (
			  $screen
			  && strpos( $screen->id, 'store-settings' ) !== false
			  && (
				  $screen->id !== 'store-settings_page_store-settings-addons'
				  || ( $screen->id === 'store-settings_page_store-settings-addons' && empty($_GET['addon']) )
			  )
			  && $screen->id !== 'store-settings_page_store-settings-productattributes'
			  && $screen->id !== 'marketpress_page_store-settings-productattributes'
		  ) {
			  add_action( $screen->id, array( &$this, 'display_settings_form' ) );
		  }
		  if ( $screen && strpos( $screen->id, 'productattributes' ) !== false ) {
			  add_action( $screen->id, array( 'MP_Product_Attributes_Admin', 'display_product_attributes' ) );
		  }
	   });
}

	add_action( 'wpmudev_metabox/after_settings_metabox_saved/mp-settings-presentation-pages-slugs', array( $this, 'reset_store_pages_cache' ) );
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
				$parent_file = 'store-settings';
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
	
		$cap = apply_filters( 'mp_store_settings_cap', 'manage_store_settings' );
	
		add_menu_page( __( 'Shop Settings', 'mp' ), __( 'Shop Settings', 'mp' ), $cap, 'store-settings', null, 'dashicons-store', 99.33 );
		add_submenu_page( 'store-settings', __( 'Store Settings: General', 'mp' ), __( 'General', 'mp' ), $cap, 'store-settings', array( &$this, 'display_settings_form' ) );
		add_submenu_page( 'store-settings', __( 'Store Settings: Presentation', 'mp' ), __( 'Presentation', 'mp' ), $cap, 'store-settings-presentation', array( &$this, 'display_settings_form' ) );
		add_submenu_page( 'store-settings', __( 'Store Settings: Notifications', 'mp' ), __( 'Notifications', 'mp' ), $cap, 'store-settings-notifications', array( &$this, 'display_settings_form' ) );
		add_submenu_page( 'store-settings', __( 'Store Settings: Shipping', 'mp' ), __( 'Shipping', 'mp' ), $cap, 'store-settings-shipping', array( &$this, 'display_settings_form' ) );
		add_submenu_page( 'store-settings', __( 'Store Settings: Payments', 'mp' ), __( 'Payments', 'mp' ), $cap, 'store-settings-payments', array( &$this, 'display_settings_form' ) );
		add_submenu_page( 'store-settings', __( 'Store Settings: Product Attributes', 'mp' ), __( 'Product Attributes', 'mp' ), $cap, 'store-settings-productattributes', array( 'MP_Product_Attributes_Admin', 'display_product_attributes' ) );
	
	add_submenu_page( 'store-settings', __( 'Store Settings: Product Categories', 'mp' ), __( 'Product Categories', 'mp' ),
		apply_filters( 'mp_manage_product_categories_cap', 'manage_product_categories' ),
		'store-settings-productcategories', '__return_empty_string'
	);

	add_submenu_page( 'store-settings', __( 'Store Settings: Product Tags', 'mp' ), __( 'Product Tags', 'mp' ),
		apply_filters( 'mp_manage_product_tags_cap', 'manage_product_tags' ),
		'store-settings-producttags', '__return_empty_string'
	);
	
		add_submenu_page( 'store-settings', __( 'Store Settings: Capabilities', 'mp' ), __( 'User Capabilities', 'mp' ), $cap, 'store-settings-capabilities', array( &$this, 'display_settings_form' ) );
	
		// Importers (Redirect)
		//add_submenu_page( 'store-settings', __( 'Store Settings: Importers', 'mp' ), __( 'Importers', 'mp' ), $cap, 'store-settings-importers', array( $this, 'redirect_to_importers' ) );
	
		// Exporters (Redirect)
		//add_submenu_page( 'store-settings', __( 'Store Settings: Exporters', 'mp' ), __( 'Exporters', 'mp' ), $cap, 'store-settings-exporters', array( $this, 'redirect_to_exporters' ) );
	
		add_submenu_page( 'store-settings', __( 'Store Settings: Add Ons', 'mp' ), __( 'Add Ons', 'mp' ), $cap, 'store-settings-addons', array( MP_Store_Settings_Addons::get_instance(), 'display_settings' ) );
	
		$mp_needs_quick_setup = get_option( 'mp_needs_quick_setup', 1 );
	
		if ( $mp_needs_quick_setup == 'skip' || $mp_needs_quick_setup == 1 && current_user_can( 'manage_options' ) || ( isset( $_GET['quick_setup_step'] ) ) ) {
			add_submenu_page( 'store-settings', __( 'Quick Setup', 'mp' ), __( 'Quick Setup', 'mp' ), $cap, 'store-setup-wizard', array( &$this, 'display_settings_form' ) );
		}
	
		if ( !WPMUDEV_REMOVE_BRANDING ) {
			add_action( 'load-toplevel_page_store-settings', array( &$this, 'add_help_tab' ) );
			add_action( 'store-settings_page_store-settings-presentation', array( &$this, 'add_help_tab' ) );
			add_action( 'store-settings_page_store-settings-notifications', array( &$this, 'add_help_tab' ) );
			add_action( 'store-settings_page_store-settings-shipping', array( &$this, 'add_help_tab' ) );
			add_action( 'store-settings_page_store-settings-payments', array( &$this, 'add_help_tab' ) );
			add_action( 'store-settings_page_store-settings-product-attributes', array( &$this, 'add_help_tab' ) );
			add_action( 'store-settings_page_store-settings-capabilities', array( &$this, 'add_help_tab' ) );
			add_action( 'store-settings_page_store-settings-importers', array( &$this, 'add_help_tab' ) );
			add_action( 'store-settings_page_store-settings-exporters', array( &$this, 'add_help_tab' ) );
			add_action( 'store-settings_page_store-setup-wizard', array( &$this, 'add_help_tab' ) );
			add_action( 'store-settings_page_store-settings-addons', array( &$this, 'add_help_tab' ) );
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
			'mp_product_attribute_added'	 => __( 'Product attribute added successfully.', 'mp' ),
			'mp_product_attribute_updated'	 => __( 'Product attribute updated successfully.', 'mp' ),
		);

		return ( isset( $messages[ $key ] ) ) ? $messages[ $key ] : sprintf( __( 'An appropriate message for key "%s" could not be found.', 'mp' ), $key );
	}

	/**
	 * Displays the settings form/metaboxes
	 *
	 * @since 3.0
	 * @access public
	 */
	public function display_settings_form() {
	   // NICHTS rendern, wenn wir auf einer Addon-Detailseite sind!
	   if (
			   isset($_GET['page']) && $_GET['page'] === 'store-settings-addons'
			   && !empty($_GET['addon'])
	   ) {
			   return;
	   }
	   // GAR NICHTS rendern, wenn wir auf der Produktattribute-Seite sind!
	   $screen_id = mp_get_current_screen()->id;
	   if (
			   $screen_id === 'store-settings_page_store-settings-productattributes'
			   || $screen_id === 'marketpress_page_store-settings-productattributes'
	   ) {
			   return;
	   }
		$updated = false;
		$title   = __( 'Store Settings', 'mp' ) . ': ';

		switch ( mp_get_current_screen()->id ) {
			// Präsentation
			case 'store-settings_page_store-settings-presentation':
			case 'marketpress_page_store-settings-presentation':
				$title .= __( 'Presentation', 'mp' );
				break;

			// Benachrichtigungen
			case 'store-settings_page_store-settings-notifications':
			case 'marketpress_page_store-settings-notifications':
				$title .= __( 'Notifications', 'mp' );
				break;

			// Versand
			case 'store-settings_page_store-settings-shipping':
			case 'marketpress_page_store-settings-shipping':
				$title .= __( 'Shipping', 'mp' );
				break;

			// Gateways
			case 'store-settings_page_store-settings-payments':
			case 'marketpress_page_store-settings-payments':
				$title .= __( 'Gateways', 'mp' );
				break;

			// Shortcodes
			case 'store-settings_page_store-settings-shortcodes':
			case 'marketpress_page_store-settings-shortcodes':
				$title .= __( 'Short Codes', 'mp' );
				break;

			// Importer
			case 'store-settings_page_store-settings-importers':
			case 'marketpress_page_store-settings-importers':
				$title .= __( 'Importers', 'mp' );
				break;

			// Exporter
			case 'store-settings_page_store-settings-exporters':
			case 'marketpress_page_store-settings-exporters':
				$title .= __( 'Exporters', 'mp' );
				break;

			// Produktattribute
			case 'store-settings_page_store-settings-productattributes':
			case 'marketpress_page_store-settings-productattributes':
				$title = ( mp_get_get_value( 'action' ) == 'mp_add_product_attribute' ) ? __( 'Add Product Attribute', 'mp' ) : sprintf( __( 'Edit Product Attribute %s', 'mp' ), '<a class="add-new-h2" href="' . admin_url( 'admin.php?page=store-settings-productattributes&amp;action=mp_add_product_attribute' ) . '">' . __( 'Add Attribute', 'mp' ) . '</a>' );
				break;

			// Fähigkeiten
			case 'store-settings_page_store-settings-capabilities':
			case 'marketpress_page_store-settings-capabilities':
				$title .= __( 'Capabilities', 'mp' );
				break;

			// Import/Export
			case 'store-settings_page_store-settings-import':
			case 'marketpress_page_store-settings-import':
				$title .= __( 'Import/Export', 'mp' );
				break;

			// Quick Setup
			case 'store-settings_page_store-setup-wizard':
			case 'marketpress_page_store-setup-wizard':
				$title = __( 'Quick Setup', 'mp' );
				break;

			default:
				// Versuche, den aktuellen Submenu-Titel zu holen
				global $submenu, $plugin_page, $pagenow;
				$page_slug = isset($_GET['page']) ? $_GET['page'] : '';
				$menu_title = '';
				if ( isset( $submenu['store-settings'] ) && $page_slug ) {
					foreach ( $submenu['store-settings'] as $submenu_item ) {
						if ( isset($submenu_item[2]) && $submenu_item[2] === $page_slug ) {
							$menu_title = $submenu_item[0];
							break;
						}
					}
				}
				if ( $menu_title ) {
					$title .= wp_strip_all_tags( $menu_title );
				} else {
					$title .= __( 'General', 'mp' );
				}
				break;
		}
		?>
		<div class="wrap mp-wrap">
			<div class="icon32"><img src="<?php echo mp_plugin_url( 'ui/images/settings.png' ); ?>" /></div>
			   <?php // Kein Titel auf der Produktattribute-Seite, nur in display_product_attributes() ?>
			<div class="clear"></div>
			<?php if ( $message_key = mp_get_get_value( 'mp_message' ) ) : ?>
				<div class="updated"><p><?php echo $this->get_message_by_key( $message_key ); ?></p></div>
			<?php endif;
			?> 	
			<div class="mp-settings">
				<form id="mp-main-form" method="post" action="<?php echo add_query_arg( array() ); ?>">
					<?php
					/**
					 * Render WPMUDEV Metabox settings
					 *
					 * @since 3.0
					 */
					do_action( 'wpmudev_metabox/render_settings_metaboxes' );

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

		if( ! $metabox instanceof WPMUDEV_Metabox ){
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

	/**
	 * Handle redirects early to avoid header issues
	 *
	 * @since 3.0
	 * @access public
	 */
	public function handle_redirects() {
		// Only handle redirects in admin area
		if ( ! is_admin() ) {
			return;
		}

		// Check if we're on one of the redirect pages
		$page = mp_get_get_value( 'page' );
		
		switch ( $page ) {
			case 'store-settings-productcategories':
				wp_redirect( admin_url( 'edit-tags.php?taxonomy=product_category&post_type=' . MP_Product::get_post_type() ) );
				exit;
				break;
			case 'store-settings-producttags':
				wp_redirect( admin_url( 'edit-tags.php?taxonomy=product_tag&post_type=' . MP_Product::get_post_type() ) );
				exit;
				break;
			case 'store-settings-importers':
				wp_redirect( admin_url( 'tools.php?page=store-settings-import' ) );
				exit;
				break;
			case 'store-settings-exporters':
				wp_redirect( admin_url( 'tools.php?page=store-settings-export' ) );
				exit;
				break;
		}
	}

}

MP_Store_Settings_Admin::get_instance();
