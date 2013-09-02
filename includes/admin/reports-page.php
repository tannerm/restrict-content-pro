<?php

function rcp_reports_page() {
	global $rcp_options, $rcp_db_name, $wpdb;
	if(isset($_GET['hide_free'])) $hide_free = $_GET['hide_free']; else $hide_free = NULL;
	ob_start(); ?>
    <script type="text/javascript">
	    google.load("visualization", "1", {packages:["corechart"]});
		// sales chart
	    google.setOnLoadCallback(drawSalesChart);
	    function drawSalesChart() {
	        var data = new google.visualization.DataTable();
	        data.addColumn('string', '<?php _e("Month", "edd"); ?>');
	        data.addColumn('number', '<?php _e("Earnings", "edd"); ?>');
	        data.addRows([
				<?php
				$i = 1;
				while($i <= 12) :?>
					['<?php echo rcp_month_num_to_name($i) . ' ' . date("Y"); ?>',
					<?php echo rcp_get_earnings_by_date(null, $i, date('Y') ); ?>,
					],
					<?php $i++;
				endwhile;
				?>
	        ]);

	        var options = {
	          	title: "<?php _e('Earnings per month', 'rcpg'); ?>",
				colors:['#a3bcd3'],
				fontSize: 12,
				backgroundColor: '#ffffff'
	        };

	        var chart = new google.visualization.ColumnChart(document.getElementById('earnings_chart_div'));
	        chart.draw(data, options);
	    }
    </script>
	<div id="earnings_chart_div"></div>

	<!--earnings per day -->
	<script type="text/javascript">
	    google.load("visualization", "1", {packages:["corechart"]});
		// sales chart
	    google.setOnLoadCallback(drawSalesChart);
	    function drawSalesChart() {
	        var data = new google.visualization.DataTable();
	        data.addColumn('string', '<?php _e("Day", "edd"); ?>');
	        data.addColumn('number', '<?php _e("Earnings", "edd"); ?>');
	        data.addRows([
				<?php
				$day = date('d');
				$month = date('n');
				$year = date('Y');
				$num_of_days = cal_days_in_month(CAL_GREGORIAN, $month, $year );
				$i = 1;
				while($i <= $num_of_days) :?>
					['<?php echo date("d", mktime(0, 0, 0, $month, $i, $year)); ?>',
					<?php echo rcp_get_earnings_by_date($i, $month, $year ); ?>,
					],
					<?php $i++;
				endwhile;
				?>
	        ]);

	        var options = {
	          	title: "<?php _e('Earnings per day', 'rcpg'); ?>",
				colors:['#a3bcd3'],
				fontSize: 12,
				backgroundColor: '#ffffff'
	        };

	        var chart = new google.visualization.ColumnChart(document.getElementById('earnings_per_day_chart_div'));
	        chart.draw(data, options);
	    }
    </script>
	<div id="earnings_per_day_chart_div"></div>

	<!--signups per day graph-->
	<script type="text/javascript">
	    google.load("visualization", "1", {packages:["corechart"]});
		// sales chart
	    google.setOnLoadCallback(drawSalesChart);
	    function drawSalesChart() {
	        var data = new google.visualization.DataTable();
	        data.addColumn('string', '<?php _e("Day of the month", "rcpg"); ?>');
	        data.addColumn('number', '<?php _e("Signups", "rcpg"); ?>');
	        data.addRows([
				<?php

				$year = date('Y');
				$month = date('n');
				$day = 1;
				$days_in_this_month = cal_days_in_month(CAL_GREGORIAN, $month, $year);
				while($day <= $days_in_this_month) {
					$users = $wpdb->get_results( $wpdb->prepare("SELECT ID FROM $wpdb->users WHERE (%d = MONTH ( user_registered ) AND %d = YEAR ( user_registered ) AND %d = DAY ( user_registered ))", $month, $year, $day ) );?>
					['<?php echo date( "jS", strtotime( $month . "/" . $day . "/" . $year ) ); ?>',
						<?php echo count($users); ?>,
					],
					<?php
					$day++;
				}
				?>
	        ]);

	        var options = {
	          	title: "<?php _e( 'Signups per day (for current month)', 'rcpg' ); ?>",
				colors:['#a3bcd3'],
				fontSize: 12,
				backgroundColor: '#ffffff'
	        };

	        var chart = new google.visualization.ColumnChart(document.getElementById('signups_per_day'));
	        chart.draw(data, options);
	    }
    </script>
	<div id="signups_per_day"></div>

	<script type="text/javascript">
	    google.load("visualization", "1", {packages:["corechart"]});
		// sales chart
	    google.setOnLoadCallback(drawSalesChart);
	    function drawSalesChart() {
	        var data = new google.visualization.DataTable();
	        data.addColumn('string', '<?php _e("Subscription Level", "rcpg"); ?>');
	        data.addColumn('number', '<?php _e("Users", "rcpg"); ?>');
	        data.addRows([
				<?php
				if(isset($_GET['hide_free']) && $_GET['hide_free'] == 'yes') {
					$levels = $wpdb->get_results("SELECT * FROM " . $rcp_db_name . " WHERE `price` > 0 OR `duration` > 0 ORDER BY list_order;");
				} else {
					$levels = $wpdb->get_results("SELECT * FROM " . $rcp_db_name . " ORDER BY list_order;");
				}
				$i = 1;
				if($levels) :
					foreach($levels as $level) : ?>
						['<?php echo rcp_get_subscription_name($level->id); ?>',
						<?php if($level->price > 0 || $level->duration > 0) {
							echo rcp_count_members($level->id, 'active');
						} else {
							echo rcp_count_members($level->id, 'free');
						} ?>,
						],
						<?php
					endforeach;
				endif;

				?>
	        ]);

	        var options = {
	          	title: "<?php _e('Members per subscription level', 'rcpg'); ?>",
				colors:['#a3bcd3'],
				fontSize: 12,
				backgroundColor: '#ffffff'
	        };

	        var chart = new google.visualization.ColumnChart(document.getElementById('members_chart_div'));
	        chart.draw(data, options);
	    }
    </script>
	<div id="members_chart_div"></div>
	<form id="user-type-filter" action="" method="get" style="float: right; margin: 0 30px 0 0;">
		<input type="hidden" name="page" value="rcp-graphs"/>
		<label for="rcp_hide_free"><?php _e('Hide Free Subscriptions', 'rcp'); ?></label>
		<select id="rcp_hide_free" name="hide_free">
			<option value="no" <?php selected($hide_free, ''); ?>><?php _e('No', 'rcpg'); ?></option>
			<option value="yes" <?php selected($hide_free, 'yes'); ?>><?php _e('Yes', 'rcpg'); ?></option>
		</select>
		<input type="submit" class="button-secondary" value="<?php _e('Filter', 'rcpg'); ?>"/>
	</form>

	<?php
	echo ob_get_clean();
}