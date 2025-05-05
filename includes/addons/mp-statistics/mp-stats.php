<?php
/*
Plugin Name: MarketPress-Statistiken
Plugin URI: https://github.com/cp-psource/marketpress
Description: Zeigt MarketPress-Statistiken mithilfe von Chart.js an.
Version: 1.0.0
Author: DerN3rd
*/

load_plugin_textdomain('mp_st', false, basename(dirname(__FILE__)) . '/languages');

/* Plugin-Aktivierung */
register_activation_hook(__FILE__, 'mp_st_install');
function mp_st_install() {
    // Installationslogik (falls erforderlich)
}

/* Plugin-Deaktivierung */
register_deactivation_hook(__FILE__, 'mp_st_remove');
function mp_st_remove() {
    // Deinstallationslogik (falls erforderlich)
}

/* Admin-Menü hinzufügen */
add_action('admin_menu', 'mp_st_admin_menu');
function mp_st_admin_menu() {
    add_dashboard_page(
        __('Verkaufsstatistik', 'mp_st'),
        __('Shopstatistik', 'mp_st'),
        'manage_options',
        'mp_st',
        'mp_st_page',
    );
}

/* Skripte und Styles laden */
add_action('admin_enqueue_scripts', 'mp_st_enqueue_scripts');
function mp_st_enqueue_scripts($hook) {
    if ($hook !== 'dashboard_page_mp_st') {
        return;
    }

    // Chart.js laden
    wp_enqueue_script('chart-js', 'https://cdn.jsdelivr.net/npm/chart.js', [], null, true);

    // Eigenes JavaScript für die Statistiken
    wp_enqueue_script('mp-stats-js', plugins_url('mp-stats.js', __FILE__), ['jquery', 'chart-js'], null, true);

    // AJAX-URL an JavaScript übergeben
    wp_localize_script('mp-stats-js', 'mpStatsAjax', [
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce'    => wp_create_nonce('mp_stats_nonce'),
    ]);
}

/* AJAX-Endpunkt für Verkaufsdaten */
add_action('wp_ajax_mp_get_sales_data', 'mp_st_get_sales_data');
function mp_st_get_sales_data() {
    check_ajax_referer('mp_stats_nonce', 'nonce');

    global $wpdb;

    $period = isset($_POST['period']) ? sanitize_text_field($_POST['period']) : '3_months';
    $month1 = isset($_POST['month1']) ? sanitize_text_field($_POST['month1']) : null;
    $month2 = isset($_POST['month2']) ? sanitize_text_field($_POST['month2']) : null;

    $query = "SELECT DATE_FORMAT(post_date, '%%Y-%%m') AS month, SUM(meta_value) AS total
              FROM {$wpdb->posts} p
              JOIN {$wpdb->postmeta} m ON p.ID = m.post_id
              WHERE post_type = %s AND meta_key = %s";

    $params = ['mp_order', 'mp_order_total'];

    if ($period === 'this_month') {
        $query .= " AND MONTH(post_date) = MONTH(NOW()) AND YEAR(post_date) = YEAR(NOW())";
    } elseif ($period === '3_months') {
        $query .= " AND post_date >= DATE_SUB(NOW(), INTERVAL 3 MONTH)";
    } elseif ($period === 'year') {
        $query .= " AND YEAR(post_date) = YEAR(NOW())";
    } elseif ($period === 'custom' && $month1 && $month2) {
        $query .= " AND DATE_FORMAT(post_date, '%%Y-%%m') BETWEEN %s AND %s";
        $params[] = $month1;
        $params[] = $month2;
    }

    $query .= " GROUP BY month ORDER BY month ASC";

    $results = $wpdb->get_results($wpdb->prepare($query, $params));

    // Gesamtumsatz berechnen
    $total_query = "SELECT SUM(meta_value) AS total
                    FROM {$wpdb->posts} p
                    JOIN {$wpdb->postmeta} m ON p.ID = m.post_id
                    WHERE post_type = %s AND meta_key = %s";
    $total = $wpdb->get_var($wpdb->prepare($total_query, 'mp_order', 'mp_order_total'));

    // Fallback, falls $total null ist
    if ($total === null) {
        $total = 0;
    }

    wp_send_json([
        'data' => $results,
        'total' => $total,
    ]);
}

/* Admin-Seite für Statistiken */
function mp_st_page() {
    ?>
    <div class="wrap">
        <h1><?php _e('Shopstatistik', 'mp_st'); ?></h1>

        <!-- Filteroptionen -->
        <div id="mp-stats-filters">
            <label for="mp-stats-period"><?php _e('Zeitraum:', 'mp_st'); ?></label>
            <select id="mp-stats-period">
                <option value="this_month"><?php _e('Dieser Monat', 'mp_st'); ?></option>
                <option value="3_months"><?php _e('Letzte 3 Monate', 'mp_st'); ?></option>
                <option value="year"><?php _e('Dieses Jahr', 'mp_st'); ?></option>
                <option value="custom"><?php _e('Benutzerdefiniert', 'mp_st'); ?></option>
                <option value="total"><?php _e('Gesamt', 'mp_st'); ?></option>
            </select>

            <div id="mp-stats-custom-filters" style="display: none;">
                <label for="mp-stats-month1"><?php _e('Monat 1:', 'mp_st'); ?></label>
                <input type="month" id="mp-stats-month1">
                <label for="mp-stats-month2"><?php _e('Monat 2:', 'mp_st'); ?></label>
                <input type="month" id="mp-stats-month2">
            </div>

            <button id="mp-stats-apply-filters" class="button button-primary">
                <?php _e('Filter anwenden', 'mp_st'); ?>
            </button>
        </div>

        <!-- Diagramm -->
        <canvas id="salesChart" width="400" height="200"></canvas>

        <!-- Gesamtumsatz -->
        <div id="mp-stats-total" style="margin-top: 20px; font-size: 16px; font-weight: bold;">
            <?php _e('Gesamtumsatz: ', 'mp_st'); ?><span id="mp-stats-total-value">0</span> €
        </div>
    </div>
    <?php
}