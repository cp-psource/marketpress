<?php
if ( ! function_exists( 'is_plugin_active_for_network' ) ) {
	require_once( ABSPATH . '/wp-admin/includes/plugin.php' );
}

if ( is_multisite() ) {

//Product tags cloud
	class MarketPress_Global_Category_List_Widget extends WP_Widget {

		function __construct() {
			$widget_ops = array( 'classname'   => 'mp_global_category_list_widget',
			                     'description' => __( "Zeigt eine netzwerkweite HTML-Liste der Produktkategorien aus MarketPress-Stores im Netzwerk an.", 'mp' )
			);
			parent::__construct( 'mp_global_category_list_widget', __( 'Netzwerk Shopkategorien', 'mp' ), $widget_ops );
		}

		function widget( $args, $instance ) {
			extract( $args );

			if ( ! empty( $instance['title'] ) ) {
				$title = $instance['title'];
			} else {
				$title = __( 'Netzwerk Shopkategorien', 'mp' );
			}
			$title = apply_filters( 'widget_title', $title, $instance, $this->id_base );

			echo $before_widget;
			if ( $title ) {
				echo $before_title . $title . $after_title;
			}

			mp_global_taxonomy_list( 'product_category', $instance, true );

			echo $after_widget;
		}

		function update( $new_instance, $old_instance ) {
			$instance['title']      = strip_tags( stripslashes( $new_instance['title'] ) );
			$instance['include']    = stripslashes( $new_instance['include'] );
			$instance['limit']      = intval( $new_instance['limit'] );
			$instance['order_by']   = $new_instance['order_by'];
			$instance['order']      = $new_instance['order'];
			$instance['show_count'] = ! empty( $new_instance['show_count'] ) ? 1 : 0;

			return $instance;
		}

		function form( $instance ) {
			$instance = wp_parse_args( (array) $instance, array( 'title'      => __( 'Netzwerk Shopkategorien', 'mp' ),
			                                                     'order_by'   => 'name',
			                                                     'order'      => 'ASC',
			                                                     'limit'      => 50,
			                                                     'show_count' => 0,
			                                                     'include'    => 'categories'
			) );
			extract( $instance );
			?>
			<p><label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Titel:', 'mp' ) ?></label>
				<input type="text" class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>"
				       name="<?php echo $this->get_field_name( 'title' ); ?>" value="<?php if ( isset ( $title ) ) {
					echo esc_attr( $title );
				} ?>"/></p>

			<p>
				<label
					for="<?php echo $this->get_field_id( 'order_by' ); ?>"><?php _e( 'Ordne Kategorien nach:', 'mp' ) ?></label><br/>
				<select id="<?php echo $this->get_field_id( 'order_by' ); ?>"
				        name="<?php echo $this->get_field_name( 'order_by' ); ?>">
					<option value="name"<?php selected( $order_by, 'name' ) ?>><?php _e( 'Name', 'mp' ) ?></option>
					<option
						value="count"<?php selected( $order_by, 'count' ) ?>><?php _e( 'Produktanzahl', 'mp' ) ?></option>
				</select><br/>
				<label><input value="DESC" name="<?php echo $this->get_field_name( 'order' ); ?>"
				              type="radio"<?php checked( $order, 'DESC' ) ?> /> <?php _e( 'Absteigend', 'mp' ) ?>
				</label>
				<label><input value="ASC" name="<?php echo $this->get_field_name( 'order' ); ?>"
				              type="radio"<?php checked( $order, 'ASC' ) ?> /> <?php _e( 'Aufsteigend', 'mp' ) ?></label>
			</p>

			<p>
				<label for="<?php echo $this->get_field_id( 'limit' ); ?>"><?php _e( 'Anzahl der Kategorien:', 'mp' ) ?>
					<input id="<?php echo $this->get_field_id( 'limit' ); ?>"
					       name="<?php echo $this->get_field_name( 'limit' ); ?>" type="text" size="3"
					       value="<?php echo intval( $limit ); ?>"/></label><br/>
			</p>

			<p>
				<input type="checkbox" class="checkbox" id="<?php echo $this->get_field_id( 'show_count' ); ?>"
				       name="<?php echo $this->get_field_name( 'show_count' ); ?>"<?php checked( $show_count ); ?> />
				<label
					for="<?php echo $this->get_field_id( 'show_count' ); ?>"><?php _e( 'Zeige Produktanzahl', 'mp' ); ?></label>
			</p>
			<?php
		}

	}

	//add_action( 'widgets_init', create_function( '', 'return register_widget("MarketPress_Global_Category_List_Widget");' ) );
	add_action( 'widgets_init', 'MarketPress_Global_Category_List_Widget' ); function MarketPress_Global_Category_List_Widget() {return register_widget('MarketPress_Global_Category_List_Widget');}
}
?>