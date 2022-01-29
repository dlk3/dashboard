//  Draws a progress bar across the top of the page
function updateProgressBarWidth(refresh_seconds, start_seconds = 0) {
	if (start_seconds == 0) {
		start_seconds = new Date().getTime() / 1000;
	}
	var page_width = 0;
	var body = window.document.body;
	if (window.innerWidth) {
		page_width = window.innerWidth;
	} else if (body.parentElement.clientWidth) {
		page_width = body.parentElement.clientWidth;
	} else if (body && body.clientWidth) {
		page_width = body.clientWidth;
	}
	
	var seconds = (new Date().getTime() / 1000) - start_seconds;
	var width = page_width / refresh_seconds * seconds
	document.getElementById("progress-bar").style.width = width + 'px';
	
	setTimeout(function() {
		updateProgressBarWidth(refresh_seconds, start_seconds);
	}, 250);
}

//  Updates the time/date text clock on page
function updateTimeDate() {
	var now = new Date();
	
	var hours = now.getHours();
	var minutes = now.getMinutes();
	var seconds = now.getSeconds();
	var ampm = ' AM';
	if (hours >= 12) {
		ampm = ' PM';
		if (hours > 12) {
			hours = hours - 12;
		}
	}
	if (hours == 0) {
		hours = 12;
	}
	var timestr = hours + ":";
	if (minutes < 10) {
		timestr = timestr + "0" + minutes + ":";
	} else {
		timestr = timestr + minutes + ":";
	}
	if (seconds < 10) {
		timestr = timestr + "0" + seconds + ampm;
	} else {
		timestr = timestr + seconds + ampm;
	}
	document.getElementById('clock-time').innerHTML = timestr;
	
	var weekdays = [ 'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday' ];
	var months = [ 'January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December' ];
	datestr = weekdays[now.getDay()] + ', ' + months[now.getMonth()] + ' ' + now.getDate() + ', ' + now.getFullYear();
	document.getElementById('clock-date').innerHTML = datestr;

	setTimeout(function() {
		updateTimeDate();
	}, 1000);
}

//  Set the height of the schedule section to fit the page
function resizeSchedule() {
	var height = 0;
	var body = window.document.body;
	if (window.innerHeight) {
		height = window.innerHeight;
	} else if (body.parentElement.clientHeight) {
		height = body.parentElement.clientHeight;
	} else if (body && body.clientHeight) {
		height = body.clientHeight;
	}
	height = height - document.getElementById("clock-time").offsetHeight;
	height = height - document.getElementById("weather").offsetHeight;
	document.getElementById("schedule").style.height = height + "px";
}

//  Things to do after the page is fully loaded (the DOM has been established)
window.addEventListener('load', function() {
	//  Set the background image
	document.getElementById("backgroundImage").style.backgroundImage = 'url("' + background_image_url + '")';
	
	//  Get the weather data and populate the weather section of the page
	var xmlHttp = new XMLHttpRequest();
	xmlHttp.onreadystatechange = function() { 
		if (xmlHttp.readyState == 4 && xmlHttp.status == 200) {
			//  Populate the weather section of the page
			populateWeather(JSON.parse(xmlHttp.responseText));
			//  Resize the schedule section of the page to fit
			resizeSchedule();
		}
	}
	xmlHttp.open("GET", weather_data_url, true);
	xmlHttp.send(null);
}, false);

