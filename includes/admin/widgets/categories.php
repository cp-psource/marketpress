<?php

//Product categories widget
class MarketPress_Categories_Widget extends WP_Widget {

	function __construct() {
		$widget_ops = array( 'classname' => 'widget_categories mp_widget mp_widget_product_categories', 'description' => __( "Eine Liste oder Dropdown-Liste von Shopkategorien aus Deinem MarketPress-Shop.", 'mp' ) );
		parent::__construct( 'mp_categories_widget', __( '(MarketPress) Shopkategorien', 'mp' ), $widget_ops );
	}

	function widget( $args, $instance ) {

		if ( $instance[ 'only_store_pages' ] && !mp_is_shop_page() )
			return;

		extract( $args );

		$title	 = apply_filters( 'widget_title', empty( $instance[ 'title' ] ) ? __( 'Shopkategorien', 'mp' ) : $instance[ 'title' ], $instance, $this->id_base );
		$c		 = $instance[ 'count' ] ? '1' : '0';
		$h		 = $instance[ 'hierarchical' ] ? '1' : '0';
		$d		 = $instance[ 'dropdown' ] ? '1' : '0';

		echo $before_widget;
		if ( $title )
			echo $before_title . $title . $after_title;

		$cat_args = array( 'orderby' => 'name', 'show_count' => $c, 'hierarchical' => $h );

		if ( $d ) {
			$cat_args[ 'show_option_none' ]	 = __( 'Wähle Shopkategorie', 'mp' );
			$cat_args[ 'taxonomy' ]			 = 'product_category';
			$cat_args[ 'id' ]				 = 'mp_category_dropdown';
			mp_dropdown_categories( true, $cat_args );
		} else {
			?>
			<ul id="mp_category_list">
				<?php
				$cat_args[ 'title_li' ]	 = '';
				$cat_args[ 'taxonomy' ]	 = 'product_category';
				wp_list_categories( $cat_args );
				?>
			</ul>
			<?php
		}

		echo $after_widget;
	}

	function update( $new_instance, $old_instance ) {
		$instance						 = $old_instance;
		$instance[ 'title' ]			 = strip_tags( $new_instance[ 'title' ] );
		$instance[ 'count' ]			 = !empty( $new_instance[ 'count' ] ) ? 1 : 0;
		$instance[ 'hierarchical' ]		 = !empty( $new_instance[ 'hierarchical' ] ) ? 1 : 0;
		$instance[ 'dropdown' ]			 = !empty( $new_instance[ 'dropdown' ] ) ? 1 : 0;
		$instance[ 'only_store_pages' ]	 = !empty( $new_instance[ 'only_store_pages' ] ) ? 1 : 0;

		return $instance;
	}

	function form( $instance ) {
		//Defaults
		$instance			 = wp_parse_args( (array) $instance, array( 'title' => '', 'only_store_pages' => 0 ) );
		$title				 = esc_attr( $instance[ 'title' ] );
		$count				 = isset( $instance[ 'count' ] ) ? (bool) $instance[ 'count' ] : false;
		$hierarchical		 = isset( $instance[ 'hierarchical' ] ) ? (bool) $instance[ 'hierarchical' ] : false;
		$dropdown			 = isset( $instance[ 'dropdown' ] ) ? (bool) $instance[ 'dropdown' ] : false;
		$only_store_pages	 = isset( $instance[ 'only_store_pages' ] ) ? (bool) $instance[ 'only_store_pages' ] : false;
		?>
		<p><label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Widgetitel:', 'mp' ); ?></label>
			<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo $title; ?>" /></p>

		<p><input type="checkbox" class="checkbox" id="<?php echo $this->get_field_id( 'dropdown' ); ?>" name="<?php echo $this->get_field_name( 'dropdown' ); ?>"<?php checked( $dropdown ); ?> />
			<label for="<?php echo $this->get_field_id( 'dropdown' ); ?>"><?php _e( 'Zeige als Dropdown', 'mp' ); ?></label><br />

			<input type="checkbox" class="checkbox" id="<?php echo $this->get_field_id( 'count' ); ?>" name="<?php echo $this->get_field_name( 'count' ); ?>"<?php checked( $count ); ?> />
			<label for="<?php echo $this->get_field_id( 'count' ); ?>"><?php _e( 'Zeige Produktanzahl', 'mp' ); ?></label><br />

			<input type="checkbox" class="checkbox" id="<?php echo $this->get_field_id( 'hierarchical' ); ?>" name="<?php echo $this->get_field_name( 'hierarchical' ); ?>"<?php checked( $hierarchical ); ?> />
			<label for="<?php echo $this->get_field_id( 'hierarchical' ); ?>"><?php _e( 'Hierarchie anzeigen', 'mp' ); ?></label></p>

		<p><input type="checkbox" class="checkbox" id="<?php echo $this->get_field_id( 'only_store_pages' ); ?>" name="<?php echo $this->get_field_name( 'only_store_pages' ); ?>"<?php checked( $only_store_pages ); ?> />
			<label for="<?php echo $this->get_field_id( 'only_store_pages' ); ?>"><?php _e( 'Nur auf Shopseiten zeigen', 'mp' ); ?></label></p>
		<?php
	}

}

function MarketPress_Categories_init_Widget ()
{
	return register_widget('MarketPress_Categories_Widget');
}
add_action ('widgets_init', 'MarketPress_Categories_init_Widget');
?>