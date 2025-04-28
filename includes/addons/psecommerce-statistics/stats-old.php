<?php
/*
Plugin Name: MarketPress-Statistiken
Plugin URI: https://n3rds.work
Description: Zeigt MarketPress-Statistiken mithilfe der GooGle-Diagrammbibliothek an (https://google-developers.appspot.com/chart/)
Version: 0.4.2
Author: DerN3rd
*/

/* Runs when plugin is activated */
register_activation_hook(__FILE__, 'mp_st_install'); 

/* Runs on plugin deactivation*/
register_deactivation_hook( __FILE__, 'mp_st_remove' );

function mp_st_install() {
} 

function mp_st_remove() {
}

add_action('admin_menu', 'mp_st_admin_menu');

function mp_st_admin_menu() {
  add_dashboard_page( __('Verkaufsstatistik', 'mp'), __('Shopstatistik', 'mp'), 'administrator', 'mp_st', 'mp_st_page', 'dashicons-analytics', 100.33 );
}

function mp_st_page() {
  if (!class_exists('MarketPress')) {
    return;
  }

  if (!current_user_can('manage_options')) {
    wp_die(__('Cheatin&#8217; uh?'));
  }

  global $wpdb, $mp;

  $totality = $wpdb->get_row("
    SELECT 
      count(p.ID) as 'count', 
      sum(m.meta_value) as 'total', 
      avg(m.meta_value) as average 
    FROM $wpdb->posts p 
    JOIN $wpdb->postmeta m 
    ON p.ID = m.post_id 
    WHERE p.post_type = 'mp_order' 
    AND m.meta_key = 'mp_order_total'
  ");  

  $totalitycount = !empty($totality->count) ? $totality->count : 0;
  $totalitytotal = !empty($totality->total) ? $totality->total : 0;
  $totalityaverage = !empty($totality->average) ? $totality->average : 0;

  $totalityitems = $wpdb->get_row("
    SELECT 
      count(p.ID) as 'count', 
      sum(m.meta_value) as 'total', 
      avg(m.meta_value) as average 
    FROM $wpdb->posts p 
    JOIN $wpdb->postmeta m 
    ON p.ID = m.post_id 
    WHERE p.post_type = 'mp_order' 
    AND m.meta_key = 'mp_order_items'
  ");  

  $totalityitemscount = !empty($totalityitems->count) ? $totalityitems->count : 0;
  $totalityitemstotal = !empty($totalityitems->total) ? $totalityitems->total : 0;
  $totalityitemsaverage = !empty($totalityitems->average) ? $totalityitems->average : 0;

  function mp_st_stat($time = '-0 days', $stat = 'count', $echo = true) {
    global $wpdb, $mp;
    $year = date('Y', strtotime($time));
    $month = date('m', strtotime($time));

    $monthquery = $wpdb->get_row("
      SELECT 
        count(p.ID) as 'count', 
        sum(m.meta_value) as 'total', 
        avg(m.meta_value) as average 
      FROM {$wpdb->posts} p 
      JOIN {$wpdb->postmeta} m 
      ON p.ID = m.post_id 
      WHERE p.post_type = 'mp_order' 
      AND m.meta_key = 'mp_order_total' 
      AND YEAR(p.post_date) = $year 
      AND MONTH(p.post_date) = $month
    ");

    $monthstat = 0;
    if (!empty($monthquery->$stat)) $monthstat = $monthquery->$stat;

    if ($echo) echo $monthstat; 
    else return $monthstat; 
  }

  function mp_st_stat_items( $time = '-0 days' , $stat = 'count', $echo = true ){
    global $wpdb, $mp;
    $year = date('Y', strtotime($time));
    $month = date('m', strtotime($time));

    $monthquery = $wpdb->get_row($wpdb->prepare("SELECT count(p.ID) as 'count', sum(m.meta_value) as 'total', avg(m.meta_value) as average FROM $wpdb->posts p JOIN $wpdb->postmeta m ON p.ID = m.post_id WHERE p.post_type = 'mp_order' AND m.meta_key = 'mp_order_items' AND YEAR(p.post_date) = %d AND MONTH(p.post_date) = %d", $year, $month));  
    $monthstat = 0;
    if (!empty($monthquery->$stat)) $monthstat = $monthquery->$stat;

    if ($echo) echo $monthstat; 
    else return $monthstat; 
  }

  echo '<script type="text/javascript" src="' . plugins_url( 'bigtext.js' , __FILE__ ) . '" ></script>';

  ?>
  <div class="wrap" style="background: #fff;">
  <table style="width: 100%;">
    <tr>
      <td>
      <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
        <script type="text/javascript">
          google.charts.load("current", {packages:["corechart"]});
          google.charts.setOnLoadCallback(drawChart);
          function drawChart() {
            const months = 12;
            const data = google.visualization.arrayToDataTable([
              ['Monat', 'Gesamt'],
              ...Array.from({length: months}, (_, i) => {
                const date = new Date();
                date.setMonth(date.getMonth() - i);
                return [
                  date.toLocaleString(navigator.language, {month: "short"}),
                  mp_st_stat(`-${i} months`, 'total')
                ];
              })
            ]);
            const options = {
              title: 'Gesamtumsatz, 12 Monate',
              colors: ['#4285F4', '#DB4437'],
              theme: {legend: {position: 'in'}, axisTitlesPosition: 'in'},
              hAxis: {title: 'Jahr', titleTextStyle: {color: '#999999'}},
              seriesType: "bars",
              series: {1: {type: "line"}}
            };
            const chart = new google.visualization.ComboChart(document.getElementById('total_chart'));
            chart.draw(data, options);
          }
        </script>
        <div id="total_chart" style="width: 100%; height: 350px;"></div>

        <script type="text/javascript">
          google.load("visualization", "1", { packages: ["corechart"] });
          google.setOnLoadCallback(drawChart);
          function drawChart() {
            const monthColumnLabel = 'Monat';
            const averageColumnLabel = 'Durchschnittlich';
            const data = [];
            data.push([monthColumnLabel, averageColumnLabel]);

            for (let i = 0; i < 12; i++) {
              const date = new Date();
              date.setMonth(date.getMonth() - (12 - i));
              const month = date.toLocaleString(navigator.language, { month: "short" });
              const average = mp_st_stat(`-${12 - i} months`, "average");
              data.push([month, average]);
            }

            const options = {
              title: 'Durchschnitt pro Verkauf, 12 Monate',
              colors: ["#000000", "#D44413"],
              theme: { legend: { position: "in" }, axisTitlesPosition: "in" },
              hAxis: { title: "Jahr", titleTextStyle: { color: "#999999" } },
              seriesType: "bars",
              series: { 1: { type: "line" } }
            };

            const chart = new google.visualization.ComboChart(
              document.getElementById("average_chart")
            );
            chart.draw(data, options);
          }
        </script>
        <div id="average_chart" style="width: 100%; height: 250px;"></div>

        <script type="text/javascript" src="https://www.google.com/jsapi"></script>
        <script type="text/javascript">
          google.load("visualization", "1", {packages:["corechart"]});
          google.setOnLoadCallback(drawChart);

          function drawChart() {
            var months = 12;
            var data = [['Monat', 'Gesamt']];
            data = data.concat(generateData(months));
            var options = {
              title: 'Anzahl der Produktverkäufe, 12 Monate',
              colors: ['#000000', '#D44413'],
              theme: {legend: {position: 'in'}, axisTitlesPosition: 'in'},
              hAxis: {title: 'Jahr', titleTextStyle: {color: '#999999'}},
              seriesType: "line",
              // curveType: "function",
              series: {1: {type: "line"}}
            };
            var chart = new google.visualization.ComboChart(document.getElementById('totalChartItems'));
            chart.draw(data, options);
          }

          function generateData(months) {
            var generatedData = [];
            for (var i = months; i >= 0; i--) {
              generatedData.push([
                date("M", strtotime("-" + i + " Months")),
                mp_st_stat_items('-' + i + ' months', 'total')
              ]);
            }
            return generatedData;
          }
        </script>
        <div id="totalChartItems" style="width: 48%; height: 350px; display: inline-block;"></div>

        <script type="text/javascript">
          google.load("visualization", "1", { packages: ["corechart"] });
          google.setOnLoadCallback(drawChart);
          function drawChart() {
            var months = 12;
            var data = [["Month", "Average"]];

            for (var i = months - 1; i >= 0; i--) {
              var date = new Date();
              date.setMonth(date.getMonth() - i);
              var month = date.toLocaleString("default", { month: "short" });
              var mpValue = <?php mp_st_stat_items("-${i} months", "average"); ?>;
              data.push([month, mpValue]);
            }

            var options = {
              title: "Average number of product sales, 12 months",
              colors: ["#000000", "#D44413"],
              theme: { legend: { position: "in" }, axisTitlesPosition: "in" },
              hAxis: { title: "Year", titleTextStyle: { color: "#999999" } },
              seriesType: "line",
              series: { 1: { type: "line" } }
            };
            var chart = new google.visualization.ComboChart(
              document.getElementById("average_chart_items")
            );
            chart.draw(data, options);
          }
        </script>
        <div id="average_chart_items" style="width: 48%; height: 350px; display: inline-block;"></div>
  <?php
  $custom_query = new WP_Query(array(
    'post_type' => 'product',
    'post_status' => 'publish',
    'meta_key' => 'mp_sales_count',
    'meta_compare' => '>',
    'meta_value' => 0,
    'orderby' => 'meta_value',
    'order' => 'DESC'
  ));
  
  if ($custom_query->have_posts()) {
    $data = array();
    while ($custom_query->have_posts()) {
      $custom_query->the_post();
      $data[] = mp_st_sales_by_price(false, get_the_ID());
    }
    wp_reset_postdata();
    if ($echo) {
      echo json_encode($data);
    } else {
      return json_encode($data);
    }
  }
}

  function mp_st_sales_by_price( $echo = true, $post_id = NULL, $label = true ) {
    global $id, $mp;
    $post_id = ( NULL === $post_id ) ? $id : $post_id;

	  $meta = get_post_custom($post_id);
    //unserialize
    foreach ($meta as $key => $val) {
	  $meta[$key] = maybe_unserialize($val[0]);
	  if (!is_array($meta[$key]) && $key != "mp_is_sale" && $key != "mp_track_inventory" && $key != "mp_product_link" && $key != "mp_file" && $key != "mp_price_sort")
	    $meta[$key] = array($meta[$key]);
	}
    if ((is_array($meta["mp_price"]) && count($meta["mp_price"]) >= 1) || !empty($meta["mp_file"])) {
      if ($meta["mp_is_sale"]) {
	    $price .= $meta["mp_sale_price"][0];
	  } else {
	    $price = $meta["mp_price"][0];
	  }
	} else {
		return '';
	}

    $sales = $meta["mp_sales_count"][0];
    $stats = $price . ', ' . $sales;
    if ($echo)
      echo $stats;
    else
      return $stats;
  } ?>

    <script type="text/javascript">
      google.load("visualization", "1", {packages:["corechart"]});
      google.setOnLoadCallback(drawChart);
      function drawChart() {
        var data = google.visualization.arrayToDataTable([
          ['<?php _e('Preis', 'mp'); ?>', '<?php _e('Umsatz', 'mp'); ?>'],
          <?php mp_st_popular_products_sales_price_all(); ?>
          ]);

        var options = {
          title: '<?php _e('Umsatz nach Produkt Preis', 'mp'); ?>',
          hAxis: {title: '<?php _e('Preis', 'mp'); ?>'},
          vAxis: {title: '<?php _e('Umsatz', 'mp'); ?>'},
          pointSize: '9',
          colors: ['#000000'],
          legend: 'none'
        };

        var chart = new google.visualization.ScatterChart(document.getElementById('sales_per_price'));
        chart.draw(data, options);
      }
    </script>
    <div id="sales_per_price" style="width: 47%; height: 300px; display: inline-block;"></div>

  <?php
  function mp_st_products_income_price_all( $echo = true ) {
    global $mp;
    //The Query
    $custom_query = new WP_Query('post_type=product&post_status=publish&meta_key=mp_sales_count&meta_compare=>&meta_value=0&orderby=meta_value&order=DESC');
    if (count($custom_query->posts)) {
      foreach ($custom_query->posts as $post) {
        echo "[" . mp_st_income_by_price(false, $post->ID) . "], ";
      ;}
    }
  }

  function mp_st_income_by_price( $echo = true, $post_id = NULL, $label = true ) {
    global $id, $mp;
    $post_id = ( NULL === $post_id ) ? $id : $post_id;

	$meta = get_post_custom($post_id);
    //unserialize
    foreach ($meta as $key => $val) {
	  $meta[$key] = maybe_unserialize($val[0]);
	  if (!is_array($meta[$key]) && $key != "mp_is_sale" && $key != "mp_track_inventory" && $key != "mp_product_link" && $key != "mp_file" && $key != "mp_price_sort")
	    $meta[$key] = array($meta[$key]);
	}
    if ((is_array($meta["mp_price"]) && count($meta["mp_price"]) >= 1) || !empty($meta["mp_file"])) {
      if ($meta["mp_is_sale"]) {
	    $price .= $meta["mp_sale_price"][0];
	  } else {
	    $price = $meta["mp_price"][0];
	  }
	  } else {
		return '';
	  }

    $sales = $meta["mp_sales_count"][0];
    $revenue = $sales*$price;
    $stats = $price . ', ' . $revenue;
    if ($echo)
      echo $stats;
    else
      return $stats;
  } ?>

    <script type="text/javascript">
      google.load("visualization", "1", {packages:["corechart"]});
      google.setOnLoadCallback(drawChart);
      function drawChart() {
        var data = google.visualization.arrayToDataTable([
          ['<?php _e('Preis', 'mp'); ?>', '<?php _e('Umsatz', 'mp'); ?>'],
          <?php mp_st_products_income_price_all(); ?>
          ]);

        var options = {
          title: '<?php _e('Umsatz nach Produktpreis', 'mp'); ?>',
          hAxis: {title: '<?php _e('Preis', 'mp'); ?>'},
          vAxis: {title: '<?php _e('Einnahmen', 'mp'); ?>'},
          colors: ['#D44413'],
          pointSize: '9',
          legend: 'none'
        };

        var chart = new google.visualization.ScatterChart(document.getElementById('income_price'));
        chart.draw(data, options);
      }
    </script>
  </head>
  <body>
    <div id="income_price" style="width: 48%; height: 300px; display: inline-block;"></div>

        <script type="text/javascript">
        google.load("visualization", "1", {packages:["corechart"]});
        google.setOnLoadCallback(drawChart);
        function drawChart() {
          var data = google.visualization.arrayToDataTable([
            ['<?php _e('Monat', 'mp'); ?>', '<?php _e('Umsatz', 'mp'); ?>'],
            ['<?php echo date("M",strtotime("-12 Months")) ?>', <?php mp_st_stat('-12 months', 'count'); ?>],
            ['<?php echo date("M",strtotime("-11 Months")) ?>', <?php mp_st_stat('-11 months', 'count'); ?>],
            ['<?php echo date("M",strtotime("-10 Months")) ?>', <?php mp_st_stat('-10 months', 'count'); ?>],
            ['<?php echo date("M",strtotime("-9 Months")) ?>', <?php mp_st_stat('-9 months', 'count'); ?>],
            ['<?php echo date("M",strtotime("-8 Months")) ?>', <?php mp_st_stat('-8 months', 'count'); ?>],
            ['<?php echo date("M",strtotime("-7 Months")) ?>', <?php mp_st_stat('-7 months', 'count'); ?>],
            ['<?php echo date("M",strtotime("-6 Months")) ?>', <?php mp_st_stat('-6 months', 'count'); ?>],
            ['<?php echo date("M",strtotime("-5 Months")) ?>', <?php mp_st_stat('-5 months', 'count'); ?>],
            ['<?php echo date("M",strtotime("-4 Months")) ?>', <?php mp_st_stat('-4 months', 'count'); ?>],
            ['<?php echo date("M",strtotime("-3 Months")) ?>', <?php mp_st_stat('-3 months', 'count'); ?>],
            ['<?php echo date("M",strtotime("-2 Months")) ?>', <?php mp_st_stat('-2 months', 'count'); ?>],
            ['<?php echo date("M",strtotime("-1 Months")) ?>', <?php mp_st_stat('-1 months', 'count'); ?>],
            ['<?php echo date("M",strtotime("-0 Months")) ?>', <?php mp_st_stat('-0 months', 'count'); ?>],
          ]);
          var options = {
            title: '<?php _e('Anzahl der Verkäufe, 12 Monate', 'mp'); ?>',
            colors: ['#000000'],
            theme: {legend: {position: 'in'}, titlePosition: 'in', axisTitlesPosition: 'in'},
            hAxis: {title: '<?php _e('Jahr', 'mp'); ?>', titleTextStyle: {color: '#999999'}}
          };
          var chart = new google.visualization.LineChart(document.getElementById('count_chart'));
          chart.draw(data, options);
        }
        </script>
        <div id="count_chart" style="width: 100%; height: 200px;"></div>
        </div>
      </td>
      <td style="width: 300px; vertical-align: top; text-align: center; color: #222;">
      	<div id="BigText" style="width: 300px; padding: 20px;">
      		<p><?php _e("Einnahmen dieses Monats:", "mp_st"); ?></p>
      		<p><strong><?php echo mp_format_currency('', mp_st_stat('-0 months', 'total', false)); ?></strong></p>
      		<p style="border-top: 1px solid #dedede;"><?php _e("Die Verkäufe dieses Monats:", "mp_st"); ?></p>
      		<p><strong><?php echo mp_st_stat('-0 months', 'count', false); ?> <?php _e('Verkäufe', 'mp'); ?>, <?php echo mp_st_stat_items('-0 months', 'total', false); ?> <?php _e('Artikel', 'mp'); ?></strong></p>
      		<p>(<?php _e('Durchschnitt von', 'mp'); ?> <?php echo number_format(mp_st_stat_items('-0 months', 'average', false), 2, '.', ''); ?> <?php _e('Artikel pro Verkauf', 'mp'); ?>)</p>
            <p style="border-top: 1px solid #dedede;"><?php _e("Durchschnitt dieses Monats:", "mp_st"); ?></p>
            <p><strong><?php echo mp_format_currency('', mp_st_stat('-0 months', 'average', false)); ?>/<?php _e('Umsatz', 'mp'); ?></strong></p>

      		<p style="border-top: 2px solid #333;"><?php _e('Gesamtumsatz:', 'mp'); ?></p>
      		<p><strong><?php echo mp_format_currency('', $totalitytotal); ?></strong></p>
      		<p style="border-top: 1px solid #dedede;"><?php _e('Gesamtumsatz:', 'mp'); ?></p>
      		<p><strong><?php echo $totalitycount; ?> <?php _e('Verkäufe', 'mp'); ?>, <?php echo $totalityitemstotal; ?> <?php _e('Artikel', 'mp'); ?></strong></p>
      		<p>(<?php _e('Durchschnitt von', 'mp'); ?> <?php echo number_format($totalityitemsaverage, 2, '.', ''); ?> <?php _e('Artikel pro Bestellung', 'mp'); ?>)</p>
            <p style="border-top: 1px solid #dedede;"><?php _e('Gesamtdurchschnitt/Verkauf:', 'mp'); ?></p>
            <p><strong><?php echo mp_format_currency('', $totalityaverage); ?></strong></p>      	</div>
      </td>
    </tr>
  </table>
  <?php
  function mp_st_popular_products_sales( $echo = true, $num = 10 ) {
    global $mp;
    //The Query
    $custom_query = new WP_Query('post_type=product&post_status=publish&posts_per_page='.intval($num).'&meta_key=mp_sales_count&meta_compare=>&meta_value=0&orderby=meta_value&order=DESC');
    if (count($custom_query->posts)) {
      foreach ($custom_query->posts as $post) {
        echo "['" . $post->post_title . "', " . mp_st_product_sales(false, $post->ID) . "], ";
      ;}
    }
  }

  function get_popular_products_revenue( $echo = true ) {
    global $post;
    $args = array(
        'post_type' => 'product',
        'post_status' => 'publish',
        'meta_key' => 'mp_sales_count',
        'meta_compare' => '>',
        'meta_value' => 0,
        'orderby' => 'meta_value',
        'order' => 'DESC',
    );

    $products = new WP_Query( $args );
    if ( $products->have_posts() ) {
        while ( $products->have_posts() ) {
            $products->the_post();
            $title = get_the_title();
            $revenue = mp_st_product_revenue( false, $post->ID );
            if ( $echo ) {
                echo "['$title', $revenue], ";
            } else {
                return "['$title', $revenue]";
            }
        }
        wp_reset_postdata();
    }
  }

  function mp_st_popular_products_revenue_table( $echo = true, $num = 10 ) {
    global $mp;
    //The Query
    $custom_query = new WP_Query('post_type=product&post_status=publish&posts_per_page='.intval($num).'&meta_key=mp_sales_count&meta_compare=>&meta_value=0&orderby=meta_value&order=DESC');
    if (count($custom_query->posts)) {
      foreach ($custom_query->posts as $post) {
        echo "['" . $post->post_title . "', {v:" . mp_st_product_revenue(false, $post->ID) . ", f:'" . mp_st_product_revenue(false, $post->ID) . "'}, {v:" . mp_st_product_sales(false, $post->ID) . ", f:'" . mp_st_product_sales(false, $post->ID) . "'}], ";
      }
    }
  }

  function mp_st_product_revenue( $echo = true, $post_id = NULL, $label = true ) {
    global $id, $mp;
    $post_id = ( NULL === $post_id ) ? $id : $post_id;

    $meta = get_post_custom($post_id);
      //unserialize
      foreach ($meta as $key => $val) {
      $meta[$key] = maybe_unserialize($val[0]);
      if (!is_array($meta[$key]) && $key != "mp_is_sale" && $key != "mp_track_inventory" && $key != "mp_product_link" && $key != "mp_file" && $key != "mp_price_sort")
        $meta[$key] = array($meta[$key]);
    }
      if ((is_array($meta["mp_price"]) && count($meta["mp_price"]) >= 1) || !empty($meta["mp_file"])) {
        if ($meta["mp_is_sale"]) {
        $price .= $meta["mp_sale_price"][0];
      } else {
        $price = $meta["mp_price"][0];
      }
    } else {
      return '';
    }

    $sales = $meta["mp_sales_count"][0];
    $revenue = $sales*$price;
    if ($echo)
      echo $revenue;
    else
    return $revenue;
  }

  function mp_st_product_sales( $echo = true, $post_id = null ) {
    global $id, $mp;
    $post_id = $post_id ?? $id;
    $meta = get_post_custom($post_id);
    $sales = (int) $meta["mp_sales_count"][0];

    if ($echo) {
        echo $sales;
    } else {
        return $sales;
    }
  }
  function mp_st_users() {
    global $wpdb;
    $order = 'postcount';
    $limit = '10';
    $usersinfo = $wpdb->get_results("SELECT $wpdb->users.ID as ID, COUNT(post_author) as postcount FROM $wpdb->users LEFT JOIN $wpdb->posts ON $wpdb->users.ID = $wpdb->posts.post_author WHERE post_type = 'mp_order' GROUP BY post_author ORDER BY $order DESC LIMIT $limit");
    foreach($usersinfo as $userinfo){
      $user = get_userdata($userinfo->ID);
      $user->postcount = $userinfo->postcount;
      echo "['";
      echo $user->mp_shipping_info['name'];
      echo "', '";
      echo $user->mp_shipping_info['city'];
      echo "', '";
      echo $user->mp_shipping_info['country'];
      echo "', '";
      echo $user->mp_shipping_info['phone'];
      echo "', '";
      echo $user->mp_shipping_info['email'];
      echo "', {v:";
      echo $user->postcount;
      echo ", f:'";
      echo $user->postcount;
      echo "'}], ";
    }
  }

  ?>
  <script type="text/javascript">
  google.load("visualization", "1", {packages:["corechart"]});
  google.setOnLoadCallback(drawChart);
  function drawChart() {
    var data = google.visualization.arrayToDataTable([
      ['<?php _e('Produkt', 'mp'); ?>', '<?php _e('Verkäufe', 'mp'); ?>'],
      <?php mp_st_popular_products_sales(); ?>
    ]);
    var options = {
      title: '<?php _e('Top-Produkte nach Anzahl der Verkäufe', 'mp'); ?>',
      is3D: true,
    };
    var chart = new google.visualization.PieChart(document.getElementById('top_products_pie'));
    chart.draw(data, options);
  }
  </script>
  <div id="top_products_pie" style="width: 45%; height: 500px; display: inline-block;"></div>

  <script type="text/javascript">
  google.load("visualization", "1", {packages:["corechart"]});
  google.setOnLoadCallback(drawChart);
  function drawChart() {
    var data = google.visualization.arrayToDataTable([
      ['<?php echo esc_js( __( 'Produkt', 'mp' ) ); ?>', '<?php echo esc_js( __( 'Einnahmen', 'mp' ) ); ?>'],

      <?php mp_st_popular_products_revenue(); ?>
    ]);
    var options = {
      title: '<?php echo esc_js( __( 'Top-Produkte Umsatz', 'mp' ) ); ?>',
    };
    var chart = new google.visualization.PieChart(document.getElementById('top_products_revenue'));
    chart.draw(data, options);
  }
  </script>
  <div id="top_products_revenue" style="width: 50%; height: 500px; display: inline-block;"></div>

  <script type="text/javascript">
  google.load("visualization", "1", {packages:["table"]});
  google.setOnLoadCallback(drawTable);
  function drawTable() {
    var data = new google.visualization.DataTable();
    data.addColumn("string", "<?php _e('Produktname', 'mp'); ?>");
    data.addColumn("number", "<?php _e('Gesamtumsatz', 'mp'); ?>");
    data.addColumn("number", "<?php _e('Produktverkäufe', 'mp'); ?>");
    data.addRows([
      <?php mp_st_popular_products_revenue_table(); ?>
    ]);
    var table = new google.visualization.Table(document.getElementById("top_products_table"));
    table.draw(data, {showRowNumber: true});
  }
  </script>
  <div id="top_products_table" style="width: 100%; height: 500px; display: block;"></div>

  <script type="text/javascript">
  google.load('visualization', '1', {packages: ['table']});
  google.setOnLoadCallback(drawTable);
  
  function drawTable() {
    var data = new google.visualization.DataTable();
    data.addColumn('string', '<?php _e('Kundenname', 'mp'); ?>');
    data.addColumn('string', '<?php _e('Stadt', 'mp'); ?>');
    data.addColumn('string', '<?php _e('Land', 'mp'); ?>');
    data.addColumn('string', '<?php _e('Telefon', 'mp'); ?>');
    data.addColumn('string', '<?php _e('Email', 'mp'); ?>');
    data.addColumn('number', '<?php _e('Bestellungen insgesamt', 'mp'); ?>');
    
    data.addRows(<?php mp_st_users(); ?>);
    var options = {};
    var table = new google.visualization.Table(document.getElementById('top_users_table'));
    table.draw(data, { showRowNumber: true });
  }
  </script>
  <div id="top_users_table" style="width: 100%; height: 500px; display: block;"></div>
  </div>
  <style>
  #bigTextWrapper p strong{
    text-shadow: 2px 2px 2px #ccc;
    filter: dropshadow(color=#ccc, offx=2, offy=2);
  }
  </style>
  <script>
    (function ($) {
      $(document).ready(function() {
        $('#bigTextWrapper').bigtext({
          childSelector: '> p',
          maxfontsize: 110
        });
      });
    })(jQuery);
  </script>
<?php 