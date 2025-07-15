<?php
/**
 * Test script for MarketPress Modern UI System
 * 
 * This script tests if the modern UI system is properly loaded
 * and jQuery UI is completely replaced
 * 
 * @since 3.3.4
 */

// Check if we're in WordPress
if (!defined('ABSPATH')) {
    die('Direct access not allowed');
}

/**
 * Test if modern UI system is loaded
 */
function mp_test_modern_ui() {
    // Check if modern UI scripts are registered
    $scripts = wp_scripts();
    $modern_ui_scripts = array(
        'mp-flatpickr',
        'mp-sortablejs', 
        'mp-tippy',
        'mp-tippy-main',
        'mp-modern-ui'
    );
    
    $loaded_scripts = array();
    foreach ($modern_ui_scripts as $script) {
        if (isset($scripts->registered[$script])) {
            $loaded_scripts[] = $script;
        }
    }
    
    // Check if jQuery UI scripts are NOT loaded
    $jquery_ui_scripts = array(
        'jquery-ui-core',
        'jquery-ui-datepicker',
        'jquery-ui-tooltip',
        'jquery-ui-sortable',
        'jquery-ui-tabs'
    );
    
    $blocked_scripts = array();
    foreach ($jquery_ui_scripts as $script) {
        if (!isset($scripts->registered[$script]) || $scripts->registered[$script]->src === false) {
            $blocked_scripts[] = $script;
        }
    }
    
    return array(
        'modern_ui_loaded' => $loaded_scripts,
        'jquery_ui_blocked' => $blocked_scripts,
        'success' => count($loaded_scripts) >= 3 && count($blocked_scripts) >= 3
    );
}

/**
 * Display test results
 */
function mp_display_test_results() {
    if (!current_user_can('manage_options')) {
        return;
    }
    
    $test_results = mp_test_modern_ui();
    
    echo '<div class="notice notice-info"><p>';
    echo '<strong>MarketPress Modern UI System Test:</strong><br>';
    echo 'Modern UI Scripts Loaded: ' . count($test_results['modern_ui_loaded']) . '<br>';
    echo 'jQuery UI Scripts Blocked: ' . count($test_results['jquery_ui_blocked']) . '<br>';
    echo 'Test Result: ' . ($test_results['success'] ? 'PASS' : 'FAIL');
    echo '</p></div>';
}

// Run test on admin pages
add_action('admin_notices', 'mp_display_test_results');
