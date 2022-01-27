<?php
	//  Set the local timezone for PHP 
	date_default_timezone_set('America/New_York');

	/* The Dashboard class object contains all of the configuration settings
	 * for the dashboard.  It also contains functions used to generate the
	 * schedule section of the page.
	 */
	require_once 'dashboardClass.php';
	$dashboard = new Dashboard();	
?>
<html>
<head>
	<meta http-equiv="refresh" content="<?php echo $dashboard->page_refresh_seconds; ?>; url=/dashboard" />
	
	<script type="text/javascript" src="dashboardScripts.js"></script>
	<script type="text/javascript" src="js/canvasjs/canvasjs.min.js"></script>
	<script type="text/javascript">
	<!--
		//  URL of the file that contains the weather data
		var weather_data_url = "weather_data.json";
		
		//  URL of the background image
		var background_image_url = "<?php echo $dashboard->randomImage(); ?>";
	-->
	</script>
	
	<link rel="stylesheet" href="basepage.css">
	<link rel="stylesheet" href="schedule.css">
	<link rel="stylesheet" href="weather.css">
</head>
<body>		
	<!--  B A C K G R O U N D   P I C T U R E  -->
	<div id="backgroundImage" class="abs-zero vignette photo"></div>
	<div class="abs-zero photo-overlay"></div>
	
	<div class="dashboard-container" id="dashboard-container">
		
		<!--  T I T L E   B A R   C L O C K  -->
		<div class="clock">
			<div class="progress-bar" id="progress-bar"></div>
			<div class="clock-time" id="clock-time">HH:MM:SS AM</div>
			<div class="clock-date" id="clock-date">Day, Month DD, YYYY</div>
		</div>	
		<script type="text/javascript">
		<!--
			updateProgressBarWidth(<?php echo $dashboard->page_refresh_seconds; ?>);
			updateTimeDate();
		-->
		</script>
		
		<!--  S C H E D U L E  -->
		<section>
			<div class="schedule" id="schedule">
				<?php $dashboard->generateSchedule(); ?>
			</div>
		</section>
		
		<!--  W E A T H E R  -->
		<section class="weather" id="weather">
			<!--  Humidity sensor data  -->
			<div class="humidity-section">
				<?php $dashboard->generateHumidityDisplay(); ?>
			</div>
			<!--  NOAA weather data  -->
			<div class="weather-widget">
				<div class="widget-container">
					<div class="container">
						<div class="current">;
							<img id="current-icon" width="200" height="200" src="" alt="weather icon">
							<div id="current-temp" class="current-temp"><strong>?&deg;</strong></div>
							<div id="current-summary"></div>
						</div>
						<div id="daily"></div>
					</div>
				</div>
			</div>
			<div id="tempAndPrecipChart" class="charts temp"></div>
			<div id="windChart" class="charts wind"></div>
			<div id="alerts" class="alerts_container"></div>
			<div><style="font-size: 1.5em; margin-left: 15px;">Weather Data by <a href="https://www.weather.gov/documentation/services-web-api#">NOAA</a> and <a href="https://developers.synopticdata.com/mesonet/">Synoptics Mesonet</a></style></div>
		</section>
	</div>
</body>
</html>
