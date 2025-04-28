<?php

class MP_Dashboard_Widgets {

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
			self::$_instance = new MP_Dashboard_Widgets();
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
//enqueue styles and scripts
		add_action( 'admin_enqueue_scripts', array( &$this, 'enqueue_styles_scripts' ) );
		add_action( 'wp_dashboard_setup', array( &$this, 'add_mp_dashboard_widgets' ) );
	}

	/**
	 * Enqueue styles and scripts
	 *
	 * @since 3.0
	 * @access public
	 */
	public function enqueue_styles_scripts() {
		global $pagenow, $mp;

		if ( !empty( $pagenow ) && ('index.php' === $pagenow) ) {

			$ajax_nonce = wp_create_nonce( "mp-ajax-nonce" );

			wp_enqueue_style( 'mp-dashboard-widgets', mp_plugin_url( 'includes/admin/ui/css/mp-dashboard-widgets.css' ), false, MP_VERSION );
			wp_enqueue_style( 'mp-dashboard-widgets-font-awesome', mp_plugin_url( 'includes/admin/ui/css/font-awesome.min.css' ), array(), MP_VERSION );
			wp_enqueue_script( 'mp-dashboard-widgets', mp_plugin_url( 'includes/admin/ui/js/mp-dashboard-widgets.js' ), array( 'jquery' ), MP_VERSION );

			wp_localize_script( 'mp-dashboard-widgets', 'mp_product_admin_i18n', array(
				'ajaxurl'								 => admin_url( 'admin-ajax.php' ),
				'creating_vatiations_message'			 => __( 'Variationen erstellen, bitte warten ...', 'mp' ),
				'ajax_nonce'							 => $ajax_nonce,
				'bulk_update_prices_multiple_title'		 => sprintf( __( 'Update prices for %s product variants', 'mp' ), '<span class="mp_variants_selected"></span>' ),
				'bulk_update_prices_single_title'		 => sprintf( __( 'Update price for %s product variant', 'mp' ), '<span class="mp_variants_selected"></span>' ),
				'bulk_update_inventory_multiple_title'	 => sprintf( __( 'Update inventory for %s product variants', 'mp' ), '<span class="mp_variants_selected"></span>' ),
				'bulk_update_inventory_single_title'	 => sprintf( __( 'Update inventory for %s product variant', 'mp' ), '<span class="mp_variants_selected"></span>' ),
				'bulk_delete_multiple_title'			 => sprintf( __( 'Delete %s product variants', 'mp' ), '<span class="mp_variants_selected"></span>' ),
				'bulk_delete_single_title'				 => sprintf( __( 'Delete %s product variant', 'mp' ), '<span class="mp_variants_selected"></span>' ),
				'date_format'							 => PSOURCE_Field_Datepicker::format_date_for_jquery( get_option( 'date_format' ) ),
				'message_valid_number_required'			 => __( 'Valid number is required', 'mp' ),
				'message_input_required'				 => __( 'Input is required', 'mp' ),
				'saving_message'						 => __( 'Please wait...saving in progress...', 'mp' ),
				'placeholder_image'						 => $mp->plugin_url( '/includes/admin/ui/images/img-placeholder.jpg' )
			) );
		}
	}

	public static function mp_dashboard_low_stock_query() {
		$inventory_threshhold = mp_get_setting( 'inventory_threshhold' );

		$out_of_stock_query = new WP_Query( array(
			'post_type'		 => array( MP_Product::get_post_type(), MP_Product::get_variations_post_type() ),
			'post_status'	 => 'publish',
			'posts_per_page' => 5,
			'meta_query'	 => array(
				'relation' => 'AND',
				array(
					'key'		 => 'inventory_tracking',
					'value'		 => '1',
					'compare'	 => '=',
					'type'		 => 'NUMERIC',
				),
				array(
					'relation' => 'AND',
					array(
						'key'		 => 'inventory',
						'value'		 => $inventory_threshhold,
						'compare'	 => '<=',
						'type'		 => 'NUMERIC',
					),
					array(
						'key'		 => 'inventory',
						'value'		 => array( '' ),
						'compare'	 => 'NOT IN',
					),
				)
			),
		) );

		return $out_of_stock_query;
	}

	public function add_mp_dashboard_widgets() {
		if ( !current_user_can( apply_filters( 'mp_can_view_dashboard_widgets_capability_needed', 'manage_options' ) ) ) {
			return;
		}
		wp_add_dashboard_widget( 'mp_store_report', __( 'Shop Berichte', 'mp' ), array( &$this, 'mp_store_report_display' ) );
		wp_add_dashboard_widget( 'mp_store_management', __( 'Shop Verwaltung', 'mp' ), array( &$this, 'mp_store_management_display' ) );

		$out_of_stock_query = $this->mp_dashboard_low_stock_query();

		$low_stock_count = $out_of_stock_query->found_posts;
		wp_add_dashboard_widget( 'mp_low_stock', sprintf( __( 'Niedriger Lagerstand <span class="low_stock_value">(%s)</span>', 'mp' ), $low_stock_count ), array( &$this, 'mp_low_stock_display' ) );
	}

	public function mp_low_stock_display() {
		$out_of_stock_query = $this->mp_dashboard_low_stock_query();
		?>
		<div class='mp-dashboard-widget-low-stock-wrap-overlay'></div>
		<div class="mp-dashboard-widget-low-stock-wrap">

			<?php if ( $out_of_stock_query->have_posts() ) { ?>
				<table class="wp-list-table widefat fixed striped posts">
					<thead>
						<tr>
							<th scope="col" id="mp_product_name" class="manage-column column-tags"><?php _e( 'Produktname', 'mp' ); ?></th>
							<th scope="col" id="mp_variation_name" class="manage-column column-tags"><?php _e( 'Variante', 'mp' ); ?></th>
							<th scope="col" id="mp_stock_level" class="manage-column column-tags"><?php _e( 'Lagerstand', 'mp' ); ?></th>
						</tr>
					</thead>

					<tbody id="the-list">
						<?php
						if ( $out_of_stock_query->have_posts() ) {
							while ( $out_of_stock_query->have_posts() ) {
								$out_of_stock_query->the_post();
								$edit_link		 = '';
								$is_variation	 = false;

								$inventory = get_post_meta( get_the_ID(), 'inventory', true );

								if ( get_post_type( get_the_ID() ) == MP_Product::get_post_type() ) {
									$is_variation	 = false;
									$edit_link		 = get_edit_post_link();
								} else {
									$is_variation	 = true;
									$post_parent	 = wp_get_post_parent_id( get_the_ID() );
									$edit_link		 = get_edit_post_link( $post_parent );
									$post_id		 = $post_parent;
								}
								?>
								<tr class="iedit author-self level-0 type-post status-publish format-standard hentry category-uncategorized">
									<th scope="row" class="check-column mp_hidden_content">
										<input type="checkbox" class="check-column-box" name="" value="<?php echo esc_attr( get_the_ID() ); ?>">
									</th>

									<td class="post-title page-title column-title">
										<strong><a class="row-title" href="<?php echo esc_attr( $edit_link ); ?>"><?php the_title(); ?></a></strong>
									</td>

									<td class="tags column-tags">
										<?php
										if ( $is_variation ) {
											echo get_post_meta( get_the_ID(), 'name', true );
										} else {
											echo '—';
										}
										?>
									</td>

									<td class="tags column-tags <?php echo $inventory <= 0 ? 'mp_low_stock_red' : 'mp_low_stock_yellow'; ?> field_editable field_editable_inventory" data-field-type="number" data-hide-field-product-type="external">
										<span class="original_value field_subtype field_subtype_inventory" data-meta="inventory" data-default="&infin;">
											<?php
											echo esc_attr( isset( $inventory ) && !empty( $inventory ) || $inventory == '0' ? $inventory : '&infin;'  );
											?>
										</span>
									</td>
								</tr>
								<?php
							}
						}
						?>
					</tbody>
				</table>
				<?php
			} else {
				?>
				<p><?php _e( 'Keine Produkte mit niedrigen Lagerstand.', 'mp' ); ?></p>
				<?php
			}
			?>
		</div>
		<?php
		$inventory_threshhold		 = mp_get_setting( 'inventory_threshhold' );
		$max_inventory_threshhold	 = apply_filters( 'mp_dashboard_widget_max_inventory_threshhold', 20 );
		?>
		<div class="mp_dashboard_widget_inventory_threshhold_wrap">
			<form id="inventory_threshhold_form" method="post">
				<input type="hidden" name="action" value="save_inventory_threshhold">
				<span class="mp-dashboard-section-title"><?php _e( 'Schwellenwert für Inventarwarnung', 'mp' ); ?></span>
				<select name="inventory_threshhold" id="mp_dashboard_widget_inventory_threshhold">
					<?php
					for ( $i = 0; $i <= $max_inventory_threshhold; $i++ ) {
						?>
						<option value="<?php echo $i; ?>" <?php selected( $i, $inventory_threshhold, true ); ?>><?php echo $i; ?></option>
						<?php
					}
					?>
				</select>
				<span class="mp_ajax_response"></span>
			</form>
		</div>
		<?php
	}

	public function mp_store_report_display() {
		global $wpdb;

		$today_date			   = date( "Y-m-d", time() );
		$yesterday_date		 = date( "Y-m-d", time() - 60 * 60 * 24 );
		$seven_days_date	 = date( "Y-m-d", time() - 60 * 60 * 24 * 7 );
		$thirty_days_date	 = date( "Y-m-d", time() - 60 * 60 * 24 * 30 );

		$day_current	 = date( 'd' );
		$month_current = date( 'm' );
		$year_current	 = date( 'Y' );

		$today		   = $wpdb->get_row( "SELECT count(p.ID) as count, sum(m.meta_value) as 'total', avg(m.meta_value) as average FROM $wpdb->posts p JOIN $wpdb->postmeta m ON p.ID = m.post_id WHERE p.post_type = 'mp_order' AND m.meta_key = 'mp_order_total' AND p.post_date LIKE '" . $year_current . "-" . $month_current . "-" . $day_current . "%' AND p.post_status != 'trash'" );
		$yesterday	 = $wpdb->get_row( "SELECT count(p.ID) as count, sum(m.meta_value) as 'total', avg(m.meta_value) as average FROM $wpdb->posts p JOIN $wpdb->postmeta m ON p.ID = m.post_id WHERE p.post_type = 'mp_order' AND m.meta_key = 'mp_order_total' AND p.post_date LIKE '" . $yesterday_date . "%' AND p.post_status != 'trash'" );
		$seven_days	 = $wpdb->get_row( "SELECT count(p.ID) as count, sum(m.meta_value) as 'total', avg(m.meta_value) as average FROM $wpdb->posts p JOIN $wpdb->postmeta m ON p.ID = m.post_id WHERE p.post_type = 'mp_order' AND m.meta_key = 'mp_order_total' AND p.post_date >= '" . $seven_days_date . "' AND p.post_status != 'trash'" );
		$thirty_days = $wpdb->get_row( "SELECT count(p.ID) as count, sum(m.meta_value) as 'total', avg(m.meta_value) as average FROM $wpdb->posts p JOIN $wpdb->postmeta m ON p.ID = m.post_id WHERE p.post_type = 'mp_order' AND m.meta_key = 'mp_order_total' AND p.post_date >= '" . $thirty_days_date . "' AND p.post_status != 'trash'" );
		//$ninty_days  = $wpdb->get_row( "SELECT count(p.ID) as count, sum(m.meta_value) as 'total', avg(m.meta_value) as average FROM $wpdb->posts p JOIN $wpdb->postmeta m ON p.ID = m.post_id WHERE p.post_type = 'mp_order' AND m.meta_key = 'mp_order_total' AND p.post_date >= '" . $ninty_days_date . "' AND p.post_status != 'trash'" );
        //$year_days   = $wpdb->get_row( "SELECT count(p.ID) as count, sum(m.meta_value) as 'total', avg(m.meta_value) as average FROM $wpdb->posts p JOIN $wpdb->postmeta m ON p.ID = m.post_id WHERE p.post_type = 'mp_order' AND m.meta_key = 'mp_order_total' AND p.post_date >= '" . $year_days_date . "' AND p.post_status != 'trash'" );
		?>
		<p><span><?php _e( "Willkommen zurück! Hier ist eine kurze Zusammenfassung der Shopaktivitäten.", 'mp' ); ?></span></p>
		<div class="main store-report">
			<span class="mp-dashboard-section-title"><?php _e( 'Verkäufe', 'mp' ); ?></span>
			<div class="mp-dashboard-stats-wrapper">
				<div class="mp-dashboard-square mp-dashboard-left">
					<span class="mp-dashboard-square-title"><?php _e( 'Heute', 'mp' ); ?></span>
					<span class="mp-dashboard-square-amount"><?php echo mp_format_currency( '', $today->total ); ?></span>
					<span class="mp-dashboard-square-footer"><?php echo $today->count . __( ' Bestellungen', 'mp' ); ?></span>
				</div>
				<div class="mp-dashboard-square mp-dashboard-right">
					<span class="mp-dashboard-square-title"><?php _e( 'Gestern', 'mp' ); ?></span>
					<span class="mp-dashboard-square-amount"><?php echo mp_format_currency( '', $yesterday->total ); ?></span>
					<span class="mp-dashboard-square-footer"><?php echo $yesterday->count . __( ' Bestellungen', 'mp' ); ?></span>
				</div>
				<div class="mp-dashboard-square mp-dashboard-left">
					<span class="mp-dashboard-square-title"><?php _e( 'Letzten 7 Tage', 'mp' ); ?></span>
					<span class="mp-dashboard-square-amount"><?php echo mp_format_currency( '', $seven_days->total ); ?></span>
					<span class="mp-dashboard-square-footer"><?php echo $seven_days->count . __( ' Bestellungen', 'mp' ); ?></span>
				</div>
				<div class="mp-dashboard-square mp-dashboard-right">
					<span class="mp-dashboard-square-title"><?php _e( 'Letzten 30 Tage', 'mp' ); ?></span>
					<span class="mp-dashboard-square-amount"><?php echo mp_format_currency( '', $thirty_days->total ); ?></span>
					<span class="mp-dashboard-square-footer"><?php echo $thirty_days->count . __( ' Bestellungen', 'mp' ); ?></span>
				</div>
			</div>

			<?php
			$count_posts = wp_count_posts( 'mp_order' );

			$out_of_stock_query = MP_Dashboard_Widgets::mp_dashboard_low_stock_query();

			$received_orders	 = $count_posts->order_received;
			$paid_orders		 = $count_posts->order_paid;
			$low_stock_products	 = $out_of_stock_query->found_posts;
			?>
			<span class="mp-dashboard-section-title"><?php _e( 'Lager & Bestellungen', 'mp' ); ?></span>

			<div class="mp-dashboard-section-stock-orders">
				<span class="mp-dashboard-stock-orders-title"><?php printf( _n( '%s Bestellung', '%s Bestellungen', $received_orders, 'mp' ), $received_orders ); ?></span>
				<span class="mp-dashboard-stock-orders-subtitle"><?php _e( 'Empfangen', 'mp' ); ?></span>
			</div>
			<div class="mp-dashboard-section-stock-orders">
				<span class="mp-dashboard-stock-orders-title"><?php printf( _n( '%s Bestellung', '%s Bestellungen', $paid_orders, 'mp' ), $paid_orders ); ?></span>
				<span class="mp-dashboard-stock-orders-subtitle"><?php _e( 'Bezahlt', 'mp' ); ?></span>
			</div>
			<div class="mp-dashboard-section-stock-orders">
				<span class="mp-dashboard-stock-orders-title"><?php printf( _n( '<span class="low_stock_value">%s</span> Produkte', '<span class="low_stock_value">%s</span> Produkte', $low_stock_products, 'mp' ), $low_stock_products ); ?></span>
				<span class="mp-dashboard-stock-orders-subtitle"><?php _e( 'Niedriger Lagerstand', 'mp' ); ?></span>
			</div>
		</div>
		<br clear="both" />
		<?php
	}

	public function mp_store_management_display() {
		?>
		<p><span><?php _e( "Hier findest Du einige schnelle Links zum Verwalten Deines Shops und Deiner Produkte, sowie Hilfequellen für Anfänger und Profis.", 'mp' ); ?></span></p>
		<div class="main store-management">
			<ul class="store-management-left">
				<li><span><?php _e( 'Verwaltung', 'mp' ); ?></span></li>
				<li><a href="<?php echo admin_url( 'edit.php?post_type=mp_order' ); ?>"><?php _e( 'Bestellungen', 'mp' ); ?></a></li>
				<?php
				if ( MP_Addons::get_instance()->is_addon_enabled( 'MP_Coupons_Addon' ) ) {
					?>
					<li><a href="<?php echo admin_url( 'edit.php?post_type=mp_coupon' ); ?>"><?php _e( 'Gutscheine', 'mp' ); ?></a></li>
				<?php } ?>
				<li><a href="<?php echo admin_url( 'edit.php?post_type=' . MP_Product::get_post_type() ); ?>"><?php _e( 'Produkte', 'mp' ); ?></a></li>
				<li><a href="<?php echo admin_url( 'edit-tags.php?taxonomy=product_category&post_type=' . MP_Product::get_post_type() ); ?>"><?php _e( 'Kategorien', 'mp' ); ?></a></li>
				<li><a href="<?php echo admin_url( 'edit-tags.php?taxonomy=product_tag&post_type=' . MP_Product::get_post_type() ); ?>"><?php _e( 'Tags', 'mp' ); ?></a></li>
			</ul>
			<ul class="store-management-right">
				<li><span><?php _e( 'Konfiguration', 'mp' ); ?></span></li>
				<li><a href="<?php echo admin_url( 'admin.php?page=shop-einstellungen-presentation' ); ?>"><?php _e( 'Präsentation', 'mp' ); ?></a></li>
				<li><a href="<?php echo admin_url( 'admin.php?page=shop-einstellungen-notifications' ); ?>"><?php _e( 'Email Benachrichtigungen', 'mp' ); ?></a></li>
				<li><a href="<?php echo admin_url( 'admin.php?page=shop-einstellungen-shipping' ); ?>"><?php _e( 'Versandraten', 'mp' ); ?></a></li>
				<li><a href="<?php echo admin_url( 'admin.php?page=shop-einstellungen-payments' ); ?>"><?php _e( 'Zahlungsgateways', 'mp' ); ?></a></li>
				<li><a href="<?php echo admin_url( 'admin.php?page=shop-einstellungen-capabilities' ); ?>"><?php _e( 'Benutzerrechte', 'mp' ); ?></a></li>
				<li><a href="<?php echo admin_url( 'admin.php?page=shop-einstellungen-addons' ); ?>"><?php _e( 'Erweiterungen', 'mp' ); ?></a></li>
				<?php if ( function_exists( 'register_nav_menus' ) ) { ?>
					<li><a href="<?php echo admin_url( 'nav-menus.php' ); ?>"><?php _e( 'Seiten zum Menü hinzufügen', 'mp' ); ?></a></li>
				<?php } ?>
			</ul>
		</div>
		<br clear="both" />
		<?php
	}

}

MP_Dashboard_Widgets::get_instance();
