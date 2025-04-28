<?php
/**
 * Plugin Name: MarketPress
 * Plugin URI:  https://cp-psource.github.io/marketpress/
 * Description: Das einfachste und dennoch mächtigste ClassicPress-E-Commerce-Plugin
 * Version:     3.5.8
 * Requires at least: 4.9
 * Author:      PSOURCE
 * Author URI:  https://github.com/cp-psource
 * Text Domain: mp
 * Domain Path: /languages
 */

/*
Copyright 20019-2024 PSOURCE (https://github.com/cp-psource)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License (Version 2 - GPLv2) as
published by the Free Software Foundation.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.	See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA	02111-1307	USA

Plugin Authors: DerN3rd (PSOURCE)
*/

require 'psource/psource-plugin-update/plugin-update-checker.php';
use YahnisElsts\PluginUpdateChecker\v5\PucFactory;
 
$myUpdateChecker = PucFactory::buildUpdateChecker(
	'https://github.com/cp-psource/marketpress',
	__FILE__,
	'marketpress'
);
 
//Set the branch that contains the stable release.
$myUpdateChecker->setBranch('master');


define( 'MP_VERSION', '3.5.8' );


/**
 * Main class MarketPress.
 */
class MarketPress {

	public $currencies;
	public $eu_countries;
	public $provinces;
	public $countries_no_postcode;
	public $countries;
	public $popular_countries;
	public $default_settings;
	public $defaults;

	/**
	 * Refers to the post types that MarketPress uses
	 *
	 * @since 3.0
	 * @access public
	 * @var array
	 */

	var $post_types = array( 'mp_product', 'product', 'mp_product_variation' );

	/**
	 * Refers to the single instance of the class
	 *
	 * @since 3.0
	 * @access private
	 * @var object
	 */
	private static $_instance = null;

	/**
	 * Refers to the absolute path to the plugin's main file
	 *
	 * @since 3.0
	 * @access private
	 * @var string
	 */
	private $_plugin_file = null;

	/**
	 * Refers to the absolute url to the plugin's directory
	 *
	 * @since 3.0
	 * @access private
	 * @var string
	 */
	private $_plugin_url = null;

	/**
	 * Refers to the absolute path to the plugin's directory
	 *
	 * @since 3.0
	 * @access private
	 * @var string
	 */
	private $_plugin_dir = null;

	/**
	 * Refers to the plugin title.
	 *
	 * @var string
	 */
	public $plugin_title = null;