//  Populate the weather section of the page
function populateWeather(weather_data) {
	
	// console.log(weather_data);
	
	//  Populate the current-conditions div with weather_data
	document.getElementById("current-icon").src = weather_data.current.icon;
	
	var temp_trend = "";
	if (weather_data.current.temperature != null) {
		if (weather_data.current.temperature > weather_data.hourly[1].temperature) {
			temp_trend = " and falling";
		} else if (weather_data.current.temperature < weather_data.hourly[1].temperature) {
			temp_trend = " and rising";
		} else if (weather_data.current.temperature == weather_data.hourly[1].temperature) {
			temp_trend = " and steady";
		}
	} else {
		weather_data.current.temperature = '?';
	}
	if (weather_data.current.wind_speed === null) weather_data.current.wind_speed = '?';
	var html_str = "<strong>" + weather_data.current.temperature + "&deg;</strong>" +
		"<span>" + temp_trend + "</span>" +
		"<span>Wind: " + weather_data.current.wind_speed + " mph</span>";
	document.getElementById("current-temp").innerHTML = html_str;
	
	if (weather_data.current.description === null) weather_data.current.description = "";
	if (weather_data.current.shortForecast === null) weather_data.current.shortForecast = "";
	html_str = "<p>" + weather_data.current.description + "</p><p>" + weather_data.current.shortForecast + "</p>";
	document.getElementById("current-summary").innerHTML = html_str;
	
	//  Populate the daily forecast blocks
	html_str = "";
	//  Get the current date/time and the offset from UTC in microseconds
	var now = new Date();
	var offset = now.getTimezoneOffset() * 60000;
	weather_data.daily.forEach(function (day) {
		//  Only include those days that have data, i.e. have an icon set
		if (day.icon != null) {
			//  Get the 3-letter abbreviation for the day name for this date
			var date = new Date(day.date); 
			//  Have to adjust the date to the timezone used by this server for this to work properly
			date = new Date(date.getTime() + offset);
			day_name = date.toLocaleDateString('en-US', {weekday: 'short'})
			//  If it's today then say "TODAY"
			if ((date.getDate() == now.getDate()) && (date.getDay() == now.getDay())) {
				day_name = "TODAY";
			}
			//  Check for what's missing
			if (day.icon == null) day.icon = '';
			if (day.high == null) {
				day.high = '&nbsp;';
			} else {
				day.high = day.high + "&deg;";
			}
			if (day.low == null) {
				day.low = '&nbsp;';
			} else {
				day.low = day.low + "&deg;";
			}
			//  Append the markup for this day
			html_str = html_str + "<div class=\"daily-forecast\">" +
				"<p class=\"day-name\">" + day_name + "</p>" +
				"<img width=\"80\" height=\"80\" src=\"" + day.icon + "\" alt=\"weather Icon\">" +
				"<p class=\"high-temp\">" + day.high + "</p>" +
				"<p class=\"low-temp\">" + day.low + "</p>" +
				"</div>";
		}
	});
	document.getElementById("daily").innerHTML = html_str;

	//  Populate the alerts section
	html_str = ''
	weather_data.alerts.forEach(function (alert) {
		html_str = html_str + "<div class=\"alert\"><p>" + alert.headline + "</p></div>";
	});
	document.getElementById("alerts").innerHTML = html_str;
	
	//  Populate the data arrays used by the graphs and collect the min/max
	//  values which we'll use to control their axis scales
	var temp_points = [];
	var precip_points = [];
	var wind_points = [];
	var min_temp = 1000;
	var max_temp = 0;
	var min_wind = 1000;
	var max_wind = 0;
	var min_precip = 1000;
	var max_precip = 0;
	weather_data.hourly.forEach(function (hour) {
		var time = new Date(hour.time);
		temp_points.push({
			x: time, 
			y: hour.temperature
		});
		wind_points.push({
			x: time, 
			y: hour.windSpeed
		});
		precip_points.push({
			x: time, 
			y: hour.precipPercent
		});
		if (hour.temperature < min_temp) min_temp = hour.temperature;
		if (hour.temperature > max_temp) max_temp = hour.temperature;
		if (hour.windSpeed < min_wind) min_wind = hour.windSpeed;
		if (hour.windSpeed > max_wind) max_wind = hour.windSpeed;
		if (hour.precipPercent < min_precip) min_precip = hour.precipPercent;
		if (hour.precipPercent > max_precip) max_precip = hour.precipPercent;
	});
	min_temp = Math.floor(min_temp / 10) * 10;
	max_temp = Math.round(max_temp / 10) * 10 + 5;
	min_precip = Math.floor(min_precip / 10) * 10;
	if (min_precip < 0) min_precip = 0;
	max_precip = Math.round(max_precip / 10) * 10 + 5;
	if (max_precip > 100) max_precip = 100;
	if (max_precip < 40) max_precip = 40;
	min_wind = Math.floor(min_wind / 10) * 10;
	if (min_wind < 0) min_wind = 0;
	max_wind = Math.round(max_wind / 10) * 10 + 5;
	if (max_wind < 20) $max_wind = 20;

	//  Set striplines values for graphs based on sunrise/sunset times
	var striplines = [];
	if (new Date(weather_data.hourly[0].time) < new Date(weather_data.daily[0].sunrise)) {
		striplines[0] = new Date(0);
		striplines[1] = new Date(weather_data.daily[0].sunrise);
		striplines[2] = new Date(weather_data.daily[0].sunset);
		striplines[3] = new Date(weather_data.daily[1].sunrise);
		striplines[4] = new Date(weather_data.daily[1].sunset);
		striplines[5] = new Date(weather_data.daily[2].sunrise);
	} else {
		striplines[0] = new Date(weather_data.daily[0].sunset);
		striplines[1] = new Date(weather_data.daily[1].sunrise);
		striplines[2] = new Date(weather_data.daily[1].sunset);
		striplines[3] = new Date(weather_data.daily[2].sunrise);
		striplines[4] = new Date(weather_data.daily[2].sunset);
		striplines[5] = new Date(weather_data.daily[3].sunrise);
	}
	
	//  Define additional colors not defined elsewhere
	var black = "#000000";
	var gray = "rgba(0, 0, 0, .4)";

	//  Create the Temperature and Precipitation Probability graph
	var tempAndPrecipChart = new CanvasJS.Chart('tempAndPrecipChart', {
		title: {
			text: "Temperature and Precipitation Probability - Hourly Forecast",
			fontColor: white,
			fontSize: 40
		},
		backgroundColor: "rgba(0, 0, 0, 0)",
		data: [
			{
				type: 'line',
				dataPoints: temp_points,
				markerType: null,
				lineThickness: 7,
				lineColor: green,
				axisYType: 'primary'
			},
			{
				type: 'line',
				dataPoints: precip_points,
				markerType: null,
				lineThickness: 7,
				lineColor: blue,
				axisYType: 'secondary'
			}
		],
		axisX: {
			// Sunrise/sunset
			stripLines: [
				{
					startValue: striplines[0],
					endValue: striplines[1],
					color: 'gray'
				},
				{
					startValue: striplines[2],
					endValue: striplines[3],
					color: 'gray'
				},
				{
					startValue: striplines[4],
					endValue: striplines[5],
					color: 'gray'
				}
			],
			titleFontColor: white,
			labelFontColor: white,
			lineColor: white,
			tickColor: white
		},
		axisY: {
			valueFormatString: "#0'\xB0'",
			includeZero: false,
			minimum: min_temp,
			// maximum: max_temp,
			labelFontColor: green,
			labelFontSize: 30,
			lineColor: green,
			tickColor: green,
			gridColor: green
		},
		axisY2: {
			valueFormatString: "#0'%'",
			includeZero: false,
			minimum: min_precip,
			// maximum: max_precip,
			labelFontColor: blue,
			labelFontSize: 30,
			lineColor: blue,
			tickColor: blue
		}
	});

	//  Create the Wind Speed graph
	var windChart = new CanvasJS.Chart('windChart', {
		title: {
			text: "Wind Speed - Hourly Forecast",
			fontColor: white,
			fontSize: 40
		},
		backgroundColor: "rgba(0, 0, 0, 0)",
		data: [
			{
				type: 'line',
				dataPoints: wind_points,
				markerType: null,
				lineThickness: 7,
				lineColor: white
			}
		],
		axisX: {
			//  Sunrise/sunset
			stripLines: [
				{
					startValue: striplines[0],
					endValue: striplines[1],
					color: 'gray'
				},
				{
					startValue: striplines[2],
					endValue: striplines[3],
					color: 'gray'
				},
				{
					startValue: striplines[4],
					endValue: striplines[5],
					color: 'gray'
				}
			],
			titleFontColor: white,
			labelFontColor: white,
			lineColor: white,
			tickColor: white
		},
		axisY: {
			valueFormatString: "#0'mph'",
			includeZero: false,
			minimum: min_wind,
			// maximum: max_wind,
			labelFontColor: white,
			labelFontSize: 30,
			lineColor: white,
			tickColor: white,
			gridColor: white
		},
	});
	
	//  Unhide the weather section of the page
	document.getElementById('weather').style.display = 'block';
	
	/*  We're not scrolling alerts for now

	//  Set the speed of any weather alert marquee lines based on their lengths.
	//  (Doesn't work when weather div is hidden, elements[i].offsetWidth = 0 then.)
	var speed = 150;   // pixels/second  
	var element_list = document.querySelectorAll('.alert p');
	for (var i = 0; i < element_list.length; i++) {
		var stringLength = element_list[i].offsetWidth;
		var timeTaken = stringLength / speed;
		element_list[i].style.animationDuration = timeTaken + "s";
	}

	*/

	//  Render the graphs
	tempAndPrecipChart.render();
	windChart.render();	
}
