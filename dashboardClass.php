<?php

	// Load johngrogg/ics-parser package
	require_once 'vendor/autoload.php';
	use ICal\ICal;
	
	class Dashboard {
				
		/*
		 *  Reload the page every 30 minutes
		 */
		public $page_refresh_seconds = 1800;
	
		/* 
		 * Generate the HTML for the humidity sensor reading
		 */
		public function generateHumidityDisplay() {
			$humidity_json_file = 'humidity.json';
			$string = file_get_contents($humidity_json_file);
			$sensor_data = json_decode($string, true);
			
			echo "<div class=\"humidity-data\">Humidity Sensor: " . round($sensor_data['humidity']) . "% (" . round($sensor_data['temperature']) . "&deg;)</div>";
			if (time() - filemtime($humidity_json_file) > 3600) {
				echo "\t\t\t\t<div class=\"sensor-time\">\n";
				echo "\t\t\t\t\t" . date("m/d/y g:ia", filemtime($humidity_json_file)) . "\n"; 
				echo "\t\t\t\t</div>\n";
			}
		}
		
		/*
		 * Generate the HTML for the schedule
		 */
		public function generateSchedule() {

			//  Defines the $sources array that lists the calendar URLs we will collect
			//  calendar data from.  This information is kept in a seperate file so that
			//  it can be protected.  Some of these URLs are only secured through 
			//  obfustication.
			include 'calendarSources.php';
			
			//  Prepare an empty days array to hold all of the calendar events.
			//  It has the start and end times for each day that we'll use to
			//  collect all of the events for one day from the various sources
			//  together.
			$days = [];
			for ($i = 0; $i < 365; $i++) {
				$today = 
				$days[$i] = array (
					'start' => strtotime("midnight +" . $i . " day"),
					'end' => strtotime("midnight +" . ($i + 1) . " day") - 1,
					'events' => array()
				);
			}

			//  Collect all the events from all the sources and put them into
			//  the days array
			$element_ctr = 0;
			foreach ($sources as $source) {
				try {
					$ical = new Ical();
					$ical->initUrl($source['url'], $username = null, $password = null, $userAgent = null);

					// print_r($ical);
					if ($ical->eventCount > 0) {
						foreach ($ical->cal['VEVENT'] as $event) {
							if (strtotime($event['DTEND_array'][1]) >= strtotime("midnight +0 day")) {
								// print_r($event);
								for ($i = 0; $i < 365; $i++) {
									if (strtotime($event['DTSTART_array'][1]) > $days[$i]['end']) {
										continue;
									} 
									if (strtotime($event['DTEND_array'][1]) <= $days[$i]['start']) {
										continue;
									}
									$new_event = array(
										'start' => strtotime($event['DTSTART_array'][1]),
										'end' => strtotime($event['DTEND_array'][1]),
										'summary' => str_replace('\\', '', str_replace('\n', ', ', $event['SUMMARY'])),
										'color' => $source['color'],
										'num-days' => 1 + (new DateTime(date('Y-m-d', strtotime($event['DTEND_array'][1]) - 1)))->diff(new DateTime(date('Y-m-d', strtotime($event['DTSTART_array'][1]))))->format('%a')
									);
									if (isset($event['LOCATION'])) {
										$new_event['location'] = "at " .  str_replace('\\', '', str_replace('\n', ', ', $event['LOCATION']));
									}
									//  Check for duplicates before adding new event to the list
									if (in_array($new_event, $days[$i]['events'], TRUE) === FALSE) {
										$days[$i]['events'][] = $new_event;
									}
								}
							}
						}
					}
					$element_ctr = $element_ctr + 1;
				} catch (\Exception $e) {
					//  If there's an error fetching a feed then drop it out of
					//  the array so that it doesn't show up in the legend.
					//  Then carry on trying the remainder of the sources.
					array_splice($sources, $element_ctr, 1);
				}
			}
			
			//  Generate the HTML for the legend
			echo "<div class=\"legend\"><small>LEGEND</small>";
			foreach ($sources as $source) {
				
				echo "<span class=\"source-bullet\" style=\"background:" . $source['color'] . "\"></span>"  . $source['label'];
			}
			echo "</div>\n";
			
			//  Generate the HTML for each day's events
			$max_days = 14;
			$day_ctr = 0;
			foreach($days as $day) {
				if (count($day['events']) > 0) {
					
					// Sort the day's events by their start times
					if (count($day['events']) > 1) {
						// usort($day['events'], 'cmp');
						usort($day['events'], function($x, $y) { return $x['start'] - $y['start']; } );
					}
				
					echo "\t\t\t\t<div class=\"day-container\">\n";
					echo "\t\t\t\t\t<div class=\"day\">\n";
					if ($day['start'] == strtotime("midnight +0 day")) {
						echo "\t\t\t\t\t\t<div class=\"day-name\">Today</div>\n";
					} else if ($day['start'] == strtotime("midnight +1 day")) {
						echo "\t\t\t\t\t\t<div class=\"day-name\">Tomorrow</div>\n";
					} else {
						echo "\t\t\t\t\t\t<div class=\"day-name\">" . date('l', $day['start']) . ", ";
						if (date('Y', $day['start']) != date('Y')) {
							$format_str = 'M j, Y';
						} else {
							$format_str = 'M j';
						}
						echo "<span class=\"dark\">" . date($format_str, $day['start']) . "</span></div>\n";
					}
					echo "\t\t\t\t\t\t<ul>\n";
					foreach($day['events'] as $event) {
						echo "\t\t\t\t\t\t\t<li style=\"color: " . $event['color'] . ";\">\n";
						echo "\t\t\t\t\t\t\t\t<div class=\"time-container\">\n";
						if ($event['start'] <= $day['start'] and $event['end'] >= $day['end']) {
							echo "\t\t\t\t\t\t\t\t\t<div class=\"minute-container\">\n";
							echo "\t\t\t\t\t\t\t\t\t\t<span class=\"ampm\">ALL<br />DAY</span>\n";
							echo "\t\t\t\t\t\t\t\t\t</div>\n";
						} else {
							if ($event['start'] < $day['start']) {
								echo "\t\t\t\t\t\t\t\t\t<div class=\"minute-container\">\n";
								echo "\t\t\t\t\t\t\t\t\t\t<span class=\"minute\">00</span>\n";
								echo "\t\t\t\t\t\t\t\t\t\t<br />\n";
								echo "\t\t\t\t\t\t\t\t\t\t<span class=\"ampm\">AM</span>\n";
								echo "\t\t\t\t\t\t\t\t\t</div>\n";
								echo "\t\t\t\t\t\t\t\t\t<div class=\"hour\">12</div>\n";
							} else {
								echo "\t\t\t\t\t\t\t\t\t<div class=\"minute-container\">\n";
								echo "\t\t\t\t\t\t\t\t\t\t<span class=\"minute\">" . date('i', $event['start']) . "</span>\n";
								echo "\t\t\t\t\t\t\t\t\t\t<br />\n";
								echo "\t\t\t\t\t\t\t\t\t\t<span class=\"ampm\">" . date('A', $event['start']) . "</span>\n";
								echo "\t\t\t\t\t\t\t\t\t</div>\n";
								echo "\t\t\t\t\t\t\t\t\t<div class=\"hour\">" . date('g', $event['start']) . "</div>\n";
							}
						}
						echo "\t\t\t\t\t\t\t\t</div>\n";
						echo "\t\t\t\t\t\t\t\t<div class\"event-summary\">\n";
						echo "\t\t\t\t\t\t\t\t\t" . $event['summary'];
						if ($event['num-days'] > 1) {
							$day_num = 1 + (new DateTime(date('Y-m-d', $day['start'])))->diff(new DateTime(date('Y-m-d', $event['start'])))->format('%a');
							echo " (" . $day_num . "/" . $event['num-days'] . ")\n";
						} else {
							echo "\n";
						}
						if ($event['start'] <= $day['start'] and $event['end'] >= $day['end']) {
							$x = '';  //  NOOP, "ALL DAY" events don't display end times
						} else if ($event['end'] > $day['end'] + 1) {
							$x = '';  //  NOOP, events that don't end today don't show end times
						} else {
							if (isset($event['location'])) {
								$event['location'] = "until " . date('g:ia', $event['end']) . " " . $event['location'];
							} else {
								$event['location'] = "until " . date('g:ia', $event['end']);
							}
						}
						if (isset($event['location'])) {
							echo "\t\t\t\t\t\t\t\t\t<span class=\"location\">" . ucfirst($event['location']) . "</span>\n";
						}
						echo "\t\t\t\t\t\t\t\t</div>\n";
					}
					echo "\t\t\t\t\t\t\t</li>\n";
					echo "\t\t\t\t\t\t</ul>\n";
					echo "\t\t\t\t\t</div>\n";
					echo "\t\t\t\t</div>\n";
					$day_ctr = $day_ctr + 1;
					if ($day_ctr >= $max_days) {
						break;
					}
				}
			}
		}
		
		/*
		 * Select a random photo fom the $background_images_path folder to use
		 * as the page background image
		 */
		public function randomImage() {
			$background_images_path = 'backgrounds';
			
			$background_images = glob($background_images_path . '/*.{jpg,jpeg,png,gif}', GLOB_BRACE);
			return($background_images[array_rand($background_images)]);
		}
	}
?>
