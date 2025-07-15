# MarketPress eCommerce

All development for MarketPress should be done in the `development` branch. Please fork and submit pull-requests to the `development` branch only!

## Start development

1. Install node, `nvm` can be used to switch between node versions.
2. In marketpress folder run `npm install` to install all needed packages in the `node_modules` folder.
3. Execute `npm run watch` to start watching changes to CSS and JS files.

## Npm run options
`npm run watch` - Watch for CSS and JS changes

`npm run release` - Prepare CSS, JS and POT files for release

`npm run build` - Build packages for both Pro and Free versions (zip files can be found in `build` directory)

## MarketPress Entwickler-Dokumentation

## Übersicht aller Hooks, Actions und Filter

Diese Datei listet alle im MarketPress-Plugin verwendeten WordPress-Hooks, Actions und Filter auf. Sie dient als Referenz für Entwickler, die eigene Erweiterungen oder Anpassungen vornehmen möchten.

---

## Gefundene Hooks, Actions und Filter

### Vollständige, nach Typ sortierte Liste

#### add_action
```
add_action('admin_menu', 'mp_st_admin_menu');
add_action('admin_enqueue_scripts', 'mp_st_enqueue_scripts');
add_action('wp_ajax_mp_get_sales_data', 'mp_st_get_sales_data');
add_action('init', array( &$this, 'register_post_type' ) );
add_action('switch_blog', array( &$this, 'get_applied' ) );
add_action('wp_enqueue_scripts', array( &$this, 'enqueue_css_frontend' ) );
add_action('wp_enqueue_scripts', array( &$this, 'enqueue_js_frontend' ), 25 );
add_action('mp_cart/after_empty_cart', array( &$this, 'remove_all_coupons' ), 10, 1 );
add_action('mp_cart/after_remove_item', array( &$this, 'check_items_in_cart' ), 10 );
add_action('mp_order/new_order', array( &$this, 'process_new_order' ), 10, 1 );
add_action('mp_cart/before_remove_item', array( &$this, 'check_coupons' ), 10, 2 );
add_action('admin_menu', array( &$this, 'add_menu_items' ), 9 );
add_action('pre_get_posts', array( &$this, 'sort_product_coupons' ) );
add_action('admin_print_styles', array( &$this, 'print_css' ) );
add_action('admin_print_footer_scripts', array( &$this, 'print_js' ) );
add_action('init', array( &$this, 'init_metaboxes' ) );
add_action('user_has_cap', array( &$this, 'user_has_cap' ), 10, 4 );
add_action('wp_ajax_mp_coupons_remove', array( &$this, 'ajax_remove_coupon' ) );
add_action('wp_ajax_nopriv_mp_coupons_remove', array( &$this, 'ajax_remove_coupon' ) );
add_action('wp_ajax_mp_coupons_apply', array( &$this, 'ajax_apply_coupon' ) );
add_action('wp_ajax_nopriv_mp_coupons_apply', array( &$this, 'ajax_apply_coupon' ) );
add_action('init', array( &$this, 'init_settings_metaboxes' ) );
add_action('init', array( &$this, 'init' ) );
add_action('add_meta_boxes_mp_order', array( &$this, 'add_meta_box' ) );
add_action('wp_ajax_mp_invoice_pdf_generate', array( &$this, 'generate_pdf' ) );
add_action('wp_ajax_nopriv_mp_invoice_pdf_generate', array( &$this, 'generate_pdf' ) );
add_action('mp_addons/enable/MP_Prosites_Addon', array( &$this, 'on_enable' ) );
...weitere im Quellcode...
```

#### add_filter
```
add_filter('mp_product_file_url_type', array( &$this, 'file_type' ), 99, 1 );
add_filter('mp_admin_multisite/theme_permissions_options', array( &$this, 'permissions_options' ) );
add_filter('mp_admin_multisite/gateway_permissions_options', array( &$this, 'permissions_options' ) );
add_filter('mp_gateway_api/get_gateways', array( &$this, 'get_gateways' ) );
add_filter('mp_get_theme_list', array( &$this, 'get_theme_list' ), 10, 2 );
add_filter('mp_addon_status_column_data', array( &$this, 'disable_active_deactive_ability' ), 10, 2 );
add_filter('mp_cart/after_cart_store_html', array( &$this, 'coupon_form_cart' ), 10, 3 );
add_filter('mp_cart/after_cart_html', array( &$this, 'coupon_form_cart' ), 10, 3 );
add_filter('mp_product/get_price', array( &$this, 'product_price' ), 10, 2 );
add_filter('mp_cart/product_total', array( &$this, 'product_total' ), 10, 2 );
add_filter('mp_cart/total', array( &$this, 'cart_total' ), 10, 3 );
add_filter('mp_cart/tax_total', array( &$this, 'tax_total' ), 10, 3 );
add_filter('mp_cart/cart_meta/product_total', array( &$this, 'cart_meta_product_total' ), 10, 2 );
add_filter('mp_coupon_total_value', array( &$this, 'max_discount' ), 10, 1 );
add_filter('manage_mp_coupon_posts_columns', array( &$this, 'product_coupon_column_headers' ) );
add_filter('manage_edit-mp_coupon_sortable_columns', array( &$this, 'product_coupon_sortable_columns' ) );
add_filter('post_row_actions', array( &$this, 'remove_row_actions' ), 10, 2 );
add_filter('mp_order/details', array( &$this, 'pdf_buttons_order_status' ), 99, 2 );
add_filter('mp_order/sendmail_attachments', array( &$this, 'mp_order_sendmail_attachments' ), 20, 3 );
...weitere im Quellcode...
```

