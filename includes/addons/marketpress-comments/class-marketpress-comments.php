<?php
/*
Plugin Name: MarketPress Erlaube Kommentare
Version: 0.3
Plugin URI: https://n3rds.work
Description: Ein einfaches Add-On, mit dem Kommentare zu Produken hinzugefügt werden können.
Author: DerN3rd
Author URI: https://n3rds.work
*/

add_action('init', 'shurf_wpml_marketpress_init');

function shurf_wpml_marketpress_init() {
	add_post_type_support( 'product', 'comments' );
}

