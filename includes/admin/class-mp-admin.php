<?php

class MP_Admin {

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
			self::$_instance = new MP_Admin();
		}
		return self::$_instance;
	}

	/**
	 * Constructor
	 *
	 * @since 3.0
	 * @access private
	 */
	private function __construct() {
		//$this->_init_dash_notices();
		add_action( 'init',array( &$this, '_includes' ), 1 );

		//set custom post-updated messages
		add_filter( 'post_updated_messages', array( &$this, 'post_updated_messages' ) );
		//enqueue styles and scripts
		add_action( 'admin_enqueue_scripts', array( &$this, 'enqueue_styles_scripts' ) );

		//add_action( 'admin_head', array( &$this, 'admin_head' ) );
		//add a notice for deprecated gateway
		if ( '1' !== get_option( 'mp_deprecated_gateway_notice_showed' ) ) {
			add_action( 'admin_notices', array( &$this, 'deprecated_gateway_notice' ) );
			add_action( 'admin_footer', array( &$this, 'print_deprecated_notice_scripts' ) );
			add_action( 'wp_ajax_mp_dismissed_deprecated_message', array( &$this, 'dismissed_deprecated_messag' ) );
		}
		// Display a native WP pointer after plugin activation
		    //add_action( 'admin_footer', array( &$this, 'display_activation_pointer' ) );

		// Show notice to run setup wizard.
		if ( '1' === get_option( 'mp_needs_quick_setup', 1 ) && ( ( isset( $_GET['quick_setup_step'] ) && '3' !== $_GET['quick_setup_step'] ) || ! isset( $_GET['quick_setup_step'] ) ) ) {
			if ( ! current_user_can( 'manage_options' ) ) {
				return;
			}

			// Do not show notice if user skipped.
			if ( isset( $_GET['quick_setup_step'] ) && 'skip' === $_GET['quick_setup_step'] ) {
				return;
			}

			add_action( 'admin_notices', array( &$this, 'display_quick_setup_notice' ) );
		}
	}

	public function dismissed_deprecated_messag(){
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		update_option( 'mp_deprecated_gateway_notice_showed' , 1 );
	}

	public function display_quick_setup_notice() {
		?>
		<div id="message" class="update-nag">
			<p>
				<?php _e( '<strong>MarketPress noch nicht Betriebsbereit!</strong> Sobald Du diesen Schnellinstallationsassistenten abgeschlossen hast, hast Du einen voll funktionsfähigen Online-Shop - aufregend! ', 'mp' ); ?>
			</p>
			<p>
				<?php printf( __( '<a class="button button-primary" href="%s">Einrichtung starten</a>', 'mp' ), admin_url( 'admin.php?page=store-setup-wizard' ) ); ?>
				<a class="button button-secondary" href="<?php echo admin_url( add_query_arg( array( 'page' => 'store-setup-wizard', 'quick_setup_step' => 'skip' ), 'admin.php' ) ); ?>"><?php _e( 'Lass das, mach ich selber', 'mp' ); ?></a>
			</p>
		</div>
		<?php
	}

	public function deprecated_gateway_notice(){
	if(!current_user_can('manage_options')){
	return;
	}
		?>
		<div class="update-nag mp-deprecated-notice">
		<div class="mp-notice-text">
		<?php echo sprintf(__("MarketPress bietet Dir eine vielzahl an Zahlungsgateways (an einigen arbeiten wir noch), richte einige ein um Deine Kunden möglichst bequem shoppen lassen zu können. Erledige das am besten gleich <a href=\"%s\">HIER</a>.","mp"),admin_url('admin.php?page=shop-einstellungen-payments')) ?>
		</div>
		<a href="#" class="mp-dismissed-deprecated-notice"><i class="dashicons dashicons-no-alt"></i></a>
		</div>
		<?php
	}

	public function print_deprecated_notice_scripts(){
	if(!current_user_can('manage_options')){
	return;
	}
		?>
			<script type="text/javascript">
				jQuery(function($){
					$('.mp-dismissed-deprecated-notice').on('click', function(e){
					e.preventDefault();
					$.ajax({
						type:'POST',
						data:{
							action:'mp_dismissed_deprecated_message'
						},
						url:ajaxurl,
						success:function(){
							$('.mp-deprecated-notice').fadeOut(150)
						}
					})
					})
				})
			</script>
		<?php
	}

	function admin_head() {
		if ( 'mp_order' == get_current_screen()->post_type ) {
			echo '<style type="text/css">
    .page-title-action {display:none;}
    </style>';
		}
	}

	/**
	 * Includes any necessary files
	 *
	 * @since 3.0
	 * @access public
	 */
	public function _includes() {
		require_once mp_plugin_dir( 'includes/admin/class-mp-orders-admin.php' );
		require_once mp_plugin_dir( 'includes/admin/class-mp-products-admin.php' );
		require_once mp_plugin_dir( 'includes/admin/class-mp-product-attributes-admin.php' );
		require_once mp_plugin_dir( 'includes/admin/class-mp-shop-einstellungen-admin.php' );
		require_once mp_plugin_dir( 'includes/admin/class-mp-setup-wizard.php' );
		require_once mp_plugin_dir( 'includes/admin/class-mp-shortcode-builder.php' );
		//require_once mp_plugin_dir( 'includes/admin/class-mp-agb-admin.php' );
				
	}

	/**
	 * Adds the MarketPress help tab
	 *
	 * @since 3.0
	 * @access public
	 */
	public function add_help_tab() {
		get_current_screen()->add_help_tab( array(
			'id'		 => 'marketpress-help',
			'title'		 => __( 'MarketPress Handbuch', 'mp' ),
			'content'	 => '<iframe src="https://n3rds.work/piestingtal_source/marketpress-shopsystem/" width="100%" height="600px"></iframe>'
		) );
	}

	/**
	 * Displays the export orders form
	 *
	 * @since 3.0
	 * @access public
	 */
	public function export_orders_form() {
		global $wpdb;

		if ( !isset( $_GET[ 'post_status' ] ) || $_GET[ 'post_status' ] != 'trash' ) {
			?>
			<div class="icon32"><img src="<?php echo mp_plugin_url( 'ui/images/download.png' ); ?>" /></div>
			<h2><?php _e( 'Export Orders', 'mp' ); ?></h2>

			<form action="<?php echo admin_url( 'admin-ajax.php?action=mp-orders-export' ); ?>" method="post">
				<?php
				$months = $wpdb->get_results( $wpdb->prepare( "
			SELECT DISTINCT YEAR( post_date ) AS year, MONTH( post_date ) AS month
			FROM $wpdb->posts
			WHERE post_type = %s
			ORDER BY post_date DESC
		", 'mp_order' ) );

				$month_count = count( $months );

				if ( !$month_count || ( 1 == $month_count && 0 == $months[ 0 ]->month ) )
					{return;}

				$m = isset( $_GET[ 'm' ] ) ? (int) $_GET[ 'm' ] : 0;
				?>
				<select name='m'>
					<option<?php selected( $m, 0 ); ?> value='0'><?php _e( 'Zeige jedes Datum', 'mp' ); ?></option>
					<?php
					foreach ( $months as $arc_row ) {
						if ( 0 == $arc_row->year )
							{continue;}

						$month	 = zeroise( $arc_row->month, 2 );
						$year	 = $arc_row->year;

						printf( "<option %s value='%s'>%s</option>\n", selected( $m, $year . $month, false ), esc_attr( $arc_row->year . $month ), $wp_locale->get_month( $month ) . " $year"
						);
					}

					$status = isset( $_GET[ 'post_status' ] ) ? $_GET[ 'post_status' ] : 'all';
					?>
				</select>
				<select name="order_status">
					<option<?php selected( $status, 'all' ); ?> value="all" selected="selected"><?php _e( 'Jeder Status', 'mp' ); ?></option>
					<option<?php selected( $status, 'order_received' ); ?> value="order_received"><?php _e( 'Empfangen', 'mp' ); ?></option>
					<option<?php selected( $status, 'order_paid' ); ?> value="order_paid"><?php _e( 'Bezahlt', 'mp' ); ?></option>
					<option<?php selected( $status, 'order_shipped' ); ?> value="order_shipped"><?php _e( 'Versendet', 'mp' ); ?></option>
					<option<?php selected( $status, 'order_closed' ); ?> value="order_closed"><?php _e( 'Abgeschlossen', 'mp' ); ?></option>
				</select>
				<input type="submit" value="<?php _e( 'Download &raquo;', 'mp' ); ?>" name="export_orders" class="button-secondary" />
			</form>


			<br class="clear">
		<?php } ?>
		
		<?php
	}

	/**
	 * Enqueue styles and scripts
	 *
	 * @since 3.0
	 * @access public
	 */
	public function enqueue_styles_scripts() {
		global $pagenow, $post_type, $mp, $post;

		wp_enqueue_script( 'mp-chosen', mp_plugin_url( 'includes/admin/ui/chosen/chosen.jquery.min.js' ), array( 'jquery' ), MP_VERSION );

		if ( !empty( $pagenow ) && ('post-new.php' === $pagenow || 'post.php' === $pagenow ) ) {
			if ( $post_type == MP_Product::get_post_type() ) {
				wp_enqueue_style( 'mp-font-awesome', mp_plugin_url( 'includes/admin/ui/css/font-awesome.min.css' ), array(), MP_VERSION );
				wp_enqueue_style( 'mp-product-admin', mp_plugin_url( 'includes/admin/ui/css/admin-product.css' ), array( 'mp-font-awesome' ), MP_VERSION );
				wp_enqueue_script( 'mp-repeatable-fields', mp_plugin_url( 'includes/admin/ui/js/repeatable-fields.js' ), array( 'jquery' ), MP_VERSION );

				wp_enqueue_style( 'jquery-smoothness', mp_plugin_url( 'includes/admin/ui/smoothness/jquery-ui-1.12.1.custom.css' ), '', MP_VERSION );
				wp_enqueue_script( 'mp-product-admin', mp_plugin_url( 'includes/admin/ui/js/admin-product.js' ), array( 'jquery', 'mp-repeatable-fields', 'jquery-ui-datepicker' ), MP_VERSION );

				$ajax_nonce = wp_create_nonce( "mp-ajax-nonce" );

				wp_localize_script( 'mp-product-admin', 'mp_product_admin_i18n', array(
					'ajaxurl'								 => admin_url( 'admin-ajax.php' ),
					'creating_vatiations_message'			 => __( 'Erstelle Variationen, bitte warte einen Augenblick...', 'mp' ),
					'ajax_nonce'							 => $ajax_nonce,
					'bulk_update_prices_multiple_title'		 => sprintf( __( 'Aktualisiere Preise für %s Produktvarianten', 'mp' ), '<span class="mp_variants_selected"></span>' ),
					'bulk_update_prices_single_title'		 => sprintf( __( 'Aktualisiere Preis %s Produktvariante', 'mp' ), '<span class="mp_variants_selected"></span>' ),
					'bulk_update_inventory_multiple_title'	 => sprintf( __( 'Aktualisiere Inventar für %s Produktvarianten', 'mp' ), '<span class="mp_variants_selected"></span>' ),
					'bulk_update_inventory_single_title'	 => sprintf( __( 'Aktualisiere Inventar für %s Produktvariante', 'mp' ), '<span class="mp_variants_selected"></span>' ),
					'bulk_delete_multiple_title'			 => sprintf( __( 'Lösche %s Produktvarianten', 'mp' ), '<span class="mp_variants_selected"></span>' ),
					'bulk_delete_single_title'				 => sprintf( __( 'Lösche %s Produktvariante', 'mp' ), '<span class="mp_variants_selected"></span>' ),
					'date_format'							 => PSOURCE_Field_Datepicker::format_date_for_jquery( get_option( 'date_format' ) ),
					'message_valid_number_required'			 => __( 'Gültige Nummer ist erforderlich', 'mp' ),
					'message_input_required'				 => __( 'Eingabe ist erforderlich', 'mp' ),
					'saving_message'						 => __( 'Bitte warten ... Speichern im Gange...', 'mp' ),
					'placeholder_image'						 => $mp->plugin_url( '/includes/admin/ui/images/img-placeholder.jpg' ),
					'status' 								 => $post->post_status
				) );

				//jquery textext
				wp_enqueue_script( 'textext.core', mp_plugin_url( 'includes/admin/ui/js/jquery-textext/src/js/textext.core.js' ), array( 'jquery' ), MP_VERSION );
				wp_enqueue_script( 'textext.plugin.ajax', mp_plugin_url( 'includes/admin/ui/js/jquery-textext/src/js/textext.plugin.ajax.js' ), array( 'jquery' ), MP_VERSION );
				wp_enqueue_script( 'textext.plugin.arrow', mp_plugin_url( 'includes/admin/ui/js/jquery-textext/src/js/textext.plugin.arrow.js' ), array( 'jquery' ), MP_VERSION );
				wp_enqueue_script( 'textext.plugin.autocomplete', mp_plugin_url( 'includes/admin/ui/js/jquery-textext/src/js/textext.plugin.autocomplete.js' ), array( 'jquery' ), MP_VERSION );
				wp_enqueue_script( 'textext.plugin.clear', mp_plugin_url( 'includes/admin/ui/js/jquery-textext/src/js/textext.plugin.clear.js' ), array( 'jquery' ), MP_VERSION );
				wp_enqueue_script( 'textext.plugin.filter', mp_plugin_url( 'includes/admin/ui/js/jquery-textext/src/js/textext.plugin.filter.js' ), array( 'jquery' ), MP_VERSION );
				wp_enqueue_script( 'textext.plugin.focus', mp_plugin_url( 'includes/admin/ui/js/jquery-textext/src/js/textext.plugin.focus.js' ), array( 'jquery' ), MP_VERSION );
				wp_enqueue_script( 'textext.plugin.prompt', mp_plugin_url( 'includes/admin/ui/js/jquery-textext/src/js/textext.plugin.prompt.js' ), array( 'jquery' ), MP_VERSION );
				wp_enqueue_script( 'textext.plugin.suggestions', mp_plugin_url( 'includes/admin/ui/js/jquery-textext/src/js/textext.plugin.suggestions.js' ), array( 'jquery' ), MP_VERSION );
				wp_enqueue_script( 'textext.plugin.tags', mp_plugin_url( 'includes/admin/ui/js/jquery-textext/src/js/textext.plugin.tags.js' ), array( 'jquery' ), MP_VERSION );

				wp_enqueue_style( 'textext.core', mp_plugin_url( 'includes/admin/ui/js/jquery-textext/src/css/textext.core.css' ), array(), MP_VERSION );
				wp_enqueue_style( 'textext.plugin.arrow', mp_plugin_url( 'includes/admin/ui/js/jquery-textext/src/css/textext.plugin.arrow.css' ), array(), MP_VERSION );
				wp_enqueue_style( 'textext.plugin.autocomplete', mp_plugin_url( 'includes/admin/ui/js/jquery-textext/src/css/textext.plugin.autocomplete.css' ), array(), MP_VERSION );
				wp_enqueue_style( 'textext.plugin.clear', mp_plugin_url( 'includes/admin/ui/js/jquery-textext/src/css/textext.plugin.clear.css' ), array(), MP_VERSION );
				wp_enqueue_style( 'textext.plugin.focus', mp_plugin_url( 'includes/admin/ui/js/jquery-textext/src/css/textext.plugin.focus.css' ), array(), MP_VERSION );
				wp_enqueue_style( 'textext.plugin.prompt', mp_plugin_url( 'includes/admin/ui/js/jquery-textext/src/css/textext.plugin.prompt.css' ), array(), MP_VERSION );
				wp_enqueue_style( 'textext.plugin.tags', mp_plugin_url( 'includes/admin/ui/js/jquery-textext/src/css/textext.plugin.tags.css' ), array(), MP_VERSION );
			}


			if ( $post_type == MP_Product::get_variations_post_type() ) {
				wp_enqueue_style( 'mp-product-variation-admin', mp_plugin_url( 'includes/admin/ui/css/admin-product-variation.css' ), false, MP_VERSION );
				wp_enqueue_script( 'mp-product-variation-admin', mp_plugin_url( 'includes/admin/ui/js/admin-product-variation.js' ), array( 'jquery' ), MP_VERSION );
			}
		}

		if ( ! empty( $pagenow ) && ( 'plugins.php' === $pagenow ) ) {
			wp_enqueue_script( 'wp-pointer' );
			wp_enqueue_style( 'wp-pointer' );
		}

		$quick_setup = mp_get_get_value( 'quick_setup_step' );
		if ( isset( $quick_setup ) ) {
			//wp_enqueue_style( 'mp-quick-setup', mp_plugin_url( 'includes/admin/ui/css/quick-setup.css' ), array(), MP_VERSION );
			wp_enqueue_script( 'mp-quick-setup', mp_plugin_url( 'includes/admin/ui/js/quick-setup.js' ), array( 'jquery', 'jquery-ui-tabs' ), MP_VERSION );
		}

		wp_enqueue_style( 'mp-admin', mp_plugin_url( 'includes/admin/ui/css/admin.css' ), array(), MP_VERSION );
	}

	/**
	 * Modifies the post-updated messages for the mp_order, product and mp_coupon post types
	 *
	 * @since 3.0
	 * @access public
	 * @filter post_updated_messages
	 *
*@param array $messages
	 *
*@return array
	 */
	public function post_updated_messages( $messages ) {
		global $post, $post_ID;

		$post_type = get_post_type( $post_ID );

		if ( $post_type != 'mp_order' && $post_type != MP_Product::get_post_type() && $post_type != 'mp_coupon' ) {
			return $messages;
		}

		$obj		 = get_post_type_object( $post_type );
		$singular	 = $obj->labels->singular_name;

		$messages[ $post_type ] = array(
			0	 => '', // Unused. Messages start at index 1.
			1	 => sprintf( __( $singular . ' aktualisiert. <a href="%s">Produkt ansehen ' . '</a>', 'mp' ), esc_url( get_permalink( $post_ID ) ) ),
			2	 => __( 'Benutzerdefiniertes Feld aktualisiert.', 'mp' ),
			3	 => __( 'Benutzerdefiniertes Feld gelöscht.', 'mp' ),
			4	 => __( $singular . ' aktualisiert.', 'mp' ),
			5	 => isset( $_GET[ 'revision' ] ) ? sprintf( __( $singular . ' Wiederhergestellte Revision von %s', 'mp' ), wp_post_revision_title( (int) $_GET[ 'revision' ], false ) ) : false,
			6	 => sprintf( __( $singular . ' veröffentlicht. <a href="%s">Produkt ansehen ' . '</a>', 'mp' ), esc_url( get_permalink( $post_ID ) ) ),
			7	 => __( 'Seite gespeichert.', 'mp' ),
			8	 => sprintf( __( $singular . ' übermittelt. <a target="_blank" href="%s">Vorschau ' . '</a>', 'mp' ), esc_url( add_query_arg( 'preview', 'true', get_permalink( $post_ID ) ) ) ),
			9	 => sprintf( __( $singular . ' eingetragen für: <strong>%1$s</strong>. <a target="_blank" href="%2$s">Vorschau ' . '</a>', 'mp' ), date_i18n( 'M j, Y @ G:i', strtotime( $post->post_date ) ), esc_url( get_permalink( $post_ID ) ) ),
			10	 => sprintf( __( $singular . ' Entwurf aktualisiert. <a target="_blank" href="%s">Vorschau ' . '</a>', 'mp' ), esc_url( add_query_arg( 'preview', 'true', get_permalink( $post_ID ) ) ) ),
		);

		return $messages;
	}

	public function display_activation_pointer() {
		$dismissed = explode(
			',',
			(string) get_user_meta( get_current_user_id(), 'dismissed_wp_pointers', true ) );
		if ( in_array( 'mp-activation-pointer', $dismissed ) ) {
			return;
		}
		$quick_setup_needed = (int) get_option( 'mp_needs_quick_setup', 1 );
		$wizard_button = sprintf(
			'<a class="button button-primary" href="%s">%s</a>',
			admin_url( 'admin.php?page=store-setup-wizard' ),
			esc_html__( 'Starte den Einrichtungsassistenten', 'mp' )
		);
		$dismiss_button = sprintf(
			'<a class="close" href="#">%s</a>',
			esc_html__( 'ABLEHNEN', 'mp' )
		);
		$activation_content = sprintf(
			'<h3>%s</h3><p>%s</p><div class="wp-pointer-buttons">%s %s</div>',
			esc_html__( 'Erstellen wir Deinen Online-Shop', 'mp' ),
			esc_html__( 'Erstelle hier Shopseiten und Produkte für Deinen Online-Shop.', 'mp' ),
			$quick_setup_needed ? $wizard_button : '<span></span>',
			$dismiss_button
		);

		?>
		<script type="text/javascript">
			;jQuery(document).ready(function ($) {
				function mp_open_pointer(id, target, content) {
					var options = {
						position: {edge: 'left', align: 'right'},
						pointerClass: 'wp-pointer mp-admin-pointer',
						content: content,
						buttons: function () {
							return $();
						},
						show: function (event, pointer_target) {
							pointer_target.pointer.css({'position': 'fixed'});
							pointer_target.pointer.find('.close').on('click.pointer', function (e) {
								e.preventDefault();
								pointer_target.element.pointer('close');
							});
						},
						close: function () {
							$.post(ajaxurl, {
								pointer: id,
								action: 'dismiss-wp-pointer'
							});
						}
					};

					var $target = $(target);
					$target.pointer(options);
					if ($target.is(':visible')) {
						$target.pointer('open');
					}
				}

				mp_open_pointer('mp-activation-pointer', '#toplevel_page_shop-einstellungen', '<?php echo $activation_content; ?>');
			});
		</script>
		<style>
			@media screen and (max-width: 782px) {
				.mp-admin-pointer {
					display: none !important;
				}
			}

			.mp-admin-pointer .wp-pointer-buttons {
				display: flex;
				justify-content: space-between;
				align-items: center;
			}
		</style>
		<?php
	}

}

MP_Admin::get_instance();