	/**
	 * Gets the single instance of the class
	 *
	 * @since 3.0
	 * @access public
	 * @return object
	 */
	public static function get_instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new MarketPress();
		}

		return self::$_instance;
	}

	/**
	 * Gets an absolute path to the plugin's base directory
	 *
	 * @since 3.0
	 * @access private
	 *
	 * @param string $path (optional) Will be appended onto the base directory.
	 *
	 * @return string
	 */
	public function plugin_dir( $path = '' ) {
		return $this->_plugin_dir . ltrim( $path, '/' );
	}

	/**
	 * Gets an absolute url to the plugin's base directory
	 *
	 * @since 3.0
	 * @access private
	 *
	 * @param string $path (optional) Will be appended onto the base directory.
	 *
	 * @return string
	 */
	public function plugin_url( $path = '' ) {
		return $this->_plugin_url . ltrim( $path, '/' );
	}


	/**
	 * Register custom post types, taxonomies and stati
	 *
	 * @since 3.0
	 * @access public
	 * @uses $wp_version
	 */
	public function register_custom_types() {
		global $wp_version;

		// Register product_category taxonomy.
		register_taxonomy( 'product_category', MP_Product::get_post_type(), apply_filters( 'mp_register_product_category', array(
			'hierarchical'      => true,
			'labels'            => array(
				'name'                       => _x( 'Shopkategorien', 'product_category', 'mp' ),
				'singular_name'              => _x( 'Shopkategorie', 'product_category', 'mp' ),
				'all_items'                  => __( 'Alle Shopkategorien', 'mp' ),
				'edit_item'                  => __( 'Shopkategorie bearbeiten', 'mp' ),
				'view_item'                  => __( 'Shopkategorie anzeigen', 'mp' ),
				'update_item'                => __( 'Shopkategorie aktualisieren', 'mp' ),
				'add_new_item'               => __( 'Neue Shopkategorie hinzufügen', 'mp' ),
				'new_item_name'              => __( 'Name der neuen Shopkategorie', 'mp' ),
				'parent_item'                => __( 'Übergeordnete Shopkategorie', 'mp' ),
				'parent_item_colon'          => __( 'Übergeordnete Shopkategorie:', 'mp' ),
				'search_items'               => __( 'Shopkategorien suchen', 'mp' ),
				'separate_items_with_commas' => __( 'Shopkategorien durch Kommas trennen', 'mp' ),
				'add_or_remove_items'        => __( 'Shopkategorien hinzufügen oder entfernen', 'mp' ),
				'choose_from_most_used'      => __( 'Auswahl häufig verwendeter Shopkategorien', 'mp' ),
				'not_found'                  => __( 'Keine Shopkategorien gefunden :(', 'mp' ),
			),
			'capabilities'      => array(
				'manage_terms' => 'manage_product_categories',
				'edit_terms'   => 'manage_product_categories',
				'delete_terms' => 'manage_product_categories',
				'assign_terms' => 'edit_products',
			),
			'show_ui'           => true,
			'show_admin_column' => true,
			'rewrite'           => array(
				'with_front' => false,
				'slug'       => mp_store_page_uri( 'products', false ) . 'category',
			),
		) ) );

		// Register product_tag taxonomy.
		register_taxonomy( 'product_tag', MP_Product::get_post_type(), apply_filters( 'mp_register_product_tag', array(
			'hierarchical'      => false,
			'labels'            => array(
				'name'                       => _x( 'Produkt-Tags', 'product_tag', 'mp' ),
				'singular_name'              => _x( 'Produkt-Tag', 'product_tag', 'mp' ),
				'all_items'                  => __( 'Alle Produkt-Tags', 'mp' ),
				'edit_item'                  => __( 'Produkt-Tag bearbeiten', 'mp' ),
				'view_item'                  => __( 'Produkt-Tag anzeigen', 'mp' ),
				'update_item'                => __( 'Produkt-Tag aktualisieren', 'mp' ),
				'add_new_item'               => __( 'Neuen Produkt-Tag hinzufügen', 'mp' ),
				'new_item_name'              => __( 'Neuer Produkt-Tag-Name', 'mp' ),
				'parent_item'                => __( 'Übergeordnetes Produkt-Tag', 'mp' ),
				'parent_item_colon'          => __( 'Übergeordnetes Produkt-Tag:', 'mp' ),
				'search_items'               => __( 'Produkt-Tags suchen', 'mp' ),
				'separate_items_with_commas' => __( 'Produktetiketten durch Kommas trennen', 'mp' ),
				'add_or_remove_items'        => __( 'Produkt-Tags hinzufügen oder entfernen', 'mp' ),
				'choose_from_most_used'      => __( 'Auswahl häufig verwendeter Produkt-Tags', 'mp' ),
				'not_found'                  => __( 'Keine Produkt-Tags gefunden', 'mp' ),
			),
			'capabilities'      => array(
				'manage_terms' => 'manage_product_tags',
				'edit_terms'   => 'manage_product_tags',
				'delete_terms' => 'manage_product_tags',
				'assign_terms' => 'edit_products',
			),
			'show_admin_column' => true,
			'show_ui'           => true,
			'rewrite'           => array(
				'with_front' => false,
				'slug'       => mp_store_page_uri( 'products', false ) . 'tag',
			),
		) ) );

		// Register product post type.
		register_post_type( MP_Product::get_post_type(), apply_filters( 'mp_register_post_type', array(
			'labels'             => array(
				'name'               => __( 'Produkte', 'mp' ),
				'singular_name'      => __( 'Produkt', 'mp' ),
				'menu_name'          => __( 'Shop', 'mp' ),
				'all_items'          => __( 'Produkte', 'mp' ),
				'add_new'            => __( 'Neues Produkt hinzufügen', 'mp' ),
				'add_new_item'       => __( 'Neues Produkt hinzufügen', 'mp' ),
				'edit_item'          => __( 'Produkt bearbeiten', 'mp' ),
				'edit'               => __( 'Bearbeiten', 'mp' ),
				'new_item'           => __( 'Neues Produkt', 'mp' ),
				'view_item'          => __( 'Produkt ansehen', 'mp' ),
				'search_items'       => __( 'Produkt suchen', 'mp' ),
				'not_found'          => __( 'Kein Produkt gefunden', 'mp' ),
				'not_found_in_trash' => __( 'Keine Produkte im Papierkorb gefunden', 'mp' ),
				'view'               => __( 'Produkt betrachten', 'mp' ),
			),
			'description'        => __( 'Produkte für Deinen MarketPress-Shop.', 'mp' ),
			'public'             => true,
			'show_ui'            => true,
			'publicly_queryable' => true,
			'capability_type'    => array( 'product', 'products' ),
			'menu_icon'          => 'dashicons-cart',
			'hierarchical'       => true,
			'map_meta_cap'       => true,
			'rewrite'            => array(
				'slug'       => rtrim( mp_store_page_uri( 'products', false ), '/' ),
				'with_front' => false,
			),
			'query_var'          => true,
			'supports'           => array(
				'title',
				'editor',
				'author',
				'excerpt',
				'revisions',
				'thumbnail',
			),
			'taxonomies'         => array(
				'product_category',
				'product_tag',
			),
		) ) );

		// Register mp_order post type.
		register_post_type( 'mp_order', apply_filters( 'mp_register_post_type_mp_order', array(
			'labels'          => array(
				'name'               => __( 'Bestellungen', 'mp' ),
				'singular_name'      => __( 'Bestellung', 'mp' ),
				'add_new'            => _x( 'Neue Bestellung hinzufügen', 'mp_order', 'mp' ),
				'add_new_item'       => __( 'Neue Bestellung hinzufügen', 'mp' ),
				'edit_item'          => __( 'Bestellung bearbeiten', 'mp' ),
				'new_item'           => __( 'Neue Bestellung', 'mp' ),
				'view_item'          => __( 'Bestellung ansehen', 'mp' ),
				'search_items'       => __( 'Bestellungen durchsuchen', 'mp' ),
				'not_found'          => __( 'Keine Bestellung(en) gefunden', 'mp' ),
				'not_found_in_trash' => __( 'Keine Bestellung(en) im Papierkorb gefunden', 'mp' ),
				'parent_item_colon'  => __( 'Übergeordnete Bestellung', 'mp' ),
			),
			'description'     => __( 'Bestellungen aus Deinem E-Commerce-Shop.', 'mp' ),
			'public'          => false,
			'show_ui'         => true,
			'show_in_menu'    => false,
			'capability_type' => array( 'store_order', 'store_orders' ),
			'capabilities'    => array(
				'create_posts' => 'do_not_allow',
			), // Temporarily disable creating order from admin.
			'map_meta_cap'    => true,
			'hierarchical'    => false,
			'rewrite'         => false,
			'query_var'       => false,
			'supports'        => array( '' ),
		) ) );

		// Register product_variation post type.
		register_post_type( MP_Product::get_variations_post_type(), array(
			'public'             => false,
			'show_ui'            => true,
			'show_in_nav_menus'  => false,
			'show_in_menu'       => false,
			'show_in_admin_bar'  => false,
			'publicly_queryable' => true,
			'hierarchical'       => true,
			'rewrite'            => false,
			'query_var'          => false,
			'supports'           => array(),
		) );

		// Register custom post statuses for our orders.
		register_post_status( 'order_received', array(
			'label'       => __( 'Bestellung eingegangen', 'mp' ),
			/* translators: %s: orders received */
			'label_count' => _n_noop( 'Eingegangen <span class="count">(%s)</span>', 'Eingegangen <span class="count">(%s)</span>', 'mp' ),
			'post_type'   => 'mp_order',
			'public'      => false,
		) );
		register_post_status( 'order_paid', array(
			'label'       => __( 'Bezahlt', 'mp' ),
			/* translators: %s: paid orders */
			'label_count' => _n_noop( 'Bezahlt <span class="count">(%s)</span>', 'Bezahlt <span class="count">(%s)</span>', 'mp' ),
			'post_type'   => 'mp_order',
			'public'      => false,
		) );
		register_post_status( 'order_shipped', array(
			'label'       => __( 'Versand', 'mp' ),
			/* translators: %s: shipped orders */
			'label_count' => _n_noop( 'Versendet <span class="count">(%s)</span>', 'Versendet <span class="count">(%s)</span>', 'mp' ),
			'post_type'   => 'mp_order',
			'public'      => false,
		) );
		register_post_status( 'order_closed', array(
			'label'       => __( 'Bestellung abgeschlossen', 'mp' ),
			/* translators: %s: orders closed */
			'label_count' => _n_noop( 'Abgeschlossen <span class="count">(%s)</span>', 'Geschlossen <span class="count">(%s)</span>', 'mp' ),
			'post_type'   => 'mp_order',
			'public'      => false,
		) );

		// Register product attributes.
		MP_Product_Attributes::get_instance()->register();
	}

	/**
	 * Load payment and shipping gateways
	 *
	 * @since 3.0
	 * @access public
	 */
	public function load_plugins() {
		/* @noinspection PhpIncludeInspection */
		require_once $this->plugin_dir( 'includes/common/shipping-modules/class-mp-shipping-api.php' );
		/* @noinspection PhpIncludeInspection */
		require_once $this->plugin_dir( 'includes/common/shipping-modules/class-mp-shipping-api-calculated.php' );
		mp_include_dir( $this->plugin_dir( 'includes/common/shipping-modules' ) );
		MP_Shipping_API::load_active_plugins();

		/* @noinspection PhpIncludeInspection */
		require_once $this->plugin_dir( 'includes/common/payment-gateways/class-mp-gateway-api.php' );
		mp_include_dir( $this->plugin_dir( 'includes/common/payment-gateways' ) );

        do_action( 'marketpress/load_plugins/mp_include' );

		MP_Gateway_API::load_active_gateways();
	}



	/**
	 * Constructor function
	 *
	 * @since 3.0
	 * @access private
	 */
	private function __construct() {
		// Init variables.
		$this->_init_vars();

		// Include constants.
		/* @noinspection PhpIncludeInspection */
		require_once $this->plugin_dir( 'includes/common/constants.php' );

		// Add GDPR compliance.
		add_action( 'admin_init', array( $this, 'add_gdpr_compliance' ), 0 );

		// Includes.
		add_action( 'init', array( &$this, 'includes' ), 0 );

		// Load gateway/shipping plugins.
		add_action( 'init', array( &$this, 'load_plugins' ), 2 );

		// Register system addons.
		add_action( 'init', array( &$this, 'register_addons' ), 2 );

		// Setup custom types.
		add_action( 'init', array( &$this, 'register_custom_types' ), 1 );

		// Maybe flush rewrites.
		add_action( 'admin_init', array( &$this, 'maybe_flush_rewrites' ), 99 );

		// Fix insecure images.
		add_filter( 'wp_get_attachment_url', array( &$this, 'fix_insecure_images' ), 10, 2 );

		// Setup rewrite rules.
		add_filter( 'rewrite_rules_array', array( &$this, 'add_rewrite_rules' ) );

		// Add custom query vars.
		add_filter( 'query_vars', array( &$this, 'add_query_vars' ) );

		// Filter billing info user meta.
		add_filter( 'get_user_metadata', array( &$this, 'get_user_billing_info' ), 10, 4 );

		add_action( 'admin_print_styles', array( &$this, 'add_notices' ) );

		add_action( 'admin_init', array( &$this, 'install_actions' ) );

		add_action( 'admin_init', array( &$this, 'variations_admin' ) );

		add_action( 'admin_init', array( &$this, 'mp_admin_init' ) );

		add_action( 'template_redirect', array( &$this, 'redirect_variation_singles_to_products' ) );

		add_filter( 'post_thumbnail_html', array( &$this, 'post_thumbnail_html5' ), 10, 5 );

		add_action( 'admin_menu', array( &$this, 'add_menu_items' ), 9 );

		$this->load_widgets();

		$this->localization();
	}

	/**
	 * Add GDPR compliance.
	 */
	public function add_gdpr_compliance() {
		/* @noinspection PhpIncludeInspection */
		require_once $this->plugin_dir( 'includes/common/class-mp-gdpr.php' );

		MP_GDPR::get_instance();
	}

	/**
	 * Add menus.
	 */
	function add_menu_items() {
		add_submenu_page( 'edit.php?post_type=' . MP_Product::get_post_type(), __( 'Produkt erstellen', 'mp' ), __( 'Produkt erstellen', 'mp' ), apply_filters( 'mp_add_new_product_capability', 'manage_options' ), 'post-new.php?post_type=' . MP_Product::get_post_type() );
	}

	/**
	 * Localization.
	 */
	function localization() {
		// Load up the localization file if we're using ClassicPress in a different language.
		// Place it in this plugin's "languages" folder and name it "mp-[value in wp-config].mo".
		$mu_plugins = wp_get_mu_plugins();
		$lang_dir   = dirname( plugin_basename( $this->_plugin_file ) ) . '/languages/';

		if ( in_array( $this->_plugin_file, $mu_plugins ) ) {
			load_muplugin_textdomain( 'mp', $lang_dir );
		} else {
			load_plugin_textdomain( 'mp', false, $lang_dir );
		}
	}

	/**
	 * Load widgets.
	 */
	function load_widgets() {
		if ( ! function_exists( 'is_plugin_active_for_network' ) ) {
			require_once( ABSPATH . '/wp-admin/includes/plugin.php' );
		}

		if ( ! function_exists( 'mp_get_plugin_slug' ) ) {
			/**
			 * Get plugin slug.
			 *
			 * @return string
			 */
			function mp_get_plugin_slug() {
				if ( file_exists( dirname( __FILE__ ) . '/psource/psource-plugin-update/plugin-update-checker.php' ) ) {
					return 'marketpress/marketpress.php';
				} else {
					return 'classicpress-ecommerce/marketpress.php';
				}
			}
		}

		if ( ! function_exists( 'mp_is_main_site' ) ) {
			/**
			 * Check if mp is main site.
			 *
			 * @return bool
			 */
			function mp_is_main_site() {
				global $wpdb;

				if ( MP_ROOT_BLOG !== false ) {
					return MP_ROOT_BLOG === $wpdb->blogid;
				} else {
					return is_main_site();
				}
			}
		}
		require_once( $this->plugin_dir( 'includes/admin/widgets/cart.php' ) );
		require_once( $this->plugin_dir( 'includes/admin/widgets/categories.php' ) );
		require_once( $this->plugin_dir( 'includes/admin/widgets/product-list.php' ) );
		require_once( $this->plugin_dir( 'includes/admin/widgets/product-tag-cloud.php' ) );

		// Multisite Widgets.
		if ( is_multisite() && is_plugin_active_for_network( mp_get_plugin_slug() ) ) {
			$settings = get_site_option( 'mp_network_settings', array() );
			if ( ( isset( $settings['main_blog'] ) && mp_is_main_site() ) || isset( $settings['main_blog'] ) && ! $settings['main_blog'] ) {
				require_once( $this->plugin_dir( 'includes/admin/widgets/ms-global-product-list.php' ) );
				require_once( $this->plugin_dir( 'includes/admin/widgets/ms-global-tag-cloud.php' ) );
				require_once( $this->plugin_dir( 'includes/admin/widgets/ms-global-categories.php' ) );
			}
		}
	}

	/**
	 * Post thumbnail for HTML5.
	 *
	 * @param string $html               HTML code.
	 * @param int    $post_id            Post ID.
	 * @param int    $post_thumbnail_id  Post thumbnail ID.
	 * @param int    $size               Thumbnail size.
	 * @param mixed  $attr               Attributes.
	 *
	 * @return mixed
	 */
	function post_thumbnail_html5( $html, $post_id, $post_thumbnail_id, $size, $attr ) {
		$post_type = get_post_type( $post_id );
		if ( class_exists( 'MP_Product' ) && ( MP_Product::get_post_type() === $post_type || MP_Product::get_variations_post_type() === $post_type ) ) {
			$html = str_replace( '/>', '>', $html );
		}

		return $html;
	}

	/**
	 * Redirect variation singles to products.
	 */
	function redirect_variation_singles_to_products() {
		global $post;
		if ( ! is_singular( MP_Product::get_variations_post_type() ) ) {
			return;
		} else {
			$product_id = wp_get_post_parent_id( $post->ID );
			$url        = get_permalink( $product_id );
			wp_safe_redirect( $url );
			exit;
		}
	}

	/**
	 * Variations admin.
	 */
	function variations_admin() {
		// Get the Post ID.
		$post_id = isset( $_GET['post'] ) ? (int) $_GET['post'] : ( isset( $_POST['post_ID'] ) ? (int) $_POST['post_ID'] : '' );

		if ( ! isset( $post_id ) ) {
			return;
		}

		if ( get_post_type( $post_id ) === MP_Product::get_variations_post_type() ) {
			remove_post_type_support( MP_Product::get_variations_post_type(), 'title' );
			add_action( 'add_meta_boxes', array( &$this, 'mp_product_variation_metaboxes' ) );
		}
	}

	/**
	 * Product variation metaboxes.
	 *
	 * @return string
	 */
	function mp_product_variation_metaboxes() {
		$post_id = isset( $_GET['post'] ) ? (int) $_GET['post'] : ( isset( $_POST['post_ID'] ) ? (int) $_POST['post_ID'] : '' );
		if ( '' !== $post_id ) {
			ob_start();
			$variation_title = get_post_meta( $post_id, 'name', true );
			?>
			<input type="hidden" name="variation_title" id="variation_title"
					value="<?php echo esc_attr( $variation_title ); ?>"/>
			<?php

			return ob_get_clean();
		}
	}

	/**
	 * Called from ClassicPress when the admin page init process is invoked.
	 *
	 * @since 3.0
	 */
	function mp_admin_init() {
		if ( is_multisite() ) {
			if ( ! is_super_admin() || ! is_network_admin() ) {
				return;
			}
		} elseif ( current_user_can( 'manage_store_settings' ) ) {
			add_filter( 'plugin_action_links_' . basename( dirname( __FILE__ ) ) . '/' . basename( __FILE__ ),
				array( &$this,'mp_plugin_settings_link' )
			);
		}
	}

	/**
	 * Adds a 'settings' link on the plugin links.
	 *
	 * @since 3.0
	 * @param array $links links for this plugin.
	 * @return array $links links including Settings link
	 */
	function mp_plugin_settings_link( $links ) {
		$settings_link = '<a href="' . esc_url( admin_url( 'admin.php?page=shop-einstellungen' ) ) . '">'
			. __( 'Shopeinstellungen', 'mp' ) . '</a>';
		array_unshift( $links, $settings_link );

		return $links;
	}

	/**
	 * Install actions.
	 */
	function install_actions() {
		// Install - Add pages button.
		if ( ! empty( $_GET['install_mp_pages'] ) ) {
			$this->create_pages();

			// We no longer need to install pages.
			update_option( 'mp_needs_pages', 0 );

			// Settings redirect.
			wp_safe_redirect( admin_url( 'admin.php?page=store-setup-wizard&quick_setup_step=2&mp_pages_created' ) );
			exit;
		}
	}

	/**
	 * Create MP pages.
	 */
	function create_pages() {
		$page_store_id = mp_create_store_page( 'store' );
		//mp_create_store_page('network_store_page');
		$page_products_id     = mp_create_store_page( 'products' );
		$page_cart_id         = mp_create_store_page( 'cart' );
		$page_checkout_id     = mp_create_store_page( 'checkout' );
		$page_order_status_id = mp_create_store_page( 'order_status' );

		$settings = get_option( 'mp_settings' );
		mp_push_to_array( $settings, 'pages->store', $page_store_id );
		mp_push_to_array( $settings, 'pages->products', $page_products_id );
		mp_push_to_array( $settings, 'pages->cart', $page_cart_id );
		mp_push_to_array( $settings, 'pages->checkout', $page_checkout_id );
		mp_push_to_array( $settings, 'pages->order_status', $page_order_status_id );

		update_option( 'mp_settings', $settings );

		flush_rewrite_rules();
	}

	/**
	 * Add notices.
	 */
	function add_notices() {
		if ( isset( $_GET['mp_pages_created'] ) ) {
			add_action( 'admin_notices', array( $this, 'pages_created_notice' ) );
		}
	}

	/**
	 * Pages created notice.
	 */
	function pages_created_notice() {
		?>
		<div id="message" class="updated mp-install-notice">
			<p><?php esc_html_e( 'Die benötigten Seiten für Deinen Shop wurden erfolgreich erstellt!', 'mp' ); ?></p>
		</div>
		<?php
	}

	/**
	 * Install notice.
	 */
	function install_notice() {
		// If we have just installed, show a message with the install pages button.
		if ( 1 === get_option( 'mp_needs_pages', 1 ) ) {
			?>
			<div id="message" class="updated mp-install-notice">
				<?php /* translators: %s: plugin title */ ?>
				<p><?php printf( __( '<strong>Einrichtungsassistent: %s</strong> &#8211; installiert vollkommen automatisch die Seiten welche Dein Shop benötigt um zu funktionieren.', 'mp' ), $this->plugin_title ); ?></p>

				<p class="submit"><a
						href="<?php echo esc_url( add_query_arg( 'install_mp_pages', 'true', admin_url( 'admin.php?page=shop-einstellungen-presentation' ) ) ); ?>"
						<?php /* translators: %s: plugin title */ ?>
						class="button-primary"><?php printf( __( 'Installiere %s Seiten', 'mp' ), $this->plugin_title ); ?></a>
				</p>
			</div>
			<?php
		}
	}

	/**
	 * Register add ons
	 *
	 * @since 3.0
	 * @access public
	 */
	public function register_addons() {
		mp_register_addon( array(
			'label'        => __( 'Gutscheine', 'mp' ),
			'desc'         => __( 'Gutscheincodes anbieten und annehmen', 'mp' ),
			'class'        => 'MP_Coupons_Addon',
			'path'         => mp_plugin_dir( 'includes/addons/mp-coupons/class-mp-coupons-addon.php' ),
			'has_settings' => true,
		) );

		if ( class_exists( 'ProSites' ) ) {
			mp_register_addon( array(
				'label' => __( 'PS Bloghosting', 'mp' ),
				'desc'  => __( 'Gewähre Zugriff auf Themen und Gateways, abhängig vom PS Bloghosting-Level des Benutzers', 'mp' ),
				'class' => 'MP_Prosites_Addon',
				'path'  => mp_plugin_dir( 'includes/addons/mp-prosites/class-mp-prosites-addon.php' ),
			) );
			// Auto assign.
			if ( ! function_exists( 'is_plugin_active_for_network' ) ) {
				require_once( ABSPATH . '/wp-admin/includes/plugin.php' );
			}
			$enabled = mp_get_setting( 'addons', array() );
			if ( is_plugin_active_for_network( mp_get_plugin_slug() ) && ! in_array( 'MP_Prosites_Addon', $enabled ) ) {
				$enabled[] = 'MP_Prosites_Addon';
				$enabled   = array_unique( $enabled );
				mp_addons()->enable( $enabled );
			}
		}

		mp_register_addon( array(
			'label'        => __( 'PDF Rechnung/Lieferschein', 'mp' ),
			'desc'         => 'Erstelle Rechnungen/Lieferscheine als PDF für Dich und Deine Kunden',
			'class'        => 'MP_PDF_Invoice_Addon',
			'path'         => mp_plugin_dir( 'includes/addons/mp-pdf-invoice/class-mp-pdf-invoice-addon.php' ),
			'has_settings' => true,
		) );

		// Multi File Addon.
		mp_register_addon( array(
			'label'        => __( 'Mehrfache Downloads', 'mp' ),
			'desc'         => __( 'Aktiviere mehrere Downloads pro Produkt', 'mp' ),
			'class'        => 'MP_Multi_File_Download_Addon',
			'path'         => mp_plugin_dir( 'includes/addons/mp-multi-file-download/class-mp-multi-file-download-addon.php' ),
			'has_settings' => false,
		) );

		// Allow Produkt Comments.
		mp_register_addon( array(
			'label'        => __( 'Erlaube Produktkommentare', 'mp' ),
			'desc'         => __( 'Erlaube das kommentieren von Produkten', 'mp' ),
			'class'        => 'MP_MARKETPRESS_COMMENTS_Addon',
			'path'         => mp_plugin_dir( 'includes/addons/marketpress-comments/class-marketpress-comments.php' ),
			'has_settings' => false,
		) );

		// Frontend Produkteditor
        mp_register_addon( array(
			'label'        => __( 'Shop Statistiken (ALFAVERSION)', 'mp' ),
			'desc'         => __( 'Aktiviere diese Erweiterung um eine Dashboard-Seite mit Shop-Statistiken zu erstellen', 'mp' ),
			'class'        => 'MP_MARKETPRESS_STATS_Addon',
			'path'         => mp_plugin_dir( 'includes/addons/marketpress-statistics/marketpress-stats.php' ),
			'has_settings' => false,
		) );

		/**
		 * Fires after all internal addons have been registered
		 *
		 * @since 3.0
		 * @access public
		 */
		do_action( 'marketpress/register_addons' );
	}

	/**
	 * Add rewrite rules.
	 *
	 * @since  3.0
	 * @access public
	 * @uses   $wp_rewrite
	 * @param  array $rewrite_rules  Rewrite rules.
	 * @filter rewrite_rules_array
	 * @return array
	 */
	public function add_rewrite_rules( $rewrite_rules ) {
		global $wp_rewrite;

		$new_rules = array();

		/*
		  Product categories/tags

		  This is necessary, otherwise product cats and tags will return 404 errors
		  due to how rewrite rules are generated for pages.

		  @see http://classicpress.stackexchange.com/questions/4127/custom-taxonomy-and-pages-rewrite-slug-conflict-gives-404
		 */
		if ( $post_id = mp_get_setting( 'pages->products' ) ) {
			$page_structure = $wp_rewrite->get_page_permastruct();
			$uri            = get_page_uri( $post_id );
			$wp_rewrite->add_rewrite_tag( '%pagename%', "({$uri})", 'pagename=' );
			$page_rewrite_rules = $wp_rewrite->generate_rewrite_rules( $page_structure, EP_PAGES );
			$rewrite_rules      = array_merge( $page_rewrite_rules, $rewrite_rules );
		}

		// Product variations.
		if ( $post_id = mp_get_setting( 'pages->products' ) ) {
			$uri                                                = get_page_uri( $post_id );
			$new_rules[ $uri . '/([^/]+)/variation/([^/]+)/?' ] = 'index.php?' . MP_Product::get_post_type() . '=$matches[1]&post_type=' . MP_Product::get_post_type() . '&name=$matches[1]&mp_variation_id=$matches[2]';
		}

		// Order status.
		if ( $post_id = mp_get_setting( 'pages->order_status' ) ) {
			$uri = get_page_uri( $post_id );
			// $new_rules[ $uri . '/([^/]+)/?' ] = 'index.php?pagename=' . $uri . '&mp_order_id=$matches[1]';
			// This rules match the default page rules, so we have to inject it before the page.
			$rewrite_rules = array_merge( array(
				$uri . '/([^/]+)/?$' => 'index.php?pagename=' . $uri . '&mp_order_id=$matches[1]',
				$uri . '/page/([^/]+)/?' => 'index.php?pagename=' . $uri . '&mp_status_pagenumber=$matches[1]',
				$uri . '/([^/]+)/([^/]+)/?' => 'index.php?pagename=' . $uri . '&mp_order_id=$matches[1]&mp_guest_email=$matches[2]',
			), $rewrite_rules );
		}

		// Order confirmation.
		if ( $post_id = mp_get_setting( 'pages->checkout' ) ) {
			$uri = get_page_uri( $post_id );
			// $new_rules[ $uri . '/confirm/?' ]	 = 'index.php?pagename=' . $uri . '&mp_confirm_order_step=1';
			$rewrite_rules = array_merge( array(
				$uri . '/confirm/?' => 'index.php?pagename=' . $uri . '&mp_confirm_order_step=1',
			), $rewrite_rules );
		}

		return $new_rules + $rewrite_rules;
	}

	/**
	 * Add custom query vars
	 *
	 * @since  3.0
	 * @access public
	 * @param  array $vars  Variables.
	 * @filter query_vars
	 * @return array $vars
	 */
	public function add_query_vars( $vars ) {
		$vars[] = 'mp_variation_id';
		$vars[] = 'mp_order_id';
		$vars[] = 'mp_confirm_order_step';
		$vars[] = 'mp_guest_email';
		$vars[] = 'mp_status_pagenumber';

		return $vars;
	}

	/**
	 * Make sure images use https protocol when displaying content over ssl.
	 *
	 * @since  3.0
	 * @access public
	 * @param  string $url      URL.
	 * @param  int    $post_id  Post Id.
	 * @filter wp_get_attachment_url
	 * @return string
	 */
	public function fix_insecure_images( $url, $post_id ) {
		// Skip file attachments.
		if ( ! wp_attachment_is_image( $post_id ) ) {
			return $url;
		}

		if ( is_ssl() ) {
			$url = str_replace( 'http://', 'https://', $url );
		}

		return $url;
	}

	/**
	 * Maybe flush rewrite rules.
	 *
	 * @since 3.0
	 * @access public
	 * @action init
	 */
	public function maybe_flush_rewrites() {
		$flush_rewrites = get_option( 'mp_flush_rewrites', 1 );

		if ( 1 === $flush_rewrites || '1' === $flush_rewrites ) {
			flush_rewrite_rules();
			update_option( 'mp_flush_rewrites', 0 );
		}
	}

	/**
	 * Get user billing info.
	 *
	 * Before 3.0 only shipping info was captured. This function will return the
	 * shipping info if billing info doesn't exist for the given user.
	 *
	 * @since 3.0
	 * @access public
	 * @param $value
	 * @param int    $user_id      User ID.
	 * @param string $meta_key  Meta key.
	 * @param $single
	 * @filter get_user_metadata
	 * @return array|mixed
	 */
	public function get_user_billing_info( $value, $user_id, $meta_key, $single ) {
		if ( 'mp_billing_info' !== $meta_key ) {
			return $value;
		}

		remove_filter( 'get_user_metadata', array( &$this, 'get_user_billing_info' ) );

		if ( metadata_exists( 'user', $user_id, 'mp_billing_info' ) ) {
			return $value;
		}

		add_filter( 'get_user_metadata', array( &$this, 'get_user_billing_info' ), 10, 4 );

		$meta = get_user_meta( $user_id, 'mp_shipping_info', true );

		/**
		 * There is a small bug in WP core with the get_user_metadata filter that
		 * will raise a PHP notice if an associative array is returned and $single is
		 * set to true. This is because WP core assumes that the returned array will
		 * be numerically indexed.
		 */

		return ( $single && is_array( $meta ) ) ? array( $meta ) : $meta;
	}

	/**
	 * Include necessary files
	 *
	 * @since 3.0
	 * @access public
	 */
	public function includes() {
		/* @noinspection PhpIncludeInspection */
		require_once $this->plugin_dir( 'includes/common/class-mp-gdpr.php' );
		/* @noinspection PhpIncludeInspection */
		require_once $this->plugin_dir( 'includes/psource-metaboxes/psource-metabox.php' );
		/* @noinspection PhpIncludeInspection */
		require_once $this->plugin_dir( 'includes/common/class-mp-mailer.php' );
		/* @noinspection PhpIncludeInspection */
		require_once $this->plugin_dir( 'includes/common/helpers.php' );

		/* @noinspection PhpIncludeInspection */
		require_once $this->plugin_dir( 'includes/common/class-mp-product-attributes.php' );
		/* @noinspection PhpIncludeInspection */
		require_once $this->plugin_dir( 'includes/addons/class-mp-addons.php' );
		/* @noinspection PhpIncludeInspection */
		require_once $this->plugin_dir( 'includes/common/class-mp-order.php' );
		/* @noinspection PhpIncludeInspection */
		require_once $this->plugin_dir( 'includes/common/class-mp-product.php' );
		/* @noinspection PhpIncludeInspection */
		require_once $this->plugin_dir( 'includes/common/class-mp-installer.php' );

		/* @noinspection PhpIncludeInspection */
		require_once $this->plugin_dir( 'includes/common/class-mp-cart.php' );
		/* @noinspection PhpIncludeInspection */
		require_once $this->plugin_dir( 'includes/common/template-functions.php' );
		/* @noinspection PhpIncludeInspection */
		require_once $this->plugin_dir( 'includes/common/class-mp-backward-compatibility.php' );
		/* @noinspection PhpIncludeInspection */
		require_once $this->plugin_dir( 'includes/common/class-mp-taxes.php' );

		if ( ! function_exists( 'is_plugin_active_for_network' ) ) {
			require_once( ABSPATH . '/wp-admin/includes/plugin.php' );
		}

		if ( is_multisite() && is_plugin_active_for_network( mp_get_plugin_slug() ) ) {
			/* @noinspection PhpIncludeInspection */
			require_once $this->plugin_dir( 'includes/multisite/class-mp-multisite.php' );
			/* @noinspection PhpIncludeInspection */
			require_once $this->plugin_dir( 'includes/multisite/template-functions.php' );
			if ( is_admin() ) {
				/* @noinspection PhpIncludeInspection */
				require_once $this->plugin_dir( 'includes/multisite/class-mp-admin-multisite.php' );
			}
		}

		if ( is_admin() ) {
			/* @noinspection PhpIncludeInspection */
			require_once $this->plugin_dir( 'includes/admin/class-mp-admin.php' );
			/* @noinspection PhpIncludeInspection */
			require_once $this->plugin_dir( 'includes/admin/class-mp-pages-admin.php' );

			if ( mp_doing_ajax() ) {
				/* @noinspection PhpIncludeInspection */
				require_once $this->plugin_dir( 'includes/admin/class-mp-ajax.php' );
				/* @noinspection PhpIncludeInspection */
				require_once $this->plugin_dir( 'includes/public/class-mp-public.php' );
			}
			/* @noinspection PhpIncludeInspection */
			require_once $this->plugin_dir( 'includes/admin/class-mp-dashboard-widgets.php' );
		} else {
			/* @noinspection PhpIncludeInspection */
			require_once $this->plugin_dir( 'includes/public/class-mp-public.php' );
		}
	}

	/**
	 * Catch deprecated functions.
	 *
	 * @since 3.0
	 * @access public
	 * @param $method
	 * @param $args
	 * @return mixed
	 */
	public function __call( $method, $args ) {
		switch ( $method ) {
			case 'display_currency' :
				_deprecated_function( $method, '3.0', 'mp_display_currency' );

				return call_user_func_array( 'mp_display_currency', $args );
				break;

			case 'get_download_url' :
				_deprecated_function( $method, '3.0', 'MP_Product::download_url' );
				$product = new MP_Product( $args[0] );

				return $product->download_url( $args[1], false );
				break;

			case 'mail' :
				_deprecated_function( $method, '3.0', 'mp_send_email' );

				return call_user_func_array( 'mp_send_email', $args );
				break;

			case 'order_notification' :
				_deprecated_function( $method, '3.0', 'MP_Order::send_notifications' );
				$order = new MP_Order( $args[0] );
				$order->send_notifications();
				break;

			case 'get_order' :
				_deprecated_function( $method, '3.0', 'MP_Order' );
				break;

			case 'low_stock_notification' :
				_deprecated_function( $method, '3.0', 'MP_Product::low_stock_notification' );
				break;

			case 'create_order' :
				_deprecated_function( $method, '3.0', 'MP_Order::save' );
				break;

			case 'generate_order_id' :
				_deprecated_function( $method, '3.0', 'MP_Order::get_id' );
				$order = new MP_Order();

				return $order->get_id();
				break;

			case 'cart_checkout_error' :
				_deprecated_function( $method, '3.0', 'MP_Checkout::add_error OR MP_Checkout::get_error' );
				break;

			case 'is_valid_zip' :
				_deprecated_function( $method, '3.0', 'mp_is_valid_zip' );

				return call_user_func_array( 'mp_is_valid_zip', $args );
				break;

			case 'coupon_applicable' :
				_deprecated_function( $method, '3.0', 'MP_Coupon::is_applicable' );
				$is_applicable = false;

				if ( class_exists( 'MP_Coupon' ) ) {
					$coupon        = new MP_Coupon( $args[0] );
					$is_applicable = $coupon->is_applicable( $args[1] );
				}

				return $is_applicable;
				break;

			case 'download_only_cart' :
				//_deprecated_function( $method, '3.0', 'MP_Cart::is_download_only' );
				$cart = MP_Cart::get_instance();
				$cart->set_id( $args[0] );
				$is_download_only = $cart->is_download_only();
				$cart->reset_id();

				return $is_download_only;
				break;

			case 'get_setting' :
				_deprecated_function( $method, '3.0', 'mp_get_setting' );

				return call_user_func_array( 'mp_get_setting', $args );
				break;

			case 'format_currency' :
				_deprecated_function( $method, '3.0', 'mp_format_currency' );

				return call_user_func_array( 'mp_format_currency', $args );
				break;

			case 'format_date' :
				_deprecated_function( $method, '3.0', 'mp_format_date' );

				return call_user_func_array( 'mp_format_date', $args );
				break;

			case 'product_excerpt' :
				_deprecated_function( $method, '3.0', 'mp_product_excerpt' );

				return call_user_func_array( 'mp_product_excerpt', $args );
				break;

			case 'product_price' :
				_deprecated_function( $method, '3.0', 'mp_product_price' );

				return call_user_func_array( 'mp_product_price', $args );
				break;

			case 'shipping_price' :
				_deprecated_function( $method, '3.0', 'MP_Cart::shipping_total' );
				$mp_cart = mp_cart();

				return call_user_func_array( array( $mp_cart, 'shipping_total' ), $args );
				break;

			case 'tax_price' :
				_deprecated_function( $method, '3.0', 'MP_Cart::tax_total' );
				$mp_cart = mp_cart();

				return call_user_func_array( array( $mp_cart, 'tax_total' ), $args );
				break;

			default :
				trigger_error( 'Error! MarketPress doesn\'t have a ' . $method . ' method.', E_USER_ERROR );
				break;
		}
	}

	/**
	 * Initializes the class variables
	 *
	 * @since 3.0
	 * @access private
	 */
	private function _init_vars() {
		// Setup proper directories.
		$this->_plugin_file = __FILE__;
		$this->_plugin_dir  = plugin_dir_path( __FILE__ );
		$this->_plugin_url  = plugin_dir_url( __FILE__ );
		$this->plugin_title = 'MarketPress';

		// Load data structures.
		require_once $this->plugin_dir( 'includes/common/data.php' );

		/**
		 * Filter the currencies list
		 *
		 * @since 3.0
		 *
		 * @param array $this ->currencies An array of available currencies
		 */
		$this->currencies = apply_filters( 'mp_currencies', $this->currencies );
	}

}


