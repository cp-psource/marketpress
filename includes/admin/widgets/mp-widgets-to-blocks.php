<?php
if ( ! function_exists( 'register_widgets_as_blocks' ) ) {
    function register_widgets_as_blocks() {
        global $wp_widget_factory;
    
        // Liste der MarketPress-Widget-IDs
        $marketpress_widgets = [
            'mp_cart_widget',
            'mp_categories_widget',
            'mp_product_list_widget',
            'mp_tag_cloud_widget',
            'mp_global_product_list_widget',
            'mp_global_tag_cloud_widget',
            'mp_global_category_list_widget',
        ];
    
        foreach ( $wp_widget_factory->widgets as $widget_class => $widget_obj ) {
            $widget_id = $widget_obj->id_base;
    
            // Nur MarketPress-Widgets registrieren
            if ( in_array( $widget_id, $marketpress_widgets, true ) ) {
                // Block-Name basierend auf dem Widget-ID
                $block_name = 'marketpress/' . preg_replace( '/[^a-z0-9-]/', '', str_replace( '_', '-', strtolower( $widget_id ) ) );
    
                // Block registrieren
                register_block_type( $block_name, [
                    'editor_script'   => 'marketpress-widgets-blocks',
                    'render_callback' => function( $attributes, $content ) use ( $widget_obj ) {
                        // Widget-Output rendern
                        ob_start();
                        the_widget( get_class( $widget_obj ), $attributes );
                        return ob_get_clean();
                    },
                ] );
    
                // Debugging-Ausgabe
                error_log( 'Block registriert: ' . $block_name );
            }
        }
    }
    add_action( 'init', 'register_widgets_as_blocks' );
}

// Block-Skripte und -Stile registrieren
function enqueue_widgets_blocks_assets() {
    wp_register_script(
        'marketpress-widgets-blocks',
        plugins_url( 'mp-widgets-to-blocks.js', __FILE__ ),
        [ 'wp-blocks', 'wp-element', 'wp-editor' ],
        filemtime( plugin_dir_path( __FILE__ ) . 'mp-widgets-to-blocks.js' )
    );

    wp_enqueue_script( 'marketpress-widgets-blocks' );
}
add_action( 'enqueue_block_editor_assets', 'enqueue_widgets_blocks_assets' );

//error_log('mp-widgets-to-blocks.php wurde geladen');