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
				$start = new DateTimeImmutable("midnight +" . $i . " day");
				$end = new DateTimeImmutable("midnight +" . ($i + 1) . " day - 1 second");
				$days[$i] = array (
					'start' => $start->getTimeStamp(),
					'end' => $end->getTimestamp(),
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

					if ($ical->eventCount > 0) {
						foreach ($ical->cal['VEVENT'] as $event) {
							//print_r($event);
							$dtstart = new DateTimeImmutable($event['DTSTART']);
							if (isset($event['DTEND'])) {
								$dtend = new DateTimeImmutable($event['DTEND']);
							} else {
								$dtend = $dtstart;
							}
							$midnight = new DateTimeImmutable("midnight +0 day");
							if ($dtend->getTimestamp() >= $midnight->getTimestamp()) {
								for ($i = 0; $i < 365; $i++) {
									if ($dtstart->getTimestamp() > $days[$i]['end']) {
										continue;
									} 
									if ($dtend->getTimestamp() <= $days[$i]['start']) {
										continue;
									}
									$num_days = $dtstart->diff($dtend)->format('%r%a');
									if ($num_days == 0) {
										$num_days = 1;
									} else {
										#  If this is an all day event (midnight to midnight)
										$start_date = new DateTimeImmutable($dtstart->format('Y-m-d') . " +1 day");
										$end_date = new DateTimeImmutable($dtend->format('Y-m-d'));
										if ($start_date == $end_date) {
											$num_days = 1;
										}
									}
									$new_event = array(
										'start' => $dtstart->getTimestamp(),
										'end' => $dtend->getTimestamp(),
										'summary' => str_replace('\\', '', str_replace('\n', ', ', $event['SUMMARY'])),
										'color' => $source['color'],
										'num_days' => $num_days
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
					$day_date = (new DateTimeImmutable)->setTimeStamp($day['start'])->format('Y-m-d');
					$today_date = (new DateTimeImmutable("midnight +0 day"))->format('Y-m-d');
					$tomorrow_date = (new DateTimeImmutable("midnight +1 day"))->format('Y-m-d');
					if ($day_date == $today_date) {
						echo "\t\t\t\t\t\t<div class=\"day-name\">Today</div>\n";
					} else if ($day_date == $tomorrow_date) {
						echo "\t\t\t\t\t\t<div class=\"day-name\">Tomorrow</div>\n";
					} else {
						$day_name = (new DateTimeImmutable)->setTimeStamp($day['start'])->format('l');
						echo "\t\t\t\t\t\t<div class=\"day-name\">" . $day_name . ", ";
						if ((new DateTimeImmutable($day_date))->format('Y') != (new DateTimeImmutable($today_date))->format('Y')) {
							$format_str = 'M j, Y';
						} else {
							$format_str = 'M j';
						}
						echo "<span class=\"dark\">" . (new DateTimeImmutable($day_date))->format($format_str) . "</span></div>\n";
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
								$dtstart = (new DateTimeImmutable)->setTimestamp($event['start']);
								echo "\t\t\t\t\t\t\t\t\t<div class=\"minute-container\">\n";
								echo "\t\t\t\t\t\t\t\t\t\t<span class=\"minute\">" . $dtstart->format('i') . "</span>\n";
								echo "\t\t\t\t\t\t\t\t\t\t<br />\n";
								echo "\t\t\t\t\t\t\t\t\t\t<span class=\"ampm\">" . $dtstart->format('A') . "</span>\n";
								echo "\t\t\t\t\t\t\t\t\t</div>\n";
								echo "\t\t\t\t\t\t\t\t\t<div class=\"hour\">" . $dtstart->format('g') . "</div>\n";
							}
						}
						echo "\t\t\t\t\t\t\t\t</div>\n";
						echo "\t\t\t\t\t\t\t\t<div class\"event-summary\">\n";
						echo "\t\t\t\t\t\t\t\t\t" . $event['summary'];
						if ($event['num_days'] > 1) {
							$day_start = new DateTimeImmutable($day_date);
							$dtstart = (new DateTimeImmutable)->setTimeStamp($event['start']);
							$day_num = $dtstart->diff($day_start)->format('%r%a') + 1;
							echo " (" . $day_num . "/" . $event['num_days'] . ")\n";
						} else {
							echo "\n";
						}
						if ($event['start'] <= $day['start'] and $event['end'] >= $day['end']) {
							$x = '';  //  NOOP, "ALL DAY" events don't display end times
						} else if ($event['end'] > $day['end'] + 1) {
							$x = '';  //  NOOP, events that don't end today don't show end times
						} else {
							if (isset($event['location'])) {
								$event['location'] = "until " . (new DateTimeImmutable)->setTimestamp($event['end'])->format('g:ia') . " " . $event['location'];
							} else {
								$event['location'] = "until " . (new DateTimeImmutable)->setTimestamp($event['end'])->format('g:ia');
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