$GLOBALS['mp'] = MarketPress::get_instance();

register_activation_hook( __FILE__, 'mp_plugin_activate' );
register_uninstall_hook( __FILE__, 'mp_plugin_uninstall' );

function mp_plugin_activate() {
	if ( get_option( 'mp_needs_quick_setup' ) == false ) {
		add_option( 'mp_needs_quick_setup', 1 );
	}

	update_option( 'mp_flush_rewrites', 1 );
}

//Auskommentiert, falls du alle Plugin-Daten beim Löschen mitentfernen möchtest Kommentare entfernen
/*function mp_plugin_uninstall() {
	global $wpdb;

	$table_attr = $wpdb->prefix . 'mp_product_attributes';
	$sql_attr = "DROP TABLE IF EXISTS $table_attr;";
    $wpdb->query( $sql_attr );

	$table_attr_terms = $wpdb->prefix . 'mp_product_attributes_terms';
	$sql_attr_terms = "DROP TABLE IF EXISTS $table_attr_terms;";
    $wpdb->query( $sql_attr_terms );

    delete_site_option( 'mp_deprecated_gateway_notice_showed' );
	delete_site_option( 'mp_flush_rewrites' );
	delete_site_option( 'mp_flush_rewrites_30' );
	delete_site_option( 'mp_needs_pages' );
	delete_site_option( 'mp_needs_quick_setup' );
	delete_site_option( 'mp_settings' );
	delete_site_option( 'mp_version' );
}*/