#### do_action
```
do_action('mp_addons/disable' . $addon_obj->class );
do_action('mp_addons/enable/' . $addon_obj->class );
do_action('mp_edit_variation_post_data', $post_id, $_POST );
do_action('mp_save_inline_variation_post_data', $post_id, $value_type, $value_sub_type, $value );
do_action('export_wp', $args );
do_action('rss2_head' );
do_action('wpmudev_metabox/render_settings_metaboxes' );
...weitere im Quellcode...
```

#### apply_filters
```
apply_filters( 'mp_coupon/is_valid', $is_valid, $this );
apply_filters( 'mp_pdf_invoice_button_params', $http_params, $type, $order );
apply_filters( 'mp_pdf_invoice_button', $html, $http_params, $type, $order );
apply_filters( 'mp_pdf_invoice/order_details/after_subtotal', $order_details, $order, $cart, $type );
apply_filters( 'mp_pdf_invoice/order_details/after_discount', $order_details, $order, $cart, $type );
apply_filters( 'mp_pdf_invoice/order_details/after_shipping_total', $order_details, $order, $cart, $type );
apply_filters( 'mp_pdf_invoice/order_details/after_tax_total', $order_details, $order, $cart, $type );
apply_filters( 'mp_pdf_invoice/order_details/after_total', $order_details, $order, $cart, $type );
apply_filters( 'mp_pdf_invoice/order_details/after_payment_method', $order_details, $order, $cart, $type );
apply_filters( 'mp_pdf_invoice_params', $data );
apply_filters( 'mp_addon_status_column_data', $status, $addon );
apply_filters( 'mp_coupons_capability', 'edit_mp_coupons' );
apply_filters( 'mp_store_settings_cap', 'manage_store_settings' );
apply_filters( 'mp_edit_variation_post_data', $meta_array_values, $post_id );
apply_filters( 'mp_edit_variation_post_data_response_array', $response_array );
apply_filters( 'mp_variations_meta', array(...));
apply_filters( 'mp_metabox_array_mp-related-products-metabox', array(...));
apply_filters( 'mp_add_field_array_related_products', array(...));
apply_filters( 'mp_metabox_array_mp-featured_product-metabox', array(...));
apply_filters( 'mp_add_field_array_featured', array(...));
apply_filters( 'mp_metabox_array_mp-product-type-metabox', array(...));
apply_filters( 'mp_add_field_array_product_type', array(...));
apply_filters( 'mp_product_kinds', $product_kinds );
apply_filters( 'mp_metabox_array_mp-product-price-inventory-variants-metabox', array(...));
apply_filters( 'mp_add_field_array_sku', array(...));
apply_filters( 'mp_add_field_array_regular_price', array(...));
apply_filters( 'mp_add_field_array_per_order_limit', array(...));
apply_filters( 'mp_add_field_array_has_sale', array(...));
apply_filters( 'mp_add_field_array_sale_price', array(...));
apply_filters( 'mp_add_field_array_amount', array(...));
apply_filters( 'mp_add_field_array_percentage', array(...));
apply_filters( 'mp_add_field_array_start_date', array(...));
apply_filters( 'mp_add_field_array_end_date', array(...));
apply_filters( 'mp_add_field_array_charge_tax', array(...));
apply_filters( 'mp_add_field_array_special_tax_rate', array(...));
apply_filters( 'mp_add_field_array_charge_shipping', array(...));
apply_filters( 'mp_add_field_array_weight', array(...));
apply_filters( 'mp_add_field_array_kilograms', array(...));
apply_filters( 'mp_add_field_array_pounds', array(...));
apply_filters( 'mp_add_field_array_ounces', array(...));
apply_filters( 'mp_add_field_array_extra_shipping_cost', array(...));
apply_filters( 'mp_add_field_array_inventory_tracking', array(...));
apply_filters( 'mp_add_field_array_inv', array(...));
apply_filters( 'mp_add_field_array_inventory', array(...));
...weitere im Quellcode...
```

#### remove_action / remove_filter
```
remove_action('debug_bar_enqueue_scripts', array($this, 'enqueuePanelDependencies'));
remove_action('wp_ajax_puc_v5_debug_check_now', array($this, 'ajaxCheckNow'));
remove_filter('debug_bar_panels', array($this, 'addDebugBarPanel'));
remove_filter('plugin_row_meta', array($this, 'addViewDetailsLink'), 20);
remove_filter('plugin_row_meta', array($this, 'addCheckForUpdatesLink'), 20);
remove_action('all_admin_notices', array($this, 'displayManualCheckResult'));
remove_action('admin_init', array($this, 'onAdminInit'));
remove_action('plugins_loaded', array($this, 'maybeInitDebugBar'));
remove_filter('upgrader_source_selection', array($this, 'fixDirectoryName'), 10);
remove_filter('http_request_host_is_external', array($this, 'allowMetadataHost'), 10);
remove_action('puc_api_error', array($this, 'collectApiErrors'), 10);
remove_action('init', array($this, 'loadTextDomain'));
...weitere im Quellcode...
```
